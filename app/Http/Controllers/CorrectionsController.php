<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Correction;
use App\Services\ReconciliationService;
use Illuminate\Http\Request;

class CorrectionsController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index()
    {
        $businessDate = $this->reconService->getBusinessDate();
        $corrections = Correction::orderBy('id', 'desc')->get();
        $bills = Bill::whereDate('business_date', $businessDate)->where('is_post_cutoff', false)->get();

        $totCd = Correction::sum('cd_amount');
        $totReturn = Correction::sum('goods_return_amount');
        $totRefund = Correction::sum('refund_amount');
        $totNetAdj = Correction::sum('net_adjustment');

        return view('corrections.index', compact('corrections', 'bills', 'totCd', 'totReturn', 'totRefund', 'totNetAdj'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
            'correction_type' => 'required|string',
            'cd_amount' => 'nullable|numeric|min:0',
            'goods_return_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bill = Bill::where('bill_no', $request->bill_no)->whereDate('business_date', $businessDate)->firstOrFail();

        $cd = (float) ($request->cd_amount ?? 0);
        $returnAmt = (float) ($request->goods_return_amount ?? 0);
        $refund = (float) ($request->refund_amount ?? 0);
        $netAdj = -($cd + $returnAmt + $refund);

        $nextId = (Correction::max('id') ?? 0) + 1;
        $corrCode = 'CORR-'.sprintf('%02d', $nextId);

        $corr = Correction::create([
            'corr_code' => $corrCode,
            'bill_id' => $bill->id,
            'bill_no' => $bill->bill_no,
            'original_amount' => $bill->amount,
            'correction_type' => $request->correction_type,
            'cd_amount' => $cd,
            'goods_return_amount' => $returnAmt,
            'refund_amount' => $refund,
            'net_adjustment' => $netAdj,
            'reason' => $request->reason,
            'approved_by' => session('active_user.name', 'Pooja Verma'),
        ]);

        // Update the Bill deductions
        $bill->cd_amount = (float) $bill->cd_amount + $cd;
        $bill->refund_amount = (float) $bill->refund_amount + $refund + $returnAmt;
        $bill->net_amount = (float) $bill->amount - ($bill->cd_amount + $bill->refund_amount);
        $bill->save();

        AuditLog::log('CORRECTION_ADDED', "Recorded adjustment {$corrCode} for {$bill->bill_no}: Net deduction ₹".abs($netAdj)." ({$request->reason})");

        return redirect()->back()->with('success', "Adjustment {$corrCode} recorded successfully for bill {$bill->bill_no}.");
    }
}
