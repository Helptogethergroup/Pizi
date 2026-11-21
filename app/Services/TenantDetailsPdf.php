<?php

namespace App\Services;

use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * The tenant's details page as a PDF: one copy for the owner's records and one the owner can send to the tenant.
 * The tenant copy leaves out the owner's private notes and carries the tenant app's Play Store link.
 * The caller has already checked that the signed-in person may see this tenant.
 */
class TenantDetailsPdf
{
    /** @return array{0: string, 1: string} file name and the PDF bytes */
    public function make(Tenant $tenant, bool $forOwner): array
    {
        $tenant->loadMissing('documents', 'property.city', 'property.locality');

        $bytes = Pdf::loadView('pdf.tenant-details', ['tenant' => $tenant, 'forOwner' => $forOwner])
            ->setPaper('a4')
            ->output();

        $name = 'Tenant-' . Str::slug($tenant->name ?: 'details') . ($forOwner ? '-owner-copy' : '-tenant-copy') . '.pdf';

        return [$name, $bytes];
    }
}
