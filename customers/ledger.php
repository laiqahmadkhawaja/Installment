<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$customer = DB::fetch("SELECT * FROM customers WHERE id = ?", [$id]);

if (!$customer) {
    set_flash('danger', 'Customer not found.');
    header("Location: " . BASE_URL . "/customers/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$contractId = (int)($_GET['contract_id'] ?? 0);

$sql = "SELECT * FROM customer_ledgers WHERE customer_id = ?";
$params = [$id];

if ($contractId > 0) {
    $sql .= " AND contract_id = ?";
    $params[] = $contractId;
}
if (!empty($startDate)) {
    $sql .= " AND transaction_date >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $sql .= " AND transaction_date <= ?";
    $params[] = $endDate;
}
$sql .= " ORDER BY transaction_date ASC, id ASC";

$ledgerEntries = DB::fetchAll($sql, $params);

// Calculate Totals
$totalDebit = 0;
$totalCredit = 0;
foreach ($ledgerEntries as $entry) {
    $totalDebit += (float)$entry['debit'];
    $totalCredit += (float)$entry['credit'];
}
$closingBalance = $totalDebit - $totalCredit;

// Available contracts for filter dropdown
$contracts = DB::fetchAll("SELECT id, contract_number FROM installment_contracts WHERE customer_id = ?", [$id]);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i><?= __('customer_ledger') ?></h4>
        <p class="text-muted small mb-0">Customer: <strong><?= htmlspecialchars($customer['full_name']) ?></strong> (<?= htmlspecialchars($customer['customer_code']) ?>) &bull; Phone: <?= htmlspecialchars($customer['phone']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> <?= __('print_statement') ?>
        </button>
        <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Profile
        </a>
    </div>
</div>

<!-- Ledger Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Filter Contract</label>
                <select name="contract_id" class="form-select form-select-sm">
                    <option value="">All Contracts</option>
                    <?php foreach ($contracts as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $contractId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['contract_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/customers/ledger.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Printable Statement Header -->
<div class="card border rounded-3 shadow-sm p-4 mb-4">
    <div class="text-center mb-4">
        <h3 class="fw-bold mb-1"><?= htmlspecialchars(get_setting('company_name', 'Installment Management System')) ?></h3>
        <div class="text-muted small"><?= htmlspecialchars(get_setting('company_address')) ?> &bull; Phone: <?= htmlspecialchars(get_setting('company_phone')) ?></div>
        <h5 class="fw-bold text-uppercase mt-3 mb-0 border-top border-bottom py-2 d-inline-block px-4">Customer Account Statement / کسٹمر کھاتہ</h5>
    </div>

    <div class="row g-3 mb-4 small">
        <div class="col-sm-6">
            <div><strong>Customer Name:</strong> <?= htmlspecialchars($customer['full_name']) ?> (<?= htmlspecialchars($customer['urdu_name'] ?: '-') ?>)</div>
            <div><strong>CNIC No:</strong> <?= htmlspecialchars($customer['cnic']) ?></div>
            <div><strong>Mobile Phone:</strong> <?= htmlspecialchars($customer['phone']) ?></div>
            <div><strong>Address:</strong> <?= htmlspecialchars($customer['current_address']) ?></div>
        </div>
        <div class="col-sm-6 text-sm-end">
            <div><strong>Customer ID:</strong> <?= htmlspecialchars($customer['customer_code']) ?></div>
            <div><strong>Statement Date:</strong> <?= date('d M Y h:i A') ?></div>
            <div><strong>Total Debit:</strong> <?= format_currency($totalDebit) ?></div>
            <div><strong>Total Credit (Paid):</strong> <span class="text-success fw-bold"><?= format_currency($totalCredit) ?></span></div>
            <div class="h6 mt-1"><strong>Closing Balance:</strong> <span class="text-danger fw-bold"><?= format_currency($closingBalance) ?></span></div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 table-custom small">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th style="width:130px;">Ref / Doc #</th>
                    <th>Transaction Details</th>
                    <th class="text-end" style="width:130px;">Debit (بنام)</th>
                    <th class="text-end" style="width:130px;">Credit (جمع)</th>
                    <th class="text-end" style="width:140px;">Balance (بقایا)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ledgerEntries)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No ledger transactions found for this period.</td></tr>
                <?php else: ?>
                    <?php 
                    $runningBalance = 0;
                    foreach ($ledgerEntries as $entry): 
                        $runningBalance += ((float)$entry['debit'] - (float)$entry['credit']);
                    ?>
                    <tr>
                        <td><?= format_date($entry['transaction_date']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($entry['reference_no'] ?: '-') ?></span></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($entry['description']) ?></div>
                            <small class="text-muted text-uppercase"><?= str_replace('_', ' ', $entry['transaction_type']) ?></small>
                        </td>
                        <td class="text-end fw-semibold <?= $entry['debit'] > 0 ? 'text-dark' : 'text-muted' ?>">
                            <?= $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' ?>
                        </td>
                        <td class="text-end fw-semibold <?= $entry['credit'] > 0 ? 'text-success' : 'text-muted' ?>">
                            <?= $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' ?>
                        </td>
                        <td class="text-end fw-bold <?= $runningBalance > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($runningBalance, 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end text-uppercase">Totals:</td>
                    <td class="text-end"><?= format_currency($totalDebit) ?></td>
                    <td class="text-end text-success"><?= format_currency($totalCredit) ?></td>
                    <td class="text-end text-danger"><?= format_currency($closingBalance) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-5 pt-4 text-muted small border-top">
        <div>Customer Signature: _______________________</div>
        <div>Authorized Stamp & Signature: _______________________</div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
