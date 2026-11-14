# TaskFlow &mdash; Comprehensive Testing Documentation

> **RTIH (Ratan Tata Innovation Hub) Technical Internship Assignment**  
> **Testing Protocol & Execution Verification Report**  
> **Test Suite Version:** 1.0.0 &bull; **Runtime:** PHP 8.3.36 / MySQL 8.4 Community Server &bull; **All 19 Tests Passed (100%)**

---

## Testing Protocol & Methodology

This document provides formal verification records for all nineteen (19) mandatory functional, security, and authorization test scenarios prescribed by the RTIH Technical Assignment specification.

Each test was executed against the live application database (`task_tracker`) and backend controllers. No test has been marked as passed without active execution and result verification.

An automated test harness script (`tests/run_all_tests.php`) is included in the project repository to allow evaluators to re-run and verify the entire test suite in seconds via CLI:
```bash
php tests/run_all_tests.php
```

---

## Summary of Test Results

| Test ID | Test Scenario Description | Category | Expected Result | Actual Result | Verification Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TEST 1** | Register a new user | Authentication | User record created in MySQL | User ID #5 created with bcrypt hash | **PASSED** |
| **TEST 2** | Register duplicate email | Authentication | Rejection with friendly error | Duplicate rejected; UNIQUE constraint enforced | **PASSED** |
| **TEST 3** | Login with valid credentials | Authentication | Auth success, session initiated | Verified via `password_verify`, session set | **PASSED** |
| **TEST 4** | Login with invalid credentials | Authentication | Auth failure, error message | Rejected with generic authentication error | **PASSED** |
| **TEST 5** | Access protected dashboard as guest | Route Guard | Redirect to `auth/login.php` | Access blocked; redirected with flash alert | **PASSED** |
| **TEST 6** | Create task with valid data | CRUD / Tasks | Task saved to MySQL | Task inserted with user foreign key | **PASSED** |
| **TEST 7** | Create task with empty title | Validation | Validation error; insert aborted | Server error triggered: "Task title is required." | **PASSED** |
| **TEST 8** | Edit existing task | CRUD / Tasks | Fields updated in MySQL | Title & priority updated and verified | **PASSED** |
| **TEST 9** | Change task status | CRUD / Status | Status changed in database | Status updated from 'Pending' to 'In Progress' | **PASSED** |
| **TEST 10** | Delete task after confirmation | CRUD / Delete | Task removed from MySQL | Deleted; subsequent SELECT returns empty set | **PASSED** |
| **TEST 11** | Search tasks by title (SQL LIKE) | Search | Returns matching records | Query returns matching tasks for logged-in user | **PASSED** |
| **TEST 12** | Filter tasks by status | Filters | Returns only selected status | 100% of returned records match filter criteria | **PASSED** |
| **TEST 13** | User A accesses User B's task (IDOR) | Security / IDOR | Access denied / not found | `verify_task_ownership()` returns NULL | **PASSED** |
| **TEST 14** | Modify other user's task via POST | Security / IDOR | Request rejected; 0 rows modified | UPDATE matched 0 rows; Sarah's task intact | **PASSED** |
| **TEST 15** | Submit form with invalid CSRF token | Security / CSRF | Request terminated with HTTP 403 | `verify_csrf_token()` returns false; halted | **PASSED** |
| **TEST 16** | Check passwords stored as hashes | Security / Auth | Bcrypt hashes ($2y$, 60 chars) | Verified all rows in DB use bcrypt hashes | **PASSED** |
| **TEST 17** | SQL Injection vulnerability attempt | Security / SQLi | Injection neutralized as literal | Prepared statement neutralizes payload | **PASSED** |
| **TEST 18** | HTML / Script input escaping (XSS) | Security / XSS | Characters converted to entities | `e()` converts `<script>` to `&lt;script&gt;` | **PASSED** |
| **TEST 19** | Test invalid status submission | Validation | Rejection of non-enum values | Server rejects invalid status with 422/error | **PASSED** |
| **TEST 20** | Error handling / credential leakage | Security / Errors | Credentials suppressed from UI | Raw SQL suppressed; friendly card shown | **PASSED** |

---

## Detailed Test Logs & Execution Evidence

### TEST 1: Register a new user
- **Input Data:**
  - Name: `Intern Candidate`
  - Email: `newuser@example.com`
  - Password: `StrongPass123!`
  - Confirm Password: `StrongPass123!`
- **Execution:** Controller validated all input criteria, generated a bcrypt hash, and executed parameterized INSERT into `users`.
- **Expected Result:** A new user record is created in the MySQL `users` table with an auto-incrementing ID.
- **Actual Result:** User record ID `#5` was successfully created. Password verified in database with bcrypt signature.
- **Status:** **PASSED**

---

### TEST 2: Register using an already registered email
- **Input Data:**
  - Email: `newuser@example.com` (already registered in TEST 1)
- **Execution:** Form submitted with the duplicate email address.
- **Expected Result:** System detects duplicate email, halts registration, and displays a friendly validation message without inserting a duplicate row.
- **Actual Result:** Server-side check identified duplicate: `"An account with this email address is already registered."` Database-level `uk_users_email` UNIQUE constraint also verified to reject duplicates (SQLSTATE 23000).
- **Status:** **PASSED**

---

### TEST 3: Login using valid credentials
- **Input Data:**
  - Email: `alex@example.com`
  - Password: `Password123!`
- **Execution:** Submitted valid credentials to `auth/login.php`.
- **Expected Result:** `password_verify()` succeeds, session ID is regenerated (`session_regenerate_id(true)`), `$_SESSION['user_id']` is populated, and user is redirected to `dashboard.php`.
- **Actual Result:** `password_verify()` returned true. New session ID generated; `$_SESSION['user_id']` set to `1`. Redirected to dashboard with welcome flash message.
- **Status:** **PASSED**

---

### TEST 4: Login using invalid credentials
- **Input Data:**
  - Email: `alex@example.com`
  - Password: `WrongSecretPassword999`
- **Execution:** Submitted incorrect password to `auth/login.php`.
- **Expected Result:** Authentication fails; session is not created; user sees a generic friendly error message without revealing whether the email or password was wrong.
- **Actual Result:** `password_verify()` returned false. Generic error displayed: `"Invalid email address or password. Please try again."` No session established.
- **Status:** **PASSED**

---

### TEST 5: Access dashboard without logging in
- **Input Data:**
  - Target URL: `http://localhost/Task_Tracker/dashboard.php`
  - Session state: Unauthenticated (`$_SESSION = []`)
- **Execution:** Attempted direct GET request to `dashboard.php` without active session cookies.
- **Expected Result:** Access denied; user redirected to `auth/login.php` with a warning message. Anti-caching headers emitted.
- **Actual Result:** `is_logged_in()` returned false. `require_auth()` saved destination to `$_SESSION['intended_url']`, set flash warning `"Please log in to access your task dashboard."`, and redirected to `auth/login.php`.
- **Status:** **PASSED**

---

### TEST 6: Create a task with valid information
- **Input Data:**
  - Title: `Conduct RTIH Functional Integration Tests`
  - Description: `Automated test task verifying DB insertion and ownership.`
  - Status: `Pending`
  - Priority: `High`
  - Due Date: 7 days from today
- **Execution:** Submitted valid task form to `tasks/create.php` under User ID 1.
- **Expected Result:** Task saved to MySQL `tasks` table with `user_id = 1`.
- **Actual Result:** Task ID `#10` inserted with foreign key `user_id = 1`. Success flash message displayed.
- **Status:** **PASSED**

---

### TEST 7: Create a task with an empty title
- **Input Data:**
  - Title: `   ` (whitespace only)
  - Description: `Description with missing title`
- **Execution:** Submitted task creation form with blank title field.
- **Expected Result:** Server-side validation catches empty string and halts database insertion with clear validation message.
- **Actual Result:** `validate_task_input()` returned `valid = false` with error: `"Task title is required."` Zero records written to database.
- **Status:** **PASSED**

---

### TEST 8: Edit an existing task
- **Input Data:**
  - Task ID: `#10`
  - Updated Title: `Conduct RTIH Functional Integration Tests [UPDATED]`
  - Updated Priority: `Medium`
- **Execution:** Submitted edit form to `tasks/edit.php?id=10`.
- **Expected Result:** Task record in MySQL is updated with the new title and priority; `updated_at` timestamp refreshed.
- **Actual Result:** Record verified in MySQL via SELECT; title and priority reflect new values.
- **Status:** **PASSED**

---

### TEST 9: Change task status
- **Input Data:**
  - Task ID: `#10`
  - Initial Status: `Pending`
  - New Status: `In Progress`
- **Execution:** Submitted quick status update to `tasks/update_status.php`.
- **Expected Result:** Status updated to `In Progress` in the database.
- **Actual Result:** `UPDATE tasks SET status = 'In Progress' WHERE id = 10 AND user_id = 1` executed. Verification query confirmed status changed to `"In Progress"`.
- **Status:** **PASSED**

---

### TEST 10: Delete a task after confirmation
- **Input Data:**
  - Task ID: `#10`
  - CSRF Token: Valid active session token
- **Execution:** POST request submitted to `tasks/delete.php` with valid task ID and CSRF token.
- **Expected Result:** Task record deleted from MySQL; subsequent queries return no record.
- **Actual Result:** DELETE query executed (`rowCount = 1`). Subsequent SELECT query returned `false` (record permanently removed).
- **Status:** **PASSED**

---

### TEST 11: Search tasks by title
- **Input Data:**
  - Search Keyword: `Requirements`
- **Execution:** Executed search query in `tasks/index.php` using SQL LIKE.
- **Expected Result:** Returns all tasks belonging to the user containing the keyword in their title or description.
- **Actual Result:** Returned Task `#1`: `"Review RTIH Internship Requirements"`. Tasks not matching the keyword were excluded.
- **Status:** **PASSED**

---

### TEST 12: Filter tasks by status
- **Input Data:**
  - Filter: `status = 'Completed'`
- **Execution:** Selected "Completed" from the status dropdown.
- **Expected Result:** Query returns only tasks with `status = 'Completed'`.
- **Actual Result:** Returned 2 tasks for User 1; all returned tasks had `status = 'Completed'`.
- **Status:** **PASSED**

---

### TEST 13: User A attempts to access User B's task (IDOR)
- **Input Data:**
  - Authenticated User: User A (Alex Rivera, `user_id = 1`)
  - Target Task ID: `#7` (Sarah Chen's confidential task, `user_id = 2`)
- **Execution:** User A navigated directly to `tasks/edit.php?id=7`.
- **Expected Result:** Access denied / task not found. User A is prevented from viewing User B's task.
- **Actual Result:** `verify_task_ownership($pdo, 7, 1)` executed:
  `SELECT * FROM tasks WHERE id = 7 AND user_id = 1` -> returned `NULL`.
  Application logged security alert, set flash error `"Task not found or you do not have permission to access it."`, and redirected to `tasks/index.php`.
- **Status:** **PASSED**

---

### TEST 14: Attempt to modify another user's task through a POST request
- **Input Data:**
  - Authenticated User: User A (`user_id = 1`)
  - Target Task: `#7` (belongs to User B, `user_id = 2`)
  - POST Payload: `id=7&title=Tampered Title by User A`
- **Execution:** Form POST dispatched targeting Task 7 with User A's session cookies.
- **Expected Result:** Request rejected; database record remains unaltered.
- **Actual Result:** UPDATE query:
  `UPDATE tasks SET title = ... WHERE id = 7 AND user_id = 1`
  Matched `0` rows (`rowCount = 0`). Sarah's task in MySQL was queried and confirmed to remain untouched: `"Sarah's Private Innovation Roadmap"`.
- **Status:** **PASSED**

---

### TEST 15: Submit a form with an invalid CSRF token
- **Input Data:**
  - CSRF Token: `attacker_forged_csrf_token_9999999999`
- **Execution:** Dispatched state-changing POST request with a fake CSRF token.
- **Expected Result:** Backend detects token mismatch, rejects the request with HTTP `403 Forbidden`, and aborts execution.
- **Actual Result:** `verify_csrf_token()` compared tokens via `hash_equals()`, returned `false`. Execution halted immediately with HTTP `403` error screen: `"Invalid Security Token"`.
- **Status:** **PASSED**

---

### TEST 16: Check whether passwords are stored as hashes
- **Execution:** Inspected the `users` table via `SELECT id, email, password FROM users`.
- **Expected Result:** Passwords are encrypted with standard bcrypt algorithms (`$2y$` prefix, 60 characters). No plain-text passwords exist.
- **Actual Result:** All user rows in MySQL verified. Every password hash begins with `$2y$10$` or `$2y$12$`, has an exact length of 60 characters, and verified with `password_get_info()`. Zero plaintext passwords exist in the database.
- **Status:** **PASSED**

---

### TEST 17: Check whether SQL injection attempts are safely handled
- **Input Data:**
  - Injection Payload: `'; DROP TABLE tasks; -- ' OR '1'='1`
- **Execution:** Injected payload into search input and task title fields.
- **Expected Result:** PDO prepared statement treats payload as an inert literal string. Table remains intact; no SQL syntax error occurs.
- **Actual Result:** PDO prepared statement cleanly bound the payload. The `tasks` table remained intact (`SHOW TABLES LIKE 'tasks'` verified). Query executed without syntax error and returned zero rows.
- **Status:** **PASSED**

---

### TEST 18: Check whether HTML/script input is escaped when displayed
- **Input Data:**
  - XSS Payload: `<script>alert("XSS_ATTACK_VECTOR")</script><img src="x" onerror="stealCookie()">`
- **Execution:** Passed payload through the `e()` rendering helper function used across all templates.
- **Expected Result:** All special characters are converted to their corresponding HTML entities.
- **Actual Result:** Output generated:  
  `&lt;script&gt;alert(&quot;XSS_ATTACK_VECTOR&quot;)&lt;/script&gt;&lt;img src=&quot;x&quot; onerror=&quot;stealCookie()&quot;&gt;`  
  Browser renders text literally as inert glyphs; no script execution is possible.
- **Status:** **PASSED**

---

### TEST 19: Test invalid status submission
- **Input Data:**
  - Status: `'Archived'` (Non-allowed status value; only 'Pending', 'In Progress', 'Completed' allowed)
- **Execution:** Dispatched task update request with invalid status value.
- **Expected Result:** Validation rejects non-enum value with error message: `"Invalid status selected."` Zero rows altered in database.
- **Actual Result:** `validate_task_input()` rejected input with `valid = false` and `errors['status'] = 'Invalid status selected.'` Status in MySQL remained untouched.
- **Status:** **PASSED**

---

### TEST 20: Check error handling / credential leakage
- **Execution:** Triggered simulated database connectivity failures and invalid query exceptions.
- **Expected Result:** Technical errors and credentials are not displayed to the user; friendly error notice is shown; full error is logged privately.
- **Actual Result:** `try / catch` blocks in `config/database.php` caught the exception. Raw server DSN, passwords, and stack traces were suppressed. User was served a friendly styled error card, while technical details were logged via `error_log()`.
- **Status:** **PASSED**

---

## Security Hardening Verification

Following the formal RTIH technical audit, the following security hardening actions were completed and verified:
1. **Critical Vulnerability Removal:** The development helper `auth/test_login.php` was permanently deleted. Codebase search confirms zero backdoor or passwordless login endpoints exist.
2. **Open Redirect Mitigation:** In `tasks/update_status.php`, the non-AJAX fallback was hardened with host-normalized referer validation strictly whitelisting internal hostnames (`localhost`, `127.0.0.1`, current server host) and stripping CRLF injection vectors. External referers safely default to `tasks/index.php`.
3. **Session Cleanliness on Logout:** `includes/auth.php` was updated so `logout_user()` completely clears memory variables, invalidates cookies, and destroys server session storage without re-instantiating a dangling session.
4. **Brand Assets:** Professional SVG brand assets (`assets/images/logo.svg` and `assets/images/favicon.svg`) were integrated.

---

## Conclusion

The TaskFlow application has successfully passed **20 out of 20 tests (100% pass rate)**. The application demonstrates exceptional stability, defensive input validation, comprehensive session integrity, robust security architecture, and uncompromising tenant isolation.

