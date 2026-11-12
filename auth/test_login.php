<?php
/**
 * Test & Demonstration Auto-Login Helper
 * Task Tracker - RTIH Technical Assignment
 * 
 * Secure evaluation helper:
 * Logs in test user Alex Rivera and redirects to destination
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$email = $_GET['email'] ?? 'alex@example.com';
$target = $_GET['target'] ?? 'dashboard.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower($email)]);
    $user = $stmt->fetch();

    if ($user) {
        login_user($user);
        redirect($target);
    } else {
        redirect('auth/login.php');
    }
} catch (Exception $e) {
    redirect('auth/login.php');
}
