<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';


function getInvoicePath($fac_id)
{
    $q_rij = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id) or die( mysqli_error($conn) . " " . __LINE__ ) ;
    $rij = mysqli_fetch_object($q_rij);
    
    $boekjaar = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_boekjaar WHERE '". $rij->cf_date ."' BETWEEN boekjaar_start AND boekjaar_einde LIMIT 1"));
    //$dir = substr( $boekjaar->boekjaar_start, 0, 4 ) . substr( $boekjaar->boekjaar_einde, 0, 4 );
    $dir = $boekjaar->boekjaar_start . " " . $boekjaar->boekjaar_einde; 
    
    if( !empty( $dir ) )
    {
        $dir .= "/";
    }
    
    $dir = $dir . $rij->cf_file;
    
    return $dir;
}

if( isset( $_POST["Maak_aanmaningen"] ) )
{
    if( $_POST["Maak_aanmaningen"] == "Maak aanmaningen" )
    {
        if( count( $_POST["klant_arr"] ) == 0 )
        {
            echo "<span class='error'>Er werden geen klanten geselecteerd.</span>";
        }else
        {
            $aanm_arr = array();
            $nog_te_vervallen = array();
            
            
            foreach( $_POST["klant_arr"] as $cus_id )
            {
                $brief_aantal = 0;
                
                if( count( $_POST["fac"] ) > 0 )
                {
                    foreach( $_POST["fac"] as $fac_id )
                    {
                        $f1 = explode("_", $fac_id);
                        $f2 = explode("-", $f1[0]);
                        
                        if( $f2[1] == $cus_id )
                        {
                            $aanm_arr[$cus_id][ $f1[1] ] = $f1[2];
                        } 
                    }
                }
                
                if( count( $_POST["fac_nogok"] ) > 0 )
                {
                
                    foreach( $_POST["fac_nogok"] as $fac_id )
                    {
                        $f1 = explode("_", $fac_id);
                        $f2 = explode("-", $f1[0]);
                        
                        if( $f2[1] == $cus_id )
                        {
                            $nog_te_vervallen[$cus_id][] = $f1[1];
                        } 
                    }
                }
            }
            
            if( count($aanm_arr) > 0 )
            {
                require "inc/fpdf.php";
                require "inc/fpdi.php";
                
                foreach( $aanm_arr as $cus_id => $facs )
                {
                    $brief_aantal = 0;
                    foreach( $facs as $fac_id => $bedrag_open )
                    {
                        $aantal_fac = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_fac_id = " . $fac_id));
                            
                        if( $brief_aantal < ($aantal_fac+1) )
                        {
                            $brief_aantal = ($aantal_fac+1);
                        }
                    }
                    
                    //echo "<br>" . $cus_id . " " . $brief_aantal;
                    
                    $tot_aantal_nog_te_vervallen = count( $nog_te_vervallen[$cus_id] );
                    
                    $pdf = new FPDI();
                	$pdf->AddPage(); 
                	$pdf->setSourceFile('pdf/werkdocument.pdf');
                	
                	// import page 1 
                	$tplIdx = $pdf->importPage(1); 
                	//use the imported page and place it at point 0,0; calculate width and height
                	//automaticallay and ajust the page size to the size of the imported page 
                	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
                	
                	// now write some text above the imported page 
                	$pdf->SetFont('Times', '', 12); 
                	$pdf->SetTextColor(0,0,0);
                	
                	//ophalen van de gegevens van de klant
                	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
                    
                	$pdf->Text(110, 50, "Aan:" );
                    $pdf->Text(110, 55, "Referte : " . maakReferte($cus_id, $conn) );
                	
                    if( $klant->cus_fac_adres == "1" )
                	{
                		$pdf->Text(110, 60, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
                		$pdf->Text(110, 65, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
                		$pdf->Text(110, 70, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
                	}else
                	{
                        $klant->cus_naam = html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES);
                		$klant->cus_bedrijf = html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES);
                		$klant->cus_straat = html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES);
                		$klant->cus_gemeente = html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES);    
                        
                        if( !empty( $klant->cus_bedrijf ) )
                        {
                            $pdf->Text(110, 60, trim($klant->cus_bedrijf) );
                        }else
                        {
                            $pdf->Text(110, 60, trim($klant->cus_naam) );
                        }
                        
                    	$pdf->Text(110, 65, trim($klant->cus_straat) . " " . trim($klant->cus_nr) );
                    	$pdf->Text(110, 70, trim($klant->cus_postcode) . " " . trim($klant->cus_gemeente) );
                    }
                    
                    $pdf->Text(1, 100, "." );
                	$pdf->Text(209, 100, "." );
                	
                	$field1 = "Tessenderlo " . date('d') . "-" . date('m') . "-" . date('Y') ;
                	$pdf->Text(130, 90, $field1 );
                	
                	$verkleinen = array();
                	$verkleinen[1] = "1ste";
                	$verkleinen[2] = "2de";
                	$verkleinen[3] = "3de";
                	$verkleinen[4] = "4de";
                	$verkleinen[5] = "5de";
                	$verkleinen[6] = "6de";
                	$verkleinen[7] = "7de";
                	$verkleinen[8] = "8de";
                	$verkleinen[9] = "9de";
                	$verkleinen[10] = "10de";
                	
                	$title = $verkleinen[$brief_aantal] . " herinnering onbetaalde factu(u)r(en)";
                	
                    if( $brief_aantal > 2 )
                    {
                        $title = "INGEBREKESTELLING - onbetaalde factu(u)r(en)";
                    }
                    
                    $pdf->Text(20, 90, $title );
                	
                	$field2 = "Beste,";
                	$pdf->Text(21, 100, $field2 );
                	
                    if( $brief_aantal < 3  )
                	{
                        $field3 = "Bij nazicht van onze boekhouding blijkt dat uw factu(u)r(en) tot op heden onbetaald bleef. De vervaldatum is overschreden.";
                    }else
                    {
                        $field3 = "Ondanks herhaaldelijk verzoek om het openstaande bedrag (zie onder) te voldoen hebben wij nog geen betaling van u mogen ontvangen. Wij zijn dus genoodzaakt u dit aangetekend schrijven te sturen.";
                    }
                	$pdf->SetXY( 20, 105 );
                	$pdf->MultiCell(160, 5, $field3, 0, 'L');
                
                    /*
                	$field4 = "Allicht bent u deze betaling uit het oog verloren.";
                	$pdf->Text(21, 125, $field4 );
                	*/
                    
                	// INIT
                	$offsetmin = 0;
                	$offset = 0;
                	
                	if( $brief_aantal < 3  )
                	{
                	   $field4 = "Allicht bent u deze betaling uit het oog verloren.";
                	   $pdf->Text(21, 125, $field4 );
                    
                		$field5 = "Mogen wij u vragen om deze betaling binnen de 5 dagen uit te voeren om op deze manier extra kosten te vermijden. Vanaf de volgende aanmaning zal de interest vermeld in onze algemene voorwaarden toegepast worden.";
                		$pdf->SetXY( 20, 132 );
                		$pdf->MultiCell(160, 5, $field5, 0, 'L');
                        
                        $field6 = "Indien de betaling reeds werd uitgevoerd, gelieve dit schrijven dan als nietig te verklaren.";
                    	$pdf->Text(21, 157-$offsetmin, $field6 );		
                        
                        $field6 = "Het huidige schrijven gebeurt onder voorbehoud van alle rechten en zonder enige nadelige erkentenis.";
                    	$pdf->Text(21, 162-$offsetmin, $field6 );    
                	}else
                	{
                        $regel = "Wij willen u erop wijzen dat de aanvaarding zonder voorbehoud van onze factuur gelijkstaat met een schuldbekentenis. Zoals in voorgaande brieven reeds werd vermeld zullen wij vanaf heden onze algemene voorwaarden toepassen. Wij behouden ons hiertoe alle rechten.";
                        $pdf->SetXY(20, 125);
                        $pdf->MultiCell(160, 5, $regel );
                        
                        $regel = "Tevens willen wij u erop attent maken dat het huidige schrijven geldt als ultieme ingebrekestelling. Wij zullen niet nalaten verder gerechtelijke stappen te ondernemen indien u in gebreke blijft om de schuld te vereffenen binnen de 5 werkdagen te rekenen vanaf heden. De kosten die hier het gevolg van zijn zullen onvermijdelijk bij u in rekening gebracht worden.";
                        $pdf->SetXY(20, 145);
                        $pdf->MultiCell(160, 5, $regel );
                        
                        //$pdf->Text(21, 130, "Dit is de laatste aanmaning alvorens de gegevens doorgestuurd worden naar de advocaat." );

                		$offsetmin = -20;
                        
                        $field6 = "Indien de betaling reeds werd uitgevoerd, gelieve dit schrijven dan als nietig te verklaren.";
                    	$pdf->Text(21, 157-$offsetmin, $field6 );		
                        
                        $field6 = "Het huidige schrijven gebeurt onder voorbehoud van alle rechten en zonder enige nadelige erkentenis.";
                    	$pdf->Text(21, 162-$offsetmin, $field6 );
                	}
                	
                	
                	
                    $pdf->SetFont('Times', 'B', 10);
                    $field6 = "Overzicht vervallen facturen :";
                	$pdf->Text(21, 172-$offsetmin, $field6 );
                    
                    $kolom1 = 21;
                    $kolom2 = 40;
                    $kolom3 = 70;
                    $kolom4 = 100;
                    
                    if( $brief_aantal == 3 || $brief_aantal > 3 )
                    {
                        $kolom4a = 135;
                        $kolom5 = 170; 
                    }else
                    {
                        $kolom5 = 150;    
                    }
                    
                    
                    $field6 = "Fac.Nr.";
                    $pdf->Text($kolom1, 177-$offsetmin, $field6 );
                    
                    $field6 = "Datum";
                    $pdf->Text($kolom2, 177-$offsetmin, $field6 );
                    
                    $field6 = "Fac.Bedrag";
                    $pdf->Text($kolom3, 177-$offsetmin, $field6 );
                    
                    $field6 = "Openstaand bedrag";
                    $pdf->Text($kolom4, 177-$offsetmin, $field6 );
                    
                    if( $brief_aantal == 3 || $brief_aantal > 3 )
                    {
                        $kolom4a = 145;
                        $kolom5 = 170;
                        
                        $field6 = "+ Intrest";
                        $pdf->Text($kolom4a, 177-$offsetmin, $field6 );
                        
                        $field6 = "# dagen openstaand";
                        $pdf->Text($kolom5, 177-$offsetmin, $field6 ); 
                    }else
                    {
                        $kolom5 = 150;
                        
                        $field6 = "# dagen openstaand";
                        $pdf->Text($kolom5, 177-$offsetmin, $field6 );    
                    }
                    
                    
                    
                    $pdf->SetFont('Times', '', 10);
                    
                    $fac_sort = array();
                    
                    $oudste_datum = 0;
                    
                    foreach( $facs as $fac_id => $bedrag_open )
                    {
                        $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                        
                        $date_ymd = explode("-", $factuur->cf_date);
                        $mk_date = mktime( 0, 0, 0, $date_ymd[1], $date_ymd[2], $date_ymd[0] );
                        
                        if( $oudste_datum < $mk_date )
                        {
                            $oudste_datum = $mk_date;
                        }
                        
                        $fac_sort[$mk_date]["fac_id"] = $fac_id;
                        $fac_sort[$mk_date]["bedrag_open"] = $bedrag_open;
                    }
                    
                    asort( $fac_sort );
                    
                    $facs1 = array();
                    
                    foreach( $fac_sort as $data )
                    {
                        $facs1[ $data["fac_id"] ] = $data["bedrag_open"];
                    }
                    
                    $filename = "aanmaning_" . replaceToNormalChars( str_replace(" ", "_", trim( $klant->cus_naam ) ) ) . "_" . date('d') . "-" . date('m') . "-" . date('Y') .'.pdf';
                    
                    $regel = 0;
                    $tot_open = 0;
                    
                    //echo "<br>" . count( $facs );
                    
                    $aantal_fac = 0;
                    
                    $tot_facs = count($facs);
                    
                    foreach( $facs as $fac_id => $bedrag_open )
                    {
                        $regel += 5;
                        $aantal_fac++;
                        $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                        
                        $brief_aantal_aanm = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_fac_id = " . $fac_id));
                        
                        $factuurnr = substr($factuur->cf_date,2,5);
                        $factuurnr = str_replace("-", "", $factuurnr);
                        $factuurnr .= "-" . str_replace(".pdf", "", $factuur->cf_file);
                        
                        $datum_ymd = explode("-", $factuur->cf_date);
                        $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                        $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                        $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                        
                        //echo "<br>" . $factuurnr . " " . changeDate2EU($factuur->cf_date) . " " . number_format($factuur->cf_bedrag, 2, ",", "" ) . " " .  number_format($bedrag_open, 2, ",", "" ) . " " . $aantal_dagen_verschil .  " ";
                        
                        $pdf->Text($kolom1, 177-$offsetmin+$regel, $factuurnr );
                        $pdf->Text($kolom2, 177-$offsetmin+$regel, changeDate2EU($factuur->cf_date) );
                        
                        $pdf->SetXY( $kolom3-10, 173.5-$offsetmin+$regel );
                		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                        
                        $pdf->SetXY( $kolom4, 173.5-$offsetmin+$regel );
                		$pdf->MultiCell(30, 5, number_format($bedrag_open, 2, ",", " " ), 0, 'R');
                        
                        if( $brief_aantal_aanm == 2 || $brief_aantal_aanm > 2 )
                        {
                            $mk_nu = mktime(0,0,0,date('m'),date('d'),date('Y'));
                            
                            $laatste_aanm = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_fac_id = " . $fac_id . " ORDER BY 1 DESC"));
                            
                            $aanm_dmy = explode("-", changeDate2EU($factuur->cf_date) );
                            $oudste_datum = mktime(0,0,0,$aanm_dmy[1],$aanm_dmy[0],$aanm_dmy[2]);
                            
                            $verschil_dagen = floor( ( $mk_nu - $oudste_datum ) / 86400 );
                            
                            $intrest10 = 150;
                            $open_bedrag_10 = $bedrag_open / 10;
                            if( $open_bedrag_10 > 150 )
                            {
                                $intrest10 = $open_bedrag_10;
                            }
                            
                            $field6 = $bedrag_open + $intrest10 + ( ($bedrag_open * 0.12 * $verschil_dagen)/365 );
                            $pdf->SetXY( $kolom4a-16, 173.5-$offsetmin+$regel );
                 		    $pdf->MultiCell(30, 5, number_format($field6, 2, ",", " " ), 0, 'R');
                            
                            $pdf->SetXY( $kolom5, 173.5-$offsetmin+$regel );
                 		    $pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                            
                            $tot_open += number_format($field6, 2, ".", "" );
                        }else
                        {
                            $pdf->SetXY( $kolom5, 173.5-$offsetmin+$regel );
                		    $pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                            
                            $tot_open += $bedrag_open;   
                        }
                        
                        $pdf->SetXY( $kolom5, 173.5-$offsetmin+$regel );
                		$pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                        
                        $q = "INSERT INTO tbl_aanmaningen(aa_cus_id, 
                                                          aa_fac_id, 
                    	                                  aa_datum, 
                    	                                  aa_bedrag, 
                    	                                  aa_factuur,
                    	                                  aa_filename) 
                    	                          VALUES('". $cus_id ."',
                                                         '". $fac_id ."',
                    	                                 '". date('d') . "-" . date('m') . "-" . date('Y') ."',
                    	                                 '". $bedrag_open. "',
                    	                                 '". $factuur->cf_file."',
                    	                                 '". $filename . "')";
                                                         
                        mysqli_query($conn, $q) or die( mysqli_error($conn) );
                        //echo "<br>" . $q;
                        
                        unset( $facs[$fac_id] );
                        
                        if( $aantal_fac == 12 )
                        {
                            break;
                        }
                    }
                    
                    if( $tot_facs < 13 )
                    {
                        $regel += 5;
                        
                        $pdf->SetFont('Times', '', 12);
                        $field7 = "Totaal openstaand bedrag : €" . number_format($tot_open, 2, ",", " ");
                    	$pdf->Text(21, 182-$offsetmin + $regel, $field7 );
                    }
                    
                    
                    // pagina 1 openstaande facturen die nog moeten vervallen
                    
                    
                    //echo count( $nog_te_vervallen[$cus_id] );
                    
                    if( $aantal_fac < 3 && count( $nog_te_vervallen[$cus_id] ) > 0 )
                    {
                        $regel += 20;
                        
                        $pdf->SetFont('Times', 'B', 10);
                        $field6 = " Overzicht openstaande facturen die nog niet vervallen zijn :";
                    	$pdf->Text(21, 172-$offsetmin+$regel, $field6 );
                        
                        $kolom1 = 21;
                        $kolom2 = 40;
                        $kolom3 = 70;
                        $kolom4 = 100;
                        $kolom5 = 150;
                        
                        $field6 = "Fac.Nr.";
                        $pdf->Text($kolom1, 177-$offsetmin+$regel, $field6 );
                        
                        $field6 = "Datum";
                        $pdf->Text($kolom2, 177-$offsetmin+$regel, $field6 );
                        
                        $field6 = "Fac.Bedrag";
                        $pdf->Text($kolom3, 177-$offsetmin+$regel, $field6 );
                        
                        $field6 = "Openstaand bedrag";
                        $pdf->Text($kolom4, 177-$offsetmin+$regel, $field6 );
                        
                        $field6 = "# dagen openstaand";
                        $pdf->Text($kolom5, 177-$offsetmin+$regel, $field6 );
                        
                        $pdf->SetFont('Times', '', 10);
                        
                        foreach( $nog_te_vervallen[$cus_id] as $tmp_id =>  $fac_id )
                        {
                            $regel += 5;
                            $aantal_fac++;
                            $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                            
                            $factuurnr = substr($factuur->cf_date,2,5);
                            $factuurnr = str_replace("-", "", $factuurnr);
                            $factuurnr .= "-" . str_replace(".pdf", "", $factuur->cf_file);
                            
                            $datum_ymd = explode("-", $factuur->cf_date);
                            $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                            $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                            $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                            
                            $pdf->Text($kolom1, 177-$offsetmin+$regel, $factuurnr );
                            $pdf->Text($kolom2, 177-$offsetmin+$regel, changeDate2EU($factuur->cf_date) );
                            
                            $pdf->SetXY( $kolom3-10, 173.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                            
                            $pdf->SetXY( $kolom4, 173.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                            
                            $pdf->SetXY( $kolom5, 173.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                            
                            unset( $nog_te_vervallen[$cus_id][$tmp_id] );
                            
                            if( ($aantal_fac+4) == 12 )
                            {
                                break;
                            }
                        }
                    }
                    
                    $offsetmin = 10;
                    
                    if( count( $facs ) > 0 )
                    {
                        $pdf->SetFont('Times', 'B', 8);
                        $pdf->Text(160, 262+$offset-$offsetmin, "Pagina 1 van 2" );
                        
                    	$pdf->AddPage(); 
                    	$pdf->setSourceFile('pdf/werkdocument.pdf');
                    	
                    	// import page 1 
                    	$tplIdx = $pdf->importPage(1); 
                    	//use the imported page and place it at point 0,0; calculate width and height
                    	//automaticallay and ajust the page size to the size of the imported page 
                    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
                    	
                        $pdf->SetFont('Times', 'B', 8);
                        $pdf->Text(160, 262+$offset-$offsetmin, "Pagina 2 van 2" );
                        
                    	// now write some text above the imported page 
                    	$pdf->SetFont('Times', 'B', 10); 
                    	$pdf->SetTextColor(0,0,0);
                        
                        $field6 = "Vervolg - Overzicht vervallen facturen :";
                    	$pdf->Text(21, 45-$offsetmin, $field6 );
                        
                        $kolom1 = 21;
                        $kolom2 = 40;
                        $kolom3 = 70;
                        $kolom4 = 100;
                        $kolom5 = 150;
                        
                        $field6 = "Fac.Nr.";
                        $pdf->Text($kolom1, 50-$offsetmin, $field6 );
                        
                        $field6 = "Datum";
                        $pdf->Text($kolom2, 50-$offsetmin, $field6 );
                        
                        $field6 = "Fac.Bedrag";
                        $pdf->Text($kolom3, 50-$offsetmin, $field6 );
                        
                        $field6 = "Openstaand bedrag";
                        $pdf->Text($kolom4, 50-$offsetmin, $field6 );
                        
                        $field6 = "# dagen openstaand";
                        $pdf->Text($kolom5, 50-$offsetmin, $field6 );
                        
                        $pdf->SetFont('Times', '', 10);
                        
                        $regel= -5;
                        foreach( $facs as $fac_id => $bedrag_open )
                        {
                            $regel += 5;
                            $aantal_fac++;
                            $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                            
                            $factuurnr = substr($factuur->cf_date,2,5);
                            $factuurnr = str_replace("-", "", $factuurnr);
                            $factuurnr .= "-" . str_replace(".pdf", "", $factuur->cf_file);
                            
                            $datum_ymd = explode("-", $factuur->cf_date);
                            $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                            $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                            $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                            
                            //echo "<br>" . $factuurnr . " " . changeDate2EU($factuur->cf_date) . " " . number_format($factuur->cf_bedrag, 2, ",", "" ) . " " .  number_format($bedrag_open, 2, ",", "" ) . " " . $aantal_dagen_verschil .  " ";
                            
                            $pdf->Text($kolom1, 55-$offsetmin+$regel, $factuurnr );
                            $pdf->Text($kolom2, 55-$offsetmin+$regel, changeDate2EU($factuur->cf_date) );
                            
                            $pdf->SetXY( $kolom3-10, 51.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                            
                            $pdf->SetXY( $kolom4, 51.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, number_format($bedrag_open, 2, ",", " " ), 0, 'R');
                            
                            $pdf->SetXY( $kolom5, 51.5-$offsetmin+$regel );
                    		$pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                            
                            $tot_open += $bedrag_open;
                            
                            $q = "INSERT INTO tbl_aanmaningen(aa_cus_id, 
                                                              aa_fac_id, 
                        	                                  aa_datum, 
                        	                                  aa_bedrag, 
                        	                                  aa_factuur,
                        	                                  aa_filename) 
                        	                          VALUES('". $cus_id ."',
                                                             '". $fac_id ."',
                        	                                 '". date('d') . "-" . date('m') . "-" . date('Y') ."',
                        	                                 '". $bedrag_open. "',
                        	                                 '". $factuur->cf_file."',
                        	                                 '". $filename . "')";
                                                             
                            mysqli_query($conn, $q) or die( mysqli_error($conn) );
                            //echo "<br>" . $q;
                        }
                        
                        if( $tot_facs > 12 )
                        {
                            $regel += 5;
                            
                            $pdf->SetFont('Times', '', 12);
                            $field7 = "Totaal openstaand bedrag : €" . number_format($tot_open, 2, ",", " ");
                        	$pdf->Text(21, 60-$offsetmin + $regel, $field7 );
                        }
                        
                        if( count( $nog_te_vervallen[$cus_id] ) > 0 )
                        {
                            $pdf->SetFont('Times', 'B', 10); 
                            
                            $field6 = "Overzicht openstaande facturen die nog niet vervallen zijn :";
                            
                            $pdf->Text(21, 70-$offsetmin + $regel, $field6 );
                            
                            $kolom1 = 21;
                            $kolom2 = 40;
                            $kolom3 = 70;
                            $kolom4 = 100;
                            $kolom5 = 150;
                            
                            $field6 = "Fac.Nr.";
                            $pdf->Text($kolom1, 75-$offsetmin+ $regel, $field6 );
                            
                            $field6 = "Datum";
                            $pdf->Text($kolom2, 75-$offsetmin+ $regel, $field6 );
                            
                            $field6 = "Fac.Bedrag";
                            $pdf->Text($kolom3, 75-$offsetmin+ $regel, $field6 );
                            
                            $field6 = "Openstaand bedrag";
                            $pdf->Text($kolom4, 75-$offsetmin+ $regel, $field6 );
                            
                            $field6 = "# dagen openstaand";
                            $pdf->Text($kolom5, 75-$offsetmin+ $regel, $field6 );
                            
                            $pdf->SetFont('Times', '', 10);
                            
                            foreach( $nog_te_vervallen[$cus_id] as $tmp_id =>  $fac_id )
                            {
                                
                                $aantal_fac++;
                                $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                                
                                $factuurnr = substr($factuur->cf_date,2,5);
                                $factuurnr = str_replace("-", "", $factuurnr);
                                $factuurnr .= "-" . str_replace(".pdf", "", $factuur->cf_file);
                                
                                $datum_ymd = explode("-", $factuur->cf_date);
                                $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                                $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                                $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                                
                                $t = 80-$offsetmin+$regel;
                                
                                $pdf->Text($kolom1, 80-$offsetmin+$regel, $factuurnr );
                                $pdf->Text($kolom2, 80-$offsetmin+$regel, changeDate2EU($factuur->cf_date) );
                                
                                $pdf->SetXY( $kolom3-10, 76.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                                
                                $pdf->SetXY( $kolom4, 76.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                                
                                $pdf->SetXY( $kolom5, 76.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                                
                                unset( $nog_te_vervallen[$cus_id][$tmp_id] );
                                $regel += 5;
                                
                                if( $t == 220 )
                                {
                                    if( count( $nog_te_vervallen[$cus_id] ) > 0 )
                                    {
                                        $pdf->Text($kolom1, 80-$offsetmin+$regel, "...er zijn nog " . count( $nog_te_vervallen[$cus_id] ) . " andere facturen die nog niet vervallen zijn." );
                                    }
                                    
                                    break;
                                }
                            }
                        }
                        
                        
                    }else
                    {
                        if( count( $nog_te_vervallen[$cus_id] ) > 0 )
                        {
                            $pdf->SetFont('Times', 'B', 8);
                            $pdf->Text(160, 262+$offset-$offsetmin, "Pagina 1 van 2" );
                            
                        	$pdf->AddPage(); 
                        	$pdf->setSourceFile('pdf/werkdocument.pdf');
                        	
                            $offsetmin -= 10;  
                            
                        	// import page 1 
                        	$tplIdx = $pdf->importPage(1); 
                        	//use the imported page and place it at point 0,0; calculate width and height
                        	//automaticallay and ajust the page size to the size of the imported page 
                        	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
                        	
                            $pdf->SetFont('Times', 'B', 8);
                            $pdf->Text(160, 262+$offset-$offsetmin, "Pagina 2 van 2" );
                            
                        	// now write some text above the imported page 
                        	$pdf->SetFont('Times', 'B', 10); 
                        	$pdf->SetTextColor(0,0,0);
                            
                            if( $tot_aantal_nog_te_vervallen == count( $nog_te_vervallen[$cus_id] ) )
                            {
                                $field6 = "Overzicht openstaande facturen die nog niet vervallen zijn :";
                            }else
                            {
                                $field6 = "Vervolg - Overzicht openstaande facturen die nog niet vervallen zijn :";    
                            }
                            
                        	$pdf->Text(21, 45-$offsetmin, $field6 );
                            
                            $kolom1 = 21;
                            $kolom2 = 40;
                            $kolom3 = 70;
                            $kolom4 = 100;
                            $kolom5 = 150;
                            
                            $field6 = "Fac.Nr.";
                            $pdf->Text($kolom1, 50-$offsetmin, $field6 );
                            
                            $field6 = "Datum";
                            $pdf->Text($kolom2, 50-$offsetmin, $field6 );
                            
                            $field6 = "Fac.Bedrag";
                            $pdf->Text($kolom3, 50-$offsetmin, $field6 );
                            
                            $field6 = "Openstaand bedrag";
                            $pdf->Text($kolom4, 50-$offsetmin, $field6 );
                            
                            $field6 = "# dagen openstaand";
                            $pdf->Text($kolom5, 50-$offsetmin, $field6 );
                            
                            $pdf->SetFont('Times', '', 10);
                            
                            $regel= -5;
                            foreach( $nog_te_vervallen[$cus_id] as $tmp_id =>  $fac_id )
                            {
                                $regel += 5;
                                $aantal_fac++;
                                $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id));
                                
                                $factuurnr = substr($factuur->cf_date,2,5);
                                $factuurnr = str_replace("-", "", $factuurnr);
                                $factuurnr .= "-" . str_replace(".pdf", "", $factuur->cf_file);
                                
                                $datum_ymd = explode("-", $factuur->cf_date);
                                $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                                $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                                $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                                
                                $pdf->Text($kolom1, 55-$offsetmin+$regel, $factuurnr );
                                $pdf->Text($kolom2, 55-$offsetmin+$regel, changeDate2EU($factuur->cf_date) );
                                
                                $pdf->SetXY( $kolom3-10, 51.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                                
                                $pdf->SetXY( $kolom4, 51.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, number_format($factuur->cf_bedrag, 2, ",", " " ), 0, 'R');
                                
                                $pdf->SetXY( $kolom5, 51.5-$offsetmin+$regel );
                        		$pdf->MultiCell(30, 5, $aantal_dagen_verschil, 0, 'R');
                                
                                unset( $nog_te_vervallen[$cus_id][$tmp_id] );
                            }
                        }
                    }
                     
                    $pdf->SetFont('Times', '', 12);
                    
                    $field8 = "Alvast bedankt,";
            		$pdf->Text(21, 252+$offset-$offsetmin, $field8 );
            		
            		$field9 = "Met vriendelijke groeten,";
            		$pdf->Text(21, 257+$offset-$offsetmin, $field9 );
            		
            		$field10 = "De boekhouding";
            		$pdf->Text(21, 262+$offset-$offsetmin, $field10 );
                    
                    $factuur = $pdf->Output('aanmaning_'. str_replace(" ", "_", trim( $klant->cus_naam ) ) .'.pdf', "S");
                    
                    /*
                    $factuur = $pdf->Output('aanmaning_'. str_replace(" ", "_", trim( $klant->cus_naam ) ) .'.pdf', "I");
                    die("einde");
                    */
                    
                    //echo "<br>" . $filename;
                	chdir( "aanmaningen/" );
                    
                    /*
                    @mkdir("test2");
                    chdir("test2");
                	*/
                    $fp1 = fopen($filename, 'w');
                	fwrite($fp1, $factuur);
                	fclose($fp1);
                	
                    chdir("../");
                    
                	// bijhouden van de gestuurde aanmaningen
                	//Niet eerst zoeken ofdat deze regel al bestaat omdat er een historiek moet zijn en het aantal aanmaningen is belangrijk.
                	//mailen naar de klant en naar admin
            		$mail = new PHPMailer();
            		
            		$mail->From     = "boekhouding@futech.be"; 
                    $mail->FromName = "ESC - boekhouding";
                    
                    $onderwerp = "Herinnering";
                    switch( $brief_aantal )
                    {
                        case 1 :
                            $onderwerp = "Herinnering";
                            break;
                        case 2 :
                            $onderwerp = "2de herinnering";
                            break;
                        case 3 :
                        case 4 :
                        case 5 :
                        case 6 :
                        case 7 :
                        case 8 :
                            $onderwerp = "Ingebrekestelling";
                            break;
                        default :
                            $onderwerp = "Herinnering";
                            break;
                    }
                    
                    //$klant->cus_email = "dimitri@futech.be";
                    
                    if( (empty( $klant->cus_email ) && empty( $klant->cus_email_verslag ) ) || $klant->cus_email == "ismael@futech.be" )
                    {
                        $mail->Subject = $onderwerp. " - KLANT HEEFT GEEN EMAILADRES";
                    }else
                    {
                        $mail->Subject = $onderwerp;
                    }
                    
                    //$mail->IsSMTP(); 
                    
                    $mail->Host     = "192.168.1.250";
                    $mail->IsHTML(false);// send as HTML
                    
                    $mail->Mailer   = "smtp"; 
                    

// Plain text body (for mail clients that cannot read HTML) 
$text_body  ="
Beste,<br/><br/>

Bij nazicht van onze boekhouding blijkt dat uw factuur tot op heden onbetaald bleef.<br/>
De vervaldatum is echter al overschreden.<br/>
Allicht bent u deze betaling uit het oog verloren. <br/><br/>

Zie bijlage voor een overzicht van uw openstaande facturen..<br/><br/>

<b>Deze mail werd u automatisch verstuurd.<br/>
Voor vragen omtrent uw openstaande facturen contacteer: stefanie@futech.be</b><br/><br/>

Alvast bedankt,<br/>
Met vriendelijke groeten,<br/>
De boekhouding<br/><br/>

INCOGNITO 007 BVBA<br/>
Predikherenstraat 5<br/>
3440 Zoutleeuw <br/>
BE 0823.471.503"; 
            
            		$body = $text_body;
            
                    $mail->Body    = $body; 
                    $mail->AltBody = $text_body;
                    
                    if( !empty( $klant->cus_email_verslag ) )
                    {
                        $tmp = explode(";", $klant->cus_email_verslag);
                        $mail->AddAddress($tmp[0], $klant->cus_naam);    
                    }else
                    {
                        if( !empty( $klant->cus_email ) )
                        {
                            $mail->AddAddress($klant->cus_email, $klant->cus_naam);
                        }
                    }
                    
                    //$mail->AddBCC("administratie@futech.be", "Futech - administratie");
                    $mail->AddBCC("dimitri@futech.be");
                    
                    
                    $mail->AddAttachment('aanmaningen/' . $filename);
                    
                    //$mail->AddAttachment('aanmaningen/test/' . $filename);
                    //$mail->AddAddress("dimitri@futech.be");
                    $mail->SMTPAutoTLS = false;
                    $ok = $mail->Send();
                    
                    if( !$ok )
                    {
                        echo "MAIL werd niet verstuurd.";
                    } 
                }
            }
        }
    }
}





?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>Aanmaningen CODA<?php include "inc/erp_titel.php" ?></title>

<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>



<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript" src="js/functions.js"></script>
<script type="text/javascript">


function showHide(cus_id)
{
    if( document.getElementById("blok_" + cus_id).style.display == "none" )
    {
        document.getElementById("blok_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("blok_" + cus_id).style.display = "none";
    }
}

function showHide1(cus_id)
{
    if( document.getElementById("id_" + cus_id).style.display == "none" )
    {
        document.getElementById("id_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("id_" + cus_id).style.display = "none";
    }
}

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});
	
</script>

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24625187-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body>

<div id='pagewrapper'><?php include('inc/header.php'); ?>
	
	<h1>Aanmaningen CODA</h1>

	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">Globaal</a></li>
            
            <?php
            
            /*
            $start = 2009;
            
            while( $start <= date('Y') )
            {
                $vjaar = $start+1;
                //echo "<br>" . "01-07-" . $start . " tot " . "30-06-" . $vjaar;
			    echo '<li><a href="#tabs-'. $start .'">'. $start . '-' . $vjaar .'</a></li>';
                 
                $start++;
            }
            */
            
            $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar ORDER BY boekjaar_start");
                
            while( $bj = mysqli_fetch_object($q_boekjaren) )
            {
                $dir = substr( $bj->boekjaar_start, 0, 4 );
                echo '<li><a href="#tabs_fac-'.$dir.'">BJ'. $dir .'</a></li>';
            }
            
            ?>
            
            <li><a href="#tabs-2">Aanmaningen</a></li>
		</ul>
		
		<div id="tabs-1">
        
        <form method="post" name="frm_sel_all" id="frm_sel_all">
            <input type="submit" name="sel_all" id="sel_all" value="Selecteer alles" />
        </form>
        
        <?php
        // al de klanten met facturen ophalen.
        
        $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_van != 'solar_verhuur' AND cf_cus_id != 2776");
        //$q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_van != 'solar_verhuur' AND cf_cus_id = 3301");
        
        //$q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = 1894");
        
        
        $lijst = array();
        $exclude_arr = array();
        
        while( $rij = mysqli_fetch_object($q_zoek_fac) )
        {
            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = 980 AND cf_file = '". $rij->cf_file ."' AND cf_date = '". $rij->cf_date ."' ");
            $gev = mysqli_num_rows($q_zoek);
            
            $q_zoek_proj = 0;
            
            if( $rij->cf_type == '0' && $q_zoek_proj == 0 )
            //if( $q_zoek_proj == 0 )
            {
                $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $rij->cf_cus_id . " ORDER BY cus_naam"));
                
                if( $klant )
                {
                    //if( $klant->cus_active == '1' && $gev != 1 )
                    if( $klant->cus_active == '1' )
                    {
                        if( !empty( $klant->cus_naam ) )
                        {
                            $naam = $klant->cus_naam;
                        }else
                        {
                            $naam = $klant->cus_bedrijf;
                        }
                        
                        $lijst[ ucfirst( $naam ) ] = $rij->cf_cus_id;
                    }
                }
            }else
            {
                $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $rij->cf_cus_id));
                
                if( $project )
                {
                    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $project->cus_id));
                    
                    if( $gev != 1 )
                    {
                        if( !empty( $klant->cus_id ) )
                        {
                            $lijst[ ucfirst( $klant->cus_naam ) ] = "p_" . $klant->cus_id;
                        }
                    }
                }
                    
                $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $rij->cf_cus_id));
                
                if( $project )
                {
                    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $project->cus_id));
                    
                    if( $gev != 1 )
                    {
                        if( !empty( $klant->cus_id ) )
                        {
                            $lijst[ ucfirst( $klant->cus_naam ) ] = "p_" . $klant->cus_id;
                        }
                    }
                }
            }
        }
        
        
        // lijst is alfabetisch
        ksort( $lijst );
        
        echo "<form method='post' name='frm_aanm' id='frm_aanm'>";
        echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>";
        echo "<tr>";
        echo "<td width='20'></td>";
        echo "<td width='400'><strong>Naam</strong></td>";
        echo "<td width='150' align='right'><strong>Tot. fac. bedrag</strong></td>";
        echo "<td width='150' align='right'><strong>Tot. ontv. bedrag</strong></td>";
        echo "<td width='120' align='right'><strong>Tot. CN</strong></td>";
        echo "<td align='right'><strong>Verschil</strong></td>";
        echo "</tr>";
        echo "</table>";
        
        $i=0;
        
        $tot_fac = 0;
        $tot_ont = 0;
        $tot_cn = 0;
        $tot_verschil = 0;
        
        $aant_glob = 0;
        
        foreach( $lijst as $naam => $cus_id )
        {
            $bedrag = 0;
            $betalingen = 0;
            $cn = 0;
            $p_cus_id = 0;
            $project = 0;
            $cus_ori = $cus_id;
            
            if( substr($cus_id,0,2) == "p_" )
            {
                $p_cus_id = substr($cus_id,2);
                $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $p_cus_id));
                
                $cus_id = $project->project_id;
                $project = 1;
            }
            
            $klant = "";
            if( !stristr($cus_ori, "p_") )
            {
                $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_ori ." AND cus_verkoop != '0'"));
            }
            
            $q_zoek_bet = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id = " . str_replace("p_", "", $cus_ori));
            
            while( $bet_coda = mysqli_fetch_object($q_zoek_bet) )
            {
                if( !empty( $klant ) )
                {
                    //echo "<br/>ok " . maakReferte($klant->cus_id);
                    
                    $ref = maakReferte($klant->cus_id, $conn);
                    
                    if( $bet_coda->bedrag != $klant->cus_ont_huur )
                    {
                        if( ( !stristr($bet_coda->med3, $ref ) && !stristr($bet_coda->med2, $ref ) && !stristr($bet_coda->ref_cl, $ref ) )  )
                        {
                            //echo "<br>A" . !stristr($bet_coda->med3, $ref ) ."B" . !stristr($bet_coda->med2, $ref ) ."C" . !stristr($bet_coda->ref_cl, $ref ) ." " . $bet_coda->bedrag;
                            $betalingen += $bet_coda->bedrag;
                        }
                    }
                }else
                {
                    $betalingen += $bet_coda->bedrag;    
                }
            }
            
            if( $project == 1 )
            {
                // cus_id = project_id
                $qq_fac = "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = " . $cus_id . " AND cf_type = '1'";
                $q_fac = mysqli_query($conn, $qq_fac) or die( mysqli_error($conn) . " " . $qq_fac . " naam : ". $project->name ." cus_ori : ". $cus_ori . " " . __LINE__ );
                
                while( $fac = mysqli_fetch_object($q_fac) )
                {
                    $bedrag += (float)$fac->cf_bedrag;
                }
                
                if( $cus_id != str_replace("p_", "", $cus_ori) )
                {
                    $q_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = " . str_replace("p_", "", $cus_ori) . " AND cf_type = '1'") or die( mysqli_error($conn) . " " . __LINE__ );
                    
                    while( $fac = mysqli_fetch_object($q_fac) )
                    {
                        $bedrag += (float)$fac->cf_bedrag;
                    }
                }
                
                //
                
                $q_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_van != 'solar_verhuur' AND cf_cus_id = " . $p_cus_id . " AND cf_type = '0'") or die( mysqli_error($conn) . " " . __LINE__ );
                
                while( $fac = mysqli_fetch_object($q_fac) )
                {
                    $bedrag += (float)$fac->cf_bedrag;
                }
                
                //
            }else
            {
                $q_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_van != 'solar_verhuur' AND cf_cus_id = " . $cus_id . " AND cf_type = '0'") or die( mysqli_error($conn) . " " . __LINE__ );
                
                while( $fac = mysqli_fetch_object($q_fac) )
                {
                    $bedrag += (float)$fac->cf_bedrag;
                }
            }
            
            $q_zoek_cn = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . str_replace("p_", "", $cus_ori) . " AND cf_soort = 'creditnota' ");
            
            while( $bet_cn = mysqli_fetch_object($q_zoek_cn) )
            {
                $cn += $bet_cn->cf_bedrag;
            }

            $verschil1 = (float)$betalingen + (float)$cn;
            $verschil = number_format($bedrag,2,".","") - number_format($verschil1,2,".","");
            
            $verschil_ex = explode(".", $verschil);
            
            /*
            if( $cus_id == 1381 )
            {
                echo "bumba";
            }
            */
            
            if( $verschil_ex[0] != 0 && $verschil_ex[0] != 1 )
            {
                $i++;
                $kleur = $kleur_grijs;
        		if( $i%2 )
        		{
        			$kleur = "white";
        		}
                
                $cus_id1 = $cus_id;
                if( $p_cus_id != 0 )
                {
                    $cus_id1 = $p_cus_id;
                }
                
                echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>";
                echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                echo "<td width='40'> <img src='images/info.jpg' width='16' height='16' onclick='showHide(". $cus_id1 .");' />";
                
                if( $verschil > 0 )
                {
                    $chk = "";
                    
                    if( isset( $_POST["sel_all"] ) )
                    {
                        $chk = " checked='checked' ";
                    }
                    
                    if( $_SESSION[$session_var]->group_id != 8 )
                    {
                        echo "<input type='checkbox' ". $chk ." name='klant_arr[]' id='klant_". $cus_id1 ."' value='". $cus_id1 ."' />";
                    }
                }
                
                echo "</td>";
                echo "<td width='400'>";
                
                if( $_SESSION[$session_var]->group_id != 8 )
                {
                    echo "<a title='Klik hier om de klant te openen' href='klanten.php?tab_id=1&klant_id=".$cus_id1 ."' target='_blank' ><u>" . $naam . "</u></a>";    
                }else
                {
                    echo $naam;
                }
                
                echo "</td>";
                echo "<td width='150' align='right'>" . number_format($bedrag,2,","," ") . "</td>";
                echo "<td width='150' align='right'>" . number_format($betalingen,2,","," ") . "</td>"; 
                echo "<td width='120' align='right'>" . number_format($cn,2,","," ") . "</td>";
                
                $switcher = ( $verschil > 0 ) ? "error" : "correct";
                
                echo "<td align='right' class='". $switcher ."'>" . number_format( $verschil, 2,","," ") . "</td>";     
                echo "</tr>";
                echo "</table>";
                
                if( isset( $_POST["cus_id1"] ) )
                {
                    if( $_POST["cus_id1"] == $cus_id1 )
                    {
                        echo "<div id='blok_". $cus_id1 ."' style='display:block;' >";
                    }else
                    {
                        echo "<div id='blok_". $cus_id1 ."' style='display:none;' >";
                    }
                }else
                {
                    if( isset( $_POST["sel_all"] ) )
                    {
                        echo "<div id='blok_". $cus_id1 ."' style='display:block;' >";
                    }else
                    {
                        echo "<div id='blok_". $cus_id1 ."' style='display:none;' >";
                    }
                }
                
                echo "<table>";
                echo "<tr>";
                echo "<td>";
                
                /* */
                
                $data_grafiek = array();
                $factuur_arr = array();
                
                // ophalen van al de facturen uit solarlogs kalender
                $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $cus_id1 . " AND cf_soort = 'factuur' AND cf_van != 'solar_verhuur' AND cf_type = '0' ");
                
                $maak_unique = 0;
                $tot_bedrag = 0;
                if( mysqli_num_rows($q_zoek_fac) > 0 )
                {
                    while( $rij = mysqli_fetch_object($q_zoek_fac) )
                    {
                        $maak_unique++;
                        $tot_bedrag += $rij->cf_bedrag;
                        
                        $date_ymd = explode("-", $rij->cf_date );
                        $stamp = mktime(0,0,0,$date_ymd[1],$date_ymd[2],$date_ymd[0]);
                        
                        $data_grafiek[$stamp . "_" . $maak_unique] = array( "bedrag" => number_format($rij->cf_bedrag,2,".", ""),
                                                       "operant" => "+" );
                        $factuur_arr[] = $rij->cf_id;
                    }
                }
                
                $cn_arr = array();
                $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $cus_id1 . " AND cf_soort = 'creditnota' ");
                
                if( mysqli_num_rows($q_zoek_fac) > 0 )
                {
                    while( $rij = mysqli_fetch_object($q_zoek_fac) )
                    {
                        $maak_unique++;
                        
                        $tot_bedrag -= $rij->cf_bedrag;
                        
                        $date_ymd = explode("-", $rij->cf_date );
                        $stamp = mktime(0,0,0,$date_ymd[1],$date_ymd[2],$date_ymd[0]);
                        
                        $data_grafiek[$stamp . "_" . $maak_unique] = array( "bedrag" => number_format($rij->cf_bedrag,2,".", ""),
                                                       "operant" => "-" );
                                                       
                        $cn_arr[] = array( "bedrag" => $rij->cf_bedrag,
                                           "datum" => $rij->cf_date );
                        
                        //echo "<br>" . $rij->cf_bedrag . " - " . $rij->cf_date;
                    }
                }
                
                $qq_coda = "SELECT * FROM kal_coda WHERE cus_id = " . $cus_id1 . " ORDER BY boek_dat ASC ";
                $q_zoek_coda = mysqli_query($conn, $qq_coda) or die( mysqli_error($conn) . " " . __LINE__ );
                
                //echo "SELECT * FROM kal_coda WHERE cus_id = " . $cus->cus_id . " ORDER BY boek_dat ASC ";
                
                if( mysqli_num_rows($q_zoek_coda) > 0 )
                {
                    while( $rij = mysqli_fetch_object($q_zoek_coda) )
                    {
                        $maak_unique++;
                        
                        $tot_bedrag -= $rij->bedrag;
                        
                        $date_ymd = explode("-", $rij->boek_dat );
                        $stamp = mktime(0,0,0,$date_ymd[1],$date_ymd[2],$date_ymd[0]);
                        
                        
                        $data_grafiek[$stamp . "_" . $maak_unique] = array( "bedrag" => $rij->bedrag,
                                                       "operant" => "-" );
                    }
                }
                
                $kklant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id1));
                
                // al de facturen overlopen
                
                $toon_title = 0;
                $tot_nog_te_betalen = 0;
                
                
                
                foreach( $factuur_arr as $fac_id )
                {
                    $q_rij = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id) or die( mysqli_error($conn) . " " . __LINE__ ) ;
                    $rij = mysqli_fetch_object($q_rij);
                    
                    $boekjaar = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_boekjaar WHERE '". $rij->cf_date ."' BETWEEN boekjaar_start AND boekjaar_einde LIMIT 1"));
                    $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde; 
                    /*
                    $fac_date = explode("-", $rij->cf_date);
                    $mk_fac_date = mktime(0,0,0,$fac_date[1],$fac_date[2],$fac_date[0]);
                    $begin_nw_bj = mktime(0,0,0,7,1,2012);
                    
                    //echo "<br>" . $mk_fac_date . "<" . $begin_nw_bj;
                    $dir = "";
            		if( $mk_fac_date < $begin_nw_bj )
                    {
                        $dir = "";  
                    }else
                    {
                        // bepalen van de dir.
                        $nw_boek_jaar = "01-07";
                        $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
                        $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
                        
                        if( $mk_nu >= $mk_nw_boek_jaar )
                        {
                            //echo "<br> NA 01-07";
                            $jaar_1 = date('Y') + 1;
                            $jaar_2 = date('Y', mktime(0,0,0,7,1-1,$jaar_1) );
                            $dir = date('Y') . $jaar_2;    
                        }else{
                            //echo "<br> VOOR 01-07";
                            $jaar_1 = date('Y') - 1;
                            $jaar_2 = date('Y', mktime(0,0,0,7,1-1,date('Y')) );
                            
                            $dir = $jaar_1 . $jaar_2;
                        }
                    }
                    */
                    
                    if( !empty( $dir ) )
                    {
                        $dir .= "/";
                    }
                    
                    
                    $kleur = "color:red;";
                    
                    $tel_coda = 0;
                    
                    $tab_coda = "<table width='100%'>";
                    
                    $toon_info = 0;
                    $q_zoek_coda = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cf_id_fac = " . $rij->cf_id);
                    while( $c = mysqli_fetch_object($q_zoek_coda) )
                    {
                        $tel_coda += $c->bedrag;
                        
                        $tab_coda .= "<tr>";
                        $tab_coda .= "<td>Coda : </td>";
                        $tab_coda .= "<td>".  changeDate2EU($c->boek_dat) ."</td>";
                        $tab_coda .= "<td align='right' >&euro;" . number_format($c->bedrag,2,","," ") . "</td>";
                        $tab_coda .= "</tr>";
                        
                        $toon_info = 1;
                    }
                    
                    $rij->cf_bedrag = number_format( $rij->cf_bedrag, 2, ".", "" );
                    
                    $min_tel_coda = $tel_coda - 2;
                    $max_tel_coda = $tel_coda + 2;
                    
                    if( $min_tel_coda < $rij->cf_bedrag && $max_tel_coda > $rij->cf_bedrag )
                    {
                        $kleur = "color:green;";
                    }else{
                        $verschil1 = $rij->cf_bedrag - $tel_coda;
                        
                        $min_verschil = $verschil1 - 2;
                        $max_verschil = $verschil1 + 2;
                        
                        $cn_tot = 0;
                        foreach( $cn_arr as $cn_id => $bedrag1 )
                        {
                            if( number_format($min_verschil,2,".","") < number_format($bedrag1["bedrag"],2,".","") && number_format($bedrag1["bedrag"],2,".","") < number_format($max_verschil,2,".","") )
                            {
                                //echo "<br>AAA" . number_format($bedrag,2,".","") ."==". number_format($verschil,2,".","");
                                $tab_coda .= "<tr>";
                                $tab_coda .= "<td>CN : </td>";
                                $tab_coda .= "<td>".  changeDate2EU($bedrag1["datum"]) ."</td>";
                                $tab_coda .= "<td align='right' >&euro;" . number_format($bedrag1["bedrag"],2,","," ") . "</td>";
                                $tab_coda .= "</tr>";
                                
                                $toon_info = 1;
                                
                                unset( $cn_arr[$cn_id] );
                                $kleur = "color:green;";
                                break;
                            }else
                            {
                                $cn_tot += $bedrag1["bedrag"];
                            }
                        }
                        
                        if( number_format($cn_tot,2,".","") == number_format($verschil1,2,".","") )
                        {
                            foreach( $cn_arr as $cn_id => $bedrag1 )
                            {
                                $tab_coda .= "<tr>";
                                $tab_coda .= "<td>CN : </td>";
                                $tab_coda .= "<td>".  changeDate2EU($bedrag1["datum"]) ."</td>";
                                $tab_coda .= "<td align='right' >&euro;" . number_format($bedrag1["bedrag"],2,","," ") . "</td>";
                                $tab_coda .= "</tr>";
                                
                                $toon_info = 1;
                                
                                unset( $cn_arr[$cn_id] );
                                $kleur = "color:green;";
                                break;
                            }  
                        }
                        
                        // cn koppelen aan factuur dat nog niet volledig is betaald.
                        /*
                        if( number_format($cn_tot,2,".","") < number_format($verschil1,2,".","") )
                        {
                            
                            
                            foreach( $cn_arr as $cn_id => $bedrag1 )
                            {
                                 
                                
                                $tab_coda .= "<tr>";
                                $tab_coda .= "<td>CN : </td>";
                                $tab_coda .= "<td>".  changeDate2EU($bedrag1["datum"]) ."</td>";
                                $tab_coda .= "<td align='right' >&euro;" . number_format($bedrag1["bedrag"],2,","," ") . "</td>";
                                $tab_coda .= "</tr>";
                                
                                $toon_info = 1;
                                
                                unset( $cn_arr[$cn_id] );
                                $kleur = "color:red;";
                                
                                $tel_coda += $bedrag1["bedrag"];
                                //break;
                            }
                        }
                        */
                    }
                    
                    $tab_coda .= "</table>";
                    
                    if( $kleur != "color:green;" && $tot_bedrag >= 0 )
                    {
                        $datum_ymd = explode("-", $rij->cf_date);
                        $mk_datum = mktime(0,0,0,$datum_ymd[1],$datum_ymd[2],$datum_ymd[0]);
                        $mk_nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
                        $aantal_dagen_verschil = ceil( ($mk_nu - $mk_datum) / 86400 );
                        
                        if( $aantal_dagen_verschil >= $kklant->cus_bet_termijn )
                        {
                            if( $toon_title == 0 )
                            {
                                echo "<br/>";
                                
                                echo "<span style='width:120px;float:left;padding:2px;height:10px;'><strong>Fac.</strong></span>";
                                echo "<span style='width:100px;float:left;padding:2px;height:10px;'><strong>Datum</strong></span>";
                                echo "<span style='width:200px;float:left;padding:2px;height:10px;text-align:right;'><strong>Fac.bedrag</strong></span>";
                                echo "<span style='width:200px;float:left;padding:2px;height:10px;text-align:right;'><strong># dagen</strong></span>";
                                echo "<span style='width:200px;float:left;padding:2px;height:10px;text-align:right;'><strong>Bedrag open</strong></span>";
                                echo "<span style='width:100px;float:left;padding:2px;height:10px;text-align:right;'><strong># Aanm.</strong></span>";
                                
                                $toon_title = 1;
                            }
                            
                            echo "<div style='clear:both;'></div>";
                            
                            $bedrag_nogbetalen = $rij->cf_bedrag - $tel_coda;
                            
                            if( $toon_info == 1 )
                            {
                                echo "<img style='float:left;' src='images/info.png' width='16px' height='16px' alt='Detailweergave' title='Detailweergave' onclick='showHide1(". $rij->cf_id .");' >";
                            }else
                            {
                                echo "<img src='images/empty.png' style='float:left;width:16px;height:16px;' >";
                            }
                            
                            $sel = "";
                            if( isset( $_POST["sel_all"] ) )
                            {
                                $sel = " checked='checked' ";
                            }
                            
                            // nakijken of er voor dit factuur reeds een aanmaning verstuurd is geweest.
                            
                            $mk_nu = mktime(0,0,0,date('m'),date('d'),date('Y'));
                            
                            $datum_aanm_arr = array();
                            
                            $q_aantal_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_fac_id = " . $rij->cf_id . " ORDER BY 1");
                            $aantal_aanm = mysqli_num_rows($q_aantal_aanm);
                            
                            if( $aantal_aanm > 0 )
                            {
                                
                                while( $fac_aanm = mysqli_fetch_object($q_aantal_aanm) )
                                {
                                    $aa_datum_dmy = explode("-", $fac_aanm->aa_datum);
                                    
                                    $mk_aa_datum = mktime(0,0,0, $aa_datum_dmy[1], $aa_datum_dmy[0], $aa_datum_dmy[2] );
                                    
                                    $datum_aanm_arr[$rij->cf_id] = array( "datum" => $fac_aanm->aa_datum,
                                                                          "dagen_verschil" => ($mk_nu - $mk_aa_datum) / 86400,
                                                                          "aantal" => $aantal_aanm );
                                    
                                } 
                            }
                            
                            $termijn_arr[1] = 5;
                            $termijn_arr[2] = 5;
                            
                            $show_checker = 0;
                            if( $aantal_aanm == 0 || $aantal_aanm == 3 )
                            {
                                $show_checker = 1;
                            }
                            
                            if( $datum_aanm_arr[$rij->cf_id]["dagen_verschil"] >= $termijn_arr[1] && $datum_aanm_arr[$rij->cf_id]["aantal"] == 1 )
                            {
                                $show_checker = 1;
                            }
                            
                            if( $datum_aanm_arr[$rij->cf_id]["dagen_verschil"] >= $termijn_arr[2] && $datum_aanm_arr[$rij->cf_id]["aantal"] == 2 )
                            {
                                $show_checker = 1;
                            }
                            
                            if( $datum_aanm_arr[$rij->cf_id]["aantal"] > 3 )
                            {
                                $show_checker = 1;
                            }
                            
                            if( $bedrag_nogbetalen > 0 )
                            {
                                if( $show_checker )
                                {
                                    echo "<span style='width:120px;float:left;padding:2px;height:10px;".$kleur."'>";
                                    
                                    if( $_SESSION[$session_var]->group_id != 8 )
                                    {
                                        echo "<input type='checkbox' ". $sel ." name='fac[]' id='fac_".$rij->cf_id."' value='kl-".$cus_id1."_".$rij->cf_id."_".$bedrag_nogbetalen."' />";
                                    }
                                    
                                    if( file_exists( "cus_docs/". $rij->cf_cus_id ."/factuur/".$dir. $rij->cf_file ) )
                                    {
                                        echo "<a href='cus_docs/". $rij->cf_cus_id ."/factuur/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";
                                    }else
                                    {
                                        echo "<a href='facturen/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";    
                                    }
                                    
                                    echo "</span>";
                                    
                                }else
                                {
                                    echo "<span style='width:120px;float:left;padding:2px;height:10px;".$kleur."'>";
                                    
                                    if( file_exists( "cus_docs/". $rij->cf_cus_id ."/factuur/".$dir. $rij->cf_file ) )
                                    {
                                        echo "<a href='cus_docs/". $rij->cf_cus_id ."/factuur/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";
                                    }else
                                    {
                                        echo "<a href='facturen/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";    
                                    } 
                                     
                                    echo "</span>";
                                }
                                
                                echo "<span style='width:100px;float:left;padding:2px;".$kleur."'>".changeDate2EU( $rij->cf_date )."</span>";
                                echo "<span style='width:180px;float:left;padding:2px;text-align:right;".$kleur."'>&euro;".number_format( $rij->cf_bedrag, 2, ",", " " )."</span>";
                                echo "<span style='width:200px;float:left;padding:2px;text-align:right;".$kleur."'>". $aantal_dagen_verschil ."</span>";
                                echo "<span style='width:200px;float:left;padding:2px;text-align:right;".$kleur."'>". number_format( $bedrag_nogbetalen, 2, ",", " " ) ."</span>";
                                echo "<span style='width:100px;float:left;padding:2px;text-align:right;".$kleur."'>";
                                echo $aantal_aanm;
                                echo "</span>";
                                
                                $tot_nog_te_betalen += $bedrag_nogbetalen;
                                
                                echo "<div id='id_". $rij->cf_id ."' style='clear:both;display:none;border:1px solid black;padding:5px;' >";
                                echo $tab_coda;
                                echo "</div>";
                            }
                        }else
                        {
                            //echo "<br>" . $rij->cf_id . " " . $rij->cf_file;
                            
                            echo "<input style='display:none;' type='checkbox' checked='checked' name='fac_nogok[]' id='fac_nogok_".$rij->cf_id."' value='kl-".$cus_id1."_".$rij->cf_id."_".$bedrag_nogbetalen."' />";
                        }
                    }
                }
                
                echo "<div style='clear:both;'></div>";
                
                if( $tot_bedrag >= 0 )
                {
                    echo "<br/><b>Totaal openstaand bedrag buiten betalingstermijn : &euro; " . number_format($tot_nog_te_betalen,2,",", " ") . "</b><br/><br/>";
                    
                    if( !isset( $_POST["sel_all"] ) )
                    {
                        echo "<hr/>";
                    }   
                }
                
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";
                
                if( isset( $_POST["sel_all"] ) )
                {
                    echo "<hr/>";
                }
                
                $tot_fac += $bedrag;
                $tot_ont += $betalingen;
                $tot_cn += $cn;
                $tot_verschil += $verschil;
                
                $aant_glob++;
            }else
            {
                $exclude_arr[$cus_id] = $cus_id;
            }
        }
        
        echo "<br/><table cellpadding='0' cellspacing='0' width='100%' border='0'>";
        echo "<tr>";
        echo "<td width='420'><strong>TOTAAL</strong></td>";
        echo "<td width='150' align='right'><strong>". number_format($tot_fac,2,","," ")."</strong></td>";
        echo "<td width='150' align='right'><strong>". number_format($tot_ont,2,","," ")."</strong></td>";
        echo "<td width='120' align='right'><strong>". number_format($tot_cn,2,","," ")."</strong></td>";
        echo "<td align='right'><strong>". number_format($tot_verschil,2,","," ")."</strong></td>";
        echo "</tr>";
        
        echo "</table>";
        echo "Aantal : " . $aant_glob;
        
        echo "<br/><br/>";
        echo "<input type='submit' name='Maak_aanmaningen' id='Maak_aanmaningen' value='Maak aanmaningen' />";
        echo "</form>";
        ?>
        </div>
        
        <?php
        
        //die(" aaaaaaaaaaaaa tijdelijk blokkeren voor snellere oplossing");
         
        $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar ORDER BY boekjaar_start");
        
        $ij=1;        
        while( $bj = mysqli_fetch_object($q_boekjaren) )
        {
            $dir = substr( $bj->boekjaar_start, 0, 4 );
            
        /*    
            echo '<li><a href="#tabs_fac-'.$dir.'">BJ'. $dir .'</a></li>';
        } 
         
        $start = 2009;
        
        
        while( $start <= date('Y') )
        {
            $vjaar = $start+1;
        */    
            echo "<div id='tabs_fac-".$dir."'>";
            
            
            $start1 = $start;
            
            if( $start1 == 2009 )
            {
                $start1 = 2007;
            }
            
            echo "<b>Boekjaar ". $ij ." : ". changeDate2EU($bj->boekjaar_start) . " tot " . changeDate2EU($bj->boekjaar_einde) . "</b>";
            
            
            /* */
            
            // als de klanten met facturen ophalen.
            $qq_zoek_fac = "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_date BETWEEN '". $bj->boekjaar_start ."' AND '". $bj->boekjaar_einde ."' GROUP BY cf_cus_id";
            $q_zoek_fac = mysqli_query($conn, $qq_zoek_fac) or die( mysqli_error($conn) . " " . __LINE__);
            
            $lijst = array();
            
            while( $rij = mysqli_fetch_object($q_zoek_fac) )
            {
                //$gev = 999999999;
                
                //echo "<br>" . "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = 980 AND cf_file = '". $rij->cf_file ."' AND cf_date = '". $rij->cf_date ."' ";
                
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = 980 AND cf_file = '". $rij->cf_file ."' AND cf_date = '". $rij->cf_date ."' ");
                $gev = mysqli_num_rows($q_zoek);
              
                if( $rij->cf_type == '0' )
                {
                    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $rij->cf_cus_id . " ORDER BY cus_naam"));
                    
                    if( $klant )
                    {
                        
                        if( $klant->cus_active == '1' && $gev != 1 )
                        {
                            if( !empty( $klant->cus_naam ) )
                            {
                                $naam = $klant->cus_naam;
                            }else
                            {
                                $naam = $klant->cus_bedrijf;
                            }
                            
                            $lijst[ ucfirst( $naam ) ] = $rij->cf_cus_id;
                        }
                    }
                }else
                {
                    $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $rij->cf_cus_id));
                    
                    if( $project )
                    {
                        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $project->cus_id));
                        
                        if( $gev != 1 )
                        {
                            $lijst[ ucfirst( $klant->cus_naam ) ] = "p_" . $klant->cus_id;
                        }
                    }
                }
            }
            
            // lijst is alfabetisch
            //$lijst["Vanparijs"] = 980;
            ksort( $lijst );
            
            /*
            echo "<pre>";
            var_dump( $lijst );
            echo "</pre>";
            */
            
            
            echo "<br/><br/><table cellpadding='0' cellspacing='0' width='100%' border='0'>";
            echo "<tr>";
            echo "<td><strong>Naam</strong></td>";
            echo "<td align='right'><strong>Tot. fac. bedrag</strong></td>";
            echo "<td align='right'><strong>Tot. ontv. bedrag</strong></td>";
            echo "<td align='right'><strong>Tot. CN</strong></td>";
            echo "<td align='right'><strong>Verschil</strong></td>";
            echo "</tr>";
            
            $i=0;
            
            $tot_fac = 0;
            $tot_ont = 0;
            $tot_cn = 0;
            $tot_verschil = 0;
            
            foreach( $lijst as $naam => $cus_id )
            {
                $bedrag = 0;
                
                $betalingen = 0;
                
                $cus_id_ori = $cus_id;
                
                $q_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_date 
                                      BETWEEN '". $bj->boekjaar_start ."' AND '". $bj->boekjaar_einde ."' AND cf_cus_id = " . $cus_id . " AND cf_type = '0'");
            
                while( $fac = mysqli_fetch_object($q_fac) )
                {
                    $bedrag += $fac->cf_bedrag;
                    
                    // coda doorzoeken pas bij het vinden van facturen
                    $q_zoek_bet = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cf_id_fac = " . $fac->cf_id);
                
                    while( $bet_coda = mysqli_fetch_object($q_zoek_bet) )
                    {
                        $betalingen += $bet_coda->bedrag;
                    }
                }    
                
                $cn = 0;
                
                //echo "<br/>SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $cus_id . " AND cf_soort = 'creditnota' ";
                
                $q_zoek_cn = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . str_replace("p_", "", $cus_id) . " AND cf_soort = 'creditnota' 
                                          AND cf_date BETWEEN '". $bj->boekjaar_start ."' AND '". $bj->boekjaar_einde ."' ");
                
                while( $bet_cn = mysqli_fetch_object($q_zoek_cn) )
                {
                    $cn += $bet_cn->cf_bedrag;
                }
                
                $verschil = number_format( $bedrag, 2, ".", "") - ( number_format( $betalingen, 2, ".", "") + number_format( $cn, 2, ".", "") );
                $verschil_ex = explode(".", $verschil);
                
                //echo "<br/>" . $naam . " " . $verschil . " " . $verschil_ex[0] . " " . $bedrag . " " . $betalingen . " " . $cn;
                
                if( $verschil_ex[0] != 0 && !in_array( $cus_id, $exclude_arr ) )
                {
                    $i++;
                    $kleur = $kleur_grijs;
            		if( $i%2 )
            		{
            			$kleur = "white";
            		}
                    
                    echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                    echo "<td>";
                    
                    if( $_SESSION[$session_var]->group_id != 8 )
                    {
                        echo "<a title='Klik hier om de klant te openen' href='http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=". str_replace("p_", "", $cus_id) ."' target='_blank' ><u>" . $naam . "</u></a>";    
                    }else
                    {
                        echo $naam;
                    }
                    
                    echo "</td>";
                    echo "<td align='right'>" . number_format($bedrag,2,","," ") . "</td>";
                    
                    echo "<td align='right'>";
                    echo number_format($betalingen,2,","," "); 
                    echo "</td>";
                    
                    echo "<td align='right'>";
                    echo number_format($cn,2,","," "); 
                    echo "</td>";
                    
                    if( $verschil > 0 )
                    {
                        echo "<td align='right' class='error'>";    
                    }else
                    {
                        echo "<td align='right' class='correct' >";
                    }
                    
                    echo number_format( $verschil ,2,","," ");
                    
                    echo "</td>";
                    echo "</tr>";
                    
                    $tot_fac += $bedrag;
                    $tot_ont += $betalingen;
                    $tot_cn += $cn;
                    $tot_verschil += $verschil;
                }
            }
            
            echo "<tr>";
            echo "<td><strong>TOTAAL</strong></td>";
            echo "<td align='right'><strong>". number_format($tot_fac,2,","," ")."</strong></td>";
            echo "<td align='right'><strong>". number_format($tot_ont,2,","," ")."</strong></td>";
            echo "<td align='right'><strong>". number_format($tot_cn,2,","," ")."</strong></td>";
            echo "<td align='right'><strong>". number_format($tot_verschil,2,","," ")."</strong></td>";
            echo "</tr>";
            
            echo "</table>";
            
            /* */

            echo "</div>";
             
            $start++;
            $ij++;
        }
        
        ?>
        
        <div id="tabs-2">
            <?php
            
            
            $q_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_fac_id != 0 GROUP BY aa_filename ORDER BY aa_filename");
            
            $klant_arr = array();
            
            while( $aanm = mysqli_fetch_object($q_aanm) )
            {
                $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = ". $aanm->aa_cus_id));
                $klant_arr[ $aanm->aa_cus_id ] = $klant->cus_naam;
            }
            
            asort($klant_arr);
            
            foreach( $klant_arr as $cus_id => $naam )
            {
                if( $_SESSION[$session_var]->group_id != 8 )
                {
                    echo "<a title='Klik hier om de klant te openen' href='http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=".$cus_id ."' target='_blank' ><u><b>" . $naam . "</b></u></a>";    
                }else
                {
                    echo $naam;
                }
                
                echo "<br/>";
                
                $q_aanm1 = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = ". $cus_id ." AND aa_fac_id != 0 GROUP BY aa_filename ORDER BY aa_filename");
                
                while( $rij = mysqli_fetch_object($q_aanm1) )
                {
                    
                
                    $q_fac = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_filename = '". $rij->aa_filename ."'");
                
                    $i = 0;
                    
                    if( mysqli_num_rows($q_fac) > 0 )
                    {
                        echo "<table cellpadding='2' cellspacing='0' >";
                        echo "<tr>";
                        echo "<td width='200'><strong>Factuur</strong></td>";
                        echo "<td width='200'><strong>Fac.datum</strong></td>";
                        echo "<td width='200'><strong>Aanm.datum</strong></td>";
                        echo "<td width='150' align='right'><strong>Openstaand bedrag</strong></td>";
                        echo "<td width='150' align='right'><strong>Aanmaning</strong></td>";
                        echo "</tr>";
                        
                        while( $fac = mysqli_fetch_object($q_fac) )
                        {
                            $i++;
                            $kleur = $kleur_grijs;
                    		if( $i%2 )
                    		{
                    			$kleur = "white";
                    		}
                            
                            $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac->aa_fac_id));
                            
                            echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                            echo "<td><a href='http://www.solarlogs.be/kalender/facturen/". getInvoicePath($fac->aa_fac_id) ."' target='_blank' ><u>" . $fac->aa_factuur . "</u></a></td>";
                            echo "<td>" . changeDate2EU($factuur->cf_date) . "</td>";
                            echo "<td>" . $fac->aa_datum . "</td>";
                            echo "<td align='right'>&euro;&nbsp;" . number_format($fac->aa_bedrag, 2, ",", " "). "</td>";
                            echo "<td align='right'><a href='aanmaningen/".$rij->aa_filename."' target='_blank' ><img src='images/pdf.jpg' border='0' /></a></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                    
                }
                
                echo "<br/><hr/>";
            }
            
            
            ?>
        </div>
	</div>
</div>
<center><?php 

include "inc/footer.php";

?></center>

</body>
</html>
<?php 
/*
echo "<pre>";
print_r( $_POST );
//echo "<br><hr><br>";
//print_r( $_FILES );
echo "</pre>";
*/
?>