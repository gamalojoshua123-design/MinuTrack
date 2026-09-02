<?php
session_start();
$redirect = 'welcome.php';

if (isset($_SESSION['user_id'])) {
    require_once 'includes/db_connect.php';

    // Update last activity
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Check if user has an active shift
    $stmt = $pdo->prepare("
        SELECT id FROM cashier_shifts 
        WHERE cashier_id = ? AND status = 'active' 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $active_shift = $stmt->fetch();

    if ($active_shift) {
        // Has active shift — redirect to close it (keep session for shift close flow)
        header('Location: /minute1/cashier/z_reading.php?mode=logout');
        exit();
    }
}

// No active shift — normal logout
session_destroy();
header('Location: ' . $redirect);
exit();
