<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\PsoConfig;
use App\Models\AuditLog;
use App\Models\PsoDailySeal;
use App\Models\Salesperson;
use App\Models\CreditCollection;
use App\Services\ReconciliationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillVerificationController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        $businessDate = $request->input('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            if (Bill::whereDate('business_date', $defaultDate)->exists()) {
                $businessDate = $defaultDate;
            } else {
                $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
                $businessDate = $latestBillDate ? date('Y-m-d', strtotime($latestBillDate)) : $defaultDate;
            }
        }

        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoList = $psoQuery->orderBy('code')->get();
        $salespersons = Salesperson::where('is_active', true)->orderBy('name')->get();

        $availableDates = Bill::selectRaw('DATE(business_date) as b_date')
            ->whereNotNull('business_date')
            ->distinct()
            ->orderBy('b_date', 'desc')
            ->pluck('b_date')
            ->toArray();

        $query = Bill::query();

        if ($businessDate && $businessDate !== 'ALL') {
            $query->whereDate('business_date', $businessDate);
        }

        if ($request->input('cutoff') === 'post') {
            $query->where('is_post_cutoff', true);
        } elseif ($request->input('cutoff') === 'regular') {
            $query->where('is_post_cutoff', false);
        } elseif ($request->input('cutoff') === 'all') {
            // no filter
        } else {
            // By default, only hide post cutoff if regular bills exist for this query
            $hasRegular = (clone $query)->where('is_post_cutoff', false)->exists();
            if ($hasRegular) {
                $query->where('is_post_cutoff', false);
            }
        }

        if ($request->filled('pso') && $request->pso !== 'ALL') {
            $query->where('pso_code', $request->pso);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type') && $request->payment_type !== 'ALL') {
            if ($request->payment_type === 'Split') {
                $query->where(function ($q) {
                    $q->where('is_split_payment', true)
                      ->orWhere(function ($sub) {
                          $sub->where('cash_amount', '>', 0)->where('paytm_amount', '>', 0);
                      });
                });
            } else {
                $query->where('payment_type', $request->payment_type);
            }
        }

        if ($request->filled('salesperson') && $request->salesperson !== 'ALL') {
            $salespersonVal = $request->salesperson;
            $query->where(function ($q) use ($salespersonVal) {
                $q->where('salesman_name', $salespersonVal)
                  ->orWhere('salesperson_id', $salespersonVal);
            });
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
        $metrics = $this->reconService->getMetrics($businessDate !== 'ALL' ? $businessDate : null);

        return view('verification.index', compact('bills', 'psoList', 'metrics', 'salespersons', 'businessDate', 'availableDates'));
    }

    public function updateBill(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'payment_type' => 'required|string',
            'cd_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string',
            'cash_amount' => 'nullable|numeric|min:0',
            'paytm_amount' => 'nullable|numeric|min:0',
            'is_split_payment' => 'nullable|boolean',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bill = Bill::findOrFail($request->bill_id);

        $rawType = strtolower(trim($request->payment_type));
        $normalizedPaymentType = match($rawType) {
            'cash' => 'Cash',
            'paytm', 'upi', 'online', 'rtgs', 'bank' => 'Paytm',
            'check', 'cheque', 'dd' => 'Check',
            'credit' => 'Credit',
            'cancelled' => 'Cancelled',
            'split' => 'Split',
            default => 'Cash'
        };

        $bill->cd_amount = floatval($request->input('cd_amount', 0));
        $bill->refund_amount = floatval($request->input('refund_amount', 0));
        $bill->net_amount = max(0, floatval($bill->amount) - $bill->cd_amount - $bill->refund_amount);

        // Handle Split Payment vs Standard Payment
        $cashAmt = floatval($request->input('cash_amount', 0));
        $paytmAmt = floatval($request->input('paytm_amount', 0));
        $isSplit = (bool) $request->input('is_split_payment', false) || ($normalizedPaymentType === 'Split');

        if (($isSplit && $cashAmt > 0 && $paytmAmt > 0) || ($cashAmt > 0 && $paytmAmt > 0)) {
            $bill->is_split_payment = true;
            $bill->cash_amount = $cashAmt;
            $bill->paytm_amount = $paytmAmt;
            $bill->payment_type = 'Cash'; // Primary accounting bucket
        } elseif ($normalizedPaymentType === 'Paytm' || ($paytmAmt > 0 && $cashAmt == 0)) {
            $bill->is_split_payment = false;
            $bill->payment_type = 'Paytm';
            $bill->paytm_amount = $bill->net_amount;
            $bill->cash_amount = 0;
        } elseif ($normalizedPaymentType === 'Check') {
            $bill->is_split_payment = false;
            $bill->payment_type = 'Check';
            $bill->cash_amount = 0;
            $bill->paytm_amount = 0;
        } elseif ($normalizedPaymentType === 'Credit') {
            $bill->is_split_payment = false;
            $bill->payment_type = 'Credit';
            $bill->cash_amount = 0;
            $bill->paytm_amount = 0;
        } elseif ($normalizedPaymentType === 'Cancelled') {
            $bill->is_split_payment = false;
            $bill->payment_type = 'Cancelled';
            $bill->cash_amount = 0;
            $bill->paytm_amount = 0;
        } else {
            // Default: Cash
            $bill->is_split_payment = false;
            $bill->payment_type = 'Cash';
            $bill->cash_amount = $bill->net_amount;
            $bill->paytm_amount = 0;
        }

        // Update Salesperson
        if ($request->filled('salesperson_id') && $request->salesperson_id !== '') {
            $sp = Salesperson::find($request->salesperson_id);
            if ($sp) {
                $bill->salesperson_id = $sp->id;
                $bill->salesman_name = $sp->name;
            } else {
                $bill->salesperson_id = null;
                $bill->salesman_name = $request->salesman_name ?: null;
            }
        } elseif ($request->filled('salesman_name') && $request->salesman_name !== '') {
            $bill->salesman_name = $request->salesman_name;
            $sp = Salesperson::where('name', $request->salesman_name)->first();
            $bill->salesperson_id = $sp ? $sp->id : null;
        } else {
            $bill->salesperson_id = null;
            $bill->salesman_name = null;
        }

        // Sync with CreditCollection if Credit
        if ($bill->payment_type === 'Credit') {
            $salesmanForCredit = $bill->salesman_name ?: 'Field Representative';
            CreditCollection::updateOrCreate(
                ['bill_id' => $bill->id],
                [
                    'bill_no' => $bill->bill_no,
                    'customer_name' => $bill->customer_name,
                    'salesman_name' => $salesmanForCredit,
                    'bill_date' => $bill->business_date ?: $businessDate,
                    'due_date' => date('Y-m-d', strtotime(($bill->business_date ? $bill->business_date->format('Y-m-d') : $businessDate) . ' +7 days')),
                    'bill_amount' => $bill->net_amount > 0 ? $bill->net_amount : $bill->amount,
                    'paid_amount' => 0,
                    'outstanding_amount' => $bill->net_amount > 0 ? $bill->net_amount : $bill->amount,
                    'collection_status' => 'Pending',
                    'payment_mode' => 'Credit Pending',
                    'remark' => $bill->remark,
                ]
            );
        }

        $bill->save();

        // Update reconciliation state
        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('BILL_INLINE_UPDATE', "Updated bill {$bill->bill_no}: Payment={$bill->payment_type}, CD=₹{$bill->cd_amount}, Refund=₹{$bill->refund_amount}, Salesman=" . ($bill->salesman_name ?? 'None'));

        return response()->json([
            'success' => true,
            'message' => "Bill {$bill->bill_no} updated successfully.",
            'bill' => [
                'id' => $bill->id,
                'bill_no' => $bill->bill_no,
                'payment_type' => $bill->payment_type,
                'is_split_payment' => (bool)$bill->is_split_payment,
                'cash_amount' => number_format($bill->cash_amount, 2, '.', ''),
                'paytm_amount' => number_format($bill->paytm_amount, 2, '.', ''),
                'cd_amount' => number_format($bill->cd_amount, 2, '.', ''),
                'refund_amount' => number_format($bill->refund_amount, 2, '.', ''),
                'net_amount' => number_format($bill->net_amount, 2, '.', ''),
                'salesman_name' => $bill->salesman_name,
                'salesperson_id' => $bill->salesperson_id,
            ],
            'metrics' => $metrics
        ]);
    }

    /**
     * Manually record a new bill / payment entry
     */
    public function storeManualBill(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string|max:50',
            'pso_code' => 'required|string|max:50',
            'business_date' => 'required|date',
            'customer_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|string',
            'cash_amount' => 'nullable|numeric|min:0',
            'paytm_amount' => 'nullable|numeric|min:0',
            'cd_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string|max:255',
            'remark' => 'nullable|string',
        ]);

        $pso = PsoConfig::where('code', $request->pso_code)->first();
        $amount = floatval($request->amount);
        $cdAmount = floatval($request->input('cd_amount', 0));
        $refundAmount = floatval($request->input('refund_amount', 0));
        $netAmount = max(0, $amount - $cdAmount - $refundAmount);

        $paymentType = $request->payment_type;
        $isSplit = ($paymentType === 'Split') || ($request->filled('cash_amount') && $request->filled('paytm_amount') && floatval($request->cash_amount) > 0 && floatval($request->paytm_amount) > 0);
        $cashAmount = 0;
        $paytmAmount = 0;

        if ($isSplit) {
            $isSplit = true;
            $paymentType = 'Cash';
            $cashAmount = floatval($request->input('cash_amount', 0));
            $paytmAmount = floatval($request->input('paytm_amount', 0));
            if ($cashAmount == 0 && $paytmAmount == 0) {
                $cashAmount = $netAmount;
            }
        } elseif ($paymentType === 'Cash') {
            $cashAmount = $netAmount;
        } elseif ($paymentType === 'Paytm') {
            $paytmAmount = $netAmount;
        }

        $salespersonId = null;
        $salesmanName = null;

        if ($request->filled('salesperson_id')) {
            $sp = Salesperson::find($request->salesperson_id);
            if ($sp) {
                $salespersonId = $sp->id;
                $salesmanName = $sp->name;
            }
        } elseif ($request->filled('salesman_name')) {
            $salesmanName = $request->salesman_name;
            $sp = Salesperson::where('name', $request->salesman_name)->first();
            $salespersonId = $sp ? $sp->id : null;
        }

        $bill = Bill::create([
            'bill_no' => $request->bill_no,
            'pso_config_id' => $pso ? $pso->id : null,
            'pso_code' => $request->pso_code,
            'business_date' => $request->business_date,
            'bill_time' => $request->input('bill_time', date('H:i')),
            'customer_name' => $request->customer_name,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'voucher_type' => 'Sales',
            'salesperson_id' => $salespersonId,
            'salesman_name' => $salesmanName,
            'cd_amount' => $cdAmount,
            'refund_amount' => $refundAmount,
            'net_amount' => $netAmount,
            'cash_amount' => $cashAmount,
            'paytm_amount' => $paytmAmount,
            'is_split_payment' => $isSplit,
            'status' => 'Matched',
            'is_expected' => true,
            'tally_found' => true,
            'is_post_cutoff' => false,
            'remark' => $request->input('remark', 'Manual bill entry added via ERP'),
            'verified_by' => session('active_user.name', 'Pooja Verma'),
            'verified_at' => now(),
        ]);

        if ($paymentType === 'Credit') {
            CreditCollection::create([
                'bill_id' => $bill->id,
                'bill_no' => $bill->bill_no,
                'customer_name' => $bill->customer_name,
                'salesman_name' => $salesmanName ?: 'Field Representative',
                'bill_date' => $bill->business_date,
                'due_date' => date('Y-m-d', strtotime($bill->business_date . ' +7 days')),
                'bill_amount' => $netAmount,
                'paid_amount' => 0,
                'outstanding_amount' => $netAmount,
                'collection_status' => 'Pending',
                'payment_mode' => 'Credit Pending',
                'remark' => $bill->remark,
            ]);
        }

        AuditLog::log('MANUAL_BILL_CREATED', "Added manual bill {$bill->bill_no} for ₹{$bill->amount} ({$paymentType}) on {$bill->business_date}");

        return redirect()->back()->with('success', "Manual bill {$bill->bill_no} added successfully!");
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'exists:bills,id',
            'payment_type' => 'nullable|string',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::whereIn('id', $request->bill_ids)->get();

        $sp = null;
        if ($request->filled('salesperson_id') && $request->salesperson_id !== '') {
            $sp = Salesperson::find($request->salesperson_id);
        }

        $normalizedPaymentType = null;
        if ($request->filled('payment_type')) {
            $rawType = strtolower(trim($request->payment_type));
            $normalizedPaymentType = match($rawType) {
                'cash' => 'Cash',
                'paytm', 'upi', 'online', 'rtgs', 'bank' => 'Paytm',
                'check', 'cheque', 'dd' => 'Check',
                'credit' => 'Credit',
                'cancelled' => 'Cancelled',
                'split' => 'Split',
                default => 'Cash'
            };
        }

        $updatedCount = 0;
        foreach ($bills as $bill) {
            if ($normalizedPaymentType !== null) {
                if ($normalizedPaymentType === 'Split') {
                    $bill->is_split_payment = true;
                    $bill->payment_type = 'Cash';
                } else {
                    $bill->is_split_payment = false;
                    $bill->payment_type = $normalizedPaymentType;
                    $effectiveNet = max(0, floatval($bill->amount) - floatval($bill->cd_amount) - floatval($bill->refund_amount));
                    $bill->net_amount = $effectiveNet;
                    if ($normalizedPaymentType === 'Cash') {
                        $bill->cash_amount = $effectiveNet;
                        $bill->paytm_amount = 0;
                    } elseif ($normalizedPaymentType === 'Paytm') {
                        $bill->paytm_amount = $effectiveNet;
                        $bill->cash_amount = 0;
                    } else {
                        $bill->cash_amount = 0;
                        $bill->paytm_amount = 0;
                    }
                }
            }

            if ($request->filled('salesperson_id') && $request->salesperson_id !== '') {
                if ($sp) {
                    $bill->salesperson_id = $sp->id;
                    $bill->salesman_name = $sp->name;
                }
            } elseif ($request->filled('salesman_name') && $request->salesman_name !== '') {
                $bill->salesman_name = $request->salesman_name;
                $spFind = Salesperson::where('name', $request->salesman_name)->first();
                $bill->salesperson_id = $spFind ? $spFind->id : null;
            }

            if ($bill->payment_type === 'Credit') {
                $salesmanForCredit = $bill->salesman_name ?: 'Field Representative';
                CreditCollection::updateOrCreate(
                    ['bill_id' => $bill->id],
                    [
                        'bill_no' => $bill->bill_no,
                        'customer_name' => $bill->customer_name,
                        'salesman_name' => $salesmanForCredit,
                        'bill_date' => $bill->business_date ?: $businessDate,
                        'due_date' => date('Y-m-d', strtotime(($bill->business_date ? $bill->business_date->format('Y-m-d') : $businessDate) . ' +7 days')),
                        'bill_amount' => $bill->net_amount > 0 ? $bill->net_amount : $bill->amount,
                        'paid_amount' => 0,
                        'outstanding_amount' => $bill->net_amount > 0 ? $bill->net_amount : $bill->amount,
                        'collection_status' => 'Pending',
                        'payment_mode' => 'Credit Pending',
                        'remark' => $bill->remark,
                    ]
                );
            }

            $bill->save();
            $updatedCount++;
        }

        // Update reconciliation metrics
        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('BULK_BILL_UPDATE', "Bulk updated {$updatedCount} bills.");

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updatedCount} bills.",
            'metrics' => $metrics
        ]);
    }

    public function resolveMissing(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
            'reason' => 'required|string',
            'remark' => 'nullable|string',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bill = Bill::where('bill_no', $request->bill_no)
            ->whereDate('business_date', $businessDate)
            ->firstOrFail();

        $bill->status = ($request->reason === 'Cancelled Bill') ? 'Cancelled' : 'Matched';
        $bill->remark = "Resolved: " . $request->reason . ($request->remark ? " - {$request->remark}" : "");
        $bill->verified_by = session('active_user.name', 'Pooja Verma');
        $bill->verified_at = now();
        $bill->save();

        // Update reconciliation state
        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('RESOLVE_MISSING', "Missing bill {$bill->bill_no} (₹{$bill->amount}) resolved via '{$request->reason}'. Status marked as {$bill->status}.");

        return response()->json([
            'success' => true,
            'message' => "Bill {$bill->bill_no} marked as {$bill->status}.",
            'metrics' => $metrics
        ]);
    }

    public function autoVerifyAll(Request $request)
    {
        $businessDate = $request->input('date');
        if (!$businessDate) {
            $businessDate = $this->reconService->getBusinessDate();
        }

        $query = Bill::where('status', '!=', 'Cancelled');
        if ($businessDate && $businessDate !== 'ALL') {
            $query->whereDate('business_date', $businessDate);
        }
        $bills = $query->get();

        foreach ($bills as $bill) {
            $bill->status = 'Matched';
            $bill->verified_by = session('active_user.name', 'Ramesh Sharma');
            $bill->verified_at = now();
            $bill->save();
        }

        $metrics = $this->reconService->getMetrics($businessDate !== 'ALL' ? $businessDate : null);
        if ($businessDate && $businessDate !== 'ALL') {
            $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
            if ($seal) {
                $seal->tally_total = $metrics['tallyTotal'];
                $seal->pso_total = $metrics['psoCollection'];
                $seal->difference = $metrics['difference'];
                $seal->is_reconciled = $metrics['isReconciled'];
                $seal->save();
            }
        }

        AuditLog::log('AUTO_VERIFY', "Auto-verified all physical bill slips for date {$businessDate}.");

        return redirect()->back()->with('success', 'All bills successfully auto-verified against physical bundles.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $businessDate = $request->input('date');
        if (!$businessDate) {
            $businessDate = $this->reconService->getBusinessDate();
        }

        $query = Bill::query();
        if ($businessDate && $businessDate !== 'ALL') {
            $query->whereDate('business_date', $businessDate);
        }
        $bills = $query->get();

        $filenameDate = $businessDate ?: 'All';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Bill_Verification_{$filenameDate}.csv\"",
        ];

        return response()->stream(function () use ($bills) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bill No', 'PSO', 'Sales Person', 'Date', 'Time', 'Customer', 'Amount', 'Payment Type', 'CD', 'Refund', 'Net Amount', 'Status', 'Remark', 'Verified By']);
            foreach ($bills as $b) {
                fputcsv($handle, [
                    $b->bill_no,
                    $b->pso_code,
                    $b->salesman_name ?? '—',
                    $b->business_date,
                    $b->bill_time,
                    $b->customer_name,
                    $b->amount,
                    $b->payment_type,
                    $b->cd_amount,
                    $b->refund_amount,
                    $b->net_amount,
                    $b->status,
                    $b->remark,
                    $b->verified_by
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
