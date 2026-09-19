@extends('layouts.dashboard')
@section('title', 'All Rent Bills — Admin')
@section('content')

<div class="mb-6">
    <h1 class="font-display font-black text-3xl"><i class="fa-solid fa-sack-dollar fa-fw"></i> All Rent Bills</h1>
    <p class="text-ink-900/60 mt-1">Platform-wide rent collection overview</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 rounded-2xl text-white">
        <div class="text-xs uppercase font-bold opacity-80">Collected (Month)</div>
        <div class="font-display font-black text-2xl mt-1">₹{{ number_format($stats['total_collected_month'], 0) }}</div>
    </div>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-5 rounded-2xl text-white">
        <div class="text-xs uppercase font-bold opacity-80">Total Collected</div>
        <div class="font-display font-black text-2xl mt-1">₹{{ number_format($stats['total_collected_all'], 0) }}</div>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 p-5 rounded-2xl text-white">
        <div class="text-xs uppercase font-bold opacity-80">Pending Dues</div>
        <div class="font-display font-black text-2xl mt-1">₹{{ number_format($stats['total_pending'], 0) }}</div>
    </div>
    <div class="bg-gradient-to-br from-rose-500 to-rose-600 p-5 rounded-2xl text-white">
        <div class="text-xs uppercase font-bold opacity-80">Overdue Bills</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['overdue_count'] }}</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-ink-100">
        <div class="text-xs text-ink-500 uppercase font-bold">Total Bills</div>
        <div class="font-display font-black text-3xl mt-1">{{ $stats['total_bills'] }}</div>
    </div>
</div>

<form method="GET" class="bg-white p-3 rounded-xl border border-ink-100 mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Bill no, tenant name..." class="flex-1 min-w-[200px] px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="owner_id" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Owners</option>
        @foreach($owners as $o)
            <option value="{{ $o->id }}" @selected(request('owner_id') == $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    <input name="month" type="month" value="{{ request('month') }}" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
    <select name="status" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
        <option value="">All Status</option>
        <option value="pending" @selected(request('status')==='pending')>Pending</option>
        <option value="partial" @selected(request('status')==='partial')>Partial</option>
        <option value="paid" @selected(request('status')==='paid')>Paid</option>
        <option value="overdue" @selected(request('status')==='overdue')>Overdue</option>
    </select>
    <button class="px-5 py-2.5 bg-ink-950 text-cream rounded-lg text-sm font-bold">Filter</button>
</form>

@if($bills->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-ink-100 text-center">
        <div class="text-5xl mb-3"><i class="fa-solid fa-sack-dollar fa-fw"></i></div>
        <p class="text-ink-700">No bills found.</p>
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
                                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Overdue</span>
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> Pending</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-ink-700 flex-wrap">
                            <span class="font-mono">{{ $bill->bill_number }}</span>
                            <span><i class="fa-solid fa-phone fa-fw"></i> {{ $bill->tenant?->phone }}</span>
                            <span><i class="fa-solid fa-user fa-fw"></i> Owner: <strong>{{ $bill->owner?->name }}</strong></span>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="font-display font-black text-xl">₹{{ number_format($bill->total_amount, 0) }}</div>
                        @if($bill->due_amount > 0)
                            <div class="text-xs text-rose-600 font-bold">Due: ₹{{ number_format($bill->due_amount, 0) }}</div>
                        @else
                            <div class="text-xs text-emerald-600 font-bold">Fully Paid</div>
                        @endif
                    </div>

                    <div class="flex gap-1.5 flex-wrap">
                        <a href="{{ route('admin.rent.show', $bill) }}" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold">View</a>
                        @if($bill->due_amount > 0)
                            <a href="https://wa.me/91{{ $bill->tenant?->phone }}?text={{ urlencode('Hi '.$bill->tenant?->name.', your rent of ₹'.number_format($bill->due_amount).' for '.$bill->month_label.' is pending. Pay here: '.route('public.pay', $bill->bill_number)) }}" target="_blank" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> Remind</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $bills->links() }}</div>
@endif

@endsection