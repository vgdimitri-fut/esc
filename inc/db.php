<?php
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}

$conn = connect_esc_db();

$GLOBALS["conn"] = $conn;

?>
