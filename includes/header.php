<?php
/**
 * Shared Application Header
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

// Route Guard: Protected area requires authentication
require_auth();

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? 'dashboard';

// Fetch quick task metrics for sidebar badges
$sidebarStats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
try {
    $pdoHeader = getDBConnection();
    $sidebarStats = get_user_task_metrics($pdoHeader, $currentUser['id']);
} catch (Exception $e) {
    error_log('[TaskTracker Header] Could not load sidebar stats: ' . $e->getMessage());
}

$flashMessages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &mdash; <?= APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Theme Stylesheet -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="app-body">
    <div class="app-wrapper">
        <!-- Sidebar Inclusion -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="app-main">
            <!-- Top Navbar -->
            <header class="app-topbar sticky-top bg-white border-bottom px-3 px-lg-4 py-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <!-- Mobile Sidebar Toggle -->
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <!-- Page Heading / Breadcrumb -->
                    <div class="page-header-title">
                        <h1 class="h5 mb-0 fw-bold text-slate-800"><?= e($pageTitle) ?></h1>
                        <span class="text-muted d-none d-sm-inline" style="font-size: 12px;">RTIH Task Management Suite</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Quick New Task Button -->
                    <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm px-3">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-sm-inline">New Task</span>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></span>
                            <span class="d-none d-md-inline fw-semibold text-slate-700"><?= e($currentUser['name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-slate-800"><?= e($currentUser['name']) ?></div>
                                <div class="text-muted small"><?= e($currentUser['email']) ?></div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/dashboard.php">
                                    <i class="bi bi-speedometer2 text-muted"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/tasks/index.php">
                                    <i class="bi bi-card-checklist text-muted"></i> All Tasks
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Flash Alert Container -->
            <div class="container-fluid px-3 px-lg-4 pt-3">
                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                        <i class="bi <?= $msg['type'] === 'success' ? 'bi-check-circle-fill text-success' : ($msg['type'] === 'danger' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-info') ?> me-2 fs-5"></i>
                        <div><?= e($msg['message']) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Page Content Body -->
            <main class="app-content px-3 px-lg-4 py-3">
