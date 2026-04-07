<?php

// railway
$db_host = 'autorack.proxy.rlwy.net';
$db_port = 35604;
$db_user = 'root';
$db_pass = 'xpuXDevsgmuHWADjNXcvlRSBmJUHZhDx';
$db_name = 'railway';

// aiven
$db_host = 'mysql-esc-service-ilumen-69b0.h.aivencloud.com';
$db_port = 18917;
$db_user = 'avnadmin';
$db_pass = 'AVNS_NfHsfTfTYpn972tQsAf';
$db_name = 'esc_db';

// aws
$db_host = 'esc-db.c72o8a2guvdp.eu-central-1.rds.amazonaws.com';
$db_port = 3306;
$db_user = 'admin';
$db_pass = 'eEohFTfwYwXes3Ar4iSE';
$db_name = 'esc_db';

$conn = @mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, $db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
mysqli_query($conn, "SET SESSION sql_mode = ''");
$GLOBALS["conn"] = $conn;

/*
$db_host = 'esc-db.c72o8a2guvdp.eu-central-1.rds.amazonaws.com';
$db_port = 3306;
$db_user = 'admin';
$db_pass = 'eEohFTfwYwXes3Ar4iSE';
$db_name = 'esc_db';

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
mysqli_query($conn, "SET SESSION sql_mode = ''");
$GLOBALS["conn"] = $conn;
*/
?>
