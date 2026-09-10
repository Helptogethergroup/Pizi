<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
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
        $query = DB::table('complaints as c')
            ->leftJoin('tenants as t', 'c.tenant_id', '=', 't.id')
            ->leftJoin('properties as p', 'c.property_id', '=', 'p.id')
            ->select('c.*', 't.name as tenant_name', 'p.name as property_name');
        $this->ownerScope($request, $query, 'c');

        if ($request->status) $query->where('c.status', $request->status);
        if ($request->priority) $query->where('c.priority', $request->priority);
        if ($request->category) $query->where('c.category', $request->category);

        $complaints = $query->orderBy('c.created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $complaints]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'category' => 'required|in:plumbing,electrical,wifi,housekeeping,food,furniture,security,ac,water,other',
            'title' => 'required|string|max:200',
            'description' => 'sometimes|nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
        $ticketNumber = 'TKT-' . date('Ymd') . '-' . substr(uniqid(), -6);

        $id = DB::table('complaints')->insertGetId([
            'ticket_number' => $ticketNumber,
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'owner_id' => $user->id,
            'category' => $request->category,
            'priority' => $request->priority ?? 'medium',
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $id, 'ticket_number' => $ticketNumber]]);
    }

    public function show(Request $request, $id)
    {
        $query = DB::table('complaints')->where('id', $id);
        $this->ownerScope($request, $query);
        $complaint = $query->first();
        if (!$complaint) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $complaint->tenant = DB::table('tenants')->where('id', $complaint->tenant_id)->first();
        $complaint->property = DB::table('properties')->where('id', $complaint->property_id)->first();
        $complaint->media = DB::table('complaint_media')->where('complaint_id', $id)->get();
        $complaint->comments = DB::table('complaint_comments')->where('complaint_id', $id)->orderBy('created_at')->get();

        return response()->json(['success' => true, 'data' => $complaint]);
    }

    public function assign(Request $request, $id)
    {
        $query = DB::table('complaints')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        DB::table('complaints')->where('id', $id)->update([
            'assigned_to_name' => $request->assigned_to_name,
            'assigned_to_phone' => $request->assigned_to_phone,
            'assigned_at' => now(),
            'status' => 'assigned',
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function changeStatus(Request $request, $id)
    {
        $query = DB::table('complaints')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $data = ['status' => $request->status, 'updated_at' => now()];
        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
            $data['resolution_notes'] = $request->resolution_notes;
        }
        DB::table('complaints')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function changePriority(Request $request, $id)
    {
        $query = DB::table('complaints')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('complaints')->where('id', $id)->update(['priority' => $request->priority, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function addComment(Request $request, $id)
    {
        $user = $request->user();
        $commentId = DB::table('complaint_comments')->insertGetId([
            'complaint_id' => $id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'author_role' => $user->role,
            'comment' => $request->comment,
            'is_internal' => $request->is_internal ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'data' => ['id' => $commentId]]);
    }

    public function destroy(Request $request, $id)
    {
        $query = DB::table('complaints')->where('id', $id);
        $this->ownerScope($request, $query);
        if (!$query->first()) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        DB::table('complaints')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
