<?php
/**
 * Migration 005: Role-Based Access Control (RBAC)
 *
 * Adds:
 *  - permissions table (system catalog)
 *  - role_permissions table (role <-> permission mapping)
 *  - audit_logs table (who did what)
 *  - roles.slug + roles.updated_at + removes legacy "inventory_staff" role
 *  - users.role_id (FK to roles.id) backfilled from users.role
 *  - Seeded permission catalog and role-permission matrix
 *
 * Run from CLI: php migrations/005_rbac.php
 * Idempotent - safe to run multiple times.
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "005 RBAC Migration\n";
echo "=================\n\n";

try {
    // === Step 1: permissions table ===
    echo "[1/7] Creating permissions table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        label VARCHAR(100) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        category VARCHAR(50) DEFAULT 'General',
        is_system TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_permissions_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // === Step 2: role_permissions table ===
    echo "[2/7] Creating role_permissions table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        role_id INT(11) NOT NULL,
        permission_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_role_permission (role_id, permission_id),
        KEY idx_rp_permission (permission_id),
        CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // === Step 3: audit_logs table ===
    echo "[3/7] Creating audit_logs table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) DEFAULT NULL,
        username VARCHAR(100) DEFAULT NULL,
        action VARCHAR(100) NOT NULL,
        category VARCHAR(50) DEFAULT NULL,
        target_type VARCHAR(50) DEFAULT NULL,
        target_id VARCHAR(50) DEFAULT NULL,
        result VARCHAR(20) DEFAULT 'success',
        detail TEXT,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_audit_user (user_id),
        KEY idx_audit_action (action),
        KEY idx_audit_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // === Step 4: roles.slug + roles.updated_at ===
    echo "[4/7] Upgrading roles table...\n";
    $hasSlug = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'slug'"
    )->fetchColumn();
    if ($hasSlug === 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN slug VARCHAR(50) DEFAULT NULL AFTER role_name, ADD UNIQUE KEY uq_roles_slug (slug)");
    }

    $hasUpdated = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'updated_at'"
    )->fetchColumn();
    if ($hasUpdated === 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    // Set slugs for existing roles (match by role_name; slug = role_name)
    foreach (['admin', 'manager', 'cashier', 'branch_owner'] as $rn) {
        $pdo->prepare("UPDATE roles SET slug = ? WHERE role_name = ? AND (slug IS NULL OR slug = '')")
            ->execute([$rn, $rn]);
    }

    // inventory_staff role was removed from the system (see migrations/012_remove_inventory_staff.sql).
    // Delete it here too so re-running this migration does not resurrect it.
    $inv = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'inventory_staff' LIMIT 1");
    $inv->execute();
    if ($inv->fetchColumn()) {
        $pdo->prepare("DELETE FROM roles WHERE role_name = 'inventory_staff'")->execute();
        echo "  Removed legacy 'inventory_staff' role.\n";
    }
    // Also repair any stale users.role enum that still references inventory_staff.
    $col = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'")->fetchColumn();
    if (strpos($col, "'inventory_staff'") !== false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','cashier','branch_owner') NOT NULL DEFAULT 'cashier'");
    }

    // === Step 5: users.role_id ===
    echo "[5/7] Adding users.role_id...\n";
    $hasRoleId = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id'"
    )->fetchColumn();
    if ($hasRoleId === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_id INT(11) DEFAULT NULL AFTER role");
        $fkExists = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'users_ibfk_role'"
        )->fetchColumn();
        if ($fkExists === 0) {
            $pdo->exec("ALTER TABLE users ADD CONSTRAINT users_ibfk_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL");
        }
    }

    // Backfill role_id from role string
    $pdo->exec("UPDATE users u LEFT JOIN roles r ON r.role_name = u.role SET u.role_id = r.id WHERE u.role_id IS NULL");
    echo "  Backfilled role_id for existing users.\n";

    // === Step 6: Seed permissions ===
    echo "[6/7] Seeding permission catalog...\n";
    $permissions = require __DIR__ . '/../includes/permission_catalog.php';
    $permissionIds = [];
    foreach ($permissions as $key => $meta) {
        $pdo->prepare(
            "INSERT INTO permissions (name, label, description, category, is_system) VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), category = VALUES(category)"
        )->execute([$key, $meta['label'], $meta['description'] ?? '', $meta['category'] ?? 'General']);
    }

    // === Step 7: Seed role_permissions ===
    echo "[7/7] Seeding role-permission matrix...\n";
    $rolePerms = require __DIR__ . '/../includes/role_permission_matrix.php';

    // Clear existing role_permissions for system roles, then reseed
    $pdo->exec("DELETE rp FROM role_permissions rp LEFT JOIN roles r ON r.id = rp.role_id WHERE r.is_system = 1");

    $roleIdStmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
    $permIdStmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
    $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

    $total = 0;
    foreach ($rolePerms as $roleName => $perms) {
        $roleIdStmt->execute([$roleName]);
        $roleId = $roleIdStmt->fetchColumn();
        if (!$roleId) {
            echo "  WARNING: role '$roleName' not found, skipping.\n";
            continue;
        }
        foreach ($perms as $permName) {
            $permIdStmt->execute([$permName]);
            $permId = $permIdStmt->fetchColumn();
            if (!$permId) {
                echo "  WARNING: permission '$permName' not found, skipping.\n";
                continue;
            }
            $insertStmt->execute([$roleId, $permId]);
            $total++;
        }
    }
    echo "  Seeded $total role-permission assignments.\n";

    echo "\nMigration complete!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
