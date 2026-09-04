@extends('layouts.app')

@section('title', 'Edit PSO Series: ' . $pso->code)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <a href="{{ route('admin.pso.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
        <i class="bi bi-arrow-left me-1"></i> Back to PSO List
      </a>
      <span class="text-muted">/</span>
      <span class="text-muted small">Edit Series</span>
      <span class="text-muted">/</span>
      <span class="badge bg-primary font-mono">{{ $pso->code }}</span>
    </div>
    <h4 class="fw-bold mb-1">Edit PSO Series: <span class="text-primary font-mono">{{ $pso->code }}</span></h4>
    <p class="text-muted mb-0">Modify serial range, prefix, assigned operator, and special bill series rules for this counter.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('admin.pso.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle me-1"></i> Cancel
    </a>
  </div>
</div>

{{-- Linked Records Notice if any --}}
@if($pso->bills_count > 0)
<div class="alert alert-info d-flex align-items-center mb-4">
  <i class="bi bi-info-circle-fill fs-5 me-3"></i>
  <div>
    <strong>Existing Reconciliation Data:</strong> This PSO series is currently linked to 
    <span class="badge bg-primary font-mono">{{ $pso->bills_count }} bill{{ $pso->bills_count > 1 ? 's' : '' }}</span> 
    in the verification register. Adjusting serial ranges will impact reconciliation calculations for active sheets.
  </div>
</div>
@endif

<div class="row g-4">
  {{-- Form Column --}}
  <div class="col-lg-8">
    <form action="{{ route('admin.pso.update', $pso->id) }}" method="POST" id="form-pso-edit">
      @csrf

      {{-- Card 1: PSO Identity & Operator Assignment --}}
      <div class="card border bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person-badge text-primary me-2"></i>PSO Identity & Operator Assignment
          </h6>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold" for="code">
                PSO Identifier Code <span class="badge bg-secondary ms-1 font-mono">System Assigned</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light font-mono"><i class="bi bi-hash"></i></span>
                <input type="text" name="code" id="code" class="form-control font-mono fw-bold bg-light" 
                       value="{{ $pso->code }}" readonly>
                <span class="input-group-text bg-light text-muted" title="Read-only identifier"><i class="bi bi-lock-fill"></i></span>
              </div>
              <div class="form-text small text-muted"><i class="bi bi-info-circle me-1"></i>System unique identifier (Read-only).</div>
            </div>

            <div class="col-md-5">
              <label class="form-label fw-semibold" for="operator_name">
                Assigned Operator / Staff <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="operator_name" id="operator_name" list="operator-options" 
                       class="form-control @error('operator_name') is-invalid @enderror" 
                       value="{{ old('operator_name', $pso->operator_name) }}" 
                       placeholder="e.g. Big Bite or Ramesh Sharma" required>
                <datalist id="operator-options">
                  @foreach($operators as $op)
                    <option value="{{ $op }}">{{ $op }}</option>
                  @endforeach
                </datalist>
              </div>
              <div class="form-text small">Operator responsible for counter bills and reconciliation.</div>
              @error('operator_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold" for="is_active">Status</label>
              <div class="form-check form-switch pt-1">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" 
                       {{ old('is_active', $pso->is_active ? '1' : '0') == '1' ? 'checked' : '' }} 
                       style="width: 2.5em; height: 1.3em;">
                <label class="form-check-label ms-2 fw-semibold {{ $pso->is_active ? 'text-success' : 'text-muted' }}" for="is_active" id="status-label">
                  {{ $pso->is_active ? 'Active (Operational)' : 'Inactive (Disabled)' }}
                </label>
              </div>
              <div class="form-text small">Include in daily verification sheet.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Card 2: Crew & Vehicle Assignment (Driver, Helpers & Gadi) --}}
      <div class="card border bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-truck text-primary me-2"></i>Crew & Vehicle Assignment (Driver, Helpers & Gadi)
          </h6>
        </div>
        <div class="card-body p-4">
          {{-- Row 1: Driver Name (Required) & Gadi Number (Not required) --}}
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="driver_name">
                Driver Name <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-badge text-primary"></i></span>
                <input type="text" name="driver_name" id="driver_name" list="driver-options" 
                       class="form-control @error('driver_name') is-invalid @enderror" 
                       value="{{ old('driver_name', $pso->driver_name) }}" 
                       placeholder="e.g. Ramesh Kumar" required>
                <datalist id="driver-options">
                  @if(!empty($drivers))
                    @foreach($drivers as $drv)
                      <option value="{{ $drv }}">{{ $drv }}</option>
                    @endforeach
                  @endif
                </datalist>
              </div>
              <div class="form-text small">Designated route driver (Required).</div>
              @error('driver_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="gadi_number">
                Gadi Number (Vehicle No) <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-truck text-secondary"></i></span>
                <input type="text" name="gadi_number" id="gadi_number" list="gadi-options" 
                       class="form-control text-uppercase font-mono @error('gadi_number') is-invalid @enderror" 
                       value="{{ old('gadi_number', $pso->gadi_number) }}" 
                       placeholder="e.g. RJ 14 GA 1234 / DL 01 AB 5678">
                <datalist id="gadi-options">
                  @if(!empty($gadiOptions))
                    @foreach($gadiOptions as $g)
                      <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                  @endif
                </datalist>
              </div>
              <div class="form-text small">Delivery vehicle / van registration number (Not required).</div>
              @error('gadi_number')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- Row 2: Helper 1 (Required), Helper 2 (Optional), Helper 3 (Optional) --}}
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold" for="helper_1">
                Helper 1 <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-fill text-primary"></i></span>
                <input type="text" name="helper_1" id="helper_1" list="helper-options" 
                       class="form-control @error('helper_1') is-invalid @enderror" 
                       value="{{ old('helper_1', $pso->helper_1) }}" 
                       placeholder="e.g. Mukesh Kumar" required>
              </div>
              <div class="form-text small">Primary counter/delivery assistant (Required).</div>
              @error('helper_1')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold" for="helper_2">
                Helper 2 <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-plus text-secondary"></i></span>
                <input type="text" name="helper_2" id="helper_2" list="helper-options" 
                       class="form-control @error('helper_2') is-invalid @enderror" 
                       value="{{ old('helper_2', $pso->helper_2) }}" 
                       placeholder="e.g. Rajesh Singh">
              </div>
              <div class="form-text small">Second assistant (Optional).</div>
              @error('helper_2')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold" for="helper_3">
                Helper 3 <span class="text-muted small fw-normal">(Optional)</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person-plus text-secondary"></i></span>
                <input type="text" name="helper_3" id="helper_3" list="helper-options" 
                       class="form-control @error('helper_3') is-invalid @enderror" 
                       value="{{ old('helper_3', $pso->helper_3) }}" 
                       placeholder="e.g. Sonu Lal">
              </div>
              <div class="form-text small">Third assistant / loader (Optional).</div>
              @error('helper_3')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <datalist id="helper-options">
              @if(!empty($helpers))
                @foreach($helpers as $h)
                  <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
              @endif
            </datalist>
          </div>
        </div>
      </div>

      {{-- Card 3: Bill Series & Sequence Range (Table-style Multi-Range) --}}
      <div class="card border bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark">
              <i class="bi bi-card-checklist text-primary me-2"></i>Bill Series & Sequence Range
            </h6>
            <div class="text-muted small">Configure counter bill prefix and continuous sequential number series.</div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btn-add-series-row-top">
            <i class="bi bi-plus-circle me-1"></i> Add More Range
          </button>
        </div>
        <div class="card-body p-4">
          {{-- Table Header Bar (Rendered once on top) --}}
          <div class="d-none d-md-block bg-light rounded-top border p-2 px-3 fw-semibold text-muted small text-uppercase" style="letter-spacing: 0.03em; font-size: 0.72rem;">
            <div class="row g-2 align-items-center">
              <div class="col-md-3">
                <i class="bi bi-tag text-primary me-1"></i>Bill Prefix <span class="text-danger">*</span>
              </div>
              <div class="col-md-3">
                <i class="bi bi-calendar-range text-primary me-1"></i>Current Financial Year <span class="badge bg-secondary-subtle text-secondary font-mono ms-1" style="font-size: 0.65rem;">Active</span>
              </div>
              <div class="col-md-2 text-center">
                Start Number <span class="text-danger">*</span>
              </div>
              <div class="col-md-2 text-center">
                End Number <span class="text-danger">*</span>
              </div>
              <div class="col-md-1 text-center">
                Bills
              </div>
              <div class="col-md-1 text-center">
                Action
              </div>
            </div>
          </div>

          <div id="series-rows-container">
            @php
              $ranges = $pso->getAllSeriesRanges();
            @endphp
            @foreach($ranges as $idx => $rng)
            {{-- Series Row --}}
            <div class="series-row p-2 px-3 border-start border-end border-bottom bg-white" data-row-index="{{ $idx }}">
              <div class="row g-2 align-items-center">
                {{-- 1. Bill Prefix Drop Down --}}
                <div class="col-md-3">
                  <div class="d-md-none text-muted small fw-semibold mb-1">Bill Prefix <span class="text-danger">*</span></div>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-tag"></i></span>
                    <select name="series[{{ $idx }}][prefix]" class="form-select form-select-sm font-mono fw-bold text-uppercase select-prefix" required>
                      <option value="">-- SELECT PREFIX --</option>
                      @php $matched = false; @endphp
                      @foreach($prefixes as $pfx)
                        @php
                          $isSelected = ($rng['prefix'] == $pfx->prefix);
                          if ($isSelected) $matched = true;
                        @endphp
                        <option value="{{ $pfx->prefix }}" {{ $isSelected ? 'selected' : '' }}>
                          {{ $pfx->prefix }} &ndash; {{ $pfx->name }}
                        </option>
                      @endforeach
                      @if(!$matched && !empty($rng['prefix']))
                        <option value="{{ $rng['prefix'] }}" selected>{{ $rng['prefix'] }} (Configured Prefix)</option>
                      @endif
                      <option value="__quick_add_prefix__" class="text-primary fw-bold">+ Add New Prefix...</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-quick-add-trigger" title="Quick Add Prefix to Master">
                      <i class="bi bi-plus-lg"></i>
                    </button>
                  </div>
                </div>

                {{-- 2. Readonly Current Financial Year --}}
                <div class="col-md-3">
                  <div class="d-md-none text-muted small fw-semibold mb-1">Current Financial Year</div>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-primary"><i class="bi bi-calendar3"></i></span>
                    <input type="text" name="series[{{ $idx }}][financial_year]" class="form-control form-control-sm font-mono fw-bold bg-light input-fy" 
                           value="{{ $rng['financial_year'] ?? $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}" readonly>
                    <span class="input-group-text bg-light text-muted" title="Active Financial Year (Read-only)"><i class="bi bi-lock-fill" style="font-size: 0.72rem;"></i></span>
                  </div>
                </div>

                {{-- 3. Start Number --}}
                <div class="col-md-2">
                  <div class="d-md-none text-muted small fw-semibold mb-1">Start Number <span class="text-danger">*</span></div>
                  <input type="number" name="series[{{ $idx }}][start_no]" class="form-control form-control-sm font-mono text-center fw-semibold input-start" 
                         value="{{ $rng['start_no'] }}" min="1" required placeholder="Start">
                </div>

                {{-- 4. End Number --}}
                <div class="col-md-2">
                  <div class="d-md-none text-muted small fw-semibold mb-1">End Number <span class="text-danger">*</span></div>
                  <input type="number" name="series[{{ $idx }}][end_no]" class="form-control form-control-sm font-mono text-center fw-semibold input-end" 
                         value="{{ $rng['end_no'] }}" min="1" required placeholder="End">
                </div>

                {{-- 5. Row Bills Count Badge --}}
                <div class="col-md-1 text-center">
                  <div class="d-md-none text-muted small fw-semibold mb-1">Bills Count</div>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono py-1 px-2 row-bills-badge">
                    {{ max(0, (int)$rng['end_no'] - (int)$rng['start_no'] + 1) }}
                  </span>
                </div>

                {{-- 6. Action / Delete button --}}
                <div class="col-md-1 text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" 
                          style="{{ count($ranges) > 1 ? 'padding: 0.25rem 0.5rem;' : 'display: none; padding: 0.25rem 0.5rem;' }}" title="Remove this range">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </div>
            </div>
            @endforeach
          </div>

          {{-- Add More Button Below Rows --}}
          <div class="d-flex justify-content-between align-items-center mt-3 pt-1">
            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-3" id="btn-add-series-row">
              <i class="bi bi-plus-circle me-1"></i> Add More Range
            </button>
            <span class="badge bg-light text-secondary border font-mono px-3 py-2">
              <i class="bi bi-layers-fill text-primary me-1"></i><span id="series-row-count">{{ count($ranges) }}</span> Range(s) Configured
            </span>
          </div>

          {{-- Enhanced Sequence Preview Banner --}}
          <div class="alert alert-primary bg-primary-subtle border-primary-subtle text-dark mt-3 p-3 mb-0 rounded shadow-xs">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary p-1.5 rounded-circle"><i class="bi bi-info-lg text-white"></i></span>
                <div>
                  <div class="small fw-semibold text-muted">Sequence Preview:</div>
                  <div class="font-mono text-primary fw-bold fs-6" id="preview-sequence-text">{{ $pso->prefix }} {{ sprintf('%02d', $pso->start_no) }} to {{ $pso->prefix }} {{ sprintf('%02d', $pso->end_no) }}</div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success font-mono fs-6 px-3 py-1.5">
                  <span id="preview-count">{{ max(0, $pso->end_no - $pso->start_no + 1) }}</span> daily bills verified
                </span>
                <span class="badge bg-white text-dark border font-mono py-1.5">
                  <i class="bi bi-calendar-range text-primary me-1"></i>FY: <span id="preview-fy">{{ $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Card 3: Special Bills & Additional Notes --}}
      <div class="card border bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-card-text text-primary me-2"></i>Special Bills & Additional Notes
          </h6>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold" for="specials">
                Special Bills Series <span class="text-muted fw-normal">(Optional &ndash; comma separated)</span>
              </label>
              @php
                $specialsString = is_array($pso->specials) ? implode(', ', $pso->specials) : ($pso->specials ?? '');
              @endphp
              <input type="text" name="specials" id="specials" class="form-control font-mono @error('specials') is-invalid @enderror" 
                     value="{{ old('specials', $specialsString) }}" placeholder="e.g. ITC 01, ITC 03, SPL 05">
              <div class="form-text small">Enter comma-separated non-standard or company specific bill numbers.</div>
              <div id="specials-badge-container" class="mt-2 d-flex flex-wrap gap-1">
                @if(!empty($pso->specials))
                  @foreach($pso->specials as $spec)
                    <span class="badge bg-info text-dark font-mono">{{ $spec }}</span>
                  @endforeach
                @endif
              </div>
              @error('specials')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="description">
                Notes & Additional Description <span class="text-muted fw-normal">(Optional)</span>
              </label>
              <textarea name="description" id="description" rows="2" class="form-control" 
                        placeholder="e.g. Handles sequence CB 01 to CB 10.">{{ old('description', $pso->description) }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-4">
        <a href="{{ route('admin.pso.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Cancel & Return to List
        </a>
        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
          <i class="bi bi-check-circle-fill me-1"></i> Update PSO Configuration
        </button>
      </div>
    </form>
  </div>

  {{-- Right Information / Live Preview Column --}}
  <div class="col-lg-4">
    {{-- Live Card Preview --}}
    <div class="card border bg-white shadow-sm mb-4">
      <div class="card-header bg-light py-3 px-4 border-bottom">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-eye text-primary me-2"></i>Updated Configuration Preview
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="p-3 border rounded bg-white shadow-xs">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-primary fs-6 font-mono" id="preview-badge-code">{{ $pso->code }}</span>
            <span class="badge {{ $pso->is_active ? 'bg-success' : 'bg-secondary' }}" id="preview-badge-status">
              {{ $pso->is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <h5 class="fw-bold text-dark mb-1 font-mono" id="preview-card-name">{{ $pso->code }}</h5>
          <div class="text-muted small mb-3" id="preview-card-desc">{{ $pso->description ?: 'No description provided.' }}</div>

          <div class="border-top pt-2 mt-2">
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Financial Year:</span>
              <span class="font-mono fw-semibold text-dark" id="preview-card-fy">{{ $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Assigned Operator:</span>
              <span class="fw-semibold text-dark" id="preview-card-operator">{{ $pso->operator_name }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Driver:</span>
              <span class="fw-semibold text-dark" id="preview-card-driver">{{ $pso->driver_name ?: '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Gadi / Vehicle:</span>
              <span class="font-mono fw-semibold text-dark" id="preview-card-gadi">{{ $pso->gadi_number ?: '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Helpers:</span>
              <span class="fw-semibold text-dark text-end" id="preview-card-helpers">{{ $pso->helpers_text ?: '—' }}</span>
            </div>
            <div class="border-top pt-2 mt-2">
              <div class="fw-semibold text-dark small mb-1">Configured Ranges:</div>
              <div id="preview-card-ranges-list" class="d-flex flex-column gap-1 small">
                @foreach($ranges as $r)
                  <div class="d-flex justify-content-between">
                    <code class="fw-bold">{{ $r['prefix'] }}</code>
                    <span class="font-mono">{{ sprintf('%02d', $r['start_no']) }} &ndash; {{ sprintf('%02d', $r['end_no']) }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- System Meta Card --}}
    <div class="card border bg-white shadow-sm mb-4">
      <div class="card-header bg-light py-3 px-4 border-bottom">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-info-square text-primary me-2"></i>System Record Meta
        </h6>
      </div>
      <div class="card-body p-4 small text-secondary">
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Record ID:</span>
          <span class="font-mono fw-bold">#{{ $pso->id }}</span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Created Date:</span>
          <span>{{ $pso->created_at ? $pso->created_at->format('d M Y, h:i A') : '—' }}</span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Last Updated:</span>
          <span>{{ $pso->updated_at ? $pso->updated_at->format('d M Y, h:i A') : '—' }}</span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Linked Bills:</span>
          <span class="badge bg-secondary font-mono">{{ $pso->bills_count }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Modal: Add New Prefix -->
<div class="modal fade" id="modal-quick-add-prefix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold"><i class="bi bi-tag-fill text-primary me-1"></i> Quick Add New Prefix</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-quick-add-prefix">
        @csrf
        <div class="modal-body p-4">
          <div id="quick-prefix-alert" class="alert alert-danger py-2 small d-none"></div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Code <span class="text-danger">*</span></label>
            <input type="text" name="prefix" id="quick-input-prefix" class="form-control font-mono fw-bold text-uppercase" 
                   placeholder="e.g. SC, IB, RET" maxlength="10" required>
            <div class="form-text">Will be automatically uppercase (e.g. SC).</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="quick-input-name" class="form-control" 
                   placeholder="e.g. School Counter, Instant Bill" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(Optional)</span></label>
            <textarea name="description" id="quick-input-desc" class="form-control" rows="2" 
                      placeholder="Optional notes for this prefix..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-save-quick-prefix">
            <i class="bi bi-plus-circle me-1"></i> Save & Use Prefix
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('series-rows-container');
  const btnAddTop = document.getElementById('btn-add-series-row-top');
  const btnAddBottom = document.getElementById('btn-add-series-row');
  const rowCountBadge = document.getElementById('series-row-count');

  const inputCode = document.getElementById('code');
  const inputOperator = document.getElementById('operator_name');
  const inputDriver = document.getElementById('driver_name');
  const inputGadi = document.getElementById('gadi_number');
  const inputHelper1 = document.getElementById('helper_1');
  const inputHelper2 = document.getElementById('helper_2');
  const inputHelper3 = document.getElementById('helper_3');
  const inputSpecials = document.getElementById('specials');
  const inputDesc = document.getElementById('description');
  const inputStatus = document.getElementById('is_active');
  const statusLabel = document.getElementById('status-label');

  const previewBadgeCode = document.getElementById('preview-badge-code');
  const previewBadgeStatus = document.getElementById('preview-badge-status');
  const previewCardName = document.getElementById('preview-card-name');
  const previewCardDesc = document.getElementById('preview-card-desc');
  const previewCardFy = document.getElementById('preview-card-fy');
  const previewCardOperator = document.getElementById('preview-card-operator');
  const previewCardDriver = document.getElementById('preview-card-driver');
  const previewCardGadi = document.getElementById('preview-card-gadi');
  const previewCardHelpers = document.getElementById('preview-card-helpers');
  const previewCardRangesList = document.getElementById('preview-card-ranges-list');
  const previewSequenceText = document.getElementById('preview-sequence-text');
  const previewCount = document.getElementById('preview-count');
  const previewFy = document.getElementById('preview-fy');
  const specialsBadgeContainer = document.getElementById('specials-badge-container');

  const activeFinancialYear = '{{ $pso->financial_year ?? $activeFinancialYear ?? "2026-2027" }}';
  let nextRowIndex = {{ count($ranges) }};
  let activeSelectTarget = null;

  function padZero(num) {
    return String(num).padStart(2, '0');
  }

  function getPrefixOptionsHtml() {
    const firstSelect = container.querySelector('.select-prefix');
    if (firstSelect) {
      return firstSelect.innerHTML;
    }
    return `<option value="">-- SELECT PREFIX --</option>
            <option value="CB" selected>CB – Counter Wholesale</option>
            <option value="RB">RB – Retail Walk-in</option>
            <option value="SC">SC – School Counter</option>
            <option value="__quick_add_prefix__" class="text-primary fw-bold">+ Add New Prefix...</option>`;
  }

  function addRow() {
    const idx = nextRowIndex++;
    const rowDiv = document.createElement('div');
    rowDiv.className = 'series-row p-2 px-3 border-start border-end border-bottom bg-white';
    rowDiv.setAttribute('data-row-index', idx);

    rowDiv.innerHTML = `
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <div class="d-md-none text-muted small fw-semibold mb-1">Bill Prefix <span class="text-danger">*</span></div>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-muted"><i class="bi bi-tag"></i></span>
            <select name="series[${idx}][prefix]" class="form-select form-select-sm font-mono fw-bold text-uppercase select-prefix" required>
              ${getPrefixOptionsHtml()}
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary btn-quick-add-trigger" title="Quick Add Prefix to Master">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
        </div>

        <div class="col-md-3">
          <div class="d-md-none text-muted small fw-semibold mb-1">Current Financial Year</div>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light text-primary"><i class="bi bi-calendar3"></i></span>
            <input type="text" name="series[${idx}][financial_year]" class="form-control form-control-sm font-mono fw-bold bg-light input-fy" 
                   value="${activeFinancialYear}" readonly>
            <span class="input-group-text bg-light text-muted" title="Active Financial Year (Read-only)"><i class="bi bi-lock-fill" style="font-size: 0.72rem;"></i></span>
          </div>
        </div>

        <div class="col-md-2">
          <div class="d-md-none text-muted small fw-semibold mb-1">Start Number <span class="text-danger">*</span></div>
          <input type="number" name="series[${idx}][start_no]" class="form-control form-control-sm font-mono text-center fw-semibold input-start" 
                 value="1" min="1" required placeholder="Start">
        </div>

        <div class="col-md-2">
          <div class="d-md-none text-muted small fw-semibold mb-1">End Number <span class="text-danger">*</span></div>
          <input type="number" name="series[${idx}][end_no]" class="form-control form-control-sm font-mono text-center fw-semibold input-end" 
                 value="10" min="1" required placeholder="End">
        </div>

        <div class="col-md-1 text-center">
          <div class="d-md-none text-muted small fw-semibold mb-1">Bills Count</div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono py-1 px-2 row-bills-badge">10</span>
        </div>

        <div class="col-md-1 text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" style="padding: 0.25rem 0.5rem;" title="Remove this range">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>
    `;

    container.appendChild(rowDiv);
    bindRowEvents(rowDiv);
    updateRowButtons();
    updatePreview();
  }

  function bindRowEvents(rowEl) {
    const sel = rowEl.querySelector('.select-prefix');
    const startIn = rowEl.querySelector('.input-start');
    const endIn = rowEl.querySelector('.input-end');
    const removeBtn = rowEl.querySelector('.btn-remove-row');
    const quickBtn = rowEl.querySelector('.btn-quick-add-trigger');

    if (sel) {
      sel.addEventListener('change', function() {
        if (this.value === '__quick_add_prefix__') {
          activeSelectTarget = this;
          openQuickAddModal();
        } else {
          updatePreview();
        }
      });
      sel.addEventListener('input', updatePreview);
    }

    if (startIn) {
      startIn.addEventListener('input', updatePreview);
      startIn.addEventListener('change', updatePreview);
    }

    if (endIn) {
      endIn.addEventListener('input', updatePreview);
      endIn.addEventListener('change', updatePreview);
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function() {
        rowEl.remove();
        updateRowButtons();
        updatePreview();
      });
    }

    if (quickBtn) {
      quickBtn.addEventListener('click', function() {
        activeSelectTarget = sel;
        openQuickAddModal();
      });
    }
  }

  function updateRowButtons() {
    const rows = container.querySelectorAll('.series-row');
    if (rowCountBadge) rowCountBadge.textContent = rows.length;

    rows.forEach((row, i) => {
      const btn = row.querySelector('.btn-remove-row');
      if (btn) {
        btn.style.display = (rows.length > 1) ? 'inline-block' : 'none';
      }
    });
  }

  function updatePreview() {
    const code = (inputCode ? inputCode.value.trim() : '') || '{{ $pso->code }}';
    const operator = (inputOperator ? inputOperator.value.trim() : '') || 'Not assigned';
    const isActive = inputStatus ? inputStatus.checked : true;

    previewBadgeCode.textContent = code;
    previewCardName.textContent = code;
    if (previewCardOperator) previewCardOperator.textContent = operator;
    if (previewCardDriver) {
      previewCardDriver.textContent = (inputDriver && inputDriver.value.trim()) || '—';
    }
    if (previewCardGadi) {
      previewCardGadi.textContent = (inputGadi && inputGadi.value.trim()) ? inputGadi.value.trim().toUpperCase() : '—';
    }
    if (previewCardHelpers) {
      const hlps = [];
      if (inputHelper1 && inputHelper1.value.trim()) hlps.push(inputHelper1.value.trim());
      if (inputHelper2 && inputHelper2.value.trim()) hlps.push(inputHelper2.value.trim());
      if (inputHelper3 && inputHelper3.value.trim()) hlps.push(inputHelper3.value.trim());
      previewCardHelpers.textContent = hlps.length ? hlps.join(', ') : '—';
    }
    if (previewCardFy) previewCardFy.textContent = activeFinancialYear;
    if (previewFy) previewFy.textContent = activeFinancialYear;

    const rows = container.querySelectorAll('.series-row');
    let totalBills = 0;
    const sequences = [];
    let rangesHtml = '';

    rows.forEach((row) => {
      const sel = row.querySelector('.select-prefix');
      const startIn = row.querySelector('.input-start');
      const endIn = row.querySelector('.input-end');

      let prefix = sel ? sel.value.trim().toUpperCase() : '{{ $pso->prefix }}';
      if (prefix === '__quick_add_prefix__' || !prefix) prefix = '{{ $pso->prefix }}';

      const start = parseInt(startIn ? startIn.value : 1) || 1;
      const end = parseInt(endIn ? endIn.value : 10) || 10;
      const count = Math.max(0, end - start + 1);

      const rowBadge = row.querySelector('.row-bills-badge');
      if (rowBadge) {
        rowBadge.textContent = count;
      }

      totalBills += count;
      sequences.push(`${prefix} ${padZero(start)} to ${prefix} ${padZero(end)}`);

      rangesHtml += `
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
          <code class="fw-bold fs-6">${prefix}</code>
          <span class="font-mono text-dark fw-semibold">${padZero(start)} &ndash; ${padZero(end)}</span>
          <span class="badge bg-light text-secondary border font-mono">${count} bills</span>
        </div>
      `;
    });

    if (previewSequenceText) {
      previewSequenceText.textContent = sequences.join(', ') || 'No range configured';
    }
    if (previewCount) {
      previewCount.textContent = totalBills;
    }
    if (previewCardRangesList) {
      previewCardRangesList.innerHTML = rangesHtml;
    }

    const desc = (inputDesc ? inputDesc.value.trim() : '') || 'No description provided.';
    if (previewCardDesc) previewCardDesc.textContent = desc;

    if (isActive) {
      previewBadgeStatus.textContent = 'Active';
      previewBadgeStatus.className = 'badge bg-success';
      if (statusLabel) {
        statusLabel.textContent = 'Active (Operational)';
        statusLabel.className = 'form-check-label ms-2 fw-semibold text-success';
      }
    } else {
      previewBadgeStatus.textContent = 'Inactive';
      previewBadgeStatus.className = 'badge bg-secondary';
      if (statusLabel) {
        statusLabel.textContent = 'Inactive (Disabled)';
        statusLabel.className = 'form-check-label ms-2 fw-semibold text-muted';
      }
    }

    // Update specials badges
    if (inputSpecials && specialsBadgeContainer) {
      const specialsVal = inputSpecials.value.trim();
      specialsBadgeContainer.innerHTML = '';
      if (specialsVal) {
        const items = specialsVal.split(',').map(s => s.trim()).filter(Boolean);
        items.forEach(item => {
          const span = document.createElement('span');
          span.className = 'badge bg-info text-dark font-mono';
          span.textContent = item;
          specialsBadgeContainer.appendChild(span);
        });
      }
    }
  }

  // Quick Add Prefix Modal Logic
  const quickModalEl = document.getElementById('modal-quick-add-prefix');
  const quickModal = quickModalEl ? new bootstrap.Modal(quickModalEl) : null;
  const quickForm = document.getElementById('form-quick-add-prefix');
  const quickAlert = document.getElementById('quick-prefix-alert');
  const quickPrefixIn = document.getElementById('quick-input-prefix');
  const quickNameIn = document.getElementById('quick-input-name');
  const quickDescIn = document.getElementById('quick-input-desc');

  function openQuickAddModal() {
    if (quickAlert) quickAlert.classList.add('d-none');
    if (quickForm) quickForm.reset();
    if (quickModal) quickModal.show();
  }

  if (quickForm) {
    quickForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const codeVal = quickPrefixIn.value.trim().toUpperCase();
      const nameVal = quickNameIn.value.trim();
      const descVal = quickDescIn ? quickDescIn.value.trim() : '';

      if (!codeVal || !nameVal) {
        if (quickAlert) {
          quickAlert.textContent = 'Prefix code and name are required.';
          quickAlert.classList.remove('d-none');
        }
        return;
      }

      const token = quickForm.querySelector('input[name="_token"]').value;
      const formData = new FormData();
      formData.append('_token', token);
      formData.append('prefix', codeVal);
      formData.append('name', nameVal);
      if (descVal) formData.append('description', descVal);

      fetch('{{ route("admin.prefix.store") }}', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success || data.prefix) {
          const newPfx = data.prefix ? data.prefix.prefix : codeVal;
          const newName = data.prefix ? data.prefix.name : nameVal;

          const allSelects = container.querySelectorAll('.select-prefix');
          allSelects.forEach(sel => {
            const opt = document.createElement('option');
            opt.value = newPfx;
            opt.textContent = `${newPfx} – ${newName}`;
            const lastOpt = sel.querySelector('option[value="__quick_add_prefix__"]');
            if (lastOpt) {
              sel.insertBefore(opt, lastOpt);
            } else {
              sel.appendChild(opt);
            }
          });

          if (activeSelectTarget) {
            activeSelectTarget.value = newPfx;
          }

          if (quickModal) quickModal.hide();
          updatePreview();
        } else {
          if (quickAlert) {
            quickAlert.textContent = data.message || 'Failed to save prefix.';
            quickAlert.classList.remove('d-none');
          }
        }
      })
      .catch(err => {
        if (quickAlert) {
          quickAlert.textContent = 'Error connecting to server. Please try again.';
          quickAlert.classList.remove('d-none');
        }
      });
    });
  }

  // Bind existing rows
  container.querySelectorAll('.series-row').forEach(row => {
    bindRowEvents(row);
  });

  if (btnAddTop) btnAddTop.addEventListener('click', addRow);
  if (btnAddBottom) btnAddBottom.addEventListener('click', addRow);

  [inputOperator, inputDriver, inputGadi, inputHelper1, inputHelper2, inputHelper3, inputSpecials, inputDesc].forEach(el => {
    if (el) {
      el.addEventListener('input', updatePreview);
      el.addEventListener('change', updatePreview);
    }
  });
  if (inputStatus) inputStatus.addEventListener('change', updatePreview);

  updateRowButtons();
  updatePreview();
});
</script>
@endsection
