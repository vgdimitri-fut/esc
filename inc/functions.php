<?php 

/*
use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\Tfpdf;
*/

/*
Verwijderen van de regel in tabel user_open_cus_id
daarna user_id en cus_id toevoegen
*/
$session_var = "esc_user";
if( isset($_SESSION[ $session_var ]) && $_SESSION[ $session_var ]->user_id == 19 && 2 == 1 )
{
    ini_set('display_errors',1); 
    error_reporting(E_ALL);
}

$smtp_server = "192.168.1.250";

$btw_vrijstelling = array();
$btw_vrijstelling[1] = "Dienstverrichting niet onderworpen aan Belgische BTW Artikel 21, &sect;3, 1&deg; van het WBTW";
$btw_vrijstelling[2] = "BTW verlegd KB 1, art 20";
$btw_vrijstelling[3] = "Intracommunautaire leveringen van goederen vrijgesteld volgens Artikel 39bis";
$btw_vrijstelling[4] = "Levering onderworpen aan de bijzondere regeling van belastingsheffing over de marge, btw niet aftrekbaar (art 58&sect;4btw-wetboek)";

$field_type_arr = array();
$field_type_arr[1] = "Datum";
$field_type_arr[2] = "Tekst";
$field_type_arr[3] = "Numeriek";
$field_type_arr[4] = "Decimaal";
$field_type_arr[5] = "Grote tekst";
$field_type_arr[6] = "Keuze veld";
$field_type_arr[7] = "Checkbox";
$field_type_arr[8] = "Custom";

$transactie_status = array();
$transactie_status[0] = 'Purchase';
$transactie_status[1] = 'Sale';
        
$btw_arr = array();
$btw_arr[] = 0;
$btw_arr[] = 6;
$btw_arr[] = 21;

$p_merk = array();
$p_merk[] = "JaSolar";
$p_merk[] = "CNPV";
$p_merk[] = "CEEG";
$p_merk[] = "Trina";

$p_dikte = array();
$p_dikte[] = 40;
$p_dikte[] = 46;
$p_dikte[] = 50;

$p_connector = array();
$p_connector[] = "Sunclix";
$p_connector[] = "MC 4";

// START ARRAY VERKOOP
$verkoop_arr["0"] = "N";
$verkoop_arr["1"] = "J, verkoop";
$verkoop_arr["2"] = "J, verhuur";
$verkoop_arr["3"] = "J, RvO";
$verkoop_arr[""] = "";
// EINDE ARRAY VERKOOP
function truncate($text, $chars = 25) {
    if(strlen($text) > 25)
    {
          $text = $text." ";
          $text = substr($text,0,$chars);
          $text = substr($text,0,strrpos($text,' '));
          $text = $text."...";
    }
    return $text;
}
function factuur_wissel($output, $wissel_id)
{
    // al de gegevens bevinden zich in 
    // $_SESSION["fac_vreg"]["id"]
    // $_SESSION["fac_vreg"]["fac_nr"] 
    require_once "../inc/fpdf.php";
	require_once "../inc/fpdi.php";
    
    // zoeken naar factuurnummer
	$nw_boek_jaar = "01-07";
    $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
    $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
    
    $zoek_fac1 = 0;
    if( $mk_nu >= $mk_nw_boek_jaar )
    {
        //echo "<br> NA 01-07";
        $jaar_1 = date('Y') + 1;
        $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,$jaar_1) );
        
        $q_geenfac = mysqli_query($conn, "SELECT * 
								FROM kal_customers_files
								WHERE cf_soort = 'factuur'
								AND cf_id != 670
								AND cf_id != 671
                                AND cf_id != 2337
                                AND cf_id != 2827
                                AND cf_date BETWEEN '". date('Y') ."-07-01' AND '". $jaar_2 ."'
								ORDER BY 1 DESC");
    }else{
        //echo "<br> VOOR 01-07";
        $jaar_1 = date('Y') - 1;
        $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,date('Y')) );
        
        $q_geenfac = mysqli_query($conn, "SELECT * 
								FROM kal_customers_files
								WHERE cf_soort = 'factuur'
								AND cf_id != 670
								AND cf_id != 671
                                AND cf_id != 2337
                                AND cf_id != 2827
                                AND cf_date BETWEEN '". $jaar_1 ."-07-01' AND '". $jaar_2 ."'
								ORDER BY 1 DESC");
    }
	
	while( $rij = mysqli_fetch_object($q_geenfac) )
	{
		$factuur = explode(".", $rij->cf_file);
		
		if( is_numeric( $factuur[0] ) && $zoek_fac1 < $factuur[0] )
		{
			$zoek_fac1 = $factuur[0];
		}
	}
    
    $zoek_fac1++;
    
    $factuur_nr = $zoek_fac1;
    
    $pdf = new FPDI();
    $max_aant_regels = 22;
    //echo "<br>" . $factuur_nr;
    
    $id_arr = array();
    $tel=0;
    
    $wissel = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_omv_wissel WHERE id = " . $wissel_id));
    
    $q_wissels_sn = mysqli_query($conn, "SELECT * FROM kal_omv_wissel_sn WHERE cus_id = " . $wissel->cus_id);
    
    $cus_id = 0;
    
    while( $sn = mysqli_fetch_object($q_wissels_sn) )
    {
        $tel++;
        $id_arr[] = array( "oude" => $sn->oude, "nieuwe" => $sn->nieuwe ); 
        
        $cus_id = $sn->cus_id;
    }
    
    $netb = $klant->cus_netbeheerder;
    $aant_p = ceil( $tel / $max_aant_regels );
    
    $excl = 0;
    $prijs = 0;
            
    for( $i=1;$i<=$aant_p;$i++ )
    {
        $pdf->AddPage();
        $pdf->AddFont('eurosti','','eurosti.php');
	    $pdf->setSourceFile('../pdf/factuur.pdf');
        $tplIdx = $pdf->importPage(1); 
    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    	$pdf->SetFont('eurosti', '', 11);
    	$pdf->SetTextColor(0,0,0);
        
        $installatie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
        
        $pdf->Text(17, 85, "Omvormers vervangen bij :     " . $installatie->cus_naam . ", " . $installatie->cus_straat . " " . $installatie->cus_nr . ", " . $installatie->cus_postcode . " " . $installatie->cus_gemeente);
        
        //var_dump( $installatie );
        
        $q_net = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = 5911");
        $klant = mysqli_fetch_object($q_net);
        
        $pdf->SetFont('eurosti', '', 16);
	    $pdf->SetTextColor(0,0,0);
        $pdf->Text(30, 39, "Factuur" );
    
    	// tonen van het documents nr
  		$pdf->SetFont('eurosti', '', 10);
        
        $pdf->Text(154, 262, "BTW verlegd KB 1, art 20" );
        
        $datum = date('d') . "-" . date('m') . "-" . date('Y'); 
        
    	$pdf->Text(40, 52.5, $datum );
    	
    	$tmp_dat = explode("-", $datum);
    	
    	$jaarmaand = "";
    
    	if( strlen( $tmp_dat[2] ) == 4 )
    	{
    		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
    	}
    	$pdf->Text(40, 59.25, $jaarmaand . "-" . $factuur_nr );
        
        if( $klant->cus_fac_adres == "1" )
    	{
    		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
    		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
    	}else
    	{
    		
    		if( !empty( $klant->cus_bedrijf ) )
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
    			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
    		}else
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
    		}
    		
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
    		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
    		
    		if( !empty( $klant->cus_btw ) )
    		{
    			$pdf->Text(40, 72.75, $klant->cus_btw );
    		}
    	}
        
        $extra_offset = 0;
        $aant = 1;
        $telme = 0;
        $prijs = 100;
        
        foreach( $id_arr as $id => $sn_array )
        {
            /*
            echo "<pre>";
            var_dump( $sn_array );
            echo "</pre>";
            */
            
            if( $telme >= $max_aant_regels )
            {
                break;
            }else
            {
                $pdf->SetXY( 44, 112+$extra_offset );
		
    			$pdf->Cell( 102, 5, "Oude sn : " . $sn_array["oude"] . ", nieuwe sn : " . $sn_array["nieuwe"], 0, 1,'L');
				
				$pdf->SetXY( 17, 112+$extra_offset );
                
                $ex_p = explode(" ", $p);
                
				$pdf->Cell( 25, 5, $ex_p[0], 0, 1,'L');
    			
                $pdf->SetXY( 145, 112+$extra_offset );
    			$pdf->Cell( 20, 5, $aant, 0, 1, 'R');
    			
    			$pdf->SetXY( 160, 112+$extra_offset );
    			$pdf->Cell( 26, 5, number_format( $prijs * $aant, 0, "", " " ), 0, 1, 'R');
                
                // toevoegen euro teken aan elke regel die wordt afgedrukt
                $euro_arr[] = 115.75 + $extra_offset;
    
    			$extra_offset += 5.5;
    			
    			$excl += $prijs * $aant;
                
                $telme++;
                unset( $id_arr[ $id ] );
            }
        }
        
        if( $aant_p > 1 )
        {
            $pdf->Text(20, 100, "Pagina " . $i . " van " . $aant_p );
        }
        
        if( $i == $aant_p )
        {
            $incl = $excl;
        	$btw = $incl - $excl;
        
            $pdf->SetXY( 168, 234 );
            $pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');
        
        
            //$pdf->Text(159, 246, "21%" );
        
        	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
        	//$pdf->SetXY( 163, 242.25 );
        	//$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
            
            $pdf->SetXY( 168, 250 );
            $pdf->Cell(25, 5, "  " . number_format($incl, 2, ",", " " ),0,1,'R');
        }
        
        // toevoegen factuurvoorwaarden.
        $pdf->AddPage();
        $pdf->setSourceFile('../pdf/factuur_vw.pdf');
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    }
    
    //force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $factuur_nr .'.pdf';
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $factuur_nr .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = "FU_" . $bestandsnaam . $factuur_nr .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output("FU_" . $bestandsnaam. $factuur_nr .'.pdf', $output);	
	}
}

function aasort (&$array, $key) 
{
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

function factuur_opstal($output)
{
	require "../inc/fpdf.php";
	require "../inc/fpdi.php";
	
    $euro_arr = array();
    
	$pdf = new FPDI();
	$pdf->AddPage(); 
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
	$pdf->setSourceFile('../pdf/factuur.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);

	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $_SESSION["opstal"]["fac_klant"]));
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, ucfirst( html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES))) ;
		$pdf->Text(110, 57.5, ucwords( html_entity_decode(trim($klant->cus_fac_straat), ENT_QUOTES) ) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . ucwords( html_entity_decode(trim($klant->cus_fac_gemeente), ENT_QUOTES) ) );
	}else
	{
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, ucfirst( html_entity_decode(trim($klant->cus_naam) ,  ENT_QUOTES)));	
		}
		
		$pdf->Text(110, 57.5, ucwords( html_entity_decode(trim($klant->cus_straat), ENT_QUOTES) ) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . ucwords( html_entity_decode(trim($klant->cus_gemeente), ENT_QUOTES) ) );
	}
	
	//$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	
	if( !empty( $klant->cus_btw ) )
	{
		$pdf->Text(40, 72.75, $klant->cus_btw );
	}
	
	$pdf->Text(40, 52.5, $_SESSION["opstal"]["fac_datum"] );	
	
	$tmp_dat = explode("-", $_SESSION["opstal"]["fac_datum"] );

	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $_SESSION["opstal"]["fac_nr"] );
	
	$pdf->Text(40, 66, ucfirst( html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) ) );
	
	$pdf->Text(159, 246, "21%" );
	
	$pdf->SetFont('eurosti', '', 10);
	
	// doorlopen van het aantal artikels
	$tot_excl = 0;
	for( $i=1; $i<15; $i++ )
	{
		$pdf->SetXY( 20, 112 + $lijnteller );
		$pdf->Cell( 24, 5, $_SESSION["opstal"]["products"][$i]["art"], 0, 1, 'L');
		
		
		$pdf->SetXY( 44, 112 + $lijnteller );
		$pdf->Cell( 102, 5, html_entity_decode(trim($_SESSION["opstal"]["products"][$i]["beschrijving"]), ENT_QUOTES), 0, 1, 'L');
		
		$pdf->SetXY( 145, 112 + $lijnteller );
		$pdf->Cell( 20, 5, $_SESSION["opstal"]["products"][$i]["aantal"], 0, 1, 'R');
		
		$pdf->SetXY( 167, 112 + $lijnteller );
		if( !empty( $_SESSION["opstal"]["products"][$i]["prijs"] ) )
		{
			$pdf->Cell( 25, 5, "  " . number_format(str_replace(",", ".", $_SESSION["opstal"]["products"][$i]["prijs"] ), 2, ",", " "), 0, 1, 'R');
            $euro_arr[] = 115.75 + $lijnteller;	
		}
        
		$lijnteller += 5.5;
		$tot_excl += str_replace(",", ".", $_SESSION["opstal"]["products"][$i]["prijs"] );
	}
	
	$btw = $tot_excl * 0.21;
	$pdf->SetXY( 163, 242.25 );
	$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", "." ),0,1,'R');
	$tot_incl = $tot_excl + $btw;
    $euro_arr[] = 245.5;

	
	$pdf->SetXY( 168, 234 );
	$pdf->Cell(25, 5, "  " . number_format( $tot_excl, 2, ",", " " ),0,1,'R');
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($tot_incl, 2, ",", " " ),0,1,'R');
	
    // toevoegen van het euroteken in een lettertype waar het euro-teken bestaat
    $pdf->setFont('Arial', '', 10);
    
    $euro_arr[] = 237.5; // subtot
    $euro_arr[] = 253.5; // eind tot
    
    foreach($euro_arr as $euro)
    {
        $pdf->text( 195, $euro, "EUR" );
    }
    
    // toevoegen pagina 2 met de factuur voorwaarden
    $pdf->AddPage();
    $pdf->setSourceFile('../pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
	//force the browser to download the output
	if( $output == "S" )
	{
        $ret["factuur"] = $pdf->Output('factuur_'.$_SESSION["opstal"]["fac_nr"] .'.pdf', $output);
		$ret["incl"] = $tot_incl;
		$ret["filename"] = 'factuur_'.$_SESSION["opstal"]["fac_nr"] .'.pdf';
		
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $_SESSION["opstal"]["fac_nr"] .'.pdf', $output);	
	}
}

function auto_factuur_boiler($output, $cus_id, $fac_nr, $datum)
{
    
    
    //$datum = date('d') . "-" . date('m') . "-" . date('Y');
    
    $daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    $daksoorten[11] = "Gevelmontage";
    $daksoorten[12] = "Grond installatie";
    
	require_once "../inc/fpdf.php";
	require_once "../inc/fpdi.php";
    
    $pdf = new FPDI();

    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $cus_id));
    $boiler = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_boiler WHERE cus_id = " . $cus_id));
    
    $dak = $boiler->cus_dak;
    $gebruik = $boiler->cus_gebruik;
    $huidig = $boiler->cus_huidig;
    $doorgang = $boiler->cus_doorgang;
    $cap = $boiler->cus_vs_cap;
    $col = $boiler->cus_vs_col;
    $woning5j = $boiler->cus_woning;
    
	$cus_id = $klant->cus_id;	
    /************************* PAGINA 2 *****************************************/
    
    $excl = 0;
    $exclude_arr = array();
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
    $euro_arr = array();
    
	$pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/distri_bon_leegfac.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// Tonen van het soort document
	$pdf->SetFont('eurosti', '', 16);
	$pdf->SetTextColor(0,0,0);
	
	$doc_nummer = "";
	$soort_b = "Factuur";
	$pdf->Text(30, 39, $soort_b );

	// tonen van het documents nr
	$pdf->SetFont('eurosti', '', 10);
	$pdf->Text(16.25, 59.25, "Factuur nr"  );
	
	$doc_nummer = $fac_nr;
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
		
		if( !empty( $klant->cus_btw ) )
		{
			$pdf->Text(40, 72.75, $klant->cus_btw );
		}
	}
	
	$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $doc_nummer );
	
	$pdf->SetFont('eurosti', '', 10);
	
    $boiler_prod = array();
    
    $boiler_prod[] = 227;
    
    /*
    $boiler_prod[] = 252; // 200l
    $boiler_prod[] = 228; // 300
    $boiler_prod[] = 251; // 500 
    */
    
    switch ( $boiler->cus_vs_cap )
    {
        case "200" :
            $boiler_prod[] = 252;
            break;
        case "310" :
            $boiler_prod[] = 228;
            break;
        case "500" :
            $boiler_prod[] = 251;
            break;   
    }
    
    //$boiler_prod[] = 229; // elek verw 2.5kw
    $boiler_prod[] = 230; // temp sensor
    $boiler_prod[] = 232; // dubbel flex
    $boiler_prod[] = 233; // connectie set 
    $boiler_prod[] = 234; // magne anode
    $boiler_prod[] = 235; // venteil 6b 
    $boiler_prod[] = 237; // 25l glucol 
    $boiler_prod[] = 238; // auto solar ont 
    $boiler_prod[] = 239; // expansite tank set 
    $boiler_prod[] = 240; // solar regelaar
    
    $boiler_prod[] = 241; // natloper
    
    if( $dak == 1 )
    {
        $boiler_prod[] = 242; // plat dak    
    }else
    {
        $boiler_prod[] = 246; // hellend dak    
    }
    
    $btw_perc = ($woning5j / 100) + 1; 
    
    $excl = $boiler->cus_prijs_incl / $btw_perc;
	$btw = $boiler->cus_prijs_incl - $excl;
    $incl = $boiler->cus_prijs_incl;
    
	$extra_offset = 0;
	foreach( $boiler_prod as $key => $dis )
	{
        $pdf->SetXY( 44, 112+$extra_offset );
		
		// soort van het artikel ophalen
		$artikelnaam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art WHERE ka_id = " . $dis));
		$pdf->Cell( 102, 5, $artikelnaam->ka_art, 0, 1,'L');
		
		$soort = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art_soort WHERE as_id = " . $artikelnaam->ka_as_id));
		$pdf->SetXY( 18, 112+$extra_offset );
		$pdf->Cell( 25, 5, $soort->as_doc, 0, 1,'L');
		
		$pdf->SetXY( 145, 112+$extra_offset );
		
        if( $dis == 227 )
        {
            $pdf->Cell( 20, 5, $col, 0, 1, 'R');
        }else
        {
            $pdf->Cell( 20, 5, 1, 0, 1, 'R');    
        }
/*
		$pdf->SetXY( 167, 112+$extra_offset );
        
        
        if( $dis == 227 )
        {
            $pdf->Cell( 26, 5, number_format( $p_ex, 2, ",", " " ), 0, 1, 'R');
            $euro_arr[] = 115.75 + $extra_offset; // artikel euro sign
            
        }
        */
        
		$extra_offset += 5.5;
	}
    
    /*
    echo "<br>" . $gebruik ;
    echo "<br>" . $huidig ;
    echo "<br>" . $doorgang;
    */
    
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_offerte_instellingen"));
    
    $tekst = array();
    
    if( $boiler->cus_cv == '1' )
    {
        $prijs_cv = $instellingen->boi_cv;
        
        $tekst[] = "Aansluiten ketel geldig indien pomp en sturing aanwezig (". number_format( $prijs_cv,2,",","" )."euro)";
    }
    
    if( $gebruik == "sanitair_cv" )
    {
        //$tekst[] = "Boiler met 2 warmtewisselaars, aansluiting op CV systeem is niet inbegrepen";
        $tekst[] = "Aansluiting op CV systeem is niet inbegrepen";
    }
    
    /*
    if( $huidig == "gas" || $huidig == "mazout" )
    {
        //$tekst[] = "Boiler met 2 warmtewisselaars, aansluiting op gas/mazout systeem is niet inbegrepen";
        $tekst[] = "Aansluiting op gas/mazout systeem is niet inbegrepen";
    }
    */
    
    if( $doorgang == "0" )
    {
        $tekst[] = "Gelieve er voor te zorgen dat de doorgang vrij is!";
    }
    
    $tekst[] = "Indien de oude boiler moet afgevoerd worden";
    $tekst[] = "komt er een kost bij van 100euro excl.";
    
    
    $tekst[] = "";
    
    $tot_opp = $col * 1.9;
    $tekst[] = "Aantal collectoroppervlakte : " . str_replace(".",",",$tot_opp) . "m�" ;
    
    if( count($tekst) > 0 )
    {
        foreach( $tekst as $t )
        {
            //echo "<br>" . $t;
            
            $extra_offset += 5.5;
            
            $pdf->SetXY( 44, 112+$extra_offset );
    		$pdf->Cell( 102, 5, $t, 0, 1,'L');
        }
    }
    
    /* Switchen naar ander lettertype voor het weergeven van de euro teken */
    $pdf->setFont('Arial', '', 10);
    
    $euro_arr[] = 237.5; // subtot
    $euro_arr[] = 253.5; // eind tot 

    foreach( $euro_arr as $euro )
    {
        $pdf->text( 195, $euro, "EUR" );
    }
    
    $pdf->SetFont('eurosti', '', 10);
    
	
	
    
    if( $klant->cus_medecontractor == '1' )
	{
	    $pdf->SetXY( 167, 234 );
	    $pdf->Cell(26, 5, number_format( $boiler->cus_prijs_incl, 2, ",", " " ), 0, 1,'R');
       
		$incl = $boiler->cus_prijs_incl;
		$pdf->Text(45, 232, "BTW verlegd KB 1, art 20" );
        $incl = $boiler->cus_prijs_incl;
	}else
    {
        $pdf->SetXY( 167, 234 );
	    $pdf->Cell(26, 5, number_format( $excl, 2, ",", " " ), 0, 1,'R');
        
    	if( $woning5j != "0" )
    	{
    		if( $woning5j == "6" )
    		{
    			// 6 %
    			$pdf->Text(161, 249.5, "6%" );
                $euro_arr[] = 249.5; // btw
    			//$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
    			$pdf->SetXY( 163, 246 );
    			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
    		}
    		
    		if( $woning5j == "21" )
    		{
    			// 21 %
                $euro_arr[] = 245.5; // btw
                
    			$pdf->Text(159, 246, "21%" );
    			//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
    			$pdf->SetXY( 163, 242.25 );
    			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
    		}
    	}
     }
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, number_format($incl, 2, ",", " " ), 0, 1, 'R');
	
    $verkl = 0;
	if( $verkl == 1 )
	{
		$verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
		$pdf->SetFont('Arial', '', 9);
		$pdf->SetXY( 20, 221 );
		$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
	}
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('boiler_factuur_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = 'boiler_factuur_'. $doc_nummer .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output('boiler_factuur_'. $doc_nummer .'.pdf', $output);	
	}
}

function auto_offerte_mon($output, $cus_id)
{
    require_once "../inc/fpdf.php";
	require_once "../inc/fpdi.php";

    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
    $mon = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_mon WHERE cus_id = " . $cus_id));
    
    $max_page = "/15";
    
    $pdf = new FPDI();
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
    
    /*************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/voorblad.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 12); 
    $pdf->SetTextColor(0,0,0);
    
    $regelmin = 164;
    $van_links = 30;
    $pdf->Text($van_links, 142, "Datum offerte : " . date('d') . "-" . date('m') . "-" . date('Y')  );
    
    $pdf->Text($van_links, 152, ucfirst( html_entity_decode( $klant->cus_naam, ENT_QUOTES ) ) );
    $pdf->Text($van_links, 158, ucfirst( html_entity_decode( $klant->cus_straat, ENT_QUOTES )) . " " . $klant->cus_nr );
    $pdf->Text($van_links, 164, $klant->cus_postcode . " " . html_entity_decode( $klant->cus_gemeente, ENT_QUOTES ) );
    
    if( !empty( $klant->cus_email ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, $klant->cus_email );
    }
    
    if( !empty( $klant->cus_tel ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "Tel. : " . $klant->cus_tel );
    }
    
    if( !empty( $klant->cus_gsm ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "GSM : " . $klant->cus_gsm );
    }
    
    if( !empty( $klant->cus_btw ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "BTW nr : " . $klant->cus_btw );
    }
    
    // boiler gedeelte
    $regelmin = 142;
    $van_links = 130;
    $lijn_factor = 5;
    $pdf->SetFont('Arial', '', 10);
    
    $mon_aanw = "Ja";
    if( $mon->cus_mon_actief == '0' )
    {
        $mon_aanw = "Neen";
    }
    
    $pdf->Text($van_links, $regelmin, "Monitoring aanwezig : " . $mon_aanw );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Aantal webboxen : " . $mon->cus_aant_wb );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Aantal Omvormers : " . $mon->cus_aant_omv );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Aantal PVZG nr's : " . $mon->cus_aant_pvz );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Merk omvormers : " . $mon->cus_merk_omv );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "KWp : " . $mon->cus_kwp );
    $regelmin += $lijn_factor;
    
    $pakket_keuzes = array();
    $pakket_keuzes[1] = "Brons";
    $pakket_keuzes[2] = "Zilver";
    $pakket_keuzes[3] = "Goud";
    $pakket_keuzes[4] = "Platinum";
    
    $pdf->Text($van_links, $regelmin, "Keuze pakket : " . $pakket_keuzes[ $mon->cus_keuze_pakket ]  );
    $regelmin += $lijn_factor;
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 200, 290, "1".$max_page );
    
    /************************/
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 22); 
    $pdf->SetTextColor(13,35,61);
    $pdf->Text(20, 20, "Monitoring en onderhoud" );
    
    $pdf->SetFont('Arial', 'I', 13); 
    $pdf->Text(20, 40, "Brons :" );
    
    $pdf->Image('../pdf/mon/ilumen.jpg', 40, 30, 24, 10 );
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 45, "Economische analyse op maandbasis. Dit is een technisch/financieel rapport op niveau van een CFO" );
    $pdf->Text(20, 50, "(financieel directeur) waaruit duidelijk blijkt of de installatie naar behoren werkt. We gaan de " );
    $pdf->Text(20, 55, "installatie automatisch een rating geven, nl A+,A-, B of een C rating. Afhankelijk van het " );
    $pdf->Text(20, 60, "resultaat kan u dan kijken of er verdere actie nodig is." );
    
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Text(30, 70, "Deze analyse is dus echt gebaseerd op volgende vraagstelling: �ik heb X euro ge�nvesteerd en" );
    $pdf->Text(30, 75, "de installateur beloofde mij Y opbrengst per jaar. Werd deze belofte gehaald, ja of neen?�" );
    
    $pdf->Image('../pdf/mon/pag1.jpg', 20, 80, 170, 110 );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text(20, 200, "Het pakket bevat maandelijkse rapportage in een overzichtelijk document met telkens:" );
    $pdf->Text(30, 210, "- Aantal certificaten die werden opgewekt per maand" );
    $pdf->Text(30, 220, "- Vergelijking met het vooropgestelde aantal certificaten" );
    $pdf->Text(30, 230, "- Status bepaling, dit geeft in een oogopzicht de kwaliteit van de installatie weer" );
    $pdf->Text(30, 240, "- Verhouding tussen de ter plaatse verbruikte energie en de ge�njecteerde energie" );
    $pdf->Text(30, 250, "- Verslag conform de verslagen die tijdens de demo verstuurd werden" );
    $pdf->Text(20, 260, "Maandelijks totaal rapport waarin de belangrijkste parameters worden opgelijst, gerangschikt" );
    $pdf->Text(20, 265, "volgens status." );
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "2".$max_page );
    
    /************************************************  P2 */
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 20, "Bv. :" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    
    $pdf->SetXY( 20, 30);
    $pdf->Cell( 80, 7, "Gemiddeld % afwijking", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 30);
    $pdf->Cell( 35, 7, "7,62", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 30);
    $pdf->Cell( 20, 7, "%", "LRBT", 0, "L", true);
    
    $pdf->SetXY( 20, 37);
    $pdf->Cell( 80, 7, "Totale afwijking", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 37);
    $pdf->Cell( 35, 7, "15 364,56", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 37);
    $pdf->Cell( 20, 7, "kWh", "LRBT", 0, "L", true);
    
    $pdf->SetXY( 20, 44);
    $pdf->Cell( 80, 7, "Waarde GSC", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 44);
    $pdf->Cell( 35, 7, "330", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 44);
    $pdf->Cell( 20, 7, "Euro", "LRBT", 0, "L", true);
    
    $pdf->SetXY( 20, 51);
    $pdf->Cell( 80, 7, "Waarde totale afwijking", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 51);
    $pdf->Cell( 35, 7, "4 950,00", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 51);
    $pdf->Cell( 20, 7, "Euro", "LRBT", 0, "L", true);
    
    $pdf->SetXY( 20, 58);
    $pdf->Cell( 80, 7, "kWh/kWp Smart Grafiek", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58);
    $pdf->Cell( 35, 7, "915", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58);
    $pdf->Cell( 20, 7, "kWh/kWp", "LRBT", 0, "L", true);
    
    $lijn = 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Gemiddelde 3 laatste maanden", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "15,87", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "%", "LRBT", 0, "L", true);
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Afwijking laatste maand", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "3825,24", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "kWh", "LRBT", 0, "L", true);
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Afwijking percentueel laatste maand", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "19,95", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "%", "LRBT", 0, "L", true);
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Aantal maanden met negatieve afwijking", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "6", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "", "LRBT", 0, "L", true);
    
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Aantal metingen binnen bij VREG", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "11", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "", "LRBT", 0, "L", true);
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Verbruik", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "107000", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "49%", "LRBT", 0, "R", true);
    
    $lijn += 7;
    $pdf->SetXY( 20, 58+$lijn);
    $pdf->Cell( 80, 7, "Injectie", "LRBT", 0, "L", true);
    $pdf->SetXY( 100, 58+$lijn);
    $pdf->Cell( 35, 7, "110000", "LRBT", 0, "R", true);
    $pdf->SetXY( 135, 58+$lijn);
    $pdf->Cell( 20, 7, "51%", "LRBT", 0, "R", true);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text(20, 130, "Voorwaarden" );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text(20, 135, "Futech gebruikt voor deze dienst enkel de gegevens van de VREG. Futech kan in geen geval " );
    $pdf->Text(20, 140, "aansprakelijk gesteld worden voor de correctheid van deze gegevens." );
    
    $pdf->Text(20, 150, "Indien de VREG beslist om geen gegevens meer te publiceren kan er onmogelijk een rapport " );
    $pdf->Text(20, 155, "opgemaakt worden. Indien de gegevens op de VREG website laattijdig gepubliceerd worden zal het " );
    $pdf->Text(20, 160, "rapport ook pas later verstuurd worden." );
    
    $pdf->Text(20, 170, "Enkel voor installaties >10kW." );
    
    $pdf->Text(20, 180, "De klant dient de volgende gegevens te delen met Futech voor de volledige duurtijd van de " );
    $pdf->Text(20, 185, "samenwerking:" );
    
    $pdf->Text(20, 195, "- PVZG nummer" );
    $pdf->Text(20, 205, "- mb nummer" );
    $pdf->Text(20, 215, "- Paswoord vreg databank" );
    $pdf->Text(20, 225, "- Totaal opgesteld vermogen per PVZG nummer in Wp" );
    $pdf->Text(20, 235, "- Opbrengst factor per PVZG nummer in Wh/Wp" );
    
    $pdf->Text(20, 245, "Indien gewenst kan Futech ook een PR (performance ratio) van de installatie berekenen. Hiervoor hebben we " );
    $pdf->Text(20, 250, "bijkomende info nodig." );
    
    $pdf->Text(20, 260, "Hiervoor zal dan een aparte offerte voor opgemaakt worden." );
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "3".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 20, "Futech kan ook op de site een aantal metingen (eenmalig of continu) uitvoeren. Dit is niet " );
    $pdf->Text(20, 25, "opgenomen in de huidige offerte." );
    
    $pdf->SetFont('Arial', 'I', 13);
    $pdf->SetTextColor(13,35,61); 
    $pdf->Text(20, 45, "Zilver :" );
    
    $pdf->Image('../pdf/mon/ilumen.jpg', 40, 35, 24, 10 );
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(20, 50, "Technische analyse van het project. Hier gaan we zelf hardware gaan toevoegen aan het project om " );
    $pdf->Text(20, 55, "alle omvormers apart te kunnen uitlezen. Aan de hand van deze informatie gaan we gaan kijken of " );
    $pdf->Text(20, 60, "dat alle strings correct werken. " );
    
    $pdf->Text(20, 70, "Indien er een string opmetingsplan aanwezig is kunnen we dit gaan uploaden zodat er bij eventuele" );
    $pdf->Text(20, 75, "interventies onmiddellijk een rapport wordt gemaakt met de vermelding waar het probleem zich " );
    $pdf->Text(20, 80, "bevindt." );
    
    $pdf->Image('../pdf/mon/zilver1.jpg', 20, 85, 170, 110 );
    $pdf->Image('../pdf/mon/zilver2.jpg', 20, 200, 170, 60 );
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "4".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 20, "Alsook bieden we de mogelijkheid om automatisch alarmeringsmails te ontvangen. Hier hebben we " );
    $pdf->Text(20, 25, "een uitgebreid platform voor dat rekening houdt met de ligging van de panelen, eventuele " );
    $pdf->Text(20, 30, "schaduwobjecten, locatie, degradatie, seizoenen,�" );
    
    $pdf->Text(20, 40, "Er wordt een alarmeringsmail verstuurd bij:" );
    
    $pdf->Text(30, 50, "1.	Onvoorziene afwijkingen" );
    $pdf->Text(30, 55, "2.	Status problemen" );
    $pdf->Text(30, 60, "3.	Geen data" );
    
    $pdf->Text(20, 70, "Bij volledige uitval wordt er een sms gestuurd." );
    
    $pdf->Text(20, 80, "Bv. :" );
    $pdf->Image('../pdf/mon/alarm.jpg', 20, 85, 174, 62 );
    
    $pdf->SetFont('Arial', 'B', 11); 
    $pdf->Text(20, 160, "Zilver plus:" );
    
    $pdf->Image('../pdf/mon/ilumen.jpg', 50, 150, 24, 10 );
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->Text(20, 165, "Hier kan aan toegevoegd worden als optie:" );
    $pdf->Text(30, 175, "1.	Pyranometer kip & zonen -> weermodule" );
    $pdf->Text(30, 180, "2.	PR a.d.h.v. KMI gegevens" );
    $pdf->Text(30, 185, "3.	Interventiemodule zodat u alle interventies in de database kan bijhouden. Deze komen " );
    $pdf->Text(30, 190, "dan te staan op het brons-verslag zoals u kan zien op het voorbeeld in bijlage ��n." );
    $pdf->Text(30, 195, "Vb koppeling weermodule:" );
    
    $pdf->Image('../pdf/mon/pyrano.jpg', 35, 200, 100, 72 );
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "5".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', 'BUI', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 20, "Hierna volgende producten (goud en platinum) worden enkel aangeboden na effectief een " );
    $pdf->Text(20, 25, "plaatsbezoek en na akkoord van het technical management. Hier gaan we dus eerst na moeten ");
    $pdf->Text(20, 30, "gaan of dat de producten voldoen aan onze vereiste kwaliteits standaard! Dus nogmaals, onder ");
    $pdf->Text(20, 35, "voorbehoud van goedkeuring van de technische directie.");
    
    $pdf->SetFont('Arial', 'I', 13);
    $pdf->SetTextColor(13,35,61); 
    $pdf->Text(20, 50, "Goud:" );
    
    $pdf->Image('../pdf/mon/ilumen.jpg', 40, 40, 24, 10 );
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(20, 55, "Brons+ zilver+ diensten contract. (in de offerte word enkel de meerkost vermeld)");
    
    $pdf->Text(20, 65, "Hier wordt het project van op afstand gemonitored en indien er problemen zijn, zullen wij deze ");
    $pdf->Text(20, 70, "kosteloos oplossen. Enkel de materialen die noodzakelijk zijn voor de reparatie worden aangerekend ");
    $pdf->Text(20, 75, "aan de hand van de catalogusprijzen van de producent.");
    
    $pdf->Text(20, 85, "Prijslijsten van specifieke omvormer, automaten, panelen,� kan u op aanvraag verkrijgen.");
    
    $pdf->Text(20, 90, "Te leveren diensten:");
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text(20, 100, "Jaarlijkse inspectie en preventief onderhoud:");
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text(30, 110, "- Jaarlijks zal de PV installatie aan een grondige inspectie onderworpen worden.");
    
    $pdf->Text(40, 120, "a. De zonnepanelen worden aan een visuele inspectie onderworpen.");
    $pdf->Text(40, 130, "b. Aan de hand van thermografie wordt onderzocht of er hotspots in");
    $pdf->Text(40, 140, "de elektrische installatie aanwezig zijn.");
    
    $pdf->Text(30, 150, "- Indien nodig zal preventief onderhoud gepleegd worden (o.a. uitkuisen");
    $pdf->Text(30, 160, "ventilatoren van de omvormers).");
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text(20, 170, "Correctief onderhoud & reparaties:");
    
    $pdf->SetFont('Arial', '', 11);

    $pdf->Text(30, 180, "- Wanneer er gebreken worden geconstateerd door de monitoring,");
    $pdf->Text(30, 190, "zal er correctief onderhoud gepleegd worden. Als gebreken");
    $pdf->Text(30, 200, "worden onder meer aanzien:");
    $pdf->Text(30, 210, "a. Gebreken die een verminderd elektrisch rendement van de");
    $pdf->Text(30, 220, "installatie met zich mee brengen.");
    
    $pdf->Text(40, 230, "I. Zonnepanelen die minder vermogen leveren dan verwacht");
    $pdf->Text(40, 240, "volgens de voorziene vermogensgarantie van de fabrikant;");
    $pdf->Text(40, 250, "II. Omvormers die een verminderd vermogen leveren");
    $pdf->Text(40, 260, "III. Een verminderde energieopbrengst op ��n of meerdere strings van de installatie");
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "6".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/doc_footer.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', 'I', 13);
    $pdf->SetTextColor(13,35,61); 
    $pdf->Text(20, 20, "Platinum:" );
    
    $pdf->Image('../pdf/mon/ilumen.jpg', 40, 10, 24, 10 );
    
    $pdf->SetFont('Arial', '', 11); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(20, 25, "Goud + materialen (in de offerte werd enkel de meerkost vermeld)" );
    
    $pdf->Text(20, 35, "Indien het project goedgekeurd wordt door het technisch comit� kunnen we het contract gaan " );
    $pdf->Text(20, 40, "uitbreiden naar een all in contract." );
    
    $pdf->Text(20, 50, "Hierdoor kunnen we effectief de opbrengst gaan garanderen aan de hand van volgende formule:" );
    
    $pdf->Text(20, 60, "CB = max (GO * Ijaar / Iref � EVF - WO; 0) * (GSC + GS).");
    
    $pdf->Text(35, 65, "Met:");
    $pdf->Text(35, 70, "CB = Compensatie betaling");
    $pdf->Text(35, 75, "GO = Gegarandeerde opbrengst");
    $pdf->Text(35, 80, "Ijaar = Werkelijke instraling in het desbetreffende jaar gemeten in het");
    $pdf->Text(35, 85, "dichtst bijzijnde offici�le waarnemingsstation op een horizontaal vlak in");
    $pdf->Text(35, 90, "kWh/m�.j");
    $pdf->Text(35, 95, "Iref = de referentie instraling in hetzelfde station op een horizontaal vlak in");
    $pdf->Text(35, 100, "kWh/m�.j");
    $pdf->Text(35, 105, "EVF = Externe verlies factoren (overmacht van de netbeheerder, externe ");
    $pdf->Text(35, 110, "factoren, storm, werken MS of LS,�)");
    $pdf->Text(35, 115, "WO = Werkelijke Opbrengst, zijnde de netto geproduceerde energie zoals");
    $pdf->Text(35, 120, "gemeten door de monitoring.");
    $pdf->Text(35, 125, "GSC = Waarde van de Groene Stroom Certificaten ( �/MWh, conform AREI ");
    $pdf->Text(35, 130, "keuring)");
    $pdf->Text(35, 135, "GS = Gemiddelde waarde injectie Stroom in dat jaar");
    
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "7".$max_page );
    
    //************************************************  P2
    /*
    $pdf = new FPDI();
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    */
    
    $boiler_prod = array();
    switch( $mon->cus_keuze_pakket )
    {
        case 1 :
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 253; // brons opstart
            }    
            $boiler_prod[] = 254; // brons mnd
            break;
        case 2 :
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 253; // brons opstart
            }    
            $boiler_prod[] = 254; // brons mnd
            
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 255; // zilver opstart
            }
            $boiler_prod[] = 256; // zilver mnd
            
            break;
        case 3 :
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 253; // brons opstart
            }    
            $boiler_prod[] = 254; // brons mnd
            
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 255; // zilver opstart
            }
            $boiler_prod[] = 256; // zilver mnd
        
            $boiler_prod[] = 258; // goud mnd
            
            break;
        case 4 :
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 253; // brons opstart
            }    
            $boiler_prod[] = 254; // brons mnd
            
            if( $mon->cus_mon_actief == '0' )
            {
                $boiler_prod[] = 255; // zilver opstart
            }
            $boiler_prod[] = 256; // zilver mnd
        
            $boiler_prod[] = 258; // goud mnd
            
            $boiler_prod[] = 259; // platinum mnd
            
            break; 
    }
    
    /*
    echo "<pre>";
    var_dump( $mon );
    echo "</pre>";
    */
    
    $euro_arr = array();
    
	$pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/distri_bon_leegfac.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// Tonen van het soort document
	$pdf->SetFont('eurosti', '', 16);
	$pdf->SetTextColor(0,0,0);
	
	$doc_nummer = "";
	$soort_b = "Offerte";
	$pdf->Text(30, 39, $soort_b );

	// tonen van het documents nr
	$pdf->SetFont('eurosti', '', 10);
	$pdf->Text(16.25, 59.25, "Offerte nr"  );
	
	// bepalen van de nummer
    $q_zoek_nr = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_date LIKE '" . date('Y') . "%'");
    $nrke = 0;
	while( $nr = mysqli_fetch_object($q_zoek_nr) )
    {
        $nrke1 = explode(".", $nr->cf_file);
        $nrke2 = explode("_", $nrke1[0]);
        
        if( $nrke < $nrke2[2] )
        {
            $nrke = $nrke2[2];
        }
    }
	
	$doc_nummer = (int)$nrke+1;
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
		
		if( !empty( $klant->cus_btw ) )
		{
			$pdf->Text(40, 72.75, $klant->cus_btw );
		}
	}
	
	$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
    $datum = date('d') . "-" . date('m') . "-" . date('Y');
    
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $doc_nummer );
	
	$pdf->SetFont('eurosti', '', 10);
	
    
    
	$extra_offset = 0;
    $excl = 0;
    
	foreach( $boiler_prod as $key => $dis )
	{
        $pdf->SetXY( 44, 112+$extra_offset );
		
		// soort van het artikel ophalen
		$artikelnaam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art WHERE ka_id = " . $dis));
		$pdf->Cell( 102, 5, html_entity_decode($artikelnaam->ka_art, ENT_QUOTES), 0, 1,'L');
		
		$soort = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art_soort WHERE as_id = " . $artikelnaam->ka_as_id));
		$pdf->SetXY( 18, 112+$extra_offset );
		$pdf->Cell( 25, 5, $soort->as_doc, 0, 1,'L');
		
        $aantal = 1;
        
        if( $dis == 253 )
        {
            $aantal = $mon->cus_aant_pvz;
        }
        
        if( $dis == 255 )
        {
            $aantal = $mon->cus_aant_wb;
        }
        
        if( $dis == 254 || $dis == 256 || $dis == 258 || $dis == 259 )
        {
            $aantal = 12;
        }
        
        switch( $dis )
        {
            case 253 :
                $prijs_mon = $mon->cus_ops_brons;
                break;
            case 254 :
                $prijs_mon = $mon->cus_mnd_brons * 12;
                break;
            case 255 :
                $prijs_mon = $mon->cus_ops_zilver;
                break;
            case 256 :
                $prijs_mon = $mon->cus_mnd_zilver * 12;
                break;
            case 258 :
                $prijs_mon = $mon->cus_mnd_goud * 12;
                break;
            case 259 :
                $prijs_mon = $mon->cus_mnd_platinum * 12;
                break;
        }
        
		$pdf->SetXY( 145, 112+$extra_offset );
        $pdf->Cell( 26, 5, $aantal, 0, 1, 'C');
        
        $pdf->SetXY( 160, 112+$extra_offset );
        $pdf->Cell( 30, 5, number_format( $prijs_mon, 2, ",", " " ), 0, 1, 'R');

        $euro_arr[] = 115.75 + $extra_offset; // artikel euro sign
            
		$extra_offset += 5.5;
        
        $excl += $prijs_mon;
	}
    
    $tekst = array();
    $tekst[] = "per formule wordt de meerkost getoond en niet de absolute kost!";
    
    if( count($tekst) > 0 )
    {
        foreach( $tekst as $t )
        {
            //echo "<br>" . $t;
            
            $extra_offset += 5.5;
            
            $pdf->SetXY( 44, 112+$extra_offset );
    		$pdf->Cell( 102, 5, $t, 0, 1,'L');
        }
    }
    
    /* Switchen naar ander lettertype voor het weergeven van de euro teken */
    $pdf->setFont('Arial', '', 10);
    
    $euro_arr[] = 237.5; // subtot
    $euro_arr[] = 253.5; // eind tot 

    foreach( $euro_arr as $euro )
    {
        $pdf->text( 195, $euro, "EUR" );
    }
    
    $pdf->SetFont('eurosti', '', 10);
    
	// btw
	$btw = "";
	$incl = 0;
	// 21 %
	$incl = $excl * 1.21;
	$btw = $incl - $excl;

	
	$pdf->SetXY( 167, 234 );
	$pdf->Cell(26, 5, number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
	// 21 %
    $euro_arr[] = 245.5; // btw
    
	$pdf->Text(159, 246, "21%" );
	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
	$pdf->SetXY( 163, 242.25 );
	$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');

	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, number_format($incl, 2, ",", " " ), 0, 1, 'R');
	
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "8".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p1.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', 'BU', 13);
    $pdf->SetTextColor(13,35,61);
    $pdf->Text( 20, 17, "Bijlage");
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "9".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p2.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "10".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p3.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "11".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p4.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "12".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p5.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "13".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p6.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "14".$max_page );
    
    //************************************************  P2
    //$pdf = new FPDI();
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/mon/demo_p7.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    // footer
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 199, 290, "15".$max_page );
    
    //************************************************ force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('mon_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = 'mon_offerte_'. $doc_nummer .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output('mon_offerte_'. $doc_nummer .'.pdf', $output);	
	}    
}

function auto_offerte_boiler($output, $cus_id, $cap, $col, $dak, $gebruik, $huidig, $doorgang, $woning, $comp, $voor2006, $gem_premie, $verw)
{
    /*
    echo "<br/>cus_id : " . $cus_id;
    echo "<br/>cap : " . $cap;
    echo "<br/>col : " . $col;
    echo "<br/>dak : " . $dak;
    echo "<br/>gebruik : " . $gebruik;
    echo "<br/>huidig : " . $huidig;
    echo "<br/>doorgang : " . $doorgang;
    echo "<br/>woning : " . $woning;
    echo "<br/>comp : " . $comp;
    echo "<br/>verw : " . $verw;
    */
    
    $woning5j = $woning;
    
    $datum = date('d') . "-" . date('m') . "-" . date('Y');
    
    $daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    $daksoorten[11] = "Gevelmontage";
    $daksoorten[12] = "Grond installatie";
    
	require_once "../inc/fpdf.php";
	require_once "../inc/fpdi.php";
    
    $pdf = new FPDI();

    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $cus_id));
    $boiler = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_boiler WHERE cus_id = " . $cus_id));
    $boiler_entry = $boiler;
    
	$cus_id = $klant->cus_id;	

    /************************* PAGINA 1 *****************************************/
	$pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/boi_pag1.pdf');

	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    $pdf->SetFont('Arial', '', 12); 
    $pdf->SetTextColor(0,0,0);
    
    $regelmin = 164;
    $van_links = 30;
    //$pdf->Text($van_links, 142, "Datum offerte : " . date('d') . "-" . date('m') . "-" . date('Y')  );
    
    $pdf->Text($van_links, 152, ucfirst( html_entity_decode( $klant->cus_naam, ENT_QUOTES ) ) );
    $pdf->Text($van_links, 158, ucfirst( html_entity_decode( $klant->cus_straat, ENT_QUOTES )) . " " . $klant->cus_nr );
    $pdf->Text($van_links, 164, $klant->cus_postcode . " " . html_entity_decode( $klant->cus_gemeente, ENT_QUOTES ) );
    
    if( !empty( $klant->cus_email ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, $klant->cus_email );
    }
    
    if( !empty( $klant->cus_tel ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "Tel. : " . $klant->cus_tel );
    }
    
    if( !empty( $klant->cus_gsm ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "GSM : " . $klant->cus_gsm );
    }
    
    if( !empty( $klant->cus_btw ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "BTW nr : " . $klant->cus_btw );
    }
    
    // boiler gedeelte
    $regelmin = 142;
    $van_links = 130;
    $lijn_factor = 5;
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text($van_links, $regelmin, "Aantal personen : " . $boiler->cus_aant_pers );
    $regelmin += $lijn_factor;
    
    $gebruik1 = "";
    
    switch( $gebruik )
    {
        case "sanitair" :
            $gebruik1 = "Sanitair";
            break;
        case "sanitair_cv" :
            $gebruik1 = "Sanitair en cv";
            break;
        case "zwembad" :
            $gebruik1 = "Zwembad";
            break;
    }
    
    $pdf->Text($van_links, $regelmin, "Gebruik : " . $gebruik1 );
    $regelmin += $lijn_factor;
    
    $huidig1 = "";
    switch( $huidig )
    {
        case "geen" :
            $huidig1 = "Geen";
            break;
        case "gas" :
            $huidig1 = "Gas";
            break;
        case "mazout" :
            $huidig1 = "Mazout";
            break;
        case "elec" :
            $huidig1 = "Elektriciteit";
            break;
        case "andere" :
            $huidig1 = "Andere";
            break;
    }
    
    $pdf->Text($van_links, $regelmin, "Huidige boiler : " . $huidig1	 );
    $regelmin += $lijn_factor;
    
    if( !empty( $cap ) )
    { 
        $cap1 = $cap . " L";
    }
    
    $pdf->Text($van_links, $regelmin, "Capaciteit huidige boiler : " . $cap1 );
    $regelmin += $lijn_factor;
    
    $doorgang1 = "";
    
    if( $doorgang == '0' )
    {
        $doorgang1 = "Neen";
    }else
    {
        $doorgang1 = "Ja";
    }
    
    $pdf->Text($van_links, $regelmin, "Doorgangen vrij? " . $doorgang1 );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Type dak : " . $daksoorten[$dak] );
    $regelmin += $lijn_factor;
    
    $verwarming = array();
                    
    $verwarming[1] = "Radiatoren Lage Temperatuur";
    $verwarming[2] = "Radiatoren Hoge Temperatuur";
    $verwarming[3] = "Convectoren";
    $verwarming[4] = "Vloerverwarming";
    $verwarming[5] = "Warmte Pomp Lucht-lucht";
    $verwarming[6] = "Warmte Pomp Lucht-water";
    $verwarming[7] = "Warmte Pomp Water-water";
    $verwarming[8] = "Andere";
    
    $pdf->Text($van_links, $regelmin, "Soort verwarming : " . $verwarming[$verw] );
    $regelmin += $lijn_factor;
    
    if( $boiler->cus_factor == 0 || $boiler->cus_factor == "" || empty($boiler->cus_factor) )
    {
        $boiler->cus_factor = $klant->cus_kwhkwp;
    }
    
    
    $pdf->Text($van_links, $regelmin, "Opbrengst factor : " . $boiler->cus_factor );
    $regelmin += $lijn_factor;
    
    $hoek_z = 0; 
    
    if( $boiler->cus_hoek_z == 0 || empty( $boiler->cus_hoek_z ) )
    {
        $hoek_z = $klant->cus_hoek_z;
    }else
    {
        $hoek_z = $boiler->cus_hoek_z;
    } 
    
    $pdf->Text($van_links, $regelmin, "Hoek panelen met het zuiden: " . $hoek_z . "�" );
    $regelmin += $lijn_factor;
    
    $hoek_p = 0; 
    if( $boiler->cus_hoek_p == 0 || empty( $boiler->cus_hoek_p ) )
    {
        $hoek_p = $klant->cus_hoek;
    }else
    {
        $hoek_p = $boiler->cus_hoek_p;
    }
    
    $pdf->Text($van_links, $regelmin, "Hoek van de panelen : " . $hoek_p . "�" );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Compensatiefactor : " . $comp );
    $regelmin += $lijn_factor;
    
    $woning_text = 0;
    switch( $woning )
    {
        case 6 :
            $woning_text = "Ja";
            break;
        case 21 :
            $woning_text = "Neen";
            break;
    }
    
    $pdf->Text($van_links, $regelmin, "Woning ouder dan 5j : " . $woning_text );
    $regelmin += $lijn_factor;
    
    switch( $voor2006 )
    {
        case 0 :
            $voor2006_1 = "Neen";
            break;
        case 1 :
            $voor2006_1 = "Ja";
            break;
    }
    
    $pdf->Text($van_links, $regelmin, "Aangesloten op net voor 2006 : " . $voor2006_1 );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Voorstel # Collectoren : " . $boiler->cus_vs_col );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Voorstel Cap. Boiler : " . $boiler->cus_vs_cap . " L" );
    $regelmin += $lijn_factor;
    
    $pdf->Text($van_links, $regelmin, "Opstelling : " . $boiler->cus_lanpor );
    $regelmin += $lijn_factor;
    
    $aansluiten_cv = "";
    switch( $boiler->cus_cv )
    {
        case '0' :
            $aansluiten_cv = "Neen";
            break;
        case '1' :
            $aansluiten_cv = "Ja";
            break;
    }
    
    $pdf->Text($van_links, $regelmin, "Aansluiten op ketel : " . $aansluiten_cv );
    $regelmin += $lijn_factor;
    
    //$pdf->Image('../pdf/boiler/pag1.jpg', 55, 215, 105, 60 );
    
    $pdf->Text(30, 245, "Tijdelijke actie geldig zolang de voorraad strekt!");
    
    $pdf->SetFont('Arial', '', 26);
    //$pdf->Text(50, 265, "Lentekorting bij Futech");
    $pdf->Text(30, 265, "Herfstkortingen bij Futech");
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->Text(30, 270, "Geldig tot " . date("d-m-Y", mktime(0,0,0,date('m'),date('d')+14,date('Y'))));
    
    $pdf->SetFont('Arial', '', 10); 
    
    //   
    
    $pdf->Text( 30, 225, "Koop nu een zonneboiler bij futech en " );
    $pdf->Text( 30, 230, "krijg gratis een 32inch led-tv. Op deze manier bespaart u" );
    $pdf->Text( 30, 235, "niet enkel wanneer u doucht maar ook wanneer u tv kijkt." );
    
    
    $pdf->Image('../pdf/boiler_tv.jpg', 30, 195, 35, 25 );
    //$pdf->Image('../pdf/batibouw.jpg', 90, 235, 30, 20 );
    
    
    //$pdf->Text( 50, 275, "Voor elke overeenkomst schenkt Futech 100 euro aan meneer Konijn." );
    
    $max_page = "/18";
    $pagina=1;
    
    // footer
    $pdf->SetFont('Arial', '', 10); 
    $pdf->Text( 20, 290, $pagina.$max_page );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 2 *****************************************/
    
    $excl = 0;
    $exclude_arr = array();
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
    $euro_arr = array();
    
	$pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/distri_bon_leegfac.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// Tonen van het soort document
	$pdf->SetFont('eurosti', '', 16);
	$pdf->SetTextColor(0,0,0);
	
	$doc_nummer = "";
	$soort_b = "Offerte";
	$pdf->Text(30, 39, $soort_b );

	// tonen van het documents nr
	$pdf->SetFont('eurosti', '', 10);
	$pdf->Text(16.25, 59.25, "Offerte nr"  );
	
	// bepalen van de nummer
    $q_zoek_nr = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_date LIKE '" . date('Y') . "%'");
    $nrke = 0;
	while( $nr = mysqli_fetch_object($q_zoek_nr) )
    {
        $nrke1 = explode(".", $nr->cf_file);
        $nrke2 = explode("_", $nrke1[0]);
        
        if( $nrke < $nrke2[2] )
        {
            $nrke = $nrke2[2];
        }
    }
	
	$doc_nummer = (int)$nrke+1;
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
		
		if( !empty( $klant->cus_btw ) )
		{
			$pdf->Text(40, 72.75, $klant->cus_btw );
		}
	}
	
	$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
	//$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $doc_nummer );
	
	$pdf->SetFont('eurosti', '', 10);
	
    $boiler_prod = array();
    $boiler_prod[] = 227;
    
    /*
    $boiler_prod[] = 252; // 200l
    $boiler_prod[] = 228; // 300
    $boiler_prod[] = 251; // 500 
    */
    
    switch ( $cap )
    {
        case "200" :
            $boiler_prod[] = 252;
            break;
        case "310" :
            $boiler_prod[] = 228;
            break;
        case "500" :
            $boiler_prod[] = 251;
            break;   
    }
    
    $boiler_prod[] = 229; // elek verw 2.5kw
    $boiler_prod[] = 230; // temp sensor
    $boiler_prod[] = 232; // dubbel flex
    $boiler_prod[] = 233; // connectie set 
    $boiler_prod[] = 234; // magne anode
    $boiler_prod[] = 235; // venteil 6b 
    $boiler_prod[] = 237; // 25l glucol 
    $boiler_prod[] = 238; // auto solar ont 
    $boiler_prod[] = 239; // expansite tank set 
    $boiler_prod[] = 240; // solar regelaar
    
    $boiler_prod[] = 241; // natloper
    
    if( $dak == 1 )
    {
        $boiler_prod[] = 242; // plat dak    
    }else
    {
        $boiler_prod[] = 246; // hellend dak    
    }
    
    // ophalen van de prijs.
    $prijs = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_prijs_boiler WHERE col = " . $col));
    
    /*
    echo "<pre>";
    var_dump( $prijs );
    echo "</pre>";
    */
    
    $tmp = "p_" . $cap;
    
    $p_ex = $prijs->$tmp;
    
	$extra_offset = 0;
	foreach( $boiler_prod as $key => $dis )
	{
        $pdf->SetXY( 44, 112+$extra_offset );
		
		// soort van het artikel ophalen
		$artikelnaam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art WHERE ka_id = " . $dis));
		$pdf->Cell( 102, 5, $artikelnaam->ka_art, 0, 1,'L');
		
		$soort = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art_soort WHERE as_id = " . $artikelnaam->ka_as_id));
		$pdf->SetXY( 18, 112+$extra_offset );
		$pdf->Cell( 25, 5, $soort->as_doc, 0, 1,'L');
		
		$pdf->SetXY( 145, 112+$extra_offset );
		
        if( $dis == 227 )
        {
            $pdf->Cell( 20, 5, $col, 0, 1, 'R');
        }else
        {
            $pdf->Cell( 20, 5, 1, 0, 1, 'R');    
        }
/*
		$pdf->SetXY( 167, 112+$extra_offset );
        
        
        if( $dis == 227 )
        {
            $pdf->Cell( 26, 5, number_format( $p_ex, 2, ",", " " ), 0, 1, 'R');
            $euro_arr[] = 115.75 + $extra_offset; // artikel euro sign
            
        }
        */
        
		$extra_offset += 5.5;
	}
    
    /*
    echo "<br>" . $gebruik ;
    echo "<br>" . $huidig ;
    echo "<br>" . $doorgang;
    */
    
    $tekst = array();
    
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_offerte_instellingen"));
    
    $prijs_cv = 0;
    
    if( $boiler->cus_cv == '1' )
    {
        $prijs_cv = $instellingen->boi_cv;
        $tekst[] = "Aansluiten ketel geldig indien pomp en sturing aanwezig (". number_format( $prijs_cv,2,",","" )."euro)";
    }else
    {
        if( $huidig == "gas" || $huidig == "mazout" )
        {
            //$tekst[] = "Boiler met 2 warmtewisselaars, aansluiting op gas/mazout systeem is niet inbegrepen";
            $tekst[] = "Aansluiting secundaire warmtewisselaar op gas/mazout niet inbegrepen";
        }
    }
    
    if( $gebruik == "sanitair_cv" && $boiler->cus_cv == '0' )
    {
        //$tekst[] = "Boiler met 2 warmtewisselaars, aansluiting op CV systeem is niet inbegrepen";
        $tekst[] = "Aansluiting op CV systeem is niet inbegrepen";
    }
    
    
    
    
    
    
    if( $doorgang == "0" )
    {
        $tekst[] = "Gelieve er voor te zorgen dat de doorgang vrij is!";
    }
    
    $tekst[] = "Indien de oude boiler moet afgevoerd worden";
    $tekst[] = "komt er een kost bij van 100euro excl.";
    
    if( count($tekst) > 0 )
    {
        foreach( $tekst as $t )
        {
            //echo "<br>" . $t;
            
            $extra_offset += 5.5;
            
            $pdf->SetXY( 44, 112+$extra_offset );
    		$pdf->Cell( 102, 5, $t, 0, 1,'L');
        }
    }
    
    // meerkost daksoort toevoegen
    $meerprijs = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_boi_dak WHERE dak_id = " . $dak));
    
    
    $lanpor = 0;
    switch( $boiler->cus_lanpor )
    {
        case "Landscape" :
            $lanpor = $instellingen->boi_landscape * $boiler->cus_vs_col; 
            break;
        case "Portrait" :
            $lanpor = $instellingen->boi_portrait * $boiler->cus_vs_col;
            break;
    }
    
    $excl_ok = $p_ex + $meerprijs->meerprijs + $lanpor + $prijs_cv;
    $excl = ($p_ex * 1.10) + $meerprijs->meerprijs + $lanpor + $prijs_cv;    
    
    /* Switchen naar ander lettertype voor het weergeven van de euro teken */
    $pdf->setFont('Arial', '', 10);
    
    $euro_arr[] = 237.5; // subtot
    $euro_arr[] = 253.5; // eind tot 

    foreach( $euro_arr as $euro )
    {
        $pdf->text( 195, $euro, "EUR" );
    }
    
    $pdf->SetFont('eurosti', '', 10);
    
	// btw
	$btw = "";
	$incl = 0;
	if( $woning5j != "0" )
	{
		if( $woning5j == "6" )
		{
			// 6 %
			$incl = $excl * 1.06;
            $incl_ok = $excl_ok * 1.06;
			$btw = $incl - $excl;
		}
		
		if( $woning5j == "21" )
		{
			// 21 %
			$incl = $excl * 1.21;
            $incl_ok = $excl_ok * 1.21;
			$btw = $incl - $excl;
		}
	}
	
	$pdf->SetXY( 167, 234 );
	$pdf->Cell(26, 5, number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
	if( $woning5j != "0" )
	{
		if( $woning5j == "6" )
		{
			// 6 %
			$pdf->Text(161, 249.5, "6%" );
            $euro_arr[] = 249.5; // btw
			//$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
			$pdf->SetXY( 163, 246 );
			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
		}
		
		if( $woning5j == "21" )
		{
			// 21 %
            $euro_arr[] = 245.5; // btw
            
			$pdf->Text(159, 246, "21%" );
			//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
			$pdf->SetXY( 163, 242.25 );
			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
		}
	}
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, number_format($incl, 2, ",", " " ), 0, 1, 'R');
	
    $pdf->SetFont('Arial', 'B', 10);
    //$pdf->Text( 142, 260, "Lentekorting -10%" );
    $pdf->Text( 142, 260, "Tijdelijke korting -10%" );
    $pdf->Text( 147, 265, number_format($incl_ok, 2, ",", " " ) . " euro incl. btw" );
	//$pdf->Cell(25, 5, number_format($incl, 2, ",", " " ), 0, 1, 'R');
	
    $pdf->SetDrawColor(0,80,180);
    $pdf->Line( 140, 255, 190, 235 );
    
    $pdf->SetDrawColor(0,0,0);
    
	if( $verkl == 1 )
	{
		$verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
		$pdf->SetFont('Arial', '', 9);
		$pdf->SetXY( 20, 221 );
		$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
	}
    
    // footer
    $pagina++;
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 270, $pagina.$max_page );
    $pdf->Text( 75, 270, "Ambachtstraat 18/19, 3980 Tessenderlo" );
	
    /************************************************* PAGINA 3 ***********************************************************/
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    $tekst1 = "Beste,
FUTECH wilt u alvast bedanken in het gestelde vertrouwen. FUTECH stelt u hierbij dan ook graag
project 'ZONNEBOILER' voor. Dit is een vertrouwelijk document tussen FUTECH en u.";
    
    $pdf->SetXY( 18.5, 35 );
    $pdf->MultiCell(0, 5, $tekst1, 0, "L", false);
    
    $titel1 = "Project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 55 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $tekst2 = "FUTECH heeft niet stil gezeten. We hebben de tijd genomen om dit project te bestuderen en een goed 
plan voor u uit te werken. Het doel van dit document is een samenwerking tussen FUTECH en u op te starten.";
    
    $pdf->SetXY( 18.5, 70 );
    $pdf->MultiCell(0, 5, $tekst2, 0, "L", false);
    
    $titel1 = "Visualisatie van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 90 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->Image('../pdf/boiler/pag3.jpg', 26, 105, 159, 86 );
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    
    $pdf->Text( 40, 200, "1. Collector" );
    
    $pdf->Text( 120, 200, "5. Secundaire warmte wisselaar" );
    $pdf->Text( 120, 205, "    gas/mazout/warmtepomp" );
    $pdf->Text( 120, 210, "    => aansluiting niet voorzien in deze offerte" );
    
    $pdf->Text( 40, 215, "2. Boiler / voorraadvat" );
    $pdf->Text( 120, 215, "6. Koud leidingwater" );
    
    $pdf->Text( 40, 230, "3. Circulatiepomp" );
    $pdf->Text( 120, 230, "7. Sanitair warm water" );
    
    $pdf->Text( 40, 245, "4. Regelsysteem" );
    $pdf->Text( 120, 245, "8. Electrisch na-verwarming" );
    
    // footer
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    
    /************************************************* PAGINA 4 ***********************************************************/
    $pdf->AddPage(); 
    
    $titel1 = "Componenten";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 20 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,35,"Zonnecollector");
    
    $pdf->SetFont('Arial', '', 10);
    
    if( $col == 1 )
    {
        $pdf->Text(181,35, $col . " paneel");
    }else
    {
        $pdf->Text(181,35, $col . " panelen");    
    }
    
    $pdf->Text(18.5,40,"Vlakke aluminium plaat waaronder hele fijne buisjes zitten die opgewarmd worden en voor warm water zorgen.");
    $pdf->Text(18.5,45,"Bij een voldoende groot temperatuurverschil tussen de collector en de boiler zal het systeem rondpompen.");
    
    $pdf->Image('../pdf/boiler/pag42.jpg', 56, 70, 8, 18 );
    $pdf->Image('../pdf/boiler/pag41.jpg', 26, 50, 20, 37 );
    
    $pdf->Text(80, 52, "Piek vermogen : 1455");
    $pdf->Text(140, 52, "Efficientie : 77,40%");
    
    $pdf->Text(80, 57, "Bruto opp. : 2,1 m�");
    $pdf->Text(140, 57, "Actief opp. : 1,9 m�");
    
    $pdf->Text(80, 62, "Afmetingen : 2,125*950*83");
    $pdf->Text(140, 62, "Gewicht (leeg) : 34,9 kg");
    
    $pdf->Text(80, 67, "K1 : 6,8");
    $pdf->Text(140, 67, "K2 : 0,007");
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,95,"Expansievat");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(18.5,100,"Het expansievat dient om de overdruk ten gevolge van een (te) hoge temperatuur op de vangen.");
    
    $pdf->Text(45,115,"Dit wordt helaas vaak vergeten en kan zorgen voor een onveilige situatie,");
    $pdf->Text(45,120,"bij Futech zit dit er standaard in vervat.");
    
    $pdf->Image('../pdf/boiler/pag43.jpg', 20, 103, 20, 25 );
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,135,"Warmtewisselaar");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(18.5,140,"Het water wordt primair opgewarmd door middel van de zonnecollectoren.");
    $pdf->Text(18.5,145,"Indien de zon niet/niet voldoende schijnt, zijn er 2 mogelijkheden waarvan ons systeem steeds de efficientste kiest.");
    $pdf->Text(40,150,"1. Elektrisch: vermogen van 2,5 kw standaard (optie : tot 3,5 KW max )");
    $pdf->Text(40,155,"2. Door middel van een secundaire warmtewisselaar zodanig dat u uw bestaand systeem (gas,");
    $pdf->Text(40,160,"mazout of warmtepomp) op de boiler kan aansluiten.  Futech sluit dit standaard niet aan maar deze");
    $pdf->Text(40,165,"voorziening is wel aanwezig indien de klant dit (later) zelf wenst te doen.");
    $pdf->Text(40,170,"3. Seri�le naverwarming is mogelijk indien de doorstromer dit toelaat of de huidige boiler behouden blijft.");
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,180,"Bevestigingsmateriaal");
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(18.5,185,"Roestvrij staal, Aluminium");
    $pdf->Text(18.5,190,"Ge�nstalleerd om meer dan 25 jaar mee te gaan.");
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,200,"Zonneboiler");
    $pdf->Text(118.5,200,"Leidingen");
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('../pdf/boiler/pag44.jpg', 20, 205, 20, 43 );
    
    $pdf->Text(50,205,"Vat van " . $cap . " liter. Ge�soleerd!");
    $pdf->Text(50,210,"Temperatuur uitgaand water : 55� - 60�");
    $pdf->Text(50,215,"Anti-legionella werking");
    $pdf->Text(50,220,"Isolatiewaarde : 40mm rondom");
    
    $pdf->Image('../pdf/boiler/boiler_leiding.jpg', 120, 205, 37, 23 );
    $pdf->Text(120,235,"Dubbel ge�soleerde leidingen");
    $pdf->Text(120,240,"Diameter van 16mm�");
    
    $mm = 0;
    $kg = 0;
    
    switch( $cap )
    {
        case 200 :
            $mm = "580*1364";
            $kg = 85;
            break;
        case 310 :
            $mm = "580*1864";
            $kg = 115;
            break;
        case 500 :
            $mm = "735*1810";
            $kg = 151;
            break;
    }
    
    $pdf->Text(50,225,"Afmetingen : ". $mm ." mm");
    $pdf->Text(50,230,"Gewicht netto : ". $kg ." kg");
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************************************* PAGINA 5 ***********************************************************/
    $pdf->AddPage(); 
    
    $titel1 = "GARANTIES";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 20 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,35,"Op de zonneboiler :");
    $pdf->Text(40,40,"- Een productgarantie van");
    $pdf->Text(100,40,"5 jaar");
    
    $pdf->Text(18.5,45,"Op de zonnecollector :");
    $pdf->Text(40,50,"- Een productgarantie van");
    $pdf->Text(100,50,"10 jaar");
    
    $pdf->Text(18.5,55,"Op de andere onderdelen :");
    $pdf->Text(40,60,"- Een productgarantie van");
    $pdf->Text(100,60,"2 jaar");
    
    $titel1 = "Planning van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 65 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 80, "FASE 0" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 80, "Rendementsberekening" );
    $pdf->Text( 55, 86, "Opbrengstanalyse" );
    $pdf->Text( 55, 92, "Offerte opmaken" );
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 98, "Ondertekenen van het contract" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 105, "FASE 1" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 105, "Technische voorbereiding van het project" );
    $pdf->SetTextColor(6,106,65);
    $pdf->Text( 55, 110, "Financiering van het project" );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 115, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 122, "FASE 2" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 122, "Installatie van het project" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 127, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 134, "FASE 3" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 134, "Aansluiting van het project" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 139, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 146, "FASE 4" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 55, 146, "Hulp bij aanvragen premie netbeheerder" );
    
    $titel1 = "Betalingswijze";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 160 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 173, "Voorschot : 40%" );
    $pdf->Text( 20, 178, "Na plaatsing : 60%" );
    $pdf->Text( 20, 183, "OF" );
    $pdf->Text( 20, 188, "Bij plaatsing : 100%" );
    
    $titel1 = "Geldigheidsduur offerte";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 200 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 215, "De offerte is geldig tot 1 maand na overhandiging." );
    
    $titel1 = "Voorziene uitvoeringstermijn";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 225 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 240, "2 tot 6 weken na ontvankelijkheid van het dossier." );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************************************* PAGINA 6 ***********************************************************/
    $pdf->AddPage(); 
    
    $titel1 = "Rendementsanalyse";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 20 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,35,"Premie :");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(35,35,"De netbeheerder (Infrax, Eandis) geeft een premie voor het plaatsen van een zonneboiler.");
    $pdf->Text(35,40,"Dit is 550 EURO per m� met een maximum van 2.750 EURO.");
    $pdf->Text(35,45,"De Vlaamse Overheid geeft een renovatiepremie van 20% op het aankoopbedrag als u in aanmerking");
    $pdf->Text(35,50,"komt. U kan dit ten alle tijden checken op energiesparen.be. Dit dient u zelf na te kijken en u bent zelf");
    $pdf->Text(35,55,"verantwoordelijk voor het verkrijgen van de premie. Futech biedt u hierbij haar hulp aan.");
    
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text(18.5,65,"Opgelet: De premie is enkel geldig bij bestaande woningen, wooneenheden of woongebouwen aangesloten");
    $pdf->Text(18.5,70,"op het elektriciteitsnet van Eandis voor 1 januari 2006. In geval van nieuwbouw dient de premie  ");
    $pdf->Text(18.5,75,"aangevraagd te worden samen met de E-peil-premie (enkel indien de aanvraag voor een    ");
    $pdf->Text(18.5,80,"stedenbouwkundige vergunning van v��r 1 januari 2012 is).");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(18.5,90,"Kijk voor alle voorwaarden op de website van uw netbeheerder.");
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18.5,100,"Compensatie :");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(45,100,"We gaan ervan uit dat niet alle energie opgewekt uit het systeem ook effectief gebruikt zal");
    $pdf->Text(45,105,"worden. Dit is wanneer er niet voldoende warm water gebruikt wordt en de boiler vol zit, dit is ");
    $pdf->Text(45,110,"vooral in de zomermaanden en vakanties. ");
    
    $minder = 20;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 140-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
    $pdf->SetXY( 115, 140-$minder );
    $pdf->Cell( 35, 5, "    " . $col, "LB", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 145-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 145-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    1.450", "LBT", 0, "L", true);
    $pdf->SetXY( 135, 145-$minder );
    $pdf->Cell( 15, 5, "Watt", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 150-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 150-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format(1450 * $col, 0,"","."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 150-$minder );
    $pdf->Cell( 15, 5, "Watt", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 155-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Investeringsbedrag (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 155-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($excl,0,"", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 155-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(216,157,64);
    $pdf->SetXY( 55, 160-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "UITZONDERLIJKE KORTING", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 160-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $commerciele_korting ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 160-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 165-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 165-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag2,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 165-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    */
    
    $minder += 10;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 170-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (incl. ". $woning ."% BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 170-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $incl, 0, "", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 170-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $incl1 = number_format( $incl, 0, "", "");
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_finalia WHERE min <= '" . $incl1 . "' AND max >= '" . $incl1 ."'");
    if( mysqli_num_rows($q_zoek) == 1 )
    {
        $rec = mysqli_fetch_object($q_zoek);
        
        $looptijd = $rec->looptijd;
        $rente = $rec->rentevoet;
    } 
    
    
    $bedrag_mens = getMensualtiteit( $incl_ok );
    
    $string_mens = "( ofwel ". $bedrag_mens . "� gedurende ".$looptijd; 
    $string_mens1 = "maanden aan ". str_replace(".",",",$rente) ."% )*";
    
    $pdf->Text(20,265, "* Enkel op voorwaarde dat Finalia de goedkeuring geeft" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 150, 145 );
    $pdf->Cell( 60, 5, $string_mens, "", 0, "L", true);
    $pdf->SetXY( 150, 150 );
    $pdf->Cell( 60, 5, $string_mens1, "", 0, "L", true);
    
    
    $pdf->SetTextColor(255,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 170-$minder+5 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Tijdelije korting -10%", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 170-$minder+5 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $incl_ok, 0, "", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 170-$minder+5 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $incl = $incl_ok;
    $excl = $excl_ok;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 180-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 180-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $boiler->cus_factor, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 180-$minder );
    $pdf->Cell( 15, 5, "Wh/Wp", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 185-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 185-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format((1450 * $col * $boiler->cus_factor / 1000 ),0,"",".")  , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 185-$minder );
    $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
    
    $minder -= 5;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 185-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Compensatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 185-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $comp . "%"  , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 185-$minder );
    $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 190-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 190-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $tot_m2 = $col * 1.89;
    $premie = 0;
    
    if( $tot_m2 > 7.5 )
    {
        $premie = 2750;
    }else
    {
        $premie = 550 * $tot_m2;
    }
    
    if( $voor2006 == 0 )
    {
        $premie = 0;
    }
    
    if( $premie > 2750 )
    {
        $premie = 2750;
    }
    
    if( $premie > ($incl_ok/2) )
    {
        $premie = $incl_ok/2;
    }
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 195-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Premie netbeheerder", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 195-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $premie, 0, "", "." ), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 195-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $ren_premie = $excl * 0.2;
    
    if( $gebruik == "zwembad" )
    {
        $ren_premie = 0;
    }
    
    $pdf->SetTextColor(255,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 200-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Renovatiepremie Vlaamse Overheid", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 200-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $ren_premie, 0, "", "." ), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 200-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY( 150, 200-$minder );
    $pdf->Cell( 15, 5, "klant kijkt zelf na of hij", 0, 0, "L", true);
    $pdf->SetXY( 150, 205-$minder );
    $pdf->Cell( 15, 5, "aan de voorwaarde voldoet.", 0, 0, "L", true);
    
    $te_betalen = $incl - $premie - $ren_premie;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 205-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Netto investering", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 205-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 205-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 210-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 210-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 215-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Opbrengsten", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 215-$minder );
    $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
    
    $minder += 5;
    
    $boi_e = mysqli_fetch_object(mysqli_query($conn, "SELECT boi_energie FROM kal_offerte_instellingen"));
    
    $energie = (1450 * $col * $boiler->cus_factor / 1000 ) * ( $comp/100) * $boi_e->boi_energie;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 225-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Besparing energie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 225-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $energie ,0,"", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 225-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 230-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 230-$minder );
    $pdf->Cell( 35, 5, "    " . number_format( $energie ,0,"", "."), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 230-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 235-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 235-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 240-$minder );
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell( 60, 5, "Terugverdientijd met renovatiepremie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 240-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($te_betalen/$energie,1,",",""), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 240-$minder );
    $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    
    $minder -= 5;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 240-$minder );
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell( 60, 5, "Terugverdientijd zonder renovatiepremie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 240-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format(($incl-$premie)/$energie,1,",",""), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 240-$minder );
    $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 245-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 245-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 250-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Voorschot 40%", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 250-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $incl * 0.4, 0, "", " " ), "LBT", 0, "L", true);
    $pdf->SetXY( 134, 250-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 255-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Na oplevering 60%", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 255-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $incl * 0.6, 0, "", " " ), "LBT", 0, "L", true);
    $pdf->SetXY( 134, 255-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $minder -= 5;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 255-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "OF", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 255-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
    $pdf->SetXY( 134, 255-$minder );
    $pdf->Cell( 15, 5, "", "BT", 0, "L", true);
    
    $minder -= 5;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 255-$minder );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Bij installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 255-$minder );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $incl, 0, "", " " ), "LBT", 0, "L", true);
    $pdf->SetXY( 134, 255-$minder );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->Text( 20, 260, "Gelieve na te kijken op de website van www.energiesparen.be of er nog gemeentepremies zijn." );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 7 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/pag7.pdf');
    
    $tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $minder = 21;
    
    $pdf->SetFont('Arial', 'B', 10);
    
    if( $boiler->cus_cv == '0' )
    {
        
        $pdf->Text( 18, 255, "De klant verklaart hiermee dat zijn huidige boiler enkel elektrisch is en dat hij dus aan" );
        $pdf->Text( 18, 260, "de premievoorwaarden voldoet, die hem duidelijk meegedeeld zijn." );
        
        /*
        $pdf->Text( 18, 255, "De klant verklaart en erkent op de hoogte te zijn van de premievoorwaarden waaraan" );
        $pdf->Text( 18, 260, "de installatie voldoet." );
        */
    }
    
    //$pdf->SetFont('Arial', '', 11);
    $pdf->Text( 50, 192.5-$minder, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 202.4-$minder, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 208-$minder, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    //$pdf->SetFont('Arial', '', 10 );
    
    if( !empty($klant->cus_tel) && !empty($klant->cus_gsm) )
    {
        $tel = $klant->cus_tel . " / " . $klant->cus_gsm;    
    }else
    {
        $tel = $klant->cus_tel . $klant->cus_gsm;
    }
    
    $pdf->Text( 50, 213-$minder, $tel );
    
    //$pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 223-$minder, $klant->cus_email );
    
    
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $boiler_entry->cus_acma));
    $pdf->Text( 135, 199.5-$minder, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->Text( 100, 122, $col );
    
    $pdf->Text( 130, 172-$minder, number_format($excl,2,","," ") );
    $pdf->Text( 130, 177.5-$minder, number_format($incl,2,","," "));
    
    //$pdf->Text( 162, 207.75-$minder, $opwoning );
    $pdf->Text( 138, 229-$minder, $daksoorten[ $dak ] );
    
    // footer
    $pdf->SetFont('Arial', 'B', 10);
    
    if( $dak == 1 ) // plat dak
    {
        $tekst_bev = "Rails en driehoeken";    
    }else
    {
        $tekst_bev = "Rails en dakhaken";
    }
    
    $pdf->Text( 55, 131, $cap . "L" );
    
    $pdf->Text( 55, 141, $tekst_bev );
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 8 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/pag8.pdf');
    
    $tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $minder = 5;
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 80, 71-$minder, html_entity_decode($klant->cus_naam, ENT_QUOTES ) ); 
    
    $pdf->Text( 45, 76-$minder, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 45, 81-$minder, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 65, 90-$minder, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 9 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/pag9.pdf');

    $tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina.$max_page );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 10 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/pag10.pdf');

    $tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /************************* PAGINA 11 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/pag11.pdf');

    $tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    
    /************************* PAGINA 12 *****************************************/
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/werkdocument.pdf'); 
    $tplIdx = $pdf->importPage(1); 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 16); 
    $pdf->SetTextColor(0,0,0);
    
    $pdf->Text(25,50,"Opbrengstgarantie - Berekening Termicol Zonneboiler");
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(212,212,212);
    $pdf->SetXY( 25, 60 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 160, 5, "Klantgegevens", "LRBT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 25, 65 );
    
    $geg_klant = "";
    $geg_klant .= html_entity_decode($klant->cus_naam) . " " . html_entity_decode($klant->cus_voornaam) . "\r\n";
    $geg_klant .= $klant->cus_straat . " " . $klant->cus_nr . "\r\n";
    $geg_klant .= $klant->cus_postcode . " " . $klant->cus_gemeente . "\r\n";
    $geg_klant .= trim($klant->cus_tel) . " " . $klant->cus_gsm . "\r\n";
    $geg_klant .= $klant->cus_email . "\r\n";
    $pdf->MultiCell( 160, 5, $geg_klant, 1, "L" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(212,212,212);
    $pdf->SetXY( 25, 100 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 160, 5, "Opbrengstberekening", "LRBT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 25, 105 );
    $pdf->Cell( 80, 5, "Gem. warm water verbruik per persoon", "LRBT", 0, "L", true);
    
    $literperpersoon = 60;
    $pdf->SetXY( 105, 105 );
    $pdf->Cell( 80, 5, $literperpersoon . " liter", "LRBT", 0, "C", true);
    
    
    $pdf->SetXY( 25, 110 );
    $pdf->Cell( 80, 5, "Aantal personen", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 110 );
    $pdf->Cell( 80, 5, $boiler_entry->cus_aant_pers, "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25, 115 );
    $pdf->Cell( 80, 5, "Gemiddeld verbruik per dag", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 115 );
    $pdf->Cell( 80, 5, $literperpersoon * $boiler_entry->cus_aant_pers . " liter", "LRBT", 0, "C", true);
    
    $gem_tmp_leiding = 11;
    $pdf->SetXY( 25, 120 );
    $pdf->Cell( 80, 5, "Gemiddeld temperatuur leidingwater", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 120 );
    $pdf->Cell( 80, 5, $gem_tmp_leiding . "�C", "LRBT", 0, "C", true);
    
    $tmp_nodig = 60;
    $pdf->SetXY( 25, 125 );
    $pdf->Cell( 80, 5, "Benodigde temperatuur", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125 );
    $pdf->Cell( 80, 5, $tmp_nodig . "�C", "LRBT", 0, "C", true);
    
    $regel = 0;
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Totale hoeveelheid water nodig per jaar", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, $literperpersoon * $boiler_entry->cus_aant_pers * 365 . " liter", "LRBT", 0, "C", true);
    
    $temp_leiding = array();
    for( $i=1;$i<13;$i++ )
    {
        switch( $i )
        {
            case 1 : $waarde = $gem_tmp_leiding - 2;
                break;
            case 2 : $waarde = $temp_leiding[1] + 1;
                break;
            case 3 : $waarde = $temp_leiding[2] + 1;
                break;
            case 4 : $waarde = $temp_leiding[3];
                break;
            case 5 : $waarde = $temp_leiding[4] + 1;
                break;
            case 6 : $waarde = $temp_leiding[5] + 1;
                break;
            case 7 : $waarde = $temp_leiding[6];
                break;
            case 8 :
                $waarde = $temp_leiding[5];
                break;
            case 9 :
                $waarde = $temp_leiding[4];
                break;
            case 10 :
                $waarde = $temp_leiding[3];
                break;
            case 11 :
                $waarde = $temp_leiding[2];
                break;
            case 12 :
                $waarde = $temp_leiding[1];
                break;
        }
        $temp_leiding[ $i ] = $waarde;
    }
    
    $factor_maand = array();
    $factor_maand[1]  = 0.0255516840882695;
    $factor_maand[2]  = 0.0499419279907085;
    $factor_maand[3]  = 0.0743321718931475;
    $factor_maand[4]  = 0.111498257839721;
    $factor_maand[5]  = 0.138211382113821;
    $factor_maand[6]  = 0.128919860627178;
    $factor_maand[7]  = 0.141695702671312;
    $factor_maand[8]  = 0.126596980255517;
    $factor_maand[9]  = 0.0882694541231127;
    $factor_maand[10] = 0.0627177700348432;
    $factor_maand[11] = 0.032520325203252;
    $factor_maand[12] = 0.0197444831591173;
    
    $efficientie = 0.9;
    $tot_verm_col = $boiler_entry->cus_vs_col * 1455;
    $opbrengst_per_jaar = $tot_verm_col * $efficientie * ( $boiler_entry->cus_factor / 1000 ) ;
    
    $tot_overschot = 0;
    $energie_nodig_per_jaar = 0;
    
    $calc_arr = array();
    for( $i=1;$i<13;$i++ )
    {
        $calc_arr[ $i ]["kwh_per_dag"] = ($literperpersoon * $boiler_entry->cus_aant_pers) * 4186 * ($tmp_nodig - $temp_leiding[$i] ) / 1000000 / 3.6 ;
        $calc_arr[ $i ]["energie_nodig_per_maand"] =  $calc_arr[ $i ]["kwh_per_dag"] * get_days_in_month($i,date('Y'));
        $calc_arr[ $i ]["kwh_opgewekt_per_dag"] = $opbrengst_per_jaar * ($factor_maand[$i] / get_days_in_month($i,date('Y') ) );
        $calc_arr[ $i ]["overschot_per_dag"] = ( $calc_arr[ $i ]["kwh_opgewekt_per_dag"] - $calc_arr[ $i ]["kwh_per_dag"] < 0 ? 0 : $calc_arr[ $i ]["kwh_opgewekt_per_dag"] - $calc_arr[ $i ]["kwh_per_dag"] );
        $calc_arr[ $i ]["overschot_per_maand"] = $calc_arr[ $i ]["overschot_per_dag"] * get_days_in_month($i,date('Y') );
        
        $tot_overschot += $calc_arr[ $i ]["overschot_per_maand"];
        $energie_nodig_per_jaar += $calc_arr[ $i ]["energie_nodig_per_maand"];
    }
    
    $werkingsgraad_boiler = ( 1 - ( $tot_overschot / $opbrengst_per_jaar ) ) * 100;
    $dekkingsgraad = (( $opbrengst_per_jaar * ($werkingsgraad_boiler/100) ) / $energie_nodig_per_jaar)*100;
    $besparing = ($dekkingsgraad/100) * $energie_nodig_per_jaar;
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Energie nodig", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, number_format($energie_nodig_per_jaar * 3.6, 0, "", "") . " Mj", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Effici�ntie warmtewisselaar en diversen", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, $efficientie * 100 . "%", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Opbrengstfactor adhv ori�ntatie", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, $boiler_entry->cus_factor . " kWh/kWp", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Aantal collectoren", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, $boiler_entry->cus_vs_col, "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Totaal vermogen collectoren", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, $tot_verm_col . " Wp", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 125+$regel );
    $pdf->Cell( 80, 5, "Opbrengst per jaar", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 125+$regel );
    $pdf->Cell( 80, 5, number_format($opbrengst_per_jaar, 0,"","") . " kWh", "LRBT", 0, "C", true);
    
    
    
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(212,212,212);
    
    $kolomb1 = 20;
    $kolomb2 = 30;
    $kolomb3 = 30;
    $kolomb4 = 30;
    $kolomb5 = 30;
    $kolomb6 = 20;
    
    $pdf->SetXY( 25, 170);
    $pdf->Cell( 160, 4, "Overzicht per maand", "LRBT", 0, "C", true);
    
    $pdf->SetFont('Arial', '', 8);
    
    $pdf->SetXY( 25, 174);
    $pdf->Cell( $kolomb1, 8, "Maand", "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25 + $kolomb1, 174);
    $pdf->Cell( $kolomb2, 8, "tot. kWh nodig", "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25 + $kolomb1 + $kolomb2, 174);
    $pdf->Cell( $kolomb3, 8, "kWh opgewekt", "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25 + $kolomb1 + $kolomb2 + $kolomb3, 174);
    $pdf->Cell( $kolomb4, 8, "overschot kWh", "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25 + $kolomb1 + $kolomb2 + $kolomb3 + $kolomb4, 174);
    $pdf->Cell( $kolomb5, 8, "kWh nog nodig", "LRBT", 0, "C", true);
    
    $pdf->SetXY( 25 + $kolomb1 + $kolomb2 + $kolomb3 + $kolomb4 + $kolomb5, 174);
    $pdf->MultiCell( $kolomb6, 4, "Leidingwater temp.", "LRBT", 0, "C", true);
    
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
    
    $pdf->SetFillColor(255,255,255);
    $teller = 0;
    for($i=1;$i<13;$i++)
    {
        $pdf->SetXY( 25, 182+$teller);
        $pdf->Cell( $kolomb1, 4, ucfirst( $maand[$i] ), 1, 0, "L", true);
        
        $pdf->SetXY( 25+$kolomb1, 182+$teller);
        $pdf->Cell( $kolomb2, 4, number_format($calc_arr[ $i ]["energie_nodig_per_maand"], 0, "", ""), 1, 0, "C", true);
        
        $pdf->SetXY( 25+$kolomb1 + $kolomb2, 182+$teller);
        $pdf->Cell( $kolomb3, 4, number_format( $calc_arr[ $i ]["kwh_opgewekt_per_dag"] * get_days_in_month($i,date('Y') ), 0, "", ""), 1, 0, "C", true);
        
        $pdf->SetXY( 25+$kolomb1 + $kolomb2 + $kolomb3, 182+$teller);
        $pdf->Cell( $kolomb4, 4, number_format( $calc_arr[ $i ]["overschot_per_maand"], 0, "", ""), 1, 0, "C", true);
        
        $tmp = $calc_arr[ $i ]["energie_nodig_per_maand"] - $calc_arr[ $i ]["kwh_opgewekt_per_dag"] * get_days_in_month($i,date('Y') );
        
        if($tmp < 0)
        {
            $tmp = 0;
        }
        
        $pdf->SetXY( 25 + $kolomb1 + $kolomb2 + $kolomb3 + $kolomb4, 182+$teller);
        $pdf->Cell( $kolomb5, 4, number_format( $tmp, 0, "", "") , "LRBT", 0, "C", true);
         
        $pdf->SetXY( 25 + $kolomb1 + $kolomb2 + $kolomb3 + $kolomb4 + $kolomb5, 182+$teller);
        $pdf->Cell( $kolomb6, 4, $temp_leiding[$i] . "�C" , "LRBT", 0, "C", true);
        
        $teller += 4;    
    }
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(212,212,212);
    
    $pdf->SetXY( 25, 235);
    $pdf->Cell( 160, 5, "Besluit", "LRBT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(255,255,255);
    
    $regel = 0;
    $regel += 5;
    $pdf->SetXY( 25, 235+$regel );
    $pdf->Cell( 80, 5, "Totale overschot aan energie", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 235+$regel );
    $pdf->Cell( 80, 5, number_format($tot_overschot, 0,"","") . " kWh", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 235+$regel );
    $pdf->Cell( 80, 5, "Werkingsgraad", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 235+$regel );
    $pdf->Cell( 80, 5, number_format( $werkingsgraad_boiler, 0,"","") . "%", "LRBT", 0, "C", true);
    
    $q_upd = mysqli_query($conn, "UPDATE kal_customer_boiler SET cus_comp = '". number_format( $werkingsgraad_boiler, 0,"","") ."' WHERE cus_id = " . $boiler_entry->cus_id . " LIMIT 1") or die( mysqli_error($conn) );
    
    $regel += 5;
    $pdf->SetXY( 25, 235+$regel );
    $pdf->Cell( 80, 5, "Dekkingsgraad", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 235+$regel );
    $pdf->Cell( 80, 5, number_format( $dekkingsgraad, 0,"","") . "%", "LRBT", 0, "C", true);
    
    $regel += 5;
    $pdf->SetXY( 25, 235+$regel );
    $pdf->Cell( 80, 5, "Besparing", "LRBT", 0, "L", true);
    $pdf->SetXY( 105, 235+$regel );
    $pdf->Cell( 80, 5, number_format( $besparing, 0,"","") . " kWh", "LRBT", 0, "C", true);
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 266, $pagina.$max_page );
    $pdf->Text( 75, 266, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    
    /*********************************** PAG 13 ***************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('../pdf/combi1.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->Text( 20, 10, "Enkel op voorwaarde dat finalia de goedkeuring geeft." );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina.$max_page );
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/combi2.pdf');
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina.$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/finalia.pdf');
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina.$max_page );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /*********************************** PAG 13 ***************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('../pdf/overzicht_futech_ok.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina.$max_page );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/boiler/termicol_datasheet.pdf');
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina.$max_page );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('../pdf/friends_actie.pdf'); 
    $tplIdx = $pdf->importPage(1); 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_page);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('boiler_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = 'boiler_offerte_'. $doc_nummer .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output('boiler_offerte_'. $doc_nummer .'.pdf', $output);	
	}
}

function is_leap_year($year)
{
    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
}

// BEGIN TEST OM TE ZIEN WIE DE KLANT GEOPEND HEEFT
if( isset( $_SESSION[ $session_var ]->user_id ) && is_numeric($_SESSION[ $session_var ]->user_id) && $_SESSION[ $session_var ]->user_id > 0 )
{
    //$q_del = "DELETE FROM user_open_cus_id WHERE user_id = " . $_SESSION[ $session_var ]->user_id;
    //mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
}
// EINDE TEST OM TE ZIEN WIE DE KLANT GEOPEND HEEFT
 
$klanten_onder_frans = "25, 28, 29, 31, 32, 1962, 40, 41";
$klanten_onder_frans_arr = array( "25", "28", "29", "31", "32", "1962", "40", "41");

$kleur_grijs = "#ECFFF0";
$kleur_aankoop = "#A14599";
$kleur_huur = "blue";

// door het aanpassen van deze variable krijgen al de gebruikers de melding om het wachtwoord te wijzigen
$switch_change_pwd = 2;

// variable betalingstermijn
$betalings_termijn = 14;

$headers = "From: European Solar Challenge <info@europeansolarchallenge.eu>\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\n";

// START ARRAY ACMA
$acma_arr = array();
$q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE (group_id < 5 OR group_id = 9 ) AND active != '0' ORDER BY voornaam");

if( mysqli_num_rows($q_zoek) > 0 )
{
    
    while($rij = mysqli_fetch_object($q_zoek))
    {
    	$acma_arr[ $rij->user_id ] = $rij->voornaam  ." " . $rij->naam;
    
    }
}
// EINDE ARRAY ACMA

// START ARRAY ACMA
$acma_tel = array();
$q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE (group_id < 5 OR group_id = 9 ) AND active != '0' ORDER BY voornaam");

if( mysqli_num_rows($q_zoek) > 0 )
{
    
    while($rij = mysqli_fetch_object($q_zoek))
    {
    	$acma_tel[ $rij->user_id ]["naam"] = $rij->voornaam  ." " . $rij->naam;
        $acma_tel[ $rij->user_id ]["tel"] = $rij->tel;
    
    }
}
// EINDE ARRAY ACMA

// START ARRAY ACTIVE USERS
$active_users = array();
$q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' AND group_id != 10 ORDER BY voornaam");
while( $rij = mysqli_fetch_object($q_zoek) )
{
    $active_users[ $rij->user_id ]["fullname"] = $rij->voornaam . " " . $rij->naam;
    $active_users[ $rij->user_id ]["voornaam"] = $rij->voornaam;
}
// EINDE ARRAY ACTIVE USERS


function getDir($fac_id, $conn)
{
    $q_rij = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id) or die( mysqli_error($conn) . " " . __LINE__ ) ;
    $rij = mysqli_fetch_object($q_rij);
    
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
    
    if( !empty( $dir ) )
    {
        $dir .= "/";
    }
    
    return $dir;
}

function maakReferte($cus_id, $conn)
{
    if( empty( $conn ) )
    {
        $cus = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));    
    }else
    {
        $cus = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
    }
    
    return str_replace("-", "", substr($cus->cus_date_added, 0, 7) ) . $cus->cus_id;
}

function checkOvereenkomst($waarde)
{
    switch( $waarde )
    {
        case "1" :
        case 1 :
            echo "K";
            break;
        case "2" :
        case 2 :
            echo "V";
            break;
        default :
            echo " ";
            break;
    }
}

// BEGIN VOOR DE METERSTAND BEREKENING
function getMonth( $month )
{
    if( $month > 12 )
    {
        $month -= 12;
    }
    
    return $month;
}

function getCurrentPercentage( $month, $year, $link_futech )
{
    $waarde = 0;
    
    $q_perc = mysqli_query($link_futech,  "SELECT * FROM futech_percentage WHERE month_num = '" . $month . "' AND year_num = '" . $year ."'") or die( mysqli_error($link_futech) );
    
    if( mysqli_num_rows($q_perc) == 0 )
    {
        $q_perc = mysqli_query($link_futech,  "SELECT * FROM futech_percentage WHERE month_num = '" . $month . "' AND year_num = '0' ");
        $q_zoek = mysqli_fetch_object($q_perc);    
    }else
    {
        $q_zoek = mysqli_fetch_object($q_perc);
    }
    
    return $q_zoek->percentage;
} 

function calculateExpectedPower($startPower,$startYear,$monthNumber,$yearNumber,$daysInMonth,$totalNumberDaysInMonth, $link_futech)
{
	$currentYear=$startYear+$yearNumber;
	$degr_factor= 0.005;
	$currentPercentage= getCurrentPercentage($monthNumber,$currentYear, $link_futech);
	$power=$startPower*$currentPercentage/100*pow((1-$degr_factor),$yearNumber)*$daysInMonth/$totalNumberDaysInMonth;
	return $power;
}

function get_months($date1, $date2) 
{ 
    $time1 = strtotime($date1); 
    $time2 = strtotime($date2); 
    $my = date('mY', $time2); 
    $months = array(date('F', $time1)); 
    $f = ''; 
    
    while($time1 < $time2) { 
    $time1 = strtotime((date('Y-m-d', $time1).' +15days')); 
    
    if(date('F', $time1) != $f) { 
    $f = date('F', $time1); 
    
    if(date('mY', $time1) != $my && ($time1 < $time2)) 
    $months[] = date('F', $time1); 
    } 
    
    } 
    
    $months[] = date('F', $time2); 
    return $months; 
} 

// EIDNE VOOR DE METERSTAND BEREKENING

function getmeterstand( $cus, $conn, $link_futech )
{
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus));
    
    // deze klant zoeken uit de db van futech.be
    $UserData = mysqli_fetch_object(mysqli_query($link_futech, "SELECT * FROM UserData WHERE UserEmail = '". $klant->cus_email ."'"));
    $Users = mysqli_fetch_object(mysqli_query($link_futech, "SELECT * FROM Users WHERE UserId = " . $UserData->UserId));
    $futech_client = mysqli_fetch_object(mysqli_query($link_futech, "SELECT * FROM futech_client WHERE user_id = " . $UserData->UserId));
    
    $startPower = $futech_client->expected_power;
    $startYear_ymd = explode("-", substr($futech_client->start_date, 0, 10) );
    $startYear = $startYear_ymd[0];
    $monthNumber = $startYear_ymd[1];
    $yearNumber = 0;
    $daysInMonth = $startYear_ymd[2];
    $totalNumberDaysInMonth = date("t", mktime( 0, 0, 0, (int)$startYear_ymd[1], (int)$startYear_ymd[2], (int)$startYear_ymd[0]) );
    
    $arei = substr( $klant->cus_arei_datum, 0, 10 );
    
    // aantal maanden berekenen van arei keuring tot nu.
    $getmonths = get_months( $startYear_ymd[0] . "-" . $startYear_ymd[1] . "-" . $startYear_ymd[2] , date('Y') . "-" . date('m') . "-" . date('d') );
    $getmonths = count($getmonths) - 2;
    
    $cum_som = 0;
    
    if( $getmonths > 0 )
    {
        $cum_som = calculateExpectedPower($startPower,$startYear,$monthNumber,$yearNumber,$totalNumberDaysInMonth-$startYear_ymd[2],$totalNumberDaysInMonth, $link_futech);
    }else
    {
        $cum_som = calculateExpectedPower($startPower, $startYear, $monthNumber, $yearNumber, $totalNumberDaysInMonth-$startYear_ymd[2], $totalNumberDaysInMonth, $link_futech);
        
        $aantal_dagen = date('d')-$startYear_ymd[2];
        
        if( $aantal_dagen == 0 )
        {
            $aantal_dagen = 1;
        }
        
        /*
        echo "<br>" . $cum_som;
        echo "<br>" . $totalNumberDaysInMonth;
        echo "<br>" . $startYear_ymd[2];
        echo "<br>" . date('d');
        echo "<br>" . $aantal_dagen;
        */
        
        $cum_som = $cum_som / ($totalNumberDaysInMonth-$startYear_ymd[2]) * $aantal_dagen ;
        
        
    }

    $block = 0;
    if( $getmonths > 0 )
    {
        for( $i=1; $i<=$getmonths; $i++ )
        {
            $maand_nummer = $monthNumber+$i;
            
            if( $block == 0 )
            {
                if( $monthNumber+$i > 12 )
                {
                    $startYear++;
                    $block = 1;
                }
            }
            
            $totalNumberDaysInMonth = date("t", mktime( 0, 0, 0, getMonth($maand_nummer), 1, (int)$startYear) );
            
            $waarde = calculateExpectedPower($startPower,$startYear, getMonth($maand_nummer),$yearNumber,$totalNumberDaysInMonth,$totalNumberDaysInMonth, $link_futech);
            $cum_som += number_format($waarde, 0, ".", "");
            
            //echo "<br>". $startYear ."-". getMonth($maand_nummer) .".." . number_format($waarde, 2, ".", "") . " cum - som : " . number_format($cum_som, 0, ".", "");
        }
        
        $waarde = calculateExpectedPower($startPower, $startYear, date('m'), $yearNumber, date('d'), $totalNumberDaysInMonth, $link_futech);
        $cum_som += number_format($waarde, 0, ".", "");
    }
        
    return "<b>" . number_format($cum_som, 2, ",", "") . " kWh</b>";
}

function auto_offerte($output, $cus_id)
{
    require "inc/fpdf.php";
    require "inc/fpdi.php";
    
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
    $offerte_inst = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_offerte_instellingen LIMIT 1"));
    
    $pdf = new FPDI();
    
    /********************************************************* PAGINA 1 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina1.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // now write some text above the imported page 
    $pdf->SetFont('Arial', '', 12); 
    $pdf->SetTextColor(0,0,0);
    
    // klantgegevens tonen op de eerste pagina
    
    $regelmin = 234;
    $van_links = 90;
    $pdf->Text($van_links, 212, "Datum offerte : " . date('d') . "-" . date('m') . "-" . date('Y')  );
    $pdf->Text($van_links, 222, ucfirst( html_entity_decode( $klant->cus_naam, ENT_QUOTES ) ) );
    $pdf->Text($van_links, 228, ucfirst( html_entity_decode( $klant->cus_straat, ENT_QUOTES )) . " " . $klant->cus_nr );
    $pdf->Text($van_links, 234, $klant->cus_postcode . " " . html_entity_decode( $klant->cus_gemeente, ENT_QUOTES ) );
    
    if( !empty( $klant->cus_email ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, $klant->cus_email );
    }
    
    if( !empty( $klant->cus_tel ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "Tel. : " . $klant->cus_tel );
    }
    
    if( !empty( $klant->cus_gsm ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "GSM : " . $klant->cus_gsm );
    }
    
    if( !empty( $klant->cus_btw ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "BTW nr : " . $klant->cus_btw );
    }
    
    
    /********************************************************* PAGINA 2 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $tekst1 = "Beste,
FUTECH wilt u alvast bedanken in het gestelde vertrouwen. FUTECH stelt u hierbij dan ook graag
project 'PV installatie' voor. Dit is een vertrouwelijk document tussen FUTECH en u.";
    
    $pdf->SetXY( 18.5, 35 );
    $pdf->MultiCell(0, 5, $tekst1, 0, "L", false);
    
    $titel1 = "Project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 55 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $tekst2 = "FUTECH heeft niet stil gezeten. We hebben de tijd genomen om dit project te bestuderen en een goed 
plan voor u uit te werken. FUTECH heeft hierin aanzienlijke tijd en energie ge�nvesteerd om dit tot 
gebruiksklare modellen uit te werken hetgeen U zal kunnen lezen op de hierna volgende pagina's. Het
Om u zo goed mogelijk in te lichten heeft FUTECH een 3D model gemaakt van de gebouwen met de PV 
installatie. Met dit model doen we een 3D schaduw analyse, zo kunnen we de opbrengst zeer 
nauwkeurig inschatten. Dit 3D model kan ook gebruikt worden om een goede inschatting te maken van
hoe het gebouw er zal uit zien na de investering.";
    
    $pdf->SetXY( 18.5, 70 );
    $pdf->MultiCell(0, 5, $tekst2, 0, "L", false);
    
    $titel1 = "Visualisatie van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 109 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p2.jpg', 36, 125, 135, 140 );
    
    // footer
    $pdf->Text( 20, 280, "2".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 3 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Engineering, montage en administratie";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $html = "-      Engineering";
    $pdf->Text( 25, 40, $html, 0, 0, "L", false);
    
    $html = "-      Tekenen van plans en schema�s";
    $pdf->Text( 25, 46, $html, 0, 0, "L", false);
    
    $html = "-      Rendementsberekeningen, studie bekabeling, dimensionering omvormer";
    $pdf->Text( 25, 52, $html, 0, 0, "L", false);
    
    $html = "-      Opmaken en afhandelen van het volledige administratieve dossier";
    $pdf->Text( 25, 58, $html, 0, 0, "L", false);
    
    $html = "-      Aanvragen alle nodige documenten en vergunningen";
    $pdf->Text( 25, 64, $html, 0, 0, "L", false);
    
    $html = "-      Montage en plaatsen van stelling en valbeveiligingen";
    $pdf->Text( 25, 70, $html, 0, 0, "L", false);
    
    $html = "-      Montage en plaatsen van de Pv-modules op bovengenoemde montageplaats";
    $pdf->Text( 25, 76, $html, 0, 0, "L", false);
    
    $html = "-      Verplaatsingsonkosten Futech Bvba � werf";
    $pdf->Text( 25, 82, $html, 0, 0, "L", false);
    
    $html = "-      Melding aan netbeheerder";
    $pdf->Text( 25, 88, $html, 0, 0, "L", false);
    
    $html = "-      Testen, keuring door erkend keuringsorganisme en oplevering";
    $pdf->Text( 25, 94, $html, 0, 0, "L", false);
    
    $html = "-      Melding van de opstart van de installatie bij de VREG (groene stroomcertificaten)";
    $pdf->Text( 25, 100, $html, 0, 0, "L", false);
    
    $html = "-      Monitoring oplossing";
    $pdf->Text( 25, 106, $html, 0, 0, "L", false);
    
    $html = "-      Kosten eigen aan de PV installatie";
    $pdf->Text( 25, 112, $html, 0, 0, "L", false);
    
    $titel1 = "Hoe ziet de installatie er uit?";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 125 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p3.jpg', 45, 140, 105, 0 );
    
    // footer
    $pdf->Text( 20, 280, "3".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 4 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Componenten";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 37, "QC  Futech Solar Module, monokristalijn silicium, 250 -255-300Wp" );
    $pdf->Text( 160, 37, $klant->cus_aant_panelen . " panelen" );
    $pdf->Text( 20, 42, "Europese keuring (T�V, CE, IEC) + IEC gecertifieerd" );
    
    $pdf->Image('pdf/offerte/foto_p4.jpg', 25, 45, 52, 38 );
    
    $pdf->Text( 90, 55, "Cell efficientie (%)" );
    $pdf->Text( 160, 55, "17,8" );
    $pdf->Text( 90, 61, "Module efficientie (%)" );
    $pdf->Text( 160, 61, "15,6" );
    $pdf->Text( 90, 67, "Temperatuur coefficient (%/�C)" );
    $pdf->Text( 160, 67, "0,45" );
    $pdf->Text( 90, 73, "Gewicht (kg)" );
    $pdf->Text( 163, 73, "19" );
    $pdf->Text( 90, 79, "Hoog doorzichtig, laag ijzer, gehard glas" );
    
    $pdf->Text( 20, 88, "SMA omvormer" );
    $pdf->Text( 40, 96, "Hoge efficientie hoge opbrengst" );
    $pdf->Text( 40, 102, "Veilig toestel, Simpel in gebruik en onderhoud" );
    $pdf->Text( 40, 108, "IEC gecertifieerd" );
    
    $pdf->Text( 20, 120, "Bevestigingsmateriaal" );
    $pdf->Text( 40, 128, "Roest Vrij Staal, Aluminium" );
    $pdf->Text( 40, 134, "Geinstalleerd om meer dan 25 jaar lang mee te gaan" );
    
    $pdf->Text( 20, 146, "Electrische componenten" );
    $pdf->Text( 40, 154, "6 mm� solar kabel, juiste sectie, T�V gekeurd, UV bestendig" );
    $pdf->Text( 40, 160, "Carlo Gavazzi meetapparatuur" );
    $pdf->Text( 40, 166, "SMA Monitoring" );
    
    $titel1 = "GARANTIES";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 170 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 182, "Op de zonnemodules :" );
    $pdf->Text( 20, 188, "De fabrikant verstrekt" );
    $pdf->Text( 30, 193, "-   Een productgarantie van" );
    $pdf->Text( 130, 193, "10 jaar" );
    
    $pdf->Text( 30, 198, "-   Een vermogensgarantie van 90% voor" );
    $pdf->Text( 130, 198, "10 jaar" );
    
    $pdf->Text( 30, 203, "-   Een vermogensgarantie van 80% voor" );
    $pdf->Text( 130, 203, "25 jaar" );
    
    $pdf->Text( 20, 215, "20 jaar garantie bij Futech:" );
    $pdf->Image('pdf/offerte/foto_p4_2.jpg', 25, 220, 140, 35 );
    
    
    $pdf->Text( 20, 265, "FUTECH zorgt binnen de 3 werkdagen voor een installateur ter plaatse bij problemen." );
     
    
    // footer
    $pdf->Text( 20, 280, "4".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 5 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Planning van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 40, "FASE 0" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 40, "Rendementsberekening" );
    $pdf->Text( 55, 46, "Opbrengstanalyse" );
    $pdf->Text( 55, 52, "Offerte opmaken" );
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 58, "Ondertekenen van het contract" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 70, "FASE 1" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 70, "Vergunningen" );
    $pdf->Text( 55, 76, "Technische voorbereiding van het project" );
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 82, "Financiering van het project" );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 88, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 100, "FASE 2" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 100, "Installatie van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 106, "Opnemen installatie in verzekeringspolis" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 112, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 124, "FASE 3" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 124, "Aansluiting van het project op het net" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 130, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 142, "FASE 4" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 142, "Registratie VREG" );
    $pdf->Text( 55, 154, "Aanvragen premies" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 148, "Aanvragen GSC" );
    
    $titel1 = "Betalingswijze";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 160 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 173, "OPTIE 1 :" );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 178, "Voorschot : 60%" );
    $pdf->Text( 20, 183, "Na oplevering : 40%" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 188, "OPTIE 2 :" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 193, "100% bij installatie" );
    
    $titel1 = "Geldigheidsduur offerte";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 200 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 215, "De offerte is geldig " . date('d-m-Y', mktime(0, 0, 0, date('m'), date('d')+8, date('Y') )) );
    
    $titel1 = "Voorziene uitvoeringstermijn";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 225 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 240, "Voor " . date('d-m-Y', mktime(0, 0, 0, date('m'), date('d')+21, date('Y') )) . " " );
    
    $pdf->Text( 20, 250, "Futech kan enkel de plaatsing tijdig garanderen, indien wij tijdig de AREI keuring kunnen verwezenlijken.");
    $pdf->Text( 20, 255, "Een dossier is pas compleet als wij alle nodige documenten ontvangen hebben. Indien wij deze niet tijdig");
    $pdf->Text( 20, 260, "ontvangen heeft Futech het recht om deze overeenkomst eenzijdig op te zeggen.");
    
    // footer
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 20, 280, "5".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 6 **********************************************************/
    
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "OPBRENGST BEREKENING";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p5.jpg', 115, 57, 80, 55 );
    
    $pdf->Text( 20, 55, "Aantal Panelen" );
    $pdf->Text( 20, 60, "Vermogen panelen Wp" );
    $pdf->Text( 20, 65, "Totaal piekvermogen" );
    $pdf->Text( 20, 70, "Hoek panelen met het zuiden" );
    $pdf->Text( 20, 75, "Hoek van de panelen" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 80, "Verwachte opbrengst op jaarbasis(kWh)" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 80, 51.5 );
    $pdf->Cell( 30, 5, $klant->cus_aant_panelen, 0, 0, "R");
    
    $pdf->SetXY( 80, 56.5 );
    $pdf->Cell( 30, 5, $klant->cus_w_panelen, 0, 0, "R");
    
    $piekvermogen = (int)$klant->cus_aant_panelen * (int)$klant->cus_w_panelen;
    $pdf->SetXY( 80, 61.5 );
    $pdf->Cell( 30, 5, $piekvermogen, 0, 0, "R");
    
    $pdf->SetXY( 80, 66.5 );
    $pdf->Cell( 30, 5, $klant->cus_hoek_z, 0, 0, "R");
    
    $pdf->SetXY( 80, 71.5 );
    $pdf->Cell( 30, 5, $klant->cus_hoek, 0, 0, "R");
    
    $verwacht_opbrengst = $piekvermogen * ( $klant->cus_kwhkwp/1000 );
    
    $pdf->SetXY( 80, 76.5 );
    $pdf->Cell( 30, 5, number_format($verwacht_opbrengst, 2, ",", ""), 0, 0, "R");
    
    $pdf->Text( 20, 90, "Verwachte temperatuur verliezen" );
    $pdf->Text( 20, 95, "Verwachte reflectie verliezen" );
    $pdf->Text( 20, 100, "Omvormer verliezen" );
    $pdf->Text( 20, 105, "Kabel en connectie verliezen" );
    $pdf->Text( 20, 110, "Andere verliezen" );
    
    $pdf->Text( 20, 120, "Totale verliezen" );
    
    
    $pdf->SetXY( 80, 86.5 );
    $pdf->Cell( 30, 5, "7,70%", 0, 0, "R");
    
    $pdf->SetXY( 80, 91.5 );
    $pdf->Cell( 30, 5, "3,80%", 0, 0, "R");
    
    $pdf->SetXY( 80, 96.5 );
    $pdf->Cell( 30, 5, "4,10%", 0, 0, "R");
    
    $pdf->SetXY( 80, 101.5 );
    $pdf->Cell( 30, 5, "0,70%", 0, 0, "R");
    
    $pdf->SetXY( 80, 106.5 );
    $pdf->Cell( 30, 5, "2%", 0, 0, "R");
    
    $pdf->SetXY( 80, 116.5 );
    $pdf->Cell( 30, 5, "18,30%", 0, 0, "R");
    
    
    $maand_perc = array();
    $maand_perc[1]["opbr"] = 2.55516840882695;
    $maand_perc[1]["min"] = 90;
    $maand_perc[1]["max"] = 105;
    
    $maand_perc[2]["opbr"] = 4.99419279907085;
    $maand_perc[2]["min"] = 90;
    $maand_perc[2]["max"] = 105;
    
    $maand_perc[3]["opbr"] = 7.43321718931475;
    $maand_perc[3]["min"] = 90;
    $maand_perc[3]["max"] = 105;
    
    $maand_perc[4]["opbr"] = 11.1498257839721;
    $maand_perc[4]["min"] = 90;
    $maand_perc[4]["max"] = 105;
    
    $maand_perc[5]["opbr"] = 13.8211382113821;
    $maand_perc[5]["min"] = 90;
    $maand_perc[5]["max"] = 105;
    
    $maand_perc[6]["opbr"] = 12.8919860627178;
    $maand_perc[6]["min"] = 90;
    $maand_perc[6]["max"] = 105;
    
    $maand_perc[7]["opbr"] = 14.1695702671312;
    $maand_perc[7]["min"] = 90;
    $maand_perc[7]["max"] = 105;
    
    $maand_perc[8]["opbr"] = 12.6596980255517;
    $maand_perc[8]["min"] = 90;
    $maand_perc[8]["max"] = 105;
    
    $maand_perc[9]["opbr"] = 8.82694541231127;
    $maand_perc[9]["min"] = 90;
    $maand_perc[9]["max"] = 105;
    
    $maand_perc[10]["opbr"] = 6.27177700348432;
    $maand_perc[10]["min"] = 90;
    $maand_perc[10]["max"] = 105;
    
    $maand_perc[11]["opbr"] = 3.2520325203252;
    $maand_perc[11]["min"] = 90;
    $maand_perc[11]["max"] = 105;
    
    $maand_perc[12]["opbr"] = 1.97444831591173;
    $maand_perc[12]["min"] = 90;
    $maand_perc[12]["max"] = 105; 
    
    $opbrengst = array();
    
    foreach( $maand_perc as $maand => $waarde )
    {
        //echo "<br>" . number_format($verwacht_opbrengst * ( $waarde / 100 ), 0, "", "");
        
        $opbrengst[ $maand ]["opbr"] = number_format($verwacht_opbrengst * ( $waarde["opbr"] / 100 ), 0, "", "");
        $opbrengst[ $maand ]["min"] = number_format( $opbrengst[ $maand ]["opbr"] * ( $waarde["min"] / 100 ), 0, "", "" );
        $opbrengst[ $maand ]["max"] = number_format( $opbrengst[ $maand ]["opbr"] * ( $waarde["max"] / 100 ), 0, "", "" );
    }
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 25, 136 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 20, 5, "Maand", "RB", 0, "L", true);
    $pdf->SetXY( 45, 136 );
    $pdf->Cell( 20, 5, "Opbr", "LB", 0, "R", true);
    $pdf->SetXY( 65, 136 );
    $pdf->Cell( 20, 5, "Min", "LB", 0, "R", true);
    $pdf->SetXY( 85, 136 );
    $pdf->Cell( 20, 5, "Max", "LB", 0, "R", true);
    
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
    
    $tot_opbr = 0;
    $tot_min = 0;
    $tot_max = 0;
    
    $pdf->SetFont('Arial', '', 10);
    $regeldown = 5;
    for( $i=1;$i<=12;$i++ )
    {
        $pdf->SetXY( 25, 136 + $regeldown );
        $pdf->Cell( 20, 5, ucfirst( $maand[$i] ) , "RB", 0, "L", true);
        $pdf->SetXY( 45, 136 + $regeldown );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["opbr"], "LB", 0, "R", true);
        $pdf->SetXY( 65, 136 + $regeldown );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["min"], "LB", 0, "R", true);
        $pdf->SetXY( 85, 136 + $regeldown );
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["max"], "LB", 0, "R", true);
        
        $regeldown = $regeldown + 5;
        
        $tot_opbr += $opbrengst[ $i ]["opbr"];
        $tot_min += $opbrengst[ $i ]["min"];
        $tot_max += $opbrengst[ $i ]["max"];
    }
    
    $pdf->SetXY( 25, 136 + $regeldown );
    $pdf->Cell( 20, 5, "TOTAAL" , "RT", 0, "L", true);
    $pdf->SetXY( 45, 136 + $regeldown );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 20, 5, number_format($tot_opbr, 0, "", " "), "LT", 0, "R", true);
    $pdf->SetXY( 65, 136 + $regeldown );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 20, 5, number_format($tot_min, 0, "", " "), "LT", 0, "R", true);
    $pdf->SetXY( 85, 136 + $regeldown );
    $pdf->Cell( 20, 5, number_format($tot_max, 0, "", " "), "LT", 0, "R", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 220, "Opbrengstfactor" );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 80, 220, $klant->cus_kwhkwp );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 90, 220, "Wh/Wp" );
    
    $pdf->Text( 20, 240, "Hoogte zon 21 juni" );
    $pdf->Text( 20, 246, "Hoogte zon 21 dec" );
    
    $pdf->Text( 70, 240, "62�50" );
    $pdf->Text( 70, 246, "15�10" );
    
    $pdf->Text( 115, 240, "Schaduw verliezen" );
    $pdf->Text( 115, 246, "Obstructieverliezen" );
    
    $pdf->Text( 160, 240, "0%" );
    $pdf->Text( 160, 246, "0%" );
    
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 20, 270, "*De opbrengstberekening is indicatief en niet-bindend" );
    
    // footer
    $pdf->Text( 20, 280, "6".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 7 **********************************************************/
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "AANKOOPFORMULE";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 40, "Om berekeningen te maken gaat FUTECH steeds uit van een realistische berekening." );
    $pdf->Text( 30, 45, "-       De degradatie van de zonnepanelen wordt realistisch genomen." );
    $pdf->Text( 30, 50, "-       De opbrengst (en verliezen) van de installatie zijn conservatief geschat" );
    $pdf->Text( 30, 55, "-       De stroomprijs is met een indexering van 4% gerekend (gunstigste model)" );
    $pdf->Text( 20, 60, "Er mag dus vanuit gegaan worden dat dit het minimum is wat de installatie jaarlijks zal opbrengen. Naarmate de " );
    $pdf->Text( 20, 65, "installatie meer naar het einde van de 20 jarige periode gaat zal de opbrengst naar alle waarschijnlijkheid hoger" );
    $pdf->Text( 20, 70, "liggen." );
    $pdf->Text( 20, 80, "Na 20 jaar heeft de installatie al heel wat opgebracht. Toch heeft de installatie een gegarandeerde opbrengst voor" );
    $pdf->Text( 20, 85, "25 jaar en waarschijnlijk nog vele jaren langer." );
    
    
    $min_kwhkwp = number_format($klant->cus_kwhkwp * 0.90, 0, "", ""); 
    $max_kwhkwp = number_format($klant->cus_kwhkwp * 1.05, 0, "", "");
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetXY( 55, 90 );
    $pdf->Cell( 60, 7, "Minimale opbrengst", "RB", 0, "L", true);
    $pdf->SetXY( 115, 90 );
    $pdf->Cell( 60, 7, "    " . $min_kwhkwp . " Wh/Wp", "LB", 0, "L", true);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY( 55, 97 );
    $pdf->Cell( 60, 7, "Gemiddelde opbrengst", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 97 );
    $pdf->Cell( 60, 7, "    " . $klant->cus_kwhkwp . " Wh/Wp", "LBT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 55, 104 );
    $pdf->Cell( 60, 7, "Verwachte opbrengst", "RT", 0, "L", true);
    $pdf->SetXY( 115, 104 );
    $pdf->Cell( 60, 7, "    " . $max_kwhkwp . " Wh/Wp", "LT", 0, "L", true);
    
    $pdf->Text( 20, 116, "Dit wordt geregeld door onze algemene voorwaarden op pag 10 en juridisch omkaderd." );
    
    $dak = $klant->cus_soort_dak;
    $ori_dak = $dak;
    
    if( $dak == 1 || $dak == 2 || $dak == 6 || $dak == 7 || $dak == 10 )
    {
    	$dak = 1;
    }
    
    if( $dak == 3 || $dak == 8 )
    {
    	$dak = 2;
    } 
    
    if( $dak == 4 || $dak == 5 || $dak == 9 )
    {
    	$dak = 3;
    }
    
    $ppp = 0;
    
    // prijs per paneel opzoeken
    $daksoort[1] = "wp_plat";
    $daksoort[2] = "wp_leien";
    $daksoort[3] = "wp_schans";
    
    $waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_wp WHERE wp_start <= ". $klant->cus_aant_panelen ." AND wp_end >=" . $klant->cus_aant_panelen));
    $ppp = $waarde->$daksoort[ $dak ];
    
    
    // 3 fasig    
    $extra = 0;
    if( $klant->cus_aant_panelen > 24 && $klant->cus_driefasig == '0' )
    {
        $extra = $waarde->wp_3f;
    }
    
    // zwarte panelen
    if( $klant->cus_type_panelen == "Zwarte" )
    {
        $verkoop_zwarte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verkoop_zwarte' "));
        $ppp += $verkoop_zwarte->value;
    }
    
    if( $ori_dak == 9 )
    {
        // Schans op voeten
        $schans_op_voeten = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'schans_op_voeten' "));
        $ppp += $schans_op_voeten->value;
    }
    
    if( $ori_dak == 10 )
    {
        // Hellend roofing dak
        $hellend_roofing = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'hellend_roofing' "));
        $ppp += $hellend_roofing->value;
    }
    
    $investeringsbedrag = number_format( (($klant->cus_aant_panelen * $klant->cus_w_panelen * $ppp) + 100 + $extra) / 0.8, 0, "", "");
    $investeringsbedrag2 = number_format( ($klant->cus_aant_panelen * $klant->cus_w_panelen * $ppp) + 100 + $extra, 0, "", "");
    $commerciele_korting = $investeringsbedrag - $investeringsbedrag2;
    
    $btw = 0;
    
    if($klant->cus_woning5j == 1 )
    {
        $btw = 6;    
    }
    
    if($klant->cus_woning5j == 0 )
    {
        $btw = 21;
    }
    
    $te_betalen = $investeringsbedrag2 * (1+($btw/100));
    $te_betalen = number_format( $te_betalen, 0, "", "" );
    
    $prijs_gsc = $offerte_inst->gsc;
    $prijs_elec = $offerte_inst->elec; 
    
    $gsc = number_format($verwacht_opbrengst * $prijs_gsc, 0, "", "");
    $elec =  number_format($verwacht_opbrengst * $prijs_elec, 0, "", "");
    
    $tot_besp = $gsc + $elec;
    
    $terugverdientijd = $te_betalen / $tot_besp;
    
    $titel1 = "Kostprijs project";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 118 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 130, "FUTECH is een firma die kwaliteit nastreeft en is er zich van bewust dat u een installatie wil die een levensduur" );
    $pdf->Text( 20, 135, "heeft van meer dan 20 jaar." );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 140 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
    $pdf->SetXY( 115, 140 );
    $pdf->Cell( 35, 5, "    " . $klant->cus_aant_panelen, "LB", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 145 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 145 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 145 );
    $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 150 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 150 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen * $klant->cus_aant_panelen, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 150 );
    $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 155 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Investeringsbedrag (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 155 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 155 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(216,157,64);
    $pdf->SetXY( 55, 160 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "UITZONDERLIJKE KORTING", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 160 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $commerciele_korting ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 160 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 165 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 165 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag2,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 165 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 170 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (incl. ". $btw ."% BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 170 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 170 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 175 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 175 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 180 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 180 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_kwhkwp , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 180 );
    $pdf->Cell( 15, 5, "Wh", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 185 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 185 );
    $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Cell( 35, 5, "    " . number_format(($klant->cus_w_panelen * $klant->cus_aant_panelen ) * ( $klant->cus_kwhkwp / 1000 ),2,",","")  , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 185 );
    $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 190 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 190 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 195 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Premie gemeente", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 195 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    0", "LBT", 0, "L", true);
    $pdf->SetXY( 135, 195 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 200 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 200 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 205 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Netto investering", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 205 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 205 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 210 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 210 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 215 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Opbrengsten", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 215 );
    $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 220 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "GSC per jaar", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 220 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $gsc, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 220 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 225 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Besparing electriciteit", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 225 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $elec, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 225 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 230 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 230 );
    $pdf->Cell( 35, 5, "    " . $tot_besp, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 230 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 235 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 235 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 240 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Theoretische terugverdientijd", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 240 );
    $pdf->Cell( 35, 5, "    " . number_format($terugverdientijd,1,",",""), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 240 );
    $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 245 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 245 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 250 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Voorschot", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 250 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    60%", "LBT", 0, "L", true);
    $pdf->SetXY( 134, 250 );
    $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.6, 0, "", " " ), "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 255 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Na oplevering", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 255 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    40%", "LBT", 0, "L", true);
    $pdf->SetXY( 134, 255 );
    $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.4, 0, "", " " ), "BT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 270, "Indien driefasig nodig is, en wanneer driefasig niet aanwezig is, dan zullen er extra kosten bijkomen." );
    
    // footer
    $pdf->Text( 20, 280, "7".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 8 **********************************************************/
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "HUURFORMULE";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 40, "FUTECH investeert massaal in groene energie en is daarvoor op zoek naar zoveel mogelijk daken om zonnepanelen" );
    $pdf->Text( 20, 45, "op te plaatsen." );
    $pdf->Text( 30, 50, "-       U investeert zelf niets" );
    $pdf->Text( 30, 55, "-       U stelt ons uw dak 20 jaar ter beschikking" );
    $pdf->Text( 30, 60, "-       De zonnepanelen leveren u gratis stroom" );
    $pdf->Text( 30, 65, "-       U betaalt maandelijks een kleine onderhoudsbijdrage gedurende 20 jaar" );
    $pdf->Text( 30, 70, "-       Futech blijft eigenaar van de installatie en de opgewekte certificaten" );
    $pdf->Text( 30, 75, "-       Na 20 jaar krijgt u de zonnepaneel installatie tegen een spotprijs die voordien wordt afgesproken" );
    $pdf->Text( 30, 80, "-       Bij defect aan de installatie wordt dit kosteloos hersteld gedurende 20 jaar" );
    
    $pdf->Image('pdf/offerte/foto_p8.jpg', 20, 110, 29, 21 );
    
    
    $dak = $klant->cus_soort_dak;
    $ori_dak = $dak;
    
    if( $dak == 1 || $dak == 2|| $dak == 6 || $dak == 7 || $dak == 10 )
    {
    	$dak = 1;
    }
    
    if( $dak == 3 || $dak == 8 )
    {
    	$dak = 2;
    } 
    
    if( $dak == 4 || $dak == 5 || $dak == 9 )
    {
    	$dak = 3;
    }
    
    $ppp = 0;
    
    // prijs per paneel opzoeken
    $daksoort[1] = "wp_plat";
    $daksoort[2] = "wp_leien";
    $daksoort[3] = "wp_schans";
    
    $waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_wp_huur WHERE wp_start <= ". $klant->cus_aant_panelen ." AND wp_end >=" . $klant->cus_aant_panelen));
    $ppp = $waarde->$daksoort[ $dak ];
    
    $extra = 0;
    
    if( $klant->cus_aant_panelen > 24 && $klant->cus_driefasig == '0' )
    {
        $extra = $waarde->wp_3f;
    }
    
    // zwarte panelen
    if( $klant->cus_type_panelen == "Zwarte" )
    {
        $verhuur_zwarte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_zwarte' "));
        
        $ppp += $verhuur_zwarte->value;
    }
    
    if( $ori_dak == 9 )
    {
        // schans op voeten.
        $verhuur_schans_op_voeten = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_schans_op_voeten' "));
        $ppp += $verhuur_schans_op_voeten->value;
    }
    
    if( $ori_dak == 10 )
    {
        // Hellend roofing dak
        $verhuur_hellend_roofing = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_hellend_roofing' "));
        $ppp += $verhuur_hellend_roofing->value;
    }
    
    $prijsperpaneel = $ppp + $extra;
    
    $prijsperpaneel1 = $offerte_inst->factor_onderhoud2 + $extra;
    $prijsperpaneel2 = $offerte_inst->factor_onderhoud3 + $extra;
    
    $maandelijksekost = $klant->cus_aant_panelen * $prijsperpaneel;
    $maandelijksekost1 = $klant->cus_aant_panelen * $prijsperpaneel1;
    $maandelijksekost2 = $klant->cus_aant_panelen * $prijsperpaneel2;
    
    $factor_huur = $offerte_inst->huurfactor;
    
    $ber_deel2 = ceil($maandelijksekost) * 12 * 20;
    
    $huurkoop = ( $verwacht_opbrengst * 20 * $factor_huur) - ( $ber_deel2 ); 
    $huurkoop = number_format( $huurkoop, 0, "", " " );
    
    if( $klant->cus_aant_panelen < 10 )
    {
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 100, 5, "De huurformule is enkel geldig vanaf 10 panelen.", 0, 0, "L", true);
        
    }else
    {
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 90 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
        $pdf->SetXY( 115, 90 );
        $pdf->Cell( 35, 5, "    " . $klant->cus_aant_panelen, "LB", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 95 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 95 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 95 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 100 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 100 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen * $klant->cus_aant_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 100 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 105 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Te betalen (incl. BTW)", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 105 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "     0", "LBT", 0, "L", true);
        $pdf->SetXY( 135, 105 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 110 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 110 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RBT", 0, "L", true);
        $pdf->SetFillColor(216,157,64);
        $pdf->SetXY( 115, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 30, 5, "    " . ceil($maandelijksekost), "LBT", 0, "L", true);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 134, 115 );
        $pdf->Cell( 16, 5, "�/maand", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 120 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 120 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 125 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 125 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_kwhkwp, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 125 );
        $pdf->Cell( 15, 5, "Wh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 130 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 130 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format($verwacht_opbrengst,2,",",""), "LBT", 0, "L", true);
        $pdf->SetXY( 135, 130 );
        $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 135 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 135 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $bedrag = "0,22";
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 140 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Energieprijs", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 140 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $bedrag, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 140 );
        $pdf->Cell( 15, 5, "�/kWh", "BT", 0, "L", true);
        
        $bedrag = "4";
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 145 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Indexatie energieprijzen", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 145 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $bedrag, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 145 );
        $pdf->Cell( 15, 5, "%", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 155 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Opbrengsten", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 155 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, " ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 160 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 95, 5, "Besparing electriciteit- onderhoudskosten", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 165 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 165 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $huurkoop, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 165 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 170 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 170 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 175 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Terugverdientijd", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 175 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "     0", "LBT", 0, "L", true);
        $pdf->SetXY( 135, 175 );
        $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    }
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 190, "Gelieve bij interesse volgende documenten aan Futech te bezorgen :" );
    $pdf->Text( 25, 195, "- Loonfiche van het gezinshoofd" );
    $pdf->Text( 25, 200, "- Eigendomsbewijs van het huis" );
    $pdf->Text( 25, 205, "- Brief dat de hypotheekhouder ingelicht en akkoord is, gelieve er rekening mee te houden dat" );
    $pdf->Text( 25, 210, "  sommige banken hier een vergoeding voor vragen." );
    
    $pdf->Text( 20, 220, "Sleutelvoorwaarde om aan deze formule te voldoen is dat je moet beslissen voor onderstaande data. Dit omdat" );
    $pdf->Text( 20, 225, "dan de subsidies nogmaals verder worden afgebouwd. Indien er na deze datum beslist wordt, zal de" );
    $pdf->Text( 20, 230, "onderhoudskost aangepast worden. Pas als bovenstaande documenten ontvangen zijn is het dossier ontvankelijk,");
    $pdf->Text( 20, 235, "enkel indien dit binnen de 5 dagen gebeurd is onderstaande kost geldig.");
    
    if( $klant->cus_aant_panelen >= 10 )
    {
        $pdf->Text( 20, 245, "Voor" );
        $pdf->Text( 45, 245, date("d-m-Y", mktime(0, 0, 0, date('m'), date('d')+8, date('Y')) ) );
        
        $pdf->Text( 20, 250, "Na" );
        $pdf->Text( 45, 250, date("d-m-Y", mktime(0, 0, 0, date('m'), date('d')+8, date('Y')) ) );
        
        $dat_onderhoud = changeDate2EU($offerte_inst->datum_onderhouds);
        $dat_onderhoud = str_replace("-", "/", $dat_onderhoud);
        
        $pdf->Text( 20, 255, "Na " . $dat_onderhoud );
        
        $pdf->Text( 20, 265, "De formule wordt geregeld en juridisch omkaderd door opstalregeling uitgewerkt op pag 10 tot en met 15." );
        
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 65, 241 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RB", 0, "L", true);
        $pdf->SetXY( 125, 241 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost) . "   �/MAAND ", "LB", 0, "L", true);
        
        $pdf->SetXY( 65, 246 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RBT", 0, "L", true);
        $pdf->SetXY( 125, 246 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost1) . "   �/MAAND ", "LBT", 0, "L", true);
        
        $pdf->SetXY( 65, 251 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RT", 0, "L", true);
        $pdf->SetXY( 125, 251 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost2) . "   �/MAAND ", "LT", 0, "L", true);
    }
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "8".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    
    /********************************************************* PAGINA 9 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina9.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 84, 126, $klant->cus_w_panelen );
    $pdf->Text( 105, 126, $klant->cus_aant_panelen );
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 135, 166.5, number_format($investeringsbedrag2,0,""," ") );
    $pdf->Text( 135, 171.5, number_format($te_betalen,0,""," ") );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 40, 192.5, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 207.5, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 213, $klant->cus_postcode );
    $pdf->Text( 76, 213, html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10 );
    $pdf->Text( 50, 218, $klant->cus_tel);
    $pdf->Text( 50, 223, $klant->cus_gsm);
    
    $daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 233, $klant->cus_email );
    $pdf->Text( 150, 223.5, $daksoorten[ $klant->cus_soort_dak ] );
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    $pdf->Text( 135, 199.5, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->Text( 162, 238.7, $opwoning );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "9".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 10 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina10.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 84, 126, $klant->cus_w_panelen );
    $pdf->Text( 105, 126, $klant->cus_aant_panelen );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 50, 192.5, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 202.4, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 208, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10 );
    
    if( !empty($klant->cus_tel) && !empty($klant->cus_gsm) )
    {
        $tel = $klant->cus_tel . " / " . $klant->cus_gsm;    
    }else
    {
        $tel = $klant->cus_tel . $klant->cus_gsm;
    }
    
    $pdf->Text( 50, 213, $tel );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 223, $klant->cus_email );
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    $pdf->Text( 135, 199.5, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 135, 166.5, ceil($maandelijksekost) );
    $pdf->Text( 135, 171.5, number_format($investeringsbedrag2,0,""," "));
    
    $pdf->Text( 162, 207.75, $opwoning );
    $pdf->Text( 151, 224, $daksoorten[ $klant->cus_soort_dak ] );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "10".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 11 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina11.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 80, 71, html_entity_decode($klant->cus_naam, ENT_QUOTES ) ); 
    
    $pdf->Text( 45, 76, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 45, 81, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 65, 90, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "11".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 12 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina12.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "12".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 13 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina13.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $verkoper = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Text( 50, 33.5, $verkoper->voornaam ." " . $verkoper->naam );
    $pdf->Text( 27, 42, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 42, 58, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "13".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 14 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina14.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "14".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 15 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina15.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "15".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 16 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina16.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Text( 45, 136, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 80, 146, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    
    $pdf->Text( 92, 155.5, $klant->cus_postcode );
    $pdf->Text( 125, 155.5, html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 80, 197, maakReferte($klant->cus_id, "") );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "16".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 17 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina17.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "17".$max_page );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 18 **********************************************************/
    if( $_SESSION[ $session_var ]->user_id == 19 || 1 == 1 )
    {
        if( $klant->cus_dag != 0.00 && $klant->cus_nacht != 0.00 && (  $klant->cus_dag_tarief != 0.00 && $klant->cus_nacht_tarief != 0.00  ) )
        {
            $pdf->AddPage(); 
            $pdf->setSourceFile('pdf/aanmaning.pdf'); 
            // import page 1 
            $tplIdx = $pdf->importPage(1); 
            //use the imported page and place it at point 0,0; calculate width and height
            //automaticallay and ajust the page size to the size of the imported page 
            $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Text( 20, 55, "Keuze dag, of dag/nacht tarief" );
            
            // Color and font restoration
            $pdf->SetFillColor(224,235,255);
            $pdf->SetTextColor(0);
            $pdf->SetLineWidth(.3);
            $pdf->SetFont('Arial', '', 10);
            
            
            
            // Data
            
            $pdf->SetXY(20, 70);
            $pdf->Cell( 100,6, "Verbruik piek-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format($klant->cus_dag, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $pdf->SetXY(20, 76);
            $pdf->Cell( 100,6, "Verbruik dal-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($klant->cus_nacht, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "kWh",1,0,'L',true);
            
            $tot_kwhkwp = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 );
            $pdf->SetXY(20, 82);
            $pdf->Cell( 100,6, "PV: Totaal aantal kWh opgewekt per jaar",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $tot_kwhkwp, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $kwh_piek = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 ) * (5/7);
            $pdf->SetXY(20, 88);
            $pdf->Cell( 100,6, "PV: Aantal kWh opgewekt gedurende piek-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $kwh_piek, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "kWh",1,0,'L',true);
            
            $kwh_dal = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 ) * (2/7);
            $pdf->SetXY(20, 94);
            $pdf->Cell( 100,6, "PV: Aantal kWh opgewekt gedurende dal-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $kwh_dal, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $pdf->SetXY(20, 100);
            $pdf->Cell( 100,6, "Tarief piek-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $klant->cus_dag_tarief , 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "all in",1,0,'L',true);
            
            $pdf->SetXY(20, 106);
            $pdf->Cell( 100,6, "Tarief dal-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $klant->cus_nacht_tarief, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "all in",1,0,'L',false);
            
            $pdf->SetXY(20, 112);
            $pdf->Cell( 100,6, "Vaste minimale vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $klant->cus_vergoeding, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "� per jaar",1,0,'L',true);
            
            $pdf->SetXY(20, 118);
            $pdf->Cell( 100,6, "",1,0,'L',false);
            $pdf->Cell(  40,6, "",1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 124);
            $pdf->Cell( 100,6, "Momenteel betaalt u aan de energiemaatschappij :",1,0,'L',true);
            $pdf->Cell(  40,6, "",1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 130);
            $pdf->Cell( 100,6, "Voor verbruik tijdens piek-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format($klant->cus_dag * $klant->cus_dag_tarief, 2, ',', '') ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $pdf->SetXY(20, 136);
            $pdf->Cell( 100,6, "Voor verbruik tijdens dal-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($klant->cus_nacht * $klant->cus_nacht_tarief, 2, ',', ''),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $pdf->SetXY(20, 142);
            $pdf->Cell( 100,6, "Totaal",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( ($klant->cus_dag * $klant->cus_dag_tarief)+($klant->cus_nacht * $klant->cus_nacht_tarief), 2, ',', '') ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $pdf->SetXY(20, 148);
            $pdf->Cell( 100,6, "",1,0,'L',true);
            $pdf->Cell(  40,6, "",1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 154);
            $pdf->Cell( 100,6, "Na plaatsing zonnepanelen, met dag/nacht teller",1,0,'L',false);
            $pdf->Cell(  40,6, "" ,1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $dn_tot_dag = ($klant->cus_dag-$kwh_piek) * $klant->cus_dag_tarief;
            
            if( $dn_tot_dag < 0 )
            {
                $dn_tot_dag = 0;
            }
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 160);
            $pdf->Cell( 100,6, "Dag",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $dn_tot_dag, 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $dn_tot_nacht = ($klant->cus_nacht-$kwh_dal) * $klant->cus_nacht_tarief;
            
            if( $dn_tot_nacht < 0 )
            {
                $dn_tot_nacht = 0;
            }
            $pdf->SetXY(20, 166);
            $pdf->Cell( 100,6, "Nacht",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $dn_tot_nacht, 2, ",", "" ),1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $tot_dn = $dn_tot_dag+$dn_tot_nacht+$klant->cus_vergoeding;
            $pdf->SetXY(20, 172);
            $pdf->Cell( 100,6, "Totaal + vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $tot_dn, 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $pdf->SetXY(20, 178);
            $pdf->Cell( 100,6, "",1,0,'L',false);
            $pdf->Cell(  40,6, "",1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 184);
            $pdf->Cell( 100,6, "Na plaatsing zonnepanelen, met enkel dag teller",1,0,'L',true);
            $pdf->Cell(  40,6, "" ,1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $enkel_dag = ($klant->cus_nacht + $klant->cus_dag - $tot_kwhkwp) * $klant->cus_dag_tarief;
            if( $enkel_dag < 0 )
            {
                $enkel_dag = 0;
            }
            
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 190);
            $pdf->Cell( 100,6, "Dag",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $enkel_dag, 2, ",", "" ) ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $tot_enkel = $enkel_dag+$klant->cus_vergoeding;
            
            if( $tot_enkel < 0 )
            {
                $tot_enkel = 0;
            }
            
            $pdf->SetXY(20, 196);
            $pdf->Cell( 100,6, "Totaal + vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($tot_enkel , 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            if( $tot_dn == $tot_enkel )
            {
                $concl = "De 2 bedragen zijn gelijk. In dit geval maakt het niet uit.";
            }else
            {
                if( $tot_dn < $tot_enkel )
                {
                    $concl = "Dag/nacht-teller komt goedkoper uit.";
                }else
                {
                    $concl = "Dag-teller komt goedkoper uit.";
                }
            }
            
            $pdf->SetFont('Arial', 'BU', 12);
            $pdf->Text(40, 215, "Conclusie : " . $concl);
            
            // footer
            $pdf->SetFont('Arial', '', 10);
            $pdf->Text( 20, 280, "18" );
            $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
        }
    }
    
    if( $output == "S" )
	{
		$ret["filename"] = 'Offerte_met_huurvoorstel.pdf';
        $ret["factuur"] = $pdf->Output('Offerte_met_huurvoorstel.pdf', $output);
		return $ret;
	}else
	{
		$pdf->Output('Offerte_met_huurvoorstel.pdf', $output);	
	}
}

function auto_offerte1($output, $cus_id)
{
    require "inc/fpdf.php";
    require "inc/fpdi.php";
    
    $max_pagina = "/15";
    $pagina = 1;
    
    
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
    $offerte_inst = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_offerte_instellingen LIMIT 1"));
    
    $pdf = new FPDI();
    
    /********************************************************* PAGINA 1 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina1_op.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 26);
    //$pdf->Text(70, 155, "Solden bij Futech");
    $pdf->Text(50, 155, "Herfstkortingen bij Futech");
    //$pdf->Text(30, 155, "Tijdelijk zomerse korting bij Futech");
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->Text(50, 160, "Geldig tot " . date("d-m-Y", mktime(0,0,0,date('m'),date('d')+14,date('Y'))));
    
    $pdf->SetFont('Arial', '', 12); 
    $pdf->Image('pdf/uitzon.jpg', 80, 165, 50, 40 );



    
    
    $pdf->SetFont('Arial', '', 12); 
    $pdf->SetTextColor(0,0,0);
    
    //$pdf->Image('pdf/offerte/op2012.png', 40, 115, 135, 80 );
    
    //$pdf->Image('pdf/offerte/meneerkonijnsquare.jpg', 75, 115, 68, 80 );
    //$pdf->Text(40, 202, "Voor elke overeenkomst schenkt Futech 100 euro aan meneer Konijn.");
    
    // now write some text above the imported page 
    
    
    // klantgegevens tonen op de eerste pagina
    
    $regelmin = 234+10;
    $van_links = 80;
    $pdf->Text($van_links, 212+10, "Datum offerte : " . date('d') . "-" . date('m') . "-" . date('Y')  );
    $pdf->Text($van_links, 222+10, ucfirst( html_entity_decode( $klant->cus_naam, ENT_QUOTES ) ) );
    $pdf->Text($van_links, 228+10, ucfirst( html_entity_decode( $klant->cus_straat, ENT_QUOTES )) . " " . $klant->cus_nr );
    $pdf->Text($van_links, 234+10, $klant->cus_postcode . " " . html_entity_decode( $klant->cus_gemeente, ENT_QUOTES ) );
    
    if( !empty( $klant->cus_email ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, $klant->cus_email );
    }
    
    if( !empty( $klant->cus_tel ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "Tel. : " . $klant->cus_tel );
    }
    
    if( !empty( $klant->cus_gsm ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "GSM : " . $klant->cus_gsm );
    }
    
    if( !empty( $klant->cus_btw ) )
    {
        $regelmin += 6;
        $pdf->Text($van_links, $regelmin, "BTW nr : " . $klant->cus_btw );
    }
    
    /*
    $pdf->SetFont('Arial', '', 15); 
    $pdf->Text(20, 270, "In het thema van de olympische spelen, heeft Futech drie zeer");
    $pdf->Text(20, 276, "interessante en unieke concepten voor u uitgewerkt!");
    */
    
    /********************************************************* PAGINA 2 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $tekst1 = "Beste,
FUTECH wilt u alvast bedanken in het gestelde vertrouwen. FUTECH stelt u hierbij dan ook graag
project 'PV installatie' voor. Dit is een vertrouwelijk document tussen FUTECH en u.";
    
    $pdf->SetXY( 18.5, 35 );
    $pdf->MultiCell(0, 5, $tekst1, 0, "L", false);
    
    $titel1 = "Project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 55 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $tekst2 = "FUTECH heeft niet stil gezeten. We hebben de tijd genomen om dit project te bestuderen en een goed 
plan voor u uit te werken. FUTECH heeft hierin aanzienlijke tijd en energie ge�nvesteerd om dit tot 
gebruiksklare modellen uit te werken hetgeen U zal kunnen lezen op de hierna volgende pagina's. Het
Om u zo goed mogelijk in te lichten heeft FUTECH een 3D model gemaakt van de gebouwen met de PV 
installatie. Met dit model doen we een 3D schaduw analyse, zo kunnen we de opbrengst zeer 
nauwkeurig inschatten. Dit 3D model kan ook gebruikt worden om een goede inschatting te maken van
hoe het gebouw er zal uit zien na de investering.";
    
    $pdf->SetXY( 18.5, 70 );
    $pdf->MultiCell(0, 5, $tekst2, 0, "L", false);
    
    $titel1 = "Visualisatie van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 109 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p2.jpg', 36, 125, 135, 140 );
    
    // footer
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 3 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Engineering, montage en administratie";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $html = "-      Engineering";
    $pdf->Text( 25, 40, $html, 0, 0, "L", false);
    
    $html = "-      Tekenen van plans en schema�s";
    $pdf->Text( 25, 46, $html, 0, 0, "L", false);
    
    $html = "-      Rendementsberekeningen, studie bekabeling, dimensionering omvormer";
    $pdf->Text( 25, 52, $html, 0, 0, "L", false);
    
    $html = "-      Opmaken en afhandelen van het volledige administratieve dossier";
    $pdf->Text( 25, 58, $html, 0, 0, "L", false);
    
    $html = "-      Aanvragen alle nodige documenten en vergunningen";
    $pdf->Text( 25, 64, $html, 0, 0, "L", false);
    
    $html = "-      Montage en plaatsen van stelling en valbeveiligingen";
    $pdf->Text( 25, 70, $html, 0, 0, "L", false);
    
    $html = "-      Montage en plaatsen van de Pv-modules op bovengenoemde montageplaats";
    $pdf->Text( 25, 76, $html, 0, 0, "L", false);
    
    $html = "-      Verplaatsingsonkosten Futech Bvba � werf";
    $pdf->Text( 25, 82, $html, 0, 0, "L", false);
    
    $html = "-      Melding aan netbeheerder";
    $pdf->Text( 25, 88, $html, 0, 0, "L", false);
    
    $html = "-      Testen, keuring door erkend keuringsorganisme en oplevering";
    $pdf->Text( 25, 94, $html, 0, 0, "L", false);
    
    $html = "-      Melding van de opstart van de installatie bij de VREG (groene stroomcertificaten)";
    $pdf->Text( 25, 100, $html, 0, 0, "L", false);
    
    $html = "-      Monitoring oplossing";
    $pdf->Text( 25, 106, $html, 0, 0, "L", false);
    
    $html = "-      Kosten eigen aan de PV installatie";
    $pdf->Text( 25, 112, $html, 0, 0, "L", false);
    
    $titel1 = "Hoe ziet de installatie er uit?";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 125 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p3.jpg', 45, 140, 105, 0 );
    
    // footer
    
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 4 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Componenten";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 37, "QC  Futech Solar Module, monokristalijn silicium, 250 -255-300Wp" );
    $pdf->Text( 160, 37, $klant->cus_aant_panelen . " panelen" );
    $pdf->Text( 20, 42, "Europese keuring (T�V, CE, IEC) + IEC gecertifieerd" );
    
    $pdf->Image('pdf/offerte/foto_p4.jpg', 25, 45, 52, 38 );
    
    $pdf->Text( 90, 55, "Cell efficientie (%)" );
    $pdf->Text( 160, 55, "17,8" );
    $pdf->Text( 90, 61, "Module efficientie (%)" );
    $pdf->Text( 160, 61, "15,6" );
    $pdf->Text( 90, 67, "Temperatuur coefficient (%/�C)" );
    $pdf->Text( 160, 67, "0,45" );
    $pdf->Text( 90, 73, "Gewicht (kg)" );
    $pdf->Text( 163, 73, "19" );
    $pdf->Text( 90, 79, "Hoog doorzichtig, laag ijzer, gehard glas" );
    
    $pdf->Text( 20, 88, "SMA omvormer" );
    $pdf->Text( 40, 96, "Hoge efficientie hoge opbrengst" );
    $pdf->Text( 40, 102, "Veilig toestel, Simpel in gebruik en onderhoud" );
    $pdf->Text( 40, 108, "IEC gecertifieerd" );
    
    $pdf->Text( 20, 120, "Bevestigingsmateriaal" );
    $pdf->Text( 40, 128, "Roest Vrij Staal, Aluminium" );
    $pdf->Text( 40, 134, "Geinstalleerd om meer dan 25 jaar lang mee te gaan" );
    
    $pdf->Text( 20, 146, "Electrische componenten" );
    $pdf->Text( 40, 154, "6 mm� solar kabel, juiste sectie, T�V gekeurd, UV bestendig" );
    $pdf->Text( 40, 160, "Carlo Gavazzi meetapparatuur" );
    $pdf->Text( 40, 166, "SMA Monitoring" );
    
    $titel1 = "GARANTIES";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 170 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 182, "Op de zonnemodules :" );
    $pdf->Text( 20, 188, "De fabrikant verstrekt" );
    $pdf->Text( 30, 193, "-   Een productgarantie van" );
    $pdf->Text( 130, 193, "10 jaar" );
    
    $pdf->Text( 30, 198, "-   Een vermogensgarantie van 90% voor" );
    $pdf->Text( 130, 198, "10 jaar" );
    
    $pdf->Text( 30, 203, "-   Een vermogensgarantie van 80% voor" );
    $pdf->Text( 130, 203, "25 jaar" );
    
    $pdf->Text( 20, 215, "20 jaar garantie bij Futech:" );
    $pdf->Image('pdf/offerte/foto_p4_2.jpg', 25, 220, 140, 35 );
    
    
    $pdf->Text( 20, 265, "FUTECH zorgt binnen de 3 werkdagen voor een installateur ter plaatse bij problemen." );
     
    
    // footer
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 5 **********************************************************/
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "Planning van het project";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 40, "FASE 0" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 40, "Rendementsberekening" );
    $pdf->Text( 55, 46, "Opbrengstanalyse" );
    $pdf->Text( 55, 52, "Offerte opmaken" );
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 58, "Ondertekenen van het contract" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 70, "FASE 1" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 70, "Vergunningen" );
    $pdf->Text( 55, 76, "Technische voorbereiding van het project" );
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 82, "Financiering van het project" );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 88, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 100, "FASE 2" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 100, "Installatie van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 106, "Opnemen installatie in verzekeringspolis" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 112, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 124, "FASE 3" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 124, "Aansluiting van het project op het net" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text( 55, 130, "Controle van het project" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 142, "FASE 4" );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 55, 142, "Registratie VREG" );
    $pdf->Text( 55, 154, "Aanvragen premies" );
    
    $pdf->SetTextColor(6,106,65);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 55, 148, "Aanvragen GSC" );
    
    $titel1 = "Betalingswijze";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 160 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 173, "OPTIE 1 :" );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 178, "Voorschot : 60%" );
    $pdf->Text( 20, 183, "Na oplevering : 40%" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 188, "OPTIE 2 :" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 193, "100% bij installatie" );
    
    $titel1 = "Geldigheidsduur offerte";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 200 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 215, "De offerte is geldig " . date('d-m-Y', mktime(0, 0, 0, date('m'), date('d')+8, date('Y') )) );
    
    $titel1 = "Voorziene uitvoeringstermijn";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 225 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 240, "Voor " . date('d-m-Y', mktime(0, 0, 0, date('m'), date('d')+21, date('Y') )) . " " );
    
    $pdf->Text( 20, 250, "Futech kan enkel de plaatsing tijdig garanderen, indien wij tijdig de AREI keuring kunnen verwezenlijken.");
    $pdf->Text( 20, 255, "Een dossier is pas compleet als wij alle nodige documenten ontvangen hebben. Indien wij deze niet tijdig");
    $pdf->Text( 20, 260, "ontvangen heeft Futech het recht om deze overeenkomst eenzijdig op te zeggen.");
    
    // footer
    $pdf->SetTextColor(0,0,0);
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 6 **********************************************************/
    
    $pdf->AddPage(); 
    
    //$pdf->setSourceFile('pdf/offerte/pagina2.pdf'); 
    // import page 1 
    //$tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    //$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = "OPBRENGST BEREKENING";
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Image('pdf/offerte/foto_p5.jpg', 115, 57, 80, 55 );
    
    $pdf->Text( 20, 55, "Aantal Panelen" );
    $pdf->Text( 20, 60, "Vermogen panelen Wp" );
    $pdf->Text( 20, 65, "Totaal piekvermogen" );
    $pdf->Text( 20, 70, "Hoek panelen met het zuiden" );
    $pdf->Text( 20, 75, "Hoek van de panelen" );
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 20, 80, "Verwachte opbrengst op jaarbasis(kWh)" );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 80, 51.5 );
    $pdf->Cell( 30, 5, $klant->cus_aant_panelen, 0, 0, "R");
    
    $pdf->SetXY( 80, 56.5 );
    $pdf->Cell( 30, 5, $klant->cus_w_panelen, 0, 0, "R");
    
    $piekvermogen = (int)$klant->cus_aant_panelen * (int)$klant->cus_w_panelen;
    $pdf->SetXY( 80, 61.5 );
    $pdf->Cell( 30, 5, $piekvermogen, 0, 0, "R");
    
    $pdf->SetXY( 80, 66.5 );
    $pdf->Cell( 30, 5, $klant->cus_hoek_z, 0, 0, "R");
    
    $pdf->SetXY( 80, 71.5 );
    $pdf->Cell( 30, 5, $klant->cus_hoek, 0, 0, "R");
    
    $verwacht_opbrengst = $piekvermogen * ( $klant->cus_kwhkwp/1000 );
    
    $pdf->SetXY( 80, 76.5 );
    $pdf->Cell( 30, 5, number_format($verwacht_opbrengst, 2, ",", ""), 0, 0, "R");
    
    $pdf->Text( 20, 90, "Verwachte temperatuur verliezen" );
    $pdf->Text( 20, 95, "Verwachte reflectie verliezen" );
    $pdf->Text( 20, 100, "Omvormer verliezen" );
    $pdf->Text( 20, 105, "Kabel en connectie verliezen" );
    $pdf->Text( 20, 110, "Andere verliezen" );
    
    $pdf->Text( 20, 120, "Totale verliezen" );
    
    
    $pdf->SetXY( 80, 86.5 );
    $pdf->Cell( 30, 5, "7,70%", 0, 0, "R");
    
    $pdf->SetXY( 80, 91.5 );
    $pdf->Cell( 30, 5, "3,80%", 0, 0, "R");
    
    $pdf->SetXY( 80, 96.5 );
    $pdf->Cell( 30, 5, "4,10%", 0, 0, "R");
    
    $pdf->SetXY( 80, 101.5 );
    $pdf->Cell( 30, 5, "0,70%", 0, 0, "R");
    
    $pdf->SetXY( 80, 106.5 );
    $pdf->Cell( 30, 5, "2%", 0, 0, "R");
    
    $pdf->SetXY( 80, 116.5 );
    $pdf->Cell( 30, 5, "18,30%", 0, 0, "R");
    
    
    $maand_perc = array();
    $maand_perc[1]["opbr"] = 2.55516840882695;
    $maand_perc[1]["min"] = 90;
    $maand_perc[1]["max"] = 105;
    
    $maand_perc[2]["opbr"] = 4.99419279907085;
    $maand_perc[2]["min"] = 90;
    $maand_perc[2]["max"] = 105;
    
    $maand_perc[3]["opbr"] = 7.43321718931475;
    $maand_perc[3]["min"] = 90;
    $maand_perc[3]["max"] = 105;
    
    $maand_perc[4]["opbr"] = 11.1498257839721;
    $maand_perc[4]["min"] = 90;
    $maand_perc[4]["max"] = 105;
    
    $maand_perc[5]["opbr"] = 13.8211382113821;
    $maand_perc[5]["min"] = 90;
    $maand_perc[5]["max"] = 105;
    
    $maand_perc[6]["opbr"] = 12.8919860627178;
    $maand_perc[6]["min"] = 90;
    $maand_perc[6]["max"] = 105;
    
    $maand_perc[7]["opbr"] = 14.1695702671312;
    $maand_perc[7]["min"] = 90;
    $maand_perc[7]["max"] = 105;
    
    $maand_perc[8]["opbr"] = 12.6596980255517;
    $maand_perc[8]["min"] = 90;
    $maand_perc[8]["max"] = 105;
    
    $maand_perc[9]["opbr"] = 8.82694541231127;
    $maand_perc[9]["min"] = 90;
    $maand_perc[9]["max"] = 105;
    
    $maand_perc[10]["opbr"] = 6.27177700348432;
    $maand_perc[10]["min"] = 90;
    $maand_perc[10]["max"] = 105;
    
    $maand_perc[11]["opbr"] = 3.2520325203252;
    $maand_perc[11]["min"] = 90;
    $maand_perc[11]["max"] = 105;
    
    $maand_perc[12]["opbr"] = 1.97444831591173;
    $maand_perc[12]["min"] = 90;
    $maand_perc[12]["max"] = 105; 
    
    $opbrengst = array();
    
    foreach( $maand_perc as $maand => $waarde )
    {
        //echo "<br>" . number_format($verwacht_opbrengst * ( $waarde / 100 ), 0, "", "");
        
        $opbrengst[ $maand ]["opbr"] = number_format($verwacht_opbrengst * ( $waarde["opbr"] / 100 ), 0, "", "");
        $opbrengst[ $maand ]["min"] = number_format( $opbrengst[ $maand ]["opbr"] * ( $waarde["min"] / 100 ), 0, "", "" );
        $opbrengst[ $maand ]["max"] = number_format( $opbrengst[ $maand ]["opbr"] * ( $waarde["max"] / 100 ), 0, "", "" );
    }
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 25, 136 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 20, 5, "Maand", "RB", 0, "L", true);
    $pdf->SetXY( 45, 136 );
    $pdf->Cell( 20, 5, "Opbr", "LB", 0, "R", true);
    $pdf->SetXY( 65, 136 );
    $pdf->Cell( 20, 5, "Min", "LB", 0, "R", true);
    $pdf->SetXY( 85, 136 );
    $pdf->Cell( 20, 5, "Max", "LB", 0, "R", true);
    
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
    
    $tot_opbr = 0;
    $tot_min = 0;
    $tot_max = 0;
    
    $pdf->SetFont('Arial', '', 10);
    $regeldown = 5;
    for( $i=1;$i<=12;$i++ )
    {
        $pdf->SetXY( 25, 136 + $regeldown );
        $pdf->Cell( 20, 5, ucfirst( $maand[$i] ) , "RB", 0, "L", true);
        $pdf->SetXY( 45, 136 + $regeldown );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["opbr"], "LB", 0, "R", true);
        $pdf->SetXY( 65, 136 + $regeldown );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["min"], "LB", 0, "R", true);
        $pdf->SetXY( 85, 136 + $regeldown );
        $pdf->Cell( 20, 5, $opbrengst[ $i ]["max"], "LB", 0, "R", true);
        
        $regeldown = $regeldown + 5;
        
        $tot_opbr += $opbrengst[ $i ]["opbr"];
        $tot_min += $opbrengst[ $i ]["min"];
        $tot_max += $opbrengst[ $i ]["max"];
    }
    
    $pdf->SetXY( 25, 136 + $regeldown );
    $pdf->Cell( 20, 5, "TOTAAL" , "RT", 0, "L", true);
    $pdf->SetXY( 45, 136 + $regeldown );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 20, 5, number_format($tot_opbr, 0, "", " "), "LT", 0, "R", true);
    $pdf->SetXY( 65, 136 + $regeldown );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 20, 5, number_format($tot_min, 0, "", " "), "LT", 0, "R", true);
    $pdf->SetXY( 85, 136 + $regeldown );
    $pdf->Cell( 20, 5, number_format($tot_max, 0, "", " "), "LT", 0, "R", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 220, "Opbrengstfactor" );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 80, 220, $klant->cus_kwhkwp );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 90, 220, "Wh/Wp" );
    
    $pdf->Text( 20, 240, "Hoogte zon 21 juni" );
    $pdf->Text( 20, 246, "Hoogte zon 21 dec" );
    
    $pdf->Text( 70, 240, "62�50" );
    $pdf->Text( 70, 246, "15�10" );
    
    $pdf->Text( 115, 240, "Schaduw verliezen" );
    $pdf->Text( 115, 246, "Obstructieverliezen" );
    
    $pdf->Text( 160, 240, "0%" );
    $pdf->Text( 160, 246, "0%" );
    
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Text( 20, 270, "*De opbrengstberekening is indicatief en niet-bindend" );
    
    // footer
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 7 **********************************************************/
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    //$titel1 = "AANKOOPFORMULE";
    $titel1 = "OLYMPISCH GOUD";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 35, "Om berekeningen te maken gaat FUTECH steeds uit van een realistische berekening." );
    $pdf->Text( 30, 40, "-       De degradatie van de zonnepanelen wordt realistisch genomen." );
    $pdf->Text( 30, 45, "-       De opbrengst (en verliezen) van de installatie zijn conservatief geschat" );
    $pdf->Text( 30, 50, "-       De stroomprijs is met een indexering van 4% gerekend (gunstigste model)" );
    $pdf->Text( 20, 55, "Er mag dus vanuit gegaan worden dat dit het minimum is wat de installatie jaarlijks zal opbrengen. Naarmate de " );
    $pdf->Text( 20, 60, "installatie meer naar het einde van de 20 jarige periode gaat zal de opbrengst naar alle waarschijnlijkheid hoger" );
    $pdf->Text( 20, 65, "liggen." );
    $pdf->Text( 20, 75, "Na 20 jaar heeft de installatie al heel wat opgebracht. Toch heeft de installatie een gegarandeerde opbrengst voor" );
    $pdf->Text( 20, 80, "25 jaar en waarschijnlijk nog vele jaren langer." );
    
    
    $min_kwhkwp = number_format($klant->cus_kwhkwp * 0.90, 0, "", ""); 
    $max_kwhkwp = number_format($klant->cus_kwhkwp * 1.05, 0, "", "");
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetXY( 55, 85 );
    $pdf->Cell( 60, 7, "Minimale opbrengst", "RB", 0, "L", true);
    $pdf->SetXY( 115, 85 );
    $pdf->Cell( 60, 7, "    " . $min_kwhkwp . " Wh/Wp", "LB", 0, "L", true);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY( 55, 92 );
    $pdf->Cell( 60, 7, "Gemiddelde opbrengst", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 92 );
    $pdf->Cell( 60, 7, "    " . $klant->cus_kwhkwp . " Wh/Wp", "LBT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 55, 99 );
    $pdf->Cell( 60, 7, "Verwachte opbrengst", "RT", 0, "L", true);
    $pdf->SetXY( 115, 99 );
    $pdf->Cell( 60, 7, "    " . $max_kwhkwp . " Wh/Wp", "LT", 0, "L", true);
    
    $pdf->Text( 20, 111, "Dit wordt geregeld door onze algemene voorwaarden op pag 10 en juridisch omkaderd." );
    $pdf->Text( 20, 116, "De opbrengstberekening is indicatief en niet-bindend" );
    
    $dak = $klant->cus_soort_dak;
    $ori_dak = $dak;
    
    if( $dak == 1 || $dak == 2 || $dak == 6 || $dak == 7 || $dak == 10 || $dak == 11 )
    {
    	$dak = 1;
    }
    
    if( $dak == 3 || $dak == 8 )
    {
    	$dak = 2;
    } 
    
    if( $dak == 4 || $dak == 5 || $dak == 9 )
    {
    	$dak = 3;
    }
    
    $ppp = 0;
    
    // prijs per paneel opzoeken
    $daksoort[1] = "wp_plat";
    $daksoort[2] = "wp_leien";
    $daksoort[3] = "wp_schans";
    
    $waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_wp WHERE wp_start <= ". $klant->cus_aant_panelen ." AND wp_end >=" . $klant->cus_aant_panelen));
    $ppp = $waarde->$daksoort[ $dak ];
    
    // 3 fasig    
    $extra = 0;
    if( $klant->cus_aant_panelen > 24 && $klant->cus_driefasig == '0' )
    {
        $extra = $waarde->wp_3f;
    }
    
    // zwarte panelen
    if( $klant->cus_type_panelen == "Zwarte" )
    {
        $verkoop_zwarte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verkoop_zwarte' "));
        $ppp += $verkoop_zwarte->value;
    }
    
    if( $ori_dak == 9 )
    {
        // Schans op voeten
        $schans_op_voeten = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'schans_op_voeten' "));
        $ppp += $schans_op_voeten->value;
    }
    
    if( $ori_dak == 10 )
    {
        // Hellend roofing dak
        $hellend_roofing = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'hellend_roofing' "));
        $ppp += $hellend_roofing->value;
    }
    
    $investeringsbedrag = number_format( (($klant->cus_aant_panelen * $klant->cus_w_panelen * $ppp) + 100 + $extra) / 0.8, 0, "", "");
    
    //echo "<br>" . $klant->cus_aant_panelen . " * " .  $klant->cus_w_panelen . " * " . $ppp . " + 100 + " . $extra . " / 0.8 = " . $investeringsbedrag;
    
    
    $investeringsbedrag2 = number_format( ($klant->cus_aant_panelen * $klant->cus_w_panelen * $ppp) + 100 + $extra, 0, "", "");
    
    $investeringsbedrag2a = $investeringsbedrag2;
        
    
    $commerciele_korting = $investeringsbedrag - $investeringsbedrag2;
    
    $btw = 0;
    
    if($klant->cus_woning5j == 1 )
    {
        $btw = 6;    
    }
    
    if($klant->cus_woning5j == 0 )
    {
        $btw = 21;
    }
    
    $te_betalen = $investeringsbedrag2 * (1+($btw/100));
    $te_betalen = number_format( $te_betalen, 0, "", "" );
    
    $prijs_gsc = $offerte_inst->gsc;
    $prijs_elec = $offerte_inst->elec; 
    
    $gsc = number_format($verwacht_opbrengst * $prijs_gsc, 0, "", "");
    $elec =  number_format($verwacht_opbrengst * $prijs_elec, 0, "", "");
    
    $tot_besp = $gsc + $elec;
    
    $terugverdientijd = $te_betalen / $tot_besp;
    
    $titel1 = "Kostprijs project";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 118 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 130, "FUTECH is een firma die kwaliteit nastreeft en is er zich van bewust dat u een installatie wil die een levensduur" );
    $pdf->Text( 20, 135, "heeft van meer dan 20 jaar." );
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 140 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
    $pdf->SetXY( 115, 140 );
    $pdf->Cell( 35, 5, "    " . $klant->cus_aant_panelen, "LB", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 145 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 145 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 145 );
    $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 150 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 150 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen * $klant->cus_aant_panelen, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 150 );
    $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 155 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Investeringsbedrag (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 155 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 155 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(216,157,64);
    $pdf->SetXY( 55, 160 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "UITZONDERLIJKE KORTING", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 160 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $commerciele_korting ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 160 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 165 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (excl. BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 165 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag2,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 165 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_finalia WHERE min <= " . $te_betalen . " AND max >= " . $te_betalen) or die( mysqli_error($conn) . " " . __LINE__ );
    if( mysqli_num_rows($q_zoek) == 1 )
    {
        $rec = mysqli_fetch_object($q_zoek);
        
        $looptijd = $rec->looptijd;
        $rente = $rec->rentevoet;
    } 
    $bedrag_mens = getMensualtiteit( $te_betalen );
    
    $string_mens = "Indien lening ". $bedrag_mens . "� gedurende ".$looptijd; 
    $string_mens1 = "maanden aan ". str_replace(".",",",$rente) ."% *";
    
    $pdf->Text(20,275, "* Enkel op voorwaarde dat Finalia de goedkeuring geeft" );
    
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY( 150, 171 );
    $pdf->Cell( 60, 5, $string_mens, "", 0, "L", true);
    $pdf->SetXY( 150, 176 );
    $pdf->Cell( 60, 5, $string_mens1, "", 0, "L", true);
    */
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 170 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Te betalen (incl. ". $btw ."% BTW)", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 170 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 170 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 175 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 175 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    */
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 175 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 175 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $klant->cus_kwhkwp , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 175 );
    $pdf->Cell( 15, 5, "Wh", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 180 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 180 );
    $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Cell( 35, 5, "    " . number_format(($klant->cus_w_panelen * $klant->cus_aant_panelen ) * ( $klant->cus_kwhkwp / 1000 ),0,"","")  , "LBT", 0, "L", true);
    $pdf->SetXY( 135, 185 );
    $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 185 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 185 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 190 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Premie gemeente", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 190 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    0", "LBT", 0, "L", true);
    $pdf->SetXY( 135, 190 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 195 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 195 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    */
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 195 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Netto investering", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 195 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", " "), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 195 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $te_betalen_a = $te_betalen;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 200 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 200 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 205 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Opbrengsten per jaar", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 205 );
    $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
    
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 215 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "GSC per jaar", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 215 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $gsc, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 215 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    */
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 210 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Besparing electriciteit", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 210 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . $elec, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 210 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 215 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 215 );
    $pdf->Cell( 35, 5, "    " . $tot_besp, "LBT", 0, "L", true);
    $pdf->SetXY( 135, 215 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    /*
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 225 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 225 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 55, 230 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 60, 5, "Theoretische terugverdientijd", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 230 );
    $pdf->Cell( 35, 5, "    " . number_format($terugverdientijd,1,",",""), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 230 );
    $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    */
    
    $deg = 0.5;
        
    $tot_gsc = 0;
    $start = 100;
    
    $tot_opbr = number_format($verwacht_opbrengst,0,"","");
    
    for($i=0;$i<$offerte_inst->gsc_aant_jaar_huurkoop;$i++ )
    {
        if( $i > 0 )
        {
            $start = $start - $deg;
            $elec1 = $elec1 * 1.04;
        } 
        
        $ber = ($tot_opbr * ($start / 100) );
        
        //echo "<br>" . number_format($ber,0,"","");
        
        $tot_gsc += number_format($ber * $offerte_inst->gsc,0,"","");
        
        
        //$bespaar_elec += ($tot_opbr * ($start / 100) ) * $elec1;
        
        //echo "<br/>" . ($tot_opbr * ($start / 100) ) . " " . ($start / 100) . " " . $offerte_inst->gsc . " " . $ber . " " . $tot_gsc . " " . $bespaar_elec;
    }
    
    
    
    
    //$tot_gsc = $tot_gsc * $offerte_inst->gsc_huurkoop;
    
    
    //echo $tot_opbr;
    
    $elec1 = $offerte_inst->elec;
    $bespaar_elec = 0;
    $start = 100;
    for($i=0;$i<20;$i++ )
    {
        if( $i > 0 )
        {
            $start = $start - $deg;
            $elec1 = $elec1 * 1.04;
        } 
        
        //$ber = ($tot_opbr * ($start / 100) ) * $offerte_inst->gsc;
        
        //$tot_gsc += $ber;
        
        $v_kwh = number_format( ($tot_opbr * ($start / 100) ), 0, "", "" );
        
        $bespaar_elec += number_format( $v_kwh * $elec1, 0, "", "" );
        
        //echo "<br>" . $bespaar_elec;
        
        //echo "<br/>" . $v_kwh . " " . ($start / 100) . " " . $offerte_inst->gsc . " " . $ber . " " . $tot_gsc . " " . $bespaar_elec . " " . $tot_opbr;
    }
    
    $winst = ($tot_gsc + $bespaar_elec) - $te_betalen_a;
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 220 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Winst na 20 jaar", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 220 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    " . number_format( $winst, 0, ",", "" ), "LBT", 0, "L", true);
    $pdf->SetXY( 135, 220 );
    $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 225 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 225 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
    
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 230 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Voorschot", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 230 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    60%", "LBT", 0, "L", true);
    $pdf->SetXY( 134, 230 );
    $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.6, 0, "", " " ), "BT", 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY( 55, 235 );
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell( 60, 5, "Na oplevering", "RBT", 0, "L", true);
    $pdf->SetXY( 115, 235 );
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell( 35, 5, "    40%", "LBT", 0, "L", true);
    $pdf->SetXY( 134, 235 );
    $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.4, 0, "", " " ), "BT", 0, "L", true);
    
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Rect(55, 242, 4, 4);
    $pdf->Text( 60, 245, $string_mens . " " . $string_mens1 );
    
    $pdf->Text( 62, 250, "- Maandelijkse last : �"  . $bedrag_mens);
    $pdf->Text( 62, 255, "- Maandelijkse besparing : �"  . number_format( $elec/12, 2, ",", "" ) );
    $pdf->Text( 62, 260, "- Nettolast per maand : �"  . number_format( str_replace(",",".",$bedrag_mens)-$elec/12, 2, ",", "" ) );
    
    $pdf->Text( 20, 270, "Indien driefasig nodig is, en wanneer driefasig niet aanwezig is, dan zullen er extra kosten bijkomen." );
    
    $prijs10procent = $te_betalen - ($te_betalen / 10);
    
    //$pdf->SetFont('Arial', 'B', 10);
    //$pdf->Text( 20, 280, "Wanneer u voor deze formule kiest voor " . date('d-m-Y', mktime(0,0,0, date('m'), date('d')+5,date('Y') ) ) . " dan is de prijs : " . number_format( $prijs10procent,2,",", "" ) ." euro" );
    
    //$pdf->Image('pdf/offerte/goud.jpg', 20, 145, 25, 40 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 7b **********************************************************/
    /*
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    $titel1 = strtoupper($offerte_inst->naam_huurkoop);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 40, "Deze formule is voor alle mensen die de goedkoopste energie formule willen." );
    $pdf->Text( 30, 45, "-       U wordt volledig eigenaar van de installatie" );
    $pdf->Text( 30, 50, "-       Futech koopt dag 1 alle certificaten van u af, zodat u dit geld onmiddelijk krijgt" );
    $pdf->Text( 30, 55, "-       U bent onafhankelijk van de energieprijzen en bent zeker dat u de certificaten zal krijgen" );
    $pdf->Text( 30, 60, "-       Niet meer afhankelijk van de staat" );
    $pdf->Text( 30, 65, "-       Niet meer afhankelijk van de energieleveranciers" );
    $pdf->Text( 30, 70, "-       Kleinere investering, met kortere terugverdientijd" );
    $pdf->Text( 30, 75, "-       Futech zal de certificaten verder verkopen aan de netbeheerder, geen administratie voor u" );
    $pdf->Text( 30, 80, "-       Extra zekerheid dat Futech de installatie naar behoren uitvoert" );
    $pdf->Text( 30, 85, "-       Bescherm u tegen de stijgende energieprijzen en wordt onafhankelijk" );
    
    //$pdf->Image('pdf/offerte/foto_p8.jpg', 20, 110, 29, 21 );
    
    
    $dak = $klant->cus_soort_dak;
    $ori_dak = $dak;
    
    if( $dak == 1 || $dak == 2|| $dak == 6 || $dak == 7 || $dak == 10 )
    {
    	$dak = 1;
    }
    
    if( $dak == 3 || $dak == 8 )
    {
    	$dak = 2;
    } 
    
    if( $dak == 4 || $dak == 5 || $dak == 9 )
    {
    	$dak = 3;
    }
    
    $ppp = 0;
    
    // prijs per paneel opzoeken
    $daksoort[1] = "wp_plat";
    $daksoort[2] = "wp_leien";
    $daksoort[3] = "wp_schans";
    
    $waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_wp_huur WHERE wp_start <= ". $klant->cus_aant_panelen ." AND wp_end >=" . $klant->cus_aant_panelen));
    $ppp = $waarde->$daksoort[ $dak ];
    
    $extra = 0;
    
    if( $klant->cus_aant_panelen > 24 && $klant->cus_driefasig == '0' )
    {
        $extra = $waarde->wp_3f;
    }
    
    // zwarte panelen
    if( $klant->cus_type_panelen == "Zwarte" )
    {
        $verhuur_zwarte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_zwarte' "));
        
        $ppp += $verhuur_zwarte->value;
    }
    
    if( $ori_dak == 9 )
    {
        // schans op voeten.
        $verhuur_schans_op_voeten = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_schans_op_voeten' "));
        $ppp += $verhuur_schans_op_voeten->value;
    }
    
    if( $ori_dak == 10 )
    {
        // Hellend roofing dak
        $verhuur_hellend_roofing = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_hellend_roofing' "));
        $ppp += $verhuur_hellend_roofing->value;
    }
    
    $prijsperpaneel = $ppp + $extra;
    
    $prijsperpaneel1 = $offerte_inst->factor_onderhoud2 + $extra;
    $prijsperpaneel2 = $offerte_inst->factor_onderhoud3 + $extra;
    
    $maandelijksekost = $klant->cus_aant_panelen * $prijsperpaneel;
    $maandelijksekost1 = $klant->cus_aant_panelen * $prijsperpaneel1;
    $maandelijksekost2 = $klant->cus_aant_panelen * $prijsperpaneel2;
    
    $factor_huur = $offerte_inst->huurfactor;
    
    $ber_deel2 = ceil($maandelijksekost) * 12 * 20;
    
    $huurkoop = ( $verwacht_opbrengst * 20 * $factor_huur) - ( $ber_deel2 ); 
    $huurkoop = number_format( $huurkoop, 0, "", " " );
    
    if( $klant->cus_aant_panelen < 10 )
    {
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 100, 5, "De huurformule is enkel geldig vanaf 10 panelen.", 0, 0, "L", true);
        
    }else
    {
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 90 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
        $pdf->SetXY( 115, 90 );
        $pdf->Cell( 35, 5, "    " . $klant->cus_aant_panelen, "LB", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 95 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 95 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 95 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 100 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 100 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen * $klant->cus_aant_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 100 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 105 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Investeringsbedrag (excl BTW) incl korting", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 105 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format($investeringsbedrag2,0,"", " "), "LBT", 0, "L", true);
        $pdf->SetXY( 135, 105 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        // BEREKEN 
        // $tot_opbr 
        
        $deg = 0.5;
        
        $tot_gsc = 0;
        $start = 100;
        
        $tot_opbr = number_format($verwacht_opbrengst,0,"","");
        
        for($i=0;$i<$offerte_inst->gsc_aant_jaar_huurkoop;$i++ )
        {
            if( $i > 0 )
            {
                $start = $start - $deg;
                $elec1 = $elec1 * 1.04;
            } 
            
            $ber = ($tot_opbr * ($start / 100) );
            
            //echo "<br>" . number_format($ber,0,"","");
            
            $tot_gsc += number_format($ber,0,"","");
            
            
            //$bespaar_elec += ($tot_opbr * ($start / 100) ) * $elec1;
            
            //echo "<br/>" . ($tot_opbr * ($start / 100) ) . " " . ($start / 100) . " " . $offerte_inst->gsc . " " . $ber . " " . $tot_gsc . " " . $bespaar_elec;
        }
        
        $tot_gsc = $tot_gsc * $offerte_inst->gsc_huurkoop;
        
        //echo $tot_opbr;
        
        $elec1 = $offerte_inst->elec;
        $bespaar_elec = 0;
        $start = 100;
        for($i=0;$i<20;$i++ )
        {
            if( $i > 0 )
            {
                $start = $start - $deg;
                $elec1 = $elec1 * 1.04;
            } 
            
            //$ber = ($tot_opbr * ($start / 100) ) * $offerte_inst->gsc;
            
            //$tot_gsc += $ber;
            
            $v_kwh = number_format( ($tot_opbr * ($start / 100) ), 0, "", "" );
            
            $bespaar_elec += number_format( $v_kwh * $elec1, 0, "", "" );
            
            //echo "<br>" . $bespaar_elec;
            
            //echo "<br/>" . $v_kwh . " " . ($start / 100) . " " . $offerte_inst->gsc . " " . $ber . " " . $tot_gsc . " " . $bespaar_elec . " " . $tot_opbr;
        }
        
        //echo "<br>" . $bespaar_elec;
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(216,157,64);
        $pdf->SetXY( 55, 110 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Afkoopsom GSC Futech", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 110 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format( $tot_gsc, 0, ",", " " ) , "LBT", 0, "L", true);
        $pdf->SetXY( 135, 110 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $te_bet = $investeringsbedrag2 - $tot_gsc;
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Te betalen (excl BTW)", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format( $te_bet, 0, ",", " " ) , "LBT", 0, "L", true);
        $pdf->SetXY( 135, 115 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $te_betalen = $te_bet * ( 1+ ( $btw / 100 ) );
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 120 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Te betalen ( incl ". $btw ."% BTW )", "RB", 0, "L", true);
        $pdf->SetXY( 115, 120 );
        $pdf->Cell( 35, 5, "    " . number_format( $te_betalen, 0, ",", " " ), "LB", 0, "L", true);
        $pdf->SetXY( 135, 120 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $te_betalen_b = $te_betalen;
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 130 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 130 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_kwhkwp , "LBT", 0, "L", true);
        $pdf->SetXY( 135, 130 );
        $pdf->Cell( 15, 5, "Wh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 135 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 135 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format(($klant->cus_w_panelen * $klant->cus_aant_panelen ) * ( $klant->cus_kwhkwp / 1000 ),0,"","")  , "LBT", 0, "L", true);
        $pdf->SetXY( 135, 135 );
        $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 140 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Premie gemeente", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 140 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    0", "LBT", 0, "L", true);
        $pdf->SetXY( 135, 140 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 145 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 145 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Netto investering", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format( $te_betalen ,0,"", " "), "LBT", 0, "L", true);
        $pdf->SetXY( 135, 150 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 155 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 155 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 160 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Opbrengsten", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 160 );
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 165 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Besparing electriciteit", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 165 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $elec, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 165 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 170 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 170 );
        $pdf->Cell( 35, 5, "    " . $elec, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 170 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 175 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 175 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
        
        $terugverdientijd = $te_betalen / $elec;
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 180 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Theoretische terugverdientijd", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 180 );
        $pdf->Cell( 35, 5, "    " . number_format($terugverdientijd,1,",",""), "LBT", 0, "L", true);
        $pdf->SetXY( 135, 180 );
        $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
        
        
        //echo $bespaar_elec . " - " . $te_betalen;
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 185 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Winst na 20 jaar", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 185 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "� " . number_format( $bespaar_elec - $te_betalen , 0, "", " " ), "BT", 0, "L", true);
        $pdf->SetXY( 134, 185 );
        $pdf->Cell( 15, 5, "", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 190 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 190 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 195 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Voorschot", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 195 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    60%", "LBT", 0, "L", true);
        $pdf->SetXY( 134, 195 );
        $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.6, 0, "", " " ), "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 200 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Na oplevering", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 200 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    40%", "LBT", 0, "L", true);
        $pdf->SetXY( 134, 200 );
        $pdf->Cell( 15, 5, "� " . number_format( $te_betalen * 0.4, 0, "", " " ), "BT", 0, "L", true);
    }
    
    $pdf->Image('pdf/offerte/zilver.jpg', 20, 95, 25, 40 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "8" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    
    /********************************************************* PAGINA 8 **********************************************************/
    /*
    $pdf->AddPage(); 
    $pdf->SetFont('Arial', '', 10);
    
    //$titel1 = "HUURFORMULE";
    $titel1 = "OLYMPISCH BRONS";
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFillColor(0,176,80);
    $pdf->SetXY( 18.5, 25 );
    $pdf->Cell( 180, 7, $titel1, 0, 0, "L", true);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 40, "Door de te grote vraag wordt dit product voorlopig niet meer aangeboden." );
    
    // unset
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 20, 40, "FUTECH investeert massaal in groene energie en is daarvoor op zoek naar zoveel mogelijk daken om zonnepanelen" );
    $pdf->Text( 20, 45, "op te plaatsen." );
    $pdf->Text( 30, 50, "-       U investeert zelf niets" );
    $pdf->Text( 30, 55, "-       U stelt ons uw dak 20 jaar ter beschikking" );
    $pdf->Text( 30, 60, "-       De zonnepanelen leveren u gratis stroom" );
    $pdf->Text( 30, 65, "-       U betaalt maandelijks een kleine onderhoudsbijdrage gedurende 20 jaar" );
    $pdf->Text( 30, 70, "-       Futech blijft eigenaar van de installatie en de opgewekte certificaten" );
    $pdf->Text( 30, 75, "-       Na 20 jaar krijgt u de zonnepaneel installatie tegen een spotprijs die voordien wordt afgesproken" );
    $pdf->Text( 30, 80, "-       Bij defect aan de installatie wordt dit kosteloos hersteld gedurende 20 jaar" );
    
    //$pdf->Image('pdf/offerte/foto_p8.jpg', 20, 110, 29, 21 );
    
    
    $dak = $klant->cus_soort_dak;
    $ori_dak = $dak;
    
    if( $dak == 1 || $dak == 2|| $dak == 6 || $dak == 7 || $dak == 10 )
    {
    	$dak = 1;
    }
    
    if( $dak == 3 || $dak == 8 )
    {
    	$dak = 2;
    } 
    
    if( $dak == 4 || $dak == 5 || $dak == 9 )
    {
    	$dak = 3;
    }
    
    $ppp = 0;
    
    // prijs per paneel opzoeken
    $daksoort[1] = "wp_plat";
    $daksoort[2] = "wp_leien";
    $daksoort[3] = "wp_schans";
    
    $waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_wp_huur WHERE wp_start <= ". $klant->cus_aant_panelen ." AND wp_end >=" . $klant->cus_aant_panelen));
    $ppp = $waarde->$daksoort[ $dak ];
    
    $extra = 0;
    
    if( $klant->cus_aant_panelen > 24 && $klant->cus_driefasig == '0' )
    {
        $extra = $waarde->wp_3f;
    }
    
    // zwarte panelen
    if( $klant->cus_type_panelen == "Zwarte" )
    {
        $verhuur_zwarte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_zwarte' "));
        
        $ppp += $verhuur_zwarte->value;
    }
    
    if( $ori_dak == 9 )
    {
        // schans op voeten.
        $verhuur_schans_op_voeten = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_schans_op_voeten' "));
        $ppp += $verhuur_schans_op_voeten->value;
    }
    
    if( $ori_dak == 10 )
    {
        // Hellend roofing dak
        $verhuur_hellend_roofing = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_config WHERE field = 'verhuur_hellend_roofing' "));
        $ppp += $verhuur_hellend_roofing->value;
    }
    
    $prijsperpaneel = $ppp + $extra;
    
    $prijsperpaneel1 = $offerte_inst->factor_onderhoud2 + $extra;
    $prijsperpaneel2 = $offerte_inst->factor_onderhoud3 + $extra;
    
    $maandelijksekost = $klant->cus_aant_panelen * $prijsperpaneel;
    $maandelijksekost1 = $klant->cus_aant_panelen * $prijsperpaneel1;
    $maandelijksekost2 = $klant->cus_aant_panelen * $prijsperpaneel2;
    
    $factor_huur = $offerte_inst->huurfactor;
    
    $ber_deel2 = ceil($maandelijksekost) * 12 * 20;
    
    $huurkoop = ( $verwacht_opbrengst * 20 * $factor_huur) - ( $ber_deel2 ); 
    $huurkoop = number_format( $huurkoop, 0, "", " " );
    
    if( $klant->cus_aant_panelen < 10 )
    {
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 100, 5, "De huurformule is enkel geldig vanaf 10 panelen.", 0, 0, "L", true);
        
    }else
    {
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 90 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Aantal panelen", "RB", 0, "L", true);
        $pdf->SetXY( 115, 90 );
        $pdf->Cell( 35, 5, "    " . $klant->cus_aant_panelen, "LB", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 95 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Vermogen paneel", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 95 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 95 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 100 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Totale vermogen installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 100 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_w_panelen * $klant->cus_aant_panelen, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 100 );
        $pdf->Cell( 15, 5, "Wp", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 105 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Te betalen (incl. BTW)", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 105 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "     0", "LBT", 0, "L", true);
        $pdf->SetXY( 135, 105 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 110 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 110 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RBT", 0, "L", true);
        $pdf->SetFillColor(216,157,64);
        $pdf->SetXY( 115, 115 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 30, 5, "    " . ceil($maandelijksekost), "LBT", 0, "L", true);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 134, 115 );
        $pdf->Cell( 16, 5, "�/maand", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 120 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 120 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 125 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Efficientie installatie", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 125 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $klant->cus_kwhkwp, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 125 );
        $pdf->Cell( 15, 5, "Wh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 130 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Jaarlijks inkomend vermogen", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 130 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . number_format($verwacht_opbrengst,2,",",""), "LBT", 0, "L", true);
        $pdf->SetXY( 135, 130 );
        $pdf->Cell( 15, 5, "kWh", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 135 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 135 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        //$bedrag = "0,22";
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 140 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Energieprijs", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 140 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $offerte_inst->elec, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 140 );
        $pdf->Cell( 15, 5, "�/kWh", "BT", 0, "L", true);
        
        $bedrag = "4";
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 145 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 60, 5, "Indexatie energieprijzen", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 145 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $bedrag, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 145 );
        $pdf->Cell( 15, 5, "%", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 150 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 155 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Opbrengsten", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 155 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, " ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 160 );
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell( 95, 5, "Besparing electriciteit- onderhoudskosten", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 165 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Totaal", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 165 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    " . $huurkoop, "LBT", 0, "L", true);
        $pdf->SetXY( 135, 165 );
        $pdf->Cell( 15, 5, "EURO", "BT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY( 55, 170 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 170 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "    ", "LBT", 0, "L", true);
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 55, 175 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Terugverdientijd", "RBT", 0, "L", true);
        $pdf->SetXY( 115, 175 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 35, 5, "     0", "LBT", 0, "L", true);
        $pdf->SetXY( 135, 175 );
        $pdf->Cell( 15, 5, "jaar", "BT", 0, "L", true);
    }
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 190, "Gelieve bij interesse volgende documenten aan Futech te bezorgen :" );
    $pdf->Text( 25, 195, "- Loonfiche van het gezinshoofd" );
    $pdf->Text( 25, 200, "- Eigendomsbewijs van het huis" );
    $pdf->Text( 25, 205, "- Brief dat de hypotheekhouder ingelicht en akkoord is, gelieve er rekening mee te houden dat" );
    $pdf->Text( 25, 210, "  sommige banken hier een vergoeding voor vragen." );
    
    $pdf->Text( 20, 220, "Sleutelvoorwaarde om aan deze formule te voldoen is dat je moet beslissen voor onderstaande data. Dit omdat" );
    $pdf->Text( 20, 225, "dan de subsidies nogmaals verder worden afgebouwd. Indien er na deze datum beslist wordt, zal de" );
    $pdf->Text( 20, 230, "onderhoudskost aangepast worden. Pas als bovenstaande documenten ontvangen zijn is het dossier ontvankelijk,");
    $pdf->Text( 20, 235, "enkel indien dit binnen de 5 dagen gebeurd is onderstaande kost geldig.");
    
    if( $klant->cus_aant_panelen >= 10 )
    {
        $pdf->Text( 20, 245, "Voor" );
        $pdf->Text( 45, 245, date("d-m-Y", mktime(0, 0, 0, date('m'), date('d')+8, date('Y')) ) );
        
        $pdf->Text( 20, 250, "Na" );
        $pdf->Text( 45, 250, date("d-m-Y", mktime(0, 0, 0, date('m'), date('d')+8, date('Y')) ) );
        
        $dat_onderhoud = changeDate2EU($offerte_inst->datum_onderhouds);
        $dat_onderhoud = str_replace("-", "/", $dat_onderhoud);
        
        $pdf->Text( 20, 255, "Na " . $dat_onderhoud );
        
        $pdf->Text( 20, 265, "De formule wordt geregeld en juridisch omkaderd door opstalregeling uitgewerkt op pag 10 tot en met 15." );
        
        
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(0,176,80);
        $pdf->SetXY( 65, 241 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RB", 0, "L", true);
        $pdf->SetXY( 125, 241 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost) . "   �/MAAND ", "LB", 0, "L", true);
        
        $pdf->SetXY( 65, 246 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RBT", 0, "L", true);
        $pdf->SetXY( 125, 246 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost1) . "   �/MAAND ", "LBT", 0, "L", true);
        
        $pdf->SetXY( 65, 251 );
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell( 60, 5, "Maandelijkse onderhoudskost", "RT", 0, "L", true);
        $pdf->SetXY( 125, 251 );
        $pdf->Cell( 35, 5, ceil($maandelijksekost2) . "   �/MAAND ", "LT", 0, "L", true);
    }
    
    $pdf->Image('pdf/offerte/brons.jpg', 20, 95, 25, 40 );
    */
    
    // footer
    /*
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "9" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    
    /********************************************************* PAGINA 9 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina9_nw.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 20, 69.5, "Overeenkomst tussen Futech en de klant : Olympisch Goud" );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 84, 126, $klant->cus_w_panelen );
    $pdf->Text( 105, 126, $klant->cus_aant_panelen );
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 135, 166.5, number_format($investeringsbedrag2,0,""," ") );
    $pdf->Text( 135, 171.5, number_format($te_betalen_a,0,""," ") );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 80, 166.5, "Voorschot  40% : � " . number_format( $te_betalen * 0.4, 0, "", " " ) );
    $pdf->Text( 80, 171.5, "Oplevering 60% : � " . number_format( $te_betalen * 0.6, 0, "", " " ) );
    
    
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 40, 192.5, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 207.5, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 213, $klant->cus_postcode );
    $pdf->Text( 76, 213, html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10 );
    $pdf->Text( 50, 218, $klant->cus_tel);
    $pdf->Text( 50, 223, $klant->cus_gsm);
    
    $daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 233, $klant->cus_email );
    $pdf->Text( 150, 223.5, $daksoorten[ $klant->cus_soort_dak ] );
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    $pdf->Text( 135, 199.5, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->Text( 162, 238.7, $opwoning );
    
    //$pdf->Image('pdf/offerte/goud.jpg', 70, 85, 20, 30 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 8b **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina9b_nw.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 20, 69.5, "Overeenkomst tussen Futech en de klant : " . ucwords( $offerte_inst->naam_huurkoop ) );
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->Text( 19, 172, "De klant koopt enkel de zonnepanelen zonder de certificaten." );
    $pdf->Text( 19, 176.5, "De groenstroomcertificaten worden eigendom van futech volgens de documenten vanaf pagina 20." );
    
    
    $pdf->Text( 84, 126, $klant->cus_w_panelen );
    $pdf->Text( 105, 126, $klant->cus_aant_panelen );
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 135, 166.5, number_format($te_bet,0,""," ") );
    $pdf->Text( 135, 171.5, number_format($te_betalen_b,0,""," ") );
    
    //echo $te_bet . " " . $investeringsbedrag2a;
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 40, 192.5, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 207.5, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 213, $klant->cus_postcode );
    $pdf->Text( 76, 213, html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10 );
    $pdf->Text( 50, 218, $klant->cus_tel);
    $pdf->Text( 50, 223, $klant->cus_gsm);
    
    $daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 233, $klant->cus_email );
    $pdf->Text( 150, 223.5, $daksoorten[ $klant->cus_soort_dak ] );
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    $pdf->Text( 135, 199.5, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->Text( 162, 238.7, $opwoning );
    
    $pdf->Image('pdf/offerte/zilver.jpg', 70, 85, 20, 30 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "11" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 10 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina10_nw.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 20, 69.5, "Overeenkomst tussen Futech en de klant : Olympisch Brons");
    $pdf->SetFont('Arial', '', 10);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 84, 126, $klant->cus_w_panelen );
    $pdf->Text( 105, 126, $klant->cus_aant_panelen );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 50, 192.5, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 55, 202.4, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 37, 208, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10 );
    
    if( !empty($klant->cus_tel) && !empty($klant->cus_gsm) )
    {
        $tel = $klant->cus_tel . " / " . $klant->cus_gsm;    
    }else
    {
        $tel = $klant->cus_tel . $klant->cus_gsm;
    }
    
    $pdf->Text( 50, 213, $tel );
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text( 25, 223, $klant->cus_email );
    
    $acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    $pdf->Text( 135, 199.5, $acma->naam . " " . $acma->voornaam );
    
    $opwoning = "";
    switch( $klant->cus_opwoning )
    {
        case "2" :
            $opwoning = "Niet ingevuld";
            break;
        case "0" :
            $opwoning = "Neen";
            break;
        case "1" :
            $opwoning = "Ja";
            break;
    }
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Text( 135, 166.5, ceil($maandelijksekost) );
    $pdf->Text( 135, 171.5, number_format($investeringsbedrag2,0,""," "));
    
    $pdf->Text( 162, 207.75, $opwoning );
    $pdf->Text( 151, 224, $daksoorten[ $klant->cus_soort_dak ] );
    
    $pdf->Image('pdf/offerte/brons.jpg', 70, 85, 20, 30 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "12" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 11 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina11.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 80, 71, html_entity_decode($klant->cus_naam, ENT_QUOTES ) ); 
    
    $pdf->Text( 45, 76, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 45, 81, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 65, 90, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    
    //$pdf->Image('pdf/offerte/goud.jpg', 140, 257, 10, 18 );
    //$pdf->Image('pdf/offerte/zilver.jpg', 150, 257, 10, 18 );
    //$pdf->Image('pdf/offerte/brons.jpg', 160, 257, 10, 18 );
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 280, $pagina . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 12 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina12.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    //$pdf->Image('pdf/offerte/goud.jpg', 160, 267, 10, 18 );
    //$pdf->Image('pdf/offerte/zilver.jpg', 170, 267, 10, 18 );
    //$pdf->Image('pdf/offerte/brons.jpg', 180, 267, 10, 18 );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina );
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    /********************************************************* PAGINA 13 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina13.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $verkoper = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant->cus_acma));
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Text( 50, 33.5, $verkoper->voornaam ." " . $verkoper->naam );
    $pdf->Text( 27, 42, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 42, 58, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    $pdf->Image('pdf/offerte/brons.jpg', 190, 257, 10, 18 );
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "15" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    
    /********************************************************* PAGINA 14 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina14.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    $pdf->Image('pdf/offerte/brons.jpg', 190, 257, 10, 18 );
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "16" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 15 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina15.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->Image('pdf/offerte/brons.jpg', 190, 257, 10, 18 );
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "17" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 16 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina16.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Text( 45, 136, html_entity_decode($klant->cus_naam, ENT_QUOTES ) );
    $pdf->Text( 80, 146, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    
    $pdf->Text( 92, 155.5, $klant->cus_postcode );
    $pdf->Text( 125, 155.5, html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 80, 197, maakReferte($klant->cus_id, "") );
    
    $pdf->Image('pdf/offerte/brons.jpg', 180, 257, 10, 18 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "18" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 17 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/pagina17.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    $pdf->Image('pdf/offerte/brons.jpg', 180, 257, 10, 18 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "19" . $max_pagina );
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 18 **********************************************************/
    if( $_SESSION[ $session_var ]->user_id == 19 || 1 == 1 )
    {
        if( $klant->cus_dag != 0.00 && $klant->cus_nacht != 0.00 && (  $klant->cus_dag_tarief != 0.00 && $klant->cus_nacht_tarief != 0.00  ) )
        {
            $pdf->AddPage(); 
            $pdf->setSourceFile('pdf/aanmaning.pdf'); 
            // import page 1 
            $tplIdx = $pdf->importPage(1); 
            //use the imported page and place it at point 0,0; calculate width and height
            //automaticallay and ajust the page size to the size of the imported page 
            $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Text( 20, 55, "Keuze dag, of dag/nacht tarief" );
            
            // Color and font restoration
            $pdf->SetFillColor(224,235,255);
            $pdf->SetTextColor(0);
            $pdf->SetLineWidth(.3);
            $pdf->SetFont('Arial', '', 10);
            
            
            
            // Data
            
            $pdf->SetXY(20, 70);
            $pdf->Cell( 100,6, "Verbruik piek-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format($klant->cus_dag, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $pdf->SetXY(20, 76);
            $pdf->Cell( 100,6, "Verbruik dal-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($klant->cus_nacht, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "kWh",1,0,'L',true);
            
            $tot_kwhkwp = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 );
            $pdf->SetXY(20, 82);
            $pdf->Cell( 100,6, "PV: Totaal aantal kWh opgewekt per jaar",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $tot_kwhkwp, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $kwh_piek = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 ) * (5/7);
            $pdf->SetXY(20, 88);
            $pdf->Cell( 100,6, "PV: Aantal kWh opgewekt gedurende piek-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $kwh_piek, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "kWh",1,0,'L',true);
            
            $kwh_dal = $klant->cus_aant_panelen * $klant->cus_w_panelen * ( $klant->cus_kwhkwp / 1000 ) * (2/7);
            $pdf->SetXY(20, 94);
            $pdf->Cell( 100,6, "PV: Aantal kWh opgewekt gedurende dal-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $kwh_dal, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "kWh",1,0,'L',false);
            
            $pdf->SetXY(20, 100);
            $pdf->Cell( 100,6, "Tarief piek-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $klant->cus_dag_tarief , 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "all in",1,0,'L',true);
            
            $pdf->SetXY(20, 106);
            $pdf->Cell( 100,6, "Tarief dal-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $klant->cus_nacht_tarief, 2, ",", ""),1,0,'R',false);
            $pdf->Cell(  30,6, "all in",1,0,'L',false);
            
            $pdf->SetXY(20, 112);
            $pdf->Cell( 100,6, "Vaste minimale vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $klant->cus_vergoeding, 2, ",", ""),1,0,'R',true);
            $pdf->Cell(  30,6, "� per jaar",1,0,'L',true);
            
            $pdf->SetXY(20, 118);
            $pdf->Cell( 100,6, "",1,0,'L',false);
            $pdf->Cell(  40,6, "",1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 124);
            $pdf->Cell( 100,6, "Momenteel betaalt u aan de energiemaatschappij :",1,0,'L',true);
            $pdf->Cell(  40,6, "",1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 130);
            $pdf->Cell( 100,6, "Voor verbruik tijdens piek-uren",1,0,'L',false);
            $pdf->Cell(  40,6, number_format($klant->cus_dag * $klant->cus_dag_tarief, 2, ',', '') ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $pdf->SetXY(20, 136);
            $pdf->Cell( 100,6, "Voor verbruik tijdens dal-uren",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($klant->cus_nacht * $klant->cus_nacht_tarief, 2, ',', ''),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $pdf->SetXY(20, 142);
            $pdf->Cell( 100,6, "Totaal",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( ($klant->cus_dag * $klant->cus_dag_tarief)+($klant->cus_nacht * $klant->cus_nacht_tarief), 2, ',', '') ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $pdf->SetXY(20, 148);
            $pdf->Cell( 100,6, "",1,0,'L',true);
            $pdf->Cell(  40,6, "",1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 154);
            $pdf->Cell( 100,6, "Na plaatsing zonnepanelen, met dag/nacht teller",1,0,'L',false);
            $pdf->Cell(  40,6, "" ,1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $dn_tot_dag = ($klant->cus_dag-$kwh_piek) * $klant->cus_dag_tarief;
            
            if( $dn_tot_dag < 0 )
            {
                $dn_tot_dag = 0;
            }
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 160);
            $pdf->Cell( 100,6, "Dag",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $dn_tot_dag, 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $dn_tot_nacht = ($klant->cus_nacht-$kwh_dal) * $klant->cus_nacht_tarief;
            
            if( $dn_tot_nacht < 0 )
            {
                $dn_tot_nacht = 0;
            }
            $pdf->SetXY(20, 166);
            $pdf->Cell( 100,6, "Nacht",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $dn_tot_nacht, 2, ",", "" ),1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $tot_dn = $dn_tot_dag+$dn_tot_nacht+$klant->cus_vergoeding;
            $pdf->SetXY(20, 172);
            $pdf->Cell( 100,6, "Totaal + vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format( $tot_dn, 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            $pdf->SetXY(20, 178);
            $pdf->Cell( 100,6, "",1,0,'L',false);
            $pdf->Cell(  40,6, "",1,0,'R',false);
            $pdf->Cell(  30,6, "",1,0,'L',false);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY(20, 184);
            $pdf->Cell( 100,6, "Na plaatsing zonnepanelen, met enkel dag teller",1,0,'L',true);
            $pdf->Cell(  40,6, "" ,1,0,'R',true);
            $pdf->Cell(  30,6, "",1,0,'L',true);
            
            $enkel_dag = ($klant->cus_nacht + $klant->cus_dag - $tot_kwhkwp) * $klant->cus_dag_tarief;
            if( $enkel_dag < 0 )
            {
                $enkel_dag = 0;
            }
            
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(20, 190);
            $pdf->Cell( 100,6, "Dag",1,0,'L',false);
            $pdf->Cell(  40,6, number_format( $enkel_dag, 2, ",", "" ) ,1,0,'R',false);
            $pdf->Cell(  30,6, "�",1,0,'L',false);
            
            $tot_enkel = $enkel_dag+$klant->cus_vergoeding;
            
            if( $tot_enkel < 0 )
            {
                $tot_enkel = 0;
            }
            
            $pdf->SetXY(20, 196);
            $pdf->Cell( 100,6, "Totaal + vergoeding",1,0,'L',true);
            $pdf->Cell(  40,6, number_format($tot_enkel , 2, ",", "" ),1,0,'R',true);
            $pdf->Cell(  30,6, "�",1,0,'L',true);
            
            if( $tot_dn == $tot_enkel )
            {
                $concl = "De 2 bedragen zijn gelijk. In dit geval maakt het niet uit.";
            }else
            {
                if( $tot_dn < $tot_enkel )
                {
                    $concl = "Dag/nacht-teller komt goedkoper uit.";
                }else
                {
                    $concl = "Dag-teller komt goedkoper uit.";
                }
            }
            
            $pdf->SetFont('Arial', 'BU', 12);
            $pdf->Text(40, 215, "Conclusie : " . $concl);
            
            
            
            // footer
            $pdf->SetFont('Arial', '', 10);
            $pagina++;
            $pdf->Text( 20, 280, $pagina . $max_pagina);
            $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
        }
    }
    
    /********************************************************* PAGINA 21 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/huurkoop1.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 125, 92, $acma->naam . " " . $acma->voornaam );
    
    
    $pdf->Text( 80, 49, html_entity_decode($klant->cus_naam, ENT_QUOTES ) ); 
    $pdf->Text( 80, 54, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr );
    $pdf->Text( 80, 59, $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 55, 198.9, html_entity_decode($klant->cus_straat, ENT_QUOTES ) . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . html_entity_decode($klant->cus_gemeente, ENT_QUOTES ) );
    
    $pdf->Text( 167, 208.5, date('d') . "-" . date('m') . "-" . date('Y') );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    $pdf->Image('pdf/offerte/zilver.jpg', 190, 257, 10, 18 );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "21" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 22 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/huurkoop2.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->Image('pdf/offerte/zilver.jpg', 190, 257, 10, 18 );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "22" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    
    /********************************************************* PAGINA 23 **********************************************************/
    /*
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/huurkoop3.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    
    $pdf->Image('pdf/offerte/zilver.jpg', 190, 257, 10, 18 );
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "23" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/offerte/huurkoop4.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 185, 230, "Handtekening" );
    $pdf->Text( 185, 235, "klant :" );
    
    $pdf->Image('pdf/offerte/zilver.jpg', 190, 257, 10, 18 );
    
    // 2130049447
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text( 53, 184, "Tessenderlo" );
    $pdf->Text( 100, 184, date('d') ."-" . date('m')."-".date('Y') );
    
    $pdf->Text( 105, 219, $acma->naam . " " . $acma->voornaam );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 20, 280, "24" . $max_pagina);
    $pdf->Text( 75, 280, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    */
    /********************************************************* PAGINA 25 **********************************************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/combi1.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    $pdf->Text( 20, 10, "Enkel op voorwaarde dat finalia de goedkeuring geeft." );
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    /****************************/
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/combi2.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    /****************************/
    
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/finalia.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    /****************************/
    
    
    $pdf->AddPage(); 
    
    $pdf->setSourceFile('pdf/overzicht_futech_ok.pdf'); 
    // import page 1 
    $tplIdx = $pdf->importPage(1); 
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
    // footer
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    $pdf->AddPage(); 
    $pdf->setSourceFile('pdf/friends_actie.pdf'); 
    $tplIdx = $pdf->importPage(1); 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    $pdf->SetFont('Arial', '', 10);
    $pagina++;
    $pdf->Text( 20, 290, $pagina . $max_pagina);
    $pdf->Text( 75, 290, "Ambachtstraat 18/19, 3980 Tessenderlo" );
    
    if( $output == "S" )
	{
		$ret["filename"] = 'Offerte_met_huurvoorstel.pdf';
        $ret["factuur"] = $pdf->Output('Offerte_met_huurvoorstel.pdf', $output);
		return $ret;
	}else
	{
		$pdf->Output('Offerte_met_huurvoorstel.pdf', $output);	
	}
}

/*
 * PDF omruildocument voor SMA
 */
function sma_omruil($output, $id)
{
	require "kalender/inc/fpdf.php";
	require "kalender/inc/fpdi.php";
	
	$user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_users WHERE user_id = " . $_SESSION["user"]->user_id));
	$onderdeel = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_sma_retour WHERE id = " . $id));
	$project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $onderdeel->project_id));
	
	$bestandsnaam = str_replace(" ", "_", $project->name) . "_" . $onderdeel->sn; 
	
	$pdf = new FPDI();
	$pdf->AddPage("L"); 
	$pdf->setSourceFile('kalender/pdf/sma_omwisseling.pdf');
	
	//$pdf->setSourceFile('kalender/pdf/aanmaning.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pageSize = $pdf->getTemplateSize($tplIdx);
	
	$pdf->useTemplate($tplIdx, 0, 0, $pageSize["w"], $pageSize["h"], true); 
	
	
	$pdf->SetFont('Arial', '', 9); 
	$pdf->SetTextColor(0,0,0);
	
    $ref = "FU_" . $bestandsnaam . $doc_nummer;
	
    if( !empty( $onderdeel->ret_code ) )
    {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Text(25, 144, "Omruilcode : " . $onderdeel->ret_code );
    }
    
    $pdf->SetFont('Arial', '', 9);
	$pdf->Text(150, 42.5, $user->voornaam . " " . $user->naam );
	$pdf->Text(150, 46, $ref );
	$pdf->Text(150, 49.5, "Futech" );
	$pdf->Text(150, 53, "Ambachtstraat 19, 3980 Tessenderlo" );
	$pdf->Text(150, 56.5, $user->tel );
	$pdf->Text(150, 60, $user->email );
	$pdf->Text(150, 64, "BE 0808 756 108" );
	
	$pdf->Text(150, 79, $onderdeel->type );
	$pdf->Text(150, 83, $onderdeel->sn );

	$pdf->Text(150, 93.5, $onderdeel->comm );
	$pdf->Text(150, 97, $onderdeel->display );
	$pdf->Text(150, 100.5, $onderdeel->conn );
	$pdf->Text(150, 104, $onderdeel->ess );
	
	$pdf->Text(150, 114.5, $onderdeel->leds );
	$pdf->Text(150, 118, $onderdeel->error );
	
	$pdf->SetXY(150, 119);
	$pdf->MultiCell(90,4, $onderdeel->tests,0,'L', 0);
	
	$pdf->Text(150, 166, "Elke werkdag tussen 9u en 18u" );
	$pdf->Text(150, 171, "Gelieve op voorhand een seintje te geven." );
	
	// Tonen van het soort document
	$pdf->SetFont('Arial', 'B', 16);
	$pdf->SetTextColor(0,0,0);
	
	// now write some text above the imported page 
	$pdf->SetFont('Arial', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	$pdf->SetFont('Arial', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $doc_nummer .'.pdf';
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = "FU_" . $bestandsnaam . $doc_nummer .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output("FU_" . $bestandsnaam. $doc_nummer .'.pdf', $output);	
	}
}

function maak_fac_gsc_ind($output, $id_arr)
{
    // al de gegevens bevinden zich in 
    // $_SESSION["fac_vreg"]["id"]
    // $_SESSION["fac_vreg"]["fac_nr"] 
    require_once "inc/fpdf.php";
	require_once "inc/fpdi.php";
    
    $pdf = new FPDI();
    
    // zoeken naar factuurnummer
	$nw_boek_jaar = "01-07";
    $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
    $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
    
    $zoek_fac1 = 0;
    if( $mk_nu >= $mk_nw_boek_jaar )
    {
        //echo "<br> NA 01-07";
        $jaar_1 = date('Y') + 1;
        $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,$jaar_1) );
        
        $q_geenfac = mysqli_query($conn, "SELECT * 
								FROM kal_customers_files
								WHERE cf_soort = 'factuur'
								AND cf_id != 670
								AND cf_id != 671
                                AND cf_id != 2337
                                AND cf_id != 2827
                                AND cf_date BETWEEN '". date('Y') ."-07-01' AND '". $jaar_2 ."'
								ORDER BY 1 DESC");
    }else{
        //echo "<br> VOOR 01-07";
        $jaar_1 = date('Y') - 1;
        $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,date('Y')) );
        
        $q_geenfac = mysqli_query($conn, "SELECT * 
								FROM kal_customers_files
								WHERE cf_soort = 'factuur'
								AND cf_id != 670
								AND cf_id != 671
                                AND cf_id != 2337
                                AND cf_id != 2827
                                AND cf_date BETWEEN '". $jaar_1 ."-07-01' AND '". $jaar_2 ."'
								ORDER BY 1 DESC");
    }
	
	while( $rij = mysqli_fetch_object($q_geenfac) )
	{
		$factuur = explode(".", $rij->cf_file);
		
		if( is_numeric( $factuur[0] ) && $zoek_fac1 < $factuur[0] )
		{
			$zoek_fac1 = $factuur[0];
		}
	}
    
    $factuur_nr = $zoek_fac1+1;
    
    $max_aant_regels = 22;
    //echo "<br>" . $factuur_nr;
    /*
    $id_arr = array();
    $tel_arr = array();
    foreach( $_SESSION["fac_vreg"]["id"] as $id )
    {
        $v = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_fac_gsc_vreg WHERE id = " . $id));
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_pvz = '". $v->pvz ."'"));
        $n = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_fac_gsc WHERE cus_id = " . $klant->cus_id)); 
        $id_arr[ $n->netbeheerder ][ $v->pvz ][ $v->periode ] = array( "aantal" => $v->aantal, 
                                                                       "arei" => substr($klant->cus_arei_datum, 0, 10) ) ;
        
        $tel_arr[$n->netbeheerder]++;
    }
    */
    
    foreach( $id_arr as $netb => $pvz )
    {
        /*
        foreach( $pvz as $p => $aant )
        {
            echo "<br>" . $p . " ";
            
            foreach( $aant as $periode => $a )
            {
              echo $periode . " " . $a;  
            }
        }
        */
        
        if( $tel_arr[$netb] > $max_aant_regels )
        {
            die("Meerdere pagina's nog voorzien");
        }
        
        
        // opzoeken netbeheerder
        $q_net = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_naam = '". $netb ."'");
        $aant_netb = mysqli_num_rows($q_net);
        
        if( $aant_netb == 0 )
        {
            die("Leverancier met de naam " . $netb . " is niet teruggevonden in de databank");
        }else
        {
            $klant = mysqli_fetch_object($q_net);
        }  
        
        $pdf->AddPage(); 
        
        $pdf->AddFont('eurosti','','eurosti.php');
	    $pdf->setSourceFile('pdf/factuur.pdf');
        $tplIdx = $pdf->importPage(1); 
    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    	$pdf->SetFont('eurosti', '', 16);
    	$pdf->SetTextColor(0,0,0);
        
        $pdf->Text(30, 39, "Factuur" );
    
    	// tonen van het documents nr
  		$pdf->SetFont('eurosti', '', 10);
        
        $datum = date('d') . "-" . date('m') . "-" . date('Y'); 
    	
    	$pdf->Text(40, 52.5, $datum );
    	
    	$tmp_dat = explode("-", $datum);
    	
    	$jaarmaand = "";
    
    	if( strlen( $tmp_dat[2] ) == 4 )
    	{
    		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
    	}
    	$pdf->Text(40, 59.25, $jaarmaand . "-" . $factuur_nr );
        
        if( $klant->cus_fac_adres == "1" )
    	{
    		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
    		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
    	}else
    	{
    		
    		if( !empty( $klant->cus_bedrijf ) )
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
    			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
    		}else
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
    		}
    		
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
    		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
    		
    		if( !empty( $klant->cus_btw ) )
    		{
    			$pdf->Text(40, 72.75, $klant->cus_btw );
    		}
    	}
        
        $extra_offset = 0;
        $telme = 0;
        $excl = 0;
        foreach( $pvz as $p => $aant )
        {
            foreach( $aant as $periode => $a )
            {
                $q_upd = "UPDATE kal_fac_gsc_vreg SET fac = '" . $factuur_nr . ".pdf' WHERE id = " . $a["id"];
                mysqli_query($conn, $q_upd);
                //echo "<br>" . $q_upd;
                
                $pdf->SetXY( 44, 112+$extra_offset );
			
    			// soort van het artikel ophalen
                $p1 = explode("-", $periode);
                
                $arei_dmy = explode("-", $a["arei"] );
                $mk_arei = mktime( 0,0,0,$arei_dmy[1], $arei_dmy[0], $arei_dmy[2] );
                $q_gsc = mysqli_query($conn, "SELECT * FROM kal_gsc_waarde WHERE gt250 = '0'");
                $prijs = 0;
                while( $gsc = mysqli_fetch_object($q_gsc) )
                {
                    $start = explode("-", $gsc->van );
                    $eind = explode("-", $gsc->tot );
                    
                    $mk_start = mktime(0,0,0, $start[1], $start[2], $start[0] );
                    $mk_eind = mktime(0,0,0, $eind[1], $eind[2], $eind[0] );
                    
                    //echo "<br>a" . $mk_start . " " . $mk_eind;
                    
                    if( $mk_arei <= $mk_eind && $mk_arei >= $mk_start )
                    {
                        $prijs = $gsc->waarde;
                        break; 
                    }
                }
                
				$pdf->Cell( 102, 5, $p1[1] ."-". $p1[0] . " - " . $prijs . "euro/MWh", 0, 1,'L');
				
				$pdf->SetXY( 17, 112+$extra_offset );
				$pdf->Cell( 25, 5, $p, 0, 1,'L');
    			
    			$pdf->SetXY( 145, 112+$extra_offset );
    			$pdf->Cell( 20, 5, $a["aantal"], 0, 1, 'R');
    			
    			$pdf->SetXY( 160, 112+$extra_offset );
    			$pdf->Cell( 26, 5, number_format( $prijs * $a["aantal"], 0, "", " " ), 0, 1, 'R');
                
                // toevoegen euro teken aan elke regel die wordt afgedrukt
                $euro_arr[] = 115.75 + $extra_offset;
    
    			$extra_offset += 5.5;
    			
    			$excl += $prijs * $a["aantal"];
                
                $telme++;
                unset( $regels[$key] );
                if( $telme == $max_aant_regels )
                {
                    break;
                }
            }
        }

        $incl = $excl * 1.21;
		$btw = $incl - $excl;

        $pdf->SetXY( 168, 234 );
	    $pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');

        $pdf->Text(159, 246, "21%" );
    
		//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
		$pdf->SetXY( 163, 242.25 );
		$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
        
        $pdf->SetXY( 168, 250 );
	    $pdf->Cell(25, 5, "  " . number_format($incl, 2, ",", " " ),0,1,'R');
        
        // toevoegen factuurvoorwaarden.
        $pdf->AddPage();
        $pdf->setSourceFile('pdf/factuur_vw.pdf');
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    }
    
    //force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $factuur_nr .'.pdf';
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = $factuur_nr .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output("FU_" . $bestandsnaam. $doc_nummer .'.pdf', $output);	
	}
}

function maak_fac_gsc($output)
{
    // al de gegevens bevinden zich in 
    // $_SESSION["fac_vreg"]["id"]
    // $_SESSION["fac_vreg"]["fac_nr"] 
    require_once "inc/fpdf.php";
	require_once "inc/fpdi.php";
    
    $pdf = new FPDI();
    
    $factuur_nr = $_SESSION["fac_vreg"]["fac_nr"];
    $max_aant_regels = 22;
    //echo "<br>" . $factuur_nr;
    
    $id_arr = array();
    $tel_arr = array();
    
    $tot_aant_regels = 0;
    foreach( $_SESSION["fac_vreg"]["id"] as $id )
    {
        $v = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_fac_gsc_vreg WHERE id = " . $id));
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_pvz = '". $v->pvz ."'"));
        $n = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_fac_gsc WHERE cus_id = " . $klant->cus_id)); 
        $id_arr[ $n->netbeheerder ][ $v->pvz ][ $v->periode ] = array( "aantal" => $v->aantal, 
                                                                       "arei" => substr($klant->cus_arei_datum, 0, 10) ) ;
        
        $tel_arr[$n->netbeheerder]++;
        $tot_aant_regels++;
    }
    
    /*
    echo "<pre>";
    var_dump( $id_arr );
    echo "</pre>";
    */
    
    
    
    $aant_p = ceil( $tot_aant_regels / $max_aant_regels );
    $excl = 0;
    
    for( $i=1;$i<=$aant_p;$i++ )
    {
        $pdf->AddPage();
         
        foreach( $id_arr as $netb => $pvz )
        {
            /*
            foreach( $pvz as $p => $aant )
            {
                echo "<br>" . $p . " ";
                
                foreach( $aant as $periode => $a )
                {
                  echo $periode . " " . $a;  
                }
            }
            */
            
            
            
            // opzoeken netbeheerder
            $q_net = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_naam LIKE '". $netb ."%'");
            $aant_netb = mysqli_num_rows($q_net);
            
            if( $aant_netb == 0 )
            {
                die("Leverancier met de naam " . $netb . " is niet teruggevonden in de databank");
            }else
            {
                $klant = mysqli_fetch_object($q_net);
            }  
            
            
            
            if( $tel_arr[$netb] > $max_aant_regels )
            {
                //die("Meerdere pagina's nog voorzien");
            }
            
            $pdf->AddFont('eurosti','','eurosti.php');
    	    $pdf->setSourceFile('pdf/factuur.pdf');
            $tplIdx = $pdf->importPage(1); 
        	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
        	$pdf->SetFont('eurosti', '', 16);
        	$pdf->SetTextColor(0,0,0);
            
            $pdf->Text(30, 39, "Factuur" );
        
        	// tonen van het documents nr
      		$pdf->SetFont('eurosti', '', 10);
            
            $datum = date('d') . "-" . date('m') . "-" . date('Y'); 
        	//datum = "26-02-2013";
        	$pdf->Text(40, 52.5, $datum );
        	
        	$tmp_dat = explode("-", $datum);
        	
        	$jaarmaand = "";
        
            if( $aant_p > 1 )
            {
                $pdf->Text(18, 100, "Pagina " . $i . " van " . $aant_p );
            }
        
        	if( strlen( $tmp_dat[2] ) == 4 )
        	{
        		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
        	}
        	$pdf->Text(40, 59.25, $jaarmaand . "-" . $factuur_nr );
            
            if( $klant->cus_fac_adres == "1" )
        	{
        		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
        		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
        		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
        	}else
        	{
        		
        		if( !empty( $klant->cus_bedrijf ) )
        		{
        			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
        			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
        		}else
        		{
        			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
        		}
        		
        		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
        		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
        		
        		if( !empty( $klant->cus_btw ) )
        		{
        			$pdf->Text(40, 72.75, $klant->cus_btw );
        		}
        	}
            
            $extra_offset = 0;
            $telme = 0;
            
            foreach( $pvz as $p => $aant )
            {
                if( count( $aant ) > 0 )
                {
                    foreach( $aant as $periode => $a )
                    {
                        $pdf->SetXY( 44, 112+$extra_offset );
        			
            			// soort van het artikel ophalen
                        $p1 = explode("-", $periode);
                        
                        $arei_dmy = explode("-", $a["arei"] );
                        $mk_arei = mktime( 0,0,0,$arei_dmy[1], $arei_dmy[0], $arei_dmy[2] );
                        $q_gsc = mysqli_query($conn, "SELECT * FROM kal_gsc_waarde WHERE gt250 = '0'");
                        $prijs = 0;
                        while( $gsc = mysqli_fetch_object($q_gsc) )
                        {
                            $start = explode("-", $gsc->van );
                            $eind = explode("-", $gsc->tot );
                            
                            $mk_start = mktime(0,0,0, $start[1], $start[2], $start[0] );
                            $mk_eind = mktime(0,0,0, $eind[1], $eind[2], $eind[0] );
                            
                            //echo "<br>a" . $mk_start . " " . $mk_eind;
                            
                            if( $mk_arei <= $mk_eind && $mk_arei >= $mk_start )
                            {
                                $prijs = $gsc->waarde;
                                break; 
                            }
                        }
                        
        				$pdf->Cell( 102, 5, $p1[1] ."-". $p1[0] . " - " . $prijs . "euro/MWh", 0, 1,'L');
        				
        				$pdf->SetXY( 17, 112+$extra_offset );
        				$pdf->Cell( 25, 5, $p, 0, 1,'L');
            			
            			$pdf->SetXY( 145, 112+$extra_offset );
            			$pdf->Cell( 20, 5, $a["aantal"], 0, 1, 'R');
            			
            			$pdf->SetXY( 160, 112+$extra_offset );
            			$pdf->Cell( 26, 5, number_format( $prijs * $a["aantal"], 0, "", " " ), 0, 1, 'R');
                        
                        // toevoegen euro teken aan elke regel die wordt afgedrukt
                        $euro_arr[] = 115.75 + $extra_offset;
            
            			$extra_offset += 5.5;
            			
            			$excl += $prijs * $a["aantal"];
                        
                        $telme++;
                        
                        
                        //echo "<br>" . $netb . " " . $p . " " . $p1[0] ."-". $p1[1] . "----" . $telme;
                        unset( $id_arr[$netb][$p][$p1[0] ."-". $p1[1]] );
                        if( $telme == $max_aant_regels )
                        {
                            break;
                        }
                    }
                    
                    if( $telme == $max_aant_regels )
                    {
                        break;
                    }
                }
            }
            
            /*
            echo "<hr>";
            echo "<pre>";
            var_dump( $id_arr );
            echo "</pre>";
            */
    
            
        
            //$factuur_nr++;
        }
        
        if( $i != $aant_p )
        {
            $pdf->AddPage();
            $pdf->setSourceFile('pdf/factuur_vw.pdf');
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
        }
    }
    
    $incl = $excl * 1.21;
	$btw = $incl - $excl;

    $pdf->SetXY( 168, 234 );
    $pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');

    $pdf->Text(159, 246, "21%" );

	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
	$pdf->SetXY( 163, 242.25 );
	$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
    
    $pdf->SetXY( 168, 250 );
    $pdf->Cell(25, 5, "  " . number_format($incl, 2, ",", " " ),0,1,'R');
    
    // toevoegen factuurvoorwaarden.
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    //force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $doc_nummer .'.pdf';
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = $factuur_nr .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output($factuur_nr .'.pdf', $output);	
	}
}

function maak_fac_gsci($output)
{
    // al de gegevens bevinden zich in 
    // $_SESSION["fac_vreg"]["id"]
    // $_SESSION["fac_vreg"]["fac_nr"] 
    require_once "inc/fpdf.php";
	require_once "inc/fpdi.php";
    
    $pdf = new FPDI();
    $factuur_nr = $_SESSION["fac_vreg"]["fac_nr"];
    $max_aant_regels = 22;
    //echo "<br>" . $factuur_nr;
    
    $id_arr = array();
    $tel_arr = array();
    
    $pvz_nr = "";
    foreach( $_SESSION["fac_vreg"]["id"] as $id )
    {
        $v = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_fac_gsc_vreg WHERE id = " . $id));
        
        $ex_pvz = explode(" ", $v->pvz);
        $pvz_nr = $ex_pvz[0];
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_pvz LIKE '". $ex_pvz[0] ."%'"));
        $id_arr[ $klant->cus_netbeheerder ][ $v->pvz ][ $v->periode ] = array( "aantal" => $v->aantal, 
                                                                               "arei" => substr($klant->cus_arei_datum, 0, 10),
                                                                               "id" => $id) ;
        
        $tel_arr[$klant->cus_netbeheerder]++;
    }
    
    $netb = $klant->cus_netbeheerder;
    $aant_p = ceil( $tel_arr[$klant->cus_netbeheerder] / $max_aant_regels );
    
    $excl = 0;
    $prijs = 0;
            
    for( $i=1;$i<=$aant_p;$i++ )
    {
        $pdf->AddPage();
        $pdf->AddFont('eurosti','','eurosti.php');
	    $pdf->setSourceFile('pdf/factuur.pdf');
        $tplIdx = $pdf->importPage(1); 
    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    	$pdf->SetFont('eurosti', '', 16);
    	$pdf->SetTextColor(0,0,0);
        
        $telme = 0;
        
        foreach( $id_arr as $netb => $pvz )
        {
            /*
            foreach( $pvz as $p => $aant )
            {
                echo "<br>" . $p . " ";
                
                foreach( $aant as $periode => $a )
                {
                  echo $periode . " " . $a;  
                }
            }
            */
            
            /*
            if( $tel_arr[$netb] > $max_aant_regels )
            {
                die("Meerdere pagina's nog voorzien");
            }
            */
            
            // opzoeken netbeheerder
            $q_net = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_naam LIKE '". $netb ."%'");
            $aant_netb = mysqli_num_rows($q_net);
            
            if( $aant_netb == 0 )
            {
                die("Leverancier met de naam " . $netb . " is niet teruggevonden in de databank");
            }else
            {
                $klant = mysqli_fetch_object($q_net);
            }  
            
            $pdf->Text(30, 39, "Factuur" );
        
        	// tonen van het documents nr
      		$pdf->SetFont('eurosti', '', 10);
            
            $datum = date('d') . "-" . date('m') . "-" . date('Y'); 
            
        	$pdf->Text(40, 52.5, $datum );
        	
        	$tmp_dat = explode("-", $datum);
        	
        	$jaarmaand = "";
        
        	if( strlen( $tmp_dat[2] ) == 4 )
        	{
        		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
        	}
        	$pdf->Text(40, 59.25, $jaarmaand . "-" . $factuur_nr );
            
            if( $klant->cus_fac_adres == "1" )
        	{
        		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
        		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
        		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
        	}else
        	{
        		
        		if( !empty( $klant->cus_bedrijf ) )
        		{
        			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
        			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
        		}else
        		{
        			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
        		}
        		
        		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
        		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
        		
        		if( !empty( $klant->cus_btw ) )
        		{
        			$pdf->Text(40, 72.75, $klant->cus_btw );
        		}
        	}
            
            $extra_offset = 0;
            
            foreach( $pvz as $p => $aant )
            {
                foreach( $aant as $periode => $a )
                {
                    //echo "<br>" . $telme;
                    
                    if( $telme >= $max_aant_regels )
                    {
                        break;
                    }else
                    {
                        $pdf->SetXY( 44, 112+$extra_offset );
    			
            			// soort van het artikel ophalen
                        $p1 = explode("-", $periode);
                        
                        $arei_dmy = explode("-", $a["arei"] );
                        $mk_arei = mktime( 0,0,0,$arei_dmy[1], $arei_dmy[0], $arei_dmy[2] );
                        $q_gsc = mysqli_query($conn, "SELECT * FROM kal_gsc_waarde WHERE gt250 = '0'");
                        
                        while( $gsc = mysqli_fetch_object($q_gsc) )
                        {
                            $start = explode("-", $gsc->van );
                            $eind = explode("-", $gsc->tot );
                            
                            $mk_start = mktime(0,0,0, $start[1], $start[2], $start[0] );
                            $mk_eind = mktime(0,0,0, $eind[1], $eind[2], $eind[0] );
                            
                            //echo "<br>a" . $mk_start . " " . $mk_eind;
                            
                            if( $mk_arei <= $mk_eind && $mk_arei >= $mk_start )
                            {
                                $prijs = $gsc->waarde;
                                break; 
                            }
                        }
                        
        				$pdf->Cell( 102, 5, $p1[1] ."-". $p1[0] . " - " . $prijs . "euro/MWh", 0, 1,'L');
        				
        				$pdf->SetXY( 17, 112+$extra_offset );
                        
                        $ex_p = explode(" ", $p);
                        
        				$pdf->Cell( 25, 5, $ex_p[0], 0, 1,'L');
            			
                        $q = "SELECT * FROM kal_fac_gsc_vreg WHERE pvz = '". $p ."' AND periode = '". $p1[0] ."-". $p1[1] ."' AND fac != '' AND id != " . $a["id"];
                        $qq_zoek = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . $q . " " . __LINE__ );
                        
                        if( mysqli_num_rows($qq_zoek) > 0 )
                        {
                            $zoek = mysqli_fetch_object($qq_zoek);
                            $aant = $a["aantal"] - $zoek->aantal;
                        }else
                        {
                            $aant = $a["aantal"];
                        }
                        
            			$pdf->SetXY( 145, 112+$extra_offset );
            			$pdf->Cell( 20, 5, $aant, 0, 1, 'R');
            			
            			$pdf->SetXY( 160, 112+$extra_offset );
            			$pdf->Cell( 26, 5, number_format( $prijs * $aant, 0, "", " " ), 0, 1, 'R');
                        
                        // toevoegen euro teken aan elke regel die wordt afgedrukt
                        $euro_arr[] = 115.75 + $extra_offset;
            
            			$extra_offset += 5.5;
            			
            			$excl += $prijs * $aant;
                        
                        $telme++;
                        unset( $id_arr[$netb][ $p ][ $periode ] );
                    }
                }
            }
            
            if( $aant_p > 1 )
            {
                $pdf->Text(20, 100, "Pagina " . $i . " van " . $aant_p );
            }
        }
        
        if( $i == $aant_p )
        {
            $incl = $excl * 1.21;
        	$btw = $incl - $excl;
        
            $pdf->SetXY( 168, 234 );
            $pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');
        
            $pdf->Text(159, 246, "21%" );
        
        	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
        	$pdf->SetXY( 163, 242.25 );
        	$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
            
            $pdf->SetXY( 168, 250 );
            $pdf->Cell(25, 5, "  " . number_format($incl, 2, ",", " " ),0,1,'R');
        }
        
        // toevoegen factuurvoorwaarden.
        $pdf->AddPage();
        $pdf->setSourceFile('pdf/factuur_vw.pdf');
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    }
    
    //force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $factuur_nr .'.pdf';
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $factuur_nr .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = "FU_" . $bestandsnaam . $factuur_nr .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output("FU_" . $bestandsnaam. $factuur_nr .'.pdf', $output);	
	}
}

/*
 * Functie voor het genereren van distri bestelbon, factuur
 */
function distri_bestel_fac($output, $cf_id, $soort_bon, $btw_vrijstelling )
{
	require_once "inc/fpdf.php";
	require_once "inc/fpdi.php";
	
	// ophalen van het huidige bestand
	$cf = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $cf_id));
	
	// ophalen van de klant
	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cf->cf_cus_id));
	
	// ophalen van de regels uit de kal_cus_art tabel
	$regels = array();
	
	$reg = mysqli_query($conn, "SELECT * FROM kal_cus_art WHERE ca_cf_id = " . $cf_id);
	while( $rij = mysqli_fetch_object($reg) )
	{
		$regels[] = $rij;
	}
	
    $max_aant_regels = 22;
    $aant_regels = count( $regels );
    $start = 1;
    $aantal_pag = ceil( $aant_regels / $max_aant_regels );
    
    $pdf = new FPDI();
    $excl = 0;
    while($start <= $aantal_pag )
    {
        //echo "<br>" . $start;
        $euro_arr = array();
	
    	$pdf->AddPage(); 
        
        $pdf->AddFont('eurosti','','eurosti.php');
        
        if( $soort_bon == "Leverbon" )
    	{
    	   $pdf->setSourceFile('pdf/distri_bon_leegfac.pdf');
        }
        
        if( $soort_bon == "Factuur" )
    	{
    	   $pdf->setSourceFile('pdf/factuur.pdf');
        }
        
        if( $soort_bon == "Offerte" )
    	{
    	   $pdf->setSourceFile('pdf/distri_bon_leegfac.pdf');
        }
        
    	//$pdf->setSourceFile('pdf/werkdocument.pdf');
    	
    	// import page 1 
    	$tplIdx = $pdf->importPage(1); 
    	//use the imported page and place it at point 0,0; calculate width and height
    	//automaticallay and ajust the page size to the size of the imported page 
    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    	
    	// Tonen van het soort document
    	$pdf->SetFont('eurosti', '', 16);
    	$pdf->SetTextColor(0,0,0);
    	
    	$doc_nummer = "";
    	
        if( $soort_bon == "Offerte" )
    	{
    		$pdf->Text(30, 39, $soort_b );
    
    		// tonen van het documents nr
    		$pdf->SetFont('eurosti', '', 10);
    		$pdf->Text(16.25, 59.25, "Offerte nr"  );
    		
    		// bepalen van de nummer
            $q_zoek_nr = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_date LIKE '" . date('Y') . "%'");
            $nrke = 0;
    		while( $nr = mysqli_fetch_object($q_zoek_nr) )
            {
                $nrke1 = explode(".", $nr->cf_file);
                $nrke2 = explode("_", $nrke1[0]);
                
                if( $nrke < $nrke2[2] )
                {
                    $nrke = $nrke2[2];
                }
            }
    		
    		//$aantal_distri_offert++;
            
    		$doc_nummer = (int)$nrke+1;
    	}
        
    	if( $soort_bon == "Leverbon" )
    	{
    		$pdf->Text(30, 39, $soort_bon );
    
    		// tonen van het documents nr
    		$pdf->SetFont('eurosti', '', 10);
    		$pdf->Text(16.25, 59.25, "Bestelbon nr :"  );
    		
    		// bepalen van de nummer
    		$aantal_distri_offert = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_bestelbon' AND cf_date LIKE '" . date('Y') . "%'"));
    		
    		$aantal_distri_offert++;
    		$doc_nummer = $aantal_distri_offert+1000;
    	}
    	
    	if( $soort_bon == "Factuur" )
    	{
            $nw_boek_jaar = "01-07";
            $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
            $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
            
            $zoek_fac1 = 0;
            if( $mk_nu >= $mk_nw_boek_jaar )
            {
                //echo "<br> NA 01-07";
                $jaar_1 = date('Y') + 1;
                $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,$jaar_1) );
                
                $q_geenfac = mysqli_query($conn, "SELECT * 
    									FROM kal_customers_files
    									WHERE cf_soort = 'factuur'
    									AND cf_id != 670
    									AND cf_id != 671
                                        AND cf_id != 2337
                                        AND cf_id != 2827
                                        AND cf_date BETWEEN '". date('Y') ."-07-01' AND '". $jaar_2 ."'
    									ORDER BY 1 DESC");
            }else{
                //echo "<br> VOOR 01-07";
                $jaar_1 = date('Y') - 1;
                $jaar_2 = date('Y-m-d', mktime(0,0,0,7,1-1,date('Y')) );
                
                $q_geenfac = mysqli_query($conn, "SELECT * 
    									FROM kal_customers_files
    									WHERE cf_soort = 'factuur'
    									AND cf_id != 670
    									AND cf_id != 671
                                        AND cf_id != 2337
                                        AND cf_id != 2827
                                        AND cf_date BETWEEN '". $jaar_1 ."-07-01' AND '". $jaar_2 ."'
    									ORDER BY 1 DESC");
            }

    		// zoeken naar factuurnummer
    		while( $rij = mysqli_fetch_object($q_geenfac) )
    		{
    			$factuur = explode(".", $rij->cf_file);
    			
    			if( is_numeric( $factuur[0] ) && $zoek_fac1 < $factuur[0] )
    			{
    				$zoek_fac1 = $factuur[0];
    			}
    		}
    		// einde zoeken naar factuurnummer
    		
    		//$pdf->Text(30, 39, $soort );
            $pdf->Text(30, 39, $soort_bon );
    
    		// tonen van het documents nr
    		$pdf->SetFont('eurosti', '', 10);
    		//$pdf->Text(16.25, 59.25, "Factuur nr :"  );
    		
    		// bepalen van de nummer
    		$aantal_distri_offert = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_factuur' AND cf_date LIKE '" . date('Y') . "%'"));
    		
    		$doc_nummer = $zoek_fac1+1;
    	}
    	
    	// now write some text above the imported page 
    	$pdf->SetFont('eurosti', '', 10); 
    	$pdf->SetTextColor(0,0,0);
    	
    	
    	if( $klant->cus_fac_adres == "1" )
    	{
    		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
    		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
    	}else
    	{
    		
    		if( !empty( $klant->cus_bedrijf ) )
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
    			$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
    		}else
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
    		}
    		
    		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
    		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
    		
    		if( !empty( $klant->cus_btw ) )
    		{
    			$pdf->Text(40, 72.75, $klant->cus_btw );
    		}
    	}
        
        if( $aantal_pag > 1 )
        {
            $pdf->Text(18, 102, "Pagina : ". $start . " van " . $aantal_pag );
        }
        
    	$pdf->SetFont('eurosti', '', 9);
    	$pdf->SetTextColor(0,0,0);
    	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
    	
    	//$datum = changeDate2EU( $cf->cf_date );
        
        $datum = date('d') . "-" . date('m') . "-" . date('Y'); 
    	
    	$pdf->Text(40, 52.5, $datum );
    	
    	$tmp_dat = explode("-", $datum);
    	
    	$jaarmaand = "";
    
    	if( strlen( $tmp_dat[2] ) == 4 )
    	{
    		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
    	}
    	
    	$pdf->Text(40, 59.25, $jaarmaand . "-" . $doc_nummer );
    	
    	/*		
    	$pdf->SetFont('Arial', 'B', 10);
    	$pdf->SetXY( 20, 90 );
    	$pdf->Cell( 90, 5, "PV",0,1,'L');
    	*/
    	
    	$pdf->SetFont('eurosti', '', 10);
    	
    	if( count( $regels ) > 0  )
    	{
    		$extra_offset = 0;
            $telme = 0;
    		foreach( $regels as $key => $dis )
    		{
    			$pdf->SetXY( 44, 112+$extra_offset );
    			
    			if( !empty( $dis->ca_art_id ) )
    			{
    				// soort van het artikel ophalen
    				$artikelnaam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art WHERE ka_id = " . $dis->ca_art_id));
    				$pdf->Cell( 102, 5, $artikelnaam->ka_art, 0, 1,'L');
    				
    				$soort = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art_soort WHERE as_id = " . $artikelnaam->ka_as_id));
    				$pdf->SetXY( 20, 112+$extra_offset );
    				$pdf->Cell( 25, 5, $soort->as_doc, 0, 1,'L');
    					
    			}else
    			{
    				$pdf->Cell( 90, 5, $dis->ca_art, 0, 1,'L');
    			}
    			
    			$pdf->SetXY( 145, 112+$extra_offset );
    			$pdf->Cell( 20, 5, $dis->ca_aantal, 0, 1, 'R');
    			
    			$pdf->SetXY( 167, 112+$extra_offset );
    			$pdf->Cell( 26, 5, number_format( $dis->ca_prijs * $dis->ca_aantal, 2, ",", " " ), 0, 1, 'R');
                
                // toevoegen euro teken aan elke regel die wordt afgedrukt
                $euro_arr[] = 115.75 + $extra_offset;
    
    			$extra_offset += 5.5;
    			
    			$excl += $dis->ca_prijs * $dis->ca_aantal;
                
                $telme++;
                unset( $regels[$key] );
                if( $telme == $max_aant_regels )
                {
                    break;
                }
    		}
    	}
        
        // toevoegen van de eurotekens
        $pdf->setFont('Arial', '', 10);
        
        if( $start == $max_aant_regels )
        {
            $euro_arr[] = 237.5;
            $euro_arr[] = 253.5;
        }
        
        foreach( $euro_arr as $euro )
        {
            $pdf->text( 195, $euro, "EUR" );
        }
        
        $start++;
	}
    
    $pdf->SetFont('eurosti', '', 10);
    
	// btw
	$btw = "";
	$incl = 0;
	
	if( $cf->cf_btw != "0" )
	{
		if( $cf->cf_btw == "6" )
		{
			// 6 %
			$incl = $excl * 1.06;
			$btw = $incl - $excl;
		}
		
		if( $cf->cf_btw == "21" )
		{
			// 21 %
			$incl = $excl * 1.21;
			$btw = $incl - $excl;
		}
	}else
	{
	   /*
		if( $klant->cus_medecontractor == '1' )
		{
			$btw = 0;
		}else
		{
			$btw = $klant->cus_verkoopsbedrag_incl - $excl;
			$incl = $excl * 1.21;
			$btw = $incl - $excl;
		}
        */
	}
	
	$pdf->SetXY( 168, 234 );
	$pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
	if( $cf->cf_btw != "0" )
	{
		if( $cf->cf_btw == "6" )
		{
			// 6 %
			$pdf->Text(161, 249.5, "6%" );
			//$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
            $euro_arr[] = 249.5;
			$pdf->SetXY( 163, 246 );
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
		}
		
		if( $cf->cf_btw == "21" )
		{
			// 21 %
			$pdf->Text(159, 246, "21%" );
            $euro_arr[] = 245.5;
            
			//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
			$pdf->SetXY( 163, 242.25 );
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
		}
	}else
	{
	   /*
		if( $klant->cus_medecontractor == '1' )
		{
			$klant->cus_verkoopsbedrag_incl = $klant->cus_verkoopsbedrag_excl;
			$pdf->Text(45, 232, "BTW verlegd KB 1, art 20" );
            $incl = $excl;
		}else
		{
			$pdf->Text(159, 246, "21%" );
			$pdf->SetXY( 163, 242.25 );
            $euro_arr[] = 245.5;
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
		}
        */
        $klant->cus_verkoopsbedrag_incl = $klant->cus_verkoopsbedrag_excl;
        $incl = $excl;
        $btw = 0;
        
        
        
        $nr = substr( $btw0, 4, 1 );
        $pdf->Text(45, 232, html_entity_decode( $btw_vrijstelling[ $cf->cf_btw_vrij ] ) );
	}
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($incl, 2, ",", " " ),0,1,'R');
	
	if( $verkl == 1 )
	{
		$verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
		$pdf->SetFont('eurosti', '', 9);
		$pdf->SetXY( 20, 221 );
		$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
	}
	
    
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["fac_nr"] = $doc_nummer .'.pdf';
		$ret["factuur"] = $pdf->Output('distri__'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
        
        if( $soort_bon == "Offerte" )
    	{
            $ret["filename"] = 'distri_bestelbon_'. $doc_nummer .'.pdf';
           
        }else
        {
            $ret["filename"] = 'distri_offerte_'. $doc_nummer .'.pdf';
        }
		return $ret;
	}else
	{
		
        if( $soort_bon == "Offerte" )
    	{
            $pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);
        }else
        {
            $pdf->Output('distri_bestelbon_'. $doc_nummer .'.pdf', $output);
        }	
	}
}

/*
 * Functie voor het genereren van distri offerte 
 */
function distri($output, $datum, $btw_per, $klant_keuze, $sel_bestaande_klant, $soort_b, $btw0, $btw_vrijstelling)
{
	require_once "inc/fpdf.php";
	require_once "inc/fpdi.php";
	
    if( $klant_keuze == "bestaande" )
	{
		$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $sel_bestaande_klant));
		$cus_id = $klant->cus_id;	
	}
	
    $max_aant_regels = 22;
    $aant_regels = count( $_SESSION["distri"] );
    $start = 1;
    $aantal_pag = ceil( $aant_regels / $max_aant_regels );
    
    $pdf = new FPDI();
    $excl = 0;
    $exclude_arr = array();
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
    while($start <= $aantal_pag )
    {
        $euro_arr = array();
        
    	$pdf->AddPage(); 
    	$pdf->setSourceFile('pdf/distri_bon_leegfac.pdf');
    	//$pdf->setSourceFile('pdf/werkdocument.pdf');
    	
    	// import page 1 
    	$tplIdx = $pdf->importPage(1); 
    	//use the imported page and place it at point 0,0; calculate width and height
    	//automaticallay and ajust the page size to the size of the imported page 
    	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    	
    	// Tonen van het soort document
    	$pdf->SetFont('eurosti', '', 16);
    	$pdf->SetTextColor(0,0,0);
    	
    	$doc_nummer = "";
    	
    	if( $soort_b == "Offerte" )
    	{
    		$pdf->Text(30, 39, $soort_b );
    
    		// tonen van het documents nr
    		$pdf->SetFont('eurosti', '', 10);
    		$pdf->Text(16.25, 59.25, "Offerte nr"  );
    		
    		// bepalen van de nummer
            
            $q_zoek_nr = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_date LIKE '" . date('Y') . "%'");
            $nrke = 0;
    		while( $nr = mysqli_fetch_object($q_zoek_nr) )
            {
                $nrke1 = explode(".", $nr->cf_file);
                $nrke2 = explode("_", $nrke1[0]);
                
                if( $nrke < $nrke2[2] )
                {
                    $nrke = $nrke2[2];
                }
            }
    		
    		//$aantal_distri_offert++;
            
    		$doc_nummer = (int)$nrke+1;
    	}
    	
    	// now write some text above the imported page 
    	$pdf->SetFont('eurosti', '', 10); 
    	$pdf->SetTextColor(0,0,0);
    	
    	if( $klant_keuze == "bestaande" )
    	{
    		if( $klant->cus_fac_adres == "1" )
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
    			$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
    			$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
    		}else
    		{
    			
    			if( !empty( $klant->cus_bedrijf ) )
    			{
    				$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
    				$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
    			}else
    			{
    			    //echo $klant->cus_naam;
    				$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
    			}
    			
    			$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
    			$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
    			
    			if( !empty( $klant->cus_btw ) )
    			{
    				$pdf->Text(40, 72.75, $klant->cus_btw );
    			}
    		}
    	}else
    	{
    		// nieuwe klant, gegevens uit de session var halen
    		if( !empty( $_SESSION["distri_klant"]["bedrijf"] ) )
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim( $_SESSION["distri_klant"]["bedrijf"] ),  ENT_QUOTES) );
    			$pdf->Text(40, 66, html_entity_decode(trim( $_SESSION["distri_klant"]["naam"] ), ENT_QUOTES) );
    		}else
    		{
    			$pdf->Text(110, 52.5, html_entity_decode(trim( $_SESSION["distri_klant"]["naam"] ),  ENT_QUOTES) );	
    		}
    		
    		$pdf->Text(110, 57.5, html_entity_decode(trim( $_SESSION["distri_klant"]["straat"] ),  ENT_QUOTES) . " " . $_SESSION["distri_klant"]["nr"] );
    		$pdf->Text(110, 62.5, $_SESSION["distri_klant"]["postcode"] . " " . html_entity_decode(trim( $_SESSION["distri_klant"]["gemeente"] ),  ENT_QUOTES));
    		
    		if( !empty( $_SESSION["distri_klant"]["btwnr"] ) )
    		{
    			$pdf->Text(40, 72.75, $_SESSION["distri_klant"]["btwnr"] );
    		}
    	}
    	
        if( $aantal_pag > 1 )
        {
            $pdf->Text(18, 102, "Pagina : ". $start . " van " . $aantal_pag );
        }
        
    	$pdf->SetFont('eurosti', '', 9);
    	$pdf->SetTextColor(0,0,0);
    	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
    	
    	$pdf->Text(40, 52.5, $datum );
    	
    	$tmp_dat = explode("-", $datum);
    	
    	$jaarmaand = "";
    
    	if( strlen( $tmp_dat[2] ) == 4 )
    	{
    		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
    	}
    	
    	$pdf->Text(40, 59.25, $jaarmaand . "-" . $doc_nummer );
    	
    	/*		
    	$pdf->SetFont('Arial', 'B', 10);
    	$pdf->SetXY( 20, 90 );
    	$pdf->Cell( 90, 5, "PV",0,1,'L');
    	*/
    	
    	$pdf->SetFont('eurosti', '', 10);
    	
    	if( count( $_SESSION["distri"] ) > 0  )
    	{
    		$telme = 0;
    		$extra_offset = 0;
    		foreach( $_SESSION["distri"] as $key => $dis )
    		{
                if( !in_array($key, $exclude_arr) )
                {
        			$pdf->SetXY( 44, 112+$extra_offset );
        			
        			if( isset( $dis["art_id"] ) )
        			{
        				// soort van het artikel ophalen
        				$artikelnaam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art WHERE ka_id = " . $dis["art_id"]));
        				$pdf->Cell( 102, 5, $artikelnaam->ka_art, 0, 1,'L');
        				
        				$soort = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_art_soort WHERE as_id = " . $artikelnaam->ka_as_id));
        				$pdf->SetXY( 18, 112+$extra_offset );
        				$pdf->Cell( 25, 5, $soort->as_doc, 0, 1,'L');
        					
        			}else
        			{
        				$pdf->Cell( 90, 5, $dis["art"], 0, 1,'L');
        			}
        			
        			$pdf->SetXY( 145, 112+$extra_offset );
        			$pdf->Cell( 20, 5, $dis["aantal"], 0, 1, 'R');
        			
        			
        			$dis["prijs"] = str_replace(",", ".", $dis["prijs"]);
        			
        			$pdf->SetXY( 167, 112+$extra_offset );
        			$pdf->Cell( 26, 5, number_format( $dis["prijs"]*$dis["aantal"], 2, ",", " " ), 0, 1, 'R');
                    
                    $euro_arr[] = 115.75 + $extra_offset; // artikel euro sign
        
        			$extra_offset += 5.5;
        			
        			$excl += $dis["prijs"]*$dis["aantal"];
                    
                    $telme++;
                    
                    $exclude_arr[] = $key;
                    
                    if( $telme == $max_aant_regels )
                    {
                        break;
                    }
                }
    		}
    	}
        
        /* Switchen naar ander lettertype voor het weergeven van de euro teken */
        $pdf->setFont('Arial', '', 10);
        
        if($start == $aantal_pag )
        {
            $euro_arr[] = 237.5; // subtot
            $euro_arr[] = 253.5; // eind tot 
        }
        
        foreach( $euro_arr as $euro )
        {
            $pdf->text( 195, $euro, "EUR" );
        }
        
        $start++;
     }
	
    $pdf->SetFont('eurosti', '', 10);
    
	// btw
	$btw = "";
	$incl = 0;
	if( $btw_per != "0" )
	{
		if( $btw_per == "6" )
		{
			// 6 %
			$incl = $excl * 1.06;
			$btw = $incl - $excl;
		}
		
		if( $btw_per == "21" )
		{
			// 21 %
			$incl = $excl * 1.21;
			$btw = $incl - $excl;
		}
	}else
	{
		if( $klant->cus_medecontractor == '1' )
		{
			$btw = 0;
		}else
		{
			$btw = $klant->cus_verkoopsbedrag_incl - $excl;
		}
	}
	
	$pdf->SetXY( 167, 234 );
	$pdf->Cell(26, 5, number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
	if( $btw_per != "0" )
	{
		if( $btw_per == "6" )
		{
			// 6 %
			$pdf->Text(161, 249.5, "6%" );
            $euro_arr[] = 249.5; // btw
			//$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
			$pdf->SetXY( 163, 246 );
			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
		}
		
		if( $btw_per == "21" )
		{
			// 21 %
            $euro_arr[] = 245.5; // btw
            
			$pdf->Text(159, 246, "21%" );
			//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
			$pdf->SetXY( 163, 242.25 );
			$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ), 0, 1, 'R');
		}
	}else
	{
		$klant->cus_verkoopsbedrag_incl = $klant->cus_verkoopsbedrag_excl;
		//$pdf->Text(45, 232, "BTW verlegd KB 1, art 20" );
        $incl = $excl;
	   
       /*
	    $incl = $excl * 1.21;
		$btw = $incl - $excl;
        
        $euro_arr[] = 245.5; // btw
		$pdf->Text(159, 246, "21%" );
        
		$pdf->SetXY( 163, 242.25 );
		$pdf->Cell(30, 5, number_format( $btw, 2, ",", " " ),0,1,'R');
        */
        $nr = substr( $btw0, 4, 1 );
        $pdf->Text(45, 232, html_entity_decode( $btw_vrijstelling[ $nr ] ) );
	}
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, number_format($incl, 2, ",", " " ), 0, 1, 'R');
	
	if( $verkl == 1 )
	{
		$verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
		$pdf->SetFont('Arial', '', 9);
		$pdf->SetXY( 20, 221 );
		$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
	}
	
    
    
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);
		$ret["incl"] = $incl;
		$ret["filename"] = 'distri_offerte_'. $doc_nummer .'.pdf';
		return $ret;
	}else
	{
		$pdf->Output('distri_offerte_'. $doc_nummer .'.pdf', $output);	
	}
}


/*
 * Functie voor het genereren van injectie facturen
 */
function factuur_injectie( $output, $inj_id, $fac_nr, $datum, $tarief_p, $tarief_op )
{
	require "kalender/inc/fpdf.php";
	require "kalender/inc/fpdi.php";
	
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
	
	// gegevens van stroomleverancier ophalen
	$q_inj = mysqli_query($conn, "SELECT * FROM tbl_injectie, tbl_stroomlev WHERE in_leverancier = id AND in_id = " . $inj_id) or die( mysqli_error($conn) );
	$inj = mysqli_fetch_object($q_inj);

	// klant = stroomleverancier
	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $inj->cus_id));
	$endex = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_endexes_xy WHERE en_project_id = " . $inj->in_project_id . " AND en_maand = ". $inj->in_maand ." AND en_jaar = " . $inj->in_jaar));
    $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $inj->in_project_id)); 

	$pdf = new FPDI();
	$pdf->AddPage(); 
	
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    
	$pdf->setSourceFile('kalender/pdf/factuur.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	if( !empty( $klant->cus_btw ) )
	{
		$pdf->Text( 40, 72.75, $klant->cus_btw );
	}
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
	}
	
    // vermelden van de naam
    $pdf->Text(16, 79, "Installatie : ");
    $pdf->Text(40, 79, $project->name );
    
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $fac_nr );
	
	if( !empty( $klant->cus_naam ) )
	{
		$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );
	}else
	{
		$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_bedrijf), ENT_QUOTES) );	
	}
	
	//$pdf->SetFont('Arial', 'B', 10);
	$pdf->SetXY( 21, 112 );
	$pdf->Cell( 20, 5, "Injectie",0,1,'L');
	
	//$pdf->SetFont('Arial', '', 10);
	
	// P
	$pdf->SetXY( 45, 112 );
	$pdf->Cell( 60, 5, "Piekuren " . $maand[ $inj->in_maand ] . " " . $inj->in_jaar,0,1,'L');
	
	$pdf->SetXY( 45, 117.5 );
	
	if( $endex->en_piek_x == 0.00000 )
	{
		$pdf->Cell( 60, 5, "- prijs Kwh " . str_replace(".", ",", $tarief_p),0,1,'L');
	}else
	{
	   $pdf->setFont('Arial', '', 10);
	   $pdf->text( 87, 121, iconv("UTF-8", "cp1252", "€") );
       $pdf->SetFont('eurosti', '', 10); 
		$pdf->Cell( 60, 5, "- " . str_replace(".", ",", $endex->en_piek_x) ." * maandbedrag,    " . str_replace(".", ",", $tarief_p) ,0,1,'L');	
	}
	
	$kwh_p = $inj->in_kwh_p / 1000;
	$pdf->SetXY( 135, 117.5 );
	$pdf->Cell( 30, 5, number_format($kwh_p, 3, ",", " "), 0, 1, 'R');
	
	$totaal_p = $kwh_p * $tarief_p;
	$pdf->SetXY( 162, 117.5 );
	$pdf->Cell( 30, 5, "  " . number_format($totaal_p, 2, ",", " "), 0, 1, 'R');
	
	// OP
	$pdf->SetXY( 45, 128 );
	$pdf->Cell( 60, 5, "Daluren " . $maand[ $inj->in_maand ] . " " . $inj->in_jaar,0,1,'L');
	
	$pdf->SetXY( 45, 133.5 );
	
	if( $endex->en_dal_x == 0.00000 )
	{
		$pdf->Cell( 60, 5, "- prijs Kwh " . str_replace(".", ",", $tarief_op),0,1,'L');
	}else
	{
        $pdf->setFont('Arial', '', 10);
        $pdf->text( 87, 137, iconv("UTF-8", "cp1252", "€") );
        $pdf->SetFont('eurosti', '', 10); 
		$pdf->Cell( 60, 5, "- " . str_replace(".", ",", $endex->en_dal_x) ." * maandbedrag,    " . str_replace(",", ".", $tarief_op),0,1,'L');	
	}
	
	$kwh_op = $inj->in_kwh_op / 1000;
	$pdf->SetXY( 135, 133.5 );
	$pdf->Cell( 30, 5, number_format($kwh_op, 3, ",", " "),0,1,'R');
	
	$totaal_op = $kwh_op * $tarief_op;
	$pdf->SetXY( 162, 133.5 );
	$pdf->Cell( 30, 5, "  " . number_format($totaal_op, 2, ",", " "),0,1,'R');
	
	$totaal = $totaal_op + $totaal_p;
	
	$pdf->SetXY( 168, 234 );
	$pdf->Cell(25, 5, "  " . number_format( $totaal, 2, ",", "." ),0,1,'R');
	
	$totaal_incl = $totaal * 1.21;
	$btw = $totaal_incl - $totaal;
	
	// 21 %
	$pdf->Text(159, 246, "21%" );
	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
	$pdf->SetXY( 168, 242.25 );
	$pdf->Cell(25, 5, "  " . number_format( $btw, 2, ",", "." ),0,1,'R');
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($totaal_incl, 2, ",", "." ),0,1,'R');
	
    $pdf->setFont('Arial', '', 10);
    $pdf->text( 168, 121, iconv("UTF-8", "cp1252", "€") );
    $pdf->text( 168, 137, iconv("UTF-8", "cp1252", "€") );
    
    $pdf->text( 168, 237.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text( 168, 245.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text( 168, 253.5, iconv("UTF-8", "cp1252", "€") );
    
    // toevoegen factuurvoorwaarden.
    $pdf->AddPage();
    $pdf->setSourceFile('kalender/pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('factuur_'. $fac_nr .'.pdf', $output);
		$ret["incl"] = number_format($totaal_incl, 2, ".", "");
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $fac_nr .'.pdf', $output);	
	}
}

/*
 * Functie voor het genereren van compensatie facturen
 * 
 */
function factuur_compensatie( $output, $project_id, $ean_id, $fac_nr, $datum, $maand1, $jaar, $prijs )
{
	require "kalender/inc/fpdf.php";
	require "kalender/inc/fpdi.php";
	
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
	
    $extra_regel = "";
    
    // 63 YML radiatorenstraat
    if( $project_id == 63 )
    {
        $extra_regel = " BESTELBON NR : YML.2013.00009 ";
    }
    
    // 70 YMD jean monetlaan
    if( $project_id == 70 )
    {
        $extra_regel = " BESTELBON NR : YM.2013.00008 ";
    }
    
	// ophalen van de ean gegegvens
	$gsm = 0;
	$q_gsm = mysqli_query($conn, "SELECT * FROM tbl_ean_kwh WHERE ek_project_id = " . $project_id . " AND ek_maand = '". $maand1 ."' AND ek_jaar = '". $jaar ."'");
	while( $q_g = mysqli_fetch_object($q_gsm) )
	{
		$gsm += $q_g->ek_waarde;
	}
	
	$inj = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_injectie WHERE in_project_id = " . $project_id . " AND in_maand = '". $maand1 . "' AND in_jaar = '". $jaar ."'"));
	$project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $project_id));
    
    if( $project->cus_id != 0 )
    {
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $project->cus_id));
        
        $project->fac_name = $klant->cus_fac_naam ;
        $project->fac_adres = $klant->cus_fac_adres;
        $project->fac_straat = $klant->cus_fac_straat;
        $project->fac_nr = $klant->cus_fac_nr;
        $project->fac_postcode = $klant->cus_fac_postcode;
        $project->fac_gemeente = $klant->cus_fac_gemeente;
        $project->straat = $klant->cus_straat;
        $project->nr = $klant->cus_nr;
        $project->postcode = $klant->cus_postcode;
        $project->gemeente = $klant->cus_gemeente;
    }
	
	$pdf = new FPDI();
	$pdf->AddPage(); 
	
    $pdf->AddFont('eurosti','','eurosti.php');
	$pdf->setSourceFile('kalender/pdf/factuur.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// now write some text above the imported page 
	//$pdf->SetFont('Arial', '', 10);
    $pdf->SetFont('eurosti', '', 14);
    $soort_b = "Factuur";
	$pdf->Text(30, 39, $soort_b );
    $pdf->SetFont('eurosti', '', 10);
     
	$pdf->SetTextColor(0,0,0);
	
    if( $project->cus_id != 0 )
    {
        $pdf->Text(40, 72.75, $klant->cus_btw );
    }else
    {
    	if( !empty( $project->btw ) )
    	{
    		$pdf->Text(40, 72.75, $project->btw );
    	}
    }

	if( !empty( $project->fac_name ) )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($project->fac_name),  ENT_QUOTES) );
		$pdf->Text(16.25, 78.5, "Interne naam : ");
        $pdf->Text(40, 78.5, html_entity_decode(trim($project->name),  ENT_QUOTES) );
	}else
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($project->name),  ENT_QUOTES) );	
	}
	
    if( $project->fac_adres == "1" )
    {
        $pdf->Text(110, 57.5, html_entity_decode(trim($project->fac_straat),  ENT_QUOTES) . " " . $project->fac_nr);
    	$pdf->Text(110, 62.5, $project->fac_postcode . " " . html_entity_decode(trim($project->fac_gemeente),  ENT_QUOTES));
    }else
    {
    	$pdf->Text(110, 57.5, html_entity_decode(trim($project->straat),  ENT_QUOTES) . " " . $project->nr);
    	$pdf->Text(110, 62.5, $project->postcode . " " . html_entity_decode(trim($project->gemeente),  ENT_QUOTES));
	}
    
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $fac_nr );
	
	if( !empty( $project->contact ) )
	{
		$pdf->Text(40, 66, html_entity_decode(trim($project->contact), ENT_QUOTES) );
	}else
	{
		$pdf->Text(40, 66, html_entity_decode(trim($klant->name), ENT_QUOTES) );	
	}
	
	//$pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 21, 112 );
	$pdf->Cell( 20, 5, "Verbruik",0,1,'L');
	
	//$pdf->SetFont('Arial', '', 10);
    $pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 44, 112 );
	$pdf->Cell( 60, 5, "Periode : " . $maand1 . "-" . $jaar,0,1,'L');
	
	$pdf->SetXY( 44, 123 );
	$pdf->Cell( 60, 5, "Groene stroom meter : " . number_format($gsm, 2, ",", " ") . "kWh", 0, 1, 'L');
	
	$pdf->SetXY( 44, 128 );
	$pdf->Cell( 60, 5, "Injectie : " . number_format( $inj->in_kwh_p + $inj->in_kwh_op, 2, ",", " ") . "kWh", 0, 1, 'L');
	
	$pdf->SetXY( 44, 134 );
	$pdf->Cell( 60, 5, "Verbruik = groene stroom meter - injectie", 0, 1, 'L');
	
	$verbruik = $gsm  - ( $inj->in_kwh_p + $inj->in_kwh_op );
	
	$pdf->SetXY( 44, 145 );
	//$pdf->Cell( 60, 5, "Prijs Verbruik : " . iconv("UTF-8", "cp1252", "€") . " " . number_format( $prijs, 4, ",", " ") ." x ", 0, 1, 'L');
	$pdf->Cell( 60, 5, "Prijs Verbruik :   " . number_format( $prijs, 4, ",", " ") ." x ", 0, 1, 'L');
    
	$pdf->SetXY( 146, 145 );
	$pdf->Cell( 20, 5, number_format($verbruik, 2, ",", " ") , 0, 1, 'R');
	
	$tot = $prijs * $verbruik;
	
	$pdf->SetXY( 168, 145 );
	$pdf->Cell( 25, 5, "  " . number_format($tot, 2, ",", " ") , 0, 1, 'R');
	
	$pdf->SetXY( 168, 234 );
	$pdf->Cell( 25, 5, "  " . number_format( $tot, 2, ",", "." ), 0, 1, 'R');
	
	$totaal_incl = $tot * 1.21;
	$btw = $totaal_incl - $tot;
	
	// 21 %
	$pdf->Text(159, 246, "21%" );
	//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
	$pdf->SetXY( 168, 242.25 );
	$pdf->Cell(25, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($totaal_incl, 2, ",", " " ),0,1,'R');
	
    
    if( !empty( $extra_regel ) )
    {
        $pdf->SetFont('eurosti', '', 11);
        $pdf->Text( 15.25, 83.75, $extra_regel );
        $pdf->SetFont('eurosti', '', 10);
    }
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text( 66.5, 148.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->Text( 168, 148.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->Text( 168, 237.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->Text( 168, 245.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->Text( 168, 253.5, iconv("UTF-8", "cp1252", "€") );
    
	// toevoegen pagina 2
    $pdf->AddPage(); 
	$pdf->setSourceFile('kalender/pdf/factuur_vw.pdf');
	$tplIdx = $pdf->importPage(1); 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('factuur_'. $fac_nr .'.pdf', $output);
		$ret["incl"] = number_format($totaal_incl, 2, ".", "");
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $fac_nr .'.pdf', $output);	
	}
}

/**
 * 
 * Functie om credit nota te generenen en te tonen of op te slaan
 * @param int $fac_nr
 * @param int $klant_id
 */
function creditnota($output) 
{
    $conn = $GLOBALS["conn"];
    ob_end_clean();
    
    echo "<pre>";
    var_dump( $_SESSION );
    echo "</pre>";
    
    $s_factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=" . $_SESSION['kalender_cn']['factuur']));
    $get_transaction = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE factuur_id=" . $s_factuur->cf_id . " LIMIT 1"));
    $get_product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=" . $get_transaction->prod_id));
    $brand = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=" . $get_product->product_brand_id));
    $model = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=" . $get_product->product_model_id));

    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $_SESSION["kalender_cn"]["klant_id"]));
    $cus_id = $klant->cus_id;
    $sub = 0;

    if ($klant->uit_cus_id > 0 && is_numeric($klant->uit_cus_id)) {
        $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant->uit_cus_id);

        if (mysqli_num_rows($q_hoofdklant) > 0) {
            $hoofdklant = mysqli_fetch_object($q_hoofdklant);
            $sub = 1;
            $cus_id = $hoofdklant->cus_id;
        }
    }

    $pdf = new Fpdi();
    
    $pdf->setPrintFooter(false);
    $pdf->setPrintHeader(false);
    $pdf->SetFontSpacing(0.25);
    $pdf->SetAutoPageBreak(false,0);
    
    $pdf->AddFont('eurosti', '', '../fh/inc/font/Eurostilenormal.php');
    $pdf->AddFont('eurosti', 'B', '../fh/inc/font/eurostilebold.php');
    $pdf->AddFont('Arial', '', '../fh/inc/font/Eurostilenormal.php');
    $pdf->AddFont('Arial', 'B', '../fh/inc/font/eurostilebold.php');
    
    
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur.pdf');
    $tplIdx = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tplIdx);
    $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'],true);

    $pdf->SetFont('eurosti', '', 16);
    $pdf->Text(30, 39, "Creditnota");
    // now write some text above the imported page
       $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Image('images/logo_car.png', 10, 10, -300);
    if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
            $pdf->Text(40, 66.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
	}
	$pdf->Text(40, 72.75, $klant->cus_btw);


    $jaarmaand = "";
	$tmp_dat = explode("-", $_SESSION["kalender_cn"]["datum"]);
	
	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
        $pdf->Text(16.25, 52.30, "Datum:" );
        $pdf->Text(16.25, 59.20, "Creditnota nr.:" );
        $pdf->Text(16.25, 66.25, "Contactpers.:" );
        $pdf->Text(16.25, 72.55, "BTW nr:" );
	$pdf->Text(40, 52.5, $_SESSION["kalender_cn"]["datum"] );
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $_SESSION["kalender_cn"]["cn_nr"] );
	
	$pdf->SetFont('eurosti', '', 16);
    $pdf->Text(30, 39, "Creditnota");
    
    $pdf->SetFont('eurosti', '', 10);
	$pdf->Text(20, 115, $_SESSION["kalender_cn"]["art"] );
	
	//$pdf->SetFont('Arial', '', 10);
	$pdf->SetXY(44,111.5);
	$pdf->MultiCell(102, 5.5, $_SESSION["kalender_cn"]["omschr"], 0, "L" );
	
    $pdf->SetXY(145,112);
	$pdf->MultiCell(20, 5, $_SESSION["kalender_cn"]["aant"], 0, "R" );
    
    $euro_pos_btw = 0;
    if( $_SESSION["kalender_cn"]["btw"] != 0 )
    {
    	$excl = $_SESSION["kalender_cn"]["prijs"] /(($_SESSION["kalender_cn"]["btw"]/100)+1);
    	$btw = $_SESSION["kalender_cn"]["prijs"] - $excl;
    	$btw_perc = $_SESSION["kalender_cn"]["btw"] . "%";
        
        switch( $_SESSION["kalender_cn"]["btw"] )
        {
            case 6 :
                $pdf->Text(161, 249.5, $btw_perc );
                $pdf->SetXY(163, 246);
                $pdf->Cell( 30, 5, number_format($btw, 2, ",", " " ), 0, 1, 'R' );
                $euro_pos_btw = 249.5;
                break;
            case 21 :
                $pdf->Text(159, 246, $btw_perc );
                $pdf->SetXY(163, 242.25);
                $pdf->Cell( 30, 5, number_format($btw, 2, ",", " " ), 0, 1, 'R' );
                $euro_pos_btw = 245.5;
                break;
        }
    }else
    {
        $excl = $_SESSION["kalender_cn"]["prijs"];
    }
	
    $pdf->SetXY(168,112);
	$pdf->MultiCell(25, 5, number_format($excl, 2, ",", " " ), 0, "R" );
    
	//$pdf->Text(150, 214, number_format($excl, 2, ",", "" ) );
    
    $pdf->SetXY( 168, 234 );
    $pdf->Cell( 25, 5, number_format($excl, 2, ",", " " ), 0, 1, "R" );
    
    //$pdf->Text(150, 223, number_format($_SESSION["kalender_cn"]["prijs"], 2, ",", "" ) );
    
    $pdf->SetXY(168, 250);
    $pdf->Cell( 25, 5, number_format($_SESSION["kalender_cn"]["prijs"], 2, ",", " " ), 0, 1, 'R' );
    
    $pdf->setFont('Arial', '', 10);
    $euro_sign = "EUR";
    $pdf->text( 195, 115.5, $euro_sign );
    $pdf->text( 195, 237.5, $euro_sign );
    $pdf->text( 195, 253.5, $euro_sign );
    
    if( $euro_pos_btw > 0 )
    {
        $pdf->text( 195, $euro_pos_btw, $euro_sign );    
    }


    $pdf->text(48, 225, "BTW terug te storten aan de staat in de mate waarin ze ");
    $pdf->text(48, 230, "oorspronkelijk in aftrek werd gebracht (art. 4 KB nr. 4)");

    /* FOOTER */
    $pdf->SetFont('Arial', '', 8);
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_instellingen"));
    $pdf->Text(40, 284, $instellingen->bedrijf_straat . " " . $instellingen->bedrijf_straatnr);
    $pdf->Text(40, 288, $instellingen->bedrijf_postcode . " " . $instellingen->bedrijf_gemeente);
    $pdf->Text(40, 292, "Tel " . $instellingen->bedrijf_tel);

    $pdf->Text(80, 284, $instellingen->bedrijf_email);
    //$pdf->Text(80, 288, 'www.carengineering.be');
    $pdf->Text(80, 292, $instellingen->bedrijf_btw);

    $bank = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_bank LIMIT 1"));
    $pdf->Text(120, 284, "FORTIS: " . $bank->bank_naam);
    $pdf->Text(120, 288, "IBAN: " . $bank->iban);
    $pdf->Text(120, 292, "BIC: " . $bank->bic);


    //force the browser to download the output
    if ($output == "S") {
        return $pdf->Output('factuur_' . $_SESSION["kalender_cn"]["cn_nr"] . '.pdf', $output);
    } else {
        $pdf->Output('factuur_' . $_SESSION["kalender_cn"]["cn_nr"] . '.pdf', $output);
    }
}


/**
 * 
 * Functie om factuur te generenen en te tonen of op te slaan
 * @param int $fac_nr
 * @param int $klant_id
 */
function factuur($fac_nr, $klant_id, $datum, $verkl, $output)
{
    require "inc/fpdf.php";
    require "inc/fpdi.php";

    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant_id));
    $cus_id = $klant->cus_id;
    $sub = 0;

    if ($klant->uit_cus_id > 0 && is_numeric($klant->uit_cus_id)) {
        $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant->uit_cus_id);

        if (mysqli_num_rows($q_hoofdklant) > 0) {
            $hoofdklant = mysqli_fetch_object($q_hoofdklant);
            $sub = 1;
            $cus_id = $hoofdklant->cus_id;
        }
    }

    $pdf = new FPDI();
    $pdf->AddPage();

    $pdf->AddFont('eurosti', '', 'eurosti.php');
    $pdf->AddFont('eurosti', 'B', 'eurostileltstdboldex2.php');

    $pdf->setSourceFile('pdf/factuur.pdf');
    //$pdf->setSourceFile('pdf/werkdocument.pdf');
    // import page 1 
    $tplIdx = $pdf->importPage(1);
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);

    // now write some text above the imported page 
    $pdf->SetFont('eurosti', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    if ($sub == 0) {
        if (!empty($klant->cus_btw)) {
            $pdf->Text(40, 72.75, $klant->cus_btw);
        }

        if ($klant->cus_fac_adres == "1") {
            $pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam), ENT_QUOTES));
            $pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat), ENT_QUOTES) . " " . $klant->cus_fac_nr);
            $pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente), ENT_QUOTES));
        } else {
            if (!empty($klant->cus_bedrijf)) {
                $pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf), ENT_QUOTES));
            } else {
                $pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES));
            }

            $pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat), ENT_QUOTES) . " " . $klant->cus_nr);
            $pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente), ENT_QUOTES));
        }
    } else {
        if (!empty($hoofdklant->cus_btw)) {
            $pdf->Text(40, 72.75, $hoofdklant->cus_btw);
        }

        if ($hoofdklant->cus_fac_adres == "1") {
            $pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_fac_naam), ENT_QUOTES));
            $pdf->Text(110, 57.5, html_entity_decode(trim($hoofdklant->cus_fac_straat), ENT_QUOTES) . " " . $hoofdklant->cus_fac_nr);
            $pdf->Text(110, 62.5, $hoofdklant->cus_fac_postcode . " " . html_entity_decode(trim($hoofdklant->cus_fac_gemeente), ENT_QUOTES));
        } else {
            if (!empty($hoofdklant->cus_bedrijf)) {
                $pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_bedrijf), ENT_QUOTES));
            } else {
                $pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_naam), ENT_QUOTES));
            }

            $pdf->Text(110, 57.5, html_entity_decode(trim($hoofdklant->cus_straat), ENT_QUOTES) . " " . $hoofdklant->cus_nr);
            $pdf->Text(110, 62.5, $hoofdklant->cus_postcode . " " . html_entity_decode(trim($hoofdklant->cus_gemeente), ENT_QUOTES));
        }
    }

    //$pdf->SetFont('eurosti', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    //$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );

    $pdf->Text(40, 52.5, $datum);

    $tmp_dat = explode("-", $datum);

    $jaarmaand = "";

    if (strlen($tmp_dat[2]) == 4) {
        $jaarmaand = substr($tmp_dat[2], 2, 2) . $tmp_dat[1];
    }

    $pdf->Text(40, 59.25, $jaarmaand . "-" . $fac_nr);

    if ($sub == 0) {
        $pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES));
    } else {
        $pdf->Text(40, 66, html_entity_decode(trim($hoofdklant->cus_naam), ENT_QUOTES));
    }

    $pdf->SetFont('eurosti', '', 10);
    $pdf->SetXY(19, 112);
    $pdf->Cell(24, 5, "PV installatie", 0, 1, 'L');

    if ($sub == 0) {
        $pdf->SetXY(44, 112);
        $pdf->Cell(90, 5, "Plaatsing van een PV installatie met panelen van " . $klant->cus_werk_w_panelen . "Wp", 0, 1, 'L');
        $pdf->SetXY(44, 117.5);
        $pdf->Cell(90, 5, "Hoogwaardige mono panelen met Q.C. Futech label. ", 0, 1, 'L');
    } else {
        $pdf->SetXY(44, 112);
        $pdf->Cell(90, 5, "Uitbreiding van een PV installatie met panelen van " . $klant->cus_werk_w_panelen . "Wp", 0, 1, 'L');
        $pdf->SetXY(44, 117.5);
        $pdf->Cell(90, 5, "Hoogwaardige mono panelen met Q.C. Futech label. ", 0, 1, 'L');
    }

    $pdf->SetXY(145, 112);
    $pdf->Cell(20, 5, number_format($klant->cus_werk_aant_panelen, 2, ",", " "), 0, 1, 'R');

    $pdf->SetXY(44, 123);
    $pdf->Cell(90, 5, "Totaal DC vermogen", 0, 1, 'L');

    if ($klant->cus_verkoop == '3') {
        $pdf->SetXY(44, 133);
        $pdf->Cell(90, 5, "De certificaten blijven gedurende 20 jaar eigendom van Futech", 0, 1, 'L');

        $pdf->SetXY(44, 138);
        $pdf->Cell(90, 5, "en de klant is eigenaar van de opgewekte elektriciteit conform", 0, 1, 'L');

        $pdf->SetXY(44, 143);
        $pdf->Cell(90, 5, "orderbon.", 0, 1, 'L');
    }

    $pdf->SetFont('eurosti', 'B', 8);
    $pdf->SetXY(145, 123);
    $pdf->Cell(20, 5, $klant->cus_werk_w_panelen * $klant->cus_werk_aant_panelen, 0, 1, 'R');

    $pdf->SetFont('eurosti', '', 10);
    // btw
    $btw = "";

    $excl = 0;
    if (empty($klant->cus_btw)) {
        if ($klant->cus_woning5j == 1) {
            // 6 %
            $excl = $klant->cus_verkoopsbedrag_incl / 1.06;
            $btw = $klant->cus_verkoopsbedrag_incl - $excl;
        }

        if ($klant->cus_woning5j == 0) {
            // 21 %
            $excl = $klant->cus_verkoopsbedrag_incl / 1.21;
            $btw = $klant->cus_verkoopsbedrag_incl - $excl;
        }
    } else {
        if ($klant->cus_medecontractor == '1') {
            $excl = $klant->cus_verkoopsbedrag_incl;
            $btw = 0;
        } else {
            $excl = $klant->cus_verkoopsbedrag_incl / 1.21;
            $btw = $klant->cus_verkoopsbedrag_incl - $excl;
        }
    }

    $pdf->SetFont('eurosti', '', 10);
    $pdf->SetXY(163, 112);
    $pdf->Cell(30, 5, "  " . number_format($excl, 2, ",", " "), 0, 1, 'R');

    //$pdf->Text(185, 222, number_format($klant->cus_verkoopsbedrag_excl, 2, ",", "." )  );

    $pdf->SetXY(163, 234);
    $pdf->Cell(30, 5, "  " . number_format($excl, 2, ",", " "), 0, 1, 'R');

    $euro_sign_pos = 0;

    if (empty($klant->cus_btw)) {
        if ($klant->cus_woning5j == 1) {
            // btw opsplitsen indien prive != 100

            if ($klant->cus_btw_prive == 100) {

                // 6 %
                $pdf->Text(161, 249.5, "6%");
                //$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
                $pdf->SetXY(163, 246);

                $euro_sign_pos = 249.5;

                $pdf->Cell(30, 5, "  " . number_format($btw, 2, ",", "."), 0, 1, 'R');
            } else {
                // gedeelte aan 6%
                $btw6 = ( number_format($excl, 2, ".", "") * ($klant->cus_btw_prive / 100) ) * 0.06;

                // gedeelte aan 21%
                $btw21 = ( number_format($excl, 2, ".", "") * ($klant->cus_btw_bedrijf / 100) ) * 0.21;

                $euro_sign_pos = 246;
                $euro_sign_pos = 249.5;

                $pdf->Text(161, 249.5, "6%");
                $pdf->SetXY(163, 246);
                $pdf->Cell(30, 5, "  " . number_format($btw6, 2, ",", "."), 0, 1, 'R');

                $pdf->Text(159, 246, "21%");
                //$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
                $pdf->SetXY(163, 242.25);
                $pdf->Cell(30, 5, "  " . number_format($btw21, 2, ",", " "), 0, 1, 'R');

                $klant->cus_verkoopsbedrag_incl = number_format($excl, 2, ".", "") + $btw6 + $btw21;
            }
        }

        if ($klant->cus_woning5j == 0) {
            // 21 %
            $euro_sign_pos = 246;

            $pdf->Text(159, 246, "21%");
            //$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
            $pdf->SetXY(163, 242.25);
            $pdf->Cell(30, 5, "  " . number_format($btw, 2, ",", " "), 0, 1, 'R');
        }
    } else {
        if ($klant->cus_medecontractor == '1') {
            /*
              $klant->cus_verkoopsbedrag_incl = $klant->cus_verkoopsbedrag_excl;
              $excl = $klant->cus_verkoopsbedrag_incl;
             */
            $pdf->Text(45, 232, "BTW verlegd KB 1, art 20");
        } else {
            $euro_sign_pos = 246;
            $pdf->Text(159, 246, "21%");
            $pdf->SetXY(163, 242.25);
            $pdf->Cell(30, 5, "  " . number_format($btw, 2, ",", " "), 0, 1, 'R');
        }
    }

    $pdf->SetXY(168, 250);
    $pdf->Cell(25, 5, "  " . number_format($klant->cus_verkoopsbedrag_incl, 2, ",", " "), 0, 1, 'R');

    if ($verkl == 1) {
        $verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
        $pdf->SetFont('eurosti', '', 10);
        $pdf->SetXY(16, 252);
        $pdf->MultiCell(110, 5, $verklaring, 0, 1);
    }

    /* weergeven van het euroteken in een ander lettertype */
    $pdf->setFont('Arial', '', 10);
    $pdf->text(168, 115.5, iconv("UTF-8", "cp1252", "€"));
    $pdf->text(168, 237.5, iconv("UTF-8", "cp1252", "€"));
    $pdf->text(168, 253.5, iconv("UTF-8", "cp1252", "€"));
    $pdf->text(168, $euro_sign_pos, iconv("UTF-8", "cp1252", "€"));

    // toevoegen pagina 2
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);

    //force the browser to download the output
    if ($output == "S") {
        $ret["factuur"] = $pdf->Output('factuur_' . $fac_nr . '.pdf', $output);
        $ret["incl"] = $klant->cus_verkoopsbedrag_incl;
        $ret["excl"] = number_format($excl, 2, ".", "");

        return $ret;
    } else {
        $pdf->Output('factuur_' . $fac_nr . '.pdf', $output);
    }
}

function factuur_trans($fac_nr, $trans_id, $datum,  $output, $conn_car){
    require "inc/fpdf.php";
    require "inc/fpdi.php";
    ob_end_clean();
    $get_transaction = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=".$trans_id." LIMIT 1"));
    $get_customer = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id=".$get_transaction->soort_id));
    
    $get_product = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * FROM tbl_products WHERE id=".$get_transaction->prod_id));
    $brand = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * FROM tbl_product_brand WHERE id=".$get_product->product_brand_id));
    $model = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * FROM tbl_product_model WHERE id=".$get_product->product_model_id));
    $instellingen = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * from kal_instellingen"));
    
    $a = utf8_decode( html_entity_decode( $get_customer->cus_naam, ENT_QUOTES ));
    
    $btw = '';
    switch($get_transaction->btw){
        case 0:
            $btw = 0;
            break;
        case 1:
            $btw = 6;
            break;
        case 2:
            $btw = 21;
            break;
    }
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile('transactie_doc/factuur.pdf');
    // import page 1 
    $tplIdx = $pdf->importPage(1);
    //use the imported page and place it at point 0,0; calculate width and height
    //automaticallay and ajust the page size to the size of the imported page 
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);

    //$pdf->Rect(10,10,120,10,"F");

    //$pdf->Image('images/logo_car.png',10,10,-300);

    $pdf->SetFont('Arial','',26);
    $pdf->Text(20,20,'ESC');

    /* HEADER */
    $pdf->SetFont('Arial','',16);
    $pdf->Text(30,40,'Factuur');
    $pdf->SetFont('Arial','',10);
    $pdf->Text(40,52.5,  $datum);
    $jaar = explode('-',$datum);
    $pdf->Text(40,59.5,substr($jaar[2],2).$jaar[1]."-".$fac_nr);
    $pdf->Text(40,66,$a);
    $pdf->Text(40,72.5,$get_customer->cus_btw);

    /* KLANT */ 
    $pdf->Text( 120,52.5, $a );
    $pdf->Text(120,56.5,$get_customer->cus_straat . " " . $get_customer->cus_nr);
    $pdf->Text(120,60.5,$get_customer->cus_postcode . " " . $get_customer->cus_gemeente);

    /* TABEL */
    $pdf->Text(20,118,'WAGEN');
    $regel = 0;
    $pdf->Text(46,118+$regel,'Merk: ' . html_entity_decode( $brand->naam, ENT_QUOTES  ));
    $regel += 5;
    $pdf->Text(46,118+$regel,'Model: ' . html_entity_decode( $model->naam, ENT_QUOTES  ));

 
    $get_product_values = mysqli_query($conn_car, "SELECT * FROM tbl_product_values as a,tbl_product_fields as b WHERE a.product_id=".$get_product->id ." AND (a.product_fields_id=b.id AND b.factuur=1)");
//    echo "SELECT * FROM tbl_product_values as a,tbl_product_fields as b WHERE a.product_id=".$get_product->id ." AND b.factuur=1 <br />";
    if(mysqli_num_rows($get_product_values) != 0){
            while($product = mysqli_fetch_object($get_product_values)){
            $q_choice = mysqli_query($conn_car, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id=".$product->product_fields_id." AND id=".$product->value);
            if(mysqli_num_rows($q_choice) != ''){
                $choice = mysqli_fetch_object($q_choice);
                $field_name = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * FROM tbl_product_fields WHERE id=".$product->product_fields_id));
                $regel += 5;
                $pdf->Text(46,118+$regel,$field_name->field.': ' . $choice->choice);
            }else{
                $field_name = mysqli_fetch_object(mysqli_query($conn_car, "SELECT * FROM tbl_product_fields WHERE id=".$product->product_fields_id));
                $regel += 5;
                $pdf->Text(46,118+$regel,$field_name->field.': ' . $product->value);
            }
        }
    }
    
    $pdf->Text(170,118, iconv("UTF-8", "cp1250", "€") .number_format($get_transaction->prijs_excl, 2, ",", " "));

    $pdf->Text(170,237.5, iconv("UTF-8", "cp1250", "€") .number_format($get_transaction->prijs_excl, 2, ",", " "));
    $pdf->Text(155,245.5,$btw."%");
    $pdf->Text(170,245.5, iconv("UTF-8", "cp1250", "€") .number_format(($get_transaction->prijs_incl - $get_transaction->prijs_excl), 2, ",", " "));
    $pdf->Text(170,253.3, iconv("UTF-8", "cp1250", "€") .number_format($get_transaction->prijs_incl, 2, ",", " "));
    $pdf->SetFont('Arial','',6);
    
    /* tekst onder tabel */
    $pdf->SetXY(20,233.5);
    $pdf->MultiCell(100, 3, $instellingen->fac_voorwaarden, 0, "L" );
    if($btw == 0){
        $pdf->SetXY(20,239.5);
	$pdf->MultiCell(100, 3, $instellingen->fac_percent, 0, "L" );
    }
    
    /* FOOTER */
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_instellingen"));
    $pdf->SetFont('Arial','',8);
    $pdf->Text(40,284,$instellingen->bedrijf_straat . " " . $instellingen->bedrijf_straatnr);
    $pdf->Text(40,288,$instellingen->bedrijf_postcode . " " . $instellingen->bedrijf_gemeente);
    $pdf->Text(40,292,"Tel " . $instellingen->bedrijf_tel);

    $pdf->Text(80,284,$instellingen->bedrijf_email);
    $pdf->Text(80,292,$instellingen->bedrijf_btw);

    $bank = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_bank LIMIT 1"));
    $pdf->Text(120,284,$bank->bank_naam);
    $pdf->Text(120,288,"IBAN: ".$bank->iban);
    $pdf->Text(120,292,"BIC: ".$bank->bic);

    if ($output == "S") {
            $ret["factuur"] = $pdf->Output('factuur_' . $fac_nr . '.pdf', $output);
            $ret["incl"] = $get_transaction->prijs_incl;
            $ret["excl"] = number_format($get_transaction->prijs_excl, 2, ".", "");
            $ret['btw'] = $btw;

            return $ret;
        } else {
            $pdf->Output('factuur_' . $fac_nr . '.pdf', $output);
        }
}
function factuur_ond($fac_nr, $klant_id, $datum, $output)
{
	require "inc/fpdf.php";
	require "inc/fpdi.php";
	
	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant_id));
    $onderhoud = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_project WHERE cus_id = " . $klant_id));
    
	$cus_id = $klant->cus_id;
	
	$pdf = new FPDI();
	$pdf->AddPage(); 
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    $pdf->AddFont('eurosti', 'B', 'eurostileltstdboldex2.php');
    
	$pdf->setSourceFile('pdf/factuur.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
    $pdf->SetFont('eurosti', '', 16);
	$pdf->SetTextColor(0,0,0);
    
    $pdf->Text(30, 39, "Factuur" );
    
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
	
	if( !empty( $klant->cus_btw ) )
	{
		$pdf->Text(40, 72.75, $klant->cus_btw );
	}
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
	}else
	{
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
		}
		
		$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
	}
	
	//$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $fac_nr );
	
	if( $sub == 0 )
	{
		$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );	
	}else
	{
		$pdf->Text(40, 66, html_entity_decode(trim($hoofdklant->cus_naam), ENT_QUOTES) );
	}
	
	$pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 19, 112 );
	$pdf->Cell( 24, 5, "PV installatie", 0, 1,'L');
    
    $ond_arr = array();
    $ond_arr[0] = "Neen";
    $ond_arr[1] = "Brons";
    $ond_arr[2] = "Zilver";
    $ond_arr[3] = "Goud";
    $ond_arr[4] = "Platinum";
    
	$pdf->SetXY( 44, 112 );
	$pdf->Cell( 90, 5, "Onderhoud PV installatie zoals overeenkomst - " . $ond_arr[ $onderhoud->ond_actief ],0,1,'L');
	
    $dat_ymd = explode("-", $onderhoud->onderhoud_datum);
    
    $datplus = date("d/m/Y", mktime(0,0,0,$dat_ymd[1],$dat_ymd[2],$dat_ymd[0] + $onderhoud->onderhoud_gratis));
    
    /*
    $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $klant->cus_id . " AND cf_van = 'onderhoudskost' AND cf_soort = 'factuur' AND cf_date LIKE '". date('Y') ."%' ");
    $q_zoek_cn = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $klant->cus_id . " AND cf_van = 'onderhoudskost' AND cf_soort = 'creditnota' AND cf_date LIKE '". date('Y') ."%' ");
    */
    
    $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $klant->cus_id . " AND cf_van = 'onderhoudskost' AND cf_soort = 'factuur' ");
    $q_zoek_cn = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $klant->cus_id . " AND cf_van = 'onderhoudskost' AND cf_soort = 'creditnota' ");
    
    $aant_fac = mysqli_num_rows($q_zoek_fac);
    $aant_cn =  mysqli_num_rows($q_zoek_cn);
    
    //echo $onderhoud->onderhoud_gratis . " " . $aant_fac . " " . $aant_cn;
    
    $datplus2 = date("d/m/Y", mktime(0,0,0,$dat_ymd[1],$dat_ymd[2]-1,$dat_ymd[0] + $onderhoud->onderhoud_gratis + $aant_fac - $aant_cn));
    $datplus1 = date("d/m/Y", mktime(0,0,0,$dat_ymd[1],$dat_ymd[2]-1,$dat_ymd[0] + 1 + $onderhoud->onderhoud_gratis + $aant_fac - $aant_cn));
    
    $pdf->SetXY( 44, 117.5 );
	$pdf->Cell( 90, 5, "Periode " . $datplus2 . " tem " . $datplus1, 0, 1, 'L');

	$pdf->SetXY( 145, 112 );
	$pdf->Cell( 20, 5, "1", 0, 1, 'R');
	
	$pdf->SetFont('eurosti', '', 10);
	// btw
	$btw = "";
	
	$excl = $onderhoud->ouderhoud;
	
	$pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 163, 112 );
	$pdf->Cell( 30, 5, "  " . number_format( $onderhoud->ouderhoud, 2, ",", " " ), 0, 1, 'R');
	
	//$pdf->Text(185, 222, number_format($klant->cus_verkoopsbedrag_excl, 2, ",", "." )  );
	
	$pdf->SetXY( 163, 234 );
	$pdf->Cell(30, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
    $euro_sign_pos = 0;
    
    if( $klant->cus_medecontractor == 1 )
	{
       	$verklaring = "BTW verlegd KB 1, art 20";
    	$pdf->SetFont('eurosti', '', 10);
    	$pdf->SetXY( 45, 228 );
    	$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
        
        $pdf->SetXY( 168, 250 );
	    $pdf->Cell(25, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1, 'R');
        
        $tot_incl = $excl;
    }else
    {
        $tot_incl = number_format($excl * 1.21, 2, ".", "");
        $btw = $tot_incl - $excl;
        
        $euro_sign_pos = 246;

		$pdf->Text(159, 246, "21%" );
		//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
		$pdf->SetXY( 163, 242.25 );
		$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
        
        $pdf->SetXY( 168, 250 );
	    $pdf->Cell(25, 5, "  " . number_format( $tot_incl, 2, ",", " " ), 0, 1, 'R');
    }
    
    /* weergeven van het euroteken in een ander lettertype */
    $pdf->setFont('Arial', '', 10);
    $pdf->text(168, 115.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, 237.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, 253.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, $euro_sign_pos, iconv("UTF-8", "cp1252", "€") );
    
    // toevoegen pagina 2
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('factuur_'. $fac_nr .'.pdf', $output);
		$ret["incl"] = $tot_incl;
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $fac_nr .'.pdf', $output);	
	}
}

/**
 * 
 * Functie om verhuur factuur te generenen en te tonen of op te slaan
 * @param int $fac_nr
 * @param int $klant_id
 */
function factuur_verhuur($fac_nr, $klant_id, $datum, $verkl, $jaar, $output)
{
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
    
	require "inc/fpdf.php";
	require "inc/fpdi.php";
    
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant_id));
	$cus_id = $klant->cus_id;
	$sub = 0;
	
	if( $klant->uit_cus_id > 0 && is_numeric($klant->uit_cus_id) )
	{
	    $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $klant->uit_cus_id);
        
        if( mysqli_num_rows($q_hoofdklant) )
        {
            $hoofdklant = mysqli_fetch_object($q_hoofdklant);
    		$sub=1;
    		$cus_id = $hoofdklant->cus_id;    
        }
	}
	
	$pdf = new FPDI();
	$pdf->AddPage(); 
    
    $pdf->AddFont('eurosti', '', 'eurosti.php');
    $pdf->AddFont('eurosti', 'B', 'eurostileltstdboldex2.php');
    
	$pdf->setSourceFile('pdf/factuur.pdf');
	//$pdf->setSourceFile('pdf/werkdocument.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);
    
    $soort_b = "Factuur";
	$pdf->Text(30, 39, $soort_b );
	
	if( $sub == 0 )
	{ 
		if( !empty( $klant->cus_btw ) )
		{
			$pdf->Text(40, 72.75, $klant->cus_btw );
		}
		
		if( $klant->cus_fac_adres == "1" )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES) );
			$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_fac_straat),  ENT_QUOTES) . " " . $klant->cus_fac_nr);
			$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . html_entity_decode(trim($klant->cus_fac_gemeente),  ENT_QUOTES));
		}else
		{
			if( !empty( $klant->cus_bedrijf ) )
			{
				$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
			}else
			{
				$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES) );	
			}
			
			$pdf->Text(110, 57.5, html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES) . " " . $klant->cus_nr);
			$pdf->Text(110, 62.5, $klant->cus_postcode . " " . html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES));
		}
	}else
	{
		if( !empty( $hoofdklant->cus_btw ) )
		{
			$pdf->Text(40, 72.75, $hoofdklant->cus_btw );
		}
		
		if( $hoofdklant->cus_fac_adres == "1" )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_fac_naam),  ENT_QUOTES) );
			$pdf->Text(110, 57.5, html_entity_decode(trim($hoofdklant->cus_fac_straat),  ENT_QUOTES) . " " . $hoofdklant->cus_fac_nr);
			$pdf->Text(110, 62.5, $hoofdklant->cus_fac_postcode . " " . html_entity_decode(trim($hoofdklant->cus_fac_gemeente),  ENT_QUOTES));
		}else
		{
			if( !empty( $hoofdklant->cus_bedrijf ) )
			{
				$pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_bedrijf),  ENT_QUOTES) );
			}else
			{
				$pdf->Text(110, 52.5, html_entity_decode(trim($hoofdklant->cus_naam),  ENT_QUOTES) );	
			}
			
			$pdf->Text(110, 57.5, html_entity_decode(trim($hoofdklant->cus_straat),  ENT_QUOTES) . " " . $hoofdklant->cus_nr);
			$pdf->Text(110, 62.5, $hoofdklant->cus_postcode . " " . html_entity_decode(trim($hoofdklant->cus_gemeente),  ENT_QUOTES));
		}
	}
	
    // weergeven van de referte bovenaan op de factuur
    $pdf->Text(16.25, 78.75, "Referte :" );
    $pdf->Text(40, 78.75, maakReferte($klant->cus_id, $conn) );
    
	//$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	//$pdf->Text(170, 57, date('d') . "-" . date('m') . "-" . date('Y') );
	
	$pdf->Text(40, 52.5, $datum );
	
	$tmp_dat = explode("-", $datum);
	
	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $fac_nr );
	
	if( $sub == 0 )
	{
		$pdf->Text(40, 66, html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) );	
	}else
	{
		$pdf->Text(40, 66, html_entity_decode(trim($hoofdklant->cus_naam), ENT_QUOTES) );
	}
	
	$pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 19, 112 );
	$pdf->Cell( 24, 5, "PV installatie", 0, 1,'L');
    
    // zoeken ofdat dit het eerste factuur is.
    $zoek_fac_huur = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_fac_huur WHERE cus_id = " . $klant->cus_id)); 
    
    if( $klant->cus_dom_datum == "0000-00-00")
    {
        $datum_arei = substr( $klant->cus_arei_datum, 0, 10 );
    }else
    {
        $datum_arei = changeDate2EU( $klant->cus_dom_datum );
    }
    
    $tmp_arei_datum = explode("-", $datum_arei);
    
    if( $zoek_fac_huur == 0 )
    {
        $onderhoud = "Van ". $tmp_arei_datum[0] ." ". $maand[ (int)$tmp_arei_datum[1] ] . " ". $jaar . " t.e.m. 31 december " . $jaar;
    }else
    {
        $onderhoud = "Van 1 januari ". $jaar ." t.e.m. 31 december " . $jaar;
    }
    
    $pdf->SetXY( 44, 112 );
	$pdf->Cell( 90, 5, "Onderhoudskosten aan de PV installatie", 0, 1, 'L');
    
    $pdf->SetXY( 44, 117.5 );
	$pdf->Cell( 90, 5, $onderhoud, 0, 1, 'L');
    
    $offset_1regel_zakken = 16.5; 
    
	if( $sub == 0 )
	{
		$pdf->SetXY( 44, 112 + $offset_1regel_zakken );
		$pdf->Cell( 90, 5, "Kenmerken PV installatie, panelen van " . $klant->cus_werk_w_panelen . "Wp",0,1,'L');
		$pdf->SetXY( 44, 117.5 + $offset_1regel_zakken );
		$pdf->Cell( 90, 5, "Hoogwaardige mono panelen met Q.C. Futech label. ",0,1,'L');
	}else
	{
		$pdf->SetXY( 44, 112 + $offset_1regel_zakken );
		$pdf->Cell( 90, 5, "Kenmerken PV installatie, panelen van " . $klant->cus_werk_w_panelen . "Wp",0,1,'L');
		$pdf->SetXY( 44, 117.5 + $offset_1regel_zakken );
		$pdf->Cell( 90, 5, "Hoogwaardige mono panelen met Q.C. Futech label. ",0,1,'L');
	}
	
	$pdf->SetXY( 145, 112 + $offset_1regel_zakken );
	$pdf->Cell( 20, 5, number_format($klant->cus_werk_aant_panelen, 2, ",", " " ), 0, 1, 'R');
	
	$pdf->SetXY( 44, 123 + $offset_1regel_zakken );
	$pdf->Cell( 90, 5, "Totaal DC vermogen",0,1,'L');
	
    // toevoegen regel dom
    $pdf->SetXY( 44, 123.5 + $offset_1regel_zakken + (12*5.5) );
    
    if( $klant->cus_overschrijving == "0" )
    {
	   $pdf->Cell( 90, 5, "Betaling via domiciliering " . number_format($klant->cus_ont_huur, 2, ",", "") . " euro/maand ",0,1,'L');
    }else
    {
        $pdf->Cell( 90, 5, "Betaling via overschrijving " . number_format($klant->cus_ont_huur, 2, ",", "") . " euro/maand ",0,1,'L');
    }
    
    $pdf->SetFont('eurosti', 'B', 8);
	$pdf->SetXY( 145, 123 + $offset_1regel_zakken );
	$pdf->Cell( 20, 5, $klant->cus_werk_w_panelen * $klant->cus_werk_aant_panelen,0,1,'R');
	
    $pdf->SetFont('eurosti', '', 10);
	// btw
	$btw = "";
	
    if( $zoek_fac_huur == 0 )
    {
        $bedragPerMaand = $klant->cus_ont_huur;
        
        // kijken hoeveel maanden er volledig moeten gedaan worden.
        $vol_maand = 12 - $tmp_arei_datum[1];
        $klant->cus_ont_huur = $klant->cus_ont_huur * $vol_maand;
        
        // $tmp_arei_datum[0] // dag
        
        $totalDayInMonth = date("t", mktime( 0, 0, 0, $tmp_arei_datum[1], 1, $tmp_arei_datum[2] ));
        
        $klant->cus_ont_huur = ((( ($totalDayInMonth+1) - $tmp_arei_datum[0] ) / $totalDayInMonth ) * $bedragPerMaand) + $klant->cus_ont_huur; 
    }else
    {
        $klant->cus_ont_huur = $klant->cus_ont_huur * 12;
    }

    /*
    $overzicht = array();
    $overzicht = maak_overzicht( $klant->cus_id, $overzicht );
    $tot = 0;
        
    foreach( $overzicht as $index => $over )
    {
        foreach( $over as $o )
        {
            //echo "<br>" . $o["geld_te_krijgen"];
            if( stristr($o["periode"], "2012") )
            {
                $tot += $o["geld_te_krijgen"];    
            }
        }
    }
    */
    
    $klant->cus_ont_huur = $tot;
    
	$excl = 0;
	if( empty( $klant->cus_btw ) )
	{
		if( $klant->cus_woning5j == 1 )
		{
			// 6 %
			$excl = $klant->cus_ont_huur / 1.06;
			$btw = $klant->cus_ont_huur - $excl;
		}
		
		if( $klant->cus_woning5j == 0 )
		{
			// 21 %
			$excl = $klant->cus_ont_huur / 1.21;
			$btw = $klant->cus_ont_huur - $excl;
		}
	}else
	{
		if( $klant->cus_medecontractor == '1' )
		{
			$excl = $klant->cus_ont_huur;
			$btw = 0;
		}else
		{
			$excl = $klant->cus_ont_huur / 1.21;
			$btw = $klant->cus_ont_huur - $excl;
		}
	}
	
    
    
	$pdf->SetFont('eurosti', '', 10);
	$pdf->SetXY( 163, 112 );
	$pdf->Cell( 30, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1, 'R');
	
	//$pdf->Text(185, 222, number_format($klant->cus_verkoopsbedrag_excl, 2, ",", "." )  );
	
	$pdf->SetXY( 163, 234 );
	$pdf->Cell(30, 5, "  " . number_format( $excl, 2, ",", " " ), 0, 1,'R');
	
    $euro_sign_pos = 0;
    
    $btw_waarde = 0;
    
	if( empty( $klant->cus_btw ) )
	{
		if( $klant->cus_woning5j == 1 )
		{
			// 6 %
			$pdf->Text(161, 249.5, "6%" );
			//$pdf->Text(185, 236, number_format( $btw, 2, ",", "." )  );
			$pdf->SetXY( 163, 246 );
            
            $euro_sign_pos = 249.5;
            
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", "." ),0,1,'R');
            $btw_waarde = 6;
		}
		
		if( $klant->cus_woning5j == 0 )
		{
			// 21 %
            $euro_sign_pos = 246;
            
			$pdf->Text(159, 246, "21%" );
			//$pdf->Text(185, 232, number_format( $btw, 2, ",", "." )  );	
			$pdf->SetXY( 163, 242.25 );
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
            $btw_waarde = 21;
		}
	}else
	{
		if( $klant->cus_medecontractor == '1' )
		{
			$klant->cus_verkoopsbedrag_incl = $klant->cus_verkoopsbedrag_excl;
			$pdf->Text(45, 232, "BTW verlegd KB 1, art 20" );
		}else
		{
            $euro_sign_pos = 246;
			$pdf->Text(159, 246, "21%" );
			$pdf->SetXY( 163, 242.25 );
			$pdf->Cell(30, 5, "  " . number_format( $btw, 2, ",", " " ),0,1,'R');
            $btw_waarde = 21;
		}
	}
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($klant->cus_ont_huur, 2, ",", " " ), 0, 1, 'R');
	
    // nakijken ofdat het factuur voldaan is.
    require_once("inc/Curl_HTTP_Client.php");
    $curl = new Curl_HTTP_Client();
    
    //pretend to be IE6 on windows
    $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    $curl->set_user_agent($useragent);
    //uncomment next two lines if you want to manage cookies
    $cookies_file = "/tmp/cookies". time() .".txt";
    $curl->store_cookies($cookies_file);
    $html_data = $curl->fetch_url( "http://www.solarlogs.be/kalender/coda_dom.php?actie=tegoed", null, 20);
    
    if( !stristr($html_data, "tegoed" . $klant->cus_id) )
    {
        $pdf->SetFont('eurosti', 'B', 12);
        $pdf->Text(149, 263, "VOLDAAN");
    }
    
    $pdf->SetFont('eurosti', '', 10);
    
	if( $verkl == 1 )
	{
		$verklaring = "==>Verklaring met toepassing van artikel 63/11 van het KB/WIB 92 betreffende de uitgevoerde werken die zijn bedoeld in artikel 145/24, 1, van het Wetboek van de inkomstenbelastingen 1992";
		$pdf->SetFont('eurosti', '', 10);
		$pdf->SetXY( 16, 252 );
		$pdf->MultiCell( 110, 5, $verklaring, 0, 1);
	}
	
    /* weergeven van het euroteken in een ander lettertype */
    $pdf->setFont('Arial', '', 10);
    $pdf->text(168, 115.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, 237.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, 253.5, iconv("UTF-8", "cp1252", "€") );
    $pdf->text(168, $euro_sign_pos, iconv("UTF-8", "cp1252", "€") );
    
    // toevoegen pagina 2
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('factuur_'. $fac_nr .'.pdf', $output);
		$ret["incl"] = number_format($klant->cus_ont_huur, 2, ".", "" );
        $ret["btw"] = $btw_waarde;
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $fac_nr .'.pdf', $output);	
	}
}

function oafactuur( $output )
{
	$daksoorten = array();
	$daksoorten[1] = "Plat dak";
	$daksoorten[2] = "pannen dak";
	$daksoorten[3] = "Leien dak";
	$daksoorten[4] = "Schans";
	$daksoorten[5] = "Zinken dak";
	$daksoorten[6] = "Steeldeck";
	$daksoorten[7] = "Golfplaten";
	$daksoorten[8] = "Overzetdak";
    $daksoorten[9] = "Schans op voeten";
    $daksoorten[10] = "Hellend roofing dak";

	require "inc/fpdf.php";
	require "inc/fpdi.php";
	
    $euro_arr = array();
    
	$pdf = new FPDI();
	$pdf->AddPage(); 
    $pdf->AddFont('eurosti', '', 'eurosti.php');
	$pdf->setSourceFile('pdf/factuur.pdf');
	
	// import page 1 
	$tplIdx = $pdf->importPage(1); 
	//use the imported page and place it at point 0,0; calculate width and height
	//automaticallay and ajust the page size to the size of the imported page 
	$pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
	
    // Tonen van het soort document
	$pdf->SetFont('eurosti', '', 16);
	$pdf->SetTextColor(0,0,0);
	
	$doc_nummer = "";
	$soort_b = "Factuur";
	$pdf->Text(30, 39, $soort_b );
    
	// now write some text above the imported page 
	$pdf->SetFont('eurosti', '', 10); 
	$pdf->SetTextColor(0,0,0);

	$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $_SESSION["factuur_oa"]["id_oa"]));
	
	if( $klant->cus_fac_adres == "1" )
	{
		$pdf->Text(110, 52.5, ucfirst( html_entity_decode(trim($klant->cus_fac_naam),  ENT_QUOTES))) ;
		$pdf->Text(110, 57.5, ucwords( trim( $klant->cus_fac_straat )) . " " . $klant->cus_fac_nr);
		$pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . ucwords( $klant->cus_fac_gemeente) );
	}else
	{
		if( !empty( $klant->cus_bedrijf ) )
		{
			$pdf->Text(110, 52.5, html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES) );
		}else
		{
			$pdf->Text(110, 52.5, ucfirst( html_entity_decode(trim($klant->cus_naam) ,  ENT_QUOTES)));	
		}
		
		$pdf->Text(110, 57.5, ucfirst( html_entity_decode(trim($klant->cus_straat) ,  ENT_QUOTES)) . " " . $klant->cus_nr);
		$pdf->Text(110, 62.5, $klant->cus_postcode . " " . ucfirst( html_entity_decode(trim($klant->cus_gemeente) ,  ENT_QUOTES)) );
	}
	
	//$pdf->SetFont('eurosti', '', 9);
	$pdf->SetTextColor(0,0,0);
	
	if( !empty( $klant->cus_btw ) )
	{
		$pdf->Text(40, 72.75, $klant->cus_btw );
	}
	
	$pdf->Text(40, 52.5, $_SESSION["factuur_oa"]["datum_oa"] );	
	
	$tmp_dat = explode("-", $_SESSION["factuur_oa"]["datum_oa"] );

	$jaarmaand = "";

	if( strlen( $tmp_dat[2] ) == 4 )
	{
		$jaarmaand = substr( $tmp_dat[2], 2, 2) . $tmp_dat[1];
	}
	
	$pdf->Text(40, 59.25, $jaarmaand . "-" . $_SESSION["factuur_oa"]["factuur_nr_oa"] );
	
	$pdf->Text(40, 66, ucfirst( html_entity_decode(trim($klant->cus_naam), ENT_QUOTES) ) );
	
	if( $_SESSION["factuur_oa"]["btw"] == 6 )
	{
		$pdf->Text(150, 212, $_SESSION["custom_factuur"]["btw"] . "%" );	
	}
	
	if( $_SESSION["factuur_oa"]["btw"] == 21 )
	{
		$pdf->Text(150, 208, $_SESSION["custom_factuur"]["btw"] . "%" );
	}
	
	
	
	// doorlopen van het aantal artikels
	$tot_excl = 0;
	foreach( $_SESSION["factuur_oa"]["oa_klant"] as $waarde )
	{
		$klant_oa = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $waarde));
		
        $pdf->SetFont('eurosti', '', 10);
    	$pdf->SetXY( 21, 112 + $lijnteller );
        
        if( $klant_oa->cus_int_boiler == '1' )
        {
    	   $pdf->Cell( 22, 5, "Zonneboiler", 0, 1, 'L');
           $boiler_entry = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_boiler WHERE cus_id = " . $waarde));
        }else
        {
            $pdf->Cell( 22, 5, "PV installatie", 0, 1, 'L');
        }
        
		$pdf->SetXY( 44, 112 + $lijnteller );
        
        if( $klant_oa->cus_int_boiler == '1' )
        {
            $regel = "";
            if( $boiler_entry->cus_vs_cap == 0 )
            {
                $regel = $boiler_entry->cus_vs_col ." collectoren";
            }else
            {
                $regel = "Boiler " . $boiler_entry->cus_vs_cap . "L, ". $boiler_entry->cus_vs_col ." collectoren";
            }
            
            $pdf->Cell( 90, 5, trim($klant_oa->cus_naam) . ", " . $regel , 0, 1, 'L');
            
            $pdf->SetXY( 145, 112 + $lijnteller );
		    $pdf->Cell( 20, 5, "1", 0, 1, 'R');
        }else
        {
            $pdf->Cell( 90, 5, trim($klant_oa->cus_naam) . ", " . $daksoorten[ $klant_oa->cus_soort_dak ] , 0, 1, 'L');
            
            $pdf->SetXY( 145, 112 + $lijnteller );
		    $pdf->Cell( 20, 5, $klant_oa->cus_werk_aant_panelen, 0, 1, 'R');
        }
		
		$pdf->SetXY( 168, 112 + $lijnteller );
		$pdf->Cell( 25, 5, number_format($klant_oa->cus_verkoopsbedrag_incl, 2, ",", " " ), 0, 1, 'R');	
		
		$euro_arr[] = 115.75 + $lijnteller;
        
		$lijnteller += 5.5;
		$tot_excl += $klant_oa->cus_verkoopsbedrag_incl;
	}

	$pdf->Text(45, 232, "BTW verlegd KB 1, art 20" );
	$tot_incl = $tot_excl;
	
	$pdf->SetXY( 168, 234 );
	$pdf->Cell(25, 5, "  " . number_format( $tot_excl, 2, ",", " " ),0,1,'R');
	
	$pdf->SetXY( 168, 250 );
	$pdf->Cell(25, 5, "  " . number_format($tot_incl, 2, ",", " " ),0,1,'R');
	
    /* euroteken op papuur zetten */
    $pdf->setFont('Arial', '', 10);
    
    $euro_arr[] = 237.5;
    $euro_arr[] = 253.5;
    
    foreach( $euro_arr as $euro )
    {
        $pdf->text( 195, $euro, "EUR" );
    }
    
    // toevoegen pagina 2 met de factuur voorwaarden
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur_vw.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true); 
    
	//force the browser to download the output
	if( $output == "S" )
	{
		$ret["factuur"] = $pdf->Output('factuur_'. $_SESSION["factuur_oa"]["factuur_nr"] .'.pdf', $output);
		$ret["incl"] = $tot_incl;
		return $ret;
	}else
	{
		$pdf->Output('factuur_'. $_SESSION["factuur_oa"]["factuur_nr"] .'.pdf', $output);	
	}
}

function customfactuur($output, $btw_vrijstelling)
{
    $conn = $GLOBALS["conn"];
    
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_instellingen"));
    $euro_arr = array();

    $pdf = new Fpdi();
    
    $pdf->setPrintFooter(false);
    $pdf->setPrintHeader(false);
    $pdf->SetFontSpacing(0.25);
    $pdf->SetAutoPageBreak(false,0);
    
    $pdf->AddFont('eurosti', '', '../fh/inc/font/Eurostilenormal.php');
    $pdf->AddFont('eurosti', 'B', '../fh/inc/font/eurostilebold.php');
    
    $pdf->AddFont('Arial', '', '../fh/inc/font/Eurostilenormal.php');
    $pdf->AddFont('Arial', 'B', '../fh/inc/font/eurostilebold.php');
    
    $pdf->AddPage();
    $pdf->setSourceFile('pdf/factuur.pdf');
    $tplIdx = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tplIdx);
    $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'],true);
    
    $pdf->Image('images/logo_fac_esc.png',150,20,40);
    
    $pdf->SetFont('Arial','',26);
    $pdf->Text(20,20-3,'European Solar Challenge');
    
    // now write some text above the imported page 
    $pdf->SetFont('Arial','',16);
    //$pdf->Text(30,40,$_SESSION['custom_factuur']['soort_fac_andere']);
    $pdf->Text(30,40-3, "Invoice" );
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);

    // tonen van het documents nr
    //$pdf->SetFont('eurosti', '', 12);

    $pdf->SetFillColor(255,255,255);
    $pdf->Rect(15, 56, 20, 5, "F");
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text(15.5, 56, "Invoice No"  );
    
    $pdf->SetFillColor(255,255,255);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY( 15.5, 49 );
    $pdf->Cell( 20, 5, "Date", 0, 1,'L', true);
    
    $pdf->SetXY( 15.5, 62.5 );
    $pdf->Cell( 24, 5, "Contact", 0, 1,'L', true);
    
    $pdf->SetXY( 15.5, 69.5 );
    $pdf->Cell( 24, 5, "VAT No", 0, 1,'L', true);

    $pdf->SetFont('eurosti', 'B', 10);
        
    $pdf->SetXY( 20, 106 );
    $pdf->Cell( 20, 4, "Article", 0, 1,'L', true);
    
    $pdf->SetXY( 80, 106 );
    $pdf->Cell( 30, 4, "Description", 0, 1,'L', true);
    
    $pdf->SetFillColor(244,247,224);
    $pdf->SetXY( 173, 106 );
    $pdf->Cell( 17, 4, "Total", 0, 1,'L', true);
    
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('eurosti', 'B', 10);
    $pdf->SetXY( 147, 106 );
    //$pdf->Cell( 18, 4, "Quantity", 0, 1,'L', true);
    
    $pdf->SetXY( 142, 234 );
    $pdf->Cell( 24, 4, "SUBTOTAL", 0, 1,'L', true);
    
    $pdf->SetXY( 152, 250.5 );
    $pdf->Cell( 16, 4, "Total", 0, 1,'L', true);
    
    $pdf->SetXY( 136.5, 242.5 );
    $pdf->Cell( 16, 4, "VAT-H", 0, 1,'L', true);
    
    $pdf->SetFillColor(243,242,242);
    $pdf->SetXY( 136.5, 246.5 );
    $pdf->Cell( 16, 4, "VAT-L", 0, 1,'L', true);
    
    $pdf->SetXY( 136.5, 238.5 );
    $pdf->Cell( 30, 4, "SHIPPING", 0, 1,'L', true);
    
    $pdf->SetFont('eurosti', '', 10);

    if (isset($_SESSION["custom_factuur"]["klant_keuze"]) && $_SESSION["custom_factuur"]["klant_keuze"] == "bestaande") 
    {
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_id = " . $_SESSION["custom_factuur"]["sel_bestaande_klant"]));
        $land = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_landen WHERE id = " . $klant->cus_land_id));
        
        if ($klant->cus_fac_adres == "1") {
            $pdf->Text(110, 52.5, ucfirst(html_entity_decode(trim($klant->cus_fac_naam), ENT_QUOTES)));
            $pdf->Text(110, 57.5, ucwords(html_entity_decode(trim($klant->cus_fac_straat), ENT_QUOTES)) . " " . $klant->cus_fac_nr);
            $pdf->Text(110, 62.5, $klant->cus_fac_postcode . " " . ucwords(html_entity_decode(trim($klant->cus_fac_gemeente), ENT_QUOTES)));
        } else {
            if (!empty($klant->cus_bedrijf)) {
                $pdf->Text(110, 52.5, utf8_decode(html_entity_decode(trim($klant->cus_bedrijf), ENT_QUOTES)));
            } else {
                $pdf->Text(110, 52.5, utf8_decode(html_entity_decode(trim($klant->cus_naam), ENT_QUOTES)));
            }

            $pdf->Text(110, 57.5, ucwords(html_entity_decode(trim($klant->cus_straat), ENT_QUOTES)) . " " . $klant->cus_nr);
            $pdf->Text(110, 62.5, $klant->cus_postcode . " " . ucwords(html_entity_decode(trim($klant->cus_gemeente), ENT_QUOTES)));
            $pdf->Text(110, 67.5, $land->land );
            
        }

        //$pdf->SetFont('eurosti', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        if (!empty($klant->cus_btw)) {
            $pdf->Text(40, 72.75-3, $klant->cus_btw);
        }

        $pdf->Text(40, 52.5-3, $_SESSION["custom_factuur"]["datum_cf"]);

        $tmp_dat = explode("-", $_SESSION["custom_factuur"]["datum_cf"]);

        $jaarmaand = "";

        if (strlen($tmp_dat[2]) == 4) {
            $jaarmaand = substr($tmp_dat[2], 2, 2) . $tmp_dat[1];
        }

        $pdf->Text(40, 59.25-3, $jaarmaand . "-" . $_SESSION["custom_factuur"]["factuur_nr"]);

        $pdf->Text(40, 66-3, utf8_decode(html_entity_decode(trim($klant->cus_naam), ENT_QUOTES)));

        if ($_SESSION["custom_factuur"]["btw"] == 6) {
            $pdf->Text(161, 249.5, $_SESSION["custom_factuur"]["btw"] . "%");
        }

        if ($_SESSION["custom_factuur"]["btw"] == 21) {
            $pdf->Text(159, 246, $_SESSION["custom_factuur"]["btw"] . "%");
        }
    } else {
        // nieuwe klant en de ingevulde gegevens overnemen.
        if (!empty($_SESSION["custom_factuur"]["bedrijf"])) {
            $pdf->Text(110, 52.5, html_entity_decode(trim($_SESSION["custom_factuur"]["bedrijf"]), ENT_QUOTES));
        } else {
            $pdf->Text(110, 52.5, ucfirst(html_entity_decode(trim($_SESSION["custom_factuur"]["naam"]), ENT_QUOTES)));
        }

        $pdf->Text(110, 57.5, ucwords(html_entity_decode(trim($_SESSION["custom_factuur"]["straat"]), ENT_QUOTES)) . " " . $_SESSION["custom_factuur"]["nr"]);
        $pdf->Text(110, 62.5, $_SESSION["custom_factuur"]["postcode"] . " " . ucwords(html_entity_decode(trim($_SESSION["custom_factuur"]["gemeente"]), ENT_QUOTES)));

        //$pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        if (!empty($_SESSION["custom_factuur"]["btwnr"])) {
            $pdf->Text(40, 72.75-3, $_SESSION["custom_factuur"]["btwnr"]);
        }

        $pdf->Text(40, 52.5-3, $_SESSION["custom_factuur"]["datum_cf"]);

        $tmp_dat = explode("-", $_SESSION["custom_factuur"]["datum_cf"]);

        $jaarmaand = "";

        if (strlen($tmp_dat[2]) == 4) {
            $jaarmaand = substr($tmp_dat[2], 2, 2) . $tmp_dat[1];
        }

        $pdf->Text(40, 59.25-3, $jaarmaand . "-" . $_SESSION["custom_factuur"]["factuur_nr"]);

        $pdf->Text(40, 66-3, ucfirst(html_entity_decode(trim($_SESSION["custom_factuur"]["naam"]), ENT_QUOTES)));

        if ($_SESSION["custom_factuur"]["btw"] == 6) {
            $pdf->Text(161, 249.5-3, $_SESSION["custom_factuur"]["btw"] . "%");
        }

        if ($_SESSION["custom_factuur"]["btw"] == 21) {
            $pdf->Text(159, 246-3, $_SESSION["custom_factuur"]["btw"] . "%");
        }
    }

    $pdf->SetFont('eurosti', '', 11);

    // doorlopen van het aantal artikels
    $lijnteller = 0;
    $tot_excl = 0;
    for ($i = 1; $i < 15; $i++) {
        $pdf->SetXY(20, 112 + $lijnteller);
        $pdf->Cell(24, 5, $_SESSION["custom_factuur"]["art_" . $i], 0, 1, 'L');


        $pdf->SetXY(44, 112 + $lijnteller);
        $pdf->Cell(102, 5, html_entity_decode(trim($_SESSION["custom_factuur"]["beschrijving_" . $i]), ENT_QUOTES), 0, 1, 'L');
//
//        $pdf->SetXY(145, 112 + $lijnteller);
//        $pdf->Cell(20, 5, $_SESSION["custom_factuur"]["aantal_" . $i], 0, 1, 'R');

        $pdf->SetXY(167, 112 + $lijnteller);
        if (!empty($_SESSION["custom_factuur"]["prijs_" . $i])) {
            $pdf->Cell(25, 5, "  " . number_format(str_replace(",", ".", $_SESSION["custom_factuur"]["prijs_" . $i]), 2, ",", " "), 0, 1, 'R');
            $euro_arr[] = 115.75 + $lijnteller;
        }

        $lijnteller += 5.5;
        $tot_excl += (float)str_replace(",", ".", $_SESSION["custom_factuur"]["prijs_" . $i]);
    }

    if ($_SESSION["custom_factuur"]["btw"] == 6) {
        $btw = $tot_excl * 0.06;
        $pdf->SetXY(163, 246);
        $pdf->Cell(30, 5, "  " . number_format($btw, 2, ",", "."), 0, 1, 'R');
        $tot_incl = $tot_excl + $btw;
        $euro_arr[] = 249.5;
    }

    if ($_SESSION["custom_factuur"]["btw"] == 21) {
        $btw = $tot_excl * 0.21;
        $pdf->SetXY(163, 242.25);
        $pdf->Cell(30, 5, "  " . number_format($btw, 2, ",", "."), 0, 1, 'R');
        $tot_incl = $tot_excl + $btw;
        $euro_arr[] = 245.5;
    }
    
    
    if ($_SESSION["custom_factuur"]["btw"] == 0) {
        $nr = substr($_SESSION["custom_factuur"]["soort0"][0], 4, 1);
        // $pdf->Text(45, 232, "A" . html_entity_decode($btw_vrijstelling[$nr]));
        
        $pdf->SetXY(45, 222);
        $pdf->MultiCell(120, 5, html_entity_decode($btw_vrijstelling[$nr]), 0, "L" );
        $pdf->SetXY( 163, 242.25 );
        $pdf->Cell(30, 5, "  0%",0,1,'R');

        $tot_incl = $tot_excl;
    }
    
    // start ogm
    $len = strlen( $klant->cus_id ) + strlen($_SESSION["custom_factuur"]["factuur_nr"]);
    
    $preogm = "";
    
    for($a=0;$a<(10-$len);$a++)
    {
        $preogm .= "0";
    }
    
    $preogm = $klant->cus_id . $preogm . $_SESSION["custom_factuur"]["factuur_nr"];
    
    
    $ogm_ctrl = $preogm % 97;
    
    if( $ogm_ctrl == 0 )
	{
		$ogm_ctrl = 97;
	}
    
    if( strlen($ogm_ctrl) == 1 )
    {
        $ogm_ctrl = "0" . $ogm_ctrl;
    }else
    {
        $ogm_ctrl = $ogm_ctrl;
    }
    
    $preogm = $preogm . $ogm_ctrl;
    $preogm1 = substr($preogm,0,3) . "/" . substr($preogm,3,4) . "/" . substr($preogm,7);
    
    if( strlen($preogm) == 12 && $soort_bon != "Leverbon" )
    {
        //$pdf->Text( 55, 273, "Gelieve enkel te betalen via de gestructureerde mededeling.");
        $pdf->Text( 140, 260-3, "Structured Communication :");
        $pdf->Text( 140, 265-3, "+++" . $preogm1 . "+++");
    }
    // einde ogm
    
    
    $pdf->SetXY(168, 234);
    $pdf->Cell(25, 5, "  " . number_format($tot_excl, 2, ",", " "), 0, 1, 'R');

    $pdf->SetXY(168, 250);
    $pdf->Cell(25, 5, "  " . number_format($tot_incl, 2, ",", " "), 0, 1, 'R');

    // toevoegen van het euroteken in een lettertype waar het euro-teken bestaat
    $pdf->setFont('Arial', '', 11);

    $euro_arr[] = 237.5; // subtot
    $euro_arr[] = 253.5; // eind tot

    foreach ($euro_arr as $euro) {
        $pdf->text( 195, $euro-3.5,  "EUR" );
    }

    /* FOOTER */
    $pdf->SetFont('Arial','',8);
    $pdf->Text(40,284-3,$instellingen->bedrijf_straat . " " . $instellingen->bedrijf_straatnr);
    $pdf->Text(40,288-3,$instellingen->bedrijf_postcode . " " . $instellingen->bedrijf_gemeente);
    $pdf->Text(40,292-3,"Tel " . $instellingen->bedrijf_tel);

    $pdf->Text(80,284-3,$instellingen->bedrijf_email);
    //$pdf->Text(80,288,'www.carengineering.be');
    $pdf->Text(80,292-3,$instellingen->bedrijf_btw);

    $bank = mysqli_fetch_object(mysqli_query($conn, "SELECT * from kal_bank LIMIT 1"));
    $pdf->Text(130,284-3,"FORTIS: " .$bank->bank_naam);
    $pdf->Text(130,288-3,"IBAN: ".$bank->iban);
    $pdf->Text(130,292-3,"BIC: ".$bank->bic);
    // toevoegen pagina 2 met de factuur voorwaarden
//    $pdf->AddPage();
//    $pdf->setSourceFile('pdf/factuur_vw.pdf');
//    $tplIdx = $pdf->importPage(1);
//    $pdf->useTemplate($tplIdx, 0, 0, 0, 0, true);

    //force the browser to download the output
    if ($output == "S") {
        $ret["factuur"] = $pdf->Output('factuur_' . $_SESSION["custom_factuur"]["factuur_nr"] . '.pdf', $output);
        $ret["incl"] = $tot_incl;

        return $ret;
    } else {
        $pdf->Output('factuur_' . $_SESSION["custom_factuur"]["factuur_nr"] . '.pdf', $output);
    }
}

/**
 * Loggen van de aangepaste velden
 * @param int $cus_id
 * @param int $user_id
 * @param var_char $field
 * @param text $oude_waarde
 * @param text $nieuwe_waarde
 */
function customersLog( $cus_id, $user_id, $field, $oude_waarde, $nieuwe_waarde, $conn )
{
    //if( $oude_waarde != "" && $nieuwe_waarde != ""  )

    if( $oude_waarde == 0.00 && is_numeric( $oude_waarde )  )
    {
        $oude_waarde = "";
    }
    
    if( $nieuwe_waarde == 0.00 && is_numeric( $nieuwe_waarde ) )
    {
        $nieuwe_waarde = "";
    }
    
    if( $oude_waarde == "0000-00-00" || $oude_waarde == "--" )
    {
        $oude_waarde = "";
    }
    
    if( $nieuwe_waarde == "0000-00-00" || $nieuwe_waarde == "--" )
    {
        $nieuwe_waarde = "";
    }
    
    if( !empty( $oude_waarde ) || !empty( $nieuwe_waarde ) )
    { 
    	$q_ins = mysqli_query($conn, "INSERT INTO kal_customers_log(cl_cus_id,
    	                                                    cl_wie, 
    	                                                    cl_veld, 
    	                                                    cl_van, 
    	                                                    cl_naar) 
    											     VALUES('". $cus_id ."',
    											            '". $user_id ."',
    											            '". $field . "',
    											            '". htmlentities($oude_waarde, ENT_QUOTES) ."',
    											            '". htmlentities($nieuwe_waarde, ENT_QUOTES) ."')") or die( mysqli_error($conn) );
        
                                                                       
    }
}

function dateEU($datum)
{
	$tmp = explode("-", $datum);
	$datum_ok = $tmp[2] . "-" . $tmp[1] ."-" . $tmp[0]; 
	return $datum_ok;
}

function changeDate2EU($datum)
{
	if( strlen($datum) == 10 )
	{
		$tmp = explode("-", $datum);
	}else
	{
		$datum = substr($datum, 0, 10);
		$tmp = explode("-", $datum);
	}
    
    if( isset( $tmp[2] ) && isset( $tmp[1] ) && isset( $tmp[0] ) )
    {
        return $tmp[2] . "-" . $tmp[1] . "-" . $tmp[0];    
    }else
    {
        return "";
    }
}

function changeDateTime2EU($datum)
{
	$datum1 = $datum;
	
	if( strlen($datum) == 10 )
	{
		$tmp = explode("-", $datum);
	}else
	{
		$datum = substr($datum, 0, 10);
		$tmp = explode("-", $datum);
	}
	
	return substr($datum1, 11) . " " . $tmp[2] . "-" . $tmp[1] . "-" . $tmp[0];
}

function replaceToNormalChars($var)
{
	$ts = array("/[Ã€-Ã…]/","/Ã†/","/Ã‡/","/[Ãˆ-Ã‹]/","/[ÃŒ-Ã]/","/Ã/","/Ã‘/","/[Ã’-Ã–Ã˜]/","/Ã—/","/[Ã™-Ãœ]/","/[Ã-ÃŸ]/","/[Ã -Ã¥]/","/Ã¦/","/Ã§/","/[Ã¨-Ã«]/","/[Ã¬-Ã¯]/","/Ã°/","/Ã±/","/[Ã²-Ã¶Ã¸]/","/Ã·/","/[Ã¹-Ã¼]/","/[Ã½-Ã¿]/");
	$tn = array("A","AE","C","E","I","D","N","O","X","U","Y","a","ae","c","e","i","d","n","o","x","u","y");
	
	$var = preg_replace($ts,$tn, html_entity_decode($var, ENT_QUOTES));
	return $var;	
}

/*
$daksoorten[1] = "Plat dak/pannen dak";
$daksoorten[2] = "Leien dak";
$daksoorten[3] = "Schans/Zinken dak";
*/
// wijzigingen ook aanbrengen in geschiedenis.php
$daksoorten = array();
$daksoorten[1] = "Plat dak";
$daksoorten[2] = "Pannen dak";
$daksoorten[3] = "Leien dak";
$daksoorten[4] = "Schans";
$daksoorten[5] = "Zinken dak";
$daksoorten[6] = "Steeldeck";
$daksoorten[7] = "Golfplaten";
$daksoorten[8] = "Overzetdak";
$daksoorten[9] = "Schans op voeten";
$daksoorten[10] = "Hellend roofing dak";
$daksoorten[11] = "Gevelmontage";
$daksoorten[12] = "Grond installatie";

asort($daksoorten);

/**
 * Blok tekenen in de kalender
 * @param object $klant_obj
 * @param array $acma_arr
 * @param var_char $soort
 * @param var_char $titel
 * @param var_char $titel1
 */
function makeBlock($klant_obj, $acma_arr, $soort, $titel, $titel1)
{
    // toekennen van kleuren aan de gebruikers
	$kleur[28] = "blue";
	$kleur[29] = "green";
	$kleur[31] = "SeaShell";
	$kleur[32] = "BlueViolet";
	$kleur[25] = "DeepPink";
    $kleur[40] = "darkblue";
    $kleur[41] = "yellow";
	
    if( $soort == "inter_ilumen" )
    {
        echo "<span class='inter_block_cus' title='". $titel1 ."' >";
    }else
    {
        echo "<span class='".$soort."' title='". $titel1 ."' >";    
    }
    
    if( $soort == "inter_ilumen" )
    {
        echo "<a href='http://www.solarlogs.be/ilumen/klanten.php?klant_id=". $klant_obj->ct_cus_id ."&tab_id=1' target='_blank'>";
        
        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_obj->ct_user_id));
        
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant_obj->ct_cus_id));
        
        echo "<b><img src='../ilumen/images/logo.jpg' height='20px' width='50px' >&nbsp;<img src='../ilumen/images/logo.jpg' height='20px' width='50px' >&nbsp;<img src='../ilumen/images/logo.jpg' height='20px' width='50px' ></b>";
    	echo "<br/>";
    	echo $klant->cus_naam . " " . $klant->cus_bedrijf;
    	echo "<br/>";
    	echo $klant->cus_straat . " " . $klant->cus_nr;
    	echo "<br/>";
    	echo $klant->cus_postcode . " " . $klant->cus_gemeente;
    	echo "<br/>";
    	
        echo "<br/>";
        echo "Door : " . $user->naam . " " . $user->voornaam . "<br/>";
        if( !empty( $klant_obj->ct_message ) )
        {
            echo "Opmerkingen : <img src='images/info.png' width='16' height='16' alt='".$klant_obj->ct_message."' title='".$klant_obj->ct_message."' />";
        }
        
        echo "</a>";
    }elseif( $soort == "inter_block_cus" )
    {
        echo "<a href='klanten.php?klant_id=". $klant_obj->ct_cus_id ."&tab_id=1'>";
        
        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_obj->ct_user_id));
        
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant_obj->ct_cus_id));
        
        echo "<b><u>".$titel."</u></b>";
    	echo "<br/>";
    	echo $klant->cus_naam . " " . $klant->cus_bedrijf;
    	echo "<br/>";
    	echo $klant->cus_straat . " " . $klant->cus_nr;
    	echo "<br/>";
    	echo $klant->cus_postcode . " " . $klant->cus_gemeente;
    	echo "<br/>";
    	
        echo "<br/>";
        echo "Door : " . $user->naam . " " . $user->voornaam . "<br/>";
        if( !empty( $klant_obj->ct_message ) )
        {
            echo "Opmerkingen : <img src='images/info.png' width='16' height='16' alt='".$klant_obj->ct_message."' title='".$klant_obj->ct_message."' />";
        }
        
        echo "</a>";
    }elseif( $soort == "inter_block" )
    {
        echo "<a href='../interventions2.php?tab_id=1&int_id=".$klant_obj->int_id."' target='_blank'>";
        
        $int = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_interventions WHERE int_id = " . $klant_obj->int_id));
        $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE project_id = " . $int->int_project_id));
        
        echo "<b><u>".$titel."</u></b>";
        echo "<br/>";
        
        switch( $int->int_soort )
        {
            case "1" :
                echo "Probleem onderzoeken";
                break;
            case "2" :
                echo "Probleem oplossen";
                break;
        }
        
    	echo "<br/>Project : " . $project->name;
        echo "<br/>Door : " . $int->int_by;
        echo "<br/>Opm. : " . substr($int->int_comment, 0, 20) . "...";
        echo "</a>";
    }else
    {
        switch( $_SESSION[ $session_var ]->group_id )
        {
            case 6 :
                echo "<a href='klanten_oa.php?klant_id=". $klant_obj->cus_id ."&tab_id=1'>";
                if( $klant_obj->cus_oa == '1' )
                {
                    $klant_van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant_obj->cus_oa_bij));
                    echo "<span style='font-weight:bold;color:black;'> OA : " . $klant_van->cus_naam . "</span><br>";
                }
                break;
            case 9 :
                echo "<a href='klanten_van.php?klant_id=". $klant_obj->cus_id ."&tab_id=1'>";
                break;
            default :
                echo "<a href='klanten.php?klant_id=". $klant_obj->cus_id ."&tab_id=1'>";
                if( $klant_obj->cus_oa == '1' )
                {
                    $klant_van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant_obj->cus_oa_bij));
                    echo "<span style='font-weight:bold;color:black;'> OA : " . $klant_van->cus_naam . "</span><br>";
                }
                break;
        }
    	
    	echo "<b><u>".$titel."</u></b>";
    	echo "<br/>";
    	echo $klant_obj->cus_naam . " " . $klant_obj->cus_bedrijf;
    	echo "<br/>";
    	echo $klant_obj->cus_straat . " " . $klant_obj->cus_nr;
    	echo "<br/>";
    	echo $klant_obj->cus_postcode . " " . $klant_obj->cus_gemeente;
    	echo "<br/>";
    	
    	
        
        if( $titel == "Boiler offerte bespreking" )
        {
            $boiler_entry = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_boiler WHERE cus_id = " . $klant_obj->cus_id));
            echo "<span style='color : ". $kleur[ $boiler_entry->cus_acma ] ."'>";
            echo "ACMA : " . $acma_arr[ $boiler_entry->cus_acma ];
        }else
        {
            echo "<span style='color : ". $kleur[ $klant_obj->cus_acma ] ."'>";
            echo "ACMA : " . $acma_arr[ $klant_obj->cus_acma ];    
        }
        
    	
    	echo "</span>";
    	
    	echo "<br/>";
    	if( strlen($klant_obj->cus_opmerkingen) > 20 )
    	{
    		echo "Opm. : " . substr($klant_obj->cus_opmerkingen, 0, 20) . "..." ;	
    	}else
    	{
    		echo "Opm. : " . $klant_obj->cus_opmerkingen;
    	}
    	echo "</a>";
    } 
	echo "</span>";
}

function makeBlock1($klant_obj, $acma_arr, $soort, $titel, $titel1, $user)
{
    echo "<span class='".$soort."' title='". $titel1 ."' >";
    echo "<b>Permanentie</b><br/>";
    echo $user->voornaam . " " . $user->naam . "<br/>";
    echo $user->tel;
    echo "</span>";
}

function getExtFromFile($file)
{
	$file_tmp = explode(".", $file);
	$aantal = count( $file_tmp );
	return $file_tmp[$aantal-1];
}

$mapping_name = array();
$mapping_name["cus_naam"] = "Naam";
$mapping_name["cus_bedrijf"] = "Bedrijf";
$mapping_name["cus_btw"] = "BTW";
$mapping_name["cus_straat"] = "Straat";
$mapping_name["cus_nr"] = "Nr";
$mapping_name["cus_postcode"] = "Postcode";
$mapping_name["cus_gemeente"] = "Gemeente";
$mapping_name["cus_land_id"] = "Land";
$mapping_name["cus_email"] = "E-mail";
$mapping_name["cus_tel"] = "Tel.";
$mapping_name["cus_gsm"] = "GSM";
$mapping_name["cus_acma"] = "ACMA";
$mapping_name["cus_contact"] = "Al gecontacteerd";
$mapping_name["cus_fac_adres"] = "Facturatie adres anders";
$mapping_name["cus_fac_naam"] = "Facturatie Naam";
$mapping_name["cus_fac_straat"] = "Facturatie Straat";
$mapping_name["cus_fac_nr"] = "Facturatie Nr";
$mapping_name["cus_fac_postcode"] = "Facturatie Postcode";
$mapping_name["cus_fac_gemeente"] = "Facturatie Gemeente";
$mapping_name["cus_fac_land_id"] = "Facturatie Land";
$mapping_name["cus_offerte_datum"] = "Offerte datum";
$mapping_name["cus_offerte_gemaakt"] = "Offerte gemaakt";
$mapping_name["cus_offerte_besproken"] = "Offerte besproken";
$mapping_name["cus_aant_panelen"] = "Aantal panelen";
$mapping_name["cus_type_panelen"] = "Type panelen";
$mapping_name["cus_w_panelen"] = "Vermogen paneel";
$mapping_name["cus_merk_panelen"] = "Merk panelen";
$mapping_name["cus_kwhkwp"] = "Opbrengst factor";
$mapping_name["cus_hoek_z"] = "Hoek panelen met het zuiden";
$mapping_name["cus_hoek"] = "Hoek vd panelen";
$mapping_name["cus_soort_dak"] = "Soort dak";
$mapping_name["cus_prijs_wp"] = "Prijs Wp";
$mapping_name["cus_bedrag_excl"] = "Bedrag excl.";
$mapping_name["cus_woning5j"] = "Woning 5j";
$mapping_name["cus_opwoning"] = "Panelen op woning";
$mapping_name["cus_driefasig"] = "Driefasig";
$mapping_name["cus_nzn"] = "Net zonder neuter";
$mapping_name["cus_verkoop"] = "Overeenkomst";
$mapping_name["cus_verkoop_datum"] = "Verkoopsdatum";
$mapping_name["cus_start_huur"] = "Start huur";
$mapping_name["cus_reden"] = "Reden";
$mapping_name["cus_datum_orderbon"] = "Datum orderbon";
$mapping_name["cus_sunnybeam"] = "Sunny Beam";
$mapping_name["cus_actie"] = "Actie";
$mapping_name["cus_ingetekend"] = "Ingetekend";
$mapping_name["cus_werkdoc_door"] = "Werkdocument door";
$mapping_name["cus_werkdoc_klaar"] = "Werkdocument klaar";
$mapping_name["cus_werkdoc_opm"] = "Werkdocument opmerking";
$mapping_name["cus_werk_aant_panelen"] = "Werkdocument Aantal panelen";
$mapping_name["cus_werk_w_panelen"] = "Werkdocument vermogen paneel ";
$mapping_name["cus_werk_merk_panelen"] = "Werkdocument merk panelen";
$mapping_name["cus_werk_aant_omvormers"] = "Werkdocument aantal omvormers";
$mapping_name["cus_ac_vermogen"] = "AC Vermogen";
$mapping_name["cus_arei"] = "AREI keuring";
$mapping_name["cus_klant_tevree"] = "Klant tevreden";
$mapping_name["cus_tevree_reden"] = "Reden tevreden";
$mapping_name["cus_type_omvormers"] = "Type omvormers";
$mapping_name["cus_opmerkingen"] = "Opmerkingen";
$mapping_name["cus_arei_datum"] = "AREI datum";
$mapping_name["cus_arei_meterstand"] = "AREI meterstand";
$mapping_name["cus_vreg_datum"] = "VREG datum";
$mapping_name["cus_vreg_un"] = "VREG gebruikersnaam";
$mapping_name["cus_vreg_pwd"] = "VREG wachtwoord";
$mapping_name["cus_datum_net"] = "Meldingsdatum netbeheerder";
$mapping_name["cus_pvz"] = "PVZ nr";
$mapping_name["cus_ean"] = "EAN nr";
$mapping_name["cus_reknr"] = "Rekeningnummer";
$mapping_name["cus_iban"] = "IBAN";
$mapping_name["cus_bic"] = "BIC";
$mapping_name["cus_banknaam"] = "Naam v/d bank";
$mapping_name["cus_opmeting_datum"] = "Opmetingsdatum";
$mapping_name["cus_opmeting_door"] = "Opmeting door";
$mapping_name["cus_installatie_datum"] = "Installatie datum";
$mapping_name["cus_installatie_datum2"] = "Installatie datum2";
$mapping_name["cus_installatie_datum3"] = "Installatie datum3";
$mapping_name["cus_installatie_datum4"] = "Installatie datum4";
$mapping_name["cus_nw_installatie_datum"] = "Nieuwe installatie datum";
$mapping_name["cus_aanp_datum"] = "Installatie aanpassen";
$mapping_name["cus_installatie_ploeg"] = "Installatieploeg";
$mapping_name["cus_elec"] = "Elektrische bekabeling door";
$mapping_name["cus_elec_door"] = "Elektrische bekabeling door";
$mapping_name["cus_elec_datum"] = "Elektrische bekabeling datum";
$mapping_name["cus_gemeentepremie"] = "Gemeentepremie";
$mapping_name["cus_bouwvergunning"] = "Bouwvergunning";
$mapping_name["cus_offerte_filename"] = "Offerte bestand";
$mapping_name["cus_order_filename"] = "Orderbon bestand";
$mapping_name["cus_werkdoc_filename"] = "Werkdocument";
$mapping_name["cus_areidoc_filename"] = "AREI document";
$mapping_name["cus_gemeentedoc_filename"] = "Gemeentedocument";
$mapping_name["cus_bouwvergunning_filename"] = "Bouwvergunning document";
$mapping_name["cus_stringdoc_filename"] = "Stringopmetingsrapport";
$mapping_name["cus_werkdoc_pic1"] = "Werkdocument foto 1";
$mapping_name["cus_werkdoc_pic2"] = "Werkdocument foto 2";
$mapping_name["cus_factuur_filename"] = "Factuur";
$mapping_name["cus_kent_ons_van"] = "Kent ons van";
$mapping_name["cus_opmetingdoc_filename"] = "Opmetingsdocument";
$mapping_name["cus_verkoopsbedrag_excl"] = "&euro; Verkoop excl";
$mapping_name["cus_verkoopsbedrag_incl"] = "&euro; Verkoop incl";
$mapping_name["cus_elecdoc_filename"] = "Electrisch schema";
$mapping_name["cus_werkdoc_check"] = "Werkdocument gecontrolleerd";
$mapping_name["cus_bet_termijn"] = "Betalings termijn";
$mapping_name["cus_ref"] = "Referentie";
$mapping_name["uitgenodigde"] = "Uitgenodigde";
$mapping_name["cus_ont_huur"] = "Tot. ontv. huur per maand";
$mapping_name["cus_bet_huur"] = "Tot. te bet. huur per maand";
$mapping_name["cus_looptijd_huur"] = "Looptijd huur";
$mapping_name["cus_huur_doc"] = "Huurdocs volledig";
$mapping_name["Uitbreiding"] = "Uitbreiding";
$mapping_name["cus_schaduw"] = "Schaduw";
$mapping_name["cus_schaduw_w"] = "Schaduw winter";
$mapping_name["cus_schaduw_z"] = "Schaduw zomer";
$mapping_name["cus_schaduw_lh"] = "Schaduw Lente/herfst";
$mapping_name["cus_dag"] = "Dag verbruik";
$mapping_name["cus_nacht"] = "Nacht verbruik";
$mapping_name["cus_dag_tarief"] = "Dag tarief";
$mapping_name["cus_nacht_tarief"] = "Nacht tarief";
$mapping_name["cus_vergoeding"] = "Vaste vergoeding";
$mapping_name["cus_overschrijving"] = "Via overschrijving";
$mapping_name["cus_indienst"] = "Datum in dienst";
$mapping_name["cus_email_verslag"] = "E-mail verslag";
$mapping_name["cus_vreg_sync"] = "Sync met VREG";
$mapping_name["cus_dom_datum"] = "Startdatum domicili&euml;ring";
$mapping_name["cus_indienst"] = "Datum in dienst";
$mapping_name["cus_int_boiler"] = "Interesse in boiler";
$mapping_name["cus_int_mon"] = "Interesse in monitoring";
$mapping_name["cus_int_solar"] = "Interesse in zonnepanelen";
$mapping_name["cus_int_iso"] = "Interesse in isolatie";
$mapping_name["cus_einde_looptijd"] = "Einde domiciliering";

// boilermapping
$mapping_name["cus_acma_boi"] = "ACMA Boiler";
$mapping_name["cus_aant_pers"] = "Boiler # personen";
$mapping_name["cus_gebruik"] = "Gebruik boiler";
$mapping_name["cus_huidig"] = "Huidige boiler op";
$mapping_name["cus_huidige_cap"] = "Capaciteit huidige boiler";
$mapping_name["cus_doorgang"] = "Doorgangen vrij?";
$mapping_name["cus_dak"] = "Type dak";
$mapping_name["cus_verw"] = "Soort verwarming";
$mapping_name["cus_factor"] = "Opbrengstfactor";
$mapping_name["cus_hoek_z"] = "Hoek naar het zuiden";
$mapping_name["cus_hoek_p"] = "Hoek van de panelen";
$mapping_name["cus_comp"] = "Boiler compensatie factor";
$mapping_name["cus_woning"] = "Woning BTW %";
$mapping_name["cus_voor2006"] = "Aangesloten voor 2006";
$mapping_name["cus_verkoop_boi"] = "Overeenkomst";
$mapping_name["cus_reden"] = "Reden";
$mapping_name["cus_datverkoop"] = "Boiler datum overeenkomst";
$mapping_name["cus_prijs_incl"] = "Boiler prijs incl.";
$mapping_name["cus_off"] = "Datum offerte boiler";
$mapping_name["cus_inst"] = "Datum installatie boiler";
$mapping_name["cus_opm"] = "Datum opmeting boiler";
$mapping_name["cus_ean"] = "EAN nummer";
$mapping_name["cus_vs_cap"] = "Voorstel boiler capaciteit";
$mapping_name["cus_vs_col"] = "Voorstel boiler # collectoren";
$mapping_name["cus_lanpor"] = "Landscape/Portrait";
$mapping_name["cus_cv"] = "Aansluiten CV";
$mapping_name["cus_sanitair_datum"] = "Sanitaire aansluiting";
$mapping_name["cus_mailing"] = "Mailing actief";
$mapping_name["groen stroom meter"] = "Type Groenstroommeter";
$mapping_name["groen stroom meter offset"] = "Groenstroommeter offset";
$mapping_name["groen stroom meter offset datum"] = "Groenstroommeter offset datum";
$mapping_name["groen stroom meter SIM"] = "Lumibox SIM nr";
$mapping_name["groen stroom meter SIMTELNR"] = "Lumibox SIM tel. nr";
$mapping_name["groen stroom meter TELNR ALARM"] = "Lumibox Alarm tel. nr";


// aanpassingen ook bijwerken in geschiedenis.php
$kent_ons_van = array();
$kent_ons_van[1] = "Social media";
$kent_ons_van[2] = "Previous participation";

function get_days_in_month($month, $year)
{
   return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year %400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

/*
function getExtFromFile($file)
{
	$file_tmp = explode(".", $file);
	$aantal = count( $file_tmp );
	return $file_tmp[$aantal-1];
}
*/

function getMensualtiteit( $bedrag )
{
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_finalia WHERE min <= " . $bedrag . " AND max >= " . $bedrag) or die( mysqli_error($conn) . " " . __LINE__ );
    if( mysqli_num_rows($q_zoek) == 1 )
    {
        $rec = mysqli_fetch_object($q_zoek);
        
        $looptijd = $rec->looptijd;
        $rente = $rec->rentevoet;
        
        $dd = 1 + ($rente / 100);
        $dd1 = pow($dd,1/12) - 1;
        $afb = $bedrag / ( (1-(1/pow( 1+$dd1,$looptijd))) / $dd1 ); 
        
        return number_format($afb,2,","," ");    
    }else
    {
        return "<span class='error'> - Bedrag te groot of te klein </span>";
    }
}

function send_sms($nrs,$msg)
{
    $user = "ilumen";
    $password = "RLRUCUGDcSQWRR";
    $api_id = "3432323";
    $baseurl ="http://api.clickatell.com";
 
    $text = urlencode($msg);
    $to = $nrs;
 
    // auth call
    $url = "$baseurl/http/auth?user=$user&password=$password&api_id=$api_id";
    
     // do auth call
    $ret = file($url);
 
    // explode our response. return string is on first line of the data returned
    $sess = explode(":",$ret[0]);
    if ($sess[0] == "OK") {
 
        
        $sess_id = trim($sess[1]); // remove any whitespace
        // $url = "$baseurl/http/sendmsg?session_id=$sess_id&to=$to&text=$text&from=Futech";
        
        //http://api.clickatell.com/http_batch/startbatch?session_id=xxx&...............&template=Hi #field1#, yourbalance is #field2#.&from=Sender&deliv_ack=1
        // ophalen van batch_id
        $url1 = "$baseurl/http_batch/startbatch?session_id=$sess_id&template=$text&deliv_ack=1&from=3213297508";
        $ret1 = file($url1);
        $tmp_batch = explode(" ", $ret1[0]); 
        
        $url = "$baseurl/http_batch/quicksend?session_id=$sess_id&batch_id=$tmp_batch[1]&to=$to&text=$text&from=3213297508";
        
        // do sendmsg call
        $ret = file($url);
        $send = explode(":",$ret[0]);
        
        if ($send[0] == "ID") {
            echo "successnmessage ID: ". $send[1];
        } else {
            echo "send message failed";
        }
        
    } else {
        echo "Authentication failure: ". $ret[0];
    }
}

function getTotaleLumi($cus_id, $lumi_rec, $gsm, $offset_active)
{
    $meeting_offset_arr = array();
    
    if( $offset_active == 1 )
    {
        $q_zoek_offset = mysqli_query($conn, "SELECT * FROM tbl_vreg_instellingen WHERE vi_cus_id = " . $cus_id . " AND vi_soort = 'im'");
        
        if( mysqli_num_rows($q_zoek_offset) > 0 )
        {
            while( $zoek_offset = mysqli_fetch_object($q_zoek_offset) )
            {
                $datum = date("Y-m-d", mktime(0,0,0,$zoek_offset->vi_maand, $zoek_offset->vi_dag, $zoek_offset->vi_jaar ) );
                $meeting_offset_arr[ $datum ] = $zoek_offset->vi_waarde;
            }
        }
        
        $puls_offset_arr = array();
        
        $q_zoek_offset = mysqli_query($conn, "SELECT * FROM tbl_vreg_instellingen WHERE vi_cus_id = " . $cus_id . " AND vi_soort = 'lb'");
        
        if( mysqli_num_rows($q_zoek_offset) > 0 )
        {
            while( $zoek_offset = mysqli_fetch_object($q_zoek_offset) )
            {
                $datum = date("Y-m-d", mktime(0,0,0,$zoek_offset->vi_maand, $zoek_offset->vi_dag, $zoek_offset->vi_jaar ) );
                $puls_offset_arr[ $datum ] = $zoek_offset->vi_waarde;
            }
        }
    }
    
    //var_dump($meeting_offset_arr,$puls_offset_arr );
    
    $q_p_waardes = mysqli_query($conn, "SELECT * FROM tbl_ibp WHERE ibp_id = " . $lumi_rec->id . " ORDER BY id ASC") or die( mysqli_error($conn) );
    
    $puls_arr = array();
    $waarde_gsm = $lumi_rec->offset;
    
    if( mysqli_num_rows($q_p_waardes) > 0 )
    {
        $puls_arr[ $lumi_rec->offset_start ] = $lumi_rec->offset;

        $groep_dag = "";
        $tot = 0;
        
        
        while( $p = mysqli_fetch_object($q_p_waardes) )
        {
            
            
            if( $groep_dag != $p->datum_start )
            {
                
                
                if( $groep_dag != "" )
                {
                    
                    
                    if( $groep_dag != $lumi_rec->offset_start )
                    {
                        
                        
                        if( isset( $puls_offset_arr[ $groep_dag ] ) )
                        {
                            $waarde_gsm += $puls_offset_arr[ $groep_dag ];
                        }
                        
                        $waarde_gsm += $tot/$gsm->puls;

                        $puls_arr[ $groep_dag ] = number_format($waarde_gsm, 0, "", "" );
                        $pulse_laatste_datum = $groep_dag;
                        $pulse_laatste_waarde = $waarde_gsm;
                    }
                }
                
                $groep_dag = $p->datum_start;
                $tot = 0;    
            }else
            {
                $tot += $p->pulse1 + $p->pulse2 + $p->pulse3 + $p->pulse4 + $p->pulse5 + $p->pulse6 + $p->pulse7 + $p->pulse8; 
            }
        }
    }
    
    if( $gsm && $gsm->puls > 0 )
    {
        $waarde_gsm += $tot/$gsm->puls;
    }
    
    return number_format($waarde_gsm,0,"","");
}

if(!function_exists('factuurNaam')) 
{ 
    function factuurNaam( $fac_id, $actie )
    {
        $conn = $GLOBALS["conn"];

        $q_cf = mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_id=".$fac_id . " LIMIT 1") or die(mysqli_error($conn) . " " . __LINE__ );
        $cf = mysqli_fetch_object($q_cf);
        
        $strip_name = explode('.', $cf->cf_file);
        $boekjaar = '';
        $vv = 'ESC';
        $fac_nr = $strip_name[0];
        
        $bj = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_boekjaar WHERE '".$cf->cf_date ."' BETWEEN boekjaar_start AND boekjaar_einde"));
        
        $dir = substr( $bj->boekjaar_start,0,4) . substr( $bj->boekjaar_einde,0,4);   
        $filename = $vv . "_BJ". $dir . "_". $fac_nr .".pdf";
        
        return $filename;
    }
}

?>