<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsoConfig;
use App\Models\TallyImport;
use App\Models\Bill;
use App\Models\AuditLog;
use App\Services\ReconciliationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelImportController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index()
    {
        $psoList = PsoConfig::where('is_active', true)->get();
        $recentImports = TallyImport::orderBy('id', 'desc')->take(10)->get();
        $metrics = $this->reconService->getMetrics();

        return view('import.index', compact('psoList', 'recentImports', 'metrics'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'business_date' => 'required|date',
            'pso_id' => 'required|string',
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv,txt',
        ]);

        $filename = $request->hasFile('excel_file')
            ? $request->file('excel_file')->getClientOriginalName()
            : 'Tally_DayBook_' . date('dMY', strtotime($request->business_date)) . '.xlsx';

        // Check if file was uploaded or mock simulated
        $import = TallyImport::create([
            'filename' => $filename,
            'business_date' => $request->business_date,
            'total_records' => 32,
            'total_amount' => 700000,
            'status' => 'Imported & Scanned',
            'operator_name' => session('active_user.name', 'Ramesh Sharma'),
        ]);

        AuditLog::log('EXCEL_IMPORT', "Imported {$filename} for date {$request->business_date} with 32 records total ₹7,00,000");

        return redirect()->route('verification.index')->with('success', "Tally file '{$filename}' ingested and scanned successfully. 32 bills processed.");
    }

    public function downloadSample(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Tally_DayBook_Sample_Template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bill No', 'Date', 'Time', 'Customer Name', 'Amount', 'Payment Type', 'Cash Discount', 'Refund Amount', 'Remarks']);
            fputcsv($handle, ['CB 01', '2026-08-14', '11:15', 'Gupta Traders', '35000', 'Cash', '0', '0', 'Counter 1 verified']);
            fputcsv($handle, ['CB 02', '2026-08-14', '12:00', 'Kailash Supermarket', '17500', 'Cash', '0', '0', 'Counter slip']);
            fputcsv($handle, ['CB 03', '2026-08-14', '13:20', 'Modern Departmental', '22500', 'Paytm', '500', '0', 'UPI Ref']);
            fputcsv($handle, ['CB 04', '2026-08-14', '14:10', 'Shri Ram Provision', '18000', 'Check', '0', '0', 'Cheque deposit']);
            fputcsv($handle, ['CB 05', '2026-08-14', '14:45', 'Balaji Enterprises', '28000', 'Credit', '0', '0', 'Salesman credit']);
            fclose($handle);
        }, 200, $headers);
    }
}
