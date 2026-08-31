@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Workflow Progress Stepper -->
<div class="workflow-stepper mb-4">
  <div class="step-item completed">
    <div class="step-circle"><i class="bi bi-check-lg"></i></div>
    <div class="step-label">1. Configure PSO</div>
  </div>
  <div class="step-divider filled"></div>
  <div class="step-item completed">
    <div class="step-circle"><i class="bi bi-check-lg"></i></div>
    <div class="step-label">2. Tally Import</div>
  </div>
  <div class="step-divider filled"></div>
  <div class="step-item {{ $metrics['matchedCount'] === $metrics['totalBillsCount'] ? 'completed' : 'active' }}">
    <div class="step-circle">{!! $metrics['matchedCount'] === $metrics['totalBillsCount'] ? '<i class="bi bi-check-lg"></i>' : '3' !!}</div>
    <div class="step-label">3. Bill Verify</div>
  </div>
  <div class="step-divider {{ $metrics['isReconciled'] ? 'filled' : '' }}"></div>
  <div class="step-item {{ $metrics['isReconciled'] ? 'completed' : '' }}">
    <div class="step-circle">{!! $metrics['isReconciled'] ? '<i class="bi bi-check-lg"></i>' : '4' !!}</div>
    <div class="step-label">4. Reconcile</div>
  </div>
  <div class="step-divider {{ $metrics['isSealed'] ? 'filled' : '' }}"></div>
  <div class="step-item {{ $metrics['isSealed'] ? 'completed' : '' }}">
    <div class="step-circle">{!! $metrics['isSealed'] ? '<i class="bi bi-lock-fill"></i>' : '5' !!}</div>
    <div class="step-label">5. Approval & Seal</div>
  </div>
</div>

<!-- Alert Notification Bar -->
@if(!$metrics['isReconciled'])
  <div class="alert alert-danger d-flex align-items-center justify-content-between mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2.5">
      <i class="bi bi-exclamation-octagon-fill fs-4"></i>
      <div>
        <strong>Reconciliation Discrepancy Detected!</strong>
        <div style="font-size: 0.82rem;">Tally Total is <strong>₹{{ number_format($metrics['tallyTotal'], 2) }}</strong> while verified PSO Collection is <strong>₹{{ number_format($metrics['psoCollection'], 2) }}</strong> (Difference: <span class="text-danger fw-bold">₹{{ number_format($metrics['difference'], 2) }}</span>). Approval is currently <strong>BLOCKED</strong>.</div>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('verification.index') }}" class="btn btn-sm btn-danger">Investigate Missing Bill</a>
      <a href="{{ route('reconciliation.index') }}" class="btn btn-sm btn-outline-dark">Recon Screen</a>
    </div>
  </div>
@else
  <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2.5">
      <i class="bi bi-shield-check fs-4 text-success"></i>
      <div>
        <strong>Reconciliation Passed (Zero Variance)</strong>
        <div style="font-size: 0.82rem;">Tally and PSO physical bundles match 100% at <strong>₹{{ number_format($metrics['tallyTotal'], 2) }}</strong>. Ready for cryptographic signing and seal.</div>
      </div>
    </div>
    <a href="{{ route('approval.index') }}" class="btn btn-sm btn-success">Proceed to Sealing <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
@endif

<!-- 8 KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="kpi-card kpi-primary">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Today's PSOs</div>
          <div class="kpi-value font-mono">3 Active</div>
          <div class="kpi-subtext">PSO 1, PSO 2 (+ITC), PSO 3</div>
        </div>
        <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-collection-fill"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card kpi-info">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Total Tally Amount</div>
          <div class="kpi-value font-mono text-primary">₹{{ number_format($metrics['tallyTotal'], 2) }}</div>
          <div class="kpi-subtext">{{ $metrics['totalBillsCount'] }} Bills Imported from DayBook</div>
        </div>
        <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-file-earmark-spreadsheet"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card kpi-success">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Total PSO Collection</div>
          <div class="kpi-value font-mono text-success">₹{{ number_format($metrics['psoCollection'], 2) }}</div>
          <div class="kpi-subtext">Sum of PSO 1 + PSO 2 + PSO 3</div>
        </div>
        <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card {{ $metrics['difference'] == 0 ? 'kpi-success' : 'kpi-danger' }}">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Difference Amount</div>
          <div class="kpi-value font-mono {{ $metrics['difference'] == 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format($metrics['difference'], 2) }}</div>
          <div class="kpi-subtext">{{ $metrics['difference'] == 0 ? 'Zero Discrepancy' : 'Discrepancy Unresolved' }}</div>
        </div>
        <div class="kpi-icon {{ $metrics['difference'] == 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"><i class="bi bi-calculator"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card kpi-success">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Matched Bills</div>
          <div class="kpi-value font-mono text-success">{{ $metrics['matchedCount'] }} / {{ $metrics['totalBillsCount'] }}</div>
          <div class="kpi-subtext">Physical slips verified</div>
        </div>
        <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card {{ $metrics['missingCount'] > 0 ? 'kpi-danger' : 'kpi-success' }}">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Missing Bills</div>
          <div class="kpi-value font-mono {{ $metrics['missingCount'] > 0 ? 'text-danger' : 'text-success' }}">{{ $metrics['missingCount'] }} Bill{{ $metrics['missingCount'] === 1 ? '' : 's' }}</div>
          <div class="kpi-subtext">{{ $metrics['missingCount'] > 0 ? 'Pending verification' : 'All slips accounted' }}</div>
        </div>
        <div class="kpi-icon {{ $metrics['missingCount'] > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}"><i class="bi bi-exclamation-triangle"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card kpi-warning">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Credit Pending</div>
          <div class="kpi-value font-mono text-warning">₹{{ number_format($metrics['creditPending'], 2) }}</div>
          <div class="kpi-subtext">Salesman recovery register</div>
        </div>
        <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-person-lines-fill"></i></div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card {{ $metrics['isSealed'] ? 'kpi-success' : ($metrics['isReconciled'] ? 'kpi-info' : 'kpi-danger') }}">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Approval Status</div>
          <div class="kpi-value font-mono">
            @if($metrics['isSealed'])
              <span class="badge badge-sealed">SEALED</span>
            @elseif($metrics['isReconciled'])
              <span class="badge bg-info text-white">READY</span>
            @else
              <span class="badge badge-blocked">BLOCKED</span>
            @endif
          </div>
          <div class="kpi-subtext">{{ $metrics['isSealed'] ? 'Read-only immutable lock' : ($metrics['isReconciled'] ? 'Awaiting Approver signoff' : 'Reconciliation required') }}</div>
        </div>
        <div class="kpi-icon bg-secondary-subtle text-dark"><i class="bi bi-shield-lock"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Middle Row: Daily PSO Summary & Payment Breakdown -->
<div class="row g-4 mb-4">
  <!-- Daily PSO Summary Table -->
  <div class="col-lg-8">
    <div class="erp-table-container h-100">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 fw-bold">Daily PSO Summary Table</h6>
          <span class="text-muted" style="font-size: 0.75rem;">Breakdown across configured counters for {{ $businessDate }}</span>
        </div>
        <a href="{{ route('summary.index') }}" class="btn btn-sm btn-outline-primary">Full Details <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="table erp-table align-middle">
          <thead>
            <tr>
              <th>PSO Code</th>
              <th>Series Range</th>
              <th>Bills</th>
              <th>Gross Sales</th>
              <th>Net Collection</th>
              <th>Operator</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($psoRows as $row)
              <tr>
                <td><span class="badge bg-primary">{{ $row['pso']->code }}</span></td>
                <td><strong>{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }} - {{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->end_no) }}</strong></td>
                <td>{{ $row['billsCount'] }}</td>
                <td class="font-mono">₹{{ number_format($row['gross'], 2) }}</td>
                <td class="font-mono text-success">₹{{ number_format($row['net'], 2) }}</td>
                <td>{{ $row['pso']->operator_name }}</td>
                <td><span class="{{ $row['statusClass'] }}">{{ $row['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2">TOTAL PSO AGGREGATE</td>
              <td>{{ $metrics['totalBillsCount'] }}</td>
              <td class="font-mono">₹{{ number_format($metrics['tallyTotal'], 2) }}</td>
              <td class="font-mono text-success">₹{{ number_format($metrics['psoCollection'], 2) }}</td>
              <td>-</td>
              <td><span class="badge {{ $metrics['isReconciled'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $metrics['isReconciled'] ? 'Reconciled' : 'Verification In Progress' }}</span></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Payment Breakdown Summary -->
  <div class="col-lg-4">
    <div class="erp-table-container h-100 p-3">
      <h6 class="fw-bold mb-3">Payment-Type Summary</h6>
      <div class="d-flex flex-column gap-2.5">
        <div class="d-flex justify-content-between align-items-center p-2.5 bg-light rounded border-start border-3 border-success">
          <div>
            <div class="fw-semibold text-dark"><i class="bi bi-cash me-1 text-success"></i> Cash Received</div>
            <small class="text-muted">Direct counter physical cash</small>
          </div>
          <span class="fw-bold font-mono text-success">₹{{ number_format($metrics['totCash'], 2) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2.5 bg-light rounded border-start border-3 border-info">
          <div>
            <div class="fw-semibold text-dark"><i class="bi bi-qr-code me-1 text-info"></i> Paytm / UPI</div>
            <small class="text-muted">Digital soundbox & QR settled</small>
          </div>
          <span class="fw-bold font-mono text-info">₹{{ number_format($metrics['totPaytm'], 2) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2.5 bg-light rounded border-start border-3 border-primary">
          <div>
            <div class="fw-semibold text-dark"><i class="bi bi-bank me-1 text-primary"></i> Cheque / Bank</div>
            <small class="text-muted">Bank deposit clearing</small>
          </div>
          <span class="fw-bold font-mono text-primary">₹{{ number_format($metrics['totCheck'], 2) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2.5 bg-light rounded border-start border-3 border-warning">
          <div>
            <div class="fw-semibold text-dark"><i class="bi bi-person-badge me-1 text-warning"></i> Credit (Salesman)</div>
            <small class="text-muted">Salesman field recovery</small>
          </div>
          <span class="fw-bold font-mono text-warning">₹{{ number_format($metrics['totCredit'], 2) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2.5 bg-light rounded border-start border-3 border-secondary">
          <div>
            <div class="fw-semibold text-dark"><i class="bi bi-x-circle me-1 text-secondary"></i> Cancelled Bills</div>
            <small class="text-muted">Void transactions / zero collection</small>
          </div>
          <span class="fw-bold font-mono text-muted">₹{{ number_format($metrics['totCancelled'], 2) }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row: Recent Imported Files & Retention Radar -->
<div class="row g-4">
  <div class="col-lg-6">
    <div class="erp-table-container p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Recent Imported Tally Files</h6>
        <a href="{{ route('import.index') }}" class="btn btn-sm btn-outline-secondary">Upload New</a>
      </div>
      <div class="table-responsive">
        <table class="table erp-table mb-0">
          <thead>
            <tr>
              <th>Filename</th>
              <th>Import Time</th>
              <th>Records</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentImports as $imp)
              <tr>
                <td><i class="bi bi-file-earmark-spreadsheet text-success me-1"></i><strong>{{ $imp->filename }}</strong></td>
                <td>{{ $imp->created_at ? $imp->created_at->format('d-M-Y H:i') : '14-Aug-2026 18:45' }}</td>
                <td>{{ $imp->total_records }}</td>
                <td class="font-mono">₹{{ number_format($imp->total_amount, 2) }}</td>
                <td><span class="badge bg-primary">{{ $imp->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="erp-table-container p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Pending Approvals & 7-Day Retention</h6>
        <a href="{{ route('retention.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table erp-table mb-0">
          <thead>
            <tr>
              <th>PSO</th>
              <th>Date</th>
              <th>Retention Window</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($retentionList as $ret)
              <tr>
                <td><strong>{{ $ret->pso_code }}</strong></td>
                <td>{{ $ret->created_date_formatted }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="small fw-semibold">{{ $ret->days_remaining }} Days Left</span>
                    <div class="retention-meter flex-grow-1" style="width: 80px;">
                      <div class="retention-fill bg-warning" style="width: {{ ($ret->days_remaining / 7) * 100 }}%;"></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge {{ $ret->badge_class }}">{{ $ret->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
