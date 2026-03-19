<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

require "../inc/fpdf.php";
require "../inc/fpdi.php";

$betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_GET["bet_id"]));
$lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $betaling->lev_id));

$pdf = new FPDI();
$pdf->AddPage();
$pdf->setSourceFile('../pdf/doc_footer.pdf'); 
$tplIdx = $pdf->importPage(1); 
$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 

$pdf->SetFont('Arial', '', 11); 
$pdf->SetTextColor(0,0,0);

$pdf->Image("../images/ESC_logo.png",20,28,78,28);

/*
$pdf->Text(130, 23, "Futech Bvba");
$pdf->Text(130, 28, "Ambachtstraat 19");
$pdf->Text(130, 33, "3980 Tessenderlo");
$pdf->Text(130, 38, "BE 0808 756 108");

$pdf->Line(20,50,190,50);
*/

$pdf->SetFont('Arial', 'B', 11);
$pdf->Text(130, 33, "AANGETEKEND");

$pdf->SetFont('Arial', '', 11);
$pdf->Text(130, 38, $lev->naam );
$pdf->Text(130, 43, $lev->straat );
$pdf->Text(130, 48, $lev->postcode . " " . $lev->gemeente );

$pdf->SetFont('Arial', '', 10);
$pdf->Text(135, 85, "Tessenderlo, " . date('d') . "-" . date('m') . "-" . date('Y') );

$pdf->SetFont('Arial', 'B', 10);
$pdf->Text(20, 100, "Onderwerp : uw factuur van ". changeDate2EU($betaling->fac_datum) ." met nummer " . $betaling->fac_nr);

$pdf->SetFont('Arial', '', 10);
$pdf->Text(20, 110, "Geachte," );

$pdf->Text(20, 120, "Recent ontvingen we een factuur voor een bedrag van " . str_replace(".",",",$betaling->bedrag_incl) . " met kenmerk " . $betaling->fac_nr . ".");

$pdf->Text(20, 130, "Door middel van deze brief willen wij tegen bovenvermelde factuur in haar totaliteit protesteren.");

$pdf->SetXY( 19, 140);
$pdf->MultiCell(180, 5, html_entity_decode($betaling->reden, ENT_QUOTES), 0, 'L', false);

$pdf->Text(20, 240, "Onder voorbehoud van alle rechten verblijven wij," );
$pdf->Text(20, 250, "Hoogachtend," );
$pdf->Text(20, 255, $_SESSION["kalender_user"]->naam . " " . $_SESSION["kalender_user"]->voornaam );


$pdf->Output('Protest_factuur_'. $betaling->nr_intern .'.pdf', "I");

?>