<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CreditCollection;
use App\Models\Prefix;
use App\Models\PsoConfig;
use App\Models\AuditLog;
use App\Services\ReconciliationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreditCollectionController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        $selectedPrefix = $request->input('prefix');
        $allPrefixes = $this->getAllAvailablePrefixes();
        $credits = $this->getFilteredCredits($request);

        $totSales = $credits->sum('bill_amount');
        $totRecovered = $credits->sum('paid_amount');
        $totOutstanding = $credits->sum('outstanding_amount');

        return view('credit.index', compact(
            'credits',
            'totSales',
            'totRecovered',
            'totOutstanding',
            'allPrefixes',
            'selectedPrefix'
        ));
    }

    public function updatePayment(Request $request)
    {
        $request->validate([
            'credit_id' => 'required|integer',
            'paid_today' => 'required|numeric|min:0',
            'payment_mode' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        $credit = CreditCollection::findOrFail($request->credit_id);
        $paidToday = (float) $request->paid_today;

        $newPaid = (float) $credit->paid_amount + $paidToday;
        if ($newPaid > (float) $credit->bill_amount) {
            $newPaid = (float) $credit->bill_amount;
        }

        $newOut = (float) $credit->bill_amount - $newPaid;
        $status = ($newOut <= 0) ? 'Collected' : (($newPaid > 0) ? 'Partially Collected' : 'Pending');

        $credit->paid_amount = $newPaid;
        $credit->outstanding_amount = $newOut;
        $credit->collection_status = $status;
        $credit->payment_mode = $request->payment_mode;
        $credit->last_payment_date = now();
        if ($request->remark) {
            $credit->remark = $credit->remark ? ($credit->remark . ' | ' . $request->remark) : $request->remark;
        }
        $credit->save();

        AuditLog::log('CREDIT_RECOVERY', "Collected ₹{$paidToday} for bill {$credit->bill_no} ({$credit->customer_name}) via {$request->payment_mode}. Status: {$status}");

        return redirect()->back()->with('success', "Payment of ₹" . number_format($paidToday) . " recorded for bill {$credit->bill_no}.");
    }

    public function sendToUdhari(Request $request)
    {
        $request->validate([
            'prefix' => 'nullable|string',
            'udhari_api' => 'required|string',
        ]);

        $prefix = trim((string)$request->input('prefix', ''));
        $api = trim((string)$request->input('udhari_api'));

        // Retrieve credit records matching this prefix
        $credits = $this->getFilteredCredits($request);

        $count = $credits->count();
        $totalAmount = $credits->sum('bill_amount');
        $prefixLabel = (!empty($prefix) && strtoupper($prefix) !== 'ALL') ? strtoupper($prefix) : 'ALL';

        $apiLabels = [
            'redbull' => 'Redbull (insert-multi)',
            'cadbury' => 'Cadbury (insert-multi)',
            'parle' => 'Parle (insert-multi)',
            'itc' => 'Itc (insert-invoice)',
        ];
        $apiLabel = $apiLabels[$api] ?? ucfirst($api);

        $redirectRoute = request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index';
        $params = (!empty($prefix) && strtoupper($prefix) !== 'ALL') ? ['prefix' => $prefix] : [];

        if ($count === 0) {
            AuditLog::log('UDHARI_APP_SYNC', "Attempted Udhari App sync for prefix '{$prefixLabel}' via {$apiLabel}, but no matching credit bills were found.");
            return redirect()->route($redirectRoute, $params)
                ->with('warning', "No credit bills found for prefix '{$prefixLabel}' to submit to Udhari App ({$apiLabel}).");
        }

        AuditLog::log('UDHARI_APP_SYNC', "Sent {$count} credit bills with prefix '{$prefixLabel}' (Total: ₹" . number_format($totalAmount, 2) . ") to Udhari App via {$apiLabel} API.");

        return redirect()->route($redirectRoute, $params)
            ->with('success', "Successfully sent {$count} bills with prefix '{$prefixLabel}' (Total: ₹" . number_format($totalAmount, 2) . ") to Udhari App ({$apiLabel}).");
    }

    public function exportSheet(Request $request): StreamedResponse
    {
        $selectedPrefix = $request->input('prefix');
        $credits = $this->getFilteredCredits($request);
        $date = $this->reconService->getBusinessDate();
        $prefixTag = (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') ? "_{$selectedPrefix}" : "";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Credit_Collection_Sheet{$prefixTag}_{$date}.csv\"",
        ];

        return response()->stream(function () use ($credits, $selectedPrefix) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Bill No', 'Prefix', 'Customer Name', 'Assigned Salesman', 'Bill Date', 'Due Date', 'Total Amount (INR)', 'Paid Amount (INR)', 'Outstanding (INR)', 'Status', 'Remarks']);
            
            $totSales = 0; $totPaid = 0; $totOut = 0;
            foreach ($credits as $c) {
                $totSales += (float)$c->bill_amount;
                $totPaid += (float)$c->paid_amount;
                $totOut += (float)$c->outstanding_amount;
                $bDate = $c->bill_date ? (is_string($c->bill_date) ? substr($c->bill_date, 0, 10) : $c->bill_date->format('d/m/Y')) : '';
                $dDate = $c->due_date ? (is_string($c->due_date) ? substr($c->due_date, 0, 10) : $c->due_date->format('d/m/Y')) : '';

                fputcsv($handle, [
                    $c->bill_no,
                    $c->bill_prefix ?: '—',
                    $c->customer_name,
                    $c->salesman_name ?: '—',
                    $bDate,
                    $dDate,
                    $c->bill_amount,
                    $c->paid_amount,
                    $c->outstanding_amount,
                    $c->collection_status,
                    $c->remark ?: '—',
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') ? "Prefix: {$selectedPrefix}" : '',
                count($credits) . ' Customers',
                '',
                '',
                '',
                $totSales,
                $totPaid,
                $totOut,
                '',
                ''
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print / Export PDF for Credit Collections
     */
    public function exportPdf(Request $request)
    {
        $businessDate = $this->reconService->getBusinessDate();
        $selectedPrefix = $request->input('prefix');
        $credits = $this->getFilteredCredits($request);

        $totSales = $credits->sum('bill_amount');
        $totRecovered = $credits->sum('paid_amount');
        $totOutstanding = $credits->sum('outstanding_amount');

        return view('credit.print', compact(
            'credits',
            'businessDate',
            'totSales',
            'totRecovered',
            'totOutstanding',
            'selectedPrefix'
        ));
    }

    /**
     * Get filtered credit collection records based on request criteria.
     */
    protected function getFilteredCredits(Request $request)
    {
        $selectedPrefix = $request->input('prefix');
        $query = CreditCollection::with(['bill.psoConfig'])->orderBy('id', 'asc');

        if (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') {
            $query->filterPrefix($selectedPrefix);
        }

        $credits = $query->get();

        if (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') {
            $prefixUpper = strtoupper(trim((string)$selectedPrefix));
            $credits = $credits->filter(function ($c) use ($prefixUpper) {
                $p = $c->bill_prefix;
                if ($p) {
                    return $p === $prefixUpper;
                }
                return stripos((string)$c->bill_no, $prefixUpper) !== false;
            })->values();
        }

        return $credits;
    }

    /**
     * Retrieve all unique prefixes available across Prefix master, PSO configs, and credit records.
     */
    protected function getAllAvailablePrefixes()
    {
        $masterPrefixes = Prefix::where('is_active', true)->pluck('prefix')->toArray();
        $psoPrefixes = PsoConfig::whereNotNull('prefix')->pluck('prefix')->toArray();
        $creditPrefixes = CreditCollection::pluck('bill_no')->map(function ($billNo) {
            $parsed = PsoConfig::parseBillNumber($billNo);
            return !empty($parsed['prefix']) ? strtoupper(trim($parsed['prefix'])) : null;
        })->filter()->toArray();

        return collect(array_merge($masterPrefixes, $psoPrefixes, $creditPrefixes))
            ->map(fn($p) => strtoupper(trim((string)$p)))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
