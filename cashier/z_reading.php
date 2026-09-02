<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backup_functions.php';
requirePermission('pos_access');

$page_title = 'Closing Report';
$logout_mode = isset($_GET['mode']) && $_GET['mode'] === 'logout';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Check if cashier has active shift
$stmt = $pdo->prepare("
    SELECT * FROM cashier_shifts 
    WHERE cashier_id = ? AND status = 'active'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$active_shift = $stmt->fetch();

if (!$active_shift) {
    header('Location: start_shift.php?message=' . urlencode('No active shift found. Please start your shift first.') . '&type=warning');
    exit();
}

$shift_id = $active_shift['id'];
$shift_type = $active_shift['shift_type'];
$shift_start = $active_shift['start_time'];
$opening_cash = $active_shift['opening_cash'];
$cashier_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']);

$message = '';
$message_type = '';

// Shift sales quota
$shift_quota = $active_shift['shift_quota'] ?? 10000;

// Get current shift's total sales
try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as shift_sales 
        FROM orders 
        WHERE shift_id = ?
    ");
    $stmt->execute([$shift_id]);
    $shift_sales = $stmt->fetch(PDO::FETCH_ASSOC)['shift_sales'];
    
    // Calculate percentage for progress bar
    $percentage = min(($shift_sales / $shift_quota) * 100, 100);
    
    // Determine color based on percentage
    if ($percentage < 50) {
        $progress_color = '#e74c3c'; // Red - below 50%
        $progress_color_light = '#e74c3c';
    } elseif ($percentage < 80) {
        $progress_color = '#f39c12'; // Yellow - 50-80%
        $progress_color_light = '#f39c12';
    } else {
        $progress_color = '#27ae60'; // Green - 80% and above
        $progress_color_light = '#27ae60';
    }
    
} catch (PDOException $e) {
    $shift_sales = 0;
    $percentage = 0;
    $progress_color = '#e74c3c';
    $progress_color_light = '#e74c3c';
}

// Current time for display
$current_time = date('h:i A');
$current_date = date('F j, Y');
$current_day = date('l');

// Calculate shift duration in seconds
$shift_start_ts = strtotime($shift_start);
$shift_seconds = time() - $shift_start_ts;

// Format shift duration
if ($shift_seconds < 3600) {
    $minutes = round($shift_seconds / 60);
    $shift_duration_display = $minutes . ' min' . ($minutes != 1 ? 's' : '');
} else {
    $hours = floor($shift_seconds / 3600);
    $minutes = floor(($shift_seconds % 3600) / 60);
    if ($minutes > 0) {
        $shift_duration_display = $hours . ' hr ' . $minutes . ' min';
    } else {
        $shift_duration_display = $hours . ' hr' . ($hours != 1 ? 's' : '');
    }
}

// Calculate remaining time in shift
$shift_start_datetime = new DateTime($shift_start);
$shift_end_datetime = clone $shift_start_datetime;

// Set shift end time based on shift type
if ($shift_type == 'AM') {
    // AM shift: 6 AM to 6 PM
    $shift_end_datetime->setTime(18, 0, 0);
} else {
    // PM shift: 6 PM to 6 AM next day
    $shift_end_datetime->setTime(6, 0, 0);
    $shift_end_datetime->modify('+1 day');
}

$current_datetime = new DateTime();
$remaining_interval = $current_datetime->diff($shift_end_datetime);

if ($remaining_interval->invert == 1) {
    $remaining_display = 'Shift ending soon';
} elseif ($remaining_interval->h == 0 && $remaining_interval->i > 0) {
    $remaining_display = $remaining_interval->i . ' minute' . ($remaining_interval->i != 1 ? 's' : '') . ' left';
} elseif ($remaining_interval->h > 0) {
    $remaining_display = $remaining_interval->h . ' hour' . ($remaining_interval->h != 1 ? 's' : '') . ' left';
} else {
    $remaining_display = 'Shift ended';
}

// Fetch shift sales data - all payments are cash
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COALESCE(AVG(total_amount), 0) as average_transaction,
        COALESCE(MAX(total_amount), 0) as highest_transaction,
        COALESCE(MIN(total_amount), 0) as lowest_transaction
    FROM orders
    WHERE shift_id = ?
");
$stmt->execute([$shift_id]);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total items sold
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) as total_items
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.shift_id = ?
");
$stmt->execute([$shift_id]);
$items_sold = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'];

// Get cash drops for this shift
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(drop_amount), 0) as total_drops
    FROM cash_drop_log
    WHERE shift_id = ?
");
$stmt->execute([$shift_id]);
$cash_drops = $stmt->fetch(PDO::FETCH_ASSOC)['total_drops'];

// Calculate expected cash - all sales are cash
$expected_cash = $opening_cash + $totals['total_sales'] - $cash_drops;

// Handle closing shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_shift'])) {
    requireCsrfToken();
    $actual_cash = floatval($_POST['actual_cash'] ?? 0);
    $cash_difference = $actual_cash - $expected_cash;
    $closing_notes = trim($_POST['closing_notes'] ?? '');
    
    if ($actual_cash < 0) {
        $message = 'Invalid amount: Actual cash cannot be negative.';
        $message_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO z_reading_log 
                (shift_id, closing_time, start_time, end_time, total_sales, total_transactions,
                 average_transaction, highest_transaction, lowest_transaction,
                 total_items_sold, expected_cash, actual_cash, cash_difference, cash_drop_total,
                 opening_cash, closing_cash, shift_duration_hours, generated_by)
                VALUES (?, NOW(), ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $shift_id, $shift_start, $totals['total_sales'], $totals['total_transactions'],
                $totals['average_transaction'], $totals['highest_transaction'], $totals['lowest_transaction'],
                $items_sold, $expected_cash, $actual_cash, $cash_difference, $cash_drops,
                $opening_cash, $actual_cash, $shift_seconds / 3600, $_SESSION['user_id']
            ]);
            
            $stmt = $pdo->prepare("
                UPDATE cashier_shifts 
                SET end_time = NOW(), closing_cash = ?, total_sales = ?, total_transactions = ?,
                    cash_drop_total = ?, status = 'closed', closed_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$actual_cash, $totals['total_sales'], $totals['total_transactions'], $cash_drops, $_SESSION['user_id'], $shift_id]);
            
            $pdo->commit();
            
            // Automatic database backup when a shift ends
            $redirect = '../auth/login.php';
            try {
                $backup = createFullBackup($pdo, 'shiftend');
                if (!empty($logout_mode)) {
                    // Logout mode: silent backup, no download modal (admin/backup page holds it)
                    $redirect = '../auth/login.php';
                } elseif ($backup['success'] && $backup['filename']) {
                    $token = createBackupDownloadToken($pdo, $backup['filename']);
                    if ($token) {
                        $redirect = '../auth/login.php?backup=1&reason=shiftend&file=' . urlencode($backup['filename']) . '&token=' . urlencode($token);
                    }
                }
            } catch (Exception $e) {
                error_log('Auto backup on shift end failed: ' . $e->getMessage());
            }
            
            // Clear session and log out
            $_SESSION = array();
            
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            
            header('Location: ' . $redirect);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Closing Report - Minute Burger</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <style>
        :root {
            --harvest-orange: #F37902ff;
            --chocolate: #DC6902ff;
            --apricot-cream: #EDD0A9ff;
            --copperwood: #BE6B03ff;
            --bright-lemon: #FAE51Dff;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
            --white: #ffffff;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
            min-height: 100vh;
        }

        .report-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Shift Sales Quota Progress Bar */
        .quota-container {
            background: var(--white);
            margin: 0;
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow);
            border-bottom: 1px solid var(--apricot-cream);
        }

        .quota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .quota-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .quota-title i {
            color: var(--harvest-orange);
            font-size: 1.2rem;
        }

        .quota-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .quota-stats span {
            font-weight: 700;
        }

        .quota-stats .current {
            color: var(--harvest-orange);
        }

        .quota-stats .target {
            color: var(--success);
        }

        .progress-bar-container {
            background: var(--light-gray);
            border-radius: 20px;
            height: 12px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease;
            background: linear-gradient(90deg, var(--progress-color), var(--progress-color-light));
        }

        .quota-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #666;
        }

        .quota-message {
            font-weight: 500;
        }

        .quota-message i {
            margin-right: 0.25rem;
        }

        /* Close Shift Container */
        .close-container {
            max-width: 620px;
            margin: 1.5rem auto;
        }

        .close-header-bar {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            color: white;
            padding: 1.1rem 1.5rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .close-title i {
            font-size: 1.35rem;
        }

        .close-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .close-meta .shift-badge {
            background: rgba(255,255,255,0.25);
            padding: 0.2rem 0.65rem;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
        }

        .close-meta .duration {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Cash Summary */
        .cash-summary {
            background: var(--white);
            padding: 1.1rem 1.5rem;
            box-shadow: var(--shadow);
        }

        .cash-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.3rem 0;
        }

        .cash-label {
            font-size: 0.83rem;
            color: #555;
        }

        .cash-amount {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .cash-line.expected .cash-label {
            font-size: 1rem;
            font-weight: 700;
            color: var(--harvest-orange);
        }

        .cash-line.expected .cash-amount {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--harvest-orange);
        }

        .cash-divider {
            height: 1px;
            background: var(--apricot-cream);
            margin: 0.3rem 0;
        }

        /* Quick Stats */
        .close-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--apricot-cream);
            box-shadow: var(--shadow);
        }

        .close-stat {
            background: var(--white);
            padding: 0.9rem 0.5rem;
            text-align: center;
        }

        .close-stat-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--harvest-orange);
            margin-bottom: 0.15rem;
        }

        .close-stat-label {
            font-size: 0.65rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Verification Card */
        .verification-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 0 0 16px 16px;
            box-shadow: var(--shadow);
        }

        .verification-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--bright-lemon);
        }

        .verification-header i {
            color: var(--harvest-orange);
            font-size: 1.2rem;
        }

        .cash-input {
            margin-bottom: 1.2rem;
        }

        .cash-input label {
            display: block;
            font-weight: 600;
            font-size: 0.83rem;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .cash-input-group {
            position: relative;
        }

        .cash-input-group::before {
            content: '₱';
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--harvest-orange);
            z-index: 1;
        }

        .cash-input-group input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.2rem;
            font-size: 1.3rem;
            font-weight: 700;
            border: 2px solid var(--apricot-cream);
            border-radius: 12px;
            transition: var(--transition);
            text-align: center;
        }

        .cash-input-group input:focus {
            outline: none;
            border-color: var(--harvest-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .difference-display {
            margin-top: 0.6rem;
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .difference-display.over {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .difference-display.short {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .difference-display.exact {
            background: #cce5ff;
            color: #004085;
            border-left: 4px solid var(--info);
        }

        .notes-section {
            margin-bottom: 1rem;
        }

        .notes-section textarea {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 10px;
            resize: vertical;
            font-size: 0.83rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .notes-section textarea:focus {
            outline: none;
            border-color: var(--harvest-orange);
        }

        .btn-close {
            width: 100%;
            background: linear-gradient(135deg, var(--danger), #c0392b);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(231, 76, 60, 0.4);
        }

        /* Message */
        .close-message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.82rem;
        }

        .close-message.success {
            background: #E8F8F0;
            color: #229954;
            border-left: 3px solid var(--success);
        }

        .close-message.error {
            background: #FEF2F0;
            color: #C0392B;
            border-left: 3px solid var(--danger);
        }

        /* Footer */
        .close-footer {
            text-align: center;
            padding: 1rem;
            font-size: 0.72rem;
            color: #aaa;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bright-lemon);
        }

        .title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .title h1 {
            font-size: 1.5rem;
            color: var(--dark-gray);
        }

        .badge {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            border-radius: 12px;
            padding: 1.2rem;
            color: white;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .stat-card .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .stat-card .sub-label {
            font-size: 0.6rem;
            opacity: 0.8;
            margin-top: 0.3rem;
        }

        /* Centered Card */
        .centered-card {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .centered-stat-card {
            background: linear-gradient(135deg, var(--harvest-orange), var(--chocolate));
            border-radius: 16px;
            padding: 1.5rem;
            color: white;
            text-align: center;
            transition: var(--transition);
            width: 100%;
            max-width: 400px;
        }

        .centered-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .centered-stat-card .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .centered-stat-card .value {
            font-size: 1.8rem;
            font-weight: 800;
        }

        .centered-stat-card .sub-label {
            font-size: 0.65rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        /* Cash Input */
        .cash-input {
            background: var(--light-gray);
            padding: 1.2rem;
            border-radius: 12px;
            margin: 1.5rem 0;
        }

        .cash-input label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            display: block;
            color: var(--dark-gray);
        }

        .cash-input-group {
            position: relative;
        }

        .cash-input-group::before {
            content: '₱';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            font-weight: 700;
            color: var(--harvest-orange);
        }

        .cash-input input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2rem;
            font-size: 1rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }

        .cash-input input:focus {
            outline: none;
            border-color: var(--harvest-orange);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
        }

        .difference-display {
            margin-top: 0.8rem;
            padding: 0.6rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .difference-display.over {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .difference-display.short {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .difference-display.exact {
            background: #cce5ff;
            color: #004085;
            border-left: 4px solid var(--info);
        }

        .notes-section {
            margin: 1rem 0;
        }

        .notes-section textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--apricot-cream);
            border-radius: 10px;
            resize: vertical;
            font-size: 0.85rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .btn-close {
            width: 100%;
            background: linear-gradient(135deg, var(--danger), #c0392b);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(231, 76, 60, 0.4);
        }

        /* Footer */
        .report-footer {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            font-size: 0.7rem;
            color: #666;
            margin-top: 1.5rem;
        }

        @media (max-width: 768px) {
            .report-container {
                padding: 10px;
            }
            .close-container {
                margin: 1rem auto;
            }
            .close-header-bar {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            .close-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .close-grid {
                grid-template-columns: 1fr;
            }
            .close-header-bar {
                padding: 1rem;
            }
            .close-title {
                font-size: 1rem;
            }
            .cash-input-group input {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 768px) {
            .report-container {
                padding: 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .top-bar {
                flex-direction: column;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-card .value {
                font-size: 1.2rem;
            }
            .quota-stats {
                flex-direction: column;
                gap: 0.25rem;
            }
            .centered-stat-card {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .report-content {
                padding: 1rem;
            }
            .stat-card .value {
                font-size: 1.1rem;
            }
            .centered-stat-card .value {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    <div class="report-container">

    <!-- Shift Sales Quota Progress Bar -->
    <div class="quota-container">
        <div class="quota-header">
            <div class="quota-title">
                <i class='bx bx-target'></i>
                <span>Shift Sales Quota (<?php echo $shift_type; ?> Shift)</span>
            </div>
            <div class="quota-stats">
                <span class="current">₱<?php echo number_format($shift_sales, 2); ?></span>
                <span>/</span>
                <span class="target">₱<?php echo number_format($shift_quota, 2); ?></span>
            </div>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo $percentage; ?>%; --progress-color: <?php echo $progress_color; ?>; --progress-color-light: <?php echo $progress_color_light; ?>;"></div>
        </div>
        <div class="quota-footer">
            <span class="quota-message">
                <?php
                if ($percentage >= 100) {
                    echo '<i class="bx bx-trophy"></i> Goal achieved! Great job!';
                } elseif ($percentage >= 80) {
                    echo '<i class="bx bx-check-circle"></i> Almost there! Keep going!';
                } elseif ($percentage >= 50) {
                    echo '<i class="bx bx-line-chart"></i> On track!';
                } else {
                    echo '<i class="bx bx-hourglass"></i> Need more sales to reach goal';
                }
                ?>
            </span>
            <span class="quota-percentage"><?php echo round($percentage, 1); ?>% complete</span>
        </div>
    </div>

    <div class="close-container">
        <div class="close-header-bar">
            <div class="close-title">
                <i class='bx bx-lock-alt'></i>
                <span>Close Shift</span>
            </div>
            <div class="close-meta">
                <span class="shift-badge"><?php echo $shift_type; ?> SHIFT</span>
                <span class="duration"><?php echo $shift_duration_display; ?></span>
            </div>
        </div>

        <div class="cash-summary">
            <div class="cash-line">
                <span class="cash-label">Opening Cash</span>
                <span class="cash-amount">₱<?php echo number_format($opening_cash, 2); ?></span>
            </div>
            <div class="cash-line">
                <span class="cash-label">+ Total Sales</span>
                <span class="cash-amount">₱<?php echo number_format($totals['total_sales'], 2); ?></span>
            </div>
            <div class="cash-line">
                <span class="cash-label">- Cash Drops</span>
                <span class="cash-amount">-₱<?php echo number_format($cash_drops, 2); ?></span>
            </div>
            <div class="cash-divider"></div>
            <div class="cash-line expected">
                <span class="cash-label">Expected Cash</span>
                <span class="cash-amount">₱<?php echo number_format($expected_cash, 2); ?></span>
            </div>
        </div>

        <div class="close-grid">
            <div class="close-stat">
                <span class="close-stat-value"><?php echo $totals['total_transactions']; ?></span>
                <span class="close-stat-label">Transactions</span>
            </div>
            <div class="close-stat">
                <span class="close-stat-value"><?php echo $items_sold; ?></span>
                <span class="close-stat-label">Items Sold</span>
            </div>
            <div class="close-stat">
                <span class="close-stat-value">₱<?php echo number_format($totals['average_transaction'], 2); ?></span>
                <span class="close-stat-label">Avg Transaction</span>
            </div>
        </div>

        <form method="POST">
            <?= csrfField() ?>

            <?php if (!empty($message)): ?>
                <div class="close-message <?php echo $message_type; ?>">
                    <i class='bx <?php echo $message_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="verification-card">
                <div class="verification-header">
                    <i class='bx bx-calculator'></i>
                    <span>Cash Verification</span>
                </div>

                <div class="cash-input">
                    <label for="actual_cash">Actual Cash in Drawer</label>
                    <div class="cash-input-group">
                        <input type="text" id="actual_cash" name="actual_cash" 
                               pattern="[0-9]*\.?[0-9]*" inputmode="decimal"
                               value="<?php echo $expected_cash; ?>" required
                               oninput="validateAndCalculate(this.value)">
                    </div>
                    <div id="difference_display" class="difference-display exact">
                        ₱0.00 EXACT
                    </div>
                </div>

                <div class="notes-section">
                    <textarea name="closing_notes" id="closing_notes" rows="2" 
                              placeholder="Add notes about this shift (optional)..."></textarea>
                </div>

                <button type="submit" name="close_shift" class="btn-close"
                        onclick="return askConfirm(event, 'WARNING: This will close your shift. This action cannot be undone. Are you sure?')">
                    <i class='bx bx-lock'></i> CLOSE SHIFT
                </button>
            </div>
        </form>

        <div class="close-footer">
            <i class='bx bx-receipt'></i> <?php echo $shift_type; ?> SHIFT &middot; <?php echo date('F j, Y'); ?> &middot; <?php echo $cashier_name; ?>
        </div>
    </div>
</div>

<script>
    const expectedCash = <?php echo $expected_cash; ?>;
    
    function validateAndCalculate(value) {
        let cleanValue = value.replace(/[^0-9.]/g, '');
        const decimalCount = (cleanValue.match(/\./g) || []).length;
        if (decimalCount > 1) cleanValue = cleanValue.substring(0, cleanValue.lastIndexOf('.'));
        
        const actualCash = parseFloat(cleanValue) || 0;
        const inputElement = document.getElementById('actual_cash');
        if (inputElement.value !== cleanValue) inputElement.value = cleanValue;
        
        const difference = actualCash - expectedCash;
        const displayDiv = document.getElementById('difference_display');
        
        if (difference > 0) {
            displayDiv.innerHTML = `₱${difference.toFixed(2)} OVER`;
            displayDiv.className = 'difference-display over';
        } else if (difference < 0) {
            displayDiv.innerHTML = `₱${Math.abs(difference).toFixed(2)} SHORT`;
            displayDiv.className = 'difference-display short';
        } else {
            displayDiv.innerHTML = `₱0.00 EXACT`;
            displayDiv.className = 'difference-display exact';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const cashInput = document.getElementById('actual_cash');
        if (cashInput) validateAndCalculate(cashInput.value);
    });
    
    const cashInputElement = document.getElementById('actual_cash');
    if (cashInputElement) {
        cashInputElement.addEventListener('blur', function() {
            let value = parseFloat(this.value);
            if (!isNaN(value) && value > 0) this.value = value.toFixed(2);
            else if (this.value === '' || isNaN(value)) this.value = expectedCash.toFixed(2);
            validateAndCalculate(this.value);
        });
        cashInputElement.addEventListener('keydown', function(e) {
            if (e.key === '-' || e.key === 'e') e.preventDefault();
        });
    }
    
    let isSubmitting = false;
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const cashAmount = parseFloat(document.getElementById('actual_cash').value) || 0;
        if (cashAmount < 0) {
            e.preventDefault();
            showToastMsg('Invalid amount: Actual cash cannot be negative.', 'error');
            return;
        }
        if (isSubmitting) { e.preventDefault(); return; }
        isSubmitting = true;
        setTimeout(() => { isSubmitting = false; }, 3000);
    });
</script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>