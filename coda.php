<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";
include "inc/phpmailer5/class.phpmailer.php";
include "inc/fpdf.php";
include "inc/fpdi.php";

if( isset( $_POST["btn_reknrok"] ) && $_POST["btn_reknrok"] == "Rekening nummer koppelen" )
{
    $q_ins = "INSERT INTO kal_reknr(klant_id, tabel, reknr) VALUES(". $_POST["levid"] .",'kal_leveranciers', '". $_POST["reknr"] ."')";
    mysqli_query($conn, $q_ins);
}

if( isset( $_POST["btn_reknrok_cus"] ) && $_POST["btn_reknrok_cus"] == "Rekening nummer koppelen" )
{
    echo $q_ins = "UPDATE kal_customers SET cus_iban = '".$_POST["reknr"]."' WHERE cus_id = " . $_POST["cus_id"];
    mysqli_query($conn, $q_ins);
}


/*
echo "<pre>";
var_dump( $_POST );
echo "</pre>";
*/

if( isset($_POST["dom_klant"]) && is_array($_POST["dom_klant"]) )
{
    foreach( $_POST["dom_klant"] as $waarde )
    {
        $klant_bedrag = explode("_", $waarde);
        //echo "<br>" . $klant_bedrag[0] . " " . $klant_bedrag[1];
        
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant_bedrag[0]));
        $coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id = " . $klant_bedrag[0] ." AND bedrag < 0 ORDER BY 1 DESC LIMIT 1"));
        
        $reden = $coda->med3;
        
        $mail = new PHPMailer();
		$mail->From     = "info@futech.be"; 
        $mail->FromName = "Futech"; 
        
        //$mail->IsSMTP(); 
        
        $mail->Host     = "192.168.1.250";
        $mail->IsHTML(true);// send as HTML
        $mail->Mailer   = "smtp";
                
        // versturen van mail
        
        if( $klant->cus_overschrijving == '0' )
        {
        $text_body  ="
<table cellpadding='0' cellspacing='0'>
<tr><td>Beste ". $klant->cus_naam .",<br/></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>We ondervinden problemen met uw domiciliering voor de huur van de zonnepanelen.</td></tr>
<tr><td>We hebben uw rekening proberen te debiteren, maar zonder succes.</td></tr>
<tr><td>De foutmelding die we kregen tijdens het debiteren is : '". $reden ."'</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Gelieve dringend contact op te nemen met uw bank om interesten (conform algemene voorwaarden) te vermijden.</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Uw rekening zal de volgende keer gedebiteerd worden voor het volledige bedrag dat nog open staat ( ". str_replace(".",",", $klant_bedrag[1]) ." euro ) + de vaste maandelijkse aflossing.</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>U kan ook het openstaand saldo storten om zeker interesten te vermijden. Hou er rekening mee dat het gestort moet zijn voor de vijfde van de maand, omdat anders de domiciliering reeds gestart is.</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Uw referte : ". maakReferte( $klant->cus_id, $conn ) ."</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>FUTECH BVBA</td></tr>
<tr><td>Ambachtstraat 19</td></tr>
<tr><td>3980 Tessenderlo</td></tr>
<tr><td>BE 0808 765 108</td></tr>
<table>";
        }else
        {
        $text_body  ="
<table cellpadding='0' cellspacing='0'>
<tr><td>Beste ". $klant->cus_naam .",<br/></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>We hebben van u nog geen betaling mogen ontvangen voor de huur van de panelen</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Gelieve het openstaande bedrag voor het einde van de maand te storten ( ". str_replace(".",",", $klant_bedrag[1]) ." euro ) </td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>Uw referte : ". maakReferte( $klant->cus_id, $conn ) ."</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>FUTECH BVBA</td></tr>
<tr><td>Ambachtstraat 19</td></tr>
<tr><td>3980 Tessenderlo</td></tr>
<tr><td>BE 0808 765 108</td></tr>
<table>";    
        } 

		$body = $text_body;
        
        
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
		//$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
		
		$pdf->Text(110, 55, "Aan:" );
		
        if( $klant->uit_cus_id != 0 )
        {
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {
                $hoofdklant = mysqli_fetch_object($q_hoofdklant);
                
        		$klant->cus_naam = html_entity_decode(trim($hoofdklant->cus_naam),  ENT_QUOTES);
        		$klant->cus_bedrijf = html_entity_decode(trim($hoofdklant->cus_bedrijf),  ENT_QUOTES);
        		$klant->cus_straat = html_entity_decode(trim($hoofdklant->cus_straat),  ENT_QUOTES);
        		$klant->cus_gemeente = html_entity_decode(trim($hoofdklant->cus_gemeente),  ENT_QUOTES);
                $klant->cus_postcode = $hoofdklant->cus_postcode;
                $klant->cus_nr = $hoofdklant->cus_nr;
            }else
            {
                $klant->cus_naam = html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES);
        		$klant->cus_bedrijf = html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES);
        		$klant->cus_straat = html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES);
        		$klant->cus_gemeente = html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES);   
            }
		}else
        {
            $klant->cus_naam = html_entity_decode(trim($klant->cus_naam),  ENT_QUOTES);
    		$klant->cus_bedrijf = html_entity_decode(trim($klant->cus_bedrijf),  ENT_QUOTES);
    		$klant->cus_straat = html_entity_decode(trim($klant->cus_straat),  ENT_QUOTES);
    		$klant->cus_gemeente = html_entity_decode(trim($klant->cus_gemeente),  ENT_QUOTES);    
        }
        
		if( $klant->cus_naam == $klant->cus_bedrijf )
		{
			$pdf->Text(110, 60, trim($klant->cus_naam) );
		}else
		{
			$pdf->Text(110, 60, trim($klant->cus_naam) . " " . trim($klant->cus_bedrijf) );	
		}
		
		$pdf->Text(110, 65, trim($klant->cus_straat) . " " . trim($klant->cus_nr) );
		$pdf->Text(110, 70, trim($klant->cus_postcode) . " " . trim($klant->cus_gemeente) );
		
		$pdf->Text(1, 100, "." );
		$pdf->Text(209, 100, "." );
		
		$field1 = "Tessenderlo " . date('d') ."-" . date('m') . "-" . date('Y') ;
		$pdf->Text(120, 110, $field1 );
		
		// het aantal aanmaningen ook vermelden in de titel
		// tellen van het aantal aanmanignen en deze ook vermelden in de filename
		$aantal_aanmaningen = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $klant->cus_id . " AND aa_factuur = '". $_POST["fac_nr_" . $klant->cus_id] ."'"));
		$aantal_aanmaningen++;
		
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
		
		$field2 = "Beste,";
		$pdf->Text(21, 120, $field2 );
        
        if( $klant->cus_overschrijving == '0' )
        {
            $title = "Uw domiciliering";
		    $pdf->Text(20, 100, $title );
            
            $field4 = "We ondervinden problemen met uw domiciliering voor de huur van de zonnepanelen.";
     		$pdf->Text(21, 130, $field4 );
            $field4 = "We hebben uw rekening proberen te debiteren, maar zonder succes.";
     		$pdf->Text(21, 135, $field4 );
            $field4 = "De foutmelding die we kregen tijdens het debiteren is :";
     		$pdf->Text(21, 140, $field4 );
            
            $pdf->SetXY( 20, 140 );
     		$pdf->MultiCell(160, 5, $reden, 0, 'L');
            
            $field4 = "Gelieve contact op te nemen met uw bank om interesten (conform algemene voorwaarden) te vermijden.";
     		$pdf->Text(21, 185, $field4 );
            
            $field4 = "Uw rekening zal de volgende keer gedebiteerd worden voor het volledige bedrag dat nog open staat:";
     		$pdf->Text(21, 190, $field4 );
            
            $field4 = str_replace(".",",", $klant_bedrag[1]) . " euro + de vaste maandelijkse aflossing.";
     		$pdf->Text(21, 195, $field4 );
            
            $field4 = "U kan ook het openstaand saldo storten om zeker interesten te vermijden. Hou er rekening mee dat het";
     		$pdf->Text(21, 200, $field4 );
            
            $field4 = "gestort moet zijn voor de vijfde van de maand, omdat anders de domiciliering reeds gestart is.";
     		$pdf->Text(21, 205, $field4 );
            
            $field4 = "Uw referte : ". maakReferte( $klant->cus_id, $conn );
     		$pdf->Text(21, 213, $field4 );
        }else
        {
            $title = "Uw overschrijving";
		    $pdf->Text(20, 110, $title );
            
            $field4 = "We hebben van u nog geen betaling mogen ontvangen voor de huur van de panelen.";
     		$pdf->Text(21, 150, $field4 );
            $field4 = "Gelieve het openstaande bedrag voor het einde van de maand te storten : " . str_replace(".",",", $klant_bedrag[1]) . " euro.";
            
            $pdf->Text(21, 155, $field4 );
            $field4 = "Dit om interesten te vermijden. (conform algemene voorwaarden)";
            
     		$pdf->Text(21, 165, $field4 );
            $field4 = "Uw referte : ". maakReferte( $klant->cus_id, $conn );
     		
        }
        
		/**
 * $field3 = "Bij nazicht van onze boekhouding blijkt dat uw factuur tot op heden onbetaald bleef. De vervaldatum is overschreden.";
 * 		$pdf->SetXY( 20, 140 );
 * 		$pdf->MultiCell(160, 5, $field3, 0, 'L');
 * 	
 * 		
 * 		
 * 		
 * 		// INIT
 * 		$offsetmin = 0;
 * 		$offset = 0;
 * 		
 * 		if( $aantal_aanmaningen == 1 )
 * 		{
 * 			$field5 = "Mogen wij u vragen om deze betaling alsnog zo snel mogelijk uit te voeren om op deze manier extra kosten te vermijden. Vanaf de volgende aanmaning zal de interest vermeld in onze algemene voorwaarden toegepast worden.";
 * 			$pdf->SetXY( 20, 167 );
 * 			$pdf->MultiCell(160, 5, $field5, 0, 'L');
 * 		}else
 * 		{
 * 			$offsetmin = 22;
 * 		}
 * 		
 * 		$field6 = "Indien de betaling reeds werd uitgevoerd, gelieve dit schrijven dan als nietig te verklaren.";
 * 		$pdf->Text(21, 192-$offsetmin, $field6 );		
 * 		
 * 		$field7 = "Openstaand bedrag : ".iconv("UTF-8", "cp1250", "�")." " . str_replace(".", ",", $bedrag["bedrag"]) ;
 * 		$pdf->Text(21, 202-$offsetmin, $field7 );
 * 		
 * 		if( !empty($bedrag["intrest"]) && $bedrag["intrest"] > 0 )
 * 		{
 * 			$offset = 12;
 * 			$regel = "Zoals in voorgaande brief vermeld passen wij vanaf heden onze algemene voorwaarden toe.";
 * 			$pdf->Text(21, 207-$offsetmin, $regel );
 * 			
 * 			$regel = "Intrest : ".iconv("UTF-8", "cp1250", "�")." " . str_replace(".", ",", $bedrag["intrest"]);
 * 			$pdf->Text(21, 212-$offsetmin, $regel );
 * 			
 * 			$regel = "Totaal openstaand bedrag : ".iconv("UTF-8", "cp1250", "�")." " . str_replace(".", ",", $bedrag["intrest"] + $bedrag["bedrag"]);
 * 			$pdf->Text(21, 217-$offsetmin, $regel );
 * 		}
 * 		
 * 		if( $aantal_aanmaningen == 3 )
 * 		{
 * 			$pdf->Text(21, 222-$offsetmin, "Dit is de laatste aanmaning alvorens de gegevens doorgestuurd worden naar de advocaat." );
 * 			$offset += 3;
 * 		}
 */
		
		$field8 = "Alvast bedankt,";
		$pdf->Text(21, 222+$offset-$offsetmin, $field8 );
		
		$field9 = "Met vriendelijke groeten,";
		$pdf->Text(21, 232+$offset-$offsetmin, $field9 );
		
		$field10 = "De boekhouding";
		$pdf->Text(21, 237+$offset-$offsetmin, $field10 );
		
		// enkel het eerste gedeelte nemen van de filename
		$tmp_fac_nr = explode(".", $_POST["fac_nr_" . $klant->cus_id]);
		
		$factuur = $pdf->Output('aanmaning_'. str_replace(" ", "_", trim( $klant->cus_naam ) ) .'.pdf', "S");
        
        $filename = 'aanmaning_'. date("d") . "-" . date("m") . "-" . date('Y') . "_" . str_replace(" ", "_", trim( $klant->cus_naam ) )  .'.pdf';
		
        @mkdir( "aanmaningen_dom/" );
		chdir( "aanmaningen_dom/" );
		$fp1 = fopen($filename, 'w');
        fwrite($fp1, $factuur);
        fclose($fp1);
		chdir("../");
        
        $mail->Body    = $body; 
        $mail->AltBody = $text_body;
        
        
        if( empty( $klant->cus_email ) )
        {
            $mail->AddAddress("aanmaningen@futech.be");
            //$mail->AddAddress("dimitri@futech.be");   
            $mail->Subject = "Futech | Domiciliering Zonnepanelen - Aanmaning - KLANT HEEFT GEEN E-MAIL ADRES"; 
        }else
        {
            $mail->AddAddress($klant->cus_email, $klant->cus_naam);
            //$mail->AddAddress("dimitri@futech.be");
            $mail->Subject = "Futech | Domiciliering Zonnepanelen - Aanmaning";    
        }
        
        /*
        $mail->AddAddress("dimitri@futech.be");
        $mail->Subject = "Futech | Domiciliering Zonnepanelen - Aanmaning";
        */
        
        $mail->AddCC("aanmaningen@futech.be");
        $mail->AddBCC("dimitri@futech.be");
        $mail->AddAttachment('aanmaningen_dom/' . $filename);
        $mail->Send();
        
        
        // toevoegen in databank
        
        $q_ins = "INSERT INTO kal_coda_dom_aanm(cus_id, bedrag) VALUES(".$klant_bedrag[0].",'".$klant_bedrag[1]."')";
        mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>CODA<?php include "inc/erp_titel.php" ?></title>

<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>


<script type="text/javascript" 	src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

<script type="text/javascript" src="js/jquery.validate.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript" src="js/functions.js"></script>
<script type="text/javascript">

function popup(id)
{
    window.open(id,'Popup','toolbar=no,location=yes,status=no,menubar=no,scrollbars=no,resizable=no,width=1100,height=600,left=430,top=23');
}

function showHide(cus_id)
{
    //alert(document.getElementById("id_" + cus_id).style.display);
    //alert( "id_" + cus_id );
    
    if( document.getElementById("id_" + cus_id).style.display == "none" )
    {
        document.getElementById("id_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("id_" + cus_id).style.display = "none";
    }
}

function showHidep(cus_id)
{
    //alert(document.getElementById("id_" + cus_id).style.display);
    
    if( document.getElementById("idp_" + cus_id).style.display == "none" )
    {
        document.getElementById("idp_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("idp_" + cus_id).style.display = "none";
    }
}

function showHidel(cus_id)
{
    //alert(document.getElementById("id_" + cus_id).style.display);
    
    if( document.getElementById("idl_" + cus_id).style.display == "none" )
    {
        document.getElementById("idl_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("idl_" + cus_id).style.display = "none";
    }
}

function isNumberKey(evt)
{
    var charCode = (evt.which) ? evt.which : evt.keyCode;

    if (charCode > 31 && (charCode < 48 || charCode > 57 ) && charCode != 46 && charCode != 44 && charCode != 45)
        return false;

    return true;
}

function commadot(that) {
	if (that.value.indexOf(",") >= 0) 
	{
		that.value = that.value.replace(/\,/g,".");
	}
}



$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
    
    $("#tabs").tabs({
   cache:false,
   load: function (e, ui) {
     $(ui.panel).find(".tab-loading").remove();
   },
   select: function (e, ui) {
     var $panel = $(ui.panel);

     if ($panel.is(":empty")) {
         $panel.append("<div class='tab-loading'><img src='images/indicator.gif' />&nbsp;&nbsp;Loading...</div>")
     }
    }
 });

});



$(function() {
	$( "#datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
});

</script>
<script>
            
var XMLHttpRequestObject1 = false;

try{
	XMLHttpRequestObject1 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject1 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject1 = false
	}
 
	if(!XMLHttpRequestObject1 && window.XMLHttpRequest){
		XMLHttpRequestObject1 = new XMLHttpRequest();
	}
}

function koppelAanKlant( id )
{
    klant_id = document.getElementById("sel_" + id).value;
    datasource = "coda_ajax.php?coda_id=" + id + "&cus_id=" + klant_id;

	if(XMLHttpRequestObject1){
		
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200)
            {
                document.getElementById("summary").innerHTML += XMLHttpRequestObject1.responseText + "<br/>";
                document.getElementById("summary").style.border = "1px solid black";
                
                $("#tabel_"+id).slideUp("slow");
			}
		}
		XMLHttpRequestObject1.send(null);
	}
}

function koppelAanLev( id )
{
    project_id = document.getElementById("lev_" + id).value;
    datasource = "coda_ajax.php?coda_id=" + id + "&leverancier=" + project_id;

	if(XMLHttpRequestObject1){
		
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200)
            {
                document.getElementById("summary").innerHTML += XMLHttpRequestObject1.responseText + "<br/>";
                document.getElementById("summary").style.border = "1px solid black";
                
                $("#tabel_"+id).slideUp("slow");
			}
		}
		XMLHttpRequestObject1.send(null);
	}
}

function OntkoppelLev( id )
{
    datasource = "coda_ajax.php?coda_id=" + id + "&lev=1&soort=ontkoppeld";

	if(XMLHttpRequestObject1){
		
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200)
            {
                document.getElementById("summary").innerHTML += XMLHttpRequestObject1.responseText + "<br/>";
                document.getElementById("summary").style.border = "1px solid black";
                
                $("#tabel_"+id).slideUp("slow");
			}
		}
		XMLHttpRequestObject1.send(null);
	}
}



function OntkoppelKlant( id )
{
    datasource = "coda_ajax.php?coda_id=" + id + "&cus_id=1&soort=ontkoppeld";

	if(XMLHttpRequestObject1){
		
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200)
            {
                document.getElementById("summary").innerHTML += XMLHttpRequestObject1.responseText + "<br/>";
                document.getElementById("summary").style.border = "1px solid black";
                
                $("#tabel_"+id).slideUp("slow");
			}
		}
		XMLHttpRequestObject1.send(null);
	}
} 

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
    
    <h1>CODA</h1>
    <!--
    Huybrechts - Smets, maart 2032, bedrag - 3.47�<br /> 
    Slenders Adrianus, maart 2032, AREI keuring was 23.03.2012, doch omvormer eerst geleverd 29.03.2012 - creditnota 7 dagen�over 20 jaar
    -->
    <div id="tabs" style="width: 1000px;">
    	<ul>
    		<li><a href="#tabs-1">Inlezen</a></li>
            <li><a href="#tabs-3">Nog af te punten</a></li>
            <li><a href="coda_klant.php" title="Afgepunten klanten" >Afgepunte Klanten</a></li>
            <li><a href="coda_lev.php" title="Afgepunten leveranciers" >Afgepunte Leveranciers</a></li>
            <li><a href="#tabs-5">Auto.koppel</a></li>
        </ul>
    	
    	<div id="tabs-1">
            Coda bestanden inlezen die in de map coda staan :
            
            <?php
            
            if( $_SESSION[ $session_var ]->group_id != 8 )
            {
            
            ?>
            
            <form method="post" enctype='multipart/form-data' name="frm_file" id="frm_file" action="coda.php">
            <input type="submit" name="start_read" id="start_read" value="start" />
            </form>
            
            <?php
            }
            
            include_once "coda_class.php";
            
            if( isset( $_POST["start_read"] ) && $_POST["start_read"] == "start" )
            {
                $rentals = getHuurKlanten();
                
                //$q = mysqli_query($conn, "INSERT INTO tbl_test(value) VALUES('voor lezen bestanden')");
                
                if ($handle = opendir('coda')) {
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry != "." && $entry != ".." && $entry != "processed" ) {
                            
                            
                            //$q = mysqli_query($conn, "INSERT INTO tbl_test(value) VALUES('in lezen bestanden')");
                            
                            echo "<br/>" . $entry;
                            
                            $filename = $entry;
                            
                            $content = file_get_contents( "coda/" . $entry );
                            
                            
                            if( !stristr($content, "0823471503") )
                            {
                                die("<br /><span class='error'> BTW NUMMER NIET GEVONDEN </span>");
                            }
                            
                            
                            
                            $content_arr = explode("\n", $content);
                            
                            $c_arr = array();
                            $i=0;
                            foreach( $content_arr as $key => $c )
                            {
                                if( strlen($c) > 0 )
                                {
                                    $c_arr[ $key ] = substr($c, 0, 128);
                                    $i++;
                                }
                            }
                            $i--;
                            
                            // gegevens halen uit de eerste rij
                            $beginOpname = new beginOpnameClass( $c_arr[0] );
                            
                            if( $beginOpname->getStatus() == "ok" )
                            {
                                //$begin1 = gegevensOpname( $c_arr[1] );
                                $gegevensOpname = new gegevensOpnameClass( $c_arr[1] );
                                $gegevensOpnameNieuw = new gegevensOpnameNieuwClass ( $c_arr[ $i-1 ] );
                                $eindOpname = new eindOpnameClass ( $c_arr[ $i ] );
                            }
                            
                            // zoeken op welke regels 21 begint
                            $start_beweging = array();
                            $start = 0;
                            
                            foreach( $content_arr as $key => $c )
                            {
                                if( strlen($c) > 0 )
                                {
                                    if( substr($c, 0, 2) == 21 )
                                    {
                                        if( $start == 0 )
                                        {
                                            $start = $key;
                                        }
                                        
                                        $start_beweging[] = $key;
                                    }
                                }
                            }
                            $start_beweging[] = $i-1;
                            
                            $aantal_verrichtingen = count($start_beweging) - 1;
                            $verrichtingen = array();
                            
                            for( $j=0;$j<$aantal_verrichtingen;$j++ )
                            {
                                // DOORLOOPEN VAN ELKE INDIVIDUELE VERRICHTING 
                                for($k=$start_beweging[$j];$k<$start_beweging[$j+1];$k++ )
                                {
                                    $soort = substr($content_arr[$k], 0, 2 );
                                    $verrichtingen[$j][] = getDataVerr( $soort, $content_arr[$k] );
                                }
                            }
                            
                            /*
                            echo "<br>Aantal verrichtingen : " . $aantal_verrichtingen;
                            echo "<br>Datum : " . makeDatum($beginOpname->getDatum() );
                            echo "<br>" . $beginOpname->getNaam();
                            echo "<br>" . $gegevensOpname->getOudSaldo();
                            
                            
                            echo "<pre>";
                            var_dump( $verrichtingen );
                            echo "</pre>";
                            */
                            
                            foreach( $verrichtingen as $verr => $data )
                            {
                                $beweging = "";
                                $bedrag = "";
                                $med1 = "";
                                $med2 = "";
                                $med3 = array();
                                $boekdat = "";
                                $ref_cl = "";
                                $naam = "";
                                $volgnr1 = "";
                                $volgnr2 = "";
                                $reknr = "";
                                
                                foreach( $data as $aant => $dat )
                                {
                                   if( $dat->geg_opname == 21 )
                                    {
                                        $beweging = $dat->bew_soort;
                                        $bedrag = $dat->bew_bedrag;
                                        $med1 = $dat->bew_mededeling;
                                        $boekdat = makeDatum($dat->bew_boekingsdat);
                                        
                                        $volgnr1 = (string)$dat->bew_volgnr;
                                        $volgnr2 = (string)$dat->bew_detnr;
                                        
                                        $volgnr = $dat->bew_volgnr . " " . $dat->bew_detnr;
                                    }
                                    
                                    if( $dat->geg_opname == 22 )
                                    {
                                        $dat->bew_mededeling = trim( $dat->bew_mededeling );
                                        
                                        if( !empty( $dat->bew_mededeling ) )
                                        {
                                            $med2 = $dat->bew_mededeling;
                                        }
                                         
                                        $ref_cl = $dat->bew_referte_client;
                                        
                                    }
                                    
                                    if( $dat->geg_opname == 23 )
                                    {
                                        //echo "<br>0 " . $dat->bew_naam;
                                        $naam = $dat->bew_naam;
                                        $reknr = explode(" ", $dat->bew_reknr);
                                        $reknr = $reknr[0];
                                    }
                                    
                                    if( $dat->geg_opname == 31 )
                                    {
                                        if( substr($dat->info_mededeling, 0, 3) == "001" )
                                        {
                                            //echo "<br>4 " . substr($dat->info_mededeling, 3);
                                            $med3[] = trim( substr($dat->info_mededeling, 3) );
                                        }else
                                        {
                                            //echo "<br>5 " . $dat->info_mededeling;   
                                            $med3[] =  trim( $dat->info_mededeling );
                                        }
                                    }
                                    
                                    if( $dat->geg_opname == 32 )
                                    {
                                        $med3[] =  trim( $dat->info_mededeling );
                                    }
                                    
                                    if( $dat->geg_opname == 33 )
                                    {
                                        $med3[] =  trim( $dat->info_mededeling );
                                    }
                                }
                                
                                
                                
                                $q_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * 
                                                                       FROM kal_coda 
                                                                       WHERE volgnr = '".$volgnr1."' 
                                                                       AND detnr = '".$volgnr2."'
                                                                       AND boek_dat = '" . changeDate2EU( $boekdat ) ."'
                                                                       AND bedrag = '" . $beweging . $bedrag . "'"));
                                
                                
                                /*
                                if( $q_zoek == 0 && $volgnr2 != '0000' )
                                {
                                    echo "<br>" . $q_zoek;
                                    echo "SELECT * 
                                                                       FROM kal_coda 
                                                                       WHERE volgnr = '".$volgnr1."' 
                                                                       AND detnr = '".$volgnr2."'
                                                                       AND boek_dat = '" . changeDate2EU( $boekdat ) ."'
                                                                       AND bedrag = '" . $beweging . $bedrag . "'";
                                }
                                */
                                
                                if( $q_zoek == 0 && $volgnr2 == "0000" )
                                {
                                    echo  "<br>$entry\n";
                                    $n="";
                                    if( count($med3) > 0 )
                                    {
                                        //echo "<br>MED3 : ";
                                        
                                        foreach( $med3 as $m )
                                        {
                                            //echo "<br>" . $m;
                                            $n .= $m . "\n";
                                        }
                                    }
                                    
                                    if( $volgnr2 == "0000" )
                                    {
                                        $q_ins = "INSERT INTO kal_coda(volgnr,
                                                                       reknr,
                                                                       detnr,
                                                                       boek_dat,
                                                                       bedrag,
                                                                       med1,
                                                                       med2,
                                                                       med3,
                                                                       ref_cl,
                                                                       naam,
                                                                       beginsaldo,
                                                                       eindsaldo,
                                                                       tot_debet,
                                                                       tot_credit,
                                                                       filename) 
                                                                VALUES('".$volgnr1."',
                                                                       '".$reknr."',
                                                                       '".$volgnr2."',
                                                                       '". changeDate2EU( $boekdat ) ."',
                                                                       '".$beweging . $bedrag."',
                                                                       '". htmlentities(trim($med1), ENT_QUOTES) ."',
                                                                       '". htmlentities(trim($med2), ENT_QUOTES) ."',
                                                                       '". htmlentities(trim($n), ENT_QUOTES)  ."',
                                                                       '". htmlentities(trim($ref_cl), ENT_QUOTES) ."',
                                                                       '". htmlentities(trim($naam), ENT_QUOTES) ."',
                                                                       '". $gegevensOpname->getOudSaldo() ."',
                                                                       '". $gegevensOpnameNieuw->getNieuwSaldo() ."',
                                                                       '". $eindOpname->getDebetOmzet() ."',
                                                                       '". $eindOpname->getCreditOmzet() ."',
                                                                       '". $filename ."')";
                                        
                                        //echo "<br>" . $q_ins;
                                        mysqli_query($conn, $q_ins) or die( mysql_error );
                                        
                                        $laatste_id = mysqli_insert_id($conn);
                                        
                                        /* DEBUG DATA */
                                        echo "<hr>";
                                        echo "<br>Volgnummer : " . $volgnr;
                                        echo "<br>Beweging + bedrag : " . $beweging . $bedrag;
                                        echo "<br>MED1 : " . $med1;
                                        
                                        if( !empty( $med2 ) )
                                        {
                                            echo "<br>MED2 : " . $med2;
                                        }
                                        
                                        $m3 = "";
                                        if( count($med3) > 0 )
                                        {
                                            echo "<br>MED3 : ";
                                            
                                            foreach( $med3 as $m )
                                            {
                                                echo "<br>" . $m;
                                                $m3 .= $m;
                                            }
                                        }
                                        
                                        echo "<br>Van rek.nr. : " . $reknr;
                                        echo "<br>Boek.dat. : " . changeDate2EU( $boekdat );
                                        echo "<br>Referte cl. : " . $ref_cl;
                                        if( !empty( $naam ) )
                                        {
                                            echo "<br>Naam : " . $naam;
                                        }
                                        
                                        /* EINDE DEBUG DATA */
                                        
                                        echo "<br>" . $m3;
                                        
                                        if( stristr(trim($m3), "U/Ref:") )
                                        {
                                            //echo "<br>HIER IS EEN CREDIT SEPA OPDRACHT GEDAAN. "  . trim($ref_cl);
                                            
                                            $cus_id = substr(trim($m3),13);
                                            
                                            $klant_gev = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));
                                            
                                            if( $klant_gev > 0 )
                                            {
                                                $q_dom = "UPDATE kal_coda SET cus_id = " . $cus_id . " WHERE id = " . $laatste_id;
                                                //echo "<br>" . $q_dom;
                                                mysqli_query($conn, $q_dom) or die( mysqli_error($conn) . " " . $q_dom );
                                            }
                                        }else
                                        {
                                            if( stristr($n, "Bericht onbetaalde voor SEPA DD" ) )
                                            {
                                                if( stristr($n, "Payment Id" ) )
                                                {
                                                    $pos = strpos($n, "Payment Id");
                                                    
                                                    $cus_id = substr( trim( substr($n, $pos + strlen("Payment Id") + 1, 10) ), 6 );
                                                    
                                                    //echo "<br>" . trim( substr($n, $pos + strlen("Payment Id") + 1, 11) );
                                                    //echo "<br>TERUGKERENDE SEPA AANVRAAG. "  . substr($n, $pos + strlen("Payment Id")+1, 11);
                                                    /*
                                                    $q_dom = "INSERT INTO kal_coda_dom(cus_id, 
                                                                               bedrag, 
                                                                               boek_dat, 
                                                                               coda_id) 
                                                                        VALUES(".$cus_id.",
                                                                              '". $beweging . $bedrag ."',
                                                                              '". changeDate2EU( $boekdat ) ."',
                                                                              '". $laatste_id ."')";
                                                    */
                                                    
                                                    $q_dom = "UPDATE kal_coda SET cus_id = " . $cus_id . " WHERE id = " . $laatste_id;
                                                    mysqli_query($conn, $q_dom) or die( mysqli_error($conn) );
                                                }
                                            }
                                        }
                                    }
                                }                                                         
                            }
                            
                            //$q = mysqli_query($conn, "INSERT INTO tbl_test(value) VALUES('voor verplaatsen bestande')");
                            //$chk = rename("coda/".$filename, "coda/processed/" . $filename);
                            
                            //echo "<br>" . getcwd();
                            
                            if (copy("coda/".$filename,"coda/processed/" . $filename)) {
                              unlink("coda/".$filename);
                            }
                            
                            //$q = mysqli_query($conn, "INSERT INTO tbl_test(value) VALUES('einde in lezen bestanden')");
                        }
                    }
                    closedir($handle);
                }
                
                
                
                
                /*
                echo "<pre>";
                var_dump( $rentals );
                echo "</pre>";
                */
                /*
                $filename = $_FILES["file_coda"]["name"];
                
                $content = file_get_contents( $_FILES["file_coda"]["tmp_name"] );
                $content_arr = explode("\n", $content);
                */
                
                
                /*
                echo "<br>" . $gegevensOpnameNieuw->getNieuwSaldo();
                echo "<br>credit " . $eindOpname->getCreditOmzet();
                echo "<br>debet " . $eindOpname->getDebetOmzet();
                */
                
                /*
                * beginOpname
                * gegevensOpname
                * gegevensOpnameNieuw
                * eindOpname
                *
                */
                
                
                /*
                echo "<pre>";  
                foreach( $content_arr as $key => $c )
                {
                    if( strlen($c) > 0 )
                    {
                        echo "<br>" . $key ."-->" . $c_arr[ $key ] = substr($c, 0, 128);
                        
                    }
                }
                echo "</pre>";
                */
                
                /*
                echo "<pre>";
                var_dump( $c_arr );
                echo "</pre>";
                */
            }
            
            ?>
            
            
        </div>
        
        <div id="tabs-3">
            <?php
            
            if( isset( $_POST["v_bedrag"] ) && !empty( $_POST["v_bedrag"] ) )
            {
                $_POST["bedrag"] = $_POST["v_bedrag"];
            }
            
            if( isset( $_POST["v_bedrag2"] ) && !empty( $_POST["v_bedrag2"] ) )
            {
                $_POST["bedrag2"] = $_POST["v_bedrag2"];
            }
            
            if( isset( $_POST["v_boek_dat"] ) && !empty( $_POST["v_boek_dat"] ) )
            {
                $_POST["datum"] = $_POST["v_boek_dat"];
            }
            
            if( isset( $_POST["v_med"] ) && !empty( $_POST["v_med"] ) )
            {
                $_POST["mededeling"] = $_POST["v_med"];
            }
            
            
            if( isset( $_POST["p_bedrag"] ) && !empty( $_POST["p_bedrag"] ) )
            {
                $_POST["bedrag"] = $_POST["p_bedrag"];
            }
            
            if( isset( $_POST["p_bedrag2"] ) && !empty( $_POST["p_bedrag2"] ) )
            {
                $_POST["bedrag2"] = $_POST["p_bedrag2"];
            }
            
            if( isset( $_POST["p_boek_dat"] ) && !empty( $_POST["p_boek_dat"] ) )
            {
                $_POST["datum"] = $_POST["p_boek_dat"];
            }
            
            if( isset( $_POST["p_med"] ) && !empty( $_POST["p_med"] ) )
            {
                $_POST["mededeling"] = $_POST["p_med"];
            }
            
            
            ?>
        
            <fieldset style="background-color: #FFC;">
            <legend><strong>Zoeken</strong></legend>
            
            <form method="post" name="frm_zoek_coda" id="frm_zoek_coda" action="coda.php" >
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <strong>Bedrag</strong>
                </td>
                <td>
                    <strong>Boek. datum</strong>
                </td>
                <td>
                    <strong>Mededeling</strong>
                </td>
                <td>
                
                </td>
            </tr>
            
            <tr>
                <td>
                    Tussen <input type="text" name="bedrag" id="bedrag" value="<?php if( isset( $_POST["bedrag"] ) ) echo $_POST["bedrag"]; ?>" onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' />
                    en <input type="text" name="bedrag2" id="bedrag2" value="<?php if( isset( $_POST["bedrag2"] ) ) echo $_POST["bedrag2"]; ?>" onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' />
                </td>
                <td>
                    <input type="text" name="datum" id="datum" value="<?php if( isset( $_POST["datum"] ) ) echo $_POST["datum"]; ?>" />
                </td>
                <td>
                    <input type="text" name="mededeling" id="mededeling" value="<?php if( isset( $_POST["mededeling"] ) ) echo $_POST["mededeling"]; ?>" />
                </td>
                <td>
                    <input type="submit" name="zoek" id="zoek" value="Zoek" />
                    
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;
                </td>
                <td >
                    <input type="checkbox" name="search_all" id="search_all" /><label for="search_all" >Ook in afgepunte zoeken.</label>
                    <br />
                    <input type="checkbox" name="order_down" id="order_down" /><label for="search_all" >Sorteren op datum aflopend</label>
                    <br />
                    <input type="checkbox" name="order_up" id="order_up" /><label for="search_all" >Sorteren op datum oplopend</label>
                </td>
            </tr>
            
            
            </table>
            <input type="hidden" name="tab_id" id="tab_id" value="1" />
            </form>
            </fieldset>
            
            <?php
            /*
            echo "<pre>";
            var_dump( $_POST );
            echo "</pre>";
            */
            
            
            
            
            $where = "";
            
            if( !empty( $_POST["bedrag"] ) && !empty( $_POST["bedrag2"] ) )
            {
                $where = " WHERE bedrag BETWEEN '".$_POST["bedrag"]."' AND '". $_POST["bedrag2"] ."' ";
            }else
            {
                if( !empty( $_POST["bedrag"] ) && empty( $_POST["bedrag2"] ) )
                {
                    $where = " WHERE bedrag = '".$_POST["bedrag"]."' ";
                }
                
                if( empty( $_POST["bedrag"] ) && !empty( $_POST["bedrag2"] ) )
                {
                    $where = " WHERE bedrag = '".$_POST["bedrag2"]."' ";
                }
            }
            
            if( !empty( $_POST["datum"] ) )
            {
                if( $where == "" )
                {
                    $where = " WHERE boek_dat = '". changeDate2EU($_POST["datum"])."' ";
                }else
                {
                    $where .= " AND boek_dat = '". changeDate2EU($_POST["datum"])."' ";
                }
                
            }
            
            if( !empty( $_POST["mededeling"] ) )
            {
                if( $where == "" )
                {
                    $where = " WHERE (med1 LIKE '%". $_POST["mededeling"]."%' 
                                  OR med2 LIKE '%". $_POST["mededeling"]."%' 
                                  OR med3 LIKE '%". $_POST["mededeling"]."%' 
                                  OR ref_cl LIKE '%". $_POST["mededeling"]."%'
                                  OR naam LIKE '%". $_POST["mededeling"]."%') ";
                }else
                {
                    $where .= " AND ( med1 LIKE '%". $_POST["mededeling"]."%' 
                                  OR med2 LIKE '%". $_POST["mededeling"]."%' 
                                  OR med3 LIKE '%". $_POST["mededeling"]."%' 
                                  OR ref_cl LIKE '%". $_POST["mededeling"]."%'
                                  OR naam LIKE '%". $_POST["mededeling"]."%' ) ";
                }
            }
            
            if( !isset( $_POST["search_all"] ) )
            {
                if( $where == "" )
                {
                    $where = " WHERE cus_id = 0 AND lev_id = 0 ";
                }else
                {
                    $where .= " AND cus_id = 0 AND lev_id = 0 ";
                }
            }
            
            $order = " ORDER BY bedrag DESC ";
            
            if( isset( $_POST["order_down"] ) )
            {
                $order = " ORDER BY boek_dat DESC ";
            }
            
            if( isset( $_POST["order_up"] ) )
            {
                $order = " ORDER BY boek_dat ASC ";
            }
            
            if( !isset($_POST["start"]) )
            {
                $order .= " LIMIT 0, 10";
                $start = 10;
            }else
            {
                if( is_numeric( $_POST["start"])  )
                {
                    $order .= " LIMIT ". $_POST["start"] .", 10";
                    $start = $_POST["start"] + 10;
                }
            }
            
            $q = "SELECT * FROM kal_coda " . $where . $order;
            $q_zoek = mysqli_query($conn, $q) or die( mysqli_error($conn) );
            
            $tot_coda = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_coda " . $where));
            
            echo "Aantal coda - records gevonden : " . $tot_coda;
            
            if( !isset( $_POST["start"] ) )
            {
                if( $tot_coda > 0 )
                {
                    if( $tot_coda < 10 )
                    {
                        echo "<br>Weergave records 1 - " . $tot_coda;
                    }else
                    {
                        echo "<br>Weergave records 1 - 10";    
                    }
                }
            }else
            {
                if( is_numeric( $_POST["start"]) )
                {
                    $startrec = $start;
                    
                    if( $startrec > $tot_coda )
                    {
                        $startrec = $tot_coda;
                    }
                    
                    echo "<br>Weergave records " . ($_POST["start"]+1) . " - " . $startrec;
                }
            }
            
            echo "<table width='100%' >";
            echo "<tr>";
            echo "<td>"; 
            
            if( $start > 10 )
            {
                $startmin = $start - 20;
                //echo "<a href='coda.php?tab_id=2&start=". $startmin ."'><strong> <== Vorige </strong></a>";
                
                echo "<form method='post' name='frm_vorige' id='frm_vorige' >";
                echo "<input type='submit' name='vorige' value='vorige' value='Vorige' />";
                echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
                echo "<input type='hidden' name='start' id='start' value='". $startmin ."' />";
                
                $tmp_bedrag = 0;
                if( isset( $_POST["bedrag"] ) )
                {
                    $tmp_bedrag = $_POST["bedrag"];
                }
                
                $tmp_bedrag2 = 0;
                if( isset( $_POST["bedrag2"] ) )
                {
                    $tmp_bedrag2 = $_POST["bedrag2"];
                }
                
                $tmp_datum = "";
                if( isset( $_POST["datum"] ) )
                {
                    $tmp_datum = $_POST["datum"];
                }
                
                $tmp_mededeling = "";
                if( isset( $_POST["mededeling"] ) )
                {
                    $tmp_mededeling = $_POST["mededeling"];
                }
                
                echo "<input type='hidden' name='p_bedrag' id='p_bedrag' value='". $tmp_bedrag ."' />";
                echo "<input type='hidden' name='p_bedrag2' id='p_bedrag2' value='". $tmp_bedrag2 ."' />";
                echo "<input type='hidden' name='p_boek_dat' id='p_boek_dat' value='". $tmp_datum ."' />";
                echo "<input type='hidden' name='p_med' id='p_med' value='". $tmp_mededeling ."' />";
                
                if( isset( $_POST["search_all"] ) )
                {
                    echo "<input type='hidden' name='search_all' id='search_all' value='1' />";
                }
                
                if( isset( $_POST["order_down"] ) )
                {
                    echo "<input type='hidden' name='order_down' id='order_down' value='1' />";
                }
                
                if( isset( $_POST["order_up"] ) )
                {
                    echo "<input type='hidden' name='order_up' id='order_up' value='1' />";
                }
                
                echo "</form>";
            }
            
            echo "</td>";
            echo "<td align='right'>";
            
            if( $tot_coda >= $start )
            {
                $tmp_bedrag = 0;
                if( isset( $_POST["bedrag"] ) )
                {
                    $tmp_bedrag = $_POST["bedrag"];
                }
                
                $tmp_bedrag2 = 0;
                if( isset( $_POST["bedrag2"] ) )
                {
                    $tmp_bedrag2 = $_POST["bedrag2"];
                }
                
                $tmp_datum = "";
                if( isset( $_POST["datum"] ) )
                {
                    $tmp_datum = $_POST["datum"];
                }
                
                $tmp_mededeling = "";
                if( isset( $_POST["mededeling"] ) )
                {
                    $tmp_mededeling = $_POST["mededeling"];
                }
                
                echo "<form method='post' name='frm_volgende' id='frm_volgende' >";
                echo "<input type='submit' name='volgende' value='volgende' value='Volgende ==> ' />";
                echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
                echo "<input type='hidden' name='start' id='start' value='". $start ."' />";
                
                echo "<input type='hidden' name='v_bedrag' id='v_bedrag' value='". $tmp_bedrag ."' />";
                echo "<input type='hidden' name='v_bedrag2' id='v_bedrag2' value='". $tmp_bedrag2 ."' />";
                echo "<input type='hidden' name='v_boek_dat' id='v_boek_dat' value='". $tmp_datum ."' />";
                echo "<input type='hidden' name='v_med' id='v_med' value='". $tmp_mededeling ."' />";
                
                if( isset( $_POST["search_all"] ) )
                {
                    echo "<input type='hidden' name='search_all' id='search_all' value='1' />";
                }
                
                if( isset( $_POST["order_down"] ) )
                {
                    echo "<input type='hidden' name='order_down' id='order_down' value='1' />";
                }
                
                if( isset( $_POST["order_up"] ) )
                {
                    echo "<input type='hidden' name='order_up' id='order_up' value='1' />";
                }
                
                echo "</form>";
            }
            
            echo "</td>"; 
            echo "</tr>";
            echo "</table>";
            echo "<br/>";
            /*
            echo "Aantal rijen : " . mysqli_num_rows($q_zoek);
            echo "<br/><br/>";
            */
            //echo "<br>" . $q;
            
            /*
            echo "<pre>";
            var_dump( $_POST );
            echo "</pre>";
            */
            
            $klanten_arr = array();
            $q_klant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = '0' AND cus_active = '1' ORDER BY cus_naam, cus_bedrijf");
            while( $klant = mysqli_fetch_object($q_klant) )
            {
                if( !empty($klant->cus_naam) || !empty( $klant->cus_bedrijf ) )
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
                    
                    $klanten_arr[ $klant->cus_id ] = $naam;
                }
            }
            
            $lev_arr = array();
            $q_lev = mysqli_query($conn, "SELECT * FROM kal_leveranciers ORDER BY naam");
            while( $rij = mysqli_fetch_object($q_lev) )
            {
                $lev_arr[ $rij->id ] = $rij->naam;
            }
            
            echo "<div id='summary' style='padding:3px;' ></div><br/>";
            
            while( $rij = mysqli_fetch_object($q_zoek) )
            {
                echo "<div id='tabel_".$rij->id."' style='display:block;'>";
                echo "<table cellpadding='2' cellspacing='0' border='1' width='100%' >";
                echo "<tr style='background-color:black;color:white;' ><td width='33%'>";
                
                if( !empty( $rij->naam ) )
                {
                    echo "<strong>Naam :</strong> " . $rij->naam;    
                }
                
                echo "</td><td width='33%' align='center'><strong>Boekingsdatum :</strong> ". changeDate2EU($rij->boek_dat)."</td><td width='34%' align='right'>";
                
                echo "<strong>Bedrag :</strong> ". number_format($rij->bedrag, 2, ",", " " );
                
                echo "</td></tr>";
                echo "<tr><td colspan='3' style='background-color:#F8F8F8;'>";
                
                
                if( !empty( $rij->med1 ) )
                {
                    echo $rij->med1 . "<br>";    
                }
                
                if( !empty( $rij->med2 ) )
                {
                    echo $rij->med2 . "<br>";    
                }
                
                if( !empty( $rij->med3 ) )
                {
                    echo str_replace("\n", "<br>", $rij->med3) . "<br>";    
                }
                
                if( !empty( $rij->ref_cl ) )
                {
                    echo "Ref. Cl. : " . $rij->ref_cl . "<br>";    
                }
                
                echo "</td></tr>";
                echo "<tr><td colspan='3' style='background-color:darkgray;color:white;' ><b>Bestandsnaam : </b>". $rij->filename ."</td></tr>";
                
                
                if( $_SESSION[ $session_var ]->group_id != 8 )
                {
                    echo "<tr>";
                    if( $rij->cus_id == 0 )
                    {
                        echo "<td colspan='3' style='background-color:darkgray;color:white;' >Koppelen aan klant : ";
                        
                        echo "<select name='sel_".$rij->id."' id='sel_".$rij->id."'>";
                        
                        $stijl = "";
                        foreach( $klanten_arr as $cus_id => $cus_naam )
                        {
                            if( !empty($rij->naam) && (stristr( strtolower($rij->naam), strtolower($cus_naam) ) || stristr( strtolower($cus_naam), strtolower($rij->naam) )) )
                            {
                                $stijl = " style='background-color:green;' ";
                                echo "<option style='color:green;' selected='selected' value='". $cus_id ."'>". $cus_naam ."</option>";
                            }else
                            {
                                echo "<option value='". $cus_id ."'>". $cus_naam ."</option>";    
                            }
                        }
                        
                        echo "</select>";
                        
                        echo "<input ". $stijl ." type='button' name='koppel_". $rij->id ."' id='koppel_". $rij->id ."' value='Koppel' onclick='koppelAanKlant(". $rij->id .")' />";
                        echo "</td>";
                    }else
                    {
                        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $rij->cus_id));
                        echo "<td colspan='3' align='right' style='background-color:green;color:white;' ><b>Gekoppeld aan klant : </b>". $klant->cus_naam;
                        echo "&nbsp;&nbsp;<img src='images/delete.png' onclick='OntkoppelKlant(". $rij->id .")' alt='Koppeling verwijderen?' title='Koppeling verwijderen?' />";
                        echo "</td>";
                    }
                    echo "</tr>";
                    
                    echo "<tr>";
                    if( $rij->lev_id == 0 )
                    {
                        echo "<td colspan='3' style='background-color:darkgray;color:white;' >Koppelen aan leverancier : ";
                        
                        // zoeken ofdat er koppeling kan gevonden worden met de ingegeven betalingen.
                        $reknrlevid = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND reknr LIKE '%". $rij->reknr ."' LIMIT 1"));
                        
                        //echo $rij->reknr;
                        
                        echo "<select name='lev_".$rij->id."' id='lev_".$rij->id."'>";
                        
                        $stijl = "";
                        
                        foreach( $lev_arr as $id => $name )
                        {
                            $sel = "";
                            
                            if( $reknrlevid->klant_id == $id && $rij->bedrag < 0 )
                            {
                                $stijl = " style='background-color:green;' ";
                                $sel = " selected='selected' ";
                            }
                            
                            echo "<option ". $sel ." value='". $id ."'>". $name ."</option>";    
                        }
                        
                        echo "</select>";
                        echo "<input ". $stijl ." type='button' name='koppell_". $rij->id ."' id='koppell_". $rij->id ."' value='Koppel leverancier' onclick='koppelAanLev(". $rij->id .")' />";
                    }else
                    {
                        $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
                        
                        echo "<td colspan='3' align='right' style='background-color:green;color:white;' ><b>Gekoppeld aan leverancier : ". $lev->naam ."</b>";
                        echo "&nbsp;&nbsp;<img src='images/delete.png' onclick='OntkoppelLev(". $rij->id .")' alt='Koppeling verwijderen?' title='Koppeling verwijderen?' />";
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table><br/>";    
                echo "</div>";
            }
            ?>
            
        </div>
       
        <div id="Afgepunte_klanten"></div>
        <div id="Afgepunte_leveranciers"></div>
        
        <div id="tabs-5">
        
        <?php
        if( $_SESSION[ $session_var ]->group_id != 8 )
        {
        
        ?>
        <form method="post" name="frm_auto_koppel" id="frm_auto_koppel">
        <input type="hidden" name="tab_id" id="tab_id" value="7" />
        <input type="submit" name="Koppel" id="Koppel" value="Koppel" />
        </form>
        <?php
        
        }
        
        if(isset($_POST["Koppel"]) && $_POST["Koppel"] == "Koppel" )
        {
            $q_klant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = 0");

            $i = 0;
            while( $klant = mysqli_fetch_object($q_klant) )
            {
                // zoeken of er facturen zijn voor deze klant
                $q_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_soort_id = " . $klant->cus_id);
                $q_coda = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id = " . $klant->cus_id);
                
                if( mysqli_num_rows($q_fac) > 0 && mysqli_num_rows($q_coda) > 0 )
                {
                    $i++;
                    
                    echo "<br>(" . maakReferte($klant->cus_id, $conn) . ") " . $klant->cus_naam . "<br>";
                    
                    echo "<iframe src='http://192.168.1.50/kalender/klanten_coda.php?klant_id=". $klant->cus_id ."' width='1200' height='300' ></iframe><br>";
                }
                
                flush();
            }
        }
        
        
        ?> 
        
        
        </div>
        
        <div id="Nog tegoed"></div>
    </div>
</div>


<center><?php 

include "inc/footer.php";

?></center>

</body>
</html>