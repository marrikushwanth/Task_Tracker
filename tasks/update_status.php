<?php
/**
 * Quick Task Status Update Controller
 * Task Tracker - RTIH Technical Assignment
 * 
 * Allows users to update task status directly from the dashboard or task list.
 * Enforces ownership check:
 * UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id
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

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'Invalid request method. POST required.'], 405);
    }
    set_flash('danger', 'Invalid request method.');
    redirect('tasks/index.php');
}

// 1. Verify CSRF Token
verify_csrf_request(true);

$taskId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = trim($_POST['status'] ?? '');

// 2. Validate Status (strictly three allowed statuses)
$allowedStatuses = ['Pending', 'In Progress', 'Completed'];
if (!in_array($status, $allowedStatuses, true)) {
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'Invalid status selected.'], 422);
    }
    set_flash('danger', 'Invalid status selected.');
    redirect('tasks/index.php');
}

if ($taskId <= 0) {
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'Invalid task ID.'], 400);
    }
    set_flash('danger', 'Invalid task ID.');
    redirect('tasks/index.php');
}

try {
    $pdo = getDBConnection();

    // 3. Strict Ownership Check in UPDATE query
    $stmt = $pdo->prepare('
        UPDATE tasks 
        SET status = :status, updated_at = NOW() 
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([
        'status'  => $status,
        'id'      => $taskId,
        'user_id' => $currentUser['id']
    ]);

    if ($stmt->rowCount() > 0) {
        if ($isAjax) {
            json_response([
                'success'      => true,
                'message'      => "Task status updated to {$status}.",
                'task_id'      => $taskId,
                'new_status'   => $status,
                'badge_html'   => render_status_badge($status)
            ]);
        }
        set_flash('success', "Task status updated to {$status}.");
    } else {
        // Task not found or not owned by user
        error_log(sprintf('[TaskTracker Security] User %d attempted unauthorized status change on Task %d', $currentUser['id'], $taskId));
        if ($isAjax) {
            json_response(['success' => false, 'message' => 'Task not found or access denied.'], 403);
        }
        set_flash('danger', 'Task not found or access denied.');
    }
} catch (PDOException $e) {
    error_log('[TaskTracker Update Status Error] ' . $e->getMessage());
    if ($isAjax) {
        json_response(['success' => false, 'message' => 'A database error occurred.'], 500);
    }
    set_flash('danger', 'Failed to update task status.');
}

// Safe redirect handling for non-AJAX fallback
// Mitigates Open Redirect vulnerabilities while seamlessly supporting both localhost and 127.0.0.1
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referer)) {
    $parsedReferer = parse_url($referer);
    $refererHost = strtolower($parsedReferer['host'] ?? '');
    
    // Determine allowed local hostnames
    $currentHostName = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
    $allowedHosts = array_unique([$currentHostName, 'localhost', '127.0.0.1']);
    
    // Only accept referers from verified local hosts
    if (in_array($refererHost, $allowedHosts, true)) {
        $safePath = $parsedReferer['path'] ?? '';
        $safeQuery = isset($parsedReferer['query']) ? '?' . $parsedReferer['query'] : '';
        $safeTarget = str_replace(["\r", "\n"], '', $safePath . $safeQuery);
        
        if (!empty($safeTarget)) {
            header("Location: " . $safeTarget);
            exit;
        }
    }
}

redirect('tasks/index.php');

