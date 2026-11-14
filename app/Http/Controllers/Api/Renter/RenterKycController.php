<?php

namespace App\Http\Controllers\Api\Renter;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\SetuDigilockerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * KYC documents of the signed-in tenant: list, upload, delete, send for review.
 *
 * Same rules and the same storage as the website (so the owner sees them where they always did),
 * but the app never receives a public link: a document is only readable through an endpoint that
 * checks it belongs to the signed-in tenant.
 */
class RenterKycController extends Controller
{
    /** type => [label, required for submit] */
    public const TYPES = [
        'aadhaar_front' => 'Aadhaar (front)',
        'aadhaar_back' => 'Aadhaar (back)',
        'pan' => 'PAN card',
        'photo' => 'Your photo',
        'id_card' => 'College / office ID',
        'address_proof' => 'Address proof',
        // not part of KYC: the owner or the tenant uploads it, and it changes the police verification status
        'police_verification' => 'Police verification (PDF or photo)',
    ];

    private const POLICE = 'police_verification';

    private const MIN_TO_SUBMIT = 3;
    private const MAX_KB = 5120;

    private function tenantOf(Request $request): ?Tenant
    {
        return Tenant::where('user_id', $request->user()->id)->orderByRaw("status = 'active' DESC")->orderByDesc('id')->first();
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

    private function payload(Tenant $tenant): array
    {
        $docs = TenantDocument::where('tenant_id', $tenant->id)->get()->keyBy('document_type');

        return [
            'police' => [
                'status' => $tenant->police_verification_status ?: 'pending',
                'uploaded' => $docs->has(self::POLICE),
            ],
            'aadhaar' => [
                'verified' => !empty($tenant->aadhaar_verified_at),
                'masked' => $tenant->aadhaar_number_masked,
                'name' => $tenant->aadhaar_name,
                // false until Pizi has its Setu Aadhaar product switched on
                'available' => app(SetuDigilockerService::class)->isConfigured(),
            ],
            'status' => $tenant->kyc_status ?: 'pending',
            'remarks' => $tenant->kyc_status === 'rejected' ? ($tenant->kyc_remarks ?? null) : null,
            'min_to_submit' => self::MIN_TO_SUBMIT,
            'can_change' => $tenant->kyc_status !== 'approved',
            'documents' => collect(self::TYPES)->map(fn ($label, $type) => [
                'type' => $type,
                'label' => $label,
                // an approved KYC is locked, but the police document can still be added or replaced
                'can_change' => $type === self::POLICE || $tenant->kyc_status !== 'approved',
                'uploaded' => $docs->has($type),
                'file_name' => $docs->get($type)?->file_name,
                'uploaded_at' => $docs->get($type)?->created_at?->toIso8601String(),
                'is_image' => $docs->has($type) && !str_ends_with(strtolower((string) $docs->get($type)->file_path), '.pdf'),
            ])->values(),
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);

        return $tenant ? $this->ok($this->payload($tenant)) : $this->noTenant();
    }

    public function upload(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        $data = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            // mimes checks the real content of the file, not just its name
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . self::MAX_KB,
        ]);

        if ($tenant->kyc_status === 'approved' && $data['type'] !== self::POLICE) {
            return $this->fail('Your KYC is approved, documents cannot be changed.', 409, 'kyc_approved');
        }

        $file = $request->file('file');

        // replace the earlier document of this type
        $old = TenantDocument::where('tenant_id', $tenant->id)->where('document_type', $data['type'])->first();
        if ($old) {
            if ($old->file_path && Storage::disk('public')->exists($old->file_path)) {
                Storage::disk('public')->delete($old->file_path);
            }
            $old->delete();
        }

        // store() picks a random file name, so the path cannot be guessed
        $path = $file->store('tenant-kyc/' . $tenant->id, 'public');
        TenantDocument::create([
            'tenant_id' => $tenant->id,
            'document_type' => $data['type'],
            'file_path' => $path,
            'file_name' => mb_substr(preg_replace('/[^\w.\- ]+/u', '_', $file->getClientOriginalName()), 0, 120),
        ]);

        if ($data['type'] === self::POLICE) {
            $tenant->forceFill(['police_verification_status' => 'done'])->save();

            return $this->ok($this->payload($tenant->fresh()), 201);
        }

        // a rejected KYC goes back to the owner for a new look once something is uploaded
        if ($tenant->kyc_status === 'rejected') {
            $tenant->update(['kyc_status' => 'pending']);
        }

        return $this->ok($this->payload($tenant->fresh()), 201);
    }

    public function destroy(Request $request, string $type): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if (!array_key_exists($type, self::TYPES)) {
            return $this->fail('Unknown document type.', 404, 'not_found');
        }
        if ($tenant->kyc_status === 'approved' && $type !== self::POLICE) {
            return $this->fail('Your KYC is approved, documents cannot be deleted.', 409, 'kyc_approved');
        }

        $doc = TenantDocument::where('tenant_id', $tenant->id)->where('document_type', $type)->first();
        if (!$doc) {
            return $this->fail('Document not found.', 404, 'not_found');
        }
        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        if ($type === self::POLICE) {
            $tenant->forceFill(['police_verification_status' => 'pending'])->save();
        }

        return $this->ok($this->payload($tenant->fresh()));
    }

    /** The document itself, for the signed-in tenant only. */
    public function file(Request $request, string $type)
    {
        $tenant = $this->tenantOf($request);
        $doc = ($tenant && array_key_exists($type, self::TYPES))
            ? TenantDocument::where('tenant_id', $tenant->id)->where('document_type', $type)->first()
            : null;

        if (!$doc || !$doc->file_path || !Storage::disk('public')->exists($doc->file_path)) {
            return $this->fail('Document not found.', 404, 'not_found');
        }

        return Storage::disk('public')->response($doc->file_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ─── Aadhaar check through DigiLocker (Setu) ────────────────────────────────────────────
    // The tenant signs in to DigiLocker on Setu's page and agrees to share their Aadhaar. Nothing is typed
    // in our app and no Aadhaar number ever reaches our server.

    private function digilocker(): SetuDigilockerService
    {
        return app(SetuDigilockerService::class);
    }

    /** The request this tenant started; the server remembers which person each Setu request belongs to. */
    private function pendingKey(int $userId): string
    {
        return 'renter:digilocker:' . $userId;
    }

    private function aadhaarGuard(Request $request): ?JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if (!$this->digilocker()->isConfigured()) {
            return $this->fail('Aadhaar check is not switched on yet. Please upload your Aadhaar photos instead.', 503, 'aadhaar_unavailable');
        }
        if (!empty($tenant->aadhaar_verified_at)) {
            return $this->fail('Your Aadhaar is already verified.', 409, 'already_verified');
        }

        return null;
    }

    /** Step 1: get the DigiLocker page for this tenant to open. */
    public function aadhaarStart(Request $request): JsonResponse
    {
        if ($blocked = $this->aadhaarGuard($request)) {
            return $blocked;
        }

        $result = $this->digilocker()->createRequest(route('renter.digilocker.return'));
        if (!$result['success']) {
            return $this->fail($result['message'] ?? 'Could not start the Aadhaar check.', 502, 'aadhaar_failed');
        }

        $userId = $request->user()->id;
        Cache::put($this->pendingKey($userId), $result['id'], now()->addMinutes(30));
        // so the page DigiLocker sends the browser back to can finish the check for the right person
        Cache::put('digilocker:request:' . $result['id'], $userId, now()->addMinutes(30));

        return $this->ok(['url' => $result['url'], 'valid_upto' => $result['valid_upto']]);
    }

    /** Step 2: the tenant is back from DigiLocker; ask Setu what happened and record it. */
    public function aadhaarCheck(Request $request): JsonResponse
    {
        if ($blocked = $this->aadhaarGuard($request)) {
            return $blocked;
        }
        $id = Cache::get($this->pendingKey($request->user()->id));
        if (!$id) {
            return $this->fail('This check has expired. Please start again.', 410, 'expired');
        }

        $tenant = $this->tenantOf($request);
        $outcome = $this->finishAadhaar($tenant, (string) $id);
        if ($outcome === 'pending') {
            return $this->ok(['pending' => true, 'verified' => false]);
        }
        if ($outcome !== 'done') {
            return $this->fail($outcome, 422, 'aadhaar_rejected');
        }

        return $this->ok($this->payload($tenant->fresh()) + ['pending' => false]);
    }

    /**
     * Asks Setu (server to server, so nobody can fake it from outside) whether this DigiLocker request
     * is complete and, if so, records the Aadhaar details and marks KYC approved.
     *
     * @return string 'done' | 'pending' | a message to show the tenant
     */
    public function finishAadhaar(Tenant $tenant, string $requestId): string
    {
        $dl = $this->digilocker();
        $status = $dl->status($requestId);
        if (!$status['success']) {
            return $status['message'] ?? 'The Aadhaar check could not be completed.';
        }
        if (($status['status'] ?? '') === 'revoked') {
            return 'This check has expired. Please start again.';
        }
        if (($status['status'] ?? '') !== 'authenticated') {
            return 'pending';
        }

        $result = $dl->aadhaar($requestId);
        if (!$result['success']) {
            return $result['message'] ?? 'The Aadhaar check could not be completed.';
        }
        $d = $result['data'];

        $update = [
            'aadhaar_number_masked' => $d['masked_aadhaar'] ?: 'XXXX-XXXX-XXXX',
            'aadhaar_name' => $d['name'],
            'aadhaar_verified_at' => now(),
            // Aadhaar checked through the government's DigiLocker: nothing more to upload or approve
            'kyc_status' => 'approved',
            'kyc_remarks' => 'Verified with Aadhaar through DigiLocker (Setu)',
        ];
        // fill what the person left blank from Aadhaar; never overwrite what they or the owner entered
        foreach (['dob' => 'dob', 'gender' => 'gender', 'address_line' => 'address', 'city' => 'city', 'state' => 'state', 'pincode' => 'pincode'] as $col => $key) {
            if (empty($tenant->{$col}) && !empty($d[$key])) {
                $update[$col] = $d[$key];
            }
        }
        $tenant->forceFill($update)->save();

        \DB::table('users')->where('id', $tenant->user_id)->update([
            'journey_stage' => \DB::raw('GREATEST(COALESCE(journey_stage, 0), 2)'),
            'updated_at' => now(),
        ]);
        Cache::forget($this->pendingKey((int) $tenant->user_id));
        Cache::forget('digilocker:request:' . $requestId);

        return 'done';
    }

    /** The page DigiLocker sends the tenant's browser to when they are done: records the result, then says "go back to the app". */
    public function landing(Request $request)
    {
        $id = (string) $request->query('id', '');
        $failed = strtolower((string) $request->query('success', '')) === 'false';
        $done = false;

        if ($id !== '' && !$failed && $this->digilocker()->isConfigured()) {
            $userId = Cache::get('digilocker:request:' . $id);
            $tenant = $userId ? Tenant::where('user_id', $userId)->orderByRaw("status = 'active' DESC")->orderByDesc('id')->first() : null;
            if ($tenant && empty($tenant->aadhaar_verified_at)) {
                $done = $this->finishAadhaar($tenant, $id) === 'done';
            } elseif ($tenant) {
                $done = true;
            }
        }

        return response()->view('tenant.digilocker-return', ['done' => $done, 'failed' => $failed]);
    }

    public function submit(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if ($tenant->kyc_status === 'approved') {
            return $this->fail('Your KYC is already approved.', 409, 'kyc_approved');
        }
        if (TenantDocument::where('tenant_id', $tenant->id)->where('document_type', '!=', self::POLICE)->count() < self::MIN_TO_SUBMIT) {
            return $this->fail('Please upload at least ' . self::MIN_TO_SUBMIT . ' documents before submitting.', 422, 'too_few_documents');
        }

        $tenant->update(['kyc_status' => 'submitted']);

        try {
            $tenant->owner?->notify(new \App\Notifications\KycSubmitted($tenant));
        } catch (\Throwable $e) {
            \Log::warning('KYC notification failed: ' . $e->getMessage());
        }

        return $this->ok($this->payload($tenant->fresh()));
    }
}
