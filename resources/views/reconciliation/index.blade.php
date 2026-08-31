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
@if(!$metrics['isReconciled'])
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
      <form action="{{ route('reconciliation.resolve') }}" method="POST">
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
      <a href="{{ route('approval.index') }}" class="btn btn-success">
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
          <span class="fw-bold font-mono">{{ $metrics['businessDate'] }}</span>
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
            <span><strong>All Physical Serials Accounted:</strong> 32/32 bills verified in counter bundles.</span>
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
      </div>
    </div>
  </div>
</div>
@endsection
