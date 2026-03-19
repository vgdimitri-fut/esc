<?php 

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$q_upd = "UPDATE kal_betalingen SET reden = '" . htmlentities( $_POST["waarde"] , ENT_QUOTES) . "', approved = '0' WHERE id = " . $_POST["id"];
mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );

?>