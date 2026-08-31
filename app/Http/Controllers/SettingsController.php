<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\AuditLog;

class SettingsController extends Controller
{
    public function index()
    {
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        $rolloverActive = (bool) SystemSetting::getVal('cutoff_rollover_active', '1');
        $businessDate = SystemSetting::getVal('business_date', '2026-08-14');

        return view('settings.index', compact('cutoffTime', 'rolloverActive', 'businessDate'));
    }

    public function update(Request $request)
    {
        $userRole = session('active_user.role_code', 'OPERATOR');
        if ($userRole !== 'ADMIN') {
            return redirect()->back()->with('error', 'Access Denied: Only System Administrator (Suresh Gupta) has permission to modify Cutoff Time and System Settings.');
        }

        $request->validate([
            'cutoff_time' => 'required|string',
        ]);

        $cutoffTime = $request->cutoff_time;
        $rollover = $request->has('cutoff_rollover_active') ? '1' : '0';

        SystemSetting::setVal('cutoff_time', $cutoffTime, 'Daily PSO Cutoff Time (24h IST)');
        SystemSetting::setVal('cutoff_rollover_active', $rollover, 'Automatic Next-Day PSO Rollover Toggle');

        AuditLog::log('SETTINGS_UPDATE', "Cutoff time updated to {$cutoffTime}, Rollover: " . ($rollover === '1' ? 'Enabled' : 'Disabled'));

        return redirect()->back()->with('success', 'System Cutoff and Rollover policies updated successfully.');
    }
}
