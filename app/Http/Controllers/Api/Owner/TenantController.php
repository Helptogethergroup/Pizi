<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    private function ownerScope(Request $request, $query)
    {
        if ($request->user()->role !== 'admin') {
            $query->where('owner_id', $request->user()->id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query = DB::table('tenants as t')
            ->leftJoin('properties as p', 't.property_id', '=', 'p.id')
            ->select('t.*', 'p.name as property_name');
        $this->ownerScope($request, $query);

        if ($request->status) $query->where('t.status', $request->status);
        if ($request->kyc) $query->where('t.kyc_status', $request->kyc);
        if ($request->property_id) $query->where('t.property_id', $request->property_id);
        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('t.name', 'like', '%' . $request->q . '%')
                  ->orWhere('t.phone', 'like', '%' . $request->q . '%');
            });
        }

        $tenants = $query->orderBy('t.created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $tenants]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer|exists:properties,id',
            'name' => 'required|string|max:200',
            'phone' => 'required|string|max:20',
            'email' => 'sometimes|nullable|email',
            'occupation' => 'sometimes|nullable|string',
            'room_number' => 'sometimes|nullable|string',
            'bed_number' => 'sometimes|nullable|string',
            'monthly_rent' => 'sometimes|nullable|numeric|min:0',
            'security_deposit' => 'sometimes|nullable|numeric|min:0',
            'move_in_date' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $id = DB::table('tenants')->insertGetId(array_merge($request->only([
            'property_id', 'name', 'phone', 'email', 'dob', 'gender', 'occupation', 'company_college',
            'address_line', 'city', 'state', 'pincode',
            'emergency_name', 'emergency_phone', 'emergency_relation',
            'room_number', 'bed_number', 'monthly_rent', 'security_deposit', 'move_in_date',
        ]), [
            'owner_id' => $user->id,
            'kyc_status' => 'pending',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    }
    
    
   
    public function show(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        $tenant = $query->first();
        if (!$tenant) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $tenant->property = DB::table('properties')->where('id', $tenant->property_id)->first();
        $tenant->documents = DB::table('tenant_documents')->where('tenant_id', $id)->get();
        $tenant->bills = DB::table('rent_bills')->where('tenant_id', $id)->orderBy('due_date', 'desc')->limit(12)->get();
        $tenant->complaints = DB::table('complaints')->where('tenant_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();

        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function update(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        $tenant = $query->first();
        if (!$tenant) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $data = $request->only([
            'name', 'phone', 'email', 'dob', 'gender', 'occupation', 'company_college',
            'address_line', 'city', 'state', 'pincode',
            'emergency_name', 'emergency_phone', 'emergency_relation',
            'room_number', 'bed_number', 'monthly_rent', 'security_deposit', 'move_in_date', 'move_out_date', 'notes',
        ]);
        $data['updated_at'] = now();
        DB::table('tenants')->where('id', $id)->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('tenants')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function approveKyc(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('tenants')->where('id', $id)->update(['kyc_status' => 'approved', 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function rejectKyc(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('tenants')->where('id', $id)->update([
            'kyc_status' => 'rejected',
            'kyc_remarks' => $request->remarks,
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function changeStatus(Request $request, $id)
    {
        $query = DB::table('tenants')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('tenants')->where('id', $id)->update(['status' => $request->status, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function uploadDocument(Request $request, $id)
    {
        if (!$request->hasFile('file')) return response()->json(['success' => false, 'message' => 'No file'], 422);

        $file = $request->file('file');
        $path = $file->store('tenant-documents', 'public');

        $docId = DB::table('tenant_documents')->insertGetId([
            'tenant_id' => $id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'document_number' => $request->document_number,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $docId, 'url' => url('storage/' . $path)]]);
    }

    public function deleteDocument($docId)
    {
        $doc = DB::table('tenant_documents')->where('id', $docId)->first();
        if (!$doc) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        \Storage::disk('public')->delete($doc->file_path);
        DB::table('tenant_documents')->where('id', $docId)->delete();
        return response()->json(['success' => true]);
    }
}
