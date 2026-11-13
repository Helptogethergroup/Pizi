<?php

namespace App\Http\Controllers\Api\Renter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantPortalController;
use App\Models\RentBill;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What a person who lives in a PG (role "tenant") can see and do in the renter app: the same things
 * the website's tenant portal offers.
 *
 * THE RULE OF THIS FILE: the tenant is always found from the signed-in user, never from an id in the
 * request. Every bill, payment and complaint is looked up together with that tenant's id, so an id
 * that belongs to somebody else simply does not exist for them (404).
 */
class RenterController extends Controller
{
    public const COMPLAINT_CATEGORIES = ['plumbing', 'electrical', 'wifi', 'housekeeping', 'food', 'furniture', 'security', 'ac', 'water', 'other'];

    // ─── helpers ────────────────────────────────────────────────────────────

    /** The tenant record of the signed-in user: the active stay, else the most recent one. */
    private function tenantOf(Request $request): ?Tenant
    {
        return Tenant::where('user_id', $request->user()->id)
            ->orderByRaw("status = 'active' DESC")
            ->orderByDesc('id')
            ->first();
    }

    private function ok($data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function fail(string $message, int $status, string $code, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code] + $extra, $status);
    }

    /** Somebody with the tenant role but no tenant record yet (the owner has not finished adding them). */
    private function noTenant(): JsonResponse
    {
        return $this->ok(['has_tenant' => false, 'message' => 'Your PG owner has not added your stay yet. Please contact them.']);
    }

    private function billResource(RentBill $b): array
    {
        $enabled = (bool) config('rent.online_payment');

        return [
            'id' => $b->id,
            'bill_number' => $b->bill_number,
            'month' => $b->month,
            'month_label' => $b->month_label,
            'rent_amount' => (float) $b->rent_amount,
            'electricity' => (float) $b->electricity,
            'water' => (float) $b->water,
            'maintenance' => (float) $b->maintenance,
            'food_charges' => (float) $b->food_charges,
            'other_charges' => (float) $b->other_charges,
            'other_charges_label' => $b->other_charges_label,
            'late_fee' => (float) $b->late_fee,
            'discount' => (float) $b->discount,
            'total_amount' => (float) $b->total_amount,
            'paid_amount' => (float) $b->paid_amount,
            'due_amount' => (float) $b->due_amount,
            'due_date' => $b->due_date?->toDateString(),
            'status' => $b->status,
            'days_late' => ($b->due_amount > 0 && $b->due_date && $b->due_date->isPast()) ? (int) $b->due_date->diffInDays(now()) : 0,
            // the online payment link only exists while online payment is switched on (config/rent.php)
            'pay_url' => $enabled && $b->due_amount > 0 ? $b->pay_url : null,
        ];
    }

    private function paymentResource($p): array
    {
        return [
            'id' => $p->id,
            'receipt_number' => $p->receipt_number,
            'amount' => (float) $p->amount,
            'payment_method' => $p->payment_method,
            'transaction_ref' => $p->transaction_ref,
            'paid_at' => $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->toIso8601String() : null,
        ];
    }

    private function complaintRow($c): array
    {
        return [
            'id' => $c->id,
            'ticket_number' => $c->ticket_number,
            'category' => $c->category,
            'priority' => $c->priority,
            'title' => $c->title,
            'description' => $c->description,
            'status' => $c->status,
            'assigned_to_name' => $c->assigned_to_name,
            'resolution_notes' => $c->resolution_notes,
            'rating' => $c->rating,
            'feedback' => $c->feedback,
            'resolved_at' => $c->resolved_at,
            'created_at' => $c->created_at,
        ];
    }

    private function ownComplaint(Tenant $tenant, $id)
    {
        return DB::table('complaints')->where('id', $id)->where('tenant_id', $tenant->id)->first();
    }

    // ─── home ───────────────────────────────────────────────────────────────

    public function home(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        $tenant->load(['property.city', 'property.locality']);

        $currentBill = $tenant->bills()->where('status', '!=', 'paid')->reorder('month')->first();
        $journey = collect(app(TenantPortalController::class)->calculateJourneySteps($user, $tenant))
            ->map(fn ($s) => ['id' => $s['id'], 'title' => $s['title'], 'description' => $s['desc'], 'completed' => (bool) $s['completed'], 'unlocked' => (bool) $s['unlocked'], 'who' => $s['who']])
            ->values();
        $agreement = $tenant->activeAgreement;

        return $this->ok([
            'has_tenant' => true,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'room_number' => $tenant->room_number,
                'bed_number' => $tenant->bed_number,
                'monthly_rent' => (float) $tenant->monthly_rent,
                'move_in_date' => $tenant->move_in_date,
                'kyc_status' => $tenant->kyc_status,
                'kyc_progress' => $tenant->kyc_progress,
                'agreement_signed' => !empty($tenant->agreement_signed_at),
                'notice_given' => !empty($tenant->notice_date),
            ],
            'property' => $tenant->property ? [
                'id' => $tenant->property->id,
                'name' => $tenant->property->name,
                'city' => $tenant->property->city?->name,
                'locality' => $tenant->property->locality?->name,
            ] : null,
            'stats' => [
                'pending_rent' => (float) $tenant->bills()->where('status', '!=', 'paid')->sum('due_amount'),
                'paid_total' => (float) $tenant->payments()->sum('amount'),
                'open_complaints' => $tenant->complaints()->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                'months_stayed' => ($tenant->move_in_date && \Carbon\Carbon::parse($tenant->move_in_date)->isPast())
                    ? (int) \Carbon\Carbon::parse($tenant->move_in_date)->diffInMonths(now()) : 0,
            ],
            'current_bill' => $currentBill ? $this->billResource($currentBill) : null,
            'recent_complaints' => $tenant->complaints()->take(3)->get()->map(fn ($c) => $this->complaintRow($c))->values(),
            'agreement' => $agreement ? [
                'id' => $agreement->id,
                'agreement_number' => $agreement->agreement_number,
                'status' => $agreement->status,
                'start_date' => $agreement->start_date,
                'end_date' => $agreement->end_date,
                'signed_at' => $agreement->signed_at,
            ] : null,
            'journey' => $journey,
            'online_payment_enabled' => (bool) config('rent.online_payment'),
        ]);
    }

    // ─── notices from the owner ─────────────────────────────────────────────

    public function notices(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant || $tenant->status !== 'active') {
            return $this->noTenant();
        }

        $rows = \App\Models\PropertyNotice::live()->forTenant($tenant)
            ->orderByDesc('pinned')->orderByDesc('created_at')->limit(50)->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'kind' => $n->kind,
                'pinned' => $n->pinned,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values();

        return $this->ok(['has_tenant' => true, 'notices' => $rows]);
    }

    // ─── my room ────────────────────────────────────────────────────────────

    public function room(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }
        $tenant->load(['property.city', 'property.locality', 'owner']);
        $p = $tenant->property;

        // Roommates: only their first name, no phone or documents.
        $roommates = $tenant->room_number
            ? Tenant::where('property_id', $tenant->property_id)->where('room_number', $tenant->room_number)
                ->where('id', '!=', $tenant->id)->where('status', 'active')->get(['name', 'bed_number'])
                ->map(fn ($t) => ['name' => strtok(trim((string) $t->name), ' ') ?: 'Roommate', 'bed_number' => $t->bed_number])->values()
            : collect();

        return $this->ok([
            'has_tenant' => true,
            'room_number' => $tenant->room_number,
            'bed_number' => $tenant->bed_number,
            'monthly_rent' => (float) $tenant->monthly_rent,
            'security_deposit' => (float) $tenant->security_deposit,
            'move_in_date' => $tenant->move_in_date,
            'property' => $p ? [
                'id' => $p->id,
                'name' => $p->name,
                'address' => $p->address_line,
                'landmark' => $p->landmark,
                'pincode' => $p->pincode,
                'city' => $p->city?->name,
                'locality' => $p->locality?->name,
                'rules' => $p->rules,
                'map_link' => $p->google_map_link,
            ] : null,
            'owner' => $tenant->owner ? ['name' => $tenant->owner->name, 'phone' => $tenant->owner->phone] : null,
            'roommates' => $roommates,
        ]);
    }

    // ─── rent ───────────────────────────────────────────────────────────────

    public function rent(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }

        $bills = $tenant->bills()->paginate(20);

        return $this->ok([
            'has_tenant' => true,
            'total_due' => (float) $tenant->bills()->where('status', '!=', 'paid')->sum('due_amount'),
            'online_payment_enabled' => (bool) config('rent.online_payment'),
            'pay_note' => config('rent.online_payment') ? null : 'Please pay your PG owner directly (cash or UPI) and ask for a receipt.',
            'bills' => $bills->getCollection()->map(fn ($b) => $this->billResource($b))->values(),
            'page' => $bills->currentPage(),
            'last_page' => $bills->lastPage(),
        ]);
    }

    public function rentShow(Request $request, $id): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        $bill = $tenant ? $tenant->bills()->where('id', $id)->first() : null;
        if (!$bill) {
            return $this->fail('Bill not found.', 404, 'not_found');
        }

        return $this->ok($this->billResource($bill) + [
            'payments' => $bill->payments()->orderByDesc('paid_at')->get()->map(fn ($p) => $this->paymentResource($p))->values(),
        ]);
    }

    // ─── complaints ─────────────────────────────────────────────────────────

    public function complaints(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant) {
            return $this->noTenant();
        }

        $page = $tenant->complaints()->paginate(15);

        return $this->ok([
            'has_tenant' => true,
            'categories' => self::COMPLAINT_CATEGORIES,
            'complaints' => $page->getCollection()->map(fn ($c) => $this->complaintRow($c))->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function complaintStore(Request $request): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        if (!$tenant || $tenant->status !== 'active' || !$tenant->owner_id) {
            return $this->fail('You can raise a complaint once your PG owner has added your stay.', 409, 'no_active_stay');
        }

        $data = $request->validate([
            'category' => 'required|in:' . implode(',', self::COMPLAINT_CATEGORIES),
            'title' => 'required|string|min:3|max:200',
            'description' => 'required|string|min:5|max:2000',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $ticket = 'CMP-' . strtoupper(uniqid());
        $id = DB::table('complaints')->insertGetId([
            'ticket_number' => $ticket,
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $tenant->owner_id,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            User::find($tenant->owner_id)?->notify(new \App\Notifications\ComplaintFiled($ticket, $data['title'], $data['priority'] ?? 'medium', $tenant->name, $id));
        } catch (\Throwable $e) {
            \Log::warning('Complaint notification failed: ' . $e->getMessage());
        }

        return $this->ok($this->complaintRow($this->ownComplaint($tenant, $id)), 201);
    }

    public function complaintShow(Request $request, $id): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        $c = $tenant ? $this->ownComplaint($tenant, $id) : null;
        if (!$c) {
            return $this->fail('Complaint not found.', 404, 'not_found');
        }

        // The owner's internal notes are never shown to the tenant.
        $comments = DB::table('complaint_comments')->where('complaint_id', $c->id)->where('is_internal', 0)
            ->orderBy('created_at')->get(['id', 'author_name', 'author_role', 'comment', 'created_at'])
            ->map(fn ($m) => ['id' => $m->id, 'author_name' => $m->author_name, 'author_role' => $m->author_role, 'mine' => $m->author_role === 'tenant', 'comment' => $m->comment, 'created_at' => $m->created_at])->values();

        return $this->ok($this->complaintRow($c) + ['comments' => $comments]);
    }

    public function complaintComment(Request $request, $id): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        $c = $tenant ? $this->ownComplaint($tenant, $id) : null;
        if (!$c) {
            return $this->fail('Complaint not found.', 404, 'not_found');
        }
        if (in_array($c->status, ['closed', 'cancelled'], true)) {
            return $this->fail('This complaint is closed. Please raise a new one.', 409, 'complaint_closed');
        }

        $data = $request->validate(['comment' => 'required|string|min:1|max:1000']);
        $user = $request->user();

        DB::table('complaint_comments')->insert([
            'complaint_id' => $c->id, 'user_id' => $user->id, 'author_name' => $user->name, 'author_role' => 'tenant',
            'comment' => $data['comment'], 'is_internal' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->ok(['saved' => true], 201);
    }

    public function complaintRate(Request $request, $id): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        $c = $tenant ? $this->ownComplaint($tenant, $id) : null;
        if (!$c) {
            return $this->fail('Complaint not found.', 404, 'not_found');
        }
        if (!in_array($c->status, ['resolved', 'closed'], true)) {
            return $this->fail('You can rate a complaint once it is resolved.', 409, 'not_resolved');
        }
        if ($c->rating) {
            return $this->fail('You have already rated this complaint.', 409, 'already_rated');
        }

        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'feedback' => 'nullable|string|max:1000']);
        DB::table('complaints')->where('id', $c->id)->update(['rating' => $data['rating'], 'feedback' => $data['feedback'] ?? null, 'updated_at' => now()]);

        return $this->ok(['saved' => true]);
    }

    public function complaintCancel(Request $request, $id): JsonResponse
    {
        $tenant = $this->tenantOf($request);
        $c = $tenant ? $this->ownComplaint($tenant, $id) : null;
        if (!$c) {
            return $this->fail('Complaint not found.', 404, 'not_found');
        }
        if ($c->status !== 'open') {
            return $this->fail('Only a complaint nobody has picked up yet can be cancelled.', 409, 'cannot_cancel');
        }
        DB::table('complaints')->where('id', $c->id)->update(['status' => 'cancelled', 'updated_at' => now()]);

        return $this->ok(['status' => 'cancelled']);
    }

    // ─── profile ────────────────────────────────────────────────────────────

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $this->tenantOf($request);

        return $this->ok([
            'name' => $user->name,
            'phone' => $user->phone,
            // owner-added tenants get a placeholder email (phone@temp.pizi.in): never show it
            'email' => str_ends_with((string) $user->email, '@temp.pizi.in') ? '' : $user->email,
            'occupation' => $tenant?->occupation,
            'company_college' => $tenant?->company_college,
            'emergency_name' => $tenant?->emergency_name,
            'emergency_phone' => $tenant?->emergency_phone,
            'emergency_relation' => $tenant?->emergency_relation,
            'has_tenant' => (bool) $tenant,
        ]);
    }

    /** The mobile number is the login, so it cannot be changed here (it would need a new OTP check). */
    public function profileUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => ['nullable', 'email', 'max:150', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'occupation' => 'nullable|string|max:100',
            'company_college' => 'nullable|string|max:200',
            'emergency_name' => 'nullable|string|max:200',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:50',
        ]);

        $email = $data['email'] ?? null;
        $user->update(['name' => $data['name'], 'email' => $email ?: $user->email]);

        $tenant = $this->tenantOf($request);
        if ($tenant) {
            $tenant->update(array_merge(
                ['name' => $data['name']],
                $email ? ['email' => $email] : [],
                array_intersect_key($data, array_flip(['occupation', 'company_college', 'emergency_name', 'emergency_phone', 'emergency_relation']))
            ));
        }

        return $this->profile($request);
    }
}
