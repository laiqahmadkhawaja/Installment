<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$error = '';
$success = '';

// Process New Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($title) || $amount <= 0) {
        $error = "Please provide an expense title and valid amount.";
    } else {
        DB::beginTransaction();
        try {
            $expenseId = DB::insert('expenses', [
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'expense_date' => $expenseDate,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'title' => $title,
                'description' => $desc,
                'paid_by' => $_SESSION['user_id'] ?? 1
            ]);

            // Insert cashbook outflow
            DB::insert('cashbook', [
                'branch_id' => $branchId,
                'entry_date' => $expenseDate,
                'account_type' => $paymentMethod === 'cash' ? 'cash' : 'bank',
                'type' => 'outflow',
                'source' => 'expense',
                'reference_no' => 'EXP-' . $expenseId,
                'amount' => $amount,
                'description' => "Expense: {$title} ({$desc})",
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            DB::commit();
            audit_log('EXPENSE_ADDED', 'Accounting', $expenseId, "Added expense: {$title} (Rs. {$amount})");
            set_flash('success', "Expense <strong>{$title}</strong> recorded successfully.");
            header("Location: " . BASE_URL . "/accounting/expenses.php");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error saving expense: " . $e->getMessage();
        }
    }
}

// Fetch categories & expenses
$categories = DB::fetchAll("SELECT * FROM expense_categories ORDER BY name ASC");

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$catFilter = (int)($_GET['category_id'] ?? 0);

$sql = "SELECT e.*, c.name as category_name, c.urdu_name as category_urdu, u.full_name as paid_by_name 
    FROM expenses e 
    JOIN expense_categories c ON e.category_id = c.id 
    LEFT JOIN users u ON e.paid_by = u.id 
    WHERE e.branch_id = ? AND e.expense_date >= ? AND e.expense_date <= ?";

$params = [$branchId, $startDate, $endDate];
if ($catFilter > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY e.expense_date DESC, e.id DESC";

$expenses = DB::fetchAll($sql, $params);

$totalExpenseAmount = 0;
foreach ($expenses as $ex) {
    $totalExpenseAmount += (float)$ex['amount'];
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-money-bill-transfer text-primary me-2"></i><?= __('expenses') ?></h4>
        <p class="text-muted small mb-0">Record shop rent, staff salaries, electricity bills, recovery fuel, and tea expenses</p>
    </div>
    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newExpenseModal">
        <i class="fa-solid fa-plus me-1"></i> Add Expense
    </button>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Date Filter Form -->
<div class="card border rounded-3 shadow-sm mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/accounting/expenses.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Expenses Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Expense Transactions (<?= format_date($startDate) ?> to <?= format_date($endDate) ?>)</h6>
        <span class="badge bg-danger fs-6"><?= format_currency($totalExpenseAmount) ?> Total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Expense Title</th>
                    <th>Details / Notes</th>
                    <th>Payment Method</th>
                    <th>Paid By</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No expenses recorded for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $ex): ?>
                    <tr>
                        <td><?= format_date($ex['expense_date']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($ex['category_name']) ?></span>
                            <?php if ($ex['category_urdu']): ?><small class="d-block text-muted"><?= htmlspecialchars($ex['category_urdu']) ?></small><?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($ex['title']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($ex['description'] ?: '-') ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= $ex['payment_method'] ?></span></td>
                        <td><?= htmlspecialchars($ex['paid_by_name'] ?? 'Admin') ?></td>
                        <td class="text-end fw-bold text-danger"><?= format_currency($ex['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="6" class="text-end text-uppercase">Total Period Expenses:</td>
                    <td class="text-end text-danger h6 mb-0"><?= format_currency($totalExpenseAmount) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="newExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Business Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Expense Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['urdu_name'] ?: '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Expense Title / Payee <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. October Shop Rent, Electricity Bill, Tea">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control fw-bold" required step="50" placeholder="e.g. 5000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Paid Through</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="bank">Bank Account / Transfer</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Additional Details</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Cheque number, bill reference, vendor note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
