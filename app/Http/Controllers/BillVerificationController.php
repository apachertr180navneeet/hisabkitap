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
        $psoQuery = PsoConfig::query();
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }

        // Merge any distinct PSO codes from bills for the selected date
        if ($businessDate && $businessDate !== 'ALL') {
            $distinctBillPsos = Bill::whereNotNull('pso_code')
                ->whereDate('business_date', $businessDate)
                ->distinct()
                ->pluck('pso_code')
                ->filter()
                ->values()
                ->toArray();

            $psoQuery->where(function ($q) use ($businessDate, $distinctBillPsos) {
                $q->whereDate('business_date', $businessDate)
                  ->orWhere(function ($sub) use ($businessDate) {
                      $sub->whereNull('business_date')
                          ->whereDate('created_at', $businessDate);
                  });
                if (!empty($distinctBillPsos)) {
                    $q->orWhereIn('code', $distinctBillPsos);
                }
            });
        } else {
            $distinctBillPsos = Bill::whereNotNull('pso_code')
                ->distinct()
                ->pluck('pso_code')
                ->filter()
                ->values()
                ->toArray();
        }

        $allPsoConfigs = $psoQuery->orderBy('code')->get();

        $existingCodes = $allPsoConfigs->pluck('code')->toArray();
        $psoList = collect($allPsoConfigs);
        foreach ($distinctBillPsos as $dbp) {
            if (!empty($dbp) && !in_array($dbp, $existingCodes, true)) {
                $psoList->push((object)[
                    'id' => null,
                    'code' => $dbp,
                    'prefix' => $dbp,
                    'operator_name' => 'Imported PSO',
                    'driver_name' => null,
                    'gadi_number' => null,
                    'is_active' => true,
                    'is_closed' => true,
                ]);
            }
        }
        $psoList = $psoList->sortBy('code')->values();

        $manualBillPsos = PsoConfig::where('is_active', true)->orderBy('code')->get();
        if ($manualBillPsos->isEmpty()) {
            $manualBillPsos = PsoConfig::orderBy('code')->get();
        }

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

        $selectedPso = $request->input('pso', 'ALL');
        if ($selectedPso && $selectedPso !== 'ALL') {
            if (!$psoList->contains('code', $selectedPso)) {
                $selectedPso = 'ALL';
            } else {
                $query->where('pso_code', $selectedPso);
            }
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type') && $request->payment_type !== 'ALL') {
            if ($request->payment_type === 'Split') {
                $query->where(function ($q) {
                    $q->where('is_split_payment', true)
                      ->orWhere('payment_type', 'Split')
                      ->orWhere(function ($sub) {
                          $sub->where('cash_amount', '>', 0)->where('paytm_amount', '>', 0);
                      })
                      ->orWhere(function ($sub) {
                          $sub->where('cash_amount', '>', 0)->where('credit_amount', '>', 0);
                      })
                      ->orWhere(function ($sub) {
                          $sub->where('paytm_amount', '>', 0)->where('credit_amount', '>', 0);
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
                  ->orWhere('pso_code', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        // Order by PSO code first, then by id/bill_no so records are cleanly organized by PSO
        $bills = $query->orderBy('pso_code', 'asc')->orderBy('id', 'asc')->get();

        // Calculate overall metrics
        $metrics = $this->reconService->getMetrics($businessDate !== 'ALL' ? $businessDate : null);

        // If a specific PSO is selected, scope metrics specifically to that PSO
        if ($selectedPso && $selectedPso !== 'ALL') {
            $psoScopedBills = $bills;
            $metrics['totalBillsCount'] = $psoScopedBills->count();
            $metrics['tallyTotal'] = (float)$psoScopedBills->sum('amount');
            $metrics['totCd'] = (float)$psoScopedBills->sum('cd_amount');
            $metrics['totRefund'] = (float)$psoScopedBills->sum('refund_amount');
            $metrics['matchedCount'] = $psoScopedBills->where('status', 'Matched')->count();
            $metrics['missingCount'] = $psoScopedBills->where('status', 'Missing')->count();
            $metrics['cancelledCount'] = $psoScopedBills->where(fn($b) => $b->status === 'Cancelled' || $b->payment_type === 'Cancelled')->count();

            $psoCash = 0;
            $psoPaytm = 0;
            $psoCredit = 0;
            $psoCheck = 0;
            foreach ($psoScopedBills as $sb) {
                if ($sb->status === 'Cancelled' || $sb->payment_type === 'Cancelled') continue;
                if ($sb->is_split_payment || ($sb->cash_amount > 0 && $sb->paytm_amount > 0) || ($sb->credit_amount > 0 && ($sb->cash_amount > 0 || $sb->paytm_amount > 0))) {
                    $psoCash += (float)$sb->cash_amount;
                    $psoPaytm += (float)$sb->paytm_amount;
                    $psoCredit += (float)$sb->credit_amount;
                } else {
                    $eff = (float)($sb->net_amount > 0 ? $sb->net_amount : max(0, $sb->amount - $sb->cd_amount - $sb->refund_amount));
                    if ($sb->payment_type === 'Cash') $psoCash += $eff;
                    elseif ($sb->payment_type === 'Paytm') $psoPaytm += $eff;
                    elseif ($sb->payment_type === 'Credit') $psoCredit += $eff;
                    elseif ($sb->payment_type === 'Check' || $sb->payment_type === 'Cheque') $psoCheck += $eff;
                }
            }
            $metrics['totCash'] = $psoCash;
            $metrics['totPaytm'] = $psoPaytm;
            $metrics['totCredit'] = $psoCredit;
            $metrics['totCheck'] = $psoCheck;
            $metrics['psoCollection'] = $psoCash + $psoPaytm + $psoCredit + $psoCheck;
            $metrics['difference'] = $metrics['tallyTotal'] - ($metrics['totCd'] + $metrics['totRefund']) - $metrics['psoCollection'];
        }

        // Group bills by PSO for clean visual sections
        $billsByPso = $bills->groupBy(fn($b) => $b->pso_code ?: 'Unassigned PSO');

        // Calculate per-PSO subtotals
        $psoSubtotals = [];
        foreach ($billsByPso as $pCode => $pBills) {
            $psoObj = $psoList->firstWhere('code', $pCode);
            $psoSubtotals[$pCode] = [
                'pso_obj' => $psoObj,
                'count' => $pBills->count(),
                'amount' => (float)$pBills->sum('amount'),
                'cd' => (float)$pBills->sum('cd_amount'),
                'cd_amount' => (float)$pBills->sum('cd_amount'),
                'refund' => (float)$pBills->sum('refund_amount'),
                'refund_amount' => (float)$pBills->sum('refund_amount'),
                'net' => (float)$pBills->where(fn($b) => $b->status !== 'Cancelled' && $b->payment_type !== 'Cancelled')->sum('net_amount'),
                'net_amount' => (float)$pBills->where(fn($b) => $b->status !== 'Cancelled' && $b->payment_type !== 'Cancelled')->sum('net_amount'),
                'cash' => (float)$pBills->sum('cash_amount'),
                'cash_amount' => (float)$pBills->sum('cash_amount'),
                'paytm' => (float)$pBills->sum('paytm_amount'),
                'paytm_amount' => (float)$pBills->sum('paytm_amount'),
                'credit' => (float)$pBills->sum('credit_amount'),
                'credit_amount' => (float)$pBills->sum('credit_amount'),
                'matched' => $pBills->where('status', 'Matched')->count(),
                'mismatches' => $pBills->where(fn($b) => $b->isSeriesMismatch() || $b->isPsoMismatch())->count(),
            ];
        }

        return view('verification.index', compact(
            'bills',
            'billsByPso',
            'psoSubtotals',
            'psoList',
            'manualBillPsos',
            'metrics',
            'salespersons',
            'businessDate',
            'availableDates',
            'selectedPso'
        ));
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
            'credit_amount' => 'nullable|numeric|min:0',
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

        if ($normalizedPaymentType === 'Cancelled') {
            $bill->is_split_payment = false;
            $bill->payment_type = 'Cancelled';
            $bill->status = 'Cancelled';
            $bill->cd_amount = 0;
            $bill->refund_amount = floatval($bill->amount);
            $bill->net_amount = 0;
            $bill->cash_amount = 0;
            $bill->paytm_amount = 0;
            $bill->credit_amount = 0;
        } else {
            $bill->cd_amount = floatval($request->input('cd_amount', 0));
            $bill->refund_amount = floatval($request->input('refund_amount', 0));
            $bill->net_amount = max(0, floatval($bill->amount) - $bill->cd_amount - $bill->refund_amount);

            // Handle Split Payment vs Standard Payment (Cash, Paytm, Credit, Cheque)
            $cashAmt = floatval($request->input('cash_amount', 0));
            $paytmAmt = floatval($request->input('paytm_amount', 0));
            $creditAmt = floatval($request->input('credit_amount', 0));
            $portionsCount = ($cashAmt > 0 ? 1 : 0) + ($paytmAmt > 0 ? 1 : 0) + ($creditAmt > 0 ? 1 : 0);
            $isSplit = (bool) $request->input('is_split_payment', false) || ($normalizedPaymentType === 'Split') || ($portionsCount > 1);

            if ($isSplit || $portionsCount > 1) {
                $bill->is_split_payment = true;
                $bill->cash_amount = $cashAmt;
                $bill->paytm_amount = $paytmAmt;
                $bill->credit_amount = $creditAmt;
                $bill->payment_type = 'Split';
            } elseif ($normalizedPaymentType === 'Paytm' || ($paytmAmt > 0 && $cashAmt == 0 && $creditAmt == 0)) {
                $bill->is_split_payment = false;
                $bill->payment_type = 'Paytm';
                $bill->paytm_amount = $bill->net_amount;
                $bill->cash_amount = 0;
                $bill->credit_amount = 0;
            } elseif ($normalizedPaymentType === 'Credit' || ($creditAmt > 0 && $cashAmt == 0 && $paytmAmt == 0)) {
                $bill->is_split_payment = false;
                $bill->payment_type = 'Credit';
                $bill->credit_amount = $bill->net_amount;
                $bill->cash_amount = 0;
                $bill->paytm_amount = 0;
            } elseif ($normalizedPaymentType === 'Check') {
                $bill->is_split_payment = false;
                $bill->payment_type = 'Check';
                $bill->cash_amount = 0;
                $bill->paytm_amount = 0;
                $bill->credit_amount = 0;
            } else {
                // Default: Cash
                $bill->is_split_payment = false;
                $bill->payment_type = 'Cash';
                $bill->cash_amount = $bill->net_amount;
                $bill->paytm_amount = 0;
                $bill->credit_amount = 0;
            }
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

        // Sync with CreditCollection if Credit or Split with Credit portion
        $effectiveCreditAmount = ($bill->payment_type === 'Credit') ? (float)$bill->net_amount : (float)$bill->credit_amount;
        if ($effectiveCreditAmount > 0) {
            $salesmanForCredit = $bill->salesman_name ?: 'Field Representative';
            $billDateStr = $bill->business_date ? (is_string($bill->business_date) ? substr($bill->business_date, 0, 10) : $bill->business_date->format('Y-m-d')) : $businessDate;
            CreditCollection::updateOrCreate(
                ['bill_id' => $bill->id],
                [
                    'bill_no' => $bill->bill_no,
                    'customer_name' => $bill->customer_name,
                    'salesman_name' => $salesmanForCredit,
                    'bill_date' => $billDateStr,
                    'due_date' => date('Y-m-d', strtotime($billDateStr . ' +7 days')),
                    'bill_amount' => $effectiveCreditAmount,
                    'paid_amount' => 0,
                    'outstanding_amount' => $effectiveCreditAmount,
                    'collection_status' => 'Pending',
                    'payment_mode' => $bill->is_split_payment ? 'Split Credit' : 'Credit Pending',
                    'remark' => $bill->remark,
                ]
            );
        } else {
            CreditCollection::where('bill_id', $bill->id)->where('collection_status', 'Pending')->delete();
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

        AuditLog::log('BILL_INLINE_UPDATE', "Updated bill {$bill->bill_no}: Payment={$bill->payment_type}, CD=₹{$bill->cd_amount}, Refund=₹{$bill->refund_amount}, Cash=₹{$bill->cash_amount}, Paytm=₹{$bill->paytm_amount}, Credit=₹{$bill->credit_amount}");

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
                'credit_amount' => number_format($bill->credit_amount, 2, '.', ''),
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
            'credit_amount' => 'nullable|numeric|min:0',
            'cd_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string|max:255',
            'remark' => 'nullable|string',
        ]);

        $pso = PsoConfig::where('code', $request->pso_code)->first()
            ?: PsoConfig::where('id', $request->pso_code)->first();
        $amount = floatval($request->amount);
        $cdAmount = floatval($request->input('cd_amount', 0));
        $refundAmount = floatval($request->input('refund_amount', 0));
        $netAmount = max(0, $amount - $cdAmount - $refundAmount);

        $paymentType = $request->payment_type;
        $cashAmount = floatval($request->input('cash_amount', 0));
        $paytmAmount = floatval($request->input('paytm_amount', 0));
        $creditAmount = floatval($request->input('credit_amount', 0));
        $portionsCount = ($cashAmount > 0 ? 1 : 0) + ($paytmAmount > 0 ? 1 : 0) + ($creditAmount > 0 ? 1 : 0);
        $isSplit = ($paymentType === 'Split') || ($portionsCount > 1);

        if ($paymentType === 'Cancelled') {
            $cdAmount = 0;
            $refundAmount = $amount;
            $netAmount = 0;
            $cashAmount = 0;
            $paytmAmount = 0;
            $creditAmount = 0;
            $isSplit = false;
            $billStatus = 'Cancelled';
        } elseif ($isSplit) {
            $isSplit = true;
            $paymentType = 'Split';
            if ($cashAmount == 0 && $paytmAmount == 0 && $creditAmount == 0) {
                $cashAmount = $netAmount;
            }
        } elseif ($paymentType === 'Cash') {
            $cashAmount = $netAmount;
            $paytmAmount = 0;
            $creditAmount = 0;
        } elseif ($paymentType === 'Paytm') {
            $paytmAmount = $netAmount;
            $cashAmount = 0;
            $creditAmount = 0;
        } elseif ($paymentType === 'Credit') {
            $creditAmount = $netAmount;
            $cashAmount = 0;
            $paytmAmount = 0;
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

        // Validate bill number against chosen PSO series range & duplicate check
        $expectedSeries = $pso ? $pso->formatted_series_summary : null;
        $validation = $pso ? $pso->validateBillNumber($request->bill_no, $request->business_date) : ['valid' => true, 'mismatch_type' => null, 'expected_series' => $expectedSeries, 'details' => ''];
        $mismatchStatus = $validation['valid'] ? null : $validation['details'];
        $billStatus = $validation['valid'] ? 'Matched' : $validation['mismatch_type'];

        $bill = Bill::create([
            'bill_no' => $request->bill_no,
            'pso_config_id' => $pso ? $pso->id : null,
            'pso_code' => $request->pso_code,
            'business_date' => $request->business_date,
            'bill_time' => $request->input('bill_time', date('H:i')),
            'customer_name' => $request->customer_name,
            'particulars' => $request->customer_name,
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
            'credit_amount' => $creditAmount,
            'is_split_payment' => $isSplit,
            'status' => $billStatus,
            'expected_series' => $validation['expected_series'],
            'mismatch_status' => $mismatchStatus,
            'is_expected' => true,
            'tally_found' => true,
            'is_post_cutoff' => false,
            'remark' => $request->input('remark') ?: (!$validation['valid'] ? $validation['details'] : 'Manual bill entry added via ERP'),
            'verified_by' => session('active_user.name', 'Pooja Verma'),
            'verified_at' => now(),
        ]);

        $effectiveCredit = ($paymentType === 'Credit') ? $netAmount : $creditAmount;
        if ($effectiveCredit > 0) {
            CreditCollection::create([
                'bill_id' => $bill->id,
                'bill_no' => $bill->bill_no,
                'customer_name' => $bill->customer_name,
                'salesman_name' => $salesmanName ?: 'Field Representative',
                'bill_date' => $bill->business_date,
                'due_date' => date('Y-m-d', strtotime($bill->business_date . ' +7 days')),
                'bill_amount' => $effectiveCredit,
                'paid_amount' => 0,
                'outstanding_amount' => $effectiveCredit,
                'collection_status' => 'Pending',
                'payment_mode' => $isSplit ? 'Split Credit' : 'Credit Pending',
                'remark' => $bill->remark,
            ]);
        }

        AuditLog::log('MANUAL_BILL_CREATED', "Added manual bill {$bill->bill_no} for ₹{$bill->amount} ({$paymentType}) on {$bill->business_date}" . (!$validation['valid'] ? " [Flagged: {$billStatus}]" : ""));

        if (!$validation['valid']) {
            return redirect()->back()->with('warning', "Manual bill {$bill->bill_no} added but marked as '{$billStatus}' ({$validation['details']}). It requires authorized approval before reconciliation.");
        }

        return redirect()->back()->with('success', "Manual bill {$bill->bill_no} added successfully!");
    }

    /**
     * Approve a Bill Series Mismatch or Duplicate / PSO Mismatch (Mandatory Reason Required)
     */
    public function approveMismatch(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'reason' => 'required|string|min:3',
        ], [
            'reason.required' => 'A mandatory justification reason must be provided to approve a mismatch.',
            'reason.min' => 'The approval reason must be at least 3 characters long.',
        ]);

        $bill = Bill::findOrFail($request->bill_id);
        $approverName = auth()->user()?->name ?? session('active_user.name', 'Authorized Officer');
        $oldStatus = $bill->status;

        $bill->is_mismatch_approved = true;
        $bill->mismatch_approved_by = $approverName;
        $bill->mismatch_approved_at = now();
        $bill->mismatch_approval_reason = trim($request->reason);
        $bill->verified_by = $approverName;
        $bill->verified_at = now();
        $bill->remark = "Approved Mismatch: {$request->reason}" . ($bill->remark ? " (Orig: {$bill->remark})" : "");
        $bill->save();

        $businessDate = $bill->business_date ? $bill->business_date->format('Y-m-d') : $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('MISMATCH_APPROVED', "Approved mismatch on bill {$bill->bill_no} (PSO: {$bill->pso_code}, was: {$oldStatus}) by {$approverName}. Reason: {$request->reason}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Bill {$bill->bill_no} mismatch successfully approved.",
                'bill' => $bill,
                'metrics' => $metrics,
            ]);
        }

        return redirect()->back()->with('success', "Mismatch for bill '{$bill->bill_no}' has been officially approved. Bill is now valid for PSO {$bill->pso_code}.");
    }

    /**
     * Reject a Bill Series Mismatch or Duplicate / PSO Mismatch (Mandatory Reason Required)
     */
    public function rejectMismatch(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'reason' => 'required|string|min:3',
        ], [
            'reason.required' => 'A mandatory rejection reason must be provided.',
            'reason.min' => 'The rejection reason must be at least 3 characters long.',
        ]);

        $bill = Bill::findOrFail($request->bill_id);
        $officerName = auth()->user()?->name ?? session('active_user.name', 'Authorized Officer');
        $oldStatus = $bill->status;

        $bill->is_mismatch_approved = false;
        $bill->mismatch_status = 'Rejected';
        $bill->mismatch_rejected_by = $officerName;
        $bill->mismatch_rejected_at = now();
        $bill->mismatch_rejection_reason = trim($request->reason);
        $bill->status = 'Cancelled';
        $bill->remark = "Rejected Mismatch: {$request->reason}" . ($bill->remark ? " (Orig: {$bill->remark})" : "");
        $bill->save();

        $businessDate = $bill->business_date ? $bill->business_date->format('Y-m-d') : $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('MISMATCH_REJECTED', "Rejected mismatch on bill {$bill->bill_no} (PSO: {$bill->pso_code}, was: {$oldStatus}) by {$officerName}. Status marked Cancelled. Reason: {$request->reason}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Bill {$bill->bill_no} mismatch rejected and marked as Cancelled.",
                'bill' => $bill,
                'metrics' => $metrics,
            ]);
        }

        return redirect()->back()->with('warning', "Mismatch for bill '{$bill->bill_no}' was rejected and marked as Cancelled.");
    }

    /**
     * Revalidate and sync all bills against PSO series for the selected business date
     */
    public function revalidateSeries(Request $request)
    {
        $businessDate = $request->input('date') ?: $this->reconService->getBusinessDate();
        $res = $this->reconService->validateAndSyncAllBills($businessDate);

        AuditLog::log('SERIES_REVALIDATE', "Revalidated all bills for date {$businessDate}. Scanned: {$res['total_scanned']}, Mismatches found: {$res['mismatches_found']}");

        return redirect()->back()->with('success', "Revalidated {$res['total_scanned']} bills. {$res['mismatches_found']} mismatch(es) identified.");
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
                if ($normalizedPaymentType === 'Cancelled') {
                    $bill->is_split_payment = false;
                    $bill->payment_type = 'Cancelled';
                    $bill->status = 'Cancelled';
                    $bill->cd_amount = 0;
                    $bill->refund_amount = floatval($bill->amount);
                    $bill->net_amount = 0;
                    $bill->cash_amount = 0;
                    $bill->paytm_amount = 0;
                } elseif ($normalizedPaymentType === 'Split') {
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

        if ($request->reason === 'Cancelled Bill') {
            $bill->status = 'Cancelled';
            $bill->payment_type = 'Cancelled';
            $bill->cd_amount = 0;
            $bill->refund_amount = floatval($bill->amount);
            $bill->net_amount = 0;
            $bill->cash_amount = 0;
            $bill->paytm_amount = 0;
            $bill->is_split_payment = false;
        } else {
            $bill->status = 'Matched';
        }
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
            fputcsv($handle, ['Bill No', 'PSO', 'Sales Person', 'Date', 'Time', 'Customer', 'Amount', 'Payment Type', 'CD', 'Refund', 'Net Amount', 'Status', 'Expected Series', 'Mismatch Status', 'Approved By', 'Remark', 'Verified By']);
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
                    $b->expected_series ?? '—',
                    $b->mismatch_status ?? '—',
                    $b->mismatch_approved_by ?? '—',
                    $b->remark,
                    $b->verified_by
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
