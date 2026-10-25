<?php
/**
 * Global Utility & Business Logic Helpers
 */

function format_currency(float|int|string|null $amount): string {
    $num = (float)($amount ?? 0);
    $curr = is_rtl() ? 'روپے ' : 'Rs. ';
    return $curr . number_format($num, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (!$date || $date === '0000-00-00') return '-';
    $time = strtotime($date);
    return date($format, $time);
}

function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $row = DB::fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            $cache[$key] = $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

function audit_log(string $action, string $module, ?int $record_id = null, string $details = ''): void {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $branch_id = current_branch_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        DB::insert('audit_logs', [
            'user_id' => $user_id,
            'branch_id' => $branch_id,
            'action' => $action,
            'module' => $module,
            'record_id' => $record_id,
            'details' => $details,
            'ip_address' => $ip
        ]);
    } catch (Exception $e) {
        // Silently continue to prevent breaking main transaction
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generate_contract_no(): string {
    $year = date('Y');
    $row = DB::fetch("SELECT COUNT(*) as cnt FROM installment_contracts WHERE YEAR(created_at) = ?", [$year]);
    $next = ($row['cnt'] ?? 0) + 1;
    return sprintf("INS-%s-%04d", $year, $next);
}

function generate_receipt_no(): string {
    $year = date('Y');
    $row = DB::fetch("SELECT COUNT(*) as cnt FROM payments WHERE YEAR(created_at) = ?", [$year]);
    $next = ($row['cnt'] ?? 0) + 1;
    return sprintf("RCP-%s-%05d", $year, $next);
}

function generate_customer_code(): string {
    $row = DB::fetch("SELECT COUNT(*) as cnt FROM customers");
    $next = ($row['cnt'] ?? 0) + 1001;
    return sprintf("CUST-%04d", $next);
}

function whatsapp_url(string $phone, string $message): string {
    $num = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($num, '03')) {
        $num = '92' . substr($num, 1);
    }
    return "https://api.whatsapp.com/send?phone=" . urlencode($num) . "&text=" . urlencode($message);
}
