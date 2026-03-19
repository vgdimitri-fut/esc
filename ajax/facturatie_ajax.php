<?php

include "../inc/db.php";
include "../inc/functions.php";

if (isset($_POST['fac_id']) && $_POST['action'] == 'fac_detail') {
    $factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=" . $_POST['fac_id']));
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id=" . $factuur->cf_soort_id));
    $transactie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE factuur_id=" . $factuur->cf_id));
    $product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=" . $transactie->prod_id));
    $brand = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=" . $product->product_brand_id));
    $model = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=" . $product->product_model_id));
    $document = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE soort='transactie' AND soort_id=" . $transactie->id);

    echo "<tr style='cursor: pointer;'>";
    echo "<td><label>Verkoop datum: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . changeDate2EU($transactie->datum) . "</td>";
    echo "</tr>";
    echo "<tr style='cursor: pointer;'>";
    echo "<td width='170'><label>Product naaam: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $product->name . "</td>";
    echo "</tr>";
    echo "<tr style='cursor: pointer;'>";
    echo "<td width='170'><label>Merk: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $brand->naam . "</td>";
    echo "</tr>";
    echo "<tr style='cursor: pointer;'>";
    echo "<td width='170'><label>Model: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $model->naam . "</td>";
    echo "</tr>";
    if (mysqli_num_rows($document) != 0) {
        $doc = mysqli_fetch_object($document);
        echo "<tr style='cursor: pointer;'>";
        echo "<td><label>Factuur: </label></td>";
        echo "<td width='" . $breedte1 . "'>";
        echo "<a href='cus_docs/" . $klant->cus_id . "/transactie/" . $transactie->id . "/" . $doc->cf_file . "' target='_blank'>" . $doc->cf_file . "</a><br/>";
        echo "</td>";
        echo "</tr>";
    }
    echo "<tr style='cursor: pointer;'>";
    echo "<td><label>Prijs exclusief: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $factuur->cf_bedrag_excl . "&euro;</td>";
    echo "</tr>";
    echo "<tr style='cursor: pointer;'>";
    echo "<td><label>BTW: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $factuur->cf_btw . "%</td>";
    echo "</tr>";
    echo "<tr style='cursor: pointer;'>";
    echo "<td><label>Prijs inclusief: </label></td>";
    echo "<td width='" . $breedte1 . "'>" . $factuur->cf_bedrag . "&euro;</td>";
    echo "</tr>";
}

if (isset($_POST['fac_id']) && $_POST['action'] == 'fac_del') {
    /* transacties -> remove factuur id */
    $u_trans = mysqli_query($conn, "UPDATE tbl_transacties SET factuur_id='' WHERE factuur_id=".$_POST['fac_id']);
    
    /* customers_files -> select factuur */
    $s_fac = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=" . $_POST['fac_id']));
    
            /* get dir */
            $s_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
            while ($boekjaar = mysqli_fetch_object($s_boekjaren)) {
                if ($s_fac->cf_date > $boekjaar->boekjaar_start && $s_fac->cf_date <= $boekjaar->boekjaar_einde) {
                    $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
                }
            }
            /* delete pdf */
            chdir('../');
            chdir("cus_docs/" . $s_fac->cf_soort_id . "/factuur/".$dir."/");
            unlink($s_fac->cf_file);
            chdir("../../../../");
            chdir("facturen/".$dir."/");
            unlink($s_fac->cf_file);
            chdir("../../");
            
    /* customers_files -> remove factuur */
    $d_fac = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id=".$_POST['fac_id']);
}

if (isset($_POST['fac_id']) && $_POST['action'] == 'cn_del') {    
    /* customers_files -> select factuur */
    $s_fac = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=" . $_POST['fac_id']));
    
            /* get dir */
            $s_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
            while ($boekjaar = mysqli_fetch_object($s_boekjaren)) {
                if ($s_fac->cf_date > $boekjaar->boekjaar_start && $s_fac->cf_date <= $boekjaar->boekjaar_einde) {
                    $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
                }
            }
            /* delete pdf */
            chdir('../');
            chdir("cus_docs/" . $s_fac->cf_soort_id . "/creditnota/".$dir."/");
            unlink($s_fac->cf_file);
            chdir("../../../../");
            chdir("creditnota/".$dir."/");
            unlink($s_fac->cf_file);
            chdir("../../");
            
    /* customers_files -> remove factuur */
    $d_fac = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id=".$_POST['fac_id']);
}