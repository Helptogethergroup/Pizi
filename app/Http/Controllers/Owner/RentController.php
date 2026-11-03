<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RentBill;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAccess();
        $managedIds = auth()->user()->getManagedPropertyIds();

        // PG picker: every PG the user manages, with how many bills each has. Picking one narrows the list,
        // the four numbers and the tab counts to that PG (an id outside the user's PGs is ignored).
        $billCounts = RentBill::whereIn('property_id', $managedIds)->selectRaw('property_id, count(*) as c')->groupBy('property_id')->pluck('c', 'property_id');
        $propertyOptions = \App\Models\Property::whereIn('id', $billCounts->keys())->orderBy('name')->get(['id', 'name'])
            ->map(fn ($p) => (object) ['id' => $p->id, 'name' => $p->name, 'count' => (int) $billCounts[$p->id]]);
        $propertyId = (int) $request->get('property_id');
        if ($propertyId && $billCounts->has($propertyId)) {
            $managedIds = [$propertyId];
        } else {
            $propertyId = 0;
        }

        $query = RentBill::whereIn('property_id', $managedIds)
            ->with(['tenant', 'property', 'payments' => function($q) {
                $q->latest('paid_at');
            }]);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->get('payment_method')) {
            $query->whereHas('payments', function($q) use ($method) {
                $q->where('payment_method', $method);
            });
        }

        if ($month = $request->get('month')) {
            $query->where('month', $month);
        }

        if ($search = $request->get('q')) {
            $query->whereHas('tenant', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $bills = $query->latest('month')->paginate(20)->withQueryString();

        $stats = [
            'total_collected_month' => RentPayment::whereIn('rent_bill_id', RentBill::whereIn('property_id', $managedIds)->pluck('id'))
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'total_pending' => RentBill::whereIn('property_id', $managedIds)
                ->where('status', '!=', 'paid')
                ->sum('due_amount'),
            'overdue_count' => RentBill::whereIn('property_id', $managedIds)
                ->where('status', 'overdue')->count(),
            'paid_count_month' => RentBill::whereIn('property_id', $managedIds)
                ->where('month', now()->format('Y-m'))
                ->where('status', 'paid')->count(),
        ];

        // Tab counts — all-time, per status (independent of month/search filters)
        // so tabs always show the true total, not the filtered count.
        $tabCounts = [
            'all' => RentBill::whereIn('property_id', $managedIds)->count(),
            'pending' => RentBill::whereIn('property_id', $managedIds)->where('status', 'pending')->count(),
            'partial' => RentBill::whereIn('property_id', $managedIds)->where('status', 'partial')->count(),
            'overdue' => RentBill::whereIn('property_id', $managedIds)->where('status', 'overdue')->count(),
            'paid' => RentBill::whereIn('property_id', $managedIds)->where('status', 'paid')->count(),
        ];

        return view('owner.rent.index', compact('bills', 'stats', 'tabCounts', 'propertyOptions', 'propertyId'));
    }

    public function create(Request $request)
    {
        $this->checkAccess();

        $tenants = Tenant::whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->where('status', 'active')
            ->with('property')
            ->orderBy('name')
            ->get();

        $selectedTenant = null;
        if ($request->tenant_id) {
            $selectedTenant = $tenants->firstWhere('id', $request->tenant_id);
        }

        return view('owner.rent.create', compact('tenants', 'selectedTenant'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|date_format:Y-m',
            'rent_amount' => 'required|numeric|min:0',
            'electricity' => 'nullable|numeric|min:0',
            'water' => 'nullable|numeric|min:0',
            'maintenance' => 'nullable|numeric|min:0',
            'food_charges' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'other_charges_label' => 'nullable|string|max:100',
            'late_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $tenant = Tenant::where('id', $data['tenant_id'])
            ->whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->firstOrFail();

        // Check duplicate — withTrashed() so soft-deleted bills also block re-creation
        $exists = RentBill::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Bill for this month already exists. Check Bill Archive if it was deleted.'])->withInput();
        }

        $data['property_id'] = $tenant->property_id;
        $data['owner_id'] = $this->effectiveOwnerId();
        $data['bill_number'] = 'PIZI-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $data['electricity'] = $data['electricity'] ?? 0;
        $data['water'] = $data['water'] ?? 0;
        $data['maintenance'] = $data['maintenance'] ?? 0;
        $data['food_charges'] = $data['food_charges'] ?? 0;
        $data['other_charges'] = $data['other_charges'] ?? 0;
        $data['late_fee'] = $data['late_fee'] ?? 0;
        $data['discount'] = $data['discount'] ?? 0;

        $total = $data['rent_amount'] + $data['electricity'] + $data['water']
               + $data['maintenance'] + $data['food_charges'] + $data['other_charges']
               + $data['late_fee'] - $data['discount'];

        $data['total_amount'] = $total;
        $data['paid_amount'] = 0;
        $data['due_amount'] = $total;
        $data['status'] = 'pending';

        $bill = RentBill::create($data);

        app(WhatsAppService::class)->sendTemplate(
            $tenant->phone,
            'bill_generated',
            // Numbered params matching rent_bill_generated_v3: {{1}} {{2}} {{3}} {{4}} {{5}}=pay link
            [
                $tenant->name,
                $bill->month_label,
                number_format($bill->total_amount, 0),
                $bill->due_date->format('d M Y'),
                $bill->pay_url,
            ]
        );

        return redirect()->route('owner.rent.show', $bill)->with('success', '✓ Bill generated.');
    }

    public function show(RentBill $bill)
    {
        $this->authorize_owner($bill);
        $bill->load('tenant', 'property', 'payments');
        return view('owner.rent.show', compact('bill'));
    }

         public function destroy(RentBill $bill)
    {
        $this->authorize_owner($bill);

        // PG Manager bill delete nahi kar sakta
        if (auth()->user()->role === 'pg_manager') {
            return back()->with('error', '⛔ PG Managers cannot delete rent bills.');
        }

        // Soft delete — bill DB me rehta hai, sirf hidden ho jaata hai
        $bill->delete();
        return redirect()->route('owner.rent.index')->with('success', '✓ Bill deleted (archived — visible in Bill Archive).');
    }

    /**
     * Bill Archive — 30+ days old bills + soft-deleted bills.
     * OWNER ONLY — PG Manager ko access nahi.
     */
    public function archive(Request $request)
    {
        if (auth()->user()->role !== 'owner' && !auth()->user()->isAdmin()) {
            abort(403, 'Only the owner can view the bill archive.');
        }

        $managedIds = auth()->user()->getManagedPropertyIds();

        // Active bills jo 30+ din purane hain (created_at ke hisaab se)
        $oldBills = RentBill::whereIn('property_id', $managedIds)
            ->where('created_at', '<=', now()->subDays(30))
            ->with('tenant', 'property')
            ->latest()
            ->paginate(20, ['*'], 'old_page');

        // Deleted bills (soft-deleted)
        $deletedBills = RentBill::onlyTrashed()
            ->whereIn('property_id', $managedIds)
            ->with('tenant', 'property')
            ->latest('deleted_at')
            ->paginate(20, ['*'], 'deleted_page');

        return view('owner.rent.archive', compact('oldBills', 'deletedBills'));
    }

    /**
     * Restore a soft-deleted bill (owner only).
     */
    public function restore($id)
    {
        if (auth()->user()->role !== 'owner' && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $bill = RentBill::onlyTrashed()->findOrFail($id);

        if (!auth()->user()->isAdmin() && !auth()->user()->getManagedPropertyIds()->contains($bill->property_id)) {
            abort(403);
        }

        $bill->restore();
        return back()->with('success', '✓ Bill restored.');
    }
    public function recordPayment(Request $request, RentBill $bill)
    {
        $this->authorize_owner($bill);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,upi,bank_transfer,razorpay,phonepe,paytm,cheque,other',
            'transaction_ref' => 'nullable|string|max:100',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $data['rent_bill_id'] = $bill->id;
        $data['tenant_id'] = $bill->tenant_id;
        $data['owner_id'] = $bill->owner_id;
        $data['received_by_id'] = auth()->id();
        $data['receipt_number'] = 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        RentPayment::create($data);

        $bill->refresh()->recalculate();

        app(WhatsAppService::class)->sendTemplate(
            $bill->tenant->phone,
            'payment_confirmed',
            [$bill->tenant->name, number_format($data['amount'], 0), $bill->month_label]
        );

        return back()->with('success', '✓ Payment recorded. Receipt: ' . $data['receipt_number']);
    }

    public function deletePayment(RentPayment $payment)
    {
        $bill = $payment->bill;
        $this->authorize_owner($bill);

        // PG Manager payment delete nahi kar sakta
        if (auth()->user()->role === 'pg_manager') {
            return back()->with('error', '⛔ PG Managers cannot delete payments.');
        }

        $payment->delete();
        $bill->refresh()->recalculate();
        return back()->with('success', '✓ Payment removed.');
    }
    
    
    public function sendReminder(RentBill $bill)
{
    $this->authorize_owner($bill);

    if ($bill->status === 'paid') {
        return response()->json(['ok' => false, 'message' => 'Bill already paid.']);
    }

    $tenant = $bill->tenant;

    $payUrl = $bill->pay_url;

    $result = app(\App\Services\WhatsAppService::class)->sendTemplate(
        $tenant->phone,
        'bill_generated',
        // Numbered params matching rent_bill_generated_v3: {{1}} {{2}} {{3}} {{4}} {{5}}=pay link
        [
            $tenant->name,
            $bill->month_label,
            number_format($bill->due_amount, 0),
            $bill->due_date->format('d M Y'),
            $payUrl,
        ]
    );

    // WhatsApp API nahi lagi — fallback: WA link return karo
    if (!$result['ok']) {
        $phone = preg_replace('/[^0-9]/', '', $tenant->phone);
        if (strlen($phone) === 10) $phone = '91' . $phone;

        $msg = urlencode(
            "Hi {$tenant->name},\n\n" .
            "Aapka {$bill->month_label} ka rent bill pending hai.\n" .
            "Amount: ₹" . number_format($bill->due_amount, 0) . "\n" .
            "Due Date: " . $bill->due_date->format('d M Y') . "\n" .
            "Bill No: {$bill->bill_number}\n\n" .
            (config('rent.online_payment') ? "Pay here: {$payUrl}\n\n" : "Please pay your PG owner directly (cash or UPI).\n\n") .
            "- Pizi Team"
        );

        return response()->json([
            'ok' => false,
            'message' => 'WhatsApp API not configured. Use manual link below.',
            'wa_link' => "https://wa.me/{$phone}?text={$msg}",
        ]);
    }

    return response()->json(['ok' => true]);
}

    public function receipt(RentPayment $payment)
    {
        $this->checkAccess();

        if (!auth()->user()->isAdmin()
            && !auth()->user()->getManagedPropertyIds()->contains($payment->bill?->property_id)) {
            abort(403);
        }

        $payment->load('bill.property', 'tenant');
        return view('owner.rent.receipt', compact('payment'));
    }

    /**
     * "Rent already collected": tick the tenants whose rent was taken in cash or UPI and put it into
     * the software in one go. Makes the month's bill when there is none. No WhatsApp goes to tenants.
     */
    public function collected(Request $request)
    {
        $this->checkAccess();

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->get('month')) ? $request->get('month') : now()->format('Y-m');
        $all = Tenant::whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->where('status', 'active')->with('property:id,name')->get();

        $properties = $all->groupBy('property_id')->map(fn ($g) => (object) [
            'id' => $g->first()->property_id,
            'name' => $g->first()->property?->name ?? 'PG',
            'count' => $g->count(),
        ])->sortBy(fn ($p) => strtolower($p->name))->values();

        $propertyId = (int) $request->get('property_id');
        if ($propertyId && ! $properties->contains('id', $propertyId)) {
            $propertyId = 0;
        }

        $tenants = ($propertyId ? $all->where('property_id', $propertyId) : $all)
            ->sort(fn ($a, $b) => strnatcasecmp(
                ($a->property?->name ?? '') . '|' . $a->room_number . '|' . $a->bed_number,
                ($b->property?->name ?? '') . '|' . $b->room_number . '|' . $b->bed_number
            ))->values();

        $bills = RentBill::withTrashed()->where('month', $month)->whereIn('tenant_id', $tenants->pluck('id'))->get()->keyBy('tenant_id');

        return view('owner.rent.collected', compact('month', 'properties', 'propertyId', 'tenants', 'bills'));
    }

    public function collectedStore(Request $request, \App\Services\RentBillingService $rent)
    {
        $this->checkAccess();

        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'paid_at' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,upi,bank_transfer,phonepe,paytm,cheque,other',
            'rows' => 'required|array',
            'rows.*.amount' => 'nullable|numeric|min:0',
            'rows.*.method' => 'nullable|in:cash,upi,bank_transfer,phonepe,paytm,cheque,other',
        ]);

        // $data['rows'] only keeps the validated keys; the tick ("on") has to be read from the request
        $picked = collect($request->input('rows'))->filter(fn ($r) => ! empty($r['on']) && (float) ($r['amount'] ?? 0) > 0);
        if ($picked->isEmpty()) {
            return back()->withInput()->with('error', 'Tick at least one tenant and enter the amount you collected.');
        }

        $tenants = Tenant::whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->where('status', 'active')->whereIn('id', $picked->keys())->get()->keyBy('id');

        $recorded = 0;
        $total = 0.0;
        $skipped = [];

        foreach ($picked as $tenantId => $row) {
            $tenant = $tenants->get((int) $tenantId);
            if (! $tenant) {
                continue;
            }
            $r = $rent->recordCollected(
                $tenant, $data['month'], (float) $row['amount'], $row['method'] ?? $data['payment_method'],
                $data['paid_at'], $this->effectiveOwnerId(), auth()->id(), $request->boolean('notify')
            );
            if ($r['status'] === 'recorded') {
                $recorded++;
                $total += $r['amount'];
            } else {
                $skipped[] = $tenant->name . ' (' . $r['message'] . ')';
            }
        }

        $msg = "✓ {$recorded} payment" . ($recorded === 1 ? '' : 's') . ' recorded, ₹' . number_format($total, 0) . ' in total.';
        if ($skipped) {
            $msg .= ' Skipped: ' . implode(', ', array_slice($skipped, 0, 5)) . (count($skipped) > 5 ? ' and ' . (count($skipped) - 5) . ' more.' : '.');
        }

        return redirect()->route('owner.rent.collected', ['month' => $data['month'], 'property_id' => $request->get('property_id') ?: null])->with('success', $msg);
    }

    public function generateAll(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'month' => 'required|date_format:Y-m',
            'due_date' => 'required|date',
        ]);

        $tenants = Tenant::whereIn('property_id', auth()->user()->getManagedPropertyIds())
            ->where('status', 'active')
            ->where('monthly_rent', '>', 0)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $exists = RentBill::withTrashed()
                ->where('tenant_id', $tenant->id)
                ->where('month', $request->month)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $bill = RentBill::create([
                'tenant_id' => $tenant->id,
                'property_id' => $tenant->property_id,
                'owner_id' => $this->effectiveOwnerId(),
                'bill_number' => 'PIZI-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'month' => $request->month,
                'rent_amount' => $tenant->monthly_rent,
                'total_amount' => $tenant->monthly_rent,
                'due_amount' => $tenant->monthly_rent,
                'paid_amount' => 0,
                'due_date' => $request->due_date,
                'status' => 'pending',
            ]);

            // NOTE: synchronous for now — fine for a few dozen tenants.
            // At 200-300+ tenants this loop will get slow; move to a
            // queued job (php artisan queue:work) once volume grows.
            app(WhatsAppService::class)->sendTemplate(
                $tenant->phone,
                'bill_generated',
                // Numbered params matching rent_bill_generated_v3: {{1}} {{2}} {{3}} {{4}} {{5}}=pay link
                [
                    $tenant->name,
                    \Carbon\Carbon::createFromFormat('Y-m', $request->month)->format('M Y'),
                    number_format($tenant->monthly_rent, 0),
                    \Carbon\Carbon::parse($request->due_date)->format('d M Y'),
                    $bill->pay_url,
                ]
            );

            $created++;
        }

        return back()->with('success', "✓ Generated {$created} bills. Skipped {$skipped} (already exist).");
    }

    /**
     * A rent bill's owner_id must always be the real property owner —
     * never the PG Manager's own user id.
     */
    private function effectiveOwnerId()
    {
        $user = auth()->user();
        return $user->role === 'pg_manager' ? $user->owner_id : $user->id;
    }

    /**
     * Blocks access entirely if this PG Manager wasn't granted the
     * "Rent Collection" feature by their owner.
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasFeature('rent')) {
            abort(403, 'You do not have access to Rent Collection.');
        }
    }

    private function authorize_owner(RentBill $bill): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($bill->property_id)) {
            abort(403);
        }
    }
}