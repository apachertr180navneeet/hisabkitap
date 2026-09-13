@extends('layouts.app')

@section('title', 'Payment Classification')

@section('content')
@php
    $routeName = request()->routeIs('admin.*') ? 'admin.payment.index' : 'payment.index';
    $currentPaytype = request('paytype', 'ALL');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-1">Payment Classification & Ledger Routing</h4>
    <p class="text-muted mb-0">Classification of gross collections into distinct clearing buckets based on accounting treatment.</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.payment.export_excel' : 'payment.export_excel', request()->query()) }}" class="btn btn-success btn-sm">
      <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.payment.export_pdf' : 'payment.export_pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF / Print
    </a>
    @if(request()->filled('date') || request()->filled('pso') || request()->filled('search') || (request()->filled('paytype') && request('paytype') !== 'ALL'))
      <a href="{{ route($routeName) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
      </a>
    @endif
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.verification.index' : 'verification.index', ['date' => $businessDate]) }}" class="btn btn-primary btn-sm">
      <i class="bi bi-receipt-cutoff me-1"></i> Bill Verification
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="card border p-3 mb-4 bg-white shadow-sm">
  <form method="GET" action="{{ route($routeName) }}" class="row g-2 align-items-center">
    <input type="hidden" name="paytype" value="{{ $currentPaytype }}">

    <div class="col-md-3 col-sm-6">
      <label class="form-label small text-muted mb-1 fw-semibold">Business Date</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
        <input type="date" name="date" class="form-control font-mono" value="{{ ($businessDate && $businessDate !== 'ALL') ? $businessDate : '' }}" placeholder="YYYY-MM-DD" onchange="this.form.submit()" title="Select Business Date">
        @if($businessDate !== 'ALL')
          <a href="{{ route($routeName, array_merge(request()->query(), ['date' => 'ALL'])) }}" class="btn btn-outline-secondary btn-sm" title="Show All Dates">All</a>
        @else
          <span class="input-group-text bg-primary text-white fw-bold">ALL</span>
        @endif
      </div>
    </div>

    @if(!empty($availableDates) && count($availableDates) > 0)
    <div class="col-md-2 col-sm-6">
      <label class="form-label small text-muted mb-1 fw-semibold">Recorded Dates</label>
      <select class="form-select form-select-sm font-mono" onchange="if(this.value){ window.location.href='{{ route($routeName, array_merge(request()->query(), ['date' => '__DATE__'])) }}'.replace('__DATE__', this.value); }">
        <option value="">-- Jump to Date --</option>
        <option value="ALL" {{ $businessDate === 'ALL' ? 'selected' : '' }}>All Dates (Combined)</option>
        @foreach($availableDates as $d)
          <option value="{{ $d }}" {{ $businessDate === $d ? 'selected' : '' }}>
            {{ date('d/m/Y', strtotime($d)) }}
          </option>
        @endforeach
      </select>
    </div>
    @endif

    <div class="col-md-2 col-sm-6">
      <label class="form-label small text-muted mb-1 fw-semibold">PSO Counter</label>
      <select name="pso" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All PSOs</option>
        @foreach($psoList as $pso)
          <option value="{{ $pso->code }}" {{ request('pso') === $pso->code ? 'selected' : '' }}>
            {{ $pso->code }} ({{ $pso->prefix }})
          </option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3 col-sm-6">
      <label class="form-label small text-muted mb-1 fw-semibold">Search Bills</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search Bill #, Customer, Salesman..." value="{{ request('search') }}">
      </div>
    </div>

    <div class="col-md-2 col-sm-12 d-flex align-items-end gap-1">
      <button type="submit" class="btn btn-primary btn-sm flex-fill">
        <i class="bi bi-filter me-1"></i> Apply
      </button>
      @if(request()->filled('date') || request()->filled('pso') || request()->filled('search') || (request()->filled('paytype') && request('paytype') !== 'ALL'))
        <a href="{{ route($routeName) }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
          <i class="bi bi-x-lg"></i>
        </a>
      @endif
    </div>
  </form>
</div>

<!-- 5 Category Summary Cards -->
<div class="row g-3 mb-4">
  <!-- Cash -->
  <div class="col-md-6 col-lg">
    <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Cash'])) }}" class="text-decoration-none">
      <div class="card border p-3 bg-white h-100 border-start border-4 border-success shadow-sm card-hover {{ $currentPaytype === 'Cash' ? 'ring-active' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-success">Received ({{ $metrics['countCash'] ?? 0 }})</span>
          <i class="bi bi-cash-stack fs-4 text-success"></i>
        </div>
        <h6 class="text-muted mb-1">Cash</h6>
        <div class="fs-4 fw-bold font-mono text-success">₹{{ number_format($metrics['totCash'] ?? 0, 2) }}</div>
        <small class="text-muted">Physically counted in cashier register</small>
      </div>
    </a>
  </div>

  <!-- Paytm / UPI -->
  <div class="col-md-6 col-lg">
    <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Paytm'])) }}" class="text-decoration-none">
      <div class="card border p-3 bg-white h-100 border-start border-4 border-info shadow-sm card-hover {{ $currentPaytype === 'Paytm' ? 'ring-active' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-info text-dark">Received ({{ $metrics['countPaytm'] ?? 0 }})</span>
          <i class="bi bi-qr-code-scan fs-4 text-info"></i>
        </div>
        <h6 class="text-muted mb-1">Paytm / UPI</h6>
        <div class="fs-4 fw-bold font-mono text-info">₹{{ number_format($metrics['totPaytm'] ?? 0, 2) }}</div>
        <small class="text-muted">Direct digital settlement to bank</small>
      </div>
    </a>
  </div>

  <!-- Cheque / DD -->
  <div class="col-md-6 col-lg">
    <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Check'])) }}" class="text-decoration-none">
      <div class="card border p-3 bg-white h-100 border-start border-4 border-primary shadow-sm card-hover {{ $currentPaytype === 'Check' ? 'ring-active' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-primary">Bank Deposit ({{ $metrics['countCheck'] ?? 0 }})</span>
          <i class="bi bi-bank fs-4 text-primary"></i>
        </div>
        <h6 class="text-muted mb-1">Cheque / DD</h6>
        <div class="fs-4 fw-bold font-mono text-primary">₹{{ number_format($metrics['totCheck'] ?? 0, 2) }}</div>
        <small class="text-muted">Physical cheques for morning clearing</small>
      </div>
    </a>
  </div>

  <!-- Credit -->
  <div class="col-md-6 col-lg">
    <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Credit'])) }}" class="text-decoration-none">
      <div class="card border p-3 bg-white h-100 border-start border-4 border-warning shadow-sm card-hover {{ $currentPaytype === 'Credit' ? 'ring-active' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-warning text-dark">Salesman Pending ({{ $metrics['countCredit'] ?? 0 }})</span>
          <i class="bi bi-person-fill-exclamation fs-4 text-warning"></i>
        </div>
        <h6 class="text-muted mb-1">Credit</h6>
        <div class="fs-4 fw-bold font-mono text-warning">₹{{ number_format($metrics['totCredit'] ?? 0, 2) }}</div>
        <small class="text-muted">Routed to salesman collection register</small>
      </div>
    </a>
  </div>

  <!-- Cancelled -->
  <div class="col-md-6 col-lg">
    <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Cancelled'])) }}" class="text-decoration-none">
      <div class="card border p-3 bg-white h-100 border-start border-4 border-secondary shadow-sm card-hover {{ $currentPaytype === 'Cancelled' ? 'ring-active' : '' }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-secondary">Void ({{ $metrics['countCancelled'] ?? 0 }})</span>
          <i class="bi bi-x-octagon fs-4 text-secondary"></i>
        </div>
        <h6 class="text-muted mb-1">Cancelled</h6>
        <div class="fs-4 fw-bold font-mono text-secondary">₹{{ number_format($metrics['totCancelled'] ?? 0, 2) }}</div>
        <small class="text-muted">Void transactions / zero settlement</small>
      </div>
    </a>
  </div>
</div>

<!-- Interactive Filter Table -->
<div class="erp-table-container">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'ALL'])) }}" class="btn btn-sm {{ (!$currentPaytype || $currentPaytype === 'ALL') ? 'btn-dark' : 'btn-outline-dark' }}">
        All Classified Bills <span class="badge bg-secondary ms-1">{{ $metrics['totalBillsCount'] ?? $bills->count() }}</span>
      </a>
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Cash'])) }}" class="btn btn-sm {{ $currentPaytype === 'Cash' ? 'btn-success' : 'btn-outline-success' }}">
        Cash <span class="badge bg-light text-dark ms-1">{{ $metrics['countCash'] ?? 0 }}</span>
      </a>
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Paytm'])) }}" class="btn btn-sm {{ $currentPaytype === 'Paytm' ? 'btn-info text-white' : 'btn-outline-info' }}">
        Paytm / UPI <span class="badge bg-light text-dark ms-1">{{ $metrics['countPaytm'] ?? 0 }}</span>
      </a>
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Check'])) }}" class="btn btn-sm {{ $currentPaytype === 'Check' ? 'btn-primary' : 'btn-outline-primary' }}">
        Cheque <span class="badge bg-light text-dark ms-1">{{ $metrics['countCheck'] ?? 0 }}</span>
      </a>
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Credit'])) }}" class="btn btn-sm {{ $currentPaytype === 'Credit' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
        Credit <span class="badge bg-light text-dark ms-1">{{ $metrics['countCredit'] ?? 0 }}</span>
      </a>
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Cancelled'])) }}" class="btn btn-sm {{ $currentPaytype === 'Cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">
        Cancelled <span class="badge bg-light text-dark ms-1">{{ $metrics['countCancelled'] ?? 0 }}</span>
      </a>
      @if(($metrics['countSplit'] ?? 0) > 0)
      <a href="{{ route($routeName, array_merge(request()->query(), ['paytype' => 'Split'])) }}" class="btn btn-sm {{ $currentPaytype === 'Split' ? 'btn-purple text-white' : 'btn-outline-secondary' }}" style="{{ $currentPaytype === 'Split' ? 'background-color: #6f42c1; border-color: #6f42c1;' : '' }}">
        Split (Cash+Paytm) <span class="badge bg-light text-dark ms-1">{{ $metrics['countSplit'] }}</span>
      </a>
      @endif
    </div>

    <div class="small text-muted font-mono">
      Showing <strong>{{ $bills->count() }}</strong> transaction(s) | Date: <strong>{{ ($businessDate && $businessDate !== 'ALL') ? date('d/m/Y', strtotime($businessDate)) : 'ALL DATES' }}</strong>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle mb-0">
      <thead>
        <tr>
          <th>Bill No.</th>
          @if($businessDate === 'ALL')
            <th>Date</th>
          @endif
          <th>PSO</th>
          <th>Customer</th>
          <th>Salesman</th>
          <th>Payment Type</th>
          <th>Gross Amount</th>
          <th>CD / Adjustments</th>
          <th>Net Receivable</th>
          <th>Accounting Rule</th>
          <th>Settlement Flag</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $bill)
          <tr>
            <td>
              <strong class="font-mono text-dark">{{ $bill->bill_no }}</strong>
              @if($bill->is_post_cutoff)
                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">Post-Cutoff</span>
              @endif
            </td>
            @if($businessDate === 'ALL')
              <td class="font-mono small">{{ $bill->business_date ? date('d/m/Y', strtotime($bill->business_date)) : '-' }}</td>
            @endif
            <td>
              <span class="badge bg-light text-dark border">{{ $bill->pso_code ?: 'N/A' }}</span>
            </td>
            <td>
              <div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $bill->customer_name }}">{{ $bill->customer_name }}</div>
            </td>
            <td class="small text-muted">
              {{ $bill->salesman_name ?: '-' }}
            </td>
            <td>
              @if($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0))
                <span class="badge bg-dark">Split Payment</span>
                <div class="small mt-1 font-mono">
                  <span class="text-success">Cash: ₹{{ number_format($bill->cash_amount, 2) }}</span><br>
                  <span class="text-info">Paytm: ₹{{ number_format($bill->paytm_amount, 2) }}</span>
                </div>
              @else
                <span class="badge {{ $bill->payment_type === 'Cash' ? 'bg-success' : ($bill->payment_type === 'Paytm' ? 'bg-info text-dark' : (($bill->payment_type === 'Check' || $bill->payment_type === 'Cheque') ? 'bg-primary' : ($bill->payment_type === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'))) }}">
                  {{ ($bill->payment_type === 'Check' || $bill->payment_type === 'Cheque') ? 'Cheque' : $bill->payment_type }}
                </span>
              @endif
            </td>
            <td class="font-mono fw-semibold">₹{{ number_format($bill->amount, 2) }}</td>
            <td class="font-mono text-danger">
              {{ ($bill->cd_amount + $bill->refund_amount) > 0 ? ('-₹' . number_format($bill->cd_amount + $bill->refund_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-success fw-bold">₹{{ number_format($bill->net_amount, 2) }}</td>
            <td class="small text-muted">
              @if($bill->is_split_payment || ($bill->cash_amount > 0 && $bill->paytm_amount > 0))
                Dr. Cash Counter & Paytm Escrow
              @elseif($bill->payment_type === 'Cash')
                Dr. Cash Counter A/c
              @elseif($bill->payment_type === 'Paytm')
                Dr. Paytm Nodal Escrow A/c
              @elseif($bill->payment_type === 'Check' || $bill->payment_type === 'Cheque')
                Dr. Cheques in Hand A/c
              @elseif($bill->payment_type === 'Credit')
                Dr. Sundry Debtors ({{ $bill->customer_name }})
              @else
                Void Transaction
              @endif
            </td>
            <td>
              @if($bill->payment_type === 'Cancelled')
                <span class="badge bg-secondary">Void</span>
              @elseif($bill->payment_type === 'Credit')
                <span class="badge bg-warning text-dark">Pending Recovery</span>
              @elseif($bill->status === 'Missing')
                <span class="badge bg-danger">Missing Slip</span>
              @else
                <span class="badge bg-success">Settled</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="{{ $businessDate === 'ALL' ? 11 : 10 }}" class="text-center text-muted py-5">
              <i class="bi bi-wallet2 fs-1 d-block mb-2 text-primary opacity-50"></i>
              <h5 class="fw-bold text-dark mb-1">No payment transactions found</h5>
              <p class="text-muted mb-3">
                @if($businessDate && $businessDate !== 'ALL')
                  No bills recorded for date <strong>{{ date('d/m/Y', strtotime($businessDate)) }}</strong>{{ request('paytype') && request('paytype') !== 'ALL' ? ' with payment type ' . request('paytype') : '' }}.
                @else
                  No payment transactions match the selected filters.
                @endif
              </p>
              <div class="d-flex justify-content-center gap-2 flex-wrap">
                @if($businessDate !== 'ALL')
                  <a href="{{ route($routeName, array_merge(request()->query(), ['date' => 'ALL'])) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-calendar-range me-1"></i> View All Dates
                  </a>
                @endif
                <a href="{{ route(request()->routeIs('admin.*') ? 'admin.import.index' : 'import.index') }}" class="btn btn-primary btn-sm">
                  <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import DayBook from Tally Excel
                </a>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<style>
  .card-hover {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
  }
  .card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
  }
  .ring-active {
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.35) !important;
  }
</style>
@endsection
