<?php

$db_host = 'autorack.proxy.rlwy.net';
$db_port = 35604;
$db_user = 'root';
$db_pass = 'xpuXDevsgmuHWADjNXcvlRSBmJUHZhDx';
$db_name = 'railway';

$db_host = 'mysql-esc-service-ilumen-69b0.h.aivencloud.com';
$db_port = 18917;
$db_user = 'avnadmin';
$db_pass = 'AVNS_NfHsfTfTYpn972tQsAf';
$db_name = 'default';

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
mysqli_query($conn, "SET SESSION sql_mode = ''");
$GLOBALS["conn"] = $conn;

?>
