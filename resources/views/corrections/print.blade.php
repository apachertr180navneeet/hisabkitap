<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Corrections, Cash Discounts & Returns Audit Register | HisabKitap ERP</title>
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
        <i class="bi bi-pencil-square text-warning fs-5"></i>
        <span class="fw-bold">Corrections, CD Discounts & Goods Return Ledger</span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.corrections.index') }}" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admin.corrections.export_excel') }}" class="btn btn-success btn-sm">
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
          <span class="text-muted small">Post-Billing Deductions & Return Audit Register</span>
        </div>
      </div>
      <div class="text-end">
        <div class="badge bg-dark fs-6 px-3 py-2 font-mono mb-1">DATE: {{ date('d/m/Y', strtotime($businessDate)) }}</div>
        <div class="text-muted small">Generated: {{ now()->format('d/m/Y h:i A') }}</div>
      </div>
    </div>

    <!-- 4 Summary KPI Boxes -->
    <div class="row g-2 mb-3">
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Cash Discount (CD)</small>
          <span class="fs-6 fw-bold font-mono text-danger">₹{{ number_format($totCd, 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Goods Returns</small>
          <span class="fs-6 fw-bold font-mono text-danger">₹{{ number_format($totReturn, 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Total Refunds</small>
          <span class="fs-6 fw-bold font-mono text-danger">₹{{ number_format($totRefund, 2) }}</span>
        </div>
      </div>
      <div class="col-3">
        <div class="border rounded p-2 text-center bg-white">
          <small class="text-muted d-block fw-semibold">Net Deductions / Variance</small>
          <span class="fs-6 fw-bold font-mono text-danger">₹{{ number_format($totNetAdj, 2) }}</span>
        </div>
      </div>
    </div>

    <!-- Corrections Table -->
    <table class="table table-bordered table-sm align-middle text-center mb-4">
      <thead>
        <tr>
          <th>Corr ID</th>
          <th>Bill No.</th>
          <th class="text-end">Original (₹)</th>
          <th>Type</th>
          <th class="text-end">CD (₹)</th>
          <th class="text-end">Return (₹)</th>
          <th class="text-end">Refund (₹)</th>
          <th class="text-end">Net Adj (₹)</th>
          <th class="text-start">Reason / Remarks</th>
          <th>Approved By</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        @forelse($corrections as $c)
          <tr>
            <td class="font-mono fw-bold text-primary">{{ $c->corr_code }}</td>
            <td class="font-mono fw-bold">{{ $c->bill_no }}</td>
            <td class="text-end font-mono">₹{{ number_format($c->original_amount, 2) }}</td>
            <td><span class="badge bg-secondary">{{ $c->correction_type }}</span></td>
            <td class="text-end font-mono">{{ $c->cd_amount > 0 ? ('₹' . number_format($c->cd_amount, 2)) : '—' }}</td>
            <td class="text-end font-mono">{{ $c->goods_return_amount > 0 ? ('₹' . number_format($c->goods_return_amount, 2)) : '—' }}</td>
            <td class="text-end font-mono">{{ $c->refund_amount > 0 ? ('₹' . number_format($c->refund_amount, 2)) : '—' }}</td>
            <td class="text-end font-mono fw-bold text-danger">{{ $c->net_adjustment < 0 ? ('-₹' . number_format(abs($c->net_adjustment), 2)) : ('₹' . number_format($c->net_adjustment, 2)) }}</td>
            <td class="text-start small">{{ $c->reason }}</td>
            <td class="small">{{ $c->approved_by }}</td>
            <td class="font-mono small">{{ $c->created_at ? $c->created_at->format('d/m/Y H:i') : '' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="11" class="py-3 text-muted">No correction or return records registered.</td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="table-dark">
        <tr>
          <th colspan="4" class="text-start ps-3">TOTAL ({{ count($corrections) }} ENTRIES):</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($totCd, 2) }}</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($totReturn, 2) }}</th>
          <th class="text-end font-mono text-danger">-₹{{ number_format($totRefund, 2) }}</th>
          <th class="text-end font-mono text-warning">-₹{{ number_format(abs($totNetAdj), 2) }}</th>
          <th colspan="3"></th>
        </tr>
      </tfoot>
    </table>

    <div class="row g-4 mt-4">
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Operator / Store</div><small class="text-muted">Recorded By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Accounts Officer</div><small class="text-muted">Audited By</small></div></div>
      <div class="col-4"><div class="signature-box"><div class="fw-bold">Manager</div><small class="text-muted">Approved Signatory</small></div></div>
    </div>
  </div>

</body>
</html>
