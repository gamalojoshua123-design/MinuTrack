<?php
/**
 * Inventory Business Logic
 * 
 * Core functions for the inventory management system.
 * Handles stock receiving, adjustments, physical counts,
 * BOM-based deduction, branch isolation, and reporting.
 */

if (!defined('INVENTORY_FUNCTIONS_LOADED')) {
    define('INVENTORY_FUNCTIONS_LOADED', true);

/**
 * Get branch-scoped inventory query condition for aliased queries
 */
function getInventoryBranchCondition(): string {
    $branch_id = getCurrentBranchId();
    if ($branch_id === null || isOwner()) {
        return '';
    }
    return ' AND branch_id = ' . (int)$branch_id;
}

/**
 * Same as getInventoryBranchCondition but prefixes the column with an alias
 */
function getInventoryBranchConditionAlias(string $alias = 'i'): string {
    $branch_id = getCurrentBranchId();
    if ($branch_id === null || isOwner()) {
        return '';
    }
    return ' AND ' . $alias . '.branch_id = ' . (int)$branch_id;
}

    /**
     * Get inventory items with optional branch filter
     */
    function getInventoryItems(PDO $pdo, ?string $search = null, ?string $category = null, ?string $status = null): array {
        $sql = "SELECT i.* FROM inventory i WHERE i.deleted_at IS NULL";
        $params = [];

        $branchCond = getInventoryBranchConditionAlias('i');
        if ($branchCond) {
            $sql .= $branchCond;
        }

        if ($search) {
            $sql .= " AND i.item_name LIKE ?";
            $params[] = '%' . $search . '%';
        }
        if ($category) {
            $sql .= " AND i.category = ?";
            $params[] = $category;
        }
        if ($status === 'active') {
            $sql .= " AND i.status = 'active'";
        } elseif ($status === 'low_stock') {
            $sql .= " AND i.quantity <= i.min_stock AND i.status = 'active'";
        }

        $sql .= " ORDER BY i.item_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get low stock items (quantity <= min_stock)
     */
    function getLowStockItems(PDO $pdo): array {
        $branchCond = getInventoryBranchConditionAlias('i');
        $sql = "SELECT i.* FROM inventory i WHERE i.deleted_at IS NULL AND i.quantity <= i.min_stock AND i.status = 'active'";
        if ($branchCond) {
            $sql .= $branchCond;
        }
        $sql .= " ORDER BY (i.min_stock - i.quantity) DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Record an inventory movement with full audit trail
     *
     * To keep previous_quantity/new_quantity accurate, callers should read the
     * stock level BEFORE applying their change and pass it as $previous_quantity.
     * When omitted, the current DB quantity is used (callers must invoke this
     * before mutating stock).
     */
    function recordMovement(PDO $pdo, int $inventory_id, string $movement_type, float $quantity, ?string $notes = null, ?string $reason = null, ?int $reference_id = null, ?string $reference_type = null, ?float $previous_quantity = null): void {
        $stmt = $pdo->prepare("SELECT quantity, item_name FROM inventory WHERE id = ?");
        $stmt->execute([$inventory_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) return;

        $previous_qty = $previous_quantity !== null ? (float)$previous_quantity : (float)$item['quantity'];
        $new_qty = $movement_type === 'stock_in' ? $previous_qty + $quantity : $previous_qty - $quantity;
        $performed_by = $_SESSION['user_id'] ?? null;
        $branch_id = getCurrentBranchId();

        $stmt = $pdo->prepare("
            INSERT INTO inventory_movements 
            (inventory_id, movement_type, quantity, previous_quantity, new_quantity, reference_type, reference_id, notes, reason, performed_by, branch_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $inventory_id, $movement_type, $quantity,
            $previous_qty, $new_qty,
            $reference_type, $reference_id,
            $notes, $reason, $performed_by, $branch_id
        ]);

        // Also log to inventory_history for backward compat
        $change = $movement_type === 'stock_in' ? $quantity : -$quantity;
        $change_type = match($movement_type) {
            'stock_in' => 'restock',
            'stock_out' => 'sale',
            'adjustment' => 'adjustment',
            'waste' => 'waste',
            'return' => 'restock',
            default => 'adjustment'
        };
        $stmt2 = $pdo->prepare("
            INSERT INTO inventory_history (inventory_id, item_name, previous_quantity, new_quantity, quantity_change, change_type, change_date, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, NOW())
        ");
        $stmt2->execute([$inventory_id, $item['item_name'], $previous_qty, $new_qty, $change, $change_type, $notes]);
    }

    /**
     * Stock Receiving - Add inventory from supplier/warehouse
     * 
     * @return array{success: bool, message: string, receiving_id?: int}
     */
    function receiveStock(PDO $pdo, int $supplier_id, string $supplier_name, array $items, ?string $notes = null): array {
        try {
            $pdo->beginTransaction();

            // Create receiving record
            $ref_num = 'RCV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $received_by = $_SESSION['user_id'] ?? 0;
            $branch_id = getCurrentBranchId();

            $stmt = $pdo->prepare("
                INSERT INTO stock_receiving (reference_number, supplier_id, supplier_name, branch_id, notes, received_by, received_date)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ref_num, $supplier_id, $supplier_name, $branch_id, $notes, $received_by]);
            $receiving_id = (int)$pdo->lastInsertId();

            $total_value = 0;

            foreach ($items as $item) {
                $inventory_id = (int)($item['inventory_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                $unit_cost = (float)($item['unit_cost'] ?? 0);
                $batch_number = $item['batch_number'] ?? null;
                $expiry_date = $item['expiry_date'] ?? null;

                if ($inventory_id <= 0 || $qty <= 0) continue;

                // Capture stock level BEFORE the update for an accurate audit trail
                $qStmt = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ? AND deleted_at IS NULL");
                $qStmt->execute([$inventory_id]);
                $prev_qty = (float)$qStmt->fetchColumn();

                $total_cost = $qty * $unit_cost;
                $total_value += $total_cost;

                // Save receiving item
                $stmt2 = $pdo->prepare("
                    INSERT INTO stock_receiving_items (receiving_id, inventory_id, item_name, quantity, unit_cost, total_cost, batch_number, expiry_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt2->execute([
                    $receiving_id, $inventory_id,
                    ($item['item_name'] ?? ''),
                    $qty, $unit_cost, $total_cost,
                    $batch_number, $expiry_date
                ]);

                // Update inventory quantity directly (float-safe; does not depend
                // on a page-local addInventoryBatch() helper)
                $stmt3 = $pdo->prepare("
                    UPDATE inventory SET quantity = quantity + ?, last_updated = NOW() WHERE id = ? AND deleted_at IS NULL
                ");
                $stmt3->execute([$qty, $inventory_id]);

                // Create batch record for FIFO tracking
                $stmt4 = $pdo->prepare("
                    INSERT INTO inventory_batches (inventory_id, batch_quantity, remaining_quantity, received_at, expiry_date)
                    VALUES (?, ?, ?, NOW(), ?)
                ");
                $stmt4->execute([$inventory_id, $qty, $qty, $expiry_date]);

                // Record movement
                recordMovement($pdo, $inventory_id, 'stock_in', $qty, $notes, 'receiving', $receiving_id, 'stock_receiving', $prev_qty);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "Stock received successfully. Reference: $ref_num", 'receiving_id' => $receiving_id];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Stock receiving failed: ' . $e->getMessage()];
        }
    }

    /**
     * Inventory Adjustment - Increase or decrease stock with reason
     */
    function adjustInventory(PDO $pdo, int $inventory_id, float $quantity, string $adjustment_type, string $reason, ?string $notes = null): array {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT quantity, item_name FROM inventory WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                return ['success' => false, 'message' => 'Inventory item not found.'];
            }

            $current_qty = (float)$item['quantity'];
            $abs_qty = abs($quantity);

            // Validate: can't deduct more than available
            if ($quantity < 0 && $abs_qty > $current_qty) {
                return ['success' => false, 'message' => "Cannot deduct {$abs_qty} {$item['item_name']}. Only {$current_qty} available."];
            }

            // Preserve the reason type in the movement audit trail
            $movement_type = match ($adjustment_type) {
                'waste' => 'waste',
                'damage' => 'waste',
                'count' => 'adjustment',
                'return' => 'return',
                default => $quantity >= 0 ? 'stock_in' : 'stock_out',
            };

            // Update stock (exclude soft-deleted items)
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_updated = NOW() WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$quantity, $inventory_id]);

            // Record movement with the pre-update quantity for an accurate trail
            recordMovement($pdo, $inventory_id, $movement_type, $abs_qty, $notes, $reason, null, null, $current_qty);

            $pdo->commit();
            $action = $quantity >= 0 ? 'Increased' : 'Decreased';
            return ['success' => true, 'message' => "{$action} {$item['item_name']} by {$abs_qty}. Reason: {$reason}."];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Adjustment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Physical Stock Count - Compare system vs actual, then auto-adjust
     */
    function performStockCount(PDO $pdo, int $inventory_id, float $actual_quantity, ?string $notes = null): array {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, item_name, quantity FROM inventory WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Inventory item not found.'];
            }

            $system_qty = (float)$item['quantity'];
            $difference = $actual_quantity - $system_qty;
            $counted_by = $_SESSION['user_id'] ?? 0;
            $branch_id = getCurrentBranchId();

            // Save the count record
            $stmt = $pdo->prepare("
                INSERT INTO inventory_counts (inventory_id, system_quantity, actual_quantity, difference, notes, counted_by, branch_id, counted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$inventory_id, $system_qty, $actual_quantity, $difference, $notes, $counted_by, $branch_id]);
            $count_id = (int)$pdo->lastInsertId();

            // Auto-adjust if difference exists (inline so it shares this transaction)
            if ($difference != 0) {
                $reason = $difference > 0 ? 'Physical count surplus' : 'Physical count shortage';
                $movement_type = $difference > 0 ? 'stock_in' : 'stock_out';
                $abs_diff = abs($difference);

                $stmt = $pdo->prepare("
                    UPDATE inventory SET quantity = quantity + ?, last_updated = NOW()
                    WHERE id = ? AND deleted_at IS NULL
                ");
                $stmt->execute([$difference, $inventory_id]);

                recordMovement($pdo, $inventory_id, $movement_type, $abs_diff, $notes, $reason, $count_id, 'inventory_count', $system_qty);
            }

            $pdo->commit();
            $direction = $difference > 0 ? 'surplus' : ($difference < 0 ? 'shortage' : 'exact');
            return ['success' => true, 'message' => "Count complete. System: {$system_qty}, Actual: {$actual_quantity}, Difference: {$difference} ({$direction})."];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Stock count failed: ' . $e->getMessage()];
        }
    }

    /**
     * Resolve a template_id to the branch-specific inventory record.
     * Auto-creates a zero-quantity record if one doesn't exist yet.
     */
    function resolveBranchInventory(PDO $pdo, int $template_id, ?int $branch_id = null): ?array {
        if ($branch_id === null) {
            $branch_id = getCurrentBranchId();
        }
        if ($branch_id === null) return null;

        // Look for existing record
        $stmt = $pdo->prepare("
            SELECT * FROM inventory 
            WHERE template_id = ? AND branch_id = ? AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([$template_id, $branch_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) return $record;

        // Auto-create one from template defaults
        $stmt = $pdo->prepare("SELECT * FROM ingredient_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tpl) return null;

        $pdo->prepare("
            INSERT INTO inventory (template_id, branch_id, item_name, category, quantity, min_stock, unit, cost_price, status)
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, 'active')
        ")->execute([
            $template_id, $branch_id,
            $tpl['item_name'], $tpl['category'],
            $tpl['default_min_stock'], $tpl['unit'],
            $tpl['default_cost_price']
        ]);

        $new_id = (int)$pdo->lastInsertId();
        return [
            'id' => $new_id,
            'template_id' => $template_id,
            'branch_id' => $branch_id,
            'item_name' => $tpl['item_name'],
            'unit' => $tpl['unit'],
            'quantity' => 0,
        ];
    }

    /**
     * BOM-based ingredient validation for POS
     * Check if all ingredients in a product's recipe have sufficient stock
     * for the current branch
     * 
     * @return array{success: bool, message: string, insufficient: array}
     */
    function validateProductIngredients(PDO $pdo, int $product_id, float $quantity): array {
        $branch_id = getCurrentBranchId();

        $stmt = $pdo->prepare("
            SELECT pi.qty_required, pi.template_id, t.item_name, t.unit, p.name AS product_name
            FROM product_ingredients pi
            JOIN ingredient_templates t ON t.id = pi.template_id
            JOIN products p ON p.id = pi.product_id
            WHERE pi.product_id = ?
        ");
        $stmt->execute([$product_id]);
        $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($ingredients)) {
            return ['success' => true, 'message' => '', 'insufficient' => []];
        }

        $insufficient = [];
        foreach ($ingredients as $ing) {
            $required = (float)$ing['qty_required'] * $quantity;
            $inv = resolveBranchInventory($pdo, (int)$ing['template_id'], $branch_id);
            $available = $inv ? (float)$inv['quantity'] : 0;

            if ($available < $required) {
                $insufficient[] = [
                    'item_name' => $ing['item_name'],
                    'required' => $required,
                    'available' => $available,
                    'unit' => $ing['unit'] ?? 'pcs'
                ];
            }
        }

        if (!empty($insufficient)) {
            $messages = [];
            foreach ($insufficient as $i) {
                $messages[] = "{$i['item_name']} (need {$i['required']} {$i['unit']}, have {$i['available']} {$i['unit']})";
            }
            return [
                'success' => false,
                'message' => 'Insufficient ingredients: ' . implode('; ', $messages) . '. Please restock before completing this sale.',
                'insufficient' => $insufficient
            ];
        }

        return ['success' => true, 'message' => '', 'insufficient' => []];
    }

    /**
     * BOM-based ingredient deduction for POS
     * Deduct ingredients from inventory based on product recipe,
     * resolved to the current branch's inventory records via templates
     */
    function deductProductIngredients(PDO $pdo, int $product_id, float $quantity, int $order_id): array {
        $branch_id = getCurrentBranchId();

        $stmt = $pdo->prepare("
            SELECT pi.qty_required, pi.template_id, t.item_name, t.unit
            FROM product_ingredients pi
            JOIN ingredient_templates t ON t.id = pi.template_id
            WHERE pi.product_id = ?
        ");
        $stmt->execute([$product_id]);
        $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($ingredients)) {
            return ['success' => true, 'message' => 'No ingredients defined for this product.'];
        }

        foreach ($ingredients as $ing) {
            $deduct_qty = (float)$ing['qty_required'] * $quantity;
            $inv = resolveBranchInventory($pdo, (int)$ing['template_id'], $branch_id);

            if (!$inv) {
                $branch_label = $branch_id !== null
                    ? "branch #{$branch_id}"
                    : "your account's branch (no branch is assigned — ask the owner to set one)";
                throw new Exception("Cannot find inventory record for ingredient '{$ing['item_name']}' at {$branch_label}.");
            }

            $prev_qty = (float)$inv['quantity'];

            $stmt2 = $pdo->prepare("
                UPDATE inventory SET quantity = quantity - ?, last_updated = NOW()
                WHERE id = ? AND deleted_at IS NULL AND quantity >= ?
            ");
            $stmt2->execute([$deduct_qty, (int)$inv['id'], $deduct_qty]);

            if ($stmt2->rowCount() === 0) {
                throw new Exception("Insufficient stock for ingredient.");
            }

            recordMovement($pdo, (int)$inv['id'], 'stock_out', $deduct_qty, "Order #{$order_id} - sale", 'sale', $order_id, 'order', $prev_qty);
        }

        return ['success' => true, 'message' => ''];
    }

    /**
     * Enrich products array with branch-specific stock from BOM inventory.
     * Replaces global products.stock with actual available qty per branch.
     * Products without a BOM fall back to their own global stock value
     * (nothing can be deducted for them at checkout either).
     */
    function enrichBranchStock(PDO $pdo, array &$products, ?int $branch_id): void {
        if ($branch_id === null) {
            return;
        }

        $productIds = array_column($products, 'id');
        if (empty($productIds)) return;

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("
            SELECT pi.product_id, pi.qty_required, pi.template_id
            FROM product_ingredients pi
            WHERE pi.product_id IN ($placeholders)
        ");
        $stmt->execute($productIds);
        $bomRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bomByProduct = [];
        foreach ($bomRows as $row) {
            $bomByProduct[$row['product_id']][] = $row;
        }

        $stmt = $pdo->prepare("
            SELECT i.template_id, i.quantity
            FROM inventory i
            WHERE i.branch_id = ? AND i.deleted_at IS NULL
        ");
        $stmt->execute([$branch_id]);
        $inventory = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inventory[$row['template_id']] = (float)$row['quantity'];
        }

        foreach ($products as &$p) {
            $bom = $bomByProduct[$p['id']] ?? [];
            if (empty($bom)) {
                // No recipe defined: keep the product's own stock figure so it
                // stays sellable instead of showing a false Out of Stock
                if (!isset($p['stock']) || (float)$p['stock'] < 0) {
                    $p['stock'] = 0;
                }
                continue;
            }
            $available = PHP_INT_MAX;
            foreach ($bom as $ing) {
                $invStock = $inventory[$ing['template_id']] ?? 0;
                $required = (float)$ing['qty_required'];
                $possible = $required > 0 ? (int)($invStock / $required) : 0;
                $available = min($available, $possible);
            }
            $p['stock'] = max(0, $available);
        }
        unset($p);
    }

    /**
     * Get inventory movement history for an item
     */
    function getItemHistory(PDO $pdo, int $inventory_id, int $limit = 50): array {
        $stmt = $pdo->prepare("
            SELECT im.*, u.full_name AS performed_by_name
            FROM inventory_movements im
            LEFT JOIN users u ON u.id = im.performed_by
            WHERE im.inventory_id = ?
            ORDER BY im.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$inventory_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get receiving history
     */
    function getReceivingHistory(PDO $pdo, ?string $from = null, ?string $to = null, int $limit = 50): array {
        $branchCond = getInventoryBranchConditionAlias('sr');
        $params = [];
        $sql = "SELECT sr.*, u.full_name AS received_by_name
                FROM stock_receiving sr
                LEFT JOIN users u ON u.id = sr.received_by
                WHERE 1=1";
        if ($branchCond) {
            $sql .= $branchCond;
        }
        if ($from) {
            $sql .= " AND sr.received_date >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to) {
            $sql .= " AND sr.received_date <= ?";
            $params[] = $to . ' 23:59:59';
        }
        $sql .= " ORDER BY sr.received_date DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get daily inventory usage for a date range
     */
    function getDailyUsage(PDO $pdo, string $from, string $to, ?int $branch_id = null): array {
        $params = [$from . ' 00:00:00', $to . ' 23:59:59'];
        $branchSql = '';
        if ($branch_id !== null) {
            $branchSql = ' AND im.branch_id = ?';
            $params[] = $branch_id;
        }
        $sql = "
            SELECT im.inventory_id, i.item_name, i.unit,
                   SUM(im.quantity) AS total_used,
                   COUNT(*) AS movement_count
            FROM inventory_movements im
            JOIN inventory i ON i.id = im.inventory_id
            WHERE im.movement_type IN ('stock_out', 'adjustment')
              AND im.created_at BETWEEN ? AND ?
              $branchSql
            GROUP BY im.inventory_id
            ORDER BY total_used DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get inventory valuation
     */
    function getInventoryValuation(PDO $pdo): array {
        $branchCond = getInventoryBranchConditionAlias('i');
        $sql = "SELECT i.*, (i.quantity * i.cost_price) AS total_value
                FROM inventory i WHERE i.deleted_at IS NULL AND i.status = 'active'";
        if ($branchCond) $sql .= $branchCond;
        $sql .= " ORDER BY total_value DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get fast/slow moving items based on usage
     */
    function getMovingItems(PDO $pdo, string $type = 'fast', int $limit = 10): array {
        $branchCond = getInventoryBranchConditionAlias('im');
        $params = [];
        $sql = "
            SELECT im.inventory_id, i.item_name, i.unit, i.quantity, i.min_stock,
                   SUM(im.quantity) AS total_used
            FROM inventory_movements im
            JOIN inventory i ON i.id = im.inventory_id
            WHERE im.movement_type = 'stock_out'
              AND im.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        if ($branchCond) {
            $sql .= $branchCond;
        }
        $sql .= " GROUP BY im.inventory_id";
        $sql .= $type === 'fast' ? " ORDER BY total_used DESC" : " ORDER BY total_used ASC";
        $sql .= " LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
