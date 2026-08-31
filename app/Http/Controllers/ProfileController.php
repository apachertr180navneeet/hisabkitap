<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLog;

class ProfileController extends Controller
{
    /**
     * Display the user's profile and change password page
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            $userCode = session('active_user.code', 'usr_01');
            $user = User::where('code', $userCode)->first() ?: User::first();
        }

        return view('profile.index', compact('user'));
    }

    /**
     * Update profile details
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $userCode = session('active_user.code', 'usr_01');
            $user = User::where('code', $userCode)->first();
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        session(['active_user' => $user->toArray()]);
        AuditLog::log('PROFILE_UPDATE', "User {$user->name} updated their profile information.");

        return redirect()->route('admin.profile')->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $userCode = session('active_user.code', 'usr_01');
            $user = User::where('code', $userCode)->first();
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        AuditLog::log('PASSWORD_CHANGE', "User {$user->name} successfully changed their account password.");

        return redirect()->route('admin.profile')->with('success', 'Password updated successfully! Please use your new password next time.');
    }
}
