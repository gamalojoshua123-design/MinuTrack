<?php
require_once __DIR__ . '/../bootstrap.php';
requirePermission('users_manage');

$active_tab = 'manager_users';
$page_title = 'User Management';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

$manager_branch_id = getCurrentBranchId();

if ($manager_branch_id === null) {
    header('Location: ../auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
    exit;
}

// AJAX: Get single user (only if in manager's branch)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    header('Content-Type: application/json');

    try {
        if (empty($_GET['id'])) {
            throw new Exception('User ID is required');
        }

        $stmt = $pdo->prepare("
            SELECT id, user_id, role, branch_id, full_name, email, status, first_name, middle_name, last_name, suffix, permissions
            FROM users
            WHERE id = ? AND branch_id = ? AND role = 'cashier'
            LIMIT 1
        ");
        $stmt->execute([intval($_GET['id']), $manager_branch_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

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
            $email = trim($_POST['email'] ?? '');
            $status = $_POST['status'] ?? 'active';

            $role = 'cashier';
            $branch_id = $manager_branch_id;

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

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $permissions = json_encode(getDefaultPermissions('cashier'));
            $cashierRoleId = $pdo->query("SELECT id FROM roles WHERE role_name = 'cashier' LIMIT 1")->fetchColumn();

            $insertSql = "INSERT INTO users (user_id, password, role, role_id, branch_id, full_name, first_name, middle_name, last_name, suffix, email, permissions, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertParams = function($uid) use ($hashedPassword, $role, $cashierRoleId, $branch_id, $full_name, $first_name, $middle_name, $last_name, $suffix, $email, $permissions, $status) {
                return [$uid, $hashedPassword, $role, $cashierRoleId ? (int)$cashierRoleId : null, $branch_id, $full_name, $first_name, $middle_name, $last_name, $suffix, $email, $permissions, $status];
            };

            $user_id = getNextUserId($pdo);
            try {
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute($insertParams($user_id));
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $user_id = getNextUserId($pdo);
                    $stmt = $pdo->prepare($insertSql);
                    $stmt->execute($insertParams($user_id));
                } else {
                    throw $e;
                }
            }

            auditLog('user_create', 'users', 'user', $user_id, 'success', 'Manager created cashier user');
            header('Location: manager_users.php?message=' . urlencode('User added successfully! User ID: ' . $user_id) . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
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
            $email = trim($_POST['email'] ?? '');
            $status = $_POST['status'] ?? 'active';

            $role = 'cashier';

            // Verify user belongs to this branch and is a cashier
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND branch_id = ? AND role = 'cashier'");
            $check->execute([$id, $manager_branch_id]);
            if (!$check->fetch()) {
                throw new Exception('User not found or access denied');
            }

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
                    SET password = ?, full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, status = ?
                    WHERE id = ? AND branch_id = ?
                ");
                $stmt->execute([
                    $hashedPassword,
                    $full_name,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $suffix,
                    $email,
                    $status,
                    $id,
                    $manager_branch_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, status = ?
                    WHERE id = ? AND branch_id = ?
                ");
                $stmt->execute([
                    $full_name,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $suffix,
                    $email,
                    $status,
                    $id,
                    $manager_branch_id
                ]);
            }

            auditLog('user_update', 'users', 'user', $id, 'success', 'Manager updated cashier user');
            header('Location: manager_users.php?message=' . urlencode('User updated successfully!') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if (isset($_POST['toggle_status'])) {
        try {
            $id = intval($_POST['record_id']);
            $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

            // Verify user belongs to this branch and is a cashier
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND branch_id = ? AND role = 'cashier'");
            $check->execute([$id, $manager_branch_id]);
            if (!$check->fetch()) {
                throw new Exception('User not found or access denied');
            }

            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND branch_id = ?");
            $stmt->execute([$newStatus, $id, $manager_branch_id]);
            $label = $newStatus === 'active' ? 'activated' : 'deactivated';
            auditLog('user_status_toggle', 'users', 'user', $id, 'success', 'User ' . $label);
            header('Location: manager_users.php?message=' . urlencode("User $label successfully!") . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        } catch (Exception $e) {
            header('Location: manager_users.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
}

// Stats counts (branch-scoped)
$stats_total = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ?");
$stats_total->execute([$manager_branch_id]);
$stats_total = $stats_total->fetchColumn();

$stats_cashiers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ? AND role = 'cashier'");
$stats_cashiers->execute([$manager_branch_id]);
$stats_cashiers = $stats_cashiers->fetchColumn();

$stats_active = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ? AND role = 'cashier' AND status = 'active'");
$stats_active->execute([$manager_branch_id]);
$stats_active = $stats_active->fetchColumn();

// Search & filter params
$search = trim($_GET['search'] ?? '');
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [10, 15, 25, 50])) $per_page = 15;
$page = max(1, intval($_GET['page'] ?? 1));

// Build WHERE clause
$where_clauses = ["branch_id = ?", "role = 'cashier'"];
$params = [$manager_branch_id];

if ($search !== '') {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ? OR user_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

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
    <title>Users - Minute Burger Manager</title>
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
            max-width: 520px;
        }

        .role-badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .role-badge.cashier { background: #d1fae5; color: #047857; }

        @media (max-width: 768px) {
            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
            .modal-content {
                max-width: 100%;
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
                                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                                <button type="submit" class="btn btn-outline btn-sm"><i class='bx bx-search'></i> Search</button>
                            </form>
                        </div>
                        <div class="table-toolbar-right">
                            <div class="per-page-select">
                                <span>Show</span>
                                <form method="GET">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
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
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($user['user_id']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php $role_badge = $user['role'] ?? 'cashier'; ?>
                                                    <span class="role-badge <?php echo htmlspecialchars($role_badge, ENT_QUOTES); ?>">
                                                        <?php echo getRoleLabel($role_badge); ?>
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
                                                            <form method="POST" onsubmit="return askConfirm(event, '<?= $user['status'] === 'active' ? 'Deactivate this user? They will not be able to log in.' : 'Activate this user? They will be able to log in again.' ?>')">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="record_id" value="<?php echo (int)$user['id']; ?>">
                                                                <?php if ($user['status'] === 'active'): ?>
                                                                    <input type="hidden" name="new_status" value="inactive">
                                                                    <button type="submit" name="toggle_status" class="action-delete">
                                                                        <i class='bx bx-x-circle'></i> Deactivate
                                                                    </button>
                                                                <?php else: ?>
                                                                    <input type="hidden" name="new_status" value="active">
                                                                    <button type="submit" name="toggle_status" class="action-edit">
                                                                        <i class='bx bx-check-circle'></i> Activate
                                                                    </button>
                                                                <?php endif; ?>
                                                            </form>
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

    <!-- User Modal -->
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
                            <select class="form-control" id="user_role" name="role" required disabled>
                                <option value="cashier" selected>Cashier</option>
                            </select>
                            <small class="text-muted">Only Cashier accounts can be created.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="user_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
            document.getElementById('user_role').value = 'cashier';
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

            fetch(`manager_users.php?action=get_user&id=${id}`)
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
                        document.getElementById('user_role').value = 'cashier';
                        document.getElementById('user_status').value = user.status || 'active';
                        document.getElementById('user_password').value = '';
                        document.getElementById('user-submit-btn').disabled = false;
                    } else {
                        showToastMsg('Error loading user data: ' + (result.error || 'Unknown error'), 'error');
                        closeModal('user-modal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToastMsg('Error loading user data. Please try again.', 'error');
                    closeModal('user-modal');
                });
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>

</html>
