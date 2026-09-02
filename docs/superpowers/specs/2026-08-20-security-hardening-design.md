# Security Hardening & Bug Fixes - Design Spec

**Goal:** Fix all critical and major security vulnerabilities identified in the bug report while maintaining existing functionality.

**Architecture:** Incremental security improvements applied file-by-file, prioritizing critical vulnerabilities first. Each fix is self-contained and testable.

**Tech Stack:** PHP 8.x, MySQL, PDO, HTML/CSS/JS

---

## Critical Fixes (Priority 1)

### 1. SQL Injection in Products AJAX Endpoint
- **File:** `products/products.php:211`
- **Fix:** Cast `$_GET['id']` to `(int)` before query execution

### 2. Reflected XSS via urldecode()
- **Files:** `admin/users.php`, `admin/inventory.php`, `users/users.php`, `admin/roles.php`, `admin/product_ingredients.php`
- **Fix:** Remove `urldecode()`, ensure `htmlspecialchars()` on output

### 3. Hardcoded Database Credentials
- **File:** `config.php:48-52`
- **Fix:** Use `getenv()` with fallbacks for DB_HOST, DB_USER, DB_PASS

### 4. MD5 Password Fallback
- **File:** `includes/auth.php:275-287`
- **Fix:** Remove MD5 check, require password_hash() for all users

### 5. IDOR on User Data Endpoint
- **Files:** `users/users.php`, `users/manager_users.php`, `inventory/suppliers.php`
- **Fix:** Add authorization check - users can only view own data unless admin

### 6. Rate Limiting on Admin Endpoints
- **Files:** `admin/users.php`, `admin/branches.php`
- **Fix:** Implement session-based rate limiting (max 10 actions per minute)

### 7. Missing CSRF on GET AJAX Endpoints
- **Files:** `products/products.php`, `users/users.php`, `inventory/suppliers.php`
- **Fix:** Add `requireAuth()` check and session validation

## Major Fixes (Priority 2)

### 8. CSP Allows unsafe-inline and unsafe-eval
- **File:** `config.php:68`
- **Fix:** Remove `unsafe-eval`, keep `unsafe-inline` temporarily with TODO

### 9. Error Messages Expose Internal Details
- **File:** `cashier/receipt.php:65`
- **Fix:** Log error, show generic message to user

### 10. Missing Input Validation on Product Price
- **File:** `products/products.php:62`
- **Fix:** Validate price is numeric and positive

### 11. Session Fixation Risk
- **File:** `includes/auth.php:303-306`
- **Fix:** Move `session_regenerate_id()` before setting session data

---

## Verification Plan

Each fix will be verified by:
1. Manual testing with crafted attack vectors
2. Code review of changed files
3. Regression testing of existing functionality
