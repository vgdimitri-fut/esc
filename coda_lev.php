<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

?>

<!--
<div id="tabs-6">
-->

    <h1>Leveranciers <a href="coda.php?tab_id=5"> <img src="images/refresh.png" alt="Vernieuw" title="Vernieuw" border="0" width="16px" height="16px" /> </a> </h1>
    <?php
    // PROJECTEN
    $dom_klanten = array();
    $q_coda = mysqli_query($conn, "SELECT * FROM kal_coda WHERE lev_id != 0 ORDER BY boek_dat");
    
    $tot_bedrag = 0;
    while( $rij = mysqli_fetch_object($q_coda) )
    {
        $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
        
        if( $lev )
        {
            if( !isset( $dom_klanten[ $rij->project_id ] ) )
            {
                $dom_klanten[ $rij->lev_id ]["bedrag"] = $rij->bedrag;
                $dom_klanten[ $rij->lev_id ]["naam"] = $lev->naam;
                $dom_klanten[ $rij->lev_id ]["coda_id"][] = $rij->id;
            }else
            {
                $dom_klanten[ $rij->lev_id ]["bedrag"] += $rij->bedrag;
                $dom_klanten[ $rij->lev_id ]["coda_id"][] = $rij->id; 
            }
            
            
        }
    }
    
    aasort($dom_klanten, "naam");
    
    $i=0;
    
    echo "<table cellspacing='0' width='100%' >";
    foreach( $dom_klanten as $project_id => $data )
    {
        $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $project_id));
        
        $i++;
        $kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}
        
        echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
        echo "<td> <img src='images/info.jpg' width='16px' height='16px' alt='Detailweergave' title='Detailweergave' onclick='showHidel(". $project_id .");' >&nbsp;";
        
        if( $_SESSION[ $session_var ]->group_id != 8 )
        {
            echo "<a title='Klik hier om de klant te openen' href='http://www.solarlogs.be/inc_erp/leveranciers.php?tab_id=1&klant_id=".$project_id."' target='_blank' ><u>". $data["naam"] ."</u></a>";    
        }else
        {
            echo $data["naam"];
        }
        
        $tot_bedrag += $data["bedrag"];
        
        echo "</td><td align='right'>" . number_format( $data["bedrag"], 2, ",", " " ) . "</td></tr>";
        echo "<tr><td colspan='2' ><div id='idl_". $project_id ."' style='display:none;border:1px solid black;padding:5px;' >";
        
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
    ?>
<!--    
</div>
-->