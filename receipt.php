<?php
$qs = http_build_query($_GET);
header('Location: cashier/receipt.php' . ($qs !== '' ? '?' . $qs : ''));
exit;
