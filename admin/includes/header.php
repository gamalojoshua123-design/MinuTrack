<?php
$user_display = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Owner';
$user_initial = strtoupper(substr($user_display, 0, 1));
$role_label = 'Owner';
$branch_label = $user_branch_name ?: 'All Branches';

$alertOutOfStock = [];
$alertLowStock = [];
$alertTotalCount = 0;
if (isset($pdo)) {
    try {
        $stmtOos = $pdo->prepare("
            SELECT item_name, quantity, min_stock
            FROM inventory
            WHERE quantity <= 0
              AND (status IS NULL OR status = 'active')
              AND deleted_at IS NULL
            ORDER BY item_name ASC
            LIMIT 10
        ");
        $stmtOos->execute();
        $alertOutOfStock = $stmtOos->fetchAll(PDO::FETCH_ASSOC);

        $stmtLow = $pdo->prepare("
            SELECT item_name, quantity, min_stock
            FROM inventory
            WHERE quantity > 0 AND quantity < min_stock
              AND (status IS NULL OR status = 'active')
              AND deleted_at IS NULL
            ORDER BY (quantity / min_stock) ASC
            LIMIT 10
        ");
        $stmtLow->execute();
        $alertLowStock = $stmtLow->fetchAll(PDO::FETCH_ASSOC);

        // True badge count (not capped by the LIMIT 10 used for panel display).
        $alertCountStmt = $pdo->query("
            SELECT
                SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
                SUM(CASE WHEN quantity > 0 AND quantity < min_stock THEN 1 ELSE 0 END) AS low_stock
            FROM inventory
            WHERE (status IS NULL OR status = 'active')
              AND deleted_at IS NULL
        ");
        $alertCounts = $alertCountStmt->fetch(PDO::FETCH_ASSOC);
        $alertTotalCount = (int) ($alertCounts['out_of_stock'] ?? 0) + (int) ($alertCounts['low_stock'] ?? 0);
    } catch (Exception $e) {
        // Silently fail
    }
}
?>
<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
            <i class='bx bx-menu'></i>
        </button>
        <div class="header-title-block">
            <span class="page-kicker">Minute Burger · Admin</span>
            <h1 id="page-title"><?php echo PAGE_TITLES[$active_tab] ?? 'Dashboard'; ?></h1>
        </div>
    </div>
    <div class="header-right">
        <div class="header-date">
            <i class='bx bx-calendar'></i>
            <span><?php echo date('D, M j, Y'); ?></span>
        </div>
        <div class="inventory-notification-bell" onclick="toggleAlertPanel()">
            <i class='bx bx-bell'></i>
            <span class="notification-badge" id="alert-count-badge" style="<?php echo $alertTotalCount > 0 ? '' : 'display:none'; ?>"><?php echo (int) $alertTotalCount; ?></span>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <?php echo $user_initial; ?>
            </div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user_display); ?></div>
                <div class="user-role text-muted">Owner <?php echo htmlspecialchars($branch_label); ?></div>
            </div>
        </div>
    </div>
</header>

<div class="alert-panel" id="alert-panel" style="display:none;">
    <?php if ($alertTotalCount > 0): ?>
        <?php if (count($alertOutOfStock) > 0): ?>
            <div class="alert-section alert-critical">
                <div class="alert-section-header">
                    <i class='bx bx-error-circle'></i>
                    Out of Stock (<?php echo count($alertOutOfStock); ?>)
                </div>
                <?php foreach ($alertOutOfStock as $item): ?>
                    <div class="alert-item">
                        <span class="alert-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                        <span class="alert-item-qty critical">0 units</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($alertLowStock) > 0): ?>
            <div class="alert-section alert-warning">
                <div class="alert-section-header">
                    <i class='bx bx-error'></i>
                    Low Stock (<?php echo count($alertLowStock); ?>)
                </div>
                <?php foreach ($alertLowStock as $item): ?>
                    <div class="alert-item">
                        <span class="alert-item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                        <span class="alert-item-qty warning"><?php echo (int) $item['quantity']; ?> / <?php echo (int) $item['min_stock']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="alert-panel-footer">
            <a href="inventory.php" class="alert-panel-link">View Inventory <i class='bx bx-right-arrow-alt'></i></a>
        </div>
    <?php else: ?>
        <div class="alert-panel-empty">
            <i class='bx bx-check-circle'></i>
            <span>All stock levels are good</span>
        </div>
    <?php endif; ?>
</div>

<style>
.alert-panel {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 320px;
    max-height: 420px;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    z-index: 9999;
    overflow-y: auto;
    font-family: inherit;
}
.alert-section {
    padding: 0;
}
.alert-section-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.alert-critical .alert-section-header {
    background: #fef2f2;
    color: #dc2626;
    border-bottom: 1px solid #fecaca;
}
.alert-warning .alert-section-header {
    background: #fffbeb;
    color: #d97706;
    border-bottom: 1px solid #fde68a;
}
.alert-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 14px;
    border-bottom: 1px solid var(--border, #f3f4f6);
    font-size: 0.82rem;
}
.alert-item:last-child {
    border-bottom: none;
}
.alert-item-name {
    font-weight: 500;
    color: var(--text-primary, #1f2937);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}
.alert-item-qty {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 6px;
    white-space: nowrap;
}
.alert-item-qty.critical {
    background: #fef2f2;
    color: #dc2626;
}
.alert-item-qty.warning {
    background: #fffbeb;
    color: #d97706;
}
.alert-panel-footer {
    padding: 10px 14px;
    border-top: 1px solid var(--border, #e5e7eb);
    text-align: center;
}
.alert-panel-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--primary, #f37902);
    text-decoration: none;
    transition: color 0.2s;
}
.alert-panel-link:hover {
    color: var(--primary-dark, #d96800);
}
.alert-panel-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: var(--text-muted, #9ca3af);
    font-size: 0.85rem;
    gap: 8px;
}
.alert-panel-empty i {
    font-size: 1.8rem;
    color: #10b981;
}
</style>

<script>
function toggleSidebar() {
    var sidebar = document.getElementById('main-sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (sidebar) {
        sidebar.classList.toggle('open');
        if (overlay) {
            overlay.classList.toggle('active', sidebar.classList.contains('open'));
        }
    }
}
function closeSidebar() {
    var sidebar = document.getElementById('main-sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (sidebar) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    }
}
(function() {
    var overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    var sidebar = document.getElementById('main-sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            if (e.target.closest('.nav-item')) {
                closeSidebar();
            }
        });
    }
})();

var alertPanelVisible = false;
function toggleAlertPanel() {
    var panel = document.getElementById('alert-panel');
    if (!panel) return;
    alertPanelVisible = !alertPanelVisible;
    panel.style.display = alertPanelVisible ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    if (alertPanelVisible && !e.target.closest('.alert-panel') && !e.target.closest('.inventory-notification-bell')) {
        alertPanelVisible = false;
        var panel = document.getElementById('alert-panel');
        if (panel) panel.style.display = 'none';
    }
});
document.addEventListener('touchstart', function(e) {
    if (alertPanelVisible && !e.target.closest('.alert-panel') && !e.target.closest('.inventory-notification-bell')) {
        alertPanelVisible = false;
        var panel = document.getElementById('alert-panel');
        if (panel) panel.style.display = 'none';
    }
}, { passive: true });
</script>
<script>
function heartbeat() {
    fetch('../api/heartbeat.php').catch(function(){});
}
setInterval(heartbeat, 30000);
</script>
