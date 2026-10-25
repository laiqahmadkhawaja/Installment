<?php
require_once __DIR__ . '/../includes/header.php';

// Add Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $code = trim($_POST['branch_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $urduName = trim($_POST['urdu_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Lahore');

    if (!empty($name) && !empty($code)) {
        DB::insert('branches', [
            'branch_code' => $code,
            'name' => $name,
            'urdu_name' => $urduName,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'is_active' => 1
        ]);
        set_flash('success', "Branch <strong>{$name}</strong> added successfully.");
        header("Location: " . BASE_URL . "/branches/index.php");
        exit;
    }
}

$branchList = DB::fetchAll("SELECT b.*, 
    COUNT(DISTINCT c.id) as customer_count,
    COUNT(DISTINCT con.id) as contract_count
    FROM branches b 
    LEFT JOIN customers c ON b.id = c.branch_id 
    LEFT JOIN installment_contracts con ON b.id = con.branch_id 
    GROUP BY b.id 
    ORDER BY b.id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-code-branch text-primary me-2"></i><?= __('branches') ?> Management</h4>
        <p class="text-muted small mb-0">Multi-branch network configuration, isolated branch inventories, and branch switching</p>
    </div>
    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newBranchModal">
        <i class="fa-solid fa-plus me-1"></i> Add New Branch
    </button>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Branch Code</th>
                    <th>Branch Name</th>
                    <th>Urdu Name</th>
                    <th>Phone</th>
                    <th>City & Address</th>
                    <th>Customers</th>
                    <th>Contracts</th>
                    <th>Current Active</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branchList as $b): ?>
                <tr class="<?= $b['id'] == current_branch_id() ? 'branch-active-row' : '' ?>">
                    <td><code><?= htmlspecialchars($b['branch_code']) ?></code></td>
                    <td class="fw-bold"><?= htmlspecialchars($b['name']) ?></td>
                    <td><?= htmlspecialchars($b['urdu_name'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($b['phone'] ?: '-') ?></td>
                    <td>
                        <div><?= htmlspecialchars($b['city']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($b['address'] ?: '-') ?></small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $b['customer_count'] ?></span></td>
                    <td><span class="badge bg-light text-dark border"><?= $b['contract_count'] ?></span></td>
                    <td>
                        <?php if ($b['id'] == current_branch_id()): ?>
                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Active</span>
                        <?php else: ?>
                            <span class="text-muted small">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($b['id'] != current_branch_id()): ?>
                        <a href="?switch_branch=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                            Switch to this branch
                        </a>
                        <?php else: ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="fa-solid fa-check me-1"></i>Current</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="newBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="add_branch" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Branch Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" name="branch_code" class="form-control" required placeholder="BR-03">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Rawalpindi Branch">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Urdu Name (اردو نام)</label>
                        <input type="text" name="urdu_name" class="form-control" placeholder="راولپنڈی برانچ">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="051-1234567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="Rawalpindi">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Physical Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Plaza / Street address..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
