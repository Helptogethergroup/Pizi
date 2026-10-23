@extends('layouts.dashboard')
@section('title', 'Lead insights — Admin')
@section('content')
@php
    $S = \App\Services\LeadInsightsService::class;
    $isTelecaller = $isTelecaller ?? false;
    $indexRoute = $isTelecaller ? 'telecaller.insights' : 'admin.lead-insights.index';
    $delta = $d['prev_total'] > 0 ? round((($d['total'] - $d['prev_total']) / $d['prev_total']) * 100) : null;
    $maxDay = max(1, collect($d['daily'])->max('total'));
    $unlockPct = $d['total'] > 0 ? round($d['unlocked_of_these'] / $d['total'] * 100) : 0;
    $periodLabel = $d['from']->isSameDay($d['to']) ? $d['from']->format('d M Y') : $d['from']->format('d M Y') . ' – ' . $d['to']->format('d M Y');
    $keep = collect($query)->only(['connection', 'telecaller'])->filter()->all();
    $cc = $d['connection_counts'];
    $ccTotal = max(1, $cc['connected'] + $cc['not_connected'] + $cc['not_called']);
    $ccStyle = [
        'connected' => ['bg-emerald-50 border-emerald-200', 'text-emerald-700'],
        'not_connected' => ['bg-amber-50 border-amber-300', 'text-amber-700'],
        'not_called' => ['bg-slate-50 border-slate-200', 'text-slate-600'],
    ];
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">Lead insights</h1>
        <p class="text-ink-900/60 mt-1">What happened to the leads, day by day. <span class="font-semibold text-ink-950">{{ $periodLabel }}</span></p>
    </div>
    @unless($isTelecaller)
    <div class="flex gap-2">
        <a href="{{ route('admin.lead-insights.xlsx', $query) }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700"><i class="fa-solid fa-file-excel fa-fw"></i> Download Excel</a>
        <a href="{{ route('admin.lead-insights.pdf', $query) }}" class="px-4 py-2.5 rounded-xl bg-ink-950 text-cream text-sm font-bold hover:bg-ink-900"><i class="fa-solid fa-file-pdf fa-fw"></i> Download PDF</a>
    </div>
    @endunless
</div>

{{-- Date range --}}
<form method="GET" class="bg-white rounded-2xl border border-ink-900/10 p-4 mb-6 flex flex-wrap items-end gap-3">
    <div class="flex flex-wrap gap-2">
        @foreach($presets as $key => $label)
            <a href="{{ route($indexRoute, array_merge(['range' => $key], $keep)) }}"
               class="px-3.5 py-2 rounded-full text-sm font-semibold {{ $range === $key ? 'bg-ink-950 text-cream' : 'bg-cream border border-ink-900/10 hover:bg-ink-900/5' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="flex items-end gap-2 ml-auto flex-wrap">
        <label class="text-xs text-ink-900/60">Calls
            <select name="connection" class="block mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                <option value="">All leads</option>
                @foreach($S::CONNECTION as $key => $label)
                    <option value="{{ $key }}" @selected($d['filter_connection'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs text-ink-900/60">{{ $isTelecaller ? 'Whose leads' : 'Telecaller' }}
            <select name="telecaller" class="block mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                <option value="">{{ $isTelecaller ? 'All leads (shared pool)' : 'All telecallers' }}</option>
                @foreach($telecallerList as $t)
                    <option value="{{ $t->id }}" @selected($d['filter_telecaller'] === $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs text-ink-900/60">From
            <input type="date" name="from" value="{{ $d['from']->toDateString() }}" max="{{ now()->toDateString() }}" class="block mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
        </label>
        <label class="text-xs text-ink-900/60">To
            <input type="date" name="to" value="{{ $d['to']->toDateString() }}" max="{{ now()->toDateString() }}" class="block mt-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
        </label>
        <button class="px-4 py-2 rounded-lg bg-coral-500 text-white text-sm font-bold">Show</button>
    </div>
</form>

{{-- Connected / not connected: click one to filter the whole page --}}
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    @foreach($S::CONNECTION as $key => $label)
        <a href="{{ route($indexRoute, array_merge($query, ['connection' => $d['filter_connection'] === $key ? null : $key])) }}"
           class="rounded-2xl border p-4 flex items-center justify-between {{ $ccStyle[$key][0] }} {{ $d['filter_connection'] === $key ? 'ring-2 ring-ink-950' : '' }}">
            <div>
                <div class="text-xs uppercase tracking-wide text-ink-900/60">{{ $label }}</div>
                <div class="font-display font-black text-3xl tabular-nums {{ $ccStyle[$key][1] }}">{{ number_format($cc[$key]) }}</div>
            </div>
            <div class="text-sm font-bold {{ $ccStyle[$key][1] }}">{{ round($cc[$key] / $ccTotal * 100) }}%</div>
        </a>
    @endforeach
</div>
<p class="text-xs text-ink-900/50 -mt-4 mb-6">
    Connected = the team spoke to the person. Not connected = we called but never reached them. Not called yet = no call made. Junk leads are left out of these three.
    @if($d['filter_connection'] || $d['filter_telecaller'])
        <span class="font-semibold text-ink-950"> Filter on: everything below shows only
            @if($d['filter_connection']) “{{ $S::CONNECTION[$d['filter_connection']] }}” leads @endif
            @if($d['filter_telecaller']) of {{ optional($telecallerList->firstWhere('id', $d['filter_telecaller']))->name ?? 'this telecaller' }} @endif
            · <a class="underline" href="{{ route($indexRoute, ['range' => $range === 'custom' ? null : $range, 'from' => $range === 'custom' ? $d['from']->toDateString() : null, 'to' => $range === 'custom' ? $d['to']->toDateString() : null]) }}">clear filters</a></span>
    @endif
</p>

{{-- Headline numbers --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
        <div class="text-xs uppercase tracking-wide text-ink-900/50">New leads</div>
        <div class="font-display font-black text-4xl mt-1 tabular-nums">{{ number_format($d['total']) }}</div>
        <div class="text-xs mt-1 {{ ($delta ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
            @if($delta === null) no leads in the period before @else {{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}% vs previous {{ $d['days'] }} day{{ $d['days'] > 1 ? 's' : '' }} ({{ $d['prev_total'] }}) @endif
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
        <div class="text-xs uppercase tracking-wide text-ink-900/50">Interested</div>
        <div class="font-display font-black text-4xl mt-1 tabular-nums text-teal-700">{{ number_format($d['groups']['interested']['count']) }}</div>
        <div class="text-xs mt-1 text-ink-900/50">{{ $d['total'] > 0 ? round($d['groups']['interested']['count'] / $d['total'] * 100) : 0 }}% of new leads</div>
    </div>
    @if($isTelecaller)
    <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
        <div class="text-xs uppercase tracking-wide text-ink-900/50">Needs another call</div>
        <div class="font-display font-black text-4xl mt-1 tabular-nums text-amber-700">{{ number_format($cc['not_connected']) }}</div>
        <div class="text-xs mt-1 text-ink-900/50">{{ number_format($cc['not_called']) }} not called yet</div>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
        <div class="text-xs uppercase tracking-wide text-ink-900/50">Unlocked by owners</div>
        <div class="font-display font-black text-4xl mt-1 tabular-nums text-coral-500">{{ number_format($d['unlocks']) }}</div>
        <div class="text-xs mt-1 text-ink-900/50">{{ number_format($d['credits']) }} credits spent</div>
    </div>
    @endif
    <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
        <div class="text-xs uppercase tracking-wide text-ink-900/50">Closed (won)</div>
        <div class="font-display font-black text-4xl mt-1 tabular-nums text-emerald-700">{{ number_format($d['groups']['won']['count']) }}</div>
        <div class="text-xs mt-1 text-ink-900/50">{{ $d['groups']['lost']['count'] }} not interested / lost</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    {{-- Where the new leads are now --}}
    <div class="{{ $isTelecaller ? 'lg:col-span-3' : 'lg:col-span-2' }} bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-xl">Where the new leads stand now</h2>
        <p class="text-sm text-ink-900/50 mb-4">Leads that came in during this period, by their status today.</p>
        @if($d['total'] > 0)
            <div class="flex h-4 rounded-full overflow-hidden mb-5 bg-ink-900/5">
                @foreach($d['groups'] as $g)
                    @if($g['count'] > 0)
                        <div title="{{ $g['label'] }}: {{ $g['count'] }}" style="width: {{ $g['count'] / $d['total'] * 100 }}%; background: {{ $g['colour'] }}"></div>
                    @endif
                @endforeach
            </div>
        @endif
        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-2">
            @foreach($d['groups'] as $g)
                @continue($g['count'] === 0 && $g['label'] === 'Other')
                <div class="flex items-center justify-between text-sm py-1.5 border-b border-ink-900/5">
                    <span class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background: {{ $g['colour'] }}"></span>{{ $g['label'] }}</span>
                    <span class="font-bold tabular-nums">{{ $g['count'] }}
                        <span class="text-ink-900/40 font-normal text-xs ml-1">{{ $d['total'] > 0 ? round($g['count'] / $d['total'] * 100) : 0 }}%</span></span>
                </div>
            @endforeach
        </div>
        <details class="mt-4 text-sm">
            <summary class="cursor-pointer text-ink-900/60 font-semibold">Exact statuses</summary>
            <div class="mt-2 grid sm:grid-cols-2 gap-x-8 gap-y-1">
                @foreach($d['by_status'] as $status => $count)
                    <div class="flex justify-between"><span>{{ $S::pretty($status) }}</span><span class="font-bold tabular-nums">{{ $count }}</span></div>
                @endforeach
            </div>
        </details>
    </div>

    {{-- Owners and reports --}}
    @unless($isTelecaller)
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-xl mb-4">Owners and quality</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt>New leads an owner unlocked</dt><dd class="font-bold tabular-nums">{{ $d['unlocked_of_these'] }} <span class="text-ink-900/40 font-normal">({{ $unlockPct }}%)</span></dd></div>
            <div class="flex justify-between"><dt>Called 3 times, nobody answered</dt><dd class="font-bold tabular-nums">{{ $d['never_answered'] }}</dd></div>
            <div class="flex justify-between"><dt>Owner reports sent</dt><dd class="font-bold tabular-nums">{{ $d['reports']['sent'] }}</dd></div>
            <div class="flex justify-between"><dt>Reports approved</dt><dd class="font-bold tabular-nums">{{ $d['reports']['approved'] }}</dd></div>
            <div class="flex justify-between"><dt>Reports waiting</dt><dd class="font-bold tabular-nums {{ $d['reports']['pending'] ? 'text-amber-700' : '' }}">{{ $d['reports']['pending'] }}</dd></div>
            <div class="flex justify-between"><dt>Credits refunded</dt><dd class="font-bold tabular-nums">{{ $d['reports']['refunded'] }}</dd></div>
        </dl>
        @if($d['reports']['pending'] > 0)
            <a href="{{ route('admin.lead-reports.index') }}" class="mt-4 block text-center text-sm font-bold py-2 rounded-lg bg-amber-100 text-amber-900">Review waiting reports</a>
        @endif
    </div>
    @endunless
</div>

{{-- Day by day --}}
<div class="bg-white rounded-2xl border border-ink-900/10 p-6 mb-6">
    <h2 class="font-display font-bold text-xl">Day by day</h2>
    <p class="text-sm text-ink-900/50 mb-4">New leads of each day and where they are now.@unless($isTelecaller) “Unlocked” counts the owners’ unlocks made that day.@endunless</p>

    @if($d['days'] > 1)
        <div class="flex items-end gap-1 h-28 mb-5" aria-hidden="true">
            @foreach($d['daily'] as $row)
                <div class="flex-1 min-w-0 flex flex-col justify-end h-full" title="{{ $row['label'] }}: {{ $row['total'] }} new leads">
                    <div class="w-full rounded-t bg-coral-500/80" style="height: {{ max(2, $row['total'] / $maxDay * 100) }}%"></div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[860px]">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-ink-900/50 border-b border-ink-900/10">
                    <th class="py-2 pr-3">Date</th>
                    <th class="py-2 px-2 text-right">New leads</th>
                    @foreach($d['groups'] as $key => $g)
                        @continue($key === 'other')
                        <th class="py-2 px-2 text-right" style="color: {{ $g['colour'] }}">{{ explode(' /', $g['label'])[0] }}</th>
                    @endforeach
                    @unless($isTelecaller)
                    <th class="py-2 px-2 text-right">Unlocked</th>
                    <th class="py-2 pl-2 text-right">Credits</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @foreach(array_reverse($d['daily']) as $row)
                    <tr class="border-b border-ink-900/5 {{ $row['total'] === 0 && $row['unlocks'] === 0 ? 'text-ink-900/30' : '' }}">
                        <td class="py-2 pr-3 font-semibold whitespace-nowrap">{{ $row['label'] }}</td>
                        <td class="py-2 px-2 text-right font-bold tabular-nums">{{ $row['total'] }}</td>
                        @foreach($d['groups'] as $key => $g)
                            @continue($key === 'other')
                            <td class="py-2 px-2 text-right tabular-nums">{{ $row[$key] ?: '·' }}</td>
                        @endforeach
                        @unless($isTelecaller)
                        <td class="py-2 px-2 text-right tabular-nums font-semibold">{{ $row['unlocks'] ?: '·' }}</td>
                        <td class="py-2 pl-2 text-right tabular-nums">{{ $row['credits'] ?: '·' }}</td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@unless($isTelecaller)
{{-- Who unlocked what, and when --}}
<div class="bg-white rounded-2xl border border-ink-900/10 p-6 mb-6">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-1">
        <h2 class="font-display font-bold text-xl">Unlocks: who and when <span class="text-ink-900/40 font-normal text-base">({{ count($d['unlock_list']) }})</span></h2>
    </div>
    <p class="text-sm text-ink-900/50 mb-4">Every lead an owner unlocked in this period.
        @if($d['test_unlocks'] > 0) <span class="font-semibold text-amber-700">{{ $d['test_unlocks'] }} unlock{{ $d['test_unlocks'] > 1 ? 's' : '' }} by test accounts are left out.</span> @endif
    </p>
    @if(count($d['unlock_list']))
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[820px]">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-ink-900/50 border-b border-ink-900/10">
                        <th class="py-2 pr-3">When</th><th class="py-2 px-2">Owner</th><th class="py-2 px-2">Owner phone</th>
                        <th class="py-2 px-2">Lead</th><th class="py-2 px-2">Type</th><th class="py-2 pl-2 text-right">Credits</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($d['unlock_list'] as $u)
                        <tr class="border-b border-ink-900/5">
                            <td class="py-2 pr-3 whitespace-nowrap font-semibold">{{ \Carbon\Carbon::parse($u['at'])->format('d M, h:i A') }}</td>
                            <td class="py-2 px-2">
                                <a class="font-semibold text-ink-950 hover:underline" href="{{ route('admin.users.show', $u['owner_id']) }}">{{ $u['owner_name'] }}</a>
                                @if($u['payer_id'] && $u['payer_id'] != $u['owner_id'])
                                    <div class="text-xs text-ink-900/50">by manager {{ $u['payer_name'] }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-2 font-mono"><a class="text-blue-700" href="tel:{{ $u['owner_phone'] }}">{{ $u['owner_phone'] ?: '—' }}</a></td>
                            <td class="py-2 px-2">{{ $u['lead_name'] ?? 'Deleted lead' }} <span class="text-ink-900/40 text-xs">#{{ $u['lead_id'] }}</span>
                                @if($u['lead_phone'])<div class="text-xs font-mono text-ink-900/60">{{ $u['lead_phone'] }}</div>@endif</td>
                            <td class="py-2 px-2">{{ $S::pretty($u['lead_type'] ?: 'direct') }}</td>
                            <td class="py-2 pl-2 text-right tabular-nums font-bold">{{ $u['credits'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-ink-900/50">No lead was unlocked in this period.</p>
    @endif
</div>
@endunless

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-lg mb-3">Call results</h2>
        @forelse($d['calls'] as $s => $c)
            <div class="flex justify-between text-sm py-1.5 border-b border-ink-900/5"><span>{{ $S::pretty($s) }}</span><span class="font-bold tabular-nums">{{ $c }}</span></div>
        @empty
            <p class="text-sm text-ink-900/50">No leads in this period.</p>
        @endforelse
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-lg mb-3">Where leads came from</h2>
        @forelse($d['sources'] as $s => $c)
            <div class="flex justify-between text-sm py-1.5 border-b border-ink-900/5"><span>{{ $S::pretty($s) }}</span><span class="font-bold tabular-nums">{{ $c }}</span></div>
        @empty
            <p class="text-sm text-ink-900/50">No leads in this period.</p>
        @endforelse
    </div>
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-lg mb-3">Lead type</h2>
        @forelse($d['types'] as $s => $c)
            <div class="flex justify-between text-sm py-1.5 border-b border-ink-900/5"><span>{{ $S::pretty($s) }}</span><span class="font-bold tabular-nums">{{ $c }}</span></div>
        @empty
            <p class="text-sm text-ink-900/50">No leads in this period.</p>
        @endforelse
    </div>
</div>

{{-- Telecallers (admin only) --}}
@unless($isTelecaller)
<div class="bg-white rounded-2xl border border-ink-900/10 p-6">
    <h2 class="font-display font-bold text-xl mb-4">Telecallers</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[780px]">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-ink-900/50 border-b border-ink-900/10">
                    <th class="py-2 pr-3">Name</th><th class="py-2 px-2 text-right">Leads given</th>
                    <th class="py-2 px-2 text-right text-emerald-700">Connected</th><th class="py-2 px-2 text-right text-amber-700">Not connected</th><th class="py-2 px-2 text-right">Not called yet</th>
                    <th class="py-2 px-2 text-right">Interested</th><th class="py-2 px-2 text-right">Closed</th><th class="py-2 px-2 text-right">Not interested</th><th class="py-2 pl-2 text-right">Junk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($d['telecallers'] as $t)
                    <tr class="border-b border-ink-900/5">
                        <td class="py-2 pr-3 font-semibold">{{ $t['name'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums font-bold">{{ $t['assigned'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums font-semibold text-emerald-700">{{ $t['connected'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums font-semibold text-amber-700">{{ $t['not_connected'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums">{{ $t['not_called'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums">{{ $t['interested'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums">{{ $t['won'] }}</td>
                        <td class="py-2 px-2 text-right tabular-nums">{{ $t['lost'] }}</td>
                        <td class="py-2 pl-2 text-right tabular-nums">{{ $t['junk'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-4 text-ink-900/50">No leads were given to a telecaller in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endunless
@endsection
