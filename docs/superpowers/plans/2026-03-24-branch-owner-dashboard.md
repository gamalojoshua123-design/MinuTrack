# Branch Owner Dashboard & Multi-Branch Management — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add multi-branch support with a new Branch Owner role, dedicated dashboard with sales/charts/delivery/inventory/staff views, and admin branch management.

**Architecture:** Branch-scoped database — add `branch_id` FK to existing tables, new `branches` + `branch_users` tables. New `branch_owner/` directory for the dedicated panel. AJAX-powered dashboard with Chart.js. Admin panel extended with branch CRUD and owner assignment.

**Tech Stack:** PHP 8.2, MySQL/PDO, Vanilla JS, Chart.js, Boxicons 2.1.4, Custom CSS

**Spec:** `docs/superpowers/specs/2026-03-24-branch-owner-dashboard-design.md`

---

## File Structure

### New Files

```
branch_owner/
├── bootstrap.php              # Auth check, branch scoping, CSRF helpers
├── dashboard.php              # Main dashboard with stats + charts
├── sales.php                  # Detailed sales table with filters + pagination
├── products.php               # Products sold breakdown
├── deliveries.php             # Delivery tracking and history
├── inventory.php              # Read-only inventory view
├── staff.php                  # Cashier/staff performance
├── includes/
│   ├── header.php             # Top bar with branch dropdown
│   └── sidebar.php            # Navigation sidebar
├── api/
│   ├── dashboard_stats.php    # AJAX: stat card data
│   ├── sales_data.php         # AJAX: sales chart data
│   ├── products_data.php      # AJAX: products chart data
│   ├── delivery_data.php      # AJAX: delivery data
│   ├── inventory_data.php     # AJAX: inventory data
│   ├── staff_data.php         # AJAX: staff performance
│   └── compare_data.php       # AJAX: branch comparison
└── assets/
    ├── css/
    │   └── branch-owner.css   # Dashboard styles + responsive
    └── js/
        └── branch-owner.js    # Charts, AJAX, interactions

admin/branches.php             # Admin branch management page
```

### Modified Files

```
pos_system.sql                          # Schema migration
includes/auth.php:11-104                # Add isBranchOwner(), update getDefaultPermissions()
includes/constants.php:19               # Add branch_owner to ROLES
login.php:20-23                         # Add branch_owner routing
admin/includes/sidebar.php:1-41         # Add "Branches" link
admin/users.php:13,34,206               # Fix MD5→bcrypt, add branch_owner role + branch assignment
receipt.php:398-399                     # Dynamic branch name from DB
```

---

## Task 1: Database Migration — Create Branch Tables & Add branch_id

**Files:**
- Modify: `pos_system.sql` (reference only — we execute SQL directly)
- Create: `migrations/001_add_branches.sql`

- [ ] **Step 1: Create migrations directory**

```bash
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/minute1/migrations
```

- [ ] **Step 2: Write the migration SQL file**

Create `migrations/001_add_branches.sql`:

```sql
-- ==============================================
-- Migration 001: Add Multi-Branch Support
-- ==============================================

-- 1. Create branches table
CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) DEFAULT '',
  `phone` VARCHAR(20) DEFAULT '',
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insert default branch
INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `status`)
VALUES (1, 'Jasaan Branch', 'Jasaan, Misamis Oriental', '', 'active');

-- 3. Create branch_users junction table
CREATE TABLE IF NOT EXISTS `branch_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `branch_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_branch_user` (`branch_id`, `user_id`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Alter users.role ENUM to include manager and branch_owner
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','cashier','manager','branch_owner') DEFAULT 'cashier';

-- 5. Add branch_id to all relevant tables (DEFAULT 1 = Jasaan Branch)
ALTER TABLE `orders` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `products` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `product_ingredients` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `product_inventory_usage` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_deliveries` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_batches` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_movements` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_history` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_alerts` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_categories` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_orders` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_order_items` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `inventory_log` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `cashier_shifts` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `sales_history` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `x_reading_log` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `z_reading_log` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `cash_drop_log` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;
ALTER TABLE `restock_requests` ADD COLUMN `branch_id` INT NOT NULL DEFAULT 1;

-- 6. Add delivery tracking columns to inventory_deliveries
ALTER TABLE `inventory_deliveries` ADD COLUMN `order_date` DATE NULL AFTER `expected_date`;
ALTER TABLE `inventory_deliveries` ADD COLUMN `received_date` DATE NULL AFTER `order_date`;

-- 7. Add foreign key constraints for branch_id
ALTER TABLE `orders` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `products` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `product_ingredients` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `product_inventory_usage` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_deliveries` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_batches` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_movements` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_history` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_alerts` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_categories` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_orders` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_order_items` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `inventory_log` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `cashier_shifts` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `sales_history` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `x_reading_log` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `z_reading_log` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `cash_drop_log` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
ALTER TABLE `restock_requests` ADD FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`);
```

- [ ] **Step 3: Run the migration via phpMyAdmin or MySQL CLI**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/mysql -u root pos_system < /Applications/XAMPP/xamppfiles/htdocs/minute1/migrations/001_add_branches.sql
```

Expected: No errors. All tables updated.

- [ ] **Step 4: Verify migration**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/mysql -u root pos_system -e "DESCRIBE branches; DESCRIBE branch_users; SHOW COLUMNS FROM orders LIKE 'branch_id'; SHOW COLUMNS FROM users LIKE 'role';"
```

Expected: `branches` table exists, `branch_users` table exists, `orders.branch_id` column exists, `users.role` enum includes 'branch_owner'.

- [ ] **Step 5: Commit**

```bash
git add migrations/001_add_branches.sql
git commit -m "feat: add multi-branch database migration"
```

---

## Task 2: Update Auth System — Branch Owner Role & Permissions

**Files:**
- Modify: `includes/auth.php:11-104`
- Modify: `includes/constants.php:19`

- [ ] **Step 1: Update constants.php — add branch_owner and manager to ROLES**

In `includes/constants.php` at line 19, change:
```php
define('ROLES', ['admin' => 'Administrator', 'cashier' => 'Cashier']);
```
To:
```php
define('ROLES', [
    'admin' => 'Administrator',
    'manager' => 'Manager',
    'cashier' => 'Cashier',
    'branch_owner' => 'Branch Owner'
]);
```

- [ ] **Step 2: Add isBranchOwner() to auth.php**

In `includes/auth.php`, after `isCashier()` function (after line 24), add:

```php
function isBranchOwner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'branch_owner';
}
```

- [ ] **Step 3: Add branch_owner permissions to getDefaultPermissions()**

In `includes/auth.php`, inside the `getDefaultPermissions()` function (around line 62-104), add a new case after the 'manager' case:

```php
case 'branch_owner':
    return [
        'branch_dashboard_view' => true,
        'branch_sales_view' => true,
        'branch_products_view' => true,
        'branch_delivery_view' => true,
        'branch_inventory_view' => true,
        'branch_staff_view' => true,
        'dashboard_view' => false,
        'products_view' => false,
        'products_manage' => false,
        'inventory_view' => false,
        'inventory_manage' => false,
        'pos_access' => false,
        'transactions_view' => false,
        'reports_view' => false,
        'users_manage' => false,
        'archive_view' => false,
    ];
```

- [ ] **Step 4: Add getBranchIds() helper function to auth.php**

Append to `includes/auth.php`:

```php
function getBranchIds($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT branch_id FROM branch_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getBranchFilter($branch_ids) {
    if (empty($branch_ids)) return "1=0";
    $placeholders = implode(',', array_fill(0, count($branch_ids), '?'));
    return "branch_id IN ($placeholders)";
}
```

- [ ] **Step 5: Verify by checking the file is syntactically correct**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/includes/auth.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/includes/constants.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add includes/auth.php includes/constants.php
git commit -m "feat: add branch_owner role, permissions, and branch helper functions"
```

---

## Task 3: Update Login Routing

**Files:**
- Modify: `login.php:20-23`

- [ ] **Step 1: Update login.php already-logged-in redirect (lines 5-8)**

In `login.php`, the top-of-file check for already-logged-in users (around lines 5-8) currently does `isAdmin() ? 'admin.php' : 'pos.php'`. This must also handle branch_owner. Replace with:

```php
if (isset($_SESSION['user_id'])) {
    require_once 'includes/auth.php';
    if (isBranchOwner()) {
        header('Location: branch_owner/dashboard.php');
    } elseif (isAdmin() || isManager()) {
        header('Location: admin.php');
    } else {
        header('Location: pos.php');
    }
    exit;
}
```

- [ ] **Step 2: Update login.php post-authentication redirect (lines 20-23)**

In `login.php`, find the redirect logic at lines 20-23. Replace the simple ternary:
```php
$redirect = ($user['role'] === 'admin') ? 'admin.php' : 'pos.php';
```

With full role routing:
```php
// Store branch IDs in session for branch owners
if ($user['role'] === 'branch_owner') {
    require_once 'includes/auth.php';
    $_SESSION['branch_ids'] = getBranchIds($pdo, $user['id']);
}

// Role-based redirect
switch ($user['role']) {
    case 'admin':
    case 'manager':
        $redirect = 'admin.php';
        break;
    case 'branch_owner':
        $redirect = 'branch_owner/dashboard.php';
        break;
    case 'cashier':
    default:
        $redirect = 'pos.php';
        break;
}
```

- [ ] **Step 3: Verify syntax**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/login.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add login.php
git commit -m "feat: add branch_owner login routing with branch ID session loading"
```

---

## Task 4: Branch Owner Bootstrap & Includes

**Files:**
- Create: `branch_owner/bootstrap.php`
- Create: `branch_owner/includes/header.php`
- Create: `branch_owner/includes/sidebar.php`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/includes
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/api
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/assets/css
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/assets/js
```

- [ ] **Step 2: Create bootstrap.php**

Create `branch_owner/bootstrap.php`:

```php
<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/constants.php';

// Verify branch owner role
if (!isset($_SESSION['user_id']) || !isBranchOwner()) {
    header('Location: ../login.php');
    exit;
}

// Load branch IDs if not in session
if (!isset($_SESSION['branch_ids']) || empty($_SESSION['branch_ids'])) {
    $_SESSION['branch_ids'] = getBranchIds($pdo, $_SESSION['user_id']);
}

// If no branches assigned, show error
if (empty($_SESSION['branch_ids'])) {
    $no_branches = true;
}

$branch_ids = $_SESSION['branch_ids'] ?? [];

// Get branch details for navigation
$branches = [];
if (!empty($branch_ids)) {
    $placeholders = implode(',', array_fill(0, count($branch_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, status FROM branches WHERE id IN ($placeholders) AND status = 'active' ORDER BY name");
    $stmt->execute($branch_ids);
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Current selected branch (from GET param or first assigned)
$current_branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : ($branches[0]['id'] ?? 0);

// Validate that current_branch_id is in the user's assigned branches
if (!in_array($current_branch_id, $branch_ids)) {
    $current_branch_id = $branches[0]['id'] ?? 0;
}

$current_branch_name = '';
foreach ($branches as $b) {
    if ($b['id'] == $current_branch_id) {
        $current_branch_name = $b['name'];
        break;
    }
}

// CSRF token helpers
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// JSON response helper for API endpoints
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['status' => 'error', 'message' => $message], $status);
}

function jsonSuccess($data) {
    jsonResponse(['status' => 'success', 'data' => $data]);
}
```

- [ ] **Step 3: Create includes/header.php**

Create `branch_owner/includes/header.php`:

```php
<?php $current_page = basename($_SERVER['PHP_SELF'], '.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_branch_name) ?> — Branch Owner Dashboard</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= dirname($_SERVER['PHP_SELF']) ?>/assets/css/branch-owner.css">
</head>
<body>
<div class="bo-layout">
    <!-- Mobile hamburger -->
    <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
        <i class="bx bx-menu"></i>
    </button>

    <!-- Top Bar -->
    <header class="bo-topbar">
        <div class="bo-topbar-left">
            <img src="../assets/img/logo/minute_burger_logo.png" alt="Logo" class="bo-logo" onerror="this.style.display='none'">
            <span class="bo-brand">Minute Burger</span>
            <span class="bo-panel-label">Branch Owner Panel</span>
        </div>
        <div class="bo-topbar-right">
            <?php if (count($branches) > 1): ?>
            <select class="bo-branch-select" id="branchSelect" onchange="switchBranch(this.value)">
                <option value="all">All My Branches</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $b['id'] == $current_branch_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <span class="bo-branch-label"><?= htmlspecialchars($current_branch_name) ?></span>
            <?php endif; ?>
            <span class="bo-user">
                <i class="bx bx-user-circle"></i>
                <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_id'] ?? 'Owner') ?>
            </span>
        </div>
    </header>
```

- [ ] **Step 4: Create includes/sidebar.php**

Create `branch_owner/includes/sidebar.php`:

```php
    <!-- Sidebar -->
    <nav class="bo-sidebar" id="sidebar">
        <div class="bo-sidebar-header">
            <h3>Branch Owner</h3>
            <button class="bo-sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <ul class="bo-nav">
            <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <a href="dashboard.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bxs-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?= $current_page === 'sales' ? 'active' : '' ?>">
                <a href="sales.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bx-line-chart"></i>
                    <span>Sales</span>
                </a>
            </li>
            <li class="<?= $current_page === 'products' ? 'active' : '' ?>">
                <a href="products.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bx-package"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="<?= $current_page === 'deliveries' ? 'active' : '' ?>">
                <a href="deliveries.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bx-truck"></i>
                    <span>Deliveries</span>
                </a>
            </li>
            <li class="<?= $current_page === 'inventory' ? 'active' : '' ?>">
                <a href="inventory.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bx-box"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li class="<?= $current_page === 'staff' ? 'active' : '' ?>">
                <a href="staff.php<?= $current_branch_id ? '?branch_id='.$current_branch_id : '' ?>">
                    <i class="bx bx-group"></i>
                    <span>Staff</span>
                </a>
            </li>
        </ul>
        <div class="bo-sidebar-footer">
            <a href="../logout.php" class="bo-logout">
                <i class="bx bx-log-out"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar Overlay for mobile -->
    <div class="bo-sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="bo-main">
```

- [ ] **Step 5: Verify all files**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/bootstrap.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add branch_owner/bootstrap.php branch_owner/includes/
git commit -m "feat: add branch owner bootstrap, header, and sidebar includes"
```

---

## Task 5: Branch Owner CSS — Full Responsive Stylesheet

**Files:**
- Create: `branch_owner/assets/css/branch-owner.css`

- [ ] **Step 1: Create the full responsive stylesheet**

Create `branch_owner/assets/css/branch-owner.css`:

```css
/* ===========================================
   Branch Owner Dashboard — Styles
   Follows existing project design system
   =========================================== */

:root {
    --bo-primary: #F37902;
    --bo-secondary: #DC6902;
    --bo-accent: #FAE51D;
    --bo-dark: #2c2c2c;
    --bo-sidebar-width: 220px;
    --bo-topbar-height: 56px;
    --bo-card-radius: 10px;
    --bo-card-shadow: 0 2px 8px rgba(0,0,0,0.08);
    --bo-success: #2a9d2a;
    --bo-danger: #cc0000;
    --bo-warning: #e6a817;
    --bo-gray: #888;
    --bo-light-bg: #f5f6fa;
    --bo-white: #ffffff;
}

/* Reset */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bo-light-bg);
    color: #333;
    min-height: 100vh;
    overflow-x: hidden;
}

/* Layout */
.bo-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Top Bar */
.bo-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--bo-topbar-height);
    background: linear-gradient(135deg, var(--bo-primary), var(--bo-secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px 0 calc(var(--bo-sidebar-width) + 20px);
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}

.bo-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bo-logo { height: 32px; width: auto; }
.bo-brand { font-size: 16px; font-weight: 700; }
.bo-panel-label { font-size: 13px; opacity: 0.8; }

.bo-topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.bo-branch-select {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    background: rgba(255,255,255,0.2);
    color: white;
    cursor: pointer;
    outline: none;
}

.bo-branch-select option { color: #333; background: white; }
.bo-branch-label { font-size: 13px; opacity: 0.9; font-weight: 500; }
.bo-user { font-size: 13px; display: flex; align-items: center; gap: 6px; }

/* Hamburger (mobile) */
.hamburger {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 1100;
    background: var(--bo-primary);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 22px;
    cursor: pointer;
    line-height: 1;
}

/* Sidebar */
.bo-sidebar {
    position: fixed;
    top: var(--bo-topbar-height);
    left: 0;
    bottom: 0;
    width: var(--bo-sidebar-width);
    background: var(--bo-dark);
    color: white;
    display: flex;
    flex-direction: column;
    z-index: 900;
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.bo-sidebar-header {
    padding: 20px;
    font-size: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bo-sidebar-header h3 { font-size: 14px; font-weight: 600; }

.bo-sidebar-close {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
}

.bo-nav {
    list-style: none;
    flex: 1;
    padding: 10px 0;
}

.bo-nav li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.bo-nav li a:hover { color: white; background: rgba(255,255,255,0.05); }

.bo-nav li.active a {
    color: white;
    background: rgba(243,121,2,0.3);
    border-left: 3px solid var(--bo-primary);
}

.bo-nav li a i { font-size: 20px; }

.bo-sidebar-footer {
    padding: 10px 0;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.bo-logout {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.bo-logout:hover { color: #ff6b6b; }

.bo-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 800;
}

/* Main Content */
.bo-main {
    margin-top: var(--bo-topbar-height);
    margin-left: var(--bo-sidebar-width);
    padding: 24px;
    flex: 1;
    min-height: calc(100vh - var(--bo-topbar-height));
}

/* Branch Tabs */
.bo-branch-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.bo-branch-tab {
    padding: 10px 24px;
    border-radius: 8px 8px 0 0;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    background: #e0e0e0;
    color: #666;
}

.bo-branch-tab.active {
    background: var(--bo-primary);
    color: white;
}

.bo-branch-tab:hover:not(.active) { background: #d0d0d0; }

.bo-branch-tab.compare {
    background: transparent;
    border: 2px dashed #ccc;
    color: #888;
}

.bo-branch-tab.compare:hover { border-color: var(--bo-primary); color: var(--bo-primary); }

/* Stat Cards */
.bo-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.bo-stat-card {
    background: var(--bo-white);
    border-radius: var(--bo-card-radius);
    padding: 20px;
    box-shadow: var(--bo-card-shadow);
    transition: transform 0.2s;
}

.bo-stat-card:hover { transform: translateY(-2px); }

.bo-stat-label {
    font-size: 11px;
    color: var(--bo-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.bo-stat-value {
    font-size: 28px;
    font-weight: 700;
    margin: 6px 0;
}

.bo-stat-value.primary { color: var(--bo-primary); }
.bo-stat-value.secondary { color: var(--bo-secondary); }
.bo-stat-value.success { color: var(--bo-success); }
.bo-stat-value.danger { color: var(--bo-danger); }

.bo-stat-change {
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.bo-stat-change.up { color: var(--bo-success); }
.bo-stat-change.down { color: var(--bo-danger); }
.bo-stat-change.neutral { color: var(--bo-gray); }

/* Chart Cards */
.bo-charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

.bo-chart-card {
    background: var(--bo-white);
    border-radius: var(--bo-card-radius);
    padding: 20px;
    box-shadow: var(--bo-card-shadow);
}

.bo-chart-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bo-chart-container {
    position: relative;
    height: 250px;
}

/* Data Tables */
.bo-table-card {
    background: var(--bo-white);
    border-radius: var(--bo-card-radius);
    padding: 20px;
    box-shadow: var(--bo-card-shadow);
    margin-bottom: 24px;
    overflow-x: auto;
}

.bo-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.bo-table-title {
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bo-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.bo-table th {
    text-align: left;
    padding: 10px 12px;
    background: var(--bo-light-bg);
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--bo-gray);
    border-bottom: 2px solid #e0e0e0;
}

.bo-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.bo-table tr:hover td { background: rgba(243,121,2,0.03); }

/* Status Badges */
.bo-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.bo-badge.success { background: #e6f4ea; color: var(--bo-success); }
.bo-badge.warning { background: #fff8e1; color: var(--bo-warning); }
.bo-badge.danger { background: #fde8e8; color: var(--bo-danger); }
.bo-badge.info { background: #e3f2fd; color: #1976d2; }
.bo-badge.neutral { background: #f0f0f0; color: #666; }

/* Pagination */
.bo-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    margin-top: 16px;
}

.bo-page-btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.bo-page-btn:hover { border-color: var(--bo-primary); color: var(--bo-primary); }
.bo-page-btn.active { background: var(--bo-primary); color: white; border-color: var(--bo-primary); }
.bo-page-btn:disabled { opacity: 0.4; cursor: default; }

/* Filters */
.bo-filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.bo-filter-select, .bo-filter-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    background: white;
    outline: none;
    transition: border-color 0.2s;
}

.bo-filter-select:focus, .bo-filter-input:focus { border-color: var(--bo-primary); }

.bo-filter-btn {
    padding: 8px 16px;
    background: var(--bo-primary);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.bo-filter-btn:hover { background: var(--bo-secondary); }

/* Date filter bar */
.bo-date-filter {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.bo-date-btn {
    padding: 6px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.bo-date-btn.active { background: var(--bo-primary); color: white; border-color: var(--bo-primary); }
.bo-date-btn:hover:not(.active) { border-color: var(--bo-primary); }

/* Compare Mode */
.bo-compare {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.bo-compare-header {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.bo-compare-select {
    padding: 8px 14px;
    border: 2px solid var(--bo-primary);
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    background: white;
    color: var(--bo-primary);
    outline: none;
}

/* Empty State */
.bo-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--bo-gray);
}

.bo-empty i { font-size: 48px; margin-bottom: 12px; opacity: 0.3; }
.bo-empty p { font-size: 14px; }

/* Toast Notifications */
.bo-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-size: 13px;
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
    max-width: 360px;
}

.bo-toast.show { transform: translateY(0); opacity: 1; }
.bo-toast.error { background: var(--bo-danger); }
.bo-toast.success { background: var(--bo-success); }
.bo-toast.info { background: #1976d2; }

/* Page Title */
.bo-page-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #333;
}

.bo-page-subtitle {
    font-size: 13px;
    color: var(--bo-gray);
    margin-bottom: 20px;
}

/* Bottom Row Grid */
.bo-bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Print Styles */
@media print {
    .bo-sidebar, .bo-topbar, .hamburger, .bo-filters, .bo-pagination, .bo-date-filter, .bo-branch-tabs { display: none !important; }
    .bo-main { margin: 0; padding: 10px; }
    .bo-chart-card, .bo-stat-card, .bo-table-card { box-shadow: none; break-inside: avoid; }
}

/* ========== RESPONSIVE ========== */

/* Tablet */
@media (max-width: 1024px) {
    .bo-topbar { padding-left: 20px; }
    .bo-sidebar { transform: translateX(-100%); }
    .bo-sidebar.open { transform: translateX(0); }
    .bo-sidebar-close { display: block; }
    .bo-sidebar-overlay.show { display: block; }
    .hamburger { display: block; }
    .bo-main { margin-left: 0; }
    .bo-charts-row { grid-template-columns: 1fr; }
    .bo-bottom-row { grid-template-columns: 1fr; }
    .bo-compare { grid-template-columns: 1fr; }
}

/* Mobile */
@media (max-width: 768px) {
    .bo-topbar {
        padding: 0 12px 0 50px;
        height: 50px;
    }
    .bo-panel-label { display: none; }
    .bo-main { padding: 16px; margin-top: 50px; }
    .bo-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .bo-stat-value { font-size: 22px; }
    .bo-stat-card { padding: 14px; }
    .bo-branch-tabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    .bo-branch-tab { white-space: nowrap; flex-shrink: 0; }
    .bo-page-title { font-size: 18px; }
}

/* Small Mobile */
@media (max-width: 480px) {
    .bo-topbar-left { gap: 8px; }
    .bo-brand { font-size: 14px; }
    .bo-main { padding: 12px; }
    .bo-stats { grid-template-columns: 1fr 1fr; gap: 8px; }
    .bo-stat-card { padding: 12px; }
    .bo-stat-value { font-size: 20px; }
    .bo-stat-label { font-size: 10px; }
    .bo-table { font-size: 12px; }
    .bo-table th, .bo-table td { padding: 8px; }
    .bo-filters { flex-direction: column; }
    .bo-filter-select, .bo-filter-input { width: 100%; }
}
```

- [ ] **Step 2: Commit**

```bash
git add branch_owner/assets/css/branch-owner.css
git commit -m "feat: add fully responsive branch owner stylesheet"
```

---

## Task 6: Branch Owner JavaScript — Charts, AJAX, Interactions

**Files:**
- Create: `branch_owner/assets/js/branch-owner.js`

- [ ] **Step 1: Create the main JavaScript file**

Create `branch_owner/assets/js/branch-owner.js`:

```javascript
// ===========================================
// Branch Owner Dashboard — JavaScript
// ===========================================

// --- Sidebar Toggle (Mobile) ---
document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }

    if (hamburger) hamburger.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Init date filter buttons
    document.querySelectorAll('.bo-date-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.bo-date-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const range = this.dataset.range;
            if (typeof loadDashboardData === 'function') loadDashboardData(range);
        });
    });
});

// --- Branch Switching ---
function switchBranch(branchId) {
    const url = new URL(window.location.href);
    url.searchParams.set('branch_id', branchId);
    window.location.href = url.toString();
}

// --- AJAX Helper ---
async function fetchAPI(endpoint, params = {}) {
    const url = new URL(endpoint, window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/'));
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

    try {
        const res = await fetch(url);
        if (res.status === 401) {
            showToast('Session expired. Redirecting to login...', 'error');
            setTimeout(() => window.location.href = '../login.php', 1500);
            return null;
        }
        if (res.status === 403) {
            showToast('Access denied for this branch.', 'error');
            return null;
        }
        const data = await res.json();
        if (data.status === 'error') {
            showToast(data.message || 'An error occurred.', 'error');
            return null;
        }
        return data.data;
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
        console.error('API Error:', err);
        return null;
    }
}

// --- Toast Notifications ---
function showToast(message, type = 'info') {
    const existing = document.querySelector('.bo-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `bo-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// --- Chart Helpers ---
const chartColors = {
    primary: '#F37902',
    secondary: '#DC6902',
    accent: '#FAE51D',
    success: '#2a9d2a',
    danger: '#cc0000',
    gray: '#888',
    palette: ['#F37902', '#DC6902', '#FAE51D', '#2a9d2a', '#1976d2', '#9c27b0', '#ff5722', '#607d8b', '#e91e63', '#00bcd4']
};

const defaultChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: window.innerWidth < 768 ? 'bottom' : 'right',
            labels: { font: { size: 12 }, padding: 12 }
        },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 10,
            cornerRadius: 6,
            titleFont: { size: 13 },
            bodyFont: { size: 12 }
        }
    }
};

function formatPeso(amount) {
    return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function destroyChart(chartRef) {
    if (chartRef && typeof chartRef.destroy === 'function') chartRef.destroy();
    return null;
}

// --- Dashboard Charts ---
let salesTrendChart = null;
let topProductsChart = null;
let monthlySalesChart = null;
let categoryChart = null;
let deliveryChart = null;

function renderSalesTrend(ctx, labels, data) {
    salesTrendChart = destroyChart(salesTrendChart);
    salesTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: data,
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(243,121,2,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: chartColors.primary,
                borderWidth: 2
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: { display: false },
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => formatPeso(ctx.raw) }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => formatPeso(v), font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
}

function renderTopProducts(ctx, labels, data) {
    topProductsChart = destroyChart(topProductsChart);
    topProductsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: chartColors.palette.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => `${ctx.label}: ${formatPeso(ctx.raw)}` }
                }
            }
        }
    });
}

function renderMonthlySales(ctx, labels, data) {
    monthlySalesChart = destroyChart(monthlySalesChart);
    monthlySalesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monthly Sales',
                data: data,
                backgroundColor: chartColors.primary + 'cc',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: { display: false },
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => formatPeso(ctx.raw) }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => formatPeso(v), font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
}

function renderCategoryChart(ctx, labels, data) {
    categoryChart = destroyChart(categoryChart);
    categoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: data,
                backgroundColor: chartColors.palette.slice(0, labels.length),
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            ...defaultChartOptions,
            indexAxis: 'y',
            plugins: {
                ...defaultChartOptions.plugins,
                legend: { display: false },
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => formatPeso(ctx.raw) }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { callback: v => formatPeso(v), font: { size: 11 } }
                },
                y: { ticks: { font: { size: 12 } } }
            }
        }
    });
}

function renderDeliveryChart(ctx, labels, data) {
    deliveryChart = destroyChart(deliveryChart);
    deliveryChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Avg Days',
                data: data,
                borderColor: chartColors.success,
                backgroundColor: 'rgba(42,157,42,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: chartColors.success,
                borderWidth: 2
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Days', font: { size: 12 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// --- Compare Mode Charts ---
function renderCompareChart(ctx, labels, data1, data2, label1, label2) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: label1,
                    data: data1,
                    backgroundColor: chartColors.primary + 'cc',
                    borderRadius: 4
                },
                {
                    label: label2,
                    data: data2,
                    backgroundColor: chartColors.secondary + 'cc',
                    borderRadius: 4
                }
            ]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: { label: (ctx) => `${ctx.dataset.label}: ${formatPeso(ctx.raw)}` }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => formatPeso(v) },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// --- Pagination Helper ---
function renderPagination(container, currentPage, totalPages, onPageChange) {
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const prevBtn = document.createElement('button');
    prevBtn.className = 'bo-page-btn';
    prevBtn.textContent = '‹ Prev';
    prevBtn.disabled = currentPage <= 1;
    prevBtn.addEventListener('click', () => onPageChange(currentPage - 1));
    container.appendChild(prevBtn);

    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

    for (let i = start; i <= end; i++) {
        const btn = document.createElement('button');
        btn.className = `bo-page-btn ${i === currentPage ? 'active' : ''}`;
        btn.textContent = i;
        btn.addEventListener('click', () => onPageChange(i));
        container.appendChild(btn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'bo-page-btn';
    nextBtn.textContent = 'Next ›';
    nextBtn.disabled = currentPage >= totalPages;
    nextBtn.addEventListener('click', () => onPageChange(currentPage + 1));
    container.appendChild(nextBtn);
}

// --- HTML Escape Helper ---
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
```

- [ ] **Step 2: Commit**

```bash
git add branch_owner/assets/js/branch-owner.js
git commit -m "feat: add branch owner JS with charts, AJAX, pagination, and mobile sidebar"
```

---

## Task 7: Branch Owner Dashboard Page

**Files:**
- Create: `branch_owner/dashboard.php`

- [ ] **Step 1: Create dashboard.php**

Create `branch_owner/dashboard.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<?php if (!empty($no_branches)): ?>
<div class="bo-empty">
    <i class="bx bx-building-house"></i>
    <p>No branches assigned to your account yet.<br>Please contact your administrator.</p>
</div>
<?php else: ?>

<!-- Branch Tabs (hidden if only 1 branch) -->
<?php if (count($branches) > 1): ?>
<div class="bo-branch-tabs">
    <?php foreach ($branches as $b): ?>
    <button class="bo-branch-tab <?= $b['id'] == $current_branch_id ? 'active' : '' ?>"
            onclick="switchBranch(<?= $b['id'] ?>)">
        <?= htmlspecialchars($b['name']) ?>
    </button>
    <?php endforeach; ?>
    <button class="bo-branch-tab compare" id="compareBtn" onclick="toggleCompareMode()">
        <i class="bx bx-git-compare"></i> Compare
    </button>
</div>
<?php endif; ?>

<!-- Date Filter -->
<div class="bo-date-filter">
    <button class="bo-date-btn active" data-range="today">Today</button>
    <button class="bo-date-btn" data-range="week">This Week</button>
    <button class="bo-date-btn" data-range="month">This Month</button>
    <button class="bo-date-btn" data-range="custom">
        <i class="bx bx-calendar"></i> Custom
    </button>
    <input type="date" id="dateFrom" class="bo-filter-input" style="display:none;">
    <input type="date" id="dateTo" class="bo-filter-input" style="display:none;">
</div>

<!-- Single Branch View -->
<div id="singleView">
    <!-- Stat Cards -->
    <div class="bo-stats">
        <div class="bo-stat-card">
            <div class="bo-stat-label">Today's Sales</div>
            <div class="bo-stat-value primary" id="statSales">₱0</div>
            <div class="bo-stat-change neutral" id="statSalesChange">Loading...</div>
        </div>
        <div class="bo-stat-card">
            <div class="bo-stat-label">Products Sold</div>
            <div class="bo-stat-value secondary" id="statProducts">0</div>
            <div class="bo-stat-change neutral" id="statProductsChange">Loading...</div>
        </div>
        <div class="bo-stat-card">
            <div class="bo-stat-label">Avg Delivery Time</div>
            <div class="bo-stat-value success" id="statDelivery">—</div>
            <div class="bo-stat-change neutral" id="statDeliveryInfo">Last 5 deliveries</div>
        </div>
        <div class="bo-stat-card">
            <div class="bo-stat-label">Low Stock Items</div>
            <div class="bo-stat-value danger" id="statLowStock">0</div>
            <div class="bo-stat-change neutral" id="statLowStockInfo">—</div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="bo-charts-row">
        <div class="bo-chart-card">
            <div class="bo-chart-title"><i class="bx bx-trending-up"></i> Sales Trend</div>
            <div class="bo-chart-container">
                <canvas id="salesTrendCanvas"></canvas>
            </div>
        </div>
        <div class="bo-chart-card">
            <div class="bo-chart-title"><i class="bx bx-pie-chart-alt-2"></i> Top Products</div>
            <div class="bo-chart-container">
                <canvas id="topProductsCanvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="bo-charts-row">
        <div class="bo-chart-card">
            <div class="bo-chart-title"><i class="bx bx-bar-chart-alt-2"></i> Monthly Sales</div>
            <div class="bo-chart-container">
                <canvas id="monthlySalesCanvas"></canvas>
            </div>
        </div>
        <div class="bo-chart-card">
            <div class="bo-chart-title"><i class="bx bx-category"></i> Products by Category</div>
            <div class="bo-chart-container">
                <canvas id="categoryCanvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="bo-bottom-row">
        <div class="bo-table-card">
            <div class="bo-table-title"><i class="bx bx-truck"></i> Recent Deliveries</div>
            <table class="bo-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody id="recentDeliveries">
                    <tr><td colspan="4" class="bo-empty"><p>Loading...</p></td></tr>
                </tbody>
            </table>
        </div>
        <div class="bo-table-card">
            <div class="bo-table-title"><i class="bx bx-group"></i> Staff on Duty</div>
            <table class="bo-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shift</th>
                        <th>Sales</th>
                    </tr>
                </thead>
                <tbody id="staffOnDuty">
                    <tr><td colspan="3" class="bo-empty"><p>Loading...</p></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Compare View (hidden by default) -->
<div id="compareView" style="display:none;">
    <?php if (count($branches) >= 2): ?>
    <div class="bo-compare-header">
        <label>Compare:</label>
        <select class="bo-compare-select" id="compareBranch1">
            <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span>vs</span>
        <select class="bo-compare-select" id="compareBranch2">
            <?php foreach ($branches as $i => $b): ?>
            <option value="<?= $b['id'] ?>" <?= $i === 1 ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="bo-filter-btn" onclick="loadCompareData()">Compare</button>
    </div>
    <div class="bo-compare">
        <div class="bo-chart-card">
            <div class="bo-chart-title">Sales Comparison</div>
            <div class="bo-chart-container"><canvas id="compareSalesCanvas"></canvas></div>
        </div>
        <div class="bo-chart-card">
            <div class="bo-chart-title">Products Sold Comparison</div>
            <div class="bo-chart-container"><canvas id="compareProductsCanvas"></canvas></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

</main>
</div>

<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;
const BRANCHES = <?= json_encode($branches) ?>;

// Toggle compare mode
function toggleCompareMode() {
    const single = document.getElementById('singleView');
    const compare = document.getElementById('compareView');
    const btn = document.getElementById('compareBtn');

    if (compare.style.display === 'none') {
        single.style.display = 'none';
        compare.style.display = 'block';
        btn.classList.add('active');
        loadCompareData();
    } else {
        single.style.display = 'block';
        compare.style.display = 'none';
        btn.classList.remove('active');
    }
}

// Custom date toggle
document.querySelectorAll('.bo-date-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const isCustom = this.dataset.range === 'custom';
        document.getElementById('dateFrom').style.display = isCustom ? 'inline-block' : 'none';
        document.getElementById('dateTo').style.display = isCustom ? 'inline-block' : 'none';
    });
});

// Load dashboard data
async function loadDashboardData(range = 'today') {
    const params = { branch_id: BRANCH_ID, range: range };
    if (range === 'custom') {
        params.from = document.getElementById('dateFrom').value;
        params.to = document.getElementById('dateTo').value;
    }

    const stats = await fetchAPI('api/dashboard_stats.php', params);
    if (stats) {
        document.getElementById('statSales').textContent = formatPeso(stats.today_sales);
        document.getElementById('statProducts').textContent = stats.products_sold;
        document.getElementById('statDelivery').textContent = stats.avg_delivery ? stats.avg_delivery + ' days' : 'No data';
        document.getElementById('statLowStock').textContent = stats.low_stock;

        // Changes
        const salesChange = stats.sales_change;
        const el = document.getElementById('statSalesChange');
        if (salesChange === null) {
            el.textContent = 'N/A'; el.className = 'bo-stat-change neutral';
        } else if (salesChange >= 0) {
            el.textContent = `↑ ${salesChange}% vs yesterday`; el.className = 'bo-stat-change up';
        } else {
            el.textContent = `↓ ${Math.abs(salesChange)}% vs yesterday`; el.className = 'bo-stat-change down';
        }

        document.getElementById('statProductsChange').textContent =
            stats.products_change !== null ? (stats.products_change >= 0 ? `↑ ${stats.products_change} more than avg` : `↓ ${Math.abs(stats.products_change)} less than avg`) : 'N/A';
        document.getElementById('statLowStockInfo').textContent =
            stats.low_stock > 0 ? '⚠ Needs attention' : '✓ All stocked';
    }

    // Charts
    const salesData = await fetchAPI('api/sales_data.php', params);
    if (salesData) {
        const ctx1 = document.getElementById('salesTrendCanvas');
        if (ctx1) renderSalesTrend(ctx1, salesData.trend_labels, salesData.trend_data);
        const ctx2 = document.getElementById('topProductsCanvas');
        if (ctx2) renderTopProducts(ctx2, salesData.top_labels, salesData.top_data);
        const ctx3 = document.getElementById('monthlySalesCanvas');
        if (ctx3) renderMonthlySales(ctx3, salesData.monthly_labels, salesData.monthly_data);
        const ctx4 = document.getElementById('categoryCanvas');
        if (ctx4) renderCategoryChart(ctx4, salesData.category_labels, salesData.category_data);
    }

    // Recent deliveries
    const deliveries = await fetchAPI('api/delivery_data.php', { branch_id: BRANCH_ID, limit: 5 });
    if (deliveries && deliveries.recent) {
        const tbody = document.getElementById('recentDeliveries');
        if (deliveries.recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">No deliveries yet</td></tr>';
        } else {
            tbody.innerHTML = deliveries.recent.map(d => `
                <tr>
                    <td>${escapeHtml(d.supplier)}</td>
                    <td>${escapeHtml(d.item_name)}</td>
                    <td><span class="bo-badge ${d.status === 'completed' ? 'success' : d.status === 'upcoming' ? 'info' : 'neutral'}">${escapeHtml(d.status)}</span></td>
                    <td>${d.delivery_time ? d.delivery_time + ' days' : '—'}</td>
                </tr>
            `).join('');
        }
    }

    // Staff on duty
    const staff = await fetchAPI('api/staff_data.php', { branch_id: BRANCH_ID });
    if (staff && staff.on_duty) {
        const tbody = document.getElementById('staffOnDuty');
        if (staff.on_duty.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#888;padding:20px;">No staff on duty</td></tr>';
        } else {
            tbody.innerHTML = staff.on_duty.map(s => `
                <tr>
                    <td>${escapeHtml(s.name)}</td>
                    <td><span class="bo-badge ${s.shift_type === 'AM' ? 'info' : 'warning'}">${s.shift_type} Shift</span></td>
                    <td>${formatPeso(s.total_sales)}</td>
                </tr>
            `).join('');
        }
    }
}

// Compare data loader
let compareSalesChartInstance = null;
let compareProductsChartInstance = null;

async function loadCompareData() {
    const b1 = document.getElementById('compareBranch1').value;
    const b2 = document.getElementById('compareBranch2').value;
    const activeRange = document.querySelector('.bo-date-btn.active')?.dataset.range || 'today';

    const data = await fetchAPI('api/compare_data.php', { branch1: b1, branch2: b2, range: activeRange });
    if (data) {
        const b1Name = BRANCHES.find(b => b.id == b1)?.name || 'Branch 1';
        const b2Name = BRANCHES.find(b => b.id == b2)?.name || 'Branch 2';

        compareSalesChartInstance = destroyChart(compareSalesChartInstance);
        const ctx1 = document.getElementById('compareSalesCanvas');
        if (ctx1) compareSalesChartInstance = renderCompareChart(ctx1, data.labels, data.sales1, data.sales2, b1Name, b2Name);

        compareProductsChartInstance = destroyChart(compareProductsChartInstance);
        const ctx2 = document.getElementById('compareProductsCanvas');
        if (ctx2) compareProductsChartInstance = renderCompareChart(ctx2, data.labels, data.products1, data.products2, b1Name, b2Name);
    }
}

// Initial load (escapeHtml is in branch-owner.js)
loadDashboardData('today');
</script>
</body>
</html>
```

- [ ] **Step 2: Verify syntax**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/dashboard.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add branch_owner/dashboard.php
git commit -m "feat: add branch owner dashboard page with stats, charts, and compare mode"
```

---

## Task 8: Dashboard API Endpoints

**Files:**
- Create: `branch_owner/api/dashboard_stats.php`
- Create: `branch_owner/api/sales_data.php`
- Create: `branch_owner/api/delivery_data.php`
- Create: `branch_owner/api/staff_data.php`
- Create: `branch_owner/api/compare_data.php`

- [ ] **Step 1: Create api/dashboard_stats.php**

Create `branch_owner/api/dashboard_stats.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

$range = $_GET['range'] ?? 'today';
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');

// Today's sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE branch_id = ? AND DATE(date_time) = CURDATE()");
$stmt->execute([$branch_id]);
$today_sales = $stmt->fetchColumn();

// Yesterday's sales for comparison
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE branch_id = ? AND DATE(date_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$stmt->execute([$branch_id]);
$yesterday_sales = $stmt->fetchColumn();

$sales_change = null;
if ($yesterday_sales > 0) {
    $sales_change = round((($today_sales - $yesterday_sales) / $yesterday_sales) * 100);
}

// Products sold today
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.branch_id = ? AND DATE(o.date_time) = CURDATE()");
$stmt->execute([$branch_id]);
$products_sold = $stmt->fetchColumn();

// Average daily products sold (last 30 days)
$stmt = $pdo->prepare("SELECT COALESCE(AVG(daily_count), 0) FROM (SELECT DATE(o.date_time) as d, SUM(oi.quantity) as daily_count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.branch_id = ? AND o.date_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(o.date_time)) sub");
$stmt->execute([$branch_id]);
$avg_products = round($stmt->fetchColumn());
$products_change = $products_sold - $avg_products;

// Average delivery time (last 5 completed deliveries)
$stmt = $pdo->prepare("SELECT ROUND(AVG(DATEDIFF(received_date, order_date)), 1) FROM (SELECT received_date, order_date FROM inventory_deliveries WHERE branch_id = ? AND status = 'completed' AND received_date IS NOT NULL AND order_date IS NOT NULL ORDER BY received_date DESC LIMIT 5) sub");
$stmt->execute([$branch_id]);
$avg_delivery = $stmt->fetchColumn();

// Low stock count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND quantity <= min_stock AND (deleted_at IS NULL OR deleted_at = '')");
$stmt->execute([$branch_id]);
$low_stock = $stmt->fetchColumn();

jsonSuccess([
    'today_sales' => $today_sales,
    'sales_change' => $sales_change,
    'products_sold' => $products_sold,
    'products_change' => $products_change,
    'avg_delivery' => $avg_delivery,
    'low_stock' => $low_stock
]);
```

- [ ] **Step 2: Create api/sales_data.php**

Create `branch_owner/api/sales_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

$range = $_GET['range'] ?? 'today';
$days = ($range === 'week') ? 7 : (($range === 'month') ? 30 : 7);
$from = $_GET['from'] ?? date('Y-m-d', strtotime("-{$days} days"));
$to = $_GET['to'] ?? date('Y-m-d');

// Daily sales trend
$stmt = $pdo->prepare("SELECT DATE(date_time) as day, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE branch_id = ? AND date_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(date_time) ORDER BY day");
$stmt->execute([$branch_id, $days]);
$trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

$trend_labels = array_map(fn($r) => date('M d', strtotime($r['day'])), $trend);
$trend_data = array_map(fn($r) => (float)$r['total'], $trend);

// Top 10 products by revenue
$stmt = $pdo->prepare("SELECT p.name, COALESCE(SUM(oi.subtotal), 0) as revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE o.branch_id = ? AND o.date_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 10");
$stmt->execute([$branch_id, $days]);
$top = $stmt->fetchAll(PDO::FETCH_ASSOC);

$top_labels = array_map(fn($r) => $r['name'], $top);
$top_data = array_map(fn($r) => (float)$r['revenue'], $top);

// Monthly sales (last 6 months)
$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_time, '%Y-%m') as month, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE branch_id = ? AND date_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(date_time, '%Y-%m') ORDER BY month");
$stmt->execute([$branch_id]);
$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthly_labels = array_map(fn($r) => date('M Y', strtotime($r['month'] . '-01')), $monthly);
$monthly_data = array_map(fn($r) => (float)$r['total'], $monthly);

// Products by category
$stmt = $pdo->prepare("SELECT p.category, COALESCE(SUM(oi.subtotal), 0) as revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE o.branch_id = ? AND o.date_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY p.category ORDER BY revenue DESC");
$stmt->execute([$branch_id, $days]);
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$category_labels = array_map(fn($r) => $r['category'] ?: 'Uncategorized', $cats);
$category_data = array_map(fn($r) => (float)$r['revenue'], $cats);

jsonSuccess([
    'trend_labels' => $trend_labels,
    'trend_data' => $trend_data,
    'top_labels' => $top_labels,
    'top_data' => $top_data,
    'monthly_labels' => $monthly_labels,
    'monthly_data' => $monthly_data,
    'category_labels' => $category_labels,
    'category_data' => $category_data
]);
```

- [ ] **Step 3: Create api/delivery_data.php**

Create `branch_owner/api/delivery_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

$limit = (int)($_GET['limit'] ?? 10);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? 'all';

$where = "del.branch_id = ?";
$params = [$branch_id];

if ($status_filter !== 'all') {
    $where .= " AND del.status = ?";
    $params[] = $status_filter;
}

// Recent deliveries
$stmt = $pdo->prepare("SELECT del.*, i.item_name, CASE WHEN del.received_date IS NOT NULL AND del.order_date IS NOT NULL THEN DATEDIFF(del.received_date, del.order_date) ELSE NULL END as delivery_time FROM inventory_deliveries del LEFT JOIN inventory i ON del.inventory_id = i.id WHERE $where ORDER BY del.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_deliveries del WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Delivery performance (avg time per week, last 8 weeks)
$stmt = $pdo->prepare("SELECT YEARWEEK(received_date) as wk, ROUND(AVG(DATEDIFF(received_date, order_date)), 1) as avg_days FROM inventory_deliveries WHERE branch_id = ? AND status = 'completed' AND received_date IS NOT NULL AND order_date IS NOT NULL AND received_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) GROUP BY YEARWEEK(received_date) ORDER BY wk");
$stmt->execute([$branch_id]);
$perf = $stmt->fetchAll(PDO::FETCH_ASSOC);

$perf_labels = array_map(fn($r) => 'Wk ' . substr($r['wk'], -2), $perf);
$perf_data = array_map(fn($r) => (float)$r['avg_days'], $perf);

jsonSuccess([
    'recent' => $recent,
    'total' => $total,
    'pages' => ceil($total / $limit),
    'perf_labels' => $perf_labels,
    'perf_data' => $perf_data
]);
```

- [ ] **Step 4: Create api/staff_data.php**

Create `branch_owner/api/staff_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

// Staff currently on duty (open shifts today)
$stmt = $pdo->prepare("
    SELECT cs.*, u.full_name as name,
    COALESCE((SELECT SUM(o.total_amount) FROM orders o WHERE o.shift_id = cs.id AND o.branch_id = ?), 0) as total_sales,
    COALESCE((SELECT COUNT(*) FROM orders o WHERE o.shift_id = cs.id AND o.branch_id = ?), 0) as total_orders
    FROM cashier_shifts cs
    LEFT JOIN users u ON cs.cashier_id = u.id
    WHERE cs.branch_id = ? AND cs.shift_date = CURDATE() AND cs.status = 'active'
    ORDER BY cs.start_time DESC
");
$stmt->execute([$branch_id, $branch_id, $branch_id]);
$on_duty = $stmt->fetchAll(PDO::FETCH_ASSOC);

// All staff performance for date range
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT u.full_name as name, u.id as user_id,
    COUNT(DISTINCT cs.id) as shifts_worked,
    COALESCE(SUM(cs.total_sales), 0) as total_sales,
    COALESCE(SUM(cs.total_transactions), 0) as total_orders,
    CASE WHEN SUM(cs.total_transactions) > 0 THEN ROUND(SUM(cs.total_sales) / SUM(cs.total_transactions), 2) ELSE 0 END as avg_order
    FROM cashier_shifts cs
    LEFT JOIN users u ON cs.cashier_id = u.id
    WHERE cs.branch_id = ? AND cs.shift_date BETWEEN ? AND ?
    GROUP BY u.id, u.full_name
    ORDER BY total_sales DESC
");
$stmt->execute([$branch_id, $from, $to]);
$performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess([
    'on_duty' => $on_duty,
    'performance' => $performance
]);
```

- [ ] **Step 5: Create api/compare_data.php**

Create `branch_owner/api/compare_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$b1 = (int)($_GET['branch1'] ?? 0);
$b2 = (int)($_GET['branch2'] ?? 0);

if (!in_array($b1, $branch_ids) || !in_array($b2, $branch_ids)) jsonError('Access denied', 403);

$range = $_GET['range'] ?? 'today';
$days = ($range === 'week') ? 7 : (($range === 'month') ? 30 : 7);

// Daily comparison
$stmt = $pdo->prepare("
    SELECT DATE(date_time) as day,
    SUM(CASE WHEN branch_id = ? THEN total_amount ELSE 0 END) as sales1,
    SUM(CASE WHEN branch_id = ? THEN total_amount ELSE 0 END) as sales2
    FROM orders WHERE branch_id IN (?, ?) AND date_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(date_time) ORDER BY day
");
$stmt->execute([$b1, $b2, $b1, $b2, $days]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = array_map(fn($r) => date('M d', strtotime($r['day'])), $rows);
$sales1 = array_map(fn($r) => (float)$r['sales1'], $rows);
$sales2 = array_map(fn($r) => (float)$r['sales2'], $rows);

// Products sold comparison
$stmt = $pdo->prepare("
    SELECT DATE(o.date_time) as day,
    SUM(CASE WHEN o.branch_id = ? THEN oi.quantity ELSE 0 END) as prod1,
    SUM(CASE WHEN o.branch_id = ? THEN oi.quantity ELSE 0 END) as prod2
    FROM order_items oi JOIN orders o ON oi.order_id = o.id
    WHERE o.branch_id IN (?, ?) AND o.date_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(o.date_time) ORDER BY day
");
$stmt->execute([$b1, $b2, $b1, $b2, $days]);
$prows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products1 = array_map(fn($r) => (int)$r['prod1'], $prows);
$products2 = array_map(fn($r) => (int)$r['prod2'], $prows);

jsonSuccess([
    'labels' => $labels,
    'sales1' => $sales1,
    'sales2' => $sales2,
    'products1' => $products1,
    'products2' => $products2
]);
```

- [ ] **Step 6: Verify all API files syntax**

```bash
cd /Applications/XAMPP/xamppfiles
for f in /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/api/*.php; do ./bin/php -l "$f"; done
```

Expected: All files `No syntax errors detected`

- [ ] **Step 7: Commit**

```bash
git add branch_owner/api/
git commit -m "feat: add all branch owner API endpoints (stats, sales, delivery, staff, compare)"
```

---

## Task 9: Branch Owner Sub-Pages (Sales, Products, Deliveries, Inventory, Staff)

**Files:**
- Create: `branch_owner/sales.php`
- Create: `branch_owner/products.php`
- Create: `branch_owner/deliveries.php`
- Create: `branch_owner/inventory.php`
- Create: `branch_owner/staff.php`
- Create: `branch_owner/api/products_data.php`
- Create: `branch_owner/api/inventory_data.php`

- [ ] **Step 1: Create sales.php**

Create `branch_owner/sales.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1 class="bo-page-title">Sales</h1>
<p class="bo-page-subtitle"><?= htmlspecialchars($current_branch_name) ?> — Sales history and trends</p>

<!-- Filters -->
<div class="bo-table-card">
    <div class="bo-table-header">
        <div class="bo-table-title"><i class="bx bx-receipt"></i> Sales History</div>
        <div class="bo-filters">
            <input type="date" id="salesFrom" class="bo-filter-input" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            <input type="date" id="salesTo" class="bo-filter-input" value="<?= date('Y-m-d') ?>">
            <select id="salesCategory" class="bo-filter-select">
                <option value="">All Categories</option>
            </select>
            <button class="bo-filter-btn" onclick="loadSales()">Filter</button>
        </div>
    </div>
    <table class="bo-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Order #</th>
                <th>Items</th>
                <th>Total</th>
                <th>Cashier</th>
            </tr>
        </thead>
        <tbody id="salesTableBody">
            <tr><td colspan="5" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;background:#f9f9f9;">
                <td colspan="3">Total</td>
                <td id="salesTotalAmount">₱0.00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="bo-pagination" id="salesPagination"></div>
</div>

<!-- Sales Charts -->
<div class="bo-charts-row">
    <div class="bo-chart-card">
        <div class="bo-chart-title"><i class="bx bx-trending-up"></i> Daily Sales Trend</div>
        <div class="bo-chart-container"><canvas id="salesPageTrendCanvas"></canvas></div>
    </div>
    <div class="bo-chart-card">
        <div class="bo-chart-title"><i class="bx bx-bar-chart-alt-2"></i> Monthly Sales</div>
        <div class="bo-chart-container"><canvas id="salesPageMonthlyCanvas"></canvas></div>
    </div>
</div>

</main></div>
<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;
let salesPage = 1;

async function loadSales(page = 1) {
    salesPage = page;
    const params = {
        branch_id: BRANCH_ID,
        from: document.getElementById('salesFrom').value,
        to: document.getElementById('salesTo').value,
        category: document.getElementById('salesCategory').value,
        page: page, limit: 25
    };

    const data = await fetchAPI('api/sales_data.php', params);
    if (!data) return;

    // Render table
    const tbody = document.getElementById('salesTableBody');
    if (!data.orders || data.orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">No sales found</td></tr>';
        document.getElementById('salesTotalAmount').textContent = formatPeso(0);
    } else {
        tbody.innerHTML = data.orders.map(o => `
            <tr>
                <td>${escapeHtml(o.date)}</td>
                <td>${escapeHtml(o.order_number)}</td>
                <td>${o.items_count}</td>
                <td>${formatPeso(o.total_amount)}</td>
                <td>${escapeHtml(o.cashier_name || '—')}</td>
            </tr>
        `).join('');
        document.getElementById('salesTotalAmount').textContent = formatPeso(data.total_amount || 0);
    }

    // Pagination
    renderPagination(document.getElementById('salesPagination'), page, data.pages || 1, loadSales);

    // Charts
    if (data.trend_labels) {
        const ctx1 = document.getElementById('salesPageTrendCanvas');
        if (ctx1) renderSalesTrend(ctx1, data.trend_labels, data.trend_data);
    }
    if (data.monthly_labels) {
        const ctx2 = document.getElementById('salesPageMonthlyCanvas');
        if (ctx2) renderMonthlySales(ctx2, data.monthly_labels, data.monthly_data);
    }
}

loadSales();
</script>
</body></html>
```

- [ ] **Step 2: Create products.php**

Create `branch_owner/products.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1 class="bo-page-title">Products</h1>
<p class="bo-page-subtitle"><?= htmlspecialchars($current_branch_name) ?> — Product performance</p>

<div class="bo-charts-row">
    <div class="bo-chart-card">
        <div class="bo-chart-title"><i class="bx bx-bar-chart-alt-2"></i> Top 10 Products by Revenue</div>
        <div class="bo-chart-container"><canvas id="topProductsPageCanvas"></canvas></div>
    </div>
    <div class="bo-chart-card">
        <div class="bo-chart-title"><i class="bx bx-category"></i> Revenue by Category</div>
        <div class="bo-chart-container"><canvas id="categoryPageCanvas"></canvas></div>
    </div>
</div>

<div class="bo-table-card">
    <div class="bo-table-header">
        <div class="bo-table-title"><i class="bx bx-package"></i> Product Performance</div>
        <div class="bo-filters">
            <input type="date" id="prodFrom" class="bo-filter-input" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            <input type="date" id="prodTo" class="bo-filter-input" value="<?= date('Y-m-d') ?>">
            <button class="bo-filter-btn" onclick="loadProducts()">Filter</button>
        </div>
    </div>
    <table class="bo-table">
        <thead>
            <tr><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th><th>Avg/Day</th></tr>
        </thead>
        <tbody id="productsTableBody">
            <tr><td colspan="5" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
    </table>
</div>

</main></div>
<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;

async function loadProducts() {
    const params = {
        branch_id: BRANCH_ID,
        from: document.getElementById('prodFrom').value,
        to: document.getElementById('prodTo').value
    };
    const data = await fetchAPI('api/products_data.php', params);
    if (!data) return;

    const tbody = document.getElementById('productsTableBody');
    if (!data.products || data.products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">No products sold</td></tr>';
    } else {
        tbody.innerHTML = data.products.map(p => `
            <tr>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.category || 'N/A')}</td>
                <td>${p.qty_sold}</td>
                <td>${formatPeso(p.revenue)}</td>
                <td>${p.avg_per_day}</td>
            </tr>
        `).join('');
    }

    if (data.top_labels) renderTopProducts(document.getElementById('topProductsPageCanvas'), data.top_labels, data.top_data);
    if (data.category_labels) renderCategoryChart(document.getElementById('categoryPageCanvas'), data.category_labels, data.category_data);
}

loadProducts();
</script>
</body></html>
```

- [ ] **Step 3: Create deliveries.php**

Create `branch_owner/deliveries.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1 class="bo-page-title">Deliveries</h1>
<p class="bo-page-subtitle"><?= htmlspecialchars($current_branch_name) ?> — Supplier delivery tracking</p>

<div class="bo-stats" style="margin-bottom:24px;">
    <div class="bo-stat-card">
        <div class="bo-stat-label">Avg Delivery Time</div>
        <div class="bo-stat-value success" id="avgDeliveryTime">—</div>
        <div class="bo-stat-change neutral">Last 5 completed</div>
    </div>
    <div class="bo-stat-card">
        <div class="bo-stat-label">Pending</div>
        <div class="bo-stat-value primary" id="pendingCount">0</div>
    </div>
    <div class="bo-stat-card">
        <div class="bo-stat-label">In Transit</div>
        <div class="bo-stat-value secondary" id="transitCount">0</div>
    </div>
    <div class="bo-stat-card">
        <div class="bo-stat-label">Completed</div>
        <div class="bo-stat-value success" id="completedCount">0</div>
    </div>
</div>

<div class="bo-chart-card" style="margin-bottom:24px;">
    <div class="bo-chart-title"><i class="bx bx-line-chart"></i> Delivery Performance (Avg Days by Week)</div>
    <div class="bo-chart-container"><canvas id="deliveryPerfCanvas"></canvas></div>
</div>

<div class="bo-table-card">
    <div class="bo-table-header">
        <div class="bo-table-title"><i class="bx bx-truck"></i> All Deliveries</div>
        <div class="bo-filters">
            <select id="deliveryStatus" class="bo-filter-select">
                <option value="all">All Status</option>
                <option value="upcoming">Upcoming</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button class="bo-filter-btn" onclick="loadDeliveries()">Filter</button>
        </div>
    </div>
    <table class="bo-table">
        <thead>
            <tr><th>Supplier</th><th>Item</th><th>Qty</th><th>Ordered</th><th>Expected</th><th>Received</th><th>Time</th><th>Status</th></tr>
        </thead>
        <tbody id="deliveriesTableBody">
            <tr><td colspan="8" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
    </table>
    <div class="bo-pagination" id="deliveriesPagination"></div>
</div>

</main></div>
<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;

async function loadDeliveries(page = 1) {
    const params = { branch_id: BRANCH_ID, status: document.getElementById('deliveryStatus').value, page: page, limit: 15 };
    const data = await fetchAPI('api/delivery_data.php', params);
    if (!data) return;

    const tbody = document.getElementById('deliveriesTableBody');
    if (!data.recent || data.recent.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;padding:20px;">No deliveries found</td></tr>';
    } else {
        tbody.innerHTML = data.recent.map(d => `
            <tr>
                <td>${escapeHtml(d.supplier)}</td>
                <td>${escapeHtml(d.item_name)}</td>
                <td>${d.quantity}</td>
                <td>${d.order_date || '—'}</td>
                <td>${d.expected_date || '—'}</td>
                <td>${d.received_date || '—'}</td>
                <td>${d.delivery_time ? d.delivery_time + ' days' : '—'}</td>
                <td><span class="bo-badge ${d.status === 'completed' ? 'success' : d.status === 'upcoming' ? 'info' : 'neutral'}">${escapeHtml(d.status)}</span></td>
            </tr>
        `).join('');
    }

    renderPagination(document.getElementById('deliveriesPagination'), page, data.pages || 1, loadDeliveries);

    if (data.perf_labels) renderDeliveryChart(document.getElementById('deliveryPerfCanvas'), data.perf_labels, data.perf_data);
}

loadDeliveries();
</script>
</body></html>
```

- [ ] **Step 4: Create inventory.php**

Create `branch_owner/inventory.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1 class="bo-page-title">Inventory</h1>
<p class="bo-page-subtitle"><?= htmlspecialchars($current_branch_name) ?> — Stock levels (read-only)</p>

<div class="bo-table-card">
    <div class="bo-table-header">
        <div class="bo-table-title"><i class="bx bx-box"></i> Inventory Items</div>
        <div class="bo-filters">
            <select id="inventoryFilter" class="bo-filter-select">
                <option value="all">All Items</option>
                <option value="low">Low Stock</option>
                <option value="critical">Critical</option>
                <option value="ok">In Stock</option>
            </select>
            <input type="text" id="inventorySearch" class="bo-filter-input" placeholder="Search items...">
            <button class="bo-filter-btn" onclick="loadInventory()">Filter</button>
        </div>
    </div>
    <table class="bo-table">
        <thead>
            <tr><th>Item Name</th><th>Category</th><th>Quantity</th><th>Min Stock</th><th>Unit</th><th>Status</th></tr>
        </thead>
        <tbody id="inventoryTableBody">
            <tr><td colspan="6" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
    </table>
    <div class="bo-pagination" id="inventoryPagination"></div>
</div>

</main></div>
<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;

async function loadInventory(page = 1) {
    const params = {
        branch_id: BRANCH_ID,
        filter: document.getElementById('inventoryFilter').value,
        search: document.getElementById('inventorySearch').value,
        page: page, limit: 25
    };
    const data = await fetchAPI('api/inventory_data.php', params);
    if (!data) return;

    const tbody = document.getElementById('inventoryTableBody');
    if (!data.items || data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;padding:20px;">No inventory items found</td></tr>';
    } else {
        tbody.innerHTML = data.items.map(i => {
            let statusClass = 'success', statusText = 'OK';
            if (i.quantity <= 0) { statusClass = 'danger'; statusText = 'Out of Stock'; }
            else if (i.quantity <= i.min_stock) { statusClass = 'danger'; statusText = 'Critical'; }
            else if (i.quantity <= i.min_stock * 1.5) { statusClass = 'warning'; statusText = 'Low'; }
            return `<tr>
                <td><strong>${escapeHtml(i.item_name)}</strong></td>
                <td>${escapeHtml(i.category || 'N/A')}</td>
                <td>${i.quantity}</td>
                <td>${i.min_stock}</td>
                <td>${escapeHtml(i.unit || 'pcs')}</td>
                <td><span class="bo-badge ${statusClass}">${statusText}</span></td>
            </tr>`;
        }).join('');
    }

    renderPagination(document.getElementById('inventoryPagination'), page, data.pages || 1, loadInventory);
}

loadInventory();
</script>
</body></html>
```

- [ ] **Step 5: Create staff.php**

Create `branch_owner/staff.php`:

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1 class="bo-page-title">Staff Performance</h1>
<p class="bo-page-subtitle"><?= htmlspecialchars($current_branch_name) ?> — Cashier and staff metrics</p>

<div class="bo-table-card" style="margin-bottom:24px;">
    <div class="bo-table-title"><i class="bx bx-user-check"></i> Currently on Duty</div>
    <table class="bo-table">
        <thead><tr><th>Name</th><th>Shift</th><th>Started</th><th>Sales</th><th>Orders</th></tr></thead>
        <tbody id="onDutyBody">
            <tr><td colspan="5" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
    </table>
</div>

<div class="bo-table-card">
    <div class="bo-table-header">
        <div class="bo-table-title"><i class="bx bx-group"></i> Staff Performance</div>
        <div class="bo-filters">
            <input type="date" id="staffFrom" class="bo-filter-input" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            <input type="date" id="staffTo" class="bo-filter-input" value="<?= date('Y-m-d') ?>">
            <button class="bo-filter-btn" onclick="loadStaff()">Filter</button>
        </div>
    </div>
    <table class="bo-table">
        <thead><tr><th>Name</th><th>Shifts</th><th>Total Sales</th><th>Orders</th><th>Avg Order</th></tr></thead>
        <tbody id="staffPerfBody">
            <tr><td colspan="5" class="bo-empty"><p>Loading...</p></td></tr>
        </tbody>
    </table>
</div>

</main></div>
<script src="assets/js/branch-owner.js"></script>
<script>
const BRANCH_ID = <?= json_encode($current_branch_id) ?>;

async function loadStaff() {
    const params = {
        branch_id: BRANCH_ID,
        from: document.getElementById('staffFrom').value,
        to: document.getElementById('staffTo').value
    };
    const data = await fetchAPI('api/staff_data.php', params);
    if (!data) return;

    // On duty
    const dutyBody = document.getElementById('onDutyBody');
    if (!data.on_duty || data.on_duty.length === 0) {
        dutyBody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">No staff on duty right now</td></tr>';
    } else {
        dutyBody.innerHTML = data.on_duty.map(s => `
            <tr>
                <td><strong>${escapeHtml(s.name)}</strong></td>
                <td><span class="bo-badge ${s.shift_type === 'AM' ? 'info' : 'warning'}">${s.shift_type}</span></td>
                <td>${s.start_time || '—'}</td>
                <td>${formatPeso(s.total_sales)}</td>
                <td>${s.total_orders}</td>
            </tr>
        `).join('');
    }

    // Performance
    const perfBody = document.getElementById('staffPerfBody');
    if (!data.performance || data.performance.length === 0) {
        perfBody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">No staff data for this period</td></tr>';
    } else {
        perfBody.innerHTML = data.performance.map(s => `
            <tr>
                <td><strong>${escapeHtml(s.name)}</strong></td>
                <td>${s.shifts_worked}</td>
                <td>${formatPeso(s.total_sales)}</td>
                <td>${s.total_orders}</td>
                <td>${formatPeso(s.avg_order)}</td>
            </tr>
        `).join('');
    }
}

loadStaff();
</script>
</body></html>
```

- [ ] **Step 6: Create api/products_data.php**

Create `branch_owner/api/products_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$days_in_range = max(1, (strtotime($to) - strtotime($from)) / 86400);

// Product performance
$stmt = $pdo->prepare("
    SELECT p.name, p.category, SUM(oi.quantity) as qty_sold,
    SUM(oi.subtotal) as revenue,
    ROUND(SUM(oi.quantity) / ?, 1) as avg_per_day
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.branch_id = ? AND DATE(o.date_time) BETWEEN ? AND ?
    GROUP BY p.id, p.name, p.category
    ORDER BY revenue DESC
");
$stmt->execute([$days_in_range, $branch_id, $from, $to]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 for chart
$top = array_slice($products, 0, 10);
$top_labels = array_map(fn($r) => $r['name'], $top);
$top_data = array_map(fn($r) => (float)$r['revenue'], $top);

// Category breakdown
$stmt = $pdo->prepare("
    SELECT p.category, SUM(oi.subtotal) as revenue
    FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id
    WHERE o.branch_id = ? AND DATE(o.date_time) BETWEEN ? AND ?
    GROUP BY p.category ORDER BY revenue DESC
");
$stmt->execute([$branch_id, $from, $to]);
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess([
    'products' => $products,
    'top_labels' => $top_labels,
    'top_data' => $top_data,
    'category_labels' => array_map(fn($r) => $r['category'] ?: 'Uncategorized', $cats),
    'category_data' => array_map(fn($r) => (float)$r['revenue'], $cats)
]);
```

- [ ] **Step 7: Create api/inventory_data.php**

Create `branch_owner/api/inventory_data.php`:

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

$branch_id = (int)($_GET['branch_id'] ?? 0);
if (!in_array($branch_id, $branch_ids)) jsonError('Access denied', 403);

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 25);
$offset = ($page - 1) * $limit;

$where = "branch_id = ? AND (deleted_at IS NULL OR deleted_at = '')";
$params = [$branch_id];

if ($filter === 'low') {
    $where .= " AND quantity <= min_stock * 1.5 AND quantity > 0";
} elseif ($filter === 'critical') {
    $where .= " AND quantity <= min_stock";
} elseif ($filter === 'ok') {
    $where .= " AND quantity > min_stock * 1.5";
}

if ($search) {
    $where .= " AND item_name LIKE ?";
    $params[] = "%$search%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, item_name, category, quantity, min_stock, unit FROM inventory WHERE $where ORDER BY CASE WHEN quantity <= min_stock THEN 0 ELSE 1 END, quantity ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess([
    'items' => $items,
    'total' => $total,
    'pages' => ceil($total / $limit)
]);
```

- [ ] **Step 8: Verify all PHP files syntax**

```bash
cd /Applications/XAMPP/xamppfiles
for f in /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/*.php /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/api/*.php; do ./bin/php -l "$f"; done
```

Expected: All `No syntax errors detected`

- [ ] **Step 9: Commit**

```bash
git add branch_owner/sales.php branch_owner/products.php branch_owner/deliveries.php branch_owner/inventory.php branch_owner/staff.php branch_owner/api/products_data.php branch_owner/api/inventory_data.php
git commit -m "feat: add all branch owner sub-pages (sales, products, deliveries, inventory, staff)"
```

---

## Task 10: Admin Branch Management Page

**Files:**
- Create: `admin/branches.php`
- Modify: `admin/includes/sidebar.php`

- [ ] **Step 1: Add "Branches" link to admin sidebar**

In `admin/includes/sidebar.php`, add a new nav item after the existing links (before the POS link). The existing sidebar uses `<a>` tags with class `nav-item` directly inside `<nav class="sidebar-nav">` (NOT `<li>` elements). Follow the same pattern:

```php
<a href="branches.php" class="nav-item <?php echo $active_tab === 'branches' ? 'active' : ''; ?>">
    <i class='bx bx-building-house'></i>Branches
</a>
```

- [ ] **Step 2: Create admin/branches.php**

Create `admin/branches.php`:

```php
<?php
$active_tab = 'branches';
require_once 'bootstrap.php';
requireAdmin(); // Enforce admin-only access

// CSRF token helpers (reuse from branch_owner or define here)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $branch_id = (int)($_POST['branch_id'] ?? 0);

        if (empty($name)) {
            $error = 'Branch name is required.';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO branches (name, address, phone, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $address, $phone, $status]);
                $success = 'Branch created successfully.';
            } else {
                $stmt = $pdo->prepare("UPDATE branches SET name = ?, address = ?, phone = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $address, $phone, $status, $branch_id]);
                $success = 'Branch updated successfully.';
            }
        }
    }

    if ($action === 'deactivate') {
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        // Check for assigned owners
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM branch_users WHERE branch_id = ?");
        $stmt->execute([$branch_id]);
        $owner_count = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE branches SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$branch_id]);
        $success = 'Branch deactivated.' . ($owner_count > 0 ? " Warning: $owner_count owner(s) were assigned to this branch." : '');
    }

    if ($action === 'activate') {
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE branches SET status = 'active' WHERE id = ?");
        $stmt->execute([$branch_id]);
        $success = 'Branch activated.';
    }
    } // end CSRF else
}

// Fetch branches
$stmt = $pdo->query("SELECT b.*, (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM branch_users bu JOIN users u ON bu.user_id = u.id WHERE bu.branch_id = b.id) as owners FROM branches b ORDER BY b.status DESC, b.name");
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Branch overview stats
$branch_stats = [];
foreach ($branches as $b) {
    if ($b['status'] !== 'active') continue;
    $s = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE branch_id = ? AND DATE(date_time) = CURDATE()");
    $s->execute([$b['id']]);
    $today_sales = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.branch_id = ? AND DATE(o.date_time) = CURDATE()");
    $s->execute([$b['id']]);
    $products_sold = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND quantity <= min_stock AND (deleted_at IS NULL OR deleted_at = '')");
    $s->execute([$b['id']]);
    $low_stock = $s->fetchColumn();

    $branch_stats[$b['id']] = ['sales' => $today_sales, 'products' => $products_sold, 'low_stock' => $low_stock];
}

require_once 'includes/header.php';
?>

<div class="content-area">
    <h1 class="page-title">Branch Management</h1>

    <?php if (!empty($error)): ?>
    <div style="background:#fde8e8;color:#cc0000;padding:12px 16px;border-radius:8px;margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
    <div style="background:#e6f4ea;color:#2a9d2a;padding:12px 16px;border-radius:8px;margin-bottom:16px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Branch Overview Cards -->
    <div class="stats-grid" style="margin-bottom:24px;">
        <?php foreach ($branches as $b): ?>
        <?php if ($b['status'] !== 'active') continue; ?>
        <?php $st = $branch_stats[$b['id']] ?? ['sales' => 0, 'products' => 0, 'low_stock' => 0]; ?>
        <div class="stat-card">
            <div style="font-weight:600;margin-bottom:8px;"><?= htmlspecialchars($b['name']) ?></div>
            <div style="font-size:13px;color:#666;">
                Today: ₱<?= number_format($st['sales'], 2) ?><br>
                Products: <?= $st['products'] ?><br>
                <?php if ($st['low_stock'] > 0): ?>
                <span style="color:#cc0000;">⚠ <?= $st['low_stock'] ?> low stock</span>
                <?php else: ?>
                <span style="color:#2a9d2a;">✓ Stock OK</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Branch Table -->
    <div class="table-container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="font-size:16px;">All Branches</h2>
            <button onclick="openBranchModal()" class="btn-primary" style="padding:8px 16px;background:#F37902;color:white;border:none;border-radius:6px;cursor:pointer;">
                <i class='bx bx-plus'></i> Add Branch
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Address</th><th>Phone</th><th>Status</th><th>Owners</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $b): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['address']) ?></td>
                    <td><?= htmlspecialchars($b['phone']) ?></td>
                    <td>
                        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;<?= $b['status'] === 'active' ? 'background:#e6f4ea;color:#2a9d2a;' : 'background:#f0f0f0;color:#888;' ?>">
                            <?= ucfirst($b['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($b['owners'] ?: 'None') ?></td>
                    <td>
                        <button onclick="editBranch(<?= htmlspecialchars(json_encode($b)) ?>)" style="background:none;border:none;color:#F37902;cursor:pointer;font-size:18px;" title="Edit"><i class='bx bx-edit'></i></button>
                        <?php if ($b['status'] === 'active'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this branch?<?= $b['owners'] ? ' Warning: ' . htmlspecialchars($b['owners']) . ' are assigned as owners.' : '' ?>')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                            <button type="submit" style="background:none;border:none;color:#cc0000;cursor:pointer;font-size:18px;" title="Deactivate"><i class='bx bx-power-off'></i></button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                            <button type="submit" style="background:none;border:none;color:#2a9d2a;cursor:pointer;font-size:18px;" title="Activate"><i class='bx bx-check-circle'></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Branch Modal -->
<div id="branchModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:12px;padding:24px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
        <h3 id="branchModalTitle" style="margin-bottom:16px;">Add Branch</h3>
        <form method="POST" id="branchForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" id="branchAction" value="create">
            <input type="hidden" name="branch_id" id="branchId" value="0">
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Branch Name *</label>
                <input type="text" name="name" id="branchName" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Address</label>
                <input type="text" name="address" id="branchAddress" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Phone</label>
                <input type="text" name="phone" id="branchPhone" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Status</label>
                <select name="status" id="branchStatus" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeBranchModal()" style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:8px 16px;background:#F37902;color:white;border:none;border-radius:6px;cursor:pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBranchModal() {
    document.getElementById('branchModalTitle').textContent = 'Add Branch';
    document.getElementById('branchAction').value = 'create';
    document.getElementById('branchId').value = 0;
    document.getElementById('branchName').value = '';
    document.getElementById('branchAddress').value = '';
    document.getElementById('branchPhone').value = '';
    document.getElementById('branchStatus').value = 'active';
    document.getElementById('branchModal').style.display = 'flex';
}

function editBranch(branch) {
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    document.getElementById('branchAction').value = 'update';
    document.getElementById('branchId').value = branch.id;
    document.getElementById('branchName').value = branch.name;
    document.getElementById('branchAddress').value = branch.address;
    document.getElementById('branchPhone').value = branch.phone;
    document.getElementById('branchStatus').value = branch.status;
    document.getElementById('branchModal').style.display = 'flex';
}

function closeBranchModal() {
    document.getElementById('branchModal').style.display = 'none';
}

document.getElementById('branchModal').addEventListener('click', function(e) {
    if (e.target === this) closeBranchModal();
});
</script>

<?php require_once 'includes/sidebar.php'; ?>
```

- [ ] **Step 3: Verify syntax**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/admin/branches.php
```

- [ ] **Step 4: Commit**

```bash
git add admin/branches.php admin/includes/sidebar.php
git commit -m "feat: add admin branch management page with CRUD and overview stats"
```

---

## Task 11: Admin Users Page — Branch Owner Support & MD5 Fix

**Files:**
- Modify: `admin/users.php:13,34,206`

- [ ] **Step 1: Fix MD5 password hashing to bcrypt**

In `admin/users.php`, find line 13 where it uses `MD5(?)` for new user creation. Replace with PHP-side hashing using `password_hash()`. Similarly fix line 34 for password updates.

**IMPORTANT:** The existing INSERT query may use `username` as the column name, but the actual DB column is `user_id` (see `pos_system.sql` line 829). Verify the column name matches before fixing. If it says `username`, change it to `user_id`.

At line 13, change the password handling from:
```php
MD5(?)
```
to using PHP's `password_hash()` before the query:
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```
And use `?` with the hashed value in the INSERT.

At line 34, apply the same fix for password updates.

- [ ] **Step 2: Add branch_owner role to role dropdown**

At line 206 where the role `<select>` uses the ROLES constant, the dropdown will automatically include 'branch_owner' since we updated constants.php in Task 2.

- [ ] **Step 3: Add branch assignment UI**

After the role dropdown in the user form (around line 206), add a branch assignment section that shows/hides based on the selected role:

```php
<div id="branchAssignment" style="display:none;margin-top:12px;">
    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Assign Branches</label>
    <select name="branch_ids[]" id="branchSelect" multiple style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;min-height:80px;">
        <?php
        $allBranches = $pdo->query("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name")->fetchAll();
        foreach ($allBranches as $ab):
        ?>
        <option value="<?= $ab['id'] ?>"><?= htmlspecialchars($ab['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <small style="color:#888;">Hold Ctrl/Cmd to select multiple branches</small>
</div>
```

Add JavaScript to toggle visibility:
```javascript
document.querySelector('[name="role"]').addEventListener('change', function() {
    document.getElementById('branchAssignment').style.display = this.value === 'branch_owner' ? 'block' : 'none';
});
```

- [ ] **Step 4: Handle branch assignment on save**

In the user create/update PHP handler, after saving the user, add branch assignment logic:

```php
// After user create/update, handle branch assignments
if ($role === 'branch_owner' && !empty($_POST['branch_ids'])) {
    $userId = $action === 'create' ? $pdo->lastInsertId() : $user_id;
    $pdo->prepare("DELETE FROM branch_users WHERE user_id = ?")->execute([$userId]);
    $insertStmt = $pdo->prepare("INSERT INTO branch_users (branch_id, user_id) VALUES (?, ?)");
    foreach ($_POST['branch_ids'] as $bid) {
        $insertStmt->execute([(int)$bid, $userId]);
    }
}
```

- [ ] **Step 5: Verify syntax**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/admin/users.php
```

- [ ] **Step 6: Commit**

```bash
git add admin/users.php
git commit -m "feat: add branch owner role to user management, fix MD5 to bcrypt, add branch assignment"
```

---

## Task 12: Update Receipt for Dynamic Branch Name

**Files:**
- Modify: `receipt.php:398-399`

- [ ] **Step 1: Replace hardcoded branch info with dynamic lookup**

In `receipt.php` around lines 398-399, replace:
```php
<div class="branch-info">Jasaan Branch</div>
<div class="branch-info">Jasaan, Misamis Oriental</div>
```

With:
```php
<?php
$branch_name = 'Jasaan Branch';
$branch_address = 'Jasaan, Misamis Oriental';
if (isset($order['branch_id'])) {
    $branchStmt = $pdo->prepare("SELECT name, address FROM branches WHERE id = ?");
    $branchStmt->execute([$order['branch_id']]);
    $branch = $branchStmt->fetch(PDO::FETCH_ASSOC);
    if ($branch) {
        $branch_name = $branch['name'];
        $branch_address = $branch['address'];
    }
}
?>
<div class="branch-info"><?= htmlspecialchars($branch_name) ?></div>
<div class="branch-info"><?= htmlspecialchars($branch_address) ?></div>
```

- [ ] **Step 2: Verify syntax**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/receipt.php
```

- [ ] **Step 3: Commit**

```bash
git add receipt.php
git commit -m "feat: replace hardcoded branch info in receipts with dynamic DB lookup"
```

---

## Task 13: Update sales_data.php for Sales Page Pagination

**Files:**
- Modify: `branch_owner/api/sales_data.php`

- [ ] **Step 1: Add paginated orders query to sales_data.php**

Add to the bottom of `branch_owner/api/sales_data.php`, before the `jsonSuccess()` call, the paginated orders list for the sales table:

```php
// Paginated orders for sales table
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 25);
$offset = ($page - 1) * $limit;
$category = $_GET['category'] ?? '';

$orderWhere = "o.branch_id = ? AND DATE(o.date_time) BETWEEN ? AND ?";
$orderParams = [$branch_id, $from, $to];

if ($category) {
    $orderWhere .= " AND EXISTS (SELECT 1 FROM order_items oi2 JOIN products p2 ON oi2.product_id = p2.id WHERE oi2.order_id = o.id AND p2.category = ?)";
    $orderParams[] = $category;
}

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $orderWhere");
$countStmt->execute($orderParams);
$totalOrders = $countStmt->fetchColumn();

// Total amount
$totalAmtStmt = $pdo->prepare("SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE $orderWhere");
$totalAmtStmt->execute($orderParams);
$totalAmount = $totalAmtStmt->fetchColumn();

// Paginated orders
$ordersStmt = $pdo->prepare("
    SELECT o.id, o.order_number, o.total_amount, DATE_FORMAT(o.date_time, '%Y-%m-%d %H:%i') as date,
    u.full_name as cashier_name,
    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as items_count
    FROM orders o LEFT JOIN users u ON o.cashier_id = u.id
    WHERE $orderWhere
    ORDER BY o.date_time DESC LIMIT $limit OFFSET $offset
");
$ordersStmt->execute($orderParams);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
```

Update the `jsonSuccess()` call to include the new data:

```php
jsonSuccess([
    'trend_labels' => $trend_labels,
    'trend_data' => $trend_data,
    'top_labels' => $top_labels,
    'top_data' => $top_data,
    'monthly_labels' => $monthly_labels,
    'monthly_data' => $monthly_data,
    'category_labels' => $category_labels,
    'category_data' => $category_data,
    'orders' => $orders,
    'total_orders' => $totalOrders,
    'total_amount' => $totalAmount,
    'pages' => ceil($totalOrders / $limit)
]);
```

- [ ] **Step 2: Verify and commit**

```bash
cd /Applications/XAMPP/xamppfiles
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner/api/sales_data.php && cd /Applications/XAMPP/xamppfiles/htdocs/minute1 && git add branch_owner/api/sales_data.php && git commit -m "feat: add paginated orders to sales API for sales page table"
```

---

## Task 14: End-to-End Testing

- [ ] **Step 1: Verify all PHP files have no syntax errors**

```bash
cd /Applications/XAMPP/xamppfiles
find /Applications/XAMPP/xamppfiles/htdocs/minute1/branch_owner -name "*.php" -exec ./bin/php -l {} \;
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/login.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/includes/auth.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/includes/constants.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/admin/branches.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/admin/users.php
./bin/php -l /Applications/XAMPP/xamppfiles/htdocs/minute1/receipt.php
```

Expected: All `No syntax errors detected`

- [ ] **Step 2: Run the database migration**

Navigate to phpMyAdmin at `http://localhost/phpmyadmin` and execute `migrations/001_add_branches.sql` against the `pos_system` database. Or run via CLI:

```bash
cd /Applications/XAMPP/xamppfiles
./bin/mysql -u root pos_system < /Applications/XAMPP/xamppfiles/htdocs/minute1/migrations/001_add_branches.sql
```

- [ ] **Step 3: Create a test branch owner account**

First, generate the bcrypt hash:

```bash
cd /Applications/XAMPP/xamppfiles
HASH=$(./bin/php -r "echo password_hash('owner123', PASSWORD_DEFAULT);")
echo "Generated hash: $HASH"
```

Then create the test data:

```bash
cd /Applications/XAMPP/xamppfiles
./bin/mysql -u root pos_system -e "
INSERT INTO branches (name, address, phone, status) VALUES ('Cagayan Branch', 'Cagayan de Oro', '09171234567', 'active');
"

./bin/mysql -u root pos_system -e "
INSERT INTO users (user_id, password, role, full_name, first_name, last_name, email, status)
VALUES ('branchowner1', '$HASH', 'branch_owner', 'Test Owner', 'Test', 'Owner', 'owner@test.com', 'active');
SET @owner_id = LAST_INSERT_ID();
INSERT INTO branch_users (branch_id, user_id) VALUES (1, @owner_id), (2, @owner_id);
"
```

- [ ] **Step 4: Test in browser**

1. Open `http://localhost/minute1/login.php`
2. Login with `branchowner1` / `owner123`
3. Verify redirect to `branch_owner/dashboard.php`
4. Verify branch tabs show both branches
5. Click through all sidebar pages (Sales, Products, Deliveries, Inventory, Staff)
6. Test branch switching
7. Test Compare mode
8. Test date filters
9. Test responsive design (resize browser)
10. Login as admin and test `admin/branches.php`
11. Test creating a new user with Branch Owner role and branch assignment

- [ ] **Step 5: Fix any issues found during testing**

Address any errors, broken queries, or UI issues.

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "feat: complete branch owner dashboard with multi-branch support"
```
