@extends('layouts.app')

@section('title', 'Corrections & Returns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Corrections, Cash Discounts & Goods Returns</h4>
    <p class="text-muted mb-0">Record and audit post-billing corrections, volume cash discounts (CD), and damaged returns.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-correction">
    <i class="bi bi-plus-circle me-1"></i> Record New Correction / Return
  </button>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Total Cash Discount (CD)</small>
      <div class="fs-4 fw-bold font-mono text-danger">₹{{ number_format($totCd, 2) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Total Goods Return</small>
      <div class="fs-4 fw-bold font-mono text-danger">₹{{ number_format($totReturn, 2) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <small class="text-muted">Total Net Deductions</small>
      <div class="fs-4 fw-bold font-mono text-danger">₹{{ number_format($totNetAdj, 2) }}</div>
    </div>
  </div>
</div>

<div class="erp-table-container">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark">Approved Corrections Register & Audit Trail</span>
    <span class="badge bg-primary">{{ $corrections->count() }} Entries</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>Corr ID</th>
          <th>Bill No.</th>
          <th>Original Amount</th>
          <th>Correction Type</th>
          <th>CD Amount</th>
          <th>Goods Return</th>
          <th>Refund Amount</th>
          <th>Net Adjustment</th>
          <th>Reason / Remarks</th>
          <th>Approved By</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        @foreach($corrections as $c)
          <tr>
            <td><span class="badge bg-secondary font-mono">{{ $c->corr_code }}</span></td>
            <td><strong>{{ $c->bill_no }}</strong></td>
            <td class="font-mono">₹{{ number_format($c->original_amount, 2) }}</td>
            <td><span class="badge bg-info text-dark">{{ $c->correction_type }}</span></td>
            <td class="font-mono text-danger">{{ $c->cd_amount > 0 ? ('-₹' . number_format($c->cd_amount, 2)) : '₹0' }}</td>
            <td class="font-mono text-danger">{{ $c->goods_return_amount > 0 ? ('-₹' . number_format($c->goods_return_amount, 2)) : '₹0' }}</td>
            <td class="font-mono text-danger">{{ $c->refund_amount > 0 ? ('-₹' . number_format($c->refund_amount, 2)) : '₹0' }}</td>
            <td class="font-mono text-danger fw-bold">-₹{{ number_format(abs($c->net_adjustment), 2) }}</td>
            <td>{{ $c->reason }}</td>
            <td><strong>{{ $c->approved_by }}</strong></td>
            <td>{{ $c->created_at ? $c->created_at->format('Y-m-d H:i') : '2026-08-14' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
