<?php
/**
 * Migration 003: Multi-Branch Inventory System
 *
 * What this does:
 * 1. Creates ingredient_templates (canonical ingredient catalog)
 * 2. Adds template_id to inventory + product_ingredients
 * 3. Assigns all existing inventory to Salay (branch 1)
 * 4. Creates zero-quantity inventory records for other branches
 * 5. Backfills branch_id on existing movements
 * 6. Adds FK constraints (safely, with duplicate checks)
 */

require_once __DIR__ . '/../includes/db_connect.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function run(string $sql, string $desc): void {
    global $pdo;
    try {
        $pdo->exec($sql);
        echo "  OK: $desc\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate column') || str_contains($msg, 'Duplicate key name') || str_contains($msg, 'already exists')) {
            echo "  SKIP: $desc (already exists)\n";
        } else {
            throw $e;
        }
    }
}

echo "Migration 003: Multi-Branch Inventory\n";
echo "======================================\n\n";

echo "Phase 1: Schema Changes\n";
echo "-----------------------\n";

// 1a. Create ingredient_templates
run("
    CREATE TABLE IF NOT EXISTS ingredient_templates (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(100) NOT NULL,
        unit VARCHAR(20) DEFAULT 'piece',
        category VARCHAR(50) DEFAULT 'Uncategorized',
        default_min_stock INT(11) DEFAULT 10,
        default_cost_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_template_name_unit (item_name, unit)
    )", "Create ingredient_templates table");

// 1b. Populate templates
$tpl_count = $pdo->exec("
    INSERT IGNORE INTO ingredient_templates (item_name, unit, category, default_min_stock)
    SELECT DISTINCT 
        i.item_name, 
        COALESCE(i.unit, 'piece'),
        COALESCE(i.category, 'Uncategorized'),
        MIN(i.min_stock)
    FROM inventory i
    WHERE i.deleted_at IS NULL AND i.item_name IS NOT NULL AND i.item_name != ''
    GROUP BY i.item_name, i.unit, i.category
");
echo "  OK: Populated $tpl_count ingredient templates\n";

// 1c. Add template_id to inventory
run("ALTER TABLE inventory ADD COLUMN template_id INT(11) DEFAULT NULL AFTER id", "Add inventory.template_id");
run("ALTER TABLE inventory ADD INDEX idx_inventory_template (template_id)", "Add inventory template_id index");

// 1d. Link inventory records to templates
$linked = $pdo->exec("
    UPDATE inventory i
    JOIN ingredient_templates t ON i.item_name = t.item_name AND COALESCE(i.unit, 'piece') = t.unit
    SET i.template_id = t.id
    WHERE i.deleted_at IS NULL AND i.template_id IS NULL
");
echo "  OK: Linked $linked inventory records to templates\n";

// 1e. Add template_id to product_ingredients
run("ALTER TABLE product_ingredients ADD COLUMN template_id INT(11) DEFAULT NULL AFTER id", "Add product_ingredients.template_id");
run("ALTER TABLE product_ingredients ADD INDEX idx_pi_template (template_id)", "Add product_ingredients template_id index");

// 1f. Populate template_id on product_ingredients
$pi_linked = $pdo->exec("
    UPDATE product_ingredients pi
    JOIN inventory i ON i.id = pi.inventory_id
    SET pi.template_id = i.template_id
    WHERE pi.template_id IS NULL
");
echo "  OK: Linked $pi_linked product_ingredient records to templates\n";

echo "\nPhase 2: Data Migration\n";
echo "-----------------------\n";

// 2a. Assign all existing inventory to branch 1 (Salay)
$assigned = $pdo->exec("UPDATE inventory SET branch_id = 1 WHERE deleted_at IS NULL AND branch_id IS NULL");
echo "  OK: Assigned $assigned inventory records to branch 1 (Salay)\n";

// 2b. Merge duplicates (same template + branch)
$stmt = $pdo->query("
    SELECT template_id, branch_id, COUNT(*) as cnt, SUM(quantity) as total_qty
    FROM inventory
    WHERE deleted_at IS NULL AND template_id IS NOT NULL AND branch_id IS NOT NULL
    GROUP BY template_id, branch_id
    HAVING cnt > 1
");
$dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$merged_count = 0;
foreach ($dupes as $d) {
    $stmt2 = $pdo->prepare("SELECT id FROM inventory WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
    $stmt2->execute([$d['template_id'], $d['branch_id']]);
    $keep_id = $stmt2->fetchColumn();
    if (!$keep_id) continue;

    $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?")->execute([(int)$d['total_qty'], $keep_id]);
    $pdo->prepare("UPDATE inventory SET deleted_at = NOW() WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL AND id != ?")
        ->execute([$d['template_id'], $d['branch_id'], $keep_id]);
    $merged_count += $d['cnt'] - 1;
}
echo "  OK: Merged $merged_count duplicate inventory records\n";

// 2c. Create inventory records for other branches
$branches = $pdo->query("SELECT id FROM branches WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$templates = $pdo->query("SELECT * FROM ingredient_templates")->fetchAll(PDO::FETCH_ASSOC);
$created_count = 0;
foreach ($templates as $tpl) {
    foreach ($branches as $branch_id) {
        if ($branch_id == 1) continue; // Already has the master record
        $check = $pdo->prepare("SELECT id FROM inventory WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL");
        $check->execute([$tpl['id'], $branch_id]);
        if ($check->fetch()) continue;
        
        // Get master (branch 1) for defaults
        $master = $pdo->prepare("SELECT * FROM inventory WHERE template_id = ? AND branch_id = 1 AND deleted_at IS NULL LIMIT 1");
        $master->execute([$tpl['id']]);
        $master = $master->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare("
            INSERT INTO inventory (template_id, branch_id, item_name, category, quantity, min_stock, unit, cost_price, supplier, status)
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, 'active')
        ")->execute([
            $tpl['id'], $branch_id,
            $tpl['item_name'], $tpl['category'],
            $tpl['default_min_stock'], $tpl['unit'],
            $master['cost_price'] ?? 0.00,
            $master['supplier'] ?? null
        ]);
        $created_count++;
    }
}
echo "  OK: Created $created_count new inventory records for branches 2+\n";

echo "\nPhase 3: Constraints & Cleanup\n";
echo "-----------------------------\n";

// 3a. Safely add FK constraints (drop first if exists)
try { $pdo->exec("ALTER TABLE inventory DROP FOREIGN KEY fk_inventory_template"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE inventory DROP FOREIGN KEY fk_inventory_branch"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE product_ingredients DROP FOREIGN KEY fk_pi_template"); } catch (Exception $e) {}

run("ALTER TABLE inventory ADD CONSTRAINT fk_inventory_template FOREIGN KEY (template_id) REFERENCES ingredient_templates(id) ON DELETE SET NULL", "Add inventory->ingredient_templates FK");
run("ALTER TABLE inventory ADD CONSTRAINT fk_inventory_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL", "Add inventory->branches FK");
run("ALTER TABLE product_ingredients ADD CONSTRAINT fk_pi_template FOREIGN KEY (template_id) REFERENCES ingredient_templates(id) ON DELETE CASCADE", "Add product_ingredients->ingredient_templates FK");

// 3b. Backfill branch_id on inventory_movements
$backfilled = $pdo->exec("
    UPDATE inventory_movements im
    JOIN inventory i ON i.id = im.inventory_id
    SET im.branch_id = i.branch_id
    WHERE im.branch_id IS NULL AND i.branch_id IS NOT NULL
");
echo "  OK: Backfilled $backfilled inventory_movements with branch_id\n";

echo "\nPhase 4: Verification\n";
echo "---------------------\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM inventory WHERE deleted_at IS NULL AND branch_id IS NULL");
echo "  Inventory with NULL branch_id: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM inventory WHERE deleted_at IS NULL AND template_id IS NULL");
echo "  Inventory with NULL template_id: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM product_ingredients WHERE template_id IS NULL");
echo "  Product_ingredients with NULL template_id: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM ingredient_templates");
echo "  Ingredient templates: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("
    SELECT b.branch_name, COUNT(i.id) as items, SUM(i.quantity) as total_qty
    FROM branches b
    LEFT JOIN inventory i ON i.branch_id = b.id AND i.deleted_at IS NULL
    GROUP BY b.id, b.branch_name
    ORDER BY b.id
");
echo "  Inventory by branch:\n";
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "    {$r['branch_name']}: {$r['items']} items, {$r['total_qty']} total qty\n";
}

echo "\nMigration 003 complete!\n";
