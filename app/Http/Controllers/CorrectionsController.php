<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Correction;
use App\Services\ReconciliationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorrectionsController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    /**
     * Get unified list of adjustments (both explicit corrections and bill CD/refunds/cancellations)
     */
    protected function getUnifiedCorrections(?string $businessDate = null)
    {
        // 1. Fetch explicit correction records
        $corrQuery = Correction::with('bill')->orderBy('id', 'desc');
        if ($businessDate && $businessDate !== 'ALL') {
            $corrQuery->where(function ($q) use ($businessDate) {
                $q->whereDate('created_at', $businessDate)
                  ->orWhereHas('bill', fn($bq) => $bq->whereDate('business_date', $businessDate));
            });
        }
        $explicitCorrections = $corrQuery->get();
        $handledBillIds = $explicitCorrections->pluck('bill_id')->filter()->toArray();

        // 2. Fetch bills that have CD, Refund, or Cancelled status not already in explicit corrections
        $billQuery = Bill::query();
        if ($businessDate && $businessDate !== 'ALL') {
            $billQuery->whereDate('business_date', $businessDate);
        }
        $billQuery->where(function ($q) {
            $q->where('cd_amount', '>', 0)
              ->orWhere('refund_amount', '>', 0)
              ->orWhere('payment_type', 'Cancelled')
              ->orWhere('status', 'Cancelled');
        });

        if (!empty($handledBillIds)) {
            $billQuery->whereNotIn('id', $handledBillIds);
        }

        $billsWithDeductions = $billQuery->orderBy('id', 'desc')->get();

        // Transform into uniform objects
        $unified = collect();

        foreach ($explicitCorrections as $c) {
            $unified->push((object)[
                'id' => $c->id,
                'corr_code' => $c->corr_code,
                'bill_id' => $c->bill_id,
                'bill_no' => $c->bill_no,
                'customer_name' => $c->bill?->customer_name ?? '—',
                'pso_code' => $c->bill?->pso_code ?? '—',
                'original_amount' => (float)$c->original_amount,
                'correction_type' => $c->correction_type,
                'cd_amount' => (float)$c->cd_amount,
                'goods_return_amount' => (float)$c->goods_return_amount,
                'refund_amount' => (float)$c->refund_amount,
                'net_adjustment' => (float)$c->net_adjustment,
                'reason' => $c->reason,
                'approved_by' => $c->approved_by,
                'created_at' => $c->created_at,
                'is_auto' => false,
            ]);
        }

        foreach ($billsWithDeductions as $b) {
            $isCancelled = ($b->payment_type === 'Cancelled' || $b->status === 'Cancelled');
            $cd = (float)$b->cd_amount;
            $refund = $isCancelled ? (float)$b->amount : (float)$b->refund_amount;
            $netAdj = -($cd + $refund);
            $type = $isCancelled ? 'Cancelled Bill / Full Return' : ($cd > 0 && $refund > 0 ? 'Cash Discount & Goods Return' : ($cd > 0 ? 'Cash Discount (CD)' : 'Goods Return / Refund'));

            $unified->push((object)[
                'id' => $b->id,
                'corr_code' => 'ADJ-' . sprintf('%03d', $b->id),
                'bill_id' => $b->id,
                'bill_no' => $b->bill_no,
                'customer_name' => $b->customer_name,
                'pso_code' => $b->pso_code,
                'original_amount' => (float)$b->amount,
                'correction_type' => $type,
                'cd_amount' => $cd,
                'goods_return_amount' => $refund,
                'refund_amount' => $refund,
                'net_adjustment' => $netAdj,
                'reason' => $b->remark ?: ($isCancelled ? 'Bill marked Cancelled & Refunded' : 'Recorded during bill verification'),
                'approved_by' => $b->verified_by ?: ($b->mismatch_approved_by ?: 'System Verified'),
                'created_at' => $b->created_at ?: now(),
                'is_auto' => true,
            ]);
        }

        return $unified;
    }

    /**
     * Get available business dates for filtering
     */
    protected function getAvailableDates(): array
    {
        $dates = Bill::selectRaw('DISTINCT business_date')->whereNotNull('business_date')->pluck('business_date')->toArray();
        $all = array_unique(array_filter($dates));
        rsort($all);
        return array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : substr((string)$d, 0, 10)), $all);
    }

    public function index(Request $request)
    {
        $availableDates = $this->getAvailableDates();
        $defaultDate = $this->reconService->getBusinessDate();
        
        $dateParam = $request->query('date');
        if ($dateParam === 'ALL') {
            $selectedDate = 'ALL';
        } elseif ($dateParam) {
            $selectedDate = $dateParam;
        } else {
            // Default to ALL or active date if bills exist for today
            $selectedDate = 'ALL';
        }

        $corrections = $this->getUnifiedCorrections($selectedDate);
        $bills = Bill::orderBy('id', 'desc')->take(100)->get();

        $totCd = (float)$corrections->sum('cd_amount');
        $totReturn = (float)$corrections->sum('goods_return_amount');
        $totRefund = (float)$corrections->sum('refund_amount');
        $totNetAdj = (float)$corrections->sum('net_adjustment');

        return view('corrections.index', compact('corrections', 'bills', 'totCd', 'totReturn', 'totRefund', 'totNetAdj', 'selectedDate', 'availableDates', 'defaultDate'));
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

        $billNo = trim($request->bill_no);
        $businessDate = $this->reconService->getBusinessDate();
        
        $bill = Bill::where('bill_no', $billNo)->whereDate('business_date', $businessDate)->first()
             ?? Bill::where('bill_no', $billNo)->latest('business_date')->first();

        if (!$bill) {
            return redirect()->back()->withInput()->with('error', "Bill '{$billNo}' was not found in the database. Please check the Bill Number.");
        }

        $cd = (float) ($request->cd_amount ?? 0);
        $returnAmt = (float) ($request->goods_return_amount ?? 0);
        $refund = (float) ($request->refund_amount ?? 0);
        $netAdj = -($cd + $returnAmt + $refund);

        $nextId = (Correction::max('id') ?? 0) + 1;
        $corrCode = 'CORR-'.sprintf('%03d', $nextId);

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
            'approved_by' => session('active_user.name', auth()->user()?->name ?? 'Pooja Verma'),
        ]);

        // Update the Bill deductions
        $bill->cd_amount = (float) $bill->cd_amount + $cd;
        $bill->refund_amount = (float) $bill->refund_amount + $refund + $returnAmt;
        $bill->net_amount = max(0, (float) $bill->amount - ($bill->cd_amount + $bill->refund_amount));
        $bill->save();

        AuditLog::log('CORRECTION_ADDED', "Recorded adjustment {$corrCode} for {$bill->bill_no}: Net deduction ₹".abs($netAdj)." ({$request->reason})");

        return redirect()->back()->with('success', "Adjustment {$corrCode} recorded successfully for bill {$bill->bill_no}.");
    }

    /**
     * Export Corrections to CSV / Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $selectedDate = $request->query('date', 'ALL');
        $corrections = $this->getUnifiedCorrections($selectedDate);
        $dateLabel = $selectedDate === 'ALL' ? 'All_Dates' : $selectedDate;

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Corrections_and_Returns_{$dateLabel}.csv\"",
        ];

        return response()->stream(function () use ($corrections) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Correction Code',
                'Bill No',
                'Customer Name',
                'PSO Code',
                'Original Bill Amount (INR)',
                'Correction Type',
                'Cash Discount CD (INR)',
                'Goods Return (INR)',
                'Refund Amount (INR)',
                'Net Adjustment (INR)',
                'Reason / Remarks',
                'Approved / Recorded By',
                'Date & Time'
            ]);

            $totOriginal = 0; $totCd = 0; $totReturn = 0; $totRefund = 0; $totNetAdj = 0;

            foreach ($corrections as $c) {
                $totOriginal += (float)$c->original_amount;
                $totCd += (float)$c->cd_amount;
                $totReturn += (float)$c->goods_return_amount;
                $totRefund += (float)$c->refund_amount;
                $totNetAdj += (float)$c->net_adjustment;
                $cDate = $c->created_at ? (is_string($c->created_at) ? $c->created_at : $c->created_at->format('d/m/Y H:i')) : '';

                fputcsv($handle, [
                    $c->corr_code,
                    $c->bill_no,
                    $c->customer_name,
                    $c->pso_code,
                    $c->original_amount,
                    $c->correction_type,
                    $c->cd_amount,
                    $c->goods_return_amount,
                    $c->refund_amount,
                    $c->net_adjustment,
                    $c->reason,
                    $c->approved_by,
                    $cDate
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                count($corrections) . ' Entries',
                '',
                '',
                $totOriginal,
                '',
                $totCd,
                $totReturn,
                $totRefund,
                $totNetAdj,
                '',
                '',
                ''
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print / Export PDF for Corrections & Returns
     */
    public function exportPdf(Request $request)
    {
        $selectedDate = $request->query('date', 'ALL');
        $corrections = $this->getUnifiedCorrections($selectedDate);
        $businessDate = $selectedDate === 'ALL' ? 'All Dates' : $selectedDate;

        $totCd = (float)$corrections->sum('cd_amount');
        $totReturn = (float)$corrections->sum('goods_return_amount');
        $totRefund = (float)$corrections->sum('refund_amount');
        $totNetAdj = (float)$corrections->sum('net_adjustment');

        return view('corrections.print', compact('corrections', 'businessDate', 'totCd', 'totReturn', 'totRefund', 'totNetAdj'));
    }
}

