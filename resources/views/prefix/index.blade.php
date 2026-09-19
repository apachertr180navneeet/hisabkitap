@extends('layouts.app')

@section('title', 'Prefix Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Prefix Master</h4>
    <p class="text-muted mb-0">Manage bill prefix codes, series identity, and assigned field sales representatives.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('admin.salespersons.index') }}" class="btn btn-outline-primary">
      <i class="bi bi-people me-1"></i> Sales Person Master
    </a>
    @if($currentUser->hasPermission('can_configure_pso'))
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-prefix">
      <i class="bi bi-plus-circle me-1"></i> Add New Prefix
    </button>
    @endif
  </div>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-hash text-primary fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Total Prefixes</div>
          <div class="fw-bold fs-5 font-mono">{{ $prefixes->count() }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-check-circle text-success fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Active Prefixes</div>
          <div class="fw-bold fs-5 font-mono text-success">{{ $prefixes->where('is_active', true)->count() }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-person-badge text-warning fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Linked to Sales Person</div>
          <div class="fw-bold fs-5 font-mono text-dark">{{ $prefixes->filter(fn($p) => $p->salesperson !== null)->count() }} <span class="text-muted fs-6 fw-normal">/ {{ $prefixes->count() }}</span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-link-45deg text-info fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Used in PSO Series</div>
          <div class="fw-bold fs-5 font-mono text-info">{{ $prefixes->filter(fn($p) => \App\Models\PsoConfig::where('prefix', $p->prefix)->exists())->count() }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Prefix Data Table --}}
<div class="erp-table-container mb-4">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark"><i class="bi bi-tag-fill text-primary me-1"></i> Prefix Master Registry</span>
    <span class="badge bg-primary">{{ $prefixes->count() }} Total Entries</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>Code</th>
          <th>Prefix</th>
          <th>Name</th>
          <th>Bill Format & Sample</th>
          <th>Linked Sales Person</th>
          <th>Description</th>
          <th>PSO Usage</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($prefixes as $pfx)
          @php
            $psoUsageCount = \App\Models\PsoConfig::where('prefix', $pfx->prefix)->count();
            $linkedSp = $pfx->salesperson;
            $format = $pfx->bill_format ?: '{PREFIX}/{FY}/{NO}';
            $sampleNo = $pfx->formatBillNo(1, '26-27');
          @endphp
          <tr>
            <td><span class="badge bg-primary">{{ $pfx->code }}</span></td>
            <td><code class="fs-6 fw-bold">{{ $pfx->prefix }}</code></td>
            <td><strong>{{ $pfx->name }}</strong></td>
            <td>
              <div class="d-flex flex-column gap-1">
                <span class="badge bg-light text-primary border font-mono fw-bold" style="font-size: 0.85rem; width: fit-content;">
                  <i class="bi bi-receipt me-1 text-secondary"></i>{{ $sampleNo }}
                </span>
                <small class="text-muted font-mono" style="font-size: 0.68rem;">{{ $format }}</small>
              </div>
            </td>
            <td>
              @if($linkedSp)
                <div class="d-flex align-items-center gap-2">
                  <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.72rem;">
                    {{ strtoupper(substr($linkedSp->name, 0, 2)) }}
                  </div>
                  <div>
                    <div class="fw-semibold text-dark lh-sm" style="font-size: 0.82rem;">{{ $linkedSp->name }}</div>
                    <small class="text-muted font-mono" style="font-size: 0.7rem;">
                      {{ $linkedSp->code }} {{ $linkedSp->phone ? '• ' . $linkedSp->phone : '' }}
                    </small>
                  </div>
                </div>
              @elseif(!empty($pfx->salesperson_name))
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-person text-secondary me-1"></i>{{ $pfx->salesperson_name }}
                </span>
              @else
                <span class="badge bg-light text-muted border border-dashed">
                  <i class="bi bi-person-x me-1"></i> Unassigned
                </span>
              @endif
            </td>
            <td>
              @if($pfx->description)
                <span class="text-muted small">{{ Str::limit($pfx->description, 45) }}</span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              @if($psoUsageCount > 0)
                <span class="badge bg-info text-dark">{{ $psoUsageCount }} PSO{{ $psoUsageCount > 1 ? 's' : '' }}</span>
              @else
                <span class="text-muted small">Not used</span>
              @endif
            </td>
            <td>
              <span class="badge {{ $pfx->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $pfx->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-end">
              @if($currentUser->hasPermission('can_configure_pso'))
                {{-- Edit Button --}}
                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                  data-bs-toggle="modal" data-bs-target="#modal-edit-prefix-{{ $pfx->id }}">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>

                {{-- Toggle Status --}}
                <form action="{{ route('admin.prefix.toggle', $pfx->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary me-1">
                    <i class="bi {{ $pfx->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                    {{ $pfx->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </form>

                {{-- Delete Button --}}
                @if($psoUsageCount === 0)
                <form action="{{ route('admin.prefix.delete', $pfx->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Are you sure you want to permanently delete prefix \'{{ $pfx->prefix }}\'?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
                @else
                <button type="button" class="btn btn-sm btn-outline-danger" disabled
                  title="Cannot delete — used by {{ $psoUsageCount }} PSO config(s)">
                  <i class="bi bi-trash3"></i>
                </button>
                @endif
              @else
                <span class="text-muted small">Read Only</span>
              @endif
            </td>
          </tr>

          {{-- Inline Edit Modal for each prefix --}}
          @if($currentUser->hasPermission('can_configure_pso'))
          <div class="modal fade" id="modal-edit-prefix-{{ $pfx->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-1"></i> Edit Prefix — {{ $pfx->code }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.prefix.update', $pfx->id) }}" method="POST">
                  @csrf
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Prefix Code <span class="text-danger">*</span></label>
                      <input type="text" name="prefix" id="edit_prefix_{{ $pfx->id }}" class="form-control" value="{{ $pfx->prefix }}" maxlength="10" required oninput="updatePrefixPreview('{{ $pfx->id }}')">
                      <div class="form-text">Must be unique across all prefixes (e.g. Sc, RB, HS, PG, I, AT).</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Prefix Name <span class="text-danger">*</span></label>
                      <input type="text" name="name" class="form-control" value="{{ $pfx->name }}" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold"><i class="bi bi-file-earmark-text text-primary me-1"></i> Bill Number Format Pattern</label>
                      <select name="bill_format" id="edit_format_{{ $pfx->id }}" class="form-select font-mono" onchange="updatePrefixPreview('{{ $pfx->id }}')">
                        @foreach(\App\Models\Prefix::FORMAT_PRESETS as $fmtKey => $fmtLabel)
                          <option value="{{ $fmtKey }}" {{ ($pfx->bill_format ?: '{PREFIX}/{FY}/{NO}') === $fmtKey ? 'selected' : '' }}>
                            {{ $fmtLabel }}
                          </option>
                        @endforeach
                      </select>
                      <div class="p-2 mt-2 bg-light rounded border d-flex align-items-center justify-content-between">
                        <small class="text-muted"><i class="bi bi-eye me-1"></i> Live Preview:</small>
                        <span id="preview_edit_{{ $pfx->id }}" class="badge bg-primary font-mono fs-6">{{ $sampleNo }}</span>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold"><i class="bi bi-person-badge text-primary me-1"></i> Linked Sales Person</label>
                      <select name="salesperson_id" class="form-select">
                        <option value="">-- No Sales Person Assigned --</option>
                        @foreach($salespersons as $sp)
                          <option value="{{ $sp->id }}" {{ ($linkedSp && $linkedSp->id == $sp->id) || ($sp->prefix_id == $pfx->id) ? 'selected' : '' }}>
                            {{ $sp->name }} [{{ $sp->code }}]{{ $sp->phone ? ' - ' . $sp->phone : '' }}
                          </option>
                        @endforeach
                      </select>
                      <div class="form-text">Assigned representative for this series' customer accounts & credit recoveries.</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Description</label>
                      <textarea name="description" class="form-control" rows="2" placeholder="Optional description...">{{ $pfx->description }}</textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Prefix</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          @endif

        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-4">
              <i class="bi bi-tag fs-3 d-block mb-1 text-primary"></i>
              No prefixes configured yet. Click <strong>"+ Add New Prefix"</strong> above to create your first prefix entry.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Add New Prefix Modal --}}
@if($currentUser->hasPermission('can_configure_pso'))
<div class="modal fade" id="modal-add-prefix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-1"></i> Add New Prefix</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.prefix.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Code <span class="text-danger">*</span></label>
            <input type="text" name="prefix" id="add_prefix_code" class="form-control font-mono" placeholder="e.g. Sc, RB, HS, PG, AT" maxlength="10" required oninput="updateAddPreview()">
            <div class="form-text">Short prefix symbol (e.g. Sc, RB, HS, PG, I, AT, CB).</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Standard Counter, Retail Bill" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="bi bi-file-earmark-text text-primary me-1"></i> Bill Number Format Pattern</label>
            <select name="bill_format" id="add_bill_format" class="form-select font-mono" onchange="updateAddPreview()">
              @foreach(\App\Models\Prefix::FORMAT_PRESETS as $fmtKey => $fmtLabel)
                <option value="{{ $fmtKey }}">{{ $fmtLabel }}</option>
              @endforeach
            </select>
            <div class="p-2 mt-2 bg-light rounded border d-flex align-items-center justify-content-between">
              <small class="text-muted"><i class="bi bi-eye me-1"></i> Live Preview:</small>
              <span id="preview_add_sample" class="badge bg-primary font-mono fs-6">Sc/26-27/1</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="bi bi-person-badge text-primary me-1"></i> Linked Sales Person</label>
            <select name="salesperson_id" class="form-select">
              <option value="">-- No Sales Person Assigned --</option>
              @foreach($salespersons as $sp)
                <option value="{{ $sp->id }}">{{ $sp->name }} [{{ $sp->code }}]{{ $sp->phone ? ' - ' . $sp->phone : '' }}</option>
              @endforeach
            </select>
            <div class="form-text">Assigned representative for credit tracking and daybook reconciliation.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Optional notes or description..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Create Prefix</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- Quick Reference Cards --}}
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-lightbulb me-1"></i> Prefix Master & Formats</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        The Prefix Master maintains centralized registry of bill prefixes and format patterns (e.g. <code>Sc/26-27/1</code>, <code>HS/1/26-27</code>, <code>26-27/PG/1</code>, <code>I/26-27/000001</code>).
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-person-check-fill me-1"></i> Sales Person Linkage</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        Assigning a field Sales Representative directly to a Prefix connects bill generation, Excel daybook imports, and salesman credit registers automatically.
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-shield-check me-1"></i> Dynamic Parsing Rules</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        <ul class="mb-0 ps-3">
          <li>Supports all formats and financial year arrangements seamlessly</li>
          <li>Prefixes assigned to active PSO configs <strong>cannot be deleted</strong></li>
          <li>Each prefix code is unique and matches case-insensitively in imports</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
function formatBillSample(prefix, template, fy, num) {
  prefix = prefix || 'Sc';
  template = template || '{PREFIX}/{FY}/{NO}';
  fy = fy || '26-27';
  num = num || 1;

  let padMatch = template.match(/\{(0+)\}/);
  if (padMatch) {
    let padLen = padMatch[1].length;
    let padded = String(num).padStart(padLen, '0');
    template = template.replace(padMatch[0], padded);
  } else {
    template = template.replace(/\{NO\}/gi, num);
  }

  template = template.replace(/\{PREFIX\}/gi, prefix);
  template = template.replace(/\{FY\}/gi, fy);
  return template;
}

function updateAddPreview() {
  const pfxInput = document.getElementById('add_prefix_code');
  const fmtSelect = document.getElementById('add_bill_format');
  const previewBadge = document.getElementById('preview_add_sample');
  if (pfxInput && fmtSelect && previewBadge) {
    const val = pfxInput.value.trim() || 'Sc';
    const fmt = fmtSelect.value;
    previewBadge.textContent = formatBillSample(val, fmt, '26-27', 1);
  }
}

function updatePrefixPreview(id) {
  const pfxInput = document.getElementById('edit_prefix_' + id);
  const fmtSelect = document.getElementById('edit_format_' + id);
  const previewBadge = document.getElementById('preview_edit_' + id);
  if (pfxInput && fmtSelect && previewBadge) {
    const val = pfxInput.value.trim() || 'CB';
    const fmt = fmtSelect.value;
    previewBadge.textContent = formatBillSample(val, fmt, '26-27', 1);
  }
}
</script>
@endsection

