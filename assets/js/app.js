/**
 * Installment Pro - Client-side Utilities & Schedule Calculation
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Auto-dismiss Flash Alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Table quick search filter
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table-custom tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Installment Calculation Engine for Contract Creation
    const calcInputs = ['input-cash-price', 'input-installment-price', 'input-discount', 'input-down-payment', 'input-months'];
    const hasCalc = calcInputs.some(id => document.getElementById(id));

    if (hasCalc) {
        function calculatePlan() {
            const instPrice = parseFloat(document.getElementById('input-installment-price')?.value) || 0;
            const discount = parseFloat(document.getElementById('input-discount')?.value) || 0;
            const downPayment = parseFloat(document.getElementById('input-down-payment')?.value) || 0;
            const months = parseInt(document.getElementById('input-months')?.value) || 1;

            const totalPayable = Math.max(0, instPrice - discount);
            const financedAmount = Math.max(0, totalPayable - downPayment);
            const monthlyInst = months > 0 ? (financedAmount / months) : 0;

            const dispPayable = document.getElementById('disp-total-payable');
            const dispFinanced = document.getElementById('disp-financed-amount');
            const dispMonthly = document.getElementById('disp-monthly-installment');

            if (dispPayable) dispPayable.innerText = 'Rs. ' + totalPayable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (dispFinanced) dispFinanced.innerText = 'Rs. ' + financedAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (dispMonthly) dispMonthly.innerText = 'Rs. ' + Math.round(monthlyInst).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const hiddenMonthly = document.getElementById('hidden-monthly-installment');
            if (hiddenMonthly) hiddenMonthly.value = Math.round(monthlyInst);
        }

        calcInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', calculatePlan);
            }
        });

        calculatePlan();
    }

    // Auto-position dropdowns inside tables (dropup if near bottom)
    document.addEventListener('show.bs.dropdown', function (e) {
        const toggle = e.target;
        const table = toggle.closest('.table-responsive');
        if (!table) return;

        const toggleRect = toggle.getBoundingClientRect();
        const tableRect = table.getBoundingClientRect();
        const spaceBelow = tableRect.bottom - toggleRect.bottom;
        const spaceAbove = toggleRect.top - tableRect.top;

        if (spaceBelow < 170 && spaceAbove > spaceBelow) {
            toggle.closest('.dropdown')?.classList.add('dropup');
        } else {
            toggle.closest('.dropdown')?.classList.remove('dropup');
        }
    });
});
