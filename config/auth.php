<?php
/**
 * Authentication & Authorization Helper
 */

function is_logged_in(): bool {
    $userId = $_SESSION['installment_user_id'] ?? $_SESSION['user_id'] ?? null;
    return !empty($userId);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    $userId = (int)($_SESSION['installment_user_id'] ?? $_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    // If session data is incomplete, automatically refresh from database
    if (empty($_SESSION['username']) || empty($_SESSION['role_name']) || empty($_SESSION['full_name'])) {
        try {
            $user = DB::fetch("SELECT u.*, r.role_name, r.permissions 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ? AND u.status = 'active'", [$userId]);

            if ($user) {
                $_SESSION['installment_user_id'] = $user['id'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['branch_id'] = $user['branch_id'] ?? 1;
                $_SESSION['active_branch_id'] = $_SESSION['active_branch_id'] ?? ($user['branch_id'] ?? 1);
                $_SESSION['permissions'] = json_decode($user['permissions'], true) ?? [];
            } else {
                // Invalid user ID in session (from another localhost app); clear session
                unset($_SESSION['installment_user_id'], $_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['role_id'], $_SESSION['role_name'], $_SESSION['permissions']);
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    return [
        'id' => $_SESSION['installment_user_id'] ?? $_SESSION['user_id'] ?? $userId,
        'username' => $_SESSION['username'] ?? 'User',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'role_id' => $_SESSION['role_id'] ?? 1,
        'role_name' => $_SESSION['role_name'] ?? 'User',
        'branch_id' => $_SESSION['branch_id'] ?? 1,
        'permissions' => $_SESSION['permissions'] ?? []
    ];
}

function current_branch_id(): int {
    return (int)($_SESSION['active_branch_id'] ?? 1);
}

function require_login(): void {
    if (!is_logged_in() || current_user() === null) {
        $return_url = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header("Location: " . BASE_URL . "/auth/login.php?ref=" . $return_url);
        exit;
    }
}
