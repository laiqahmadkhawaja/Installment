<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$suppliers = DB::fetchAll("SELECT * FROM suppliers ORDER BY name ASC");
$products = DB::fetchAll("SELECT * FROM products ORDER BY name ASC");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $invoiceNo = trim($_POST['invoice_number'] ?? ('PUR-' . rand(1000, 9999)));
    $quantity = (int)($_POST['quantity'] ?? 1);
    $unitCost = (float)($_POST['unit_cost'] ?? 0);
    $paidAmount = (float)($_POST['paid_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $product = DB::fetch("SELECT * FROM products WHERE id = ?", [$productId]);
    $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ?", [$supplierId]);

    if (!$product || !$supplier || $quantity < 1 || $unitCost <= 0) {
        $error = "Please specify a valid supplier, product, quantity, and cost price.";
    } else {
        $totalAmount = $quantity * $unitCost;
        $balanceAmount = max(0, $totalAmount - $paidAmount);
        $status = ($balanceAmount <= 0) ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

        DB::beginTransaction();
        try {
            // 1. Insert Purchase
            $purchaseId = DB::insert('purchases', [
                'invoice_number' => $invoiceNo,
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'purchase_date' => $purchaseDate,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $status,
                'notes' => $notes,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            // 2. Insert item
            DB::insert('purchase_items', [
                'purchase_id' => $purchaseId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $totalAmount
            ]);

            // 3. Stock in: Increase product quantity
            DB::query("UPDATE products SET stock_quantity = stock_quantity + ?, purchase_cost = ? WHERE id = ?", [$quantity, $unitCost, $productId]);

            // 4. Update supplier balance
            if ($balanceAmount > 0) {
                DB::query("UPDATE suppliers SET current_balance = current_balance + ? WHERE id = ?", [$balanceAmount, $supplierId]);
            }

            // 5. If paid amount > 0, log in cashbook as outflow
            if ($paidAmount > 0) {
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => $purchaseDate,
                    'account_type' => 'cash',
                    'type' => 'outflow',
                    'source' => 'purchase',
                    'reference_no' => $invoiceNo,
                    'amount' => $paidAmount,
                    'description' => "Supplier Payment to {$supplier['name']} for Invoice {$invoiceNo}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            DB::commit();
            audit_log('PURCHASE_CREATED', 'Purchases', $purchaseId, "Purchased {$quantity}x {$product['name']} from {$supplier['name']}");
            set_flash('success', "Stock-in Purchase recorded! Stock increased by <strong>{$quantity}</strong> units.");
            header("Location: " . BASE_URL . "/purchases/index.php");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Purchase Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-cart-plus text-primary me-2"></i><?= __('new_purchase') ?> (Stock-In)</h4>
        <p class="text-muted small mb-0">Record inventory intake from suppliers and update available physical stock</p>
    </div>
    <a href="<?= BASE_URL ?>/purchases/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Purchases
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border rounded-3 shadow-sm p-4">
            <form method="POST" action="">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Supplier / Distributor <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select form-select-lg" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['company_name'] ?: 'Distributor') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Purchase Invoice / Bill # <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control form-control-lg" required value="PUR-<?= date('Y') ?>-<?= rand(1000, 9999) ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Select Product to Stock-In <span class="text-danger">*</span></label>
                        <select name="product_id" id="purchase-product" class="form-select form-select-lg" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-cost="<?= $p['purchase_cost'] ?>">
                                <?= htmlspecialchars($p['name']) ?> &bull; Current Stock: <?= $p['stock_quantity'] ?> units
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Quantity Purchased <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="pur-qty" class="form-control form-control-lg fw-bold" value="5" min="1" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Unit Purchase Cost (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" name="unit_cost" id="pur-cost" class="form-control form-control-lg fw-bold" step="500" value="80000" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Amount Paid to Supplier (Rs.)</label>
                        <input type="number" name="paid_amount" class="form-control form-control-lg fw-bold text-success" step="500" value="0">
                        <small class="text-muted">Remaining will be added to supplier payable.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Invoice Notes / Shipment Details</label>
                        <input type="text" name="notes" class="form-control" placeholder="Bilty number, transport details...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                    <i class="fa-solid fa-boxes-packing me-1"></i> Confirm Stock-In Purchase & Update Inventory &rarr;
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const prodSelect = document.getElementById('purchase-product');
    const costInput = document.getElementById('pur-cost');
    if (prodSelect && costInput) {
        prodSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.cost) {
                costInput.value = opt.dataset.cost;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
