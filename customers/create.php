<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$error = '';
$success = '';

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
    $category = $_POST['category'] ?? 'new';
    $notes = trim($_POST['notes'] ?? '');

    // Guarantor 1
    $g1Name = trim($_POST['g1_name'] ?? '');
    $g1Cnic = trim($_POST['g1_cnic'] ?? '');
    $g1Phone = trim($_POST['g1_phone'] ?? '');
    $g1Relation = trim($_POST['g1_relation'] ?? '');
    $g1Address = trim($_POST['g1_address'] ?? '');

    if (empty($fullName) || empty($cnic) || empty($phone) || empty($currentAddress)) {
        $error = "Please fill in all required customer fields (Name, CNIC, Mobile, Current Address).";
    } else {
        // Duplicate check
        $dup = DB::fetch("SELECT id FROM customers WHERE cnic = ? OR phone = ?", [$cnic, $phone]);
        if ($dup) {
            $error = "A customer with this CNIC number or Mobile phone already exists in the system!";
        } else {
            DB::beginTransaction();
            try {
                $code = generate_customer_code();
                $customerId = DB::insert('customers', [
                    'branch_id' => $branchId,
                    'customer_code' => $code,
                    'full_name' => $fullName,
                    'father_husband_name' => $fatherHusband,
                    'urdu_name' => $urduName,
                    'cnic' => $cnic,
                    'phone' => $phone,
                    'whatsapp' => !empty($whatsapp) ? $whatsapp : $phone,
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
                    'notes' => $notes,
                    'created_by' => $_SESSION['user_id'] ?? 1
                ]);

                // Insert Guarantor 1 if provided
                if (!empty($g1Name) && !empty($g1Phone)) {
                    DB::insert('customer_guarantors', [
                        'customer_id' => $customerId,
                        'full_name' => $g1Name,
                        'cnic' => $g1Cnic,
                        'phone' => $g1Phone,
                        'whatsapp' => $g1Phone,
                        'address' => $g1Address ?: $currentAddress,
                        'relationship' => $g1Relation ?: 'Relative',
                        'occupation' => 'Employed'
                    ]);
                }

                DB::commit();
                audit_log('CUSTOMER_CREATED', 'Customers', $customerId, "Registered customer: {$fullName} ({$code})");
                set_flash('success', "Customer <strong>{$fullName}</strong> successfully registered with Code: <strong>{$code}</strong>");
                echo "<script>window.location.href = '" . BASE_URL . "/customers/view.php?id={$customerId}';</script>";
                exit;
            } catch (Exception $e) {
                DB::rollBack();
                $error = "Error saving customer: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-plus text-primary me-2"></i><?= __('new_customer') ?></h4>
        <p class="text-muted small mb-0">Register a new customer and primary guarantor for installment purchases</p>
    </div>
    <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4">
    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row g-4">
        <!-- Customer Personal Information -->
        <div class="col-lg-8">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-id-card me-2"></i>Customer Identification & Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Muhammad Farooq" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Father / Husband Name</label>
                            <input type="text" name="father_husband_name" class="form-control" placeholder="e.g. Abdul Hameed" value="<?= htmlspecialchars($_POST['father_husband_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Urdu Name (اردو نام)</label>
                            <input type="text" name="urdu_name" class="form-control" placeholder="مثال: محمد فاروق" value="<?= htmlspecialchars($_POST['urdu_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">CNIC Number (قومی شناختی کارڈ) <span class="text-danger">*</span></label>
                            <input type="text" name="cnic" class="form-control" required placeholder="35201-1234567-1" value="<?= htmlspecialchars($_POST['cnic'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Primary Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="0300-1234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">WhatsApp Number</label>
                            <input type="text" name="whatsapp" class="form-control" placeholder="03001234567" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Alternate Contact / PTCL</label>
                            <input type="text" name="alt_phone" class="form-control" placeholder="042-35889900" value="<?= htmlspecialchars($_POST['alt_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="customer@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City / علاقہ</label>
                            <input type="text" name="city" class="form-control" value="Lahore" value="<?= htmlspecialchars($_POST['city'] ?? 'Lahore') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Current Residential Address (موجودہ رہائشی پتہ) <span class="text-danger">*</span></label>
                            <textarea name="current_address" class="form-control" rows="2" required placeholder="House #, Street #, Mohallah, Area"><?= htmlspecialchars($_POST['current_address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Permanent Address (مستقل پتہ)</label>
                            <textarea name="permanent_address" class="form-control" rows="2" placeholder="Permanent Home / Village Address"><?= htmlspecialchars($_POST['permanent_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Guarantor Information -->
            <div class="card border rounded-3 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-success"><i class="fa-solid fa-user-shield me-2"></i>Primary Guarantor Information (ضامن کی معلومات)</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Guarantor Full Name</label>
                            <input type="text" name="g1_name" class="form-control" placeholder="e.g. Tariq Mehmood" value="<?= htmlspecialchars($_POST['g1_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Guarantor CNIC #</label>
                            <input type="text" name="g1_cnic" class="form-control" placeholder="35201-9988771-3" value="<?= htmlspecialchars($_POST['g1_cnic'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Guarantor Mobile #</label>
                            <input type="text" name="g1_phone" class="form-control" placeholder="0301-2233441" value="<?= htmlspecialchars($_POST['g1_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Relationship with Customer</label>
                            <input type="text" name="g1_relation" class="form-control" placeholder="e.g. Brother, Uncle, Colleague" value="<?= htmlspecialchars($_POST['g1_relation'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Guarantor Complete Address</label>
                            <input type="text" name="g1_address" class="form-control" placeholder="Guarantor residential address" value="<?= htmlspecialchars($_POST['g1_address'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Eligibility, Occupation & Category -->
        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-shield-halved me-2"></i>Eligibility & Credit Limit</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer Category</label>
                        <select name="category" class="form-select">
                            <option value="new" selected>New Customer</option>
                            <option value="regular">Regular Customer</option>
                            <option value="premium">Premium Customer (Approved)</option>
                            <option value="high_risk">High Risk</option>
                            <option value="defaulter">Defaulter</option>
                            <option value="blacklisted">Blacklisted</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer Credit Limit (Rs.)</label>
                        <input type="number" name="credit_limit" class="form-control" value="250000" step="5000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Occupation / پیشہ</label>
                        <input type="text" name="occupation" class="form-control" placeholder="e.g. Govt Teacher, Businessman" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Employer or Business Name</label>
                        <input type="text" name="employer_name" class="form-control" placeholder="Company / Business Name" value="<?= htmlspecialchars($_POST['employer_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Estimated Monthly Income (Rs.)</label>
                        <input type="number" name="monthly_income" class="form-control" placeholder="e.g. 75000" value="<?= htmlspecialchars($_POST['monthly_income'] ?? '60000') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Internal Remarks / Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Reference notes, verification status..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Register Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
