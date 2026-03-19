<?php
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}
require_once $path . '/inc/global.php';

$conn = connect_esc_db();

$GLOBALS["conn"] = $conn;

?>
