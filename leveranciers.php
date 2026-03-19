<?php

session_start();

    ini_set('display_errors',1); 
    error_reporting(E_ALL);
    
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";
//include("inc/Curl_HTTP_Client.php");

if( isset( $_GET["actie"] ) && $_GET["actie"] == "delete" )
{
    $q_del = "DELETE FROM kal_reknr WHERE id = " . $_GET["rekid"];
    mysqli_query($conn, $q_del) or die( mysqli_error($conn) ) ;
}

if( isset( $_POST["opslaan"] ) && $_POST["opslaan"] == "Opslaan" )
{
    $koppel = 0;
    if( isset( $_POST["nt_koppelen"] ) )
    {
        $koppel = 1;
    }
    
    $q_upd = "UPDATE kal_leveranciers 
              SET naam = '" . htmlentities($_POST["lev"], ENT_QUOTES) . "',
                  straat = '" . htmlentities($_POST["straat"], ENT_QUOTES) . "',
                  postcode = '" . $_POST["postcode"] . "',
                  gemeente = '" . htmlentities($_POST["gemeente"], ENT_QUOTES) . "',
                  land = '" . htmlentities($_POST["land"], ENT_QUOTES) . "',
                  email = '" . $_POST["email"] . "',
                  betalingstermijn = '" . $_POST["bet_term"] . "',
                  taal = '" . htmlentities($_POST["taal"], ENT_QUOTES) . "',
                  btwperc = '" . $_POST["btw_perc"] . "',
                  btwnr = '" . $_POST["btw_nr"] . "',
                  tel = '" . $_POST["tel"] . "',
                  gsm = '" . $_POST["gsm"] . "',
                  koppelen = '". $koppel ."'
              WHERE id = " . $_POST["lev_id"];
    
    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
    
    if( !empty( $_POST["reknr"] ) )
    {
        $aant_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_reknr WHERE klant_id = " . $_POST["lev_id"] . " AND reknr = '" . $_POST["reknr"] . "'"));
        
        if( $aant_zoek == 0 )
        {
            $q_ins = "INSERT INTO kal_reknr(klant_id, tabel, reknr, bic) 
VALUES(". $_POST["lev_id"] .",'kal_leveranciers', '". str_replace(" ", "", str_replace(".", "",$_POST["reknr"]) )  ."','". str_replace(" ", "", $_POST["bic"]) ."')";
            mysqli_query($conn, $q_ins);
        }
    }
    
    if( !empty( $_FILES["doc"]["name"] ) )
    {
        // aantal bestanden te uploaden
        $aantal_bestanden = count($_FILES["doc"]["name"]);
        // verander dir naar lev_docs
        chdir( "lev_docs/");
        // maak dir met lev_id
        @mkdir( $_POST["lev_id"] );
        // ga daar in
        chdir( $_POST["lev_id"] );
        // maak dir 'doc'
        @mkdir( "doc" );
        // ga daar in
        chdir( "doc" );
        // loop bestanden
        
        //echo getcwd();
        
        for($i=0;$i<$aantal_bestanden;$i++)
        {
            // upload bestand
            $upload_ok = move_uploaded_file($_FILES['doc']['tmp_name'][$i], $_FILES['doc']['name'][$i]);

            // insert naar database
            $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,cf_soort,cf_file) 
                                               VALUES(". $_POST["lev_id"] .",'lev_docs','".$_FILES["doc"]["name"][$i]."')";
            // als bestand geupload is dan voer query uit
            if( $upload_ok )
            {
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );    
            } 
        }
        
        chdir( "../../../" );
        
    }
    
    // begin verwijderen distri offerte
    foreach( $_POST as $key => $post )
    {
    	if( substr($key, 0, 11) == "levdoc_del_" )
    	{
            // opzoeken record
            $id = substr( $key, 11 );
            $distri_off = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'lev_docs' AND cf_id = " . $id));
            
            // bestand verwijderen 
            $bestand = "lev_docs/" . $_POST["lev_id"] . "/doc/" . $distri_off->cf_file;
            if(file_exists($bestand))
            {
                if(unlink( "lev_docs/" . $_POST["lev_id"] . "/doc/" . $distri_off->cf_file ))
                {
                    // record verwijderen
                    $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $distri_off->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
                }
            }else{echo "<br />Het werkt niet.";}
    	}
    }
}

if( isset( $_POST["bewaar"] ) && $_POST["bewaar"] == "Bewaar" )
{
    if( !empty( $_POST["n_naam"] ) )
    {
        // toevoegen in de databank
        $q_ins = "INSERT INTO kal_leveranciers(naam,straat,postcode,gemeente,land,email,betalingstermijn,taal,btwperc,btwnr,tel,gsm) 
                                       VALUES('". htmlentities($_POST["n_naam"], ENT_QUOTES) ."',
                                              '".htmlentities($_POST["n_straat"], ENT_QUOTES)."',
                                              '".$_POST["n_postcode"]."',
                                              '". htmlentities($_POST["n_gemeente"], ENT_QUOTES) ."',
                                              '". htmlentities($_POST["n_land"], ENT_QUOTES) ."',
                                              '". $_POST["n_email"] ."',
                                              '". $_POST["n_betterm"] ."',
                                              '". htmlentities($_POST["n_taal"], ENT_QUOTES) ."',
                                              '". $_POST["n_btwperc"] ."',
                                              '". $_POST["n_btwnr"] ."',
                                              '". $_POST["n_tel"] ."',
                                              '". $_POST["n_gsm"] ."' )";
        mysqli_query($conn, $q_ins) or die( mysqli_error($conn) . " " . __LINE__ );                                              
        
        unset( $_POST );
        
        $_POST["cus_id1"] = mysqli_insert_id($conn);
  		$_REQUEST["tab_id"] = '1';
        
    }
}

/*
echo "<pre>";
var_dump( $_POST );
echo "</pre>";
*/

/*
// IMPORTEREN VAN DE LEVERANCIERS UIT EXCEL BESTAND

$row = 1;
if (($handle = fopen("leveranciers.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        
        $row++;
        
        if( $row > 3 )
        {
            
            echo "<p> $num fields in line $row: <br /></p>\n";
            for ($c=0; $c < $num; $c++) {
                echo $data[$c] . "<br />\n";
                
                
                $regels = explode(";", $data[$c]);
                
                echo "<pre>";
                var_dump( $regels );
                echo "</pre>";
                
                if( stristr($regels[5], "belg") )
                {
                    $regels[5] = "Belgie";
                }
                
                $q_ins = "INSERT INTO kal_leveranciers(naam,
                                                       straat,
                                                       postcode,
                                                       gemeente,
                                                       land,
                                                       betalingstermijn,
                                                       taal,
                                                       btwperc,
                                                       btwnr,
                                                       added) 
                                                VALUES('". htmlspecialchars($regels[1], ENT_QUOTES) ."',
                                                       '". htmlspecialchars($regels[2], ENT_QUOTES) ."',
                                                       '". htmlspecialchars( $regels[3], ENT_QUOTES ) ."',
                                                       '". htmlspecialchars( $regels[4] , ENT_QUOTES ) ."',
                                                       '". htmlspecialchars( $regels[5] , ENT_QUOTES) ."',
                                                       '". $regels[14] ."',
                                                       '". $regels[15]."',
                                                       '". str_replace("%","", $regels[17]) ."',
                                                       '". $regels[0] ."',
                                                       '". $regels[16] ."') ";
                
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) . " " . __LINE__  );
                
            }
        }
    }
    fclose($handle);
}
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>Leveranciers<?php include "inc/erp_titel.php" ?></title>
<style type='text/css'>

table.main_table {
	background-color: #FFFFCC;
	border-top: 1px solid silver;
	border-left: 1px solid silver;
	border-bottom: 2px solid silver;
	border-right: 2px solid silver;
}

input:focus {
	border: 2px solid #3333FF;
}

select:hover {
	border: 1px solid #3399FF;
}

input[type=text]:hover,textarea:hover,checkbox:hover {
	border: 2px solid #3399FF;
}

.klant_gegevens{
	font-weight:800;
}

.offerte_gegevens{
	font-weight:800;
	color: darkblue;
}

.verkoop_gegevens{
	font-weight:800;
	color: darkgreen;
}

fieldset{
	width: 440px;
}

legend{
	font-weight:800;
	font-style:italic;
}

#id_vies fieldset{
    width : 370px;    
}
[id^=tbl_] label{
    font-weight:bold;
}

</style>
<script type="text/javascript" src="js/functions.js"></script>


<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" 	src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" media="print" href="css/print.css" />

<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<!-- 
<script type="text/javascript" src="js/jquery.validate.js"></script>
-->

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="js/jquery.validate.js"></script>

<script type='text/javascript'>

function isNumberKey1(evt)
{
   var charCode = (evt.which) ? evt.which : evt.keyCode;
   
   if (charCode == 32 )
      return false;

   return true;
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

function getViesData(btw)
{
	datasource = "ajax/lev_vies.php?btw=" + btw;
        var obj = document.getElementById("id_vies");

	if(XMLHttpRequestObject1){
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200){
				obj.innerHTML = XMLHttpRequestObject1.responseText;
			}else
                        {
                            obj.innerHTML = "<img src='images/indicator.gif' /> De gegevens worden opgehaald van de VIES-website";
                        }
    	}
		
		XMLHttpRequestObject1.send(null);
	}
}

jQuery(document).ready(function() {
$("#transactie_add").fancybox({
        'width': '80%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe',
    });
    $(".edit_trans").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });
    $('tr[id^=extra_]').hide();
    // TOGGLE EXTRA DETAIL
    $("tr[id^='transactie_']").live("click",function(){
        var id = $(this).children().find('a').attr('alt');
       $('#extra_' + id).toggle();
       $.post("ajax/klanten_ajax.php", {transactie_id: id,action: 'getList'}, function(data) {
            $('#tbl_' + id).html(data);
        });
    });
    $('.delete_trans').live('click',function(){
        if(confirm("Verwijderen?")){
            var id = $(this).attr('alt');
            $.post("ajax/klanten_ajax.php", {transactie_id: id,action: 'del_lev'}, function() {
                $('#transactie_' + id).remove();
            });
        }
        return false;
    });
    $('.edit_trans').live('click',function(){
        return false;
    });
    $("#callhistory").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'iframe'
	});
    
    $("#various_m").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'iframe'
	});
    $("#reactie").fadeOut(5000);
    $("#opslaan").live('click',function(){
        var reknr = $('#reknr').val();
        var bic = $('#bic').val();
        if(reknr != '' && bic == ''){
            $('#bic').css('border','1px solid red');
            return false;
        }
    });
});

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});

function gotoKlant(cus_id1)
{
	document.getElementById("cus_id1").value = cus_id1;
	document.getElementById("frm_overzicht").submit();	
}

$().ready(function() {
	$("#klant").autocomplete("lev_ajax.php", {
		width: 260,
		matchContains: false,
		mustMatch: false,
		//minChars: 0,
		//multiple: true,
		//highlight: false,
		//multipleSeparator: ",",
		selectFirst: false
	});
	
	$("#klant").result(function(event, data, formatted) {
		$("#klant_val").val(data[1]);
	});
});

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

<?php

$onload = "";

if( isset( $_POST["cus_id1"] ) )
{
    $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $_POST["cus_id1"]));
    
    $vat = $lev->btwnr;

/*    
echo "<pre>";
var_dump($lev);
echo "</pre>";
*/
      
    if( !empty( $vat ) )
    {
        
        
        $onload = " onload='getViesData(\"". $vat ."\")' ";
        
        /*
        
        */
    }
}       

?>


<body <?php echo $onload; ?> >

<div id='pagewrapper'>
<?php include('inc/header.php'); ?>
<h1>Leveranciers</h1>

<div id="tabs">
<ul>
	<li><a href="#tabs-1">Nieuw</a></li>
	<li><a href="#tabs-2">Zoek</a></li>
	<li><a href="#tabs-4">Overzicht</a></li>
	
    <!--
    <li><a href="#tabs-10" style='font-size:0.63em;'>Uitgebreid<br/>Zoeken</a></li>
    -->
</ul>
<div id="tabs-1">

Nieuwe leverancier toevoegen.<br/><br/>

<?php


if( isset( $_POST["bewaar"] ) && $_POST["bewaar"] == "Bewaar" )
{
    if( empty( $_POST["n_naam"] ) )
    {
        echo "<span class='error'>De naam van de leverancier is verplicht.</span>";
    }
}

?>


<form method='post' id='frm_new_cus' name='frm_new_cus' action='' enctype='multipart/form-data'>

<table>
	<tr>
		<td>Leverancier :</td>
		<td><input type='text' name='n_naam' id='n_naam' class='lengte' value='<?php if( isset( $_POST["n_naam"] ) ) echo $_POST["n_naam"]; ?>' />
		</td>
	</tr>
    
    <tr>
		<td>Straat &amp; Nr. :</td>
		<td><input type='text' name='n_straat' id='n_straat' class="lengte" value='<?php if( isset( $_POST["n_straat"] ) ) echo $_POST["n_straat"]; ?>' /> </td>
	</tr>
    
    <tr>
		<td>Postcode &amp; gemeente :</td>
		<td><input type='text' name='n_postcode' id='n_postcode' size='4' value='<?php if( isset( $_POST["n_postcode"] ) ) echo $_POST["n_postcode"]; ?>' onblur='checkCity(this);' /><input type='text' name='n_gemeente' id='n_gemeente' value='<?php if( isset( $_POST["n_gemeente"] ) ) echo $_POST["n_gemeente"]; ?>' /></td>
	</tr>

	<tr>
		<td>Land :</td>
		<td><input type='text' name='n_land' id='n_land' class='lengte' value='<?php if( isset( $_POST["n_land"] ) ) echo $_POST["n_land"]; ?>' /></td>
	</tr>
    
    <tr>
		<td>Taal :</td>
		<td><input type='text' name='n_taal' id='n_taal' class='lengte' value='<?php if( isset( $_POST["n_taal"] ) ) echo $_POST["n_taal"]; ?>' />
		</td>
	</tr>
    
    <tr>
		<td>Tel :</td>
		<td><input type='text' name='n_tel' id='n_tel' class='lengte' value='<?php if( isset( $_POST["n_tel"] ) ) echo $_POST["n_tel"]; ?>' />
		</td>
	</tr>
    
    <tr>
		<td>GSM :</td>
		<td><input type='text' name='n_gsm' id='n_gsm' class='lengte' value='<?php if( isset( $_POST["n_gsm"] ) ) echo $_POST["n_gsm"]; ?>' />
		</td>
	</tr>
    
    <tr>
		<td>E-mail :</td>
		<td><input type='text' name='n_email' id='n_email' class='lengte' value='<?php if( isset( $_POST["n_email"] ) ) echo $_POST["n_email"]; ?>' />
		</td>
	</tr>
    
	<tr>
		<td>BTW %:</td>
		<td><input type='text' name='n_btwperc' id='n_btwperc' class='lengte' value='<?php if( isset( $_POST["n_btwperc"] ) ) echo $_POST["n_btwperc"]; ?>' />
		</td>
	</tr>
    
    <tr>
		<td>BTW nr.:</td>
		<td><input type='text' name='n_btwnr' id='n_btwnr' class='lengte' value='<?php if( isset( $_POST["n_btwnr"] ) ) echo $_POST["n_btwnr"]; ?>' />
		</td>
	</tr>
	
	<tr>
		<td>Betalingstermijn :</td>
		<td><input type='text' name='n_betterm' id='n_betterm' class='lengte' value='<?php if( isset( $_POST["n_betterm"] ) ) echo $_POST["n_betterm"]; ?>' />
		</td>
	</tr>
    
	<tr>
		<td colspan='2' align='center'>&nbsp;</td>
	</tr>

	<tr>
		<td colspan='2' align='center'>&nbsp;</td>
	</tr>

	<tr>
		<td colspan='2' align='center'><input type='submit' name='bewaar' id='bewaar' value='Bewaar' /></td>
	</tr>
</table>

<input type='hidden' name='tab_id' id='tab_id' value='0' /></form>
</div>

<div id="tabs-2">
    
    <table cellpadding="0" cellspacing="0" width="100%">
    <tr>
    <td>
<script type="text/javascript">
function searchKlant()
{
    var klant = document.getElementById("klant_val").value;
    document.frm_lev.action = "leveranciers.php?tab_id=1&klant_id=" + klant;
    document.frm_lev.submit();
}        
</script>    
    <form autocomplete="off" method='post' id='frm_lev' name='frm_lev' action="leveranciers.php">
		<label for='klant'>Zoek leverancier :</label> <input type="text" name="klant" id="klant" /> 
		<input type="hidden" name="klant_id" id="klant_val" /> 
		<input type='hidden' name='tab_id' id='tab_id' value='1' /> 
		<input type="button" name="button" onclick="searchKlant();" value="Go" />
                <span id="reactie" style="color:green;"><?php if(isset($_POST['opslaan']))echo "Opslaan voltooid." ?></span>
	</form>
    
    </td><td align="right">
    
    <?php
    
    $lev_id = 0;
    
    
    if( isset( $_GET["klant_id"] ) )
    {
        $_POST["klant_id"] = $_GET["klant_id"];
    }
    
    
    if( isset( $_POST["klant_id"] ) && $_POST["klant_id"] > 0 )
    {
        $lev_id = $_POST["klant_id"];
    }
    
    if( isset( $_POST["cus_id1"] ) && $_POST["cus_id1"] > 0 )
    {
        $lev_id = $_POST["cus_id1"];
    }
    
    if( isset( $_POST["lev_id"] ) && $_POST["lev_id"] > 0 )
    {
        $lev_id = $_POST["lev_id"];
    }
    
    
    // als er coda bestanden zijn dan de knop weergeven.
    
    $aant_coda = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_coda WHERE lev_id = " . $lev_id));
    
    if( $aant_coda > 0 )
    {
    	?>
    	<input type='button' value='CODA' onclick="window.open('klanten_coda.php?lev_id=<?php echo $lev_id;  ?>','Coda','status,width=1100,height=800,scrollbars=yes'); return false;" />
        <?php
    }
    
    ?>
    
    </td>
    </tr>
    </table>    
	<?php
    
    /*    
    echo "<pre>";
    var_dump( $_POST );
    echo "</pre>";
    */
    
    
    
    if( $lev_id != 0 )
    {
        $lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $lev_id));
        
        if( !$lev )
        {
            die("Leverancier niet gevonden.");
        }
        
        /*
        echo "<pre>";
        var_dump( $lev );
        echo "</pre>";
        */
        
        echo "<form method='post' name='frm_leverancier' id='frm_leverancier' enctype='multipart/form-data' >";
        echo "<fieldset style='width:940px;background-color:#CCCCFF;'>";
        echo "<legend>Leverancier  (Referte : ";
        
        echo "1" . date("Ym") . $lev->id;
    
        echo ") </legend>";
        
        echo "<table width='100%'>";
        echo "<tr><td valign='top'>";
        
        echo "<table>";
        echo "<tr><td><b>Leverancier : </b></td><td><input type='text' name='lev' id='lev' size='40' value='".$lev->naam."' /></td></tr>";
        echo "<tr><td><b>Straat &amp; Nr. : </b></td><td><input type='text' name='straat' id='straat' size='40' value='". $lev->straat ."' /></td></tr>";
        echo "<tr><td><b>Postcode/Gemeente : </b></td><td><input type='text' name='postcode' id='postcode' value='". $lev->postcode ."' size='5' />&nbsp;&nbsp;<input type='text' name='gemeente' id='gemeente' value='". $lev->gemeente ."' size='30' /></td></tr>";
        echo "<tr><td><b>Land : </b></td><td><input type='text' name='land' id='land' value='". $lev->land ."' /></td></tr>";
        echo "<tr><td><b>Taal : </b></td><td><input type='text' name='taal' id='taal' value='". $lev->taal ."' /></td></tr>";
        
        echo "<tr><td>";
        
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td><strong>Tel.:</strong></td>";
        echo "<td align='right'>";         
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "</td><td><input type='text' name='tel' id='tel' value='". $lev->tel ."' /></td></tr>";
        
        
        echo "<tr><td>";
        
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td class='klant_gegevens' valign='top'>GSM:</td>";
        echo "<td align='right' valign='top'>";         
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "</td><td><input type='text' name='gsm' id='gsm' value='". $lev->gsm ."' /></td></tr>";
        
        
        echo "<tr><td><b>E-mail : </b></td><td><input type='text' name='email' id='email' value='". $lev->email ."' /></td></tr>";
        echo "<tr><td><b>BTW % :</b></td><td><input type='text' name='btw_perc' id='btw_perc' value='". $lev->btwperc ."' /></td></tr>";
        echo "<tr><td><b>BTW nr. : </b></td><td><input type='text' name='btw_nr' id='btw_nr' value='". $lev->btwnr ."' />";
        
        echo "</td></tr>";
        echo "<tr><td><b>Betalingstermijn :</b></td><td><input type='text' name='bet_term' id='bet_term' value='". $lev->betalingstermijn ."' /></td></tr>";
        echo "<tr><td valign='top'><b>Rekeningnummer :</b></td><td><input type='text' name='reknr' id='reknr' /> <strong>BIC</strong> <input type='text' onkeypress='return isNumberKey1(event);'  name='bic' id='bic' />";
        
        $q_zoek = mysqli_query($conn, "SELECT * FROM kal_reknr WHERE tabel = 'kal_leveranciers' AND klant_id = " . $lev->id);
        
        while( $reknr = mysqli_fetch_object($q_zoek) )
        {
            echo "<br>";
            /*
            echo "<form style='display:inline;' method='post' name='frm_". $reknr->id ."' id='frm_". $reknr->id ."' >";
            echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
            echo "<input type='hidden' name='cus_id1' id='cus_id1' value='". $lev->id ."' />";
            echo "<input type='hidden' name='delete_reknr' id='delete_reknr' value='". $reknr->id ."' />";
            echo "<input type='image' src='images/delete.png' />";
            echo "</form>";
            */
            
            echo "<a onclick=\"javascript:return confirm('Rek.nr. verwijderen?')\" href='leveranciers.php?tab_id=1&klant_id=".$lev->id."&actie=delete&rekid=". $reknr->id ."'><img src='images/delete.png' border='0' /></a>";
            
            echo $reknr->reknr . " (BIC: ". $reknr->bic .") ";
        }
        
        echo "</td></tr>";
        
        $chk = "";
        if( $lev->koppelen == '1' )
        {
            $chk = " checked='checked' ";
        }
        
        echo "<tr><td><b>Rek.Nr. niet koppelen:</b></td><td><input ". $chk ." type='checkbox' name='nt_koppelen' id='nt_koppelen' /></td></tr>";
        
        echo "<tr><td><strong>Documenten uploaden :</strong> </td><td> <input type='file' name='doc[]' id='doc' multiple='multiple' /> </td></tr>";
        
        // zoeken of er distri offertes zijn
//                                    $q = "SELECT * 
//		                                 FROM kal_customers_files
//		                                WHERE cf_cus_id = '". $lev->id ."'
//		                                  AND cf_soort = 'lev_docs' ";
//                                    echo $q;
		$q_zoek_levdocs = mysqli_query($conn, "SELECT * 
		                                 FROM kal_customers_files
		                                WHERE cf_cus_id = '". $lev->id ."'
		                                  AND cf_soort = 'lev_docs' ");
		
		if( mysqli_num_rows($q_zoek_levdocs) > 0 )
		{
			echo "<tr><td align='left' valign='top' colspan='2'><b>Documentatie:</b></td></tr>";
			while( $lev_docs = mysqli_fetch_object($q_zoek_levdocs) )
			{
				if( file_exists( "lev_docs/" . $lev->id . "/doc/" . $lev_docs->cf_file ) )
				{
					echo "<tr><td align='right'> ";
					echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
					
					echo "</td><td align='left'>";
						echo "<input type='checkbox' name='levdoc_del_". $lev_docs->cf_id ."' id='levdoc_". $lev_docs->cf_id ."' />";
						echo "<a href='lev_docs/" . $lev->id . "/doc/" . $lev_docs->cf_file . "' target='_blank' >";
						echo $lev_docs->cf_file;
						echo "</a>";
					echo "</td>";
					echo "</tr>";
				}
			}
		}
        
        echo "<tr>";
        echo "<td colspan='2' align='center'>";
        
        echo "<br/><br/>";
        
        echo "<input type='submit' name='opslaan' id='opslaan' value='Opslaan' />";
        
        if( $_SESSION[ $session_var ]->group_id ==1 )
		{
			//echo "<input type='submit' name='verwijderen' id='verwijderen' value='Verwijderen' onclick=\"javascript:return confirm('Deze leverancier verwijderen?')\" />";
		}
        
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        
        
        echo "</td><td valign='top'>";
        echo "<div id='id_vies'> </div>";
        echo "</td></tr></table>";
        
        
        
        echo "</fieldset>";
        echo "<fieldset>";
        echo "<legend>Transacties</legend>";
        echo "<a id='transactie_add' href='ajax/transactie_leverancier.php?lev_id=" . $lev->id . "'' >Transactie toevoegen</a>";
        echo "<table cellpadding='0' cellspacing='0'>";
        echo "<tr>";
        echo "<td>";
            echo "<table cellspacing='0' cellpadding='0' id='tbl_transactie_list' width='400'>";
                echo "<tr>";
                    echo "<th>";
                    echo "</th>";
                    echo "<th>";
                        echo 'Naam';
                    echo "</th>";
                    echo "<th>";
                        echo 'Status';
                    echo "</th>";
                    echo "<th>";
                    echo "</th>";
                echo "</tr>";
                $get_all_transacties = mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE soort='1' AND soort_id=".$lev->id);
                $i=0;
                while($transactie = mysqli_fetch_object($get_all_transacties)){
                    $i++;
                    $kleur = $kleur_grijs;
                    if ($i % 2) {
                        $kleur = "white";
                    }
                    echo "<tr id='transactie_".$transactie->id."' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                        echo "<td>";
                                echo "<a href='ajax/transactie_leverancier.php?lev_id=" . $lev->id . "&trans_id=".$transactie->id."' class='edit_trans' alt=".$transactie->id." '><img src='images/edit.png'/></a>";
                                echo "<a href='' class='delete_trans' alt=".$transactie->id."><img src='images/delete.png'/></a>";
                        echo "</td>";
                        echo "<td>";
                                $get_name = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$transactie->prod_id));
                                echo truncate($get_name->name,40);
                        echo "</td>";
                        echo "<td>";
                                if($transactie->status == '0'){
                                    echo "Aankoop";
                                }else{
                                    echo "Verkoop";
                                }
                        echo "</td>";
                    echo "</tr>";
                    echo "<tr id='extra_".$transactie->id."'>";
                        echo "<td colspan='3'>";
                            echo "<table width='350' style='margin-left:50px;' cellpadding='0' cellspacing='0' id='tbl_".$transactie->id."'>";
                            echo "</table>";
                        echo "</td>";
                    echo "</tr>";
                }
            echo "</table>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</fieldset>";
        echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
        echo "<input type='hidden' name='lev_id' id='lev_id' value='". $lev->id ."' />";
        
        echo "</form>";
        
        
        
        echo "<br/><b><u>Betalingen</u></b>";
        
        $q_bet = mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE lev_id = " . $lev_id);
        
        if( mysqli_num_rows($q_bet) > 0 )
        {
            echo "<table width='100%' cellpadding='0' cellspacing='0' >";
            echo "<tr>";
            echo "<td><b>Fac.Nr.</b></td>";
            echo "<td><b>Fac.Datum</b></td>";
            echo "<td><b>Betaald via</b></td>";
            echo "<td><b>Interne Nr.</b></td>";
            echo "<td><b>Factuur</b></td>";
            echo "<td><b>Protest brief</b></td>";
            echo "<td align='right'><b>Bedrag Incl.&nbsp;&nbsp;</b></td>";
            echo "</tr>";
            
            $i = 1;
            
            while( $bet = mysqli_fetch_object($q_bet) )
            {
                $i++;
                $kleur = $kleur_grijs;
        		if( $i%2 )
        		{
        			$kleur = "white";
        		}
        
        		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                echo "<td>" . $bet->fac_nr . "</td>";
                echo "<td>" . changeDate2EU($bet->fac_datum) . "</td>";
                
                echo "<td>";
                
                if( $bet->exported == '1' )
                {
                    
                    if( !empty( $bet->betaald ) )
                    {
                        echo $bet->betaald;    
                    }else
                    {
                        echo "Isabel";
                    }
                }else
                {
                    echo "Nog niet betaald.";
                }
                
                echo "</td>";
                
                echo "<td>" . $bet->nr_intern . "</td>";
                echo "<td><a href='betalingen/". $bet->scan ."' target='_blank'>". $bet->scan ."</a></td>";
                
                //copy( "betalingen/". $bet->scan, "betalingen/lampiris/". $bet->scan );
                
                echo "<td>";
                
                if( file_exists("lev_docs/" . $lev_id . "/protest/Protest_factuur_" . $bet->nr_intern . ".pdf") )
                {
                    echo "<a target='_blank' href='lev_docs/" . $lev_id . "/protest/Protest_factuur_" . $bet->nr_intern . ".pdf'> Brief </a>";
                }
 
                echo "</td>";
                echo "<td align='right'>" . number_format($bet->bedrag_incl,2,","," ") . "&euro;&nbsp;&nbsp;</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
    ?>	
</div>


<div id="tabs-4"><?php

$sorteer = "ORDER BY naam ASC";

if( isset( $_POST["soort1"] ) && !empty($_POST["soort1"]) )
{
	$sorteer = "ORDER BY " . $_POST["soort1"];
}

if( isset( $_POST["volgorde1"] ) )
{
	if( $_POST["volgorde1"] == 1 )
	{
		$sorteer .= " ASC";
	}else
	{
		$sorteer .= " DESC";
	}
}

$q_klanten = mysqli_query($conn, "SELECT * FROM kal_leveranciers " . $sorteer);

$sorteer = "";

?>
<form name='frm_overzicht' id='frm_overzicht' method='post' action="leveranciers.php" >
	<input type='hidden' name='tab_id' id='tab_id' value='1' /> 
	<input type='hidden' name='cus_id1' id='cus_id1' />
</form>

<script type='text/javascript'>

function setSort1(soort, volgorde)
{
	document.getElementById("soort1").value = soort;
	document.getElementById("volgorde1").value = volgorde;
	document.getElementById("frm_overzicht_sort1").submit(); 
}

</script>

<form name='frm_overzicht_sort1' id='frm_overzicht_sort1' method='post'>
	<input type='hidden' name='tab_id' id='tab_id' value='2' /> 
	<input type='hidden' name='soort1' id='soort1' value='' /> 
	<input type='hidden' name='volgorde1' id='volgorde1' value='' />
    
    <?php
    
    if( isset($_POST["showall_no"]) )
    {
    ?>
    <input type='hidden' name='showall_no' id='showall_no' value='showall_no' />
    
    <?php
    }
    ?>
    
</form>

<?php

$sort1 = 1;

if( isset( $_POST["volgorde1"] ) && $_POST["volgorde1"] == 1 )
{
	$sort1 = 0;
}
?>

<table cellpadding='0' cellspacing='0' width='100%' border="0">
	<tr style='cursor: pointer;'>
        <td onclick='setSort1("naam", <?php echo $sort1; ?>);' width="250"><b>Naam</b></td>
		<td onclick='setSort1("straat", <?php echo $sort1; ?>);'><b>Straat</b></td>
		<td onclick='setSort1("gemeente", <?php echo $sort1; ?>);'><b>Gemeente</b></td>
	</tr>

	<?php

	$cus = array();

	$i = 0;
	while( $lev = mysqli_fetch_object($q_klanten) )
	{
		$i++;

		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}

		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $lev->id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
        echo "<td onclick='gotoKlant(".$lev->id.")'>". $lev->naam ."</td>";
        echo "<td onclick='gotoKlant(".$lev->id.")'>". $lev->straat ."</td>";
        echo "<td onclick='gotoKlant(".$lev->id.")'>". $lev->postcode . " " . $lev->gemeente ."</td>";	
		echo "</tr>";
	}

	?>

</table>
<br />

</div>

    <!--
	<div id='tabs-10'>
		Uitgebreid zoeken<br/><br/>
		Vul 1 of meerdere velden volledig of gedeeltelijk in om klanten te zoeken.<br/><br/>
		
		<form method='post' name='frm_uit_zoeken' id='frm_uit_zoeken'>
		<table>
		<tr>
			<td> Referte : </td>
			<td> <input type='text' name='z_ref' id='z_ref' /> </td>
		</tr>
        
        <tr>
			<td> Naam : </td>
			<td> <input type='text' name='z_naam' id='z_naam' /> </td>
		</tr>
		
		<tr>
			<td>Bedrijf :</td>
			<td> <input type='text' name='z_bedrijf' id='z_bedrijf' /> </td>
		</tr>
		
		<tr>
			<td>Straat :</td>
			<td> <input type='text' name='z_straat' id='z_straat' /> </td>
		</tr>
		
		<tr>
			<td> Huis nr. : </td>
			<td> <input type='text' name='z_nr' id='z_nr' /> </td>
		</tr>
		
		<tr>
			<td>Postcode :</td>
			<td> <input type='text' name='z_postcode' id='z_postcode' /> </td>
		</tr>
		
		<tr>
			<td> Gemeente : </td>
			<td> <input type='text' name='z_gemeente' id='z_gemeente' /> </td>
		</tr>
		
		<tr>
			<td> E-mail : </td>
			<td> <input type='text' name='z_email' id='z_email' /> </td>
		</tr>
		
		<tr>
			<td> Tel. / GSM : </td>
			<td> <input type='text' name='z_telgsm' id='z_telgsm' /> </td>
		</tr>
        <tr>
			<td> Naam van de bank : </td>
			<td> <input type='text' name='z_bank' id='z_bank' /> </td>
		</tr>
        
        <tr>
			<td> PVZ nr. : </td>
			<td> <input type='text' name='z_pvz' id='z_pvz' /> </td>
			<td> <input type='submit' name='z_zoek' id='z_zoek' value='Zoek' /> </td>
		</tr>
		</table>
		
		<?php 
		
		if( $_SESSION[ $session_var ]->group_id == 5 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='8' />";	
		}
		
		if( $_SESSION[ $session_var ]->group_id == 3 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='7' />";	
		}
		
		if( $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='10' />";
		}
		?>
		</form>
		
		<?php 
		if( isset( $_POST["z_zoek"] ) && $_POST["z_zoek"] == "Zoek" )
		{
			$where = "";
            if( $_POST["z_ref"] != "" && !empty( $_POST["z_ref"] ) )
			{
			    if( strlen( $_POST["z_ref"] ) > 6 )
                {
                    $where = " AND cus_id = '". substr($_POST["z_ref"],6) ."' ";
                }else
                {
                    $where = " AND cus_id = '". $_POST["z_ref"] ."' ";    
                }
			}
            
			if( $_POST["z_naam"] != "" && !empty( $_POST["z_naam"] ) )
			{
				$where = " AND cus_naam LIKE '%". $_POST["z_naam"] ."%' ";
			}
			
			if( isset( $_POST["z_bedrijf"] ) && !empty( $_POST["z_bedrijf"] ) )
			{
				$where .= " AND cus_bedrijf LIKE '%". $_POST["z_bedrijf"] ."%' ";
			}
			
			if( isset( $_POST["z_straat"] ) && !empty( $_POST["z_straat"] ) )
			{
				$where .= " AND cus_straat LIKE '%". $_POST["z_straat"] ."%' ";
			}
			
			if( isset( $_POST["z_nr"] ) && !empty( $_POST["z_nr"] ) )
			{
				$where .= " AND cus_nr LIKE '%". $_POST["z_nr"] ."%' ";
			}
			
			if( isset( $_POST["z_postcode"] ) && !empty( $_POST["z_postcode"] ) )
			{
				$where .= " AND cus_postcode LIKE '%". $_POST["z_postcode"] ."%' ";
			}
			
			if( isset( $_POST["z_gemeente"] ) && !empty( $_POST["z_gemeente"] ) )
			{
				$where .= " AND cus_gemeente LIKE '%". $_POST["z_gemeente"] ."%' ";
			}
			
			if( isset( $_POST["z_email"] ) && !empty( $_POST["z_email"] ) )
			{
				$where .= " AND cus_email LIKE '%". $_POST["z_email"] ."%' ";
			}
            
            if( isset( $_POST["z_bank"] ) && !empty( $_POST["z_bank"] ) )
			{
				$where .= " AND cus_banknaam LIKE '%". $_POST["z_bank"] ."%' ";
			}
			
			if( isset( $_POST["z_telgsm"] ) && !empty( $_POST["z_telgsm"] ) )
			{
				$where .= " AND ( cus_tel LIKE '%". $_POST["z_telgsm"] ."%' OR cus_gsm LIKE '%". $_POST["z_telgsm"] ."%' ) ";
			}
            
            if( isset( $_POST["z_pvz"] ) && !empty( $_POST["z_pvz"] ) )
			{
				$where .= " AND cus_pvz LIKE '%". $_POST["z_pvz"] ."%' ";
			}
			
			$q_zzoek = "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' " . $where ." ORDER BY cus_naam";
			$q_zoek = mysqli_query($conn, $q_zzoek) or die( mysqli_error($conn) );
			
			echo "<hr/>";
			
			if( mysqli_num_rows($q_zoek) == 0 )
			{
				echo "<br/><b>Geen gegevens gevonden.</b><br/><br/>";
			}else
			{
				echo "<br/><b>Klanten gevonden : " . mysqli_num_rows($q_zoek) . "</b><br/><br/>";
			}
			
			echo "<table cellpadding='0' cellspacing='0' width='100%'>";
			
			echo "<tr>";
			echo "<td><b>Naam</b></td>";
			echo "<td><b>Straat </b></td>";
			echo "<td><b>Gemeente </b></td>";
			echo "</tr>";
			
			$i = 1;
			while( $zklant = mysqli_fetch_object($q_zoek) )
			{
				$i++;
		
				$kleur = $kleur_grijs;
				if( $i%2 )
				{
					$kleur = "white";
				}
				
				echo "<tr style='background-color:".$kleur.";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $zklant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_naam;
				echo "</td>";
				
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_straat . " " . $zklant->cus_nr;
				echo "</td>";
				
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_postcode . " " . $zklant->cus_gemeente;
				echo "</td>";
				echo "</tr>";
			}
			
			echo "</table>";
		}
		?>
	</div>
    -->
    
</div>
</div>
<center><?php 

include "inc/footer.php";

?></center>

</body>
</html>