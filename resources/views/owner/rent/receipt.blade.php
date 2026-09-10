<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt — {{ $payment->receipt_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f6f5f1; }
        h1, h2 { font-family: 'Fraunces', serif; }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .print-area { box-shadow: none !important; }
        }
    </style>
</head>
<body class="py-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-lg print-area">
        
        <div class="flex justify-between items-start pb-6 border-b-2 border-ink-900">
            <div>
                <h1 class="font-black text-3xl text-ink-950">PIZI</h1>
                <p class="text-xs text-ink-700 mt-1">Live Better. Stay Smarter.</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-ink-500 uppercase font-bold">Payment Receipt</div>
                <div class="font-mono font-bold text-lg">{{ $payment->receipt_number }}</div>
                <div class="text-xs text-ink-700">{{ $payment->paid_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mt-6">
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold mb-2">Received From</div>
                <div class="font-bold">{{ $payment->tenant->name }}</div>
                <div class="text-sm text-ink-700">{{ $payment->tenant->phone }}</div>
                @if($payment->tenant->email)
                    <div class="text-sm text-ink-700">{{ $payment->tenant->email }}</div>
                @endif
                <div class="text-sm text-ink-700 mt-1">
                    {{ $payment->bill->property->name }}
                    @if($payment->tenant->room_number) · Room {{ $payment->tenant->room_number }}@endif
                </div>
            </div>
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold mb-2">Towards Bill</div>
                <div class="font-bold">{{ $payment->bill->month_label }}</div>
                <div class="text-sm font-mono">{{ $payment->bill->bill_number }}</div>
                <div class="text-sm text-ink-700 mt-1">Total Bill: ₹{{ number_format($payment->bill->total_amount, 0) }}</div>
            </div>
        </div>

        <div class="mt-8 p-6 bg-gradient-to-br from-ink-950 to-ink-900 text-cream rounded-2xl">
            <div class="text-xs uppercase font-bold opacity-70">Amount Paid</div>
            <div class="font-black text-5xl mt-1">₹{{ number_format($payment->amount, 2) }}</div>
            <div class="text-sm opacity-70 mt-2">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</div>
        </div>

        <div class="grid grid-cols-2 gap-6 mt-6 text-sm">
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold">Payment Method</div>
                <div class="font-bold">{{ $payment->method_label }}</div>
            </div>
            @if($payment->transaction_ref)
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold">Transaction Ref</div>
                <div class="font-mono font-bold">{{ $payment->transaction_ref }}</div>
            </div>
            @endif
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold">Paid Status</div>
                @if($payment->bill->due_amount > 0)
                    <div class="text-amber-700 font-bold">Partial Payment</div>
                    <div class="text-xs text-ink-700">Remaining: ₹{{ number_format($payment->bill->due_amount, 0) }}</div>
                @else
                    <div class="text-emerald-700 font-bold">✓ Fully Paid</div>
                @endif
            </div>
            <div>
                <div class="text-xs text-ink-500 uppercase font-bold">Received By</div>
                <div class="font-bold">{{ $payment->receivedBy?->name ?? 'Owner' }}</div>
            </div>
        </div>

        @if($payment->notes)
            <div class="mt-6 p-3 bg-cream rounded-xl text-sm">
                <strong>Notes:</strong> {{ $payment->notes }}
            </div>
        @endif

        <div class="mt-8 pt-6 border-t border-ink-100 text-center text-xs text-ink-700">
            <p>This is a computer-generated receipt. No signature required.</p>
            <p class="mt-1">For queries, contact: contact@pizi.in · 9999999999</p>
            <p class="mt-2 font-bold">Thank you for your payment! 🙏</p>
        </div>

        <div class="no-print mt-6 flex gap-3">
            <button onclick="window.print()" class="flex-1 px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">🖨 Print / Save as PDF</button>
            <a href="{{ route('owner.rent.show', $payment->bill) }}" class="px-5 py-3 border border-ink-200 rounded-xl font-bold">Close</a>
        </div>
    </div>
</body>
</html>