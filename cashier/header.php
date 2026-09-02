<?php
$cashier_current_page = basename($_SERVER['PHP_SELF']);

function isCaActive($pages) {
    global $cashier_current_page;
    $pages = is_array($pages) ? $pages : [$pages];
    return in_array($cashier_current_page, $pages) ? 'active' : '';
}
?>
<!-- Shared cashier touch/speed ergonomics (applies to every page that
     includes this header). Loaded before the per-page <style> blocks so
     pages can still override it where they need to. -->
<link rel="stylesheet" href="/minute1/assets/css/cashier-touch.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/cashier-touch.css') ?: '1'; ?>">
<style>
    /* ═══════════════ CASHIER HEADER (SHARED) ═══════════════ */
    .pos-header {
        background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
        color: white;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .pos-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 1.25rem;
        gap: 1rem;
    }
    .pos-header .logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-shrink: 0;
    }
    .pos-header .logo img {
        height: 38px;
        width: 38px;
        border-radius: 50%;
        border: 2px solid var(--bright-lemon);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        transition: var(--transition);
        object-fit: cover;
    }
    .pos-header .logo img:hover {
        transform: scale(1.05);
    }
    .pos-header .logo span {
        font-size: 1rem;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        color: var(--white);
        white-space: nowrap;
    }
    .pos-header .header-right {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        justify-content: flex-end;
        flex-shrink: 1;
        min-width: 0;
    }
    .pos-header .shift-active-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.2rem 0.55rem;
        border-radius: 20px;
        font-size: 0.68rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .pos-header .alert-bell {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .pos-header .alert-bell i {
        font-size: 1.15rem;
        color: white;
    }
    .pos-header .bell-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        font-size: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    .pos-header .user-info {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .pos-header .user-info span {
        font-weight: 500;
        font-size: 0.78rem;
    }
    .pos-header-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.3rem;
        padding: 0.35rem 1.25rem;
        background: rgba(0, 0, 0, 0.1);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .pos-header-nav::-webkit-scrollbar {
        height: 0;
    }
    .pos-header .btn-header {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.35rem 0.7rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        font-size: 0.72rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        white-space: nowrap;
        flex-shrink: 0;
        height: 30px;
    }
    .pos-header .btn-header:hover {
        background: rgba(255, 255, 255, 0.25);
    }
    .pos-header .btn-header.active {
        background: rgba(255, 255, 255, 0.3);
        border-color: var(--bright-lemon);
    }
    .pos-header .cashier-nav-toggle {
        display: none;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        line-height: 1;
        flex-shrink: 0;
    }

    @media (max-width: 900px) {
        .pos-header-nav {
            display: none;
            flex-direction: column;
            align-items: stretch;
            gap: 0.25rem;
            padding: 0.5rem 1.25rem;
        }
        .pos-header-nav.open {
            display: flex;
        }
        .pos-header-nav .btn-header {
            width: 100%;
            justify-content: center;
            height: 34px;
        }
        .pos-header .cashier-nav-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .pos-header-top {
            padding: 0.4rem 0.75rem;
            gap: 0.5rem;
        }
        .pos-header .logo img {
            height: 32px;
            width: 32px;
        }
        .pos-header .logo span {
            font-size: 0.88rem;
        }
        .pos-header .shift-active-badge {
            font-size: 0.62rem;
            padding: 0.15rem 0.45rem;
        }
        .pos-header .user-info span {
            font-size: 0.7rem;
        }
        .pos-header-nav {
            padding: 0.35rem 0.75rem;
        }
    }

    @media (max-width: 480px) {
        .pos-header .logo img {
            height: 30px;
            width: 30px;
        }
        .pos-header .logo span {
            font-size: 0.82rem;
        }
    }

    @media (max-width: 360px) {
        .pos-header-top {
            padding: 0.3rem 0.5rem;
            gap: 0.4rem;
        }
        .pos-header .logo img {
            height: 26px;
            width: 26px;
        }
        .pos-header .logo span {
            font-size: 0.75rem;
        }
        .pos-header .shift-active-badge {
            font-size: 0.58rem;
            padding: 0.1rem 0.35rem;
        }
        .pos-header .user-info span {
            font-size: 0.65rem;
        }
        .pos-header-nav {
            padding: 0.25rem 0.5rem;
        }
        .pos-header .btn-header {
            height: 28px;
            font-size: 0.68rem;
            padding: 0.25rem 0.5rem;
        }
    }
</style>
<header class="pos-header">
    <div class="pos-header-top">
        <div class="logo">
            <img src="../img/logo (1)/mblogo (1).png" alt="Minute Burger Logo" />
            <span>Point of Sale System</span>
        </div>
        <div class="header-right">
            <?php if (isset($_SESSION['active_shift_id']) && isset($_SESSION['active_shift_type']) && !isAdmin()): ?>
                <div class="shift-active-badge">
                    <i class='bx bx-time'></i> <?php echo $_SESSION['active_shift_type']; ?> Shift
                </div>
            <?php elseif (isAdmin()): ?>
                <div class="shift-active-badge">
                    <i class='bx bx-shield'></i> Admin
                </div>
            <?php endif; ?>

            <div class="alert-bell" onclick="if(typeof showLowStockModal==='function'){showLowStockModal();}else{window.location.href='cashier_inventory.php?filter=low';}" title="View stock alerts">
                <i class='bx bx-bell'></i>
                <span class="bell-count" id="alert-count"><?php echo isset($total_alerts) ? $total_alerts : 0; ?></span>
            </div>

            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
            </div>

            <button class="cashier-nav-toggle" onclick="document.querySelector('.pos-header-nav').classList.toggle('open')" aria-label="Toggle navigation">
                <i class='bx bx-menu'></i>
            </button>
        </div>
    </div>
    <nav class="pos-header-nav">
        <?php if (!isCashier()): ?>
            <a href="<?php echo isOwner() ? '../admin/dashboard.php' : '../ai/admin.php'; ?>" class="btn-header">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
        <?php endif; ?>

        <a href="pos.php" class="btn-header <?php echo isCaActive('pos.php'); ?>">
            <i class='bx bx-cart'></i> POS
        </a>

        <a href="<?php echo isAdmin() ? '../inventory/inventory.php' : 'cashier_inventory.php'; ?>" class="btn-header <?php echo isCaActive(['cashier_inventory.php', 'inventory.php']); ?>">
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

        <a href="../auth/logout.php" class="btn-header <?php echo isCaActive('logout.php'); ?>">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </nav>
</header>
