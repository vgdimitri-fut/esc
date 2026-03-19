<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

if( isset( $_POST["koppel"] ) && $_POST["koppel"] == "koppel" )
{
     
    if( isset( $_POST["meerdere_facs"] ) )
    {
        $coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE id = " . $_POST["coda_id"]));
        var_dump( $coda );
        $q_upd = "UPDATE kal_coda SET cf_id_fac = concat(cf_id_fac, '@". $_POST["sel_fac"] ."') WHERE id = " . $_POST["coda_id"];
    }else
    {
        $q_upd = "UPDATE kal_coda SET cf_id_fac = ". $_POST["sel_fac"] ." WHERE id = " . $_POST["coda_id"];    
    }
    
    echo $q_upd;
    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
}

// post waarden overzetten naar session waarden
unset($_SESSION["solarlogs_coda"]);
if( isset( $_POST["check_bedrag"] ) && $_POST["check_bedrag"] == "Splits" )
{
    $_SESSION["solarlogs_coda"] = $_POST["waarde"];
}

/*
echo "<pre>";
var_dump( $_POST );
var_dump( $_SESSION );
echo "</pre>";
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
Algemene kalender - CODA
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />

<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />

<script language="javascript" type="text/javascript" src="../jqplot/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.9.2.custom.min.js"></script>
<link href="css/start/jquery-ui-1.9.2.custom.min.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript">

function isNumberKey(evt)
{
   var charCode = (evt.which) ? evt.which : evt.keyCode;

   if (charCode > 31 && (charCode < 48 || charCode > 57 ) && charCode != 46 && charCode != 44)
      return false;

   return true;
}

function commadot(that) {
	if (that.value.indexOf(",") >= 0) 
	{
		that.value = that.value.replace(/\,/g,".");
	}
}

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> },
    {
        ajaxOptions: {
            beforeSend: function() {
                $('#loader').show();
            },
            complete: function() {
                $("#loader").hide();
            }
        }
    })
});

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
	<?php include('inc/header.php'); ?><br/>
	
	<h1>CODA</h1>
    Bij het openen van deze pagina worden de betalingen automatisch gekoppeld aan de facturen wanneer de bedragen overeenkomen.<br /><br />

    <?php
    
    $rest = "";
    if( isset( $_POST["splits"] ) && $_POST["splits"] == "Verder" )
    {
        $rest = "&sel_fac=" . $_POST["sel_fac"] . "&coda_id=" . $_POST["coda_id"] . "&sel_coda=" . $_POST["sel_coda"] . "&splits=Verder";
    }
    
    if( isset( $_POST["check_bedrag"] ) && $_POST["check_bedrag"] == "Splits" )
    {
        $rest = "&coda_id=" . $_POST["coda_id"] . "&sel_coda=" . $_POST["sel_coda"] . "&splits=Verder&check_bedrag=Splits";
    }
    
    ?>

    <div id="tabs" style="width: 1050;">
        <ul>
            <?php
            
            if( isset( $_GET["klant_id"] ) )
            {
                ?>
        
            	<li><a href="klanten/klanten_coda1.php?klant_id=<?php echo $_GET["klant_id"] . $rest; ?>" title="Nog te koppelen CODA's">Nog te koppelen CODA's</a></li>
                <li><a href="klanten/klanten_coda2.php?klant_id=<?php echo $_GET["klant_id"] . $rest; ?>" title="Gekoppelde CODA's">Gekoppelde CODA's</a></li>
                <?php
            }
            
            if( isset( $_GET["lev_id"] ) )
            {
                ?>
        
            	<li><a href="klanten/klanten_coda1.php?lev_id=<?php echo $_GET["lev_id"] . $rest; ?>" title="Nog te koppelen CODA's">Nog te koppelen CODA's</a></li>
                <li><a href="klanten/klanten_coda2.php?lev_id=<?php echo $_GET["lev_id"] . $rest; ?>" title="Gekoppelde CODA's">Gekoppelde CODA's</a></li>
                <?php
            }
            
            ?>
            
        </ul>
        
        
        <div id="Nog te koppelen CODA's">
            <div id="loader" style="display:none">Loading...</div>
        </div>
        
        <div id="Gekoppelde CODA's">
            <div id="loader" style="display:none">Loading...</div>
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