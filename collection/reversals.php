<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$error = '';
$success = '';

// Handle Reversal Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reverse') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $reason = trim($_POST['reversal_reason'] ?? '');

    if (empty($reason)) {
        $error = "Reversal reason is strictly required for financial auditing purposes.";
    } else {
        $payment = DB::fetch("SELECT * FROM payments WHERE id = ? AND is_reversed = 0", [$paymentId]);
        if (!$payment) {
            $error = "Payment record not found or already reversed.";
        } else {
            DB::beginTransaction();
            try {
                // 1. Mark payment as reversed
                DB::update('payments', [
                    'is_reversed' => 1,
                    'reversal_reason' => $reason,
                    'reversed_by' => $_SESSION['user_id'] ?? 1,
                    'reversed_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$paymentId]);

                // 2. Roll back schedules allocation
                $allocations = DB::fetchAll("SELECT * FROM payment_allocations WHERE payment_id = ?", [$paymentId]);
                foreach ($allocations as $a) {
                    $sch = DB::fetch("SELECT * FROM installment_schedules WHERE id = ?", [$a['schedule_id']]);
                    if ($sch) {
                        $restoredPaid = max(0, (float)$sch['paid_amount'] - (float)$a['allocated_amount']);
                        $restoredRemaining = (float)$sch['installment_amount'] - $restoredPaid;
                        $restoredStatus = ($restoredPaid <= 0) ? ($sch['due_date'] < date('Y-m-d') ? 'overdue' : 'pending') : 'partial';

                        DB::update('installment_schedules', [
                            'paid_amount' => $restoredPaid,
                            'remaining_amount' => $restoredRemaining,
                            'status' => $restoredStatus
                        ], 'id = ?', [$sch['id']]);
                    }
                }

                // 3. Roll back Contract total_paid & balance
                $contract = DB::fetch("SELECT * FROM installment_contracts WHERE id = ?", [$payment['contract_id']]);
                if ($contract) {
                    $newPaid = max(0, (float)$contract['total_paid'] - (float)$payment['amount']);
                    $newBalance = (float)$contract['total_payable'] - $newPaid;
                    DB::update('installment_contracts', [
                        'total_paid' => $newPaid,
                        'balance_amount' => $newBalance,
                        'status' => 'active'
                    ], 'id = ?', [$contract['id']]);
                }

                // 4. Record Customer Ledger offsetting entry
                DB::insert('customer_ledgers', [
                    'customer_id' => $payment['customer_id'],
                    'contract_id' => $payment['contract_id'],
                    'branch_id' => $branchId,
                    'transaction_type' => 'reversal',
                    'transaction_date' => date('Y-m-d'),
                    'reference_id' => $paymentId,
                    'reference_no' => $payment['receipt_number'],
                    'description' => "REVERSAL of Receipt {$payment['receipt_number']}: {$reason}",
                    'debit' => $payment['amount'],
                    'credit' => 0.00,
                    'balance' => ($newBalance ?? $payment['amount']),
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);

                // 5. Cashbook offsetting entry
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => date('Y-m-d'),
                    'account_type' => in_array($payment['payment_method'], ['cash', 'easypaisa', 'jazzcash']) ? 'cash' : 'bank',
                    'type' => 'outflow',
                    'source' => 'reversal',
                    'reference_no' => $payment['receipt_number'],
                    'amount' => $payment['amount'],
                    'description' => "REVERSAL of Receipt {$payment['receipt_number']}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);

                DB::commit();
                audit_log('PAYMENT_REVERSED', 'Collection', $paymentId, "Reversed Receipt {$payment['receipt_number']} (Rs. {$payment['amount']}) Reason: {$reason}");
                set_flash('success', "Payment receipt <strong>{$payment['receipt_number']}</strong> has been successfully reversed.");
                header("Location: " . BASE_URL . "/collection/reversals.php");
                exit;
            } catch (Exception $e) {
                DB::rollBack();
                $error = "Reversal error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all reversed payments
$reversedList = DB::fetchAll("SELECT p.*, c.contract_number, cust.full_name as customer_name, u.full_name as rev_by 
    FROM payments p 
    JOIN installment_contracts c ON p.contract_id = c.id 
    JOIN customers cust ON p.customer_id = cust.id 
    LEFT JOIN users u ON p.reversed_by = u.id 
    WHERE p.branch_id = ? AND p.is_reversed = 1 
    ORDER BY p.reversed_at DESC", [$branchId]);

// Recent Active Payments eligible for reversal
$activePayments = DB::fetchAll("SELECT p.*, c.contract_number, cust.full_name as customer_name 
    FROM payments p 
    JOIN installment_contracts c ON p.contract_id = c.id 
    JOIN customers cust ON p.customer_id = cust.id 
    WHERE p.branch_id = ? AND p.is_reversed = 0 
    ORDER BY p.id DESC LIMIT 15", [$branchId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-rotate-left text-danger me-2"></i><?= __('payment_reversals') ?></h4>
        <p class="text-muted small mb-0">Authorized reversal of erroneously posted receipts with audit logging</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Active Payments Eligible for Reversal -->
    <div class="col-lg-7">
        <div class="card border rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-primary">Recent Active Receipts (Select to Reverse)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom small">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Customer & Contract</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activePayments as $p): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($p['receipt_number']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($p['customer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($p['contract_number']) ?></small>
                            </td>
                            <td><?= format_date($p['payment_date']) ?></td>
                            <td class="fw-bold text-success"><?= format_currency($p['amount']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="openReverseModal('<?= $p['id'] ?>', '<?= htmlspecialchars($p['receipt_number']) ?>', '<?= format_currency($p['amount']) ?>')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Reverse
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reversed Payments History -->
    <div class="col-lg-5">
        <div class="card border rounded-3 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-ban me-2"></i>Reversal Audit Log</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($reversedList)): ?>
                    <p class="text-muted text-center py-4 mb-0">No payment reversals recorded.</p>
                <?php else: ?>
                    <?php foreach ($reversedList as $rev): ?>
                    <div class="p-3 bg-light rounded-3 mb-2 border border-danger border-opacity-25 small">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-danger"><?= htmlspecialchars($rev['receipt_number']) ?></strong>
                            <span class="badge bg-danger text-white"><?= format_currency($rev['amount']) ?></span>
                        </div>
                        <div class="text-muted">Customer: <?= htmlspecialchars($rev['customer_name']) ?> (<?= htmlspecialchars($rev['contract_number']) ?>)</div>
                        <div class="mt-1"><strong>Reason:</strong> <em><?= htmlspecialchars($rev['reversal_reason']) ?></em></div>
                        <div class="text-muted small mt-1">Reversed by <?= htmlspecialchars($rev['rev_by'] ?? 'Admin') ?> on <?= format_date($rev['reversed_at'], 'd M Y h:i A') ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Reversal Reason -->
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="reverse">
                <input type="hidden" name="payment_id" id="modal-payment-id" value="">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Payment Reversal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to reverse receipt <strong id="modal-receipt-no"></strong> for amount <strong id="modal-amount"></strong>.</p>
                    <p class="text-danger small">This will restore the customer's outstanding balance, reverse installment schedules, and make an offsetting ledger entry.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Audit Reversal Reason <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" class="form-control" rows="3" required placeholder="Specify why this payment is being reversed (e.g. Posted to wrong contract, customer cheque bounced, cashier entry mistake)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm & Reverse Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReverseModal(id, receiptNo, amount) {
    document.getElementById('modal-payment-id').value = id;
    document.getElementById('modal-receipt-no').innerText = receiptNo;
    document.getElementById('modal-amount').innerText = amount;
    new bootstrap.Modal(document.getElementById('reverseModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
