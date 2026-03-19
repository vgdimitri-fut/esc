<?php

$db_host = 'autorack.proxy.rlwy.net';
$db_port = 35604;
$db_user = 'root';
$db_pass = 'xpuXDevsgmuHWADjNXcvlRSBmJUHZhDx';
$db_name = 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$GLOBALS["conn"] = $conn;

?>
