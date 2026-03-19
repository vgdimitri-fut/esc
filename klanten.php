<?php
session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

$isiPad = (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad');
$isiPhone = (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone');

$aant_verplicht = 0;


/*
echo "<pre>";
var_dump( $_SESSION );
echo "</pre>";
*/


$kleur_aankoop = "#A14599";
$kleur_huur = "blue";
$kleur_koop_z_gsc = "green";

// data: {actie: "delete_year", rec_id: rec_id},
if( isset( $_POST["actie"] ) && $_POST["actie"] == "delete_year" )
{
    $q_del = "DELETE FROM esc_db.kal_team_pressence WHERE id = " . $_POST["rec_id"];
    mysqli_query($conn, $q_del);
    die();
}

//data: {actie: "add_year", cus_id: cus_id, jaar: jaar},
if( isset( $_POST["actie"] ) && $_POST["actie"] == "add_year" )
{
    $q_zoek = mysqli_query($conn, "SELECT * FROM esc_db.kal_team_pressence WHERE cus_id = " . $_POST["cus_id"] . " AND year = '". $_POST["jaar"] ."' ");
    
    if( mysqli_num_rows($q_zoek) == 0 )
    {
        $q_ins = "INSERT INTO esc_db.kal_team_pressence(cus_id, year) VALUES(".$_POST["cus_id"].",".$_POST["jaar"].")";
        mysqli_query($conn, $q_ins);
    }
    
    $q_zoek_jaren = mysqli_query($conn, "SELECT * FROM esc_db.kal_team_pressence WHERE cus_id = " . $_POST["cus_id"] . " ORDER BY year");
        
    if( mysqli_num_rows($q_zoek_jaren) > 0 )
    {
        echo "<br />This team was present in :";
        
        while( $j = mysqli_fetch_object($q_zoek_jaren) )
        {
            echo "<br /><span id='tr_".$j->id."' ><img src='images/delete.png' class='delete_year' rec_id='". $j->id ."' />" . $j->year . "</span>";
        }
    }
    
    die();
}


// BEGIN toevoegen nieuwe klant
// opslaan van de nieuwe klant
if (isset($_POST["bewaar"]) && $_POST["bewaar"] == "Save") {
    $error = "";
    if (empty($_POST["n_naam"]) && empty($_POST["n_bedrijf"])) {
        $error = "Naam en/of bedrijf zijn verplicht";
    } else {
        // evt. opslaan

        if (empty($_POST["nw_offerte_datum"])) {
            $datum = date('d-m-Y');
        } else {
            $datum = explode("-", $_POST["nw_offerte_datum"]);
        }


        $_POST["nw_offerte_datum"] = $datum[2] . "-" . $datum[1] . "-" . $datum[0];

        $q_ins = "INSERT INTO kal_customers(cus_naam,
		                                    cus_bedrijf,
		                                    cus_btw,
		                                    cus_straat,
		                                    cus_nr,
		                                    cus_postcode,
		                                    cus_gemeente,
		                                    cus_email,
		                                    cus_gsm,
		                                    cus_acma,
		                                    cus_offerte_datum
                                                    )
		                            VALUES('" . htmlentities($_POST["n_naam"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_bedrijf"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_btw"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_straat"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_nr"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_postcode"], ENT_QUOTES) . "',
		                                   '" . htmlentities($_POST["n_gemeente"], ENT_QUOTES) . "',
		                                   '" . $_POST["n_email"] . "',
		                                   '" . $_POST["n_gsm"] . "',
		                                   '" . $_POST["nw_acma"] . "',
		                                   '" . $_POST["nw_offerte_datum"] . "')";

        $ok = mysqli_query($conn, $q_ins) or die(mysqli_error($conn) . " " . $q_ins . " " . __LINE__);

        if ($ok) {
            // get id en redirect to tab 1
            $_POST["klant_id"] = mysqli_insert_id($conn);
            $_POST["cus_id1"] = mysqli_insert_id($conn);
            $_REQUEST["tab_id"] = '1';

            if (!empty($_POST["nw_acma"])) {
                $qq_acma = mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST["nw_acma"]) or die(mysqli_error($conn) . " " . __LINE__);
                $q_acma = mysqli_fetch_object($qq_acma);

                $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
                $bericht .= "<tr><td>Beste " . $q_acma->voornaam . " " . $q_acma->naam . " </td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen. - INC ERP</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>Klantgegevens :</td></tr>";
                $bericht .= "<tr><td><b>" . $_POST["n_naam"] . " " . $_POST["n_bedrijf"] . "</b></td></tr>";
                $bericht .= "<tr><td><b>" . $_POST["n_straat"] . " " . $_POST["n_nr"] . "</b></td></tr>";
                $bericht .= "<tr><td><b>" . $_POST["n_postcode"] . " " . $_POST["n_gemeente"] . "</b></td></tr>";
                $bericht .= "<tr><td><b>GSM. : " . $_POST["n_gsm"] . "</b></td></tr>";
                $bericht .= "<tr><td><b>Tel. : " . $_POST["n_tel"] . "</b></td></tr>";
                $bericht .= "</table>";

                mail($q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers);
            }
        }
    }
    echo $error;
}
// EINDE toevoegen nieuwe klant
// START ARRAY VERKOOP
$verkoop_arr["0"] = "N";
$verkoop_arr["1"] = "J, verkoop";
$verkoop_arr["2"] = "J, verhuur";
$verkoop_arr["3"] = "J, RvO";
$verkoop_arr[""] = "";
// EINDE ARRAY VERKOOP

$acmas = array();
// BEGIN meerdere klanten toekennen aan acma
if (isset($_POST["acma_toekennen"]) && $_POST["acma_toekennen"] == "Klanten toekennen" && isset($_POST['naar_acma']) && !empty($_POST['naar_acma'])) {

    $qq_acma = mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['naar_acma']) or die(mysqli_error($conn) . " " . __LINE__);
    $q_acma = mysqli_fetch_object($qq_acma);

    // toekennen van een acma
    if (is_array($_POST["acma_nodig"])) {
        foreach ($_POST["acma_nodig"] as $acma) {
            $acmas[] = $acma;
            $q_klant = "UPDATE kal_customers SET cus_acma = " . $_POST["naar_acma"] . " WHERE cus_id = " . $acma;
            mysqli_query($conn, $q_klant) or die(mysqli_error($conn));

            // mail sturen naar de klant om te verwittigen wie de acma is en indien nodig deze te contacteren.
            $q_klant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $acma) or die(mysqli_error($conn) . " " . __LINE__);
            $klant = mysqli_fetch_object($q_klant);

            if (!empty($klant->cus_email)) {
                /*
                  $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
                  $bericht .= "<tr><td>Beste ". $klant->cus_naam . "</td></tr>";
                  $bericht .= "<tr><td>&nbsp;</td></tr>";
                  $bericht .= "<tr><td>Bedankt voor uw offerte aanvraag.</td></tr>";
                  //$bericht .= "<tr><td>Gezien de grote drukte zijn we niet meer in de mogelijkheid u binnen de 48 uur te contacteren omtrent uw interesse in zonnepanelen.</td></tr>";
                  $bericht .= "<tr><td>&nbsp;</td></tr>";

                  $bericht .= "<tr><td>&nbsp;</td></tr>";
                  $bericht .= "<tr><td>Uw aanvraag werd doorgestuurd naar onze adviseur :</td></tr>";
                  $bericht .= "<tr><td>Naam : " . $q_acma->naam . " " . $q_acma->voornaam . "</td></tr>";
                  $bericht .= "<tr><td>Tel : " . $q_acma->tel . "</td></tr>";
                  $bericht .= "<tr><td>U kan steeds de adviseur zelf contacteren voor dringende zaken.</td></tr>";
                  $bericht .= "<tr><td>&nbsp;</td></tr>";
                  $bericht .= "<tr><td>Uw gegevens :</td></tr>";
                  $bericht .= "<tr><td>". $klant->cus_naam . " " . $klant->cus_bedrijf ."</td></tr>";
                  $bericht .= "<tr><td>". $klant->cus_straat . " " . $klant->cus_nr ."</td></tr>";
                  $bericht .= "<tr><td>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td></tr>";
                  $bericht .= "<tr><td>GSM. : ". $klant->cus_gsm ."</td></tr>";
                  $bericht .= "<tr><td>Tel. : ". $klant->cus_tel ."</td></tr>";
                  $bericht .= "<tr><td>&nbsp;</td></tr>";

                  $bericht .= "</table>";

                  $mail = new PHPMailer();

                  $mail->From     = $q_acma->email;
                  $mail->FromName = $q_acma->voornaam . " " . $q_acma->naam;
                  $mail->Subject = "Uw offerte aanvraag";
                  //$mail->IsSMTP();

                  $mail->Host     = "localhost";
                  $mail->IsHTML(true);// send as HTML
                  $mail->Mailer   = "sendmail";

                  $text_body  = $bericht;

                  $body = $text_body;

                  $mail->Body    = $body;
                  $mail->AltBody = $text_body;
                  $mail->AddAddress($klant->cus_email, $klant->cus_naam);

                  if($klant->cus_int_solar == '1')
                  {
                  $mail->AddAttachment("downloads/FUTE_magazine_EVV07.pdf");
                  }

                  $mail->Send();
                 */
            }
        }
    }

    // mailen naar de acma dat er nieuwe klanten zijn toegevoegd
    $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
    $bericht .= "<tr><td>Beste " . $q_acma->naam . " " . $q_acma->voornaam . " </td></tr>";
    $bericht .= "<tr><td>&nbsp;</td></tr>";
    $bericht .= "<tr><td>U heeft ��n of meerdere nieuwe klanten toegekend gekregen.</td></tr>";
    $bericht .= "<tr><td>&nbsp;</td></tr>";

    foreach ($acmas as $klant) {
        $q_klant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant) or die(mysqli_error($conn) . " " . __LINE__);
        $a_klant = mysqli_fetch_object($q_klant);

        $bericht .= "<tr><td><br/>Klantgegevens :</td></tr>";
        $bericht .= "<tr><td><b>" . $a_klant->cus_naam . " " . $a_klant->cus_bedrijf . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $a_klant->cus_straat . " " . $a_klant->cus_nr . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $a_klant->cus_postcode . " " . $a_klant->cus_gemeente . "</b></td></tr>";
        $bericht .= "<tr><td><b>GSM. : " . $a_klant->cus_gsm . "</b></td></tr>";
        // TEL AAAAA
        $bericht .= "<tr><td><b>Tel. : " . $a_klant->cus_tel . "</b></td></tr>";
    }
    $bericht .= "</table>";

    mail($q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers);
}
// EINDE meerdere klanten toekennen aan acma

$verwijderen = 0;
if (isset($_POST["verwijderen"]) && $_POST["verwijderen"] == "Delete" && $_POST["cus_id"] > 0) {
    //$q_del = "DELETE FROM kal_customers WHERE cus_id = " . $_POST["cus_id"];
    $q_del = "UPDATE kal_customers SET cus_active = '0' WHERE cus_id = " . $_POST["cus_id"];

    $ok = mysqli_query($conn, $q_del) or die(mysqli_error($conn) . " " . __LINE__);

    if ($ok) {
        $verwijderen = 1;
    }
}

/* verwijderen van de extra kost */
if (isset($_GET["ek"]) && is_numeric($_GET["ek"])) {
    $q_del = "DELETE FROM kal_customers_project_extra WHERE id = " . $_GET["ek"] . " LIMIT 1";
    mysqli_query($conn, $q_del) or die(mysqli_error($conn) . " " . __LINE__);
}

// KLANTEN AANPASSEN GEKLIKT
if (isset($_POST["pasaan"]) && $_POST["pasaan"] == "Save") {
    include "klanten/klanten_aanpassen.php";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv='Content-Type' content='text-html;charset=UTF-8' /> 
<?php
$extra_url = "";
$name_title = "";
// naam in titel zetten

$p_cus_id1 = 0;
if (isset($_POST["cus_id1"])) {
    $p_cus_id1 = $_POST["cus_id1"];
}

$p_cus_id2 = 0;
if (isset($_POST["cus_id2"])) {
    $p_cus_id2 = $_POST["cus_id2"];
}

$p_klant_id = 0;
if (isset($_REQUEST["klant_id"])) {
    $p_klant_id = $_REQUEST["klant_id"];
}

if (((isset($_POST["submit"]) && $_POST["submit"] == "Go") || $p_cus_id1 > 0 || $p_cus_id2 > 0 || $p_klant_id > 0 ) && $verwijderen == 0) {
    if (isset($_REQUEST["klant_id"]) && !isset($_POST["klant_val"])) {
        $_POST["klant_val"] = $_REQUEST["klant_id"];
    }

    if (isset($_POST["cus_id1"])) {
        $_POST["klant_val"] = $_POST["cus_id1"];
    }

    if (isset($_POST["cus_id2"])) {
        $_POST["klant_val"] = $_POST["cus_id2"];
    }
}
?>
        <title>Solar Teams<?php include "inc/erp_titel.php"; ?></title>
        <link rel="SHORTCUT ICON" href="favicon.ico" />
        <!-- STYLE -->
        <link href="css/klanten.css" rel="stylesheet" type="text/css" />
        <link href="css/style.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
        <link href="css/print.css" rel="stylesheet" type="text/css" media="print"/>
        <link href="fancybox/jquery.fancybox-1.3.4.css" rel="stylesheet" type="text/css" media="screen" />
        <!-- SCRIPTS -->
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/jquery.validate.js"></script>
        <script type="text/javascript" src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
        <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
        <script type="text/javascript" src="js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="js/jquery.ui.widget.js"></script>
        <script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
        <script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
        <script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
        <script type="text/javascript" src="js/highstock.js"></script>
        <script type="text/javascript" src="js/functions.js"></script>
        <script type="text/javascript" src="js/klanten.js"></script>


        <script type="text/javascript">
        
            
                
                
            
            
        
        
            $(function() {
                
                $('.add_year').live('click',function(){
                    
                    var cus_id = $(this).attr("rec_id");
                    var jaar = $("#sel_jaar").val();
                    
                    $.ajax({
                        data: {actie: "add_year", cus_id: cus_id, jaar: jaar},
                        type:'POST',
                        success: function(data) {
                            $("#show_years").html(data);
                        }
                    });
                });
                
                $('.delete_year').live('click',function(){
                    
                    var rec_id = $(this).attr("rec_id");
                    
                    if( confirm('Delete year?') )
                    {
                        
                        $.ajax({
                            data: {actie: "delete_year", rec_id: rec_id},
                            type:'POST',
                            success: function(data) {
                                $("#tr_" + rec_id).css("display","none");
                            }
                        });
                    }
                });
                
                $('.send_factuur').live('click',function(){
                    var rec_id = $(this).attr("rec_id");
                    
                    if( confirm("Factuur versturen naar de klant") )
                    {
                        
                        $.ajax({
                            url: "ajax/klant_send_factuur.php",
                            data: {actie: "send_factuur", rec_id: rec_id},
                            type: 'POST',
                            /*
                            beforeSend: function(data) {
                                $('#div_zoek_res').html("<img src='images/indicator.gif' />")
                            },
                            */
                            success: function(data) {
                                $('#div_zoek_res').html(data)
                            }
                        });
                        
                    }
                    
                });
                
                $("#tabs").tabs({selected: <?php
            if (isset($_REQUEST["tab_id"])) {
                echo $_REQUEST["tab_id"];
            } else {
                echo 0;
            };
            ?>});
            });
        </script>
    </head>
    <body>
        <div id='pagewrapper'>
        
        
            <?php 


                include('inc/header.php'); 
                
            ?>

            <form name='frm_overzicht' id='frm_overzicht' method='post'>
                <input type='hidden' name='tab_id' id='tab_id' value='1' /> 
                <input type='hidden' name='cus_id1' id='cus_id1' />
            </form>

            <br />
            <h1>Solar Teams</h1>
            <br />

            <div id="tabs" style="width: 1000px;">
                <ul>
                    <li><a href="#tabs-1">New</a></li>
                    <li><a href="#tabs-2">Search</a></li>
                    <li><a href="klanten/klanten_overzicht.php" title="Overzicht">Overview</a></li>
                    <li><a href="klanten/klanten_verkoop.php" title="Verkoop" >Sale</a></li>
                    <li><a href="klanten/klanten_geenov.php" title="Geen_Overeenkomst" style='font-size:0.63em;'>No <br/> agreement </a></li>
                    <li><a href="#tabs-10" style='font-size:0.63em;'>Advanced<br/>search</a></li>
                </ul>
                <div id="tabs-1"> <?php include "klanten/klanten_nieuw.php"; ?>  </div>

                <div id="tabs-2"> <?php include "klanten/klanten_zoek.php"; ?>  </div>
                <div id='tabs-10'>
                    Enter one or more fields in part or in full to search a solar team.<br/><br/>

                    <form method='post' name='frm_uit_zoeken' id='frm_uit_zoeken'>
                        <table>
                            <tr>
                                <td> Reference : </td>
                                <td> <input type='text' name='z_ref' id='z_ref' /> </td>
                            </tr>

                            <tr>
                                <td> Name : </td>
                                <td> <input type='text' name='z_naam' id='z_naam' /> </td>
                            </tr>

                            <tr>
                                <td>Company :</td>
                                <td> <input type='text' name='z_bedrijf' id='z_bedrijf' /> </td>
                            </tr>

                            <tr>
                                <td>Street :</td>
                                <td> <input type='text' name='z_straat' id='z_straat' /> </td>
                            </tr>

                            <tr>
                                <td> House nr. : </td>
                                <td> <input type='text' name='z_nr' id='z_nr' /> </td>
                            </tr>

                            <tr>
                                <td>Zip code :</td>
                                <td> <input type='text' name='z_postcode' id='z_postcode' /> </td>
                            </tr>

                            <tr>
                                <td> City : </td>
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
                                <td> Name of the bank: </td>
                                <td> <input type='text' name='z_bank' id='z_bank' /> </td>
                            </tr>

                            <tr>
                                <td> PVZ nr. : </td>
                                <td> <input type='text' name='z_pvz' id='z_pvz' /> </td>
                            </tr>

                            <tr>
                                <td> MB nr. : </td>
                                <td> <input type='text' name='z_mb' id='z_mb' /> </td>
                                <td> <input type='button' name='z_zoek' id='z_zoek' value='Zoek' onclick="uitgebreidZoek('res_uitgebreid_zoeken');" /> </td>
                            </tr>
                        </table>


                        <div id="res_uitgebreid_zoeken"></div>
                    </form>
                </div>
            </div>
        </div>
        <center><?php include "inc/footer.php"; ?></center>
    </body>
</html>
<script type="text/javascript">
    $('#frm_go').submit(function() {
        //alert("test");
        $('input:file[value=""]').attr('disabled', true);

    });
</script>

<?php
mysqli_close($conn);
?>
