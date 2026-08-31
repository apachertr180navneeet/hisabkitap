@extends('layouts.app')

@section('title', 'Approval & Sealing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Approval Workflow & Final File Sealing</h4>
    <p class="text-muted mb-0">Multi-stage compliance gate. After final approval, daily PSOs are sealed and permanently locked into read-only mode.</p>
  </div>
</div>

<!-- Approval Gate Checklist -->
<div class="card border p-4 bg-white shadow-sm mb-4">
  <h5 class="fw-bold mb-3">Approval Prerequisite Checks</h5>
  <div class="row g-3">
    <div class="col-md-3">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">1. Bill Verification</span>
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
        </div>
        <small class="text-muted">{{ $metrics['totalBillsCount'] }} bills imported</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">2. Missing Bills</span>
          @if($metrics['missingCount'] === 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
          @endif
        </div>
        <small class="text-muted">
          {{ $metrics['missingCount'] === 0 ? 'All bills accounted' : ($metrics['missingCount'] . ' missing unresolved') }}
        </small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">3. Master Recon</span>
          @if($metrics['difference'] == 0)
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
          @else
            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
          @endif
        </div>
        <small class="text-muted">
          {{ $metrics['difference'] == 0 ? 'Difference ₹0 (Balanced)' : 'Difference ₹' . number_format($metrics['difference']) }}
        </small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="p-3 border rounded bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">4. Corrections</span>
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
        </div>
        <small class="text-muted">All deductions authorized</small>
      </div>
    </div>
  </div>
</div>

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
      Sealing binds the business date's collections, freezes all bill entries, creates immutable audit hashes, and allows export of audited Master Summary.
    </p>
  </div>

  <div class="d-flex justify-content-center gap-3">
    @if(!$metrics['isSealed'])
      <form action="{{ route('approval.seal') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-lg btn-success px-4" {{ !$metrics['isReconciled'] ? 'disabled' : '' }}>
          <i class="bi bi-lock-fill me-1"></i> Approve & Seal Daily Records
        </button>
      </form>
    @else
      <form action="{{ route('approval.unseal') }}" method="POST" class="d-inline">
        @csrf
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
    <div class="text-danger small mt-2">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> Cannot seal while Reconciliation is FAILED or Difference &gt; ₹0.
    </div>
  @endif
</div>
@endsection
