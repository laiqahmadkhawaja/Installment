<?php
/**
 * Master Sidebar Navigation
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function isActive(string $segment): string {
    global $currentUri;
    return strpos($currentUri, $segment) !== false ? 'active' : '';
}
?>
<nav id="sidebar">
    <div class="brand">
        <i class="fa-solid fa-coins text-warning"></i>
        <span><?= __('app_name') ?></span>
    </div>

    <div class="sidebar-nav-container overflow-y-auto flex-grow-1 py-2">
        <!-- Main Core -->
        <a class="nav-link <?= isActive('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard/index.php">
            <i class="fa-solid fa-gauge-high"></i>
            <span><?= __('dashboard') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('customers') ?></div>
        <a class="nav-link <?= isActive('/customers/index.php') ?>" href="<?= BASE_URL ?>/customers/index.php">
            <i class="fa-solid fa-users"></i>
            <span><?= __('customer_list') ?></span>
        </a>
        <a class="nav-link <?= isActive('/customers/create.php') ?>" href="<?= BASE_URL ?>/customers/create.php">
            <i class="fa-solid fa-user-plus"></i>
            <span><?= __('new_customer') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('installment_plans') ?></div>
        <a class="nav-link <?= isActive('/contracts/create.php') ?>" href="<?= BASE_URL ?>/contracts/create.php">
            <i class="fa-solid fa-file-circle-plus"></i>
            <span><?= __('new_contract') ?></span>
        </a>
        <a class="nav-link <?= isActive('/contracts/index.php') ?>" href="<?= BASE_URL ?>/contracts/index.php">
            <i class="fa-solid fa-file-contract"></i>
            <span><?= __('all_contracts') ?></span>
        </a>
        <a class="nav-link <?= isActive('/collection/receive.php') ?>" href="<?= BASE_URL ?>/collection/receive.php">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span><?= __('receive_payment') ?></span>
        </a>
        <a class="nav-link <?= isActive('/collection/daily_sheet.php') ?>" href="<?= BASE_URL ?>/collection/daily_sheet.php">
            <i class="fa-solid fa-receipt"></i>
            <span><?= __('daily_collection') ?></span>
        </a>
        <a class="nav-link <?= isActive('/collection/reversals.php') ?>" href="<?= BASE_URL ?>/collection/reversals.php">
            <i class="fa-solid fa-rotate-left"></i>
            <span><?= __('payment_reversals') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('defaulters') ?></div>
        <a class="nav-link <?= isActive('/defaulters/index.php') ?>" href="<?= BASE_URL ?>/defaulters/index.php">
            <i class="fa-solid fa-triangle-exclamation text-danger"></i>
            <span><?= __('overdue_list') ?></span>
        </a>
        <a class="nav-link <?= isActive('/defaulters/followups.php') ?>" href="<?= BASE_URL ?>/defaulters/followups.php">
            <i class="fa-solid fa-phone-volume text-warning"></i>
            <span><?= __('followups') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('pos') ?></div>
        <a class="nav-link <?= isActive('/pos/index.php') ?>" href="<?= BASE_URL ?>/pos/index.php">
            <i class="fa-solid fa-cash-register text-success"></i>
            <span><?= __('pos') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('products') ?></div>
        <a class="nav-link <?= isActive('/products/index.php') ?>" href="<?= BASE_URL ?>/products/index.php">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span><?= __('product_list') ?></span>
        </a>
        <a class="nav-link <?= isActive('/products/create.php') ?>" href="<?= BASE_URL ?>/products/create.php">
            <i class="fa-solid fa-box-open"></i>
            <span><?= __('new_product') ?></span>
        </a>
        <a class="nav-link <?= isActive('/products/categories.php') ?>" href="<?= BASE_URL ?>/products/categories.php">
            <i class="fa-solid fa-tags"></i>
            <span><?= __('categories') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('accounting') ?></div>
        <a class="nav-link <?= isActive('/accounting/cashbook.php') ?>" href="<?= BASE_URL ?>/accounting/cashbook.php">
            <i class="fa-solid fa-book-journal-whills"></i>
            <span><?= __('cashbook') ?></span>
        </a>
        <a class="nav-link <?= isActive('/accounting/expenses.php') ?>" href="<?= BASE_URL ?>/accounting/expenses.php">
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span><?= __('expenses') ?></span>
        </a>
        <a class="nav-link <?= isActive('/accounting/profit_loss.php') ?>" href="<?= BASE_URL ?>/accounting/profit_loss.php">
            <i class="fa-solid fa-chart-line text-success"></i>
            <span><?= __('profit_loss') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('purchases') ?></div>
        <a class="nav-link <?= isActive('/purchases/suppliers.php') ?>" href="<?= BASE_URL ?>/purchases/suppliers.php">
            <i class="fa-solid fa-truck-field"></i>
            <span><?= __('supplier_list') ?></span>
        </a>
        <a class="nav-link <?= isActive('/purchases/index.php') ?>" href="<?= BASE_URL ?>/purchases/index.php">
            <i class="fa-solid fa-cart-flatbed"></i>
            <span><?= __('purchases') ?></span>
        </a>
        <a class="nav-link <?= isActive('/returns/index.php') ?>" href="<?= BASE_URL ?>/returns/index.php">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            <span><?= __('returns') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('reports') ?></div>
        <a class="nav-link <?= isActive('/reports/index.php') ?>" href="<?= BASE_URL ?>/reports/index.php">
            <i class="fa-solid fa-chart-pie"></i>
            <span><?= __('reports') ?></span>
        </a>

        <div class="sidebar-heading"><?= __('system') ?></div>
        <a class="nav-link <?= isActive('/branches/index.php') ?>" href="<?= BASE_URL ?>/branches/index.php">
            <i class="fa-solid fa-code-branch"></i>
            <span><?= __('branches') ?></span>
        </a>
        <a class="nav-link <?= isActive('/users/index.php') ?>" href="<?= BASE_URL ?>/users/index.php">
            <i class="fa-solid fa-user-shield"></i>
            <span><?= __('users') ?></span>
        </a>
        <a class="nav-link <?= isActive('/system/audit.php') ?>" href="<?= BASE_URL ?>/system/audit.php">
            <i class="fa-solid fa-list-check"></i>
            <span><?= __('audit_logs') ?></span>
        </a>
        <a class="nav-link <?= isActive('/system/backup.php') ?>" href="<?= BASE_URL ?>/system/backup.php">
            <i class="fa-solid fa-database"></i>
            <span><?= __('backup') ?></span>
        </a>
        <a class="nav-link <?= isActive('/system/settings.php') ?>" href="<?= BASE_URL ?>/system/settings.php">
            <i class="fa-solid fa-gear"></i>
            <span><?= __('settings') ?></span>
        </a>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-3 border-top border-secondary border-opacity-25 small text-muted d-flex justify-content-between align-items-center">
        <span>v2.5 Enterprise</span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="text-danger text-decoration-none" title="<?= __('logout') ?>">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>
</nav>
