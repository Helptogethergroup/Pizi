<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgreementController extends Controller
{
    private function ownerScope(Request $request, $query, $alias = '')
    {
        if ($request->user()->role !== 'admin') {
            $col = $alias ? $alias . '.owner_id' : 'owner_id';
            $query->where($col, $request->user()->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = DB::table('rent_agreements as a')
            ->leftJoin('tenants as t', 'a.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'a.property_id', '=', 'p.id')
            ->select('a.*', 't.name as tenant_name', 'p.name as property_name');
        $this->ownerScope($request, $query, 'a');

        if ($request->status) $query->where('a.status', $request->status);

        $agreements = $query->orderBy('a.created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $agreements]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
        $agreementNumber = 'AGR-' . date('Ymd') . '-' . str_pad($tenant->id, 4, '0', STR_PAD_LEFT);

        $id = DB::table('rent_agreements')->insertGetId([
            'agreement_number' => $agreementNumber,
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $user->id,
            'room_number' => $request->room_number ?? $tenant->room_number,
            'bed_number' => $request->bed_number ?? $tenant->bed_number,
            'monthly_rent' => $request->monthly_rent,
            'security_deposit' => $request->security_deposit,
            'maintenance_fee' => $request->maintenance_fee ?? 0,
            'electricity_included' => $request->electricity_included ?? false,
            'water_included' => $request->water_included ?? true,
            'food_included' => $request->food_included ?? false,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'lock_in_months' => $request->lock_in_months ?? 3,
            'notice_period_days' => $request->notice_period_days ?? 30,
            'rent_due_day' => $request->rent_due_day ?? 5,
            'terms_template' => $request->terms_template ?? 'delhi_standard',
            'additional_terms' => $request->additional_terms,
            'house_rules' => $request->house_rules,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $id, 'agreement_number' => $agreementNumber]]);
    }

    public function show(Request $request, $id)
    {
        $query = DB::table('rent_agreements')->where('id', $id);
        $this->ownerScope($request, $query);
        $agreement = $query->first();
        if (!$agreement) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $agreement->tenant = DB::table('tenants')->where('id', $agreement->tenant_id)->first();
        $agreement->property = DB::table('properties')->where('id', $agreement->property_id)->first();
        $agreement->signatures = DB::table('agreement_signatures')->where('agreement_id', $id)->get();
        return response()->json(['success' => true, 'data' => $agreement]);
    }

    public function update(Request $request, $id)
    {
        $query = DB::table('rent_agreements')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $data = $request->only([
            'monthly_rent', 'security_deposit', 'maintenance_fee', 'start_date', 'end_date',
            'lock_in_months', 'notice_period_days', 'rent_due_day', 'additional_terms', 'house_rules',
            'electricity_included', 'water_included', 'food_included',
        ]);
        $data['updated_at'] = now();
        DB::table('rent_agreements')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function signAsOwner(Request $request, $id)
    {
        $user = $request->user();
        $query = DB::table('rent_agreements')->where('id', $id);
        $this->ownerScope($request, $query);
        $agreement = $query->first();
        if (!$agreement) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        DB::table('agreement_signatures')->insert([
            'agreement_id' => $id,
            'signer_type' => 'owner',
            'signer_name' => $user->name,
            'signer_id' => $user->id,
            'signature_data' => $request->signature_data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newStatus = $agreement->status === 'signed_tenant' ? 'active' : 'signed_owner';
        DB::table('rent_agreements')->where('id', $id)->update([
            'status' => $newStatus,
            'signed_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function signAsTenant(Request $request, $id)
    {
        $agreement = DB::table('rent_agreements')->where('id', $id)->first();
        if (!$agreement) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        DB::table('agreement_signatures')->insert([
            'agreement_id' => $id,
            'signer_type' => 'tenant',
            'signer_name' => $request->signer_name,
            'signer_id' => $request->signer_id,
            'signature_data' => $request->signature_data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newStatus = $agreement->status === 'signed_owner' ? 'active' : 'signed_tenant';
        DB::table('rent_agreements')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function terminate(Request $request, $id)
    {
        $query = DB::table('rent_agreements')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        DB::table('rent_agreements')->where('id', $id)->update([
            'status' => 'terminated',
            'terminated_at' => now(),
            'termination_reason' => $request->reason,
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $query = DB::table('rent_agreements')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('rent_agreements')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
