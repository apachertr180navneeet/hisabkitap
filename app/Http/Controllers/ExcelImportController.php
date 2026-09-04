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
        $psoList = PsoConfig::where('is_active', true)->get();
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

        $psoConfigs = PsoConfig::where('is_active', true)->get();

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

            // Find header row (search first 10 rows in case of company/date headers)
            $headerIndex = -1;
            $normalizedHeader = [];
            $colMap = [];

            $dateKeys = ['date', 'dt', 'bill date', 'vch date', 'business date', 'invoice date'];
            $custKeys = ['particulars', 'particular', 'party name', 'party', 'customer name', 'customer', 'ledger', 'party / ledger', 'account', 'party name / ledger'];
            $typeKeys = ['voucher type', 'vch type', 'sales type', 'type', 'vouchertype', 'trn type'];
            $billKeys = ['voucher no.', 'voucher no', 'vch no', 'vch no.', 'bill no', 'bill_no', 'bill number', 'invoice no', 'invoice #', 'bill', 'billno', 'vch #'];
            $amtKeys = ['amount', 'total amount', 'bill amount', 'gross amount', 'total', 'debit', 'credit', 'billamount', 'net amount'];

            foreach (array_slice($parsedRows, 0, 10, true) as $idx => $row) {
                $norm = array_map(function ($col) {
                    $col = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string)$col);
                    return strtolower(trim($col));
                }, $row);

                $foundBill = $this->findColumnIndex($norm, $billKeys);
                $foundAmt = $this->findColumnIndex($norm, $amtKeys);

                if ($foundBill !== null || $foundAmt !== null) {
                    $headerIndex = $idx;
                    $normalizedHeader = $norm;
                    $colMap = [
                        'date' => $this->findColumnIndex($norm, $dateKeys),
                        'customer_name' => $this->findColumnIndex($norm, $custKeys),
                        'voucher_type' => $this->findColumnIndex($norm, $typeKeys),
                        'bill_no' => $foundBill,
                        'amount' => $foundAmt,
                    ];
                    break;
                }
            }

            // If header was not explicitly found by names, fallback if first row has 4+ columns
            if ($headerIndex === -1) {
                $firstRow = $parsedRows[0];
                if (count($firstRow) >= 4) {
                    $headerIndex = 0;
                    $colMap = [
                        'date' => 0,
                        'customer_name' => 1,
                        'voucher_type' => 2,
                        'bill_no' => 3,
                        'amount' => count($firstRow) > 4 ? 4 : 3,
                    ];
                } else {
                    return redirect()->back()->withInput()->with('error', "Invalid spreadsheet format in '{$filename}': Could not identify required columns (Voucher No., Amount, Particulars, Date). Please download the sample template.");
                }
            }

            // Ensure essential columns are mapped
            if ($colMap['bill_no'] === null && $colMap['amount'] === null) {
                return redirect()->back()->withInput()->with('error', "Missing required columns in '{$filename}': Both 'Voucher No.' and 'Amount' columns could not be identified.");
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
                
                // Skip header repetitions or grand total rows
                if (empty($rawBillNo) || stripos($rawBillNo, 'voucher') !== false || stripos($rawBillNo, 'bill no') !== false || stripos($rawBillNo, 'total') !== false) {
                    if (!empty($rawBillNo) && (stripos($rawBillNo, 'total') !== false || stripos($rawBillNo, 'grand total') !== false)) {
                        $rowNum++;
                        continue;
                    }
                    if (empty($rawBillNo)) {
                        $rowErrors[] = "Row {$rowNum}: Skipped due to missing Voucher/Bill No.";
                        $rowNum++;
                        continue;
                    }
                }

                $billNo = $rawBillNo;

                // Validate and parse amount
                $rawAmount = ($colMap['amount'] !== null && isset($row[$colMap['amount']])) ? trim((string)$row[$colMap['amount']]) : '0';
                $cleanAmount = str_replace([',', ' ', '₹', '$'], '', $rawAmount);
                if (!is_numeric($cleanAmount)) {
                    $rowErrors[] = "Row {$rowNum} (Bill '{$billNo}'): Invalid amount format '{$rawAmount}'. Amount must be numeric.";
                    $rowNum++;
                    continue;
                }

                $amount = (float) $cleanAmount;
                if ($amount < 0) {
                    $rowErrors[] = "Row {$rowNum} (Bill '{$billNo}'): Negative amount ₹{$amount} not allowed.";
                    $rowNum++;
                    continue;
                }

                // Date Parsing
                $rawDate = ($colMap['date'] !== null && !empty($row[$colMap['date']])) ? trim((string)$row[$colMap['date']]) : '';
                if (!empty($rawDate)) {
                    if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/', $rawDate, $matches)) {
                        $year = (int)$matches[3];
                        if ($year < 100) {
                            $year += 2000;
                        }
                        $rowDate = sprintf('%04d-%02d-%02d', $year, (int)$matches[2], (int)$matches[1]);
                    } else {
                        $parsedTime = strtotime(str_replace('/', '-', $rawDate));
                        $rowDate = ($parsedTime !== false && $parsedTime > 0) ? date('Y-m-d', $parsedTime) : $businessDate;
                    }
                } else {
                    $rowDate = $businessDate;
                }

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

    protected function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle !== false) {
            // Read first line to detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);

            $delimiter = ',';
            if ($firstLine !== false) {
                // Strip UTF-8 BOM if present
                if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
                    fseek($handle, 3);
                }
                $commaCount = substr_count($firstLine, ',');
                $semicolonCount = substr_count($firstLine, ';');
                $tabCount = substr_count($firstLine, "\t");

                if ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                    $delimiter = "\t";
                } elseif ($semicolonCount > $commaCount) {
                    $delimiter = ';';
                }
            }

            while (($data = fgetcsv($handle, 8000, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    protected function parseXlsFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (empty($content)) {
            return [];
        }

        // Check if SpreadsheetML XML format
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
                    // Check ss:Index for skipped columns
                    if ($c->hasAttribute('ss:Index')) {
                        $colIndex = (int)$c->getAttribute('ss:Index') - 1;
                    }
                    $dataElements = $c->getElementsByTagName('Data');
                    $cellVal = ($dataElements->length > 0) ? trim($dataElements->item(0)->textContent) : trim($c->textContent);
                    $rowVals[$colIndex] = $cellVal;
                    $colIndex++;
                }
                if (!empty(array_filter($rowVals, fn($v) => $v !== null && $v !== ''))) {
                    // Re-index array keys 0..max
                    $maxKey = !empty($rowVals) ? max(array_keys($rowVals)) : 0;
                    $normalizedRow = [];
                    for ($i = 0; $i <= $maxKey; $i++) {
                        $normalizedRow[$i] = $rowVals[$i] ?? '';
                    }
                    $rows[] = $normalizedRow;
                }
            }
            return $rows;
        }

        // Check if HTML Table format
        if (stripos($content, '<table') !== false && stripos($content, '<tr') !== false) {
            $rows = [];
            $dom = new \DOMDocument();
            @$dom->loadHTML($content);
            $trElements = $dom->getElementsByTagName('tr');
            foreach ($trElements as $tr) {
                $rowVals = [];
                $cells = $tr->getElementsByTagName('td');
                if ($cells->length === 0) {
                    $cells = $tr->getElementsByTagName('th');
                }
                foreach ($cells as $c) {
                    $rowVals[] = trim($c->textContent);
                }
                if (!empty(array_filter($rowVals, fn($v) => $v !== null && $v !== ''))) {
                    $rows[] = $rowVals;
                }
            }
            return $rows;
        }

        // Fallback to CSV parser
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
                if (preg_match('#xl/worksheets/sheet\d+\.xml#i', $stat['name'])) {
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
                foreach ($cells as $c) {
                    $coord = $c->getAttribute('r'); // e.g. A1, B1, E1
                    $colIdx = 0;
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
                    }

                    $rowVals[$colIdx] = $val;
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

