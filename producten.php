<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

/* PRODUCT AANPASSEN */
if (isset($_POST['btn_edit_prod_opslaan']))
{
    $brand = '';
    if(isset($_POST['brand']))
    {
        $brand = $_POST['brand'];
    }
    $model = '';
    if(isset($_POST['model']))
    {
        $model = $_POST['model'];
    }
    $name = '';
    if(isset($_POST['name']))
    {
        $name = $_POST['name'];
    }
    // update table products
    $q_upd_product = "UPDATE tbl_products SET product_brand_id=".$brand.", product_model_id=".$model.", name='".$name."' WHERE id=".$_POST['product_id'];
    mysqli_query($conn, $q_upd_product);
    // update table product_values
    foreach ($_POST as $post => $waarde) {
        // FIELDS -> PRODUCTS VALUES
        if (substr($post, 0, 5) == "veld_") {
            $id = substr($post, 5);
            // delete if empty
            if(empty($waarde))
            {
                // delete if empty record
                mysqli_query($conn, "DELETE FROM tbl_product_values WHERE product_id=".$_POST['product_id']." AND product_fields_id=".$id);
            }else{
                // check if exist
                $q_check_exist = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=".$_POST['product_id']." AND product_fields_id=".$id));
                if($q_check_exist == 0)
                {
                    // insert if doesn't exist
                    mysqli_query($conn, "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (".$_POST['product_id'].",".$id.",'".htmlentities($waarde,ENT_QUOTES)."')");
                }else{
                    // update if exist
                    mysqli_query($conn, "UPDATE tbl_product_values SET value='".htmlentities($waarde,ENT_QUOTES)."' WHERE product_id=".$_POST['product_id']." AND product_fields_id=".$id);
                }
            }
        }
        if (substr($post, 0, 6) == "opties") { // FIELDS -> PRODUCTS VALUES -> checkbox -> multiple checked
            $id = substr($post, 6);
            $getOldOptions = mysqli_query($conn, "SELECT value FROM tbl_product_values WHERE product_id=".$_POST['product_id']." AND product_fields_id=".$id);
            $options = array();
            while ($option = mysqli_fetch_object($getOldOptions))// check old options if it still exist
            {
                $options[]=$option->value;
            }
            $existOption = array_diff($options, $waarde);
            $newOption = array_diff($waarde, $options);
            if(!empty($existOption)) // CHANGED
            {
                foreach($existOption as $old) // delete not used options
                {
                    mysqli_query($conn, "DELETE FROM tbl_product_values WHERE product_id=".$_POST['product_id']." AND product_fields_id=".$id." AND value='".$old."'");
                }
            }
            if(!empty($newOption)) // NEW
            {
                foreach($newOption as $new)
                {
                    mysqli_query($conn, "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (".$_POST['product_id']."," . $id . ",'" . $new . "')");
                }
            }
        }
    }
}
/* PRODUCT TOEVOEGEN */
if (isset($_POST["bewaar"]) && $_POST['bewaar'] == 'Opslaan' && isset($_POST["product_type"]) && $_POST["product_type"] != '') {
    // NAME & BRAND & MODEL & PRODUCT TYPE -> PRODUCTS
    // BRAND & MODEL -> AUTO

    if (isset($_POST['product_name']) && !empty($_POST['product_name']))
    {
        $name = $_POST['product_name'];
    } else {
        $name = '';
    }
    if (isset($_POST['brand']) && isset($_POST['model'])) {

        $q_new_product = "INSERT INTO tbl_products (product_type_id,product_brand_id,product_model_id,name) VALUES (" . $_POST['product_type'] . "," . $_POST['brand'] . "," . $_POST['model'] . ",'" . $name . "')";
        mysqli_query($conn, $q_new_product);

        $lastid = mysqli_insert_id($conn);

    } else {
        // OTHER PRODUCTS
        // NOT AUTO
        $q_new_product = "INSERT INTO tbl_products (product_type_id,product_brand_id,product_model_id,name) VALUES (" . $_POST['product_type'] . ",'','','" . $name . "')";
        mysqli_query($conn, $q_new_product) or die("br />" . mysqli_errno($conn) . " " . __LINE__ . " " . $q_new_product);

        $lastid = mysqli_insert_id($conn);
    }
    // FIELD -> CHOICES -> SELECT
    foreach ($_POST as $post => $waarde) {
        if (substr($post, 0, 5) == "veld_" && !empty($waarde)) { // FIELDS -> PRODUCTS VALUES
            $id = substr($post, 5);
            $q_product_values = "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (" . $lastid . "," . $id . ",'" . htmlentities($waarde,ENT_QUOTES) . "')";
            mysqli_query($conn, $q_product_values) or die(mysqli_errno($conn) . " " . __LINE__ . " " . $q_product_values);
        }        
        if (substr($post, 0, 6) == "opties") { // FIELDS -> PRODUCTS VALUES -> checkbox -> multiple checked
            $id = substr($post, 6);
            foreach($waarde as $v)
            {
                mysqli_query($conn, "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (" . $lastid . "," . $id . ",'" . htmlentities($v,ENT_QUOTES) . "')");
            }
        }
    }
}
/* PRODUCT TYPE TOEVOEGEN */
if (isset($_POST["btn_toevoegen_type"]) && $_POST['btn_toevoegen_type'] == 'Toevoegen' && $_POST['soort_type'] != '') {
    // check if exist
    $q_check_type = mysqli_query($conn, "SELECT * FROM tbl_product_type WHERE type='" . $_POST['soort_type'] . "'");
    if (mysqli_num_rows($q_check_type) == 0) {
        // insert soort
        $q_ins_type = mysqli_query($conn, "INSERT INTO tbl_product_type (type) VALUES ('" . $_POST['soort_type'] . "')");
    }
}

/* PRODUCT TYPE UPDATE */
if (isset($_POST["btn_update_type"]) && $_POST['btn_update_type'] == 'Wijzig' && $_POST['soort_type'] != '') {
    mysqli_query($conn, "UPDATE tbl_product_type SET type='" . $_POST['soort_type'] . "' WHERE id='" . $_POST['type_id'] . "'");
}

/* PRODUCT FIELD UPDATE */
if (isset($_POST["btn_update_field"]) && $_POST['btn_update_field'] == 'Wijzig' && $_POST['field_id'] != '') {
    $required = 0;
    if(isset($_POST['field_required']))
    {
        $required = 1;
    }
    mysqli_query($conn, "UPDATE tbl_product_fields SET field='" . $_POST['field_update'] . "', field_type=".$_POST['veld_type'].", field_required=".$required." WHERE id=" . $_POST['field_id']);
}

/* PRODUCT FIELD CHOICE UPDATE */
if (isset($_POST["btn_update_choice"]) && $_POST['btn_update_choice'] == 'Wijzig' && $_POST['choice_id'] != '') {
    mysqli_query($conn, "UPDATE tbl_product_field_choices SET choice='" . $_POST['choice_update'] . "' WHERE id='" . $_POST['choice_id'] . "'");
}

/* BRAND EDIT */
if (isset($_POST['btn_edit_brand']) && !empty($_POST['brand_edit']))
{
    mysqli_query($conn, "UPDATE tbl_product_brand SET naam='".$_POST['brand_edit']."' WHERE id=".$_POST['brand_id']);
}

/* BRAND DELETE */
if (isset($_POST['btn_brand_delete']) && !empty($_POST['selected_brand']))
{
    mysqli_query($conn, "DELETE FROM tbl_product_brand WHERE id=".$_POST['selected_brand']);
    mysqli_query($conn, "DELETE FROM tbl_product_model WHERE merk_id=".$_POST['selected_brand']);
}

/* BRAND ADD */
if (isset($_POST['btn_new_brand']) && !empty($_POST['new_brand']))
{
    mysqli_query($conn, "INSERT INTO tbl_product_brand (naam) VALUES ('".$_POST['new_brand']."')");
}

/* BRAND DELETE */
if (isset($_POST['btn_brand_delete']) && !empty($_POST['selected_brand']))
{
    mysqli_query($conn, "DELETE FROM tbl_product_brand WHERE id=".$_POST['selected_brand']);
}

/* MODEL EDIT */
if (isset($_POST['btn_edit_model']) && !empty($_POST['model_edit']))
{
    mysqli_query($conn, "UPDATE tbl_product_model SET naam='".$_POST['model_edit']."' WHERE id=".$_POST['model_id']);
}

/* MODEL ADD */
if (isset($_POST['btn_new_model']) && !empty($_POST['new_model']))
{
    mysqli_query($conn, "INSERT INTO tbl_product_model (merk_id,naam) VALUES (".$_POST['brand_model'].",'".$_POST['new_model']."')");
}

/* MODEL DELETE */
if (isset($_POST['btn_model_delete']) && !empty($_POST['selected_model']))
{
    mysqli_query($conn, "DELETE FROM tbl_product_model WHERE id=".$_POST['selected_model']);
}

/* PRODUCT PHOTO UPLOAD */
if (isset($_FILES["photo_doc"]) && !empty($_FILES["photo_doc"]))
{
    chdir('../images/uploads/products');
    
    // make map and go 
    if(isset($_POST['product_id']) && !empty($_POST['product_id']))
    {
        if(!is_dir($_POST['product_id']) || !file_exists($_POST['product_id']))
        {
            mkdir($_POST['product_id'], 0777);
        }        
        
        chdir($_POST['product_id']);
    }else{
        if(!is_dir($lastid) || !file_exists($lastid))
        {
            mkdir($lastid);
        }
        chdir($lastid);
    }
    // upload hoofd foto
    if(isset($_FILES['photo_hoofd']))
    {
        if(move_uploaded_file($_FILES['photo_hoofd']['tmp_name'], $_FILES['photo_hoofd']['name']))
        {
            if(isset($_POST['product_id']))
            {
                mysqli_query($conn, "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (".$_POST['product_id'].",75,'".$_FILES['photo_hoofd']['name']."')");
                mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$_POST['product_id'].",'product','".$_FILES['photo_hoofd']['name']."')");
            }else{
                mysqli_query($conn, "INSERT INTO tbl_product_values (product_id,product_fields_id,value) VALUES (".$lastid.",75,'".$_FILES['photo_hoofd']['name']."')");
                mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$lastid.",'product','".$_FILES['photo_hoofd']['name']."')");
            }
            
            
        }
    }
    // upload andere foto's
    for($i=0; $i<count($_FILES['photo_doc']['name']); $i++) 
    {
      $tmpFilePath = $_FILES['photo_doc']['tmp_name'][$i];
        if ($tmpFilePath != "")
        {
            if(file_exists($_FILES['photo_doc']['name'][$i]))
            {
                $new = 1;
                $newFilePath = time().$_FILES['photo_doc']['name'][$i];
            }else{
                $new = 0;
                $newFilePath = $_FILES['photo_doc']['name'][$i];
            }

            if(move_uploaded_file($tmpFilePath, $newFilePath)) 
            {
                if($new == 1)
                {
                    if(isset($_POST['product_id']) && !empty($_POST['product_id']))
                    {
                        mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$_POST['product_id'].",'product','".time().$_FILES['photo_doc']['name'][$i]."')");
                    }else{
                        mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$lastid.",'product','".time().$_FILES['photo_doc']['name'][$i]."')");
                    }
                }else{
                    if(isset($_POST['product_id']) && !empty($_POST['product_id']))
                    {
                        mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$_POST['product_id'].",'product','".$_FILES['photo_doc']['name'][$i]."')");
                    }else{
                        mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$lastid.",'product','".$_FILES['photo_doc']['name'][$i]."')");
                    }
                }
                
            }
        }
    }
    chdir("../../../../");
    chdir("beheer");
}

/* PRODUCT PHOTO DELETE */
if (isset($_POST["photo_del"]) && !empty($_POST["photo_del"]))
{
    foreach($_POST['photo_del'] as $index => $waarde)
    {
        $get_photo = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id=".$waarde));
        unlink("../images/uploads/products/".$_POST['product_id']."/".$get_photo->cf_file);
        mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id=".$waarde);
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>Producten<?php include "inc/erp_titel.php" ?></title>

        <script type="text/javascript" src="js/functions.js"></script>


        <link href="css/style.css" rel="stylesheet" type="text/css" />
        <link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />

        <link href="css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
        <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>

        <link rel="stylesheet" type="text/css" media="print" href="css/print.css" />

        <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
        <script type="text/javascript" src="js/jquery.validate.js"></script>
        <script type="text/javascript" src="js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="js/jquery.ui.widget.js"></script>
        <script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
        <script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
        <script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
        <style type='text/css'>
            .sort_down{
                    position:relative;
                    float:left;
                    background: url('images/sprite-chrome.png')-144px -119px no-repeat;
                    height:20px;
                    width:25px;
            }
            .sort_down:hover{
                    background: url('images/sprite-chrome.png')-144px -117px no-repeat;
            }
            .sort_up{
                    position:relative;
                    float:left;
                    background: url('images/sprite-chrome.png')-143px -142px no-repeat;
                    height:20px;
                    width:25px;
            }
            .sort_up:hover{
                    background: url('images/sprite-chrome.png')-143px -140px no-repeat;
            }
            .product_name{
                -o-text-overflow: ellipsis;   /* Opera */
                text-overflow:    ellipsis;   /* IE, Safari (WebKit) */
                overflow:hidden;              /* don't show excess chars */
                white-space:nowrap;           /* force single line */
                width: 50px;        
            }
            .autovlan, .tweedehands, .kapaza, .autoscout24{
                margin-left:40%;
            }
        </style>
        <script type='text/javascript'>
            $(document).ready(function() {
                /******** OVERZICHT **********/
                            $('.filter_status').live('change',function(){
                                var status = $(this).val();
                                $('.status').each(function(){
                                    /* geen filter */
                                    if(status == ''){
                                        $(this).parent().show();
                                    }else{
                                        /* filter op status */
                                        if($(this).html() != status){
                                            $(this).parent().hide();
                                        }else{
                                            $(this).parent().show();
                                        }
                                    }
                                });
                            });
                            // DELETE PRODUCT
                            $('.del_prod').live("click",function(){
                                if (confirm("Verwijderen?")) {
                                    var id = $(this).attr('alt');
                                    $.post("auto/autovlan.php", {id:id,action:'delete'},function(){
                                        $.post("ajax/producten_ajax.php", {product_id: id,action: 'del_prod'}, function() {
                                        $('.extra_' + id).remove();
                                        $('.product_' + id).remove();
                                        });
                                    });
                                }
                                return false;
                            });
                            // EDIT PRODUCT
                           $('.edit_prod').live("click",function(){
                               var id = $(this).attr('alt');
                               $.post("ajax/producten_ajax.php", {product_id: id,action: 'edit_prod'}, function(data) {
                                        // show new tab
                                        $('.prod_aanpassen').show();
                                        $("#tabs").tabs({selected:5});
                                        $('#tabs-6').html(data);
                                    });

                               return false;
                           });
                            // HIDE EXTRA DETAIL
                            $('.prod_aanpassen').hide();
                            $('tr[class^=extra_]').hide();
                            // TOGGLE EXTRA DETAIL
                            $("tr[class^='product_'] td:not(.no_extra)").live("click",function(){
                                var waarde = $(this).parent().attr('class');
                                var id = waarde.substr(8);
                               $('.extra_' + id).toggle();
                               $.post("ajax/producten_ajax.php", {product_id: id,action: 'getList'}, function(data) {
                                    $('#tbl_' + id).html(data);
                                });
                            });
                            // SORT Product
                            $('.sortProd').live("click",function(){
                                var sortDirection = $(this).attr('alt');
                                $.post("ajax/producten_ajax.php", {sort: "type", direction:sortDirection, action: 'sortList'}, function(data) {
                                    $('.sortProd').attr('alt',data.substr(0,1));
                                    $('#tbl_overview_product tbody').html(data.substr(1));
                                });
                                return false;
                            });
                            // SORT Model
                            $('.sortModel').live("click",function(){
                                var sortDirection = $(this).attr('alt');
                                $.post("ajax/producten_ajax.php", {sort: "model", direction:sortDirection, action: 'sortList'}, function(data) {
                                    $('.sortModel').attr('alt',data.substr(0,1));
                                    $('#tbl_overview_product tbody').html(data.substr(1));
                                });
                                return false;
                            });
                            // SORT Brand
                            $('.sortBrand').live("click",function(){
                                var sortDirection = $(this).attr('alt');
                                $.post("ajax/producten_ajax.php", {sort: "brand", direction:sortDirection, action: 'sortList'}, function(data) {
                                    $('.sortBrand').attr('alt',data.substr(0,1));
                                    $('#tbl_overview_product tbody').html(data.substr(1));
                                });
                                return false;
                            });
                            // SORT Name
                            $('.sortName').live("click",function(){
                                var sortDirection = $(this).attr('alt');
                                $.post("ajax/producten_ajax.php", {sort: "name", direction:sortDirection, action: 'sortList'}, function(data) {
                                    $('.sortName').attr('alt',data.substr(0,1));
                                    $('#tbl_overview_product tbody').html(data.substr(1));
                                });
                                return false;
                            });
                            // SORT Name
                            $('.sortStatus').live("click",function(){
                                var sortDirection = $(this).attr('alt');
                                $.post("ajax/producten_ajax.php", {sort: "status", direction:sortDirection, action: 'sortList'}, function(data) {
                                    $('.sortStatus').attr('alt',data.substr(0,1));
                                    $('#tbl_overview_product tbody').html(data.substr(1));
                                });
                                return false;
                            });
                            // send autovlan
                            $('.autovlan:checkbox').live('change', function(){
                                var id = $(this).parent().parent().attr('class').substr(8);
                                if($(this).is(':checked')){
                                    $.post("auto/autovlan.php", {id:id,action: 'add'}, function() {
                                    });
                                } else {
                                    $.post("auto/autovlan.php", {id:id,action: 'delete'}, function() {
                                    });
                                }
                            });
                            // send tweedehands
                            $('.tweedehands:checkbox').live('change', function(){
                                var id = $(this).parent().parent().attr('class').substr(8);
                                if($(this).is(':checked')){
                                    $.post("auto/2dehands.php", {id:id,action: 'add'}, function() {
                                    });
                                } else {
                                    $.post("auto/2dehands.php", {id:id,action: 'delete'}, function() {
                                    });
                                }
                            });
                            // send autoscout24
                            $('.autoscout24:checkbox').live('change', function(){
                                var id = $(this).parent().parent().attr('class').substr(8);
                                if($(this).is(':checked')){
                                    $.post("auto/autoscout24.php", {id:id,action: 'add'}, function() {
                                    });
                                } else {
                                    $.post("auto/autoscout24.php", {id:id,action: 'delete'}, function() {
                                    });
                                }
                            });
                            // send kapaza
                            $('.kapaza:checkbox').live('change', function(e){
                                e.stopPropagation();
                                var id = $(this).parent().parent().attr('class').substr(8);
                                if($(this).is(':checked')){
                                    $.post("auto/kapaza.php", {id:id,action: 'add'}, function() {
                                    });
                                } else {
                                    $.post("auto/kapaza.php", {id:id,action: 'delete'}, function() {
                                    });
                                }
                            });
                /***** NEW | SELECT TYPE  *****/
                            $('#select_product_type_id').change(function() {
                                var type_id = $(this).val();
                                if (type_id != '')
                                {
                                    $.post("ajax/producten_ajax.php", {type_id: type_id, action: 'new_fields'}, function(data) {
                                        $('#new_product_fields').html(data);
                                    });
                                } else {
                                    $('#new_product_fields').html('');
                                }
                                return false;
                            });
                            $("#brand").live("change", function() {
                                var brand_id = $(this).val();
                                $.post("ajax/producten_ajax.php", {brand_id: brand_id}, function(data) {
                                    $('#model').html(data);
                                });
                                return false;
                            });
                            $('#frm_new_product').validate();
                            /***** OVERZICHT VELDEN | SHOW FIELD AFTER SELECTED FIELD *****/
                            $('#frm_choice_add').hide();
                            $('#btn_toevoegen_type').click(function(){
                                if(!$('#soort_type').val())
                                {
                                    $('#soort_type').css('border-color','red');
                                    return false;
                                }else{
                                    $('#soort_type').css('border-color','');
                                }
                            });

                /****** TYPE PRODUCTEN | Verwijder type *****/
                            $('.del_type').live("click",function()
                            {
                                if (confirm("Verwijderen?")) {
                                    // sla waarde id op
                                    var id = $(this).attr('alt');
                                    // verwijder rij
                                    $("#overzicht_type_" + id).remove();
                                    // verzend de waarde naar ajax
                                    $.post("ajax/producten_ajax.php", {type_id: id, action: 'delete'},function(){
                                        $('.response').text('Type is verwijderd.');
                                        $('.response').css('color','green');
                                        $('.response').show();
                                        $('.response').fadeOut(5000);
                                    });
                                }
                                return false;
                            });
                            $('.response').fadeOut(5000);
                            /****** Wijzig type *****/
                            $('.edit_type').live("click",function()
                            {
                                // sla waarde id op
                                var id = $(this).attr('alt');
                                // verzend de waarde naar ajax
                                $.post("ajax/producten_ajax.php", {type_id: id, action: 'edit'}, function(data) {
                                    $('#tabs-3').html(data);
                                });
                                return false;
                            });
                /****** FIELD ******/
                            /****** Verwijder veld *****/
                            $('.del_veld').live("click",function()
                            {
                                if (confirm("Verwijderen?")) {
                                    // sla waarde id op
                                    var id = $(this).attr('alt');
                                    // verwijder rij
                                    $(this).parent('td').parent('tr').remove();
                                    // verzend de waarde naar ajax
                                    $.post("ajax/producten_ajax.php", {field_id: id, action: 'delete'},function(){
                                        $('.response_field').text('Veld is verwijderd.');
                                        $('.response_field').css('color','green');
                                        $('.response_field').show();
                                        $('.response_field').fadeOut(5000);
                                        $('#select_field_choice option[value='+id+']').remove();
                                    });
                                }
                                return false;
                            });

                            /****** EDIT FIELD *****/
                            $('.edit_veld').live("click",function()
                            {
                                // sla waarde id op
                                var id = $(this).attr('alt');
                                // verzend de waarde naar ajax
                                $.post("ajax/producten_ajax.php", {field_id: id, action: 'edit'}, function(data) {
                                    $('#tabs-4').html(data);
                                });
                                return false;
                            });
                            /***** BLOCK ENTER ******/
                            $('#nieuw_veld').keypress(function(e) {
                                if (e.which == 13) {
                                    e.preventDefault();
                                    jQuery(this).blur();
                                    jQuery('#submit').focus().click();
                                }
                            });
                            /***** SORT FIELD *****/
                            $('.sort_down').live('click',function(){
                                var huidig_id = $(this).parent('td').attr('title');
                                var type = $(this).attr('title');
                                // verzend de waarde naar ajax
                                $.post("ajax/producten_ajax.php", {field_id: huidig_id, type:type, action: 'sortdown'}, function(data) {
                                    $('#tbl_field_list').html(data);
                                });
                            });
                            $('.sort_down').eq(0).hide();
                            $('.sort_up').last().css('height','1px');
                            $('.sort_up').live('click',function(){
                                var huidig_id = $(this).parent('td').attr('title');
                                var type = $(this).attr('title');
                                // verzend de waarde naar ajax
                                $.post("ajax/producten_ajax.php", {field_id: huidig_id, type:type, action: 'sortup'}, function(data) {
                                    $('#tbl_field_list').html(data);
                                });
                            });
                            /***** NEW VELD VIA AJAX *****/
                            $('#btn_nieuw_veld').live("click",function() {
                                // sla waarde op
                                var required = 0;
                                if($('#required_veld').is(':checked') == true)
                                {
                                    required = 1;
                                }
                                var waarde = $('#nieuw_veld').val();
                                var id = $('#product_id').val();
                                var type = $('#gekozen_type').val();
                                var field_type = $('#veld_type').val();
                                if (waarde != '' && field_type != '') {
                                    $('#nieuw_veld').css("border", "");
                                    $('#veld_type').css("border", "");
                                    // verzend dit via ajax
                                    var post_nieuw_veld = $.post("ajax/producten_ajax.php", {field_id: id, waarde: waarde,field_type: field_type,field_required: required, action: 'add', type: type});
                                    post_nieuw_veld.done(function(data) {
                                        if(data.substr(0,5) == 'exist')
                                        {
                                            $('.response_field').text('Dat bestaat al.');
                                            $('.response_field').css('color','red');
                                            $('.response_field').show();
                                            $('.response_field').fadeOut(5000);
                                        }else{
                                            $('#tbl_field_list').html(data);
                                            $('.response_field').text('Nieuw veld is toegevoegd.');
                                            $('.response_field').css('color','green');
                                            $('.response_field').show();
                                            $('.response_field').fadeOut(5000);
                                            var option_id = $('.del_veld').last().attr('alt');
                                            var option = $('.del_veld').last().parent('td').next('td').text();
                                            $('#select_field_choice').append('<option value='+option_id+'>'+option+'</option>');
                                        }
                                        
                                    });
                                } else{
                                    if(!$('#nieuw_veld').val())
                                    {
                                        $('#nieuw_veld').css("border-color", "red");
                                    }else{
                                        $('#nieuw_veld').css("border-color", "");
                                    }
                                    if(!$('#veld_type').val())
                                    {
                                        $('#veld_type').css("border-color", "red");
                                    }else{
                                        $('#veld_type').css("border-color", "");
                                    }
                                }
                                return false;
                            });

                            /***** GET LIST AFTER SELECTED FIELD *****/
                            $('#select_field_choice').change(function() {
                                var id = $(this).val();
                                if (id == '')
                                {
                                    $('#frm_choice_add').hide();
                                    $('#lbl_choices').hide();
                                } else {
                                    $('#lbl_choices').show();
                                    $.post("ajax/producten_ajax.php", {field_choice_id: id, action: 'get'}, function(data) {
                                        $('#lbl_choices').html(data);
                                        $('#frm_choice_add').show();
                                    });
                                }
                            });

                            /***** ADD CHOICE *****/
                            $('#btn_new_choice').live("click",function(event) {
                                var waarde = $('#new_choice').val();
                                var product_fields_id = $('#select_field_choice').val();
                                if (waarde != '')
                                {
                                    $('#new_choice').css('border-color','');
                                    $.post("ajax/producten_ajax.php", {product_fields_id: product_fields_id, choice: waarde, action: 'new'}, function(data) {
                                        // refresh list
                                        if(data.substr(0,5) != 'exist')
                                        {
                                            $('#lbl_choices').html(data);
                                            $('.response_field').text('Nieuw keuze is toegevoegd.');
                                            $('.response_field').css('color','green');
                                            $('.response_field').show();
                                            $('.response_field').fadeOut(5000);
                                        }else{
                                            $('#lbl_choices').html(data.substr(5));
                                            $('.response_field').text('Keuze bestaat al.');
                                            $('.response_field').css('color','red');
                                            $('.response_field').show();
                                            $('.response_field').fadeOut(5000);
                                        }
                                    });
                                }else{
                                    $('#new_choice').css('border-color','red');
                                }
                                
                                event.preventDefault();
                                return false;
                            });

                            /***** DELETE CHOICE *****/
                            $(".del_choice").live("click", function() {
                                if (confirm("Verwijderen?")) {
                                    // sla waarde op
                                    var choice_id = $(this).attr('alt');
                                    var product_fields_id = $('#select_field_choice').val();
                                    // verzend naar ajax
                                    $.post("ajax/producten_ajax.php", {choice_id: choice_id, product_fields_id: product_fields_id, action: 'delete'}, function(data) {
                                        // refresh list
                                        $('#lbl_choices').html(data);
                                        $('.response_field').text('Keuze is verwijderd.');
                                        $('.response_field').css('color','green');
                                        $('.response_field').show();
                                        $('.response_field').fadeOut(5000);
                                    });
                                }
                                return false;
                            });

                            /***** EDIT CHOICE *****/
                            $(".edit_choice").live("click", function() {
                                var choice_id = $(this).attr('alt');
                                // verzend de waarde naar ajax
                                $.post("ajax/producten_ajax.php", {choice_id: choice_id, action: 'edit'}, function(data) {
                                    $('#tabs-4').html(data);
                                });
                                return false;
                            });
                     /***** BRAND/MODEL *****/
                            // selectie brand
                            $('#selected_brand').change(function(){
                                   $('#frm_brand_select').submit();
                            });
                            // controle toevoegen van brand
                            $('#btn_new_brand').click(function(){
                                   if(!$('#new_brand').val())
                                   {
                                       $('#new_brand').css('border-color','red');
                                       return false;
                                   }else{
                                       $('#new_brand').css('border-color','');
                                   }

                            });
                            // controle toevoegen van model
                            $('#btn_new_model').live("click",function(){
                                   if(!$('#new_model').val() || !$('#brand_model').val() )
                                   {
                                       if(!$('#new_model').val())
                                       {
                                           $('#new_model').css('border-color','red');
                                       }else{
                                           $('#new_model').css('border-color','');
                                       }
                                       if(!$('#brand_model').val())
                                       {
                                           $('#brand_model').css('border-color','red');
                                       }else{
                                           $('#brand_model').css('border-color','');
                                       }
                                       return false;
                                   }else{
                                       $('#brand_model').css('border-color','');
                                       $('#new_model').css('border-color','');
                                   }

                            });
                            $('.brandmodel_response').fadeOut(5000);
            });
            
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
                            that.value = that.value.replace(/\,/g,"");
                    }
            }
            $(function() {
                $("#tabs").tabs({selected: <?php if (isset($_REQUEST["tab_id"])) {
    echo $_REQUEST["tab_id"];
} else {
    echo 0;
}; ?>});
            });

            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-24625187-1']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script');
                ga.type = 'text/javascript';
                ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </head>
    <body>

        <div id='pagewrapper'>
<?php include('inc/header.php'); ?>
            <h1>Producten</h1>

            <div id="tabs">
                <ul>
                    <li><a href="#tabs-1">Overzicht</a></li>
                    <li><a href="#tabs-2">Nieuw</a></li>
                    <li><a href="#tabs-3">Type producten</a></li>
                    <li><a href="#tabs-4">Overzicht velden</a></li>
                    <li><a href="#tabs-5">Merk/model</a></li>
                    <li class="prod_aanpassen"><a href="#tabs-6">Product aanpassen</a></li>
                </ul>

                <div id="tabs-1">
                    Filter op status
                    <select class="filter_status">
                        <option value=''>Alles</option>
                        <option value="Aangekocht">Aangekocht</option>
                        <option value="Te koop">Te koop</option>
                        <option value="Verkocht">Verkocht</option>
                    </select>
                    <hr />
                    <table id="tbl_overview_product" width='950' cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width:50px;"></th>
                                <th>
                                    <a href class="sortProd" alt="0">
                                    <b>Product</b>
                                    </a>
                                </th>
                                <th>
                                    <a href="" class="sortBrand" alt="0">
                                    <b>Merk</b>
                                    </a>
                                </th>
                                <th>
                                    <a href="" class="sortModel" alt="0">
                                    <b>Model</b>
                                    </a>
                                </th>
                                
                                <th>
                                    <a href="" class="sortName" alt="0">
                                    <b>Naam</b>
                                    </a>
                                </th>
                                <th>
                                     <a href="" class="sortStatus" alt="0">
                                    <b>Status</b>
                                    </a>
                                </th>
                                <th>
                                    <b>Autovlan</b>
                                </th>
                                <th>
                                    <b>2dehands</b>
                                </th>
                                <th>
                                    <b>kapaza</b>
                                </th>
                                <th>
                                    <b>autoscout24</b>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_get_products = mysqli_query($conn, "SELECT * FROM tbl_products ORDER BY id DESC");
                            $i=0;
                            while($product = mysqli_fetch_object($q_get_products))
                            {
                                $i++;
                                $kleur = $kleur_grijs;
                                if ($i % 2) {
                                    $kleur = "white";
                                }
                                echo "<tr class='product_".$product->id."' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'><td>";
                                echo "<a href='' class='edit_prod' alt=".$product->id."><img src='images/edit.png'/></a>";
                                echo "<a href='' class='del_prod' alt=".$product->id."><img src='images/delete.png'/></a></td><td>";
                                $productname = mysqli_fetch_array(mysqli_query($conn, "SELECT type FROM tbl_product_type WHERE id=".$product->product_type_id));
                                echo $productname[0]."</td>";
                                $brandname = mysqli_fetch_array(mysqli_query($conn, "SELECT naam FROM tbl_product_brand WHERE id=".$product->product_brand_id));
                                echo "<td>".$brandname[0]."</td>";
                                $modelname = mysqli_fetch_array(mysqli_query($conn, "SELECT naam FROM tbl_product_model WHERE id=".$product->product_model_id));
                                echo "<td>".$modelname[0]."</td>";
                                echo "<td class='product_name' title='".$product->name."'>".truncate($product->name,25)."</td>";
                                $q = "SELECT * FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$product->id." AND a.product_fields_id=67 AND a.value=b.id";
                                $conditie = mysqli_fetch_array(mysqli_query($conn, $q));
                                echo "<td class='status'>".$conditie['choice']."</td>";
                                if($product->autovlan == '1')
                                {
                                    $checked = 'checked';
                                }else{$checked = '';}
                                echo "<td class='no_extra'><input type='checkbox' class='autovlan' name='chkbox_autovlan' ".$checked." /></td>";
                                if($product->tweedehands == '1')
                                {
                                    $checked = 'checked';
                                }else{$checked = '';}
                                echo "<td class='no_extra'><input type='checkbox' class='tweedehands' name='chkbox_2dehands' ".$checked." /></td>";
                                if($product->kapaza == '1')
                                {
                                    $checked = 'checked';
                                }else{$checked = '';}
                                echo "<td class='no_extra'><input type='checkbox' class='kapaza' name='chkbox_kapaza' ".$checked." /></td>";
                                if($product->autoscout24 == '1')
                                {
                                    $checked = 'checked';
                                }else{$checked = '';}
                                echo "<td class='no_extra'><input type='checkbox' class='autoscout24' name='chkbox_autoscout24' ".$checked."/></td>";
                                echo "</tr>";
                                // extra info
                                echo "<tr class='extra_".$product->id."'><td colspan='9'><table width='600' style='margin-left:60px;' cellpadding='0' cellspacing='0' id='tbl_".$product->id."'>";

                                echo "</table></td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="tabs-2">

                    <b>Nieuwe product toevoegen.</b><br/><br/>


                    <form method='post' id='frm_new_product' name='frm_new_product' enctype='multipart/form-data'>
                        <table>
                            <tr>
                                <td style="width:150px;">Product soort :</td>
                                <td>
                                    <select id="select_product_type_id" name="product_type">
                                        <option value=""> == KEUZE == </option>
                                        <?php
                                        $q_type = mysqli_query($conn, "SELECT * FROM tbl_product_type");
                                        $q_aant_type = mysqli_num_rows($q_type);
                                        while ($soort = mysqli_fetch_object($q_type)) {
                                            echo "<option value=" . $soort->id . ">" . $soort->type . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <table id="new_product_fields"></table>
                       <input type='hidden' name='tab_id' id='tab_id' value='0' />
                    </form>
                </div>
                <div id="tabs-3">
                    <!-- soorten toevoegen -->
                    <b>Nieuw soort toevoegen:</b>
                    <form id="frm_soort_nieuw" method="post" name="frm_soort_nieuw" style="margin-top:10px;margin-left:5px;margin-bottom:20px;">
                        <label>Soortnaam: </label>
                        <input type="text" name="soort_type" id="soort_type"/>
                        <input type="submit" name="btn_toevoegen_type" id="btn_toevoegen_type" value="Toevoegen" />
                        <input type='hidden' name='tab_id' id='tab_id' value='2' />
                    </form>
                    <hr>
                        <!-- overzicht soorten indien niet gebruikt delete -->
                        <span style="margin-top:10px;"><b>Overzicht soorten:</b></span><br /><br />
                        <table width='500' cellpadding="0" cellspacing="0">
                        <?php
                        $q_soort_overzicht = mysqli_query($conn, "SELECT * FROM tbl_product_type");
                        $i = 0;
                        while ($soort = mysqli_fetch_object($q_soort_overzicht)) {
                            $i++;
                            $kleur = $kleur_grijs;
                            if ($i % 2) {
                                $kleur = "white";
                            }
                            echo "<tr id='overzicht_type_" . $soort->id . "' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                            echo "<td style='width:50px;'><a class='edit_type' alt='" . $soort->id . "' href=''><img src='images/edit.png'/></a>";
                            // als type niet gebruikt is dan moet de delete knop zichtbaar worden.
                            $q_type_used = mysqli_query($conn, "SELECT * FROM tbl_products WHERE product_type_id='" . $soort->id . "'");
                            if (mysqli_num_rows($q_type_used) < 1) {
                                echo "<a class='del_type' alt='" . $soort->id . "' href=''><img src='images/delete.png'/></a></td>";
                            } else {
                                echo "</td>";
                            }
                            echo "<td>" . $soort->type . "</td>";
                        }
                        echo "</table>";
                        if(isset($_POST['btn_toevoegen_type']))
                        {
                            echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='response'>Type is toegevoegd.</span>";
                        }else{
                            echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='response'></span>";
                        }
                        ?>
                </div>
                <!-- DIV NIEUW VELDTYPE -->
                <div id="tabs-4">
                    <!-- PRODUCT TYPE KIEZEN -->
                    <form method="post" name="frm_veld_type" id='frm_veld_type'>
                        <table>
                            <tr>
                                <td colspan="2">
                                    <b>Velden koppelen aan een product</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Kies product type:</td>
                                <td>
                                    <select name="gekozen_type" id="gekozen_type" onchange="this.form.submit()">
                                        <option value=""> == KEUZE == </option>
                                        <?php
                                        $q_select_product_type = mysqli_query($conn, "SELECT * FROM tbl_product_type");
                                        while ($type = mysqli_fetch_object($q_select_product_type)) {
                                            if (isset($_POST['gekozen_type']) && $_POST['gekozen_type'] == $type->id) {
                                                echo "<option value='" . $type->id . "' selected='selected'>" . $type->type . "</option>";
                                            } else {
                                                echo "<option value='" . $type->id . "'>" . $type->type . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type='hidden' name='tab_id' id='tab_id' value='3' />
                                </td>
                            </tr>
                        </table>
                    </form>
                    <!-- NA PRODUCT KIEZEN, KOPPELEN VAN VELDEN AAN PRODUCTEN -->
                    <?php
                    if (isset($_POST['gekozen_type']) && $_POST['gekozen_type'] != '') {
                        ?>
                        <!-- huidige velden -->
                        <form method="post" name="frm_veld_namen">
                            <table id="tbl_veld_overzicht" width='500' cellpadding="0" cellspacing="0">
                                <tr>
                                    <td colspan='2' style="margin-bottom:5px;">
                                        <u>Huidige velden:</u>
                                    </td>
                                </tr>
                            </table>
                            <table width='800' cellpadding="0" cellspacing="0" id="tbl_field_list">
                            <?php
                            $q_get_velden = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['gekozen_type'] . "' ORDER BY field_sort ASC");
                            $i = 0;
                            echo "<tr><td></td><td><b>Veldnaam</b></td><td><b>Veldtype</b></td><td><b>Verplicht</b></td><td><b>Sorteren</b></td></tr>";
                            while ($veld = mysqli_fetch_object($q_get_velden)) {
                                $i++;
                                $kleur = $kleur_grijs;
                                if ($i % 2) {
                                    $kleur = "white";
                                }
                                
                                echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
                                echo "<td style='width:50px;'><a class='edit_veld' alt='" . $veld->id . "' href=''><img src='images/edit.png'/></a>";
                                // als veld niet gebruikt is dan moet de delete knop zichtbaar worden.
                                $q_type_used = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id='" . $veld->id . "'");
                                if (mysqli_num_rows($q_type_used) < 1) {
                                    echo "<a class='del_veld' alt='" . $veld->id . "' href=''><img src='images/delete.png'/></a></td>";
                                } else {
                                    echo "</td>";
                                }
                                echo "<td>" . $veld->field . "</td>";
                                if($veld->field_type != 0)
                                {
                                    echo "<td>".$field_type_arr[$veld->field_type] . "</td>";
                                }else{
                                    echo "<td></td>";
                                }
                                if($veld->field_required == 1)
                                {
                                    echo "<td>Ja</td>";
                                }else{
                                    echo "<td>Nee</td>";
                                }
                                echo "<td title=".$veld->id."><span class='sort_up' title=".$_POST['gekozen_type']."></span><span class='sort_down' title=".$_POST['gekozen_type']."></span></td>";
                                echo "</tr>";
                            }
                            ?>
                            </table>
                        </form><br />
                        <!-- nieuw veld -->
                        <form method='post' name='frm_veld_toevoegen'>
                            <table>
                                    <tr>
                                        <td><b>Nieuw veld</b></td>
                                </tr>
                                <tr>
                                    <td>
                                        Veldnaam:
                                    </td>
                                
                                    <td>
                                        <input type='text' name='nieuw_veld' id="nieuw_veld"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label>Kies een veldtype: </label>
                                    </td>
                                    <td>
                                        <select  name="veld_type" id='veld_type'>
                                            <option value=''> == KEUZE == </option>
                                            
                                            <?php
                                            
                                            foreach( $field_type_arr as $index => $waarde )
                                            {
                                                echo "<option value='".$index."'>".$waarde."</option>";
                                            }
                                            
                                            ?>
                                            
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label>Verplicht: </label>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="required_veld" id="required_veld" value="1"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type='submit' name='btn_nieuw_veld' id="btn_nieuw_veld" value="Toevoegen"/>
                                        <input type='hidden' name='tab_id' id='tab_id' value='3' />
                                        <input type='hidden' name='product_id' id='product_id' value='<?php echo $_POST['gekozen_type']; ?>' />
                                    </td>
                                </tr>
                                </tr>
                            </table>
                        </form>
                        <hr>
                            <form method="post" name="frm_field_choice">
                                <table id="tbl_field_choice" width='500' cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td colspan='2'>
                                            <b>Keuze defini&euml;ren aan een veld.</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="120">Kies een veld: </td>
                                        <td>
                                            <select id="select_field_choice">
                                                <option value=""> == KEUZE == </option>
                                                <?php
                                                $q_select_product_field = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['gekozen_type'] . "' ORDER BY field_sort ASC");
                                                while ($type = mysqli_fetch_object($q_select_product_field)) {
                                                    echo "<option value='" . $type->id . "'>" . $type->field . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                                <br /><u>Huidige keuzes:</u><br />
                                <table id="lbl_choices" width='500' cellpadding="0" cellspacing="0"></table>
                            </form>
                            <!-- new choice -->
                            <form method='post' name='frm_choice_add' id="frm_choice_add">
                                <table width='500' cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td colspan="2">
                                            Nieuw keuze:
                                        </td>
                                        <td>
                                            <input type='text' name='new_choice' id="new_choice"/>
                                            <input type='submit' name='btn_new_choice' id="btn_new_choice" value="Toevoegen"/>
                                            <input type='hidden' name='tab_id' id='tab_id' value='3' />
                                        </td>
                                    </tr>
                                </table>
                            </form>
<?php } ?>
                            <span style="position:absolute;top:50px;right:10px;color:green;" class="response_field"></span>
                        <!-- EINDE DIV NIEUW VELDTYPE -->
                </div>
                <div id="tabs-5">
                    <?php
                    if (!isset($_POST['btn_brand_edit']) && !isset($_POST['btn_model_edit'])) {
                        // Brand kiezen
                        ?>
                        <span style='font-weight:bold;'>Overzicht merk</span><br /><br />
                        <form method="post" name="frm_brand_select" id="frm_brand_select">
                            <label>Kies een merk: </label><select name="selected_brand" id="selected_brand">
                                <option value=''> == KEUZE == </option>
                                <?php
                                $get_brands = mysqli_query($conn, "SELECT * FROM tbl_product_brand");
                                $brandname = '';
                                while ($brand = mysqli_fetch_object($get_brands)) {
                                    if (isset($_POST['selected_brand']) && $_POST['selected_brand'] == $brand->id) {
                                        echo "<option value='" . $brand->id . "' selected>" . $brand->naam . "</option>";
                                        $brandname = $brand->naam;
                                    } else {
                                        echo "<option value='" . $brand->id . "' >" . $brand->naam . "</option>";
                                    }
                                }
                                ?>
                                <input type='hidden' name='tab_id' id='tab_id' value='4' />

                            </select>
                                <?php
                                // NA EEN GEKOZEN BRAND, TOON AANPASSEN EN VERWIJDEREN
                                if (isset($_POST['selected_brand']) && !empty($_POST['selected_brand']) && !isset($_POST['btn_brand_delete'])) {
                                    echo "<br /><br /><label>U heeft gekozen voor het merk: </label><span style='font-weight:bold'>" . $brandname . "</span>";
                                    echo "<input style='margin-left:10px;' type='submit' name='btn_brand_edit' id='btn_brand_edit' value='Aanpassen' />";
                                    echo "<input style='margin-left:10px;' type='submit' name='btn_brand_delete' id='btn_brand_delete' value='Verwijderen' />";
                                }
                                ?>
                        </form>
                            <?php
                            if (isset($_POST['selected_brand']) && !empty($_POST['selected_brand']) && !isset($_POST['btn_brand_delete'])) {
                                // NA EEN GEKOZEN BRAND, TOON MODEL
                                echo "<form method='post' name='frm_select_model' id='frm_select_model'>";
                                echo "<br /><span style='font-weight:bold;'>Overzicht model</span><br /><br />";
                                echo "<label>Kies een model: </label>";
                                echo "<select name='selected_model' id='selected_model'>";
                                $get_models = mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE merk_id=" . $_POST['selected_brand']);
                                while ($model = mysqli_fetch_object($get_models)) {
                                    echo "<option value=" . $model->id . " >" . $model->naam . "</option>";
                                }
                                echo "</select>";
                                echo "<input style='margin-left:10px;' type='submit' name='btn_model_edit' id='btn_model_edit' value='Aanpassen' />";
                                echo "<input style='margin-left:10px;' type='submit' name='btn_model_delete' id='btn_model_delete' value='Verwijderen' />";
                                echo "<input type='hidden' name='tab_id' id='tab_id' value='4'/>";
                                echo "</form>";
                            }
                        } else if (!isset($_POST['btn_model_edit'])) {
                            // Aanpassen van een brand
                            ?>
                        <span style='font-weight:bold;'>Aanpassen van een merk</span><br /><br />
                        <form method="post" name="frm_brand_edit" id="frm_brand_edit">
                        <?php
                        $get_brand = mysqli_fetch_object(mysqli_query($conn, "SELECT * from tbl_product_brand WHERE id=" . $_POST['selected_brand']));
                        echo "<input type='text' name='brand_edit' id='brand_edit' value=" . $get_brand->naam . " />";
                        echo "<input type='submit' name='btn_edit_brand' id='btn_edit_brand' value='Opslaan' />";
                        echo "<input type='submit' value='Annuleren' />";
                        echo "<input type='hidden' name='brand_id' id='brand_id' value='" . $get_brand->id . "' />";
                        ?>
                            <input type='hidden' name='tab_id' id='tab_id' value='4' />
                        </form>
                            <?php
                        }
                        if (isset($_POST['btn_model_edit']) && !isset($_POST['btn_brand_delete'])) {
                            echo "<span style='font-weight:bold;'>Aanpassen van een model</span>";
                            echo "<form method='post' name='frm_model_edit' id='frm_model_edit'>";
                            $get_model = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=" . $_POST['selected_model']));
                            echo "<input type='text' name='model_edit' id='model_edit' value=" . $get_model->naam . " />";
                            echo "<input type='submit' name='btn_edit_model' id='btn_edit_model' value='Opslaan' />";
                            echo "<input type='submit' value='Annuleren' />";
                            echo "<input type='hidden' name='model_id' id='model_id' value='" . $get_model->id . "' />";
                            echo "<input type='hidden' name='tab_id' id='tab_id' value='4'/>";
                            echo "</form>";
                        }
                        ?>
                    <br /><hr /><br />
                    <form method="post">
                        <span style='font-weight:bold;'>Toevoegen van een merk:</span><br /><br />
                        <label>Nieuw merk: </label><input type="text" name="new_brand" id="new_brand"/>
                        <input type="submit" name="btn_new_brand" id="btn_new_brand" value="Toevoegen"/>
                        <input type='hidden' name='tab_id' id='tab_id' value='4' />
                    </form>
                    <form method="post">
                        <br /><span style='font-weight:bold;'>Toevoegen van een model:</span><br /><br />
                        <label>Kies een merk: </label><select name="brand_model" id="brand_model">
                    <?php
                    $get_brands = mysqli_query($conn, "SELECT * FROM tbl_product_brand");
                    echo "<option value=''> == KEUZE == </option>";
                    while ($brand = mysqli_fetch_object($get_brands)) {
                        echo "<option value='" . $brand->id . "' >" . $brand->naam . "</option>";
                    }
                    ?>
                        </select>
                        <label>Nieuw model: </label><input type="text" name="new_model" id="new_model"/>
                        <input type="submit" name="btn_new_model" id="btn_new_model" value="Toevoegen"/>
                        <input type='hidden' name='tab_id' id='tab_id' value='4' />
                    </form>
                            <?php
                            foreach ($_POST as $post => $waarde) {
                                switch ($post) {
                                    case 'btn_new_model':
                                        echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='brandmodel_response'>Nieuw model is toegevoegd.</span>";
                                        break;
                                    case 'btn_new_brand':
                                        echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='brandmodel_response'>Nieuw brand is toegevoegd.</span>";
                                        break;
                                    case 'btn_model_delete':
                                        echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='brandmodel_response'>Model is Verwijderd.</span>";
                                        break;
                                    case 'btn_brand_delete':
                                        echo "<span style='position:absolute;top:50px;right:10px;color:green;' class='brandmodel_response'>Merk is Verwijderd.</span>";
                                        break;
                                }
                            }
                            ?>
                </div>
                <div id="tabs-6"></div>
            </div>
        </div>
        <center><?php
include "inc/footer.php";
?></center>

    </body>
</html>
