<?php
$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
header('Location: auth/login.php' . ($query !== '' ? '?' . $query : ''));
exit;
