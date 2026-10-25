<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/dashboard/index.php");
} else {
    header("Location: " . BASE_URL . "/auth/login.php");
}
exit;
