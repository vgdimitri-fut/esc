<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';

if( isset( $_POST["verwerk"] ) && $_POST["verwerk"] == 'Verwerk' )
{
	if( $_SESSION["custom_factuur"]["klant_keuze"] == "bestaande" )
	{
		$_POST["fac_nr"] = $_SESSION["custom_factuur"]["factuur_nr"];
		$_POST["klant_id"] = $_SESSION["custom_factuur"]["sel_bestaande_klant"];
		$_POST["datum"] = $_SESSION["custom_factuur"]["datum_cf"];
	}else
	{
		// opslaan van de nieuwe klant
		/*
            $_SESSION["custom_factuur"]["naam"] => Nieuwe klant
            $_SESSION["custom_factuur"]["bedrijf"] => geen bedrijf
            $_SESSION["custom_factuur"]["btwnr"] => btw
            $_SESSION["custom_factuur"]["straat"] => straaat
            $_SESSION["custom_factuur"]["nr"] => 128
            $_SESSION["custom_factuur"]["postcode"] => 3945
            $_SESSION["custom_factuur"]["gemeente"] => Ham 
		*/
		$q_ins = mysqli_query($conn, "INSERT INTO kal_customers(cus_naam, 
		                                                 cus_bedrijf, 
		                                                 cus_btw, 
		                                                 cus_straat, 
		                                                 cus_nr, 
		                                                 cus_postcode, 
		                                                 cus_gemeente) 
		                                         VALUES('" . htmlentities($_SESSION["custom_factuur"]["naam"], ENT_QUOTES) . "',
		                                                '" . htmlentities($_SESSION["custom_factuur"]["bedrijf"], ENT_QUOTES) . "',
		                                                '" . $_SESSION["custom_factuur"]["btwnr"] . "',
		                                                '" . htmlentities($_SESSION["custom_factuur"]["straat"], ENT_QUOTES) . "',
		                                                '" . $_SESSION["custom_factuur"]["nr"] . "',
		                                                '" . $_SESSION["custom_factuur"]["postcode"] . "',
		                                                '" . htmlentities($_SESSION["custom_factuur"]["gemeente"], ENT_QUOTES) . "' )");
		
		$_POST["fac_nr"] = $_SESSION["custom_factuur"]["factuur_nr"];
		$_POST["klant_id"] = mysqli_insert_id($conn);
		$_POST["datum"] = $_SESSION["custom_factuur"]["datum_cf"];
	}
	
	$factuur = customfactuur("S", $btw_vrijstelling);
	
	$file =	$_POST["fac_nr"] . ".pdf";
	
    $soort_fac = $_SESSION["custom_factuur"]["soort_fac"];
    if( $soort_fac == "Andere" )
    {
        $soort_fac = $_SESSION["custom_factuur"]["soort_fac_andere"];
    }
    
	// toevoegen bij de klant zelf
	//$q_upd = mysqli_query($conn, "UPDATE kal_customers SET cus_factuur_filename = '" . $file . "' WHERE cus_id = " . $_POST["klant_id"] . " LIMIT 1");
	$q_ins = "INSERT INTO kal_customers_files(cf_cus_id, cf_soort, cf_file, cf_date, cf_bedrag, cf_van, cf_btw) VALUES(".$_POST["klant_id"].",'factuur','".$file."', '". changeDate2EU($_SESSION["custom_factuur"]["datum_cf"]) ."','". $factuur["incl"] ."','". $soort_fac ."',". $_SESSION["custom_factuur"]["btw"] .")";
	mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );

	$fac_id = mysqli_insert_id($conn);
    
    // begin mailen facturen
    $factuur_rec = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_id = " . $fac_id));
    
    $cf_mail = 0;
    $cf_mail_op = '';
    
    if( isset( $_POST["facmails"] ) && count( $_POST["facmails"] ) > 0 )
    {
        $cf_mail = 1;
        $cf_mail_op = date("Y-m-d");
        
        $q_upd = "UPDATE kal_customers_files SET cf_mail = '".$cf_mail."', cf_mail_op = '". $cf_mail_op ."' WHERE cf_id = " . $fac_id;
        mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q_upd ); 
        
        $mail1 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_mails WHERE id = 124"));
        $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_customers WHERE cus_id = " . $_POST["klant_id"]));
        
        foreach( $_POST["facmails"] as $email )
        {
			$facnaam = factuurNaam($factuur_rec->cf_id, 0, $conn);
    
            $var_arr = array( "%%NAAM%%" );
            $naar_arr = array( $klant->cus_naam );
            
            if( $klant->cus_lang_fr == "1" )
            {
                $mail_body = str_replace( $var_arr, $naar_arr, $mail1->body_fr );
            }else
            {
                $mail_body = str_replace( $var_arr, $naar_arr, $mail1->body );
            }
            
            $var_arr = array( "%%FACTUURNR%%" );
            $naar_arr = array( str_replace(".pdf", "", $facnaam ) );
            
            if( $klant->cus_lang_fr == "1" )
            {
                $mail_onderwerp = str_replace( $var_arr, $naar_arr, $mail1->onderwerp_fr );
            }else
            {
                $mail_onderwerp = str_replace( $var_arr, $naar_arr, $mail1->onderwerp );
            }
            
            $mail = new PHPMailer();
            
            $mail->Sender = $mail->From     = $mail1->from_email; 
            $mail->FromName = $mail1->from_name; 
            $mail->Subject = $mail_onderwerp . " - " . maakReferte($klant->cus_id, $conn) ;
            
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.gmail.com';  // Google Workspace SMTP relay host
            $mail->SMTPAuth   = false;                   // No authentication if IP-based relay
            $mail->Port       = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            $text_body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
            $text_body .= '<html xmlns="http://www.w3.org/1999/xhtml">';
            $text_body .= '<head></head><body>';
            $text_body .= html_entity_decode($mail_body); 
            $text_body .= '</body></html>';
            
            $mail->MsgHTML($text_body); 
            $mail->IsHTML(true);// send as HTML
            
            $mail->addStringAttachment( $factuur["factuur"], $facnaam );
            
            $mail->AddAddress( $email );
            //$mail->AddCC("verisolcus@solarlogs.be");
            
            if( $klant->cus_lang_fr == "0" )
            {
                // zoeken naar attachments
                $q_att = mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_soort = 'mails_nl_". $mail1->id ."'");
                
                if( mysqli_num_rows($q_att) > 0 )
                {
                    while( $cf = mysqli_fetch_object($q_att) )
                    {
                        $filea = "../../../../../../../var/www/html/esc/mails/" . $mail1->id . "/NL/" . $cf->cf_file;
                        
                        if( file_exists( $filea ) )
                        {
                            $mail->AddAttachment( $filea );
                        }
                    }
                }
            }
            
            if( $klant->cus_lang_fr == "1" )
            {
                // zoeken naar attachments
                $q_att = mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_soort = 'mails_fr_". $mail1->id ."'");
                
                if( mysqli_num_rows($q_att) > 0 )
                {
                    while( $cf = mysqli_fetch_object($q_att) )
                    {
                        $filea = "../../../../../../../var/www/html/esc/mails/" . $mail1->id . "/FR/" . $cf->cf_file;
                        
                        if( file_exists( $filea ) )
                        {
                            $mail->AddAttachment( $filea );
                        }
                    }
                }
            }
            
			$ok = $mail->Send();

            if( $klant->cus_lang_fr == "0" )
            {
                $ok = $mail->Send();
            }
            
            if( $klant->cus_lang_fr == "1" && !empty( $mail1->body_fr ) )
            {
                $ok = $mail->Send();
            }
            
            if( $ok )
            {
                // toevoegen aan kal_customers_files
                $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,
                                                          cf_soort,
                                                          cf_file) 
                                                   VALUES(". $_POST["klant_id"] .",
                                                          'factuur_send',
                                                          '". $file ."')";
                
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
            }else
            {
                echo "AAA " . $mail->ErrorInfo;    
				echo "<pre>BBB";
				var_dump( "CCC" . $ok );
				var_dump( $mail );
				echo "</pre>";
            }
        }
        
    }
    // einde mailen facturen
	
	// plaatsen op de juiste map op de server
	$q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
	while($boekjaar = mysqli_fetch_object($q_boekjaren)){
		if(changeDate2EU($_SESSION["custom_factuur"]["datum_cf"]) > $boekjaar->boekjaar_start && changeDate2EU($_SESSION["custom_factuur"]["datum_cf"])<= $boekjaar->boekjaar_einde){
			$dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
		}
	}
    
	chdir( "cus_docs/");
	@mkdir( $_POST["klant_id"] );
	chdir( $_POST["klant_id"]);
	@mkdir( "factuur" );
	chdir( "factuur" );
	@mkdir( $dir );
	chdir( $dir );
	$fp = fopen($file, 'w');
	fwrite($fp, $factuur["factuur"] );
	fclose($fp);
	chdir("../../../../");

	chdir( "facturen/" );
	@mkdir( $dir );
	chdir( $dir );
	$fp1 = fopen($file, 'w');
	fwrite($fp1, $factuur["factuur"] );
	fclose($fp1);
	chdir("../../");
	// plaatsen op de netwerk hdd
	
	// leegmaken van de sessie variable
	$_SESSION["custom_factuur"] = array();
	
	
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
Factuur verwerken
</title>
</head>
<body>

<?php 

/*
echo "<pre>";
print_r( $_SESSION );
echo "</pre>";
*/

?>


<table width='100%'>
<tr>
	<td align='center'>
		<form method='post'>
            <table>
            <tr><td valign='top' >
            <?php
            
            $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_customers WHERE cus_id = " . $_SESSION["custom_factuur"]["sel_bestaande_klant"]));
            
            if( $_SESSION["custom_factuur"]["btw"] == 0 && $klant->cus_btw == '' )
            {
                die("Klant heeft geen BTW nummer en daardoor kan er geen factuur met 0% btw gemaakt worden.");
            }else
            {
            
            ?>
            <input type='submit' name='verwerk' id='verwerk' value='Verwerk' />
            <?php
            
            }
            
            ?>
            
            </td>
            <td>
            
            <?php
            
            $q_cus = mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_details WHERE cus_id = " . $_SESSION["custom_factuur"]["sel_bestaande_klant"] . " AND soort = '3' " );

			$customer = mysqli_fetch_object( mysqli_query($conn, "SELECT * FROM esc_db.kal_customers WHERE cus_id = " . $_SESSION["custom_factuur"]["sel_bestaande_klant"] ) );
            
			$email_arr = array();

			$email_arr[ $customer->cus_email ] = $customer->cus_email;

			while( $cus = mysqli_fetch_object($q_cus) )
			{
				$email_arr[ $cus->waarde ] = $cus->waarde;
			}

            if( count( $email_arr ) > 0 )
            {
                foreach( $email_arr as $a )
                {
                    echo "<input type='checkbox' name='facmails[]' value='".$a."' /> " . $a . "<br />";
                }    
            }
            
            ?>
            
            </td>
            </tr>
			</table>
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
<iframe src="klanten_cusfac_toon.php?klant_id=<?php echo $_GET["klant_id"] ?>&fac_nr=<?php echo $_GET["fac_nr"] ?>&datum=<?php echo $_GET["datum"]; ?>" width="100%" height="90%">
  <p>Your browser does not support iframes.</p>
</iframe> 

</body>
</html>