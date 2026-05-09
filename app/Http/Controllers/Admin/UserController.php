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
        if ($request->filled('role')) {
            $q->where('role', $request->role);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(fn ($qb) => $qb->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }
        $users = $q->latest()->paginate(25)->withQueryString();
        return view('admin.users', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'role' => 'required|in:telecaller,field_executive,owner,seo_manager,admin',
            'password' => 'required|string|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success', 'User created.');
    }

    public function toggle(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
        return back()->with('success', 'User status updated.');
    }
}
