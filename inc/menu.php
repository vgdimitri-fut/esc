<?php 

session_start();

//echo md5("test");

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";


//echo md5("w1234");

// begin uitlezen van dde klanten die zich via de website hebben ingeschreven
/*	
$dbhost = 'www.futech.be';
$dbuser = 'futech';
$dbpass = 'solarmysql?321';
$dbname = 'futech';

$conn1 = mysqli_connect($dbhost, $dbuser, $dbpass) or die('Error connecting to mysql');
mysqli_select_db($conn, $dbname);

$q = mysqli_query($conn, "SELECT * FROM UserData WHERE UserId > 340");

while($rij = mysqli_fetch_object($q))
{
	// eerst zoeken, als niet gevonden dan toevoegen
	$klant = "SELECT * 
	          FROM kal_customers 
	          WHERE cus_naam = '" . $rij->UserName . "'
	            AND cus_voornaam = '" . $rij->UserFirstName . "'
	            AND cus_straat = '". $rij->UserStreet ."'
	            AND cus_nr = '" . $rij->UserStreetNr . "'
	            AND cus_gemeente = '" . $rij->UserCity . "'
	            AND cus_postcode = '" . $rij->UserPostalCode . "'
	            AND cus_email = '" . $rij->UserEmail . "'
	            AND cus_tel = '" . $rij->UserTel . "'";
	
	$q_klant = mysqli_query($conn, $klant) or die( mysqli_error($conn) );
	
	if( mysqli_num_rows($q_klant) == 0 )
	{
		// toevoegen als niet gevonden
		$q_ins = mysqli_query($conn, "INSERT INTO kal_customers(cus_naam,
		                                                cus_voornaam,
		                                                cus_straat,
		                                                cus_nr,
		                                                cus_gemeente,
		                                                cus_postcode,
		                                                cus_email,
		                                                cus_tel ) 
		                                        VALUES('". $rij->UserName ."',
		                                               '". $rij->UserFirstName ."',
		                                               '". $rij->UserStreet ."',
		                                               '". $rij->UserStreetNr ."',
		                                               '". $rij->UserCity ."',
		                                               '". $rij->UserPostalCode ."',
		                                               '". $rij->UserEmail ."',
		                                               '". $rij->UserTel ."')");
	}
	
}
*/
// begin uitlezen van dde klanten die zich via de website hebben ingeschreven




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
Algemene kalender
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />

<style type='text/css'>

UL{
	list-style-type: none;
	margin: 0;
	padding: 0;
}

LI{
	height:27px;
	width:135px;
	background-color: lightblue;
	margin-top: 10px;
	cursor: pointer;
	padding-left:25px;
	padding-top:5px;
	border:1px solid gray;
}

LI:hover{
	background-color:white;
}

</style>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24625187-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body>

<div id='pagewrapper'>
<?php include('inc/header.php'); ?>

<h1>Futech's Particuliere Klanten Overzicht</h1>

<br/><br/>
<b>Welkom <?php echo $_SESSION['kalender_user']->naam . " " . $_SESSION['kalender_user']->voornaam; ?>,</b>
<br/><br/>
<table width='100%'>
<tr>
<td valign='top'>
<!-- Klanten  -->
<ul>
	<?php
	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 3 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 )
	{
 	?>
		<li onclick='window.location="klanten.php"'> Particuliere klanten </li>
        
        <?php
        
        if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 )
	    {
        
        ?>
        
        <li onclick='window.location="klanten_in_oa.php"'>Klanten in OA</li>
        <?php
        
        }
        
        ?>
	<?php
	}else
	{
	   if( $_SESSION["kalender_user"]->group_id == 9 )
       {
        ?>
    		<li onclick='window.location="klanten_van.php"'> Particuliere klanten </li>
    	<?php        
        }else
        {
    	?>
    		<li onclick='window.location="klanten_oa.php"'> Particuliere klanten </li>
    	<?php
        } 
	}
	
	if( $_SESSION["kalender_user"]->group_id == 1 )
	{
		?>
		<li onclick='window.location="overzicht_klanten.php"'> Klanten Overzicht </li>
		<?php 	
	}
	
	if( $_SESSION["kalender_user"]->group_id != 6 &&  $_SESSION["kalender_user"]->group_id != 9 )
	{
		?>
		<li onclick='window.location="prospectie.php"'> Prospectie </li>
		<?php 	
	}
	?>
	<li onclick='window.location="ref.php"'> Referenties </li>
</ul>

	<br/><br/><br/><br/><br/><br/>
	<ul>
        <?php 
    	if( $_SESSION["kalender_user"]->group_id != 9 )
    	{
		?>
		<li onclick='window.location="verlof.php"' >Verlofaanvragen</li>
        <?php
        }
        
         
    	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 )
    	{
		?>
        <li onclick='window.location="stats.php"'>Stats &amp; Invoicing</li>
        <?php
        }
        ?>
	</ul>
	
</td>
<td valign='top'>
<!-- Planning  -->
<ul>
	<?php
	if( $_SESSION["kalender_user"]->group_id == 6 )
	{
	?>
		<li onclick='window.location="kalender_oa.php"'> Kalender </li>
	<?php
	}else{
	   if( $_SESSION["kalender_user"]->group_id == 9 )
	   {  
		?>
			<li onclick='window.location="kalender_van.php"'> Kalender </li>
		<?php
        }else
        {
        ?>
            <li onclick='window.location="kalender.php"'> Kalender </li>
        <?php
        }
        
	} 
	?>
	<?php 
	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 6 || $_SESSION["kalender_user"]->group_id == 5 )
	{
		?>
		<li onclick='window.location="planning.php"'> Planning </li>
		
		<?php 
	}
	
	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->user_id == 20 || $_SESSION["kalender_user"]->user_id == 34 )
	{
		?>
		<li onclick='window.location="controle.php"'> Controle </li>
		
		<?php 
	}
	
	if( $_SESSION["kalender_user"]->user_id == 19 || $_SESSION["kalender_user"]->user_id == 26 )
	{
		?>
		<li onclick='window.location="facturatie.php"'> Facturatie </li>
        
		<?php 
	}
    
    if( $_SESSION["kalender_user"]->user_id == 19 || $_SESSION["kalender_user"]->user_id == 20 || $_SESSION["kalender_user"]->group_id == 1 )
	{
		?>
        <li onclick='window.location="domicile.php"'> Domicili�ringen </li>
        <li onclick='window.location="vreg.php"'> VREG </li>
		<?php 
	}
    
    if( $_SESSION["kalender_user"]->user_id == 19 || $_SESSION["kalender_user"]->group_id == 26 )
	{
		?>
        <li onclick='window.location="coda.php"'> CODA </li>
		<?php 
	}
	?>
</ul>

</td>
<td valign='top'>
<!-- Voorraad  -->
<ul>
	<?php
	if( ($_SESSION["kalender_user"]->group_id != 6 && $_SESSION["kalender_user"]->group_id != 4 && $_SESSION["kalender_user"]->group_id != 9 && $_SESSION["kalender_user"]->group_id != 3) || $_SESSION["kalender_user"]->user_id == 29 )
	{
		if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id != 4 || $_SESSION["kalender_user"]->group_id != 5 )
		{
	 		?>
			<li onclick='window.location="products.php"'> Voorraadbeheer </li>
			<?php
		}
		
		
		if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id != 4 || $_SESSION["kalender_user"]->group_id != 5 || $_SESSION["kalender_user"]->group_id != 3 )
		{
			?>
			<li onclick='window.location="distri.php"'> Distributie </li>
			<?php 
		}
		
		if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id != 4 || $_SESSION["kalender_user"]->group_id != 5 )
		{
			?>
			<li onclick='window.location="barcodes.php"'> Bar - &amp; QRcodes  </li>
			<?php
		} 
	}
	?>
</ul>

</td>
<td valign='top'>
<!-- Instellingen  -->
<ul>
	<?php
	if( $_SESSION["kalender_user"]->group_id != 9 && $_SESSION["kalender_user"]->group_id != 6 && $_SESSION["kalender_user"]->group_id != 5 && $_SESSION["kalender_user"]->group_id != 3 && $_SESSION["kalender_user"]->group_id != 4 )
	{
 	?>
		<li onclick='window.location="users.php"'> Gebruikersbeheer </li>
        <li onclick='window.location="fut_grafiek.php"'> Futech.be grafiek </li>
        <li onclick='window.location="instellingen.php"'> Instellingen </li>
	<?php
	}
	?>
	
    <!--
    <li onclick='window.location="inverters.php"'> Bereken omvormers </li>
    -->
</ul>

<?php
if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 )
{
?>
    
    <ul style="margin-top: 238px;">
    	<li onclick='window.location="http://www.solarlogs.be/menu.php"'> SolarLogs </li>
    </ul>
<?php
}
?>

</td>
</tr>
</table>


</div>
<center>
<?php 

if( $_SESSION["kalender_user"]->user_id == 19 && 0 == 1 )
{
	// Initialize cURL with given url
	$url = 'http://download.bethere.co.uk/images/61859740_3c0c5dbc30_o.jpg';
	$ch = curl_init($url);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Sitepoint Examples (thread 581410; http://www.sitepoint.com/forums/showthread.php?t=581410)');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	
	set_time_limit(65);
	
	$execute = curl_exec($ch);
	$info = curl_getinfo($ch);
	
	// Time spent downloading, I think
	$time = $info['total_time']
	      - $info['namelookup_time']
	      - $info['connect_time']
	      - $info['pretransfer_time']
	      - $info['starttransfer_time']
	      - $info['redirect_time'];
	
	// Echo friendly messages
	header('Content-Type: text/plain');
	printf("Downloaded %d bytes in %0.4f seconds.\n", $info['size_download'], $time);
	printf("Which is %0.4f mbps\n", $info['size_download'] * 8 / $time / 1024 / 1024);
	printf("CURL said %0.4f mbps\n", $info['speed_download'] * 8 / 1024 / 1024);
	
	echo "\n\ncurl_getinfo() said:\n", str_repeat('-', 31 + strlen($url)), "\n";
	foreach ($info as $label => $value)
	{
		printf("%-30s %s\n", $label, $value);
	}
}


include "inc/footer.php";

?>
</center>

</body>
</html>