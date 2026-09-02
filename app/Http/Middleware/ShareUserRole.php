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
        $activeUser = auth()->user();
        if (!$activeUser) {
            $activeUser = User::where('code', 'usr_admin')->first() ?: User::first();
        }

        if ($activeUser) {
            session(['active_user' => $activeUser->toArray()]);
        }

        $allUsers = User::orderBy('id', 'asc')->get();
        $businessDate = SystemSetting::getVal('business_date', '2026-08-14');
        $formattedBusinessDate = date('d/m/Y', strtotime($businessDate));
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        $isSealed = $seal ? (bool) $seal->is_sealed : false;

        $reconService = app(ReconciliationService::class);
        $metrics = $reconService->getMetrics($businessDate);

        view()->share([
            'currentUser' => $activeUser,
            'allUsers' => $allUsers,
            'businessDate' => $businessDate,
            'formattedBusinessDate' => $formattedBusinessDate,
            'cutoffTime' => $cutoffTime,
            'isSealed' => $isSealed,
            'sealInfo' => $seal,
            'globalMetrics' => $metrics,
        ]);

        return $next($request);
    }
}
