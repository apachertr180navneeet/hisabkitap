<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Credit Collections & Field Recovery Sheet - {{ date('d/m/Y', strtotime($businessDate)) }} | HisabKitap ERP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; color: #1e293b; background-color: #f8fafc; font-size: 0.85rem; }
    .font-mono { font-family: 'JetBrains Mono', monospace; }
    .print-container { max-width: 1050px; margin: 20px auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
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
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2" style="max-width: 1050px;">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-person-lines-fill text-warning fs-5"></i>
        <span class="fw-bold">Credit Collection & Outstanding Field Recovery Sheet</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.credit.index') }}" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admin.credit.export_excel') }}" class="btn btn-success btn-sm">
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
          <span class="text-muted small">Customer Credit Outstanding & Field Recovery Sheet</span>
        </div>
      </div>
      <div class="text-end">
        <div class="badge bg-dark fs-6 px-3 py-2 font-mono mb-1">DATE: {{ date('d/m/Y', strtotime($businessDate)) }}</div>
        <div class="text-muted small">Generated: {{ now()->format('d/m/Y h:i A') }}</div>
      </div>
    </div>

    <!-- 3 KPI Summary Boxes -->
    <div class="row g-2 mb-3">
      <div class="col-4">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Credit Sales</small>
          <span class="fs-5 fw-bold font-mono text-warning">₹{{ number_format($totSales, 2) }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Recovered</small>
          <span class="fs-5 fw-bold font-mono text-success">₹{{ number_format($totRecovered, 2) }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Outstanding Field Balance</small>
          <span class="fs-5 fw-bold font-mono text-danger">₹{{ number_format($totOutstanding, 2) }}</span>
        </div>
      </div>
    </div>

    <!-- Credit Table -->
    <table class="table table-bordered table-sm align-middle text-center mb-4">
      <thead>
        <tr>
          <th>Bill No.</th>
          <th class="text-start">Customer</th>
          <th>Salesman</th>
          <th>Bill Date</th>
          <th>Due Date</th>
          <th class="text-end">Total Bill (₹)</th>
          <th class="text-end">Paid (₹)</th>
          <th class="text-end">Outstanding (₹)</th>
          <th>Status</th>
          <th>Salesman Sign</th>
        </tr>
      </thead>
      <tbody>
        @forelse($credits as $c)
          @php
            $bDate = $c->bill_date ? (is_string($c->bill_date) ? substr($c->bill_date, 0, 10) : $c->bill_date->format('d/m/Y')) : '—';
            $dDate = $c->due_date ? (is_string($c->due_date) ? substr($c->due_date, 0, 10) : $c->due_date->format('d/m/Y')) : '—';
          @endphp
          <tr>
            <td class="font-mono fw-bold text-primary">{{ $c->bill_no }}</td>
            <td class="text-start fw-semibold">{{ $c->customer_name }}</td>
            <td>{{ $c->salesman_name ?: '—' }}</td>
            <td class="font-mono small">{{ $bDate }}</td>
            <td class="font-mono small text-danger">{{ $dDate }}</td>
            <td class="text-end font-mono">₹{{ number_format($c->bill_amount, 2) }}</td>
            <td class="text-end font-mono text-success">₹{{ number_format($c->paid_amount, 2) }}</td>
            <td class="text-end font-mono fw-bold text-danger">₹{{ number_format($c->outstanding_amount, 2) }}</td>
            <td><span class="badge {{ $c->collection_status === 'Collected' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $c->collection_status }}</span></td>
            <td style="width: 120px;"></td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="py-3 text-muted">No credit records available.</td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <th colspan="5" class="text-start ps-3">TOTAL ({{ count($credits) }} CUSTOMERS):</th>
          <th class="text-end font-mono text-warning">₹{{ number_format($totSales, 2) }}</th>
          <th class="text-end font-mono text-success">₹{{ number_format($totRecovered, 2) }}</th>
          <th class="text-end font-mono text-danger">₹{{ number_format($totOutstanding, 2) }}</th>
          <th colspan="2"></th>
        </tr>
      </tfoot>
    </table>

    <div class="row g-4 mt-4">
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Field Salesman / Recovery</div><small class="text-muted">Handed Over By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Cashier / Credit In-Charge</div><small class="text-muted">Received & Credited By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Accounts Manager</div><small class="text-muted">Approved Signatory</small></div></div>
    </div>
  </div>

</body>
</html>
