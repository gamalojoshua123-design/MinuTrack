<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
requirePermission('transactions_view');

$page_title = 'Transactions';
$active_page = 'transactions';

// Initialize date range variables
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 20;
$txn_current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Branch filtering
$branch_id = getCurrentBranchId();
$bid = (int)$branch_id;
$branch_condition = '';
if ($branch_id !== null) {
    $branch_condition = " AND (o.branch_id = $bid OR (o.branch_id IS NULL AND o.cashier_id IN (SELECT id FROM users WHERE branch_id = $bid)))";
}

// Build WHERE conditions
$where_sql = " WHERE 1=1" . $branch_condition;
$params = [];

if (!empty($from_date) && !empty($to_date)) {
    $from_datetime = $from_date . ' 00:00:00';
    $to_datetime = $to_date . ' 23:59:59';
    $where_sql .= " AND o.date_time BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_datetime;
    $params[':to_date'] = $to_datetime;
}

if (!empty($search)) {
    $where_sql .= " AND o.order_number LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Aggregation query for summary totals (all matching records)
$agg_sql = "SELECT COUNT(*) AS total_count, COALESCE(SUM(o.total_amount), 0) AS total_sales FROM orders o LEFT JOIN users u ON o.cashier_id = u.id" . $where_sql;
$agg_stmt = $pdo->prepare($agg_sql);
$agg_stmt->execute($params);
$agg_row = $agg_stmt->fetch(PDO::FETCH_ASSOC);

$total_transactions = (int)$agg_row['total_count'];
$total_sales = (float)$agg_row['total_sales'];
$total_pages = (int) max(1, ceil($total_transactions / $per_page));

// Clamp current page
if ($txn_current_page > $total_pages) {
    $txn_current_page = $total_pages;
}
$offset = ($txn_current_page - 1) * $per_page;

// Fetch paginated transactions
$sql = "
    SELECT
        o.*,
        COALESCE(NULLIF(u.full_name, ''), u.user_id, 'Unknown User') AS cashier_name
    FROM orders o
    LEFT JOIN users u ON o.cashier_id = u.id
" . $where_sql . " ORDER BY o.date_time DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build pagination query params
$pagination_params = array_filter([
    'from_date' => $from_date,
    'to_date' => $to_date,
    'search' => $search,
    'per_page' => $per_page != 20 ? $per_page : null,
]);

// Live-polling endpoint: same page-1 data the page renders, as JSON. Only
// ever returns page 1 (new sales land at the top); the client only applies
// it while the visitor is actually looking at page 1.
if (isset($_GET['ajax_transactions'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total_transactions' => $total_transactions,
        'total_transactions_fmt' => number_format($total_transactions),
        'total_sales_fmt' => number_format($total_sales, 2),
        'avg_transaction_fmt' => number_format($total_transactions > 0 ? $total_sales / $total_transactions : 0, 2),
        'transactions' => array_map(function ($t) {
            return [
                'id' => (int)$t['id'],
                'order_number' => htmlspecialchars($t['order_number']),
                'date_time' => date('M j, Y g:i A', strtotime($t['date_time'])),
                'cashier_name' => htmlspecialchars($t['cashier_name']),
                'total_amount' => number_format($t['total_amount'], 2),
                'payment' => number_format($t['payment'], 2),
                'change' => number_format($t['change'], 2),
            ];
        }, $transactions),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* Layout (flex, wrap, stacking) comes from .l-cluster
           .l-cluster--stack-sm in assets/css/layout.css — this rule only
           carries the visual skin. */
        .filter-bar {
            background: var(--white);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.25rem;
        }

        .date-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-group label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .date-group input[type="date"] {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            position: relative;
        }

        .date-group input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
        }

        .date-group input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 1;
            position: absolute;
            right: 0.4rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .search-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .search-group input[type="text"] {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            width: 180px;
        }

        .search-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
        }

        .per-page-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .per-page-group label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .per-page-group select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            cursor: pointer;
        }

        .per-page-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-btn {
            padding: 0.5rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }

        .filter-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Grid + responsive column counts come from .l-grid.l-grid--fixed3
           (3 cols -> 2 on tablet -> 1 on phone). Only the spacing below the
           block is page-specific. */
        .stats-grid {
            --l-gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-icon.orange { background: var(--primary-light); color: var(--primary); }
        .stat-icon.green { background: var(--green-light); color: var(--green); }
        .stat-icon.blue { background: var(--info-light); color: var(--info); }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-top: 2px;
        }

        .stat-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .table-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-card .data-table {
            font-size: 0.85rem;
        }

        .table-card .data-table th {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: none;
        }

        /* The sticky Actions column header (th:last-child) is reset to grey by
           the global sticky-column rule in admin.css — restore the themed color */
        .table-card .table-container .data-table th:last-child {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
        }

        .table-card .data-table td {
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
        }

        .amount-col {
            font-weight: 700;
            color: var(--green);
        }

        .order-id-col {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.85rem;
        }

        .date-col {
            color: var(--text-muted);
            font-size: 0.82rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            background: var(--bg);
            color: var(--text-muted);
        }

        .empty-state p {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .empty-state a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        /* The stats-grid column counts (3 -> 2 -> 1) and the filter bar's
           stacking are now handled by .l-grid--fixed3 and
           .l-cluster--stack-sm in assets/css/layout.css.
           What remains here is only what those primitives can't know:
           making this page's specific controls fill the stacked width. */
        @media (max-width: 768px) {
            .date-group input[type="date"] { flex: 1; }
            .search-group input[type="text"] { flex: 1; width: auto; }
            .per-page-group select { flex: 1; }
            .filter-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-area">
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <form method="GET" action="" class="l-cluster l-cluster--stack-sm">
                        <div class="date-group">
                            <label for="from_date">From</label>
                            <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="date-group">
                            <label for="to_date">To</label>
                            <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="search-group">
                            <input type="text" name="search" placeholder="Search order #..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="per-page-group">
                            <label for="per_page">Show</label>
                            <select id="per_page" name="per_page">
                                <?php foreach ([10, 20, 50, 100] as $pp): ?>
                                    <option value="<?php echo $pp; ?>" <?php echo $per_page == $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="filter-btn"><i class='bx bx-filter-alt'></i> Apply</button>
                    </form>
                </div>

                <?php if ($total_transactions === 0): ?>
                    <div class="table-card">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class='bx bx-receipt'></i></div>
                            <p>No transactions found<?php echo !empty($search) ? ' matching "' . htmlspecialchars($search) . '"' : ' for this date range'; ?></p>
                            <?php if ($from_date != $to_date || !empty($search)): ?>
                                <p style="margin-top:0.75rem;font-size:0.85rem;">Try a different range or <a href="transactions.php">view today's transactions</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Stats Grid -->
                    <div class="stats-grid l-grid l-grid--fixed3">
                        <div class="stat-card">
                            <div class="stat-icon orange"><i class='bx bx-receipt'></i></div>
                            <div>
                                <div class="stat-label">Total Transactions</div>
                                <div class="stat-value" id="txn-total-count"><?php echo number_format($total_transactions); ?></div>
                                <div class="stat-sub">For selected period</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class='bx bx-dollar'></i></div>
                            <div>
                                <div class="stat-label">Total Sales</div>
                                <div class="stat-value">₱<span id="txn-total-sales"><?php echo number_format($total_sales, 2); ?></span></div>
                                <div class="stat-sub"><?php echo date('M j', strtotime($from_date)); ?> - <?php echo date('M j, Y', strtotime($to_date)); ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class='bx bx-calculator'></i></div>
                            <div>
                                <div class="stat-label">Average Transaction</div>
                                <div class="stat-value">₱<span id="txn-avg-amount"><?php echo number_format($total_transactions > 0 ? $total_sales / $total_transactions : 0, 2); ?></span></div>
                                <div class="stat-sub">Per transaction</div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="table-card">
                        <div class="table-container l-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date & Time</th>
                                        <th>Cashier</th>
                                        <th class="text-right">Total Amount</th>
                                        <th class="text-right">Payment</th>
                                        <th class="text-right">Change</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-body">
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><span class="order-id-col"><?php echo htmlspecialchars($transaction['order_number']); ?></span></td>
                                            <td><span class="date-col"><?php echo date('M j, Y g:i A', strtotime($transaction['date_time'])); ?></span></td>
                                            <td><?php echo htmlspecialchars($transaction['cashier_name']); ?></td>
                                            <td class="text-right u-nums"><span class="amount-col">₱<?php echo number_format($transaction['total_amount'], 2); ?></span></td>
                                            <td class="text-right u-nums">₱<?php echo number_format($transaction['payment'], 2); ?></td>
                                            <td class="text-right u-nums">₱<?php echo number_format($transaction['change'], 2); ?></td>
                                            <td class="text-center">
                                                <div class="action-dropdown">
                                                    <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                                                    <div class="action-dropdown-menu">
                                                        <a href="receipt.php?id=<?php echo $transaction['id']; ?>" target="_blank" class="action-receipt"><i class='bx bx-receipt'></i> View Receipt</a>
                                                        <a href="receipt.php?id=<?php echo $transaction['id']; ?>&print=1" target="_blank" class="action-view"><i class='bx bx-printer'></i> Print Receipt</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-bar">
                                <div class="pagination-info">
                                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_transactions); ?> of <?php echo number_format($total_transactions); ?> transactions
                                </div>
                                <div class="pagination-controls">
                                    <?php if ($txn_current_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $txn_current_page - 1])); ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $txn_current_page - 2);
                                    $end_page = min($total_pages, $txn_current_page + 2);
                                    if ($start_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" class="page-btn">1</a>
                                        <?php if ($start_page > 2): ?><span class="page-dots">...</span><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $p])); ?>" class="page-btn <?php echo $p === $txn_current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                                    <?php endif; ?>

                                    <?php if ($txn_current_page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $txn_current_page + 1])); ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="pagination-bar">
                                <div class="pagination-info">
                                    <?php echo number_format($total_transactions); ?> transaction<?php echo $total_transactions !== 1 ? 's' : ''; ?> total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var fromDate = document.getElementById('from_date');
            var toDate = document.getElementById('to_date');

            function validateDates() {
                if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
                    toDate.value = fromDate.value;
                }
            }

            fromDate.addEventListener('change', function() {
                validateDates();
                toDate.min = fromDate.value;
            });

            toDate.addEventListener('change', validateDates);

            if (fromDate.value) {
                toDate.min = fromDate.value;
            }
        });

        // Live updates: only while viewing page 1 with the default filters,
        // since that's the only view new sales can land on without changing
        // what the visitor is currently paging/filtering through.
        (function() {
            var CURRENT_PAGE = <?php echo (int)$txn_current_page; ?>;
            if (CURRENT_PAGE !== 1) return;

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function renderRows(rows) {
                var body = document.getElementById('transactions-body');
                if (!body) return;
                body.innerHTML = rows.map(function(t) {
                    return '<tr>' +
                        '<td><span class="order-id-col">' + esc(t.order_number) + '</span></td>' +
                        '<td><span class="date-col">' + esc(t.date_time) + '</span></td>' +
                        '<td>' + esc(t.cashier_name) + '</td>' +
                        '<td class="text-right u-nums"><span class="amount-col">₱' + t.total_amount + '</span></td>' +
                        '<td class="text-right u-nums">₱' + t.payment + '</td>' +
                        '<td class="text-right u-nums">₱' + t.change + '</td>' +
                        '<td class="text-center">' +
                            '<div class="action-dropdown">' +
                                '<button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)"><i class="bx bx-dots-vertical-rounded"></i></button>' +
                                '<div class="action-dropdown-menu">' +
                                    '<a href="receipt.php?id=' + t.id + '" target="_blank" class="action-receipt"><i class="bx bx-receipt"></i> View Receipt</a>' +
                                    '<a href="receipt.php?id=' + t.id + '&print=1" target="_blank" class="action-view"><i class="bx bx-printer"></i> Print Receipt</a>' +
                                '</div>' +
                            '</div>' +
                        '</td>' +
                        '</tr>';
                }).join('');
            }

            function setText(id, value) {
                var el = document.getElementById(id);
                if (el) el.textContent = value;
            }

            function refreshTransactions() {
                var url = new URL(window.location.href);
                url.searchParams.set('ajax_transactions', '1');
                url.searchParams.set('page', '1');
                fetch(url.toString(), { credentials: 'same-origin' })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (!data || !data.success) return;
                        setText('txn-total-count', data.total_transactions_fmt);
                        setText('txn-total-sales', data.total_sales_fmt);
                        setText('txn-avg-amount', data.avg_transaction_fmt);
                        if (document.getElementById('transactions-body')) {
                            renderRows(data.transactions);
                        }
                    })
                    .catch(function() { /* silent: try again next tick */ });
            }

            setInterval(refreshTransactions, 6000);
        })();
    </script>
</body>
</html>
