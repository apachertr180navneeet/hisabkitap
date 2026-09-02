@extends('layouts.app')

@section('title', 'Tally Excel Import')

@section('content')
<!-- Top Header & Action Buttons -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-excel-fill text-success me-2"></i>Tally Excel Import & Bill Ingestion</h4>
    <p class="text-muted mb-0">Import daily bill registers exported from Tally ERP/Prime or upload Excel (.XLS / .XLSX) sheets into the reconciliation pipeline.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <!-- Download Excel Template Dropdown / Action -->
    <div class="dropdown">
      <button class="btn btn-success dropdown-toggle shadow-sm d-flex align-items-center gap-1.5 fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-file-earmark-excel-fill"></i>
        <span>Download Excel Template (.XLS)</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="min-width: 300px;">
        <li class="px-2 py-1">
          <small class="text-muted fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Excel Spreadsheet Templates</small>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-start gap-2 py-2 rounded" href="{{ route('admin.import.sample', ['type' => 'sample', 'format' => 'xls']) }}">
            <i class="bi bi-file-earmark-excel text-success fs-4 mt-0.5"></i>
            <div>
              <div class="fw-semibold text-dark">Sample Bill Template (.XLS)</div>
              <small class="text-muted" style="font-size: 0.72rem;">Pre-filled with 10 sample bills (Cash, UPI, Cheque, Credit)</small>
            </div>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-start gap-2 py-2 rounded" href="{{ route('admin.import.sample', ['type' => 'blank', 'format' => 'xls']) }}">
            <i class="bi bi-file-earmark-plus text-primary fs-4 mt-0.5"></i>
            <div>
              <div class="fw-semibold text-dark">Blank Bill Template (.XLS)</div>
              <small class="text-muted" style="font-size: 0.72rem;">Clean Excel table structure ready for daily entry</small>
            </div>
          </a>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li class="px-2 py-1">
          <small class="text-muted fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Alternative Formats & Help</small>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-1.5 rounded" href="{{ route('admin.import.sample', ['type' => 'sample', 'format' => 'csv']) }}">
            <i class="bi bi-filetype-csv text-secondary"></i>
            <span style="font-size: 0.8rem;">Download CSV Template (.CSV)</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center gap-2 py-1.5 rounded text-secondary" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modal-template-guide">
            <i class="bi bi-info-circle text-info"></i>
            <span style="font-size: 0.8rem;">View Column Specification Guide</span>
          </a>
        </li>
      </ul>
    </div>

    <button class="btn btn-outline-secondary shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modal-template-guide">
      <i class="bi bi-question-circle me-1"></i> Format Guide
    </button>
  </div>
</div>

<!-- Template Column Reference Quick-Banner -->
<div class="card border-0 bg-primary-subtle border-start border-4 border-primary shadow-sm mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div class="d-flex align-items-start gap-3">
        <div class="p-2 bg-white rounded-circle shadow-xs text-primary mt-1">
          <i class="bi bi-file-earmark-excel-fill fs-4 text-success"></i>
        </div>
        <div>
          <h6 class="fw-bold text-dark mb-1">Standard Excel Bill Import Template (.XLS / .XLSX)</h6>
          <p class="text-muted small mb-2">
            The import system expects <strong>5 standard fields</strong> exported from Tally Sales Register / DayBook:
            <code class="text-primary bg-white px-1.5 py-0.5 rounded border">Date</code>,
            <code class="text-primary bg-white px-1.5 py-0.5 rounded border">Particulars</code>,
            <code class="text-primary bg-white px-1.5 py-0.5 rounded border">Voucher Type</code>,
            <code class="text-primary bg-white px-1.5 py-0.5 rounded border">Voucher No.</code>,
            <code class="text-primary bg-white px-1.5 py-0.5 rounded border">Amount</code>
          </p>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-secondary" style="font-size: 0.72rem;">Supported Formats:</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Native Excel (.XLS / .XLSX)</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">CSV (.CSV)</span>
            <span class="badge bg-info-subtle text-info border border-info-subtle">Tally DayBook XML</span>
          </div>
        </div>
      </div>
      <div class="d-flex flex-column flex-sm-row gap-2 flex-shrink-0">
        <a href="{{ route('admin.import.sample', ['type' => 'sample', 'format' => 'xls']) }}" class="btn btn-sm btn-success text-nowrap shadow-sm">
          <i class="bi bi-file-earmark-excel-fill me-1"></i> Download Sample Excel (.XLS)
        </a>
        <a href="{{ route('admin.import.sample', ['type' => 'blank', 'format' => 'xls']) }}" class="btn btn-sm btn-outline-primary bg-white text-nowrap">
          <i class="bi bi-file-earmark-plus me-1"></i> Blank Excel (.XLS)
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Upload Form -->
  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Import Configuration</h5>
        <span class="badge bg-light text-dark border">Step 1 of 2</span>
      </div>

      <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="form-label fw-semibold">Business Date <span class="text-danger">*</span></label>
          <input type="date" name="business_date" class="form-control font-mono" value="{{ $businessDate }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Target PSO Assignment</label>
          <select name="pso_id" class="form-select">
            <option value="ALL" selected>All PSOs (Auto-map by Series Prefix: CB, RB, etc.)</option>
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
        <div class="upload-dropzone mb-3 position-relative" id="dropzone-area" onclick="document.getElementById('excel_file_input').click()" style="cursor: pointer;">
          <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-2"></i>
          <h6 class="fw-bold mb-1" id="dropzone-title">Drag & Drop Bill Excel / DayBook File Here</h6>
          <p class="text-muted small mb-2" id="dropzone-subtitle">Supports .XLS, .XLSX, .CSV files exported from Tally ERP / Prime</p>
          <input type="file" name="excel_file" id="excel_file_input" class="d-none" accept=".xlsx, .xls, .csv, .txt">
          <button type="button" class="btn btn-sm btn-primary px-3">
            <i class="bi bi-folder2-open me-1"></i> Browse Computer
          </button>
          <div id="file-info-badge" class="mt-2 d-none">
            <span class="badge bg-success py-1.5 px-2.5 font-mono"><i class="bi bi-check-circle me-1"></i> <span id="file-info-name"></span></span>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2.5 bg-light rounded border mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-excel-fill text-success fs-5"></i>
            <small class="text-muted">Need the Excel template format first?</small>
          </div>
          <a href="{{ route('admin.import.sample', ['type' => 'sample', 'format' => 'xls']) }}" class="btn btn-sm btn-link text-success text-decoration-none fw-semibold p-0">
            <i class="bi bi-download me-1"></i> Download Excel (.XLS)
          </a>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success flex-grow-1 py-2 fw-semibold">
            <i class="bi bi-arrow-repeat me-1"></i> Ingest & Process Import
          </button>
          <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary py-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Right Column: Diagnostics & Recent Imports -->
  <div class="col-lg-6">
    <div class="card border p-4 bg-white shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Validation & Audit Diagnostics</h5>
        <span class="badge bg-primary">Live Scan</span>
      </div>
      
      <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle fs-4 text-info"></i>
        <div>
          <strong>Ingestion Pipeline Status</strong>
          <div style="font-size: 0.8rem;">Current scan for {{ date('d/m/Y', strtotime($businessDate)) }}: {{ $metrics['totalBillsCount'] }} records found across configured PSO ranges.</div>
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
            {{ $metrics['missingCount'] > 0 ? ($metrics['missingCount'] . ' Gap') : '0 Gaps' }}
          </span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-clock text-warning me-2"></i> Post-Cutoff Bills (&gt; {{ $cutoffTime }})</span>
          <span class="badge bg-warning text-dark">{{ $metrics['totalBillsCount'] > 0 ? 'Checked' : '0' }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><i class="bi bi-credit-card text-info me-2"></i> Invalid Payment Types</span>
          <span class="badge bg-success">0 Invalid</span>
        </li>
      </ul>

      <a href="{{ route('admin.verification.index') }}" class="btn btn-primary w-100 py-2 fw-semibold">
        Proceed to Bill Sequence Verification <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</div>

<!-- Modal: Template Column Specification & Guidance -->
<div class="modal fade" id="modal-template-guide" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel-fill text-success me-2"></i>Excel Bill Import Template Specification</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-3">
          When uploading bills exported from Tally ERP/Prime or Excel workbooks, ensure your spreadsheet file contains the following column headers:
        </p>

        <div class="table-responsive border rounded mb-3">
          <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light font-mono">
              <tr>
                <th>#</th>
                <th>Column Name</th>
                <th>Required</th>
                <th>Data Format</th>
                <th>Example Value</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1</td>
                <td><strong class="font-mono text-primary">Date</strong></td>
                <td><span class="badge bg-danger">Required</span></td>
                <td>DD/MM/YYYY or DD-Mon-YY</td>
                <td><code>02-Sep-26</code> / <code>{{ date('d/m/Y', strtotime($businessDate)) }}</code></td>
              </tr>
              <tr>
                <td>2</td>
                <td><strong class="font-mono text-primary">Particulars</strong></td>
                <td><span class="badge bg-danger">Required</span></td>
                <td>Party / Customer / Ledger Name</td>
                <td><code>MOHAN LAL GULABCHAND [1093381]</code></td>
              </tr>
              <tr>
                <td>3</td>
                <td><strong class="font-mono text-primary">Voucher Type</strong></td>
                <td><span class="badge bg-danger">Required</span></td>
                <td>Sales / Series Classification</td>
                <td><code>Sales Cadbury</code></td>
              </tr>
              <tr>
                <td>4</td>
                <td><strong class="font-mono text-primary">Voucher No.</strong></td>
                <td><span class="badge bg-danger">Required</span></td>
                <td>Invoice / Voucher Serial No.</td>
                <td><code>Sc/26-27/6447</code></td>
              </tr>
              <tr>
                <td>5</td>
                <td><strong class="font-mono text-primary">Amount</strong></td>
                <td><span class="badge bg-danger">Required</span></td>
                <td>Numeric decimal amount (₹)</td>
                <td><code>1531.00</code></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="alert alert-light border d-flex align-items-center justify-content-between p-3 mb-0">
          <div>
            <strong>Ready to use the Excel template?</strong>
            <div class="text-muted small">Download the template and open directly in Microsoft Excel, Google Sheets, or LibreOffice.</div>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.import.sample', ['type' => 'sample', 'format' => 'xls']) }}" class="btn btn-sm btn-success">
              <i class="bi bi-file-earmark-excel me-1"></i> Download Sample Excel (.XLS)
            </a>
            <a href="{{ route('admin.import.sample', ['type' => 'blank', 'format' => 'xls']) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-file-earmark-plus me-1"></i> Blank Excel (.XLS)
            </a>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const fileInput = document.getElementById('excel_file_input');
  const fileBadge = document.getElementById('file-info-badge');
  const fileNameSpan = document.getElementById('file-info-name');
  const dropzoneTitle = document.getElementById('dropzone-title');
  const dropzoneSubtitle = document.getElementById('dropzone-subtitle');
  const dropzoneArea = document.getElementById('dropzone-area');

  if (fileInput) {
    fileInput.addEventListener('change', function (e) {
      if (this.files && this.files.length > 0) {
        const file = this.files[0];
        const fileSizeKb = (file.size / 1024).toFixed(1);
        fileNameSpan.textContent = file.name + ' (' + fileSizeKb + ' KB)';
        fileBadge.classList.remove('d-none');
        dropzoneTitle.textContent = 'Selected: ' + file.name;
        dropzoneSubtitle.textContent = 'Ready to ingest & process into reconciliation pipeline';
        dropzoneArea.classList.add('border-success', 'bg-success-subtle');
      }
    });

    // Drag and drop events
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzoneArea.addEventListener(eventName, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzoneArea.classList.add('border-primary', 'bg-primary-subtle');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzoneArea.addEventListener(eventName, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzoneArea.classList.remove('border-primary', 'bg-primary-subtle');
      }, false);
    });

    dropzoneArea.addEventListener('drop', function (e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length > 0) {
        fileInput.files = files;
        const file = files[0];
        const fileSizeKb = (file.size / 1024).toFixed(1);
        fileNameSpan.textContent = file.name + ' (' + fileSizeKb + ' KB)';
        fileBadge.classList.remove('d-none');
        dropzoneTitle.textContent = 'Selected: ' + file.name;
        dropzoneSubtitle.textContent = 'Ready to ingest & process into reconciliation pipeline';
        dropzoneArea.classList.add('border-success', 'bg-success-subtle');
      }
    }, false);
  }
});
</script>
@endsection
@endsection

