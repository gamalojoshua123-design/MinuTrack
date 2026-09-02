<?php
$cashier_current_page = basename($_SERVER['PHP_SELF']);

function isCaActive($pages) {
    global $cashier_current_page;
    $pages = is_array($pages) ? $pages : [$pages];
    return in_array($cashier_current_page, $pages) ? 'active' : '';
}
?>
<header class="pos-header">
    <div class="logo">
        <img src="img/logo (1)/mblogo (1).png" alt="Minute Burger Logo" />
        <span>Point of Sale System</span>
    </div>
    <div class="user-info">
        <?php if (isset($_SESSION['active_shift_id']) && isset($_SESSION['active_shift_type']) && !isAdmin()): ?>
            <div class="shift-active-badge">
                <i class='bx bx-time'></i> <?php echo $_SESSION['active_shift_type']; ?> Shift Active
            </div>
        <?php elseif (isAdmin()): ?>
            <div class="shift-active-badge" style="background: rgba(255, 255, 255, 0.3);">
                <i class='bx bx-shield'></i> Admin Mode (No Shift Required)
            </div>
        <?php endif; ?>

        <button class="cashier-nav-toggle" onclick="document.querySelector('.cashier-nav-links').classList.toggle('open')" aria-label="Toggle navigation">
            <i class='bx bx-menu'></i>
        </button>

        <div class="cashier-nav-links">
            <a href="pos.php" class="btn-header <?php echo isCaActive('pos.php'); ?>">
                <i class='bx bx-cart'></i> POS
            </a>

            <a href="cashier_inventory.php" class="btn-header <?php echo isCaActive('cashier_inventory.php'); ?>">
                <i class='bx bx-package'></i> Inventory
            </a>

            <a href="transactions.php" class="btn-header <?php echo isCaActive('transactions.php'); ?>">
                <i class='bx bx-receipt'></i> Transactions
            </a>

            <?php if (isset($_SESSION['active_shift_id']) && !isAdmin()): ?>
                <a href="x_reading.php" class="btn-header <?php echo isCaActive('x_reading.php'); ?>">
                    <i class='bx bx-chart'></i> Mid Shift Report
                </a>
                <a href="z_reading.php" class="btn-header <?php echo isCaActive('z_reading.php'); ?>">
                    <i class='bx bx-clipboard'></i> Close Shift
                </a>
            <?php endif; ?>

            <?php if (!isset($_SESSION['active_shift_id']) && !isAdmin()): ?>
                <a href="start_shift.php" class="btn-header <?php echo isCaActive('start_shift.php'); ?>">
                    <i class='bx bx-play-circle'></i> Start Shift
                </a>
            <?php endif; ?>

            <a href="logout.php" class="btn-header <?php echo isCaActive('logout.php'); ?>">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>
</header>
