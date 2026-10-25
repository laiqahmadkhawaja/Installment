<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$preCustomerId = (int)($_GET['customer_id'] ?? 0);
$preProductId = (int)($_GET['product_id'] ?? 0);

$customers = DB::fetchAll("SELECT id, customer_code, full_name, phone, cnic FROM customers WHERE branch_id = ? AND status != 'blacklisted' ORDER BY full_name ASC", [$branchId]);
$products = DB::fetchAll("SELECT id, product_code, name, purchase_cost, cash_price, installment_price, stock_quantity FROM products WHERE status = 'active' ORDER BY name ASC");
$users = DB::fetchAll("SELECT id, full_name, role_id FROM users WHERE status = 'active'");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $salespersonId = !empty($_POST['salesperson_id']) ? (int)$_POST['salesperson_id'] : $_SESSION['user_id'];
    $collectionOfficerId = !empty($_POST['collection_officer_id']) ? (int)$_POST['collection_officer_id'] : null;
    $saleDate = $_POST['sale_date'] ?? date('Y-m-d');
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : $saleDate;
    
    $installmentPrice = (float)($_POST['installment_price'] ?? 0);
    $discount = (float)($_POST['discount_amount'] ?? 0);
    $downPayment = (float)($_POST['down_payment'] ?? 0);
    $months = (int)($_POST['installment_count'] ?? 12);
    $frequency = $_POST['frequency'] ?? 'monthly';
    $firstDueDate = $_POST['first_due_date'] ?? date('Y-m-d', strtotime('+1 month'));
    $notes = trim($_POST['notes'] ?? '');
    $serialNumber = trim($_POST['serial_number'] ?? '');

    $product = DB::fetch("SELECT * FROM products WHERE id = ?", [$productId]);
    $customer = DB::fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);

    if (!$customer || !$product) {
        $error = "Please select both a valid customer and product.";
    } elseif ($installmentPrice <= 0 || $months < 1) {
        $error = "Installment price and duration must be greater than zero.";
    } else {
        $totalPayable = max(0, $installmentPrice - $discount);
        $financedAmount = max(0, $totalPayable - $downPayment);
        $monthlyInstallment = $months > 0 ? round($financedAmount / $months) : 0;
        $finalDueDate = date('Y-m-d', strtotime("+$months months", strtotime($firstDueDate)));

        DB::beginTransaction();
        try {
            $contractNumber = generate_contract_no();

            // 1. Insert Contract
            $contractId = DB::insert('installment_contracts', [
                'contract_number' => $contractNumber,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'salesperson_id' => $salespersonId,
                'collection_officer_id' => $collectionOfficerId,
                'sale_date' => $saleDate,
                'delivery_date' => $deliveryDate,
                'cash_price_total' => $product['cash_price'],
                'installment_price_total' => $installmentPrice,
                'discount_amount' => $discount,
                'down_payment' => $downPayment,
                'financed_amount' => $financedAmount,
                'total_payable' => $totalPayable,
                'total_paid' => $downPayment,
                'balance_amount' => $financedAmount,
                'installment_count' => $months,
                'frequency' => $frequency,
                'installment_amount' => $monthlyInstallment,
                'first_due_date' => $firstDueDate,
                'final_due_date' => $finalDueDate,
                'status' => 'active',
                'notes' => $notes,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            // 2. Insert Contract Item
            DB::insert('contract_items', [
                'contract_id' => $contractId,
                'product_id' => $productId,
                'serial_number' => $serialNumber,
                'quantity' => 1,
                'unit_cost' => $product['purchase_cost'],
                'cash_price' => $product['cash_price'],
                'installment_price' => $installmentPrice,
                'subtotal' => $installmentPrice
            ]);

            // 3. Deduct Inventory Stock
            DB::query("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - 1) WHERE id = ?", [$productId]);

            // Update serial status if provided
            if (!empty($serialNumber)) {
                DB::query("UPDATE product_serials SET status = 'sold', contract_id = ? WHERE serial_or_imei = ?", [$contractId, $serialNumber]);
            }

            // 4. Generate Installment Schedules (Monthly / Frequency)
            $remainingFinanced = $financedAmount;
            for ($i = 1; $i <= $months; $i++) {
                $dueDate = date('Y-m-d', strtotime("+ " . ($i - 1) . " months", strtotime($firstDueDate)));
                $amountForThis = ($i == $months) ? $remainingFinanced : $monthlyInstallment;
                $remainingFinanced -= $amountForThis;

                DB::insert('installment_schedules', [
                    'contract_id' => $contractId,
                    'installment_number' => $i,
                    'due_date' => $dueDate,
                    'installment_amount' => $amountForThis,
                    'late_fee' => 0.00,
                    'paid_amount' => 0.00,
                    'remaining_amount' => $amountForThis,
                    'status' => 'pending'
                ]);
            }

            // 5. Down Payment receipt & Ledger transactions
            if ($downPayment > 0) {
                $receiptNo = generate_receipt_no();
                $paymentId = DB::insert('payments', [
                    'receipt_number' => $receiptNo,
                    'branch_id' => $branchId,
                    'contract_id' => $contractId,
                    'customer_id' => $customerId,
                    'payment_date' => $saleDate,
                    'amount' => $downPayment,
                    'payment_method' => 'cash',
                    'transaction_reference' => 'DOWN-PAYMENT',
                    'collected_by' => $_SESSION['user_id'] ?? 1,
                    'remarks' => "Initial Down Payment for Contract {$contractNumber}"
                ]);

                // Record cash inflow in cashbook
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => $saleDate,
                    'account_type' => 'cash',
                    'type' => 'inflow',
                    'source' => 'down_payment',
                    'reference_no' => $receiptNo,
                    'amount' => $downPayment,
                    'description' => "Down Payment from {$customer['full_name']} for {$contractNumber}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            // 6. Customer Ledger:
            // Entry A: Installment Sale (Debit)
            DB::insert('customer_ledgers', [
                'customer_id' => $customerId,
                'contract_id' => $contractId,
                'branch_id' => $branchId,
                'transaction_type' => 'sale',
                'transaction_date' => $saleDate,
                'reference_no' => $contractNumber,
                'description' => "Installment Sale: {$product['name']} ({$months} Months)",
                'debit' => $installmentPrice,
                'credit' => 0.00,
                'balance' => $installmentPrice,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            // Entry B: Discount if given
            if ($discount > 0) {
                DB::insert('customer_ledgers', [
                    'customer_id' => $customerId,
                    'contract_id' => $contractId,
                    'branch_id' => $branchId,
                    'transaction_type' => 'discount',
                    'transaction_date' => $saleDate,
                    'reference_no' => $contractNumber,
                    'description' => "Promotional Discount on Contract {$contractNumber}",
                    'debit' => 0.00,
                    'credit' => $discount,
                    'balance' => ($installmentPrice - $discount),
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            // Entry C: Down payment credit
            if ($downPayment > 0) {
                DB::insert('customer_ledgers', [
                    'customer_id' => $customerId,
                    'contract_id' => $contractId,
                    'branch_id' => $branchId,
                    'transaction_type' => 'down_payment',
                    'transaction_date' => $saleDate,
                    'reference_no' => $receiptNo,
                    'description' => "Down Payment Received (Cash)",
                    'debit' => 0.00,
                    'credit' => $downPayment,
                    'balance' => $financedAmount,
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            DB::commit();
            audit_log('CONTRACT_CREATED', 'Contracts', $contractId, "Created plan {$contractNumber} for customer {$customer['full_name']}");
            set_flash('success', "Contract <strong>{$contractNumber}</strong> successfully generated!");
            header("Location: " . BASE_URL . "/contracts/view.php?id={$contractId}");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-circle-plus text-primary me-2"></i><?= __('new_contract') ?></h4>
        <p class="text-muted small mb-0">Book a new installment financing sale, calculate schedule, and process down payment</p>
    </div>
    <a href="<?= BASE_URL ?>/contracts/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Contracts
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="" id="contract-form">
    <div class="row g-4">
        <!-- Left: Customer, Product & Terms -->
        <div class="col-lg-8">
            <!-- Customer Section -->
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-user me-2"></i>Customer Information</h6>
                    <a href="<?= BASE_URL ?>/customers/create.php" target="_blank" class="btn btn-sm btn-outline-primary">+ New Customer</a>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Select Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select form-select-lg" required id="select-customer">
                                <option value="">-- Choose Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $preCustomerId == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['full_name']) ?> &bull; Phone: <?= htmlspecialchars($c['phone']) ?> &bull; CNIC: <?= htmlspecialchars($c['cnic']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Selection -->
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-box-open me-2"></i>Product & Serial Selection</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Select Product to Finance <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required id="select-product">
                                <option value="">-- Choose Product --</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" 
                                    data-cash="<?= $p['cash_price'] ?>" 
                                    data-installment="<?= $p['installment_price'] ?>"
                                    data-cost="<?= $p['purchase_cost'] ?>"
                                    data-stock="<?= $p['stock_quantity'] ?>"
                                    <?= $preProductId == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['stock_quantity'] ?>) - Installment: Rs. <?= number_format($p['installment_price']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Serial # / IMEI (Optional)</label>
                            <input type="text" name="serial_number" class="form-control" placeholder="Scan or enter Serial/IMEI">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Officers & Date Settings -->
            <div class="card border rounded-3 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="fa-solid fa-users-gear me-2"></i>Officer Assignment & Schedule Dates</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sale Date</label>
                            <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-primary">First Installment Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="first_due_date" class="form-control fw-bold" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Salesperson</label>
                            <select name="salesperson_id" class="form-select">
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($_SESSION['user_id'] ?? 1) == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Collection / Recovery Officer</label>
                            <select name="collection_officer_id" class="form-select">
                                <option value="">-- Default Branch Officer --</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Special Contract Notes / Terms</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Agreed terms, warranty conditions, custom payment notes..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Calculation Breakdown -->
        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-calculator me-2"></i>Pricing & Schedule Calculator</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Installment Sale Price (Rs.)</label>
                        <input type="number" name="installment_price" id="input-installment-price" class="form-control fw-bold" step="500" value="120000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Special Discount (Rs.)</label>
                        <input type="number" name="discount_amount" id="input-discount" class="form-control" step="100" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-success">Down Payment / Advance (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" name="down_payment" id="input-down-payment" class="form-control fw-bold border-success" step="500" value="30000" required>
                        <small class="text-muted">Paid upfront at time of delivery.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Duration (Number of Installments) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="installment_count" id="input-months" class="form-control fw-bold" value="12" min="1" max="60" required>
                            <span class="input-group-text">Months</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="monthly" selected>Monthly (ماہانہ)</option>
                            <option value="weekly">Weekly (ہفتہ وار)</option>
                            <option value="daily">Daily (روزانہ)</option>
                            <option value="biweekly">Bi-weekly (پندرہ روزہ)</option>
                            <option value="quarterly">Quarterly (سہ ماہی)</option>
                        </select>
                    </div>

                    <!-- Live Calculation Card -->
                    <div class="p-3 bg-light rounded-3 mb-4 border calculation-summary-box">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Contract Price:</span>
                            <span class="fw-bold" id="disp-total-payable">Rs. 120,000.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Remaining Financed:</span>
                            <span class="fw-bold text-danger" id="disp-financed-amount">Rs. 90,000.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Monthly Installment:</span>
                            <span class="h5 fw-bold text-success mb-0" id="disp-monthly-installment">Rs. 7,500.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-check-circle me-1"></i> Generate Installment Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect = document.getElementById('select-product');
    const instInput = document.getElementById('input-installment-price');

    if (productSelect && instInput) {
        productSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.installment) {
                instInput.value = opt.dataset.installment;
                instInput.dispatchEvent(new Event('input'));
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
