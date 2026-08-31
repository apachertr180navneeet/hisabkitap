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
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Credit_Collection_Sheet_{$date}.csv\"",
        ];

        return response()->stream(function () use ($credits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bill No', 'Customer Name', 'Assigned Salesman', 'Bill Date', 'Due Date', 'Total Amount', 'Paid Amount', 'Outstanding', 'Status', 'Remarks']);
            foreach ($credits as $c) {
                fputcsv($handle, [
                    $c->bill_no,
                    $c->customer_name,
                    $c->salesman_name,
                    $c->bill_date,
                    $c->due_date,
                    $c->bill_amount,
                    $c->paid_amount,
                    $c->outstanding_amount,
                    $c->collection_status,
                    $c->remark,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
