<?php

session_start();
session_unset();
session_destroy();

include "inc/db.php";
include "inc/functions.php";


if( isset( $_COOKIE[$session_var] ) )
{
	// delete
	setcookie($session_var, "", time()-3600); 
}

?>
<meta http-equiv="refresh" content="0;URL=index.php" />