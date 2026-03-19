<?php

use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';


$smtp_server = "192.168.1.250";

if( isset( $_POST['toevoegen'] ) )
{
    include "inc/db.php";

    // opslaan in database en tonen in ERP
    $dag1 = '0';
    if( isset( $_POST['elf']) && $_POST['elf'] == 'ja' )
    {
        $dag1 = '1';
    }
    
    $dag2 = '0';
    if( isset( $_POST['twaalf']) && $_POST['twaalf'] == 't_ja' )
    {
        $dag2 = '1';
    }
    $q_ins = "INSERT INTO kal_tesla (   naam,
                                        adres,
                                        woonplaats,
                                        tel_gsm,
                                        type_tesla,
                                        vin_nr,
                                        nr_plaat,
                                        dag_1,
                                        dag_2) VALUES (
                                        '".$_POST['name']."',
                                        '".$_POST['address']."',
                                        '".$_POST['city']."',
                                        '".$_POST['tel_gsm']."',
                                        '".$_POST['type_tesla']."',
                                        '".$_POST['vin_nr']."',
                                        '".$_POST['license_plate']."',
                                        '".$dag1."',
                                        '".$dag2."')";
    
    mysqli_query($conn, $q_ins) or die( mysqli_error($conn) ." " . __LINE__ ." " . $q_ins );
    
    // set bericht tonen
    $gelukt = 1;
        
    // mailen naar
    $mail_output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $mail_output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
    $mail_output .= '<head>';
    $mail_output .= "<meta http-equiv='Content-Type' content='text-html;charset=UTF-8' />";
    $mail_output .= '</head><body>Application Tesla Club:<br />';
    $mail_output .= "<table style='color:#1f497d;font-family:Calibri;' >";
    
    foreach( $_POST as $key => $value )
    {
        if( $key != 'toevoegen' && $key != 'elf' && $key != 'twaalf')
        {
            $mail_output .= "<tr><td><b>".$key ."</b></td><td>" . $value ."</td></tr>";
        }
    }
    
    if( $dag1 == '1' )
    {
        $mail_output .= "<tr><td><b>Present 11/10</b></td><td>Yes</td></tr>";
    }else{
        $mail_output .= "<tr><td><b>Present 11/10</b></td><td>No</td></tr>";
    }
    
    if( $dag2 == '1' )
    {
        $mail_output .= "<tr><td><b>Present 12/10</b></td><td>Yes</td></tr>";
    }else{
        $mail_output .= "<tr><td><b>Present 12/10</b></td><td>No</td></tr>";
    }    
    
    $mail_output_footer .= "</table>";
    $mail_output_footer .= '</body></html>';
    
    $mail_output .= $mail_output_footer;
    
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug=1;
    
    $mail->From     = "info@europeansolarchallenge.eu";
    $mail->FromName = "Info | ESC"; 
        
    //$mail->Host     = "localhost";
    $mail->Host     = $smtp_server;
    $mail->Mailer   = "smtp";
    
    $mail_txt .= $mail_output_footer;
    
    $mail->IsHTML(true);// send as HTML
    $mail->MsgHTML($mail_output); 
    
    $mail->AddAddress("info@europeansolarchallenge.eu");
    $mail->AddAddress("laurent@teslaclub.be");
    $mail->Subject = "Apply to Tesla club";
    $mail->SMTPAutoTLS = false;    
    $ok = $mail->Send();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
Tesla club
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />

<style>
    @font-face {
        font-family: 'ESCFont';
        src: url('css/pirulen.woff') format('woff'), /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
             url('css/pirulen.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3â€”5 */
      }
      html, body{
          font-family: 'ESCfont';
          background: white;
      }
</style>

<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.js"></script>
<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript">

$(document).ready(function(){
	$("#frm_nieuwegebruiker").validate();
});

</script>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24625187-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body>

<div id='pagewrapper'>
    <?php
    if( isset( $gelukt ) )
    {
        echo "<span style='color:#7E9A33;'>Application is sent.</span></br />";
    }
    ?>
    <form id='frm_nieuwegebruiker' name='frm_nieuwegebruiker' method='post' >
        <table>
            <tr>
                <td>Firstname & name :</td>
                <td>
                    <input type='text' class='lengte required' name='name' id='name' />
                </td>
            </tr>

            <tr>
                <td>Address :</td>
                <td>
                    <input type='text' class='lengte required' name='address' id='address' />
                </td>
            </tr>

            <tr>
                <td>City :</td>
                <td>
                    <!--<input type='text' class='lengte required email' name='woonplaats' id='woonplaats' />-->
                    <input type='text' class='lengte required' name='city' id='city' />
                </td>
            </tr>

            <tr>
                <td>Tel / Mobile :</td>
                <td>
                    <input type='text' class='lengte required' name='tel_gsm' id='tel_gsm' />
                </td>
            </tr>

            <tr>
                <td>Type Tesla :</td>
                <td>
                    <input type='text' class='lengte required' name='type_tesla' id='type_tesla' />
                </td>
            </tr>

            <tr>
                <td>VIN nr :</td>
                <td>
                    <input type='text' class='lengte required' name='vin_nr' id='vin_nr' />
                </td>
            </tr>

            <tr>
                <td>License plate :</td>
                <td>
                    <input type='text' class='lengte required' name='license_plate' id='license_plate' />
                </td>
            </tr>
            
            <tr>
                <td valign='top'>Present 11/10 :</td>
                <td align='left'>
                    <input type='radio' class='required' name='elf' id='ja' value='ja'/><label for='ja'>Yes</label><br />
                    <input type='radio' class='required' name='elf' id='nee' value='nee'/><label for='nee'>No</label>
                </td>
            </tr>
            
            <tr>
                <td valign='top'>Present 12/10 :</td>
                <td align='left'>
                    <input type='radio' class='required' name='twaalf' id='t_ja' value='t_ja'/><label for='t_ja'>Yes</label><br />
                    <input type='radio' class='required' name='twaalf' id='t_nee' value='t_nee'/><label for='t_nee'>No</label>
                </td>
            </tr>
            
            <tr>
                <td></td>
                <td><input type='submit' name='toevoegen' id='toevoegen' value='Submit' /></td>
            </tr>
        </table>
    </form>
</div>
</body>
</html>
