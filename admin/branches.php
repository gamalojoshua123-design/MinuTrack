<?php
$active_tab = 'branches';
require_once __DIR__ . '/bootstrap.php';
requirePermission('branches_manage');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    if (!checkRateLimit('admin_branches')) {
        $error = 'Too many requests. Please wait a minute.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $branch_name = trim($_POST['branch_name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $branch_id = (int)($_POST['branch_id'] ?? 0);

            if (empty($branch_name)) {
                $error = 'Branch name is required.';
            } else {
                try {
                    if ($action === 'create') {
                        $stmt = $pdo->prepare("INSERT INTO branches (branch_name, location, status) VALUES (?, ?, ?)");
                        $stmt->execute([$branch_name, $location, $status]);
                        $success = 'Branch created successfully.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE branches SET branch_name = ?, location = ?, status = ? WHERE id = ?");
                        $stmt->execute([$branch_name, $location, $status, $branch_id]);
                        $success = 'Branch updated successfully.';
                    }
                } catch (PDOException $e) {
                    $error = 'An error occurred. Please try again.';
                }
            }
        }

        if ($action === 'deactivate') {
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $blockers = [];
            try {
                $active_shifts = $pdo->prepare("SELECT COUNT(*) FROM cashier_shifts cs JOIN users u ON cs.cashier_id = u.id WHERE u.branch_id = ? AND cs.status = 'active'");
                $active_shifts->execute([$branch_id]);
                if ((int)$active_shifts->fetchColumn() > 0) { $blockers[] = 'active cashier shift'; }
            } catch (PDOException $e) {}
            try {
                $pending_inv = $pdo->prepare("SELECT COUNT(*) FROM stock_receiving WHERE branch_id = ? AND DATE(received_date) = CURDATE()");
                $pending_inv->execute([$branch_id]);
                if ((int)$pending_inv->fetchColumn() > 0) { $blockers[] = 'stock received today'; }
            } catch (PDOException $e) {}
            try {
                // inventory_counts has no status column; all recorded counts are final,
                // so only counts from the last 24 hours are treated as still-in-progress activity
                $pending_count = $pdo->prepare("SELECT COUNT(*) FROM inventory_counts WHERE branch_id = ? AND counted_at >= (NOW() - INTERVAL 1 DAY)");
                $pending_count->execute([$branch_id]);
                if ((int)$pending_count->fetchColumn() > 0) { $blockers[] = 'recent inventory counts'; }
            } catch (PDOException $e) {}
            if (!empty($blockers)) {
                $error = 'Cannot deactivate branch: ' . implode(', ', $blockers) . ' still active. Resolve these first.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE branches SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$branch_id]);
                    $success = 'Branch has been deactivated successfully.';
                } catch (PDOException $e) {
                    $error = 'An error occurred. Please try again.';
                }
            }
        }

        if ($action === 'activate') {
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE branches SET status = 'active' WHERE id = ?");
                $stmt->execute([$branch_id]);
                $success = 'Branch has been activated successfully.';
            } catch (PDOException $e) {
                $error = 'An error occurred. Please try again.';
            }
        }

        if ($action === 'delete') {
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("SELECT status, branch_name FROM branches WHERE id = ?");
                $stmt->execute([$branch_id]);
                $branch = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$branch) {
                    $error = 'Branch not found.';
                } elseif ($branch['status'] !== 'inactive') {
                    $error = 'Only deactivated branches can be permanently deleted.';
                } else {
                    $blockers = [];
                    $u = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ?");
                    $u->execute([$branch_id]);
                    $user_count = (int)$u->fetchColumn();
                    if ($user_count > 0) { $blockers[] = $user_count . ' assigned user account(s) — reassign or remove them first'; }
                    $o = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE branch_id = ?");
                    $o->execute([$branch_id]);
                    $order_count = (int)$o->fetchColumn();
                    if ($order_count > 0) { $blockers[] = $order_count . ' recorded sale(s) — sales history cannot be deleted'; }

                    if (!empty($blockers)) {
                        $error = 'Cannot delete branch: ' . implode('; ', $blockers) . '.';
                    } else {
                        $pdo->beginTransaction();
                        // No FK constraints exist in this schema, so clean up
                        // branch-scoped rows explicitly before removing the branch.
                        foreach ([['branch_users', 'branch_id'], ['stock_receiving', 'branch_id'], ['inventory_counts', 'branch_id'], ['inventory', 'branch_id']] as $target) {
                            try {
                                $d = $pdo->prepare("DELETE FROM {$target[0]} WHERE {$target[1]} = ?");
                                $d->execute([$branch_id]);
                            } catch (PDOException $e) {}
                        }
                        $d = $pdo->prepare("DELETE FROM branches WHERE id = ? AND status = 'inactive'");
                        $d->execute([$branch_id]);
                        $pdo->commit();
                        auditLog('branch_delete', 'branches', 'branch', $branch_id, 'success', 'Permanently deleted inactive branch "' . $branch['branch_name'] . '"');
                        $success = 'Branch "' . htmlspecialchars($branch['branch_name']) . '" has been permanently deleted.';
                    }
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = 'An error occurred while deleting the branch.';
            }
        }
    }
}

// Fetch branches with manager info
    $stmt = $pdo->query("
        SELECT b.*, 
        (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM users u WHERE u.branch_id = b.id AND u.role = 'manager') as managers,
        (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id AND u.role = 'cashier') as cashier_count
        FROM branches b 
        ORDER BY b.status DESC, b.branch_name
    ");
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Batch stats per branch
$today_sales = [];
$monthly_sales = [];
$today_orders = [];
$inventory_counts = [];
$low_stock_counts = [];

try {
    $q = $pdo->query("SELECT branch_id, COALESCE(SUM(total_amount),0) as total FROM orders WHERE DATE(date_time) = CURDATE() AND branch_id IS NOT NULL GROUP BY branch_id");
    while ($r = $q->fetch()) { $today_sales[(int)$r['branch_id']] = (float)$r['total']; }
} catch (PDOException $e) {}
try {
    $q = $pdo->query("SELECT branch_id, COALESCE(SUM(total_amount),0) as total FROM orders WHERE MONTH(date_time)=MONTH(CURDATE()) AND YEAR(date_time)=YEAR(CURDATE()) AND branch_id IS NOT NULL GROUP BY branch_id");
    while ($r = $q->fetch()) { $monthly_sales[(int)$r['branch_id']] = (float)$r['total']; }
} catch (PDOException $e) {}
try {
    $q = $pdo->query("SELECT branch_id, COUNT(*) as cnt FROM orders WHERE DATE(date_time) = CURDATE() AND branch_id IS NOT NULL GROUP BY branch_id");
    while ($r = $q->fetch()) { $today_orders[(int)$r['branch_id']] = (int)$r['cnt']; }
} catch (PDOException $e) {}
try {
    $q = $pdo->query("SELECT branch_id, COUNT(*) as cnt FROM inventory WHERE deleted_at IS NULL GROUP BY branch_id");
    while ($r = $q->fetch()) { $inventory_counts[(int)$r['branch_id']] = (int)$r['cnt']; }
} catch (PDOException $e) {}
try {
    $q = $pdo->query("SELECT branch_id, COUNT(*) as cnt FROM inventory WHERE quantity < min_stock AND deleted_at IS NULL GROUP BY branch_id");
    while ($r = $q->fetch()) { $low_stock_counts[(int)$r['branch_id']] = (int)$r['cnt']; }
} catch (PDOException $e) {}

$cashier_counts = [];
$active_shift_counts = [];
try {
    $q = $pdo->query("SELECT branch_id, COUNT(*) as cnt FROM users WHERE role = 'cashier' AND status = 'active' GROUP BY branch_id");
    while ($r = $q->fetch()) { $cashier_counts[(int)$r['branch_id']] = (int)$r['cnt']; }
} catch (PDOException $e) {}
try {
    $q = $pdo->query("SELECT u.branch_id, COUNT(*) as cnt FROM cashier_shifts cs JOIN users u ON cs.cashier_id = u.id WHERE cs.status = 'active' GROUP BY u.branch_id");
    while ($r = $q->fetch()) { $active_shift_counts[(int)$r['branch_id']] = (int)$r['cnt']; }
} catch (PDOException $e) {}

$product_count = 0;
try { $product_count = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(); } catch (PDOException $e) {}

$csrf_token = getCsrfToken();
$branch_view_active = isset($_SESSION['branch_view_id']);
$current_branch_view_id = $branch_view_active ? (int)$_SESSION['branch_view_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        .branch-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }
        .branch-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
            border-color: rgba(243, 121, 2, 0.2);
        }
        .branch-card.active { border-left: 4px solid var(--green); }
        .branch-card.inactive { border-left: 4px solid var(--red); opacity: 0.8; }
        .branch-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
        .branch-name { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); }
        .branch-location { font-size: 0.82rem; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 4px; }
        .branch-location i { font-size: 0.85rem; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; flex-shrink: 0; }
        .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .badge.active { background: #f0fdf4; color: #15803d; }
        .badge.inactive { background: #fef2f2; color: #dc2626; }
        .branch-stats { display: flex; gap: 1rem; margin-top: 0.75rem; flex-wrap: wrap; }
        .branch-stat { font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
        .branch-stat i { font-size: 0.9rem; color: var(--text-muted); }
        .branch-actions { margin-top: 1rem; display: flex; gap: 6px; flex-wrap: wrap; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 2000; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { display: block; position: relative; background: var(--bg-card); border-radius: 16px; padding: 0; width: 100%; max-width: 480px; box-shadow: 0 25px 60px -12px rgba(0,0,0,0.25); max-height: calc(100vh - 2rem); overflow: hidden; border: 1px solid var(--border); animation: brModalIn 0.2s ease; }
        @keyframes brModalIn { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { background: linear-gradient(135deg, var(--primary), var(--copperwood)); color: white; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; }
        .modal h3 { font-size: 1.05rem; font-weight: 700; margin: 0; }
        .modal .close-x {
            width: 32px; height: 32px; border: none; border-radius: 8px;
            background: rgba(255,255,255,0.2); font-size: 1.2rem; line-height: 1;
            color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .modal .close-x:hover { background: rgba(255,255,255,0.35); }
        .modal-body { padding: 1.25rem; overflow-y: auto; max-height: calc(90vh - 120px); }
        @media (hover: none) and (pointer: coarse) {
            .modal .close-x { width: 44px; height: 44px; }
        }
        .modal-footer { display: flex; gap: 8px; justify-content: flex-end; padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--bg); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-primary); }
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.15s ease; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(243,121,2,0.3); }
        .btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-primary); border-radius: 10px; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: rgba(243,121,2,0.04); }
        .btn-sm { padding: 0.4rem 0.85rem; font-size: 0.78rem; border-radius: 8px; }
        .btn-danger { background: var(--red); color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-1px); }
        .btn-success { background: var(--green); color: white; }
        .btn-success:hover { transform: translateY(-1px); }
        .message { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-weight: 500; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .message.success { background: #f0fdf4; color: #065f46; border: 1px solid #a7f3d0; }
        .message.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .branches-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1rem; margin-top: 1.25rem; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .br-stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(84px, 1fr)); gap: 4px;
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border);
        }
        .br-stat { text-align: center; padding: 6px 2px; background: var(--bg); border-radius: 8px; min-width: 0; }
        .br-stat-label { display: block; font-size: 0.62rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
        .br-stat-value { display: block; font-size: 0.8rem; font-weight: 800; color: var(--text-primary); margin-top: 2px; line-height: 1.25; overflow-wrap: anywhere; white-space: nowrap; overflow: hidden; text-overflow: clip; }
        .br-stat-value.stat-danger { color: var(--red); }
        .br-actions-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 4px;
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border);
        }
        .br-action-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px; padding: 0.5rem 0.25rem; border-radius: 8px;
            text-decoration: none; transition: all 0.15s ease;
            background: var(--bg); color: var(--text-secondary); font-size: 0.65rem; font-weight: 600;
            border: 1px solid transparent;
        }
        .br-action-btn:hover {
            background: rgba(243,121,2,0.06); color: var(--primary); border-color: rgba(243,121,2,0.2);
            transform: translateY(-1px);
        }
        .br-action-btn i { font-size: 1.1rem; }
        .btn-danger-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text-primary); border-radius: 10px; }
        .btn-danger-outline:hover { border-color: var(--red); color: var(--red); background: #fef2f2; }
        .br-overview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .br-ov-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--text-secondary); padding: 0.45rem 0.6rem; background: var(--bg); border-radius: 8px; }
        .br-ov-item i { font-size: 0.95rem; color: var(--primary); width: 18px; text-align: center; flex-shrink: 0; }
        .br-ov-label { color: var(--text-muted); font-weight: 500; margin-right: auto; }
        .br-ov-value { font-weight: 700; color: var(--text-primary); }
        .br-status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .br-status-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; padding: 0.45rem 0.6rem; background: var(--bg); border-radius: 8px; color: var(--text-secondary); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #d1d5db; }
        .status-dot.ok { background: var(--green); box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }
        .status-dot.warn { background: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .status-dot.bad { background: var(--red); box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }
        .br-checklist { display: flex; flex-direction: column; gap: 0.4rem; }
        .br-check-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--text-secondary); }
        .br-check-item i { color: var(--green); font-size: 1rem; }
        @media (max-width: 768px) { .branches-grid { grid-template-columns: 1fr; } .br-stats-grid { grid-template-columns: repeat(2, 1fr); } .br-actions-grid { grid-template-columns: repeat(2, 1fr); } .br-overview-grid { grid-template-columns: 1fr; } .br-status-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="content-area">
                <?php if ($error): ?>
                    <div class="message error"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message success"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="header-actions">
                    <h2 style="font-size:1.3rem;font-weight:700;">Branches</h2>
                    <button class="btn btn-primary" onclick="openModal('create')"><i class='bx bx-plus'></i> Add Branch</button>
                </div>

                <div class="branches-grid">
                    <?php foreach ($branches as $b):
                        $bid = (int)$b['id'];
                        $ts = $today_sales[$bid] ?? 0;
                        $ms = $monthly_sales[$bid] ?? 0;
                        $to = $today_orders[$bid] ?? 0;
                        $ic = $inventory_counts[$bid] ?? 0;
                        $ls = $low_stock_counts[$bid] ?? 0;
                        $cc = $cashier_counts[$bid] ?? 0;
                        $asc = $active_shift_counts[$bid] ?? 0;
                    ?>
                        <div class="branch-card <?php echo $b['status']; ?>">
                            <div class="branch-card-header">
                                <div>
                                    <div class="branch-name"><?php echo htmlspecialchars($b['branch_name']); ?></div>
                                    <div class="branch-location"><i class='bx bx-map'></i> <?php echo htmlspecialchars($b['location'] ?: 'N/A'); ?></div>
                                </div>
                                <span class="badge <?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span>
                            </div>

                            <div class="branch-stats">
                                <span class="branch-stat"><i class='bx bx-user'></i> Manager: <?php echo $b['managers'] ? htmlspecialchars($b['managers']) : 'None'; ?></span>
                                <span class="branch-stat"><i class='bx bx-user-check'></i> <?php echo (int)$b['cashier_count']; ?> cashiers</span>
                            </div>

                            <!-- Stats Grid -->
                            <div class="br-stats-grid">
                                <div class="br-stat">
                                    <span class="br-stat-label">Today Sales</span>
                                    <span class="br-stat-value">₱<?php echo number_format($ts, 2); ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Monthly</span>
                                    <span class="br-stat-value">₱<?php echo number_format($ms, 2); ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Products</span>
                                    <span class="br-stat-value"><?php echo $product_count; ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Inventory</span>
                                    <span class="br-stat-value"><?php echo $ic; ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Cashiers</span>
                                    <span class="br-stat-value"><?php echo $cc; ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Low Stock</span>
                                    <span class="br-stat-value <?php echo $ls > 0 ? 'stat-danger' : ''; ?>"><?php echo $ls; ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Active Shift</span>
                                    <span class="br-stat-value"><?php echo $asc; ?></span>
                                </div>
                                <div class="br-stat">
                                    <span class="br-stat-label">Orders Today</span>
                                    <span class="br-stat-value"><?php echo $to; ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons Grid -->
                            <div class="br-actions-grid">
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=branch_dashboard.php" class="br-action-btn" title="View Dashboard">
                                    <i class='bx bx-bar-chart-alt-2'></i>
                                    <span>Dashboard</span>
                                </a>
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=<?php echo urlencode('../inventory/inventory.php'); ?>" class="br-action-btn" title="Inventory">
                                    <i class='bx bx-package'></i>
                                    <span>Inventory</span>
                                </a>
                                <a href="../admin/products.php" class="br-action-btn" title="Products">
                                    <i class='bx bxs-food-menu'></i>
                                    <span>Products</span>
                                </a>
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=<?php echo urlencode('../users/manager_users.php'); ?>" class="br-action-btn" title="Cashiers">
                                    <i class='bx bx-user'></i>
                                    <span>Cashiers</span>
                                </a>
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=<?php echo urlencode('../admin/users.php'); ?>" class="br-action-btn" title="Manager">
                                    <i class='bx bx-user-pin'></i>
                                    <span>Manager</span>
                                </a>
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=reports.php" class="br-action-btn" title="Reports">
                                    <i class='bx bx-receipt'></i>
                                    <span>Reports</span>
                                </a>
                                <a href="switch_branch.php?branch_id=<?php echo $bid; ?>&redirect=<?php echo urlencode('../pos.php'); ?>" class="br-action-btn" title="POS Preview">
                                    <i class='bx bx-cart'></i>
                                    <span>POS</span>
                                </a>
                                <a href="branch_comparison.php" class="br-action-btn" title="Compare">
                                    <i class='bx bx-git-compare'></i>
                                    <span>Compare</span>
                                </a>
                            </div>

                            <div class="branch-actions">
                                <button class="btn btn-outline btn-sm" onclick="openModal('edit', <?php echo $bid; ?>)"><i class='bx bx-edit'></i> Edit</button>
                                <?php if ($b['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="branch_id" value="<?php echo $bid; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirmDeactivate(<?php echo $bid; ?>, '<?php echo htmlspecialchars($b['branch_name'], ENT_QUOTES); ?>')"><i class='bx bx-x'></i> Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="branch_id" value="<?php echo $bid; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm btn-success"><i class='bx bx-check'></i> Activate</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="branch_id" value="<?php echo $bid; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirmDelete(<?php echo $bid; ?>, '<?php echo htmlspecialchars($b['branch_name'], ENT_QUOTES); ?>')"><i class='bx bx-trash'></i> Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Modal -->
    <div class="modal-overlay" id="branchModal">
        <div class="modal" id="branchModalDialog">
            <button type="button" class="close-x" onclick="closeModal()" aria-label="Close">&times;</button>
            <h3 id="modalTitle">Add Branch</h3>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="branch_id" id="formBranchId" value="0">

                <div class="form-group">
                    <label for="branch_name">Branch Name</label>
                    <input type="text" name="branch_name" id="formBranchName" class="form-control" required placeholder="Enter branch name">
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="formLocation" class="form-control" placeholder="Enter location">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="formStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Branch Code Preview (create mode only) -->
                <div id="branchCodePreview" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;background:var(--bg);border-radius:8px;font-size:0.82rem;">
                    <span style="color:var(--text-muted);font-weight:500;">Branch Code:</span>
                    <span id="codePreviewValue" style="color:var(--primary);font-weight:700;font-family:monospace;">MB-</span>
                </div>

                <!-- After Creating (create mode only) -->
                <div id="afterCreating" style="display:none;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                    <h4 style="font-size:0.85rem;font-weight:700;color:var(--text-primary);margin-bottom:0.75rem;">After creating this branch:</h4>
                    <div class="br-checklist">
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> A new branch will be added to the system.</div>
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> The Owner can assign one Manager to this branch.</div>
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> Inventory will be initialized with zero stock.</div>
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> Products can be stocked using the Stock Receiving module.</div>
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> Cashiers can be added by the Owner or the assigned Manager.</div>
                        <div class="br-check-item"><i class='bx bx-check-circle'></i> Sales and inventory will be completely separate from other branches.</div>
                    </div>
                    <div style="margin-top:0.75rem;padding:0.5rem 0.65rem;background:var(--blue-light);border-radius:6px;font-size:0.75rem;color:var(--blue);line-height:1.5;">
                        <i class='bx bx-info-circle'></i> Each branch has its own inventory, sales reports, POS transactions, cashiers, and manager. Creating a branch does not affect existing branches.
                    </div>
                </div>

                <!-- Branch Overview (edit mode only) -->
                <div id="branchOverview" style="display:none;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                    <h4 style="font-size:0.85rem;font-weight:700;color:var(--text-primary);margin-bottom:0.75rem;">Branch Overview</h4>
                    <div class="br-overview-grid">
                        <div class="br-ov-item"><i class='bx bx-user'></i> <span class="br-ov-label">Manager</span><span class="br-ov-value" id="ovManager">-</span></div>
                        <div class="br-ov-item"><i class='bx bx-user-check'></i> <span class="br-ov-label">Cashiers</span><span class="br-ov-value" id="ovCashiers">-</span></div>
                        <div class="br-ov-item"><i class='bx bxs-food-menu'></i> <span class="br-ov-label">Products</span><span class="br-ov-value" id="ovProducts">-</span></div>
                        <div class="br-ov-item"><i class='bx bx-package'></i> <span class="br-ov-label">Inventory Items</span><span class="br-ov-value" id="ovInventory">-</span></div>
                        <div class="br-ov-item"><i class='bx bx-hash'></i> <span class="br-ov-label">Branch Code</span><span class="br-ov-value" id="ovCode">-</span></div>
                        <div class="br-ov-item"><i class='bx bx-calendar'></i> <span class="br-ov-label">Created</span><span class="br-ov-value" id="ovCreated">-</span></div>
                    </div>
                </div>

                <!-- System Status (edit mode only) -->
                <div id="branchStatus" style="display:none;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                    <h4 style="font-size:0.85rem;font-weight:700;color:var(--text-primary);margin-bottom:0.75rem;">System Status</h4>
                    <div class="br-status-grid">
                        <div class="br-status-item" id="stInventory"><span class="status-dot"></span> Inventory Initialized</div>
                        <div class="br-status-item" id="stManager"><span class="status-dot"></span> Manager Assigned</div>
                        <div class="br-status-item" id="stPos"><span class="status-dot"></span> POS Ready</div>
                        <div class="br-status-item" id="stActive"><span class="status-dot"></span> Branch Active</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmit">Create Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deactivate Confirmation Modal -->
    <div class="modal-overlay" id="deactivateModal">
        <div class="modal" style="max-width:440px;">
            <h3 style="color:var(--red);display:flex;align-items:center;gap:8px;"><i class='bx bx-error-circle'></i> Deactivate Branch</h3>
            <div style="margin:1rem 0;font-size:0.85rem;color:var(--text-secondary);line-height:1.6;">
                <p style="margin-bottom:0.75rem;">Are you sure you want to deactivate this branch?</p>
                <p style="font-weight:600;color:var(--text-primary);margin-bottom:0.75rem;" id="deactivateBranchName">Branch Name</p>
                <p style="color:var(--amber);">This will prevent managers and cashiers assigned to this branch from logging in until the branch is activated again.</p>
            </div>
            <form method="POST" id="deactivateForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="deactivate">
                <input type="hidden" name="branch_id" id="deactivateBranchId" value="0">
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeDeactivateModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width:440px;">
            <h3 style="color:var(--red);display:flex;align-items:center;gap:8px;"><i class='bx bx-trash'></i> Delete Branch</h3>
            <div style="margin:1rem 0;font-size:0.85rem;color:var(--text-secondary);line-height:1.6;">
                <p style="margin-bottom:0.75rem;">Permanently delete this deactivated branch?</p>
                <p style="font-weight:600;color:var(--text-primary);margin-bottom:0.75rem;" id="deleteBranchName">Branch Name</p>
                <p style="color:var(--red);"><strong>This cannot be undone.</strong> The branch and all of its inventory records will be removed permanently. Branches with recorded sales or assigned users cannot be deleted.</p>
            </div>
            <form method="POST" id="deleteForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="branch_id" id="deleteBranchId" value="0">
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var branchData = (function () {
            try { return <?php echo json_encode($branches, JSON_THROW_ON_ERROR); ?>; }
            catch (e) { console.error('Failed to load branch data', e); return []; }
        })();
        var inventoryCounts = <?php echo json_encode($inventory_counts); ?>;
        var activeShiftCounts = <?php echo json_encode($active_shift_counts); ?>;
        var globalProductCount = <?php echo (int)$product_count; ?>;

        function openModal(action, id) {
            var modal = document.getElementById('branchModal');
            if (!modal) return;
            var title = document.getElementById('modalTitle');
            var formAction = document.getElementById('formAction');
            var submitBtn = document.getElementById('modalSubmit');

            var formBranchId = document.getElementById('formBranchId');
            var formBranchName = document.getElementById('formBranchName');
            var formLocation = document.getElementById('formLocation');
            var formStatus = document.getElementById('formStatus');

            var overview = document.getElementById('branchOverview');
            var statusSection = document.getElementById('branchStatus');
            var afterCreating = document.getElementById('afterCreating');
            var codePreview = document.getElementById('branchCodePreview');
            var dialog = document.getElementById('branchModalDialog');

            if (action === 'create') {
                if (title) title.textContent = 'Add New Branch';
                if (formAction) formAction.value = 'create';
                if (submitBtn) submitBtn.textContent = 'Create Branch';
                if (formBranchId) formBranchId.value = '0';
                if (formBranchName) formBranchName.value = '';
                if (formLocation) formLocation.value = '';
                if (formStatus) formStatus.value = 'active';
                if (overview) overview.style.display = 'none';
                if (statusSection) statusSection.style.display = 'none';
                if (afterCreating) afterCreating.style.display = 'block';
                if (codePreview) codePreview.style.display = 'block';
                if (dialog) dialog.style.maxWidth = '560px';
                updateBranchCode();
            } else {
                if (title) title.textContent = 'Edit Branch';
                if (formAction) formAction.value = 'update';
                if (submitBtn) submitBtn.textContent = 'Update Branch';
                if (overview) overview.style.display = 'block';
                if (statusSection) statusSection.style.display = 'block';
                if (afterCreating) afterCreating.style.display = 'none';
                if (codePreview) codePreview.style.display = 'none';
                if (dialog) dialog.style.maxWidth = '600px';
                var branch = branchData.find(function (b) { return b.id == id; });
                if (branch) {
                    if (formBranchId) formBranchId.value = branch.id;
                    if (formBranchName) formBranchName.value = branch.branch_name;
                    if (formLocation) formLocation.value = branch.location || '';
                    if (formStatus) formStatus.value = branch.status;

                    if (overview) {
                        setText('ovManager', branch.managers || 'None');
                        var cc = (branch.cashier_count || 0) + ' Active';
                        setText('ovCashiers', cc);
                        setText('ovProducts', globalProductCount);
                        var inv = inventoryCounts[id] || 0;
                        setText('ovInventory', inv + ' Items');
                        setText('ovCode', 'MB-' + String(id).padStart(3, '0'));
                        var created = branch.created_at ? formatDate(branch.created_at) : '-';
                        setText('ovCreated', created);
                    }
                    if (statusSection) {
                        var hasInv = (inventoryCounts[id] || 0) > 0;
                        setStatus('stInventory', hasInv, 'Inventory Initialized');
                        var hasMgr = branch.managers ? true : false;
                        setStatus('stManager', hasMgr, 'Manager Assigned');
                        var hasProducts = globalProductCount > 0;
                        setStatus('stPos', hasProducts, 'POS Ready');
                        var isActive = branch.status === 'active';
                        setStatus('stActive', isActive, 'Branch Active');
                    }
                }
            }
            modal.classList.add('show');
        }

        function setText(id, val) {
            var el = document.getElementById(id);
            if (el) el.textContent = val;
        }

        function setStatus(id, ok, label) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = '<span class="status-dot ' + (ok ? 'ok' : 'bad') + '"></span> ' + label;
        }

        function formatDate(dateStr) {
            try {
                var d = new Date(dateStr);
                var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
            } catch(e) { return dateStr; }
        }

        function updateBranchCode() {
            var nameEl = document.getElementById('formBranchName');
            var codeEl = document.getElementById('codePreviewValue');
            if (!nameEl || !codeEl) return;
            var name = nameEl.value.trim();
            var code = generateBranchCode(name);
            codeEl.textContent = code;
        }

        function generateBranchCode(name) {
            if (!name) return 'MB-';
            var cleaned = name.replace(/^(Minute\s+Burger\s+)/i, '');
            var words = cleaned.split(/\s+/).filter(function(w) { return w.length > 0; });
            var shortWords = ['de', 'del', 'la', 'las', 'los', 'da', 'do', 'das', 'dos', 'san', 'santa', 'van', 'von'];
            var prefix = '';
            for (var i = 0; i < words.length; i++) {
                var w = words[i].toLowerCase();
                if (shortWords.indexOf(w) === -1) {
                    prefix += words[i][0].toUpperCase();
                }
            }
            return 'MB-' + (prefix || 'BR');
        }

        function closeModal() {
            var modal = document.getElementById('branchModal');
            if (modal) modal.classList.remove('show');
        }

        function confirmDeactivate(id, name) {
            var modal = document.getElementById('deactivateModal');
            if (!modal) return true;
            document.getElementById('deactivateBranchId').value = id;
            var nameEl = document.getElementById('deactivateBranchName');
            if (nameEl) nameEl.textContent = name;
            modal.classList.add('show');
            return false;
        }

        function closeDeactivateModal() {
            var modal = document.getElementById('deactivateModal');
            if (modal) modal.classList.remove('show');
        }

        function confirmDelete(id, name) {
            var modal = document.getElementById('deleteModal');
            if (!modal) return true;
            document.getElementById('deleteBranchId').value = id;
            var nameEl = document.getElementById('deleteBranchName');
            if (nameEl) nameEl.textContent = name;
            modal.classList.add('show');
            return false;
        }

        function closeDeleteModal() {
            var modal = document.getElementById('deleteModal');
            if (modal) modal.classList.remove('show');
        }

        function htmlEsc(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function () {
            var branchModal = document.getElementById('branchModal');
            if (branchModal) {
                branchModal.addEventListener('click', function (e) {
                    if (e.target === this) closeModal();
                });
            }
            var deactModal = document.getElementById('deactivateModal');
            if (deactModal) {
                deactModal.addEventListener('click', function (e) {
                    if (e.target === this) closeDeactivateModal();
                });
            }
            var delModal = document.getElementById('deleteModal');
            if (delModal) {
                delModal.addEventListener('click', function (e) {
                    if (e.target === this) closeDeleteModal();
                });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { closeModal(); closeDeactivateModal(); closeDeleteModal(); }
            });
            var nameInput = document.getElementById('formBranchName');
            if (nameInput) {
                nameInput.addEventListener('input', updateBranchCode);
            }
        });
    </script>
</body>
</html>
