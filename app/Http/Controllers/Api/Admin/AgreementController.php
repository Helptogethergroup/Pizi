<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('rent_agreements as a')
            ->leftJoin('tenants as t', 'a.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'a.property_id', '=', 'p.id')
            ->leftJoin('users as u', 'a.owner_id', '=', 'u.id')
            ->select('a.*', 't.name as tenant_name', 'p.name as property_name', 'u.name as owner_name');

        if ($request->status) $query->where('a.status', $request->status);

        return response()->json(['success' => true, 'data' => $query->orderBy('a.created_at', 'desc')->get()]);
    }
}
