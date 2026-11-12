<?php
/**
 * Tasks List Controller and View
 * Task Tracker - RTIH Technical Assignment
 * 
 * Features:
 * - Search by title and description using SQL LIKE
 * - Filter by status (Pending, In Progress, Completed)
 * - Filter by priority (Low, Medium, High)
 * - Sorting (Newest, Oldest, Due Date, Priority)
 * - Pagination with Next / Previous navigation
 * - Card Grid vs Table view toggle
 * - Direct inline status update
 * - Strict User Ownership & Prepared Statements
 */

$pageTitle = 'Task Management';
$currentPage = 'tasks';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$currentUser = current_user();

$pdo = getDBConnection();

// 1. Collect and sanitize filter parameters
$search         = trim($_GET['q'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$sort           = trim($_GET['sort'] ?? 'newest');
$viewMode       = trim($_GET['view'] ?? 'cards'); // 'cards' or 'table'
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 6;

// Validate status filter
$validStatuses = ['Pending', 'In Progress', 'Completed'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

// Validate priority filter
$validPriorities = ['Low', 'Medium', 'High'];
if (!in_array($priorityFilter, $validPriorities, true)) {
    $priorityFilter = '';
}

// 2. Build Dynamic SQL Query with PDO Prepared Statements
$whereClauses = ['user_id = :user_id'];
$params = ['user_id' => $currentUser['id']];

if ($search !== '') {
    $whereClauses[] = '(title LIKE :search OR description LIKE :search_desc)';
    $params['search'] = '%' . $search . '%';
    $params['search_desc'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $whereClauses[] = 'status = :status_filter';
    $params['status_filter'] = $statusFilter;
}

if ($priorityFilter !== '') {
    $whereClauses[] = 'priority = :priority_filter';
    $params['priority_filter'] = $priorityFilter;
}

$whereSql = implode(' AND ', $whereClauses);

// 3. Count total matching tasks for pagination
$countSql = "SELECT COUNT(*) FROM tasks WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// 4. Determine Sort Clause
switch ($sort) {
    case 'oldest':
        $orderBySql = 'created_at ASC';
        break;
    case 'due_date':
        $orderBySql = '(due_date IS NULL), due_date ASC, created_at DESC';
        break;
    case 'priority':
        $orderBySql = 'CASE priority WHEN "High" THEN 1 WHEN "Medium" THEN 2 WHEN "Low" THEN 3 ELSE 4 END, created_at DESC';
        break;
    case 'title':
        $orderBySql = 'title ASC';
        break;
    case 'newest':
    default:
        $sort = 'newest';
        $orderBySql = 'created_at DESC';
        break;
}

// 5. Fetch Paginated Records
$tasksSql = "SELECT * FROM tasks WHERE $whereSql ORDER BY $orderBySql LIMIT :limit OFFSET :offset";
$tasksStmt = $pdo->prepare($tasksSql);

// Bind where parameters
foreach ($params as $key => $val) {
    $tasksStmt->bindValue(':' . $key, $val);
}
// Bind integer limit and offset
$tasksStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$tasksStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$tasksStmt->execute();
$tasks = $tasksStmt->fetchAll();

// Helper to preserve active query parameters when generating pagination/filter URLs
function filter_url(array $overrides = []): string {
    $current = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return BASE_URL . '/tasks/index.php' . (!empty($current) ? '?' . http_build_query($current) : '');
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Action Bar: Search, Filters & Controls -->
<div class="card shadow-sm border-0 mb-4 rounded-3">
    <div class="card-body p-3 p-md-4">
        <form action="<?= BASE_URL ?>/tasks/index.php" method="GET" id="filterForm">
            <!-- Hidden view and page resets -->
            <input type="hidden" name="view" value="<?= e($viewMode) ?>">

            <div class="row g-2 align-items-center">
                <!-- Search Bar (SQL LIKE) -->
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="q" 
                               id="taskSearchInput" 
                               class="form-control border-start-0" 
                               placeholder="Search tasks by title or details..." 
                               value="<?= e($search) ?>" 
                               autocomplete="off">
                        <?php if ($search !== ''): ?>
                            <a href="<?= filter_url(['q' => null, 'page' => 1]) ?>" class="input-group-text bg-white text-muted text-decoration-none" title="Clear search">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Filter Dropdown -->
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select filter-auto-submit" aria-label="Filter by Status">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <!-- Priority Filter Dropdown (Bonus) -->
                <div class="col-6 col-md-2">
                    <select name="priority" class="form-select filter-auto-submit" aria-label="Filter by Priority">
                        <option value="">All Priorities</option>
                        <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High Priority</option>
                        <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium Priority</option>
                        <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low Priority</option>
                    </select>
                </div>

                <!-- Sort Order Dropdown (Bonus) -->
                <div class="col-6 col-md-2">
                    <select name="sort" class="form-select filter-auto-submit" aria-label="Sort Tasks">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="due_date" <?= $sort === 'due_date' ? 'selected' : '' ?>>Due Date Soonest</option>
                        <option value="priority" <?= $sort === 'priority' ? 'selected' : '' ?>>Priority Highest</option>
                    </select>
                </div>

                <!-- View Switcher & Submit -->
                <div class="col-6 col-md-2 d-flex justify-content-end gap-1">
                    <div class="btn-group" role="group" aria-label="View switch">
                        <a href="<?= filter_url(['view' => 'cards']) ?>" 
                           class="btn btn-outline-secondary <?= $viewMode === 'cards' ? 'active' : '' ?>" 
                           title="Card View">
                            <i class="bi bi-grid-fill"></i>
                        </a>
                        <a href="<?= filter_url(['view' => 'table']) ?>" 
                           class="btn btn-outline-secondary <?= $viewMode === 'table' ? 'active' : '' ?>" 
                           title="Table View">
                            <i class="bi bi-table"></i>
                        </a>
                    </div>
                    <button type="submit" class="btn btn-primary px-3" title="Apply Filters">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                </div>
            </div>

            <!-- Active filter chips -->
            <?php if ($search !== '' || $statusFilter !== '' || $priorityFilter !== '' || $sort !== 'newest'): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                    <span class="text-muted small fw-semibold">Active filters:</span>
                    <?php if ($search !== ''): ?>
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1">
                            Search: "<?= e($search) ?>"
                            <a href="<?= filter_url(['q' => null, 'page' => 1]) ?>" class="text-muted"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($statusFilter !== ''): ?>
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1">
                            Status: <?= e($statusFilter) ?>
                            <a href="<?= filter_url(['status' => null, 'page' => 1]) ?>" class="text-muted"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($priorityFilter !== ''): ?>
                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1">
                            Priority: <?= e($priorityFilter) ?>
                            <a href="<?= filter_url(['priority' => null, 'page' => 1]) ?>" class="text-muted"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-link btn-xs text-danger text-decoration-none p-0 ms-1" style="font-size: 12px;">Reset All</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Task Results Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="h6 text-muted mb-0">
            Showing <strong class="text-slate-900"><?= count($tasks) ?></strong> of <strong class="text-slate-900"><?= $totalRecords ?></strong> tasks
        </h2>
    </div>
    <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-sm btn-primary shadow-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i> Add Task
    </a>
</div>

<!-- Task Content: Empty State vs Cards vs Table -->
<?php if (empty($tasks)): ?>
    <div class="card shadow-sm border-0 rounded-3 text-center py-5">
        <div class="card-body">
            <div class="empty-state-icon mx-auto mb-3 bg-light rounded-circle d-flex align-items-center justify-content-center text-muted" style="width: 72px; height: 72px;">
                <i class="bi bi-inbox fs-1"></i>
            </div>
            <h3 class="h5 fw-bold text-slate-800 mb-1">No tasks found</h3>
            <?php if ($search !== '' || $statusFilter !== '' || $priorityFilter !== ''): ?>
                <p class="text-muted small mb-3">No tasks match your current search and filter criteria.</p>
                <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                </a>
            <?php else: ?>
                <p class="text-muted small mb-3">You haven't created any tasks yet. Get started by creating your first task!</p>
                <a href="<?= BASE_URL ?>/tasks/create.php" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Create First Task
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($viewMode === 'table'): ?>
    <!-- TABLE VIEW -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-slate-700">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Task Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Created</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): 
                        $isTaskOverdue = is_overdue($task['due_date'], $task['status']);
                    ?>
                        <tr class="<?= $task['status'] === 'Completed' ? 'table-light text-muted' : '' ?>">
                            <td class="text-muted small">#<?= (int)$task['id'] ?></td>
                            <td>
                                <div class="fw-bold text-slate-900 <?= $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                                    <?= e($task['title']) ?>
                                </div>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">
                                        <?= e($task['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Quick Status Dropdown -->
                                <div class="dropdown quick-status-dropdown">
                                    <button class="btn btn-sm p-0 border-0 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                <?php if ($isTaskOverdue): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Overdue">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= format_date($task['due_date']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="small text-slate-700"><?= format_date($task['due_date']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="small text-muted"><?= format_date($task['created_at']) ?></span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-outline-secondary" title="Edit Task">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger delete-task-btn" 
                                            data-id="<?= (int)$task['id'] ?>" 
                                            data-title="<?= e($task['title']) ?>" 
                                            title="Delete Task">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <!-- CARD GRID VIEW -->
    <div class="row g-3" id="tasksGrid">
        <?php foreach ($tasks as $task): 
            $isTaskOverdue = is_overdue($task['due_date'], $task['status']);
        ?>
            <div class="col-12 col-md-6 col-xl-4 task-card-wrapper" id="task-card-<?= (int)$task['id'] ?>">
                <div class="card h-100 task-card shadow-sm border-0 rounded-3 <?= $isTaskOverdue ? 'border-overdue' : '' ?>">
                    <div class="card-body d-flex flex-column p-3 p-lg-4">
                        <!-- Top Row: Badges & Quick Action -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-1">
                                <!-- Status Badge with Quick Update Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-sm p-0 border-0 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Click to quickly change status">
                                        <?= render_status_badge($task['status']) ?>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm border-0">
                                        <li class="dropdown-header small">Quick Status Update</li>
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

                                <?= render_priority_badge($task['priority']) ?>
                            </div>

                            <!-- Due Date Pill -->
                            <?php if ($isTaskOverdue): ?>
                                <span class="badge bg-danger text-white" title="Task is overdue!">
                                    <i class="bi bi-exclamation-circle me-1"></i>Overdue
                                </span>
                            <?php elseif (!empty($task['due_date'])): ?>
                                <span class="text-muted small" style="font-size: 11px;">
                                    <i class="bi bi-calendar3 me-1"></i><?= format_date($task['due_date']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title -->
                        <h3 class="h6 fw-bold text-slate-900 mb-2 <?= $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                            <?= e($task['title']) ?>
                        </h3>

                        <!-- Description -->
                        <p class="text-muted small mb-3 flex-grow-1 task-description">
                            <?= !empty($task['description']) ? nl2br(e($task['description'])) : '<em class="text-slate-400">No additional description provided.</em>' ?>
                        </p>

                        <!-- Card Footer: Meta Info & Action Buttons -->
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between mt-auto">
                            <span class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-clock-history me-1"></i><?= time_ago($task['created_at']) ?>
                            </span>

                            <div class="d-flex align-items-center gap-1">
                                <a href="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-sm btn-outline-secondary px-2 py-1" title="Edit Task">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger px-2 py-1 delete-task-btn" 
                                        data-id="<?= (int)$task['id'] ?>" 
                                        data-title="<?= e($task['title']) ?>" 
                                        title="Delete Task">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Pagination (Bonus Feature 2) -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2" aria-label="Tasks pagination">
        <div class="text-muted small">
            Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong>
        </div>
        <ul class="pagination pagination-sm mb-0">
            <!-- Previous Page -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= filter_url(['page' => $page - 1]) ?>" tabindex="<?= $page <= 1 ? '-1' : '0' ?>" aria-label="Previous">
                    <i class="bi bi-chevron-left me-1"></i> Previous
                </a>
            </li>

            <!-- Page Number Links -->
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 1 && $p <= $page + 1)): ?>
                    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= filter_url(['page' => $p]) ?>"><?= $p ?></a>
                    </li>
                <?php elseif ($p == $page - 2 || $p == $page + 2): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Next Page -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= filter_url(['page' => $page + 1]) ?>" tabindex="<?= $page >= $totalPages ? '-1' : '0' ?>" aria-label="Next">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal for Delete Confirmation -->
<?php include __DIR__ . '/_delete_modal.php'; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
