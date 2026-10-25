<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

$returns = DB::fetchAll("SELECT r.*, 
    c.contract_number, 
    cust.full_name as customer_name, cust.phone, cust.customer_code,
    u.full_name as processed_by_name
    FROM returns_cancellations r
    JOIN installment_contracts c ON r.contract_id = c.id
    JOIN customers cust ON r.customer_id = cust.id
    LEFT JOIN users u ON r.processed_by = u.id
    WHERE r.branch_id = ?
    ORDER BY r.id DESC", [$branchId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-arrow-rotate-left text-danger me-2"></i><?= __('returns') ?> & Contract Cancellations</h4>
        <p class="text-muted small mb-0">Manage product returns, contract cancellations, restocking, and customer refunds</p>
    </div>
    <a href="<?= BASE_URL ?>/returns/create.php" class="btn btn-danger fw-semibold shadow-sm">
        <i class="fa-solid fa-ban me-1"></i> Cancel Contract / Return
    </a>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Contract #</th>
                    <th>Customer Details</th>
                    <th>Type</th>
                    <th>Refund Given</th>
                    <th>Cancellation Fee</th>
                    <th>Reason</th>
                    <th>Authorized By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No returns or cancellations recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $r): ?>
                    <tr>
                        <td><?= format_date($r['return_date']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $r['contract_id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($r['contract_number']) ?>
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($r['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($r['phone']) ?></small>
                        </td>
                        <td><span class="badge bg-secondary text-uppercase"><?= str_replace('_', ' ', $r['return_type']) ?></span></td>
                        <td class="text-danger fw-bold"><?= format_currency($r['refund_amount']) ?></td>
                        <td class="text-dark fw-semibold"><?= format_currency($r['cancellation_fee']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($r['reason']) ?></td>
                        <td><?= htmlspecialchars($r['processed_by_name'] ?? 'Admin') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
