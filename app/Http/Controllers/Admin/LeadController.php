<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    /**
     * Shared filter logic — applied to both the paginated list and the
     * unpaginated export, so "download" always matches what's on screen.
     */
    private function applyFilters($q, Request $request)
    {
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('lead_type')) {
            $q->where('lead_type', $request->lead_type);
        }
        if ($request->filled('inquiry_type')) {
            $q->where('inquiry_type', $request->inquiry_type);
        }
        if ($request->filled('source')) {
            $q->where('source', $request->source);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                   ->orWhere('phone', 'like', $term)
                   ->orWhere('email', 'like', $term);
            });
        }
        // Duplicate-phone filter — only leads whose phone appears 2+ times.
        if ($request->boolean('duplicates_only')) {
            $dupPhones = Lead::select('phone')->whereNotNull('phone')
                ->groupBy('phone')->havingRaw('COUNT(*) > 1')->pluck('phone');
            $q->whereIn('phone', $dupPhones);
        }

        return $q;
    }

    public function index(Request $request)
    {
        // Counts for the Tenant/Owner tabs — computed before any filter.
        $tenantCount = Lead::where('inquiry_type', 'tenant')->count();
        $ownerCount = Lead::where('inquiry_type', 'owner')->count();

        // Source breakdown — respects the current filters (minus pagination)
        // so it stays relevant to what's actually on screen.
        $sourceCounts = $this->applyFilters(Lead::query(), $request)
            ->select('source', DB::raw('count(*) as cnt'))
            ->groupBy('source')->pluck('cnt', 'source');

        // Phones that appear on 2+ leads — flagged on each row so a
        // telecaller doesn't call the same person twice under different
        // lead entries.
        $dupPhones = Lead::select('phone')->whereNotNull('phone')
            ->groupBy('phone')->havingRaw('COUNT(*) > 1')->pluck('phone')->flip();

        $q = Lead::with(['property.locality', 'property.city', 'telecaller', 'lockedBy', 'unlocks']);
        $this->applyFilters($q, $request);

        // Column sort — whitelisted so ?sort= can't be used to inject
        // an arbitrary column/order into the query.
        $sortable = ['created_at', 'status', 'name'];
        $sort = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'created_at';
        $dir = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $q->orderBy($sort, $dir);

        $perPage = in_array((int) $request->get('per_page'), [25, 50, 100]) ? (int) $request->get('per_page') : 25;

        $leads = $q->paginate($perPage)->withQueryString();
        $leads->getCollection()->each(fn ($lead) => $lead->is_duplicate_phone = $lead->phone && $dupPhones->has($lead->phone));

        $telecallers = User::where('role', 'telecaller')->where('is_active', true)->get();
        return view('admin.leads', compact('leads', 'telecallers', 'tenantCount', 'ownerCount', 'sourceCounts', 'sort', 'dir', 'perPage'));
    }

    /**
     * CSV download of every lead matching the current filters (not just
     * the current page) — id/created_at intentionally left out per the
     * usual request for a clean, shareable list.
     */
    public function export(Request $request)
    {
        $q = Lead::with(['property.city']);
        $this->applyFilters($q, $request);
        $leads = $q->latest()->get();

        $filename = 'pizi-leads-' . now()->format('Y-m-d_His') . '.csv';

        $callback = function () use ($leads) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Phone', 'Email', 'Source', 'Inquiry Type', 'Status', 'City', 'Property']);
            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->source,
                    $lead->inquiry_type,
                    $lead->status,
                    $lead->property?->city?->name ?? $lead->preferred_city,
                    $lead->property?->name,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate([
            'telecaller_id' => 'required|exists:users,id',
        ]);

        // Owner ne claim kar li — assign hi nahi hone denge
        if ($lead->is_locked && $lead->locked_by_user_id) {
            $ownerUser = User::find($lead->locked_by_user_id);
            if ($ownerUser && $ownerUser->role === 'owner') {
                return back()->with('error', '🔒 This lead has been claimed by PG owner: ' . $ownerUser->name . '. Cannot assign to telecaller.');
            }
        }

        // Already assign ho chuki hai — dubara assign nahi hone denge
        if ($lead->assigned_telecaller_id) {
            $existingTc = User::find($lead->assigned_telecaller_id);
            return back()->with('error', '⚠️ Lead already assigned to ' . ($existingTc->name ?? 'a telecaller') . '.');
        }

        $lead->update(['assigned_telecaller_id' => $request->telecaller_id]);
        return back()->with('success', 'Lead assigned.');
    }

    public function markVerified(Lead $lead)
    {
        // A verified lead needs a city so it actually reaches the right
        // owners — without it, it falls into the "city unknown" bucket
        // shown to every owner instead of the ones who can act on it.
        if (!$lead->property_id && !$lead->preferred_city) {
            return back()->withErrors(['verify' => 'Please fill in the "Preferred City" field before verifying this lead.']);
        }

        $lead->update(['lead_type' => 'verified']);
        return back()->with('success', '✓ Lead marked as Verified.');
    }

    /**
     * Reversible "not a real lead" flag — unlike destroy(), this keeps the
     * row (and its history/remarks) around, just hides it from the normal
     * owner-facing matching pipeline via the existing 'junk' status.
     */
    public function markJunk(Lead $lead)
    {
        $lead->update(['status' => 'junk']);
        return back()->with('success', '🚩 Lead marked as Junk.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return back()->with('success', '🗑️ Lead deleted successfully.');
    }

    private function selectedLeadIds(Request $request): array
    {
        return $request->validate(['lead_ids' => 'required|array|min:1', 'lead_ids.*' => 'integer'])['lead_ids'];
    }

    public function bulkAssign(Request $request)
    {
        $ids = $this->selectedLeadIds($request);
        $request->validate(['telecaller_id' => 'required|exists:users,id']);

        // Same claim/already-assigned guardrails as the single assign() —
        // just skips those rows instead of failing the whole batch.
        $updated = Lead::whereIn('id', $ids)
            ->whereNull('assigned_telecaller_id')
            ->where(function ($q) {
                $q->where('is_locked', false)->orWhereNull('locked_by_user_id');
            })
            ->update(['assigned_telecaller_id' => $request->telecaller_id]);

        return back()->with('success', "✅ Assigned {$updated} lead(s) (claimed/already-assigned ones were skipped).");
    }

    public function bulkVerify(Request $request)
    {
        $ids = $this->selectedLeadIds($request);

        $updated = Lead::whereIn('id', $ids)
            ->where(function ($q) {
                $q->whereNotNull('property_id')->orWhereNotNull('preferred_city');
            })
            ->update(['lead_type' => 'verified']);

        return back()->with('success', "✅ Verified {$updated} lead(s) (ones without a city were skipped).");
    }

    public function bulkJunk(Request $request)
    {
        $ids = $this->selectedLeadIds($request);
        Lead::whereIn('id', $ids)->update(['status' => 'junk']);
        return back()->with('success', '🚩 Marked ' . count($ids) . ' lead(s) as Junk.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $this->selectedLeadIds($request);
        Lead::whereIn('id', $ids)->delete();
        return back()->with('success', '🗑️ Deleted ' . count($ids) . ' lead(s).');
    }
}
