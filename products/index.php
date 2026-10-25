<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);

$sql = "SELECT p.*, c.name as category_name, c.urdu_name as category_urdu, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.product_code LIKE ? OR p.barcode LIKE ? OR p.model LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}
if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}
$sql .= " ORDER BY p.id DESC";
$products = DB::fetchAll($sql, $params);

$categories = DB::fetchAll("SELECT * FROM categories ORDER BY name ASC");
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i><?= __('product_list') ?></h4>
        <p class="text-muted small mb-0">Manage inventory items, pricing tiers (Cash vs Installment), and stock levels</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/products/create.php" class="btn btn-primary fw-semibold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> <?= __('new_product') ?>
        </a>
        <a href="<?= BASE_URL ?>/products/categories.php" class="btn btn-light border fw-semibold shadow-sm">
            <i class="fa-solid fa-tags me-1"></i> <?= __('categories') ?>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border rounded-3 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-barcode text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, code, barcode, or model..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Product / Model</th>
                    <th>Category & Brand</th>
                    <th>Purchase Cost</th>
                    <th>Cash Price</th>
                    <th>Installment Price</th>
                    <th>Stock Qty</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="small text-muted">
                                <?php if ($p['urdu_name']): ?><span class="me-2"><?= htmlspecialchars($p['urdu_name']) ?></span><?php endif; ?>
                                <code><?= htmlspecialchars($p['product_code']) ?></code>
                                <?php if ($p['model']): ?>&bull; Model: <?= htmlspecialchars($p['model']) ?><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name'] ?: 'General') ?></span></div>
                            <small class="text-muted"><?= htmlspecialchars($p['brand_name'] ?: '-') ?></small>
                        </td>
                        <td><?= format_currency($p['purchase_cost']) ?></td>
                        <td><?= format_currency($p['cash_price']) ?></td>
                        <td class="fw-bold text-primary"><?= format_currency($p['installment_price']) ?></td>
                        <td>
                            <?php if ($p['stock_quantity'] <= $p['min_stock_alert']): ?>
                                <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= $p['stock_quantity'] ?> Low</span>
                            <?php else: ?>
                                <span class="badge bg-success"><?= $p['stock_quantity'] ?> in stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($p['status']) ?></span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/products/edit.php?id=<?= $p['id'] ?>"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit Product</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/contracts/create.php?product_id=<?= $p['id'] ?>"><i class="fa-solid fa-file-circle-plus me-2 text-success"></i>Sell on Installment</a></li>
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
