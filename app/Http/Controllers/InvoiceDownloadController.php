<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class InvoiceDownloadController extends Controller
{
    /**
     * Signed, un-authenticated download — this is the URL sent to WhatsApp
     * so Meta's servers can fetch the PDF to attach it. Valid for 7 days
     * (set when the signed URL is generated in InvoiceService).
     */
    public function signed(Invoice $invoice)
    {
        abort_unless(Storage::disk('local')->exists($invoice->pdf_path), 404);

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }

    /**
     * Authenticated download — owner can only download their own invoices,
     * admin can download any.
     */
    public function download(Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($user->role === 'admin' || $invoice->owner_id === $user->id, 403);
        abort_unless(Storage::disk('local')->exists($invoice->pdf_path), 404);

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
