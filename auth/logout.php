<?php
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_id'])) {
    audit_log('LOGOUT', 'Auth', $_SESSION['user_id'], 'User logged out');
}

session_unset();
session_destroy();

header("Location: " . BASE_URL . "/auth/login.php");
exit;
