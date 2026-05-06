@extends('layouts.dashboard')
@section('title', 'Lead Pricing')
@section('content')

<h1 class="font-display font-black text-3xl mb-2">Lead Pricing</h1>
<p class="text-ink-900/60">Set the credit cost for each lead type. Owners spend these credits to unlock leads.</p>

<form method="POST" action="{{ route('admin.pricing.update') }}" class="mt-8">
    @csrf @method('PATCH')
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($pricing as $i => $p)
            <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-display font-bold text-xl capitalize">{{ $p->lead_type }} Leads</h3>
                        <p class="text-xs text-ink-900/60 mt-1">
                            @switch($p->lead_type)
                                @case('direct') From property listing pages @break
                                @case('verified') Verified by tele-caller @break
                                @case('converted') Already visited / converted @break
                                @case('manual') Manually added by staff @break
                            @endswitch
                        </p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="pricing[{{ $i }}][id]" value="{{ $p->id }}">
                        <input type="checkbox" name="pricing[{{ $i }}][is_active]" value="1" @checked($p->is_active) class="rounded">
                        Active
                    </label>
                </div>
                
                <label class="text-xs font-semibold text-ink-900/60 uppercase">Credit cost to unlock</label>
                <div class="flex items-center gap-2 mt-2">
                    <input name="pricing[{{ $i }}][credit_cost]" type="number" min="0" required
                           value="{{ $p->credit_cost }}"
                           class="flex-1 px-4 py-3 rounded-xl border-2 border-ink-900/15 text-3xl font-bold focus:border-coral-500 outline-none">
                    <span class="text-ink-900/60">credits</span>
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="mt-8 flex gap-3">
        <button class="px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Save pricing</button>
    </div>
</form>

<div class="mt-12 p-6 rounded-2xl bg-amber-50 border border-amber-200">
    <h3 class="font-display font-bold text-lg text-amber-900">💡 How pricing works</h3>
    <ul class="text-sm text-amber-900/80 mt-3 space-y-1 list-disc pl-5">
        <li>When a PG owner clicks "Unlock" on a lead, these credits are deducted from their wallet.</li>
        <li>Higher quality leads (Verified / Converted) should cost more credits than raw Direct leads.</li>
        <li>Setting cost to 0 means free unlocks. Disabling makes that lead type unavailable.</li>
        <li>Owners with insufficient credits will see a "Recharge wallet" prompt instead of unlock.</li>
    </ul>
</div>

@endsection