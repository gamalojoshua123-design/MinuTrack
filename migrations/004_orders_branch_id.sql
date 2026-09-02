-- Migration 004: Add branch_id to orders and cashier_shifts tables

SET @dbname = DATABASE();

-- Add branch_id to orders table
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'branch_id');
SET @sql = IF(@exists = 0,
    'ALTER TABLE orders ADD COLUMN branch_id INT(11) DEFAULT NULL AFTER shift_id, ADD INDEX idx_orders_branch (branch_id)',
    'SELECT "orders.branch_id already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for orders.branch_id
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @dbname AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'orders_ibfk_3');
SET @sql2 = IF(@fk_exists = 0,
    'ALTER TABLE orders ADD CONSTRAINT orders_ibfk_3 FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL',
    'SELECT "orders FK already exists"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Run via: mysql -u root pos_system < migrations/004_orders_branch_id.sql
