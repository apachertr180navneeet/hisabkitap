@extends('layouts.app')

@section('title', 'Bill Sequence Verification')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-1">Bill Sequence Verification</h4>
    <p class="text-muted mb-0">Cross-match physical bills against Tally DayBook. Identify missing, mismatched, and counter sale issues.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('verification.export') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-file-earmark-arrow-down me-1"></i> Export Verification
    </a>
    <form action="{{ route('verification.auto_verify') }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2-all me-1"></i> Auto-Verify Slips
      </button>
    </form>
  </div>
</div>

<!-- Filters Bar -->
<div class="card border p-3 mb-3 bg-white">
  <form method="GET" action="{{ route('verification.index') }}" class="row g-2 align-items-center">
    <div class="col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search Bill / Customer..." value="{{ request('search') }}">
      </div>
    </div>
    <div class="col-md-3">
      <select name="pso" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All PSOs</option>
        @foreach($psoList as $pso)
          <option value="{{ $pso->code }}" {{ request('pso') === $pso->code ? 'selected' : '' }}>{{ $pso->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Statuses</option>
        <option value="Matched" {{ request('status') === 'Matched' ? 'selected' : '' }}>Matched</option>
        <option value="Missing" {{ request('status') === 'Missing' ? 'selected' : '' }}>Missing</option>
        <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <select name="payment_type" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All Payment Types</option>
        <option value="Cash" {{ request('payment_type') === 'Cash' ? 'selected' : '' }}>Cash</option>
        <option value="Paytm" {{ request('payment_type') === 'Paytm' ? 'selected' : '' }}>Paytm / UPI</option>
        <option value="Check" {{ request('payment_type') === 'Check' ? 'selected' : '' }}>Cheque</option>
        <option value="Credit" {{ request('payment_type') === 'Credit' ? 'selected' : '' }}>Credit</option>
        <option value="Cancelled" {{ request('payment_type') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <a href="{{ route('verification.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- Verification Table -->
<div class="erp-table-container mb-4">
  <div class="table-responsive" style="max-height: 600px;">
    <table class="table erp-table align-middle">
      <thead class="sticky-top">
        <tr>
          <th>Bill No.</th>
          <th>PSO</th>
          <th>Expected</th>
          <th>Tally Found</th>
          <th>Bill Date / Time</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Payment Type</th>
          <th>CD</th>
          <th>Refund</th>
          <th>Net Amount</th>
          <th>Status</th>
          <th>Remark</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bills as $bill)
          <tr class="{{ $bill->status === 'Missing' ? 'table-danger' : '' }}">
            <td><strong>{{ $bill->bill_no }}</strong></td>
            <td><span class="badge bg-primary">{{ $bill->pso_code }}</span></td>
            <td><i class="bi bi-check-circle-fill text-success"></i></td>
            <td><i class="bi bi-check-circle-fill text-success"></i></td>
            <td>{{ $bill->business_date ? $bill->business_date->format('d-M') : '' }} <small class="text-muted">{{ $bill->bill_time }}</small></td>
            <td>{{ $bill->customer_name }}</td>
            <td class="font-mono">₹{{ number_format($bill->amount, 2) }}</td>
            <td>
              <span class="badge {{ $bill->payment_type === 'Cash' ? 'bg-success' : ($bill->payment_type === 'Paytm' ? 'bg-info text-dark' : ($bill->payment_type === 'Check' ? 'bg-primary' : ($bill->payment_type === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'))) }}">
                {{ $bill->payment_type }}
              </span>
            </td>
            <td class="font-mono {{ $bill->cd_amount > 0 ? 'text-danger' : '' }}">
              {{ $bill->cd_amount > 0 ? ('-₹' . number_format($bill->cd_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono {{ $bill->refund_amount > 0 ? 'text-danger' : '' }}">
              {{ $bill->refund_amount > 0 ? ('-₹' . number_format($bill->refund_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-success fw-bold">₹{{ number_format($bill->net_amount, 2) }}</td>
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
            <td class="small text-muted">{{ $bill->remark }}</td>
            <td class="text-end">
              @if($bill->status === 'Missing')
                <button class="btn btn-sm btn-danger btn-open-investigate" data-bill-no="{{ $bill->bill_no }}" data-customer="{{ $bill->customer_name }}" data-amount="₹{{ number_format($bill->amount, 2) }}" data-pso="{{ $bill->pso_code }}">
                  <i class="bi bi-search me-1"></i> Resolve
                </button>
              @else
                <span class="text-success small"><i class="bi bi-check2"></i> Verified</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="14" class="text-center text-muted py-5">
              <i class="bi bi-receipt-cutoff fs-3 d-block mb-1 text-primary"></i>
              No bill records found for this business date.
              <a href="{{ route('admin.import.index') }}" class="btn btn-sm btn-primary ms-2">Import Tally DayBook</a>
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6">TOTAL VERIFIED COUNT ({{ $bills->count() }} bills)</td>
          <td class="font-mono">₹{{ number_format($bills->sum('amount'), 2) }}</td>
          <td>-</td>
          <td class="font-mono text-danger">-₹{{ number_format($bills->sum('cd_amount'), 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($bills->sum('refund_amount'), 2) }}</td>
          <td class="font-mono text-success">₹{{ number_format($bills->where('status', '!=', 'Missing')->sum('net_amount'), 2) }}</td>
          <td colspan="3" class="text-end text-muted small">Active PSO records</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
@endsection
