<?php
require_once __DIR__ . '/bootstrap.php';
requireOwner();
requirePermission('users_roles_manage');

$active_tab = 'roles';
$page_title = 'Roles & Permissions';

$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $roleName = $_POST['role_name'] ?? '';
    $permIds = isset($_POST['permissions']) && is_array($_POST['permissions'])
        ? array_values(array_map('intval', $_POST['permissions']))
        : [];

    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
    $roleStmt->execute([$roleName]);
    $roleId = $roleStmt->fetchColumn();

    if (!$roleId) {
        header('Location: roles.php?message=' . urlencode('Role not found') . '&type=error');
        exit;
    }

    // The System Owner role always keeps every permission
    if ($roleName === 'admin') {
        header('Location: roles.php?message=' . urlencode('The System Owner role always keeps all permissions.') . '&type=error');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);
        $ins = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permIds as $pid) {
            if ($pid > 0) {
                $ins->execute([$roleId, $pid]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    auditLog('roles_permissions_update', 'users', 'role', $roleName, 'success', 'Updated permissions for role ' . $roleName);
    header('Location: roles.php?message=' . urlencode('Permissions updated for ' . getRoleLabel($roleName) . '!') . '&type=success');
    exit;
}

// Roles (branch_owner is not assignable/manageable)
$roles = $pdo->query("SELECT id, role_name, description, is_system FROM roles WHERE role_name != 'branch_owner' ORDER BY FIELD(role_name, 'admin','manager','cashier')")->fetchAll();

// All permissions grouped by category
$perms = $pdo->query("SELECT id, name, label, description, category FROM permissions ORDER BY category, name")->fetchAll();
$grouped_perms = [];
foreach ($perms as $p) {
    $grouped_perms[$p['category']][] = $p;
}

// role -> set of permission ids
$rolePermMap = [];
foreach ($roles as $r) {
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$r['id']]);
    $rolePermMap[$r['role_name']] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles &amp; Permissions - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .roles-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1.25rem;
            align-items: start;
        }

        .role-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .role-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            background: var(--bg-card);
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .role-list-item:hover {
            border-color: var(--primary);
        }

        .role-list-item.active {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .role-list-item .role-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: #fff;
            flex-shrink: 0;
        }

        .role-list-item .role-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .role-list-item .role-count {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .perm-group {
            margin-bottom: 1.25rem;
        }

        .perm-group-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.6rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid var(--border);
        }

        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.5rem;
        }

        .perm-item {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            background: var(--bg-card);
        }

        .perm-item:hover {
            border-color: var(--primary);
            background: var(--bg);
        }

        .perm-item input[type="checkbox"] {
            margin-top: 2px;
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .perm-item .perm-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        .perm-item .perm-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: block;
        }

        .perm-item.checked {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .role-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.72rem; font-weight: 600; }
        .role-badge.admin { background: #fef3c7; color: #92400e; }
        .role-badge.manager { background: #dbeafe; color: #1e40af; }
        .role-badge.cashier { background: #d1fae5; color: #047857; }

        .select-all-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .select-all-bar label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .roles-layout { grid-template-columns: 1fr; }
        }
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

                <div class="roles-layout">
                    <!-- Role selector -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-lock-alt'></i> Roles</h3>
                        </div>
                        <div class="card-body">
                            <div class="role-list">
                                <?php foreach ($roles as $idx => $r): ?>
                                    <button type="button" class="role-list-item <?php echo $idx === 0 ? 'active' : ''; ?>"
                                            data-role="<?php echo $r['role_name']; ?>"
                                            onclick="selectRole('<?php echo $r['role_name']; ?>', this)">
                                        <span class="role-avatar" style="background: <?php
                                            echo ['admin' => '#92400e', 'manager' => '#1e40af', 'cashier' => '#047857'][$r['role_name']] ?? '#6b7280';
                                        ?>;"><?php echo strtoupper(substr($r['role_name'], 0, 2)); ?></span>
                                        <span>
                                            <span class="role-name"><?php echo getRoleLabel($r['role_name']); ?></span><br>
                                            <span class="role-count"><?php echo count($rolePermMap[$r['role_name']]); ?> permissions</span>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Permission editor -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class='bx bx-check-shield'></i>
                                <span id="editor-role-title">Permissions</span>
                                <span class="role-badge" id="editor-role-badge" style="margin-left:0.5rem;"></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="perm-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="role_name" id="role_name" value="<?php echo $roles[0]['role_name']; ?>">

                                <?php foreach ($roles as $r): $roleName = $r['role_name']; ?>
                                <div class="perm-panel" id="panel-<?php echo $roleName; ?>" <?php echo $roleName === $roles[0]['role_name'] ? '' : 'style="display:none;"'; ?>>
                                    <?php if ($roleName === 'admin'): ?>
                                        <div class="message success" style="margin-bottom:1rem;">
                                            The System Owner always has full access to every permission and cannot be changed.
                                        </div>
                                    <?php else: ?>
                                        <div class="select-all-bar">
                                            <label>
                                                <input type="checkbox" id="select-all-<?php echo $roleName; ?>" onchange="toggleSelectAll('<?php echo $roleName; ?>', this.checked)">
                                                Select all / none
                                            </label>
                                        </div>
                                    <?php endif; ?>

                                    <?php foreach ($grouped_perms as $category => $perms): ?>
                                        <div class="perm-group">
                                            <div class="perm-group-title"><?php echo htmlspecialchars($category); ?></div>
                                            <div class="perm-grid">
                                                <?php foreach ($perms as $p): $checked = in_array((int)$p['id'], $rolePermMap[$roleName] ?? [], true); ?>
                                                    <label class="perm-item <?php echo $checked ? 'checked' : ''; ?>"
                                                           data-checkbox-label="1">
                                                        <input type="checkbox" name="permissions[]" value="<?php echo (int)$p['id']; ?>"
                                                               class="perm-checkbox <?php echo $roleName; ?>-perm"
                                                               <?php echo $roleName === 'admin' ? 'checked disabled' : ($checked ? 'checked' : ''); ?>
                                                               onchange="this.closest('.perm-item').classList.toggle('checked', this.checked)">
                                                        <span>
                                                            <span class="perm-label"><?php echo htmlspecialchars($p['label']); ?></span>
                                                            <span class="perm-desc"><?php echo htmlspecialchars($p['description'] ?? ''); ?></span>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if ($roleName !== 'admin'): ?>
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class='bx bx-save'></i> Save Permissions
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php
        $roleLabels = [];
        foreach ($roles as $r) {
            $roleLabels[$r['role_name']] = getRoleLabel($r['role_name']);
        }
        ?>
        var ROLE_LABELS = <?php echo json_encode($roleLabels); ?>;

        function selectRole(roleName, el) {
            document.querySelectorAll('.role-list-item').forEach(function(item) { item.classList.remove('active'); });
            el.classList.add('active');
            document.getElementById('role_name').value = roleName;
            document.querySelectorAll('.perm-panel').forEach(function(panel) { panel.style.display = 'none'; });
            var panel = document.getElementById('panel-' + roleName);
            if (panel) panel.style.display = 'block';
            var badge = document.getElementById('editor-role-badge');
            badge.className = 'role-badge ' + roleName;
            badge.textContent = ROLE_LABELS[roleName] || roleName;
        }

        function toggleSelectAll(roleName, checked) {
            document.querySelectorAll('.perm-checkbox.' + roleName + '-perm').forEach(function(cb) {
                cb.checked = checked;
                cb.closest('.perm-item').classList.toggle('checked', checked);
            });
        }

        // Auto-dismiss messages
        setTimeout(function() {
            var msg = document.querySelector('.message');
            if (msg) msg.style.display = 'none';
        }, 5000);
    </script>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
</body>
</html>
