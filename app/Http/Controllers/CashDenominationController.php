<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $businessDate = $request->query('date', $this->reconService->getBusinessDate());
        $selectedPso = $request->query('pso', 'ALL');

        $metrics = $this->reconService->getMetrics($businessDate);
        $psoList = PsoConfig::where('is_active', true)->orderBy('code')->get();

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

        // Available dates from bills
        $availableDates = Bill::selectRaw('DISTINCT business_date')
            ->orderBy('business_date', 'desc')
            ->pluck('business_date')
            ->map(fn($d) => is_string($d) ? $d : $d->format('Y-m-d'))
            ->toArray();

        return view('denomination.index', compact(
            'businessDate',
            'selectedPso',
            'metrics',
            'psoList',
            'denominations',
            'scopedBookCash',
            'scopedPaytm',
            'scopedTotalBills',
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
            'notes_2000' => 'nullable|integer|min:0',
            'notes_500' => 'nullable|integer|min:0',
            'notes_200' => 'nullable|integer|min:0',
            'notes_100' => 'nullable|integer|min:0',
            'notes_50' => 'nullable|integer|min:0',
            'notes_20' => 'nullable|integer|min:0',
            'notes_10' => 'nullable|integer|min:0',
            'notes_5' => 'nullable|integer|min:0',
            'notes_2' => 'nullable|integer|min:0',
            'notes_1' => 'nullable|integer|min:0',
            'coins_total' => 'nullable|numeric|min:0',
            'total_km' => 'nullable|numeric|min:0',
            'km_rate' => 'nullable|numeric|min:0',
            'book_cash_amount' => 'nullable|numeric|min:0',
            'cashier_name' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $n2000 = (int) $request->input('notes_2000', 0);
        $n500  = (int) $request->input('notes_500', 0);
        $n200  = (int) $request->input('notes_200', 0);
        $n100  = (int) $request->input('notes_100', 0);
        $n50   = (int) $request->input('notes_50', 0);
        $n20   = (int) $request->input('notes_20', 0);
        $n10   = (int) $request->input('notes_10', 0);
        $n5    = (int) $request->input('notes_5', 0);
        $n2    = (int) $request->input('notes_2', 0);
        $n1    = (int) $request->input('notes_1', 0);
        $coins = (float) $request->input('coins_total', 0);

        $physicalTotal = ($n2000 * 2000)
            + ($n500 * 500)
            + ($n200 * 200)
            + ($n100 * 100)
            + ($n50 * 50)
            + ($n20 * 20)
            + ($n10 * 10)
            + ($n5 * 5)
            + ($n2 * 2)
            + ($n1 * 1)
            + $coins;

        $totalKm = (float) $request->input('total_km', 0);
        $kmRate  = (float) $request->input('km_rate', 0);
        $kmAllowance = $totalKm * $kmRate;

        $bookCash = (float) $request->input('book_cash_amount', 0);
        $expectedDeposit = max(0, $bookCash - $kmAllowance);

        $shortCash = 0;
        $excessCash = 0;

        if ($expectedDeposit > $physicalTotal) {
            $shortCash = $expectedDeposit - $physicalTotal;
        } elseif ($physicalTotal > $expectedDeposit) {
            $excessCash = $physicalTotal - $expectedDeposit;
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
            'notes_2000' => $n2000,
            'notes_500' => $n500,
            'notes_200' => $n200,
            'notes_100' => $n100,
            'notes_50' => $n50,
            'notes_20' => $n20,
            'notes_10' => $n10,
            'notes_5' => $n5,
            'notes_2' => $n2,
            'notes_1' => $n1,
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
}
