<?php 

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

if( isset( $_POST["bewaar"] ) && $_POST["bewaar"] == "Bewaar" )
{
    $q_fac = "INSERT INTO kal_customers_files(cf_cus_id, 
                                              cf_soort, 
                                              cf_file,
                                              cf_bedrag,
                                              cf_btw,
                                              cf_date) 
                                      VALUES('".$_POST["cus_id"]."',
                                             '". $_POST["sel_doc"] ."',
                                             '". $_FILES["bestand"]["name"] ."',
                                             '". $_POST["bedrag"] ."',
                                             '". $_POST["btw"] ."',
                                             '". changeDate2EU($_POST["datum"]) ."')";
                                             
    mysqli_query($conn, $q_fac) or die( mysqli_error($conn) );
    
    $fac_date = explode("-", changeDate2EU($_POST["datum"]));
    $mk_fac_date = mktime(0,0,0,$fac_date[1],$fac_date[2],$fac_date[0]);
    $begin_nw_bj = mktime(0,0,0,7,1,2012);
    
    $q_boekjaren = mysqli_query($conn, "SELECT * FROM kal_boekjaar");
        while($boekjaar = mysqli_fetch_object($q_boekjaren)){
            if(changeDate2EU($_POST["datum"]) > $boekjaar->boekjaar_start && changeDate2EU($_POST["datum"])<= $boekjaar->boekjaar_einde){
                $dir = $boekjaar->boekjaar_start . " - " . $boekjaar->boekjaar_einde;
            }
        }
    
	chdir( "cus_docs/");
	@mkdir( $_POST["cus_id"] );
	chdir( $_POST["cus_id"]);
	@mkdir( $_POST["sel_doc"] );
	chdir( $_POST["sel_doc"] );
    if( !empty( $dir ) )
    {
        @mkdir( $dir );
        chdir( $dir );
    }
    
    $a = $_FILES['bestand']['tmp_name']; 
    $b = $_FILES["bestand"]["name"];
    
	$t = move_uploaded_file( $_FILES['bestand']['tmp_name'], $_FILES["bestand"]["name"] );
	
    //var_dump( $t );
    
    if( !empty( $dir ) )
    {
        chdir("../../../../");
    }else
    {
        chdir("../../../");
    }
    
    if( $_POST["sel_doc"] == "factuur" )
    {
        chdir( "facturen" );    
    }else
    {
        chdir( "creditnota" );
    }
    
    if( !empty( $dir ) )
    {
        @mkdir( $dir );
        chdir( $dir );
    }
    
    //$t = move_uploaded_file( $a, $b );
    
    if( !empty( $dir ) )
    {
        $t = copy( "../../cus_docs/" . $_POST["cus_id"] . "/" . $_POST["sel_doc"] . "/" . $dir . "/" . $b , $b );
    }else{
        $t = copy( "../cus_docs/" . $_POST["cus_id"] . "/" . $_POST["sel_doc"] . "/" . $b , $b );
    }
    
    if( !empty( $dir ) )
    {
        chdir("../../");
    }else
    {
        chdir("../");
    }
    
    ?>
    <script language="javascript" type="text/javascript">
        window.opener.location = "klanten.php?tab_id=1&klant_id=<?php echo $_POST["cus_id"]; ?>";
        window.close();
    </script>
    <?php
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
ESC - Add Bills and Creditnotes
</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/ui.theme.css" rel="stylesheet" type="text/css" media="all" />

<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/functions.js"></script>

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript" src="../jquery/jquery.validate.min.js"></script>

<script type="text/javascript">

$(document).ready(function(){
	$("#frm_new_cus").validate();
});

$(function() {
	$( "#datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
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

<div id='pagewrapper' style="height: 400px !important;">
	<?php
$bestand = mysqli_fetch_object(mysqli_query($conn, "SELECT bedrijf_foto FROM kal_instellingen"));
?>
<img src='images/<?php echo $bestand->bedrijf_foto; ?>'/><br/>

<form method='post' id='frm_new_cus' name='frm_new_cus' action='' enctype='multipart/form-data'>
<table>
<tr>
    <td> Document type : </td>
    <td>
        <select name="sel_doc" id="sel_doc">
            <option value="creditnota">Creditnote</option>
            <option value="factuur">Bill</option>
        </select>
    </td>
</tr>

<tr>
    <td> File : </td>
    <td>
        <input type="file" class='required' name="bestand" id="bestand" />
    </td>
</tr>

<tr>
    <td> Date : </td>
    <td>
        <input type="text" class='required' name="datum" id="datum" />
    </td>
</tr>

<tr>
    <td> Amount incl. : </td>
    <td>
        <input type="text" class='required' name="bedrag" id="bedrag" />
    </td>
</tr>

<tr>
    <td> VAT rate : </td>
    <td>
        <input type="text" class='required' name="btw" id="btw" /> 0, 6 of 21
    </td>
</tr>

</table>
<input type="hidden" name="cus_id" id="cus_id" value="<?php echo $_GET["cus_id"]; ?>" />
<input type="submit" name="bewaar" id="bewaar" value="Bewaar" />
</form>


</div>
</body>
</html>