<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Models\Visit;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get revenue (paid payments) for last N days, grouped by date.
     */
    public function revenueLastDays(int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $payments = Payment::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount_inr) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenue = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $revenue[] = (int) ($payments[$date]->total ?? 0);
            $counts[] = (int) ($payments[$date]->count ?? 0);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'counts' => $counts,
            'total' => array_sum($revenue),
        ];
    }

    /**
     * Lead funnel: count of leads at each stage.
     */
    public function leadFunnel(): array
    {
        $stages = ['new', 'contacted', 'interested', 'follow_up', 'visit_scheduled', 'visit_done', 'closed_won'];
        $counts = Lead::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();

        // Cumulative funnel — at each stage, count includes all later stages too
        $funnel = [];
        $totalLeads = Lead::count();

        $funnel[] = ['stage' => 'Total Leads', 'count' => $totalLeads];

        $running = $totalLeads;
        foreach ($stages as $i => $stage) {
            $atOrBeyondThisStage = 0;
            $remaining = array_slice($stages, $i);
            foreach ($remaining as $s) {
                $atOrBeyondThisStage += ($counts[$s] ?? 0);
            }
            $funnel[] = [
                'stage' => ucfirst(str_replace('_', ' ', $stage)),
                'count' => $atOrBeyondThisStage,
            ];
        }

        return $funnel;
    }

    /**
     * Lead sources breakdown.
     */
    public function leadSources(): array
    {
        $sources = Lead::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        return [
            'labels' => $sources->pluck('source')->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->toArray(),
            'data' => $sources->pluck('count')->map(fn ($c) => (int) $c)->toArray(),
        ];
    }

    /**
     * Top cities by lead count.
     */
    public function topCitiesByLeads(int $limit = 5): array
    {
        $rows = Lead::selectRaw('preferred_city as city, COUNT(*) as count')
            ->whereNotNull('preferred_city')
            ->groupBy('preferred_city')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('city')->toArray(),
            'data' => $rows->pluck('count')->map(fn ($c) => (int) $c)->toArray(),
        ];
    }

    /**
     * Conversion rate over last N days.
     */
    public function conversionTrend(int $days = 30): array
    {
        $labels = [];
        $rates = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');

            $totalThatDay = Lead::whereDate('created_at', $date)->count();
            $closedThatDay = Lead::whereDate('created_at', $date)
                ->where('status', 'closed_won')->count();

            $rates[] = $totalThatDay > 0 ? round(($closedThatDay / $totalThatDay) * 100, 1) : 0;
        }

        return ['labels' => $labels, 'data' => $rates];
    }

    /**
     * Top owners by spending.
     */
    public function topSpendingOwners(int $limit = 5): array
    {
        return User::where('role', 'owner')
            ->whereHas('wallet')
            ->with('wallet')
            ->get()
            ->sortByDesc(fn ($u) => $u->wallet?->lifetime_spent ?? 0)
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Top performing owners (by closed deals on their properties).
     */
    public function topPerformingOwners(int $limit = 5): array
    {
        return User::where('role', 'owner')
            ->withCount(['properties as closed_deals' => function ($q) {
                $q->whereHas('leads', fn ($l) => $l->where('status', 'closed_won'));
            }])
            ->orderByDesc('closed_deals')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Field exec performance this month.
     */
    public function fieldExecPerformance(): array
    {
        return User::where('role', 'field_executive')
            ->where('is_active', true)
            ->withCount([
                'fieldVisits as month_total' => fn ($q) => $q->whereMonth('scheduled_at', now()->month),
                'fieldVisits as month_closed' => fn ($q) => $q->whereMonth('scheduled_at', now()->month)->where('outcome', 'closed'),
            ])
            ->get()
            ->map(function ($exec) {
                $exec->conversion_rate = $exec->month_total > 0
                    ? round(($exec->month_closed / $exec->month_total) * 100)
                    : 0;
                return $exec;
            })
            ->toArray();
    }

    /**
     * Owner-specific analytics.
     */
    public function ownerAnalytics(User $owner): array
    {
        $properties = Property::where('owner_id', $owner->id)
            ->withCount(['leads', 'leads as closed_leads_count' => fn ($q) => $q->where('status', 'closed_won')])
            ->orderByDesc('leads_count')
            ->get();

        // Credit usage last 6 months
        $creditUsage = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $spent = WalletTransaction::where('user_id', $owner->id)
                ->where('type', 'debit')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            $creditUsage[] = (int) $spent;
        }

        // Lead quality breakdown
        $leadTypes = Lead::whereIn('property_id', $properties->pluck('id'))
            ->selectRaw('lead_type, COUNT(*) as count')
            ->groupBy('lead_type')
            ->pluck('count', 'lead_type')
            ->toArray();

        return [
            'properties' => $properties,
            'credit_usage' => [
                'labels' => $labels,
                'data' => $creditUsage,
            ],
            'lead_types' => [
                'labels' => array_keys($leadTypes),
                'data' => array_values($leadTypes),
            ],
            'totals' => [
                'total_leads' => array_sum($properties->pluck('leads_count')->toArray()),
                'closed_leads' => array_sum($properties->pluck('closed_leads_count')->toArray()),
                'total_spent' => $owner->wallet?->lifetime_spent ?? 0,
                'current_balance' => $owner->wallet?->balance ?? 0,
            ],
        ];
    }
}