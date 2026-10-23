<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #14213d; }
    h1 { font-size: 20px; margin: 0; }
    h2 { font-size: 13px; margin: 18px 0 6px; color: #0f2748; }
    .sub { color: #64748b; margin: 2px 0 12px; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px; }
    .kpi { border: 1px solid #dbe2ec; border-radius: 6px; padding: 8px 10px; }
    .kpi .n { font-size: 20px; font-weight: bold; }
    .kpi .l { font-size: 8px; text-transform: uppercase; color: #64748b; }
    table.t { width: 100%; border-collapse: collapse; }
    table.t th { background: #0f2748; color: #fff; text-align: right; padding: 5px 6px; font-size: 9px; }
    table.t th:first-child, table.t td:first-child { text-align: left; }
    table.t td { padding: 4px 6px; text-align: right; border-bottom: 1px solid #e5eaf2; }
    table.t tr:nth-child(even) td { background: #f6f8fb; }
    .bar { height: 9px; }
    .two td { vertical-align: top; width: 50%; }
</style>
</head>
<body>
@php
    $S = \App\Services\LeadInsightsService::class;
    $period = $d['from']->isSameDay($d['to']) ? $d['from']->format('d M Y') : $d['from']->format('d M Y') . ' to ' . $d['to']->format('d M Y');
@endphp
<h1>Pizi lead insights</h1>
<div class="sub">{{ $period }} &middot; made {{ now()->format('d M Y, h:i A') }}</div>

<table class="kpis"><tr>
    <td class="kpi"><div class="l">New leads</div><div class="n">{{ $d['total'] }}</div></td>
    <td class="kpi"><div class="l">Interested</div><div class="n">{{ $d['groups']['interested']['count'] }}</div></td>
    <td class="kpi"><div class="l">Unlocked by owners</div><div class="n">{{ $d['unlocks'] }}</div><div class="l">{{ $d['credits'] }} credits</div></td>
    <td class="kpi"><div class="l">Closed (won)</div><div class="n">{{ $d['groups']['won']['count'] }}</div></td>
    <td class="kpi"><div class="l">Not interested / lost</div><div class="n">{{ $d['groups']['lost']['count'] }}</div></td>
    <td class="kpi"><div class="l">Junk</div><div class="n">{{ $d['groups']['junk']['count'] }}</div></td>
</tr></table>

<h2>Where the new leads stand now</h2>
<table class="t">
    <tr><th>Status group</th><th>Leads</th><th>Share</th></tr>
    @foreach($d['groups'] as $g)
        <tr><td>{{ $g['label'] }}</td><td>{{ $g['count'] }}</td><td>{{ $d['total'] > 0 ? round($g['count'] / $d['total'] * 100) : 0 }}%</td></tr>
    @endforeach
</table>

<h2>Day by day</h2>
<table class="t">
    <tr>
        <th>Date</th><th>New leads</th>
        @foreach($d['groups'] as $key => $g) @continue($key === 'other') <th>{{ explode(' /', $g['label'])[0] }}</th> @endforeach
        <th>Unlocked</th><th>Credits</th>
    </tr>
    @foreach($d['daily'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td><td><b>{{ $row['total'] }}</b></td>
            @foreach($d['groups'] as $key => $g) @continue($key === 'other') <td>{{ $row[$key] }}</td> @endforeach
            <td>{{ $row['unlocks'] }}</td><td>{{ $row['credits'] }}</td>
        </tr>
    @endforeach
</table>

<table class="two" style="width:100%; margin-top:6px"><tr>
<td style="padding-right:10px">
    <h2>Call results</h2>
    <table class="t"><tr><th>Result</th><th>Leads</th></tr>
        @foreach($d['calls'] as $s => $c)<tr><td>{{ $S::pretty($s) }}</td><td>{{ $c }}</td></tr>@endforeach
        <tr><td>Called 3 times, nobody answered</td><td>{{ $d['never_answered'] }}</td></tr>
    </table>
    <h2>Owner reports</h2>
    <table class="t"><tr><th>What</th><th>Number</th></tr>
        <tr><td>Reports sent</td><td>{{ $d['reports']['sent'] }}</td></tr>
        <tr><td>Approved</td><td>{{ $d['reports']['approved'] }}</td></tr>
        <tr><td>Waiting</td><td>{{ $d['reports']['pending'] }}</td></tr>
        <tr><td>Credits refunded</td><td>{{ $d['reports']['refunded'] }}</td></tr>
    </table>
</td>
<td style="padding-left:10px">
    <h2>Where leads came from</h2>
    <table class="t"><tr><th>Source</th><th>Leads</th></tr>
        @foreach($d['sources'] as $s => $c)<tr><td>{{ $S::pretty($s) }}</td><td>{{ $c }}</td></tr>@endforeach
    </table>
    <h2>Lead type</h2>
    <table class="t"><tr><th>Type</th><th>Leads</th></tr>
        @foreach($d['types'] as $s => $c)<tr><td>{{ $S::pretty($s) }}</td><td>{{ $c }}</td></tr>@endforeach
    </table>
</td>
</tr></table>

@if(count($d['telecallers']))
<h2>Telecallers</h2>
<table class="t">
    <tr><th>Name</th><th>Leads given</th><th>Called</th><th>Interested</th><th>Closed</th><th>Not interested</th><th>Junk</th></tr>
    @foreach($d['telecallers'] as $t)
        <tr><td>{{ $t['name'] }}</td><td>{{ $t['assigned'] }}</td><td>{{ $t['called'] }}</td><td>{{ $t['interested'] }}</td><td>{{ $t['won'] }}</td><td>{{ $t['lost'] }}</td><td>{{ $t['junk'] }}</td></tr>
    @endforeach
</table>
@endif
</body>
</html>
