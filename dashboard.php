<?php
/**
 * Main Application Dashboard
 * Task Tracker - RTIH Technical Assignment
 * 
 * Includes:
 * - Welcome banner personalized with user's name
 * - KPI Metrics Cards (Total, Pending, In Progress, Completed, Completion Rate)
 * - Bonus Analytics: Overdue counts & Priority breakdown
 * - Quick Action Controls (Search, Quick Filter, Add Task)
 * - Recent Tasks Activity Feed with Quick Status Updates
 */

$pageTitle = 'Dashboard Overview';
$currentPage = 'dashboard';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

require_auth();
$currentUser = current_user();

$pdo = getDBConnection();

// 1. Fetch live metrics for logged-in user
$metrics = get_user_task_metrics($pdo, $currentUser['id']);

// 2. Fetch recent tasks (limit 6) for quick dashboard view
$recentStmt = $pdo->prepare('
    SELECT * FROM tasks 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 6
');
$recentStmt->execute(['user_id' => $currentUser['id']]);
$recentTasks = $recentStmt->fetchAll();

// 3. Priority breakdown for analytics
$priorityStmt = $pdo->prepare('
    SELECT 
        SUM(CASE WHEN priority = "High" THEN 1 ELSE 0 END) AS high,
        SUM(CASE WHEN priority = "Medium" THEN 1 ELSE 0 END) AS medium,
        SUM(CASE WHEN priority = "Low" THEN 1 ELSE 0 END) AS low
    FROM tasks
    WHERE user_id = :user_id
');
$priorityStmt->execute(['user_id' => $currentUser['id']]);
$priorityCounts = $priorityStmt->fetch() ?: ['high' => 0, 'medium' => 0, 'low' => 0];

include __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="welcome-card card border-0 shadow-sm rounded-3 mb-4 text-white overflow-hidden position-relative">
    <div class="card-body p-4 p-lg-5 position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-2 px-3 py-1 fw-semibold">Workspace Overview</span>
                <h1 class="h2 fw-bold mb-2">Welcome back, <?= e($currentUser['name']) ?>!</h1>
                <p class="text-white-50 mb-3" style="max-width: 600px;">
                    Track your productivity, manage active priorities, and stay on top of deadlines with your personal TaskFlow workspace.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-light text-primary fw-semibold shadow-sm px-4">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add New Task
                    </a>
                    <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-outline-light px-4">
                        <i class="bi bi-view-list me-1"></i> View All Tasks
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-end d-none d-lg-block">
                <div class="welcome-stat-pill bg-white bg-opacity-10 rounded-3 p-3 text-start d-inline-block border border-white border-opacity-25">
                    <div class="small text-white-50">Task Completion Rate</div>
                    <div class="h3 fw-bold mb-1 text-white"><?= (int)$metrics['percentage'] ?>%</div>
                    <div class="progress bg-white bg-opacity-25" style="height: 6px; width: 180px;">
                        <div class="progress-bar bg-white" role="progressbar" style="width: <?= (int)$metrics['percentage'] ?>%;" aria-valuenow="<?= (int)$metrics['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Decorative background glow -->
    <div class="welcome-glow-circle"></div>
</div>

<!-- KPI Metrics Grid (Bonus Feature 1: Dashboard Analytics) -->
<div class="row g-3 mb-4">
    <!-- Total Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-card">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">Total Tasks</span>
                    <div class="kpi-icon bg-primary-subtle text-primary rounded-2 p-2">
                        <i class="bi bi-folder2-open fs-5"></i>
                    </div>
                </div>
                <div class="h2 fw-bold text-slate-900 mb-1"><?= (int)$metrics['total'] ?></div>
                <div class="small text-muted d-flex align-items-center">
                    <a href="<?= BASE_URL ?>/tasks/index.php" class="text-decoration-none text-primary fw-medium">View all <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-card">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">Pending</span>
                    <div class="kpi-icon bg-warning-subtle text-warning-emphasis rounded-2 p-2">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                </div>
                <div class="h2 fw-bold text-warning-emphasis mb-1"><?= (int)$metrics['pending'] ?></div>
                <div class="small text-muted d-flex align-items-center">
                    <a href="<?= BASE_URL ?>/tasks/index.php?status=Pending" class="text-decoration-none text-warning-emphasis fw-medium">Awaiting action <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- In Progress Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-card">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">In Progress</span>
                    <div class="kpi-icon bg-info-subtle text-info rounded-2 p-2">
                        <i class="bi bi-arrow-repeat fs-5"></i>
                    </div>
                </div>
                <div class="h2 fw-bold text-info mb-1"><?= (int)$metrics['in_progress'] ?></div>
                <div class="small text-muted d-flex align-items-center">
                    <a href="<?= BASE_URL ?>/tasks/index.php?status=In+Progress" class="text-decoration-none text-info fw-medium">In execution <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 kpi-card">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">Completed</span>
                    <div class="kpi-icon bg-success-subtle text-success rounded-2 p-2">
                        <i class="bi bi-check2-circle fs-5"></i>
                    </div>
                </div>
                <div class="h2 fw-bold text-success mb-1"><?= (int)$metrics['completed'] ?></div>
                <div class="small text-muted d-flex align-items-center">
                    <a href="<?= BASE_URL ?>/tasks/index.php?status=Completed" class="text-decoration-none text-success fw-medium">Finished tasks <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Analytics & Quick Search Bar -->
<div class="row g-3 mb-4">
    <!-- Progress & Distribution Analytics Card -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-slate-800 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line text-primary"></i> Productivity Analytics
                </div>
                <span class="badge bg-light text-muted border">Real-time</span>
            </div>
            <div class="card-body p-4">
                <!-- Overall completion bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-slate-700 small">Overall Completion Rate</span>
                        <span class="fw-bold text-primary small"><?= (int)$metrics['percentage'] ?>% Completed</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= (int)$metrics['percentage'] ?>%;" aria-valuenow="<?= (int)$metrics['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Priority Breakdown (Bonus) -->
                <div class="row g-2 pt-2 border-top">
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded-2">
                            <span class="d-block text-danger small fw-semibold"><i class="bi bi-fire me-1"></i>High</span>
                            <span class="h5 fw-bold text-slate-800"><?= (int)($priorityCounts['high'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded-2">
                            <span class="d-block text-warning small fw-semibold"><i class="bi bi-dash-circle me-1"></i>Medium</span>
                            <span class="h5 fw-bold text-slate-800"><?= (int)($priorityCounts['medium'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded-2">
                            <span class="d-block text-info small fw-semibold"><i class="bi bi-arrow-down-circle me-1"></i>Low</span>
                            <span class="h5 fw-bold text-slate-800"><?= (int)($priorityCounts['low'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>

                <?php if ((int)$metrics['overdue'] > 0): ?>
                    <div class="alert alert-warning d-flex align-items-center mt-3 mb-0 py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2 text-warning"></i>
                        <div>
                            You have <strong><?= (int)$metrics['overdue'] ?> overdue task(s)</strong> requiring attention!
                            <a href="<?= BASE_URL ?>/tasks/index.php?sort=due_date" class="alert-link ms-1">Review overdue</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Search & Actions Box -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <div class="fw-bold text-slate-800 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning"></i> Quick Search &amp; Actions
                </div>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <form action="<?= BASE_URL ?>/tasks/index.php" method="GET" class="mb-3">
                    <label class="form-label small fw-semibold text-slate-700">Search Tasks by Title</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Type keywords and hit enter...">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>

                <div class="pt-2">
                    <div class="small fw-semibold text-slate-700 mb-2">Quick Filter Shortcuts</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/tasks/index.php?status=Pending" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-clock me-1"></i>Pending (<?= (int)$metrics['pending'] ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/tasks/index.php?status=In+Progress" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-1"></i>In Progress (<?= (int)$metrics['in_progress'] ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/tasks/index.php?status=Completed" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-check-circle me-1"></i>Completed (<?= (int)$metrics['completed'] ?>)
                        </a>
                        <a href="<?= BASE_URL ?>/tasks/index.php?priority=High" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-fire me-1"></i>High Priority
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tasks Section with Quick Status Updates -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="fw-bold text-slate-800 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Recent Tasks Activity
        </div>
        <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-sm btn-outline-primary">
            View All (<?= (int)$metrics['total'] ?>)
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentTasks)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard2-plus fs-1 text-muted d-block mb-2"></i>
                <h3 class="h6 fw-bold text-slate-700">No tasks created yet</h3>
                <p class="text-muted small mb-3">Add your first task to see it on your dashboard.</p>
                <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Create Task
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-slate-700">
                        <tr>
                            <th class="ps-4">Task</th>
                            <th>Status (Quick Update)</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTasks as $task): 
                            $isOverdue = is_overdue($task['due_date'], $task['status']);
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-slate-900 <?= $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                                        <?= e($task['title']) ?>
                                    </div>
                                    <div class="text-muted small">Created <?= time_ago($task['created_at']) ?></div>
                                </td>
                                <td>
                                    <!-- Quick Status Update Dropdown -->
                                    <div class="dropdown quick-status-dropdown">
                                        <button class="btn btn-sm p-0 border-0 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick status change">
                                            <?= render_status_badge($task['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow-sm border-0">
                                            <li class="dropdown-header small">Change Status</li>
                                            <li>
                                                <form action="<?= BASE_URL ?>/tasks/update_status.php" method="POST" class="quick-status-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                                    <input type="hidden" name="status" value="Pending">
                                                    <button type="submit" class="dropdown-item py-1 <?= $task['status'] === 'Pending' ? 'active' : '' ?>">
                                                        <i class="bi bi-clock text-warning me-2"></i> Pending
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="<?= BASE_URL ?>/tasks/update_status.php" method="POST" class="quick-status-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                                    <input type="hidden" name="status" value="In Progress">
                                                    <button type="submit" class="dropdown-item py-1 <?= $task['status'] === 'In Progress' ? 'active' : '' ?>">
                                                        <i class="bi bi-arrow-repeat text-primary me-2"></i> In Progress
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="<?= BASE_URL ?>/tasks/update_status.php" method="POST" class="quick-status-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                                    <input type="hidden" name="status" value="Completed">
                                                    <button type="submit" class="dropdown-item py-1 <?= $task['status'] === 'Completed' ? 'active' : '' ?>">
                                                        <i class="bi bi-check-circle text-success me-2"></i> Completed
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td><?= render_priority_badge($task['priority']) ?></td>
                                <td>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= format_date($task['due_date']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="small text-slate-700"><?= format_date($task['due_date']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger delete-task-btn" 
                                                data-id="<?= (int)$task['id'] ?>" 
                                                data-title="<?= e($task['title']) ?>" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Delete Confirmation -->
<?php include __DIR__ . '/tasks/_delete_modal.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

