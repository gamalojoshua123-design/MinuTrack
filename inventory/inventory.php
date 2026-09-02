<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('inventory_view');

$page_title = 'Inventory Management';
$active_page = 'inventory';

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/
function logInventoryMovement(PDO $pdo, int $inventory_id, ?int $batch_id, string $movement_type, int $quantity, ?string $notes = null): void
{
    $performed_by = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    $stmt = $pdo->prepare("
        INSERT INTO inventory_movements (inventory_id, batch_id, movement_type, quantity, notes, performed_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$inventory_id, $batch_id, $movement_type, $quantity, $notes, $performed_by]);
}

function addInventoryBatch(PDO $pdo, int $inventory_id, int $quantity, ?string $received_at = null, ?string $expiry_date = null, ?string $notes = null): void
{
    if ($quantity <= 0) {
        throw new Exception('Batch quantity must be greater than 0');
    }

    $received_at = $received_at ?: date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO inventory_batches (inventory_id, batch_quantity, remaining_quantity, received_at, expiry_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$inventory_id, $quantity, $quantity, $received_at, $expiry_date ?: null]);

    $batch_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        UPDATE inventory
        SET quantity = quantity + ?, last_updated = NOW()
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$quantity, $inventory_id]);

    logInventoryMovement($pdo, $inventory_id, $batch_id, 'stock_in', $quantity, $notes);
}

function deductInventoryFIFO(PDO $pdo, int $inventory_id, int $quantity, string $movement_type = 'stock_out', ?string $notes = null): void
{
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0');
    }

    $stmt = $pdo->prepare("
        SELECT id, item_name
        FROM inventory
        WHERE id = ? AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$inventory_id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory) {
        throw new Exception('Inventory item not found');
    }

    // The true source of stock for FIFO is the sum of batch remaining quantities.
    // Check against that (not inventory.quantity) so a tallied/order-of-magnitude
    // mismatch between the two tables can never produce a bogus "insufficient
    // batch stock" error after the availability check already passed.
    $stmt = $pdo->prepare("
        SELECT id, remaining_quantity
        FROM inventory_batches
        WHERE inventory_id = ?
          AND remaining_quantity > 0
        ORDER BY received_at ASC, id ASC
    ");
    $stmt->execute([$inventory_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $available = 0;
    foreach ($batches as $batch) {
        $available += (int) $batch['remaining_quantity'];
    }

    if ($available < $quantity) {
        throw new Exception('Not enough stock available: requested ' . $quantity . ', only ' . $available . ' in stock');
    }

    $remainingToDeduct = $quantity;

    foreach ($batches as $batch) {
        if ($remainingToDeduct <= 0) {
            break;
        }

        $available_batch = (int) $batch['remaining_quantity'];
        if ($available_batch <= 0) {
            continue;
        }

        $deduct = min($available_batch, $remainingToDeduct);

        $update = $pdo->prepare("
            UPDATE inventory_batches
            SET remaining_quantity = remaining_quantity - ?
            WHERE id = ?
        ");
        $update->execute([$deduct, $batch['id']]);

        logInventoryMovement($pdo, $inventory_id, (int) $batch['id'], $movement_type, $deduct, $notes);

        $remainingToDeduct -= $deduct;
    }

    // Reconcile inventory.quantity to the true batch total so the two tables
    // never drift apart (self-heals any prior mismatch on the next deduction).
    syncInventoryQuantityFromBatches($pdo, $inventory_id);
}

function syncInventoryQuantityFromBatches(PDO $pdo, int $inventory_id): void
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(remaining_quantity), 0) AS total_remaining
        FROM inventory_batches
        WHERE inventory_id = ?
    ");
    $stmt->execute([$inventory_id]);
    $total = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        UPDATE inventory
        SET quantity = ?, last_updated = NOW()
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$total, $inventory_id]);
}

/*
|--------------------------------------------------------------------------
| Lookup Data
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT DISTINCT unit
    FROM inventory
    WHERE deleted_at IS NULL AND unit IS NOT NULL AND unit != ''
    ORDER BY unit
");
$stmt->execute();
$unit_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("
    SELECT DISTINCT category
    FROM inventory
    WHERE deleted_at IS NULL AND category IS NOT NULL AND category != ''
    ORDER BY category
");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("
    SELECT DISTINCT supplier
    FROM inventory
    WHERE deleted_at IS NULL AND supplier IS NOT NULL AND supplier != ''
    ORDER BY supplier
");
$suppliers = $stmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/inventory_functions.php';
$branchCondition = getInventoryBranchCondition();
$branchCondInv = getInventoryBranchConditionAlias('i');
$branchCondMov = getInventoryBranchConditionAlias('m');
$branchCondSr = getInventoryBranchConditionAlias('sr');
$branchJoin = '';

$stmt = $pdo->prepare("
    SELECT DISTINCT unit
    FROM inventory
    WHERE deleted_at IS NULL AND unit IS NOT NULL AND unit != '' $branchCondition
    ORDER BY unit
");
$stmt->execute();
$unit_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT DISTINCT category
    FROM inventory
    WHERE deleted_at IS NULL AND category IS NOT NULL AND category != '' $branchCondition
    ORDER BY category
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT DISTINCT supplier
    FROM inventory
    WHERE deleted_at IS NULL AND supplier IS NOT NULL AND supplier != '' $branchCondition
    ORDER BY supplier
");
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT id, item_name, unit, quantity, min_stock, category, supplier
    FROM inventory
    WHERE deleted_at IS NULL $branchCondition
    ORDER BY item_name ASC
");
$stmt->execute();
$inventory_select_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Add Inventory
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['item_name'])) {
            throw new Exception('Item name is required');
        }
        if (!isset($_POST['quantity']) || $_POST['quantity'] === '') {
            throw new Exception('Quantity is required');
        }
        if (!isset($_POST['min_stock']) || $_POST['min_stock'] === '') {
            throw new Exception('Minimum stock is required');
        }

        $item_name = trim($_POST['item_name']);
        $quantity = intval($_POST['quantity']);
        $min_stock = intval($_POST['min_stock']);
        $unit = !empty($_POST['unit']) ? trim($_POST['unit']) : 'piece';
        $category = !empty($_POST['category']) ? trim($_POST['category']) : 'Uncategorized';
        $supplier = !empty($_POST['supplier']) ? trim($_POST['supplier']) : null;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        if ($quantity < 0) {
            throw new Exception('Quantity cannot be negative');
        }

        $pdo->beginTransaction();

        // Scope the new item to the current branch (non-owners) so it shows up
        // in the branch-filtered inventory list instead of disappearing with a
        // NULL branch_id while the list is scoped to the user's branch.
        $branch_id = getCurrentBranchId();

        $stmt = $pdo->prepare("
            INSERT INTO inventory (branch_id, item_name, category, supplier, quantity, min_stock, unit, status, last_updated)
            VALUES (?, ?, ?, ?, 0, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $branch_id,
            $item_name,
            $category,
            $supplier,
            $min_stock,
            $unit
        ]);

        $inventory_id = (int) $pdo->lastInsertId();

        if ($quantity > 0) {
            addInventoryBatch($pdo, $inventory_id, $quantity, null, $expiry_date, 'Opening stock');
        }

        $pdo->commit();

        header("Location: inventory.php?message=" . urlencode("Inventory item added successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Update Inventory Details Only
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['inventory_id'])) {
            throw new Exception('Inventory ID is required');
        }
        if (empty($_POST['item_name'])) {
            throw new Exception('Item name is required');
        }
        if (!isset($_POST['min_stock']) || $_POST['min_stock'] === '') {
            throw new Exception('Minimum stock is required');
        }

        $stmt = $pdo->prepare("
            UPDATE inventory
            SET item_name = ?, category = ?, supplier = ?, min_stock = ?, unit = ?, last_updated = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([
            trim($_POST['item_name']),
            !empty($_POST['category']) ? trim($_POST['category']) : 'Uncategorized',
            !empty($_POST['supplier']) ? trim($_POST['supplier']) : null,
            intval($_POST['min_stock']),
            !empty($_POST['unit']) ? trim($_POST['unit']) : 'piece',
            intval($_POST['inventory_id'])
        ]);

        header("Location: inventory.php?message=" . urlencode("Inventory item updated successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Stock In
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_in_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['inventory_id'])) {
            throw new Exception('Inventory ID is required');
        }
        if (!isset($_POST['stock_in_qty']) || $_POST['stock_in_qty'] === '') {
            throw new Exception('Stock-in quantity is required');
        }

        $inventory_id = intval($_POST['inventory_id']);
        $stock_in_qty = floatval($_POST['stock_in_qty']);
        $expiry_date = !empty($_POST['stock_in_expiry_date']) ? $_POST['stock_in_expiry_date'] : null;
        $notes = !empty($_POST['stock_in_notes']) ? trim($_POST['stock_in_notes']) : null;

        if ($stock_in_qty <= 0) {
            throw new Exception('Stock-in quantity must be greater than 0');
        }

        $pdo->beginTransaction();
        addInventoryBatch($pdo, $inventory_id, $stock_in_qty, null, $expiry_date, $notes);
        $pdo->commit();

        header("Location: inventory.php?message=" . urlencode("Stock added successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Stock Out (FIFO)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_out_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['inventory_id'])) {
            throw new Exception('Inventory ID is required');
        }
        if (!isset($_POST['stock_out_qty']) || $_POST['stock_out_qty'] === '') {
            throw new Exception('Stock-out quantity is required');
        }

        $inventory_id = intval($_POST['inventory_id']);
        $stock_out_qty = floatval($_POST['stock_out_qty']);
        $notes = !empty($_POST['stock_out_notes']) ? trim($_POST['stock_out_notes']) : null;

        if ($stock_out_qty <= 0) {
            throw new Exception('Stock-out quantity must be greater than 0');
        }

        $pdo->beginTransaction();
        deductInventoryFIFO($pdo, $inventory_id, $stock_out_qty, 'stock_out', $notes);
        $pdo->commit();

        header("Location: inventory.php?message=" . urlencode("Stock deducted using FIFO successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Waste / Spoilage
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['waste_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['inventory_id'])) {
            throw new Exception('Inventory ID is required');
        }
        if (!isset($_POST['waste_qty']) || $_POST['waste_qty'] === '') {
            throw new Exception('Waste quantity is required');
        }

        $inventory_id = intval($_POST['inventory_id']);
        $waste_qty = floatval($_POST['waste_qty']);
        $notes = !empty($_POST['waste_notes']) ? trim($_POST['waste_notes']) : 'Waste / spoilage';

        if ($waste_qty <= 0) {
            throw new Exception('Waste quantity must be greater than 0');
        }

        $pdo->beginTransaction();
        deductInventoryFIFO($pdo, $inventory_id, $waste_qty, 'waste', $notes);
        $pdo->commit();

        header("Location: inventory.php?message=" . urlencode("Waste recorded successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Archive Inventory
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_inventory'])) {
    requireCsrfToken();
    try {
        if (empty($_POST['inventory_id'])) {
            throw new Exception('Inventory ID is required');
        }

        $stmt = $pdo->prepare("
            UPDATE inventory
            SET deleted_at = NOW(), status = 'archived'
            WHERE id = ?
        ");
        $stmt->execute([intval($_POST['inventory_id'])]);

        header("Location: inventory.php?message=" . urlencode("Inventory item archived successfully!") . "&type=success");
        exit;
    } catch (Exception $e) {
        header("Location: inventory.php?message=" . urlencode("Error: " . $e->getMessage()) . "&type=error");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| AJAX Requests
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {

    if ($_GET['action'] === 'get_inventory') {
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Inventory ID is required');
            }

            $branchCond = getInventoryBranchConditionAlias('i');
            $sql = "SELECT i.id, i.item_name, i.category, i.supplier, i.quantity, i.min_stock, i.unit, i.status, i.last_updated
                FROM inventory i
                WHERE i.id = ? AND i.deleted_at IS NULL";
            if ($branchCond) {
                $sql .= $branchCond;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($_GET['id'])]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inventory) {
                throw new Exception('Inventory item not found');
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $inventory]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'get_inventory_batches') {
        try {
            if (empty($_GET['id'])) {
                throw new Exception('Inventory ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT id, batch_quantity, remaining_quantity, received_at, expiry_date
                FROM inventory_batches
                WHERE inventory_id = ?
                ORDER BY received_at ASC, id ASC
            ");
            $stmt->execute([intval($_GET['id'])]);
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $batches]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'get_all_inventory_batches') {
        try {
            $branchCond = getInventoryBranchConditionAlias('i');
            $sql = "SELECT
                    i.id AS inventory_id,
                    i.item_name,
                    i.unit,
                    i.quantity AS current_stock,
                    i.min_stock,
                    b.id AS batch_id,
                    b.batch_quantity,
                    b.remaining_quantity,
                    b.received_at,
                    b.expiry_date
                FROM inventory i
                LEFT JOIN inventory_batches b ON i.id = b.inventory_id
                WHERE i.deleted_at IS NULL";
            if ($branchCond) {
                $sql .= $branchCond;
            }
            $sql .= " ORDER BY i.item_name ASC, b.received_at ASC, b.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$selected_unit = $_GET['unit'] ?? '';
$search_term = trim($_GET['search'] ?? '');
$selected_category = $_GET['category'] ?? '';
$selected_supplier = $_GET['supplier'] ?? '';
$filter_stock = $_GET['stock'] ?? '';
$sort_by = $_GET['sort'] ?? '';

/*
|--------------------------------------------------------------------------
| Inventory Query
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        i.id,
        i.item_name,
        i.category,
        i.supplier,
        i.quantity,
        i.min_stock,
        i.unit,
        i.last_updated,
        i.status
    FROM inventory i
    WHERE i.deleted_at IS NULL
";

$params = [];

if (!isOwner()) {
    $branch_id = getCurrentBranchId();
    if ($branch_id !== null) {
        $sql .= " AND i.branch_id = " . (int)$branch_id;
    }
}

if (!empty($selected_unit) && $selected_unit !== 'all') {
    $sql .= " AND i.unit = ?";
    $params[] = $selected_unit;
}

if (!empty($search_term)) {
    $sql .= " AND (i.item_name LIKE ? OR i.unit LIKE ? OR i.category LIKE ? OR i.supplier LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($selected_category)) {
    $sql .= " AND i.category = ?";
    $params[] = $selected_category;
}

if (!empty($selected_supplier)) {
    $sql .= " AND i.supplier = ?";
    $params[] = $selected_supplier;
}

if ($filter_stock === 'low') {
    $sql .= " AND i.quantity <= i.min_stock AND i.quantity > 0";
} elseif ($filter_stock === 'out') {
    $sql .= " AND i.quantity <= 0";
} elseif ($filter_stock === 'expiring') {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM inventory_batches bx
        WHERE bx.inventory_id = i.id
          AND bx.remaining_quantity > 0
          AND bx.expiry_date IS NOT NULL
          AND bx.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    )";
} elseif ($filter_stock === 'expired') {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM inventory_batches bx
        WHERE bx.inventory_id = i.id
          AND bx.remaining_quantity > 0
          AND bx.expiry_date IS NOT NULL
          AND bx.expiry_date < CURDATE()
    )";
}

// Stock-priority default: out-of-stock first, then almost-out-of-stock (<= min_stock),
// each tier ordered most-critical first, then the rest alphabetically.
$order = "
    ORDER BY
        CASE
            WHEN i.quantity <= 0 THEN 0
            WHEN i.quantity <= i.min_stock THEN 1
            ELSE 2
        END ASC,
        CASE WHEN i.quantity > 0 AND i.quantity <= i.min_stock THEN (i.min_stock - i.quantity) ELSE 0 END DESC,
        i.item_name ASC
";

switch ($sort_by) {
    case 'high_stock':
        $order = " ORDER BY i.quantity DESC, i.item_name ASC";
        break;
    case 'low_stock':
        $order = " ORDER BY i.quantity ASC, i.item_name ASC";
        break;
    case 'recent':
        $order = " ORDER BY i.last_updated DESC, i.item_name ASC";
        break;
}

$sql .= $order;

// Pagination
$items_per_page = intval($_GET['items_per_page'] ?? 15);
if (!in_array($items_per_page, [10, 15, 25, 50])) $items_per_page = 15;
$inv_current_page = max(1, intval($_GET['page'] ?? 1));

// Count total matching items
$count_sql = "SELECT COUNT(*) FROM (" . $sql . ") AS filtered";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items_filtered = (int) $count_stmt->fetchColumn();
$total_pages = (int) max(1, ceil($total_items_filtered / $items_per_page));

if ($inv_current_page > $total_pages) {
    $inv_current_page = $total_pages;
}

$offset = ($inv_current_page - 1) * $items_per_page;
$sql .= " LIMIT $items_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Unit Counts
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT unit, COUNT(*) as count
    FROM inventory i
    WHERE i.deleted_at IS NULL $branchCondInv
    GROUP BY unit
    ORDER BY unit
");
$stmt->execute();
$unit_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Stats (global counts, not limited to current page)
|--------------------------------------------------------------------------
*/
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_items,
        SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock_count,
        SUM(CASE WHEN quantity > 0 AND quantity <= min_stock THEN 1 ELSE 0 END) AS low_stock_count
    FROM inventory i
    WHERE i.deleted_at IS NULL $branchCondInv
");
$statsStmt->execute();
$globalStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$total_items = (int)($globalStats['total_items'] ?? 0);
$low_stock_count = (int)($globalStats['low_stock_count'] ?? 0);
$out_of_stock_count = (int)($globalStats['out_of_stock_count'] ?? 0);

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM inventory_batches bx
    INNER JOIN inventory i ON i.id = bx.inventory_id
    WHERE bx.remaining_quantity > 0
      AND bx.expiry_date IS NOT NULL
      AND bx.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      AND i.deleted_at IS NULL $branchCondInv
");
$stmt->execute();
$expiring_soon_count = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM inventory_batches bx
    INNER JOIN inventory i ON i.id = bx.inventory_id
    WHERE bx.remaining_quantity > 0
      AND bx.expiry_date IS NOT NULL
      AND bx.expiry_date < CURDATE()
      AND i.deleted_at IS NULL $branchCondInv
");
$stmt->execute();
$expired_count = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(m.quantity), 0)
    FROM inventory_movements m
    WHERE m.movement_type = 'stock_out'
      AND DATE(m.created_at) = CURDATE() $branchCondMov
");
$stmt->execute();
$daily_usage = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(m.quantity), 0)
    FROM inventory_movements m
    WHERE m.movement_type = 'stock_out'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $branchCondMov
");
$stmt->execute();
$weekly_usage = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(m.quantity), 0)
    FROM inventory_movements m
    WHERE m.movement_type = 'waste'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $branchCondMov
");
$stmt->execute();
$weekly_waste = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT i.item_name, COALESCE(SUM(m.quantity), 0) AS total_used
    FROM inventory i
    LEFT JOIN inventory_movements m
      ON i.id = m.inventory_id
      AND m.movement_type = 'stock_out'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    WHERE i.deleted_at IS NULL $branchCondInv
    GROUP BY i.id, i.item_name
    ORDER BY total_used DESC, i.item_name ASC
    LIMIT 5
");
$stmt->execute();
$fast_moving_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT i.item_name, COALESCE(SUM(m.quantity), 0) AS total_used
    FROM inventory i
    LEFT JOIN inventory_movements m
      ON i.id = m.inventory_id
      AND m.movement_type = 'stock_out'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    WHERE i.deleted_at IS NULL $branchCondInv
    GROUP BY i.id, i.item_name
    ORDER BY total_used ASC, i.item_name ASC
    LIMIT 5
");
$stmt->execute();
$slow_moving_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT i.item_name, SUM(m.quantity) AS waste_qty
    FROM inventory_movements m
    INNER JOIN inventory i ON i.id = m.inventory_id
    WHERE m.movement_type = 'waste'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND i.deleted_at IS NULL $branchCondInv
    GROUP BY i.id, i.item_name
    ORDER BY waste_qty DESC
    LIMIT 5
");
$stmt->execute();
$waste_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT i.id, i.item_name, i.unit, bx.expiry_date, bx.remaining_quantity
    FROM inventory_batches bx
    INNER JOIN inventory i ON i.id = bx.inventory_id
    WHERE i.deleted_at IS NULL $branchCondInv
      AND bx.remaining_quantity > 0
      AND bx.expiry_date IS NOT NULL
      AND bx.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY bx.expiry_date ASC, i.item_name ASC
");
$stmt->execute();
$expiring_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT i.id, i.item_name, i.unit, bx.expiry_date, bx.remaining_quantity
    FROM inventory_batches bx
    INNER JOIN inventory i ON i.id = bx.inventory_id
    WHERE i.deleted_at IS NULL $branchCondInv
      AND bx.remaining_quantity > 0
      AND bx.expiry_date IS NOT NULL
      AND bx.expiry_date < CURDATE()
    ORDER BY bx.expiry_date ASC, i.item_name ASC
");
$stmt->execute();
$expired_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get detailed waste log (last 30 days)
$stmt = $pdo->prepare("
    SELECT
        m.quantity,
        m.notes,
        m.created_at,
        i.item_name,
        i.unit,
        u.full_name as recorded_by
    FROM inventory_movements m
    JOIN inventory i ON m.inventory_id = i.id
    LEFT JOIN users u ON m.performed_by = u.id
    WHERE m.movement_type = 'waste'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) $branchCondInv
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->execute();
$waste_log = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total waste cost this month
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(m.quantity), 0) as total_waste_qty,
           COUNT(*) as total_waste_records
    FROM inventory_movements m
    WHERE m.movement_type = 'waste'
      AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) $branchCondMov
");
$stmt->execute();
$waste_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent stock movements for the history log
$stmt = $pdo->prepare("
    SELECT
        m.movement_type,
        m.quantity,
        m.notes,
        m.created_at,
        i.item_name,
        i.unit,
        u.full_name as performed_by_name
    FROM inventory_movements m
    JOIN inventory i ON m.inventory_id = i.id
    LEFT JOIN users u ON m.performed_by = u.id
    WHERE 1=1 $branchCondInv
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->execute();
$movement_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Page-specific styles for inventory */
        .stat-icon.icon-total { background: var(--blue-light); color: var(--blue); }
        .stat-icon.icon-low { background: var(--amber-light); color: var(--amber); }
        .stat-icon.icon-out { background: var(--red-light); color: var(--red); }

        .status-good { background: var(--green-light); color: var(--green); }
        .status-out { background: var(--red-light); color: var(--red); }
        .status-warning { background: var(--amber-light); color: #b45309; }
        .text-warning { color: var(--amber) !important; }

        /* Pagination */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--border);
            background: var(--bg);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .pagination-info {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-secondary);
            background: var(--bg-card);
            border: 1px solid var(--border);
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .page-btn:hover:not(.disabled):not(.active) {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-btn.disabled {
            opacity: 0.4;
            cursor: default;
        }

        .page-dots {
            font-size: 0.75rem;
            color: var(--text-muted);
            padding: 0 4px;
        }

        /* Client-side table pagination (same pattern as tools/archive.php) */
        .table-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.25rem 1.25rem 0.85rem;
        }
        .table-footer .table-pagination {
            border-top: none;
            padding: 0.35rem 0;
        }
        .table-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.35rem;
            padding: 0.85rem 1.25rem;
            border-top: 1px solid var(--border);
        }
        .table-pagination button {
            padding: 0.35rem 0.65rem;
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text-secondary);
            border-radius: var(--radius);
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
            transition: var(--transition);
            min-width: 32px;
        }
        .table-pagination button:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(243, 121, 2, 0.05);
        }
        .table-pagination button.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .table-pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .unit-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid var(--border);
        }

        .filter-bar {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .unit-filters {
            display: flex;
            flex-wrap: wrap;
            flex: 1;
        }

        .unit-filters .unit-btn {
            margin: 0 0.5rem 0.5rem 0;
        }

        .unit-btn {
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            background: var(--bg);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .unit-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .unit-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Filter toolbar: exclusive flex stack with margin-based spacing
           (flex `gap` is unsupported on old CefSharp/IE engines and caused
           wrapped controls to pack into the table below) */
        .filter-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: -0.75rem;
        }

        .filter-tools > * {
            margin: 0 0.75rem 0.75rem 0;
        }

        /* Keep the toolbar above the table card in stacking order so table
           content that escapes the card's overflow:visible can never paint
           over the filter controls or vice versa */
        .content-area > .card {
            position: relative;
            z-index: 0;
        }

        /* btn-stock-in, btn-stock-out, btn-archive styles removed - now using .btn-icon classes from admin.css */

        .unit-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .unit-option {
            padding: 0.5rem;
            text-align: center;
            background: var(--bg);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border);
            font-size: 0.85rem;
        }

        .unit-option:hover {
            background: var(--primary-light);
            border-color: var(--primary);
        }

        .unit-option.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .notification-panel {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 380px;
            max-width: 90vw;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: 2000;
            display: none;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .notification-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .notification-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .notification-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .notification-body {
            max-height: 450px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .notification-item:hover {
            background: var(--bg);
        }

        .notification-item.critical {
            border-left: 3px solid var(--red);
            background: var(--red-light);
        }

        .notification-item.warning {
            border-left: 3px solid var(--amber);
            background: var(--amber-light);
        }

        .notification-item .item-info { flex: 1; }

        .notification-item .item-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.85rem;
            margin-bottom: 0.15rem;
        }

        .notification-item .item-stock {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .notification-item .stock-critical { color: var(--red); font-weight: 600; }
        .notification-item .stock-warning { color: var(--amber); font-weight: 600; }

        .notification-item .update-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .notification-item .update-btn:hover {
            background: var(--primary-dark);
        }

        .empty-notification {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-notification i {
            font-size: 2.5rem;
            color: var(--green);
            margin-bottom: 0.5rem;
            display: block;
        }

        .empty-notification p {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .report-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }

        .report-row:last-child {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-tools {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                margin-bottom: 0;
            }

            .filter-tools > * {
                margin: 0 0 0.75rem 0;
                width: 100%;
                min-width: 0 !important;
            }

            .unit-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .notification-panel {
                top: 60px;
                right: 10px;
                width: calc(100% - 20px);
            }

            .unit-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .unit-options {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($_GET['message'])): ?>
                    <div class="message <?php echo (($_GET['type'] ?? 'success') === 'error') ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-total"><i class='bx bx-package'></i></div>
                            <div>
                                <div class="stat-title">Total Items</div>
                                <div class="stat-value"><?php echo $total_items; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-low"><i class='bx bx-error'></i></div>
                            <div>
                                <div class="stat-title">Low Stock</div>
                                <div class="stat-value"><?php echo $low_stock_count; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-out"><i class='bx bx-error-circle'></i></div>
                            <div>
                                <div class="stat-title">Out of Stock</div>
                                <div class="stat-value"><?php echo $out_of_stock_count; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-low"><i class='bx bx-time-five'></i></div>
                            <div>
                                <div class="stat-title">Expiring Soon</div>
                                <div class="stat-value"><?php echo $expiring_soon_count; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-total"><i class='bx bx-bar-chart-alt-2'></i></div>
                            <div>
                                <div class="stat-title">Weekly Usage</div>
                                <div class="stat-value"><?php echo $weekly_usage; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($low_stock_count > 0 || $out_of_stock_count > 0): ?>
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                    <i class='bx bx-error-circle' style="font-size:1.5rem; color:#ea580c;"></i>
                    <span style="flex:1;">
                        <strong style="color:#c2410c;">Inventory Alert:</strong>
                        <?php if ($out_of_stock_count > 0): ?>
                            <span style="color:#dc2626; font-weight:600;"><?= $out_of_stock_count ?> item(s) out of stock.</span>
                        <?php endif; ?>
                        <?php if ($low_stock_count > 0): ?>
                            <span style="color:#ea580c; font-weight:600;"><?= $low_stock_count ?> item(s) low on stock.</span>
                        <?php endif; ?>
                        Consider restocking soon to avoid shortages.
                    </span>
                    <a href="inventory_count.php" style="display:inline-flex; align-items:center; gap:0.375rem; background:#f3f4f6; color:#374151; padding:0.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:600; font-size:0.875rem;">
                        <i class='bx bx-clipboard'></i> Count
                    </a>
                    <a href="inventory_reports.php?report=low_stock" style="display:inline-flex; align-items:center; gap:0.375rem; background:#f3f4f6; color:#374151; padding:0.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:600; font-size:0.875rem;">
                        <i class='bx bx-bar-chart-alt-2'></i> Reports
                    </a>
                </div>
                <?php endif; ?>

                <div class="filter-bar" style="display:block;">
                    <div class="unit-filters" style="margin-bottom:1rem;">
                        <a href="inventory.php?<?php echo http_build_query(array_filter([
                            'search' => $search_term,
                            'category' => $selected_category,
                            'supplier' => $selected_supplier,
                            'stock' => $filter_stock,
                            'sort' => $sort_by
                        ])); ?>" class="unit-btn <?php echo empty($selected_unit) ? 'active' : ''; ?>">
                            All Units
                        </a>
                        <?php
                        $unit_order = ['piece', 'ml', 'portion', 'bottle', 'scoop', 'cup'];
                        foreach ($unit_order as $unit):
                            $count = 0;
                            foreach ($unit_counts as $uc) {
                                if ($uc['unit'] == $unit) {
                                    $count = $uc['count'];
                                }
                            }
                            ?>
                            <a href="inventory.php?<?php echo http_build_query(array_filter([
                                'unit' => $unit,
                                'search' => $search_term,
                                'category' => $selected_category,
                                'supplier' => $selected_supplier,
                                'stock' => $filter_stock,
                                'sort' => $sort_by
                            ])); ?>" class="unit-btn <?php echo $selected_unit === $unit ? 'active' : ''; ?>">
                                <?php echo ucfirst($unit); ?> (<?php echo $count; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <form method="GET" class="filter-tools">
                        <?php if (!empty($selected_unit)): ?>
                            <input type="hidden" name="unit" value="<?php echo htmlspecialchars($selected_unit); ?>">
                        <?php endif; ?>

                        <input type="text" class="form-control" style="width:auto; min-width:180px;" name="search"
                            placeholder="Search items..." value="<?php echo htmlspecialchars($search_term); ?>">

                        <select class="form-control" style="width:auto; min-width:170px;" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select class="form-control" style="width:auto; min-width:170px;" name="supplier">
                            <option value="">All Suppliers</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?php echo htmlspecialchars($sup); ?>" <?php echo $selected_supplier === $sup ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sup); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select class="form-control" style="width:auto; min-width:170px;" name="stock">
                            <option value="">All Stock</option>
                            <option value="low" <?php echo $filter_stock === 'low' ? 'selected' : ''; ?>>Low Stock
                            </option>
                            <option value="out" <?php echo $filter_stock === 'out' ? 'selected' : ''; ?>>Out of Stock
                            </option>
                            <option value="expiring" <?php echo $filter_stock === 'expiring' ? 'selected' : ''; ?>>
                                Expiring Soon</option>
                            <option value="expired" <?php echo $filter_stock === 'expired' ? 'selected' : ''; ?>>Expired
                            </option>
                        </select>

                        <select class="form-control" style="width:auto; min-width:170px;" name="sort">
                            <option value="">Sort By</option>
                            <option value="high_stock" <?php echo $sort_by === 'high_stock' ? 'selected' : ''; ?>>Highest
                                Stock</option>
                            <option value="low_stock" <?php echo $sort_by === 'low_stock' ? 'selected' : ''; ?>>Lowest
                                Stock</option>
                            <option value="recent" <?php echo $sort_by === 'recent' ? 'selected' : ''; ?>>Recently Updated
                            </option>
                        </select>

                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-search'></i> Apply
                        </button>

                        <a href="inventory.php" class="btn btn-outline">Reset</a>
                    </form>
                </div>

                <div class="card" style="overflow:visible;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class='bx bx-package'></i>
                            Inventory Items
                            <span style="font-size:0.7rem;font-weight:500;color:var(--text-muted);margin-left:6px;">(<?php echo $total_items_filtered; ?>)</span>
                        </h3>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button class="btn btn-sm" style="background:var(--bg);color:var(--text-secondary);border:1px solid var(--border);" onclick="viewAllBatches()">
                                <i class='bx bx-list-ul'></i> FIFO Overview
                            </button>

                            <button class="btn btn-sm" style="background:var(--bg);color:var(--text-secondary);border:1px solid var(--border);" onclick="showWasteForm()">
                                <i class='bx bx-trash'></i> Waste
                            </button>

                            <button class="btn btn-primary btn-sm" onclick="showAddInventoryForm()">
                                <i class='bx bx-plus'></i> Add New Item
                            </button>
                        </div>
                    </div>

                    <script>
                    // Define action menu functions early so inline onclick handlers work
                    function showAddInventoryForm(){var m=document.getElementById('inventory-modal');if(m)m.style.display='flex';}
                    function showWasteForm(){var m=document.getElementById('waste-modal');if(m){m.style.display='flex';}}
                    function closeModal(id){var m=document.getElementById(id);if(m)m.style.display='none';}
                    function editInventory(id){var m=document.getElementById('inventory-modal');if(m)m.style.display='flex';}
                    function stockInInventory(id){var m=document.getElementById('stock-in-modal');if(m){m.style.display='flex';var f=document.getElementById('stock_in_inventory_id');if(f)f.value=id;}}
                    function stockOutInventory(id){var m=document.getElementById('stock-out-modal');if(m){m.style.display='flex';var f=document.getElementById('stock_out_inventory_id');if(f)f.value=id;}}
                    function wasteInventory(id){var m=document.getElementById('waste-modal');if(m){m.style.display='flex';var s=document.getElementById('waste_item_select');if(s)s.value=id;}}
                    function viewAllBatches(){var m=document.getElementById('batch-modal');if(m)m.style.display='flex';}
                    </script>
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <span style="font-size:0.82rem;color:var(--text-secondary);">
                                Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $items_per_page, $total_items_filtered); ?> of <?php echo $total_items_filtered; ?> items
                            </span>
                        </div>
                        <div class="table-toolbar-right">
                            <div class="per-page-select">
                                <span>Show</span>
                                <form method="GET" style="display:inline;">
                                    <?php if ($search_term): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>"><?php endif; ?>
                                    <?php if ($selected_unit): ?><input type="hidden" name="unit" value="<?php echo htmlspecialchars($selected_unit); ?>"><?php endif; ?>
                                    <?php if ($selected_category): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>"><?php endif; ?>
                                    <?php if ($selected_supplier): ?><input type="hidden" name="supplier" value="<?php echo htmlspecialchars($selected_supplier); ?>"><?php endif; ?>
                                    <?php if ($filter_stock): ?><input type="hidden" name="stock" value="<?php echo htmlspecialchars($filter_stock); ?>"><?php endif; ?>
                                    <?php if ($sort_by): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>"><?php endif; ?>
                                    <select name="items_per_page" onchange="this.form.submit()">
                                        <?php foreach ([10, 15, 25, 50] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo $items_per_page == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <span>entries</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="padding-top:0;">
                        <div class="table-container" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Unit</th>
                                        <th>Current Stock</th>
                                        <th>Min Stock</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inventory_items)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                <div class="empty-state">
                                                    <i class='bx bx-package'></i>
                                                    <p>No inventory items found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($inventory_items as $item):
                                            $status_class = 'status-active';
                                            $status_text = 'In Stock';

                                            $item_expiring = false;
                                            $item_expired = false;

                                            foreach ($expiring_items as $expItem) {
                                                if ((int) $expItem['id'] === (int) $item['id']) {
                                                    $item_expiring = true;
                                                    break;
                                                }
                                            }

                                            foreach ($expired_items as $expItem) {
                                                if ((int) $expItem['id'] === (int) $item['id']) {
                                                    $item_expired = true;
                                                    break;
                                                }
                                            }

                                            if ($item['quantity'] <= 0) {
                                                $status_class = 'status-inactive';
                                                $status_text = 'Out of Stock';
                                            } elseif ($item_expired) {
                                                $status_class = 'status-inactive';
                                                $status_text = 'Expired';
                                            } elseif ($item_expiring) {
                                                $status_class = 'status-low';
                                                $status_text = 'Expiring Soon';
                                            } elseif ($item['quantity'] <= $item['min_stock']) {
                                                $status_class = 'status-low';
                                                $status_text = 'Low Stock';
                                            }
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></td>
                                                <td><?php echo htmlspecialchars($item['supplier'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="unit-badge">
                                                        <?php echo htmlspecialchars($item['unit'] ?? 'piece'); ?>
                                                    </span>
                                                </td>
                                                <td class="<?php echo $item['quantity'] <= $item['min_stock'] ? 'text-warning' : ''; ?>">
                                                    <?php echo (int) $item['quantity']; ?>
                                                </td>
                                                <td><?php echo (int) $item['min_stock']; ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($item['last_updated'])); ?>
                                                </td>
                                                <td>
                                                    <div class="action-dropdown">
                                                        <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)" title="Actions">
                                                            <i class='bx bx-dots-vertical-rounded'></i>
                                                        </button>
                                                        <div class="action-dropdown-menu">
                                                            <button class="action-edit" onclick="editInventory(<?php echo (int) $item['id']; ?>);closeAllDropdowns()">
                                                                <i class='bx bx-edit'></i> Edit Item
                                                            </button>
                                                            <button class="action-link" onclick="stockInInventory(<?php echo (int) $item['id']; ?>);closeAllDropdowns()">
                                                                <i class='bx bx-plus-circle'></i> Stock In
                                                            </button>
                                                            <button class="action-delete" onclick="stockOutInventory(<?php echo (int) $item['id']; ?>);closeAllDropdowns()">
                                                                <i class='bx bx-minus-circle'></i> Stock Out
                                                            </button>
                                                            <div class="action-dropdown-divider"></div>
                                                            <button class="action-archive" onclick="wasteInventory(<?php echo (int) $item['id']; ?>);closeAllDropdowns()">
                                                                <i class='bx bx-trash'></i> Record Waste
                                                            </button>
                                                            <form method="POST" onsubmit="return askConfirm(event, 'Archive this item?')" style="margin:0;">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="inventory_id" value="<?php echo (int) $item['id']; ?>">
                                                                <button type="submit" name="archive_inventory" class="action-delete">
                                                                    <i class='bx bx-archive-in'></i> Archive
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <?php
                            // Build query string for pagination links
                            $pagination_params = array_filter([
                                'search' => $search_term,
                                'unit' => $selected_unit,
                                'category' => $selected_category,
                                'supplier' => $selected_supplier,
                                'stock' => $filter_stock,
                                'sort' => $sort_by,
                                'items_per_page' => $items_per_page != 15 ? $items_per_page : null,
                            ]);
                            ?>
                            <div class="pagination-bar">
                                <div class="pagination-info">
                                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $items_per_page, $total_items_filtered); ?> of <?php echo $total_items_filtered; ?> items
                                </div>
                                <div class="pagination-controls">
                                    <?php if ($inv_current_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $inv_current_page - 1])); ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $inv_current_page - 2);
                                    $end_page = min($total_pages, $inv_current_page + 2);
                                    if ($start_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" class="page-btn">1</a>
                                        <?php if ($start_page > 2): ?><span class="page-dots">...</span><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $p])); ?>" class="page-btn <?php echo $p === $inv_current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                                    <?php endif; ?>

                                    <?php if ($inv_current_page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $inv_current_page + 1])); ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="pagination-bar">
                                <div class="pagination-info">
                                    <?php echo $total_items_filtered; ?> item<?php echo $total_items_filtered !== 1 ? 's' : ''; ?> total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-bar-chart-alt-2'></i> Usage Reports</h3>
                        </div>
                        <div class="card-body">
                            <div class="report-row">
                                <span>Daily usage</span><strong><?php echo $daily_usage; ?></strong>
                            </div>
                            <div class="report-row">
                                <span>Weekly usage</span><strong><?php echo $weekly_usage; ?></strong>
                            </div>
                            <div class="report-row">
                                <span>Weekly waste</span><strong><?php echo $weekly_waste; ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-trending-up'></i> Fast Moving Items</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($fast_moving_items)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-trending-up'></i>
                                    <p>No data yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($fast_moving_items as $row): ?>
                                    <div class="report-row">
                                        <span><?php echo htmlspecialchars($row['item_name']); ?></span><strong><?php echo (int) $row['total_used']; ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-trending-down'></i> Slow Moving Items</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($slow_moving_items)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-trending-down'></i>
                                    <p>No data yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($slow_moving_items as $row): ?>
                                    <div class="report-row">
                                        <span><?php echo htmlspecialchars($row['item_name']); ?></span><strong><?php echo (int) $row['total_used']; ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-trash'></i> Waste / Spoilage</h3>
                        </div>
                        <div class="card-body">
                            <div style="display:flex;gap:1rem;margin-bottom:0.75rem;">
                                <div style="flex:1;background:var(--red-light);border-radius:8px;padding:0.6rem 0.85rem;">
                                    <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em;color:var(--red);">Total Wasted (30d)</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:var(--text-primary);"><?php echo (int)$waste_summary['total_waste_qty']; ?> items</div>
                                </div>
                                <div style="flex:1;background:var(--amber-light);border-radius:8px;padding:0.6rem 0.85rem;">
                                    <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.03em;color:#b45309;">Records</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:var(--text-primary);"><?php echo (int)$waste_summary['total_waste_records']; ?></div>
                                </div>
                            </div>
                            <?php if (empty($waste_items)): ?>
                                <div class="empty-state" style="padding:1rem;">
                                    <p style="font-size:0.82rem;">No waste by item yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($waste_items as $row): ?>
                                    <div class="report-row">
                                        <span><?php echo htmlspecialchars($row['item_name']); ?></span><strong style="color:var(--red);"><?php echo (int) $row['waste_qty']; ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-time-five'></i> Expiry Alerts</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expiring_items) && empty($expired_items)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-check-circle'></i>
                                    <p>No expiry alerts</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($expired_items, 0, 3) as $row): ?>
                                    <div class="report-row">
                                        <span><?php echo htmlspecialchars($row['item_name']); ?>
                                            <span class="status-badge status-inactive" style="font-size:0.65rem; margin-left:0.25rem;">Expired</span></span>
                                        <strong><?php echo htmlspecialchars($row['expiry_date']); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                                <?php foreach (array_slice($expiring_items, 0, 3) as $row): ?>
                                    <div class="report-row">
                                        <span><?php echo htmlspecialchars($row['item_name']); ?>
                                            <span class="status-badge status-low" style="font-size:0.65rem; margin-left:0.25rem;">Soon</span></span>
                                        <strong><?php echo htmlspecialchars($row['expiry_date']); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Waste Log Detail -->
                <div class="card" style="margin-top:1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-trash'></i> Waste Log</h3>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:0.7rem;color:var(--text-muted);font-weight:500;">Last 30 days</span>
                            <button class="btn btn-sm" style="background:var(--bg);color:var(--text-secondary);border:1px solid var(--border);padding:0.3rem 0.7rem;font-size:0.75rem;" onclick="showWasteForm()">
                                <i class='bx bx-plus'></i> Record Waste
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($waste_log)): ?>
                            <div style="text-align:center;padding:2.5rem 1rem;">
                                <div style="width:48px;height:48px;border-radius:12px;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:1.5rem;color:var(--green);"><i class='bx bx-check-circle'></i></div>
                                <p style="font-size:0.85rem;font-weight:600;color:var(--text-primary);margin-bottom:2px;">No waste recorded</p>
                                <p style="font-size:0.75rem;color:var(--text-muted);">Great job! No spoilage or waste in the last 30 days.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table" id="wasteLogTable">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty Wasted</th>
                                            <th>Reason</th>
                                            <th>Recorded By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($waste_log as $wl): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($wl['item_name']); ?></strong></td>
                                                <td>
                                                    <span style="font-weight:700;color:var(--red);">-<?php echo (int)$wl['quantity']; ?></span>
                                                    <span style="font-size:0.7rem;color:var(--text-muted);margin-left:2px;"><?php echo htmlspecialchars($wl['unit'] ?? 'pcs'); ?></span>
                                                </td>
                                                <td style="max-width:200px;">
                                                    <?php if (!empty($wl['notes'])): ?>
                                                        <span style="font-size:0.78rem;color:var(--text-secondary);display:inline-block;background:var(--bg);padding:2px 8px;border-radius:4px;"><?php echo htmlspecialchars($wl['notes']); ?></span>
                                                    <?php else: ?>
                                                        <span style="font-size:0.75rem;color:var(--text-muted);">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size:0.78rem;"><?php echo htmlspecialchars($wl['recorded_by'] ?? 'System'); ?></td>
                                                <td style="font-size:0.78rem;color:var(--text-muted);"><?php echo date('M j, g:i A', strtotime($wl['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-footer">
                                <span class="table-info" id="wasteLogInfo"></span>
                                <div class="table-pagination" id="wasteLogPagination"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stock Movement History -->
                <div class="card" style="margin-top:1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-history'></i> Stock Movement History</h3>
                        <span style="font-size:0.7rem;color:var(--text-muted);font-weight:500;">Last 20 movements</span>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($movement_history)): ?>
                            <div style="text-align:center;padding:2rem;color:var(--text-muted);">
                                <i class='bx bx-history' style="font-size:2rem;opacity:0.5;display:block;margin-bottom:0.5rem;"></i>
                                <p style="font-size:0.85rem;">No stock movements recorded yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table" id="movementTable">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Notes</th>
                                            <th>By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($movement_history as $mv):
                                            $type_color = 'var(--green)';
                                            $type_bg = 'var(--green-light)';
                                            $type_label = 'Stock In';
                                            $qty_prefix = '+';
                                            if ($mv['movement_type'] === 'stock_out') {
                                                $type_color = 'var(--amber)'; $type_bg = 'var(--amber-light)'; $type_label = 'Out'; $qty_prefix = '-';
                                            } elseif ($mv['movement_type'] === 'waste') {
                                                $type_color = 'var(--red)'; $type_bg = 'var(--red-light)'; $type_label = 'Waste'; $qty_prefix = '-';
                                            } elseif ($mv['movement_type'] === 'adjustment') {
                                                $type_color = 'var(--purple)'; $type_bg = 'var(--purple-light)'; $type_label = 'Adjust'; $qty_prefix = '';
                                            } elseif ($mv['movement_type'] === 'return') {
                                                $type_color = 'var(--blue)'; $type_bg = 'var(--blue-light)'; $type_label = 'Return'; $qty_prefix = '+';
                                            }
                                        ?>
                                            <tr>
                                                <td><span class="status-badge" style="background:<?php echo $type_bg; ?>;color:<?php echo $type_color; ?>;"><?php echo $type_label; ?></span></td>
                                                <td><strong><?php echo htmlspecialchars($mv['item_name']); ?></strong></td>
                                                <td style="font-weight:700;color:<?php echo $type_color; ?>;"><?php echo $qty_prefix . (int)$mv['quantity']; ?> <?php echo htmlspecialchars($mv['unit'] ?? 'pcs'); ?></td>
                                                <td style="font-size:0.78rem;color:var(--text-muted);max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($mv['notes'] ?? '-'); ?></td>
                                                <td style="font-size:0.78rem;"><?php echo htmlspecialchars($mv['performed_by_name'] ?? 'System'); ?></td>
                                                <td style="font-size:0.78rem;color:var(--text-muted);"><?php echo date('M j, g:i A', strtotime($mv['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-footer">
                                <span class="table-info" id="movementInfo"></span>
                                <div class="table-pagination" id="movementPagination"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h4><i class='bx bx-bell'></i> Inventory Alerts</h4>
            <button class="notification-close" aria-label="Close notifications" onclick="closeNotificationPanel()"><i class='bx bx-x'></i></button>
        </div>
        <div class="notification-body" id="notificationBody"></div>
    </div>

    <!-- Inventory Modal -->
    <div class="modal" id="inventory-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="inventory-modal-title">Add New Inventory Item</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('inventory-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="inventory-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="inventory_id">

                    <div class="form-group">
                        <label class="form-label">Item Name *</label>
                        <input type="text" class="form-control" id="inventory_item_name" name="item_name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" id="inventory_category" name="category"
                            placeholder="e.g. Cheese, Bread, Drinks">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" id="inventory_supplier" name="supplier"
                            placeholder="e.g. ABC Supplier">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unit Type *</label>
                        <div class="unit-options" id="unit-options">
                            <div class="unit-option" data-unit="piece">Piece</div>
                            <div class="unit-option" data-unit="ml">ml</div>
                            <div class="unit-option" data-unit="portion">Portion</div>
                            <div class="unit-option" data-unit="bottle">Bottle</div>
                            <div class="unit-option" data-unit="scoop">Scoop</div>
                            <div class="unit-option" data-unit="cup">Cup</div>
                        </div>
                        <input type="hidden" name="unit" id="inventory_unit" value="piece">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="inventory_quantity" name="quantity" min="0"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Opening Expiry Date</label>
                        <input type="date" class="form-control" id="inventory_expiry_date" name="expiry_date">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Minimum Stock Level *</label>
                        <input type="number" class="form-control" id="inventory_min_stock" name="min_stock" min="0"
                            required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline"
                            onclick="closeModal('inventory-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_inventory" id="inventory-submit-btn">
                            Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock In Modal -->
    <div class="modal" id="stock-in-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Stock In</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('stock-in-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="stock_in_inventory_id">
                    <input type="hidden" name="stock_in_inventory" value="1">

                    <div class="form-group">
                        <label class="form-label">Quantity to Add *</label>
                        <input type="number" class="form-control" name="stock_in_qty" id="stock_in_qty" min="1"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" name="stock_in_expiry_date" id="stock_in_expiry_date">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="stock_in_notes" id="stock_in_notes"
                            placeholder="Optional note">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline"
                            onclick="closeModal('stock-in-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-plus'></i> Confirm Stock In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div class="modal" id="stock-out-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Stock Out</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('stock-out-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="stock_out_inventory_id">
                    <input type="hidden" name="stock_out_inventory" value="1">

                    <div class="form-group">
                        <label class="form-label">Quantity to Deduct *</label>
                        <input type="number" class="form-control" name="stock_out_qty" id="stock_out_qty" min="1"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="stock_out_notes" id="stock_out_notes"
                            placeholder="Optional note">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline"
                            onclick="closeModal('stock-out-modal')">Cancel</button>
                        <button type="submit" class="btn btn-archive">
                            <i class='bx bx-minus'></i> Confirm Stock Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Waste Modal -->
    <div class="modal" id="waste-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class='bx bx-trash' style="margin-right:6px;"></i> Record Waste / Spoilage</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('waste-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="waste_inventory_id">
                    <input type="hidden" name="waste_inventory" value="1">

                    <div class="form-group">
                        <label class="form-label">Select Item *</label>
                        <select class="form-control" id="waste_item_select" name="inventory_id" required onchange="updateWasteMax(this)">
                            <option value="">Select item</option>
                            <?php foreach ($inventory_items as $item): ?>
                                <option value="<?php echo (int) $item['id']; ?>" data-stock="<?php echo (int) $item['quantity']; ?>" data-unit="<?php echo htmlspecialchars($item['unit'] ?? 'piece'); ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?> (Stock: <?php echo (int) $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? 'piece'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Waste Quantity *</label>
                        <input type="number" class="form-control" name="waste_qty" id="waste_qty" min="1" required>
                        <small id="waste_stock_hint" style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;display:block;"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason *</label>
                        <select class="form-control" id="waste_reason_select" onchange="updateWasteNotes(this)">
                            <option value="">Select reason</option>
                            <option value="Expired">Expired</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Spoilage">Spoilage</option>
                            <option value="Contaminated">Contaminated</option>
                            <option value="Quality issue">Quality issue</option>
                            <option value="Overcooked / Burnt">Overcooked / Burnt</option>
                            <option value="Dropped / Spilled">Dropped / Spilled</option>
                            <option value="other">Other (type below)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <input type="text" class="form-control" name="waste_notes" id="waste_notes"
                            placeholder="Optional details...">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline"
                            onclick="closeModal('waste-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--red);">
                            <i class='bx bx-trash'></i> Record Waste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FIFO Batch Modal -->
    <div class="modal" id="batch-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="batch-modal-title">FIFO Batches</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('batch-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body" id="batch-modal-body">
                <p class="text-muted">Loading batches...</p>
            </div>
        </div>
    </div>

    <script>
    /* ES5 FALLBACK for older tablet/CefSharp engines that cannot parse ES6.
       Runs first; modern browsers override these with the ES6 definitions below. */
    (function () {
        function el(id) { return document.getElementById(id); }
        function forEachNode(list, fn) { for (var i = 0; i < list.length; i++) fn(list[i], i); }
        function xhrJSON(url, done, fail) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try { done(JSON.parse(xhr.responseText)); }
                        catch (e) { if (fail) fail(e); }
                    } else if (fail) { fail(new Error('HTTP ' + xhr.status)); }
                }
            };
            xhr.send();
        }
        function resetUnitSelection(unit) {
            forEachNode(document.querySelectorAll('.unit-option'), function (opt) {
                opt.classList.remove('selected');
                if (opt.getAttribute('data-unit') === unit) opt.classList.add('selected');
            });
        }

        function showAddInventoryForm() {
            var modal = el('inventory-modal');
            if (!modal) return;
            var title = el('inventory-modal-title');
            if (title) title.textContent = 'Add New Inventory Item';
            var form = el('inventory-form');
            if (form) form.reset();
            var id = el('inventory_id');
            if (id) id.value = '';
            var qi = el('inventory_quantity');
            if (qi) { qi.disabled = false; qi.required = true; qi.value = ''; }
            var ed = el('inventory_expiry_date');
            if (ed) ed.value = '';
            var u = el('inventory_unit');
            if (u) u.value = 'piece';
            resetUnitSelection('piece');
            var btn = el('inventory-submit-btn');
            if (btn) { btn.name = 'add_inventory'; btn.textContent = 'Add Item'; btn.disabled = false; }
            modal.style.display = 'flex';
        }

        function editInventory(id) {
            var modal = el('inventory-modal');
            if (!modal) return;
            var title = el('inventory-modal-title');
            if (title) title.textContent = 'Edit Inventory Item';
            var hid = el('inventory_id');
            if (hid) hid.value = id;
            var btn = el('inventory-submit-btn');
            if (btn) { btn.name = 'update_inventory'; btn.textContent = 'Update Item'; btn.disabled = true; }
            xhrJSON('inventory.php?action=get_inventory&id=' + encodeURIComponent(id), function (result) {
                if (result && result.success && result.data) {
                    var d = result.data;
                    var ni = el('inventory_item_name'); if (ni) ni.value = d.item_name || '';
                    var nc = el('inventory_category'); if (nc) nc.value = d.category || '';
                    var ns = el('inventory_supplier'); if (ns) ns.value = d.supplier || '';
                    var qi = el('inventory_quantity');
                    if (qi) { qi.value = d.quantity || ''; qi.disabled = true; qi.required = false; }
                    var ed = el('inventory_expiry_date'); if (ed) ed.value = '';
                    var ms = el('inventory_min_stock'); if (ms) ms.value = d.min_stock || '';
                    var unit = d.unit || 'piece';
                    var u = el('inventory_unit'); if (u) u.value = unit;
                    resetUnitSelection(unit);
                    if (btn) btn.disabled = false;
                    modal.style.display = 'flex';
                } else {
                    showToastMsg((result && result.error) || 'Error loading inventory data', 'error');
                    if (btn) btn.disabled = false;
                }
            }, function () {
                showToastMsg('Error loading inventory data', 'error');
                if (btn) btn.disabled = false;
            });
        }

        function closeModal(modalId) {
            var m = el(modalId);
            if (m) m.style.display = 'none';
            if (modalId === 'inventory-modal') {
                var btn = el('inventory-submit-btn');
                if (btn) btn.disabled = false;
            }
        }

        function showWasteForm() {
            var m = el('waste-modal');
            if (!m) return;
            var q = el('waste_qty'); if (q) q.value = '';
            var n = el('waste_notes'); if (n) n.value = '';
            var r = el('waste_reason_select'); if (r) r.value = '';
            var h = el('waste_stock_hint'); if (h) h.textContent = '';
            var s = el('waste_item_select'); if (s) s.value = '';
            m.style.display = 'flex';
        }

        function stockInInventory(id) {
            var m = el('stock-in-modal');
            if (m) {
                var f = el('stock_in_inventory_id'); if (f) f.value = id;
                var q = el('stock_in_qty'); if (q) q.value = '';
                var e = el('stock_in_expiry_date'); if (e) e.value = '';
                var n = el('stock_in_notes'); if (n) n.value = '';
                m.style.display = 'flex';
            }
        }

        function stockOutInventory(id) {
            var m = el('stock-out-modal');
            if (m) {
                var f = el('stock_out_inventory_id'); if (f) f.value = id;
                var q = el('stock_out_qty'); if (q) q.value = '';
                var n = el('stock_out_notes'); if (n) n.value = '';
                m.style.display = 'flex';
            }
        }

        function wasteInventory(id) {
            var m = el('waste-modal');
            if (m) {
                var s = el('waste_item_select');
                if (s) { s.value = id; updateWasteMax(s); }
                var q = el('waste_qty'); if (q) q.value = '';
                var n = el('waste_notes'); if (n) n.value = '';
                var r = el('waste_reason_select'); if (r) r.value = '';
                m.style.display = 'flex';
            }
        }

        function updateWasteMax(select) {
            var opt = select.options[select.selectedIndex];
            var stock = opt ? (opt.getAttribute('data-stock') || 0) : 0;
            var unit = opt ? (opt.getAttribute('data-unit') || 'pcs') : 'pcs';
            var qty = el('waste_qty');
            var hint = el('waste_stock_hint');
            if (!qty || !hint) return;
            if (stock > 0) {
                qty.max = stock;
                hint.textContent = 'Available: ' + stock + ' ' + unit;
                hint.style.color = 'var(--text-muted)';
            } else {
                qty.max = 1;
                hint.textContent = 'No stock available';
                hint.style.color = 'var(--red)';
            }
        }

        function updateWasteNotes(select) {
            var val = select.value;
            var ni = el('waste_notes');
            if (ni) {
                if (val && val !== 'other') { ni.value = val; ni.placeholder = 'Add more details (optional)...'; }
                else { ni.value = ''; ni.placeholder = 'Describe the reason...'; }
            }
        }

        function viewAllBatches() {
            var modal = el('batch-modal');
            var body = el('batch-modal-body');
            if (!modal || !body) return;
            var title = el('batch-modal-title');
            if (title) title.textContent = 'FIFO Overview - Batch Management System';
            body.innerHTML = '<div class="text-muted" style="text-align:center;padding:2rem;"><i class="bx bx-loader-alt bx-spin" style="font-size:2rem;"></i><br>Loading FIFO overview...</div>';
            modal.style.display = 'flex';
            xhrJSON('inventory.php?action=get_all_inventory_batches', function (result) {
                if (!result || !result.success) {
                    body.innerHTML = '<div class="text-muted" style="text-align:center;padding:2rem;">' + ((result && result.error) || 'Unable to load FIFO overview.') + '</div>';
                    return;
                }
                var rows = result.data || [];
                if (rows.length === 0) {
                    body.innerHTML = '<div class="empty-state"><i class="bx bx-package"></i><p>No FIFO batch records found.</p></div>';
                    return;
                }
                body.innerHTML = '<div style="padding:1rem;">FIFO batch overview loaded.</div>';
            }, function () {
                body.innerHTML = '<div class="empty-state"><i class="bx bx-error-circle"></i><p>Error loading FIFO overview. Please try again.</p></div>';
            });
        }

        function quickEditInventory(id) { closeNotificationPanel(); editInventory(id); }
        function toggleNotificationPanel() {
            var p = el('notificationPanel');
            if (!p) return;
            if (p.style.display === 'none' || p.style.display === '') { p.style.display = 'block'; }
            else { p.style.display = 'none'; }
        }
        function closeNotificationPanel() {
            var p = el('notificationPanel');
            if (p) p.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            forEachNode(document.querySelectorAll('.unit-option'), function (option) {
                option.addEventListener('click', function () {
                    forEachNode(document.querySelectorAll('.unit-option'), function (o) { o.classList.remove('selected'); });
                    this.classList.add('selected');
                    var ui = el('inventory_unit');
                    if (ui) ui.value = this.getAttribute('data-unit');
                });
            });
            forEachNode(document.querySelectorAll('.modal'), function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) { var id = this.id; if (id) closeModal(id); }
                });
            });
        });

        window.showAddInventoryForm = showAddInventoryForm;
        window.editInventory = editInventory;
        window.closeModal = closeModal;
        window.showWasteForm = showWasteForm;
        window.stockInInventory = stockInInventory;
        window.stockOutInventory = stockOutInventory;
        window.wasteInventory = wasteInventory;
        window.updateWasteMax = updateWasteMax;
        window.updateWasteNotes = updateWasteNotes;
        window.viewAllBatches = viewAllBatches;
        window.quickEditInventory = quickEditInventory;
        window.toggleNotificationPanel = toggleNotificationPanel;
        window.closeNotificationPanel = closeNotificationPanel;
    })();
    </script>

    <script>
        // Initialize all functions when DOM is ready
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize unit options
            const unitOptions = document.querySelectorAll('.unit-option');
            const unitInput = document.getElementById('inventory_unit');

            if (unitOptions.length > 0 && unitInput) {
                unitOptions.forEach(option => {
                    option.addEventListener('click', function () {
                        unitOptions.forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');
                        unitInput.value = this.dataset.unit;
                    });
                });
            }

            // Initialize notification bell
            const bell = document.querySelector('.inventory-notification-bell');
            if (bell) {
                bell.addEventListener('click', toggleNotificationPanel);
            }

            // Update notification badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const totalAlerts = <?php echo $low_stock_count + $out_of_stock_count + $expiring_soon_count + $expired_count; ?>;
                badge.textContent = totalAlerts;
                if (totalAlerts === 0) {
                    badge.style.display = 'none';
                }
            }

            // Set default unit selection
            const defaultUnit = document.querySelector('.unit-option[data-unit="piece"]');
            if (defaultUnit) {
                defaultUnit.classList.add('selected');
            }

            // Close modals when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal(this.id);
                });
            });
        });

        function showWasteForm() {
            const modal = document.getElementById('waste-modal');
            if (modal) {
                modal.style.display = 'flex';
                document.getElementById('waste_qty').value = '';
                document.getElementById('waste_notes').value = '';
                document.getElementById('waste_reason_select').value = '';
                document.getElementById('waste_stock_hint').textContent = '';
                document.getElementById('waste_item_select').value = '';
            }
        }

        function updateWasteMax(select) {
            const option = select.options[select.selectedIndex];
            const stock = option.dataset.stock || 0;
            const unit = option.dataset.unit || 'pcs';
            const qtyInput = document.getElementById('waste_qty');
            const hint = document.getElementById('waste_stock_hint');
            if (stock > 0) {
                qtyInput.max = stock;
                hint.textContent = 'Available: ' + stock + ' ' + unit;
                hint.style.color = 'var(--text-muted)';
            } else {
                hint.textContent = 'No stock available';
                hint.style.color = 'var(--red)';
            }
        }

        function updateWasteNotes(select) {
            const val = select.value;
            const notesInput = document.getElementById('waste_notes');
            if (val && val !== 'other') {
                notesInput.value = val;
                notesInput.placeholder = 'Add more details (optional)...';
            } else {
                notesInput.value = '';
                notesInput.placeholder = 'Describe the reason...';
                notesInput.focus();
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
            if (modalId === 'inventory-modal') {
                const submitBtn = document.getElementById('inventory-submit-btn');
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        function showAddInventoryForm() {
            const modal = document.getElementById('inventory-modal');
            const title = document.getElementById('inventory-modal-title');
            const form = document.getElementById('inventory-form');
            const quantityInput = document.getElementById('inventory_quantity');
            const submitBtn = document.getElementById('inventory-submit-btn');

            if (!modal) return;

            title.textContent = 'Add New Inventory Item';
            if (form) form.reset();
            document.getElementById('inventory_id').value = '';
            if (quantityInput) {
                quantityInput.disabled = false;
                quantityInput.required = true;
                quantityInput.value = '';
            }
            document.getElementById('inventory_expiry_date').value = '';

            const unitInput = document.getElementById('inventory_unit');
            if (unitInput) unitInput.value = 'piece';

            const unitOptions = document.querySelectorAll('.unit-option');
            unitOptions.forEach(opt => opt.classList.remove('selected'));
            const defaultUnit = document.querySelector('.unit-option[data-unit="piece"]');
            if (defaultUnit) defaultUnit.classList.add('selected');

            if (submitBtn) {
                submitBtn.name = 'add_inventory';
                submitBtn.textContent = 'Add Item';
                submitBtn.disabled = false;
            }

            modal.style.display = 'flex';
        }

        // Add hover effect to action menu items
        document.addEventListener('mouseover', function(e) {
            const btn = e.target.closest('.action-menu button, .action-menu [type="submit"]');
            if (btn) btn.style.background = '#f8f8f8';
        });
        document.addEventListener('mouseout', function(e) {
            const btn = e.target.closest('.action-menu button, .action-menu [type="submit"]');
            if (btn) btn.style.background = 'none';
        });

        function editInventory(id) {
            const modal = document.getElementById('inventory-modal');
            const title = document.getElementById('inventory-modal-title');
            const submitBtn = document.getElementById('inventory-submit-btn');

            if (!modal) return;

            title.textContent = 'Edit Inventory Item';
            document.getElementById('inventory_id').value = id;
            if (submitBtn) {
                submitBtn.name = 'update_inventory';
                submitBtn.textContent = 'Update Item';
                submitBtn.disabled = true;
            }

            fetch(`inventory.php?action=get_inventory&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        document.getElementById('inventory_item_name').value = result.data.item_name || '';
                        document.getElementById('inventory_category').value = result.data.category || '';
                        document.getElementById('inventory_supplier').value = result.data.supplier || '';
                        const quantityInput = document.getElementById('inventory_quantity');
                        if (quantityInput) {
                            quantityInput.value = result.data.quantity || '';
                            quantityInput.disabled = true;
                            quantityInput.required = false;
                        }
                        document.getElementById('inventory_expiry_date').value = '';
                        document.getElementById('inventory_min_stock').value = result.data.min_stock || '';

                        const unit = result.data.unit || 'piece';
                        const unitInput = document.getElementById('inventory_unit');
                        if (unitInput) unitInput.value = unit;

                        const unitOptions = document.querySelectorAll('.unit-option');
                        unitOptions.forEach(opt => {
                            opt.classList.remove('selected');
                            if (opt.dataset.unit === unit) {
                                opt.classList.add('selected');
                            }
                        });

                        if (submitBtn) submitBtn.disabled = false;
                        modal.style.display = 'flex';
                    } else {
                        showToastMsg(result.error || 'Error loading inventory data', 'error');
                        if (submitBtn) submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToastMsg('Error loading inventory data', 'error');
                    if (submitBtn) submitBtn.disabled = false;
                });
        }

        function stockInInventory(id) {
            const modal = document.getElementById('stock-in-modal');
            if (modal) {
                document.getElementById('stock_in_inventory_id').value = id;
                document.getElementById('stock_in_qty').value = '';
                document.getElementById('stock_in_expiry_date').value = '';
                document.getElementById('stock_in_notes').value = '';
                modal.style.display = 'flex';
            }
        }

        function stockOutInventory(id) {
            const modal = document.getElementById('stock-out-modal');
            if (modal) {
                document.getElementById('stock_out_inventory_id').value = id;
                document.getElementById('stock_out_qty').value = '';
                document.getElementById('stock_out_notes').value = '';
                modal.style.display = 'flex';
            }
        }

        function wasteInventory(id) {
            const modal = document.getElementById('waste-modal');
            if (modal) {
                const wasteSelect = document.getElementById('waste_item_select');
                if (wasteSelect) {
                    wasteSelect.value = id;
                    updateWasteMax(wasteSelect);
                }
                document.getElementById('waste_qty').value = '';
                document.getElementById('waste_notes').value = '';
                document.getElementById('waste_reason_select').value = '';
                modal.style.display = 'flex';
            }
        }

        function viewAllBatches() {
            const modal = document.getElementById('batch-modal');
            const title = document.getElementById('batch-modal-title');
            const body = document.getElementById('batch-modal-body');

            if (!modal) return;

            title.textContent = 'FIFO Overview - Batch Management System';
            body.innerHTML = '<div class="text-muted" style="text-align: center; padding: 2rem;"><i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i><br>Loading FIFO overview...</div>';
            modal.style.display = 'flex';

            fetch('inventory.php?action=get_all_inventory_batches')
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        body.innerHTML = `<div class="text-muted" style="text-align: center; padding: 2rem;">${result.error || 'Unable to load FIFO overview.'}</div>`;
                        return;
                    }

                    const rows = result.data || [];

                    if (rows.length === 0) {
                        body.innerHTML = '<div class="empty-state"><i class="bx bx-package"></i><p>No FIFO batch records found.</p></div>';
                        return;
                    }

                    // Group by inventory_id
                    const grouped = {};
                    let totalProducts = 0;
                    let totalBatches = 0;
                    let expiringBatches = 0;
                    let expiredBatches = 0;

                    rows.forEach(row => {
                        const key = row.inventory_id;
                        if (!grouped[key]) {
                            grouped[key] = {
                                item_name: row.item_name,
                                unit: row.unit || 'piece',
                                current_stock: row.current_stock || 0,
                                min_stock: row.min_stock || 0,
                                batches: []
                            };
                            totalProducts++;
                        }

                        if (row.batch_id) {
                            const batch = {
                                batch_id: row.batch_id,
                                batch_quantity: row.batch_quantity,
                                remaining_quantity: row.remaining_quantity,
                                received_at: row.received_at,
                                expiry_date: row.expiry_date
                            };
                            grouped[key].batches.push(batch);
                            totalBatches++;

                            // Check expiry status
                            if (row.expiry_date) {
                                const today = new Date();
                                const expiryDate = new Date(row.expiry_date);
                                const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

                                if (daysUntilExpiry < 0) {
                                    expiredBatches++;
                                } else if (daysUntilExpiry <= 7) {
                                    expiringBatches++;
                                }
                            }
                        }
                    });

                    // Build HTML
                    let summaryHtml = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; padding: 1rem; background: var(--bg); border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary);">${totalProducts}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Total Products</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: var(--blue);">${totalBatches}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Total Batches</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: var(--amber);">${expiringBatches}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Expiring Soon</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: var(--red);">${expiredBatches}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Expired Batches</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-sm btn-outline" onclick="filterFIFOProducts('all')" style="padding: 0.3rem 0.8rem;">All Products</button>
                            <button class="btn btn-sm btn-outline" onclick="filterFIFOProducts('low')" style="padding: 0.3rem 0.8rem;">Low Stock</button>
                            <button class="btn btn-sm btn-outline" onclick="filterFIFOProducts('expiring')" style="padding: 0.3rem 0.8rem;">Expiring</button>
                            <button class="btn btn-sm btn-outline" onclick="filterFIFOProducts('expired')" style="padding: 0.3rem 0.8rem;">Expired</button>
                        </div>
                        <div>
                            <input type="text" id="fifoSearchInput" class="form-control" style="width: 200px; padding: 0.3rem 0.6rem;" placeholder="Search products..." onkeyup="searchFIFOProducts()">
                        </div>
                    </div>
                    <div id="fifo-products-container">
                `;

                    let html = summaryHtml;

                    Object.values(grouped).forEach((product) => {
                        const stockStatus = product.current_stock <= product.min_stock ? 'low' : 'normal';
                        const stockClass = stockStatus === 'low' ? 'status-low' : 'status-active';
                        const stockText = stockStatus === 'low' ? 'Low Stock' : 'In Stock';

                        let hasExpiring = false;
                        let hasExpired = false;
                        let earliestExpiry = null;

                        product.batches.forEach(batch => {
                            if (batch.expiry_date) {
                                if (!earliestExpiry || batch.expiry_date < earliestExpiry) {
                                    earliestExpiry = batch.expiry_date;
                                }
                                const today = new Date();
                                const expiryDate = new Date(batch.expiry_date);
                                const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

                                if (daysUntilExpiry < 0) {
                                    hasExpired = true;
                                } else if (daysUntilExpiry <= 7) {
                                    hasExpiring = true;
                                }
                            }
                        });

                        html += `
                        <div class="fifo-product-item" data-product-name="${product.item_name.toLowerCase()}" data-stock-status="${stockStatus}" data-has-expiring="${hasExpiring}" data-has-expired="${hasExpired}" style="margin-bottom: 1.5rem; padding: 1.25rem; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border); border-left: 3px solid ${hasExpired ? 'var(--red)' : (hasExpiring ? 'var(--amber)' : 'var(--primary)')};">
                            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <h4 style="margin-bottom: 0.5rem; color: var(--text-primary); font-size: 1.1rem;">
                                        ${product.item_name}
                                        ${hasExpired ? '<span class="status-badge status-inactive" style="font-size: 0.65rem; margin-left: 0.5rem;">EXPIRED</span>' : (hasExpiring ? '<span class="status-badge status-low" style="font-size: 0.65rem; margin-left: 0.5rem;">EXPIRING SOON</span>' : '')}
                                    </h4>
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-secondary);">
                                        <span><i class='bx bx-package' style="vertical-align: middle;"></i> Unit: <strong>${product.unit}</strong></span>
                                        <span><i class='bx bx-bar-chart' style="vertical-align: middle;"></i> Current Stock: <strong style="color: ${stockStatus === 'low' ? 'var(--red)' : 'var(--green)'};">${product.current_stock}</strong></span>
                                        <span><i class='bx bx-error' style="vertical-align: middle;"></i> Min Stock: <strong>${product.min_stock}</strong></span>
                                        ${earliestExpiry ? `<span><i class='bx bx-time-five' style="vertical-align: middle;"></i> Earliest Expiry: <strong style="color: ${hasExpired ? 'var(--red)' : (hasExpiring ? 'var(--amber)' : 'var(--text-secondary)')};">${earliestExpiry}</strong></span>` : ''}
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge ${stockClass}">${stockText}</span>
                                </div>
                            </div>
                    `;

                        if (product.batches.length === 0) {
                            html += `<div class="empty-state" style="padding: 1rem;"><i class="bx bx-info-circle"></i><p>No batch history found for this product.</p></div>`;
                        } else {
                            html += `
                            <div class="table-container" style="margin-top: 1rem;">
                                <table class="data-table" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Order</th>
                                            <th>Batch ID</th>
                                            <th>Original Qty</th>
                                            <th>Remaining Qty</th>
                                            <th>Usage %</th>
                                            <th>Received At</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                            product.batches.forEach((batch, idx) => {
                                const orderLabel = idx === 0 ? 'Oldest' : (idx === product.batches.length - 1 ? 'Newest' : `#${idx + 1}`);
                                const usagePercent = ((batch.batch_quantity - batch.remaining_quantity) / batch.batch_quantity * 100).toFixed(1);
                                const usageWidth = Math.min(100, usagePercent);

                                let batchStatus = '';
                                let batchStatusClass = '';
                                let expiryWarning = '';

                                if (batch.expiry_date) {
                                    const today = new Date();
                                    const expiryDate = new Date(batch.expiry_date);
                                    const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

                                    if (daysUntilExpiry < 0) {
                                        batchStatus = 'Expired';
                                        batchStatusClass = 'status-inactive';
                                        expiryWarning = `Expired ${Math.abs(daysUntilExpiry)} days ago`;
                                    } else if (daysUntilExpiry <= 7) {
                                        batchStatus = `Expires in ${daysUntilExpiry} days`;
                                        batchStatusClass = 'status-low';
                                        expiryWarning = `Expires soon (${daysUntilExpiry} days)`;
                                    } else {
                                        batchStatus = 'Valid';
                                        batchStatusClass = 'status-active';
                                    }
                                } else {
                                    batchStatus = 'No expiry';
                                    batchStatusClass = 'status-active';
                                }

                                html += `
                                <tr style="background: ${batch.remaining_quantity === 0 ? 'var(--bg)' : 'var(--bg-card)'}">
                                    <td><strong>${orderLabel}</strong></td>
                                    <td><code style="background: var(--bg); padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.8rem;">#${batch.batch_id}</code></td>
                                    <td>${batch.batch_quantity}</td>
                                    <td><strong>${batch.remaining_quantity}</strong></td>
                                    <td style="min-width: 100px;">
                                        <div style="background: var(--border); border-radius: 10px; overflow: hidden; height: 20px;">
                                            <div style="background: var(--primary); width: ${usageWidth}%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: bold;">
                                                ${usagePercent}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>${batch.received_at ? new Date(batch.received_at).toLocaleDateString() : '-'}</td>
                                    <td style="color: ${batch.expiry_date && new Date(batch.expiry_date) < new Date() ? 'var(--red)' : (batch.expiry_date && new Date(batch.expiry_date) - new Date() <= 7 * 24 * 60 * 60 * 1000 ? 'var(--amber)' : 'inherit')}">
                                        ${batch.expiry_date || '-'}
                                        ${expiryWarning ? `<br><small style="font-size: 0.7rem;">${expiryWarning}</small>` : ''}
                                    </td>
                                    <td><span class="status-badge ${batchStatusClass}" style="font-size: 0.7rem;">${batchStatus}</span></td>
                                </tr>
                            `;
                            });

                            html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        }

                        html += `</div>`;
                    });

                    html += `</div>`;
                    body.innerHTML = html;

                    // Add filter and search functions
                    window.filterFIFOProducts = function (filter) {
                        const products = document.querySelectorAll('.fifo-product-item');
                        products.forEach(product => {
                            const stockStatus = product.dataset.stockStatus;
                            const hasExpiring = product.dataset.hasExpiring === 'true';
                            const hasExpired = product.dataset.hasExpired === 'true';

                            let show = true;
                            if (filter === 'low' && stockStatus !== 'low') show = false;
                            if (filter === 'expiring' && !hasExpiring) show = false;
                            if (filter === 'expired' && !hasExpired) show = false;

                            product.style.display = show ? 'block' : 'none';
                        });
                    };

                    window.searchFIFOProducts = function () {
                        const searchTerm = document.getElementById('fifoSearchInput').value.toLowerCase();
                        const products = document.querySelectorAll('.fifo-product-item');
                        products.forEach(product => {
                            const productName = product.dataset.productName;
                            if (productName.includes(searchTerm)) {
                                product.style.display = 'block';
                            } else {
                                product.style.display = 'none';
                            }
                        });
                    };
                })
                .catch(error => {
                    console.error(error);
                    body.innerHTML = '<div class="empty-state"><i class="bx bx-error-circle"></i><p>Error loading FIFO overview. Please try again.</p></div>';
                });
        }

        let notifPanel = null;
        let notifVisible = false;

        function loadAlerts() {
            const body = document.getElementById('notificationBody');
            if (!body) return;

            let html = '';

            <?php foreach ($expired_items as $item): ?>
                html += `
                <div class="notification-item critical">
                    <div class="item-info">
                        <div class="item-name"><?php echo addslashes($item['item_name']); ?> expired</div>
                        <div class="item-stock">Expiry: <span class="stock-critical"><?php echo addslashes($item['expiry_date']); ?></span> | Remaining: <?php echo (int) $item['remaining_quantity']; ?> <?php echo addslashes($item['unit']); ?></div>
                    </div>
                </div>
            `;
            <?php endforeach; ?>

            <?php foreach ($expiring_items as $item): ?>
                html += `
                <div class="notification-item warning">
                    <div class="item-info">
                        <div class="item-name"><?php echo addslashes($item['item_name']); ?> expiring soon</div>
                        <div class="item-stock">Expiry: <span class="stock-warning"><?php echo addslashes($item['expiry_date']); ?></span> | Remaining: <?php echo (int) $item['remaining_quantity']; ?> <?php echo addslashes($item['unit']); ?></div>
                    </div>
                </div>
            `;
            <?php endforeach; ?>

            <?php foreach ($inventory_items as $item): ?>
                <?php if ($item['quantity'] <= 0): ?>
                    html += `
                    <div class="notification-item critical">
                        <div class="item-info">
                            <div class="item-name"><?php echo addslashes($item['item_name']); ?></div>
                            <div class="item-stock">Stock: <span class="stock-critical">0 <?php echo addslashes($item['unit'] ?? 'piece'); ?></span> (Min: <?php echo (int) $item['min_stock']; ?>)</div>
                        </div>
                        <button class="update-btn" onclick="quickEditInventory(<?php echo (int) $item['id']; ?>)">Update</button>
                    </div>
                `;
                <?php elseif ($item['quantity'] <= $item['min_stock']): ?>
                    html += `
                    <div class="notification-item warning">
                        <div class="item-info">
                            <div class="item-name"><?php echo addslashes($item['item_name']); ?></div>
                            <div class="item-stock">Stock: <span class="stock-warning"><?php echo (int) $item['quantity']; ?> <?php echo addslashes($item['unit'] ?? 'piece'); ?></span> (Min: <?php echo (int) $item['min_stock']; ?>)</div>
                        </div>
                        <button class="update-btn" onclick="quickEditInventory(<?php echo (int) $item['id']; ?>)">Update</button>
                    </div>
                `;
                <?php endif; ?>
            <?php endforeach; ?>

            if (!html) {
                html = '<div class="empty-notification"><i class="bx bx-check-circle"></i><p>All inventory items are well stocked!</p></div>';
            }

            body.innerHTML = html;
        }

        function quickEditInventory(id) {
            closeNotificationPanel();
            editInventory(id);
        }

        function toggleNotificationPanel() {
            notifPanel = document.getElementById('notificationPanel');
            if (!notifPanel) return;

            if (notifVisible) {
                notifPanel.style.display = 'none';
                notifVisible = false;
            } else {
                loadAlerts();
                notifPanel.style.display = 'block';
                notifVisible = true;
            }
        }

        function closeNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (panel) {
                panel.style.display = 'none';
                notifVisible = false;
            }
        }
    </script>

    <script>
        // ── Client-side pagination for secondary tables (same pattern as tools/archive.php) ──
        const SECONDARY_ROWS_PER_PAGE = 10;

        const secondaryTableConfig = {
            wasteLog: {
                tableId: 'wasteLogTable',
                infoId: 'wasteLogInfo',
                paginationId: 'wasteLogPagination',
                currentPage: 1
            },
            movement: {
                tableId: 'movementTable',
                infoId: 'movementInfo',
                paginationId: 'movementPagination',
                currentPage: 1
            }
        };

        function getSecondaryRows(key) {
            const table = document.getElementById(secondaryTableConfig[key].tableId);
            if (!table) return [];
            return Array.from(table.querySelectorAll('tbody tr'));
        }

        function renderSecondaryPage(key) {
            const cfg = secondaryTableConfig[key];
            const rows = getSecondaryRows(key);
            const totalRows = rows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / SECONDARY_ROWS_PER_PAGE));

            if (cfg.currentPage > totalPages) cfg.currentPage = totalPages;

            const start = (cfg.currentPage - 1) * SECONDARY_ROWS_PER_PAGE;
            const end = start + SECONDARY_ROWS_PER_PAGE;

            rows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            const infoEl = document.getElementById(cfg.infoId);
            if (infoEl) {
                if (totalRows === 0) {
                    infoEl.textContent = '';
                } else {
                    infoEl.textContent = `Showing ${start + 1}-${Math.min(end, totalRows)} of ${totalRows}`;
                }
            }

            const pagEl = document.getElementById(cfg.paginationId);
            if (!pagEl) return;
            if (totalPages <= 1) {
                pagEl.innerHTML = '';
                return;
            }

            let html = '';
            html += `<button type="button" onclick="goToSecondaryPage('${key}', ${cfg.currentPage - 1})" ${cfg.currentPage === 1 ? 'disabled' : ''}><i class='bx bx-chevron-left'></i></button>`;

            let pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (cfg.currentPage > 3) pages.push('...');
                for (let i = Math.max(2, cfg.currentPage - 1); i <= Math.min(totalPages - 1, cfg.currentPage + 1); i++) pages.push(i);
                if (cfg.currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    html += `<button type="button" disabled style="border:none;background:none;cursor:default;">...</button>`;
                } else {
                    html += `<button type="button" onclick="goToSecondaryPage('${key}', ${p})" class="${p === cfg.currentPage ? 'active' : ''}">${p}</button>`;
                }
            });

            html += `<button type="button" onclick="goToSecondaryPage('${key}', ${cfg.currentPage + 1})" ${cfg.currentPage === totalPages ? 'disabled' : ''}><i class='bx bx-chevron-right'></i></button>`;
            pagEl.innerHTML = html;
        }

        function goToSecondaryPage(key, page) {
            const cfg = secondaryTableConfig[key];
            const totalRows = getSecondaryRows(key).length;
            const totalPages = Math.max(1, Math.ceil(totalRows / SECONDARY_ROWS_PER_PAGE));
            if (page < 1 || page > totalPages) return;
            cfg.currentPage = page;
            renderSecondaryPage(key);
        }
        window.goToSecondaryPage = goToSecondaryPage;

        document.addEventListener('DOMContentLoaded', function() {
            Object.keys(secondaryTableConfig).forEach(key => {
                if (document.getElementById(secondaryTableConfig[key].tableId)) {
                    renderSecondaryPage(key);
                }
            });
        });
    </script>

    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>

</html>
