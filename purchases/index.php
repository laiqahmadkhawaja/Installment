<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

$purchases = DB::fetchAll("SELECT p.*, s.name as supplier_name, s.company_name, u.full_name as created_by_name 
    FROM purchases p 
    JOIN suppliers s ON p.supplier_id = s.id 
    LEFT JOIN users u ON p.created_by = u.id 
    WHERE p.branch_id = ? 
    ORDER BY p.id DESC", [$branchId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-cart-flatbed text-primary me-2"></i><?= __('purchases') ?> & Stock-in Log</h4>
        <p class="text-muted small mb-0">Record product inventory stock-in, cost invoices, and supplier payables</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/purchases/create.php" class="btn btn-primary fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> <?= __('new_purchase') ?>
        </a>
        <a href="<?= BASE_URL ?>/purchases/suppliers.php" class="btn btn-light border fw-semibold shadow-sm">
            <i class="fa-solid fa-truck-field me-1"></i> <?= __('supplier_list') ?>
        </a>
    </div>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Supplier</th>
                    <th>Purchase Date</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance Payable</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No purchase invoices recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($p['invoice_number']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($p['supplier_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($p['company_name'] ?: '-') ?></small>
                        </td>
                        <td><?= format_date($p['purchase_date']) ?></td>
                        <td class="fw-bold"><?= format_currency($p['total_amount']) ?></td>
                        <td class="text-success"><?= format_currency($p['paid_amount']) ?></td>
                        <td class="text-danger fw-bold"><?= format_currency($p['balance_amount']) ?></td>
                        <td><span class="badge bg-<?= $p['payment_status'] === 'paid' ? 'success' : 'warning' ?>"><?= ucfirst($p['payment_status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
