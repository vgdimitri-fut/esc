<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

function getDaysInWeek ($weekNumber, $year) {
  // Count from '0104' because January 4th is always in week 1
  // (according to ISO 8601).
  $time = strtotime($year . '0104 +' . ($weekNumber - 1)
                    . ' weeks');
  // Get the time of the first day of the week
  $mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days',
                          $time);
  // Get the times of days 0 -> 6
  $dayTimes = array ();
  for ($i = 0; $i < 7; ++$i) {
    $dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
  }
  // Return timestamps for mon-sun.
  return $dayTimes;
}

$acma_arr = array();

$q_acma = mysqli_query($conn, "SELECT * FROM kal_users");

while( $rij = mysqli_fetch_object($q_acma) )
{
	$acma_arr[ $rij->user_id ] = $rij->voornaam . " " . $rij->naam;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
<?php include "inc/erp_titel.php" ?> - Algemene kalender
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<style type='text/css'>
.maand_overzicht td{
	padding-left:10px;
	padding-right: 10px;
}

.maand_overzicht{
	color: #404040;
	background-color: #FFEEC9;
}

.maand_td{
	color: #404040;
	background-color: #FFF2E2;
}
</style>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24625187-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
  
    $(function() {
        $( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
    });
</script>
</head>
<body>

<div id='pagewrapper'>
	<?php
        $bestand = mysqli_fetch_object(mysqli_query($conn, "SELECT bedrijf_foto FROM kal_instellingen"));
        ?>
        <img src='images/<?php echo $bestand->bedrijf_foto; ?>'/><br/>
	<h1>Overzicht per adviseur</h1>
	
    <div id="tabs">
    	<ul>
    		<li><a href="#tabs-1">Verkoop + Verhuur + Huurkoop</a></li>
            <li><a href="#tabs-2">Verkoop</a></li>
            <li><a href="#tabs-3">Verhuur</a></li>
            <li><a href="#tabs-3a">Huurkoop</a></li>
            
            <?php
            if( $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->user_id == 19 || $_SESSION[ $session_var ]->user_id == 29 )
            {
            ?>
            <li><a href="#tabs-4">Leads</a></li>
            <?php
            }
            ?>
    	</ul>
    
        <div id="tabs-1">
        Hier wordt gekeken naar de verkoopsdatum.<br /><br />
    	<form method='post' name='frm_overzicht' id='frm_overzicht' >
    	Kies een jaar : 
    	<select name='jaar' id='jaar'>
    	<?php 
    	
    	$startjaar = 2011;
    	
    	for($i=$startjaar;$i<date('Y')+2;$i++ )
    	{
            if( isset( $_POST["jaar"] ) && $_POST["jaar"] == $i )
            {
                echo "<option selected='selected' value=".$i.">".$i."</option>";
            }else
            {
                if( $i == date('Y') && !isset( $_POST["jaar"] ) )
                {
                    echo "<option selected='selected' value=".$i.">".$i."</option>";
                }else{
                    echo "<option value=".$i.">".$i."</option>";    
                }
            }
    	}
    	
    	?>
    		
    	</select>
    	<input type='submit' name='go' value='Go' id='go' />
    	</form>
    	<br/>
    
    	<?php 
    
    	
    	if( !isset( $_POST["jaar"] ) )
    	{
    		$jaar = date('Y');	
    	}else
    	{
    		$jaar = $_POST["jaar"];
    	}
    	
    	$overzicht = array();
    	
        for($i=1;$i<53;$i++)
    	{
    		$dayTimes = getDaysInWeek($i, $jaar);
    		foreach ($dayTimes as $dayTime) {
    			$datum = date("d-m-Y", $dayTime);	
    		
    			if( !isset( $overzicht[$i]["startdatum"] ) )
    		  	{
    		  		$overzicht[$i]['startdatum'] = $datum; 	
    		  	}
    		  	
    		  	$overzicht[$i]['einddatum'] = $datum;
    		  	
    			if( $_SESSION[ $session_var ]->group_id == 3 )
    			{
    				if( $_SESSION[ $session_var ]->user_id == 29 )
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop != '0' AND cus_acma IN(". $klanten_onder_frans .") AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    					
    				}else
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop != '0' AND cus_acma = '".$_SESSION[ $session_var ]->user_id."' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    				}
    			}else
    			{
    				$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop != '0' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    			}
    			
    			// AND uit_cus_id = 0 
    			
    		  	while($rij = mysqli_fetch_object($q_datum))
    		  	{
    		  		if( $rij->uit_cus_id != 0 )
    		  		{
    		  			// dan is het een subklant
    		  			$hoofdklant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $rij->uit_cus_id));
    		  			
    		  			// tellen van het aantal dagen verschil
    		  			$hoofd_dat = explode("-", $hoofdklant->cus_verkoop_datum );
    		  			$sub_dat = explode("-", $rij->cus_verkoop_datum );
    		  			
    		  			$h_dat = mktime( 0, 0, 0, $hoofd_dat[1], $hoofd_dat[2], $hoofd_dat[0] );
    		  			$s_dat = mktime( 0, 0, 0, $sub_dat[1], $sub_dat[2], $sub_dat[0] );
    		  			
    		  			
    		  			$verschil = ($s_dat - $h_dat) / 86400;
    		  			
    		  			if( $verschil > 30 )
    		  			{
    		  				$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;
    		  			}
    		  		}else
    		  		{
    		  			$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;	
    		  		}
    		  	}
      		}	
    	}
    	
    	if( count( $overzicht ) > 0 )
    	{
            echo "<table width='100%'>";
            echo "<tr>";
            echo "<td valign='top'>";
           
    		echo "<table cellpadding='0' cellspacing='0' border='1' class='maand_overzicht' >";
    		echo "<tr>";
    		echo "<td colspan='3' align='center'>". $jaar ."</td>";
    		echo "</tr>";
    		
    		echo "<tr>";
    		echo "<td width='50' >&nbsp;Week&nbsp;</td>";
            echo "<td width='180'>&nbsp;</td>";
    		echo "<td width='180' align='center'>&nbsp;ACMA + #overeenkomsten&nbsp;</td>";
    		echo "</tr>";
    		
            
            $gen_tot = array();
    		foreach( $overzicht as $wn => $detail )
    		{
    			echo "<tr>";
    			echo "<td title='". $detail["startdatum"] . " - " . $detail["einddatum"] ."' align='center' valign='middle' >".$wn."</td>";
    			echo "<td>" . $detail["startdatum"] . " - " . $detail["einddatum"] . "</td>";
    			echo "<td class='maand_td' align='left' >";
    			
    			if( isset($detail["acmas"]) )
    			{
    				foreach( $detail["acmas"] as $acma => $klanten )
    				{
    					echo $acma_arr[ $acma ] . " : ";
    					echo count( $klanten );
    					
    					if( isset( $gen_tot[$acma] ) )
                        {
                            $gen_tot[$acma] += count($klanten);
                        }else
                        {
                            $gen_tot[$acma] = count($klanten);
                        }
    					
    					echo "<br>";
    				}
    			}
    			echo "</td>";
    			echo "</tr>";
    		}
    		
    		echo "</table>";
            
            echo "</td>";
            echo "<td valign='top'>";
            echo "<b>Totalen</b>";
            
            if( count( $gen_tot ) > 0 )
            {
                echo "<table>";
                
                foreach( $gen_tot as $acma => $aantal  )
                {
                    echo "<tr>";
                    echo "<td>";
                    echo $acma_arr[$acma];
                    echo " : </td>";
                    echo "<td>";
                    echo $aantal;
                    echo "</td>";
                    echo "</tr>";    
                }
                echo "</table>";
            }
            
            
            echo "</td>";
            echo "</tr>";
            echo "</table>";
    	}
    	
    	?>
        </div>
        
        <!-- verkoop -->
        <div id="tabs-2">
        Hier wordt gekeken naar de verkoopsdatum.<br /><br />
    	<form method='post' name='frm_overzicht_verkoop' id='frm_overzicht_verkoop' >
        <input type='hidden' name="tab_id" id="tab_id" value="1" />
    	Kies een jaar : 
    	<select name='jaar' id='jaar'>
    	<?php 
    	
    	$startjaar = 2011;
    	
    	for($i=$startjaar;$i<date('Y')+2;$i++ )
    	{
            if( isset( $_POST["jaar"] ) && $_POST["jaar"] == $i )
            {
                echo "<option selected='selected' value=".$i.">".$i."</option>";
            }else
            {
                if( $i == date('Y') && !isset( $_POST["jaar"] ) )
                {
                    echo "<option selected='selected' value=".$i.">".$i."</option>";
                }else{
                    echo "<option value=".$i.">".$i."</option>";    
                }
            }
    	}
    	
    	?>
    		
    	</select>
    	<input type='submit' name='go' value='Go' id='go' />
    	</form>
    	<br/>
    
    	<?php 
    
    	
    	if( !isset( $_POST["jaar"] ) )
    	{
    		$jaar = date('Y');	
    	}else
    	{
    		$jaar = $_POST["jaar"];
    	}
    	
    	$overzicht = array();
    	
        for($i=1;$i<53;$i++)
    	{
    		$dayTimes = getDaysInWeek($i, $jaar);
    		foreach ($dayTimes as $dayTime) {
    			$datum = date("d-m-Y", $dayTime);	
    		
    			if( !isset( $overzicht[$i]["startdatum"] ) )
    		  	{
    		  		$overzicht[$i]['startdatum'] = $datum; 	
    		  	}
    		  	
    		  	$overzicht[$i]['einddatum'] = $datum;
    		  	
    			if( $_SESSION[ $session_var ]->group_id == 3 )
    			{
    				if( $_SESSION[ $session_var ]->user_id == 29 )
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '1' AND cus_acma IN(". $klanten_onder_frans .") AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    					
    				}else
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '1' AND cus_acma = '".$_SESSION[ $session_var ]->user_id."' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    				}
    			}else
    			{
    				$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '1' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    			}
    			
    			// AND uit_cus_id = 0 
    			
    		  	while($rij = mysqli_fetch_object($q_datum))
    		  	{
    		  		if( $rij->uit_cus_id != 0 )
    		  		{
    		  			// dan is het een subklant
    		  			$hoofdklant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $rij->uit_cus_id));
    		  			
    		  			// tellen van het aantal dagen verschil
    		  			$hoofd_dat = explode("-", $hoofdklant->cus_verkoop_datum );
    		  			$sub_dat = explode("-", $rij->cus_verkoop_datum );
    		  			
    		  			$h_dat = mktime( 0, 0, 0, $hoofd_dat[1], $hoofd_dat[2], $hoofd_dat[0] );
    		  			$s_dat = mktime( 0, 0, 0, $sub_dat[1], $sub_dat[2], $sub_dat[0] );
    		  			
    		  			
    		  			$verschil = ($s_dat - $h_dat) / 86400;
    		  			
    		  			if( $verschil > 30 )
    		  			{
    		  				$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;
    		  			}
    		  		}else
    		  		{
    		  			$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;	
    		  		}
    		  	}
    		}	
    	}
    	
    	if( count( $overzicht ) > 0 )
    	{
            echo "<table width='100%'>";
            echo "<tr>";
            echo "<td valign='top'>";
           
    		echo "<table cellpadding='0' cellspacing='0' border='1' class='maand_overzicht' >";
    		echo "<tr>";
    		echo "<td colspan='3' align='center'>". $jaar ."</td>";
    		echo "</tr>";
    		
    		echo "<tr>";
    		echo "<td width='50' >&nbsp;Week&nbsp;</td>";
            echo "<td width='180'>&nbsp;</td>";
    		echo "<td width='180' align='center'>&nbsp;ACMA + #overeenkomsten&nbsp;</td>";
    		echo "</tr>";
    		
            
            $gen_tot = array();
    		foreach( $overzicht as $wn => $detail )
    		{
    			echo "<tr>";
    			echo "<td title='". $detail["startdatum"] . " - " . $detail["einddatum"] ."' align='center' valign='middle' >".$wn."</td>";
    			echo "<td>" . $detail["startdatum"] . " - " . $detail["einddatum"] . "</td>";
    			echo "<td class='maand_td' align='left' >";
    			
    			if( isset($detail["acmas"]) )
    			{
    				foreach( $detail["acmas"] as $acma => $klanten )
    				{
    					echo $acma_arr[ $acma ] . " : ";
    					echo count( $klanten );
    					
    					if( isset( $gen_tot[$acma] ) )
                        {
                            $gen_tot[$acma] += count($klanten);
                        }else
                        {
                            $gen_tot[$acma] = count($klanten);
                        }
    					
    					echo "<br>";
    				}
    			}
    			echo "</td>";
    			echo "</tr>";
    		}
    		
    		echo "</table>";
            
            echo "</td>";
            echo "<td valign='top'>";
            echo "<b>Totalen</b>";
            
            if( count( $gen_tot ) > 0 )
            {
                echo "<table>";
                
                foreach( $gen_tot as $acma => $aantal  )
                {
                    echo "<tr>";
                    echo "<td>";
                    echo $acma_arr[$acma];
                    echo " : </td>";
                    echo "<td>";
                    echo $aantal;
                    echo "</td>";
                    echo "</tr>";    
                }
                echo "</table>";
            }
            
            
            echo "</td>";
            echo "</tr>";
            echo "</table>";
    	}
    	
    	?>
        </div>
        
        <!-- verhuur -->
        <div id="tabs-3">
        Hier wordt gekeken naar de verkoopsdatum.<br /><br />
    	<form method='post' name='frm_overzicht_verhuur' id='frm_overzicht_verhuur' >
        <input type="hidden" name="tab_id" id="tab_id" value="2" />
    	Kies een jaar : 
    	<select name='jaar' id='jaar'>
    	<?php 
    	
    	$startjaar = 2011;
    	
    	for($i=$startjaar;$i<date('Y')+2;$i++ )
    	{
            if( isset( $_POST["jaar"] ) && $_POST["jaar"] == $i )
            {
                echo "<option selected='selected' value=".$i.">".$i."</option>";
            }else
            {
                if( $i == date('Y') && !isset( $_POST["jaar"] ) )
                {
                    echo "<option selected='selected' value=".$i.">".$i."</option>";
                }else{
                    echo "<option value=".$i.">".$i."</option>";    
                }
            }
    	}
    	
    	?>
    		
    	</select>
    	<input type='submit' name='go' value='Go' id='go' />
    	</form>
    	<br/>
    
    	<?php 
    
    	
    	if( !isset( $_POST["jaar"] ) )
    	{
    		$jaar = date('Y');	
    	}else
    	{
    		$jaar = $_POST["jaar"];
    	}
    	
    	$overzicht = array();
    	
        for($i=1;$i<53;$i++)
    	{
    		$dayTimes = getDaysInWeek($i, $jaar);
    		foreach ($dayTimes as $dayTime) {
    			$datum = date("d-m-Y", $dayTime);	
    		
    			if( !isset( $overzicht[$i]["startdatum"] ) )
    		  	{
    		  		$overzicht[$i]['startdatum'] = $datum; 	
    		  	}
    		  	
    		  	$overzicht[$i]['einddatum'] = $datum;
    		  	
    			if( $_SESSION[ $session_var ]->group_id == 3 )
    			{
    				if( $_SESSION[ $session_var ]->user_id == 29 )
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '2' AND cus_acma IN(". $klanten_onder_frans .") AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    					
    				}else
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '2' AND cus_acma = '".$_SESSION[ $session_var ]->user_id."' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    				}
    			}else
    			{
    				$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '2' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    			}
    			
    			// AND uit_cus_id = 0 
    			
    		  	while($rij = mysqli_fetch_object($q_datum))
    		  	{
    		  		if( $rij->uit_cus_id != 0 )
    		  		{
    		  			// dan is het een subklant
    		  			$hoofdklant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $rij->uit_cus_id));
    		  			
    		  			// tellen van het aantal dagen verschil
    		  			$hoofd_dat = explode("-", $hoofdklant->cus_verkoop_datum );
    		  			$sub_dat = explode("-", $rij->cus_verkoop_datum );
    		  			
    		  			$h_dat = mktime( 0, 0, 0, $hoofd_dat[1], $hoofd_dat[2], $hoofd_dat[0] );
    		  			$s_dat = mktime( 0, 0, 0, $sub_dat[1], $sub_dat[2], $sub_dat[0] );
    		  			
    		  			
    		  			$verschil = ($s_dat - $h_dat) / 86400;
    		  			
    		  			if( $verschil > 30 )
    		  			{
    		  				$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;
    		  			}
    		  		}else
    		  		{
    		  			$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;	
    		  		}
    		  	}
    		}	
    	}
        
    	if( count( $overzicht ) > 0 )
    	{
            echo "<table width='100%'>";
            echo "<tr>";
            echo "<td valign='top'>";
           
    		echo "<table cellpadding='0' cellspacing='0' border='1' class='maand_overzicht' >";
    		echo "<tr>";
    		echo "<td colspan='3' align='center'>". $jaar ."</td>";
    		echo "</tr>";
    		
    		echo "<tr>";
    		echo "<td width='50' >&nbsp;Week&nbsp;</td>";
            echo "<td width='180'>&nbsp;</td>";
    		echo "<td width='180' align='center'>&nbsp;ACMA + #overeenkomsten&nbsp;</td>";
    		echo "</tr>";
    		
            
            $gen_tot = array();
    		foreach( $overzicht as $wn => $detail )
    		{
    			echo "<tr>";
    			echo "<td title='". $detail["startdatum"] . " - " . $detail["einddatum"] ."' align='center' valign='middle' >".$wn."</td>";
    			echo "<td>" . $detail["startdatum"] . " - " . $detail["einddatum"] . "</td>";
    			echo "<td class='maand_td' align='left' >";
    			
    			if( isset($detail["acmas"]) )
    			{
    				foreach( $detail["acmas"] as $acma => $klanten )
    				{
    					echo $acma_arr[ $acma ] . " : ";
    					echo count( $klanten );
    					
    					if( isset( $gen_tot[$acma] ) )
                        {
                            $gen_tot[$acma] += count($klanten);
                        }else
                        {
                            $gen_tot[$acma] = count($klanten);
                        }
    					
    					echo "<br>";
    				}
    			}
    			echo "</td>";
    			echo "</tr>";
    		}
    		
    		echo "</table>";
            
            echo "</td>";
            echo "<td valign='top'>";
            echo "<b>Totalen</b>";
            
            if( count( $gen_tot ) > 0 )
            {
                echo "<table>";
                
                foreach( $gen_tot as $acma => $aantal  )
                {
                    echo "<tr>";
                    echo "<td>";
                    echo $acma_arr[$acma];
                    echo " : </td>";
                    echo "<td>";
                    echo $aantal;
                    echo "</td>";
                    echo "</tr>";    
                }
                echo "</table>";
            }
            
            
            echo "</td>";
            echo "</tr>";
            echo "</table>";
    	}
    	
    	?>
        </div>
        
        <!-- huurkoop -->
        <div id="tabs-3a">
        Hier wordt gekeken naar de verkoopsdatum.<br /><br />
    	<form method='post' name='frm_overzicht_huurkoop' id='frm_overzicht_huurkoop' >
        <input type="hidden" name="tab_id" id="tab_id" value="3" />
    	Kies een jaar : 
    	<select name='jaar' id='jaar'>
    	<?php 
    	
    	$startjaar = 2011;
    	
    	for($i=$startjaar;$i<date('Y')+2;$i++ )
    	{
            if( isset( $_POST["jaar"] ) && $_POST["jaar"] == $i )
            {
                echo "<option selected='selected' value=".$i.">".$i."</option>";
            }else
            {
                if( $i == date('Y') && !isset( $_POST["jaar"] ) )
                {
                    echo "<option selected='selected' value=".$i.">".$i."</option>";
                }else{
                    echo "<option value=".$i.">".$i."</option>";    
                }
            }
    	}
    	
    	?>
    		
    	</select>
    	<input type='submit' name='go' value='Go' id='go' />
    	</form>
    	<br/>
    
    	<?php 
    
    	
    	if( !isset( $_POST["jaar"] ) )
    	{
    		$jaar = date('Y');	
    	}else
    	{
    		$jaar = $_POST["jaar"];
    	}
    	
    	$overzicht = array();
    	
        for($i=1;$i<53;$i++)
    	{
    		$dayTimes = getDaysInWeek($i, $jaar);
    		foreach ($dayTimes as $dayTime) {
    			$datum = date("d-m-Y", $dayTime);	
    		
    			if( !isset( $overzicht[$i]["startdatum"] ) )
    		  	{
    		  		$overzicht[$i]['startdatum'] = $datum; 	
    		  	}
    		  	
    		  	$overzicht[$i]['einddatum'] = $datum;
    		  	
    			if( $_SESSION[ $session_var ]->group_id == 3 )
    			{
    				if( $_SESSION[ $session_var ]->user_id == 29 )
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '3' AND cus_acma IN(". $klanten_onder_frans .") AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    					
    				}else
    				{
    					$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '3' AND cus_acma = '".$_SESSION[ $session_var ]->user_id."' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    				}
    			}else
    			{
    				$q_datum = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active='1' AND cus_verkoop = '3' AND cus_verkoop_datum = '" . date("Y-m-d", $dayTime) . "'");
    			}
    			
    			// AND uit_cus_id = 0 
    			
    		  	while($rij = mysqli_fetch_object($q_datum))
    		  	{
    		  		if( $rij->uit_cus_id != 0 )
    		  		{
    		  			// dan is het een subklant
    		  			$hoofdklant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $rij->uit_cus_id));
    		  			
    		  			// tellen van het aantal dagen verschil
    		  			$hoofd_dat = explode("-", $hoofdklant->cus_verkoop_datum );
    		  			$sub_dat = explode("-", $rij->cus_verkoop_datum );
    		  			
    		  			$h_dat = mktime( 0, 0, 0, $hoofd_dat[1], $hoofd_dat[2], $hoofd_dat[0] );
    		  			$s_dat = mktime( 0, 0, 0, $sub_dat[1], $sub_dat[2], $sub_dat[0] );
    		  			
    		  			
    		  			$verschil = ($s_dat - $h_dat) / 86400;
    		  			
    		  			if( $verschil > 30 )
    		  			{
    		  				$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;
    		  			}
    		  		}else
    		  		{
    		  			$overzicht[$i]["acmas"][ $rij->cus_acma ][] = $rij->cus_id;	
    		  		}
    		  	}
    		}	
    	}
        
    	if( count( $overzicht ) > 0 )
    	{
            echo "<table width='100%'>";
            echo "<tr>";
            echo "<td valign='top'>";
           
    		echo "<table cellpadding='0' cellspacing='0' border='1' class='maand_overzicht' >";
    		echo "<tr>";
    		echo "<td colspan='3' align='center'>". $jaar ."</td>";
    		echo "</tr>";
    		
    		echo "<tr>";
    		echo "<td width='50' >&nbsp;Week&nbsp;</td>";
            echo "<td width='180'>&nbsp;</td>";
    		echo "<td width='180' align='center'>&nbsp;ACMA + #overeenkomsten&nbsp;</td>";
    		echo "</tr>";
    		
            
            $gen_tot = array();
    		foreach( $overzicht as $wn => $detail )
    		{
    			echo "<tr>";
    			echo "<td title='". $detail["startdatum"] . " - " . $detail["einddatum"] ."' align='center' valign='middle' >".$wn."</td>";
    			echo "<td>" . $detail["startdatum"] . " - " . $detail["einddatum"] . "</td>";
    			echo "<td class='maand_td' align='left' >";
    			
    			if( isset($detail["acmas"]) )
    			{
    				foreach( $detail["acmas"] as $acma => $klanten )
    				{
    					echo $acma_arr[ $acma ] . " : ";
    					echo count( $klanten );
    					
    					if( isset( $gen_tot[$acma] ) )
                        {
                            $gen_tot[$acma] += count($klanten);
                        }else
                        {
                            $gen_tot[$acma] = count($klanten);
                        }
    					
    					echo "<br>";
    				}
    			}
    			echo "</td>";
    			echo "</tr>";
    		}
    		
    		echo "</table>";
            
            echo "</td>";
            echo "<td valign='top'>";
            echo "<b>Totalen</b>";
            
            if( count( $gen_tot ) > 0 )
            {
                echo "<table>";
                
                foreach( $gen_tot as $acma => $aantal  )
                {
                    echo "<tr>";
                    echo "<td>";
                    echo $acma_arr[$acma];
                    echo " : </td>";
                    echo "<td>";
                    echo $aantal;
                    echo "</td>";
                    echo "</tr>";    
                }
                echo "</table>";
            }
            
            
            echo "</td>";
            echo "</tr>";
            echo "</table>";
    	}
    	
    	?>
        </div>
        
        <?php
        if( $_SESSION[ $session_var ]->group_id == 1 || $_SESSION[ $session_var ]->user_id == 19 || $_SESSION[ $session_var ]->user_id == 29 )
        {
        ?>
        <div id="tabs-4" style="height: 500px;" >
            <strong>Zonnepanelen :</strong> Hier wordt gekeken naar de datum waarop de klant is toegevoegd.<br />
            <strong>Zonneboiler : </strong> Is niet meegenomen in de statistieken omdat het grootste deel op nee staat. Is gekomen door mailing boiler en automatisch toegekend aan acma. <br /><br />
            <br />Controle bestand klanten met een overeenkomst waarbij de overeenkomstdatum niet is ingevuld : <a href="http://www.solarlogs.be/kalender/check_date.php" target="_blank">klik hier</a>
            <br /><br />
            <!--
            <form method="post" name="frm_leads" id="frm_leads">
            Maand :
            <select name="sel_maand" id="sel_maand">
            <option value='alles'>Al de maanden samen</option>
            <?php
                for( $i=1;$i<13;$i++ )
                {
                    echo "<option value='". $i ."'>". $i ."</option>";
                }
            ?>
            </select>
            
            Jaar :
            <select name="sel_jaar" id="sel_jaar">
            <?php
                for( $i=2011;$i<date('Y')+1;$i++ )
                {
                    echo "<option value='". $i ."'>". $i ."</option>";
                }            
            ?>
            </select>
            <input type="submit" name="zoek" id="zoek" value="Zoek" />
            <input type="hidden" name="tab_id" id="tab_id" value="3" />
            </form>
            -->
            <?php
            
            if( !isset( $_POST["sel_maand"] ) )
            {
                $_POST["sel_maand"] = "";
            }
            
            if( isset( $_POST["zoek"] ) && $_POST["zoek"] == "Zoek" || 1 == 1 )
            {
                if( isset($_POST["sel_maand"]) && $_POST["sel_maand"] == 'alles' )
                {
                    $_POST["sel_maand"] = "";
                }else
                {
                    if( isset($_POST["sel_maand"]) && $_POST["sel_maand"] < 10 )
                    {
                        $_POST["sel_maand"] = "0" . $_POST["sel_maand"] . "-";
                    }else
                    {
                        $_POST["sel_maand"] = $_POST["sel_maand"] . "-";
                    }
                }
                
                //echo "<br/><br/><b>Overzicht van periode " . $_POST["sel_maand"] . " " . $_POST["sel_jaar"] ."</b><br/><br/>";  
                
                //$q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = '0' AND cus_active = '1' AND cus_date_added LIKE '". $_POST["sel_jaar"] . "-" . $_POST["sel_maand"] ."%' ") or die( mysqli_error($conn) );
                
                $q_zoek = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = '0' AND cus_active = '1' ") or die( mysqli_error($conn) );
                
                $sum_acma = array();
                if( mysqli_num_rows($q_zoek) > 0 )
                {
                    while( $rij = mysqli_fetch_object($q_zoek) )
                    {
                        if( !empty( $rij->cus_acma ) && $rij->cus_acma != 19 && $rij->cus_acma != 26 && $rij->cus_acma != 22 && $rij->cus_acma != 28 && $rij->cus_acma != 31 && $rij->cus_acma != 36 )
                        {
                            if( isset( $sum_acma[ $rij->cus_acma ] ) )
                            {
                                $sum_acma[ $rij->cus_acma ]["aantal"] += 1;    
                                
                                switch( $rij->cus_verkoop )
                                {
                                    case '0' :
                                        $sum_acma[ $rij->cus_acma ]["geen_over"] += 1;
                                        break;
                                    case '' :
                                        $sum_acma[ $rij->cus_acma ]["open"] += 1;
                                        break;
                                    case '1' :
                                        
                                        if( isset( $sum_acma[ $rij->cus_acma ]["verkoop"] ) )
                                        {
                                            $sum_acma[ $rij->cus_acma ]["verkoop"] += 1;
                                        }else
                                        {
                                            $sum_acma[ $rij->cus_acma ]["verkoop"] = 1;
                                        }
                                        
                                        break;
                                    case '2' :
                                        $sum_acma[ $rij->cus_acma ]["verhuur"] += 1;
                                        break;
                                    case '3' :
                                        $sum_acma[ $rij->cus_acma ]["huurkoop"] += 1;
                                        break;
                                }
                                
                                if( !empty( $rij->cus_offerte_besproken ) && $rij->cus_offerte_besproken != '@@' )
                                {
                                    if( isset( $sum_acma[ $rij->cus_acma ]["offerte"] ) )
                                    {
                                        $sum_acma[ $rij->cus_acma ]["offerte"] += 1;
                                    }else
                                    {
                                        $sum_acma[ $rij->cus_acma ]["offerte"] = 1;
                                    }
                                }
                                
                            }else
                            {
                                $sum_acma[ $rij->cus_acma ]["aantal"] = 1;    
                                switch( $rij->cus_verkoop )
                                {
                                    case '0' :
                                        $sum_acma[ $rij->cus_acma ]["geen_over"] = 1;
                                        break;
                                    case '' :
                                        $sum_acma[ $rij->cus_acma ]["open"] = 1;
                                        break;
                                    case '1' :
                                        $sum_acma[ $rij->cus_acma ]["verkoop"] = 1;
                                        break;
                                    case '2' :
                                        $sum_acma[ $rij->cus_acma ]["verhuur"] = 1;
                                        break;
                                    case '3' :
                                        $sum_acma[ $rij->cus_acma ]["huurkoop"] = 1;
                                        break;
                                }
                                
                                if( !empty( $rij->cus_offerte_besproken ) && $rij->cus_offerte_besproken != '@@' )
                                {
                                    $sum_acma[ $rij->cus_acma ]["offerte"] = 1;
                                }
                            }
                        }
                    }
                    
                    echo "<span style='height:600;' >";
                    foreach( $sum_acma as $acma => $data )
                    {
                        // Controleer verhuur
                        if(!empty($data['verhuur'])){$verhuur = $data['verhuur'];}else{$verhuur = '';}
                        // Controleer huurkoop
                        if(!empty($data['huurkoop'])){$huurkoop = $data['huurkoop'];}else{$huurkoop = '';}
                        // Controleer geen_over
                        if(!empty($data['geen_over'])){$geen_over = $data['geen_over'];}else{$geen_over = '';}
                        
                        $tot = (($data["verkoop"] + $verhuur + $huurkoop) / $data["offerte"])*100;
                        $tot = number_format($tot, 0, "", "");
                        
                        $tot1 = (($data["verkoop"] + $verhuur + $huurkoop) / $data["aantal"])*100;
                        $tot1 = number_format($tot1, 0, "", "");
                        
                        echo "<div style='display:block;float:left;'>";
                        echo "<table style='border:1px solid black;' width='300' cellpadding='2' cellspacing='0' >";
                        echo "<tr style='background-color:silver;' ><td colspan='3' align='center' ><b>".$acma_arr[$acma]. "</b></td></tr>";
                        echo "<tr><td># Openstaande</td><td>". $data["open"] ."</td></tr>";
                        echo "<tr><td># Verkoop</td><td>". $data["verkoop"] ."</td></tr>";
                        echo "<tr><td># Verhuur</td><td>". $verhuur ."</td></tr>";
                        echo "<tr><td># Huurkoop</td><td>". $huurkoop ."</td></tr>";
                        echo "<tr><td># Geen overeenkomst</td><td>". $geen_over ."</td></tr>";
                        echo "<tr><td># Offerte besproken</td><td>". $data["offerte"] ."</td></tr>";
                        echo "<tr><td># Leads</td><td>". $data["aantal"] ."</td></tr>";
                        echo "<tr><td><b><img src='images/info.jpg' width='16' height='16' title='( verkoop + verhuur + huurkoop ) / # offertes' /> PERCENTAGE 1 </b></td><td><b>". $tot ."%</b></td></tr>";
                        echo "<tr><td><b><img src='images/info.jpg' width='16' height='16' title='( verkoop + verhuur + huurkoop ) / # leads' /> PERCENTAGE 2 </b></td><td><b>". $tot1 ."%</b></td></tr>";
                        echo "</table> <br/>";
                        echo "</div>&nbsp;";
                    }
                    
                    echo "&nbsp;</span>";
                    
                    /*
                    echo "<pre>";
                    var_dump( $sum_acma );
                    echo "</pre>";
                    */   
                }else
                {
                    echo "Geen gegevens gevonden voor deze periode."; 
                }
            }
            
            ?>
        </div>
        <?php
        
        }
        
        ?>
     </div>
</div>

<center>
<?php 

include "inc/footer.php";

?>
</center>

</body>
</html>