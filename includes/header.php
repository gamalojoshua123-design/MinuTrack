<?php
// includes/header.php
$user_display = $_SESSION['full_name'] ?? $_SESSION['username'] ?? $_SESSION['login_user_id'] ?? 'Admin';
$user_initial = strtoupper(substr($user_display, 0, 1));
$user_role = ucfirst($_SESSION['role'] ?? 'Administrator');
?>
<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
            <i class='bx bx-menu'></i>
        </button>
        <h1 id="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
    </div>
    <div class="header-right">
        <div class="notification-bell" onclick="typeof toggleAlertPanel === 'function' ? toggleAlertPanel() : (typeof toggleNotificationPanel === 'function' ? toggleNotificationPanel() : null)">
            <i class='bx bx-bell'></i>
            <span class="notification-badge" id="alert-count-badge" style="display:none;">0</span>
        </div>
        <div style="width:1px;height:28px;background:var(--border);"></div>
        <div class="user-info">
            <div class="user-avatar"><?php echo $user_initial; ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user_display); ?></div>
                <div class="user-role"><?php echo $user_role; ?></div>
            </div>
        </div>
    </div>
</header>
