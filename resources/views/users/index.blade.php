@extends('layouts.app')

@section('title', 'User & Role Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">User & Role Management</h4>
    <p class="text-muted mb-0">Manage system users, role assignments, and granular permission controls.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-user">
    <i class="bi bi-person-plus-fill me-1"></i> Add New User
  </button>
</div>

<!-- 3 Role Summary Cards -->
<div class="row g-4 mb-4">
  <!-- Role 1: Super Admin -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100 p-4 border-top border-4 border-danger bg-white">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="badge bg-danger px-2.5 py-1.5 font-mono">SUPER_ADMIN</div>
        <span class="badge bg-danger-subtle text-danger fw-bold">{{ $stats['superAdmins'] }} Active</span>
      </div>
      <h5 class="fw-bold text-dark mb-1">Super Administrator</h5>
      <p class="text-muted small mb-3">Full Master Administrative Control</p>
      
      <div class="p-2.5 bg-light rounded small text-secondary mb-3" style="font-size: 0.8rem;">
        <i class="bi bi-shield-check text-danger me-1"></i> <strong>All Permissions Enabled:</strong> Master access across all modules, cutoff policies, user administration, and emergency unseal overrides.
      </div>

      <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-secondary-subtle text-dark small">All Modules</span>
        <span class="badge bg-secondary-subtle text-dark small">User Management</span>
        <span class="badge bg-secondary-subtle text-dark small">Cutoff Policy</span>
        <span class="badge bg-secondary-subtle text-dark small">Digital Sealing</span>
      </div>
    </div>
  </div>

  <!-- Role 2: Operator -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100 p-4 border-top border-4 border-primary bg-white">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="badge bg-primary px-2.5 py-1.5 font-mono">OPERATOR</div>
        <span class="badge bg-primary-subtle text-primary fw-bold">{{ $stats['operators'] }} Active</span>
      </div>
      <h5 class="fw-bold text-dark mb-1">PSO Operator</h5>
      <p class="text-muted small mb-3">Daily Counter Operations & Ingestion</p>
      
      <div class="p-2.5 bg-light rounded small text-secondary mb-3" style="font-size: 0.8rem;">
        <i class="bi bi-person-badge text-primary me-1"></i> <strong>Counter Operations:</strong> Series creation, Tally Excel DayBook imports, bill sequence matching, discount logging, and salesman credit collection.
      </div>

      <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-primary-subtle text-primary small">PSO Config</span>
        <span class="badge bg-primary-subtle text-primary small">Tally Import</span>
        <span class="badge bg-primary-subtle text-primary small">Bill Verification</span>
        <span class="badge bg-primary-subtle text-primary small">Credit Collection</span>
      </div>
    </div>
  </div>

  <!-- Role 3: Approver -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100 p-4 border-top border-4 border-success bg-white">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="badge bg-success px-2.5 py-1.5 font-mono">APPROVER</div>
        <span class="badge bg-success-subtle text-success fw-bold">{{ $stats['approvers'] }} Active</span>
      </div>
      <h5 class="fw-bold text-dark mb-1">Accounts Approver</h5>
      <p class="text-muted small mb-3">Variance Review & Cryptographic Day Seal</p>
      
      <div class="p-2.5 bg-light rounded small text-secondary mb-3" style="font-size: 0.8rem;">
        <i class="bi bi-lock-fill text-success me-1"></i> <strong>Approval Authority:</strong> Sign off on reconciliation differences, authorize cash discount deductions, and execute SHA-256 digital seals.
      </div>

      <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-success-subtle text-success small">Master Recon</span>
        <span class="badge bg-success-subtle text-success small">Approval & Seal</span>
        <span class="badge bg-success-subtle text-success small">7-Day Retention</span>
      </div>
    </div>
  </div>
</div>

<!-- Users Table Directory -->
<div class="erp-table-container">
  <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
    <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i> System User Directory ({{ $stats['totalUsers'] }})</h6>
    <span class="text-muted small">Active enterprise accounts & assigned role permissions</span>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle mb-0">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Key Permissions</th>
          <th>Status</th>
          <th>Created</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2.5">
                <div class="bg-{{ $u->badge_color }} text-white rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.9rem;">
                  {{ $u->avatar }}
                </div>
                <div>
                  <div class="fw-bold text-dark">{{ $u->name }}</div>
                  <div class="text-muted font-mono" style="font-size: 0.76rem;">{{ $u->email }}</div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge bg-{{ $u->badge_color }} px-2 py-1">
                <i class="bi {{ $u->icon }} me-1"></i> {{ $u->role_name }}
              </span>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap" style="max-width: 380px;">
                @if($u->isSuperAdmin())
                  <span class="badge bg-danger-subtle text-danger border border-danger small"><i class="bi bi-check-all"></i> ALL PERMISSIONS</span>
                @else
                  @if($u->can_configure_pso) <span class="badge bg-light text-dark border small">PSO Config</span> @endif
                  @if($u->can_import_excel) <span class="badge bg-light text-dark border small">Tally Import</span> @endif
                  @if($u->can_edit_bills) <span class="badge bg-light text-dark border small">Verify Bills</span> @endif
                  @if($u->can_record_corrections) <span class="badge bg-light text-dark border small">Corrections</span> @endif
                  @if($u->can_record_credit) <span class="badge bg-light text-dark border small">Credit</span> @endif
                  @if($u->can_approve_sealing) <span class="badge bg-success-subtle text-success border border-success small">Seal Day</span> @endif
                  @if($u->can_edit_cutoff) <span class="badge bg-warning-subtle text-dark border border-warning small">Cutoff Policy</span> @endif
                  @if($u->can_manage_users) <span class="badge bg-danger-subtle text-danger border border-danger small">User Admin</span> @endif
                @endif
              </div>
            </td>
            <td>
              <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $u->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-muted small">
              {{ $u->created_at ? $u->created_at->format('d-M-Y') : 'System Seed' }}
            </td>
            <td class="text-end">
              <div class="btn-group">
                <!-- Edit User -->
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-user"
                  data-id="{{ $u->id }}"
                  data-name="{{ $u->name }}"
                  data-email="{{ $u->email }}"
                  data-role="{{ $u->role_code }}"
                  data-pso="{{ $u->can_configure_pso ? '1' : '0' }}"
                  data-import="{{ $u->can_import_excel ? '1' : '0' }}"
                  data-bills="{{ $u->can_edit_bills ? '1' : '0' }}"
                  data-corrections="{{ $u->can_record_corrections ? '1' : '0' }}"
                  data-credit="{{ $u->can_record_credit ? '1' : '0' }}"
                  data-seal="{{ $u->can_approve_sealing ? '1' : '0' }}"
                  data-cutoff="{{ $u->can_edit_cutoff ? '1' : '0' }}"
                  data-users="{{ $u->can_manage_users ? '1' : '0' }}"
                  title="Edit User & Permissions">
                  <i class="bi bi-pencil-square"></i>
                </button>

                <!-- Reset Password -->
                <button type="button" class="btn btn-sm btn-outline-warning btn-password-user"
                  data-id="{{ $u->id }}"
                  data-name="{{ $u->name }}"
                  title="Reset Password">
                  <i class="bi bi-key-fill"></i>
                </button>

                <!-- Toggle Status -->
                <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $u->is_active ? 'Deactivate User' : 'Activate User' }}">
                    <i class="bi {{ $u->is_active ? 'bi-person-x-fill text-danger' : 'bi-person-check-fill text-success' }}"></i>
                  </button>
                </form>

                <!-- Delete (only if not current user) -->
                @if(Auth::id() !== $u->id)
                  <form action="{{ route('admin.users.delete', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete user {{ $u->name }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL: ADD NEW USER -->
<div class="modal fade" id="modal-add-user" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Add New System User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Rajesh Kumar" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Work Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" placeholder="e.g. rajesh@hisabkitap.in" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Temporary Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Assign Role <span class="text-danger">*</span></label>
              <select name="role_code" id="add-role-select" class="form-select" required>
                <option value="OPERATOR" selected>PSO Operator (Counter Accountant)</option>
                <option value="APPROVER">Accounts Approver (Variance Signoff & Sealing)</option>
                <option value="SUPER_ADMIN">Super Administrator (All Permissions)</option>
              </select>
            </div>
          </div>

          <div class="border rounded p-3 bg-light mt-3">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-sliders me-1"></i> Granular Module Permissions</h6>
            <p class="text-muted small mb-3">Super Admin automatically receives all permissions. Customize permissions below for Operator or Approver roles.</p>
            
            <div class="row g-2" id="add-permission-checkboxes">
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_configure_pso" id="add_perm_pso" checked>
                  <label class="form-check-label small fw-semibold" for="add_perm_pso">Configure PSO Counter Series</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_import_excel" id="add_perm_import" checked>
                  <label class="form-check-label small fw-semibold" for="add_perm_import">Import Tally DayBook Excel</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_edit_bills" id="add_perm_bills" checked>
                  <label class="form-check-label small fw-semibold" for="add_perm_bills">Verify Sequential Bills & Resolve Missing</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_record_corrections" id="add_perm_corrections" checked>
                  <label class="form-check-label small fw-semibold" for="add_perm_corrections">Log Cash Discounts & Goods Returns</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_record_credit" id="add_perm_credit" checked>
                  <label class="form-check-label small fw-semibold" for="add_perm_credit">Track Salesman Credit Collections</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_approve_sealing" id="add_perm_seal">
                  <label class="form-check-label small fw-semibold" for="add_perm_seal">Execute Cryptographic Day Seal (Approve)</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_edit_cutoff" id="add_perm_cutoff">
                  <label class="form-check-label small fw-semibold" for="add_perm_cutoff">Configure 19:00 Cutoff Policy & Rollover</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input perm-cb" type="checkbox" name="can_manage_users" id="add_perm_users">
                  <label class="form-check-label small fw-semibold" for="add_perm_users">Manage System Users & Roles</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Create User Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: EDIT USER & PERMISSIONS -->
<div class="modal fade" id="modal-edit-user" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit User & Role Permissions</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-edit-user" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Work Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" id="edit-email" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Role Classification <span class="text-danger">*</span></label>
            <select name="role_code" id="edit-role-select" class="form-select" required>
              <option value="OPERATOR">PSO Operator (Counter Accountant)</option>
              <option value="APPROVER">Accounts Approver (Variance Signoff & Sealing)</option>
              <option value="SUPER_ADMIN">Super Administrator (All Permissions)</option>
            </select>
          </div>

          <div class="border rounded p-3 bg-light mt-3">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-sliders me-1"></i> Granted Permissions</h6>
            <div class="row g-2" id="edit-permission-checkboxes">
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_configure_pso" id="edit_perm_pso">
                  <label class="form-check-label small fw-semibold" for="edit_perm_pso">Configure PSO Counter Series</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_import_excel" id="edit_perm_import">
                  <label class="form-check-label small fw-semibold" for="edit_perm_import">Import Tally DayBook Excel</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_edit_bills" id="edit_perm_bills">
                  <label class="form-check-label small fw-semibold" for="edit_perm_bills">Verify Sequential Bills & Resolve Missing</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_record_corrections" id="edit_perm_corrections">
                  <label class="form-check-label small fw-semibold" for="edit_perm_corrections">Log Cash Discounts & Goods Returns</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_record_credit" id="edit_perm_credit">
                  <label class="form-check-label small fw-semibold" for="edit_perm_credit">Track Salesman Credit Collections</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_approve_sealing" id="edit_perm_seal">
                  <label class="form-check-label small fw-semibold" for="edit_perm_seal">Execute Cryptographic Day Seal (Approve)</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_edit_cutoff" id="edit_perm_cutoff">
                  <label class="form-check-label small fw-semibold" for="edit_perm_cutoff">Configure 19:00 Cutoff Policy & Rollover</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="can_manage_users" id="edit_perm_users">
                  <label class="form-check-label small fw-semibold" for="edit_perm_users">Manage System Users & Roles</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: RESET USER PASSWORD -->
<div class="modal fade" id="modal-password-user" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold"><i class="bi bi-key-fill me-2"></i> Reset User Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-password-user" method="POST">
        @csrf
        <div class="modal-body p-4">
          <p class="text-muted small">Setting a new password for <strong id="pwd-user-name" class="text-dark"></strong>.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" name="new_password_confirmation" class="form-control" placeholder="Confirm password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check-lg me-1"></i> Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Role selector dynamic permission presets in Add Modal
  const addRoleSelect = document.getElementById('add-role-select');
  if (addRoleSelect) {
    addRoleSelect.addEventListener('change', function () {
      const role = this.value;
      const cbs = document.querySelectorAll('#add-permission-checkboxes .perm-cb');
      
      if (role === 'SUPER_ADMIN') {
        cbs.forEach(cb => { cb.checked = true; cb.disabled = true; });
      } else if (role === 'APPROVER') {
        cbs.forEach(cb => cb.disabled = false);
        document.getElementById('add_perm_pso').checked = false;
        document.getElementById('add_perm_import').checked = false;
        document.getElementById('add_perm_bills').checked = false;
        document.getElementById('add_perm_corrections').checked = true;
        document.getElementById('add_perm_credit').checked = false;
        document.getElementById('add_perm_seal').checked = true;
        document.getElementById('add_perm_cutoff').checked = false;
        document.getElementById('add_perm_users').checked = false;
      } else { // OPERATOR
        cbs.forEach(cb => cb.disabled = false);
        document.getElementById('add_perm_pso').checked = true;
        document.getElementById('add_perm_import').checked = true;
        document.getElementById('add_perm_bills').checked = true;
        document.getElementById('add_perm_corrections').checked = true;
        document.getElementById('add_perm_credit').checked = true;
        document.getElementById('add_perm_seal').checked = false;
        document.getElementById('add_perm_cutoff').checked = false;
        document.getElementById('add_perm_users').checked = false;
      }
    });
  }

  // Role selector dynamic permission presets in Edit Modal
  const editRoleSelect = document.getElementById('edit-role-select');
  if (editRoleSelect) {
    editRoleSelect.addEventListener('change', function () {
      const role = this.value;
      const cbs = document.querySelectorAll('#edit-permission-checkboxes input[type="checkbox"]');
      
      if (role === 'SUPER_ADMIN') {
        cbs.forEach(cb => { cb.checked = true; cb.disabled = true; });
      } else if (role === 'APPROVER') {
        cbs.forEach(cb => cb.disabled = false);
        document.getElementById('edit_perm_pso').checked = false;
        document.getElementById('edit_perm_import').checked = false;
        document.getElementById('edit_perm_bills').checked = false;
        document.getElementById('edit_perm_corrections').checked = true;
        document.getElementById('edit_perm_credit').checked = false;
        document.getElementById('edit_perm_seal').checked = true;
        document.getElementById('edit_perm_cutoff').checked = false;
        document.getElementById('edit_perm_users').checked = false;
      } else { // OPERATOR
        cbs.forEach(cb => cb.disabled = false);
        document.getElementById('edit_perm_pso').checked = true;
        document.getElementById('edit_perm_import').checked = true;
        document.getElementById('edit_perm_bills').checked = true;
        document.getElementById('edit_perm_corrections').checked = true;
        document.getElementById('edit_perm_credit').checked = true;
        document.getElementById('edit_perm_seal').checked = false;
        document.getElementById('edit_perm_cutoff').checked = false;
        document.getElementById('edit_perm_users').checked = false;
      }
    });
  }

  // Edit user modal fill
  document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const form = document.getElementById('form-edit-user');
      form.action = `/admin/users/${id}/update`;

      document.getElementById('edit-name').value = this.dataset.name;
      document.getElementById('edit-email').value = this.dataset.email;
      document.getElementById('edit-role-select').value = this.dataset.role;

      const isSuperAdmin = (this.dataset.role === 'SUPER_ADMIN' || this.dataset.role === 'ADMIN');
      const cbs = document.querySelectorAll('#edit-permission-checkboxes input[type="checkbox"]');
      
      if (isSuperAdmin) {
        cbs.forEach(cb => { cb.checked = true; cb.disabled = true; });
      } else {
        cbs.forEach(cb => cb.disabled = false);
        document.getElementById('edit_perm_pso').checked = (this.dataset.pso === '1');
        document.getElementById('edit_perm_import').checked = (this.dataset.import === '1');
        document.getElementById('edit_perm_bills').checked = (this.dataset.bills === '1');
        document.getElementById('edit_perm_corrections').checked = (this.dataset.corrections === '1');
        document.getElementById('edit_perm_credit').checked = (this.dataset.credit === '1');
        document.getElementById('edit_perm_seal').checked = (this.dataset.seal === '1');
        document.getElementById('edit_perm_cutoff').checked = (this.dataset.cutoff === '1');
        document.getElementById('edit_perm_users').checked = (this.dataset.users === '1');
      }

      const modal = new bootstrap.Modal(document.getElementById('modal-edit-user'));
      modal.show();
    });
  });

  // Re-enable disabled checkboxes on form submit so values can be sent if needed
  document.querySelectorAll('form').forEach(f => {
    f.addEventListener('submit', function () {
      this.querySelectorAll('input:disabled').forEach(inp => {
        inp.disabled = false;
      });
    });
  });

  // Password reset modal fill
  document.querySelectorAll('.btn-password-user').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const form = document.getElementById('form-password-user');
      form.action = `/admin/users/${id}/change-password`;

      document.getElementById('pwd-user-name').textContent = name;

      const modal = new bootstrap.Modal(document.getElementById('modal-password-user'));
      modal.show();
    });
  });
});
</script>
@endsection
