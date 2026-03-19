<?php 

include "../inc/db.php";
include "../inc/functions.php";

$rechten = array();
$q_group = mysqli_query($conn, "SELECT * FROM kal_user_groups ORDER BY ug_id") or die( mysqli_error($conn) );
while( $recht = mysqli_fetch_object($q_group) )
{
	$rechten[ $recht->ug_id ] = $recht->ug_group ; 
}
?>

<div id="tabs-3">
		
			<form name='frm_overzicht' id='frm_overzicht' method='post'>
				<input type='hidden' name='tab_id' id='tab_id' value='1' />
				<input type='hidden' name='user_id1' id='user_id1' />
			</form> 
			<?php 
			
			$q_all = mysqli_query($conn, "SELECT * FROM kal_users ORDER BY group_id, naam, voornaam");

			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
			
			echo "<tr>";
			echo "<td valign='bottom'><b>Name</b></td>";
			echo "<td valign='bottom'><b>Firstname </b></td>";
			echo "<td valign='bottom'><b>E-mail</b></td>";
			echo "<td valign='bottom'><b>Username</b></td>";
			echo "<td valign='bottom'><b>Rights</b></td>";
                        echo "<td valign='bottom'><b>Telephone</b></td>";
			echo "</tr>";
			
                        $i=0;
			while( $user = mysqli_fetch_object($q_all) )
			{
				if( $user->sip_user2 == 0 )
                                {
                                    $user->sip_user2 = "";
                                }
                                $kleur = $kleur_grijs;
                                if( $i%2 )
                                {
                                        $kleur = "white";
                                }

                                //echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"user_id1\").value=". $user->user_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";

                                //echo "<tr style='cursor:pointer;background-color:". $kleur .";' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"user_id1\").value=". $user->user_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");'>";

                                echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $user->naam . "</td>";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $user->voornaam . "</td>";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $user->email . "</td>";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $user->username . "</td>";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $rechten[ $user->group_id ] . "</td>";
                                echo "<td onclick='gotoKlant(".$user->user_id.")'>" . $user->tel . "</td>";
                                echo "</tr>";
                                $i++;
			}
			echo "</table>";
						
			?>
			
		</div>