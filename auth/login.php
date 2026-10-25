<?php
require_once __DIR__ . '/../config/config.php';

$error = '';
if (is_logged_in()) {
    header("Location: " . BASE_URL . "/dashboard/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $user = DB::fetch("SELECT u.*, r.role_name, r.permissions FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'", [$username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['installment_user_id'] = $user['id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['branch_id'] = $user['branch_id'] ?? 1;
            $_SESSION['active_branch_id'] = $user['branch_id'] ?? 1;
            $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];

            // Update last login
            DB::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            audit_log('LOGIN', 'Auth', $user['id'], 'User logged into system');

            $ref = $_GET['ref'] ?? '';
            if (empty($ref) || strpos($ref, '/auth/login.php') !== false || strpos($ref, '/auth/dashboard') !== false) {
                $ref = BASE_URL . '/dashboard/index.php';
            }
            header("Location: " . $ref);
            exit;
        } else {
            $error = "Invalid username or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= get_setting('company_name', 'Installment Pro') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
            padding: 40px;
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            background: #2563eb;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 16px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-icon">
                <i class="fa-solid fa-coins"></i>
            </div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars(get_setting('company_name', 'Installment Pro')) ?></h4>
            <p class="text-muted small">Sign in to manage installment plans & collections</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username / یوزر نیم</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" value="admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-semibold mb-0">Password / پاس ورڈ</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Sign In &rarr;
            </button>
        </form>

        <div class="mt-4 p-3 bg-light rounded text-center small text-muted">
            <div><strong>Default Admin:</strong> <code>admin</code> / <code>admin123</code></div>
            <div><strong>Cashier:</strong> <code>aliraza</code> / <code>admin123</code></div>
        </div>
    </div>
</body>
</html>
