<?php 
$path = '/var/www/html';
if (gethostname() == 'zeropoint') {
    $path = '/var/www/html/solarlogs';
}

session_cache_expire(24*60*14);
session_set_cookie_params(3600 * 24 * 7);

session_start();

include "inc/db.php";
include "inc/functions.php";

//echo md5("FJJ123");

/*
if( isset( $_COOKIE[$session_var] ) && !isset( $_SESSION[$session_var] ) )
{
	$q = mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_COOKIE[$session_var]) or die( mysqli_error($conn) );
    $user = mysqli_fetch_object($q);
    $_SESSION[$session_var] = $user;
}
*/

if( isset( $_SESSION[ $session_var ]->user_id ) )
{
	?>
	<meta http-equiv="refresh" content="0;URL=menu.php" />
	<?php
	die();
}


$found = 0;
if( isset( $_POST["login"] ) && $_POST["login"] == "Log in" )
{
    //echo $_POST["pwd"];
    
    $q = "SELECT *
             FROM kal_users
            WHERE username = '". mysqli_real_escape_string($conn, $_POST["naam"]) ."'
              AND pwd = '". md5( $_POST["pwd"] ) ."'";
    
    //echo "<br />" . $q;
    
	$q_zoek = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q );
	
	if( mysqli_num_rows($q_zoek) > 0 )
	{
		$gebruiker = mysqli_fetch_object($q_zoek);
		$_SESSION[ $session_var ] = $gebruiker;
		
		// cookie aanmaken bij het aangemeld blijven
		if( isset( $_POST["stay"] ) )
		{
			setcookie($session_var, $gebruiker->user_id, 2147483640 );
		}
		
		?>
		<meta http-equiv="refresh" content="0;URL=menu.php" />
		<?php
		die(); 
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
<?php include "inc/erp_titel.php" ?> - LOGIN
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />


<script type='text/javascript'>

function dofocus()
{
	document.getElementById("naam").focus();
}

</script>
</head>

<body onload='dofocus();'>

<div id='pagewrapper'>
<?php
$bestand = mysqli_fetch_object(mysqli_query($conn, "SELECT bedrijf_foto FROM kal_instellingen"));
?>
<img src='images/<?php echo $bestand->bedrijf_foto; ?>'/><br/>


<br/><br/><br/><br/><br/>
<br/><br/>
<form method='post' id='frm_login' action=''>
<table cellpadding='0' cellspacing='0' border='0' id='tabel_login' width='500'>
<tr>
<td width='150'><label for='naam'> Username :</label></td>
<td align='left'> <input type='text' class='required' name='naam' id='naam' value='' /></td>
</tr>

<tr>
<td><label for='pwd'>Password :</label></td>
<td align='left'> <input type='password' class='required' name='pwd' id='pwd' value='' /></td>
</tr>

<tr>
<td>&nbsp;</td>
<td> <input type='checkbox' name='stay' id='stay' /> <label for='stay'>Stay logged in</label> </td>
</tr>
<tr>
<td>&nbsp;</td>
<td> <a href="<?php echo url(). '/esc/forget_password.php'; ?>">Forget your password?</a> </td>
</tr>
<tr>
<td align='center' colspan='2'> &nbsp; </td>
</tr>

<tr>
<td align='center' colspan='2'> <input type='submit' name='login' id='login' value='Log in' /></td>
</tr>

<?php 

if( isset( $_POST["login"] ) && $_POST["login"] == "Log in" )
{
	if( $found == 0 )
	{
		echo "<tr><td colspan='2'><br><label class='error'>Gebruikersnaam en/of wachtwoord zijn niet correct.</label></td></tr>";
	}
}

?>

</table>
</form>


</div>
<center>
<?php 
include "inc/footer.php";

?>
</center>

</body>
</html>