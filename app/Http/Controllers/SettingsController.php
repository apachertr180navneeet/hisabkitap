<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('can_edit_cutoff')) {
            return redirect()->back()->with('error', 'Access Denied: You do not have permission to modify Cutoff Time and System Settings.');
        }

        $request->validate([
            'cutoff_time' => 'required|string',
        ]);

        $cutoffTime = $request->cutoff_time;
        $rollover = $request->has('cutoff_rollover_active') ? '1' : '0';

        SystemSetting::setVal('cutoff_time', $cutoffTime, 'Daily PSO Cutoff Time (24h IST)');
        SystemSetting::setVal('cutoff_rollover_active', $rollover, 'Automatic Next-Day PSO Rollover Toggle');

        AuditLog::log('SETTINGS_UPDATE', "Cutoff time updated to {$cutoffTime}, Rollover: ".($rollover === '1' ? 'Enabled' : 'Disabled'));

        return redirect()->back()->with('success', 'System Cutoff and Rollover policies updated successfully.');
    }
}
