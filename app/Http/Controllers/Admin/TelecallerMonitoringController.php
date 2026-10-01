<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadAssignmentLog;
use App\Models\LeadStatusLog;
use App\Models\OwnerProspect;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Read-only view for admin — per-telecaller daily activity, without
 * giving the telecaller themselves any admin-panel access. Every number
 * here comes straight from a telecaller's normal day-to-day use of their
 * own dashboard (marking calls, updating lead status) — there's no
 * separate "submit your daily numbers" step for them to remember.
 */
class TelecallerMonitoringController extends Controller
{
    /**
     * Resolves the date-range from the request — defaults to "just today"
     * so existing bookmarks/behaviour don't change, but accepts a
     * date_from/date_to pair for a real multi-day performance report.
     */
    private function resolveRange(Request $request): array
    {
        $to = $request->filled('date_to')
            ? \Carbon\Carbon::parse($request->date_to)->endOfDay()
            : ($request->filled('date') ? \Carbon\Carbon::parse($request->date)->endOfDay() : now()->endOfDay());

        $from = $request->filled('date_from')
            ? \Carbon\Carbon::parse($request->date_from)->startOfDay()
            : ($request->filled('date') ? \Carbon\Carbon::parse($request->date)->startOfDay() : now()->startOfDay());

        return [$from, $to];
    }

    private function buildRows($telecallers, $from, $to)
    {
        return $telecallers->map(function (User $tc) use ($from, $to) {
            $base = Lead::where('assigned_telecaller_id', $tc->id);

            $attended = (clone $base)->whereBetween('called_at', [$from, $to])->count();
            $rejected = (clone $base)->whereBetween('called_at', [$from, $to])->where('call_status', 'not_interested')->count();
            $converted = (clone $base)->whereBetween('updated_at', [$from, $to])->where('status', 'closed_won')->count();
            $verified = (clone $base)->whereBetween('updated_at', [$from, $to])->where('lead_type', 'verified')->count();
            $pending = (clone $base)->whereNull('call_status')->count();
            $assignedTotal = (clone $base)->count();
            $assignedInRange = (clone $base)->whereBetween('created_at', [$from, $to])->count();

            $days = max(1, (int) round($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) + 1);
            $target = $tc->daily_call_target ?: config('telecaller.default_daily_call_target', 20);
            $targetForRange = $target * $days;

            // Owner-outreach numbers (separate pipeline from tenant leads —
            // this is B2B calling to onboard PG owners onto Pizi).
            $opBase = OwnerProspect::where('telecaller_id', $tc->id);
            $ownerCalled = (clone $opBase)->whereBetween('called_at', [$from, $to])->count();
            $ownerRejected = (clone $opBase)->whereBetween('called_at', [$from, $to])->where('call_status', 'not_interested')->count();
            $ownerPending = (clone $opBase)->whereNull('call_status')->count();
            $ownerRegistered = (clone $opBase)->whereBetween('registered_at', [$from, $to])->count();
            $ownerListed = (clone $opBase)->whereBetween('property_listed_at', [$from, $to])->count();
            $ownerPaid = (clone $opBase)->whereBetween('paid_plan_at', [$from, $to])->count();

            return [
                'telecaller' => $tc,
                'assigned_total' => $assignedTotal,
                'assigned_in_range' => $assignedInRange,
                'pending' => $pending,
                'attended_today' => $attended,
                'rejected_today' => $rejected,
                'converted_today' => $converted,
                'verified_in_range' => $verified,
                'target' => $target,
                'target_for_range' => $targetForRange,
                'progress_pct' => $targetForRange > 0 ? min(100, round($attended / $targetForRange * 100)) : 0,
                'owner_called_today' => $ownerCalled,
                'owner_rejected_today' => $ownerRejected,
                'owner_pending' => $ownerPending,
                'owner_registered_today' => $ownerRegistered,
                'owner_listed_today' => $ownerListed,
                'owner_paid_today' => $ownerPaid,
            ];
        });
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        $telecallers = User::where('role', 'telecaller')->orderBy('name')->get();
        $rows = $this->buildRows($telecallers, $from, $to);

        // Both activity feeds now respect the same date range as the
        // metrics above — this was the main bug: they previously always
        // showed "most recent N regardless of date filter".
        $recentReassignments = LeadAssignmentLog::with(['lead', 'fromUser', 'toUser', 'changedBy'])
            ->whereBetween('created_at', [$from, $to])
            ->latest('created_at')
            ->take(50)
            ->get();

        $recentStatusUpdates = LeadStatusLog::with(['lead', 'changedBy'])
            ->whereBetween('created_at', [$from, $to])
            ->latest('created_at')
            ->take(50)
            ->get();

        $rejectionBreakdown = Lead::whereBetween('called_at', [$from, $to])
            ->where('call_status', 'not_interested')
            ->whereNotNull('rejection_reason')
            ->selectRaw('rejection_reason, count(*) as total')
            ->groupBy('rejection_reason')
            ->pluck('total', 'rejection_reason');

        return view('admin.telecallers.index', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'recentReassignments' => $recentReassignments,
            'recentStatusUpdates' => $recentStatusUpdates,
            'rejectionBreakdown' => $rejectionBreakdown,
        ]);
    }

    /**
     * CSV download of the same per-telecaller numbers shown on screen, for
     * the currently selected date range — so "download" always matches
     * what admin is looking at.
     */
    public function export(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        $telecallers = User::where('role', 'telecaller')->orderBy('name')->get();
        $rows = $this->buildRows($telecallers, $from, $to);

        $filename = 'pizi-telecaller-report-' . $from->format('Y-m-d') . '_to_' . $to->format('Y-m-d') . '.csv';

        $callback = function () use ($rows, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Range', $from->format('d M Y') . ' to ' . $to->format('d M Y')]);
            fputcsv($out, []);
            fputcsv($out, [
                'Telecaller', 'Email', 'Phone', 'Specialization',
                'Total Assigned (lifetime)', 'Assigned In Range', 'Pending (never called)',
                'Calls Made In Range', 'Rejected In Range', 'Verified In Range', 'Converted In Range',
                'Daily Target', 'Target For Range', 'Target Achieved %',
                'Owner Calls Made', 'Owner Rejected', 'Owner Registered', 'Owner Listed', 'Owner Paid Plan',
            ]);
            foreach ($rows as $row) {
                $tc = $row['telecaller'];
                fputcsv($out, [
                    $tc->name,
                    $tc->email,
                    $tc->phone,
                    $tc->lead_specialization,
                    $row['assigned_total'],
                    $row['assigned_in_range'],
                    $row['pending'],
                    $row['attended_today'],
                    $row['rejected_today'],
                    $row['verified_in_range'],
                    $row['converted_today'],
                    $row['target'],
                    $row['target_for_range'],
                    $row['progress_pct'] . '%',
                    $row['owner_called_today'],
                    $row['owner_rejected_today'],
                    $row['owner_registered_today'],
                    $row['owner_listed_today'],
                    $row['owner_paid_today'],
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function updateTarget(Request $request, User $telecaller)
    {
        if ($telecaller->role !== 'telecaller') {
            abort(404);
        }

        $data = $request->validate([
            'daily_call_target' => 'nullable|integer|min:1|max:500',
        ]);

        $telecaller->update(['daily_call_target' => $data['daily_call_target'] ?: null]);

        return back()->with('success', "✓ Daily call target updated for {$telecaller->name}.");
    }
}
