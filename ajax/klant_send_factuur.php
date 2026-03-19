<?php

include "../inc/db.php";
include "../inc/functions.php";
use PHPMailer\PHPMailer\PHPMailer;
require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../../vendor/phpmailer/phpmailer/src/Exception.php';


$factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_customers_files WHERE cf_id = " . $_POST["rec_id"]));
$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM esc_db.kal_customers WHERE cus_id = " . $factuur->cf_cus_id));    

if( !empty( $klant->cus_email ) )
{
    $qq = "SELECT * FROM esc_db.kal_boekjaar WHERE '". $factuur->cf_date ."' BETWEEN boekjaar_start AND boekjaar_einde LIMIT 1";
    $q = mysqli_query($conn, $qq) or die( mysqli_error($conn) . " " . $qq . " " . __LINE__ );
    $boekjaar = mysqli_fetch_object($q);
    //$dir = substr( $boekjaar->boekjaar_start, 0, 4 ) . substr( $boekjaar->boekjaar_einde, 0, 4 ) . "/";
    $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde . "/";
    
    $path = "";
    if( file_exists( "../cus_docs/". $factuur->cf_cus_id ."/factuur/".$dir. $factuur->cf_file ) )
    {
        //echo "<a href='cus_docs/". $rij->cf_cus_id ."/factuur/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";
        $path = "cus_docs/". $factuur->cf_cus_id ."/factuur/".$dir. $factuur->cf_file;
        
    }
    
    //echo "<br />" . getcwd();
    //echo "<br />" . "../facturen/". $dir. $factuur->cf_file;
    
    if( file_exists( "../facturen/". $dir. $factuur->cf_file ) )
    {
        //echo "<a href='facturen/".$dir. $rij->cf_file . "' target='_blank'>" . $rij->cf_file . "</a>";
        $path = "facturen/".$dir. $factuur->cf_file;    
    }
    
    $path = "../" . $path;
    
    //die($path);
    
    $facnaam = $factuur->cf_file;
    
    $mail = new PHPMailer();
    $mail->From     = "michiel@futech.be"; 
    $mail->FromName = "European Solar Challenge"; 
    
    $mail->Subject = "Your invoice";
    
    $mail->Host     = $smtp_server;
    $mail->Mailer   = "smtp";
    
    
    $mail_body = "Dear, <br /><br />Please find attached our invoice.<br /><br /> With kind regards<br />Michiel Janssens <br />0492 63 95 68";
    
    $text_body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $text_body .= '<html xmlns="http://www.w3.org/1999/xhtml">';
    $text_body .= '<head></head><body>';
    $text_body .= html_entity_decode($mail_body); 
    $text_body .= '</body></html>';
    
    $mail->MsgHTML($text_body); 
    $mail->IsHTML(true);// send as HTML
    
    $mail->AddAttachment( $path, $facnaam );
    
    $mail->AddAddress($klant->cus_email, $klant->cus_naam);
    $mail->SMTPAutoTLS = false;
    $ok = $mail->Send();
    
    if( $ok )
    {
        // toevoegen aan kal_customers_files
        $q_ins = "INSERT INTO esc_db.kal_customers_files(cf_cus_id,
                                                      cf_soort,
                                                      cf_file) 
                                               VALUES(". $factuur->cf_cus_id .",
                                                      'factuur_send',
                                                      '". $facnaam ."')";
        
        mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
        
    }else
    {
        var_dump($mail);
        
        mail("dimitri@ilumen.be",'ESC - Mail met factuur werd niet verstuurd ' . $klant->cus_id,'Factuur werd niet verstuurd. Factuurnaam ' . $facnaam,$headers);
    }
    
}else
{
    mail("dimitri@ilumen.be",'ESC - Klant heeft geen emailadres ' . $klant->cus_id,'Factuur werd niet verstuurd. Factuurnaam ' . $facnaam,$headers);
}


?>