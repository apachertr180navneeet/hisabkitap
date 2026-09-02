@extends('layouts.app')

@section('title', 'Reports & Statutory Exports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Reports & Statutory Exports</h4>
    <p class="text-muted mb-0">Generate, print, and export comprehensive daily reconciliation sheets and audit ledgers.</p>
  </div>
</div>

<div class="row g-4">
  <!-- Report Selector -->
  <div class="col-lg-4">
    <div class="card border p-3 bg-white shadow-sm">
      <h6 class="fw-bold mb-3">Report Catalog</h6>
      <div class="list-group list-group-flush">
        <a href="{{ route('reports.index', ['type' => 'daily_pso']) }}" class="list-group-item list-group-item-action {{ $reportType === 'daily_pso' ? 'active' : '' }}">
          <div class="fw-bold">1. Daily PSO Report</div>
          <small>Full summary of all 3 PSO counters & payments</small>
        </a>
        <a href="{{ route('reports.index', ['type' => 'recon_sheet']) }}" class="list-group-item list-group-item-action {{ $reportType === 'recon_sheet' ? 'active' : '' }}">
          <div class="fw-bold">2. Tally vs PSO Reconciliation</div>
          <small>Detailed variance comparator with difference notes</small>
        </a>
        <a href="{{ route('reports.index', ['type' => 'credit_sheet']) }}" class="list-group-item list-group-item-action {{ $reportType === 'credit_sheet' ? 'active' : '' }}">
          <div class="fw-bold">3. Credit Collection Register</div>
          <small>Salesman-wise credit recovery list</small>
        </a>
        <a href="{{ route('reports.index', ['type' => 'missing_bills']) }}" class="list-group-item list-group-item-action {{ $reportType === 'missing_bills' ? 'active' : '' }}">
          <div class="fw-bold">4. Missing Bills & Investigations</div>
          <small>Log of missing serials and remarks</small>
        </a>
        <a href="{{ route('reports.index', ['type' => 'corrections_log']) }}" class="list-group-item list-group-item-action {{ $reportType === 'corrections_log' ? 'active' : '' }}">
          <div class="fw-bold">5. Cash Discounts & Goods Returns</div>
          <small>Audit trail of all spot adjustments</small>
        </a>
        <a href="{{ route('reports.index', ['type' => 'audit_history']) }}" class="list-group-item list-group-item-action {{ $reportType === 'audit_history' ? 'active' : '' }}">
          <div class="fw-bold">6. System Audit History</div>
          <small>Timestamped operator actions & system events</small>
        </a>
      </div>
    </div>
  </div>

  <!-- Report Preview Pane -->
  <div class="col-lg-8">
    <div class="card border p-4 bg-white shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">{{ $reportData['title'] ?? 'Report Preview' }}</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
          </button>
          <a href="{{ route('reports.export', ['type' => $reportType]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export CSV / Excel
          </a>
        </div>
      </div>
      <hr class="my-2">
      <div class="p-3 bg-light rounded font-mono" style="min-height: 400px; font-size: 0.85rem; overflow-x: auto;">
        @if($reportType === 'daily_pso')
          <div class="p-2 border-bottom mb-2 fw-bold">HISABKITAP ERP - DAILY PSO SUMMARY REPORT (Date: {{ date('d/m/Y', strtotime($businessDate)) }})</div>
          <p>Total Tally Sales: ₹{{ number_format($metrics['tallyTotal'], 2) }} | Total Verified PSO: ₹{{ number_format($metrics['psoCollection'], 2) }} | Variance: ₹{{ number_format($metrics['difference'], 2) }}</p>
          <table class="table table-sm table-bordered bg-white">
            <thead>
              <tr><th>Bill No</th><th>PSO</th><th>Customer</th><th>Amount</th><th>Pay Type</th><th>CD</th><th>Net</th><th>Status</th></tr>
            </thead>
            <tbody>
              @foreach($reportData['bills'] as $b)
                <tr>
                  <td>{{ $b->bill_no }}</td>
                  <td>{{ $b->pso_code }}</td>
                  <td>{{ $b->customer_name }}</td>
                  <td>₹{{ number_format($b->amount) }}</td>
                  <td>{{ $b->payment_type }}</td>
                  <td>₹{{ number_format($b->cd_amount) }}</td>
                  <td>₹{{ number_format($b->net_amount) }}</td>
                  <td>{{ $b->status }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @elseif($reportType === 'credit_sheet')
          <div class="p-2 border-bottom mb-2 fw-bold">SALESMAN CREDIT COLLECTION RECOVERY SHEET</div>
          <table class="table table-sm table-bordered bg-white">
            <thead>
              <tr><th>Bill No</th><th>Customer</th><th>Salesman</th><th>Bill Date</th><th>Amount</th><th>Paid</th><th>Outstanding</th><th>Status</th></tr>
            </thead>
            <tbody>
              @foreach($reportData['credits'] as $c)
                <tr>
                  <td>{{ $c->bill_no }}</td>
                  <td>{{ $c->customer_name }}</td>
                  <td>{{ $c->salesman_name }}</td>
                  <td>{{ $c->bill_date ? $c->bill_date->format('d/m/Y') : '' }}</td>
                  <td>₹{{ number_format($c->bill_amount) }}</td>
                  <td>₹{{ number_format($c->paid_amount) }}</td>
                  <td class="text-danger fw-bold">₹{{ number_format($c->outstanding_amount) }}</td>
                  <td>{{ $c->collection_status }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @elseif($reportType === 'recon_sheet')
          <div class="p-2 border-bottom mb-2 fw-bold">TALLY vs PSO MASTER RECONCILIATION SHEET (Date: {{ date('d/m/Y', strtotime($businessDate)) }})</div>
          <p>Total Tally: ₹{{ number_format($metrics['tallyTotal'], 2) }} | PSO Collection: ₹{{ number_format($metrics['psoCollection'], 2) }} | Variance: <strong>₹{{ number_format($metrics['difference'], 2) }}</strong></p>
          <table class="table table-sm table-bordered bg-white">
            <thead>
              <tr><th>Bill No</th><th>PSO</th><th>Customer</th><th>Amount</th><th>Pay Type</th><th>CD</th><th>Net</th><th>Status</th></tr>
            </thead>
            <tbody>
              @foreach($reportData['bills'] as $b)
                <tr>
                  <td>{{ $b->bill_no }}</td>
                  <td>{{ $b->pso_code }}</td>
                  <td>{{ $b->customer_name }}</td>
                  <td>₹{{ number_format($b->amount) }}</td>
                  <td>{{ $b->payment_type }}</td>
                  <td>₹{{ number_format($b->cd_amount) }}</td>
                  <td>₹{{ number_format($b->net_amount) }}</td>
                  <td>{{ $b->status }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @elseif($reportType === 'missing_bills')
          <div class="p-2 border-bottom mb-2 fw-bold">MISSING & DISCREPANCY BILL INVESTIGATION LOG (Date: {{ date('d/m/Y', strtotime($businessDate)) }})</div>
          <table class="table table-sm table-bordered bg-white">
            <thead>
              <tr><th>Bill No</th><th>PSO</th><th>Customer</th><th>Amount</th><th>Pay Type</th><th>Status</th><th>Remark</th></tr>
            </thead>
            <tbody>
              @forelse($reportData['bills'] as $b)
                <tr>
                  <td>{{ $b->bill_no }}</td>
                  <td>{{ $b->pso_code }}</td>
                  <td>{{ $b->customer_name }}</td>
                  <td>₹{{ number_format($b->amount) }}</td>
                  <td>{{ $b->payment_type }}</td>
                  <td><span class="badge bg-danger">{{ $b->status }}</span></td>
                  <td>{{ $b->remark }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted p-3">No missing or discrepant bills.</td></tr>
              @endforelse
            </tbody>
          </table>
        @elseif($reportType === 'corrections_log')
          <div class="p-2 border-bottom mb-2 fw-bold">CASH DISCOUNTS, GOODS RETURNS & SPOT ADJUSTMENTS REGISTER</div>
          <table class="table table-sm table-bordered bg-white">
            <thead>
              <tr><th>Code</th><th>Bill No</th><th>Original</th><th>Type</th><th>CD</th><th>Return</th><th>Refund</th><th>Net Adj</th><th>Approved By</th></tr>
            </thead>
            <tbody>
              @forelse($reportData['corrections'] as $c)
                <tr>
                  <td>{{ $c->corr_code }}</td>
                  <td>{{ $c->bill_no }}</td>
                  <td>₹{{ number_format($c->original_amount) }}</td>
                  <td>{{ $c->correction_type }}</td>
                  <td>₹{{ number_format($c->cd_amount) }}</td>
                  <td>₹{{ number_format($c->goods_return_amount) }}</td>
                  <td>₹{{ number_format($c->refund_amount) }}</td>
                  <td>₹{{ number_format($c->net_adjustment) }}</td>
                  <td>{{ $c->approved_by }}</td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted p-3">No corrections recorded.</td></tr>
              @endforelse
            </tbody>
          </table>
        @elseif($reportType === 'audit_history')
          <div class="p-2 border-bottom mb-2 fw-bold">STATUTORY REPORT PREVIEW ({{ $reportType }})</div>
          <p>Generated for Business Date: {{ date('d/m/Y', strtotime($businessDate)) }} | Status: Active</p>
          <div class="p-3 bg-white border rounded">
            Report dataset loaded successfully. Use the Export button above to download full CSV dataset.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
