<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('archive_view');

$page_title = 'Archive Management';
$active_page = 'archive';

$message = '';
$message_type = '';

// HANDLE ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    // RESTORE PRODUCT
    if (isset($_POST['restore_product'])) {
        requirePermission('archive_restore');
        try {
            $stmt = $pdo->prepare("UPDATE products
                SET status = 'active', deleted_at = NULL
                WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([(int)$_POST['product_id']]);

            auditLog('archive_restore', 'products', 'product', $_POST['product_id'] ?? null, 'success', 'Restored archived product');
            $message = "Product restored successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error restoring product: " . $e->getMessage();
            $message_type = "error";
        }
    }

    // DELETE PRODUCT PERMANENTLY
    elseif (isset($_POST['permanent_delete'])) {
        requirePermission('archive_delete');
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([(int)$_POST['product_id']]);

            auditLog('archive_delete', 'products', 'product', $_POST['product_id'] ?? null, 'success', 'Permanently deleted archived product');
            $message = "Product permanently deleted!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting product: " . $e->getMessage();
            $message_type = "error";
        }
    }

    // RESTORE INVENTORY
    elseif (isset($_POST['restore_inventory'])) {
        requirePermission('archive_restore');
        try {
            $stmt = $pdo->prepare("UPDATE inventory
                SET status = 'active', deleted_at = NULL
                WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([(int)$_POST['inventory_id']]);

            auditLog('archive_restore', 'inventory', 'inventory', $_POST['inventory_id'] ?? null, 'success', 'Restored archived inventory item');
            $message = "Inventory item restored successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error restoring inventory: " . $e->getMessage();
            $message_type = "error";
        }
    }

    // DELETE INVENTORY PERMANENTLY
    elseif (isset($_POST['permanent_delete_inventory'])) {
        requirePermission('archive_delete');
        try {
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([(int)$_POST['inventory_id']]);

            auditLog('archive_delete', 'inventory', 'inventory', $_POST['inventory_id'] ?? null, 'success', 'Permanently deleted archived inventory item');
            $message = "Inventory item permanently deleted!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Error deleting inventory: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get low stock count from inventory for notification badge
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= min_stock
      AND (status IS NULL OR status = 'active')
      AND deleted_at IS NULL
");
$stmt->execute();
$low_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get out of stock count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL
");
$stmt->execute();
$out_of_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_alerts = $low_stock_total + $out_of_stock_total;

// Fetch out of stock items for notification
$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL
    ORDER BY item_name ASC
    LIMIT 10
");
$stmt->execute();
$out_of_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch low stock items for notification (excluding out of stock)
$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= min_stock
      AND quantity > 0
      AND deleted_at IS NULL
    ORDER BY quantity ASC
    LIMIT 10
");
$stmt->execute();
$low_stock_notify = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH ARCHIVED PRODUCTS
try {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC
    ");
    $stmt->execute();
    $archived_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $archived_products = [];
}

// FETCH ARCHIVED INVENTORY
try {
    $stmt = $pdo->prepare("
        SELECT * FROM inventory
        WHERE deleted_at IS NOT NULL
        ORDER BY deleted_at DESC
    ");
    $stmt->execute();
    $archived_inventory = $stmt->fetchAll();
} catch (PDOException $e) {
    $archived_inventory = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Page-specific styles for Archive */
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .btn-success {
            background: var(--green);
            color: white;
            padding: 0.45rem 0.85rem;
            font-size: 0.8rem;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            margin-bottom: 0.35rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .empty-state p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Notification Panel */
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
            background: linear-gradient(135deg, var(--primary), var(--copperwood));
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

        .notification-header h4 i {
            font-size: 1.2rem;
        }

        .notification-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .notification-close:hover {
            background: rgba(255,255,255,0.3);
        }

        .notification-body {
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #fafbfc;
        }

        .notification-item.critical {
            border-left: 3px solid var(--red);
            background: var(--red-light);
        }

        .notification-item.warning {
            border-left: 3px solid var(--amber);
            background: var(--amber-light);
        }

        .notification-item .item-info {
            flex: 1;
        }

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

        .notification-item .stock-critical {
            color: var(--red);
            font-weight: 600;
        }

        .notification-item .stock-warning {
            color: var(--amber);
            font-weight: 600;
        }

        .notification-item .update-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-family: inherit;
        }

        .notification-item .update-btn:hover {
            background: var(--primary-dark);
        }

        .empty-notification {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-notification i {
            font-size: 2.5rem;
            color: var(--green);
            margin-bottom: 0.5rem;
        }

        .empty-notification p {
            margin-top: 0.35rem;
            font-size: 0.85rem;
        }

        /* Table toolbar with search */
        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            margin-bottom: -0.5rem;
        }

        .table-toolbar > * {
            margin: 0 0.5rem 0.5rem 0;
        }

        .table-search {
            position: relative;
            flex: 0 1 300px;
            min-width: 180px;
        }

        .table-search i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .table-search input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-family: inherit;
            background: var(--bg-main);
            color: var(--text-primary);
            transition: var(--transition);
        }

        .table-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .table-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* Pagination */
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

        @media (max-width: 1280px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .notification-panel {
                top: 60px;
                right: 10px;
                width: calc(100% - 20px);
            }
            .table-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .table-search {
                flex: 1 1 100%;
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
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: var(--purple-light); color: var(--purple);"><i class='bx bx-food-menu'></i></div>
                            <div>
                                <div class="stat-title">Archived Products</div>
                                <div class="stat-value"><?php echo count($archived_products); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: var(--blue-light); color: var(--blue);"><i class='bx bx-package'></i></div>
                            <div>
                                <div class="stat-title">Archived Inventory</div>
                                <div class="stat-value"><?php echo count($archived_inventory); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: var(--green-light); color: var(--green);"><i class='bx bx-check-circle'></i></div>
                            <div>
                                <div class="stat-title">Total Archived</div>
                                <div class="stat-value"><?php echo count($archived_products) + count($archived_inventory); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archived Products Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class='bx bx-food-menu'></i>
                            Archived Products
                        </h3>
                    </div>
                    <?php if (empty($archived_products)): ?>
                        <div class="card-body">
                            <div class="empty-state">
                                <i class='bx bx-inbox'></i>
                                <h3>No Archived Products</h3>
                                <p>All your products are currently active.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-toolbar">
                            <div class="table-search">
                                <i class='bx bx-search'></i>
                                <input type="text" id="searchProducts" placeholder="Search archived products..." oninput="filterTable('products')">
                            </div>
                            <div class="table-info" id="productsInfo"></div>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <div class="table-container" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                                <table class="data-table" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Category</th>
                                            <th>Archived Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archived_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="product-image-small">
                                                        <?php
                                                        $image_path = 'assets/images/products/' . ($product['image'] ?? 'default-product.png');
                                                        $default_emoji = '&#x1F354;';
                                                        if ($product['category'] === 'Drinks')
                                                            $default_emoji = '&#x1F964;';
                                                        if ($product['category'] === 'Add-ons')
                                                            $default_emoji = '&#x1F35F;';

                                                        if (file_exists($image_path) && !empty($product['image']) && $product['image'] !== 'default-product.png'):
                                                            ?>
                                                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="product-emoji-small"><?php echo $default_emoji; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                                <td class="text-muted"><?php echo number_format($product['price'], 2); ?></td>
                                                <td><?php echo $product['stock']; ?></td>
                                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                <td class="text-muted"><?php echo date('M j, Y g:i A', strtotime($product['deleted_at'])); ?></td>
                                                <td>
                                                    <div class="action-dropdown">
                                                        <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                                                        <div class="action-dropdown-menu">
                                                            <form method="POST">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <button type="submit" name="restore_product" class="action-restore"><i class='bx bx-revision'></i> Restore</button>
                                                            </form>
                                                            <div class="action-dropdown-divider"></div>
                                                            <form method="POST" onsubmit="return askConfirm(event, 'Permanently delete this product? This cannot be undone.')">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <button type="submit" name="permanent_delete" class="action-delete"><i class='bx bx-trash'></i> Delete Permanently</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-pagination" id="productsPagination"></div>
                    <?php endif; ?>
                </div>

                <!-- Archived Inventory Section -->
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class='bx bx-package'></i>
                            Archived Inventory Items
                        </h3>
                    </div>
                    <?php if (empty($archived_inventory)): ?>
                        <div class="card-body">
                            <div class="empty-state">
                                <i class='bx bx-package'></i>
                                <h3>No Archived Inventory Items</h3>
                                <p>All your inventory items are currently active.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-toolbar">
                            <div class="table-search">
                                <i class='bx bx-search'></i>
                                <input type="text" id="searchInventory" placeholder="Search archived inventory..." oninput="filterTable('inventory')">
                            </div>
                            <div class="table-info" id="inventoryInfo"></div>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <div class="table-container" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                                <table class="data-table" id="inventoryTable">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Min Stock</th>
                                            <th>Archived Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archived_inventory as $item): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo $item['min_stock']; ?></td>
                                                <td class="text-muted"><?php echo date('M j, Y g:i A', strtotime($item['deleted_at'])); ?></td>
                                                <td>
                                                    <div class="action-dropdown">
                                                        <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                                                        <div class="action-dropdown-menu">
                                                            <form method="POST">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                                                                <button type="submit" name="restore_inventory" class="action-restore"><i class='bx bx-revision'></i> Restore</button>
                                                            </form>
                                                            <div class="action-dropdown-divider"></div>
                                                            <form method="POST" onsubmit="return askConfirm(event, 'Permanently delete this inventory item? This cannot be undone.')">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="inventory_id" value="<?php echo $item['id']; ?>">
                                                                <button type="submit" name="permanent_delete_inventory" class="action-delete"><i class='bx bx-trash'></i> Delete Permanently</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="table-pagination" id="inventoryPagination"></div>
                    <?php endif; ?>
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

    <script>
        // ── Client-side Search & Pagination ──
        const ROWS_PER_PAGE = 10;

        const tableConfig = {
            products: {
                tableId: 'productsTable',
                searchId: 'searchProducts',
                infoId: 'productsInfo',
                paginationId: 'productsPagination',
                currentPage: 1,
                filteredRows: []
            },
            inventory: {
                tableId: 'inventoryTable',
                searchId: 'searchInventory',
                infoId: 'inventoryInfo',
                paginationId: 'inventoryPagination',
                currentPage: 1,
                filteredRows: []
            }
        };

        function getAllRows(key) {
            const table = document.getElementById(tableConfig[key].tableId);
            if (!table) return [];
            return Array.from(table.querySelectorAll('tbody tr'));
        }

        function filterTable(key) {
            const cfg = tableConfig[key];
            const searchInput = document.getElementById(cfg.searchId);
            if (!searchInput) return;
            const term = searchInput.value.toLowerCase().trim();
            const allRows = getAllRows(key);

            cfg.filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                const match = !term || text.includes(term);
                return match;
            });

            cfg.currentPage = 1;
            renderPage(key);
        }

        function renderPage(key) {
            const cfg = tableConfig[key];
            const allRows = getAllRows(key);
            const rows = cfg.filteredRows;
            const totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));

            if (cfg.currentPage > totalPages) cfg.currentPage = totalPages;

            const start = (cfg.currentPage - 1) * ROWS_PER_PAGE;
            const end = start + ROWS_PER_PAGE;
            const pageRows = new Set(rows.slice(start, end));

            // Show/hide rows
            allRows.forEach(row => {
                row.style.display = pageRows.has(row) ? '' : 'none';
            });

            // Update info text
            const infoEl = document.getElementById(cfg.infoId);
            if (infoEl) {
                if (rows.length === 0) {
                    infoEl.textContent = 'No results found';
                } else {
                    const showStart = start + 1;
                    const showEnd = Math.min(end, rows.length);
                    infoEl.textContent = `Showing ${showStart}-${showEnd} of ${rows.length}`;
                }
            }

            // Render pagination buttons
            const pagEl = document.getElementById(cfg.paginationId);
            if (!pagEl) return;

            if (totalPages <= 1 && rows.length > 0) {
                pagEl.innerHTML = '';
                return;
            }
            if (rows.length === 0) {
                pagEl.innerHTML = '';
                return;
            }

            let html = '';
            html += `<button onclick="goToPage('${key}', ${cfg.currentPage - 1})" ${cfg.currentPage === 1 ? 'disabled' : ''}><i class='bx bx-chevron-left'></i></button>`;

            // Determine which page numbers to show
            let pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (cfg.currentPage > 3) pages.push('...');
                for (let i = Math.max(2, cfg.currentPage - 1); i <= Math.min(totalPages - 1, cfg.currentPage + 1); i++) {
                    pages.push(i);
                }
                if (cfg.currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    html += `<button disabled style="border:none;background:none;cursor:default;">...</button>`;
                } else {
                    html += `<button onclick="goToPage('${key}', ${p})" class="${p === cfg.currentPage ? 'active' : ''}">${p}</button>`;
                }
            });

            html += `<button onclick="goToPage('${key}', ${cfg.currentPage + 1})" ${cfg.currentPage === totalPages ? 'disabled' : ''}><i class='bx bx-chevron-right'></i></button>`;

            pagEl.innerHTML = html;
        }

        function goToPage(key, page) {
            const cfg = tableConfig[key];
            const totalPages = Math.max(1, Math.ceil(cfg.filteredRows.length / ROWS_PER_PAGE));
            if (page < 1 || page > totalPages) return;
            cfg.currentPage = page;
            renderPage(key);
        }

        // Initialize tables on load
        document.addEventListener('DOMContentLoaded', function() {
            ['products', 'inventory'].forEach(key => {
                const cfg = tableConfig[key];
                const table = document.getElementById(cfg.tableId);
                if (table) {
                    cfg.filteredRows = getAllRows(key);
                    renderPage(key);
                }
            });
        });

        // ── Notification Panel ──
        const alertData = {
            outOfStock: <?php echo json_encode($out_of_stock_items); ?>,
            lowStock: <?php echo json_encode($low_stock_notify); ?>,
            totalAlerts: <?php echo $total_alerts; ?>
        };

        let notifPanel = document.getElementById('notificationPanel');
        let notifVisible = false;

        function renderNotificationPanel() {
            const body = document.getElementById('notificationBody');
            if (!body) return;

            let html = '';

            if (alertData.outOfStock && alertData.outOfStock.length > 0) {
                alertData.outOfStock.forEach(item => {
                    html += `
                        <div class="notification-item critical">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-critical">0 ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (alertData.lowStock && alertData.lowStock.length > 0) {
                alertData.lowStock.forEach(item => {
                    html += `
                        <div class="notification-item warning">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-warning">${item.quantity} ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (!html) {
                html = '<div class="empty-notification"><i class="bx bx-check-circle"></i><p>All inventory items are well stocked!</p></div>';
            }

            body.innerHTML = html;
        }

        function goToInventory(id) {
            closeNotificationPanel();
            window.location.href = '../inventory/inventory.php';
        }

        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (notifVisible) {
                panel.style.display = 'none';
                notifVisible = false;
            } else {
                renderNotificationPanel();
                panel.style.display = 'block';
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

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close notification panel when clicking outside
        document.addEventListener('click', function(e) {
            const bell = document.querySelector('.notification-bell');
            if (notifVisible && !notifPanel.contains(e.target) && !bell?.contains(e.target)) {
                closeNotificationPanel();
            }
        });

        // Update notification badge using header.php's badge ID
        document.addEventListener('DOMContentLoaded', function() {
            const badge = document.getElementById('alert-count-badge');
            if (badge && alertData.totalAlerts > 0) {
                badge.textContent = alertData.totalAlerts;
                badge.style.display = 'flex';
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>
