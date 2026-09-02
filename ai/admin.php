<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requireManager();

$page_title = 'Dashboard Overview';
$active_page = 'dashboard';

$branch_id = getCurrentBranchId();

/**
 * All dashboard data scoped to the manager's branch.
 * Shared by the page render and the ?ajax_stats live-polling endpoint,
 * mirroring admin/dashboard.php.
 */
function getManagerDashboardStats(PDO $pdo, $branch_id): array
{
    // Combined branch filter: direct branch_id match + fallback for old orders with NULL branch_id
    $bid = (int)$branch_id;
    $branch_condition = $branch_id ? "AND (branch_id = $bid OR (branch_id IS NULL AND cashier_id IN (SELECT id FROM users WHERE branch_id = $bid)))" : '';

    // Inventory branch filter (single-table queries)
    $inv_branch_condition = $branch_id ? "AND (branch_id = $bid OR branch_id IS NULL)" : '';
    // Inventory branch filter for JOIN queries (need i. prefix)
    $inv_join_condition = $branch_id ? "AND (i.branch_id = $bid OR i.branch_id IS NULL)" : '';

    try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE DATE(date_time) = CURDATE() $branch_condition
    ");
    $stmt->execute();
    $today_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $yesterday_total = 0;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE DATE(date_time) = CURDATE() - INTERVAL 1 DAY $branch_condition
    ");
    $stmt->execute();
    $yesterday_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $trend_percent = 0;
    if ($yesterday_total > 0) {
        $trend_percent = (($today_stats['total'] - $yesterday_total) / $yesterday_total) * 100;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE YEAR(date_time) = YEAR(CURDATE())
          AND MONTH(date_time) = MONTH(CURDATE()) $branch_condition
    ");
    $stmt->execute();
    $month_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM inventory
        WHERE quantity < min_stock
          AND (status IS NULL OR status = 'active')
          AND deleted_at IS NULL $inv_branch_condition
    ");
    $stmt->execute();
    $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);

    $recent_where = $branch_id ? "AND (o.branch_id = $bid OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = $bid)))" : '';
    $stmt = $pdo->prepare("
        SELECT o.order_number, o.date_time, o.total_amount, u.full_name as username
        FROM orders o
        LEFT JOIN users u ON o.cashier_id = u.id
        WHERE 1=1 $recent_where
        ORDER BY o.date_time DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT item_name as name, quantity as stock, min_stock, id
        FROM inventory
        WHERE quantity < min_stock
          AND (status IS NULL OR status = 'active')
          AND deleted_at IS NULL $inv_branch_condition
        ORDER BY quantity ASC
        LIMIT 5
    ");
    $stmt->execute();
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM inventory
        WHERE (status IS NULL OR status = 'active')
          AND deleted_at IS NULL $inv_branch_condition
    ");
    $stmt->execute();
    $total_inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, item_name, quantity, min_stock, unit
        FROM inventory
        WHERE quantity <= 0
          AND deleted_at IS NULL $inv_branch_condition
        ORDER BY item_name ASC
        LIMIT 10
    ");
    $stmt->execute();
    $out_of_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, item_name, quantity, min_stock, unit
        FROM inventory
        WHERE quantity <= min_stock
          AND quantity > 0
          AND deleted_at IS NULL $inv_branch_condition
        ORDER BY quantity ASC
        LIMIT 10
    ");
    $stmt->execute();
    $low_stock_notify = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_alerts = count($out_of_stock_items) + count($low_stock_notify);
    $avg_order = ($today_stats['count'] > 0) ? ($today_stats['total'] / $today_stats['count']) : 0;

    $stmt = $pdo->prepare("
        SELECT m.movement_type, m.quantity, m.notes, m.created_at, i.item_name, i.unit
        FROM inventory_movements m
        JOIN inventory i ON m.inventory_id = i.id
        WHERE 1=1 $inv_join_condition
        ORDER BY m.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT item_name, unit, min_stock, last_updated
        FROM inventory
        WHERE quantity <= 0
          AND deleted_at IS NULL $inv_branch_condition
        ORDER BY item_name ASC
        LIMIT 5
    ");
    $stmt->execute();
    $out_of_stock_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT i.item_name, m.quantity, m.created_at, i.unit
        FROM inventory_movements m
        JOIN inventory i ON m.inventory_id = i.id
        WHERE m.movement_type = 'stock_in'
          AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $inv_join_condition
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_restocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
        error_log("Dashboard Error: " . $e->getMessage());
        $today_stats = ['count' => 0, 'total' => 0];
        $yesterday_total = 0;
        $month_stats = ['count' => 0, 'total' => 0];
        $low_stock = ['count' => 0];
        $recent_orders = [];
        $low_stock_items = [];
        $out_of_stock_items = [];
        $low_stock_notify = [];
        $total_alerts = 0;
        $trend_percent = 0;
        $total_inventory = ['count' => 0];
        $avg_order = 0;
        $recent_movements = [];
        $out_of_stock_details = [];
        $recent_restocks = [];
    }

    return compact('today_stats', 'yesterday_total', 'trend_percent', 'month_stats', 'low_stock', 'low_stock_items', 'total_inventory', 'out_of_stock_items', 'low_stock_notify', 'total_alerts', 'avg_order', 'recent_orders', 'recent_movements', 'out_of_stock_details', 'recent_restocks');
}

// Live-polling endpoint: same branch-scoped data the page renders, as JSON.
if (isset($_GET['ajax_stats'])) {
    header('Content-Type: application/json');
    $stats = getManagerDashboardStats($pdo, getCurrentBranchId());
    echo json_encode([
        'success' => true,
        'today_total' => number_format((float)$stats['today_stats']['total'], 2),
        'today_count' => (int)$stats['today_stats']['count'],
        'avg_order' => number_format((float)$stats['avg_order'], 2),
        'trend_percent' => round((float)$stats['trend_percent'], 1),
        'month_total' => number_format((float)$stats['month_stats']['total'], 2),
        'month_count' => (int)$stats['month_stats']['count'],
        'low_stock_count' => (int)$stats['low_stock']['count'],
        'inventory_total' => (int)$stats['total_inventory']['count'],
        'total_alerts' => (int)$stats['total_alerts'],
        'recent_orders' => array_map(fn($o) => [
            'order_number' => $o['order_number'] ?? '',
            'date_time' => date('M j, g:i A', strtotime($o['date_time'])),
            'amount' => number_format((float)$o['total_amount'], 2),
            'cashier' => $o['username'] ?? 'Unknown',
            'initial' => strtoupper(substr($o['username'] ?? 'U', 0, 1)),
        ], $stats['recent_orders']),
        'low_stock_items' => array_map(fn($i) => [
            'name' => $i['name'],
            'stock' => (int)$i['stock'],
            'min_stock' => (int)$i['min_stock'],
        ], $stats['low_stock_items']),
        'out_of_stock_items' => array_map(fn($i) => [
            'id' => (int)$i['id'],
            'item_name' => $i['item_name'],
            'quantity' => (int)$i['quantity'],
            'unit' => $i['unit'] ?? 'pcs',
            'min_stock' => (int)$i['min_stock'],
        ], $stats['out_of_stock_items']),
        'low_stock_notify' => array_map(fn($i) => [
            'id' => (int)$i['id'],
            'item_name' => $i['item_name'],
            'quantity' => (int)$i['quantity'],
            'unit' => $i['unit'] ?? 'pcs',
            'min_stock' => (int)$i['min_stock'],
        ], $stats['low_stock_notify']),
    ]);
    exit;
}

extract(getManagerDashboardStats($pdo, $branch_id));

// Time-based greeting
$hour = (int)date('G');
if ($hour < 12) $greeting = 'Good Morning';
elseif ($hour < 17) $greeting = 'Good Afternoon';
else $greeting = 'Good Evening';

$first_name = explode(' ', $_SESSION['full_name'] ?? $_SESSION['login_user_id'] ?? 'Admin')[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* ═══════════════ DASHBOARD-SPECIFIC STYLES ═══════════════ */
        /* Base layout, sidebar, header, cards, tables, etc. come from admin.css */

        .header-greeting {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .header-greeting strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-left h1 {
            line-height: 1.2;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .notification-bell:hover {
            background: var(--primary-light);
            border-color: var(--primary);
        }

        .notification-bell i {
            font-size: 1.25rem;
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .notification-bell:hover i {
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--red);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
            border: 2px solid var(--bg-card);
        }

        .header-divider {
            width: 1px;
            height: 28px;
            background: var(--border);
        }

        /* ═══════════════ WELCOME BANNER ═══════════════ */
        .welcome-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .welcome-text h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .welcome-text p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .quick-actions {
            display: flex;
            gap: 8px;
        }

        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            font-family: inherit;
        }

        .quick-action-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .quick-action-btn.primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .quick-action-btn.primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .quick-action-btn i {
            font-size: 1rem;
        }

        /* ═══════════════ STAT CARD OVERRIDES ═══════════════ */
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.sales::before { background: var(--green); }
        .stat-card.orders::before { background: var(--blue); }
        .stat-card.monthly::before { background: var(--purple); }
        .stat-card.alerts::before { background: var(--amber); }

        .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            margin-top: 6px;
        }

        .stat-trend.up {
            color: #059669;
            background: #d1fae5;
        }

        .stat-trend.down {
            color: #dc2626;
            background: #fee2e2;
        }

        .stat-trend.neutral {
            color: var(--text-muted);
            background: var(--bg);
        }

        .stat-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ═══════════════ DASHBOARD CARD OVERRIDES ═══════════════ */
        .card-body {
            padding: 0;
        }

        .card-body-padded {
            padding: 1.25rem;
        }

        .card-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        .card-badge.green { background: var(--green-light); color: var(--green); }
        .card-badge.red { background: var(--red-light); color: var(--red); }
        .card-badge.amber { background: var(--amber-light); color: var(--amber); }

        /* ═══════════════ TABLE CELL HELPERS ═══════════════ */
        .table-amount {
            font-weight: 700;
            color: var(--green);
        }

        .table-order-id {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--primary);
        }

        .table-date {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .table-cashier {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cashier-avatar {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: var(--blue-light);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ═══════════════ STATUS BADGES ═══════════════ */
        .status-critical {
            background: var(--red-light);
            color: var(--red);
        }

        .status-warning {
            background: var(--amber-light);
            color: #b45309;
        }

        .status-ok {
            background: var(--green-light);
            color: var(--green);
        }

        /* ═══════════════ STOCK BAR ═══════════════ */
        .stock-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-bar {
            flex: 1;
            height: 6px;
            background: #f1f5f9;
            border-radius: 3px;
            overflow: hidden;
            max-width: 60px;
        }

        .stock-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .stock-bar-fill.critical { background: var(--red); }
        .stock-bar-fill.warning { background: var(--amber); }

        /* ═══════════════ EMPTY STATE ═══════════════ */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
        }

        .empty-state-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.5rem;
        }

        .empty-state-icon.green { background: var(--green-light); color: var(--green); }
        .empty-state-icon.muted { background: var(--bg); color: var(--text-muted); }

        .empty-state p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .empty-state small {
            font-size: 0.75rem;
            color: var(--text-muted);
            opacity: 0.7;
        }

        /* ═══════════════ AI INSIGHTS ═══════════════ */
        .ai-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ai-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: var(--transition);
        }

        .ai-card:hover {
            box-shadow: var(--shadow);
        }

        .ai-card-header {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .ai-card-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .ai-card-label i {
            font-size: 1rem;
            color: var(--primary);
        }

        .ai-tag {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--primary-light);
            color: var(--primary);
        }

        .refresh-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            border-radius: 6px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .refresh-btn:hover {
            background: var(--bg);
            color: var(--primary);
        }

        .refresh-btn i {
            font-size: 1rem;
        }

        .ai-card-body {
            padding: 0.85rem 1rem;
            font-size: 0.82rem;
            line-height: 1.5;
            color: var(--text-secondary);
            min-height: 56px;
            display: flex;
            align-items: center;
        }

        .ai-card-body p {
            margin: 0;
        }

        /* ═══════════════ AI ASSISTANT ═══════════════ */
        .ai-assistant-card {
            margin-bottom: 1.5rem;
        }

        .ask-row {
            display: flex;
            gap: 8px;
        }

        .ask-row input {
            flex: 1;
            padding: 0.65rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg);
            transition: var(--transition);
        }

        .ask-row input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(243,121,2,0.08);
        }

        .ask-row input::placeholder {
            color: var(--text-muted);
        }

        .ask-row button {
            padding: 0.65rem 1.25rem;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.82rem;
            font-family: inherit;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .ask-row button:hover {
            background: var(--primary-dark);
        }

        .ai-response {
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border-radius: 8px;
            font-size: 0.82rem;
            color: var(--text-secondary);
            line-height: 1.5;
            border: 1px solid var(--border);
        }

        /* ═══════════════ NOTIFICATION PANEL ═══════════════ */
        .notification-panel {
            position: fixed;
            top: 60px;
            right: 16px;
            width: 360px;
            max-width: calc(100vw - 32px);
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: 2000;
            display: none;
            overflow: hidden;
            border: 1px solid var(--border);
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
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
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
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
        }

        .notification-item {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
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
        }

        .notification-item.warning {
            border-left: 3px solid var(--amber);
        }

        .notification-item .item-info {
            flex: 1;
            min-width: 0;
        }

        .notification-item .item-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .notification-item .item-stock {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .stock-critical { color: var(--red) !important; font-weight: 600; }
        .stock-warning { color: var(--amber) !important; font-weight: 600; }

        .notification-item .update-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.72rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-family: inherit;
            white-space: nowrap;
        }

        .notification-item .update-btn:hover {
            background: var(--primary-dark);
        }

        .empty-notification {
            text-align: center;
            padding: 2rem;
        }

        .empty-notification i {
            font-size: 2.5rem;
            color: var(--green);
            margin-bottom: 0.5rem;
        }

        .empty-notification p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* ═══════════════ LOADING ═══════════════ */
        .ai-loading {
            color: var(--text-muted);
            font-style: italic;
        }

        /* ═══════════════ DASHBOARD RESPONSIVE ═══════════════ */
        @media (max-width: 1280px) {
            .ai-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
            .quick-actions { display: none; }
        }

        @media (max-width: 768px) {
            .welcome-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .notification-panel {
                top: auto;
                bottom: 0;
                right: 0;
                width: 100%;
                max-width: 100%;
                border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* ═══════════════ NAV LOGOUT ═══════════════ */
        .nav-item.nav-logout {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
            opacity: 0.8;
        }

        .nav-item.nav-logout:hover {
            opacity: 1;
            color: #fca5a5;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
                        <i class='bx bx-menu'></i>
                    </button>
                    <div class="header-greeting"><?php echo $greeting; ?>, <strong><?php echo htmlspecialchars($first_name); ?></strong></div>
                    <h1>Dashboard Overview</h1>
                </div>
                <div class="header-right">
                    <div class="notification-bell" onclick="toggleNotificationPanel()">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge" id="notificationBadge" style="<?php echo $total_alerts > 0 ? '' : 'display:none'; ?>"><?php echo $total_alerts; ?></span>
                    </div>
                    <div class="header-divider"></div>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['login_user_id'] ?? 'A', 0, 1)); ?>
                        </div>
                        <div>
                            <div class="user-name">
                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['login_user_id'] ?? 'Administrator'); ?>
                            </div>
                            <div class="user-role"><?php echo isOwner() ? 'Owner' : 'Manager'; ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <?php if (!empty($_GET['message'])): ?>
                    <div class="message <?php echo htmlspecialchars($_GET['type'] ?? 'success'); ?>">
                        <i class='bx <?php echo ($_GET['type'] ?? 'success') === 'error' ? 'bx-x-circle' : 'bx-check-circle'; ?>'></i>
                        <?php echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h2>Business at a Glance</h2>
                        <p><?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <div class="quick-actions">
                        <a href="../cashier/pos.php" class="quick-action-btn primary"><i class='bx bx-cart'></i> Open POS</a>
                        <a href="../reports/reports.php" class="quick-action-btn"><i class='bx bx-bar-chart'></i> Reports</a>
                        <a href="../inventory/inventory.php" class="quick-action-btn"><i class='bx bx-package'></i> Inventory</a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div id="live-indicator" style="display:none;font-size:0.72rem;color:#059669;margin-bottom:6px;">● Live</div>
                <div class="stats-grid">
                    <div class="stat-card sales">
                        <div class="stat-header">
                            <div class="stat-title">Today's Sales</div>
                            <div class="stat-icon"><i class='bx bx-peso-sign'></i></div>
                        </div>
                        <div class="stat-value" id="stat-today-total"><?php echo number_format((float)$today_stats['total'], 2); ?></div>
                        <span id="stat-trend">
                        <?php if ($trend_percent != 0): ?>
                            <div class="stat-trend <?php echo $trend_percent > 0 ? 'up' : 'down'; ?>">
                                <i class='bx <?php echo $trend_percent > 0 ? 'bx-trending-up' : 'bx-trending-down'; ?>'></i>
                                <?php echo number_format(abs($trend_percent), 1); ?>% from yesterday
                            </div>
                        <?php else: ?>
                            <div class="stat-trend neutral">Same as yesterday</div>
                        <?php endif; ?>
                        </span>
                    </div>

                    <div class="stat-card orders">
                        <div class="stat-header">
                            <div class="stat-title">Orders Today</div>
                            <div class="stat-icon"><i class='bx bx-receipt'></i></div>
                        </div>
                        <div class="stat-value" id="stat-orders-count"><?php echo (int)$today_stats['count']; ?></div>
                        <div class="stat-sub" id="stat-orders-avg">Avg: <?php echo number_format($avg_order, 2); ?> per order</div>
                    </div>

                    <div class="stat-card monthly">
                        <div class="stat-header">
                            <div class="stat-title">Monthly Revenue</div>
                            <div class="stat-icon"><i class='bx bx-trending-up'></i></div>
                        </div>
                        <div class="stat-value" id="stat-monthly-total"><?php echo number_format((float)$month_stats['total'], 2); ?></div>
                        <div class="stat-sub" id="stat-monthly-sub"><?php echo (int)$month_stats['count']; ?> orders this month</div>
                    </div>

                    <div class="stat-card alerts">
                        <div class="stat-header">
                            <div class="stat-title">Low Stock Items</div>
                            <div class="stat-icon"><i class='bx bx-error'></i></div>
                        </div>
                        <div class="stat-value" id="stat-lowstock-count"><?php echo (int)$low_stock['count']; ?></div>
                        <div class="stat-sub" id="stat-lowstock-sub">Out of <?php echo (int)$total_inventory['count']; ?> total items</div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="content-grid">
                    <!-- Recent Transactions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-transfer'></i> Recent Transactions</h3>
                            <span class="card-badge green" id="recent-transactions-badge"><?php echo count($recent_orders); ?> latest</span>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Cashier</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-transactions-body">
                                        <?php if (empty($recent_orders)): ?>
                                            <tr><td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon muted"><i class='bx bx-receipt'></i></div>
                                                    <p>No transactions yet today</p>
                                                    <small>Sales will appear here once orders come in</small>
                                                </div>
                                            </td></tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td><span class="table-order-id"><?php echo htmlspecialchars($order['order_number']); ?></span></td>
                                                    <td><span class="table-date"><?php echo date('M j, g:i A', strtotime($order['date_time'])); ?></span></td>
                                                    <td><span class="table-amount"><?php echo number_format((float)$order['total_amount'], 2); ?></span></td>
                                                    <td>
                                                        <div class="table-cashier">
                                                            <span class="cashier-avatar"><?php echo strtoupper(substr($order['username'] ?? 'U', 0, 1)); ?></span>
                                                            <?php echo htmlspecialchars($order['username'] ?? 'Unknown'); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-error-circle'></i> Low Stock Alert</h3>
                            <?php if (!empty($low_stock_items)): ?>
                                <span class="card-badge red" id="low-stock-badge"><?php echo count($low_stock_items); ?> items</span>
                            <?php else: ?>
                                <span class="card-badge green" id="low-stock-badge">All good</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="low-stock-body">
                                        <?php if (empty($low_stock_items)): ?>
                                            <tr><td colspan="3">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon green"><i class='bx bx-check-circle'></i></div>
                                                    <p>All items are well stocked</p>
                                                    <small>No inventory items below minimum threshold</small>
                                                </div>
                                            </td></tr>
                                        <?php else: ?>
                                        <?php foreach ($low_stock_items as $item):
                                            $stock_pct = ($item['min_stock'] > 0) ? min(100, ($item['stock'] / $item['min_stock']) * 100) : 0;
                                            $is_out = (int)$item['stock'] === 0;
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                                <td>
                                                    <div class="stock-bar-container">
                                                        <span style="font-weight:600;color:<?php echo $is_out ? 'var(--red)' : '#b45309'; ?>;"><?php echo (int)$item['stock']; ?></span>
                                                        <div class="stock-bar">
                                                            <div class="stock-bar-fill <?php echo $is_out ? 'critical' : 'warning'; ?>" style="width:<?php echo $stock_pct; ?>%"></div>
                                                        </div>
                                                        <span style="font-size:0.7rem;color:var(--text-muted);">/ <?php echo (int)$item['min_stock']; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $is_out ? 'status-critical' : 'status-warning'; ?>">
                                                        <?php echo $is_out ? 'Out of Stock' : 'Low'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Activity Section -->
                <div class="content-grid" style="margin-bottom:1.5rem;">
                    <!-- Recent Stock Movements -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-transfer-alt'></i> Stock Activity</h3>
                            <a href="../inventory/inventory.php" style="font-size:0.75rem;color:var(--primary);text-decoration:none;font-weight:600;">View All</a>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php if (empty($recent_movements)): ?>
                                <div style="text-align:center;padding:2rem;">
                                    <div style="width:48px;height:48px;border-radius:12px;background:var(--bg);display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:1.5rem;color:var(--text-muted);"><i class='bx bx-transfer-alt'></i></div>
                                    <p style="font-size:0.85rem;color:var(--text-muted);">No stock movements yet</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height:320px;overflow-y:auto;">
                                    <?php foreach ($recent_movements as $mv):
                                        $type_icon = 'bx-plus-circle';
                                        $type_color = 'var(--green)';
                                        $type_bg = 'var(--green-light)';
                                        $type_label = 'Stock In';
                                        if ($mv['movement_type'] === 'stock_out') {
                                            $type_icon = 'bx-minus-circle';
                                            $type_color = 'var(--amber)';
                                            $type_bg = 'var(--amber-light)';
                                            $type_label = 'Stock Out';
                                        } elseif ($mv['movement_type'] === 'waste') {
                                            $type_icon = 'bx-trash';
                                            $type_color = 'var(--red)';
                                            $type_bg = 'var(--red-light)';
                                            $type_label = 'Waste';
                                        } elseif ($mv['movement_type'] === 'adjustment') {
                                            $type_icon = 'bx-slider-alt';
                                            $type_color = 'var(--purple)';
                                            $type_bg = 'var(--purple-light)';
                                            $type_label = 'Adjustment';
                                        } elseif ($mv['movement_type'] === 'return') {
                                            $type_icon = 'bx-undo';
                                            $type_color = 'var(--blue)';
                                            $type_bg = 'var(--blue-light)';
                                            $type_label = 'Return';
                                        }
                                    ?>
                                        <div style="display:flex;align-items:center;gap:10px;padding:0.6rem 1.25rem;border-bottom:1px solid #f1f5f9;">
                                            <div style="width:32px;height:32px;border-radius:8px;background:<?php echo $type_bg; ?>;color:<?php echo $type_color; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">
                                                <i class='bx <?php echo $type_icon; ?>'></i>
                                            </div>
                                            <div style="flex:1;min-width:0;">
                                                <div style="font-size:0.82rem;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($mv['item_name']); ?></div>
                                                <div style="font-size:0.7rem;color:var(--text-muted);"><?php echo $type_label; ?> &middot; <?php echo date('M j, g:i A', strtotime($mv['created_at'])); ?></div>
                                            </div>
                                            <div style="font-size:0.82rem;font-weight:700;color:<?php echo $type_color; ?>;">
                                                <?php echo ($mv['movement_type'] === 'stock_in' || $mv['movement_type'] === 'return') ? '+' : '-'; ?><?php echo (int)$mv['quantity']; ?> <?php echo htmlspecialchars($mv['unit'] ?? 'pcs'); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Restock Activity & Out of Stock Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-package'></i> Inventory Status</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php if (!empty($out_of_stock_details)): ?>
                                <div style="padding:0.75rem 1.25rem;background:var(--red-light);border-bottom:1px solid #fecaca;">
                                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--red);margin-bottom:6px;">Out of Stock</div>
                                    <?php foreach ($out_of_stock_details as $oos): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:0.82rem;">
                                            <span style="font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($oos['item_name']); ?></span>
                                            <span style="font-size:0.7rem;color:var(--red);font-weight:600;">0 / <?php echo (int)$oos['min_stock']; ?> <?php echo htmlspecialchars($oos['unit'] ?? 'pcs'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($recent_restocks)): ?>
                                <div style="padding:0.75rem 1.25rem;">
                                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--green);margin-bottom:6px;">Recently Restocked (7 days)</div>
                                    <?php foreach ($recent_restocks as $rs): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:0.82rem;">
                                            <span style="font-weight:500;color:var(--text-primary);"><?php echo htmlspecialchars($rs['item_name']); ?></span>
                                            <div>
                                                <span style="font-size:0.75rem;font-weight:600;color:var(--green);">+<?php echo (int)$rs['quantity']; ?> <?php echo htmlspecialchars($rs['unit'] ?? 'pcs'); ?></span>
                                                <span style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"><?php echo date('M j', strtotime($rs['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?php if (empty($out_of_stock_details)): ?>
                                    <div style="text-align:center;padding:2rem;">
                                        <div style="width:48px;height:48px;border-radius:12px;background:var(--green-light);display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:1.5rem;color:var(--green);"><i class='bx bx-check-circle'></i></div>
                                        <p style="font-size:0.85rem;color:var(--text-muted);">All inventory is well stocked</p>
                                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">No restocks in the last 7 days</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AI Insights -->
                <div class="ai-grid">
                    <div class="ai-card">
                        <div class="ai-card-header">
                            <div class="ai-card-label"><i class='bx bx-line-chart'></i> Sales Insight</div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="ai-tag">AI</span>
                                <button class="refresh-btn" onclick="loadSalesInsights()" aria-label="Refresh sales insights"><i class='bx bx-refresh'></i></button>
                            </div>
                        </div>
                        <div class="ai-card-body" id="ai-sales">
                            <p class="ai-loading">Loading...</p>
                        </div>
                    </div>

                    <div class="ai-card">
                        <div class="ai-card-header">
                            <div class="ai-card-label"><i class='bx bx-package'></i> Inventory Alert</div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="ai-tag">AI</span>
                                <button class="refresh-btn" onclick="loadInventoryPredictions()" aria-label="Refresh inventory predictions"><i class='bx bx-refresh'></i></button>
                            </div>
                        </div>
                        <div class="ai-card-body" id="ai-inventory">
                            <p class="ai-loading">Loading...</p>
                        </div>
                    </div>

                    <div class="ai-card">
                        <div class="ai-card-header">
                            <div class="ai-card-label"><i class='bx bx-trending-up'></i> Tomorrow's Outlook</div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="ai-tag">AI</span>
                                <button class="refresh-btn" onclick="loadAIPredictions()" aria-label="Refresh AI predictions"><i class='bx bx-refresh'></i></button>
                            </div>
                        </div>
                        <div class="ai-card-body" id="ai-prediction">
                            <p class="ai-loading">Loading...</p>
                        </div>
                    </div>
                </div>

                <!-- AI Assistant is now the floating chatbot widget (bottom-right icon) -->
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
    const alertData = {
        outOfStock: <?php echo json_encode($out_of_stock_items); ?>,
        lowStock: <?php echo json_encode($low_stock_notify); ?>,
        totalAlerts: <?php echo $total_alerts; ?>
    };

    const dashboardData = {
        todaySales: <?php echo (float)$today_stats['total']; ?>,
        todayOrders: <?php echo (int)$today_stats['count']; ?>,
        lowStockCount: <?php echo (int)$low_stock['count']; ?>,
        trendPercent: <?php echo (float)$trend_percent; ?>
    };

    let panelVisible = false;

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
                            <div class="item-stock">Stock: <span class="stock-critical">0 ${escapeHtml(item.unit || 'pcs')}</span> (Min: ${item.min_stock})</div>
                        </div>
                        <button class="update-btn" onclick="goToInventory(${item.id})">Restock</button>
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
                            <div class="item-stock">Stock: <span class="stock-warning">${item.quantity} ${escapeHtml(item.unit || 'pcs')}</span> (Min: ${item.min_stock})</div>
                        </div>
                        <button class="update-btn" onclick="goToInventory(${item.id})">Restock</button>
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
        if (panelVisible) {
            panel.style.display = 'none';
            panelVisible = false;
        } else {
            renderNotificationPanel();
            panel.style.display = 'block';
            panelVisible = true;
        }
    }

    function closeNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        if (panel) {
            panel.style.display = 'none';
            panelVisible = false;
        }
    }

    function askQuick(q) {
        document.getElementById('ai-question').value = q;
        askAIQuestion();
    }

    function askAIQuestion() {
        const input = document.getElementById('ai-question');
        const responseBox = document.getElementById('ai-response');
        const btn = document.getElementById('ai-ask-btn');
        const question = input.value.trim();

        if (!question) {
            responseBox.innerHTML = '<div style="color:#e74c3c;">Please enter a question first.</div>';
            return;
        }

        // Show loading state
        responseBox.innerHTML = '<div style="display:flex;align-items:center;gap:8px;color:#F37902;"><i class="bx bx-loader-alt bx-spin" style="font-size:20px;"></i> AI is thinking...</div>';
        btn.disabled = true;
        btn.style.opacity = '0.5';
        input.disabled = true;

        fetch('ai_endpoint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: question })
        })
        .then(async (res) => {
            const text = await res.text();
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Invalid JSON: ' + text.substring(0, 100)); }
        })
        .then((data) => {
            if (data.answer) {
                // Format the response
                let formatted = data.answer
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>')
                    .replace(/^[-•]\s/gm, '&bull; ');
                responseBox.innerHTML = '<div style="line-height:1.6;">' + formatted + '</div>';
            } else {
                responseBox.innerHTML = '<div style="color:#888;">No response received. Try asking differently.</div>';
            }
        })
        .catch((error) => {
            console.error('AI Error:', error);
            responseBox.innerHTML = '<div style="color:#e74c3c;"><i class="bx bx-error-circle"></i> Unable to get response. <a href="../admin/ai_chat.php" style="color:#F37902;">Try the full AI Chat instead</a></div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
            input.disabled = false;
            input.value = '';
            input.focus();
        });
    }

    function loadSalesInsights() {
        const box = document.getElementById('ai-sales');
        if (!box) return;

        box.innerHTML = '<p class="ai-loading">Loading...</p>';

        fetch('dashboard_ai.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sales) {
                    box.innerHTML = `<p>${escapeHtml(data.sales)}</p>`;
                } else {
                    setStaticSalesInsight();
                }
            })
            .catch(() => setStaticSalesInsight());
    }

    function setStaticSalesInsight() {
        const box = document.getElementById('ai-sales');
        const trendText = dashboardData.trendPercent > 0 ?
            `${dashboardData.trendPercent.toFixed(1)}% up from yesterday` :
            (dashboardData.trendPercent < 0 ? `${Math.abs(dashboardData.trendPercent).toFixed(1)}% down from yesterday` : 'steady compared to yesterday');
        box.innerHTML = `<p>Today: ${formatCurrency(dashboardData.todaySales)} from ${dashboardData.todayOrders} orders (${trendText})</p>`;
    }

    function loadInventoryPredictions() {
        const box = document.getElementById('ai-inventory');
        if (!box) return;

        box.innerHTML = '<p class="ai-loading">Loading...</p>';

        fetch('dashboard_ai.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.inventory) {
                    box.innerHTML = `<p>${escapeHtml(data.inventory)}</p>`;
                } else {
                    setStaticInventoryInsight();
                }
            })
            .catch(() => setStaticInventoryInsight());
    }

    function setStaticInventoryInsight() {
        const box = document.getElementById('ai-inventory');
        if (dashboardData.lowStockCount > 0) {
            box.innerHTML = `<p>${dashboardData.lowStockCount} item(s) running low. Check inventory to prevent stockouts.</p>`;
        } else {
            box.innerHTML = `<p>All stock levels are healthy. No restocking needed right now.</p>`;
        }
    }

    function loadAIPredictions() {
        const box = document.getElementById('ai-prediction');
        if (!box) return;

        box.innerHTML = '<p class="ai-loading">Generating prediction...</p>';

        const predictionPrompt = `Based on today's sales of ${formatCurrency(dashboardData.todaySales)} and ${dashboardData.lowStockCount} low stock items, give a one-sentence prediction for tomorrow.`;

        fetch('ai_endpoint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: predictionPrompt })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.answer) {
                box.innerHTML = `<p>${escapeHtml(data.answer)}</p>`;
            } else {
                setStaticPrediction();
            }
        })
        .catch(() => setStaticPrediction());
    }

    function setStaticPrediction() {
        const box = document.getElementById('ai-prediction');
        const trend = dashboardData.todaySales > 10000 ? "Strong" : (dashboardData.todaySales > 5000 ? "Good" : "Steady");
        box.innerHTML = `<p>${trend} sales expected tomorrow. ${dashboardData.lowStockCount > 0 ? 'Consider restocking low items.' : 'Inventory looks good.'}</p>`;
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('notificationPanel');
        const bell = document.querySelector('.notification-bell');
            if (panel && panelVisible && !panel.contains(e.target) && !(bell && bell.contains(e.target))) {
            closeNotificationPanel();
        }
    });

    // ============================================================
    // LIVE UPDATES: poll ?ajax_stats so new cashier orders appear
    // without a manual refresh (same pattern as admin/dashboard.php)
    // ============================================================
    (function() {
        var POLL_INTERVAL_MS = 6000;
        var pollInFlight = false;

        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function renderTrend(percent) {
            var el = document.getElementById('stat-trend');
            if (!el) return;
            percent = parseFloat(percent) || 0;
            if (percent === 0) {
                el.innerHTML = '<div class="stat-trend neutral">Same as yesterday</div>';
                return;
            }
            var up = percent > 0;
            el.innerHTML = '<div class="stat-trend ' + (up ? 'up' : 'down') + '">' +
                '<i class="bx ' + (up ? 'bx-trending-up' : 'bx-trending-down') + '"></i> ' +
                Math.abs(percent).toFixed(1) + '% from yesterday</div>';
        }

        function renderRecentTransactions(orders) {
            var body = document.getElementById('recent-transactions-body');
            if (!body) return;
            var badge = document.getElementById('recent-transactions-badge');
            if (badge) badge.textContent = orders.length + ' latest';
            if (!orders.length) {
                body.innerHTML = '<tr><td colspan="4"><div class="empty-state">' +
                    '<div class="empty-state-icon muted"><i class="bx bx-receipt"></i></div>' +
                    '<p>No transactions yet today</p>' +
                    '<small>Sales will appear here once orders come in</small>' +
                    '</div></td></tr>';
                return;
            }
            body.innerHTML = orders.map(function(o) {
                return '<tr>' +
                    '<td><span class="table-order-id">' + escapeHtml(o.order_number) + '</span></td>' +
                    '<td><span class="table-date">' + escapeHtml(o.date_time) + '</span></td>' +
                    '<td><span class="table-amount">' + escapeHtml(o.amount) + '</span></td>' +
                    '<td><div class="table-cashier">' +
                    '<span class="cashier-avatar">' + escapeHtml(o.initial) + '</span>' +
                    escapeHtml(o.cashier) +
                    '</div></td>' +
                    '</tr>';
            }).join('');
        }

        function renderLowStock(items) {
            var body = document.getElementById('low-stock-body');
            if (!body) return;
            var badge = document.getElementById('low-stock-badge');
            if (!items.length) {
                if (badge) { badge.className = 'card-badge green'; badge.textContent = 'All good'; }
                body.innerHTML = '<tr><td colspan="3"><div class="empty-state">' +
                    '<div class="empty-state-icon green"><i class="bx bx-check-circle"></i></div>' +
                    '<p>All items are well stocked</p>' +
                    '<small>No inventory items below minimum threshold</small>' +
                    '</div></td></tr>';
                return;
            }
            if (badge) { badge.className = 'card-badge red'; badge.textContent = items.length + ' items'; }
            body.innerHTML = items.map(function(i) {
                var pct = i.min_stock > 0 ? Math.min(100, (i.stock / i.min_stock) * 100) : 0;
                var isOut = i.stock === 0;
                return '<tr>' +
                    '<td><strong>' + escapeHtml(i.name) + '</strong></td>' +
                    '<td><div class="stock-bar-container">' +
                    '<span style="font-weight:600;color:' + (isOut ? 'var(--red)' : '#b45309') + ';">' + i.stock + '</span>' +
                    '<div class="stock-bar">' +
                    '<div class="stock-bar-fill ' + (isOut ? 'critical' : 'warning') + '" style="width:' + pct + '%"></div>' +
                    '</div>' +
                    '<span style="font-size:0.7rem;color:var(--text-muted);">/ ' + i.min_stock + '</span>' +
                    '</div></td>' +
                    '<td><span class="status-badge ' + (isOut ? 'status-critical' : 'status-warning') + '">' +
                    (isOut ? 'Out of Stock' : 'Low') +
                    '</span></td>' +
                    '</tr>';
            }).join('');
        }

        function applyLiveStats(data) {
            var liveEl = document.getElementById('live-indicator');
            if (liveEl) {
                liveEl.style.display = '';
                liveEl.textContent = '● Live — updated ' + new Date().toLocaleTimeString();
            }
            setText('stat-today-total', data.today_total);
            setText('stat-orders-count', data.today_count);
            setText('stat-orders-avg', 'Avg: ' + data.avg_order + ' per order');
            setText('stat-monthly-total', data.month_total);
            setText('stat-monthly-sub', data.month_count + ' orders this month');
            setText('stat-lowstock-count', data.low_stock_count);
            setText('stat-lowstock-sub', 'Out of ' + data.inventory_total + ' total items');
            renderTrend(data.trend_percent);
            renderRecentTransactions(data.recent_orders);
            renderLowStock(data.low_stock_items);

            dashboardData.todaySales = parseFloat(String(data.today_total).replace(/,/g, '')) || 0;
            dashboardData.todayOrders = data.today_count;
            dashboardData.lowStockCount = data.low_stock_count;
            dashboardData.trendPercent = data.trend_percent;

            alertData.outOfStock = data.out_of_stock_items || [];
            alertData.lowStock = data.low_stock_notify || [];
            alertData.totalAlerts = data.total_alerts || 0;
            var badgeEl = document.getElementById('notificationBadge');
            if (badgeEl) {
                badgeEl.textContent = alertData.totalAlerts;
                badgeEl.style.display = alertData.totalAlerts > 0 ? '' : 'none';
            }
        }

        function refreshLiveStats() {
            if (document.hidden || pollInFlight) return;
            pollInFlight = true;
            fetch('?ajax_stats=1&_=' + Date.now(), { credentials: 'same-origin' })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.success) applyLiveStats(data);
                }, function() {})
                .then(function() { pollInFlight = false; });
        }

        setInterval(refreshLiveStats, POLL_INTERVAL_MS);
    })();

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        setStaticSalesInsight();
        setStaticInventoryInsight();
        setStaticPrediction();

        const questionInput = document.getElementById('ai-question');
        if (questionInput) {
            questionInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    askAIQuestion();
                }
            });
        }
    });
    </script>

    <?php include __DIR__ . '/../admin/includes/ai_chatbot_widget.php'; ?>
</body>
</html>
