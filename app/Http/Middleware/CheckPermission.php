<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request and check if authenticated user has required permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied: You do not have permission to perform this action.',
                ], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Access Denied: You do not have permission to access that section.');
        }

        return $next($request);
    }
}
