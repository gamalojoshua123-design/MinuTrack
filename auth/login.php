<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/auth.php';
    header('Location: welcome.php');
    exit;
}

$error = '';
$error_type = '';

// Check for error query params from checkUserStatus() redirects
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'inactive') {
        $error = 'Your account is currently inactive. Please contact your Manager or Owner.';
        $error_type = 'inactive';
    } elseif ($_GET['error'] === 'session_expired') {
        $error = 'Your session has expired. Please log in again.';
        $error_type = 'session_expired';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limiting: max 5 attempts per minute per IP (server-side, keyed by
    // IP in the database so it can't be bypassed by discarding cookies).
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!checkIpRateLimit($pdo, $client_ip, 5, 60)) {
        $error = 'Too many failed login attempts. Please wait a minute and try again.';
        $error_type = 'rate_limited';
    } elseif ($user_id !== '' && $password !== '') {
        $checkStmt = $pdo->prepare("SELECT u.status, u.role, u.branch_id, b.status as branch_status FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.user_id = ?");
        $checkStmt->execute([$user_id]);
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser && strtolower($existingUser['status']) === 'inactive') {
            $error = 'Your account is currently inactive. Please contact your Manager or Owner.';
            $error_type = 'inactive';
        } elseif ($existingUser && $existingUser['role'] !== 'admin' && isset($existingUser['branch_status']) && strtolower($existingUser['branch_status']) === 'inactive') {
            $error = 'This branch has been deactivated. Please contact the System Owner.';
            $error_type = 'branch_inactive';
        } else {
            $user = authenticateUser($user_id, $password);

            if ($user) {
                resetIpRateLimit($pdo, $client_ip);
                header('Location: welcome.php');
                exit();
            } else {
                // Track failed attempt
                recordIpRateLimitAttempt($pdo, $client_ip);

                $error = 'Invalid User ID or password';
                $error_type = 'invalid';
            }
        }
    } else {
        $error = 'Please enter both User ID and password';
        $error_type = 'validation';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minute Burger - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #F37902;
            --primary-dark: #DC6902;
            --primary-light: #FFF7ED;
            --lemon: #FAE51D;
            --bg: #f5f6fa;
            --white: #ffffff;
            --text: #1a1a2e;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            --shadow-lg: 0 20px 50px -12px rgba(0,0,0,0.15);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══ LEFT BRANDING PANEL ═══ */
        .brand-panel {
            flex: 1;
            background: linear-gradient(160deg, #F37902 0%, #c45e00 60%, #a04d00 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(250,229,29,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 420px;
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.3);
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            margin: 0 auto 2rem;
            overflow: hidden;
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
        }

        .brand-logo:hover {
            transform: scale(1.05);
            border-color: var(--lemon);
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.03em;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .brand-tagline {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.85);
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 3rem;
        }

        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            transition: var(--transition);
        }

        .brand-feature:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(4px);
        }

        .brand-feature i {
            font-size: 1.3rem;
            color: var(--lemon);
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .brand-feature span {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
        }

        /* ═══ RIGHT LOGIN PANEL ═══ */
        .login-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: var(--bg);
            position: relative;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 440px;
            padding: 0;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .login-card-accent {
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--lemon), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 4s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: -200% 0; }
            50% { background-position: 200% 0; }
        }

        .login-card-body {
            padding: 2.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .login-header-icon i {
            font-size: 1.6rem;
            color: var(--primary);
        }

        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .login-header p {
            font-size: 0.88rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.4s ease;
        }

        .login-error i { font-size: 1.1rem; color: #ef4444; flex-shrink: 0; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        @supports not (gap: 1rem) {
            .login-form > .form-group + .form-group { margin-top: 1.25rem; }
            .form-group > * + * { margin-top: 0.4rem; }
            .brand-features > .brand-feature + .brand-feature { margin-top: 0.6rem; }
        }

        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        .form-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.15rem;
            color: var(--text-secondary);
            transition: var(--transition);
            pointer-events: none;
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 2.75rem 0.75rem 2.75rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.92rem;
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(243,121,2,0.08);
        }

        .form-input:focus + i,
        .form-input-wrap:focus-within i {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.15rem;
            padding: 6px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: rgba(243, 121, 2, 0.06);
        }

        .password-toggle:active {
            transform: scale(0.92);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
            box-shadow: 0 4px 14px rgba(243,121,2,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(243,121,2,0.4);
        }

        .btn-login:active { transform: translateY(0); }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.85;
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .btn-login.loading .spinner { display: block; }
        .btn-login.loading .btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .login-footer {
            text-align: center;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        .login-footer p {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .login-footer strong {
            color: var(--primary);
            font-weight: 600;
        }

        .role-info {
            display: flex;
            justify-content: center;
            gap: 1.25rem;
            margin-top: 0.75rem;
        }

        .role-tag {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .role-tag i { font-size: 0.85rem; }

        @media (max-width: 1024px) {
            .brand-name { font-size: 2rem; }
            .brand-tagline { font-size: 0.95rem; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; overflow: auto; }

            .brand-panel {
                padding: 2.5rem 2rem 2rem;
                min-height: auto;
            }

            .brand-logo { width: 80px; height: 80px; margin-bottom: 1.25rem; }
            .brand-name { font-size: 1.75rem; margin-bottom: 0.5rem; }
            .brand-tagline { font-size: 0.88rem; margin-bottom: 1.5rem; }
            .brand-features { display: none; }

            .login-panel { padding: 1.5rem; }
            .login-card { max-width: 100%; }
            .login-card-body { padding: 2rem 1.5rem; }
        }

        @media (max-width: 480px) {
            .brand-panel { padding: 2rem 1.5rem 1.5rem; }
            .brand-logo { width: 64px; height: 64px; }
            .brand-name { font-size: 1.5rem; }
            .login-card-body { padding: 1.75rem 1.25rem; }
            .login-header h2 { font-size: 1.3rem; }
            .password-toggle {
                width: 28px;
                height: 28px;
                right: 6px;
                font-size: 1rem;
            }
            .form-input {
                padding: 0.65rem 2.25rem 0.65rem 2.25rem;
                font-size: 0.85rem;
            }
            .form-input-wrap i {
                left: 10px;
                font-size: 1rem;
            }
        }

        .login-card { animation: fadeUp 0.6s ease-out; }
        .brand-content { animation: fadeUp 0.8s ease-out; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-feature:nth-child(1) { animation: fadeUp 0.6s ease-out 0.2s both; }
        .brand-feature:nth-child(2) { animation: fadeUp 0.6s ease-out 0.35s both; }
        .brand-feature:nth-child(3) { animation: fadeUp 0.6s ease-out 0.5s both; }

        /* Backup Completed Modal */
        .backup-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 2000;
        }

        .backup-modal {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 2rem;
            text-align: center;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: fadeUp 0.4s ease-out;
        }

        .backup-modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #f5f6fa;
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 1.2rem;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .backup-modal-close:hover {
            background: rgba(243,121,2,0.1);
            color: var(--primary);
        }

        .backup-modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #ecfdf5;
            color: #10b981;
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .backup-modal h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .backup-modal-reason {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .backup-modal-file {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f5f6fa;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            text-align: left;
            margin-bottom: 1.25rem;
        }

        .backup-modal-file i {
            font-size: 1.5rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .backup-modal-file-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            word-break: break-all;
        }

        .backup-modal-file-meta {
            font-size: 0.72rem;
            color: var(--text-secondary);
        }

        .backup-modal-actions {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .backup-modal-download {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .backup-modal-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16,185,129,0.35);
        }

        .backup-modal-continue {
            background: #f5f6fa;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }

        .backup-modal-continue:hover {
            background: rgba(243,121,2,0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .backup-modal-note {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 1rem;
        }

        /* Legacy-engine touch targets (login.php loads no admin.css).
           Placed last so it wins the cascade over mobile/base overrides. */
        @media (hover: none) and (pointer: coarse) {
            .password-toggle,
            .backup-modal-close { width: 44px; height: 44px; }
        }

        /* On touch there is no real hover, so a tap makes :hover stick and
           the button stays lifted. Neutralise the lift (not the -50%
           centering transforms, which aren't hover rules). */
        @media (hover: none) {
            .btn-login:hover,
            .backup-modal-download:hover,
            .backup-modal-continue:hover,
            .brand-feature:hover {
                transform: none !important;
            }

            .btn-login:active,
            .backup-modal-download:active,
            .backup-modal-continue:active {
                transform: scale(0.98) !important;
            }
        }

        button,
        .backup-modal-download {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body>

    <!-- Left: Branding -->
    <section class="brand-panel">
        <div class="brand-content">
            <div class="brand-logo">
                <img src="../img/logo (1)/mblogo (1).png" alt="Minute Burger Logo" onerror="this.parentElement.innerHTML='🍔'">
            </div>
            <h1 class="brand-name">Minute Burger</h1>
            <p class="brand-tagline">Point of Sale & Business Management System</p>
        </div>
    </section>

    <!-- Right: Login Form -->
    <section class="login-panel">
        <div class="login-card">
            <div class="login-card-accent"></div>
            <div class="login-card-body">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account to continue</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="login-error">
                        <i class='bx bx-error-circle'></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label class="form-label" for="user_id">User ID</label>
                        <div class="form-input-wrap">
                            <input type="text" class="form-input" id="user_id" name="user_id"
                                value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>"
                                required placeholder="Enter your User ID" autocomplete="username">
                            <i class='bx bx-user'></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-input-wrap">
                            <input type="password" class="form-input" id="password" name="password"
                                required placeholder="Enter your password" autocomplete="current-password">
                            <i class='bx bx-lock-alt'></i>
                            <button type="button" class="password-toggle" aria-label="Toggle password visibility" onclick="togglePassword()" title="Show password">
                                <i class='bx bx-hide' id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <div class="spinner"></div>
                    </button>
                </form>

                <div class="login-footer">
                    <p>Powered by <strong>Minute Burger</strong> POS System</p>
                </div>
            </div>
        </div>
    </section>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'bx bx-show';
            } else {
                pwd.type = 'password';
                icon.className = 'bx bx-hide';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            setTimeout(() => btn.classList.remove('loading'), 10000);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const userId = document.getElementById('user_id');
            const pwd = document.getElementById('password');
            if (!userId.value) {
                userId.focus();
            } else {
                pwd.focus();
            }
        });
    </script>

</body>
</html>