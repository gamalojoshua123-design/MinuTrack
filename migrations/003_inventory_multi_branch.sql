-- =============================================
-- Migration 003: Multi-Branch Inventory
--
-- Creates ingredient_templates as canonical layer
-- between products and per-branch inventory.
-- Allows each branch to have independent stock levels
-- for the same ingredient.
-- =============================================

-- Step 1: Create ingredient_templates table
-- Each row = one canonical ingredient (e.g., "Burger Patty (piece)")
-- Products reference templates; inventory records are per-branch copies
CREATE TABLE IF NOT EXISTS ingredient_templates (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  unit VARCHAR(20) DEFAULT 'piece',
  category VARCHAR(50) DEFAULT 'Uncategorized',
  default_min_stock INT(11) DEFAULT 10,
  default_cost_price DECIMAL(10,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_template_name_unit (item_name, unit)
);

-- Step 2: Populate templates from existing inventory
INSERT IGNORE INTO ingredient_templates (item_name, unit, category, default_min_stock)
SELECT DISTINCT 
  i.item_name, 
  COALESCE(i.unit, 'piece') AS unit, 
  COALESCE(i.category, 'Uncategorized') AS category, 
  MIN(i.min_stock) AS min_stock
FROM inventory i
WHERE i.deleted_at IS NULL AND i.item_name IS NOT NULL AND i.item_name != ''
GROUP BY i.item_name, i.unit, i.category;

-- Step 3: Add template_id FK to inventory
ALTER TABLE inventory
  ADD COLUMN template_id INT(11) DEFAULT NULL AFTER id,
  ADD INDEX idx_inventory_template (template_id);

-- Step 4: Link existing inventory records to templates
UPDATE inventory i
  JOIN ingredient_templates t ON i.item_name = t.item_name AND COALESCE(i.unit, 'piece') = t.unit
  SET i.template_id = t.id
  WHERE i.deleted_at IS NULL AND i.template_id IS NULL;

-- Step 5: Assign all existing branchless inventory to branch 1 (Salay)
UPDATE inventory SET branch_id = 1 WHERE deleted_at IS NULL AND branch_id IS NULL;

-- Step 6: Add template_id to product_ingredients
-- Changes the meaning: instead of pointing to a specific inventory record,
-- now points to a canonical ingredient template.
-- At deduction time: resolve template_id + branch_id → inventory record
ALTER TABLE product_ingredients
  ADD COLUMN template_id INT(11) DEFAULT NULL AFTER id,
  ADD INDEX idx_pi_template (template_id);

-- Step 7: Populate template_id on product_ingredients from linked inventory
UPDATE product_ingredients pi
  JOIN inventory i ON i.id = pi.inventory_id
  SET pi.template_id = i.template_id
  WHERE pi.template_id IS NULL;

-- Step 8: Add FK constraints
ALTER TABLE inventory
  ADD CONSTRAINT fk_inventory_template FOREIGN KEY (template_id) REFERENCES ingredient_templates(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_inventory_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;

ALTER TABLE product_ingredients
  ADD CONSTRAINT fk_pi_template FOREIGN KEY (template_id) REFERENCES ingredient_templates(id) ON DELETE CASCADE;

-- Step 9: Backfill branch_id on inventory_movements from their inventory items
UPDATE inventory_movements im
  JOIN inventory i ON i.id = im.inventory_id
  SET im.branch_id = i.branch_id
  WHERE im.branch_id IS NULL AND i.branch_id IS NOT NULL;
