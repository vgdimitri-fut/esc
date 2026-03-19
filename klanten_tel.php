<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

function getEUdatetime($timestamp)
{
	$tmp = explode(" ", $timestamp);
	$tmp1 = explode("-", $tmp[0]);
	return $tmp1[2] . "-" . $tmp1[1] . "-" . $tmp1[0] . " " . $tmp[1];
}

if( isset( $_POST["opslaan"] ) && $_POST["opslaan"] == "Save" )
{
	if(!empty( $_POST["opm"] ) )
	{
		
		$q_zoek = mysqli_query($conn, "SELECT * 
		                         FROM kal_cus_tel 
		                        WHERE ct_user_id = " . $_SESSION[ $session_var ]->user_id . "
		                          AND ct_cus_id = " . $_POST["cus_id"] . "
		                          AND ct_message = '" . htmlentities( $_POST["opm"], ENT_QUOTES) . "'");
		
		if( mysqli_num_rows($q_zoek) == 0 )
		{
			$q_ins = "INSERT INTO kal_cus_tel(ct_user_id, 
			                                  ct_cus_id, 
			                                  ct_message) 
			                          VALUES(".$_SESSION[ $session_var ]->user_id.",
			                                 ".$_POST["cus_id"].",
			                                 '". htmlentities( $_POST["opm"], ENT_QUOTES) ."')";

			mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );
		}
	}
}

// bewerken
if( isset($_POST["bewerk"]) && $_POST["bewerk"] == "Save edit" )
{
	$q_upd = mysqli_query($conn, "UPDATE kal_cus_tel 
	                         SET ct_message = '".htmlentities( $_POST["opm_edit"], ENT_QUOTES)."', 
	                             ct_datetime = '".date('Y-m-d H:i:s')."' 
	                       WHERE ct_id = " . $_POST["ct_id"]) or die( mysqli_error($conn) );
}




// verwijderen
if( isset( $_GET["del"] ) )
{
	$q_del = mysqli_query($conn, "DELETE FROM kal_cus_tel WHERE ct_id = ". $_GET["del"] ." LIMIT 1");
}

?>
<html>
<head>
<title>

</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
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
<body style='text-align:left;'>

<?php 
if( !isset( $_POST["wijzig"] ) )
{
    $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_GET["klantid"]));
    echo "Telephone remarks with customer " . $klant->cus_naam;
    echo "<br/>";
    echo "<table width='100%'>";
    echo "<tr>";
    echo "<td width='50%' valign='top'>";
    echo "<br/>";
    // tonen van de laatste telefonische opmerkingen
    $q_opm = mysqli_query($conn, "SELECT * FROM kal_cus_tel WHERE ct_cus_id = " . $_REQUEST["klantid"] . " ORDER BY 1 DESC");

    while( $rij = mysqli_fetch_object($q_opm) )
    {		
                    // user
                    $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $rij->ct_user_id));

                    echo "<form method='post' name='frm_int". $rij->ct_id ."' id='frm_int". $rij->ct_id ."' >";
                    echo "<table style='border:1px solid black;' width='100%'>";
                    echo "<tr>";
                    echo "<td align='left'>";
                    echo $user->voornaam . " " . $user->naam;
                    echo "</td><td align='right'>";
                    echo getEUdatetime($rij->ct_datetime)  . "<br/>"; 
                    echo "</td></tr>";

                    echo "<tr><td colspan='2'><hr/></td></tr>";

                    echo "<tr><td colspan='2'>";
                    echo "Opm :";
                    echo str_replace("\n", "<br/>", $rij->ct_message );
                    echo "</td>";
                    echo "</tr>";
                    echo "<tr><td colspan='2'><hr/></td></tr>";

                    if( 1 )
                    {
                            echo "<tr>";
                            echo "<td>";
                            echo "<a style='cursor:pointer;' onclick='document.forms[\"frm_int". $rij->ct_id ."\"].submit();' > <img src='images/edit.png' border='0' width='16' height='16' />Wijzigen </a>";
                            echo "&nbsp;&nbsp;";
                            echo "<a href='klanten_tel.php?del=". $rij->ct_id ."&klantid=". $_REQUEST["klantid"] ."' onclick=\"if(confirm('opmerking verwijderen?')){return true;}return false;\"><img src='images/delete.png' border='0'/>Verwijderen</a>";
                            echo "<input type='hidden' name='opmerking_id' id='opmerking_id' value='". $rij->ct_id ."' />";
                            echo "<input type='hidden' name='wijzig' id='wijzig' value='wijzig' />";
                            echo "</td>";
                            echo "</tr>";
                    }

                    echo "</table><br/>";
                    echo "</form>";

            echo "</a><br/>";
    }

    echo "</td><td width='50%' valign='top'>";
    echo "Add new remark :";
    echo "<form method='post'>";
    echo "<table cellpadding='0' cellspacing='0'>";
    echo "<tr>";
    echo "<td>";
    echo "<textarea name='opm' id='opm' rows='10' cols='50' ></textarea>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td align='center'>";
    echo "<input type='hidden' name='cus_id' id='cus_id' value='".$_GET["klantid"]."' />";
    echo "<input type='submit' name='opslaan' id='opslaan' value='Save' />";
    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "</form>";


    echo "</td></tr></table>";
}
if( isset( $_POST["wijzig"] ) )
{
	// ophalen van de interventie gegevens
	$opmerking = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_cus_tel WHERE ct_id = " . $_POST["opmerking_id"]));
	
	echo "<form method='post' name='frm_edit' id='frm_edit' >";
	echo "<b>Edit remark</b><br/><br/>";
	echo "Opmerking :";
	echo "<table cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td>";
	echo "<textarea name='opm_edit' id='opm_edit' rows='10' cols='50' >". $opmerking->ct_message ."</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<input type='hidden' name='ct_id' id='ct_id' value='".$_POST["opmerking_id"]."' />";
	echo "<input type='submit' name='bewerk' id='bewerk' value='Save edit' />";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
}
?>
</body>
</html>