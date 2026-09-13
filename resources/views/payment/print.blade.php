<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Classification Ledger - {{ $businessDate !== 'ALL' ? date('d/m/Y', strtotime($businessDate)) : 'All Dates' }} | HisabKitap ERP</title>
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
        <i class="bi bi-wallet2 text-warning fs-5"></i>
        <span class="fw-bold">Payment Classification & Ledger Routing</span>
        <span class="badge bg-secondary font-mono">{{ $businessDate !== 'ALL' ? date('d M Y', strtotime($businessDate)) : 'All Dates' }}</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.payment.index', request()->query()) }}" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admin.payment.export_excel', request()->query()) }}" class="btn btn-success btn-sm">
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
          <span class="text-muted small">Payment Classification & Gross Collection Ledger</span>
        </div>
      </div>
      <div class="text-end">
        <div class="badge bg-dark fs-6 px-3 py-2 font-mono mb-1">DATE: {{ $businessDate !== 'ALL' ? date('d/m/Y', strtotime($businessDate)) : 'ALL DATES' }}</div>
        <div class="text-muted small">Generated: {{ now()->format('d/m/Y h:i A') }}</div>
      </div>
    </div>

    <!-- 5 KPI Summary Boxes -->
    <div class="row g-2 mb-3">
      <div class="col">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Cash Total</small>
          <span class="fs-6 fw-bold font-mono text-success">₹{{ number_format($metrics['totCash'], 2) }}</span>
        </div>
      </div>
      <div class="col">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Paytm / Digital</small>
          <span class="fs-6 fw-bold font-mono text-info">₹{{ number_format($metrics['totPaytm'], 2) }}</span>
        </div>
      </div>
      <div class="col">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Cheques</small>
          <span class="fs-6 fw-bold font-mono text-primary">₹{{ number_format($metrics['totCheck'], 2) }}</span>
        </div>
      </div>
      <div class="col">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Credit Sales</small>
          <span class="fs-6 fw-bold font-mono text-warning">₹{{ number_format($metrics['totCredit'], 2) }}</span>
        </div>
      </div>
      <div class="col">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Collections</small>
          <span class="fs-6 fw-bold font-mono text-dark">₹{{ number_format($metrics['totNet'], 2) }}</span>
        </div>
      </div>
    </div>

    <!-- Bills Table -->
    <table class="table table-bordered table-sm align-middle text-center mb-4">
      <thead>
        <tr>
          <th>Bill No</th>
          <th>Date</th>
          <th>PSO</th>
          <th class="text-start">Customer Name</th>
          <th>Salesman</th>
          <th>Payment Type</th>
          <th class="text-end">Gross (₹)</th>
          <th class="text-end">CD / Refund (₹)</th>
          <th class="text-end">Net Amount (₹)</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $b)
          @php
            $calcNet = max(0, (float)$b->amount - (float)$b->cd_amount - (float)$b->refund_amount);
            $net = (float) ($b->net_amount > 0 ? $b->net_amount : $calcNet);
            $bDate = $b->business_date ? (is_string($b->business_date) ? substr($b->business_date, 0, 10) : $b->business_date->format('d/m/Y')) : '';
          @endphp
          <tr>
            <td class="font-mono fw-bold">{{ $b->bill_no }}</td>
            <td class="font-mono small">{{ $bDate }}</td>
            <td><span class="badge bg-light text-dark border font-mono">{{ $b->pso_code }}</span></td>
            <td class="text-start">{{ $b->customer_name }}</td>
            <td class="small">{{ $b->salesman_name ?: '—' }}</td>
            <td>
              @if($b->is_split_payment)
                <span class="badge bg-warning text-dark font-mono">Split: ₹{{ number_format($b->cash_amount) }}C + ₹{{ number_format($b->paytm_amount) }}P</span>
              @else
                <span class="badge bg-secondary font-mono">{{ $b->payment_type }}</span>
              @endif
            </td>
            <td class="text-end font-mono">₹{{ number_format($b->amount, 2) }}</td>
            <td class="text-end font-mono text-danger">{{ ($b->cd_amount > 0 || $b->refund_amount > 0) ? ('-₹' . number_format($b->cd_amount + $b->refund_amount, 2)) : '—' }}</td>
            <td class="text-end font-mono fw-bold text-dark">₹{{ number_format($net, 2) }}</td>
            <td><span class="badge {{ $b->status === 'Matched' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $b->status }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="py-3 text-muted">No bill records match current filter scope.</td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <th colspan="6" class="text-start ps-3">TOTAL ({{ count($bills) }} BILLS):</th>
          <th class="text-end font-mono">₹{{ number_format($bills->sum('amount'), 2) }}</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($bills->sum('cd_amount') + $bills->sum('refund_amount'), 2) }}</th>
          <th class="text-end font-mono text-warning">₹{{ number_format($metrics['totNet'], 2) }}</th>
          <th></th>
        </tr>
      </tfoot>
    </table>

    <div class="row g-4 mt-4">
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Counter Operator</div><small class="text-muted">Prepared By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Cashier / Accounts</div><small class="text-muted">Verified & Reconciled By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Manager</div><small class="text-muted">Approved Signatory</small></div></div>
    </div>
  </div>

</body>
</html>
