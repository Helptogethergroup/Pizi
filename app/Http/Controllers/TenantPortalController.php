<?php

namespace App\Http\Controllers;
use App\Services\SetuKycService;
use App\Services\SetuEsignService;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TenantPortalController extends Controller
{
    
      public function __construct(protected ?\App\Services\SetuEsignService $setu = null)
    {
    }
    /**
     * Tenant Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)
            ->with(['property.city', 'property.locality'])
            ->first();

        if (!$tenant) {
            return view('tenant.no-tenant');
        }

        // Stats
        $pendingRent = $tenant->bills()
            ->where('status', '!=', 'paid')
            ->sum('due_amount') ?? 0;

        $paidTotal = $tenant->payments()->sum('amount') ?? 0;

        $openComplaints = $tenant->complaints()
            ->whereIn('status', ['new', 'in_progress', 'pending', 'open'])
            ->count();

        $monthsStayed = ($tenant->move_in_date && \Carbon\Carbon::parse($tenant->move_in_date)->isPast())
            ? (int) \Carbon\Carbon::parse($tenant->move_in_date)->diffInMonths(now())
            : 0;

        $stats = [
            'pending_rent' => $pendingRent,
            'paid_total' => $paidTotal,
            'open_complaints' => $openComplaints,
            'months_stayed' => $monthsStayed,
        ];

        // Current bill (this month or overdue)
        $currentBill = $tenant->bills()
            ->where('status', '!=', 'paid')
            ->orderBy('month')
            ->first();

        // Recent complaints
        $recentComplaints = $tenant->complaints()
            ->take(5)
            ->get();

        // Active agreement
        $agreement = $tenant->activeAgreement;

        return view('tenant.dashboard', compact(
            'tenant', 'stats', 'currentBill', 'recentComplaints', 'agreement'
        ));
    }

    /**
     * Profile
     */
    public function profile()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        // Owner-added tenants get an auto-generated placeholder email
        // (phone@temp.pizi.in) internally — never show this fake value
        // to the tenant. Show blank so they can add their real email.
        $displayEmail = str_ends_with((string) $user->email, '@temp.pizi.in')
            ? ''
            : $user->email;

        return view('tenant.profile', compact('user', 'tenant', 'displayEmail'));
    }

    public function profileUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'nullable|email',
            'occupation' => 'nullable|string|max:100',
            'company_college' => 'nullable|string|max:200',
            'emergency_name' => 'nullable|string|max:200',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email ?? $user->email,
        ]);

        $tenant = Tenant::where('user_id', $user->id)->first();
        if ($tenant) {
            $tenant->update($request->only([
                'name', 'phone', 'email',
                'occupation', 'company_college',
                'emergency_name', 'emergency_phone', 'emergency_relation',
            ]));
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Set up email + password login — so tenant doesn't have to
     * request an OTP every single time. One-time setup, optional.
     */
    public function setupPasswordLogin(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        $tenant = Tenant::where('user_id', $user->id)->first();
        if ($tenant) {
            $tenant->update(['email' => $request->email]);
        }

        return back()->with('success', '✓ Password login set up! You can now log in with your email and password anytime, or keep using OTP — whichever is easier.');
    }

    /**
     * My Room
     */
    public function myRoom()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)
            ->with(['property.city', 'property.locality'])
            ->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        // Roommates (same property + same room_number)
        $roommates = collect();
        if ($tenant->room_number) {
            $roommates = Tenant::where('property_id', $tenant->property_id)
                ->where('room_number', $tenant->room_number)
                ->where('id', '!=', $tenant->id)
                ->where('status', 'active')
                ->get();
        }

        return view('tenant.room', compact('tenant', 'roommates'));
    }

    /**
     * Rent History
     */
    public function rentHistory()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        $bills = $tenant->bills()->paginate(20);

        return view('tenant.rent', compact('tenant', 'bills'));
    }

    /**
     * Pay Rent
     */
    public function payRent($billId)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $bill = $tenant->bills()->where('id', $billId)->first();

        if (!$bill) {
            return redirect()->route('tenant.rent.history')->with('error', 'Bill not found');
        }

        return view('tenant.pay-rent', compact('bill', 'tenant'));
    }


     /**
 * Create Razorpay order for rent payment
 */
public function createRazorpayOrder(Request $request, $billId)
{
    $user = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->first();
    $bill = $tenant->bills()->where('id', $billId)->firstOrFail();

    if ($bill->due_amount <= 0) {
        return response()->json(['error' => 'Bill already paid'], 400);
    }

    $razorpay = new \Razorpay\Api\Api(
        config('services.razorpay.key_id',   env('RAZORPAY_KEY_ID')),
        config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET'))
    );

    $order = $razorpay->order->create([
        'amount'   => (int) ($bill->due_amount * 100),
        'currency' => 'INR',
        'receipt'  => $bill->bill_number,
        'notes'    => [
            'bill_id'   => $bill->id,
            'tenant_id' => $tenant->id,
            'bill_no'   => $bill->bill_number,
        ],
    ]);

    return response()->json([
        'order_id' => $order['id'],
        'amount'   => $order['amount'],
        'currency' => $order['currency'],
        'bill_no'  => $bill->bill_number,
    ]);
}

/**
 * Verify Razorpay payment and record rent payment
 */
public function verifyRentPayment(Request $request, $billId)
{
    $user = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->first();
    $bill = $tenant->bills()->where('id', $billId)->firstOrFail();

    try {
        $razorpay = new \Razorpay\Api\Api(
            env('RAZORPAY_KEY_ID'),
            env('RAZORPAY_KEY_SECRET')
        );

        $razorpay->utility->verifyPaymentSignature([
            'razorpay_order_id'   => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature'  => $request->razorpay_signature,
        ]);

        $payment = \App\Models\RentPayment::create([
            'rent_bill_id'    => $bill->id,
            'tenant_id'       => $bill->tenant_id,
            'owner_id'        => $bill->owner_id,
            'amount'          => $bill->due_amount,
            'payment_method'  => 'razorpay',
            'transaction_ref' => $request->razorpay_payment_id,
            'receipt_number'  => 'RCP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5)),
            'paid_at'         => now(),
            'notes'           => 'Paid online via Razorpay',
        ]);

        $bill->refresh()->recalculate();

        try {
            $bill->owner?->notify(new \App\Notifications\PaymentReceived($payment));
        } catch (\Exception $e) {
            \Log::warning('Payment notification failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        \Log::error('Rent Razorpay verify failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}



    /**
     * Complaints List
     */
    public function complaints()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        $complaints = $tenant->complaints()->paginate(15);

        return view('tenant.complaints.index', compact('complaints', 'tenant'));
    }

    public function complaintCreate()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.complaints.create', compact('tenant'));
    }

    public function complaintStore(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        $request->validate([
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:200',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $ticketNumber = 'CMP-' . strtoupper(uniqid());

        $complaintId = DB::table('complaints')->insertGetId([
            'ticket_number' => $ticketNumber,
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $tenant->owner_id,
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $owner = \App\Models\User::find($tenant->owner_id);
            $owner?->notify(new \App\Notifications\ComplaintFiled(
                $ticketNumber,
                $request->title,
                $request->priority ?? 'medium',
                $tenant->name,
                $complaintId
            ));
        } catch (\Exception $e) {
            \Log::warning('Complaint notification failed: ' . $e->getMessage());
        }

        return redirect()->route('tenant.complaints.index')
            ->with('success', 'Complaint registered! The owner has been notified.');
    }
    
    
    
    
    public function agreementPreview($id)
{
    $user = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
    $agreement = \App\Models\RentAgreement::where('id', $id)
        ->where('tenant_id', $tenant->id)
        ->with('tenant', 'property', 'owner', 'signatures')
        ->firstOrFail();

    return view('owner.agreements.preview', compact('agreement'));
}

public function agreementDownload($id)
{
    $user = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
    $agreement = \App\Models\RentAgreement::where('id', $id)
        ->where('tenant_id', $tenant->id)
        ->with('tenant', 'property', 'owner', 'signatures')
        ->firstOrFail();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('owner.agreements.preview', compact('agreement'))
        ->setPaper('a4', 'portrait');

    return $pdf->download($agreement->agreement_number . '.pdf');
}

    /**
     * Agreement
     */
    public function agreement()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        $agreements = $tenant->agreements()->get();

        return view('tenant.agreement', compact('agreements', 'tenant'));
    }

    /**
     * Notice Period
     */
    public function noticeForm()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.notice', compact('tenant'));
    }

    public function noticeSubmit(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        $request->validate([
            'notice_date' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant->update([
            'notice_date' => $request->notice_date,
            'notice_reason' => $request->reason,
            'status' => 'notice_period',
        ]);

        try {
            $tenant->owner?->notify(new \App\Notifications\NoticeSubmitted($tenant->fresh()));
        } catch (\Exception $e) {
            \Log::warning('Notice notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Notice submitted! The owner will contact you.');
    }

    /**
     * ONBOARDING PAGE - Step-by-step verification
     */
    public function onboarding()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->with('property')->first();

        if (!$tenant) {
            $tenant = Tenant::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => 'active',
                'kyc_status' => 'pending',
                'monthly_rent' => 0,
                'security_deposit' => 0,
            ]);
        }

        // Owner-added walk-in tenants never go through the self-serve
        // steps (Aadhaar/agreement/token) — those fields will stay empty
        // forever for them. Recalculating from those fields would wrongly
        // downgrade their journey_stage back down every time this page
        // loads. So: skip recalculation entirely, keep them at stage 5,
        // and send them straight to the dashboard.
        if ($tenant->onboarding_source === 'owner_added') {
            if ((int) $user->journey_stage < 5) {
                DB::table('users')->where('id', $user->id)->update([
                    'journey_stage' => 5,
                    'journey_completed_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return redirect()->route('tenant.dashboard');
        }

        // SAFETY: is user ke unlinked leads ko phone/email se link karo
        $this->linkLeadsToUser($user);

        $steps = $this->calculateJourneySteps($user, $tenant);
        $newStage = collect($steps)->where('completed', true)->count();

        // DB stage ko hamesha latest count pe rakho (single source of truth)
        if ($newStage != $user->journey_stage) {
            DB::table('users')->where('id', $user->id)->update([
                'journey_stage' => $newStage,
                'journey_completed_at' => $newStage >= 5 ? now() : null,
                'updated_at' => now(),
            ]);
            $user->journey_stage = $newStage;
        }

        // Stage 5+ -> dashboard. ?view=1 ho to onboarding hi dikhao.
        if ($newStage >= 5 && !request()->has('view')) {
            return redirect()->route('tenant.dashboard');
        }

      $agreement = \App\Models\RentAgreement::where('tenant_id', $tenant->id)->latest()->first();
    return view('tenant.onboarding', compact('steps', 'agreement'));
    }

    /**
     * Unlinked leads (user_id NULL) ko is user se phone/email match karke link karo
     */
    private function linkLeadsToUser($user)
    {
        if (!$user) return;

        $last10 = $user->phone
            ? substr(preg_replace('/[^0-9]/', '', $user->phone), -10)
            : null;

        if (!$last10 && !$user->email) return;

        DB::table('leads')
            ->whereNull('user_id')
            ->where(function ($q) use ($user, $last10) {
                $applied = false;
                if ($user->email) {
                    $q->where('email', $user->email);
                    $applied = true;
                }
                if ($last10) {
                    $applied
                        ? $q->orWhereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10])
                        : $q->whereRaw('RIGHT(REPLACE(REPLACE(phone, "+91", ""), " ", ""), 10) = ?', [$last10]);
                }
            })
            ->update(['user_id' => $user->id, 'updated_at' => now()]);
    }

    /**
     * Journey steps calculation — single source of truth
     * (public so other controllers can use it via isStepComplete)
     */
    public function calculateJourneySteps($user, $tenant)
    {
        // ===== Step 1: Profile Complete =====
        $profileComplete = !empty($user->phone)
            && !empty($tenant->emergency_phone)
            && !empty($tenant->emergency_name);

        

        // ===== Step 3: Rental Agreement Signed =====
        $agreementSigned = !empty($tenant->agreement_signed_at);

        // ===== Step 4: Token Paid =====
        $tokenPaid = DB::table('token_payments')
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->exists();

        // ===== Step 5: Owner Confirms + Assigns Room =====
        $ownerConfirmed = $tenant->kyc_status === 'approved' && !empty($tenant->property_id);

        // ===== SEQUENCE: har step tabhi complete jab pichla bhi complete ho =====
       $agreementSigned = $agreementSigned && $profileComplete;
        $tokenPaid        = $tokenPaid        && $agreementSigned;
        $ownerConfirmed   = $ownerConfirmed   && $tokenPaid;

        return [
            [
                'id' => 1,
                'title' => 'Complete Your Profile',
                'desc' => 'Add personal info, occupation, emergency contact',
                'icon' => '👤',
                'completed' => $profileComplete,
                'cta' => 'Complete Profile',
                'cta_route' => 'tenant.profile',
                'unlocked' => true,
                'who' => 'You',
            ],
         
            [
               'id' => 2,
               'title' => 'Sign Rental Agreement',
                'desc' => 'Review and e-sign your rental agreement',
                'icon' => '📝',
                'completed' => $agreementSigned,
                'cta' => 'Sign Agreement',
                'cta_route' => 'tenant.agreement.sign',
                'unlocked' => $profileComplete,
                'who' => 'You',
            ],
            [
                'id' => 3,
               'title' => 'Pay Token Amount',
                'desc' => 'Pay the token amount to confirm your booking',
                'icon' => '💳',
                'completed' => $tokenPaid,
                'cta' => 'Pay Token',
                'cta_route' => 'tenant.token.pay',
                'unlocked' => $agreementSigned,
                'who' => 'You',
            ],
            [
                'id' => 4,
                'title' => 'Owner Confirms & Assigns Room',
                'desc' => 'PG owner reviews your details and assigns your room',
                'icon' => '✅',
                'completed' => $ownerConfirmed,
                'cta' => 'Wait for confirmation',
                'cta_route' => null,
                'unlocked' => $tokenPaid,
                'who' => 'PG Owner',
            ],
        ];
    }

    public function onboardingUpdate(Request $request)
    {
        return redirect()->route('tenant.onboarding')->with('success', 'Status updated!');
    }

  /**
     * Upload a single KYC document (one file input at a time)
     */
    public function kycUpload(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->with('error', 'Tenant record not found.');
        }

        $types = ['aadhaar_front', 'aadhaar_back', 'pan', 'photo', 'id_card', 'address_proof'];
        $count = 0;

        foreach ($types as $type) {
            if ($request->hasFile($type)) {
                $file = $request->file($type);

                if ($file->getSize() > 2 * 1024 * 1024) {
                    return back()->with('error', strtoupper($type) . ' file is too large (max 2MB).');
                }

                // Delete previous document of same type
                $old = TenantDocument::where('tenant_id', $tenant->id)
                    ->where('document_type', $type)->first();
                if ($old) {
                    if ($old->file_path && \Storage::disk('public')->exists($old->file_path)) {
                        \Storage::disk('public')->delete($old->file_path);
                    }
                    $old->delete();
                }

                $path = $file->store('tenant-kyc/' . $tenant->id, 'public');
                TenantDocument::create([
                    'tenant_id'     => $tenant->id,
                    'document_type' => $type,
                    'file_path'     => $path,
                    'file_name'     => $file->getClientOriginalName(),
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            // If already submitted/approved, do not downgrade status — owner has to re-review
            if ($tenant->kyc_status === 'rejected') {
                $tenant->update(['kyc_status' => 'pending']);
            }
            return redirect()->route('tenant.kyc')->with('success', "$count document(s) uploaded successfully.");
        }

        return back()->with('error', 'No file selected.');
    }

    /**
     * Token Payment Page
     */
public function tokenPay()
    {
        $user = Auth::user();

        // ===== CHAIN: token tabhi jab agreement (Step 3) sign ho chuka ho =====
        if (!self::isStepComplete($user->id, 3)) {
            return redirect()->route('tenant.onboarding')
                ->with('error', '⛔ Please sign your rental agreement first before paying the token.');
        }

        $tenant = Tenant::where('user_id', $user->id)->first();

        // Latest pending token for this user
        $tokenPayment = DB::table('token_payments')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        // ===== Naya self-serve flow: agar koi pending token nahi hai, default amount se ek bana do =====
        if (!$tokenPayment) {
            $defaultAmount = (float) env('DEFAULT_TOKEN_AMOUNT', 500);

            $newId = DB::table('token_payments')->insertGetId([
                'user_id'    => $user->id,
                'tenant_id'  => $tenant?->id,
                'amount'     => $defaultAmount,
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tokenPayment = DB::table('token_payments')->where('id', $newId)->first();
        }

        $razorpayKeyId   = env('RAZORPAY_KEY_ID');
        $razorpayOrderId = null;

        if ($tokenPayment && $tokenPayment->amount > 0) {
            try {
                $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
                $order = $api->order->create([
                    'amount'   => (int) round($tokenPayment->amount * 100),
                    'currency' => 'INR',
                    'receipt'  => 'token_' . $tokenPayment->id,
                ]);
                $razorpayOrderId = $order['id'];

                DB::table('token_payments')->where('id', $tokenPayment->id)->update([
                    'razorpay_order_id' => $razorpayOrderId,
                    'updated_at'        => now(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Razorpay order create failed: ' . $e->getMessage());
            }
        }

        $property = ($tenant && $tenant->property_id)
            ? \App\Models\Property::find($tenant->property_id)
            : null;

        return view('tenant.token-pay', compact('tenant', 'tokenPayment', 'razorpayOrderId', 'razorpayKeyId', 'property'));
    }

    /**
     * Verify Razorpay online payment
     */
    public function tokenVerify(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'token_payment_id'    => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_order_id'   => 'required',
            'razorpay_signature'  => 'required',
        ]);

        try {
            $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('tenant.token.pay')
                ->with('error', 'Payment verification failed. Please try again.');
        }

        DB::table('token_payments')
            ->where('id', $request->token_payment_id)
            ->where('user_id', $user->id)
            ->update([
                'payment_method'      => 'online',
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id'   => $request->razorpay_order_id,
                'status'              => 'paid',
                'paid_at'             => now(),
                'updated_at'          => now(),
            ]);

        return redirect()->route('tenant.onboarding')->with('success', '✅ Token paid successfully!');
    }

    /**
     * Tenant chooses CASH — stays pending until field exec collects
     */
    public function tokenCash(Request $request)
    {
        $user = Auth::user();

        DB::table('token_payments')
            ->where('id', $request->token_payment_id)
            ->where('user_id', $user->id)
            ->update([
                'payment_method' => 'cash',
                'updated_at'     => now(),
            ]);

        return redirect()->route('tenant.token.pay')
            ->with('success', 'Cash selected. Please pay your field executive — they will confirm the collection.');
    }

    /**
     * Kisi user ka ek step complete hai ya nahi — chain validation ke liye
     */
    public static function isStepComplete($userId, $stepId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $tenant = Tenant::where('user_id', $userId)->with('property')->first();
        if (!$tenant) return false;

        $ctrl = new self();
        $steps = $ctrl->calculateJourneySteps($user, $tenant);
        foreach ($steps as $s) {
            if ($s['id'] == $stepId) return (bool) $s['completed'];
        }
        return false;
    }
    
        /**
     * KYC dedicated page
     */
    public function kycPage()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            $tenant = Tenant::create([
                'user_id'    => $user->id,
                'name'       => $user->name,
                'phone'      => $user->phone,
                'email'      => $user->email,
                'status'     => 'active',
                'kyc_status' => 'pending',
                'monthly_rent'     => 0,
                'security_deposit' => 0,
            ]);
        }

        return view('tenant.kyc', compact('user', 'tenant'));
    }
    
        /**
     * Delete a single KYC document
     */
    public function kycDelete($type)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant) {
            return back()->with('error', 'Tenant record not found.');
        }

        if ($tenant->kyc_status === 'approved') {
            return back()->with('error', 'Cannot delete documents after KYC approval.');
        }

        $doc = TenantDocument::where('tenant_id', $tenant->id)
            ->where('document_type', $type)
            ->first();

        if (!$doc) {
            return back()->with('error', 'Document not found.');
        }

        if ($doc->file_path && \Storage::disk('public')->exists($doc->file_path)) {
            \Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();

        return redirect()->route('tenant.kyc')->with('success', 'Document deleted.');
    }
    
    
        /**
     * Submit KYC for review (status: pending -> submitted)
     */
    public function kycSubmit()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant) {
            return back()->with('error', 'Tenant record not found.');
        }

        $count = TenantDocument::where('tenant_id', $tenant->id)->count();
        if ($count < 3) {
            return back()->with('error', 'Please upload at least 3 documents before submitting.');
        }

        if ($tenant->kyc_status === 'approved') {
            return back()->with('error', 'KYC already approved.');
        }

        $tenant->update(['kyc_status' => 'submitted']);

        try {
            $tenant->owner?->notify(new \App\Notifications\KycSubmitted($tenant));
        } catch (\Exception $e) {
            \Log::warning('KYC notification failed: ' . $e->getMessage());
        }

        return redirect()->route('tenant.kyc')->with('success', '✓ KYC submitted! Owner will review within 24 hours.');
    }

    /**
     * ===== AADHAAR e-KYC (Setu) — Step 2 of the new tenant journey =====
     */

   public function aadhaarKycPage()
{
    // Redirect if already verified
    if (auth()->user()->journey_stage >= 2) {
        return redirect()->route('tenant.onboarding')
            ->with('info', 'Aadhaar already verified.');
    }
    return view('tenant.kyc-aadhaar');
}

public function aadhaarSendOtp(Request $request)
{
    $request->validate([
        'aadhaar_number' => ['required', 'digits:12'],
    ]);

    $setu   = app(SetuKycService::class);
    $result = $setu->createRequest();

    if (!$result['success']) {
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ]);
    }

    session(['setu_request_id' => $result['id']]);

    return response()->json([
        'success'       => true,
        'request_id'    => $result['id'],
        'captcha_image' => $result['captcha_image'],
        'valid_upto'    => $result['valid_upto'],
    ]);
}

    public function aadhaarVerifyOtp(Request $request)
{
    $step = $request->input('step', 'verify_otp');
    $setu = app(SetuKycService::class);

    if ($step === 'verify_aadhaar') {
        $request->validate([
            'request_id'     => ['required', 'string'],
            'aadhaar_number' => ['required', 'digits:12'],
            'captcha_code'   => ['required', 'string'],
        ]);

        $requestId = $request->input('request_id');

        if (session('setu_request_id') !== $requestId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Please start over.',
            ]);
        }

        $result = $setu->verifyAadhaar(
            $requestId,
            $request->input('aadhaar_number'),
            $request->input('captcha_code')
        );

        return response()->json($result);
    }

    // step = verify_otp
    $request->validate([
        'request_id' => ['required', 'string'],
        'otp'        => ['required', 'digits_between:4,6'],
        'share_code' => ['required', 'digits:4'],
    ]);

    $requestId = $request->input('request_id');

    if (session('setu_request_id') !== $requestId) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid session. Please start over.',
        ]);
    }

    $result = $setu->verifyOtp(
        $requestId,
        $request->input('otp'),
        $request->input('share_code')
    );

    if (!$result['success']) {
        return response()->json($result);
    }

    $user = auth()->user();
    $user->update([
        'aadhaar_verified' => true,
        'aadhaar_masked'   => $result['masked_aadhaar'] ?? null,
        'journey_stage'    => max($user->journey_stage, 2),
    ]);

    session()->forget('setu_request_id');

    return response()->json([
        'success' => true,
        'message' => 'Aadhaar verified successfully!',
        'name'    => $result['name'],
    ]);
}
    
    
    
    /**
     * ⚠️ TEMPORARY TESTING BYPASS — remove before going live.
     */
    public function aadhaarSkipForTesting(Request $request)
    {
        if (!config('app.debug')) {
            abort(403, 'Testing bypass is only available when APP_DEBUG=true');
        }

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if ($tenant) {
            $tenant->update([
                'aadhaar_number_masked' => 'TEST-BYPASS',
                'aadhaar_name' => $user->name,
                'aadhaar_verified_at' => now(),
            ]);
        }

        return redirect()->route('tenant.onboarding')
            ->with('success', '⚠️ Aadhaar step skipped for testing (Setu not yet connected).');
    }
    
    

    /**
     * ===== RENTAL AGREEMENT E-SIGN — Step 3 of the new tenant journey =====
     */

public function agreementSignPage()
{
    $user   = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->with('property')->first();

    if ($tenant && $tenant->agreement_signed_at) {
        return redirect()->route('tenant.onboarding')
            ->with('success', 'Agreement already signed.');
    }

    $pendingEsignUrl = session('esign_signer_url');

    // Active agreement fetch karo
    $agreement = \App\Models\RentAgreement::where('tenant_id', $tenant->id ?? 0)
        ->whereNotIn('status', ['terminated', 'expired'])
        ->latest()
        ->first();

    return view('tenant.agreement-sign', compact('tenant', 'user', 'pendingEsignUrl', 'agreement'));
}

public function agreementSignSubmit(Request $request)
{
    $user   = Auth::user();
    $tenant = Tenant::where('user_id', $user->id)->with('property')->first();

    if (!$tenant) {
        return response()->json(['success' => false, 'message' => 'Tenant record not found.']);
    }

    if ($tenant->agreement_signed_at) {
        return response()->json(['success' => false, 'message' => 'Agreement already signed.']);
    }

    try {
        // 1. PDF generate karo
        $pdfBytes = \Barryvdh\DomPDF\Facade\Pdf::loadView('tenant.agreement-pdf', compact('tenant', 'user'))
            ->setPaper('a4', 'portrait')
            ->output();

        $fileName = 'agreement-' . $tenant->id . '-' . time() . '.pdf';

        // 2. Setu pe upload karo (multipart, NOT base64)
        $uploaded = $this->setu->uploadDocumentBytes(
            $pdfBytes,
            $fileName,
            'Pizi Rental Agreement - ' . $user->name
        );

        $documentId = $uploaded['id'] ?? null;
        if (!$documentId) {
            return response()->json(['success' => false, 'message' => 'Document upload failed.']);
        }

        // 3. Signer payload — position NAMED STRING hai, x/y nahi
        $signer = [
            'identifier'  => $user->phone,
            'displayName' => $user->name,
            'signature'   => [
                'onPages'  => ['1'],
                'position' => 'bottom-left',
                'height'   => 60,
                'width'    => 180,
            ],
        ];

        // birthYear optional — Aadhaar OTP verification help karta hai
        if (!empty($tenant->dob)) {
            $signer['birthYear'] = (int) \Carbon\Carbon::parse($tenant->dob)->year;
        }

        // 4. Signature request banao
        $signatureRequest = $this->setu->createSignatureRequest(
            $documentId,
            $signer,
            route('tenant.agreement.callback')
        );

        $signerUrl = $signatureRequest['signers'][0]['url'] ?? null;
        if (!$signerUrl) {
            return response()->json(['success' => false, 'message' => 'Setu did not return a signing URL.']);
        }

        // 5. DB + session mein save karo
        $tenant->update([
            'esign_request_id' => $signatureRequest['id'],
            'esign_status'     => 'pending',
        ]);

        session([
            'esign_request_id' => $signatureRequest['id'],
            'esign_signer_url' => $signerUrl,
        ]);

        return response()->json([
            'success'    => true,
            'signer_url' => $signerUrl,
        ]);

    } catch (\Throwable $e) {
        \Log::error('[eSign] agreementSignSubmit error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function agreementCallback(Request $request)
{
    $success   = $request->query('success');
    $requestId = $request->query('id') ?: session('esign_request_id');
    $errCode   = $request->query('errCode');
    $errMsg    = $request->query('errorMessage');

    if ($success === 'false' || !$requestId) {
        return redirect()->route('tenant.agreement.sign')
            ->with('error', 'Signing was not completed. ' . ($errMsg ?? 'Please try again.'));
    }

    try {
        $status = $this->setu->getSignatureStatus($requestId);

        if (($status['status'] ?? '') === 'sign_complete') {
            $download    = $this->setu->getSignedDocumentDownload($requestId);
            $downloadUrl = $download['downloadUrl'] ?? null;

            $user   = Auth::user();
            $tenant = Tenant::where('user_id', $user->id)->first();

            $tenant->update([
                'agreement_signed_at'  => now(),
                'agreement_ip'         => $request->ip(),
                'esign_status'         => 'signed',
                'signed_doc_url' => $downloadUrl,
            ]);

            $user->update([
                'journey_stage' => max($user->journey_stage, 3),
            ]);

            try {
                $tenant->owner?->notify(new \App\Notifications\AgreementSigned($tenant->fresh()));
            } catch (\Exception $e) {
                \Log::warning('Agreement notification failed: ' . $e->getMessage());
            }

            session()->forget(['esign_request_id', 'esign_signer_url']);

          return redirect()->route('tenant.onboarding')
    ->with('success', '✅ Agreement signed successfully!')
    ->with('signed_doc_url', $downloadUrl);
        }

        return redirect()->route('tenant.onboarding')
            ->with('info', 'Signing is being processed. Please refresh in a moment.');

    } catch (\Throwable $e) {
        \Log::error('[eSign] agreementCallback error: ' . $e->getMessage());
        return redirect()->route('tenant.onboarding')
            ->with('error', 'Error processing signature. Please contact support.');
    }
}
}