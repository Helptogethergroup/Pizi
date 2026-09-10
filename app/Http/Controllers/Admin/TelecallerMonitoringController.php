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
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? \Carbon\Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();

        $telecallers = User::where('role', 'telecaller')->orderBy('name')->get();

        $rows = $telecallers->map(function (User $tc) use ($date) {
            $base = Lead::where('assigned_telecaller_id', $tc->id);

            $attendedToday = (clone $base)->whereDate('called_at', $date)->count();
            $rejectedToday = (clone $base)->whereDate('called_at', $date)->where('call_status', 'not_interested')->count();
            $convertedToday = (clone $base)->whereDate('updated_at', $date)->where('status', 'closed_won')->count();
            $pending = (clone $base)->whereNull('call_status')->count();
            $assignedTotal = (clone $base)->count();

            $target = $tc->daily_call_target ?: config('telecaller.default_daily_call_target', 20);

            // Owner-outreach numbers (separate pipeline from tenant leads —
            // this is B2B calling to onboard PG owners onto Pizi).
            $opBase = OwnerProspect::where('telecaller_id', $tc->id);
            $ownerCalledToday = (clone $opBase)->whereDate('called_at', $date)->count();
            $ownerRejectedToday = (clone $opBase)->whereDate('called_at', $date)->where('call_status', 'not_interested')->count();
            $ownerPending = (clone $opBase)->whereNull('call_status')->count();
            $ownerRegisteredToday = (clone $opBase)->whereDate('registered_at', $date)->count();
            $ownerListedToday = (clone $opBase)->whereDate('property_listed_at', $date)->count();
            $ownerPaidToday = (clone $opBase)->whereDate('paid_plan_at', $date)->count();

            return [
                'telecaller' => $tc,
                'assigned_total' => $assignedTotal,
                'pending' => $pending,
                'attended_today' => $attendedToday,
                'rejected_today' => $rejectedToday,
                'converted_today' => $convertedToday,
                'target' => $target,
                'progress_pct' => $target > 0 ? min(100, round($attendedToday / $target * 100)) : 0,
                'owner_called_today' => $ownerCalledToday,
                'owner_rejected_today' => $ownerRejectedToday,
                'owner_pending' => $ownerPending,
                'owner_registered_today' => $ownerRegisteredToday,
                'owner_listed_today' => $ownerListedToday,
                'owner_paid_today' => $ownerPaidToday,
            ];
        });

        $recentReassignments = LeadAssignmentLog::with(['lead', 'fromUser', 'toUser', 'changedBy'])
            ->latest('created_at')
            ->take(20)
            ->get();

        // Live feed — every status/call_status change a telecaller made,
        // most recent first, regardless of date filter above.
        $recentStatusUpdates = LeadStatusLog::with(['lead', 'changedBy'])
            ->latest('created_at')
            ->take(30)
            ->get();

        $rejectionBreakdown = Lead::whereDate('called_at', $date)
            ->where('call_status', 'not_interested')
            ->whereNotNull('rejection_reason')
            ->selectRaw('rejection_reason, count(*) as total')
            ->groupBy('rejection_reason')
            ->pluck('total', 'rejection_reason');

        return view('admin.telecallers.index', [
            'rows' => $rows,
            'date' => $date,
            'recentReassignments' => $recentReassignments,
            'recentStatusUpdates' => $recentStatusUpdates,
            'rejectionBreakdown' => $rejectionBreakdown,
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
