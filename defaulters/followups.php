<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$preContractId = (int)($_GET['contract_id'] ?? 0);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractId = (int)($_POST['contract_id'] ?? 0);
    $contactMethod = $_POST['contact_method'] ?? 'phone';
    $promiseDate = !empty($_POST['promise_to_pay_date']) ? $_POST['promise_to_pay_date'] : null;
    $promiseAmount = (float)($_POST['promise_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $contract = DB::fetch("SELECT * FROM installment_contracts WHERE id = ?", [$contractId]);

    if (!$contract || empty($notes)) {
        $error = "Please select a valid contract and provide detailed follow-up remarks.";
    } else {
        DB::insert('defaulter_followups', [
            'customer_id' => $contract['customer_id'],
            'contract_id' => $contractId,
            'officer_id' => $_SESSION['user_id'] ?? 1,
            'followup_date' => date('Y-m-d'),
            'contact_method' => $contactMethod,
            'promise_to_pay_date' => $promiseDate,
            'promise_amount' => $promiseAmount,
            'notes' => $notes,
            'status' => 'pending'
        ]);

        audit_log('FOLLOWUP_LOGGED', 'Defaulters', $contractId, "Logged recovery follow-up for Contract {$contract['contract_number']}");
        set_flash('success', "Follow-up notes & Promise-to-Pay recorded successfully.");
        header("Location: " . BASE_URL . "/defaulters/followups.php");
        exit;
    }
}

// Fetch active follow-ups
$followups = DB::fetchAll("SELECT f.*, 
    c.contract_number, 
    cust.full_name as customer_name, cust.phone, cust.customer_code,
    u.full_name as officer_name 
    FROM defaulter_followups f 
    JOIN installment_contracts c ON f.contract_id = c.id 
    JOIN customers cust ON f.customer_id = cust.id 
    LEFT JOIN users u ON f.officer_id = u.id 
    ORDER BY f.id DESC");

$contracts = DB::fetchAll("SELECT c.id, c.contract_number, cust.full_name as customer_name, cust.phone 
    FROM installment_contracts c 
    JOIN customers cust ON c.customer_id = cust.id 
    WHERE c.branch_id = ? AND c.status IN ('active', 'overdue', 'defaulted') 
    ORDER BY c.id DESC", [$branchId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-phone-volume text-primary me-2"></i><?= __('followups') ?> & Recovery Diary</h4>
        <p class="text-muted small mb-0">Record collection calls, field visits, promises-to-pay (PTP), and legal follow-ups</p>
    </div>
    <a href="<?= BASE_URL ?>/defaulters/index.php" class="btn btn-outline-danger btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Overdue List
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Log New Follow-up Form -->
    <div class="col-lg-5">
        <div class="card border rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-pen-nib me-2"></i>Log New Follow-up</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contract / Customer <span class="text-danger">*</span></label>
                        <select name="contract_id" class="form-select" required>
                            <option value="">-- Choose Contract --</option>
                            <?php foreach ($contracts as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $preContractId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['contract_number']) ?> &bull; <?= htmlspecialchars($c['customer_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Method</label>
                        <select name="contact_method" class="form-select">
                            <option value="phone" selected>Phone Call (فون کال)</option>
                            <option value="whatsapp">WhatsApp Message</option>
                            <option value="visit">Field / Home Visit (گھر کا دورہ)</option>
                            <option value="letter">Notice / Letter</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-warning">Promise-to-Pay (PTP) Date</label>
                        <input type="date" name="promise_to_pay_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-warning">Promised Payment Amount (Rs.)</label>
                        <input type="number" name="promise_amount" class="form-control" placeholder="e.g. 15000" step="500">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Follow-up Remarks / Details <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Outcome of the conversation, customer reason for delay, guarantor contacted..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Follow-up Note
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Past Follow-up Diary -->
    <div class="col-lg-7">
        <div class="card border rounded-3 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Recovery Follow-up History</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($followups)): ?>
                    <p class="text-muted text-center py-4 mb-0">No follow-up notes logged yet.</p>
                <?php else: ?>
                    <?php foreach ($followups as $f): ?>
                    <div class="p-3 bg-light rounded-3 mb-3 border small">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-primary"><?= htmlspecialchars($f['customer_name']) ?> (<?= htmlspecialchars($f['contract_number']) ?>)</strong>
                            <span class="badge bg-secondary text-uppercase"><?= $f['contact_method'] ?></span>
                        </div>
                        <div class="text-muted mb-2">Officer: <?= htmlspecialchars($f['officer_name'] ?? 'Admin') ?> &bull; Logged: <?= format_date($f['followup_date']) ?></div>
                        <p class="mb-2 bg-white p-2 rounded border"><?= nl2br(htmlspecialchars($f['notes'])) ?></p>
                        <?php if ($f['promise_to_pay_date']): ?>
                        <div class="text-danger fw-semibold">
                            <i class="fa-solid fa-hand-holding-dollar me-1"></i> PTP Promised: <strong><?= format_currency($f['promise_amount']) ?></strong> on <strong><?= format_date($f['promise_to_pay_date']) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
