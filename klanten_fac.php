<?php
session_start();

include "inc/db_car.php";
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";



if (isset($_POST["verwerk"]) && $_POST["verwerk"] == 'Verwerk') {
    $factuur = factuur_trans($_POST["fac_nr"], $_POST["trans_id"], $_POST["datum"], "S", $conn_car);
//
    $file = $_POST["fac_nr"] . ".pdf";

    $q_transaction = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=".$_POST['trans_id']));
    switch($q_transaction->btw){
        case 0:
            $btw = 0;
            break;
        case 1:
            $btw = 6;
            break;
        case 2:
            $btw = 21;
            break;
    }
    $q_ins_fac = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_soort_id, cf_soort, cf_file, cf_date, cf_bedrag, cf_bedrag_excl,cf_btw) VALUES(" . $q_transaction->soort_id . ",'factuur','" . $file . "','" . changeDate2EU($_POST["datum"]) . "','" . $factuur["incl"] . "','" . $factuur["excl"] . "','".$btw."')");
    $factuur_id = mysqli_insert_id($conn);
    $q_upd_transactie = mysqli_query($conn, "UPDATE tbl_transacties SET factuur_id=".$factuur_id." WHERE id=".$_POST['trans_id']);
    
        /* dir naam ingeven voor de boekjaar */
        $nu =  date('d-m-Y');
        $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
        while($boekjaar = mysqli_fetch_object($q_boekjaren)){
            if($nu<$boekjaar->boekjaar_einde){
                $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
            }
        }
    
    chdir("cus_docs/");
    @mkdir($q_transaction->soort_id, 0777);
    chdir($q_transaction->soort_id);
    @mkdir("factuur", 0777);
    chdir("factuur");
    @mkdir($dir, 0777);
    chdir($dir);
    $fp = fopen($file, 'w');
    fwrite($fp, $factuur["factuur"]);
    fclose($fp);
    chdir("../../../../");
    
    chdir("facturen/");
    @mkdir($dir, 0777);
    chdir($dir);
    $fp1 = fopen($file, 'w');
    fwrite($fp1, $factuur["factuur"]);
    fclose($fp1);
    chdir("../../");
    
    echo  "<script type='text/javascript'>";
    echo "window.close();";
    echo "</script>";
}
?>

<html>
    <head>
        <title>
            Factuur verwerken
        </title>
    </head>
    <body>

        <table width='100%'>
            <tr>
                <td align='center'>
                    <form method='post'>
                        <input type='submit' name='verwerk' id='verwerk' value='Verwerk' />
                        <input type='hidden' name='trans_id' id='trans_id' value='<?php echo $_GET["trans_id"] ?>' />
                        <input type='hidden' name='fac_nr' id='fac_nr' value='<?php echo $_GET["fac_nr"] ?>' />
                        <input type='hidden' name='datum' id='datum' value='<?php echo $_GET["datum"] ?>' />
                    </form>
                </td>
            </tr>
        </table>

        <!-- 
        <FRAMESET ROWS="50,*">
                <FRAME SRC="klanten_fac_verwerk.php?klant_id=<?php echo $_GET["klant_id"] ?>" NAME="navigatieframe">
                <FRAME SRC="" NAME="hoofdframe">
        </FRAMESET>
        -->
        <iframe src="klanten_fac_toon.php?trans_id=<?php echo $_GET["trans_id"] ?>&fac_nr=<?php echo $_GET["fac_nr"] ?>&datum=<?php echo $_GET["datum"]; ?>&verklaring=<?php echo $_GET["verklaring"]; ?>" width="100%" height="90%">
        <p>Your browser does not support iframes.</p>
        </iframe> 

    </body>
</html>