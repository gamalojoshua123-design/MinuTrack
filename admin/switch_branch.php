<?php
require_once __DIR__ . '/bootstrap.php';
requireOwner();

$branch_id = (int)($_GET['branch_id'] ?? 0);
$redirect = $_GET['redirect'] ?? 'branches.php';

if ($branch_id > 0) {
    $stmt = $pdo->prepare("SELECT id, branch_name FROM branches WHERE id = ? AND status = 'active'");
    $stmt->execute([$branch_id]);
    $branch = $stmt->fetch();
    if ($branch) {
        $_SESSION['branch_view_id'] = $branch_id;
        $_SESSION['branch_view_name'] = $branch['branch_name'];
    }
} else {
    unset($_SESSION['branch_view_id']);
    unset($_SESSION['branch_view_name']);
}

$redirect = ltrim($redirect, '/');
if (preg_match('#^(\.\./[a-zA-Z0-9_/.-]+|[a-zA-Z0-9_/.-]+)\.php$#', $redirect)) {
    $location = $redirect;
} else {
    $location = 'branches.php';
}

header("Location: " . $location);
exit;
