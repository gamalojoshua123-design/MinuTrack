<?php
require_once __DIR__ . '/../bootstrap.php';
requireOwner();

$active_tab = 'users';
$page_title = 'User Management';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) && in_array($_GET['type'], ['success', 'error'], true) ? $_GET['type'] : '';

// ============================================
// PERMISSION DEFINITIONS
// ============================================

/**
 * Get all available permissions with labels and descriptions
 * @return array
 */
function getAvailablePermissions() {
    return [
        // Dashboard
        'dashboard_view' => ['label' => 'Dashboard', 'description' => 'View dashboard and statistics'],
        
        // POS
        'pos_access' => ['label' => 'POS Access', 'description' => 'Access Point of Sale system'],
        
        // Products
        'products_view' => ['label' => 'View Products', 'description' => 'View product list and details'],
        'products_manage' => ['label' => 'Manage Products', 'description' => 'Add, edit, delete products'],
        
        // Inventory
        'inventory_view' => ['label' => 'View Inventory', 'description' => 'View inventory items and levels'],
        'inventory_manage' => ['label' => 'Manage Inventory', 'description' => 'Add, edit, delete inventory items'],
        'inventory_stock_in' => ['label' => 'Stock In', 'description' => 'Add stock to inventory'],
        'inventory_stock_out' => ['label' => 'Stock Out', 'description' => 'Remove stock from inventory'],
        
        // Transactions
        'transactions_view' => ['label' => 'View Transactions', 'description' => 'View transaction history'],
        'transactions_void' => ['label' => 'Void Transactions', 'description' => 'Void/void transactions'],
        
        // Reports
        'reports_view' => ['label' => 'View Reports', 'description' => 'View sales and financial reports'],
        'reports_export' => ['label' => 'Export Reports', 'description' => 'Export reports to Excel/PDF'],
        
        // Users (Admin only)
        'users_view' => ['label' => 'View Users', 'description' => 'View user list and details'],
        'users_manage' => ['label' => 'Manage Users', 'description' => 'Add, edit, delete users'],
        'users_permissions' => ['label' => 'Manage Permissions', 'description' => 'Edit user permissions'],
        
        // Archive
        'archive_view' => ['label' => 'View Archive', 'description' => 'View archived items'],
        'archive_restore' => ['label' => 'Restore Archive', 'description' => 'Restore archived items'],
        'archive_delete' => ['label' => 'Delete Archive', 'description' => 'Permanently delete archived items'],
        
        // Branch (for branch owners)
        'branch_view' => ['label' => 'View Branches', 'description' => 'View branch list and details'],
        'branch_manage' => ['label' => 'Manage Branches', 'description' => 'Add, edit, delete branches'],
        
        // Staff
        'staff_view' => ['label' => 'View Staff', 'description' => 'View staff list and performance'],
        'staff_manage' => ['label' => 'Manage Staff', 'description' => 'Add, edit, delete staff members'],
        
        // Settings
        'settings_view' => ['label' => 'View Settings', 'description' => 'View system settings'],
        'settings_manage' => ['label' => 'Manage Settings', 'description' => 'Edit system settings'],
    ];
}

// AJAX: Get single user with permissions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    header('Content-Type: application/json');

    try {
        if (empty($_GET['id'])) {
            throw new Exception('User ID is required');
        }

        $stmt = $pdo->prepare("
            SELECT id, user_id, role, full_name, email, status, first_name, middle_name, last_name, suffix, permissions
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([intval($_GET['id'])]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

        // Authorization check: non-admins can only view their own profile
        if (!isOwner() && !isManager()) {
            if ((int)$user['id'] !== (int)$_SESSION['user_id']) {
                throw new Exception('Unauthorized');
            }
        }

        // Decode permissions
        $permissions = json_decode($user['permissions'] ?? '{}', true);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        echo json_encode([
            'success' => true,
            'data' => $user,
            'permissions' => $permissions
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Generate next user ID
function getNextUserId($pdo) {
    $stmt = $pdo->prepare("SELECT MAX(CAST(user_id AS UNSIGNED)) as max_id FROM users WHERE user_id REGEXP '^[0-9]+$'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextId = ($result['max_id'] ?? 0) + 1;
    return str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $form_action = $_POST['form_action'] ?? '';

    if ($form_action === 'add_user' || isset($_POST['add_user'])) {
        try {
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'cashier';
            $email = trim($_POST['email'] ?? '');
            $status = $_POST['status'] ?? 'active';

            if (!canAssignRole($role)) {
                throw new Exception('You are not allowed to assign this role');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            // Get custom permissions from form
            $custom_permissions = [];
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $perm_key => $value) {
                    $custom_permissions[$perm_key] = $value === '1' || $value === true;
                }
            }
            
            // Merge with default permissions for the role (using the one from auth.php)
            $default_perms = getDefaultPermissions($role);
            $permissions = array_merge($default_perms, $custom_permissions);

            // Build full name
            $full_name_parts = [$first_name];
            if (!empty($middle_name)) $full_name_parts[] = $middle_name;
            $full_name_parts[] = $last_name;
            if (!empty($suffix)) $full_name_parts[] = $suffix;
            $full_name = implode(' ', $full_name_parts);

            if ($first_name === '') {
                throw new Exception('First name is required');
            }

            if ($last_name === '') {
                throw new Exception('Last name is required');
            }

            if ($password === '') {
                throw new Exception('Password is required');
            }

            if ($email === '') {
                throw new Exception('Email is required');
            }

            // Generate unique numeric user ID
            $user_id = getNextUserId($pdo);

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, password, role, full_name, first_name, middle_name, last_name, suffix, email, permissions, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $hashedPassword,
                $role,
                $full_name,
                $first_name,
                $middle_name,
                $last_name,
                $suffix,
                $email,
                json_encode($permissions),
                $status
            ]);

            header('Location: users.php?message=' . urlencode('User added successfully! User ID: ' . $user_id) . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if ($form_action === 'update_user' || isset($_POST['update_user'])) {
        try {
            $id = intval($_POST['record_id'] ?? 0);
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'cashier';
            $email = trim($_POST['email'] ?? '');
            $status = $_POST['status'] ?? 'active';

            if (!canAssignRole($role)) {
                throw new Exception('You are not allowed to assign this role');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            // Get custom permissions from form
            $custom_permissions = [];
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $perm_key => $value) {
                    $custom_permissions[$perm_key] = $value === '1' || $value === true;
                }
            }
            
            // Merge with default permissions for the role (using the one from auth.php)
            $default_perms = getDefaultPermissions($role);
            $permissions = array_merge($default_perms, $custom_permissions);

            // Build full name
            $full_name_parts = [$first_name];
            if (!empty($middle_name)) $full_name_parts[] = $middle_name;
            $full_name_parts[] = $last_name;
            if (!empty($suffix)) $full_name_parts[] = $suffix;
            $full_name = implode(' ', $full_name_parts);

            if ($id <= 0) {
                throw new Exception('Invalid user record');
            }

            if ($first_name === '') {
                throw new Exception('First name is required');
            }

            if ($last_name === '') {
                throw new Exception('Last name is required');
            }

            if ($email === '') {
                throw new Exception('Email is required');
            }

            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password = ?, role = ?, full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, permissions = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $hashedPassword,
                    $role,
                    $full_name,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $suffix,
                    $email,
                    json_encode($permissions),
                    $status,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET role = ?, full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, permissions = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $role,
                    $full_name,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $suffix,
                    $email,
                    json_encode($permissions),
                    $status,
                    $id
                ]);
            }

            header('Location: users.php?message=' . urlencode('User updated successfully!') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if (isset($_POST['delete_user'])) {
        try {
            $id = intval($_POST['record_id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Invalid user record');
            }

            // Never delete your own account
            if ($id === (int)$_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account');
            }

            // Never delete the last System Owner
            $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $targetStmt->execute([$id]);
            $targetRole = $targetStmt->fetchColumn();
            if ($targetRole === 'admin') {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new Exception('You cannot delete the last System Owner');
                }
            }

            // Soft delete so sales attribution / audit history is preserved
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            auditLog('user_delete', 'users', 'user', $id, 'success', 'Deleted user (soft delete)');
            header('Location: users.php?message=' . urlencode('User deleted successfully!') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if (isset($_POST['toggle_status'])) {
        try {
            $id = intval($_POST['record_id'] ?? 0);
            $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

            if ($id <= 0) {
                throw new Exception('Invalid user record');
            }

            // Prevent deactivating the last active System Owner
            if ($newStatus === 'inactive') {
                $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $targetStmt->execute([$id]);
                $targetRole = $targetStmt->fetchColumn();
                if ($targetRole === 'admin') {
                    $activeAdminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
                    if ($activeAdminCount <= 1 && $id === (int)$_SESSION['user_id']) {
                        throw new Exception('You cannot deactivate the last active System Owner');
                    }
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            $label = $newStatus === 'active' ? 'activated' : 'deactivated';
            auditLog('user_status_toggle', 'users', 'user', $id, 'success', 'User ' . $label);
            header('Location: users.php?message=' . urlencode("User $label successfully!") . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
}

// Stats counts
$stats_total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$stats_managers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager'")->fetchColumn();
$stats_cashiers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'cashier'")->fetchColumn();
$stats_active = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

// Search & filter params
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [10, 15, 25, 50])) $per_page = 15;
$page = max(1, intval($_GET['page'] ?? 1));

// Build WHERE clause
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ? OR user_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '' && in_array($role_filter, ['admin', 'manager', 'cashier'])) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Count total matching
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where_sql");
$count_stmt->execute($params);
$total_records = (int) $count_stmt->fetchColumn();
$total_pages = (int) max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch users with LIMIT/OFFSET
$stmt = $pdo->prepare("SELECT * FROM users $where_sql ORDER BY role, user_id LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$showing_start = $total_records > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $per_page, $total_records);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .user-id-preview {
            background: var(--bg);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 1.25rem;
        }

        .user-id-preview label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .user-id-preview span {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .modal-content {
            max-width: 750px;
        }

        /* Permissions Grid */
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
            padding: 0.5rem 0;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .permission-item:hover {
            background: var(--bg);
        }

        .permission-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .permission-item .perm-label {
            font-weight: 500;
            color: var(--text-primary);
        }

        .permission-item .perm-desc {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: block;
        }

        .permission-group {
            margin-bottom: 0.75rem;
        }

        .permission-group-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid var(--border);
        }

        .permission-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .permission-item.disabled input[type="checkbox"] {
            cursor: not-allowed;
        }

        .role-badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .role-badge.admin { background: #fef3c7; color: #92400e; }
        .role-badge.manager { background: #dbeafe; color: #1e40af; }
        .role-badge.cashier { background: #d1fae5; color: #047857; }

        @media (max-width: 768px) {
            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
            .permissions-grid {
                grid-template-columns: 1fr 1fr;
            }
            .modal-content {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .permissions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type === 'error' ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-orders"><i class='bx bx-group'></i></div>
                            <div>
                                <div class="stat-title">Total Users</div>
                                <div class="stat-value"><?php echo $stats_total; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-alert"><i class='bx bx-shield'></i></div>
                            <div>
                                <div class="stat-title">Admins</div>
                                <div class="stat-value"><?php echo $stats_admins; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-trend"><i class='bx bx-user'></i></div>
                            <div>
                                <div class="stat-title">Managers</div>
                                <div class="stat-value"><?php echo $stats_managers; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales"><i class='bx bx-user'></i></div>
                            <div>
                                <div class="stat-title">Cashiers</div>
                                <div class="stat-value"><?php echo $stats_cashiers; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales"><i class='bx bx-check-circle'></i></div>
                            <div>
                                <div class="stat-title">Active Users</div>
                                <div class="stat-value"><?php echo $stats_active; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-user'></i>User Management</h3>
                        <button class="btn btn-primary" onclick="showAddUserForm()">
                            <i class='bx bx-plus'></i> Add New User
                        </button>
                    </div>
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <form method="GET" style="display:inline-flex; align-items:center; flex-wrap:wrap;">
                                <div class="table-search">
                                    <i class='bx bx-search'></i>
                                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <select name="role" class="form-control" style="width:auto;min-width:140px;" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="cashier" <?php echo $role_filter === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                </select>
                                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                                <button type="submit" class="btn btn-outline btn-sm"><i class='bx bx-search'></i> Search</button>
                            </form>
                        </div>
                        <div class="table-toolbar-right">
                            <div class="per-page-select">
                                <span>Show</span>
                                <form method="GET">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                                    <select name="per_page" onchange="this.form.submit()">
                                        <?php foreach ([10, 15, 25, 50] as $pp): ?>
                                            <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <span>entries</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <?php 
                                            $role_class = $user['role'] ?? 'cashier';
                                            $role_label = ucfirst($user['role'] ?? 'Cashier');
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($user['user_id']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="role-badge <?php echo $role_class; ?>">
                                                        <?php echo $role_label; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $user['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $user['status'] === 'active' ? '🟢 Active' : '🔴 Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <div class="action-dropdown">
                                                        <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                                                        <div class="action-dropdown-menu">
                                                            <button class="action-edit" onclick="editUser(<?php echo (int)$user['id']; ?>)"><i class='bx bx-edit'></i> Edit User</button>
                                                            <div class="action-dropdown-divider"></div>
                                                            <form method="POST">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="record_id" value="<?php echo (int)$user['id']; ?>">
                                                                <?php if ($user['status'] === 'active'): ?>
                                                                    <input type="hidden" name="new_status" value="inactive">
                                                                    <button type="submit" name="toggle_status" class="action-delete" onclick="return confirm('Deactivate this user? They will not be able to log in.')">
                                                                        <i class='bx bx-x-circle'></i> Deactivate
                                                                    </button>
                                                                <?php else: ?>
                                                                    <input type="hidden" name="new_status" value="active">
                                                                    <button type="submit" name="toggle_status" class="action-edit" onclick="return confirm('Activate this user? They will be able to log in again.')">
                                                                        <i class='bx bx-check-circle'></i> Activate
                                                                    </button>
                                                                <?php endif; ?>
                                                            </form>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <div class="action-dropdown-divider"></div>
                                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                                    <?= csrfField() ?>
                                                                    <input type="hidden" name="record_id" value="<?php echo (int)$user['id']; ?>">
                                                                    <button type="submit" name="delete_user" class="action-delete"><i class='bx bx-trash'></i> Delete User</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">Showing <?php echo $showing_start; ?>-<?php echo $showing_end; ?> of <?php echo $total_records; ?> users</div>
                            <div class="pagination-controls">
                                <?php
                                $query_params = [];
                                if ($search !== '') $query_params['search'] = $search;
                                if ($role_filter !== '') $query_params['role'] = $role_filter;
                                if ($per_page !== 15) $query_params['per_page'] = $per_page;
                                ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => 1])); ?>" class="pagination-btn"><i class='bx bx-chevrons-left'></i></a>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page - 1])); ?>" class="pagination-btn"><i class='bx bx-chevron-left'></i></a>
                                <?php endif; ?>
                                <?php
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages, $page + 2);
                                for ($p = $start_p; $p <= $end_p; $p++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $p])); ?>" class="pagination-btn <?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page + 1])); ?>" class="pagination-btn"><i class='bx bx-chevron-right'></i></a>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $total_pages])); ?>" class="pagination-btn"><i class='bx bx-chevrons-right'></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">Showing <?php echo $showing_start; ?>-<?php echo $showing_end; ?> of <?php echo $total_records; ?> users</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal with Permissions -->
    <div class="modal" id="user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="user-modal-title">Add New User</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('user-modal')"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="user-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="record_id" id="record_id">
                    <input type="hidden" name="form_action" id="form_action" value="add_user">

                    <div class="user-id-preview" id="user-id-preview" style="display: none;">
                        <label>User ID (Auto-generated):</label><br>
                        <span id="preview-user-id"></span>
                    </div>

                    <!-- Name Fields -->
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="user_first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="user_middle_name" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="user_last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Suffix (Jr., Sr., III, etc.)</label>
                        <input type="text" class="form-control" id="user_suffix" name="suffix" placeholder="e.g., Jr., Sr., III">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" id="user_email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="user_password" name="password">
                        <small class="text-muted" id="password-help">Required for new users. Leave blank when updating to keep current password.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select class="form-control" id="user_role" name="role" required onchange="updatePermissionsForRole(this.value)">
                                <option value="cashier">Cashier</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="user_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Permissions Section -->
                    <div class="form-group" style="margin-top:1rem;border-top:1px solid var(--border);padding-top:1rem;">
                        <label class="form-label" style="font-size:0.95rem;font-weight:700;">Permissions</label>
                        <small class="text-muted" style="display:block;margin-bottom:0.5rem;">Customize what this user can access</small>
                        <div class="permissions-grid" id="permissions-grid">
                            <?php 
                            $available_perms = getAvailablePermissions();
                            $grouped_perms = [];
                            foreach ($available_perms as $key => $perm) {
                                $group = explode('_', $key)[0];
                                if (!isset($grouped_perms[$group])) {
                                    $grouped_perms[$group] = [];
                                }
                                $grouped_perms[$group][$key] = $perm;
                            }
                            
                            foreach ($grouped_perms as $group => $perms): 
                            ?>
                                <div class="permission-group">
                                    <div class="permission-group-title"><?php echo ucfirst($group); ?></div>
                                    <?php foreach ($perms as $key => $perm): ?>
                                        <label class="permission-item" data-perm="<?php echo $key; ?>">
                                            <input type="checkbox" name="permissions[<?php echo $key; ?>]" value="1" id="perm_<?php echo $key; ?>" <?php echo in_array($key, ['pos_access', 'inventory_view', 'transactions_view']) ? 'checked' : ''; ?>>
                                            <span>
                                                <span class="perm-label"><?php echo $perm['label']; ?></span>
                                                <span class="perm-desc"><?php echo $perm['description']; ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('user-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="submit_user" id="user-submit-btn">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // PERMISSION MANAGEMENT
        // ============================================

        // Default permissions for each role (matching auth.php)
        const rolePermissions = {
            admin: {
                'dashboard_view': true,
                'pos_access': true,
                'products_view': true,
                'products_manage': true,
                'inventory_view': true,
                'inventory_manage': true,
                'inventory_stock_in': true,
                'inventory_stock_out': true,
                'transactions_view': true,
                'transactions_void': true,
                'reports_view': true,
                'reports_export': true,
                'users_view': true,
                'users_manage': true,
                'users_permissions': true,
                'archive_view': true,
                'archive_restore': true,
                'archive_delete': true,
                'branch_view': true,
                'branch_manage': true,
                'staff_view': true,
                'staff_manage': true,
                'settings_view': true,
                'settings_manage': true,
            },
            manager: {
                'dashboard_view': true,
                'pos_access': true,
                'products_view': true,
                'products_manage': true,
                'inventory_view': true,
                'inventory_manage': true,
                'inventory_stock_in': true,
                'inventory_stock_out': true,
                'transactions_view': true,
                'transactions_void': false,
                'reports_view': true,
                'reports_export': true,
                'users_view': true,
                'users_manage': false,
                'users_permissions': false,
                'archive_view': true,
                'archive_restore': false,
                'archive_delete': false,
                'staff_view': true,
                'staff_manage': true,
            },
            cashier: {
                'pos_access': true,
                'inventory_view': true,
                'transactions_view': true,
            }
        };

        function updatePermissionsForRole(role) {
            const permissions = rolePermissions[role] || {};
            const checkboxes = document.querySelectorAll('.permission-item input[type="checkbox"]');
            
            checkboxes.forEach(cb => {
                const permKey = cb.name.replace('permissions[', '').replace(']', '');
                if (permissions[permKey] !== undefined) {
                    cb.checked = permissions[permKey];
                    cb.disabled = false;
                    cb.closest('.permission-item').classList.remove('disabled');
                } else {
                    cb.checked = false;
                    cb.disabled = false;
                    cb.closest('.permission-item').classList.remove('disabled');
                }
            });
        }

        function setPermissionsFromData(permissionsData) {
            const checkboxes = document.querySelectorAll('.permission-item input[type="checkbox"]');
            checkboxes.forEach(cb => {
                const permKey = cb.name.replace('permissions[', '').replace(']', '');
                if (permissionsData && permissionsData[permKey] !== undefined) {
                    cb.checked = permissionsData[permKey] === true || permissionsData[permKey] === '1';
                }
            });
        }

        // ============================================
        // MODAL FUNCTIONS
        // ============================================

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showAddUserForm() {
            document.getElementById('user-modal-title').textContent = 'Add New User';
            document.getElementById('user-form').reset();
            document.getElementById('record_id').value = '';
            document.getElementById('form_action').value = 'add_user';
            document.getElementById('user_password').required = true;
            document.getElementById('password-help').textContent = 'Required for new users.';
            document.getElementById('user-submit-btn').textContent = 'Add User';
            document.getElementById('user-submit-btn').disabled = false;
            document.getElementById('user-id-preview').style.display = 'block';
            document.getElementById('preview-user-id').textContent = 'Auto-generated (numeric)';
            
            // Set default permissions for cashier
            document.getElementById('user_role').value = 'cashier';
            updatePermissionsForRole('cashier');
            
            document.getElementById('user-modal').style.display = 'flex';
        }

        function editUser(id) {
            document.getElementById('user-modal-title').textContent = 'Edit User';
            document.getElementById('user-form').reset();
            document.getElementById('record_id').value = id;
            document.getElementById('form_action').value = 'update_user';
            document.getElementById('user_password').required = false;
            document.getElementById('password-help').textContent = 'Leave blank to keep current password';
            document.getElementById('user-submit-btn').textContent = 'Update User';
            document.getElementById('user-submit-btn').disabled = true;
            document.getElementById('user-id-preview').style.display = 'none';
            document.getElementById('user-modal').style.display = 'flex';

            fetch(`users.php?action=get_user&id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch user data');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success && result.data) {
                        const user = result.data;
                        document.getElementById('user_first_name').value = user.first_name || '';
                        document.getElementById('user_middle_name').value = user.middle_name || '';
                        document.getElementById('user_last_name').value = user.last_name || '';
                        document.getElementById('user_suffix').value = user.suffix || '';
                        document.getElementById('user_email').value = user.email || '';
                        document.getElementById('user_role').value = user.role || 'cashier';
                        document.getElementById('user_status').value = user.status || 'active';
                        document.getElementById('user_password').value = '';
                        
                        // Set permissions from user data
                        const permissions = result.permissions || {};
                        setPermissionsFromData(permissions);
                        
                        // Disable permissions if user is admin (they have all)
                        if (user.role === 'admin') {
                            document.querySelectorAll('.permission-item input[type="checkbox"]').forEach(cb => {
                                cb.checked = true;
                            });
                        }
                        
                        document.getElementById('user-submit-btn').disabled = false;
                    } else {
                        alert('Error loading user data: ' + (result.error || 'Unknown error'));
                        closeModal('user-modal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user data. Please try again.');
                    closeModal('user-modal');
                });
        }

        // Close modal on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Initialize permissions on page load
        document.addEventListener('DOMContentLoaded', function() {
            // When role changes, update permissions
            const roleSelect = document.getElementById('user_role');
            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    updatePermissionsForRole(this.value);
                });
            }
        });
    </script>
</body>

</html>