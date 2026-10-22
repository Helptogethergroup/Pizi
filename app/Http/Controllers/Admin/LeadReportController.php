<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadReport;
use App\Services\LeadReportService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Leads that owners say were not real. The team calls the number if needed, then approves
 * (credits go back) or rejects (with a note the owner sees).
 */
class LeadReportController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('status', 'pending');

        $reports = LeadReport::with(['lead', 'owner', 'reporter'])
            ->when(in_array($filter, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $filter))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'pending' => LeadReport::where('status', 'pending')->count(),
            'approved' => LeadReport::where('status', 'approved')->count(),
            'rejected' => LeadReport::where('status', 'rejected')->count(),
        ];

        return view('admin.lead-reports.index', compact('reports', 'filter', 'counts'));
    }

    public function approve(Request $request, LeadReport $report, LeadReportService $service)
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:500']);

        try {
            $service->approve($report, $request->user(), $data['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', '✓ Approved. ' . $report->fresh()->credits_refunded . ' credits returned to the owner.');
    }

    public function reject(Request $request, LeadReport $report, LeadReportService $service)
    {
        $data = $request->validate(['admin_note' => 'required|string|min:3|max:500']);

        try {
            $service->reject($report, $request->user(), $data['admin_note']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rejected. The owner has been told.');
    }
}
