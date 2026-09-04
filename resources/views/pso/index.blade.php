@extends('layouts.app')

@section('title', 'PSO Series Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">PSO Series Management</h4>
    <p class="text-muted mb-0">Define counter PSO prefixes, starting/ending serial ranges, and special company bill series.</p>
  </div>
  @if($currentUser->hasPermission('can_configure_pso'))
  <a href="{{ route('admin.pso.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> Configure New PSO
  </a>
  @endif
</div>

<div class="erp-table-container mb-4">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark">Active PSO Series Rules (Default Configuration)</span>
    <span class="badge bg-success">{{ $psoList->where('is_active', true)->count() }} Configs Active</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>PSO Identifier</th>
          <th>Prefix</th>
          <th>Range Start</th>
          <th>Range End</th>
          <th>Special Bills Series</th>
          <th>Assigned Operator</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($psoList as $pso)
          <tr>
            <td>
              <span class="badge bg-primary font-mono fs-6">{{ $pso->code }}</span>
              @if($pso->description)
                <div class="text-muted small mt-1" style="font-size: 0.76rem;">{{ Str::limit($pso->description, 50) }}</div>
              @endif
            </td>
            <td>
              <code class="fw-bold fs-6">{{ $pso->prefix }}</code>
              <div class="text-muted font-mono" style="font-size: 0.72rem;">
                <i class="bi bi-calendar3 text-primary me-0.5"></i>FY: {{ $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}
              </div>
            </td>
            <td class="font-mono">{{ sprintf('%02d', $pso->start_no) }}</td>
            <td class="font-mono">{{ sprintf('%02d', $pso->end_no) }}</td>
            <td>
              @if(!empty($pso->specials))
                @foreach($pso->specials as $spec)
                  <span class="badge bg-info text-dark me-1 font-mono">{{ $spec }}</span>
                @endforeach
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>{{ $pso->operator_name }}</td>
            <td>
              <span class="badge {{ $pso->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $pso->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-end text-nowrap">
              @if($currentUser->hasPermission('can_configure_pso'))
              <div class="d-flex justify-content-end align-items-center gap-1">
                {{-- Edit Action --}}
                <a href="{{ route('admin.pso.edit', $pso->id) }}" class="btn btn-sm btn-outline-primary" title="Edit PSO Configuration">
                  <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                {{-- Status Toggle --}}
                <form action="{{ route('admin.pso.toggle', $pso->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm {{ $pso->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}" 
                          title="{{ $pso->is_active ? 'Disable' : 'Enable' }}">
                    <i class="bi {{ $pso->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                    {{ $pso->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </form>

                {{-- Delete Action (if safe) --}}
                @if(($pso->bills_count ?? 0) === 0)
                <form action="{{ route('admin.pso.delete', $pso->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Are you sure you want to delete PSO Series {{ $pso->code }}?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete PSO Configuration">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                @endif
              </div>
              @else
                <span class="text-muted small">Read Only</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-diagram-3 fs-3 d-block mb-1 text-primary"></i>
              No PSO Series configured. Click <a href="{{ route('admin.pso.create') }}" class="text-primary fw-bold">"+ Configure New PSO"</a> above to add your first counter series.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Series Reference Cards -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 1 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles standard counter sequence <code>CB 01</code> to <code>CB 10</code>. Verifies 10 bills sequentially.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 2 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles sequence <code>CB 11</code> to <code>CB 20</code> plus Special Company bills <code>ITC 01</code> and <code>ITC 03</code>.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 3 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles Retail counter sequence <code>RB 01</code> to <code>RB 10</code> for walk-in instant counter receipts.</div>
    </div>
  </div>
</div>
@endsection
