<?php

session_start();
include "../../inc/db.php";
include "../../inc/functions.php";
include "../../inc/checklogin.php";




?>

<?php
        
if( $_SESSION["tcc_user"]->group_id == 1 )
{
    echo "<strong>Summary finished assignments</strong><br /><br />";
    
    echo "<form method='post' name='frm_afg' id='frm_afg' >";
    
    $list_arr["all"] = "Alles";
                
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE active = '1' ORDER BY voornaam");
        
    while( $rij = mysqli_fetch_object($q_zoek) )
    {
        $list_arr[$rij->user_id] = $rij->voornaam . " " . $rij->naam;
    }
    
    echo "Assignment done by : ";
    
    echo "<select name='sel_wie' id='sel_wie'  onchange='getAfgewerkte_opdrachten(this);' >";
    
    foreach( $list_arr as $id => $user )
    {
        echo "<option value='".$id."'>".$user."</option>";
    }
    
    echo "</select>";
    
    echo "</form><br /><br />";
    
}else
{
    echo "<strong>Summary of my finished assignments</strong><br /><br />";
}

$q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten ORDER BY start");

if( mysqli_num_rows($q_zoek) > 0 )
{
    //$i=0;
    while( $opdracht = mysqli_fetch_object($q_zoek) )
    {
        // nakijken of er 1 of meerde gebruikers de opdracht nog niet hebben afgesloten.
        
        if( $_SESSION["tcc_user"]->group_id == 1 )
        {
            if( !isset( $_POST["sel1"] ) || (isset($_POST["sel1"]) && $_POST["sel1"] == "mijn" ) )
            {
                $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_SESSION["tcc_user"]->user_id ." AND status = 'done'"));
            }
            
            if( isset( $_POST["sel1"] ) && $_POST["sel1"] == "all" )
            {
                $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND status = 'done'"));
            }
            
            if( isset( $_POST["sel1"] ) && is_numeric($_POST["sel1"]) )
            {
                $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_POST["sel1"] ." AND status = 'done'"));
            }
        }else
        {
            $aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = ". $_SESSION["tcc_user"]->user_id ." AND status = 'done'"));
        }
        
        //$aantal_open = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND status = 'done'"));
        
        if( $aantal_open > 0 )
        {
            echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
            echo "<tr>";
            echo "<td colspan='2' bgcolor='gray'>
            
            <table width='100%' cellpadding='0' cellspacing='0' border='0'>
            <tr><td width='25%'>";
            
            $tab_id = 3;
            
            /*
            if( $_SESSION["tcc_user"]->group_id == 1 )
            {
                $tab_id = 4;    
            }
            */
            
            echo "<a href='interne_opdrachten.php?tab_id=".$tab_id."&opdracht_id=".$opdracht->id."'>";
            echo "<img alt='Open assignment' title='Open assignment' src='images/info.png' width='20' height='20' /></a>";
            
            if( $_SESSION["tcc_user"]->user_id == $opdracht->van_user_id )
            {
                echo "<form method='post' name='frm_delete' id='frm_delete' style='display:inline;' >";
                echo "<input onclick='return confirm(\"Delete assignment?\")' type='image' src='images/delete.png' name='delete_opdracht' id='delete_opdracht' width='20' height='20' value='". $opdracht->id ."' />";
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
            echo "<td width='25%' style='color:white;'>ID : ". $opdracht->unique_id ."</td>";
            
            echo "<td align='right' style='color:white;'>";
            
            $now = time(); // or your date as well
            $your_date = strtotime( substr( $opdracht->datetime,0,10 ) );
            
            if( $_SESSION["tcc_user"]->group_id == 1 && isset( $_POST["sel1"] ) )
            {
                $user_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = " . $_POST["sel1"]));
            }else
            {
                $user_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id . " AND user_id = " . $_SESSION["tcc_user"]->user_id));
            }
            
            if( $user_opdracht->done_time != '0000-00-00 00:00:00' )
            {
                $datediff = strtotime($user_opdracht->done_time) - $your_date;
                $aantal_dagen = floor($datediff/(60*60*24));
                echo "#";
                echo $aantal_dagen;
            }
            
            echo "</span>";
            echo "</td>";
            
            echo "</tr></table></td>";
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
                echo " - on : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
            }
            
            $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
            echo "<br /><strong>Created by : </strong>" . $van->voornaam . " " . $van->naam;
            echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td colspan='2'>".html_entity_decode($opdracht->omschrijving)."</td>";
            echo "</tr>";
            
            // zoeken naar de eerste id van deze opdracht
            
            $eerst_opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE unique_id = '" . $opdracht->unique_id . "' ORDER BY 1 ASC LIMIT 1"));
            $eerst_opdracht_id = $eerst_opdracht->id;
            
            $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $eerst_opdracht_id . " AND cf_soort = 'int_opdrachten' ");
            
            if( mysqli_num_rows($q_zoek_bijlage) > 0 )
            {
                echo "<tr>";
                echo "<td colspan='2'><strong>Attachements :</strong>";
                
                while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                {
                    echo "<br /><a href='opdrachten/".$eerst_opdracht_id."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                }
                
                echo "</td>";
                echo "</tr>";
            }
            
            // nakijken of er nog andere gebruikers aan deze opdracht gekoppeld zijn.
            $q1 = "SELECT * 
                              FROM kal_opdrachten_users 
                             WHERE opdracht_id = " . $opdracht->id . " 
                               AND ( ready != ''
                               OR notfinished != '' )";
                               
            $q_opdracht_info = mysqli_query($conn, $q1) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q1 );
            
            if( mysqli_num_rows($q_opdracht_info) > 0 )
            {
                echo "<tr><td colspan='3'>";
                
                $k=0;
                while( $opus = mysqli_fetch_object($q_opdracht_info) )
                {
                    if( $k == 0 )
                    {
                        echo "Below you can find the info of other persons with the same assignment :<br />";
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
        }
        
        /*
        if( $aantal_open > 0 )
        {
            echo "<table cellpadding='2' cellspacing='0' width='100%' style='border:2px solid gray;' border='1' >";
            echo "<tr>";
            echo "<td colspan='2' bgcolor='gray'>
            
            <table width='100%' cellpadding='0' cellspacing='0' border='0'>
            <tr><td width='25%'>";
            
            $tab_id = 3;
            
            
            
            echo "<a href='interne_opdrachten.php?tab_id=".$tab_id."&opdracht_id=".$opdracht->id."'>";
            echo "<img alt='Klik hier om de opdracht te openen' title='Klik hier om de opdracht te openen' src='images/info.png' width='20' height='20' /></a>";
            
            if( $_SESSION["tcc_user"]->user_id == $opdracht->van_user_id )
            {
                echo "<form method='post' name='frm_delete' id='frm_delete' style='display:inline;' >";
                echo "<input onclick='return confirm(\"Deze opdracht werkelijk verwijderen?\")' type='image' src='images/delete.png' name='delete_opdracht' id='delete_opdracht' width='20' height='20' value='". $opdracht->id ."' />";
                echo "</form>";
            }
            
            
            echo "</td>
            <td align='center'><b style='color:white;' >".html_entity_decode($opdracht->titel)."</b></td>
            <td width='25%' style='color:white;'>ID : ". $opdracht->unique_id ."</td></tr></table></td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td width='50%'>";
            echo "<strong>Start : </strong>" . changeDate2EU( $opdracht->start );
            echo "</td>";
            
            echo "<td width='50%'>";
            
            if( $opdracht->stop != $opdracht->start && $opdracht->stop != '0000-00-00' )
            {
                echo "<strong>Einde : </strong>" . changeDate2EU( $opdracht->stop );
            }
            
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td colspan='2'><strong>Voor : </strong>";
            
            // ophalen van al de gebruikers die aan deze opdracht gekoppeld zijn.
            
            $q_opdracht_users = mysqli_query($conn, "SELECT * FROM kal_opdrachten_users WHERE opdracht_id = " . $opdracht->id);
            
            $u = "";
            while( $opus = mysqli_fetch_object($q_opdracht_users)  )
            {
                $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opus->user_id));
                
                $chk = "";
                
                if( $opus->status == 'done' )
                {
                    $chk = " <strong>(afgewerkt)</strong>";
                }
                
                $u .= $user->voornaam . " " . $user->naam . $chk . ", ";
            }
            
            echo substr( $u, 0, -2 );
            
            if( $opdracht->type == "wekelijks" || $opdracht->type == "maandelijks" || $opdracht->type == "jaarlijks" || substr($opdracht->type,0,9) == "dagelijks" )
            {
                echo " - op : " . changeDate2EU( substr( $opdracht->datetime,0,10 ) );
            }
            
            $van = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $opdracht->van_user_id));
            echo "<br /><strong>Aangemaakt door : </strong>" . $van->voornaam . " " . $van->naam;
            echo "<br /><strong>Type : </strong>" . $types_arr[ $opdracht->type ];
            
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td colspan='2'>".html_entity_decode($opdracht->omschrijving)."</td>";
            echo "</tr>";
            
            $q_zoek_bijlage = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_cus_id = " . $opdracht->id . " AND cf_soort = 'int_opdrachten' ");
            
            if( mysqli_num_rows($q_zoek_bijlage) > 0 )
            {
                echo "<tr>";
                echo "<td colspan='2'><strong>Bijlage(s) :</strong>";
                
                while( $attach = mysqli_fetch_object($q_zoek_bijlage) )
                {
                    echo "<br /><a href='opdrachten/".$opdracht->id  ."/".$attach->cf_file."' target='_blank'>" . $attach->cf_file . "</a>";
                }
                
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table><br />";
        }
        */
    }
}

?>