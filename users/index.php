<?php
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int)($_POST['role_id'] ?? 3);
    $branchId = (int)($_POST['branch_id'] ?? 1);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($fullName) || empty($username) || empty($password)) {
        $error = "Please fill in all required user fields.";
    } else {
        $dup = DB::fetch("SELECT id FROM users WHERE username = ?", [$username]);
        if ($dup) {
            $error = "This username is already taken. Please choose another.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            DB::insert('users', [
                'branch_id' => $branchId,
                'role_id' => $roleId,
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => $hash,
                'status' => 'active'
            ]);
            audit_log('USER_CREATED', 'Users', null, "Created user account: {$username}");
            set_flash('success', "User <strong>{$username}</strong> added successfully.");
            header("Location: " . BASE_URL . "/users/index.php");
            exit;
        }
    }
}

$users = DB::fetchAll("SELECT u.*, r.role_name, b.name as branch_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN branches b ON u.branch_id = b.id 
    ORDER BY u.id ASC");

$roles = DB::fetchAll("SELECT * FROM roles ORDER BY id ASC");
$branches = DB::fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY id ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-shield text-primary me-2"></i><?= __('users') ?> & Permissions</h4>
        <p class="text-muted small mb-0">Role-based access control (Super Admin, Branch Manager, Salesperson, Cashier, Recovery Officer)</p>
    </div>
    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
        <i class="fa-solid fa-user-plus me-1"></i> Add System User
    </button>
</div>

<?php if ($error): ?>
<div class="alert alert-danger shadow-sm py-2 px-3 mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>User / Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Assigned Branch</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($u['email'] ?: '-') ?></small>
                    </td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><span class="badge bg-primary text-uppercase"><?= htmlspecialchars($u['role_name']) ?></span></td>
                    <td><?= htmlspecialchars($u['branch_name'] ?? 'Main Branch') ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($u['status']) ?></span>
                    </td>
                    <td class="small text-muted"><?= $u['last_login'] ? format_date($u['last_login'], 'd M Y h:i A') : 'Never' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="add_user" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create System User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Employee Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. Asad Ali">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="asadali">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Password">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">System Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Branch</label>
                            <select name="branch_id" class="form-select">
                                <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="0300-1122334">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="asad@shop.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create User Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
