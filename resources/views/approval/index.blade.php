@extends('layouts.app')

@section('title', 'Approval & Sealing')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1">Approval Workflow & Final File Sealing</h4>
    <p class="text-muted mb-0">Multi-stage compliance gate. After final approval, daily PSOs are sealed and permanently locked into read-only mode.</p>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2">
    <!-- Date Filter Form -->
    <form action="{{ route('admin.approval.index') }}" method="GET" class="d-flex align-items-center gap-2">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-calendar3 me-1"></i> Date</span>
        <input type="date" name="date" class="form-control font-mono" value="{{ $businessDate }}" onchange="this.form.submit()" title="Select Business Date">
      </div>
      <button type="submit" class="btn btn-sm btn-primary">
        <i class="bi bi-search"></i>
      </button>
    </form>
    <a href="{{ route('admin.reconciliation.index', ['date' => $businessDate]) }}" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-calculator me-1"></i> View Master Recon
    </a>
  </div>
</div>

<!-- Quick Date Navigation Bar -->
@if(!empty($availableDates) && count($availableDates) > 0)
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 bg-light rounded border">
    <small class="text-muted fw-semibold me-1"><i class="bi bi-clock-history me-1"></i>Available Dates:</small>
    @foreach(array_slice($availableDates, 0, 8) as $ad)
      <a href="{{ route('admin.approval.index', ['date' => $ad]) }}" 
         class="badge {{ $businessDate === $ad ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark border text-decoration-none' }} px-2 py-1 font-mono">
        {{ date('d/m/Y', strtotime($ad)) }}
      </a>
    @endforeach
  </div>
@endif

<!-- Approval Gate Checklist -->
<div class="card border p-4 bg-white shadow-sm mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Approval Prerequisite Checks (Date: <span class="font-mono text-primary">{{ date('d/m/Y', strtotime($businessDate)) }}</span>)</h5>
    <span class="badge {{ $metrics['isReconciled'] ? 'bg-success' : 'bg-danger' }}">
      {{ $metrics['isReconciled'] ? 'ALL GATES PASSED' : 'ACTION REQUIRED' }}
    </span>
  </div>
  <div class="row g-3">
    <div class="col-md">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">1. Bill Verification</span>
          @if($metrics['totalBillsCount'] > 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-hourglass-split text-secondary fs-5"></i>
          @endif
        </div>
        <small class="text-muted">{{ $metrics['totalBillsCount'] > 0 ? ($metrics['totalBillsCount'] . ' bills imported') : '0 bills imported' }}</small>
      </div>
    </div>
    <div class="col-md">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">2. Missing Bills</span>
          @if($metrics['totalBillsCount'] === 0)
            <i class="bi bi-dash-circle text-secondary fs-5"></i>
          @elseif($metrics['missingCount'] === 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
          @endif
        </div>
        <small class="text-muted">
          {{ $metrics['totalBillsCount'] === 0 ? 'No bills loaded' : ($metrics['missingCount'] === 0 ? 'All bills accounted' : ($metrics['missingCount'] . ' missing unresolved')) }}
        </small>
      </div>
    </div>
    <div class="col-md">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">3. PSO Series Checks</span>
          @if($metrics['totalBillsCount'] === 0)
            <i class="bi bi-dash-circle text-secondary fs-5"></i>
          @elseif(($metrics['unapprovedMismatchCount'] ?? 0) === 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
          @endif
        </div>
        <small class="text-muted">
          @if(($metrics['unapprovedMismatchCount'] ?? 0) > 0)
            <span class="text-danger fw-bold">{{ $metrics['unapprovedMismatchCount'] }} unapproved mismatch(es)</span>
          @elseif(($metrics['approvedMismatchCount'] ?? 0) > 0)
            <span class="text-success">{{ $metrics['approvedMismatchCount'] }} approved override(s)</span>
          @else
            All in series
          @endif
        </small>
      </div>
    </div>
    <div class="col-md">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">4. Master Recon</span>
          @if($metrics['totalBillsCount'] === 0)
            <i class="bi bi-dash-circle text-secondary fs-5"></i>
          @elseif($metrics['difference'] == 0 && ($metrics['unapprovedMismatchCount'] ?? 0) === 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
          @endif
        </div>
        <small class="text-muted">
          {{ $metrics['totalBillsCount'] === 0 ? 'Awaiting bills' : ($metrics['difference'] == 0 && ($metrics['unapprovedMismatchCount'] ?? 0) === 0 ? 'Difference ₹0 (Balanced)' : ('Difference ₹' . number_format($metrics['difference'], 2))) }}
        </small>
      </div>
    </div>
    <div class="col-md">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">5. Deductions</span>
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
        </div>
        <small class="text-muted">{{ $metrics['correctionsCount'] ?? 0 }} authorized deductions</small>
      </div>
    </div>
  </div>
</div>

@if(($metrics['unapprovedMismatchCount'] ?? 0) > 0)
  <div class="alert alert-danger d-flex align-items-center justify-content-between mb-4 shadow-sm border-danger">
    <div class="d-flex align-items-center gap-3">
      <i class="bi bi-shield-slash-fill fs-2"></i>
      <div>
        <h6 class="fw-bold mb-1">PSO Bill Series Mismatches Blocking Final Seal</h6>
        <p class="mb-0 small">
          <strong>{{ $metrics['unapprovedMismatchCount'] }} bill(s)</strong> fall outside their assigned PSO range or belong to another PSO and have not been approved. Review and approve/reject them before sealing.
        </p>
      </div>
    </div>
    <a href="{{ route('admin.reconciliation.index', ['date' => $businessDate]) }}" class="btn btn-sm btn-danger text-nowrap">
      <i class="bi bi-arrow-right-circle me-1"></i> Review Mismatches
    </a>
  </div>
@endif

<!-- Sealing Action Card -->
<div class="card border p-4 bg-white shadow-sm text-center">
  <div class="mx-auto mb-3" style="max-width: 550px;">
    <div class="seal-stamp mb-3">
      <span>OFFICIAL</span>
      <span>HISABKITAP</span>
      <span>SEAL</span>
    </div>
    <h4 class="fw-bold mb-2">Official PSO Seal & Final Sign-Off</h4>
    <p class="text-muted small">
      Sealing binds the business date's collections (<strong>{{ date('d/m/Y', strtotime($businessDate)) }}</strong>), freezes all bill entries, creates immutable audit hashes, and allows export of audited Master Summary.
    </p>
  </div>

  @if(!$currentUser->hasPermission('can_approve_sealing'))
    <div class="alert alert-warning small mb-3 text-start">
      <i class="bi bi-shield-exclamation me-1"></i> Sealing authorization is restricted. Your account <strong>{{ $currentUser->name }}</strong> ({{ $currentUser->role_name }}) does not have Sealing Sign-off permission.
    </div>
  @endif

  <div class="d-flex justify-content-center gap-3">
    @if(!$metrics['isSealed'])
      <form action="{{ route('admin.approval.seal') }}" method="POST">
        @csrf
        <input type="hidden" name="date" value="{{ $businessDate }}">
        <button type="submit" class="btn btn-lg btn-success px-4" {{ (!$metrics['isReconciled'] || !$currentUser->hasPermission('can_approve_sealing')) ? 'disabled' : '' }}>
          <i class="bi bi-lock-fill me-1"></i> Approve & Seal Daily Records
        </button>
      </form>
    @else
      <form action="{{ route('admin.approval.unseal') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="date" value="{{ $businessDate }}">
        <button type="submit" class="btn btn-lg btn-outline-danger px-4">
          <i class="bi bi-unlock-fill me-1"></i> Emergency Unseal (Audit Logged)
        </button>
      </form>
      <button class="btn btn-lg btn-success px-4" data-bs-toggle="modal" data-bs-target="#modal-seal-cert">
        <i class="bi bi-file-earmark-check me-1"></i> View Certificate
      </button>
    @endif
  </div>

  @if(!$metrics['isReconciled'] && !$metrics['isSealed'])
    <div class="text-muted small mt-2">
      <i class="bi bi-info-circle me-1"></i>
      @if(!$metrics['hasBills'])
        Cannot seal: No bills exist for business date {{ date('d/m/Y', strtotime($businessDate)) }}.
      @else
        Cannot seal while Reconciliation is FAILED or Difference &gt; ₹0.
      @endif
    </div>
  @endif
</div>
@endsection
