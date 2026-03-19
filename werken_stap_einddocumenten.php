<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";


// controle of na te kijken of de get aangepast werd door de klant;
if( isset( $_GET["opdracht_id"] ) && is_numeric($_GET["opdracht_id"]) && $_SESSION[$session_var]->group_id == 3 )
{
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $_GET["opdracht_id"] . " AND cus_id = " . $_SESSION[$session_var]->user_id);
    
    if( mysqli_num_rows($q_zoek) == 0 )
    {
        ?>
        <meta http-equiv="refresh" content="0;URL=werken.php" />
        <?php
        die();
    }
}

// controle of na te kijken of de get aangepast werd door de architecht;
if( isset( $_GET["opdracht_id"] ) && is_numeric($_GET["opdracht_id"]) && $_SESSION[$session_var]->group_id == 4 )
{
    if(strpos($_SESSION[$session_var]->username , 'archi') == 0)
    {
        $archi_id = substr($_SESSION[$session_var]->username, 5);
    }
    $q_zoek = mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $_GET["opdracht_id"] . " AND archi_id = " . $archi_id);
    
    if( mysqli_num_rows($q_zoek) == 0 )
    {
        ?>
        <meta http-equiv="refresh" content="0;URL=werken.php" />
        <?php
        die();
    }
}

$user_logged_in = $_SESSION[$session_var]->user_id;

// OPDRACHT ID
if (isset($_POST['opdracht_id'])) {
    $opdracht_id = $_POST['opdracht_id'];
}

if (isset($_GET['opdracht_id'])) {
    $opdracht_id = $_GET['opdracht_id'];
}

$instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen")); 
$opdracht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_opdrachten WHERE id = " . $opdracht_id));
$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $opdracht->cus_id));
$cus = $klant;

if( isset( $_POST["menu"] ) && $_POST["menu"] == "Terug naar overzicht" )
{
    if( $_SESSION[$session_var]->group_id != 4 )
    {
        $q_einddoc = "UPDATE kal_opdrachten SET einddocumenten='1' WHERE id=".$opdracht_id;
    
        mysqli_query($conn, $q_einddoc);
    }
    
    header('Location: werken.php');
    die();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv='Content-Type' content='text-html;charset=UTF-8' />

<title>Stap 8 - Einddocumenten<?php include "inc/erp_titel.php" ?></title>

<link rel="SHORTCUT ICON" href="favicon.ico" />

<link href="css/klanten.css" rel="stylesheet" type="text/css" />
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<script type="text/javascript" src="http://www.solarlogs.be/kalender/js/jquery.validate.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<script type="text/javascript" 	src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript" src="js/functions.js"></script>
<script type="text/javascript" src="js/klanten.js"></script>

<script type="text/javascript" src="js/googleanalytics.js"></script>

<script type="text/javascript">
$(function() {
    
    $('#vorige').live('click',function(){
            window.location.href = '<?php echo $werken_stappen_arr[6][1]."?opdracht_id=".$opdracht_id; ?>';
    })
});

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});

</script>
</head>
<body>

<div id='pagewrapper'><?php include('inc/header.php'); ?>
	
	<h1>Stap 8 - Einddocumenten</h1>

	<div id="tabs" style="width: 1000px;">
	
        <?php
                
        if( isset( $_GET["opdracht_id"] ) )
        {
        
            echo "<div id='tabs-2'>";
            
            ?>    
             <form method="post" name="frm_edit_opdracht" id="frm_edit_opdracht" class='frm_go' enctype='multipart/form-data'>
            
            <?php
            echo "<input type='hidden' name='cus_id' id='cus_id' value='".$opdracht->cus_id."' />";
            
            echo "<table border='0' width='100%' class='main_table' style='background-color:#CCFFCC;' >";
            echo "<tr valign='top'><td>";
            
            echo "<fieldset style='width:950px'>";
                echo "<legend>Documenten Enerdo<img src='images/indicator.gif' class='tech_img_load' /></legend>";
                echo "<table id='tbl_tech'>";
                
                if( $_SESSION[$session_var]->group_id != 4 )
                {
                    getverslagen( $opdracht->id , 7 , '&download=ja');
                
                    getverslagen( $opdracht->id , 8 , '&download=ja');

                    getverslagen( $opdracht->id , 9 , '&download=ja');
                }else{
                    getverslagen( $opdracht->id , 7);
                
                    getverslagen( $opdracht->id , 8);

                    getverslagen( $opdracht->id , 9);
                }
                
                
                echo "<tr><td><br /><br />";
                echo "<a href='pid_zipper.php?opdracht_id=".$opdracht_id."' alt='downloaden van PID' target='_blank'/><input type='button' name='pid_downloaden' value='Download PID mappen structuur' /></a><br />";
                echo "Gelieve er rekening mee te houden dat het enkele minuten duurt om de mappenstructuur te downloaden, even geduld aub.";
                echo "</td></tr>";
                
                echo "</table>";
                echo "</fieldset>";
                
            echo "</td></tr>";    
                            
            echo "</table>";
                        
            echo "<input type='hidden' name='tab_id' id='tab_id' value='2' />";
            echo "<input type='hidden' name='cus_id' id='cus_id' value='" . $cus->cus_id . "' />";
            echo "<input type='hidden' name='cus_id2' id='cus_id2' value='" . $cus->cus_id . "' />";
            
            echo "<input type='hidden' name='opdracht_id' id='opdracht_id' value='" . $opdracht->id . "' />";
    
            if (( $_SESSION[$session_var]->user_id == $cus->cus_acma) || empty($cus->cus_acma) || $_SESSION[$session_var]->group_id == 1 || $_SESSION[$session_var]->group_id == 4 || $_SESSION[$session_var]->group_id == 5 || $_SESSION[$session_var]->user_id == 29) {
                echo "<table border='0' width='100%' class='main_table' style='background-color:#CCFFCC;' >";
                echo "<tr><td>&nbsp;</td></tr>";
                
                echo "<tr><td colspan='2' align='center'>";
                ?>
                    <input type='submit' name='menu' id='menu' value='Terug naar overzicht' />
                <?php
    
                echo "</td></tr>";
                echo "<tr><td>&nbsp;</td></tr>";
                echo "</table>";
            }
    
            echo "</form>";
            echo "</div>";
        
        }
        
        ?>
        
	</div>
    
</div>
<center><?php 

include "inc/footer.php";

?></center>

</body>
</html>