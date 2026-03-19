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
		$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
	}else
	{
		$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' AND cus_acma = '". $_SESSION[ $session_var ]->user_id ."' " . $sorteer);
	}
}else
{
	$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' " . $sorteer);
}

$sorteer = "";

?>
 
<br />
<br />
<table cellpadding='0' cellspacing='0' width='100%'>
<tr style='cursor: pointer;'>

<td onclick='ajax_refresh("Geen_Overeenkomst","klanten/klanten_geenov.php?sort=cus_naam<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_naam" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="350"><b>Name</b></td>
<td onclick='ajax_refresh("Geen_Overeenkomst","klanten/klanten_geenov.php?sort=cus_gemeente<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_gemeente" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' width="350"><b>City</b></td>
<td onclick='ajax_refresh("Geen_Overeenkomst","klanten/klanten_geenov.php?sort=cus_acma<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_acma" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")'><b>ACMA</b></td>
<td onclick='ajax_refresh("Geen_Overeenkomst","klanten/klanten_geenov.php?sort=cus_verkoop<?php if( isset( $_GET["sort"] ) && $_GET["sort"] == "cus_verkoop" && !isset( $_GET["order"] ) ){ echo "&order=desc"; } ?>")' align='right'><b>Agreement</b></td>
</tr>

<?php

$i = 0;
while( $klant = mysqli_fetch_object($q_verkocht) )
{
	$cus_id = $klant->cus_id;
	
	$sub = 0;
	if( $klant->uit_cus_id != 0 )
	{
        $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
        
        if( mysqli_num_rows($q_hoofdklant) > 0 )
        {
			$hoofdklant = mysqli_fetch_object($q_hoofdklant);
			$sub=1;
			$cus_id = $hoofdklant->cus_id;
        }
	}
	
	$i++;
	$kleur = $kleur_grijs;
	if( $i%2 )
	{
		$kleur = "white";
	}
		
	echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
	echo "<td onclick='gotoKlant(".$cus_id.")'>";
		
	$vol_klant = "";
	
	if( $sub == 1 )
	{
		if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
		{
			$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .")" . " <i>(extension)</i>";
		}else
		{
			if( $hoofdklant->cus_bedrijf == "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " <i>(extension)</i>";
			}else
			{
				$vol_klant = $hoofdklant->cus_bedrijf . " <i>(extension)</i>";
			}
		}
	}else
	{
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
	}
		
	echo $vol_klant;
	echo "</td>";
	
	if( $sub == 1 )
	{
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
	}else
	{
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
	}
	
    $naam_acma = "";
    
    if( !empty($klant->cus_acma) )
    {
        $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    }
    
    if($acma)
    {
        $naam_acma = $acma->naam . " " . $acma->voornaam;
    }
    
	echo "<td onclick='gotoKlant(".$cus_id.")' title='". $naam_acma ."'>";
	if($acma)
    {
        echo substr($acma->voornaam,0,2) . substr($acma->naam,0,1);
    }
	
    echo "</td>";
		
	echo "<td align='right'>";
	if( $sub == 1 )
	{
		echo "N - <span title='". $hoofdklant->cus_reden . "'>reason</span>";
	}else
	{
		echo "N - <span title='". $klant->cus_reden . "'>reason</span>";
	}
	echo "</td>";
	echo "</tr>";
}
?>
</table>