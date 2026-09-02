<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/rbac.php';

/*
 * =============================================
 * ROLE CHECKING FUNCTIONS
 * =============================================
 */

function isOwner()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isAdmin()
{
    return isOwner();
}

function isManager()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}

function isCashier()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
}

function isBranchOwner()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'branch_owner';
}

/*
 * =============================================
 * BRANCH FUNCTIONS
 * =============================================
 */

function getCurrentBranchId()
{
    if (isOwner() && isset($_SESSION['branch_view_id'])) {
        return (int)$_SESSION['branch_view_id'];
    }
    $branch_id = $_SESSION['branch_id'] ?? null;
    if ($branch_id === null && isset($_SESSION['user_id'])) {
        global $pdo;
        if (isset($pdo)) {
            try {
                $s = $pdo->prepare("SELECT branch_id FROM users WHERE id = ?");
                $s->execute([$_SESSION['user_id']]);
                $branch_id = $s->fetchColumn();
            } catch (PDOException $e) {}
        }
    }
    return $branch_id;
}

function getCurrentBranchName()
{
    if (isOwner() && isset($_SESSION['branch_view_name'])) {
        return $_SESSION['branch_view_name'];
    }
    return $_SESSION['branch_name'] ?? 'All Branches';
}

function getUserBranchId()
{
    return getCurrentBranchId();
}

/*
 * =============================================
 * SESSION / AUTH STATE
 * =============================================
 */

function getLoginUrl()
{
    return '/minute1/auth/login.php';
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function checkUserStatus()
{
    global $pdo;

    // Idempotent per request: whether called explicitly (requireAuth()) or
    // automatically (see bottom of this file), only hit the DB once.
    static $checked = false;
    if ($checked) {
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($pdo)) {
        return;
    }
    $checked = true;

    $stmt = $pdo->prepare("SELECT id, role, role_id, permissions, status, last_activity FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: ' . getLoginUrl());
        session_write_close();
        exit();
    }

    // Keep the RBAC session permission list in sync (role edits / permission
    // changes take effect on the user's next page load instead of next login).
    loadUserPermissions($user);

    if (strtolower($user['status'] ?? 'inactive') !== 'active') {
        session_destroy();
        header('Location: ' . getLoginUrl() . '?error=inactive');
        session_write_close();
        exit();
    }

    if ($user['last_activity'] !== null) {
        $timeout = 300;
        $lastActivity = strtotime($user['last_activity']);
        if (time() - $lastActivity > $timeout) {
            session_destroy();
            header('Location: ' . getLoginUrl() . '?error=session_expired');
            session_write_close();
            exit();
        }
    }

    $updateStmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $updateStmt->execute([$_SESSION['user_id']]);
}

function requireAuth()
{
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit();
    }
    checkUserStatus();
}

/*
 * =============================================
 * ROLE-BASED MIDDLEWARE
 * =============================================
 */

function requireOwner()
{
    requireAuth();
    if (!isOwner()) {
        header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
        exit();
    }
}

function requireAdmin()
{
    requireOwner();
}

function requireManager()
{
    requireAuth();
    if (!isManager() && !isOwner()) {
        header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
        exit();
    }
}

function requireCashier()
{
    requireAuth();
    if (!isCashier() && !isManager() && !isOwner()) {
        header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
        exit();
    }
}

/*
 * =============================================
 * BRANCH ACCESS MIDDLEWARE
 * =============================================
 */

function requireBranchAccess($target_branch_id = null)
{
    requireAuth();

    if (isOwner()) {
        return;
    }

    $user_branch_id = getCurrentBranchId();

    if ($user_branch_id === null) {
        header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
        exit();
    }

    if ($target_branch_id !== null && (int)$user_branch_id !== (int)$target_branch_id) {
        header('Location: /minute1/auth/unauthorized.php?page=' . urlencode($_SERVER['PHP_SELF']));
        exit();
    }
}

/*
 * =============================================
 * PERMISSION SYSTEM
 * =============================================
 *
 * hasPermission(), requirePermission(), hasAnyPermission(), requireAnyPermission()
 * and requireRole() are defined in includes/rbac.php.
 */

function getDefaultPermissions($role)
{
    $matrix = getRolePermissionMatrix();
    $names = $matrix[$role] ?? [];
    $result = [];
    foreach (array_keys(getSystemPermissions()) as $perm) {
        $result[$perm] = in_array($perm, $names, true);
    }
    return $result;
}

function getUserPermissions()
{
    return getSessionPermissionList() ?: [];
}

/*
 * =============================================
 * AUTHENTICATION
 * =============================================
 */

function authenticateUser($user_id, $password)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, b.branch_name, b.status as branch_status
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            WHERE u.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (strtolower($user['status']) === 'inactive') {
            return false;
        }

        if ($user['role'] !== 'admin' && isset($user['branch_status']) && strtolower($user['branch_status']) === 'inactive') {
            return false;
        }

        $storedPassword = $user['password'] ?? '';
        $passwordValid = false;

        if (!empty($storedPassword) && password_verify($password, $storedPassword)) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            return false;
        }

        // Regenerate the session id on login to prevent session fixation
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
        } catch (Exception $e) {
            error_log('session_regenerate_id error: ' . $e->getMessage());
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['role_id'] = $user['role_id'] ?? null;
        $_SESSION['branch_id'] = $user['branch_id'];
        $_SESSION['branch_name'] = $user['branch_name'] ?? null;
        $_SESSION['full_name'] = !empty($user['full_name']) ? $user['full_name'] : $user['user_id'];

        loadUserPermissions($user);

        $updateStmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        auditLog('login', 'auth', 'user', $user['id'], 'success', 'User logged in');

        return $user;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

function generateUserID($pdo)
{
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $next = ((int)($row['max_id'] ?? 0)) + 1;

    return 'USR-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

function getBranchIds($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT branch_id FROM branch_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getBranchFilter($branch_ids)
{
    if (empty($branch_ids)) return "1=0";
    $placeholders = implode(',', array_fill(0, count($branch_ids), '?'));
    return "branch_id IN ($placeholders)";
}

function getBranchScopeCondition($table_alias = '')
{
    $branch_id = getCurrentBranchId();
    if ($branch_id === null) {
        return ''; // Owner sees all
    }
    $alias = $table_alias ? $table_alias . '.' : '';
    return $alias . 'branch_id = ' . (int)$branch_id;
}

function appendBranchFilter(&$sql, $table_alias = '')
{
    $condition = getBranchScopeCondition($table_alias);
    if ($condition) {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            $sql .= ' AND ' . $condition;
        } else {
            $sql .= ' WHERE ' . $condition;
        }
    }
}

/*
 * =============================================
 * CENTRALIZED SESSION-STATUS ENFORCEMENT
 * =============================================
 *
 * Run the idle-timeout / active-status check as soon as auth.php is loaded
 * for any already-logged-in session, rather than relying on every page to
 * remember to call requireAuth(). checkUserStatus() is a no-op if there's no
 * session or $pdo isn't connected yet, and is idempotent per request, so
 * pages that also call requireAuth() explicitly are unaffected.
 */
if (isset($_SESSION['user_id']) && isset($GLOBALS['pdo'])) {
    checkUserStatus();
}
