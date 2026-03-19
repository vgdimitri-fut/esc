<?php
session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

include "inc/watch_start.php";


//  echo "<pre>";
//  var_dump( $_POST );
//  echo "</pre>";

if (isset($_POST['save_oa']) && $_POST['save_oa'] == "Aantal panelen OA opslaan") {
    foreach ($_POST as $post => $waarde) {
        if (substr($post, 0, 4) == "p_oa") {
            $cus_id = substr($post, 5);
            $q_upd = "UPDATE kal_customers SET cus_aant_panelen_fac = " . $waarde . " WHERE cus_id = " . $cus_id;
            mysqli_query($conn, $q_upd) or die(mysqli_error($conn));
        }
    }
}


include "inc/phpmailer.inc.php";

if (isset($_POST["volgende1"]) && $_POST["volgende1"] == "Volgende") {
    $klanten = array();
    $mail_arr = array();

    foreach ($_POST as $key => $post) {
        //echo "<br>" . $key . " " . $post;

        if (substr($key, 0, 7) == "bedrag_") {
            $id = substr($key, 7);
            $klanten[$id] = array("bedrag" => $post, "intrest" => $_POST["intrest_" . $id]);

            if (isset($_POST["mail_" . $id])) {
                $mail_arr[$id] = $id;
            }
        }
    }
    /*
      echo "<pre>";
      var_dump( $_POST );
      var_dump( $mail_arr );
      var_dump( $klanten );
      echo "</pre>";
      die("EINDE");
     */

    require "inc/fpdf.php";
    require "inc/fpdi.php";

    foreach ($klanten as $cus_id => $bedrag) {
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
        $pdf->SetTextColor(0, 0, 0);

        //ophalen van de gegevens van de klant
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cus_id));

        $pdf->Text(110, 55, "Aan:");

        if ($klant->uit_cus_id != 0) {
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);

            if (mysqli_num_rows($q_hoofdklant) > 0) {
                $hoofdklant = mysqli_fetch_object($q_hoofdklant);

                $klant->cus_naam = html_entity_decode(trim($hoofdklant->cus_naam), ENT_QUOTES);
                $klant->cus_bedrijf = html_entity_decode(trim($hoofdklant->cus_bedrijf), ENT_QUOTES);
                $klant->cus_straat = html_entity_decode(trim($hoofdklant->cus_straat), ENT_QUOTES);
                $klant->cus_gemeente = html_entity_decode(trim($hoofdklant->cus_gemeente), ENT_QUOTES);
                $klant->cus_postcode = $hoofdklant->cus_postcode;
                $klant->cus_nr = $hoofdklant->cus_nr;
            } else {
                $klant->cus_naam = html_entity_decode(trim($klant->cus_naam), ENT_QUOTES);
                $klant->cus_bedrijf = html_entity_decode(trim($klant->cus_bedrijf), ENT_QUOTES);
                $klant->cus_straat = html_entity_decode(trim($klant->cus_straat), ENT_QUOTES);
                $klant->cus_gemeente = html_entity_decode(trim($klant->cus_gemeente), ENT_QUOTES);
            }
        } else {
            $klant->cus_naam = html_entity_decode(trim($klant->cus_naam), ENT_QUOTES);
            $klant->cus_bedrijf = html_entity_decode(trim($klant->cus_bedrijf), ENT_QUOTES);
            $klant->cus_straat = html_entity_decode(trim($klant->cus_straat), ENT_QUOTES);
            $klant->cus_gemeente = html_entity_decode(trim($klant->cus_gemeente), ENT_QUOTES);
        }

        if ($klant->cus_naam == $klant->cus_bedrijf) {
            $pdf->Text(110, 60, trim($klant->cus_naam));
        } else {
            $pdf->Text(110, 60, trim($klant->cus_naam) . " " . trim($klant->cus_bedrijf));
        }

        $pdf->Text(110, 65, trim($klant->cus_straat) . " " . trim($klant->cus_nr));
        $pdf->Text(110, 70, trim($klant->cus_postcode) . " " . trim($klant->cus_gemeente));

        $pdf->Text(1, 100, ".");
        $pdf->Text(209, 100, ".");

        $field1 = "Tessenderlo " . $_POST["aanm_datum"];
        $pdf->Text(130, 110, $field1);

        // het aantal aanmaningen ook vermelden in de titel
        // tellen van het aantal aanmanignen en deze ook vermelden in de filename
        $aantal_aanmaningen = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $klant->cus_id . " AND aa_factuur = '" . $_POST["fac_nr_" . $klant->cus_id] . "'"));
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

        $title = $verkleinen[$aantal_aanmaningen] . " herinnering onbetaald factuur";
        $pdf->Text(20, 110, $title);

        $field2 = "Beste,";
        $pdf->Text(21, 130, $field2);

        $field3 = "Bij nazicht van onze boekhouding blijkt dat uw factuur tot op heden onbetaald bleef. De vervaldatum is overschreden.";
        $pdf->SetXY(20, 140);
        $pdf->MultiCell(160, 5, $field3, 0, 'L');

        $field4 = "Allicht bent u deze betaling uit het oog verloren.";
        $pdf->Text(21, 160, $field4);


        // INIT
        $offsetmin = 0;
        $offset = 0;

        if ($aantal_aanmaningen == 1) {
            $field5 = "Mogen wij u vragen om deze betaling alsnog zo snel mogelijk uit te voeren om op deze manier extra kosten te vermijden. Vanaf de volgende aanmaning zal de interest vermeld in onze algemene voorwaarden toegepast worden.";
            $pdf->SetXY(20, 167);
            $pdf->MultiCell(160, 5, $field5, 0, 'L');
        } else {
            $offsetmin = 22;
        }

        $field6 = "Indien de betaling reeds werd uitgevoerd, gelieve dit schrijven dan als nietig te verklaren.";
        $pdf->Text(21, 192 - $offsetmin, $field6);

        $field7 = "Openstaand bedrag : " . iconv("UTF-8", "cp1250", "€") . " " . str_replace(".", ",", $bedrag["bedrag"]);
        $pdf->Text(21, 202 - $offsetmin, $field7);

        if (!empty($bedrag["intrest"]) && $bedrag["intrest"] > 0) {
            $offset = 12;
            $regel = "Zoals in voorgaande brief vermeld passen wij vanaf heden onze algemene voorwaarden toe.";
            $pdf->Text(21, 207 - $offsetmin, $regel);

            $regel = "Intrest : " . iconv("UTF-8", "cp1250", "€") . " " . str_replace(".", ",", $bedrag["intrest"]);
            $pdf->Text(21, 212 - $offsetmin, $regel);

            $regel = "Totaal openstaand bedrag : " . iconv("UTF-8", "cp1250", "€") . " " . str_replace(".", ",", $bedrag["intrest"] + $bedrag["bedrag"]);
            $pdf->Text(21, 217 - $offsetmin, $regel);
        }

        if ($aantal_aanmaningen == 3) {
            $pdf->Text(21, 222 - $offsetmin, "Dit is de laatste aanmaning alvorens de gegevens doorgestuurd worden naar de advocaat.");
            $offset += 3;
        }

        $field8 = "Alvast bedankt,";
        $pdf->Text(21, 222 + $offset - $offsetmin, $field8);

        $field9 = "Met vriendelijke groeten,";
        $pdf->Text(21, 232 + $offset - $offsetmin, $field9);

        $field10 = "De boekhouding";
        $pdf->Text(21, 237 + $offset - $offsetmin, $field10);

        // enkel het eerste gedeelte nemen van de filename
        $tmp_fac_nr = explode(".", $_POST["fac_nr_" . $klant->cus_id]);

        $factuur = $pdf->Output('aanmaning_' . str_replace(" ", "_", trim($klant->cus_naam)) . '.pdf', "S");

        $filename = 'aanmaning_' . $aantal_aanmaningen . "_" . $tmp_fac_nr[0] . "_" . replaceToNormalChars(str_replace(" ", "_", trim($klant->cus_naam))) . "_" . $_POST["aanm_datum"] . '.pdf';

        chdir("aanmaningen/");
        $fp1 = fopen($filename, 'w');
        fwrite($fp1, $factuur);
        fclose($fp1);
        chdir("../");

        // bijhouden van de gestuurde aanmaningen

        /*
         * Niet eerst zoeken ofdat deze regel al bestaat omdat er een historiek moet zijn en het aantal aanmaningen is belangrijk.
         */
        $q = "INSERT INTO tbl_aanmaningen(aa_cus_id, 
		                                  aa_datum, 
		                                  aa_bedrag, 
		                                  aa_factuur,
		                                  aa_filename) 
		                          VALUES('" . $klant->cus_id . "',
		                                 '" . $_POST["aanm_datum"] . "',
		                                 '" . $bedrag["bedrag"] . "',
		                                 '" . $_POST["fac_nr_" . $klant->cus_id] . "',
		                                 '" . $filename . "')";
        mysqli_query($conn, $q) or die(mysqli_error($conn));

        //mailen naar de klant en naar admin
        if (isset($mail_arr[$klant->cus_id])) {
            unset($mail);

            $mail = new PHPMailer();

            $mail->From = "administratie@futech.be";
            $mail->FromName = "Futech - administratie";
            $mail->Subject = "Herinnering";
            //$mail->IsSMTP(); 

            $mail->Host = "192.168.1.250";
            $mail->Mailer   = "smtp";
            $mail->IsHTML(false); // send as HTML
            //$mail->Mailer   = "smtp"; 
            // HTML body 
            $body = "Beste,<br/><br/>

Bij nazicht van onze boekhouding blijkt dat uw factuur tot op heden onbetaald bleef. De<br/>
vervaldatum is overschreden.<br/>
Allicht bent u deze betaling uit het oog verloren.<br/><br/> 

Zie bijlage.<br/><br/>

Alvast bedankt,<br/>
Met vriendelijke groeten,<br/>
De boekhouding<br/><br/>

FUTECH BVBA<br/>
Ambachtstraat 19<br/>
3980 Tessenderlo<br/>
BE 0808 765 108 ";


            // Plain text body (for mail clients that cannot read HTML) 
            $text_body = "
Beste,

Bij nazicht van onze boekhouding blijkt dat uw factuur tot op heden onbetaald bleef. De
vervaldatum is overschreden.
Allicht bent u deze betaling uit het oog verloren. 

Zie bijlage.
Of indien de bijlage niet open gaat, klik dan op deze link : 
http://www.solarlogs.be/kalender/aanmaningen/" . $filename . "

Alvast bedankt,
Met vriendelijke groeten,
De boekhouding

FUTECH BVBA
Ambachtstraat 19
3980 Tessenderlo
BE 0808 765 108 ";

            $body = $text_body;

            $mail->Body = $body;
            $mail->AltBody = $text_body;


            $mail->AddAddress($klant->cus_email, $klant->cus_naam);
            $mail->AddBCC("administratie@futech.be", "Futech - administratie");


            //$mail->AddAddress("dimitri@futech.be");

            $mail->AddAttachment('aanmaningen/' . $filename);
            $mail->Send();
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>Facturatie<?php include "inc/erp_titel.php" ?></title>

        <link href="css/style.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

        <link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>


        <script type="text/javascript" src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
        <link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

        <script type="text/javascript" src="js/jquery.autocomplete.js"></script>

        <script type="text/javascript" src="js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="js/jquery.ui.widget.js"></script>
        <script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
        <script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
        <script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
        <script type="text/javascript" src="js/jquery.validate.js"></script>

        <script type="text/javascript" src="js/functions.js"></script>
        <script type="text/javascript" src="js/facturatie.js"></script>
        <script type="text/javascript">

            
            function maakfactuurOpstal()
            {
                window.open('klanten/facturatie_opstal_toon.php', 'Klanten opstal factuur Futech', 'status,width=1100,height=800,scrollbars=yes');
            }

            $(function() {
                $("#datum").datepicker({dateFormat: 'dd-mm-yy'});
                $("#datum1").datepicker({dateFormat: 'dd-mm-yy'});
                $("#datum_cf").datepicker({dateFormat: 'dd-mm-yy'});
                $("#datum_oa").datepicker({dateFormat: 'dd-mm-yy'});
                $("#aanm_datum").datepicker({dateFormat: 'dd-mm-yy'});
            });

            function selectAlles(FieldName, dit, formulier)
            {
                var CheckValue = dit.checked;

                var objCheckBoxes = document.forms[formulier].elements[FieldName];
                if (!objCheckBoxes)
                    return;
                var countCheckBoxes = objCheckBoxes.length;
                if (!countCheckBoxes)
                    objCheckBoxes.checked = CheckValue;
                else
                    // set the check value for all check boxes
                    for (var i = 0; i < countCheckBoxes; i++)
                        objCheckBoxes[i].checked = CheckValue;
            }

            function gotoKlant(cus_id1)
            {
                document.getElementById("cus_id1").value = cus_id1;
                document.getElementById("frm_overzicht").submit();
            }

            function getValueFac()
            {
                var chk = document.getElementById("soort_fac").value;

                if (chk == "Andere")
                {
                    document.getElementById("andere_soort_factuur").style.display = 'inline';
                } else
                {
                    document.getElementById("andere_soort_factuur").style.display = 'none';
                }
            }

            function getValueFac1()
            {
                var chk = document.getElementById("soort_fac").value;

                if (chk == "Andere")
                {
                    document.getElementById("andere_soort_factuur1").style.display = 'inline';
                } else
                {
                    document.getElementById("andere_soort_factuur1").style.display = 'none';
                }
            }

            $(function() {
                $("#tabs").tabs({selected: <?php
if (isset($_REQUEST["tab_id"])) {
    echo $_REQUEST["tab_id"];
} else {
    echo 0;
};
?>});
                $("#tabs_fac").tabs({selected: <?php
if (isset($_REQUEST["tabfac_id"])) {
    echo $_REQUEST["tabfac_id"];
} else {
    echo 0;
};
?>});
                $("#tabs_cn").tabs({selected: <?php
if (isset($_REQUEST["tabcn_id"])) {
    echo $_REQUEST["tabcn_id"];
} else {
    echo 0;
};
?>});

            });

            var XMLHttpRequestObject1 = false;

            try {
                XMLHttpRequestObject1 = new ActiveXObject("MSXML2.XMLHTTP");
            } catch (exception1) {
                try {
                    XMLHttpRequestObject1 = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (exception2) {
                    XMLHttpRequestObject1 = false
                }

                if (!XMLHttpRequestObject1 && window.XMLHttpRequest) {
                    XMLHttpRequestObject1 = new XMLHttpRequest();
                }
            }

            function saveWaarde(cus_id, dit)
            {

                datasource = "facturatie_ajax_facpan.php?cus_id=" + cus_id + "&waarde=" + dit.value;

                if (XMLHttpRequestObject1) {
                    XMLHttpRequestObject1.open("GET", datasource, true);
                    XMLHttpRequestObject1.onreadystatechange = function() {
                        if (XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200) {

                        }
                    }

                    XMLHttpRequestObject1.send(null);
                }
            }

            var XMLHttpRequestObject2 = false;

            try {
                XMLHttpRequestObject2 = new ActiveXObject("MSXML2.XMLHTTP");
            } catch (exception1) {
                try {
                    XMLHttpRequestObject2 = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (exception2) {
                    XMLHttpRequestObject2 = false
                }

                if (!XMLHttpRequestObject2 && window.XMLHttpRequest) {
                    XMLHttpRequestObject2 = new XMLHttpRequest();
                }
            }

            function savewaardeoa(cus_id, dit, veld)
            {
                datasource = "facturatie_ajax_oa.php?cus_id=" + cus_id + "&waarde=" + dit.value + "&veld=" + veld;

                if (XMLHttpRequestObject1) {
                    XMLHttpRequestObject1.open("GET", datasource, true);
                    XMLHttpRequestObject1.onreadystatechange = function() {
                        if (XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200) {

                        }
                    }

                    XMLHttpRequestObject1.send(null);
                }
            }

            var XMLHttpRequestObject3 = false;

            try {
                XMLHttpRequestObject3 = new ActiveXObject("MSXML2.XMLHTTP");
            } catch (exception1) {
                try {
                    XMLHttpRequestObject3 = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (exception2) {
                    XMLHttpRequestObject3 = false
                }

                if (!XMLHttpRequestObject3 && window.XMLHttpRequest) {
                    XMLHttpRequestObject3 = new XMLHttpRequest();
                }
            }

            function checkCity(dit)
            {
                DIVOK = "gemeente";
                datasource = "klanten_ajax2.php?postcode=" + dit.value;

                if (XMLHttpRequestObject3) {
                    var obj = document.getElementById(DIVOK);

                    XMLHttpRequestObject3.open("GET", datasource, true);
                    XMLHttpRequestObject3.onreadystatechange = function() {
                        if (XMLHttpRequestObject3.readyState == 4 && XMLHttpRequestObject3.status == 200) {
                            obj.value = XMLHttpRequestObject3.responseText;
                        }
                    }

                    XMLHttpRequestObject3.send(null);
                }
            }

            function checkKlant()
            {
                var bestaande = document.getElementById("bestaande").checked;
                var nieuwe = document.getElementById("nieuwe").checked;

                if (bestaande == true)
                {
                    document.getElementById("tbl_bestaande_klant").style.display = "block";
                    document.getElementById("tbl_nieuwe_klant").style.display = "none";
                }

                if (nieuwe == true)
                {
                    document.getElementById("tbl_bestaande_klant").style.display = "none";
                    document.getElementById("tbl_nieuwe_klant").style.display = "block";
                }
            }

        </script>
        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-24625187-1']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script');
                ga.type = 'text/javascript';
                ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </head>
    <body>

        <?php
//        if (isset($_POST["opstal_factuur"])) {
//            $_SESSION["opstal"] = array();
//            $_SESSION["opstal"]["fac_nr"] = $_POST["factuur_nr"];
//            $_SESSION["opstal"]["fac_datum"] = $_POST["datum_opstal"];
//            $_SESSION["opstal"]["fac_klant"] = $_POST["chk_"];
//
//            for ($i = 1; $i < 16; $i++) {
//                $_SESSION["opstal"]["products"][$i] = array("art" => $_POST["art_" . $i],
//                    "beschrijving" => $_POST["beschrijving_" . $i],
//                    "aantal" => $_POST["aantal_" . $i],
//                    "prijs" => $_POST["prijs_" . $i]);
//            }
//            ?>

<!--            <script type="text/javascript">
                maakfactuurOpstal();
            </script>-->
    <?php
//}
?>

        <div id='pagewrapper'><?php include('inc/header.php'); ?>

            <h1>Facturatie</h1>

            <div id="tabs" style="width: 1000px;">
                <ul>
                    <!--		
                    <li><a href="#tabs-1">Verkoop</a></li>
                    <li><a href="#tabs-1a">Verhuur</a></li>-->
                    <li><a href="#tabs-2">Custom Facturen</a></li>
                    <li><a href="#tabs-3">CN</a></li>
                    <!--
            <li><a href="#tabs-4">Betalingen</a></li>
                    -->
                    <li><a href="#tabs-5">Facturen</a></li>
                    <li><a href="#tabs-6">Creditnota's</a></li>

                    <!--
                    <li><a href="#tabs-7">Import excel</a></li>
                    -->

                    <!--        <li><a href="#tabs-8">Klanten OA</a></li>-->
                    <!--
            <li><a href="#tabs-9">Aanmaningen</a></li>
                    -->
                    <!--        <li><a href="#tabs-10">Boiler</a></li>-->

                    <!--
                    <li><a href="klanten/klanten_overzicht.php" title="Overzicht">Overzicht</a></li>
                    -->

                    <!--        <li><a href="klanten/facturatie_opstal.php" title="Opstalvergoeding">Opstalvergoeding</a></li>-->
                </ul>

                <!--
                <div id="tabs-1">
                <?php
                $tab_id = 0;
                ?>
                    <form method='post' name='frm_overzicht' id='frm_overzicht' action='klanten.php?tab_id=1'>
                        <input type='hidden' name='cus_id1' id='cus_id1' value='' />
                    </form>

                    <?php
                    
                    // einde zoeken naar factuurnummer
                    //echo "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_factuur_filename = '' AND cus_verkoop = '1' " . $sorteer;
                    //$q_geenfac = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_factuur_filename = '' AND cus_verkoop = '1' " . $sorteer);
                    
                    //AAAAAAAAAA
                    $transactie_no_fac= '';
                    $q_trans = '';
                    $get_all_transacties = mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE status='1' AND factuur_id=''");
                    $z = 0;
                    $transactie_fac_arr = array();
                    while($transactie = mysqli_fetch_object($get_all_transacties)){
                        $check_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort='factuur' AND cf_soort_id=".$transactie->id);
                        if(mysqli_num_rows($check_fac) == 0){ /* als geen factuur */
                            if($z != 0){ /* Na het eerste keer */
                                $transactie_no_fac .= " OR id=".$transactie->id;
                                $q_trans .= ' OR id='.$transactie->id;
                                $transactie_no_fac_arr[] = $transactie->id;
                            }else{ /* Eerste keer */
                                $transactie_no_fac .= " WHERE id=".$transactie->id;
                                $q_trans .= 'id='.$transactie->id;
                                $transactie_no_fac_arr[] = $transactie->id;
                            }
                            $z++;
                        }else{ /* heeft een factuur */
                            $transactie_fac_arr = $transactie->id;
                        }
                    }
                    
                    if($transactie_no_fac != ''){
                        $q_geenfac = mysqli_query($conn, "SELECT * FROM tbl_transacties".$transactie_no_fac);
                    }
                    
                    
                    $zoek_fac1 = 0;
                    /* boekjaar bepalen */
                    $fac_id = array();
                    $nu =  date('Y-m-d');
                    $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                    while($boekjaar = mysqli_fetch_object($q_boekjaren)){
                        if($nu<$boekjaar->boekjaar_einde){
                            $get_transaction_between = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort='factuur' AND (cf_date BETWEEN '".$boekjaar->boekjaar_start."' AND '".$boekjaar->boekjaar_einde."') ");
                            if(mysqli_num_rows($get_transaction_between) != 0){
                                while($fac = mysqli_fetch_object($get_transaction_between)){
                                    $id = explode(".", $fac->cf_file);
                                    $fac_id[] = $id[0];
                                }
                            }
                        }
                    }
                    /*  SORT ARRAY EN NEEM DE LAATSTE WAARDE (MAX WAARDE)*/
                    asort($fac_id);
                    $zoek_fac1 = end($fac_id);
                    ?>
                    <form method='post' name='frm_factuur' id='frm_factuur'>
                        Onderstaande klanten met nog geen factuur, waarbij de transactie's verkoop op ja staat.<br/>
                        Laatste factuurnummer in de database : <?php echo $zoek_fac1; ?>.<br/>


                        <table>
                            <tr><td>Starten met nummer : </td><td><input type='text' size='4' name='factuur_nr' id='factuur_nr' value='<?php echo $zoek_fac1 + 1; ?>' />? </td></tr>
                            <?php
                            $dat = date('d') . "-" . date('m') . "-" . date('Y');

                            if (isset($_POST["datum"])) {
                                $dat = $_POST["datum"];
                            }


                            $verklaring_chk = " checked='checked' ";
                            if (!isset($_POST["verklaring"]) && isset($_POST["go"])) {
                                $verklaring_chk = "";
                            }

                            /*
                              echo "<pre>";
                              var_dump( $_POST );
                              echo "</pre>";
                             */
                            ?>

                            <tr><td>Datum :  </td><td><input type='text' size='10' name='datum' id='datum' value='<?php echo $dat; ?>' /> </td></tr>
                            <tr><td>Verklaring tonen onderaan op factuur : </td><td><input type='checkbox' <?php echo $verklaring_chk; ?>  name='verklaring' id='verklaring' /> </td></tr>
                        </table>
                        <br/> 
                        Selecteer de klanten waarvoor het factuur moet worden opgemaakt :

                        <input type='submit' name='go' id='go' value='go' />
                        <input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' />


                        <?php
                        echo "<br/><br/>";
                        echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";

                        $lijn_onder = " style='border-bottom:1px solid black' ";

                        echo "<tr style='cursor:pointer;'>";
                        echo "<td " . $lijn_onder . "> <input type='checkbox' name='chk_alles' id='chk_alles' onclick='selectAlles(\"fac_klant[]\", this, \"frm_factuur\");' /></td>";
                        echo "<td " . $lijn_onder . "><b>Verkoopdatum</b></td>";
                        echo "<td " . $lijn_onder . "><b>Naam</b></td>";
                        echo "<td " . $lijn_onder . "><b>Plaats</b></td>";
                        echo "<td " . $lijn_onder . " align='right'><b>&euro; Excl.</b></td>";
                        echo "<td " . $lijn_onder . " align='right'><b>&euro; Incl.</b></td>";
                        echo "<td " . $lijn_onder . " align='right'><b>Opm.</b></td>";
                        echo "</tr>";

                        /* GEEN FACTUUR DUS OOK GEEN LIJST TONEN */
                        if($transactie_no_fac != ''){
                                $i = 0;
                                while ($transactie = mysqli_fetch_object($q_geenfac)) {                  
                                    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id=".$transactie->soort_id));
                                    // zoeken of er nog geen factuur is
                                    $q_zoek_fac = "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $transactie->soort_id . " AND cf_soort = 'factuur' AND cf_van = 'solar' ";
                                    $zoek_fac = mysqli_query($conn, $q_zoek_fac);

                                    $q_zoek_cn = "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $transactie->soort_id . " AND cf_soort = 'creditnota' AND cf_van = 'solar' ";
                                    $zoek_cn = mysqli_query($conn, $q_zoek_cn);
                                    
 
                                        // nakijken of dit een subklant is
                                        $cus_id = $transactie->soort_id;
                                        $vet = 0;

                                        if (!empty($transactie->btw)) {
                                            $vet = 1;
                                        }

                                        $i++;

                                        $kleur = $kleur_grijs;
                                        if ($i % 2) {
                                            $kleur = "white";
                                        }

                                        echo "<tr  style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");document.getElementById(\"cus_id1\").value=" . $transactie->soort_id . ";' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' >";
                                        echo "<td>";

                                        echo "<input type='checkbox' name='fac_klant[]' id='fac_klant_" . $transactie->soort_id . "[]' value='" . $transactie->soort_id . "' />";

                                        if (isset($_POST["fac_klant"])) {
                                            if (is_array($_POST["fac_klant"])) {
                                                if (isset($_POST["go"]) && $_POST["go"] == "go" && in_array($transactie->soort_id, $_POST["fac_klant"])) {
                                                    // this.style.backgroundColor='green';this.style.color='white';
                                                    $verklaring = 0;
                                                    if (isset($_POST["verklaring"])) {
                                                        $verklaring = 1;
                                                    }
                                                    ?>
                                                    <input type='button' id='fac_<?php echo $_POST["factuur_nr"]; ?>' value='<?php echo $_POST["factuur_nr"]; ?>' onclick="window.open('klanten_fac.php?trans_id=<?php echo $transactie->id; ?>&fac_nr=<?php echo $_POST["factuur_nr"]; ?>&datum=<?php echo $_POST["datum"]; ?>&verklaring=<?php echo $verklaring; ?>', 'Klanten factuur', 'status,width=1100,height=800,scrollbars=yes');
                                                                                return false;" />
                                                           <?php
                                                           $_POST["factuur_nr"] ++;
                                                       }
                                                   }
                                               }

                                               echo "</td>";

                                               echo "<td onclick='gotoKlant(" . $cus_id . ")'>";
                                               if($q_trans != ''){
                                                    echo changeDate2EU($transactie->datum); 
                                               }
                                               echo "</td>";

                                               echo "<td onclick='gotoKlant(" . $cus_id . ")'>";

//                                               $vol_klant = "";
//
//                                               if ($klant->cus_bedrijf != "" && $klant->cus_naam != "") {
//                                                   $vol_klant = $klant->cus_naam . " (" . $klant->cus_bedrijf . ")";
//                                               } else {
//                                                   if ($klant->cus_bedrijf == "") {
//                                                       $vol_klant = $klant->cus_naam;
//                                                   } else {
//                                                       $vol_klant = $klant->cus_bedrijf;
//                                                   }
//                                               }
                                               $vol_klant = $klant->cus_naam;
                                               if (strlen($vol_klant) > 20) {
                                                   $vol_klant = "<span title='" . str_replace("</i>", "", str_replace("<i>", "", $vol_klant)) . "' >" . substr($vol_klant, 0, 20) . "...</span>";
                                               }

                                               echo $vol_klant;

                                               if ($vet == 1) {
                                                   echo "</b>";
                                               }

                                               echo "</td>";
                                               //echo "<td onclick='gotoKlant(".$klant->cus_id.")'>". $klant->cus_straat . " " . $klant->cus_nr ."</td>";
                                               echo "<td onclick='gotoKlant(" . $cus_id . ")'>" . $klant->cus_postcode . " " . $klant->cus_gemeente . "</td>";


                                               echo "<td onclick='gotoKlant(" . $cus_id . ")' align='right'>";
                                               if($q_trans != ''){
                                                   echo number_format($transactie->prijs_excl, 2, ",", " ");
                                               }
                                               echo "</td>";

                                               echo "<td onclick='gotoKlant(" . $cus_id . ")' align='right'>";
                                               if($q_trans != ''){
                                                   echo number_format($transactie->prijs_incl, 2, ",", " ");
                                               }
                                               echo "</td>";

                                               echo "<td onclick='gotoKlant(" . $cus_id . ")' align='right'>";

                                               if (!empty($klant->cus_opmerkingen)) {
                                                   echo "<span title='" . $klant->cus_opmerkingen . "'> <img src='images/info.jpg' width='16px' height='16px' /> </span>";
                                               }

                                               echo "</td>";
                                               echo "</tr>";
                                       }
                                }
                               echo "</table>";
                               ?>
                    </form>
                    <hr/>
                    Vet gedrukte namen hebben een BTW-nummer;<br/>
                    De namen in het groen zijn mede-contractanten;<br/>
                    De namen in het blauw zijn aankopen zonder certificaten;

                </div>

-->

                    <div id="tabs-2">
                    <?php
                    $tab_id=0;
                    ?>
                    Custom facturen aanmaken :<br/><br/>

                    <form method='post' name='frm_factuur_custom' id='frm_factuur_custom'>
                        Laatste factuurnummer in de database : <?php echo $zoek_fac1; ?>.<br/>
                        Starten met nummer : <input type='text' size='4' name='factuur_nr' id='factuur_nr' value='<?php echo $zoek_fac1 + 1; ?>' />?<br/>
                        <br/> 

                        <input type='submit' name='go2' id='go2' value='Begin' />
                        <input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' />
                    </form>

                    <?php
                    if (isset($_POST["go2"]) && $_POST["go2"] == "Begin") {
                        $dat = date('d') . "-" . date('m') . "-" . date('Y');

                        if (isset($_POST["datum_cf"])) {
                            $dat = $_POST["datum_cf"];
                        }

                        $_SESSION["custom_factuur"] = "";

                        echo "<form method='post' name='frm_fac_cust' id='frm_fac_cus' onsubmit='return check_form();' >";
                        echo "<table border='0' width='100%'>";
                        echo '<tr><td valign="top" width="180" >Soort factuur : </td><td>';
                        echo "<select name='soort_fac' id='soort_fac' onchange='getValueFac();'>";
                        echo "<option value=''> == Keuze == </option>";

                        $q_zoek_soort = mysqli_query($conn, "SELECT cf_van FROM kal_customers_files GROUP BY cf_van ORDER BY cf_van");

                        while ($rij = mysqli_fetch_object($q_zoek_soort)) {
                            if ($rij->cf_van != '') {
                                $sel = "";

                                if (isset($_POST["soort_fac"]) && $_POST["soort_fac"] == $rij->cf_van) {
                                    $sel = " selected='selected' ";
                                }

                                echo "<option " . $sel . " value='" . $rij->cf_van . "'>" . $rij->cf_van . "</option>";
                            }
                        }

                        $sel = "";
                        if (isset($_POST["soort_fac"]) && $_POST["soort_fac"] == "Andere") {
                            $sel = " selected='selected' ";
                        }

                        echo "<option " . $sel . " value='Andere'>Nieuwe soort toevoegen :</option>";

                        echo "</select>";

                        $showAndere = " style='display:none;' ";

                        if (isset($_POST["soort_fac"]) && $_POST["soort_fac"] == "Andere") {
                            $showAndere = "";
                        }

                        $andere_soor_factuur = "";
                        if (isset($_POST['soort_fac_andere']))
                            $andere_soor_factuur = $_POST['soort_fac_andere'];
                        echo "<span id='andere_soort_factuur' " . $showAndere . " >";
                        echo "<input type='text' name='soort_fac_andere' id='soort_fac_andere' value='" . $andere_soor_factuur . "' />";
                        echo "</span>";

                        echo "</td></tr>";
                        echo "<tr><td>Datum :</td>";
                        echo "<td><input type='text' size='10' name='datum_cf' id='datum_cf' value='" . $dat . "' /> </td>";
                        echo "</tr>";
                        echo "<tr><td>BTW - percentage :</td>";
                        echo "<td>";
                        ?>

                        <script type="text/javascript">

                            function checkBTW(dit)
                            {
                                if (dit.value == 0)
                                {
                                    document.getElementById("div_0procent").style.display = "block";
                                } else
                                {
                                    document.getElementById("div_0procent").style.display = "none";
                                }
                            }

                            function check_form()
                            {
                                var fout = false;
                                var foutMsg = "Gelieve de volgende velden na te kijken :\n";

                                var factuur_nr = document.getElementById("factuur_nr").value;
                                var datum = document.getElementById("datum_cf").value;
                                var btw = document.getElementById("btw").value;
                                var soort = document.getElementById("soort_fac").value;

                                if (soort == '')
                                {
                                    fout = true;
                                    foutMsg += "- factuur soort moet ingevuld zijn\n";
                                }

                                if (factuur_nr == '')
                                {
                                    fout = true;
                                    foutMsg += "- factuur nr is leeg\n";
                                }

                                if (datum == '')
                                {
                                    fout = true;
                                    foutMsg += "- datum veld is leeg\n";
                                }

                                if (btw == 0)
                                {
                                    var mede1 = document.getElementById("mede1").checked;
                                    var mede2 = document.getElementById("mede2").checked;
                                    var mede3 = document.getElementById("mede3").checked;
                                    var mede4 = document.getElementById("mede4").checked;

                                    if (mede1 == false && mede2 == false && mede3 == false && mede4 == false)
                                    {
                                        fout = true;
                                        foutMsg += "- btw is 0%, kies de mededeling hiervoor\n";
                                    }
                                }

                                var bestaande = document.getElementById("bestaande").checked;
                                var nieuwe = document.getElementById("nieuwe").checked;

                                if (bestaande == false && nieuwe == false)
                                {
                                    fout = true;
                                    foutMsg += "- Bestaande of Nieuwe klant?\n";
                                }

                                if (nieuwe == true)
                                {
                                    var naam = document.getElementById("naam").value;
                                    var bedrijf = document.getElementById("bedrijf").value;
                                    var btwnr = document.getElementById("btwnr").value;
                                    var straat = document.getElementById("straat").value;
                                    var nr = document.getElementById("nr").value;
                                    var postcode = document.getElementById("postcode").value;
                                    var gemeente = document.getElementById("gemeente").value;

                                    if (naam == '' && bedrijf == '' && btwnr == '' && straat == '' && nr == '' && postcode == '' && gemeente == '')
                                    {
                                        fout = true;
                                        foutMsg += "- nieuwe klant : al de velden zijn leeg\n";
                                    }
                                }

                                if (fout == false)
                                {
                                    return true;
                                } else
                                {
                                    alert(foutMsg);
                                    return false;
                                }
                            }

                        </script>

                        <?php
                        echo "<select name='btw' id='btw' onchange='checkBTW(this);' >";

                        foreach ($btw_arr as $btw) {
                            $sel = "";

                            if (isset($_POST["btw"]) && $_POST["btw"] == $btw) {
                                $sel = " selected='selected' ";
                            }

                            echo "<option " . $sel . " value='" . $btw . "'>" . $btw . "%</option>";
                        }

                        echo "</select>";
                        echo "</td>";
                        echo "</tr>";
                        echo "</table>";

                        echo "<div id='div_0procent'>";
                        echo "Welke vermelding moet er komen op een factuur met 0% BTW :";

                        foreach ($btw_vrijstelling as $v => $vrijstelling) {

                            echo "<br/>";

                            $sel = "";

                            if (isset($_POST["soort0"][0]) && $_POST["soort0"][0] == "mede" . $v) {
                                $sel = " checked='checked' ";
                            }

                            echo "<input " . $sel . " type='radio' name='soort0[]' id='mede" . $v . "' value='mede" . $v . "' /><label for='mede" . $v . "'>" . $vrijstelling . "</label>";
                        }

                        echo "</div>";
                        echo "<br/>";

                        $checked1 = "";
                        $checked2 = "";

                        if (isset($_POST["klant_keuze"]) && $_POST["klant_keuze"] == "bestaande") {
                            $checked1 = " checked='checked' ";
                        }

                        if (isset($_POST["klant_keuze"]) && $_POST["klant_keuze"] == "nieuwe") {
                            $checked2 = " checked='checked' ";
                        }

                        echo "Kies klant :";
                        echo "<br/>";
                        echo "<input type='radio' " . $checked1 . " name='klant_keuze' id='bestaande' value='bestaande' onclick='checkKlant();' /> <label for='bestaande' >Bestaande klant</label>";
                        echo "<br/>";
                        echo "<input type='radio' " . $checked2 . " name='klant_keuze' id='nieuwe' value='nieuwe' onclick='checkKlant();' /> <label for='nieuwe' >Nieuwe klant</label>";

                        $hide_bestaande = " style='display:none;' ";
                        if (isset($_POST["klant_keuze"]) && $_POST["klant_keuze"] == "bestaande") {
                            $hide_bestaande = "";
                        }

                        echo "<table id='tbl_bestaande_klant' " . $hide_bestaande . " >";
                        echo "<tr>";
                        echo "<td>";
                        echo "Bestaande klant :";
                        echo "</td>";
                        echo "<td>";

                        $q_klanten1 = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_oa = '0' AND uit_cus_id = '0' ORDER BY cus_naam");

                        echo "<select name='sel_bestaande_klant' id='sel_bestaande_klant' >";
                        while ($rij = mysqli_fetch_object($q_klanten1)) {
                            if (isset($_POST["sel_bestaande_klant"]) && $_POST["sel_bestaande_klant"] == $rij->cus_id) {
                                echo "<option selected='selected' value='" . $rij->cus_id . "'>" . $rij->cus_naam . " (" . $rij->cus_straat . " " . $rij->cus_nr . ", " . $rij->cus_postcode . " " . $rij->cus_gemeente . ")</option>";
                            } else {
                                echo "<option value='" . $rij->cus_id . "'>" . $rij->cus_naam . " (" . $rij->cus_straat . " " . $rij->cus_nr . ", " . $rij->cus_postcode . " " . $rij->cus_gemeente . ")</option>";
                            }
                        }
                        echo "</select>";
                        echo "</td>";
                        echo "</tr>";
                        echo "</table>";


                        $hide_nieuwe = " style='display:none;' ";
                        if (isset($_POST["klant_keuze"]) && $_POST["klant_keuze"] == "nieuwe") {
                            $hide_nieuwe = "";
                        }
                        // naam controle
                        $naam = "";
                        if (isset($_POST['naam']))
                            $naam = $_POST['naam'];
                        // bedrijf controle
                        $bedrijf = "";
                        if (isset($_POST['bedrijf']))
                            $naam = $_POST['bedrijf'];
                        // btwnr controle
                        $btwnr = "";
                        if (isset($_POST['btwnr']))
                            $naam = $_POST['btwnr'];
                        // straat controle
                        $straat = "";
                        if (isset($_POST['straat']))
                            $naam = $_POST['straat'];
                        // straatnr controle
                        $straatnr = "";
                        if (isset($_POST['nr']))
                            $naam = $_POST['nr'];
                        // gemeente controle
                        $gemeente = "";
                        if (isset($_POST['gemeente']))
                            $naam = $_POST['gemeente'];
                        // postcode controle
                        $postcode = "";
                        if (isset($_POST['postcode']))
                            $naam = $_POST['postcode'];
                        echo "<br/><table id='tbl_nieuwe_klant' " . $hide_nieuwe . " >";
                        echo "<tr><td>Naam :</td>";
                        echo "<td><input type='text' class='lengte' name='naam' id='naam' value='" . $naam . "' /> </td>";
                        echo "</tr>";
                        echo "<tr><td>Bedrijf :</td>";
                        echo "<td><input type='text' class='lengte' name='bedrijf' id='bedrijf' value='" . $bedrijf . "' /> </td>";
                        echo "</tr>";
                        echo "<tr><td>BTW :</td>";
                        echo "<td><input type='text' class='lengte' name='btwnr' id='btwnr' value='" . $btwnr . "' /> </td>";
                        echo "</tr>";
                        echo "<tr><td>Straat + nr :</td>";
                        echo "<td><input type='text' name='straat' id='straat' value='" . $straat . "' /><input size='4' type='text' name='nr' id='nr' value='" . $straatnr . "' /> </td>";
                        echo "</tr>";
                        echo "<tr><td>Postcode + gemeente :</td>";
                        echo "<td><input size='4' type='text' name='postcode' id='postcode' value='" . $postcode . "' onblur='checkCity(this);' /><input type='text' name='gemeente' id='gemeente' value='" . $gemeente . "' /> </td>";
                        echo "</tr>";

                        echo "</table>";

                        echo "<br/>";

                        echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
                        echo "<tr>";
                        echo "<td><b>Artikel :</b></td>";
                        echo "<td><b>Beschrijving :</b></td>";
                        echo "<td><b>Prijs :</b></td>";
                        echo "</tr>";

                        for ($i = 1; $i < 16; $i++) {
                            // artikel controleren
                            $artikel = "";
                            if (isset($_POST["art_" . $i]))
                                $artikel = $_POST["art_" . $i];
                            // beschrijving controleren
                            $beschrijving = "";
                            if (isset($_POST["beschrijving_" . $i]))
                                $beschrijving = $_POST["beschrijving_" . $i];
                            // prijs controleren
                            $prijs = "";
                            if (isset($_POST["prijs_" . $i]))
                                $prijs = $_POST["prijs_" . $i];
                            echo "<tr>";
                            echo "<td>";
                            echo "<input type='text' size='10' name='art_" . $i . "' id='art_" . $i . "' value='" . $artikel . "' />";
                            echo "</td>";
                            echo "<td>";
                            echo "<input type='text' size='96' name='beschrijving_" . $i . "' id='beschrijving_" . $i . "' value='" . $beschrijving . "' />";
                            echo "</td>";
                            echo "<td>";
                            echo "<input type='text' size='20' name='prijs_" . $i . "' id='prijs_" . $i . "' value='" . $prijs . "' />";
                            echo "</td>";
                            echo "</tr>";
                        }

                        echo "<tr>";
                        echo "<td align='center' colspan='3' >";
                        echo "<input type='submit' name='show' id='show' value='Toon factuur' />";
                        echo "</td>";
                        echo "</tr>";

                        echo "</table>";
                        echo "<input type='hidden' name='tab_id' id='tab_id' value='" . $tab_id . "' />";
                        echo "<input type='hidden' name='factuur_nr' id='factuur_nr' value='" . $_POST["factuur_nr"] . "' />";
                        echo "<input type='hidden' name='go2' id='go2' value='Begin' />";
                        echo "</form>";
                    }

                    if (isset($_POST["show"]) && $_POST["show"] == "Toon factuur") {
                        $_SESSION["custom_factuur"] = $_POST;
                        ?>
                        <script type='text/javascript'>
                            window.open('klanten_cusfac.php', 'Klanten factuur Futech', 'status,width=1100,height=800,scrollbars=yes');
                        </script>
                        <?php
                    }
                    ?>
                </div>

                <div id="tabs-3">
                    <?php
                    $tab_id++;
                    ?>
                    <?php
                    $nw_boek_jaar = "01-07";
                    $mk_nw_boek_jaar = mktime(0, 0, 0, 7, 1, 0);
                    $mk_nu = mktime(0, 0, 0, date('m'), date('d'), 0);

                    $zoek_cn = 0;
                    if ($mk_nu >= $mk_nw_boek_jaar) {
                        //echo "<br> NA 01-07";
                        $jaar_1 = date('Y') + 1;
                        $jaar_2 = date('Y-m-d', mktime(0, 0, 0, 7, 1 - 1, $jaar_1));

                        $q_geenfac = mysqli_query($conn, "SELECT * FROM kal_customers_files
                                                                                            WHERE cf_soort = 'creditnota'
                                                                                            AND cf_date BETWEEN '" . date('Y') . "-07-01' AND '" . $jaar_2 . "'
                                                                                            ORDER BY 1 DESC");
                    } else {
                        //echo "<br> VOOR 01-07";
                        $jaar_1 = date('Y') - 1;
                        $jaar_2 = date('Y-m-d', mktime(0, 0, 0, 7, 1 - 1, date('Y')));

                        $q_geenfac = mysqli_query($conn, "SELECT * FROM kal_customers_files
					WHERE cf_soort = 'creditnota'
                                                                                          AND cf_date BETWEEN '" . $jaar_1 . "-07-01' AND '" . $jaar_2 . "'
                                                            		ORDER BY 1 DESC");
                    }

                    while ($rij = mysqli_fetch_object($q_geenfac)) {
                        $factuur = explode(".", $rij->cf_file);

                        if (is_numeric($factuur[0]) && $zoek_cn < $factuur[0]) {
                            $zoek_cn = $factuur[0];
                        }
                    }

                    if ($zoek_cn == 0) {
                        $zoek_cn = 1;
                    } else {
                        $zoek_cn += 1;
                    }
                    ?>

                    Kies een klant :

                    <form method='post' name='frm_cn' id='frm_cn'>
                        <?php
                        $q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' ORDER BY cus_naam");

                        $klanten = array();

                        while ($rij = mysqli_fetch_object($q_klanten)) {
                            if ($rij->uit_cus_id > 0) {
                                // dan is het een uitbreiding
                                $q_klant_uit = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $rij->uit_cus_id);

                                if (mysqli_num_rows($q_klant_uit) > 0) {
                                    $klant_uit = mysqli_fetch_object($q_klant_uit);
                                    $klanten[$rij->cus_id] = $klant_uit->cus_naam . " (Uitbreiding) ";
                                } else {
                                    $klanten[$rij->cus_id] = $rij->cus_naam;
                                }
                            } else {
                                $klanten[$rij->cus_id] = $rij->cus_naam;
                            }
                        }

                        asort($klanten);

                        echo "<select name='sel_klant' id='sel_klant'>";
                        echo "<option value='0'>== Keuze == </option>";

                        foreach ($klanten as $id => $naam) {
                            if (isset($_POST["sel_klant"]) && $_POST["sel_klant"] > 0 && $_POST["sel_klant"] == $id) {
                                echo "<option selected='selected' value='" . $id . "'>" . $naam . "</option>";
                            } else {
                                echo "<option value='" . $id . "'>" . $naam . "</option>";
                            }
                        }

                        echo "</select>";
                        ?>
                        <input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' />
                        <input type='submit' name='go' id='go' value='Go' />
                    </form> 

                    <?php
                    if (isset($_POST["go"]) && $_POST["go"] == 'Go') {
                        // ophalen van de facturen bij deze klant
                        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["sel_klant"]));

                        $q_zoek_fac = "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_soort_id = " . $_POST["sel_klant"]." ORDER BY cf_file ASC";
                        $zoek_fac = mysqli_query($conn, $q_zoek_fac);

                        $factuur_arr_cn = array();

                        if (mysqli_num_rows($zoek_fac) > 0) {
                            echo "<br/><u>Facturen :</u>";
                            while ($factuur = mysqli_fetch_object($zoek_fac)) {
                                $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                                while($boekjaar = mysqli_fetch_object($q_boekjaren)){
                                    if($factuur->cf_date > $boekjaar->boekjaar_start && $factuur->cf_date <= $boekjaar->boekjaar_einde){
                                        $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
                                    }
                                }
                                echo "<br/>";
                                echo "<a href='facturen/" . $dir . "/". $factuur->cf_file . "' target='_blank' >";
                                echo $factuur->cf_file;
                                echo "</a>";

                                $factuur_arr_cn[] = array("id" => $factuur->cf_id, "factuur" => $factuur->cf_file, "bedrag" => $factuur->cf_bedrag);
                            }
                        } else {
                            echo "Aan deze klant zijn nog geen facturen gekoppeld.";
                        }

                        $begin_cnnr = $zoek_cn;

                        echo "<br/><br/><u>Creditnota maken voor " . $klant->cus_naam . " :</u>";

                        echo "<form method='post' name='frm_cn' id='frm_cn' >";
                        echo "<table>";
                        echo "<tr>";
                        echo "<td>CN NR :</td>";
                        echo "<td> <input type='text' name='cn_nr' id='cn_nr' value='" . $begin_cnnr . "' /> </td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td>Datum :</td>";
                        echo "<td> <input type='text' name='datum1' id='datum1' value='" . date("d") . "-" . date('m') . "-" . date('Y') . "' /> </td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td>CN op factuur</td>";
                        echo "<td>";

                        echo "<select name='factuur' id='factuur' >";
                        foreach ($factuur_arr_cn as $factuur) {
                            echo "<option value='" . $factuur["id"] . "'>" . $factuur["factuur"] . " (" . number_format($factuur["bedrag"], 2, ",", " ") . ")</option>";
                        }
                        echo "</select>";

                        echo "</td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td>Artikel :</td>";
                        echo "<td> <input type='text' name='art' id='art' value='PV installatie' /> </td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td valign='top'>Omschrijving :</td>";
                        echo "<td> <textarea style='width:300px;height:100px;' name='omschr' id='omschr' value='' ></textarea> </td>";
                        echo "</tr>";
//
//                        echo "<tr>";
//                        echo "<td>Aantal :</td>";
//                        echo "<td> <input type='text' name='aant' id='aant' value='1' /> </td>";
//                        echo "</tr>";

                        echo "<tr>";
                        echo "<td>Prijs incl.:</td>";
                        echo "<td> <input type='text' name='prijs' id='prijs' value='' /> </td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td>BTW :</td>";
                        echo "<td>";

                        echo "<select name='btw' id='btw' >";
                        echo "<option value='0'>0</option>";
                        echo "<option value='6'>6</option>";
                        echo "<option value='21'>21</option>";
                        echo "</select>";

                        echo "</td>";
                        echo "</tr>";

                        echo "<tr>";
                        echo "<td colspan='2' align='center'>";

                        echo "<input type='submit' name='maak' id='maak' value='Maak CN' />";

                        echo "</td>";
                        echo "</tr>";

                        echo "</table>";

                        echo "<input type='hidden' name='sel_klant' id='sel_klant' value='" . $_POST["sel_klant"] . "' />";
                        echo "<input type='hidden' name='tab_id' id='tab_id' value='" . $tab_id . "' />";
                        echo "<input type='hidden' name='go' id='go' value='Go' />";
                        echo "</form>";

                        if (isset($_POST["maak"])) {
                            $_SESSION["kalender_cn"]["cn_nr"] = $_POST["cn_nr"];
                            $_SESSION["kalender_cn"]["datum"] = $_POST["datum1"];
                            $_SESSION["kalender_cn"]["art"] = $_POST["art"];
                            $_SESSION["kalender_cn"]["omschr"] = $_POST["omschr"];
                            $_SESSION["kalender_cn"]["prijs"] = $_POST["prijs"];
                            $_SESSION["kalender_cn"]["klant_id"] = $_POST["sel_klant"];
                            $_SESSION["kalender_cn"]["btw"] = $_POST["btw"];
                            $_SESSION["kalender_cn"]["factuur"] = $_POST["factuur"];
                            ?>
                            <script type='text/javascript'>
                                window.open('klanten_cn.php?klant_id=<?php echo $_POST["sel_klant"]; ?>&cn_nr=<?php echo $_POST["cn_nr"]; ?>&datum=<?php echo $_POST["datum1"]; ?>');
                            </script>
        <?php
    }
}
?>

                </div>

                <div id="tabs-5">
                    <?php
                    $tab_id++;
                    ?>
                    <form name='frm_overzicht_sort' id='frm_overzicht_sort' method='post'>
                        <input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' /> 
                    </form>

                    <div id="tabs_fac" >
                        <ul>
                            <?php
                            $q_bj = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                            $i = 0;
                            while ($bj = mysqli_fetch_object($q_bj)) {
                                $i++;
                                echo "<li><a href='#tabs_fac-" . $i . "'>BJ" . $bj->boekjaar_start . " - " . $bj->boekjaar_einde . "</a></li>";
                            }
                            ?> 

                        </ul>
                        <?php
                        $q_bj = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                        $j = 0;
                        while ($bj = mysqli_fetch_object($q_bj)) {
                            $j++;
                            echo "<div id='tabs_fac-" . $j . "'>";


                            //$q = mysqli_query($conn, "SELECT * FROM kal_customers, kal_customers_files WHERE cf_soort = 'factuur' AND cus_active='1' AND cus_factuur_filename != '' " . $sorteer);
                            //$q = mysqli_query($conn, "SELECT * FROM kal_customers, kal_customers_files WHERE cus_id = cf_cus_id AND cf_soort = 'factuur' AND cus_oa = '0' AND cus_active='1' " . $sorteer);
                            //$q = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' " . $sorteer);
                            $q = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_bedrag != '' AND cf_date BETWEEN '".$bj->boekjaar_start."' AND '".$bj->boekjaar_einde."' ORDER BY cf_file ASC");


                            /*
                              echo "<pre>";
                              var_dump( $factuur_arr );
                              echo "</pre>";
                             */

                            $i = 0;

                            $border = 0;
                            $tabelbreedte = "";
                            $breedte0 = 80;
                            $breedte0a = 82;
                            $breedte1 = 250;
                            $breedte2 = 250;
                            $breedte3 = 150;
                            $breedte4 = 100;
                            $breedte5 = 100;
                            $breedte6 = 100;

                            echo "<table cellpadding='0' cellspacing='0' width='" . $tabelbreedte . "' border='" . $border . "' id='fac_lijst'>";
                            echo "<thead>";
                            echo "<tr style='cursor: pointer;'>";
                            echo "<th width='20'></th>";
                            echo "<th width='" . $breedte4 . "'><b> Status </b></th>";
                            echo "<th width='". $breedte4 ."'><b> Datum </b></th>";
                            echo "<th width='" . $breedte1 . "'><b> Naam </b></th>";
                            echo "<th width='" . $breedte3 . "'><b> Adres </b></th>";
                            echo "<th width='" . $breedte3 . "'><b> Gemeente </b></th>";
                            echo "<th width='" . $breedte4 . "'><b> Factuur </b></th>";
                            echo "</tr>";
                            echo "</thead>";
//                            echo "</table>";
                                echo "<tbody>";
                                while ($klant = mysqli_fetch_object($q)) {
                                    $customer = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id=".$klant->cf_cus_id));
                                    $cus_id = $klant->cf_cus_id;

                                    // bedrag incl berekening
                                    $bedrag_incl = $klant->cf_bedrag;

                                    if($customer->cus_bedrijf == ''){
                                        $bedrijf = '';
                                    }else{
                                        $bedrijf = " (".$customer->cus_bedrijf.")";
                                    }
                                    $transaction = mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE factuur_id=".$klant->cf_id);
                                    if(mysqli_num_rows($transaction) > 0){
                                        $trans = mysqli_fetch_object($transaction);
                                        if($trans->status == '1'){
                                            $status = 'Verkoop';
                                        }else{
                                            $status = 'Aankoop';
                                        }
                                    }else{
                                        $status = 'Custom';
                                    }
                                    
//                                    echo "<table cellpadding='0' cellspacing='0' border='0'>";
                                    echo "<tr style='cursor: pointer;'>";
                                    echo "<td class='fac_del_".$klant->cf_id."' width='20'><img src='images/delete.png' /></td>";
                                    if($status == 'Custom'){
                                        echo "<td width='" . $breedte4 . "'>" . $status . "</td>";
                                        echo "<td width='" . $breedte4 . "'>" . changeDate2EU($klant->cf_date) . "</td>";
                                    }else{
                                        echo "<td class='fac_extra_".$klant->cf_id."' width='" . $breedte4 . "'>" . $status . "</td>";
                                        echo "<td class='fac_extra_".$klant->cf_id."' width='" . $breedte4 . "'>" . changeDate2EU($klant->cf_date) . "</td>";  
                                    }                                    
                                    echo "<td width='" . $breedte1 . "' onclick='gotoKlant(" . $cus_id . ")'>" . $customer->cus_naam .$bedrijf. "</td>";
                                    echo "<td width='" . $breedte3 . "' onclick='gotoKlant(" . $cus_id . ")'>" . $customer->cus_straat . " " . $customer->cus_nr . "</td>";
                                    echo "<td width='" . $breedte3 . "' onclick='gotoKlant(" . $cus_id . ")'>" . $customer->cus_postcode . " " . $customer->cus_gemeente . "</td>";
                                    echo "<td width='" . $breedte4 . "'>";
                                    echo "<a href='cus_docs/" . $cus_id . "/factuur/".$bj->boekjaar_start ." - " . $bj->boekjaar_einde."/" . $klant->cf_file . "' target='_blank'>" . $klant->cf_file . "</a><br/>";
                                    echo "</td>";
                                    echo "</tr>";
                                    echo "<tr>";
                                    echo "<td colspan='7'>";
                                    echo "<table cellpadding='0' cellspacing='0' border='0' style='margin-left:50px;display:none;' id='facturen_overzicht_".$klant->cf_id."'>";
                                    echo "</table>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody>";
                            echo "</table>";
                            ?>
    <?php
    echo "</div>";
}
?>



                        <!-- einde loop -->


                    </div> 




                </div>

                <div id="tabs-6">
                            <?php
                            $tab_id++;
                            ?>
                    <div id="tabs_cn" >
                        <ul>
                            <?php
                            $q_bj = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                            $i = 0;
                            while ($bj = mysqli_fetch_object($q_bj)) {
                                $i++;
                                echo "<li><a href='#tabs_cn-" . $i . "'>BJ" . $bj->boekjaar_start . " - " . $bj->boekjaar_einde . "</a></li>";
                            }
                            ?> 

                        </ul>
                        <?php
                        $q_bj = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                        $j = 0;
                        while ($bj = mysqli_fetch_object($q_bj)) {
                            $j++;
                            echo "<div id='tabs_cn-" . $j . "'>";
                            $q_zoek_cn = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'creditnota' AND cf_date BETWEEN '" . $bj->boekjaar_start . "' AND '" . $bj->boekjaar_einde . "' ORDER BY cf_file ");

                            $i = 0;
                            echo "<table width='100%' cellpadding='0' cellspacing='0'>";
                            echo "<tr>";
                            echo "<td></td>";
                            echo "<td><b>Naam</b></td>";
                            echo "<td><b>Gemeente</b></td>";
                            echo "<td><b>Creditnota</b></td>";
                            echo "</tr>";

                            while ($cn = mysqli_fetch_object($q_zoek_cn)) {
                                $i++;
                                $kleur = $kleur_grijs;
                                if ($i % 2) {
                                    $kleur = "white";
                                }

                                $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $cn->cf_soort_id));

                                $cus_id = $klant->cus_id;

                                echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");document.getElementById(\"cus_id1\").value=" . $cus_id . ";' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' >";
                                echo "<td class='cn_del_".$cn->cf_id."'><img src='images/delete.png' /></td>";
                                echo "<td onclick='gotoKlant(" . $klant->cus_id . ")'>" . $klant->cus_naam . "</td>";
                                echo "<td onclick='gotoKlant(" . $klant->cus_id . ")'>" . $klant->cus_postcode . " " . $klant->cus_gemeente . "</td>";
                                echo "<td>";
                                
                                $s_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                                while ($boekjaar = mysqli_fetch_object($s_boekjaren)) {
                                    if ($cn->cf_date > $boekjaar->boekjaar_start && $cn->cf_date <= $boekjaar->boekjaar_einde) {
                                        $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
                                    }
                                }
            
                                echo "<a href='cus_docs/" . $klant->cus_id . "/creditnota/" . $dir ."/" . $cn->cf_file . "' target='_blank' >";
                                echo $cn->cf_file;
                                echo "</a>";

                                echo "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            echo "</div>";
                        }
                        ?> 

                    </div>




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
<?php
mysqli_close($conn);
mysqli_close($conn_mon);
?>
