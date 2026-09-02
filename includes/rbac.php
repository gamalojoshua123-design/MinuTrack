<?php
/**
 * RBAC (Role-Based Access Control) engine.
 *
 * Permission names are defined in includes/permission_catalog.php and stored in
 * the DB `permissions` table. Roles map to permissions via `role_permissions`
 * (see includes/role_permission_matrix.php for the defaults).
 *
 * The current user's granted permission names live in $_SESSION['permissions']
 * (flat array). hasPermission() checks against that list.
 */

require_once __DIR__ . '/permission_catalog.php';
require_once __DIR__ . '/role_permission_matrix.php';

/**
 * Full permission catalog (name => ['label','description','category']).
 */
function getSystemPermissions()
{
    return require __DIR__ . '/permission_catalog.php';
}

/**
 * Default role-permission matrix (role name => permission names[]).
 */
function getRolePermissionMatrix()
{
    return require __DIR__ . '/role_permission_matrix.php';
}

/**
 * Human-friendly label for a role name.
 */
function getRoleLabel($role)
{
    $labels = [
        'admin'           => 'System Owner',
        'manager'         => 'Admin',
        'cashier'         => 'Cashier',
        'branch_owner'    => 'Branch Owner',
    ];
    return $labels[$role] ?? ucwords(str_replace('_', ' ', (string)$role));
}

/**
 * Roles that can be assigned to a user by the current user.
 * System Owner can assign everything; everyone else can only create Cashiers.
 */
function getAssignableRoles()
{
    if (isOwner()) {
        return ['admin', 'manager', 'cashier'];
    }
    return ['cashier'];
}

/**
 * True if the currently logged-in user may assign $role to someone.
 */
function canAssignRole($role)
{
    return in_array($role, getAssignableRoles(), true);
}

/**
 * Permission names granted to a role, resolved from the database.
 * Returns [] if the role has no mapping.
 */
function getRolePermissionsFromDB($roleId)
{
    global $pdo;
    if (!$roleId) {
        return [];
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT p.name FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?
             ORDER BY p.name"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log('getRolePermissionsFromDB error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Compute the permission list for a user row and store it in the session.
 * Order of preference: DB role_permissions -> role matrix defaults.
 */
function loadUserPermissions($user)
{
    $role = $user['role'] ?? 'cashier';
    $roleId = $user['role_id'] ?? null;

    if ($role === 'admin') {
        $perms = array_keys(getSystemPermissions());
    } else {
        $perms = $roleId ? getRolePermissionsFromDB($roleId) : [];
        if (empty($perms)) {
            $matrix = getRolePermissionMatrix();
            $perms = $matrix[$role] ?? [];
        }
        // Honor per-user permission overrides stored in users.permissions (JSON bool map).
        // This makes the per-user permission checkboxes in the user editors take effect.
        $stored = $user['permissions'] ?? null;
        if (!empty($stored) && is_string($stored)) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                foreach ($decoded as $perm => $granted) {
                    if (is_string($perm) && ($granted === true || $granted === 1 || $granted === '1')) {
                        $perms[] = $perm;
                    }
                }
            }
        }
    }

    $_SESSION['permissions'] = array_values(array_unique($perms));
    return $_SESSION['permissions'];
}

/**
 * Normalise the session permission value to a flat array of granted names.
 * Supports both the flat list (RBAC) and the legacy bool-map format.
 */
function getSessionPermissionList()
{
    if (!isset($_SESSION['permissions'])) {
        return null;
    }
    $perms = $_SESSION['permissions'];
    if (is_string($perms)) {
        $perms = json_decode($perms, true);
    }
    if (!is_array($perms)) {
        return [];
    }
    $list = [];
    foreach ($perms as $key => $value) {
        if (is_int($key)) {
            $list[] = $value;
        } elseif ($value === true || $value === 1 || $value === '1') {
            $list[] = $key;
        }
    }
    return $list;
}

/**
 * True if the current user has the given permission.
 * The System Owner always has every permission.
 */
function hasPermission($permission)
{
    if (isOwner()) {
        return true;
    }
    $list = getSessionPermissionList();
    if ($list === null) {
        $role = $_SESSION['role'] ?? '';
        $matrix = getRolePermissionMatrix();
        $list = $matrix[$role] ?? [];
        $_SESSION['permissions'] = $list;
    }
    return in_array($permission, $list, true);
}

/**
 * True if the current user has ANY of the given permissions.
 */
function hasAnyPermission($permissions)
{
    if (empty($permissions)) {
        return false;
    }
    foreach ($permissions as $perm) {
        if (hasPermission($perm)) {
            return true;
        }
    }
    return false;
}

/**
 * Redirect to the unauthorized page (and record it in the audit log).
 */
function denyAccess($page = null)
{
    auditLog('unauthorized_access', 'auth', 'page', $page, 'denied');
    header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($page ?? ($_SERVER['PHP_SELF'] ?? '')));
    exit();
}

/**
 * Require the current user to hold $permission, otherwise deny.
 */
function requirePermission($permission)
{
    requireAuth();
    if (!hasPermission($permission)) {
        denyAccess($_SERVER['PHP_SELF'] ?? '');
    }
}

/**
 * Require the current user to hold at least one of $permissions.
 */
function requireAnyPermission($permissions)
{
    requireAuth();
    if (!hasAnyPermission($permissions)) {
        denyAccess($_SERVER['PHP_SELF'] ?? '');
    }
}

/**
 * Require the current user to be one of the given roles (Owner always passes).
 */
function requireRole(...$roles)
{
    requireAuth();
    if (isOwner()) {
        return;
    }
    $role = $_SESSION['role'] ?? null;
    if (!in_array($role, $roles, true)) {
        denyAccess($_SERVER['PHP_SELF'] ?? '');
    }
}

/**
 * Write an entry to the audit_logs table. Never throws.
 */
function auditLog($action, $category = null, $targetType = null, $targetId = null, $result = 'success', $detail = null)
{
    global $pdo;
    if (!$pdo) {
        return;
    }
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['login_user_id'] ?? ($_SESSION['full_name'] ?? null);
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, username, action, category, target_type, target_id, result, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $username,
            $action,
            $category,
            $targetType,
            $targetId !== null ? (string)$targetId : null,
            $result,
            $detail,
            $_SERVER['REMOTE_ADDR'] ?? null,
            isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);
    } catch (PDOException $e) {
        error_log('auditLog error: ' . $e->getMessage());
    }
}
