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

/*
echo "<pre>";
var_dump( $_SESSION );
echo "</pre>";
*/

sl_check_uitdienst();

// nakijken ofdat het wachtwoord moet gewijzigd worden.
if( isset( $_SESSION[ $session_var ] ) )
{
    $q_zoek1 = mysqli_query($conn, "SELECT * FROM monitoring.kal_users_pwd_changed WHERE switch = ". switch_change_pwd() ." AND user_id = " . $_SESSION[ $session_var ]->user_id) or die( mysqli_error($conn) . " " . __LINE__ );

    if( mysqli_num_rows($q_zoek1) == 0 )
    {
    	if( ! stristr($_SERVER['PHP_SELF'], "change_pwd.php") )
    	{
    		// redirect naar een andere pagina om het wachtwoord te wijzigen
    	}
    }
}

?>