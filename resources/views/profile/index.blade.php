@extends('layouts.app')

@section('title', 'My Profile & Security')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">My Account Profile & Security Settings</h4>
    <p class="text-muted mb-0">Manage your ERP operator profile, work email, and login credentials.</p>
  </div>
  <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
  </a>
</div>

<div class="row g-4">
  <!-- Left Side: User Overview Card -->
  <div class="col-lg-4">
    <div class="card border p-4 bg-white shadow-sm text-center h-100">
      <div class="bg-{{ $user->badge_color ?? 'primary' }} text-white rounded-circle fw-bold d-flex align-items-center justify-content-center mx-auto mb-3 shadow" style="width: 76px; height: 76px; font-size: 1.8rem;">
        {{ $user->avatar ?? 'SG' }}
      </div>
      
      <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
      <span class="badge bg-{{ $user->badge_color ?? 'primary' }} mb-3" style="font-size: 0.82rem;">
        {{ $user->role_name }} ({{ $user->code }})
      </span>
      
      <div class="text-muted small mb-4">
        <i class="bi bi-envelope me-1"></i> {{ $user->email }}
      </div>

      <div class="text-start border-top pt-3">
        <h6 class="fw-bold small text-uppercase text-muted mb-2">Role Responsibilities:</h6>
        <ul class="list-unstyled small mb-3">
          @if(!empty($user->responsibilities))
            @foreach($user->responsibilities as $resp)
              <li class="mb-1 text-muted"><i class="bi bi-check2 text-success me-1"></i> {{ $resp }}</li>
            @endforeach
          @else
            <li class="text-muted"><i class="bi bi-check2 text-success me-1"></i> General ERP Operations</li>
          @endif
        </ul>

        <h6 class="fw-bold small text-uppercase text-muted mb-2">Permitted Modules:</h6>
        <div class="d-flex flex-wrap gap-1">
          @if(!empty($user->allowed_modules))
            @foreach($user->allowed_modules as $mod)
              <span class="badge bg-light text-dark border font-mono" style="font-size: 0.7rem;">{{ $mod }}</span>
            @endforeach
          @else
            <span class="badge bg-light text-dark border">ALL MODULES</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side: Forms for Profile & Change Password -->
  <div class="col-lg-8">
    <div class="d-flex flex-column gap-4">
      
      <!-- 1. Edit Profile Form -->
      <div class="card border p-4 bg-white shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-person-gear text-primary fs-4"></i>
          <h5 class="fw-bold mb-0">Personal Profile Details</h5>
        </div>

        <form action="{{ route('admin.profile.update') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Work Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Employee / Persona ID</label>
              <input type="text" class="form-control font-mono bg-light" value="{{ $user->code }}" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Designation / Role</label>
              <input type="text" class="form-control bg-light" value="{{ $user->role_name }}" readonly>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-1"></i> Save Profile Changes
            </button>
          </div>
        </form>
      </div>

      <!-- 2. Change Password Form -->
      <div class="card border p-4 bg-white shadow-sm" id="change-password">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-shield-lock-fill text-warning fs-4"></i>
          <h5 class="fw-bold mb-0">Change Account Password</h5>
        </div>
        <p class="text-muted small mb-3">
          Update your secure ERP login password. Default password for demo accounts is <code>password</code>.
        </p>

        <form action="{{ route('admin.profile.password') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter existing password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('current_password', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" placeholder="Re-type new password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password_confirmation', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-warning text-dark fw-semibold">
              <i class="bi bi-key-fill me-1"></i> Update Password
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
  function togglePass(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye');
      icon.classList.toggle('bi-eye-slash');
    }
  }
</script>
@endsection
