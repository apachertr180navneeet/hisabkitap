<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\PsoDailySeal;
use App\Services\ReconciliationService;

class ShareUserRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If Auth is logged in, synchronize active_user
        if (auth()->check()) {
            session(['active_user' => auth()->user()->toArray()]);
        } elseif (!session()->has('active_user')) {
            $defaultUser = User::where('code', 'usr_admin')->first() ?: User::first();
            if ($defaultUser) {
                session(['active_user' => $defaultUser->toArray()]);
            }
        }

        $activeUser = session('active_user');
        $allUsers = User::all();
        $businessDate = SystemSetting::getVal('business_date', '2026-08-14');
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        $isSealed = $seal ? (bool) $seal->is_sealed : false;

        $reconService = app(ReconciliationService::class);
        $metrics = $reconService->getMetrics($businessDate);

        view()->share([
            'currentUser' => $activeUser,
            'allUsers' => $allUsers,
            'businessDate' => $businessDate,
            'cutoffTime' => $cutoffTime,
            'isSealed' => $isSealed,
            'sealInfo' => $seal,
            'globalMetrics' => $metrics,
        ]);

        return $next($request);
    }
}
