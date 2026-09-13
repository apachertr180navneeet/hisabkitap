<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cash Denomination Slip - {{ date('d/m/Y', strtotime($businessDate)) }} | HisabKitap ERP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: #1e293b;
      background-color: #f8fafc;
      font-size: 0.9rem;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace;
    }
    .print-container {
      max-width: 900px;
      margin: 20px auto;
      background: #ffffff;
      padding: 32px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      border: 1px solid #e2e8f0;
    }
    .slip-header {
      border-bottom: 2px solid #0f172a;
      padding-bottom: 16px;
      margin-bottom: 20px;
    }
    .table-custom th {
      background-color: #f1f5f9;
      color: #334155;
      font-weight: 600;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .table-custom td {
      font-size: 0.88rem;
    }
    .signature-box {
      border-top: 1px dashed #94a3b8;
      padding-top: 8px;
      margin-top: 40px;
      text-align: center;
      font-size: 0.82rem;
      color: #475569;
    }
    @media print {
      body {
        background-color: #ffffff !important;
        font-size: 11pt;
      }
      .no-print {
        display: none !important;
      }
      .print-container {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
      }
      .page-break {
        page-break-after: always;
      }
      @page {
        size: A4 portrait;
        margin: 10mm;
      }
    }
  </style>
</head>
<body>

  <!-- Floating Action Bar for Web View -->
  <div class="no-print bg-dark text-white py-2 px-3 sticky-top shadow-sm">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2" style="max-width: 900px;">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-receipt-cutoff text-warning fs-5"></i>
        <span class="fw-bold">Cash Denomination Slip & Driver Handover</span>
        <span class="badge bg-secondary font-mono">{{ date('d M Y', strtotime($businessDate)) }}</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.denomination.index', ['date' => $businessDate, 'pso' => $selectedPso]) }}" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Counter
        </a>
        <a href="{{ route('admin.denomination.export_excel', ['date' => $businessDate, 'pso' => $selectedPso]) }}" class="btn btn-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
          <i class="bi bi-printer-fill me-1"></i> Print / Save PDF
        </button>
      </div>
    </div>
  </div>

  <div class="print-container">
    <!-- Header -->
    <div class="slip-header d-flex justify-content-between align-items-start">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="bg-dark text-white p-2 rounded">
            <i class="bi bi-shield-check fs-4"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">HISABKITAP ERP</h4>
            <span class="text-muted small">Fuel Station & PSO Reconciliation Core</span>
          </div>
        </div>
        <div class="fw-semibold text-secondary mt-1">PHYSICAL CASH DENOMINATION & HANDOVER SLIP</div>
      </div>
      <div class="text-end">
        <div class="badge bg-dark fs-6 px-3 py-2 font-mono mb-1">
          DATE: {{ date('d/m/Y', strtotime($businessDate)) }}
        </div>
        <div class="text-muted small">Generated: {{ now()->format('d/m/Y h:i A') }}</div>
        @if($selectedPso !== 'ALL' && !empty($selectedPso))
          <span class="badge bg-primary font-mono mt-1">PSO: {{ $selectedPso }}</span>
        @else
          <span class="badge bg-secondary font-mono mt-1">ALL PSO COUNTERS</span>
        @endif
      </div>
    </div>

    @php
      $activeSlip = $denominations->first();
    @endphp

    <!-- Metadata Grid -->
    <div class="row g-2 mb-4 p-3 bg-light rounded border">
      <div class="col-3">
        <span class="text-muted small d-block">PSO Counter / Series:</span>
        <strong class="font-mono text-primary fs-6">{{ $activeSlip ? ($activeSlip->pso_code ?: 'General Cashier') : ($selectedPso ?: 'General') }}</strong>
      </div>
      <div class="col-3">
        <span class="text-muted small d-block">Driver / Handover By:</span>
        <strong class="text-dark">{{ $activeSlip ? ($activeSlip->driver_name ?: '—') : '—' }}</strong>
      </div>
      <div class="col-3">
        <span class="text-muted small d-block">Vehicle / Gadi No:</span>
        <strong class="font-mono text-dark">{{ $activeSlip ? ($activeSlip->gadi_number ?: '—') : '—' }}</strong>
      </div>
      <div class="col-3 text-end">
        <span class="text-muted small d-block">Cashier / Recorded By:</span>
        <strong class="text-dark">{{ $activeSlip ? ($activeSlip->cashier_name ?: session('active_user.name', 'Cashier')) : session('active_user.name', 'Cashier') }}</strong>
      </div>
    </div>

    <!-- 4 Summary KPI Boxes -->
    <div class="row g-2 mb-4">
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Book Cash (Gross)</small>
          <span class="fs-5 fw-bold font-mono text-primary">₹{{ number_format($totals['book_cash_amount'], 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Counted Physical Cash</small>
          <span class="fs-5 fw-bold font-mono text-success">₹{{ number_format($totals['total_physical_cash'], 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Driver KM Allowance</small>
          <span class="fs-5 fw-bold font-mono text-info">₹{{ number_format($totals['km_allowance_amount'], 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Variance / Short Cash</small>
          <span class="fs-5 fw-bold font-mono {{ $totals['short_cash_amount'] > 0 ? 'text-danger' : 'text-success' }}">
            ₹{{ number_format($totals['short_cash_amount'], 2) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Denominations Table -->
    <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-cash-stack text-success me-1"></i>Physical Currency Notes Count</h6>
    <table class="table table-bordered table-custom table-sm align-middle text-center mb-4">
      <thead>
        <tr>
          <th style="width: 35%;" class="text-start ps-3">Denomination</th>
          <th style="width: 25%;">Note / Coin Count</th>
          <th style="width: 40%;" class="text-end pe-3">Total Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 500 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_500']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_500'], 2) }}</td>
        </tr>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 200 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_200']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_200'], 2) }}</td>
        </tr>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 100 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_100']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_100'], 2) }}</td>
        </tr>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 50 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_50']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_50'], 2) }}</td>
        </tr>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 20 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_20']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_20'], 2) }}</td>
        </tr>
        <tr>
          <td class="text-start ps-3 fw-bold">₹ 10 Note</td>
          <td class="font-mono">{{ number_format($totals['notes_10']) }}</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['amt_10'], 2) }}</td>
        </tr>
        <tr class="table-light">
          <td class="text-start ps-3 fw-bold">Coins Total (₹)</td>
          <td class="font-mono text-muted">—</td>
          <td class="text-end pe-3 font-mono fw-bold">₹{{ number_format($totals['coins_total'], 2) }}</td>
        </tr>
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <th class="text-start ps-3 fs-6">GRAND TOTAL PHYSICAL CASH:</th>
          <th class="font-mono text-center">{{ number_format($totals['notes_500'] + $totals['notes_200'] + $totals['notes_100'] + $totals['notes_50'] + $totals['notes_20'] + $totals['notes_10']) }} Notes</th>
          <th class="text-end pe-3 fs-6 font-mono text-warning">₹{{ number_format($totals['total_physical_cash'], 2) }}</th>
        </tr>
      </tfoot>
    </table>

    <!-- Driver KM Allowance Details (if logged) -->
    @if($totals['total_km'] > 0 || $totals['km_allowance_amount'] > 0)
    <div class="p-3 bg-light rounded border mb-4">
      <div class="row g-2 align-items-center">
        <div class="col-4">
          <span class="text-muted small d-block">Trip KM Logged:</span>
          <strong class="font-mono fs-6">{{ number_format($totals['total_km'], 1) }} KM</strong>
        </div>
        <div class="col-4">
          <span class="text-muted small d-block">Rate Applied:</span>
          <strong class="font-mono fs-6">₹{{ $activeSlip ? number_format($activeSlip->km_rate, 2) : '—' }} / KM</strong>
        </div>
        <div class="col-4 text-end">
          <span class="text-muted small d-block">Total KM Allowance Deducted:</span>
          <strong class="font-mono fs-6 text-info">₹{{ number_format($totals['km_allowance_amount'], 2) }}</strong>
        </div>
      </div>
    </div>
    @endif

    <!-- Reconciliation Settlement Block -->
    <div class="border rounded p-3 mb-4 bg-white">
      <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-shield-check text-primary me-1"></i>Settlement & Discrepancy Breakdown</h6>
      <div class="row g-2">
        <div class="col-6">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Expected Book Cash:</span>
            <span class="font-mono fw-bold">₹{{ number_format($totals['book_cash_amount'], 2) }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Less: KM Allowance Deduction:</span>
            <span class="font-mono text-info">- ₹{{ number_format($totals['km_allowance_amount'], 2) }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom fw-bold">
            <span class="text-dark">Net Expected Cash Deposit:</span>
            <span class="font-mono text-primary">₹{{ number_format(max(0, $totals['book_cash_amount'] - $totals['km_allowance_amount']), 2) }}</span>
          </div>
        </div>
        <div class="col-6">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Actual Physical Cash Handed Over:</span>
            <span class="font-mono fw-bold text-success">₹{{ number_format($totals['total_physical_cash'], 2) }}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Discrepancy (Short Cash):</span>
            <span class="font-mono fw-bold {{ $totals['short_cash_amount'] > 0 ? 'text-danger' : 'text-success' }}">
              {{ $totals['short_cash_amount'] > 0 ? '₹' . number_format($totals['short_cash_amount'], 2) . ' (SHORT)' : '₹0.00 (BALANCED)' }}
            </span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Settlement Status:</span>
            <span class="badge {{ $totals['short_cash_amount'] > 0 ? 'bg-danger' : 'bg-success' }}">
              {{ $totals['short_cash_amount'] > 0 ? 'Pending Recovery' : 'Fully Reconciled' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Signatures -->
    <div class="row g-4 mt-5">
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold">{{ $activeSlip ? ($activeSlip->driver_name ?: 'Driver / Handover By') : 'Driver / Handover By' }}</div>
          <small class="text-muted">Driver / Delivery Person Signature</small>
        </div>
      </div>
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold">{{ $activeSlip ? ($activeSlip->cashier_name ?: 'Cashier') : 'Cashier' }}</div>
          <small class="text-muted">Cashier / Counter In-charge</small>
        </div>
      </div>
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold">Manager / Accounts</div>
          <small class="text-muted">Supervisor / Authorized Signatory</small>
        </div>
      </div>
    </div>

    <!-- Footer Note -->
    <div class="mt-4 pt-3 border-top text-center text-muted small">
      <p class="mb-0">This is a system generated cash denomination handover slip from HisabKitap ERP. Document verification reference ID: HK-DENOM-{{ date('Ymd', strtotime($businessDate)) }}-{{ $activeSlip ? $activeSlip->id : 'ALL' }}.</p>
    </div>
  </div>

</body>
</html>
