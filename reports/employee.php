<?php
require_once __DIR__ . '/../includes/header.php';

$branchId = current_branch_id();

$officers = DB::fetchAll("SELECT u.*, r.role_name,
    COUNT(DISTINCT c.id) as assigned_contracts,
    COALESCE(SUM(p.amount), 0) as total_collected
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN installment_contracts c ON u.id = c.salesperson_id OR u.id = c.collection_officer_id
    LEFT JOIN payments p ON u.id = p.collected_by AND p.is_reversed = 0
    GROUP BY u.id
    ORDER BY total_collected DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-check text-primary me-2"></i>Staff & Collection Performance Report</h4>
        <p class="text-muted small mb-0">Salesperson performance, recovery officer collections, and commission calculation</p>
    </div>
    <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Reports
    </a>
</div>

<div class="card border rounded-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Role</th>
                    <th>Phone / Username</th>
                    <th class="text-center">Contracts Handled</th>
                    <th class="text-end">Total Cash Collected</th>
                    <th class="text-end">Est. Commission (1%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($officers as $off): ?>
                <?php
                $coll = (float)$off['total_collected'];
                $comm = $coll * 0.01; // 1% collection commission standard
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($off['full_name']) ?></div>
                        <small class="text-muted"><code><?= htmlspecialchars($off['username']) ?></code></small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($off['role_name']) ?></span></td>
                    <td><?= htmlspecialchars($off['phone'] ?: '-') ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= $off['assigned_contracts'] ?></span></td>
                    <td class="text-end fw-bold text-success"><?= format_currency($coll) ?></td>
                    <td class="text-end fw-semibold text-primary"><?= format_currency($comm) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
