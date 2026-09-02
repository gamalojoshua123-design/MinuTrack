# Security Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all critical and major security vulnerabilities identified in the security audit.

**Architecture:** File-by-file security fixes, each self-contained and independently testable.

**Tech Stack:** PHP 8.x, MySQL, PDO

**Spec:** `docs/superpowers/specs/2026-08-20-security-hardening-design.md`

---

## Global Constraints
- PHP 8.x minimum
- PDO for all database operations
- Maintain backward compatibility with existing sessions
- All fixes must not break existing functionality

---

## Task 1: Fix SQL Injection in Products AJAX Endpoint

**Files:**
- Modify: `products/products.php:211`

**Interfaces:**
- Produces: Safe product data retrieval

- [ ] **Step 1: Fix the SQL injection vulnerability**

```php
// products/products.php line 211
// Before:
$stmt->execute([$_GET['id']]);

// After:
$stmt->execute([(int)$_GET['id']]);
```

- [ ] **Step 2: Verify fix**

Test URL: `?action=get_product&id=1 OR 1=1`
Expected: Returns only product with id=1, not all products

- [ ] **Step 3: Commit**

```bash
git add products/products.php
git commit -m "fix: cast GET id to int to prevent SQL injection"
```

---

## Task 2: Fix Reflected XSS via urldecode()

**Files:**
- Modify: `admin/users.php:6-7`
- Modify: `admin/inventory.php:6-7`
- Modify: `users/users.php:7-8`
- Modify: `admin/roles.php:9-10`
- Modify: `admin/product_ingredients.php:6-7`

**Interfaces:**
- Produces: Safe message display without XSS

- [ ] **Step 1: Fix admin/users.php**

```php
// Line 6-7, change:
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$message_type = isset($_GET['type']) && in_array($_GET['type'], ['success', 'error'], true) ? $_GET['type'] : '';

// To:
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) && in_array($_GET['type'], ['success', 'error'], true) ? $_GET['type'] : '';
```

- [ ] **Step 2: Fix admin/inventory.php**

```php
// Line 6-7, same change as above
```

- [ ] **Step 3: Fix users/users.php**

```php
// Line 7-8, same change as above
```

- [ ] **Step 4: Fix admin/roles.php**

```php
// Line 9-10, same change as above
```

- [ ] **Step 5: Fix admin/product_ingredients.php**

```php
// Line 6-7, same change as above
```

- [ ] **Step 6: Verify all output is escaped**

Search for all `echo $message` and ensure `htmlspecialchars()` is applied.

- [ ] **Step 7: Commit**

```bash
git add admin/users.php admin/inventory.php users/users.php admin/roles.php admin/product_ingredients.php
git commit -m "fix: remove urldecode() to prevent reflected XSS"
```

---

## Task 3: Move Database Credentials to Environment Variables

**Files:**
- Modify: `config.php:48-52`
- Modify: `.env.example` (add DB vars)

**Interfaces:**
- Produces: Database connection using env vars with fallbacks

- [ ] **Step 1: Update config.php**

```php
// Lines 48-52, change:
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// To:
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pos_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

- [ ] **Step 2: Update .env.example**

Add these lines:
```
# Database Configuration
DB_HOST=localhost
DB_NAME=pos_system
DB_USER=root
DB_PASS=
```

- [ ] **Step 3: Verify connection still works**

Load any page that uses database - should connect successfully.

- [ ] **Step 4: Commit**

```bash
git add config.php .env.example
git commit -m "fix: move DB credentials to environment variables"
```

---

## Task 4: Remove MD5 Password Fallback

**Files:**
- Modify: `includes/auth.php:275-287`

**Interfaces:**
- Produces: Authentication only via password_verify()

- [ ] **Step 1: Remove MD5 fallback block**

```php
// Remove lines 280-287:
} elseif (strlen($storedPassword) === 32 && $storedPassword === md5($password)) {
    // Legacy MD5 password — force rehash to bcrypt on next login
    $passwordValid = true;
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$newHash, $user['id']]);
    $user['password'] = $newHash;
}

// Keep only the password_verify() check:
if (!empty($storedPassword) && password_verify($password, $storedPassword)) {
    $passwordValid = true;
}
```

- [ ] **Step 2: Verify authentication still works**

Login with a bcrypt-hashed password - should succeed.

- [ ] **Step 3: Commit**

```bash
git add includes/auth.php
git commit -m "fix: remove MD5 password fallback for security"
```

---

## Task 5: Fix IDOR on User Data Endpoint

**Files:**
- Modify: `users/users.php:69-108`
- Modify: `users/manager_users.php:18-40`

**Interfaces:**
- Produces: User data only accessible by authorized users

- [ ] **Step 1: Add authorization check in users/users.php**

```php
// After line 84 ($user = $stmt->fetch()), add:
if (!$user) {
    throw new Exception('User not found');
}

// Add authorization check:
if (!isOwner() && !isManager()) {
    if ((int)$user['id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('Unauthorized');
    }
}
```

- [ ] **Step 2: Add same check in users/manager_users.php**

Same logic as Step 1.

- [ ] **Step 3: Verify fix**

Login as cashier, attempt `?action=get_user&id=1` (admin ID) - should get unauthorized error.

- [ ] **Step 4: Commit**

```bash
git add users/users.php users/manager_users.php
git commit -m "fix: add authorization check to prevent IDOR on user data"
```

---

## Task 6: Add Rate Limiting on Admin Endpoints

**Files:**
- Modify: `admin/users.php:16-57`
- Modify: `admin/branches.php:9-80`

**Interfaces:**
- Produces: Rate-limited admin actions (max 10 per minute)

- [ ] **Step 1: Create rate limiting helper in includes/functions.php**

```php
function checkRateLimit($key, $maxAttempts = 10, $windowSeconds = 60) {
    $rate_key = 'rate_' . $key;
    $attempts = $_SESSION[$rate_key] ?? ['count' => 0, 'first_at' => time()];
    
    if (time() - $attempts['first_at'] > $windowSeconds) {
        $attempts = ['count' => 0, 'first_at' => time()];
    }
    
    if ($attempts['count'] >= $maxAttempts) {
        return false;
    }
    
    $attempts['count']++;
    $_SESSION[$rate_key] = $attempts;
    return true;
}
```

- [ ] **Step 2: Add rate limit check in admin/users.php POST handler**

```php
// At top of POST handler, after requireCsrfToken():
if (!checkRateLimit('admin_users')) {
    header('Location: users.php?message=' . urlencode('Too many requests. Please wait.') . '&type=error');
    exit;
}
```

- [ ] **Step 3: Add same check in admin/branches.php POST handler**

Same logic as Step 2 with key 'admin_branches'.

- [ ] **Step 4: Verify rate limiting works**

Perform 11 rapid POST requests - 11th should be blocked.

- [ ] **Step 5: Commit**

```bash
git add includes/functions.php admin/users.php admin/branches.php
git commit -m "feat: add rate limiting on admin endpoints"
```

---

## Task 7: Add Authorization Check on GET AJAX Endpoints

**Files:**
- Modify: `products/products.php:208-222`
- Modify: `inventory/suppliers.php:11-29`

**Interfaces:**
- Produces: AJAX endpoints require authentication

- [ ] **Step 1: Add requireAuth() to products AJAX**

```php
// At line 208, before the if block:
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_product') {
    requireAuth(); // Add this line
    // ... rest of code
```

- [ ] **Step 2: Add requireAuth() to suppliers AJAX**

Same as Step 1.

- [ ] **Step 3: Verify fix**

Access AJAX endpoint without being logged in - should redirect to login.

- [ ] **Step 4: Commit**

```bash
git add products/products.php inventory/suppliers.php
git commit -m "fix: add auth check to GET AJAX endpoints"
```

---

## Task 8: Fix CSP Header

**Files:**
- Modify: `config.php:68`

**Interfaces:**
- Produces: Stricter Content Security Policy

- [ ] **Step 1: Update CSP header**

```php
// Line 68, change:
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://unpkg.com; img-src 'self' data:; connect-src 'self'");

// To:
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://unpkg.com; img-src 'self' data:; connect-src 'self'");
// Note: 'unsafe-inline' kept temporarily for inline scripts; TODO: move to nonces
```

- [ ] **Step 2: Verify pages still load**

Navigate through the application - JavaScript should still work.

- [ ] **Step 3: Commit**

```bash
git add config.php
git commit -m "fix: remove unsafe-eval from CSP header"
```

---

## Task 9: Fix Error Message Information Disclosure

**Files:**
- Modify: `cashier/receipt.php:65`

**Interfaces:**
- Produces: Generic error messages for users

- [ ] **Step 1: Fix error handling**

```php
// Line 65, change:
} catch (PDOException $e) {
    die("Error fetching transaction: " . $e->getMessage());
}

// To:
} catch (PDOException $e) {
    error_log('Receipt fetch error: ' . $e->getMessage());
    die('An error occurred. Please try again later.');
}
```

- [ ] **Step 2: Verify fix**

Trigger a database error - should show generic message, not detailed error.

- [ ] **Step 3: Commit**

```bash
git add cashier/receipt.php
git commit -m "fix: hide internal error details from users"
```

---

## Task 10: Add Input Validation on Product Price

**Files:**
- Modify: `products/products.php:58-66`

**Interfaces:**
- Produces: Validated product data before database insert

- [ ] **Step 1: Add price validation**

```php
// Before the INSERT statement, add:
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
if ($price === false || $price < 0) {
    throw new Exception('Price must be a valid positive number');
}

$stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
if ($stock === false || $stock < 0) {
    throw new Exception('Stock must be a valid non-negative number');
}
```

- [ ] **Step 2: Update INSERT to use validated values**

```php
$stmt->execute([
    $_POST['name'],
    $price,  // Use validated price
    $stock,  // Use validated stock
    $_POST['category'],
    $_POST['status'],
    $image_filename
]);
```

- [ ] **Step 3: Verify fix**

Submit product with negative price - should show validation error.

- [ ] **Step 4: Commit**

```bash
git add products/products.php
git commit -m "fix: add input validation for product price and stock"
```

---

## Task 11: Fix Session Fixation Risk

**Files:**
- Modify: `includes/auth.php:303-310`

**Interfaces:**
- Produces: Secure session regeneration on login

- [ ] **Step 1: Move session_regenerate_id before session data**

```php
// Current order (lines 303-310):
$_SESSION['user_id'] = $user['id'];
$_SESSION['login_user_id'] = $user['user_id'];
// ... more session data ...
session_regenerate_id(true);

// Change to:
session_regenerate_id(true);  // Move this FIRST
$_SESSION['user_id'] = $user['id'];
$_SESSION['login_user_id'] = $user['user_id'];
// ... rest of session data ...
```

- [ ] **Step 2: Verify fix**

Login and check session ID changes.

- [ ] **Step 3: Commit**

```bash
git add includes/auth.php
git commit -m "fix: regenerate session ID before setting session data"
```

---

## Completion

After all tasks:
1. Run full regression test of login, POS, inventory, and admin functions
2. Verify no PHP errors in error log
3. Document any remaining TODOs
