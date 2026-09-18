<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Rent — {{ $bill->bill_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm mb-4">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
            <div class="bg-gray-900 text-white p-6 text-center">
                <div class="text-sm opacity-70">Rent Bill</div>
                <div class="font-black text-3xl mt-1">₹{{ number_format($bill->due_amount, 0) }}</div>
                <div class="text-xs opacity-60 mt-1">Due</div>
            </div>

            <div class="p-6 space-y-3 border-b border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Tenant</span>
                    <span class="font-bold">{{ $bill->tenant?->name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Property</span>
                    <span class="font-bold">{{ $bill->property?->name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Month</span>
                    <span class="font-bold">{{ $bill->month_label }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Due Date</span>
                    <span class="font-bold">{{ $bill->due_date->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Bill No.</span>
                    <span class="font-mono text-xs">{{ $bill->bill_number }}</span>
                </div>
            </div>

            @if($bill->due_amount <= 0)
                <div class="p-8 text-center">
                    <div class="text-4xl mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-width="2.2"/></svg></div>
                    <p class="font-bold text-emerald-700">This bill is fully paid.</p>
                    <p class="text-sm text-gray-500 mt-1">Thank you!</p>
                </div>
            @elseif($errorMsg || !$order)
                <div class="p-8 text-center">
                    <p class="text-sm text-gray-500">Online payment isn't available right now. Please contact your PG owner directly.</p>
                </div>
            @else
                <div class="p-6 text-center">
                    <button id="payBtn" class="w-full px-6 py-3.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-bold text-lg">
                        Pay ₹{{ number_format($bill->due_amount, 0) }} Now
                    </button>
                    <p class="text-xs text-gray-400 mt-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Secured by Razorpay — UPI, Cards, Net Banking, Wallets</p>
                </div>
            @endif
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">Powered by Pizi</p>
    </div>

    @if($order && !$errorMsg && $bill->due_amount > 0)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    document.getElementById('payBtn').addEventListener('click', function () {
        var options = {
            "key": "{{ $razorpayKey }}",
            "amount": "{{ (int) round($bill->due_amount * 100) }}",
            "currency": "INR",
            "name": "Pizi",
            "description": "Rent — {{ $bill->month_label }} ({{ $bill->bill_number }})",
            "order_id": "{{ $order->razorpay_order_id }}",
            "handler": function (response) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('public.pay.callback', $bill->bill_number) }}";

                var fields = {
                    '_token': "{{ csrf_token() }}",
                    'razorpay_order_id': response.razorpay_order_id,
                    'razorpay_payment_id': response.razorpay_payment_id,
                    'razorpay_signature': response.razorpay_signature,
                };

                for (var key in fields) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
                document.body.appendChild(form);
                form.submit();
            },
            "prefill": {
                "name": "{{ $bill->tenant?->name }}",
                "contact": "{{ $bill->tenant?->phone }}"
            },
            "theme": { "color": "#ff6b5b" },
            "modal": {
                "ondismiss": function () {
                    window.location.href = "{{ route('public.pay.failed', $bill->bill_number) }}";
                }
            }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            window.location.href = "{{ route('public.pay.failed', $bill->bill_number) }}?error=" + encodeURIComponent(response.error.description);
        });
        rzp.open();
    });
    </script>
    @endif

</body>
</html>