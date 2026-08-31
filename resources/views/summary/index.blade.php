@extends('layouts.app')

@section('title', 'PSO Summary Matrix')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">PSO Summary Matrix</h4>
    <p class="text-muted mb-0">Granular aggregate collection metrics for PSO 1, PSO 2 (+ITC), and PSO 3.</p>
  </div>
  <button class="btn btn-outline-primary" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Print Summary
  </button>
</div>

<div class="erp-table-container mb-4">
  <div class="table-responsive">
    <table class="table erp-table align-middle text-center">
      <thead>
        <tr>
          <th class="text-start">PSO Code & Details</th>
          <th>No. of Bills</th>
          <th>Gross Sales</th>
          <th>Cash</th>
          <th>Paytm</th>
          <th>Cheque</th>
          <th>Credit</th>
          <th>Cancelled</th>
          <th>CD</th>
          <th>Refund</th>
          <th class="text-end">Net Collection</th>
        </tr>
      </thead>
      <tbody>
        @foreach($matrixRows as $row)
          <tr>
            <td class="text-start">
              <strong>{{ $row['pso']->name }}</strong>
              <div class="small text-muted">{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }}-{{ sprintf('%02d', $row['pso']->end_no) }} | Op: {{ $row['pso']->operator_name }}</div>
            </td>
            <td>{{ $row['billsCount'] }}</td>
            <td class="font-mono">₹{{ number_format($row['gross'], 2) }}</td>
            <td class="font-mono text-success">₹{{ number_format($row['cash'], 2) }}</td>
            <td class="font-mono text-info">₹{{ number_format($row['paytm'], 2) }}</td>
            <td class="font-mono text-primary">₹{{ number_format($row['check'], 2) }}</td>
            <td class="font-mono text-warning">₹{{ number_format($row['credit'], 2) }}</td>
            <td class="font-mono text-muted">₹{{ number_format($row['cancelled'], 2) }}</td>
            <td class="font-mono text-danger">{{ $row['cd'] > 0 ? ('-₹' . number_format($row['cd'], 2)) : '₹0' }}</td>
            <td class="font-mono text-danger">{{ $row['refund'] > 0 ? ('-₹' . number_format($row['refund'], 2)) : '₹0' }}</td>
            <td class="text-end font-mono text-success fw-bold">₹{{ number_format($row['net'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="fw-bold">
          <td class="text-start">MASTER TOTAL</td>
          <td>{{ $metrics['totalBillsCount'] }}</td>
          <td class="font-mono">₹{{ number_format($metrics['tallyTotal'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCash'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totPaytm'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCheck'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCredit'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCancelled'], 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($metrics['totCd'], 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($metrics['totRefund'], 2) }}</td>
          <td class="text-end font-mono text-success fs-6">₹{{ number_format($metrics['psoCollection'], 2) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Highlight Cards matching Prompt -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <h6 class="fw-bold text-primary">PSO 1 (CB 01–CB 10)</h6>
      <div class="fs-4 fw-bold font-mono text-dark">Total = ₹{{ number_format($metrics['pso1Total'], 2) }}</div>
      <small class="text-muted">10 Bills | Counter Wholesale</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <h6 class="fw-bold text-primary">PSO 2 (CB 11–CB 20 + ITC)</h6>
      <div class="fs-4 fw-bold font-mono text-dark">Total = ₹{{ number_format($metrics['pso2Total'], 2) }}</div>
      <small class="text-muted">12 Bills (incl. ITC 01, ITC 03)</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white">
      <h6 class="fw-bold text-primary">PSO 3 (RB 01–RB 10)</h6>
      <div class="fs-4 fw-bold font-mono text-dark">Total = ₹{{ number_format($metrics['pso3Total'], 2) }}</div>
      <small class="text-muted">10 Bills | Retail Walk-in Counter</small>
    </div>
  </div>
</div>
@endsection
