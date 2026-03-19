<?php

use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';


$mail = new PHPMailer();
$mail->From     = "dimitri@futech.be"; 
$mail->FromName = "Dimitri | Futech"; 
$mail->Host     = "192.168.1.250";
$mail->Mailer   = "smtp";
$mail->IsHTML(true);// send as HTML

$mail_output = file_get_contents("mailing/mailing1.html");


$mail->MsgHTML($mail_output); 

$mail->AddAddress("dimitri@ilumen.be");
$mail->AddAddress("frans-joseph@europeansolarchallenge.eu");

$mail->Subject = "European Solar Challenge";
$mail->SMTPAutoTLS = false;    
$ok = $mail->Send();

if( $ok )
{
    echo "ok";
}else
{
    echo "nok";
}

?>