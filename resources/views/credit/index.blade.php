@extends('layouts.app')

@section('title', 'Credit Collection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Credit Collection Management</h4>
    <p class="text-muted mb-0">Dedicated ledger for bills sold on Credit. Assign to salesmen and export physical collection sheets.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Print Sheet
    </button>
    <a href="{{ route('credit.export') }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel me-1"></i> Export Credit Collection Sheet
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Total Credit Sales</small>
      <div class="fs-4 fw-bold font-mono text-warning">₹{{ number_format($totSales, 2) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Total Recovered</small>
      <div class="fs-4 fw-bold font-mono text-success">₹{{ number_format($totRecovered, 2) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Outstanding Field Recovery</small>
      <div class="fs-4 fw-bold font-mono text-danger">₹{{ number_format($totOutstanding, 2) }}</div>
    </div>
  </div>
</div>

<div class="erp-table-container">
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>Bill No.</th>
          <th>Customer</th>
          <th>Assigned Salesman</th>
          <th>Bill Date</th>
          <th>Bill Amount</th>
          <th>Paid Amount</th>
          <th>Outstanding</th>
          <th>Collection Status</th>
          <th>Due Date</th>
          <th>Remark</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($credits as $c)
          <tr>
            <td><strong>{{ $c->bill_no }}</strong></td>
            <td>{{ $c->customer_name }}</td>
            <td><i class="bi bi-person-badge text-primary me-1"></i>{{ $c->salesman_name }}</td>
            <td>{{ $c->bill_date ? $c->bill_date->format('d/m/Y') : '' }}</td>
            <td class="font-mono">₹{{ number_format($c->bill_amount, 2) }}</td>
            <td class="font-mono text-success">₹{{ number_format($c->paid_amount, 2) }}</td>
            <td class="font-mono {{ $c->outstanding_amount > 0 ? 'text-danger fw-bold' : 'text-success' }}">₹{{ number_format($c->outstanding_amount, 2) }}</td>
            <td>
              <span class="badge {{ $c->collection_status === 'Collected' ? 'bg-success' : ($c->collection_status === 'Partially Collected' ? 'bg-info text-white' : 'bg-warning text-dark') }}">
                {{ $c->collection_status }}
              </span>
            </td>
            <td>{{ $c->due_date ? $c->due_date->format('d/m/Y') : '-' }}</td>
            <td class="small text-muted">{{ $c->remark }}</td>
            <td class="text-end">
              @if($c->outstanding_amount > 0)
                <button class="btn btn-sm btn-outline-success btn-open-credit-update" data-credit-id="{{ $c->id }}" data-bill-no="{{ $c->bill_no }}" data-customer="{{ $c->customer_name }}" data-salesman="{{ $c->salesman_name }}" data-total="₹{{ number_format($c->bill_amount, 2) }}" data-outstanding="₹{{ number_format($c->outstanding_amount, 2) }}">
                  <i class="bi bi-cash-coin me-1"></i> Receive
                </button>
              @else
                <span class="text-success small"><i class="bi bi-check-all"></i> Settled</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="11" class="text-center text-muted py-4">
              <i class="bi bi-cash-coin fs-3 d-block mb-1 text-primary"></i>
              No credit transactions logged yet.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
