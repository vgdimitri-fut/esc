<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_GET["bet_id"]));
$lev = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_leveranciers WHERE id = " . $betaling->lev_id));

/*
echo "<pre>";
var_dump( $_POST );
echo "</pre>";
*/
/*
if( isset( $_POST["opslaan_protest"] ) && $_POST["opslaan_protest"] == "Opslaan" )
{
    $q_upd = "UPDATE kal_betalingen SET reden = '" . htmlentities( $_POST["reden"] , ENT_QUOTES) . "', approved = '0' WHERE id = " . $_POST["bet_id"];
    mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );

    $betaling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_betalingen WHERE id = " . $_POST["bet_id"]));
    
    ?>
    <script type="text/javascript">
        parent.location.reload();    
    </script>
    <?php
}
*/

?>
<html>
<head>
<title>
Factuur protesteren
</title>
<link href="../css/style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

function check()
{
    var text = document.getElementById("reden").value;
    
    if( text == '' )
    {
        alert('Gelieve de reden in te vullen.');
        return false;
    }else
    {
        return true;
    }
}

var XMLHttpRequestObject3 = false;

try{
	XMLHttpRequestObject3 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject3 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject3 = false;
	}
 
	if(!XMLHttpRequestObject3 && window.XMLHttpRequest){
		XMLHttpRequestObject3 = new XMLHttpRequest();
	}
}

function save_value(id)
{
    //DIVOK = "kwhkwp";
    var waarde = document.getElementById("reden").value;
	var datasource = "klanten/betaling_opslaan.php?waarde=" + waarde + "&id=" + id;
    var params = "waarde=" + waarde + "&id=" + id;

	if(XMLHttpRequestObject3){
		//var obj = document.getElementById(DIVOK);

		XMLHttpRequestObject3.open("POST",datasource,true);
        
        XMLHttpRequestObject3.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        XMLHttpRequestObject3.setRequestHeader("Content-length", params.length);
        XMLHttpRequestObject3.setRequestHeader("Connection", "close");
        
		XMLHttpRequestObject3.onreadystatechange = function(){
			if(XMLHttpRequestObject3.readyState == 4 && XMLHttpRequestObject3.status == 200)
            {
                
                //alert("De ingevulde en berekende opbrengstfactor zijn hetzelfde.");
			}
		}
		
		//XMLHttpRequestObject3.send(null);
        XMLHttpRequestObject3.send(params);
	}
}

</script>

</head>
<body style="text-align: left;">
<div style="text-align: left;">
<b>Factuur protesteren</b><br/>
<?
    echo "<br/>Factuur Van : " . $lev->naam;
    echo "<br/>Factuur Nr. : " . $betaling->fac_nr;
    echo "<br/>Bedrag : " . number_format($betaling->bedrag_incl, 2, ",", " ");
    echo "<br/>Beschrijving : " . $betaling->beschrijving;
    echo "<br/>Factuur : <a href='../betalingen/".$betaling->scan."' target='_blank'>". $betaling->scan ."</a>";
    
    echo "<br/>";
    echo "<form method='post' name='frm_protest' id='frm_protest' onsubmit='return check();' >";
    echo "Reden protest :";
    echo "<br/><textarea name='reden' id='reden' style='width:400px;height:150px;' >";
    echo $betaling->reden;
    echo "</textarea>";
    //echo "<br><input type='submit' name='opslaan_protest' id='opslaan_protest' value='Opslaan' />";
    
    echo "<br><input type='button' name='save' id='save' value='Opslaan' onclick='save_value(".$betaling->id.");' />";
    
    echo "<input type='hidden' name='bet_id' id='bet_id' value='". $betaling->id ."' />";
    echo "</form>";

    if( isset( $_POST["opslaan_protest"] ) && $_POST["opslaan_protest"] == "Opslaan" )
    {
        echo "<span class='correct'>De reden is bewaard.</span>";
    }
    
    echo "<br/>";
    
    // ophalen en tonen van de protestbrief
    $q_brief = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'protest' AND cf_cus_id = " . $lev->id . " AND cf_file LIKE '%". $betaling->nr_intern ."%' LIMIT 1") or die( mysqli_error($conn) );
    
    while( $rij = mysqli_fetch_object($q_brief) )
    {
        $ex_date = explode(" ", $rij->cf_datetime);
        
        echo "<a onclick=\"javascript:return confirm('Protest brief verwijderen?')\" href='betalingen.php?tab_id=1&brief_id=".$rij->cf_id."&int_id=". $betaling->nr_intern ."'><img src='images/delete.png' alt='Protest brief verwijderen?' title='Protest brief verwijderen?' /></a>";
        echo "&nbsp;<a class='error' href='lev_docs/".$lev->id."/protest/".$rij->cf_file."' target='_blank'>Protest brief " . changeDate2EU($ex_date[0]) . " </a>"; 
    }
    
    echo "<br/><br/>";
    
    
    ?>
    <input type="button" value="Maak protest brief" onclick="window.open('klanten/betaling_protest_brief.php?bet_id=<?php echo $betaling->id; ?>','Facturatie GSC','status,width=1100,height=800,scrollbars=yes');" />
</div>
</body>
</html>