// PSO & Bill Reconciliation Management System - Application Controller
document.addEventListener("DOMContentLoaded", () => {
  
  // Clone initial state from window.INITIAL_DATA
  const state = JSON.parse(JSON.stringify(window.INITIAL_DATA));

  // Helper: Format Currency in Indian Rupee format (₹ X,XX,XXX)
  function formatINR(val) {
    const num = Number(val) || 0;
    const isNeg = num < 0;
    const absVal = Math.abs(num);
    const formatted = absVal.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    return (isNeg ? "-₹" : "₹") + formatted;
  }

  // Toast Helper
  function showToast(message, type = "primary") {
    const toastEl = document.getElementById("erp-toast");
    const toastBody = document.getElementById("erp-toast-body");
    if (!toastEl || !toastBody) return;

    toastEl.className = `toast align-items-center text-white bg-${type} border-0 shadow-lg`;
    toastBody.innerHTML = `<i class="bi bi-info-circle-fill me-2"></i><span>${message}</span>`;
    const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
    bsToast.show();
  }

  // --- NAVIGATION & ROUTING ---
  const navLinks = document.querySelectorAll(".nav-item-custom");
  const contentViews = document.querySelectorAll(".content-view");

  function switchView(targetViewId) {
    navLinks.forEach(link => {
      if (link.dataset.view === targetViewId) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });

    contentViews.forEach(view => {
      if (view.id === targetViewId) {
        view.classList.add("active");
      } else {
        view.classList.remove("active");
      }
    });

    // Refresh specific view data on navigate
    if (targetViewId === "view-dashboard") renderDashboard();
    if (targetViewId === "view-pso-mgmt") renderPsoMgmt();
    if (targetViewId === "view-bill-verification") renderBillVerification();
    if (targetViewId === "view-payment-class") renderPaymentClassification();
    if (targetViewId === "view-corrections") renderCorrections();
    if (targetViewId === "view-credit-collection") renderCreditCollections();
    if (targetViewId === "view-pso-summary") renderPsoSummary();
    if (targetViewId === "view-master-recon") renderMasterRecon();
    if (targetViewId === "view-approval-sealing") renderApprovalSealing();
    if (targetViewId === "view-retention") renderRetention();
    if (targetViewId === "view-reports") renderReports();
    if (targetViewId === "view-settings") renderSettings();
  }

  navLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const target = link.dataset.view;
      if (target) switchView(target);
    });
  });

  // Direct buttons navigation
  document.addEventListener("click", (e) => {
    const directBtn = e.target.closest(".nav-direct-btn");
    if (directBtn) {
      const target = directBtn.dataset.target;
      if (target) switchView(target);
    }
  });

  // Mobile sidebar toggle
  const btnToggleSidebar = document.getElementById("btn-toggle-sidebar");
  const sidebar = document.getElementById("sidebar");
  if (btnToggleSidebar && sidebar) {
    btnToggleSidebar.addEventListener("click", () => {
      sidebar.classList.toggle("show-mobile");
    });
  }

  // --- RECONCILIATION & AGGREGATE CALCULATIONS ENGINE ---
  function calculateMetrics() {
    let tallyTotal = 0;
    let pso1Total = 0;
    let pso2Total = 0;
    let pso3Total = 0;

    let totCash = 0;
    let totPaytm = 0;
    let totCheck = 0;
    let totCredit = 0;
    let totCancelled = 0;

    let totCd = 0;
    let totRefund = 0;

    let matchedCount = 0;
    let missingCount = 0;
    let totalBillsCount = state.bills.length;

    state.bills.forEach(bill => {
      // In Tally, we sum the full billing amount
      tallyTotal += bill.amount;

      // Net deduction
      totCd += (bill.cd || 0);
      totRefund += (bill.refund || 0);

      // Status check
      if (bill.status === "Matched") matchedCount++;
      if (bill.status === "Missing") missingCount++;

      // Payments breakdown
      if (bill.paymentType === "Cash") totCash += (bill.netAmount || bill.amount);
      if (bill.paymentType === "Paytm") totPaytm += (bill.netAmount || bill.amount);
      if (bill.paymentType === "Check") totCheck += (bill.netAmount || bill.amount);
      if (bill.paymentType === "Credit") totCredit += (bill.netAmount || bill.amount);
      if (bill.paymentType === "Cancelled") totCancelled += bill.amount;

      // PSO Breakdown (Only if not missing)
      const effectiveAmt = (bill.status === "Missing") ? 0 : bill.netAmount;

      if (bill.psoId === "PSO-1") pso1Total += effectiveAmt;
      if (bill.psoId === "PSO-2") pso2Total += effectiveAmt;
      if (bill.psoId === "PSO-3") pso3Total += effectiveAmt;
    });

    // Total PSO Collection is the physical sum verified across 3 PSOs
    const psoCollection = pso1Total + pso2Total + pso3Total;
    const difference = tallyTotal - psoCollection;
    const isReconciled = (difference === 0 && missingCount === 0);

    return {
      tallyTotal,
      pso1Total,
      pso2Total,
      pso3Total,
      psoCollection,
      difference,
      isReconciled,
      totCash,
      totPaytm,
      totCheck,
      totCredit,
      totCancelled,
      totCd,
      totRefund,
      matchedCount,
      missingCount,
      totalBillsCount
    };
  }

  // --- RENDER 1: DASHBOARD ---
  function renderDashboard() {
    const metrics = calculateMetrics();

    // Top badges
    document.getElementById("badge-pso-count").textContent = state.psoSeries.length;
    document.getElementById("badge-missing-count").textContent = metrics.missingCount;
    document.getElementById("badge-corr-count").textContent = state.corrections.length;
    document.getElementById("badge-credit-count").textContent = state.creditCollections.length;

    // Header Recon Badge
    const headerReconBadge = document.getElementById("header-recon-badge");
    const headerReconText = document.getElementById("header-recon-text");
    const badgeReconStatus = document.getElementById("badge-recon-status");

    if (state.isSealed) {
      headerReconBadge.className = "badge badge-sealed d-flex align-items-center gap-1.5 py-2 px-2.5";
      headerReconText.textContent = "PSO SEALED & LOCKED";
      badgeReconStatus.className = "badge bg-success";
      badgeReconStatus.textContent = "SEALED";
    } else if (metrics.isReconciled) {
      headerReconBadge.className = "badge bg-success d-flex align-items-center gap-1.5 py-2 px-2.5";
      headerReconText.textContent = "RECONCILIATION SUCCESSFUL (ENABLED)";
      badgeReconStatus.className = "badge bg-success";
      badgeReconStatus.textContent = "PASS";
    } else {
      headerReconBadge.className = "badge bg-danger d-flex align-items-center gap-1.5 py-2 px-2.5";
      headerReconText.textContent = "RECONCILIATION FAILED (BLOCKED)";
      badgeReconStatus.className = "badge bg-danger";
      badgeReconStatus.textContent = "FAIL";
    }

    // Dashboard Alert Banner
    const dashAlert = document.getElementById("dash-recon-alert");
    if (dashAlert) {
      if (state.isSealed) {
        dashAlert.className = "alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm";
        dashAlert.innerHTML = `
          <div class="d-flex align-items-center gap-2.5">
            <i class="bi bi-shield-check fs-4 text-success"></i>
            <div>
              <strong>Daily Records Officially Sealed!</strong>
              <div style="font-size: 0.82rem;">Business Date <strong>14-Aug-2026</strong> has been validated, audited, and locked into permanent read-only archive.</div>
            </div>
          </div>
          <button class="btn btn-sm btn-success" id="btn-view-cert-dash"><i class="bi bi-file-earmark-check me-1"></i> View Certificate</button>
        `;
        document.getElementById("btn-view-cert-dash")?.addEventListener("click", () => {
          const certModal = new bootstrap.Modal(document.getElementById("modal-seal-cert"));
          certModal.show();
        });
      } else if (metrics.isReconciled) {
        dashAlert.className = "alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm";
        dashAlert.innerHTML = `
          <div class="d-flex align-items-center gap-2.5">
            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
            <div>
              <strong>Reconciliation Successful (Zero Discrepancy)</strong>
              <div style="font-size: 0.82rem;">Tally Total matches verified PSO Collections exactly at <strong>${formatINR(metrics.tallyTotal)}</strong>. System is ready for final Approval & Sealing.</div>
            </div>
          </div>
          <button class="btn btn-sm btn-success nav-direct-btn" data-target="view-approval-sealing">Proceed to Seal <i class="bi bi-arrow-right"></i></button>
        `;
      } else {
        dashAlert.className = "alert alert-danger d-flex align-items-center justify-content-between mb-4 shadow-sm";
        dashAlert.innerHTML = `
          <div class="d-flex align-items-center gap-2.5">
            <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
            <div>
              <strong>Reconciliation Discrepancy Detected!</strong>
              <div style="font-size: 0.82rem;">Tally Total is <strong>${formatINR(metrics.tallyTotal)}</strong> while verified PSO Collection is <strong>${formatINR(metrics.psoCollection)}</strong> (Difference: <span class="text-danger fw-bold">${formatINR(metrics.difference)}</span>). Approval is currently <strong>BLOCKED</strong>.</div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-danger nav-direct-btn" data-target="view-bill-verification">Investigate Missing Bill</button>
            <button class="btn btn-sm btn-outline-dark nav-direct-btn" data-target="view-master-recon">Recon Screen</button>
          </div>
        `;
      }
    }

    // KPI Values
    document.getElementById("kpi-pso-count").textContent = `${state.psoSeries.length} Active`;
    document.getElementById("kpi-tally-amount").textContent = formatINR(metrics.tallyTotal);
    document.getElementById("kpi-pso-collection").textContent = formatINR(metrics.psoCollection);
    document.getElementById("kpi-diff-amount").textContent = formatINR(metrics.difference);
    document.getElementById("kpi-diff-amount").className = `kpi-value font-mono ${metrics.difference === 0 ? 'text-success' : 'text-danger'}`;
    document.getElementById("kpi-diff-status").textContent = metrics.difference === 0 ? "Perfect Match (₹0 Variance)" : "Discrepancy Unresolved";

    document.getElementById("kpi-matched-bills").textContent = `${metrics.matchedCount} / ${metrics.totalBillsCount}`;
    document.getElementById("kpi-missing-bills").textContent = `${metrics.missingCount} Bill${metrics.missingCount === 1 ? '' : 's'}`;
    
    // Credit pending total
    const totalCreditOut = state.creditCollections.reduce((acc, c) => acc + c.outstanding, 0);
    document.getElementById("kpi-credit-pending").textContent = formatINR(totalCreditOut);

    const approvalStatusEl = document.getElementById("kpi-approval-status");
    if (state.isSealed) {
      approvalStatusEl.innerHTML = `<span class="badge badge-sealed">SEALED</span>`;
    } else if (metrics.isReconciled) {
      approvalStatusEl.innerHTML = `<span class="badge bg-success">READY TO SEAL</span>`;
    } else {
      approvalStatusEl.innerHTML = `<span class="badge badge-blocked">BLOCKED</span>`;
    }

    // Dashboard Payment Breakdown
    document.getElementById("dash-pay-cash").textContent = formatINR(metrics.totCash);
    document.getElementById("dash-pay-paytm").textContent = formatINR(metrics.totPaytm);
    document.getElementById("dash-pay-check").textContent = formatINR(metrics.totCheck);
    document.getElementById("dash-pay-credit").textContent = formatINR(metrics.totCredit);
    document.getElementById("dash-pay-cancelled").textContent = formatINR(metrics.totCancelled);

    // Dashboard PSO Summary Table
    const psoSummaryBody = document.getElementById("dash-pso-summary-body");
    psoSummaryBody.innerHTML = `
      <tr>
        <td class="fw-bold text-primary">PSO 1</td>
        <td><span class="badge bg-light text-dark border">CB 01 – CB 10</span></td>
        <td>10</td>
        <td class="font-mono">₹2,25,000</td>
        <td class="font-mono text-success fw-bold">${formatINR(metrics.pso1Total)}</td>
        <td>Ramesh Sharma</td>
        <td>${metrics.pso1Total === 225000 ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-danger">Missing Slips</span>'}</td>
      </tr>
      <tr>
        <td class="fw-bold text-primary">PSO 2</td>
        <td><span class="badge bg-light text-dark border">CB 11 – CB 20 + ITC</span></td>
        <td>12</td>
        <td class="font-mono">₹2,80,000</td>
        <td class="font-mono text-success fw-bold">${formatINR(metrics.pso2Total)}</td>
        <td>Rajesh Kumar</td>
        <td><span class="badge bg-success">Verified</span></td>
      </tr>
      <tr>
        <td class="fw-bold text-primary">PSO 3</td>
        <td><span class="badge bg-light text-dark border">RB 01 – RB 10</span></td>
        <td>10</td>
        <td class="font-mono">₹1,77,500</td>
        <td class="font-mono text-success fw-bold">${formatINR(metrics.pso3Total)}</td>
        <td>Amit Saxena</td>
        <td><span class="badge bg-success">Verified</span></td>
      </tr>
    `;

    document.getElementById("dash-pso-tot-bills").textContent = metrics.totalBillsCount;
    document.getElementById("dash-pso-tot-gross").textContent = formatINR(metrics.tallyTotal);
    document.getElementById("dash-pso-tot-net").textContent = formatINR(metrics.psoCollection);

    // Dashboard Recent Imports Table
    const recentImportsBody = document.getElementById("dash-recent-imports-body");
    recentImportsBody.innerHTML = state.recentImports.map(item => `
      <tr>
        <td class="fw-semibold font-mono"><i class="bi bi-file-earmark-excel text-success me-1"></i>${item.filename}</td>
        <td>${item.time}</td>
        <td>${item.records} Bills</td>
        <td class="font-mono text-primary">${formatINR(item.amount)}</td>
        <td><span class="badge bg-light text-dark border">${item.status}</span></td>
      </tr>
    `).join("");

    // Dashboard Retention Table
    const dashRetentionBody = document.getElementById("dash-retention-body");
    dashRetentionBody.innerHTML = state.retentionPsoList.slice(0, 3).map(item => `
      <tr>
        <td class="fw-bold">${item.psoCode}</td>
        <td>${item.businessDate}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary font-mono">${item.daysRemaining}d Left</span>
            <div class="progress flex-grow-1" style="height: 6px;">
              <div class="progress-bar ${item.daysRemaining <= 2 ? 'bg-danger' : 'bg-primary'}" style="width: ${(item.daysRemaining / 7) * 100}%"></div>
            </div>
          </div>
        </td>
        <td><span class="badge ${item.badgeClass}">${item.status}</span></td>
        <td><button class="btn btn-xs btn-outline-primary nav-direct-btn" data-target="view-retention">View</button></td>
      </tr>
    `).join("");
  }

  // --- RENDER 2: PSO MANAGEMENT ---
  function renderPsoMgmt() {
    const tableBody = document.getElementById("pso-mgmt-table-body");
    const canConfigure = state.currentUser.canConfigurePso || state.currentUser.roleCode === "OPERATOR" || state.currentUser.roleCode === "ADMIN" || state.currentUser.roleCode === "APPROVER";
    const isAuditor = state.currentUser.roleCode === "AUDITOR";
    const alertContainer = document.getElementById("pso-mgmt-alert-container");
    const btnConfigurePso = document.getElementById("btn-configure-pso");

    if (btnConfigurePso) {
      if (canConfigure) {
        btnConfigurePso.classList.remove("disabled");
        btnConfigurePso.removeAttribute("disabled");
        btnConfigurePso.innerHTML = `<i class="bi bi-plus-circle me-1"></i> Configure New PSO`;
      } else {
        btnConfigurePso.classList.add("disabled");
        btnConfigurePso.setAttribute("disabled", "true");
        btnConfigurePso.innerHTML = `<i class="bi bi-lock-fill me-1"></i> Configure PSO (Auditor Read-Only)`;
      }
    }

    if (alertContainer) {
      if (canConfigure) {
        alertContainer.innerHTML = `
          <div class="alert alert-primary d-flex align-items-center justify-content-between py-2 px-3 mb-3 small shadow-sm">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-person-badge-fill text-primary fs-5"></i>
              <div>
                <strong>PSO Series Authoring Mode (${state.currentUser.name} - ${state.currentUser.role}):</strong>
                <div class="text-muted">You are authorized to define counter prefixes, serial ranges (Start/End), and special series assignments.</div>
              </div>
            </div>
            <span class="badge bg-primary">Active Authoring Access</span>
          </div>
        `;
      } else {
        alertContainer.innerHTML = `
          <div class="alert alert-warning d-flex align-items-center justify-content-between gap-2 py-2 px-3 mb-3 small shadow-sm">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-shield-exclamation text-warning fs-5"></i>
              <div>
                <strong>Auditor Read-Only Inspection:</strong>
                <div class="text-muted">PSO series setup is in view-only mode for Internal Auditor.</div>
              </div>
            </div>
            <button class="btn btn-sm btn-outline-primary py-1 px-2.5 role-switch-btn" data-role-id="usr_01">
              <i class="bi bi-person-badge me-1"></i>Switch to Accountant (Operator)
            </button>
          </div>
        `;
      }
    }

    tableBody.innerHTML = state.psoSeries.map(pso => `
      <tr>
        <td class="fw-bold text-primary font-mono">${pso.id}</td>
        <td>${pso.name}</td>
        <td><span class="badge bg-light text-dark border font-mono">${pso.prefix}</span></td>
        <td class="font-mono">${pso.prefix} ${String(pso.startNo).padStart(2, '0')}</td>
        <td class="font-mono">${pso.prefix} ${String(pso.endNo).padStart(2, '0')}</td>
        <td>
          ${pso.specials.length ? pso.specials.map(s => `<span class="badge bg-info-subtle text-info border me-1">${s}</span>`).join("") : '<span class="text-muted small">None</span>'}
        </td>
        <td><i class="bi bi-person me-1"></i>${pso.operator}</td>
        <td><span class="badge ${pso.active ? 'bg-success' : 'bg-secondary'}">${pso.active ? 'Active' : 'Inactive'}</span></td>
        <td class="text-end">
          ${canConfigure ? `
            <button class="btn btn-sm btn-outline-secondary me-1" onclick="showToast('Configuration rule for ${pso.id} is verified and active.', 'info')"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" onclick="showToast('Default core series cannot be deleted during active business date.', 'warning')"><i class="bi bi-trash"></i></button>
          ` : `
            <span class="badge bg-light text-muted border" title="Read-only during audit review"><i class="bi bi-eye me-1"></i>Audit View</span>
          `}
        </td>
      </tr>
    `).join("");
  }

  // Pre-fill operator on opening PSO creation modal
  document.getElementById("btn-configure-pso")?.addEventListener("click", () => {
    const operatorInput = document.getElementById("new-pso-operator");
    if (operatorInput) {
      operatorInput.value = state.currentUser.name || "Ramesh Sharma";
    }
  });

  // Create PSO Form Submission
  const formCreatePso = document.getElementById("form-create-pso");
  if (formCreatePso) {
    formCreatePso.addEventListener("submit", (e) => {
      e.preventDefault();

      if (state.currentUser.roleCode === "AUDITOR") {
        showToast("Access Denied: Creating PSO series is disabled in Auditor Read-Only Mode.", "danger");
        return;
      }

      const name = document.getElementById("new-pso-name").value;
      const prefix = document.getElementById("new-pso-prefix").value.toUpperCase();
      const startNo = parseInt(document.getElementById("new-pso-start").value);
      const endNo = parseInt(document.getElementById("new-pso-end").value);
      const specialsRaw = document.getElementById("new-pso-specials").value;
      const operator = document.getElementById("new-pso-operator").value || state.currentUser.name;

      const specials = specialsRaw ? specialsRaw.split(",").map(s => s.trim()).filter(Boolean) : [];
      const newId = `PSO-${state.psoSeries.length + 1}`;

      state.psoSeries.push({
        id: newId,
        name,
        prefix,
        startNo,
        endNo,
        specials,
        operator,
        active: true,
        description: `${name} (${prefix} ${startNo} to ${endNo})`
      });

      state.auditTrail.unshift({
        timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
        user: `${state.currentUser.name} (${state.currentUser.role})`,
        action: "CREATE_PSO_SERIES",
        details: `Created new series ${newId}: ${prefix} ${startNo}-${endNo} assigned to ${operator}`
      });

      bootstrap.Modal.getInstance(document.getElementById("modal-add-pso")).hide();
      formCreatePso.reset();
      showToast(`PSO Series ${newId} (${name}) created successfully by ${state.currentUser.name}!`, "success");
      renderPsoMgmt();
      renderDashboard();
    });
  }

  // --- RENDER 3: TALLY EXCEL IMPORT SIMULATION ---
  const excelDropzone = document.getElementById("excel-dropzone");
  const excelFileInput = document.getElementById("excel-file-input");
  const selectedFileLabel = document.getElementById("selected-file-label");
  const selectedFileName = document.getElementById("selected-file-name");

  if (excelFileInput) {
    excelFileInput.addEventListener("change", (e) => {
      if (e.target.files && e.target.files[0]) {
        selectedFileName.textContent = e.target.files[0].name;
        selectedFileLabel.classList.remove("d-none");
        showToast(`Selected file: ${e.target.files[0].name}`, "info");
      }
    });
  }

  // Validate Button
  const btnValidateExcel = document.getElementById("btn-validate-excel");
  if (btnValidateExcel) {
    btnValidateExcel.addEventListener("click", () => {
      showToast("Validating Tally Excel schema against PSO range parameters...", "info");
      setTimeout(() => {
        showToast("Validation complete: 32 bills detected. 1 sequence gap detected (CB 02).", "warning");
      }, 500);
    });
  }

  // Execute Import Form
  const formExcelImport = document.getElementById("form-excel-import");
  if (formExcelImport) {
    formExcelImport.addEventListener("submit", (e) => {
      e.preventDefault();
      showToast("Ingesting Tally Excel records into reconciliation engine...", "primary");
      setTimeout(() => {
        showToast("Tally DayBook imported successfully (32 records, ₹7,00,000)!", "success");
        state.auditTrail.unshift({
          timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
          user: state.currentUser.name,
          action: "EXCEL_INGEST",
          details: "Tally DayBook re-scanned and synchronized with verification queue."
        });
        switchView("view-bill-verification");
      }, 600);
    });
  }

  // Download Sample Excel
  document.getElementById("btn-download-sample-excel")?.addEventListener("click", () => {
    let csvContent = "data:text/csv;charset=utf-8,Bill_No,PSO,Date,Time,Customer_Name,Amount,Payment_Type,Remark\n";
    state.bills.forEach(b => {
      csvContent += `${b.billNo},${b.psoId},${b.billDate},${b.time},"${b.customer}",${b.amount},${b.paymentType},"${b.remark}"\n`;
    });
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "Tally_DayBook_Template_14Aug2026.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast("Downloaded sample Tally DayBook CSV template", "success");
  });

  // --- RENDER 4: BILL SEQUENCE VERIFICATION ---
  function renderBillVerification() {
    const tableBody = document.getElementById("bill-verification-table-body");
    const searchFilter = document.getElementById("filter-bill-search").value.toLowerCase();
    const psoFilter = document.getElementById("filter-bill-pso").value;
    const statusFilter = document.getElementById("filter-bill-status").value;
    const paytypeFilter = document.getElementById("filter-bill-paytype").value;

    const filteredBills = state.bills.filter(b => {
      const matchSearch = b.billNo.toLowerCase().includes(searchFilter) || b.customer.toLowerCase().includes(searchFilter);
      const matchPso = (psoFilter === "ALL" || b.psoId === psoFilter);
      const matchStatus = (statusFilter === "ALL" || b.status === statusFilter);
      const matchPaytype = (paytypeFilter === "ALL" || b.paymentType === paytypeFilter);
      return matchSearch && matchPso && matchStatus && matchPaytype;
    });

    tableBody.innerHTML = filteredBills.map(b => {
      let badgeClass = "badge-matched";
      if (b.status === "Missing") badgeClass = "badge-missing";
      if (b.status === "Mismatch") badgeClass = "badge-mismatch";
      if (b.status === "Duplicate") badgeClass = "badge-duplicate";
      if (b.status === "Cancelled") badgeClass = "badge-cancelled";
      if (b.status === "Counter Sale Check") badgeClass = "badge-countersale";
      if (b.status === "Pending Review") badgeClass = "badge-pending";

      return `
        <tr>
          <td class="fw-bold font-mono text-dark">${b.billNo}</td>
          <td><span class="badge bg-light text-secondary border font-mono">${b.psoId}</span></td>
          <td>${b.expected ? '<i class="bi bi-check-circle-fill text-success"></i> Yes' : '<i class="bi bi-dash text-muted"></i> No'}</td>
          <td>${b.tallyFound ? '<i class="bi bi-check2 text-primary"></i> Found' : '<i class="bi bi-x text-danger"></i> Missing'}</td>
          <td class="font-mono text-muted small">${b.billDate} ${b.time}</td>
          <td class="fw-medium">${b.customer}</td>
          <td class="font-mono fw-semibold">${formatINR(b.amount)}</td>
          <td><span class="badge ${b.paymentType === 'Cash' ? 'bg-success' : b.paymentType === 'Paytm' ? 'bg-info' : b.paymentType === 'Check' ? 'bg-primary' : b.paymentType === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'}">${b.paymentType}</span></td>
          <td class="font-mono text-danger">${b.cd ? '-₹' + b.cd : '₹0'}</td>
          <td class="font-mono text-danger">${b.refund ? '-₹' + b.refund : '₹0'}</td>
          <td class="font-mono fw-bold ${b.status === 'Missing' ? 'text-muted' : 'text-success'}">${b.status === 'Missing' ? '₹0 (Missing)' : formatINR(b.netAmount)}</td>
          <td><span class="badge ${badgeClass}">${b.status}</span></td>
          <td class="text-muted small">${b.remark}</td>
          <td class="text-end">
            ${state.currentUser.isReadOnly ? `
              <span class="badge bg-warning-subtle text-dark border px-2 py-1"><i class="bi bi-eye me-1"></i>Audit View</span>
            ` : (b.status === 'Missing' ? `
              <button class="btn btn-sm btn-danger py-0 px-2 btn-investigate" data-billno="${b.billNo}">
                <i class="bi bi-search me-1"></i> Investigate
              </button>
            ` : `
              <button class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-bill" data-billno="${b.billNo}">
                <i class="bi bi-pencil"></i>
              </button>
            `)}
          </td>
        </tr>
      `;
    }).join("");

    // Hook investigate buttons
    if (!state.currentUser.isReadOnly) {
      document.querySelectorAll(".btn-investigate").forEach(btn => {
        btn.addEventListener("click", () => {
          const billNo = btn.dataset.billno;
          openInvestigateModal(billNo);
        });
      });
    }

    // Update table foot totals
    const metrics = calculateMetrics();
    document.getElementById("verif-tot-gross").textContent = formatINR(metrics.tallyTotal);
    document.getElementById("verif-tot-cd").textContent = "-₹" + metrics.totCd.toLocaleString('en-IN');
    document.getElementById("verif-tot-refund").textContent = "-₹" + metrics.totRefund.toLocaleString('en-IN');
    document.getElementById("verif-tot-net").textContent = formatINR(metrics.psoCollection);
  }

  // Filter Listeners
  ["filter-bill-search", "filter-bill-pso", "filter-bill-status", "filter-bill-paytype"].forEach(id => {
    document.getElementById(id)?.addEventListener("input", renderBillVerification);
  });

  // Auto verify button
  document.getElementById("btn-auto-verify-matched")?.addEventListener("click", () => {
    showToast("Bulk verified 31 physical bundles matching Tally DayBook slips.", "success");
  });

  // Export Verification CSV
  document.getElementById("btn-export-verification-csv")?.addEventListener("click", () => {
    let csv = "data:text/csv;charset=utf-8,Bill_No,PSO,Date,Customer,Amount,Payment_Type,CD,Refund,Net_Amount,Status,Remark\n";
    state.bills.forEach(b => {
      csv += `${b.billNo},${b.psoId},${b.billDate},"${b.customer}",${b.amount},${b.paymentType},${b.cd},${b.refund},${b.netAmount},${b.status},"${b.remark}"\n`;
    });
    const encoded = encodeURI(csv);
    const a = document.createElement("a");
    a.href = encoded;
    a.download = `Bill_Verification_Report_${state.businessDate}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    showToast("Exported Bill Verification CSV file", "success");
  });

  // Modal: Missing Bill Investigation
  function openInvestigateModal(billNo) {
    const bill = state.bills.find(b => b.billNo === billNo);
    if (!bill) return;

    document.getElementById("investigate-bill-no").textContent = bill.billNo;
    document.getElementById("investigate-bill-hidden-no").value = bill.billNo;
    document.getElementById("investigate-customer").textContent = bill.customer;
    document.getElementById("investigate-amount").textContent = formatINR(bill.amount);
    
    const modal = new bootstrap.Modal(document.getElementById("modal-investigate-bill"));
    modal.show();
  }

  const formInvestigate = document.getElementById("form-investigate-bill");
  if (formInvestigate) {
    formInvestigate.addEventListener("submit", (e) => {
      e.preventDefault();
      const billNo = document.getElementById("investigate-bill-hidden-no").value;
      const reason = document.getElementById("investigate-reason").value;
      const remark = document.getElementById("investigate-remark").value;

      const bill = state.bills.find(b => b.billNo === billNo);
      if (bill) {
        bill.status = "Matched";
        bill.remark = `[Resolved: ${reason}] ${remark}`;
        
        state.auditTrail.unshift({
          timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
          user: state.currentUser.name,
          action: "RESOLVE_MISSING_BILL",
          details: `Bill ${billNo} (₹${bill.amount}) marked as Matched via reason: ${reason}`
        });

        bootstrap.Modal.getInstance(document.getElementById("modal-investigate-bill")).hide();
        showToast(`Missing bill ${billNo} resolved! Reconciliation is now updated.`, "success");
        renderBillVerification();
        renderDashboard();
        renderMasterRecon();
      }
    });
  }

  // --- RENDER 5: PAYMENT CLASSIFICATION ---
  function renderPaymentClassification(filterType = "ALL") {
    const metrics = calculateMetrics();
    document.getElementById("class-cash-amount").textContent = formatINR(metrics.totCash);
    document.getElementById("class-paytm-amount").textContent = formatINR(metrics.totPaytm);
    document.getElementById("class-check-amount").textContent = formatINR(metrics.totCheck);
    document.getElementById("class-credit-amount").textContent = formatINR(metrics.totCredit);
    document.getElementById("class-cancelled-amount").textContent = formatINR(metrics.totCancelled);

    const tableBody = document.getElementById("payment-class-table-body");
    const filteredBills = state.bills.filter(b => (filterType === "ALL" || b.paymentType === filterType));

    tableBody.innerHTML = filteredBills.map(b => {
      let ruleDesc = "";
      let flagClass = "bg-success";
      let flagText = "Direct Receipt";

      if (b.paymentType === "Cash") {
        ruleDesc = "Direct cashier cash drawer receipt";
        flagClass = "bg-success";
        flagText = "Cash Received";
      } else if (b.paymentType === "Paytm") {
        ruleDesc = "Settled directly via Nodal QR / UPI";
        flagClass = "bg-info";
        flagText = "Digital Settled";
      } else if (b.paymentType === "Check") {
        ruleDesc = "Physical cheque lodged for bank clearing";
        flagClass = "bg-primary";
        flagText = "Deposit Pending";
      } else if (b.paymentType === "Credit") {
        ruleDesc = "Routed to salesman recovery register";
        flagClass = "bg-warning text-dark";
        flagText = "Salesman Pending";
      } else if (b.paymentType === "Cancelled") {
        ruleDesc = "Void transaction / zero revenue";
        flagClass = "bg-secondary";
        flagText = "No Collection";
      }

      return `
        <tr>
          <td class="fw-bold font-mono">${b.billNo}</td>
          <td>${b.customer}</td>
          <td><span class="badge ${b.paymentType === 'Cash' ? 'bg-success' : b.paymentType === 'Paytm' ? 'bg-info' : b.paymentType === 'Check' ? 'bg-primary' : b.paymentType === 'Credit' ? 'bg-warning text-dark' : 'bg-secondary'}">${b.paymentType}</span></td>
          <td class="font-mono">${formatINR(b.amount)}</td>
          <td class="font-mono text-danger">${(b.cd || b.refund) ? '-₹' + ((b.cd || 0) + (b.refund || 0)) : '₹0'}</td>
          <td class="font-mono fw-bold text-dark">${formatINR(b.netAmount)}</td>
          <td class="text-muted small">${ruleDesc}</td>
          <td><span class="badge ${flagClass}">${flagText}</span></td>
        </tr>
      `;
    }).join("");
  }

  // Payment tab filter clicks
  document.querySelectorAll(".paytype-tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".paytype-tab-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      renderPaymentClassification(btn.dataset.paytype);
    });
  });

  // --- RENDER 6: CORRECTIONS / GOODS RETURN ---
  function renderCorrections() {
    const totCd = state.corrections.reduce((acc, c) => acc + (c.cdAmount || 0), 0);
    const totReturn = state.corrections.reduce((acc, c) => acc + (c.goodsReturnAmount || 0), 0);
    const totNet = state.corrections.reduce((acc, c) => acc + (c.netAdjustment || 0), 0);

    document.getElementById("corr-tot-cd").textContent = formatINR(totCd);
    document.getElementById("corr-tot-return").textContent = formatINR(totReturn);
    document.getElementById("corr-tot-net").textContent = (totNet < 0 ? "-₹" : "₹") + Math.abs(totNet).toLocaleString('en-IN');
    document.getElementById("corr-table-count").textContent = `${state.corrections.length} Entries`;

    const tableBody = document.getElementById("corrections-table-body");
    tableBody.innerHTML = state.corrections.map(c => `
      <tr>
        <td class="fw-bold font-mono text-primary">${c.id}</td>
        <td class="fw-bold font-mono">${c.billNo}</td>
        <td class="font-mono">${formatINR(c.originalAmount)}</td>
        <td><span class="badge bg-secondary">${c.correctionType}</span></td>
        <td class="font-mono text-danger">${c.cdAmount ? '₹' + c.cdAmount : '₹0'}</td>
        <td class="font-mono text-danger">${c.goodsReturnAmount ? '₹' + c.goodsReturnAmount : '₹0'}</td>
        <td class="font-mono text-danger">${c.refundAmount ? '₹' + c.refundAmount : '₹0'}</td>
        <td class="font-mono fw-bold text-danger">${c.netAdjustment < 0 ? '-₹' + Math.abs(c.netAdjustment) : '₹' + c.netAdjustment}</td>
        <td class="text-muted small">${c.reason}</td>
        <td><i class="bi bi-shield-check text-success me-1"></i>${c.approvedBy}</td>
        <td class="font-mono text-muted small">${c.timestamp}</td>
      </tr>
    `).join("");
  }

  // Create Correction Form
  const formNewCorrection = document.getElementById("form-new-correction");
  if (formNewCorrection) {
    formNewCorrection.addEventListener("submit", (e) => {
      e.preventDefault();
      const billNo = document.getElementById("corr-input-billno").value;
      const type = document.getElementById("corr-input-type").value;
      const cd = parseInt(document.getElementById("corr-input-cd").value) || 0;
      const ret = parseInt(document.getElementById("corr-input-return").value) || 0;
      const ref = parseInt(document.getElementById("corr-input-refund").value) || 0;
      const reason = document.getElementById("corr-input-reason").value;

      const targetBill = state.bills.find(b => b.billNo === billNo);
      const origAmt = targetBill ? targetBill.amount : 20000;
      const netAdj = -(cd + ret + ref);

      const newCorr = {
        id: `CORR-0${state.corrections.length + 1}`,
        billNo,
        originalAmount: origAmt,
        correctionType: type,
        cdAmount: cd,
        goodsReturnAmount: ret,
        refundAmount: ref,
        netAdjustment: netAdj,
        reason,
        approvedBy: state.currentUser.name,
        timestamp: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };

      state.corrections.push(newCorr);

      // Update bill record
      if (targetBill) {
        targetBill.cd = (targetBill.cd || 0) + cd;
        targetBill.refund = (targetBill.refund || 0) + (ret + ref);
        targetBill.netAmount = targetBill.amount - (targetBill.cd + targetBill.refund);
        targetBill.remark += ` | Adj: ${type} ${formatINR(netAdj)}`;
      }

      state.auditTrail.unshift({
        timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
        user: state.currentUser.name,
        action: "RECORD_CORRECTION",
        details: `Applied ${type} of ${formatINR(netAdj)} on Bill ${billNo}. Reason: ${reason}`
      });

      bootstrap.Modal.getInstance(document.getElementById("modal-add-correction")).hide();
      formNewCorrection.reset();
      showToast(`Correction ${newCorr.id} recorded and applied to ledger!`, "success");
      renderCorrections();
      renderBillVerification();
      renderDashboard();
    });
  }

  // --- RENDER 7: CREDIT COLLECTION ---
  function renderCreditCollections() {
    const totSales = state.creditCollections.reduce((acc, c) => acc + c.billAmount, 0);
    const totRecovered = state.creditCollections.reduce((acc, c) => acc + c.paidAmount, 0);
    const totOut = state.creditCollections.reduce((acc, c) => acc + c.outstanding, 0);

    document.getElementById("credit-tot-sales").textContent = formatINR(totSales);
    document.getElementById("credit-tot-recovered").textContent = formatINR(totRecovered);
    document.getElementById("credit-tot-outstanding").textContent = formatINR(totOut);

    const tableBody = document.getElementById("credit-collection-table-body");
    tableBody.innerHTML = state.creditCollections.map(c => `
      <tr>
        <td class="fw-bold font-mono text-primary">${c.billNo}</td>
        <td class="fw-semibold">${c.customer}</td>
        <td><i class="bi bi-person-fill text-warning me-1"></i>${c.salesman}</td>
        <td class="font-mono text-muted">${c.billDate}</td>
        <td class="font-mono">${formatINR(c.billAmount)}</td>
        <td class="font-mono text-success">${formatINR(c.paidAmount)}</td>
        <td class="font-mono fw-bold ${c.outstanding > 0 ? 'text-danger' : 'text-success'}">${formatINR(c.outstanding)}</td>
        <td>
          <span class="badge ${c.collectionStatus === 'Collected' ? 'bg-success' : c.collectionStatus === 'Partially Collected' ? 'bg-warning text-dark' : 'bg-danger'}">
            ${c.collectionStatus}
          </span>
        </td>
        <td class="font-mono small ${new Date(c.dueDate) <= new Date() ? 'text-danger fw-bold' : 'text-muted'}">${c.dueDate}</td>
        <td class="text-muted small">${c.remark}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-success py-0 px-2 btn-pay-credit" data-billno="${c.billNo}">
            <i class="bi bi-cash me-1"></i> Collect
          </button>
        </td>
      </tr>
    `).join("");

    // Hook collection modal buttons
    document.querySelectorAll(".btn-pay-credit").forEach(btn => {
      btn.addEventListener("click", () => {
        const billNo = btn.dataset.billno;
        openCreditModal(billNo);
      });
    });
  }

  function openCreditModal(billNo) {
    const item = state.creditCollections.find(c => c.billNo === billNo);
    if (!item) return;

    document.getElementById("credit-modal-billno").textContent = item.billNo;
    document.getElementById("credit-modal-hidden-no").value = item.billNo;
    document.getElementById("credit-modal-customer").textContent = item.customer;
    document.getElementById("credit-modal-salesman").textContent = item.salesman;
    document.getElementById("credit-modal-total").textContent = formatINR(item.billAmount);
    document.getElementById("credit-modal-out").textContent = formatINR(item.outstanding);
    document.getElementById("credit-modal-paid-input").value = item.outstanding;

    const modal = new bootstrap.Modal(document.getElementById("modal-update-credit"));
    modal.show();
  }

  const formUpdateCredit = document.getElementById("form-update-credit");
  if (formUpdateCredit) {
    formUpdateCredit.addEventListener("submit", (e) => {
      e.preventDefault();
      const billNo = document.getElementById("credit-modal-hidden-no").value;
      const amtPaid = parseInt(document.getElementById("credit-modal-paid-input").value) || 0;
      const mode = document.getElementById("credit-modal-mode").value;
      const remark = document.getElementById("credit-modal-remark").value;

      const item = state.creditCollections.find(c => c.billNo === billNo);
      if (item) {
        item.paidAmount = Math.min(item.billAmount, item.paidAmount + amtPaid);
        item.outstanding = item.billAmount - item.paidAmount;
        item.collectionStatus = item.outstanding === 0 ? "Collected" : "Partially Collected";
        item.remark = `[${mode}] Collected ₹${amtPaid} by ${item.salesman}. ${remark}`;

        state.auditTrail.unshift({
          timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
          user: state.currentUser.name,
          action: "CREDIT_COLLECT",
          details: `Collected ₹${amtPaid} for Credit Bill ${billNo} via ${mode}`
        });

        bootstrap.Modal.getInstance(document.getElementById("modal-update-credit")).hide();
        showToast(`Payment of ₹${amtPaid} recorded for Bill ${billNo}!`, "success");
        renderCreditCollections();
        renderDashboard();
      }
    });
  }

  // Export Credit Sheet CSV
  document.getElementById("btn-export-credit-sheet")?.addEventListener("click", () => {
    let csv = "data:text/csv;charset=utf-8,Bill_No,Customer,Assigned_Salesman,Bill_Date,Bill_Amount,Paid_Amount,Outstanding,Status,Due_Date,Remark\n";
    state.creditCollections.forEach(c => {
      csv += `${c.billNo},"${c.customer}","${c.salesman}",${c.billDate},${c.billAmount},${c.paidAmount},${c.outstanding},${c.collectionStatus},${c.dueDate},"${c.remark}"\n`;
    });
    const encoded = encodeURI(csv);
    const a = document.createElement("a");
    a.href = encoded;
    a.download = `Salesman_Credit_Collection_Sheet_${state.businessDate}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    showToast("Exported Salesman Credit Collection Sheet", "success");
  });

  // Print Credit Sheet
  document.getElementById("btn-print-credit-sheet")?.addEventListener("click", () => {
    window.print();
  });

  // --- RENDER 8: PSO SUMMARY ---
  function renderPsoSummary() {
    const metrics = calculateMetrics();

    // Helper for computing breakdown per PSO
    function getPsoBreakdown(psoId) {
      const bills = state.bills.filter(b => b.psoId === psoId);
      const billCount = bills.length;
      let gross = 0, cash = 0, paytm = 0, check = 0, credit = 0, cancelled = 0, cd = 0, refund = 0, net = 0;

      bills.forEach(b => {
        gross += b.amount;
        cd += (b.cd || 0);
        refund += (b.refund || 0);
        const eff = (b.status === "Missing") ? 0 : b.netAmount;
        net += eff;

        if (b.paymentType === "Cash") cash += eff;
        if (b.paymentType === "Paytm") paytm += eff;
        if (b.paymentType === "Check") check += eff;
        if (b.paymentType === "Credit") credit += eff;
        if (b.paymentType === "Cancelled") cancelled += b.amount;
      });

      return { billCount, gross, cash, paytm, check, credit, cancelled, cd, refund, net };
    }

    const b1 = getPsoBreakdown("PSO-1");
    const b2 = getPsoBreakdown("PSO-2");
    const b3 = getPsoBreakdown("PSO-3");

    const matrixBody = document.getElementById("pso-summary-matrix-body");
    matrixBody.innerHTML = `
      <tr>
        <td class="text-start">
          <div class="fw-bold text-primary">PSO 1</div>
          <small class="text-muted">CB 01–CB 10 (Main Wholesale)</small>
        </td>
        <td>${b1.billCount}</td>
        <td class="font-mono">${formatINR(b1.gross)}</td>
        <td class="font-mono">${formatINR(b1.cash)}</td>
        <td class="font-mono">${formatINR(b1.paytm)}</td>
        <td class="font-mono">${formatINR(b1.check)}</td>
        <td class="font-mono">${formatINR(b1.credit)}</td>
        <td class="font-mono">${formatINR(b1.cancelled)}</td>
        <td class="font-mono text-danger">${b1.cd ? '-₹' + b1.cd : '₹0'}</td>
        <td class="font-mono text-danger">${b1.refund ? '-₹' + b1.refund : '₹0'}</td>
        <td class="text-end font-mono fw-bold text-success">${formatINR(b1.net)}</td>
      </tr>
      <tr>
        <td class="text-start">
          <div class="fw-bold text-primary">PSO 2</div>
          <small class="text-muted">CB 11–CB 20 + ITC (Key Accounts)</small>
        </td>
        <td>${b2.billCount}</td>
        <td class="font-mono">${formatINR(b2.gross)}</td>
        <td class="font-mono">${formatINR(b2.cash)}</td>
        <td class="font-mono">${formatINR(b2.paytm)}</td>
        <td class="font-mono">${formatINR(b2.check)}</td>
        <td class="font-mono">${formatINR(b2.credit)}</td>
        <td class="font-mono">${formatINR(b2.cancelled)}</td>
        <td class="font-mono text-danger">${b2.cd ? '-₹' + b2.cd : '₹0'}</td>
        <td class="font-mono text-danger">${b2.refund ? '-₹' + b2.refund : '₹0'}</td>
        <td class="text-end font-mono fw-bold text-success">${formatINR(b2.net)}</td>
      </tr>
      <tr>
        <td class="text-start">
          <div class="fw-bold text-primary">PSO 3</div>
          <small class="text-muted">RB 01–RB 10 (Retail Counter)</small>
        </td>
        <td>${b3.billCount}</td>
        <td class="font-mono">${formatINR(b3.gross)}</td>
        <td class="font-mono">${b3.cash ? formatINR(b3.cash) : '₹0'}</td>
        <td class="font-mono">${b3.paytm ? formatINR(b3.paytm) : '₹0'}</td>
        <td class="font-mono">${b3.check ? formatINR(b3.check) : '₹0'}</td>
        <td class="font-mono">${b3.credit ? formatINR(b3.credit) : '₹0'}</td>
        <td class="font-mono">${b3.cancelled ? formatINR(b3.cancelled) : '₹0'}</td>
        <td class="font-mono text-danger">${b3.cd ? '-₹' + b3.cd : '₹0'}</td>
        <td class="font-mono text-danger">${b3.refund ? '-₹' + b3.refund : '₹0'}</td>
        <td class="text-end font-mono fw-bold text-success">${formatINR(b3.net)}</td>
      </tr>
    `;

    // Totals row
    document.getElementById("matrix-tot-bills").textContent = metrics.totalBillsCount;
    document.getElementById("matrix-tot-gross").textContent = formatINR(metrics.tallyTotal);
    document.getElementById("matrix-tot-cash").textContent = formatINR(metrics.totCash);
    document.getElementById("matrix-tot-paytm").textContent = formatINR(metrics.totPaytm);
    document.getElementById("matrix-tot-check").textContent = formatINR(metrics.totCheck);
    document.getElementById("matrix-tot-credit").textContent = formatINR(metrics.totCredit);
    document.getElementById("matrix-tot-cancelled").textContent = formatINR(metrics.totCancelled);
    document.getElementById("matrix-tot-cd").textContent = "-₹" + metrics.totCd.toLocaleString('en-IN');
    document.getElementById("matrix-tot-refund").textContent = "-₹" + metrics.totRefund.toLocaleString('en-IN');
    document.getElementById("matrix-tot-net").textContent = formatINR(metrics.psoCollection);

    // Prompt Highlight Cards
    document.getElementById("card-pso1-total").textContent = `Total = ${formatINR(b1.net)}`;
    document.getElementById("card-pso2-total").textContent = `Total = ${formatINR(b2.net)}`;
    document.getElementById("card-pso3-total").textContent = `Total = ${formatINR(b3.net)}`;
  }

  // Print PSO Summary
  document.getElementById("btn-print-pso-summary")?.addEventListener("click", () => {
    window.print();
  });

  // --- RENDER 9: MASTER RECONCILIATION ---
  function renderMasterRecon() {
    const metrics = calculateMetrics();

    document.getElementById("recon-tally-total").textContent = formatINR(metrics.tallyTotal);
    document.getElementById("recon-pso-total").textContent = formatINR(metrics.psoCollection);
    document.getElementById("recon-sub-pso1").textContent = formatINR(metrics.pso1Total);
    document.getElementById("recon-sub-pso2").textContent = formatINR(metrics.pso2Total);
    document.getElementById("recon-sub-pso3").textContent = formatINR(metrics.pso3Total);
    document.getElementById("recon-diff-display").textContent = formatINR(metrics.difference);

    const banner = document.getElementById("recon-master-banner");
    const bannerIcon = document.getElementById("recon-banner-icon");
    const bannerTitle = document.getElementById("recon-banner-title");
    const bannerDesc = document.getElementById("recon-banner-desc");
    const bannerActions = document.getElementById("recon-banner-actions");

    if (state.isSealed) {
      banner.className = "recon-banner success";
      bannerIcon.className = "rounded-circle p-3 bg-white shadow-sm text-success";
      bannerIcon.innerHTML = `<i class="bi bi-lock-fill fs-1"></i>`;
      bannerTitle.textContent = "PSO SEALED & ARCHIVED";
      bannerDesc.textContent = `Tally Total and PSO Collections reconciled with ₹0 difference. Records are locked for 14-Aug-2026.`;
      bannerActions.innerHTML = `<button class="btn btn-success" id="btn-view-cert-recon"><i class="bi bi-file-earmark-check me-1"></i> View Seal Certificate</button>`;
      document.getElementById("btn-view-cert-recon")?.addEventListener("click", () => {
        new bootstrap.Modal(document.getElementById("modal-seal-cert")).show();
      });
    } else if (metrics.isReconciled) {
      banner.className = "recon-banner success";
      bannerIcon.className = "rounded-circle p-3 bg-white shadow-sm text-success";
      bannerIcon.innerHTML = `<i class="bi bi-check2-circle fs-1"></i>`;
      bannerTitle.textContent = "RECONCILIATION SUCCESSFUL";
      bannerDesc.textContent = `Tally Total (${formatINR(metrics.tallyTotal)}) matches Total PSO Collection (${formatINR(metrics.psoCollection)}) with ZERO difference. Approval and Sealing are enabled!`;
      bannerActions.innerHTML = `<button class="btn btn-success nav-direct-btn" data-target="view-approval-sealing"><i class="bi bi-arrow-right-circle me-1"></i> Proceed to Approval & Sealing</button>`;
    } else {
      banner.className = "recon-banner failed";
      bannerIcon.className = "rounded-circle p-3 bg-white shadow-sm text-danger";
      bannerIcon.innerHTML = `<i class="bi bi-shield-x fs-1"></i>`;
      bannerTitle.textContent = "RECONCILIATION FAILED";
      bannerDesc.innerHTML = `Tally Total (${formatINR(metrics.tallyTotal)}) does not match Total PSO Collection (${formatINR(metrics.psoCollection)}). Difference: <span class="fw-bold text-danger">${formatINR(metrics.difference)}</span>. Approval and sealing are strictly blocked.`;
      bannerActions.innerHTML = `<button class="btn btn-danger" id="btn-resolve-recon-diff-2"><i class="bi bi-tools me-1"></i> Resolve Discrepancy</button>`;
      
      document.getElementById("btn-resolve-recon-diff-2")?.addEventListener("click", () => {
        // Find first missing bill and trigger modal
        const missing = state.bills.find(b => b.status === "Missing");
        if (missing) {
          openInvestigateModal(missing.billNo);
        } else {
          showToast("No missing bills found. Check corrections or adjustments.", "info");
        }
      });
    }
  }

  // Hook Resolve Recon Diff Button
  document.getElementById("btn-resolve-recon-diff")?.addEventListener("click", () => {
    const missing = state.bills.find(b => b.status === "Missing");
    if (missing) {
      openInvestigateModal(missing.billNo);
    } else {
      showToast("No missing bills found.", "info");
    }
  });

  document.getElementById("btn-recompute-recon")?.addEventListener("click", () => {
    showToast("Re-evaluating mathematical reconciliation matrix across all series...", "info");
    setTimeout(() => {
      renderMasterRecon();
      showToast("Calculations refreshed.", "success");
    }, 400);
  });

  // --- RENDER 10: APPROVAL & SEALING ---
  function renderApprovalSealing() {
    const metrics = calculateMetrics();
    const btnSeal = document.getElementById("btn-trigger-seal");
    const sealBlockNotice = document.getElementById("seal-block-notice");
    const btnUnseal = document.getElementById("btn-unseal-demo");

    const gateMissingIcon = document.getElementById("gate-icon-missing");
    const gateMissingDesc = document.getElementById("gate-desc-missing");
    const gateReconIcon = document.getElementById("gate-icon-recon");
    const gateReconDesc = document.getElementById("gate-desc-recon");

    if (metrics.missingCount === 0) {
      gateMissingIcon.className = "bi bi-check-circle-fill text-success fs-5";
      gateMissingDesc.textContent = "All bills verified & resolved";
    } else {
      gateMissingIcon.className = "bi bi-x-circle-fill text-danger fs-5";
      gateMissingDesc.textContent = `${metrics.missingCount} missing bill unresolved`;
    }

    if (metrics.difference === 0) {
      gateReconIcon.className = "bi bi-check-circle-fill text-success fs-5";
      gateReconDesc.textContent = "Difference is ₹0 (Balanced)";
    } else {
      gateReconIcon.className = "bi bi-x-circle-fill text-danger fs-5";
      gateReconDesc.textContent = `Variance ${formatINR(metrics.difference)} detected`;
    }

    const canSeal = state.currentUser.canApproveSealing || state.currentUser.roleCode === "APPROVER" || state.currentUser.roleCode === "ADMIN";

    if (state.isSealed) {
      btnSeal.disabled = true;
      btnSeal.innerHTML = `<i class="bi bi-lock-fill me-1"></i> PSO IS SEALED & ARCHIVED`;
      sealBlockNotice.className = "text-success small mt-2";
      sealBlockNotice.innerHTML = `<i class="bi bi-shield-check me-1"></i> Records sealed on ${state.sealedInfo?.time || '14-Aug-2026 19:15'} by ${state.sealedInfo?.user || 'Pooja Verma'}.`;
      if (btnUnseal) btnUnseal.style.display = canSeal ? "inline-block" : "none";
    } else if (!canSeal) {
      btnSeal.disabled = true;
      btnSeal.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Sealing Locked (Approver Role Required)`;
      sealBlockNotice.className = "text-warning small mt-2";
      sealBlockNotice.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Current User: <strong>${state.currentUser.name} (${state.currentUser.role})</strong>. Final Sealing requires <strong>Accounts Officer (Pooja Verma)</strong> authorization. <button class="btn btn-sm btn-outline-success py-0 px-2 ms-2 role-switch-btn" data-role-id="usr_02">Switch to Pooja Verma</button>`;
      if (btnUnseal) btnUnseal.style.display = "none";
    } else if (metrics.isReconciled) {
      btnSeal.disabled = false;
      btnSeal.innerHTML = `<i class="bi bi-lock-fill me-1"></i> Approve & Seal Daily Records`;
      sealBlockNotice.className = "text-success small mt-2";
      sealBlockNotice.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Authorized Signatory: <strong>${state.currentUser.name}</strong>. All 4 prerequisite checks passed. Click to seal.`;
      if (btnUnseal) btnUnseal.style.display = "none";
    } else {
      btnSeal.disabled = true;
      btnSeal.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Approval Blocked (Discrepancy)`;
      sealBlockNotice.className = "text-danger small mt-2";
      sealBlockNotice.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> Cannot seal while Reconciliation is FAILED or Difference > ₹0.`;
      if (btnUnseal) btnUnseal.style.display = "none";
    }
  }

  // Trigger Sealing
  document.getElementById("btn-trigger-seal")?.addEventListener("click", () => {
    state.isSealed = true;
    state.sealedInfo = {
      user: state.currentUser.name,
      role: state.currentUser.role,
      time: new Date().toISOString().replace('T', ' ').substring(0, 19),
      hash: "SHA256: " + Math.random().toString(36).substring(2, 12).toUpperCase()
    };

    document.body.classList.add("is-sealed");
    document.getElementById("sealed-banner").style.display = "flex";
    document.getElementById("sealed-banner-user").textContent = `${state.sealedInfo.user} (${state.sealedInfo.role})`;
    document.getElementById("sealed-banner-hash").textContent = state.sealedInfo.hash;

    state.auditTrail.unshift({
      timestamp: state.sealedInfo.time,
      user: state.sealedInfo.user,
      action: "OFFICIAL_SEAL",
      details: `PSO sealed and locked into Read-Only with Hash ${state.sealedInfo.hash}`
    });

    showToast("PSO Approved and Digitally Sealed! Records are now read-only.", "success");
    renderApprovalSealing();
    renderDashboard();
    renderMasterRecon();

    const certModal = new bootstrap.Modal(document.getElementById("modal-seal-cert"));
    certModal.show();
  });

  // Unseal Demo Button
  document.getElementById("btn-unseal-demo")?.addEventListener("click", () => {
    state.isSealed = false;
    state.sealedInfo = null;
    document.body.classList.remove("is-sealed");
    document.getElementById("sealed-banner").style.display = "none";
    showToast("PSO Unsealed (Demo Reset)", "info");
    renderApprovalSealing();
    renderDashboard();
  });

  document.getElementById("btn-view-seal-cert")?.addEventListener("click", () => {
    new bootstrap.Modal(document.getElementById("modal-seal-cert")).show();
  });

  // --- RENDER 11: 7-DAY RETENTION ---
  function renderRetention() {
    const tableBody = document.getElementById("retention-table-body");
    tableBody.innerHTML = state.retentionPsoList.map(item => `
      <tr>
        <td class="fw-bold text-primary font-mono">${item.psoCode}</td>
        <td>${item.businessDate}</td>
        <td class="text-muted">${item.createdDate}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <span class="badge ${item.daysRemaining <= 2 ? 'bg-danger' : 'bg-primary'} font-mono">${item.daysRemaining} Days</span>
            <div class="progress flex-grow-1" style="height: 8px;">
              <div class="progress-bar ${item.daysRemaining <= 2 ? 'bg-danger' : 'bg-success'}" style="width: ${(item.daysRemaining / 7) * 100}%"></div>
            </div>
          </div>
        </td>
        <td class="font-mono fw-bold">${formatINR(item.totalAmount)}</td>
        <td><span class="badge ${item.badgeClass}">${item.status}</span></td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="alert('Viewing archived ledger for ${item.psoCode} (${item.businessDate})')">
            <i class="bi bi-box-arrow-up-right me-1"></i> Open Ledger
          </button>
        </td>
      </tr>
    `).join("");
  }

  // --- RENDER 12: REPORTS VIEW ---
  function renderReports(reportType = "daily_pso") {
    const titleEl = document.getElementById("report-preview-title");
    const contentEl = document.getElementById("report-preview-content");
    const metrics = calculateMetrics();

    if (reportType === "daily_pso") {
      titleEl.textContent = `Daily PSO Reconciliation Report (${state.businessDate})`;
      contentEl.innerHTML = `
        <div style="font-family: var(--font-family-mono);">
          <div class="text-center pb-2 border-bottom mb-3">
            <h5 class="fw-bold mb-0">HISABKITAP STATUTORY ERP - DAILY PSO SUMMARY</h5>
            <small>Business Date: ${state.businessDate} | Cutoff: ${state.cutoffTime} | Status: ${state.isSealed ? 'SEALED' : 'UNSEALED'}</small>
          </div>
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>PSO Series</th>
                <th>Total Bills</th>
                <th>Gross (₹)</th>
                <th>Cash (₹)</th>
                <th>Paytm (₹)</th>
                <th>Cheque (₹)</th>
                <th>Credit (₹)</th>
                <th>Net (₹)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>PSO 1 (CB 01-10)</td>
                <td>10</td>
                <td>2,25,000</td>
                <td>70,000</td>
                <td>54,500</td>
                <td>34,000</td>
                <td>51,000</td>
                <td class="fw-bold">${formatINR(metrics.pso1Total)}</td>
              </tr>
              <tr>
                <td>PSO 2 (CB 11-20+ITC)</td>
                <td>12</td>
                <td>2,80,000</td>
                <td>73,000</td>
                <td>45,000</td>
                <td>1,05,000</td>
                <td>45,000</td>
                <td class="fw-bold">${formatINR(metrics.pso2Total)}</td>
              </tr>
              <tr>
                <td>PSO 3 (RB 01-10)</td>
                <td>10</td>
                <td>1,77,500</td>
                <td>52,500</td>
                <td>53,000</td>
                <td>47,000</td>
                <td>25,000</td>
                <td class="fw-bold">${formatINR(metrics.pso3Total)}</td>
              </tr>
            </tbody>
            <tfoot class="table-dark">
              <tr>
                <td>GRAND TOTAL</td>
                <td>${metrics.totalBillsCount}</td>
                <td>${formatINR(metrics.tallyTotal)}</td>
                <td>${formatINR(metrics.totCash)}</td>
                <td>${formatINR(metrics.totPaytm)}</td>
                <td>${formatINR(metrics.totCheck)}</td>
                <td>${formatINR(metrics.totCredit)}</td>
                <td>${formatINR(metrics.psoCollection)}</td>
              </tr>
            </tfoot>
          </table>
          <div class="mt-3 small text-muted">
            Printed by: ${state.currentUser.name} | Verified Signatory: Pooja Verma
          </div>
        </div>
      `;
    } else if (reportType === "recon_sheet") {
      titleEl.textContent = `Tally vs PSO Master Reconciliation Sheet (${state.businessDate})`;
      contentEl.innerHTML = `
        <div style="font-family: var(--font-family-mono);">
          <div class="text-center pb-2 border-bottom mb-3">
            <h5 class="fw-bold mb-0">TALLY ERP vs PHYSICAL PSO RECONCILIATION SHEET</h5>
            <small>Business Date: ${state.businessDate}</small>
          </div>
          <div class="p-3 border bg-white mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span>(A) Total Tally DayBook Gross Sales:</span>
              <strong>${formatINR(metrics.tallyTotal)}</strong>
            </div>
            <div class="d-flex justify-content-between mb-2 text-danger">
              <span>(B) Less Cash Discount (CD) & Deductions:</span>
              <strong>-₹${metrics.totCd}</strong>
            </div>
            <div class="d-flex justify-content-between mb-2 text-danger">
              <span>(C) Less Goods Return & Refunds:</span>
              <strong>-₹${metrics.totRefund}</strong>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-2">
              <span>(D) Total Physical PSO Collections (1 + 2 + 3):</span>
              <strong class="text-success">${formatINR(metrics.psoCollection)}</strong>
            </div>
            <div class="d-flex justify-content-between fs-6 fw-bold ${metrics.difference === 0 ? 'text-success' : 'text-danger'}">
              <span>FINAL RECONCILIATION VARIANCE (A - D):</span>
              <span>${formatINR(metrics.difference)}</span>
            </div>
          </div>
          <div class="alert ${metrics.isReconciled ? 'alert-success' : 'alert-danger'}">
            Status: <strong>${metrics.isReconciled ? 'RECONCILIATION SUCCESSFUL' : 'RECONCILIATION FAILED (APPROVAL BLOCKED)'}</strong>
          </div>
        </div>
      `;
    } else if (reportType === "credit_sheet") {
      titleEl.textContent = `Salesman Credit Collection Register (${state.businessDate})`;
      contentEl.innerHTML = `
        <table class="table table-sm table-bordered font-mono">
          <thead class="table-light">
            <tr>
              <th>Bill No.</th>
              <th>Customer</th>
              <th>Salesman</th>
              <th>Bill Amount</th>
              <th>Paid</th>
              <th>Outstanding</th>
              <th>Status</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
            ${state.creditCollections.map(c => `
              <tr>
                <td>${c.billNo}</td>
                <td>${c.customer}</td>
                <td>${c.salesman}</td>
                <td>${formatINR(c.billAmount)}</td>
                <td>${formatINR(c.paidAmount)}</td>
                <td class="fw-bold ${c.outstanding > 0 ? 'text-danger' : 'text-success'}">${formatINR(c.outstanding)}</td>
                <td>${c.collectionStatus}</td>
                <td>${c.dueDate}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      `;
    } else if (reportType === "missing_bills") {
      titleEl.textContent = `Missing Bills Investigation Log (${state.businessDate})`;
      const missingBills = state.bills.filter(b => b.status === "Missing" || b.remark.includes("Resolved"));
      contentEl.innerHTML = `
        <table class="table table-sm table-bordered font-mono">
          <thead class="table-light">
            <tr>
              <th>Bill No.</th>
              <th>PSO</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Investigation Details / Remark</th>
            </tr>
          </thead>
          <tbody>
            ${missingBills.length ? missingBills.map(b => `
              <tr>
                <td class="fw-bold">${b.billNo}</td>
                <td>${b.psoId}</td>
                <td>${b.customer}</td>
                <td class="text-danger">${formatINR(b.amount)}</td>
                <td><span class="badge ${b.status === 'Missing' ? 'bg-danger' : 'bg-success'}">${b.status}</span></td>
                <td>${b.remark}</td>
              </tr>
            `).join("") : '<tr><td colspan="6" class="text-center text-muted">No missing bills found. All serials accounted for.</td></tr>'}
          </tbody>
        </table>
      `;
    } else if (reportType === "corrections_log") {
      titleEl.textContent = `Cash Discounts & Goods Returns Register (${state.businessDate})`;
      contentEl.innerHTML = `
        <table class="table table-sm table-bordered font-mono">
          <thead class="table-light">
            <tr>
              <th>Corr ID</th>
              <th>Bill No.</th>
              <th>Correction Type</th>
              <th>Adjustment</th>
              <th>Reason</th>
              <th>Approved By</th>
            </tr>
          </thead>
          <tbody>
            ${state.corrections.map(c => `
              <tr>
                <td>${c.id}</td>
                <td>${c.billNo}</td>
                <td>${c.correctionType}</td>
                <td class="text-danger">${formatINR(c.netAdjustment)}</td>
                <td>${c.reason}</td>
                <td>${c.approvedBy}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      `;
    } else if (reportType === "audit_history") {
      titleEl.textContent = `System Security & Audit Trail Log`;
      contentEl.innerHTML = `
        <table class="table table-sm table-bordered font-mono">
          <thead class="table-light">
            <tr>
              <th>Timestamp</th>
              <th>User</th>
              <th>Action Code</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            ${state.auditTrail.map(a => `
              <tr>
                <td>${a.timestamp}</td>
                <td>${a.user}</td>
                <td><span class="badge bg-secondary">${a.action}</span></td>
                <td>${a.details}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      `;
    }
  }

  // Report Selector clicks
  document.querySelectorAll(".report-select-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".report-select-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      renderReports(btn.dataset.report);
    });
  });

  // Export Report Excel
  document.getElementById("btn-export-report-excel")?.addEventListener("click", () => {
    showToast("Generating comprehensive Excel workbook for statutory export...", "info");
    setTimeout(() => {
      showToast("Workbook exported: Master_Recon_Report_14Aug2026.xlsx", "success");
    }, 600);
  });

  // --- RENDER 13: SETTINGS & CUTOFF ---
  function renderSettings() {
    const isSuperAdmin = state.currentUser.roleCode === "ADMIN";
    const cutoffInput = document.getElementById("setting-cutoff-time");
    const cutoffToggle = document.getElementById("setting-cutoff-toggle");
    const btnSaveCutoff = document.getElementById("btn-save-cutoff-settings");
    const alertContainer = document.getElementById("cutoff-role-alert-container");

    if (cutoffInput) {
      cutoffInput.value = state.cutoffTime || "19:00";
      cutoffInput.disabled = !isSuperAdmin;
    }

    if (cutoffToggle) {
      cutoffToggle.checked = state.cutoffRuleActive !== false;
      cutoffToggle.disabled = !isSuperAdmin;
    }

    if (btnSaveCutoff) {
      btnSaveCutoff.disabled = !isSuperAdmin;
      if (isSuperAdmin) {
        btnSaveCutoff.className = "btn btn-primary";
        btnSaveCutoff.innerHTML = `<i class="bi bi-shield-check me-1"></i> Save Cutoff Settings (Super Admin)`;
      } else {
        btnSaveCutoff.className = "btn btn-secondary disabled";
        btnSaveCutoff.innerHTML = `<i class="bi bi-lock-fill me-1"></i> Locked (Super Admin Only)`;
      }
    }

    if (alertContainer) {
      if (isSuperAdmin) {
        alertContainer.innerHTML = `
          <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-3 small shadow-sm">
            <i class="bi bi-shield-lock-fill text-success fs-5"></i>
            <div>
              <strong>Super Admin Mode (Suresh Gupta):</strong>
              <div class="text-muted">You have authorization to configure daily cutoff timings and rollover rules.</div>
            </div>
          </div>
        `;
      } else {
        alertContainer.innerHTML = `
          <div class="alert alert-danger d-flex align-items-center justify-content-between gap-2 py-2 px-3 mb-3 small shadow-sm">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-shield-exclamation text-danger fs-5"></i>
              <div>
                <strong>Access Restricted: Super Admin Only</strong>
                <div class="text-muted">Current Role: <strong>${state.currentUser.role}</strong>. Only <strong>Super Admin (Suresh Gupta)</strong> can modify cutoff timings and rollover rules.</div>
              </div>
            </div>
            <button class="btn btn-sm btn-outline-danger py-1 px-2.5 role-switch-btn" data-role-id="usr_03">
              <i class="bi bi-shield-lock me-1"></i>Switch to Super Admin
            </button>
          </div>
        `;
      }
    }
  }

  const formCutoff = document.getElementById("form-cutoff-settings");
  if (formCutoff) {
    formCutoff.addEventListener("submit", (e) => {
      e.preventDefault();

      // Strict role check: Only Super Admin can change cutoff
      if (state.currentUser.roleCode !== "ADMIN") {
        showToast("Access Denied: Only Super Admin (Suresh Gupta) is authorized to modify Daily Cutoff settings.", "danger");
        return;
      }

      const newCutoff = document.getElementById("setting-cutoff-time").value;
      const isCutoffActive = document.getElementById("setting-cutoff-toggle").checked;

      state.cutoffTime = newCutoff;
      state.cutoffRuleActive = isCutoffActive;

      // Update UI displays
      const [h, m] = newCutoff.split(":");
      const hours = parseInt(h);
      const ampm = hours >= 12 ? "PM" : "AM";
      const formattedCutoff = `${String(hours % 12 || 12).padStart(2, '0')}:${m} ${ampm}`;

      document.getElementById("header-cutoff-display").textContent = formattedCutoff;
      document.getElementById("import-cutoff-view").value = `${formattedCutoff} (${newCutoff} IST)`;

      state.auditTrail.unshift({
        timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
        user: state.currentUser.name,
        action: "CONFIG_CUTOFF",
        details: `Updated daily cutoff time to ${formattedCutoff} (Rollover: ${isCutoffActive ? 'Enabled' : 'Disabled'})`
      });

      showToast(`Cutoff updated to ${formattedCutoff}. Automatic rollover rule is active.`, "success");
      renderSettings();
    });
  }

  // --- ROLE SWITCHER / AUTHENTICATION SIMULATION & RBAC ---
  function setCurrentUser(userObj, notify = true) {
    state.currentUser = userObj;

    // Header User Updates
    const headerName = document.getElementById("header-user-name");
    const headerRole = document.getElementById("header-user-role");
    const headerAvatar = document.getElementById("header-avatar");

    if (headerName) headerName.textContent = userObj.name;
    if (headerRole) headerRole.textContent = userObj.role.split("(")[0].trim();
    if (headerAvatar) {
      headerAvatar.textContent = userObj.avatar || userObj.name.substring(0, 2).toUpperCase();
      headerAvatar.className = `text-white rounded-circle fw-bold d-flex align-items-center justify-content-center ${userObj.badgeClass || 'bg-primary'}`;
    }

    // Dynamic Top Role Context Bar
    const roleBar = document.getElementById("role-context-bar");
    if (roleBar) {
      roleBar.className = `role-context-banner role-${userObj.roleCode.toLowerCase()}`;
    }

    const roleBarBadge = document.getElementById("role-bar-badge");
    if (roleBarBadge) {
      roleBarBadge.className = `badge ${userObj.badgeClass || 'bg-primary'} px-2 py-1 d-flex align-items-center gap-1.5`;
    }

    const roleBarIcon = document.getElementById("role-bar-icon");
    if (roleBarIcon) {
      roleBarIcon.className = `bi ${userObj.icon || 'bi-person-badge-fill'}`;
    }

    const roleBarRoleName = document.getElementById("role-bar-role-name");
    if (roleBarRoleName) roleBarRoleName.textContent = userObj.role;

    const roleBarUserName = document.getElementById("role-bar-user-name");
    if (roleBarUserName) roleBarUserName.textContent = userObj.name;

    const roleBarSummary = document.getElementById("role-bar-summary");
    if (roleBarSummary) {
      if (userObj.roleCode === "OPERATOR") {
        roleBarSummary.innerHTML = `Allowed: Tally Import, Bill Check, Discounts/Returns, Credit. <span class="text-danger fw-semibold">Final Sealing requires Approver (Pooja Verma).</span>`;
      } else if (userObj.roleCode === "APPROVER") {
        roleBarSummary.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-shield-check me-1"></i>Authorized Signatory:</span> Full authority to review variance, authorize discounts, and execute Digital Cryptographic Sealing.`;
      } else if (userObj.roleCode === "ADMIN") {
        roleBarSummary.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-gear-fill me-1"></i>Master Admin Access:</span> Configure PSO Counter Series, Cutoff Time (7 PM), User Roles, and System Security.`;
      } else if (userObj.roleCode === "AUDITOR") {
        roleBarSummary.innerHTML = `<span class="text-warning fw-semibold text-dark"><i class="bi bi-eye-fill me-1"></i>Statutory Inspection Mode (Read-Only):</span> Inspect 7-day retention log, audit trails, and export certified compliance files.`;
      }
    }

    // Quick switch buttons active state
    document.querySelectorAll(".role-quick-btn").forEach(btn => {
      if (btn.dataset.roleId === userObj.id) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    // Auditor Banner toggle
    const auditorBanner = document.getElementById("auditor-banner");
    if (auditorBanner) {
      if (userObj.roleCode === "AUDITOR") {
        auditorBanner.classList.remove("d-none");
      } else {
        auditorBanner.classList.add("d-none");
      }
    }

    // Role Matrix Modal Card Highlights
    state.rolesList.forEach(r => {
      const card = document.getElementById(`role-card-${r.id}`);
      if (card) {
        const indicator = card.querySelector(".active-indicator");
        if (r.id === userObj.id) {
          card.classList.add("active-card");
          if (r.roleCode === "APPROVER") card.classList.add("approver-border");
          if (r.roleCode === "ADMIN") card.classList.add("admin-border");
          if (r.roleCode === "AUDITOR") card.classList.add("auditor-border");
          if (indicator) {
            indicator.classList.remove("d-none");
            indicator.textContent = "Current Active Role";
            indicator.className = `badge ${r.badgeClass || 'bg-primary'} active-indicator`;
          }
        } else {
          card.classList.remove("active-card", "approver-border", "admin-border", "auditor-border");
          if (indicator) indicator.classList.add("d-none");
        }
      }
    });

    // Refresh views to apply role permissions & restrictions
    renderDashboard();
    renderBillVerification();
    renderMasterRecon();
    renderApprovalSealing();
    renderPsoMgmt();
    renderCorrections();
    renderCreditCollections();
    renderRetention();
    renderSettings();

    if (notify) {
      showToast(`Active profile switched to: ${userObj.name} (${userObj.role})`, "info");
    }
  }

  // Bind role switch buttons (delegated & direct)
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".role-switch-btn") || e.target.closest(".role-quick-btn");
    if (btn && btn.dataset.roleId) {
      const roleId = btn.dataset.roleId;
      const user = state.rolesList.find(u => u.id === roleId);
      if (user) {
        setCurrentUser(user, true);
        // If inside modal, close it
        const matrixModal = bootstrap.Modal.getInstance(document.getElementById("modal-role-matrix"));
        if (matrixModal) matrixModal.hide();
      }
    }
  });

  document.getElementById("btn-logout")?.addEventListener("click", () => {
    if (confirm("Reset prototype back to initial default prompt state?")) {
      window.location.reload();
    }
  });

  // --- PRESET DEMO SCENARIOS ---
  document.querySelectorAll(".scenario-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const scenario = btn.dataset.scenario;
      if (scenario === "discrepancy") {
        // Prompt Default
        const cb2 = state.bills.find(b => b.billNo === "CB 02");
        if (cb2) {
          cb2.status = "Missing";
          cb2.remark = "Pending physical slip from counter 1";
        }
        state.isSealed = false;
        document.body.classList.remove("is-sealed");
        document.getElementById("sealed-banner").style.display = "none";
        showToast("Loaded Prompt Default Scenario (Difference ₹17,500 / Blocked)", "warning");
      } else if (scenario === "balanced") {
        // All bills matched
        state.bills.forEach(b => {
          if (b.status === "Missing") {
            b.status = "Matched";
            b.remark = "Physical slip confirmed in cashier bundle";
          }
        });
        showToast("Loaded Balanced Scenario (Difference ₹0 / Ready to Seal)", "success");
      } else if (scenario === "missing_multiple") {
        const cb2 = state.bills.find(b => b.billNo === "CB 02");
        const cb6 = state.bills.find(b => b.billNo === "CB 06");
        if (cb2) cb2.status = "Missing";
        if (cb6) cb6.status = "Missing";
        showToast("Loaded Multiple Missing Bills Scenario (CB 02 + CB 06 missing)", "danger");
      }

      renderDashboard();
      renderBillVerification();
      renderMasterRecon();
      renderApprovalSealing();
    });
  });

  // INITIAL BOOTSTRAP
  setCurrentUser(state.currentUser, false);
  renderDashboard();
});
