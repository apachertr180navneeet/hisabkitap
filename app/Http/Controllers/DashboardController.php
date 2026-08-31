<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReconciliationService;
use App\Models\PsoConfig;
use App\Models\TallyImport;
use App\Models\PsoRetention;
use App\Models\Bill;

class DashboardController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index()
    {
        $metrics = $this->reconService->getMetrics();
        $psoConfigs = PsoConfig::where('is_active', true)->get();
        $recentImports = TallyImport::orderBy('id', 'desc')->take(5)->get();
        $retentionList = PsoRetention::orderBy('id', 'asc')->take(5)->get();

        // Calculate PSO rows for dashboard table
        $psoRows = [];
        foreach ($psoConfigs as $pso) {
            $bills = Bill::whereDate('business_date', $metrics['businessDate'])
                ->where('pso_code', $pso->code)
                ->where('is_post_cutoff', false)
                ->get();

            $gross = $bills->sum('amount');
            $net = $bills->where('status', '!=', 'Missing')->sum('net_amount');
            $missing = $bills->where('status', 'Missing')->count();

            $psoRows[] = [
                'pso' => $pso,
                'billsCount' => $bills->count(),
                'gross' => $gross,
                'net' => $net,
                'missing' => $missing,
                'status' => ($missing > 0) ? 'Missing Bills Detected' : 'Verified',
                'statusClass' => ($missing > 0) ? 'badge bg-danger' : 'badge bg-success',
            ];
        }

        return view('dashboard', compact('metrics', 'psoRows', 'recentImports', 'retentionList'));
    }
}
