<?php
/**
 * CSRF Protection Module
 * Task Tracker - RTIH Technical Assignment
 * 
 * Provides cryptographically secure token generation, embedding,
 * and timing-attack resistant validation for all state-changing operations.
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Generate or retrieve the current session's CSRF token.
 * 
 * @return string
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output an HTML hidden input tag containing the CSRF token.
 * 
 * @return string
 */
function csrf_field(): string {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate a submitted CSRF token against the session token.
 * Uses timing-attack resistant hash_equals.
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF on state-changing requests (POST, PUT, DELETE, PATCH).
 * Inspects both $_POST['csrf_token'] and HTTP header 'X-CSRF-Token'.
 * 
 * @param bool $terminateOnFailure Whether to stop execution immediately with HTTP 403
 * @return bool
 */
function verify_csrf_request(bool $terminateOnFailure = true): bool {
    // Only enforce for state-changing HTTP methods
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        return true;
    }

    $token = $_POST['csrf_token'] ?? null;
    if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if (!verify_csrf_token($token)) {
        error_log('[TaskTracker Security] CSRF validation failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));
        
        if ($terminateOnFailure) {
            http_response_code(403);
            
            // Check if AJAX request
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Security token invalid or expired. Please refresh the page and try again.'
                ]);
            } else {
                die('
                    <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 40px; text-align: center; background: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
                        <div style="background: #ffffff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 440px; width: 100%;">
                            <div style="color: #f59e0b; font-size: 40px; margin-bottom: 12px;">&#9888;</div>
                            <h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">Invalid Security Token</h2>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">
                                Your session security token could not be verified. This can happen if the page was idle for too long or submitted from an unauthorized source.
                            </p>
                            <a href="javascript:history.back()" style="display: inline-block; margin-top: 16px; padding: 10px 20px; background: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 14px;">Go Back &amp; Retry</a>
                        </div>
                    </div>
                ');
            }
            exit;
        }
        return false;
    }

    return true;
}

