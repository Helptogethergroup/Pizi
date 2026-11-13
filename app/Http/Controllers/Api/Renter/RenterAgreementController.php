<?php

namespace App\Http\Controllers\Api\Renter;

use App\Http\Controllers\Controller;
use App\Models\RentAgreement;
use App\Models\Tenant;
use App\Services\SetuEsignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The tenant's rental agreement (read, download, e-sign with Aadhaar through Setu) and the
 * notice to leave the PG. Always the signed-in tenant's own records.
 */
class RenterAgreementController extends Controller
{
    private function tenantOf(Request $request): ?Tenant
    {
        return Tenant::where('user_id', $request->user()->id)->orderByRaw("status IN ('active','notice_period') DESC")->orderByDesc('id')->first();
    }

    private function ok($data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function fail(string $message, int $status, string $code): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code], $status);
    }

    private function noTenant(): JsonResponse
    {
        return $this->fail('Your PG owner has not added your stay yet.', 409, 'no_tenant');
    }

    /** The Setu e-sign service, or null while its keys are missing on this server. */
    private function esign(): ?SetuEsignService
    {
        try {
            return app(SetuEsignService::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function payload(Tenant $tenant): array
    {
        $agreements = RentAgreement::where('tenant_id', $tenant->id)->orderByDesc('id')->get()->map(fn ($a) => [
            'id' => $a->id,
            'agreement_number' => $a->agreement_number,
            'status' => $a->status,
            'start_date' => $a->start_date,
            'end_date' => $a->end_date,
            'monthly_rent' => (float) $a->monthly_rent,
            'security_deposit' => (float) $a->security_deposit,
            'signed_at' => $a->signed_at,
        ])->values();

        return [
            'has_tenant' => true,
            'signed' => !empty($tenant->agreement_signed_at),
            'signed_at' => $tenant->agreement_signed_at,
            'esign_status' => $tenant->esign_status,
            // false until Pizi's Setu e-sign keys are on the server
            'esign_available' => $this->esign() !== null,
            'agreements' => $agreements,
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);

        return $tenant ? $this->ok($this->payload($tenant)) : $this->ok(['has_tenant' => false, 'message' => 'Your PG owner has not added your stay yet.']);
    }

    /** The agreement document the owner created, as a PDF; only the tenant it belongs to can open it. */
    public function pdf(Request $request, int $id)
    {
        $tenant = $this->tenantOf($request);
        $agreement = $tenant
            ? RentAgreement::where('id', $id)->where('tenant_id', $tenant->id)->with('tenant', 'property', 'owner', 'signatures')->first()
            : null;
        if (!$agreement) {
            return $this->fail('Agreement not found.', 404, 'not_found');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('owner.agreements.preview', compact('agreement'))->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $agreement->agreement_number) . '.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Start e-signing: upload the agreement to Setu and hand back the page where the tenant signs with Aadhaar OTP. */
    public function signStart(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if (!empty($tenant->agreement_signed_at)) {
            return $this->fail('You have already signed the agreement.', 409, 'already_signed');
        }
        $setu = $this->esign();
        if (!$setu) {
            return $this->fail('Online signing is not switched on yet. Please sign the agreement with your PG owner.', 503, 'esign_unavailable');
        }

        $user = $request->user();
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.agreement-pdf', ['tenant' => $tenant, 'user' => $user])->setPaper('a4', 'portrait')->output();
            $uploaded = $setu->uploadDocumentBytes($pdf, 'agreement-' . $tenant->id . '-' . time() . '.pdf', 'Pizi Rental Agreement - ' . $user->name);
            $documentId = $uploaded['id'] ?? null;
            if (!$documentId) {
                return $this->fail('Could not prepare the agreement. Please try again.', 502, 'esign_failed');
            }

            $signer = [
                'identifier' => $user->phone,
                'displayName' => $user->name,
                'signature' => ['onPages' => ['1'], 'position' => 'bottom-left', 'height' => 60, 'width' => 180],
            ];
            if (!empty($tenant->dob)) {
                $signer['birthYear'] = (int) \Carbon\Carbon::parse($tenant->dob)->year;
            }

            $req = $setu->createSignatureRequest($documentId, $signer, route('renter.agreement.return'));
            $url = $req['signers'][0]['url'] ?? null;
            if (!$url || empty($req['id'])) {
                return $this->fail('Could not start signing. Please try again.', 502, 'esign_failed');
            }

            DB::table('tenants')->where('id', $tenant->id)->update(['esign_request_id' => $req['id'], 'esign_status' => 'pending', 'updated_at' => now()]);

            return $this->ok(['signer_url' => $url]);
        } catch (\Throwable $e) {
            \Log::error('[renter eSign] start failed: ' . $e->getMessage());

            return $this->fail('Signing is not available right now. Please try again later.', 502, 'esign_failed');
        }
    }

    /** After the tenant comes back from the signing page: ask Setu what happened and record it. */
    public function signRefresh(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if (empty($tenant->agreement_signed_at)) {
            $this->finalize($tenant, $request->ip());
        }

        return $this->ok($this->payload($tenant->fresh()));
    }

    /** The signed copy: a fresh, short-lived download link from Setu. */
    public function signedCopy(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant || empty($tenant->agreement_signed_at) || empty($tenant->esign_request_id)) {
            return $this->fail('There is no signed agreement yet.', 404, 'not_found');
        }
        $setu = $this->esign();
        if (!$setu) {
            return $this->fail('Online signing is not switched on yet.', 503, 'esign_unavailable');
        }
        try {
            $link = $setu->getSignedDocumentDownload($tenant->esign_request_id)['downloadUrl'] ?? null;
        } catch (\Throwable $e) {
            $link = null;
        }

        return $link ? $this->ok(['download_url' => $link]) : $this->fail('Could not get the signed copy right now.', 502, 'esign_failed');
    }

    /**
     * Asks Setu (server to server, so it cannot be faked from outside) whether the signature is
     * complete, and if so records it. Returns true when the agreement is now signed.
     */
    public function finalize(Tenant $tenant, ?string $ip = null): bool
    {
        if (!empty($tenant->agreement_signed_at)) {
            return true;
        }
        $setu = $this->esign();
        if (!$setu || empty($tenant->esign_request_id)) {
            return false;
        }

        try {
            $status = $setu->getSignatureStatus($tenant->esign_request_id);
            if (($status['status'] ?? '') !== 'sign_complete') {
                return false;
            }
            $download = $setu->getSignedDocumentDownload($tenant->esign_request_id);

            $tenant->forceFill([
                'agreement_signed_at' => now(),
                'agreement_ip' => $ip,
                'esign_status' => 'signed',
                'esign_signed_doc_url' => $download['downloadUrl'] ?? null,
            ])->save();

            DB::table('users')->where('id', $tenant->user_id)->update([
                'journey_stage' => DB::raw('GREATEST(COALESCE(journey_stage, 0), 3)'),
                'updated_at' => now(),
            ]);

            try {
                $tenant->owner?->notify(new \App\Notifications\AgreementSigned($tenant->fresh()));
            } catch (\Throwable $e) {
                \Log::warning('Agreement notification failed: ' . $e->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            \Log::error('[renter eSign] finalize failed: ' . $e->getMessage());

            return false;
        }
    }

    /** The page Setu sends the tenant's browser to after signing: records the result, then says "go back to the app". */
    public function landing(Request $request)
    {
        $id = (string) $request->query('id', '');
        $done = false;
        if ($id !== '' && $request->query('success') !== 'false') {
            $tenant = Tenant::where('esign_request_id', $id)->first();
            $done = $tenant ? $this->finalize($tenant, $request->ip()) : false;
        }

        return response()->view('tenant.agreement-return', ['done' => $done, 'failed' => $request->query('success') === 'false']);
    }

    // ─── notice to leave the PG ─────────────────────────────────────────────

    private function noticePayload(Tenant $tenant): array
    {
        return [
            'has_tenant' => true,
            'status' => $tenant->status,
            'notice_date' => $tenant->notice_date,
            'reason' => $tenant->notice_reason,
            'on_notice' => $tenant->status === 'notice_period',
            'can_submit' => $tenant->status === 'active',
        ];
    }

    public function noticeShow(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);

        return $tenant ? $this->ok($this->noticePayload($tenant)) : $this->ok(['has_tenant' => false, 'message' => 'Your PG owner has not added your stay yet.']);
    }

    public function noticeStore(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if ($tenant->status !== 'active') {
            return $this->fail($tenant->status === 'notice_period' ? 'You have already given your notice.' : 'You cannot give notice right now.', 409, 'cannot_give_notice');
        }

        $data = $request->validate([
            'notice_date' => 'required|date|after:today|before:' . now()->addDays(180)->toDateString(),
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant->forceFill([
            'notice_date' => $data['notice_date'],
            'notice_reason' => $data['reason'] ?? null,
            'status' => 'notice_period',
        ])->save();

        try {
            $tenant->owner?->notify(new \App\Notifications\NoticeSubmitted($tenant->fresh()));
        } catch (\Throwable $e) {
            \Log::warning('Notice notification failed: ' . $e->getMessage());
        }

        return $this->ok($this->noticePayload($tenant->fresh()), 201);
    }
}
