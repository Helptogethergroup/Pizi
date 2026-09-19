@extends('layouts.dashboard')
@section('title', 'Bill — ' . $bill->bill_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('admin.rent.index') }}" class="text-coral-500 font-bold">← Back to all bills</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
                <div>
                    @if($bill->status === 'paid')
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold">✓ Fully Paid</span>
                    @elseif($bill->status === 'partial')
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold">Partial</span>
                    @elseif($bill->status === 'overdue')
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Overdue</span>
                    @else
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> Pending</span>
                    @endif
                    <h1 class="font-display font-black text-2xl mt-2">{{ $bill->month_label }}</h1>
                    <p class="text-sm font-mono">{{ $bill->bill_number }}</p>
                </div>
                <div class="text-right">
                    <div class="text-xs text-ink-500 uppercase font-bold">Total</div>
                    <div class="font-display font-black text-3xl text-coral-600">₹{{ number_format($bill->total_amount, 0) }}</div>
                </div>
            </div>

            <div class="pt-4 border-t border-ink-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Tenant</div>
                    <div class="font-bold">{{ $bill->tenant?->name }}</div>
                    <div class="text-sm"><i class="fa-solid fa-phone fa-fw"></i> {{ $bill->tenant?->phone }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Owner</div>
                    <div class="font-bold">{{ $bill->owner?->name }}</div>
                    <div class="text-sm">{{ $bill->property?->name }}</div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-ink-100">
                <h3 class="font-bold mb-3">Charges Breakdown</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span>Rent</span><span class="font-bold">₹{{ number_format($bill->rent_amount, 0) }}</span></div>
                    @if($bill->electricity > 0)<div class="flex justify-between"><span>Electricity</span><span>₹{{ number_format($bill->electricity, 0) }}</span></div>@endif
                    @if($bill->water > 0)<div class="flex justify-between"><span>Water</span><span>₹{{ number_format($bill->water, 0) }}</span></div>@endif
                    @if($bill->maintenance > 0)<div class="flex justify-between"><span>Maintenance</span><span>₹{{ number_format($bill->maintenance, 0) }}</span></div>@endif
                    @if($bill->food_charges > 0)<div class="flex justify-between"><span>Food</span><span>₹{{ number_format($bill->food_charges, 0) }}</span></div>@endif
                    @if($bill->late_fee > 0)<div class="flex justify-between text-rose-700"><span>Late Fee</span><span>+₹{{ number_format($bill->late_fee, 0) }}</span></div>@endif
                    @if($bill->discount > 0)<div class="flex justify-between text-emerald-700"><span>Discount</span><span>-₹{{ number_format($bill->discount, 0) }}</span></div>@endif
                    <div class="flex justify-between pt-2 mt-2 border-t border-ink-100 font-bold text-lg">
                        <span>Total</span><span>₹{{ number_format($bill->total_amount, 0) }}</span>
                    </div>
                </div>

                @if($bill->paid_amount > 0)
                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex justify-between text-sm">
                            <span class="text-emerald-700">Paid</span>
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
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.rent.receipt', $payment) }}" target="_blank" class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-file-lines fa-fw"></i> Receipt</a>
                            <form method="POST" action="{{ route('admin.rent.payment.delete', $payment) }}" onsubmit="return confirm('Remove this payment?')" class="inline-block">
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

            @if($bill->due_amount > 0)
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3"><i class="fa-solid fa-credit-card fa-fw"></i> Payment Link</h3>
                <p class="text-xs text-ink-500 mb-3">Tenant opens this to pay online (Razorpay — UPI/Card/NetBanking). Payment auto-confirms the bill.</p>
                <div class="flex gap-2">
                    <input type="text" id="payLinkInput" readonly value="{{ route('public.pay', $bill->bill_number) }}" class="flex-1 px-3 py-2.5 rounded-lg border border-ink-200 text-xs font-mono bg-cream">
                    <button type="button" onclick="copyPayLink()" class="px-4 py-2.5 bg-ink-950 text-cream rounded-lg text-xs font-bold whitespace-nowrap"><i class="fa-solid fa-clipboard-list fa-fw"></i> Copy</button>
                </div>
                <p id="copyConfirm" class="text-xs text-emerald-700 font-bold mt-2 hidden">✓ Copied to clipboard!</p>
            </div>

            <a href="https://wa.me/91{{ $bill->tenant?->phone }}?text={{ urlencode('Hi '.$bill->tenant?->name.', your rent of ₹'.number_format($bill->due_amount).' for '.$bill->month_label.' is pending. Bill #'.$bill->bill_number.'. Pay here: '.route('public.pay', $bill->bill_number)) }}" target="_blank" class="block w-full text-center px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">
                <i class="fa-solid fa-comment-dots fa-fw"></i> Send WhatsApp Reminder
            </a>
            @endif

            <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200">
                <h3 class="font-display font-bold text-amber-900 mb-3"><i class="fa-solid fa-bolt fa-fw"></i> Admin Actions</h3>
                <form method="POST" action="{{ route('admin.rent.destroy', $bill) }}" onsubmit="return confirm('Delete bill and all payments?')">
                    @csrf @method('DELETE')
                    <button class="w-full px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Bill</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

<script>
function copyPayLink() {
    const input = document.getElementById('payLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const confirm = document.getElementById('copyConfirm');
        confirm.classList.remove('hidden');
        setTimeout(() => confirm.classList.add('hidden'), 2000);
    });
}
</script>