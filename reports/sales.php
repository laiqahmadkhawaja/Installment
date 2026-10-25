<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$startDate = $_GET['start_date'] ?? date('Y-01-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$sql = "SELECT c.*, 
    cust.full_name as customer_name, cust.phone, cust.cnic, cust.customer_code,
    u.full_name as salesperson_name 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    LEFT JOIN users u ON c.salesperson_id = u.id 
    WHERE c.branch_id = ? AND c.sale_date >= ? AND c.sale_date <= ? AND c.status != 'cancelled'
    ORDER BY c.sale_date DESC, c.id DESC";

$sales = DB::fetchAll($sql, [$branchId, $startDate, $endDate]);

$totalSales = 0;
$totalDP = 0;
$totalPaid = 0;
$totalBalance = 0;

foreach ($sales as $s) {
    $totalSales += (float)$s['total_payable'];
    $totalDP += (float)$s['down_payment'];
    $totalPaid += (float)$s['total_paid'];
    $totalBalance += (float)$s['balance_amount'];
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Installment Sales Report</h4>
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
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Total Financed Sales</div>
            <div class="h4 fw-bold text-dark mt-1 mb-0"><?= format_currency($totalSales) ?></div>
            <small class="text-muted"><?= count($sales) ?> Contracts</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Down Payments Received</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($totalDP) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-semibold">Total Recovered</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($totalPaid) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-danger">
            <div class="text-muted small fw-semibold">Remaining Balance</div>
            <div class="h4 fw-bold text-danger mt-1 mb-0"><?= format_currency($totalBalance) ?></div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Contract #</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>Salesperson</th>
                    <th class="text-end">Sale Value</th>
                    <th class="text-end">Down Payment</th>
                    <th class="text-end">Total Paid</th>
                    <th class="text-end">Remaining</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No installment sales in this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $s['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($s['contract_number']) ?>
                            </a>
                        </td>
                        <td><?= format_date($s['sale_date']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($s['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($s['phone']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($s['salesperson_name'] ?? 'Admin') ?></td>
                        <td class="text-end fw-semibold"><?= format_currency($s['total_payable']) ?></td>
                        <td class="text-end text-primary"><?= format_currency($s['down_payment']) ?></td>
                        <td class="text-end text-success fw-semibold"><?= format_currency($s['total_paid']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= format_currency($s['balance_amount']) ?></td>
                        <td><span class="badge-status status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end text-uppercase">Totals:</td>
                    <td class="text-end"><?= format_currency($totalSales) ?></td>
                    <td class="text-end text-primary"><?= format_currency($totalDP) ?></td>
                    <td class="text-end text-success"><?= format_currency($totalPaid) ?></td>
                    <td class="text-end text-danger"><?= format_currency($totalBalance) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
