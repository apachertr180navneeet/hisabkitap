<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Correction;
use App\Models\CreditCollection;
use App\Models\PsoConfig;
use App\Models\PsoDailySeal;
use App\Models\SystemSetting;
use App\Models\User;

class ReconciliationService
{
    /**
     * Get active business date
     */
    public function getBusinessDate(): string
    {
        return SystemSetting::getVal('business_date', '2026-08-14');
    }

    /**
     * Calculate all reconciliation aggregates for business date
     */
    public function getMetrics(?string $businessDate = null): array
    {
        $date = $businessDate ?: $this->getBusinessDate();

        $bills = Bill::whereDate('business_date', $date)
            ->where('is_post_cutoff', false)
            ->get();

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

            // Payment breakdown (using net_amount or amount)
            $effectiveAmt = (float) ($bill->net_amount > 0 ? $bill->net_amount : $bill->amount);

            if ($bill->payment_type === 'Cash') {
                $totCash += $effectiveAmt;
            } elseif ($bill->payment_type === 'Paytm') {
                $totPaytm += $effectiveAmt;
            } elseif ($bill->payment_type === 'Check') {
                $totCheck += $effectiveAmt;
            } elseif ($bill->payment_type === 'Credit') {
                $totCredit += $effectiveAmt;
            } elseif ($bill->payment_type === 'Cancelled') {
                $totCancelled += (float) $bill->amount;
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

        $expectedCollection = $tallyTotal - ($totCd + $totRefund + $totCancelled);
        $difference = $expectedCollection - $psoCollection;
        $hasBills = ($totalBillsCount > 0);
        $isReconciled = ($hasBills && $difference == 0 && $missingCount === 0);

        // Check daily seal state
        $seal = PsoDailySeal::whereDate('business_date', $date)->first();
        $isSealed = $seal ? (bool) $seal->is_sealed : false;

        // Dynamic database counts
        $activePsoCount = PsoConfig::where('is_active', true)->count();
        $totalPsoCount = PsoConfig::count();
        $correctionsCount = Correction::count();
        $creditRecordsCount = CreditCollection::where('outstanding_amount', '>', 0)->count();
        $totalUsersCount = User::count();

        // Pending credit calculation
        $creditPending = CreditCollection::whereDate('bill_date', $date)
            ->where('outstanding_amount', '>', 0)
            ->sum('outstanding_amount');

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
        ];
    }
}
