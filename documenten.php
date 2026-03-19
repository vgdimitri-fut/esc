<?php 

session_start();
include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

if( isset( $_GET["actie"] ) && $_GET["actie"] == "del" )
{
    $q = "SELECT * FROM kal_customers_files WHERE cf_soort = 'documenten' AND cf_cus_id = " . $_GET["files"];
    $q_zoek_files = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q ) ;
    
    if( mysqli_num_rows($q_zoek_files) > 0 )
    {
        while( $file = mysqli_fetch_object($q_zoek_files) )
        {
            unlink("module_docs/" . $_GET["files"] . "/" . $file->cf_file );
        }
    }
    
    rmdir( "module_docs/" . $_GET["files"] );
    
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_soort = 'documenten' AND cf_cus_id = " . $_GET["files"]) or die( mysqli_error($conn) ) ;
    mysqli_query($conn, "DELETE FROM kal_docs WHERE id = " . $_GET["files"]) or die( mysqli_error($conn) );
    
}

if( isset( $_POST["opslaan"] ) && $_POST["opslaan"] == "Save" )
{
    $q_ins = "INSERT INTO kal_docs(info) VALUES('". htmlentities($_POST["info"], ENT_QUOTES) ."')";
    
    $ok = mysqli_query($conn, $q_ins) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q_ins );
    
    if( $ok )
    {
        $id = mysqli_insert_id($conn);
        
        if( !empty( $_FILES["file_doc"]["name"] ) )
        {
            // aantal bestanden te uploaden
            $aantal_bestanden = count($_FILES["file_doc"]["name"]);
            // verander dir naar lev_docs
            chdir( "module_docs/");
            // maak dir met lev_id
            @mkdir( $id );
            // ga daar in
            chdir( $id );
            
            // loop bestanden
            for($i=0;$i<$aantal_bestanden;$i++)
            {
                // upload bestand
                $upload_ok = move_uploaded_file($_FILES['file_doc']['tmp_name'][$i], $_FILES['file_doc']['name'][$i]);
    
                // insert naar database
                $q_ins = "INSERT INTO kal_customers_files(cf_cus_id,cf_soort,cf_file) 
                                                   VALUES(". $id .",'documenten','".$_FILES["file_doc"]["name"][$i]."')";
                // als bestand geupload is dan voer query uit
                if( $upload_ok )
                {
                    mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );    
                } 
            }
            
            chdir( "../../" );
            
        }
    }
}

    
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<title>
Documents<?php include "inc/erp_titel.php" ?>
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
</head>

<script type="text/javascript">

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
    $( "#frm_doc" ).validate();
});

</script>

<body>

<div id='pagewrapper'>
	<?php include('inc/header.php'); ?>
	
	<h1>Documents<?php include "inc/erp_titel.php" ?></h1>
	
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">Add documents</a></li>
            <li><a href="#tabs-2">Documents</a></li>
		</ul>
		<div id="tabs-1">
            
            <form method='post' id='frm_doc' name='frm_doc' action='' enctype='multipart/form-data'>
            
            <table>
            <tr>
                <td colspan="2">
                    <strong>Information :</strong>
                </td>
            </tr>
            
            <tr>
                <td colspan="2">
                    <textarea class="required" name="info" id="info" style="width: 800px;height:200px;" ></textarea>
                </td>
            </tr>
            
            <tr>
                <td>
                    <strong>Files :</strong>
                </td>
                <td>
                    <input type="file" class="required" name="file_doc[]" id="file_doc" multiple />
                </td>
            </tr>
            
            </table>
            <input type="submit" name="opslaan" id="opslaan" value="Save" />
            </form>
		</div>
        
        
        <div id="tabs-2">
        <?php
        
        // zoeken
        $q = "SELECT * FROM kal_docs";
        $q_zoek = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q );
        
        if( mysqli_num_rows($q_zoek) > 0 )
        {
            echo "<table border='1' cellspacing='0' width='100%' >";
            echo "<tr>";
            echo "<td width='20' >&nbsp;</td>";
            echo "<td><strong>Info</strong></td>";
            echo "<td><strong>Files</strong></td>";
            echo "</tr>";
            
            while( $rij = mysqli_fetch_object($q_zoek) )
            {
                echo "<tr>";
                
                echo "<td valign='top' >";
                echo "<a href='documenten.php?tab_id=1&actie=del&files=". $rij->id ."' onclick='return confirm(\"Delete?\");' ><img src='images/delete.png' alt='Delete' title='Delete' /></a>";
                echo "</td>";
                
                echo "<td valign='top' >";
                echo $rij->info;
                echo "</td>";
                echo "<td valign='top' >";
                
                // zoeken naar bestanden.
                $q_zoek1 = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'documenten' AND cf_cus_id = " . $rij->id);
                
                while( $file = mysqli_fetch_object($q_zoek1) )
                {
                    $file1 = "module_docs/" . $rij->id . "/" . $file->cf_file;
                    
                    if( file_exists( $file1 ) )
                    {
                        echo "<a href='".$file1."' target='_blank' >";
                        echo $file->cf_file;
                        echo "</a><br />";
                    }
                }
                
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";            
        }else
        {
            echo "No documents found.";
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