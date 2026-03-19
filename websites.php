<?php
session_start();

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//var_dump($_POST);
include "inc/db_car_conn.php";
include "inc/functions.php";
include "inc/checklogin.php";


if(isset($_POST['btn_autovlan']))
{
    $q = "UPDATE kal_instellingen SET autovlan_ftp='".$_POST['lbl_ftp']."',autovlan_ftp_user='".$_POST['lbl_ftp_user']."',autovlan_ftp_pwd='".$_POST['lbl_ftp_pwd']."'";
    mysqli_query($conn, $q);
    $q = "SELECT * FROM kal_instellingen";
    $ftp = mysqli_fetch_object(mysqli_query($conn, $q));
    //*********** CREATE ZIP **************/
    $files_to_zip = 'xml/autovlan.xml';
    $filename = date('Ymdhis');
    //if true, good; if false, zip creation failed
    $result = create_zip($files_to_zip,'xml/zip/'.$filename.'.zip');
    
    /*********** UPLOAD TO FTP ***********/
    $file = 'xml/zip/'.$filename.'.zip';
    $remote_file = $filename . ".zip";
    
    // set up basic connection
    $conn_id = ftp_connect($ftp->autovlan_ftp);
    
    // login with username and password
    $login_result = ftp_login($conn_id, $ftp->autovlan_ftp_user, $ftp->autovlan_ftp_pwd);
    
    // turn passive mode on
    ftp_pasv($conn_id, true);
    
    // upload a file
    if (ftp_put($conn_id, $remote_file, $file, FTP_ASCII)) {
     echo "<span class='response'>successfully uploaded $file</span>";
     
    // update site historiek
     $new_ids = array();
     $used_ids = array();
     $get_producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE autovlan='1'");
     while($product = mysqli_fetch_object($get_producten))
     {
         $new_ids[] = $product->id;
     }
    
     // unselected products DELETE
     $get_used_ids = mysqli_query($conn, "SELECT product_id FROM tbl_site_historiek WHERE website=0 AND actie='1'"); // get all used ids
     if(mysqli_num_rows($get_used_ids) != 0) 
     {
         while($id = mysqli_fetch_object($get_used_ids)) // loop ids
        {
             $used_ids[] = $id->product_id;
             $last_action = mysqli_query($conn, "SELECT * FROM tbl_site_historiek WHERE product_id=".$id->product_id." AND website=0 ORDER BY id DESC LIMIT 1"); // check last action
             if(mysqli_num_rows($last_action) != 0) 
             {
                 $last_action_send = mysqli_fetch_object($last_action);
                 if(in_array($last_action_send->product_id,$new_ids )) // check if id exist in selected ids
                 {
                        // GESELECTEEERD
                        if($last_action_send->actie == '0')
                        {
                            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$last_action_send->name."',".$last_action_send->product_id.",'1',0)");
                        }
                 }else{
                        // NIET GESELECTEERD
                        if($last_action_send->actie == '1') // last action == send
                        {
                            // insert delete
                            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$last_action_send->name."',".$last_action_send->product_id.",'0',0)");
                        }
                 }
             }
        }
     }
     // insert new product
     $new =  array_diff($new_ids, $used_ids);
     if(!empty($new))
     {
         foreach($new as $value)
         {
             $data = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$value));
             mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$data->name."',".$data->id.",'1',0)");
         }
     }
     
     

     // delete zip file
     unlink($file);
    } else {
     echo "There was a problem while uploading $file\n";
    }
    
    // close the connection
    ftp_close($conn_id);
}

if(isset($_POST['btn_autoscout']))
{
    $q = "UPDATE kal_instellingen SET autoscout_ftp='".$_POST['lbl_ftp']."',autoscout_ftp_user='".$_POST['lbl_ftp_user']."',autoscout_ftp_pwd='".$_POST['lbl_ftp_pwd']."'";
    mysqli_query($conn, $q);
    // update site historiek
     $new_ids = array();
     $used_ids = array();
     $get_producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE autoscout24='1'");
     while($product = mysqli_fetch_object($get_producten))
     {
         $new_ids[] = $product->id;
     }
    
     // unselected products DELETE
     $get_used_ids = mysqli_query($conn, "SELECT product_id FROM tbl_site_historiek WHERE website=3 AND actie='1'"); // get all used ids
     if(mysqli_num_rows($get_used_ids) != 0) 
     {
         while($id = mysqli_fetch_object($get_used_ids)) // loop ids
        {
             $used_ids[] = $id->product_id;
             $last_action = mysqli_query($conn, "SELECT * FROM tbl_site_historiek WHERE product_id=".$id->product_id." AND website=3 ORDER BY id DESC LIMIT 1"); // check last action
             if(mysqli_num_rows($last_action) != 0) 
             {
                 $last_action_send = mysqli_fetch_object($last_action);
                 if(in_array($last_action_send->product_id,$new_ids )) // check if id exist in selected ids
                 {
                        // GESELECTEEERD
                        if($last_action_send->actie == '0')
                        {
                            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$last_action_send->name."',".$last_action_send->product_id.",'1',3)");
                        }
                 }else{
                        // NIET GESELECTEERD
                        if($last_action_send->actie == '1') // last action == send
                        {
                            // insert delete
                            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$last_action_send->name."',".$last_action_send->product_id.",'0',3)");
                        }
                 }
             }
        }
     }
     // insert new product
     $new =  array_diff($new_ids, $used_ids);
     if(!empty($new))
     {
         foreach($new as $value)
         {
             $data = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$value));
             mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$data->name."',".$data->id.",'1',3)");
         }
     }
     
    $file = 'xml/autoscout24.xml';
    $remote_file = 'autoscout24.xml';
    $q = "SELECT * FROM kal_instellingen";
    
    $ftp = mysqli_fetch_object(mysqli_query($conn, $q));
    
    // set up basic connection
    $conn_id = ftp_connect($ftp->autoscout_ftp);

    // login with username and password
    $login_result = ftp_login($conn_id, $ftp->autoscout_ftp_user, $ftp->autoscout_ftp_pwd);
    
//    // turn passive mode on
    ftp_pasv($conn_id, true);
    // upload a file
    ftp_put($conn_id, $remote_file, $file, FTP_BINARY);

    // close the connection
    ftp_close($conn_id);
}

function create_zip($file = '',$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(!empty($file)) {
                //make sure the file exists
                if(file_exists($file)) {
                        $valid_file[] = $file;
                }
	}
	//if we have good files...
	if(count($valid_file)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		//add the files
		$zip->addFile($file,$file);
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		
		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>Websites<?php include "inc/erp_titel.php" ?></title>

        <script type="text/javascript" src="js/functions.js"></script>
        
        <link href="css/style.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery.fancybox.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
        <link rel="stylesheet" type="text/css" media="print" href="css/print.css" />
        
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="js/jquery.fancybox.pack.js"></script>
        <script type="text/javascript" src="js/jquery.fancybox.js"></script>
        <script type="text/javascript" src="js/jquery.ui.widget.js"></script>
        <script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
        
        <style>
            th,td{
                text-align:left;
            }
            label{
                width:100px;
                float:left;
            }
            textarea{
                border:0;
                width:100%;
            }
            .response{
                color:green;
            }
            .vlanhistoriek,.tweedehandshistoriek,.autoscouthistoriek,.kapazahistoriek{
                float:right;
                background:#005083;
                padding:5px;
                color:white !important;
                border-radius:2px;
            }
            .historiek:hover{
                background:#002a44;
                border-radius:2px;
            }
            #btn_tweede{
                margin-top:10px;
            }
            .img_load{
                display:none;
            }
            input[type=checkbox]{
                margin-left:45%;
            }
        </style>
        <script type='text/javascript'>
            $(document).ready(function() {
                    $('tr[class^=extra]').hide();
                    // TOGGLE EXTRA DETAIL
                    $("tr[class^='product1_'] td:not(.no_extra)").live("click",function(){
                        var waarde = $(this).parent().attr('class');
                        var id = waarde.substr(9);
                       $('.extra1_' + id).toggle();
                       $.post("ajax/producten_ajax.php", {product_id: id,action: 'getList',websites:'autovlan'}, function(data) {
                            $('.tbl1_' + id).html(data);
                        });
                    });
                    // TOGGLE EXTRA DETAIL
                    $("tr[class^='product2_'] td:not(.no_extra)").live("click",function(){
                        var waarde = $(this).parent().attr('class');
                        var id = waarde.substr(9);
                       $('.extra2_' + id).toggle();
                       $.post("ajax/producten_ajax.php", {product_id: id,action: 'getList',websites:'autovlan'}, function(data) {
                            $('.tbl2_' + id).html(data);
                        });
                    });
                    // TOGGLE EXTRA DETAIL
                    $("tr[class^='product3_'] td:not(.no_extra)").live("click",function(){
                        var waarde = $(this).parent().attr('class');
                        var id = waarde.substr(9);
                       $('.extra3_' + id).toggle();
                       $.post("ajax/producten_ajax.php", {product_id: id,action: 'getList',websites:'autovlan'}, function(data) {
                            $('.tbl3_' + id).html(data);
                        });
                    });
                    // TOGGLE EXTRA DETAIL
                    $("tr[class^='product4_'] td:not(.no_extra)").live("click",function(){
                        var waarde = $(this).parent().attr('class');
                        var id = waarde.substr(9);
                       $('.extra4_' + id).toggle();
                       $.post("ajax/producten_ajax.php", {product_id: id,action: 'getList',websites:'autovlan'}, function(data) {
                            $('.tbl4_' + id).html(data);
                        });
                    });
                    var autovlan_height = $('.txt_xml').attr('scrollHeight');
                    $('.txt_xml').css('height',autovlan_height+100);
                    $('.response').fadeOut(5000);
                    $('a.vlanhistoriek').fancybox({type:'ajax',href:'auto/historiek/vlanhistoriek.php'});
                    $('a.autoscouthistoriek').fancybox({type:'ajax',href:'auto/historiek/autoscouthistoriek.php'});
                    $('a.tweedehandshistoriek').fancybox({type:'ajax',href:'auto/historiek/tweedehandshistoriek.php'});
                    $('a.kapazahistoriek').fancybox({type:'ajax',href:'auto/historiek/kapazahistoriek.php'});
                    
                    $('.tweedehands_send_all').live('click',function(){
                            if($(this).is(':checked'))
                            {
                                if($('.tweedehands_verzenden:checkbox').length == 0)
                                {
                                    alert('Te verzenden producten niet gevonden.');
                                    $('.tweedehands_send_all').attr('checked',false);
                                    return;
                                }
                                $('.tweedehands_verzenden:checkbox').each(function(){
                                    $(this).attr('checked',true);
                                });
                            }else{
                                $('.tweedehands_verzenden:checkbox').each(function(){
                                    $(this).attr('checked',false);
                                });
                            }
                    });
                    $('.tweedehands_del_all').live('click',function(){
                            if($(this).is(':checked'))
                            {
                                if($('.tweedehands_verwijderen:checkbox').length == 0)
                                {
                                    alert('Verzonden producten niet gevonden.');
                                    $('.tweedehands_del_all').attr('checked',false);
                                    return;
                                }
                                $('.tweedehands_verwijderen:checkbox').each(function(){
                                    $(this).attr('checked',true);
                                });
                            }else{
                                $('.tweedehands_verwijderen:checkbox').each(function(){
                                    $(this).attr('checked',false);
                                });
                            }
                    });
                    $('#btn_tweede').live("click",function(){
                        var ids = [];
                        $('.tweedehands_verzenden:checkbox:checked').each(function(){
                            ids.push($(this).val());
                        });
                        if(ids.length == 0)
                        {
                            alert('Geen gekozen');
                        }else{
                            $.ajax({
                                url:'auto/2dehands.php',
                                data: {action:'upload',ids:ids},
                                type:'POST',
                                beforeSend: function() {
                                    $('.img_load').show();
                                },
                                success:function(){
                                    $('.img_load').hide();
                                    $('.txt_ajax_finish').html('Auto is toegevoegd.').fadeOut(5000);
                                    $('#tweedehands_tabs').submit();
                                }
                            });
                        }
                    });
                    $('#btn_tweede_del').live("click",function(e){
                        e.stopPropagation();
                        var ids = [];
                        $('.tweedehands_verwijderen:checkbox:checked').each(function(){
                            ids.push($(this).val());
                            $('.product_' + $(this).val()).remove();
                        });
                        if(ids.length == 0)
                        {
                            alert('Geen gekozen');
                        }else{
                            $.ajax({
                                url:'auto/2dehands_del.php',
                                data: {product_id: ids},
                                type:'POST',
                                beforeSend: function() {
                                    $('.img_load').show();
                                },
                                success:function(){
                                    $('.img_load').hide();
                                    $('#tweedehands_tabs').submit();
                                }
                            });
                        }
                    });
                    $('.kapaza_send_all').live('click',function(){
                            if($(this).is(':checked'))
                            {
                                if($('.kapaza_verzenden:checkbox').length == 0)
                                {
                                    alert('Te verzenden producten niet gevonden.');
                                    $('.kapaza_send_all').attr('checked',false);
                                    return;
                                }
                                $('.kapaza_verzenden:checkbox').each(function(){
                                    $(this).attr('checked',true);
                                });
                            }else{
                                $('.kapaza_verzenden:checkbox').each(function(){
                                    $(this).attr('checked',false);
                                });
                            }
                    });
                    $('.kapaza_del_all').live('click',function(){
                            if($(this).is(':checked'))
                            {
                                if($('.kapaza_verwijderen:checkbox').length == 0)
                                {
                                    alert('Verzonden producten niet gevonden.');
                                    $('.kapaza_del_all').attr('checked',false);
                                    return;
                                }
                                $('.kapaza_verwijderen:checkbox').each(function(){
                                    $(this).attr('checked',true);
                                });
                            }else{
                                $('.kapaza_verwijderen:checkbox').each(function(){
                                    $(this).attr('checked',false);
                                });
                            }
                    });
                    $('#btn_kapaza').live("click",function(){
                        var ids = [];
                        $('.kapaza_verzenden:checkbox:checked').each(function(){
                            ids.push($(this).val());
                        });
                        if(ids.length == 0)
                        {
                            alert('Geen gekozen');
                        }else{
                            $.ajax({
                                url:'auto/kapaza.php',
                                data: {action:'upload',ids:ids},
                                type:'POST',
                                beforeSend: function() {
                                    $('.img_load').show();
                                },
                                success:function(){
                                    $('.img_load').hide();
                                    $('.txt_ajax_finish').html('Auto is toegevoegd.').fadeOut(5000);
                                    $('#kapaza_tabs').submit();
                                }
                            });
                        }
                    });
                    $('#btn_kapaza_del').live("click",function(e){
                        e.stopPropagation();
                        var ids = [];
                        $('.kapaza_verwijderen:checkbox:checked').each(function(){
                            ids.push($(this).val());
                            $('.product_' + $(this).val()).remove();
                        });
                        if(ids.length == 0)
                        {
                            alert('Geen gekozen');
                        }else{
                            $.ajax({
                                url:'auto/kapaza_del.php',
                                data: {product_id: ids},
                                type:'POST',
                                beforeSend: function() {
                                    $('.img_load').show();
                                },
                                success:function(){
                                    $('.img_load').hide();
                                    $('#kapaza_tabs').submit();
                                }
                            });
                        }
                    });
            });
            $(function() {
                $("#tabs").tabs({selected: <?php if (isset($_REQUEST["tab_id"])) {
                    echo $_REQUEST["tab_id"];
                } else {
                    echo 0;
                }; ?>});
            });            
            
        </script>
    </head>
    <body>

        <div id='pagewrapper'>
<?php include('inc/header.php'); ?>
            <h1>Websites</h1>

            <div id="tabs">
                <ul>
                    <li><a href="#tabs-1">AutoVlan.be</a></li>
                    <li><a href="#tabs-2">2dehands.be</a></li>
                    <li><a href="#tabs-3">Kapaza.be</a></li>
                    <li><a href="#tabs-4">Autoscout24.be</a></li>
                </ul>

                <div id="tabs-1">
                    <?php 
                        $getFTP = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
                    ?>
                    <form method="post">
                        <a class="vlanhistoriek" href="auto/historiek/vlanhistoriek.php">Historiek</a>
                        <h3>FTP gegevens:</h3>
                        <label for="lbl_ftp">Ftp server: </label>
                        <input type="text" name="lbl_ftp" id="lbl_ftp" <?php if(isset($getFTP->autovlan_ftp))echo "value='".$getFTP->autovlan_ftp."'"; ?>/><br />
                        <label for="lbl_ftp_user">User: </label>
                        <input type="text" name="lbl_ftp_user" id="lbl_ftp_user" <?php if(isset($getFTP->autovlan_ftp_user))echo "value='".$getFTP->autovlan_ftp_user."'"; ?>/><br />
                        <label for="lbl_ftp_pwd">Paswoord: </label>
                        <input type="text" name="lbl_ftp_pwd" id="lbl_ftp_pw" <?php if(isset($getFTP->autovlan_ftp_pwd))echo "value='".$getFTP->autovlan_ftp_pwd."'"; ?>/>
                        <input type="submit" name="btn_autovlan" id="btn_autovlan" value="Verzend naar autovlan.be"/>
                        <br /><br />
                        <hr />
                        <h3>Auto's: </h3>
                        <table>
                            <tr>
                                <th>Merk</th>
                                <th>Model</th>
                                <th>Naam</th>
                                <th>Status</th>
                            </tr>
                        <?php
                            $i=0;
                            $producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE autovlan='1'");
                            if(mysqli_num_rows($producten) == 0)
                            {
                                echo "<tr><td colspan='4'>Geen gekozen.</td></tr>";
                            }else
                            {
                            
                                while($auto = mysqli_fetch_object($producten))
                                {
                                    $brandname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$auto->product_brand_id));
                                    $modelname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$auto->product_model_id));
                                    $i++;
                                    $kleur = $kleur_grijs;
                                    if ($i % 2) {
                                        $kleur = "white";
                                    }
                                    echo "<tr class='product1_".$auto->id."' title='1' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                    echo "<td width='100'>".$brandname->naam."</td>";
                                    echo "<td width='100'>".$modelname->naam."</td>";
                                    echo "<td>".$auto->name."</td>";
                                    $q = "SELECT * FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$auto->id." AND a.product_fields_id=67 AND a.value=b.id";
                                    $conditie = mysqli_fetch_array(mysqli_query($conn, $q));
                                    echo "<td>".$conditie['choice']."</td>";
                                    echo "</tr>";
                                    // extra info
                                    echo "<tr class='extra1_".$auto->id."'><td colspan='4'><table style='margin-left:60px;' cellpadding='0' cellspacing='0' class='tbl1_".$auto->id."'>";

                                    echo "</table></td></tr>";
                                }
                             }
                        ?>
                        </table>
                        <hr />
                        <h3>XML bestand:</h3>
                        <textarea style="font-size:10px;" spellcheck="false" class='txt_xml' border="0" name="xml" readonly><?php $xml = file_get_contents("xml/autovlan.xml");echo $xml; ?></textarea>
                        
                    </form>
                </div>
                <div id="tabs-2">
                    <a class="tweedehandshistoriek" href="auto/historiek/tweedehandshistoriek.php">Historiek</a>
                    <h3>Auto's: </h3>
                    <table cellpadding="0" cellspacing="0">
                            <tr>
                                <th>Merk</th>
                                <th>Model</th>
                                <th>Naam</th>
                                <th>Status</th>
                                <th>Verzenden</th>
                                <th>Verwijderen</th>
                            </tr>
                        <?php
                            $i=0;
                            $producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE tweedehands='1'");
                            if(mysqli_num_rows($producten) == 0)
                            {
                                echo "<tr><td colspan='4'>Geen gekozen.</td></tr>";
                            }else
                            {
                                echo "<tr>";
                                echo "<td></td><td></td><td></td><td></td>";
                                echo "<td><input type='checkbox' class='tweedehands_send_all' /></td>";
                                echo "<td><input type='checkbox' class='tweedehands_del_all' /></td>";
                                echo "</tr>";
                            
                                while($auto = mysqli_fetch_object($producten))
                                {
                                    $brandname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$auto->product_brand_id));
                                    $modelname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$auto->product_model_id));
                                    $i++;
                                    $kleur = $kleur_grijs;
                                    if ($i % 2) {
                                        $kleur = "white";
                                    }             
                                    
                                    echo "<tr class='product2_".$auto->id."' title='2' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                    echo "<td width='100'>".$brandname->naam."</td>";
                                    echo "<td width='100'>".$modelname->naam."</td>";
                                    echo "<td>".$auto->name."</td>";
                                    $q = "SELECT * FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$auto->id." AND a.product_fields_id=67 AND a.value=b.id";
                                    $conditie = mysqli_fetch_array(mysqli_query($conn, $q));
                                    echo "<td>".$conditie['choice']."</td>";
                                    
                                    // id -> check if id exist and last id's action is not 0
                                    $q_historiek_verwijderd = mysqli_query($conn, "SELECT id FROM tbl_site_historiek WHERE website='1' AND product_id=".$auto->id." ORDER BY id DESC LIMIT 1");
                                    if(mysqli_num_rows($q_historiek_verwijderd) > 0) // als er een record bestaat
                                    {
                                        $id = mysqli_fetch_object($q_historiek_verwijderd);
                                        $check_action = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_site_historiek WHERE id=".$id->id)); 
                                        if($check_action->actie == '0') // controleer verwijderd
                                        {
                                            $delete = 0;
                                        }else{ // verzonden
                                            $delete = 1;
                                        }
                                    } else{ // geen record
                                        $delete = 0;
                                    }
                                            
                                    if($delete == 0)
                                    {
                                        echo "<td class='no_extra'><input type='checkbox' name='tweedehands_verzenden' class='tweedehands_verzenden' value='".$auto->id."' checked/> </td><td class='no_extra'></td>";
                                    }else
                                    {
                                        echo "<td class='no_extra'></td><td class='no_extra'><input type='checkbox' name='tweedehands_verwijderen' class='tweedehands_verwijderen' value='".$auto->id."' checked/> </td>";
                                    }
                                    echo "</tr>";
                                    // extra info
                                    echo "<tr class='extra2_".$auto->id."'><td colspan='6'><table style='margin-left:60px;' cellpadding='0' cellspacing='0' class='tbl2_".$auto->id."'>";

                                    echo "</table></td></tr>";
                                }
                             }
                        ?>
                        </table>
                    <form id="tweedehands_tabs" method="post">
                    <input type='hidden' name='tab_id' value='1' /> 
                    </form>
                    <input type="submit" id="btn_tweede" name="btn_tweede" value="Verzenden"/><input type="submit" id="btn_tweede_del" name="btn_tweede_del" value="Verwijderen"/><img class="img_load" src="images/indicator.gif" alt="loading..."/><span class="txt_ajax_finish"></span>
                </div>
                <div id="tabs-3"><a class="kapazahistoriek" href="auto/historiek/kapazahistoriek.php">Historiek</a>
                    <h3>Auto's: </h3>
                    <table cellpadding="0" cellspacing="0">
                            <tr>
                                <th>Merk</th>
                                <th>Model</th>
                                <th>Naam</th>
                                <th>Status</th>
                                <th>Verzenden</th>
                                <th>Verwijderen</th>
                            </tr>
                        <?php
                            $i=0;
                            $producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE kapaza='1'");
                            if(mysqli_num_rows($producten) == 0)
                            {
                                echo "<tr><td colspan='4'>Geen gekozen.</td></tr>";
                            }else
                            {
                                echo "<tr>";
                                echo "<td></td><td></td><td></td><td></td>";
                                echo "<td><input type='checkbox' class='kapaza_send_all' /></td>";
                                echo "<td><input type='checkbox' class='kapaza_del_all' /></td>";
                                echo "</tr>";
                            
                                while($auto = mysqli_fetch_object($producten))
                                {
                                    $brandname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$auto->product_brand_id));
                                    $modelname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$auto->product_model_id));
                                    $i++;
                                    $kleur = $kleur_grijs;
                                    if ($i % 2) {
                                        $kleur = "white";
                                    }             
                                    
                                    echo "<tr class='product3_".$auto->id."' title='2' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                    echo "<td width='100'>".$brandname->naam."</td>";
                                    echo "<td width='100'>".$modelname->naam."</td>";
                                    echo "<td>".$auto->name."</td>";
                                    $q = "SELECT * FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$auto->id." AND a.product_fields_id=67 AND a.value=b.id";
                                    $conditie = mysqli_fetch_array(mysqli_query($conn, $q));
                                    echo "<td>".$conditie['choice']."</td>";
                                    
                                    // id -> check if id exist and last id's action is not 0
                                    $q_historiek_verwijderd = mysqli_query($conn, "SELECT id FROM tbl_site_historiek WHERE website='2' AND product_id=".$auto->id." ORDER BY id DESC LIMIT 1");
                                    if(mysqli_num_rows($q_historiek_verwijderd) > 0) // als er een record bestaat
                                    {
                                        $id = mysqli_fetch_object($q_historiek_verwijderd);
                                        $check_action = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_site_historiek WHERE id=".$id->id)); 
                                        if($check_action->actie == '0') // controleer verwijderd
                                        {
                                            $delete = 0;
                                        }else{ // verzonden
                                            $delete = 1;
                                        }
                                    } else{ // geen record
                                        $delete = 0;
                                    }
                                            
                                    if($delete == 0)
                                    {
                                        echo "<td class='no_extra'><input type='checkbox' name='kapaza_verzenden' class='kapaza_verzenden' value='".$auto->id."' checked/> </td><td class='no_extra'></td>";
                                    }else
                                    {
                                        echo "<td class='no_extra'></td><td class='no_extra'><input type='checkbox' name='kapaza_verwijderen' class='kapaza_verwijderen' value='".$auto->id."' checked/> </td>";
                                    }
                                    echo "</tr>";
                                    // extra info
                                    echo "<tr class='extra3_".$auto->id."'><td colspan='6'><table style='margin-left:60px;' cellpadding='0' cellspacing='0' class='tbl3_".$auto->id."'>";

                                    echo "</table></td></tr>";
                                }
                             }
                        ?>
                        </table>
                    <form id="kapaza_tabs" method="post">
                    <input type='hidden' name='tab_id' value='2' /> 
                    </form>
                    <input type="submit" id="btn_kapaza" name="btn_kapaza" value="Verzenden"/><input type="submit" id="btn_kapaza_del" name="btn_kapaza_del" value="Verwijderen"/><img class="img_load" src="images/indicator.gif" alt="loading..."/><span class="txt_ajax_finish"></span>
                </div>
                <div id="tabs-4">
                    <?php 
                        $getFTP = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
                    ?>
                    <form method="post">
                        <a class="autoscouthistoriek" href="auto/historiek/autoscouthistoriek.php">Historiek</a>
                        <h3>FTP gegevens:</h3>
                        <label for="lbl_ftp">Ftp server: </label>
                        <input type="text" name="lbl_ftp" id="lbl_ftp" <?php if(isset($getFTP->autoscout_ftp))echo "value='".$getFTP->autoscout_ftp."'"; ?>/><br />
                        <label for="lbl_ftp_user">User: </label>
                        <input type="text" name="lbl_ftp_user" id="lbl_ftp_user" <?php if(isset($getFTP->autoscout_ftp_user))echo "value='".$getFTP->autoscout_ftp_user."'"; ?>/><br />
                        <label for="lbl_ftp_pw">Paswoord: </label>
                        <input type="text" name="lbl_ftp_pwd" id="lbl_ftp_pw" <?php if(isset($getFTP->autoscout_ftp_pwd))echo "value='".$getFTP->autoscout_ftp_pwd."'"; ?>/>
                        <input type='hidden' name='tab_id' value='3' /> 
                        <input type="submit" name="btn_autoscout" id="btn_autoscout" value="Verzend naar autoscout24.be"/>
                        <br /><br />
                        <hr />
                        <h3>Auto's: </h3>
                        <table>
                            <tr>
                                <th>Merk</th>
                                <th>Model</th>
                                <th>Naam</th>
                                <th>Status</th>
                            </tr>
                        <?php
                            $i=0;
                            $producten = mysqli_query($conn, "SELECT * FROM tbl_products WHERE autoscout24='1'");
                            if(mysqli_num_rows($producten) == 0)
                            {
                                echo "<tr><td colspan='4'>Geen gekozen.</td></tr>";
                            }else
                            {
                            
                                while($auto = mysqli_fetch_object($producten))
                                {
                                    $brandname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$auto->product_brand_id));
                                    $modelname = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$auto->product_model_id));
                                    $i++;
                                    $kleur = $kleur_grijs;
                                    if ($i % 2) {
                                        $kleur = "white";
                                    }
                                    echo "<tr class='product4_".$auto->id."' title='4' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                    echo "<td width='100'>".$brandname->naam."</td>";
                                    echo "<td width='100'>".$modelname->naam."</td>";
                                    echo "<td>".$auto->name."</td>";
                                    $q = "SELECT * FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$auto->id." AND a.product_fields_id=67 AND a.value=b.id";
                                    $conditie = mysqli_fetch_array(mysqli_query($conn, $q));
                                    echo "<td>".$conditie['choice']."</td>";
                                    echo "</tr>";
                                    // extra info
                                    echo "<tr class='extra4_".$auto->id."'><td colspan='4'><table style='margin-left:60px;' cellpadding='0' cellspacing='0' class='tbl4_".$auto->id."'>";

                                    echo "</table></td></tr>";
                                }
                             }
                        ?>
                        </table>
                        <hr />
                        <h3>XML bestand:</h3>
                        <textarea style="font-size:10px;" spellcheck="false" class='txt_xml' border="0" name="xml" readonly><?php $xml = file_get_contents("xml/autoscout24.xml");echo $xml; ?></textarea>
                        
                    </form>
                </div>
            </div>
        </div>
        <center><?php
include "inc/footer.php";
?></center>

    </body>
</html>
