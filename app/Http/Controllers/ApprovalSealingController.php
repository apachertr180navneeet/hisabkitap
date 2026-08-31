<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsoDailySeal;
use App\Models\AuditLog;
use App\Services\ReconciliationService;

class ApprovalSealingController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index()
    {
        $businessDate = $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        return view('approval.index', compact('metrics'));
    }

    public function sealDay(Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        if (!$metrics['isReconciled']) {
            return redirect()->back()->with('error', 'Cannot seal day while Reconciliation variance is non-zero or missing bills remain.');
        }

        $activeUser = session('active_user.name', 'Pooja Verma');
        $hashToken = 'SHA256:' . substr(hash('sha256', $businessDate . '_' . $metrics['psoCollection'] . '_' . time()), 0, 16);

        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if (!$seal) {
            $seal = new PsoDailySeal();
            $seal->business_date = $businessDate;
        }

        $seal->tally_total = $metrics['tallyTotal'];
        $seal->pso_total = $metrics['psoCollection'];
        $seal->difference = 0;
        $seal->is_reconciled = true;
        $seal->is_sealed = true;
        $seal->sealed_by = $activeUser;
        $seal->seal_hash = $hashToken;
        $seal->sealed_at = now();
        $seal->status = 'Sealed & Approved';
        $seal->remarks = "Digitally Sealed by {$activeUser} on " . now()->toFormattedDateString();
        $seal->save();

        AuditLog::log('SEAL_DAY', "PSO for date {$businessDate} was officially SEALED and LOCKED by {$activeUser} with Hash Token {$hashToken}");

        return redirect()->back()->with('success', "Daily Records for {$businessDate} have been officially SEALED with Digital Hash Token {$hashToken}. All records are now in Read-Only state.");
    }

    public function unsealDay(Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        $activeUser = session('active_user.name', 'Pooja Verma');
        $reason = $request->reason ?: 'Administrative correction requested';

        $seal = PsoDailySeal::where('business_date', $businessDate)->first();
        if ($seal) {
            $seal->is_sealed = false;
            $seal->unsealed_by = $activeUser;
            $seal->unseal_reason = $reason;
            $seal->unsealed_at = now();
            $seal->status = 'Unsealed';
            $seal->save();
        }

        AuditLog::log('UNSEAL_DAY', "Emergency unseal executed for date {$businessDate} by {$activeUser}. Reason: {$reason}");

        return redirect()->back()->with('success', "Records for {$businessDate} have been UNSEALED for emergency edits. Audit remark logged.");
    }
}
