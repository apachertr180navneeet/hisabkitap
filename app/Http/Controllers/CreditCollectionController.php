<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CreditCollection;
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

    public function index()
    {
        $credits = CreditCollection::orderBy('id', 'asc')->get();

        $totSales = $credits->sum('bill_amount');
        $totRecovered = $credits->sum('paid_amount');
        $totOutstanding = $credits->sum('outstanding_amount');

        return view('credit.index', compact('credits', 'totSales', 'totRecovered', 'totOutstanding'));
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

    public function exportSheet(): StreamedResponse
    {
        $credits = CreditCollection::all();
        $date = $this->reconService->getBusinessDate();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Credit_Collection_Sheet_{$date}.csv\"",
        ];

        return response()->stream(function () use ($credits) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Bill No', 'Customer Name', 'Assigned Salesman', 'Bill Date', 'Due Date', 'Total Amount (INR)', 'Paid Amount (INR)', 'Outstanding (INR)', 'Status', 'Remarks']);
            
            $totSales = 0; $totPaid = 0; $totOut = 0;
            foreach ($credits as $c) {
                $totSales += (float)$c->bill_amount;
                $totPaid += (float)$c->paid_amount;
                $totOut += (float)$c->outstanding_amount;
                $bDate = $c->bill_date ? (is_string($c->bill_date) ? substr($c->bill_date, 0, 10) : $c->bill_date->format('d/m/Y')) : '';
                $dDate = $c->due_date ? (is_string($c->due_date) ? substr($c->due_date, 0, 10) : $c->due_date->format('d/m/Y')) : '';

                fputcsv($handle, [
                    $c->bill_no,
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
        $credits = CreditCollection::orderBy('id', 'asc')->get();

        $totSales = $credits->sum('bill_amount');
        $totRecovered = $credits->sum('paid_amount');
        $totOutstanding = $credits->sum('outstanding_amount');

        return view('credit.print', compact('credits', 'businessDate', 'totSales', 'totRecovered', 'totOutstanding'));
    }
}
