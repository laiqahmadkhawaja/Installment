<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$contract = DB::fetch("SELECT c.*, 
    cust.full_name as customer_name, cust.urdu_name as customer_urdu, cust.phone as customer_phone, cust.cnic as customer_cnic, cust.current_address as customer_address, cust.customer_code,
    u.full_name as salesperson_name,
    co.full_name as collection_officer_name,
    b.name as branch_name
    FROM installment_contracts c
    JOIN customers cust ON c.customer_id = cust.id
    LEFT JOIN users u ON c.salesperson_id = u.id
    LEFT JOIN users co ON c.collection_officer_id = co.id
    LEFT JOIN branches b ON c.branch_id = b.id
    WHERE c.id = ?", [$id]);

if (!$contract) {
    set_flash('danger', 'Contract not found.');
    header("Location: " . BASE_URL . "/contracts/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Contract Items
$items = DB::fetchAll("SELECT ci.*, p.name as product_name, p.product_code, p.model 
    FROM contract_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.contract_id = ?", [$id]);

// Installment Schedules
$schedules = DB::fetchAll("SELECT s.*, 
    DATEDIFF(CURDATE(), s.due_date) as days_diff 
    FROM installment_schedules s 
    WHERE s.contract_id = ? 
    ORDER BY s.installment_number ASC", [$id]);

// Payments against this contract
$payments = DB::fetchAll("SELECT * FROM payments WHERE contract_id = ? AND is_reversed = 0 ORDER BY id DESC", [$id]);

// Check for overdue installments and update status dynamically
$today = date('Y-m-d');
foreach ($schedules as &$sch) {
    if ($sch['status'] === 'pending' && $sch['due_date'] < $today) {
        $sch['status'] = 'overdue';
        // Auto update status in db if overdue
        DB::query("UPDATE installment_schedules SET status = 'overdue' WHERE id = ?", [$sch['id']]);
    }
}
unset($sch);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($contract['contract_number']) ?></h4>
            <span class="badge-status status-<?= $contract['status'] ?>"><?= ucfirst($contract['status']) ?></span>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($contract['branch_name'] ?? 'Main') ?></span>
        </div>
        <p class="text-muted small mb-0">Customer: <strong><?= htmlspecialchars($contract['customer_name']) ?></strong> &bull; Sale Date: <?= format_date($contract['sale_date']) ?></p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $contract['id'] ?>" class="btn btn-success btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Receive Payment
        </a>
        <a href="<?= BASE_URL ?>/contracts/print_contract.php?id=<?= $contract['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Agreement (اقرار نامہ)
        </a>
        <a href="<?= BASE_URL ?>/contracts/early_settlement.php?id=<?= $contract['id'] ?>" class="btn btn-outline-primary btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-calculator me-1"></i> Early Settlement
        </a>
        <a href="<?= BASE_URL ?>/customers/ledger.php?id=<?= $contract['customer_id'] ?>&contract_id=<?= $contract['id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-invoice me-1"></i> Ledger
        </a>
    </div>
</div>

<!-- Contract Snapshot Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Installment Sale Price</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($contract['installment_price_total']) ?></div>
            <small class="text-muted"><?= $contract['discount_amount'] > 0 ? 'Discount: ' . format_currency($contract['discount_amount']) : 'No Discount' ?></small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Down Payment</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($contract['down_payment']) ?></div>
            <small class="text-success"><i class="fa-solid fa-check me-1"></i>Paid at Delivery</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-semibold">Total Paid to Date</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($contract['total_paid']) ?></div>
            <small class="text-muted">DP + <?= count($payments) ?> collection(s)</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-danger">
            <div class="text-muted small fw-semibold">Remaining Balance</div>
            <div class="h4 fw-bold text-danger mt-1 mb-0"><?= format_currency($contract['balance_amount']) ?></div>
            <small class="text-muted">Over <?= $contract['installment_count'] ?> <?= ucfirst($contract['frequency']) ?> Installments</small>
        </div>
    </div>
</div>

<!-- Purchased Product Details -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-box-open me-2"></i>Purchased Item(s)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code / Model</th>
                    <th>Serial # / IMEI</th>
                    <th>Qty</th>
                    <th>Cash Price</th>
                    <th>Installment Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><code><?= htmlspecialchars($item['product_code']) ?></code> &bull; <?= htmlspecialchars($item['model'] ?: '-') ?></td>
                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['serial_number'] ?: 'N/A') ?></span></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= format_currency($item['cash_price']) ?></td>
                    <td class="fw-bold text-primary"><?= format_currency($item['installment_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Installment Schedule Breakdown (Core Module 5) -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-calendar-days text-success me-2"></i>Installment Schedule (اقساط کا شیڈول)</h6>
            <small class="text-muted">Individual tracking of each installment, partial payments, and overdue days</small>
        </div>
        <a href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $contract['id'] ?>" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Receive Next Due
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th style="width:70px;">Inst #</th>
                    <th>Due Date</th>
                    <th>Installment Amount</th>
                    <th>Late Fee</th>
                    <th>Paid Amount</th>
                    <th>Remaining Amount</th>
                    <th>Status</th>
                    <th>Paid Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $s): ?>
                <tr class="<?= $s['status'] === 'overdue' ? 'table-danger' : ($s['status'] === 'paid' ? 'table-success' : '') ?>">
                    <td class="fw-bold text-center">#<?= $s['installment_number'] ?></td>
                    <td>
                        <span class="fw-semibold <?= $s['status'] === 'overdue' ? 'text-danger' : '' ?>">
                            <?= format_date($s['due_date']) ?>
                        </span>
                        <?php if ($s['status'] === 'overdue'): ?>
                            <small class="badge bg-danger ms-1"><?= $s['days_diff'] ?> days overdue</small>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= format_currency($s['installment_amount']) ?></td>
                    <td><?= $s['late_fee'] > 0 ? format_currency($s['late_fee']) : '-' ?></td>
                    <td class="text-success fw-semibold"><?= format_currency($s['paid_amount']) ?></td>
                    <td class="fw-bold <?= $s['remaining_amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= format_currency($s['remaining_amount'] + $s['late_fee']) ?>
                    </td>
                    <td>
                        <span class="badge-status status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
                    </td>
                    <td class="small text-muted"><?= format_date($s['paid_date']) ?></td>
                    <td>
                        <?php if ($s['status'] !== 'paid'): ?>
                        <a href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $contract['id'] ?>&schedule_id=<?= $s['id'] ?>&amount=<?= ($s['remaining_amount'] + $s['late_fee']) ?>" class="btn btn-sm btn-outline-success py-1 px-2" title="Collect this installment">
                            <i class="fa-solid fa-dollar-sign me-1"></i> Pay
                        </a>
                        <?php else: ?>
                        <span class="text-success small fw-semibold"><i class="fa-solid fa-check-double me-1"></i> Settled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment History for this Contract -->
<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3">
        <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-receipt me-2"></i>Payment Receipts Collected Under This Contract</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Payment Date</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Remarks</th>
                    <th>Print</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" class="text-center py-3 text-muted">No collections recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($p['receipt_number']) ?></td>
                        <td><?= format_date($p['payment_date']) ?></td>
                        <td class="text-success fw-bold"><?= format_currency($p['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $p['payment_method']) ?></span></td>
                        <td><?= htmlspecialchars($p['transaction_reference'] ?: '-') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($p['remarks'] ?: '-') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/collection/receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-light border">
                                <i class="fa-solid fa-print text-primary"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
