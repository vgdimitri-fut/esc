<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";
use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';


$types_arr = array();
$types_arr["eenmaling"] = "E&eacute;nmalig";
$types_arr["dagelijks_mw"] = "Dagelijks met weekends";
$types_arr["dagelijks_zw"] = "Dagelijks zonder weekends";
$types_arr["wekelijks"] = "Wekelijks";
$types_arr["maandelijks"] = "Maandelijks";
$types_arr["jaarlijks"] = "Jaarlijks";

//"actie=zoeken&waarde=" + document.getElementById("string").value;

if( isset( $_POST["actie"] ) && $_POST["actie"] == "Search" )
{
    $q123 = "SELECT a.id FROM kal_opdrachten as a, kal_opdrachten_users as b
                WHERE a.id = b.opdracht_id
                AND ( titel LIKE '%". $_POST["waarde"] ."%' 
                      OR omschrijving LIKE '%". $_POST["waarde"] ."%' 
                      OR ready LIKE '%". $_POST["waarde"] ."%' 
                      OR notfinished LIKE '%". $_POST["waarde"] ."%' )
                AND user_id = " . $_SESSION[$session_var]->user_id;
    
    $q_opdracht = mysqli_query($conn, $q123);
    
    echo "<b>Number of found assignments : " . mysqli_num_rows($q_opdracht) . "</b><br /><br />";
    
    while( $opdracht = mysqli_fetch_object($q_opdracht) ) 
    {
        //echo "<br />" . $nr . " " . $opdracht_id;
        $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $opdracht->id));
        
        // BEGIN
        echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
        echo "<tr>";
        echo "<td colspan='2' bgcolor='gray'>
        
        <table width='100%' cellpadding='0' cellspacing='0' border='0'>
        <tr><td width='25%'>";
        
        $tab_id = 3;
        
        /*
        if( $_SESSION[$session_var]->group_id == 1 )
        {
            $tab_id = 4;    
        }
        */
        
        echo "<a href='interne_opdrachten.php?tab_id=".$tab_id."&opdracht_id=".$opdracht->id."'>";
        echo "<img alt='Open assignment' title='Open assignment' src='images/info.png' width='20' height='20' /></a>";
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id )
        {
            echo "<form method='post' name='frm_delete' id='frm_delete' style='display:inline;' >";
            echo "<input onclick='return confirm(\"Delete assignment?\")' type='image' src='images/delete.png' name='delete_opdracht' id='delete_opdracht' width='20' height='20' value='". $opdracht->id ."' />";
            echo "<input type='hidden' name='delete_opdracht' id='delete_opdracht' value='". $opdracht->id ."' />";
            echo "</form>";
        }
        
        
        echo "</td>
        <td align='center'><b style='color:white;' >";
        
        echo "<table cellpadding='0' cellspacing='0' border='0'><tr><td>";
        
        echo "<img src='images/openclose.png' alt='Detailview' title='Detailview' onclick='showHide(\"". $opdracht->id ."\");' />&nbsp;";
        
        echo "</td><td>";
        
        echo html_entity_decode($opdracht->titel)."</b>";
        
        echo "</td></tr></table>";
        
        echo "</td>";
        echo "<td width='25%' style='color:white;'>";
        
        /* tabel */
        echo "<table cellpadding='0' cellspacing='0' width='100%' ><tr><td>";
        
        echo "ID : ". $opdracht->unique_id;
        
        echo "</td><td align='right' >";
        
        $now = time(); // or your date as well
        $your_date = strtotime( $opdracht->start );
        $datediff = $now - $your_date;
        $aantal_dagen = floor($datediff/(60*60*24));
        
        // overwrite aantal dagen wanneer het een recurrente opdracht is
        if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
        {
            $your_date = strtotime( substr( $opdracht->datetime,0,10 ) );
            $datediff = $now - $your_date;
            $aantal_dagen = floor($datediff/(60*60*24));
        }
        
        echo "<span title='Assignment is open for ". $aantal_dagen ." days'>";
        
        echo "#";
        echo $aantal_dagen;
        
        echo "</span>";
        echo "</td></tr></table>";
        /* einde tabel */
        
        
        
        echo "</td></tr></table></td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<div style='display:none;' id='id_". $opdracht->id ."' >";
        echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1'  >";
        echo "<tr>";
        echo "<td width='50%'>";
        echo "<strong>Start : </strong>" . changeDate2EU( $opdracht->start );
        echo "</td>";
        
        echo "<td width='50%'>";
        
        if( $opdracht->stop != $opdracht->start && $opdracht->stop != '0000-00-00' )
        {
            echo "<strong>Deadline : </strong>" . changeDate2EU( $opdracht->stop );
        }
        
        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td colspan='2'><strong>Assigned to : </strong>";
        
        // ophalen van al de gebruikers die aan deze opdracht gekoppeld zijn.
        
        $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
        
        $u = "";
        while( $opus = mysqli_fetch_object($q_opdracht_users)  )
        {
            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
            
            $chk = "";
            
            if( $opus->status == 'done' )
            {
                $chk = " <strong>(finished)</strong>";
            }
            
            $u .= $user->voornaam . " " . $user->naam . $chk . ", ";
        }
        
        echo substr( $u, 0, -2 );
        
        
        if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
        {
            echo " - op : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
        }
        
        $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
        echo "<br /><strong>Created by : </strong>" . $van->voornaam . " " . $van->naam;
        echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];
        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td colspan='2'>".html_entity_decode($opdracht->omschrijving)."</td>";
        echo "</tr>";
        
        $eerst_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE unique_id = '" . $opdracht->unique_id . "' ORDER BY 1 ASC LIMIT 1"));
        $eerst_opdracht_id = $eerst_opdracht->id;
        
        $exclude_arr = array();
        
        $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten' ");
        
        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
        {
            echo "<tr>";
            echo "<td colspan='2'><strong>Attachments :</strong>";
            
            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
            {
                $exclude_arr[ $attach->cf_id ] = $attach->cf_id;
                echo "<br /><a href='opdrachten/".$opdracht->id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        
        $toon_titel = 0;                    
        $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
        {
            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
            {
                if( !in_array($attach->cf_id, $exclude_arr) )
                {
                    $toon_titel = 1;
                }
            }
        }
        
        if( $toon_titel == 1 )
        {
            $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
            
            if( mysqli_num_rows($q_zoek_bijlage) > 0 )
            {
                echo "<tr>";
                echo "<td colspan='2'><strong>Attachments :</strong>";
                
                while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                {
                    if( !in_array($attach->cf_id, $exclude_arr) )
                    {
                        echo "<br /><a href='opdrachten/".$eerst_opdracht_id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";    
                    }
                }
                
                echo "</td>";
                echo "</tr>";
            }
        }
        
        // nakijken of er nog andere gebruikers aan deze opdracht gekoppeld zijn.
        $q1 = "SELECT * 
                  FROM kal_opdrachten_users 
                 WHERE opdracht_id = " . $opdracht->id . " 
                   AND ( ready != ''
                   OR notfinished != '' ) ";
                   
        $q_opdracht_info = mysqli_query($conn, $q1) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q1 );
        
        if( mysqli_num_rows($q_opdracht_info) > 0 )
        {
            echo "<tr><td colspan='2'>";
            
            $k=0;
            while( $opus = mysqli_fetch_object($q_opdracht_info) )
            {
                if( $k == 0 )
                {
                    echo "Below you can find the info of others persons with the same assignment :<br />";
                }
                
                $k++;
                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                
                echo "<br /><b><u>";
                echo $user->voornaam . " " . $user->naam;
                echo "</u></b>";
                echo "<br /><strong>What is finished?</strong><br />";
                
                echo "Documents :";
                //echo '<input type="file" name="bijlagen_klaar[]" id="bijlagen_klaar" multiple="" />';
                
                $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " 
                                               AND cf_soort = 'int_opdrachten_klaar' AND cf_van_distri_offerte = '". $opus->user_id ."' ");
            
                if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                {
                    echo "<br /><br /><strong>Attachments :</strong>";
                    
                    while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                    {
                        echo "<br />";
                        echo "<a href='interne_opdrachten.php?tab_id=". $_GET["tab_id"] ."&opdracht_id=". $_GET["opdracht_id"] ."&rm_file_id=".$attach->cf_id."&soort=klaar' onclick='return confirm(\"Bijlage verwijderen?\");' > <img src='images/delete.png'> </a>";
                        echo "<a href='opdrachten/".$opdracht->id  ."/klaar/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                    }
                }
                
                echo html_entity_decode( $opus->ready );
                
                
                echo "<br /><br /><br /><strong>What needs to be done?</strong><br />";
                echo html_entity_decode($opus->notfinished);
                
                if( !empty( $opus->status ))
                {
                    echo "<b>";
                }
                
                echo "<br /><br />Status : " . $opus->status;
                
                if( !empty( $opus->status ))
                {
                    echo "</b>";
                }
                
                echo "<br /><hr/>";
            }
            
            echo "</td></tr>";
            
        }
        
        echo "</table></div><br />";
        // EINDE
        
    }
    
    die();
}

function secs_to_h($secs)
{
        $units = array(
                "week"   => 7*24*3600,
                "dag(en)"    =>   24*3600,
                "u(u)r(en)"   =>      3600,
                "minu(u)t(en)" =>        60,
                "second(en)" =>         1,
        );

	// specifically handle zero
        if ( $secs == 0 ) return "0 seconds";

        $s = "";

        foreach ( $units as $name => $divisor ) {
                if ( $quot = intval($secs / $divisor) ) {
                        $s .= "$quot $name";
                        $s .= ", ";
                        $secs -= $quot * $divisor;
                }
        }

        return substr($s, 0, -2);
}

// bijlage verwijderen
if( isset( $_GET["rm_file_id"] ) && $_GET["rm_file_id"] > 0 )
{
    $q_file = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $_GET["rm_file_id"]) or die( mysqli_error($conn) . " " . __LINE__ );
    
    if( mysqli_num_rows($q_file) == 1 )
    {
        $file = mysqli_fetch_object($q_file);
        
        
        $path_file = "opdrachten/" . $file->cf_cus_id . "/" . $file->cf_file;
        if( isset( $_GET["soort"] ) && $_GET["soort"] == "klaar" )
        {
            $path_file = "opdrachten/" . $file->cf_cus_id . "/klaar/" . $file->cf_file;    
        } 
        
        if( file_exists( $path_file ) )
        {
            if( unlink($path_file) )
            {
                $q_del = "DELETE FROM kal_customers_files WHERE cf_id = ". $file->cf_id ." LIMIT 1";
                mysqli_query($conn, $q_del)  or die( mysqli_error($conn) . " " . __LINE__ );
            }
        }
    }
}

if( isset( $_POST["delete_opdracht"] ) && $_POST["delete_opdracht"] > 0 )
{
    // nakijken of er bijlages zijn, files & rec
    $q_zoek_files = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'int_opdrachten' AND cf_cus_id = " . $_POST["delete_opdracht"]) or die( mysqli_error($conn) . " " . __LINE__ );;
    
    //echo "Aantal : " . mysqli_num_rows($q_zoek_files);
    
    while( $rec = mysqli_fetch_object($q_zoek_files) )
    {
        // bestand verwijderen en rec verwijderen
        $path_file = "opdrachten/" . $_POST["delete_opdracht"] . "/" . $rec->cf_file;
        
        if( file_exists( $path_file ) )
        {
            unlink($path_file);
            
            $q_del = "DELETE FROM kal_customers_files WHERE cf_id = ". $rec->cf_id ." LIMIT 1";
            mysqli_query($conn, $q_del) or die( mysqli_error($conn) . " " . __LINE__ );
        }
    }
    
    // gebruikers verwijderen
    $q_del = "DELETE FROM kal_opdrachten_users WHERE opdracht_id = " . $_POST["delete_opdracht"];
    mysqli_query($conn, $q_del)  or die( mysqli_error($conn) . " " . __LINE__ );
    
    // opdracht verwijderen
    $q_del = "DELETE FROM kal_opdrachten WHERE id = " . $_POST["delete_opdracht"];
    mysqli_query($conn, $q_del)  or die( mysqli_error($conn) . " " . __LINE__ );
    
    ?>
	<meta http-equiv="refresh" content="0;URL=interne_opdrachten.php" />
	<?php
	die(); 
}

if( isset( $_POST["edit_opdracht"] ) && $_POST["edit_opdracht"] == "Opslaan" )
{
    if( !empty($_POST["opus_id"]) )
    {
        $q_upd = "UPDATE kal_opdrachten_users 
                     SET ready = '". htmlentities($_POST["wat_klaar"], ENT_QUOTES) ."', 
                         notfinished = '". htmlentities($_POST["wat_nog"], ENT_QUOTES) ."',
                         status = '". $_POST["status"] ."' 
                   WHERE id = " . $_POST["opus_id"];
                   
        mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q_upd );
    }
    
    $opus = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE id = " . $_POST["opus_id"]));
    
    if( $_POST["status"] == "done" && $opus->mail == '0' )
    {
        $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $opus->opdracht_id));
        $opdracht_van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
        $opdracht_voor = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
        
        $mail_output = "";
        $mail_output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        $mail_output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
        $mail_output .= '<head></head><body>';
        $mail_output .= "<table style='width:800px;'>";
        $mail_output .= "<tr><td>";
        $mail_output .= "<strong>Assignment finished :</strong> " . $opdracht->titel;
        $mail_output .= "</td></tr>";
        
        $mail_output .= "<tr><td>";
        $mail_output .= "<strong><br />Description :</strong> " . html_entity_decode( $opdracht->omschrijving );
        $mail_output .= "</td></tr>";
        
        $mail_output .= "<tr><td>";
        $mail_output .= "<strong><br />What is finished :</strong> " . html_entity_decode($opus->ready);
        $mail_output .= "</td></tr>";
        
        $mail_output .= "<tr><td>";
        $mail_output .= "<strong><br />What needs to be done :</strong> " . html_entity_decode($opus->notfinished);
        $mail_output .= "</td></tr>";
        
        $mail_output .= "<tr><td>";
        $mail_output .= "<strong><br />Assigned on :</strong> " . changeDateTime2EU( $opdracht->datetime );
        $mail_output .= "<strong><br />Finished on :</strong> " . changeDateTime2EU( $opus->done_time );
        $mail_output .= "</td></tr>";
        
        $mail_output .= "</table><br/><hr/>";
        $mail_output .= '</body></html>';
        
        $mail = new PHPMailer();
        $mail->From     = "noreply@solarlogs.be"; 
        $mail->FromName = "European Solar Challenge";
        $mail->Host     = "192.168.1.250";
        $mail->Mailer   = "smtp";
        $mail->AddAddress( $opdracht_van->email );
        
        //$mail->AddBcc( "dimitri@futech.be" );
        
        $mail->IsHTML(true);// send as HTML
        $mail->MsgHTML($mail_output); 
        
        $mail->Subject = "Assignment finished by : " . $opdracht_voor->voornaam . " " . $opdracht_voor->naam;    
        
        $mail->SMTPAutoTLS = false;
        if( $mail->Send() )
        {
            $q_upd = "UPDATE kal_opdrachten_users SET mail = '1', done_time = '". date('Y-m-d H:i:s') ."' WHERE id = " . $_POST["opus_id"];
            mysqli_query($conn, $q_upd) or die( mysqli_error($conn) . " " . $q_upd );
        }
    }
    
    // admin data ook opslaan
    
    if( isset( $_POST["edit_start"] ) && isset( $_POST["edit_stop"] ) && isset( $_POST["titel"] ) && isset( $_POST["omschrijving_edit"] ) )
    {
        
        $q_upd = "UPDATE kal_opdrachten 
                     SET titel = '".htmlentities($_POST["titel"], ENT_QUOTES)."',
                         start = '" . changeDate2EU($_POST["edit_start"]) . "',
                         stop = '". changeDate2EU($_POST["edit_stop"]) ."',
                         type = '". $_POST["sel_type"] ."',
                         omschrijving = '" . htmlentities($_POST["omschrijving_edit"], ENT_QUOTES) . "'
                     WHERE id = " . $_POST["opdracht_id"];
        
        mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
    }
    
    // uploaden en bewaren van de bijlages.
    if( count( $_FILES["bijlagen"]["name"] ) > 0 )
    {
        /*
        echo "<pre>";
        var_dump($_FILES["bijlagen"]);
        echo "</pre>";
        */
        
        for( $i=0;$i <= count( $_FILES["bijlagen"]["name"] ); $i++ )
        {
             
            
            $target_path = "opdrachten/" . $_POST["opdracht_id"] . "/";
            @mkdir( $target_path );
            $target_path = $target_path . basename( $_FILES['bijlagen']['name'][$i]); 
            
            //echo "<br />---" . getcwd() . " ------ " . $target_path . "-----";
            
            if(move_uploaded_file($_FILES['bijlagen']['tmp_name'][$i], $target_path)) 
            {
                $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,
                                                         cf_soort,
                                                         cf_file) 
                                                  VALUES(".$_POST["opdracht_id"].",
                                                         'int_opdrachten',
                                                         '".basename( $_FILES['bijlagen']['name'][$i])."') ";
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
            }
        }
    }
    
    if( count( $_FILES["bijlagen_klaar"]["name"] ) > 0 )
    {
        for( $i=0;$i <= count( $_FILES["bijlagen_klaar"]["name"] ); $i++ )
        {
            $target_path = "opdrachten/" . $_POST["opdracht_id"] . "/klaar/";
            @mkdir( $target_path );
            $target_path = $target_path . basename( $_FILES['bijlagen_klaar']['name'][$i]); 
            
            if(move_uploaded_file($_FILES['bijlagen_klaar']['tmp_name'][$i], $target_path)) 
            {
                $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,
                                                         cf_soort,
                                                         cf_file,
                                                         cf_van_distri_offerte) 
                                                  VALUES(".$_POST["opdracht_id"].",
                                                         'int_opdrachten_klaar',
                                                         '".basename( $_FILES['bijlagen_klaar']['name'][$i])."',
                                                         '". $_SESSION[$session_var]->user_id ."') ";
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
            }
        }
    }
    
    $all_user = array();
    
    $mail_output = "";
    $mail_output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $mail_output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
    $mail_output .= '<head></head><body>';
    $mail_output .= "<table style='width:800px;'>";
    $mail_output .= "<tr><td>";
    $mail_output .= "You have got a new assignment : <a href='http://www.solarlogs.be/esc/interne_opdrachten.php'> Click here</a><br /><br />";
    $mail_output .= "Title : " . $_POST["titel"] . "<br /><br />";
    $mail_output .= "Description : " . $_POST["omschrijving_edit"]  . "<br />";
    $mail_output .= "</td></tr>";
    $mail_output .= "</table>";
    $mail_output .= '</body></html>';
    
    // gebruikers toevoegen
    if( count( $_POST["users"] ) > 0 )
    {
        foreach( $_POST["users"] as $user )
        {
            $all_user[$user] = $user;
            
            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $_POST["opdracht_id"] . " AND user_id = " . $user);
            
            if( mysqli_num_rows($q_zoek) == 0 )
            {
                // als niet gevonden
                $q_ins = "INSERT INTO kal_opdrachten_users(opdracht_id, user_id) VALUES(".$_POST["opdracht_id"].",".$user.")";
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
                
                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $user));
                
                // mail versturen naar de personen voor wie deze opdracht is.
                $mail = new PHPMailer();
                $mail->From     = "noreply@solarlogs.be"; 
                $mail->FromName = "European Solar Challenge";
                $mail->Host     = "192.168.1.250";
                $mail->Mailer   = "smtp";
                $mail->AddAddress( $user->email );
                $mail->IsHTML(true);// send as HTML
                $mail->MsgHTML($mail_output); 
                
                $mail->Subject = "New assignment";
                $mail->SMTPAutoTLS = false;    
                $mail->Send();
            }
        }
    }
    
    // gebruikers verwijderen
    
    if( isset( $_POST["users"] ) && count( $_POST["users"] ) > 0 )
    {
        $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $_POST["opdracht_id"]);
        
        while( $rec_user = mysqli_fetch_object($q_zoek) )
        {
            if( !isset( $all_user[ $rec_user->user_id ] ) )
            {
                
                $q_del = "DELETE FROM kal_opdrachten_users WHERE opdracht_id = " . $_POST["opdracht_id"] . " AND user_id = " . $rec_user->user_id . " LIMIT 1";
                mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
            }
        }
    }
}

if( isset( $_POST["opslaan"] ) && $_POST["opslaan"] == "Save" )
{
    if( $_POST["sel_type"] == "jaarlijks" || $_POST["sel_type"] == "maandelijks" )
    {
        $q_ins = "INSERT INTO kal_opdrachten(unique_id,
                                         titel,
                                         omschrijving,
                                         van_user_id,
                                         type,
                                         start,
                                         stop,
                                         datetime) 
                                  VALUES('". strtoupper($_POST["unique_id"]) ."',
                                         '". htmlentities($_POST["titel"], ENT_QUOTES) ."',
                                         '". htmlentities($_POST["omschrijving"], ENT_QUOTES) ."',
                                         '". $_SESSION[$session_var]->user_id ."',
                                         '". $_POST["sel_type"] ."',
                                         '". changeDate2EU($_POST["start"]) ."',
                                         '". changeDate2EU($_POST["einde"]) ."',
                                         '". changeDate2EU($_POST["start"]) . " " . Date('H:i:s') ."')"; 
    }else
    {
        $q_ins = "INSERT INTO kal_opdrachten(unique_id,
                                         titel,
                                         omschrijving,
                                         van_user_id,
                                         type,
                                         start,
                                         stop) 
                                  VALUES('". strtoupper($_POST["unique_id"]) ."',
                                         '". htmlentities($_POST["titel"], ENT_QUOTES) ."',
                                         '". htmlentities($_POST["omschrijving"], ENT_QUOTES) ."',
                                         '". $_SESSION[$session_var]->user_id ."',
                                         '". $_POST["sel_type"] ."',
                                         '". changeDate2EU($_POST["start"]) ."',
                                         '". changeDate2EU($_POST["einde"]) ."')"; 
    }
    
    
    
    mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
    $opdracht_id = mysqli_insert_id($conn);
    
    $mail_output = "";
    $mail_output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $mail_output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
    $mail_output .= '<head></head><body>';
    $mail_output .= "<table style='width:800px;'>";
    $mail_output .= "<tr><td>";
    $mail_output .= "You have got a new assignment. <a href='http://www.solarlogs.be/esc/interne_opdrachten.php'> Click here</a><br /><br />";
    $mail_output .= "Title : " . $_POST["titel"] . "<br /><br />";
    $mail_output .= "Description : " . $_POST["omschrijving"]  . "<br />";
    $mail_output .= "</td></tr>";
    $mail_output .= "</table>";
    $mail_output .= '</body></html>';
    
    // opslaan van de uitvoerders van de opdracht
    foreach( $_POST["sel_voor"] as $user_id )
    {
        $q_ins = "INSERT INTO kal_opdrachten_users(opdracht_id,
                                                   user_id) 
                                            VALUES(". $opdracht_id .",
                                                   ". $user_id .")";
                                                   
        mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
        
        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $user_id));
        
        // mail versturen naar de personen voor wie deze opdracht is.
        $mail = new PHPMailer();
        $mail->From     = "noreply@solarlogs.be"; 
        $mail->FromName = "European Solar Challenge";
        $mail->Host     = "192.168.1.250";
        $mail->Mailer   = "smtp";
        $mail->AddAddress( $user->email );
        $mail->IsHTML(true);// send as HTML
        $mail->MsgHTML($mail_output); 
        $mail->Subject = "New assignment";
        $mail->SMTPAutoTLS = false;    
        $mail->Send();
    }
    
    if( count( $_FILES["bijlage"]["name"] ) > 0 )
    {
        for( $i=0;$i <= count( $_FILES["bijlage"]["name"] ); $i++ )
        {
            $target_path = "opdrachten/" . $opdracht_id . "/";
            @mkdir( $target_path );
            $target_path = $target_path . basename( $_FILES['bijlage']['name'][$i]); 
            
            if(move_uploaded_file($_FILES['bijlage']['tmp_name'][$i], $target_path)) 
            {
                $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,
                                                         cf_soort,
                                                         cf_file) 
                                                  VALUES(".$opdracht_id.",
                                                         'int_opdrachten',
                                                         '".basename( $_FILES['bijlage']['name'][$i])."') ";
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
            }
        }
    }
    
    ?>
	<meta http-equiv="refresh" content="0;URL=interne_opdrachten.php" />
	<?php
	die();
}

//include "../cron/cron_int_opdrachten.php";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Internal assignments<?php include "inc/erp_titel.php" ?></title>

<link rel="SHORTCUT ICON" href="favicon.ico" />

<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />

<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="js/jquery.validate.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>

<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript">
$('span[class="clickShowHide"]').live('click',function(){
    $(this).next().next().toggle();
});


var XMLHttpRequestObject2 = false;

try{
	XMLHttpRequestObject2 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject2 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject2 = false
	}
 
	if(!XMLHttpRequestObject2 && window.XMLHttpRequest){
		XMLHttpRequestObject2 = new XMLHttpRequest();
	}
}

function getZoekResults()
{
    var url = "interne_opdrachten.php";
    var params = "actie=Search&waarde=" + document.getElementById("string").value;
    
    XMLHttpRequestObject2.open("POST", url, true);

    XMLHttpRequestObject2.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=utf-8");
    XMLHttpRequestObject2.setRequestHeader("Content-length", params.length);
    XMLHttpRequestObject2.setRequestHeader("Connection", "close");

    XMLHttpRequestObject2.onreadystatechange = function() {//Call a function when the state changes.
        if (XMLHttpRequestObject2.readyState == 4 && XMLHttpRequestObject2.status == 200) {
            document.getElementById("div_zoek_res").innerHTML = XMLHttpRequestObject2.responseText;
            
            //document.getElementById("tabd-2").innerHTML = XMLHttpRequestObject2.responseText;
            //document.getElementById("ui-tabs-1").innerHTML = XMLHttpRequestObject1.responseText;            
        }else
        {
            document.getElementById("div_zoek_res").innerHTML = "<img src='images/indicator.gif' alt='loading...' /> Loading ...";
        }
    }
    XMLHttpRequestObject2.send(params);
    
}


var XMLHttpRequestObject1 = false;

try{
	XMLHttpRequestObject1 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject1 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject1 = false
	}
 
	if(!XMLHttpRequestObject1 && window.XMLHttpRequest){
		XMLHttpRequestObject1 = new XMLHttpRequest();
	}
}



function getAfgewerkte_opdrachten(dit)
{
	datasource = "ajax/interne_opdrachten/afgewerkte2.php?user=" + dit.value;

	if(XMLHttpRequestObject1){
		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200){
                document.getElementById("ui-tabs-1").innerHTML = XMLHttpRequestObject1.response;
			}else
            {
                document.getElementById("ui-tabs-1").innerHTML = "<img src='images/indicator.gif' alt='loading...' /> Loading ...";
            }
    	}
		
		XMLHttpRequestObject1.send(null);
	}
}

$(document).ready(function(){
	$("#frm_nieuw").validate();
    
    $( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?>,
        show: function( e,ui )
        {
            if(ui.index == 2){
                $( ui.panel ).html("<img src='images/indicator.gif' alt='loading...' /> Loading ...");
            }
        } 
    });
});

jQuery(document).ready(function() {
    $( ".datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
});

function showHide(cus_id)
{
    if( document.getElementById("id_" + cus_id).style.display == "none" )
    {
        document.getElementById("id_" + cus_id).style.display = "block";
    }else
    {
        document.getElementById("id_" + cus_id).style.display = "none";
    }
}

</script>
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
tinyMCE.init({
        // General options
        mode : "textareas",
        theme : "advanced",
        plugins : "autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

        // Theme options
        //theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect,|,tablecontrols,|,hr,removeformat,visualaid,|,charmap,iespell",
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        //theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,charmap,emotions,iespell,media",
        //theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,

        // Skin options
        skin : "o2k7",
        skin_variant : "silver",

        // Example content CSS (should be your site CSS)
        //content_css : "css/example.css",

        // Drop lists for link/image/media/template dialogs
        template_external_list_url : "js/template_list.js",
        external_link_list_url : "js/link_list.js",
        external_image_list_url : "js/image_list.js",
        media_external_list_url : "js/media_list.js",
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

<div id='pagewrapper'><?php include('inc/header.php'); ?>
	<h1>Internal assignments</h1>
	
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">My assignments</a></li>
            <li><a href="#tabs-2">New assignment</a></li>
            <li><a href="ajax/interne_opdrachten/afgewerkte.php">Finished assignments</a></li>
            <?php
            
            if( isset( $_GET["tab_id"] ) && isset( $_GET["opdracht_id"] ) )
            {
            ?>
            
			<li><a href="#tabs-5">Info</a></li>
            <?php
            
            }
            
            ?>
            
            <li><a href="#tabs-6">Search</a></li>
		</ul>
		<div id="tabs-1">
        
        <?php
        
            $aantal = 0;
        
            $q = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE user_id = " . $_SESSION[$session_var]->user_id . " AND status != 'done'");
            
            if( isset( $_POST["sel1"] ) && is_numeric($_POST["sel1"]) )
            {
                $q = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE user_id = " . $_POST["sel1"] . " AND status != 'done'");
            }
            
            while( $rij = mysqli_fetch_object($q) )
            {
                $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $rij->opdracht_id . "  AND start <= '". date('Y-m-d') ."'"));
                if( $opdracht )
                {
                    $aantal++;
                }
            }
            
            $tmp_aantal = "";
            if( !isset( $_POST["sel1"] ) || ( isset($_POST["sel1"]) && is_numeric($_POST["sel1"]) ) )
            {
                $tmp_aantal = " (".$aantal.")";
            }
            
            $list_arr = array();
            $list_arr["mijn"] = "My assignments";
            $list_arr["toekomst"] = "Assignments planned in the future";
            $list_arr["toegekende"] = "Assigned assignments";
            $list_arr["afg_toegekende"] = "Finished assigned assignments";
            
            echo "<strong>Summary assignments ".$tmp_aantal."</strong><br /><br />";
            
            echo "<form method='post' action='interne_opdrachten.php'>";
            echo " View : <select name='sel1' id='sel1'>";
            
            if( $_SESSION[$session_var]->group_id == 1 )
            {
                $list_arr["all"] = "All";
                
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' ORDER BY voornaam");
                    
                while( $rij = mysqli_fetch_object($q_zoek) )
                {
                    $list_arr[$rij->user_id] = $rij->voornaam . " " . $rij->naam;
                }
            }    
                
            foreach( $list_arr as $index => $waarde )
            {
                $sel = "";
                
                if( isset( $_POST["sel1"] ) && $_POST["sel1"] == $index )
                {
                    $sel = " selected='selected' ";
                }
                
                echo "<option ". $sel ." value='". $index ."'>". $waarde ."</option>";
            }
            
            echo "<select>";
            echo "<input type='submit' name='toon' id='toon' value='Show' />";
            echo "</form><br />";
            
        /*
        else
        {
            echo "<strong>Overzicht van mijn opdrachten</strong><br /><br />";
        }
        */
        
        if( isset( $_POST["sel1"] ) && $_POST["sel1"] == "toegekende" )
        {
            $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE start <= '". date('Y-m-d') ."' AND van_user_id = " . $_SESSION[$session_var]->user_id . " ORDER BY start");
            
            $toegekende_opdrachten = array();
            
            while( $opdracht = mysqli_fetch_object($q_zoek) )
            {
                $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id));
                
                if( $aantal_open > 0 )
                {
                    $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
                        
                    while( $opus = mysqli_fetch_object($q_opdracht_users)  )
                    {
                        
                        
                        $chk = "";
                        
                        if( $opus->status != 'done' )
                        {
                            $toegekende_opdrachten[ $opus->user_id ][] = $opdracht->id;
                        }
                    }
                }
            }
            
            ksort( $toegekende_opdrachten );
            
            foreach( $toegekende_opdrachten as $user_id => $opdracht_arr )
            {
                $aantal = 0;
                $q = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE user_id = " . $user_id . " AND status != 'done'");
                while( $rij = mysqli_fetch_object($q) )
                {
                    $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $rij->opdracht_id . "  AND start <= '". date('Y-m-d') ."'"));
                    if( $opdracht )
                    {
                        $aantal++;
                    }
                }
                
                $aantal_open = 0;
                $q = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE user_id = " . $user_id . " AND status != 'done'");
                while( $rij = mysqli_fetch_object($q) )
                {
                    $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $rij->opdracht_id . "  AND start <= '". date('Y-m-d') ."' AND van_user_id = '". $_SESSION[$session_var]->user_id ."'"));
                    if( $opdracht )
                    {
                        $aantal_open++;
                    }
                }
                
                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $user_id));
                
                
                $q123 = "SELECT * FROM kal_opdrachten as a, kal_opdrachten_users as b
                        WHERE a.id = b.opdracht_id
                        AND b.status != 'done'
                        AND user_id = ". $user_id ."
                        ORDER BY a.start
                        LIMIT 1";
                
                $q12 = mysqli_query($conn, $q123) or die( mysqli_error($conn) . " " . __LINE__ ." " . $q123 );
                $opdracht = mysqli_fetch_object($q12);
                
                $now = time(); // or your date as well
                $your_date = strtotime( $opdracht->start );
                $datediff = $now - $your_date;
                $aantal_dagen = floor($datediff/(60*60*24));
                
                // overwrite aantal dagen wanneer het een recurrente opdracht is
                if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
                {
                    $your_date = strtotime( substr( $opdracht->datetime,0,10 ) );
                    $datediff = $now - $your_date;
                    $aantal_dagen = floor($datediff/(60*60*24));
                }
                
                $q123 = "SELECT * FROM kal_opdrachten as a, kal_opdrachten_users as b
                        WHERE a.id = b.opdracht_id
                        AND b.status = 'done'
                        AND user_id = ". $user_id ."
                        ORDER BY a.start";
                
                $q12 = mysqli_query($conn, $q123) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q123 );
                
                $aant_sec = 0;
                $aant_opdracht = 0;
                while( $opdracht = mysqli_fetch_object($q12) )
                {
                    $your_date = strtotime( $opdracht->datetime );
                    if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" || $opdracht->start == "0000-00-00" )
                    {
                        $your_date = strtotime( $opdracht->datetime ) + 30600;    
                    }
                    
                    //echo "<br />" . $opdracht->type . " " . $opdracht->start . " " . $opdracht->datetime . " " . $opdracht->done_time;
                    
                    if( $opdracht->done_time != "0000-00-00 00:00:00" )
                    {
                        $stop_date = strtotime( $opdracht->done_time );
                        $dif = $stop_date - $your_date;
                        $aant_sec += $dif;
                        $aant_opdracht++;
                    }
                }
                
                //echo "<br />" . $aant_sec . " " . $aant_opdracht;
                
                $tmp = ($aant_sec / $aant_opdracht);
                
                $gem_tijd = $tmp; 
                
                
                
                // toegekende alleen
                
                $q123 = "SELECT * FROM kal_opdrachten as a, kal_opdrachten_users as b
                        WHERE a.id = b.opdracht_id
                        AND user_id = ". $user_id ."
                        AND van_user_id = '". $_SESSION[$session_var]->user_id ."'
                        ORDER BY a.start";
                
                //echo "<br />" . $q123;
                
                // AND b.status = 'done'
                
                $q12 = mysqli_query($conn, $q123) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q123 );
                
                $aant_sec = 0;
                $aant_opdracht = 0;
                while( $opdracht = mysqli_fetch_object($q12) )
                {
                    $your_date = strtotime( $opdracht->datetime );
                    if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" || $opdracht->start == "0000-00-00" )
                    {
                        $your_date = strtotime( $opdracht->datetime ) + 30600;    
                    }
                    
                    //echo "<br />" . $opdracht->type . " " . $opdracht->start . " " . $opdracht->datetime . " " . $opdracht->done_time;
                    
                    if( $opdracht->status != "done" )
                    {
                        $opdracht->done_time = date("Y-m-d H:i:s");
                    }
                    
                    if( $opdracht->done_time != "0000-00-00 00:00:00" )
                    {
                        //echo "<br />" . $opdracht->done_time;
                        
                        $stop_date = strtotime( $opdracht->done_time );
                        
                        
                        
                        $dif = $stop_date - $your_date;
                        $aant_sec += $dif;
                        $aant_opdracht++;
                        
                        /*
                        echo "<br />stop" . $stop_date . " start" . $your_date;
                        echo "<br />" . date("d-m-Y H:i:s", $stop_date) . " " . date("d-m-Y H:i:s", $your_date);
                        echo "<br />" . $aant_opdracht;
                        */
                    }
                }
                
                //echo "<br />" . $aant_sec . " " . $aant_opdracht;
                
                $tmp = ($aant_sec / $aant_opdracht);
                
                $gem_tijd_open = $tmp; 
                echo "<br /><h1>" . $user->naam . " " . $user->voornaam . "</h1>";
                
                echo "<table><tr><td>";
                echo "Oldest assignment :</td><td>" . $aantal_dagen . " days </td></tr>";
                echo "<tr><td>Number of unfinished assignments : </td><td>" . $aantal . " </td></tr>";
                echo "<tr><td>Number of assigned unfinished assignments : </td><td>" . $aantal_open . " </td></tr>";
                echo "<tr><td>Average time of the assignments : </td><td>" . secs_to_h( $gem_tijd );
                echo "<tr><td>Average time of assigned assignments : </td><td>" . secs_to_h( $gem_tijd_open );
                echo "</td></tr></table>";
                
                echo "<br /><span class='clickShowHide' style='cursor:pointer;' > <strong>Show/Hide assignments</strong> </span><br />";
                
                echo "<div class='hideOpdrachten' style='display:none;' >";
                
                foreach( $opdracht_arr as $nr => $opdracht_id )
                {
                    //echo "<br />" . $nr . " " . $opdracht_id;
                    $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $opdracht_id));
                    
                    // BEGIN
                    echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
                    echo "<tr>";
                    echo "<td colspan='2' bgcolor='gray'>
                    
                    <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                    <tr><td width='25%'>";
                    
                    $tab_id = 3;
                    
                    /*
                    if( $_SESSION[$session_var]->group_id == 1 )
                    {
                        $tab_id = 4;    
                    }
                    */
                    
                    echo "<a href='interne_opdrachten.php?tab_id=".$tab_id."&opdracht_id=".$opdracht->id."'>";
                    echo "<img alt='Open assignment' title='Open assignment' src='images/info.png' width='20' height='20' /></a>";
                    
                    if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id )
                    {
                        echo "<form method='post' name='frm_delete' id='frm_delete' style='display:inline;' >";
                        echo "<input onclick='return confirm(\"Delete assignment?\")' type='image' src='images/delete.png' name='delete_opdracht' id='delete_opdracht' width='20' height='20' value='". $opdracht->id ."' />";
                        echo "<input type='hidden' name='delete_opdracht' id='delete_opdracht' value='". $opdracht->id ."' />";
                        echo "</form>";
                    }
                    
                    
                    echo "</td>
                    <td align='center'><b style='color:white;' >";
                    
                    echo "<table cellpadding='0' cellspacing='0' border='0'><tr><td>";
                    
                    echo "<img src='images/openclose.png' alt='Detailview' title='Detailview' onclick='showHide(\"". $opdracht->id ."\");' />&nbsp;";
                    
                    echo "</td><td>";
                    
                    echo html_entity_decode($opdracht->titel)."</b>";
                    
                    echo "</td></tr></table>";
                    
                    echo "</td>";
                    echo "<td width='25%' style='color:white;'>";
                    
                    /* tabel */
                    echo "<table cellpadding='0' cellspacing='0' width='100%' ><tr><td>";
                    
                    echo "ID : ". $opdracht->unique_id;
                    
                    echo "</td><td align='right' >";
                    
                    $now = time(); // or your date as well
                    $your_date = strtotime( $opdracht->start );
                    $datediff = $now - $your_date;
                    $aantal_dagen = floor($datediff/(60*60*24));
                    
                    // overwrite aantal dagen wanneer het een recurrente opdracht is
                    if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
                    {
                        $your_date = strtotime( substr( $opdracht->datetime,0,10 ) );
                        $datediff = $now - $your_date;
                        $aantal_dagen = floor($datediff/(60*60*24));
                    }
                    
                    echo "<span title='This assignment is open for ". $aantal_dagen ." days'>";
                    
                    echo "#";
                    echo $aantal_dagen;
                    
                    echo "</span>";
                    echo "</td></tr></table>";
                    /* einde tabel */
                    
                    
                    
                    echo "</td></tr></table></td>";
                    echo "</tr>";
                    echo "</table>";
                    
                    echo "<div style='display:none;' id='id_". $opdracht->id ."' >";
                    echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1'  >";
                    echo "<tr>";
                    echo "<td width='50%'>";
                    echo "<strong>Start : </strong>" . changeDate2EU( $opdracht->start );
                    echo "</td>";
                    
                    echo "<td width='50%'>";
                    
                    if( $opdracht->stop != $opdracht->start && $opdracht->stop != '0000-00-00' )
                    {
                        echo "<strong>Deadline : </strong>" . changeDate2EU( $opdracht->stop );
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td colspan='2'><strong>Assigned to : </strong>";
                    
                    // ophalen van al de gebruikers die aan deze opdracht gekoppeld zijn.
                    
                    $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
                    
                    $u = "";
                    while( $opus = mysqli_fetch_object($q_opdracht_users)  )
                    {
                        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                        
                        $chk = "";
                        
                        if( $opus->status == 'done' )
                        {
                            $chk = " <strong>(finished)</strong>";
                        }
                        
                        $u .= $user->voornaam . " " . $user->naam . $chk . ", ";
                    }
                    
                    echo substr( $u, 0, -2 );
                    
                    
                    if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
                    {
                        echo " - op : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
                    }
                    
                    $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
                    echo "<br /><strong>Created by : </strong>" . $van->voornaam . " " . $van->naam;
                    echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];
                    echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td colspan='2'>".html_entity_decode($opdracht->omschrijving)."</td>";
                    echo "</tr>";
                    
                    $eerst_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE unique_id = '" . $opdracht->unique_id . "' ORDER BY 1 ASC LIMIT 1"));
                    $eerst_opdracht_id = $eerst_opdracht->id;
                    
                    $exclude_arr = array();
                    
                    $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten' ");
                    
                    if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                    {
                        echo "<tr>";
                        echo "<td colspan='2'><strong>Attachments :</strong>";
                        
                        while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                        {
                            $exclude_arr[ $attach->cf_id ] = $attach->cf_id;
                            echo "<br /><a href='opdrachten/".$opdracht->id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    
                    $toon_titel = 0;                    
                    $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
                    if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                    {
                        while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                        {
                            if( !in_array($attach->cf_id, $exclude_arr) )
                            {
                                $toon_titel = 1;
                            }
                        }
                    }
                    
                    if( $toon_titel == 1 )
                    {
                        $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
                        
                        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                        {
                            echo "<tr>";
                            echo "<td colspan='2'><strong>Attachments :</strong>";
                            
                            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                            {
                                if( !in_array($attach->cf_id, $exclude_arr) )
                                {
                                    echo "<br /><a href='opdrachten/".$eerst_opdracht_id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";    
                                }
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    
                    // nakijken of er nog andere gebruikers aan deze opdracht gekoppeld zijn.
                    $q1 = "SELECT * 
                              FROM kal_opdrachten_users 
                             WHERE opdracht_id = " . $opdracht->id . " 
                               AND ( ready != ''
                               OR notfinished != '' ) ";
                               
                    $q_opdracht_info = mysqli_query($conn, $q1) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q1 );
                    
                    if( mysqli_num_rows($q_opdracht_info) > 0 )
                    {
                        echo "<tr><td colspan='2'>";
                        
                        $k=0;
                        while( $opus = mysqli_fetch_object($q_opdracht_info) )
                        {
                            if( $k == 0 )
                            {
                                echo "Below you can find the info of the other persons with the same assignment :<br />";
                            }
                            
                            $k++;
                            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                            
                            echo "<br /><b><u>";
                            echo $user->voornaam . " " . $user->naam;
                            echo "</u></b>";
                            echo "<br /><strong>What is finished?</strong><br />";
                            echo html_entity_decode( $opus->ready );
                            
                            
                            echo "<br /><br /><br /><strong>What needs to be done?</strong><br />";
                            echo html_entity_decode($opus->notfinished);
                            
                            if( !empty( $opus->status ))
                            {
                                echo "<b>";
                            }
                            
                            echo "<br /><br />Status : " . $opus->status;
                            
                            if( !empty( $opus->status ))
                            {
                                echo "</b>";
                            }
                            
                            echo "<br /><hr/>";
                        }
                        
                        echo "</td></tr>";
                        
                    }
                    
                    echo "</table></div><br />";
                    // EINDE
                    
                }
                echo "</div>";
                
                echo "<hr/>";
            }
            
        }else
        {
            if( !isset( $_POST["sel1"] ) || ($_POST["sel1"] != "toekomst" ) )
            {
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE start <= '". date('Y-m-d') ."' ORDER BY start");
            }
            
            if( isset( $_POST["sel1"] ) && $_POST["sel1"] == "toekomst" )
            {
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE start > '". date('Y-m-d') ."' ORDER BY start");    
            }
            
            //if( isset( $_POST["sel1"] ) && ( $_POST["sel1"] == "toegekende" || $_POST["sel1"] == "afg_toegekende" ) )
            if( isset( $_POST["sel1"] ) && $_POST["sel1"] == "afg_toegekende" )
            {
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE start <= '". date('Y-m-d') ."' AND van_user_id = " . $_SESSION[$session_var]->user_id . " ORDER BY start");
            }
            
            if( mysqli_num_rows($q_zoek) > 0 )
            {
                
                //$i=0;
                while( $opdracht = mysqli_fetch_object($q_zoek) )
                {
                    // nakijken of er 1 of meerde gebruikers de opdracht nog niet hebben afgesloten.
                    
                    $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_SESSION[$session_var]->user_id ." AND status != 'done'"));
                    
                    if( !isset( $_POST["sel1"] ) || (isset($_POST["sel1"]) && $_POST["sel1"] == "mijn" ) )
                    {
                        $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_SESSION[$session_var]->user_id ." AND status != 'done'"));
                    }
                    
                    if( isset( $_POST["sel1"] ) && ( $_POST["sel1"] == "all" || $_POST["sel1"] == "toegekende" ) )
                    {
                        $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND status != 'done' ORDER BY user_id"));
                    }
                    
                    if( isset( $_POST["sel1"] ) && ( $_POST["sel1"] == "all" || $_POST["sel1"] == "afg_toegekende" ) )
                    {
                        $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND status = 'done'"));
                    }
                    
                    if( isset( $_POST["sel1"] ) && is_numeric($_POST["sel1"]) )
                    {
                        $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_POST["sel1"] ." AND status != 'done'"));
                    }
                    
                    if( isset( $_POST["sel1"] ) && ( $_POST["sel1"] == "toekomst" ) )
                    {
                        $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_SESSION[$session_var]->user_id ." AND status != 'done'"));
                    }
                    
                    if( $aantal_open > 0 )
                    {
                        echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
                        echo "<tr>";
                        echo "<td colspan='2' bgcolor='gray'>
                        
                        <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                        <tr><td width='25%'>";
                        
                        $tab_id = 3;
                        
                        /*
                        if( $_SESSION[$session_var]->group_id == 1 )
                        {
                            $tab_id = 4;    
                        }
                        */
                        
                        echo "<a href='interne_opdrachten.php?tab_id=".$tab_id."&opdracht_id=".$opdracht->id."'>";
                        echo "<img alt='Open assignment' title='assignment' src='images/info.png' width='20' height='20' /></a>";
                        
                        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id )
                        {
                            echo "<form method='post' name='frm_delete' id='frm_delete' style='display:inline;' >";
                            echo "<input onclick='return confirm(\"Delete assignment?\")' type='image' src='images/delete.png' name='delete_opdracht' id='delete_opdracht' width='20' height='20' value='". $opdracht->id ."' />";
                            echo "<input type='hidden' name='delete_opdracht' id='delete_opdracht' value='". $opdracht->id ."' />";
                            echo "</form>";
                        }
                        
                        
                        echo "</td>
                        <td align='center'><b style='color:white;' >";
                        
                        echo "<table cellpadding='0' cellspacing='0' border='0'><tr><td>";
                        
                        echo "<img src='images/openclose.png' alt='Detailview' title='Detailview' onclick='showHide(\"". $opdracht->id ."\");' />&nbsp;";
                        
                        echo "</td><td>";
                        
                        echo html_entity_decode($opdracht->titel)."</b>";
                        
                        echo "</td></tr></table>";
                        
                        echo "</td>";
                        echo "<td width='25%' style='color:white;'>";
                        
                        /* tabel */
                        echo "<table cellpadding='0' cellspacing='0' width='100%' ><tr><td>";
                        
                        echo "ID : ". $opdracht->unique_id;
                        
                        echo "</td><td align='right' >";
                        
                        $now = time(); // or your date as well
                        $your_date = strtotime( $opdracht->start );
                        $datediff = $now - $your_date;
                        $aantal_dagen = floor($datediff/(60*60*24));
                        
                        // overwrite aantal dagen wanneer het een recurrente opdracht is
                        if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
                        {
                            $your_date = strtotime( substr( $opdracht->datetime,0,10 ) );
                            $datediff = $now - $your_date;
                            $aantal_dagen = floor($datediff/(60*60*24));
                        }
                        
                        echo "<span title='This assignment is open for ". $aantal_dagen ." days'>";
                        
                        echo "#";
                        echo $aantal_dagen;
                        
                        echo "</span>";
                        echo "</td></tr></table>";
                        /* einde tabel */
                        
                        
                        
                        echo "</td></tr></table></td>";
                        echo "</tr>";
                        echo "</table>";
                        
                        echo "<div style='display:none;' id='id_". $opdracht->id ."' >";
                        echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1'  >";
                        echo "<tr>";
                        echo "<td width='50%'>";
                        echo "<strong>Start : </strong>" . changeDate2EU( $opdracht->start );
                        echo "</td>";
                        
                        echo "<td width='50%'>";
                        
                        if( $opdracht->stop != $opdracht->start && $opdracht->stop != '0000-00-00' )
                        {
                            echo "<strong>Deadline : </strong>" . changeDate2EU( $opdracht->stop );
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td colspan='2'><strong>Assigned to : </strong>";
                        
                        // ophalen van al de gebruikers die aan deze opdracht gekoppeld zijn.
                        
                        $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
                        
                        $u = "";
                        while( $opus = mysqli_fetch_object($q_opdracht_users)  )
                        {
                            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                            
                            $chk = "";
                            
                            if( $opus->status == 'done' )
                            {
                                $chk = " <strong>(finished)</strong>";
                            }
                            
                            $u .= $user->voornaam . " " . $user->naam . $chk . ", ";
                        }
                        
                        echo substr( $u, 0, -2 );
                        
                        
                        if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
                        {
                            echo " - op : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
                        }
                        
                        $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
                        echo "<br /><strong>Created by : </strong>" . $van->voornaam . " " . $van->naam;
                        echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td colspan='2'>".html_entity_decode($opdracht->omschrijving)."</td>";
                        echo "</tr>";
                        
                        $eerst_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE unique_id = '" . $opdracht->unique_id . "' ORDER BY 1 ASC LIMIT 1"));
                        $eerst_opdracht_id = $eerst_opdracht->id;
                        
                        $exclude_arr = array();
                        
                        $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten' ");
                        
                        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                        {
                            echo "<tr>";
                            echo "<td colspan='2'><strong>Attachments :</strong>";
                            
                            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                            {
                                $exclude_arr[ $attach->cf_id ] = $attach->cf_id;
                                echo "<br /><a href='opdrachten/".$opdracht->id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        
                        $toon_titel = 0;                    
                        $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
                        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                        {
                            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                            {
                                if( !in_array($attach->cf_id, $exclude_arr) )
                                {
                                    $toon_titel = 1;
                                }
                            }
                        }
                        
                        if( $toon_titel == 1 )
                        {
                            $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
                            
                            if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                            {
                                echo "<tr>";
                                echo "<td colspan='2'><strong>Attachments :</strong>";
                                
                                while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                                {
                                    if( !in_array($attach->cf_id, $exclude_arr) )
                                    {
                                        echo "<br /><a href='opdrachten/".$eerst_opdracht_id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";    
                                    }
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        
                        // nakijken of er nog andere gebruikers aan deze opdracht gekoppeld zijn.
                        
                        $q1 = "SELECT * 
                                  FROM kal_opdrachten_users 
                                 WHERE opdracht_id = " . $opdracht->id . " 
                                   AND ( ready != ''
                                   OR notfinished != '' ) ";
                                   
                        $q1 = "SELECT * 
                                  FROM kal_opdrachten_users 
                                 WHERE opdracht_id = " . $opdracht->id;
                                   
                        $q_opdracht_info = mysqli_query($conn, $q1) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q1 );
                        
                        //echo mysqli_num_rows($q_opdracht_info);
                        
                        if( mysqli_num_rows($q_opdracht_info) > 0 )
                        {
                            echo "<tr><td colspan='2'>";
                            
                            $k=0;
                            while( $opus = mysqli_fetch_object($q_opdracht_info) )
                            {
                                if( $k == 0 )
                                {
                                    echo "Below you can find the information of other persons with the same assignment :<br />";
                                }
                                
                                $k++;
                                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                                
                                echo "<br /><b><u>";
                                echo $user->voornaam . " " . $user->naam;
                                echo "</u></b>";
                                echo "<br /><strong>What is finished?</strong><br />";
                                echo html_entity_decode( $opus->ready );
                                
                                // uitlezen van de documenten. AAAAAAAAAAAAAAAAAAA
                                echo "Documenten :";
                                //echo '<input type="file" name="bijlagen_klaar[]" id="bijlagen_klaar" multiple="" />';
                                
                                $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten_klaar' AND cf_van_distri_offerte = '". $opus->user_id ."' ");
                            
                                if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                                {
                                    echo "<br /><br /><strong>Attachments :</strong>";
                                    
                                    while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                                    {
                                        echo "<br />";
                                        echo "<a href='interne_opdrachten.php?tab_id=". $_GET["tab_id"] ."&opdracht_id=". $_GET["opdracht_id"] ."&rm_file_id=".$attach->cf_id."&soort=klaar' onclick='return confirm(\"Bijlage verwijderen?\");' > <img src='images/delete.png'> </a>";
                                        echo "<a href='opdrachten/".$opdracht->id  ."/klaar/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                                    }
                                }
                                
                                
                                echo "<br /><br /><br /><strong>What needs to be done?</strong><br />";
                                echo html_entity_decode($opus->notfinished);
                                
                                if( !empty( $opus->status ))
                                {
                                    echo "<b>";
                                }
                                
                                echo "<br /><br />Status : " . $opus->status;
                                
                                if( !empty( $opus->status ))
                                {
                                    echo "</b>";
                                }
                                
                                echo "<br /><hr/>";
                            }
                            
                            echo "</td></tr>";
                            
                        }
                        
                        echo "</table></div><br />";
                    }
                }
            }
        }
        
        ?>
        
        
        
		</div>
        
        <div id="tabs-2">
            <strong>Add new assignment :</strong><br /><br />
            <form method="post" name="frm_nieuw" id="frm_nieuw" action="interne_opdrachten.php" enctype="multipart/form-data">
            <table>
            <tr>
                <td>Unique ID</td>
                <td>
                
                <?php
                /*
                echo "<pre>";
                var_dump( $_SESSION[$session_var] );
                echo "</pre>";
                */
                $v = explode(" ", $_SESSION[$session_var]->voornaam );
                $a1 = explode(" ", $_SESSION[$session_var]->naam );
                
                $unique_id = "";
                
                if( count($v) > 0 )
                {
                    foreach( $v as $a )
                    {
                        $unique_id .= substr($a,0,1);
                    }
                }
                
                if( count($a1) > 0 )
                {
                    foreach( $a1 as $a )
                    {
                        $unique_id .= substr($a,0,1);
                    }
                }
                
                //echo $unique_id;
                
                for($i=1;$i<300000;$i++)
                {
                    $number = $unique_id . $i;
                    
                    $q_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE unique_id = '". $number ."'"));
                    
                    if( $q_zoek == 0 )
                    {
                        
                        break;
                    }
                }
                
                $number = strtoupper( $number );
                
                echo $number;
                
                echo "<input type='hidden' name='unique_id' id='unique_id' value='". $number ."' />";
                
                ?>
                
                </td>
            </tr>
            
            <tr>
                <td valign="top" width="200" >Assign to :</td>
                <td>
                
                <select multiple="multiple" size="10" name="sel_voor[]" id="sel_voor" class="required" >
                <?php
                
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' ORDER BY voornaam");
                
                while( $rij = mysqli_fetch_object($q_zoek) )
                {
                    echo "<option value='". $rij->user_id ."'>". $rij->voornaam . " " . $rij->naam ."</option>";
                }
                
                ?>
                
                </select>
                </td>
            </tr>
            
            <tr>
                <td width="200" >Title :</td>
                <td> <input type="text" class="required" size="89" name="titel" id="titel" /></td>
            </tr>
            
            <tr>
                <td valign="top" width="200" >Description :</td>
                <td> 
                <textarea name="omschrijving" id="omschrijving" ></textarea> 
                </td>
            </tr>
            
            <tr>
                <td valign="top" width="200" class="required" >Start date :</td>
                <td> <input style="width: 200px;" type="text" class="datum" name="start" id="start" value="<?php echo date("d-m-Y"); ?>" /></td>
            </tr>
            
            <tr>
                <td valign="top" width="200" >Deadline :</td>
                <td> <input style="width: 200px;" type="text" class="datum" name="einde" id="einde" /></td>
            </tr>
            
            <tr>
                <td valign="top" width="200" >Type of assignment :</td>
                <td> 
                
                <select name="sel_type" id="sel_type">
                    <option value="eenmaling">Once</option>
                    <option value="dagelijks_mw">Daily included weekends</option>
                    <option value="dagelijks_zw">Daily without weekends</option>
                    <option value="wekelijks">Weekly</option>
                    <option value="maandelijks">Montly</option>
                    <option value="jaarlijks">Annually</option>
                </select>
                
                </td>
            </tr>
            
            <tr>
                <td valign="top" width="200" >Attachments :</td>
                <td> <input type="file" name="bijlage[]" id="bijlage" multiple="" /></td>
            </tr>
            
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" name="opslaan" id="opslaan" value="Save" />
                </td>
            </tr>
            
            </table>
            </form>
		</div>
		
        <?php
            
        if( isset( $_GET["tab_id"] ) && isset( $_GET["opdracht_id"] ) )
        {
        ?>
        
		<div id="tabs-5">
        
 
        
        <?php
        
        // nakijken of deze opdracht bij de ingelogde gebruiker hoort.
        
        if( $_SESSION[$session_var]->group_id != 1 )
        {
            //echo "abc" . $_SESSION[$session_var]->user_id;
            
            //mysqli_query($conn)
            
        }
        
        $opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $_GET["opdracht_id"]));
        
        echo "<form method='post' action='interne_opdrachten.php' enctype='multipart/form-data' >";
        echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
        echo "<tr>";
        echo "<td colspan='2' bgcolor='gray'>
        
        <table width='100%' cellpadding='0' cellspacing='0' border='0'>
        <tr><td width='25%'>";
        
        
        echo "</td>
        <td align='center'><b style='color:white;' >";
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
        {
            echo "<input type='text' name='titel' id='titel' size='100' value='".html_entity_decode($opdracht->titel)."' />";
        }else
        {
            echo html_entity_decode($opdracht->titel);    
        }
        
        echo "</b></td>
        <td width='25%' style='color:white;'>ID : ". $opdracht->unique_id ."</td></tr></table></td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td width='50%'>";
        echo "<strong>Start : </strong>";
        
        
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
        {
            echo "<input type='text' class='datum' name='edit_start' id='edit_start' value='". changeDate2EU( $opdracht->start ) ."' />";
        }else
        {
            echo changeDate2EU( $opdracht->start );    
        }
        
        echo "</td>";
        
        echo "<td width='50%'>";
        
        if( $opdracht->stop != $opdracht->start && $opdracht->stop != '0000-00-00' )
        {
            if( $_SESSION[$session_var]->user_id ==$opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
            {
                echo "<strong>Deadline : </strong><input type='text' class='datum' name='edit_stop' id='edit_stop' value='".changeDate2EU( $opdracht->stop )."' />";
            }else
            {
                echo "<strong>Deadline : </strong>" . changeDate2EU( $opdracht->stop );    
            }
            
        }else
        {
            if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
            {
                echo "<strong>Deadline : </strong><input type='text' class='datum' name='edit_stop' id='edit_stop' value='' />";
            }
        }
        
        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td colspan='2'>";
        
        // ophalen van al de gebruikers die aan deze opdracht gekoppeld zijn.
        
        $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
        
        
        $users_arr = array();
        
        $u = "";
        while( $opus = mysqli_fetch_object($q_opdracht_users)  )
        {
            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
            
            $chk = "";
            
            if( $opus->status == 'done' )
            {
                $chk = " <strong>(finished)</strong>";
            }
            
            $u .= $user->voornaam . " " . $user->naam . $chk . ", ";
            
            $users_arr[ $user->user_id ] = $user->voornaam . " " . $user->naam . $chk;
            
        }
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
        {
            echo "<select name='users[]' size='10' id='users[]' multiple='multiple' style='width:200px;' >";
            
            foreach( $users_arr as $user_id => $naam )
            {
                echo "<option selected='selected' value='".$user_id."'>". $naam ."</option>";
            }
            
            
            $q_users = mysqli_query($conn, "SELECT * FROM kal_users WHERE group_id < 6");
            
            while( $rec = mysqli_fetch_object($q_users) )
            {
                if( !isset( $users_arr[ $rec->user_id ] ) )
                {
                    echo "<option value='".$rec->user_id."'>". $rec->voornaam . " " . $rec->naam ."</option>";
                } 
            }
            
            echo "</select>";
        }
        
        
        if( $_SESSION[$session_var]->user_id != $opdracht->van_user_id && $_SESSION[$session_var]->group_id != 1 )
        {
            echo substr( $u, 0, -2 );
        }
        
        if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
        {
            echo " - on : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
        }
        
        $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
        echo "<br /><strong>Created by : </strong>" . $van->voornaam . " " . $van->naam;
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
        {
            echo "<br /><strong>Type : </strong><select name='sel_type' id='sel_type'>";
            
            foreach( $types_arr as $index => $value )
            {
                $sel = "";
                
                if( $index == $opdracht->type )
                {
                    $sel = " selected='selected' ";
                }
                
                echo "<option ".$sel." value='".$index."'>" . $value . "</option>";
            }
            
            
            echo "</select>";
        }else{
            echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];    
        }
        
        
        
        echo "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td colspan='2'>";
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 )
        {
            echo "<textarea name='omschrijving_edit' id='omschrijving_edit'>".html_entity_decode($opdracht->omschrijving)."</textarea>";
        }else
        {
            echo html_entity_decode($opdracht->omschrijving); 
        }
        
        echo "</td>";
        echo "</tr>";
        
        
        //$q = "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten' AND cf_van_distri_offerte = '". $opdracht->van_user_id ."'";
        $q = "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten'";
        
        
        
        $q_zoek_bijlage = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q );
        
        if( mysqli_num_rows($q_zoek_bijlage) > 0 )
        {
            echo "<tr>";
            echo "<td colspan='2'><strong>Attachments :</strong>";
            
            while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
            {
                echo "<br />";
                echo "<a href='interne_opdrachten.php?tab_id=". $_GET["tab_id"] ."&opdracht_id=". $_GET["opdracht_id"] ."&rm_file_id=".$attach->cf_id."' onclick='return confirm(\"Bijlage verwijderen?\");' > <img src='images/delete.png'> </a>";
                echo "<a href='opdrachten/".$opdracht->id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "<tr><td colspan='2'>Add attachment : ";
        
        echo '<input type="file" name="bijlagen[]" id="bijlagen" multiple="" />';
        
        echo "</td></tr>";
        
        echo "</table><br />";
        
        $q = "SELECT * 
              FROM kal_opdrachten_users 
             WHERE opdracht_id = " . $_GET["opdracht_id"] . " 
               AND user_id = " . $_SESSION[$session_var]->user_id;
        
        $q_opdracht_info = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE . " " . $q ) ;
                                           
        
        if( mysqli_num_rows($q_opdracht_info) > 0 )
        {
            
            $opdracht_info = mysqli_fetch_object($q_opdracht_info);
            
            
            echo "<table>";
            echo "<tr>";
            echo "<td>";
            echo "<strong>What is finished?</strong><br />";
            
            echo "Documenten :";
            echo '<input type="file" name="bijlagen_klaar[]" id="bijlagen_klaar" multiple="" />';
            
            $q_zoek_bijlage = mysqli_query($conn, "SELECT * 
                                             FROM kal_customers_files 
                                            WHERE cf_cus_id = " . $opdracht->id . " 
                                              AND cf_soort = 'int_opdrachten_klaar' 
                                              AND cf_van_distri_offerte = '". $_SESSION[$session_var]->user_id ."' ");
        
            if( mysqli_num_rows($q_zoek_bijlage) > 0 )
            {
                echo "<br /><br /><strong>Attachments :</strong>";
                
                while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                {
                    echo "<br />";
                    echo "<a href='interne_opdrachten.php?tab_id=". $_GET["tab_id"] ."&opdracht_id=". $_GET["opdracht_id"] ."&rm_file_id=".$attach->cf_id."&soort=klaar' onclick='return confirm(\"Bijlage verwijderen?\");' > <img src='images/delete.png'> </a>";
                    echo "<a href='opdrachten/".$opdracht->id  ."/klaar/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                }
            }
            
            echo "<br /><br /><textarea name='wat_klaar' id='wat_klaar'>" . $opdracht_info->ready . "</textarea>";
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td><br /><br />";
            echo "<strong>What needs to be done?</strong><br />";
            echo "<textarea name='wat_nog' id='wat_nog'>" . $opdracht_info->notfinished . "</textarea>";
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td><br /><br /> Status : ";
            
            echo "<select name='status' id='status'>";
            echo "<option value=''> Not finished </option>";
            
            $sel = "";
            
            if( $opdracht_info->status == 'done' )
            {
                $sel = " selected='selected' ";
            }
            
            echo "<option " . $sel . " value='done'> Assignment finished - (close) </option>";
            
            echo "</select>";
            
            echo "</td>";
            echo "</tr>";
            
            
            echo "</table>";
        }
        
        if( $_SESSION[$session_var]->user_id == $opdracht->van_user_id || $_SESSION[$session_var]->group_id == 1 || 1 == 1 )
        { 
            echo "<input type='hidden' name='opus_id' id='opus_id' value='".$opdracht_info->id."' />";
            echo "<input type='hidden' name='opdracht_id' id='opdracht_id' value='".$_GET["opdracht_id"]."' />";
            echo "<input type='submit' name='edit_opdracht' id='edit_opdracht' value='Opslaan' />";    
        }
        
        echo "</form>";
        
        // nakijken of er nog andere gebruikers aan deze opdracht gekoppeld zijn.
        $q_opdracht_info = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $_GET["opdracht_id"] . " AND user_id != " . $_SESSION[$session_var]->user_id);
        
        if( mysqli_num_rows($q_opdracht_info) > 0 )
        {
            echo "<hr/><br />Below you can find the info of other persons with the same assignment :<br />";
            
            while( $opus = mysqli_fetch_object($q_opdracht_info) )
            {
                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                
                echo "<br /><b><u>";
                echo $user->voornaam . " " . $user->naam;
                echo "</u></b>";
                echo "<br /><strong>What is finished?</strong><br />";
                
                echo "Documents :";
                //echo '<input type="file" name="bijlagen_klaar[]" id="bijlagen_klaar" multiple="" />';
                
                $q_zoek_bijlage = mysqli_query($conn, "SELECT * 
                                                 FROM kal_customers_files 
                                                WHERE cf_cus_id = " . $opdracht->id . " 
                                                  AND cf_soort = 'int_opdrachten_klaar' 
                                                  AND cf_van_distri_offerte = '". $opus->user_id ."' ");
            
                if( mysqli_num_rows($q_zoek_bijlage) > 0 )
                {
                    echo "<br /><br /><strong>Attachments :</strong>";
                    
                    while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                    {
                        echo "<br />";
                        echo "<a href='interne_opdrachten.php?tab_id=". $_GET["tab_id"] ."&opdracht_id=". $_GET["opdracht_id"] ."&rm_file_id=".$attach->cf_id."&soort=klaar' onclick='return confirm(\"Bijlage verwijderen?\");' > <img src='images/delete.png'> </a>";
                        echo "<a href='opdrachten/".$opdracht->id  ."/klaar/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                    }
                }
                
                echo html_entity_decode( $opus->ready );
                
                
                echo "<br /><br /><br /><strong>What needs to be done?</strong><br />";
                echo html_entity_decode($opus->notfinished);
                
                if( !empty( $opus->status ))
                {
                    echo "<b>";
                }
                
                echo "<br /><br />Status : " . $opus->status;
                
                if( !empty( $opus->status ))
                {
                    echo "</b>";
                }
                
                echo "<br /><hr/>";
            }
            
        }
        
        
        ?>
        
		</div>
        <?php
        
        }
        
        ?>
		<div id="tabs-6">
        
        Search for : <input type="text" name="string" id="string" value="<?php if( isset( $_POST["string"] ) ) echo $_POST["string"]; ?>" />
        <input type="button" name="zoek" id="zoek" value="Search" onclick="getZoekResults();" />
        <input type="hidden" name="tab_id" id="tab_id" value="3" />
        <br />
        
        <div id="div_zoek_res"></div>
        
        </div>
	</div>
</div>

<center>
<?php 

include "inc/footer.php";

?>
</center>

</body>
</html>