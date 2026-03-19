<?php

if (isset($_POST["pasaan"]) && $_POST["pasaan"] == "Save") 
{
    //$isProject = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $_POST["cus_id"]));
    $q_cus_p = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"]) or die(mysqli_error($conn) . " " . __LINE__);
    $cus_p = mysqli_fetch_object($q_cus_p);

    $isProject = 0;
    if ($cus_p->cus_10 == '1') {
        $isProject = 1;
    }

    if ($isProject) {
        $gev = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers_project WHERE cus_id = " . $_POST["cus_id"]));

        $onderhoud = "0000-00-00";

        if (isset($_POST["onderhoud_datum"]) && !empty($_POST["onderhoud_datum"])) {
            $onderhoud = changeDate2EU($_POST["onderhoud_datum"]);
        }

        if ($gev == 0) {

            $q = "INSERT INTO kal_customers_project(cus_id,
                                                    overeenkomst,
                                                    leider,
                                                    oa,
                                                    oa_elec,
                                                    monitoring,
                                                    opstal,
                                                    vergoeding,
                                                    invest,
                                                    verzekering,
                                                    rvo_verleden,
                                                    bedrag,
                                                    ond_actief,
                                                    ouderhoud,
                                                    onderhoud_datum,
                                                    onderhoud_gratis,
                                                    dossiernr,
                                                    ean_a,
                                                    ean_i,
                                                    ean_e,
                                                    ean_p) 
                                            VALUES(" . $_POST["cus_id"] . ",
                                                  '" . $_POST["overeenkomst"] . "',
                                                  '" . $_POST["projectleider"] . "',
                                                  '" . $_POST["onderaannemer"] . "',
                                                  '" . $_POST["oa_elec"] . "',
                                                  '" . $_POST["monitoring"] . "',
                                                  '" . $_POST["opstal"] . "',
                                                  '" . $_POST["vergoeding_jaar"] . "',
                                                  '" . $_POST["investering"] . "',
                                                  '" . $_POST["verzekering"] . "',
                                                  '" . $_POST["rvo_verleden"] . "',
                                                  '" . $_POST["verk_bedrag"] . "',
                                                  '" . $_POST["ond_actief"] . "',
                                                  '" . $_POST["onderhoudsbedrag"] . "',
                                                  '" . $onderhoud . "',
                                                  '" . $_POST["onderhoud_gratis"] . "',
                                                  '" . $_POST["dossiernr"] . "',
                                                  '" . $_POST["ean_a"] . "',
                                                  '" . $_POST["ean_i"] . "',
                                                  '" . $_POST["ean_e"] . "',
                                                  '" . $_POST["ean_p"] . "')";
        } else {
            $q = " UPDATE kal_customers_project SET overeenkomst = '" . $_POST["overeenkomst"] . "',
                                                    leider = '" . $_POST["projectleider"] . "',
                                                    oa = '" . $_POST["onderaannemer"] . "',
                                                    oa_elec = '" . $_POST["oa_elec"] . "',
                                                    monitoring = '" . $_POST["monitoring"] . "',
                                                    opstal = '" . $_POST["opstal"] . "',
                                                    vergoeding = '" . $_POST["vergoeding_jaar"] . "',
                                                    invest = '" . $_POST["investering"] . "',
                                                    verzekering = '" . $_POST["verzekering"] . "',
                                                    rvo_verleden = '" . $_POST["rvo_verleden"] . "',
                                                    bedrag = '" . $_POST["verk_bedrag"] . "',
                                                    dossiernr = '" . $_POST["dossiernr"] . "',
                                                    ean_a = '" . $_POST["ean_a"] . "',
                                                    ean_i = '" . $_POST["ean_i"] . "',
                                                    ean_e = '" . $_POST["ean_e"] . "',
                                                    ean_p = '" . $_POST["ean_p"] . "',
                                                    ond_actief = '" . $_POST["ond_actief"] . "',
                                                    ouderhoud = '" . $_POST["onderhoudsbedrag"] . "', 
                                                    onderhoud_datum = '" . $onderhoud . "',
                                                    onderhoud_gratis = '" . $_POST["onderhoud_gratis"] . "'
                                              WHERE cus_id = " . $_POST["cus_id"];
        }

        mysqli_query($conn, $q) or die(mysqli_error($conn) . " " . $q . " " . __LINE__);

        // toevoegen van de extra kost

        if (!empty($_POST["kost_naam"]) && !empty($_POST["kost_bedrag"])) {
            $q_ins = mysqli_query($conn, "INSERT INTO kal_customers_project_extra(cus_id, kost, bedrag) VALUES(" . $_POST["cus_id"] . ",'" . $_POST["kost_naam"] . "','" . $_POST["kost_bedrag"] . "')");
        }


        // uploaden van de netstudie
        $netstudie_filename = "";
        if (isset($_FILES["netstudie"]) && $_FILES["netstudie"]["tmp_name"] != "") {
            $netstudie_filename = $_FILES["netstudie"]["name"];

            chdir("cus_docs/");
            @mkdir($_POST["cus_id"]);
            chdir($_POST["cus_id"]);
            @mkdir("netstudie");
            chdir("netstudie");
            move_uploaded_file($_FILES['netstudie']['tmp_name'], $netstudie_filename);
            chdir("../../../");

            // toevoegen in de nieuwe tabel
            $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
    		                                                              cf_soort, 
    		                                                              cf_file) 
    		                                                      VALUES('" . $_POST["cus_id"] . "',
    		                                                             'netstudie',
    		                                                             '" . $netstudie_filename . "')") or die(mysqli_error($conn));
        }
        // EINDE TOEVOEGEN netstudie
        // uploaden van de INJECTIE
        $netstudie_filename = "";
        if (isset($_FILES["injectie"]) && $_FILES["injectie"]["tmp_name"] != "") {
            $netstudie_filename = $_FILES["injectie"]["name"];

            chdir("cus_docs/");
            @mkdir($_POST["cus_id"]);
            chdir($_POST["cus_id"]);
            @mkdir("injectie");
            chdir("injectie");
            move_uploaded_file($_FILES['injectie']['tmp_name'], $netstudie_filename);
            chdir("../../../");

            // toevoegen in de nieuwe tabel
            $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
    		                                                              cf_soort, 
    		                                                              cf_file) 
    		                                                      VALUES('" . $_POST["cus_id"] . "',
    		                                                             'injectie',
    		                                                             '" . $netstudie_filename . "')") or die(mysqli_error($conn));
        }
        // EINDE TOEVOEGEN INJECTIE
        // uploaden van de INJECTIE
        $netstudie_filename = "";
        if (isset($_FILES["contract"]) && $_FILES["contract"]["tmp_name"] != "") {
            $netstudie_filename = $_FILES["contract"]["name"];

            chdir("cus_docs/");
            @mkdir($_POST["cus_id"]);
            chdir($_POST["cus_id"]);
            @mkdir("contract");
            chdir("contract");
            move_uploaded_file($_FILES['contract']['tmp_name'], $netstudie_filename);
            chdir("../../../");

            // toevoegen in de nieuwe tabel
            $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
    		                                                              cf_soort, 
    		                                                              cf_file) 
    		                                                      VALUES('" . $_POST["cus_id"] . "',
    		                                                             'contract',
    		                                                             '" . $netstudie_filename . "')") or die(mysqli_error($conn));
        }
        // EINDE TOEVOEGEN INJECTIE
        // VERWIJDEREN netstudie FILE
        foreach ($_POST as $key => $post) {
            if (substr($key, 0, 14) == "netstudie_del_") {
                // opzoeken record
                $id = substr($key, 14);
                $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'netstudie' AND cf_id = " . $id));

                // record verwijderen
                $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

                // bestand verwijderen 
                if (file_exists("cus_docs/" . $_POST["cus_id"] . "/netstudie/" . $offerte->cf_file)) {
                    unlink("cus_docs/" . $_POST["cus_id"] . "/netstudie/" . $offerte->cf_file);
                }
            }
        }
        // EINDE VERWIJDEREN netstudie FILE
        // VERWIJDEREN contract FILE
        foreach ($_POST as $key => $post) {
            if (substr($key, 0, 13) == "contract_del_") {
                // opzoeken record
                $id = substr($key, 13);
                $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'contract' AND cf_id = " . $id));

                // record verwijderen
                $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

                // bestand verwijderen 
                if (file_exists("cus_docs/" . $_POST["cus_id"] . "/contract/" . $offerte->cf_file)) {
                    unlink("cus_docs/" . $_POST["cus_id"] . "/contract/" . $offerte->cf_file);
                }
            }
        }
        // EINDE VERWIJDEREN contract FILE
        // VERWIJDEREN contract FILE
        foreach ($_POST as $key => $post) {
            if (substr($key, 0, 13) == "injectie_del_") {
                // opzoeken record
                $id = substr($key, 13);
                $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'injectie' AND cf_id = " . $id));

                // record verwijderen
                $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

                // bestand verwijderen 
                if (file_exists("cus_docs/" . $_POST["cus_id"] . "/injectie/" . $offerte->cf_file)) {
                    unlink("cus_docs/" . $_POST["cus_id"] . "/injectie/" . $offerte->cf_file);
                }
            }
        }
        // EINDE VERWIJDEREN contract FILE




        /*
          if( $_SESSION[ $session_var ]->user_id == 19 )
          {
          echo "<pre>";
          var_dump( $_FILES );
          echo "</pre>";
          }
         */
    }

    $klant_old_data = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"]));

    if (isset($_POST["datum_arei1"]) && !empty($_POST["datum_arei1"])) {
        $_POST["datum_arei"] = $_POST["datum_arei1"];
    }

    if (isset($_POST["datum_indienst1"]) && !empty($_POST["datum_indienst1"])) {
        $_POST["datum_indienst"] = $_POST["datum_indienst1"];
    }

    if (!empty($_POST["aant_panelen"]) && $_POST["aant_panelen"] != 0 && !empty($_POST["werk_aant_panelen"]) && $_POST["werk_aant_panelen"] != 0) {
        if ($_POST["aant_panelen"] > 0 && $_POST["werk_aant_panelen"] > 0 && $_POST["werk_aant_panelen"] != $klant_old_data->cus_werk_aant_panelen) {
            // zoeken ofdat de mail al verstuurd is.
            $aant_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_panelen WHERE cus_id = " . $_POST["cus_id"] . " AND van_panelen = " . $klant_old_data->cus_werk_aant_panelen . " AND aant_panelen = " . $_POST["werk_aant_panelen"]));

            if ($aant_zoek == 0) {
                // mail naar de acma
                $acma_inv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
                $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
                $bericht .= "<tr><td>Beste</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>Het aantal panelen is gewijzigd van " . $klant_old_data->cus_werk_aant_panelen . " naar " . $_POST["werk_aant_panelen"] . " en de prijs dient herberekend te worden.</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>ACMA : " . $acma_inv->voornaam . " " . $acma_inv->naam . "</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>Klantgegevens :</td></tr>";
                $bericht .= "<tr><td><b>" . $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf . "</b></td></tr>";
                $bericht .= "<tr><td><b>" . $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr . "</b></td></tr>";
                $bericht .= "<tr><td><b>" . $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente . "</b></td></tr>";
                $bericht .= "<tr><td><b>GSM. : " . $klant_old_data->cus_gsm . "</b></td></tr>";
                // TEL AAAAA
                $bericht .= "<tr><td><b>Tel. : " . $klant_old_data->cus_tel . "</b></td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "</table>";

                mail($acma_inv->email, "Aantal panelen is gewijzigd bij " . $klant_old_data->cus_naam, $bericht, $headers);
                mail("elise@weygersmontage.be", "Aantal panelen is gewijzigd bij " . $klant_old_data->cus_naam, $bericht, $headers);

                // insert rec into new table
                $q_ins = "INSERT INTO kal_cus_panelen(cus_id, van_panelen, aant_panelen) VALUES(" . $_POST["cus_id"] . "," . $klant_old_data->cus_werk_aant_panelen . "," . $_POST["werk_aant_panelen"] . ")";
                mysqli_query($conn, $q_ins) or die(mysqli_error($conn));
            }
        }
    }

    // begin kmo opslaan van de kortingen
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 6) == 'ascus_') {
            // ascus_5 10
            $tmp = explode("_", $key);
            $as_id = $tmp[1];
            $korting = $post;

            if ($korting == '') {
                $korting = 0;
            }

            // eerst kijken ofdat deze regel al bestaat, als bestaat dan upd anders insert
            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_as_cus_korting WHERE as_id = " . $as_id . " AND cus_id = " . $_POST["cus_id"]) or die(mysqli_error($conn));

            if (mysqli_num_rows($q_zoek) == 0) {
                // insert
                $q_ins = mysqli_query($conn, "INSERT INTO kal_as_cus_korting(as_id, 
				                                                     cus_id, 
				                                                     korting)
				                                             VALUES(" . $as_id . ",
				                                                    " . $_POST["cus_id"] . ",
				                                                    " . $korting . ")") or die(mysqli_error($conn));
            } else {
                // update
                $q_upd = mysqli_query($conn, "UPDATE kal_as_cus_korting SET korting = " . $korting . " WHERE as_id = " . $as_id . " AND cus_id = " . $_POST["cus_id"]) or die(mysqli_error($conn));
            }
        }
    }
    // eind kmo opslaan van de kortingen.
    // eerst de klant ophalen en zo nakijken welke velden er gewijzigd zijn geweest
    // als nieuwe of andere acma, dan moet deze een mail krijgen met de melding van een nieuwe klant
    //echo "<br>1" . $klant_old_data->cus_acma . "1 2" . $_POST["acma"] . "2";

    if (empty($klant_old_data->cus_acma) && !empty($_POST["acma"])) {
        // toekennen van een acma
        $q_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['acma']));

        $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        $bericht .= "<tr><td>Beste " . $q_acma->naam . " " . $q_acma->voornaam . " </td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>Klantgegevens :</td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["naam"] . " " . $_POST["bedrijf"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["straat"] . " " . $_POST["nr"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["postcode"] . " " . $_POST["gemeente"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>GSM. : " . $_POST["gsm"] . "</b></td></tr>";
        // TEL AAAAA
        $bericht .= "<tr><td><b>Tel. : " . $_POST["tel"] . "</b></td></tr>";
        $bericht .= "</table>";

        mail($q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers);
    }

    $cus1 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));

    if (!empty($klant_old_data->cus_acma) && !empty($_POST["acma"]) && $klant_old_data->cus_acma != $_POST["acma"]) {
        // toekennen van een andere acma
        // mailen naar nieuwe en mailen naar de oude acma
        $q_acma_new = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['acma']));
        $q_acma_old = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));

        $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        $bericht .= "<tr><td>Beste " . $q_acma_new->naam . " " . $q_acma_new->voornaam . " </td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>Klantgegevens :</td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["naam"] . " " . $_POST["bedrijf"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["straat"] . " " . $_POST["nr"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["postcode"] . " " . $_POST["gemeente"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>GSM. : " . $_POST["gsm"] . "</b></td></tr>";
        // TEL AAAAA
        $bericht .= "<tr><td><b>Tel. : " . $_POST["tel"] . "</b></td></tr>";
        $bericht .= "</table>";

        mail($q_acma_new->email, "Nieuwe klant toegevoegd", $bericht, $headers);
        //mail( "dimitri@futech.be", "Nieuwe klant toegevoegd", $bericht, $headers );

        $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        $bericht .= "<tr><td>Beste " . $q_acma_old->naam . " " . $q_acma_old->voornaam . " </td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>Deze klant werd weggenomen bij u en geplaatst bij " . $q_acma_new->naam . " " . $q_acma_new->voornaam . ".</td></tr>";
        $bericht .= "<tr><td>&nbsp;</td></tr>";
        $bericht .= "<tr><td>Klantgegevens :</td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["naam"] . " " . $_POST["bedrijf"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["straat"] . " " . $_POST["nr"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>" . $_POST["postcode"] . " " . $_POST["gemeente"] . "</b></td></tr>";
        $bericht .= "<tr><td><b>GSM. : " . $_POST["gsm"] . "</b></td></tr>";
        // TEL AAAAA
        $bericht .= "<tr><td><b>Tel. : " . $_POST["tel"] . "</b></td></tr>";
        $bericht .= "</table>";

        mail($q_acma_old->email, "Klant weggenomen", $bericht, $headers);
        //mail( "dimitri@futech.be", "Klant weggenomen", $bericht, $headers );
    }
    // einde mailen naar nieuwe acma
    // VERWIJDEREN OFFERTE FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 12) == "offerte_del_") {
            // opzoeken record
            $id = substr($key, 12);
            $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'offerte' AND cf_id = " . $id));

            // record verwijderen
            $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/offerte/" . $offerte->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/offerte/" . $offerte->cf_file);
            }
        }
    }
    // EINDE VERWIJDEREN OFFERTE FILE
    // TOEVOEGEN offerte file
    $offerte_file = "";
    $offerte_filename = "";

    if (isset($_FILES["offerte"]) && $_FILES["offerte"]["tmp_name"] != "") {
        $offerte_file = "";
        $offerte_filename = $_FILES["offerte"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("offerte");
        chdir("offerte");
        move_uploaded_file($_FILES['offerte']['tmp_name'], $offerte_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'offerte',
		                                                             '" . $offerte_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN offerte file
    // VERWIJDEREN MON OFFERTE FILE AAA
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 13) == "file_mon_del_") {
            // opzoeken record
            $id = substr($key, 13);
            $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'mon_offerte' AND cf_id = " . $id));

            // record verwijderen
            $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/mon/" . $offerte->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/mon/" . $offerte->cf_file);
            }
        }
    }
    // EINDE VERWIJDEREN MON OFFERTE FILE
    // VERWIJDEREN extra FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 15) == "file_extra_del_") {
            // opzoeken record
            $id = substr($key, 15);
            $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'file_extra' AND cf_id = " . $id));

            // record verwijderen
            $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/file_extra/" . $offerte->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/file_extra/" . $offerte->cf_file);
            }
        }
    }
    // EINDE VERWIJDEREN extra FILE
    // TOEVOEGEN extra file file_extra
    $extra_filename = "";

    if( is_array( $_FILES["file_extra"]["tmp_name"] ) && count($_FILES["file_extra"]["tmp_name"]) > 0 )
    {
        for ($bestand = 0; $bestand < count($_FILES["file_extra"]["tmp_name"]); $bestand++) {
            if (isset($_FILES["file_extra"]) && $_FILES["file_extra"]["tmp_name"][$bestand] != "") {
                $extra_filename = $_FILES["file_extra"]["name"][$bestand];

                if ($extra_filename == "image.jpg") {
                    $extra_filename = $bestand . "_" . date("d") . "-" . date('m') . "-" . date('Y') . "_" . date('H') . "u" . date('i') . "m" . date('s') . "s.jpg";
                }

                if ($extra_filename == "capturedvideo.MOV") {
                    $extra_filename = "video_" . $i . "_" . date("d") . "-" . date('m') . "-" . date('Y') . "_" . date('H') . "u" . date('i') . "m" . date('s') . "s.mov";
                }

                @chdir("cus_docs/");
                @mkdir($_POST["cus_id"]);
                @chdir($_POST["cus_id"]);
                @mkdir("file_extra");
                chdir("file_extra");
                move_uploaded_file($_FILES['file_extra']['tmp_name'][$bestand], $extra_filename);
                chdir("../../../");

                // toevoegen in de nieuwe tabel
                $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
                                                                            cf_soort, 
                                                                            cf_file) 
                                                                    VALUES('" . $_POST["cus_id"] . "',
                                                                            'file_extra',
                                                                            '" . $extra_filename . "')") or die(mysqli_error($conn));
            }
        }
    }
    
    // EINDE TOEVOEGEN extra file
    // VERWIJDEREN orderbon FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 10) == "order_del_") {
            // opzoeken record
            $id = substr($key, 10);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'orderbon' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $order->cf_file);
            }
        }
    }
    // EINDE VERWIJDEREN orderbon FILE
    // toevoegen hypotheek
    $hypo_file = "";
    $hypo_filename = "";

    if (isset($_FILES["hypotheek"]) && $_FILES["hypotheek"]["tmp_name"] != "") {
        $hypo_file = "";
        $hypo_filename = $_FILES["hypotheek"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("hypotheek");
        chdir("hypotheek");
        move_uploaded_file($_FILES['hypotheek']['tmp_name'], $hypo_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'hypotheek',
		                                                             '" . $hypo_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN hypotheek file
    // VERWIJDEREN hypotheek FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 14) == "hypotheek_del_") {
            // opzoeken record
            $id = substr($key, 14);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'hypotheek' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/hypotheek/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/hypotheek/" . $order->cf_file);
            }
        }
    }
    // einde verwijderen hypotheek
    // toevoegen eigendomsacte
    $eigendom_file = "";
    $eigendom_filename = "";

    if (isset($_FILES["eigendom"]) && $_FILES["eigendom"]["tmp_name"] != "") {
        $eigendom_file = "";
        $eigendom_filename = $_FILES["eigendom"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("eigendom");
        chdir("eigendom");
        move_uploaded_file($_FILES['eigendom']['tmp_name'], $eigendom_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'eigendom',
		                                                             '" . $eigendom_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN eigendomsacte file
    // VERWIJDEREN eigendomsacte FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 13) == "eigendom_del_") {
            // opzoeken record
            $id = substr($key, 13);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'eigendom' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/eigendom/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/eigendom/" . $order->cf_file);
            }
        }
    }
    // einde verwijderen eigendomsacte
    // toevoegen isolatie
    $isolatie_file = "";
    $isolatie_filename = "";

    if (isset($_FILES["isolatie"]) && $_FILES["isolatie"]["tmp_name"] != "") {
        $isolatie_file = "";
        $isolatie_filename = $_FILES["isolatie"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("isolatie");
        chdir("isolatie");
        move_uploaded_file($_FILES['isolatie']['tmp_name'], $isolatie_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'isolatie',
		                                                             '" . $isolatie_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN isolatie file
    // VERWIJDEREN isolatie FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 13) == "isolatie_del_") {
            // opzoeken record
            $id = substr($key, 13);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'isolatie' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/isolatie/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/isolatie/" . $order->cf_file);
            }
        }
    }
    // einde verwijderen isolatie
    // toevoegen loonfiche
    $loonfiche_file = "";
    $loonfiche_filename = "";

    if (isset($_FILES["loonfiche"]) && $_FILES["loonfiche"]["tmp_name"] != "") {
        $loonfiche_file = "";
        $loonfiche_filename = $_FILES["loonfiche"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("loonfiche");
        chdir("loonfiche");
        move_uploaded_file($_FILES['loonfiche']['tmp_name'], $loonfiche_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'loonfiche',
		                                                             '" . $loonfiche_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN loonfiche file
    // VERWIJDEREN loonfiche FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 14) == "loonfiche_del_") {
            // opzoeken record
            $id = substr($key, 14);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'loonfiche' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/loonfiche/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/loonfiche/" . $order->cf_file);
            }
        }
    }
    // einde verwijderen loonfiche
    // toevoegen alg. vw
    $vol_off_file = "";
    $vol_off_filename = "";

    if (isset($_FILES["vol_off"]) && $_FILES["vol_off"]["tmp_name"] != "") {
        $vol_off_file = "";
        $vol_off_filename = $_FILES["vol_off"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("vol_off");
        chdir("vol_off");
        move_uploaded_file($_FILES['vol_off']['tmp_name'], $vol_off_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'vol_off',
		                                                             '" . $vol_off_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN alg. vw. file
    // VERWIJDEREN alg. vw. FILE
    foreach ($_POST as $key => $post) {
        if (substr($key, 0, 12) == "vol_off_del_") {
            // opzoeken record
            $id = substr($key, 12);
            $order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'vol_off' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/vol_off/" . $order->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/vol_off/" . $order->cf_file);
            }
        }
    }
    // einde verwijderen als.vw
    // TOEVOEGEN orderbon file
    $order_file = "";
    $order_filename = "";

    if (isset($_FILES["orderbon"]) && $_FILES["orderbon"]["tmp_name"] != "") {
        $order_file = "";
        $order_filename = $_FILES["orderbon"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("orderbon");
        chdir("orderbon");
        move_uploaded_file($_FILES['orderbon']['tmp_name'], $order_filename);
        chdir("../../../");

        // toevoegen in de nieuwe tabel
        $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('" . $_POST["cus_id"] . "',
		                                                             'orderbon',
		                                                             '" . $order_filename . "')") or die(mysqli_error($conn));
    }
    // EINDE TOEVOEGEN orderbon file

    $werkdoc_file = "";
    $werkdoc_filename = "";
    if (isset($_FILES["werkdocument_file"]) && $_FILES["werkdocument_file"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_werkdoc_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/" . $klant_old_data->cus_werkdoc_filename);
        }

        //$werkdoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["werkdocument_file"]["tmp_name"] ));
        $werkdoc_file = "";
        $werkdoc_filename = $_FILES["werkdocument_file"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("werkdocument_file");
        chdir("werkdocument_file");
        move_uploaded_file($_FILES['werkdocument_file']['tmp_name'], $werkdoc_filename);
        chdir("../../../");
    }

    $areidoc_file = "";
    $areidoc_filename = "";
    if (isset($_FILES["doc_arei"]) && $_FILES["doc_arei"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_areidoc_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_arei/" . $klant_old_data->cus_areidoc_filename);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $areidoc_file = "";
        $areidoc_filename = $_FILES["doc_arei"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_arei");
        chdir("doc_arei");
        move_uploaded_file($_FILES['doc_arei']['tmp_name'], $areidoc_filename);
        chdir("../../../");
    }

    // begin toevoegen elec schema
    $elecdoc_file = "";
    $elecdoc_filename = "";
    if (isset($_FILES["doc_elec"]) && $_FILES["doc_elec"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_elecdoc_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_elec/" . $klant_old_data->cus_elecdoc_filename);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $elecdoc_file = "";
        $elecdoc_filename = $_FILES["doc_elec"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_elec");
        chdir("doc_elec");
        move_uploaded_file($_FILES['doc_elec']['tmp_name'], $elecdoc_filename);
        chdir("../../../");
    }
    // einde toevoegen elec schema

    $gemeentedoc_file = "";
    $gemeentedoc_filename = "";
    if (isset($_FILES["doc_gemeente"]) && $_FILES["doc_gemeente"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_gemeentedoc_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_gemeente/" . $klant_old_data->cus_gemeentedoc_filename);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $gemeentedoc_file = "";
        $gemeentedoc_filename = $_FILES["doc_gemeente"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_gemeente");
        chdir("doc_gemeente");
        move_uploaded_file($_FILES['doc_gemeente']['tmp_name'], $gemeentedoc_filename);
        chdir("../../../");
    }

    $bouwdoc_file = "";
    $bouwdoc_filename = "";
    if (isset($_FILES["doc_bouwver"]) && $_FILES["doc_bouwver"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_bouwvergunning_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_bouwver/" . $klant_old_data->cus_bouwvergunning_filename);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $bouwdoc_file = "";
        $bouwdoc_filename = $_FILES["doc_bouwver"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_bouw");
        chdir("doc_bouw");
        move_uploaded_file($_FILES['doc_bouwver']['tmp_name'], $bouwdoc_filename);
        chdir("../../../");
    }

    $stringdoc_file = "";
    $stringdoc_filename = "";
    if (isset($_FILES["doc_string"]) && $_FILES["doc_string"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_stringdoc_filename)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_string/" . $klant_old_data->cus_stringdoc_filename);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $stringdoc_file = "";
        $stringdoc_filename = $_FILES["doc_string"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_string");
        chdir("doc_string");
        move_uploaded_file($_FILES['doc_string']['tmp_name'], $stringdoc_filename);
        chdir("../../../");
    }

    $werkdoc_file1 = "";
    $werkdoc_filename1 = "";
    if (isset($_FILES["werkdoc_pic1"]) && $_FILES["werkdoc_pic1"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_werkdoc_pic1)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic1/" . $klant_old_data->cus_werkdoc_pic1);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $werkdoc_file1 = "";
        $werkdoc_filename1 = $_FILES["werkdoc_pic1"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("werkdocument_file");
        chdir("werkdocument_file");
        @mkdir("pic1");
        chdir("pic1");
        move_uploaded_file($_FILES['werkdoc_pic1']['tmp_name'], $werkdoc_filename1);
        chdir("../../../../");
    }

    $werkdoc_file2 = "";
    $werkdoc_filename2 = "";
    if (isset($_FILES["werkdoc_pic2"]) && $_FILES["werkdoc_pic2"]["tmp_name"] != "") {
        if (!empty($klant_old_data->cus_werkdoc_pic2)) {
            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic2/" . $klant_old_data->cus_werkdoc_pic2);
        }

        //$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
        $werkdoc_file2 = "";
        $werkdoc_filename2 = $_FILES["werkdoc_pic2"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("werkdocument_file");
        chdir("werkdocument_file");
        @mkdir("pic2");
        chdir("pic2");
        move_uploaded_file($_FILES['werkdoc_pic2']['tmp_name'], $werkdoc_filename2);
        chdir("../../../../");
    }

    // TOEVOEGEN offerte file
    $factuur_file = "";
    $factuur_filename = "";

    if (isset($_FILES["doc_factuur"]) && $_FILES["doc_factuur"]["tmp_name"] != "") {
        $factuur_file = "";
        $factuur_filename = $_FILES["doc_factuur"]["name"];

        $dir = date('Y') . "/";

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("factuur");
        chdir("factuur");
        if ($_SESSION[ $session_var ]->user_id != 34) {
            @mkdir($dir);
            chdir($dir);
        }
        move_uploaded_file($_FILES['doc_factuur']['tmp_name'], $factuur_filename);

        if ($_SESSION[ $session_var ]->user_id != 34) {
            chdir("../../../../");
        } else {
            chdir("../../../");
        }

        chdir("facturen/");
        if ($_SESSION[ $session_var ]->user_id != 34) {
            @mkdir($dir);
            chdir($dir);
        }

        move_uploaded_file($_FILES['doc_factuur']['tmp_name'], $factuur_filename);

        if ($_SESSION[ $session_var ]->user_id != 34) {
            chdir("../../");
        } else {
            chdir("../");
        }

        // toevoegen in de nieuwe tabel

        if ($_SESSION[ $session_var ]->user_id != 34) {
            $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
    		                                                              cf_soort, 
    		                                                              cf_file) 
    		                                                      VALUES('" . $_POST["cus_id"] . "',
    		                                                             'factuur',
    		                                                             '" . $factuur_filename . "')") or die(mysqli_error($conn));
        } else {
            $q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
    		                                                              cf_soort, 
    		                                                              cf_file,
                                                                          cf_date) 
    		                                                      VALUES('" . $_POST["cus_id"] . "',
    		                                                             'factuur',
    		                                                             '" . $factuur_filename . "',
                                                                         '0000-00-00')") or die(mysqli_error($conn));
        }
    }
    // EINDE TOEVOEGEN offerte file

    foreach ($_POST as $key => $post) {
        // VERWIJDEREN factuur FILE
        if (substr($key, 0, 12) == "factuur_del_") {
            // opzoeken record
            $id = substr($key, 12);
            $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $factuur->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // als deze rec in kal_fac_huur staat, dan deze regel ook verwijderen
            /*$zoek_fac_huur = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_fac_huur WHERE cf_id = " . $factuur->cf_id));

            if ($zoek_fac_huur > 0) {
                $q_del = "DELETE FROM kal_fac_huur WHERE cf_id = " . $factuur->cf_id;
                mysqli_query($conn, $q_del) or die(mysqli_error($conn));
            }
            */
            
            $fac_date = explode("-", $factuur->cf_date);
            $dir = $fac_date[0] . "/";

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/" . $var_fac . $dir . $factuur->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/" . $var_fac . $dir . $factuur->cf_file);
            }
        }
        // EINDE VERWIJDEREN factuur FILE
        // VERWIJDEREN cn FILE
        if (substr($key, 0, 7) == "cn_del_") {
            // opzoeken record
            $id = substr($key, 7);
            $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'creditnota' AND cf_id = " . $id));

            // record verwijderen
            $q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $factuur->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen
            // TODO nakijken in welke map het bestand moet verwijdert worden.

            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/creditnota/" . $factuur->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/creditnota/" . $factuur->cf_file);
            }
        }
        // EINDE VERWIJDEREN cn FILE
        // begin verwijderen distri offerte
        if (substr($key, 0, 15) == "distri_off_del_") {
            // opzoeken record
            $id = substr($key, 15);
            $distri_off = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_id = " . $id));

            // record verwijderen
            $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $distri_off->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/doc_distri/" . $distri_off->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/doc_distri/" . $distri_off->cf_file);
            }
        }
        // einde verwijderen distri offerte
        // begin verwijderen distri leverbon
        if (substr($key, 0, 15) == "distri_bon_del_") {
            // opzoeken record
            $id = substr($key, 15);
            $distri_off = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_bestelbon' AND cf_id = " . $id));

            // record verwijderen
            $q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $distri_off->cf_id . " LIMIT 1 ") or die(mysqli_error($conn));

            // bestand verwijderen 
            if (file_exists("cus_docs/" . $_POST["cus_id"] . "/bon_distri/" . $distri_off->cf_file)) {
                unlink("cus_docs/" . $_POST["cus_id"] . "/bon_distri/" . $distri_off->cf_file);
            }
        }
        // einde verwijderen distri leverbon
    }

    // als er als een bestand is geupload en er wordt een nieuwe bestand geupload zonder het oude te verwijderen, dan moet eerst het oude bestand verwijdert worden.
    // OF als isset bestand verwijderen.
    if ((isset($_FILES["doc_opmeting"]["tmp_name"]) && !empty($_FILES["doc_opmeting"]["tmp_name"]) && !empty($klant_old_data->cus_opmetingdoc_filename)) || isset($_POST["opmetingdoc_del"])) {
        $q_upd6 = "UPDATE kal_customers SET cus_opmetingdoc_filename = ''
	                              WHERE cus_id = " . $_POST["cus_id"];

        mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-3");

        // verwijderen loggen
        customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_opmetingdoc_filename", $klant_old_data->cus_opmetingdoc_filename, "", $conn);

        unlink("cus_docs/" . $_POST["cus_id"] . "/doc_opmeting/" . $klant_old_data->cus_opmetingdoc_filename);
    }

    // toevoegen van het stringopmetingsrapport
    $opmetingdoc_filename = "";
    if (isset($_FILES["doc_opmeting"]) && $_FILES["doc_opmeting"]["tmp_name"] != "") {
        $opmetingdoc_filename = $_FILES["doc_opmeting"]["name"];

        chdir("cus_docs/");
        @mkdir($_POST["cus_id"]);
        chdir($_POST["cus_id"]);
        @mkdir("doc_opmeting");
        chdir("doc_opmeting");
        move_uploaded_file($_FILES['doc_opmeting']['tmp_name'], $opmetingdoc_filename);
        chdir("../../../");

        $q_upd6 = "UPDATE kal_customers 
		              SET cus_opmetingdoc_filename  = '" . $opmetingdoc_filename . "'
	                WHERE cus_id = " . $_POST["cus_id"];

        mysqli_query($conn, $q_upd6) or die(mysqli_error($conn));

        // toevoegen loggen
        customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_opmetingdoc_filename", $klant_old_data->cus_opmetingdoc_filename, $opmetingdoc_filename, $conn);
    }

    if (isset($_POST["int_iso"])) {
        $iso_entry = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customer_iso WHERE cus_id = " . $_POST["cus_id"]));

        if ($iso_entry == 1) {
            $q_upd = "UPDATE kal_customer_iso 
                         SET cus_dak = '" . $_POST["iso_soort_dak"] . "',
                             cus_waar = '" . $_POST["iso_wat"] . "',
                             cus_m2 = '" . $_POST["iso_opp"] . "',
                             cus_clean = '" . $_POST["iso_zolder"] . "'
                       WHERE cus_id = " . $_POST["cus_id"];
            mysqli_query($conn, $q_upd) or die(mysqli_error($conn) . " " . $q_upd);
        } else {
            $q_ins = "INSERT INTO kal_customer_iso(cus_dak,
                                                      cus_waar,
                                                      cus_m2,
                                                      cus_clean,
                                                      cus_id) 
                                              VALUES('" . $_POST["iso_soort_dak"] . "',
                                                     '" . $_POST["iso_wat"] . "',
                                                     '" . $_POST["iso_opp"] . "',
                                                     '" . $_POST["iso_zolder"] . "',
                                                     '" . $_POST["cus_id"] . "')";
            mysqli_query($conn, $q_ins) or die(mysqli_error($conn) . " " . $q_ins);
        }
    }

    if (isset($_POST["int_pid"])) {
        $q_pid_entry = mysqli_query($conn, "SELECT * FROM kal_cus_pid WHERE cus_id = " . $_POST["cus_id"]);
        $pid_entry = mysqli_num_rows($q_pid_entry);

        $_POST["pid_tel"] = filter_var($_POST["pid_tel"], FILTER_SANITIZE_NUMBER_INT);
        $_POST["pid_tel"] = str_replace("-", "", $_POST["pid_tel"]);
        $_POST["pid_tel"] = str_replace("+", "", $_POST["pid_tel"]);

        if ($pid_entry == 1) {
            $pid_old = mysqli_fetch_object($q_pid_entry);

            //var_dump( $pid_old );

            $q_upd = "UPDATE kal_cus_pid 
                         SET telnr = '" . $_POST["pid_tel"] . "', 
                             simnr = '" . $_POST["pid_sim"] . "'
                       WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd) or die(mysqli_error($conn));

            if ($pid_old->telnr != $_POST["pid_tel"]) {
                customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "pid_tel", $pid_old->telnr, $_POST["pid_tel"], $conn);
            }

            if ($pid_old->simnr != $_POST["pid_sim"]) {
                customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "pid_sim", $pid_old->simnr, $_POST["pid_sim"], $conn);
            }
        } else {
            // add
            $q_ins = "INSERT INTO kal_cus_pid(cus_id, 
                                              telnr, 
                                              simnr) 
                                      VALUES(" . $_POST["cus_id"] . ",
                                            '" . $_POST["pid_tel"] . "',
                                            '" . $_POST["pid_sim"] . "' )";
            mysqli_query($conn, $q_ins) or die(mysqli_error($conn));

            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "pid_tel", "", $_POST["pid_tel"], $conn);
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "pid_sim", "", $_POST["pid_sim"], $conn);
        }
    }

    //$_POST["datum_net"] = changeDate2EU( $_POST["datum_net"] );
//    $_POST["datum_indienst"] = changeDate2EU( $_POST["datum_indienst"] );

    $int_boiler = "0";
    if (isset($_POST["int_boiler"]) && $_POST["int_boiler"] == 'on') {
        $int_boiler = '1';
    }

    $int_mon = "0";
    if (isset($_POST["int_mon"]) && $_POST["int_mon"] == 'on') {
        $int_mon = '1';
    }

    $int_pid = "0";
    if (isset($_POST["int_pid"]) && $_POST["int_pid"] == 'on') {
        $int_pid = '1';
    }

    $int_solar = "0";
    if (isset($_POST["int_solar"]) && $_POST["int_solar"] == 'on') {
        $int_solar = '1';
    }

    $int_iso = "0";
    if (isset($_POST["int_iso"]) && $_POST["int_iso"] == 'on') {
        $int_iso = '1';
    }

    $fac_adres = '0';
    if (isset($_POST["fac_adres"]) && $_POST["fac_adres"] == 'on') {
        $fac_adres = '1';
    }

    $cus_10 = '0';
    if (isset($_POST["cus_10"]) && $_POST["cus_10"] == 'on') {
        $cus_10 = '1';
    }

    $set_sunny = '0';
    if (isset($_POST["sunnybeam"]) && $_POST["sunnybeam"] == 'on') {
        $set_sunny = '1';
    }

    $set_huur_doc = '0';
    if (isset($_POST["huur_doc"]) && ($_POST["huur_doc"] == 'on' || $_POST["huur_doc"] == '1' )) {
        $set_huur_doc = '1';
    }

    $in_oa = '0';
    if (isset($_POST["oa"]) && $_POST["oa"] == 'on') {
        $in_oa = '1';
    }

    $ref = "0";
    if (isset($_POST["ref"]) && $_POST["ref"] == 'on') {
        $ref = '1';
    }

    $medecontractant = '0';
    if (isset($_POST["contractant"]) && $_POST["contractant"] == 'on') {
        $medecontractant = '1';
    }

    // berekenen van de looptijd
    $looptijd = 0;
    if (isset($_POST["looptijd_jaar"]) && isset($_POST["looptijd_maand"])) {
        $looptijd = ( $_POST["looptijd_jaar"] * 12 ) + $_POST["looptijd_maand"];
    }

    $schaduw = '0';
    if (isset($_POST["schaduw"]) && $_POST["schaduw"] == 'on') {
        $schaduw = '1';
    }

    $schaduw_w = '0';
    if (isset($_POST["winter"]) && $_POST["winter"] == 'on') {
        $schaduw_w = '1';
    }

    $schaduw_z = '0';
    if (isset($_POST["zomer"]) && $_POST["zomer"] == 'on') {
        $schaduw_z = '1';
    }

    $schaduw_lh = '0';
    if (isset($_POST["lente_herfst"]) && $_POST["lente_herfst"] == 'on') {
        $schaduw_lh = '1';
    }

    $overschrijving = '0';
    if (isset($_POST["overschrijving"])) {
        $overschrijving = '1';
    }

    if (isset($_POST['ean1'])) {
        $_POST['ean'] = $_POST['ean1'];
    }

    $_POST["offerte_datum"] = changeDate2EU($_POST["offerte_datum"]);

    // begin nakijken wie welke velden heeft aangepast
    $mapping = array();

    if ($_SESSION[ $session_var ]->group_id == 5) {
        // Engineering
        $mapping["cus_ingetekend"] = htmlentities($_POST["ingetekend"], ENT_QUOTES);
        $mapping["cus_werk_aant_panelen"] = $_POST["werk_aant_panelen"];
        $mapping["cus_werk_w_panelen"] = $_POST["werk_w_panelen"];
        $mapping["cus_werk_merk_panelen"] = $_POST["werk_merk_panelen"];
        $mapping["cus_werk_aant_omvormers"] = $_POST["werk_aant_omvormers"];
        $mapping["cus_werkdoc_door"] = $_POST["werkdocument_door"];
        $mapping["cus_werkdoc_klaar"] = $_POST["werkdocument_klaar"];
        $mapping["cus_werkdoc_opm"] = $_POST["werkdoc_opm"];
        $mapping["cus_werkdoc_opm2"] = $_POST["cus_werkdoc_opm2"];
        $mapping["cus_ac_vermogen"] = $_POST["ac_vermogen"];
    } else {
        $mapping["cus_naam"] = htmlentities($_POST["naam"], ENT_QUOTES);
        $mapping["cus_bedrijf"] = htmlentities($_POST["bedrijf"], ENT_QUOTES);
        $mapping["cus_btw"] = $_POST["btw_edit"];
        $mapping["cus_straat"] = htmlentities($_POST["straat"], ENT_QUOTES);
        $mapping["cus_nr"] = $_POST["nr"];
        $mapping["cus_postcode"] = $_POST["postcode"];
        $mapping["cus_gemeente"] = htmlentities($_POST["gemeente"], ENT_QUOTES);
        $mapping["cus_land_id"] = htmlentities($_POST["land"], ENT_QUOTES);
//	$mapping["cus_email"] = $_POST["email"];
//      $mapping["cus_email_verslag"] = $_POST["mail_verslag"];
        // TEL HTML entities fout
//	$mapping["cus_tel"] = $_POST["tel"];
//        $mapping["cus_gsm"] = $_POST["gsm"];
        $mapping["cus_acma"] = $_POST["acma"];
        $mapping["cus_fac_adres"] = $fac_adres;
        $mapping["cus_fac_naam"] = htmlentities($_POST["fac_naam"], ENT_QUOTES);
        $mapping["cus_fac_straat"] = htmlentities($_POST["fac_straat"], ENT_QUOTES);
        $mapping["cus_fac_nr"] = $_POST["fac_nr"];
        $mapping["cus_fac_postcode"] = $_POST["fac_postcode"];
        $mapping["cus_fac_gemeente"] = htmlentities($_POST["fac_gemeente"], ENT_QUOTES);
        $mapping["cus_fac_land_id"] = htmlentities($_POST["fac_land"], ENT_QUOTES);
        $mapping["cus_offerte_datum"] = $_POST["offerte_datum"];
//	$mapping["cus_kwhkwp"] = $_POST["kwhkwp"];
      //$mapping["cus_bedrag_excl"] = $_POST["bedrag_excl"];
        $mapping["cus_sunnybeam"] = $set_sunny;
//	$mapping["cus_werk_aant_panelen"] = $_POST["werk_aant_panelen"];
//	$mapping["cus_werk_w_panelen"] = $_POST["werk_w_panelen"];
        $mapping["cus_opmerkingen"] = htmlentities($_POST["opmerkingen"], ENT_QUOTES);
//	$mapping["cus_arei_datum"] = $_POST["datum_arei"];
//	$mapping["cus_vreg_datum"] = $_POST["datum_vreg"];
//      $mapping["cus_vreg_un"] = $_POST["vreg_un"];
//      $mapping["cus_vreg_pwd"] = $_POST["vreg_pwd"];
//	$mapping["cus_datum_net"] = $_POST["datum_net"];
//	$mapping["cus_pvz"] = strtoupper( $_POST["pvz"] );
//	$mapping["cus_ean"] = $_POST["ean"];
//        $mapping["cus_reknr"] = $_POST["reknr"];
//        $mapping["cus_iban"] = $_POST["iban"];
//        $mapping["cus_bic"] = $_POST["bic"];
//        $mapping["cus_banknaam"] = $_POST["banknaam"];
//	$mapping["cus_gemeentepremie"] = $_POST["gem_premie"];
//	$mapping["cus_bouwvergunning"] = $_POST["bouwver"];
        $mapping["cus_ref"] = $ref;
      //$mapping["cus_looptijd_huur"] = $looptijd;
        $mapping["cus_huur_doc"] = $set_huur_doc;
        $mapping["cus_schaduw"] = $schaduw;
        $mapping["cus_schaduw_w"] = $schaduw_w;
        $mapping["cus_schaduw_z"] = $schaduw_z;
        $mapping["cus_schaduw_lh"] = $schaduw_lh;
        $mapping["cus_overschrijving"] = $overschrijving;
//      $mapping["cus_indienst"] = $_POST["datum_indienst"];
        $mapping["cus_int_boiler"] = $int_boiler;
        $mapping["cus_int_mon"] = $int_mon;
        $mapping["cus_int_pid"] = $int_pid;
        $mapping["cus_int_solar"] = $int_solar;
        $mapping["cus_int_iso"] = $int_iso;

        if ($_POST["kent"] == "") {
            $_POST["kent"] = 0;
        }

        $mapping["cus_kent_ons_van"] = $_POST["kent"];
    }

    if (!isset($_POST["bet_termijn"])) {
        $_POST["bet_termijn"] = $klant_old_data->cus_bet_termijn;
        $mapping["cus_bet_termijn"] = $_POST["bet_termijn"];
    }

    foreach ($mapping as $field => $new_value) {
        if ($new_value == "--") {
            $new_value = "";
        }

        if ($klant_old_data->$field == "--") {
            $klant_old_data->$field = "";
        }

        //echo "<br>" . $klant_old_data->$field . "!=" . $new_value;

        if ($klant_old_data->$field != $new_value) {
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, $field, $klant_old_data->$field, $new_value, $conn);
        }
    }
//    var_dump($klant_old_data);
    // einde nakijken wie welke velden heeft aangepast

    if ($_SESSION[ $session_var ]->group_id == 5) {
        $q_upd = "UPDATE kal_customers SET 	cus_ingetekend = '" . htmlentities($_POST["ingetekend"], ENT_QUOTES) . "',
		                               		cus_werkdoc_door = '" . $_POST["werkdocument_door"] . "',
		                               		cus_werkdoc_klaar = '" . $_POST["werkdocument_klaar"] . "',
		                               		cus_werkdoc_opm = '" . $_POST["werkdoc_opm"] . "',
		                               		cus_werkdoc_opm2 = '" . $_POST["werkdoc_opm2"] . "',
		                               		cus_werk_aant_panelen = '" . $_POST["werk_aant_panelen"] . "',
		                               		cus_werk_w_panelen = '" . $_POST["werk_w_panelen"] . "',
		                               		cus_werk_merk_panelen = '" . $_POST["werk_merk_panelen"] . "',
		                               		cus_werk_aant_omvormers = '" . $_POST["werk_aant_omvormers"] . "',
		                               		cus_ac_vermogen = '" . $_POST["ac_vermogen"] . "',
		                               		cus_type_omvormers = '" . $werk_omvormers . "'
		                              WHERE cus_id = " . $_POST["cus_id"];
    } else {
        /* Als emailadres wordt gewijzig dan de koppeling naar futech.be ook wijzigen */
//        if( $klant_old_data->cus_email != $_POST["email"] )
//        {
//            $qq_zoek = mysqli_query($conn, "SELECT * FROM UserData WHERE UserEmail = '". $_POST["email"] ."'") or die( mysqli_error($conn) . " " . __LINE__ );
//            $q_zoek = mysqli_num_rows($qq_zoek);
//            
//            if( $q_zoek == 0 )
//            {
//                $q_upd = "UPDATE UserData SET UserEmail = '" . $_POST["email"] . "' WHERE UserEmail = '" . $klant_old_data->cus_email . "'";
//                mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . __LINE__ );
//            }else
//            {
//                echo "Het emailadres bestaat al";
//            }
//            
//            // Mijn futech, aanp�ssen van het emailadres in de tabel groepen
//            // groepen van installaties
//            if( !isset( $_POST["email_indi"] ) )
//            {
//                if( !empty($klant_old_data->cus_email) )
//                {
//                    $q_upd = "UPDATE kal_group_pvz SET email = '". $_POST["email"] ."' WHERE email = '" . $klant_old_data->cus_email . "'";
//                    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . __LINE__ );
//                    
//                    // al klanten wijzigen met dit emailadres
//                    $q_upd = "UPDATE kal_customers SET cus_email = '". $_POST["email"] ."' WHERE cus_email = '".$klant_old_data->cus_email."'";
//                    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . __LINE__ );
//                }
//            }
//        }
        /* Einde - Als emailadres wordt gewijzig dan de koppeling naar futech.be ook wijzigen */

        if (!empty($_POST["vreg_pwd"]) && !empty($_POST["vreg_un"])) {
            $q_upd = mysqli_query($conn, "UPDATE kal_customers SET cus_vreg_pwd = '" . $_POST["vreg_pwd"] . "' WHERE cus_vreg_un = '" . $_POST["vreg_un"] . "'") or die(mysqli_error($conn) . " " . __LINE__);
        }
        // test in_oa_van waarde
        $test_oa = "";
        // als waarde geset, dan waarde gelijk aan postwaarde
        if (isset($_POST['in_oa_van']))
            $test_oa = $_POST['in_oa_van'];
        // AAAAAAAAAAAAAA
        // UPDATE database
        // // TEL AAAAA
        // cccccccc
        $q_upd = "UPDATE kal_customers SET 	cus_naam = '" . htmlentities($_POST["naam"], ENT_QUOTES) . "',
				                            cus_bedrijf = '" . htmlentities($_POST["bedrijf"], ENT_QUOTES) . "',
                                            cus_school = '" . htmlentities($_POST["school"], ENT_QUOTES) . "',
                                            cus_contact1 = '" . htmlentities($_POST["contact"], ENT_QUOTES) . "',
                                            cus_btw = '" . $_POST["btw_edit"] . "', 
                                            cus_btw_prive = '" . $_POST["btw_prive"] . "',
                                            cus_btw_bedrijf = '" . $_POST["btw_beroeps"] . "',
                                            cus_medecontractor = '" . $medecontractant . "',
                                            cus_oa = '" . $in_oa . "',
                                            cus_oa_bij = '" . $test_oa . "',
                                            cus_straat = '" . htmlentities($_POST["straat"], ENT_QUOTES) . "',
                                            cus_nr = '" . $_POST["nr"] . "',
                                            cus_postcode = '" . $_POST["postcode"] . "',
                                            cus_gemeente = '" . htmlentities($_POST["gemeente"], ENT_QUOTES) . "',
                                            cus_land_id = '" . htmlentities($_POST["land"], ENT_QUOTES) . "',    
                                            cus_kent_ons_van = '" . $_POST["kent"] . "',
                                            cus_acma = '" . $_POST["acma"] . "',
                                            cus_fac_adres = '" . $fac_adres . "',
                                            cus_10 = '" . $cus_10 . "',
                                            cus_fac_naam = '" . htmlentities($_POST["fac_naam"], ENT_QUOTES) . "',
                                            cus_fac_straat = '" . htmlentities($_POST["fac_straat"], ENT_QUOTES) . "',
                                            cus_fac_nr = '" . $_POST["fac_nr"] . "',
                                            cus_fac_postcode = '" . $_POST["fac_postcode"] . "',
                                            cus_fac_gemeente = '" . htmlentities($_POST["fac_gemeente"], ENT_QUOTES) . "',
                                            cus_fac_land_id = '" . htmlentities($_POST["fac_land"], ENT_QUOTES) . "',
                                            cus_offerte_datum = '" . $_POST["offerte_datum"] . "',
                                            cus_opmerkingen = '" . htmlentities($_POST["opmerkingen"], ENT_QUOTES) . "',
                                            cus_bet_termijn = '" . $_POST["bet_termijn"] . "',
                                            cus_ref = '" . $ref . "', 
                                            cus_ref_lengte = '" . $_POST["lengte"] . "',
                                            cus_ref_breedte = '" . $_POST["breedte"] . "',
                                            cus_int_solar = '" . $int_solar . "',
                                            cus_int_iso = '" . $int_iso . "',
                                            cus_int_boiler = '" . $int_boiler . "',
                                            cus_int_mon = '" . $int_mon . "',
                                            cus_int_pid = '" . $int_pid . "',
                                            cus_email = '". $_POST["fac_mail"] ."'
                                            WHERE cus_id = " . $_POST["cus_id"];

//        echo $q_upd;
    }

    if (!empty($_POST["naam"]) || !empty($_POST["bedrijf"]) || $_SESSION[ $session_var ]->group_id == 5) {
        
        /*         * *** UPDATE kal_customers **** */
        mysqli_query($conn, $q_upd) or die(mysqli_error($conn) . " " . __LINE__);

        /*****  INSERT/UPDATE EMAIL INTO kal_customers_details *****/// 
        if (!empty($_POST["email"])) {
            $aantal_email = 0;
            $aantal_details = 0;
            $id_details = array();
            // sorteer array
            foreach($_POST["email"] as $tel => $value)
            {
                $exp_key = explode("_",$tel);
                if($exp_key[0] == 'details')
                {
                    // details_id
                    $id_details[] = $exp_key[1];
                    $aantal_details++;
                }
                $aantal_email++;
            }
            // als er emails zijn
            if($aantal_email > 0)
            {   
                // als er database records zijn dan update
                if($aantal_details > 0)
                {
                    $i = 0;
                    while($i < $aantal_details)
                    {
                        $waarde = "details_". $id_details[$i];
                        if($_POST["email"][$waarde] != '')
                        {
                            // get oude waarde
                            $q_email_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='".$id_details[$i]."'"));
                            if($q_email_log->waarde != $_POST["email"][$waarde])
                            {
                                // log als oude waarde != nieuwe waarde
                                mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_email','".$q_email_log->waarde."','" . $_POST["email"][$waarde] . "','".date("Y-m-d h:i:s")."')");                                
                            }
                            mysqli_query($conn, "UPDATE kal_customers_details SET waarde='".$_POST["email"][$waarde]."' WHERE id='".$id_details[$i]."'");
                        }
                        $i++;
                    }
                }
                // als er meer email zijn dan details
                if($aantal_email > $aantal_details)
                {
                    $rest = ($aantal_email - $aantal_details);
                    for($j=0;$j<$rest;$j++)
                    {
                        if($_POST["email"][$j] != '')
                        {
                            echo "email_insert_log";
                            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_email','','" . $_POST["email"][$j] . "','".date("Y-m-d h:i:s")."')");
                            mysqli_query($conn, "INSERT INTO kal_customers_details (cus_id,waarde,soort) VALUES ('" . $_POST["cus_id"] . "','" . $_POST["email"][$j] . "','3')");
                        }
                    }
                }
            }
        }
       /*****  INSERT/UPDATE GSM INTO kal_customers_details *****/// 
        if (!empty($_POST["gsm"])) {
            $aantal_gsms = 0;
            $aantal_details = 0;
            $id_details = array();
            // sorteer array
            foreach($_POST["gsm"] as $tel => $value)
            {
                $exp_key = explode("_",$tel);
                if($exp_key[0] == 'details')
                {
                    // details_id
                    $id_details[] = $exp_key[1];
                    $aantal_details++;
                }
                $aantal_gsms++;
            }
            // als er gsms zijn
            if($aantal_gsms > 0)
            {   
                // als er database records zijn dan update
                if($aantal_details > 0)
                {
                    $i = 0;
                    while($i < $aantal_details)
                    {
                        $waarde = "details_". $id_details[$i];
                        if($_POST["gsm"][$waarde] != '')
                        {
                            // get oude waarde
                            $q_gsm_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='".$id_details[$i]."'"));
                            if($q_gsm_log->waarde != $_POST["gsm"][$waarde])
                            {
                                // log als oude waarde != nieuwe waarde
                                mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_gsm','".$q_gsm_log->waarde."','" . $_POST["gsm"][$waarde] . "','".date("Y-m-d h:i:s")."')");                                
                            }
                            mysqli_query($conn, "UPDATE kal_customers_details SET waarde='".$_POST["gsm"][$waarde]."' WHERE id='".$id_details[$i]."'");
                        }
                        $i++;
                    }
                }
                // als er meer gsms zijn dan details
                if($aantal_gsms > $aantal_details)
                {
                    $rest = ($aantal_gsms - $aantal_details);
                    for($j=0;$j<$rest;$j++)
                    {
                        if($_POST["gsm"][$j] != '')
                        {
                            // log nieuwe waarde
                            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_gsm','','" . $_POST["gsm"][$j] . "','".date("Y-m-d h:i:s")."')");
                            mysqli_query($conn, "INSERT INTO kal_customers_details (cus_id,waarde,soort) VALUES ('" . $_POST["cus_id"] . "','" . $_POST["gsm"][$j] . "','2')");
                        }
                    }
                }
            }
        }        
        
        
        /*****  INSERT/UPDATE TELEFOON INTO kal_customers_details **** */
        if (!empty($_POST["tel"])) {
            $aantal_telefoons = 0;
            $aantal_details = 0;
            $id_details = array();
            // sorteer array
            foreach($_POST["tel"] as $tel => $value)
            {
                $exp_key = explode("_",$tel);
                if($exp_key[0] == 'details')
                {
                    // details_id
                    $id_details[] = $exp_key[1];
                    $aantal_details++;
                }
                $aantal_telefoons++;
            }
            // als er telefoons zijn
            if($aantal_telefoons > 0)
            {   
                // als er database records zijn dan update
                if($aantal_details > 0)
                {
                    $i = 0;
                    while($i < $aantal_details)
                    {
                        $waarde = "details_". $id_details[$i];
                        if($_POST["tel"][$waarde] != '')
                        {
                            // get oude waarde
                            $q_tel_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='".$id_details[$i]."'"));
                            if($q_tel_log->waarde != $_POST["tel"][$waarde])
                            {
                                // log als oude waarde != nieuwe waarde
                                mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_tel','".$q_tel_log->waarde."','" . $_POST["tel"][$waarde] . "','".date("Y-m-d h:i:s")."')");                                
                            }
                            // update
                            mysqli_query($conn, "UPDATE kal_customers_details SET waarde='".$_POST["tel"][$waarde]."' WHERE id='".$id_details[$i]."'");
                        }
                        $i++;
                    }
                }
                // als er meer telefoons zijn dan details
                if($aantal_telefoons > $aantal_details)
                {
                    $rest = ($aantal_telefoons - $aantal_details);
                    for($j=0;$j<$rest;$j++)
                    {
                        if($_POST["tel"][$j] != '')
                        {
                            // log nieuwe waarde
                            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_tel','','" . $_POST["tel"][$j] . "','".date("Y-m-d h:i:s")."')");
                            mysqli_query($conn, "INSERT INTO kal_customers_details (cus_id,waarde,soort) VALUES ('" . $_POST["cus_id"] . "','" . $_POST["tel"][$j] . "','1')");
                        }
                    }
                }
            }
        }
        
        
        
        /*         * ***  INSERT BANK INTO kal_customers_reknr **** */
        if (!empty($_POST["banknaam"]) && !empty($_POST["iban"]) && !empty($_POST["bic"])) {
            // log bank
            // banknaam, bank iban, bank bic, bank reknr
            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_banknaam','','" . $_POST["banknaam"] . "','".date("Y-m-d h:i:s")."')");
            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_iban','','" . $_POST["iban"] . "','".date("Y-m-d h:i:s")."')");
            mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_bic','','" . $_POST["bic"] . "','".date("Y-m-d h:i:s")."')");
            if($_POST["reknr"] != '')
            {
                mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $_POST["cus_id"] . "','".$_SESSION[ $session_var ]->user_id."','cus_reknr','','" . $_POST["reknr"] . "','".date("Y-m-d h:i:s")."')");               
            }
            $q_upd_tel = "INSERT INTO kal_customers_reknr (cus_id,bank_naam,bank_iban,bank_bic,bank_reknr) VALUES ('" . $_POST["cus_id"] . "','" . $_POST["banknaam"] . "','" . $_POST["iban"] . "','" . $_POST["bic"] . "','" . $_POST["reknr"] . "')";
            mysqli_query($conn, $q_upd_tel) or die(mysqli_error($conn) . " " . __LINE__);
        }

        // begin delete van de blobs
        if (isset($_POST["order_del"])) {
            $q_upd2 = "UPDATE kal_customers SET cus_order_file = '',
		                               			cus_order_filename = ''
		                              	WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd2) or die(mysqli_error($conn) . "-2");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_order_filename", $klant_old_data->cus_order_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $klant_old_data->cus_order_filename);
        }

        if (isset($_POST["werkdoc_del"])) {
            $q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '',
		                               			cus_werkdoc_filename = ''
		                             	 WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd3) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_filename", $klant_old_data->cus_werkdoc_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/" . $klant_old_data->cus_werkdoc_filename);
        }

        if (isset($_POST["areidoc_del"])) {
            $q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '',
		                               			cus_areidoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd4) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_areidoc_filename", $klant_old_data->cus_areidoc_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_arei/" . $klant_old_data->cus_areidoc_filename);
        }

        if (isset($_POST["gemeentedoc_del"])) {
            $q_upd5 = "UPDATE kal_customers SET cus_gemeentedoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd5) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_gemeentedoc_filename", $klant_old_data->cus_gemeentedoc_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_gemeente/" . $klant_old_data->cus_gemeentedoc_filename);
        }

        if (isset($_POST["bouwverdoc_del"])) {
            $q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd5) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_bouwvergunning_filename", $klant_old_data->cus_bouwvergunning_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_bouw/" . $klant_old_data->cus_bouwvergunning_filename);
        }

        if (isset($_POST["stringdoc_del"])) {
            $q_upd6 = "UPDATE kal_customers SET cus_stringdoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_stringdoc_filename", $klant_old_data->cus_stringdoc_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_string/" . $klant_old_data->cus_stringdoc_filename);
        }

        if (isset($_POST["werkdocpic1_del"])) {
            $q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic1 = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_pic1", $klant_old_data->cus_werkdoc_pic1, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic1/" . $klant_old_data->cus_werkdoc_pic1);
        }

        if (isset($_POST["werkdocpic2_del"])) {
            $q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic2 = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_pic2", $klant_old_data->cus_werkdoc_pic2, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic2/" . $klant_old_data->cus_werkdoc_pic2);
        }

        if (isset($_POST["elecdoc_del"])) {
            $q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

            mysqli_query($conn, $q_upd4) or die(mysqli_error($conn) . "-3");

            // verwijderen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_elecdoc_filename", $klant_old_data->cus_elecdoc_filename, "", $conn);

            unlink("cus_docs/" . $_POST["cus_id"] . "/doc_elec/" . $klant_old_data->cus_elecdoc_filename);
        }
        // EINDE DELETE VAN DE FILES

        if (!empty($_FILES["orderbon"]["name"])) {
            $q_upd2 = "UPDATE kal_customers SET cus_order_file = '" . $order_file . "',
		                               		cus_order_filename = '" . $order_filename . "'
		                              WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_order_filename", $klant_old_data->cus_order_filename, $order_filename, $conn);

            mysqli_query($conn, $q_upd2) or die(mysqli_error($conn) . "-2");
        }

        if (!empty($_FILES["werkdocument_file"]["name"])) {
            $q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '" . $werkdoc_file . "',
		                               		cus_werkdoc_filename = '" . $werkdoc_filename . "'
		                              WHERE cus_id = " . $_POST["cus_id"];


            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_filename", $klant_old_data->cus_werkdoc_filename, $werkdoc_filename, $conn);

            mysqli_query($conn, $q_upd3) or die(mysqli_error($conn) . "-3");
        }

        if (!empty($_FILES["doc_arei"]["name"])) {
            $q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '" . $areidoc_file . "',
		                               			cus_areidoc_filename = '" . $areidoc_filename . "'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_areidoc_filename", $klant_old_data->cus_areidoc_filename, $areidoc_filename, $conn);

            mysqli_query($conn, $q_upd4) or die(mysqli_error($conn) . "-4");
        }

        if (!empty($_FILES["doc_gemeente"]["name"])) {
            $q_upd4 = "UPDATE kal_customers SET cus_gemeentedoc_filename = '" . $gemeentedoc_filename . "'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_gemeentedoc_filename", $klant_old_data->cus_gemeentedoc_filename, $gemeentedoc_filename, $conn);

            mysqli_query($conn, $q_upd4) or die(mysqli_error($conn) . "-5");
        }

        if (!empty($_FILES["doc_bouwver"]["name"])) {
            $q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = '" . $bouwdoc_filename . "'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_bouwvergunning_filename", $klant_old_data->cus_bouwvergunning_filename, $bouwdoc_filename, $conn);

            mysqli_query($conn, $q_upd5) or die(mysqli_error($conn) . "-5");
        }

        if (!empty($_FILES["doc_string"]["name"])) {
            $q_upd6 = "UPDATE kal_customers
			              SET cus_stringdoc_filename  = '" . $stringdoc_filename . "'
		                WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_stringdoc_filename", $klant_old_data->cus_stringdoc_filename, $stringdoc_filename, $conn);

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-5");
        }

        if (!empty($_FILES["werkdoc_pic1"]["name"])) {
            $q_upd6 = "UPDATE kal_customers
			              SET cus_werkdoc_pic1  = '" . $werkdoc_filename1 . "'
		                WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_pic1", $klant_old_data->cus_werkdoc_pic1, $werkdoc_filename1, $conn);

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-5");
        }

        if (!empty($_FILES["werkdoc_pic2"]["name"])) {
            $q_upd6 = "UPDATE kal_customers
			              SET cus_werkdoc_pic2  = '" . $werkdoc_filename2 . "'
		                WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_werkdoc_pic2", $klant_old_data->cus_werkdoc_pic2, $werkdoc_filename2, $conn);

            mysqli_query($conn, $q_upd6) or die(mysqli_error($conn) . "-5");
        }

        if (!empty($_FILES["doc_elec"]["name"])) {
            $q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = '" . $elecdoc_filename . "'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

            // toevoegen loggen
            customersLog($_POST["cus_id"], $_SESSION[ $session_var ]->user_id, "cus_elecdoc_filename", $klant_old_data->cus_elecdoc_filename, $elecdoc_filename, $conn);

            mysqli_query($conn, $q_upd4) or die(mysqli_error($conn) . "-4");
        }
    }
}

?>