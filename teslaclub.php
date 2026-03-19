<?php
include "inc/db.php";



// initialisatie
$yesno_arr = array();
$yesno_arr[0] = 'No';
$yesno_arr[1] = 'Yes';
$kleur_grijs = "#ECFFF0";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
Tesla club<?php include "inc/erp_titel.php" ?>
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />

</style>
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
	
	<h1>Tesla club</h1>
	
	<div id="tabs">
		<ul>
			<li>
                            <a href="#tabs-1">Applications</a>
                            </a>
                        </li>
		</ul>
		<div id="tabs-1">
                    <?php
                        $q_tesla = mysqli_query($conn, "SELECT * FROM kal_tesla");
                        if( mysqli_num_rows($q_tesla) > 0 )
                        {
                            echo "<table width='100%' cellpadding='0' cellspacing='0'>";
                            echo "<tr>";
                            echo "<th><b>Name</b></th>";
                            echo "<th><b>Address</b></th>";
                            echo "<th><b>City</b></th>";
                            echo "<th><b>Tel/Mobile</b></th>";
                            echo "<th><b>Type Tesla</b></th>";
                            echo "<th><b>VIN nr</b></th>";
                            echo "<th><b>License plate</b></th>";
                            echo "<th><b>11/10</b></th>";
                            echo "<th><b>12/10</b></th>";
                            echo "</tr>";
                            $i = 0;
                            while( $tesla = mysqli_fetch_object($q_tesla))
                            {
                                $i++;
                                $kleur = $kleur_grijs;
                                if ($i % 2) {
                                    $kleur = "white";
                                }
                                echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                echo "<td>".$tesla->naam."</td>";
                                echo "<td>".$tesla->adres."</td>";
                                echo "<td>".$tesla->woonplaats."</td>";
                                echo "<td>".$tesla->tel_gsm."</td>";
                                echo "<td>".$tesla->type_tesla."</td>";
                                echo "<td>".$tesla->vin_nr."</td>";
                                echo "<td>".$tesla->nr_plaat."</td>";
                                echo "<td>".$yesno_arr[$tesla->dag_1]."</td>";
                                echo "<td>".$yesno_arr[$tesla->dag_2]."</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        }else{
                            echo "No application received.";
                        }
                    ?>			
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