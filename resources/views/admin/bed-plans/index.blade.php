@extends('layouts.dashboard')
@section('title', 'Bed Plan')
@section('content')

<h1 class="font-display font-black text-3xl mb-1">Bed Plan</h1>
<p class="text-ink-900/60 text-sm">Owners pay a monthly price for every bed from their Bed Wallet. You decide every price and who is on the plan.</p>

@if(session('error'))
    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
@endif

{{-- Master switch --}}
<form method="POST" action="{{ route('admin.bed-plans.settings') }}" class="mt-6 p-5 rounded-2xl border {{ $enabled ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }} flex flex-wrap items-center gap-4">
    @csrf
    <div class="flex-1 min-w-[240px]">
        <div class="font-bold">Bed Plan is {{ $enabled ? 'ON' : 'OFF' }}</div>
        <div class="text-xs text-ink-900/60 mt-0.5">The emergency switch. Off means nobody is charged and nobody is restricted, whatever else is set below. Owners are only affected after you switch the plan on for them.</div>
    </div>
    <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
    <button class="px-5 py-2.5 rounded-xl font-bold text-white {{ $enabled ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}"
            onclick="return confirm('{{ $enabled ? 'Switch the Bed Plan OFF? Nobody will be charged or restricted.' : 'Switch the Bed Plan ON?' }}')">
        {{ $enabled ? 'Switch OFF' : 'Switch ON' }}
    </button>
</form>

{{-- Numbers --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
    @foreach([
        ['Owners on the plan', $stats['active'], 'text-emerald-700'],
        ['Ended (restricted)', $stats['expired'], 'text-rose-700'],
        ['Not on the plan yet', $stats['not_on_plan'], 'text-ink-900'],
        ['Switched off', $stats['disabled'], 'text-ink-900'],
        ['Money in wallets', '₹' . number_format($stats['wallet_total'], 2), 'text-ink-900'],
        ['Top-ups, last 30 days', '₹' . number_format($stats['topups_30d'], 2), 'text-ink-900'],
        ['Billed, last 30 days', '₹' . number_format($stats['billed_30d'], 2), 'text-ink-900'],
    ] as [$label, $value, $color])
        <div class="p-4 rounded-xl bg-white border border-ink-900/10">
            <div class="text-xs text-ink-900/50">{{ $label }}</div>
            <div class="font-display font-black text-2xl mt-1 {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

{{-- Plans --}}
<div class="mt-10 bg-white rounded-2xl border border-ink-900/10 p-6">
    <h2 class="font-display font-bold text-xl">Plans (price per bed per month, GST included)</h2>
    <p class="text-xs text-ink-900/50 mt-1">The default plan is used for owners you switch on without picking a plan. You can also give one owner their own price from that owner's page.</p>
    <div class="mt-4 space-y-3">
        @foreach($plans as $plan)
            <form method="POST" action="{{ route('admin.bed-plans.plans.update', $plan) }}" class="grid grid-cols-2 md:grid-cols-12 gap-2 items-end p-3 rounded-xl border border-ink-900/10 {{ $plan->is_active ? '' : 'opacity-60' }}">
                @csrf @method('PATCH')
                <label class="col-span-2 md:col-span-3 text-xs">Name<input name="name" value="{{ $plan->name }}" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="md:col-span-2 text-xs">₹ per bed / month<input name="price_per_bed" type="number" step="0.01" min="0" value="{{ $plan->price_per_bed }}" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="md:col-span-1 text-xs">GST %<input name="gst_percent" type="number" step="0.01" min="0" max="40" value="{{ $plan->gst_percent }}" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="col-span-2 md:col-span-3 text-xs">Note<input name="description" value="{{ $plan->description }}" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <div class="col-span-2 md:col-span-2 flex items-center gap-3 text-xs pb-2">
                    <label class="flex items-center gap-1"><input type="checkbox" name="is_default" value="1" @checked($plan->is_default)> Default</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
                </div>
                <button class="col-span-2 md:col-span-1 px-4 py-2 bg-ink-900 text-white rounded-lg text-sm font-bold">Save</button>
            </form>
        @endforeach
    </div>
    <form method="POST" action="{{ route('admin.bed-plans.plans.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-12 gap-2 items-end p-3 rounded-xl bg-cream/50 border border-dashed border-ink-900/20">
        @csrf
        <label class="col-span-2 md:col-span-3 text-xs">New plan name<input name="name" required placeholder="e.g. Standard" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="md:col-span-2 text-xs">₹ per bed / month<input name="price_per_bed" type="number" step="0.01" min="0" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="md:col-span-1 text-xs">GST %<input name="gst_percent" type="number" step="0.01" min="0" max="40" value="18" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="col-span-2 md:col-span-3 text-xs">Note<input name="description" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <div class="col-span-2 md:col-span-2 flex items-center gap-3 text-xs pb-2">
            <label class="flex items-center gap-1"><input type="checkbox" name="is_default" value="1" @checked($plans->isEmpty())> Default</label>
        </div>
        <button class="col-span-2 md:col-span-1 px-4 py-2 bg-coral-500 text-white rounded-lg text-sm font-bold">Add</button>
    </form>
</div>

{{-- Top-up packages --}}
<div class="mt-8 bg-white rounded-2xl border border-ink-900/10 p-6">
    <h2 class="font-display font-bold text-xl">Top-up packages</h2>
    <p class="text-xs text-ink-900/50 mt-1">What owners can buy to fill the Bed Wallet. They pay the amount and get the amount plus the bonus.</p>
    <div class="mt-4 space-y-3">
        @foreach($packages as $p)
            <form method="POST" action="{{ route('admin.bed-plans.packages.update', $p) }}" class="grid grid-cols-2 md:grid-cols-12 gap-2 items-end p-3 rounded-xl border border-ink-900/10 {{ $p->is_active ? '' : 'opacity-60' }}">
                @csrf @method('PATCH')
                <label class="col-span-2 md:col-span-3 text-xs">Name<input name="name" value="{{ $p->name }}" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="md:col-span-2 text-xs">Owner pays ₹<input name="amount_inr" type="number" min="1" value="{{ $p->amount_inr }}" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="md:col-span-2 text-xs">Bonus ₹<input name="bonus_inr" type="number" min="0" value="{{ $p->bonus_inr }}" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <label class="md:col-span-2 text-xs">Order<input name="display_order" type="number" min="0" value="{{ $p->display_order }}" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200"></label>
                <div class="md:col-span-2 text-xs pb-2"><label class="flex items-center gap-1"><input type="checkbox" name="is_active" value="1" @checked($p->is_active)> Active</label></div>
                <button class="md:col-span-1 px-4 py-2 bg-ink-900 text-white rounded-lg text-sm font-bold">Save</button>
            </form>
        @endforeach
    </div>
    <form method="POST" action="{{ route('admin.bed-plans.packages.store') }}" class="mt-4 grid grid-cols-2 md:grid-cols-12 gap-2 items-end p-3 rounded-xl bg-cream/50 border border-dashed border-ink-900/20">
        @csrf
        <label class="col-span-2 md:col-span-3 text-xs">New package name<input name="name" required placeholder="e.g. Top-up 5000" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="md:col-span-2 text-xs">Owner pays ₹<input name="amount_inr" type="number" min="1" required class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="md:col-span-2 text-xs">Bonus ₹<input name="bonus_inr" type="number" min="0" value="0" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <label class="md:col-span-2 text-xs">Order<input name="display_order" type="number" min="0" value="0" class="w-full mt-1 px-3 py-2 rounded-lg border border-ink-200 bg-white"></label>
        <div class="md:col-span-2"></div>
        <button class="md:col-span-1 px-4 py-2 bg-coral-500 text-white rounded-lg text-sm font-bold">Add</button>
    </form>
</div>

{{-- Owners --}}
<div class="mt-8 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10 flex flex-wrap items-center gap-3">
        <h2 class="font-display font-bold text-xl flex-1">Owners</h2>
        <form method="GET" class="flex flex-wrap gap-2">
            <input name="q" value="{{ $search }}" placeholder="Name, phone or email" class="px-3 py-2 rounded-lg border border-ink-200 text-sm">
            <select name="status" class="px-3 py-2 rounded-lg border border-ink-200 text-sm">
                <option value="">All</option>
                <option value="expired" @selected($status === 'expired')>Ended (restricted)</option>
                <option value="active" @selected($status === 'active')>On the plan</option>
                <option value="none" @selected($status === 'none')>Not on the plan</option>
                <option value="disabled" @selected($status === 'disabled')>Switched off</option>
            </select>
            <button class="px-4 py-2 bg-ink-900 text-white rounded-lg text-sm font-bold">Filter</button>
        </form>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-6 py-3">Owner</th><th>Status</th><th class="text-right">Beds</th><th class="text-right">Bed Wallet</th><th>Renews on</th><th class="text-right pr-6"></th></tr>
        </thead>
        <tbody>
        @forelse($owners as $o)
            <tr class="border-t border-ink-900/5">
                <td class="px-6 py-3"><div class="font-semibold">{{ $o->name }}</div><div class="text-xs text-ink-900/50">{{ $o->phone ?: $o->email }}</div></td>
                <td>
                    @if(!$o->plan_status)<span class="px-2 py-1 rounded-full text-xs bg-ink-900/5 text-ink-900/60 font-semibold">Not on plan</span>
                    @elseif($o->plan_status === 'active')<span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700 font-semibold">Active</span>
                    @elseif($o->plan_status === 'expired')<span class="px-2 py-1 rounded-full text-xs bg-rose-100 text-rose-700 font-semibold">Ended</span>
                    @else<span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700 font-semibold">Switched off</span>@endif
                </td>
                <td class="text-right">{{ $o->bed_count }}</td>
                <td class="text-right font-semibold">₹{{ number_format((float) $o->wallet_balance, 2) }}</td>
                <td class="text-xs">{{ $o->cycle_end && $o->plan_status === 'active' ? \Illuminate\Support\Carbon::parse($o->cycle_end)->format('d M Y') : '-' }}</td>
                <td class="text-right pr-6"><a href="{{ route('admin.bed-plans.owner', $o->id) }}" class="text-coral-600 font-bold">Manage →</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-6 py-10 text-center text-ink-900/50">No owners found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-4">{{ $owners->links() }}</div>
</div>

{{-- Everyone at once: deliberately hard to press by accident --}}
<div class="mt-8 p-6 rounded-2xl border border-rose-200 bg-rose-50">
    <h2 class="font-display font-bold text-lg text-rose-900">Switch the plan on for every owner who is not on it</h2>
    <p class="text-xs text-rose-900/70 mt-1">Each of those owners is charged at once for the beds they have, using the default plan price. Owners whose wallet is empty are restricted straight away until they recharge. There is no free period and no grace period. Do this only when owners know about it.</p>
    <form method="POST" action="{{ route('admin.bed-plans.activate-all') }}" class="mt-3 flex flex-wrap gap-2">
        @csrf
        <input name="confirm" placeholder="Type ACTIVATE ALL to confirm" class="px-3 py-2 rounded-lg border border-rose-200 text-sm w-72" autocomplete="off">
        <button class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-bold">Switch on for all ({{ $stats['not_on_plan'] }})</button>
    </form>
</div>

@endsection
