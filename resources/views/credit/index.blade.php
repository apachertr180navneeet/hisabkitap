@extends('layouts.app')

@section('title', 'Credit Collection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-1">Credit Collection Management</h4>
    <p class="text-muted mb-0">Dedicated ledger for bills sold on Credit. Filter by bill prefix, assign to salesmen and export physical collection sheets.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.export_excel' : 'credit.export_excel', request()->query()) }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.export_pdf' : 'credit.export_pdf', request()->query()) }}" target="_blank" class="btn btn-danger">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF / Print Sheet
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border p-3 bg-white shadow-sm">
      <div class="d-flex justify-content-between align-items-start">
        <small class="text-muted fw-semibold">Total Credit Sales</small>
        @if(!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL')
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono small">Prefix: {{ $selectedPrefix }}</span>
        @endif
      </div>
      <div class="fs-4 fw-bold font-mono text-warning">₹{{ number_format($totSales, 2) }}</div>
      <small class="text-muted">{{ count($credits) }} credit record{{ count($credits) === 1 ? '' : 's' }}</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white shadow-sm">
      <div class="d-flex justify-content-between align-items-start">
        <small class="text-muted fw-semibold">Total Recovered</small>
        <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="bi bi-arrow-down-left"></i> Received</span>
      </div>
      <div class="fs-4 fw-bold font-mono text-success">₹{{ number_format($totRecovered, 2) }}</div>
      <small class="text-muted">Cleared & partial settlements</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white shadow-sm">
      <div class="d-flex justify-content-between align-items-start">
        <small class="text-muted fw-semibold">Outstanding Field Recovery</small>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small"><i class="bi bi-clock-history"></i> Pending</span>
      </div>
      <div class="fs-4 fw-bold font-mono text-danger">₹{{ number_format($totOutstanding, 2) }}</div>
      <small class="text-muted">Balance to be collected</small>
    </div>
  </div>
</div>

<!-- Filters Bar -->
<div class="card border p-3 mb-3 bg-white shadow-sm">
  <form method="GET" action="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index') }}" class="row g-2 align-items-center">
    <!-- Primary Bill Prefix Filter -->
    <div class="col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-primary text-white fw-semibold">
          <i class="bi bi-tag-fill me-1"></i> Bill Prefix
        </span>
        <select name="prefix" class="form-select form-select-sm fw-bold font-mono" onchange="this.form.submit()">
          <option value="ALL">All Prefixes ({{ $allPrefixes->count() }})</option>
          @foreach($allPrefixes as $pfx)
            <option value="{{ $pfx }}" {{ (string)$selectedPrefix === (string)$pfx ? 'selected' : '' }}>
              {{ $pfx }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <!-- Assigned Salesman Filter -->
    <div class="col-md-2">
      <select name="salesman" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Salesmen</option>
        @foreach($salesmen as $sm)
          <option value="{{ $sm }}" {{ $selectedSalesman === $sm ? 'selected' : '' }}>{{ $sm }}</option>
        @endforeach
      </select>
    </div>

    <!-- Collection Status Filter -->
    <div class="col-md-2">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Statuses</option>
        <option value="Pending" {{ $selectedStatus === 'Pending' ? 'selected' : '' }}>Pending</option>
        <option value="Partially Collected" {{ $selectedStatus === 'Partially Collected' ? 'selected' : '' }}>Partially Collected</option>
        <option value="Collected" {{ $selectedStatus === 'Collected' ? 'selected' : '' }}>Collected</option>
      </select>
    </div>

    <!-- Search Input -->
    <div class="col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search Bill No, Customer, Remarks..." value="{{ $search }}">
        @if($search)
          <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index', request()->except('search')) }}" class="btn btn-outline-secondary" title="Clear search"><i class="bi bi-x"></i></a>
        @endif
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
        <i class="bi bi-funnel me-1"></i> Filter
      </button>
      <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset all filters">
        Reset
      </a>
    </div>
  </form>

  <!-- Quick Prefix Filter Badges -->
  @if($allPrefixes->count() > 0)
    <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2 small text-muted">
      <span class="fw-semibold"><i class="bi bi-tags me-1 text-primary"></i> Quick Prefix:</span>
      <div class="d-flex flex-wrap gap-1">
        <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index', array_merge(request()->except('prefix'), ['prefix' => 'ALL'])) }}"
           class="badge {{ empty($selectedPrefix) || $selectedPrefix === 'ALL' ? 'bg-primary text-white' : 'bg-light text-dark border text-decoration-none' }}">
          All ({{ $allPrefixes->count() }})
        </a>
        @foreach($allPrefixes as $pfx)
          <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index', array_merge(request()->except('prefix'), ['prefix' => $pfx])) }}"
             class="badge {{ (string)$selectedPrefix === (string)$pfx ? 'bg-primary text-white' : 'bg-light text-dark border text-decoration-none' }} font-mono">
            {{ $pfx }}
          </a>
        @endforeach
      </div>
    </div>
  @endif
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
            <td>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono me-1" title="Bill Prefix">{{ $c->bill_prefix ?: '—' }}</span>
              <strong>{{ $c->bill_no }}</strong>
            </td>
            <td>{{ $c->customer_name }}</td>
            <td><i class="bi bi-person-badge text-primary me-1"></i>{{ $c->salesman_name ?: '—' }}</td>
            <td>{{ $c->bill_date ? (is_string($c->bill_date) ? substr($c->bill_date, 0, 10) : $c->bill_date->format('d/m/Y')) : '—' }}</td>
            <td class="font-mono">₹{{ number_format($c->bill_amount, 2) }}</td>
            <td class="font-mono text-success">₹{{ number_format($c->paid_amount, 2) }}</td>
            <td class="font-mono {{ $c->outstanding_amount > 0 ? 'text-danger fw-bold' : 'text-success' }}">₹{{ number_format($c->outstanding_amount, 2) }}</td>
            <td>
              <span class="badge {{ $c->collection_status === 'Collected' ? 'bg-success' : ($c->collection_status === 'Partially Collected' ? 'bg-info text-white' : 'bg-warning text-dark') }}">
                {{ $c->collection_status }}
              </span>
            </td>
            <td>{{ $c->due_date ? (is_string($c->due_date) ? substr($c->due_date, 0, 10) : $c->due_date->format('d/m/Y')) : '—' }}</td>
            <td class="small text-muted">{{ $c->remark ?: '—' }}</td>
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
              No credit transactions found matching the selected filters.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-open-credit-update').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const creditId = this.dataset.creditId;
      const billNo = this.dataset.billNo;
      const customer = this.dataset.customer;
      const salesman = this.dataset.salesman;
      const total = this.dataset.total;
      const outstanding = this.dataset.outstanding;

      const hiddenIdInput = document.getElementById('credit-modal-hidden-id');
      if (hiddenIdInput) hiddenIdInput.value = creditId;

      const billNoSpan = document.getElementById('credit-modal-billno');
      if (billNoSpan) billNoSpan.textContent = billNo;

      const custSpan = document.getElementById('credit-modal-customer');
      if (custSpan) custSpan.textContent = customer;

      const smSpan = document.getElementById('credit-modal-salesman');
      if (smSpan) smSpan.textContent = salesman || '—';

      const totSpan = document.getElementById('credit-modal-total');
      if (totSpan) totSpan.textContent = total;

      const outSpan = document.getElementById('credit-modal-out');
      if (outSpan) outSpan.textContent = outstanding;

      const numericOut = parseFloat(outstanding.replace(/[^0-9.]/g, '')) || 0;
      const paidInput = document.getElementById('credit-modal-paid-input');
      if (paidInput) {
        paidInput.value = numericOut;
        paidInput.max = numericOut;
      }

      const modalEl = document.getElementById('modal-update-credit');
      if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      }
    });
  });
});
</script>
@endsection
