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
      <small class="text-muted">
        {{ count($credits) }} credit record{{ count($credits) === 1 ? '' : 's' }}
        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" title="Already submitted to Udhari"><i class="bi bi-cloud-check"></i> {{ $totSent ?? 0 }} Sent</span>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1" title="Pending submit to Udhari"><i class="bi bi-clock-history"></i> {{ $totNotSent ?? 0 }} Not Sent</span>
      </small>
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

<div class="erp-table-container">
  <!-- Sleek Unified Action & Filter Toolbar -->
  <div class="p-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between gap-2 flex-wrap">
    <!-- Filters: Prefix, Sent/Not Sent, Date Range -->
    <form method="GET" action="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index') }}" class="m-0 d-inline-flex align-items-center gap-2 flex-wrap" id="form-credit-filter">
      <!-- Prefix Filter -->
      <div class="input-group input-group-sm" style="width: auto;">
        <span class="input-group-text bg-white text-muted" title="Prefix">
          <i class="bi bi-tag text-primary"></i>
        </span>
        <select name="prefix" id="select-credit-prefix" class="form-select form-select-sm font-mono fw-bold bg-white" style="width: auto; min-width: 130px;">
          <option value="ALL">All Prefixes ({{ $allPrefixes->count() }})</option>
          @foreach($allPrefixes as $pfx)
            <option value="{{ $pfx }}" {{ (string)$selectedPrefix === (string)$pfx ? 'selected' : '' }}>
              {{ $pfx }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Sent / Not Sent Status Filter -->
      <div class="input-group input-group-sm" style="width: auto;">
        <span class="input-group-text bg-white text-muted" title="Udhari Sync Status">
          <i class="bi bi-cloud-arrow-up text-primary"></i>
        </span>
        <select name="status" id="select-credit-status" class="form-select form-select-sm fw-semibold bg-white" style="width: auto; min-width: 120px;">
          <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
          <option value="sent" {{ ($selectedStatus ?? '') === 'sent' ? 'selected' : '' }}>Sent</option>
          <option value="not_sent" {{ ($selectedStatus ?? '') === 'not_sent' ? 'selected' : '' }}>Not Sent</option>
        </select>
      </div>

      <!-- Date Range (Start Date & End Date) Filter -->
      <div class="input-group input-group-sm" style="width: auto;">
        <span class="input-group-text bg-white text-muted" title="Start Date">
          <i class="bi bi-calendar-event text-primary me-1"></i> From
        </span>
        <input type="date" name="start_date" id="filter-start-date" class="form-control form-control-sm bg-white" value="{{ $startDate ?? '' }}" style="max-width: 135px;">
        <span class="input-group-text bg-white text-muted">To</span>
        <input type="date" name="end_date" id="filter-end-date" class="form-control form-control-sm bg-white" value="{{ $endDate ?? '' }}" style="max-width: 135px;">
      </div>

      <button type="submit" class="btn btn-sm btn-primary fw-semibold px-2.5" id="btn-apply-filter">
        <i class="bi bi-funnel me-1"></i> Filter
      </button>

      @php
        $hasActiveFilter = (!empty($selectedPrefix) && strtoupper($selectedPrefix) !== 'ALL') ||
                           (!empty($selectedStatus) && $selectedStatus !== 'all') ||
                           !empty($startDate) ||
                           !empty($endDate);
      @endphp
      @if($hasActiveFilter)
        <a href="{{ route(request()->routeIs('admin.*') ? 'admin.credit.index' : 'credit.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear All Filters">
          <i class="bi bi-x-lg me-1"></i> Reset
        </a>
      @endif
    </form>

    <!-- Udhari API Submit Form ("Add Bill Udhari App") - Sends NOT SENT bills only -->
    <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.credit.send_udhari' : 'credit.send_udhari') }}" class="m-0 d-inline-flex align-items-center gap-1" id="form-udhari-submit">
      @csrf
      <input type="hidden" name="prefix" id="udhari-form-prefix" value="{{ $selectedPrefix ?? 'ALL' }}">
      <input type="hidden" name="status" id="udhari-form-status" value="{{ $selectedStatus ?? 'all' }}">
      <input type="hidden" name="start_date" id="udhari-form-start-date" value="{{ $startDate ?? '' }}">
      <input type="hidden" name="end_date" id="udhari-form-end-date" value="{{ $endDate ?? '' }}">
      <div class="input-group input-group-sm" style="width: auto;">
        <span class="input-group-text bg-white text-muted" title="Select Udhari API Brand">
          <i class="bi bi-hdd-network text-primary"></i>
        </span>
        <select name="udhari_api" id="select-udhari-api" class="form-select form-select-sm font-mono fw-semibold bg-white" style="width: auto; min-width: 110px;">
          <option value="redbull">Redbull</option>
          <option value="cadbury">Cadbury</option>
          <option value="parle">Parle</option>
          <option value="itc">Itc</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm fw-semibold text-nowrap" id="btn-add-udhari-prefix" title="Send Not-Sent bills to selected Udhari App API">
          <i class="bi bi-plus-circle me-1"></i> Add Bill Udhari App <span class="badge bg-light text-primary ms-1" style="font-size: 0.72rem;">Not Sent Only</span>
        </button>
      </div>
    </form>
  </div>

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
          <th>Udhari Status</th>
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
            <td>
              @if($c->is_udhari_synced)
                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" title="Sent to {{ $c->udhari_api ?: 'Udhari App' }}{{ $c->udhari_synced_at ? ' on ' . $c->udhari_synced_at->format('d/m/Y H:i') : '' }}">
                  <i class="bi bi-cloud-check-fill text-success"></i> Sent ({{ $c->udhari_api ? strtok($c->udhari_api, ' ') : 'Udhari' }})
                </span>
              @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-inline-flex align-items-center gap-1" title="Not submitted to Udhari App yet">
                  <i class="bi bi-clock-history"></i> Not Sent
                </span>
              @endif
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
            <td colspan="12" class="text-center text-muted py-4">
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

  // Ensure filter values (prefix, status, dates) are synced before Udhari form submission
  const prefixSelect = document.getElementById('select-credit-prefix');
  const statusSelect = document.getElementById('select-credit-status');
  const startDateInput = document.getElementById('filter-start-date');
  const endDateInput = document.getElementById('filter-end-date');

  const udhariPrefixInput = document.getElementById('udhari-form-prefix');
  const udhariStatusInput = document.getElementById('udhari-form-status');
  const udhariStartDateInput = document.getElementById('udhari-form-start-date');
  const udhariEndDateInput = document.getElementById('udhari-form-end-date');
  const udhariForm = document.getElementById('form-udhari-submit');

  function syncUdhariFormInputs() {
    if (prefixSelect && udhariPrefixInput) {
      udhariPrefixInput.value = prefixSelect.value || 'ALL';
    }
    if (statusSelect && udhariStatusInput) {
      udhariStatusInput.value = statusSelect.value || 'all';
    }
    if (startDateInput && udhariStartDateInput) {
      udhariStartDateInput.value = startDateInput.value || '';
    }
    if (endDateInput && udhariEndDateInput) {
      udhariEndDateInput.value = endDateInput.value || '';
    }
  }

  if (prefixSelect) prefixSelect.addEventListener('change', syncUdhariFormInputs);
  if (statusSelect) statusSelect.addEventListener('change', syncUdhariFormInputs);
  if (startDateInput) startDateInput.addEventListener('change', syncUdhariFormInputs);
  if (endDateInput) endDateInput.addEventListener('change', syncUdhariFormInputs);

  if (udhariForm) {
    udhariForm.addEventListener('submit', function() {
      syncUdhariFormInputs();
    });
  }
});
</script>
@endsection
