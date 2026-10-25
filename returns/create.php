<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$contracts = DB::fetchAll("SELECT c.*, cust.full_name as customer_name, cust.phone 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.branch_id = ? AND c.status IN ('active', 'overdue') 
    ORDER BY c.id DESC", [$branchId]);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractId = (int)($_POST['contract_id'] ?? 0);
    $returnType = $_POST['return_type'] ?? 'full_cancellation';
    $refundAmount = (float)($_POST['refund_amount'] ?? 0);
    $cancellationFee = (float)($_POST['cancellation_fee'] ?? 0);
    $restock = isset($_POST['restock_product']) ? 1 : 0;
    $reason = trim($_POST['reason'] ?? '');

    $contract = DB::fetch("SELECT * FROM installment_contracts WHERE id = ?", [$contractId]);

    if (!$contract || empty($reason)) {
        $error = "Please select a contract and provide an authorized cancellation reason.";
    } else {
        DB::beginTransaction();
        try {
            // 1. Insert return record
            DB::insert('returns_cancellations', [
                'branch_id' => $branchId,
                'contract_id' => $contractId,
                'customer_id' => $contract['customer_id'],
                'return_type' => $returnType,
                'return_date' => date('Y-m-d'),
                'refund_amount' => $refundAmount,
                'cancellation_fee' => $cancellationFee,
                'reason' => $reason,
                'processed_by' => $_SESSION['user_id'] ?? 1
            ]);

            // 2. Restock product if returned
            if ($restock) {
                $items = DB::fetchAll("SELECT product_id, quantity FROM contract_items WHERE contract_id = ?", [$contractId]);
                foreach ($items as $item) {
                    DB::query("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$item['quantity'], $item['product_id']]);
                }
            }

            // 3. Mark contract as cancelled
            DB::update('installment_contracts', [
                'status' => 'cancelled',
                'notes' => CONCAT_WS(' | ', $contract['notes'], "Cancelled on " . date('Y-m-d') . ": " . $reason)
            ], 'id = ?', [$contractId]);

            // 4. Mark remaining schedules
            DB::query("UPDATE installment_schedules SET status = 'pending', notes = 'Contract Cancelled' WHERE contract_id = ? AND status != 'paid'", [$contractId]);

            // 5. Customer Ledger Entry
            DB::insert('customer_ledgers', [
                'customer_id' => $contract['customer_id'],
                'contract_id' => $contractId,
                'branch_id' => $branchId,
                'transaction_type' => 'refund',
                'transaction_date' => date('Y-m-d'),
                'reference_no' => $contract['contract_number'],
                'description' => "Contract Cancellation & Refund: {$reason}",
                'debit' => 0.00,
                'credit' => 0.00,
                'balance' => 0.00,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);

            // 6. Cashbook outflow for refund
            if ($refundAmount > 0) {
                DB::insert('cashbook', [
                    'branch_id' => $branchId,
                    'entry_date' => date('Y-m-d'),
                    'account_type' => 'cash',
                    'type' => 'outflow',
                    'source' => 'refund',
                    'reference_no' => $contract['contract_number'],
                    'amount' => $refundAmount,
                    'description' => "Refund to customer for cancelled contract {$contract['contract_number']}",
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);
            }

            DB::commit();
            audit_log('CONTRACT_CANCELLED', 'Returns', $contractId, "Cancelled contract {$contract['contract_number']} Reason: {$reason}");
            set_flash('success', "Contract <strong>{$contract['contract_number']}</strong> has been cancelled and recorded.");
            header("Location: " . BASE_URL . "/returns/index.php");
            exit;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error cancelling contract: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-danger"><i class="fa-solid fa-ban me-2"></i>Process Return / Contract Cancellation</h4>
        <p class="text-muted small mb-0">Cancel installment plan, restore product inventory, and issue customer refunds</p>
    </div>
    <a href="<?= BASE_URL ?>/returns/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Returns
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border rounded-3 shadow-sm p-4">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Active Contract to Cancel <span class="text-danger">*</span></label>
                    <select name="contract_id" class="form-select form-select-lg" required>
                        <option value="">-- Choose Contract --</option>
                        <?php foreach ($contracts as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['contract_number']) ?> &bull; <?= htmlspecialchars($c['customer_name']) ?> (Total Paid: Rs. <?= number_format($c['total_paid']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Refund Amount to Customer (Rs.)</label>
                        <input type="number" name="refund_amount" class="form-control" value="0" step="500">
                        <small class="text-muted">Cash paid back to customer.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Deducted Cancellation Fee (Rs.)</label>
                        <input type="number" name="cancellation_fee" class="form-control" value="0" step="100">
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="restock_product" class="form-check-input" id="restockCheck" checked>
                    <label class="form-check-label fw-semibold" for="restockCheck">Restock Product back into Inventory (+1 Qty)</label>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Authorized Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Reason for cancellation (e.g. Customer returned product due to relocation, agreement revoked with mutual consent)..."></textarea>
                </div>

                <button type="submit" class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow" onclick="return confirm('Are you sure you want to permanently cancel this contract?');">
                    <i class="fa-solid fa-ban me-1"></i> Confirm Contract Cancellation & Save
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
