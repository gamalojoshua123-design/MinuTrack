<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

// If user is logged in, show post-login welcome screen
if (isset($_SESSION['user_id'])) {
    $branch_name = $_SESSION['branch_name'] ?? 'Minute Burger';
    $full_name = $_SESSION['full_name'] ?? 'User';

    // Determine redirect based on role
    if (isOwner()) {
        $redirect_url = '/minute1/admin/dashboard.php';
    } elseif (isManager()) {
        $redirect_url = '/minute1/ai/admin.php';
    } else {
        $redirect_url = '/minute1/cashier/pos.php';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Minute Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(160deg, #F37902 0%, #c45e00 60%, #a04d00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(250,229,29,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
            z-index: 0;
        }
        .welcome-card {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 32px;
            padding: 3rem 4rem;
            text-align: center;
            max-width: 460px;
            width: 90%;
            animation: welcomeIn 0.8s ease-out;
            box-shadow: 0 40px 80px rgba(0,0,0,0.3);
        }
        @keyframes welcomeIn {
            from { opacity: 0; transform: scale(0.92) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .welcome-logo {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.25);
            margin: 0 auto 1.5rem;
            overflow: hidden;
            background: rgba(255,255,255,0.1);
            animation: float 3s ease-in-out infinite;
        }
        .welcome-logo img {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .welcome-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        .welcome-title {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }
        .welcome-title span {
            color: #FAE51D;
        }
        .welcome-branch {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.75);
            margin-bottom: 2rem;
            font-weight: 400;
        }
        .btn-get-started {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: #fff;
            color: #F37902;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .btn-get-started:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            background: #FAE51D;
            color: #c45e00;
        }
        .btn-get-started i { font-size: 1.2rem; }
        .btn-get-started .arrow { transition: transform 0.3s ease; }
        .btn-get-started:hover .arrow { transform: translateX(4px); }
        .welcome-footer {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
        }
        @media (max-width: 480px) {
            .welcome-card { padding: 2rem 1.5rem; }
            .welcome-title { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="welcome-logo">
            <img src="/minute1/img/logo%20(1)/mblogo%20(1).png" alt="Minute Burger" onerror="this.parentElement.innerHTML='🍔'">
        </div>
        <div class="welcome-label">Welcome to</div>
        <div class="welcome-title">
            Minute <span>Burger</span>
        </div>
        <?php if ($branch_name && !isOwner()): ?>
            <div class="welcome-branch"><?php echo htmlspecialchars($branch_name); ?></div>
        <?php else: ?>
            <div class="welcome-branch">Owner Dashboard</div>
        <?php endif; ?>
        <a href="<?php echo $redirect_url; ?>" class="btn-get-started" id="getStartedBtn">
            Get Started <span class="arrow"><i class='bx bx-right-arrow-alt'></i></span>
        </a>
        <div class="welcome-footer">Redirecting automatically...</div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2800);
    </script>
</body>
</html>
<?php
    exit;
}

// ─── NOT LOGGED IN: public landing page ───
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Minute Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #F37902; --primary-dark: #DC6902; --lemon: #FAE51D; --bg: #f8fafc; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg); min-height: 100vh;
            display: flex; flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }
        .hero {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 2rem; position: relative; min-height: 100vh;
            background: linear-gradient(160deg, #F37902 0%, #c45e00 60%, #a04d00 100%);
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(250,229,29,0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
            z-index: 0;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: 0;
        }
        .hero-content {
            position: relative; z-index: 1; text-align: center;
            max-width: 700px; padding: 2rem; animation: fadeUp 1s ease-out;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .hero-logo {
            width: 140px; height: 140px; border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin: 0 auto 2rem; overflow: hidden;
            background: rgba(255,255,255,0.1);
            transition: all 0.4s ease; animation: float 3s ease-in-out infinite;
        }
        .hero-logo:hover { transform: scale(1.05); border-color: var(--lemon); animation: none; }
        .hero-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-title {
            font-size: 3.5rem; font-weight: 900; color: #fff;
            letter-spacing: -0.03em; margin-bottom: 0.5rem;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2); line-height: 1.1;
        }
        .hero-title span { color: var(--lemon); position: relative; }
        .hero-title span::after {
            content: ''; position: absolute; bottom: 5px; left: 0; right: 0;
            height: 4px; background: var(--lemon); border-radius: 2px; opacity: 0.3;
        }
        .hero-subtitle {
            font-size: 1.1rem; color: rgba(255,255,255,0.8);
            font-weight: 400; line-height: 1.6; margin-bottom: 2.5rem;
            max-width: 500px; margin-left: auto; margin-right: auto;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 1rem 2.8rem; border-radius: 50px; font-weight: 700;
            font-size: 1rem; font-family: inherit; cursor: pointer;
            transition: all 0.4s ease; text-decoration: none; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: relative; overflow: hidden;
            background: #ffffff; color: var(--primary);
        }
        .btn::before {
            content: ''; position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(250,229,29,0.2), transparent);
            transition: 0.5s;
        }
        .btn:hover::before { left: 100%; }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255,255,255,0.3);
            background: var(--lemon); color: var(--primary-dark);
        }
        .btn i { font-size: 1.2rem; }
        .btn .arrow { transition: all 0.3s ease; }
        .btn:hover .arrow { transform: translateX(4px); }
        .floating-decoration {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.03);
            pointer-events: none; z-index: 0;
        }
        .floating-decoration:nth-child(1) { width: 300px; height: 300px; top: -100px; right: -100px; animation: float 8s ease-in-out infinite; }
        .floating-decoration:nth-child(2) { width: 200px; height: 200px; bottom: -50px; left: -50px; animation: float 10s ease-in-out infinite reverse; }
        .floating-decoration:nth-child(3) { width: 150px; height: 150px; top: 50%; left: 50%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite; }
        @media (max-width: 768px) {
            .hero { padding: 1.5rem; }
            .hero-content { padding: 1rem; }
            .hero-logo { width: 100px; height: 100px; }
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 0.95rem; }
            .btn { padding: 0.8rem 2rem; font-size: 0.9rem; }
            .floating-decoration { display: none; }
        }
        @media (max-width: 480px) {
            .hero-title { font-size: 2rem; }
            .hero-logo { width: 80px; height: 80px; }
            .hero-subtitle { font-size: 0.85rem; }
        }

        /* On touch there is no real hover, so a tap leaves :hover stuck and
           the button stays lifted. Keyframe float animations are unaffected. */
        @media (hover: none) {
            .btn:hover,
            .btn-get-started:hover {
                transform: none !important;
            }
            .btn:active,
            .btn-get-started:active {
                transform: scale(0.98) !important;
            }
        }

        .btn,
        .btn-get-started,
        button {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="floating-decoration"></div>
        <div class="floating-decoration"></div>
        <div class="floating-decoration"></div>
        <div class="hero-content">
            <div class="hero-logo">
                <img src="/minute1/img/logo%20(1)/mblogo%20(1).png" alt="Minute Burger Logo" onerror="this.parentElement.innerHTML='🍔'">
            </div>
            <h1 class="hero-title">Minute <span>Burger</span></h1>
            <p class="hero-subtitle">Point of Sale &amp; Business Management System</p>
            <a href="login.php" class="btn">
                <i class='bx bx-log-in'></i> Get Started
                <span class="arrow"><i class='bx bx-right-arrow-alt'></i></span>
            </a>
        </div>
    </section>
</body>
</html>
