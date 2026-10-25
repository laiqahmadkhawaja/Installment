<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT c.*, 
    COALESCE(SUM(con.balance_amount), 0) as total_balance,
    COUNT(con.id) as contract_count
    FROM customers c
    LEFT JOIN installment_contracts con ON c.id = con.customer_id AND con.status IN ('active', 'overdue')
    WHERE c.branch_id = ?";

$params = [$branchId];

if (!empty($search)) {
    $sql .= " AND (c.full_name LIKE ? OR c.cnic LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

if (!empty($category)) {
    $sql .= " AND c.category = ?";
    $params[] = $category;
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";
$customers = DB::fetchAll($sql, $params);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-users text-primary me-2"></i><?= __('customer_list') ?></h4>
        <p class="text-muted small mb-0">Manage customer records, eligibility, guarantors, and credit balances</p>
    </div>
    <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-primary fw-semibold shadow-sm">
        <i class="fa-solid fa-user-plus me-1"></i> <?= __('new_customer') ?>
    </a>
</div>

<!-- Search & Category Filters -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, CNIC, phone, or customer code..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">All Customer Categories</option>
                    <option value="new" <?= $category === 'new' ? 'selected' : '' ?>>New Customer</option>
                    <option value="regular" <?= $category === 'regular' ? 'selected' : '' ?>>Regular Customer</option>
                    <option value="premium" <?= $category === 'premium' ? 'selected' : '' ?>>Premium Customer</option>
                    <option value="high_risk" <?= $category === 'high_risk' ? 'selected' : '' ?>>High-Risk Customer</option>
                    <option value="defaulter" <?= $category === 'defaulter' ? 'selected' : '' ?>>Defaulter</option>
                    <option value="blacklisted" <?= $category === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> <?= __('filter') ?></button>
                <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>CNIC & Contact</th>
                    <th>City & Address</th>
                    <th>Category</th>
                    <th>Active Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-user-slash fa-2x mb-2 d-block text-secondary"></i>
                        No customers found matching your criteria.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                                    <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $c['id'] ?>" class="fw-bold text-decoration-none text-body">
                                        <?= htmlspecialchars($c['full_name']) ?>
                                    </a>
                                    <?php if (!empty($c['urdu_name'])): ?>
                                        <small class="d-block text-muted"><?= htmlspecialchars($c['urdu_name']) ?></small>
                                    <?php endif; ?>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($c['customer_code']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><i class="fa-regular fa-id-card text-muted me-1"></i><?= htmlspecialchars($c['cnic']) ?></div>
                            <div class="mt-1">
                                <a href="tel:<?= $c['phone'] ?>" class="text-decoration-none text-muted small me-2"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['phone']) ?></a>
                                <?php if (!empty($c['whatsapp'])): ?>
                                <a href="<?= whatsapp_url($c['whatsapp'], "Assalam-o-Alaikum " . $c['full_name'] . ", this message is from " . get_setting('company_name')) ?>" target="_blank" class="text-success small" title="WhatsApp Message">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= htmlspecialchars($c['city']) ?></div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;"><?= htmlspecialchars($c['current_address']) ?></small>
                        </td>
                        <td>
                            <?php
                            $catColors = [
                                'new' => 'info',
                                'regular' => 'success',
                                'premium' => 'primary',
                                'high_risk' => 'warning',
                                'defaulter' => 'danger',
                                'blacklisted' => 'dark'
                            ];
                            $color = $catColors[$c['category']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?> text-uppercase"><?= str_replace('_', ' ', $c['category']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold <?= $c['total_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= format_currency($c['total_balance']) ?>
                            </div>
                            <small class="text-muted"><?= $c['contract_count'] ?> Active Plan(s)</small>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/customers/view.php?id=<?= $c['id'] ?>"><i class="fa-solid fa-eye me-2 text-primary"></i>View 360&deg; Profile</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/customers/ledger.php?id=<?= $c['id'] ?>"><i class="fa-solid fa-file-invoice-dollar me-2 text-success"></i>Customer Ledger</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/contracts/create.php?customer_id=<?= $c['id'] ?>"><i class="fa-solid fa-file-circle-plus me-2 text-warning"></i>New Installment Sale</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/customers/edit.php?id=<?= $c['id'] ?>"><i class="fa-solid fa-pen-to-square me-2 text-muted"></i>Edit Customer</a></li>
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
