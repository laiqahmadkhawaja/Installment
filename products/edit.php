<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$product = DB::fetch("SELECT * FROM products WHERE id = ?", [$id]);

if (!$product) {
    set_flash('danger', 'Product not found.');
    header("Location: " . BASE_URL . "/products/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$categories = DB::fetchAll("SELECT * FROM categories ORDER BY name ASC");
$brands = DB::fetchAll("SELECT * FROM brands ORDER BY name ASC");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $urduName = trim($_POST['urdu_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $barcode = trim($_POST['barcode'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $purchaseCost = (float)($_POST['purchase_cost'] ?? 0);
    $cashPrice = (float)($_POST['cash_price'] ?? 0);
    $installmentPrice = (float)($_POST['installment_price'] ?? 0);
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $minStock = (int)($_POST['min_stock_alert'] ?? 2);
    $warranty = trim($_POST['warranty_period'] ?? '1 Year');
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || $installmentPrice <= 0) {
        $error = "Please fill in Product Name and Installment Price.";
    } else {
        try {
            DB::update('products', [
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'barcode' => $barcode,
                'name' => $name,
                'urdu_name' => $urduName,
                'model' => $model,
                'purchase_cost' => $purchaseCost,
                'cash_price' => $cashPrice,
                'installment_price' => $installmentPrice,
                'stock_quantity' => $stockQuantity,
                'min_stock_alert' => $minStock,
                'warranty_period' => $warranty,
                'status' => $status
            ], 'id = ?', [$id]);

            audit_log('PRODUCT_UPDATED', 'Products', $id, "Updated product: {$name}");
            set_flash('success', "Product <strong>{$name}</strong> updated successfully.");
            header("Location: " . BASE_URL . "/products/index.php");
            exit;
        } catch (Exception $e) {
            $error = "Update Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Product</h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars($product['name']) ?> &bull; <code><?= htmlspecialchars($product['product_code']) ?></code></p>
    </div>
    <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary">General Product Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Model / ماڈل</label>
                            <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($product['model'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Urdu Name (اردو نام)</label>
                            <input type="text" name="urdu_name" class="form-control" value="<?= htmlspecialchars($product['urdu_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Brand / Manufacturer</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- Select Brand --</option>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $product['brand_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Warranty Terms</label>
                            <input type="text" name="warranty_period" class="form-control" value="<?= htmlspecialchars($product['warranty_period']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-success">Pricing & Stock</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Purchase Cost (خریداری قیمت)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="purchase_cost" class="form-control" value="<?= (float)$product['purchase_cost'] ?>" step="500">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cash Sale Price (نقد قیمت)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="cash_price" class="form-control" value="<?= (float)$product['cash_price'] ?>" step="500">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-primary">Installment Sale Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">Rs.</span>
                            <input type="number" name="installment_price" class="form-control fw-bold text-primary" required value="<?= (float)$product['installment_price'] ?>" step="500">
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?= $product['stock_quantity'] ?>" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Minimum Stock Alert</label>
                        <input type="number" name="min_stock_alert" class="form-control" value="<?= $product['min_stock_alert'] ?>" min="1">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="fa-solid fa-check me-1"></i> Update Product
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
