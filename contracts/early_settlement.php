<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$contractId = (int)($_GET['id'] ?? 0);
$contract = DB::fetch("SELECT c.*, cust.full_name as customer_name, cust.phone, cust.cnic, cust.customer_code 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.id = ?", [$contractId]);

if (!$contract) {
    set_flash('danger', 'Contract not found.');
    header("Location: " . BASE_URL . "/contracts/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$error = '';

// Calculate outstanding
$outstandingBalance = (float)$contract['balance_amount'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settlementDiscount = (float)($_POST['settlement_discount'] ?? 0);
    $finalAmountPaid = (float)($_POST['final_amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $remarks = trim($_POST['remarks'] ?? 'Full Early Settlement & Contract Closure');

    if ($finalAmountPaid + $settlementDiscount < $outstandingBalance) {
        $error = "Payment amount + discount must equal or settle the outstanding balance of " . format_currency($outstandingBalance);
    } else {
        DB::beginTransaction();
        try {
            $receiptNo = generate_receipt_no();

            // 1. Record final settlement payment
            if ($finalAmountPaid > 0) {
                DB::insert('payments', [
                    'receipt_number' => $receiptNo,
                    'branch_id' => $branchId,
                    'contract_id' => $contractId,
                    'customer_id' => $contract['customer_id'],
                    'payment_date' => date('Y-m-d'),
                    'amount' => $finalAmountPaid,
                    'payment_method' => $paymentMethod,
                    'transaction_reference' => 'EARLY-SETTLEMENT',
                    'collected_by' => $_SESSION['user_id'] ?? 1,
                    'remarks' => $remarks
                ]);

                // Record cashbook inflow
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => date('Y-m-d'),
                    'account_type' => $paymentMethod === 'cash' ? 'cash' : 'bank',
                    'type' => 'inflow',
                    'source' => 'installment',
                    'reference_no' => $receiptNo,
                    'amount' => $finalAmountPaid,
                    'description' => "Early Settlement for Contract {$contract['contract_number']}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            // 2. Record Discount if provided
            if ($settlementDiscount > 0) {
                DB::insert('customer_ledgers', [
                    'customer_id' => $contract['customer_id'],
                    'contract_id' => $contractId,
                    'branch_id' => $branchId,
                    'transaction_type' => 'discount',
                    'transaction_date' => date('Y-m-d'),
                    'reference_no' => $contract['contract_number'],
                    'description' => "Early Settlement Rebate / Discount",
                    'debit' => 0.00,
                    'credit' => $settlementDiscount,
                    'balance' => ($outstandingBalance - $settlementDiscount),
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            // 3. Customer Ledger for Payment
            if ($finalAmountPaid > 0) {
                DB::insert('customer_ledgers', [
                    'customer_id' => $contract['customer_id'],
                    'contract_id' => $contractId,
                    'branch_id' => $branchId,
                    'transaction_type' => 'installment_payment',
                    'transaction_date' => date('Y-m-d'),
                    'reference_no' => $receiptNo,
                    'description' => "Early Settlement Clearance Payment ({$paymentMethod})",
                    'debit' => 0.00,
                    'credit' => $finalAmountPaid,
                    'balance' => 0.00,
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            // 4. Mark all remaining schedules as paid
            DB::query("UPDATE installment_schedules 
                SET paid_amount = installment_amount, remaining_amount = 0.00, status = 'paid', paid_date = CURDATE(), notes = 'Closed via Early Settlement' 
                WHERE contract_id = ? AND status != 'paid'", [$contractId]);

            // 5. Update Contract Status to 'settled'
            DB::query("UPDATE installment_contracts 
                SET total_paid = total_payable, balance_amount = 0.00, status = 'settled', notes = CONCAT(COALESCE(notes,''), ' | Early Settled on " . date('Y-m-d') . "')
                WHERE id = ?", [$contractId]);

            // 6. Record in returns_cancellations table for audit trail
            DB::insert('returns_cancellations', [
                'branch_id' => $branchId,
                'contract_id' => $contractId,
                'customer_id' => $contract['customer_id'],
                'return_type' => 'early_settlement',
                'return_date' => date('Y-m-d'),
                'refund_amount' => 0.00,
                'discount_given' => $settlementDiscount,
                'reason' => $remarks,
                'processed_by' => $_SESSION['user_id'] ?? 1
            ]);

            DB::commit();
            audit_log('CONTRACT_SETTLED', 'Contracts', $contractId, "Contract {$contract['contract_number']} settled early by {$contract['customer_name']}");
            set_flash('success', "Contract <strong>{$contract['contract_number']}</strong> has been successfully settled and closed!");
            header("Location: " . BASE_URL . "/contracts/view.php?id={$contractId}");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Settlement error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calculator text-primary me-2"></i>Early Settlement & Contract Closure</h4>
        <p class="text-muted small mb-0">Pre-closure calculation for Contract: <strong><?= htmlspecialchars($contract['contract_number']) ?></strong></p>
    </div>
    <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $contractId ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Contract
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-primary">Contract Status & Current Balance</h6>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted">Customer:</td><td class="fw-bold"><?= htmlspecialchars($contract['customer_name']) ?></td></tr>
                    <tr><td class="text-muted">Phone:</td><td><?= htmlspecialchars($contract['phone']) ?></td></tr>
                    <tr><td class="text-muted">Total Financed Price:</td><td><?= format_currency($contract['installment_price_total']) ?></td></tr>
                    <tr><td class="text-muted">Total Paid to Date:</td><td class="text-success fw-semibold"><?= format_currency($contract['total_paid']) ?></td></tr>
                    <tr><td class="text-muted">Current Outstanding Balance:</td><td class="text-danger fw-bold h5 mb-0"><?= format_currency($outstandingBalance) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border rounded-3 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-check-double me-2"></i>Process Early Settlement Clearance</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Early Settlement Discount / Rebate (Rs.)</label>
                        <input type="number" name="settlement_discount" id="settlement-discount" class="form-control" value="0" step="500">
                        <small class="text-muted">Optional discount authorized for lump-sum early payoff.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-success">Final Amount Paid by Customer (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" name="final_amount" id="final-amount" class="form-control fw-bold border-success" value="<?= $outstandingBalance ?>" step="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" selected>Cash in Hand (نقد)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">Easypaisa</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Settlement Notes / Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">Full early settlement payment received. Account cleared and closed with zero remaining balance.</textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Are you sure you want to mark this contract as settled and complete?');">
                        <i class="fa-solid fa-circle-check me-1"></i> Close Contract & Issue Clearance
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const origBalance = <?= $outstandingBalance ?>;
    const discInput = document.getElementById('settlement-discount');
    const finalInput = document.getElementById('final-amount');

    if (discInput && finalInput) {
        discInput.addEventListener('input', function () {
            const disc = parseFloat(this.value) || 0;
            const net = Math.max(0, origBalance - disc);
            finalInput.value = net;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
