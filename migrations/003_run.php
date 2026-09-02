<?php
/**
 * Migration 003 Data Migration
 * 
 * Run after 003_inventory_multi_branch.sql.
 * Handles complex data cleanup: merges duplicate inventory records,
 * creates per-branch inventory records for branches 2+.
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "003 Data Migration\n";
echo "=================\n\n";

try {
    // === Step 1: Merge duplicate inventory records for same template+branch ===
    echo "[1/4] Merging duplicate inventory records...\n";
    $stmt = $pdo->query("
        SELECT template_id, branch_id, COUNT(*) as cnt, SUM(quantity) as total_qty
        FROM inventory
        WHERE deleted_at IS NULL AND template_id IS NOT NULL AND branch_id IS NOT NULL
        GROUP BY template_id, branch_id
        HAVING cnt > 1
    ");
    $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $merged = 0;
    foreach ($dupes as $d) {
        // Find the first record - keep it
        $stmt2 = $pdo->prepare("
            SELECT id FROM inventory 
            WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL 
            ORDER BY id ASC LIMIT 1
        ");
        $stmt2->execute([$d['template_id'], $d['branch_id']]);
        $keep_id = $stmt2->fetchColumn();
        if (!$keep_id) continue;

        // Update keeper with total quantity
        $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?")
            ->execute([(int)$d['total_qty'], $keep_id]);

        // Soft-delete the rest
        $pdo->prepare("
            UPDATE inventory SET deleted_at = NOW() 
            WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL AND id != ?
        ")->execute([$d['template_id'], $d['branch_id'], $keep_id]);

        $merged += $d['cnt'] - 1;
    }
    echo "  Merged $merged duplicate records.\n";

    // === Step 2: Create inventory records for branches that are missing templates ===
    echo "[2/4] Creating per-branch inventory records...\n";
    $branches = $pdo->query("SELECT id FROM branches WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    $templates = $pdo->query("SELECT * FROM ingredient_templates")->fetchAll(PDO::FETCH_ASSOC);
    $created = 0;

    foreach ($templates as $tpl) {
        // Get the master branch-1 record for defaults
        $master = $pdo->prepare("SELECT * FROM inventory WHERE template_id = ? AND branch_id = 1 AND deleted_at IS NULL LIMIT 1");
        $master->execute([$tpl['id']]);
        $master = $master->fetch(PDO::FETCH_ASSOC);

        foreach ($branches as $branch_id) {
            // Check if this branch already has this template
            $check = $pdo->prepare("SELECT id FROM inventory WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL");
            $check->execute([$tpl['id'], $branch_id]);
            if ($check->fetch()) continue;

            if ($master) {
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
                $created++;
            }
        }
    }
    echo "  Created $created new inventory records for branches 2+.\n";

    // === Step 3: Update product_ingredients that may still have NULL template_id ===
    echo "[3/4] Backfilling product_ingredients template_id...\n";
    $stmt = $pdo->query("
        UPDATE product_ingredients pi
        JOIN inventory i ON i.id = pi.inventory_id
        SET pi.template_id = i.template_id
        WHERE pi.template_id IS NULL AND i.template_id IS NOT NULL
    ");
    $fixed = $stmt->rowCount();
    echo "  Fixed $fixed product_ingredient records.\n";

    // Log any that still have no template_id
    $orphans = $pdo->query("
        SELECT pi.id, pi.product_id, pi.inventory_id 
        FROM product_ingredients pi 
        WHERE pi.template_id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($orphans)) {
        echo "  WARNING: " . count($orphans) . " product_ingredient records still have NULL template_id.\n";
        foreach ($orphans as $o) {
            echo "    product_ingredient #{$o['id']}: product_id={$o['product_id']}, inventory_id={$o['inventory_id']}\n";
        }
    }

    // === Step 4: Verify ===
    echo "\n[4/4] Verification:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM inventory WHERE deleted_at IS NULL AND branch_id IS NULL
    ");
    $null_branch = (int)$stmt->fetchColumn();
    echo "  Inventory records with NULL branch_id: $null_branch\n";

    $stmt = $pdo->query("
        SELECT COUNT(*) FROM inventory WHERE deleted_at IS NULL AND template_id IS NULL
    ");
    $null_template = (int)$stmt->fetchColumn();
    echo "  Inventory records with NULL template_id: $null_template\n";

    $stmt = $pdo->query("
        SELECT COUNT(*) FROM product_ingredients WHERE template_id IS NULL
    ");
    $null_pi = (int)$stmt->fetchColumn();
    echo "  Product_ingredients with NULL template_id: $null_pi\n";

    $stmt = $pdo->query("
        SELECT b.branch_name, COUNT(i.id) as items
        FROM branches b
        LEFT JOIN inventory i ON i.branch_id = b.id AND i.deleted_at IS NULL
        GROUP BY b.id, b.branch_name
        ORDER BY b.id
    ");
    echo "  Inventory count by branch:\n";
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "    {$r['branch_name']}: {$r['items']} items\n";
    }

    echo "\nMigration complete!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
