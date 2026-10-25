<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

// 1. Core KPIs
$totalCustomers = DB::fetch("SELECT COUNT(*) as c FROM customers WHERE branch_id = ? AND status != 'blacklisted'", [$branchId])['c'] ?? 0;
$activePlans = DB::fetch("SELECT COUNT(*) as c FROM installment_contracts WHERE branch_id = ? AND status = 'active'", [$branchId])['c'] ?? 0;

// Sales & Cash Metrics
$salesMetrics = DB::fetch("SELECT 
    COALESCE(SUM(total_payable), 0) as total_sales,
    COALESCE(SUM(down_payment), 0) as total_dp,
    COALESCE(SUM(total_paid), 0) as total_received,
    COALESCE(SUM(balance_amount), 0) as total_outstanding,
    COALESCE(SUM(cash_price_total), 0) as total_cash_cost
    FROM installment_contracts WHERE branch_id = ? AND status != 'cancelled'", [$branchId]);

$totalSalesValue = (float)($salesMetrics['total_sales'] ?? 0);
$totalDownPayments = (float)($salesMetrics['total_dp'] ?? 0);
$totalCollections = (float)($salesMetrics['total_received'] ?? 0);
$totalOutstanding = (float)($salesMetrics['total_outstanding'] ?? 0);

// Today & Monthly Collections
$today = date('Y-m-d');
$firstOfMonth = date('Y-m-01');
$todayCollections = (float)(DB::fetch("SELECT COALESCE(SUM(amount), 0) as s FROM payments WHERE branch_id = ? AND payment_date = ? AND is_reversed = 0", [$branchId, $today])['s'] ?? 0);
$monthCollections = (float)(DB::fetch("SELECT COALESCE(SUM(amount), 0) as s FROM payments WHERE branch_id = ? AND payment_date >= ? AND is_reversed = 0", [$branchId, $firstOfMonth])['s'] ?? 0);

// Overdue Metrics
$overdueMetrics = DB::fetch("SELECT 
    COALESCE(SUM(remaining_amount + late_fee), 0) as overdue_total,
    COUNT(DISTINCT c.customer_id) as defaulter_customers
    FROM installment_schedules s
    JOIN installment_contracts c ON s.contract_id = c.id
    WHERE c.branch_id = ? AND s.status = 'overdue' AND c.status IN ('active', 'overdue', 'defaulted')", [$branchId]);

$overdueTotal = (float)($overdueMetrics['overdue_total'] ?? 0);
$defaulterCustomers = (int)($overdueMetrics['defaulter_customers'] ?? 0);

// Expected collections this month
$expectedMonth = (float)(DB::fetch("SELECT COALESCE(SUM(s.installment_amount), 0) as s 
    FROM installment_schedules s 
    JOIN installment_contracts c ON s.contract_id = c.id 
    WHERE c.branch_id = ? AND s.due_date >= ? AND s.due_date <= LAST_DAY(?) AND c.status = 'active'", [$branchId, $firstOfMonth, $today])['s'] ?? 0);

// Expenses & Real Profit
$totalExpenses = (float)(DB::fetch("SELECT COALESCE(SUM(amount), 0) as s FROM expenses WHERE branch_id = ?", [$branchId])['s'] ?? 0);
// Estimated gross markup: total installment sales value minus product purchase cost
$grossProfit = (float)(DB::fetch("SELECT COALESCE(SUM(i.subtotal - (i.quantity * i.unit_cost)), 0) as profit 
    FROM contract_items i 
    JOIN installment_contracts c ON i.contract_id = c.id 
    WHERE c.branch_id = ? AND c.status != 'cancelled'", [$branchId])['profit'] ?? 0);
$netProfit = $grossProfit - $totalExpenses;

// Recent Sales (Last 5)
$recentContracts = DB::fetchAll("SELECT c.*, cust.full_name as customer_name, cust.phone 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.branch_id = ? 
    ORDER BY c.id DESC LIMIT 5", [$branchId]);

// Recent Payments (Last 5)
$recentPayments = DB::fetchAll("SELECT p.*, cust.full_name as customer_name, c.contract_number 
    FROM payments p 
    JOIN customers cust ON p.customer_id = cust.id 
    JOIN installment_contracts c ON p.contract_id = c.id 
    WHERE p.branch_id = ? AND p.is_reversed = 0 
    ORDER BY p.id DESC LIMIT 5", [$branchId]);

// Overdue Installments alert list
$upcomingOverdue = DB::fetchAll("SELECT s.*, c.contract_number, cust.full_name as customer_name, cust.phone, DATEDIFF(CURDATE(), s.due_date) as days_overdue
    FROM installment_schedules s 
    JOIN installment_contracts c ON s.contract_id = c.id 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.branch_id = ? AND s.status = 'overdue' 
    ORDER BY s.due_date ASC LIMIT 5", [$branchId]);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-gauge-high text-primary me-2"></i><?= __('dashboard') ?></h4>
        <p class="text-muted small mb-0"><?= get_setting('company_name') ?> &bull; <?= htmlspecialchars($activeBranch['name']) ?></p>
    </div>
    
    <!-- Quick Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/collection/receive.php" class="btn btn-primary btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> <?= __('receive_payment') ?>
        </a>
        <a href="<?= BASE_URL ?>/contracts/create.php" class="btn btn-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-file-circle-plus me-1"></i> <?= __('new_contract') ?>
        </a>
        <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-light border btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-user-plus me-1"></i> <?= __('new_customer') ?>
        </a>
        <a href="<?= BASE_URL ?>/defaulters/index.php" class="btn btn-outline-danger btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= __('overdue_list') ?>
        </a>
    </div>
</div>

<!-- Primary Financial Distinction Banner -->
<div class="alert alert-light border shadow-sm py-2 px-3 mb-4 rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2 small">
    <div>
        <i class="fa-solid fa-circle-info text-primary me-1"></i>
        <strong>Financial Distinction:</strong> Sales Volume differs from Cash Received and True Profit.
    </div>
    <div class="d-flex gap-4">
        <span>Total Sales Value: <strong><?= format_currency($totalSalesValue) ?></strong></span>
        <span>Total Cash Inflow: <strong class="text-success"><?= format_currency($totalCollections) ?></strong></span>
        <span>Market Receivables: <strong class="text-danger"><?= format_currency($totalOutstanding) ?></strong></span>
    </div>
</div>

<!-- Row 1: Key Operational KPIs -->
<div class="row g-3 mb-4">
    <!-- Active Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title"><?= __('total_customers') ?></div>
                    <div class="kpi-value"><?= number_format($totalCustomers) ?></div>
                    <div class="small text-muted mt-1"><?= $activePlans ?> <?= __('active_contracts') ?></div>
                </div>
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Receivables -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-kpi p-3 border-start border-4 border-danger">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title"><?= __('outstanding_receivables') ?></div>
                    <div class="kpi-value text-danger"><?= format_currency($totalOutstanding) ?></div>
                    <div class="small text-muted mt-1"><?= number_format($totalSalesValue) ?> Total Financed</div>
                </div>
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Collections -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-kpi p-3 border-start border-4 border-success">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title"><?= __('today_collections') ?></div>
                    <div class="kpi-value text-success"><?= format_currency($todayCollections) ?></div>
                    <div class="small text-muted mt-1"><?= format_currency($monthCollections) ?> This Month</div>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Installments -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-kpi p-3 border-start border-4 border-warning">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title"><?= __('overdue_installments') ?></div>
                    <div class="kpi-value text-warning"><?= format_currency($overdueTotal) ?></div>
                    <div class="small text-muted mt-1"><span class="badge bg-danger"><?= $defaulterCustomers ?> <?= __('defaulter_count') ?></span></div>
                </div>
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Secondary Profitability & Cash Flow KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 bg-body border rounded-3">
            <div class="text-muted small fw-semibold"><?= __('down_payments') ?></div>
            <div class="h5 fw-bold text-primary mt-1 mb-0"><?= format_currency($totalDownPayments) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 bg-body border rounded-3">
            <div class="text-muted small fw-semibold"><?= __('sales_profit') ?></div>
            <div class="h5 fw-bold text-success mt-1 mb-0"><?= format_currency($grossProfit) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 bg-body border rounded-3">
            <div class="text-muted small fw-semibold"><?= __('total_expenses') ?></div>
            <div class="h5 fw-bold text-secondary mt-1 mb-0"><?= format_currency($totalExpenses) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 bg-body border rounded-3">
            <div class="text-muted small fw-semibold"><?= __('net_profit') ?></div>
            <div class="h5 fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?> mt-1 mb-0">
                <?= format_currency($netProfit) ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Charts Section -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-chart-column text-primary me-2"></i><?= __('sales_vs_collections') ?></h6>
                <span class="badge bg-light text-dark border">FY 2026</span>
            </div>
            <div class="card-body p-3">
                <canvas id="salesCollectionsChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-pie-chart text-success me-2"></i>Payment Channels</h6>
            </div>
            <div class="card-body p-3 d-flex align-items-center justify-content-center">
                <div style="max-height: 220px; width: 100%;">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Recent Activity & Overdue Follow-ups -->
<div class="row g-3">
    <!-- Recent Installment Sales -->
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-file-invoice text-primary me-2"></i><?= __('recent_sales') ?></h6>
                <a href="<?= BASE_URL ?>/contracts/index.php" class="btn btn-sm btn-link p-0 text-decoration-none"><?= __('view') ?> &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th><?= __('contract_no') ?></th>
                            <th><?= __('customer_name') ?></th>
                            <th><?= __('installment_price') ?></th>
                            <th><?= __('balance') ?></th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentContracts)): ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted"><?= __('no_records') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($recentContracts as $rc): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $rc['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= htmlspecialchars($rc['contract_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($rc['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($rc['phone']) ?></small>
                                </td>
                                <td><?= format_currency($rc['installment_price_total']) ?></td>
                                <td class="fw-semibold text-danger"><?= format_currency($rc['balance_amount']) ?></td>
                                <td>
                                    <span class="badge-status status-<?= $rc['status'] ?>"><?= ucfirst($rc['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Overdue Installment Alerts -->
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= __('overdue_list') ?></h6>
                <a href="<?= BASE_URL ?>/defaulters/index.php" class="btn btn-sm btn-link text-danger p-0 text-decoration-none"><?= __('view') ?> &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th><?= __('customer_name') ?></th>
                            <th><?= __('due_date') ?></th>
                            <th><?= __('monthly_installment') ?></th>
                            <th>Days</th>
                            <th><?= __('action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcomingOverdue)): ?>
                            <tr><td colspan="5" class="text-center py-3 text-success"><i class="fa-solid fa-check-circle me-1"></i> No overdue installments right now!</td></tr>
                        <?php else: ?>
                            <?php foreach ($upcomingOverdue as $uo): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($uo['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($uo['contract_number']) ?></small>
                                </td>
                                <td class="text-danger fw-semibold"><?= format_date($uo['due_date']) ?></td>
                                <td><?= format_currency($uo['remaining_amount'] + $uo['late_fee']) ?></td>
                                <td><span class="badge bg-danger"><?= $uo['days_overdue'] ?> days</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $uo['contract_id'] ?>" class="btn btn-outline-success" title="<?= __('receive_payment') ?>">
                                            <i class="fa-solid fa-dollar-sign"></i>
                                        </a>
                                        <a href="<?= whatsapp_url($uo['phone'], "Dear Customer, your installment of " . format_currency($uo['remaining_amount']) . " for Contract " . $uo['contract_number'] . " is overdue by " . $uo['days_overdue'] . " days. Please make payment urgently.") ?>" target="_blank" class="btn btn-outline-success" title="WhatsApp Reminder">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart initialization scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sales vs Collections Chart
    const ctx1 = document.getElementById('salesCollectionsChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'Installment Sales (Rs.)',
                        data: [180000, 240000, 310000, 420000, 380000, <?= $totalSalesValue ?>],
                        backgroundColor: 'rgba(37, 99, 235, 0.8)',
                        borderRadius: 6
                    },
                    {
                        label: 'Cash Collections (Rs.)',
                        data: [120000, 160000, 210000, 290000, 320000, <?= $totalCollections ?>],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return 'Rs. ' + (value/1000) + 'k'; }
                        }
                    }
                }
            }
        });
    }

    // Payment Methods Breakdown
    const ctx2 = document.getElementById('paymentMethodsChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'JazzCash', 'Bank Transfer', 'Easypaisa'],
                datasets: [{
                    data: [65, 20, 12, 3],
                    backgroundColor: ['#2563eb', '#f59e0b', '#10b981', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
