<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$filterDate = $_GET['date'] ?? date('Y-m-d');
$accountType = $_GET['account_type'] ?? '';

// Manual Cashbook Entry Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_entry') {
    $entryType = $_POST['type'] ?? 'inflow';
    $source = $_POST['source'] ?? 'adjustment';
    $accType = $_POST['account_type'] ?? 'cash';
    $amount = (float)($_POST['amount'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    if ($amount > 0 && !empty($desc)) {
        DB::insert('cashbook', [
            'branch_id' => $branchId,
            'entry_date' => $filterDate,
            'account_type' => $accType,
            'type' => $entryType,
            'source' => $source,
            'reference_no' => 'MAN-' . rand(1000, 9999),
            'amount' => $amount,
            'description' => $desc,
            'created_by' => $_SESSION['user_id'] ?? 1
        ]);
        audit_log('CASHBOOK_ENTRY', 'Accounting', null, "Manual {$entryType} of Rs. {$amount} ({$desc})");
        set_flash('success', "Cashbook entry recorded successfully.");
        header("Location: " . BASE_URL . "/accounting/cashbook.php?date={$filterDate}");
        exit;
    }
}

// Cashbook entries for selected date
$sql = "SELECT * FROM cashbook WHERE branch_id = ? AND entry_date = ?";
$params = [$branchId, $filterDate];
if (!empty($accountType)) {
    $sql .= " AND account_type = ?";
    $params[] = $accountType;
}
$sql .= " ORDER BY id DESC";

$entries = DB::fetchAll($sql, $params);

// Calculate Totals for Date
$dayInflow = 0;
$dayOutflow = 0;
foreach ($entries as $e) {
    if ($e['type'] === 'inflow') $dayInflow += (float)$e['amount'];
    else $dayOutflow += (float)$e['amount'];
}
$dayNet = $dayInflow - $dayOutflow;

// Overall Balances (All time up to date)
$cashBalance = (float)(DB::fetch("SELECT 
    COALESCE(SUM(CASE WHEN type='inflow' THEN amount ELSE -amount END), 0) as bal 
    FROM cashbook WHERE branch_id = ? AND account_type = 'cash' AND entry_date <= ?", [$branchId, $filterDate])['bal'] ?? 0);

$bankBalance = (float)(DB::fetch("SELECT 
    COALESCE(SUM(CASE WHEN type='inflow' THEN amount ELSE -amount END), 0) as bal 
    FROM cashbook WHERE branch_id = ? AND account_type = 'bank' AND entry_date <= ?", [$branchId, $filterDate])['bal'] ?? 0);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-book-journal-whills text-primary me-2"></i><?= __('cashbook') ?></h4>
        <p class="text-muted small mb-0">Daily cash control, bank transactions, and payment method reconciliation</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Cashbook
        </button>
        <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="fa-solid fa-plus me-1"></i> Manual Entry / Transfer
        </button>
    </div>
</div>

<!-- Balance Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-success">
            <div class="text-muted small fw-semibold">Total Cash in Hand</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($cashBalance) ?></div>
            <small class="text-muted">Physical Drawer Cash</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm border-start border-4 border-primary">
            <div class="text-muted small fw-semibold">Bank & Digital Accounts</div>
            <div class="h4 fw-bold text-primary mt-1 mb-0"><?= format_currency($bankBalance) ?></div>
            <small class="text-muted">Bank / JazzCash / Mobile</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Today Inflow (آمد)</div>
            <div class="h4 fw-bold text-success mt-1 mb-0"><?= format_currency($dayInflow) ?></div>
            <small class="text-muted">Collections & DP</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border rounded-3 bg-body shadow-sm">
            <div class="text-muted small fw-semibold">Today Outflow (خرچ)</div>
            <div class="h4 fw-bold text-danger mt-1 mb-0"><?= format_currency($dayOutflow) ?></div>
            <small class="text-muted">Expenses & Payouts</small>
        </div>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Select Cashbook Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Account Filter</label>
                <select name="account_type" class="form-select">
                    <option value="">All Accounts (Cash + Bank)</option>
                    <option value="cash" <?= $accountType === 'cash' ? 'selected' : '' ?>>Cash in Hand only</option>
                    <option value="bank" <?= $accountType === 'bank' ? 'selected' : '' ?>>Bank & Digital Accounts only</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/accounting/cashbook.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Cashbook Entries Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Daily Cashbook Transactions for <?= format_date($filterDate) ?></h6>
        <span class="badge bg-light text-dark border"><?= count($entries) ?> Entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Time / Date</th>
                    <th>Account</th>
                    <th>Source / Category</th>
                    <th>Ref #</th>
                    <th>Description</th>
                    <th class="text-end">Cash Inflow (آمد)</th>
                    <th class="text-end">Cash Outflow (خرچ)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No cashbook entries on this date.</td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td class="small text-muted"><?= format_date($e['entry_date']) ?></td>
                        <td>
                            <span class="badge bg-<?= $e['account_type'] === 'cash' ? 'success' : 'primary' ?> text-uppercase">
                                <?= $e['account_type'] ?>
                            </span>
                        </td>
                        <td class="fw-semibold text-uppercase small"><?= str_replace('_', ' ', $e['source']) ?></td>
                        <td><code><?= htmlspecialchars($e['reference_no'] ?: '-') ?></code></td>
                        <td><?= htmlspecialchars($e['description']) ?></td>
                        <td class="text-end fw-bold text-success">
                            <?= $e['type'] === 'inflow' ? format_currency($e['amount']) : '-' ?>
                        </td>
                        <td class="text-end fw-bold text-danger">
                            <?= $e['type'] === 'outflow' ? format_currency($e['amount']) : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="5" class="text-end text-uppercase">Day Total:</td>
                    <td class="text-end text-success"><?= format_currency($dayInflow) ?></td>
                    <td class="text-end text-danger"><?= format_currency($dayOutflow) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="manual_entry">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Cashbook Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Entry Type</label>
                        <select name="type" class="form-select">
                            <option value="inflow">Inflow (Cash Coming In)</option>
                            <option value="outflow">Outflow (Cash Going Out)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account</label>
                        <select name="account_type" class="form-select">
                            <option value="cash">Cash in Hand</option>
                            <option value="bank">Bank Account</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Source / Nature</label>
                        <select name="source" class="form-select">
                            <option value="deposit">Bank Deposit</option>
                            <option value="withdrawal">Bank Withdrawal</option>
                            <option value="adjustment">Balance Adjustment</option>
                            <option value="owner_capital">Owner Capital Inflow</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required step="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="2" required placeholder="Reason for transaction..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Cashbook Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
