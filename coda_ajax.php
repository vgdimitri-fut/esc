<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

$coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE id = " . $_GET["coda_id"]));

if( isset( $_GET["soort"] ) )
{
    if( isset( $_GET["cus_id"] ) )
    {
        // ontkoppel klant
        echo "<b>Klant</b> ontkoppeld van <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        mysqli_query($conn, "UPDATE kal_coda SET cus_id = 0 WHERE id = " . $_GET["coda_id"]);
    }
    
    if( isset( $_GET["project"] ) )
    {
        // ontkoppel project
        echo "<b>Project</b> ontkoppeld van <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        mysqli_query($conn, "UPDATE kal_coda SET project_id = 0 WHERE id = " . $_GET["coda_id"]);
    }
    
    if( isset( $_GET["lev"] ) )
    {
        // ontkoppel project
        echo "<b>Leverancier</b> ontkoppeld van <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        mysqli_query($conn, "UPDATE kal_coda SET lev_id = 0 WHERE id = " . $_GET["coda_id"]);
    }
    
    
}else
{
    if( isset( $_GET["cus_id"] )  )
    {
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_GET["cus_id"]));
        
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
        
        echo "<b>".$naam."</b> gekoppeld aan <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        mysqli_query($conn, "UPDATE kal_coda SET cus_id = " . $_GET["cus_id"] . " WHERE id = " . $_GET["coda_id"]);
        
        if( empty( $klant->cus_iban ) )
        {
            if( !empty($coda->reknr) )
            {
                echo "<form method='post'>";
                echo "Wil u het rekening nummer ". $coda->reknr ." gebruiken?";
                echo "<input type='hidden' name='coda_id' id='coda_id' value='". $_GET["coda_id"] ."' />";
                echo "<input type='hidden' name='reknr' id='reknr' value='". $coda->reknr ."' />";
                echo "<input type='hidden' name='cus_id' id='cus_id' value='". $_GET["cus_id"] ."' />";
                echo "<input type='hidden' name='tab_id' id='tab_id' value='2' />";
                echo "<input type='submit' name='btn_reknrok_cus' id='btn_reknrok_cus' value='Rekening nummer koppelen' />";
                echo "</form>";
            }
        }
    }
    
    if( isset( $_GET["project"] ) )
    {
        $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $_GET["project"]));
        echo "<b>".$project->name."</b> gekoppeld aan <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        mysqli_query($conn, "UPDATE kal_coda SET project_id = " . $_GET["project"] . " WHERE id = " . $_GET["coda_id"]);
    }
    
    if( isset( $_GET["leverancier"] ) )
    {
        $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $_GET["leverancier"]));
        
        echo "<b>".$project->naam."</b> gekoppeld aan <b>". $coda->naam ." - " . changeDate2EU( $coda->boek_dat ) . " - &euro;" . number_format($coda->bedrag, 2, ",", " ") . "</b>"; 
        $qq = "UPDATE kal_coda SET lev_id = " . $_GET["leverancier"] . " WHERE id = " . $_GET["coda_id"];
        mysqli_query($conn, $qq);
        
        $coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE id = " . $_GET["coda_id"]));
        $q_zoek_reknr = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND klant_id = ". $_GET["leverancier"] ." AND reknr = '" . $coda->reknr . "'"));
        
        if( count( $q_zoek_reknr ) == 0 )
        {
            if( !empty($coda->reknr) )
            {
                echo "<form method='post'>";
                echo "Wil u het rekening nummer ". $coda->reknr ." gebruiken?";
                echo "<input type='hidden' name='coda_id' id='coda_id' value='". $_GET["coda_id"] ."' />";
                echo "<input type='hidden' name='reknr' id='reknr' value='". $coda->reknr ."' />";
                echo "<input type='hidden' name='levid' id='levid' value='". $_GET["leverancier"] ."' />";
                echo "<input type='hidden' name='tab_id' id='tab_id' value='2' />";
                echo "<input type='submit' name='btn_reknrok' id='btn_reknrok' value='Rekening nummer koppelen' />";
                echo "</form>";
            }
        }
    }
}

?>