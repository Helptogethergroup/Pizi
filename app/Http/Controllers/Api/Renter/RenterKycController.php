<?php

namespace App\Http\Controllers\Api\Renter;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    ];

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
            'status' => $tenant->kyc_status ?: 'pending',
            'remarks' => $tenant->kyc_status === 'rejected' ? ($tenant->kyc_remarks ?? null) : null,
            'min_to_submit' => self::MIN_TO_SUBMIT,
            'can_change' => $tenant->kyc_status !== 'approved',
            'documents' => collect(self::TYPES)->map(fn ($label, $type) => [
                'type' => $type,
                'label' => $label,
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
        if ($tenant->kyc_status === 'approved') {
            return $this->fail('Your KYC is approved, documents cannot be changed.', 409, 'kyc_approved');
        }

        $data = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            // mimes checks the real content of the file, not just its name
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . self::MAX_KB,
        ]);

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
        if ($tenant->kyc_status === 'approved') {
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

    public function submit(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        if ($tenant->kyc_status === 'approved') {
            return $this->fail('Your KYC is already approved.', 409, 'kyc_approved');
        }
        if (TenantDocument::where('tenant_id', $tenant->id)->count() < self::MIN_TO_SUBMIT) {
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
