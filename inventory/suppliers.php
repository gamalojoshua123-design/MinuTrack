<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('inventory_manage');

$page_title = 'Suppliers';
$active_page = 'suppliers';

// ─── AJAX: Get single supplier ───
if (isset($_GET['action']) && $_GET['action'] === 'get_supplier') {
    requireAuth();
    header('Content-Type: application/json');
    try {
        if (empty($_GET['id'])) {
            throw new Exception('Supplier ID is required');
        }
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ? LIMIT 1");
        $stmt->execute([intval($_GET['id'])]);
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$supplier) {
            throw new Exception('Supplier not found');
        }
        echo json_encode(['success' => true, 'data' => $supplier]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ─── POST handlers ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $form_action = $_POST['form_action'] ?? '';

    // ADD SUPPLIER
    if ($form_action === 'add_supplier') {
        try {
            $supplier_name = trim($_POST['supplier_name'] ?? '');
            $contact_person = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $payment_terms = trim($_POST['payment_terms'] ?? 'COD');
            $lead_time_days = intval($_POST['lead_time_days'] ?? 7);

            if ($supplier_name === '') {
                throw new Exception('Supplier name is required');
            }

            $stmt = $pdo->prepare("
                INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, payment_terms, lead_time_days, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$supplier_name, $contact_person, $phone, $email, $address, $payment_terms, $lead_time_days]);

            header('Location: suppliers.php?message=' . urlencode('Supplier added successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: suppliers.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    // UPDATE SUPPLIER
    if ($form_action === 'update_supplier') {
        try {
            $id = intval($_POST['supplier_id'] ?? 0);
            $supplier_name = trim($_POST['supplier_name'] ?? '');
            $contact_person = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $payment_terms = trim($_POST['payment_terms'] ?? 'COD');
            $lead_time_days = intval($_POST['lead_time_days'] ?? 7);

            if ($id <= 0) throw new Exception('Invalid supplier');
            if ($supplier_name === '') throw new Exception('Supplier name is required');

            $stmt = $pdo->prepare("
                UPDATE suppliers
                SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, payment_terms = ?, lead_time_days = ?
                WHERE id = ?
            ");
            $stmt->execute([$supplier_name, $contact_person, $phone, $email, $address, $payment_terms, $lead_time_days, $id]);

            header('Location: suppliers.php?message=' . urlencode('Supplier updated successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: suppliers.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    // TOGGLE STATUS
    if ($form_action === 'toggle_status') {
        try {
            $id = intval($_POST['supplier_id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid supplier');

            $stmt = $pdo->prepare("UPDATE suppliers SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: suppliers.php?message=' . urlencode('Supplier status updated') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: suppliers.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    // DELETE SUPPLIER
    if ($form_action === 'delete_supplier') {
        try {
            $id = intval($_POST['supplier_id'] ?? 0);
            if ($id <= 0) throw new Exception('Invalid supplier');

            // Check if supplier has linked inventory
            $stmt = $pdo->prepare("SELECT supplier_name FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $sup = $stmt->fetch();
            if (!$sup) throw new Exception('Supplier not found');

            $linked = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE supplier = ?");
            $linked->execute([$sup['supplier_name']]);
            if ($linked->fetchColumn() > 0) {
                throw new Exception('Cannot delete supplier with linked inventory items. Deactivate it instead.');
            }

            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: suppliers.php?message=' . urlencode('Supplier deleted successfully') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: suppliers.php?message=' . urlencode('Error: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }
}

// ─── Stats queries ───
$total_suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$active_suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE is_active = 1")->fetchColumn();

$pending_deliveries = 0;
try {
    $pending_deliveries = $pdo->query("SELECT COUNT(*) FROM inventory_deliveries WHERE status = 'upcoming'")->fetchColumn();
} catch (Exception $e) {}

$avg_lead_time = 0;
try {
    $avg = $pdo->query("SELECT AVG(lead_time_days) FROM suppliers WHERE is_active = 1")->fetchColumn();
    $avg_lead_time = $avg ? round($avg, 1) : 0;
} catch (Exception $e) {}

// ─── Filters & Pagination ───
$search_term = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$sup_current_page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($sup_current_page - 1) * $per_page;

// Build WHERE clause
$where_clauses = [];
$params = [];

if ($search_term !== '') {
    $where_clauses[] = "(supplier_name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = '%' . $search_term . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if ($status_filter === 'active') {
    $where_clauses[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_clauses[] = "is_active = 0";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = (int) max(1, ceil($total_records / $per_page));

// Fetch suppliers
$stmt = $pdo->prepare("
    SELECT s.*,
        (SELECT COUNT(*) FROM inventory WHERE supplier = s.supplier_name) AS inventory_count
    FROM suppliers s
    $where_sql
    ORDER BY s.supplier_name ASC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$suppliers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Recent deliveries ───
$recent_deliveries = [];
try {
    $stmt = $pdo->query("
        SELECT d.*, i.item_name
        FROM inventory_deliveries d
        LEFT JOIN inventory i ON d.inventory_id = i.id
        ORDER BY d.created_at DESC
        LIMIT 10
    ");
    $recent_deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Minute Burger Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stat-icon.icon-suppliers { background: var(--blue-light); color: var(--blue); }
        .stat-icon.icon-active { background: var(--green-light); color: var(--green); }
        .stat-icon.icon-deliveries { background: var(--amber-light); color: var(--amber); }
        .stat-icon.icon-leadtime { background: var(--purple-light); color: var(--purple); }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .filter-form .form-control {
            width: auto;
            min-width: 170px;
        }
        .supplier-email {
            color: var(--text-muted);
            font-size: 0.82rem;
        }
        .lead-time-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            background: var(--blue-light);
            color: var(--blue);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .delivery-status {
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .delivery-status.upcoming { background: var(--amber-light); color: #b45309; }
        .delivery-status.completed { background: var(--green-light); color: var(--green); }
        .delivery-status.cancelled { background: var(--red-light); color: var(--red); }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .filter-form { flex-direction: column; }
            .filter-form .form-control { width: 100%; min-width: unset; }
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($_GET['message'])): ?>
                    <div class="message <?php echo (($_GET['type'] ?? 'success') === 'error') ? 'error' : 'success'; ?>">
                        <?php echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-suppliers"><i class='bx bx-store'></i></div>
                            <div>
                                <div class="stat-title">Total Suppliers</div>
                                <div class="stat-value"><?php echo $total_suppliers; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-active"><i class='bx bx-check-circle'></i></div>
                            <div>
                                <div class="stat-title">Active Suppliers</div>
                                <div class="stat-value"><?php echo $active_suppliers; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-deliveries"><i class='bx bx-truck'></i></div>
                            <div>
                                <div class="stat-title">Pending Deliveries</div>
                                <div class="stat-value"><?php echo $pending_deliveries; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon icon-leadtime"><i class='bx bx-time-five'></i></div>
                            <div>
                                <div class="stat-title">Avg Lead Time</div>
                                <div class="stat-value"><?php echo $avg_lead_time; ?> <small style="font-size:0.6em;font-weight:500;">days</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suppliers Table Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class='bx bx-store'></i> Supplier Directory
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="showAddSupplierForm()">
                            <i class='bx bx-plus' style="margin-right:4px;"></i> Add Supplier
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="card-body" style="padding-bottom:0;">
                        <form method="GET" class="filter-form">
                            <input type="text" name="search" class="form-control" placeholder="Search suppliers..."
                                value="<?php echo htmlspecialchars($search_term); ?>">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">
                                <i class='bx bx-search' style="margin-right:4px;"></i> Search
                            </button>
                            <?php if ($search_term !== '' || $status_filter !== 'all'): ?>
                                <a href="suppliers.php" class="btn btn-outline btn-sm" style="text-decoration:none;">
                                    <i class='bx bx-x' style="margin-right:4px;"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <?php if (empty($suppliers_list)): ?>
                            <div class="empty-state">
                                <i class='bx bx-store'></i>
                                <p>No suppliers found<?php echo $search_term !== '' ? ' matching your search' : ''; ?>.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Supplier Name</th>
                                        <th>Contact Person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Payment Terms</th>
                                        <th>Lead Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers_list as $s): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($s['supplier_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($s['contact_person'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($s['phone'] ?: '—'); ?></td>
                                            <td><span class="supplier-email"><?php echo htmlspecialchars($s['email'] ?: '—'); ?></span></td>
                                            <td><?php echo htmlspecialchars($s['payment_terms'] ?: '—'); ?></td>
                                            <td><span class="lead-time-badge"><?php echo intval($s['lead_time_days']); ?> days</span></td>
                                            <td>
                                                <?php if ($s['is_active']): ?>
                                                    <span class="status-badge status-active">Active</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-dropdown">
                                                    <button class="action-dropdown-toggle" type="button" aria-label="Actions menu" onclick="toggleDropdown(this)">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <div class="action-dropdown-menu">
                                                        <button class="action-edit" onclick="editSupplier(<?php echo $s['id']; ?>)">
                                                            <i class='bx bx-edit-alt'></i> Edit Supplier
                                                        </button>
                                                        <form method="POST" style="margin:0;">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="form_action" value="toggle_status">
                                                            <input type="hidden" name="supplier_id" value="<?php echo $s['id']; ?>">
                                                            <button type="submit" class="action-toggle">
                                                                <i class='bx bx-<?php echo $s['is_active'] ? 'hide' : 'show'; ?>'></i>
                                                                <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                        </form>
                                                        <?php if (intval($s['inventory_count']) === 0): ?>
                                                            <div class="action-dropdown-divider"></div>
                                                            <form method="POST" style="margin:0;" onsubmit="return askConfirm(event, 'Permanently delete this supplier?');">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="form_action" value="delete_supplier">
                                                                <input type="hidden" name="supplier_id" value="<?php echo $s['id']; ?>">
                                                                <button type="submit" class="action-delete">
                                                                    <i class='bx bx-trash'></i> Delete
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <?php
                        $pagination_params = array_filter([
                            'search' => $search_term,
                            'status' => $status_filter !== 'all' ? $status_filter : null,
                        ]);
                        ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> suppliers
                            </div>
                            <div class="pagination-controls">
                                <?php if ($sup_current_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $sup_current_page - 1])); ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class='bx bx-chevron-left'></i></span>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $sup_current_page - 2);
                                $end_page = min($total_pages, $sup_current_page + 2);

                                if ($start_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => 1])); ?>" class="page-btn">1</a>
                                    <?php if ($start_page > 2): ?><span class="page-dots">...</span><?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $p])); ?>"
                                       class="page-btn <?php echo $p === $sup_current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $total_pages])); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($sup_current_page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $sup_current_page + 1])); ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class='bx bx-chevron-right'></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="pagination-bar">
                            <div class="pagination-info">
                                Showing <?php echo $total_records; ?> supplier<?php echo $total_records !== 1 ? 's' : ''; ?>
                            </div>
                            <div class="pagination-controls"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Deliveries Section -->
                <?php if (!empty($recent_deliveries)): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class='bx bx-truck'></i> Recent Deliveries
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Supplier</th>
                                    <th>Quantity</th>
                                    <th>Order Date</th>
                                    <th>Expected Date</th>
                                    <th>Received Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_deliveries as $d): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($d['item_name'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($d['supplier'] ?? '—'); ?></td>
                                        <td><?php echo intval($d['quantity'] ?? 0); ?></td>
                                        <td><?php echo $d['order_date'] ? date('M j, Y', strtotime($d['order_date'])) : '—'; ?></td>
                                        <td><?php echo $d['expected_date'] ? date('M j, Y', strtotime($d['expected_date'])) : '—'; ?></td>
                                        <td><?php echo $d['received_date'] ? date('M j, Y', strtotime($d['received_date'])) : '—'; ?></td>
                                        <td><span class="delivery-status <?php echo htmlspecialchars($d['status'] ?? ''); ?>"><?php echo htmlspecialchars($d['status'] ?? '—'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /content-area -->
        </div><!-- /main-content -->
    </div><!-- /admin-layout -->

    <!-- Add/Edit Supplier Modal -->
    <div class="modal" id="supplier-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="supplier-modal-title">Add New Supplier</h3>
                <button type="button" class="modal-close" aria-label="Close modal" onclick="closeModal('supplier-modal')">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="supplier-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" id="form-action" value="add_supplier">
                    <input type="hidden" name="supplier_id" id="supplier-id" value="">

                    <div class="form-group">
                        <label class="form-label">Supplier Name <span style="color:var(--red);">*</span></label>
                        <input type="text" name="supplier_name" id="field-supplier_name" class="form-control" required placeholder="Enter supplier name">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="field-contact_person" class="form-control" placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="field-phone" class="form-control" placeholder="09XXXXXXXXX">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="field-email" class="form-control" placeholder="email@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="field-address" class="form-control" rows="2" placeholder="Full address"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" id="field-payment_terms" class="form-control">
                                <option value="COD">COD</option>
                                <option value="Net 7">Net 7</option>
                                <option value="Net 15">Net 15</option>
                                <option value="Net 30">Net 30</option>
                                <option value="Net 60">Net 60</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lead Time (days)</label>
                            <input type="number" name="lead_time_days" id="field-lead_time_days" class="form-control" min="1" max="365" value="7" placeholder="7">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal('supplier-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="supplier-submit-btn">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Close modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Show Add form
        function showAddSupplierForm() {
            document.getElementById('supplier-modal-title').textContent = 'Add New Supplier';
            document.getElementById('form-action').value = 'add_supplier';
            document.getElementById('supplier-id').value = '';
            document.getElementById('supplier-submit-btn').textContent = 'Add Supplier';
            document.getElementById('supplier-form').reset();
            document.getElementById('supplier-modal').style.display = 'flex';
        }

        // Edit supplier (fetch via AJAX then populate modal)
        function editSupplier(id) {
            fetch('suppliers.php?action=get_supplier&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showToastMsg(data.error || 'Failed to load supplier', 'error');
                        return;
                    }
                    const s = data.data;
                    document.getElementById('supplier-modal-title').textContent = 'Edit Supplier';
                    document.getElementById('form-action').value = 'update_supplier';
                    document.getElementById('supplier-id').value = s.id;
                    document.getElementById('supplier-submit-btn').textContent = 'Update Supplier';

                    document.getElementById('field-supplier_name').value = s.supplier_name || '';
                    document.getElementById('field-contact_person').value = s.contact_person || '';
                    document.getElementById('field-phone').value = s.phone || '';
                    document.getElementById('field-email').value = s.email || '';
                    document.getElementById('field-address').value = s.address || '';
                    document.getElementById('field-payment_terms').value = s.payment_terms || 'COD';
                    document.getElementById('field-lead_time_days').value = s.lead_time_days || 7;

                    document.getElementById('supplier-modal').style.display = 'flex';
                })
                .catch(err => {
                    showToastMsg('Error loading supplier data', 'error');
                    console.error(err);
                });
        }

        // Auto-dismiss success messages
        setTimeout(function() {
            const msg = document.querySelector('.message');
            if (msg) msg.style.display = 'none';
        }, 4000);
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>
