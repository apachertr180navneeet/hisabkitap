<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? 'Database Migration Dashboard' }} - HisabKitap ERP</title>
  
  <!-- Google Fonts & Bootstrap -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    :root {
      --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --primary: #0F52BA;
      --bg-dark: #0f172a;
    }
    body {
      font-family: var(--font-sans);
      background-color: #f8fafc;
      color: #1e293b;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .font-mono { font-family: var(--font-mono); }
    .top-navbar {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 0.75rem 1.5rem;
    }
    .terminal-window {
      background: #090d16;
      border-radius: 12px;
      border: 1px solid #1e293b;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }
    .terminal-header {
      background: #131b2e;
      padding: 0.6rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #1e293b;
    }
    .terminal-dot {
      width: 11px;
      height: 11px;
      border-radius: 50%;
      display: inline-block;
    }
    .dot-red { background: #ef4444; }
    .dot-yellow { background: #f59e0b; }
    .dot-green { background: #10b981; }
    .terminal-body {
      padding: 1.25rem;
      color: #38bdf8;
      font-family: var(--font-mono);
      font-size: 0.85rem;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-all;
      max-height: 400px;
      overflow-y: auto;
    }
    .badge-status {
      padding: 0.4rem 0.85rem;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 0.78rem;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header class="top-navbar d-flex align-items-center justify-content-between shadow-xs">
    <div class="d-flex align-items-center gap-2">
      <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
        <i class="bi bi-database-fill-gear fs-5"></i>
      </div>
      <div>
        <h6 class="fw-bold text-dark mb-0">HisabKitap Server Database Migration Core</h6>
        <small class="text-muted" style="font-size: 0.75rem;">Direct Online Schema & Migration Manager</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ url('/admin/dashboard') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
      </a>
    </div>
  </header>

  <!-- Content Container -->
  <main class="container my-4 flex-grow-1">
    
    <!-- Status Alert Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <h4 class="fw-bold mb-0">{{ $title }}</h4>
              @if($status === 'success')
                <span class="badge bg-success-subtle text-success border border-success-subtle badge-status">
                  <i class="bi bi-check-circle-fill me-1"></i> COMPLETED
                </span>
              @elseif($status === 'info')
                <span class="badge bg-info-subtle text-info border border-info-subtle badge-status">
                  <i class="bi bi-info-circle-fill me-1"></i> STATUS READY
                </span>
              @else
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle badge-status">
                  <i class="bi bi-exclamation-octagon-fill me-1"></i> NOTICE / ERROR
                </span>
              @endif
            </div>
            <p class="text-muted mb-0 small">{{ $message }}</p>
          </div>

          <!-- Quick Action Buttons -->
          <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/migrate') }}" class="btn btn-sm btn-success">
              <i class="bi bi-play-circle-fill me-1"></i> Run Migration (php artisan migrate)
            </a>
            <a href="{{ url('/migrate/status') }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-list-check me-1"></i> Check Status
            </a>
            <a href="{{ url('/migrate/clear') }}" class="btn btn-sm btn-outline-warning text-dark">
              <i class="bi bi-stars me-1"></i> Clear Cache
            </a>
            <a href="{{ url('/migrate/seed') }}" class="btn btn-sm btn-outline-info" onclick="return confirm('Run database seeds now?');">
              <i class="bi bi-database-add me-1"></i> Seed Database
            </a>
          </div>
        </div>

        <!-- Terminal Output Window -->
        <div class="terminal-window mb-4">
          <div class="terminal-header">
            <div class="d-flex align-items-center gap-1.5">
              <span class="terminal-dot dot-red"></span>
              <span class="terminal-dot dot-yellow"></span>
              <span class="terminal-dot dot-green"></span>
              <span class="text-white-50 small ms-2 font-mono" style="font-size: 0.75rem;">artisan console output</span>
            </div>
            <span class="text-white-50 font-mono small" style="font-size: 0.72rem;">{{ now()->format('d/m/Y H:i:s') }}</span>
          </div>
          <div class="terminal-body">{{ !empty(trim($output)) ? $output : "Command executed successfully with no additional output.\nDatabase tables are fully up to date." }}</div>
        </div>

        <!-- Schema Overview Row -->
        <div class="row g-3">
          <!-- Column 1: Bills Table Structure -->
          <div class="col-lg-6">
            <div class="border rounded p-3 bg-light h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table text-primary me-1"></i> <code>bills</code> Table Columns</h6>
                <span class="badge bg-primary">{{ count($billsColumns) }} Columns</span>
              </div>
              <p class="text-muted small mb-2">Verified columns currently active in the database:</p>
              <div class="d-flex flex-wrap gap-1">
                @forelse($billsColumns as $col)
                  <span class="badge {{ in_array($col, ['particulars', 'voucher_type', 'bill_no', 'amount', 'business_date']) ? 'bg-success' : 'bg-white text-dark border' }} font-mono" style="font-size: 0.73rem;">
                    {{ $col }}
                  </span>
                @empty
                  <span class="text-muted small">Table not found or connection offline.</span>
                @endforelse
              </div>
            </div>
          </div>

          <!-- Column 2: Available Tables in DB -->
          <div class="col-lg-6">
            <div class="border rounded p-3 bg-light h-100">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-database text-success me-1"></i> Active Database Tables</h6>
                <span class="badge bg-success">{{ count($tables) }} Tables</span>
              </div>
              <p class="text-muted small mb-2">Tables detected in current connection:</p>
              <div class="d-flex flex-wrap gap-1">
                @forelse($tables as $tbl)
                  <span class="badge bg-white text-dark border font-mono" style="font-size: 0.73rem;">
                    {{ $tbl }}
                  </span>
                @empty
                  <span class="text-muted small">No tables detected.</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Direct URLs Info Card -->
    <div class="card border bg-white shadow-xs">
      <div class="card-body p-3">
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-link-45deg text-primary me-1"></i> Direct Server Endpoints for Browser / Cron Jobs</h6>
        <div class="row g-2 font-mono" style="font-size: 0.8rem;">
          <div class="col-md-6">
            <div class="p-2 border rounded bg-light">
              <span class="text-muted d-block" style="font-size: 0.7rem;">Run Migration:</span>
              <a href="{{ url('/migrate') }}" target="_blank" class="text-decoration-none fw-semibold text-primary">{{ url('/migrate') }}</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-2 border rounded bg-light">
              <span class="text-muted d-block" style="font-size: 0.7rem;">Migration Status:</span>
              <a href="{{ url('/migrate/status') }}" target="_blank" class="text-decoration-none fw-semibold text-primary">{{ url('/migrate/status') }}</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-2 border rounded bg-light">
              <span class="text-muted d-block" style="font-size: 0.7rem;">Clear All Caches:</span>
              <a href="{{ url('/migrate/clear') }}" target="_blank" class="text-decoration-none fw-semibold text-primary">{{ url('/migrate/clear') }}</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-2 border rounded bg-light">
              <span class="text-muted d-block" style="font-size: 0.7rem;">Seed Database:</span>
              <a href="{{ url('/migrate/seed') }}" target="_blank" class="text-decoration-none fw-semibold text-primary">{{ url('/migrate/seed') }}</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>

  <footer class="py-3 bg-white border-top text-center text-muted small">
    HisabKitap Statutory ERP &copy; {{ date('Y') }} &bull; Database Migration Engine
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
