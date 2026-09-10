<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 13px; color: #222; }
    .header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 3px solid #FF6B4A; padding-bottom: 15px; }
    .header-left { display: table-cell; width: 60%; vertical-align: top; }
    .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: top; }
    .company-name { font-size: 20px; font-weight: bold; color: #FF6B4A; }
    .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
    .meta-table { width: 100%; margin: 20px 0; }
    .meta-table td { padding: 4px 0; vertical-align: top; }
    .label { color: #888; font-size: 11px; text-transform: uppercase; }
    .bill-to { background: #f9f9f9; padding: 12px; border-radius: 6px; margin: 15px 0; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table.items th { background: #FF6B4A; color: #fff; padding: 8px; text-align: left; font-size: 12px; }
    table.items td { padding: 8px; border-bottom: 1px solid #eee; }
    .totals { width: 40%; margin-left: 60%; margin-top: 15px; }
    .totals td { padding: 5px 8px; }
    .totals .grand { font-weight: bold; font-size: 15px; border-top: 2px solid #333; }
    .footer { margin-top: 40px; font-size: 11px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="company-name">{{ config('invoice.company_name') }}</div>
        <div>{{ config('invoice.company_address') }}</div>
        <div>GSTIN: {{ config('invoice.company_gstin') }}</div>
    </div>
    <div class="header-right">
        <div class="invoice-title">INVOICE</div>
        <div>{{ $invoice->invoice_number }}</div>
    </div>
</div>

<table class="meta-table">
    <tr>
        <td width="50%">
            <div class="label">Invoice Date</div>
            {{ $invoice->created_at->format('d M Y') }}
        </td>
        <td width="50%">
            <div class="label">Status</div>
            {{ $invoice->type === 'manual' ? 'Manual' : 'Paid' }}
        </td>
    </tr>
</table>

<div class="bill-to">
    <div class="label">Billed To</div>
    <strong>{{ $invoice->owner->billing_business_name ?: $invoice->owner->name }}</strong><br>
    {{ $invoice->owner->phone }}<br>
    @if($invoice->owner->email) {{ $invoice->owner->email }}<br> @endif
    @if($invoice->owner->billing_address) {{ $invoice->owner->billing_address }}<br> @endif
    @if($invoice->owner->billing_state || $invoice->owner->billing_pincode)
        {{ $invoice->owner->billing_state }} {{ $invoice->owner->billing_pincode }}<br>
    @endif
    @if($invoice->owner->gst_number)
        <strong>GSTIN: {{ $invoice->owner->gst_number }}</strong>
    @endif
</div>

<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th style="text-align:right">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $invoice->title }}</td>
            <td style="text-align:right">{{ number_format($invoice->base_amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Subtotal</td>
        <td style="text-align:right">₹{{ number_format($invoice->base_amount, 2) }}</td>
    </tr>
    <tr>
        <td>GST ({{ rtrim(rtrim(number_format($invoice->gst_rate, 2), '0'), '.') }}%)</td>
        <td style="text-align:right">₹{{ number_format($invoice->gst_amount, 2) }}</td>
    </tr>
    <tr class="grand">
        <td>Total</td>
        <td style="text-align:right">₹{{ number_format($invoice->total_amount, 2) }}</td>
    </tr>
</table>

<div class="footer">
    This is a system-generated invoice from {{ config('invoice.company_name') }}.
    @if($invoice->notes)
        <br><br><strong>Notes:</strong> {{ $invoice->notes }}
    @endif
</div>

</body>
</html>
