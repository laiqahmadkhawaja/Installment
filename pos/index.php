<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$products = DB::fetchAll("SELECT * FROM products WHERE status = 'active' AND stock_quantity > 0 ORDER BY name ASC");
$customers = DB::fetchAll("SELECT id, full_name, phone, cnic, customer_code FROM customers WHERE branch_id = ? AND status != 'blacklisted' ORDER BY full_name ASC", [$branchId]);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saleType = $_POST['sale_type'] ?? 'cash';
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $downPayment = (float)($_POST['down_payment'] ?? 0);
    $months = (int)($_POST['months'] ?? 12);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';

    $product = DB::fetch("SELECT * FROM products WHERE id = ?", [$productId]);
    $customer = DB::fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);

    if (!$product || !$customer || $price <= 0) {
        $error = "Please select valid product, customer, and price.";
    } else {
        DB::beginTransaction();
        try {
            if ($saleType === 'cash') {
                // Quick Cash Sale
                $receiptNo = generate_receipt_no();

                // Deduct stock
                DB::query("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - 1) WHERE id = ?", [$productId]);

                // Insert into cashbook
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => date('Y-m-d'),
                    'account_type' => 'cash',
                    'type' => 'inflow',
                    'source' => 'sale',
                    'reference_no' => $receiptNo,
                    'amount' => $price,
                    'description' => "POS Counter Cash Sale: {$product['name']} to {$customer['full_name']}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);

                // Customer ledger entry
                DB::insert('customer_ledgers', [
                    'customer_id' => $customerId,
                    'branch_id' => $branchId,
                    'transaction_type' => 'sale',
                    'transaction_date' => date('Y-m-d'),
                    'reference_no' => $receiptNo,
                    'description' => "Cash Sale: {$product['name']}",
                    'debit' => $price,
                    'credit' => $price,
                    'balance' => 0.00,
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);

                DB::commit();
                audit_log('POS_CASH_SALE', 'POS', $productId, "Counter cash sale of {$product['name']} (Rs. {$price})");
                set_flash('success', "Cash Sale completed successfully! Receipt: <strong>{$receiptNo}</strong>");
                header("Location: " . BASE_URL . "/pos/index.php");
                exit;
            } else {
                // Redirect to full contract creator with pre-selected IDs
                DB::commit();
                header("Location: " . BASE_URL . "/contracts/create.php?customer_id={$customerId}&product_id={$productId}");
                exit;
            }
        } catch (Exception $e) {
            DB::rollBack();
            $error = "POS Transaction Failed: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-cash-register text-success me-2"></i><?= __('pos') ?> - Counter Billing</h4>
        <p class="text-muted small mb-0">Fast point-of-sale checkout for immediate counter cash sales and quick installment initiation</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- POS Interface Form -->
    <div class="col-lg-7">
        <div class="card border rounded-3 shadow-sm p-4 mb-4">
            <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-barcode me-2"></i>Fast Product Selection</h6>
            <form method="POST" action="">
                <!-- Sale Type Toggle -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sale Mode</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="sale_type" id="sale-cash" value="cash" checked autocomplete="off">
                        <label class="btn btn-outline-success fw-bold py-2" for="sale-cash">
                            <i class="fa-solid fa-money-bill me-1"></i> Full Cash Sale
                        </label>

                        <input type="radio" class="btn-check" name="sale_type" id="sale-inst" value="installment" autocomplete="off">
                        <label class="btn btn-outline-primary fw-bold py-2" for="sale-inst">
                            <i class="fa-solid fa-coins me-1"></i> Installment Plan Sale
                        </label>
                    </div>
                </div>

                <!-- Select Customer -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select form-select-lg" required>
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> &bull; Phone: <?= htmlspecialchars($c['phone']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Select Product -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product / Item <span class="text-danger">*</span></label>
                    <select name="product_id" id="pos-product" class="form-select form-select-lg" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" 
                            data-cash="<?= $p['cash_price'] ?>" 
                            data-inst="<?= $p['installment_price'] ?>"
                            data-stock="<?= $p['stock_quantity'] ?>">
                            <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['stock_quantity'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price input -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Final Sale Price (Rs.)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">Rs.</span>
                            <input type="number" name="price" id="pos-price" class="form-control fw-bold" step="100" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Mode</label>
                        <select name="payment_method" class="form-select form-select-lg">
                            <option value="cash" selected>Cash in Hand</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">Easypaisa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow">
                    <i class="fa-solid fa-receipt me-1"></i> Complete POS Checkout &rarr;
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stock Lookup Grid -->
    <div class="col-lg-5">
        <div class="card border rounded-3 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked me-2"></i>Quick Stock & Price Lookup</h6>
            </div>
            <div class="card-body p-3 overflow-y-auto" style="max-height: 480px;">
                <?php foreach ($products as $p): ?>
                <div class="p-3 bg-light rounded-3 mb-2 border d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                        <small class="text-muted">Code: <code><?= htmlspecialchars($p['product_code']) ?></code> &bull; In Stock: <strong><?= $p['stock_quantity'] ?></strong></small>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-bold">Cash: <?= format_currency($p['cash_price']) ?></div>
                        <div class="text-primary small fw-semibold">Inst: <?= format_currency($p['installment_price']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const prodSelect = document.getElementById('pos-product');
    const priceInput = document.getElementById('pos-price');
    const cashRadio = document.getElementById('sale-cash');
    const instRadio = document.getElementById('sale-inst');

    function updatePrice() {
        const opt = prodSelect.options[prodSelect.selectedIndex];
        if (opt && opt.dataset) {
            priceInput.value = cashRadio.checked ? opt.dataset.cash : opt.dataset.inst;
        }
    }

    if (prodSelect) prodSelect.addEventListener('change', updatePrice);
    if (cashRadio) cashRadio.addEventListener('change', updatePrice);
    if (instRadio) instRadio.addEventListener('change', updatePrice);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
