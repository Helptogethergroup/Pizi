<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('rent_bills as rb')
            ->leftJoin('tenants as t', 'rb.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'rb.property_id', '=', 'p.id')
            ->leftJoin('users as u', 'rb.owner_id', '=', 'u.id')
            ->select('rb.*', 't.name as tenant_name', 'p.name as property_name', 'u.name as owner_name');

        if ($request->status) $query->where('rb.status', $request->status);
        if ($request->month) $query->where('rb.month', $request->month);

        return response()->json(['success' => true, 'data' => $query->orderBy('rb.due_date', 'desc')->get()]);
    }
}
