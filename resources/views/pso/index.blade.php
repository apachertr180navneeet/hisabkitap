@extends('layouts.app')

@section('title', 'PSO Series Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">PSO Series Management</h4>
    <p class="text-muted mb-0">Define counter PSO prefixes, starting/ending serial ranges, and special company bill series.</p>
  </div>
  @if($currentUser->hasPermission('can_configure_pso'))
  <a href="{{ route('admin.pso.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> Configure New PSO
  </a>
  @endif
</div>

<div class="erp-table-container mb-4">
  <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-dark">Active PSO Series Rules (Default Configuration)</span>
    <span class="badge bg-success">{{ $psoList->where('is_active', true)->count() }} Configs Active</span>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>PSO Identifier</th>
          <th>Prefix</th>
          <th>Range Start</th>
          <th>Range End</th>
          <th>Special Bills Series</th>
          <th>Operator, Driver & Crew</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($psoList as $pso)
          <tr>
            <td>
              <span class="badge bg-primary font-mono fs-6">{{ $pso->code }}</span>
              @if($pso->description)
                <div class="text-muted small mt-1" style="font-size: 0.76rem;">{{ Str::limit($pso->description, 50) }}</div>
              @endif
            </td>
            <td>
              @php $allRanges = $pso->getAllSeriesRanges(); @endphp
              @if(count($allRanges) > 1)
                <div class="d-flex flex-column gap-1">
                  @foreach($allRanges as $rng)
                    <div>
                      <code class="fw-bold">{{ $rng['prefix'] }}</code>
                      <span class="text-muted font-mono" style="font-size: 0.70rem;">({{ $rng['financial_year'] ?? $pso->financial_year ?? '2026-2027' }})</span>
                    </div>
                  @endforeach
                </div>
              @else
                <code class="fw-bold fs-6">{{ $pso->prefix }}</code>
                <div class="text-muted font-mono" style="font-size: 0.72rem;">
                  <i class="bi bi-calendar3 text-primary me-0.5"></i>FY: {{ $pso->financial_year ?? $activeFinancialYear ?? '2026-2027' }}
                </div>
              @endif
            </td>
            <td class="font-mono">
              @if(count($allRanges) > 1)
                <div class="d-flex flex-column gap-1">
                  @foreach($allRanges as $rng)
                    <div>{{ sprintf('%02d', $rng['start_no'] ?? 1) }}</div>
                  @endforeach
                </div>
              @else
                {{ sprintf('%02d', $pso->start_no) }}
              @endif
            </td>
            <td class="font-mono">
              @if(count($allRanges) > 1)
                <div class="d-flex flex-column gap-1">
                  @foreach($allRanges as $rng)
                    <div>{{ sprintf('%02d', $rng['end_no'] ?? 10) }}</div>
                  @endforeach
                </div>
              @else
                {{ sprintf('%02d', $pso->end_no) }}
              @endif
            </td>
            <td>
              @if(!empty($pso->specials))
                @foreach($pso->specials as $spec)
                  <span class="badge bg-info text-dark me-1 font-mono">{{ $spec }}</span>
                @endforeach
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              <div class="fw-semibold text-dark">{{ $pso->operator_name }}</div>
              @if($pso->created_by)
                <div class="text-muted small mt-0.5" style="font-size: 0.73rem;">
                  <i class="bi bi-person-check text-primary me-1"></i>Created by: <strong class="text-secondary">{{ $pso->created_by_name }}</strong>
                </div>
              @endif
              @if($pso->driver_name || $pso->gadi_number)
                <div class="small mt-1 d-flex flex-wrap align-items-center gap-1">
                  @if($pso->gadi_number)
                    <span class="badge bg-light text-dark border font-mono" title="Gadi / Vehicle Number">
                      <i class="bi bi-truck me-1 text-primary"></i>{{ $pso->gadi_number }}
                    </span>
                  @endif
                  @if($pso->driver_name)
                    <span class="text-secondary small font-mono" title="Driver Name">
                      <i class="bi bi-person-badge text-muted me-0.5"></i>{{ $pso->driver_name }}
                    </span>
                  @endif
                </div>
              @endif
              @if(!empty($pso->helpers_list))
                <div class="small text-muted mt-0.5" style="font-size: 0.72rem;" title="Helpers: {{ $pso->helpers_text }}">
                  <i class="bi bi-people me-1 text-secondary"></i>{{ $pso->helpers_text }}
                </div>
              @endif
            </td>
            <td>
              @if($pso->is_closed)
                <span class="badge bg-dark">
                  <i class="bi bi-lock-fill me-1"></i>Closed
                </span>
                @if($pso->has_goods_return && $pso->goods_return_amount > 0)
                  <div class="text-danger font-mono small mt-0.5" style="font-size: 0.72rem;" title="{{ $pso->goods_return_particulars }}">
                    <i class="bi bi-arrow-return-left me-0.5"></i>Return: ₹{{ number_format($pso->goods_return_amount, 2) }}
                  </div>
                @else
                  <div class="text-muted small mt-0.5" style="font-size: 0.72rem;">No Returns</div>
                @endif
              @else
                <span class="badge {{ $pso->is_active ? 'bg-success' : 'bg-secondary' }}">
                  {{ $pso->is_active ? 'Active' : 'Inactive' }}
                </span>
              @endif
            </td>
            <td class="text-end text-nowrap">
              @if($currentUser && $currentUser->isOperator())
                @if($pso->is_closed)
                  <span class="badge bg-secondary-subtle text-secondary border font-mono small py-1.5 px-2">
                    <i class="bi bi-lock-fill text-muted me-1"></i>PSO Closed
                  </span>
                @else
                  <button type="button" class="btn btn-sm btn-outline-danger btn-open-close-modal"
                          data-id="{{ $pso->id }}"
                          data-code="{{ $pso->code }}"
                          data-operator="{{ $pso->operator_name }}"
                          title="Close this PSO Series">
                    <i class="bi bi-door-closed-fill me-1"></i> Close PSO
                  </button>
                @endif
              @elseif($currentUser->hasPermission('can_configure_pso'))
              <div class="d-flex justify-content-end align-items-center gap-1">
                {{-- Close / Reopen Action --}}
                @if($pso->is_closed)
                  <form action="{{ route('admin.pso.reopen', $pso->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success" title="Reopen PSO Series">
                      <i class="bi bi-unlock-fill me-1"></i> Reopen
                    </button>
                  </form>
                @else
                  <button type="button" class="btn btn-sm btn-outline-warning btn-open-close-modal"
                          data-id="{{ $pso->id }}"
                          data-code="{{ $pso->code }}"
                          data-operator="{{ $pso->operator_name }}"
                          title="Close this PSO Series">
                    <i class="bi bi-door-closed me-1"></i> Close
                  </button>
                @endif

                {{-- Edit Action --}}
                <a href="{{ route('admin.pso.edit', $pso->id) }}" class="btn btn-sm btn-outline-primary" title="Edit PSO Configuration">
                  <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                {{-- Status Toggle --}}
                @if(!$pso->is_closed)
                <form action="{{ route('admin.pso.toggle', $pso->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm {{ $pso->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}" 
                          title="{{ $pso->is_active ? 'Disable' : 'Enable' }}">
                    <i class="bi {{ $pso->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                    {{ $pso->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </form>
                @endif

                {{-- Delete Action (if safe) --}}
                @if(($pso->bills_count ?? 0) === 0)
                <form action="{{ route('admin.pso.delete', $pso->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Are you sure you want to delete PSO Series {{ $pso->code }}?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete PSO Configuration">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                @endif
              </div>
              @else
                <span class="text-muted small">Read Only</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-diagram-3 fs-3 d-block mb-1 text-primary"></i>
              No PSO Series configured. Click <a href="{{ route('admin.pso.create') }}" class="text-primary fw-bold">"+ Configure New PSO"</a> above to add your first counter series.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Series Reference Cards -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 1 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles standard counter sequence <code>CB 01</code> to <code>CB 10</code>. Verifies 10 bills sequentially.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 2 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles sequence <code>CB 11</code> to <code>CB 20</code> plus Special Company bills <code>ITC 01</code> and <code>ITC 03</code>.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border p-3 bg-white h-100">
      <div class="fw-bold text-primary mb-1">PSO 3 Series Logic</div>
      <div class="text-muted" style="font-size: 0.82rem;">Handles Retail counter sequence <code>RB 01</code> to <code>RB 10</code> for walk-in instant counter receipts.</div>
    </div>
  </div>
</div>

<!-- MODAL: CLOSE PSO & GOODS RETURN CHECK -->
<div class="modal fade" id="modal-close-pso" tabindex="-1" aria-labelledby="modalClosePsoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="modalClosePsoLabel">
          <i class="bi bi-door-closed-fill me-2"></i> Close PSO Series: <span id="close-modal-pso-code" class="font-mono"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="form-close-pso" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="text-muted small">Assigned Operator:</span>
              <strong id="close-modal-operator" class="text-dark"></strong>
            </div>
            <p class="text-secondary small mb-0">
              Closing this PSO series locks the counter sequence for the day. Please verify any customer goods return below.
            </p>
          </div>

          <!-- Question: Any Goods Return? -->
          <div class="mb-3">
            <label class="form-label fw-bold text-dark fs-6">
              <i class="bi bi-question-circle text-primary me-1"></i> Are there any Goods Returned for this PSO counter? <span class="text-danger">*</span>
            </label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check p-2.5 border rounded flex-fill bg-white">
                <input class="form-check-input ms-1 me-2" type="radio" name="has_goods_return" id="goods_return_no" value="0" checked>
                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="goods_return_no">
                  <i class="bi bi-check-circle text-success me-1"></i> No Goods Return
                </label>
                <div class="text-muted small ms-4">All items delivered cleanly</div>
              </div>
              <div class="form-check p-2.5 border rounded flex-fill bg-white">
                <input class="form-check-input ms-1 me-2" type="radio" name="has_goods_return" id="goods_return_yes" value="1">
                <label class="form-check-label fw-semibold text-danger cursor-pointer" for="goods_return_yes">
                  <i class="bi bi-arrow-return-left text-danger me-1"></i> Yes, Goods Returned
                </label>
                <div class="text-muted small ms-4">Customer returned goods / refund</div>
              </div>
            </div>
          </div>

          <!-- Conditional Goods Return Details Container -->
          <div id="goods-return-details-card" class="p-3 border border-danger-subtle bg-danger-subtle rounded mb-3 d-none">
            <h6 class="fw-bold text-danger mb-2">
              <i class="bi bi-box-arrow-in-left me-1"></i> Goods Return Details
            </h6>
            <div class="mb-2">
              <label class="form-label fw-semibold text-dark small">Return Amount (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white">₹</span>
                <input type="number" step="0.01" min="0.01" name="goods_return_amount" id="goods_return_amount" 
                       class="form-control font-mono" placeholder="e.g. 1500.00">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold text-dark small">Bill / Invoice Reference (Optional)</label>
              <input type="text" name="goods_return_bill_no" id="goods_return_bill_no" 
                     class="form-control font-mono" placeholder="e.g. SC 6718">
            </div>
            <div>
              <label class="form-label fw-semibold text-dark small">Particulars / Reason for Return</label>
              <textarea name="goods_return_particulars" id="goods_return_particulars" class="form-control" rows="2" 
                        placeholder="e.g. Damaged cartons returned by customer / cancelled order"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-bold">
            <i class="bi bi-door-closed-fill me-1"></i> Confirm & Close PSO
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalClosePso = document.getElementById('modal-close-pso');
  const formClosePso = document.getElementById('form-close-pso');
  const codeSpan = document.getElementById('close-modal-pso-code');
  const operatorSpan = document.getElementById('close-modal-operator');
  const goodsReturnDetailsCard = document.getElementById('goods-return-details-card');
  const radioNo = document.getElementById('goods_return_no');
  const radioYes = document.getElementById('goods_return_yes');
  const inputAmount = document.getElementById('goods_return_amount');

  function toggleGoodsReturnFields() {
    if (radioYes.checked) {
      goodsReturnDetailsCard.classList.remove('d-none');
      inputAmount.setAttribute('required', 'required');
    } else {
      goodsReturnDetailsCard.classList.add('d-none');
      inputAmount.removeAttribute('required');
      inputAmount.value = '';
    }
  }

  if (radioNo && radioYes) {
    radioNo.addEventListener('change', toggleGoodsReturnFields);
    radioYes.addEventListener('change', toggleGoodsReturnFields);
  }

  document.querySelectorAll('.btn-open-close-modal').forEach(btn => {
    btn.addEventListener('click', function () {
      const psoId = this.dataset.id;
      const psoCode = this.dataset.code;
      const operator = this.dataset.operator || 'Assigned Operator';

      if (formClosePso) {
        formClosePso.action = `/admin/pso/${psoId}/close`;
      }
      if (codeSpan) codeSpan.textContent = psoCode;
      if (operatorSpan) operatorSpan.textContent = operator;

      if (radioNo) radioNo.checked = true;
      toggleGoodsReturnFields();

      const modal = new bootstrap.Modal(modalClosePso);
      modal.show();
    });
  });
});
</script>
@endsection
