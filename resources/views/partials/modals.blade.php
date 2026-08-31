<!-- ALL INTERACTIVE MODALS -->

<!-- Modal 1: Add/Edit PSO Series -->
<div class="modal fade" id="modal-add-pso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Configure New PSO Series</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('pso.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">PSO Code / Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. PSO 4 - Institutional Delivery" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label fw-semibold">Prefix</label>
              <input type="text" name="prefix" class="form-control text-uppercase" placeholder="e.g. IB" required>
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">Start No.</label>
              <input type="number" name="start_no" class="form-control" value="1" required>
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">End No.</label>
              <input type="number" name="end_no" class="form-control" value="10" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Special Bills (Comma separated)</label>
            <input type="text" name="specials" class="form-control" placeholder="e.g. ITC 05, SPL 01">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Assigned Operator</label>
            <input type="text" name="operator_name" class="form-control" value="{{ $currentUser['name'] ?? 'Ramesh Sharma' }}" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save PSO Configuration</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
            <span>{{ $businessDate }}</span>
            <span>HISABKITAP</span>
          </div>
          <h4 class="fw-bold text-success mb-1">CERTIFICATE OF DAILY PSO RECONCILIATION</h4>
          <p class="text-muted small mb-4">Issued by HisabKitap Statutory ERP Verification Core</p>

          <div class="row g-3 text-start border p-3 rounded bg-light mb-4">
            <div class="col-6">
              <small class="text-muted d-block">Business Date</small>
              <strong class="font-mono">{{ $businessDate }}</strong>
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

<!-- Modal 6: Role Capabilities Matrix & System Guide -->
<div class="modal fade" id="modal-role-matrix" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <div>
          <h5 class="modal-title fw-bold"><i class="bi bi-diagram-3-fill text-primary me-2"></i> Role-Based Access Control (RBAC) & Capability Guide</h5>
          <small class="text-white-50">Discover which role uses which feature and switch persona on-the-fly to test workflows</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <ul class="nav nav-pills mb-3" id="role-matrix-tabs">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-role-cards">
              <i class="bi bi-person-badge-fill me-1.5"></i> Role Personas & Responsibilities
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-role-matrix">
              <i class="bi bi-table me-1.5"></i> Feature Permission Matrix Table
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Tab 1: Role Personas Cards -->
          <div class="tab-pane fade show active" id="tab-role-cards">
            <div class="row g-3">
              @foreach($allUsers as $u)
                <div class="col-md-6 col-xl-3">
                  <div class="role-matrix-card p-3 shadow-sm {{ ($currentUser['code'] ?? '') === $u->code ? 'active-card' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="badge bg-{{ $u->badge_color }} px-2.5 py-1">{{ $u->role_code }}</span>
                      @if(($currentUser['code'] ?? '') === $u->code)
                        <span class="badge bg-success text-white">Current Active</span>
                      @endif
                    </div>
                    <div class="text-center py-2">
                      <div class="bg-{{ $u->badge_color }} text-white rounded-circle fw-bold mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.1rem;">
                        {{ $u->avatar }}
                      </div>
                      <h6 class="fw-bold mb-0">{{ $u->name }}</h6>
                      <small class="text-muted d-block">{{ $u->role_name }}</small>
                    </div>
                    <div class="small bg-light p-2 rounded mb-3 text-secondary" style="font-size: 0.75rem;">
                      {{ $u->tagline }}
                    </div>
                    <div class="mb-3 flex-grow-1">
                      <div class="fw-bold text-dark small mb-1"><i class="bi bi-check-circle text-success me-1"></i> Responsibilities:</div>
                      <ul class="list-unstyled small mb-2 text-muted ps-2" style="font-size: 0.74rem;">
                        @if($u->responsibilities)
                          @foreach($u->responsibilities as $resp)
                            <li>• {{ $resp }}</li>
                          @endforeach
                        @endif
                      </ul>
                    </div>
                    <a href="{{ route('role.switch', ['role_code' => $u->code]) }}" class="btn btn-sm btn-{{ $u->badge_color }} w-100 mt-auto">
                      <i class="bi bi-box-arrow-in-right me-1"></i> Switch to Persona
                    </a>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- Tab 2: Feature Matrix Table -->
          <div class="tab-pane fade" id="tab-role-matrix">
            <div class="table-responsive bg-white rounded border shadow-sm">
              <table class="table table-hover table-bordered mb-0 role-matrix-table">
                <thead class="table-light">
                  <tr>
                    <th style="width: 28%;">Module / ERP Feature</th>
                    <th class="text-center" style="width: 18%;"><span class="badge bg-primary">Operator</span></th>
                    <th class="text-center" style="width: 18%;"><span class="badge bg-success">Approver</span></th>
                    <th class="text-center" style="width: 18%;"><span class="badge bg-danger">System Admin</span></th>
                    <th class="text-center" style="width: 18%;"><span class="badge bg-warning text-dark">Auditor</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="fw-semibold"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Tally Excel Import</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-secondary"><i class="bi bi-lock"></i> Read Only</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Physical Bill Verification</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Verification</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Verification</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-secondary"><i class="bi bi-eye"></i> Audit Spot-check</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold"><i class="bi bi-arrow-left-right text-primary me-2"></i>Cash Discounts & Returns</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Record Entries</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Authorize & Sign</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-secondary"><i class="bi bi-lock"></i> Read Only</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold"><i class="bi bi-cash-coin text-primary me-2"></i>Credit Collection Register</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Log Recoveries</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-secondary"><i class="bi bi-lock"></i> Read Only</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold"><i class="bi bi-check2-all text-success me-2"></i>Master Reconciliation Signoff</td>
                    <td class="text-center text-warning"><i class="bi bi-eye"></i> Draft & Submit</td>
                    <td class="text-center text-success fw-bold"><i class="bi bi-shield-check"></i> Sign Variance</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Full Access</td>
                    <td class="text-center text-secondary"><i class="bi bi-eye"></i> Compliance Review</td>
                  </tr>
                  <tr class="table-success-subtle">
                    <td class="fw-bold"><i class="bi bi-lock-fill text-success me-2"></i>Daily PSO Sealing (Hash Lock)</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Blocked (No Auth)</td>
                    <td class="text-center text-success fw-bold"><i class="bi bi-shield-lock-fill"></i> Exclusive Authority</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Authorized</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Blocked</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div class="text-muted small"><i class="bi bi-info-circle me-1"></i> You can change roles anytime using the top bar buttons.</div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Guide</button>
      </div>
    </div>
  </div>
</div>
