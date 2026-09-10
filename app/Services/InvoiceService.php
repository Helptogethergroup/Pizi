<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class InvoiceService
{
    public function __construct(protected WhatsAppService $whatsApp)
    {
    }

    /**
     * Create an invoice (GST-inclusive $totalAmount) and render its PDF.
     *
     * @param array $data ['title', 'total_amount', 'owner_id'|'owner', 'payment_id'?, 'credit_package_id'?,
     *                     'type'?, 'created_by'?, 'notes'?, 'gst_rate'?]
     */
    public function generate(array $data): Invoice
    {
        $owner = $data['owner'] ?? User::findOrFail($data['owner_id']);
        $gstRate = $data['gst_rate'] ?? config('invoice.default_gst_rate', 18.00);
        $totalAmount = (float) $data['total_amount'];

        // Price is GST-inclusive — back out the base amount and GST split.
        $baseAmount = round($totalAmount / (1 + $gstRate / 100), 2);
        $gstAmount = round($totalAmount - $baseAmount, 2);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::nextInvoiceNumber(),
            'owner_id' => $owner->id,
            'payment_id' => $data['payment_id'] ?? null,
            'credit_package_id' => $data['credit_package_id'] ?? null,
            'title' => $data['title'],
            'total_amount' => $totalAmount,
            'gst_rate' => $gstRate,
            'base_amount' => $baseAmount,
            'gst_amount' => $gstAmount,
            'type' => $data['type'] ?? 'auto',
            'created_by' => $data['created_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->renderPdf($invoice);

        return $invoice;
    }

    public function renderPdf(Invoice $invoice): void
    {
        $invoice->loadMissing('owner');

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        $relativePath = "invoices/{$invoice->invoice_number}.pdf";

        Storage::disk('local')->put($relativePath, $pdf->output());

        $invoice->update(['pdf_path' => $relativePath]);
    }

    /**
     * Send the invoice PDF to the owner's WhatsApp via a document-header template.
     * Uses a 7-day signed URL so Meta's servers can fetch the PDF without auth.
     */
    public function sendViaWhatsApp(Invoice $invoice): array
    {
        $invoice->loadMissing('owner');

        if (empty($invoice->pdf_path)) {
            $this->renderPdf($invoice);
        }

        $signedUrl = URL::temporarySignedRoute(
            'invoices.download.signed',
            now()->addDays(7),
            ['invoice' => $invoice->id]
        );

        $result = $this->whatsApp->sendDocumentTemplate(
            $invoice->owner->phone,
            'invoice_ready',
            documentLink: $signedUrl,
            documentFilename: "{$invoice->invoice_number}.pdf",
            bodyParams: [$invoice->owner->name, $invoice->invoice_number, number_format($invoice->total_amount, 2)]
        );

        if ($result['ok'] ?? false) {
            $invoice->update(['sent_via_whatsapp' => true, 'whatsapp_sent_at' => now()]);
        }

        return $result;
    }
}
