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
        $businessDate = $this->reconService->getBusinessDate();
        $psoList = PsoConfig::where('is_active', true)->get();
        $salespersons = Salesperson::where('is_active', true)->orderBy('name')->get();

        $query = Bill::whereDate('business_date', $businessDate)
            ->where('is_post_cutoff', false);

        if ($request->filled('pso') && $request->pso !== 'ALL') {
            $query->where('pso_code', $request->pso);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type') && $request->payment_type !== 'ALL') {
            $query->where('payment_type', $request->payment_type);
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
        $metrics = $this->reconService->getMetrics($businessDate);

        return view('verification.index', compact('bills', 'psoList', 'metrics', 'salespersons'));
    }

    public function updateBill(Request $request)
    {
        $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'payment_type' => 'required|in:Cash,Paytm,Check,Credit,Cancelled',
            'cd_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bill = Bill::findOrFail($request->bill_id);

        $bill->payment_type = $request->payment_type;
        $bill->cd_amount = floatval($request->input('cd_amount', 0));
        $bill->refund_amount = floatval($request->input('refund_amount', 0));
        $bill->net_amount = max(0, floatval($bill->amount) - $bill->cd_amount - $bill->refund_amount);

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
                'cd_amount' => number_format($bill->cd_amount, 2, '.', ''),
                'refund_amount' => number_format($bill->refund_amount, 2, '.', ''),
                'net_amount' => number_format($bill->net_amount, 2, '.', ''),
                'salesman_name' => $bill->salesman_name,
                'salesperson_id' => $bill->salesperson_id,
            ],
            'metrics' => $metrics
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'exists:bills,id',
            'payment_type' => 'nullable|in:Cash,Paytm,Check,Credit,Cancelled',
            'salesperson_id' => 'nullable',
            'salesman_name' => 'nullable|string',
        ]);

        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::whereIn('id', $request->bill_ids)->get();

        $sp = null;
        if ($request->filled('salesperson_id') && $request->salesperson_id !== '') {
            $sp = Salesperson::find($request->salesperson_id);
        }

        $updatedCount = 0;
        foreach ($bills as $bill) {
            if ($request->filled('payment_type')) {
                $bill->payment_type = $request->payment_type;
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

    public function autoVerifyAll()
    {
        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::whereDate('business_date', $businessDate)
            ->where('is_post_cutoff', false)
            ->where('status', '!=', 'Cancelled')
            ->get();

        foreach ($bills as $bill) {
            $bill->status = 'Matched';
            $bill->verified_by = session('active_user.name', 'Ramesh Sharma');
            $bill->verified_at = now();
            $bill->save();
        }

        $metrics = $this->reconService->getMetrics($businessDate);
        $seal = PsoDailySeal::whereDate('business_date', $businessDate)->first();
        if ($seal) {
            $seal->tally_total = $metrics['tallyTotal'];
            $seal->pso_total = $metrics['psoCollection'];
            $seal->difference = $metrics['difference'];
            $seal->is_reconciled = $metrics['isReconciled'];
            $seal->save();
        }

        AuditLog::log('AUTO_VERIFY', "Auto-verified all physical bill slips for date {$businessDate}.");

        return redirect()->back()->with('success', 'All bills successfully auto-verified against physical bundles.');
    }

    public function exportCsv(): StreamedResponse
    {
        $businessDate = $this->reconService->getBusinessDate();
        $bills = Bill::whereDate('business_date', $businessDate)->where('is_post_cutoff', false)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Bill_Verification_{$businessDate}.csv\"",
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
