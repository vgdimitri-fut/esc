<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";

/* * ***    DELETE TELEFOON    **** */
if (isset($_POST['details_id']) && !empty($_POST['details_id']) && $_POST["soort"] == '1') {
    $q_tel_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='" . $_POST["details_id"] . "'"));
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_tel_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_tel','" . $q_tel_log->waarde . "','','" . date("Y-m-d h:i:s") . "')");
    mysqli_query($conn, "DELETE FROM kal_customers_details WHERE id='" . $_POST['details_id'] . "'");
    echo $_POST['details_id'];
    die();
}
/* * ***    DELETE GSM    **** */
if (isset($_POST['details_id']) && !empty($_POST['details_id']) && $_POST["soort"] == '2') {
    $q_gsm_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='" . $_POST["details_id"] . "'"));
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_gsm_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_gsm','" . $q_gsm_log->waarde . "','','" . date("Y-m-d h:i:s") . "')");
    mysqli_query($conn, "DELETE FROM kal_customers_details WHERE id='" . $_POST['details_id'] . "'");
    echo $_POST['details_id'];
    die();
}
/* * ***    DELETE EMAIL    **** */
if (isset($_POST['details_id']) && !empty($_POST['details_id']) && $_POST["soort"] == '3') {
    // get cus_id en waarde
    $q_email_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_details WHERE id='" . $_POST["details_id"] . "'"));
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_email_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_email','" . $q_email_log->waarde . "','','" . date("Y-m-d h:i:s") . "')");
    mysqli_query($conn, "DELETE FROM kal_customers_details WHERE id='" . $_POST['details_id'] . "'");
    echo $_POST['details_id'];
    die();
}
/* * ***    DELETE BANK   **** */
if (isset($_POST['bank_id']) && !empty($_POST["bank_id"])) {
    // get bank details
    $q_bank_log = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_reknr WHERE id='" . $_POST["bank_id"] . "'"));
    // log delete banknaam, iban, bic, reknr
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_bank_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_banknaam','" . $q_bank_log->bank_naam . "','','" . date("Y-m-d h:i:s") . "')");
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_bank_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_iban','" . $q_bank_log->bank_iban . "','','" . date("Y-m-d h:i:s") . "')");
    mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_bank_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_bic','" . $q_bank_log->bank_bic . "','','" . date("Y-m-d h:i:s") . "')");
    if ($q_bank_log->bank_reknr != '') {
        mysqli_query($conn, "INSERT INTO kal_customers_log (cl_cus_id,cl_wie,cl_veld,cl_van,cl_naar,cl_datetime) VALUES ('" . $q_bank_log->cus_id . "','" . $_SESSION["tcc_user"]->user_id . "','cus_reknr','" . $q_bank_log->bank_reknr . "','','" . date("Y-m-d h:i:s") . "')");
    }
    mysqli_query($conn, "DELETE FROM kal_customers_reknr WHERE id='" . $_POST['bank_id'] . "'");
    echo $_POST["bank_iban"];
    die();
}

if (isset($_POST['status']) && $_POST['status'] == 'verkoop') {
    include "../inc/db_car.php";
    $get_products = mysqli_query($conn_car, "SELECT * FROM tbl_product_values WHERE product_fields_id=67 AND value=39");
    while ($products = mysqli_fetch_object($get_products)) {
        $q = mysqli_query($conn_car, "SELECT * FROM tbl_products WHERE id=" . $products->product_id);
        $get_product = mysqli_fetch_object($q);
        echo "<option value='" . $get_product->id . "'>" . $get_product->name . " (Te koop)</option>";
    }
    die();
}

if (isset($_POST['status']) && $_POST['status'] == 'aankoop') {
    $get_products = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=67 AND (value=37 OR value=38) ORDER BY value ASC");
    while ($products = mysqli_fetch_object($get_products)) {
        $get_product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=" . $products->product_id));
        if($products->value == 37){
            echo "<option value='" . $get_product->id . "'>" . $get_product->name . " (Aangekocht)</option>";
        }else{
            echo "<option value='" . $get_product->id . "'>" . $get_product->name . " (Verkocht)</option>";
        }
    }
    die();
}

if (isset($_POST['transactie_id']) && $_POST['action'] == 'getList') {
    $trans_id = $_POST['transactie_id'];
    $get_transactie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=" . $trans_id));
    $get_product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=" . $get_transactie->prod_id));
    $brand = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=" . $get_product->product_brand_id));
    $model = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=" . $get_product->product_model_id));
    $j = 0;
    $j++;
    $kleur = $kleur_grijs;
    if ($j % 2) {
        $kleur = "white";
    }
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td width='100'>";
    echo "<label>Merk: </label>";
    echo "</td>";
    echo "<td width='200'>";
    echo $brand->naam;
    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>Model: </label>";
    echo "</td>";
    echo "<td>";
    echo $model->naam;
    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>Naam: </label>";
    echo "</td>";
    echo "<td>";
    echo truncate($get_product->name, 40);
    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>Prijs exclusief: </label>";
    echo "</td>";
    echo "<td>";
    echo $get_transactie->prijs_excl . "&euro;";
    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>BTW: </label>";
    echo "</td>";
    echo "<td>";
    switch ($get_transactie->btw) {
        case 0:
            echo 0 . "&#37;";
            break;
        case 1:
            echo 6 . "&#37;";
            break;
        case 2:
            echo 21 . "&#37;";
            break;
    }

    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>Prijs inclusief: </label>";
    echo "</td>";
    echo "<td>";
    echo $get_transactie->prijs_incl . "&euro;";
    echo "</td>";
    echo "</tr>";
    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
    echo "<td>";
    echo "<label>Datum: </label>";
    echo "</td>";
    echo "<td>";
    echo $get_transactie->datum;
    echo "</td>";
    echo "</tr>";
    $get_documents = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort='transactie' AND cf_soort_id=" . $trans_id);
    if (mysqli_num_rows($get_documents) != 0) {
        while ($document = mysqli_fetch_object($get_documents)) {
            echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
            echo "<td>";
            echo "<label>Document: </label>";
            echo "</td>";
            echo "<td>";
            if ($get_transactie->soort == '1') {
                echo "<a href='../beheer/lev_docs/" . $get_transactie->soort_id . "/transactie/" . $trans_id . "/" . $document->cf_file . "' target='_blank'>" . $document->cf_file . "</a>";
            } else {
                echo "<a href='../beheer/cus_docs/" . $get_transactie->soort_id . "/transactie/" . $trans_id . "/" . $document->cf_file . "' target='_blank'>" . $document->cf_file . "</a>";
            }

            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
        echo "<td colspan='2'>";
        echo "<label>Er is geen document.</label>";
        echo "</td>";
        echo "</tr>";
    }
    die();
}

if (isset($_POST['transactie_id']) && $_POST['action'] == 'del_cus') {
    $transactie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=" . $_POST['transactie_id']));
    
    // update product back to 'Te koop'
    mysqli_query($conn, "UPDATE tbl_product_values SET value=39 WHERE product_id=".$transactie->prod_id." AND product_fields_id=67");
    
    chdir('../cus_docs/' . $transactie->soort_id . '/transactie/');
    // verwijder folder
    delFolder($_POST['transactie_id'] . '/');
    if($transactie->factuur_id != ''  && $transactie->factuur_id != 0){
        $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=".$transactie->factuur_id));
        $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
        while($boekjaar = mysqli_fetch_object($q_boekjaren)){
            if($factuur->cf_date > $boekjaar->boekjaar_start && $factuur->cf_date <= $boekjaar->boekjaar_einde){
                $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
            }
        }
        chdir('../');
        chdir('factuur/'.$dir.'/');
        unlink($factuur->cf_file);
        chdir('../');
    }
  
    // ga terug naar beheer
    chdir('../../../');
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_soort='transactie' AND cf_soort_id=" . $_POST['transactie_id']);
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id=".$transactie->factuur_id);
    mysqli_query($conn, "DELETE FROM tbl_transacties WHERE id=" . $_POST['transactie_id']);
    die();
}
if (isset($_POST['transactie_id']) && $_POST['action'] == 'del_lev') {
    $transactie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=" . $_POST['transactie_id']));
  
    chdir('../lev_docs/' . $transactie->soort_id . '/transactie/');
    // verwijder folder
    delFolder($_POST['transactie_id'] . '/');
    if($transactie->factuur_id != '' && $transactie->factuur_id != 0){
        $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=".$transactie->factuur_id));
        $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
        while($boekjaar = mysqli_fetch_object($q_boekjaren)){
            if($factuur->cf_date > $boekjaar->boekjaar_start){
                $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
            }
        }
        chdir('../');
        chdir('factuur/'.$dir.'/');
        delFolder($factuur->cf_file);
        chdir('../');
    }
  
    // ga terug naar beheer
    chdir('../../../');
    
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_soort='transactie' AND cf_soort_id=" . $_POST['transactie_id']);
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id=".$transactie->factuur_id);
    mysqli_query($conn, "DELETE FROM tbl_transacties WHERE id=" . $_POST['transactie_id']);
     die();
}

function delFolder($dir) {
    $files = glob($dir . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (substr($file, -1) == '/')
            delTree($file);
        else
            unlink($file);
    }
    rmdir($dir);
}

$_GET["q"] = htmlentities($_GET["q"], ENT_QUOTES, "UTF-8");
/*
  if( isset( $_GET["onder"] ) && $_GET["onder"] == 1 )
  {
  $sql = "SELECT cus_naam as klant, kal_customers.cus_id
  FROM kal_customers
  LEFT JOIN kal_customer_boiler ON ( kal_customers.cus_id = kal_customer_boiler.cus_id )
  WHERE cus_active='1' AND cus_naam LIKE '%". $_GET["q"] ."%' AND (kal_customers.cus_verkoop = '1' OR kal_customers.cus_verkoop = '2' OR kal_customer_boiler.cus_verkoop = '1')
  UNION
  SELECT cus_bedrijf as klant, kal_customers.cus_id
  FROM kal_customers
  LEFT JOIN kal_customer_boiler ON ( kal_customers.cus_id = kal_customer_boiler.cus_id )
  WHERE cus_active='1' AND cus_bedrijf LIKE '%". $_GET["q"] ."%' AND (kal_customers.cus_verkoop = '1' OR kal_customers.cus_verkoop = '2' OR kal_customer_boiler.cus_verkoop = '1')
  ORDER BY klant
  ";
  //echo $sql;
  }else
  { */
$sql = "SELECT cus_naam as klant, cus_id 
                FROM kal_customers WHERE cus_active='1' AND 
                cus_naam LIKE '%" . $_GET["q"] . "%'
            UNION
                SELECT cus_bedrijf as klant, cus_id 
                FROM kal_customers WHERE cus_active='1' AND 
                cus_bedrijf LIKE '%" . $_GET["q"] . "%'
            ORDER BY klant
                ";
//}
//echo $sql;
//$sql = "SELECT CONCAT(cus_naam + ', ' + cus_bedrijf) as klant, cus_id FROM kal_customers WHERE klant LIKE '%". $_GET["q"] ."%'";

$rsd = mysqli_query($conn, $sql);

while ($rs = mysqli_fetch_array($rsd)) {
    $cid = $rs['cus_id'];
    $cname = html_entity_decode($rs['klant'], ENT_QUOTES, "UTF-8");
    echo "$cname|$cid\n";
}
?>