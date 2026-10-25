<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$startDate = $_GET['start_date'] ?? date('Y-01-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 1. Total Sales Value in period
$salesData = DB::fetch("SELECT 
    COUNT(c.id) as contract_count,
    COALESCE(SUM(c.installment_price_total - c.discount_amount), 0) as total_sales_revenue,
    COALESCE(SUM(ci.quantity * ci.unit_cost), 0) as total_cogs
    FROM installment_contracts c
    JOIN contract_items ci ON c.id = ci.contract_id
    WHERE c.branch_id = ? AND c.sale_date >= ? AND c.sale_date <= ? AND c.status != 'cancelled'", [$branchId, $startDate, $endDate]);

$totalRevenue = (float)$salesData['total_sales_revenue'];
$totalCOGS = (float)$salesData['total_cogs'];
$grossProfit = $totalRevenue - $totalCOGS;
$grossMarginPct = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;

// 2. Collections in period
$totalCashCollected = (float)(DB::fetch("SELECT COALESCE(SUM(amount), 0) as s 
    FROM payments WHERE branch_id = ? AND payment_date >= ? AND payment_date <= ? AND is_reversed = 0", [$branchId, $startDate, $endDate])['s'] ?? 0);

// 3. Expenses Breakdown by Category
$expenseCategories = DB::fetchAll("SELECT ec.name, ec.urdu_name, COALESCE(SUM(e.amount), 0) as category_total 
    FROM expense_categories ec 
    LEFT JOIN expenses e ON ec.id = e.category_id AND e.branch_id = ? AND e.expense_date >= ? AND e.expense_date <= ? 
    GROUP BY ec.id 
    HAVING category_total > 0 
    ORDER BY category_total DESC", [$branchId, $startDate, $endDate]);

$totalOperatingExpenses = 0;
foreach ($expenseCategories as $ec) {
    $totalOperatingExpenses += (float)$ec['category_total'];
}

// 4. Net Operating Profit
$netProfit = $grossProfit - $totalOperatingExpenses;
$netMarginPct = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-line text-success me-2"></i><?= __('profit_loss') ?> (نفع و نقصان)</h4>
        <p class="text-muted small mb-0">Financial statement showing Gross Markup Margin, COGS, Operating Expenses, and Net Profit</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
        <i class="fa-solid fa-print me-1"></i> Print P&L Statement
    </button>
</div>

<!-- Date Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">From Period Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">To Period Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="fa-solid fa-calculator me-1"></i> Calculate</button>
            </div>
        </form>
    </div>
</div>

<!-- KPI Overview -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Contract Sales Revenue</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($totalRevenue) ?></div>
            <small class="text-muted"><?= $salesData['contract_count'] ?> Contracts Financed</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-warning">
            <div class="text-muted small fw-semibold">Cost of Goods Sold (COGS)</div>
            <div class="h4 fw-bold text-warning mt-1 mb-0"><?= format_currency($totalCOGS) ?></div>
            <small class="text-muted">Product Inventory Cost</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-primary">
            <div class="text-muted small fw-semibold">Gross Profit Margin</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($grossProfit) ?></div>
            <small class="text-muted"><?= $grossMarginPct ?>% Margin</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 <?= $netProfit >= 0 ? 'border-success' : 'border-danger' ?>">
            <div class="text-muted small fw-semibold">Net Operating Profit</div>
            <div class="h4 fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?> mt-1 mb-0">
                <?= format_currency($netProfit) ?>
            </div>
            <small class="text-muted"><?= $netMarginPct ?>% Net Profit</small>
        </div>
    </div>
</div>

<!-- Structured P&L Table -->
<div class="card border rounded-3 shadow-sm p-4 mb-4">
    <div class="text-center mb-4">
        <h3 class="fw-bold mb-1"><?= htmlspecialchars(get_setting('company_name', 'Installment Management System')) ?></h3>
        <h5 class="fw-bold text-uppercase border-top border-bottom py-2 d-inline-block px-4">
            Income Statement (Profit & Loss) / گوشوارہ نفع و نقصان
        </h5>
        <div class="text-muted small mt-1">Period: <?= format_date($startDate) ?> to <?= format_date($endDate) ?></div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 table-custom">
            <thead class="table-light">
                <tr>
                    <th>Account / Particulars</th>
                    <th class="text-end" style="width:180px;">Amount (Rs.)</th>
                    <th class="text-end" style="width:180px;">Total (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Revenue -->
                <tr class="table-light fw-bold">
                    <td colspan="3"><i class="fa-solid fa-arrow-trend-up text-success me-2"></i>1. Operating Sales Revenue (فروخت آمدنی)</td>
                </tr>
                <tr>
                    <td class="ps-4">Installment Financing Contracts Sales Value</td>
                    <td class="text-end"><?= number_format($totalRevenue, 2) ?></td>
                    <td></td>
                </tr>
                <tr class="fw-bold">
                    <td class="text-end">Total Revenue (کل سیلز آمدنی):</td>
                    <td></td>
                    <td class="text-end text-success"><?= format_currency($totalRevenue) ?></td>
                </tr>

                <!-- COGS -->
                <tr class="table-light fw-bold">
                    <td colspan="3"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i>2. Cost of Sales / COGS (سامان کی لاگت)</td>
                </tr>
                <tr>
                    <td class="ps-4">Purchase Cost of Financed Products</td>
                    <td class="text-end text-danger">(<?= number_format($totalCOGS, 2) ?>)</td>
                    <td></td>
                </tr>
                <tr class="fw-bold">
                    <td class="text-end">Total Cost of Goods Sold:</td>
                    <td></td>
                    <td class="text-end text-danger">(<?= format_currency($totalCOGS) ?>)</td>
                </tr>

                <!-- Gross Profit -->
                <tr class="table-primary fw-bold">
                    <td class="text-uppercase">Gross Sales Margin / منافع خام:</td>
                    <td></td>
                    <td class="text-end text-primary h6 mb-0"><?= format_currency($grossProfit) ?></td>
                </tr>

                <!-- Operating Expenses -->
                <tr class="table-light fw-bold">
                    <td colspan="3"><i class="fa-solid fa-money-bill-transfer text-danger me-2"></i>3. Operating Expenses (کاروباری اخراجات)</td>
                </tr>
                <?php if (empty($expenseCategories)): ?>
                    <tr><td colspan="3" class="ps-4 text-muted">No operating expenses recorded for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenseCategories as $ec): ?>
                    <tr>
                        <td class="ps-4">
                            <?= htmlspecialchars($ec['name']) ?>
                            <?php if ($ec['urdu_name']): ?><small class="text-muted ms-2">(<?= htmlspecialchars($ec['urdu_name']) ?>)</small><?php endif; ?>
                        </td>
                        <td class="text-end text-danger">(<?= number_format($ec['category_total'], 2) ?>)</td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="fw-bold">
                    <td class="text-end">Total Operating Expenses (کل اخراجات):</td>
                    <td></td>
                    <td class="text-end text-danger">(<?= format_currency($totalOperatingExpenses) ?>)</td>
                </tr>

                <!-- Net Profit -->
                <tr class="table-success fw-bold">
                    <td class="text-uppercase h6 mb-0">Net Operating Profit / خالص منافع:</td>
                    <td></td>
                    <td class="text-end text-success h5 mb-0"><?= format_currency($netProfit) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Accrual vs Cash Note -->
    <div class="mt-4 p-3 bg-light rounded-3 border small text-muted">
        <strong>Accounting Note:</strong> This statement reflects accrual markup based on recognized installment contracts and inventory cost during the period. Total actual physical cash collected during this period was <strong><?= format_currency($totalCashCollected) ?></strong>.
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
