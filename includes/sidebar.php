<?php
$current_page = basename($_SERVER['PHP_SELF']);

$is_owner = isOwner();
$branch_name = $_SESSION['branch_name'] ?? 'All Branches';
$role_label = getRoleLabel($_SESSION['role'] ?? 'user');

$base_path = '/minute1/';

// Role-appropriate dashboard landing page
if ($is_owner) {
    $dashboard_url = $base_path . 'admin/dashboard.php';
} elseif (isManager()) {
    $dashboard_url = $base_path . 'ai/admin.php';
} else {
    $dashboard_url = $base_path . 'cashier/pos.php';
}

// Inventory landing (owner has its own inventory dashboard)
$inventory_url = $is_owner ? $base_path . 'admin/inventory.php' : $base_path . 'inventory/inventory.php';

// Users landing (owner vs. branch-scoped manager page)
$users_url = $is_owner ? $base_path . 'admin/users.php' : $base_path . 'users/manager_users.php';

// Reports landing
$reports_url = $is_owner ? $base_path . 'admin/reports.php' : $base_path . 'reports/reports.php';

function isActive($pages) {
    global $current_page;
    $pages = is_array($pages) ? $pages : [$pages];
    return in_array($current_page, $pages) ? 'active' : '';
}
?>
<aside class="sidebar" id="main-sidebar">
    <div class="sidebar-inner">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/minute1/img/logo (1)/mblogo (1).png" alt="Minute Burger" onerror="this.parentElement.innerHTML='🍔'">
        </div>
        <div class="sidebar-title">
            Minute Burger
            <small><?php echo $role_label; ?><?php echo $branch_name && !$is_owner ? ' - ' . htmlspecialchars($branch_name) : ''; ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if (hasAnyPermission(['dashboard_view', 'pos_access', 'transactions_view'])): ?>
            <div class="nav-section-label">Overview</div>
        <?php endif; ?>

        <?php if (hasPermission('dashboard_view')): ?>
            <a href="<?php echo $dashboard_url; ?>" class="nav-item <?php echo isActive(['dashboard.php', 'admin.php', 'pos.php']); ?>">
                <i class='bx bx-home'></i> Dashboard
            </a>
        <?php endif; ?>

        <?php if (hasPermission('pos_access')): ?>
            <a href="<?php echo $base_path; ?>cashier/pos.php" class="nav-item <?php echo isActive('pos.php'); ?>">
                <i class='bx bx-cart'></i> POS
            </a>
        <?php endif; ?>

        <?php if (hasPermission('transactions_view')): ?>
            <a href="<?php echo $base_path; ?>transactions.php" class="nav-item <?php echo isActive('transactions.php'); ?>">
                <i class='bx bx-receipt'></i> Transactions
            </a>
        <?php endif; ?>

        <?php if (hasAnyPermission(['inventory_view', 'inventory_receive', 'inventory_manage', 'inventory_count'])): ?>
            <div class="nav-section-label">Inventory</div>
        <?php endif; ?>

        <?php if (hasPermission('inventory_view')): ?>
            <a href="<?php echo $inventory_url; ?>" class="nav-item <?php echo isActive(['inventory.php', 'inventory_view.php', 'cashier_inventory.php']); ?>">
                <i class='bx bx-package'></i> Inventory
            </a>
        <?php endif; ?>

        <?php if (hasPermission('inventory_count')): ?>
            <a href="<?php echo $base_path; ?>inventory/inventory_count.php" class="nav-item <?php echo isActive('inventory_count.php'); ?>">
                <i class='bx bx-clipboard'></i> Physical Count
            </a>
        <?php endif; ?>

        <?php if (hasPermission('products_view')): ?>
            <div class="nav-section-label">Menu</div>
            <a href="<?php echo hasPermission('products_manage') ? $base_path . 'admin/products.php' : $base_path . 'products/products.php'; ?>" class="nav-item <?php echo isActive(['products.php', 'product_ingredients.php', 'inventory_recipes.php']); ?>">
                <i class='bx bx-food-menu'></i> Products
            </a>
        <?php endif; ?>

        <?php if (hasAnyPermission(['users_manage', 'users_roles_manage', 'cashiers_manage'])): ?>
            <div class="nav-section-label">Administration</div>
        <?php endif; ?>

        <?php if (hasPermission('users_manage')): ?>
            <a href="<?php echo $users_url; ?>" class="nav-item <?php echo isActive(['users.php', 'manager_users.php']); ?>">
                <i class='bx bx-user'></i> Users
            </a>
        <?php endif; ?>

        <?php if (hasPermission('users_roles_manage')): ?>
            <a href="<?php echo $base_path; ?>admin/roles.php" class="nav-item <?php echo isActive('roles.php'); ?>">
                <i class='bx bx-lock-alt'></i> Roles &amp; Permissions
            </a>
        <?php endif; ?>

        <?php if (hasPermission('cashiers_manage')): ?>
            <a href="<?php echo $base_path; ?>admin/cashiers.php" class="nav-item <?php echo isActive('cashiers.php'); ?>">
                <i class='bx bx-user-check'></i> Cashiers
            </a>
        <?php endif; ?>

        <?php if (hasPermission('reports_view') || (hasPermission('branch_comparison_view') && !isManager())): ?>
            <div class="nav-section-label">Insights</div>
        <?php endif; ?>

        <?php if (hasPermission('reports_view')): ?>
            <a href="<?php echo $reports_url; ?>" class="nav-item <?php echo isActive('reports.php'); ?>">
                <i class='bx bx-bar-chart'></i> Reports
            </a>
        <?php endif; ?>

        <?php if (hasPermission('branch_comparison_view') && !isManager()): ?>
            <a href="<?php echo $base_path; ?>admin/branch_comparison.php" class="nav-item <?php echo isActive('branch_comparison.php'); ?>">
                <i class='bx bx-git-compare'></i> Branch Comparison
            </a>
        <?php endif; ?>

        <?php if (hasAnyPermission(['branches_manage', 'archive_view', 'backup_create', 'backup_restore', 'backup_delete', 'backup_download'])): ?>
            <div class="nav-section-label">System</div>
        <?php endif; ?>

        <?php if (hasPermission('branches_manage') && !isManager()): ?>
            <a href="<?php echo $base_path; ?>admin/branches.php" class="nav-item <?php echo isActive('branches.php'); ?>">
                <i class='bx bx-building-house'></i> Branches
            </a>
        <?php endif; ?>

        <?php if (hasPermission('archive_view')): ?>
            <a href="<?php echo $base_path; ?>tools/archive.php" class="nav-item <?php echo isActive('archive.php'); ?>">
                <i class='bx bx-archive'></i> Archive
            </a>
        <?php endif; ?>

        <?php if (hasAnyPermission(['backup_create', 'backup_restore', 'backup_delete', 'backup_download'])): ?>
            <a href="<?php echo $base_path; ?>tools/backup.php" class="nav-item <?php echo isActive('backup.php'); ?>">
                <i class='bx bx-cloud-download'></i> Backup
            </a>
        <?php endif; ?>

        <hr class="nav-divider">
        <a href="<?php echo $base_path; ?>auth/logout.php" class="nav-item nav-logout">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </nav>
    </div>

    <div class="sidebar-footer">
        <div class="system-status">
            <span class="status-dot pulse"></span>
            <span class="status-body">
                <span class="status-label">System Online</span>
                <span class="status-meta">All services operational</span>
            </span>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
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
function heartbeat() {
    fetch('<?php echo $base_path; ?>api/heartbeat.php').catch(function(){});
}
setInterval(heartbeat, 30000);

/* ═══════════════ GLOBAL DROPDOWN ═══════════════ */
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
    if (!e.target.closest('.action-dropdown, .action-dropdown-menu')) {
        closeAllDropdowns();
    }
});
document.addEventListener('touchstart', function(e) {
    if (!e.target.closest('.action-dropdown, .action-dropdown-menu')) {
        closeAllDropdowns();
    }
}, { passive: true });
</script>
