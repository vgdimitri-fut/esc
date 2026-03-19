<?php

// connectie maken met de databank
/*
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'futech_monitoring';
*/

// online gegevens

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'dimi@futech';
$dbname = 'monitoring';


$conn_mon = mysqli_connect($dbhost, $dbuser, $dbpass, true) or die('Error connecting to mysql');
mysqli_select_db($conn, $dbname);
?>
