<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckReadOnly
{
    /**
     * Block mutating requests for read-only users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isReadOnly() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return redirect()->back()->with('error', 'Access Denied: Your account is in Read-Only mode. Modification requests are disabled.');
        }

        return $next($request);
    }
}
