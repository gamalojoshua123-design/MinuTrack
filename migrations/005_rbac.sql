-- Migration 005: Role-Based Access Control (RBAC)
-- Preferred runner: php migrations/005_rbac.php (idempotent, also seeds data).
-- This SQL documents the schema changes.

-- ============================================================
-- 1. Permissions catalog table
-- ============================================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    label VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'General',
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. Role <-> Permission mapping table
-- ============================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    role_id INT(11) NOT NULL,
    permission_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_role_permission (role_id, permission_id),
    KEY idx_rp_permission (permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. Audit log table
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 4. Roles: slug, updated_at; remove legacy inventory_staff role
-- ============================================================
ALTER TABLE roles ADD COLUMN slug VARCHAR(50) DEFAULT NULL AFTER role_name,
    ADD UNIQUE KEY uq_roles_slug (slug);
ALTER TABLE roles ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
UPDATE roles SET slug = role_name WHERE slug IS NULL OR slug = '';
DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE role_name = 'inventory_staff');
DELETE FROM roles WHERE role_name = 'inventory_staff';

-- ============================================================
-- 5. Users: role_id FK, narrowed role enum
-- ============================================================
ALTER TABLE users ADD COLUMN role_id INT(11) DEFAULT NULL AFTER role,
    ADD CONSTRAINT users_ibfk_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;
ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','cashier','branch_owner') NOT NULL DEFAULT 'cashier';
UPDATE users u LEFT JOIN roles r ON r.role_name = u.role SET u.role_id = r.id WHERE u.role_id IS NULL;

-- ============================================================
-- 6 & 7. Permission catalog + role-permission matrix are seeded
--        by the PHP runner from includes/permission_catalog.php and
--        includes/role_permission_matrix.php.
-- ============================================================
