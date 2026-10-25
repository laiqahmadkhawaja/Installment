<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i><?= __('reports') ?> & Business Analytics</h4>
        <p class="text-muted small mb-0">Generate, view, and print financial, collection, customer, and inventory audit reports</p>
    </div>
</div>

<div class="row g-4">
    <!-- Sales Reports -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="fa-solid fa-file-invoice-dollar fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Installment Sales Report</h5>
                    <small class="text-muted">Contracts created & financed volume</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Analyze sales performance by date range, branch, product category, and salesperson.</p>
            <a href="<?= BASE_URL ?>/reports/sales.php" class="btn btn-outline-primary btn-sm fw-semibold">View Sales Report &rarr;</a>
        </div>
    </div>

    <!-- Collections Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="fa-solid fa-hand-holding-dollar fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Collections Report</h5>
                    <small class="text-muted">Cash received & recovery breakdown</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Track payments received across cash, bank transfers, JazzCash, Easypaisa, and collection officers.</p>
            <a href="<?= BASE_URL ?>/reports/collections.php" class="btn btn-outline-success btn-sm fw-semibold">View Collections &rarr;</a>
        </div>
    </div>

    <!-- Receivables & Aging -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                    <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Receivables & Aging</h5>
                    <small class="text-muted">Outstanding market balances</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Aging analysis: 1-7, 8-30, 31-60, 61-90, 90+ days overdue balances per customer.</p>
            <a href="<?= BASE_URL ?>/reports/receivables.php" class="btn btn-outline-danger btn-sm fw-semibold">View Receivables &rarr;</a>
        </div>
    </div>

    <!-- Stock Valuation -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                    <i class="fa-solid fa-boxes-stacked fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Stock Valuation Report</h5>
                    <small class="text-muted">Inventory audit & asset value</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Valuation of current in-stock goods calculated at cost, cash sale price, and installment price.</p>
            <a href="<?= BASE_URL ?>/reports/stock.php" class="btn btn-outline-warning text-dark btn-sm fw-semibold">View Stock Valuation &rarr;</a>
        </div>
    </div>

    <!-- Profit & Loss -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-info bg-opacity-10 text-info rounded-3 p-3">
                    <i class="fa-solid fa-chart-line fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Profit & Loss Statement</h5>
                    <small class="text-muted">True operating profitability</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Gross markup profit minus operating expenses, salaries, rent, and utility bills.</p>
            <a href="<?= BASE_URL ?>/accounting/profit_loss.php" class="btn btn-outline-info text-dark btn-sm fw-semibold">View Profit & Loss &rarr;</a>
        </div>
    </div>

    <!-- Recovery Officer Performance -->
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 border rounded-3 shadow-sm h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary rounded-3 p-3">
                    <i class="fa-solid fa-user-check fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Staff Recovery Targets</h5>
                    <small class="text-muted">Employee performance & commission</small>
                </div>
            </div>
            <p class="small text-muted flex-grow-1">Compare daily recovery targets against actual collections achieved by field officers.</p>
            <a href="<?= BASE_URL ?>/reports/employee.php" class="btn btn-outline-secondary btn-sm fw-semibold">View Staff Performance &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
