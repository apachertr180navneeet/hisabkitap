@extends('layouts.app')

@section('title', 'Corrections & Returns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-1">Corrections, Cash Discounts & Goods Returns</h4>
    <p class="text-muted mb-0">Record and audit post-billing corrections, volume cash discounts (CD), goods returns, and cancelled bills.</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.corrections.export_excel' : 'corrections.export_excel', ['date' => $selectedDate ?? 'ALL']) }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.corrections.export_pdf' : 'corrections.export_pdf', ['date' => $selectedDate ?? 'ALL']) }}" target="_blank" class="btn btn-danger">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF / Print
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-correction">
      <i class="bi bi-plus-circle me-1"></i> Record New Correction / Return
    </button>
  </div>
</div>

<!-- Date Filter Toolbar -->
<div class="card border-0 shadow-sm mb-4 bg-light">
  <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="text-muted small fw-semibold"><i class="bi bi-calendar3 me-1"></i> Filter by Date:</span>
      <a href="{{ route(request()->routeIs('admin.*') ? 'admin.corrections.index' : 'corrections.index', ['date' => 'ALL']) }}" 
         class="btn btn-sm {{ ($selectedDate ?? 'ALL') === 'ALL' ? 'btn-primary' : 'btn-outline-secondary bg-white' }}">
        All Dates
      </a>
      @if(!empty($availableDates))
        @foreach(array_slice($availableDates, 0, 5) as $d)
          <a href="{{ route(request()->routeIs('admin.*') ? 'admin.corrections.index' : 'corrections.index', ['date' => $d]) }}" 
             class="btn btn-sm {{ ($selectedDate ?? '') === $d ? 'btn-primary' : 'btn-outline-secondary bg-white' }}">
            {{ date('d M Y', strtotime($d)) }}
          </a>
        @endforeach
      @endif
    </div>

    @if(!empty($availableDates) && count($availableDates) > 5)
      <form method="GET" action="{{ route(request()->routeIs('admin.*') ? 'admin.corrections.index' : 'corrections.index') }}" class="d-flex align-items-center gap-1">
        <select name="date" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="ALL" {{ ($selectedDate ?? 'ALL') === 'ALL' ? 'selected' : '' }}>-- More Dates --</option>
          @foreach($availableDates as $d)
            <option value="{{ $d }}" {{ ($selectedDate ?? '') === $d ? 'selected' : '' }}>
              {{ date('d/m/Y (D)', strtotime($d)) }}
            </option>
          @endforeach
        </select>
      </form>
    @endif
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="card border p-3 bg-white shadow-sm h-100">
      <small class="text-muted fw-semibold">Total Cash Discount (CD)</small>
      <div class="fs-4 fw-bold font-mono text-danger mt-1">₹{{ number_format($totCd ?? 0, 2) }}</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card border p-3 bg-white shadow-sm h-100">
      <small class="text-muted fw-semibold">Total Goods Return</small>
      <div class="fs-4 fw-bold font-mono text-danger mt-1">₹{{ number_format($totReturn ?? 0, 2) }}</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card border p-3 bg-white shadow-sm h-100">
      <small class="text-muted fw-semibold">Total Refunds / Cancelled</small>
      <div class="fs-4 fw-bold font-mono text-warning-emphasis mt-1">₹{{ number_format($totRefund ?? 0, 2) }}</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card border p-3 bg-white shadow-sm h-100">
      <small class="text-muted fw-semibold">Total Net Deductions</small>
      <div class="fs-4 fw-bold font-mono text-danger mt-1">₹{{ number_format($totNetAdj ?? 0, 2) }}</div>
    </div>
  </div>
</div>

<div class="erp-table-container shadow-sm">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <span class="fw-semibold text-dark">Approved Corrections & Deductions Register</span>
      <span class="text-muted small ms-2">
        (Filter: <strong class="text-primary">{{ ($selectedDate ?? 'ALL') === 'ALL' ? 'All Dates' : date('d/m/Y', strtotime($selectedDate)) }}</strong>)
      </span>
    </div>
    <span class="badge bg-primary px-3 py-2">{{ $corrections->count() }} Entries Found</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>Adj ID</th>
          <th>Bill No.</th>
          <th>Customer / Party</th>
          <th>PSO</th>
          <th class="text-end">Original Amount</th>
          <th>Adjustment Type</th>
          <th class="text-end">CD Amount</th>
          <th class="text-end">Goods Return</th>
          <th class="text-end">Refund</th>
          <th class="text-end">Net Deduction</th>
          <th>Reason / Remarks</th>
          <th>Recorded By</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        @forelse($corrections as $c)
          <tr>
            <td>
              <span class="badge {{ $c->is_auto ? 'bg-secondary' : 'bg-primary' }} font-mono">
                {{ $c->corr_code }}
              </span>
            </td>
            <td><strong class="font-mono">{{ $c->bill_no }}</strong></td>
            <td>
              <span class="fw-semibold text-dark">{{ $c->customer_name ?? '—' }}</span>
            </td>
            <td>
              <span class="badge bg-light text-dark border font-mono">{{ $c->pso_code ?? '—' }}</span>
            </td>
            <td class="font-mono text-end">₹{{ number_format($c->original_amount, 2) }}</td>
            <td>
              @php
                $badgeClass = 'bg-info text-dark';
                if (str_contains(strtolower($c->correction_type), 'cancel')) {
                  $badgeClass = 'bg-danger text-white';
                } elseif (str_contains(strtolower($c->correction_type), 'discount') || str_contains(strtolower($c->correction_type), 'cd')) {
                  $badgeClass = 'bg-warning text-dark';
                } elseif (str_contains(strtolower($c->correction_type), 'return')) {
                  $badgeClass = 'bg-secondary text-white';
                }
              @endphp
              <span class="badge {{ $badgeClass }}">{{ $c->correction_type }}</span>
            </td>
            <td class="font-mono text-danger text-end">
              {{ $c->cd_amount > 0 ? ('-₹' . number_format($c->cd_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-danger text-end">
              {{ $c->goods_return_amount > 0 ? ('-₹' . number_format($c->goods_return_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-danger text-end">
              {{ $c->refund_amount > 0 ? ('-₹' . number_format($c->refund_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-danger fw-bold text-end">
              -₹{{ number_format(abs($c->net_adjustment), 2) }}
            </td>
            <td style="max-width: 200px;" class="text-truncate" title="{{ $c->reason }}">{{ $c->reason }}</td>
            <td><small class="fw-semibold text-secondary">{{ $c->approved_by }}</small></td>
            <td class="text-nowrap small text-muted">
              @if(is_object($c->created_at))
                {{ $c->created_at->format('d/m/Y H:i') }}
              @elseif($c->created_at)
                {{ date('d/m/Y H:i', strtotime($c->created_at)) }}
              @else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="13" class="text-center text-muted py-5">
              <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-primary opacity-50"></i>
              <h6 class="fw-bold mb-1">No Corrections or Deductions Found</h6>
              <p class="small text-muted mb-0">No cash discounts, goods returns, or cancelled bills recorded for the selected date filter.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<datalist id="correction-bill-list">
  @if(isset($bills))
    @foreach($bills as $b)
      <option value="{{ $b->bill_no }}">{{ $b->customer_name }} (₹{{ number_format($b->amount, 2) }})</option>
    @endforeach
  @endif
</datalist>
@endsection
