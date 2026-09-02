@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Workflow Progress Stepper -->
<div class="workflow-stepper mb-3">
  <div class="step-item {{ ($metrics['activePsoCount'] ?? 0) > 0 ? 'completed' : 'active' }}">
    <div class="step-circle">{!! ($metrics['activePsoCount'] ?? 0) > 0 ? '<i class="bi bi-check"></i>' : '1' !!}</div>
    <div class="step-label">1. Configure PSO</div>
  </div>
  <div class="step-divider {{ ($metrics['activePsoCount'] ?? 0) > 0 ? 'filled' : '' }}"></div>
  <div class="step-item {{ ($metrics['totalBillsCount'] ?? 0) > 0 ? 'completed' : (($metrics['activePsoCount'] ?? 0) > 0 ? 'active' : '') }}">
    <div class="step-circle">{!! ($metrics['totalBillsCount'] ?? 0) > 0 ? '<i class="bi bi-check"></i>' : '2' !!}</div>
    <div class="step-label">2. Tally Import</div>
  </div>
  <div class="step-divider {{ ($metrics['totalBillsCount'] ?? 0) > 0 ? 'filled' : '' }}"></div>
  <div class="step-item {{ ($metrics['hasBills'] && $metrics['matchedCount'] === $metrics['totalBillsCount']) ? 'completed' : (($metrics['totalBillsCount'] ?? 0) > 0 ? 'active' : '') }}">
    <div class="step-circle">{!! ($metrics['hasBills'] && $metrics['matchedCount'] === $metrics['totalBillsCount']) ? '<i class="bi bi-check"></i>' : '3' !!}</div>
    <div class="step-label">3. Bill Verify</div>
  </div>
  <div class="step-divider {{ $metrics['isReconciled'] ? 'filled' : '' }}"></div>
  <div class="step-item {{ $metrics['isReconciled'] ? 'completed' : '' }}">
    <div class="step-circle">{!! $metrics['isReconciled'] ? '<i class="bi bi-check"></i>' : '4' !!}</div>
    <div class="step-label">4. Reconcile</div>
  </div>
  <div class="step-divider {{ $metrics['isSealed'] ? 'filled' : '' }}"></div>
  <div class="step-item {{ $metrics['isSealed'] ? 'completed' : '' }}">
    <div class="step-circle">{!! $metrics['isSealed'] ? '<i class="bi bi-lock-fill"></i>' : '5' !!}</div>
    <div class="step-label">5. Approval & Seal</div>
  </div>
</div>

<!-- Alert Notification Bar -->
@if(!$metrics['hasBills'])
  <div class="alert-erp alert-erp-info mb-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
      <div>
        <span class="fw-semibold text-dark">Ready for Daily Reconciliation:</span>
        <span class="text-secondary ms-1">No bills recorded yet for <strong>{{ date('d/m/Y', strtotime($businessDate)) }}</strong>. Configure PSO series and import Tally DayBook to start.</span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.pso.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Configure PSO</a>
      <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-primary"><i class="bi bi-cloud-arrow-up me-1"></i>Import DayBook</a>
    </div>
  </div>
@elseif(!$metrics['isReconciled'])
  <div class="alert-erp alert-erp-danger mb-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
      <div>
        <span class="fw-semibold text-danger">Reconciliation Discrepancy:</span>
        <span class="text-secondary ms-1">Tally Total is <strong>₹{{ number_format($metrics['tallyTotal'], 2) }}</strong> vs PSO Collection <strong>₹{{ number_format($metrics['psoCollection'], 2) }}</strong> (Diff: <strong class="text-danger">₹{{ number_format($metrics['difference'], 2) }}</strong>).</span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.verification.index') }}" class="btn btn-sm btn-danger"><i class="bi bi-search me-1"></i>Investigate Bills</a>
      <a href="{{ route('admin.reconciliation.index') }}" class="btn btn-sm btn-outline-secondary">Reconciliation</a>
    </div>
  </div>
@else
  <div class="alert-erp alert-erp-success mb-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-check-circle-fill fs-5 text-success"></i>
      <div>
        <span class="fw-semibold text-success">Reconciliation Passed (Zero Variance):</span>
        <span class="text-secondary ms-1">Tally and PSO physical bundles match 100% at <strong>₹{{ number_format($metrics['tallyTotal'], 2) }}</strong>.</span>
      </div>
    </div>
    <a href="{{ route('admin.approval.index') }}" class="btn btn-sm btn-success">Proceed to Sealing <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
@endif

<!-- 8 KPI Cards (4 per row) -->
<div class="row g-2.5 mb-3">
  <!-- 1. Today's PSOs -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Today's PSOs</div>
          <div class="kpi-value font-mono">{{ $metrics['activePsoCount'] ?? 0 }} Active</div>
          <div class="kpi-subtext">{{ ($metrics['totalPsoCount'] ?? 0) > 0 ? ($metrics['totalPsoCount'] . ' series configured') : 'No active PSO series' }}</div>
        </div>
        <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-grid-fill"></i></div>
      </div>
    </div>
  </div>

  <!-- 2. Total Tally Amount -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Total Tally Amount</div>
          <div class="kpi-value font-mono text-primary">₹{{ number_format($metrics['tallyTotal'], 2) }}</div>
          <div class="kpi-subtext">{{ $metrics['totalBillsCount'] }} Bills from DayBook</div>
        </div>
        <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-file-earmark-spreadsheet"></i></div>
      </div>
    </div>
  </div>

  <!-- 3. Total PSO Collection -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Total PSO Collection</div>
          <div class="kpi-value font-mono text-success">₹{{ number_format($metrics['psoCollection'], 2) }}</div>
          <div class="kpi-subtext">Sum of active counters</div>
        </div>
        <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
      </div>
    </div>
  </div>

  <!-- 4. Difference Amount -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
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

  <!-- 5. Matched Bills -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
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

  <!-- 6. Missing Bills -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
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

  <!-- 7. Credit Pending -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
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

  <!-- 8. Approval Status -->
  <div class="col-xl-3 col-sm-6">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-title">Approval Status</div>
          <div class="kpi-value font-mono pt-0.5">
            @if($metrics['isSealed'])
              <span class="badge badge-matched">SEALED</span>
            @elseif(!$metrics['hasBills'])
              <span class="badge bg-secondary-subtle text-secondary border">NO BILLS</span>
            @elseif($metrics['isReconciled'])
              <span class="badge badge-countersale">READY</span>
            @else
              <span class="badge badge-missing">BLOCKED</span>
            @endif
          </div>
          <div class="kpi-subtext">{{ $metrics['isSealed'] ? 'Immutable digital seal' : (!$metrics['hasBills'] ? 'Awaiting DayBook import' : ($metrics['isReconciled'] ? 'Ready for approval' : 'Variance detected')) }}</div>
        </div>
        <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-shield-lock"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Middle Row: Daily PSO Summary & Payment Breakdown -->
<div class="row g-3 mb-3">
  <!-- Daily PSO Summary Table -->
  <div class="col-lg-8">
    <div class="erp-table-container h-100 d-flex flex-column">
      <div class="erp-card-header">
        <div>
          <span class="fw-semibold text-dark">Daily PSO Summary</span>
          <span class="text-muted ms-2" style="font-size: 0.72rem;">Counter breakdown for {{ date('d/m/Y', strtotime($businessDate)) }}</span>
        </div>
        <a href="{{ route('admin.summary.index') }}" class="btn btn-sm btn-outline-secondary">Full Details <i class="bi bi-chevron-right ms-1"></i></a>
      </div>
      <div class="table-responsive flex-grow-1">
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
            @forelse($psoRows as $row)
              <tr>
                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $row['pso']->code }}</span></td>
                <td><span class="fw-medium font-mono">{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }} - {{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->end_no) }}</span></td>
                <td>{{ $row['billsCount'] }}</td>
                <td class="font-mono">₹{{ number_format($row['gross'], 2) }}</td>
                <td class="font-mono text-success fw-medium">₹{{ number_format($row['net'], 2) }}</td>
                <td class="text-muted">{{ $row['pso']->operator_name }}</td>
                <td><span class="{{ $row['statusClass'] }}">{{ $row['status'] }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="bi bi-diagram-3 fs-4 d-block mb-1 text-secondary"></i>
                  No active PSO counter series configured.
                  <a href="{{ route('admin.pso.index') }}" class="btn btn-sm btn-primary ms-2">Configure PSO</a>
                </td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" class="text-uppercase" style="letter-spacing: 0.04em;">Total PSO Aggregate</td>
              <td>{{ $metrics['totalBillsCount'] }}</td>
              <td class="font-mono">₹{{ number_format($metrics['tallyTotal'], 2) }}</td>
              <td class="font-mono text-success">₹{{ number_format($metrics['psoCollection'], 2) }}</td>
              <td>-</td>
              <td><span class="badge {{ $metrics['isReconciled'] ? 'badge-matched' : 'badge-pending' }}">{{ $metrics['isReconciled'] ? 'Reconciled' : 'In Progress' }}</span></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Payment Breakdown Summary -->
  <div class="col-lg-4">
    <div class="erp-table-container h-100 p-3 d-flex flex-column">
      <div class="d-flex justify-content-between align-items-center mb-2.5">
        <span class="fw-semibold text-dark">Payment-Type Summary</span>
        <span class="text-muted" style="font-size: 0.72rem;">5 Settlement Modes</span>
      </div>
      <div class="d-flex flex-column gap-2 flex-grow-1 justify-content-between">
        <!-- 1. Cash Received -->
        <div class="payment-list-item">
          <div class="d-flex align-items-center gap-2">
            <div class="payment-item-icon bg-success-subtle text-success">
              <i class="bi bi-cash"></i>
            </div>
            <div>
              <div class="fw-medium text-dark" style="font-size: 0.79rem;">Cash Received</div>
              <div class="text-muted" style="font-size: 0.68rem;">Physical cash in counter</div>
            </div>
          </div>
          <span class="fw-semibold font-mono text-success" style="font-size: 0.82rem;">₹{{ number_format($metrics['totCash'], 2) }}</span>
        </div>

        <!-- 2. Paytm / UPI -->
        <div class="payment-list-item">
          <div class="d-flex align-items-center gap-2">
            <div class="payment-item-icon bg-info-subtle text-info">
              <i class="bi bi-qr-code"></i>
            </div>
            <div>
              <div class="fw-medium text-dark" style="font-size: 0.79rem;">Paytm / UPI</div>
              <div class="text-muted" style="font-size: 0.68rem;">Digital soundbox & QR</div>
            </div>
          </div>
          <span class="fw-semibold font-mono text-info" style="font-size: 0.82rem;">₹{{ number_format($metrics['totPaytm'], 2) }}</span>
        </div>

        <!-- 3. Cheque / Bank -->
        <div class="payment-list-item">
          <div class="d-flex align-items-center gap-2">
            <div class="payment-item-icon bg-primary-subtle text-primary">
              <i class="bi bi-bank"></i>
            </div>
            <div>
              <div class="fw-medium text-dark" style="font-size: 0.79rem;">Cheque / Bank</div>
              <div class="text-muted" style="font-size: 0.68rem;">Bank deposit clearing</div>
            </div>
          </div>
          <span class="fw-semibold font-mono text-primary" style="font-size: 0.82rem;">₹{{ number_format($metrics['totCheck'], 2) }}</span>
        </div>

        <!-- 4. Credit (Salesman) -->
        <div class="payment-list-item">
          <div class="d-flex align-items-center gap-2">
            <div class="payment-item-icon bg-warning-subtle text-warning">
              <i class="bi bi-person-badge"></i>
            </div>
            <div>
              <div class="fw-medium text-dark" style="font-size: 0.79rem;">Credit (Salesman)</div>
              <div class="text-muted" style="font-size: 0.68rem;">Salesman field recovery</div>
            </div>
          </div>
          <span class="fw-semibold font-mono text-warning" style="font-size: 0.82rem;">₹{{ number_format($metrics['totCredit'], 2) }}</span>
        </div>

        <!-- 5. Cancelled Bills -->
        <div class="payment-list-item">
          <div class="d-flex align-items-center gap-2">
            <div class="payment-item-icon bg-secondary-subtle text-secondary">
              <i class="bi bi-x-circle"></i>
            </div>
            <div>
              <div class="fw-medium text-dark" style="font-size: 0.79rem;">Cancelled Bills</div>
              <div class="text-muted" style="font-size: 0.68rem;">Void transactions</div>
            </div>
          </div>
          <span class="fw-medium font-mono text-muted" style="font-size: 0.82rem;">₹{{ number_format($metrics['totCancelled'], 2) }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row: Recent Imported Files & Retention Radar -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="erp-table-container">
      <div class="erp-card-header">
        <span class="fw-semibold text-dark">Recent Imported Tally Files</span>
        <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-outline-secondary">Upload New</a>
      </div>
      <div class="table-responsive">
        <table class="table erp-table mb-0 align-middle">
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
            @forelse($recentImports as $imp)
              <tr>
                <td><i class="bi bi-file-earmark-spreadsheet text-success me-1"></i><span class="fw-medium text-dark">{{ $imp->filename }}</span></td>
                <td class="text-muted">{{ $imp->created_at ? $imp->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}</td>
                <td>{{ $imp->total_records }}</td>
                <td class="font-mono">₹{{ number_format($imp->total_amount, 2) }}</td>
                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $imp->status }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-3">
                  No Tally files imported yet. <a href="{{ route('admin.import.index') }}">Import Tally Excel</a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="erp-table-container">
      <div class="erp-card-header">
        <span class="fw-semibold text-dark">Pending Approvals & 7-Day Retention</span>
        <a href="{{ route('admin.retention.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table erp-table mb-0 align-middle">
          <thead>
            <tr>
              <th>PSO</th>
              <th>Date</th>
              <th>Retention Window</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($retentionList as $ret)
              <tr>
                <td><strong>{{ $ret->pso_code }}</strong></td>
                <td class="text-muted">{{ $ret->created_date_formatted }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="font-mono text-secondary" style="font-size: 0.74rem;">{{ $ret->days_remaining }}d left</span>
                    <div class="retention-meter flex-grow-1" style="width: 70px;">
                      <div class="retention-fill bg-warning" style="width: {{ ($ret->days_remaining / 7) * 100 }}%;"></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge {{ $ret->badge_class }}">{{ $ret->status }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-3">
                  No pending unapproved PSOs in retention window.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
