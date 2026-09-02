<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_functions.php';
requirePermission('inventory_count');

$page_title = 'Physical Inventory Count';
$active_page = 'inventory_count';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perform_count'])) {
    requireCsrfToken();

    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $actual_quantity = (float)($_POST['actual_quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($inventory_id <= 0 || $actual_quantity < 0) {
        flashMessage('Invalid input. Please select an item and enter a valid count.');
        redirectTo('inventory_count.php');
    }

    $result = performStockCount($pdo, $inventory_id, $actual_quantity, $notes);
    if ($result['success']) {
        flashMessage($result['message']);
    } else {
        flashMessage('Error: ' . $result['message']);
    }
    redirectTo('inventory_count.php');
}

$items = getInventoryItems($pdo);

// Get recent count history
$branchCond = getInventoryBranchConditionAlias('ic');
$sql = "SELECT ic.*, i.item_name, i.unit, u.full_name AS user_name
        FROM inventory_counts ic
        JOIN inventory i ON i.id = ic.inventory_id
        LEFT JOIN users u ON u.id = ic.counted_by
        WHERE 1=1 $branchCond
        ORDER BY ic.counted_at DESC LIMIT 30";
$stmt = $pdo->query($sql);
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Minute Burger</title>
    <link rel="icon" type="image/png" href="/minute1/img/logo%20(1)/mblogo%20(1).png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .diff-positive { color: var(--green); font-weight: 600; }
        .diff-negative { color: var(--red); font-weight: 600; }
        .diff-zero { color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php foreach (getFlashMessages() as $msg): ?>
                    <div class="message success"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>

                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-pencil'></i> Perform Count</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted" style="margin-bottom:1rem;">Enter the actual physical count for an inventory item. The system will compare it to the recorded quantity and auto-adjust if there's a difference.</p>

                            <form method="POST">
                                <?php $token = getCsrfToken(); ?>
                                <input type="hidden" name="csrf_token" value="<?= $token ?>">

                                <div class="form-group">
                                    <label class="form-label">Item</label>
                                    <select name="inventory_id" class="form-control" required id="itemSelect" onchange="showSystemQty()">
                                        <option value="">-- Select Item --</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= $item['id'] ?>" data-qty="<?= (float)$item['quantity'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>">
                                                <?= htmlspecialchars($item['item_name']) ?> (System: <?= (float)$item['quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">System Quantity</label>
                                    <div id="systemQtyDisplay" style="font-size:1.5rem; font-weight:700; color:var(--text-primary);">--</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Actual Count</label>
                                    <input type="number" name="actual_quantity" class="form-control" step="0.01" min="0" required placeholder="Enter physical count" id="actualQty" oninput="showDifference()">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Difference</label>
                                    <div id="differenceDisplay" style="font-size:1.25rem; font-weight:600; color:var(--text-muted);">--</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this count..."></textarea>
                                </div>

                                <button type="submit" name="perform_count" class="btn btn-primary" style="width:100%;">
                                    <i class='bx bx-check'></i> Save Count
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class='bx bx-history'></i> Recent Counts</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php if (empty($counts)): ?>
                                <p class="text-muted" style="padding:2rem;text-align:center;">No physical counts recorded yet.</p>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height:480px; overflow-y:auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Item</th>
                                                <th>System</th>
                                                <th>Actual</th>
                                                <th>Diff</th>
                                                <th>By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($counts as $c):
                                                $diff = (float)$c['difference'];
                                                $diffClass = $diff > 0 ? 'diff-positive' : ($diff < 0 ? 'diff-negative' : 'diff-zero');
                                                $diffSign = $diff > 0 ? '+' : '';
                                            ?>
                                                <tr>
                                                    <td><?= date('M d, h:i A', strtotime($c['counted_at'])) ?></td>
                                                    <td><?= htmlspecialchars($c['item_name']) ?></td>
                                                    <td><?= (float)$c['system_quantity'] ?></td>
                                                    <td><?= (float)$c['actual_quantity'] ?></td>
                                                    <td class="<?= $diffClass ?>"><?= $diffSign ?><?= $diff ?></td>
                                                    <td><?= htmlspecialchars($c['user_name'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showSystemQty() {
            const select = document.getElementById('itemSelect');
            const opt = select.options[select.selectedIndex];
            const display = document.getElementById('systemQtyDisplay');
            if (opt && opt.value) {
                display.textContent = opt.dataset.qty;
            } else {
                display.textContent = '--';
            }
            showDifference();
        }

        function showDifference() {
            const select = document.getElementById('itemSelect');
            const opt = select.options[select.selectedIndex];
            const actual = parseFloat(document.getElementById('actualQty').value || 0);
            const display = document.getElementById('differenceDisplay');

            if (opt && opt.value && !isNaN(actual)) {
                const system = parseFloat(opt.dataset.qty);
                const diff = actual - system;
                const cls = diff > 0 ? '#22c55e' : (diff < 0 ? '#ef4444' : '#6b7280');
                const sign = diff > 0 ? '+' : '';
                display.innerHTML = `<span style="color:${cls}">${sign}${diff.toFixed(2)}</span>`;
            } else {
                display.textContent = '--';
            }
        }
    </script>
</body>
</html>
