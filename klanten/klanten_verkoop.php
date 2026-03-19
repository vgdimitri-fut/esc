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

if( $_SESSION[ $session_var ]->group_id == 3 )
{
	if( $_SESSION[ $session_var ]->user_id == 29 )
	{
		$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
	}else
	{
		$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' AND cus_acma = '". $_SESSION[ $session_var ]->user_id ."' " . $sorteer);
	}
}else
{
	$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' " . $sorteer);
}

$sorteer = "";

?>

<input type='button' value='Overzicht per week' onclick="window.open('overzicht_verkoop.php','Verkoopsoverzicht','status,width=1100,height=800,scrollbars=yes'); return false;" />

<br />
<br />
<table cellpadding='0' cellspacing='0' width='100%'>
<tr style='cursor: pointer;'>
<td onclick='ajax_refresh("Verkoop","klanten/klanten_verkoop.php?sort=cus_naam<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_naam" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="350"><b>Name</b></td>
<td onclick='ajax_refresh("Verkoop","klanten/klanten_verkoop.php?sort=cus_gemeente<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_gemeente" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="350"><b>City</b></td>
<td onclick='ajax_refresh("Verkoop","klanten/klanten_verkoop.php?sort=cus_acma<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_acma" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>ACMA</b></td>
<td onclick='ajax_refresh("Verkoop","klanten/klanten_verkoop.php?sort=cus_verkoop_datum<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_verkoop_datum" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' align='right'><b>Date of sale</b></td>
</tr>

<?php

$i = 0;
while( $klant = mysqli_fetch_object($q_verkocht) )
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

$vol_klant = "";
if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
{
	$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
}else
{
	if( $klant->cus_bedrijf == "" )
	{
		$vol_klant = $klant->cus_naam;
	}else
	{
		$vol_klant = $klant->cus_bedrijf;
	}
}

echo "<td><a title='Klik hier om de klant te openen' href='http://". $_SERVER['SERVER_NAME'] ."/car/beheer/klanten.php?tab_id=1&klant_id=".$klant->cus_id ."' target='_blank' ><u>" . $vol_klant . "</u></a></td>";
echo "<td>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
echo "<td title='".$acma_arr[ $klant->cus_acma ]."'>";
$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
echo "</td>";
	
echo "<td align='right'>";

if( dateEU( $klant->cus_verkoop_datum ) != "00-00-0000" )
{
	echo dateEU( $klant->cus_verkoop_datum );
}
	
echo "</td>";
echo "</tr>";
}

?>

</table>
<br />