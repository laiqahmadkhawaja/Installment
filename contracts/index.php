<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT c.*, cust.full_name as customer_name, cust.phone, cust.cnic, cust.customer_code 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.branch_id = ?";
$params = [$branchId];

if (!empty($status)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}
if (!empty($search)) {
    $sql .= " AND (c.contract_number LIKE ? OR cust.full_name LIKE ? OR cust.phone LIKE ? OR cust.cnic LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY c.id DESC";

$contracts = DB::fetchAll($sql, $params);

// Totals
$totals = DB::fetch("SELECT 
    COUNT(id) as total_count,
    COALESCE(SUM(total_payable), 0) as total_payable,
    COALESCE(SUM(total_paid), 0) as total_paid,
    COALESCE(SUM(balance_amount), 0) as total_balance
    FROM installment_contracts 
    WHERE branch_id = ? AND status != 'cancelled'", [$branchId]);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-contract text-primary me-2"></i><?= __('all_contracts') ?></h4>
        <p class="text-muted small mb-0">Master register of customer installment financing contracts and schedules</p>
    </div>
    <a href="<?= BASE_URL ?>/contracts/create.php" class="btn btn-primary fw-semibold shadow-sm">
        <i class="fa-solid fa-file-circle-plus me-1"></i> <?= __('new_contract') ?>
    </a>
</div>

<!-- Contract Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Contracts</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= number_format($totals['total_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Financed Value</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($totals['total_payable'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-semibold">Total Recovered Cash</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($totals['total_paid'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-danger">
            <div class="text-muted small fw-semibold">Outstanding Balance</div>
            <div class="h4 fw-bold text-danger mt-1 mb-0"><?= format_currency($totals['total_balance'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Search & Status Filter -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by contract #, customer name, phone, or CNIC..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Contract Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (جاری)</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed (مکمل)</option>
                    <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue (تاخیر شدہ)</option>
                    <option value="defaulted" <?= $status === 'defaulted' ? 'selected' : '' ?>>Defaulted (ڈیفالٹر)</option>
                    <option value="settled" <?= $status === 'settled' ? 'selected' : '' ?>>Settled (کلئیر شدہ)</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled (منسوخ)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/contracts/index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Contracts Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Contract #</th>
                    <th>Customer Details</th>
                    <th>Sale Date</th>
                    <th>Total Price</th>
                    <th>Down Payment</th>
                    <th>Total Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No contracts found matching your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $c['id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($c['contract_number']) ?>
                            </a>
                            <div class="small text-muted"><?= $c['installment_count'] ?> <?= ucfirst($c['frequency']) ?> installments</div>
                        </td>
                        <td>
                            <div class="fw-bold">
                                <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $c['customer_id'] ?>" class="text-decoration-none text-body">
                                    <?= htmlspecialchars($c['customer_name']) ?>
                                </a>
                            </div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['phone']) ?></small>
                        </td>
                        <td><?= format_date($c['sale_date']) ?></td>
                        <td class="fw-semibold"><?= format_currency($c['installment_price_total']) ?></td>
                        <td><?= format_currency($c['down_payment']) ?></td>
                        <td class="text-success fw-semibold"><?= format_currency($c['total_paid']) ?></td>
                        <td class="fw-bold <?= $c['balance_amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($c['balance_amount']) ?>
                        </td>
                        <td>
                            <span class="badge-status status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/contracts/view.php?id=<?= $c['id'] ?>"><i class="fa-solid fa-eye me-2 text-primary"></i>View Schedule</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/collection/receive.php?contract_id=<?= $c['id'] ?>"><i class="fa-solid fa-hand-holding-dollar me-2 text-success"></i>Collect Payment</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/contracts/print_contract.php?id=<?= $c['id'] ?>" target="_blank"><i class="fa-solid fa-print me-2 text-secondary"></i>Print Agreement</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/contracts/early_settlement.php?id=<?= $c['id'] ?>"><i class="fa-solid fa-calculator me-2 text-warning"></i>Early Settlement</a></li>
                                </ul>
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
