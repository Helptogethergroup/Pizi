<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeadInsightsService;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin > Lead insights: a date-wise picture of the lead pipeline, with Excel and PDF downloads.
 */
class LeadInsightsController extends Controller
{
    private const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
    ];

    public function index(Request $request, LeadInsightsService $service)
    {
        [$from, $to, $range] = $this->range($request);
        $data = $service->build($from, $to, $this->connection($request), $this->telecaller($request));

        return view('admin.lead-insights.index', [
            'd' => $data,
            'range' => $range,
            'presets' => self::PRESETS,
            'query' => $request->only(['range', 'from', 'to', 'connection', 'telecaller']),
            // a telecaller sees the shared pool, without owner credits or the other telecallers' table
            'isTelecaller' => $this->isTelecaller(),
            'telecallerList' => $this->isTelecaller()
                ? collect([(object) ['id' => (int) auth()->id(), 'name' => 'My leads only']])
                : \App\Models\User::where('role', 'telecaller')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function xlsx(Request $request, LeadInsightsService $service)
    {
        [$from, $to] = $this->range($request);
        $d = $service->build($from, $to, $this->connection($request), $this->telecaller($request));

        $groupKeys = array_keys(LeadInsightsService::GROUPS);
        $groupLabels = array_map(fn ($g) => $g['label'], $d['groups']);

        $summary = [['What', 'Number']];
        $summary[] = ['Period', $d['from']->format('d M Y') . ' to ' . $d['to']->format('d M Y')];
        if ($d['filter_connection']) {
            $summary[] = ['Filter: calls', LeadInsightsService::CONNECTION[$d['filter_connection']]];
        }
        if ($d['filter_telecaller']) {
            $summary[] = ['Filter: telecaller', optional(\App\Models\User::find($d['filter_telecaller']))->name];
        }
        $summary[] = ['New leads', $d['total']];
        $summary[] = ['New leads in the period before this one', $d['prev_total']];
        foreach ($d['groups'] as $g) {
            $summary[] = [$g['label'], $g['count']];
        }
        foreach (LeadInsightsService::CONNECTION as $k => $label) {
            $summary[] = [$label . ' (all new leads, junk left out)', $d['connection_counts'][$k]];
        }
        $summary[] = ['Unlocks by owners in this period', $d['unlocks']];
        $summary[] = ['Credits spent on those unlocks', $d['credits']];
        $summary[] = ['Of the new leads, unlocked by an owner', $d['unlocked_of_these']];
        $summary[] = ['Called 3 times, nobody answered', $d['never_answered']];
        $summary[] = ['Owner reports sent', $d['reports']['sent']];
        $summary[] = ['Owner reports approved', $d['reports']['approved']];
        $summary[] = ['Credits refunded after reports', $d['reports']['refunded']];

        $daily = [array_merge(['Date', 'New leads'], array_values($groupLabels), ['Unlocked by owners', 'Credits spent'])];
        foreach ($d['daily'] as $row) {
            $daily[] = array_merge([$row['label'], $row['total']], array_map(fn ($k) => $row[$k], $groupKeys), [$row['unlocks'], $row['credits']]);
        }

        $status = [['Status', 'Leads']];
        foreach ($d['by_status'] as $s => $c) {
            $status[] = [LeadInsightsService::pretty($s), (int) $c];
        }

        $calls = [['Call result', 'Leads']];
        foreach ($d['calls'] as $s => $c) {
            $calls[] = [LeadInsightsService::pretty($s), (int) $c];
        }

        $sources = [['Source', 'Leads']];
        foreach ($d['sources'] as $s => $c) {
            $sources[] = [LeadInsightsService::pretty($s), (int) $c];
        }
        $sources[] = ['', ''];
        $sources[] = ['Lead type', 'Leads'];
        foreach ($d['types'] as $s => $c) {
            $sources[] = [LeadInsightsService::pretty($s), (int) $c];
        }

        $tele = [['Telecaller', 'Leads given', 'Connected', 'Not connected', 'Not called yet', 'Interested', 'Closed (won)', 'Not interested / lost', 'Junk']];
        foreach ($d['telecallers'] as $t) {
            $tele[] = [$t['name'], $t['assigned'], $t['connected'], $t['not_connected'], $t['not_called'], $t['interested'], $t['won'], $t['lost'], $t['junk']];
        }

        $unlockRows = [['When', 'Owner', 'Owner phone', 'Unlocked by (if a manager)', 'Lead', 'Lead phone', 'Lead type', 'Credits']];
        foreach ($d['unlock_list'] as $u) {
            $unlockRows[] = [
                Carbon::parse($u['at'])->format('d M Y, h:i A'),
                (string) $u['owner_name'],
                (string) $u['owner_phone'],
                ($u['payer_id'] && $u['payer_id'] != $u['owner_id']) ? (string) $u['payer_name'] : '',
                (string) ($u['lead_name'] ?? 'Deleted lead'),
                (string) $u['lead_phone'],
                LeadInsightsService::pretty($u['lead_type'] ?: 'direct'),
                (int) $u['credits'],
            ];
        }

        $file = (new SimpleXlsx())
            ->addSheet('Summary', $summary, [44, 30])
            ->addSheet('Day by day', $daily, array_merge([16, 11], array_fill(0, count($groupKeys), 16), [18, 14]))
            ->addSheet('Unlocks', $unlockRows, [20, 28, 16, 26, 28, 16, 12, 9])
            ->addSheet('Status', $status, [28, 10])
            ->addSheet('Calls', $calls, [28, 10])
            ->addSheet('Source and type', $sources, [28, 10])
            ->addSheet('Telecallers', $tele, [28, 12, 12, 15, 15, 12, 14, 22, 8]);

        return $file->download('pizi-lead-insights-' . $d['from']->format('Ymd') . '-' . $d['to']->format('Ymd') . '.xlsx');
    }

    public function pdf(Request $request, LeadInsightsService $service)
    {
        [$from, $to] = $this->range($request);
        $d = $service->build($from, $to, $this->connection($request), $this->telecaller($request));

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.lead-insights.pdf', ['d' => $d])
            ->setPaper('a4', 'landscape')
            ->download('pizi-lead-insights-' . $d['from']->format('Ymd') . '-' . $d['to']->format('Ymd') . '.pdf');
    }

    private function connection(Request $request): ?string
    {
        $c = (string) $request->get('connection', '');

        return isset(LeadInsightsService::CONNECTION[$c]) ? $c : null;
    }

    private function telecaller(Request $request): ?int
    {
        $id = (int) $request->get('telecaller', 0);
        // a telecaller can only narrow to their own leads, never look at another telecaller's
        if ($this->isTelecaller() && $id !== (int) auth()->id()) {
            return null;
        }

        return $id > 0 ? $id : null;
    }

    private function isTelecaller(): bool
    {
        return auth()->user()?->role === 'telecaller';
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $today = now()->startOfDay();
        $range = (string) $request->get('range', '7d');

        if ($request->filled('from') || $request->filled('to')) {
            try {
                $from = Carbon::parse($request->get('from') ?: $request->get('to'))->startOfDay();
                $to = Carbon::parse($request->get('to') ?: $request->get('from'))->startOfDay();
            } catch (\Throwable) {
                $from = $today->copy()->subDays(6);
                $to = $today->copy();
            }
            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }
            if ($from->diffInDays($to) > 365) {
                $from = $to->copy()->subDays(365);
            }

            return [$from, $to, 'custom'];
        }

        return match ($range) {
            'today' => [$today->copy(), $today->copy(), 'today'],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay(), 'yesterday'],
            '30d' => [$today->copy()->subDays(29), $today->copy(), '30d'],
            'month' => [$today->copy()->startOfMonth(), $today->copy(), 'month'],
            default => [$today->copy()->subDays(6), $today->copy(), '7d'],
        };
    }
}
