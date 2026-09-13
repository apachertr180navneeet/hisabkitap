<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\CashDenomination;
use App\Models\PsoConfig;
use App\Models\Bill;
use App\Models\AuditLog;
use App\Services\ReconciliationService;

class CashDenominationController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    /**
     * Display the Cash Denomination & Driver Reconciliation sheet
     */
    public function index(Request $request)
    {
        $businessDate = $request->query('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            if (Bill::whereDate('business_date', $defaultDate)->exists() || CashDenomination::whereDate('business_date', $defaultDate)->exists()) {
                $businessDate = $defaultDate;
            } else {
                $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
                $latestDenomDate = CashDenomination::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
                $latestDate = $latestBillDate ?: $latestDenomDate;
                $businessDate = $latestDate ? date('Y-m-d', strtotime($latestDate)) : $defaultDate;
            }
        }

        $selectedPso = $request->query('pso', 'ALL');
        $metrics = $this->reconService->getMetrics($businessDate);

        $user = auth()->user();
        $psoQuery = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $psoQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoList = $psoQuery->orderBy('code')->get();

        // Query existing denominations for this date
        $denominationsQuery = CashDenomination::whereDate('business_date', $businessDate);
        if ($selectedPso !== 'ALL') {
            $denominationsQuery->where('pso_code', $selectedPso);
        }
        $denominations = $denominationsQuery->orderBy('id', 'desc')->get();

        // Calculate Book Cash for the selected scope
        $billsQuery = Bill::whereDate('business_date', $businessDate)->where('is_post_cutoff', false);
        if ($selectedPso !== 'ALL') {
            $billsQuery->where('pso_code', $selectedPso);
        }
        $bills = $billsQuery->get();

        $scopedBookCash = 0;
        $scopedPaytm = 0;
        $scopedTotalBills = 0;

        foreach ($bills as $b) {
            $scopedTotalBills += (float) $b->amount;
            $calculatedNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
            $effective = (float) ($b->net_amount > 0 ? $b->net_amount : $calculatedNet);

            if ($b->is_split_payment || ($b->cash_amount > 0 && $b->paytm_amount > 0)) {
                $scopedBookCash += (float) $b->cash_amount;
                $scopedPaytm += (float) $b->paytm_amount;
            } elseif ($b->payment_type === 'Cash') {
                $scopedBookCash += $effective;
            } elseif ($b->payment_type === 'Paytm') {
                $scopedPaytm += $effective;
            }
        }

        // Build a complete per-PSO book cash map for dynamic client-side reconciliation
        $allBills = Bill::whereDate('business_date', $businessDate)->where('is_post_cutoff', false)->get();
        $psoBookCashMap = [];
        $totalAllCash = 0;

        foreach ($psoList as $psoItem) {
            $psoCash = 0;
            $psoBills = $allBills->where('pso_code', $psoItem->code);
            foreach ($psoBills as $b) {
                $calculatedNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
                $effective = (float) ($b->net_amount > 0 ? $b->net_amount : $calculatedNet);
                if ($b->is_split_payment || ($b->cash_amount > 0 && $b->paytm_amount > 0)) {
                    $psoCash += (float) $b->cash_amount;
                } elseif ($b->payment_type === 'Cash') {
                    $psoCash += $effective;
                }
            }
            $psoBookCashMap[$psoItem->code] = round($psoCash, 2);
        }

        foreach ($allBills as $b) {
            $calculatedNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
            $effective = (float) ($b->net_amount > 0 ? $b->net_amount : $calculatedNet);
            if ($b->is_split_payment || ($b->cash_amount > 0 && $b->paytm_amount > 0)) {
                $totalAllCash += (float) $b->cash_amount;
            } elseif ($b->payment_type === 'Cash') {
                $totalAllCash += $effective;
            }
        }
        $psoBookCashMap['ALL'] = round($totalAllCash, 2);
        $psoBookCashMap[''] = round($totalAllCash, 2);

        $scopedCountedCash = (float) $denominations->sum('total_physical_cash');
        $scopedKmAllowance = (float) $denominations->sum('km_allowance_amount');
        $scopedKmCompleted = (float) $denominations->sum('total_km');
        $scopedShortCash = (float) $denominations->sum('short_cash_amount');
        $scopedDenomCount = $denominations->count();

        $activeDenom = null;
        if ($selectedPso !== 'ALL') {
            $activeDenom = $denominations->where('pso_code', $selectedPso)->first();
        }

        // Available dates from bills and denominations
        $billDates = Bill::selectRaw('DISTINCT business_date')->whereNotNull('business_date')->pluck('business_date')->toArray();
        $denomDates = CashDenomination::selectRaw('DISTINCT business_date')->whereNotNull('business_date')->pluck('business_date')->toArray();
        $allDates = array_unique(array_filter(array_merge($billDates, $denomDates)));
        rsort($allDates);
        $availableDates = array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'), $allDates);

        return view('denomination.index', compact(
            'businessDate',
            'selectedPso',
            'metrics',
            'psoList',
            'psoBookCashMap',
            'denominations',
            'activeDenom',
            'scopedBookCash',
            'scopedPaytm',
            'scopedTotalBills',
            'scopedCountedCash',
            'scopedKmAllowance',
            'scopedKmCompleted',
            'scopedShortCash',
            'scopedDenomCount',
            'availableDates'
        ));
    }

    /**
     * Save a new Cash Denomination & Driver Handover Record
     */
    public function store(Request $request)
    {
        $request->validate([
            'business_date' => 'required|date',
            'pso_code' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'gadi_number' => 'nullable|string|max:50',
            'notes_500' => 'nullable|integer|min:0',
            'notes_200' => 'nullable|integer|min:0',
            'notes_100' => 'nullable|integer|min:0',
            'notes_50' => 'nullable|integer|min:0',
            'notes_20' => 'nullable|integer|min:0',
            'notes_10' => 'nullable|integer|min:0',
            'coins_total' => 'nullable|numeric|min:0',
            'total_km' => 'nullable|numeric|min:0',
            'km_rate' => 'nullable|numeric|min:0',
            'book_cash_amount' => 'nullable|numeric|min:0',
            'cashier_name' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $n500  = (int) $request->input('notes_500', 0);
        $n200  = (int) $request->input('notes_200', 0);
        $n100  = (int) $request->input('notes_100', 0);
        $n50   = (int) $request->input('notes_50', 0);
        $n20   = (int) $request->input('notes_20', 0);
        $n10   = (int) $request->input('notes_10', 0);
        $coins = (float) $request->input('coins_total', 0);

        $physicalTotal = ($n500 * 500)
            + ($n200 * 200)
            + ($n100 * 100)
            + ($n50 * 50)
            + ($n20 * 20)
            + ($n10 * 10)
            + $coins;

        $totalKm = (float) $request->input('total_km', 0);
        $kmRate  = (float) $request->input('km_rate', 0);
        $kmAllowance = $totalKm * $kmRate;

        $psoCode = $request->input('pso_code');
        $bookCash = (float) $request->input('book_cash_amount', 0);

        if ($bookCash <= 0 && $request->filled('business_date')) {
            $billsForPsoQuery = Bill::whereDate('business_date', $request->input('business_date'))
                ->where('is_post_cutoff', false);
            if ($psoCode) {
                $billsForPsoQuery->where('pso_code', $psoCode);
            }
            $psoBills = $billsForPsoQuery->get();
            $calcCash = 0;
            foreach ($psoBills as $b) {
                $calculatedNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
                $effective = (float) ($b->net_amount > 0 ? $b->net_amount : $calculatedNet);
                if ($b->is_split_payment || ($b->cash_amount > 0 && $b->paytm_amount > 0)) {
                    $calcCash += (float) $b->cash_amount;
                } elseif ($b->payment_type === 'Cash') {
                    $calcCash += $effective;
                }
            }
            $bookCash = round($calcCash, 2);
        }

        $expectedDeposit = max(0, $bookCash - $kmAllowance);

        $shortCash = 0;
        $excessCash = 0;

        if ($expectedDeposit > $physicalTotal) {
            $shortCash = round($expectedDeposit - $physicalTotal, 2);
        } elseif ($physicalTotal > $expectedDeposit) {
            $excessCash = round($physicalTotal - $expectedDeposit, 2);
        }

        $psoConfig = null;
        if ($request->filled('pso_code')) {
            $psoConfig = PsoConfig::where('code', $request->input('pso_code'))->first();
        }

        $denom = CashDenomination::create([
            'business_date' => $request->input('business_date'),
            'pso_config_id' => $psoConfig ? $psoConfig->id : null,
            'pso_code' => $request->input('pso_code'),
            'driver_name' => $request->input('driver_name') ?: ($psoConfig ? $psoConfig->driver_name : null),
            'gadi_number' => $request->input('gadi_number') ?: ($psoConfig ? $psoConfig->gadi_number : null),
            'notes_500' => $n500,
            'notes_200' => $n200,
            'notes_100' => $n100,
            'notes_50' => $n50,
            'notes_20' => $n20,
            'notes_10' => $n10,
            'coins_total' => $coins,
            'total_physical_cash' => $physicalTotal,
            'total_km' => $totalKm,
            'km_rate' => $kmRate,
            'km_allowance_amount' => $kmAllowance,
            'book_cash_amount' => $bookCash,
            'short_cash_amount' => $shortCash,
            'excess_cash_amount' => $excessCash,
            'cashier_name' => $request->input('cashier_name', session('active_user.name', 'Cashier')),
            'remarks' => $request->input('remarks'),
        ]);

        AuditLog::log('CASH_DENOMINATION_RECORDED', "Recorded physical cash deposit of ₹" . number_format($physicalTotal, 2) . " (Short: ₹" . number_format($shortCash, 2) . ") for " . ($request->input('pso_code') ?: 'General') . " on " . $request->input('business_date'));

        return redirect()->back()->with('success', "Cash Denomination saved successfully! Total Cash: ₹" . number_format($physicalTotal, 2) . ($shortCash > 0 ? " (Pending/Short Cash: ₹" . number_format($shortCash, 2) . ")" : ""));
    }

    /**
     * Delete a Cash Denomination record
     */
    public function destroy($id)
    {
        $denom = CashDenomination::findOrFail($id);
        $amt = $denom->total_physical_cash;
        $date = $denom->business_date;
        $denom->delete();

        AuditLog::log('CASH_DENOMINATION_DELETED', "Deleted denomination entry of ₹" . number_format($amt, 2) . " for date {$date}");

        return redirect()->back()->with('success', 'Cash denomination entry removed successfully.');
    }

    /**
     * Export Cash Denominations and Note Breakdown to CSV/Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $businessDate = $request->query('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
            $latestDenomDate = CashDenomination::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
            $latestDate = $latestBillDate ?: $latestDenomDate;
            $businessDate = $latestDate ? date('Y-m-d', strtotime($latestDate)) : $defaultDate;
        }

        $selectedPso = $request->query('pso', 'ALL');

        $user = auth()->user();
        $query = CashDenomination::whereDate('business_date', $businessDate);
        if ($selectedPso !== 'ALL' && !empty($selectedPso)) {
            $query->where('pso_code', $selectedPso);
        }
        if ($user && $user->isOperator()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('psoConfig', function ($sub) use ($user) {
                    $sub->where('created_by', $user->id)
                        ->orWhere('operator_name', $user->name);
                })->orWhere('cashier_name', $user->name);
            });
        }

        $records = $query->orderBy('id', 'asc')->get();

        $filenameDate = $businessDate ?: date('Y-m-d');
        $filenamePso = ($selectedPso !== 'ALL' && !empty($selectedPso)) ? "_{$selectedPso}" : '';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Cash_Denomination_{$filenameDate}{$filenamePso}.csv\"",
        ];

        return response()->stream(function () use ($records, $businessDate) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header Row
            fputcsv($handle, [
                'Slip ID',
                'Business Date',
                'PSO Counter',
                'Driver / Handover By',
                'Vehicle / Gadi No',
                '500 Notes (Qty)',
                '500 Amount (INR)',
                '200 Notes (Qty)',
                '200 Amount (INR)',
                '100 Notes (Qty)',
                '100 Amount (INR)',
                '50 Notes (Qty)',
                '50 Amount (INR)',
                '20 Notes (Qty)',
                '20 Amount (INR)',
                '10 Notes (Qty)',
                '10 Amount (INR)',
                'Coins Total (INR)',
                'Total Physical Cash (INR)',
                'Trip KM',
                'KM Rate (INR)',
                'KM Allowance (INR)',
                'Book Cash (INR)',
                'Short Cash / Pending (INR)',
                'Excess Cash (INR)',
                'Cashier / Handled By',
                'Remarks',
                'Recorded Time'
            ]);

            $tot500Count = 0; $tot500Amt = 0;
            $tot200Count = 0; $tot200Amt = 0;
            $tot100Count = 0; $tot100Amt = 0;
            $tot50Count = 0; $tot50Amt = 0;
            $tot20Count = 0; $tot20Amt = 0;
            $tot10Count = 0; $tot10Amt = 0;
            $totCoins = 0; $totPhysical = 0;
            $totKm = 0; $totKmAllowance = 0;
            $totBookCash = 0; $totShortCash = 0; $totExcessCash = 0;

            foreach ($records as $r) {
                $a500 = (int)$r->notes_500 * 500;
                $a200 = (int)$r->notes_200 * 200;
                $a100 = (int)$r->notes_100 * 100;
                $a50  = (int)$r->notes_50 * 50;
                $a20  = (int)$r->notes_20 * 20;
                $a10  = (int)$r->notes_10 * 10;

                $tot500Count += (int)$r->notes_500; $tot500Amt += $a500;
                $tot200Count += (int)$r->notes_200; $tot200Amt += $a200;
                $tot100Count += (int)$r->notes_100; $tot100Amt += $a100;
                $tot50Count  += (int)$r->notes_50;  $tot50Amt  += $a50;
                $tot20Count  += (int)$r->notes_20;  $tot20Amt  += $a20;
                $tot10Count  += (int)$r->notes_10;  $tot10Amt  += $a10;
                $totCoins += (float)$r->coins_total;
                $totPhysical += (float)$r->total_physical_cash;
                $totKm += (float)$r->total_km;
                $totKmAllowance += (float)$r->km_allowance_amount;
                $totBookCash += (float)$r->book_cash_amount;
                $totShortCash += (float)$r->short_cash_amount;
                $totExcessCash += (float)$r->excess_cash_amount;

                fputcsv($handle, [
                    $r->id,
                    $r->business_date ? date('d/m/Y', strtotime($r->business_date)) : '',
                    $r->pso_code ?: 'General',
                    $r->driver_name ?: '—',
                    $r->gadi_number ?: '—',
                    $r->notes_500,
                    $a500,
                    $r->notes_200,
                    $a200,
                    $r->notes_100,
                    $a100,
                    $r->notes_50,
                    $a50,
                    $r->notes_20,
                    $a20,
                    $r->notes_10,
                    $a10,
                    $r->coins_total,
                    $r->total_physical_cash,
                    $r->total_km,
                    $r->km_rate,
                    $r->km_allowance_amount,
                    $r->book_cash_amount,
                    $r->short_cash_amount,
                    $r->excess_cash_amount,
                    $r->cashier_name ?: '—',
                    $r->remarks ?: '—',
                    $r->created_at ? $r->created_at->format('d/m/Y H:i') : ''
                ]);
            }

            // Summary Total Row
            fputcsv($handle, [
                'TOTAL',
                date('d/m/Y', strtotime($businessDate)),
                'ALL SCOPED',
                '',
                '',
                $tot500Count,
                $tot500Amt,
                $tot200Count,
                $tot200Amt,
                $tot100Count,
                $tot100Amt,
                $tot50Count,
                $tot50Amt,
                $tot20Count,
                $tot20Amt,
                $tot10Count,
                $tot10Amt,
                $totCoins,
                $totPhysical,
                $totKm,
                '',
                $totKmAllowance,
                $totBookCash,
                $totShortCash,
                $totExcessCash,
                '',
                '',
                ''
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print / Export PDF slip for Cash Denomination
     */
    public function exportPdf(Request $request)
    {
        $businessDate = $request->query('date');
        if (!$businessDate) {
            $defaultDate = $this->reconService->getBusinessDate();
            $latestBillDate = Bill::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
            $latestDenomDate = CashDenomination::whereNotNull('business_date')->orderBy('business_date', 'desc')->value('business_date');
            $latestDate = $latestBillDate ?: $latestDenomDate;
            $businessDate = $latestDate ? date('Y-m-d', strtotime($latestDate)) : $defaultDate;
        }

        $selectedPso = $request->query('pso', 'ALL');
        $slipId = $request->query('id');

        $user = auth()->user();
        $query = CashDenomination::whereDate('business_date', $businessDate);
        if ($slipId) {
            $query->where('id', $slipId);
        } elseif ($selectedPso !== 'ALL' && !empty($selectedPso)) {
            $query->where('pso_code', $selectedPso);
        }

        if ($user && $user->isOperator()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('psoConfig', function ($sub) use ($user) {
                    $sub->where('created_by', $user->id)
                        ->orWhere('operator_name', $user->name);
                })->orWhere('cashier_name', $user->name);
            });
        }

        $denominations = $query->orderBy('id', 'asc')->get();

        // Calculate aggregate sums
        $totals = [
            'notes_500' => $denominations->sum('notes_500'),
            'amt_500'   => $denominations->sum('notes_500') * 500,
            'notes_200' => $denominations->sum('notes_200'),
            'amt_200'   => $denominations->sum('notes_200') * 200,
            'notes_100' => $denominations->sum('notes_100'),
            'amt_100'   => $denominations->sum('notes_100') * 100,
            'notes_50'  => $denominations->sum('notes_50'),
            'amt_50'    => $denominations->sum('notes_50') * 50,
            'notes_20'  => $denominations->sum('notes_20'),
            'amt_20'    => $denominations->sum('notes_20') * 20,
            'notes_10'  => $denominations->sum('notes_10'),
            'amt_10'    => $denominations->sum('notes_10') * 10,
            'coins_total' => $denominations->sum('coins_total'),
            'total_physical_cash' => $denominations->sum('total_physical_cash'),
            'total_km' => $denominations->sum('total_km'),
            'km_allowance_amount' => $denominations->sum('km_allowance_amount'),
            'book_cash_amount' => $denominations->sum('book_cash_amount'),
            'short_cash_amount' => $denominations->sum('short_cash_amount'),
            'excess_cash_amount' => $denominations->sum('excess_cash_amount'),
        ];

        return view('denomination.print', compact(
            'businessDate',
            'selectedPso',
            'denominations',
            'totals',
            'slipId'
        ));
    }
}
