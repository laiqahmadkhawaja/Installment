<?php
/**
 * Installment Management System - Application Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('INSTALLMENT_PRO_SESSID');
    session_start();
}

// Enable output buffering to prevent headers already sent issues
if (!ob_get_level()) {
    ob_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'installment_db');

// Base URL detection
if (php_sapi_name() === 'cli') {
    define('BASE_URL', '/Installment');
} else {
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)));

    $base_path = '';
    if (!empty($docRoot) && strpos($appRoot, $docRoot) === 0) {
        $base_path = substr($appRoot, strlen($docRoot));
    }

    // Preserve exact case from user's URL request (e.g. /installment vs /Installment)
    if (preg_match('#^/(installment)(/|$)#i', $_SERVER['REQUEST_URI'] ?? '', $matches)) {
        $base_path = '/' . $matches[1];
    } elseif ($base_path === '' && preg_match('#^/([^/]+)#', $_SERVER['SCRIPT_NAME'] ?? '', $matches)) {
        if (stripos($appRoot, '/' . $matches[1]) !== false) {
            $base_path = '/' . $matches[1];
        }
    }

    define('BASE_URL', rtrim($base_path, '/'));
}

// Language setting: 'en' or 'ur'
if (!isset($_SESSION['app_lang'])) {
    $_SESSION['app_lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ur'])) {
    $_SESSION['app_lang'] = $_GET['lang'];
}

// Theme mode: 'light' or 'dark'
if (!isset($_SESSION['theme_mode'])) {
    $_SESSION['theme_mode'] = 'light';
}
if (isset($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'])) {
    $_SESSION['theme_mode'] = $_GET['theme'];
}

// Default Branch Setting
if (!isset($_SESSION['active_branch_id'])) {
    $_SESSION['active_branch_id'] = 1; // Main Branch default
}
if (isset($_GET['switch_branch'])) {
    $_SESSION['active_branch_id'] = (int)$_GET['switch_branch'];
}

// Date & Timezone
date_default_timezone_set('Asia/Karachi');

// Autoload core helpers
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
