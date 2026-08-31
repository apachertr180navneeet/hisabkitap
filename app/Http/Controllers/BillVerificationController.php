<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\PsoConfig;
use App\Models\AuditLog;
use App\Models\PsoDailySeal;
use App\Services\ReconciliationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillVerificationController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        $psoList = PsoConfig::where('is_active', true)->get();

        $query = Bill::where('business_date', $businessDate)
            ->where('is_post_cutoff', false);

        if ($request->filled('pso') && $request->pso !== 'ALL') {
            $query->where('pso_code', $request->pso);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type') && $request->payment_type !== 'ALL') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $bills = $query->orderBy('id', 'asc')->get();
        $metrics = $this->reconService->getMetrics($businessDate);

        return view('verification.index', compact('bills', 'psoList', 'metrics'));
    }

    public function resolveMissing(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
            'reason' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bill = Bill::where('bill_no', $request->bill_no)
            ->whereDate('business_date', $businessDate)
            ->firstOrFail();

        $bill->status = ($request->reason === 'Cancelled Bill') ? 'Cancelled' : 'Matched';
        $bill->remark = "Resolved: " . $request->reason . ($request->remark ? " - {$request->remark}" : "");
        $bill->verified_by = session('active_user.name', 'Pooja Verma');
        $bill->verified_at = now();
        $bill->save();

        // Update reconciliation state
        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::where('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('RESOLVE_MISSING', "Missing bill {$bill->bill_no} (₹{$bill->amount}) resolved via '{$request->reason}'. Status marked as {$bill->status}.");

        return response()->json([
            'success' => true,
            'message' => "Bill {$bill->bill_no} marked as {$bill->status}.",
            'metrics' => $metrics
        ]);
    }

    public function autoVerifyAll()
    {
        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::where('business_date', $businessDate)
            ->where('is_post_cutoff', false)
            ->where('status', '!=', 'Cancelled')
            ->get();

        foreach ($bills as $bill) {
            $bill->status = 'Matched';
            $bill->verified_by = session('active_user.name', 'Ramesh Sharma');
            $bill->verified_at = now();
            $bill->save();
        }

        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::where('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('AUTO_VERIFY', "Auto-verified all physical bill slips for date {$businessDate}.");

        return redirect()->back()->with('success', 'All bills successfully auto-verified against physical bundles.');
    }

    public function exportCsv(): StreamedResponse
    {
        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::where('business_date', $businessDate)->where('is_post_cutoff', false)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Bill_Verification_{$businessDate}.csv\"",
        ];

        return response()->stream(function () use ($bills) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bill No', 'PSO', 'Date', 'Time', 'Customer', 'Amount', 'Payment Type', 'CD', 'Refund', 'Net Amount', 'Status', 'Remark', 'Verified By']);
            foreach ($bills as $b) {
                fputcsv($handle, [
                    $b->bill_no,
                    $b->pso_code,
                    $b->business_date,
                    $b->bill_time,
                    $b->customer_name,
                    $b->amount,
                    $b->payment_type,
                    $b->cd_amount,
                    $b->refund_amount,
                    $b->net_amount,
                    $b->status,
                    $b->remark,
                    $b->verified_by
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
