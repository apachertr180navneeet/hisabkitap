@extends('layouts.app')

@section('title', 'PSO Summary Matrix')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h4 class="fw-bold mb-1">PSO Summary Matrix</h4>
    <p class="text-muted mb-0">Granular aggregate collection metrics for closed PSO counters (PSO 1, PSO 2, PSO 3).</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <!-- Date Filter Dropdown -->
    <form method="GET" action="{{ route(request()->routeIs('admin.*') ? 'admin.summary.index' : 'summary.index') }}" class="d-inline-block">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-white"><i class="bi bi-calendar3 text-primary"></i></span>
        <select name="date" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
          @if(!empty($availableDates))
            @foreach($availableDates as $d)
              <option value="{{ $d }}" {{ $businessDate === $d ? 'selected' : '' }}>
                {{ date('d/m/Y', strtotime($d)) }}
              </option>
            @endforeach
            <option value="ALL" {{ $businessDate === 'ALL' ? 'selected' : '' }}>All Available Dates</option>
          @else
            <option value="{{ $businessDate }}" selected>{{ date('d/m/Y', strtotime($businessDate)) }}</option>
          @endif
        </select>
      </div>
    </form>

    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.summary.export_excel' : 'summary.export_excel', ['date' => $businessDate]) }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.summary.export_pdf' : 'summary.export_pdf', ['date' => $businessDate]) }}" target="_blank" class="btn btn-danger">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF / Print
    </a>
    <a href="{{ route(request()->routeIs('admin.*') ? 'admin.verification.index' : 'verification.index', ['date' => $businessDate]) }}" class="btn btn-primary">
      <i class="bi bi-receipt-cutoff me-1"></i> View All Bills
    </a>
  </div>
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
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($matrixRows as $index => $row)
          <tr>
            <td class="text-start">
              <a href="{{ route('admin.summary.show', ['id' => $row['pso']->id, 'date' => $businessDate]) }}" class="text-decoration-none fw-bold font-mono text-primary fs-6">
                {{ $row['pso']->code }} <i class="bi bi-box-arrow-up-right small"></i>
              </a>
              <div class="small text-muted">{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }}-{{ sprintf('%02d', $row['pso']->end_no) }} | Op: {{ $row['pso']->operator_name }}@if($row['pso']->driver_name) | Drv: {{ $row['pso']->driver_name }}@endif @if($row['pso']->gadi_number) | Gadi: {{ $row['pso']->gadi_number }}@endif</div>
            </td>
            <td>
              <a href="{{ route('admin.summary.show', ['id' => $row['pso']->id, 'date' => $businessDate]) }}" class="btn btn-sm btn-light border font-mono px-2 py-1" title="View single PSO detail page">
                {{ $row['billsCount'] }} bills <i class="bi bi-arrow-right-short ms-1 text-primary"></i>
              </a>
            </td>
            <td class="font-mono">₹{{ number_format($row['gross'], 2) }}</td>
            <td class="font-mono text-success">₹{{ number_format($row['cash'], 2) }}</td>
            <td class="font-mono text-info">₹{{ number_format($row['paytm'], 2) }}</td>
            <td class="font-mono text-primary">₹{{ number_format($row['check'], 2) }}</td>
            <td class="font-mono text-warning">₹{{ number_format($row['credit'], 2) }}</td>
            <td class="font-mono text-muted">₹{{ number_format($row['cancelled'], 2) }}</td>
            <td class="font-mono text-danger">{{ $row['cd'] > 0 ? ('-₹' . number_format($row['cd'], 2)) : '₹0' }}</td>
            <td class="font-mono text-danger">{{ $row['refund'] > 0 ? ('-₹' . number_format($row['refund'], 2)) : '₹0' }}</td>
            <td class="text-end font-mono text-success fw-bold">₹{{ number_format($row['net'], 2) }}</td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.summary.show', ['id' => $row['pso']->id, 'date' => $businessDate]) }}" class="btn btn-primary">
                  <i class="bi bi-file-earmark-text me-1"></i> Detail Page
                </a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#psoDetailModal{{ $index }}" title="Quick Modal Preview">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="12" class="text-center text-muted py-4">
              No PSO records configured for summary matrix.
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr class="fw-bold">
          <td class="text-start">MASTER TOTAL</td>
          <td>
            <a href="{{ route('admin.verification.index', ['date' => $businessDate]) }}" class="badge bg-primary text-white text-decoration-none px-2 py-1" title="View all bills">
              {{ $metrics['totalBillsCount'] }} bills <i class="bi bi-arrow-right-short"></i>
            </a>
          </td>
          <td class="font-mono">₹{{ number_format($metrics['tallyTotal'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCash'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totPaytm'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCheck'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCredit'], 2) }}</td>
          <td class="font-mono">₹{{ number_format($metrics['totCancelled'], 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($metrics['totCd'], 2) }}</td>
          <td class="font-mono text-danger">-₹{{ number_format($metrics['totRefund'], 2) }}</td>
          <td class="text-end font-mono text-success fs-6">₹{{ number_format($metrics['psoCollection'], 2) }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Highlight Cards matching Prompt -->
<div class="row g-3 mb-4">
  @foreach($matrixRows as $index => $row)
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100 shadow-sm">
      <div class="d-flex justify-content-between align-items-start">
        <h6 class="fw-bold text-primary mb-1">{{ $row['pso']->code }} ({{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->start_no) }}–{{ $row['pso']->prefix }} {{ sprintf('%02d', $row['pso']->end_no) }})</h6>
        <a href="{{ route('admin.summary.show', ['id' => $row['pso']->id, 'date' => $businessDate]) }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
          <i class="bi bi-file-earmark-text me-1"></i>Detail Page
        </a>
      </div>
      <div class="fs-4 fw-bold font-mono text-dark">Total = ₹{{ number_format($row['net'], 2) }}</div>
      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted">{{ $row['billsCount'] }} Bills | {{ $row['pso']->operator_name }}</small>
        <a href="{{ route('admin.verification.index', ['pso' => $row['pso']->code, 'date' => $businessDate]) }}" class="small text-decoration-none">
          Verify Bills <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>
  @endforeach
</div>

<!-- SINGLE PSO DETAIL MODALS -->
@foreach($matrixRows as $index => $row)
<div class="modal fade" id="psoDetailModal{{ $index }}" tabindex="-1" aria-labelledby="psoDetailLabel{{ $index }}" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="psoDetailLabel{{ $index }}">
            <i class="bi bi-diagram-3-fill me-2"></i>{{ $row['pso']->code }} — Single PSO Detail Breakdown
          </h5>
          <small class="opacity-75">
            Prefix: <span class="badge bg-light text-dark font-mono">{{ $row['pso']->prefix }} ({{ sprintf('%02d', $row['pso']->start_no) }}-{{ sprintf('%02d', $row['pso']->end_no) }})</span>
            | Operator: <strong>{{ $row['pso']->operator_name }}</strong>
            @if($row['pso']->driver_name) | Driver: <strong>{{ $row['pso']->driver_name }}</strong> @endif
            @if($row['pso']->gadi_number) | Gadi: <strong>{{ $row['pso']->gadi_number }}</strong> @endif
          </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 bg-light">
        <!-- Metric Summary Chips -->
        <div class="row g-2 mb-4">
          <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded text-center">
              <div class="text-muted small">Total Bills</div>
              <div class="fs-5 fw-bold font-mono text-dark">{{ $row['billsCount'] }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded text-center">
              <div class="text-muted small">Gross Sales</div>
              <div class="fs-5 fw-bold font-mono text-dark">₹{{ number_format($row['gross'], 2) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded text-center">
              <div class="text-muted small">Cash Collection</div>
              <div class="fs-5 fw-bold font-mono text-success">₹{{ number_format($row['cash'], 2) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 bg-white border rounded text-center">
              <div class="text-muted small">Net Collection</div>
              <div class="fs-5 fw-bold font-mono text-success fw-bold">₹{{ number_format($row['net'], 2) }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 bg-white border rounded text-center small">
              <span class="text-muted">Paytm:</span> <strong class="text-info font-mono">₹{{ number_format($row['paytm'], 2) }}</strong>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 bg-white border rounded text-center small">
              <span class="text-muted">Cheque:</span> <strong class="text-primary font-mono">₹{{ number_format($row['check'], 2) }}</strong>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 bg-white border rounded text-center small">
              <span class="text-muted">Credit:</span> <strong class="text-warning font-mono">₹{{ number_format($row['credit'], 2) }}</strong>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 bg-white border rounded text-center small">
              <span class="text-muted">CD + Refund:</span> <strong class="text-danger font-mono">-₹{{ number_format($row['cd'] + $row['refund'], 2) }}</strong>
            </div>
          </div>
        </div>

        <!-- Bill Details List Table -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="bi bi-receipt me-1"></i> Individual Bills List ({{ $row['billsCount'] }})</span>
            <a href="{{ route('admin.verification.index', ['pso' => $row['pso']->code]) }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-pencil-square me-1"></i> Open in Bill Verification
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 text-center font-mono" style="font-size: 0.85rem;">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Bill No</th>
                  <th class="text-start">Customer Name</th>
                  <th>Salesperson</th>
                  <th>Amount</th>
                  <th>Payment Type</th>
                  <th>CD</th>
                  <th>Refund</th>
                  <th class="text-end">Net Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($row['bills'] as $billIndex => $b)
                  <tr>
                    <td>{{ $billIndex + 1 }}</td>
                    <td class="fw-bold text-primary">{{ $b->bill_no }}</td>
                    <td class="text-start font-sans">{{ $b->customer_name }}</td>
                    <td class="font-sans">{{ $b->salesman_name ?: '—' }}</td>
                    <td>₹{{ number_format($b->amount, 2) }}</td>
                    <td>
                      @php
                        $badgeClass = match($b->payment_type) {
                          'Cash' => 'bg-success',
                          'Paytm' => 'bg-info text-dark',
                          'Check' => 'bg-primary',
                          'Credit' => 'bg-warning text-dark',
                          'Cancelled' => 'bg-secondary',
                          default => 'bg-light text-dark'
                        };
                      @endphp
                      <span class="badge {{ $badgeClass }}">{{ $b->payment_type }}</span>
                    </td>
                    <td class="text-danger">{{ $b->cd_amount > 0 ? ('-₹' . number_format($b->cd_amount, 2)) : '₹0' }}</td>
                    <td class="text-danger">{{ $b->refund_amount > 0 ? ('-₹' . number_format($b->refund_amount, 2)) : '₹0' }}</td>
                    <td class="text-end fw-bold text-success">₹{{ number_format($b->net_amount, 2) }}</td>
                    <td>
                      @if($b->status === 'Matched')
                        <span class="badge bg-success-subtle text-success border border-success">Matched</span>
                      @elseif($b->status === 'Cancelled')
                        <span class="badge bg-secondary">Cancelled</span>
                      @else
                        <span class="badge bg-danger-subtle text-danger border border-danger">{{ $b->status }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center py-3 text-muted">No bills recorded for this PSO.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <div class="modal-footer bg-white">
        <a href="{{ route('admin.summary.show', $row['pso']->id) }}" class="btn btn-outline-primary">
          <i class="bi bi-file-earmark-text me-1"></i> Open Full Detail Page
        </a>
        <a href="{{ route('admin.verification.index', ['pso' => $row['pso']->code]) }}" class="btn btn-primary">
          <i class="bi bi-pencil-square me-1"></i> Edit & Verify Bills for {{ $row['pso']->code }}
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endforeach

@endsection
