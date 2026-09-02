<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission('pos_access');

// ============================================================
// FIX: Managers don't need to start shifts - auto redirect
// ============================================================
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')) {
    // For managers: Auto-create a shift silently
    if ($_SESSION['role'] === 'manager') {
        $stmt = $pdo->prepare("
            SELECT * FROM cashier_shifts 
            WHERE cashier_id = ? AND status = 'active'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $active_shift = $stmt->fetch();
        
        if (!$active_shift) {
            // Auto-start a shift for managers
            $shift_type = date('H') >= 6 && date('H') < 18 ? 'AM' : 'PM';
            $shift_date = date('Y-m-d');
            if ($shift_type === 'PM' && date('H') < 6) {
                $shift_date = date('Y-m-d', strtotime('-1 day'));
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO cashier_shifts
                    (cashier_id, shift_date, shift_type, start_time, opening_cash, status, started_by, shift_quota, late_start, late_minutes)
                VALUES (?, ?, ?, NOW(), 0, 'active', ?, 10000, 0, 0)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $shift_date,
                $shift_type,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['active_shift_id'] = $pdo->lastInsertId();
            $_SESSION['active_shift_type'] = $shift_type;
            $_SESSION['shift_quota'] = 10000;
        } else {
            $_SESSION['active_shift_id'] = $active_shift['id'];
            $_SESSION['active_shift_type'] = $active_shift['shift_type'];
            $_SESSION['shift_quota'] = $active_shift['shift_quota'] ?? 10000;
        }
    }
    
    header('Location: pos.php');
    exit();
}
// ============================================================
    
$page_title = 'Start Shift';

// Check if cashier has an active shift
$stmt = $pdo->prepare("
    SELECT * FROM cashier_shifts 
    WHERE cashier_id = ? AND status = 'active'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$active_shift = $stmt->fetch();

if ($active_shift) {
    header('Location: pos.php?message=' . urlencode('You have an active shift. Please close your current shift before starting a new one.') . '&type=warning');
    exit();
}


$message = '';
$message_type = '';

// Get current time for automatic shift selection
date_default_timezone_set('Asia/Manila');
$current_hour = date('H');
$current_minute = date('i');
$current_time_24 = date('H:i');
$current_time_12 = date('h:i A');
$current_date = date('F j, Y');
$current_day = date('l');
$current_timestamp = time(); // Define current timestamp here

// Get shift quota from database or use default
$shift_quota = 10000; // Default quota per shift (₱10,000)

// Get current shift's sales if starting late
if (isset($_GET['late_shift'])) {
    $late_shift = true;
    $current_shift_id = $_GET['shift_id'] ?? null;
} else {
    $late_shift = false;
}

// Automatic shift determination based on current time
if ($current_hour >= 6 && $current_hour < 18) {
    $auto_shift = 'AM';
    $auto_shift_start = "6:00 AM";
    $auto_shift_end = "6:00 PM";
    $shift_icon = 'bx-sun';
    $shift_color = '#F39C12';
    $shift_gradient = 'linear-gradient(135deg, #F39C12, #E67E22)';
    $shift_start_timestamp = strtotime(date('Y-m-d') . ' 06:00:00');
} else {
    $auto_shift = 'PM';
    $auto_shift_start = "6:00 PM";
    $auto_shift_end = "6:00 AM";
    $shift_icon = 'bx-moon';
    $shift_color = '#2C3E50';
    $shift_gradient = 'linear-gradient(135deg, #2C3E50, #1a2632)';
    $shift_start_timestamp = strtotime(date('Y-m-d') . ' 18:00:00');
}

// Check if user is late (current time is after shift start)
$is_late = false;
$late_minutes = 0;

if ($current_timestamp > $shift_start_timestamp) {
    $is_late = true;
    $late_minutes = floor(($current_timestamp - $shift_start_timestamp) / 60);
}

// Calculate time remaining in current shift period
if ($auto_shift == 'AM') {
    // AM Shift: 6 AM to 6 PM
    $shift_end_timestamp = strtotime(date('Y-m-d') . ' 18:00:00');
    
    // If current time is after 6 PM, show next AM shift
    if ($current_timestamp > $shift_end_timestamp) {
        $shift_end_timestamp = strtotime(date('Y-m-d', strtotime('+1 day')) . ' 18:00:00');
        $is_late = false; // Not late for next shift
    }
} else {
    // PM Shift: 6 PM to 6 AM next day
    $shift_end_timestamp = strtotime(date('Y-m-d') . ' 06:00:00 +1 day');
    
    // If current time is after 6 AM but before 6 PM, show next PM shift
    if ($current_timestamp > strtotime(date('Y-m-d') . ' 06:00:00') && $current_timestamp < strtotime(date('Y-m-d') . ' 18:00:00')) {
        $shift_end_timestamp = strtotime(date('Y-m-d', strtotime('+1 day')) . ' 06:00:00 +1 day');
        $is_late = false; // Not late for next shift
    }
}

$time_remaining = $shift_end_timestamp - $current_timestamp;

// Ensure time remaining is not negative
if ($time_remaining < 0) {
    $time_remaining = 0;
}

$hours_remaining = floor($time_remaining / 3600);
$minutes_remaining = floor(($time_remaining % 3600) / 60);

// Cap at 12 hours max (should never exceed shift length)
if ($hours_remaining > 12) {
    $hours_remaining = 12;
    $minutes_remaining = 0;
}

// Auto-calculate recommended opening cash (based on shift type and time)
if ($auto_shift == 'AM') {
    // Morning shift: start with ₱2,000
    $recommended_cash = 2000;
    // If late, reduce recommended cash based on lost hours
    if ($is_late && $late_minutes > 0) {
        $lost_hours = $late_minutes / 60;
        $projected_sales = ($shift_quota / 12) * (12 - $lost_hours);
        $recommended_cash = max(500, $recommended_cash - ($recommended_cash * ($lost_hours / 12)));
    }
} else {
    // PM shift: start with ₱3,000 (since more transactions in evening)
    $recommended_cash = 3000;
    // If late, reduce recommended cash based on lost hours
    if ($is_late && $late_minutes > 0) {
        $lost_hours = $late_minutes / 60;
        $projected_sales = ($shift_quota / 12) * (12 - $lost_hours);
        $recommended_cash = max(500, $recommended_cash - ($recommended_cash * ($lost_hours / 12)));
    }
}

// Calculate recommended quota for this shift (if starting late)
$recommended_quota = $shift_quota;
if ($is_late && $late_minutes > 0) {
    $lost_hours = $late_minutes / 60;
    $recommended_quota = ($shift_quota / 12) * (12 - $lost_hours);
    $recommended_quota = max(0, round($recommended_quota));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_shift'])) {
    requireCsrfToken();
    $shift_type = $_POST['shift_type'] ?? $auto_shift;
    $opening_cash = floatval($_POST['opening_cash'] ?? 0);
    $shift_quota_amount = floatval($_POST['shift_quota'] ?? $shift_quota);
    
    // Check for negative amount
    if ($opening_cash < 0) {
        $message = 'Invalid amount: Opening cash cannot be negative. Please enter a valid amount.';
        $message_type = 'error';
    }
    // Check if opening cash exceeds 100,000
    elseif ($opening_cash > 100000) {
        $message = 'Warning: Opening cash amount (₱' . number_format($opening_cash, 2) . ') exceeds ₱100,000. Please verify the amount with your manager before proceeding.';
        $message_type = 'error';
    } elseif ($shift_type && in_array($shift_type, ['AM', 'PM'])) {
        try {
            $shift_date = date('Y-m-d');
            
            if ($shift_type == 'PM' && $current_hour >= 0 && $current_hour < 6) {
                $shift_date = date('Y-m-d', strtotime('-1 day'));
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM cashier_shifts 
                WHERE cashier_id = ? AND shift_date = ? AND shift_type = ? AND status = 'active'
            ");
            $stmt->execute([$_SESSION['user_id'], $shift_date, $shift_type]);
            $existing_shift = $stmt->fetch();
            
            if ($existing_shift) {
                throw new Exception('You already have an active ' . $shift_type . ' shift for this period.');
            }
            
            // Record if shift was started late
            $late_start = $is_late ? 1 : 0;
            $late_minutes_recorded = $is_late ? $late_minutes : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO cashier_shifts 
                (cashier_id, shift_date, shift_type, start_time, opening_cash, status, started_by, shift_quota, late_start, late_minutes)
                VALUES (?, ?, ?, NOW(), ?, 'active', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $shift_date,
                $shift_type,
                $opening_cash,
                $_SESSION['user_id'],
                $shift_quota_amount,
                $late_start,
                $late_minutes_recorded
            ]);
            
            $shift_id = $pdo->lastInsertId();
            
            $_SESSION['active_shift_id'] = $shift_id;
            $_SESSION['active_shift_type'] = $shift_type;
            $_SESSION['shift_quota'] = $shift_quota_amount;
            
            $message_text = 'Shift started successfully! ' . $shift_type . ' shift | Opening cash: ₱' . number_format($opening_cash, 2) . ' | Quota: ₱' . number_format($shift_quota_amount, 2);
            if ($is_late) {
                $message_text .= ' | ⚠️ Late by ' . $late_minutes . ' minutes';
            }
            
            header('Location: pos.php?message=' . urlencode($message_text) . '&type=success');
            exit();
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Please select a valid shift type';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Start Shift - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <style>
        :root {
            --mb-orange: #F37902;
            --mb-orange-dark: #DC6902;
            --mb-yellow: #FAE51D;
            --mb-cream: #EDD0A9;
            --mb-dark: #2C3E50;
            --mb-gray: #7F8C8D;
            --mb-light: #F8F9FA;
            --white: #FFFFFF;
            --success: #27AE60;
            --warning: #F39C12;
            --danger: #E74C3C;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --transition: all 0.2s ease;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .shift-container {
            background: var(--white);
            border-radius: 32px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 720px;
            margin: 2rem auto;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shift-header {
            background: linear-gradient(135deg, var(--mb-orange), var(--mb-orange-dark));
            color: white;
            padding: 28px 24px;
            text-align: center;
        }

        .shift-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--mb-yellow);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            margin-bottom: 12px;
        }

        .shift-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .shift-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .shift-body {
            padding: 28px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-card {
            background: var(--mb-light);
            border-radius: 20px;
            padding: 16px;
            text-align: center;
            border: 1px solid var(--mb-cream);
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--mb-gray);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--mb-dark);
        }

        .info-value.large {
            font-size: 1.8rem;
            font-family: monospace;
            letter-spacing: 1px;
        }

        .info-sub {
            font-size: 0.7rem;
            color: var(--mb-gray);
            margin-top: 4px;
        }

        /* Late Alert */
        .late-alert {
            background: #FEF2F0;
            border-left: 4px solid var(--danger);
            padding: 12px 16px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .late-alert i {
            font-size: 1.5rem;
            color: var(--danger);
        }

        .late-alert-content {
            flex: 1;
        }

        .late-alert-title {
            font-weight: 700;
            color: #C0392B;
            margin-bottom: 4px;
        }

        .late-alert-message {
            font-size: 0.8rem;
            color: #666;
        }

        /* Auto Shift Card */
        .auto-shift-card {
            background: <?php echo $shift_gradient; ?>;
            color: white;
            padding: 24px;
            border-radius: 24px;
            margin-bottom: 24px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .auto-shift-card:active {
            transform: scale(0.98);
        }

        .auto-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .late-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--danger);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .auto-shift-icon {
            font-size: 2.5rem;
            margin-bottom: 8px;
        }

        .auto-shift-time {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .auto-shift-period {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .time-remaining-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px;
            border-radius: 40px;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .time-remaining-badge strong {
            font-weight: 800;
        }

        .tap-hint {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 12px;
        }

        /* Quota Display */
        .quota-info {
            background: var(--mb-light);
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border: 1px solid var(--mb-cream);
        }

        .quota-label {
            font-size: 0.75rem;
            color: var(--mb-gray);
            font-weight: 600;
        }

        .quota-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--mb-orange);
        }

        .quota-value.recommended {
            color: var(--success);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--mb-dark);
            font-size: 0.85rem;
        }

        .cash-input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--mb-cream);
            border-radius: 16px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            font-weight: 500;
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }

        .form-control::-webkit-inner-spin-button,
        .form-control::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--mb-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .form-control.warning {
            border-color: var(--danger);
            background: #FFF5F5;
        }

        .warning-message {
            background: #FEF2F0;
            color: #C0392B;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 3px solid var(--danger);
        }

        .text-muted {
            color: var(--mb-gray);
            font-size: 0.7rem;
            display: block;
            margin-top: 6px;
        }

        .btn-start {
            width: 100%;
            background: linear-gradient(135deg, var(--success), #229954);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-start:active {
            transform: scale(0.98);
        }

        .btn-start:disabled {
            background: var(--mb-gray);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }

        .message {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message.error {
            background: #FEF2F0;
            color: #C0392B;
            border-left: 3px solid #E74C3C;
        }

        .shift-footer {
            text-align: center;
            padding-top: 20px;
            margin-top: 16px;
            border-top: 1px solid var(--mb-cream);
        }

        .shift-footer a {
            color: var(--mb-orange);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            padding: 8px 16px;
        }

        @media (max-width: 767px) {
            body {
                padding: 16px;
            }
            .shift-body {
                padding: 20px;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .auto-shift-time {
                font-size: 1.6rem;
            }
            .shift-header {
                padding: 20px 16px;
            }
            .shift-header img {
                width: 65px;
                height: 65px;
            }
            .shift-header h2 {
                font-size: 1.4rem;
            }
            .quota-info {
                flex-direction: column;
                text-align: center;
            }
        }


    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    <div class="shift-container">
        <div class="shift-header">
            <img src="../img/logo (1)/mblogo (1).png" alt="Minute Burger">
            <h2>Start Your Shift</h2>
            <p><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></p>
        </div>

        <div class="shift-body">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class='bx bx-error-circle'></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">CURRENT TIME</div>
                    <div class="info-value large" id="currentTimeDisplay"><?php echo $current_time_12; ?></div>
                    <div class="info-sub"><?php echo $current_day; ?>, <?php echo $current_date; ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">TIME REMAINING</div>
                    <div class="info-value">
                        <?php echo sprintf("%02d", $hours_remaining); ?>h <?php echo sprintf("%02d", $minutes_remaining); ?>m
                    </div>
                    <div class="info-sub">in <?php echo $auto_shift; ?> shift period</div>
                </div>
            </div>

            <!-- Late Alert -->
            <?php if ($is_late): ?>
                <div class="late-alert">
                    <i class='bx bx-time'></i>
                    <div class="late-alert-content">
                        <div class="late-alert-title">⚠️ Late Start Notice</div>
                        <div class="late-alert-message">
                            You are starting your shift <?php echo $late_minutes; ?> minutes late.
                            Quota has been adjusted to ₱<?php echo number_format($recommended_quota, 2); ?>.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Auto-detected Shift Card -->
            <div class="auto-shift-card" onclick="document.getElementById('start_shift_form').submit();">
                <?php if ($is_late): ?>
                    <div class="late-badge">
                        <i class='bx bx-time'></i> LATE
                    </div>
                <?php endif; ?>
                <div class="auto-badge">AUTO-DETECTED</div>
                <div class="auto-shift-icon">
                    <i class='bx <?php echo $shift_icon; ?>'></i>
                </div>
                <div class="auto-shift-time"><?php echo $auto_shift; ?> SHIFT</div>
                <div class="auto-shift-period"><?php echo $auto_shift_start; ?> - <?php echo $auto_shift_end; ?></div>
                <div class="time-remaining-badge">
                    <i class='bx bx-time'></i> <strong><?php echo sprintf("%02d", $hours_remaining); ?>h <?php echo sprintf("%02d", $minutes_remaining); ?>m</strong> remaining
                </div>
                <div class="tap-hint">
                    Tap to start shift
                </div>
            </div>

            <!-- Quota Info -->
            <div class="quota-info">
                <div>
                    <span class="quota-label">Shift Quota</span>
                    <div class="quota-value">₱<?php echo number_format($shift_quota, 2); ?></div>
                </div>
                <div>
                    <span class="quota-label">Adjusted Quota</span>
                    <div class="quota-value recommended">
                        ₱<?php echo number_format($recommended_quota, 2); ?>
                        <?php if ($is_late): ?>
                            <small style="font-size: 0.65rem;">(reduced due to late start)</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form method="POST" id="start_shift_form">
                <?= csrfField() ?>
                <input type="hidden" name="shift_type" value="<?php echo $auto_shift; ?>">
                <input type="hidden" name="shift_quota" value="<?php echo $recommended_quota; ?>">
                <input type="hidden" name="start_shift" value="1">
                
                <div class="form-group">
                    <label for="opening_cash">
                        Opening Cash 
                        <?php if ($is_late): ?>
                            <span class="recommended-badge" style="background: var(--warning);">ADJUSTED</span>
                        <?php endif; ?>
                    </label>
                    <div class="cash-input-group">
                        <input type="text" class="form-control" id="opening_cash" name="opening_cash" 
                               pattern="[0-9]*\.?[0-9]*" inputmode="decimal"
                               value="<?php echo round($recommended_cash); ?>" required
                               oninput="validateOpeningCash(this.value)">
                    </div>
                    <div id="cashWarning" style="display: none;" class="warning-message">
                        <i class='bx bx-error-circle'></i>
                        <span id="warningText"></span>
                    </div>
                    <small class="text-muted">
                        Recommended: ₱<?php echo number_format($recommended_cash, 2); ?>
                        <?php if ($is_late): ?>
                            (Adjusted for <?php echo $late_minutes; ?> min late start)
                        <?php endif; ?>
                    </small>
                </div>

                <button type="submit" class="btn-start" id="startBtn">
                    <i class='bx bx-play-circle'></i> Start <?php echo $auto_shift; ?> Shift
                </button>
            </form>

            </div>
        </div>
    </div>

    <script>
        const MAX_OPENING_CASH = 100000;
        const RECOMMENDED_CASH = <?php echo round($recommended_cash); ?>;
        
        function validateOpeningCash(value) {
            let cleanValue = value.replace(/[^0-9.]/g, '');
            const decimalCount = (cleanValue.match(/\./g) || []).length;
            if (decimalCount > 1) {
                cleanValue = cleanValue.substring(0, cleanValue.lastIndexOf('.'));
            }
            
            const cashAmount = parseFloat(cleanValue) || 0;
            const warningDiv = document.getElementById('cashWarning');
            const warningText = document.getElementById('warningText');
            const cashInput = document.getElementById('opening_cash');
            const startBtn = document.getElementById('startBtn');
            
            if (cashInput.value !== cleanValue) {
                cashInput.value = cleanValue;
            }
            
            if (cashAmount < 0) {
                warningDiv.style.display = 'flex';
                warningText.innerHTML = 'Invalid amount: Opening cash cannot be negative. Please enter a valid amount.';
                cashInput.classList.add('warning');
                startBtn.disabled = true;
                return;
            }
            
            if (cashAmount > MAX_OPENING_CASH) {
                warningDiv.style.display = 'flex';
                warningText.innerHTML = 'Warning: Opening cash amount (₱' + cashAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ') exceeds ₱100,000. Please verify with your manager before proceeding.';
                cashInput.classList.add('warning');
                startBtn.disabled = true;
            } else {
                warningDiv.style.display = 'none';
                cashInput.classList.remove('warning');
                startBtn.disabled = false;
            }
        }
        
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            const timeDisplay = document.getElementById('currentTimeDisplay');
            if (timeDisplay) {
                timeDisplay.textContent = timeString;
            }
        }
        
        setInterval(updateClock, 1000);
        
        document.addEventListener('DOMContentLoaded', function() {
            const openingCash = document.getElementById('opening_cash');
            if (openingCash && !openingCash.value) {
                openingCash.value = RECOMMENDED_CASH;
            }
            validateOpeningCash(openingCash.value);
            updateClock();
        });
        
        const cashInputElement = document.getElementById('opening_cash');
        if (cashInputElement) {
            cashInputElement.addEventListener('blur', function() {
                let value = parseFloat(this.value);
                if (!isNaN(value) && value > 0) {
                    this.value = value.toFixed(2);
                } else if (value === 0) {
                    this.value = '0';
                } else if (this.value === '' || isNaN(value)) {
                    this.value = RECOMMENDED_CASH;
                }
                validateOpeningCash(this.value);
            });
        }
        
        let isSubmitting = false;
        document.getElementById('start_shift_form')?.addEventListener('submit', function(e) {
            const cashAmount = parseFloat(document.getElementById('opening_cash').value) || 0;
            
            if (cashAmount < 0) {
                e.preventDefault();
                showToastMsg('Invalid amount: Opening cash cannot be negative.', 'error');
                return;
            }
            
            if (cashAmount > MAX_OPENING_CASH) {
                e.preventDefault();
                showToastMsg('Warning: Opening cash amount exceeds ₱100,000. Please verify with your manager.', 'warning');
                return;
            }
            
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            setTimeout(() => { isSubmitting = false; }, 3000);
        });
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>