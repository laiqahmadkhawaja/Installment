<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $val) {
        DB::query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $val, $val]);
    }
    audit_log('SETTINGS_UPDATED', 'Settings', null, "Updated company business settings");
    set_flash('success', "Business settings updated successfully.");
    header("Location: " . BASE_URL . "/system/settings.php");
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-gear text-primary me-2"></i><?= __('settings') ?> & Business Profile</h4>
        <p class="text-muted small mb-0">Configure company name, contact numbers, receipt headers, and installment late fee policies</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border rounded-3 shadow-sm p-4">
            <form method="POST" action="">
                <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-store me-2"></i>Company & Store Identity</h6>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Company Name (English) <span class="text-danger">*</span></label>
                        <input type="text" name="settings[company_name]" class="form-control" value="<?= htmlspecialchars(get_setting('company_name')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Company Name (اردو نام)</label>
                        <input type="text" name="settings[company_name_urdu]" class="form-control" value="<?= htmlspecialchars(get_setting('company_name_urdu')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Phone / Landline</label>
                        <input type="text" name="settings[company_phone]" class="form-control" value="<?= htmlspecialchars(get_setting('company_phone')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">WhatsApp Number (for Notifications)</label>
                        <input type="text" name="settings[company_whatsapp]" class="form-control" value="<?= htmlspecialchars(get_setting('company_whatsapp')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="settings[company_email]" class="form-control" value="<?= htmlspecialchars(get_setting('company_email')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Currency Symbol</label>
                        <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= htmlspecialchars(get_setting('currency_symbol', 'Rs.')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Store Address (Printed on Invoices & Receipts)</label>
                        <textarea name="settings[company_address]" class="form-control" rows="2"><?= htmlspecialchars(get_setting('company_address')) ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Overdue & Late Payment Policies</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Grace Period (Days before Overdue)</label>
                        <input type="number" name="settings[grace_period_days]" class="form-control" value="<?= htmlspecialchars(get_setting('grace_period_days', '5')) ?>" min="0">
                        <small class="text-muted">Days after due date before showing in overdue list.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Default Late Fee Charge (Rs.)</label>
                        <input type="number" name="settings[default_late_fee]" class="form-control" value="<?= htmlspecialchars(get_setting('default_late_fee', '200')) ?>" min="0">
                        <small class="text-muted">Leave 0 if business does not charge late payment fees.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 py-2 fw-bold shadow-sm">
                    <i class="fa-solid fa-check me-1"></i> Save Business Settings
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
