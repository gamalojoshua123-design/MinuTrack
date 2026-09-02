<?php
require_once __DIR__ . '/bootstrap.php';
requireOwner();

$active_tab = 'users';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) && in_array($_GET['type'], ['success', 'error'], true) ? $_GET['type'] : '';

// Assignable roles (Owner can assign any; branch_owner is not assignable)
$roles = $pdo->query("SELECT id, role_name, description FROM roles WHERE role_name != 'branch_owner' ORDER BY FIELD(role_name, 'admin','manager','cashier')")->fetchAll();
$role_id_by_name = [];
foreach ($roles as $r) {
    $role_id_by_name[$r['role_name']] = (int)$r['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (!checkRateLimit('admin_users')) {
        header('Location: users.php?message=' . urlencode('Too many requests. Please wait a minute.') . '&type=error');
        exit;
    }
    if (isset($_POST['add_user'])) {
        try {
            $role = $_POST['role'];
            if (!canAssignRole($role)) {
                throw new Exception('You are not allowed to assign this role');
            }
            $role_id = $role_id_by_name[$role] ?? null;
            $raw_password = $_POST['password'];
            if (strlen($raw_password) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            $password = password_hash($raw_password, PASSWORD_DEFAULT);
            $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;

            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '');

            if ($first_name === '') {
                throw new Exception('First name is required');
            }
            if ($last_name === '') {
                throw new Exception('Last name is required');
            }

            $full_name_parts = [$first_name];
            if (!empty($middle_name)) $full_name_parts[] = $middle_name;
            $full_name_parts[] = $last_name;
            if (!empty($suffix)) $full_name_parts[] = $suffix;
            $full_name = implode(' ', $full_name_parts);

            $status = $_POST['status'];
            $lastActivity = $status === 'inactive' ? null : date('Y-m-d H:i:s');
            $permissions = json_encode(buildPermissionsMap($role, $perm_catalog));
            $stmt = $pdo->prepare("INSERT INTO users (user_id, password, role, role_id, branch_id, full_name, first_name, middle_name, last_name, suffix, email, permissions, status, last_activity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['username'],
                $password,
                $role,
                $role_id,
                $branch_id,
                $full_name,
                $first_name,
                $middle_name,
                $last_name,
                $suffix,
                $_POST['email'],
                $permissions,
                $status,
                $lastActivity
            ]);
            auditLog('user_create', 'users', 'user', $_POST['username'], 'success', 'Created user with role ' . $role);
            header('Location: users.php?message=' . urlencode('User added successfully!') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if (isset($_POST['update_user'])) {
        try {
            $role = $_POST['role'];
            if (!canAssignRole($role)) {
                throw new Exception('You are not allowed to assign this role');
            }
            $userId = (int)$_POST['user_id'];
            $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;

            // Load the target user for privilege-escalation checks
            $targetStmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
            $targetStmt->execute([$userId]);
            $target = $targetStmt->fetch();
            if (!$target) {
                throw new Exception('User not found');
            }

            // Cannot change your own role away from System Owner
            if ($userId === (int)$_SESSION['user_id'] && $role !== 'admin' && $target['role'] === 'admin') {
                throw new Exception('You cannot change your own System Owner role');
            }

            // Cannot demote the last System Owner
            if ($target['role'] === 'admin' && $role !== 'admin') {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new Exception('You cannot remove the last System Owner');
                }
            }

            $role_id = $role_id_by_name[$role] ?? null;
            $status = $_POST['status'];
            $lastActivity = $status === 'inactive' ? null : date('Y-m-d H:i:s');
            $permissions = json_encode(buildPermissionsMap($role, $perm_catalog));

            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '');

            if ($first_name === '') {
                throw new Exception('First name is required');
            }
            if ($last_name === '') {
                throw new Exception('Last name is required');
            }

            $full_name_parts = [$first_name];
            if (!empty($middle_name)) $full_name_parts[] = $middle_name;
            $full_name_parts[] = $last_name;
            if (!empty($suffix)) $full_name_parts[] = $suffix;
            $full_name = implode(' ', $full_name_parts);

            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                if (strlen($password) < 6) {
                    throw new Exception('Password must be at least 6 characters long');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET user_id = ?, password = ?, role = ?, role_id = ?, branch_id = ?, full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, permissions = ?, status = ?, last_activity = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['username'], $hashedPassword, $role, $role_id, $branch_id,
                    $full_name, $first_name, $middle_name, $last_name, $suffix,
                    $_POST['email'], $permissions, $status, $lastActivity, $userId
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET user_id = ?, role = ?, role_id = ?, branch_id = ?, full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, permissions = ?, status = ?, last_activity = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['username'], $role, $role_id, $branch_id,
                    $full_name, $first_name, $middle_name, $last_name, $suffix,
                    $_POST['email'], $permissions, $status, $lastActivity, $userId
                ]);
            }
            auditLog('user_update', 'users', 'user', $userId, 'success', 'Updated user, role = ' . $role);
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
            $userId = (int)$_POST['user_id'];

            // Never delete yourself
            if ($userId === (int)$_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account');
            }

            // Never delete the last System Owner
            $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $targetStmt->execute([$userId]);
            $targetRole = $targetStmt->fetchColumn();
            if ($targetRole === 'admin') {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new Exception('You cannot delete the last System Owner');
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$userId]);
            auditLog('user_delete', 'users', 'user', $userId, 'success', 'Deleted user (soft delete)');
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
            $id = (int)$_POST['user_id'];
            $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

            // Prevent deactivating the last active System Owner
            if ($newStatus === 'inactive') {
                $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $targetStmt->execute([$id]);
                $targetRole = $targetStmt->fetchColumn();
                if ($targetRole === 'admin') {
                    $activeAdminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
                    if ($activeAdminCount <= 1) {
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

// Search & filter params
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [10, 15, 25, 50])) $per_page = 15;
$page = max(1, intval($_GET['page'] ?? 1));

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '' && in_array($role_filter, ['admin', 'manager', 'cashier'], true)) {
    $where_clauses[] = "u.role = ?";
    $params[] = $role_filter;
}

$where_sql = empty($where_clauses) ? '' : ('WHERE ' . implode(' AND ', $where_clauses));

// Count total matching
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where_sql");
$count_stmt->execute($params);
$total_records = (int) $count_stmt->fetchColumn();
$total_pages = (int) max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch users with branch info
$stmt = $pdo->prepare("
    SELECT u.*, b.branch_name 
    FROM users u 
    LEFT JOIN branches b ON u.branch_id = b.id 
    $where_sql
    ORDER BY u.role, u.user_id
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$showing_start = $total_records > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $per_page, $total_records);

// Fetch branches for dropdown
$branches = $pdo->query("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name")->fetchAll();

// Permission catalog grouped by category (single source of truth = rbac catalog)
$perm_catalog = getSystemPermissions();
$perm_groups = [];
foreach ($perm_catalog as $perm => $meta) {
    $perm_groups[$meta['category']][$perm] = $meta;
}
// Default granted permission names per role, for JS pre-population
$role_default_perms = [];
foreach ($roles as $r) {
    $role_default_perms[$r['role_name']] = getDefaultPermissions($r['role_name']);
}

// Merge role defaults with submitted per-user permission checkboxes into a full bool-map
function buildPermissionsMap($role, $catalog) {
    $map = getDefaultPermissions($role);
    if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
        foreach ($catalog as $name => $_) {
            $map[$name] = isset($_POST['permissions'][$name]) && ($_POST['permissions'][$name] === '1' || $_POST['permissions'][$name] === 1 || $_POST['permissions'][$name] === true);
        }
    }
    return $map;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: var(--bg-card); border-radius: var(--radius-lg); width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-xl); }
        .modal-header { padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .modal-header h3 { font-size: 1.1rem; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted); padding: 4px; border-radius: 6px; }
        .modal-close:hover { background: var(--bg); color: var(--text-primary); }
        .modal-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-primary); }
        .form-control { width: 100%; padding: 0.65rem 0.85rem; border: 1.5px solid var(--border); border-radius: 8px; font-size: 0.9rem; font-family: inherit; transition: var(--transition); box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(243,121,2,0.08); }
        .form-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); }
        .text-muted { color: var(--text-muted); font-size: 0.78rem; }
        .btn { padding: 0.55rem 1.1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: inherit; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.78rem; }
        .btn-edit { background: var(--blue-light); color: var(--blue); }
        .btn-edit:hover { background: #dbeafe; }
        .btn-delete { background: var(--red-light); color: var(--red); }
        .btn-delete:hover { background: #fee2e2; }
        .message { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 500; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .message.success { background: var(--green-light); color: #065f46; border: 1px solid #a7f3d0; }
        .message.error { background: var(--red-light); color: #991b1b; border: 1px solid #fecaca; }
        .role-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.72rem; font-weight: 600; }
        .role-badge.admin { background: #fef3c7; color: #92400e; }
        .role-badge.manager { background: #dbeafe; color: #1e40af; }
        .role-badge.cashier { background: #d1fae5; color: #047857; }
        .branch-label { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-user'></i> User Management</h3>
                        <button class="btn btn-primary" onclick="showAddUserForm()">
                            <i class='bx bx-plus'></i> Add New User
                        </button>
                    </div>
                    <div class="table-toolbar" style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;border-bottom:1px solid var(--border);">
                        <form method="GET" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <input type="text" name="search" class="form-control" style="width:240px;" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="role" class="form-control" style="width:auto;min-width:140px;">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>><?php echo getRoleLabel('admin'); ?></option>
                                <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>><?php echo getRoleLabel('manager'); ?></option>
                                <option value="cashier" <?php echo $role_filter === 'cashier' ? 'selected' : ''; ?>><?php echo getRoleLabel('cashier'); ?></option>
                            </select>
                            <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                            <button type="submit" class="btn btn-outline btn-sm"><i class='bx bx-search'></i> Search</button>
                        </form>
                        <form method="GET" style="display:inline-flex;align-items:center;gap:6px;">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                            <span style="font-size:0.8rem;color:var(--text-muted);">Show</span>
                            <select name="per_page" class="form-control" style="width:auto;" onchange="this.form.submit()">
                                <?php foreach ([10, 15, 25, 50] as $pp): ?>
                                    <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span style="font-size:0.8rem;color:var(--text-muted);">entries</span>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="8" class="text-center text-muted">No users found</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($user['user_id']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="role-badge <?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>">
                                                        <?php echo getRoleLabel($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['branch_id']): ?>
                                                        <span><?php echo htmlspecialchars($user['branch_name'] ?? 'Branch #' . $user['branch_id']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">All Branches</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $user['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $user['status'] === 'active' ? '🟢 Active' : '🔴 Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-edit btn-sm" onclick='editUser(<?php echo htmlspecialchars(json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES); ?>)'>
                                                        <i class='bx bx-edit'></i> Edit
                                                    </button>
                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <form method="POST" style="display: inline;"
                                                              onsubmit="return askConfirm(event, 'Deactivate this user? They will not be able to log in.')">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="new_status" value="inactive">
                                                            <?php echo csrfField(); ?>
                                                            <button type="submit" class="btn btn-delete btn-sm" name="toggle_status">
                                                                <i class='bx bx-x-circle'></i> Deactivate
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;"
                                                              onsubmit="return askConfirm(event, 'Activate this user? They will be able to log in again.')">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="new_status" value="active">
                                                            <?php echo csrfField(); ?>
                                                            <button type="submit" class="btn btn-edit btn-sm" name="toggle_status">
                                                                <i class='bx bx-check-circle'></i> Activate
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" style="display: inline;"
                                                              onsubmit="return askConfirm(event, 'Are you sure you want to delete this user?')">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <?php echo csrfField(); ?>
                                                            <button type="submit" class="btn btn-delete btn-sm" name="delete_user">
                                                                <i class='bx bx-trash'></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-bar" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem;flex-wrap:wrap;">
                            <div class="pagination-info" style="font-size:0.85rem;color:var(--text-muted);">Showing <?php echo $showing_start; ?>-<?php echo $showing_end; ?> of <?php echo $total_records; ?> users</div>
                            <div class="pagination-controls" style="display:flex;gap:4px;">
                                <?php
                                $query_params = [];
                                if ($search !== '') $query_params['search'] = $search;
                                if ($role_filter !== '') $query_params['role'] = $role_filter;
                                if ($per_page !== 15) $query_params['per_page'] = $per_page;
                                ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => 1])); ?>" class="pagination-btn" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;color:var(--text-muted);text-decoration:none;"><i class='bx bx-chevrons-left'></i></a>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page - 1])); ?>" class="pagination-btn" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;color:var(--text-muted);text-decoration:none;"><i class='bx bx-chevron-left'></i></a>
                                <?php endif; ?>
                                <?php
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages, $page + 2);
                                for ($p = $start_p; $p <= $end_p; $p++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $p])); ?>" class="pagination-btn" style="padding:6px 10px;border:1px solid <?php echo $p === $page ? 'var(--primary)' : 'var(--border)'; ?>;border-radius:6px;color:<?php echo $p === $page ? 'var(--primary)' : 'var(--text-muted)'; ?>;font-weight:<?php echo $p === $page ? '600' : '400'; ?>;text-decoration:none;"><?php echo $p; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page + 1])); ?>" class="pagination-btn" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;color:var(--text-muted);text-decoration:none;"><i class='bx bx-chevron-right'></i></a>
                                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $total_pages])); ?>" class="pagination-btn" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;color:var(--text-muted);text-decoration:none;"><i class='bx bx-chevrons-right'></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal" id="user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="user-modal-title">Add New User</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal()"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="user-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_id" id="form_user_id">

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" id="form_username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" id="form_password" name="password">
                        <small class="text-muted">Leave blank to keep current password when editing</small>
                    </div>

                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" class="form-control" id="form_first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" class="form-control" id="form_middle_name" name="middle_name">
                    </div>

                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" class="form-control" id="form_last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label>Suffix</label>
                        <input type="text" class="form-control" id="form_suffix" name="suffix" placeholder="e.g., Jr., Sr., III">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" id="form_email" name="email">
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" id="form_role" name="role" required>
                            <option value="admin"><?php echo getRoleLabel('admin'); ?></option>
                            <option value="manager"><?php echo getRoleLabel('manager'); ?></option>
                            <option value="cashier"><?php echo getRoleLabel('cashier'); ?></option>
                        </select>
                    </div>

                    <div class="form-group" id="branch_group">
                        <label>Branch</label>
                        <select class="form-control" id="form_branch" name="branch_id">
                            <option value="">-- All Branches (Owner) --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Managers and Cashiers must be assigned to a branch</small>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="form_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top:1.25rem;border-top:1px solid var(--border);padding-top:1rem;">
                        <label style="font-size:0.9rem;font-weight:700;">Permissions</label>
                        <small class="text-muted" style="display:block;margin-bottom:0.5rem;">Customize what this user can access. Unchecked boxes keep the role default; check extra ones to grant more.</small>
                        <div style="max-height:320px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:0.75rem;">
                            <?php foreach ($perm_groups as $category => $perms): ?>
                                <div style="margin-bottom:0.75rem;">
                                    <div style="font-weight:600;font-size:0.78rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.25rem;"><?php echo htmlspecialchars($category); ?></div>
                                    <?php foreach ($perms as $perm => $meta): ?>
                                        <label class="perm-item" data-perm="<?php echo htmlspecialchars($perm); ?>" style="display:flex;align-items:flex-start;gap:6px;padding:2px 0;cursor:pointer;font-size:0.82rem;">
                                            <input type="checkbox" name="permissions[<?php echo htmlspecialchars($perm); ?>]" value="1" class="perm-check" style="margin-top:3px;accent-color:var(--primary);">
                                            <span>
                                                <span style="font-weight:500;display:block;"><?php echo htmlspecialchars($meta['label']); ?></span>
                                                <span style="font-size:0.7rem;color:var(--text-muted);display:block;"><?php echo htmlspecialchars($meta['description']); ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_user" id="form_submit_btn">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const branches = <?php echo json_encode($branches); ?>;
        const roleDefaults = <?php echo json_encode($role_default_perms); ?>;

        function closeModal() {
            document.getElementById('user-modal').classList.remove('show');
        }

        // Apply permission checkboxes for a role, optionally overlaying a user's
        // saved per-user permission map (a JSON bool-map from users.permissions).
        function applyPermissions(role, rawPerms) {
            if (role === 'admin') {
                document.querySelectorAll('.perm-check').forEach(cb => cb.checked = true);
                return;
            }
            const defaults = roleDefaults[role] || {};
            let saved = {};
            if (rawPerms && typeof rawPerms === 'string') {
                try { saved = JSON.parse(rawPerms); } catch (e) { saved = {}; }
            }
            const hasSaved = Object.keys(saved).length > 0;
            document.querySelectorAll('.perm-check').forEach(cb => {
                const name = cb.name.replace('permissions[', '').replace(']', '');
                const val = hasSaved ? saved[name] : defaults[name];
                cb.checked = val === true || val === 1 || val === '1';
            });
        }

        function showAddUserForm() {
            document.getElementById('user-modal-title').textContent = 'Add New User';
            document.getElementById('user-form').reset();
            document.getElementById('form_user_id').value = '';
            document.getElementById('form_password').required = true;
            document.getElementById('form_submit_btn').name = 'add_user';
            document.getElementById('form_submit_btn').textContent = 'Add User';
            document.getElementById('user-modal').classList.add('show');
            handleRoleChange();
        }

        function editUser(user) {
            document.getElementById('user-modal-title').textContent = 'Edit User';
            document.getElementById('form_user_id').value = user.id;
            document.getElementById('form_username').value = user.user_id;
            document.getElementById('form_first_name').value = user.first_name || '';
            document.getElementById('form_middle_name').value = user.middle_name || '';
            document.getElementById('form_last_name').value = user.last_name || '';
            document.getElementById('form_suffix').value = user.suffix || '';
            document.getElementById('form_email').value = user.email || '';
            document.getElementById('form_role').value = user.role;
            document.getElementById('form_status').value = user.status;
            document.getElementById('form_password').required = false;
            document.getElementById('form_submit_btn').name = 'update_user';
            document.getElementById('form_submit_btn').textContent = 'Update User';

            // Set branch
            document.getElementById('form_branch').value = user.branch_id || '';
            handleRoleChange();
            // Apply the user's saved per-user permissions (overrides role defaults)
            applyPermissions(user.role, user.permissions);
            document.getElementById('user-modal').classList.add('show');
        }

        function handleRoleChange() {
            const role = document.getElementById('form_role').value;
            const branchGroup = document.getElementById('branch_group');
            const branchSelect = document.getElementById('form_branch');
            // Re-apply the newly selected role's default permissions
            applyPermissions(role, '');
            if (role === 'admin') {
                branchGroup.style.display = 'block';
                branchSelect.value = '';
            } else {
                branchGroup.style.display = 'block';
            }
        }

        document.getElementById('form_role').addEventListener('change', handleRoleChange);

        document.getElementById('user-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>
