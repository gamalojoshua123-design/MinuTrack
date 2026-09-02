<?php
require_once __DIR__ . '/../includes/auth.php';

// Get the page they were trying to access
$requested_page = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'this page';
$role = isset($_SESSION['role']) ? getRoleLabel($_SESSION['role']) : 'User';
$user_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User';
$is_logged_in = isset($_SESSION['user_id']);

// Role-based dashboard link
if (isOwner()) {
    $dashboard_url = '/minute1/admin/dashboard.php';
} elseif (isManager()) {
    $dashboard_url = '/minute1/ai/admin.php';
} else {
    $dashboard_url = '/minute1/cashier/pos.php';
}

// Get referrer for back button
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$back_url = !empty($referrer) ? $referrer : (isset($_SESSION['last_page']) ? $_SESSION['last_page'] : '/minute1/cashier/pos.php');

if (!isset($_SESSION['last_page']) && isset($_SERVER['REQUEST_URI'])) {
    $_SESSION['last_page'] = $_SERVER['REQUEST_URI'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        :root {
            --harvest-orange: #F37902ff;
            --chocolate: #DC6902ff;
            --copperwood: #BE6B03ff;
            --bright-lemon: #FAE51Dff;
            --light-gray: #f5f5f5;
            --dark-gray: #333;
            --white: #fff;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
            --red: #e74c3c;
            --success: #27ae60;
        }

        body {
            background: linear-gradient(135deg, var(--harvest-orange), var(--copperwood));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 440px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--red), var(--harvest-orange), var(--bright-lemon));
        }

        .icon {
            width: 80px;
            height: 80px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid var(--red);
        }

        .icon i {
            font-size: 40px;
            color: var(--red);
        }

        h1 {
            color: var(--dark-gray);
            font-size: 26px;
            margin-bottom: 8px;
            font-weight: 800;
        }

        .subtitle {
            color: var(--red);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: var(--harvest-orange);
            margin: 0 auto 20px;
            border-radius: 2px;
        }

        p {
            color: #666;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 8px;
        }

        .user-info {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .user-info .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .user-info .value {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-top: 2px;
        }

        .user-info .role-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }

        .role-badge.admin { background: #fef3c7; color: #92400e; }
        .role-badge.manager { background: #dbeafe; color: #1e40af; }
        .role-badge.cashier { background: #d1fae5; color: #047857; }
        .role-badge.branch_owner { background: #e0e7ff; color: #3730a3; }
        .role-badge.default { background: #f3f4f6; color: #6b7280; }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-direction: column;
            margin-top: 5px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: var(--white);
            box-shadow: 0 4px 14px rgba(243, 121, 2, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 121, 2, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--harvest-orange);
            color: var(--harvest-orange);
        }

        .btn-outline:hover {
            background: var(--harvest-orange);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-back {
            background: var(--light-gray);
            color: var(--dark-gray);
            border: 2px solid #ddd;
        }

        .btn-back:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .btn-logout {
            background: var(--red);
            color: var(--white);
        }

        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }

        .footer span {
            color: var(--harvest-orange);
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 22px;
            }
            .icon {
                width: 65px;
                height: 65px;
            }
            .icon i {
                font-size: 30px;
            }
        }

        /* On touch there is no real hover, so a tap leaves :hover stuck and
           the button stays lifted until the user taps elsewhere. */
        @media (hover: none) {
            .btn-primary:hover,
            .btn-outline:hover,
            .btn-back:hover,
            .btn-logout:hover {
                transform: none !important;
            }
            .btn-primary:active,
            .btn-outline:active,
            .btn-back:active,
            .btn-logout:active {
                transform: scale(0.98) !important;
            }
        }

        .btn-primary,
        .btn-outline,
        .btn-back,
        .btn-logout,
        button {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class='bx bx-lock-alt'></i>
        </div>
        
        <h1>Access Denied</h1>
        <div class="subtitle">
            <i class='bx bx-error-circle'></i> Insufficient Permissions
        </div>
        <div class="divider"></div>
        
        <p>
            You don't have permission to access <strong><?php echo $requested_page; ?></strong>.
        </p>
        <p style="font-size: 13px; color: #888;">
            This action requires higher privileges than your current role provides.
        </p>

        <!-- User Info Card -->
        <div class="user-info">
            <div class="label">Logged in as</div>
            <div class="value"><?php echo $user_name; ?></div>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                <span class="role-badge <?php echo strtolower($role); ?>"><?php echo $role; ?></span>
                <span style="font-size: 12px; color: #999;">ID: <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <div class="btn-group">
            <?php if ($is_logged_in): ?>
                <!-- Dashboard Button -->
                <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">
                    <i class='bx bx-home'></i> Go to Dashboard
                </a>

                <!-- Back Button -->
                <button onclick="goBack()" class="btn btn-back">
                    <i class='bx bx-arrow-back'></i> Go Back
                </button>
                
                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-logout">
                    <i class='bx bx-log-out'></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class='bx bx-log-in'></i> Login
                </a>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            Need help? Contact <span>Administrator</span> &bull; Minute Burger <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        function goBack() {
            // Try browser history first
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
                return;
            }

            // Fallback: use stored URL
            const backUrl = '<?php echo htmlspecialchars($back_url); ?>';
            if (backUrl && backUrl !== window.location.href) {
                window.location.href = backUrl;
                return;
            }

            // Final fallback
            window.location.href = '/minute1/cashier/pos.php';
        }

        // Escape key goes back
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                goBack();
            }
        });

        sessionStorage.setItem('last_page', window.location.href);
    </script>
</body>
</html>