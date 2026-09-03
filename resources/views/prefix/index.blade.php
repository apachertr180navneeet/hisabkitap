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
          @endphp
          <tr>
            <td><span class="badge bg-primary">{{ $pfx->code }}</span></td>
            <td><code class="fs-6 fw-bold">{{ $pfx->prefix }}</code></td>
            <td><strong>{{ $pfx->name }}</strong></td>
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
                      <input type="text" name="prefix" class="form-control text-uppercase" value="{{ $pfx->prefix }}" maxlength="10" required>
                      <div class="form-text">Must be unique across all prefixes.</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Prefix Name <span class="text-danger">*</span></label>
                      <input type="text" name="name" class="form-control" value="{{ $pfx->name }}" required>
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
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-tag fs-3 d-block mb-1 text-primary"></i>
              No prefixes configured yet. Click <strong>"+ Add New Prefix"</strong> above to create your first prefix entry.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Quick Reference Cards --}}
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-lightbulb me-1"></i> Prefix Master Registry</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        The Prefix Master maintains a centralized registry of all bill prefix codes (e.g. <code>CB</code>, <code>RB</code>, <code>ITC</code>).
        PSO configurations reference these prefixes to define their bill number ranges.
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
      <div class="fw-bold text-primary mb-1"><i class="bi bi-shield-check me-1"></i> Safety Rules</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        <ul class="mb-0 ps-3">
          <li>Prefixes assigned to active PSO configs <strong>cannot be deleted</strong></li>
          <li>All prefix values are stored in <strong>UPPERCASE</strong></li>
          <li>Each prefix code must be <strong>unique</strong> across the system</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
