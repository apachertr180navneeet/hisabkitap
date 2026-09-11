<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\CashDenomination;
use App\Models\Correction;
use App\Models\CreditCollection;
use App\Models\PsoConfig;
use App\Models\PsoDailySeal;
use App\Models\SystemSetting;
use App\Models\User;

class ReconciliationService
{
    /**
     * Get active business date (defaults to current date)
     */
    public function getBusinessDate(): string
    {
        return date('Y-m-d');
    }

    /**
     * Calculate all reconciliation aggregates for business date
     */
    public function getMetrics(?string $businessDate = null): array
    {
        $date = $businessDate ?: $this->getBusinessDate();

        $tallyTotal = 0;
        $psoCollection = 0;
        $pso1Total = 0;
        $pso2Total = 0;
        $pso3Total = 0;

        $totCash = 0;
        $totPaytm = 0;
        $totCheck = 0;
        $totCredit = 0;
        $totCancelled = 0;

        $totCd = 0;
        $totRefund = 0;

        $matchedCount = 0;
        $missingCount = 0;
        $cancelledCount = 0;
        $totalBillsCount = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('bills')) {
                $bills = Bill::whereDate('business_date', $date)
                    ->where('is_post_cutoff', false)
                    ->get();
                $totalBillsCount = $bills->count();

                foreach ($bills as $bill) {
                    $tallyTotal += (float) $bill->amount;
                    $totCd += (float) $bill->cd_amount;
                    $totRefund += (float) $bill->refund_amount;

                    if ($bill->status === 'Matched') {
                        $matchedCount++;
                    } elseif ($bill->status === 'Missing') {
                        $missingCount++;
                    } elseif ($bill->status === 'Cancelled') {
                        $cancelledCount++;
                    }

                    // Payment breakdown (handling split Cash + Paytm or standard payment_type)
                    $effectiveAmt = (float) ($bill->net_amount > 0 ? $bill->net_amount : $bill->amount);

                    if ($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0)) {
                        $totCash += (float) $bill->cash_amount;
                        $totPaytm += (float) $bill->paytm_amount;
                    } else {
                        if ($bill->payment_type === 'Cash') {
                            $totCash += (float) ($bill->cash_amount > 0 ? $bill->cash_amount : $effectiveAmt);
                        } elseif ($bill->payment_type === 'Paytm') {
                            $totPaytm += (float) ($bill->paytm_amount > 0 ? $bill->paytm_amount : $effectiveAmt);
                        } elseif ($bill->payment_type === 'Check') {
                            $totCheck += $effectiveAmt;
                        } elseif ($bill->payment_type === 'Credit') {
                            $totCredit += $effectiveAmt;
                        } elseif ($bill->payment_type === 'Cancelled') {
                            $totCancelled += (float) $bill->amount;
                        }
                    }

                    // PSO breakdown (Only if non-missing)
                    $psoAmt = ($bill->status === 'Missing') ? 0 : (float) $bill->net_amount;
                    $psoCollection += $psoAmt;

                    // Explicit breakdown for the standard PSO-1/2/3 counters
                    if ($bill->pso_code === 'PSO-1') {
                        $pso1Total += $psoAmt;
                    } elseif ($bill->pso_code === 'PSO-2') {
                        $pso2Total += $psoAmt;
                    } elseif ($bill->pso_code === 'PSO-3') {
                        $pso3Total += $psoAmt;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback during migration/setup
        }

        $expectedCollection = $tallyTotal - ($totCd + $totRefund + $totCancelled);
        $difference = $expectedCollection - $psoCollection;
        $hasBills = ($totalBillsCount > 0);
        $isReconciled = ($hasBills && $difference == 0 && $missingCount === 0);

        // Check daily seal state
        $seal = null;
        $isSealed = false;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pso_daily_seals')) {
                $seal = PsoDailySeal::whereDate('business_date', $date)->first();
                $isSealed = $seal ? (bool) $seal->is_sealed : false;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Dynamic database counts
        $activePsoCount = 0;
        $totalPsoCount = 0;
        $correctionsCount = 0;
        $creditRecordsCount = 0;
        $totalUsersCount = 0;
        $creditPending = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pso_configs')) {
                $activePsoCount = PsoConfig::where('is_active', true)->count();
                $totalPsoCount = PsoConfig::count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('corrections')) {
                $correctionsCount = Correction::count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('credit_collections')) {
                $creditRecordsCount = CreditCollection::where('outstanding_amount', '>', 0)->count();
                $creditPending = CreditCollection::whereDate('bill_date', $date)
                    ->where('outstanding_amount', '>', 0)
                    ->sum('outstanding_amount');
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $totalUsersCount = User::count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Cash Denominations Aggregates
        $totalPhysicalCash = 0;
        $totalKmCompleted = 0;
        $totalKmAllowance = 0;
        $totalShortCash = 0;
        $totalExcessCash = 0;
        $denominationCount = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('cash_denominations')) {
                $denominations = CashDenomination::whereDate('business_date', $date)->get();
                $totalPhysicalCash = (float) $denominations->sum('total_physical_cash');
                $totalKmCompleted = (float) $denominations->sum('total_km');
                $totalKmAllowance = (float) $denominations->sum('km_allowance_amount');
                $totalShortCash = (float) $denominations->sum('short_cash_amount');
                $totalExcessCash = (float) $denominations->sum('excess_cash_amount');
                $denominationCount = $denominations->count();
            }
        } catch (\Throwable $e) {
            // Graceful fallback during migration
        }

        // If no denominations recorded yet, calculate theoretical variance from book cash
        $cashVariance = $totalPhysicalCash > 0 ? ($totCash - $totalKmAllowance - $totalPhysicalCash) : 0;

        return [
            'businessDate' => $date,
            'tallyTotal' => $tallyTotal,
            'pso1Total' => $pso1Total,
            'pso2Total' => $pso2Total,
            'pso3Total' => $pso3Total,
            'psoCollection' => $psoCollection,
            'difference' => $difference,
            'isReconciled' => $isReconciled,
            'hasBills' => $hasBills,
            'isSealed' => $isSealed,
            'seal' => $seal,
            'totCash' => $totCash,
            'totPaytm' => $totPaytm,
            'totCheck' => $totCheck,
            'totCredit' => $totCredit,
            'totCancelled' => $totCancelled,
            'totCd' => $totCd,
            'totRefund' => $totRefund,
            'matchedCount' => $matchedCount,
            'missingCount' => $missingCount,
            'cancelledCount' => $cancelledCount,
            'totalBillsCount' => $totalBillsCount,
            'activePsoCount' => $activePsoCount,
            'totalPsoCount' => $totalPsoCount,
            'correctionsCount' => $correctionsCount,
            'creditRecordsCount' => $creditRecordsCount,
            'totalUsersCount' => $totalUsersCount,
            'creditPending' => (float) $creditPending,
            // Denomination & KM metrics
            'totalPhysicalCash' => $totalPhysicalCash,
            'totalKmCompleted' => $totalKmCompleted,
            'totalKmAllowance' => $totalKmAllowance,
            'totalShortCash' => $totalShortCash,
            'totalExcessCash' => $totalExcessCash,
            'denominationCount' => $denominationCount,
            'cashVariance' => $cashVariance,
        ];
    }
}
