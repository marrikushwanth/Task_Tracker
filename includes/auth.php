<?php
/**
 * Authentication and Authorization Guard Module
 * Task Tracker - RTIH Technical Assignment
 * 
 * Enforces session security, route protection, login/logout,
 * and strict user-task ownership checks (IDOR prevention).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Determine if an authenticated user session is active.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Get the currently authenticated user's session profile.
 *
 * @return array|null
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

/**
 * Require authentication for protected routes.
 * If user is not authenticated, redirects to login page with an alert.
 * Emits anti-caching headers so protected pages cannot be seen via browser Back button after logout.
 *
 * @return void
 */
function require_auth(): void {
    // Prevent browser back-button caching of protected content
    header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

    if (!is_logged_in()) {
        // Store intended destination URL for post-login redirect
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('warning', 'Please log in to access your task dashboard.');
        redirect('auth/login.php');
    }
}

/**
 * Require unauthenticated (guest) status for auth routes (login, register).
 * If already logged in, redirects to the dashboard.
 *
 * @return void
 */
function require_guest(): void {
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

/**
 * Perform secure user login.
 * Regenerates session ID to prevent session fixation attacks.
 *
 * @param array $user Database user record
 * @return void
 */
function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['last_activity'] = time();

    // Re-seed CSRF token for the new authenticated session
    unset($_SESSION['csrf_token']);
}

/**
 * Perform secure user logout.
 * Cleans up session variables, invalidates the cookie, and destroys the session.
 *
 * @return void
 */
function logout_user(): void {
    // Clear all session variables
    $_SESSION = [];

    // Delete the session cookie from the browser
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the server-side session
    session_destroy();

    // Start a temporary session solely to display the logout confirmation notice
    session_start();
    set_flash('info', 'You have been logged out successfully.');
}

/**
 * Strict Task Ownership Verification (Backend Authorization).
 * Enforces ownership check: SELECT * FROM tasks WHERE id = ? AND user_id = ?
 * Never trusts user_id from client input; uses session user_id.
 *
 * @param PDO $pdo
 * @param int $taskId
 * @param int $userId
 * @return array|null Returns task row if owned, null otherwise
 */
function verify_task_ownership(PDO $pdo, int $taskId, int $userId): ?array {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = :task_id AND user_id = :user_id LIMIT 1');
    $stmt->execute([
        'task_id' => $taskId,
        'user_id' => $userId
    ]);
    $task = $stmt->fetch();
    return $task ?: null;
}
