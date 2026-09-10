<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|digits:10|unique:users,phone,' . $user->id,
            'address' => 'nullable|string|max:500',
            'upi_id' => 'nullable|string|max:100|regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/',
        ]);

        $user->update($data);
        
            \DB::table('user_activity_log')->insert([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => 'Updated profile details (name/email/phone/address)',
            'created_at' => now(),
        ]);

        return back()->with('success', '✓ Profile updated successfully.');
    }

public function updatePassword(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        $user->update(['password' => Hash::make($request->new_password)]);

        \DB::table('user_activity_log')->insert([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'description' => 'Changed account password',
            'created_at' => now(),
        ]);

        return back()->with('success', '✓ Password changed successfully.');
    }

    public function updatePhoto(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('success', '✓ Profile photo updated.');
    }
}