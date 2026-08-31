@extends('layouts.app')

@section('title', 'PSO Series Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">PSO Series Management</h4>
    <p class="text-muted mb-0">Define counter PSO prefixes, starting/ending serial ranges, and special company bill series.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-pso">
    <i class="bi bi-plus-circle me-1"></i> Configure New PSO
  </button>
</div>

<div class="erp-table-container mb-4">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark">Active PSO Series Rules (Default Configuration)</span>
    <span class="badge bg-success">{{ $psoList->count() }} Configs Active</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>PSO Identifier</th>
          <th>PSO Name</th>
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
            <td><span class="badge bg-primary">{{ $pso->code }}</span></td>
            <td><strong>{{ $pso->name }}</strong></td>
            <td><code>{{ $pso->prefix }}</code></td>
            <td>{{ sprintf('%02d', $pso->start_no) }}</td>
            <td>{{ sprintf('%02d', $pso->end_no) }}</td>
            <td>
              @if(!empty($pso->specials))
                @foreach($pso->specials as $spec)
                  <span class="badge bg-info text-dark me-1">{{ $spec }}</span>
                @endforeach
              @else
                <span class="text-muted small">None</span>
              @endif
            </td>
            <td>{{ $pso->operator_name }}</td>
            <td>
              <span class="badge {{ $pso->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $pso->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-end">
              <form action="{{ route('pso.toggle', $pso->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                  <i class="bi {{ $pso->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                  {{ $pso->is_active ? 'Disable' : 'Enable' }}
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-4">
              <i class="bi bi-diagram-3 fs-3 d-block mb-1 text-primary"></i>
              No PSO Series configured. Click <strong>"+ Configure New PSO"</strong> above to add your first counter series.
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
    <div class="card border p-3 bg-white">
      <div class="fw-bold text-primary mb-1">PSO 1 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles standard counter sequence <code>CB 01</code> to <code>CB 10</code>. Verifies 10 bills sequentially.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <div class="fw-bold text-primary mb-1">PSO 2 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles sequence <code>CB 11</code> to <code>CB 20</code> plus Special Company bills <code>ITC 01</code> and <code>ITC 03</code>.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <div class="fw-bold text-primary mb-1">PSO 3 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles Retail counter sequence <code>RB 01</code> to <code>RB 10</code> for walk-in instant counter receipts.</div>
    </div>
  </div>
</div>
@endsection
