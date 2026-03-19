<?php 

session_start();


//echo md5("12345") == 827ccb0eea8a706c4c34a16891f84e7b

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";


$acma_arr = array();
$q_acma = mysqli_query($conn, "SELECT * FROM kal_users");
while( $rij = mysqli_fetch_object($q_acma) )
{
	$acma_arr[ $rij->user_id ] = $rij->voornaam . " " . $rij->naam;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
Geschiedenis<?php include "inc/erp_titel.php" ?>
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />

<style type='text/css'>
.maand_overzicht td{
	padding-left:10px;
	padding-right: 10px;
}

.maand_overzicht{
	color: #404040;
	background-color: #FFEEC9;
}

.maand_td{
	color: #404040;
	background-color: #FFF2E2;
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
	
	<h1>Geschiedenis</h1>
	
<?php 

function check01($waarde)
{
	switch($waarde)
	{
		case '0' :
        case '' :
			echo "Nee";
			break;
		case '1' :
			echo "Ja";
			break;
		case '2' :
			echo "";
			break;
	}
}

$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $_GET["klant_id"]));
$geschiedenis = mysqli_query($conn, "SELECT * FROM kal_customers_log WHERE cl_cus_id = " . $_GET["klant_id"] . " ORDER BY 1 DESC ");

echo "<table class='maand_overzicht' border='1' cellpadding='0' cellspacing='0' width='100%'>";
echo "<tr><th align='center' colspan='6'>". $klant->cus_naam . "  ( ". $klant->cus_gemeente ." )" ."</th></tr>";

echo "<tr>";
echo "<td align='center'><b>ACMA</b></td>";
echo "<td align='center'><b>Veld</b></td>";
echo "<td align='center'><b>Van</b></td>";
echo "<td align='center'><b>Naar</b></td>";
echo "<td align='center' width='100'><b>Tijdstip</b></td>";
echo "</tr>";

function goedWaarde($veld, $waarde)
{
	// aanpassingen ook bijwerken in functions.php
	$kent_ons_van = array();
	$kent_ons_van[1] = "Thema";
	$kent_ons_van[2] = "Zondag";
	$kent_ons_van[3] = "Internet";
	$kent_ons_van[4] = "Solar Team";
	$kent_ons_van[5] = "Futech website";
	$kent_ons_van[6] = "Andere klant";
	$kent_ons_van[7] = "Flyer in de brievenbus";
	$kent_ons_van[8] = "Solvari";
	$kent_ons_van[9] = "Werknemer Futech";
	$kent_ons_van[10] = "Companeo";
    $kent_ons_van[11] = "Tienen actueel";
    $kent_ons_van[12] = "Provahof";
	
	$daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    $daksoorten[11] = "Gevelmontage";
	
    $verwarming = array();
    $verwarming[1] = "Radiatoren Lage Temperatuur";
    $verwarming[2] = "Radiatoren Hoge Temperatuur";
    $verwarming[3] = "Convectoren";
    $verwarming[4] = "Vloerverwarming";
    $verwarming[5] = "Warmte Pomp Lucht-lucht";
    $verwarming[6] = "Warmte Pomp Lucht-water";
    $verwarming[7] = "Warmte Pomp Water-water";
    $verwarming[8] = "Andere";
    
    $tedoen_arr = array();
    $tedoen_arr[0] = "Nog niks gebeurd";
    $tedoen_arr[1] = "Mechanisch ge&iuml;nstalleerd maar sanitair nog niks gebeurd";
    $tedoen_arr[2] = "Mechanisch klaar, sanitair begonnen, maar nog niet werkend";
    $tedoen_arr[3] = "Enkel nog glucolleiding vullen";
                    
	$ret_waarde = "";
	
	switch( $veld )
	{
	    case "cus_reden_nt_klaar" :
            echo $tedoen_arr[ $waarde ];
            break;
		case "cus_verkoop" :
			
			switch( $waarde )
			{
				case '' :
				case '0' :
					echo "Neen";
					break;
				case '1' :
					echo "ja, verkoop";
					break;
				case '2' :
					echo "ja, verhuur";
					break;
			}
			break;
        case "cus_verkoop_boi" :
        
            switch( $waarde )
            {
                case "" :
                    echo "";
                    break;
                case "1" :
                    echo "Ja";
                    break;
                case "2" :
                    echo "Neen";
                    break;
            }
            break;
		case "cus_werkdoc_check" :
		case "cus_werkdoc_klaar" :
		case "cus_opwoning" :
		case "cus_woning5j" :
        case "cus_net_voor_2014" :
		case "cus_arei" :
		case "cus_driefasig" :
		case "cus_nzn" :
        case "cus_cv" :
		case "cus_sunnybeam" :
		case "cus_klant_tevree" :
		case "cus_bouwvergunning" :
        case "cus_mailing" :
			$ret_waarde = check01($waarde); 
			break;
		case "cus_installatie_datum" :
		case "cus_installatie_datum2" :
		case "cus_installatie_datum3" :
		case "cus_installatie_datum4" :
		case "cus_nw_installatie_datum" :
		case "cus_verkoop_datum" :
		case "cus_contact" :
		case "cus_offerte_gemaakt" :
		case "cus_start_huur" :
        case "cus_einde_looptijd" :
        case "cus_dom_datum" :
			if( changeDate2EU($waarde) != "00-00-0000" )
			{
                if( substr($waarde,2,1) == "-" )
                {
                    $ret_waarde = $waarde;    
                }else
                {
                    $ret_waarde = changeDate2EU($waarde);    
                }
			}else
			{
				$ret_waarde = "";
			}
			break;
		case "cus_type_omvormers" :
			
			$tmp_omv = explode('@', $waarde);
			
			foreach( $tmp_omv as $omv )
			{
				if( !empty( $omv ) )
				{
					$omvormer = mysqli_fetch_object(mysqli_query($conn, "SELECT in_id, in_inverter FROM kal_inverters WHERE in_id = " . $omv));
					echo $omvormer->in_inverter . "<br/>";
				}
			}
			break;
		case "cus_kent_ons_van" :
			echo $kent_ons_van[ $waarde ];  
			break;
		case "cus_soort_dak" :
        case "cus_dak" :
			echo $daksoorten[ $waarde ];
			break;
		case "cus_offerte_besproken" :
			$tmp_off = explode("@", $waarde);
			foreach( $tmp_off as $off )
			{
				if( !empty( $off ) )
				{
					echo $off . "<br/>";
				}
			}
			break;
		case "cus_acma" :
        case "cus_acma_boi" :
			if( !empty( $waarde ) )
			{
				$acma = mysqli_fetch_object(mysqli_query($conn, "SELECT naam, voornaam FROM kal_users WHERE user_id = " . $waarde));
				echo $acma->naam . " " . $acma->voornaam;	
			}
			break;
		case "cus_elec" :
			if( $waarde == "1" )
			{
				echo "Andere";
			}else
			{
				echo "Dezelfde ploeg";
			}
			break;
		case "cus_gemeentepremie" :
			
			if( $waarde == 0 )
			{
				echo "Aangevraagd en nodig";
			}else
			{
				echo "Aangevraagd maar niet nodig";
			}
			
			break;
		case "Uitbreiding" :
			if( $waarde == 0 )
			{
				echo "Uitbreiding uit";
			}else
			{
				echo "Uitbreiding aan";
			}
			break;
        case "cus_verw" :
            echo $verwarming[ $waarde ];
			break;
        case "groen stroom meter" :
        
            $q = mysqli_query($conn, "SELECT * FROM kal_gsm WHERE id = '" . $waarde . "'");
        
            if( mysqli_num_rows($q) > 0 )
            {
                $gsm = mysqli_fetch_object($q);
                echo $gsm->model;    
            }
            
			break;
		default :
			$ret_waarde = $waarde;
			break;
	}

	return $ret_waarde;
}

while( $rij = mysqli_fetch_object($geschiedenis) )
{
	echo "<tr>";
	echo "<td class='maand_td' >";
    
    if( $rij->cl_wie == 99999 )
    {
        echo "Solarlogs";
    }else
    {
        if( $rij->cl_wie == 0 )
        {
            echo "Klant zelf";
        }else
        {
            echo $acma_arr[$rij->cl_wie];    
        }
    }
		
	echo "</td>";
	echo "<td class='maand_td'>";
	/*
	if( $klant->cus_verkoop == '2' )
	{
		echo "Huurbedrag";
	}else
	{
		echo $mapping_name[ $rij->cl_veld ];	
	}
	*/
	
	if( $mapping_name[ $rij->cl_veld ] == "&euro; Verkoop incl" )
	{
		if( $klant->cus_verkoop == '2' )
		{
			echo "Verhuurbedrag";
		}else
		{
			echo $mapping_name[ $rij->cl_veld ];
		}
	}else
	{
		echo $mapping_name[ $rij->cl_veld ];
	}
	
	echo "</td>";
	echo "<td class='maand_td'>";
	echo goedWaarde( $rij->cl_veld, $rij->cl_van);
	echo "</td>";
	echo "<td class='maand_td'>";
	echo htmlspecialchars_decode( goedWaarde( $rij->cl_veld, $rij->cl_naar), ENT_NOQUOTES );
	echo "</td>";
	echo "<td class='maand_td'>";
	$tmp_date = explode(" ", changeDateTime2EU( $rij->cl_datetime));
	echo str_replace(":", "u", substr($tmp_date[0], 0, 5) ); 
	echo "<br/>";
	echo $tmp_date[1];
	
	echo "</td>";
	echo "</tr>";
}

echo "</table>";

?>
	

	 

	
	
</div>

<center>
<?php 

include "inc/footer.php";

?>
</center>

</body>
</html>