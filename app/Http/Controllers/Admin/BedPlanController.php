<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BedPlan;
use App\Models\BedSubscription;
use App\Models\BedTopupPackage;
use App\Models\BedWalletTransaction;
use App\Models\User;
use App\Services\BedBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Admin control of the Bed Plan: prices, top-up packages, who is on the plan, and every wallet. */
class BedPlanController extends Controller
{
    public function __construct(private BedBillingService $billing)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $status = $request->get('status');

        $owners = User::where('role', 'owner')
            ->leftJoin('bed_subscriptions as s', 's.user_id', '=', 'users.id')
            ->leftJoin('bed_wallets as w', 'w.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.phone', 'users.email',
                's.status as plan_status', 's.cycle_end', 's.custom_price_per_bed', 's.bed_plan_id',
                DB::raw('COALESCE(w.balance, 0) as wallet_balance'),
                DB::raw('(SELECT COUNT(*) FROM beds b WHERE b.owner_id = users.id AND b.deleted_at IS NULL) as bed_count'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.phone', 'like', "%{$search}%")->orWhere('users.email', 'like', "%{$search}%")))
            ->when($status === 'none', fn ($q) => $q->whereNull('s.id'))
            ->when(in_array($status, ['active', 'expired', 'disabled'], true), fn ($q) => $q->where('s.status', $status))
            ->orderByRaw("FIELD(COALESCE(s.status, 'zz'), 'expired', 'active', 'disabled', 'zz')")
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'active' => BedSubscription::where('status', 'active')->count(),
            'expired' => BedSubscription::where('status', 'expired')->count(),
            'disabled' => BedSubscription::where('status', 'disabled')->count(),
            'not_on_plan' => User::where('role', 'owner')->whereDoesntHave('bedSubscription')->count(),
            'wallet_total' => (float) DB::table('bed_wallets')->sum('balance'),
            'topups_30d' => (float) DB::table('bed_wallet_transactions')->where('source', 'topup')->where('created_at', '>=', now()->subDays(30))->sum('amount'),
            'billed_30d' => (float) DB::table('bed_wallet_transactions')->whereIn('source', ['plan_renewal', 'new_beds'])->where('created_at', '>=', now()->subDays(30))->sum('amount'),
        ];

        return view('admin.bed-plans.index', [
            'enabled' => $this->billing->enabled(),
            'plans' => BedPlan::orderByDesc('is_default')->orderBy('id')->get(),
            'packages' => BedTopupPackage::orderBy('display_order')->orderBy('amount_inr')->get(),
            'owners' => $owners,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function settings(Request $request)
    {
        $this->billing->setSetting('enabled', $request->boolean('enabled') ? '1' : '0');

        return back()->with('success', $request->boolean('enabled')
            ? 'Bed Plan is ON. Owners who are on the plan are charged and restricted as set.'
            : 'Bed Plan is OFF. Nobody is charged and nobody is restricted until you switch it on again.');
    }

    // ── plans (price lists) ──────────────────────────────────────────────

    public function planStore(Request $request)
    {
        $data = $this->validatePlan($request);
        DB::transaction(function () use ($data) {
            if ($data['is_default'] ?? false) {
                BedPlan::query()->update(['is_default' => false]);
            }
            BedPlan::create($data);
        });

        return back()->with('success', 'Plan added.');
    }

    public function planUpdate(Request $request, BedPlan $plan)
    {
        $data = $this->validatePlan($request);
        DB::transaction(function () use ($data, $plan) {
            if ($data['is_default'] ?? false) {
                BedPlan::where('id', '!=', $plan->id)->update(['is_default' => false]);
            }
            $plan->update($data);
        });

        return back()->with('success', 'Plan updated. The new price is used from each owner\'s next charge.');
    }

    private function validatePlan(Request $request): array
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'price_per_bed' => 'required|numeric|min:0|max:100000',
            'gst_percent' => 'required|numeric|min:0|max:40',
            'description' => 'nullable|string|max:500',
        ]);
        $v['is_default'] = $request->boolean('is_default');
        $v['is_active'] = $request->boolean('is_active', true);

        return $v;
    }

    // ── top-up packages ──────────────────────────────────────────────────

    public function packageStore(Request $request)
    {
        BedTopupPackage::create($this->validatePackage($request));

        return back()->with('success', 'Top-up package added.');
    }

    public function packageUpdate(Request $request, BedTopupPackage $package)
    {
        $package->update($this->validatePackage($request));

        return back()->with('success', 'Top-up package updated.');
    }

    private function validatePackage(Request $request): array
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'amount_inr' => 'required|integer|min:1|max:1000000',
            'bonus_inr' => 'nullable|integer|min:0|max:1000000',
            'display_order' => 'nullable|integer|min:0|max:1000',
        ]);
        $v['bonus_inr'] = (int) ($v['bonus_inr'] ?? 0);
        $v['display_order'] = (int) ($v['display_order'] ?? 0);
        $v['is_active'] = $request->boolean('is_active', true);

        return $v;
    }

    // ── owners ───────────────────────────────────────────────────────────

    public function owner(User $owner)
    {
        abort_unless($owner->role === 'owner', 404);

        return view('admin.bed-plans.owner', [
            'owner' => $owner,
            'summary' => $this->billing->summary($owner->id),
            'plans' => BedPlan::where('is_active', true)->orderByDesc('is_default')->get(),
            'transactions' => BedWalletTransaction::where('user_id', $owner->id)->latest('id')->paginate(30),
            'enabled' => $this->billing->enabled(),
        ]);
    }

    public function activate(Request $request, User $owner)
    {
        abort_unless($owner->role === 'owner', 404);
        $data = $request->validate(['bed_plan_id' => 'nullable|exists:bed_plans,id', 'custom_price_per_bed' => 'nullable|numeric|min:0|max:100000']);
        if (!$this->billing->defaultPlan() && empty($data['bed_plan_id']) && ($data['custom_price_per_bed'] ?? '') === '') {
            return back()->with('error', 'Make a plan (a price per bed) first.');
        }

        $sub = $this->billing->activate(
            $owner->id,
            isset($data['bed_plan_id']) ? (int) $data['bed_plan_id'] : null,
            ($data['custom_price_per_bed'] ?? '') === '' ? null : (float) $data['custom_price_per_bed'],
            auth()->user()
        );

        return back()->with($sub->status === 'active' ? 'success' : 'error', $sub->status === 'active'
            ? "{$owner->name} is on the Bed Plan; this month is paid from the Bed Wallet."
            : "{$owner->name} is on the Bed Plan but the Bed Wallet cannot pay this month, so they are restricted until they recharge.");
    }

    public function disable(User $owner)
    {
        abort_unless($owner->role === 'owner', 404);
        $this->billing->disable($owner->id);

        return back()->with('success', "{$owner->name} is off the Bed Plan: no charge and no restriction.");
    }

    /** Change the price for this owner (empty = use the plan's price again). Used from the next charge. */
    public function price(Request $request, User $owner)
    {
        abort_unless($owner->role === 'owner', 404);
        $data = $request->validate(['custom_price_per_bed' => 'nullable|numeric|min:0|max:100000', 'bed_plan_id' => 'nullable|exists:bed_plans,id']);
        $sub = $this->billing->subscriptionFor($owner->id);
        if (!$sub) {
            return back()->with('error', 'Switch the plan on for this owner first.');
        }
        $sub->custom_price_per_bed = ($data['custom_price_per_bed'] ?? '') === '' ? null : $data['custom_price_per_bed'];
        if (!empty($data['bed_plan_id'])) {
            $sub->bed_plan_id = $data['bed_plan_id'];
        }
        $sub->save();

        return back()->with('success', 'Price saved. It is used from this owner\'s next charge.');
    }

    /** Put money into, or take money out of, one owner's Bed Wallet by hand. */
    public function adjust(Request $request, User $owner)
    {
        abort_unless($owner->role === 'owner', 404);
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01|max:1000000',
            'notes' => 'required|string|max:300',
        ]);

        try {
            if ($data['type'] === 'credit') {
                $this->billing->credit($owner->id, (float) $data['amount'], 'admin_credit', null, $data['notes'], auth()->user());
            } else {
                $this->billing->adminDebit($owner->id, (float) $data['amount'], $data['notes'], auth()->user());
            }
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bed Wallet updated.');
    }

    /** Switch the plan on for every owner who is not on it yet (each one is charged for the beds they have). */
    public function activateAll(Request $request)
    {
        $request->validate(['confirm' => 'required|in:ACTIVATE ALL']);
        if (!$this->billing->defaultPlan()) {
            return back()->with('error', 'Make a plan (a price per bed) first.');
        }

        $count = 0;
        User::where('role', 'owner')->whereDoesntHave('bedSubscription')->orderBy('id')->chunkById(100, function ($owners) use (&$count) {
            foreach ($owners as $owner) {
                $this->billing->activate($owner->id, null, null, auth()->user());
                $count++;
            }
        });

        return back()->with('success', "Bed Plan switched on for {$count} owner(s).");
    }
}
