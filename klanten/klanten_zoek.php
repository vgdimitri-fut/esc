<?php

include "inc/db.php";

?>

<table width='100%'>
    <tr>
        <td><?php
            if ($_SESSION[ $session_var ]->group_id == 3) {
                $q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_acma = " . $_SESSION[ $session_var ]->user_id . " ORDER BY cus_naam, cus_bedrijf") or die(mysqli_error($conn) . " " . __LINE__);
            } else {
                $q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers ORDER BY cus_naam, cus_bedrijf") or die(mysqli_error($conn) . " " . __LINE__);
            }
            ?>
            
            <script type="text/javascript">
                function searchKlant()
                {
                    var klant = document.getElementById("klant_val").value;
                    //alert(window.location.host);
                    document.frm_klant.action = "https://" + window.location.host + "/esc/klanten.php?tab_id=1&klant_id=" + klant;
                    //document.frm_klant.action = "http://www.google.be";
                    document.frm_klant.submit();
                }
            </script>
            <form autocomplete="off" method='post' id='frm_klant' name='frm_klant' action="" accept-charset="UTF-8">
                <label for='klant'>Search team :</label> <input type="text" name="klant" id="klant" /> 
                <input type="hidden" name="klant_id" id="klant_val" /> 
                <input type='hidden' name='tab_id' id='tab_id' value='1' /> 
                <input type="button" name="button" onclick="searchKlant();" value="Go" />
            </form>
        </td>
        <td align='right'>
            <?php
            // BBBBBBBBBBBBBBBBBB
            if (isset($_POST["pasaan"]) && $_POST["cus_id2"] > 0) {
                ?>
                <script type='text/javascript'>
                    $(function() {
                        $("#go_away1").fadeOut(5000);
                    });
                </script> 
                <?php
                if (isset($_POST["invitees"]) && count($_POST["invitees"]) > 0) {
                    echo "<span id='go_away1' class='correct' >Gegevens zijn bewaard &amp; uitgenodigden gemaild.</span>";
                } else {
                    echo "<span id='go_away1' class='correct' >Gegevens zijn bewaard</span>";
                }
            }

            echo "<span id='go_away_mail' style='display:none;' class='correct' >E-mail is verstuurd.</span>";

            if ($verwijderen == 1) {
                ?> <script type='text/javascript'>
                    $(function() {
                        $("#go_away2").fadeOut(5000);
                    });
                </script> <?php
                echo "<span id='go_away2' class='correct' >Customer is deleted.</span>";
            }


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
                $p_klant_id = (int) $_REQUEST["klant_id"];
            }

            if (((isset($_POST["submit"]) && $_POST["submit"] == "Go") || $p_cus_id1 > 0 || $p_cus_id2 > 0 || $p_klant_id > 0) && $verwijderen == 0) {
                echo "&nbsp;&nbsp;&nbsp;";
                //echo "test:".$_REQUEST["klant_id"];
                if (isset($_REQUEST["klant_id"])) {
                    $_POST["klant_val"] = $_REQUEST["klant_id"];
                }

                if (isset($_POST["cus_id1"])) {
                    $_POST["klant_val"] = $_POST["cus_id1"];
                }

                if (isset($_POST["cus_id2"])) {
                    $_POST["klant_val"] = $_POST["cus_id2"];
                }
                $klant_val = '';
                if (isset($_POST["klant_val"]))
                    $klant_val = $_POST["klant_val"];
                
                $q = mysqli_query($conn, "SELECT * FROM kal_customer_mail WHERE cus_id = " . $klant_val) or die( mysqli_error($conn) . " " . __LINE__ );
                $aant_archief_mail = mysqli_num_rows($q);

                if ($aant_archief_mail > 0) {
//                echo "<a id='various_m' class='verkoop_gegevens' href='http://www.solarlogs.be/kalender/klanten/mail.php?cus_id=".$klant_val."'>";
                    echo "<input type='button' value='Mails' />";
                    echo "</a>";
                }
                
                //$isProject = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $_POST["klant_val"]));
                $q_cus = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["klant_val"]) or die(mysqli_error($conn) . " " . __LINE__);
                $cus = mysqli_fetch_object($q_cus);

                $isProject = 0;
                if ($cus->cus_10 == '1') {
                    $isProject = 1;
                }

                if ($isProject) {
                    ?>
                    <input type='button' name='btn_verslag' id='btn_verslag' value="Rendementsverslag" onclick="window.open('toon_verslag.php?cus_id=<?php echo $_POST["klant_val"]; ?>', 'Toon verslag', 'status,width=1100,height=960,scrollbars=yes');
                                                        return false;" />
                           <?php
                       }
                       ?>


                <!--
                <input type='button' name='btn_grafiek' id='btn_grafiek' value="Grafiek v2" onclick="window.open('mijnfutech_solarlogs_v2.php?cus_id=<?php echo $_POST["klant_val"]; ?>','Monitoring tool','status,width=1300,height=960,scrollbars=yes'); return false;" />
                -->

                <?php
                
                    // als er coda bestanden zijn dan de knop weergeven.
                    
                    
                    $q = "SELECT * FROM kal_coda WHERE cus_id = " . $_POST["klant_val"];
                    $q_aant_coda = mysqli_query($conn, $q) or die(mysqli_error($conn) . " " . __LINE__);
                    $aant_coda = mysqli_num_rows($q_aant_coda);

                    if ($aant_coda > 0) 
                    {
                        ?>
                        <input type='button' value='CODA' onclick="window.open('klanten_coda.php?klant_id=<?php echo $_POST["klant_val"]; ?>', 'Coda', 'status,width=1100,height=800,scrollbars=yes');return false;" />
                        <?php   
                    }
                   
                   ?>


                    <input type='button' value='History' onclick="window.open('geschiedenis.php?klant_id=<?php echo $_POST["klant_val"]; ?>', 'geschiedenis', 'status,width=1100,height=800,scrollbars=yes');return false;" />
                       <?php
                   }
                   ?></td>
    </tr>
</table>

<?php
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
    $p_klant_id = (int) $_REQUEST["klant_id"];
}

if(isset($_POST['tab_id'])){
    $tab_id = $_POST['tab_id'];
}
if (((isset($_POST["submit"]) && $_POST["submit"] == "Go") || $p_cus_id1 > 0 || $p_cus_id2 > 0 || $p_klant_id > 0 ) && $verwijderen == 0) {
    echo "<br/>";

    if (isset($_REQUEST["klant_id"]) && !isset($_POST["klant_val"])) {
        $_POST["klant_val"] = $_REQUEST["klant_id"];
    }

    if (isset($_POST["cus_id1"])) {
        $_POST["klant_val"] = $_POST["cus_id1"];
    }

    if (isset($_POST["cus_id2"])) {
        $_POST["klant_val"] = $_POST["cus_id2"];
    }

    if ($_POST["klant_val"] == "") {
        echo "No customers found.";
    } else {
        /*
          Verwijderen van de regel in tabel user_open_cus_id
          daarna user_id en cus_id toevoegen
         */
        // BEGIN CONTROLE OM TE ZIEN WIE DE KLANT GEOPEND HEEFT
        /*
        $q_del = "DELETE FROM monitoring.user_open_cus_id WHERE user_id = " . $_SESSION[ $session_var ]->user_id;
        mysqli_query($conn, $q_del) or die(mysqli_error($conn) . " " . __LINE__);

        $q_ins = "INSERT INTO monitoring.user_open_cus_id(user_id, cus_id) VALUES(" . $_SESSION[ $session_var ]->user_id . "," . $_POST["klant_val"] . ")";
        mysqli_query($conn, $q_ins) or die(mysqli_error($conn) . " " . __LINE__ );
        */
        // EINDE TEST
        // UITLEZEN VAN DE NIEUWE TABEL EN TONEN WIE DAT ER DEZE KLANT GEOPEND HEEFT.
        $q_zoek_klant = mysqli_query($conn, "SELECT * FROM monitoring.user_open_cus_id WHERE cus_id = " . $_POST["klant_val"]);

        if (mysqli_num_rows($q_zoek_klant) > 0) {
            $tmp_g = "";
            $nu = date('Y') . date('m') . date('d');

            while ($u = mysqli_fetch_object($q_zoek_klant)) {
                $dt = explode("-", substr($u->datetime, 0, 10));
                $dt = $dt[0] . $dt[1] . $dt[2];

                if ($dt < $nu) {
                    // verwijderen van de regel van geopende klant uit het verleden
                    $q_del = "DELETE FROM monitoring.user_open_cus_id WHERE id = " . $u->id;
                    mysqli_query($conn, $q_del) or die(mysqli_error($conn));
                } else {
                    if ($u->user_id != $_SESSION[ $session_var ]->user_id) {
                        $gebruiker = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $u->user_id));
                        $tmp_g .= $gebruiker->voornaam . ", ";
                    }
                }
            }

            if (!empty($tmp_g)) {
                echo "<div style='width:948px;height:20px;border:2px solid orange;background-color:#FFFFCC;padding:2px;padding-left:10px;padding-top:4px;'>";
                echo "This customer is open in following user(s): ";
                echo substr($tmp_g, 0, -2);
                echo "</div>";
                echo "<br/>";
            }
        }


        $q_cus = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["klant_val"]);
        $aant_cus = mysqli_num_rows($q_cus);
        $cus = mysqli_fetch_object($q_cus);

        //echo "<form method='post' action='". str_replace("/kalender", "", $_SERVER['PHP_SELF']) ."' id='frm_go' enctype='multipart/form-data'>";
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "' id='frm_go' class='frm_go' name='frm_go' enctype='multipart/form-data'>";

        //$isProject = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $_POST["klant_val"]));

        $isProject = 0;
        if ($cus->cus_id == 3230) {
            $isProject = 1;
        }

        if ($cus->cus_10 == '1') {
            $isProject = 1;
        }

        if ($isProject) {
            echo "<table border='0' width='100%' class='main_table' style='background-color:lightblue;' >";
        } else {
            echo "<table border='0' width='100%' class='main_table' >";
        }


        echo "<tr>";
        echo "<td valign='top' width='50%'>";

        // begin eerste tabel
        echo "<table border='0'>";
        echo "<tr><td colspan='2'>";

        echo "<fieldset>";
        echo "<legend>Klantgegevens (Referte : " . maakReferte($cus->cus_id, $conn) . ") <a href='klanten.php?tab_id=1&klant_id=" . $cus->cus_id . "'> <img src='images/refresh.png' width='16' height='16' alt='Vernieuwen' title='Vernieuwen' border='0' /> </a> </legend>";
        echo "<table border='0'>";
        echo "<tr>";
        echo "<td class='klant_gegevens' >Manager:</td>";
        echo "<td>";

        $hoofdgroep = array($cus->cus_acma);

        /*
          if( $cus->cus_int_boiler == '1' )
          {
          $boiler_entry = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_boiler WHERE cus_id = " . $cus->cus_id));
          $hoofdgroep[] = $boiler_entry->cus_acma;
          }
         */
        //cccccccc
        if ((in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep)) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' style='border:2px solid green;' name='naam' id='naam' class='lengte' value='" . $cus->cus_naam . "' />";
        } else {
            echo $cus->cus_naam;
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Team:</td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='bedrijf' id='bedrijf' class='lengte' value='" . $cus->cus_bedrijf . "' />";
        } else {
            echo $cus->cus_bedrijf;
        }

        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td class='klant_gegevens'>School:</td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='school' id='school' class='lengte' value='" . $cus->cus_school . "' />";
        } else {
            echo $cus->cus_school;
        }

        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td class='klant_gegevens'>Email facturatie:</td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='fac_mail' id='fac_mail' class='lengte' value='" . $cus->cus_email . "' />";
        } else {
            echo $cus->cus_email;
        }

        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td colspan='2'>";
        echo "<strong>Contactpersonen:</strong><br/>";

        if (( $_SESSION[ $session_var ]->user_id == $cus->cus_acma && isset($cus->cus_acma) ) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<textarea rows='5' style='width:395px;' name='contact' id='contact' >" . $cus->cus_contact1 . "</textarea>";
        } else {
            echo $cus->cus_contact1;
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>VAT:</td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='btw_edit' id='btw_edit' class='lengte' value='" . $cus->cus_btw . "' onblur='berekenPrijs();' />";
        } else {
            echo $cus->cus_btw;
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Reference:</td>";
        echo "<td>";

        switch ($cus->cus_ref) {
            case '0':
                $ref_chk = "";
                $ref = "Nee";
                break;
            case '1' :
                $ref_chk = " checked='checked' ";
                $ref = "Ja";
                break;
        }

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input onclick='checkref(this);' type='checkbox' " . $ref_chk . " name='ref' id='ref' />";
        } else {
            echo $ref;
        }

        echo "</td>";
        echo "</tr>";

        $stijl_ref = " style='display:none;' ";
        if ($cus->cus_ref == 1) {
            $stijl_ref = "";
        }

        echo "<tr><td class='klant_gegevens'><span id='ref1' " . $stijl_ref . ">Lengtegraad :</span></td><td><span id='ref3' " . $stijl_ref . ">";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' class='lengte' name='lengte' id='lengte' value='" . $cus->cus_ref_lengte . "' />";
        } else {
            echo $cus->cus_ref_lengte;
        }

        echo "</span></td></tr>";

        echo "<tr><td class='klant_gegevens'><span id='ref2' " . $stijl_ref . ">Latitude :</span></td><td><span id='ref4' " . $stijl_ref . ">";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' class='lengte' name='breedte' id='breedte' value='" . $cus->cus_ref_breedte . "' />";
        } else {
            $cus->cus_ref_breedte;
        }

        echo "</span></td></tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Contracting partner:</td>";

        echo "<td>";

        $contractant = "";
        $contractant_chk = "";

        switch ($cus->cus_medecontractor) {
            case '0':
                $contractant_chk = "";
                $contractant = "Nee";
                break;
            case '1' :
                $contractant_chk = " checked='checked' ";
                $contractant = "Ja";
                break;
        }

        echo "<table width='230' cellpadding='0' cellspacing='0' border='0'>";
        echo "<tr>";
        echo "<td align='left'>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='checkbox' " . $contractant_chk . " name='contractant' id='contractant' />";
        } else {
            echo $contractant;
        }

        echo "</td>";
        echo "<td align='right'>";

        //http://be.bing.com/maps/?v=2&where1=10%20downing%20street,%20london&sty=b
        echo "<a title='Toon locatie in Bing Maps' href='https://be.bing.com/maps/?v=2&where1=" . $cus->cus_straat . " " . $cus->cus_nr . ", " . $cus->cus_postcode . " " . $cus->cus_gemeente . "&sty=b'  target='_blank'> <img border='0' src='images/bing.png' /> </a>";
        echo "<a title='Toon locatie in Google Maps' href='https://maps.google.be/maps?q=" . $cus->cus_straat . "+" . $cus->cus_nr . "+" . $cus->cus_postcode . "+" . $cus->cus_gemeente . "'  target='_blank'> <img border='0' src='images/google.png' /> </a>";

        echo "</td>";
        echo "</tr>";
        echo "</table>";

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Street &amp; Nr.: </td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' style='border:2px solid green;' name='straat' id='straat' value='" . $cus->cus_straat . "' /> ";
            echo "<input type='text' style='border:2px solid green;' name='nr' id='nr' value='" . $cus->cus_nr . "' size='4' />";
        } else {
            echo $cus->cus_straat . " " . $cus->cus_nr;
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'> Zip code &amp; city:</td>";
        echo "<td>";

        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' style='border:2px solid green;' name='postcode' id='postcode' value='" . $cus->cus_postcode . "' size='4' /> ";
            echo "<input type='text' style='border:2px solid green;' name='gemeente' id='gemeente' value='" . $cus->cus_gemeente . "' />";
        } else {
            echo $cus->cus_postcode . " " . $cus->cus_gemeente;
        }

        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        $q_naam_bedrijf = mysqli_query($conn, "SELECT bedrijf_naam FROM kal_instellingen");
        $banknaam = mysqli_fetch_array($q_naam_bedrijf);
        ?>
        <td class='klant_gegevens'>Country :</td>
        <td>
            <select class="lengte" name="land" id="land">

                <?php
                $q_bank = mysqli_query($conn, "SELECT * FROM kal_landen ORDER BY land ASC");

                while ($bank = mysqli_fetch_object($q_bank)) {
                    $sel = "";

                    if ($bank->id == $cus->cus_land_id) {
                        $sel = " selected='selected' ";
                    }

                    echo "<option " . $sel . " value='" . $bank->id . "' >" . $bank->land . "</option>";
                }
                ?>


            </select> 
        </td>
        </tr>
        <?php
        echo "<tr>";
        // EMAIL
        $q_get_all_email = mysqli_query($conn, "SELECT * from kal_customers_details WHERE cus_id='" . $cus->cus_id . "' AND soort='3'");
        // Als er data is
        $aantal_email = mysqli_num_rows($q_get_all_email);
        $email_teller = 1;
        if ($aantal_email == 0) {
            // GEEN EMAIL
            echo "<tr>";
            echo "<td class='klant_gegevens'>";
            echo "<table cellpadding='0' cellspacing='0' width='100%'>";
            echo "<tr>";
            echo "<td class='klant_gegevens'>E-mail:</td>";
            echo "<td align='right'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</td>";
            echo "<td>";
            echo "<input type='text' style='border:2px solid green;' id='email_1' name='email[]' title='" . $cus->cus_id . "' class='lengte' value='' /><a href='' class='add_email' title='email toevoegen' style='margin-left:10px'>+</a>";
            echo "</td>";
            echo "</tr>";
            // EINDE GEEN EMAIL
        } else {
            // LOOP ALLE EMAILS
            while ($emails = mysqli_fetch_object($q_get_all_email)) {
                if ($email_teller == 1) {
                    // EERSTE KEER EMAIL
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_email' alt=''>";
                    echo "<img src='images/delete.png' title='" . $emails->id . "'/>";
                    echo "</a>";
                    echo "E-mail 1.:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='email_" . $email_teller . "' title='" . $emails->id . ',' . $emails->cus_id . "' name='email[details_" . $emails->id . "]' class='lengte' value='" . $emails->waarde . "' />";
                    // eerste keer toon plus icon
                    echo "<a href='' class='add_email' title='Email toevoegen' style='margin-left:10px'>+</a>";
                    echo "</td>";
                    // EINDE EERSTE KEER EMAIL
                } else {
                    // VOLGENDE EMAILS
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_email' alt=''>";
                    echo "<img src='images/delete.png' title='" . $emails->id . "'/>";
                    echo "</a>";
                    echo "E-mail " . $email_teller . ".:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='email_" . $email_teller . "' title='" . $emails->id . ',' . $emails->cus_id . "' name='email[details_" . $emails->id . "]' class='lengte' value='" . $emails->waarde . "' />";
                    echo "</td>";
                    echo "</tr>";
                    // EINDE VOLGENDE EMAILS
                }
                // TELLER +1
                $email_teller++;
            }
            echo "</tr>";
        }
//        if ($isProject) {
//            echo "<tr>";
//            echo "<td class='klant_gegevens'> <img style='float:left;' src='images/info.jpg' width='16px' height='16px' alt='Gebruik ; tussen de e-mailadressen' title='Gebruik ; tussen de e-mailadressen' />&nbsp; E-mail verslag:</td>";
//            echo "<td>";
//            echo "<input type='text' class='lengte' name='mail_verslag' id='mail_verslag' value='" . $cus->cus_email_verslag . "' />";
//            echo "</td>";
//            echo "</tr>";
//        }
//
//        // zoeken naar klanten die ook dit email adres gebruiken.
//        if (!empty($cus->cus_email)) {
//            $q_mail = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' AND cus_email = '" . $cus->cus_email . "' AND cus_id != " . $cus->cus_id . " ORDER BY cus_naam");
//
//            if (mysqli_num_rows($q_mail) > 0) {
//                echo "<tr>";
//                echo "<td colspan='2' style='color:red;'>";
//
//                echo "DUBBELE KLANT?<br/>";
//            }
//
//            if (mysqli_num_rows($q_mail) == 1) {
//                echo "Er is nog " . mysqli_num_rows($q_mail) . " klant gevonden met dit e-mail adres.";
//            } else {
//                if (mysqli_num_rows($q_mail) > 1) {
//                    echo "Er zijn nog " . mysqli_num_rows($q_mail) . " klanten gevonden met dit e-mail adres.";
//                }
//            }
//
//            //echo "<table width='100%'>";
//
//            echo "&nbsp;<span style='cursor:pointer;' id='switch_dubbele_klant' onclick='toonDubbeleKlant();' ><b>Toon</b></span>";
//            echo "<br/>";
//
//
//            if (mysqli_num_rows($q_mail) > 0) {
//                echo "<div id='dubbele_klant' style='display:none;' >";
//                echo "<table cellspacing='0'>";
//
//                while ($r = mysqli_fetch_object($q_mail)) {
//                    echo "<tr><td>";
//
//                    echo maakReferte($r->cus_id, $conn);
//
//                    echo "</td><td>&nbsp;</td><td>";
//
//                    $nnaamm = $r->cus_naam;
//
//                    if (empty($nnaamm)) {
//                        $nnaamm = $r->cus_bedrijf;
//                    }
//
//                    echo "<a href='klanten.php?tab_id=1&klant_id=" . $r->cus_id . "'>" . $nnaamm . "</a>";
//
//                    echo "</td></tr>";
//                }
//
//                echo "</table>";
//                echo "</div>";
//            }
//
//            //echo "</table>";
//
//            if (mysqli_num_rows($q_mail) > 0) {
//                echo "</td>";
//                echo "</tr>";
//            }
//        }
        // GSM 
        $q_get_all_gsm = mysqli_query($conn, "SELECT * from kal_customers_details WHERE cus_id='" . $cus->cus_id . "' AND soort='2'");
        // Als er data is
        $aantal_gsm = mysqli_num_rows($q_get_all_gsm);
        $gsm_teller = 1;
        if ($aantal_gsm == 0) {
            // GEEN TELEFOON
            echo "<tr>";
            echo "<td class='klant_gegevens'>";
            echo "<table cellpadding='0' cellspacing='0' width='100%'>";
            echo "<tr>";
            echo "<td class='klant_gegevens'>GSM:</td>";
            echo "<td align='right'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</td>";
            echo "<td>";
            echo "<input type='text' style='border:2px solid green;' id='gsm_1' name='gsm[]' title='" . $cus->cus_id . "' class='lengte' value='' /><a href='' class='add_gsm' title='Gsm toevoegen' style='margin-left:10px'>+</a>";
            echo "</td>";
            echo "</tr>";
            // EINDE GEEN TELEFOON
        } else {
            // LOOP ALLE TELEFOONS
            while ($gsms = mysqli_fetch_object($q_get_all_gsm)) {
                if ($gsm_teller == 1) {
                    // EERSTE KEER TELEFOON
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_gsm' alt=''>";
                    echo "<img src='images/delete.png' title='" . $gsms->id . "'/>";
                    echo "</a>";
                    echo "GSM 1.:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='gsm_" . $gsm_teller . "' title='" . $gsms->id . ',' . $gsms->cus_id . "' name='gsm[details_" . $gsms->id . "]' class='lengte' value='" . $gsms->waarde . "' />";
                    // eerste keer toon plus icon
                    echo "<a href='' class='add_gsm' title='Gsm toevoegen' style='margin-left:10px'>+</a>";
                    echo "</td>";
                    // EINDE EERSTE KEER TELEFOON
                } else {
                    // VOLGENDE TELEFOONS
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_gsm' alt=''>";
                    echo "<img src='images/delete.png' title='" . $gsms->id . "'/>";
                    echo "</a>";
                    echo "GSM " . $gsm_teller . ".:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='gsm_" . $gsm_teller . "' title='" . $gsms->id . ',' . $gsms->cus_id . "' name='gsm[details_" . $gsms->id . "]' class='lengte' value='" . $gsms->waarde . "' />";
                    echo "</td>";
                    echo "</tr>";
                    // EINDE VOLGENDE TELEFOONS
                }
                // TELLER +1
                $gsm_teller++;
            }
            echo "</tr>";
        }
        // TELEFOON // TEL bbbbbbbbb
        $q_get_all_tel = mysqli_query($conn, "SELECT * from kal_customers_details WHERE cus_id='" . $cus->cus_id . "' AND soort='1'");
        // Als er data is
        $aantal_tel = mysqli_num_rows($q_get_all_tel);
        $tel_teller = 1;
        if ($aantal_tel == 0) {
            // GEEN TELEFOON
            echo "<tr>";
            echo "<td class='klant_gegevens'>";
            echo "<table cellpadding='0' cellspacing='0' width='100%'>";
            echo "<tr>";
            echo "<td class='klant_gegevens'>Tel:</td>";
            echo "<td align='right'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</td>";
            echo "<td>";
            echo "<input type='text' style='border:2px solid green;' id='tel_1' name='tel[]' title='" . $cus->cus_id . "' class='lengte' value='' /><a href='' class='add_tel' title='Telefoon toevoegen' style='margin-left:10px'>+</a>";
            echo "</td>";
            echo "</tr>";
            // EINDE GEEN TELEFOON
        } else {
            // LOOP ALLE TELEFOONS
            while ($telefoons = mysqli_fetch_object($q_get_all_tel)) {
                if ($tel_teller == 1) {
                    // EERSTE KEER TELEFOON
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_tel' alt=''>";
                    echo "<img src='images/delete.png' title='" . $telefoons->id . "'/>";
                    echo "</a>";
                    echo "Tel 1.:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='tel_" . $tel_teller . "' title='" . $telefoons->id . ',' . $telefoons->cus_id . "' name='tel[details_" . $telefoons->id . "]' class='lengte' value='" . $telefoons->waarde . "' />";
                    // eerste keer toon plus icon
                    echo "<a href='' class='add_tel' title='Telefoon toevoegen' style='margin-left:10px'>+</a>";
                    echo "</td>";
                    // EINDE EERSTE KEER TELEFOON
                } else {
                    // VOLGENDE TELEFOONS
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<table cellpadding='0' cellspacing='0' width='100%'>";
                    echo "<tr>";
                    echo "<td class='klant_gegevens'>";
                    echo "<a href='' class='delete_tel' alt=''>";
                    echo "<img src='images/delete.png' title='" . $telefoons->id . "'/>";
                    echo "</a>";
                    echo "Tel " . $tel_teller . ".:";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "</td>";
                    echo "<td>";
                    echo "<input type='text' style='border:2px solid green;' id='tel_" . $tel_teller . "' title='" . $telefoons->id . ',' . $telefoons->cus_id . "' name='tel[details_" . $telefoons->id . "]' class='lengte' value='" . $telefoons->waarde . "' />";
                    echo "</td>";
                    echo "</tr>";
                    // EINDE VOLGENDE TELEFOONS
                }
                // TELLER +1
                $tel_teller++;
            }
            echo "</tr>";
        }
        // BANKNAAM
        echo "<tr>";
        echo "<td class='klant_gegevens'>";
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td class='klant_gegevens'>Name of the bank:</td>";
        echo "<td align='right'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</td>";
        echo "<td>";
        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='banknaam' id='banknaam' class='lengte' value='' />";
        } else {
            echo $cus->cus_banknaam;
        }
        echo "</td>";
        echo "</tr>";
        // IBAN
        echo "<tr>";
        echo "<td class='klant_gegevens'>";
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td class='klant_gegevens'>IBAN:<a style='color:seagreen;' class='bankCalculator' href='javascript:void(0);' onclick='bankCalculator()'>Calculator</a></td>";
        echo "<td align='right'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</td>";
        echo "<td>";
        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='iban' id='iban' class='lengte' value='' />";
        } else {
            echo $cus->cus_iban;
        }
        echo "</td>";
        echo "</tr>";
        // BIC
        echo "<tr>";
        echo "<td class='klant_gegevens'>";
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td class='klant_gegevens'>BIC:</td>";
        echo "<td align='right'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</td>";
        echo "<td>";
        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='bic' id='bic' class='lengte' value='' />";
        } else {
            echo $cus->cus_bic;
        }
        echo "</td>";
        echo "</tr>";
        // reknr
        echo "<tr>";
        echo "<td class='klant_gegevens'>";
        echo "<table cellpadding='0' cellspacing='0' width='100%'>";
        echo "<tr>";
        echo "<td class='klant_gegevens'>Account number:</td>";
        echo "<td align='right'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</td>";
        echo "<td>";
        if (in_array($_SESSION[ $session_var ]->user_id, $hoofdgroep) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='reknr' id='reknr' class='lengte' value='' />";
        } else {
            echo $cus->cus_reknr;
        }
        echo "</td>";
        echo "</tr>";
        // OVERZICHT BANKEN
        $q_get_all_bank = mysqli_query($conn, "SELECT * from kal_customers_reknr WHERE cus_id='" . $cus->cus_id . "'");
        if (mysqli_num_rows($q_get_all_bank) > 0) {
            echo "<tr>";
            echo "<td colspan='2'>";
            $q_get_all_bank = mysqli_query($conn, "SELECT * from kal_customers_reknr WHERE cus_id='" . $cus->cus_id . "'");
            while ($banken = mysqli_fetch_object($q_get_all_bank)) {
                echo "<a href='' class='deletebank' alt=''>";
                echo "<img src='images/delete.png' name='$banken->id' title='" . $banken->bank_iban . "'/>";
                echo $banken->bank_naam . " (IBAN:" . $banken->bank_iban . ")";
                echo "</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        // BTW TARIEF
        echo "<tr>";
        echo "<td class='klant_gegevens' valign='top'>VAT rate :</td>";
        echo "<td>";
        echo "<table>";
        echo "<tr><td>Private: </td><td><input type='text' size='4' name='btw_prive' id='btw_prive' value='" . $cus->cus_btw_prive . "' />%</td></tr>";
        echo "<tr><td>Professionals: </td><td> <input type='text' size='4' name='btw_beroeps' id='btw_beroeps' value='" . $cus->cus_btw_bedrijf . "' />%</td></tr>";
        echo "</table>";
        echo "</td>";
        echo "</tr>";


        $fac_adres_checked = "";
        $fac_stijl = "style='display:none;'";

        if ($cus->cus_fac_adres == '1') {
            $fac_adres_checked = "checked='checked'";
            $fac_stijl = "";
        }

        echo "<tr><td class='klant_gegevens'>Other billing address: ";

        echo "</td><td>";
        echo "<input type='checkbox' " . $fac_adres_checked . " name='fac_adres' id='fac_adres' onclick='showFacadres(this);' />";
        echo "</td></tr>";


        echo "<tr><td colspan='2'>";

        echo "<table id='id_facadres' border='0' " . $fac_stijl . ">";
        echo "<tr>";
        echo "<td class='klant_gegevens'>Naam and/or company:</td>";
        echo "<td width='235'>";
        echo "<input type='text' class='lengte' name='fac_naam' id='fac_naam' value='" . $cus->cus_fac_naam . "' />";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Street + nr:</td>";
        echo "<td>";
        //echo "<input type='text' class='lengte' name='fac_adres1' id='fac_adres1' value='". $cus->cus_fac_adres1 ."' />";
        echo "<input type='text' name='fac_straat' id='fac_straat' value='" . $cus->cus_fac_straat . "' /> ";
        echo "<input type='text' name='fac_nr' id='fac_nr' value='" . $cus->cus_fac_nr . "' size='4' />";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='klant_gegevens'>Zip code &amp; city: </td>";
        echo "<td>";
        //echo "<input type='text' class='lengte' name='fac_adres2' id='fac_adres2' value='". $cus->cus_fac_adres2 ."' />";
        echo "<input type='text' name='fac_postcode' id='fac_postcode' value='" . $cus->cus_fac_postcode . "' size='4' /> ";
        echo "<input type='text' name='fac_gemeente' id='fac_gemeente' value='" . $cus->cus_fac_gemeente . "' />";
        echo "</td>";
        echo "</tr>";
        // LAND
        echo "<td class='klant_gegevens'>Country :</td>";
        echo "<td>";
        echo "<select class='lengte' name='fac_land' id='fac_land'>";
        $q_land = mysqli_query($conn, "SELECT * FROM kal_landen ORDER BY land ASC");
        while ($land = mysqli_fetch_object($q_land)) {
            $sel = "";

            if ($land->id == $cus->cus_fac_land_id) {
                $sel = " selected='selected' ";
            }

            echo "<option " . $sel . " value='" . $land->id . "' >" . $land->land . "</option>";
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        echo "<tr><td colspan='2'>&nbsp;</td></tr>";
        echo "</table>";

        echo "</td></tr>";

        echo "</table>";
        echo "</fieldset>";

        echo "</td></tr>";
        echo "</table>";

        //if( $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 )

//        echo "<fieldset>";
//        echo "<legend>Klant is ge&iuml;nteresseerd in :</legend>";
//
//        echo "<table width='100%'>";
//
//        $int_boiler = "";
//        $int_mon = "";
//        $int_solar = "";
//        $int_iso = "";
//        $int_pid = "";
//
//        if ($cus->cus_int_solar == '1') {
//            $int_solar = " checked='checked' ";
//        }
//
//        if ($cus->cus_int_iso == '1') {
//            $int_iso = " checked='checked' ";
//        }
//
//        if ($cus->cus_int_boiler == '1') {
//            $int_boiler = " checked='checked' ";
//        }
//
//        if ($cus->cus_int_mon == '1') {
//            $int_mon = " checked='checked' ";
//        }
//
//        if ($cus->cus_int_pid == '1') {
//            $int_pid = " checked='checked' ";
//        }
//
//        echo "<tr><td><input " . $int_solar . " type='checkbox' name='int_solar' id='int_solar' /> <label for='int_solar' >Zonnepanelen</label> </td><td> <input " . $int_boiler . " type='checkbox' name='int_boiler' id='int_boiler' /> <label for='int_boiler' >Zonneboiler</label> </td></tr>";
//        echo "<tr>";
//        echo "<td><input " . $int_iso . " type='checkbox' name='int_iso' id='int_iso' /> <label for='int_iso' >Isolatie</label> </td>";
//        echo "<td> <input type='checkbox' " . $int_mon . "name='int_mon' id='int_mon' /> <label for='int_mon' >Monitoring</label> </td>";
//        echo "</tr>";
//
//        echo "<tr>";
//        echo "<td><input " . $int_pid . " type='checkbox' name='int_pid' id='int_pid' /> <label for='int_pid' >PID Box</label> </td>";
//        echo "<td> </td>";
//        echo "</tr>";
//
//        echo "</table>";
//
//        echo "</fieldset>";

        $aant_coda = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id = " . $cus->cus_id . " ORDER BY boek_dat ASC "));
        if (($_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 20 || $_SESSION[ $session_var ]->user_id == 34 ) && $aant_coda) {
            $data_grafiek = array();
            $factuur_arr = array();

            echo "<fieldset>";
            echo "<legend>Billing CODA</legend>";



            // ophalen van al de facturen uit solarlogs kalender
            $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $cus->cus_id . " AND cf_soort = 'factuur' AND cf_type = '0' ");

            $maak_unique = 0;
            $tot_bedrag = 0;
            if (mysqli_num_rows($q_zoek_fac) > 0) {
                while ($rij = mysqli_fetch_object($q_zoek_fac)) {
                    $maak_unique++;
                    $tot_bedrag += $rij->cf_bedrag;

                    $date_ymd = explode("-", $rij->cf_date);
                    $stamp = mktime(0, 0, 0, $date_ymd[1], $date_ymd[2], $date_ymd[0]);

                    $data_grafiek[$stamp . "_" . $maak_unique] = array("bedrag" => number_format($rij->cf_bedrag, 2, ".", ""),
                        "operant" => "+");
                    $factuur_arr[] = $rij->cf_id;
                }
            }

            $cn_arr = array();
            $q_zoek_fac = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $cus->cus_id . " AND cf_soort = 'creditnota' ");

            if (mysqli_num_rows($q_zoek_fac) > 0) {
                while ($rij = mysqli_fetch_object($q_zoek_fac)) {
                    $maak_unique++;

                    $tot_bedrag -= $rij->cf_bedrag;

                    $date_ymd = explode("-", $rij->cf_date);
                    $stamp = mktime(0, 0, 0, $date_ymd[1], $date_ymd[2], $date_ymd[0]);

                    $data_grafiek[$stamp . "_" . $maak_unique] = array("bedrag" => number_format($rij->cf_bedrag, 2, ".", ""),
                        "operant" => "-");

                    $cn_arr[] = array("bedrag" => $rij->cf_bedrag,
                        "datum" => $rij->cf_date);

                    //echo "<br>" . $rij->cf_bedrag . " - " . $rij->cf_date;
                }
            }

            /*
              echo "<pre>";
              var_dump($cn_arr);
              echo "</pre>";
             */

            $qq_coda = "SELECT * FROM kal_coda WHERE cus_id = " . $cus->cus_id . " ORDER BY boek_dat ASC ";
            $q_zoek_coda = mysqli_query($conn, $qq_coda) or die(mysqli_error($conn) . " " . __LINE__);

            //echo "SELECT * FROM kal_coda WHERE cus_id = " . $cus->cus_id . " ORDER BY boek_dat ASC ";

            if (mysqli_num_rows($q_zoek_coda) > 0) {
                while ($rij = mysqli_fetch_object($q_zoek_coda)) {
                    $maak_unique++;

                    $tot_bedrag -= $rij->bedrag;

                    $date_ymd = explode("-", $rij->boek_dat);
                    $stamp = mktime(0, 0, 0, $date_ymd[1], $date_ymd[2], $date_ymd[0]);


                    $data_grafiek[$stamp . "_" . $maak_unique] = array("bedrag" => $rij->bedrag,
                        "operant" => "-");
                }
            }

            /*
              if( $_SESSION[ $session_var ]->user_id == 19 )
              {
              echo "<pre>";
              //var_dump( $data_grafiek );
              echo "</pre>";

              foreach( $data_grafiek as $d )
              {
              echo "<br>" . $d["bedrag"] . " " . $d["operant"];
              }
              }
             */

            // BEGIN KOPPELING MET CODA



            /*
              echo "<pre>";
              var_dump($factuur_arr);
              echo "</pre>";
             */

            // al de facturen overlopen

            foreach ($factuur_arr as $fac_id) {
                $q_rij = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $fac_id) or die(mysqli_error($conn) . " " . __LINE__);
                $rij = mysqli_fetch_object($q_rij);

                $fac_date = explode("-", $rij->cf_date);
                $dir = $fac_date[0] . "/";

                $kleur = "color:red;";

                $tel_coda = 0;

                $tab_coda = "<table width='100%'>";

                $toon_info = 0;
                $q_zoek_coda = mysqli_query($conn, "SELECT * FROM kal_coda WHERE cf_id_fac = " . $rij->cf_id);
                while ($c = mysqli_fetch_object($q_zoek_coda)) {
                    $tel_coda += $c->bedrag;

                    $tab_coda .= "<tr>";
                    $tab_coda .= "<td>Coda : </td>";
                    $tab_coda .= "<td>" . changeDate2EU($c->boek_dat) . "</td>";
                    $tab_coda .= "<td align='right' >&euro;" . number_format($c->bedrag, 2, ",", " ") . "</td>";
                    $tab_coda .= "</tr>";

                    $toon_info = 1;
                }

                $rij->cf_bedrag = number_format($rij->cf_bedrag, 2, ".", "");

                $min_tel_coda = $tel_coda - 2;
                $max_tel_coda = $tel_coda + 2;

                if ($min_tel_coda < $rij->cf_bedrag && $max_tel_coda > $rij->cf_bedrag) {
                    $kleur = "color:green;";
                } else {
                    $verschil = $rij->cf_bedrag - $tel_coda;

                    $min_verschil = $verschil - 2;
                    $max_verschil = $verschil + 2;


                    //echo "<br>" . $min_verschil . " " . $max_verschil . " " .$bedrag;

                    $cn_tot = 0;
                    foreach ($cn_arr as $cn_id => $bedrag) {
                        //echo "<br>" . number_format($bedrag,2,".","") ."==". number_format($verschil,2,".","");
                        //if( number_format($bedrag,2,".","") == number_format($verschil,2,".","") )
                        if (number_format($min_verschil, 2, ".", "") < number_format($bedrag["bedrag"], 2, ".", "") && number_format($bedrag["bedrag"], 2, ".", "") < number_format($max_verschil, 2, ".", "")) {
                            //echo "<br>" . number_format($bedrag,2,".","") ."==". number_format($verschil,2,".","");
                            $tab_coda .= "<tr>";
                            $tab_coda .= "<td>CN : </td>";
                            $tab_coda .= "<td>" . changeDate2EU($bedrag["datum"]) . "</td>";
                            $tab_coda .= "<td align='right' >&euro;" . number_format($bedrag["bedrag"], 2, ",", " ") . "</td>";
                            $tab_coda .= "</tr>";

                            $toon_info = 1;

                            unset($cn_arr[$cn_id]);
                            $kleur = "color:green;";
                        } else {
                            $cn_tot += $bedrag["bedrag"];
                        }
                    }

                    if (number_format($cn_tot, 2, ".", "") == number_format($verschil, 2, ".", "")) {
                        foreach ($cn_arr as $cn_id => $bedrag) {
                            //echo "<br>" . number_format($bedrag,2,".","") ."==". number_format($verschil,2,".","");
                            $tab_coda .= "<tr>";
                            $tab_coda .= "<td>CN : </td>";
                            $tab_coda .= "<td>" . changeDate2EU($bedrag["datum"]) . "</td>";
                            $tab_coda .= "<td align='right' >&euro;" . number_format($bedrag["bedrag"], 2, ",", " ") . "</td>";
                            $tab_coda .= "</tr>";

                            $toon_info = 1;

                            unset($cn_arr[$cn_id]);
                            $kleur = "color:green;";
                        }
                    }
                }
                $tab_coda .= "</table>";

                echo "<br/>";
                if ($toon_info == 1) {
                    echo "<img style='float:left;' src='images/info.jpg' width='16px' height='16px' alt='Detailweergave' title='Detailweergave' onclick='showHide(" . $rij->cf_id . ");' >";
                } else {
                    echo "<img src='images/empty.png' style='float:left;width:16px;height:16px;' >";
                }
                
                $fac_date = explode("-", $rij->cf_date);
                    
                $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar") or die( mysqli_error($conn) . " regel:" . __LINE__ );
                while($boekjaar = mysqli_fetch_object($q_boekjaren)){
                    if($rij->cf_date > $boekjaar->boekjaar_start && $rij->cf_date<= $boekjaar->boekjaar_einde){
                        $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde ."/";
                    }
                }
                
                if (file_exists("cus_docs/" . $rij->cf_cus_id . "/factuur/" . $dir . $rij->cf_file)) {
                    echo "<span style='width:100px;float:left;padding:2px;height:10px;" . $kleur . "'><a href='cus_docs/" . $rij->cf_cus_id . "/factuur/" . $dir . $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a></span>";
                } else {
                    echo "<span style='width:100px;float:left;padding:2px;height:10px;" . $kleur . "'><a href='facturen/" . $dir . $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a></span>";
                }

                echo "<span style='width:100px;float:left;padding:2px;" . $kleur . "'>" . changeDate2EU($rij->cf_date) . "</span>";
                echo "<span style='width:200px;float:left;padding:2px;text-align:right;" . $kleur . "'>&euro;" . number_format($rij->cf_bedrag, 2, ",", " ") . "</span>";

                echo "<div id='id_" . $rij->cf_id . "' style='clear:both;display:none;border:1px solid black;padding:5px;' >";
                echo $tab_coda;
                echo "</div>";
            }

            echo "<div style='clear:both;'></div>";

            echo "<br/><b>Total outstanding amount : &euro; " . number_format($tot_bedrag, 2, ",", " ") . "</b><br/><br/>";


            /*
              if( $_SESSION[ $session_var ]->user_id == 19 )
              {
              echo "<pre>";
              var_dump( $data_grafiek );
              echo "</pre>";
              }
             */

            ksort($data_grafiek);

            $bbedrag = 0;
            $vorig_bedrag = 0;
            $vorige_dag = "";
            $i = 0;

            $graph_ext = array();

            $graph3 = "[";
            $grafiek3 = "[";

            foreach ($data_grafiek as $datum => $waardes) {
                if ($waardes["operant"] == "+") {
                    $bbedrag += $waardes["bedrag"];
                }

                if ($waardes["operant"] == "-") {
                    $bbedrag -= $waardes["bedrag"];
                }

                $datum_ex = explode("_", $datum);
                $datum = $datum_ex[0];

                //echo "<br>..." . $vorige_dag . " " . $datum;

                if ($i == 0) {
                    $graph3 .= "['" . date("Y-m-d", $datum - 86400) . "',0],";

                    $d = ($datum - 86400) * 1000;
                    $grafiek3 .= "[" . $d . ",0],";
                }

                if ($vorige_dag != $datum && $vorig_bedrag != $bbedrag && $i > 0) {
                    $graph3 .= "['" . date("Y-m-d", $datum - 86400) . "'," . $vorig_bedrag . "],";
                    //echo "<br>." . date("d-m-Y", $datum)  . " " . $vorig_bedrag;
                    //$graph_ext[] = array( "datum" => date("Y-m-d", $datum), "bedrag" => $vorig_bedrag );


                    $d = ($datum - 86400) * 1000;
                    $grafiek3 .= "[" . $d . "," . number_format($vorig_bedrag, 2, ".", "") . "],";
                }

                $graph3 .= "['" . date("Y-m-d", $datum) . "'," . $bbedrag . "],";

                $d = ($datum) * 1000;
                $grafiek3 .= "[" . $d . "," . number_format($bbedrag, 2, ".", "") . "],";

                $graph_ext[] = array("datum" => date("Y-m-d", $datum), "bedrag" => $bbedrag);
                //echo "<br>.." . date("d-m-Y", $datum)  . " " . $bbedrag;

                $vorig_bedrag = $bbedrag;
                $vorige_dag = $datum;

                $i++;
            }

            $graph3 = substr($graph3, 0, -1);
            $grafiek3 = substr($grafiek3, 0, -1);
            $grafiek3 .= "]";

            $graph3 .= "]";

            echo "<div id='grafiek_coda2'></div>";

            echo "</fieldset>";
        }

        if ($_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4) {
            echo "<fieldset>";
            echo "<legend>Billing docs</legend>";

            // als er GSC gefactureerd zijn, dan een knop weergeven.
            if (!empty($cus->cus_pvz)) {
                $q_zoek_vreg = mysqli_query($conn, "SELECT * FROM kal_fac_gsc_vreg WHERE pvz LIKE '" . $cus->cus_pvz . "%' AND fac != '' ");

                if (mysqli_num_rows($q_zoek_vreg) > 0) {
                    echo "<a id='various7' class='verkoop_gegevens' href='klanten/fac_gsc.php?klantid=" . $cus->cus_id . "'>Show billed GSC</a><br/>";
                }
            }
            ?>

            <a href="javascript:addFac(<?php echo $cus->cus_id; ?>)" >Add Bill/Creditnote</a><br /><br />

            <?php
            echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
            echo "<tr>";
            echo "<td width='190' ><b>Bill: </b></td>";
            echo "<td>";
            //echo "<input class='lengte' type='file' name='doc_factuur' id='doc_factuur' />";


            echo "</td>";
            echo "</tr>";

            $cus_id_p = $cus->cus_id;
            $cus_id_ori = $cus->cus_id;

            // zoeken of er facturen zijn
            $q_zoek_factuur = mysqli_query($conn, "SELECT * 
			                                 FROM kal_customers_files
			                                WHERE cf_cus_id = '" . $cus_id_ori . "'
			                                  AND cf_soort = 'factuur' ");
                                              
            //echo "<br />" . mysqli_num_rows($q_zoek_factuur);
                                              
            if (mysqli_num_rows($q_zoek_factuur) > 0) {
                while ($factuur = mysqli_fetch_object($q_zoek_factuur)) {
                    $fac_date = explode("-", $factuur->cf_date);
                    
                    $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                    while($boekjaar = mysqli_fetch_object($q_boekjaren)){
                        if($factuur->cf_date > $boekjaar->boekjaar_start && $factuur->cf_date<= $boekjaar->boekjaar_einde){
                            $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde ."/";
                        }
                    }
                    
                    $var_fac = "/factuur/" . $dir;
                    
                    //echo "<br>" . "cus_docs/" . $cus_id_ori . $var_fac . $factuur->cf_file;

                    if (file_exists("cus_docs/" . $cus_id_ori . $var_fac . $factuur->cf_file)) {
                        echo "<tr><td align='right' valign='top'>";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "<b>Delete?</b>&nbsp;&nbsp;";
                        }

                        echo "</td><td>";
                        echo "<input type='checkbox' name='factuur_del_" . $factuur->cf_id . "' id='factuur_del_" . $factuur->cf_id . "' />";
                        echo "<a href='cus_docs/" . $cus_id_ori . "/factuur/" . $dir . $factuur->cf_file . "' target='_blank' >";
                        echo $factuur->cf_file;
                        echo "</a>";
                        echo " (" . changeDate2EU($factuur->cf_date) . ")";
                        
                        
                        
                        $mail_verstuurd = "";
                        $mail_style = "";
                        
                        $q_zoek_fac = mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_cus_id = " . $cus->cus_id . " AND cf_soort = 'factuur_send' AND cf_file = '".$factuur->cf_file."' ");
                        
                        if( mysqli_num_rows($q_zoek_fac) > 0 )
                        {
                            $mail_style = " style='font-weight:800;color:green;' ";
                            
                            while($zoek_fac = mysqli_fetch_object($q_zoek_fac))
                            {
                                $mail_verstuurd .= " - " . $zoek_fac->cf_datetime . "; ";
                            }
                        }
                        echo "&nbsp;<span rec_id='".$factuur->cf_id."' class='send_factuur' title='". $mail_verstuurd ."' ". $mail_style ." >";
                        echo "[mail]";
                        echo "</span>";

                        
                        echo "</td>";
                        echo "</tr>";
                    }
                }

                echo "<tr>";
                echo "<td>";
                echo "<b>Paymentsterm : </b>";
                echo "</td>";
                echo "<td>";

                if ($cus->cus_bet_termijn == 0) {
                    $cus->cus_bet_termijn = $betalings_termijn;
                }

                echo "<input type='text' style='text-align:right;' size='4' name='bet_termijn' id='bet_termijn' value='" . $cus->cus_bet_termijn . "' /> dagen";
                echo "</td>";
                echo "</tr>";
            }


            // zoeken of er creditnota zijn
            $q_zoek_factuur = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_soort_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'creditnota' ");

            if (mysqli_num_rows($q_zoek_factuur) > 0) {
                echo "<tr><td align='left' valign='top' colspan='2'><b>Credit note:</b></td></tr>";

                while ($factuur = mysqli_fetch_object($q_zoek_factuur)) {
                    $fac_date = explode("-", $factuur->cf_date);
                    $dir = $fac_date[0] . "/";

                    $var_fac = "/creditnota/" . $dir;
                    $var_fac1 = "/creditnota/";

                    if (file_exists("cus_docs/" . $cus->cus_id . $var_fac . $factuur->cf_file) || file_exists("cus_docs/" . $cus->cus_id . $var_fac1 . $factuur->cf_file)) {
                        echo "<tr><td align='right'> ";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "<b>Delete?</b>&nbsp;&nbsp;";
                        }

                        echo "</td><td align='left'>";
                        echo "<input type='checkbox' name='cn_del_" . $factuur->cf_id . "' id='cn_del_" . $factuur->cf_id . "' />";
                        if (file_exists("cus_docs/" . $cus->cus_id . $var_fac . $factuur->cf_file)) {
                            echo "<a href='cus_docs/" . $cus->cus_id . "/creditnota/" . $dir . $factuur->cf_file . "' target='_blank' >";
                        } else {
                            echo "<a href='cus_docs/" . $cus->cus_id . "/creditnota/" . $factuur->cf_file . "' target='_blank' >";
                        }
                        echo $factuur->cf_file;
                        echo "</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
            }

            // zoeken of er distri offertes zijn
            $q_zoek_distri = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_soort_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'distri_offerte' ");

            if (mysqli_num_rows($q_zoek_distri) > 0) {
                echo "<tr><td align='left' valign='top' colspan='2'><b>Quotations distri:</b></td></tr>";

                while ($distri_offerte = mysqli_fetch_object($q_zoek_distri)) {
                    if (file_exists("cus_docs/" . $cus->cus_id . "/doc_distri/" . $distri_offerte->cf_file)) {
                        echo "<tr><td align='right'> ";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "<b>Delete?</b>&nbsp;&nbsp;";
                        }

                        echo "</td><td align='left'>";
                        echo "<input type='checkbox' name='distri_off_del_" . $distri_offerte->cf_id . "' id='distri_off_" . $distri_offerte->cf_id . "' />";
                        echo "<a href='cus_docs/" . $cus->cus_id . "/doc_distri/" . $distri_offerte->cf_file . "' target='_blank' >";
                        echo $distri_offerte->cf_file;
                        echo "</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
            }

            // zoeken of er distri leverbonnen zijn
            $q_zoek_distri = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_soort_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'distri_bestelbon' ");

            if (mysqli_num_rows($q_zoek_distri) > 0) {
                echo "<tr><td align='left' valign='top' colspan='2'><b>Delivery vouchers distri:</b></td></tr>";

                while ($distri_offerte = mysqli_fetch_object($q_zoek_distri)) {
                    if (file_exists("cus_docs/" . $cus->cus_id . "/bon_distri/" . $distri_offerte->cf_file)) {
                        echo "<tr><td align='right'> ";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "<b>Delete?</b>&nbsp;&nbsp;";
                        }

                        echo "</td><td align='left'>";
                        echo "<input type='checkbox' name='distri_bon_del_" . $distri_offerte->cf_id . "' id='distri_bon_del_" . $distri_offerte->cf_id . "' />";
                        echo "<a href='cus_docs/" . $cus->cus_id . "/bon_distri/" . $distri_offerte->cf_file . "' target='_blank' >";
                        echo $distri_offerte->cf_file;
                        echo "</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
            }

            //$q_zoek_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $cus->cus_id  ." AND aa_factuur = '" . $factuur->cf_file . "' ORDER BY 1");
            $q_zoek_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $cus->cus_id . " GROUP BY aa_filename ORDER BY 1");

            if (mysqli_num_rows($q_zoek_aanm) > 0) {
                echo "<tr><td align='left' valign='top' colspan='2'>&nbsp;</td></tr>";
                echo "<tr><td align='left' valign='top' colspan='2'><b>Reminders:</b></td></tr>";

                $teller = 0;
                while ($aanmaning = mysqli_fetch_object($q_zoek_aanm)) {
                    $teller++;
                    echo "<tr>";
                    echo "<td colspan='2' align='center'>";

                    echo "<a href='aanmaningen/" . $aanmaning->aa_filename . "' target='_blank'>";
                    echo "Reminder " . $teller . " (" . $aanmaning->aa_datum . ")";
                    echo "</a>";

                    echo "</td></tr>";

                    //daarna hieronder ook het openstaande saldo vermelden
                    // bedrag van factuur ophalen

                    /*
                      $saldo_openstaand = $factuur->cf_bedrag;

                      $q_pay = mysqli_query($conn, "SELECT *
                      FROM kal_customers_payments
                      WHERE cp_cus_id = '". $cus_id_p ."'
                      AND cp_factuur = '". $factuur->cf_file ."'") or die( mysqli_error($conn) );

                      while( $pay = mysqli_fetch_object($q_pay) )
                      {
                      $saldo_openstaand -= $pay->cp_bedrag;
                      }

                      echo "<br/>&nbsp;&nbsp;&nbsp;+ ";
                      echo "Openstaand saldo : ";
                      echo "&euro; " . number_format( $saldo_openstaand, 2, ",", " ");
                     */
                }
            }



            echo "</table>";
            echo "</fieldset>";
        }

        echo "<table>";

        $showhide1 = "";
        if ($cus->cus_oa == 1) {
            $showhide1 = " style='display:none;' ";
        }

        echo "<tr><td colspan='2'>";

        echo "<fieldset id='showhide1' " . $showhide1 . ">";
        echo "<legend>ACMA</legend>";
        echo "<table width='100%' cellpadding='0' cellspacing='0'>";

        if (!empty($cus->cus_klant_wilt)) {
            echo "<tr>";
            echo "<td>";
            echo "Customer want :";
            echo "</td>";
            echo "<td>" . $cus->cus_klant_wilt . "</td>";
            echo "</tr>";
        }

        echo "<tr>";
        echo "<td>Knows us from:</td>";
        echo "<td>";

        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<select name='kent' id='kent' class='lengte' >";
            echo "<option value=''>== choice ==</option>";

            foreach ($kent_ons_van as $key => $kent_ons) {
                if ($cus->cus_kent_ons_van == $key) {
                    echo "<option selected='selected' value='" . $key . "'>" . $kent_ons . "</option>";
                } else {
                    echo "<option value='" . $key . "'>" . $kent_ons . "</option>";
                }
            }
            echo "</select>";
        } else {
            if (isset($kent_ons_van[$cus->cus_kent_ons_van])) {
                echo $kent_ons_van[$cus->cus_kent_ons_van];
            }
        }

        echo "</td>";
        echo "</tr>";

        if (($_SESSION[ $session_var ]->group_id != 3) || $_SESSION[ $session_var ]->user_id == 29 || $_SESSION[ $session_var ]->active == '2') {
            echo "<tr>";
            echo "<td width='190' >ACMA:</td>";
            echo "<td>";

            if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                echo "<select style='border:2px solid green;' name='acma' id='acma' class='lengte'>";
                echo "<option value=''></option>";

                foreach ($acma_tel as $key => $acma) {
                    if ($key == $cus->cus_acma) {
                        echo "<option selected='yes' value='" . $key . "'>" . $acma["naam"] . " (" . $acma["tel"] . ")</option>";
                    } else {
                        echo "<option value='" . $key . "'>" . $acma["naam"] . " (" . $acma["tel"] . ")</option>";
                    }
                }

                echo "</select>";
            } else {
                echo $acma_tel[$cus->cus_acma]["naam"] . " " . $acma_tel[$cus->cus_acma]["tel"];
            }

            echo "</td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td>ACMA:</td>";
            echo "<td>" . $acma_tel[$cus->cus_acma]["naam"] . " " . $acma_tel[$cus->cus_acma]["tel"];
            echo "<input type='hidden' name='acma' id='acma' value='" . $cus->cus_acma . "' /> ";
            echo "</td>";
            echo "</tr>";
        }

        if ($cus->cus_offerte_datum == "0000-00-00") {
            $cus->cus_offerte_datum = "";
        } else {
            $datum = explode("-", $cus->cus_offerte_datum);
            $cus->cus_offerte_datum = $datum[2] . "-" . $datum[1] . "-" . $datum[0];
        }

        echo "<tr>";
        echo "<td>Date quotation:</td>";
        echo "<td>";

        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<input type='text' name='offerte_datum' id='offerte_datum' class='lengte' value='" . $cus->cus_offerte_datum . "' />";
        } else {
            echo $cus->cus_offerte_datum;
        }

        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</fieldset>";
        
        
        
        
        echo "<fieldset>";
        echo "<legend>Pressence</legend>";
        echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
        
        echo "<tr><td>";
        
        echo "Choose a year : <select name='sel_jaar' id='sel_jaar' >";
        
        for($jaar=2012;$jaar<date('Y')+1;$jaar++)
        {
            echo "<option value='".$jaar."'>".$jaar."</option>";
        }
        
        
        
        echo "</select>";
        
        echo "<input type='button' class='add_year' rec_id='". $cus->cus_id ."' value='Add year' />";
        
        echo "<br />";
        
        echo "<div id='show_years'>";
        
        // toon jaren
        $q_zoek_jaren = mysqli_query($conn, "SELECT * FROM esc_db.kal_team_pressence WHERE cus_id = " . $cus->cus_id . " ORDER BY year");
        
        if( mysqli_num_rows($q_zoek_jaren) > 0 )
        {
            echo "<br />This team was present in :";
            
            while( $j = mysqli_fetch_object($q_zoek_jaren) )
            {
                echo "<br /><span id='tr_".$j->id."' ><img src='images/delete.png' class='delete_year' rec_id='". $j->id ."' />" . $j->year . "</span>";
            }
        }
        
        echo "</div>";
        
        echo "</td></tr>";
        
        echo "</table>";
        echo "</fieldset>";



        echo "</td></tr>";

        echo "<tr><td colspan='2'>";

        $showhide2 = " style='display:none;' ";

        if ((!empty($cus->cus_acma) && !empty($cus->cus_offerte_datum) ) || $cus->cus_oa == '1') {
            $showhide2 = "";
        }

        if ($cus->cus_int_solar == '1') {
            $showhide2 = "";
        } else {
            $showhide2 = " style='display:none;' ";
        }

        echo "</td></tr>";
        echo "</table>";

        echo "</td>";
        echo "<td valign='top' width='50%'>";
        // begin tabel 2

        echo "<table width='100%' >";

        echo "<tr>";
        echo "<td colspan='2' >";

        echo "<table width='100%'>";
        echo "<tr>";
        echo "<td>";
        echo "<a id='various5' class='verkoop_gegevens' href='klanten_tel.php?klantid=" . $cus->cus_id . "'>";

        // tellen en weergeven van het aantal interventies
        $aant_tel = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_tel WHERE ct_soort = '' AND ct_cus_id = " . $cus->cus_id));
        echo "Telephone remarks";

        if ($aant_tel > 0) {
            echo " (" . $aant_tel . ")";
        }
        echo "</a>";

        echo "</td>";
        echo "<td>";

        // tellen en weergeven van het aantal interventies
        $aant_interventies = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_interventies WHERE ct_cus_id = " . $cus->cus_id));
        echo "<a id='various6' class='verkoop_gegevens' href='klanten_interventies.php?klantid=" . $cus->cus_id . "'>";

//                                $tot_inter += $aant_proj_interve;
//                                $tot_inter += $aant_interventies;

        echo "Interventions";

//				if( $tot_inter > 0 )
//				{
//					echo " (". $tot_inter .")";
//				}
//				
        echo "</a>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";

        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='verkoop_gegevens'>";
        echo "Remarks:<br/>";

        if (( $_SESSION[ $session_var ]->user_id == $cus->cus_acma && isset($cus->cus_acma) ) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<textarea rows='5' style='width:452px;' name='opmerkingen' id='opmerkingen' >" . $cus->cus_opmerkingen . "</textarea>";
        } else {
            echo $cus->cus_opmerkingen;
        }

        echo "</td>";
        echo "</tr>";
        echo "</table>";

        $stijl = " style='display:none;' ";

        if (!empty($cus->cus_btw)) {
            $stijl = "";
        }

        // nieuwe blok voor het ingeven van de kortingen
//        if ($_SESSION[ $session_var ]->group_id == 1) {
//            echo "<fieldset id='tabel2a' " . $stijl . " >";
//            echo "<legend>Kortingen per materiaalsoort</legend>";
//            echo "<table width='100%' border='0' >";
//
//            // materiaal soorten ophalen
//            $q_ms = mysqli_query($conn, "SELECT * FROM kal_art_soort ORDER BY as_soort");
//            while ($ms = mysqli_fetch_object($q_ms)) {
//                echo "<tr>";
//                echo "<td width='46%'>";
//                echo $ms->as_soort;
//                echo ": </td>";
//
//                echo "<td>";
//                // ophalen van de reeds bestaande waardes
//                $sql_korting = "SELECT * FROM kal_as_cus_korting WHERE as_id = " . $ms->as_id . " AND cus_id = " . $cus->cus_id;
//                $q_korting = mysqli_query($conn, $sql_korting);
//                $korting = mysqli_fetch_object($q_korting);
//
//                echo "<input type='text' name='ascus_" . $ms->as_id . "' value='" . $korting->korting . "' />";
//
//                if ($ms->as_soort != "Zonnepanelen") {
//                    echo "%";
//                }
//
//                echo "</td>";
//                echo "</tr>";
//            }
//
//            echo "</table>";
//            echo "</fieldset>";
//        }
        // einde blok ingeven van de kortingen

        if ($isProject) {
            ?>


            <script type="text/javascript" >

                function checkProject(waarde)
                {
                    if (waarde.value == "Verkoop")
                    {
                        document.getElementById("tbl_rvo").style.display = "none";
                        document.getElementById("tbl_verkoop").style.display = "block";
                    }

                    if (waarde.value == "RVO")
                    {
                        document.getElementById("tbl_rvo").style.display = "block";
                        document.getElementById("tbl_verkoop").style.display = "none";
                    }
                }

            </script>

            <?php
            echo "<fieldset>";
            echo "<legend>Project Info</legend>";

            $project = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_project WHERE cus_id = " . $cus->cus_id));

            /*
              echo "<pre>";
              var_dump( $project );
              echo "</pre>";
             */

            echo "<table cellpadding='0' cellspacing='0' width='100%'>";
            echo "<tr>";
            echo "<td style='width:210px;'> Agreement : </td>";
            echo "<td>";

            echo "<select name='overeenkomst' id='overeenkomst' onchange='checkProject(this);' class='lengte'>";
            echo "<option value=''> == Make your choice == </option>";

            if ($project->overeenkomst == "Verkoop") {
                echo "<option selected='selected' value='Verkoop'> Verkoop </option>";
            } else {
                echo "<option value='Verkoop'> Verkoop </option>";
            }

            if ($project->overeenkomst == "RVO") {
                echo "<option selected='selected' value='RVO'> RVO </option>";
            } else {
                echo "<option value='RVO'> RVO </option>";
            }

            if ($project->overeenkomst == "iLumen") {
                echo "<option selected='selected' value='iLumen'> iLumen </option>";
            } else {
                echo "<option value='iLumen'> iLumen </option>";
            }
            echo "</select>";

            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Projectleader : </td>";
            echo "<td>";


            $leider_arr = array('Joren', 'Bart', 'Kristof', 'Ismael', 'Pieter', 'Dimitri', 'Koen Langie');

            echo "<select name='projectleider' id='projectleider' class='lengte'>";

            echo "<option value=''> == Make your choice == </option>";

            foreach ($leider_arr as $leider) {
                if ($project->leider == $leider) {
                    echo "<option selected='selected' value='" . $leider . "'>" . $leider . "</option>";
                } else {
                    echo "<option value='" . $leider . "'>" . $leider . "</option>";
                }
            }

            echo "</select>";

            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Subcontractor Mech. : </td>";
            echo "<td>";

            echo "<select name='onderaannemer' id='onderaannemer' class='lengte'>";
            echo "<option value='' > == Make your choice == </option>";

            if ($project->oa == "Weygers") {
                echo "<option selected='selected' value='Weygers' > Weygers</option>";
            } else {
                echo "<option value='Weygers' > Weygers</option>";
            }

            if ($project->oa == "Kestens") {
                echo "<option selected='selected' value='Kestens' > Kestens</option>";
            } else {
                echo "<option value='Kestens' > Kestens</option>";
            }

            if ($project->oa == "Melbotech") {
                echo "<option selected='selected' value='Melbotech' > Melbotech</option>";
            } else {
                echo "<option value='Melbotech' > Melbotech</option>";
            }

            if ($project->oa == "Sleurs") {
                echo "<option selected='selected' value='Sleurs' > Sleurs</option>";
            } else {
                echo "<option value='Sleurs' > Sleurs</option>";
            }

            if ($project->oa == "DMW") {
                echo "<option selected='selected' value='DMW' > DMW</option>";
            } else {
                echo "<option value='DMW' > DMW</option>";
            }
            echo "</select>";

            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Subcontractor. Elec. : </td>";
            echo "<td>";

            echo "<select name='oa_elec' id='oa_elec' class='lengte'>";
            echo "<option value='' > == Make your choice == </option>";

            if ($project->oa_elec == "Vansant") {
                echo "<option selected='selected' value='Vansant' > Vansant</option>";
            } else {
                echo "<option value='Vansant' > Vansant</option>";
            }

            if ($project->oa_elec == "Vanparijs") {
                echo "<option selected='selected' value='Vanparijs' > Vanparijs</option>";
            } else {
                echo "<option value='Vanparijs' > Vanparijs</option>";
            }

            if ($project->oa_elec == "Janssens") {
                echo "<option selected='selected' value='Janssens' > Janssens</option>";
            } else {
                echo "<option value='Janssens' > Janssens</option>";
            }
            echo "</select>";

            echo "</td>";
            echo "</tr>";

            /*
              echo "<tr>";
              echo "<td> Onderaan. Mech. : </td>";
              echo "<td>";

              echo "<select name='oa_mech' id='oa_mech' class='lengte'>";
              echo "<option value='' > == Maak uw keuze == </option>";



              echo "</select>";

              echo "</td>";
              echo "</tr>";
             */

            echo "<tr>";
            echo "<td> Monitoring : </td>";
            echo "<td>";

            echo "<select name='monitoring' id='monitoring' class='lengte'>";
            echo "<option value='' > == Make your choice == </option>";

            if ($project->monitoring == "OK") {
                echo "<option selected='selected' value='OK' > OK </option>";
            } else {
                echo "<option value='OK' > OK </option>";
            }

            if ($project->monitoring == "NOK") {
                echo "<option selected='selected' value='NOK' > NOK </option>";
            } else {
                echo "<option value='NOK' > NOK </option>";
            }

            echo "</select>";

            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Dossier nr. : </td>";
            echo "<td><input type='text' name='dossiernr' id='dossiernr' value='" . $project->dossiernr . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> EAN decrease : </td>";
            echo "<td><input type='text' name='ean_a' id='ean_a' value='" . $project->ean_a . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> EAN injection : </td>";
            echo "<td><input type='text' name='ean_i' id='ean_i' value='" . $project->ean_i . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> EAN consumption : </td>";
            echo "<td><input type='text' name='ean_e' id='ean_e' value='" . $project->ean_e . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> EAN production : </td>";
            echo "<td><input type='text' name='ean_p' id='ean_p' value='" . $project->ean_p . "' class='lengte' /></td>";
            echo "</tr>";


            echo "<tr>";
            echo "<td> Netstudy : </td>";
            echo "<td><input type='file' name='netstudie' id='netstudie' /></td>";
            echo "</tr>";

            echo "<tr><td colspan='2'>";

            // zoeken of er offertes zijn
            $q_zoek_netstudie = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'netstudie' ");

            if (mysqli_num_rows($q_zoek_netstudie) > 0) {
                echo "<table width='100%'>";

                while ($netstudie1 = mysqli_fetch_object($q_zoek_netstudie)) {
                    if (file_exists("cus_docs/" . $cus->cus_id . "/netstudie/" . $netstudie1->cf_file)) {
                        echo "<tr><td align='right' valign='top' >";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "Delete?&nbsp;&nbsp;<input type='checkbox' name='netstudie_del_" . $netstudie1->cf_id . "' id='netstudie_del_" . $netstudie1->cf_id . "' />";
                        }

                        echo "</td><td>";

                        echo "<a href='cus_docs/" . $cus->cus_id . "/netstudie/" . $netstudie1->cf_file . "' target='_blank' >";
                        echo $netstudie1->cf_file;
                        echo "</a>";

                        echo "</td>";
                        echo "</tr>";
                    }
                }

                echo "</table>";
            }

            echo "</td></tr>";

            echo "<tr>";
            echo "<td> Contract : </td>";
            echo "<td><input type='file' name='contract' id='contract' /></td>";
            echo "</tr>";

            echo "<tr><td colspan='2'>";

            // zoeken of er offertes zijn
            $q_zoek_netstudie = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'contract' ");

            if (mysqli_num_rows($q_zoek_netstudie) > 0) {
                echo "<table width='100%'>";

                while ($netstudie1 = mysqli_fetch_object($q_zoek_netstudie)) {
                    if (file_exists("cus_docs/" . $cus->cus_id . "/contract/" . $netstudie1->cf_file)) {
                        echo "<tr><td align='right' valign='top' >";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "Delete?&nbsp;&nbsp;<input type='checkbox' name='contract_del_" . $netstudie1->cf_id . "' id='contract_del_" . $netstudie1->cf_id . "' />";
                        }

                        echo "</td><td>";

                        echo "<a href='cus_docs/" . $cus->cus_id . "/contract/" . $netstudie1->cf_file . "' target='_blank' >";
                        echo $netstudie1->cf_file;
                        echo "</a>";

                        echo "</td>";
                        echo "</tr>";
                    }
                }

                echo "</table>";
            }

            echo "</td></tr>";

            echo "<tr>";
            echo "<td> Injection : </td>";
            echo "<td><input type='file' name='injectie' id='injectie' /></td>";
            echo "</tr>";

            echo "<tr><td colspan='2'>";

            // zoeken of er offertes zijn
            $q_zoek_netstudie = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '" . $cus->cus_id . "'
					                                  AND cf_soort = 'injectie' ");

            if (mysqli_num_rows($q_zoek_netstudie) > 0) {
                echo "<table width='100%'>";

                while ($netstudie1 = mysqli_fetch_object($q_zoek_netstudie)) {
                    if (file_exists("cus_docs/" . $cus->cus_id . "/injectie/" . $netstudie1->cf_file)) {
                        echo "<tr><td align='right' valign='top' >";

                        if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                            echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='injectie_del_" . $netstudie1->cf_id . "' id='injectie_del_" . $netstudie1->cf_id . "' />";
                        }

                        echo "</td><td>";

                        echo "<a href='cus_docs/" . $cus->cus_id . "/injectie/" . $netstudie1->cf_file . "' target='_blank' >";
                        echo $netstudie1->cf_file;
                        echo "</a>";

                        echo "</td>";
                        echo "</tr>";
                    }
                }

                echo "</table>";
            }

            echo "</td></tr>";

            echo "<tr>";
            echo "<td colspan='2'><b> Extra cost : </b></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Name : </td>";
            echo "<td><input type='text' name='kost_naam' id='kost_naam' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Amount : </td>";
            echo "<td><input type='text' name='kost_bedrag' id='kost_bedrag' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' /></td>";
            echo "</tr>";

            echo "</table>";

            $q_extra_kost = mysqli_query($conn, "SELECT * FROM kal_customers_project_extra WHERE cus_id = " . $cus->cus_id);
            $extra_kost = mysqli_num_rows($q_extra_kost);

            if ($extra_kost == 0) {
                echo "<i>There are no additional costs found.</i>";
            } else {
                // uitlezen van de extra kost

                echo "<table style='border:1px solid black;' width='100%' >";
                echo "<tr><td width='20' >&nbsp;</td><td><b>Name</b></td><td><b>Amount</b></td></tr>";

                while ($kost = mysqli_fetch_object($q_extra_kost)) {
                    echo "<tr>";
                    echo "<td><a onclick=\"javascript:return confirm('Delete Extra cost?')\" href='klanten.php?ek=" . $kost->id . "&tab_id=1&klant_id=" . $cus->cus_id . "'><img src='images/delete.png' /></a></td>";
                    echo "<td>" . $kost->kost . "</td>";
                    echo "<td>" . $kost->bedrag . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            }


            $stijl = " style='display:none;' ";
            if ($project->overeenkomst == "Verkoop") {
                $stijl = "";
            }

            echo "<table id='tbl_verkoop' " . $stijl . ">";
            echo "<tr>";
            echo "<td>Sales amount :</td>";
            echo "<td> <input type='text' name='verk_bedrag' id='verk_bedrag' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' value='" . $project->bedrag . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td colspan='2'><b>Maintenance :</b></td>";
            echo "</tr>";

            if ($project->onderhoud_datum == "0000-00-00") {
                $project->onderhoud_datum = "";
            } else {
                $datum = explode("-", $project->onderhoud_datum);
                $project->onderhoud_datum = $datum[2] . "-" . $datum[1] . "-" . $datum[0];
            }

            echo "<tr>";
            echo "<td>Maintenance active?</td>";
            echo "<td>";


            $ond_arr = array();
            $ond_arr[0] = "Neen";
            $ond_arr[1] = "Brons";
            $ond_arr[2] = "Zilver";
            $ond_arr[3] = "Goud";
            $ond_arr[4] = "Platinum";

            echo "<select name='ond_actief' id='ond_actief' >";

            foreach ($ond_arr as $index => $ond) {
                $sel = "";

                if ($project->ond_actief == $index) {
                    $sel = " selected='selected' ";
                }

                echo "<option " . $sel . " value='" . $index . "'>" . $ond . "</option>";
            }

            echo "</select>";


            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Contract date :</td>";
            echo "<td> <input type='text' name='onderhoud_datum' id='onderhoud_datum' value='" . $project->onderhoud_datum . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Maintenance cost/year :</td>";
            echo "<td> <input type='text' name='onderhoudsbedrag' id='onderhoudsbedrag' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' value='" . $project->ouderhoud . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Free maintenance :</td>";
            echo "<td>";

            echo "<select name='onderhoud_gratis' id='onderhoud_gratis'>";
            echo "<option value='0'>No</option>";

            for ($i = 1; $i < 6; $i++) {
                $sel = "";

                if ($project->onderhoud_gratis == $i) {
                    $sel = " selected='selected' ";
                }

                echo "<option " . $sel . " value='" . $i . "'>" . $i . " jaar</option>";
            }

            echo "</td></tr>";



            echo "</table>";

            $stijl = " style='display:none;' ";
            if ($project->overeenkomst == "RVO") {
                $stijl = "";
            }

            echo "<table id='tbl_rvo' " . $stijl . " border='0'>";
            echo "<tr>";
            echo "<td width='160'>Tenancy fee :</td>";
            echo "<td> <input type='text' name='opstal' id='opstal' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' value='" . $project->opstal . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Compensation/year :</td>";
            echo "<td> <input type='text' name='vergoeding_jaar' id='vergoeding_jaar' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' value='" . $project->vergoeding . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Investment</td>";
            echo "<td> <input type='text' name='investering' id='investering' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' value='" . $project->invest . "' class='lengte' /></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> Insurance : </td>";
            echo "<td>";

            echo "<select name='verzekering' id='verzekering' class='lengte'>";
            echo "<option value='' > == Make your choice == </option>";

            if ($project->verzekering == "RSA") {
                echo "<option selected='selected' value='RSA' > RSA </option>";
            } else {
                echo "<option value='RSA' > RSA </option>";
            }

            if ($project->verzekering == "CNA") {
                echo "<option selected='selected' value='CNA' > CNA </option>";
            } else {
                echo "<option value='CNA' > CNA </option>";
            }

            echo "</select>";

            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td> RVO past : </td>";
            echo "<td>";

            echo "<select name='rvo_verleden' id='rvo_verleden' class='lengte'>";
            echo "<option value='' > == Make your choice == </option>";

            if ($project->rvo_verleden == "Ja") {
                echo "<option selected='selected' value='Ja' > Yes </option>";
            } else {
                echo "<option value='Ja' > Yes </option>";
            }

            if ($project->rvo_verleden == "Neen") {
                echo "<option selected='selected' value='Neen' > No </option>";
            } else {
                echo "<option value='Neen' > No </option>";
            }
            echo "</select>";

            echo "</td>";
            echo "</tr>";

            //$q_p1 = mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $cus->cus_id);
            //if( mysqli_num_rows($q_p1) > 0 && 1 == 0 )
            if (1 == 0) {

                $project1 = mysqli_fetch_object($q_p1);

                $index_prijs = array();
                $verm = 0;
                for ($startjaar = 2010; $startjaar <= date('Y') + 5; $startjaar++) {
                    $waarde = 0;

                    $qq = mysqli_query($conn, "SELECT * FROM tbl_comp_index WHERE jaar = " . $startjaar);


                    if (mysqli_num_rows($qq) > 0) {

                        $q = mysqli_fetch_object($qq);

                        if ($q) {
                            $waarde = $q->waarde;
                        }

                        $qq1 = mysqli_query($conn, "SELECT * FROM tbl_comp_index WHERE jaar = " . ($startjaar + 1));

                        if (mysqli_num_rows($qq1) > 0) {

                            $q1 = mysqli_fetch_object($qq1);

                            $index_prijs[$project1->prijs_comp_jaar] = $project1->prijs_comp;

                            if ($waarde != 0 && $q1->waarde != 0 && $project1->prijs_comp_jaar <= $startjaar) {
                                if ($verm == 0) {
                                    $verm = $project1->prijs_comp / $waarde * $q1->waarde;
                                } else {
                                    $verm = $verm / $waarde * $q1->waarde;
                                }
                            } else {
                                $verm = 0;
                            }

                            if (!isset($index_prijs[$startjaar])) {
                                // nakijken ofdat het stijgingspercentage overschreden is

                                if (!empty($project1->prijs_comp_index) && $project1->prijs_comp_index > 0) {
                                    if ($index_prijs[$startjaar - 1] * (1 + ($project1->prijs_comp_index / 100) ) < $verm) {
                                        $verm = $index_prijs[$startjaar - 1] * (1 + ($project1->prijs_comp_index / 100) );
                                    }
                                }

                                $index_prijs[$startjaar] = $verm;
                            }
                        }
                    }
                }
            }



            echo "</table>";

            /*
              echo "<table width='100%'>";
              echo "<tr>";
              echo "<td valign='top' style='width:160px;' >Stroomprijs : </td><td>";

              for( $startjaar = 2011;$startjaar<=date('Y')+1;$startjaar++ )
              {
              $waarde = 0;

              $q = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_comp_index WHERE jaar = " . $startjaar));

              if( $q )
              {
              $waarde = $q->waarde;
              }

              echo $startjaar;
              echo "&nbsp;-&nbsp;&euro;";

              if( isset( $index_prijs[$startjaar] ) )
              {
              echo number_format($index_prijs[$startjaar], 4, ",", "");
              }else
              {
              echo "0";
              }

              echo "<br/>";
              //echo $index_prijs[2012];
              }

              echo "</td>";
              echo "</tr>";
              echo "</table>";
             */
            echo "</fieldset>";
        }

        if ($cus->cus_int_iso == '1') {
            $iso = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customer_iso WHERE cus_id = " . $cus->cus_id));

            echo "<fieldset>";
            echo "<legend>Isolation</legend>";
            echo "<table>";
            echo "<tr>";
            echo "<td width='205'>Type of roof :</td>";
            echo "<td>";
            echo "<select name='iso_soort_dak' id='iso_soort_dak' >";
            echo "<option value='0'> == Make your choice == </option>";

            $dak_arr = array();
            $dak_arr["plat"] = "Plat dak";
            $dak_arr["hellend"] = "Hellend dak";

            foreach ($dak_arr as $index => $gebruik) {
                $sel = "";

                if ($iso && $iso->cus_dak == $index) {
                    $sel = " selected='selected' ";
                }
                echo "<option " . $sel . " value='" . $index . "'>" . $gebruik . "</option>";
            }

            echo "</select>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>What isolate :</td>";
            echo "<td>";
            echo "<select name='iso_wat' id='iso_wat' >";
            echo "<option value=''> == Make your choice == </option>";

            $iso_wat = array();
            $iso_wat["vloer"] = "Vloer";
            $iso_wat["dak"] = "Dak";

            foreach ($iso_wat as $index => $gebruik) {
                $sel = "";
                if ($iso && $iso->cus_waar == $index) {
                    $sel = " selected='selected' ";
                }
                echo "<option " . $sel . " value='" . $index . "'>" . $gebruik . "</option>";
            }

            echo "</select>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Area to be insulated :</td>";
            echo "<td>";
            echo "<input type='text' name='iso_opp' id='iso_opp' value='" . $iso->cus_m2 . "' />";
            echo "m^2</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Attic cleared? :</td>";
            echo "<td>";
            echo "<select name='iso_zolder' id='iso_zolder' >";
            echo "<option value=''> == Make your choice == </option>";

            $zolder_ok = array();
            $zolder_ok['0'] = "Neen";
            $zolder_ok['1'] = "Ja";

            foreach ($zolder_ok as $index => $gebruik) {
                $sel = "";
                if ($iso && $iso->cus_clean == $index) {
                    $sel = " selected='selected' ";
                }
                echo "<option " . $sel . " value='" . $index . "'>" . $gebruik . "</option>";
            }

            echo "</select>";
            echo "</td>";
            echo "</tr>";

            echo "</table>";
            echo "</fieldset>";
        }

        if ($cus->cus_int_pid == "1") {
            $q_pid = mysqli_query($conn, "SELECT * FROM kal_cus_pid WHERE cus_id = " . $cus->cus_id) or die(mysqli_error($conn) . " " . __LINE__);

            $pid_tel = "";
            $pid_sim = "";

            if (mysqli_num_rows($q_pid) > 0) {
                $pid = mysqli_fetch_object($q_pid);

                $pid_tel = $pid->telnr;
                $pid_sim = $pid->simnr;
            }

            echo "<fieldset>";
            echo "<legend>PID Box</legend>";
            echo "<table width='100%'>";
            echo "<tr>";
            echo "<td width='160' >Tel.nr. :</td>";
            echo "<td>";
            echo "<input type='text' class='lengte' name='pid_tel' id='pid_tel' value= '" . $pid_tel . "' />";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>SIM.nr. :</td>";
            echo "<td>";
            echo "<input type='text' class='lengte' name='pid_sim' id='pid_sim' value= '" . $pid_sim . "' />";
            echo "</td>";
            echo "</tr>";

            echo "</table>";
            echo "</fieldset>";
        }

        echo "<fieldset>";
        echo "<legend>Extra documents/photos</legend>";
        echo "<table>";
        echo "<tr><td colspan='2'>";
        echo "<i>Choose good filenames.</i>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>";
        echo "<strong>Upload documents:</strong>";
        echo "</td>";
        echo "<td>";
        echo "<input type='file' name='file_extra[]' id='file_extra1' multiple='multiple'/>";
        echo "</td>";
        echo "</tr>";


        // zoeken of er offertes zijn
        $q_zoek_extra = mysqli_query($conn, "SELECT * 
				                               FROM kal_customers_files
				                              WHERE cf_cus_id = '" . $cus->cus_id . "'
				                                AND cf_soort = 'file_extra' ");

        if (mysqli_num_rows($q_zoek_extra) > 0) {
            echo "<tr><td colspan='2'>";
            echo "<table width='100%'>";

            while ($extra_f = mysqli_fetch_object($q_zoek_extra)) {
                if (file_exists("cus_docs/" . $cus->cus_id . "/file_extra/" . $extra_f->cf_file)) {
                    echo "<tr><td align='right' valign='top' class='offerte_gegevens' >";

                    if (($_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->user_id == 29) {
                        echo "Delete?&nbsp;&nbsp;<input type='checkbox' name='file_extra_del_" . $extra_f->cf_id . "' id='file_extra_del_" . $extra_f->cf_id . "' />";
                    }

                    echo "</td><td>";

                    echo "<a href='cus_docs/" . $cus->cus_id . "/file_extra/" . $extra_f->cf_file . "' target='_blank' >";
                    echo $extra_f->cf_file;
                    echo "</a>";

                    echo "</td>";
                    echo "</tr>";

                    if (!$isiPad && !$isiPhone) {
                        $extensions = strtolower(getExtFromFile($extra_f->cf_file));

                        if ($extensions == "jpg" || $extensions == "jpeg") {
                            echo "<tr><td colspan='2'>";
                            echo "<a href='cus_docs/" . $cus->cus_id . "/file_extra/" . rawurlencode($extra_f->cf_file) . "' target='_blank' rel='lightbox' >";
                            echo "<figure>";
                            echo "<img src='cus_docs/" . $cus->cus_id . "/file_extra/" . rawurlencode($extra_f->cf_file) . "' width='400' />";
                            echo "</figure>";
                            echo "</a>";
                            echo "<hr/>";
                            echo "</td></tr>";
                        } else {
                            echo "<tr><td colspan='2'>";
                            echo "<hr/>";
                            echo "</td></tr>";
                        }
                    }
                }
            }

            echo "</table>";
            echo "</td></tr>";
        }




        echo "</table>";
        echo "</fieldset>";
        // AAAAAAAAAAAAA
        echo "<fieldset style='display:none;' >";
        echo "<legend>Transacties <a href='klanten.php?tab_id=1&klant_id=" . $cus->cus_id . "'>"
        . "<img width='16' border='0' height='16' title='Vernieuwen' alt='Vernieuwen' src='images/refresh.png'></a></legend>";
        echo "<a id='transactie_add' href='ajax/transactie.php?klantid=" . $cus->cus_id . "'' >Transactie toevoegen</a>";
        echo "<table cellpadding='0' cellspacing='0'>";
        echo "<tr>";
        echo "<td>";
            echo "<table cellspacing='0' cellpadding='0' id='tbl_transactie_list' width='420'>";
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
                $get_all_transacties = mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE soort='0' AND soort_id=".$cus->cus_id);
                $i=0;
                if(mysqli_num_rows($get_all_transacties) != 0){
                     while($transactie = mysqli_fetch_object($get_all_transacties)){
                        $i++;
                        $kleur = $kleur_grijs;
                        if ($i % 2) {
                            $kleur = "white";
                        }
                        echo "<tr id='transactie_".$transactie->id."' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                            echo "<td>";
                                    echo "<a href='ajax/transactie.php?klantid=" . $cus->cus_id . "&trans_id=".$transactie->id."' class='edit_trans' alt=".$transactie->id." '><img src='images/edit.png'/></a>";
                                    echo "<a href='' class='delete_trans' alt=".$transactie->id."><img src='images/delete.png'/></a>";
                            echo "</td>";
                            echo "<td>";
                                    $q= mysqli_query($conn_car, "SELECT * FROM tbl_products WHERE id=".$transactie->prod_id);
                                    $get_name = mysqli_fetch_object($q);
                                    echo truncate($get_name->name,40);
                            echo "</td>";
                            echo "<td>";
                                    if($transactie->status == '0'){
                                        echo "Aankoop";
                                    }else{
                                        echo "Verkoop";
                                    }
                            echo "</td>";
                            if($transactie->factuur_id != '' && $transactie->factuur_id != 0){
                                $get_fac = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=".$transactie->factuur_id));
                                /* boekjaar bepalen */
                                $fac_id = array();
                                $fac_datum =  $get_fac->cf_date;
                                $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
                                while($boekjaar = mysqli_fetch_object($q_boekjaren)){
                                    if($fac_datum > $boekjaar->boekjaar_start && $fac_datum <= $boekjaar->boekjaar_einde){
                                        $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
                                    }
                                }
                                echo "<td>";
                                echo "<a class='trans_pdf' href='cus_docs/".$cus->cus_id."/factuur/".$dir."/".$get_fac->cf_file."' alt='Verkoop factuur' target='_blank'>";
                                echo "<img src='images/pdf.jpg'>";
                                echo "</a>";
                                echo "</td>";
                            }else{
                                echo "<td></td>";
                            }
                        echo "</tr>";
                        echo "<tr id='extra_".$transactie->id."'>";
                            echo "<td colspan='4'>";
                                echo "<table width='370' style='margin-left:50px;' cellpadding='0' cellspacing='0' id='tbl_".$transactie->id."'>";
                                echo "</table>";
                            echo "</td>";
                        echo "</tr>";
                    }
                }
            echo "</table>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</fieldset>";
        // EINDE TABEL 2
        echo "</td>";
        echo "</tr>";

        echo "</table>";
       
        echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
        echo "<input type='hidden' name='cus_id' id='cus_id' value='" . $cus->cus_id . "' />";
        echo "<input type='hidden' name='cus_id2' id='cus_id2' value='" . $cus->cus_id . "' />";

        if (( $_SESSION[ $session_var ]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->group_id == 4 || $_SESSION[ $session_var ]->group_id == 5 || $_SESSION[ $session_var ]->user_id == 29) {
            echo "<table border='0' width='100%' class='main_table'>";
            echo "<tr><td>&nbsp;</td></tr>";

            if ($aant_verplicht > 0) {
                if ($aant_verplicht == 1) {
                    echo "<tr><td align='center'><span class='error'>Er is " . $aant_verplicht . " verplicht veld dat nog dient ingevuld te worden.</span></td></tr>";
                } else {
                    echo "<tr><td align='center'><span class='error'>Er zijn " . $aant_verplicht . " verplichte velden die nog dienen ingevuld te worden.</span></td></tr>";
                }
            }

            echo "<tr><td colspan='2' align='center'>";
            echo "<input type='submit' name='pasaan' id='pasaan' value='Save' onclick='selectAll(\"invitees[]\", true);' />&nbsp;&nbsp;&nbsp;";

            if ($_SESSION[ $session_var ]->group_id == 1) {
                echo "<input type='submit' name='verwijderen' id='verwijderen' value='Delete' onclick=\"javascript:return confirm('Deze klant verwijderen?')\" />";
            }
            echo "</td></tr>";
            echo "<tr><td>&nbsp;</td></tr>";
            echo "</table>";
        }

        echo "</form>";
    }

    if ($cus->cus_oa == '1') {
        ?>
        <script type='text/javascript'>
            switchOA("none");
        </script>
        <?php
    }
}
?>
