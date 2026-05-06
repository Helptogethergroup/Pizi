<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadPricing;
use App\Models\Property;
use App\Services\LeadMatchingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use RuntimeException;

class LeadController extends Controller
{
    public function index(Request $request, LeadMatchingService $matcher)
    {
        $owner = auth()->user();

        // Ensure wallet exists
        $wallet = $owner->wallet ?? $owner->wallet()->create(['balance' => 0]);

        // Get matched leads with score, badge, affordability
        $minScore = (int) $request->get('min_score', 30);
        $allMatched = $matcher->leadsForOwner($owner, $minScore);

        // Filter by tab
        $tab = $request->get('tab', 'all');
        $filtered = match ($tab) {
            'hot' => $allMatched->filter(fn ($l) => $l->match_score >= 70),
            'unlocked' => $allMatched->filter(fn ($l) => $l->is_unlocked),
            'affordable' => $allMatched->filter(fn ($l) => $l->can_afford && !$l->is_unlocked),
            default => $allMatched,
        };

        // Manual pagination since we built a collection
        $perPage = 12;
        $page = (int) $request->get('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $pricing = LeadPricing::where('is_active', true)->pluck('credit_cost', 'lead_type');

        $stats = [
            'total' => $allMatched->count(),
            'hot' => $allMatched->filter(fn ($l) => $l->match_score >= 70)->count(),
            'unlocked' => $allMatched->filter(fn ($l) => $l->is_unlocked)->count(),
            'affordable' => $allMatched->filter(fn ($l) => $l->can_afford && !$l->is_unlocked)->count(),
        ];

        return view('owner.leads', compact('paginated', 'pricing', 'wallet', 'stats', 'tab'));
    }

    public function unlock(Lead $lead, WalletService $service)
    {
        $owner = auth()->user();

        try {
            $result = $service->unlockLead($owner, $lead);
            return back()->with('success', "✓ Lead unlocked! {$result['credits_spent']} credits used. Balance: {$result['balance_remaining']}");
        } catch (RuntimeException $e) {
            return back()->withErrors(['unlock' => $e->getMessage()]);
        }
    }
}