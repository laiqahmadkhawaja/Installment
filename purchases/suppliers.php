<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

// Add Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $openingBal = (float)($_POST['opening_balance'] ?? 0);

    if (!empty($name) && !empty($phone)) {
        DB::insert('suppliers', [
            'name' => $name,
            'company_name' => $company,
            'phone' => $phone,
            'address' => $address,
            'opening_balance' => $openingBal,
            'current_balance' => $openingBal
        ]);
        set_flash('success', "Supplier <strong>{$name}</strong> added successfully.");
        header("Location: " . BASE_URL . "/purchases/suppliers.php");
        exit;
    }
}

$suppliers = DB::fetchAll("SELECT * FROM suppliers ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-truck-field text-primary me-2"></i><?= __('supplier_list') ?></h4>
        <p class="text-muted small mb-0">Manage wholesale distributors, suppliers, and vendor payable balances</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newSupplierModal">
            <i class="fa-solid fa-plus me-1"></i> Add Supplier
        </button>
        <a href="<?= BASE_URL ?>/purchases/create.php" class="btn btn-dark fw-semibold shadow-sm">
            <i class="fa-solid fa-cart-flatbed me-1"></i> Stock-in Purchase
        </a>
    </div>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Company / Distribution</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Current Balance (Payable)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No suppliers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['company_name'] ?: '-') ?></td>
                        <td><i class="fa-solid fa-phone text-muted me-1"></i><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['address'] ?: '-') ?></td>
                        <td class="fw-bold text-danger"><?= format_currency($s['current_balance']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="newSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="add_supplier" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register New Supplier / Distributor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Person Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Sheikh Naveed">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Company / Wholesale Firm</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. Haier Electronics Lahore">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" required placeholder="0300-1234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Office Address / Market</label>
                        <input type="text" name="address" class="form-control" placeholder="e.g. Hall Road, Lahore">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Opening Balance Payable (Rs.)</label>
                        <input type="number" name="opening_balance" class="form-control" value="0" step="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
