<?php

$db_host = 'autorack.proxy.rlwy.net';
$db_port = 35604;
$db_user = 'root';
$db_pass = 'xpuXDevsgmuHWADjNXcvlRSBmJUHZhDx';
$db_name = 'railway';

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

$GLOBALS["conn"] = $conn;

?>
