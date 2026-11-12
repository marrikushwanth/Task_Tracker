<?php
/**
 * Delete Task Controller
 * Task Tracker - RTIH Technical Assignment
 * 
 * Strict ownership check:
 * DELETE FROM tasks WHERE id = :id AND user_id = :user_id
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$currentUser = current_user();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Security: Reject GET deletion attempts (prevents CSRF via image tags, crawlers, or prefetching)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'Invalid request method. POST required.'], 405);
    }
    set_flash('danger', 'Invalid deletion request method.');
    redirect('tasks/index.php');
}

// 1. Verify CSRF Token
verify_csrf_request(true);

$taskId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($taskId <= 0) {
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'Invalid task ID specified.'], 400);
    }
    set_flash('danger', 'Invalid task ID specified.');
    redirect('tasks/index.php');
}

try {
    $pdo = getDBConnection();

    // 2. Strict IDOR Authorization Check:
    // Delete only if both ID and USER_ID match the authenticated user!
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
    $stmt->execute([
        'id'      => $taskId,
        'user_id' => $currentUser['id']
    ]);

    $deletedRows = $stmt->rowCount();

    if ($deletedRows > 0) {
        if ($isAjax) {
            json_response([
                'success' => true,
                'message' => 'Task #' . $taskId . ' has been deleted successfully.',
                'task_id' => $taskId
            ]);
        }
        set_flash('success', 'Task has been deleted successfully.');
    } else {
        // Either task doesn't exist or belongs to another user
        error_log(sprintf('[TaskTracker Security] User %d attempted unauthorized delete on Task %d', $currentUser['id'], $taskId));
        if ($isAjax) {
            json_response([
                'success' => false,
                'message' => 'Task not found or access denied.'
            ], 403);
        }
        set_flash('danger', 'Task not found or you do not have permission to delete it.');
    }
} catch (PDOException $e) {
    error_log('[TaskTracker Delete Error] ' . $e->getMessage());
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'A database error occurred while deleting task.'], 500);
    }
    set_flash('danger', 'Failed to delete task due to a server error.');
}

redirect('tasks/index.php');
