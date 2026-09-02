-- =============================================
-- Inventory System Upgrade
-- Adds branch isolation, cost tracking, decimal BOM
-- =============================================

-- 1. Add columns to inventory (only add if missing)
SET @dbname = (SELECT DATABASE());
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'branch_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE inventory ADD COLUMN branch_id INT(11) DEFAULT NULL AFTER id, ADD INDEX idx_inventory_branch (branch_id)', 'SELECT \"branch_id already exists\"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'cost_price');
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE inventory ADD COLUMN cost_price DECIMAL(10,2) DEFAULT 0.00 AFTER unit', 'SELECT \"cost_price already exists\"');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'selling_unit');
SET @sql3 = IF(@exists3 = 0, 'ALTER TABLE inventory ADD COLUMN selling_unit VARCHAR(50) DEFAULT NULL AFTER unit', 'SELECT \"selling_unit already exists\"');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 2. Update product_ingredients to support decimal quantities
ALTER TABLE product_ingredients MODIFY COLUMN qty_required DECIMAL(10,2) NOT NULL DEFAULT 1.00;

-- 3. Add columns to inventory_movements (only if missing)
SET @exists4 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inventory_movements' AND COLUMN_NAME = 'reason');
SET @sql4 = IF(@exists4 = 0, 'ALTER TABLE inventory_movements ADD COLUMN reason VARCHAR(100) DEFAULT NULL AFTER notes', 'SELECT \"reason already exists\"');
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

SET @exists5 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inventory_movements' AND COLUMN_NAME = 'branch_id');
SET @sql5 = IF(@exists5 = 0, 'ALTER TABLE inventory_movements ADD COLUMN branch_id INT(11) DEFAULT NULL AFTER performed_by, ADD INDEX idx_movements_branch (branch_id)', 'SELECT \"branch_id on movements already exists\"');
PREPARE stmt5 FROM @sql5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

-- Add indexes (if not exist, safe to run)
ALTER TABLE inventory_movements ADD INDEX IF NOT EXISTS idx_movements_type (movement_type);
ALTER TABLE inventory_movements ADD INDEX IF NOT EXISTS idx_movements_date (created_at);

-- 4. Create stock receiving tables
CREATE TABLE IF NOT EXISTS stock_receiving (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  reference_number VARCHAR(50) NOT NULL,
  supplier_id INT(11) DEFAULT NULL,
  supplier_name VARCHAR(255) DEFAULT NULL,
  branch_id INT(11) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  received_by INT(11) NOT NULL,
  received_date DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_receiving_branch (branch_id),
  INDEX idx_receiving_date (received_date)
);

CREATE TABLE IF NOT EXISTS stock_receiving_items (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  receiving_id INT(11) NOT NULL,
  inventory_id INT(11) NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  unit_cost DECIMAL(10,2) DEFAULT 0.00,
  total_cost DECIMAL(10,2) DEFAULT 0.00,
  batch_number VARCHAR(100) DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  INDEX idx_receiving_items_recv (receiving_id),
  INDEX idx_receiving_items_inv (inventory_id)
);

-- 5. Create inventory_counts table
CREATE TABLE IF NOT EXISTS inventory_counts (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  inventory_id INT(11) NOT NULL,
  system_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  actual_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  difference DECIMAL(10,2) NOT NULL DEFAULT 0,
  reason VARCHAR(255) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  counted_by INT(11) NOT NULL,
  branch_id INT(11) DEFAULT NULL,
  counted_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_counts_inventory (inventory_id),
  INDEX idx_counts_branch (branch_id),
  INDEX idx_counts_date (counted_at)
);
