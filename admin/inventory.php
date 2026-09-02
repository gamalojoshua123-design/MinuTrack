<?php
require_once __DIR__ . '/bootstrap.php';
requireOwner();

$active_tab = 'inventory';
$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_type = isset($_GET['type']) && in_array($_GET['type'], ['success', 'error'], true) ? $_GET['type'] : 'info';

// Handle AJAX request to get inventory item for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_inventory') {
    try {
        $inventory_id = $_GET['id'] ?? null;
        if (!$inventory_id) throw new Exception('Inventory ID not provided');
        
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$inventory_id]);
        $item = $stmt->fetch();
        
        if (!$item) throw new Exception('Inventory item not found');
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $item]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    // Add inventory item
    if (isset($_POST['add_inventory'])) {
        try {
            $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO inventory (branch_id, item_name, quantity, min_stock, status, last_updated) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$branch_id, trim($_POST['item_name']), (float)$_POST['quantity'], (float)$_POST['min_stock']]);
            header('Location: inventory.php?message=' . urlencode('Item added successfully!') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: inventory.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
    
    // Update inventory item
    if (isset($_POST['update_inventory'])) {
        try {
            $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
            $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, quantity = ?, min_stock = ?, branch_id = ?, last_updated = NOW() WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([
                trim($_POST['item_name']),
                (float)$_POST['quantity'],
                (float)$_POST['min_stock'],
                $branch_id,
                (int)$_POST['inventory_id']
            ]);
            header('Location: inventory.php?message=' . urlencode('Item updated successfully!') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: inventory.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
    
    // Delete inventory item
    if (isset($_POST['delete_inventory'])) {
        try {
            $inventory_id = (int)$_POST['inventory_id'];

            // First check if this inventory item is used in any products
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM product_ingredients
                WHERE template_id = (SELECT template_id FROM inventory WHERE id = ?)
                  AND (SELECT template_id FROM inventory WHERE id = ?) IS NOT NULL
            ");
            $stmt->execute([$inventory_id, $inventory_id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                // Item is used in products, ask for confirmation or prevent deletion
                header('Location: inventory.php?message=' . urlencode('Cannot delete: This item is used in ' . $usage_count . ' product(s). Remove from products first.') . '&type=error');
                exit;
            }
            
            // Soft delete so history is preserved
            $stmt = $pdo->prepare("UPDATE inventory SET status = 'inactive', deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$inventory_id]);
            
            header('Location: inventory.php?message=' . urlencode('Item deleted successfully!') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: inventory.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
}

// Fetch branches for filter
$branches = [];
try {
    $branches = $pdo->query("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $branches = []; }

$selected_branch = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
$show_branch_col = $selected_branch === null;

// Fetch inventory with branch info (only non-deleted)
$sql = "SELECT i.*, b.branch_name FROM inventory i LEFT JOIN branches b ON i.branch_id = b.id WHERE i.deleted_at IS NULL";
$params = [];
if ($selected_branch !== null) {
    $sql .= " AND i.branch_id = ?";
    $params[] = $selected_branch;
}
$sql .= " ORDER BY " . ($show_branch_col ? "b.branch_name, " : "") . "i.item_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventory_items = $stmt->fetchAll();

// Branch label
$branch_label = 'All Branches';
if ($selected_branch !== null) {
    foreach ($branches as $b) {
        if ((int)$b['id'] === $selected_branch) { $branch_label = $b['branch_name']; break; }
    }
}
$total_items = count($inventory_items);
$total_qty = array_sum(array_column($inventory_items, 'quantity'));
$low_stock_count = 0;
$critical_count = 0;
foreach ($inventory_items as $item) {
    if ((int)$item['quantity'] <= 0) { $critical_count++; }
    elseif ((int)$item['quantity'] <= (int)($item['min_stock'] ?? 0)) { $low_stock_count++; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(getCsrfToken()); ?>">
    <title>Inventory - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        /* Additional styles for inventory */
        .low-stock {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
        }
        .critical-stock {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        .stock-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .stock-good { background-color: #28a745; }
        .stock-low { background-color: #ffc107; }
        .stock-critical { background-color: #dc3545; }
        .delete-warning {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .inv-filter-bar {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;
            background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
            padding: 0.85rem 1.25rem; margin-bottom: 1.25rem;
        }
        .inv-filter-left { display: flex; align-items: center; gap: 0.5rem; }
        .inv-filter-left label { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
        .inv-filter-left select {
            padding: 0.4rem 0.75rem; border: 1.5px solid var(--border); border-radius: 8px;
            font-size: 0.85rem; font-family: inherit; color: var(--text-primary); background: var(--bg-card);
            outline: none; cursor: pointer; transition: var(--transition); min-width: 200px;
        }
        .inv-filter-left select:focus { border-color: var(--primary); }
        .inv-filter-right { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
        .inv-stat { display: flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap; }
        .inv-stat-label { color: var(--text-muted); }
        .inv-stat strong { font-size: 0.85rem; }
        .stat-amber { color: var(--amber); }
        .stat-red { color: var(--red); }
        .branch-tag {
            display: inline-block; padding: 2px 10px; border-radius: 10px;
            font-size: 0.72rem; font-weight: 600; white-space: nowrap;
            background: var(--primary-light); color: var(--primary);
        }

        @media (max-width: 768px) {
            .inv-filter-bar { flex-direction: column; align-items: stretch; }
            .inv-filter-left { flex-wrap: wrap; }
            .inv-filter-left select { min-width: 100%; }
            .inv-filter-right { flex-wrap: wrap; gap: 0.75rem; justify-content: flex-start; }
        }

        /* Modal styles - ensure they work on all devices */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            max-width: 500px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
        }
        .modal-close:hover {
            color: var(--red);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn-sm {
            padding: 0.3rem 0.7rem;
            font-size: 0.75rem;
        }
        .btn-edit {
            background: var(--blue-light);
            color: var(--blue);
            border: none;
        }
        .btn-edit:hover {
            background: var(--blue);
            color: #fff;
        }
        .btn-delete {
            background: var(--red-light);
            color: var(--red);
            border: none;
        }
        .btn-delete:hover {
            background: var(--red);
            color: #fff;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .table-container {
            overflow-x: hidden;
            overflow-y: auto;
            max-height: 60vh;
        }
        .card-body {
            overflow-y: auto;
        }
        .table-container thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* Table pagination (same pattern as tools/archive.php) */
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

        /* Mobile: the 60vh internally-scrolled table box traps touch
           gestures (old iOS WebKit can't bubble them to the page), which
           freezes scrolling. Let the page scroll naturally instead and
           keep columns reachable by restoring horizontal panning. */
        @media (max-width: 768px) {
            .table-container {
                max-height: none;
                overflow-x: auto;
                overflow-y: visible;
                -webkit-overflow-scrolling: touch;
            }
            .card-body {
                overflow-y: visible;
            }
            /* sticky th only made sense inside the capped box; unstick it
               or it would pin under the app header while the page scrolls */
            .table-container thead th {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>
            
            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <!-- Branch Filter & Stats -->
                <div class="inv-filter-bar">
                    <div class="inv-filter-left">
                        <label>Branch:</label>
                        <form method="GET" id="branchFilterForm" style="display:inline-flex; align-items:center; flex-wrap:wrap;">
                            <select name="branch_id" id="branchFilterSelect" onchange="this.form.submit()">
                                <option value="" <?php echo $selected_branch === null ? 'selected' : ''; ?>>All Branches</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?php echo (int)$b['id']; ?>" <?php echo $selected_branch !== null && (int)$b['id'] === $selected_branch ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['branch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="inv-filter-right">
                        <span class="inv-stat"><span class="inv-stat-label">Items</span><strong><?php echo $total_items; ?></strong></span>
                        <span class="inv-stat"><span class="inv-stat-label">Total Stock</span><strong><?php echo $total_qty; ?></strong></span>
                        <span class="inv-stat"><span class="inv-stat-label">Low Stock</span><strong class="stat-amber"><?php echo $low_stock_count; ?></strong></span>
                        <span class="inv-stat"><span class="inv-stat-label">Critical</span><strong class="stat-red"><?php echo $critical_count; ?></strong></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-package'></i>Inventory Management</h3>
                        <button class="btn btn-primary" onclick="showAddInventoryForm()">
                            <i class='bx bx-plus'></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table" id="inventoryTable">
                                <thead>
                                    <tr>
                                        <?php if ($show_branch_col): ?>
                                            <th>Branch</th>
                                        <?php endif; ?>
                                        <th>Item Name</th>
                                        <th>Current Stock</th>
                                        <th>Min Stock</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_items as $item): 
                                        $status = '';
                                        $status_class = '';
                                        if ($item['quantity'] <= 0) {
                                            $status = 'Out of Stock';
                                            $status_class = 'critical-stock';
                                        } elseif ($item['quantity'] <= $item['min_stock']) {
                                            $status = 'Low Stock';
                                            $status_class = 'low-stock';
                                        } else {
                                            $status = 'Normal';
                                        }
                                    ?>
                                        <tr>
                                            <?php if ($show_branch_col): ?>
                                                <td><span class="branch-tag"><?php echo htmlspecialchars($item['branch_name'] ?? 'Unassigned'); ?></span></td>
                                            <?php endif; ?>
                                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                            <td class="<?php echo $status_class; ?>">
                                                <?php echo $item['quantity']; ?>
                                            </td>
                                            <td><?php echo $item['min_stock']; ?></td>
                                            <td>
                                                <?php if ($item['quantity'] <= 0): ?>
                                                    <span class="stock-indicator stock-critical"></span> Critical
                                                <?php elseif ($item['quantity'] <= $item['min_stock']): ?>
                                                    <span class="stock-indicator stock-low"></span> Low
                                                <?php else: ?>
                                                    <span class="stock-indicator stock-good"></span> Good
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($item['last_updated'])); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-edit btn-sm" onclick="editInventory(<?php echo $item['id']; ?>)">
                                                    <i class='bx bx-edit'></i> Edit
                                                </button>
                                                <button class="btn btn-delete btn-sm" onclick="showDeleteModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                    <i class='bx bx-trash'></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <span class="table-info" id="inventoryInfo"></span>
                            <div class="table-pagination" id="inventoryPagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="inventory-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="inventory-modal-title">Add Inventory Item</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('inventory-modal')"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="inventory-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="inventory_id">
                    
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="inventory_item_name" name="item_name" 
                               placeholder="e.g., Burger Buns, Beef Patties, Cheese" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <select class="form-control" id="inventory_branch_id" name="branch_id">
                            <option value="">All Branches (Shared Item)</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['branch_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="inventory_quantity" name="quantity" 
                               min="0" step="1" placeholder="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="inventory_min_stock" name="min_stock" 
                               min="0" step="1" placeholder="10" required>
                        <small class="text-muted">You'll be alerted when stock falls below this level</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('inventory-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="inventory-submit-btn">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header" style="background: #dc3545; color: white;">
                <h3 class="modal-title" style="color: white;">Confirm Delete</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('delete-modal')" style="color: white;"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <p id="delete-message">Are you sure you want to delete this item?</p>
                <p class="delete-warning">This action cannot be undone!</p>
                
                <form method="POST" id="delete-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="inventory_id" id="delete_inventory_id">
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('delete-modal')">Cancel</button>
                        <button type="submit" class="btn btn-delete" name="delete_inventory">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- ES5 COMPATIBLE JAVASCRIPT - WORKS ON OLD TABLETS             -->
    <!-- ============================================================ -->
    <script>
        // ================================================================
        // INVENTORY MANAGEMENT - ES5 Compatible (No fetch, no arrow functions)
        // ================================================================

        function $(id) {
            return document.getElementById(id);
        }

        var CURRENT_SELECTED_BRANCH = <?php echo $selected_branch !== null ? (int)$selected_branch : 'null'; ?>;

        function closeModal(id) {
            var modal = $(id);
            if (modal) modal.style.display = 'none';
        }

        // -------- Add Inventory --------
        function showAddInventoryForm() {
            var title = $('inventory-modal-title');
            var idField = $('inventory_id');
            var nameField = $('inventory_item_name');
            var qtyField = $('inventory_quantity');
            var minField = $('inventory_min_stock');
            var branchField = $('inventory_branch_id');
            var submitBtn = $('inventory-submit-btn');
            var modal = $('inventory-modal');

            if (!modal) return;

            title.textContent = 'Add Inventory Item';
            if (idField) idField.value = '';
            if (nameField) nameField.value = '';
            if (qtyField) qtyField.value = '';
            if (minField) minField.value = '';
            // Default the branch to the currently-selected branch filter so a new
            // item actually shows up in the view being looked at.
            if (branchField) {
                branchField.value = (typeof CURRENT_SELECTED_BRANCH !== 'undefined' && CURRENT_SELECTED_BRANCH) ? CURRENT_SELECTED_BRANCH : '';
            }
            if (submitBtn) {
                submitBtn.textContent = 'Add Item';
                submitBtn.name = 'add_inventory';
            }

            modal.style.display = 'flex';
        }

        // -------- Edit Inventory (uses XMLHttpRequest - works on old tablets) --------
        function editInventory(id) {
            var modal = $('inventory-modal');
            var title = $('inventory-modal-title');
            var idField = $('inventory_id');
            var nameField = $('inventory_item_name');
            var qtyField = $('inventory_quantity');
            var minField = $('inventory_min_stock');
            var branchField = $('inventory_branch_id');
            var submitBtn = $('inventory-submit-btn');

            if (!modal) return;

            title.textContent = 'Edit Inventory Item';
            if (idField) idField.value = id;
            if (submitBtn) {
                submitBtn.textContent = 'Update Item';
                submitBtn.name = 'update_inventory';
            }

            // Show loading state
            if (nameField) nameField.value = 'Loading...';
            if (qtyField) qtyField.value = '';
            if (minField) minField.value = '';

            modal.style.display = 'flex';

            // Use XMLHttpRequest instead of fetch (compatible with old browsers)
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=get_inventory&id=' + encodeURIComponent(id), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var result = JSON.parse(xhr.responseText);
                            if (result.success) {
                                if (nameField) nameField.value = result.data.item_name || '';
                                if (qtyField) qtyField.value = result.data.quantity || '';
                                if (minField) minField.value = result.data.min_stock || '';
                                if (branchField) branchField.value = result.data.branch_id || '';
                            } else {
                                showToastMsg('Error loading item: ' + (result.error || 'Unknown error'), 'error');
                                closeModal('inventory-modal');
                            }
                        } catch (e) {
                            showToastMsg('Error parsing response. Please try again.', 'error');
                            closeModal('inventory-modal');
                        }
                    } else {
                        showToastMsg('Error loading inventory data (HTTP ' + xhr.status + '). Please refresh and try again.', 'error');
                        closeModal('inventory-modal');
                    }
                }
            };
            xhr.send();
        }

        // -------- Delete Inventory (opens the confirm modal) --------
        function deleteInventory(id, itemName) {
            showDeleteModal(id, itemName);
        }

        // -------- Alternative: Delete using modal (if you prefer) --------
        function showDeleteModal(id, itemName) {
            var deleteId = $('delete_inventory_id');
            var deleteMsg = $('delete-message');
            var modal = $('delete-modal');

            if (deleteId) deleteId.value = id;
            if (deleteMsg) deleteMsg.innerHTML = 'Are you sure you want to delete <strong>"' + itemName + '"</strong>?';
            if (modal) modal.style.display = 'flex';
        }

        // -------- Table Pagination (ES5 compatible - same behaviour as tools/archive.php) --------
        var INV_ROWS_PER_PAGE = 10;
        var invCurrentPage = 1;

        function getInventoryRows() {
            var table = document.getElementById('inventoryTable');
            if (!table) return [];
            var rows = table.querySelectorAll('tbody tr');
            var arr = [];
            for (var i = 0; i < rows.length; i++) arr.push(rows[i]);
            return arr;
        }

        function renderInventoryPage() {
            var rows = getInventoryRows();
            var totalRows = rows.length;
            var totalPages = Math.max(1, Math.ceil(totalRows / INV_ROWS_PER_PAGE));
            if (invCurrentPage > totalPages) invCurrentPage = totalPages;

            var start = (invCurrentPage - 1) * INV_ROWS_PER_PAGE;
            var end = start + INV_ROWS_PER_PAGE;

            for (var i = 0; i < totalRows; i++) {
                rows[i].style.display = (i >= start && i < end) ? '' : 'none';
            }

            var infoEl = document.getElementById('inventoryInfo');
            if (infoEl) {
                if (totalRows === 0) {
                    infoEl.textContent = 'No results found';
                } else {
                    infoEl.textContent = 'Showing ' + (start + 1) + '-' + Math.min(end, totalRows) + ' of ' + totalRows;
                }
            }

            var pagEl = document.getElementById('inventoryPagination');
            if (!pagEl) return;
            if (totalPages <= 1) {
                pagEl.innerHTML = '';
                return;
            }

            var html = '';
            html += '<button type="button" onclick="goToInventoryPage(' + (invCurrentPage - 1) + ')"' + (invCurrentPage === 1 ? ' disabled' : '') + '><i class=\'bx bx-chevron-left\'></i></button>';

            var pages = [];
            if (totalPages <= 7) {
                for (var p = 1; p <= totalPages; p++) pages.push(p);
            } else {
                pages.push(1);
                if (invCurrentPage > 3) pages.push('...');
                for (var q = Math.max(2, invCurrentPage - 1); q <= Math.min(totalPages - 1, invCurrentPage + 1); q++) pages.push(q);
                if (invCurrentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }
            for (var j = 0; j < pages.length; j++) {
                if (pages[j] === '...') {
                    html += '<button type="button" disabled style="border:none;background:none;cursor:default;">...</button>';
                } else {
                    html += '<button type="button" onclick="goToInventoryPage(' + pages[j] + ')"' + (pages[j] === invCurrentPage ? ' class="active"' : '') + '>' + pages[j] + '</button>';
                }
            }

            html += '<button type="button" onclick="goToInventoryPage(' + (invCurrentPage + 1) + ')"' + (invCurrentPage === totalPages ? ' disabled' : '') + '><i class=\'bx bx-chevron-right\'></i></button>';
            pagEl.innerHTML = html;
        }

        function goToInventoryPage(page) {
            var totalRows = getInventoryRows().length;
            var totalPages = Math.max(1, Math.ceil(totalRows / INV_ROWS_PER_PAGE));
            if (page < 1 || page > totalPages) return;
            invCurrentPage = page;
            renderInventoryPage();
        }
        window.goToInventoryPage = goToInventoryPage;

        // -------- Form Validation (ES5 compatible) --------
        document.addEventListener('DOMContentLoaded', function() {
            var form = $('inventory-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var nameField = $('inventory_item_name');
                    var qtyField = $('inventory_quantity');
                    var minField = $('inventory_min_stock');

                    if (!nameField || !nameField.value.trim()) {
                        e.preventDefault();
                        showToastMsg('Please enter an item name', 'warning');
                        if (nameField) nameField.focus();
                        return false;
                    }

                    if (!qtyField || qtyField.value === '' || parseFloat(qtyField.value) < 0) {
                        e.preventDefault();
                        showToastMsg('Please enter a valid quantity', 'warning');
                        if (qtyField) qtyField.focus();
                        return false;
                    }

                    if (!minField || minField.value === '' || parseFloat(minField.value) < 0) {
                        e.preventDefault();
                        showToastMsg('Please enter a valid minimum stock level', 'warning');
                        if (minField) minField.focus();
                        return false;
                    }

                    return true;
                });
            }

            // -------- Click outside modal to close --------
            var modals = document.querySelectorAll('.modal');
            for (var i = 0; i < modals.length; i++) {
                (function(modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeModal(this.id);
                        }
                    });
                })(modals[i]);
            }

            // -------- Initialize table pagination --------
            renderInventoryPage();
        });

        // Make functions globally accessible
        window.closeModal = closeModal;
        window.showAddInventoryForm = showAddInventoryForm;
        window.editInventory = editInventory;
        window.deleteInventory = deleteInventory;
        window.showDeleteModal = showDeleteModal;
    </script>
    
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>