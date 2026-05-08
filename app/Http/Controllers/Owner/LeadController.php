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
        $allMatched = $matcher->leadsForOwner($owner, 60);

        $tab = $request->get('tab', 'all');

        // Filter by tab
        $filtered = match ($tab) {
            'hot' => $allMatched->where('match_score', '>=', 70),
            'affordable' => $allMatched->filter(fn($l) => $l->affordable && !$l->is_unlocked),
            'unlocked' => $allMatched->where('is_unlocked', true),
            default => $allMatched,
        };

        $page = (int) $request->get('page', 1);
        $perPage = 12;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $wallet = $owner->wallet ?? \App\Models\Wallet::firstOrCreate(
            ['user_id' => $owner->id], ['balance' => 0]
        );

        // Tab counts
        $counts = [
            'all' => $allMatched->count(),
            'hot' => $allMatched->where('match_score', '>=', 70)->count(),
            'affordable' => $allMatched->filter(fn($l) => $l->affordable && !$l->is_unlocked)->count(),
            'unlocked' => $allMatched->where('is_unlocked', true)->count(),
        ];

        // Pricing for top strip
        $pricing = \App\Models\LeadPricing::where('is_active', true)->get()->keyBy('lead_type');

        return view('owner.leads', compact('paginated', 'wallet', 'tab', 'counts', 'pricing'));
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