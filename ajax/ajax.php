<?php

include "../inc/db.php";
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * Aantal tonen in de lijst = $_POST['aantal']
 * 56 = prijs
 * 2 = kilometerstand
 * 1 = brandstof
 */
/*************** GET POST VARIABLES ************/
$product_type_id = 1;
$product_brand = $_POST['brand'];
$product_model = $_POST['model'];
$product_prijs_min = '';
$product_prijs_max = '';
if(isset($_POST['min_prijs']))
{
    $product_prijs_min = $_POST['min_prijs'];
    $product_prijs_max = $_POST['max_prijs'];
}
$product_km_min = '';
$product_km_max = '';
if(isset($_POST['min_km']))
{
    $product_km_min = $_POST['min_km'];
    $product_km_max = $_POST['max_km'];
}
$name = '';

/*************** VARIABLES IN AN ARRAY *************/
$rgCondition = array('product_brand_id'=>$product_brand,
                'product_model_id'=>$product_model,
                'name'=>$name,
                'product_type_id'=>$product_type_id,
                'min_prijs'=>$product_prijs_min,
                'max_prijs'=>$product_prijs_max,
                'min_km'=>$product_km_min,
                'max_km'=>$product_km_max);
// removes all NULL, FALSE and Empty Strings but leaves 0 (zero) values
$result = array_filter( $rgCondition, 'strlen' );

$Where = array();
$From = array();
$From[] = " tbl_products as a";
foreach($result as $sField=>$mValue)
{
    switch($sField)
    {
        case 'product_brand_id':
            $Where[] = $sField."=".$mValue;
            break;
        case 'product_model_id':
            $Where[] = $sField."=".$mValue;
            break;
        case 'name':
            $Where[] = $sField."=".$mValue;
            break;
        case 'min_prijs':
            $Where[] = "z.product_fields_id=56";
            $Where[] = "z.value>".$mValue;
            $From[] = "JOIN tbl_product_values as z ON a.id=z.product_id";
            break;
        case 'max_prijs':
            $Where[] = "z.value<".$mValue;
            break;
        case 'min_km':
            $Where[] = "x.product_fields_id=2";
            $Where[] = "x.value>".$mValue;
            $From[] = " JOIN tbl_product_values as x ON a.id = x.product_id";
            break;
        case 'max_km':
            $Where[] = "x.value<".$mValue;
            break;
        default:
            break;
    }
}
/*/******** Verwijder duplicate ******/
$Where_unique = array_unique($Where);
$From_unique = array_unique($From);

/************** SQL COMMANDO *********/ 
$sWhere = count($Where_unique)?' WHERE '.join(' AND ', $Where_unique):'';
$sFrom = count($From_unique)?' FROM'.join( ' ', $From_unique):'';
$q = "SELECT a.id,a.product_brand_id,a.product_model_id,a.name ".$sFrom . $sWhere." ORDER BY a.id DESC";

/********* PAGINATIE *****************/
$count_auto = mysqli_num_rows(mysqli_query($conn, $q));
echo $count_auto;
$aantal = $_POST['aantal'];
$pagina = 1;
if(isset($_POST['pagina']) && $_POST['pagina'] != '')
{
    $pagina = $_POST['pagina'];
}
if($count_auto>$aantal)
{
    $limit = ($aantal*$pagina)-$aantal.','.$aantal;
    $q .= " LIMIT " .$limit;
}else{
    $limit = $aantal;
    $q .= " LIMIT " .$limit;
}

/*************** TEST SQL COMMANDO *******/
//echo $q;

/*********************** AUTO LIJST ***************************/
$aantal_auto = mysqli_num_rows(mysqli_query($conn, "SELECT a.id,a.product_brand_id,a.product_model_id,a.name ".$sFrom . $sWhere));
$get_products = mysqli_query($conn, $q);
                    while($product = mysqli_fetch_object($get_products))
                    {
                    ?>
                            <div class="auto<?php echo $product->id; ?>">
                                <?php

                                echo "<div class='headerauto' title=".$product->id.">";
                                            echo $product->name;
                                echo "</div>";
                                echo "<div class='detailauto'>";
                                            $get_foto = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$product->id);
                                            $headphoto = mysqli_query($conn, "SELECT a.cf_file FROM kal_customers_files as a, tbl_product_values as b WHERE a.cf_soort_id=".$product->id." AND b.product_fields_id=75 AND a.cf_file=b.value");
                                            if(mysqli_num_rows($headphoto) == 0)// if no headphoto
                                            {
                                                if(mysqli_num_rows($get_foto) != 0) 
                                                {
                                                    echo "<ul class='fotoauto'>";
                                                    $i=0;
                                                    while($foto = mysqli_fetch_object($get_foto))
                                                    {
                                                        if($i == 5)
                                                        {
                                                            break;
                                                        }
                                                        if($i == 0)
                                                        {
                                                            echo "<li><a alt='".$product->id."' href='images/uploads/products/".$product->id."/".$foto->cf_file."' target='_blank'><img width=180 height=150 src='images/uploads/products/".$product->id."/".$foto->cf_file."' /></a></li>";
                                                        }else{
                                                            echo "<li><a alt='".$product->id."' href='images/uploads/products/".$product->id."/".$foto->cf_file."' target='_blank'><img width=100 height=70 src='images/uploads/products/".$product->id."/".$foto->cf_file."' /></a></li>";
                                                        }
                                                        $i++;
                                                    }
                                                    echo "</ul>";
                                                }
                                            }else{ // if head photo
                                                if(mysqli_num_rows($get_foto) != 0) 
                                                {
                                                    echo "<ul class='fotoauto'>";
                                                    $i=0;
                                                    while($foto = mysqli_fetch_object($get_foto))
                                                    {
                                                        if($i == 5)
                                                        {
                                                            break;
                                                        }
                                                        if($i == 0)
                                                        {
                                                            $head = mysqli_fetch_object($headphoto);
                                                            echo "<li><a alt='".$product->id."' href='images/uploads/products/".$product->id."/".$head->cf_file."' target='_blank'><img width=180 height=150 src='images/uploads/products/".$product->id."/".$head->cf_file."' /></a></li>";
                                                        }else{
                                                            echo "<li><a alt='".$product->id."' href='images/uploads/products/".$product->id."/".$foto->cf_file."' target='_blank'><img width=100 height=70 src='images/uploads/products/".$product->id."/".$foto->cf_file."' /></a></li>";
                                                        }
                                                        $i++;
                                                    }
                                                    echo "</ul>";
                                                }
                                            }
                               echo "<div class='infoauto'>";
                                    echo "<ul style='width:140px;margin-left:20px;padding:0;'>";
                                        // GET FIELDS - motor, brandstof(keuze), Schakeling(keuze), Type(keuze), eerste inschrijving, verbruik, km
                                        /*** MOTOR INHOUD ***/
                                        $get_motor_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=54"));
                                        $get_motor_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".number_format($get_motor_id->id, 0, ",", " ")." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_motor_value) == 1)
                                        {
                                            $motor_value = mysqli_fetch_object($get_motor_value);
                                            echo "<li><label title='Motor inhoud' class='motor_img'></label><label title='Motor inhoud'>".$motor_value->value."cm&sup3;</label></li>";
                                        }
                                        /*** BRANDSTOF ***/
                                        $get_brandstof_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=1"));
                                        $get_brandstof_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_brandstof_id->id." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_brandstof_value) == 1)
                                        {
                                            $brandstof_value = mysqli_fetch_object($get_brandstof_value);
                                            $get_brandstof = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id=".$brandstof_value->value));
                                            echo "<li><label title='Brandstof' class='diesel_img'></label><label title='Brandstof'>".$get_brandstof->choice."</label></li>";
                                        }
                                        /*** SCHAKELING ***/
                                        $get_schakeling_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=59"));
                                        $get_schakeling_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_schakeling_id->id." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_schakeling_value) == 1)
                                        {
                                            $schakeling_value = mysqli_fetch_object($get_schakeling_value);
                                            $get_schakeling = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id=".$schakeling_value->value));
                                            echo "<li><label title='Schakeling' class='type_img'></label><label title='Schakeling'>".$get_schakeling->choice."</label></li>";
                                        }
                                        /*** TYPE ***/
                                        $get_type_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=55"));
                                        $get_type_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_type_id->id." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_type_value) == 1)
                                        {
                                            $type_value = mysqli_fetch_object($get_type_value);
                                            $get_type = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id=".$type_value->value));
                                            echo "<li><label title='Soort auto' class='typeauto_img'></label><label title='Soort auto'>".$get_type->choice."</label></li>";
                                        }
                                    echo "</ul>";
                                echo "</div>";
                                echo "<div class='infoauto'>";
                                    echo "<ul style='width:200px;padding-left:0;'>";
                                        /*** INSCHRIJVINGSDATUM ***/
                                        $get_inschrijving_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=58"));
                                        $get_inschrijving_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_inschrijving_id->id." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_inschrijving_value) == 1)
                                        {
                                            $inschrijving_value = mysqli_fetch_object($get_inschrijving_value);
                                            echo "<li><label title='Eerste inschrijvingsdatum' style='position:relative;float:left;width:25px;color:grey;'>1.</label><label title='Eerste inschrijvingsdatum'>".$inschrijving_value->value."</label></li>";
                                        }
                                        /*** VERBRUIK ***/
                                        $verbruiken_id = array(57,70,71);//57 = gemengd; 70 = stad; 71 = snelweg
                                        $verbruiken = array();
                                        foreach($verbruiken_id as $id)
                                        {
                                            $get_verbruik_value = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$id." AND product_id=".$product->id));
                                            if(isset($get_verbruik_value->value))
                                            {
                                                $verbruiken[$get_verbruik_value->product_fields_id] = $get_verbruik_value->value;
                                            }
                                        }
                                        if(!empty($verbruiken))
                                        {
                                            echo "<li><label title='Verbruik per 100km(comb/stad/snelweg)' class='diesel_img'></label><label title='Verbruik per 100km(comb/stad/snelweg)'>";
                                            if(array_key_exists(57, $verbruiken))
                                            {
                                                echo $verbruiken[57]. "L";
                                            }
                                            if(array_key_exists(70, $verbruiken))
                                            {
                                                echo " / ".$verbruiken[70]. "L";
                                            }else{
                                                echo " / ";
                                            }
                                            if(array_key_exists(71, $verbruiken))
                                            {
                                                echo " / ".$verbruiken[71]."L";
                                            }else{
                                                echo " / ";
                                            }
                                            echo "</label></li>";
                                        }
                                        /*** KILOMETERSTAND ***/
                                        $get_km_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=2"));
                                        $get_km_value = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_km_id->id." AND product_id=".$product->id);
                                        if(mysqli_num_rows($get_km_value) == 1)
                                        {
                                            $km_value = mysqli_fetch_object($get_km_value);
                                            echo "<li><label title='Kilometerstand' class='km_img'></label><label title='Kilometerstand'>".number_format($km_value->value, 0, ",", " ")."km</label></li>";
                                        }
                                    echo "</ul>";
                                echo "</div>";
                                /*** COMMENTAAR ***/
                                echo "<div class='veldenauto1'><span>";
                                $get_commentaar_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=60"));
                                $get_commentaar = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_commentaar_id->id." AND product_id=".$product->id);
                                if(mysqli_num_rows($get_commentaar) == 1)
                                {
                                    $commentaar_value = mysqli_fetch_object($get_commentaar);
                                    echo $commentaar_value->value;
                                }
                                echo "</span></div>"; 

                                echo "</div>";
                                echo "<div class='prijsauto'>";
                                            echo "<span style='margin:0 auto;font-weight:bold;font-size:18px;'>";
                                            $get_prijs_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE field='Prijs'"));
                                            $get_prijs = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_fields_id=".$get_prijs_id->id." AND product_id=".$product->id);
                                            if(mysqli_num_rows($get_prijs) == 1)
                                            {
                                                $prijs_value = mysqli_fetch_object($get_prijs);
                                                echo number_format($prijs_value->value, 0, ",", " ") . "&euro;";
                                            }
                                            echo "</span>";
                                            echo "<form method='post' id='frm_auto_".$product->id."'>";
                                            echo "<input class='meer_link' type='submit' value='Details' />";
                                            echo "<input type='hidden' name='product_id' id='product_id' value='" . $product->id . "' />";
                                            echo "</form>";
                                echo "</div>";
                                echo "</div>";
                    }
                    // paginatie
                    echo "<div class='pagination'>";
                    $waarde = ceil(($aantal_auto / $_POST['aantal']));
                    $i=1;
                    $max=$waarde+1;
                    $page = 1;
                    if(isset($_POST['pagina']) && !empty($_POST['pagina']))
                    {
                        $page = $_POST['pagina'];
                        switch($_POST['pagina'])
                        {
                            case 1:
                                if($waarde<5)
                                {
                                    $i=1;
                                    $max=$_POST['pagina']+$waarde;
                                }else{
                                    $i=1;
                                    $max=$_POST['pagina']+5;
                                }
                                break;
                            case 2:
                                if($waarde<5)
                                {
                                    $i=1;
                                    $max=$_POST['pagina']+$waarde-1;
                                }else{
                                    $i=1;
                                    $max=$_POST['pagina']+4;
                                }
                                break;
                            case 3:
                                if($waarde<5)
                                {
                                    $i=1;
                                    $max=$_POST['pagina']+$waarde-2;
                                }else{
                                    $i=1;
                                    $max=$_POST['pagina']+3;
                                }
                                break;
                            case 4:
                                if($waarde<5)
                                {
                                    $i=1;
                                    $max=$_POST['pagina']+$waarde-3;
                                }else{
                                    $i=1;
                                    $max=$_POST['pagina']+3;
                                }
                                break;
                            case $waarde-1:
                                $i=$waarde-4;
                                $max=$waarde+1;
                                break;
                            case $waarde:
                                $i=$waarde-4;
                                $max=$waarde+1;
                                break;
                            default:
                                $i=$_POST['pagina']-2;
                                $max = $_POST['pagina']+3;
                                break;
                        }
                    }
                    
                    
                    echo "<span>".$page." van ".$waarde."</span>";
                    echo "<span class='pagina'>Vorige</span>";
                    for($i;$i<$max;$i++)
                    {
                        if($page == $i)
                        {
                            echo "<span class='pagina active'>".$i."</span>";
                        }else{
                            echo "<span class='pagina'>".$i."</span>";
                        }
                    }
                    echo "<span class='pagina'>Volgende</span>";
                echo "</div>";
