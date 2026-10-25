<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$preContractId = (int)($_GET['contract_id'] ?? 0);
$preCustomerId = (int)($_GET['customer_id'] ?? 0);
$preScheduleId = (int)($_GET['schedule_id'] ?? 0);
$preAmount = (float)($_GET['amount'] ?? 0);

$contracts = DB::fetchAll("SELECT c.id, c.contract_number, c.customer_id, c.balance_amount, c.installment_amount,
    cust.full_name as customer_name, cust.phone, cust.cnic, cust.customer_code
    FROM installment_contracts c
    JOIN customers cust ON c.customer_id = cust.id
    WHERE c.branch_id = ? AND c.status IN ('active', 'overdue')
    ORDER BY c.id DESC", [$branchId]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractId = (int)($_POST['contract_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $bankName = trim($_POST['bank_name'] ?? '');
    $reference = trim($_POST['transaction_reference'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    $contract = DB::fetch("SELECT c.*, cust.full_name as customer_name, cust.phone, cust.cnic, cust.customer_code 
        FROM installment_contracts c 
        JOIN customers cust ON c.customer_id = cust.id 
        WHERE c.id = ?", [$contractId]);

    if (!$contract) {
        $error = "Please select an active installment contract.";
    } elseif ($amount <= 0) {
        $error = "Payment amount must be greater than zero.";
    } else {
        DB::beginTransaction();
        try {
            $receiptNumber = generate_receipt_no();

            // 1. Insert Payment
            $paymentId = DB::insert('payments', [
                'receipt_number' => $receiptNumber,
                'branch_id' => $branchId,
                'contract_id' => $contractId,
                'customer_id' => $contract['customer_id'],
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'bank_name' => $bankName,
                'transaction_reference' => $reference,
                'collected_by' => $_SESSION['user_id'] ?? 1,
                'remarks' => $remarks ?: "Installment collection for {$contract['contract_number']}"
            ]);

            // 2. Smart Oldest-Due-First Allocation
            $unallocated = $amount;
            $schedules = DB::fetchAll("SELECT * FROM installment_schedules 
                WHERE contract_id = ? AND status IN ('overdue', 'pending', 'partial') 
                ORDER BY due_date ASC, installment_number ASC", [$contractId]);

            foreach ($schedules as $sch) {
                if ($unallocated <= 0) break;

                $dueForThis = (float)$sch['remaining_amount'] + (float)$sch['late_fee'];
                $alloc = min($unallocated, $dueForThis);

                // Insert allocation record
                DB::insert('payment_allocations', [
                    'payment_id' => $paymentId,
                    'schedule_id' => $sch['id'],
                    'allocated_amount' => $alloc
                ]);

                $newPaid = (float)$sch['paid_amount'] + $alloc;
                $newRemaining = max(0, (float)$sch['installment_amount'] - $newPaid);
                $newStatus = ($newRemaining <= 0) ? 'paid' : 'partial';

                DB::update('installment_schedules', [
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'status' => $newStatus,
                    'paid_date' => ($newStatus === 'paid') ? $paymentDate : $sch['paid_date']
                ], 'id = ?', [$sch['id']]);

                $unallocated -= $alloc;
            }

            // 3. Update Contract Total Paid & Balance
            $newTotalPaid = (float)$contract['total_paid'] + $amount;
            $newBalance = max(0, (float)$contract['total_payable'] - $newTotalPaid);
            $contractStatus = ($newBalance <= 0) ? 'completed' : $contract['status'];

            DB::update('installment_contracts', [
                'total_paid' => $newTotalPaid,
                'balance_amount' => $newBalance,
                'status' => $contractStatus
            ], 'id = ?', [$contractId]);

            // 4. Update Customer Ledger
            $lastLedger = DB::fetch("SELECT balance FROM customer_ledgers WHERE customer_id = ? ORDER BY id DESC LIMIT 1", [$contract['customer_id']]);
            $prevBal = $lastLedger ? (float)$lastLedger['balance'] : $newBalance;
            $updatedCustomerBalance = max(0, $prevBal - $amount);

            DB::insert('customer_ledgers', [
                'customer_id' => $contract['customer_id'],
                'contract_id' => $contractId,
                'branch_id' => $branchId,
                'transaction_type' => 'installment_payment',
                'transaction_date' => $paymentDate,
                'reference_id' => $paymentId,
                'reference_no' => $receiptNumber,
                'description' => "Installment Payment Received ({$paymentMethod})",
                'debit' => 0.00,
                'credit' => $amount,
                'balance' => $updatedCustomerBalance,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            // 5. Cashbook Inflow
            DB::insert('cashbook', [
                'branch_id' => $branchId,
                'entry_date' => $paymentDate,
                'account_type' => in_array($paymentMethod, ['cash', 'easypaisa', 'jazzcash']) ? 'cash' : 'bank',
                'type' => 'inflow',
                'source' => 'installment',
                'reference_no' => $receiptNumber,
                'amount' => $amount,
                'description' => "Installment received from {$contract['customer_name']} ({$receiptNumber})",
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            DB::commit();
            audit_log('PAYMENT_COLLECTED', 'Collection', $paymentId, "Collected Rs. {$amount} from {$contract['customer_name']} (Receipt: {$receiptNumber})");
            set_flash('success', "Payment of <strong>" . format_currency($amount) . "</strong> received successfully! Receipt: <strong>{$receiptNumber}</strong>");
            header("Location: " . BASE_URL . "/collection/receipt.php?id={$paymentId}");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Collection Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i><?= __('receive_payment') ?></h4>
        <p class="text-muted small mb-0">Fast counter payment collection with automatic smart allocation to oldest due installments</p>
    </div>
    <a href="<?= BASE_URL ?>/collection/daily_sheet.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-receipt me-1"></i> <?= __('daily_collection') ?>
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border rounded-3 shadow-sm p-4">
            <form method="POST" action="">
                <!-- Select Contract -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Active Contract / Customer <span class="text-danger">*</span></label>
                    <select name="contract_id" id="collection-contract-select" class="form-select form-select-lg" required>
                        <option value="">-- Search Customer or Contract # --</option>
                        <?php foreach ($contracts as $c): ?>
                        <option value="<?= $c['id'] ?>" 
                            data-balance="<?= $c['balance_amount'] ?>" 
                            data-monthly="<?= $c['installment_amount'] ?>"
                            data-customer="<?= htmlspecialchars($c['customer_name']) ?>"
                            data-phone="<?= htmlspecialchars($c['phone']) ?>"
                            <?= ($preContractId == $c['id'] || ($preCustomerId > 0 && $preCustomerId == $c['customer_id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['contract_number']) ?> &bull; <?= htmlspecialchars($c['customer_name']) ?> (Phone: <?= $c['phone'] ?>) - Balance: Rs. <?= number_format($c['balance_amount']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Contract Snapshot Banner -->
                <div id="contract-info-box" class="p-3 bg-light rounded-3 mb-4 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Monthly Installment:</span>
                        <strong class="h5 text-primary mb-0" id="box-monthly">Rs. -</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Current Remaining Balance:</span>
                        <strong class="h5 text-danger mb-0" id="box-balance">Rs. -</strong>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-success">Collection Amount (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white">Rs.</span>
                            <input type="number" name="amount" id="collection-amount-input" class="form-control fw-bold text-success" step="100" placeholder="e.g. 7500" value="<?= $preAmount > 0 ? $preAmount : '' ?>" required autofocus>
                        </div>
                        <!-- Quick 1-Click Chips -->
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small" onclick="setQuickAmount('monthly')">Exact Inst.</button>
                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 small" onclick="setQuickAmount('balance')">Full Balance</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small" onclick="addQuickAmount(1000)">+1,000</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small" onclick="addQuickAmount(5000)">+5,000</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small" onclick="document.getElementById('collection-amount-input').value='';">Clear</button>
                        </div>
                        <small class="text-muted d-block mt-1">Smart allocation will settle oldest overdue installments first.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control form-control-lg" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-lg">
                            <option value="cash" selected>Cash (نقد)</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">Easypaisa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="bank_deposit">Bank Deposit</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Transaction Ref / Cheque #</label>
                        <input type="text" name="transaction_reference" class="form-control form-control-lg" placeholder="e.g. JC-99281726 or Cheque #">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Remarks / Internal Note</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Installment payment receipt...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow">
                    <i class="fa-solid fa-check-circle me-1"></i> Confirm Payment & Print Receipt &rarr;
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const contractSelect = document.getElementById('collection-contract-select');
    const boxMonthly = document.getElementById('box-monthly');
    const boxBalance = document.getElementById('box-balance');
    const amountInput = document.getElementById('collection-amount-input');

    function updateContractInfo() {
        const opt = contractSelect.options[contractSelect.selectedIndex];
        if (opt && opt.dataset.balance) {
            boxBalance.innerText = 'Rs. ' + parseFloat(opt.dataset.balance).toLocaleString('en-US', { minimumFractionDigits: 2 });
            boxMonthly.innerText = 'Rs. ' + parseFloat(opt.dataset.monthly).toLocaleString('en-US', { minimumFractionDigits: 2 });
            if (!amountInput.value || amountInput.value === '0') {
                amountInput.value = opt.dataset.monthly;
            }
        } else {
            boxBalance.innerText = 'Rs. -';
            boxMonthly.innerText = 'Rs. -';
        }
    }

    if (contractSelect) {
        contractSelect.addEventListener('change', updateContractInfo);
        updateContractInfo();
    }

    window.setQuickAmount = function(type) {
        const opt = contractSelect.options[contractSelect.selectedIndex];
        if (opt) {
            if (type === 'monthly' && opt.dataset.monthly) {
                amountInput.value = Math.round(parseFloat(opt.dataset.monthly));
            } else if (type === 'balance' && opt.dataset.balance) {
                amountInput.value = Math.round(parseFloat(opt.dataset.balance));
            }
        }
    };

    window.addQuickAmount = function(addVal) {
        const curr = parseFloat(amountInput.value) || 0;
        amountInput.value = curr + addVal;
    };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
