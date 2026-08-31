@extends('layouts.app')

@section('title', 'Payment Classification')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Payment Classification & Ledger Routing</h4>
    <p class="text-muted mb-0">Classification of gross collections into distinct clearing buckets based on accounting treatment.</p>
  </div>
</div>

<!-- 5 Category Cards -->
<div class="row g-3 mb-4">
  <div class="col-lg">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-success">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-success">Received</span>
        <i class="bi bi-cash-stack fs-4 text-success"></i>
      </div>
      <h6 class="text-muted mb-1">Cash</h6>
      <div class="fs-4 fw-bold font-mono text-success">₹{{ number_format($metrics['totCash'], 2) }}</div>
      <small class="text-muted">Physically counted in cashier register</small>
    </div>
  </div>

  <div class="col-lg">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-info">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-info text-dark">Received</span>
        <i class="bi bi-qr-code-scan fs-4 text-info"></i>
      </div>
      <h6 class="text-muted mb-1">Paytm / UPI</h6>
      <div class="fs-4 fw-bold font-mono text-info">₹{{ number_format($metrics['totPaytm'], 2) }}</div>
      <small class="text-muted">Direct digital settlement to bank</small>
    </div>
  </div>

  <div class="col-lg">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-primary">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-primary">Bank Deposit Pending</span>
        <i class="bi bi-bank fs-4 text-primary"></i>
      </div>
      <h6 class="text-muted mb-1">Cheque / DD</h6>
      <div class="fs-4 fw-bold font-mono text-primary">₹{{ number_format($metrics['totCheck'], 2) }}</div>
      <small class="text-muted">Physical cheques for morning clearing</small>
    </div>
  </div>

  <div class="col-lg">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-warning">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-warning text-dark">Salesman Pending</span>
        <i class="bi bi-person-fill-exclamation fs-4 text-warning"></i>
      </div>
      <h6 class="text-muted mb-1">Credit</h6>
      <div class="fs-4 fw-bold font-mono text-warning">₹{{ number_format($metrics['totCredit'], 2) }}</div>
      <small class="text-muted">Routed to salesman collection register</small>
    </div>
  </div>

  <div class="col-lg">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-secondary">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-secondary">No Collection</span>
        <i class="bi bi-x-octagon fs-4 text-secondary"></i>
      </div>
      <h6 class="text-muted mb-1">Cancelled</h6>
      <div class="fs-4 fw-bold font-mono text-secondary">₹{{ number_format($metrics['totCancelled'], 2) }}</div>
      <small class="text-muted">Void transactions / zero settlement</small>
    </div>
  </div>
</div>

<!-- Interactive Filter Table -->
<div class="erp-table-container">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('payment.index') }}" class="btn btn-sm {{ !request('paytype') ? 'btn-dark' : 'btn-outline-dark' }}">All Classified Bills</a>
      <a href="{{ route('payment.index', ['paytype' => 'Cash']) }}" class="btn btn-sm {{ request('paytype') === 'Cash' ? 'btn-success' : 'btn-outline-success' }}">Cash</a>
      <a href="{{ route('payment.index', ['paytype' => 'Paytm']) }}" class="btn btn-sm {{ request('paytype') === 'Paytm' ? 'btn-info text-white' : 'btn-outline-info' }}">Paytm / UPI</a>
      <a href="{{ route('payment.index', ['paytype' => 'Check']) }}" class="btn btn-sm {{ request('paytype') === 'Check' ? 'btn-primary' : 'btn-outline-primary' }}">Cheque</a>
      <a href="{{ route('payment.index', ['paytype' => 'Credit']) }}" class="btn btn-sm {{ request('paytype') === 'Credit' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">Credit</a>
      <a href="{{ route('payment.index', ['paytype' => 'Cancelled']) }}" class="btn btn-sm {{ request('paytype') === 'Cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Cancelled</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>Bill No.</th>
          <th>Customer</th>
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
            <td><strong>{{ $bill->bill_no }}</strong></td>
            <td>{{ $bill->customer_name }}</td>
            <td>
              <span class="badge {{ $bill->payment_type === 'Cash' ? 'bg-success' : ($bill->payment_type === 'Paytm' ? 'bg-info text-dark' : ($bill->payment_type === 'Check' ? 'bg-primary' : ($bill->payment_type === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'))) }}">
                {{ $bill->payment_type }}
              </span>
            </td>
            <td class="font-mono">₹{{ number_format($bill->amount, 2) }}</td>
            <td class="font-mono text-danger">
              {{ ($bill->cd_amount + $bill->refund_amount) > 0 ? ('-₹' . number_format($bill->cd_amount + $bill->refund_amount, 2)) : '₹0' }}
            </td>
            <td class="font-mono text-success fw-bold">₹{{ number_format($bill->net_amount, 2) }}</td>
            <td class="small text-muted">
              @if($bill->payment_type === 'Cash')
                Dr. Cash Counter A/c
              @elseif($bill->payment_type === 'Paytm')
                Dr. Paytm Nodal Escrow A/c
              @elseif($bill->payment_type === 'Check')
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
              @else
                <span class="badge bg-success">Settled</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-wallet2 fs-3 d-block mb-1 text-primary"></i>
              No payment transactions recorded yet.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
