<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_functions.php';
requireAuth();

$token = $_GET['token'] ?? '';
$filename = consumeBackupDownloadToken($pdo, $token);

if ($filename === false) {
    http_response_code(410);
    header('Content-Type: text/plain; charset=UTF-8');
    die('This download link is invalid, expired, or has already been used.');
}

$backup_dir = getBackupDir();
$filepath = resolveBackupFilePath($backup_dir, $filename);

if ($filepath === false || !is_file($filepath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    die('The backup file no longer exists on the server.');
}

$safe_name = basename($filepath);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $safe_name . '"');
header('Content-Length: ' . filesize($filepath));
header('X-Content-Type-Options: nosniff');
readfile($filepath);
exit;
