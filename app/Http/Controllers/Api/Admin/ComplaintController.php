<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('complaints as c')
            ->leftJoin('tenants as t', 'c.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'c.property_id', '=', 'p.id')
            ->leftJoin('users as u', 'c.owner_id', '=', 'u.id')
            ->select('c.*', 't.name as tenant_name', 'p.name as property_name', 'u.name as owner_name');

        if ($request->status) $query->where('c.status', $request->status);
        if ($request->priority) $query->where('c.priority', $request->priority);
        if ($request->category) $query->where('c.category', $request->category);

        return response()->json(['success' => true, 'data' => $query->orderBy('c.created_at', 'desc')->get()]);
    }
}
