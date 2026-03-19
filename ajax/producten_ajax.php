<?php

include "../inc/db.php";
include "../inc/functions.php";




/* * ******************************
 * TAB Overzicht producten
 * ****************************** */
/***** SORT LIST **** */
if( isset($_POST['sort']) && $_POST['action'] == 'sortList'){
    // ASC/DESC
    if(isset($_POST['direction']) && $_POST['direction'] == 0)
    {
        $direction = "ASC";
        echo 1;
    }else{
        $direction = "DESC";
        echo 0;
    }
   // SORT ON: BRAND - MODEL - PRODUCT TYPE

   $sort_arr = array();

   $q_get_products = mysqli_query($conn, "SELECT * FROM tbl_products");
   while($product = mysqli_fetch_object($q_get_products))
   {
       $productname = mysqli_fetch_array(mysqli_query($conn, "SELECT type FROM tbl_product_type WHERE id=".$product->product_type_id));
       $brandname = mysqli_fetch_array(mysqli_query($conn, "SELECT naam FROM tbl_product_brand WHERE id=".$product->product_brand_id));
       $modelname = mysqli_fetch_array(mysqli_query($conn, "SELECT naam FROM tbl_product_model WHERE id=".$product->product_model_id));
       $q_status = "SELECT b.choice FROM tbl_product_values as a , tbl_product_field_choices as b WHERE a.product_id=".$product->id." AND a.product_fields_id=67 AND a.value=b.id";
       $status = mysqli_fetch_array(mysqli_query($conn, $q_status));

       $sort_arr[] = array( "type" => $productname[0],
                            "brand" => $brandname[0],
                            "model" => $modelname[0],
                            "name" => $product->name,
                            "status" => $status[0],
                            "product_type_id" => $product->product_type_id,
                            "product_brand_id" => $product->product_brand_id,
                            "product_model_id" => $product->product_model_id,
                            "product_id" => $product->id);
   }

   aasort($sort_arr, $_POST['sort']);

   if(isset($_POST['direction']) && $_POST['direction'] == 1)
   {
       $sort_arr = array_reverse($sort_arr,TRUE);
   }

   $i=0;
   foreach($sort_arr as $arr )
   {
       $i++;
       $kleur = $kleur_grijs;
       if ($i % 2) {
           $kleur = "white";
       }
       echo "<tr class='product_".$arr['product_id']."' style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'><td>";
       echo "<a href='' class='edit_prod' alt=".$arr['product_id']."><img src='images/edit.png'/></a>";
       echo "<a href='' class='del_prod' alt=".$arr['product_id']."><img src='images/delete.png'/></a></td>";
       echo "<td>".$arr['type']."</td>";
       echo "<td>".$arr['brand']."</td>";
       echo "<td>".$arr['model']."</td>";
       echo "<td class='product_name' title='".$arr['name']."'>".truncate($arr['name'],25)."</td>";
       echo "<td class='status'>".$arr['status']."</td>";
       if($arr['status'] != 'Te koop'){
           echo "<td></td><td></td><td></td><td></td>";
       }else{
            $autovlan_check = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$arr['product_id']));
            if($autovlan_check->autovlan == '1')
            {
                $checked = 'checked';
            }else{$checked = '';}
            echo "<td><input type='checkbox' class='autovlan' name='chkbox_autovlan' ".$checked." /></td>";
            echo "<td><input type='checkbox' class='2dehands' name='chkbox_2dehands' /></td>";
            echo "<td><input type='checkbox' class='kapaza' name='chkbox_kapaza' /></td>";
            echo "<td><input type='checkbox' class='autoscout24' name='chkbox_autoscout24' /></td>";
       }
       echo "</tr>";
       // extra info
       echo "<tr class='extra_".$arr['product_id']."' style='display:none;'><td colspan='9'><table width='600' style='margin-left:60px;' cellpadding='0' cellspacing='0' id='tbl_".$arr['product_id']."'>";

       echo "</table></td></tr>";
   }
}
/***** GET EXTRA INFORMATIONS **** */
if (isset($_POST['product_id']) && !empty($_POST['product_id']) && $_POST['action'] == 'getList'){
    $g_get_values = mysqli_query($conn, "SELECT a.* FROM tbl_product_values as a,tbl_product_fields as b WHERE product_id=".$_POST['product_id']. " AND a.product_fields_id=b.id AND b.id!=75 ORDER BY field_sort ASC");
    $j = 0;
    $countSelectBox = '';
    while($field = mysqli_fetch_object($g_get_values)) // loop all fields
    {
        $j++;
        $kleur = $kleur_grijs;
        if ($j % 2) {
            $kleur = "white";
        }
        $fieldname = mysqli_fetch_array(mysqli_query($conn, "SELECT field,field_type FROM tbl_product_fields WHERE id=".$field->product_fields_id));
        $q_choice = mysqli_query($conn, "SELECT choice FROM tbl_product_field_choices WHERE product_fields_id=".$field->product_fields_id." AND id=".$field->value);
        $choice = mysqli_fetch_array($q_choice);
        if(mysqli_num_rows($q_choice) != 0) // Choice
        {
            if($fieldname['field_type'] == 7) // type = selectbox
            {
                echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' class=''>";
                if($countSelectBox != $fieldname[0]) // first time show label
                {
                    echo "<td style='width:150px;font-size:10px;' valign='top'>".$fieldname[0].": </td><td style='font-size:10px;'>".$choice[0]."</td></tr>";
                }else{ // next don't show 
                    echo "<td style='width:150px;font-size:10px;' valign='top'></td><td style='font-size:10px;'>".$choice[0]."</td></tr>";
                }
                $countSelectBox = $fieldname[0];
            }else{
                echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' class=''>";
                echo "<td style='width:150px;font-size:10px;' valign='top'>".$fieldname[0].": </td><td style='font-size:10px;'>".$choice[0]."</td></tr>";
            }
        }else{ // No choice
            echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");' class=''>";
            echo "<td style='width:150px;font-size:10px;' valign='top'>".$fieldname[0].": </td><td style='font-size:10px;'>".$field->value."</td></tr>";
        }
    }    
}
/* * *** DELETE PRODUCT **** */
if (isset($_POST['product_id']) && $_POST['action'] == 'del_prod'){
    // delete tabel products
    mysqli_query($conn, "DELETE FROM tbl_products WHERE id=".$_POST['product_id']);
    // delete product_values
    mysqli_query($conn, "DELETE FROM tbl_product_values WHERE product_id=".$_POST['product_id']);
    // delete fotos
    mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_soort_id=".$_POST['product_id']);
    // verander dir
    chdir('../../images/uploads/products/');
    // verwijder folder
    delFolder($_POST['product_id'] . '/');
    // ga terug naar beheer
    chdir('../../../');
    chdir('beheer');
    // product id terug sturen naar de hoofdpagina
    echo $_POST['product_id'];
}
// delete images/uploads/products/id folder
function delFolder($dir) {
        $files = glob( $dir . '*', GLOB_MARK );
        foreach( $files as $file ){
            if( substr( $file, -1 ) == '/' )
                delTree( $file );
            else
                unlink( $file );
        }
        rmdir( $dir );
    }
/* * *** EDIT PRODUCT **** */
if (isset($_POST['product_id']) && $_POST['action'] == 'edit_prod'){
    echo "<b>Aanpassen van een product.</b><br /><br />";
    echo "<form method='post' id='frm_product_edit' enctype='multipart/form-data'><table cellspacing='2' cellpadding='2'>";
    $get_product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$_POST['product_id']));
    echo "<tr><td width='200'>Type:</td>";
    $get_type = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_type WHERE id=".$get_product->product_type_id));
    echo "<td>".$get_type->type."</td>";
    echo "</tr>";
    echo "<tr><td>Naam:</td><td>";
    if($get_type->id == 1)
    {
        echo "<input type='text' name='name' id='name' value='".$get_product->name."'/>";
    }else{
        echo "<input class='required' type='text' name='name' id='name' value='".$get_product->name."'/>";
    }
    echo "</td></tr>";
    if($get_type->id == 1)
    {
        // product = AUTO
        $get_brands = mysqli_query($conn, "SELECT * FROM tbl_product_brand");
        echo "<tr><td>Merk: </td><td><select class='required' name='brand' id='brand'>";
            // get BRAND options
            while($brand = mysqli_fetch_object($get_brands))
            {
                if($brand->id == $get_product->product_brand_id)
                {
                    echo "<option value=".$brand->id." selected='selected'>".$brand->naam."</option>";
                }else{
                    echo "<option value=".$brand->id.">".$brand->naam."</option>";
                }
            }
        echo "</select></td></tr>";
        $get_models = mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE merk_id=".$get_product->product_brand_id);
        echo "<tr><td>Model: </td><td><select class='required' name='model' id='model'>";
        // get MODEL options
            while($model = mysqli_fetch_object($get_models))
            {
                if($model->id == $get_product->product_model_id)
                {
                    echo "<option value=".$model->id." selected='selected'>".$model->naam."</option>";
                }else{
                    echo "<option value=".$model->id.">".$model->naam."</option>";
                }
            }
        echo "</select></td></tr>";
    }
    $get_fields = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id=".$get_product->product_type_id . " ORDER BY field_sort ASC");
    while($field = mysqli_fetch_object($get_fields)) // loop alle velden
    {
        $required = '';
        if($field->field_required == 1) // check if required
        {
            $required = " required";
        }
        $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id=".$field->id);
        if(mysqli_num_rows($get_choices) == 0) // als geen keuze
        {
            $date = '';
            echo "<tr><td valign='top'>".$field->field.": </td>";
            $get_field_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$field->id." AND product_id=".$get_product->id);
            if(mysqli_num_rows($get_field_value) == 0) // als er geen data is
            {
                switch($field->field_type)
                {
                    case 1:
                        echo "<td><input class='field_datum".$required."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 3:
                        echo "<td><input onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 4:
                        echo "<td><input onkeypress='return isNumberKey(event);' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 5:
                        echo "<td><textarea style='resize:both;width:400px;height:200px;' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' ></textarea></td></tr>";
                        break;
                    case 8:
                        echo "<td><select name='veld_".$field->id."'>";
                        $getFotos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$_POST['product_id']);
                        while($foto = mysqli_fetch_object($getFotos))
                        {
                            if($foto->cf_file == $field_value->value)
                            {
                                echo "<option value='".$field_value->value."' selected>".$field_value->value."</option>";
                            }else{
                                echo "<option value='".$foto->cf_file."'>".$foto->cf_file."</option>";
                            }
                        }
                        echo "</select></td></tr>";
                        break;
                    default:
                        echo "<td><input class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                }
            }else{ // als er data is
                $field_value = mysqli_fetch_object($get_field_value);
                switch($field->field_type)
                {
                    case 1:
                        echo "<td><input class='field_datum".$required."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' value='".$field_value->value."' /></td></tr>";
                        break;
                    case 3:
                        echo "<td><input onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' value='".$field_value->value."' /></td></tr>";
                        break;
                    case 4:
                        echo "<td><input onkeypress='return isNumberKey(event);' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' value='".$field_value->value."' /></td></tr>";
                        break;
                    case 5:
                        echo "<td><textarea style='resize:both;width:400px;height:200px;' class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' >".$field_value->value."</textarea></td></tr>";
                        break;
                    case 8:
                        echo "<td><select name='veld_".$field->id."'>";
                        $getFotos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$_POST['product_id']);
                        while($foto = mysqli_fetch_object($getFotos))
                        {
                            if($foto->cf_file == $field_value->value)
                            {
                                echo "<option value='".$field_value->value."' selected>".$field_value->value."</option>";
                            }else{
                                echo "<option value='".$foto->cf_file."'>".$foto->cf_file."</option>";
                            }
                        }
                        echo "</select></td></tr>";
                        break;
                    default:
                        echo "<td><input class=".$date.$required." type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' value='".$field_value->value."' /></td></tr>";
                        break;
                }
            }
        }else{ // als keuze
            echo "<tr><td valign='top'>".$field->field.": </td>";
            $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id=".$field->id);
            if($field->field_type == 6) // keuze veld
            {
                echo "<td>";
                echo "<select class='".$required."' name='veld_".$field->id."' >";
                echo "<option value=''> == KEUZE == </option>";
                while($choice = mysqli_fetch_object($get_choices)) // loop keuzes
                {
                    $get_field_value = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$field->id." AND product_id=".$get_product->id));
                    if($choice->id == $get_field_value->value)
                    { // selected
                        echo "<option value='".$choice->id."' selected>".$choice->choice."</option>";
                    }else{ // not selected
                        echo "<option value='".$choice->id."'>".$choice->choice."</option>";
                    }
                }
                echo "</select>";
            }else{ // selectiebox
                echo "<td colspan='2'>";
                while ($choice = mysqli_fetch_object($get_choices)) 
                {
                    $get_field_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$field->id." AND value='".$choice->id."' AND product_id=".$get_product->id);
                    if(mysqli_num_rows($get_field_value) == 0) // geen data
                    {
                        echo "<input type='checkbox' name='opties".$field->id."[]' value=".$choice->id.">" . $choice->choice . "<br />";
                    }else{ // data
                        echo "<input type='checkbox' name='opties".$field->id."[]' value=".$choice->id." checked>" . $choice->choice . "<br />";
                    }
                    
                }
            }
            echo "</td></tr>";
        }
    }
    // ADD PHOTO'S
    echo "<tr><td><label>Foto's</label></td>";
    echo "<td><input type='file' name='photo_doc[]' id='photo_doc' multiple='multiple'/></td></tr>";
    $get_photos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$_POST['product_id']);
    if(mysqli_num_rows($get_photos) != 0)
    {
        while($photo = mysqli_fetch_object($get_photos))
        {
            echo "<tr>";
            echo "<td></td>";
            echo "<td>Verwijder <input type='checkbox' name='photo_del[]' id='del_photo' value='".$photo->cf_id."' />";
            echo " <a href='../images/uploads/products/".$_POST['product_id']."/".$photo->cf_file."' target='_blank' alt='".$photo->cf_file."'> <img width=50 height=50 src='../images/uploads/products/".$_POST['product_id']."/".$photo->cf_file."' alt='".$photo->cf_file."' /></a><span>".$photo->cf_file."</span></td>";
            echo "</tr>";
        }
    }
    echo "</table><br />";
    echo "<input type='submit' name='btn_edit_prod_opslaan' id='btn_edit_prod_opslaan' value='Opslaan' />";
    echo "<input type='hidden' name='product_id' id='product_id' value='" . $get_product->id . "' />";
    echo "</form>";
    
}
/* * ******************************
 * TAB Nieuw
 * ****************************** */
if (isset($_POST['type_id']) && !empty($_POST['type_id']) && $_POST['action'] == 'new_fields') {
    // get fields
    // if it has a field
    //      loop fields
    //      <tr>
    //          if it has choice 
    //          then select <td></td>
    //          else input<td></td>
    //      </tr>
    $get_fields = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['type_id'] . "' AND id!=75 ORDER BY field_sort ASC");
    // FIELD YES/NO
    if (mysqli_num_rows($get_fields) > 0) {
        if($_POST['type_id'] == 1)
        {
            echo "<tr><td>Naam: </td><td><input type='text' name='product_name' id='product_name' /></td></tr>";
        }else{
            echo "<tr><td>Naam: </td><td><input type='text' class='required' name='product_name' id='product_name' /></td></tr>";
        }
        // AUTO BRAND & MODEL
        if($_POST['type_id'] == 1){
            echo "<tr><td>Brand: </td>";
            echo "<td><select class='required' name='brand' id='brand'><option></option>";
            $get_brands = mysqli_query($conn, "SELECT * FROM tbl_product_brand");
            while($brand = mysqli_fetch_object($get_brands))
            {
                echo "<option value=".$brand->id.">".$brand->naam."</option>";
            }
            echo "</select></td></tr>";
            echo "<tr><td>Model: </td>";
            echo "<td><select class='required' name='model' id='model'>";
            echo "</select></td></tr>";
        }
        // FIELD LOOP
        while ($field = mysqli_fetch_object($get_fields)) {
            $required = '';
            if($field->field_required == 1)
            {
                $required = ' required';
            }
            echo "<tr>";
            echo "<td style='width:200px;' valign='top'>" . $field->field . " :</td>";
            $check_choice = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id='" . $field->id . "'");
            // CHOICE YES/NO
            if (mysqli_num_rows($check_choice) > 0) {
                
                if($field->field_type == 6)
                {
                    echo "<td>";
                    echo "<select class='".$required."' name='veld_" . $field->id . "'>";
                    echo "<option value=''> == KEUZE == </option>";
                    while ($choice = mysqli_fetch_object($check_choice)) 
                    {
                        echo "<option value='" . $choice->id . "'>" . $choice->choice . "</option>";
                    }
                    echo "</select>";
                }else{
                    echo "<td colspan='2'>";
                    while ($choice = mysqli_fetch_object($check_choice)) 
                    {
                        echo "<input type='checkbox' name='opties" . $field->id . "[]' value=".$choice->id.">" . $choice->choice . "<br />";
                    }
                }
                echo "</td></tr>";
            } else {
                switch($field->field_type)
                {
                    case 1:
                        echo "<td><input class='field_datum".$required."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 3:
                        echo "<td><input onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='".$required.$date."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 4:
                        echo "<td><input onkeypress='return isNumberKey(event);' class='".$required.$date."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                    case 5:
                        echo "<td><textarea style='resize:both;width:400px;height:200px;' class='".$required.$date."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' ></textarea></td></tr>";
                        break;
                    default:
                        echo "<td><input class='".$required.$date."' type='text' id='veld_" . $field->id . "' name='veld_" . $field->id . "' /></td></tr>";
                        break;
                }
                // input einde tr
            }
        }
        // ADD PHOTO'S
        echo "<tr><td><label>Hoofdfoto: </label></td>";
        echo "<td><input type='file' name='photo_hoofd' id='photo_doc'/></td></tr>";
        echo "<tr><td><label>Foto's: </label></td>";
        echo "<td><input type='file' name='photo_doc[]' id='photo_doc' multiple='multiple'/></td></tr>";
    }
    echo "<td colspan='2' align='center'><input style='position:relative;float:right;' type='submit' name='bewaar' id='bewaar' value='Opslaan' /></td>";
}
if(isset($_POST['brand_id']) && !empty($_POST['brand_id'])){
    $get_models = mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE merk_id=".$_POST['brand_id']);
    while($model = mysqli_fetch_object($get_models))
    {
        echo "<option value=".$model->id.">".$model->naam."</option>";
    }
}
/* * ******************************
 * TAB Overzicht velden
 * ****************************** */
/* * *** DELETE TYPE PRODUCT **** */
if (isset($_POST['type_id']) && !empty($_POST['type_id']) && $_POST['action'] == 'delete') {
    // delete rij
    $q_del_type = mysqli_query($conn, "DELETE FROM tbl_product_type WHERE id='" . $_POST['type_id'] . "'");
}

/* * *** EDIT TYPE PRODUCT **** */
if (isset($_POST['type_id']) && !empty($_POST['type_id']) && $_POST['action'] == 'edit') {
    $q_upd_type = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_type WHERE id='" . $_POST['type_id'] . "'"));
    echo "<b>Aanpassen van soortnaam</b><br /><br />";
    echo "<form method='post' name='frm_type_update'>";
    echo "<label>Soortnaam: </label>";
    echo "<input type='text' name='soort_type' value='" . $q_upd_type->type . "'/>";
    echo "<input type='submit' name='btn_update_type' value='Wijzig'/>";
    echo "<input type='submit' name='btn_annuleren' value='Annuleren'/>";
    echo "<input type='hidden' name='tab_id' id='tab_id' value='2' />";
    echo "<input type='hidden' name='type_id' id='type_id' value='" . $q_upd_type->id . "' />";
    echo "</form>";
    // form maken
}
/* * ******************************
 * TAB VELDEN
 * ****************************** */
/* * *** ADD FIELD **** */
if (isset($_POST['field_id']) && !empty($_POST['field_id']) && $_POST['action'] == 'add') {
    // check if exist
    $q_check_field = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['field_id'] . "' AND field='" . $_POST['waarde'] . "'"));
    if ($q_check_field == 0) {
        // insert field
        $max_field_sort = mysqli_fetch_array(mysqli_query($conn, "SELECT MAX(field_sort) FROM tbl_product_fields WHERE product_type_id=".$_POST['type']));
        $field_sort_id = $max_field_sort['MAX(field_sort)'] + 1;
        mysqli_query($conn, "INSERT INTO tbl_product_fields (product_type_id,field,field_type,field_required,field_sort) VALUES (" . $_POST['field_id'] . ",'" . $_POST['waarde'] . "',".$_POST['field_type'].",".$_POST['field_required'].",".$field_sort_id.")");

        // get field list (refreshing the list)
        $q_get_velden = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['type'] . "' ORDER BY field_sort ASC");
        getListFields($q_get_velden, $kleur_grijs,$field_type_arr,$_POST['type']);
    }else{
        // get field list (refreshing the list)
        $q_get_velden = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id='" . $_POST['type'] . "' ORDER BY field_sort ASC");
        echo 'exist';
        getListFields($q_get_velden, $kleur_grijs,$field_type_arr,$_POST['type']);
    }
}

/* * *** DELETE FIELD **** */
if (isset($_POST['field_id']) && !empty($_POST['field_id']) && $_POST['action'] == 'delete') {
    // delete rij
    mysqli_query($conn, "DELETE FROM tbl_product_fields WHERE id = " . $_POST['field_id']);
}
/* * *** EDIT FIELD **** */
if (isset($_POST['field_id']) && !empty($_POST['field_id']) && $_POST['action'] == 'edit') {
    $q_upd_field = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=" . $_POST['field_id']));

    echo "<b>Aanpassen van veld</b><br /><br />";
    echo "<form method='post' name='frm_field_update'>";
    echo "<label style='width:100px;'>Veldnaam: </label>";
    echo "<input class='required' type='text' name='field_update' value='" . $q_upd_field->field . "'/><br />";
    echo "<label>Veldtype: </label><select class='required' name='veld_type' id='veld_type'><br />";
    echo "<option value=''> == KEUZE == </option>";
    foreach( $field_type_arr as $index => $waarde )
    {
        if($index == $q_upd_field->field_type)
        {
            echo "<option value='".$index."' selected>".$waarde."</option>";
        }else{
            echo "<option value='".$index."'>".$waarde."</option>";
        }
    }
    echo "</select><br />";
    $required = '';
    if($q_upd_field->field_required == 1)
    {
        $required = 'checked';
    }
    echo "<label>Verplicht: </label><input type='checkbox' name='field_required' id='field_required' ".$required."/>";
    echo "<br /><br />";
    echo "<input type='submit' name='btn_update_field' value='Wijzig'/>";
    echo "<input type='submit' name='btn_annuleren' value='Annuleren'/>";
    echo "<input type='hidden' name='tab_id' id='tab_id' value='3' />";
    echo "<input type='hidden' name='field_id' id='field_id' value='" . $q_upd_field->id . "' />";
    echo "</form>";
}
/* * *** SORT UP FIELD **** */
if (isset($_POST['field_id']) && $_POST['action'] == 'sortup')
{
    // huidige id -> get sort nummer
    // find other field with sort nummer + 1
    // update this field with current nummer
    // update huidige id with sort current nummer + 1
    $get_current_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=".$_POST['field_id']));
    $max_field_sort = mysqli_fetch_array(mysqli_query($conn, "SELECT MAX(field_sort) FROM tbl_product_fields WHERE product_type_id=".$_POST['type']));
    // CHECK IF LAST SORT_ID 
    if($get_current_id->field_sort != $max_field_sort['MAX(field_sort)'])
    {
        $other_sort_id = $get_current_id->field_sort + 1;
        $get_other_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE field_sort=".$other_sort_id." AND product_type_id=".$_POST['type']));
        $update_other_field = mysqli_query($conn, "UPDATE tbl_product_fields SET field_sort=".$get_current_id->field_sort." WHERE id=".$get_other_id->id);
        $update_current_field = mysqli_query($conn, "UPDATE tbl_product_fields SET field_sort=".$other_sort_id." WHERE id=".$_POST['field_id']);
    }
    $q_get_velden = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id=".$_POST['type']." ORDER BY field_sort ASC");
    getListFields($q_get_velden, $kleur_grijs, $field_type_arr,$_POST['type']);
}
/* * *** SORT DOWN FIELD **** */
if (isset($_POST['field_id']) && $_POST['action'] == 'sortdown'){
    // huidige id -> get sort nummer
    // find other field with sort nummer - 1
    // update this field with current nummer
    // update huidige id with sort current nummer - 1
    $get_current_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=".$_POST['field_id']));
    // CHECK IF SORT_ID ISN'T FIRST
    if($get_current_id->field_sort != 1)
    {
        $other_sort_id = $get_current_id->field_sort - 1;
        $get_other_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE field_sort=".$other_sort_id." AND product_type_id=".$_POST['type']));
        $update_other_field = mysqli_query($conn, "UPDATE tbl_product_fields SET field_sort=".$get_current_id->field_sort." WHERE id=".$get_other_id->id);
        $update_current_field = mysqli_query($conn, "UPDATE tbl_product_fields SET field_sort=".$other_sort_id." WHERE id=".$_POST['field_id']);
    }
    $q_get_velden = mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE product_type_id=".$_POST['type']." ORDER BY field_sort ASC");
    getListFields($q_get_velden, $kleur_grijs, $field_type_arr,$_POST['type']);
}
/* * *** GET CHOICES FOR A FIELD **** */
if (isset($_POST['field_choice_id']) && !empty($_POST['field_choice_id']) && $_POST['action'] == 'get') {
    $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id=" . $_POST['field_choice_id']);
    if(mysqli_num_rows($get_choices) == 0)
    {
        echo "er is geen keuze.";
    }else{
        getListChoice($get_choices, $kleur_grijs);
    }
}

/* * *** ADD CHOICE FOR A FIELD **** */
if (isset($_POST['product_fields_id']) && !empty($_POST['product_fields_id']) && $_POST['action'] == 'new') {
    // check if exist
    $q_check_choice = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id='" . $_POST['product_fields_id'] . "' AND choice='" . $_POST['choice'] . "'"));
    if ($q_check_choice == 0) {
        // insert
        mysqli_query($conn, "INSERT INTO tbl_product_field_choices (product_fields_id,choice) VALUES ('" . $_POST['product_fields_id'] . "','" . $_POST['choice'] . "')");

        // html add
        $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id='" . $_POST['product_fields_id'] . "'");
        getListChoice($get_choices, $kleur_grijs);
    }else{
        echo "exist";
        // html add
        $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id='" . $_POST['product_fields_id'] . "'");
        getListChoice($get_choices, $kleur_grijs);
    }
}

/* * *** DELETE CHOICE FOR A FIELD **** */
if (isset($_POST['choice_id']) && !empty($_POST['choice_id']) && $_POST['action'] == 'delete') {
    mysqli_query($conn, "DELETE FROM tbl_product_field_choices WHERE id='" . $_POST['choice_id'] . "'");

    // html add
    $get_choices = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE product_fields_id='" . $_POST['product_fields_id'] . "'");
    getListChoice($get_choices, $kleur_grijs);
}

/* * *** EDIT CHOICE FOR A FIELD **** */
if (isset($_POST['choice_id']) && !empty($_POST['choice_id']) && $_POST['action'] == 'edit') {
    $q_upd_choice = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id='" . $_POST['choice_id'] . "'"));
    echo "<label><b>Aanpassen van keuze</b></label>";
    echo "<form method='post' name='frm_choice_update'>";
    echo "<label>Keuzenaam: </label>";
    echo "<input type='text' name='choice_update' value='" . $q_upd_choice->choice . "'/>";
    echo "<input type='submit' name='btn_update_choice' value='Wijzig'/>";
    echo "<input type='submit' name='btn_annuleren' value='Annuleren'/>";
    echo "<input type='hidden' name='tab_id' id='tab_id' value='3' />";
    echo "<input type='hidden' name='choice_id' id='choice_id' value='" . $q_upd_choice->id . "' />";
    echo "</form>";
}

/* * *** CHOICES FORM **** */

function getListChoice($get_choices, $kleur_grijs) {
    $i = 0;
    while ($choice = mysqli_fetch_object($get_choices)) {
        $i++;
        $kleur = $kleur_grijs;
        if ($i % 2) {
            $kleur = "white";
        }
        echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
        echo "<td style='width:50px;'><a class='edit_choice' alt='" . $choice->id . "' href=''><img src='images/edit.png'/></a>";
        // als veld niet gebruikt is dan moet de delete knop zichtbaar worden.
        $q_choice_used = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE value='" . $choice->id . "' AND product_fields_id=".$choice->product_fields_id);
        if (mysqli_num_rows($q_choice_used) == 0) {
            echo "<a class='del_choice' alt='" . $choice->id . "' href=''><img src='images/delete.png'/></a></td>";
        } else {
            echo "</td>";
        }
        echo "<td>" . $choice->choice . "</td>";
        echo "</tr>";
    }
}
function getListFields($q_get_velden, $kleur_grijs,$field_type_arr,$sortTitle){
        $i = 0;
        $kleur = $kleur_grijs;
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
            echo "<td title=".$veld->id."><span class='sort_up' title=".$sortTitle."></span><span class='sort_down' title=".$sortTitle."></span></td>";
            echo "</tr>";
        }
}
?>
<script type="text/javascript">
$(document).ready(function(){
    $('.field_datum').datepicker( { dateFormat: 'dd-mm-yy' } );
    $('#frm_product_edit').validate();
   $('#frm_field_update').validate();
});
</script>

