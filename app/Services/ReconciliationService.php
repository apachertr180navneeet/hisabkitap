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
     * Standardize any date input into YYYY-MM-DD format
     */
    public function normalizeDate(?string $date): string
    {
        if (empty($date) || $date === 'ALL') {
            return $date ?? '';
        }
        $date = trim($date);
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : $date;
    }

    /**
     * Get active business date (setting date or today, or requested date)
     */
    public function getBusinessDate(?string $requestedDate = null): string
    {
        if ($requestedDate && $requestedDate !== 'ALL') {
            return $this->normalizeDate($requestedDate);
        }

        $settingDate = SystemSetting::getVal('business_date');
        if ($settingDate) {
            return $this->normalizeDate(is_string($settingDate) ? substr($settingDate, 0, 10) : (is_object($settingDate) ? $settingDate->format('Y-m-d') : (string)$settingDate));
        }

        return date('Y-m-d');
    }

    /**
     * Calculate all reconciliation aggregates for business date
     */
    public function getMetrics(?string $businessDate = null): array
    {
        $date = $this->getBusinessDate($businessDate);

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

        $seriesMismatchCount = 0;
        $psoMismatchCount = 0;
        $unapprovedMismatchCount = 0;
        $approvedMismatchCount = 0;
        $mismatchBills = [];
        $psoBreakdown = [];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('bills')) {
                $bills = Bill::with('psoConfig')
                    ->whereDate('business_date', $date)
                    ->where('is_post_cutoff', false)
                    ->get();
                $totalBillsCount = $bills->count();

                foreach ($bills as $bill) {
                    $isCancelled = ($bill->status === 'Cancelled' || $bill->payment_type === 'Cancelled');
                    $calculatedNet = $isCancelled ? 0 : max(0, (float)$bill->amount - (float)$bill->cd_amount - (float)$bill->refund_amount);
                    $effectiveAmt = (float) ($isCancelled ? 0 : ($bill->net_amount > 0 ? $bill->net_amount : $calculatedNet));

                    $tallyTotal += (float) $bill->amount;
                    if ($isCancelled) {
                        $totCancelled += (float) $bill->amount;
                    } else {
                        $totCd += (float) $bill->cd_amount;
                        $totRefund += (float) $bill->refund_amount;
                    }

                    $isMismatch = $bill->isSeriesMismatch() || $bill->isPsoMismatch() || in_array($bill->status, ['Bill Series Mismatch', 'Duplicate / PSO Mismatch', 'Mismatch']) || (bool)$bill->is_mismatch_approved || !empty($bill->mismatch_rejected_by);
                    $isApproved = $bill->isMismatchApproved();

                    if ($isMismatch) {
                        if ($bill->isPsoMismatch()) {
                            $psoMismatchCount++;
                        } else {
                            $seriesMismatchCount++;
                        }

                        if ($isApproved) {
                            $approvedMismatchCount++;
                        } else {
                            $unapprovedMismatchCount++;
                        }

                        $mismatchBills[] = (object) [
                            'id' => $bill->id,
                            'pso_code' => $bill->pso_code,
                            'bill_no' => $bill->bill_no,
                            'expected_series' => $bill->expected_series ?: ($bill->psoConfig?->formatted_series_summary ?? '—'),
                            'entered_bill_no' => $bill->bill_no,
                            'status' => $bill->status,
                            'mismatch_status' => $bill->isPsoMismatch() ? 'Duplicate / PSO Mismatch' : ($bill->mismatch_status ?: 'Bill Series Mismatch'),
                            'amount' => (float)$bill->amount,
                            'net_amount' => (float)$effectiveAmt,
                            'customer_name' => $bill->customer_name,
                            'salesman_name' => $bill->salesman_name,
                            'is_mismatch_approved' => $isApproved,
                            'is_approved' => $isApproved,
                            'mismatch_approved_by' => $bill->mismatch_approved_by,
                            'mismatch_approved_at' => $bill->mismatch_approved_at,
                            'mismatch_approval_reason' => $bill->mismatch_approval_reason,
                            'mismatch_rejected_by' => $bill->mismatch_rejected_by,
                            'mismatch_rejected_at' => $bill->mismatch_rejected_at,
                            'rejection_reason' => $bill->mismatch_rejection_reason,
                            'remark' => $bill->remark,
                        ];
                    }

                    if ($bill->status === 'Matched') {
                        $matchedCount++;
                    } elseif ($bill->status === 'Missing') {
                        $missingCount++;
                    } elseif ($bill->status === 'Cancelled' || $bill->payment_type === 'Cancelled') {
                        $cancelledCount++;
                    }

                    // Payment breakdown (handling split Cash + Paytm or standard payment_type)
                    if (!$isCancelled) {
                        if ($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0)) {
                            $totCash += (float) $bill->cash_amount;
                            $totPaytm += (float) $bill->paytm_amount;
                        } else {
                            if ($bill->payment_type === 'Cash') {
                                $totCash += $effectiveAmt;
                            } elseif ($bill->payment_type === 'Paytm') {
                                $totPaytm += $effectiveAmt;
                            } elseif ($bill->payment_type === 'Check') {
                                $totCheck += $effectiveAmt;
                            } elseif ($bill->payment_type === 'Credit') {
                                $totCredit += $effectiveAmt;
                            }
                        }
                    }

                    // PSO breakdown (Only if valid for reconciliation: non-missing, non-cancelled, non-unapproved mismatch)
                    $isValid = $bill->isValidForReconciliation();
                    $psoAmt = $isValid ? $effectiveAmt : 0;
                    $psoCollection += $psoAmt;

                    // Standard PSO code mapping
                    if ($isValid) {
                        if ($bill->pso_code === 'PSO-1' || $bill->pso_code === 'PSO-01' || $bill->pso_code === 'PSO 1') {
                            $pso1Total += $psoAmt;
                        } elseif ($bill->pso_code === 'PSO-2' || $bill->pso_code === 'PSO-02' || $bill->pso_code === 'PSO 2') {
                            $pso2Total += $psoAmt;
                        } elseif ($bill->pso_code === 'PSO-3' || $bill->pso_code === 'PSO-03' || $bill->pso_code === 'PSO 3') {
                            $pso3Total += $psoAmt;
                        }
                    }
                }

                // Dynamic breakdown for all configured PSOs
                if (\Illuminate\Support\Facades\Schema::hasTable('pso_configs')) {
                    $configs = PsoConfig::orderBy('code')->get();
                    foreach ($configs as $cfg) {
                        $cfgBills = $bills->where('pso_code', $cfg->code);
                        $cfgAmt = 0;
                        $cfgCount = $cfgBills->count();
                        foreach ($cfgBills as $cb) {
                            if ($cb->isValidForReconciliation()) {
                                $isCancelled = ($cb->status === 'Cancelled' || $cb->payment_type === 'Cancelled');
                                if (!$isCancelled) {
                                    $cNet = max(0, (float)$cb->amount - (float)$cb->cd_amount - (float)$cb->refund_amount);
                                    $cfgAmt += (float) ($cb->net_amount > 0 ? $cb->net_amount : $cNet);
                                }
                            }
                        }
                        $psoBreakdown[] = [
                            'pso' => $cfg,
                            'code' => $cfg->code,
                            'name' => $cfg->name,
                            'series_summary' => $cfg->formatted_series_summary ?? '',
                            'total' => $cfgAmt,
                            'bills_count' => $cfgCount,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback during migration/setup
        }

        $expectedCollection = $tallyTotal - ($totCd + $totRefund + $totCancelled);
        $difference = $expectedCollection - $psoCollection;
        $hasBills = ($totalBillsCount > 0);
        $isReconciled = ($hasBills && $difference == 0 && $missingCount === 0 && $unapprovedMismatchCount === 0);

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
            // Mismatch metrics
            'seriesMismatchCount' => $seriesMismatchCount,
            'psoMismatchCount' => $psoMismatchCount,
            'unapprovedMismatchCount' => $unapprovedMismatchCount,
            'approvedMismatchCount' => $approvedMismatchCount,
            'mismatchBills' => $mismatchBills,
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
            'psoBreakdown' => $psoBreakdown,
        ];
    }

    /**
     * Automatically scan and validate all bills for the given business date against PSO series
     */
    public function validateAndSyncAllBills(?string $businessDate = null): array
    {
        $date = $businessDate ?: $this->getBusinessDate();
        $bills = Bill::whereDate('business_date', $date)->get();
        $psoConfigs = PsoConfig::all()->keyBy('code');

        $validatedCount = 0;
        $mismatchFoundCount = 0;

        foreach ($bills as $bill) {
            // If already approved, preserve approval
            if ($bill->isMismatchApproved()) {
                continue;
            }

            $pso = $psoConfigs->get($bill->pso_code)
                ?: PsoConfig::where('id', $bill->pso_config_id)->first();

            if (!$pso) {
                continue;
            }

            $validation = $pso->validateBillNumber($bill->bill_no, $date, $bill->id);
            $bill->expected_series = $validation['expected_series'];

            if (!$validation['valid']) {
                $bill->status = $validation['mismatch_type'];
                $bill->mismatch_status = $validation['mismatch_type'];
                $mismatchFoundCount++;
            } else {
                if (in_array($bill->status, ['Bill Series Mismatch', 'Duplicate / PSO Mismatch', 'Mismatch'])) {
                    $bill->status = 'Matched';
                    $bill->mismatch_status = null;
                }
            }

            $bill->save();
            $validatedCount++;
        }

        return [
            'total_scanned' => $validatedCount,
            'mismatches_found' => $mismatchFoundCount,
        ];
    }
}
