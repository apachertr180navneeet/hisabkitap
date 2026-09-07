<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

    public function index()
    {
        $businessDate = $this->reconService->getBusinessDate();
        $psoConfigs = PsoConfig::where('is_active', true)->get();
        $metrics = $this->reconService->getMetrics($businessDate);

        $matrixRows = [];
        foreach ($psoConfigs as $pso) {
            $bills = Bill::whereDate('business_date', $businessDate)
                ->where('pso_code', $pso->code)
                ->where('is_post_cutoff', false)
                ->get();

            $gross = $bills->sum('amount');
            $cash = $bills->where('payment_type', 'Cash')->sum('net_amount');
            $paytm = $bills->where('payment_type', 'Paytm')->sum('net_amount');
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

        $allPsoConfigs = PsoConfig::where('is_active', true)->orderBy('code')->get();

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
        $cash = $allPsoBills->where('payment_type', 'Cash')->sum('net_amount');
        $paytm = $allPsoBills->where('payment_type', 'Paytm')->sum('net_amount');
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
}
