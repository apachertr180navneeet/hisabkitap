<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bill;
use App\Models\PsoDailySeal;
use App\Services\ReconciliationService;

class RoleSwitchController extends Controller
{
    public function switchRole(Request $request)
    {
        $code = $request->get('role_code', 'usr_01');
        $user = User::where('code', $code)->first();

        if ($user) {
            session(['active_user' => $user->toArray()]);
            return redirect()->back()->with('success', "Persona switched to {$user->name} ({$user->role_name}).");
        }

        return redirect()->back()->with('error', 'User persona not found.');
    }

    public function setScenario(Request $request)
    {
        $scenario = $request->get('scenario', 'discrepancy');
        $businessDate = SystemSetting::getVal('business_date', '2026-08-14');

        if ($scenario === 'discrepancy') {
            // Set CB 02 to Missing
            Bill::where('bill_no', 'CB 02')->where('business_date', $businessDate)->update([
                'status' => 'Missing',
                'remark' => 'Pending physical slip from counter 1'
            ]);
            // Unseal day
            PsoDailySeal::where('business_date', $businessDate)->update([
                'is_sealed' => false,
                'status' => 'Draft',
                'difference' => 17500,
                'is_reconciled' => false
            ]);
        } elseif ($scenario === 'balanced') {
            // Set all bills to Matched
            Bill::where('business_date', $businessDate)
                ->where('is_post_cutoff', false)
                ->where('status', '!=', 'Cancelled')
                ->update([
                    'status' => 'Matched',
                    'remark' => 'Physical slip verified'
                ]);
            // Update seal
            PsoDailySeal::where('business_date', $businessDate)->update([
                'difference' => 0,
                'is_reconciled' => true
            ]);
        } elseif ($scenario === 'missing_multiple') {
            Bill::where('bill_no', 'CB 02')->where('business_date', $businessDate)->update(['status' => 'Missing']);
            Bill::where('bill_no', 'CB 09')->where('business_date', $businessDate)->update(['status' => 'Missing', 'remark' => 'Cheque slip pending from sales desk']);
            PsoDailySeal::where('business_date', $businessDate)->update([
                'is_sealed' => false,
                'status' => 'Draft',
                'difference' => 33500,
                'is_reconciled' => false
            ]);
        }

        return redirect()->back()->with('success', "Demo scenario '{$scenario}' applied.");
    }
}
