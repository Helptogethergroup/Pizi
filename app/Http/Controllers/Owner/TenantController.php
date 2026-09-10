<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
  public function index(Request $request)
    {
        $this->checkAccess();
        $managedIds = auth()->user()->getManagedPropertyIds();
        $filter = $request->get('filter', 'mine');

        // Unassigned/self-registered tenant claiming is an owner-level
        // decision — PG Managers don't get this queue.
        if ($filter === 'unassigned' && auth()->user()->role !== 'pg_manager') {
            $query = Tenant::whereNull('owner_id')->with('property', 'documents');
        } else {
            $query = Tenant::whereIn('property_id', $managedIds)->with('property', 'documents');
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($kyc = $request->get('kyc')) {
            $query->where('kyc_status', $kyc);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        $tenants = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => Tenant::whereIn('property_id', $managedIds)->count(),
            'active' => Tenant::whereIn('property_id', $managedIds)->where('status', 'active')->count(),
            'pending_kyc' => Tenant::whereIn('property_id', $managedIds)->whereIn('kyc_status', ['pending', 'submitted'])->count(),
            'notice' => Tenant::whereIn('property_id', $managedIds)->where('status', 'notice_period')->count(),
        ];

        return view('owner.tenants.index', compact('tenants', 'stats'));
    }

    public function create()
    {
        $this->checkAccess();
        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())->orderBy('name')->get();
        return view('owner.tenants.create', compact('properties'));
    }

    /**
     * Step 1 of Aadhaar auto-KYC — start a request, get a captcha to show
     * the owner. Lets the owner pull the tenant's verified details
     * straight from UIDAI, so the tenant never uploads any ID photos.
     */
    public function aadhaarStart(\App\Services\SetuKycService $setu)
    {
        $result = $setu->createRequest();

        if ($result['success']) {
            session(['owner_kyc_aadhaar_id' => $result['id']]);
        }

        return response()->json($result);
    }

    /**
     * Step 2 — Aadhaar number + captcha text. On success, Setu texts an
     * OTP to the Aadhaar-linked mobile.
     */
    public function aadhaarVerifyCaptcha(Request $request, \App\Services\SetuKycService $setu)
    {
        $request->validate([
            'aadhaar_number' => 'required|digits:12',
            'captcha' => 'required|string',
        ]);

        $requestId = session('owner_kyc_aadhaar_id');
        if (!$requestId) {
            return response()->json(['success' => false, 'message' => 'Session expired — start over.']);
        }

        $result = $setu->verifyAadhaar($requestId, $request->aadhaar_number, $request->captcha);

        return response()->json($result);
    }

    /**
     * Step 3 — OTP + a 4-digit share code the owner picks. Returns the
     * fetched details so the "Add Tenant" form can auto-fill itself.
     */
    public function aadhaarVerifyOtp(Request $request, \App\Services\SetuKycService $setu)
    {
        $request->validate([
            'otp' => 'required|digits_between:4,6',
            'share_code' => 'required|digits:4',
        ]);

        $requestId = session('owner_kyc_aadhaar_id');
        if (!$requestId) {
            return response()->json(['success' => false, 'message' => 'Session expired — start over.']);
        }

        $result = $setu->verifyOtp($requestId, $request->otp, $request->share_code);
        session()->forget('owner_kyc_aadhaar_id');

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $data = $this->validateTenant($request);

        // ---- 1. Agar bed select ki hai to usko LOCK karke fetch karo
        //         (race condition se bachne ke liye)
        $bed = null;
        if (!empty($data['bed_id'])) {
            $bed = Bed::where('id', $data['bed_id'])
                ->where('property_id', $data['property_id'])
                ->where('status', 'vacant')
                ->lockForUpdate()
                ->first();

            if (!$bed) {
                return back()
                    ->withErrors(['bed_id' => 'This bed is no longer available. Please select a different one.'])
                    ->withInput();
            }
        }

        try {
            $tenant = DB::transaction(function () use ($data, $bed, $request) {

                // ---- 2. Phone normalize karo
                $phone = substr(preg_replace('/[^0-9]/', '', $data['phone']), -10);

                // ---- 3. Existing User dhoondo is phone se
                $user = User::where('phone', $phone)->first();

                if ($user && $user->role !== 'tenant') {
                    throw ValidationException::withMessages([
                        'phone' => "This number is already registered as a '{$user->role}' account. Please use a different number.",
                    ]);
                }

                if (!$user) {
                    // ---- 4. Naya User account banao (role=tenant)
                    //         NOTE: journey_stage User model ke $fillable mein
                    //         nahi hai, isliye create() ke baad ALAG SE raw
                    //         DB update se set karna zaroori hai (warna
                    //         Eloquent mass-assignment chupchap ignore kar
                    //         deta hai — koi error nahi deta, bas save nahi hota).
                    $user = User::create([
                        'name'      => $data['name'],
                        'phone'     => $phone,
                        'email'     => $phone . '@temp.pizi.in',
                        'password'  => Hash::make(Str::random(32)),
                        'role'      => 'tenant',
                        'is_active' => true,
                    ]);

                    DB::table('users')->where('id', $user->id)->update([
                        'journey_stage' => 5,
                        'journey_completed_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $user->journey_stage = 5;
                } elseif ((int) $user->journey_stage < 5) {
                    DB::table('users')->where('id', $user->id)->update([
                        'journey_stage' => 5,
                        'journey_completed_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $user->journey_stage = 5;
                }

                // ---- 5. Tenant record banao, User se link karke
                $data['user_id']  = $user->id;
                $data['owner_id'] = $this->effectiveOwnerId();
                $data['status']   = 'active';

                if ($bed) {
                    $data['room_number']  = $bed->room->room_number;
                    $data['bed_number']   = $bed->bed_number;
                    $data['monthly_rent'] = $data['monthly_rent'] ?? $bed->monthly_rent;
                }

                // Aadhaar-verified via Setu OKYC → auto-approve, no manual
                // document upload/review needed at all.
                $aadhaarVerified = $request->boolean('aadhaar_verified');
                $data['kyc_status'] = ($aadhaarVerified || $request->boolean('kyc_verified_now'))
                    ? 'approved'
                    : 'pending';

                if ($aadhaarVerified) {
                    $data['aadhaar_number_masked'] = $data['aadhaar_number_masked'] ?? null;
                    $data['aadhaar_name'] = $data['aadhaar_name'] ?? null;
                    $data['aadhaar_verified_at'] = now();
                }

                // Marker: yeh tenant self-serve journey se nahi guzra —
                // owner ne khud add kiya. Onboarding recalculation isko
                // skip karega (warna journey_stage wapas neeche gir jaata).
                $data['onboarding_source'] = 'owner_added';

                               // Advance rent / security deposit status ye Tenant table ka field nahi
                // hai — alag se nikaal ke baad me RentPayment banane ke liye use karenge
                $securityDepositStatus = $data['security_deposit_status'] ?? null;
                $advanceRentMonths = $data['advance_rent_months'] ?? 0;
                $advanceRentAmount = $data['advance_rent_amount'] ?? 0;
                $aadhaarPhotoBase64 = $data['aadhaar_photo_base64'] ?? null;

                unset(
                    $data['bed_id'], $data['security_deposit_status'], $data['advance_rent_months'],
                    $data['advance_rent_amount'], $data['aadhaar_verified'], $data['aadhaar_photo_base64']
                );

                $tenant = Tenant::create($data);

                // Aadhaar photo (base64 from Setu) — save it the same way a
                // manually-uploaded photo would be, so it shows up wherever
                // tenant documents are already displayed.
                if ($aadhaarPhotoBase64) {
                    try {
                        $binary = base64_decode($aadhaarPhotoBase64);
                        $path = 'tenants/' . $tenant->id . '/aadhaar-photo-' . time() . '.jpg';
                        \Storage::disk('public')->put($path, $binary);
                        TenantDocument::create([
                            'tenant_id' => $tenant->id,
                            'document_type' => 'photo',
                            'file_path' => $path,
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Aadhaar photo save failed: ' . $e->getMessage());
                    }
                }

                             // ---- 6. Bed ko occupied mark karo
                if ($bed) {
                    $bed->update([
                        'tenant_id'      => $tenant->id,
                        'status'         => 'occupied',
                        'occupied_since' => now(),
                    ]);
                }

                // ---- 7. Advance rent liya ho to RentBill + RentPayment bana do
                if ($advanceRentAmount > 0) {
                    $advBill = \App\Models\RentBill::create([
                        'tenant_id'     => $tenant->id,
                        'property_id'   => $tenant->property_id,
                        'owner_id'      => $tenant->owner_id,
                        'bill_number'   => 'PIZI-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                        'month'         => now()->format('Y-m'),
                        'rent_amount'   => $advanceRentAmount,
                        'total_amount'  => $advanceRentAmount,
                        'paid_amount'   => $advanceRentAmount,
                        'due_amount'    => 0,
                        'due_date'      => now(),
                        'status'        => 'paid',
                        'notes'         => 'Advance rent (' . $advanceRentMonths . ' month(s)) collected at onboarding.',
                    ]);

                    \App\Models\RentPayment::create([
                        'rent_bill_id'    => $advBill->id,
                        'tenant_id'       => $tenant->id,
                        'owner_id'        => $tenant->owner_id,
                        'amount'          => $advanceRentAmount,
                        'payment_method'  => 'cash',
                        'received_by_id'  => auth()->id(),
                        'receipt_number'  => 'RCP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5)),
                        'paid_at'         => now(),
                        'notes'           => 'Advance rent collected at tenant onboarding.',
                    ]);
                }

                // ---- 8. Security deposit status note me save karo (Tenant model me column ho to wahi use karo)
                if ($securityDepositStatus) {
                    $tenant->notes = trim(($tenant->notes ?? '') . "\nSecurity Deposit Status: " . ucfirst($securityDepositStatus) . " paid.");
                    $tenant->save();
                }

                return $tenant;
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('owner.tenants.show', $tenant)
            ->with('success', '✓ Tenant added'
                . ($bed ? ' & Room ' . $bed->room->room_number . '/Bed ' . $bed->bed_number . ' assigned' : '')
                . '. Tenant can log in with their phone number via OTP to access their dashboard.');
    }

    public function show(Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $tenant->load('property', 'documents');
        return view('owner.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())->orderBy('name')->get();
        return view('owner.tenants.edit', compact('tenant', 'properties'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $data = $this->validateTenant($request);
        $tenant->update($data);
        return redirect()->route('owner.tenants.show', $tenant)->with('success', '✓ Tenant updated.');
    }

    public function destroy(Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $tenant->delete();
        return redirect()->route('owner.tenants.index')->with('success', '✓ Tenant removed.');
    }

    public function uploadDocument(Request $request, Tenant $tenant)
    {
        $this->authorize_owner($tenant);

        $request->validate([
            'document_type' => 'required|in:aadhaar_front,aadhaar_back,pan,employment_id,student_id,address_proof,photo,other',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'document_number' => 'nullable|string|max:50',
        ]);

        $path = $request->file('file')->store('tenants/' . $tenant->id, 'public');

        TenantDocument::create([
            'tenant_id' => $tenant->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'document_number' => $request->document_number,
        ]);

        if ($tenant->kyc_status === 'pending') {
            $tenant->update(['kyc_status' => 'submitted']);
        }

        return back()->with('success', '✓ Document uploaded.');
    }

    public function deleteDocument(TenantDocument $document)
    {
        $tenant = $document->tenant;
        $this->authorize_owner($tenant);
        $document->delete();
        return back()->with('success', '✓ Document removed.');
    }

    public function approveKyc(Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $tenant->update(['kyc_status' => 'approved']);
        return back()->with('success', '✓ KYC approved.');
    }

    public function rejectKyc(Request $request, Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $tenant->update([
            'kyc_status' => 'rejected',
            'kyc_remarks' => $request->input('remarks'),
        ]);
        return back()->with('success', '✓ KYC rejected. Tenant will be notified.');
    }

    public function changeStatus(Request $request, Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $request->validate(['status' => 'required|in:active,notice_period,left,blacklisted']);
        $update = ['status' => $request->status];

        if ($request->status === 'left') {
            $update['move_out_date'] = now();

            // Auto-free the bed — owner doesn't need to separately go
            // to Rooms & Beds and unassign it manually.
            \App\Models\Bed::where('tenant_id', $tenant->id)->update([
                'tenant_id' => null,
                'status' => 'vacant',
                'occupied_since' => null,
            ]);
        }

        $tenant->update($update);

        if ($request->status === 'left' && $tenant->phone) {
            try {
                app(\App\Services\WhatsAppService::class)->sendTemplate(
                    $tenant->phone,
                    'move_out_refund_status',
                    [$tenant->name, number_format($tenant->security_deposit ?? 0, 0), 7]
                );
            } catch (\Exception $e) {
                \Log::warning('Move-out WhatsApp alert failed: ' . $e->getMessage());
            }
        }

        $bedFreedNote = $request->status === 'left' ? ' Bed automatically freed up.' : '';
        return back()->with('success', '✓ Status updated.' . $bedFreedNote);
    }


    /**
     * Owner claims an unassigned (self-registered) tenant — assigns their
     * own property and marks KYC approved, completing Step 5 of the journey.
     * PG Managers cannot claim — this is an owner-level decision.
     */
    public function claim(Request $request, Tenant $tenant)
    {
        if (auth()->user()->role === 'pg_manager') {
            abort(403, 'Only the property owner can claim new tenants.');
        }

        if ($tenant->owner_id !== null) {
            abort(403, 'This tenant is already assigned.');
        }

        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_number' => 'nullable|string|max:50',
            'monthly_rent' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
        ]);

        $property = Property::where('id', $data['property_id'])
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $tenant->update([
            'owner_id' => auth()->id(),
            'property_id' => $property->id,
            'room_number' => $data['room_number'] ?? null,
            'monthly_rent' => $data['monthly_rent'] ?? $property->rent_min,
            'security_deposit' => $data['security_deposit'] ?? $property->security_deposit,
            'kyc_status' => 'approved',
        ]);

        return redirect()->route('owner.tenants.show', $tenant)
            ->with('success', '✓ Tenant claimed and room assigned!');
    }

    /**
     * AJAX endpoint — property select hote hi is property ke vacant
     * beds ka dropdown populate karne ke liye.
     */
    public function vacantBeds(Property $property)
    {
        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($property->id)) {
            abort(403);
        }

        $beds = Bed::where('property_id', $property->id)
            ->where('status', 'vacant')
            ->with('room')
            ->get()
            ->map(function ($bed) {
                return [
                    'id' => $bed->id,
                    'label' => 'Room ' . $bed->room->room_number . ' — Bed ' . $bed->bed_number
                        . ($bed->monthly_rent ? ' (₹' . number_format($bed->monthly_rent) . '/mo)' : ''),
                    'monthly_rent' => $bed->monthly_rent,
                ];
            })
            ->values();

        return response()->json($beds);
    }

    private function validateTenant(Request $request): array
    {
        return $request->validate([
            'property_id' => 'required|exists:properties,id',
            'bed_id' => 'nullable|exists:beds,id',
            'name' => 'required|string|max:200',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:150',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'occupation' => 'nullable|string|max:100',
            'company_college' => 'nullable|string|max:200',
            'address_line' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'emergency_name' => 'nullable|string|max:200',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'bed_number' => 'nullable|string|max:20',
             'monthly_rent' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'security_deposit_status' => 'nullable|in:full,half,pending',
            'advance_rent_months' => 'nullable|numeric|min:0|max:12',
            'advance_rent_amount' => 'nullable|numeric|min:0',
            'move_in_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'aadhaar_verified' => 'nullable|boolean',
            'aadhaar_number_masked' => 'nullable|string|max:20',
            'aadhaar_name' => 'nullable|string|max:200',
            'aadhaar_photo_base64' => 'nullable|string',
        ]);
    }

   private function authorize_owner(Tenant $tenant): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        if ($tenant->owner_id === null) {
            return; // unassigned — any owner can view/claim (pg_manager blocked inside claim() itself)
        }

        $this->checkAccess();

        if (!auth()->user()->getManagedPropertyIds()->contains($tenant->property_id)) {
            abort(403);
        }
    }

    /**
     * A tenant's owner_id must always be the real property owner —
     * never the PG Manager's own user id.
     */
    private function effectiveOwnerId()
    {
        $user = auth()->user();
        return $user->role === 'pg_manager' ? $user->owner_id : $user->id;
    }

    /**
     * Blocks access entirely if this PG Manager wasn't granted the
     * "My Tenants" feature by their owner.
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasFeature('tenants')) {
            abort(403, 'You do not have access to Tenants.');
        }
    }
}