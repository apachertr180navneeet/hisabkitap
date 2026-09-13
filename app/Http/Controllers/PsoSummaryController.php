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

    /**
     * Resolve active business date with fallback to latest bill date
     */
    protected function resolveBusinessDate(Request $request): string
    {
        $businessDate = $request->query('date') ?: $request->input('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            if (Bill::whereDate('business_date', $defaultDate)->exists()) {
                $businessDate = $defaultDate;
            } else {
                $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
                $businessDate = $latestBillDate ? (is_string($latestBillDate) ? substr($latestBillDate, 0, 10) : $latestBillDate->format('Y-m-d')) : $defaultDate;
            }
        }
        return $businessDate;
    }

    /**
     * Get list of all available business dates in the database
     */
    protected function getAvailableDates(): array
    {
        $billDates = Bill::selectRaw('DISTINCT business_date')->whereNotNull('business_date')->pluck('business_date')->toArray();
        $allDates = array_unique(array_filter($billDates));
        rsort($allDates);
        return array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : substr((string)$d, 0, 10)), $allDates);
    }

    /**
     * Build query for bills belonging to a PSO configuration
     */
    protected function getPsoBillsQuery(PsoConfig $pso, ?string $businessDate = null)
    {
        $query = Bill::query();

        if ($businessDate && $businessDate !== 'ALL') {
            $query->whereDate('business_date', $businessDate);
        }

        $query->where(function ($q) use ($pso) {
            $q->where('pso_config_id', $pso->id)
              ->orWhere('pso_code', $pso->code);

            // Match by prefix and series ranges
            $ranges = $pso->getAllSeriesRanges();
            foreach ($ranges as $range) {
                $prefix = trim($range['prefix'] ?? $pso->prefix ?? '');
                if (!empty($prefix)) {
                    $q->orWhere('bill_no', 'like', $prefix . '%')
                      ->orWhere('bill_no', 'like', $prefix . ' %');
                }
            }
        });

        $query->where(function ($q) {
            $q->where('is_post_cutoff', false)
              ->orWhereNull('is_post_cutoff');
        });

        return $query;
    }

    /**
     * Compute statistics for a collection of bills
     */
    protected function computeBillStats($bills): array
    {
        $gross = (float) $bills->sum('amount');
        $cash = (float) $bills->where('is_split_payment', true)->sum('cash_amount') 
              + (float) $bills->where('is_split_payment', false)->where('payment_type', 'Cash')->sum('net_amount');
        $paytm = (float) $bills->where('is_split_payment', true)->sum('paytm_amount') 
               + (float) $bills->where('is_split_payment', false)->where('payment_type', 'Paytm')->sum('net_amount');
        $check = (float) $bills->whereIn('payment_type', ['Check', 'Cheque'])->sum('net_amount');
        $credit = (float) $bills->where('payment_type', 'Credit')->sum('net_amount');
        $cancelled = (float) $bills->where('payment_type', 'Cancelled')->sum('amount');
        $cd = (float) $bills->sum('cd_amount');
        $refund = (float) $bills->sum('refund_amount');

        $net = 0;
        foreach ($bills as $b) {
            if ($b->status !== 'Missing' && $b->payment_type !== 'Cancelled') {
                $calcNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
                $net += (float) ($b->net_amount > 0 ? $b->net_amount : $calcNet);
            }
        }

        return [
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

    public function index(Request $request)
    {
        $businessDate = $this->resolveBusinessDate($request);
        $availableDates = $this->getAvailableDates();

        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $psoQuery->orderBy('code')->get();
        $metrics = $this->reconService->getMetrics($businessDate === 'ALL' ? null : $businessDate);

        $matrixRows = [];
        $masterTotalGross = 0; $masterTotalCash = 0; $masterTotalPaytm = 0;
        $masterTotalCheck = 0; $masterTotalCredit = 0; $masterTotalCancelled = 0;
        $masterTotalCd = 0; $masterTotalRefund = 0; $masterTotalNet = 0; $masterTotalBills = 0;

        foreach ($psoConfigs as $pso) {
            $bills = $this->getPsoBillsQuery($pso, $businessDate)->get();
            $stats = $this->computeBillStats($bills);

            $masterTotalGross += $stats['gross'];
            $masterTotalCash += $stats['cash'];
            $masterTotalPaytm += $stats['paytm'];
            $masterTotalCheck += $stats['check'];
            $masterTotalCredit += $stats['credit'];
            $masterTotalCancelled += $stats['cancelled'];
            $masterTotalCd += $stats['cd'];
            $masterTotalRefund += $stats['refund'];
            $masterTotalNet += $stats['net'];
            $masterTotalBills += $bills->count();

            $matrixRows[] = [
                'pso' => $pso,
                'bills' => $bills,
                'billsCount' => $bills->count(),
                'gross' => $stats['gross'],
                'cash' => $stats['cash'],
                'paytm' => $stats['paytm'],
                'check' => $stats['check'],
                'credit' => $stats['credit'],
                'cancelled' => $stats['cancelled'],
                'cd' => $stats['cd'],
                'refund' => $stats['refund'],
                'net' => $stats['net'],
            ];
        }

        // Update metrics aggregates from matrix rows if calculated
        if ($masterTotalBills > 0 || empty($metrics['totalBillsCount'])) {
            $metrics['totalBillsCount'] = $masterTotalBills;
            $metrics['tallyTotal'] = $masterTotalGross;
            $metrics['totCash'] = $masterTotalCash;
            $metrics['totPaytm'] = $masterTotalPaytm;
            $metrics['totCheck'] = $masterTotalCheck;
            $metrics['totCredit'] = $masterTotalCredit;
            $metrics['totCancelled'] = $masterTotalCancelled;
            $metrics['totCd'] = $masterTotalCd;
            $metrics['totRefund'] = $masterTotalRefund;
            $metrics['psoCollection'] = $masterTotalNet;
        }

        return view('summary.index', compact('matrixRows', 'metrics', 'businessDate', 'availableDates'));
    }

    public function show($id, Request $request)
    {
        $businessDate = $this->resolveBusinessDate($request);
        $availableDates = $this->getAvailableDates();
        
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

        $query = $this->getPsoBillsQuery($pso, $businessDate);

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

        // Calculate single PSO statistics across all bills for this date
        $allPsoBills = $this->getPsoBillsQuery($pso, $businessDate)->get();
        $statsCalculated = $this->computeBillStats($allPsoBills);

        $stats = [
            'totalBills' => $allPsoBills->count(),
            'matchedCount' => $allPsoBills->where('status', 'Matched')->count(),
            'missingCount' => $allPsoBills->where('status', 'Missing')->count(),
            'cancelledCount' => $allPsoBills->where('payment_type', 'Cancelled')->count(),
            'gross' => $statsCalculated['gross'],
            'cash' => $statsCalculated['cash'],
            'paytm' => $statsCalculated['paytm'],
            'check' => $statsCalculated['check'],
            'credit' => $statsCalculated['credit'],
            'cancelled' => $statsCalculated['cancelled'],
            'cd' => $statsCalculated['cd'],
            'refund' => $statsCalculated['refund'],
            'net' => $statsCalculated['net'],
        ];

        $globalMetrics = $this->reconService->getMetrics($businessDate === 'ALL' ? null : $businessDate);

        return view('summary.show', compact('pso', 'bills', 'stats', 'allPsoConfigs', 'businessDate', 'availableDates', 'globalMetrics'));
    }

    /**
     * Export Full PSO Summary Matrix to CSV / Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $businessDate = $this->resolveBusinessDate($request);
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
                $bills = $this->getPsoBillsQuery($pso, $businessDate)->get();
                $stats = $this->computeBillStats($bills);

                $totGross += $stats['gross'];
                $totCash += $stats['cash'];
                $totPaytm += $stats['paytm'];
                $totCheck += $stats['check'];
                $totCredit += $stats['credit'];
                $totCancelled += $stats['cancelled'];
                $totCd += $stats['cd'];
                $totRefund += $stats['refund'];
                $totNet += $stats['net'];
                $totBills += $bills->count();

                fputcsv($handle, [
                    $pso->code,
                    $pso->prefix . ' ' . $pso->start_no . '-' . $pso->end_no,
                    $pso->operator_name ?: '—',
                    $pso->driver_name ?: '—',
                    $pso->gadi_number ?: '—',
                    $bills->count(),
                    $stats['gross'],
                    $stats['cash'],
                    $stats['paytm'],
                    $stats['check'],
                    $stats['credit'],
                    $stats['cancelled'],
                    $stats['cd'],
                    $stats['refund'],
                    $stats['net']
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
        $businessDate = $this->resolveBusinessDate($request);
        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $psoQuery->orderBy('code')->get();
        $metrics = $this->reconService->getMetrics($businessDate === 'ALL' ? null : $businessDate);

        $matrixRows = [];
        $masterTotalGross = 0; $masterTotalCash = 0; $masterTotalPaytm = 0;
        $masterTotalCheck = 0; $masterTotalCredit = 0; $masterTotalCancelled = 0;
        $masterTotalCd = 0; $masterTotalRefund = 0; $masterTotalNet = 0; $masterTotalBills = 0;

        foreach ($psoConfigs as $pso) {
            $bills = $this->getPsoBillsQuery($pso, $businessDate)->get();
            $stats = $this->computeBillStats($bills);

            $masterTotalGross += $stats['gross'];
            $masterTotalCash += $stats['cash'];
            $masterTotalPaytm += $stats['paytm'];
            $masterTotalCheck += $stats['check'];
            $masterTotalCredit += $stats['credit'];
            $masterTotalCancelled += $stats['cancelled'];
            $masterTotalCd += $stats['cd'];
            $masterTotalRefund += $stats['refund'];
            $masterTotalNet += $stats['net'];
            $masterTotalBills += $bills->count();

            $matrixRows[] = [
                'pso' => $pso,
                'bills' => $bills,
                'billsCount' => $bills->count(),
                'gross' => $stats['gross'],
                'cash' => $stats['cash'],
                'paytm' => $stats['paytm'],
                'check' => $stats['check'],
                'credit' => $stats['credit'],
                'cancelled' => $stats['cancelled'],
                'cd' => $stats['cd'],
                'refund' => $stats['refund'],
                'net' => $stats['net'],
            ];
        }

        if ($masterTotalBills > 0 || empty($metrics['totalBillsCount'])) {
            $metrics['totalBillsCount'] = $masterTotalBills;
            $metrics['tallyTotal'] = $masterTotalGross;
            $metrics['totCash'] = $masterTotalCash;
            $metrics['totPaytm'] = $masterTotalPaytm;
            $metrics['totCheck'] = $masterTotalCheck;
            $metrics['totCredit'] = $masterTotalCredit;
            $metrics['totCancelled'] = $masterTotalCancelled;
            $metrics['totCd'] = $masterTotalCd;
            $metrics['totRefund'] = $masterTotalRefund;
            $metrics['psoCollection'] = $masterTotalNet;
        }

        return view('summary.print', compact('matrixRows', 'metrics', 'businessDate'));
    }

    /**
     * Export Single PSO Detail Breakdown to CSV / Excel
     */
    public function exportSingleExcel($id, Request $request): StreamedResponse
    {
        $businessDate = $this->resolveBusinessDate($request);
        $pso = is_numeric($id)
            ? PsoConfig::findOrFail($id)
            : PsoConfig::where('code', $id)->firstOrFail();

        $bills = $this->getPsoBillsQuery($pso, $businessDate)
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
        $businessDate = $this->resolveBusinessDate($request);
        $pso = is_numeric($id)
            ? PsoConfig::findOrFail($id)
            : PsoConfig::where('code', $id)->firstOrFail();

        $bills = $this->getPsoBillsQuery($pso, $businessDate)
            ->orderBy('id', 'asc')
            ->get();

        $statsCalculated = $this->computeBillStats($bills);

        $stats = [
            'totalBills' => $bills->count(),
            'matchedCount' => $bills->where('status', 'Matched')->count(),
            'missingCount' => $bills->where('status', 'Missing')->count(),
            'cancelledCount' => $bills->where('payment_type', 'Cancelled')->count(),
            'gross' => $statsCalculated['gross'],
            'cash' => $statsCalculated['cash'],
            'paytm' => $statsCalculated['paytm'],
            'check' => $statsCalculated['check'],
            'credit' => $statsCalculated['credit'],
            'cancelled' => $statsCalculated['cancelled'],
            'cd' => $statsCalculated['cd'],
            'refund' => $statsCalculated['refund'],
            'net' => $statsCalculated['net'],
        ];

        return view('summary.print_single', compact('pso', 'bills', 'stats', 'businessDate'));
    }
}
