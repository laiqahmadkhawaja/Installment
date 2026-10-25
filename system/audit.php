<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();
$moduleFilter = $_GET['module'] ?? '';

$sql = "SELECT a.*, u.full_name as user_name, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE 1=1";
$params = [];

if (!empty($moduleFilter)) {
    $sql .= " AND a.module = ?";
    $params[] = $moduleFilter;
}
$sql .= " ORDER BY a.id DESC LIMIT 100";

$logs = DB::fetchAll($sql, $params);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-list-check text-primary me-2"></i><?= __('audit_logs') ?></h4>
        <p class="text-muted small mb-0">System audit trail recording user logins, financial transactions, and modifications</p>
    </div>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom small">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Activity Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No audit logs recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-muted"><?= format_date($l['created_at'], 'd M Y h:i:s A') ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($l['user_name'] ?? 'System') ?></div>
                            <small class="text-muted"><code><?= htmlspecialchars($l['username'] ?? 'sys') ?></code></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['module']) ?></span></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($l['action']) ?></span></td>
                        <td><?= htmlspecialchars($l['details']) ?></td>
                        <td><code><?= htmlspecialchars($l['ip_address'] ?: '127.0.0.1') ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
