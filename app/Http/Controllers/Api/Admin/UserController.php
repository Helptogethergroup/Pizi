<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users')->select('id', 'name', 'email', 'phone', 'role', 'is_active', 'avatar', 'created_at');

        if ($request->role) $query->where('role', $request->role);
        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('email', 'like', '%' . $request->q . '%')
                  ->orWhere('phone', 'like', '%' . $request->q . '%');
            });
        }

        return response()->json(['success' => true, 'data' => $query->orderBy('created_at', 'desc')->get()]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only(['name', 'phone', 'role', 'is_active']);
        $data['updated_at'] = now();
        DB::table('users')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        return response()->json(['success' => true]);
    }
}
