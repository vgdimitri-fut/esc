<?php 

session_start();

include "inc/db_car.php";
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

factuur_trans( $_GET["fac_nr"], $_GET["trans_id"], $_GET["datum"],  "I", $conn_car );

?>