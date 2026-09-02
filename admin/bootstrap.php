<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/constants.php';

if (!isset($pdo)) {
    require_once __DIR__ . '/../includes/db_connect.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireAuth();

$isOwner = isOwner();
$isManager = isManager();
$user_branch_id = getCurrentBranchId();
$user_branch_name = getCurrentBranchName();
