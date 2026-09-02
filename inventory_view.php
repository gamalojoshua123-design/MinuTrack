<?php
$qs = http_build_query($_GET);
header('Location: inventory/inventory_view.php' . ($qs !== '' ? '?' . $qs : ''));
exit;
