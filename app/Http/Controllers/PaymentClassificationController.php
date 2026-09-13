<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\PsoConfig;
use App\Services\ReconciliationService;

class PaymentClassificationController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index(Request $request)
    {
        // 1. Determine Business Date with robust fallback
        $businessDate = $request->query('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            if (Bill::whereDate('business_date', $defaultDate)->exists()) {
                $businessDate = $defaultDate;
            } else {
                $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
                $businessDate = $latestBillDate ? (is_string($latestBillDate) ? substr($latestBillDate, 0, 10) : $latestBillDate->format('Y-m-d')) : $defaultDate;
            }
        }

        // Available dates in DB for quick date-picker dropdown
        $billDates = Bill::selectRaw('DISTINCT business_date')->whereNotNull('business_date')->pluck('business_date')->toArray();
        $allDates = array_unique(array_filter($billDates));
        rsort($allDates);
        $availableDates = array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : (is_object($d) ? $d->format('Y-m-d') : substr((string)$d, 0, 10)), $allDates);

        // 2. Base Query for Bills
        $query = Bill::query();

        if ($businessDate && $businessDate !== 'ALL') {
            $query->whereDate('business_date', $businessDate);
        }

        // Cutoff handling
        if ($request->input('cutoff') === 'post') {
            $query->where('is_post_cutoff', true);
        } elseif ($request->input('cutoff') === 'regular') {
            $query->where('is_post_cutoff', false);
        } elseif ($request->input('cutoff') === 'all') {
            // no cutoff filter
        } else {
            $hasRegular = (clone $query)->where('is_post_cutoff', false)->exists();
            if ($hasRegular) {
                $query->where('is_post_cutoff', false);
            }
        }

        // PSO Filter
        if ($request->filled('pso') && $request->pso !== 'ALL') {
            $query->where('pso_code', $request->pso);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('bill_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('salesman_name', 'like', "%{$search}%")
                  ->orWhere('pso_code', 'like', "%{$search}%");
            });
        }

        // Calculate card metrics based on current date & pso scope (before paytype filter)
        $metricsQuery = clone $query;
        $scopeBills = $metricsQuery->get();

        $totCash = 0;
        $totPaytm = 0;
        $totCheck = 0;
        $totCredit = 0;
        $totCancelled = 0;
        $countCash = 0;
        $countPaytm = 0;
        $countCheck = 0;
        $countCredit = 0;
        $countCancelled = 0;
        $countSplit = 0;

        foreach ($scopeBills as $b) {
            $calculatedNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
            $effectiveAmt = (float) ($b->net_amount > 0 ? $b->net_amount : $calculatedNet);

            if ($b->is_split_payment || ($b->cash_amount > 0 && $b->paytm_amount > 0)) {
                $totCash += (float) $b->cash_amount;
                $totPaytm += (float) $b->paytm_amount;
                $countSplit++;
                if ($b->cash_amount > 0) $countCash++;
                if ($b->paytm_amount > 0) $countPaytm++;
            } else {
                if ($b->payment_type === 'Cash') {
                    $totCash += $effectiveAmt;
                    $countCash++;
                } elseif ($b->payment_type === 'Paytm') {
                    $totPaytm += $effectiveAmt;
                    $countPaytm++;
                } elseif ($b->payment_type === 'Check' || $b->payment_type === 'Cheque') {
                    $totCheck += $effectiveAmt;
                    $countCheck++;
                } elseif ($b->payment_type === 'Credit') {
                    $totCredit += $effectiveAmt;
                    $countCredit++;
                } elseif ($b->payment_type === 'Cancelled') {
                    $totCancelled += (float) $b->amount;
                    $countCancelled++;
                }
            }
        }

        $metrics = [
            'totCash' => $totCash,
            'totPaytm' => $totPaytm,
            'totCheck' => $totCheck,
            'totCredit' => $totCredit,
            'totCancelled' => $totCancelled,
            'countCash' => $countCash,
            'countPaytm' => $countPaytm,
            'countCheck' => $countCheck,
            'countCredit' => $countCredit,
            'countCancelled' => $countCancelled,
            'countSplit' => $countSplit,
            'totalBillsCount' => $scopeBills->count(),
            'businessDate' => $businessDate,
        ];

        // 3. Payment Type Filter for Table listing
        if ($request->filled('paytype') && $request->paytype !== 'ALL') {
            $paytype = $request->paytype;
            if ($paytype === 'Cash') {
                $query->where(function ($q) {
                    $q->where('payment_type', 'Cash')
                      ->orWhere(function ($sub) {
                          $sub->where('is_split_payment', true)->where('cash_amount', '>', 0);
                      })
                      ->orWhere(function ($sub) {
                          $sub->where('cash_amount', '>', 0);
                      });
                });
            } elseif ($paytype === 'Paytm') {
                $query->where(function ($q) {
                    $q->where('payment_type', 'Paytm')
                      ->orWhere(function ($sub) {
                          $sub->where('is_split_payment', true)->where('paytm_amount', '>', 0);
                      })
                      ->orWhere(function ($sub) {
                          $sub->where('paytm_amount', '>', 0);
                      });
                });
            } elseif ($paytype === 'Check') {
                $query->where(function ($q) {
                    $q->where('payment_type', 'Check')
                      ->orWhere('payment_type', 'Cheque');
                });
            } elseif ($paytype === 'Credit') {
                $query->where('payment_type', 'Credit');
            } elseif ($paytype === 'Cancelled') {
                $query->where('payment_type', 'Cancelled');
            } elseif ($paytype === 'Split') {
                $query->where(function ($q) {
                    $q->where('is_split_payment', true)
                      ->orWhere(function ($sub) {
                          $sub->where('cash_amount', '>', 0)->where('paytm_amount', '>', 0);
                      });
                });
            } else {
                $query->where('payment_type', $paytype);
            }
        }

        $bills = $query->orderBy('id', 'asc')->get();

        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoList = $psoQuery->orderBy('code')->get();

        return view('payment.index', compact('bills', 'metrics', 'businessDate', 'availableDates', 'psoList'));
    }
}
