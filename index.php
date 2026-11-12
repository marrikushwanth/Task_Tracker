<?php
/**
 * Root Entry Point
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('auth/login.php');
}
