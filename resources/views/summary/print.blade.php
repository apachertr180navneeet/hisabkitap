<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PSO Summary Matrix Report - {{ date('d/m/Y', strtotime($businessDate)) }} | HisabKitap ERP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; color: #1e293b; background-color: #f8fafc; font-size: 0.85rem; }
    .font-mono { font-family: 'JetBrains Mono', monospace; }
    .print-container { max-width: 1100px; margin: 20px auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
    .table th { background-color: #f1f5f9; color: #334155; font-size: 0.78rem; text-transform: uppercase; }
    .signature-box { border-top: 1px dashed #94a3b8; padding-top: 6px; margin-top: 35px; text-align: center; font-size: 0.8rem; }
    @media print {
      body { background-color: #fff !important; font-size: 9.5pt; }
      .no-print { display: none !important; }
      .print-container { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
      @page { size: A4 landscape; margin: 8mm; }
    }
  </style>
</head>
<body>

  <div class="no-print bg-dark text-white py-2 px-3 sticky-top shadow-sm">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2" style="max-width: 1100px;">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-grid-3x3-gap-fill text-warning fs-5"></i>
        <span class="fw-bold">PSO Summary Matrix Collection Report</span>
        <span class="badge bg-secondary font-mono">{{ date('d M Y', strtotime($businessDate)) }}</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.summary.index') }}" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admin.summary.export_excel', ['date' => $businessDate]) }}" class="btn btn-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
          <i class="bi bi-printer-fill me-1"></i> Print / Save PDF
        </button>
      </div>
    </div>
  </div>

  <div class="print-container">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
      <div class="d-flex align-items-center gap-2">
        <div class="bg-dark text-white p-2 rounded"><i class="bi bi-shield-check fs-4"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark">HISABKITAP ERP</h4>
          <span class="text-muted small">Daily PSO Counter Collections & Reconciliation Summary Matrix</span>
        </div>
      </div>
      <div class="text-end">
        <div class="badge bg-dark fs-6 px-3 py-2 font-mono mb-1">DATE: {{ date('d/m/Y', strtotime($businessDate)) }}</div>
        <div class="text-muted small">Generated: {{ now()->format('d/m/Y h:i A') }}</div>
      </div>
    </div>

    <!-- Matrix Table -->
    <table class="table table-bordered table-sm align-middle text-center mb-4">
      <thead>
        <tr>
          <th class="text-start">PSO Code & Series</th>
          <th>Bills</th>
          <th class="text-end">Gross Sales (₹)</th>
          <th class="text-end">Cash (₹)</th>
          <th class="text-end">Paytm (₹)</th>
          <th class="text-end">Cheque (₹)</th>
          <th class="text-end">Credit (₹)</th>
          <th class="text-end">Cancelled (₹)</th>
          <th class="text-end">CD (₹)</th>
          <th class="text-end">Refund (₹)</th>
          <th class="text-end">Net Collection (₹)</th>
        </tr>
      </thead>
      <tbody>
        @php
          $totGross = 0; $totCash = 0; $totPaytm = 0; $totCheck = 0; $totCredit = 0;
          $totCancelled = 0; $totCd = 0; $totRefund = 0; $totNet = 0; $totBills = 0;
        @endphp
        @forelse($matrixRows as $row)
          @php
            $totGross += $row['gross'];
            $totCash += $row['cash'];
            $totPaytm += $row['paytm'];
            $totCheck += $row['check'];
            $totCredit += $row['credit'];
            $totCancelled += $row['cancelled'];
            $totCd += $row['cd'];
            $totRefund += $row['refund'];
            $totNet += $row['net'];
            $totBills += $row['billsCount'];
          @endphp
          <tr>
            <td class="text-start">
              <strong class="font-mono text-primary">{{ $row['pso']->code }}</strong>
              <div class="small text-muted">{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }}-{{ sprintf('%02d', $row['pso']->end_no) }} | {{ $row['pso']->operator_name }}</div>
            </td>
            <td class="font-mono fw-bold">{{ $row['billsCount'] }}</td>
            <td class="text-end font-mono">₹{{ number_format($row['gross'], 2) }}</td>
            <td class="text-end font-mono text-success">₹{{ number_format($row['cash'], 2) }}</td>
            <td class="text-end font-mono text-info">₹{{ number_format($row['paytm'], 2) }}</td>
            <td class="text-end font-mono text-primary">₹{{ number_format($row['check'], 2) }}</td>
            <td class="text-end font-mono text-warning">₹{{ number_format($row['credit'], 2) }}</td>
            <td class="text-end font-mono text-muted">₹{{ number_format($row['cancelled'], 2) }}</td>
            <td class="text-end font-mono text-danger">{{ $row['cd'] > 0 ? ('-₹' . number_format($row['cd'], 2)) : '—' }}</td>
            <td class="text-end font-mono text-danger">{{ $row['refund'] > 0 ? ('-₹' . number_format($row['refund'], 2)) : '—' }}</td>
            <td class="text-end font-mono fw-bold text-dark">₹{{ number_format($row['net'], 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="11" class="py-3 text-muted">No PSO summary data available for this date.</td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <th class="text-start ps-3">TOTAL ({{ count($matrixRows) }} PSOS):</th>
          <th class="font-mono">{{ $totBills }}</th>
          <th class="text-end font-mono">₹{{ number_format($totGross, 2) }}</th>
          <th class="text-end font-mono text-success">₹{{ number_format($totCash, 2) }}</th>
          <th class="text-end font-mono text-info">₹{{ number_format($totPaytm, 2) }}</th>
          <th class="text-end font-mono text-primary">₹{{ number_format($totCheck, 2) }}</th>
          <th class="text-end font-mono text-warning">₹{{ number_format($totCredit, 2) }}</th>
          <th class="text-end font-mono text-muted">₹{{ number_format($totCancelled, 2) }}</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($totCd, 2) }}</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($totRefund, 2) }}</th>
          <th class="text-end font-mono text-warning">₹{{ number_format($totNet, 2) }}</th>
        </tr>
      </tfoot>
    </table>

    <div class="row g-4 mt-4">
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Head Cashier</div><small class="text-muted">Calculated & Compiled By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Chief Accountant</div><small class="text-muted">Reconciled & Audited By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">General Manager / Owner</div><small class="text-muted">Approved Signatory</small></div></div>
    </div>
  </div>

</body>
</html>
