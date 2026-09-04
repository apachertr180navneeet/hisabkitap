@extends('layouts.app')

@section('title', 'Configure New PSO Series')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <a href="{{ route('admin.pso.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
        <i class="bi bi-arrow-left me-1"></i> Back to PSO List
      </a>
      <span class="text-muted">/</span>
      <span class="text-muted small">Series Configuration</span>
    </div>
    <h4 class="fw-bold mb-1">Configure New PSO Series</h4>
    <p class="text-muted mb-0">Define counter sequence rules, bill prefixes, starting/ending serial ranges, and special series.</p>
  </div>
  <div>
    <a href="{{ route('admin.pso.index') }}" class="btn btn-outline-secondary me-2">
      <i class="bi bi-x-circle me-1"></i> Cancel
    </a>
  </div>
</div>

<div class="row g-4">
  {{-- Form Column --}}
  <div class="col-lg-8">
    <form action="{{ route('admin.pso.store') }}" method="POST" id="form-pso-create">
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
                PSO Identifier Code <span class="badge bg-secondary ms-1 font-mono">Auto-generated</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light font-mono"><i class="bi bi-hash"></i></span>
                <input type="text" name="code" id="code" class="form-control font-mono fw-bold bg-light" 
                       value="{{ $suggestedCode ?? 'PSO-1' }}" readonly>
                <span class="input-group-text bg-light text-muted" title="Read-only auto-generated identifier"><i class="bi bi-lock-fill"></i></span>
              </div>
              <div class="form-text small text-muted"><i class="bi bi-info-circle me-1"></i>System sequence code (Read-only).</div>
            </div>

            <div class="col-md-5">
              <label class="form-label fw-semibold" for="operator_name">
                Assigned Operator / Staff <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="operator_name" id="operator_name" list="operator-options" 
                       class="form-control @error('operator_name') is-invalid @enderror" 
                       value="{{ old('operator_name', $currentUser['name'] ?? 'Big Bite') }}" 
                       placeholder="e.g. Big Bite or Ramesh Sharma" required>
                <datalist id="operator-options">
                  @foreach($operators as $op)
                    <option value="{{ $op }}">{{ $op }}</option>
                  @endforeach
                </datalist>
              </div>
              <div class="form-text small">Staff responsible for physical verification and cash handover.</div>
              @error('operator_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold" for="is_active">Status</label>
              <div class="form-check form-switch pt-1">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                <label class="form-check-label ms-2 fw-semibold text-success" for="is_active" id="status-label">
                  Active (Operational)
                </label>
              </div>
              <div class="form-text small">Include in daily verification.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Card 2: Bill Prefix, Financial Year & Range (All in One Line) --}}
      <div class="card border bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-card-checklist text-primary me-2"></i>Bill Series & Sequence Range
          </h6>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            {{-- 1. Bill Prefix Drop Down --}}
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="prefix">
                Bill Prefix <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                <select name="prefix" id="prefix" class="form-select font-mono fw-bold text-uppercase @error('prefix') is-invalid @enderror" required>
                  <option value="">-- Select Prefix --</option>
                  @foreach($prefixes as $pfx)
                    <option value="{{ $pfx->prefix }}" {{ old('prefix', 'CB') == $pfx->prefix ? 'selected' : '' }}>
                      {{ $pfx->prefix }} &ndash; {{ $pfx->name }}
                    </option>
                  @endforeach
                  @if($prefixes->isEmpty())
                    <option value="CB" selected>CB &ndash; Counter Wholesale</option>
                    <option value="RB">RB &ndash; Retail Walk-in</option>
                    <option value="SC">SC &ndash; School Counter</option>
                  @endif
                </select>
              </div>
              <div class="form-text small">Select from Prefix Master.</div>
              @error('prefix')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- 2. Readonly Current Financial Year --}}
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="financial_year">
                Current Financial Year <span class="badge bg-secondary ms-1 font-mono">Current</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-calendar-range text-primary"></i></span>
                <input type="text" name="financial_year" id="financial_year" class="form-control font-mono fw-bold bg-light" 
                       value="{{ old('financial_year', $activeFinancialYear ?? '2026-2027') }}" readonly>
                <span class="input-group-text bg-light text-muted" title="Active Financial Year (Read-only)"><i class="bi bi-lock-fill"></i></span>
              </div>
              <div class="form-text small text-muted"><i class="bi bi-shield-check me-1"></i>System active FY period.</div>
              @error('financial_year')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- 3. Start Number --}}
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="start_no">
                Start Number <span class="text-danger">*</span>
              </label>
              <input type="number" name="start_no" id="start_no" class="form-control font-mono @error('start_no') is-invalid @enderror" 
                     value="{{ old('start_no', 1) }}" min="1" required>
              <div class="form-text small">Initial sequential serial (e.g. 1, 11).</div>
              @error('start_no')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- 4. End Number --}}
            <div class="col-md-3">
              <label class="form-label fw-semibold" for="end_no">
                End Number <span class="text-danger">*</span>
              </label>
              <input type="number" name="end_no" id="end_no" class="form-control font-mono @error('end_no') is-invalid @enderror" 
                     value="{{ old('end_no', 10) }}" min="1" required>
              <div class="form-text small">Final expected serial (inclusive).</div>
              @error('end_no')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="alert alert-light border mt-3 p-3 mb-0 rounded">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill text-primary"></i>
                <div class="small">
                  <strong>Sequence Preview:</strong> 
                  <span id="preview-sequence-text" class="font-mono text-dark fw-bold">CB 01 to CB 10</span> 
                  (<span id="preview-count">10</span> daily bills verified per sheet).
                </div>
              </div>
              <span class="badge bg-light text-dark border font-mono">
                <i class="bi bi-calendar-range text-primary me-1"></i>FY: <span id="preview-fy">{{ $activeFinancialYear ?? '2026-2027' }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- Card 3: Special Bills & Description Notes --}}
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
              <input type="text" name="specials" id="specials" class="form-control font-mono @error('specials') is-invalid @enderror" 
                     value="{{ old('specials') }}" placeholder="e.g. ITC 01, ITC 03, SPL 05">
              <div class="form-text small">Enter specific non-standard or corporate bills tracked under this PSO series separated by comma.</div>
              <div id="specials-badge-container" class="mt-2 d-flex flex-wrap gap-1"></div>
              @error('specials')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold" for="description">
                Notes & Additional Description <span class="text-muted fw-normal">(Optional)</span>
              </label>
              <textarea name="description" id="description" rows="2" class="form-control" 
                        placeholder="e.g. Handles institutional delivery sequence CB 01 to CB 10.">{{ old('description') }}</textarea>
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
          <i class="bi bi-check-circle-fill me-1"></i> Save PSO Configuration
        </button>
      </div>
    </form>
  </div>

  {{-- Right Information / Reference Column --}}
  <div class="col-lg-4">
    {{-- Live Card Preview --}}
    <div class="card border bg-white shadow-sm mb-4">
      <div class="card-header bg-light py-3 px-4 border-bottom">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-eye text-primary me-2"></i>Configuration Live Card
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="p-3 border rounded bg-white shadow-xs">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-primary fs-6 font-mono" id="preview-badge-code">{{ $suggestedCode ?? 'PSO-1' }}</span>
            <span class="badge bg-success" id="preview-badge-status">Active</span>
          </div>
          <h5 class="fw-bold text-dark mb-1" id="preview-card-name">{{ $suggestedCode ?? 'PSO-1' }}</h5>
          <div class="text-muted small mb-3" id="preview-card-desc">Series description preview will appear here.</div>

          <div class="border-top pt-2 mt-2">
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Prefix:</span>
              <code class="fw-bold text-uppercase" id="preview-card-prefix">CB</code>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Financial Year:</span>
              <span class="font-mono fw-semibold text-dark" id="preview-card-fy">{{ $activeFinancialYear ?? '2026-2027' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Serial Range:</span>
              <span class="font-mono fw-semibold" id="preview-card-range">01 &ndash; 10</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Assigned Operator:</span>
              <span class="fw-semibold text-dark" id="preview-card-operator">Big Bite</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Guidance Card --}}
    <div class="card border bg-white shadow-sm mb-4">
      <div class="card-header bg-light py-3 px-4 border-bottom">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-lightbulb text-warning me-2"></i>PSO Series Guidelines
        </h6>
      </div>
      <div class="card-body p-4 small text-secondary">
        <ul class="ps-3 mb-0 d-flex flex-column gap-2">
          <li><strong>Auto Code:</strong> PSO Identifier code is automatically assigned sequentially (read-only).</li>
          <li><strong>Prefix:</strong> Select bill serial prefix directly from Prefix Master dropdown.</li>
          <li><strong>Financial Year:</strong> Locked to the current active financial year session.</li>
          <li><strong>Serial Range:</strong> Typically 10 bills per booklet (e.g., <code>1-10</code>, <code>11-20</code>, <code>21-30</code>).</li>
          <li><strong>Special Bills:</strong> Non-sequential corporate accounts, e.g. <code>ITC 01, ITC 03</code>.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const inputCode = document.getElementById('code');
  const inputPrefix = document.getElementById('prefix');
  const inputFy = document.getElementById('financial_year');
  const inputStart = document.getElementById('start_no');
  const inputEnd = document.getElementById('end_no');
  const inputOperator = document.getElementById('operator_name');
  const inputSpecials = document.getElementById('specials');
  const inputDesc = document.getElementById('description');
  const inputStatus = document.getElementById('is_active');
  const statusLabel = document.getElementById('status-label');

  const previewBadgeCode = document.getElementById('preview-badge-code');
  const previewBadgeStatus = document.getElementById('preview-badge-status');
  const previewCardName = document.getElementById('preview-card-name');
  const previewCardDesc = document.getElementById('preview-card-desc');
  const previewCardPrefix = document.getElementById('preview-card-prefix');
  const previewCardFy = document.getElementById('preview-card-fy');
  const previewCardRange = document.getElementById('preview-card-range');
  const previewCardOperator = document.getElementById('preview-card-operator');
  const previewSequenceText = document.getElementById('preview-sequence-text');
  const previewCount = document.getElementById('preview-count');
  const previewFy = document.getElementById('preview-fy');
  const specialsBadgeContainer = document.getElementById('specials-badge-container');

  function padZero(num) {
    return String(num).padStart(2, '0');
  }

  function updatePreview() {
    const code = (inputCode ? inputCode.value.trim() : '') || 'PSO-X';
    const prefix = (inputPrefix ? inputPrefix.value.trim() : 'CB').toUpperCase() || 'CB';
    const fy = (inputFy ? inputFy.value.trim() : '') || '{{ $activeFinancialYear ?? "2026-2027" }}';
    const start = parseInt(inputStart.value) || 1;
    const end = parseInt(inputEnd.value) || 10;
    const operator = inputOperator.value.trim() || 'Not assigned';
    const desc = inputDesc.value.trim() || `Counter sequence ${prefix} ${padZero(start)} to ${prefix} ${padZero(end)}.`;
    const isActive = inputStatus.checked;

    previewBadgeCode.textContent = code;
    previewCardName.textContent = code;
    previewCardPrefix.textContent = prefix;
    if (previewCardFy) previewCardFy.textContent = fy;
    if (previewFy) previewFy.textContent = fy;
    previewCardRange.textContent = `${padZero(start)} – ${padZero(end)}`;
    previewCardOperator.textContent = operator;
    previewCardDesc.textContent = desc;

    const count = Math.max(0, end - start + 1);
    previewSequenceText.textContent = `${prefix} ${padZero(start)} to ${prefix} ${padZero(end)}`;
    previewCount.textContent = count;

    if (isActive) {
      previewBadgeStatus.textContent = 'Active';
      previewBadgeStatus.className = 'badge bg-success';
      statusLabel.textContent = 'Active (Operational)';
      statusLabel.className = 'form-check-label ms-2 fw-semibold text-success';
    } else {
      previewBadgeStatus.textContent = 'Inactive';
      previewBadgeStatus.className = 'badge bg-secondary';
      statusLabel.textContent = 'Inactive (Disabled)';
      statusLabel.className = 'form-check-label ms-2 fw-semibold text-muted';
    }

    // Update specials badges
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

  [inputPrefix, inputStart, inputEnd, inputOperator, inputSpecials, inputDesc, inputFy].forEach(el => {
    if (el) {
      el.addEventListener('input', updatePreview);
      el.addEventListener('change', updatePreview);
    }
  });
  if (inputStatus) inputStatus.addEventListener('change', updatePreview);

  updatePreview();
});
</script>
@endsection
