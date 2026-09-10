<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('tenants as t')
            ->leftJoin('properties as p', 't.property_id', '=', 'p.id')
            ->leftJoin('users as u', 't.owner_id', '=', 'u.id')
            ->select('t.*', 'p.name as property_name', 'u.name as owner_name');

        if ($request->status) $query->where('t.status', $request->status);
        if ($request->kyc) $query->where('t.kyc_status', $request->kyc);

        return response()->json(['success' => true, 'data' => $query->orderBy('t.created_at', 'desc')->get()]);
    }
}
