<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isDashboard = strpos($_SERVER['PHP_SELF'], 'dashboard') !== false;
$cssVer = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle).' – ' : '' ?><?= SITE_NAME ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $cssVer ?>">
    <?= isset($extraHead) ? $extraHead : '' ?>
</head>
<body class="<?= trim(($bodyClass ?? '') . ($isDashboard ? ' page-dashboard' : '')) ?>">

<!-- ── SIDEBAR ─────────────────────────────────────────────── -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-house-heart-fill"></i></div>
        <div class="brand-text">
            <span class="brand-name">LARTS</span>
            <span class="brand-sub">Barangay System</span>
        </div>
        <button type="button" class="sidebar-collapse-icon" id="sidebarCollapseToggle" aria-label="Collapse sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="<?= BASE_URL ?>/dashboard/index.php" class="nav-link-item <?= $currentPage==='index' && strpos($_SERVER['PHP_SELF'],'dashboard')!==false ? 'active':'' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>

        <div class="nav-section-label">Records</div>
        <a href="<?= BASE_URL ?>/modules/households/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'households')!==false ? 'active':'' ?>">
            <i class="bi bi-people-fill"></i><span>Households</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/household-members/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'household-members')!==false ? 'active':'' ?>">
            <i class="bi bi-person-badge-fill"></i><span>Household Members</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/income/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'income')!==false ? 'active':'' ?>">
            <i class="bi bi-cash-stack"></i><span>Income</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/expenses/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'expenses')!==false ? 'active':'' ?>">
            <i class="bi bi-receipt"></i><span>Expenses</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/assistance/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'assistance')!==false && strpos($_SERVER['PHP_SELF'],'assistance-types')===false ? 'active':'' ?>">
            <i class="bi bi-gift-fill"></i><span>Assistance</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/assistance-types/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'assistance-types')!==false ? 'active':'' ?>">
            <i class="bi bi-tags-fill"></i><span>Assistance Types</span>
        </a>

        <div class="nav-section-label">Settings & Master Data</div>
        <a href="<?= BASE_URL ?>/modules/barangays/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'barangays')!==false ? 'active':'' ?>">
            <i class="bi bi-geo-alt-fill"></i><span>Barangays</span>
        </a>

        <div class="nav-section-label">Support & Compliance</div>
        <a href="<?= BASE_URL ?>/modules/grievances/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'grievances')!==false ? 'active':'' ?>">
            <i class="bi bi-exclamation-circle-fill"></i><span>Grievances</span>
        </a>

        <div class="nav-section-label">Analytics</div>
        <a href="<?= BASE_URL ?>/modules/reports/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'reports')!==false ? 'active':'' ?>">
            <i class="bi bi-file-earmark-bar-graph-fill"></i><span>Reports</span>
        </a>

        <?php if (isAdmin()): ?>
        <div class="nav-section-label">Admin</div>
        <a href="<?= BASE_URL ?>/modules/users/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'users')!==false ? 'active':'' ?>">
            <i class="bi bi-person-gear"></i><span>Manage Users</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/audit-logs/index.php" class="nav-link-item <?= strpos($_SERVER['PHP_SELF'],'audit-logs')!==false ? 'active':'' ?>">
            <i class="bi bi-file-earmark-text-fill"></i><span>Audit Logs</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
            <div class="user-info">
                <span class="user-name"><?= clean($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
<div class="main-wrap" id="mainWrap">
    <!-- Top Bar -->
    <div class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title"><?= isset($pageTitle) ? clean($pageTitle) : 'Dashboard' ?></div>
        <div class="topbar-right">
            <?php if (!$isDashboard): ?>
            <div class="live-clock" aria-live="polite">
                <i class="bi bi-clock-fill live-clock-icon"></i>
                <div class="live-clock-body">
                    <span class="live-clock-time" id="liveClockTime"><?= date('H:i:s') ?></span>
                    <span class="live-clock-date" id="liveClockDate"><?= date('M d, Y') ?></span>
                </div>
            </div>
            <?php endif; ?>
            <span class="badge-role badge bg-<?= $user['role']==='admin'?'danger':($user['role']==='staff'?'primary':'success') ?>">
                <?= ucfirst($user['role']) ?>
            </span>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
        <?= renderFlash() ?>