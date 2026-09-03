<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FinancialYear;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Display System Settings (Cutoff & Financial Year).
     */
    public function index()
    {
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        $rolloverActive = (bool) SystemSetting::getVal('cutoff_rollover_active', '1');
        $businessDate = SystemSetting::getVal('business_date', '2026-08-14');

        $financialYears = collect();
        $activeFy = null;

        try {
            $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
            $activeFy = FinancialYear::getActive();
        } catch (\Throwable $e) {
            // In case table hasn't migrated yet
        }

        $activeFyName = $activeFy ? $activeFy->name : SystemSetting::getVal('financial_year', '2026-2027');
        $activeFyStart = $activeFy ? $activeFy->start_date->format('Y-m-d') : SystemSetting::getVal('financial_year_start', '2026-04-01');
        $activeFyEnd = $activeFy ? $activeFy->end_date->format('Y-m-d') : SystemSetting::getVal('financial_year_end', '2027-03-31');

        $currentUser = Auth::user() ?: \App\Models\User::first();

        return view('settings.index', compact(
            'currentUser',
            'cutoffTime',
            'rolloverActive',
            'businessDate',
            'financialYears',
            'activeFy',
            'activeFyName',
            'activeFyStart',
            'activeFyEnd'
        ));
    }

    /**
     * Update Cutoff & Rollover Settings.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('can_edit_cutoff')) {
            return redirect()->back()->with('error', 'Access Denied: You do not have permission to modify Cutoff Time and System Settings.');
        }

        $request->validate([
            'cutoff_time' => 'required|string',
            'financial_year_id' => 'nullable|exists:financial_years,id',
        ]);

        $cutoffTime = $request->cutoff_time;
        $rollover = $request->has('cutoff_rollover_active') ? '1' : '0';

        SystemSetting::setVal('cutoff_time', $cutoffTime, 'Daily PSO Cutoff Time (24h IST)');
        SystemSetting::setVal('cutoff_rollover_active', $rollover, 'Automatic Next-Day PSO Rollover Toggle');

        if ($request->filled('financial_year_id')) {
            FinancialYear::setActiveById($request->financial_year_id);
        }

        AuditLog::log('SETTINGS_UPDATE', "Cutoff time updated to {$cutoffTime}, Rollover: ".($rollover === '1' ? 'Enabled' : 'Disabled'));

        return redirect()->back()->with('success', 'System Cutoff and System Settings updated successfully.');
    }

    /**
     * Switch Active Financial Year.
     */
    public function setActiveFinancialYear(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('can_edit_cutoff')) {
            return redirect()->back()->with('error', 'Access Denied: You do not have permission to modify Financial Year settings.');
        }

        $request->validate([
            'financial_year_id' => 'required|exists:financial_years,id',
        ]);

        $activeFy = FinancialYear::setActiveById($request->financial_year_id);

        if ($activeFy) {
            AuditLog::log('FINANCIAL_YEAR_SWITCH', "Active financial year changed to {$activeFy->name} ({$activeFy->formatted_range})");
            return redirect()->back()->with('success', "Active Financial Year changed to {$activeFy->name} successfully.");
        }

        return redirect()->back()->with('error', 'Unable to activate the selected Financial Year.');
    }

    /**
     * Store a newly created Financial Year.
     */
    public function storeFinancialYear(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('can_edit_cutoff')) {
            return redirect()->back()->with('error', 'Access Denied: You do not have permission to create Financial Years.');
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:50|unique:financial_years,name',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'notes'         => 'nullable|string|max:255',
            'set_as_active' => 'nullable',
        ]);

        $isSetActive = $request->has('set_as_active');

        $fy = FinancialYear::create([
            'name'       => trim($validated['name']),
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_active'  => false,
            'is_locked'  => false,
            'notes'      => $validated['notes'] ?? null,
        ]);

        if ($isSetActive) {
            FinancialYear::setActiveById($fy->id);
        }

        AuditLog::log('FINANCIAL_YEAR_CREATE', "Created Financial Year {$fy->name} ({$fy->formatted_range})" . ($isSetActive ? " [Set as Active]" : ""));

        return redirect()->back()->with('success', "Financial Year '{$fy->name}' created successfully." . ($isSetActive ? " Marked as Active." : ""));
    }

    /**
     * Toggle lock status for a Financial Year.
     */
    public function toggleLockFinancialYear($id)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('can_edit_cutoff')) {
            return redirect()->back()->with('error', 'Access Denied: You do not have permission to lock/unlock Financial Years.');
        }

        $fy = FinancialYear::findOrFail($id);
        $fy->is_locked = !$fy->is_locked;
        $fy->save();

        $statusText = $fy->is_locked ? 'Locked (Protected from modifications)' : 'Unlocked (Open for transactions)';
        AuditLog::log('FINANCIAL_YEAR_LOCK', "Financial Year {$fy->name} set to {$statusText}");

        return redirect()->back()->with('success', "Financial Year '{$fy->name}' is now {$statusText}.");
    }
}
