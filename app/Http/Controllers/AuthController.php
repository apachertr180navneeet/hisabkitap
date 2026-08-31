<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $personas = User::all();
        return view('auth.login', compact('personas'));
    }

    /**
     * Handle login attempt with credentials
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();
            session(['active_user' => $user->toArray()]);

            AuditLog::log('USER_LOGIN', "User {$user->name} ({$user->role_name}) logged in successfully.");

            return redirect()->intended(route('dashboard'))->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our ERP records.',
        ])->onlyInput('email');
    }

    /**
     * Quick One-Click Persona Login (for instant testing/switching)
     */
    public function quickLogin(Request $request)
    {
        $code = $request->get('role_code', 'usr_01');
        $user = User::where('code', $code)->first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
            session(['active_user' => $user->toArray()]);

            AuditLog::log('USER_LOGIN_QUICK', "Quick Persona Login: {$user->name} ({$user->role_name})");

            return redirect()->route('dashboard')->with('success', "Logged in as {$user->name} ({$user->role_name}).");
        }

        return redirect()->route('login')->with('error', 'Selected persona not found.');
    }

    /**
     * Log the user out
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('USER_LOGOUT', "User {$user->name} logged out.");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out of the ERP system.');
    }
}
