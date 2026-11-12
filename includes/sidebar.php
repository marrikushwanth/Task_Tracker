<?php
/**
 * Shared Application Sidebar Navigation
 * Task Tracker - RTIH Technical Assignment
 */

$currentPage = $currentPage ?? 'dashboard';
$currentStatus = $_GET['status'] ?? '';
$sidebarStats = $sidebarStats ?? ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
$currentUser = current_user();
?>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>

<aside class="app-sidebar bg-slate-900 text-white" id="appSidebar">
    <!-- Brand / Header -->
    <div class="sidebar-brand px-4 py-3 border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none text-white">
            <div class="brand-icon-box">
                <i class="bi bi-check2-square text-primary-light fs-4"></i>
            </div>
            <div>
                <span class="fs-5 fw-bold tracking-tight text-white"><?= APP_NAME ?></span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size: 10px;">PRO</span>
            </div>
        </a>
        <button class="btn btn-sm text-slate-400 d-lg-none p-0" id="sidebarClose" aria-label="Close Sidebar">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Quick Action -->
    <div class="p-3">
        <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2 fw-semibold shadow-sm">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Add New Task</span>
        </a>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-menu px-2 py-1 flex-grow-1 overflow-y-auto">
        <div class="text-uppercase text-slate-400 fw-semibold px-3 mb-2" style="font-size: 11px; letter-spacing: 0.05em;">Overview</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'tasks' && empty($currentStatus)) ? 'active' : '' ?>" href="<?= BASE_URL ?>/tasks/index.php">
                    <i class="bi bi-card-checklist me-2"></i>
                    <span>All Tasks</span>
                    <span class="badge bg-slate-700 text-slate-200 ms-auto"><?= (int)$sidebarStats['total'] ?></span>
                </a>
            </li>
        </ul>

        <div class="text-uppercase text-slate-400 fw-semibold px-3 mb-2" style="font-size: 11px; letter-spacing: 0.05em;">Task Status</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'tasks' && $currentStatus === 'Pending') ? 'active' : '' ?>" href="<?= BASE_URL ?>/tasks/index.php?status=Pending">
                    <i class="bi bi-clock me-2 text-warning"></i>
                    <span>Pending</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis ms-auto"><?= (int)$sidebarStats['pending'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'tasks' && $currentStatus === 'In Progress') ? 'active' : '' ?>" href="<?= BASE_URL ?>/tasks/index.php?status=In+Progress">
                    <i class="bi bi-arrow-repeat me-2 text-primary"></i>
                    <span>In Progress</span>
                    <span class="badge bg-primary-subtle text-primary ms-auto"><?= (int)$sidebarStats['in_progress'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'tasks' && $currentStatus === 'Completed') ? 'active' : '' ?>" href="<?= BASE_URL ?>/tasks/index.php?status=Completed">
                    <i class="bi bi-check-circle me-2 text-success"></i>
                    <span>Completed</span>
                    <span class="badge bg-success-subtle text-success ms-auto"><?= (int)$sidebarStats['completed'] ?></span>
                </a>
            </li>
        </ul>

        <div class="text-uppercase text-slate-400 fw-semibold px-3 mb-2" style="font-size: 11px; letter-spacing: 0.05em;">Task Priority</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/tasks/index.php?priority=High">
                    <i class="bi bi-fire me-2 text-danger"></i>
                    <span>High Priority</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/tasks/index.php?priority=Medium">
                    <i class="bi bi-dash-circle me-2 text-warning"></i>
                    <span>Medium Priority</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/tasks/index.php?priority=Low">
                    <i class="bi bi-arrow-down-circle me-2 text-info"></i>
                    <span>Low Priority</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- User Profile & Logout at Bottom -->
    <div class="sidebar-footer p-3 border-top border-slate-800">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="avatar-circle-sm bg-primary text-white">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white text-truncate fw-semibold small"><?= e($currentUser['name'] ?? 'User') ?></div>
                    <div class="text-slate-400 text-truncate" style="font-size: 11px;"><?= e($currentUser['email'] ?? '') ?></div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-danger p-1 text-danger" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right fs-6"></i>
            </a>
        </div>
    </div>
</aside>
