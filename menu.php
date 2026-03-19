<?php 

session_start();

//echo md5("c4ca4238");

// 21f69f83dcc2b2c5346f9c996bf412eb == c4ca4238

//                  a0b923820dcc509a6f75849b
// md5 solarlogs    2258157f72d958d80e775bce254bd172
// filezilla :      2258157f72d958d80e775bce254bd172

//include "inc/db_ilumen.php";
//include "inc/db_futech.php";
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";




/*
if( $_SESSION[ $session_var ]->user_id == 19 )
{
    echo "test" . $_SERVER['HOST_NAME'];
}
*/

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

//include "../cron/cron_int_opdrachten.php";


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
Menu<?php include "inc/erp_titel.php" ?>
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
    border-radius:12px;
    
<?php 

if( $_SESSION[ $session_var ]->user_id == 20 || $_SESSION[ $session_var ]->user_id == 25 )
{
    ?>
        background-color: pink;
    <?php
}else
{
    ?>
        background-color: lightblue;
    <?php
}

 ?>
	
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
<?php
$bestand = mysqli_fetch_object(mysqli_query($conn, "SELECT bedrijf_naam,bedrijf_foto FROM kal_instellingen"));

?>
<img src='images/<?php echo $bestand->bedrijf_foto; ?>'/><br/>
<div id='logout' ><a href='logout.php'>Logout</a></div>

<h1><?php echo $bestand->bedrijf_naam; ?> ERP</h1>

<br/><br/>
<b><a style="color: black;" href="http://www.solarlogs.be/ilumen" target="_blank">W</a>elkom <?php echo $_SESSION[$session_var]->naam . " " . $_SESSION[$session_var]->voornaam; ?>,</b>
<br/><br/>
<table width='100%'>
<tr>
<td valign='top'>
    <!-- Klanten  -->
    <ul>
        <li onclick='window.location="klanten.php"'> Solar Teams </li>
        <li onclick='window.location="leveranciers.php"'> Suppliers </li>
        <li onclick='window.location="sponsors.php"'> Sponsors </li>
        <li onclick='window.location="documenten.php"'> Documents </li>
    </ul>	
</td>
<td valign='top'>
<!-- Planning 
<ul>
    <li onclick='window.location="facturatie.php"'> Facturatie </li>
    <li onclick='window.location="coda.php"'> CODA </li>
    <li onclick='window.location="aanmaning_coda.php"'> CODA - Aanmaningen </li>
    <li onclick='window.location="betalingen.php"'> Betalingen <?php echo "(" . mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE exported = '0'")) . ")";  ?>  </li>

</ul>
 -->
</td>
<td valign='top'>
<!-- Voorraad  -->
<ul>
    <?php
    if( ($_SESSION[ $session_var ]->group_id != 6 && $_SESSION[ $session_var ]->group_id != 9 ) || $_SESSION[ $session_var ]->user_id == 29 || $_SESSION[ $session_var ]->user_id == 20 )
    {  
    ?>
    <li onclick='window.location="interne_opdrachten.php"'> Internal assignments </li>
    <li onclick='window.location="betalingen.php"'> Payments </li>
    <li onclick='window.location="teslaclub.php"'> Tesla club </li>
    
    <!--
    <li onclick='window.location="producten.php"'> 1 Producten </li>
    <li onclick='window.location="websites.php"'> Auto Websites </li>
    -->
    
    
    
    <?php
	}
    
    if( $_SESSION[ "esc_user" ]->group_id == 1 || $_SESSION[ "esc_user" ]->group_id == 11  )
    {
        ?>
        <li onclick='window.location="facturatie.php"'> Facturatie </li>
        <li onclick='window.location="coda.php"'> CODA </li>
        <li onclick='window.location="aanmaning_coda.php"'> CODA - Aanmaningen </li>
        <?php
        
    }
    
    ?>
</ul>
</td>
<td valign='top'>
<!-- Instellingen  -->
<ul>
		<li onclick='window.location="users.php"'> User management </li>
                <li onclick='window.location="instellingen.php"'> Settings </li>
    <!--
    <li onclick='window.location="inverters.php"'> Bereken omvormers </li>
    -->
</ul>
</td>
</tr>
</table>


</div>
<center>
<?php 

if( $_SESSION[ $session_var ]->user_id == 19 && 0 == 1 )
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
<?php
/*
$kerstkaarten = array();

if( $_SESSION[ $session_var ]->user_id == 19 )
{
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_verkoop = '2'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_verkoop = '1'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_verkoop = '3'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers as c, kal_customer_boiler as b WHERE b.cus_id = c.cus_id AND cus_int_boiler = '1' AND cus_active = '1' AND b.cus_verkoop = '1'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_10 = '1'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_offerte_besproken LIKE '%12-2012%'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            
            $kerstkaarten[ $str ] = $str;
        }
    }
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers as c, kal_customer_boiler as b WHERE b.cus_id = c.cus_id AND cus_int_boiler = '1' AND cus_active = '1' AND b.cus_off LIKE '%12-2012%'");
    
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $rij->cus_straat ) && !empty($rij->cus_nr ) && !empty($rij->cus_postcode ) && !empty($rij->cus_gemeente ) )
        {
            $str = $rij->cus_naam . ";" . $rij->cus_straat . " " . $rij->cus_nr . ";" . $rij->cus_postcode . " " . $rij->cus_gemeente;
            
            $kerstkaarten[ $str ] = $str;
        }
    }
}

echo "Aantal : " . count( $kerstkaarten );

foreach( $kerstkaarten as $index => $k )
{
    echo "<br/>" . $index;
}
*/
?>