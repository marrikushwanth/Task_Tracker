<?php
/**
 * Application Configuration
 * Task Tracker - RTIH Technical Assignment
 */

// Prevent multiple inclusions
if (defined('APP_INITIALIZED')) {
    return;
}
define('APP_INITIALIZED', true);

// Application details
define('APP_NAME', 'TaskFlow');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // 'development' or 'production'

// Default Timezone
date_default_timezone_set('Asia/Kolkata');

// Configure secure session parameters BEFORE session_start
if (session_status() === PHP_SESSION_NONE) {
    // 2 hours session lifetime
    $sessionLifetime = 7200;
    
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0, // cookie lasts until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

/**
 * Dynamically detect application base URL
 * Works in:
 *  - PHP Built-in server (e.g. http://localhost:8000)
 *  - XAMPP/Apache root (e.g. http://localhost)
 *  - XAMPP/Apache subdirectory (e.g. http://localhost/Task_Tracker or /task-tracker)
 */
function get_base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine path prefix relative to document root
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)));
    
    $subDir = '';
    if (!empty($docRoot) && strpos($appRoot, $docRoot) === 0) {
        $subDir = substr($appRoot, strlen($docRoot));
    }
    
    $subDir = '/' . trim($subDir, '/');
    if ($subDir === '/') {
        $subDir = '';
    }
    
    return $protocol . $host . $subDir;
}

define('BASE_URL', get_base_url());

