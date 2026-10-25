<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $catName = trim($_POST['cat_name'] ?? '');
    $catUrdu = trim($_POST['cat_urdu'] ?? '');
    if (!empty($catName)) {
        DB::insert('categories', ['name' => $catName, 'urdu_name' => $catUrdu]);
        set_flash('success', 'Category added successfully.');
        header("Location: " . BASE_URL . "/products/categories.php");
        exit;
    }
}

// Add Brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brandName = trim($_POST['brand_name'] ?? '');
    if (!empty($brandName)) {
        DB::insert('brands', ['name' => $brandName]);
        set_flash('success', 'Brand added successfully.');
        header("Location: " . BASE_URL . "/products/categories.php");
        exit;
    }
}

$categories = DB::fetchAll("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id DESC");
$brands = DB::fetchAll("SELECT b.*, COUNT(p.id) as product_count FROM brands b LEFT JOIN products p ON b.id = p.brand_id GROUP BY b.id ORDER BY b.id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-tags text-primary me-2"></i><?= __('categories') ?> & Brands</h4>
        <p class="text-muted small mb-0">Organize catalog by product types (appliances, mobiles, solar, bikes) and manufacturers</p>
    </div>
    <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Products
    </a>
</div>

<div class="row g-4">
    <!-- Categories Column -->
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-folder-plus me-2"></i>Add New Category</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="" class="row g-2">
                    <input type="hidden" name="add_category" value="1">
                    <div class="col-sm-6">
                        <input type="text" name="cat_name" class="form-control form-control-sm" required placeholder="Category Name (e.g. Solar Inverters)">
                    </div>
                    <div class="col-sm-4">
                        <input type="text" name="cat_urdu" class="form-control form-control-sm" placeholder="اردو نام">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">+ Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border rounded-3 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-list me-2"></i>Existing Categories</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Urdu</th>
                            <th>Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= htmlspecialchars($cat['urdu_name'] ?: '-') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $cat['product_count'] ?> products</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Brands Column -->
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-copyright me-2"></i>Add New Brand</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="" class="row g-2">
                    <input type="hidden" name="add_brand" value="1">
                    <div class="col-sm-9">
                        <input type="text" name="brand_name" class="form-control form-control-sm" required placeholder="Brand Name (e.g. Orient, Pel, Waves)">
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">+ Add Brand</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border rounded-3 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-list me-2"></i>Existing Brands</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Brand Name</th>
                            <th>Products Linked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $b): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($b['name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $b['product_count'] ?> products</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
