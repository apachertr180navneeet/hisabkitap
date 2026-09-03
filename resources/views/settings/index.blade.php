@extends('layouts.app')

@section('title', 'Cutoff & Financial Year Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">System Settings & Configuration</h4>
    <p class="text-muted mb-0">Configure active Financial Year (FY), daily PSO cutoff timings, and automatic rollover rules.</p>
  </div>
  @if($currentUser->hasPermission('can_edit_cutoff'))
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-fy">
    <i class="bi bi-calendar-plus me-1"></i> Add Financial Year
  </button>
  @endif
</div>

{{-- KPI Summary Cards --}}
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card border p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
          <i class="bi bi-calendar-range fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Active Financial Year</div>
          <div class="fw-bold fs-5 font-mono text-primary">{{ $activeFyName }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
          <i class="bi bi-calendar2-check fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">FY Date Period</div>
          <div class="fw-semibold font-mono text-dark" style="font-size: 0.88rem;">
            {{ $activeFy ? $activeFy->formatted_range : date('d/m/Y', strtotime($activeFyStart)) . ' – ' . date('d/m/Y', strtotime($activeFyEnd)) }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
          <i class="bi bi-alarm fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Daily PSO Cutoff</div>
          <div class="fw-bold fs-5 font-mono text-dark">{{ $cutoffTime }} <span class="text-muted fs-6 fw-normal">IST</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border p-3 bg-white h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
          <i class="bi bi-arrow-repeat fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">PSO Next-Day Rollover</div>
          <div class="fw-bold fs-5 {{ $rolloverActive ? 'text-success' : 'text-secondary' }}">
            {{ $rolloverActive ? 'Enabled' : 'Disabled' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@if(!$currentUser->hasPermission('can_edit_cutoff'))
<div class="alert alert-warning small mb-4">
  <i class="bi bi-exclamation-triangle me-1"></i> You are currently viewing as <strong>{{ $currentUser->name }} ({{ $currentUser->role_name }})</strong>. Configuration modifications are restricted to Super Admin / Cutoff policy managers.
</div>
@endif

{{-- SECTION 1: FINANCIAL YEAR CONFIGURATION --}}
<div class="card border bg-white shadow-sm mb-4">
  <div class="card-header bg-white py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-calendar3-week text-primary fs-5"></i>
      <div>
        <h5 class="fw-bold mb-0">Financial Year Management</h5>
        <small class="text-muted">Define, activate, and lock accounting financial years for the ERP system.</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary px-3 py-2 font-mono">Current Active FY: {{ $activeFyName }}</span>
    </div>
  </div>

  <div class="card-body p-4">
    {{-- Quick Switcher Bar --}}
    <div class="bg-light p-3 rounded border mb-4">
      <form action="{{ route('admin.settings.financial_year.set_active') }}" method="POST" class="row g-3 align-items-center">
        @csrf
        <div class="col-md-auto">
          <label class="form-label fw-bold mb-0 text-dark">
            <i class="bi bi-check2-square me-1 text-primary"></i>Select Current Financial Year:
          </label>
        </div>
        <div class="col-md-5 col-lg-4">
          <select name="financial_year_id" class="form-select font-mono fw-semibold" {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }} required>
            @foreach($financialYears as $fy)
              <option value="{{ $fy->id }}" {{ $fy->is_active ? 'selected' : '' }}>
                {{ $fy->name }} ({{ $fy->formatted_range }}) {{ $fy->is_active ? '★ Current' : '' }} {{ $fy->is_locked ? '[Locked]' : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-auto">
          <button type="submit" class="btn btn-primary" {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }}>
            <i class="bi bi-check-circle-fill me-1"></i> Apply as Current FY
          </button>
        </div>
        <div class="col-md text-md-end">
          <span class="text-muted small">Current active FY is used across all bills, reconciliation, and audit reporting.</span>
        </div>
      </form>
    </div>

    {{-- Financial Years Registry Table --}}
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width: 20%;">Financial Year</th>
            <th style="width: 25%;">Date Range (Start &ndash; End)</th>
            <th style="width: 15%;">Active Status</th>
            <th style="width: 15%;">Audit Status</th>
            <th style="width: 12%;">Notes</th>
            <th class="text-end pe-3" style="width: 13%;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($financialYears as $fy)
          <tr class="{{ $fy->is_active ? 'table-primary bg-opacity-10' : '' }}">
            <td class="ps-3">
              <span class="fw-bold font-mono {{ $fy->is_active ? 'text-primary fs-6' : 'text-dark' }}">{{ $fy->name }}</span>
              @if($fy->is_active)
                <span class="badge bg-primary ms-1" style="font-size: 0.7rem;">CURRENT</span>
              @endif
            </td>
            <td class="font-mono small">
              <i class="bi bi-calendar-event me-1 text-muted"></i>
              {{ $fy->formatted_range }}
            </td>
            <td>
              @if($fy->is_active)
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Current Operating</span>
              @else
                <span class="badge bg-secondary">Inactive</span>
              @endif
            </td>
            <td>
              @if($fy->is_locked)
                <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Locked / Closed</span>
              @else
                <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-unlock-fill me-1"></i>Open</span>
              @endif
            </td>
            <td class="small text-muted">
              {{ $fy->notes ?: '—' }}
            </td>
            <td class="text-end pe-3">
              <div class="d-flex justify-content-end align-items-center gap-1">
                @if($fy->is_active)
                  <span class="badge bg-success bg-opacity-10 text-success border border-success py-1.5 px-2">
                    <i class="bi bi-check2-all me-1"></i>Selected
                  </span>
                @elseif($currentUser->hasPermission('can_edit_cutoff'))
                  <form action="{{ route('admin.settings.financial_year.set_active') }}" method="POST" class="d-inline" onsubmit="return confirm('Select {{ $fy->name }} as current financial year?');">
                    @csrf
                    <input type="hidden" name="financial_year_id" value="{{ $fy->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap" title="Select as Current Financial Year">
                      <i class="bi bi-check-circle me-1"></i>Select
                    </button>
                  </form>
                @endif

                @if($currentUser->hasPermission('can_edit_cutoff'))
                <form action="{{ route('admin.settings.financial_year.toggle_lock', $fy->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Toggle lock status for Financial Year {{ $fy->name }}?');">
                  @csrf
                  <button type="submit" class="btn btn-sm {{ $fy->is_locked ? 'btn-outline-warning' : 'btn-outline-secondary' }}" title="{{ $fy->is_locked ? 'Unlock FY' : 'Lock FY' }}">
                    <i class="bi {{ $fy->is_locked ? 'bi-lock-fill text-danger' : 'bi-unlock' }}"></i>
                  </button>
                </form>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">
              <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary"></i>
              No Financial Years registered yet. Click "Add Financial Year" above to create one.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- SECTION 2: DAILY PSO CUTOFF & ROLLOVER --}}
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-gear-wide-connected text-primary me-2"></i>System Cutoff & Financial Year</h5>
        <span class="badge bg-danger"><i class="bi bi-shield-lock-fill me-1"></i>Super Admin Only</span>
      </div>

      <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="mb-3">
          <label class="form-label fw-semibold">Select Current Financial Year</label>
          <select name="financial_year_id" class="form-select font-mono" {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }}>
            @foreach($financialYears as $fy)
              <option value="{{ $fy->id }}" {{ $fy->is_active ? 'selected' : '' }}>
                {{ $fy->name }} ({{ $fy->formatted_range }}) {{ $fy->is_active ? '★ (Currently Active)' : '' }}
              </option>
            @endforeach
          </select>
          <small class="text-muted">Active financial year: <strong>{{ $activeFyName }}</strong></small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Cutoff Time (24h Format)</label>
          <input type="time" name="cutoff_time" class="form-control font-mono" value="{{ $cutoffTime }}" {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }} required>
          <small class="text-muted">Standard Default: 7:00 PM (19:00 IST)</small>
        </div>
        
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="cutoff_rollover_active" id="setting-cutoff-toggle" {{ $rolloverActive ? 'checked' : '' }} {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }}>
          <label class="form-check-label fw-semibold" for="setting-cutoff-toggle">Enable Automatic Next-Day PSO Rollover</label>
        </div>

        <button type="submit" class="btn btn-primary" {{ !$currentUser->hasPermission('can_edit_cutoff') ? 'disabled' : '' }}>
          <i class="bi bi-save me-1"></i> Save Settings & Financial Year
        </button>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="cutoff-card h-100 p-4 rounded-3 text-white" style="background: linear-gradient(135deg, #1e3a8a, #0f172a); min-height: 260px;">
      <h5 class="fw-bold mb-2 text-white"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Daily Cutoff Business Rule</h5>
      <p class="text-white-50 mb-3" style="font-size: 0.88rem;">
        The ERP enforces strict daily timing integrity:
      </p>
      <div class="p-3 rounded mb-3 border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.1);">
        <div class="fw-bold text-white mb-1"><i class="bi bi-check2 text-success me-1"></i> Bill entered &le; Cutoff Time ({{ $cutoffTime }}):</div>
        <div class="text-white-50 small ps-3">&rarr; Assigned to <strong class="text-white">Today's PSO</strong> and counted in today's reconciliation.</div>
      </div>
      <div class="p-3 rounded border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.1);">
        <div class="fw-bold text-white mb-1"><i class="bi bi-clock-history text-warning me-1"></i> Bill entered &gt; Cutoff Time ({{ $cutoffTime }}):</div>
        <div class="text-white-50 small ps-3">&rarr; Automatically assigned to <strong class="text-white">Next Day's PSO</strong>.</div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL: ADD FINANCIAL YEAR --}}
@if($currentUser->hasPermission('can_edit_cutoff'))
<div class="modal fade" id="modal-add-fy" tabindex="-1" aria-labelledby="modalAddFyLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalAddFyLabel"><i class="bi bi-calendar-plus text-primary me-2"></i>Add New Financial Year</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.settings.financial_year.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="fy-start-date" class="form-control" value="{{ date('Y') }}-04-01" required>
            <small class="text-muted">Standard Indian FY starts on 1st April (e.g., 2027-04-01)</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
            <input type="date" name="end_date" id="fy-end-date" class="form-control" value="{{ date('Y') + 1 }}-03-31" required>
            <small class="text-muted">Standard Indian FY ends on 31st March (e.g., 2028-03-31)</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Financial Year Name / Code <span class="text-danger">*</span></label>
            <input type="text" name="name" id="fy-name" class="form-control font-mono" placeholder="e.g. 2027-2028" value="{{ date('Y') }}-{{ date('Y') + 1 }}" required>
            <small class="text-muted">Standard convention: YYYY-YYYY (e.g. 2027-2028)</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Notes / Description</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes (e.g. Next Operating Cycle)">
          </div>

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="set_as_active" id="fy-set-active" value="1">
            <label class="form-check-label fw-semibold" for="fy-set-active">Set as current Active Financial Year now</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Create Financial Year</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const startDateInput = document.getElementById('fy-start-date');
    const endDateInput = document.getElementById('fy-end-date');
    const nameInput = document.getElementById('fy-name');

    if (startDateInput && endDateInput && nameInput) {
      startDateInput.addEventListener('change', function () {
        if (!this.value) return;
        const d = new Date(this.value);
        if (isNaN(d.getTime())) return;

        const startYear = d.getFullYear();
        // Indian FY convention: if started in year Y, ends March 31 of Y+1
        const endYear = startYear + 1;
        const endMonthStr = '03';
        const endDayStr = '31';
        endDateInput.value = `${endYear}-${endMonthStr}-${endDayStr}`;
        nameInput.value = `${startYear}-${endYear}`;
      });
    }
  });
</script>
@endif

@endsection
