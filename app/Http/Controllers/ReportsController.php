<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\CreditCollection;
use App\Models\Correction;
use App\Models\AuditLog;
use App\Models\PsoConfig;
use App\Services\ReconciliationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        $reportType = $request->get('type', 'daily_pso');
        $businessDate = $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        $reportData = [];

        if ($reportType === 'daily_pso') {
            $reportData = [
                'title' => 'Daily PSO Summary Report',
                'psoConfigs' => PsoConfig::all(),
                'bills' => Bill::where('business_date', $businessDate)->where('is_post_cutoff', false)->get(),
                'metrics' => $metrics,
            ];
        } elseif ($reportType === 'recon_sheet') {
            $reportData = [
                'title' => 'Tally vs PSO Master Reconciliation Sheet',
                'metrics' => $metrics,
                'bills' => Bill::where('business_date', $businessDate)->where('is_post_cutoff', false)->get(),
            ];
        } elseif ($reportType === 'credit_sheet') {
            $reportData = [
                'title' => 'Credit Collection & Salesman Ledger',
                'credits' => CreditCollection::all(),
            ];
        } elseif ($reportType === 'missing_bills') {
            $reportData = [
                'title' => 'Missing & Discrepancy Bill Investigation Log',
                'bills' => Bill::where('business_date', $businessDate)->where('status', '!=', 'Matched')->get(),
            ];
        } elseif ($reportType === 'corrections_log') {
            $reportData = [
                'title' => 'Cash Discounts, Goods Returns & Spot Adjustments Register',
                'corrections' => Correction::all(),
            ];
        } elseif ($reportType === 'audit_history') {
            $reportData = [
                'title' => 'System-Wide Statutory Audit Log',
                'logs' => AuditLog::orderBy('id', 'desc')->take(50)->get(),
            ];
        }

        return view('reports.index', compact('reportType', 'reportData', 'metrics'));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $reportType = $request->get('type', 'daily_pso');
        $businessDate = $this->reconService->getBusinessDate();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"HisabKitap_{$reportType}_{$businessDate}.csv\"",
        ];

        return response()->stream(function () use ($reportType, $businessDate) {
            $handle = fopen('php://output', 'w');

            if ($reportType === 'daily_pso' || $reportType === 'recon_sheet') {
                fputcsv($handle, ['Bill No', 'PSO', 'Customer', 'Amount', 'Payment Type', 'CD', 'Refund', 'Net Amount', 'Status']);
                $bills = Bill::where('business_date', $businessDate)->where('is_post_cutoff', false)->get();
                foreach ($bills as $b) {
                    fputcsv($handle, [$b->bill_no, $b->pso_code, $b->customer_name, $b->amount, $b->payment_type, $b->cd_amount, $b->refund_amount, $b->net_amount, $b->status]);
                }
            } elseif ($reportType === 'credit_sheet') {
                fputcsv($handle, ['Bill No', 'Customer', 'Salesman', 'Bill Date', 'Due Date', 'Amount', 'Paid', 'Outstanding', 'Status']);
                foreach (CreditCollection::all() as $c) {
                    fputcsv($handle, [$c->bill_no, $c->customer_name, $c->salesman_name, $c->bill_date, $c->due_date, $c->bill_amount, $c->paid_amount, $c->outstanding_amount, $c->collection_status]);
                }
            } else {
                fputcsv($handle, ['ID', 'User', 'Action', 'Details', 'Timestamp']);
                foreach (AuditLog::all() as $l) {
                    fputcsv($handle, [$l->id, $l->user_name, $l->action, $l->details, $l->created_at]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
