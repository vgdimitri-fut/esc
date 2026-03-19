<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";



echo $_POST["naam"];
$where = "";
// Controleer referentie
if ($_POST["referte"] != "" && !empty($_POST["referte"])) {
    if (strlen($_POST["referte"]) > 6) {
        $where = " AND cus_id = '" . substr($_POST["referte"], 6) . "' ";
    } else {
        $where = " AND cus_id = '" . $_POST["referte"] . "' ";
    }
}
// Controleer naam
if ($_POST["naam"] != "" && !empty($_POST["naam"])) {
    $where = " AND cus_naam LIKE '%" . htmlentities($_POST["naam"],ENT_QUOTES) . "%' ";
}
// Controleer bedrijf
if (isset($_POST["bedrijf"]) && !empty($_POST["bedrijf"])) {
    $where .= " AND cus_bedrijf LIKE '%" . htmlentities($_POST["bedrijf"],ENT_QUOTES) . "%' ";
}
// Controleer straat
if (isset($_POST["straat"]) && !empty($_POST["straat"])) {
    $where .= " AND cus_straat LIKE '%" . htmlentities($_POST["straat"],ENT_QUOTES) . "%' ";
}
// Controleer nummer
if (isset($_POST["huisnummer"]) && !empty($_POST["huisnummer"])) {
    $where .= " AND cus_nr LIKE '%" . $_POST["huisnummer"] . "%' ";
}
// Controleer postcode
if (isset($_POST["postcode"]) && !empty($_POST["postcode"])) {
    $where .= " AND cus_postcode LIKE '%" . $_POST["postcode"] . "%' ";
}
// Controleer gemeente
if (isset($_POST["gemeente"]) && !empty($_POST["gemeente"])) {
    $where .= " AND cus_gemeente LIKE '%" . htmlentities($_POST["gemeente"],ENT_QUOTES) . "%' ";
}
// Controleer email
if (isset($_POST["email"]) && !empty($_POST["email"])) {
    $where .= " AND cus_email LIKE '%" . $_POST["email"] . "%' ";
}
// Controleer bank
if (isset($_POST["bank"]) && !empty($_POST["bank"])) {
    $where .= " AND cus_banknaam LIKE '%" . $_POST["bank"] . "%' ";
}
// Controleer telgsm
if (isset($_POST["telgsm"]) && !empty($_POST["telgsm"])) {
    $where .= " AND ( cus_tel LIKE '%" . $_POST["telgsm"] . "%' OR cus_gsm LIKE '%" . $_POST["telgsm"] . "%' ) ";
}
// Controleer pvz nr
if (isset($_POST["pvz"]) && !empty($_POST["pvz"])) {
    $where .= " AND cus_pvz LIKE '%" . $_POST["pvz"] . "%' ";
}
// Controleer mb nr
if (isset($_POST["mb"]) && !empty($_POST["mb"])) {
    $where .= " AND cus_vreg_un LIKE '%" . $_POST["mb"] . "%' ";
}
// sql query
$q_zzoek = "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' " . $where . " ORDER BY cus_naam";
$q_zoek = mysqli_query($conn, $q_zzoek) or die(mysqli_error($conn));

echo "<hr/>";

if (mysqli_num_rows($q_zoek) == 0) {
    echo "<br/><b>Geen gegevens gevonden.</b><br/><br/>";
} else {
    echo "<br/><b>Klanten gevonden : " . mysqli_num_rows($q_zoek) . "</b><br/><br/>";
}

echo "<table cellpadding='0' cellspacing='0' width='100%'>";

echo "<tr>";
echo "<td><b>Naam</b></td>";
echo "<td><b>Straat </b></td>";
echo "<td><b>Gemeente </b></td>";
echo "</tr>";

$i = 1;
while ($zklant = mysqli_fetch_object($q_zoek)) {
    $i++;

    $kleur = $kleur_grijs;
    if ($i % 2) {
        $kleur = "white";
    }

    echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");document.getElementById(\"cus_id1\").value=" . $zklant->cus_id . ";' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' >";
    echo "<td onclick='gotoKlant(" . $zklant->cus_id . ")'>";
    echo $zklant->cus_naam;
    echo "</td>";

    echo "<td onclick='gotoKlant(" . $zklant->cus_id . ")'>";
    echo $zklant->cus_straat . " " . $zklant->cus_nr;
    echo "</td>";

    echo "<td onclick='gotoKlant(" . $zklant->cus_id . ")'>";
    echo $zklant->cus_postcode . " " . $zklant->cus_gemeente;
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
?>