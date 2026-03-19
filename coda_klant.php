<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

?>

<!--
<div id="tabs-4">
-->
<h1>Klanten <a href="coda.php?tab_id=3"> <img src="images/refresh.png" alt="Vernieuw" title="Vernieuw" border="0" width="16px" height="16px" /> </a> </h1>
<?php

$dom_klanten = array();
$q_coda = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id != 0 ORDER BY cus_id, boek_dat");

$tot_bedrag = 0;
while( $rij = mysqli_fetch_object($q_coda) )
{
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop != '2' AND cus_id = " . $rij->cus_id));
    
    if( $klant )
    {
        
        $naam = "";
            
        if( $klant->cus_naam == $klant->cus_bedrijf )
        {
            $naam = $klant->cus_naam;
        }else
        {
            if( empty($klant->cus_naam) && !empty( $klant->cus_bedrijf ) )
            {
                $naam = $klant->cus_bedrijf;
            }
            
            if( !empty($klant->cus_naam) && empty( $klant->cus_bedrijf ) )
            {
                $naam = $klant->cus_naam;
            }
            
            if( !empty($klant->cus_naam) && !empty( $klant->cus_bedrijf ) && $klant->cus_bedrijf != $klant->cus_naam )
            {
                $naam = $klant->cus_naam . " / " . $klant->cus_bedrijf;
            }
        }
        
        if( !isset( $dom_klanten[ $rij->cus_id ] ) )
        {
            $dom_klanten[ $rij->cus_id ]["bedrag"] = $rij->bedrag;
            $dom_klanten[ $rij->cus_id ]["naam"] = $naam;
            $dom_klanten[ $rij->cus_id ]["coda_id"][] = $rij->id;
        }else
        {
            $dom_klanten[ $rij->cus_id ]["bedrag"] += $rij->bedrag;
            $dom_klanten[ $rij->cus_id ]["coda_id"][] = $rij->id; 
        }
        
        $tot_bedrag += $rij->bedrag;
    }
}

aasort($dom_klanten, "naam");

/*
echo "<pre>";
var_dump( $dom_klanten );
echo "</pre>";
*/

$i=0;
echo "<table cellspacing='0' width='100%' >";
foreach( $dom_klanten as $cus_id => $data )
{
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
    
    
    $i++;
    $kleur = $kleur_grijs;
	if( $i%2 )
	{
		$kleur = "white";
	}
    
    echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
    
    
    echo "<td> <img src='images/info.jpg' width='16px' height='16px' alt='Detailweergave' title='Detailweergave' onclick='showHide(". $cus_id .");' >&nbsp;";
    
    if( $_SESSION[ $session_var ]->group_id != 8 )
    {
        echo "<a title='Klik hier om de klant te openen' href='http://www.solarlogs.be/inc_erp/klanten.php?tab_id=1&klant_id=".$cus_id."' target='_blank' ><u>".$cus_id. " - " . $data["naam"] ."</u></a>";    
    }else
    {
        echo $data["naam"];
    }
    
    echo "</td><td align='right'>" . number_format( $data["bedrag"], 2, ",", " " ) . "</td></tr>";
    echo "<tr><td colspan='2' ><div id='id_". $cus_id ."' style='display:none;border:1px solid black;padding:5px;' >";
    
    echo "<table>";
    foreach( $data["coda_id"] as $coda_id )
    {
        $coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE id = " . $coda_id));
        /*
        echo "<pre>";
        var_dump( $coda );
        echo "</pre>";
        */
        
        echo "<tr>";
        echo "<td width='85' valign='top' >" . changeDate2EU( $coda->boek_dat ) . "</td>";
        echo "<td width='80' valign='top' align='right'>" . number_format( $coda->bedrag, 2, ",", "" ). "&nbsp;&euro;&nbsp;</td>";
        echo "<td>" . $coda->med2 . $coda->med3 . "</td>";
        
        echo "</tr>";
    }
    echo "</table>";
    
    
    echo "</div> </td></tr>";
}

echo "<tr><td colspan='2' align='right' ><b>Tot ontvangen : " . number_format( $tot_bedrag, 2, ",", " " ) . "</b></td></tr>";

echo "</table>";

/*
echo "<pre>";
var_dump( $dom_klanten );
echo "</pre>";
*/

?>
<!--
</div>
-->