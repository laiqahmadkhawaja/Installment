<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$agingFilter = trim($_GET['aging'] ?? '');

// Auto detect overdue schedules and mark overdue
DB::query("UPDATE installment_schedules SET status = 'overdue' WHERE status = 'pending' AND due_date < CURDATE()");

$sql = "SELECT s.*, 
    c.contract_number, c.customer_id, c.balance_amount as total_contract_balance,
    cust.full_name as customer_name, cust.urdu_name as customer_urdu, cust.phone, cust.whatsapp, cust.current_address, cust.category as customer_category, cust.customer_code,
    g.full_name as guarantor_name, g.phone as guarantor_phone,
    DATEDIFF(CURDATE(), s.due_date) as days_overdue
    FROM installment_schedules s
    JOIN installment_contracts c ON s.contract_id = c.id
    JOIN customers cust ON c.customer_id = cust.id
    LEFT JOIN customer_guarantors g ON cust.id = g.customer_id
    WHERE c.branch_id = ? AND s.status = 'overdue' AND c.status IN ('active', 'overdue', 'defaulted')";

$params = [$branchId];

if ($agingFilter === '1_7') {
    $sql .= " AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 1 AND 7";
} elseif ($agingFilter === '8_30') {
    $sql .= " AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 8 AND 30";
} elseif ($agingFilter === '31_60') {
    $sql .= " AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 31 AND 60";
} elseif ($agingFilter === '61_90') {
    $sql .= " AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 61 AND 90";
} elseif ($agingFilter === '90_plus') {
    $sql .= " AND DATEDIFF(CURDATE(), s.due_date) > 90";
}

$sql .= " GROUP BY s.id ORDER BY s.due_date ASC";

$overdueList = DB::fetchAll($sql, $params);

// Calculate Aging Buckets for KPIs
$aging1_7 = (float)(DB::fetch("SELECT COALESCE(SUM(remaining_amount + late_fee), 0) as s FROM installment_schedules s JOIN installment_contracts c ON s.contract_id = c.id WHERE c.branch_id = ? AND s.status = 'overdue' AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 1 AND 7", [$branchId])['s'] ?? 0);
$aging8_30 = (float)(DB::fetch("SELECT COALESCE(SUM(remaining_amount + late_fee), 0) as s FROM installment_schedules s JOIN installment_contracts c ON s.contract_id = c.id WHERE c.branch_id = ? AND s.status = 'overdue' AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 8 AND 30", [$branchId])['s'] ?? 0);
$aging31_60 = (float)(DB::fetch("SELECT COALESCE(SUM(remaining_amount + late_fee), 0) as s FROM installment_schedules s JOIN installment_contracts c ON s.contract_id = c.id WHERE c.branch_id = ? AND s.status = 'overdue' AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 31 AND 60", [$branchId])['s'] ?? 0);
$aging61_90 = (float)(DB::fetch("SELECT COALESCE(SUM(remaining_amount + late_fee), 0) as s FROM installment_schedules s JOIN installment_contracts c ON s.contract_id = c.id WHERE c.branch_id = ? AND s.status = 'overdue' AND DATEDIFF(CURDATE(), s.due_date) BETWEEN 61 AND 90", [$branchId])['s'] ?? 0);
$aging90Plus = (float)(DB::fetch("SELECT COALESCE(SUM(remaining_amount + late_fee), 0) as s FROM installment_schedules s JOIN installment_contracts c ON s.contract_id = c.id WHERE c.branch_id = ? AND s.status = 'overdue' AND DATEDIFF(CURDATE(), s.due_date) > 90", [$branchId])['s'] ?? 0);

$totalOverdueSum = $aging1_7 + $aging8_30 + $aging31_60 + $aging61_90 + $aging90Plus;
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= __('defaulters') ?> & Overdue Recovery</h4>
        <p class="text-muted small mb-0">Aging categorization, promise-to-pay records, field recovery, and WhatsApp reminders</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/defaulters/followups.php" class="btn btn-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-phone-volume me-1"></i> <?= __('followups') ?>
        </a>
    </div>
</div>

<!-- Aging Category Filter Tabs / Cards -->
<div class="row g-2 mb-4">
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= empty($agingFilter) ? 'border-primary bg-primary bg-opacity-10' : 'bg-body' ?>">
            <div class="small fw-semibold text-muted">All Overdue</div>
            <div class="h5 fw-bold text-dark mt-1 mb-0"><?= format_currency($totalOverdueSum) ?></div>
        </a>
    </div>
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php?aging=1_7" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= $agingFilter === '1_7' ? 'border-warning bg-warning bg-opacity-10' : 'bg-body' ?>">
            <div class="small fw-semibold text-muted">1–7 Days</div>
            <div class="h5 fw-bold text-warning mt-1 mb-0"><?= format_currency($aging1_7) ?></div>
        </a>
    </div>
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php?aging=8_30" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= $agingFilter === '8_30' ? 'border-danger bg-danger bg-opacity-10' : 'bg-body' ?>">
            <div class="small fw-semibold text-muted">8–30 Days</div>
            <div class="h5 fw-bold text-danger mt-1 mb-0"><?= format_currency($aging8_30) ?></div>
        </a>
    </div>
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php?aging=31_60" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= $agingFilter === '31_60' ? 'border-danger bg-danger bg-opacity-10' : 'bg-body' ?>">
            <div class="small fw-semibold text-muted">31–60 Days</div>
            <div class="h5 fw-bold text-danger mt-1 mb-0"><?= format_currency($aging31_60) ?></div>
        </a>
    </div>
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php?aging=61_90" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= $agingFilter === '61_90' ? 'border-danger bg-danger bg-opacity-10' : 'bg-body' ?>">
            <div class="small fw-semibold text-muted">61–90 Days</div>
            <div class="h5 fw-bold text-danger mt-1 mb-0"><?= format_currency($aging61_90) ?></div>
        </a>
    </div>
    <div class="col">
        <a href="<?= BASE_URL ?>/defaulters/index.php?aging=90_plus" class="card p-3 border rounded-3 text-decoration-none shadow-sm <?= $agingFilter === '90_plus' ? 'border-dark bg-dark text-white' : 'bg-body' ?>">
            <div class="small fw-semibold <?= $agingFilter === '90_plus' ? 'text-white-50' : 'text-muted' ?>">90+ Days (Severe)</div>
            <div class="h5 fw-bold <?= $agingFilter === '90_plus' ? 'text-white' : 'text-danger' ?> mt-1 mb-0"><?= format_currency($aging90Plus) ?></div>
        </a>
    </div>
</div>

<!-- Overdue Installments Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-list-ul me-2"></i>Overdue Customers & Installments List</h6>
        <span class="badge bg-danger"><?= count($overdueList) ?> Overdue Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Contract #</th>
                    <th>Due Date</th>
                    <th>Overdue Days</th>
                    <th>Installment Amount</th>
                    <th>Guarantor</th>
                    <th>Recovery Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($overdueList)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-success"><i class="fa-solid fa-check-circle fa-2x mb-2 d-block"></i>No overdue installments in this aging category.</td></tr>
                <?php else: ?>
                    <?php foreach ($overdueList as $o): ?>
                    <?php
                    $dueAmount = (float)$o['remaining_amount'] + (float)$o['late_fee'];
                    $waText = "محترم جناب " . $o['customer_name'] . " صاحب!\nآپ کا اقساط معاہدہ نمبر " . $o['contract_number'] . " کی قسط بمبلغ " . format_currency($dueAmount) . " گزشتہ " . $o['days_overdue'] . " دن سے واجب الادا ہے۔ برائے مہربانی فوری ادائیگی فرما کر رسید حاصل کریں۔ شکریہ!\n" . get_setting('company_name_urdu');
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold">
                                <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $o['customer_id'] ?>" class="text-decoration-none text-body">
                                    <?= htmlspecialchars($o['customer_name']) ?>
                                </a>
                            </div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($o['phone']) ?></small>
                            <div><span class="badge bg-light text-dark border text-uppercase" style="font-size:0.7rem;"><?= $o['customer_category'] ?></span></div>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $o['contract_id'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($o['contract_number']) ?>
                            </a>
                            <div class="small text-muted">Inst #<?= $o['installment_number'] ?></div>
                        </td>
                        <td>
                            <div class="text-danger fw-semibold"><?= format_date($o['due_date']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-danger"><?= $o['days_overdue'] ?> Days Overdue</span>
                        </td>
                        <td>
                            <div class="fw-bold text-danger"><?= format_currency($dueAmount) ?></div>
                            <small class="text-muted">Total Bal: <?= format_currency($o['total_contract_balance']) ?></small>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= htmlspecialchars($o['guarantor_name'] ?: '-') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($o['guarantor_phone'] ?: '') ?></small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $o['contract_id'] ?>&schedule_id=<?= $o['id'] ?>&amount=<?= $dueAmount ?>" class="btn btn-sm btn-success" title="<?= __('receive_payment') ?>">
                                    <i class="fa-solid fa-dollar-sign"></i> Collect
                                </a>
                                <a href="<?= whatsapp_url($o['whatsapp'] ?: $o['phone'], $waText) ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Send WhatsApp Reminder in Urdu">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/defaulters/followups.php?contract_id=<?= $o['contract_id'] ?>" class="btn btn-sm btn-outline-dark" title="Record PTP / Field Visit">
                                    <i class="fa-solid fa-notes-medical"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
