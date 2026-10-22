@php
    $tier = $lead->match_score >= 85 ? 'Hot Match'
          : ($lead->match_score >= 70 ? 'Great Match'
          : ($lead->match_score >= 50 ? 'Good Match' : 'Possible Match'));
    $tierClass = $lead->match_score >= 85 ? 'bg-coral-500 text-white'
               : ($lead->match_score >= 70 ? 'bg-orange-100 text-orange-800'
               : ($lead->match_score >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-700'));
    $emoji = $lead->match_score >= 85 ? '🔥' : ($lead->match_score >= 70 ? '⭐' : ($lead->match_score >= 50 ? '✓' : ''));
@endphp
<div class="bg-white p-5 rounded-2xl border-2 {{ $lead->area_match ?? false ? 'border-coral-500' : 'border-ink-900/10' }} relative">

    @if($lead->is_new ?? false)
        <span class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-500 text-white shadow">● NEW</span>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h3 class="font-display font-bold text-lg">{{ $lead->name }}</h3>
                @if($lead->area_match ?? false)
                    <span class="px-2 py-0.5 rounded-full text-xs bg-coral-500 text-white font-bold"><i class="fa-solid fa-location-dot fa-fw"></i> Your Area</span>
                @endif
            </div>
        </div>
        <div class="text-right flex-shrink-0">
            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $tierClass }}">{{ $emoji }} {{ $tier }}</span>
            <div class="text-xs text-ink-900/50 mt-1">Score: {{ $lead->match_score }}/100</div>
        </div>
    </div>

    {{-- Date --}}
    <div class="text-xs text-ink-900/60 mb-3"><i class="fa-solid fa-calendar-days fa-fw"></i> {{ $lead->created_at->format('d M, h:i A') }}</div>

    {{-- Lead requirements --}}
    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
        <div>
            <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-location-dot fa-fw"></i> Looking in</div>
            <div class="font-semibold">{{ $lead->preferred_locality ?? '—' }}</div>
            <div class="text-xs text-ink-900/60">{{ $lead->preferred_city ?? '' }}</div>
        </div>
        <div>
            <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-sack-dollar fa-fw"></i> Budget</div>
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
                    <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-phone fa-fw"></i> Phone</div>
                    <a href="tel:{{ $lead->phone }}" class="font-bold text-emerald-700">{{ $lead->phone }}</a>
                </div>
                <div>
                    <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-envelope fa-fw"></i> Email</div>
                    <a href="mailto:{{ $lead->email }}" class="font-bold text-emerald-700 truncate block">{{ $lead->email ?? '—' }}</a>
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" class="flex-1 text-center py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
                <a href="tel:{{ $lead->phone }}" class="flex-1 text-center py-2 bg-blue-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
            </div>
            <a href="{{ route('owner.leads.show', $lead) }}" class="mt-2 block w-full text-center py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold hover:bg-ink-900">
                <i class="fa-solid fa-clipboard-list fa-fw"></i> Manage Lead — {{ $lead->status ? str_replace('_',' ',$lead->status) : 'New' }}
            </a>
            @php($reportState = ($reportStatuses ?? [])[$lead->id] ?? null)
            @if($reportState)
                <div class="mt-2 text-xs font-semibold text-center py-2 rounded-lg
                    {{ $reportState === 'pending' ? 'bg-amber-100 text-amber-800' : ($reportState === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                    @if($reportState === 'pending') <i class="fa-solid fa-hourglass-half fa-fw"></i> You reported this lead. We are checking it.
                    @elseif($reportState === 'approved') <i class="fa-solid fa-circle-check fa-fw"></i> Reported. Your credits were returned.
                    @else <i class="fa-solid fa-circle-info fa-fw"></i> Reported. We could not confirm the problem. @endif
                </div>
            @elseif(!$lead->converted_tenant_id)
                <details class="mt-2 text-xs">
                    <summary class="cursor-pointer text-center py-2 rounded-lg border border-rose-200 text-rose-700 font-semibold hover:bg-rose-50">
                        <i class="fa-solid fa-flag fa-fw"></i> This lead is not genuine? Report it
                    </summary>
                    <form method="POST" action="{{ route('owner.leads.report', $lead) }}" class="mt-2 space-y-2 p-3 rounded-lg bg-rose-50/60 border border-rose-100">
                        @csrf
                        <select name="reason" required class="w-full px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                            <option value="">What was wrong?</option>
                            @foreach(\App\Models\LeadReport::REASONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" maxlength="500" placeholder="Anything else we should know (optional)"
                            class="w-full px-3 py-2 rounded-lg border border-ink-900/15 text-sm"></textarea>
                        <button class="w-full py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm">Send report</button>
                        <p class="text-ink-900/50">Within {{ config('leads.report_hours', 72) }} hours of unlocking. We check the number; if you are right, your credits come back.</p>
                    </form>
                </details>
            @endif
            @if($lead->converted_tenant_id)
                <a href="{{ route('owner.tenants.show', $lead->converted_tenant_id) }}" class="mt-2 block w-full text-center py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">
                    <i class="fa-solid fa-circle-check fa-fw"></i> Converted — View Tenant
                </a>
            @endif
        @else
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-phone fa-fw"></i> Phone</div>
                    <div class="font-mono text-ink-900/40"><i class="fa-solid fa-lock fa-fw"></i> {{ substr($lead->phone, 0, 2) }}XXXXXX{{ substr($lead->phone, -2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-900/50 uppercase"><i class="fa-solid fa-envelope fa-fw"></i> Email</div>
                    <div class="font-mono text-ink-900/40 truncate"><i class="fa-solid fa-lock fa-fw"></i> {{ $lead->email ? substr($lead->email, 0, 2) . 'XXX@' . explode('@', $lead->email)[1] : '—' }}</div>
                </div>
            </div>

            @if($lead->affordable)
                <form method="POST" action="{{ route('owner.leads.unlock', $lead) }}" onsubmit="return confirm('Unlock this lead for {{ $lead->unlock_cost }} credits?');">
                    @csrf
                    <button class="w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold text-sm">
                        <i class="fa-solid fa-lock-open fa-fw"></i> Unlock for {{ $lead->unlock_cost }} credits
                    </button>
                </form>
            @else
                <a href="{{ route('owner.packages') }}" class="block w-full text-center py-2.5 bg-amber-500 text-white rounded-lg font-bold text-sm">
                    <i class="fa-solid fa-credit-card fa-fw"></i> Recharge to unlock ({{ $lead->unlock_cost }} credits needed)
                </a>
            @endif

            <form method="POST" action="{{ route('owner.leads.reportJunk', $lead) }}" onsubmit="return confirm('Report this as spam/junk? It will stop showing to all owners.');" class="mt-2">
                @csrf
                <button class="w-full py-1.5 text-xs text-ink-900/40 hover:text-rose-600 font-semibold"><i class="fa-solid fa-flag fa-fw"></i> Report as spam/junk</button>
            </form>
        @endif
    </div>
</div>
