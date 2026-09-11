@extends('layouts.app')

@section('title', 'Cash Denomination & Driver Reconciliation')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-1">Cash Denomination & Driver Handover</h4>
    <p class="text-muted mb-0">Note-by-note physical cash counting, Driver KM travel deductions, and real-time short cash discrepancy tracking.</p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <a href="{{ route('admin.reconciliation.index') }}" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-shield-check me-1"></i> Master Reconciliation
    </a>
    <a href="{{ route('admin.payment.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-wallet2 me-1"></i> Payment Ledger
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="card border p-3 mb-3 bg-white shadow-sm">
  <form method="GET" action="{{ route('admin.denomination.index') }}" class="row g-2 align-items-center">
    <div class="col-md-3">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
        <input type="date" name="date" class="form-control font-mono" value="{{ $businessDate }}" onchange="this.form.submit()" title="Select Business Date">
      </div>
    </div>
    <div class="col-md-4">
      <select name="pso" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="ALL">All PSO Counters & Drivers</option>
        @foreach($psoList as $pso)
          <option value="{{ $pso->code }}" {{ $selectedPso === $pso->code ? 'selected' : '' }}>
            {{ $pso->code }} ({{ $pso->prefix }} - {{ $pso->operator_name }}{{ $pso->driver_name ? ' | Drv: ' . $pso->driver_name : '' }}{{ $pso->gadi_number ? ' | ' . $pso->gadi_number : '' }})
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-md-5 d-flex gap-2">
      <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Apply Filter</button>
      <a href="{{ route('admin.denomination.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
  </form>

  @if(!empty($availableDates) && count($availableDates) > 0)
    <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2 small text-muted">
      <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i> Available Dates:</span>
      <div class="d-flex flex-wrap gap-1">
        @foreach($availableDates as $ad)
          <a href="{{ route('admin.denomination.index', ['date' => $ad, 'pso' => $selectedPso]) }}" class="badge {{ $businessDate === $ad ? 'bg-primary text-white' : 'bg-light text-dark border text-decoration-none' }}">
            {{ date('d M Y', strtotime($ad)) }}
          </a>
        @endforeach
      </div>
    </div>
  @endif
</div>

<!-- 5 Key Metrics Cards -->
<div class="row g-3 mb-4">
  <div class="col-md">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-primary shadow-sm">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-primary">Book Cash</span>
        <i class="bi bi-journal-text fs-4 text-primary"></i>
      </div>
      <h6 class="text-muted small mb-1">Total Cash Bills</h6>
      <div class="fs-4 fw-bold font-mono text-primary">₹{{ number_format($scopedBookCash, 2) }}</div>
      <small class="text-muted">Expected gross cash from verified bills</small>
    </div>
  </div>

  <div class="col-md">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-success shadow-sm">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-success">Counted Cash</span>
        <i class="bi bi-cash-stack fs-4 text-success"></i>
      </div>
      <h6 class="text-muted small mb-1">Physical Cash Deposited</h6>
      <div class="fs-4 fw-bold font-mono text-success">₹{{ number_format($scopedCountedCash, 2) }}</div>
      <small class="text-muted">{{ $scopedDenomCount }} handover slips recorded</small>
    </div>
  </div>

  <div class="col-md">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-info shadow-sm">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-info text-dark">Travel / KM</span>
        <i class="bi bi-speedometer2 fs-4 text-info"></i>
      </div>
      <h6 class="text-muted small mb-1">Driver KM Allowance</h6>
      <div class="fs-4 fw-bold font-mono text-info">₹{{ number_format($scopedKmAllowance, 2) }}</div>
      <small class="text-muted">{{ number_format($scopedKmCompleted, 1) }} KM total logged</small>
    </div>
  </div>

  <div class="col-md">
    <div class="card border p-3 bg-white h-100 border-start border-4 {{ $scopedShortCash > 0 ? 'border-danger' : 'border-secondary' }} shadow-sm">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge {{ $scopedShortCash > 0 ? 'bg-danger' : 'bg-secondary' }}">Variance</span>
        <i class="bi bi-exclamation-octagon fs-4 {{ $scopedShortCash > 0 ? 'text-danger' : 'text-secondary' }}"></i>
      </div>
      <h6 class="text-muted small mb-1">Short / Pending Cash</h6>
      <div class="fs-4 fw-bold font-mono {{ $scopedShortCash > 0 ? 'text-danger' : 'text-dark' }}">
        ₹{{ number_format($scopedShortCash, 2) }}
      </div>
      <small class="text-muted">{{ $scopedShortCash > 0 ? 'Pending recovery from driver' : 'No shortage detected' }}</small>
    </div>
  </div>

  <div class="col-md">
    <div class="card border p-3 bg-white h-100 border-start border-4 border-info shadow-sm">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="badge bg-info text-dark">Digital</span>
        <i class="bi bi-bank fs-4 text-info"></i>
      </div>
      <h6 class="text-muted small mb-1">Paytm / RTGS / Bank Total</h6>
      <div class="fs-4 fw-bold font-mono text-info">₹{{ number_format($scopedPaytm, 2) }}</div>
      <small class="text-muted">Direct digital / bank clearing</small>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Left Column: Interactive Cash Denomination Form -->
  <div class="col-lg-7">
    <div class="card border bg-white shadow-sm h-100">
      <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-calculator me-2"></i>Physical Cash Denomination Counter</h6>
        <span class="badge bg-warning text-dark font-mono"><i class="bi bi-calendar-event me-1"></i>{{ date('d/m/Y', strtotime($businessDate)) }}</span>
      </div>
      <div class="card-body p-4">
        <form action="{{ route('admin.denomination.store') }}" method="POST" id="denominationForm">
          @csrf
          <input type="hidden" name="business_date" value="{{ $businessDate }}">
          <input type="hidden" name="book_cash_amount" id="form_book_cash" value="{{ $scopedBookCash }}">

          <!-- Driver & Counter Selection -->
          <div class="row g-3 mb-4 p-3 bg-light rounded border">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">PSO Counter / Series</label>
              <select name="pso_code" id="denom_pso_code" class="form-select form-select-sm" onchange="autoFillDriver(this)">
                <option value="">-- General / Cashier --</option>
                @foreach($psoList as $pso)
                  <option value="{{ $pso->code }}" 
                          data-driver="{{ $pso->driver_name }}" 
                          data-gadi="{{ $pso->gadi_number }}"
                          {{ $selectedPso === $pso->code ? 'selected' : '' }}>
                    {{ $pso->code }} ({{ $pso->prefix }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Driver / Handover By</label>
              <input type="text" name="driver_name" id="denom_driver_name" class="form-control form-control-sm" placeholder="e.g. Ramesh Kumar">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Vehicle / Gadi No.</label>
              <input type="text" name="gadi_number" id="denom_gadi_number" class="form-control form-control-sm text-uppercase font-mono" placeholder="e.g. RJ 14 GA 5555">
            </div>
          </div>

          <!-- Currency Notes Breakdown Table -->
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm align-middle text-center mb-0" id="denominationTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 25%;">Denomination</th>
                  <th style="width: 35%;">Notes / Count</th>
                  <th style="width: 40%;" class="text-end">Subtotal (₹)</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $denoms = [
                    ['value' => 2000, 'name' => 'notes_2000', 'label' => '₹ 2,000 Note', 'badge' => 'bg-danger'],
                    ['value' => 500,  'name' => 'notes_500',  'label' => '₹ 500 Note',   'badge' => 'bg-secondary'],
                    ['value' => 200,  'name' => 'notes_200',  'label' => '₹ 200 Note',   'badge' => 'bg-warning text-dark'],
                    ['value' => 100,  'name' => 'notes_100',  'label' => '₹ 100 Note',   'badge' => 'bg-primary'],
                    ['value' => 50,   'name' => 'notes_50',   'label' => '₹ 50 Note',    'badge' => 'bg-info text-dark'],
                    ['value' => 20,   'name' => 'notes_20',   'label' => '₹ 20 Note',    'badge' => 'bg-success'],
                    ['value' => 10,   'name' => 'notes_10',   'label' => '₹ 10 Note',    'badge' => 'bg-dark'],
                    ['value' => 5,    'name' => 'notes_5',    'label' => '₹ 5 Note',     'badge' => 'bg-secondary'],
                    ['value' => 2,    'name' => 'notes_2',    'label' => '₹ 2 Note',     'badge' => 'bg-light text-dark border'],
                    ['value' => 1,    'name' => 'notes_1',    'label' => '₹ 1 Note',     'badge' => 'bg-light text-dark border'],
                  ];
                @endphp

                @foreach($denoms as $d)
                  <tr>
                    <td class="text-start ps-3 fw-bold">
                      <span class="badge {{ $d['badge'] }} px-2 py-1">{{ $d['label'] }}</span>
                    </td>
                    <td>
                      <div class="input-group input-group-sm justify-content-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustCount('{{ $d['name'] }}', -1)">-</button>
                        <input type="number" min="0" name="{{ $d['name'] }}" id="{{ $d['name'] }}" 
                               data-val="{{ $d['value'] }}" 
                               class="form-control form-control-sm text-center font-mono note-input" 
                               value="0" 
                               style="max-width: 90px;" 
                               oninput="calculateTotal()">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustCount('{{ $d['name'] }}', 1)">+</button>
                      </div>
                    </td>
                    <td class="text-end pe-3 font-mono fw-bold text-dark subtotal-cell" id="subtotal_{{ $d['name'] }}">₹0.00</td>
                  </tr>
                @endforeach

                <tr class="table-light">
                  <td class="text-start ps-3 fw-bold">
                    <span class="badge bg-secondary px-2 py-1">Coins Total (₹)</span>
                  </td>
                  <td>
                    <input type="number" step="0.50" min="0" name="coins_total" id="coins_total" 
                           class="form-control form-control-sm text-center font-mono" 
                           value="0" 
                           style="max-width: 140px; margin: 0 auto;" 
                           oninput="calculateTotal()">
                  </td>
                  <td class="text-end pe-3 font-mono fw-bold text-dark" id="subtotal_coins">₹0.00</td>
                </tr>
              </tbody>
              <tfoot class="table-dark">
                <tr>
                  <th colspan="2" class="text-start ps-3 fs-6">Grand Total Counted Cash:</th>
                  <th class="text-end pe-3 fs-5 font-mono text-warning" id="grand_total_display">₹0.00</th>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Driver KM & Travel Allowance Details -->
          <div class="card border p-3 mb-3 bg-light">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-speedometer2 text-info me-1"></i>Taxi / Driver KM Travel Deduction</h6>
            <div class="row g-2 align-items-center">
              <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Total Trip KM</label>
                <div class="input-group input-group-sm">
                  <input type="number" step="0.1" min="0" name="total_km" id="total_km" class="form-control font-mono" value="0" placeholder="e.g. 200" oninput="calculateTotal()">
                  <span class="input-group-text bg-white">KM</span>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Rate per KM (₹)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white">₹</span>
                  <input type="number" step="0.1" min="0" name="km_rate" id="km_rate" class="form-control font-mono" value="0" placeholder="e.g. 2.0" oninput="calculateTotal()">
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label small text-muted mb-1">KM Travel Allowance</label>
                <div class="form-control form-control-sm bg-white font-mono text-end fw-bold text-info" id="km_allowance_display">
                  ₹0.00
                </div>
              </div>
            </div>
          </div>

          <!-- Live Comparison Box -->
          <div class="p-3 mb-3 rounded border" id="live_comparison_box" style="background: #f8fafc;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold">1. Book Cash Bills:</span>
              <span class="font-mono fw-bold text-primary">₹{{ number_format($scopedBookCash, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold">2. Less KM Travel Allowance:</span>
              <span class="font-mono fw-bold text-info" id="live_km_sub">-₹0.00</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
              <span class="fw-bold">3. Net Cash Expected from Driver:</span>
              <span class="font-mono fw-bold text-dark fs-6" id="live_net_expected">₹{{ number_format($scopedBookCash, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold">4. Physical Cash Counted:</span>
              <span class="font-mono fw-bold text-success fs-6" id="live_physical_counted">₹0.00</span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top" id="live_variance_row">
              <span class="fw-bold" id="live_variance_label">Discrepancy / Short Cash:</span>
              <span class="font-mono fw-bold fs-5 text-danger" id="live_variance_display">₹0.00</span>
            </div>
          </div>

          <!-- Cashier remarks -->
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small text-muted mb-1">Cashier / Receiver Name</label>
              <input type="text" name="cashier_name" class="form-control form-control-sm" value="{{ session('active_user.name', 'Pooja Verma') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label small text-muted mb-1">Remarks / Note</label>
              <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Driver cash submitted at 11:30 PM counter close">
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg fw-bold">
              <i class="bi bi-check-circle-fill me-2"></i>Save Physical Cash Handover & Reconcile
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Right Column: Historical / Recorded Slips & Discrepancy Log -->
  <div class="col-lg-5">
    <div class="card border bg-white shadow-sm h-100">
      <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Recorded Cash Handover Slips</h6>
        <span class="badge bg-primary font-mono">{{ count($denominations) }} Slips</span>
      </div>
      <div class="card-body p-3">
        @forelse($denominations as $d)
          <div class="card border mb-3 shadow-none {{ $d->short_cash_amount > 0 ? 'border-danger' : 'border-success' }}">
            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
              <div>
                <span class="badge bg-primary me-1">{{ $d->pso_code ?: 'General' }}</span>
                <strong class="font-mono">{{ $d->gadi_number ?: 'Counter Cash' }}</strong>
              </div>
              <small class="text-muted font-mono">{{ $d->created_at->format('h:i A') }}</small>
            </div>
            <div class="card-body py-2 px-3 small">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Driver / Depositor:</span>
                <strong>{{ $d->driver_name ?: 'Counter Operator' }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Physical Cash Deposited:</span>
                <strong class="font-mono text-success fs-6">₹{{ number_format($d->total_physical_cash, 2) }}</strong>
              </div>
              @if($d->total_km > 0)
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Travel Details:</span>
                  <span class="font-mono">{{ $d->total_km }} KM @ ₹{{ $d->km_rate }}/KM = <strong class="text-info">-₹{{ number_format($d->km_allowance_amount, 2) }}</strong></span>
                </div>
              @endif
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Expected Book Cash:</span>
                <span class="font-mono">₹{{ number_format($d->book_cash_amount, 2) }}</span>
              </div>
              
              <!-- Variance badge -->
              <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                @if($d->short_cash_amount > 0)
                  <span class="badge bg-danger-subtle text-danger border border-danger p-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Short Cash: ₹{{ number_format($d->short_cash_amount, 2) }}
                  </span>
                @elseif($d->excess_cash_amount > 0)
                  <span class="badge bg-info-subtle text-info border border-info p-2">
                    <i class="bi bi-plus-circle-fill me-1"></i> Excess Cash: ₹{{ number_format($d->excess_cash_amount, 2) }}
                  </span>
                @else
                  <span class="badge bg-success-subtle text-success border border-success p-2">
                    <i class="bi bi-check-circle-fill me-1"></i> 100% Balanced Handover
                  </span>
                @endif

                <form action="{{ route('admin.denomination.delete', $d->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this denomination record?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Delete Handover Record">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>

              <!-- Note Breakdown Popover or small list -->
              <div class="mt-2 pt-2 border-top font-mono text-muted" style="font-size: 0.76rem;">
                <span class="fw-semibold">Notes:</span> 
                @if($d->notes_2000 > 0) 2000×{{ $d->notes_2000 }} @endif
                @if($d->notes_500 > 0) 500×{{ $d->notes_500 }} @endif
                @if($d->notes_200 > 0) 200×{{ $d->notes_200 }} @endif
                @if($d->notes_100 > 0) 100×{{ $d->notes_100 }} @endif
                @if($d->notes_50 > 0) 50×{{ $d->notes_50 }} @endif
                @if($d->notes_20 > 0) 20×{{ $d->notes_20 }} @endif
                @if($d->notes_10 > 0) 10×{{ $d->notes_10 }} @endif
                @if($d->coins_total > 0) Coins: ₹{{ number_format($d->coins_total, 2) }} @endif
              </div>
            </div>
          </div>
        @empty
          <div class="text-center py-5 text-muted">
            <i class="bi bi-cash-coin fs-1 d-block mb-2 text-secondary"></i>
            <p class="mb-0">No physical cash handover slips recorded for {{ date('d/m/Y', strtotime($businessDate)) }}.</p>
            <small>Use the counter calculator on the left to enter physical cash notes and verify driver collections.</small>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<script>
function adjustCount(inputId, delta) {
  const input = document.getElementById(inputId);
  if (!input) return;
  let val = parseInt(input.value || 0, 10) + delta;
  if (val < 0) val = 0;
  input.value = val;
  calculateTotal();
}

function calculateTotal() {
  const inputs = document.querySelectorAll('.note-input');
  let grandTotal = 0;

  inputs.forEach(input => {
    const val = parseFloat(input.getAttribute('data-val') || 0);
    const count = parseInt(input.value || 0, 10);
    const sub = val * (isNaN(count) ? 0 : count);
    grandTotal += sub;

    const subCell = document.getElementById('subtotal_' + input.name);
    if (subCell) {
      subCell.textContent = '₹' + sub.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  });

  const coinsInput = document.getElementById('coins_total');
  const coins = parseFloat(coinsInput ? coinsInput.value : 0) || 0;
  grandTotal += coins;
  const coinsCell = document.getElementById('subtotal_coins');
  if (coinsCell) {
    coinsCell.textContent = '₹' + coins.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Display grand total
  const grandDisplay = document.getElementById('grand_total_display');
  if (grandDisplay) {
    grandDisplay.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // KM Allowance Calculation
  const totalKm = parseFloat(document.getElementById('total_km')?.value || 0) || 0;
  const kmRate = parseFloat(document.getElementById('km_rate')?.value || 0) || 0;
  const kmAllowance = totalKm * kmRate;
  
  const kmDisplay = document.getElementById('km_allowance_display');
  if (kmDisplay) {
    kmDisplay.textContent = '₹' + kmAllowance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  const liveKmSub = document.getElementById('live_km_sub');
  if (liveKmSub) {
    liveKmSub.textContent = '-₹' + kmAllowance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Live reconciliation comparison
  const bookCash = parseFloat(document.getElementById('form_book_cash')?.value || 0) || 0;
  const netExpected = Math.max(0, bookCash - kmAllowance);

  const liveNetExpected = document.getElementById('live_net_expected');
  if (liveNetExpected) {
    liveNetExpected.textContent = '₹' + netExpected.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  const liveCounted = document.getElementById('live_physical_counted');
  if (liveCounted) {
    liveCounted.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  const liveVarianceLabel = document.getElementById('live_variance_label');
  const liveVarianceDisplay = document.getElementById('live_variance_display');
  
  if (liveVarianceDisplay) {
    if (netExpected > grandTotal) {
      const short = netExpected - grandTotal;
      liveVarianceLabel.textContent = 'Short / Pending Cash:';
      liveVarianceLabel.className = 'fw-bold text-danger';
      liveVarianceDisplay.textContent = '₹' + short.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Short';
      liveVarianceDisplay.className = 'font-mono fw-bold fs-5 text-danger';
    } else if (grandTotal > netExpected) {
      const excess = grandTotal - netExpected;
      liveVarianceLabel.textContent = 'Excess Cash Deposited:';
      liveVarianceLabel.className = 'fw-bold text-info';
      liveVarianceDisplay.textContent = '₹' + excess.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Excess';
      liveVarianceDisplay.className = 'font-mono fw-bold fs-5 text-info';
    } else {
      liveVarianceLabel.textContent = 'Reconciliation Status:';
      liveVarianceLabel.className = 'fw-bold text-success';
      liveVarianceDisplay.textContent = '₹0.00 (Zero Variance)';
      liveVarianceDisplay.className = 'font-mono fw-bold fs-5 text-success';
    }
  }
}

function autoFillDriver(selectEl) {
  const opt = selectEl.options[selectEl.selectedIndex];
  if (opt) {
    const driver = opt.getAttribute('data-driver');
    const gadi = opt.getAttribute('data-gadi');
    if (driver) document.getElementById('denom_driver_name').value = driver;
    if (gadi) document.getElementById('denom_gadi_number').value = gadi;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  calculateTotal();
});
</script>
@endsection
