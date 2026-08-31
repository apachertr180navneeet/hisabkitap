@extends('layouts.app')

@section('title', 'Tally Excel Import')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Tally Excel Import & Ingestion</h4>
    <p class="text-muted mb-0">Import daily bill registers exported from Tally ERP/Prime into the reconciliation pipeline.</p>
  </div>
  <a href="{{ route('import.sample') }}" class="btn btn-outline-secondary">
    <i class="bi bi-download me-1"></i> Download Sample Excel Template
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <h5 class="fw-bold mb-3">Import Configuration</h5>
      <form action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="form-label fw-semibold">Business Date <span class="text-danger">*</span></label>
          <input type="date" name="business_date" class="form-control font-mono" value="{{ $businessDate }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Target PSO Assignment</label>
          <select name="pso_id" class="form-select">
            <option value="ALL" selected>All PSOs (Auto-map by Series Prefix)</option>
            @foreach($psoList as $pso)
              <option value="{{ $pso->code }}">{{ $pso->name }} ({{ $pso->prefix }} {{ $pso->start_no }}-{{ $pso->end_no }})</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Cutoff Time Applied</label>
          <input type="text" class="form-control bg-light" value="{{ $cutoffTime }} IST" readonly>
          <small class="text-muted">Bills stamped after {{ $cutoffTime }} are automatically scheduled for next day's PSO.</small>
        </div>
        
        <!-- Upload Dropzone -->
        <div class="upload-dropzone mb-3" onclick="document.getElementById('excel_file_input').click()">
          <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-2"></i>
          <h6 class="fw-bold mb-1">Drag & Drop Tally DayBook Excel File Here</h6>
          <p class="text-muted small mb-2">Supports .XLSX, .XLS, .CSV files exported from Tally ERP / Prime</p>
          <input type="file" name="excel_file" id="excel_file_input" class="d-none" accept=".xlsx, .xls, .csv">
          <button type="button" class="btn btn-sm btn-primary">Browse Computer</button>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success flex-grow-1">
            <i class="bi bi-arrow-repeat me-1"></i> Ingest & Process
          </button>
          <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <h5 class="fw-bold mb-3">Validation & Audit Diagnostics</h5>
      
      <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle fs-4"></i>
        <div>
          <strong>Ingestion Pipeline Status</strong>
          <div style="font-size: 0.8rem;">Current scan for {{ $businessDate }}: {{ $metrics['totalBillsCount'] }} records found across configured PSO ranges.</div>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <div class="p-2.5 border rounded bg-light">
            <small class="text-muted d-block">Total Records</small>
            <span class="fs-5 fw-bold font-mono text-dark">{{ $metrics['totalBillsCount'] }} Bills</span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2.5 border rounded bg-light">
            <small class="text-muted d-block">Total Tally Amount</small>
            <span class="fs-5 fw-bold font-mono text-primary">₹{{ number_format($metrics['tallyTotal'], 2) }}</span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2.5 border rounded bg-light">
            <small class="text-muted d-block">First Bill Number</small>
            <span class="fw-bold font-mono">CB 01</span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2.5 border rounded bg-light">
            <small class="text-muted d-block">Last Bill Number</small>
            <span class="fw-bold font-mono">RB 10</span>
          </div>
        </div>
      </div>

      <h6 class="fw-bold small text-uppercase text-muted mb-2">Automated Checks:</h6>
      <ul class="list-group list-group-flush small mb-3 border rounded">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-files text-secondary me-2"></i> Duplicate Bills Detected</span>
          <span class="badge bg-success">0 Duplicates</span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-exclamation-circle text-danger me-2"></i> Missing Sequence Gaps</span>
          <span class="badge {{ $metrics['missingCount'] > 0 ? 'bg-danger' : 'bg-success' }}">
            {{ $metrics['missingCount'] > 0 ? ($metrics['missingCount'] . ' Gap (CB 02)') : '0 Gaps' }}
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-clock text-warning me-2"></i> Post-Cutoff Bills (&gt; {{ $cutoffTime }})</span>
          <span class="badge bg-warning text-dark">2 Diverted to Next Day</span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-credit-card text-info me-2"></i> Invalid Payment Types</span>
          <span class="badge bg-success">0 Invalid</span>
        </li>
      </ul>

      <a href="{{ route('verification.index') }}" class="btn btn-primary w-100">
        Proceed to Bill Sequence Verification <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</div>
@endsection
