<?php
require_once __DIR__ . '/../config/config.php';

$receiptNo = trim($_GET['rcp'] ?? '');
$payment = null;

if (!empty($receiptNo)) {
    $payment = DB::fetch("SELECT p.*, 
        c.contract_number, c.balance_amount as contract_balance,
        cust.full_name as customer_name, cust.cnic as customer_cnic,
        b.name as branch_name, b.phone as branch_phone
        FROM payments p
        JOIN installment_contracts c ON p.contract_id = c.id
        JOIN customers cust ON p.customer_id = cust.id
        LEFT JOIN branches b ON p.branch_id = b.id
        WHERE p.receipt_number = ?", [$receiptNo]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Verification - <?= htmlspecialchars(get_setting('company_name')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .verify-card { background: #ffffff; max-width: 500px; width: 100%; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 35px; }
    </style>
</head>
<body>
    <div class="verify-card text-center">
        <?php if ($payment): ?>
            <div class="text-success mb-3">
                <i class="fa-solid fa-circle-check fa-4x"></i>
            </div>
            <h4 class="fw-bold mb-1">Official Receipt Verified!</h4>
            <p class="text-muted small">This payment receipt is authentic and recorded in <?= htmlspecialchars(get_setting('company_name')) ?></p>

            <div class="text-start bg-light p-3 rounded-3 mb-4 small border">
                <div class="mb-2"><strong>Receipt #:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($payment['receipt_number']) ?></span></div>
                <div class="mb-2"><strong>Payment Date:</strong> <?= format_date($payment['payment_date']) ?></div>
                <div class="mb-2"><strong>Amount Paid:</strong> <span class="text-success fw-bold"><?= format_currency($payment['amount']) ?></span></div>
                <div class="mb-2"><strong>Payment Mode:</strong> <span class="text-uppercase"><?= str_replace('_', ' ', $payment['payment_method']) ?></span></div>
                <div class="mb-2"><strong>Contract #:</strong> <?= htmlspecialchars($payment['contract_number']) ?></div>
                <div class="mb-2"><strong>Customer:</strong> <?= htmlspecialchars($payment['customer_name']) ?></div>
                <div class="mb-2"><strong>Masked CNIC:</strong> <?= substr($payment['customer_cnic'], 0, 5) . '-XXXXXXX-' . substr($payment['customer_cnic'], -1) ?></div>
                <div class="mb-0"><strong>Status:</strong> <?= $payment['is_reversed'] ? '<span class="text-danger fw-bold">REVERSED</span>' : '<span class="text-success fw-bold">SETTLED & VALID</span>' ?></div>
            </div>

            <div class="text-muted small">
                <strong>Issuing Branch:</strong> <?= htmlspecialchars($payment['branch_name'] ?? 'Main Branch') ?><br>
                For inquiries call: <?= htmlspecialchars($payment['branch_phone'] ?? get_setting('company_phone')) ?>
            </div>
        <?php else: ?>
            <div class="text-danger mb-3">
                <i class="fa-solid fa-triangle-exclamation fa-4x"></i>
            </div>
            <h4 class="fw-bold mb-1">Invalid Receipt</h4>
            <p class="text-muted small">No payment record found matching this receipt number.</p>
        <?php endif; ?>
    </div>
</body>
</html>
