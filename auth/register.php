<?php
/**
 * User Registration Controller and View
 * Task Tracker - RTIH Technical Assignment
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect authenticated users away from register
require_guest();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    verify_csrf_request(true);

    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 2. Comprehensive Server-Side Validation
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Full name must be between 2 and 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 191) {
        $errors['email'] = 'Email address is too long.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 3. Database Check: Prevent duplicate email
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => strtolower($email)]);
            if ($stmt->fetch()) {
                $errors['email'] = 'An account with this email address is already registered.';
            } else {
                // 4. Secure Password Hashing
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $insertStmt = $pdo->prepare('
                    INSERT INTO users (name, email, password, created_at)
                    VALUES (:name, :email, :password, NOW())
                ');
                $insertStmt->execute([
                    'name'     => $name,
                    'email'    => strtolower($email),
                    'password' => $passwordHash
                ]);

                $newUserId = (int)$pdo->lastInsertId();

                // 5. Auto-login newly registered user
                login_user([
                    'id'    => $newUserId,
                    'name'  => $name,
                    'email' => strtolower($email)
                ]);

                // Create a welcome starter task for the new user
                $welcomeStmt = $pdo->prepare('
                    INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at)
                    VALUES (:user_id, :title, :description, :status, :priority, :due_date, NOW())
                ');
                $welcomeStmt->execute([
                    'user_id'     => $newUserId,
                    'title'       => 'Welcome to TaskFlow! Explore your dashboard',
                    'description' => 'You can edit, change status, or delete this starter task to explore all features.',
                    'status'      => 'Pending',
                    'priority'    => 'Medium',
                    'due_date'    => date('Y-m-d', strtotime('+3 days'))
                ]);

                set_flash('success', 'Account created successfully! Welcome to TaskFlow, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '.');
                redirect('dashboard.php');
            }
        } catch (PDOException $e) {
            error_log('[TaskTracker Registration Error] ' . $e->getMessage());
            $errors['general'] = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account &mdash; TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <!-- Brand Header -->
                <div class="text-center mb-4">
                    <div class="auth-logo mx-auto mb-3">
                        <i class="bi bi-check2-square fs-2 text-white"></i>
                    </div>
                    <h1 class="h3 fw-bold text-slate-900 mb-1">Create an Account</h1>
                    <p class="text-muted small">Join TaskFlow to organize and manage your tasks effectively</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?= e($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form action="<?= BASE_URL ?>/auth/register.php" method="POST" novalidate id="registerForm">
                    <?= csrf_field() ?>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-slate-700">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" 
                                   class="form-control ps-2 border-start-0 <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   placeholder="e.g. Alex Rivera" 
                                   value="<?= e($name) ?>" 
                                   required 
                                   autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-slate-700">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" 
                                   class="form-control ps-2 border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   placeholder="name@example.com" 
                                   value="<?= e($email) ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-slate-700">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" 
                                   class="form-control ps-2 border-start-0 border-end-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" 
                                   name="password" 
                                   placeholder="At least 8 characters (letter + number)" 
                                   required>
                            <button type="button" class="btn btn-outline-light border border-start-0 text-muted toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text text-muted small">Must be at least 8 characters with letters and numbers.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold text-slate-700">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   class="form-control ps-2 border-start-0 border-end-0 <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Re-enter password" 
                                   required>
                            <button type="button" class="btn btn-outline-light border border-start-0 text-muted toggle-password" data-target="confirm_password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </button>

                    <!-- Switch to Login -->
                    <div class="text-center text-muted small">
                        Already have an account? 
                        <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-semibold text-decoration-none">Sign In</a>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light border-0 py-3 text-center text-muted small">
                RTIH Technical Internship Assignment &bull; TaskFlow &bull; Secure Auth
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
