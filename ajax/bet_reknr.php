<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$ret = "<table cellpadding='0' cellspacing='0' border='0' >";
$ret .= "<tr>";
$ret .= "<td>";
$ret .= "Rekeningnrs van de klant :";
$ret .= "</td>";
$ret .= "<td>";


// ophalen en tonen van de rekeningnummers
$q_rek = mysqli_query($conn, "SELECT * FROM kal_reknr WHERE klant_id = " . $_GET["lev_id"] . " AND tabel = 'kal_leveranciers'");

if( mysqli_num_rows($q_rek) == 0 )
{
    $ret .= "<span class='error'> Geen rekeningnummer gevonden.</span>";    
}else
{
    $ret .= "<select name='sel_reknr' id='sel_reknr'>";
    
    while( $rij = mysqli_fetch_object($q_rek) )
    {
        $ret .= "<option value='". $rij->id ."'>". $rij->reknr ." (". $rij->bic .")</option>";
    }
    
    $ret .= "</select>";
}
$ret .= "</td>";
$ret .= "</tr>";
$ret .= "</table>";

echo $ret;

?>