<?php
/**
 * Helper Functions and Utility Module
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escape output for safe rendering in HTML templates (XSS Protection).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize simple text input (strip tags, normalize whitespace).
 *
 * @param mixed $input
 * @return string
 */
function sanitize_string($input): string {
    if ($input === null) {
        return '';
    }
    return trim(strip_tags((string)$input));
}

/**
 * Set a flash notification message stored in session.
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all pending flash notification messages.
 *
 * @return array
 */
function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Safe HTTP redirection helper.
 *
 * @param string $path Target path or full URL
 * @return void
 */
function redirect(string $path): void {
    // If relative path without scheme, prefix with BASE_URL
    if (!preg_match('#^https?://#i', $path)) {
        $path = BASE_URL . '/' . ltrim($path, '/');
    }
    header("Location: $path");
    exit;
}

/**
 * Return JSON response and terminate.
 *
 * @param mixed $data
 * @param int $statusCode
 * @return void
 */
function json_response($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Validate a date string against a format.
 *
 * @param string $date
 * @param string $format
 * @return bool
 */
function validate_date(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Format date for friendly display.
 *
 * @param string|null $dateStr
 * @return string
 */
function format_date(?string $dateStr): string {
    if (empty($dateStr)) {
        return 'No deadline';
    }
    try {
        $date = new DateTime($dateStr);
        return $date->format('M d, Y');
    } catch (Exception $e) {
        return e($dateStr);
    }
}

/**
 * Determine if a task is overdue.
 * A task is overdue if its due date is before today and its status is not 'Completed'.
 *
 * @param string|null $dueDate
 * @param string $status
 * @return bool
 */
function is_overdue(?string $dueDate, string $status): bool {
    if (empty($dueDate) || $status === 'Completed') {
        return false;
    }
    $today = date('Y-m-d');
    return $dueDate < $today;
}

/**
 * Return friendly relative time string (e.g. "2 hours ago").
 *
 * @param string|int $timestamp
 * @return string
 */
function time_ago($timestamp): string {
    $time = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
    if (!$time) {
        return '';
    }

    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Render visual status badge HTML with icons.
 *
 * @param string $status
 * @return string
 */
function render_status_badge(string $status): string {
    switch ($status) {
        case 'Completed':
            return '<span class="badge badge-status badge-status-completed"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
        case 'In Progress':
            return '<span class="badge badge-status badge-status-inprogress"><i class="bi bi-arrow-repeat me-1 spin-icon"></i>In Progress</span>';
        case 'Pending':
        default:
            return '<span class="badge badge-status badge-status-pending"><i class="bi bi-clock-fill me-1"></i>Pending</span>';
    }
}

/**
 * Render visual priority badge HTML with icons.
 *
 * @param string $priority
 * @return string
 */
function render_priority_badge(string $priority): string {
    switch ($priority) {
        case 'High':
            return '<span class="badge badge-priority badge-priority-high"><i class="bi bi-fire me-1"></i>High</span>';
        case 'Medium':
            return '<span class="badge badge-priority badge-priority-medium"><i class="bi bi-dash-circle-fill me-1"></i>Medium</span>';
        case 'Low':
        default:
            return '<span class="badge badge-priority badge-priority-low"><i class="bi bi-arrow-down-circle me-1"></i>Low</span>';
    }
}

/**
 * Comprehensive server-side validation for task inputs.
 *
 * @param array $data
 * @return array ['valid' => bool, 'errors' => array, 'data' => array]
 */
function validate_task_input(array $data): array {
    $errors = [];
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $status = trim($data['status'] ?? 'Pending');
    $priority = trim($data['priority'] ?? 'Medium');
    $dueDate = trim($data['due_date'] ?? '');

    // Title validation
    if ($title === '') {
        $errors['title'] = 'Task title is required.';
    } elseif (mb_strlen($title) < 3) {
        $errors['title'] = 'Task title must be at least 3 characters long.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Task title cannot exceed 255 characters.';
    }

    // Description validation
    if ($description !== '' && mb_strlen($description) > 2000) {
        $errors['description'] = 'Description cannot exceed 2000 characters.';
    }

    // Status validation (strictly three statuses)
    $allowedStatuses = ['Pending', 'In Progress', 'Completed'];
    if (!in_array($status, $allowedStatuses, true)) {
        $errors['status'] = 'Invalid status selected.';
    }

    // Priority validation
    $allowedPriorities = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    // Due date validation
    if ($dueDate !== '') {
        if (!validate_date($dueDate, 'Y-m-d')) {
            $errors['due_date'] = 'Invalid due date format. Please use YYYY-MM-DD.';
        }
    } else {
        $dueDate = null;
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'data'   => [
            'title'       => $title,
            'description' => $description === '' ? null : $description,
            'status'      => $status,
            'priority'    => $priority,
            'due_date'    => $dueDate
        ]
    ];
}

/**
 * Retrieve aggregated task metrics for the authenticated user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function get_user_task_metrics(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END), 0) AS pending,
            COALESCE(SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END), 0) AS in_progress,
            COALESCE(SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END), 0) AS completed,
            COALESCE(SUM(CASE WHEN status != "Completed" AND due_date IS NOT NULL AND due_date < CURRENT_DATE() THEN 1 ELSE 0 END), 0) AS overdue
        FROM tasks
        WHERE user_id = :user_id
    ');
    $stmt->execute(['user_id' => $userId]);
    $stats = $stmt->fetch();

    $total = (int)($stats['total'] ?? 0);
    $completed = (int)($stats['completed'] ?? 0);
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

    return [
        'total'       => $total,
        'pending'     => (int)($stats['pending'] ?? 0),
        'in_progress' => (int)($stats['in_progress'] ?? 0),
        'completed'   => $completed,
        'overdue'     => (int)($stats['overdue'] ?? 0),
        'percentage'  => $percentage
    ];
}

