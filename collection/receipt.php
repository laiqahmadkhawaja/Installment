<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$payment = DB::fetch("SELECT p.*, 
    c.contract_number, c.installment_price_total, c.total_paid as contract_total_paid, c.balance_amount as contract_balance,
    cust.full_name as customer_name, cust.urdu_name as customer_urdu, cust.phone as customer_phone, cust.cnic as customer_cnic, cust.customer_code,
    u.full_name as cashier_name,
    b.name as branch_name, b.phone as branch_phone, b.address as branch_address
    FROM payments p
    JOIN installment_contracts c ON p.contract_id = c.id
    JOIN customers cust ON p.customer_id = cust.id
    LEFT JOIN users u ON p.collected_by = u.id
    LEFT JOIN branches b ON p.branch_id = b.id
    WHERE p.id = ?", [$id]);

if (!$payment) {
    die("Receipt not found.");
}

// Allocations
$allocations = DB::fetchAll("SELECT pa.*, s.installment_number, s.due_date 
    FROM payment_allocations pa 
    JOIN installment_schedules s ON pa.schedule_id = s.id 
    WHERE pa.payment_id = ?", [$id]);

// Verification URL for QR code
$verifyUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . "/verify/receipt.php?rcp=" . urlencode($payment['receipt_number']);
$qrApi = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($verifyUrl);

// WhatsApp message template
$waMsg = "Dear " . $payment['customer_name'] . ", your payment of " . format_currency($payment['amount']) . " for Contract " . $payment['contract_number'] . " has been received with thanks (Receipt: " . $payment['receipt_number'] . "). Remaining Balance: " . format_currency($payment['contract_balance']) . ". " . get_setting('company_name');
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title>Receipt # <?= htmlspecialchars($payment['receipt_number']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <?php if (is_rtl()): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/rtl.css">
    <?php endif; ?>
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 25px 0; }
        .receipt-card { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.06); padding: 30px; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .receipt-card { box-shadow: none; max-width: 100%; border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Actions -->
        <div class="text-center mb-4 no-print d-flex justify-content-center gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-primary fw-semibold shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Print A4/A5 Receipt
            </button>
            <a href="<?= whatsapp_url($payment['customer_phone'], $waMsg) ?>" target="_blank" class="btn btn-success fw-semibold shadow-sm">
                <i class="fa-brands fa-whatsapp me-1"></i> Send WhatsApp Receipt
            </a>
            <a href="<?= BASE_URL ?>/contracts/view.php?id=<?= $payment['contract_id'] ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Contract
            </a>
            <a href="<?= BASE_URL ?>/collection/receive.php" class="btn btn-dark">
                + Next Payment
            </a>
        </div>

        <div class="receipt-card">
            <!-- Header -->
            <div class="text-center border-bottom pb-3 mb-3">
                <h4 class="fw-bold mb-1"><?= htmlspecialchars(get_setting('company_name', 'Installment Management System')) ?></h4>
                <?php if (get_setting('company_name_urdu')): ?>
                    <div class="fw-semibold text-muted"><?= htmlspecialchars(get_setting('company_name_urdu')) ?></div>
                <?php endif; ?>
                <div class="small text-muted"><?= htmlspecialchars(get_setting('company_address')) ?> &bull; Phone: <?= htmlspecialchars(get_setting('company_phone')) ?></div>
                <div class="badge bg-dark mt-2 text-uppercase px-3 py-2">Payment Collection Receipt / رسید ادائیگی</div>
            </div>

            <!-- Receipt Meta -->
            <div class="d-flex justify-content-between mb-3 small">
                <div>
                    <div><strong>Receipt #:</strong> <span class="fw-bold text-primary"><?= htmlspecialchars($payment['receipt_number']) ?></span></div>
                    <div><strong>Date & Time:</strong> <?= format_date($payment['payment_date']) ?></div>
                    <div><strong>Payment Mode:</strong> <span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $payment['payment_method']) ?></span></div>
                </div>
                <div class="text-end">
                    <div><strong>Contract #:</strong> <span class="fw-bold"><?= htmlspecialchars($payment['contract_number']) ?></span></div>
                    <div><strong>Branch:</strong> <?= htmlspecialchars($payment['branch_name'] ?? 'Main') ?></div>
                    <div><strong>Cashier:</strong> <?= htmlspecialchars($payment['cashier_name'] ?? 'Admin') ?></div>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="p-3 bg-light rounded-3 mb-3 small border">
                <div class="row g-1">
                    <div class="col-sm-6"><strong>Customer Name:</strong> <?= htmlspecialchars($payment['customer_name']) ?></div>
                    <div class="col-sm-6"><strong>Customer ID:</strong> <?= htmlspecialchars($payment['customer_code']) ?></div>
                    <div class="col-sm-6"><strong>CNIC Number:</strong> <?= htmlspecialchars($payment['customer_cnic']) ?></div>
                    <div class="col-sm-6"><strong>Phone:</strong> <?= htmlspecialchars($payment['customer_phone']) ?></div>
                </div>
            </div>

            <!-- Allocation details -->
            <table class="table table-bordered table-sm small mb-3">
                <thead class="table-light">
                    <tr>
                        <th>Description / Installment</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount Allocated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allocations)): ?>
                    <tr>
                        <td colspan="2">Installment Payment against Contract <?= htmlspecialchars($payment['contract_number']) ?></td>
                        <td class="text-end fw-bold"><?= format_currency($payment['amount']) ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($allocations as $a): ?>
                        <tr>
                            <td>Installment #<?= $a['installment_number'] ?></td>
                            <td><?= format_date($a['due_date']) ?></td>
                            <td class="text-end fw-bold"><?= format_currency($a['allocated_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td colspan="2" class="text-end text-uppercase">Total Received:</td>
                        <td class="text-end text-success h6 mb-0"><?= format_currency($payment['amount']) ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Remaining Balance Summary -->
            <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 mb-3 bg-white">
                <div>
                    <span class="text-muted small d-block">Remaining Contract Balance:</span>
                    <strong class="h5 <?= $payment['contract_balance'] > 0 ? 'text-danger' : 'text-success' ?> mb-0">
                        <?= format_currency($payment['contract_balance']) ?>
                    </strong>
                </div>
                <div>
                    <img src="<?= $qrApi ?>" alt="QR Code" width="64" height="64" class="border p-1 rounded">
                </div>
            </div>

            <div class="text-muted small text-center border-top pt-3">
                <em>Thank you for your prompt installment payment!</em><br>
                This is a computer-generated receipt and requires no physical signature.
            </div>
        </div>
    </div>
</body>
</html>
