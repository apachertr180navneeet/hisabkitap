<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CreditCollection;
use App\Models\Prefix;
use App\Models\PsoConfig;
use App\Models\AuditLog;
use App\Services\ReconciliationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
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
        $selectedStatus = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $allPrefixes = $this->getAllAvailablePrefixes();
        $credits = $this->getFilteredCredits($request);

        $totSales = $credits->sum('bill_amount');
        $totRecovered = $credits->sum('paid_amount');
        $totOutstanding = $credits->sum('outstanding_amount');
        $totSent = $credits->where('is_udhari_synced', true)->count();
        $totNotSent = $credits->where('is_udhari_synced', false)->count();

        return view('credit.index', compact(
            'credits',
            'totSales',
            'totRecovered',
            'totOutstanding',
            'totSent',
            'totNotSent',
            'allPrefixes',
            'selectedPrefix',
            'selectedStatus',
            'startDate',
            'endDate'
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
            'start_date' => 'nullable|string',
            'end_date' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $prefix = trim((string)$request->input('prefix', ''));
        $api = trim((string)$request->input('udhari_api'));

        // Retrieve credit records matching criteria, sending NOT SENT only!
        $credits = $this->getFilteredCredits($request, onlyUnsent: true);
        $prefixLabel = (!empty($prefix) && strtoupper($prefix) !== 'ALL') ? strtoupper($prefix) : 'ALL';

        $apiLabels = [
            'redbull' => 'Redbull (insert-multi)',
            'cadbury' => 'Cadbury (insert-multi)',
            'parle' => 'Parle (insert-multi)',
            'itc' => 'Itc (insert-invoice)',
        ];
        $apiLabel = $apiLabels[$api] ?? ucfirst($api);

        $count = $credits->count();
        $totalAmount = $credits->sum('bill_amount');
        $redirectRoute = request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index';
        $params = [];
        if (!empty($prefix) && strtoupper($prefix) !== 'ALL') {
            $params['prefix'] = $prefix;
        }
        if ($request->filled('start_date')) {
            $params['start_date'] = $request->input('start_date');
        }
        if ($request->filled('end_date')) {
            $params['end_date'] = $request->input('end_date');
        }
        if ($request->filled('status')) {
            $params['status'] = $request->input('status');
        }

        if ($count === 0) {
            return redirect()->route($redirectRoute, $params)
                ->with('warning', "No unsent credit bills found for prefix '{$prefixLabel}' to submit to Udhari App ({$apiLabel}).");
        }

        // Check prefix and API labels according to selected API
        $apiUrl = null;
        if ($api === 'redbull') {
            // API: Redbull (insert-multi) | Prefix: $prefixLabel
            $apiUrl = 'https://bigbiteagencys.com/api/redbull/insert-multi';
        } elseif ($api === 'cadbury') {
            // API: Cadbury (insert-multi) | Prefix: $prefixLabel
            $apiUrl = 'https://bigbiteagencys.com/api/cadbury/insert-multi';
        } elseif ($api === 'parle') {
            // API: Parle (insert-multi) | Prefix: $prefixLabel
            $apiUrl = 'https://bigbiteagencys.com/api/parle/insert-multi';
        } elseif ($api === 'itc') {
            // API: Itc (insert-multi) | Prefix: $prefixLabel
            $apiUrl = 'https://bigbiteagencys.com/udhari_itc/api/invoices/insert-multi';
        } else {
            return redirect()->route($redirectRoute, $params)
                ->with('error', "Invalid or unsupported Udhari API selected: {$api}");
        }

        $invoiceData = [];
        foreach ($credits as $c) {
            $dateStr = $c->bill_date
                ? (is_string($c->bill_date) ? substr($c->bill_date, 0, 10) : $c->bill_date->format('Y-m-d'))
                : date('Y-m-d');

            $invoiceData[] = [
                'date' => $dateStr,
                'invoice_no' => (string)$c->bill_no,
                'firm_id' => (string)$c->customer_name,
                'salesperson_id' => (string)($c->salesman_name ?: 'Unknown'),
                'amount' => (float)$c->bill_amount,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'payable_amount' => (float)$c->bill_amount,
            ];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($apiUrl, [
                    'invoices' => $invoiceData,
                ]);

            $respData = $response->json();
            $isSuccess = $response->successful() && (!isset($respData['status']) || $respData['status'] == true);

            if ($isSuccess) {
                // Update credit records as synced to Udhari app inside an atomic transaction
                DB::transaction(function () use ($credits, $apiLabel) {
                    foreach ($credits as $c) {
                        $c->update([
                            'is_udhari_synced' => true,
                            'udhari_synced_at' => now(),
                            'udhari_api' => $apiLabel,
                        ]);
                    }
                });

                $msg = $respData['message'] ?? "Successfully submitted {$count} bills with prefix '{$prefixLabel}' to {$apiLabel} Udhari App.";
                AuditLog::log('UDHARI_APP_SYNC', "Sent {$count} credit bills with prefix '{$prefixLabel}' (Total: ₹" . number_format($totalAmount, 2) . ") to {$apiLabel} API ({$apiUrl}). Response: " . json_encode($respData));

                return redirect()->back(fallback: route($redirectRoute, $params))
                    ->with('success', $msg);
            } else {
                // Failure: DO NOT complete the update on any credit bills
                $errorMessage = $this->formatUdhariApiError($apiLabel, $apiUrl, $response);
                AuditLog::log('UDHARI_APP_SYNC_FAIL', "Failed sending {$count} bills with prefix '{$prefixLabel}' to {$apiLabel} API ({$apiUrl}). Error: {$errorMessage}");

                $errDetails = is_array($respData) ? $respData : null;
                if ($errDetails && empty($errDetails['agency'])) {
                    $errDetails['agency'] = $apiLabel;
                }

                return redirect()->back(fallback: route($redirectRoute, $params))
                    ->with('error', $errorMessage)
                    ->with('udhari_error_details', $errDetails);
            }
        } catch (\Throwable $e) {
            // Exception: DO NOT complete the update on any credit bills
            $errorMessage = $this->formatUdhariApiError($apiLabel, $apiUrl, null, $e);
            AuditLog::log('UDHARI_APP_SYNC_EXCEPTION', "Exception sending bills with prefix '{$prefixLabel}' to {$apiLabel} API ({$apiUrl}): " . $e->getMessage());

            return redirect()->back(fallback: route($redirectRoute, $params))
                ->with('error', $errorMessage);
        }
    }

    /**
     * Build a clear and descriptive error message explaining which API failed and the exact reason.
     */
    protected function formatUdhariApiError(string $apiName, string $apiUrl, $response = null, ?\Throwable $exception = null): string
    {
        $header = "[{$apiName} API Error]";

        if ($exception) {
            return "{$header} Connection to {$apiUrl} failed: " . $exception->getMessage();
        }

        if (!$response) {
            return "{$header} No response received from {$apiUrl}.";
        }

        $statusCode = $response->status();
        $respData = $response->json();

        // 1. Structured JSON error
        if (is_array($respData)) {
            $msg = $respData['message'] ?? $respData['msg'] ?? null;
            $fieldErrors = [];

            if (!empty($respData['errors']) && is_array($respData['errors'])) {
                foreach ($respData['errors'] as $field => $errors) {
                    if (is_array($errors)) {
                        $fieldErrors[] = (!is_numeric($field) ? "{$field}: " : "") . implode(', ', $errors);
                    } elseif (is_string($errors)) {
                        $fieldErrors[] = (!is_numeric($field) ? "{$field}: " : "") . $errors;
                    }
                }
            } elseif (!empty($respData['error'])) {
                $fieldErrors[] = is_string($respData['error']) ? $respData['error'] : json_encode($respData['error']);
            }

            // Extract missing customers & salespersons from Udhari APIs (Cadbury / Redbull / Parle)
            if (!empty($respData['missing_customers']) && is_array($respData['missing_customers'])) {
                $fieldErrors[] = "Missing Customers (" . count($respData['missing_customers']) . "): " . implode(', ', $respData['missing_customers']);
            }
            if (!empty($respData['missing_salespersons']) && is_array($respData['missing_salespersons'])) {
                $fieldErrors[] = "Missing Salespersons (" . count($respData['missing_salespersons']) . "): " . implode(', ', $respData['missing_salespersons']);
            }

            $details = [];
            if ($msg) {
                $details[] = $msg;
            }
            if (!empty($fieldErrors)) {
                $details[] = "Details: " . implode(' | ', $fieldErrors);
            }

            if (!empty($details)) {
                return "{$header} HTTP {$statusCode} at {$apiUrl}: " . implode(' - ', $details);
            }
        }

        // 2. Non-JSON body (e.g. 500 HTML error page, Cloudflare error)
        $rawBody = trim(strip_tags($response->body()));
        if (!empty($rawBody)) {
            $snippet = substr(preg_replace('/\s+/', ' ', $rawBody), 0, 200);
            return "{$header} HTTP {$statusCode} at {$apiUrl}: {$snippet}";
        }

        return "{$header} Remote server returned HTTP {$statusCode} ({$apiUrl}) with no error details.";
    }

    public function exportSheet(Request $request): StreamedResponse
    {
        $selectedPrefix = $request->input('prefix');
        $selectedStatus = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $credits = $this->getFilteredCredits($request);
        $date = $this->reconService->getBusinessDate();
        $prefixTag = (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') ? "_{$selectedPrefix}" : "";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Credit_Collection_Sheet{$prefixTag}_{$date}.csv\"",
        ];

        return response()->stream(function () use ($credits, $selectedPrefix, $selectedStatus, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Bill No', 'Prefix', 'Customer Name', 'Assigned Salesman', 'Bill Date', 'Due Date', 'Total Amount (INR)', 'Paid Amount (INR)', 'Outstanding (INR)', 'Status', 'Udhari Status', 'Remarks']);
            
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
                    $c->is_udhari_synced ? ('Sent (' . ($c->udhari_api ?: 'App') . ')') : 'Not Sent',
                    $c->remark ?: '—',
                ]);
            }

            $infoParts = [];
            if (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') {
                $infoParts[] = "Prefix: {$selectedPrefix}";
            }
            if ($selectedStatus && $selectedStatus !== 'all') {
                $infoParts[] = "Status: " . ($selectedStatus === 'sent' ? 'Sent' : 'Not Sent');
            }
            if ($startDate && $endDate) {
                $infoParts[] = "Date: {$startDate} to {$endDate}";
            } elseif ($startDate) {
                $infoParts[] = "From: {$startDate}";
            } elseif ($endDate) {
                $infoParts[] = "To: {$endDate}";
            }

            fputcsv($handle, [
                'TOTAL',
                implode(' | ', $infoParts),
                count($credits) . ' Customers',
                '',
                '',
                '',
                $totSales,
                $totPaid,
                $totOut,
                '',
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
        $selectedStatus = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
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
            'selectedPrefix',
            'selectedStatus',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get filtered credit collection records based on request criteria.
     */
    protected function getFilteredCredits(Request $request, bool $onlyUnsent = false)
    {
        $selectedPrefix = $request->input('prefix');
        $status = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = CreditCollection::with(['bill.psoConfig'])->orderBy('id', 'asc');

        if (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') {
            $query->filterPrefix($selectedPrefix);
        }

        // Status Filter: sent vs not sent
        if ($onlyUnsent) {
            $query->where('is_udhari_synced', false);
        } elseif ($status === 'sent') {
            $query->where('is_udhari_synced', true);
        } elseif ($status === 'not_sent') {
            $query->where('is_udhari_synced', false);
        }

        // Date Range Filter: start_date and end_date on bill_date
        if (!empty($startDate)) {
            try {
                $parsedStart = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
                $query->whereDate('bill_date', '>=', $parsedStart);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }
        if (!empty($endDate)) {
            try {
                $parsedEnd = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
                $query->whereDate('bill_date', '<=', $parsedEnd);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
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
