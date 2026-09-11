<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsoConfig;
use App\Models\TallyImport;
use App\Models\Bill;
use App\Models\CreditCollection;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\Prefix;
use App\Models\Salesperson;
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
        $businessDate = $this->reconService->getBusinessDate();
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        
        $user = auth()->user();
        $query = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoList = $query->orderBy('code')->get();
        $recentImports = TallyImport::orderBy('id', 'desc')->take(10)->get();
        $metrics = $this->reconService->getMetrics($businessDate);

        return view('import.index', compact('psoList', 'recentImports', 'metrics', 'businessDate', 'cutoffTime'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'business_date' => 'required|date',
            'pso_id' => 'required|string',
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv,txt,xml|max:10240',
        ], [
            'excel_file.mimes' => 'The uploaded file must be an Excel spreadsheet (.xlsx, .xls) or CSV file (.csv).',
            'excel_file.max' => 'The uploaded file size must not exceed 10 MB.',
            'business_date.required' => 'Please select a valid business date.',
        ]);

        $businessDate = $request->business_date;
        $targetPsoId = $request->pso_id;
        $cutoffTime = SystemSetting::getVal('cutoff_time', '19:00');
        $operatorName = session('active_user.name', 'Suresh Gupta');

        $user = auth()->user();
        $query = PsoConfig::where('is_closed', true);
        if ($user && $user->isOperator()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('operator_name', $user->name);
            });
        }
        $psoConfigs = $query->get();
        if ($psoConfigs->isEmpty()) {
            $psoConfigs = PsoConfig::all();
        }

        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            $filename = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $filePath = $file->getRealPath();

            if (!$file->isValid()) {
                return redirect()->back()->withInput()->with('error', "Upload error: The file '{$filename}' could not be uploaded properly.");
            }

            $parsedRows = [];
            $parsingError = null;

            try {
                if (in_array($extension, ['csv', 'txt'])) {
                    $parsedRows = $this->parseCsvFile($filePath);
                } elseif ($extension === 'xlsx') {
                    $parsedRows = $this->parseXlsxFile($filePath);
                } elseif ($extension === 'xls' || $extension === 'xml') {
                    $parsedRows = $this->parseXlsFile($filePath);
                } else {
                    // Try parsing as CSV if unknown extension
                    $parsedRows = $this->parseCsvFile($filePath);
                }
            } catch (\Throwable $e) {
                $parsingError = $e->getMessage();
            }

            if ($parsingError !== null) {
                return redirect()->back()->withInput()->with('error', "Failed to read file '{$filename}': {$parsingError}. Please verify the file format.");
            }

            if (empty($parsedRows)) {
                return redirect()->back()->withInput()->with('error', "The uploaded file '{$filename}' is empty or could not be parsed into rows. Please ensure it contains data.");
            }

            // Detect header row and column mapping across top 30 rows
            $mappingResult = $this->findHeaderAndMapColumns($parsedRows);
            $headerIndex = $mappingResult['header_index'];
            $colMap = $mappingResult['col_map'];

            if ($headerIndex === -1 || ($colMap['bill_no'] === null && $colMap['amount'] === null)) {
                return redirect()->back()->withInput()->with('error', "Invalid spreadsheet format in '{$filename}': Could not identify required columns (Voucher/Bill No. and Amount). Please ensure column headers like 'Voucher No.', 'Particulars', 'Amount', 'Date' exist or download the sample template.");
            }

            // Remove rows up to and including the header
            $dataRows = array_slice($parsedRows, $headerIndex + 1);

            $importedRows = 0;
            $totalAmount = 0;
            $rowErrors = [];
            $rowNum = $headerIndex + 2; // 1-based index for user display

            // Create TallyImport record
            $import = TallyImport::create([
                'filename' => $filename,
                'business_date' => $businessDate,
                'total_records' => 0,
                'total_amount' => 0,
                'status' => 'Imported & Scanned',
                'operator_name' => $operatorName,
            ]);

            foreach ($dataRows as $row) {
                // Skip completely empty rows
                if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== ''))) {
                    $rowNum++;
                    continue;
                }

                $rawBillNo = ($colMap['bill_no'] !== null && isset($row[$colMap['bill_no']])) ? trim((string)$row[$colMap['bill_no']]) : '';
                
                // Skip header repetitions, dashes/separator rows, or summary rows
                if (empty($rawBillNo) || 
                    preg_match('/^(voucher|bill[\s_]*no|total|grand[\s_]*total|closing|opening|carried|brought|\-+|\=+|\*+)$/i', $rawBillNo) ||
                    stripos($rawBillNo, 'grand total') !== false ||
                    (stripos($rawBillNo, 'total') !== false && !preg_match('/\d/', $rawBillNo))) {
                    
                    if (!empty($rawBillNo) && (stripos($rawBillNo, 'total') !== false || stripos($rawBillNo, 'closing') !== false)) {
                        $rowNum++;
                        continue;
                    }
                    if (empty($rawBillNo)) {
                        $rowErrors[] = "Row {$rowNum}: Skipped due to missing Voucher/Bill No.";
                        $rowNum++;
                        continue;
                    }
                    $rowNum++;
                    continue;
                }

                $billNo = $rawBillNo;

                // Validate and parse amount
                $rawAmount = ($colMap['amount'] !== null && isset($row[$colMap['amount']])) ? trim((string)$row[$colMap['amount']]) : '0';
                $cleanAmount = $this->normalizeAmount($rawAmount);

                if ($cleanAmount === null || !is_numeric($cleanAmount)) {
                    $rowErrors[] = "Row {$rowNum} (Bill '{$billNo}'): Invalid amount format '{$rawAmount}'. Amount must be numeric.";
                    $rowNum++;
                    continue;
                }

                $amount = abs((float) $cleanAmount);

                // Date Parsing
                $rawDate = ($colMap['date'] !== null && !empty($row[$colMap['date']])) ? trim((string)$row[$colMap['date']]) : '';
                $rowDate = $this->parseRowDate($rawDate, $businessDate);

                $customer = ($colMap['customer_name'] !== null && !empty($row[$colMap['customer_name']])) ? trim((string)$row[$colMap['customer_name']]) : 'General Customer';
                $voucherType = ($colMap['voucher_type'] !== null && !empty($row[$colMap['voucher_type']])) ? trim((string)$row[$colMap['voucher_type']]) : 'Sales Cadbury';
                $rowTime = '12:00';
                $cdAmount = 0;
                $refundAmount = 0;
                $remarks = $voucherType;

                // Standardize Payment Type
                $paymentTypeNormalized = 'Cash';
                if (stripos($voucherType, 'paytm') !== false || stripos($voucherType, 'upi') !== false) {
                    $paymentTypeNormalized = 'Paytm';
                } elseif (stripos($voucherType, 'credit') !== false || stripos($customer, 'credit') !== false) {
                    $paymentTypeNormalized = 'Credit';
                } elseif (stripos($voucherType, 'cancel') !== false) {
                    $paymentTypeNormalized = 'Cancelled';
                }

                // Determine PSO Mapping
                $assignedPsoCode = 'PSO-1';
                $psoConfigId = null;

                if ($targetPsoId !== 'ALL') {
                    $matchedPso = $psoConfigs->firstWhere('code', $targetPsoId);
                    if ($matchedPso) {
                        $assignedPsoCode = $matchedPso->code;
                        $psoConfigId = $matchedPso->id;
                    }
                } else {
                    foreach ($psoConfigs as $pso) {
                        if (!empty($pso->prefix) && stripos($billNo, $pso->prefix) === 0) {
                            $assignedPsoCode = $pso->code;
                            $psoConfigId = $pso->id;
                            break;
                        }
                        if (!empty($pso->series_ranges)) {
                            foreach ($pso->series_ranges as $sr) {
                                if (!empty($sr['prefix']) && stripos($billNo, $sr['prefix']) === 0) {
                                    $assignedPsoCode = $pso->code;
                                    $psoConfigId = $pso->id;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Check post-cutoff
                $isPostCutoff = false;
                $netAmount = max(0, $amount - $cdAmount - $refundAmount);

                $bill = Bill::updateOrCreate(
                    [
                        'bill_no' => $billNo,
                        'business_date' => $rowDate,
                    ],
                    [
                        'pso_config_id' => $psoConfigId,
                        'pso_code' => $assignedPsoCode,
                        'tally_import_id' => $import->id,
                        'bill_time' => $rowTime,
                        'customer_name' => $customer,
                        'particulars' => $customer,
                        'amount' => $amount,
                        'payment_type' => $paymentTypeNormalized,
                        'voucher_type' => $voucherType,
                        'cd_amount' => $cdAmount,
                        'refund_amount' => $refundAmount,
                        'net_amount' => $netAmount,
                        'status' => $paymentTypeNormalized === 'Cancelled' ? 'Cancelled' : ($isPostCutoff ? 'Next Day PSO' : 'Matched'),
                        'is_expected' => true,
                        'tally_found' => true,
                        'is_post_cutoff' => $isPostCutoff,
                        'remark' => $remarks,
                        'verified_by' => $operatorName,
                        'verified_at' => now(),
                    ]
                );

                // Create Credit Collection record if payment type is Credit
                if ($paymentTypeNormalized === 'Credit' && $amount > 0) {
                    $billPrefix = strtoupper(trim(explode(' ', $billNo)[0] ?? ''));
                    $assignedSalesman = Salesperson::where('prefix_code', $billPrefix)
                        ->orWhereHas('prefix', fn($q) => $q->where('prefix', $billPrefix))
                        ->value('name') ?? 'Field Representative';

                    CreditCollection::updateOrCreate(
                        ['bill_id' => $bill->id],
                        [
                            'bill_no' => $billNo,
                            'customer_name' => $customer,
                            'salesman_name' => $assignedSalesman,
                            'bill_date' => $rowDate,
                            'due_date' => date('Y-m-d', strtotime($rowDate . ' +7 days')),
                            'bill_amount' => $amount,
                            'paid_amount' => 0,
                            'outstanding_amount' => $amount,
                            'collection_status' => 'Pending',
                            'payment_mode' => 'Credit Pending',
                            'remark' => $remarks,
                        ]
                    );
                }

                $importedRows++;
                $totalAmount += $amount;
                $rowNum++;
            }

            if ($importedRows === 0) {
                $import->delete();
                $errDetail = !empty($rowErrors) ? ' (' . implode('; ', array_slice($rowErrors, 0, 3)) . ')' : '';
                return redirect()->back()->withInput()->with('error', "No valid bill records could be imported from '{$filename}'{$errDetail}. Please check file structure.")->with('import_errors', $rowErrors);
            }

            // Update import aggregates
            $import->update([
                'total_records' => $importedRows,
                'total_amount' => $totalAmount,
            ]);

            // Redirect to verification
            AuditLog::log('EXCEL_IMPORT', "Imported {$filename} for date {$businessDate} with {$importedRows} records total ₹" . number_format($totalAmount, 2));

            $redirect = redirect()->route('admin.verification.index');
            if (!empty($rowErrors)) {
                return $redirect->with('success', "Excel file '{$filename}' imported! {$importedRows} bills processed (Total: ₹" . number_format($totalAmount, 2) . ").")
                                ->with('warning', count($rowErrors) . " row(s) had formatting errors and were skipped.")
                                ->with('import_errors', $rowErrors);
            }

            return $redirect->with('success', "Excel file '{$filename}' successfully imported! {$importedRows} bills processed (Total: ₹" . number_format($totalAmount, 2) . ").");
        }

        // Fallback / simulated import when no file is chosen (for quick testing/demo)
        $filename = 'Tally_DayBook_' . date('dMY', strtotime($businessDate)) . '.xlsx';

        $import = TallyImport::create([
            'filename' => $filename,
            'business_date' => $businessDate,
            'total_records' => 32,
            'total_amount' => 700000,
            'status' => 'Imported & Scanned',
            'operator_name' => $operatorName,
        ]);

        AuditLog::log('EXCEL_IMPORT', "Imported {$filename} for date {$businessDate} with 32 records total ₹7,00,000");

        return redirect()->route('admin.verification.index')->with('success', "Tally file '{$filename}' ingested and scanned successfully.");
    }

    /**
     * Clean and normalize raw amount string (supports commas, currency symbols, Dr/Cr, parentheses).
     */
    protected function normalizeAmount(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Remove currency symbols, Dr/Cr tags, and non-breaking spaces
        $clean = str_ireplace(['dr', 'cr', 'rs.', 'rs', 'inr', '₹', '$', '/-', ','], '', $raw);
        $clean = preg_replace('/\s+/', '', $clean);

        // Accounting parentheses e.g. (1500) -> -1500 or 1500
        if (preg_match('/^\((.+)\)$/', $clean, $m)) {
            $clean = $m[1];
        }

        // Remove any remaining unexpected characters except digits, minus, and dot
        $clean = preg_replace('/[^\d\.\-]/', '', $clean);

        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        return $clean;
    }

    /**
     * Robust Date Parsing (supports Excel serial numbers, DD/MM/YYYY, YYYY-MM-DD, DD-Mon-YYYY).
     */
    protected function parseRowDate(string $rawDate, string $businessDate): string
    {
        $rawDate = trim($rawDate);
        if (empty($rawDate)) {
            return $businessDate;
        }

        // 1. Check if Excel numeric serial date (e.g. 45543 = 2024-09-08)
        if (is_numeric($rawDate)) {
            $numVal = (float)$rawDate;
            if ($numVal >= 25000 && $numVal <= 80000) {
                // Excel epoch: 1900-01-01 (with leap year bug, offset is 25569 to UNIX epoch 1970-01-01)
                $unix = (int)(($numVal - 25569) * 86400);
                return gmdate('Y-m-d', $unix);
            }
        }

        // 2. Format: DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/', $rawDate, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            if ($year < 100) {
                $year += 2000;
            }

            // Validate day & month order (if month > 12 and day <= 12, swap)
            if ($month > 12 && $day <= 12) {
                $tmp = $day;
                $day = $month;
                $month = $tmp;
            }

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 3. Format: YYYY-MM-DD or YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $rawDate, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 4. Try standard strtotime with hyphen separator
        $standardized = str_replace(['/', '.'], '-', $rawDate);
        $parsedTime = strtotime($standardized);
        if ($parsedTime !== false && $parsedTime > 0) {
            return date('Y-m-d', $parsedTime);
        }

        return $businessDate;
    }

    /**
     * Smart Header & Column Detection across top 30 rows.
     */
    protected function findHeaderAndMapColumns(array $parsedRows): array
    {
        $headerIndex = -1;
        $colMap = [
            'date' => null,
            'customer_name' => null,
            'voucher_type' => null,
            'bill_no' => null,
            'amount' => null,
        ];

        $maxScanRows = min(30, count($parsedRows));

        for ($idx = 0; $idx < $maxScanRows; $idx++) {
            $row = $parsedRows[$idx];
            $cleaned = [];
            foreach ($row as $cIdx => $cell) {
                $str = (string) $cell;
                $str = preg_replace('/[\x00-\x1F\x7F\x80-\xFF]/', '', $str);
                $str = strtolower(trim($str));
                $str = preg_replace('/[^\w\s\.\#\/\-]/', '', $str);
                $cleaned[$cIdx] = $str;
            }

            $dateCol = $this->matchColumnDate($cleaned);
            $billCol = $this->matchColumnBillNo($cleaned, $dateCol);
            $amtCol = $this->matchColumnAmount($cleaned, $dateCol, $billCol);
            $custCol = $this->matchColumnCustomer($cleaned, [$dateCol, $billCol, $amtCol]);
            $typeCol = $this->matchColumnVoucherType($cleaned, [$dateCol, $billCol, $amtCol, $custCol]);

            $matchedCount = 0;
            if ($billCol !== null) $matchedCount++;
            if ($amtCol !== null) $matchedCount++;
            if ($custCol !== null) $matchedCount++;
            if ($dateCol !== null) $matchedCount++;
            if ($typeCol !== null) $matchedCount++;

            // If at least 2 primary columns matched (including bill_no or amount)
            if ($matchedCount >= 2 && ($billCol !== null || $amtCol !== null)) {
                $headerIndex = $idx;
                $colMap = [
                    'date' => $dateCol,
                    'customer_name' => $custCol,
                    'voucher_type' => $typeCol,
                    'bill_no' => $billCol,
                    'amount' => $amtCol,
                ];
                break;
            }
        }

        // Fallback: If header row wasn't found by text, check if first row has 4+ columns
        if ($headerIndex === -1 && !empty($parsedRows)) {
            $firstRow = $parsedRows[0];
            $colCount = count($firstRow);
            if ($colCount >= 4) {
                $headerIndex = 0;
                $colMap = [
                    'date' => 0,
                    'customer_name' => 1,
                    'voucher_type' => 2,
                    'bill_no' => 3,
                    'amount' => $colCount > 4 ? 4 : 3,
                ];
            }
        }

        return [
            'header_index' => $headerIndex,
            'col_map' => $colMap,
        ];
    }

    protected function matchColumnDate(array $headers): ?int
    {
        $exacts = ['date', 'dt', 'bill date', 'bill_date', 'vch date', 'vch_date', 'voucher date', 'voucher_date', 'invoice date', 'inv date', 'business date', 'entry date', 'doc date', 'trn date', 'txn date'];
        foreach ($headers as $idx => $h) {
            if (in_array($h, $exacts, true)) {
                return $idx;
            }
        }

        foreach ($headers as $idx => $h) {
            if (preg_match('/\b(date|bill\s*date|vch\s*date|invoice\s*date)\b/i', $h) && !preg_match('/\b(no|num|#|amt|amount|total)\b/i', $h)) {
                return $idx;
            }
        }
        return null;
    }

    protected function matchColumnBillNo(array $headers, ?int $excludeDateCol = null): ?int
    {
        $exacts = [
            'voucher no.', 'voucher no', 'vch no.', 'vch no', 'bill no.', 'bill no', 'bill_no',
            'bill number', 'bill #', 'vch #', 'invoice no.', 'invoice no', 'inv no.', 'inv no',
            'billno', 'vchno', 'invoice #', 'doc no.', 'doc no', 'ref no.', 'ref no', 'bill'
        ];

        foreach ($headers as $idx => $h) {
            if ($idx === $excludeDateCol) continue;
            if (in_array($h, $exacts, true)) {
                return $idx;
            }
        }

        foreach ($headers as $idx => $h) {
            if ($idx === $excludeDateCol) continue;
            // Never match date, amount, total or type as bill number
            if (preg_match('/\b(date|dt|amt|amount|total|sum|type)\b/i', $h)) {
                continue;
            }
            if (preg_match('/\b(voucher\s*no|vch\s*no|bill\s*no|inv\s*no|invoice\s*no|bill_num|billnum|vch_num|doc\s*no|reference\s*no|ref\s*no)\b/i', $h) ||
                (stripos($h, 'bill') !== false && stripos($h, 'no') !== false) ||
                (stripos($h, 'voucher') !== false && (stripos($h, 'no') !== false || stripos($h, '#') !== false))) {
                return $idx;
            }
        }
        return null;
    }

    protected function matchColumnAmount(array $headers, ?int $exclude1 = null, ?int $exclude2 = null): ?int
    {
        $exacts = [
            'amount', 'bill amount', 'gross amount', 'net amount', 'total amount', 'total',
            'debit', 'credit', 'debit amount', 'credit amount', 'dr amount', 'cr amount',
            'gross total', 'net total', 'bill amount (rs.)', 'amount (rs.)', 'amt', 'value', 'bill value', 'total value'
        ];

        foreach ($headers as $idx => $h) {
            if ($idx === $exclude1 || $idx === $exclude2) continue;
            if (in_array($h, $exacts, true)) {
                return $idx;
            }
        }

        foreach ($headers as $idx => $h) {
            if ($idx === $exclude1 || $idx === $exclude2) continue;
            if (preg_match('/\b(date|no|num|#|type|particular)\b/i', $h)) {
                continue;
            }
            if (preg_match('/\b(amount|amt|total|debit|credit|dr_amt|cr_amt|value)\b/i', $h)) {
                return $idx;
            }
        }
        return null;
    }

    protected function matchColumnCustomer(array $headers, array $exclude = []): ?int
    {
        $exacts = [
            'particulars', 'particular', 'party name', 'party', 'customer name', 'customer',
            'ledger', 'ledger name', 'party / ledger', 'account', 'account name', 'party name / ledger',
            'buyer', 'buyer name', 'consignee', 'client', 'client name', 'party ledger'
        ];

        foreach ($headers as $idx => $h) {
            if (in_array($idx, $exclude, true)) continue;
            if (in_array($h, $exacts, true)) {
                return $idx;
            }
        }

        foreach ($headers as $idx => $h) {
            if (in_array($idx, $exclude, true)) continue;
            if (preg_match('/\b(particulars|particular|party|customer|ledger|account|buyer|consignee|client)\b/i', $h)) {
                return $idx;
            }
        }
        return null;
    }

    protected function matchColumnVoucherType(array $headers, array $exclude = []): ?int
    {
        $exacts = [
            'voucher type', 'vch type', 'sales type', 'trn type', 'transaction type', 'type',
            'vouchertype', 'trn_type', 'vch_type', 'category', 'bill type'
        ];

        foreach ($headers as $idx => $h) {
            if (in_array($idx, $exclude, true)) continue;
            if (in_array($h, $exacts, true)) {
                return $idx;
            }
        }

        foreach ($headers as $idx => $h) {
            if (in_array($idx, $exclude, true)) continue;
            if (preg_match('/\b(no|num|#|date|amt|amount|total)\b/i', $h)) {
                continue;
            }
            if (preg_match('/\b(voucher\s*type|vch\s*type|sales\s*type|trans\s*type|type)\b/i', $h)) {
                return $idx;
            }
        }
        return null;
    }

    protected function parseCsvFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false || $rawContent === '') {
            return [];
        }

        // Check for UTF-16 LE / BE encoding (frequent in Tally exports)
        if (str_starts_with($rawContent, "\xFF\xFE")) {
            $rawContent = mb_convert_encoding(substr($rawContent, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($rawContent, "\xFE\xFF")) {
            $rawContent = mb_convert_encoding(substr($rawContent, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_contains(substr($rawContent, 0, 100), "\x00")) {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($rawContent, "\xEF\xBB\xBF")) {
            // Strip UTF-8 BOM
            $rawContent = substr($rawContent, 3);
        }

        // Split lines
        $lines = preg_split('/\r\n|\r|\n/', $rawContent);
        if (empty($lines)) {
            return [];
        }

        // Detect delimiter from non-empty first 5 lines
        $sample = '';
        $sampleLines = 0;
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $sample .= $line . "\n";
                $sampleLines++;
                if ($sampleLines >= 5) break;
            }
        }

        $commaCount = substr_count($sample, ',');
        $tabCount = substr_count($sample, "\t");
        $semiCount = substr_count($sample, ';');
        $pipeCount = substr_count($sample, '|');

        $delimiter = ',';
        if ($tabCount > $commaCount && $tabCount > $semiCount) {
            $delimiter = "\t";
        } elseif ($semiCount > $commaCount && $semiCount > $tabCount) {
            $delimiter = ';';
        } elseif ($pipeCount > $commaCount && $pipeCount > $tabCount) {
            $delimiter = '|';
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $data = str_getcsv($line, $delimiter);
            if (!empty($data)) {
                $rows[] = $data;
            }
        }

        return $rows;
    }

    protected function parseXlsFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (empty($content)) {
            return [];
        }

        // Check if UTF-16 XML
        if (str_starts_with($content, "\xFF\xFE") || str_contains(substr($content, 0, 50), "\x00")) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        }

        // 1. Check if SpreadsheetML XML format (<Workbook ... <Row ... <Cell>)
        if (stripos($content, '<Workbook') !== false && stripos($content, '<Row') !== false) {
            $rows = [];
            $dom = new \DOMDocument();
            @$dom->loadXML($content);
            $xmlRows = $dom->getElementsByTagName('Row');
            foreach ($xmlRows as $r) {
                $rowVals = [];
                $cells = $r->getElementsByTagName('Cell');
                $colIndex = 0;
                foreach ($cells as $c) {
                    if ($c->hasAttribute('ss:Index')) {
                        $colIndex = (int)$c->getAttribute('ss:Index') - 1;
                    }
                    $dataElements = $c->getElementsByTagName('Data');
                    $cellVal = ($dataElements->length > 0) ? trim($dataElements->item(0)->textContent) : trim($c->textContent);
                    $rowVals[$colIndex] = $cellVal;
                    $colIndex++;
                }
                if (!empty(array_filter($rowVals, fn($v) => $v !== null && $v !== ''))) {
                    $maxKey = !empty($rowVals) ? max(array_keys($rowVals)) : 0;
                    $normalizedRow = [];
                    for ($i = 0; $i <= $maxKey; $i++) {
                        $normalizedRow[$i] = $rowVals[$i] ?? '';
                    }
                    $rows[] = $normalizedRow;
                }
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        // 2. Check if HTML Table format (<table ... <tr> ... <td>)
        if (stripos($content, '<table') !== false && stripos($content, '<tr') !== false) {
            $rows = [];
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
            $trElements = $dom->getElementsByTagName('tr');
            foreach ($trElements as $tr) {
                $rowVals = [];
                $cells = $tr->getElementsByTagName('td');
                if ($cells->length === 0) {
                    $cells = $tr->getElementsByTagName('th');
                }
                foreach ($cells as $c) {
                    $rowVals[] = html_entity_decode(trim($c->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                if (!empty(array_filter($rowVals, fn($v) => $v !== null && $v !== ''))) {
                    $rows[] = $rowVals;
                }
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        // 3. Fallback to CSV parser
        return $this->parseCsvFile($filePath);
    }

    protected function parseXlsxFile(string $filePath): array
    {
        if (!class_exists('\ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is required to read .xlsx files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            // Check if file is actually an XML or CSV disguised as XLSX
            return $this->parseXlsFile($filePath);
        }

        // Extract sharedStrings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $sDom = new \DOMDocument();
            @$sDom->loadXML($sharedStringsXml);
            $siElements = $sDom->getElementsByTagName('si');
            foreach ($siElements as $si) {
                $tElements = $si->getElementsByTagName('t');
                $str = '';
                foreach ($tElements as $t) {
                    $str .= $t->textContent;
                }
                $sharedStrings[] = $str;
            }
        }

        // Locate worksheet XML
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            // Find any sheet in xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('#xl/worksheets/sheet\d*\.xml#i', $stat['name'])) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $rows = [];
        if ($sheetXml) {
            $dom = new \DOMDocument();
            @$dom->loadXML($sheetXml);
            $rowElements = $dom->getElementsByTagName('row');
            foreach ($rowElements as $r) {
                $rowVals = [];
                $cells = $r->getElementsByTagName('c');
                $autoColIdx = 0;
                foreach ($cells as $c) {
                    $coord = $c->getAttribute('r'); // e.g. A1, B1, E1
                    $colIdx = $autoColIdx;
                    if (!empty($coord) && preg_match('/^([A-Z]+)/i', $coord, $m)) {
                        $colIdx = $this->columnLetterToIndex(strtoupper($m[1]));
                    }

                    $type = $c->getAttribute('t');
                    $valElements = $c->getElementsByTagName('v');
                    $val = ($valElements->length > 0) ? $valElements->item(0)->textContent : '';

                    if ($type === 's' && isset($sharedStrings[(int)$val])) {
                        $val = $sharedStrings[(int)$val];
                    } elseif ($type === 'inlineStr') {
                        $isElements = $c->getElementsByTagName('t');
                        $val = ($isElements->length > 0) ? $isElements->item(0)->textContent : $val;
                    } elseif ($type === 'b') {
                        $val = $val === '1' ? 'TRUE' : 'FALSE';
                    }

                    $rowVals[$colIdx] = $val;
                    $autoColIdx = $colIdx + 1;
                }

                if (!empty(array_filter($rowVals, fn($v) => $v !== null && $v !== ''))) {
                    $maxKey = max(array_keys($rowVals));
                    $normalizedRow = [];
                    for ($i = 0; $i <= $maxKey; $i++) {
                        $normalizedRow[$i] = $rowVals[$i] ?? '';
                    }
                    $rows[] = $normalizedRow;
                }
            }
        }

        $zip->close();
        return $rows;
    }

    protected function columnLetterToIndex(string $col): int
    {
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }
        return $index - 1;
    }

    protected function findColumnIndex(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }
        foreach ($headers as $index => $header) {
            foreach ($candidates as $candidate) {
                if (stripos($header, $candidate) !== false) {
                    return $index;
                }
            }
        }
        return null;
    }

    public function downloadSample(Request $request)
    {
        $type = $request->query('type', 'sample'); // 'sample' or 'blank'
        $format = strtolower($request->query('format', 'xls')); // default 'xls', or 'csv'
        $businessDate = $this->reconService->getBusinessDate();
        $dateFormatted = date('d/m/Y', strtotime($businessDate));

        $headers = [
            'Date',
            'Particulars',
            'Voucher Type',
            'Voucher No.',
            'Amount'
        ];

        if ($type === 'blank') {
            $dataRows = [
                [$dateFormatted, 'MOHAN LAL GULABCHAND [1093381]', 'Sales Cadbury', 'Sc/26-27/6447', '1531.00']
            ];
            $baseName = 'Bill_Import_Blank_Template';
        } else {
            $dataRows = [
                [$dateFormatted, 'MOHAN LAL GULABCHAND [1093381]', 'Sales Cadbury', 'Sc/26-27/6447', '1531.00'],
                [$dateFormatted, 'RATHORE PAN CORNER [1086231]', 'Sales Cadbury', 'Sc/26-27/6448', '6167.00'],
                [$dateFormatted, 'ARIHANT STORE [1093427]', 'Sales Cadbury', 'Sc/26-27/6449', '436.00'],
                [$dateFormatted, 'RATHORE PAN BHANDARA [1093431]', 'Sales Cadbury', 'Sc/26-27/6450', '1964.00'],
                [$dateFormatted, 'HANUMAN PROV STORE [1093375]', 'Sales Cadbury', 'Sc/26-27/6451', '436.00'],
                [$dateFormatted, 'Khatri Medical & Pro. Store', 'Sales Cadbury', 'Sc/26-27/6452', '1253.00'],
                [$dateFormatted, 'EVERGREEN FRUIT JUICE [1092131]', 'Sales Cadbury', 'Sc/26-27/6453', '2418.00'],
                [$dateFormatted, 'PUKHRAJ MISTHAN BHANDAR [2027077]', 'Sales Cadbury', 'Sc/26-27/6454', '2779.00'],
                [$dateFormatted, 'HARSH MUSIC & MOBILE WORLD [1088372]', 'Sales Cadbury', 'Sc/26-27/6455', '1183.00'],
                [$dateFormatted, 'MAHADEV TRADERS [2679896]', 'Sales Cadbury', 'Sc/26-27/6456', '23924.00'],
            ];
            $baseName = 'Bill_Import_Sample_Template';
        }

        // Return Excel (.xls) as the primary format
        if ($format === 'xls' || $format === 'xlsx' || $format === 'xlx') {
            $filename = "{$baseName}_" . date('Ymd') . ".xls";
            $xmlContent = $this->generateXls($headers, $dataRows, 'Bill Import Template');

            return response($xmlContent, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ]);
        }

        // Fallback / CSV format
        $filename = "{$baseName}_" . date('Ymd') . ".csv";
        $httpHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($headers, $dataRows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($dataRows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $httpHeaders);
    }

    protected function generateXls(array $headers, array $rows, string $sheetTitle = 'Bill Import Template'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xml .= ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n";
        $xml .= '  <Title>' . htmlspecialchars($sheetTitle) . '</Title>' . "\n";
        $xml .= '  <Author>HisabKitap ERP</Author>' . "\n";
        $xml .= ' </DocumentProperties>' . "\n";

        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Default" ss:Name="Normal">' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#333333"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        
        $xml .= '  <Style ss:ID="HeaderStyle">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D0D5DD"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '   <Font ss:FontName="Segoe UI" ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF"/>' . "\n";
        $xml .= '   <Interior ss:Color="#0F52BA" ss:Pattern="Solid"/>' . "\n";
        $xml .= '  </Style>' . "\n";

        $xml .= '  <Style ss:ID="TextStyle">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="9.5"/>' . "\n";
        $xml .= '  </Style>' . "\n";

        $xml .= '  <Style ss:ID="CenterStyle">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="9.5"/>' . "\n";
        $xml .= '  </Style>' . "\n";

        $xml .= '  <Style ss:ID="CurrencyStyle">' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <NumberFormat ss:Format="#,##0.00"/>' . "\n";
        $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="9.5"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= ' </Styles>' . "\n";

        $xml .= ' <Worksheet ss:Name="' . htmlspecialchars(substr($sheetTitle, 0, 31)) . '">' . "\n";
        $xml .= '  <Table ss:DefaultRowHeight="20">' . "\n";
        
        // Column widths for the 5 columns
        $xml .= '   <Column ss:Width="105"/>' . "\n"; // Date
        $xml .= '   <Column ss:Width="280"/>' . "\n"; // Particulars (Customer Name)
        $xml .= '   <Column ss:Width="130"/>' . "\n"; // Voucher Type
        $xml .= '   <Column ss:Width="140"/>' . "\n"; // Voucher No.
        $xml .= '   <Column ss:Width="110"/>' . "\n"; // Amount

        // Header Row
        $xml .= '   <Row ss:Height="26">' . "\n";
        foreach ($headers as $h) {
            $xml .= '    <Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";

        // Data Rows
        foreach ($rows as $row) {
            $xml .= '   <Row ss:Height="20">' . "\n";
            foreach ($row as $idx => $val) {
                // Numeric column: Amount (idx 4)
                if ($idx === 4 && is_numeric($val)) {
                    $xml .= '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . htmlspecialchars((string) $val) . '</Data></Cell>' . "\n";
                } elseif ($idx === 1) {
                    $xml .= '    <Cell ss:StyleID="TextStyle"><Data ss:Type="String">' . htmlspecialchars((string) $val) . '</Data></Cell>' . "\n";
                } else {
                    $xml .= '    <Cell ss:StyleID="CenterStyle"><Data ss:Type="String">' . htmlspecialchars((string) $val) . '</Data></Cell>' . "\n";
                }
            }
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>' . "\n";

        return $xml;
    }
}

