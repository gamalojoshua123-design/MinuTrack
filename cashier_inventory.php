<?php
$qs = http_build_query($_GET);
header('Location: cashier/cashier_inventory.php' . ($qs !== '' ? '?' . $qs : ''));
exit;
