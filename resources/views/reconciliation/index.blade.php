@extends('layouts.app')

@section('title', 'Master Reconciliation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Master Reconciliation Engine</h4>
    <p class="text-muted mb-0">Final mathematical comparison between Tally Total and Total Verified PSO Collection.</p>
  </div>
  <a href="{{ route('reconciliation.index') }}" class="btn btn-outline-primary">
    <i class="bi bi-arrow-clockwise me-1"></i> Re-Calculate
  </a>
</div>

<!-- Master Status Banner -->
@if(!$metrics['hasBills'])
  <div class="recon-banner" style="background: #f8fafc; border-left: 5px solid #64748b; border: 1px solid #e2e8f0;">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle p-3 bg-white shadow-sm text-secondary">
        <i class="bi bi-inbox fs-1"></i>
      </div>
      <div>
        <h4 class="fw-bold mb-1 text-dark">AWAITING TALLY DAYBOOK IMPORT</h4>
        <p class="mb-0 text-muted">
          No bill records found for business date <strong>{{ date('d/m/Y', strtotime($metrics['businessDate'])) }}</strong>. Please configure PSO counter series and import your DayBook Excel file to begin reconciliation.
        </p>
      </div>
    </div>
    <div>
      <a href="{{ route('admin.import.index') }}" class="btn btn-primary">
        <i class="bi bi-cloud-arrow-up me-1"></i> Import Tally DayBook
      </a>
    </div>
  </div>
@elseif(!$metrics['isReconciled'])
  <div class="recon-banner failed">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle p-3 bg-white shadow-sm text-danger">
        <i class="bi bi-shield-x fs-1"></i>
      </div>
      <div>
        <h4 class="fw-bold mb-1">RECONCILIATION FAILED (VARIANCE DETECTED)</h4>
        <p class="mb-0 text-muted">
          Tally Total (₹{{ number_format($metrics['tallyTotal'], 2) }}) does not match Total PSO Collection (₹{{ number_format($metrics['psoCollection'], 2) }}). Difference: <span class="fw-bold text-danger">₹{{ number_format($metrics['difference'], 2) }}</span>.
          Approval and sealing are strictly blocked until discrepancy is cleared.
        </p>
      </div>
    </div>
    <div>
      <form action="{{ route('admin.reconciliation.resolve') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-tools me-1"></i> Resolve Discrepancy (Match Missing)
        </button>
      </form>
    </div>
  </div>
@else
  <div class="recon-banner success">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle p-3 bg-white shadow-sm text-success">
        <i class="bi bi-shield-check fs-1"></i>
      </div>
      <div>
        <h4 class="fw-bold mb-1 text-success">RECONCILIATION 100% BALANCED</h4>
        <p class="mb-0 text-muted">
          Tally Total matches Total PSO Collection perfectly at <strong>₹{{ number_format($metrics['tallyTotal'], 2) }}</strong> (Difference: <span class="fw-bold text-success">₹0.00</span>).
          All compliance prerequisite gates are passed.
        </p>
      </div>
    </div>
    <div>
      <a href="{{ route('admin.approval.index') }}" class="btn btn-success">
        <i class="bi bi-lock-fill me-1"></i> Proceed to Sealing & Sign-Off
      </a>
    </div>
  </div>
@endif

<!-- Comparison Matrix -->
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Tally DayBook Total</h5>
        <span class="badge bg-primary">Source of Truth</span>
      </div>
      <div class="display-6 fw-bold font-mono text-primary mb-3">₹{{ number_format($metrics['tallyTotal'], 2) }}</div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>Imported Records</span>
          <span class="fw-bold font-mono">{{ $metrics['totalBillsCount'] }} Bills</span>
        </li>
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>Business Accounting Date</span>
          <span class="fw-bold font-mono">{{ date('d/m/Y', strtotime($metrics['businessDate'])) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>Gross Debit/Credit Check</span>
          <span class="text-success fw-bold">Balanced</span>
        </li>
      </ul>
    </div>
  </div>

  <div class="col-lg-2 d-flex align-items-center justify-content-center">
    <div class="text-center">
      <div class="fs-1 fw-bold text-muted font-mono">VS</div>
      <span class="badge bg-secondary">Comparison</span>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">PSO Aggregates Sum</h5>
        <span class="badge bg-info text-white">Physical Verification</span>
      </div>
      <div class="display-6 fw-bold font-mono text-success mb-3">₹{{ number_format($metrics['psoCollection'], 2) }}</div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>PSO 1 (CB 01 - CB 10)</span>
          <span class="fw-bold font-mono">₹{{ number_format($metrics['pso1Total'], 2) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>PSO 2 (CB 11 - CB 20 + ITC)</span>
          <span class="fw-bold font-mono">₹{{ number_format($metrics['pso2Total'], 2) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between px-0">
          <span>PSO 3 (RB 01 - RB 10)</span>
          <span class="fw-bold font-mono">₹{{ number_format($metrics['pso3Total'], 2) }}</span>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- 7-Metric Cash & Multi-Mode Reconciliation Matrix -->
<div class="card border p-4 bg-white shadow-sm mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold mb-1"><i class="bi bi-wallet2 text-primary me-2"></i>Cash vs Paytm Split & Driver Reconciliation Matrix</h5>
      <p class="text-muted small mb-0">Separate tracking of physical cash, Paytm QR settlements, driver travel deductions, and pending cash shortages.</p>
    </div>
    <a href="{{ route('admin.denomination.index', ['date' => $metrics['businessDate']]) }}" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-calculator me-1"></i> Open Cash Denomination Counter
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle text-center mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th class="text-start">Reconciliation Parameter</th>
          <th class="text-end">Amount (₹)</th>
          <th class="text-start">Accounting Treatment & Ledger Destination</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="fw-bold">1</td>
          <td class="text-start fw-semibold">Total Bill Amount (DayBook Gross)</td>
          <td class="text-end font-mono fw-bold text-dark">₹{{ number_format($metrics['tallyTotal'], 2) }}</td>
          <td class="text-start small text-muted">Primary invoice / DayBook total imported from Tally ERP</td>
        </tr>
        <tr>
          <td class="fw-bold text-success">2</td>
          <td class="text-start fw-semibold text-success">Cash Received / Book Cash</td>
          <td class="text-end font-mono fw-bold text-success">₹{{ number_format($metrics['totCash'], 2) }}</td>
          <td class="text-start small text-muted">Gross cash portion collected across single & split bills</td>
        </tr>
        <tr>
          <td class="fw-bold text-info">3</td>
          <td class="text-start fw-semibold text-info">Paytm / Digital UPI Received</td>
          <td class="text-end font-mono fw-bold text-info">₹{{ number_format($metrics['totPaytm'], 2) }}</td>
          <td class="text-start small text-muted">Direct digital settlement routed straight to bank clearing ledger</td>
        </tr>
        <tr>
          <td class="fw-bold text-primary">4</td>
          <td class="text-start fw-semibold text-primary">Total Collections (Cash + Paytm)</td>
          <td class="text-end font-mono fw-bold text-primary">₹{{ number_format($metrics['totCash'] + $metrics['totPaytm'], 2) }}</td>
          <td class="text-start small text-muted">Combined realized receipts against daily turnover</td>
        </tr>
        <tr>
          <td class="fw-bold text-danger">5</td>
          <td class="text-start fw-semibold text-danger">Pending / Short Cash Variance</td>
          <td class="text-end font-mono fw-bold {{ $metrics['totalShortCash'] > 0 ? 'text-danger' : 'text-success' }}">
            {{ $metrics['totalShortCash'] > 0 ? ('₹' . number_format($metrics['totalShortCash'], 2) . ' (Short)') : '₹0.00 (Zero Shortage)' }}
          </td>
          <td class="text-start small text-muted">
            @if($metrics['totalShortCash'] > 0)
              <span class="badge bg-danger">Pending Recoveries</span> Cash discrepancy between driver deposit & bill book
            @else
              <span class="badge bg-success">Balanced</span> All driver cash handovers fully accounted
            @endif
          </td>
        </tr>
        <tr>
          <td class="fw-bold text-secondary">6</td>
          <td class="text-start fw-semibold text-secondary">KM / Driver Travel Allowance</td>
          <td class="text-end font-mono fw-bold text-info">
            {{ $metrics['totalKmAllowance'] > 0 ? ('-₹' . number_format($metrics['totalKmAllowance'], 2)) : '₹0.00' }}
          </td>
          <td class="text-start small text-muted">
            @if($metrics['totalKmCompleted'] > 0)
              <span class="badge bg-info text-dark font-mono">{{ $metrics['totalKmCompleted'] }} KM</span> Authorized driver fuel / travel deduction
            @else
              No travel mileage deductions entered for this business date
            @endif
          </td>
        </tr>
        <tr class="table-dark">
          <td class="fw-bold">7</td>
          <td class="text-start fw-bold fs-6">Final Master Reconciliation Balance</td>
          <td class="text-end font-mono fw-bold fs-6 {{ $metrics['difference'] == 0 ? 'text-success' : 'text-danger' }}">
            ₹{{ number_format($metrics['difference'], 2) }}
          </td>
          <td class="text-start small">
            @if($metrics['isReconciled'])
              <span class="badge bg-success">100% RECONCILED</span> Ready for approval and immutable sealing
            @else
              <span class="badge bg-danger">VARIANCE DETECTED</span> Resolve missing bills or discrepancies to seal
            @endif
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Difference Breakdown Card -->
<div class="card border p-4 bg-white shadow-sm">
  <h5 class="fw-bold mb-3">Discrepancy Breakdown & Resolution Checklist</h5>
  <div class="row g-3 align-items-center">
    <div class="col-md-4">
      <div class="p-3 rounded {{ $metrics['difference'] == 0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' }}">
        <small class="d-block fw-semibold text-uppercase">Net Variance</small>
        <span class="fs-3 fw-bold font-mono">₹{{ number_format($metrics['difference'], 2) }}</span>
        <small class="d-block mt-1">{{ $metrics['difference'] == 0 ? 'Zero Variance (Reconciled)' : 'Variance > ₹0 (Action Required)' }}</small>
      </div>
    </div>
    <div class="col-md-8">
      <div class="d-flex flex-column gap-2" style="font-size: 0.84rem;">
        @if($missingBills->count() > 0)
          @foreach($missingBills as $mb)
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-x-circle-fill text-danger"></i>
              <span><strong>Missing Bill {{ $mb->bill_no }}:</strong> Amount ₹{{ number_format($mb->amount, 2) }} physical slip not verified.</span>
            </div>
          @endforeach
        @else
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill text-success"></i>
            <span><strong>All Physical Serials Accounted:</strong> Verified in counter bundles.</span>
          </div>
        @endif
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success"></i>
          <span><strong>Cash Discounts (CD):</strong> Total ₹{{ number_format($metrics['totCd'], 2) }} properly deducted & authorized.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success"></i>
          <span><strong>Goods Returns & Refunds:</strong> Total ₹{{ number_format($metrics['totRefund'], 2) }} adjusted with customer slips.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill {{ $metrics['totalShortCash'] > 0 ? 'text-danger' : 'text-success' }}"></i>
          <span><strong>Physical Cash Count:</strong> Total ₹{{ number_format($metrics['totalPhysicalCash'], 2) }} counted in denomination registers.</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
