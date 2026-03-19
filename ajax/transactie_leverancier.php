<?php
include "../inc/db.php";
include "../inc/functions.php";

// NEW TRANSACTION
if(isset($_POST['btn_transactie']) && !isset($_POST['update']))
{
    mysqli_query($conn, "INSERT INTO tbl_transacties (prod_id,status,soort,soort_id,datum,prijs_excl,prijs_incl,btw) VALUES (".$_POST['product'].",'".$_POST['status']."','1',".$_POST['lev_id'].",'".changeDate2EU($_POST["datum"])."','".$_POST['prijs_exclu']."', '".$_POST['prijs_inclu']."','".$_POST['btw']."')"); 
}

// UPDATE TRANSACTION
if(isset($_POST['btn_transactie']) && isset($_POST['update']))
{
    mysqli_query($conn, "UPDATE tbl_transacties SET prod_id=".$_POST['product'].","
            . " status='".$_POST['status']."',"
            . "soort='1',soort_id=".$_POST['lev_id'].","
            . "datum='".changeDate2EU($_POST["datum"])."',"
            . "prijs_excl='".$_POST['prijs_exclu']."',"
            . "prijs_incl='".$_POST['prijs_inclu']."',"
            . "btw='".$_POST['btw']."' WHERE id=".$_POST['update']); 
}

// UPDATE PRODUCT
if(isset($_POST['status'])){
    $product_id = $_POST['product'];
    if($_POST['status'] == 0){
        $u_product = mysqli_query($conn, "UPDATE tbl_product_values SET value=39 WHERE product_id=".$product_id." AND product_fields_id=67");
    }else{
        $u_product = mysqli_query($conn, "UPDATE tbl_product_values SET value=38 WHERE product_id=".$product_id." AND product_fields_id=67");
    }
}

/* DOCUMENT UPLOAD */
if (isset($_FILES["file"]) && !empty($_FILES["file"]))
{
    chdir('../lev_docs/');
    // make map for user and go
    if(!is_dir($_POST['lev_id']) || !file_exists($_POST['lev_id'])){
        @mkdir($_POST['lev_id'],0777);
    }
    chdir($_POST['lev_id']);
    
    $get_last_trans_id = mysqli_query($conn, "SELECT * FROM tbl_transacties ORDER BY id DESC LIMIT 1");
    if(mysqli_num_rows($get_last_trans_id) != 0){
        $transactie_id = mysqli_fetch_object($get_last_trans_id);
        $trans_id = $transactie_id->id;
    }else{
        $trans_id = 1;
    }
    
    if (!is_dir('transactie') || !file_exists('transactie')) {
        @mkdir('transactie', 0777);
    }
    chdir('transactie');
    
    $ext = findexts ($_FILES['file']['name']) ;
    
    // make map for transaction and go 
    if(isset($trans_id) && !empty($trans_id))
    {
        if(!is_dir($trans_id) || !file_exists($trans_id))
        {
            @mkdir($trans_id, 0777);
        }       
        
        chdir($trans_id);
    }
    // upload file
    if(isset($_FILES['file']))
    {
        if(move_uploaded_file($_FILES['file']['tmp_name'], $trans_id. "." .$ext))
        {
            if(isset($trans_id))
            {
                mysqli_query($conn, "INSERT INTO kal_customers_files (cf_soort_id,cf_soort,cf_file) VALUES (".$trans_id.",'transactie','".$trans_id.".".$ext."')");
            }
        }
    }
    chdir("../../../../");
}
function findexts ($filename) 
    { 
        $filename = strtolower($filename) ; 
        $exts = split("[/\\.]", $filename) ; 
        $n = count($exts)-1; 
        $exts = $exts[$n]; 
        return $exts; 
    } 
?>

<html>
    <head>
        <script type="text/javascript" src="../js/functions.js"></script>
        
        <link href="../css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
        <link href="../css/jquery.fancybox.css" rel="stylesheet" type="text/css" />
        <link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" media="all" />
        <link rel="stylesheet" type="text/css" media="print" href="../css/print.css" />
        
        <script type="text/javascript" src="../js/jquery-1.5.1.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="../js/jquery.fancybox.pack.js"></script>
        <script type="text/javascript" src="../js/jquery.fancybox.js"></script>
        <script type="text/javascript" src="../js/jquery.ui.widget.js"></script>
        <script type="text/javascript" src="../js/jquery.ui.tabs.js"></script>
        <script type="text/javascript" src="../js/jquery.validate.js"></script>
        <style>
            .error{color:red;}
        </style>
        <script type="text/javascript">
        $(document).ready(function(){
            $('.field_datum').datepicker( { dateFormat: 'dd-mm-yy' } );
            $('.status').live('change',function(){
                    var status = '';
                    if($(this).val() == 0){
                        status = 'aankoop';
                    }else{
                        status = 'verkoop';
                    }
                    $.post("../ajax/klanten_ajax.php", {status: status}, function(data) {
                        $('#product').html(data);
                    });
                });
            $('.prijs_exc').live('change',function(){
                var btw = $('.btw').val();
                var exc = $(this).val();
                switch(btw){
                    case '0':
                        $('.prijs_inc').val(exc);
                        break;
                    case '1':
                        $('.prijs_inc').val(exc * 1.06);
                        break;
                    case '2':
                        $('.prijs_inc').val(exc * 1.21);
                        break;
                }
            });
            if($('#posted').length > 0){
//                window.parent.$.fancybox.close();
            }
            $('#frm_transactie').validate();
            $('.btw').live('change',function(){
                var btw = $(this).val();
                var exc = $('.prijs_exc').val();
                switch(btw){
                    case '0':
                        $('.prijs_inc').val(exc);
                        break;
                    case '1':
                        $('.prijs_inc').val(exc * 1.06);
                        break;
                    case '2':
                        $('.prijs_inc').val(exc * 1.21);
                        break;
                }
            });
        });
        </script>
    </head>
    <body>
<?php
if(isset($_POST['btn_transactie'])){
    echo "<div id='posted'></div>";
}
if(isset($_GET['trans_id'])){
    $transactie_get_id = $_GET['trans_id'];
    $get_transactie = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_transacties WHERE id=".$transactie_get_id));
    $datum = $get_transactie->datum;
    $prijs_incl = $get_transactie->prijs_incl;
    $prijs_excl = $get_transactie->prijs_excl;
}else{
    $datum = '';
    $prijs_incl = '';
    $prijs_excl = '';
}

echo "<h3>Add Transaction</h3>";
echo "<form method='post' enctype='multipart/form-data'  id='frm_transactie'>";
echo "<table cellpadding='2' cellspacing='2'>";
    echo "<tr>";
        echo "<td width='160'>";
            echo "<label>Status: </label>";
        echo "</td>";
        echo "<td>";
            echo "<select name='status' class='status'>";
            echo "<option>= CHOICE =</option>";
                foreach( $transactie_status as $index => $waarde )
                {
                    if(isset($transactie_get_id) && $get_transactie->status == $index){
                        echo "<option value='".$index."' selected>".$waarde."</option>";
                    }else{
                        echo "<option value='".$index."'>".$waarde."</option>";
                    }
                }
            echo "</select>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>Product: </label>";
        echo "</td>";
        echo "<td>";
        echo "<select name='product' class='required' id='product'>";
            echo "</select>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>Date: </label>";
        echo "</td>";
        echo "<td>";
            echo "<input class='field_datum required' type='text' id='datum' name='datum' value='".changeDate2EU($datum)."'/>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>Price exclusive: </label>";
        echo "</td>";
        echo "<td>";
            echo "<input class='prijs_exc required' type='text' id='prijs_exc' name='prijs_exclu' value='".$prijs_excl."' />";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>VAT: </label>";
        echo "</td>";
        echo "<td>";
            echo "<select name='btw' class='btw'>";
                foreach( $btw_arr as $index => $waarde )
                {
                    if(isset($transactie_get_id) && $get_transactie->btw == $index){
                        echo "<option value='".$index."' selected>".$waarde."</option>";
                    }else{
                        echo "<option value='".$index."'>".$waarde."</option>";
                    }
                }
            echo "</select>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>Price inclusive: </label>";
        echo "</td>";
        echo "<td>";
            echo "<input class='prijs_inc required' type='text' id='prijs_inclu' name='prijs_inclu' value='".$prijs_incl."' readonly/>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<label>Purchase/sale document: </label>";
        echo "</td>";
        echo "<td>";
            echo "<input type='file' id='file' name='file'/>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            if(isset($_GET['trans_id'])){
                echo "<input type='hidden' name='update' value='".$_GET['trans_id']."' />";
            }
            echo "<input type='hidden' name='tab_id' value='1' />";
            echo "<input type='hidden' name='lev_id' value='".$_GET["lev_id"]."' />";
            echo "<input type='submit' name='btn_transactie' id='btn_transactie' value='Save'/>";
        echo "</td>";
    echo "</tr>";
echo "</table>";
echo "</form>";
?>
    </body>
</html>