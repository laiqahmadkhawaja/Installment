<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

$customers = DB::fetchAll("SELECT cust.*,
    COALESCE(SUM(c.balance_amount), 0) as total_receivable,
    COUNT(c.id) as active_contracts_count
    FROM customers cust
    JOIN installment_contracts c ON cust.id = c.customer_id
    WHERE c.branch_id = ? AND c.status IN ('active', 'overdue', 'defaulted')
    GROUP BY cust.id
    HAVING total_receivable > 0
    ORDER BY total_receivable DESC", [$branchId]);

$grandReceivable = 0;
foreach ($customers as $cu) {
    $grandReceivable += (float)$cu['total_receivable'];
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Outstanding Receivables Report</h4>
        <p class="text-muted small mb-0">Total active market receivables by customer with risk category</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Receivables
        </button>
        <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<div class="card p-3 border rounded-3 bg-body shadow-sm mb-4 border-start border-4 border-danger">
    <div class="text-muted small fw-semibold">Grand Total Outstanding Receivables Across All Customers</div>
    <div class="h3 fw-bold text-danger mt-1 mb-0"><?= format_currency($grandReceivable) ?></div>
    <small class="text-muted"><?= count($customers) ?> Customers with Outstanding Balances</small>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>CNIC & Contact</th>
                    <th>Address & City</th>
                    <th>Category</th>
                    <th>Active Plans</th>
                    <th class="text-end">Outstanding Balance</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No outstanding customer balances!</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $cu): ?>
                    <tr>
                        <td>
                            <div class="fw-bold">
                                <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $cu['id'] ?>" class="text-decoration-none text-body">
                                    <?= htmlspecialchars($cu['full_name']) ?>
                                </a>
                            </div>
                            <small class="text-muted"><code><?= htmlspecialchars($cu['customer_code']) ?></code></small>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($cu['cnic']) ?></div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($cu['phone']) ?></small>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($cu['city']) ?></div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width:200px;"><?= htmlspecialchars($cu['current_address']) ?></small>
                        </td>
                        <td><span class="badge bg-secondary text-uppercase"><?= str_replace('_', ' ', $cu['category']) ?></span></td>
                        <td><span class="badge bg-light text-dark border"><?= $cu['active_contracts_count'] ?> active</span></td>
                        <td class="text-end fw-bold text-danger"><?= format_currency($cu['total_receivable']) ?></td>
                        <td class="no-print">
                            <a href="<?= BASE_URL ?>/customers/ledger.php?id=<?= $cu['id'] ?>" class="btn btn-sm btn-outline-dark">
                                Ledger
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="5" class="text-end text-uppercase">Total Market Receivables:</td>
                    <td class="text-end text-danger h6 mb-0"><?= format_currency($grandReceivable) ?></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
