<?php 

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

if( isset( $_POST["verwerk"] ) && $_POST["verwerk"] == 'Verwerk' )
{
    require_once "../inc/fpdf.php";
	require_once "../inc/fpdi.php";
    
    $maand = array();
    $maand[1] = "januari";
    $maand[2] = "februari";
    $maand[3] = "maart";
    $maand[4] = "april";
    $maand[5] = "mei";
    $maand[6] = "juni";
    $maand[7] = "juli";
    $maand[8] = "augustus";
    $maand[9] = "september";
    $maand[10] = "oktober";
    $maand[11] = "november";
    $maand[12] = "december";
    
    $betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_POST["bet_id"]));
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
    
    $offerte_filename = 'Protest_factuur_'. $betaling->nr_intern .'.pdf';
    $offert["factuur"] = $pdf->Output($offerte_filename, "S");
    
    
    chdir("..");
    @mkdir( "lev_docs/");
	chdir( "lev_docs/");
	@mkdir( $lev->id );
	chdir( $lev->id );
	@mkdir( "protest" );
	chdir( "protest" );
	$fp = fopen($offerte_filename, 'w');
	fwrite($fp, $offert["factuur"] );
	fclose($fp);
	chdir("../../../../../");
	
	// toevoegen in de nieuwe tabel
	$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
	                                                              cf_soort, 
	                                                              cf_file) 
	                                                      VALUES('".$lev->id."',
	                                                             'protest',
	                                                             '".$offerte_filename."')") or die( mysqli_error($conn) );
                                                                 
    
	?>
	<script type='text/javascript'>
		//parent.parent.parent.opener.location = "../betalingen.php?tab_id=1";
        //parent.$.fancybox.close();
		parent.window.close();
	</script>
	<?php 
    
}

?>

<html>
<head>
<title>
Facturatie - Protestbrief 
</title>
</head>
<body>

<?php 
/*
echo "<pre>";
var_dump( $_GET );
echo "</pre>";
*/
?>

<table width='100%'>
<tr>
	<td align='center'>
		<form method='post'>
			<input type='submit' name='verwerk' id='verwerk' value='Verwerk' />
            <input type='hidden' name='bet_id' id='bet_id' value='<?php echo $_GET["bet_id"]; ?>' />
		</form>
	</td>
</tr>
</table>

<iframe src="betaling_protest_brief_toon.php?bet_id=<?php echo $_GET["bet_id"]; ?>" width="100%" height="90%">
  <p>Your browser does not support iframes.</p>
</iframe> 

</body>
</html>