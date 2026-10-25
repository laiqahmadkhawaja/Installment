<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$method = $_GET['payment_method'] ?? '';

$sql = "SELECT p.*, 
    c.contract_number, 
    cust.full_name as customer_name, cust.phone, cust.customer_code,
    u.full_name as cashier_name
    FROM payments p
    JOIN installment_contracts c ON p.contract_id = c.id
    JOIN customers cust ON p.customer_id = cust.id
    LEFT JOIN users u ON p.collected_by = u.id
    WHERE p.branch_id = ? AND p.payment_date >= ? AND p.payment_date <= ? AND p.is_reversed = 0";

$params = [$branchId, $startDate, $endDate];
if (!empty($method)) {
    $sql .= " AND p.payment_method = ?";
    $params[] = $method;
}
$sql .= " ORDER BY p.payment_date DESC, p.id DESC";

$collections = DB::fetchAll($sql, $params);

$totalCollected = 0;
foreach ($collections as $c) {
    $totalCollected += (float)$c['amount'];
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Collections Report</h4>
        <p class="text-muted small mb-0">Period: <?= format_date($startDate) ?> to <?= format_date($endDate) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Report
        </button>
        <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Payment Channel</label>
                <select name="payment_method" class="form-select">
                    <option value="">All Payment Modes</option>
                    <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="jazzcash" <?= $method === 'jazzcash' ? 'selected' : '' ?>>JazzCash</option>
                    <option value="easypaisa" <?= $method === 'easypaisa' ? 'selected' : '' ?>>Easypaisa</option>
                    <option value="bank_transfer" <?= $method === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="fa-solid fa-filter"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Collections Summary</h6>
        <span class="badge bg-success fs-6"><?= format_currency($totalCollected) ?> Total Received</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>Contract #</th>
                    <th>Payment Method</th>
                    <th>Reference</th>
                    <th>Collected By</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($collections)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No collections recorded for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($collections as $c): ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="<?= BASE_URL ?>/collection/receipt.php?id=<?= $c['id'] ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($c['receipt_number']) ?>
                            </a>
                        </td>
                        <td><?= format_date($c['payment_date']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($c['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($c['phone']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($c['contract_number']) ?></td>
                        <td><span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $c['payment_method']) ?></span></td>
                        <td><?= htmlspecialchars($c['transaction_reference'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($c['cashier_name'] ?? 'Admin') ?></td>
                        <td class="text-end fw-bold text-success"><?= format_currency($c['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="7" class="text-end text-uppercase">Total Collections:</td>
                    <td class="text-end text-success h6 mb-0"><?= format_currency($totalCollected) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
