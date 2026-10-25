<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

$products = DB::fetchAll("SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.stock_quantity DESC, p.name ASC");

$totalItems = 0;
$totalCostVal = 0;
$totalCashVal = 0;
$totalInstVal = 0;

foreach ($products as $p) {
    $qty = (int)$p['stock_quantity'];
    $totalItems += $qty;
    $totalCostVal += ($qty * (float)$p['purchase_cost']);
    $totalCashVal += ($qty * (float)$p['cash_price']);
    $totalInstVal += ($qty * (float)$p['installment_price']);
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i>Inventory Valuation Report</h4>
        <p class="text-muted small mb-0">Total available stock valued at Purchase Cost, Cash Sale Price, and Installment Price</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Valuation
        </button>
        <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Stock Valuation KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Physical Units in Stock</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= number_format($totalItems) ?></div>
            <small class="text-muted"><?= count($products) ?> Product Models</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-warning">
            <div class="text-muted small fw-semibold">Total Cost Value (Purchase)</div>
            <div class="h4 fw-bold text-warning mt-1 mb-0"><?= format_currency($totalCostVal) ?></div>
            <small class="text-muted">Total Capital Tied in Stock</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Cash Price Valuation</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($totalCashVal) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-primary">
            <div class="text-muted small fw-semibold">Installment Sale Valuation</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($totalInstVal) ?></div>
            <small class="text-muted">Potential Gross Revenue</small>
        </div>
    </div>
</div>

<!-- Inventory Details Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Product Details</th>
                    <th>Category</th>
                    <th class="text-center">Available Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Unit Inst. Price</th>
                    <th class="text-end">Total Cost Value</th>
                    <th class="text-end">Total Inst. Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <?php
                $qty = (int)$p['stock_quantity'];
                $itemCost = $qty * (float)$p['purchase_cost'];
                $itemInst = $qty * (float)$p['installment_price'];
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                        <small class="text-muted"><code><?= htmlspecialchars($p['product_code']) ?></code> &bull; Model: <?= htmlspecialchars($p['model'] ?: '-') ?></small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name'] ?: 'General') ?></span></td>
                    <td class="text-center">
                        <?php if ($qty <= $p['min_stock_alert']): ?>
                            <span class="badge bg-danger"><?= $qty ?> Low</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= $qty ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= format_currency($p['purchase_cost']) ?></td>
                    <td class="text-end text-primary fw-semibold"><?= format_currency($p['installment_price']) ?></td>
                    <td class="text-end fw-semibold text-warning"><?= format_currency($itemCost) ?></td>
                    <td class="text-end fw-bold text-primary"><?= format_currency($itemInst) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="2" class="text-uppercase text-end">Grand Totals:</td>
                    <td class="text-center"><?= number_format($totalItems) ?></td>
                    <td colspan="2"></td>
                    <td class="text-end text-warning"><?= format_currency($totalCostVal) ?></td>
                    <td class="text-end text-primary"><?= format_currency($totalInstVal) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
