<?php
/**
 * Database Configuration and PDO Connection Manager
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/config.php';

// Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'task_tracker');
define('DB_USER', getenv('DB_USER') ?: 'root');
// Primary password: check env, default to 'mysql' (current local server) or '' (standard XAMPP)
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'mysql');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO database connection instance.
 * Implements PDO Prepared Statements & Secure Error Handling.
 * 
 * @return PDO
 * @throws RuntimeException If database connection fails
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements for maximum security
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
    ];

    // Password candidates: DB_PASS, then empty string '' (standard XAMPP default), then 'root'
    $candidatePasswords = array_unique([DB_PASS, '', 'root', 'mysql']);

    $lastException = null;
    foreach ($candidatePasswords as $candidatePass) {
        try {
            $pdo = new PDO($dsn, DB_USER, $candidatePass, $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            // Continue trying fallback candidates
        }
    }

    // Technical error logging (private, never exposed to user)
    error_log('[TaskTracker DB Error] Connection failed: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));

    // Friendly, sanitized message for the end user without exposing credentials or stack traces
    http_response_code(500);
    die('
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 40px; text-align: center; background: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
            <div style="background: #ffffff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 480px; width: 100%;">
                <div style="color: #ef4444; font-size: 40px; margin-bottom: 12px;">&#9888;</div>
                <h2 style="color: #0f172a; margin-top: 0; font-size: 20px;">Database Connection Error</h2>
                <p style="color: #64748b; font-size: 14px; line-height: 1.6;">
                    Unable to connect to the database. Please verify that your MySQL server is running and database <code>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</code> has been imported.
                </p>
                <p style="color: #94a3b8; font-size: 12px; margin-bottom: 0;">Details have been recorded in the private system error log.</p>
            </div>
        </div>
    ');
}
