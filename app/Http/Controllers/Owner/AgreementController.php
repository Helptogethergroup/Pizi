<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AgreementSignature;
use App\Models\RentAgreement;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $query = RentAgreement::where('owner_id', auth()->id())
            ->with('tenant', 'property');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('agreement_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $agreements = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'active' => RentAgreement::where('owner_id', auth()->id())->where('status', 'active')->count(),
            'draft' => RentAgreement::where('owner_id', auth()->id())->where('status', 'draft')->count(),
            'expiring' => RentAgreement::where('owner_id', auth()->id())
                ->where('status', 'active')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>', now())
                ->count(),
            'expired' => RentAgreement::where('owner_id', auth()->id())
                ->where(function($q) {
                    $q->where('status', 'expired')
                      ->orWhere(function($q2) {
                          $q2->where('end_date', '<', now())
                             ->whereIn('status', ['active', 'signed_owner']);
                      });
                })->count(),
        ];

        return view('owner.agreements.index', compact('agreements', 'stats'));
    }

    public function create(Request $request)
    {
        $tenants = Tenant::where('owner_id', auth()->id())
            ->where('status', 'active')
            ->with('property')
            ->orderBy('name')
            ->get();

        $selectedTenant = $request->tenant_id ? $tenants->firstWhere('id', $request->tenant_id) : null;

        return view('owner.agreements.create', compact('tenants', 'selectedTenant'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'maintenance_fee' => 'nullable|numeric|min:0',
            'electricity_included' => 'nullable|boolean',
            'water_included' => 'nullable|boolean',
            'food_included' => 'nullable|boolean',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'lock_in_months' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'rent_due_day' => 'nullable|integer|min:1|max:31',
            'terms_template' => 'required|in:delhi_standard,custom',
            'additional_terms' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $tenant = Tenant::where('id', $data['tenant_id'])
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $data['agreement_number'] = 'AGR-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $data['property_id'] = $tenant->property_id;
        $data['owner_id'] = auth()->id();
        $data['room_number'] = $tenant->room_number;
        $data['bed_number'] = $tenant->bed_number;
        $data['maintenance_fee'] = $data['maintenance_fee'] ?? 0;
        $data['lock_in_months'] = $data['lock_in_months'] ?? 3;
        $data['notice_period_days'] = $data['notice_period_days'] ?? 30;
        $data['rent_due_day'] = $data['rent_due_day'] ?? 5;

        foreach (['electricity_included', 'water_included', 'food_included'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        $data['status'] = 'draft';

        $agreement = RentAgreement::create($data);

        return redirect()->route('owner.agreements.show', $agreement)
            ->with('success', '✓ Agreement created. ' . $agreement->agreement_number);
    }

    public function show(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);
        $agreement->load('tenant', 'property', 'owner', 'signatures');
        return view('owner.agreements.show', compact('agreement'));
    }

    public function edit(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        if (!in_array($agreement->status, ['draft', 'sent'])) {
            return back()->withErrors(['edit' => 'Cannot edit signed/active agreement.']);
        }

        $tenants = Tenant::where('owner_id', auth()->id())->orderBy('name')->get();
        return view('owner.agreements.edit', compact('agreement', 'tenants'));
    }

    public function update(Request $request, RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        if (!in_array($agreement->status, ['draft', 'sent'])) {
            return back()->withErrors(['edit' => 'Cannot edit.']);
        }

        $data = $request->validate([
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'maintenance_fee' => 'nullable|numeric|min:0',
            'electricity_included' => 'nullable|boolean',
            'water_included' => 'nullable|boolean',
            'food_included' => 'nullable|boolean',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'lock_in_months' => 'nullable|integer|min:0',
            'notice_period_days' => 'nullable|integer|min:0',
            'rent_due_day' => 'nullable|integer|min:1|max:31',
            'additional_terms' => 'nullable|string',
            'house_rules' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        foreach (['electricity_included', 'water_included', 'food_included'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        $agreement->update($data);
        return redirect()->route('owner.agreements.show', $agreement)->with('success', '✓ Updated.');
    }

    public function signAsOwner(Request $request, RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        if ($agreement->ownerSigned()) {
            return back()->withErrors(['sign' => 'Already signed by owner.']);
        }

        AgreementSignature::create([
            'agreement_id' => $agreement->id,
            'signer_type' => 'owner',
            'signer_name' => auth()->user()->name,
            'signer_id' => auth()->id(),
            'signature_data' => $request->signature_data,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 250),
            'signed_at' => now(),
        ]);

        // Update status
        $newStatus = $agreement->tenantSigned() ? 'active' : 'signed_owner';
        $agreement->update([
            'status' => $newStatus,
            'signed_at' => $newStatus === 'active' ? now() : $agreement->signed_at,
        ]);

        return back()->with('success', '✓ Signed as owner.');
    }

    public function signAsTenant(Request $request, RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        if ($agreement->tenantSigned()) {
            return back()->withErrors(['sign' => 'Already signed by tenant.']);
        }

        AgreementSignature::create([
            'agreement_id' => $agreement->id,
            'signer_type' => 'tenant',
            'signer_name' => $agreement->tenant->name,
            'signer_id' => $agreement->tenant->user_id,
            'signature_data' => $request->signature_data,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 250),
            'signed_at' => now(),
        ]);

        $newStatus = $agreement->ownerSigned() ? 'active' : 'signed_tenant';
        $agreement->update([
            'status' => $newStatus,
            'signed_at' => $newStatus === 'active' ? now() : $agreement->signed_at,
        ]);

        return back()->with('success', '✓ Tenant signature recorded.');
    }

    public function terminate(Request $request, RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        $request->validate([
            'termination_reason' => 'required|string|max:500',
        ]);

        $agreement->update([
            'status' => 'terminated',
            'terminated_at' => now(),
            'termination_reason' => $request->termination_reason,
        ]);

        return back()->with('success', '✓ Agreement terminated.');
    }

    public function renew(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        $newAgreement = $agreement->replicate();
        $newAgreement->agreement_number = 'AGR-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $newAgreement->parent_agreement_id = $agreement->id;
        $newAgreement->start_date = $agreement->end_date;
        $newAgreement->end_date = $agreement->end_date->copy()->addMonths($agreement->duration_months);
        $newAgreement->status = 'draft';
        $newAgreement->signed_at = null;
        $newAgreement->terminated_at = null;
        $newAgreement->save();

        // Mark old as renewed
        $agreement->update(['status' => 'renewed']);

        return redirect()->route('owner.agreements.show', $newAgreement)
            ->with('success', '✓ Renewal draft created.');
    }

    public function preview(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);
        $agreement->load('tenant', 'property', 'owner', 'signatures');
        return view('owner.agreements.preview', compact('agreement'));
    }
    
        public function downloadPdf(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);
        $agreement->load('tenant', 'property', 'owner', 'signatures');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('owner.agreements.preview', compact('agreement'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($agreement->agreement_number . '.pdf');
    }

    public function destroy(RentAgreement $agreement)
    {
        $this->authorize_owner($agreement);

        if (!in_array($agreement->status, ['draft', 'sent'])) {
            return back()->withErrors(['delete' => 'Cannot delete signed/active agreement.']);
        }

        $agreement->signatures()->delete();
        $agreement->delete();
        return redirect()->route('owner.agreements.index')->with('success', '✓ Deleted.');
    }

    private function authorize_owner(RentAgreement $agreement): void
    {
        if ($agreement->owner_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}