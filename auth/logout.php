<?php
/**
 * Logout Controller
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If user is logged in, perform full session destruction
if (is_logged_in()) {
    logout_user();
}

// Redirect to login page with explicit logged out confirmation parameter
redirect('auth/login.php?logged_out=1');

