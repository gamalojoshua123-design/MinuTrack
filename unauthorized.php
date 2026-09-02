<?php
$qs = http_build_query($_GET);
header('Location: auth/unauthorized.php' . ($qs !== '' ? '?' . $qs : ''));
exit;
