<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
   public function index(Request $request)
    {
        $q = User::query();
        if ($request->role === 'others') {
            $q->whereNotIn('role', ['owner', 'tenant']);
        } elseif ($request->filled('role')) {
            $q->where('role', $request->role);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn ($qb) => $qb->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }
        $users = $q->withCount(['properties'])->latest()->paginate(25)->withQueryString();

        // ===== Role-wise counts for summary cards =====
        $roleCounts = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $totalUsers = User::count();

        return view('admin.users', compact('users', 'roleCounts', 'totalUsers'));
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'role' => 'required|in:telecaller,field_executive,owner,tenant,seo_manager,admin',
            'password' => 'required|string|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        if ($data['role'] === 'owner') {
            // Admin-created owner — free access, no payment paywall.
            $data['signup_type'] = 'free';
        }
        User::create($data);
        return back()->with('success', 'User created.');
    }

    public function toggle(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
        return back()->with('success', 'User status updated.');
    }

    /**
     * Marks/unmarks an owner as an internal QA/testing account — see the
     * note on LeadMatchingService::leadsForOwner(). Use this for any
     * dummy "testuser"-style owner account used to check the unlock flow,
     * so its unlocks don't permanently remove leads from real owners.
     */
    public function toggleTestAccount(User $user)
    {
        $user->is_test_account = !$user->is_test_account;
        $user->save();
        return back()->with('success', $user->is_test_account
            ? "🧪 {$user->name} marked as a test account — their lead unlocks no longer block real owners."
            : "{$user->name} is no longer marked as a test account.");
    }
    
    
    public function show(User $user)
    {
        $activity = \DB::table('user_activity_log')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $changeCount = $activity->count();

        return view('admin.users-detail', compact('user', 'activity', 'changeCount'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:15',
            'role' => 'required|in:telecaller,field_executive,owner,seo_manager,admin,tenant',
        ]);

        $user->update($data);

        return back()->with('success', '✓ User details updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->update(['password' => Hash::make($data['new_password'])]);

        return back()->with('success', "✓ Password reset for {$user->name}. Share the new password with them securely — admins cannot view existing passwords, only set new ones.");
    }
    
    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $propertyCount = \App\Models\Property::where('owner_id', $user->id)->count();
        $tenantCount = \App\Models\Tenant::where('owner_id', $user->id)
            ->orWhere('user_id', $user->id)
            ->count();

        if (($propertyCount > 0 || $tenantCount > 0) && !$request->boolean('force')) {
            return back()->with('error', "Cannot delete: this user has {$propertyCount} propert(y/ies) and {$tenantCount} tenant record(s) linked. Use Force Delete to unlink and remove, or reassign them first.");
        }

        if ($request->boolean('force')) {
            \App\Models\Property::where('owner_id', $user->id)->update(['owner_id' => null]);
            \App\Models\Tenant::where('owner_id', $user->id)->update(['owner_id' => null]);
            \App\Models\Tenant::where('user_id', $user->id)->update(['user_id' => null]);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "✓ User '{$name}' permanently deleted from the database.");
    }
    
    
    public function loginActivity(Request $request)
    {
        $q = \DB::table('user_activity_log')
            ->join('users', 'users.id', '=', 'user_activity_log.user_id')
            ->where('user_activity_log.action', 'login')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.role',
                \DB::raw('COUNT(*) as login_count'),
                \DB::raw('MAX(user_activity_log.created_at) as last_login')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.role');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn ($qb) => $qb->where('users.name', 'like', $term)->orWhere('users.email', 'like', $term));
        }

        $activity = $q->orderByDesc('login_count')->paginate(25)->withQueryString();

        return view('admin.login-activity', compact('activity'));
    }
}
