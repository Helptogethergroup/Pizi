@extends('layouts.dashboard')
@section('title', 'Rent Collection')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="font-display font-black text-3xl">Rent Collection</h1>
        <p class="text-ink-900/60 mt-1">Manage rent bills, payments, and receipts</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <button onclick="document.getElementById('genAllModal').classList.remove('hidden')" class="px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-bold">⚡ Generate All Bills</button>
        <a href="{{ route('owner.rent.create') }}" class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-coral-500/30">+ New Bill</a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-emerald-200">
        <div class="text-xs text-emerald-700 uppercase font-bold">Collected (This Month)</div>
        <div class="font-display font-black text-2xl text-emerald-700 mt-1">₹{{ number_format($stats['total_collected_month'], 0) }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-amber-200">
        <div class="text-xs text-amber-700 uppercase font-bold">Total Pending</div>
        <div class="font-display font-black text-2xl text-amber-700 mt-1">₹{{ number_format($stats['total_pending'], 0) }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-rose-200">
        <div class="text-xs text-rose-700 uppercase font-bold">Overdue Bills</div>
        <div class="font-display font-black text-3xl text-rose-700 mt-1">{{ $stats['overdue_count'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-blue-200">
        <div class="text-xs text-blue-700 uppercase font-bold">Paid This Month</div>
        <div class="font-display font-black text-3xl text-blue-700 mt-1">{{ $stats['paid_count_month'] }}</div>
    </div>
</div>

{{-- Status Tabs --}}
<div class="bg-white rounded-xl border border-ink-100 p-2 mb-4 flex gap-1 overflow-x-auto scrollbar-hide">
    @php
        $tabs = [
            '' => ['label' => 'All', 'count' => $tabCounts['all']],
            'pending' => ['label' => '⏳ Pending', 'count' => $tabCounts['pending']],
            'partial' => ['label' => '◐ Partial', 'count' => $tabCounts['partial']],
            'overdue' => ['label' => '⚠️ Overdue', 'count' => $tabCounts['overdue']],
            'paid' => ['label' => '✓ Paid', 'count' => $tabCounts['paid']],
        ];
        $currentStatus = request('status', '');
    @endphp
    @foreach($tabs as $value => $tab)
        <a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null]) }}"
           class="px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap {{ $currentStatus === $value ? 'bg-coral-500 text-white' : 'text-ink-700 hover:bg-cream' }}">
            {{ $tab['label'] }} ({{ $tab['count'] }})
        </a>
    @endforeach
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Tenant name or phone..." class="flex-1 min-w-[200px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <input name="month" type="month" value="{{ request('month') }}" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
      <select name="payment_method" onchange="this.form.submit()" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Payment Methods</option>
        <option value="cash" @selected(request('payment_method') === 'cash')>💵 Cash</option>
        <option value="upi" @selected(request('payment_method') === 'upi')>📱 UPI</option>
        <option value="razorpay" @selected(request('payment_method') === 'razorpay')>💳 Razorpay</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($bills->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3">💰</div>
        <p class="text-ink-700 mb-4">No rent bills yet.</p>
        <a href="{{ route('owner.rent.create') }}" class="inline-block px-5 py-3 bg-coral-500 text-white rounded-xl font-bold">+ Create First Bill</a>
    </div>
@else
    <div class="space-y-3">
        @foreach($bills as $bill)
            <div class="bg-white p-4 rounded-2xl border border-ink-100 hover:border-coral-300 transition">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-bold">{{ $bill->tenant?->name }}</h3>
                            <span class="text-xs text-ink-500">·</span>
                            <span class="text-xs text-ink-700">{{ $bill->month_label }}</span>
                            
                            @if($bill->status === 'paid')
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ Paid</span>
                            @elseif($bill->status === 'partial')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">Partial</span>
                            @elseif($bill->status === 'overdue')
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">⚠️ Overdue</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">⏳ Pending</span>
                            @endif
                        </div>
                                               <div class="flex items-center gap-3 text-xs text-ink-700 flex-wrap">
                            <span>📱 {{ $bill->tenant?->phone }}</span>
                            @if($bill->tenant?->room_number)<span>🚪 Room {{ $bill->tenant->room_number }}</span>@endif
                            <span>📅 Due: {{ $bill->due_date->format('d M Y') }}</span>
                            <span class="font-mono">#{{ $bill->bill_number }}</span>
                        </div>
                        @if($bill->payments->count())
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($bill->payments as $payment)
                                    @php
                                        $methodIcons = [
                                            'cash' => '💵', 'upi' => '📱', 'razorpay' => '💳',
                                            'bank_transfer' => '🏦', 'phonepe' => '📱',
                                            'paytm' => '📱', 'cheque' => '📝', 'other' => '💰',
                                        ];
                                        $icon = $methodIcons[$payment->payment_method] ?? '💰';
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                                        {{ $icon }} <span class="font-bold capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                                        · ₹{{ number_format($payment->amount, 0) }}
                                        · {{ $payment->paid_at->format('d M Y, h:i A') }}
                                        @if($payment->transaction_ref)
                                            · Ref: <span class="font-mono">{{ $payment->transaction_ref }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="text-right">
                        <div class="font-display font-black text-xl">₹{{ number_format($bill->total_amount, 0) }}</div>
                        @if($bill->due_amount > 0)
                            <div class="text-xs text-rose-600 font-bold">Due: ₹{{ number_format($bill->due_amount, 0) }}</div>
                        @else
                            <div class="text-xs text-emerald-600 font-bold">Fully Paid</div>
                        @endif
                    </div>

                    <div class="flex gap-1.5 flex-wrap items-center">
                        <a href="{{ route('owner.rent.show', $bill) }}" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold">View</a>
                        <button
                            onclick="sendReminder({{ $bill->id }})"
                            id="wa-btn-{{ $bill->id }}"
                            title="Send WhatsApp Reminder"
                            class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold">
                            💬
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $bills->links() }}</div>
@endif

{{-- Generate All Modal --}}
<div id="genAllModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display font-bold text-xl">⚡ Generate All Bills</h2>
            <button type="button" onclick="document.getElementById('genAllModal').classList.add('hidden')" class="text-2xl">×</button>
        </div>

        <p class="text-sm text-ink-700 mb-4">Generate rent bills for all active tenants for a specific month. Uses each tenant's monthly_rent value.</p>

        <form method="POST" action="{{ route('owner.rent.generateAll') }}">
            @csrf
            <label class="text-xs font-bold uppercase text-ink-500">Month *</label>
            <input name="month" type="month" required value="{{ now()->format('Y-m') }}" class="w-full mt-1 mb-3 px-4 py-2.5 rounded-lg border border-ink-200">

            <label class="text-xs font-bold uppercase text-ink-500">Due Date *</label>
            <input name="due_date" type="date" required value="{{ now()->day(5)->format('Y-m-d') }}" class="w-full mt-1 mb-4 px-4 py-2.5 rounded-lg border border-ink-200">

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-5 py-2.5 bg-coral-500 text-white rounded-lg font-bold">Generate Bills</button>
                <button type="button" onclick="document.getElementById('genAllModal').classList.add('hidden')" class="px-5 py-2.5 border border-ink-200 rounded-lg font-bold">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
async function sendReminder(billId) {
    const btn = document.getElementById('wa-btn-' + billId);
    btn.disabled = true;
    btn.textContent = '⏳';

    try {
        const res = await fetch('/owner/rent/' + billId + '/reminder', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        if (data.ok) {
            btn.textContent = '✅';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = '💬';
            }, 3000);
        } else if (data.wa_link) {
            window.open(data.wa_link, '_blank');
            btn.disabled = false;
            btn.textContent = '💬';
        } else {
            alert(data.message ?? 'Could not send reminder.');
            btn.disabled = false;
            btn.textContent = '💬';
        }
    } catch(e) {
        alert('Network error. Try again.');
        btn.disabled = false;
        btn.textContent = '💬';
    }
}
</script>

@endsection