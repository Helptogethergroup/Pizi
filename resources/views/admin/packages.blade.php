@extends('layouts.dashboard')
@section('title', 'Credit Packages')
@section('content')

<h1 class="font-display font-black text-3xl mb-2">Credit Packages</h1>
<p class="text-ink-900/60">Manage what owners can buy.</p>

{{-- Stats --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
    <div class="p-6 rounded-2xl bg-emerald-100 text-emerald-900">
        <div class="text-xs uppercase tracking-wide opacity-70">Total revenue</div>
        <div class="font-display font-black text-3xl mt-2">₹{{ number_format($stats['total_revenue']) }}</div>
    </div>
    <div class="p-6 rounded-2xl bg-ink-900 text-cream">
        <div class="text-xs uppercase tracking-wide opacity-70">Successful payments</div>
        <div class="font-display font-black text-3xl mt-2">{{ $stats['total_payments'] }}</div>
    </div>
    <div class="p-6 rounded-2xl bg-rose-100 text-rose-900">
        <div class="text-xs uppercase tracking-wide opacity-70">Failed payments</div>
        <div class="font-display font-black text-3xl mt-2">{{ $stats['failed_payments'] }}</div>
    </div>
</div>

{{-- Existing packages --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
    @foreach($packages as $pkg)
        <div class="p-6 bg-white rounded-2xl border-2 {{ $pkg->is_active ? 'border-ink-900/10' : 'border-rose-200 opacity-60' }}">
            <div class="flex justify-between items-start">
                <h3 class="font-display font-bold text-xl">{{ $pkg->name }}</h3>
                @if($pkg->is_popular)<span class="text-xs px-2 py-1 rounded-full bg-coral-500 text-white">⭐ Popular</span>@endif
            </div>
            <div class="font-display font-black text-3xl mt-2">₹{{ number_format($pkg->price_inr) }}</div>
            <div class="text-sm text-ink-900/60">{{ $pkg->total_credits }} credits ({{ $pkg->credits }} + {{ $pkg->bonus_credits }} bonus)</div>
            <div class="flex gap-2 mt-4">
                <form method="POST" action="{{ route('admin.packages.toggle', $pkg) }}">@csrf @method('PATCH')
                    <button class="text-xs px-3 py-1.5 rounded-lg border border-ink-900/15">{{ $pkg->is_active ? 'Disable' : 'Enable' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.packages.destroy', $pkg) }}" onsubmit="return confirm('Delete this package?')">@csrf @method('DELETE')
                    <button class="text-xs px-3 py-1.5 rounded-lg border border-rose-300 text-rose-600">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

{{-- Add new --}}
<div class="mt-10 bg-white p-8 rounded-2xl border border-ink-900/10">
    <h2 class="font-display font-bold text-2xl mb-4">+ Add new package</h2>
    <form method="POST" action="{{ route('admin.packages.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Name</label>
            <input name="name" required placeholder="e.g. Starter Pack" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Price (₹)</label>
            <input name="price_inr" type="number" required placeholder="999" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Base credits</label>
            <input name="credits" type="number" required placeholder="50" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Bonus credits</label>
            <input name="bonus_credits" type="number" placeholder="10" value="0" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Description (optional)</label>
            <input name="description" placeholder="Best for occasional users" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_popular" value="1" class="rounded">
            Mark as "Most Popular"
        </label>
        <div class="md:col-span-2">
            <button class="px-6 py-3 bg-coral-500 text-white rounded-xl font-bold">Create package</button>
        </div>
    </form>
</div>

{{-- Recent payments --}}
<div class="mt-10 bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <div class="p-6 border-b border-ink-900/10">
        <h2 class="font-display font-bold text-xl">Recent payments</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr><th class="px-6 py-3">When</th><th>Owner</th><th>Package</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($recentPayments as $p)
                <tr class="border-t border-ink-900/5">
                    <td class="px-6 py-3 text-xs">{{ $p->created_at->format('d M, h:i A') }}</td>
                    <td class="text-xs">{{ $p->user?->name }}</td>
                    <td class="text-xs">{{ $p->package?->name ?? '—' }}</td>
                    <td class="font-bold">₹{{ number_format($p->amount_inr) }}</td>
                    <td>
                        @switch($p->status)
                            @case('paid')<span class="px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700">✓ Paid</span>@break
                            @case('failed')<span class="px-2 py-1 rounded-full text-xs bg-rose-100 text-rose-700">✗ Failed</span>@break
                            @default<span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">{{ $p->status }}</span>
                        @endswitch
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-ink-900/50">No payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection