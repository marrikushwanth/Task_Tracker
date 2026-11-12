-- =====================================================================
-- Task Tracker Web Application - Database Schema
-- Internship Technical Assignment: Ratan Tata Innovation Hub (RTIH)
-- =====================================================================

-- 1. Create Database
CREATE DATABASE IF NOT EXISTS `task_tracker`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `task_tracker`;

-- =====================================================================
-- 2. Drop Existing Tables (in reverse dependency order for clean re-run)
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 3. Users Table
-- =====================================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. Tasks Table
-- =====================================================================
CREATE TABLE `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    `priority` ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    `due_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_user_id` (`user_id`),
    KEY `idx_tasks_status` (`status`),
    KEY `idx_tasks_priority` (`priority`),
    KEY `idx_tasks_due_date` (`due_date`),
    KEY `idx_tasks_created_at` (`created_at`),
    CONSTRAINT `fk_tasks_users`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. Seed Data for Evaluation & Local Testing
-- Password for both test users is: Password123!
-- Passwords are encrypted using bcrypt via PHP password_hash()
-- =====================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Alex Rivera', 'alex@example.com', '$2y$10$qXHD5lWXSCQvutgWSAa/y.0oLLlAQyRaIG8wGvZaiOvFswh6kVSb.', NOW()),
(2, 'Sarah Chen', 'sarah@example.com', '$2y$10$qXHD5lWXSCQvutgWSAa/y.0oLLlAQyRaIG8wGvZaiOvFswh6kVSb.', NOW());

-- Tasks for User 1 (Alex Rivera)
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`) VALUES
(1, 1, 'Review RTIH Internship Requirements', 'Carefully read the project specification, security expectations, and bonus features before building.', 'Completed', 'High', DATE_ADD(CURRENT_DATE(), INTERVAL -2 DAY), NOW()),
(2, 1, 'Design Database Schema and Relationships', 'Set up InnoDB tables with foreign key constraints, ON DELETE CASCADE, and performance indexes.', 'Completed', 'High', DATE_ADD(CURRENT_DATE(), INTERVAL -1 DAY), NOW()),
(3, 1, 'Implement PDO Authentication & CSRF Shield', 'Write clean PHP session management, password hashing, and anti-CSRF token verification.', 'In Progress', 'High', CURRENT_DATE(), NOW()),
(4, 1, 'Build SaaS Modern Dashboard Interface', 'Create responsive layout with Bootstrap 5, stats analytics cards, search and filter controls.', 'In Progress', 'Medium', DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY), NOW()),
(5, 1, 'Run Comprehensive Security Audit', 'Execute all 19 test scenarios: SQL injection prevention, XSS escaping, IDOR boundary checks.', 'Pending', 'High', DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), NOW()),
(6, 1, 'Draft Documentation and Testing Logs', 'Write detailed README.md, TESTING.md, PROJECT_REPORT.md and capture application screenshots.', 'Pending', 'Low', DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY), NOW());

-- Tasks for User 2 (Sarah Chen) - Used to verify strict User A / User B isolation
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`) VALUES
(7, 2, 'Sarah\'s Private Innovation Roadmap', 'Confidential project planning for Q1 innovation sprint. Must never be visible to Alex.', 'In Progress', 'High', DATE_ADD(CURRENT_DATE(), INTERVAL 4 DAY), NOW()),
(8, 2, 'Prepare Investor Presentation Slides', 'Draft keynote deck highlighting key milestones and technical architecture metrics.', 'Pending', 'Medium', DATE_ADD(CURRENT_DATE(), INTERVAL 10 DAY), NOW());
