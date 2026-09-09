@extends('layouts.app')

@section('title', 'Bill Sequence Verification')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-1">Bill Sequence Verification</h4>
    <p class="text-muted mb-0">Cross-match physical bills against Tally DayBook. Edit payment classifications, cash discounts, refunds, and assigned salespersons in real time.</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-manual-bill">
      <i class="bi bi-plus-circle-fill me-1"></i> Add Manual Bill / Payment
    </button>
    <a href="{{ route('admin.verification.export', ['date' => $businessDate]) }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-file-earmark-arrow-down me-1"></i> Export Verification
    </a>
    <form action="{{ route('admin.verification.auto_verify') }}" method="POST" class="d-inline">
      @csrf
      <input type="hidden" name="date" value="{{ $businessDate }}">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2-all me-1"></i> Auto-Verify Slips
      </button>
    </form>
  </div>
</div>

<!-- Filters Bar -->
<div class="card border p-3 mb-3 bg-white shadow-sm">
  <form method="GET" action="{{ route('admin.verification.index') }}" class="row g-2 align-items-center">
    <div class="col-md-2">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
        <input type="date" name="date" class="form-control font-mono" value="{{ $businessDate !== 'ALL' ? $businessDate : '' }}" placeholder="YYYY-MM-DD" onchange="this.form.submit()" title="Select Business Date">
      </div>
    </div>
    <div class="col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search Bill / Customer / Salesperson..." value="{{ request('search') }}">
      </div>
    </div>
    <div class="col-md-2">
      <select name="pso" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All PSOs</option>
        @foreach($psoList as $pso)
          <option value="{{ $pso->code }}" {{ request('pso') === $pso->code ? 'selected' : '' }}>{{ $pso->code }} ({{ $pso->prefix }} - {{ $pso->operator_name }}{{ $pso->driver_name ? ' | Drv: ' . $pso->driver_name : '' }}{{ $pso->gadi_number ? ' | ' . $pso->gadi_number : '' }})</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select name="salesperson" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Sales Persons</option>
        @foreach($salespersons as $sp)
          <option value="{{ $sp->name }}" {{ request('salesperson') === $sp->name || request('salesperson') == $sp->id ? 'selected' : '' }}>{{ $sp->name }} ({{ $sp->code }}{{ $sp->prefix_code ? ' - ' . $sp->prefix_code : '' }})</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3 d-flex gap-1">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Statuses</option>
        <option value="Matched" {{ request('status') === 'Matched' ? 'selected' : '' }}>Matched</option>
        <option value="Missing" {{ request('status') === 'Missing' ? 'selected' : '' }}>Missing</option>
        <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <select name="payment_type" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Payments</option>
        <option value="Cash" {{ request('payment_type') === 'Cash' ? 'selected' : '' }}>Cash</option>
        <option value="Paytm" {{ request('payment_type') === 'Paytm' ? 'selected' : '' }}>Paytm / RTGS</option>
        <option value="Split" {{ request('payment_type') === 'Split' ? 'selected' : '' }}>Split (Cash + Paytm/RTGS)</option>
        <option value="Check" {{ request('payment_type') === 'Check' ? 'selected' : '' }}>Cheque</option>
        <option value="Credit" {{ request('payment_type') === 'Credit' ? 'selected' : '' }}>Credit</option>
        <option value="Cancelled" {{ request('payment_type') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <a href="{{ route('admin.verification.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
  </form>

  @if(!empty($availableDates) && count($availableDates) > 0)
    <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2 small text-muted">
      <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i> Dates in Database:</span>
      <div class="d-flex flex-wrap gap-1">
        <a href="{{ route('admin.verification.index', array_merge(request()->except('date'), ['date' => 'ALL'])) }}" class="badge {{ $businessDate === 'ALL' ? 'bg-dark text-white' : 'bg-light text-dark border text-decoration-none' }}">
          All Dates
        </a>
        @foreach($availableDates as $ad)
          <a href="{{ route('admin.verification.index', array_merge(request()->except('date'), ['date' => $ad])) }}" class="badge {{ $businessDate === $ad ? 'bg-primary text-white' : 'bg-light text-dark border text-decoration-none' }}">
            {{ date('d M Y', strtotime($ad)) }}
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>

<!-- Bulk Actions Toolbar (Visible when rows are selected) -->
<div id="bulkActionsToolbar" class="card border-primary bg-light p-2 mb-3 d-none shadow-sm animate-fade-in">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary px-3 py-2 fs-6">
        <i class="bi bi-check2-square me-1"></i> <span id="selectedBillsCount">0</span> Selected
      </span>
      <button type="button" class="btn btn-sm btn-outline-primary" id="btnBulkEdit">
        <i class="bi bi-pencil-square me-1"></i> Edit Selected
      </button>
      <button type="button" class="btn btn-sm btn-success d-none" id="btnBulkSave">
        <i class="bi bi-check-circle me-1"></i> Save Selected
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnBulkCancel">
        <i class="bi bi-x-circle me-1"></i> Cancel Edit
      </button>
    </div>

    <!-- Quick Batch Assign Dropdowns -->
    <div class="d-flex flex-wrap align-items-center gap-2">
      <div class="input-group input-group-sm" style="width: 260px;">
        <select class="form-select form-select-sm" id="bulkSalespersonSelect">
          <option value="">-- Assign Sales Person --</option>
          @foreach($salespersons as $sp)
            <option value="{{ $sp->id }}" data-name="{{ $sp->name }}">{{ $sp->name }} ({{ $sp->code }})</option>
          @endforeach
        </select>
        <button class="btn btn-primary btn-sm" type="button" id="btnApplyBulkSalesperson">Apply</button>
      </div>

      <div class="input-group input-group-sm" style="width: 220px;">
        <select class="form-select form-select-sm" id="bulkPaymentSelect">
          <option value="">-- Set Payment Type --</option>
          <option value="Cash">Cash</option>
          <option value="Paytm">Paytm / UPI</option>
          <option value="Check">Cheque</option>
          <option value="Credit">Credit</option>
          <option value="Cancelled">Cancelled</option>
        </select>
        <button class="btn btn-primary btn-sm" type="button" id="btnApplyBulkPayment">Apply</button>
      </div>

      <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeselectAll">
        <i class="bi bi-x"></i> Deselect
      </button>
    </div>
  </div>
</div>

<!-- Verification Table -->
<div class="erp-table-container mb-4 shadow-sm">
  <div class="table-responsive" style="max-height: 620px;">
    <table class="table erp-table align-middle table-hover" id="verificationTable">
      <thead class="sticky-top bg-white border-bottom shadow-sm">
        <tr>
          <th style="width: 40px;" class="text-center">
            <input type="checkbox" id="selectAllBills" class="form-check-input" title="Select / Deselect All">
          </th>
          <th>Bill No.</th>
          <th>PSO</th>
          <th style="min-width: 170px;">Sales Person</th>
          <th>Expected</th>
          <th>Tally Found</th>
          <th>Bill Date / Time</th>
          <th>Customer</th>
          <th class="text-end">Amount</th>
          <th style="min-width: 130px;">Payment Type</th>
          <th style="min-width: 110px;" class="text-end">CD</th>
          <th style="min-width: 110px;" class="text-end">Refund</th>
          <th class="text-end">Net Amount</th>
          <th>Status</th>
          <th>Remark</th>
          <th class="text-end" style="min-width: 120px;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $bill)
          <tr id="bill-row-{{ $bill->id }}" 
              class="bill-row {{ $bill->status === 'Missing' ? 'table-danger' : '' }}" 
              data-id="{{ $bill->id }}"
              data-bill-no="{{ $bill->bill_no }}"
              data-amount="{{ (float)$bill->amount }}"
              data-payment-type="{{ $bill->payment_type }}"
              data-cd="{{ (float)$bill->cd_amount }}"
              data-refund="{{ (float)$bill->refund_amount }}"
              data-salesperson-id="{{ $bill->salesperson_id }}"
              data-salesman-name="{{ $bill->salesman_name }}">
            
            <!-- Checkbox -->
            <td class="text-center">
              <input type="checkbox" class="form-check-input bill-select-cb" value="{{ $bill->id }}">
            </td>

            <!-- Bill No -->
            <td><strong class="font-mono">{{ $bill->bill_no }}</strong></td>

            <!-- PSO -->
            <td><span class="badge bg-primary">{{ $bill->pso_code }}</span></td>

            <!-- Sales Person (View / Edit) -->
            <td>
              <span class="salesperson-display text-dark fw-semibold">
                @if($bill->salesman_name)
                  <i class="bi bi-person-badge text-primary me-1"></i>{{ $bill->salesman_name }}
                @else
                  <span class="text-muted fst-italic">— Unassigned —</span>
                @endif
              </span>
              <select class="form-select form-select-sm inline-salesperson-select d-none" style="min-width: 160px;">
                <option value="">-- Unassigned --</option>
                @foreach($salespersons as $sp)
                  <option value="{{ $sp->id }}" data-name="{{ $sp->name }}" {{ ($bill->salesperson_id == $sp->id || $bill->salesman_name === $sp->name) ? 'selected' : '' }}>
                    {{ $sp->name }} ({{ $sp->code }})
                  </option>
                @endforeach
              </select>
            </td>

            <!-- Expected / Tally Found -->
            <td><i class="bi bi-check-circle-fill text-success"></i></td>
            <td><i class="bi bi-check-circle-fill text-success"></i></td>

            <!-- Bill Date & Time -->
            <td>
              {{ $bill->business_date ? $bill->business_date->format('d/m/Y') : '' }} 
              <small class="text-muted d-block">{{ $bill->bill_time }}</small>
            </td>

            <!-- Customer -->
            <td>{{ $bill->customer_name }}</td>

            <!-- Original Amount -->
            <td class="font-mono text-end fw-bold">₹{{ number_format($bill->amount, 2) }}</td>

            <!-- Payment Type (View / Edit) -->
            <td>
              @if($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0))
                <div class="payment-badge-display">
                  <span class="badge bg-success mb-1 d-block"><i class="bi bi-cash me-1"></i>Cash: ₹{{ number_format($bill->cash_amount, 2) }}</span>
                  <span class="badge bg-info text-dark d-block"><i class="bi bi-qr-code-scan me-1"></i>Paytm: ₹{{ number_format($bill->paytm_amount, 2) }}</span>
                </div>
              @else
                <span class="payment-badge-display badge {{ $bill->payment_type === 'Cash' ? 'bg-success' : ($bill->payment_type === 'Paytm' ? 'bg-info text-dark' : ($bill->payment_type === 'Check' ? 'bg-primary' : ($bill->payment_type === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'))) }}">
                  {{ $bill->payment_type }}
                </span>
              @endif
              <select class="form-select form-select-sm inline-payment-select d-none" style="min-width: 140px;">
                <option value="Cash" {{ (!$bill->is_split_payment && $bill->payment_type === 'Cash') ? 'selected' : '' }}>Cash</option>
                <option value="Paytm" {{ (!$bill->is_split_payment && $bill->payment_type === 'Paytm') ? 'selected' : '' }}>Paytm / RTGS</option>
                <option value="Split" {{ ($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0)) ? 'selected' : '' }}>Split (Cash + Paytm)</option>
                <option value="Check" {{ $bill->payment_type === 'Check' ? 'selected' : '' }}>Cheque</option>
                <option value="Credit" {{ $bill->payment_type === 'Credit' ? 'selected' : '' }}>Credit</option>
                <option value="Cancelled" {{ $bill->payment_type === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
              </select>
            </td>

            <!-- CD (Cash Discount) (View / Edit) -->
            <td class="text-end">
              <span class="cd-display font-mono {{ $bill->cd_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $bill->cd_amount > 0 ? ('-₹' . number_format($bill->cd_amount, 2)) : '₹0.00' }}
              </span>
              <div class="input-group input-group-sm inline-cd-group d-none" style="width: 110px;">
                <span class="input-group-text p-1 text-muted">₹</span>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-mono inline-cd-input" value="{{ (float)$bill->cd_amount }}">
              </div>
            </td>

            <!-- Refund (View / Edit) -->
            <td class="text-end">
              <span class="refund-display font-mono {{ $bill->refund_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $bill->refund_amount > 0 ? ('-₹' . number_format($bill->refund_amount, 2)) : '₹0.00' }}
              </span>
              <div class="input-group input-group-sm inline-refund-group d-none" style="width: 110px;">
                <span class="input-group-text p-1 text-muted">₹</span>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-mono inline-refund-input" value="{{ (float)$bill->refund_amount }}">
              </div>
            </td>

            <!-- Net Amount (Dynamic calculation) -->
            <td class="font-mono text-end text-success fw-bold">
              <span class="net-display">₹{{ number_format($bill->net_amount, 2) }}</span>
            </td>

            <!-- Status -->
            <td>
              @if($bill->status === 'Matched')
                <span class="badge badge-matched"><i class="bi bi-check-circle me-1"></i>Matched</span>
              @elseif($bill->status === 'Missing')
                <span class="badge badge-missing"><i class="bi bi-exclamation-octagon me-1"></i>Missing</span>
              @elseif($bill->status === 'Cancelled')
                <span class="badge badge-cancelled">Cancelled</span>
              @else
                <span class="badge bg-secondary">{{ $bill->status }}</span>
              @endif
            </td>

            <!-- Remark -->
            <td class="small text-muted">{{ $bill->remark }}</td>

            <!-- Actions (Edit button / Save & Cancel / Resolve / Split) -->
            <td class="text-end text-nowrap">
              <!-- View Mode Action Buttons -->
              <div class="btn-group-view">
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-row" title="Edit row details">
                  <i class="bi bi-pencil me-1"></i> Edit
                </button>
                <button type="button" class="btn btn-sm btn-outline-info btn-split-pay ms-1" 
                        data-id="{{ $bill->id }}" 
                        data-bill-no="{{ $bill->bill_no }}" 
                        data-customer="{{ $bill->customer_name }}" 
                        data-net="{{ (float)($bill->net_amount > 0 ? $bill->net_amount : $bill->amount) }}" 
                        data-cash="{{ (float)$bill->cash_amount }}" 
                        data-paytm="{{ (float)$bill->paytm_amount }}"
                        data-is-split="{{ $bill->is_split_payment ? '1' : '0' }}"
                        title="Split payment between Cash and Paytm">
                  <i class="bi bi-pie-chart-fill me-1"></i> Split
                </button>
                @if($bill->status === 'Missing')
                  <button type="button" class="btn btn-sm btn-danger btn-open-investigate ms-1" data-bill-no="{{ $bill->bill_no }}" data-customer="{{ $bill->customer_name }}" data-amount="₹{{ number_format($bill->amount, 2) }}" data-pso="{{ $bill->pso_code }}">
                    <i class="bi bi-search me-1"></i> Resolve
                  </button>
                @endif
              </div>

              <!-- Edit Mode Action Buttons (Save hides after save) -->
              <div class="btn-group-edit d-none">
                <button type="button" class="btn btn-sm btn-success btn-save-row me-1" title="Save changes in database">
                  <i class="bi bi-check-lg me-1"></i> Save
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-row" title="Cancel edit">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="16" class="text-center text-muted py-5">
              <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-primary opacity-75"></i>
              <div class="fw-semibold fs-6 text-dark mb-1">No bill records found for {{ $businessDate === 'ALL' ? 'the selected filters' : ($businessDate ? 'date ' . date('d/m/Y', strtotime($businessDate)) : 'the current business date') }}</div>
              <p class="small text-muted mb-3">Try adjusting your date/PSO filters, or import the latest Tally DayBook.</p>
              <div class="d-flex justify-content-center gap-2">
                @if($businessDate !== 'ALL')
                  <a href="{{ route('admin.verification.index', ['date' => 'ALL']) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar-range me-1"></i> View All Dates
                  </a>
                @endif
                <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-primary">
                  <i class="bi bi-cloud-arrow-up me-1"></i> Import Tally DayBook
                </a>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot class="bg-light fw-bold">
        <tr>
          <td colspan="8">TOTAL VERIFIED COUNT (<span id="totalBillsCount">{{ $bills->count() }}</span> bills)</td>
          <td class="font-mono text-end" id="sumAmount">₹{{ number_format($bills->sum('amount'), 2) }}</td>
          <td>-</td>
          <td class="font-mono text-end text-danger" id="sumCd">-₹{{ number_format($bills->sum('cd_amount'), 2) }}</td>
          <td class="font-mono text-end text-danger" id="sumRefund">-₹{{ number_format($bills->sum('refund_amount'), 2) }}</td>
          <td class="font-mono text-end text-success" id="sumNet">₹{{ number_format($bills->where('status', '!=', 'Missing')->sum('net_amount'), 2) }}</td>
          <td colspan="3" class="text-end text-muted small">Active PSO & Salesperson records</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<style>
  .row-highlight-success {
    animation: pulseGreen 1.6s ease-in-out;
  }
  @keyframes pulseGreen {
    0% { background-color: #d1e7dd; }
    50% { background-color: #badbcc; }
    100% { background-color: inherit; }
  }
  .animate-fade-in {
    animation: fadeIn 0.25s ease-in-out;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const updateRoute = "{{ route('admin.verification.update') }}";
  const bulkUpdateRoute = "{{ route('admin.verification.bulk_update') }}";

  const selectAllCb = document.getElementById('selectAllBills');
  const rowCheckboxes = document.querySelectorAll('.bill-select-cb');
  const bulkToolbar = document.getElementById('bulkActionsToolbar');
  const selectedCountSpan = document.getElementById('selectedBillsCount');

  // Update selected count & bulk toolbar visibility
  function updateSelectionState() {
    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    const count = checkedBoxes.length;
    selectedCountSpan.textContent = count;

    if (count > 0) {
      bulkToolbar.classList.remove('d-none');
    } else {
      bulkToolbar.classList.add('d-none');
    }

    if (selectAllCb) {
      if (count === 0) {
        selectAllCb.checked = false;
        selectAllCb.indeterminate = false;
      } else if (count === rowCheckboxes.length && rowCheckboxes.length > 0) {
        selectAllCb.checked = true;
        selectAllCb.indeterminate = false;
      } else {
        selectAllCb.checked = false;
        selectAllCb.indeterminate = true;
      }
    }
  }

  // Select All Checkbox Handler
  if (selectAllCb) {
    selectAllCb.addEventListener('change', function () {
      rowCheckboxes.forEach(cb => {
        cb.checked = selectAllCb.checked;
      });
      updateSelectionState();
    });
  }

  // Row Checkbox Handler
  rowCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateSelectionState);
  });

  // Deselect All Button
  document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
    rowCheckboxes.forEach(cb => cb.checked = false);
    if (selectAllCb) {
      selectAllCb.checked = false;
      selectAllCb.indeterminate = false;
    }
    updateSelectionState();
  });

  // Helper to calculate & update Net Amount in live edit mode
  function calculateRowNet(row) {
    const amount = parseFloat(row.dataset.amount || 0);
    const cdInput = row.querySelector('.inline-cd-input');
    const refundInput = row.querySelector('.inline-refund-input');
    const netDisplay = row.querySelector('.net-display');

    const cd = parseFloat(cdInput ? cdInput.value : 0) || 0;
    const refund = parseFloat(refundInput ? refundInput.value : 0) || 0;
    const net = Math.max(0, amount - cd - refund);

    if (netDisplay) {
      netDisplay.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }

  // Enter edit mode for a row
  function setRowEditMode(row, isEditing) {
    const viewGroup = row.querySelector('.btn-group-view');
    const editGroup = row.querySelector('.btn-group-edit');

    const spDisplay = row.querySelector('.salesperson-display');
    const spSelect = row.querySelector('.inline-salesperson-select');

    const payDisplay = row.querySelector('.payment-badge-display');
    const paySelect = row.querySelector('.inline-payment-select');

    const cdDisplay = row.querySelector('.cd-display');
    const cdGroup = row.querySelector('.inline-cd-group');

    const refundDisplay = row.querySelector('.refund-display');
    const refundGroup = row.querySelector('.inline-refund-group');

    if (isEditing) {
      row.classList.add('table-warning');
      viewGroup?.classList.add('d-none');
      editGroup?.classList.remove('d-none');

      spDisplay?.classList.add('d-none');
      spSelect?.classList.remove('d-none');

      payDisplay?.classList.add('d-none');
      paySelect?.classList.remove('d-none');

      cdDisplay?.classList.add('d-none');
      cdGroup?.classList.remove('d-none');

      refundDisplay?.classList.add('d-none');
      refundGroup?.classList.remove('d-none');
    } else {
      row.classList.remove('table-warning');
      viewGroup?.classList.remove('d-none');
      editGroup?.classList.add('d-none');

      spDisplay?.classList.remove('d-none');
      spSelect?.classList.add('d-none');

      payDisplay?.classList.remove('d-none');
      paySelect?.classList.add('d-none');

      cdDisplay?.classList.remove('d-none');
      cdGroup?.classList.add('d-none');

      refundDisplay?.classList.remove('d-none');
      refundGroup?.classList.add('d-none');
    }
  }

  // Individual Row "Edit" Button Click
  document.querySelectorAll('.btn-edit-row').forEach(btn => {
    btn.addEventListener('click', function () {
      const row = this.closest('.bill-row');
      if (row) {
        setRowEditMode(row, true);
      }
    });
  });

  // Individual Row "Cancel" Button Click
  document.querySelectorAll('.btn-cancel-row').forEach(btn => {
    btn.addEventListener('click', function () {
      const row = this.closest('.bill-row');
      if (row) {
        // Reset form inputs back to data attributes
        const cdInput = row.querySelector('.inline-cd-input');
        const refundInput = row.querySelector('.inline-refund-input');
        const paySelect = row.querySelector('.inline-payment-select');
        const spSelect = row.querySelector('.inline-salesperson-select');

        if (cdInput) cdInput.value = parseFloat(row.dataset.cd || 0);
        if (refundInput) refundInput.value = parseFloat(row.dataset.refund || 0);
        if (paySelect) paySelect.value = row.dataset.paymentType || 'Cash';
        if (spSelect) spSelect.value = row.dataset.salespersonId || '';

        calculateRowNet(row);
        setRowEditMode(row, false);
      }
    });
  });

  // Live Net Amount Recalculation on CD / Refund input changes
  document.querySelectorAll('.inline-cd-input, .inline-refund-input').forEach(input => {
    input.addEventListener('input', function () {
      const row = this.closest('.bill-row');
      if (row) calculateRowNet(row);
    });
  });

  // Auto-trigger Split modal when "Split" is selected in payment dropdown
  document.querySelectorAll('.inline-payment-select').forEach(sel => {
    sel.addEventListener('change', function () {
      if (this.value === 'Split') {
        const row = this.closest('.bill-row');
        const splitBtn = row?.querySelector('.btn-split-pay');
        if (splitBtn) {
          splitBtn.click();
        }
      }
    });
  });

  // Helper to update badge color dynamically for payment type
  function getPaymentBadgeClass(type) {
    switch (type) {
      case 'Cash': return 'bg-success';
      case 'Paytm': return 'bg-info text-dark';
      case 'Check': return 'bg-primary';
      case 'Credit': return 'bg-warning text-dark';
      case 'Cancelled': return 'bg-secondary';
      default: return 'bg-secondary';
    }
  }

  // Individual Row "Save" Button Click (AJAX Database Save -> Hides Save button)
  document.querySelectorAll('.btn-save-row').forEach(btn => {
    btn.addEventListener('click', async function () {
      const row = this.closest('.bill-row');
      if (!row) return;

      const billId = row.dataset.id;
      const paySelect = row.querySelector('.inline-payment-select');
      const cdInput = row.querySelector('.inline-cd-input');
      const refundInput = row.querySelector('.inline-refund-input');
      const spSelect = row.querySelector('.inline-salesperson-select');

      const paymentType = paySelect?.value || 'Cash';
      const cdAmount = parseFloat(cdInput?.value || 0);
      const refundAmount = parseFloat(refundInput?.value || 0);
      const salespersonId = spSelect?.value || '';
      const selectedOption = spSelect?.options[spSelect.selectedIndex];
      const salesmanName = salespersonId ? (selectedOption?.getAttribute('data-name') || selectedOption?.text) : '';

      // Save button spinner
      const origBtnHtml = this.innerHTML;
      this.disabled = true;
      this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

      try {
        const response = await fetch(updateRoute, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            bill_id: billId,
            payment_type: paymentType,
            cd_amount: cdAmount,
            refund_amount: refundAmount,
            salesperson_id: salespersonId || null,
            salesman_name: salesmanName || null
          })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          // Update row data attributes
          row.dataset.paymentType = paymentType;
          row.dataset.cd = cdAmount;
          row.dataset.refund = refundAmount;
          row.dataset.salespersonId = salespersonId;
          row.dataset.salesmanName = data.bill.salesman_name || '';

          // Update display DOM elements
          const payDisplay = row.querySelector('.payment-badge-display');
          if (payDisplay) {
            payDisplay.textContent = paymentType;
            payDisplay.className = `payment-badge-display badge ${getPaymentBadgeClass(paymentType)}`;
          }

          const spDisplay = row.querySelector('.salesperson-display');
          if (spDisplay) {
            if (data.bill.salesman_name) {
              spDisplay.innerHTML = `<i class="bi bi-person-badge text-primary me-1"></i>${data.bill.salesman_name}`;
            } else {
              spDisplay.innerHTML = `<span class="text-muted fst-italic">— Unassigned —</span>`;
            }
          }

          const cdDisplay = row.querySelector('.cd-display');
          if (cdDisplay) {
            if (cdAmount > 0) {
              cdDisplay.textContent = `-₹${cdAmount.toFixed(2)}`;
              cdDisplay.className = 'cd-display font-mono text-danger fw-semibold';
            } else {
              cdDisplay.textContent = '₹0.00';
              cdDisplay.className = 'cd-display font-mono text-muted';
            }
          }

          const refundDisplay = row.querySelector('.refund-display');
          if (refundDisplay) {
            if (refundAmount > 0) {
              refundDisplay.textContent = `-₹${refundAmount.toFixed(2)}`;
              refundDisplay.className = 'refund-display font-mono text-danger fw-semibold';
            } else {
              refundDisplay.textContent = '₹0.00';
              refundDisplay.className = 'refund-display font-mono text-muted';
            }
          }

          const netDisplay = row.querySelector('.net-display');
          if (netDisplay) {
            const netAmt = parseFloat(data.bill.net_amount || 0);
            netDisplay.textContent = '₹' + netAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }

          // Switch back to view mode: Save button HIDES!
          setRowEditMode(row, false);

          // Success highlight effect & toast
          row.classList.remove('row-highlight-success');
          void row.offsetWidth; // trigger reflow
          row.classList.add('row-highlight-success');

          if (typeof window.showErpToast === 'function') {
            window.showErpToast(data.message || `Bill ${data.bill.bill_no} updated successfully.`, 'success');
          }

          recalculateTableTotals();
        } else {
          alert(data.message || 'Failed to save bill changes.');
        }
      } catch (error) {
        console.error('Error saving bill:', error);
        alert('Server communication error while saving.');
      } finally {
        this.disabled = false;
        this.innerHTML = origBtnHtml;
      }
    });
  });

  // Recalculate footer table totals dynamically
  function recalculateTableTotals() {
    let totAmount = 0;
    let totCd = 0;
    let totRefund = 0;
    let totNet = 0;

    document.querySelectorAll('.bill-row').forEach(r => {
      const amt = parseFloat(r.dataset.amount || 0);
      const cd = parseFloat(r.dataset.cd || 0);
      const refund = parseFloat(r.dataset.refund || 0);
      const net = Math.max(0, amt - cd - refund);

      totAmount += amt;
      totCd += cd;
      totRefund += refund;
      totNet += net;
    });

    const sumAmtEl = document.getElementById('sumAmount');
    const sumCdEl = document.getElementById('sumCd');
    const sumRefEl = document.getElementById('sumRefund');
    const sumNetEl = document.getElementById('sumNet');

    if (sumAmtEl) sumAmtEl.textContent = '₹' + totAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (sumCdEl) sumCdEl.textContent = '-₹' + totCd.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (sumRefEl) sumRefEl.textContent = '-₹' + totRefund.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (sumNetEl) sumNetEl.textContent = '₹' + totNet.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Bulk Edit Selected Button
  const btnBulkEdit = document.getElementById('btnBulkEdit');
  const btnBulkSave = document.getElementById('btnBulkSave');
  const btnBulkCancel = document.getElementById('btnBulkCancel');

  btnBulkEdit?.addEventListener('click', function () {
    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    checkedBoxes.forEach(cb => {
      const row = cb.closest('.bill-row');
      if (row) setRowEditMode(row, true);
    });
    btnBulkEdit.classList.add('d-none');
    btnBulkSave?.classList.remove('d-none');
    btnBulkCancel?.classList.remove('d-none');
  });

  btnBulkCancel?.addEventListener('click', function () {
    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    checkedBoxes.forEach(cb => {
      const row = cb.closest('.bill-row');
      if (row) {
        const cdInput = row.querySelector('.inline-cd-input');
        const refundInput = row.querySelector('.inline-refund-input');
        const paySelect = row.querySelector('.inline-payment-select');
        const spSelect = row.querySelector('.inline-salesperson-select');

        if (cdInput) cdInput.value = parseFloat(row.dataset.cd || 0);
        if (refundInput) refundInput.value = parseFloat(row.dataset.refund || 0);
        if (paySelect) paySelect.value = row.dataset.paymentType || 'Cash';
        if (spSelect) spSelect.value = row.dataset.salespersonId || '';

        calculateRowNet(row);
        setRowEditMode(row, false);
      }
    });
    btnBulkEdit?.classList.remove('d-none');
    btnBulkSave?.classList.add('d-none');
    btnBulkCancel?.classList.add('d-none');
  });

  // Bulk Save Button (saves all currently edited selected rows)
  btnBulkSave?.addEventListener('click', function () {
    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    checkedBoxes.forEach(cb => {
      const row = cb.closest('.bill-row');
      const saveBtn = row?.querySelector('.btn-save-row');
      if (saveBtn) saveBtn.click();
    });
    btnBulkEdit?.classList.remove('d-none');
    btnBulkSave?.classList.add('d-none');
    btnBulkCancel?.classList.add('d-none');
  });

  // Bulk Apply Salesperson
  document.getElementById('btnApplyBulkSalesperson')?.addEventListener('click', async function () {
    const spSelect = document.getElementById('bulkSalespersonSelect');
    const spId = spSelect?.value;
    if (!spId) {
      alert('Please select a Sales Person to assign.');
      return;
    }

    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    const billIds = Array.from(checkedBoxes).map(cb => cb.value);
    if (billIds.length === 0) return;

    const opt = spSelect.options[spSelect.selectedIndex];
    const spName = opt.getAttribute('data-name') || opt.text;

    try {
      const res = await fetch(bulkUpdateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          bill_ids: billIds,
          salesperson_id: spId,
          salesman_name: spName
        })
      });

      const data = await res.json();
      if (res.ok && data.success) {
        checkedBoxes.forEach(cb => {
          const row = cb.closest('.bill-row');
          if (row) {
            row.dataset.salespersonId = spId;
            row.dataset.salesmanName = spName;
            const spDisplay = row.querySelector('.salesperson-display');
            if (spDisplay) spDisplay.innerHTML = `<i class="bi bi-person-badge text-primary me-1"></i>${spName}`;
            const rowSpSelect = row.querySelector('.inline-salesperson-select');
            if (rowSpSelect) rowSpSelect.value = spId;
            row.classList.add('row-highlight-success');
          }
        });
        if (typeof window.showErpToast === 'function') {
          window.showErpToast(data.message, 'success');
        }
      } else {
        alert(data.message || 'Failed to bulk assign Sales Person.');
      }
    } catch (e) {
      console.error(e);
      alert('Error updating bills.');
    }
  });

  // Bulk Apply Payment Type
  document.getElementById('btnApplyBulkPayment')?.addEventListener('click', async function () {
    const paySelect = document.getElementById('bulkPaymentSelect');
    const payType = paySelect?.value;
    if (!payType) {
      alert('Please select a Payment Type to set.');
      return;
    }

    const checkedBoxes = document.querySelectorAll('.bill-select-cb:checked');
    const billIds = Array.from(checkedBoxes).map(cb => cb.value);
    if (billIds.length === 0) {
      alert('Please select at least one bill.');
      return;
    }

    const origBtnHtml = this.innerHTML;
    this.disabled = true;
    this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

    try {
      const res = await fetch(bulkUpdateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          bill_ids: billIds,
          payment_type: payType
        })
      });

      const data = await res.json();
      if (res.ok && data.success) {
        checkedBoxes.forEach(cb => {
          const row = cb.closest('.bill-row');
          if (row) {
            row.dataset.paymentType = payType;
            const payDisplay = row.querySelector('.payment-badge-display');
            if (payDisplay) {
              payDisplay.textContent = payType;
              payDisplay.className = `payment-badge-display badge ${getPaymentBadgeClass(payType)}`;
            }
            const rowPaySelect = row.querySelector('.inline-payment-select');
            if (rowPaySelect) rowPaySelect.value = payType;
            row.classList.add('row-highlight-success');
          }
        });
        if (typeof window.showErpToast === 'function') {
          window.showErpToast(data.message || `Payment type set to ${payType} for ${billIds.length} bills.`, 'success');
        }
      } else {
        alert(data.message || 'Failed to bulk assign Payment Type.');
      }
    } catch (e) {
      console.error(e);
      alert('Error updating bills.');
    } finally {
      this.disabled = false;
      this.innerHTML = origBtnHtml;
    }
  });

  // ==========================================
  // Split Payment (Cash + Paytm) Modal Handlers
  // ==========================================
  let currentSplitRow = null;
  const splitModalEl = document.getElementById('modal-split-pay');
  const splitModal = splitModalEl ? new bootstrap.Modal(splitModalEl) : null;

  const splitBillNo = document.getElementById('split-modal-bill-no');
  const splitCustomer = document.getElementById('split-modal-customer');
  const splitNet = document.getElementById('split-modal-net');
  const splitCashInput = document.getElementById('split-modal-cash');
  const splitPaytmInput = document.getElementById('split-modal-paytm');
  const splitRemainingDisplay = document.getElementById('split-modal-remaining');
  const splitStatusBadge = document.getElementById('split-modal-status-badge');
  const btnSaveSplit = document.getElementById('btnSaveSplitPayment');

  function updateSplitBalance() {
    const net = parseFloat(splitModalEl.dataset.net || 0);
    const cash = parseFloat(splitCashInput.value || 0);
    const paytm = parseFloat(splitPaytmInput.value || 0);
    const totalAllocated = cash + paytm;
    const diff = net - totalAllocated;

    if (Math.abs(diff) < 0.01) {
      splitStatusBadge.className = 'badge bg-success';
      splitStatusBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i> 100% Balanced';
      splitRemainingDisplay.textContent = '₹0.00 Remaining';
      splitRemainingDisplay.className = 'font-mono fw-bold text-success';
      if (btnSaveSplit) btnSaveSplit.disabled = false;
    } else if (diff > 0) {
      splitStatusBadge.className = 'badge bg-warning text-dark';
      splitStatusBadge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Under-Allocated';
      splitRemainingDisplay.textContent = '₹' + diff.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + ' Remaining to allocate';
      splitRemainingDisplay.className = 'font-mono fw-bold text-warning';
      if (btnSaveSplit) btnSaveSplit.disabled = false;
    } else {
      splitStatusBadge.className = 'badge bg-danger';
      splitStatusBadge.innerHTML = '<i class="bi bi-x-circle me-1"></i> Over-Allocated';
      splitRemainingDisplay.textContent = '₹' + Math.abs(diff).toLocaleString('en-IN', { minimumFractionDigits: 2 }) + ' Exceeds Total';
      splitRemainingDisplay.className = 'font-mono fw-bold text-danger';
      if (btnSaveSplit) btnSaveSplit.disabled = true;
    }
  }

  // Open modal on Split button click
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-split-pay');
    if (!btn) return;

    currentSplitRow = btn.closest('.bill-row');
    const billId = btn.dataset.id;
    const billNo = btn.dataset.billNo;
    const customer = btn.dataset.customer;
    const net = parseFloat(btn.dataset.net || 0);
    let cash = parseFloat(btn.dataset.cash || 0);
    let paytm = parseFloat(btn.dataset.paytm || 0);

    if (cash === 0 && paytm === 0) {
      cash = net;
      paytm = 0;
    }

    splitModalEl.dataset.billId = billId;
    splitModalEl.dataset.net = net;

    splitBillNo.textContent = billNo;
    splitCustomer.textContent = customer;
    splitNet.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2 });

    splitCashInput.value = cash;
    splitPaytmInput.value = paytm;

    updateSplitBalance();
    splitModal.show();
  });

  // Auto calculate Paytm when Cash changes
  splitCashInput?.addEventListener('input', function() {
    const net = parseFloat(splitModalEl.dataset.net || 0);
    const cash = parseFloat(this.value || 0);
    if (cash <= net && cash >= 0) {
      splitPaytmInput.value = (net - cash).toFixed(2);
    }
    updateSplitBalance();
  });

  // Auto calculate Cash when Paytm changes
  splitPaytmInput?.addEventListener('input', function() {
    const net = parseFloat(splitModalEl.dataset.net || 0);
    const paytm = parseFloat(this.value || 0);
    if (paytm <= net && paytm >= 0) {
      splitCashInput.value = (net - paytm).toFixed(2);
    }
    updateSplitBalance();
  });

  // Save Split Payment
  btnSaveSplit?.addEventListener('click', async function() {
    const billId = splitModalEl.dataset.billId;
    const cash = parseFloat(splitCashInput.value || 0);
    const paytm = parseFloat(splitPaytmInput.value || 0);

    btnSaveSplit.disabled = true;
    btnSaveSplit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    try {
      const res = await fetch(updateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          bill_id: billId,
          payment_type: 'Cash',
          is_split_payment: (cash > 0 && paytm > 0) ? 1 : 0,
          cash_amount: cash,
          paytm_amount: paytm
        })
      });

      const data = await res.json();
      if (res.ok && data.success) {
        splitModal.hide();
        if (currentSplitRow) {
          const splitBtn = currentSplitRow.querySelector('.btn-split-pay');
          if (splitBtn) {
            splitBtn.dataset.cash = cash;
            splitBtn.dataset.paytm = paytm;
            splitBtn.dataset.isSplit = (cash > 0 && paytm > 0) ? '1' : '0';
          }

          const payContainer = currentSplitRow.querySelector('.payment-badge-display');
          if (payContainer) {
            if (cash > 0 && paytm > 0) {
              payContainer.innerHTML = `
                <span class="badge bg-success mb-1 d-block"><i class="bi bi-cash me-1"></i>Cash: ₹${cash.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                <span class="badge bg-info text-dark d-block"><i class="bi bi-bank me-1"></i>Paytm / RTGS: ₹${paytm.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
              `;
            } else if (paytm > 0) {
              payContainer.innerHTML = `<span class="badge bg-info text-dark"><i class="bi bi-bank me-1"></i>Paytm / RTGS</span>`;
            } else {
              payContainer.innerHTML = `<span class="badge bg-success"><i class="bi bi-cash me-1"></i>Cash</span>`;
            }
          }

          currentSplitRow.classList.add('row-highlight-success');
        }

        if (typeof window.showErpToast === 'function') {
          window.showErpToast(`Split payment saved: Cash ₹${cash} + Online/RTGS/Paytm ₹${paytm}`, 'success');
        }
      } else {
        alert(data.message || 'Error saving split payment.');
      }
    } catch (e) {
      console.error(e);
      alert('Error updating split payment.');
    } finally {
      btnSaveSplit.disabled = false;
      btnSaveSplit.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Save Split Allocation';
    }
  });

});
</script>

<!-- Modal: Split Payment (Cash + Paytm / RTGS / Bank) Adjustment -->
<div class="modal fade" id="modal-split-pay" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-pie-chart-fill text-info me-2"></i>Split Bill Payment (Cash + Paytm / RTGS)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Bill Overview Header -->
        <div class="p-3 bg-light rounded border mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Bill Serial:</span>
            <strong class="font-mono fs-6 text-primary" id="split-modal-bill-no">CB 01</strong>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Customer Name:</span>
            <strong id="split-modal-customer">Apex Wholesale</strong>
          </div>
          <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <span class="fw-bold">Total Net Amount:</span>
            <span class="fs-5 font-mono fw-bold text-success" id="split-modal-net">₹25,000.00</span>
          </div>
        </div>

        <!-- 1. Cash Input -->
        <div class="mb-3">
          <label class="form-label fw-semibold text-success d-flex justify-content-between">
            <span><i class="bi bi-cash-stack me-1"></i> Cash Amount (Physically Deposited)</span>
          </label>
          <div class="input-group">
            <span class="input-group-text bg-success text-white fw-bold">₹</span>
            <input type="number" step="0.01" min="0" id="split-modal-cash" class="form-control form-control-lg font-mono text-end fw-bold" placeholder="0.00">
          </div>
          <div class="form-text">Physically counted in cashier register / driver deposit.</div>
        </div>

        <!-- 2. Paytm / RTGS / Bank Input -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold text-info mb-0">
              <i class="bi bi-bank me-1"></i> Paytm / RTGS / UPI / NEFT Amount
            </label>
            <span class="badge bg-light text-dark border font-mono">Bank Settlement</span>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-info text-white fw-bold">₹</span>
            <input type="number" step="0.01" min="0" id="split-modal-paytm" class="form-control form-control-lg font-mono text-end fw-bold" placeholder="0.00">
          </div>
          <div class="form-text">Direct digital transaction transferred via Paytm QR, RTGS, NEFT or IMPS to bank.</div>
        </div>

        <!-- Balance Status Bar -->
        <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center">
          <div>
            <span class="d-block small text-muted">Allocation Status</span>
            <span id="split-modal-remaining" class="font-mono fw-bold text-success">₹0.00 Remaining</span>
          </div>
          <span id="split-modal-status-badge" class="badge bg-success">100% Balanced</span>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success fw-bold" id="btnSaveSplitPayment">
          <i class="bi bi-check-circle-fill me-1"></i> Save Split Allocation
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add Manual Bill / Payment Entry -->
<div class="modal fade" id="modal-add-manual-bill" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-plus-circle-fill text-success me-2"></i>Add Manual Bill / Payment Entry
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.verification.store_manual') }}" method="POST" id="formAddManualBill">
        @csrf
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">PSO Counter / Series <span class="text-danger">*</span></label>
              <select name="pso_code" id="manual_pso_code" class="form-select" required onchange="updateManualBillPrefix(this)">
                @foreach($psoList as $pso)
                  <option value="{{ $pso->code }}" data-prefix="{{ $pso->prefix }}" data-driver="{{ $pso->driver_name }}">
                    {{ $pso->code }} ({{ $pso->prefix }} - {{ $pso->operator_name }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Bill Serial No. <span class="text-danger">*</span></label>
              <input type="text" name="bill_no" id="manual_bill_no" class="form-control font-mono fw-bold" placeholder="e.g. CB 01" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Business Date <span class="text-danger">*</span></label>
              <input type="date" name="business_date" class="form-control font-mono" value="{{ $businessDate !== 'ALL' ? $businessDate : date('Y-m-d') }}" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
              <input type="text" name="customer_name" class="form-control" placeholder="e.g. Jodhpur Sweet Corner" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Bill Time</label>
              <input type="time" name="bill_time" class="form-control font-mono" value="{{ date('H:i') }}">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Sales Person / Driver</label>
              <select name="salesperson_id" class="form-select">
                <option value="">-- Unassigned --</option>
                @foreach($salespersons as $sp)
                  <option value="{{ $sp->id }}">{{ $sp->name }} ({{ $sp->code }})</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- Amount and Adjustments Row -->
          <div class="row g-3 mb-3 p-3 bg-light rounded border">
            <div class="col-md-4">
              <label class="form-label fw-semibold text-primary">Gross Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-primary text-white fw-bold">₹</span>
                <input type="number" step="0.01" min="0.01" name="amount" id="manual_gross_amount" class="form-control font-mono fw-bold text-end" placeholder="0.00" required oninput="calculateManualNet()">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold text-muted">Cash Discount (CD)</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₹</span>
                <input type="number" step="0.01" min="0" name="cd_amount" id="manual_cd_amount" class="form-control font-mono text-end" value="0.00" oninput="calculateManualNet()">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold text-muted">Refund / Goods Return</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₹</span>
                <input type="number" step="0.01" min="0" name="refund_amount" id="manual_refund_amount" class="form-control font-mono text-end" value="0.00" oninput="calculateManualNet()">
              </div>
            </div>
          </div>

          <!-- Payment Mode Selection -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
            <select name="payment_type" id="manual_payment_type" class="form-select form-select-lg fw-semibold" onchange="toggleManualSplitInputs(this)">
              <option value="Cash">Cash (Physical Currency Deposit)</option>
              <option value="Paytm">Paytm / RTGS / UPI (Direct Bank Settlement)</option>
              <option value="Split">✂️ Split Payment (Both Cash + Paytm / RTGS)</option>
              <option value="Check">Cheque / Demand Draft</option>
              <option value="Credit">Credit (Salesman Collection Register)</option>
            </select>
          </div>

          <!-- Conditional Split Inputs (Shown when Split is selected) -->
          <div id="manual_split_container" class="p-3 bg-light rounded border mb-3 d-none">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-pie-chart-fill text-info me-1"></i>Split Payment Allocation</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-success"><i class="bi bi-cash me-1"></i>Cash Portion (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-success text-white fw-bold">₹</span>
                  <input type="number" step="0.01" min="0" name="cash_amount" id="manual_cash_amount" class="form-control font-mono fw-bold text-end" value="0.00" oninput="syncManualSplitFromCash()">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-info"><i class="bi bi-bank me-1"></i>Paytm / RTGS Portion (₹)</label>
                <div class="input-group">
                  <span class="input-group-text bg-info text-white fw-bold">₹</span>
                  <input type="number" step="0.01" min="0" name="paytm_amount" id="manual_paytm_amount" class="form-control font-mono fw-bold text-end" value="0.00">
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks -->
          <div class="mb-2">
            <label class="form-label small fw-semibold text-muted">Remarks / Note</label>
            <input type="text" name="remark" class="form-control" placeholder="e.g. Counter manual invoice / cash & RTGS bill">
          </div>
        </div>
        <div class="modal-footer bg-light d-flex justify-content-between">
          <div class="fw-bold fs-6">
            <span class="text-muted">Net Receivable:</span>
            <span class="text-success font-mono ms-1" id="manual_net_display">₹0.00</span>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success fw-bold">
              <i class="bi bi-check-circle-fill me-1"></i> Save Manual Bill Entry
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calculateManualNet() {
  const gross = parseFloat(document.getElementById('manual_gross_amount')?.value || 0);
  const cd = parseFloat(document.getElementById('manual_cd_amount')?.value || 0);
  const refund = parseFloat(document.getElementById('manual_refund_amount')?.value || 0);
  const net = Math.max(0, gross - cd - refund);

  const netDisplay = document.getElementById('manual_net_display');
  if (netDisplay) {
    netDisplay.textContent = '₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  const payType = document.getElementById('manual_payment_type')?.value;
  if (payType === 'Split') {
    const cashInput = document.getElementById('manual_cash_amount');
    const paytmInput = document.getElementById('manual_paytm_amount');
    if (cashInput && paytmInput) {
      const currentCash = parseFloat(cashInput.value || 0);
      if (currentCash <= net) {
        paytmInput.value = (net - currentCash).toFixed(2);
      }
    }
  }
}

function toggleManualSplitInputs(selectEl) {
  const container = document.getElementById('manual_split_container');
  if (selectEl.value === 'Split') {
    container?.classList.remove('d-none');
    calculateManualNet();
  } else {
    container?.classList.add('d-none');
  }
}

function syncManualSplitFromCash() {
  const gross = parseFloat(document.getElementById('manual_gross_amount')?.value || 0);
  const cd = parseFloat(document.getElementById('manual_cd_amount')?.value || 0);
  const refund = parseFloat(document.getElementById('manual_refund_amount')?.value || 0);
  const net = Math.max(0, gross - cd - refund);

  const cash = parseFloat(document.getElementById('manual_cash_amount')?.value || 0);
  const paytmInput = document.getElementById('manual_paytm_amount');
  if (paytmInput && cash <= net) {
    paytmInput.value = (net - cash).toFixed(2);
  }
}

function updateManualBillPrefix(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  const prefix = opt?.getAttribute('data-prefix');
  const billNoInput = document.getElementById('manual_bill_no');
  if (prefix && billNoInput && !billNoInput.value) {
    billNoInput.value = prefix + ' ';
  }
}
</script>
@endsection
