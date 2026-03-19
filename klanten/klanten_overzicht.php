<?php 

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$sorteer = "";

if( isset( $_GET["sort"] ) && !empty($_GET["sort"]) )
{
	$sorteer = "ORDER BY " . $_GET["sort"];
}

if( isset( $_GET["order"] ) )
{
	if( $_GET["order"] == 1 )
	{
		$sorteer .= " ASC";
	}else
	{
		$sorteer .= " DESC";
	}
}

if( empty( $sorteer ) )
{
    $sorteer = " ORDER BY cus_verkoop_datum DESC";
}

$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_ilumen = '0' AND cus_verkoop = '' " . $sorteer);

?>

<table cellpadding='0' cellspacing='0' width='100%'>
<tr style='cursor: pointer;'>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_offerte_datum<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_offerte_datum" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="100"><b>Request</b></td>
    <td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_naam<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_naam" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="250"><b>Name</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_gemeente<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_gemeente" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>City</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_acma<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_acma" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>ACMA</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_contact<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_contact" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>Contact</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_offerte_besproken<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_offerte_besproken" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>Conversation</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_verkoop<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_verkoop" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>Verkoop</b></td>
	<td onclick='ajax_refresh("Overzicht","klanten/klanten_overzicht.php?sort=cus_opmerkingen<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_opmerkingen" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>Remark</b></td>
</tr>

<?php

$cus = array();

$i = 0;
while( $klant = mysqli_fetch_object($q_klanten) )
{
		$cus_id = $klant->cus_id;
		$sub = 0;
		
		$i++;
		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}

		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		echo "<td onclick='gotoKlant(".$cus_id.")'>". changeDate2EU($klant->cus_offerte_datum) ."</td>";	
		echo "<td onclick='gotoKlant(".$cus_id.")'>";

		$vol_klant = "";

		if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
		{
			$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
		}else
		{
			if( $klant->cus_bedrijf == "" )
			{
				$vol_klant = html_entity_decode($klant->cus_naam);
			}else
			{
				$vol_klant = $klant->cus_bedrijf;
			}
		}

		if( strlen($vol_klant) > 20 )
		{
			$vol_klant = "<span title='". strip_tags($vol_klant) ."' >". strip_tags(substr($vol_klant,0,20))  ."...</span>";
		}

		echo "<span title='". $klant->cus_gsm ."-". $klant->cus_tel ."' >";
		echo $vol_klant;
		echo "</span>";

		echo "</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";
		
		$naam1 = "";
        $naam2 = "";
        
        if( !empty($klant->cus_acma) )
        {
            $qq_acma = "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma;
            $q_acma = mysqli_query($conn, $qq_acma);
            $acma = mysqli_fetch_object($q_acma);
            
            $naam1 = $acma->naam . " " . $acma->voornaam;
            $naam2 = substr($acma->voornaam,0,2) . substr($acma->naam,0,1);
        }
        
		echo "<td onclick='gotoKlant(".$cus_id.")' title='". $naam1 ."'>";
		echo $naam2;
		echo "</td>";	
		
		$datum = explode("-", $klant->cus_contact);
		$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
		if( $klant->cus_contact == "00-00-0000" )
		{
			$klant->cus_contact = "";
		}
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_contact ."</td>";
				
		$bespreking = "";
		$tmp_b = explode('@', $klant->cus_offerte_besproken);
		if( count( $tmp_b ) > 1 )
		{
			foreach( $tmp_b as $b )
			{
				$tmp_b1 = explode(" ", $b);
				$bespreking .= $tmp_b1[0];
			}
		}
		
		//$klant->cus_offerte_besproken
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $bespreking ."</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $verkoop_arr[$klant->cus_verkoop]  ."</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>";

		if( !empty( $klant->cus_opmerkingen ) )
		{
			echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
		}

		echo "</td>";
		echo "</tr>";
    
}

?>

</table>