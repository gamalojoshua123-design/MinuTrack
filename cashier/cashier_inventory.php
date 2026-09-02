<?php
require_once __DIR__ . '/../bootstrap.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

if (!hasPermission('inventory_view')) {
    header('Location: ../auth/unauthorized.php');
    exit();
}

$page_title = 'Inventory View';

// Handle stock update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    requireCsrfToken();
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($quantity > 0 && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();

            if (!$item) {
                throw new Exception("Item not found");
            }

            $new_quantity = $item['quantity'] + $quantity;

            $stmt = $pdo->prepare("UPDATE inventory SET quantity = ?, last_updated = NOW() WHERE id = ?");
            $update_result = $stmt->execute([$new_quantity, $item_id]);

            if (!$update_result) {
                throw new Exception("Failed to update inventory");
            }

            $stmt = $pdo->prepare("INSERT INTO inventory_log (item_id, item_name, previous_quantity, quantity_added, new_quantity, user_id, user_name, update_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $log_result = $stmt->execute([
                $item_id,
                $item['item_name'],
                $item['quantity'],
                $quantity,
                $new_quantity,
                $_SESSION['user_id'],
                $_SESSION['full_name'] ?? $_SESSION['username']
            ]);

            if ($log_result) {
                $update_message = "Stock updated successfully! Added " . $quantity . " to " . $item['item_name'] . ". New stock: " . $new_quantity;
            } else {
                $update_message = "Stock updated but log failed. Added " . $quantity . " to " . $item['item_name'];
            }
        } catch (Exception $e) {
            $update_message = "Error updating stock: " . $e->getMessage();
        }
    } else {
        $update_message = "Please enter a valid quantity greater than 0";
    }
}

// Handle restock request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_restock'])) {
    requireCsrfToken();
    $item_id = intval($_POST['item_id'] ?? 0);
    $request_quantity = intval($_POST['request_quantity'] ?? 0);
    $request_note = trim($_POST['request_note'] ?? '');

    if ($item_id > 0 && $request_quantity > 0) {
        try {
            $stmt = $pdo->prepare("SELECT item_name, quantity, min_stock FROM inventory WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();

            if (!$item) {
                throw new Exception("Item not found");
            }

            $stmt = $pdo->prepare("INSERT INTO restock_requests (item_id, item_name, current_quantity, requested_quantity, notes, requested_by, requested_by_name, request_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");
            $result = $stmt->execute([
                $item_id,
                $item['item_name'],
                $item['quantity'],
                $request_quantity,
                $request_note,
                $_SESSION['user_id'],
                $_SESSION['full_name'] ?? $_SESSION['username']
            ]);

            if ($result) {
                $update_message = "Restock request sent to manager successfully! Requested: " . $request_quantity . " " . $item['item_name'];
            } else {
                $update_message = "Failed to send restock request. Please try again.";
            }
        } catch (Exception $e) {
            $update_message = "Error sending request: " . $e->getMessage();
        }
    } else {
        $update_message = "Please enter a valid quantity greater than 0";
    }
}

// Handle bulk update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update'])) {
    requireCsrfToken();
    $bulk_items = $_POST['bulk_items'] ?? [];
    $bulk_quantities = $_POST['bulk_quantities'] ?? [];
    $updated_count = 0;
    $error_count = 0;

    if (!empty($bulk_items)) {
        try {
            foreach ($bulk_items as $item_id) {
                $item_id = intval($item_id);
                $quantity = intval($bulk_quantities[$item_id] ?? 0);

                if ($quantity > 0) {
                    $stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE id = ? AND deleted_at IS NULL");
                    $stmt->execute([$item_id]);
                    $item = $stmt->fetch();

                    if ($item) {
                        $new_quantity = $item['quantity'] + $quantity;

                        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ?, last_updated = NOW() WHERE id = ?");
                        $update_result = $stmt->execute([$new_quantity, $item_id]);

                        if ($update_result) {
                            $stmt = $pdo->prepare("INSERT INTO inventory_log (item_id, item_name, previous_quantity, quantity_added, new_quantity, user_id, user_name, update_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([
                                $item_id,
                                $item['item_name'],
                                $item['quantity'],
                                $quantity,
                                $new_quantity,
                                $_SESSION['user_id'],
                                $_SESSION['full_name'] ?? $_SESSION['username']
                            ]);
                            $updated_count++;
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                    }
                }
            }

            if ($updated_count > 0) {
                $update_message = "Bulk update completed! Updated $updated_count item(s).";
                if ($error_count > 0) {
                    $update_message .= " ($error_count item(s) failed)";
                }
            } else {
                $update_message = "No items were updated. Please select items and enter quantities.";
            }
        } catch (Exception $e) {
            $update_message = "Error in bulk update: " . $e->getMessage();
        }
    } else {
        $update_message = "Please select at least one item to update";
    }
}

// Fetch inventory items
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE deleted_at IS NULL ORDER BY 
    CASE 
        WHEN quantity <= 0 THEN 1
        WHEN quantity <= min_stock THEN 2
        ELSE 3
    END, 
    item_name ASC");
$stmt->execute();
$inventory_items = $stmt->fetchAll();

$total_items = count($inventory_items);
$low_stock = 0;
$out_of_stock = 0;

foreach ($inventory_items as $item) {
    if ($item['quantity'] <= 0) {
        $out_of_stock++;
    } elseif ($item['quantity'] <= $item['min_stock']) {
        $low_stock++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Inventory - Cashier View | Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <style>
        :root {
            --harvest-orange: #F37902;
            --chocolate: #DC6902;
            --apricot-cream: #EDD0A9;
            --copperwood: #BE6B03;
            --bright-lemon: #FAE51D;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
            --white: #ffffff;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --blue: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --red: #e74c3c;
            --info: #3498db;
            --danger: #e74c3c;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        .content-area {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.83rem;
            font-weight: 500;
        }

        .message.success {
            background: #E8F8F0;
            color: #229954;
            border-left: 3px solid var(--success);
        }

        .message.error {
            background: #FEF2F0;
            color: #C0392B;
            border-left: 3px solid var(--danger);
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 0.85rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body { padding: 1.25rem; }

        .filter-section {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-size: 0.85rem;
            opacity: 0.4;
        }

        .search-box input {
            width: 100%;
            padding: 0.55rem 0.75rem 0.55rem 2rem;
            border: 1.5px solid var(--apricot-cream);
            border-radius: 8px;
            font-size: 0.85rem;
            min-height: 44px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--harvest-orange);
        }

        .filter-buttons {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .btn-filter {
            padding: 0.55rem 1rem;
            border: 1.5px solid var(--apricot-cream);
            background: #ffffff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            color: var(--dark-gray);
            font-size: 0.8rem;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            transition: all 0.2s ease;
            min-height: 44px;
        }
        .btn-filter:hover {
            background: var(--apricot-cream);
        }
        .btn-filter.active {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: #ffffff !important;
            border-color: transparent;
        }

        .btn-bulk {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            transition: all 0.2s ease;
            min-height: 44px;
        }
        .btn-bulk:hover {
            background: rgba(255, 255, 255, 0.3) !important;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--apricot-cream);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .stat-card { background: var(--white); padding: 1.25rem; text-align: center; }

        .stat-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .stat-icon.icon-total { background: var(--blue); }
        .stat-icon.icon-low { background: var(--warning); }
        .stat-icon.icon-out { background: var(--red); }

        .stat-title {
            color: #999;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-value {
            color: var(--dark-gray);
            font-size: 1.4rem;
            font-weight: 800;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--apricot-cream);
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.83rem;
        }

        .data-table thead th {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: #ffffff !important;
            padding: 0.7rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .data-table tbody td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid var(--apricot-cream);
            vertical-align: middle;
        }

        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: #FFF8F0; }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-good { background: #d4edda; color: #155724; }
        .status-low { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }

        .text-muted { color: #666; }
        .text-center { text-align: center; }
        .text-danger { color: var(--danger); }
        .text-success { color: var(--success); }

        /* ============================================================
           ACTION BUTTONS - COLORED AND TOUCH-FRIENDLY
           ============================================================ */
        .action-buttons {
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-action {
            padding: 0.4rem 0.7rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.72rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #ffffff !important;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            min-height: 36px;
            min-width: 36px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        /* Green - Add Stock / Update */
        .btn-update {
            background: #27ae60 !important;
            color: #ffffff !important;
        }
        .btn-update:hover {
            background: #219a52 !important;
            transform: scale(1.02);
        }
        .btn-update:active {
            transform: scale(0.95);
        }

        /* Yellow/Orange - Request Restock */
        .btn-request {
            background: #f39c12 !important;
            color: #ffffff !important;
        }
        .btn-request:hover {
            background: #d68910 !important;
            transform: scale(1.02);
        }
        .btn-request:active {
            transform: scale(0.95);
        }

        /* Blue - View History */
        .btn-view {
            background: #3498db !important;
            color: #ffffff !important;
        }
        .btn-view:hover {
            background: #2e86c1 !important;
            transform: scale(1.02);
        }
        .btn-view:active {
            transform: scale(0.95);
        }

        /* Red - Delete / Danger (if needed) */
        .btn-delete {
            background: #e74c3c !important;
            color: #ffffff !important;
        }
        .btn-delete:hover {
            background: #c0392b !important;
            transform: scale(1.02);
        }
        .btn-delete:active {
            transform: scale(0.95);
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal.open { display: flex; }

        .modal-content {
            background: var(--white);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .modal-body { padding: 1.5rem; }

        .form-group { margin-bottom: 1.5rem; }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            min-height: 44px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--harvest-orange);
        }

        .form-control[readonly] {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        textarea.form-control { resize: vertical; min-height: 80px; }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            min-height: 44px;
        }

        .btn-modal-primary { background: var(--harvest-orange); color: white; }
        .btn-modal-primary:hover { background: var(--chocolate); }
        .btn-modal-secondary { background: var(--light-gray); color: var(--dark-gray); }
        .btn-modal-secondary:hover { background: #ddd; }

        /* ============================================================
           RESPONSIVE - TABLET & MOBILE
           ============================================================ */
        @media (max-width: 768px) {
            .content-area { padding: 0 0.75rem; margin: 0.75rem auto; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-section { flex-direction: column; }
            .search-box { width: 100%; min-width: 100%; }
            .filter-buttons { width: 100%; justify-content: center; }
            .action-buttons { flex-direction: column; gap: 0.3rem; }
            .btn-action {
                width: 100%;
                justify-content: center;
                min-height: 44px;
                padding: 0.5rem 0.85rem;
                font-size: 0.8rem;
            }
            .data-table { font-size: 0.75rem; }
            .data-table th,
            .data-table td {
                padding: 0.4rem 0.5rem;
                white-space: nowrap;
            }
            .btn-bulk { width: 100%; justify-content: center; }
        }

        @media (pointer: coarse) {
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                touch-action: pan-x pinch-zoom;
            }
            .btn-action {
                min-height: 44px;
                min-width: 44px;
                padding: 0.5rem 0.85rem;
                font-size: 0.8rem;
            }
            .btn-filter {
                min-height: 44px;
                padding: 0.5rem 1rem;
            }
            .btn-bulk {
                min-height: 44px;
                padding: 0.5rem 1rem;
            }
            .modal-close {
                min-width: 44px;
                min-height: 44px;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>

    <div class="content-area">
        <?php if (!empty($update_message)): ?>
            <div class="message <?php echo (strpos($update_message, 'Error') !== false || strpos($update_message, 'Failed') !== false) ? 'error' : 'success'; ?>">
                <i class='bx <?php echo strpos($update_message, 'Error') !== false ? 'bx-error-circle' : 'bx-check-circle'; ?>'></i>
                <?php echo htmlspecialchars($update_message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon icon-total"><i class='bx bx-package'></i></div>
                    <div>
                        <div class="stat-title">Items in Stock</div>
                        <div class="stat-value"><?php echo $total_items - $out_of_stock; ?></div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon icon-low"><i class='bx bx-error'></i></div>
                    <div>
                        <div class="stat-title">Low Stock Items</div>
                        <div class="stat-value"><?php echo $low_stock; ?></div>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon icon-out"><i class='bx bx-error-circle'></i></div>
                    <div>
                        <div class="stat-title">Out of Stock</div>
                        <div class="stat-value"><?php echo $out_of_stock; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" id="searchInventory" placeholder="Search items by name...">
            </div>
            <div class="filter-buttons">
                <button type="button" class="btn-filter active" data-filter="all">All Items</button>
                <button type="button" class="btn-filter" data-filter="low">Low Stock</button>
                <button type="button" class="btn-filter" data-filter="out">Out of Stock</button>
                <button type="button" class="btn-filter" data-filter="good">Good Stock</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class='bx bx-package'></i>
                    Current Inventory Levels
                </h3>
                <div class="action-buttons">
                    <button type="button" class="btn-bulk" id="btnBulkUpdate">
                        <i class='bx bx-package'></i> Bulk Update
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="data-table" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Item Name</th>
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
                                    <td colspan="6" class="text-center text-muted">No inventory items found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory_items as $item):
                                    $status_class = 'status-good';
                                    $status_text = 'Good';
                                    $data_status = 'good';

                                    if ($item['quantity'] <= 0) {
                                        $status_class = 'status-critical';
                                        $status_text = 'Out of Stock';
                                        $data_status = 'out';
                                    } elseif ($item['quantity'] <= $item['min_stock']) {
                                        $status_class = 'status-low';
                                        $status_text = 'Low Stock';
                                        $data_status = 'low';
                                    }
                                ?>
                                    <tr data-status="<?php echo htmlspecialchars($data_status); ?>">
                                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                        <td class="stock-value"><?php echo (int) $item['quantity']; ?></td>
                                        <td><?php echo (int) $item['min_stock']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo date('M j, Y', strtotime($item['last_updated'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button"
                                                    class="btn-action btn-update"
                                                    data-action="update"
                                                    data-id="<?php echo (int) $item['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-quantity="<?php echo (int) $item['quantity']; ?>">
                                                    <i class='bx bx-plus'></i> Add Stock
                                                </button>
                                                <?php if ($item['quantity'] <= $item['min_stock']): ?>
                                                <button type="button"
                                                    class="btn-action btn-request"
                                                    data-action="request"
                                                    data-id="<?php echo (int) $item['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-quantity="<?php echo (int) $item['quantity']; ?>"
                                                    data-min-stock="<?php echo (int) $item['min_stock']; ?>">
                                                    <i class='bx bx-cart'></i> Request
                                                </button>
                                                <?php endif; ?>
                                                <button type="button"
                                                    class="btn-action btn-view"
                                                    data-action="history"
                                                    data-id="<?php echo (int) $item['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class='bx bx-history'></i> History
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted" style="margin-top: 1rem; text-align: right;">
                    <i class='bx bx-info-circle'></i>
                    You can update stock levels and request restocks. All actions are logged for accountability.
                </p>
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class='bx bx-package'></i> Add Stock</h3>
                <button type="button" class="modal-close" data-close-modal="updateModal" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="updateForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="item_id" id="update_item_id">
                    <input type="hidden" name="update_stock" value="1">

                    <div class="form-group">
                        <label for="update_item_name">Item Name</label>
                        <input type="text" class="form-control" id="update_item_name" readonly>
                    </div>

                    <div class="form-group">
                        <label for="update_current_stock">Current Stock</label>
                        <input type="text" class="form-control" id="update_current_stock" readonly>
                    </div>

                    <div class="form-group">
                        <label for="update_quantity">Quantity to Add</label>
                        <input type="number" name="quantity" class="form-control" id="update_quantity" min="1" required placeholder="Enter quantity">
                        <small class="text-muted">Enter the number of items to add to stock</small>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-modal btn-modal-secondary" data-close-modal="updateModal">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restock Request Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class='bx bx-cart'></i> Request Restock</h3>
                <button type="button" class="modal-close" data-close-modal="requestModal" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="requestForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="item_id" id="request_item_id">
                    <input type="hidden" name="request_restock" value="1">

                    <div class="form-group">
                        <label for="request_item_name">Item Name</label>
                        <input type="text" class="form-control" id="request_item_name" readonly>
                    </div>

                    <div class="form-group">
                        <label for="request_current_stock">Current Stock</label>
                        <input type="text" class="form-control" id="request_current_stock" readonly>
                    </div>

                    <div class="form-group">
                        <label for="request_min_stock">Minimum Stock Level</label>
                        <input type="text" class="form-control" id="request_min_stock" readonly>
                    </div>

                    <div class="form-group">
                        <label for="request_quantity">Quantity to Request</label>
                        <input type="number" name="request_quantity" class="form-control" id="request_quantity" min="1" required>
                        <small class="text-muted">Recommended: 2x minimum stock level</small>
                    </div>

                    <div class="form-group">
                        <label for="request_note">Notes (Optional)</label>
                        <textarea name="request_note" id="request_note" class="form-control" rows="3" placeholder="Add any notes for the manager..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-modal btn-modal-secondary" data-close-modal="requestModal">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-primary">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Item History Modal -->
    <div class="modal" id="historyModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class='bx bx-history'></i> <span id="historyItemName">Item History</span></h3>
                <button type="button" class="modal-close" data-close-modal="historyModal" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center text-muted">Loading history...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal" id="bulkModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class='bx bx-package'></i> Bulk Stock Update</h3>
                <button type="button" class="modal-close" data-close-modal="bulkModal" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="bulkForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="bulk_update" value="1">

                    <div class="form-group">
                        <label>Select Items to Update</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--apricot-cream); border-radius: 8px; padding: 1rem;">
                            <?php foreach ($inventory_items as $item): ?>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; border-bottom: 1px solid var(--light-gray);">
                                <input type="checkbox" name="bulk_items[]" value="<?php echo (int) $item['id']; ?>" id="bulk_<?php echo (int) $item['id']; ?>">
                                <label for="bulk_<?php echo (int) $item['id']; ?>" style="flex: 1; cursor: pointer;">
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    <span class="text-muted"> (Current: <?php echo (int) $item['quantity']; ?>)</span>
                                </label>
                                <input type="number" name="bulk_quantities[<?php echo (int) $item['id']; ?>]" min="0" placeholder="Qty to add" style="width: 100px; padding: 0.3rem; border: 1px solid var(--apricot-cream); border-radius: 4px;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-modal btn-modal-secondary" data-close-modal="bulkModal">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-primary">Update Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    /* ════════════════════════════════════════════════════════════════
       ES5 COMPATIBLE JAVASCRIPT - WORKS ON OLD TABLETS
       ════════════════════════════════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', function() {

        /* -------- DOM Helpers -------- */
        function $(id) { return document.getElementById(id); }
        function $$(selector) { return document.querySelectorAll(selector); }

        /* -------- Modal Functions -------- */
        function openModal(modalId) {
            var modal = $(modalId);
            if (modal) modal.classList.add('open');
        }

        function closeModal(modalId) {
            var modal = $(modalId);
            if (modal) modal.classList.remove('open');
        }

        /* -------- Escape HTML -------- */
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        /* -------- Filter Items -------- */
        function filterItems(type, activeButton) {
            var buttons = document.querySelectorAll('.btn-filter');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            if (activeButton) activeButton.classList.add('active');

            var rows = document.querySelectorAll('#inventoryTable tbody tr[data-status]');
            for (var j = 0; j < rows.length; j++) {
                var row = rows[j];
                var status = row.getAttribute('data-status');
                if (type === 'all' || status === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        /* -------- Show Update Form -------- */
        function showUpdateForm(id, name, currentStock) {
            $('update_item_id').value = id;
            $('update_item_name').value = name;
            $('update_current_stock').value = currentStock;
            $('update_quantity').value = '';
            openModal('updateModal');
        }

        /* -------- Show Request Form -------- */
        function showRequestForm(id, name, currentStock, minStock) {
            $('request_item_id').value = id;
            $('request_item_name').value = name;
            $('request_current_stock').value = currentStock;
            $('request_min_stock').value = minStock;
            $('request_quantity').value = Math.max(minStock * 2, 1);
            openModal('requestModal');
        }

        /* -------- View Item History (uses XMLHttpRequest) -------- */
        function viewItemHistory(id, name) {
            $('historyItemName').textContent = name + ' - History';
            $('historyContent').innerHTML = '<div class="text-center text-muted">Loading history...</div>';
            openModal('historyModal');

            // Use XMLHttpRequest instead of fetch (compatible with old browsers)
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../api/get_item_history.php?id=' + encodeURIComponent(id), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success && data.history && data.history.length > 0) {
                                var html = '<div class="table-container"><table class="data-table" style="width:100%;">';
                                html += '<thead><tr><th>Date</th><th>Previous</th><th>Added</th><th>New</th><th>User</th></tr></thead><tbody>';

                                for (var i = 0; i < data.history.length; i++) {
                                    var entry = data.history[i];
                                    var dateStr = entry.update_date ? new Date(entry.update_date).toLocaleString() : '';
                                    html += '<tr>';
                                    html += '<td>' + escapeHtml(dateStr) + '</td>';
                                    html += '<td>' + escapeHtml(entry.previous_quantity) + '</td>';
                                    html += '<td class="text-success">+' + escapeHtml(entry.quantity_added) + '</td>';
                                    html += '<td>' + escapeHtml(entry.new_quantity) + '</td>';
                                    html += '<td>' + escapeHtml(entry.user_name) + '</td>';
                                    html += '</tr>';
                                }

                                html += '</tbody></table></div>';
                                $('historyContent').innerHTML = html;
                            } else {
                                $('historyContent').innerHTML = '<div class="text-center text-muted">No history found for this item.</div>';
                            }
                        } catch (e) {
                            $('historyContent').innerHTML = '<div class="text-center text-danger">Error parsing history data.</div>';
                        }
                    } else {
                        $('historyContent').innerHTML = '<div class="text-center text-danger">Error loading history (HTTP ' + xhr.status + ').</div>';
                    }
                }
            };
            xhr.send();
        }

        /* -------- Search -------- */
        var searchInput = $('searchInventory');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                var searchText = this.value.toLowerCase();
                var rows = document.querySelectorAll('#inventoryTable tbody tr[data-status]');
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var cells = row.cells;
                    var itemName = cells[0] ? cells[0].textContent.toLowerCase() : '';
                    if (itemName.indexOf(searchText) !== -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        /* -------- Filter Buttons -------- */
        var filterButtons = document.querySelectorAll('.btn-filter');
        for (var i = 0; i < filterButtons.length; i++) {
            (function(btn) {
                btn.addEventListener('click', function() {
                    filterItems(this.getAttribute('data-filter'), this);
                });
            })(filterButtons[i]);
        }

        /* -------- Bulk Update Button -------- */
        var bulkBtn = $('btnBulkUpdate');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function() {
                openModal('bulkModal');
            });
        }

        /* -------- Table Action Buttons (Event Delegation) -------- */
        var inventoryTable = $('inventoryTable');
        if (inventoryTable) {
            inventoryTable.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-action]');
                if (!btn) return;

                var action = btn.getAttribute('data-action');
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var name = btn.getAttribute('data-name') || '';
                var quantity = parseInt(btn.getAttribute('data-quantity'), 10) || 0;
                var minStock = parseInt(btn.getAttribute('data-min-stock'), 10) || 0;

                if (action === 'update') {
                    showUpdateForm(id, name, quantity);
                } else if (action === 'request') {
                    showRequestForm(id, name, quantity, minStock);
                } else if (action === 'history') {
                    viewItemHistory(id, name);
                }
            });
        }

        /* -------- Close Modal Buttons -------- */
        var closeButtons = document.querySelectorAll('[data-close-modal]');
        for (var i = 0; i < closeButtons.length; i++) {
            (function(btn) {
                btn.addEventListener('click', function() {
                    closeModal(this.getAttribute('data-close-modal'));
                });
            })(closeButtons[i]);
        }

        /* -------- Click Outside Modal to Close -------- */
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

        /* -------- Form Validation (ES5 compatible) -------- */
        var updateForm = $('updateForm');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                var quantity = $('update_quantity').value;
                if (!quantity || parseInt(quantity, 10) <= 0) {
                    e.preventDefault();
                    showToastMsg('Please enter a valid quantity greater than 0', 'warning');
                    return false;
                }
                e.preventDefault();
                askConfirmCallback('Are you sure you want to add ' + quantity + ' items to stock?', function() {
                    updateForm.submit();
                });
                return false;
            });
        }

        var requestForm = $('requestForm');        if (requestForm) {
            requestForm.addEventListener('submit', function(e) {
                var quantity = $('request_quantity').value;
                if (!quantity || parseInt(quantity, 10) <= 0) {
                    e.preventDefault();
                    showToastMsg('Please enter a valid quantity greater than 0', 'warning');
                    return false;
                }
                e.preventDefault();
                askConfirmCallback('Send restock request for ' + quantity + ' items?', function() {
                    requestForm.submit();
                });
                return false;
            });
        }

        var bulkForm = $('bulkForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', function(e) {
                var checkedItems = document.querySelectorAll('input[name="bulk_items[]"]:checked');
                if (checkedItems.length === 0) {
                    e.preventDefault();
                    showToastMsg('Please select at least one item to update', 'warning');
                    return false;
                }

                var hasQuantity = false;
                for (var i = 0; i < checkedItems.length; i++) {
                    var qtyInput = document.querySelector('input[name="bulk_quantities[' + checkedItems[i].value + ']"]');
                    if (qtyInput && parseInt(qtyInput.value, 10) > 0) {
                        hasQuantity = true;
                        break;
                    }
                }

                if (!hasQuantity) {
                    e.preventDefault();
                    showToastMsg('Please enter quantities for selected items', 'warning');
                    return false;
                }

                e.preventDefault();
                askConfirmCallback('Update stock for selected items?', function() {
                    bulkForm.submit();
                });
                return false;
            });
        }

        /* -------- URL Filter Support -------- */
        var urlParams = new URLSearchParams(window.location.search);
        var urlFilter = urlParams.get('filter');
        if (urlFilter === 'low' || urlFilter === 'out') {
            var filterBtn = document.querySelector('.btn-filter[data-filter="' + urlFilter + '"]');
            if (filterBtn) {
                filterBtn.click();
            }
        }

    }); // END DOMContentLoaded
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>