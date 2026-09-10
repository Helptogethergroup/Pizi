<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('leads as l')
            ->leftJoin('properties as p', 'l.property_id', '=', 'p.id')
            ->leftJoin('users as t', 'l.assigned_telecaller_id', '=', 't.id')
            ->select('l.*', 'p.name as property_name', 't.name as telecaller_name');

        if ($request->status) $query->where('l.status', $request->status);
        if ($request->source) $query->where('l.source', $request->source);

        return response()->json(['success' => true, 'data' => $query->orderBy('l.created_at', 'desc')->get()]);
    }

    public function assign(Request $request, $id)
    {
        $data = ['updated_at' => now()];
        if ($request->telecaller_id) $data['assigned_telecaller_id'] = $request->telecaller_id;
        if ($request->field_executive_id) $data['assigned_field_executive_id'] = $request->field_executive_id;
        DB::table('leads')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }
}
