<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'HisabKitap ERP') | PSO & Bill Reconciliation Management System</title>
  
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom ERP Styles -->
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  @yield('styles')
</head>
<body class="{{ $isSealed ? 'is-sealed' : '' }}">

  <!-- App Wrapper -->
  <div id="app-container">
    
    <!-- LEFT SIDEBAR -->
    <aside id="sidebar">
      <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2">
          <div class="sidebar-brand-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="d-flex flex-column text-start">
            <span class="fw-bold text-white fs-6 lh-1">HisabKitap ERP</span>
            <span class="text-secondary" style="font-size: 0.72rem;">Fuel & PSO Recon Core</span>
          </div>
        </a>
      </div>

      <ul class="sidebar-menu">
        <li class="menu-category">Core Operations</li>
        <li>
          <a href="{{ route('admin.dashboard') }}" class="nav-item-custom {{ request()->routeIs('*.dashboard') || request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.pso.index') }}" class="nav-item-custom {{ request()->routeIs('*.pso.*') || request()->routeIs('pso.*') ? 'active' : '' }}">
            <i class="bi bi-diagram-3-fill"></i>
            <span>PSO Management</span>
            <span class="badge bg-primary">{{ $globalMetrics['activePsoCount'] ?? 0 }}</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.import.index') }}" class="nav-item-custom {{ request()->routeIs('*.import.*') || request()->routeIs('import.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
            <span>Tally Excel Import</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.verification.index') }}" class="nav-item-custom {{ request()->routeIs('*.verification.*') || request()->routeIs('verification.*') ? 'active' : '' }}">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Bill Verification</span>
            @if(($globalMetrics['missingCount'] ?? 0) > 0)
              <span class="badge bg-danger">{{ $globalMetrics['missingCount'] }}</span>
            @else
              <span class="badge bg-success">{{ $globalMetrics['totalBillsCount'] ?? 0 }}</span>
            @endif
          </a>
        </li>

        <li class="menu-category">Financial Workflow</li>
        <li>
          <a href="{{ route('admin.payment.index') }}" class="nav-item-custom {{ request()->routeIs('*.payment.*') || request()->routeIs('payment.*') ? 'active' : '' }}">
            <i class="bi bi-wallet2"></i>
            <span>Payment Classification</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.corrections.index') }}" class="nav-item-custom {{ request()->routeIs('*.corrections.*') || request()->routeIs('corrections.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i>
            <span>Corrections / Returns</span>
            <span class="badge bg-secondary">{{ $globalMetrics['correctionsCount'] ?? 0 }}</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.credit.index') }}" class="nav-item-custom {{ request()->routeIs('*.credit.*') || request()->routeIs('credit.*') ? 'active' : '' }}">
            <i class="bi bi-cash-coin"></i>
            <span>Credit Collection</span>
            <span class="badge bg-warning text-dark">{{ $globalMetrics['creditRecordsCount'] ?? 0 }}</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.summary.index') }}" class="nav-item-custom {{ request()->routeIs('*.summary.*') || request()->routeIs('summary.*') ? 'active' : '' }}">
            <i class="bi bi-table"></i>
            <span>PSO Summary</span>
          </a>
        </li>

        <li class="menu-category">Recon & Compliance</li>
        <li>
          <a href="{{ route('admin.reconciliation.index') }}" class="nav-item-custom {{ request()->routeIs('*.reconciliation.*') || request()->routeIs('reconciliation.*') ? 'active' : '' }}">
            <i class="bi bi-check2-all"></i>
            <span>Master Reconciliation</span>
            @if($isSealed)
              <span class="badge bg-success">SEALED</span>
            @elseif(!($globalMetrics['hasBills'] ?? false))
              <span class="badge bg-secondary">NO BILLS</span>
            @elseif($globalMetrics['isReconciled'])
              <span class="badge bg-success">PASS</span>
            @else
              <span class="badge bg-danger">FAIL</span>
            @endif
          </a>
        </li>
        <li>
          <a href="{{ route('admin.approval.index') }}" class="nav-item-custom {{ request()->routeIs('*.approval.*') || request()->routeIs('approval.*') ? 'active' : '' }}">
            <i class="bi bi-lock-fill"></i>
            <span>Approval & Sealing</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.retention.index') }}" class="nav-item-custom {{ request()->routeIs('*.retention.*') || request()->routeIs('retention.*') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i>
            <span>7-Day Retention</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.reports.index') }}" class="nav-item-custom {{ request()->routeIs('*.reports.*') || request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>
            <span>Reports & Exports</span>
          </a>
        </li>
        <li>
          <a href="{{ route('admin.settings.index') }}" class="nav-item-custom {{ request()->routeIs('*.settings.*') || request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i>
            <span>Cutoff & Settings</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success rounded-circle p-1">&nbsp;</span>
            <span class="text-white-50" style="font-size: 0.75rem;">MySQL Online</span>
          </div>
          <span class="text-secondary font-mono" style="font-size: 0.7rem;">v2.4-LARAVEL</span>
        </div>
        <div class="pt-1 border-top border-secondary text-center">
          <a href="{{ route('home') }}" class="text-white-50 text-decoration-none small" style="font-size: 0.72rem;">
            <i class="bi bi-house-door me-1"></i> Visit Public Landing Page
          </a>
        </div>
      </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div id="main-wrapper">
      
      <!-- TOP HEADER -->
      <header id="top-header">
        <div class="d-flex align-items-center gap-3">
          <!-- Mobile Toggle -->
          <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btn-toggle-sidebar">
            <i class="bi bi-list fs-5"></i>
          </button>
          
          <!-- Business Date Badge -->
          <div class="d-flex align-items-center gap-2 bg-light border px-2.5 py-1 rounded">
            <i class="bi bi-calendar3 text-primary"></i>
            <span class="text-muted" style="font-size: 0.8rem;">Date:</span>
            <span class="fw-semibold font-mono" style="font-size: 0.82rem;">{{ $businessDate }}</span>
          </div>

          <!-- Cutoff Rule Indicator -->
          <div class="d-none d-md-flex align-items-center gap-1.5 bg-light border px-2.5 py-1 rounded text-muted" style="font-size: 0.8rem;">
            <i class="bi bi-alarm text-warning"></i>
            <span>Daily Cutoff:</span>
            <span class="fw-bold text-dark font-mono">{{ $cutoffTime }} IST</span>
          </div>
        </div>

        <!-- Right User & Status Area -->
        <div class="d-flex align-items-center gap-3">
          
          <!-- Reconciliation Status Pill -->
          @if($isSealed)
            <div class="badge bg-success d-flex align-items-center gap-1.5 py-2 px-2.5">
              <i class="bi bi-shield-lock-fill"></i>
              <span>DIGITALLY SEALED (APPROVED)</span>
            </div>
          @elseif(!($globalMetrics['hasBills'] ?? false))
            <div class="badge bg-secondary d-flex align-items-center gap-1.5 py-2 px-2.5">
              <i class="bi bi-inbox-fill"></i>
              <span>AWAITING TALLY IMPORT</span>
            </div>
          @elseif($globalMetrics['isReconciled'])
            <div class="badge bg-success d-flex align-items-center gap-1.5 py-2 px-2.5">
              <i class="bi bi-shield-check"></i>
              <span>RECONCILIATION MATCHED (READY TO SEAL)</span>
            </div>
          @else
            <div class="badge bg-danger d-flex align-items-center gap-1.5 py-2 px-2.5">
              <i class="bi bi-shield-x"></i>
              <span>RECONCILIATION FAILED (BLOCKED)</span>
            </div>
          @endif

          <!-- Notification Dropdown -->
          <div class="dropdown">
            <button class="btn btn-light position-relative rounded-circle p-2" data-bs-toggle="dropdown">
              <i class="bi bi-bell text-secondary"></i>
              <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow p-3" style="width: 320px;">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">Live ERP Alerts</h6>
                <span class="badge bg-primary">Active</span>
              </div>
              <hr class="my-2">
              <div class="d-flex flex-column gap-2" style="font-size: 0.8rem;">
                @if($globalMetrics['missingCount'] > 0)
                  <div class="p-2 bg-light rounded border-start border-3 border-danger">
                    <div class="fw-semibold text-danger">Missing Bills Detected ({{ $globalMetrics['missingCount'] }})</div>
                    <div class="text-muted">₹{{ number_format($globalMetrics['difference']) }} pending physical verification bundle.</div>
                  </div>
                @endif
                <div class="p-2 bg-light rounded border-start border-3 border-warning">
                  <div class="fw-semibold text-warning">Post-Cutoff Bills</div>
                  <div class="text-muted">Bills recorded after {{ $cutoffTime }} automatically assigned to next day's PSO.</div>
                </div>
                <div class="p-2 bg-light rounded border-start border-3 border-info">
                  <div class="fw-semibold text-info">Credit Due Alert</div>
                  <div class="text-muted">₹{{ number_format($globalMetrics['creditPending']) }} field credit pending recovery.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- User Profile & Role Switcher -->
          <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 border py-1.5 px-2.5 rounded-pill" data-bs-toggle="dropdown">
              <div class="bg-{{ $currentUser['badge_color'] ?? 'primary' }} text-white rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                {{ $currentUser['avatar'] ?? 'RS' }}
              </div>
              <div class="text-start d-none d-sm-block">
                <div class="fw-semibold lh-1" style="font-size: 0.82rem;">{{ $currentUser['name'] ?? 'Ramesh Sharma' }}</div>
                <div class="text-muted" style="font-size: 0.7rem;">{{ $currentUser['role_name'] ?? 'PSO Operator' }}</div>
              </div>
              <i class="bi bi-chevron-down text-muted" style="font-size: 0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="min-width: 240px;">
              <!-- User Info Header -->
              <li class="p-2 border-bottom mb-2 bg-light rounded">
                <div class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $currentUser['name'] ?? 'Suresh Gupta' }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $currentUser['email'] ?? 'admin@hisabkitap.in' }}</div>
                <span class="badge bg-{{ $currentUser['badge_color'] ?? 'primary' }} mt-1" style="font-size: 0.68rem;">
                  {{ $currentUser['role_name'] ?? 'System Administrator' }}
                </span>
              </li>
              
              <!-- 1. My Profile -->
              <li>
                <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('admin.profile') }}">
                  <i class="bi bi-person-circle text-primary fs-5 me-2.5"></i>
                  <div>
                    <div class="fw-semibold">My Profile</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Account & operator details</small>
                  </div>
                </a>
              </li>

              <!-- 2. Change Password -->
              <li>
                <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('admin.profile') }}#change-password">
                  <i class="bi bi-key-fill text-warning fs-5 me-2.5"></i>
                  <div>
                    <div class="fw-semibold">Change Password</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Update security credentials</small>
                  </div>
                </a>
              </li>

              <li><hr class="dropdown-divider my-2"></li>

              <!-- 3. Logout -->
              <li>
                <a class="dropdown-item d-flex align-items-center text-danger py-2" href="{{ route('admin.logout.get') }}">
                  <i class="bi bi-box-arrow-right fs-5 me-2.5"></i>
                  <div class="fw-semibold">Logout</div>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- AUDITOR READ-ONLY INSPECTION NOTICE (Visible only when in Auditor role) -->
      @if(($currentUser['role_code'] ?? '') === 'AUDITOR')
        <div class="alert alert-warning border-warning mx-4 mt-3 mb-0 d-flex align-items-center justify-content-between py-2 px-3 shadow-sm">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation text-warning fs-4"></i>
            <div>
              <strong class="text-dark">Auditor Inspection Mode Active ({{ $currentUser['name'] ?? 'Auditor' }})</strong>
              <div style="font-size: 0.78rem;" class="text-muted">You have Read-Only statutory oversight. All modifications, imports, and day seal actions are disabled to ensure compliance.</div>
            </div>
          </div>
        </div>
      @endif

      <!-- SEALED WATERMARK BANNER (Appears when day is locked/sealed) -->
      @if($isSealed)
        <div class="sealed-watermark mx-4 mt-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-lock-fill fs-4"></i>
            <div>
              <div class="fw-bold">PSO SEALED & LOCKED FOR BUSINESS DATE {{ $businessDate }}</div>
              <div style="font-size: 0.78rem;">All records are read-only. Sealed by <strong>{{ $sealInfo->sealed_by ?? 'Authorized Signatory' }}</strong> with Digital Hash Token <span class="font-mono">{{ $sealInfo->seal_hash ?? '#HK-8891-SEAL' }}</span></div>
            </div>
          </div>
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modal-seal-cert">
            <i class="bi bi-file-earmark-check me-1"></i> View Certificate
          </button>
        </div>
      @endif

      <!-- Flash Messages -->
      <div class="px-4 pt-3">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show shadow-sm mb-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-0" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif
      </div>

      <!-- MAIN CONTENT INJECTION -->
      <main class="content-view active">
        @yield('content')
      </main>

    </div>
  </div>

  <!-- ALL MODALS INCLUDE -->
  @include('partials.modals')

  <!-- Toast Notification Container -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="erp-toast" class="toast align-items-center text-white bg-primary border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2" id="erp-toast-body">
          <i class="bi bi-info-circle-fill"></i>
          <span>Action executed successfully.</span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Application JS Controller -->
  <script src="{{ asset('js/hisabkitap.js') }}"></script>
  @yield('scripts')
</body>
</html>
