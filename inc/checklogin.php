<?php
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}

/*
if( isset( $_COOKIE[$session_var] ) && !isset( $_SESSION[ $session_var ] ) )
{
	$q = mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_COOKIE[$session_var]) or die( mysqli_error($conn) . " " . __LINE__ );  
	$_SESSION[ $session_var ] = mysqli_fetch_object($q);
}
*/

if( !isset( $_SESSION[ $session_var ] ) )
{
	//@session_destroy();
	
	@session_start();
	@session_unset();
	@session_destroy();
	
	?>
	<meta http-equiv="refresh" content="0;URL=index.php" />
	<?php
	die(); 
}

?>