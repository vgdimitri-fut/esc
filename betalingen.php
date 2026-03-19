<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

$user_logged_in = $_SESSION[ $session_var ]->user_id;

if( isset( $_POST["rec_id"] ) && isset( $_POST["delete_contactpersoon"] ) )
{
    $q_del = "UPDATE kal_betalingen SET check_by = '0', approved = '2', by_date = '0000-00-00' WHERE id = " . $_POST["rec_id"];
    mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
    
    $bet = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_POST["rec_id"]));
    $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $bet->lev_id));
    
    echo "<select name='check". $_POST["rec_id"] ."' id='check". $_POST["rec_id"] ."' style='width:100px;' >";
    echo "<option value='0'>&nbsp;</option>";
    
    $q_users = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' AND user_id IN(2008, 2012, 2013, 2016, 2000) ORDER BY naam, voornaam");
    
    while( $u = mysqli_fetch_object($q_users) )
    {
        $sel = "";
        
        if( $u->user_id == $lev->user_id )
        {
            $sel = " selected='selected' ";
        }
        
        echo "<option ". $sel ." value='". $u->user_id ."'>". $u->naam . " " . $u->voornaam ."</option>";
    }
    
    echo "</select>";
    echo "<input type='button' value='Verstuur' class='ajax_controle_persoon_toegekennen' rec_id='". $_POST["rec_id"] ."' name='controle_".$_POST["rec_id"]."' />";
    
    die();
}

if( isset( $_POST["aanpassen"] ) && $_POST["aanpassen"] == "Aanpassen" )
{
    if( !empty( $_FILES["nw_fac"]["name"] ) )
    {
        $laatste_id = $_POST["bet_id"];
        
        chdir( "betalingen/");
        $file = $laatste_id . "." . getExtFromFile($_FILES['nw_fac']['name']);
        move_uploaded_file( $_FILES['nw_fac']['tmp_name'], $file );
		chdir("../");
        
        $q_upd = "UPDATE kal_betalingen SET scan = '" . $file . "' WHERE id = " . $laatste_id;
        mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
    }
}

if( isset( $_GET["brief_id"] ) && $_GET["brief_id"] > 0 && isset( $_GET["int_id"] ) )
{
    $cf = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $_GET["brief_id"]));
    
    if( $cf )
    {
        // nakijken of de file bestaat
        if( file_exists("lev_docs/" . $cf->cus_id . "/protest/Protest_factuur_" . $_GET["int_id"] ) );
        {
            // unlink
            unlink("lev_docs/" . $cf->cus_id . "/protest/Protest_factuur_" . $_GET["int_id"]);
            
            // rem record
            $q_del = "DELETE FROM kal_customers_files WHERE cf_id = " . $_GET["brief_id"] . " LIMIT 1";
            mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
        }
    }
}

if( isset( $_POST["opslaan_protest"] ) && $_POST["opslaan_protest"] == "Opslaan" )
{
    $q_upd = "UPDATE kal_betalingen SET reden = '" . htmlentities( $_POST["reden"] , ENT_QUOTES) . "', approved = '0' WHERE id = " . $_POST["bet_id"];
    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );

    $betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_POST["bet_id"]));
    
    
    ?>
    <script type="text/javascript">
        
            //alert("test");
            //$("#various5").fancybox().trigger('click');
        
    </script>
    <?php
}

function RemoveAccents($string) 
{
    // From http://theserverpages.com/php/manual/en/function.str-replace.php
    $string = htmlentities($string);
    return preg_replace("/&([a-z])[a-z]+;/i", "$1", $string);
}

function checkwoord($woord)
{
    $woord = RemoveAccents($woord);
    
    $allowed_chars = array();
    
    // A-Z a-z 0-9
    foreach(range('A','Z') as $i) $allowed_chars[$i] = $i;
    foreach(range('a','z') as $i) $allowed_chars[$i] = $i;
    foreach(range('0','9') as $i) $allowed_chars["$i"] = "$i";
    
    $allowed_chars[" "] = " ";
    $allowed_chars["/"] = "/";
    $allowed_chars["-"] = "-";
    $allowed_chars["?"] = "?";
    $allowed_chars[":"] = ":";
    $allowed_chars["("] = "(";
    $allowed_chars[")"] = ")";
    $allowed_chars["."] = ".";
    $allowed_chars["'"] = "'";
    $allowed_chars["+"] = "+";
    
    $ret_woord = "";
    
    for($i=0;$i<strlen($woord);$i++)
    {
        //echo $woord[$i] . " ";
        if( in_array($woord[$i], $allowed_chars, true) )
        {
            $ret_woord .= $woord[$i];            
        }
    }
    
    return $ret_woord;
}

if( isset( $_POST["export"] ) && $_POST["export"] == "Export" )
{
    // ophalen van de betalingen
    if( count( $_POST["chk_bet"] ) > 0 )
    {
        
        $midden = "";
        
        $verwerk_arr = array();
        
        foreach( $_POST["chk_bet"] as $bet_id )
        {
            $verwerk_arr[ $bet_id ] = $bet_id;
            
            $betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $bet_id));
            $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $betaling->lev_id));
            
            $bedrag = $betaling->bedrag_incl;
            
            if( $betaling->reknr_id == 0 )
            {
                $rek = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND klant_id = " . $betaling->lev_id ." LIMIT 1"));
            }else
            {
                $rek = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND id = " . $betaling->reknr_id ." LIMIT 1"));    
            }
            
            if( !$rek )
            {
                die("Geen rek.nr. gevonden : " . $betaling->scan . " " . $lev->naam);
            }
            
            // controle van het reknr.
            if( is_numeric($rek->reknr[0]) )
            {
                $xml = file_get_contents("http://www.ibanbic.be/IBANBIC.asmx/BBANtoIBAN?Value=" . str_replace(" ", "", $rek->reknr) );
                
                $tmp_bic = str_replace( " ", "", strip_tags($xml) );
                $tmp_bic = str_replace( "\r\n", "", $tmp_bic );
                
                if( !empty( $tmp_bic ) )
                {
                    $q_upd = "UPDATE kal_reknr SET reknr = '" . $tmp_bic . "' WHERE id = " . $rek->id;
                    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
                    $rek = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE id = " . $rek->id));
                }else
                {
                    die("A.IBAN is leeg" . $rek->id . " " . $rek->reknr . " <b>Leverancier : " . $lev->naam . "</b>");
                }
            }
            
            if( empty( $rek->bic ) )
            {
                $xml = file_get_contents("http://www.ibanbic.be/IBANBIC.asmx/BBANtoBIC?Value=" . str_replace(" ", "", $rek->reknr));
                
                $tmp_bic = str_replace( " ", "", strip_tags($xml) );
                $tmp_bic = str_replace( "\r\n", "", $tmp_bic );
                
                if( empty( $tmp_bic ) )
                {
                    die("B.BIC IS LEEG" . $rek->id . " " . $rek->reknr . " <b>Leverancier : " . $lev->naam. "</b>");    
                }else
                {
                    $q_upd = "UPDATE kal_reknr SET bic = '" . $tmp_bic . "' WHERE id = " . $rek->id;
                    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
                    
                    $rek = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE id = " . $rek->id));
                }
            }
            
            $myFile = "xml/bet_pmtinf.txt";
            $fh = fopen($myFile, 'r');
            $start = fread($fh, filesize($myFile));
            fclose($fh);
            
            $reqdate = date("Y-m-d", mktime(0,0,0,date('m'),date('d'),date('Y')) );
            
            $mededeling = "";
            
            if( !empty( $betaling->vrije_mededeling ) )
            {
                $vrijemed = $betaling->vrije_mededeling;
                
                if( isset( $_SESSION["betalingen"][ $betaling->id ] ) )
                {
                    if( count( $_SESSION["betalingen"][ $betaling->id ] ) > 0 )
                    {
                        foreach( $_SESSION["betalingen"][ $betaling->id ] as $cn_id )
                        {
                            $cn = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $cn_id));
                            $vrijemed .= " en " . $cn->vrije_mededeling;
                            $bedrag += $cn->bedrag_incl;
                            
                            $verwerk_arr[ $cn_id ] = $cn_id;
                        }
                    }
                }
                
                $mededeling = "<Ustrd>". checkwoord($vrijemed) ."</Ustrd>";
            }else
            {
                if( isset( $_SESSION["betalingen"][ $betaling->id ] ) && count( $_SESSION["betalingen"][ $betaling->id ] ) > 0 )
                {
                    $vrijemed = $betaling->ogm;
                    
                    foreach( $_SESSION["betalingen"][ $betaling->id ] as $cn_id )
                    {
                        $cn = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $cn_id));
                        $vrijemed .= " en " . $cn->vrije_mededeling;
                        $bedrag += $cn->bedrag_incl;
                        
                        $verwerk_arr[ $cn_id ] = $cn_id;
                    }
                    
                    $mededeling = "<Ustrd>". checkwoord($vrijemed) ."</Ustrd>";
                }else
                {
                    if( !empty( $betaling->ogm ) )
                    {
                        $mededeling = "<Strd>";
                        $mededeling .= "<CdtrRefInf>";
                        $mededeling .= "<CdtrRefTp>";
                        $mededeling .= "<Cd>SCOR</Cd>";
                        $mededeling .= "<Issr>BBA</Issr>";
                        $mededeling .= "</CdtrRefTp>";
                        $mededeling .= "<CdtrRef>". str_replace("-", "", checkwoord($betaling->ogm) ) ."</CdtrRef>";
                        $mededeling .= "</CdtrRefInf>";
                        $mededeling .= "</Strd>";
                    }
                }
            }
            
            $bank_futech = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_bank WHERE id = " . $betaling->bank_id));
            $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
            $var_arr = array( "%%REQDATE%%",
                              "%%ENDTOEND%%",
                              "%%BEDRAG_INCL%%",
                              "%%BIC%%",
                              "%%CRED_NAME%%",
                              "%%CRED_IBAN%%",
                              "%%FUT_IBAN%%",
                              "%%FUT_BIC%%",
                              "%%BEDRIJF%%",
                              "%%MEDEDELING%%" );
            
            
            $naar_arr = array( $reqdate,
                               $bet_id . "/" . $reqdate,
                               $bedrag,
                               strtoupper($rek->bic),
                               substr(checkwoord($lev->naam),0,34),
                               strtoupper( str_replace(" ", "", $rek->reknr) ),
                               $bank_futech->iban,
                               str_replace(" ", "", $bank_futech->bic),
                               $instellingen->bedrijf_naam,
                               $mededeling );
            
            $midden .= str_replace( $var_arr, $naar_arr, $start );
            
            if( isset( $_POST["verwerk"] ) )
            {
                foreach( $verwerk_arr as $v )
                {
                    $q_upd = "UPDATE kal_betalingen SET exported = '1', exported_dt = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $v;
                    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . $q_upd );
                } 
            }
        }
        
        
        $sepa_xml = "";
    
        $myFile = "xml/bet_start.txt";
        $fh = fopen($myFile, 'r');
        $start = fread($fh, filesize($myFile));
        fclose($fh);
        
        $var_arr = array( "%%MSGID%%",
                          "%%DATETIME%%",
                          "%%BEDRIJF%%",
                          "%%BTW%%",
                          "%%NRTRANSACTIONS%%" );
                          
        $btw_input = $instellingen->bedrijf_btw;
        if(substr($btw_input,0,2) == 'BE'){
            $btw = substr($btw_input,2);
        }
        $naar_arr = array( "xml".date("dmY"),
                           $creationDate = substr( date('c', time() ), 0, 19 ),
                           $instellingen->bedrijf_naam,
                           str_replace(' ','',$btw),
                           count( $_POST["chk_bet"]) );
        
        $start = str_replace( $var_arr, $naar_arr, $start );
    
        $sepa_xml = $start;
        
        $sepa_xml .= $midden;
        
        $myFile = "xml/bet_einde.txt";
        $fh = fopen($myFile, 'r');
        $einde = fread($fh, filesize($myFile));
        fclose($fh);
        // EINDE EINDE
        
        $sepa_xml .= $einde;
        
        if( isset( $_POST["verwerk"] ) )
        {
            chdir( "sepa/" );
            chdir( "betalingen/" );
        	$fp1 = fopen( "betalingen-xml-". date('d-m-Y') . "_" . date('H-i-s') .".xml" , 'w');
        	fwrite($fp1, $sepa_xml);
        	fclose($fp1);
        	chdir("../../");
        }
        
        header('Content-Type: application/x-download');
    	header('Content-Disposition: attachment; filename="betalingen-xml-'. date('d-m-Y') .'.xml"');
    	header('Cache-Control: private, max-age=0, must-revalidate');
    	header('Pragma: public');
    	echo $sepa_xml;
        exit();
    }
}

// toekennen van de persoon die moet controlleren + deze persoon mailen
if( isset( $_POST ) )
{
    foreach( $_POST as $index => $post )
    {
        if( substr($index,0,9) == "controle_" )
        {
            $q_upd = "UPDATE kal_betalingen SET check_by = " . $_POST["check" . substr($index,9)] . " WHERE id = " . substr($index,9);
            mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . $q_upd );
            
            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST["check" . substr($index,9)]));
            mail($user->email,"Controleer uitgaande betaling", "via de volgende link : https://www.solarlogs.be/esc/betalingen.php?tab_id=2", $headers);
        }
    }
}

// ingevoerde betalingen die reeds betaald zijn
if( isset( $_POST ) )
{
    foreach( $_POST as $index => $post )
    {
        if( substr($index,0,4) == "pay_" )
        {
            $q_upd = "UPDATE kal_betalingen SET betaald = '" . $_POST["betaald_via_" . substr($index,4)] . "', exported = '1', exported_dt = '" . date('Y-m-d H:i:s') . "' WHERE id = " . substr($index,4);
            mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
        }
    }
}


if( isset( $_POST["actie"] ) )
{
    switch( $_POST["actie"] )
    {
        case "ok" :
            $approve = 1;
            break;
        case "nok" :
            $approve = 0;
            break;
    }
    
    $q_upd = "UPDATE kal_betalingen SET approved = '" . $approve . "' WHERE id = " . $_POST["bet_id"];
    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . $q_upd );
}

/*
$_POST["sel_lev"]
$_POST["bedrag_incl"]
$_POST["bedrag_btw"]
$_POST["sel_btw"]
$_POST["fac_nummer"]
$_POST["fac_datum"]
$_POST["interne_nr"]
$_POST["vrije_mededeling"]
$_POST["ogm"]
$_POST["beschrijving"]
*/

// Verwijderen van een betaling
if( isset( $_GET["actie"] ) && $_GET["actie"] == 'delete' )
{
    $q_del = "DELETE FROM kal_betalingen WHERE id = " . $_GET["id"];
    mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
    
    header("Location: betalingen.php?tab_id=1");
}

// Opslaan van een betaling
if( isset( $_POST["opslaan"] ) && $_POST["opslaan"] == 'Save' )
{
    $q_sel = "SELECT * 
                FROM kal_betalingen 
               WHERE lev_id = " . $_POST["sel_lev"] . " 
                 AND bedrag_incl = '". $_POST["bedrag_incl"] ."'
                 AND bedrag_btw = '". $_POST["bedrag_btw"] ."'
                 AND btw = '". $_POST["sel_btw"] ."'
                 AND fac_nr = '" . $_POST["fac_nummer"] . "'
                 AND fac_datum = '". changeDate2EU($_POST["fac_datum"]) ."' ";
    
    $aant = mysqli_num_rows(mysqli_query($conn, $q_sel));
    
    if( $aant == 0 )
    {
        $q_ins = "INSERT INTO kal_betalingen(lev_id,
                                             reknr_id,
                                             bedrag_incl,
                                             bedrag_btw,
                                             btw,
                                             fac_nr,
                                             fac_datum,
                                             nr_intern,
                                             vrije_mededeling,
                                             ogm,
                                             bank_id,
                                             beschrijving) 
                                     VALUES(". $_POST["sel_lev"] .",
                                            '". $_POST["sel_reknr"] ."',
                                            '". $_POST["bedrag_incl"] ."',
                                            '". $_POST["bedrag_btw"] ."',
                                            '". $_POST["sel_btw"] ."',
                                            '". $_POST["fac_nummer"] ."',
                                            '". changeDate2EU($_POST["fac_datum"]) ."',
                                            '". $_POST["interne_nr"] ."',
                                            '". $_POST["vrije_mededeling"] ."',
                                            '". $_POST["ogm"] ."',
                                            ". $_POST["sel_bank"] .",
                                            '". $_POST["beschrijving"] ."')";
        //echo $q_ins;                                            
        mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
        $laatste_id = mysqli_insert_id($conn);
        
        if( !empty( $_FILES['scan']['name'] ) )
        {
            chdir( "betalingen/");
            $file = $laatste_id . "." . getExtFromFile($_FILES['scan']['name']);
            move_uploaded_file( $_FILES['scan']['tmp_name'], $file );
    		chdir("../");
            
            $q_upd = "UPDATE kal_betalingen SET scan = '" . $file . "' WHERE id = " . $laatste_id;
            mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
        }
    }else
    {
        echo "<div class='error'>Deze betaling staat al in het systeem.</div>";
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>Payments<?php include "inc/erp_titel.php" ?></title>

<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="https://www.solarlogs.be/kalender/js/jquery.validate.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<script type="text/javascript" 	src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript" src="js/googleanalytics.js"></script>

<script type="text/javascript">


function opteller(dit,bedrag)
{
    /*
    if( document.getElementById( dit.id ).checked == false )
    {
        document.getElementById( dit.id ).checked = true;
    }
    */
    var tot = document.getElementById("id_teller").value;
    
    if( document.getElementById( dit.id ).checked == true )
    {
        tot = parseFloat(tot) + parseFloat(bedrag);
    }else
    {
        tot = parseFloat(tot) - parseFloat(bedrag);
    }
    
    document.getElementById("id_teller").value = Math.round(parseFloat(tot) * 100) / 100 ;
}

function popup(bet_id)
{
    if( document.getElementById( "check_" + bet_id ).checked == true )
    {
        window.open('klanten/betalingen_neg.php?id=' + bet_id,'Popup_cn_futech','toolbar=no,location=yes,status=no,menubar=no,scrollbars=no,resizable=no,width=520,height=400,left=430,top=23');
    }
}

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



function checkogm(dit)
{
    if( dit.value != '' )
    {
    	datasource = "ajax/checkogm.php?ogm=" + dit.value;
        var obj = document.getElementById("id_checkogm");
    
    	if(XMLHttpRequestObject1){
    		XMLHttpRequestObject1.open("GET",datasource,true);
    		XMLHttpRequestObject1.onreadystatechange = function(){
    			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200){
    				obj.innerHTML = XMLHttpRequestObject1.responseText;
    			}else
                {
                    obj.innerHTML = "<img src='images/indicator.gif' />Rek.nrs worden opgehaald.";
                }
        	}
    		
    		XMLHttpRequestObject1.send(null);
    	}
     }
}

function getReknr(lev_id)
{
	datasource = "ajax/bet_reknr.php?lev_id=" + lev_id;
    var obj = document.getElementById("id_reknr");

	if(XMLHttpRequestObject1){
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200){
				obj.innerHTML = XMLHttpRequestObject1.responseText;
			}else
            {
                obj.innerHTML = "<img src='images/indicator.gif' />Rek.nrs worden opgehaald.";
            }
    	}
		
		XMLHttpRequestObject1.send(null);
	}
}

function selectAlles(FieldName, dit, formulier)
{
    document.getElementById("id_teller").value = 0;
    
    if(dit.checked == true)
    {
       document.getElementById("id_teller").value = document.getElementById("tot_vink").value;  
    }
    
    
	var CheckValue = dit.checked;

	var objCheckBoxes = document.forms[formulier].elements[FieldName];
	if(!objCheckBoxes)
		return;
	var countCheckBoxes = objCheckBoxes.length;
	if(!countCheckBoxes)
		objCheckBoxes.checked = CheckValue;
	else
		// set the check value for all check boxes
		for(var i = 0; i < countCheckBoxes; i++)
			objCheckBoxes[i].checked = CheckValue;
}

function isNumberKey(evt)
{
   var charCode = (evt.which) ? evt.which : event.keyCode;
   
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
});

$(function() {
	$( "#fac_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
});

$(document).ready(function(){
    //$("#frm_bet").validate();
    
    
    
    $('.delete_contactpersoon').live('click',function(){
        var rec_id = $(this).attr("rec_id");
        
        $.ajax({
            data: {rec_id: rec_id, delete_contactpersoon:"delete_contactpersoon"},
            type: 'POST',
            success: function(data) {
                //$('#id_showpdf').html(data);
                $('#controlelijst_' + rec_id).html(data);
                
            }
        })
    });
    
    $("#frm_bet").validate({
        rules: {
            vrije_mededeling: {
                required: function(element) {
                    if ($("#ogm").val().length > 0) {
                        return false;
                    }
                    else {
                        return true;
                    }
                }
            },
            ogm: {
                required: function(element) {
                    if ($("#vrije_mededeling").val().length > 0) {
                        return false;
                    }
                    else {
                        return true;
                    }
    
                }
            }
            /*
            ,
            sel_lev:{
                required: function(element) {
                    //alert( $("#sel_lev").val() );
                    
                    if ($("#sel_lev").val() != 0) {
                        return false;
                    }
                    else {
                        return true;
                    }
                }
            }
            */
        }
    });
});

jQuery(document).ready(function() {
	$(".various5").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'inline'
	});

    $(".various6").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'inline'
	});
});

</script>

</head>
<body>

<div id='pagewrapper'><?php include('inc/header.php'); ?>
	
	<h1>Payments</h1>

	<div id="tabs" style="width: 1200px;">
		<ul>
            <?php
            if( $_SESSION[ $session_var ]->group_id != 8 )
            {
            ?>
			<li><a href="#tabs-1">New payment</a></li>
            <?php
            }
            ?>
            <li><a href="#tabs-2">To pay</a></li>
            <?php
            if( $_SESSION[ $session_var ]->group_id != 8 )
            {
            ?>
            <li><a href="#tabs-3">Check</a></li>
            <li><a href="#tabs-4">Protested bills</a></li>
            <li><a href="#tabs-5">Paid invoices</a></li>
            <?php
            }
            ?>
		</ul>
		
        <?php
        if( $_SESSION[ $session_var ]->group_id != 8 )
        {
        ?>
		<div id="tabs-1">
            
            <form method="post" name="frm_bet" id="frm_bet" enctype='multipart/form-data'>
            <table>
                <tr>
                    <td>Supplier :</td>
                    <td>
                        <select name="sel_lev" class='required' id="sel_lev" onchange="getReknr(this.value);" >
                        <option value=''> == Make your choice == </option>
                            <?php
                            
                            $q_lev = mysqli_query($conn, "SELECT * FROM kal_leveranciers ORDER BY naam");
                            
                            while( $lev = mysqli_fetch_object($q_lev) )
                            {
                                $stijl = "";
                                
                                $aantal_reknr = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND klant_id = " . $lev->id));
                                
                                if( $aantal_reknr > 0 )
                                {
                                    $stijl = " style='color:green;' ";
                                }
                                
                                echo "<option ". $stijl ." value='". $lev->id ."'>".$lev->naam."</option>";
                            }
                            
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2">
                        <div id="id_reknr"> </div>                    
                    </td>
                </tr>
                
                <tr>
                    <td>Amount incl. :</td>
                    <td>
                        <input type="text" class='required' name="bedrag_incl" id="bedrag_incl" onkeyup="commadot(this)" onkeypress='return isNumberKey(event);' />
                    </td>
                </tr>
                
                <tr>
                    <td>VAT amount :</td>
                    <td>
                        <input type="text" class='required' name="bedrag_btw" id="bedrag_btw" onkeyup="commadot(this)" onkeypress='return isNumberKey(event);' />
                    </td>
                </tr>
                
                <tr>
                    <td>VAT %:</td>
                    <td>
                        <select name="sel_btw" id="sel_btw">
                            <option value="0">0</option>
                            <option value="6">6</option>
                            <option value="12">12</option>
                            <option value="21">21</option>
                        </select> 
                    </td>
                </tr>
                
                <tr>
                    <?php
                    $q_naam_bedrijf = mysqli_query($conn, "SELECT bedrijf_naam FROM kal_instellingen");
                    $banknaam = mysqli_fetch_array($q_naam_bedrijf);
                    ?>
                    <td>From <?php echo $banknaam["bedrijf_naam"] ?> bank :</td>
                    <td>
                        <select name="sel_bank" id="sel_bank">
                        
                        <?php
                        
                        $q_bank = mysqli_query($conn, "SELECT * FROM kal_bank");
                        
                        while( $bank = mysqli_fetch_object($q_bank) )
                        {
                            $sel = "";
                            
                            if( $bank->id == 2 )
                            {
                                $sel = " selected='selected' ";
                            }
                            
                            echo "<option ". $sel ." value='".$bank->id."' >". $bank->bank_naam ." (". $bank->iban .")</option>";
                        }
                        
                        ?>
                        
                            
                        </select> 
                    </td>
                </tr>
                
                <tr>
                    <td>Bill Number :</td>
                    <td>
                         <input type="text" class='required' name="fac_nummer" id="fac_nummer" />
                    </td>
                </tr>
                
                <tr>
                    <td>Bill Date :</td>
                    <td>
                         <input type="text" class='required' name="fac_datum" id="fac_datum" />
                    </td>
                </tr>
                
                <?php
                
                $q = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen ORDER BY 1 DESC LIMIT 1"));
                
                if( !$q )
                {
                    $id = 1;
                }else{
                    $id = $q->nr_intern + 1;
                }
                
                
                ?>
                
                <tr>
                    <td>Internal nr :</td>
                    <td>
                         <input type="text" class='required' name="interne_nr" id="interne_nr" value="<?php echo $id; ?>" />
                    </td>
                </tr>
                
                <tr>
                    <td>Scan bill :</td>
                    <td>
                         <input type="file" name="scan" id="scan" />
                    </td>
                </tr>
                
                <tr>
                    <td valign="top">Free communication :</td>
                    <td>
                         <textarea name="vrije_mededeling" id="vrije_mededeling" ></textarea>
                    </td>
                </tr>
                
                <tr>
                    <td>Structured communication :</td>
                    <td>
                         <input type="text" name="ogm" id="ogm" onblur="checkogm(this);" /><span id="id_checkogm"></span>
                    </td>
                </tr>
                
                <tr>
                    <td valign="top">Description : </td>
                    <td>
                         <textarea class='required' name="beschrijving" id="beschrijving" ></textarea>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2">
                        <input type="submit" name="opslaan" id="opslaan" value="Save" />
                    </td>
                </tr>
                
            </table>
            </form>
            
        </div>
        
        <?php
        
        }
        
        ?>
        
        <div id="tabs-2">
        
        <?php
        
        $q = mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE exported = '0' ORDER BY lev_id");
        
        if( mysqli_num_rows($q) > 0 )
        {
            echo "<form name='frm_exp' id='frm_exp' method='post' >";
            
            if( $_SESSION[ $session_var ]->group_id != 8 )
            {
                echo "<input type='submit' name='export' id='export' value='Export' />";
                echo "<input type='checkbox' checked='checked' name='verwerk' id='verwerk' /> process payments.";
                echo "<br/>";
            }

            echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
            echo "<tr>";
            echo "<td width='50' >&nbsp;";
            ?>
             <input type='checkbox' name='sel_all'  onclick='selectAlles("chk_bet[]", this, "frm_exp");' /> 
            <?php
            echo "</td>";
            echo "<td><b> Supplier </b></td>";
            echo "<td><b> Amount<br/>Incl. </b></td>";
            echo "<td width='300'><b> Description </b></td>";
            echo "<td><b> Bill </b></td>";
            echo "<td><b> Date<br/>input </b></td>";
            echo "<td><b> Int.ID </b></td>";
            
            if( $_SESSION[ $session_var ]->group_id != 8 )
            {
            
                echo "<td><b> Check </b></td>";
                echo "<td><b> Paid through </b></td>";
            }
            
            echo "</tr>";
            
            $i = 0;
            
            $tot = 0;
            $tot_vink = 0;
            
            while( $rij = mysqli_fetch_object($q) )
            {
                $tot += $rij->bedrag_incl;
                
                $i++;
                
                $kleur = $kleur_grijs;
        		if( $i%2 )
        		{
        			$kleur = "white";
        		}
                
                // nakijken of er een CN is van deze leverancier.
                $aantal_cn = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE exported = '0' AND bedrag_incl < 0 AND lev_id = " . $rij->lev_id));
                
                if( $aantal_cn > 0 )
                {
                    $kleur = "lightgreen";
                }
                
                
                $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
        
        		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                echo "<td valign='middle'>";
                
                if( $_SESSION[ $session_var ]->group_id != 8 )
                {
                    echo "<a onclick=\"javascript:return confirm('Payment delete?')\" href='betalingen.php?id=". $rij->id ."&actie=delete'><img src='images/delete.png' /></a>";
                }
                
                // cn voor deze betaling? 
                $neg_bet = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE lev_id = " . $rij->lev_id . " AND exported = '0' AND bedrag_incl < 0 LIMIT 1"));
                
                $str = "";
                if( $neg_bet )
                {
                    $str = "popup(". $rij->id .");";
                }
                
                if( $rij->approved != '0' && $rij->bedrag_incl > 0 && $rij->check_by == 0 )
                {
                    echo "<input type='checkbox' name='chk_bet[]' id='check_". $rij->id ."' value='". $rij->id ."' onclick='opteller(this, ".$rij->bedrag_incl.");". $str ."' />";
                    $tot_vink += $rij->bedrag_incl;   
                }else
                {
                    if( $rij->check_by != 0 && $rij->approved == 1 )
                    {
                        echo "<input type='checkbox' name='chk_bet[]' id='check_". $rij->id ."' value='". $rij->id ."'  onclick='opteller(this, ".$rij->bedrag_incl.");". $str ."' />";
                        $tot_vink += $rij->bedrag_incl;
                    }
                }
                
                echo "</td>";
                echo "<td valign='middle'>". ucwords( strtolower($lev->naam)) ."</td>";
                echo "<td valign='middle'>". number_format( $rij->bedrag_incl, 2, ",", " " ) ."</td>";
                echo "<td valign='middle'>". $rij->beschrijving ."</td>";
                echo "<td valign='middle'>";
                echo "<a href='betalingen/". $rij->scan ."' target='_blank'>". $rij->scan ."</a>";
                
                //echo "&nbsp;<a class='various6' href='klanten/betalingen_nw_fac.php'>";
                if( $_SESSION[ $session_var ]->group_id != 8 )
                {
                    echo "&nbsp;<a class='various6' href='klanten/betalingen_nw_fac.php?id=". $rij->id ."'>";
                    echo "<img src='images/disk.jpg' title='Add another bill' alt='Add another bill' />";
                    echo "</a>";
                }
                
                echo "</td>";
                
                echo "<td valign='middle'>". changeDate2EU( substr( $rij->datetime,0,10) ) ."</td>";
                
                echo "<td valign='middle'>". $rij->nr_intern ."</td>";
                
                if( $_SESSION[ $session_var ]->group_id != 8 )
                {
                
                    echo "<td valign='middle'><span id='controlelijst_".$rij->id."'>";
                    
                    if( $rij->bedrag_incl > 0 || 1 )
                    {
                        if( $rij->check_by == 0 )
                        {
                            echo "<select name='check". $rij->id ."' id='check". $rij->id ."' style='width:50px;' >";
                            echo "<option value='0'>&nbsp;</option>";
                            
                            
                            $q_users = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' AND user_id IN(2008, 2012, 2013, 2016, 2000,2015) ORDER BY naam, voornaam");
                            
                            while( $u = mysqli_fetch_object($q_users) )
                            {
                                echo "<option value='". $u->user_id ."'>". $u->naam . " " . $u->voornaam ."</option>";
                            }
                            
                            echo "</select>";
                            echo "<input type='submit' value='Verstuur' name='controle_".$rij->id."' />";
                        }else
                        {
                            echo "<img src='images/delete.png' class='delete_contactpersoon' rec_id='".$rij->id."' />";
                            
                            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $rij->check_by));
                            echo $user->voornaam . " " . $user->naam;
                            
                            switch( $rij->approved )
                            {
                                case "0" :
                                    echo "<a class='various5' href='klanten/betalingen_protest.php?bet_id=". $rij->id ."'> - <span class='error' style='font-weight:800;pointer:cursor;'><u>Protested </u></span></a>";
                                    break;
                                case "1" :
                                    echo " - <span class='correct'>Approved </span>";
                                    break;
                            }
                        }
                    }
                    
                    echo "</span></td>";
                    
                    echo "<td valign='middle'>";
                    
                    if( empty( $rij->betaald ) )
                    {
                        $pay_arr = array();
                        
                        $pay_arr[] = "Bank card";
                        $pay_arr[] = "Compensation";
                        $pay_arr[] = "Domiciliary";
                        $pay_arr[] = "Isabel";
                        $pay_arr[] = "Counter";
                        $pay_arr[] = "PayPal";
                        $pay_arr[] = "Visa";
                        $pay_arr[] = "Erroneous entry";
                        $pay_arr[] = "Other";
                        
                        echo "<select name='betaald_via_".$rij->id."' id='betaald_via_".$rij->id."' style='width:50px;'>";
                        echo "<option value=''></option>";
                        
                        foreach( $pay_arr as $p )
                        
                        echo "<option value='".$p."'>". $p ."</option>";
                        
                        echo "</select>";
                    }
                    
                    echo "<input type='submit' name='pay_".$rij->id."' id='pay' value='Betaald' />";
                    
                    echo "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
            echo "<input type='hidden' value='1' name='tab_id' id='tab_id' />";
            //echo "<input type='hidden' value='1' name='bet_id' id='bet_id' value='". $rij->id ."' />";
            echo "<input type='hidden' name='tot_vink' id='tot_vink' value='". $tot_vink ."' />";
            echo "</form>";
            
            
            
            echo "<br/><b>Total : &euro; ". number_format($tot, 2, ",", " ") ." </b>";
            echo "<br/>Totaal selected : <input type='text' disabled name='id_teller' id='id_teller' value='0' />";
            
            echo "<br/><br/><br/><table>";
            echo "<tr><td style='background-color:green;'>&nbsp;&nbsp;&nbsp;</td><td>These payments need to have a CN</td></tr>";
            echo "</table>";
            
        }else
        {
            echo "No payments have been found.";
        }
        
        ?>
        
        </div>
        
        <?php
        if( $_SESSION[ $session_var ]->group_id != 8 )
        {
        ?>
        
        <div id="tabs-3">
            Check payments<br />
            
            <?php

            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE exported = '0' AND check_by > 0 AND approved = '2'") or die( mysqli_error($conn) );
            
            if( mysqli_num_rows($q_zoek) > 0 )
            {
                echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
                echo "<tr>";
                echo "<td><b> Supplier </b></td>";
                echo "<td><b> Bill </b></td>";
                echo "<td><b> Amount Incl. </b></td>";
                echo "<td><b> Description </b></td>";
                echo "<td><b> Control by </b></td>";
                echo "<td><b> Action </b></td>";
                echo "</tr>";
                
                while( $rij = mysqli_fetch_object($q_zoek) )
                {
                    $i++;
                
                    $kleur = $kleur_grijs;
            		if( $i%2 )
            		{
            			$kleur = "white";
            		}
                    
                    $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
            
            		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                    //echo "<td valign='top'>". ucwords( strtolower($lev->naam)) ."</td>";
                    echo "<td valign='middle'><a href='leveranciers.php?klant_id=". $lev->id ."&tab_id=1' target='_blank'><u>". ucwords( strtolower($lev->naam)) ."</u></a></td>";
                    
                    
                    echo "<td valign='top'>";
                    echo "<a href='betalingen/". $rij->scan ."' target='_blank'>". $rij->scan ."</a>";
                    echo "</td>";
                    
                    echo "<td valign='top'>". number_format( $rij->bedrag_incl, 2, ",", " " ) ."</td>";
                    echo "<td valign='top'>". $rij->beschrijving ."</td>";
                    echo "<td valign='top'>";
                    
                    $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $rij->check_by));
                    echo $user->voornaam . " " . $user->naam;
                    
                    echo "</td>";
                    echo "<td valign='top'>";
                    
                    //echo $rij->check_by . " " . $user_logged_in;
                    
                    if( $rij->check_by == $user_logged_in || $rij->check_by == 26 || $rij->check_by == 1998 || $rij->check_by == 2000 )
                    {
                    
                        echo "<form method='post' name='frm_check_". $rij->id ."' id='frm_check_". $rij->id ."' style='display:inline;' >";
                        echo "<input type='image' src='images/ok.png' width='16' height='16' title='Approve' />";
                        echo "<input type='hidden' name='actie' id='actie' value='ok' />";
                        echo "<input type='hidden' name='bet_id' id='bet_id' value='". $rij->id ."' />";
                        echo "<input type='hidden' name='tab_id' id='tab_id' value='2' />";
                        echo "</form>";
                        
                        echo "&nbsp;";
                        
                        /*
                        if( $_SESSION[ $session_var ]->user_id == 19 )
                        {
                        */
                            echo "<a class='various5' href='klanten/betalingen_protest.php?bet_id=". $rij->id ."'>";
                            echo "<img src='images/nok.png' width='16' height='16' />";
                            echo "</a>";
                        /*
                        }else
                        {
                            echo "<form method='post' name='frm_check_nok_". $rij->id ."' id='frm_check_nok_". $rij->id ."' style='display:inline;'>";
                            echo "<input type='image' src='images/nok.png' width='16' height='16' title='Factuur protesteren' />";
                            echo "<input type='hidden' name='actie' id='actie' value='nok' />";
                            echo "<input type='hidden' name='bet_id' id='bet_id' value='". $rij->id ."' />";
                            echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
                            echo "</form>";
                        }
                        */
                    }
                        
                    echo "</td>";
                    echo "</tr>";
                    
                    controleerVerrichting($rij);
                }
                
                echo "</table>";
            }
            
            ?>
        </div>
        
        <div id="tabs-4">
            Protested bills <br />
            
            <?php
            
            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE check_by > 0 AND approved = '0'") or die( mysqli_error($conn) );
            
            if( mysqli_num_rows($q_zoek) > 0 )
            {
                echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
                echo "<tr>";
                //echo "<td></td>";
                echo "<td><b> Supplier </b></td>";
                echo "<td><b> Int.ID </b></td>";
                echo "<td><b> Amount Incl. </b></td>";
                echo "<td><b> Description </b></td>";
                echo "<td><b> Control by </b></td>";
                echo "<td align='center'><b> Action </b></td>";
                echo "</tr>";
                
                while( $rij = mysqli_fetch_object($q_zoek) )
                {
                    $i++;
                
                    $kleur = $kleur_grijs;
            		if( $i%2 )
            		{
            			$kleur = "white";
            		}
                    
                    $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
            
            		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                    
                    //echo "<td valign='top'>".  ."</td>";
                    
                    echo "<td valign='top'>". ucwords( strtolower($lev->naam)) ."</td>";
                    echo "<td valign='top'>". $rij->nr_intern ."</td>";
                    echo "<td valign='top'>". number_format( $rij->bedrag_incl, 2, ",", " " ) ."</td>";
                    echo "<td valign='top'>". $rij->beschrijving ."</td>";
                    echo "<td valign='top'>";
                    
                    $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $rij->check_by));
                    echo $user->voornaam . " " . $user->naam;
                    
                    echo "</td>";
                    echo "<td valign='top' align='center'>";
                    
                    if( $rij->check_by == 26 || $_SESSION[ $session_var ]->user_id == 19 || $_SESSION[ $session_var ]->user_id == 26 || $rij->check_by == 2000 )
                    {
                        echo "<form method='post' name='frm_check_". $rij->id ."' id='frm_check_". $rij->id ."' style='display:inline;' >";
                        echo "<input type='image' src='images/ok.png' width='16' height='16' title='Toch goedkeuren' />";
                        echo "<input type='hidden' name='actie' id='actie' value='ok' />";
                        echo "<input type='hidden' name='bet_id' id='bet_id' value='". $rij->id ."' />";
                        echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
                        echo "</form>";
                        
                        echo "&nbsp;&nbsp;<a class='various5' href='klanten/betalingen_protest.php?bet_id=". $rij->id ."'><span class='error' style='font-size:18px;font-weight:800;' >P</span></a>";
                        
                    }else
                    {
                        echo "No rights";
                    }
                        
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
            ?>
        </div>
        
        <div id="tabs-5">
        
        <?php
        
        echo "Paid invoices";
        
        $q = mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE exported = '1' ORDER BY exported_dt DESC");
        
        if( mysqli_num_rows($q) > 0 )
        {
            echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
            echo "<tr>";
            echo "<td><b> Supplier </b></td>";
            echo "<td><b> Amount Incl. </b></td>";
            echo "<td><b> Description </b></td>";
            echo "<td><b> Through </b></td>";
            echo "<td><b> Bill </b></td>";
            echo "<td><b> Int.ID </b></td>";
            echo "<td><b> Processed </b></td>";
            echo "</tr>";
            
            $i = 0;
            while( $rij = mysqli_fetch_object($q) )
            {
                $i++;
                
                $kleur = $kleur_grijs;
        		if( $i%2 )
        		{
        			$kleur = "white";
        		}
                
                $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $rij->lev_id));
        
        		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                
                echo "<td><a title='Click here to open client' href='leveranciers.php?tab_id=1&klant_id=".$rij->lev_id ."' target='_blank' ><u>";
                echo ucwords( strtolower($lev->naam));
                echo "</u></a></td>";
                
                echo "<td valign='center'>". number_format( $rij->bedrag_incl, 2, ",", " " ) ."</td>";
                echo "<td valign='center'>". $rij->beschrijving ."</td>";
                
                echo "<td valign='center'>";
                
                if( empty( $rij->betaald ) )
                {
                    echo "Isabel";
                }else
                {
                    echo $rij->betaald;
                }
                                 
                echo "</td>";
                
                echo "<td valign='center'>";
                echo "<a href='betalingen/". $rij->scan ."' target='_blank'>". $rij->scan ."</a>";
                echo "</td>";
                echo "<td valign='center'>". $rij->nr_intern ."</td>";
                echo "<td valign='right'>";
                $dt = explode(" ", $rij->exported_dt);
                echo changeDate2EU( $dt[0] ) . " " . $dt[1];
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        ?>
        
        </div>
        
        
        <?php
        
        }
        
        ?>
        
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

function controleerVerrichting($verrichting)
{
    $conn = $GLOBALS["conn"];
    
    $fout_arr = array();
    $fout = 0;
    /*
    $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
    
    // controle ERP bedrijfsnaam
    if( strlen($instellingen->bedrijf_naam) > 70 )
    {
        $fout = 1;
        $fout_arr[] = 'Bedrijfsnaam is te lang(max. 70 karakters): '.$instellingen->bedrijf_naam;
    }
    
    // controle ERP btw
    $btw = $instellingen->bedrijf_btw;
    if(substr($btw,0,2) == 'BE'){
        $btw = substr($btw,2);
    }
    if( strlen($btw) > 35 )
    {
        $fout = 1;
        $fout_arr[] = 'ERP BTW is te lang';
    }
    */
    // controle ERP rekeningnummer
    /*
    $q_bank = mysqli_query($conn, "SELECT * FROM kal_bank WHERE id=".$verrichting->bank_id);
    
    if( mysqli_num_rows($q_bank) != 0 )
    {
        $bank = mysqli_fetch_object($q_bank);
        if( !empty($bank->iban) )
        {

            if(!preg_match("/[a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}([a-zA-Z0-9]?){0,16}/i", $bank->iban)) 
            {
                $fout = 1;
                $fout_arr[] = "ERP IBAN is niet geldig: <b>".$bank->iban."</b>";
            }
        }else{
            $fout = 1;
            $fout_arr[] = "ERP IBAN is leeg.";
        }

        if( !empty($bank->bic) )
        {
            // controle bic
            if(!preg_match("/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/i", $bank->bic)) 
            {
                $fout = 1;
                $fout_arr[] = "ERP BIC is niet geldig: <b>".$bank->bic."</b>";
            }
        }else{
            $fout = 1;
            $fout_arr[] = "ERP BIC is leeg.";
        }
    }else{
        $fout = 1;
        $fout_arr[] = "ERP bankrekening bestaat niet meer.";
    }
    */
    
    // REQDATE
    $reqdate = date("Y-m-d", mktime(0,0,0,date('m'),date('d'),date('Y')) );
    if( strlen( $reqdate ) > 10 )
    {
        $fout = 1;
        $fout_arr[] = "Reqdate is too long"; 
    }
    
    // endtoend
    $endtoend = $verrichting->id . "/" . $reqdate;
    
    if( strlen( $endtoend ) > 35 )
    {
        $fout = 1;
        $fout_arr[] = "PaymentsID + reqdate are too long"; 
    }
    
    // bedrag_incl
    if( stristr( $verrichting->bedrag_incl,'.') )
    {
        $exp_bedrag = explode('.', $verrichting->bedrag_incl);
        if( strlen( $exp_bedrag[0] ) > 9)
        {
            $fout = 1;
            $fout_arr[] = 'Amount incl. too large';
        }
        if( strlen( $exp_bedrag[1] ) > 2 )
        {
            $fout = 1;
            $fout_arr[] = "Amount incl. contains too many digits after the decimal point";
        }
        
    }else{
        $fout = 1;
        $fout_arr[] = 'Amount incl. -> no point used as comma';
    }
    
    // Leverancier naam
    if( !empty($verrichting->lev_id) )
    {
        
        $q_lev = mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id=".$verrichting->lev_id);
        if( mysqli_num_rows($q_lev) != 0 )
        {
           $lev = mysqli_fetch_object($q_lev);
        
            if( !empty($lev->naam) )
            {
                if( strlen( $lev->naam) > 75)
                {
                    $fout = 1;
                    $fout_arr[] = 'Suppliers name is too large(max. 75 characters): <b>'.$lev->naam.'</b>';
                }
            } 
        }else{
            $fout = 1;
            $fout_arr[] = "Suppliers does not exist.";
        }
        
    }else{
        $fout = 1;
        $fout_arr[] = 'No supplier found.';
    }    
    
    // leveranciers bank nummer
    if( !empty($verrichting->reknr_id) && $verrichting->reknr_id != 0 )
    {
        $q_reknr = mysqli_query($conn, "SELECT * FROM kal_reknr WHERE id=".$verrichting->reknr_id);
        
        if( mysqli_num_rows($q_reknr) != 0 )
        {
            $reknr = mysqli_fetch_object($q_reknr);

            if( !empty($reknr->reknr) )
            {
                if(!preg_match("/[a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}([a-zA-Z0-9]?){0,16}/i", $reknr->reknr)) 
                {
                    $fout = 1;
                    $fout_arr[] = "Suppliers IBAN is not valid: <b>".$reknr->reknr."</b>";
                }
            }

            if( !empty($reknr->bic) )
            {
                // controle bic
                if(!preg_match("/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/i", $reknr->bic)) 
                {
                    $fout = 1;
                    $fout_arr[] = "Suppliers BIC is not valid: <b>".$reknr->bic."</b>";
                }
            }
        }else{
            $fout = 1;
            $fout_arr[] = 'Account no longer exists.';
        }
    }else{
        
        $rek = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND klant_id = " . $verrichting->lev_id ." LIMIT 1"));
        
        if( !$rek )
        {
            $fout = 1;
            $fout_arr[] = 'Suppliers account is not filled.';    
        }
        
        /*
        echo "<br />" . $verrichting->id;
        echo "<pre>";
        var_dump( $rek );
        echo "</pre>";
        */
        
    }
    
    // vrije mededeling
    if( !empty( $verrichting->vrije_mededeling ) )
    {
        if( strlen( $verrichting->vrije_mededeling ) > 140 )
        {
            $fout = 1;
            $fout_arr[] = 'Free communication is longer than 140 characters.';
        }
    }
    
    // gestructueerd mededeling
    if( !empty( $verrichting->ogm ) )
    {
        
        // TODO
        // eerst - wegdoen, dan sanitizen, dan ogm controle uitvoeren.
        
        // eerst - wegdoen
        if( stristr($verrichting->ogm, '-') )
        {
            $verrichting->ogm = str_replace('-', '', $verrichting->ogm);
        }
        
        // dan sanitizen
        $int = filter_var($verrichting->ogm, FILTER_SANITIZE_NUMBER_INT);
        
        // dan ogm controle uitvoeren.
        if( strlen( $int ) == 12 )
        {
            $ogm_10 = substr( $int, 0 , 10 );
            $ogm_2 = substr( $int , 10 , 2 );

            $controle = $ogm_10 % 97;
            
            if( $controle == 0 )
            {
                $controle = 97;
            }
            
            if( $controle != $ogm_2 )
            {
                $fout = 1;
                $fout_arr[] = "Structured communication is not valid: ".$verrichting->ogm;
            }
        }else{
            $fout = 1;
            $fout_arr[] = "Structured communication must have 12 characters. (#".strlen( $int )."): ".$verrichting->ogm;
        }
    }
    
    // todo : ogm of vrije mededeling is verplicht.
    if( empty($verrichting->ogm) && empty($verrichting->vrije_mededeling) )
    {
        $fout = 1;
        $fout_arr[] = "no free communication and no structured information found..";
    }else if( !empty($verrichting->ogm) && !empty($verrichting->vrije_mededeling) )
    {
        $fout = 1;
        $fout_arr[] = "free communication and structured information are found. Structured information will be used.";
    }
    
    if( $fout == 1 )
    {
        echo "<tr>";
        echo "<td colspan='9' style='border:1px solid red;color:red;'>";
        foreach( $fout_arr as $f)
        {
            echo " - ".$f ." <br />";
        }
        echo "</td>";
        echo "</tr>";
    }
}
?>