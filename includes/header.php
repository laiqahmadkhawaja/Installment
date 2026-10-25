<?php
/**
 * Application Master Header
 */
require_once __DIR__ . '/../config/config.php';
require_login();

$currentUser = current_user();
$activeBranchId = current_branch_id();
$branches = DB::fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY id ASC");
$activeBranch = DB::fetch("SELECT * FROM branches WHERE id = ?", [$activeBranchId]) ?? ($branches[0] ?? ['name' => 'Main Branch']);
$themeMode = $_SESSION['theme_mode'] ?? 'light';
$lang = current_lang();
$isRtl = is_rtl();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>" data-bs-theme="<?= $themeMode ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('app_name') ?> - <?= get_setting('company_name', 'Installment Management System') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts for Urdu & English -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <?php if ($isRtl): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/rtl.css">
    <?php endif; ?>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
<div id="app-wrapper">
    <!-- Include Sidebar Navigation -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div id="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-secondary d-lg-none" id="sidebar-toggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle fw-semibold" type="button" data-bs-override="true" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-building-circle-check text-primary me-1"></i>
                        <?= htmlspecialchars($isRtl && !empty($activeBranch['urdu_name']) ? $activeBranch['urdu_name'] : $activeBranch['name']) ?>
                    </button>
                    <ul class="dropdown-menu shadow">
                        <li class="dropdown-header text-uppercase small"><?= __('switch_branch') ?></li>
                        <?php foreach ($branches as $b): ?>
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center <?= $b['id'] == $activeBranchId ? 'active fw-bold' : '' ?>" href="?switch_branch=<?= $b['id'] ?>">
                                <?= htmlspecialchars($isRtl && !empty($b['urdu_name']) ? $b['urdu_name'] : $b['name']) ?>
                                <?php if ($b['id'] == $activeBranchId): ?>
                                    <i class="fa-solid fa-check ms-2"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Header Right Actions: Quick Actions, Theme, Language, Profile -->
            <div class="d-flex align-items-center gap-2">
                <!-- POS Quick Link -->
                <a href="<?= BASE_URL ?>/pos/index.php" class="btn btn-sm btn-success fw-semibold d-none d-sm-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-cash-register"></i> <?= __('pos') ?>
                </a>

                <!-- Collect Payment Quick Link -->
                <a href="<?= BASE_URL ?>/collection/receive.php" class="btn btn-sm btn-primary fw-semibold d-none d-md-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-hand-holding-dollar"></i> <?= __('receive_payment') ?>
                </a>

                <!-- Language Switcher -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-globe text-muted me-1"></i>
                        <?= $lang === 'ur' ? 'اردو' : 'English' ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item <?= $lang === 'en' ? 'fw-bold active' : '' ?>" href="?lang=en">English (LTR)</a></li>
                        <li><a class="dropdown-item <?= $lang === 'ur' ? 'fw-bold active' : '' ?>" href="?lang=ur">اردو - Urdu (RTL)</a></li>
                    </ul>
                </div>

                <!-- Theme Toggle -->
                <a href="?theme=<?= $themeMode === 'light' ? 'dark' : 'light' ?>" class="btn btn-sm btn-light border" title="Toggle Light/Dark Theme">
                    <i class="fa-solid <?= $themeMode === 'light' ? 'fa-moon text-dark' : 'fa-sun text-warning' ?>"></i>
                </a>

                <!-- User Profile Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.8rem;font-weight:700;">
                            <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header">
                            <div class="fw-bold"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($currentUser['role_name']) ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/system/settings.php"><i class="fa-solid fa-sliders me-2 text-muted"></i><?= __('settings') ?></a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/system/audit.php"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i><?= __('audit_logs') ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i><?= __('logout') ?></a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Notice Container -->
        <div class="container-fluid pt-3 px-4">
            <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Page Body Container -->
        <main class="flex-grow-1 px-4 py-3">
