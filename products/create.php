<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$categories = DB::fetchAll("SELECT * FROM categories ORDER BY name ASC");
$brands = DB::fetchAll("SELECT * FROM brands ORDER BY name ASC");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $urduName = trim($_POST['urdu_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $productCode = trim($_POST['product_code'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $purchaseCost = (float)($_POST['purchase_cost'] ?? 0);
    $cashPrice = (float)($_POST['cash_price'] ?? 0);
    $installmentPrice = (float)($_POST['installment_price'] ?? 0);
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $minStock = (int)($_POST['min_stock_alert'] ?? 2);
    $warranty = trim($_POST['warranty_period'] ?? '1 Year');
    $serialsInput = trim($_POST['serials'] ?? '');

    if (empty($name) || empty($productCode) || $installmentPrice <= 0) {
        $error = "Please fill in Product Name, Unique Code, and Installment Price.";
    } else {
        $check = DB::fetch("SELECT id FROM products WHERE product_code = ?", [$productCode]);
        if ($check) {
            $error = "A product with this Product Code / SKU already exists!";
        } else {
            DB::beginTransaction();
            try {
                $productId = DB::insert('products', [
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'product_code' => $productCode,
                    'barcode' => $barcode ?: $productCode,
                    'name' => $name,
                    'urdu_name' => $urduName,
                    'model' => $model,
                    'purchase_cost' => $purchaseCost,
                    'cash_price' => $cashPrice,
                    'installment_price' => $installmentPrice,
                    'stock_quantity' => $stockQuantity,
                    'min_stock_alert' => $minStock,
                    'warranty_period' => $warranty,
                    'status' => 'active'
                ]);

                // Insert serials or IMEIs if provided
                if (!empty($serialsInput)) {
                    $serials = array_filter(array_map('trim', explode("\n", $serialsInput)));
                    foreach ($serials as $sn) {
                        if (!empty($sn)) {
                            DB::insert('product_serials', [
                                'product_id' => $productId,
                                'branch_id' => $branchId,
                                'serial_or_imei' => $sn,
                                'status' => 'available'
                            ]);
                        }
                    }
                }

                DB::commit();
                audit_log('PRODUCT_CREATED', 'Products', $productId, "Added product: {$name} ({$productCode})");
                set_flash('success', "Product <strong>{$name}</strong> added successfully.");
                header("Location: " . BASE_URL . "/products/index.php");
                exit;
            } catch (Exception $e) {
                DB::rollBack();
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-box-open text-primary me-2"></i><?= __('new_product') ?></h4>
        <p class="text-muted small mb-0">Define product details, dual pricing (Cash vs Installment), and inventory tracking</p>
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
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Haier 1.5 Ton DC Inverter Air Conditioner" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Model / ماڈل</label>
                            <input type="text" name="model" class="form-control" placeholder="e.g. HSU-18HFT" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Urdu Name (اردو نام)</label>
                            <input type="text" name="urdu_name" class="form-control" placeholder="مثال: ہائیر ڈی سی انورٹر اے سی" value="<?= htmlspecialchars($_POST['urdu_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Product Code / SKU <span class="text-danger">*</span></label>
                            <input type="text" name="product_code" class="form-control" required placeholder="PRD-AC-02" value="<?= htmlspecialchars($_POST['product_code'] ?? 'PRD-' . rand(1000, 9999)) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Barcode</label>
                            <input type="text" name="barcode" class="form-control" placeholder="Scan or enter barcode" value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Brand / Manufacturer</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- Select Brand --</option>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Warranty Terms</label>
                            <input type="text" name="warranty_period" class="form-control" value="1 Year Official Warranty" value="<?= htmlspecialchars($_POST['warranty_period'] ?? '1 Year Official Warranty') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Serial / IMEI tracking -->
            <div class="card border rounded-3 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="fa-solid fa-barcode me-2"></i>Serial Numbers / Mobile IMEIs (Optional)</h6>
                </div>
                <div class="card-body p-4">
                    <label class="form-label small text-muted">Enter serial numbers or IMEIs (one per line). Useful for mobile phones, bikes, and appliances:</label>
                    <textarea name="serials" class="form-control font-monospace" rows="4" placeholder="IMEI-352899102938475&#10;IMEI-352899102938476"><?= htmlspecialchars($_POST['serials'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Pricing & Stock -->
        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-money-bill-wave me-2"></i>Pricing & Stock</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Purchase Cost (خریداری قیمت)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="purchase_cost" class="form-control" placeholder="80000" step="500" value="<?= htmlspecialchars($_POST['purchase_cost'] ?? '80000') ?>">
                        </div>
                        <small class="text-muted">Used to calculate actual gross sales profit.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cash Sale Price (نقد قیمت)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="cash_price" class="form-control" placeholder="90000" step="500" value="<?= htmlspecialchars($_POST['cash_price'] ?? '90000') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-primary">Installment Sale Price (قسطوں والی قیمت) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">Rs.</span>
                            <input type="number" name="installment_price" class="form-control fw-bold text-primary" required placeholder="110000" step="500" value="<?= htmlspecialchars($_POST['installment_price'] ?? '110000') ?>">
                        </div>
                        <small class="text-muted">Agreed price when sold on installment plan.</small>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="5" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Minimum Stock Alert</label>
                        <input type="number" name="min_stock_alert" class="form-control" value="2" min="1">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Product
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
