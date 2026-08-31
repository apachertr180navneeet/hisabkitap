<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | HisabKitap ERP - Fuel Station & PSO Reconciliation</title>
  
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    :root {
      --erp-primary: #1e3a8a;
      --erp-bg-dark: #0b1329;
      --erp-card-bg: rgba(255, 255, 255, 0.98);
      --font-base: 'Inter', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }
    
    body {
      font-family: var(--font-base);
      background: radial-gradient(circle at 10% 20%, #1e3a8a 0%, #0f172a 90%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      color: #1e293b;
    }

    .font-mono {
      font-family: var(--font-mono);
    }

    .login-container {
      width: 100%;
      max-width: 980px;
      background: var(--erp-card-bg);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .brand-side {
      background: linear-gradient(145deg, #1e3a8a, #0f172a);
      color: white;
      padding: 3.5rem 3rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }

    .brand-side::after {
      content: "";
      position: absolute;
      top: 0; right: 0; bottom: 0; left: 0;
      background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
      background-size: 20px 20px;
      pointer-events: none;
    }

    .brand-logo-icon {
      width: 52px;
      height: 52px;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.7rem;
      color: white;
      box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
    }

    .feature-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 0.85rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .feature-item:last-child {
      border-bottom: none;
    }

    .feature-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      color: #60a5fa;
      flex-shrink: 0;
    }

    .form-side {
      padding: 3.5rem 3rem;
      background: #ffffff;
    }

    .form-control {
      padding: 0.75rem 1rem;
      border-color: #cbd5e1;
      font-size: 0.95rem;
    }

    .form-control:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .btn-login {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      color: white;
      font-weight: 600;
      padding: 0.85rem;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
      transition: all 0.2s ease;
    }

    .btn-login:hover {
      background: linear-gradient(135deg, #172554, #1d4ed8);
      color: white;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="row g-0">
      
      <!-- LEFT BRAND & OVERVIEW SIDE -->
      <div class="col-lg-5 brand-side">
        <div>
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="brand-logo-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-white">HisabKitap ERP</h4>
              <small class="text-white-50">Fuel & Counter PSO Reconciliation Core</small>
            </div>
          </div>

          <p class="text-white-50 small mb-4">
            Enterprise statutory accounting platform for fuel station counter sales, Tally ERP DayBook ingestion, credit ledgers, and digital day sealing.
          </p>

          <!-- Core System Capabilities Highlights -->
          <div class="mb-4">
            <div class="feature-item">
              <div class="feature-icon">
                <i class="bi bi-file-earmark-spreadsheet"></i>
              </div>
              <div>
                <div class="fw-bold text-white small">Tally Prime Ingestion</div>
                <div class="text-white-50" style="font-size: 0.76rem;">Automatic DayBook sequence & gap verification</div>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon">
                <i class="bi bi-shield-lock"></i>
              </div>
              <div>
                <div class="fw-bold text-white small">SHA-256 Digital Sealing</div>
                <div class="text-white-50" style="font-size: 0.76rem;">Immutable cryptographic locking of daily books</div>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon">
                <i class="bi bi-person-lines-fill"></i>
              </div>
              <div>
                <div class="fw-bold text-white small">Credit & Ledger Tracking</div>
                <div class="text-white-50" style="font-size: 0.76rem;">Real-time salesman recovery & cash routing</div>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-top border-white border-opacity-10 text-white-50 small d-flex justify-content-between align-items-center">
          <span><i class="bi bi-lock-fill me-1"></i> 256-Bit SSL Encrypted</span>
          <span class="font-mono">v2.4-IND</span>
        </div>
      </div>

      <!-- RIGHT CREDENTIALS LOGIN FORM -->
      <div class="col-lg-7 form-side d-flex flex-column justify-content-between">
        <div>
          <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1">Sign In to Dashboard</h3>
            <p class="text-muted small mb-0">Enter your official ERP work credentials to access the system.</p>
          </div>

          <!-- Flash Alerts -->
          @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
              <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
              <i class="bi bi-exclamation-octagon-fill me-1"></i> {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          <form action="{{ route('admin.login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold" for="email">Work Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" value="{{ old('email') }}" required autofocus>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-semibold mb-0" for="password">Password <span class="text-danger">*</span></label>
              </div>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('bi-eye'); this.querySelector('i').classList.toggle('bi-eye-slash');">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                <label class="form-check-label small text-muted" for="remember">
                  Keep me signed in
                </label>
              </div>
              <a href="javascript:void(0)" class="small text-decoration-none text-primary" onclick="alert('For password resets, please contact your System Administrator (Suresh Gupta).')">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-login w-100 mb-3">
              <i class="bi bi-box-arrow-in-right me-2"></i> Authenticate & Open ERP
            </button>
          </form>

          <div class="p-3 bg-light rounded border text-muted small mt-4 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle text-primary fs-5"></i>
            <span>Authorized access only. All login attempts, sessions, and data transactions are monitored and recorded.</span>
          </div>
        </div>

        <div class="text-center text-muted small mt-4">
          &copy; {{ date('Y') }} HisabKitap Statutory Systems. All rights reserved.
        </div>
      </div>

    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
