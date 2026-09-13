<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\PsoConfig;
use App\Models\Bill;
use App\Services\ReconciliationService;

class PsoSummaryController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        $businessDate = $request->query('date', $this->reconService->getBusinessDate());
        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $psoQuery->orderBy('code')->get();
        $metrics = $this->reconService->getMetrics($businessDate);

        $matrixRows = [];
        foreach ($psoConfigs as $pso) {
            $bills = Bill::whereDate('business_date', $businessDate)
                ->where('pso_code', $pso->code)
                ->where('is_post_cutoff', false)
                ->get();

            $gross = $bills->sum('amount');
            $cash = $bills->where('is_split_payment', true)->sum('cash_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
            $paytm = $bills->where('is_split_payment', true)->sum('paytm_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
            $check = $bills->where('payment_type', 'Check')->sum('net_amount');
            $credit = $bills->where('payment_type', 'Credit')->sum('net_amount');
            $cancelled = $bills->where('payment_type', 'Cancelled')->sum('amount');
            $cd = $bills->sum('cd_amount');
            $refund = $bills->sum('refund_amount');
            $net = $bills->where('status', '!=', 'Missing')->sum('net_amount');

            $matrixRows[] = [
                'pso' => $pso,
                'bills' => $bills,
                'billsCount' => $bills->count(),
                'gross' => $gross,
                'cash' => $cash,
                'paytm' => $paytm,
                'check' => $check,
                'credit' => $credit,
                'cancelled' => $cancelled,
                'cd' => $cd,
                'refund' => $refund,
                'net' => $net,
            ];
        }

        return view('summary.index', compact('matrixRows', 'metrics'));
    }

    public function show($id, Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        
        // Find PSO by ID or by Code
        $pso = is_numeric($id)
            ? PsoConfig::findOrFail($id)
            : PsoConfig::where('code', $id)->firstOrFail();

        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $allPsoConfigs = $psoQuery->orderBy('code')->get();

        $query = Bill::whereDate('business_date', $businessDate)
            ->where('pso_code', $pso->code)
            ->where('is_post_cutoff', false);

        if ($request->filled('payment_type') && $request->payment_type !== 'ALL') {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('salesman_name', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $bills = $query->orderBy('id', 'asc')->get();

        // Calculate single PSO statistics
        $allPsoBills = Bill::whereDate('business_date', $businessDate)
            ->where('pso_code', $pso->code)
            ->where('is_post_cutoff', false)
            ->get();

        $gross = $allPsoBills->sum('amount');
        $cash = $allPsoBills->where('is_split_payment', true)->sum('cash_amount') + $allPsoBills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
        $paytm = $allPsoBills->where('is_split_payment', true)->sum('paytm_amount') + $allPsoBills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
        $check = $allPsoBills->where('payment_type', 'Check')->sum('net_amount');
        $credit = $allPsoBills->where('payment_type', 'Credit')->sum('net_amount');
        $cancelled = $allPsoBills->where('payment_type', 'Cancelled')->sum('amount');
        $cd = $allPsoBills->sum('cd_amount');
        $refund = $allPsoBills->sum('refund_amount');
        $net = $allPsoBills->where('status', '!=', 'Missing')->sum('net_amount');

        $stats = [
            'totalBills' => $allPsoBills->count(),
            'matchedCount' => $allPsoBills->where('status', 'Matched')->count(),
            'missingCount' => $allPsoBills->where('status', 'Missing')->count(),
            'cancelledCount' => $allPsoBills->where('payment_type', 'Cancelled')->count(),
            'gross' => $gross,
            'cash' => $cash,
            'paytm' => $paytm,
            'check' => $check,
            'credit' => $credit,
            'cancelled' => $cancelled,
            'cd' => $cd,
            'refund' => $refund,
            'net' => $net,
        ];

        $globalMetrics = $this->reconService->getMetrics($businessDate);

        return view('summary.show', compact('pso', 'bills', 'stats', 'allPsoConfigs', 'businessDate', 'globalMetrics'));
    }

    /**
     * Export Full PSO Summary Matrix to CSV / Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $businessDate = $request->query('date', $this->reconService->getBusinessDate());
        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $psoQuery->orderBy('code')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"PSO_Summary_Matrix_{$businessDate}.csv\"",
        ];

        return response()->stream(function () use ($psoConfigs, $businessDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'PSO Code',
                'Prefix / Series',
                'Operator Name',
                'Driver Name',
                'Gadi No',
                'Total Bills',
                'Gross Sales (INR)',
                'Cash Collection (INR)',
                'Paytm / Digital (INR)',
                'Cheque / Bank (INR)',
                'Credit Amount (INR)',
                'Cancelled Amount (INR)',
                'Cash Discount CD (INR)',
                'Refund Amount (INR)',
                'Net Collection (INR)'
            ]);

            $totGross = 0; $totCash = 0; $totPaytm = 0; $totCheck = 0; $totCredit = 0;
            $totCancelled = 0; $totCd = 0; $totRefund = 0; $totNet = 0; $totBills = 0;

            foreach ($psoConfigs as $pso) {
                $bills = Bill::whereDate('business_date', $businessDate)
                    ->where('pso_code', $pso->code)
                    ->where('is_post_cutoff', false)
                    ->get();

                $gross = $bills->sum('amount');
                $cash = $bills->where('is_split_payment', true)->sum('cash_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
                $paytm = $bills->where('is_split_payment', true)->sum('paytm_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
                $check = $bills->where('payment_type', 'Check')->sum('net_amount') + $bills->where('payment_type', 'Cheque')->sum('net_amount');
                $credit = $bills->where('payment_type', 'Credit')->sum('net_amount');
                $cancelled = $bills->where('payment_type', 'Cancelled')->sum('amount');
                $cd = $bills->sum('cd_amount');
                $refund = $bills->sum('refund_amount');
                $net = $bills->where('status', '!=', 'Missing')->sum('net_amount');

                $totGross += $gross;
                $totCash += $cash;
                $totPaytm += $paytm;
                $totCheck += $check;
                $totCredit += $credit;
                $totCancelled += $cancelled;
                $totCd += $cd;
                $totRefund += $refund;
                $totNet += $net;
                $totBills += $bills->count();

                fputcsv($handle, [
                    $pso->code,
                    $pso->prefix . ' ' . $pso->start_no . '-' . $pso->end_no,
                    $pso->operator_name ?: '—',
                    $pso->driver_name ?: '—',
                    $pso->gadi_number ?: '—',
                    $bills->count(),
                    $gross,
                    $cash,
                    $paytm,
                    $check,
                    $credit,
                    $cancelled,
                    $cd,
                    $refund,
                    $net
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                'ALL PSO COUNTERS',
                '',
                '',
                '',
                $totBills,
                $totGross,
                $totCash,
                $totPaytm,
                $totCheck,
                $totCredit,
                $totCancelled,
                $totCd,
                $totRefund,
                $totNet
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print / Export PDF for Full PSO Summary Matrix
     */
    public function exportPdf(Request $request)
    {
        $businessDate = $request->query('date', $this->reconService->getBusinessDate());
        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $psoQuery->orderBy('code')->get();
        $metrics = $this->reconService->getMetrics($businessDate);

        $matrixRows = [];
        foreach ($psoConfigs as $pso) {
            $bills = Bill::whereDate('business_date', $businessDate)
                ->where('pso_code', $pso->code)
                ->where('is_post_cutoff', false)
                ->get();

            $gross = $bills->sum('amount');
            $cash = $bills->where('is_split_payment', true)->sum('cash_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
            $paytm = $bills->where('is_split_payment', true)->sum('paytm_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
            $check = $bills->where('payment_type', 'Check')->sum('net_amount') + $bills->where('payment_type', 'Cheque')->sum('net_amount');
            $credit = $bills->where('payment_type', 'Credit')->sum('net_amount');
            $cancelled = $bills->where('payment_type', 'Cancelled')->sum('amount');
            $cd = $bills->sum('cd_amount');
            $refund = $bills->sum('refund_amount');
            $net = $bills->where('status', '!=', 'Missing')->sum('net_amount');

            $matrixRows[] = [
                'pso' => $pso,
                'bills' => $bills,
                'billsCount' => $bills->count(),
                'gross' => $gross,
                'cash' => $cash,
                'paytm' => $paytm,
                'check' => $check,
                'credit' => $credit,
                'cancelled' => $cancelled,
                'cd' => $cd,
                'refund' => $refund,
                'net' => $net,
            ];
        }

        return view('summary.print', compact('matrixRows', 'metrics', 'businessDate'));
    }

    /**
     * Export Single PSO Detail Breakdown to CSV / Excel
     */
    public function exportSingleExcel($id, Request $request): StreamedResponse
    {
        $businessDate = $this->reconService->getBusinessDate();
        $pso = is_numeric($id)
            ? PsoConfig::findOrFail($id)
            : PsoConfig::where('code', $id)->firstOrFail();

        $bills = Bill::whereDate('business_date', $businessDate)
            ->where('pso_code', $pso->code)
            ->where('is_post_cutoff', false)
            ->orderBy('id', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"PSO_{$pso->code}_Details_{$businessDate}.csv\"",
        ];

        return response()->stream(function () use ($pso, $bills, $businessDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Bill No',
                'Date',
                'Customer Name',
                'Salesman',
                'Payment Type',
                'Gross Amount (INR)',
                'CD Amount (INR)',
                'Refund (INR)',
                'Net Amount (INR)',
                'Cash Portion (INR)',
                'Paytm Portion (INR)',
                'Verification Status'
            ]);

            $totGross = 0; $totCd = 0; $totRefund = 0; $totNet = 0;
            foreach ($bills as $b) {
                $calcNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
                $net = (float) ($b->net_amount > 0 ? $b->net_amount : $calcNet);
                $totGross += (float)$b->amount;
                $totCd += (float)$b->cd_amount;
                $totRefund += (float)$b->refund_amount;
                $totNet += $net;

                $cashPortion = 0;
                $paytmPortion = 0;
                if ($b->is_split_payment) {
                    $cashPortion = (float)$b->cash_amount;
                    $paytmPortion = (float)$b->paytm_amount;
                } elseif ($b->payment_type === 'Cash') {
                    $cashPortion = $net;
                } elseif ($b->payment_type === 'Paytm') {
                    $paytmPortion = $net;
                }

                fputcsv($handle, [
                    $b->bill_no,
                    $b->business_date ? date('d/m/Y', strtotime($b->business_date)) : '',
                    $b->customer_name,
                    $b->salesman_name ?: '—',
                    $b->is_split_payment ? 'Split (Cash+Paytm)' : $b->payment_type,
                    $b->amount,
                    $b->cd_amount,
                    $b->refund_amount,
                    $net,
                    $cashPortion,
                    $paytmPortion,
                    $b->status
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                count($bills) . ' Bills',
                '',
                '',
                '',
                $totGross,
                $totCd,
                $totRefund,
                $totNet,
                '',
                '',
                ''
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print / Export PDF for Single PSO Detail
     */
    public function exportSinglePdf($id, Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        $pso = is_numeric($id)
            ? PsoConfig::findOrFail($id)
            : PsoConfig::where('code', $id)->firstOrFail();

        $bills = Bill::whereDate('business_date', $businessDate)
            ->where('pso_code', $pso->code)
            ->where('is_post_cutoff', false)
            ->orderBy('id', 'asc')
            ->get();

        $gross = $bills->sum('amount');
        $cash = $bills->where('is_split_payment', true)->sum('cash_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
        $paytm = $bills->where('is_split_payment', true)->sum('paytm_amount') + $bills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
        $check = $bills->where('payment_type', 'Check')->sum('net_amount') + $bills->where('payment_type', 'Cheque')->sum('net_amount');
        $credit = $bills->where('payment_type', 'Credit')->sum('net_amount');
        $cancelled = $bills->where('payment_type', 'Cancelled')->sum('amount');
        $cd = $bills->sum('cd_amount');
        $refund = $bills->sum('refund_amount');
        $net = $bills->where('status', '!=', 'Missing')->sum('net_amount');

        $stats = [
            'totalBills' => $bills->count(),
            'matchedCount' => $bills->where('status', 'Matched')->count(),
            'missingCount' => $bills->where('status', 'Missing')->count(),
            'cancelledCount' => $bills->where('payment_type', 'Cancelled')->count(),
            'gross' => $gross,
            'cash' => $cash,
            'paytm' => $paytm,
            'check' => $check,
            'credit' => $credit,
            'cancelled' => $cancelled,
            'cd' => $cd,
            'refund' => $refund,
            'net' => $net,
        ];

        return view('summary.print_single', compact('pso', 'bills', 'stats', 'businessDate'));
    }
}
