@extends('layouts.app')

@section('title', $pso->code . ' Detail Breakdown | PSO Summary')

@section('content')
<!-- BREADCRUMB & HEADER -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.summary.index') }}" class="text-decoration-none">PSO Summary Matrix</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $pso->code }} Details</li>
      </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
      <h3 class="fw-bold mb-0 text-dark">{{ $pso->code }} &mdash; Detail Summary</h3>
      <span class="badge bg-primary fs-6 font-mono">{{ $pso->code }}</span>
      <span class="badge bg-light text-muted border font-mono">Date: {{ date('d M Y', strtotime($businessDate)) }}</span>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center">
    <!-- PSO SWITCHER DROPDOWN -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-arrow-left-right me-1"></i> Switch PSO: <strong>{{ $pso->code }}</strong>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        @foreach($allPsoConfigs as $otherPso)
          <li>
            <a class="dropdown-item d-flex justify-content-between align-items-center {{ $otherPso->id === $pso->id ? 'active fw-bold' : '' }}" href="{{ route('admin.summary.show', $otherPso->id) }}">
              <span>{{ $otherPso->code }} &mdash; {{ $otherPso->operator_name }}</span>
              <span class="badge {{ $otherPso->id === $pso->id ? 'bg-light text-dark' : 'bg-secondary' }} ms-2 font-mono">{{ $otherPso->prefix }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.summary.export_single_excel' : 'summary.export_single_excel', $pso->id) }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.summary.export_single_pdf' : 'summary.export_single_pdf', $pso->id) }}" target="_blank" class="btn btn-danger">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF / Print
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.verification.index' : 'verification.index', ['pso' => $pso->code]) }}" class="btn btn-primary">
      <i class="bi bi-receipt-cutoff me-1"></i> Open in Verification
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.summary.index' : 'summary.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Back to Matrix
    </a>
  </div>
</div>

<!-- PSO PROFILE & CONFIG CARD -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3 p-md-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3 border-end">
        <div class="text-muted small">Assigned Prefix & Range</div>
        <div class="fs-5 fw-bold font-mono text-primary mt-1">
          {{ $pso->prefix }} {{ sprintf('%02d', $pso->start_no) }} &ndash; {{ $pso->prefix }} {{ sprintf('%02d', $pso->end_no) }}
        </div>
        <div class="small text-muted mt-1">
          <i class="bi bi-calendar3 me-1"></i>FY: {{ $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}
        </div>
      </div>

      <div class="col-md-3 border-end">
        <div class="text-muted small">Operator & Counter</div>
        <div class="fs-6 fw-bold text-dark mt-1">
          <i class="bi bi-person-fill text-primary me-1"></i>{{ $pso->operator_name }}
        </div>
        @if($pso->description)
          <div class="small text-muted mt-1">{{ $pso->description }}</div>
        @endif
      </div>

      <div class="col-md-3 border-end">
        <div class="text-muted small">Driver & Vehicle (Gadi)</div>
        <div class="fs-6 fw-semibold text-dark mt-1">
          @if($pso->driver_name)
            <div><i class="bi bi-truck text-secondary me-1"></i>Driver: {{ $pso->driver_name }}</div>
          @else
            <div class="text-muted small">No Driver assigned</div>
          @endif
          @if($pso->gadi_number)
            <span class="badge bg-light text-dark border font-mono mt-1">Gadi: {{ $pso->gadi_number }}</span>
          @endif
        </div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">Special Series / Rules</div>
        <div class="mt-1">
          @if(!empty($pso->specials) && count($pso->specials) > 0)
            @foreach($pso->specials as $spec)
              <span class="badge bg-info text-dark font-mono me-1">{{ $spec }}</span>
            @endforeach
          @else
            <span class="text-muted small">Standard counter series</span>
          @endif
        </div>
        <div class="mt-2">
          <span class="badge {{ $stats['missingCount'] > 0 ? 'bg-danger' : 'bg-success' }}">
            {{ $stats['matchedCount'] }} / {{ $stats['totalBills'] }} Bills Verified
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- FINANCIAL BREAKDOWN KPI TILES -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">GROSS SALES</span>
        <div class="badge bg-primary-subtle text-primary">{{ $stats['totalBills'] }} Bills</div>
      </div>
      <div class="fs-3 fw-bold font-mono text-dark mt-2">₹{{ number_format($stats['gross'], 2) }}</div>
      <div class="small text-muted mt-1">Total invoiced value</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100 border-start border-success border-4">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold text-success">NET COLLECTION</span>
        <i class="bi bi-cash-stack text-success fs-5"></i>
      </div>
      <div class="fs-3 fw-bold font-mono text-success mt-2">₹{{ number_format($stats['net'], 2) }}</div>
      <div class="small text-muted mt-1">Actual payable collection</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">CASH IN HAND</span>
        <i class="bi bi-wallet2 text-success fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono text-success mt-2">₹{{ number_format($stats['cash'], 2) }}</div>
      <div class="small text-muted mt-1">Physical currency collected</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">DIGITAL & BANK</span>
        <i class="bi bi-qr-code text-info fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono text-info mt-2">₹{{ number_format($stats['paytm'] + $stats['check'], 2) }}</div>
      <div class="small text-muted mt-1">Paytm: ₹{{ number_format($stats['paytm'], 2) }} | Cheque: ₹{{ number_format($stats['check'], 2) }}</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">CREDIT EXTENDED</span>
        <i class="bi bi-clock-history text-warning fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono text-warning mt-2">₹{{ number_format($stats['credit'], 2) }}</div>
      <div class="small text-muted mt-1">Deferred payment collection</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">DISCOUNTS & RETURNS</span>
        <i class="bi bi-tag text-danger fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono text-danger mt-2">-₹{{ number_format($stats['cd'] + $stats['refund'], 2) }}</div>
      <div class="small text-muted mt-1">CD: ₹{{ number_format($stats['cd'], 2) }} | Refund: ₹{{ number_format($stats['refund'], 2) }}</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">CANCELLED BILLS</span>
        <i class="bi bi-x-circle text-secondary fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono text-muted mt-2">₹{{ number_format($stats['cancelled'], 2) }}</div>
      <div class="small text-muted mt-1">{{ $stats['cancelledCount'] }} Cancelled Bills</div>
    </div>
  </div>

  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm p-3 bg-white h-100">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small fw-semibold">VERIFICATION HEALTH</span>
        <i class="bi bi-shield-check text-primary fs-5"></i>
      </div>
      <div class="fs-4 fw-bold font-mono mt-2 {{ $stats['missingCount'] > 0 ? 'text-danger' : 'text-success' }}">
        {{ $stats['missingCount'] > 0 ? $stats['missingCount'] . ' Missing' : '100% Matched' }}
      </div>
      <div class="small text-muted mt-1">{{ $stats['matchedCount'] }} verified / {{ $stats['totalBills'] }} total</div>
    </div>
  </div>
</div>

<!-- BILLS LIST CARD & FILTER BAR -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Individual Bills for {{ $pso->code }}</h5>
      <small class="text-muted">Showing {{ $bills->count() }} bills registered under this PSO series</small>
    </div>

    <!-- INLINE FILTER FORM -->
    <form method="GET" action="{{ route('admin.summary.show', $pso->id) }}" class="d-flex flex-wrap gap-2 align-items-center">
      <select name="payment_type" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
        <option value="ALL" {{ request('payment_type') == 'ALL' ? 'selected' : '' }}>All Payment Types</option>
        <option value="Cash" {{ request('payment_type') == 'Cash' ? 'selected' : '' }}>Cash</option>
        <option value="Paytm" {{ request('payment_type') == 'Paytm' ? 'selected' : '' }}>Paytm</option>
        <option value="Check" {{ request('payment_type') == 'Check' ? 'selected' : '' }}>Cheque</option>
        <option value="Credit" {{ request('payment_type') == 'Credit' ? 'selected' : '' }}>Credit</option>
        <option value="Cancelled" {{ request('payment_type') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>

      <select name="status" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
        <option value="ALL" {{ request('status') == 'ALL' ? 'selected' : '' }}>All Statuses</option>
        <option value="Matched" {{ request('status') == 'Matched' ? 'selected' : '' }}>Matched</option>
        <option value="Missing" {{ request('status') == 'Missing' ? 'selected' : '' }}>Missing</option>
        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>

      <div class="input-group input-group-sm" style="width: 220px;">
        <input type="text" name="search" class="form-control" placeholder="Search bill / party..." value="{{ request('search') }}">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
      </div>

      @if(request()->hasAny(['payment_type', 'status', 'search']))
        <a href="{{ route('admin.summary.show', $pso->id) }}" class="btn btn-sm btn-outline-danger" title="Clear Filters">
          <i class="bi bi-x-circle"></i>
        </a>
      @endif
    </form>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle text-center mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Bill No</th>
          <th>Time</th>
          <th class="text-start">Customer / Party Name</th>
          <th>Sales Person</th>
          <th>Gross Amt</th>
          <th>Payment Mode</th>
          <th>CD</th>
          <th>Refund</th>
          <th class="text-end">Net Amount</th>
          <th>Status</th>
          <th>Remark</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $index => $b)
          <tr class="bill-row">
            <td class="text-muted">{{ $index + 1 }}</td>
            <td class="fw-bold font-mono text-primary">
              {{ $b->bill_no }}
            </td>
            <td class="font-mono text-muted small">
              {{ $b->bill_time ? date('H:i', strtotime($b->bill_time)) : '—' }}
            </td>
            <td class="text-start fw-semibold text-dark">
              {{ $b->customer_name }}
            </td>
            <td>
              @if($b->salesman_name)
                <span class="badge bg-light text-dark border"><i class="bi bi-person me-1"></i>{{ $b->salesman_name }}</span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td class="font-mono">₹{{ number_format($b->amount, 2) }}</td>
            <td>
              @php
                $badgeClass = match($b->payment_type) {
                  'Cash' => 'bg-success text-white',
                  'Paytm' => 'bg-info text-dark',
                  'Check' => 'bg-primary text-white',
                  'Credit' => 'bg-warning text-dark',
                  'Cancelled' => 'bg-secondary text-white',
                  default => 'bg-light text-dark border'
                };
              @endphp
              <span class="badge {{ $badgeClass }}">{{ $b->payment_type }}</span>
            </td>
            <td class="font-mono text-danger">
              {{ $b->cd_amount > 0 ? ('-₹' . number_format($b->cd_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-danger">
              {{ $b->refund_amount > 0 ? ('-₹' . number_format($b->refund_amount, 2)) : '₹0' }}
            </td>
            <td class="text-end font-mono text-success fw-bold">
              ₹{{ number_format($b->net_amount, 2) }}
            </td>
            <td>
              @if($b->status === 'Matched')
                <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check2-circle me-1"></i>Matched</span>
              @elseif($b->status === 'Cancelled')
                <span class="badge bg-secondary">Cancelled</span>
              @else
                <span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $b->status }}</span>
              @endif
            </td>
            <td class="text-muted small">
              {{ $b->remark ?: '—' }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="12" class="text-center py-5 text-muted">
              <i class="bi bi-receipt display-6 d-block mb-2 text-secondary opacity-50"></i>
              No bills found matching your filter criteria for <strong>{{ $pso->code }}</strong>.
            </td>
          </tr>
        @endforelse
      </tbody>
      @if($bills->count() > 0)
      <tfoot class="table-light fw-bold">
        <tr>
          <td colspan="5" class="text-start">SUBTOTAL ({{ $bills->count() }} bills)</td>
          <td class="font-mono">₹{{ number_format($bills->sum('amount'), 2) }}</td>
          <td></td>
          <td class="font-mono text-danger">-₹{{ number_format($bills->sum('cd_amount'), 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($bills->sum('refund_amount'), 2) }}</td>
          <td class="text-end font-mono text-success fs-6">₹{{ number_format($bills->where('status', '!=', 'Missing')->sum('net_amount'), 2) }}</td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
      @endif
    </table>
  </div>
</div>
@endsection
