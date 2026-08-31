<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
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
        $businessDate = $this->reconService->getBusinessDate();
        $metrics = $this->reconService->getMetrics($businessDate);

        $query = Bill::where('business_date', $businessDate)
            ->where('is_post_cutoff', false);

        if ($request->filled('paytype') && $request->paytype !== 'ALL') {
            $query->where('payment_type', $request->paytype);
        }

        $bills = $query->orderBy('id', 'asc')->get();

        return view('payment.index', compact('bills', 'metrics'));
    }
}
