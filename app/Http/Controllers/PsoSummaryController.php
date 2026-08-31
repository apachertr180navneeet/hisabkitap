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
            $bills = Bill::where('business_date', $businessDate)
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
}
