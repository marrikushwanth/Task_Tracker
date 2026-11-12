<?php
/**
 * Edit Task Controller and View
 * Task Tracker - RTIH Technical Assignment
 */

$pageTitle = 'Edit Task';
$currentPage = 'tasks_edit';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$currentUser = current_user();

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($taskId <= 0) {
    set_flash('danger', 'Invalid task ID specified.');
    redirect('tasks/index.php');
}

$pdo = getDBConnection();

// Strict Ownership Verification (IDOR Protection):
// User A can NEVER view or edit User B's task!
$task = verify_task_ownership($pdo, $taskId, $currentUser['id']);

if (!$task) {
    error_log(sprintf('[TaskTracker Security Alert] User %d attempted unauthorized access to Task %d', $currentUser['id'], $taskId));
    set_flash('danger', 'Task not found or you do not have permission to access it.');
    redirect('tasks/index.php');
}

$errors = [];
$title = $task['title'];
$description = $task['description'] ?? '';
$status = $task['status'];
$priority = $task['priority'];
$dueDate = $task['due_date'] ?? '';

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
            // 3. Update query strictly bound to user_id to prevent tampering
            $updateStmt = $pdo->prepare('
                UPDATE tasks
                SET title = :title,
                    description = :description,
                    status = :status,
                    priority = :priority,
                    due_date = :due_date,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ');

            $updateStmt->execute([
                'id'          => $taskId,
                'user_id'     => $currentUser['id'],
                'title'       => $validation['data']['title'],
                'description' => $validation['data']['description'],
                'status'      => $validation['data']['status'],
                'priority'    => $validation['data']['priority'],
                'due_date'    => $validation['data']['due_date']
            ]);

            set_flash('success', 'Task "' . htmlspecialchars($validation['data']['title'], ENT_QUOTES, 'UTF-8') . '" has been updated successfully!');
            redirect('tasks/index.php');
        } catch (PDOException $e) {
            error_log('[TaskTracker Edit Task Error] ' . $e->getMessage());
            $errors['general'] = 'Failed to update task due to a server error. Please try again.';
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
            <span class="text-muted small">Task ID: #<?= (int)$taskId ?></span>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                <div><?= e($errors['general']) ?></div>
            </div>
        <?php endif; ?>

        <!-- Edit Task Card -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-warning-subtle rounded-2 text-warning-emphasis">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-slate-800">Edit Task</h2>
                        <p class="text-muted small mb-0">Modify task details and save changes</p>
                    </div>
                </div>
                <div>
                    <?= render_status_badge($task['status']) ?>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$taskId ?>" method="POST" novalidate id="taskEditForm">
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
                            <div class="form-text text-muted small">A concise summary of what needs to be done.</div>
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

                    <!-- Meta info -->
                    <div class="p-3 bg-light rounded-2 mb-4 d-flex flex-wrap justify-content-between text-muted small">
                        <span><i class="bi bi-calendar-plus me-1"></i>Created: <?= format_date($task['created_at']) ?></span>
                        <span><i class="bi bi-clock-history me-1"></i>Last Updated: <?= time_ago($task['updated_at']) ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <button type="button" 
                                class="btn btn-outline-danger btn-sm delete-task-btn" 
                                data-id="<?= (int)$taskId ?>" 
                                data-title="<?= e($task['title']) ?>">
                            <i class="bi bi-trash me-1"></i> Delete Task
                        </button>

                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= BASE_URL ?>/tasks/index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Update Task
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Delete Confirmation -->
<?php include __DIR__ . '/_delete_modal.php'; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
