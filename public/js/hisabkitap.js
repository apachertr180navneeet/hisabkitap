/**
 * HisabKitap ERP - Interactive Client Logic & Modal Handlers
 */
document.addEventListener("DOMContentLoaded", () => {
    // CSRF Token Setup for AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Toast Helper
    window.showErpToast = function (message, type = "primary") {
        const toastEl = document.getElementById("erp-toast");
        const toastBody = document.getElementById("erp-toast-body");
        if (!toastEl || !toastBody) return;

        toastEl.className = `toast align-items-center text-white bg-${type} border-0 shadow-lg`;
        toastBody.innerHTML = `<i class="bi bi-info-circle-fill me-2"></i><span>${message}</span>`;
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
    };

    // Mobile Sidebar Toggle
    const btnToggleSidebar = document.getElementById("btn-toggle-sidebar");
    const sidebar = document.getElementById("sidebar");
    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener("click", () => {
            sidebar.classList.toggle("show-mobile");
        });
    }

    // Missing Bill Investigation Modal Trigger
    document.querySelectorAll(".btn-open-investigate").forEach(btn => {
        btn.addEventListener("click", () => {
            const billNo = btn.dataset.billNo;
            const customer = btn.dataset.customer;
            const amount = btn.dataset.amount;
            const pso = btn.dataset.pso;

            document.getElementById("investigate-bill-no").textContent = billNo;
            document.getElementById("investigate-bill-hidden-no").value = billNo;
            document.getElementById("investigate-customer").textContent = customer;
            document.getElementById("investigate-amount").textContent = amount;

            const modal = new bootstrap.Modal(document.getElementById("modal-investigate-bill"));
            modal.show();
        });
    });

    // Handle Missing Bill Form Resolution AJAX
    const formInvestigate = document.getElementById("form-investigate-bill");
    if (formInvestigate) {
        formInvestigate.addEventListener("submit", async (e) => {
            e.preventDefault();
            const billNo = document.getElementById("investigate-bill-hidden-no").value;
            const reason = document.getElementById("investigate-reason").value;
            const remark = document.getElementById("investigate-remark").value;

            try {
                const res = await fetch("/verification/resolve-missing", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ bill_no: billNo, reason, remark })
                });

                const data = await res.json();
                if (data.success) {
                    showErpToast(data.message, "success");
                    bootstrap.Modal.getInstance(document.getElementById("modal-investigate-bill"))?.hide();
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    showErpToast("Error resolving bill: " + (data.message || "Unknown error"), "danger");
                }
            } catch (err) {
                console.error(err);
                showErpToast("Server communication error", "danger");
            }
        });
    }

    // Credit Collection Update Modal Trigger
    document.querySelectorAll(".btn-open-credit-update").forEach(btn => {
        btn.addEventListener("click", () => {
            const creditId = btn.dataset.creditId;
            const billNo = btn.dataset.billNo;
            const customer = btn.dataset.customer;
            const salesman = btn.dataset.salesman;
            const total = btn.dataset.total;
            const outstanding = btn.dataset.outstanding;

            document.getElementById("credit-modal-billno").textContent = billNo;
            document.getElementById("credit-modal-hidden-id").value = creditId;
            document.getElementById("credit-modal-customer").textContent = customer;
            document.getElementById("credit-modal-salesman").textContent = salesman;
            document.getElementById("credit-modal-total").textContent = total;
            document.getElementById("credit-modal-out").textContent = outstanding;
            document.getElementById("credit-modal-paid-input").max = outstanding.replace(/[^0-9.]/g, '');
            document.getElementById("credit-modal-paid-input").value = outstanding.replace(/[^0-9.]/g, '');

            const modal = new bootstrap.Modal(document.getElementById("modal-update-credit"));
            modal.show();
        });
    });
});
