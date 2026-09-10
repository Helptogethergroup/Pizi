@php
    $tier = $lead->match_score >= 85 ? 'Hot Match'
          : ($lead->match_score >= 70 ? 'Great Match'
          : ($lead->match_score >= 50 ? 'Good Match' : 'Possible Match'));
    $tierClass = $lead->match_score >= 85 ? 'bg-coral-500 text-white'
               : ($lead->match_score >= 70 ? 'bg-orange-100 text-orange-800'
               : ($lead->match_score >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-700'));
    $emoji = $lead->match_score >= 85 ? '🔥' : ($lead->match_score >= 70 ? '⭐' : ($lead->match_score >= 50 ? '✓' : ''));
@endphp
<div class="bg-white p-5 rounded-2xl border-2 {{ $lead->area_match ?? false ? 'border-coral-500' : 'border-ink-900/10' }}">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h3 class="font-display font-bold text-lg">{{ $lead->name }}</h3>
                @if($lead->area_match ?? false)
                    <span class="px-2 py-0.5 rounded-full text-xs bg-coral-500 text-white font-bold">📍 Your Area</span>
                @elseif($lead->city_unknown ?? false)
                    <span class="px-2 py-0.5 rounded-full text-xs bg-slate-200 text-slate-700 font-bold">❓ City not specified</span>
                @endif
            </div>
            <div class="text-xs text-ink-900/60">
                {{ $lead->matched_property?->name ?? '—' }} · 📍 {{ $lead->display_location }}
            </div>
        </div>
        <div class="text-right flex-shrink-0">
            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $tierClass }}">{{ $emoji }} {{ $tier }}</span>
            <div class="text-xs text-ink-900/50 mt-1">Score: {{ $lead->match_score }}/100</div>
        </div>
    </div>

    {{-- Date --}}
    <div class="text-xs text-ink-900/60 mb-3">📅 {{ $lead->created_at->format('d M, h:i A') }}</div>

    {{-- Lead requirements --}}
    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
        <div>
            <div class="text-xs text-ink-900/50 uppercase">📍 Looking in</div>
            <div class="font-semibold">{{ $lead->preferred_locality ?? '—' }}</div>
            <div class="text-xs text-ink-900/60">{{ $lead->preferred_city ?? '' }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-900/50 uppercase">💰 Budget</div>
            <div class="font-semibold">
                @if($lead->budget_max)
                    ₹{{ number_format($lead->budget_min ?? 0) }}–{{ number_format($lead->budget_max) }}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    @if($lead->owner_safe_message)
        <div class="bg-cream p-3 rounded-lg text-sm mb-3">
            <div class="text-xs text-ink-900/50 uppercase">Message</div>
            <p class="italic text-ink-900/80 mt-1">"{{ Str::limit($lead->owner_safe_message, 120) }}"</p>
        </div>
    @endif

    {{-- Contact section --}}
    <div class="border-t border-ink-900/5 pt-3">
        @if($lead->is_unlocked)
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <div class="text-xs text-ink-900/50 uppercase">📞 Phone</div>
                    <a href="tel:{{ $lead->phone }}" class="font-bold text-emerald-700">{{ $lead->phone }}</a>
                </div>
                <div>
                    <div class="text-xs text-ink-900/50 uppercase">📧 Email</div>
                    <a href="mailto:{{ $lead->email }}" class="font-bold text-emerald-700 truncate block">{{ $lead->email ?? '—' }}</a>
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="flex-1 text-center py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">💬 WhatsApp</a>
                <a href="tel:{{ $lead->phone }}" class="flex-1 text-center py-2 bg-blue-500 text-white rounded-lg text-sm font-bold">📞 Call</a>
            </div>
            <a href="{{ route('owner.leads.show', $lead) }}" class="mt-2 block w-full text-center py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold hover:bg-ink-900">
                📋 Manage Lead — {{ $lead->status ? str_replace('_',' ',$lead->status) : 'New' }}
            </a>
            @if($lead->converted_tenant_id)
                <a href="{{ route('owner.tenants.show', $lead->converted_tenant_id) }}" class="mt-2 block w-full text-center py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">
                    ✅ Converted — View Tenant
                </a>
            @endif
        @else
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <div class="text-xs text-ink-900/50 uppercase">📞 Phone</div>
                    <div class="font-mono text-ink-900/40">🔒 {{ substr($lead->phone, 0, 2) }}XXXXXX{{ substr($lead->phone, -2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-900/50 uppercase">📧 Email</div>
                    <div class="font-mono text-ink-900/40 truncate">🔒 {{ $lead->email ? substr($lead->email, 0, 2) . 'XXX@' . explode('@', $lead->email)[1] : '—' }}</div>
                </div>
            </div>

            @if($lead->affordable)
                <form method="POST" action="{{ route('owner.leads.unlock', $lead) }}" onsubmit="return confirm('Unlock this lead for {{ $lead->unlock_cost }} credits?');">
                    @csrf
                    <button class="w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold text-sm">
                        🔓 Unlock for {{ $lead->unlock_cost }} credits
                    </button>
                </form>
            @else
                <a href="{{ route('owner.packages') }}" class="block w-full text-center py-2.5 bg-amber-500 text-white rounded-lg font-bold text-sm">
                    💳 Recharge to unlock ({{ $lead->unlock_cost }} credits needed)
                </a>
            @endif

            <form method="POST" action="{{ route('owner.leads.reportJunk', $lead) }}" onsubmit="return confirm('Report this as spam/junk? It will stop showing to all owners.');" class="mt-2">
                @csrf
                <button class="w-full py-1.5 text-xs text-ink-900/40 hover:text-rose-600 font-semibold">🚩 Report as spam/junk</button>
            </form>
        @endif
    </div>
</div>
