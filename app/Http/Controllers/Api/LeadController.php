<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    // Get all leads
    public function all()
    {
        try {
            $leads = DB::table('leads')
                ->leftJoin('properties', 'leads.property_id', '=', 'properties.id')
                ->select(
                    'leads.id',
                    'leads.name',
                    'leads.phone',
                    'leads.email',
                    'leads.status',
                    'leads.notes',
                    'leads.preferred_city',
                    'leads.budget_min',
                    'leads.budget_max',
                    'leads.created_at',
                    'properties.name as property_name'
                )
                ->orderBy('leads.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $leads
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get single lead for editing
public function edit($id)
    {
        try {
            $lead = DB::table('leads')->where('id', $id)->first();
            
            if (!$lead) {
                return response()->json(['success' => false, 'message' => 'Lead not found'], 404);
            }

            // PERMANENT check — owner ne credits kharch karke le liya hai
            if ($lead->is_locked && $lead->locked_by_user_id) {
                $ownerUser = DB::table('users')->where('id', $lead->locked_by_user_id)->first();
                if ($ownerUser && $ownerUser->role === 'owner') {
                    return response()->json([
                        'success' => false,
                        'locked' => true,
                        'message' => '🔒 This lead has been claimed by PG owner: ' . $ownerUser->name . '. Editing disabled.',
                    ], 423);
                }
            }

            $currentUserId = auth()->id();
            $lockExpired = !$lead->edit_locked_at || now()->diffInMinutes($lead->edit_locked_at) > 15;

            // TEMPORARY check — koi doosra admin/telecaller edit kar raha hai
            if ($lead->edit_locked_by && $lead->edit_locked_by != $currentUserId && !$lockExpired) {
                $lockedByUser = DB::table('users')->where('id', $lead->edit_locked_by)->first();
                return response()->json([
                    'success' => false,
                    'locked' => true,
                    'message' => '⚠️ Lead already opened by ' . ($lockedByUser->name ?? 'another user'),
                ], 423);
            }

            DB::table('leads')->where('id', $id)->update([
                'edit_locked_by' => $currentUserId,
                'edit_locked_at' => now(),
            ]);
            
            return response()->json(['success' => true, 'data' => $lead]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Update lead
    public function update(Request $request, $id)
    {
        try {
            // Check if lead exists
            $lead = DB::table('leads')->where('id', $id)->first();
            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            // Prepare update data - only update fields that are sent
            $updateData = [];
            
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('preferred_city')) {
                $updateData['preferred_city'] = $request->preferred_city;
            }
            if ($request->has('budget_min')) {
                $updateData['budget_min'] = $request->budget_min;
            }
            if ($request->has('budget_max')) {
                $updateData['budget_max'] = $request->budget_max;
            }
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }
            if ($request->has('inquiry_type') && in_array($request->inquiry_type, ['tenant', 'owner', 'unknown'])) {
                $updateData['inquiry_type'] = $request->inquiry_type;
            }

            $updateData['updated_at'] = now();

            // Update lead
            $updateData['edit_locked_by'] = null;
            $updateData['edit_locked_at'] = null;
            DB::table('leads')->where('id', $id)->update($updateData);

            // Get updated lead
            $updatedLead = DB::table('leads')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'data' => $updatedLead
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create lead
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'sometimes|nullable|email',
            'preferred_locality' => 'sometimes|nullable|string',
            'preferred_city' => 'sometimes|nullable|string',
            'preferred_gender' => 'sometimes|nullable|in:male,female,unisex',
            'budget_min' => 'sometimes|nullable|numeric',
            'budget_max' => 'sometimes|nullable|numeric',
            'move_in_date' => 'sometimes|nullable|date',
            'message' => 'sometimes|nullable|string',
            'property_id' => 'sometimes|nullable|integer|exists:properties,id',
            'source' => 'sometimes|nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $leadId = DB::table('leads')->insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'preferred_locality' => $request->preferred_locality,
            'preferred_city' => $request->preferred_city,
            'preferred_gender' => $request->preferred_gender,
            'budget_min' => $request->budget_min,
            'budget_max' => $request->budget_max,
            'move_in_date' => $request->move_in_date,
            'message' => $request->message,
            'property_id' => $request->property_id,
            'source' => $request->source ?? 'website',
            'status' => 'new',
            'lead_type' => 'direct',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        if ($request->property_id) {
            DB::table('properties')->where('id', $request->property_id)->increment('lead_count');
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Lead submitted successfully!',
            'data' => ['id' => $leadId]
        ]);
    }

    // Delete lead
    public function destroy($id)
    {
        try {
            $lead = DB::table('leads')->where('id', $id)->first();
            
            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            DB::table('leads')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lead deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    
    /**
 * Add remark to lead
 */
public function addRemark(Request $request, $id)
{
    try {
        $request->validate([
            'remark' => 'required|string|max:1000',
            'user_type' => 'required|in:admin,telecaller'
        ]);

        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Lead not found'], 404);
        }

        DB::table('lead_remarks')->insert([
            'lead_id' => $id,
            'user_id' => auth()->id() ?? 1,
            'user_type' => $request->user_type,
            'remark' => $request->remark,
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Remark added!'
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

/**
 * Get remarks for lead
 */
public function getRemarks($id)
{
    try {
        $remarks = DB::table('lead_remarks')
            ->where('lead_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $remarks
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

public function releaseLock($id)
    {
        DB::table('leads')->where('id', $id)->update([
            'edit_locked_by' => null,
            'edit_locked_at' => null,
        ]);
        return response()->json(['success' => true]);
    }


}
?>