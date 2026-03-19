<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$vat = $_GET["btw"];

$btw = substr($vat, 2);
$btw = str_replace(" ", "", $btw);
$btw = str_replace(".", "", $btw);
$vies_url = "http://ec.europa.eu/taxation_customs/vies/vatResponse.html?memberStateCode=".substr( $vat, 0, 2 )."&number=". $btw ."&traderName=&traderStreet=&traderPostalCode=&traderCity=&requesterMemberStateCode=&requesterNumber=&action=check&check=Verificatie";
$content = file_get_contents($vies_url);
$pos1 = strpos($content, '<fieldset>');
$pos2 = strpos( substr($content,$pos1), '</table>');
$verschil = $pos2;
$ok = substr( $content, $pos1, $verschil ) . "</table></fieldset>";

$ret = $ok;
$ret .= "<a href='".$vies_url."' target='_blank' > Link to VIES </a>";

echo $ret;

?>