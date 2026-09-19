@extends('layouts.dashboard')
@section('title', 'Bill #' . $bill->bill_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rent.index') }}" class="text-coral-500 font-bold">← Back to bills</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
                <div>
                    @if($bill->status === 'paid')
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold">✓ Fully Paid</span>
                    @elseif($bill->status === 'partial')
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold">Partial Payment</span>
                    @elseif($bill->status === 'overdue')
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Overdue</span>
                    @else
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> Pending</span>
                    @endif
                    <h1 class="font-display font-black text-2xl mt-2">Bill — {{ $bill->month_label }}</h1>
                    <p class="text-sm text-ink-700 mt-1">Bill #: <span class="font-mono font-bold">{{ $bill->bill_number }}</span></p>
                </div>
                <div class="text-right">
                    <div class="text-xs text-ink-500 uppercase font-bold">Total</div>
                    <div class="font-display font-black text-3xl text-coral-600">₹{{ number_format($bill->total_amount, 0) }}</div>
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100">
                <h3 class="font-bold mb-2">Tenant</h3>
                <p>{{ $bill->tenant?->name }} · <i class="fa-solid fa-mobile-screen fa-fw"></i> {{ $bill->tenant?->phone }}</p>
                <p class="text-sm text-ink-700">{{ $bill->property?->name }}, Room {{ $bill->tenant?->room_number }}</p>
            </div>

            <div class="mt-4 pt-4 border-t border-ink-100">
                <h3 class="font-bold mb-3">Charges Breakdown</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span>Rent</span><span class="font-bold">₹{{ number_format($bill->rent_amount, 0) }}</span></div>
                    @if($bill->electricity > 0)<div class="flex justify-between"><span>Electricity</span><span>₹{{ number_format($bill->electricity, 0) }}</span></div>@endif
                    @if($bill->water > 0)<div class="flex justify-between"><span>Water</span><span>₹{{ number_format($bill->water, 0) }}</span></div>@endif
                    @if($bill->maintenance > 0)<div class="flex justify-between"><span>Maintenance</span><span>₹{{ number_format($bill->maintenance, 0) }}</span></div>@endif
                    @if($bill->food_charges > 0)<div class="flex justify-between"><span>Food</span><span>₹{{ number_format($bill->food_charges, 0) }}</span></div>@endif
                    @if($bill->other_charges > 0)<div class="flex justify-between"><span>{{ $bill->other_charges_label ?? 'Other' }}</span><span>₹{{ number_format($bill->other_charges, 0) }}</span></div>@endif
                    @if($bill->late_fee > 0)<div class="flex justify-between text-rose-700"><span>Late Fee</span><span>+₹{{ number_format($bill->late_fee, 0) }}</span></div>@endif
                    @if($bill->discount > 0)<div class="flex justify-between text-emerald-700"><span>Discount</span><span>-₹{{ number_format($bill->discount, 0) }}</span></div>@endif
                    <div class="flex justify-between pt-2 mt-2 border-t border-ink-100 font-bold text-lg">
                        <span>Total</span>
                        <span>₹{{ number_format($bill->total_amount, 0) }}</span>
                    </div>
                </div>

                @if($bill->paid_amount > 0)
                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-700">Paid So Far</span>
                            <span class="font-bold text-emerald-700">₹{{ number_format($bill->paid_amount, 0) }}</span>
                        </div>
                        @if($bill->due_amount > 0)
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-rose-700">Remaining</span>
                                <span class="font-bold text-rose-700">₹{{ number_format($bill->due_amount, 0) }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if($bill->due_amount > 0)
        <div class="bg-white p-6 rounded-2xl border border-emerald-200">
            <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-sack-dollar fa-fw"></i> Record Payment</h2>
            <form method="POST" action="{{ route('owner.rent.payment', $bill) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-500">Amount *</label>
                        <input name="amount" type="number" step="0.01" required max="{{ $bill->due_amount }}" value="{{ $bill->due_amount }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-500">Payment Method *</label>
                        <select name="payment_method" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="phonepe">PhonePe</option>
                            <option value="paytm">Paytm</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-500">Paid At *</label>
                        <input name="paid_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-500">Transaction Ref</label>
                        <input name="transaction_ref" placeholder="UPI ref / Cheque no." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    </div>
                </div>
                <textarea name="notes" rows="2" placeholder="Notes (optional)" class="w-full px-4 py-3 rounded-xl border border-ink-200"></textarea>
                <button class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">✓ Record Payment</button>
            </form>
        </div>
        @endif

        @if($bill->payments->count())
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-clipboard-list fa-fw"></i> Payment History</h2>
            <div class="space-y-2">
                @foreach($bill->payments as $payment)
                    <div class="flex items-center justify-between gap-3 p-3 bg-cream rounded-xl flex-wrap">
                        <div>
                            <div class="font-bold">₹{{ number_format($payment->amount, 0) }} <span class="text-xs font-normal text-ink-700">via {{ $payment->method_label }}</span></div>
                            <div class="text-xs text-ink-700">
                                <span class="font-mono">{{ $payment->receipt_number }}</span> · 
                                {{ $payment->paid_at->format('d M Y, h:i A') }}
                                @if($payment->transaction_ref)<br>Ref: <span class="font-mono">{{ $payment->transaction_ref }}</span>@endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('owner.rent.receipt', $payment) }}" target="_blank" class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-file-lines fa-fw"></i> Receipt</a>
                            <form method="POST" action="{{ route('owner.rent.payment.delete', $payment) }}" onsubmit="return confirm('Remove this payment?')" class="inline-block">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1.5 bg-rose-500 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-trash-can fa-fw"></i></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Bill Details</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-ink-500">Due Date:</span> <strong>{{ $bill->due_date->format('d M Y') }}</strong></div>
                    <div><span class="text-ink-500">Created:</span> <strong>{{ $bill->created_at->format('d M Y') }}</strong></div>
                    @if($bill->notes)
                        <div class="pt-2 border-t border-ink-100">
                            <span class="text-ink-500">Notes:</span>
                            <p class="mt-1">{{ $bill->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
            
                        {{-- Payment Link — always works, independent of WhatsApp template/button approval --}}
            @if($bill->status !== 'paid')
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3"><i class="fa-solid fa-link fa-fw"></i> Payment Link</h3>
                <div class="flex items-center gap-2 mb-3">
                    <input id="payUrlInput" type="text" readonly value="{{ $bill->pay_url }}"
                        class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-ink-200 text-xs font-mono bg-cream">
                    <button onclick="copyPayUrl()" id="copyBtn"
                        class="shrink-0 px-3 py-2 bg-ink-800 hover:bg-ink-900 text-white rounded-lg text-xs font-bold">
                        Copy
                    </button>
                </div>
                <a href="https://wa.me/?text={{ urlencode('Hi ' . $bill->tenant?->name . ', your rent bill for ' . $bill->month_label . ' — Amount: ₹' . number_format($bill->due_amount, 0) . '. Pay here: ' . $bill->pay_url) }}"
                    target="_blank"
                    class="block text-center w-full px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold transition">
                    <i class="fa-solid fa-comment-dots fa-fw"></i> Share on WhatsApp
                </a>
            </div>

            {{-- WhatsApp Template Reminder --}}
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3"><i class="fa-solid fa-mobile-screen-button fa-fw"></i> Auto Reminder</h3>
                <p class="text-xs text-ink-500 mb-3">{{ $bill->tenant?->name }} ko template message bhejo (link ke bina, agar button approved nahi hai)</p>
                <button id="waBtn" onclick="sendWaReminder()"
                    class="w-full px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold transition">
                    <i class="fa-solid fa-comment-dots fa-fw"></i> Send Reminder
                </button>
                <div id="waResult" class="mt-2 text-xs text-center hidden"></div>
            </div>
            @endif

            <script>
            function copyPayUrl() {
                const input = document.getElementById('payUrlInput');
                input.select();
                navigator.clipboard.writeText(input.value).then(() => {
                    const btn = document.getElementById('copyBtn');
                    const original = btn.textContent;
                    btn.textContent = '✓ Copied';
                    setTimeout(() => { btn.textContent = original; }, 1500);
                });
            }
            </script>

            <script>
            async function sendWaReminder() {
                const btn = document.getElementById('waBtn');
                const result = document.getElementById('waResult');
                btn.disabled = true;
                btn.textContent = 'Sending...';
                result.className = 'mt-2 text-xs text-center';
                result.classList.remove('hidden');
                result.textContent = '';

                try {
                    const res = await fetch('{{ route('owner.rent.reminder', $bill) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();

                    if (data.ok) {
                        result.textContent = '✅ Reminder sent!';
                        result.classList.add('text-emerald-600');
                    } else {
                        result.textContent = '⚠️ ' + (data.message ?? 'Could not send.');
                                                if (data.wa_link) {
                            window.open(data.wa_link, '_blank');
                        }
                        result.classList.add('text-amber-600');
                    }
                } catch(e) {
                    result.textContent = '❌ Network error.';
                    result.classList.add('text-rose-600');
                }

                btn.disabled = false;
                btn.textContent = '💬 Send Reminder';
            }
            </script>

            <form method="POST" action="{{ route('owner.rent.destroy', $bill) }}" onsubmit="return confirm('Delete this bill and all its payments?')">
                @csrf @method('DELETE')
                <button class="w-full px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Bill</button>
            </form>
        </div>
    </div>
</div>

@endsection