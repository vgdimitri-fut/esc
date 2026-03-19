<?php 

session_start();
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

$rechten = array();
$q_group = mysqli_query($conn, "SELECT * FROM kal_user_groups ORDER BY ug_id") or die( mysqli_error($conn) );
while( $recht = mysqli_fetch_object($q_group) )
{
	$rechten[ $recht->ug_id ] = $recht->ug_group ; 
}

$zoek = 0;
$added = 0; 
if( isset( $_POST["toevoegen"] ) && $_POST["toevoegen"] == "Add" )
{
	// eerst kijken of deze gebruiker nog niet bestaat
	
	$q_zoek = mysqli_query($conn, "SELECT * FROM kal_users WHERE email = '" . $_POST["email"] . "'") or die( mysqli_error($conn) );
	
	$zoek = mysqli_num_rows($q_zoek);
	
	if( $zoek == 0 )
	{
		$q_ins = mysqli_query($conn, "INSERT INTO kal_users(group_id,
		                                            naam,
		                                            voornaam,
		                                            email,
		                                            username,
		                                            pwd,
                                                    tel) 
		                                 VALUES(". $_POST['groep'] .",
		                                        '". htmlentities($_POST['naam'], ENT_QUOTES) ."',
		                                        '". htmlentities($_POST['voornaam'], ENT_QUOTES) ."',
		                                        '". $_POST['email'] ."',
		                                        '". $_POST['username'] ."',
		                                        '". md5( $_POST['password'] ) ."',
                                                '". $_POST["add_tel"] ."')") or die( mysqli_error($conn) );
		
		// als goed is toegevoegd, dan versturen van een email met daar de nodige gegevens.
		// mailen
		
		$body = "Dear " . $_POST["naam"] . " " . $_POST["voornaam"] . "\n\n";
		$body .= "Below you can find your credential for logging into : http://www.solarlogs.be/esc\n";
		$body .= "Login : " . $_POST['username'] . "\n";
		$body .= "Password : " . $_POST['password'] . "\n";
		$body .= "User group " . $rechten[ $_POST['groep'] ] . "\n\n";
		
		$headers = "From: info@solarlogs.be\r\n";
		mail( $_POST['email'] , "Your credentials", $body, $headers );

		$added = 1;
	}
}

/*
echo "<pre>";
var_dump( $_POST );
echo "</pre>";
*/

$verwijderen = 0;
if( isset( $_POST["verwijderen"] ) && $_POST["verwijderen"] == "Delete" )
{
	$q_del = "DELETE FROM kal_users WHERE user_id = " . $_POST["user_id"];
	
	if( mysqli_query($conn, $q_del) )
	{
		$verwijderen = 1;
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
User management<?php include "inc/erp_titel.php" ?>
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="js/jquery.validate.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>

<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript">

function gotoKlant(cus_id1)
{
	document.getElementById("user_id1").value = cus_id1;
	document.getElementById("frm_overzicht").submit();	
}

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});

$(document).ready(function(){
	$("#frm_mod_user").validate();
        $("#frm_new_groep").validate();
        // Verwijderen van een groep
        $('.groep_delete').click(function()
        { 
            if(confirm("Verwijderen?"))
            {
                var groepdata = $(this).attr('class').substr(13);
                var group = groepdata.split(',');
                var groepid = group[0];
                var groepnaam = group[1];
                var groeprechten = group[2];

                // post data naar php
                var postedit = $.post("ajax/users_groep.php",{id: groepid, naam: groepnaam,rechten: groeprechten, action: 'delete'});
                // toon resultaat
                postedit.done(function( data ) {
                    // verwijder rij
                    $("#" + data).remove();
                });
            }
            return false;
        });
        // Wijzigen van een groep
        $('.groep_edit').click(function()
        { 
            // ug_id van de groep
            var groepdata = $(this).attr('class').substr(11);
            var group = groepdata.split(',');
            var groepid = group[0];
            var groepnaam = group[1];
            var groeprechten = group[2];

            var postdelete = $.post("ajax/users_groep.php",{id: groepid, naam: groepnaam,rechten: groeprechten, action: "wijzig"},function(data){
              $('#tabs-4').html(data);
            });
            postdelete.fail(function( data ){
                $(".overzicht_groepen_ajax").html("<img src='images/indicator.gif' /> Loading...");
            });
            return false;
        });
});

$(document).ready(function(){
	$("#frm_nieuwegebruiker").validate();
});

$(document).ready(function(){
	$("#frm_nieuwegebruiker2").validate();
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
	<?php include('inc/header.php'); ?>
	
	<h1>User management</h1>
	
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">New user</a></li>
			<li><a href="#tabs-2">Edit</a></li>
			<li><a href="ajax/users_overzicht.php">Overview</a></li>
                        <li><a href="#tabs-4">Permissions</a></li>
		</ul>
		<div id="tabs-1">
			
			<?php 
			
			if( isset( $_POST["toevoegen"] ) && $_POST["toevoegen"] == "Toevoegen" && $added == 1 )
			{
				?>
				<script type='text/javascript'>
				$(function() {
					$("#id_added").fadeOut(5000);
				});
				</script>
				<?php
				
				echo "<span id='id_added' class='correct'>User added and login details are emailed.</span>";
			}
			
			if( $zoek > 0 )
			{
				?>
				<script type='text/javascript'>
				$(function() {
					$("#id_user_exists").fadeOut(5000);
				});
				</script>
				<?php
					
				echo "<span id='id_user_exists' class='error'>There is already an user with this e-mail address.</span>";
			}
			
			
			?>
			
			
			<form id='frm_nieuwegebruiker' name='frm_nieuwegebruiker' method='post' >
			<table>
				<tr>
					<td>Name :</td>
					<td>
						<input type='text' class='lengte required' name='naam' id='naam' />
					</td>
				</tr>
				
				<tr>
					<td>First name :</td>
					<td>
						<input type='text' class='lengte required' name='voornaam' id='voornaam' />
					</td>
				</tr>
				
				<tr>
					<td>E-mail :</td>
					<td>
						<input type='text' class='lengte required email' name='email' id='email' />
					</td>
				</tr>
				
                <tr>
					<td>Telephone :</td>
					<td>
						<input type='text' class='lengte required' name='add_tel' id='add_tel' />
					</td>
				</tr>
                
				<tr>
					<td>Username :</td>
					<td>
						<input type='text' class='lengte required' name='username' id='username' />
					</td>
				</tr>
				
				<tr>
					<td>Password :</td>
					<td>
						<input type='password' class='lengte required' name='password' id='password' />
					</td>
				</tr>
				
				<tr>
					<td>Group :</td>
					<td>
						<select name='groep' id='groep' class='lengte required'>
							<?php 
							
							$q_group = mysqli_query($conn, "SELECT * FROM kal_user_groups ORDER BY ug_group");
							
							echo "<option value='' >== Choice ==</option>";
							
							while( $group = mysqli_fetch_object($q_group) )
							{
								echo "<option value='". $group->ug_id ."' >" . $group->ug_group . "</option>";
							}
							
							?>
						
						</select>
					</td>
				</tr>
			</table>
			
			<br/>
			
			<table width='320'>
				<tr>
					<td align='center'> <input type='submit' name='toevoegen' id='toevoegen' value='Add' /> </td>
				</tr>
			</table>
			</form>
			
		</div>
		
		<div id="tabs-2">
			<?php 
			$aanpassen = 0;
			$mail = 0;
			if( isset( $_POST["aanpassen"] ) && $_POST["aanpassen"] == "Save" )
			{
			     $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST["user_id"]));
             
				$q_upd = "UPDATE kal_users SET naam = '" . htmlentities($_POST['naam'], ENT_QUOTES) . "',
				                               voornaam = '" . htmlentities($_POST['voornaam'], ENT_QUOTES) . "',
				                               email = '" . $_POST["email"] . "',
				                               username = '" . $_POST["username"] . "',
				                               group_id = '" . $_POST["groep"] . "',
                                               tel = '" . $_POST["tel"] . "'
				                       WHERE user_id = " . $_POST["user_id"];
				
				mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
				$aanpassen = 1;
				
                
                
				if( !empty( $_POST["password"] ) && md5( $_POST["password"] ) != $user->pwd )
				{
				    
                    
					//echo "Het wachtwoord is niet leeg";
					$q_ww = mysqli_query($conn, "UPDATE kal_users SET pwd = '" . md5( $_POST["password"] )  . "' WHERE user_id = " . $_POST['user_id']);
					
					if( $q_ww )
					{
					    echo "OK";
                       
						$body = "Dear " . $_POST["naam"] . " " . $_POST["voornaam"] . "\n\n";
						$body .= "Below you can find your new credentials for logging into http://www.solarlogs.be/esc\n";
						$body .= "Login : " . $_POST['username'] . "\n";
						$body .= "Password : " . $_POST['password'] . "\n\n";
						
						$headers = "From: info@solarlogs.be\r\n";
						mail( $_POST['email'] , "Your new credentials", $body, $headers );
						
						$mail = 1;
					}
				}
			}
			
			//lijstje van al de users

			$q_users = mysqli_query($conn, "SELECT * FROM kal_users ORDER BY naam, voornaam");
			
			echo "<table width='100%'>";
			echo "<tr><td>";
			
			echo "<form method='post' name='frm_mod_user' id='frm_mod_user' >";
			
			echo "<table width='500'>";
			echo "<tr>";
			
			echo "<td width='150' valign='top'>User : </td>";
			
			echo "<td>";
			echo "<select name='mod_user' id='mod_user' class='lengte required'>";
			echo "<option value=''>== Choice ==</option>";
			
			while( $user = mysqli_fetch_object($q_users) )
			{
				if( isset( $_POST['mod_user'] ) && $_POST['mod_user'] == $user->user_id )
				{
					echo "<option selected='yes' value='". $user->user_id ."'>". $user->naam . " " . $user->voornaam ."</option>";
				}else
				{
					echo "<option value='". $user->user_id ."'>". $user->naam . " " . $user->voornaam ."</option>";	
				}
			}
			
			echo "</select>";
			echo "</td>";
			
			echo "<td valign='top'>";
			echo "<input type='submit' name='zoek_user' id='zoek_user' value='Search' />";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			
			echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
			echo "</form>";
			
			echo "</td>";
			echo "<td align='right'>";

			if( $verwijderen == 1 )
			{
				?>
				<script type='text/javascript'>
				$(function() {
					$("#id_user_del").fadeOut(5000);
				});
				</script>
				<?php
				
				echo "<span id='id_user_del' class='correct'>User is deleted.</span>";
			}
			
			if( $aanpassen == 1 )
			{
				?>
				<script type='text/javascript'>
				$(function() {
					$("#id_user_mod").fadeOut(7000);
				});
				</script>
				<?php
				
				echo "<span id='id_user_mod' class='correct'>User is edited";
				
				if( $mail == 1 )
				{
					echo " and an email is sent with new password.";
				}else
				{
					echo ".";
				}
				
				echo "</span>";
			}
			
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			
			
			if( (isset( $_POST["zoek_user"] ) && $_POST["zoek_user"] == "Search" && $_POST["mod_user"] > 0) || isset( $_POST["user_id1"] ) )
			{
				if( isset( $_POST["user_id1"] ) && $_POST["user_id1"] >0 )
				{
					$_POST["mod_user"] = $_POST["user_id1"];
				}
				
				$q_mod_user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST["mod_user"]));
			}
			
			if( (isset( $_POST["zoek_user"] ) && $_POST["mod_user"] > 0) || isset( $_POST["user_id1"] ) )
			{
			
			?>
			
			<br/><br/><br/>
			
			<form id='frm_nieuwegebruiker2' name='frm_nieuwegebruiker2' method='post' >
			<table width='500'>
				<tr>
					<td width='150' >Name :</td>
					<td>
						<input type='text' class='lengte required' name='naam' id='naam' value='<?php echo $q_mod_user->naam; ?>' />
					</td>
				</tr>
				
				<tr>
					<td>First name :</td>
					<td>
						<input type='text' class='lengte required' name='voornaam' id='voornaam' value='<?php echo $q_mod_user->voornaam; ?>' />
					</td>
				</tr>
				
				<tr>
					<td>E-mail :</td>
					<td>
						<input type='text' class='lengte required email' name='email' id='email' value='<?php echo $q_mod_user->email; ?>' />
					</td>
				</tr>
                
                <tr>
					<td>Tel. :</td>
					<td>
						<input type='text' class='lengte required' name='tel' id='tel' value='<?php echo $q_mod_user->tel; ?>' />
					</td>
				</tr>
				
				<tr>
					<td>Username :</td>
					<td>
						<input type='text' class='lengte required' name='username' id='username' value='<?php echo $q_mod_user->username; ?>' />
					</td>
				</tr>
				
				<tr>
					<td>Password :</td>
					<td>
						<input type='password' class='lengte' name='password' id='password' />
						&nbsp;<img src='images/q_mark.gif' title='Als er een wachtwoord wordt ingevuld, dan krijgt deze persoon een email met daarin het nieuwe wachtwoord.' />
					</td>
				</tr>
				
				<tr>
					<td>Group :</td>
					<td>
						<select name='groep' id='groep'>
							<?php 
							
							$q_group = mysqli_query($conn, "SELECT * FROM kal_user_groups ORDER BY ug_group");
							
							while( $group = mysqli_fetch_object($q_group) )
							{
								if( $q_mod_user->group_id == $group->ug_id )
								{
									echo "<option selected='yes' value='". $group->ug_id ."' >" . $group->ug_group . "</option>";
								}else
								{
									echo "<option value='". $group->ug_id ."' >" . $group->ug_group . "</option>";	
								}
							}
							
							?>
						
						</select>
					</td>
				</tr>
			</table>
			
			<br/>
			
			<table width='320'>
				<tr>
					<td align='center' colspan='3'> 
						<input type='submit' name='aanpassen' id='aanpassen' value='Save' />
						<input type='submit' name='verwijderen' id='verwijderen' value='Delete' onclick="javascript:return confirm('Deze gebruiker verwijderen?');" />
						<input type='hidden' name='user_id' id='user_id' value='<?php echo $q_mod_user->user_id; ?>' />
						<input type='hidden' name='tab_id' id='tab_id' value='2' /> 
					</td>
				</tr>
			</table>
			</form>
			
			<?php 
			
			}
			
			?>
		</div>
		
		
                <div id="tabs-4">
                    <?php 
                    // Wijzigen van groep
                    if(isset($_POST["wijzigen_groep"]) && $_POST["wijzigen_groep"] == "Save")
                    {
                        mysqli_query($conn, "UPDATE kal_user_groups SET ug_group='".$_POST["groepnaam"]."', ug_rights='".$_POST["soortrechten"]."' WHERE ug_id='".$_POST["id"]."'");
                    }
                    // Toevoegen van groep
                    if(isset($_POST["toevoegen_groep"]) && $_POST["toevoegen_groep"] == "Add")
                    {
                        mysqli_query($conn, "INSERT INTO kal_user_groups (ug_group,ug_rights) VALUES ('".$_POST['groepnaam']."','".$_POST['soortrechten']."')");
                    }
                    ?>
                    <table width='100%'>
                        <tr>
                            <td>
                                <h4 style='margin:0;padding:0;'>New group</h4>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <form method='post' name='frm_new_groep' id='frm_new_groep'>
                                    <table width='500'>
                                        <tr>
                                            <td width='150' valign='top'>Name group:</td>
                                            <td>
                                                <input type='text' class='lengte required' name='groepnaam' id='groepnaam'/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width='150' valign="top">Type of rights:</td>
                                            <td>
                                                <input type='text' class='lengte required' name='soortrechten' id='soortrechten'/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input type='submit' name='toevoegen_groep' id='toevoegen_groep' value='Add' />
                                            </td>
                                        </tr>
                                    </table>
                                    <input type='hidden' name='tab_id' id='tab_id' value='3' />
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h4 style='margin:0;padding:0;display:inline;'>Overzicht groepen</h4><span style='margin-left:10px' class='overzicht_groepen_ajax'></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <form method='post' name='frm_overzicht_groep' id='frm_overzicht_groep'>
                                    <table width='500' cellpadding="0" cellspacing="0"> 
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><b>Name</b></td>
                                            <td><b>Rights</b></td>
                                        </tr>
                                        <?php 
                                            // Haal groep data van database
                                            $i=0;
                                            $q_groep = mysqli_query($conn, "SELECT * FROM kal_user_groups ORDER BY ug_id");
                                            while($q_groep_overzicht = mysqli_fetch_object($q_groep))
                                            {
                                                $i++;
                                                $kleur = $kleur_grijs;
                                                if( $i%2 )
                                                {
                                                        $kleur = "white";
                                                }

                                                echo "<tr id=".$q_groep_overzicht->ug_id." style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";

                                                ?>
                                        <td>
                                            <a class='groep_edit <?php echo $q_groep_overzicht->ug_id.','.$q_groep_overzicht->ug_group.','.$q_groep_overzicht->ug_rights ?>' href='' ><img src='images/edit.png' width="16" height="16" title='wijzig <?php echo $q_groep_overzicht->ug_group ?>'/></a>
                                                
                                                
                                                <?php
                                                
                                                $gev = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_users WHERE group_id = " . $q_groep_overzicht->ug_id));
                                                
                                                if( $gev == 0 )
                                                {
                                                
                                                ?>
                                                <a class='groep_delete <?php echo $q_groep_overzicht->ug_id.','.$q_groep_overzicht->ug_group.','.$q_groep_overzicht->ug_rights ?>' href='' ><img src='images/delete.png' title='verwijder <?php echo $q_groep_overzicht->ug_group ?>'/></a>
                                                <?php
                                                
                                                }
                                                
                                                ?>
                                            </td>
                                        
                                            <td><?php echo $q_groep_overzicht->ug_group ?></td>
                                            <td><?php echo $q_groep_overzicht->ug_rights ?></td>
                                            
                                        </tr>
                                                <?php
                                            }
                                        ?>
                                    </table>
                                    <input type='hidden' name='tab_id' id='tab_id' value='3' />
                                </form>
                            </td>
                        </tr>
                    </table>			
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
