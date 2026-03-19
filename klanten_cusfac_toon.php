<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";


customfactuur( "I", $btw_vrijstelling );

?>