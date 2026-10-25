<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$sheetDate = $_GET['date'] ?? date('Y-m-d');
$officerId = (int)($_GET['officer_id'] ?? 0);

$sql = "SELECT p.*, 
    c.contract_number, 
    cust.full_name as customer_name, cust.phone, cust.customer_code,
    u.full_name as cashier_name
    FROM payments p
    JOIN installment_contracts c ON p.contract_id = c.id
    JOIN customers cust ON p.customer_id = cust.id
    LEFT JOIN users u ON p.collected_by = u.id
    WHERE p.branch_id = ? AND p.payment_date = ? AND p.is_reversed = 0";

$params = [$branchId, $sheetDate];
if ($officerId > 0) {
    $sql .= " AND p.collected_by = ?";
    $params[] = $officerId;
}
$sql .= " ORDER BY p.id DESC";

$payments = DB::fetchAll($sql, $params);

// Method-wise totals
$channelTotals = DB::fetchAll("SELECT payment_method, COUNT(id) as cnt, SUM(amount) as total 
    FROM payments 
    WHERE branch_id = ? AND payment_date = ? AND is_reversed = 0 
    GROUP BY payment_method", [$branchId, $sheetDate]);

$grandTotal = 0;
foreach ($payments as $p) {
    $grandTotal += (float)$p['amount'];
}

$users = DB::fetchAll("SELECT id, full_name FROM users WHERE status = 'active'");
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-2"></i><?= __('daily_collection') ?></h4>
        <p class="text-muted small mb-0">Daily cashbook receipts and recovery breakdown for <?= format_date($sheetDate) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Daily Sheet
        </button>
        <a href="<?= BASE_URL ?>/collection/receive.php" class="btn btn-primary btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> New Payment
        </a>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Select Collection Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($sheetDate) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Filter by Collection Officer / Cashier</label>
                <select name="officer_id" class="form-select">
                    <option value="">All Officers</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $officerId == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Load Sheet</button>
                <a href="<?= BASE_URL ?>/collection/daily_sheet.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Channel Breakdown Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-primary">
            <div class="text-muted small fw-semibold">Grand Total Collections</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($grandTotal) ?></div>
            <small class="text-muted"><?= count($payments) ?> Transactions</small>
        </div>
    </div>
    <?php foreach ($channelTotals as $ct): ?>
    <div class="col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold text-uppercase"><?= str_replace('_', ' ', $ct['payment_method']) ?></div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($ct['total']) ?></div>
            <small class="text-muted"><?= $ct['cnt'] ?> Receipts</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Transactions Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Customer Name</th>
                    <th>Contract #</th>
                    <th>Payment Mode</th>
                    <th>Reference</th>
                    <th>Officer</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="no-print">Print</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No collections recorded on this date.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="<?= BASE_URL ?>/collection/receipt.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($p['receipt_number']) ?>
                            </a>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($p['phone']) ?></small>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $p['contract_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($p['contract_number']) ?>
                            </a>
                        </td>
                        <td><span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $p['payment_method']) ?></span></td>
                        <td><?= htmlspecialchars($p['transaction_reference'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($p['cashier_name'] ?? 'Admin') ?></td>
                        <td class="text-end fw-bold text-success"><?= format_currency($p['amount']) ?></td>
                        <td class="no-print">
                            <a href="<?= BASE_URL ?>/collection/receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-light border">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="6" class="text-end text-uppercase">Total Collections for <?= format_date($sheetDate) ?>:</td>
                    <td class="text-end text-success h6 mb-0"><?= format_currency($grandTotal) ?></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
