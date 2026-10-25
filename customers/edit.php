<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$customer = DB::fetch("SELECT * FROM customers WHERE id = ?", [$id]);

if (!$customer) {
    set_flash('danger', 'Customer not found.');
    header("Location: " . BASE_URL . "/customers/index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $fatherHusband = trim($_POST['father_husband_name'] ?? '');
    $urduName = trim($_POST['urdu_name'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $altPhone = trim($_POST['alt_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentAddress = trim($_POST['current_address'] ?? '');
    $permanentAddress = trim($_POST['permanent_address'] ?? '');
    $city = trim($_POST['city'] ?? 'Lahore');
    $occupation = trim($_POST['occupation'] ?? '');
    $employerName = trim($_POST['employer_name'] ?? '');
    $monthlyIncome = (float)($_POST['monthly_income'] ?? 0);
    $creditLimit = (float)($_POST['credit_limit'] ?? 200000);
    $category = $_POST['category'] ?? 'regular';
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($fullName) || empty($cnic) || empty($phone) || empty($currentAddress)) {
        $error = "Please fill in all required customer fields.";
    } else {
        try {
            DB::update('customers', [
                'full_name' => $fullName,
                'father_husband_name' => $fatherHusband,
                'urdu_name' => $urduName,
                'cnic' => $cnic,
                'phone' => $phone,
                'whatsapp' => $whatsapp ?: $phone,
                'alt_phone' => $altPhone,
                'email' => $email,
                'current_address' => $currentAddress,
                'permanent_address' => $permanentAddress,
                'city' => $city,
                'occupation' => $occupation,
                'employer_name' => $employerName,
                'monthly_income' => $monthlyIncome,
                'credit_limit' => $creditLimit,
                'category' => $category,
                'status' => $status,
                'notes' => $notes
            ], 'id = ?', [$id]);

            audit_log('CUSTOMER_UPDATED', 'Customers', $id, "Updated customer details: {$fullName}");
            set_flash('success', "Customer details updated successfully.");
            header("Location: " . BASE_URL . "/customers/view.php?id={$id}");
            exit;
        } catch (Exception $e) {
            $error = "Update error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-pen text-primary me-2"></i>Edit Customer Details</h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars($customer['full_name']) ?> &bull; <?= htmlspecialchars($customer['customer_code']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary">Personal Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($customer['full_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Father / Husband Name</label>
                            <input type="text" name="father_husband_name" class="form-control" value="<?= htmlspecialchars($customer['father_husband_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Urdu Name (اردو نام)</label>
                            <input type="text" name="urdu_name" class="form-control" value="<?= htmlspecialchars($customer['urdu_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">CNIC Number <span class="text-danger">*</span></label>
                            <input type="text" name="cnic" class="form-control" required value="<?= htmlspecialchars($customer['cnic']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Primary Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($customer['phone']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">WhatsApp Number</label>
                            <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($customer['whatsapp']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Alternate Contact</label>
                            <input type="text" name="alt_phone" class="form-control" value="<?= htmlspecialchars($customer['alt_phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($customer['city']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Current Address <span class="text-danger">*</span></label>
                            <textarea name="current_address" class="form-control" rows="2" required><?= htmlspecialchars($customer['current_address']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2"><?= htmlspecialchars($customer['permanent_address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0">Category & Status</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach (['new', 'regular', 'premium', 'high_risk', 'defaulter', 'blacklisted'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $customer['category'] === $cat ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cat)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="blacklisted" <?= $customer['status'] === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Credit Limit (Rs.)</label>
                        <input type="number" name="credit_limit" class="form-control" value="<?= (float)$customer['credit_limit'] ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Occupation</label>
                        <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($customer['occupation']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Employer / Business</label>
                        <input type="text" name="employer_name" class="form-control" value="<?= htmlspecialchars($customer['employer_name']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monthly Income (Rs.)</label>
                        <input type="number" name="monthly_income" class="form-control" value="<?= (float)$customer['monthly_income'] ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($customer['notes']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-check me-1"></i> Update Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
