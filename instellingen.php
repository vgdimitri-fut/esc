<?php 

session_start();
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

//******************** factuur velden toevoegen *****************//
if(isset($_POST['factuur']) || isset($_POST['btn_factuur'])){
    if(!isset($_POST['factuur'])){
        $counter = mysqli_query($conn, "SELECT * FROM tbl_product_fields");
        while($field = mysqli_fetch_object($counter)){
            mysqli_query($conn, "UPDATE tbl_product_fields SET factuur=0");
        }
    }else{
        $fields_fac_id = array();
        foreach($_POST['factuur'] as $key => $value){
            mysqli_query($conn, "UPDATE tbl_product_fields SET factuur=1 WHERE id=".$key);
            $fields_fac_id[] = $key;
        }
        $q_non_used = mysqli_query($conn, "SELECT * FROM tbl_product_fields");
        while($fields = mysqli_fetch_object($q_non_used)){
            if(!in_array($fields->id, $fields_fac_id)){
                mysqli_query($conn, "UPDATE tbl_product_fields SET factuur=0 WHERE id=".$fields->id);
            }
        }
    }
    if(isset($_POST['percent'])){
        mysqli_query($conn, "UPDATE kal_instellingen SET fac_percent='".  htmlentities($_POST['percent'])."'");
    }
    if(isset($_POST['voorwaarden'])){
        mysqli_query($conn, "UPDATE kal_instellingen SET fac_voorwaarden='".  htmlentities($_POST['voorwaarden'])."'");
    }
}
//******************** controleer instellingen ********************//
$q_instelling = mysqli_query($conn, "SELECT * FROM kal_instellingen");
$aantal_instellingen = mysqli_num_rows($q_instelling);
//******************** TITEL KIEZEN ********************//
if (isset($_POST["titel_erp"])) {
    $erp = $_POST["titel_erp"];
} else {
    $erp = '';
}
if (isset($_POST["titel_bedrijf"])) {
    $bedrijf = $_POST["titel_bedrijf"];
} else {
    $bedrijf = '';
}
if ($erp != '' || $bedrijf != '') {
    if ($erp != '' && $bedrijf == '') {
        $titel = 2;
    }elseif($erp != '' && $bedrijf != '') {
        $titel = 3;
    }elseif($erp == '' && $bedrijf != ''){
        $titel = 1;
    }
} else {
    $titel = 0;
}
//******************** nieuw bedrijfinformatie ********************//
if( isset( $_POST["Opslaan"] ) && $_POST["Opslaan"] == "Save" && $aantal_instellingen < 1 )
{
    $q_ins = mysqli_query($conn, "INSERT INTO kal_instellingen "
            . "(bedrijf_erp_titel,bedrijf_naam, bedrijf_straat,bedrijf_straatnr,"
            . "bedrijf_postcode,bedrijf_gemeente,bedrijf_email,"
            . "bedrijf_tel,bedrijf_fax,bedrijf_slogan,bedrijf_foto,bedrijf_btw,bedrijf_startjaar,bedrijf_titel) VALUES "
            . "('" . $_POST['erp_naam'] ."','" . $_POST['naam'] ."','".$_POST['straat']."','".$_POST['nummer']."',"
            . "'".$_POST['postcode']."','".$_POST['gemeente']."','".$_POST['email']."',"
            . "'".$_POST['tel']."',. '".$_POST['fax']."','".$_POST['slogan']."','".$_FILES['doc']['name']."','".$_POST['btw']."','".$_POST['sjaar']."',$titel)");
    $update = "Bedrijfsinformatie is toegevoegd.";
}
//******************** bedrijfinformatie aanpassen ********************//
if( isset( $_POST["Opslaan"] ) && $_POST["Opslaan"] == "Save" && $aantal_instellingen > 0 )
{
    //********** file delete **********//
    if(isset($_POST['instellingendoc_del']) && $_POST['instellingendoc_del'] != '')
    {
        // get file name
        $getbestand = mysqli_query($conn, "SELECT bedrijf_foto FROM kal_instellingen");
        $bestand = mysqli_fetch_row($getbestand);
        // delete bestand in de map instellingen_doc
        $bestanddir = "images/" . $bestand[0];
        if(file_exists($bestanddir))
        {
            // delete bestand
            if(unlink( $bestanddir ))
            {
                // sql query update bedrijf_foto
                $q_delete = mysqli_query($conn, "UPDATE kal_instellingen SET bedrijf_foto=''");
            }
        }
    }
    //********** file upload **********//
    if(!empty( $_FILES["doc"]["name"]))
    {
        // file upload naar de map images
        // verander dir naar images
        chdir( "images");
        // upload bestand
        move_uploaded_file($_FILES['doc']['tmp_name'], $_FILES['doc']['name']);
        // sql query update bedrijf foto
        mysqli_query($conn, "UPDATE kal_instellingen SET bedrijf_foto='" . $_FILES['doc']['name'] . "'");
        chdir( "..");
    }
    //********** update andere velden **********//
    $q_update = mysqli_query($conn, "UPDATE kal_instellingen SET bedrijf_erp_titel='".$_POST["erp_naam"]."',bedrijf_naam='" . $_POST['naam'] . "',"
            . "bedrijf_straat='" . $_POST['straat'] . "',"
            . "bedrijf_straatnr='" . $_POST['nummer'] . "',"
            . "bedrijf_postcode='" . $_POST['postcode'] . "',"
            . "bedrijf_gemeente='" . $_POST['gemeente'] . "',"
            . "bedrijf_email='" . $_POST['email'] . "',"
            . "bedrijf_tel='" . $_POST['tel'] . "',"
            . "bedrijf_fax='" . $_POST['fax'] . "',"
            . "bedrijf_slogan='" . $_POST['slogan'] . "',"
            . "bedrijf_btw='" . $_POST['btw'] . "',"
            . "bedrijf_startjaar='" . $_POST['sjaar'] . "',"
            . "bedrijf_titel='" . $titel . "'");
            $update = "Aanpassingen zijn voltooid.";
}

//******************** boekjaar aanpassen ********************//
if( isset( $_POST["Opslaan"] ) && $_POST["Opslaan"] == "Save" && isset($_POST["boekjaar_id"]))
{
    //********** BOEKJAREN **********//
    $bj_teller = 1;
    while($bj_teller <= $_POST["bj_teller"])
    {
        // ID = 0
        $q_zoek_boekjaar = 0;
        // CONTROLEER ID 
        if(isset($_POST["boekjaar_id"][$bj_teller - 1]))
        {
            $q_zoek_boekjaar = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_boekjaar WHERE id=" . $_POST["boekjaar_id"][$bj_teller - 1]));
        }
        // ALS ID > 0
        if($q_zoek_boekjaar > 0 && 1 == 2)
        {
            $get_old_bj = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_boekjaar WHERE id=".$bj_teller));
            $folders = array('cus_docs','facturen','creditnota');
            foreach($folders as $folder){
                $get_paths = array();
                //echo getcwd() . "<br />";
                $get_url = end(explode('/',getcwd()));
                chdir($folder);
                
                
                $yourStartingPath = getcwd();
                $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($yourStartingPath), RecursiveIteratorIterator::SELF_FIRST);

                foreach ($iterator as $file) {
                    if ($file->isDir()) {
                        $path = strtoupper($file->getRealpath());
                        $path2 = PHP_EOL;
                        $path3 = $path . $path2;
                        $result = end(explode('/', $path3));

                        if($get_old_bj->boekjaar_start != changeDate2EU($_POST["startjaar$bj_teller"]) || $get_old_bj->boekjaar_einde != changeDate2EU($_POST["eindjaar$bj_teller"])){
                            /* look for old or not changed boekjaar */
                            if(strpos($path3,$get_old_bj->boekjaar_start ." - " .$get_old_bj->boekjaar_einde)){

                                /* get folder path */
                                $folder_path = str_replace(getcwd(),'',strtolower($path));

                                $get_paths[] = substr($folder_path,1);
                            }
                        }
                    }
                }
                $paths = array();
                $paths = array_unique($get_paths);
                foreach($paths as $key => $value){
                    chdir($value);
                    chdir('../');
                    rename($get_old_bj->boekjaar_start." - ".$get_old_bj->boekjaar_einde,changeDate2EU($_POST["startjaar$bj_teller"])." - ".changeDate2EU($_POST["eindjaar$bj_teller"]));
                    /* go back to startfolder */
                    $count_path = 0;
                    $tmp_array = explode('/',$value);
                    $count_path = count($tmp_array);
                    for($i=0;$i<$count_path;$i++){
                        chdir('../');
                    }
                    //echo getcwd()."<br />";
                }
            }
            /* if path is not correct , fix it */
            $current_url = end(explode('/',getcwd()));
            if($current_url != 'beheer'){
                chdir('../');
            }
            
            // UPDATE boekjaar
            $q_update_boekjaar = mysqli_query($conn, "UPDATE kal_boekjaar SET "
                    . "boekjaar_start='". changeDate2EU($_POST["startjaar$bj_teller"]) . "',"
                    . " boekjaar_einde='". changeDate2EU($_POST["eindjaar$bj_teller"]) . "' "
                    . "WHERE id='". $_POST["boekjaar_id"][$bj_teller - 1] . "'");
        }else{
            // ANDERS INSERT
            $tmp = "INSERT INTO kal_boekjaar (boekjaar_start, boekjaar_einde) "
                    . "VALUES ('".changeDate2EU($_POST["startjaar$bj_teller"]) ."','".changeDate2EU($_POST["eindjaar$bj_teller"])."' )";
            $q_insert_boekjaar = mysqli_query($conn, $tmp);
        }
        $bj_teller += 1;
    }
}

//******************** bank toevoegen ********************//
if( isset( $_POST["Opslaan"] ) && $_POST["Opslaan"] == "Save" && isset($_POST["bank_iban"]))
{
    //********** BANK **********//
    if(isset($_POST["bank_iban"]) && $_POST["bank_iban"] != '' )
    {
        $q_insertbank = mysqli_query($conn, "INSERT INTO kal_bank (bank_naam,iban,bic,reknr,soort) VALUES ('".$_POST['bank_naam']."','".$_POST['bank_iban']."','".$_POST['bank_bic']."','".$_POST['bank_reknr']."','instellingen')");
    }
}

//******************** Websites informatie opslaan ********//
if( isset ( $_POST['btn_website']))
{
    mysqli_query($conn, "UPDATE kal_instellingen SET website_auto='".$_POST['txt_informatie']."',"
            . " twee_user='".$_POST['txt_twee_user']."', "
            . "twee_pwd='".$_POST['txt_twee_pwd']."', "
            . "kapaza_user='".$_POST['txt_kapaza_user']."',"
            . " kapaza_pwd='".$_POST['txt_kapaza_pwd']."',"
            . " autovlan_ftp='".$_POST['txt_autovlan_ftp']."',"
            . " autovlan_ftp_user='".$_POST['txt_autovlan_ftp_user']."',"
            . " autovlan_ftp_pwd='".$_POST['txt_autovlan_ftp_pwd']."',"
            . " autovlan_user='".$_POST['txt_autovlan_user']."',"
            . " autovlan_pwd='".$_POST['txt_autovlan_pwd']."',"
            . " autovlan_user_id='".$_POST['txt_autovlan_id']."',"
            . " autoscout_ftp='".$_POST['txt_autoscout_ftp']."',"
            . " autoscout_ftp_user='".$_POST['txt_autoscout_ftp_user']."',"
            . " autoscout_ftp_pwd='".$_POST['txt_autoscout_ftp_pwd']."',"
            . " autoscout_user='".$_POST['txt_autoscout_user']."',"
            . " autoscout_pwd='".$_POST['txt_autoscout_pwd']."',"
            . " autoscout_user_id='".$_POST['txt_autoscout_user_id']."'");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
Settings<?php include "inc/erp_titel.php" ?>
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
	<style>
input[type=checkbox] {
}
 
input[type=checkbox] + label,input#titel_bedrijf[type=checkbox]:checked + label,input#titel_erp[type=checkbox]:checked + label
{
    background: url(images/ok16.png);
    height: 14px;
    width: 16px;
    display:inline-block;
    padding: 0 0 0 0px;
}
input[type=checkbox]:checked + label,input#titel_bedrijf[type=checkbox] + label,input#titel_erp[type=checkbox] + label
{
    background: url(images/delete.png);
    height: 14px;
    width: 16px;
    display:inline-block;
    padding: 0 0 0 0px;
}
</style>
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="js/jquery.validate.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>

<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript">

function gotoKlant(cus_id1)
{
	document.getElementById("user_id1").value = cus_id1;
	document.getElementById("frm_overzicht").submit();	
}
$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});

$(document).ready(function(){
        $('.bankCalculator').click(function(){
            var iban = $('#bank_iban').val();
                var post_iban = $.post("klanten/klanten_calculator.php",{iban: iban});
                post_iban.done(function( data ){
                    var bankarray = data.split(',');
                    if(bankarray[0].indexOf('safe_mode') > 0){
                        var n = bankarray[0].lastIndexOf("<br />");
                        $('#bank_naam').val(bankarray[0].substring(n+6));
                    }else{
                        $('#bank_naam').val(bankarray[0]);
                    }
                    $('#bank_bic').val(bankarray[1]);
                    $('#bank_iban').val(bankarray[2]);
                    if(bankarray[3] != '')
                    {
                        $('#bank_reknr').val(bankarray[3]);
                    }
                });
            return false;
        });
        // Boekjaar overzicht toggle
         $('#open_bj').click(function(){
            $('tr[class^=boekjaar]').toggle();
            return false;
        });
        // boekjaar onzichtbaar maken
        $('tr[class^=boekjaar]').hide();
        
        
        // banknummer verwijderen
         $('.deletebank').click(function(){
             if(confirm("Verwijderen?")){
                var bank_id = $(this).parent().attr("class");
                var post_bank = $.post("ajax/instellingen_ajax.php",{id: bank_id, delete: 'delete'});
                post_bank.done(function( data ) {
                   // verwijder rij
                   $('.' + bank_id).remove()
               });
            }
             return false;
         });
        // Bank toevoegen toggle
         $('#open_bank').click(function(){
             // toggle banknaam
             $(this).parent().parent().next().toggle();
             // toggle iban
             $(this).parent().parent().next().next().toggle();
             // toggle bic
             $(this).parent().parent().next().next().next().toggle();
             // toggle reknr
             $(this).parent().parent().next().next().next().next().toggle();
             return false;
        });
        // toggle banknaam
             $('#open_bank').parent().parent().next().hide();
             // toggle iban
             $('#open_bank').parent().parent().next().next().hide();
             // toggle bic
             $('#open_bank').parent().parent().next().next().next().hide();
              // toggle reknr
             $('#open_bank').parent().parent().next().next().next().next().hide();
        
	$("#frm_mod_user").validate();
        $('.reactie').fadeOut(5000);
        
        
        // DATE PICKER START EN EIND JAAR
        $( "input[id^=startjaar]" ).datepicker( { dateFormat: 'dd-mm-yy' } );
        $( "input[id^=eindjaar]" ).datepicker( { dateFormat: 'dd-mm-yy' } );
        // TOEVOEGEN VAN EXTRA START EN EIND JAAR
        $('.add_boekjaar').click(function(){
            var boekjaar = $('#bj_teller').val();
            var boekjaar_plus = parseInt(boekjaar) + 1;
            // voeg een nieuwe rij toe
            $('.boekjaar' + boekjaar).after("  <tr class='boekjaar" + boekjaar_plus  + "'>\n\
                                        <td>Start-end FY " + boekjaar_plus + ":</td>\n\
                                        <td>\n\
                                            <input type='text' size='10' class='required' name='startjaar" + boekjaar_plus + "' id='startjaar" + boekjaar_plus + "' <?php if(isset($row[1])){echo "value='" . $row[11] . "'";} ?> />\n\
                                            - <input type='text' size='10' class='required' name='eindjaar" + boekjaar_plus + "' id='eindjaar" + boekjaar_plus + "' <?php if(isset($row[1])){echo "value='" . $row[11] . "'";} ?> />\n\
                                        </td>\n\
                                    </tr>");
            $( "#startjaar" + boekjaar_plus ).datepicker( { dateFormat: 'dd-mm-yy' } );
            $( "#eindjaar" + boekjaar_plus ).datepicker( { dateFormat: 'dd-mm-yy' } );                                    
            // teller verhogen
            $('#bj_teller').val(boekjaar_plus);
            return false;
        });
        // KLIKKEN OP AANPASSEN
        $('#Opslaan').click(function(){
            var counter = 0;
            var vorige_eindjaar = $( "input[id^=eindjaar]" ).eq(0).val();
            var bj_error = 0;
            var vej_id = 0;
            // voor alle startjaren
            $( "input[id^=startjaar]" ).each(function(){
                // startjaar datum
                var startjaar = $(this).val();
                // ID zoeken
                var data = $(this).attr('name');
                // huidige ID
                var id = data.substr(9);
                // waarde eindjaar1,2,3,...
                var eindjaar = $('#eindjaar' + id).val();
                // convert datums
                var sj_conv = new Date( startjaar.replace( /(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3") );
                var ej_conv = new Date( eindjaar.replace( /(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3") );
                var vej_conv = new Date( vorige_eindjaar.replace( /(\d{2})-(\d{2})-(\d{4})/, "$2/$1/$3") );
                // niet eerste keer 
                if(counter != 0)
                {
                        if(sj_conv >= ej_conv)
                        {
                            // startjaar > eindjaar
                            // kleur rood
                            $(this).css("border-color", "red");
                            $("#eindjaar" + id).css("border-color", "red");
                            // error + 1
                            bj_error++;
                        }else 
                            if(sj_conv <= vej_conv)
                            {
                                // startjaar < vorige eindjaar
                                // kleur rood
                                $(this).css("border-color", "red");
                                $("#eindjaar" + vej_id).css("border-color", "red");
                                // error  +1
                                bj_error++;
                            }else 
                                if(bj_error < 1){
                                    // correcte datums
                                    // eindjaar rood verwijderen
                                    $("#eindjaar" + id).css("border-color", "");
                                    $("#eindjaar" + vej_id).css("border-color", "");
                                    // startjaar rood verwijderen
                                    $(this).css("border-color", "");
                                }
                }else{
                    // eerste keer
                    var start = new Date(startjaar);
                    var einde = new Date(vorige_eindjaar);
                    if(start <= einde)
                    {
                        $(this).css("border-color", "red");
                        $("#eindjaar" + id).css("border-color", "red");
                        bj_error++;
                    }else{
                        $(this).css("border-color", "");
                    }
                }
                counter++;
                // update vorige_eindjaar
                vorige_eindjaar = eindjaar;
                vej_id = id;
            });
            // als er fouten zijn
            if(bj_error>0)
            {
                return false;
            }
        })
    });
$(document).ready(function(){
	$("#frm_nieuwegebruiker").validate();
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

<div id='pagewrapper'>
	<?php include('inc/header.php'); ?>
	
	<h1>Settings<?php include "inc/erp_titel.php" ?></h1>
	
	<div id="tabs">
		<ul>
			<li>
                            <a href="#tabs-1">Company information</a><a href="instellingen.php">
                                <img width="16px" height="16px" border="0" title="Vernieuw" alt="Vernieuw" src="images/refresh.png">
                            </a>
                        </li>
                        <!--
                        <li>
                            <a href="#tabs-2">Informatie auto websites</a>
                        </li>
                        -->
                        <li>
                            <a href="#tabs-3">Bill information</a>
                        </li>
		</ul>
		<div id="tabs-1">
			
			<?php 	
			$result = mysqli_query($conn, "SELECT * FROM kal_instellingen");
                        $row = mysqli_fetch_array($result);
			?>
                        <form id='frm_nieuwegebruiker' name='frm_nieuwegebruiker' method='post' enctype="multipart/form-data" >
			<table>
                                <tr>
					<td>Company name :</td>
					<td>
                                                <input type='text' class='lengte required' name='naam' id='naam' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_naam"] . "'";} ?> />
                                                <input type='checkbox' name='titel_bedrijf' id='titel_bedrijf' <?php if($row["bedrijf_titel"] == 1 || $row["bedrijf_titel"] == 3)echo "checked"; ?>/><label for="titel_bedrijf"></label><span style="position:absolute;float:right;margin-left:10px;margin-top:15px;font-weight:bold;"> > Titel</span>
					</td>
				</tr>
                                <tr>
					<td>Erp title :</td>
					<td>
                                                <input type='text' class='lengte required' name='erp_naam' id='erp_naam' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_erp_titel"] . "'";} ?> />
                                                <input type='checkbox' name='titel_erp' id='titel_erp' <?php if($row["bedrijf_titel"] > 1)echo "checked"; ?>/><label title="Toevoegen aan de titel" for="titel_erp"></label>
					</td>
				</tr>
				<tr>
					<td>Street :</td>
					<td>
						<input type='text' class='lengte required' name='straat' id='straat' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_straat"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
					<td>Number :</td>
					<td>
						<input type='text' class='lengte required' name='nummer' id='nummer' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_straatnr"] . "'";} ?> />
					</td>
				</tr>
				<tr>
					<td>Zipcode :</td>
					<td>
						<input type='text' class='lengte required' name='postcode' id='postcode' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_postcode"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
					<td>City :</td>
					<td>
						<input type='text' class='lengte required' name='gemeente' id='gemeente' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_gemeente"] . "'";} ?> />
					</td>
				</tr>
				<tr>
					<td>E-mail :</td>
					<td>
						<input type='text' class='lengte required email' name='email' id='email' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_email"] . "'";} ?> />
					</td>
				</tr>
				
                                <tr>
					<td>Telephone :</td>
					<td>
						<input type='text' class='lengte required' name='tel' id='tel' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_tel"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
					<td>Fax :</td>
					<td>
						<input type='text' class='lengte required' name='fax' id='fax' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_fax"] . "'";} ?> />
					</td>
				</tr>
                
				<tr>
					<td>Slogan :</td>
					<td>
						<input type='text' class='lengte required' name='slogan' id='slogan' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_slogan"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
					<td>VAT :</td>
					<td>
						<input type='text' class='lengte required' name='btw' id='btw' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_btw"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
					<td>Start fiscal year :</td>
					<td>
						<input type='text' class='lengte required' name='sjaar' id='sjaar' <?php if(isset($row["bedrijf_naam"])){echo "value='" . $row["bedrijf_startjaar"] . "'";} ?> />
					</td>
				</tr>
                                <tr>
                                    <td>
                                        <input type="button" name="bank_open" id="open_bank" value="Bank toevoegen"/>
                                    </td>
                                </tr>
                                <tr>
					<td>Name of bank :</td>
					<td>
						<input type='text' class='lengte' name='bank_naam' id='bank_naam' value='' />
					</td>
				</tr>
                                <tr>
					<td>IBAN :<a href='' style='color:seagreen;' class='bankCalculator'>Calculator</a></td>
					<td>
						<input type='text' class='lengte' name='bank_iban' id='bank_iban' value='' />
					</td>
				</tr>
                                <tr>
					<td>BIC :</td>
					<td>
						<input type='text' class='lengte' name='bank_bic' id='bank_bic' value='' />
					</td>
				</tr>
                                <tr>
					<td>Account nr. :</td>
					<td>
						<input type='text' class='lengte' name='bank_reknr' id='bank_reknr' value='' />
					</td>
				</tr>
                                <?php
                                $q_bank = mysqli_query($conn, "SELECT * FROM kal_bank");
                                while($bankdata = mysqli_fetch_object($q_bank))
                                {
                                    echo "<tr><td colspan='2'>";
                                    echo "<span class='".$bankdata->id."'>";
                                    echo "<a href='' class='deletebank' alt='".$bankdata->id."'>";
                                    echo "<img src='images/delete.png' name='bankdelete' title='".$bankdata->id."'/>";
                                    echo "</a>";
                                    echo $bankdata->bank_naam . " (IBAN:" . $bankdata->iban . ")</span>";
                                    echo "</td></tr>";
                                }
                                
                                ?>
                                <tr>
                                    <td>
                                        <input type="button" name="open_bj" id="open_bj" value="Fiscal year"/>
                                    </td>
                                </tr>
                                <?php
                                $q_boekjaar = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                                $aantal_boekjaren = mysqli_num_rows($q_boekjaar);
                                $boekjaar_teller = 0;
                                if($aantal_boekjaren > 0)
                                {
                                    while($boekjaar = mysqli_fetch_object($q_boekjaar))
                                    {
                                        $boekjaar_teller += 1;
                                        echo '<tr class="boekjaar'. $boekjaar_teller . '">';
                                        echo '<td>Start-Eind FY '. $boekjaar_teller . ':</td>';
                                        echo '<td>';
                                        echo "<input type='text' size='10' name='startjaar". $boekjaar_teller . "' id='startjaar". $boekjaar_teller . "' value='" . changeDate2EU($boekjaar->boekjaar_start) ."'  />";
                                        echo "<input type='hidden' name='boekjaar_id[]' value='". $boekjaar->id . "' >";
                                        echo " - <input type='text' size='10' name='eindjaar". $boekjaar_teller . "' id='eindjaar". $boekjaar_teller . "' value='" . changeDate2EU($boekjaar->boekjaar_einde) . "' />";
                                        if($boekjaar_teller < 2){
                                            echo "<a class='add_boekjaar' href='' style='color:blue;margin-left:10px'><b>+</b></a>";
                                        }
                                        echo '</td>';
                                        echo '</tr>';

                                    }
                                }else{
                                    $boekjaar_teller += 1;
                                    echo '<tr class="boekjaar1">';
                                    echo '<td>Start-Eind FY 1:</td>';
                                    echo '<td>';
                                    echo "<input type='text' size='10' name='startjaar1' id='startjaar1' value=''  />";
                                    echo "<input type='hidden' name='boekjaar_id[]' value='1' >";
                                    echo " - <input type='text' size='10' name='eindjaar1' id='eindjaar1' value='' />";
                                    echo "<a class='add_boekjaar' href='' style='color:blue;margin-left:10px'><b>+</b></a>";
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                ?>
                                <!--  AAAAAAAAAAAAAAAAAAA -->
                                <tr><td><strong>Upload photo header :</strong> </td><td> <input type='file' name='doc' id='doc' /> </td></tr>
                                
                                <?php
                                // als er een bedrijfsinformatie is
                                if( $row["bedrijf_foto"] != '' )
                                {
                                    echo "<tr><td align='left' valign='top'><b>Foto:</b></td>";
                                    
                                    if( file_exists( "images/" . $row["bedrijf_foto"] ) )
                                    {
                                          echo "<td align='left'>";
                                          echo "Delete?<input type='checkbox' name='instellingendoc_del' id='instellingendoc_". $row["bedrijf_foto"] ."' /><label for='instellingendoc_". $row["bedrijf_foto"] ."'></label>";
                                          echo "<a href='images/" . $row["bedrijf_foto"] . "' target='_blank' >";
                                          echo $row["bedrijf_foto"];
                                          echo "</a>";
                                          echo "</td>";
                                          echo "</tr>";
                                     }
                                 }
                                ?>
			</table>
<!--			<table>
                            <tr>
                                <td>
                                    <b>Kies een theme:</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="radio" id="start" name="theme" value="Start" />Start
                                </td>
                                <td>
                                    <input type="radio" id="dark" name="theme" value="Dark" />Dark
                                </td>
                                <td>
                                    <input type="radio" id="cupertino" name="theme" value="Cupertino" />Cupertino
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="images/theme_90_start_menu.png" />
                                </td>
                                <td>
                                    <img src="images/theme_90_dark_hive.png" />
                                </td>
                                <td>
                                    <img src="images/theme_90_cupertino.png" />
                                </td>
                            </tr>
                        </table>-->
			<br/>
			
			<table width='320'>
				<tr>
                                        <td align='center'> <input type='submit' name='Opslaan' id='Opslaan' value='Save'/> 
                                        </td>
				</tr>
			</table>
                        <input type='hidden' id='bj_teller' name='bj_teller' value='<?php echo $boekjaar_teller ?>'/>
			</form>
                        <span class='reactie' style='color:green'><?php if(isset($update))echo $update ?></span>
			
		</div>
        
            <!--
            <div id="tabs-2">
                <form method="post">
                    <h3>Login detail websites</h3>
                    <textarea id="informatie_auto" rows="10" cols="70" name="txt_informatie"><?php echo $row["website_auto"]; ?> </textarea><br />
                    <h3>2dehands.be</h3>
                    <label>2dehands username: </label>
                    <input type="text" name="txt_twee_user" value="<?php echo $row["twee_user"]; ?>" /><br />
                    <label>2dehands paswoord: </label>
                    <input type="text" name="txt_twee_pwd" value="<?php echo $row["twee_pwd"]; ?>" /><br />
                    <h3>kapaza.be</h3>
                    <label>Kapaza username: </label>
                    <input type="text" name="txt_kapaza_user" value="<?php echo $row["kapaza_user"]; ?>" /><br />
                    <label>Kapaza paswoord: </label>
                    <input type="text" name="txt_kapaza_pwd" value="<?php echo $row["kapaza_pwd"]; ?>" /><br />
                    <h3>Autovlan.be</h3>
                    <label>Autovlan ID: </label>
                    <input type="text" name="txt_autovlan_id" value="<?php echo $row["autovlan_user_id"]; ?>" /><br />
                    <label>Autovlan username: </label>
                    <input type="text" name="txt_autovlan_user" value="<?php echo $row["autovlan_user"]; ?>" /><br />
                    <label>Autovlan password: </label>
                    <input type="text" name="txt_autovlan_pwd" value="<?php echo $row["autovlan_pwd"]; ?>" /><br />
                    <label>Autovlan FTP: </label>
                    <input type="text" name="txt_autovlan_ftp" value="<?php echo $row["autovlan_ftp"]; ?>" /><br />
                    <label>Autovlan FTP username: </label>
                    <input type="text" name="txt_autovlan_ftp_user" value="<?php echo $row["autovlan_ftp_user"]; ?>" /><br />
                    <label>Autovlan FTP password: </label>
                    <input type="text" name="txt_autovlan_ftp_pwd" value="<?php echo $row["autovlan_ftp_pwd"]; ?>" /><br />
                    <h3>Autoscout24.be</h3>
                    <label>Autoscout24 ID: </label>
                    <input type="text" name="txt_autoscout_user_id" value="<?php echo $row["autoscout_user_id"]; ?>" /><br />
                    <label>Autoscout24 username: </label>
                    <input type="text" name="txt_autoscout_user" value="<?php echo $row["autoscout_user"]; ?>" /><br />
                    <label>Autoscout24 password: </label>
                    <input type="text" name="txt_autoscout_pwd" value="<?php echo $row["autoscout_pwd"]; ?>" /><br />
                    <label>Autoscout24 FTP: </label>
                    <input type="text" name="txt_autoscout_ftp" value="<?php echo $row["autoscout_ftp"]; ?>" /><br />
                    <label>Autoscout24 FTP username: </label>
                    <input type="text" name="txt_autoscout_ftp_user" value="<?php echo $row["autoscout_ftp_user"]; ?>" /><br />
                    <label>Autoscout24 FTP password: </label>
                    <input type="text" name="txt_autoscout_ftp_pwd" value="<?php echo $row["autoscout_ftp_pwd"]; ?>" /><br />
                    
                    <input type="submit" name="btn_website" id="btn_website" value="Opslaan"/>
                    <input type='hidden' name='tab_id' value='1' /> 
                </form>
            </div>
            -->
            
            <div id="tabs-3">
                <h3>Billing arrangement</h3>
<!--                <p>Select fields to align a bill:</p>-->
                <form method="POST">
                <?php
                    $q_fields = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id!=55 AND id!=67 AND id!=60 AND id!=69 AND id!=75 AND id!=80");
                    while($field = mysqli_fetch_object($q_fields)){
                        $checked = '';
                        if($field->factuur == 1){
                            $checked = 'checked';
                        }else{
                            $checked = '';
                        }
                        echo "<input type='checkbox' name='factuur[".$field->id."]' value=".$field->field." ".$checked.">".$field->field."<br />";
                    }
                ?>
                    <p>At 0% procent sales:</p>
                    <textarea style='width:300px;' name='percent' id='percent'><?php if(isset($row["fac_percent"])){echo $row["fac_percent"];} ?></textarea>
                    <p>Terms and conditions:</p>
                    <textarea style='width:300px;' name='voorwaarden' id='percent'><?php if(isset($row["fac_voorwaarden"])){echo $row["fac_voorwaarden"];} ?></textarea>
                    <input type="submit" name="btn_factuur" id="btn_factuur" value="Save"/>
                    <input type='hidden' name='tab_id' value='2' /> 
                </form>
            </div>
	</div>
</div>

<center>
<?php 
                                    
include "inc/footer.php";

?>
</center>

</body>
</html>