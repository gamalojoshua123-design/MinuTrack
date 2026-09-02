-- 011_add_users_role_id.sql
-- Complements migrations/005_rbac.php (which restores the RBAC tables and
-- users.role_id) by extending the users.role enum with 'branch_owner'.
-- The old pos_system (2).sql dump predates both.

ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','cashier','manager','inventory_staff','branch_owner') NOT NULL DEFAULT 'cashier';

INSERT INTO roles (role_name, slug, description, is_system)
    SELECT 'branch_owner', 'branch_owner', 'Branch Owner - Views their own branch performance', 1
    WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'branch_owner');

-- Ensure role_id exists even if 005_rbac.php has not been run yet.
SET @dbname = DATABASE();
SET @has_role_id = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id');
SET @sql = IF(@has_role_id = 0,
    'ALTER TABLE users ADD COLUMN role_id INT NULL AFTER role',
    'SELECT "users.role_id already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE users u
LEFT JOIN roles r ON r.role_name = u.role
SET u.role_id = r.id
WHERE u.role_id IS NULL;

-- permission_logs table (DDL recovered from the original dump)
CREATE TABLE IF NOT EXISTS permission_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    action VARCHAR(100) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
