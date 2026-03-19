<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";


if (isset($_POST["verwerk"]) && $_POST["verwerk"] == 'Verwerk') {
    
//    echo "<pre>";
//    var_dump($_POST);
//    echo "</pre>";
//    
//    echo "<pre>";
//    var_dump($_SESSION);
//    echo "</pre>";
//    die();
    // ophalen van factuur
    $cn = creditnota("S");

    $file = $_SESSION["kalender_cn"]["cn_nr"] . ".pdf";

    // toevoegen bij de betalingen van deze klant
    $tmp_datum = explode("-", $_SESSION["kalender_cn"]["datum"]);
    $datum = $tmp_datum[2] . "-" . $tmp_datum[1] . "-" . $tmp_datum[0];

    $factuura = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id = " . $_SESSION["kalender_cn"]["klant_id"] . " AND cf_soort = 'factuur' AND cf_id = '" . $_SESSION["kalender_cn"]["factuur"] . "' "));

    // toevoegen bij de klant zelf
    $q_ins = "INSERT INTO kal_customers_files(cf_soort_id, 
                                              cf_soort, 
                                              cf_file, 
                                              cf_bedrag,
                                              cf_bedrag_excl,
                                              cf_date,
                                              cf_btw) 
                                      VALUES(" . $_SESSION["kalender_cn"]["klant_id"] . ",
                                             'creditnota',
                                             '" . $file . "',
                                             '" . $factuura->cf_bedrag . "',
                                             '". $factuura->cf_bedrag_excl . "',
                                             '" . $datum . "',
                                             '" . $factuura->cf_btw . "')";

    //echo $q_ins;

    mysqli_query($conn, $q_ins) or die(mysqli_error($conn));

//    $q_ins = "INSERT INTO kal_customers_payments(cp_cus_id, 
//	                                             cp_datum, 
//	                                             cp_bedrag, 
//	                                             cp_factuur,
//	                                             cp_opm ) 
//	                                      VALUES('" . $_SESSION["kalender_cn"]["klant_id"] . "',
//	                                             '" . $datum . "',
//	                                             '" . $_SESSION["kalender_cn"]["prijs"] . "',
//	                                             '" . $_SESSION["kalender_cn"]["factuur"] . "',
//	                                             'CN" . $_SESSION["kalender_cn"]["cn_nr"] . "') ";
//    mysqli_query($conn, $q_ins) or die(mysqli_error($conn));

    $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
    while($boekjaar = mysqli_fetch_object($q_boekjaren)){
        if($datum > $boekjaar->boekjaar_start && $datum <= $boekjaar->boekjaar_einde){
            $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
        }
    }

    // plaatsen op de juiste map op de server
    chdir("cus_docs/");
    @mkdir($_SESSION["kalender_cn"]["klant_id"]);
    chdir($_SESSION["kalender_cn"]["klant_id"]);
    @mkdir("creditnota");
    chdir("creditnota");
    @mkdir($dir);
    chdir($dir);
    $fp = fopen($file, 'w');
    fwrite($fp, $cn);
    fclose($fp);
    chdir("../../../../");

    // toevoegen in aparte map creditnota
    @mkdir("creditnota/");
    chdir("creditnota/");
    @mkdir($dir);
    chdir($dir);
    $fp1 = fopen($file, 'w');
    fwrite($fp1, $cn);
    fclose($fp1);
    chdir("../../")
    // plaatsen op de netwerk hdd
?>
    	<script type='text/javascript'>

    	parent.window.close();

    	</script>
    <?php
}
    ?>

<html>
<head>
<title>
Creditnota verwerken
</title>
</head>
<body>

<?php 
/*
echo "<pre>";
var_dump( $_SESSION["kalender_cn"] );
echo "</pre>";
*/
?>


<table width='100%'>
<tr>
	<td align='center'>
		<form method='post'>
			
			<input type='submit' name='verwerk' id='verwerk' value='Verwerk' />
			<input type='hidden' name='klant_id' id='klant_id' value='<?php echo $_GET["klant_id"] ?>' />
			<input type='hidden' name='fac_nr' id='fac_nr' value='<?php echo $_GET["cn_nr"] ?>' />
			<input type='hidden' name='datum' id='datum' value='<?php echo $_GET["datum"] ?>' />
		</form>
	</td>
</tr>
</table>

<iframe src="klanten_cn_toon.php" width="100%" height="90%">

  <p>Your browser does not support iframes.</p>
</iframe> 

</body>
</html>