<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HisabKitap ERP | Fuel & PSO Bill Reconciliation System</title>
  
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    :root {
      --primary: #1e3a8a;
      --primary-dark: #0f172a;
      --accent: #2563eb;
      --success: #10b981;
      --warning: #f59e0b;
      --font-main: 'Inter', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }

    body {
      font-family: var(--font-main);
      color: #334155;
      background-color: #f8fafc;
      overflow-x: hidden;
    }

    .font-mono {
      font-family: var(--font-mono);
    }

    /* Modern Navbar */
    .landing-nav {
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .landing-brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: #ffffff;
      font-weight: 800;
      font-size: 1.25rem;
      text-decoration: none;
    }

    .brand-icon {
      width: 42px;
      height: 42px;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    /* Hero Section */
    .hero-section {
      background: radial-gradient(circle at 20% 30%, #1e3a8a 0%, #0f172a 85%);
      color: white;
      padding: 6rem 0 7rem;
      position: relative;
      overflow: hidden;
    }

    .hero-section::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
      background-size: 24px 24px;
      pointer-events: none;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 1rem;
      background: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.35);
      border-radius: 9999px;
      color: #93c5fd;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    .hero-title {
      font-size: 3.5rem;
      font-weight: 900;
      line-height: 1.15;
      letter-spacing: -0.02em;
      margin-bottom: 1.5rem;
    }

    .hero-title span {
      background: linear-gradient(135deg, #60a5fa, #38bdf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
      font-size: 1.2rem;
      color: #94a3b8;
      max-width: 650px;
      margin-bottom: 2.5rem;
      line-height: 1.6;
    }

    .btn-hero-primary {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: white;
      font-weight: 700;
      padding: 0.9rem 2rem;
      border-radius: 10px;
      border: none;
      box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.5);
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-hero-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.7);
      color: white;
    }

    .btn-hero-secondary {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      font-weight: 600;
      padding: 0.9rem 1.8rem;
      border-radius: 10px;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-hero-secondary:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
    }

    /* Live Preview Card */
    .hero-card {
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      padding: 1.75rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
    }

    /* Feature Cards */
    .feature-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 2rem;
      transition: all 0.3s ease;
      height: 100%;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
      border-color: #3b82f6;
    }

    .feature-icon-wrapper {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin-bottom: 1.25rem;
    }

    /* Step Timeline */
    .step-pill {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .step-num {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #eff6ff;
      color: #2563eb;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-mono);
    }
  </style>
</head>
<body>

  <!-- TOP NAVIGATION BAR -->
  <nav class="landing-nav py-3">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="{{ route('home') }}" class="landing-brand">
        <div class="brand-icon">
          <i class="bi bi-shield-check"></i>
        </div>
        <div>
          <span>HisabKitap ERP</span>
          <span class="badge bg-primary-subtle text-primary ms-2" style="font-size: 0.65rem;">FUEL EDITION</span>
        </div>
      </a>

      <div class="d-flex align-items-center gap-3">
        <a href="#features" class="text-white-50 text-decoration-none d-none d-md-inline-block small hover-text-white">Features</a>
        <a href="#workflow" class="text-white-50 text-decoration-none d-none d-md-inline-block small hover-text-white">Workflow</a>
        <a href="#roles" class="text-white-50 text-decoration-none d-none d-md-inline-block small hover-text-white">RBAC Roles</a>
        
        <a href="{{ route('login') }}" class="btn btn-sm btn-primary px-3 py-2 fw-semibold rounded-pill">
          <i class="bi bi-box-arrow-in-right me-1"></i> Admin Login
        </a>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-7">
          <div class="hero-badge">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            Automated Fuel PSO & Tally DayBook Reconciliation
          </div>
          <h1 class="hero-title">
            Zero Discrepancy.<br>
            <span>Cryptographically Sealed</span> Daily Accounts.
          </h1>
          <p class="hero-subtitle">
            Enterprise fuel station accounting system. Ingest Tally DayBook registers, cross-verify sequential counter bundles, track credit recoveries, and lock daily books with SHA-256 digital seals.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="{{ route('login') }}" class="btn-hero-primary">
              <i class="bi bi-speedometer2"></i> Open ERP Admin Portal
            </a>
            <a href="#features" class="btn-hero-secondary">
              <i class="bi bi-grid-fill"></i> View System Features
            </a>
          </div>
        </div>

        <div class="col-lg-5">
          <!-- Hero Live Reconciliation Snapshot Card -->
          <div class="hero-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> LIVE SYSTEM</span>
                <span class="text-white-50 small font-mono">{{ $metrics['businessDate'] }}</span>
              </div>
              <span class="badge bg-primary font-mono">Cutoff: 19:00 IST</span>
            </div>

            <div class="p-3 bg-white bg-opacity-10 rounded border border-white border-opacity-10 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50">Tally DayBook Gross</small>
                <span class="text-info fw-bold font-mono">₹{{ number_format($metrics['tallyTotal'], 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50">Verified PSO Collection</small>
                <span class="text-success fw-bold font-mono">₹{{ number_format($metrics['psoCollection'], 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-white-50">Current Variance</small>
                <span class="fw-bold font-mono {{ $metrics['difference'] == 0 ? 'text-success' : 'text-danger' }}">
                  {{ $metrics['difference'] == 0 ? '₹0.00 (Balanced)' : ('₹' . number_format($metrics['difference'], 2)) }}
                </span>
              </div>
            </div>

            <div class="row g-2 text-center text-white small mb-3">
              <div class="col-4">
                <div class="p-2 bg-white bg-opacity-5 rounded">
                  <div class="fw-bold font-mono text-warning">{{ $psoCount }}</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">PSOs Active</div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 bg-white bg-opacity-5 rounded">
                  <div class="fw-bold font-mono text-success">{{ $metrics['matchedCount'] }}/{{ $metrics['totalBillsCount'] }}</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Bills Matched</div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 bg-white bg-opacity-5 rounded">
                  <div class="fw-bold font-mono text-info">₹{{ number_format($metrics['creditPending']) }}</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Credit Pending</div>
                </div>
              </div>
            </div>

            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light w-100 py-2">
              <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Access Dashboard &rarr;
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 5-STEP WORKFLOW TIMELINE -->
  <section id="workflow" class="py-5 bg-white border-bottom">
    <div class="container py-4">
      <div class="text-center max-w-700 mx-auto mb-5">
        <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Standard Operating Procedure</h6>
        <h2 class="fw-bold text-dark">5-Stage Daily Reconciliation Pipeline</h2>
        <p class="text-muted">A structured, auditable pipeline ensuring no fuel sale slip or credit settlement is lost.</p>
      </div>

      <div class="row g-3">
        <div class="col-lg">
          <div class="step-pill">
            <div class="step-num">1</div>
            <div>
              <div class="fw-bold text-dark">Configure PSO</div>
              <small class="text-muted">Series prefix & ranges</small>
            </div>
          </div>
        </div>
        <div class="col-lg">
          <div class="step-pill">
            <div class="step-num">2</div>
            <div>
              <div class="fw-bold text-dark">Tally Import</div>
              <small class="text-muted">DayBook Excel ingestion</small>
            </div>
          </div>
        </div>
        <div class="col-lg">
          <div class="step-pill">
            <div class="step-num">3</div>
            <div>
              <div class="fw-bold text-dark">Bill Verify</div>
              <small class="text-muted">Physical slip checking</small>
            </div>
          </div>
        </div>
        <div class="col-lg">
          <div class="step-pill">
            <div class="step-num">4</div>
            <div>
              <div class="fw-bold text-dark">Reconcile</div>
              <small class="text-muted">Variance comparator</small>
            </div>
          </div>
        </div>
        <div class="col-lg">
          <div class="step-pill">
            <div class="step-num">5</div>
            <div>
              <div class="fw-bold text-dark">Digital Seal</div>
              <small class="text-muted">SHA-256 hash lock</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CORE FEATURES GRID -->
  <section id="features" class="py-5 bg-light">
    <div class="container py-5">
      <div class="text-center max-w-700 mx-auto mb-5">
        <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Engine Capabilities</h6>
        <h2 class="fw-bold text-dark">Everything Needed to Run Modern Fuel Books</h2>
      </div>

      <div class="row g-4">
        <!-- Feature 1 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-primary-subtle text-primary">
              <i class="bi bi-file-earmark-spreadsheet"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Tally DayBook Ingestion</h5>
            <p class="text-muted small mb-0">
              Drag-and-drop Tally Prime Excel exports. Automated schema validation, duplicate detection, and missing sequence scanning.
            </p>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-success-subtle text-success">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Sequence Slip Verification</h5>
            <p class="text-muted small mb-0">
              Cross-check counter slips sequentially. Easily resolve missing bills with instant remark audit logs and counter bundle inspections.
            </p>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-info-subtle text-info">
              <i class="bi bi-qr-code"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Multi-Ledger Routing</h5>
            <p class="text-muted small mb-0">
              Classify collections into Cash, Paytm/UPI, Cheques in hand, Salesman Credit, and Void/Cancelled transactions.
            </p>
          </div>
        </div>

        <!-- Feature 4 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-warning-subtle text-warning">
              <i class="bi bi-person-lines-fill"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Credit Collection Management</h5>
            <p class="text-muted small mb-0">
              Assign field recoveries to salesmen. Log partial or full payments, calculate real-time outstanding balances, and export printable collection sheets.
            </p>
          </div>
        </div>

        <!-- Feature 5 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-danger-subtle text-danger">
              <i class="bi bi-shield-lock"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Digital Cryptographic Sealing</h5>
            <p class="text-muted small mb-0">
              Approved daily balances are permanently locked using SHA-256 cryptographic hashes, preventing backdated edits.
            </p>
          </div>
        </div>

        <!-- Feature 6 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <div class="feature-icon-wrapper bg-secondary-subtle text-dark">
              <i class="bi bi-clock-history"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">7-Day Retention Radar</h5>
            <p class="text-muted small mb-0">
              Visual countdown window for unsealed PSOs with automated daily cutoff (7:00 PM IST) and next-day rollover logic.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ROLE-BASED ACCESS CONTROL (RBAC) -->
  <section id="roles" class="py-5 bg-white border-top">
    <div class="container py-5">
      <div class="text-center max-w-700 mx-auto mb-5">
        <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Governance & Compliance</h6>
        <h2 class="fw-bold text-dark">Pre-Configured Enterprise RBAC Personas</h2>
        <p class="text-muted">Test any role instantly using one-click persona switching.</p>
      </div>

      <div class="row g-4">
        @foreach($users as $u)
          <div class="col-md-6 col-lg-3">
            <div class="card border p-4 text-center h-100 bg-light">
              <div class="bg-{{ $u->badge_color }} text-white rounded-circle fw-bold d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.1rem;">
                {{ $u->avatar }}
              </div>
              <h5 class="fw-bold text-dark mb-1">{{ $u->name }}</h5>
              <span class="badge bg-{{ $u->badge_color }}-subtle text-{{ $u->badge_color }} mb-3">{{ $u->role_name }}</span>
              <p class="text-muted small mb-4">
                {{ $u->responsibilities[0] ?? 'Daily counter operations and verification.' }}
              </p>
              <a href="{{ route('login.quick', ['role_code' => $u->code]) }}" class="btn btn-sm btn-outline-primary mt-auto">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In as {{ $u->role_code }}
              </a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-5 bg-dark text-white border-top border-secondary">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <div class="brand-icon" style="width: 32px; height: 32px; font-size: 1rem;">
            <i class="bi bi-shield-check"></i>
          </div>
          <span class="fw-bold">HisabKitap ERP</span>
          <span class="text-white-50 small ms-2">&copy; {{ date('Y') }} Fuel Station Reconciliation Systems.</span>
        </div>
        <div class="d-flex gap-3 small text-white-50">
          <a href="{{ route('login') }}" class="text-white text-decoration-none">Admin Login</a>
          <a href="{{ route('dashboard') }}" class="text-white text-decoration-none">Dashboard</a>
          <span>Statutory Compliance v2.4</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
