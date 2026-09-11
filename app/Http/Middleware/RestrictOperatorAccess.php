<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictOperatorAccess
{
    /**
     * Allowed route names for PSO Operator.
     */
    protected array $allowedRouteNames = [
        'admin.dashboard',
        'admin.dashoard',
        'dashboard',
        'admin.pso.index',
        'pso.index',
        'admin.pso.create',
        'pso.create',
        'admin.pso.store',
        'pso.store',
        'admin.pso.close',
        'pso.close',
        'admin.profile',
        'profile',
        'admin.profile.update',
        'profile.update',
        'admin.profile.password',
        'profile.password',
        'admin.logout',
        'logout',
        'admin.logout.get',
        'logout.get',
        'admin.switch_user',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isOperator()) {
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            // If current route is not in allowed list, deny access
            if ($currentRoute && !in_array($currentRoute, $this->allowedRouteNames, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access Denied: PSO Operators only have access to Dashboard and PSO Management (Create and List View).',
                    ], 403);
                }

                return redirect()->route('admin.dashboard')->with('error', 'Access Denied: PSO Operators are restricted to Dashboard and PSO Management (Create & List View Only).');
            }
        }

        return $next($request);
    }
}
