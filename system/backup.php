<?php
require_once __DIR__ . '/../config/config.php';
require_login();

// Handle Database Backup Download
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $tables = [];
    $pdo = DB::getConnection();
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $dump = "-- Installment Pro Database Backup\n";
    $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $row2 = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $dump .= "\n\n" . $row2[1] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $dump .= "INSERT INTO `{$table}` VALUES\n";
            $valuesArr = [];
            foreach ($rows as $row) {
                $escaped = array_map(function ($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                $valuesArr[] = "(" . implode(", ", $escaped) . ")";
            }
            $dump .= implode(",\n", $valuesArr) . ";\n";
        }
    }

    $dump .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    $filename = "installment_db_backup_" . date('Y-m-d_His') . ".sql";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Length: ' . strlen($dump));
    echo $dump;
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-database text-primary me-2"></i><?= __('backup') ?> & Database Maintenance</h4>
        <p class="text-muted small mb-0">Export complete MySQL database snapshot, customer ledgers, and transactions</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border rounded-3 shadow-sm p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="fa-solid fa-download fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Create Immediate Database Backup</h5>
                    <small class="text-muted">Export complete `.sql` archive</small>
                </div>
            </div>
            <p class="text-muted small">
                Generates a complete, consistent MySQL dump containing all customers, active and completed contracts, payment collections, cashbook, and audit history.
            </p>
            <a href="?action=download" class="btn btn-success fw-bold py-2 px-4 shadow-sm">
                <i class="fa-solid fa-file-arrow-down me-1"></i> Download Database Backup (.SQL)
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border rounded-3 shadow-sm p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                    <i class="fa-solid fa-shield-halved fa-2x"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Data Safety Guidelines</h5>
                    <small class="text-muted">Best practices for installment stores</small>
                </div>
            </div>
            <ul class="text-muted small ps-3 mb-0">
                <li class="mb-2">Download a database backup at the end of every business day.</li>
                <li class="mb-2">Keep an off-site copy on Google Drive, Dropbox, or a USB drive.</li>
                <li class="mb-2">Never permanently delete records; use the payment reversal tool instead.</li>
                <li>Your database is hosted locally on MySQL <strong><?= DB_NAME ?></strong>.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
