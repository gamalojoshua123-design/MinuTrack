<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requirePermission('transactions_view');

$page_title = 'Transactions';
$active_page = 'transactions';

// Initialize date range variables
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 20;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

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
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}
$offset = ($current_page - 1) * $per_page;

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <style>
        .page-header-bar {
            margin-bottom: 1rem;
        }

        .page-header-bar h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-gray);
        }

        .page-header-bar p {
            font-size: 0.78rem;
            color: #999;
            margin-top: 2px;
        }

        .filter-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 0.85rem 1.25rem;
            margin-bottom: 1rem;
        }

        /* Flex/wrap/stacking comes from .l-cluster.l-cluster--stack-sm;
           only the gap size is page-specific. */
        .filter-form {
            --l-gap: 0.6rem;
        }

        .date-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-group label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #999;
            white-space: nowrap;
        }

        .date-group input[type="date"] {
            padding: 0.5rem 2.25rem 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--dark-gray);
            background: var(--bg);
            position: relative;
            min-width: 150px;
        }

        .date-group input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
        }

        .date-group input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 1;
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .search-group input[type="text"] {
            padding: 0.45rem 0.6rem;
            border: 1.5px solid var(--apricot-cream);
            border-radius: 7px;
            font-size: 0.8rem;
            font-family: inherit;
            color: var(--dark-gray);
            width: 140px;
        }

        .search-group input[type="text"]:focus {
            outline: none;
            border-color: var(--harvest-orange);
        }

        .per-page-group {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .per-page-group label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #999;
        }

        .per-page-group select {
            padding: 0.45rem 0.5rem;
            border: 1.5px solid var(--apricot-cream);
            border-radius: 7px;
            font-size: 0.8rem;
            font-family: inherit;
            color: var(--dark-gray);
            background: var(--white);
            cursor: pointer;
        }

        .per-page-group select:focus {
            outline: none;
            border-color: var(--harvest-orange);
        }

        .filter-btn {
            padding: 0.45rem 1rem;
            border: none;
            border-radius: 7px;
            font-weight: 600;
            font-size: 0.78rem;
            font-family: inherit;
            cursor: pointer;
        }

        .filter-btn.primary {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
        }

        .filter-btn.primary:hover {
            opacity: 0.9;
        }

        /* Grid + responsive column counts come from .l-grid.l-grid--fixed3.
           The 1px gap is load-bearing here: combined with the tinted
           background and overflow:hidden it draws the hairline dividers
           between cards, so it's passed through --l-gap rather than using
           the primitive's default spacing. */
        .summary-grid {
            --l-gap: 1px;
            background: var(--apricot-cream);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .summary-card {
            background: var(--white);
            padding: 1rem;
            text-align: center;
        }

        .summary-label {
            color: #999;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark-gray);
        }

        .summary-sub {
            font-size: 0.65rem;
            color: #bbb;
            margin-top: 4px;
        }

        .table-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-card .data-table {
            font-size: 0.83rem;
        }

        .table-card .data-table th {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            color: white;
            padding: 0.65rem 1rem;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: none;
        }

        .table-card .data-table td {
            padding: 0.65rem 1rem;
            font-size: 0.83rem;
        }

        .table-card .table-container {
            border: none;
        }

        .amount-col {
            font-weight: 700;
            color: var(--success);
        }

        .order-id-col {
            font-weight: 600;
            color: var(--harvest-orange);
            font-size: 0.8rem;
        }

        .date-col {
            color: #999;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1.5rem;
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
            background: var(--light-gray);
            color: #bbb;
        }

        .empty-state p {
            font-size: 0.85rem;
            color: #999;
            font-weight: 500;
        }

        .empty-state a {
            color: var(--harvest-orange);
            text-decoration: none;
            font-weight: 600;
        }

        /* Column counts (3 -> 2 -> 1) and the filter bar's stacking now come
           from .l-grid--fixed3 and .l-cluster--stack-sm in layout.css.
           Only this page's own control sizing remains. */
        @media (max-width: 768px) {
            .date-group input[type="date"] { flex: 1; }
            .search-group input[type="text"] { flex: 1; width: auto; }
            .per-page-group select { flex: 1; }
            .filter-btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>

    <div style="max-width:1400px;margin:1.5rem auto;padding:0 1.5rem;">
        <div class="page-header-bar" style="margin-bottom:1.25rem;">
            <div>
                <h2>Transaction History</h2>
                <p><?php echo date('M j, Y', strtotime($from_date)); ?><?php echo ($from_date !== $to_date) ? ' - ' . date('M j, Y', strtotime($to_date)) : ''; ?></p>
            </div>
        </div>

                <!-- Filter -->
                <div class="filter-card">
                    <form method="GET" action="" class="filter-form l-cluster l-cluster--stack-sm">
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
                        <button type="submit" class="filter-btn primary"><i class='bx bx-filter-alt'></i> Apply</button>
                    </form>
                </div>

                <?php if ($total_transactions === 0): ?>
                    <div class="table-card">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class='bx bx-receipt'></i></div>
                            <p>No transactions found<?php echo !empty($search) ? ' matching "' . htmlspecialchars($search) . '"' : ' for this date range'; ?></p>
                            <?php if ($from_date != $to_date || !empty($search)): ?>
                                <p style="margin-top:0.5rem;font-size:0.82rem;">Try a different range or <a href="transactions.php">view today's transactions</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Summary Cards -->
                    <div class="summary-grid l-grid l-grid--fixed3">
                        <div class="summary-card">
                            <div class="summary-label">Total Transactions</div>
                            <div class="summary-value" id="txn-total-count"><?php echo number_format($total_transactions); ?></div>
                            <div class="summary-sub">For selected period</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Total Sales</div>
                            <div class="summary-value" id="txn-total-sales"><?php echo number_format($total_sales, 2); ?></div>
                            <div class="summary-sub"><?php echo date('M j', strtotime($from_date)); ?> - <?php echo date('M j, Y', strtotime($to_date)); ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Average Transaction</div>
                            <div class="summary-value" id="txn-avg-amount"><?php echo number_format($total_transactions > 0 ? $total_sales / $total_transactions : 0, 2); ?></div>
                            <div class="summary-sub">Per transaction</div>
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
                                        <th>Total Amount</th>
                                        <th>Payment</th>
                                        <th>Change</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-body">
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><span class="order-id-col"><?php echo htmlspecialchars($transaction['order_number']); ?></span></td>
                                            <td><span class="date-col"><?php echo date('M j, Y g:i A', strtotime($transaction['date_time'])); ?></span></td>
                                            <td><?php echo htmlspecialchars($transaction['cashier_name']); ?></td>
                                            <td class="u-nums"><span class="amount-col"><?php echo number_format($transaction['total_amount'], 2); ?></span></td>
                                            <td class="u-nums"><?php echo number_format($transaction['payment'], 2); ?></td>
                                            <td class="u-nums"><?php echo number_format($transaction['change'], 2); ?></td>
                                            <td>
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
                                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_transactions); ?> of <?php echo $total_transactions; ?> transactions
                                </div>
                                <div class="pagination-controls">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $current_page - 1])); ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    if ($start_page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" class="page-btn">1</a>
                                        <?php if ($start_page > 2): ?><span class="page-dots">...</span><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $p])); ?>" class="page-btn <?php echo $p === $current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                                    <?php endif; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $current_page + 1])); ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                                    <?php else: ?>
                                        <span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="pagination-bar">
                                <div class="pagination-info">
                                    <?php echo $total_transactions; ?> transaction<?php echo $total_transactions !== 1 ? 's' : ''; ?> total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
    </div>

    <script>
        function toggleDropdown(btn) {
            var wrap = btn.closest('.action-dropdown');
            if (!wrap) return;
            var menu = btn._dropdownMenu;
            if (!menu) {
                menu = btn._dropdownMenu = wrap.querySelector('.action-dropdown-menu');
            }
            if (!menu) return;
            var wasOpen = menu.classList.contains('show');
            closeAllDropdowns();
            if (!wasOpen) {
                menu._returnParent = wrap;
                var rect = btn.getBoundingClientRect();
                document.body.appendChild(menu);
                menu.style.position = 'fixed';
                menu.style.left = rect.left + 'px';
                menu.style.top = (rect.bottom + 4) + 'px';
                menu.style.right = 'auto';
                menu.style.margin = '0';
                menu.classList.add('show');
            }
        }
        function closeAllDropdowns() {
            document.querySelectorAll('.action-dropdown-menu.show').forEach(function(m) {
                m.classList.remove('show');
                m.style.left = '';
                m.style.top = '';
                m.style.right = '';
                m.style.position = '';
                m.style.margin = '';
                var home = m._returnParent;
                if (home) {
                    home.appendChild(m);
                    delete m._returnParent;
                }
            });
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.action-dropdown, .action-dropdown-menu')) closeAllDropdowns();
        });
        document.addEventListener('touchstart', function(e) {
            if (!e.target.closest('.action-dropdown, .action-dropdown-menu')) closeAllDropdowns();
        }, { passive: true });

        document.addEventListener('DOMContentLoaded', function() {
            const fromDate = document.getElementById('from_date');
            const toDate = document.getElementById('to_date');

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

        // Live updates: only while viewing page 1 with the current filters,
        // since that's the only view new sales can land on without changing
        // what the visitor is currently paging/filtering through.
        (function() {
            var CURRENT_PAGE = <?php echo (int)$current_page; ?>;
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
                        '<td class="u-nums"><span class="amount-col">' + t.total_amount + '</span></td>' +
                        '<td class="u-nums">' + t.payment + '</td>' +
                        '<td class="u-nums">' + t.change + '</td>' +
                        '<td>' +
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
