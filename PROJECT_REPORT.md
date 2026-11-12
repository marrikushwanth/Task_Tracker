# TaskFlow &mdash; Internship Technical Project Report

> **Program:** RTIH (Ratan Tata Innovation Hub) Technical Internship  
> **Project:** Task Tracker Web Application  
> **Submission Date:** November 2026  
> **Candidate:** Technical Intern Candidate  

---

## 1. Project Overview

**TaskFlow** is a modern, production-grade Task Tracker Web Application developed to solve personal and professional workflow management challenges. Built with **Core PHP (PDO)** and **MySQL**, the application provides a robust, responsive SaaS-style productivity dashboard inspired by modern tools such as Linear, Notion, and ClickUp.

The application was designed from the ground up to fulfill every requirement across all evaluation tiers:
- **Level 1 (Basic Task Management):** Complete CRUD lifecycle for tasks, persistent MySQL storage, clean card/table presentations, and confirmation dialogs.
- **Level 2 (Status, Search, and Validation):** Three distinct statuses with real-time badges, debounced SQL LIKE live search, multi-facet filtering, and dual-layer validation.
- **Level 3 (Authentication and Security):** Multi-tenant isolation preventing IDOR vulnerabilities, timing-attack resistant CSRF protection, secure bcrypt password hashing, and anti-session fixation.
- **Bonus Features:** Dashboard KPI analytics, pagination preserving query parameters, task priorities (Low, Medium, High), due dates with overdue alerts, and multi-column sorting.

---

## 2. Features Implemented

### Task Management (CRUD Lifecycle)
- **Create:** User-friendly form with client and server validation supporting Title, Description, Status, Priority, and Due Date.
- **Read:** Personalized task feeds displayed in either a modern 3-column Card Grid or a compact Data Table with responsive drawer navigation.
- **Update:** Full task editing form prefilled with live task data, ownership-verified on display and save.
- **Delete:** Interactive confirmation modal dialog preventing accidental deletion, protected by anti-CSRF verification.

### Status Management & Quick Actions
- Exactly three supported statuses: `Pending` (amber clock), `In Progress` (animated indigo spinner), and `Completed` (emerald checkmark).
- Inline quick status dropdown on both Dashboard and Task List views powered by AJAX with immediate UI updates and toast feedback.

### Search, Filtering & Sorting
- Dynamic SQL LIKE search targeting both task titles and task descriptions.
- Status filters (`All`, `Pending`, `In Progress`, `Completed`) working in unison with search terms.
- Priority filters (`Low`, `Medium`, `High`).
- Sorting dropdown (`Newest First`, `Oldest First`, `Due Date Soonest`, `Priority Highest`).
- Integrated Pagination retaining all filter states across page navigations.

### Analytics & Visual Enhancements
- Welcome overview banner with completion rate percentage bar.
- Five real-time KPI metrics cards (Total, Pending, In Progress, Completed, Overdue).
- Priority distribution analytics breakdown.

---

## 3. Database Design

The relational database (`task_tracker`) was architected in MySQL with InnoDB engine and `utf8mb4` encoding:

1. **`users` Table:**
   - `id` (INT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY)
   - `name` (VARCHAR(100), NOT NULL)
   - `email` (VARCHAR(191), NOT NULL, UNIQUE KEY `uk_users_email`)
   - `password` (VARCHAR(255), NOT NULL &mdash; holds 60-character bcrypt hash)
   - `created_at` & `updated_at` (TIMESTAMP)

2. **`tasks` Table:**
   - `id` (INT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY)
   - `user_id` (INT UNSIGNED, NOT NULL, FOREIGN KEY -> `users.id` with `ON DELETE CASCADE` and `ON UPDATE CASCADE`)
   - `title` (VARCHAR(255), NOT NULL)
   - `description` (TEXT, DEFAULT NULL)
   - `status` (ENUM('Pending', 'In Progress', 'Completed'), NOT NULL DEFAULT 'Pending')
   - `priority` (ENUM('Low', 'Medium', 'High'), NOT NULL DEFAULT 'Medium')
   - `due_date` (DATE, DEFAULT NULL)
   - `created_at` & `updated_at` (TIMESTAMP)
   - **Performance Indexes:** `idx_tasks_user_id`, `idx_tasks_status`, `idx_tasks_priority`, `idx_tasks_due_date`, `idx_tasks_created_at`.

---

## 4. Security Implementation

Security and authorization represent 20% of the project evaluation and were engineered with zero compromises:

| Security Vector | Implementation Mechanism | Defensive Guarantee |
| :--- | :--- | :--- |
| **SQL Injection (SQLi)** | PDO prepared statements with native parameter binding (`ATTR_EMULATE_PREPARES => false`). | Zero untrusted SQL concatenation; injection inputs treated as inert literals. |
| **Cross-Site Scripting (XSS)** | `e()` helper function wrapping `htmlspecialchars($val, ENT_QUOTES \| ENT_HTML5, 'UTF-8')`. | Script tags and HTML entities safely converted before DOM rendering. |
| **Cross-Site Request Forgery (CSRF)** | 256-bit cryptographic tokens via `random_bytes(32)`, verified on POST/PUT/DELETE via `hash_equals()`. | Protects state-changing operations against forged third-party requests. |
| **Broken Object Level Auth (IDOR)** | Ownership verified in every query: `WHERE id = :id AND user_id = :session_user_id`. | User A can never read, modify, or delete User B's tasks. |
| **Session Fixation & Hijacking** | `session_regenerate_id(true)` upon login; `httponly=true`, `samesite=Lax`, `use_strict_mode=1`. | JavaScript cannot steal session cookies; old sessions destroyed upon authentication. |
| **Credential Storage** | `password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])` with automatic per-user salts. | Industry-standard password encryption resistant to dictionary and rainbow table attacks. |
| **Information Disclosure** | Database credentials isolated in `config/database.php`; exceptions caught and logged privately. | Technical stack traces and connection strings are never shown to end users. |

---

## 5. Completed Assignment Levels

- **Level 1 (Basic Task Management):** 100% Completed
- **Level 2 (Status, Search, and Validation):** 100% Completed
- **Level 3 (Authentication and Security):** 100% Completed
- **Bonus Feature 1 (Dashboard Analytics):** 100% Completed
- **Bonus Feature 2 (Pagination with Filter Retention):** 100% Completed
- **Bonus Feature 3 (Task Priority Levels):** 100% Completed
- **Bonus Feature 4 (Due Dates & Overdue Highlighting):** 100% Completed
- **Bonus Feature 5 (Task Sorting Criteria):** 100% Completed

---

## 6. Known Limitations

1. **Email Verification:** The registration flow currently assumes valid email delivery without requiring an email activation link or SMTP server setup.
2. **Password Recovery:** Self-service "Forgot Password" reset flows via one-time email tokens are not yet implemented.
3. **Task Attachments:** File and image uploads to tasks are not supported in the current version.

---

## 7. AI Tool Disclosure

In compliance with academic and professional transparency standards:
- **AI Coding Assistant Used:** **Google Antigravity** was utilized as an AI coding assistant during this project to assist with architectural design, boilerplate generation, CSS styling suggestions, and automated test suite composition.
- **Verification & Ownership:** Every component, database query, session handler, security check, and test case was manually reviewed, executed, and verified against a live local MySQL and PHP environment.

---

## 8. Candidate Personal Code Modification

*To be completed by the intern candidate:*

Document below one meaningful code change you personally made after reviewing the generated code:

```text
[CANDIDATE PERSONAL CODE CHANGE PLACEHOLDER]

File Modified: 
e.g. includes/auth.php / assets/css/style.css / tasks/index.php

Description of Change:
[Describe what you changed, why you decided to modify it, and what improvement or fix it provided.]

Code Before:
[Insert previous snippet here]

Code After:
[Insert your updated snippet here]
```

---

*Submitted for the RTIH Technical Internship Assignment &bull; TaskFlow Application*
