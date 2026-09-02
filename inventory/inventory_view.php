<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('inventory_view');
require_once __DIR__ . '/../includes/inventory_functions.php';

// Fetch inventory items (branch-scoped)
$branchCond = getInventoryBranchConditionAlias('i');
$sql = "SELECT i.* FROM inventory i WHERE i.deleted_at IS NULL";
if ($branchCond) {
    $sql .= $branchCond;
}
$sql .= " ORDER BY CASE 
        WHEN i.quantity <= 0 THEN 1
        WHEN i.quantity <= i.min_stock THEN 2
        ELSE 3
    END, i.item_name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Calculate statistics
$total_items = count($inventory_items);
$low_stock_count = 0;
$out_of_stock_count = 0;
$total_value = 0;
$critical_count = 0;

foreach ($inventory_items as $item) {
    if ($item['quantity'] <= 0) {
        $out_of_stock_count++;
        $critical_count++;
    } elseif ($item['quantity'] <= $item['min_stock']) {
        $low_stock_count++;
        if ($item['quantity'] <= $item['min_stock'] * 0.5) {
            $critical_count++;
        }
    }
    // Estimate value using actual cost_price from database
    $cost = isset($item['cost_price']) ? (float)$item['cost_price'] : 0;
    $total_value += $item['quantity'] * $cost;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory View - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #F37902ff;
            --primary-dark: #DC6902ff;
            --primary-light: #FF9F4D;
            --secondary: #FAE51Dff;
            --accent: #EDD0A9ff;
            --success: #27ae60;
            --success-light: #d4edda;
            --warning: #f39c12;
            --warning-light: #fff3cd;
            --danger: #e74c3c;
            --danger-light: #f8d7da;
            --info: #3498db;
            --info-light: #d1ecf1;
            --dark: #2c3e50;
            --gray: #7f8c8d;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo img {
            height: 55px;
            width: 55px;
            border-radius: 50%;
            border: 3px solid var(--secondary);
            object-fit: cover;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .logo img:hover {
            transform: rotate(5deg) scale(1.1);
            border-color: white;
        }

        .logo span {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
        }

        .user-badge i {
            font-size: 1.1rem;
        }

        .btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.7rem 1.4rem;
            border-radius: 40px;
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 500;
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.4);
            box-shadow: var(--shadow);
        }

        .btn i {
            font-size: 1.1rem;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Title */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .title-icon {
            font-size: 2.2rem;
            color: var(--primary);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .readonly-badge {
            background: linear-gradient(135deg, var(--info), #2980b9);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .readonly-badge i {
            font-size: 1rem;
        }

        /* Info Note */
        .info-note {
            background: linear-gradient(135deg, #e8f4fd, #d1e9ff);
            color: var(--info);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 6px solid var(--info);
            box-shadow: var(--shadow-sm);
            font-weight: 500;
        }

        .info-note i {
            font-size: 1.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 50%;
            color: var(--info);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: transparent;
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-icon.total {
            background: linear-gradient(135deg, var(--info), #2980b9);
        }

        .stat-icon.low {
            background: linear-gradient(135deg, var(--warning), #e67e22);
        }

        .stat-icon.out {
            background: linear-gradient(135deg, var(--danger), #c0392b);
        }

        .stat-icon.value {
            background: linear-gradient(135deg, var(--success), #229954);
            font-size: 2rem;
            font-weight: 800;
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.4rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-info .value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.2;
        }

        .stat-info .sub-text {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.3rem;
        }

        /* Alert Banner */
        .alert-banner {
            background: linear-gradient(135deg, var(--warning-light), #ffe5b4);
            color: var(--warning);
            padding: 1.2rem 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            border-left: 6px solid var(--warning);
            box-shadow: var(--shadow);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-banner.critical {
            background: linear-gradient(135deg, var(--danger-light), #fcc);
            border-left-color: var(--danger);
            color: var(--danger);
        }

        .alert-banner i {
            font-size: 2rem;
            background: white;
            padding: 0.8rem;
            border-radius: 50%;
            box-shadow: var(--shadow);
        }

        .alert-banner strong {
            font-size: 1.1rem;
            margin-right: 0.3rem;
        }

        /* Table Card */
        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .table-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .table-header i {
            font-size: 1.5rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem;
            border-radius: 10px;
        }

        .table-container {
            overflow-x: auto;
            padding: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--light-gray);
            padding: 1.2rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary-light);
        }

        td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid #e9ecef;
            color: var(--dark);
            font-size: 0.95rem;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover td {
            background: linear-gradient(135deg, #f8f9fa, #f1f3f5);
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
        }

        /* Status Badges */
        .status-container {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }

        .indicator-good {
            background: var(--success);
            box-shadow: 0 0 10px var(--success);
        }

        .indicator-low {
            background: var(--warning);
            box-shadow: 0 0 10px var(--warning);
        }

        .indicator-critical {
            background: var(--danger);
            box-shadow: 0 0 10px var(--danger);
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1px solid transparent;
        }

        .status-good {
            background: var(--success-light);
            color: var(--success);
            border-color: var(--success);
        }

        .status-warning {
            background: var(--warning-light);
            color: var(--warning);
            border-color: var(--warning);
        }

        .status-critical {
            background: var(--danger-light);
            color: var(--danger);
            border-color: var(--danger);
        }

        /* Stock Values */
        .stock-value {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .stock-normal {
            color: var(--success);
        }

        .stock-warning {
            color: var(--warning);
        }

        .stock-critical {
            color: var(--danger);
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .min-stock-badge {
            background: var(--light-gray);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            color: var(--gray);
            margin-left: 0.5rem;
        }

        /* Notes */
        .note-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            width: fit-content;
        }

        .note-critical {
            background: var(--danger-light);
            color: var(--danger);
        }

        .note-warning {
            background: var(--warning-light);
            color: var(--warning);
        }

        .note-good {
            background: var(--success-light);
            color: var(--success);
        }

        .note-text i {
            font-size: 1rem;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            margin-top: 3rem;
            padding: 1.5rem;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 2px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .footer-note i {
            color: var(--primary);
            animation: spin 4s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--light-gray);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 1.2rem;
                padding: 1.2rem;
            }

            .logo {
                flex-direction: column;
                gap: 0.8rem;
            }

            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                align-items: start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                padding: 1rem;
            }

            th, td {
                padding: 1rem 0.8rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .stat-card {
                padding: 1.2rem;
            }

            .stat-icon {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
            }

            .stat-info .value {
                font-size: 1.5rem;
            }

            .table-header {
                padding: 1.2rem;
            }

            .status-badge {
                padding: 0.3rem 0.8rem;
                font-size: 0.8rem;
            }
        }

        /* Print Styles */
        @media print {
            .header, .btn, .info-note, .readonly-badge, .footer-note {
                display: none;
            }

            body {
                background: white;
            }

            .table-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <img src="/minute1/img/logo (1)/mblogo (1).png" alt="Minute Burger">
            <span>Minute Burger</span>
        </div>
        <div class="user-info">
            <span class="user-badge">
                <i class='bx bx-user-circle'></i>
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                (<?php echo ucfirst($_SESSION['role'] ?? 'cashier'); ?>)
            </span>
            <a href="../cashier/pos.php" class="btn">
                <i class='bx bx-cart'></i> 
                <span class="hide-mobile">Back to POS</span>
            </a>
            <a href="../auth/logout.php" class="btn">
                <i class='bx bx-log-out'></i> 
                <span class="hide-mobile">Logout</span>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class='bx bx-package title-icon'></i>
                <h1>Inventory Overview</h1>
            </div>
            <span class="readonly-badge">
                <i class='bx bx-low-vision'></i>
                Read-Only View
            </span>
        </div>

        <!-- Info Note -->
        <div class="info-note">
            <i class='bx bx-info-circle'></i>
            <span><strong>Note:</strong> You're viewing inventory in read-only mode. For changes, please contact a manager or administrator.</span>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class='bx bx-package'></i>
                </div>
                <div class="stat-info">
                    <h3>Total Items</h3>
                    <div class="value"><?php echo $total_items; ?></div>
                    <div class="sub-text">unique ingredients</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon low">
                    <i class='bx bx-error'></i>
                </div>
                <div class="stat-info">
                    <h3>Low Stock</h3>
                    <div class="value"><?php echo $low_stock_count; ?></div>
                    <div class="sub-text">items below minimum</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon out">
                    <i class='bx bx-error-circle'></i>
                </div>
                <div class="stat-info">
                    <h3>Out of Stock</h3>
                    <div class="value"><?php echo $out_of_stock_count; ?></div>
                    <div class="sub-text">items unavailable</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon value">₱</div>
                <div class="stat-info">
                    <h3>Est. Value</h3>
                    <div class="value">₱<?php echo number_format($total_value, 0); ?></div>
                    <div class="sub-text">estimated inventory cost</div>
                </div>
            </div>
        </div>

        <!-- Alert Banner -->
        <?php if ($out_of_stock_count > 0): ?>
            <div class="alert-banner critical">
                <i class='bx bx-error-circle'></i>
                <div>
                    <strong>Critical Alert!</strong> <?php echo $out_of_stock_count; ?> item(s) are completely out of stock and need immediate reordering.
                </div>
            </div>
        <?php elseif ($critical_count > 0): ?>
            <div class="alert-banner critical">
                <i class='bx bx-error'></i>
                <div>
                    <strong>Attention Needed!</strong> <?php echo $critical_count; ?> item(s) are critically low and need to be reordered soon.
                </div>
            </div>
        <?php elseif ($low_stock_count > 0): ?>
            <div class="alert-banner">
                <i class='bx bx-time'></i>
                <div>
                    <strong>Heads Up!</strong> <?php echo $low_stock_count; ?> item(s) are running low on stock. Plan to reorder soon.
                </div>
            </div>
        <?php endif; ?>

        <!-- Inventory Table -->
        <div class="table-card">
            <div class="table-header">
                <i class='bx bx-list-ul'></i>
                Current Inventory Levels
                <span style="margin-left: auto; font-size: 0.9rem; opacity: 0.9;">
                    <i class='bx bx-time'></i> real-time data
                </span>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Last Updated</th>
                            <th>Action Needed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_items as $item):
                            // Determine status
                            if ($item['quantity'] <= 0) {
                                $status_text = 'Out of Stock';
                                $status_class = 'critical';
                                $indicator_class = 'indicator-critical';
                                $badge_class = 'status-critical';
                                $stock_class = 'stock-critical';
                                $note_class = 'note-critical';
                                $note_icon = 'bx-error-circle';
                                $note_text = 'Reorder immediately';
                            } elseif ($item['quantity'] <= $item['min_stock']) {
                                $status_text = 'Low Stock';
                                $status_class = 'warning';
                                $indicator_class = 'indicator-low';
                                $badge_class = 'status-warning';
                                $stock_class = 'stock-warning';
                                $note_class = 'note-warning';
                                $note_icon = 'bx-time';
                                $note_text = 'Reorder soon';
                            } else {
                                $status_text = 'Good';
                                $status_class = 'good';
                                $indicator_class = 'indicator-good';
                                $badge_class = 'status-good';
                                $stock_class = 'stock-normal';
                                $note_class = 'note-good';
                                $note_icon = 'bx-check';
                                $note_text = 'Sufficient';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="status-container">
                                        <span class="status-indicator <?php echo $indicator_class; ?>"></span>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <i class='bx <?php echo $note_icon; ?>'></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="stock-value <?php echo $stock_class; ?>">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                    <span class="min-stock-badge">min: <?php echo $item['min_stock']; ?></span>
                                </td>
                                <td><?php echo $item['min_stock']; ?></td>
                                <td>
                                    <i class='bx bx-calendar' style="color: var(--gray); margin-right: 0.3rem;"></i>
                                    <?php echo date('M j, Y', strtotime($item['last_updated'])); ?>
                                    <br>
                                    <small style="color: var(--gray);">
                                        <i class='bx bx-time'></i>
                                        <?php echo date('g:i A', strtotime($item['last_updated'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="note-text <?php echo $note_class; ?>">
                                        <i class='bx <?php echo $note_icon; ?>'></i>
                                        <?php echo $note_text; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="footer-note">
            <i class='bx bx-time'></i>
            Last updated: <?php echo date('F j, Y \a\t g:i A'); ?>
            <span class="loading" style="margin-left: 1rem;"></span>
        </div>
    </div>
</body>

</html>