<?php
/**
 * Create Task Controller and View
 * Task Tracker - RTIH Technical Assignment
 */

$pageTitle = 'Add New Task';
$currentPage = 'tasks_create';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$currentUser = current_user();

$errors = [];
$title = '';
$description = '';
$status = 'Pending';
$priority = 'Medium';
$dueDate = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Enforce CSRF protection
    verify_csrf_request(true);

    $title       = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status      = $_POST['status'] ?? 'Pending';
    $priority    = $_POST['priority'] ?? 'Medium';
    $dueDate     = $_POST['due_date'] ?? '';

    // 2. Perform rigorous server-side validation
    $validation = validate_task_input([
        'title'       => $title,
        'description' => $description,
        'status'      => $status,
        'priority'    => $priority,
        'due_date'    => $dueDate
    ]);

    if (!$validation['valid']) {
        $errors = $validation['errors'];
    } else {
        try {
            $pdo = getDBConnection();

            // 3. Insert task with authenticated user's ID (never trust client-sent user_id)
            $stmt = $pdo->prepare('
                INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at)
                VALUES (:user_id, :title, :description, :status, :priority, :due_date, NOW())
            ');

            $stmt->execute([
                'user_id'     => $currentUser['id'],
                'title'       => $validation['data']['title'],
                'description' => $validation['data']['description'],
                'status'      => $validation['data']['status'],
                'priority'    => $validation['data']['priority'],
                'due_date'    => $validation['data']['due_date']
            ]);

            set_flash('success', 'Task "' . htmlspecialchars($validation['data']['title'], ENT_QUOTES, 'UTF-8') . '" has been created successfully!');
            redirect('tasks/index.php');
        } catch (PDOException $e) {
            error_log('[TaskTracker Create Task Error] ' . $e->getMessage());
            $errors['general'] = 'Failed to create task due to a server error. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <!-- Breadcrumb / Back button -->
        <div class="mb-3 d-flex align-items-center justify-content-between">
            <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Tasks
            </a>
            <span class="text-muted small">All fields marked with <span class="text-danger">*</span> are required</span>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                <div><?= e($errors['general']) ?></div>
            </div>
        <?php endif; ?>

        <!-- Create Task Card -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary-subtle rounded-2 text-primary">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-slate-800">Create New Task</h2>
                        <p class="text-muted small mb-0">Add a new item to your personal task tracker</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/tasks/create.php" method="POST" novalidate id="taskCreateForm">
                    <?= csrf_field() ?>

                    <!-- Task Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold text-slate-700">
                            Task Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                               id="title" 
                               name="title" 
                               placeholder="e.g. Implement user authorization middleware" 
                               value="<?= e($title) ?>" 
                               minlength="3" 
                               maxlength="255" 
                               required 
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                        <?php else: ?>
                            <div class="form-text text-muted small">A concise summary of what needs to be done (3-255 characters).</div>
                        <?php endif; ?>
                    </div>

                    <!-- Task Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold text-slate-700">Description</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  maxlength="2000" 
                                  placeholder="Provide any additional context, checklist items, or notes..."><?= e($description) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                        <?php else: ?>
                            <div class="form-text text-muted small">Optional details or instructions (up to 2000 characters).</div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold text-slate-700">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status" required>
                                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Priority (Bonus Feature) -->
                        <div class="col-md-4">
                            <label for="priority" class="form-label fw-semibold text-slate-700">
                                Priority <span class="text-muted fw-normal">(Bonus)</span>
                            </label>
                            <select class="form-select <?= isset($errors['priority']) ? 'is-invalid' : '' ?>" id="priority" name="priority">
                                <option value="Low" <?= $priority === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High</option>
                            </select>
                            <?php if (isset($errors['priority'])): ?>
                                <div class="invalid-feedback"><?= e($errors['priority']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Due Date (Bonus Feature) -->
                        <div class="col-md-4">
                            <label for="due_date" class="form-label fw-semibold text-slate-700">
                                Due Date <span class="text-muted fw-normal">(Bonus)</span>
                            </label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="<?= e($dueDate) ?>">
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback"><?= e($errors['due_date']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

