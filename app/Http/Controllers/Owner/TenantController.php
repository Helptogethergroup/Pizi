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

        // One PG at a time (chips above the list); counts below follow the PG picked.
        $propertyId = (int) $request->get('property', 0);
        if (!in_array($propertyId, collect($managedIds)->map(fn ($i) => (int) $i)->all(), true)) {
            $propertyId = 0;
        }
        $mine = !($filter === 'unassigned' && auth()->user()->role !== 'pg_manager');
        if ($mine && $propertyId) {
            $query->where('property_id', $propertyId);
        }

        // PG by PG, then room by room ("Room 9" before "Room 10"); unassigned sign-ups stay newest first
        $tenants = ($mine
                ? $query->orderByRaw('(select name from properties where properties.id = tenants.property_id), length(room_number), room_number, name')
                : $query->latest())
            ->paginate(30)->withQueryString();

        $scope = fn () => Tenant::whereIn('property_id', $managedIds)->when($propertyId, fn ($q) => $q->where('property_id', $propertyId));
        $stats = [
            'total' => $scope()->count(),
            'active' => $scope()->where('status', 'active')->count(),
            'pending_kyc' => $scope()->whereIn('kyc_status', ['pending', 'submitted'])->count(),
            'notice' => $scope()->where('status', 'notice_period')->count(),
        ];

        // the chips: every PG that has tenants, with how many
        $counts = Tenant::whereIn('property_id', $managedIds)->selectRaw('property_id, count(*) as c')->groupBy('property_id')->pluck('c', 'property_id');
        $propertyTabs = Property::whereIn('id', $counts->keys())->orderBy('name')->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'count' => (int) $counts[$p->id]]);

        return view('owner.tenants.index', compact('tenants', 'stats', 'propertyTabs', 'propertyId'));
    }

    public function create()
    {
        $this->checkAccess();
        $properties = Property::whereIn('id', auth()->user()->getManagedPropertyIds())->orderBy('name')->get();
        return view('owner.tenants.create', compact('properties'));
    }

    /**
     * Aadhaar check for a new tenant, through Setu DigiLocker. The owner presses Start and gets a link;
     * the tenant opens it on their own phone, signs in to DigiLocker and taps Allow. The Add Tenant form
     * then fills itself from the tenant's Aadhaar. (DigiLocker needs the tenant's own consent, so it
     * cannot be done by typing the tenant's Aadhaar number here.)
     */
    public function aadhaarStart(\App\Services\SetuDigilockerService $dl)
    {
        if (!$dl->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Aadhaar check is not switched on yet. Please add the tenant and upload their documents instead.']);
        }

        $result = $dl->createRequest(route('renter.digilocker.return'));
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not start the Aadhaar check. Please try again.']);
        }

        session(['owner_kyc_aadhaar_id' => $result['id']]);

        return response()->json(['success' => true, 'id' => $result['id'], 'url' => $result['url']]);
    }

    /**
     * Asked every few seconds by the Add Tenant page while the tenant is on DigiLocker: still waiting,
     * failed, or done with the details to fill into the form.
     */
    public function aadhaarVerifyCaptcha(Request $request, \App\Services\SetuDigilockerService $dl)
    {
        $requestId = session('owner_kyc_aadhaar_id');
        if (!$requestId) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start again.']);
        }

        $status = $dl->status($requestId);
        if (!$status['success']) {
            return response()->json(['success' => false, 'message' => $status['message'] ?? 'Could not check right now.']);
        }
        if (($status['status'] ?? '') === 'revoked') {
            session()->forget('owner_kyc_aadhaar_id');

            return response()->json(['success' => false, 'message' => 'This check has expired. Please start again.']);
        }
        if (($status['status'] ?? '') !== 'authenticated') {
            return response()->json(['success' => true, 'pending' => true]);
        }

        $result = $dl->aadhaar($requestId);
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not read the Aadhaar details.']);
        }
        session()->forget('owner_kyc_aadhaar_id');

        $d = $result['data'];

        return response()->json(['success' => true, 'pending' => false, 'data' => [
            'name' => $d['name'],
            'dob' => $d['dob'],
            'gender' => $d['gender'],
            'address' => $d['address'],
            'city' => $d['city'],
            'state' => $d['state'],
            'pincode' => $d['pincode'],
            'aadhaar_last4' => substr(preg_replace('/\D/', '', (string) $d['masked_aadhaar']), -4),
            // the photo is not taken: it is not needed and is not stored
            'photo_base64' => null,
        ]]);
    }

    /** The old OTP step. DigiLocker has no OTP typed here, so there is nothing to do. */
    public function aadhaarVerifyOtp(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'This step is no longer used.'], 410);
    }

    /** One page with everything about the tenant, laid out like the Add Tenant form. */
    public function overview(Tenant $tenant)
    {
        $this->authorize_owner($tenant);
        $tenant->load('documents', 'property.city', 'property.locality');

        return view('owner.tenants.overview', ['tenant' => $tenant]);
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

                // Someone who signed up in the Pizi app to look for a PG becomes a tenant the moment
                // an owner adds them (only a "guest" can be turned into a tenant, never an owner).
                if ($user && $user->role === 'guest') {
                    DB::table('users')->where('id', $user->id)->update([
                        'role' => 'tenant', 'journey_stage' => 5, 'journey_completed_at' => now(), 'updated_at' => now(),
                    ]);
                    $user->refresh();
                }

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
                    $data['advance_rent_amount'], $data['aadhaar_verified'], $data['aadhaar_photo_base64'],
                    $data['police_verification'], $data['police_document']
                );

                $tenant = Tenant::create($data);

                // Police verification: "yes" with the document marks it done; "no" leaves it pending, and the owner and
                // the tenant both see it as pending until the document is uploaded.
                if ($request->input('police_verification') === 'yes' && $request->hasFile('police_document')) {
                    $file = $request->file('police_document');
                    TenantDocument::create([
                        'tenant_id' => $tenant->id,
                        'document_type' => 'police_verification',
                        'file_path' => $file->store('tenants/' . $tenant->id, 'public'),
                        'file_name' => mb_substr(preg_replace('/[^\w.\- ]+/u', '_', $file->getClientOriginalName()), 0, 120),
                    ]);
                    $tenant->forceFill(['police_verification_status' => 'done'])->save();
                }

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

        // the one-view page: everything entered in the form, laid out as the tenant's record
        return redirect()->route('owner.tenants.overview', $tenant)
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
        // the bed goes back to vacant, otherwise it would stay "occupied" by nobody
        Bed::where('tenant_id', $tenant->id)->update(['tenant_id' => null, 'status' => 'vacant', 'occupied_since' => null]);
        $tenant->delete();
        return redirect()->route('owner.tenants.index')->with('success', '✓ Tenant removed.');
    }

    public function uploadDocument(Request $request, Tenant $tenant)
    {
        $this->authorize_owner($tenant);

        $request->validate([
            'document_type' => 'required|in:aadhaar_front,aadhaar_back,pan,employment_id,student_id,address_proof,photo,police_verification,other',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'document_number' => 'nullable|string|max:50',
        ]);

        // the police document replaces the earlier one and marks the verification done (not a KYC document)
        if ($request->document_type === 'police_verification') {
            foreach (TenantDocument::where('tenant_id', $tenant->id)->where('document_type', 'police_verification')->get() as $old) {
                \Storage::disk('public')->delete($old->file_path);
                $old->delete();
            }
            TenantDocument::create([
                'tenant_id' => $tenant->id,
                'document_type' => 'police_verification',
                'file_path' => $request->file('file')->store('tenants/' . $tenant->id, 'public'),
            ]);
            $tenant->forceFill(['police_verification_status' => 'done'])->save();

            return back()->with('success', '✓ Police verification document uploaded. Marked Done.');
        }

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
        $wasPolice = $document->document_type === 'police_verification';
        $document->delete();
        if ($wasPolice) {
            // no police document any more: pending again, for the owner and the tenant to see
            $tenant->forceFill(['police_verification_status' => 'pending'])->save();
        }
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
            // police verification: "yes" needs the document, "no" leaves it pending
            'police_verification' => 'nullable|in:yes,no',
            'police_document' => 'nullable|required_if:police_verification,yes|file|mimes:jpg,jpeg,png,pdf|max:5120',
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