@extends('layouts.app')

@section('title', '7-Day Retention Window')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">7-Day PSO Retention Window Radar</h4>
    <p class="text-muted mb-0">Unapproved and pending PSOs remain active and editable for 7 days before compliance auto-archiving.</p>
  </div>
</div>

<div class="erp-table-container mb-4">
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>PSO Code</th>
          <th>Business Date</th>
          <th>Created Date</th>
          <th>7-Day Retention Remaining</th>
          <th>Total Amount</th>
          <th>Approval Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($retentions as $ret)
          <tr>
            <td><strong>{{ $ret->pso_code }}</strong></td>
            <td>{{ $ret->business_date ? $ret->business_date->format('d/m/Y') : '' }}</td>
            <td>{{ $ret->created_date_formatted }}</td>
            <td>
              <div class="d-flex align-items-center gap-2" style="max-width: 220px;">
                <span class="small fw-semibold">{{ $ret->days_remaining }} Days Remaining</span>
                <div class="retention-meter flex-grow-1">
                  <div class="retention-fill {{ $ret->days_remaining <= 2 ? 'bg-danger' : ($ret->days_remaining <= 4 ? 'bg-warning' : 'bg-success') }}" style="width: {{ ($ret->days_remaining / 7) * 100 }}%;"></div>
                </div>
              </div>
            </td>
            <td class="font-mono">₹{{ number_format($ret->total_amount, 2) }}</td>
            <td><span class="badge {{ $ret->badge_class }}">{{ $ret->status }}</span></td>
            <td>
              <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye me-1"></i> Inspect
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
