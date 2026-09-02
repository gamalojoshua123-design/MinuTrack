<?php
/**
 * Legacy path shim.
 *
 * Routes to the role-appropriate reports page, mirroring the logic in
 * includes/sidebar.php ($reports_url): the Owner gets the admin reports
 * dashboard, everyone else gets the branch-scoped reports page.
 */
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth.php';

$target = isOwner() ? 'admin/reports.php' : 'reports/reports.php';

$qs = http_build_query($_GET);
header('Location: ' . $target . ($qs !== '' ? '?' . $qs : ''));
exit;
