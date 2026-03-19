<?php

$dbhost = 'carengineering.be';
$dbuser = 'car_user';
$dbpass = 'car123pwd';
$dbname = 'car_db';

// online gegevens
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, true) or die('Error connecting to mysql');
mysqli_select_db($conn, $dbname);

?>