<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('owner')->latest();

        if ($request->filled('owner')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->owner . '%')
                  ->orWhere('phone', 'like', '%' . $request->owner . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $invoices = $query->paginate(30)->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $owners = User::where('role', 'owner')->orderBy('name')->get(['id', 'name', 'phone', 'gst_number', 'billing_business_name']);
        return view('admin.invoices.create', compact('owners'));
    }

    public function store(Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:150',
            'total_amount' => 'required|numeric|min:1',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'send_whatsapp' => 'nullable|boolean',
        ]);

        $invoice = $invoiceService->generate([
            'owner_id' => $data['owner_id'],
            'title' => $data['title'],
            'total_amount' => $data['total_amount'],
            'gst_rate' => $data['gst_rate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'type' => 'manual',
            'created_by' => auth()->id(),
        ]);

        if ($request->boolean('send_whatsapp')) {
            $invoiceService->sendViaWhatsApp($invoice);
        }

        return redirect()->route('admin.invoices.index')->with('success', "✓ Invoice {$invoice->invoice_number} created.");
    }

    public function edit(Invoice $invoice)
    {
        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'total_amount' => 'required|numeric|min:1',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $gstRate = $data['gst_rate'] ?? $invoice->gst_rate;
        $totalAmount = (float) $data['total_amount'];
        $baseAmount = round($totalAmount / (1 + $gstRate / 100), 2);
        $gstAmount = round($totalAmount - $baseAmount, 2);

        $invoice->update([
            'title' => $data['title'],
            'total_amount' => $totalAmount,
            'gst_rate' => $gstRate,
            'base_amount' => $baseAmount,
            'gst_amount' => $gstAmount,
            'notes' => $data['notes'] ?? null,
        ]);

        $invoiceService->renderPdf($invoice);

        return redirect()->route('admin.invoices.index')->with('success', "✓ Invoice {$invoice->invoice_number} updated.");
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->pdf_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($invoice->pdf_path);
        }
        $invoice->delete();

        return back()->with('success', '✓ Invoice deleted.');
    }

    public function send(Invoice $invoice, InvoiceService $invoiceService)
    {
        $result = $invoiceService->sendViaWhatsApp($invoice);

        return back()->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            ($result['ok'] ?? false) ? '✓ Invoice sent via WhatsApp.' : ('WhatsApp send failed: ' . ($result['message'] ?? 'unknown error'))
        );
    }
}
