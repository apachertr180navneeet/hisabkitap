@extends('layouts.app')

@section('title', 'Sales Person Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Sales Person Master</h4>
    <p class="text-muted mb-0">Manage field sales representatives and link them to their designated bill prefix series.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('admin.prefix.index') }}" class="btn btn-outline-primary">
      <i class="bi bi-tag me-1"></i> Prefix Master
    </a>
    @if($currentUser->hasPermission('can_configure_pso'))
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-salesperson">
      <i class="bi bi-person-plus-fill me-1"></i> Add New Sales Person
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
          <i class="bi bi-people-fill text-primary fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Total Sales Persons</div>
          <div class="fw-bold fs-5 font-mono">{{ $stats['total'] }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-person-check-fill text-success fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Active Representatives</div>
          <div class="fw-bold fs-5 font-mono text-success">{{ $stats['active'] }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-tag-fill text-info fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Prefixes Linked</div>
          <div class="fw-bold fs-5 font-mono text-info">{{ $stats['coveredPrefixes'] }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border p-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-person-dash text-secondary fs-4"></i>
        </div>
        <div>
          <div class="text-muted" style="font-size: 0.78rem;">Inactive</div>
          <div class="fw-bold fs-5 font-mono text-secondary">{{ $stats['inactive'] }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Salesperson Data Table --}}
<div class="erp-table-container mb-4">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark"><i class="bi bi-person-badge-fill text-primary me-1"></i> Sales Representatives Registry</span>
    <span class="badge bg-primary">{{ $salespersons->count() }} Total Representatives</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>Code</th>
          <th>Sales Person</th>
          <th>Linked Bill Prefix</th>
          <th>Contact &amp; Email</th>
          <th>Territory / Area</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($salespersons as $sp)
          <tr>
            <td><span class="badge bg-primary">{{ $sp->code }}</span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.78rem;">
                  {{ strtoupper(substr($sp->name, 0, 2)) }}
                </div>
                <div>
                  <div class="fw-semibold text-dark">{{ $sp->name }}</div>
                  <small class="text-muted" style="font-size: 0.72rem;">Field Representative</small>
                </div>
              </div>
            </td>
            <td>
              @if($sp->prefix)
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-info text-dark font-mono px-2 py-1 fs-6">
                    {{ $sp->prefix->prefix }}
                  </span>
                  <div class="lh-sm">
                    <div class="fw-semibold text-dark small">{{ $sp->prefix->name }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">{{ $sp->prefix->code }}</small>
                  </div>
                </div>
              @elseif(!empty($sp->prefix_code))
                <span class="badge bg-info text-dark font-mono px-2 py-1 fs-6">
                  {{ $sp->prefix_code }}
                </span>
              @else
                <span class="badge bg-light text-muted border border-dashed">
                  <i class="bi bi-link-45deg me-1"></i> Unassigned
                </span>
              @endif
            </td>
            <td>
              <div>
                @if($sp->phone)
                  <div><i class="bi bi-telephone text-primary me-1" style="font-size: 0.78rem;"></i>{{ $sp->phone }}</div>
                @endif
                @if($sp->email)
                  <small class="text-muted"><i class="bi bi-envelope text-secondary me-1" style="font-size: 0.72rem;"></i>{{ $sp->email }}</small>
                @endif
                @if(!$sp->phone && !$sp->email)
                  <span class="text-muted small">—</span>
                @endif
              </div>
            </td>
            <td>
              @if($sp->area)
                <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $sp->area }}</span>
              @else
                <span class="text-muted small">General Counter</span>
              @endif
            </td>
            <td>
              <span class="badge {{ $sp->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $sp->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-end">
              @if($currentUser->hasPermission('can_configure_pso'))
                {{-- Edit Button --}}
                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                  data-bs-toggle="modal" data-bs-target="#modal-edit-sp-{{ $sp->id }}">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>

                {{-- Toggle Status --}}
                <form action="{{ route('admin.salespersons.toggle', $sp->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary me-1">
                    <i class="bi {{ $sp->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                    {{ $sp->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </form>

                {{-- Delete Button --}}
                <form action="{{ route('admin.salespersons.delete', $sp->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Are you sure you want to delete \'{{ $sp->name }}\'?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
              @else
                <span class="text-muted small">Read Only</span>
              @endif
            </td>
          </tr>

          {{-- Edit Salesperson Modal --}}
          @if($currentUser->hasPermission('can_configure_pso'))
          <div class="modal fade" id="modal-edit-sp-{{ $sp->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-1"></i> Edit Sales Person — {{ $sp->code }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.salespersons.update', $sp->id) }}" method="POST">
                  @csrf
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                      <input type="text" name="name" class="form-control" value="{{ $sp->name }}" required>
                    </div>

                    {{-- Linked Prefix dropdown --}}
                    <div class="mb-3">
                      <label class="form-label fw-semibold"><i class="bi bi-tag-fill text-primary me-1"></i> Linked Bill Prefix</label>
                      <select name="prefix_id" class="form-select">
                        <option value="">-- No Prefix Linked (Optional) --</option>
                        @foreach($allPrefixes as $pfxOption)
                          <option value="{{ $pfxOption->id }}" {{ $sp->prefix_id == $pfxOption->id ? 'selected' : '' }}>
                            {{ $pfxOption->prefix }} — {{ $pfxOption->name }} ({{ $pfxOption->code }})
                          </option>
                        @endforeach
                      </select>
                      <div class="form-text">Select the bill prefix series handled by this sales representative.</div>
                    </div>

                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ $sp->phone }}" placeholder="e.g. 9876543210">
                      </div>
                      <div class="col-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ $sp->email }}" placeholder="e.g. rep@hisabkitap.in">
                      </div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Assigned Area / Territory</label>
                      <input type="text" name="area" class="form-control" value="{{ $sp->area }}" placeholder="e.g. Wholesale Zone, Sector 5">
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Representative</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          @endif

        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              <i class="bi bi-people fs-3 d-block mb-1 text-primary"></i>
              No sales representatives added yet. Click <strong>"+ Add New Sales Person"</strong> above to create your first entry.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Add New Salesperson Modal --}}
@if($currentUser->hasPermission('can_configure_pso'))
<div class="modal fade" id="modal-add-salesperson" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-primary me-1"></i> Add New Sales Person</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.salespersons.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Rajesh Kumar" required>
          </div>

          {{-- Linked Prefix dropdown --}}
          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="bi bi-tag-fill text-primary me-1"></i> Linked Bill Prefix</label>
            <select name="prefix_id" class="form-select">
              <option value="">-- No Prefix Linked (Optional) --</option>
              @foreach($allPrefixes as $pfxOption)
                <option value="{{ $pfxOption->id }}">
                  {{ $pfxOption->prefix }} — {{ $pfxOption->name }} ({{ $pfxOption->code }})
                </option>
              @endforeach
            </select>
            <div class="form-text">Select the bill prefix series handled by this sales representative.</div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Phone Number</label>
              <input type="text" name="phone" class="form-control" placeholder="e.g. 9876543210">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="e.g. rajesh@hisabkitap.in">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Assigned Area / Territory</label>
            <input type="text" name="area" class="form-control" placeholder="e.g. Wholesale Zone, Counter 1">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Save Sales Person</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- Quick Reference Cards --}}
<div class="row g-3">
  <div class="col-md-6">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-diagram-3-fill me-1"></i> Sales Person &amp; Prefix Linkage</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        Each sales representative has an assigned bill prefix (stored as <code>prefix_id</code>).
        When bills or credit recoveries of that series are processed, the system automatically routes them to the appropriate sales person.
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1"><i class="bi bi-shield-check me-1"></i> Synchronized Master Management</div>
      <div class="text-muted" style="font-size: 0.82rem;">
        You can assign a prefix directly when creating or editing a Sales Person here, OR map the representative inside the <a href="{{ route('admin.prefix.index') }}">Prefix Master</a>.
      </div>
    </div>
  </div>
</div>
@endsection
