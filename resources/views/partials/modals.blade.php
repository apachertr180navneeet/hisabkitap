<!-- ALL INTERACTIVE MODALS -->


<!-- Modal 2: Missing Bill Investigation -->
<div class="modal fade" id="modal-investigate-bill" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger-subtle">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-search me-1"></i> Missing Bill Investigation: <span id="investigate-bill-no">CB 02</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-investigate-bill">
        <div class="modal-body">
          <input type="hidden" id="investigate-bill-hidden-no">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Customer:</span>
              <strong id="investigate-customer">Kailash Supermarket</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Amount in Tally:</span>
              <strong class="font-mono text-danger" id="investigate-amount">₹17,500</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Target PSO:</span>
              <span class="badge bg-primary">Counter Sequence Verified</span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Resolution Reason <span class="text-danger">*</span></label>
            <select class="form-select" id="investigate-reason" required>
              <option value="Physical Slip Found & Verified in Counter Bundle" selected>Physical Slip Found & Verified in Counter Bundle</option>
              <option value="Counter Sale Check Completed">Counter Sale Check Completed</option>
              <option value="Cancelled Bill">Cancelled Bill (Mark Void)</option>
              <option value="Manual Authorized Correction">Manual Authorized Correction</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Investigation Remark / Slip ID</label>
            <textarea class="form-control" id="investigate-remark" rows="2" placeholder="Enter physical bundle serial or cashier stamp note...">Slip recovered from Cashier bundle #1, verified by Operator.</textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Resolve & Mark Matched</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 3: Record Correction / Return -->
<div class="modal fade" id="modal-add-correction" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Record Correction / Goods Return</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('corrections.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Bill No. <span class="text-danger">*</span></label>
            <input type="text" name="bill_no" class="form-control font-mono" placeholder="e.g. CB 01" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Correction Type <span class="text-danger">*</span></label>
            <select name="correction_type" class="form-select" required>
              <option value="Cash Discount">Cash Discount (CD)</option>
              <option value="Goods Return">Goods Return</option>
              <option value="Refund">Refund</option>
              <option value="Bill Correction">Bill Correction</option>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label fw-semibold">CD (₹)</label>
              <input type="number" name="cd_amount" class="form-control" value="0" min="0">
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">Return (₹)</label>
              <input type="number" name="goods_return_amount" class="form-control" value="0" min="0">
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">Refund (₹)</label>
              <input type="number" name="refund_amount" class="form-control" value="0" min="0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Approved volume discount / 1 damaged box" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save & Apply Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 4: Credit Collection Payment Recorder -->
<div class="modal fade" id="modal-update-credit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-cash-coin me-1"></i> Update Credit Collection: <span id="credit-modal-billno">CB 05</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('credit.update') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="credit_id" id="credit-modal-hidden-id">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Customer:</span>
              <strong id="credit-modal-customer">Balaji Enterprises</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Assigned Salesman:</span>
              <strong id="credit-modal-salesman">Rajesh Kumar</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Total Bill Amount:</span>
              <strong class="font-mono text-dark" id="credit-modal-total">₹28,000</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Currently Outstanding:</span>
              <strong class="font-mono text-danger" id="credit-modal-out">₹28,000</strong>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Amount Received Today (₹) <span class="text-danger">*</span></label>
            <input type="number" name="paid_today" class="form-control font-mono" id="credit-modal-paid-input" min="0" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Collection Mode</label>
            <select name="payment_mode" class="form-select" id="credit-modal-mode">
              <option value="Cash">Cash Collected by Salesman</option>
              <option value="Paytm / UPI">Direct UPI / QR Transfer</option>
              <option value="Cheque">Customer Cheque</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Collection Remarks</label>
            <input type="text" name="remark" class="form-control" placeholder="e.g. Receipt #902 issued / UPI Ref">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Update Payment Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 5: Digital Seal Certificate Viewer -->
<div class="modal fade" id="modal-seal-cert" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i> Official PSO Sealing Certificate</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="seal-certificate text-center">
          <div class="seal-stamp mb-3">
            <span>SEALED</span>
            <span>{{ $formattedBusinessDate ?? date('d/m/Y', strtotime($businessDate)) }}</span>
            <span>HISABKITAP</span>
          </div>
          <h4 class="fw-bold text-success mb-1">CERTIFICATE OF DAILY PSO RECONCILIATION</h4>
          <p class="text-muted small mb-4">Issued by HisabKitap Statutory ERP Verification Core</p>

          <div class="row g-3 text-start border p-3 rounded bg-light mb-4">
            <div class="col-6">
              <small class="text-muted d-block">Business Date</small>
              <strong class="font-mono">{{ $formattedBusinessDate ?? date('d/m/Y', strtotime($businessDate)) }}</strong>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Total Tally Amount Reconciled</small>
              <strong class="font-mono text-primary">₹{{ number_format($globalMetrics['tallyTotal'], 2) }}</strong>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Total Net PSO Collection</small>
              <strong class="font-mono text-success">₹{{ number_format($globalMetrics['psoCollection'], 2) }}</strong>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Final Variance</small>
              <strong class="font-mono text-success">₹0.00 (Zero Discrepancy)</strong>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Authorized Signatory</small>
              <strong>{{ $sealInfo->sealed_by ?? 'Pooja Verma (Accounts Officer)' }}</strong>
            </div>
            <div class="col-6">
              <small class="text-muted d-block">Audit Hash Token</small>
              <strong class="font-mono text-dark">{{ $sealInfo->seal_hash ?? 'SHA256: 7e9b41a80c2f82e1' }}</strong>
            </div>
          </div>

          <div class="alert alert-success d-flex align-items-center justify-content-center gap-2 mb-0" style="font-size: 0.85rem;">
            <i class="bi bi-lock-fill"></i>
            <span>All records for this date are locked into Read-Only state. Modifications require Administrator override.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Certificate</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add New Prefix -->
<div class="modal fade" id="modal-add-prefix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-tag-fill text-primary me-1"></i> Add New Prefix</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.prefix.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Code <span class="text-danger">*</span></label>
            <input type="text" name="prefix" class="form-control text-uppercase" placeholder="e.g. CB, RB, ITC" maxlength="10" required>
            <div class="form-text">Unique bill prefix code. Will be stored in UPPERCASE.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Prefix Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Counter Bill, Retail Bill" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Optional description for this prefix..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="bi bi-person-badge text-primary me-1"></i> Linked Sales Person</label>
            <select name="salesperson_id" class="form-select">
              <option value="">-- No Sales Person Assigned (Optional) --</option>
              @php
                $spList = isset($salespersons) ? $salespersons : \App\Models\Salesperson::where('is_active', true)->orderBy('name')->get();
              @endphp
              @foreach($spList as $sp)
                <option value="{{ $sp->id }}">{{ $sp->name }} [{{ $sp->code }}]{{ $sp->phone ? ' - ' . $sp->phone : '' }}</option>
              @endforeach
            </select>
            <div class="form-text">Select the representative responsible for this bill sequence and credit recoveries.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Save Prefix</button>
        </div>
      </form>
    </div>
  </div>
</div>
