@extends('layouts.app')

@section('title', 'Cutoff & Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Daily Cutoff & System Settings</h4>
    <p class="text-muted mb-0">Configure daily PSO cutoff timings and automatic rollover business rules.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0">Daily PSO Cutoff Time Configuration</h5>
        <span class="badge bg-danger"><i class="bi bi-shield-lock-fill me-1"></i>Super Admin Only</span>
      </div>

      @if(!in_array(($currentUser['role_code'] ?? ''), ['SUPER_ADMIN', 'ADMIN']))
        <div class="alert alert-warning small mb-3">
          <i class="bi bi-exclamation-triangle me-1"></i> You are currently viewing as <strong>{{ $currentUser['name'] }} ({{ $currentUser['role_name'] }})</strong>. Modification is restricted to Super Admin (Suresh Gupta).
        </div>
      @endif

      <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        <div class="mb-3">
          <label class="form-label fw-semibold">Cutoff Time (24h Format)</label>
          <input type="time" name="cutoff_time" class="form-control font-mono" value="{{ $cutoffTime }}" {{ !in_array(($currentUser['role_code'] ?? ''), ['SUPER_ADMIN', 'ADMIN']) ? 'disabled' : '' }} required>
          <small class="text-muted">Standard Default: 7:00 PM (19:00 IST)</small>
        </div>
        
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="cutoff_rollover_active" id="setting-cutoff-toggle" {{ $rolloverActive ? 'checked' : '' }} {{ !in_array(($currentUser['role_code'] ?? ''), ['SUPER_ADMIN', 'ADMIN']) ? 'disabled' : '' }}>
          <label class="form-check-label fw-semibold" for="setting-cutoff-toggle">Enable Automatic Next-Day PSO Rollover</label>
        </div>

        <button type="submit" class="btn btn-primary" {{ !in_array(($currentUser['role_code'] ?? ''), ['SUPER_ADMIN', 'ADMIN']) ? 'disabled' : '' }}>
          Save Cutoff Settings
        </button>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="cutoff-card h-100">
      <h5 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Daily Cutoff Business Rule</h5>
      <p class="text-white-50" style="font-size: 0.85rem;">
        The ERP enforces strict daily timing integrity:
      </p>
      <div class="p-3 bg-white bg-opacity-10 rounded mb-2 border border-white border-opacity-25">
        <div class="fw-bold text-white mb-1"><i class="bi bi-check2 text-success me-1"></i> Bill entered &le; Cutoff Time ({{ $cutoffTime }}):</div>
        <div class="text-white-50 small">&rarr; Assigned to <strong>Today's PSO</strong> and counted in today's reconciliation.</div>
      </div>
      <div class="p-3 bg-white bg-opacity-10 rounded border border-white border-opacity-25">
        <div class="fw-bold text-white mb-1"><i class="bi bi-clock-history text-warning me-1"></i> Bill entered &gt; Cutoff Time ({{ $cutoffTime }}):</div>
        <div class="text-white-50 small">&rarr; Automatically assigned to <strong>Next Day's PSO</strong>.</div>
      </div>
    </div>
  </div>
</div>
@endsection
