<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('cashiers_manage');

$active_tab = 'cashiers';

// Branch-based authorization: Manager is restricted to their branch, Owner sees all
$user_branch_id = getCurrentBranchId();
$is_owner = isOwner();

/**
 * Cashier performance stats scoped to the manager's branch (Owner sees all).
 * Shared by the page render and the ?ajax_stats live-polling endpoint.
 */
function getCashierStats(PDO $pdo, bool $is_owner, $user_branch_id): array
{
    $branch_filter = '';
    $branch_params = [];
    $subquery_branch_filter = '';
    if (!$is_owner && $user_branch_id !== null) {
        $branch_filter = ' AND u.branch_id = ?';
        $subquery_branch_filter = ' AND branch_id = ?';
        $branch_params = [(int)$user_branch_id];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.user_id,
                u.full_name,
                u.status,
                u.last_activity as user_last_activity,
                u.branch_id,
                b.branch_name,
                COUNT(o.id) as today_orders,
                COALESCE(SUM(o.total_amount), 0) as today_revenue,
                COALESCE(AVG(o.total_amount), 0) as avg_order,
                MAX(o.date_time) as last_activity
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            LEFT JOIN orders o ON u.id = o.cashier_id AND DATE(o.date_time) = CURDATE()
            WHERE u.role = 'cashier'" . $branch_filter . "
            GROUP BY u.id, u.user_id, u.full_name, u.status, u.last_activity, u.branch_id, b.branch_name
            ORDER BY today_revenue DESC
        ");
        $stmt->execute($branch_params);
        $cashier_performance = $stmt->fetchAll();
    } catch (PDOException $e) {
        $cashier_performance = [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM orders
            WHERE DATE(date_time) = CURDATE()
            AND cashier_id IN (SELECT id FROM users WHERE role = 'cashier'" . $subquery_branch_filter . ")
        ");
        $stmt->execute($branch_params);
        $cashier_revenue = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT COALESCE(AVG(total_amount), 0) as avg_order
            FROM orders
            WHERE DATE(date_time) = CURDATE()
            AND cashier_id IN (SELECT id FROM users WHERE role = 'cashier'" . $subquery_branch_filter . ")
        ");
        $stmt->execute($branch_params);
        $avg_order = $stmt->fetch();
    } catch (PDOException $e) {
        $cashier_revenue = ['total' => 0];
        $avg_order = ['avg_order' => 0];
    }

    return compact('cashier_performance', 'cashier_revenue', 'avg_order');
}

// Live-polling endpoint: same branch-scoped data the page renders, as JSON.
if (isset($_GET['ajax_stats'])) {
    header('Content-Type: application/json');
    $stats = getCashierStats($pdo, isOwner(), getCurrentBranchId());
    echo json_encode([
        'success' => true,
        'active_cashiers' => count(array_filter($stats['cashier_performance'], fn($c) => $c['status'] === 'active')),
        'revenue_total' => number_format((float)$stats['cashier_revenue']['total'], 2),
        'avg_order' => number_format((float)$stats['avg_order']['avg_order'], 2),
        'cashiers' => array_map(fn($c) => [
            'name' => $c['full_name'] ?? $c['user_id'],
            'initial' => strtoupper(substr($c['full_name'] ?? $c['user_id'], 0, 1)),
            'user_id' => $c['user_id'],
            'status' => $c['status'],
            'branch' => $c['branch_name'] ?? 'N/A',
            'today_orders' => (int)$c['today_orders'],
            'revenue' => number_format((float)$c['today_revenue'], 2),
            'avg' => number_format((float)$c['avg_order'], 2),
            'last_activity' => $c['last_activity'] ? date('M j, g:i A', strtotime($c['last_activity'])) : 'No activity',
        ], $stats['cashier_performance']),
    ]);
    exit;
}

extract(getCashierStats($pdo, $is_owner, $user_branch_id));

$active_cashiers = array_filter($cashier_performance, function($c) {
    return $c['status'] === 'active';
});

function formatMoney($amount) {
    return '₱' . number_format($amount, 2);
}

// Fetch branches for filter (Owner only)
$branches = [];
if ($is_owner) {
    $branches = $pdo->query("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashiers - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); }
        .stat-header { display: flex; align-items: center; gap: 0.75rem; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-info { flex: 1; }
        .stat-title { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
        .stat-value { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
        .icon-sales { background: var(--green-light); color: var(--green); }
        .branch-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; background: var(--primary-light); color: var(--primary); }
        .role-badge.cashier { background: #d1fae5; color: #047857; display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.72rem; font-weight: 600; }
        .status-badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .status-active { background: var(--green-light); color: var(--green); }
        .status-inactive { background: var(--red-light); color: var(--red); }
        .revenue-positive { color: var(--green); font-weight: 700; }
        .table-cashier { display: flex; align-items: center; gap: 8px; }
        .cashier-avatar { width: 32px; height: 32px; border-radius: 8px; background: var(--blue-light); color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
        .text-muted { color: var(--text-muted); }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="content-area">
                <div id="live-indicator" style="display:none;font-size:0.72rem;color:#059669;margin-bottom:6px;">● Live</div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: var(--blue-light);color:var(--blue);"><i class='bx bx-user'></i></div>
                            <div class="stat-info">
                                <div class="stat-title">Active Cashiers</div>
                                <div class="stat-value" id="stat-active-cashiers"><?php echo count($active_cashiers); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-sales"><span style="font-size: 1.5rem;">₱</span></div>
                            <div class="stat-info">
                                <div class="stat-title">Today's Cashier Revenue</div>
                                <div class="stat-value" id="stat-cashier-revenue"><?php echo formatMoney($cashier_revenue['total']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: var(--purple-light);color:var(--purple);"><i class='bx bx-trending-up'></i></div>
                            <div class="stat-info">
                                <div class="stat-title">Avg. Order Value</div>
                                <div class="stat-value" id="stat-avg-order"><?php echo formatMoney($avg_order['avg_order']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-user-check'></i> Cashier Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Cashier</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Today Orders</th>
                                        <th>Today Revenue</th>
                                        <th>Avg Order</th>
                                        <th>Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody id="cashier-performance-body">
                                    <?php if (empty($cashier_performance)): ?>
                                        <tr><td colspan="7" class="text-center text-muted">No cashiers found</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($cashier_performance as $c): ?>
                                            <tr>
                                                <td>
                                                    <div class="table-cashier">
                                                        <span class="cashier-avatar"><?php echo strtoupper(substr($c['full_name'] ?? $c['user_id'], 0, 1)); ?></span>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($c['full_name'] ?? $c['user_id']); ?></strong>
                                                            <div style="font-size:0.72rem;color:var(--text-muted);"><?php echo htmlspecialchars($c['user_id']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="branch-badge"><?php echo htmlspecialchars($c['branch_name'] ?? 'N/A'); ?></span></td>
                                                <td>
                                                    <span class="status-badge <?php echo $c['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $c['status'] === 'active' ? '🟢 Active' : '🔴 Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo (int)$c['today_orders']; ?></td>
                                                <td class="revenue-positive"><?php echo formatMoney($c['today_revenue']); ?></td>
                                                <td><?php echo formatMoney($c['avg_order']); ?></td>
                                                <td class="text-muted"><?php echo $c['last_activity'] ? date('M j, g:i A', strtotime($c['last_activity'])) : 'No activity'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/ai_chatbot_widget.php'; ?>
    <script>
    (function() {
        var POLL_INTERVAL_MS = 6000;
        var pollInFlight = false;

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function renderCashierRows(rows) {
            var body = document.getElementById('cashier-performance-body');
            if (!body) return;
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No cashiers found</td></tr>';
                return;
            }
            body.innerHTML = rows.map(function(c) {
                var active = c.status === 'active';
                return '<tr>' +
                    '<td><div class="table-cashier">' +
                    '<span class="cashier-avatar">' + escapeHtml(c.initial) + '</span>' +
                    '<div><strong>' + escapeHtml(c.name) + '</strong>' +
                    '<div style="font-size:0.72rem;color:var(--text-muted);">' + escapeHtml(c.user_id) + '</div></div>' +
                    '</div></td>' +
                    '<td><span class="branch-badge">' + escapeHtml(c.branch) + '</span></td>' +
                    '<td><span class="status-badge ' + (active ? 'status-active' : 'status-inactive') + '">' +
                    (active ? '🟢 Active' : '🔴 Inactive') +
                    '</span></td>' +
                    '<td>' + c.today_orders + '</td>' +
                    '<td class="revenue-positive">' + escapeHtml(c.revenue) + '</td>' +
                    '<td>' + escapeHtml(c.avg) + '</td>' +
                    '<td class="text-muted">' + escapeHtml(c.last_activity) + '</td>' +
                    '</tr>';
            }).join('');
        }

        function refreshCashierStats() {
            if (document.hidden || pollInFlight) return;
            pollInFlight = true;
            fetch('?ajax_stats=1&_=' + Date.now(), { credentials: 'same-origin' })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data || !data.success) return;
                    var liveEl = document.getElementById('live-indicator');
                    if (liveEl) {
                        liveEl.style.display = '';
                        liveEl.textContent = '● Live — updated ' + new Date().toLocaleTimeString();
                    }
                    setText('stat-active-cashiers', data.active_cashiers);
                    setText('stat-cashier-revenue', data.revenue_total);
                    setText('stat-avg-order', data.avg_order);
                    renderCashierRows(data.cashiers);
                }, function() {})
                .then(function() { pollInFlight = false; });
        }

        setInterval(refreshCashierStats, POLL_INTERVAL_MS);
    })();
    </script>
</body>
</html>
