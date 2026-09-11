@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Dashboard</h4>
    <p class="text-muted mb-0">HisabKitap ERP — Fuel & PSO Reconciliation System</p>
  </div>
  @if(isset($currentUser) && $currentUser && ($currentUser->isOperator() || $currentUser->hasPermission('can_configure_pso')))
  <div>
    <a href="{{ route('admin.pso.index') }}" class="btn btn-primary">
      <i class="bi bi-diagram-3-fill me-1"></i> PSO Management
    </a>
  </div>
  @endif
</div>

<!-- Blank Dashboard Canvas -->
<div class="card border-0 shadow-sm rounded-3 bg-white p-5 text-center my-4">
  <div class="py-5">
    <div class="mb-3">
      <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 80px; height: 80px;">
        <i class="bi bi-grid-1x2-fill fs-1"></i>
      </div>
    </div>
    <h4 class="fw-bold text-dark mb-2">Dashboard Workspace</h4>
    <p class="text-muted mx-auto mb-4" style="max-width: 520px;">
      Welcome to <strong>HisabKitap ERP</strong>. The new dashboard analytics, charts, and KPI widgets will be configured here upon project completion.
    </p>
    @if(isset($currentUser) && $currentUser && ($currentUser->isOperator() || $currentUser->hasPermission('can_configure_pso')))
    <div class="d-flex justify-content-center gap-2">
      <a href="{{ route('admin.pso.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-list-ul me-1"></i> View PSO Series
      </a>
      <a href="{{ route('admin.pso.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Configure New PSO
      </a>
    </div>
    @endif
  </div>
</div>
@endsection
