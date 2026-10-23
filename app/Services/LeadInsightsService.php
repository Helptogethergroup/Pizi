<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadReport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * The numbers behind Admin > Lead insights: what happened to the leads that came in during a date
 * range, how many owners unlocked, how many closed, and how each telecaller is doing.
 *
 * Per-day figures are by the day the LEAD CAME IN and show where those leads are now.
 * Unlock figures are by the day the owner UNLOCKED, whatever day the lead came in.
 */
class LeadInsightsService
{
    /** group key => [label, statuses in it, colour used on screen] */
    public const GROUPS = [
        'new' => ['New', ['new', 'new_lead'], '#3b82f6'],
        'contacted' => ['Contacted / follow-up', ['contacted', 'follow_up'], '#a855f7'],
        'interested' => ['Interested / visit', ['interested', 'visit_scheduled', 'visit_completed'], '#14b8a6'],
        'won' => ['Closed (won)', ['closed_won', 'deal_closed'], '#16a34a'],
        'lost' => ['Not interested / lost', ['not_interested', 'closed_lost'], '#f59e0b'],
        'junk' => ['Junk', ['junk'], '#94a3b8'],
        'other' => ['Other', [], '#cbd5e1'],
    ];

    public static function groupOf(?string $status): string
    {
        foreach (self::GROUPS as $key => [, $statuses]) {
            if (in_array($status, $statuses, true)) {
                return $key;
            }
        }

        return 'other';
    }

    public function build(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $days = max(1, $from->diffInDays($to->copy()->startOfDay()) + 1);
        $prevFrom = $from->copy()->subDays($days);
        $prevTo = $from->copy()->subSecond();

        $inRange = fn () => Lead::query()->whereBetween('created_at', [$from, $to]);

        // ── status mix of the leads that came in during the range
        $byStatus = $inRange()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();
        $groups = [];
        foreach (self::GROUPS as $key => [$label, , $colour]) {
            $groups[$key] = ['label' => $label, 'colour' => $colour, 'count' => 0];
        }
        foreach ($byStatus as $status => $count) {
            $groups[self::groupOf($status)]['count'] += (int) $count;
        }
        $total = (int) array_sum($byStatus);

        // ── per day: leads by where they are now
        $dailyRaw = $inRange()->selectRaw('DATE(created_at) as d, status, count(*) as c')->groupBy('d', 'status')->get();
        $unlocksDaily = $this->realUnlocks()->whereBetween('lead_unlocks.created_at', [$from, $to])
            ->selectRaw('DATE(lead_unlocks.created_at) as d, count(*) as c, coalesce(sum(credits_spent),0) as credits')
            ->groupBy('d')->get()->keyBy('d');

        $daily = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $row = ['date' => $day->toDateString(), 'label' => $day->format('d M, D'), 'total' => 0, 'unlocks' => 0, 'credits' => 0];
            foreach (array_keys(self::GROUPS) as $key) {
                $row[$key] = 0;
            }
            $daily[$day->toDateString()] = $row;
        }
        foreach ($dailyRaw as $r) {
            $d = (string) $r->d;
            if (!isset($daily[$d])) {
                continue;
            }
            $daily[$d][self::groupOf($r->status)] += (int) $r->c;
            $daily[$d]['total'] += (int) $r->c;
        }
        foreach ($unlocksDaily as $d => $r) {
            if (isset($daily[$d])) {
                $daily[$d]['unlocks'] = (int) $r->c;
                $daily[$d]['credits'] = (int) $r->credits;
            }
        }
        $daily = array_values($daily);

        // ── unlocks in the range
        $unlockRows = $this->realUnlocks()->whereBetween('lead_unlocks.created_at', [$from, $to]);
        $unlocks = (int) (clone $unlockRows)->count();
        $credits = (int) (clone $unlockRows)->sum('credits_spent');
        $unlockedOfThese = (int) $this->realUnlocks()
            ->whereIn('lead_unlocks.lead_id', $inRange()->select('id'))
            ->distinct()->count('lead_unlocks.lead_id');

        // ── calls
        $calls = $inRange()->selectRaw("coalesce(nullif(call_status,''), 'not_called') as s, count(*) as c")->groupBy('s')->pluck('c', 's')->all();
        $neverAnswered = (int) $inRange()->where('call_attempts', '>=', 3)
            ->where(fn ($q) => $q->whereNull('call_status')->orWhereNotIn('call_status', ['contacted', 'interested', 'converted']))->count();

        // ── where they came from, and what kind
        $sources = $inRange()->selectRaw("coalesce(nullif(source,''), 'unknown') as s, count(*) as c")->groupBy('s')->orderByDesc('c')->pluck('c', 's')->all();
        $types = $inRange()->selectRaw("coalesce(nullif(lead_type,''), 'direct') as s, count(*) as c")->groupBy('s')->orderByDesc('c')->pluck('c', 's')->all();

        // ── telecallers
        $telecallers = [];
        $tcRows = $inRange()->whereNotNull('assigned_telecaller_id')
            ->get(['assigned_telecaller_id', 'status', 'call_status', 'called_at']);
        $names = DB::table('users')->whereIn('id', $tcRows->pluck('assigned_telecaller_id')->unique())->pluck('name', 'id');
        foreach ($tcRows->groupBy('assigned_telecaller_id') as $id => $leads) {
            $g = fn ($key) => $leads->filter(fn ($l) => self::groupOf($l->status) === $key)->count();
            $telecallers[] = [
                'name' => $names[$id] ?? ('Telecaller #' . $id),
                'assigned' => $leads->count(),
                'called' => $leads->filter(fn ($l) => $l->called_at || ($l->call_status && $l->call_status !== 'not_called'))->count(),
                'interested' => $g('interested'),
                'won' => $g('won'),
                'lost' => $g('lost'),
                'junk' => $g('junk'),
            ];
        }
        usort($telecallers, fn ($a, $b) => $b['assigned'] <=> $a['assigned']);

        // ── owner reports
        $reports = LeadReport::whereBetween('created_at', [$from, $to]);
        $reportStats = [
            'sent' => (int) (clone $reports)->count(),
            'approved' => (int) (clone $reports)->where('status', 'approved')->count(),
            'pending' => (int) (clone $reports)->where('status', 'pending')->count(),
            'refunded' => (int) (clone $reports)->sum('credits_refunded'),
        ];

        $prevTotal = (int) Lead::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'total' => $total,
            'prev_total' => $prevTotal,
            'groups' => $groups,
            'by_status' => $byStatus,
            'daily' => $daily,
            'unlocks' => $unlocks,
            'credits' => $credits,
            'unlocked_of_these' => $unlockedOfThese,
            'calls' => $calls,
            'never_answered' => $neverAnswered,
            'sources' => $sources,
            'types' => $types,
            'telecallers' => $telecallers,
            'reports' => $reportStats,
        ];
    }

    /** lead_unlocks rows, leaving out internal test accounts */
    private function realUnlocks()
    {
        return DB::table('lead_unlocks')->whereNotIn('lead_unlocks.user_id', function ($q) {
            $q->select('id')->from('users')->where('is_test_account', true);
        });
    }

    public static function pretty(?string $value): string
    {
        return ucfirst(str_replace('_', ' ', (string) $value));
    }
}
