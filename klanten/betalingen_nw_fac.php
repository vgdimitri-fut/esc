<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$bet = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_GET['id']));
$lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $bet->lev_id));

echo "<div style='text-align:left;display:block;width:600px;'>";

echo "Betaling voor <b>". $lev->naam ."</b><br/>";
echo "<b>Bedrag incl : </b>" . $bet->bedrag_incl ."<br/>";
echo "<b>Bedrag BTW : </b>" . $bet->bedrag_btw ."<br/>";
echo "<b>BTW % : </b>" . number_format($bet->btw,0,"","") ."<br/>";
echo "<b>Factuur nr : </b>" . $bet->fac_nr ."<br/>";
echo "<b>Factuur datum : </b>" . $bet->fac_datum ."<br/>";
echo "<b>Interne nummer : </b>" . $bet->nr_intern ."<br/>";
echo "<b>Huidig factuur : </b><a href='betalingen/". $bet->scan ."' target='_blank'><u>". $bet->scan ."</u></a><br/>";
 
echo "<form method='post' name='frm_change_scan' id='frm_change_scan' enctype='multipart/form-data' >";
echo "Nieuw factuur toevoegen : <input type='file' name='nw_fac' id='nw_fac' />";
echo "<input type='submit' name='aanpassen' id='aanpassen' value='Aanpassen' />";
echo "<input type='hidden' name='bet_id' id='bet_id' value='". $bet->id ."' />";
echo "</form>";

echo "</div>";

?>