<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$customer = DB::fetch("SELECT * FROM customers WHERE id = ?", [$id]);

if (!$customer) {
    set_flash('danger', 'Customer record not found.');
    header("Location: " . BASE_URL . "/customers/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Financial Statistics
$stats = DB::fetch("SELECT 
    COUNT(id) as total_contracts,
    COALESCE(SUM(installment_price_total), 0) as total_purchases,
    COALESCE(SUM(down_payment), 0) as total_down_payment,
    COALESCE(SUM(total_paid), 0) as total_payments_received,
    COALESCE(SUM(balance_amount), 0) as total_remaining_balance
    FROM installment_contracts 
    WHERE customer_id = ? AND status != 'cancelled'", [$id]);

// Overdue details
$overdueSum = (float)(DB::fetch("SELECT COALESCE(SUM(s.remaining_amount + s.late_fee), 0) as s
    FROM installment_schedules s 
    JOIN installment_contracts c ON s.contract_id = c.id
    WHERE c.customer_id = ? AND s.status = 'overdue'", [$id])['s'] ?? 0);

// Next upcoming installment
$nextDue = DB::fetch("SELECT s.*, c.contract_number 
    FROM installment_schedules s 
    JOIN installment_contracts c ON s.contract_id = c.id 
    WHERE c.customer_id = ? AND s.status IN ('pending', 'partial') 
    ORDER BY s.due_date ASC LIMIT 1", [$id]);

// Contracts
$contracts = DB::fetchAll("SELECT * FROM installment_contracts WHERE customer_id = ? ORDER BY id DESC", [$id]);

// Guarantors
$guarantors = DB::fetchAll("SELECT * FROM customer_guarantors WHERE customer_id = ? ORDER BY id ASC", [$id]);

// Recent Payments
$payments = DB::fetchAll("SELECT p.*, c.contract_number 
    FROM payments p 
    JOIN installment_contracts c ON p.contract_id = c.id 
    WHERE p.customer_id = ? AND p.is_reversed = 0 
    ORDER BY p.id DESC LIMIT 10", [$id]);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($customer['full_name']) ?></h4>
            <span class="badge bg-primary text-uppercase"><?= str_replace('_', ' ', $customer['category']) ?></span>
            <span class="badge bg-light text-dark border"><?= htmlspecialchars($customer['customer_code']) ?></span>
        </div>
        <p class="text-muted small mb-0">Registered on <?= format_date($customer['created_at']) ?> &bull; <?= htmlspecialchars($customer['city']) ?></p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/collection/receive.php?customer_id=<?= $customer['id'] ?>" class="btn btn-success btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Receive Payment
        </a>
        <a href="<?= BASE_URL ?>/contracts/create.php?customer_id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-file-circle-plus me-1"></i> New Contract
        </a>
        <a href="<?= BASE_URL ?>/customers/ledger.php?id=<?= $customer['id'] ?>" class="btn btn-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-file-lines me-1"></i> Customer Ledger
        </a>
        <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
        </a>
    </div>
</div>

<!-- 360 Financial KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Purchases Value</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($stats['total_purchases']) ?></div>
            <small class="text-muted"><?= $stats['total_contracts'] ?> Total Contract(s)</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Down Payments</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($stats['total_down_payment']) ?></div>
            <small class="text-muted">Initial Cash Paid</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-semibold">Total Cash Received</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($stats['total_payments_received']) ?></div>
            <small class="text-muted">Down Payment + Installments</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-danger">
            <div class="text-muted small fw-semibold">Remaining Balance</div>
            <div class="h4 fw-bold text-danger mt-1 mb-0"><?= format_currency($stats['total_remaining_balance']) ?></div>
            <?php if ($overdueSum > 0): ?>
                <small class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue: <?= format_currency($overdueSum) ?></small>
            <?php else: ?>
                <small class="text-success"><i class="fa-solid fa-circle-check me-1"></i>No overdue payments</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Profile Details & Guarantor -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-address-card me-2"></i>Contact & Identification Details</h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:35%;">Father / Husband:</td><td class="fw-semibold"><?= htmlspecialchars($customer['father_husband_name'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">Urdu Name:</td><td class="fw-semibold"><?= htmlspecialchars($customer['urdu_name'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">CNIC #:</td><td class="fw-bold text-primary"><?= htmlspecialchars($customer['cnic']) ?></td></tr>
                    <tr>
                        <td class="text-muted">Mobile / Phone:</td>
                        <td>
                            <strong><?= htmlspecialchars($customer['phone']) ?></strong>
                            <?php if ($customer['whatsapp']): ?>
                            <a href="<?= whatsapp_url($customer['whatsapp'], "Assalam-o-Alaikum " . $customer['full_name']) ?>" target="_blank" class="btn btn-sm btn-outline-success ms-2 py-0 px-2" title="Chat on WhatsApp">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><td class="text-muted">Current Address:</td><td><?= htmlspecialchars($customer['current_address']) ?></td></tr>
                    <tr><td class="text-muted">City:</td><td><?= htmlspecialchars($customer['city']) ?></td></tr>
                    <tr><td class="text-muted">Occupation:</td><td><?= htmlspecialchars($customer['occupation'] ?: '-') ?> (<?= htmlspecialchars($customer['employer_name'] ?: '-') ?>)</td></tr>
                    <tr><td class="text-muted">Credit Limit:</td><td class="fw-semibold"><?= format_currency($customer['credit_limit']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Guarantor Information -->
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-user-shield me-2"></i>Guarantors & References (ضامن)</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($guarantors)): ?>
                    <p class="text-muted small py-3 mb-0">No guarantors attached to this customer yet.</p>
                <?php else: ?>
                    <?php foreach ($guarantors as $g): ?>
                    <div class="p-3 bg-light rounded-3 mb-2 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($g['full_name']) ?></h6>
                            <span class="badge bg-secondary"><?= htmlspecialchars($g['relationship']) ?></span>
                        </div>
                        <div class="small mt-2 text-muted">
                            <div><i class="fa-regular fa-id-card me-1"></i> CNIC: <strong><?= htmlspecialchars($g['cnic'] ?: '-') ?></strong></div>
                            <div><i class="fa-solid fa-phone me-1"></i> Phone: <strong><?= htmlspecialchars($g['phone']) ?></strong></div>
                            <div><i class="fa-solid fa-location-dot me-1"></i> Address: <?= htmlspecialchars($g['address']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($nextDue): ?>
                <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                    <i class="fa-solid fa-bell me-1"></i> <strong>Next Installment Due:</strong> 
                    <?= format_currency($nextDue['remaining_amount']) ?> on <strong><?= format_date($nextDue['due_date']) ?></strong> (Contract: <?= htmlspecialchars($nextDue['contract_number']) ?>)
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Installment Contracts Tab -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-file-contract text-primary me-2"></i>Installment Contracts & Purchases</h6>
        <a href="<?= BASE_URL ?>/contracts/create.php?customer_id=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">+ New Contract</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Contract #</th>
                    <th>Date</th>
                    <th>Installment Price</th>
                    <th>Down Payment</th>
                    <th>Total Paid</th>
                    <th>Balance</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="9" class="text-center py-3 text-muted">No contracts on record.</td></tr>
                <?php else: ?>
                    <?php foreach ($contracts as $ct): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $ct['id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($ct['contract_number']) ?>
                            </a>
                        </td>
                        <td><?= format_date($ct['sale_date']) ?></td>
                        <td class="fw-semibold"><?= format_currency($ct['installment_price_total']) ?></td>
                        <td><?= format_currency($ct['down_payment']) ?></td>
                        <td class="text-success fw-semibold"><?= format_currency($ct['total_paid']) ?></td>
                        <td class="text-danger fw-bold"><?= format_currency($ct['balance_amount']) ?></td>
                        <td><?= $ct['installment_count'] ?> Months</td>
                        <td><span class="badge-status status-<?= $ct['status'] ?>"><?= ucfirst($ct['status']) ?></span></td>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $ct['id'] ?>" class="btn btn-sm btn-light border" title="View Schedule">
                                <i class="fa-solid fa-eye text-primary"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/contracts/print_contract.php?id=<?= $ct['id'] ?>" target="_blank" class="btn btn-sm btn-light border" title="Print Agreement">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Collections from Customer -->
<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-receipt text-success me-2"></i>Recent Payment Receipts</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Payment Date</th>
                    <th>Contract #</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Remarks</th>
                    <th>Print</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" class="text-center py-3 text-muted">No payment receipts found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($p['receipt_number']) ?></td>
                        <td><?= format_date($p['payment_date']) ?></td>
                        <td><?= htmlspecialchars($p['contract_number']) ?></td>
                        <td class="text-success fw-bold"><?= format_currency($p['amount']) ?></td>
                        <td><span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $p['payment_method']) ?></span></td>
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
