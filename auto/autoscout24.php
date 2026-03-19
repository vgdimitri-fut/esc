<?php
include "../inc/db.php";
include "../inc/functions.php";

$productid = $_POST['id'];
if($_POST['action'] == 'add')
{
    mysqli_query($conn, "UPDATE tbl_products SET autoscout24='1' WHERE id=".$productid);
}else{
    mysqli_query($conn, "UPDATE tbl_products SET autoscout24='0' WHERE id=".$productid);
}

// GET EVERYTHING
echo "SELECT * FROM tbl_products WHERE id=".$productid ." <br />";
$q_product = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$productid));
echo "SELECT * FROM tbl_product_brand WHERE id=".$q_product->product_brand_id ." <br />";
$q_brand = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$q_product->product_brand_id));
$q_model = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$q_product->product_model_id));
$oud_brand_id = $q_brand->id;
$brand = $q_brand->naam;
$oud_model_id = $q_model->id;
$model= $q_model->naam;
echo "Model: " . $model . " -  Merk: " . $brand;


// GET EVERYTHING
include('autoscout24Data.php');
// DATA
//echo "data";
//echo "<pre>";
//print_r($data);
//echo "</pre>";
//echo "fuel";
//echo "<pre>";
//print_r($consumption);
//echo "</pre>";
//echo "opties";
//echo "<pre>";
//print_r($opties);
//echo "</pre>";
//echo "price";
//echo "<pre>";
//print_r($price);
//echo "</pre>";
//echo "emission";
//echo "<pre>";
//print_r($emission);
//echo "</pre>";
//if(empty($emission))
//{
//    echo "emission is leeg";
//}

/******************* ADD XML NODE *********************/

if(isset($_POST['id']) && $_POST['action'] == 'add')
{
    echo $_POST['action'];
    $file = "../xml/autoscout24.xml";
    $fp = fopen($file, "rb") or die("cannot open file");
    $str = fread($fp, filesize($file));

    $xml = new DOMDocument();
    $xml->formatOutput = true;
    $xml->preserveWhiteSpace = false;
    $xml->loadXML($str) or die("Error");

    // original
    //echo "<xmp>OLD:\n". $xml->saveXML() ."</xmp>";

    // get document element
    $root = $xml->documentElement;
    $fnode = $root->firstChild;
    $snode = $fnode->firstChild;

    //add a node
    $ori = $snode->childNodes->item(0);

    // create media
    $media = $xml->createElement("media");
    $images = $xml->createElement('images');
    // loop all photos
    $getAllPhotos = mysqli_query($conn, "SELECT cf_file FROM kal_customers_files WHERE cf_soort_id=".$productid);
    $q_hoofdfoto = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=".$productid." AND product_fields_id=75");
    $i=0;
    $link_hoofdfoto = '';
    
    if(mysqli_num_rows($q_hoofdfoto) != 0){
        $hoofdfoto = mysqli_fetch_object($q_hoofdfoto);
        $link_hoofdfoto = $hoofdfoto->value;
        $image = $xml->createElement('image');
        $uri = $xml->createElement('uri', "http://www.carengineering.be/images/uploads/products/".$productid."/".$link_hoofdfoto);
        $image->appendChild($uri);
        $images->appendChild($image);
        $media->appendChild($images);
        $i++;
    }
    
    while($link = mysqli_fetch_object($getAllPhotos))
    {
        if($link_hoofdfoto != $link->cf_file){
            if($i < 15)
            {
               // create Photo
                $image = $xml->createElement('image');
                $uri = $xml->createElement('uri', "http://www.carengineering.be/images/uploads/products/".$productid."/".$link->cf_file);
                $image->appendChild($uri);
            }
            $images->appendChild($image);
            $media->appendChild($images);
            $i++;
        }
    }
    /* loop consumption if not empty*/
    if(!empty($consumption))
    {
            $xml_consumption = $xml->createElement('consumption');
            $xml_liquid = $xml->createElement('liquid');
            $fuel_type = $xml->createElement('fuel_type',$data['fuel_type']);
            $xml_liquid->appendChild($fuel_type);
            foreach($consumption as $index => $value)
            {
                $xml_txt = $xml->createElement($index,$value);
                $xml_liquid->appendChild($xml_txt);
            }
            $xml_consumption->appendChild($xml_liquid);
    }
    /* loop opties */
    if(!empty($opties))
    {
        $xml_opties = $xml->createElement('equipments');
        foreach($opties['text'] as $value){
            $xml_equipment = $xml->createElement('equipment');
            $xml_optie = $xml->createElement('text',$value); 
            $xml_equipment->appendChild($xml_optie);
            $xml_opties->appendChild($xml_equipment);
        }
    }
    
    /* loop price */
    $xml_prices = $xml->createElement("prices");
    $xml_price = $xml->createElement("price");
    foreach($price as $index => $value)
    {
            $xml_txt = $xml->createElement($index,$value);
            $xml_price->appendChild($xml_txt);
    }
    $xml_prices->appendChild($xml_price);
    /* loop emission if not empty*/
    if(!empty($emission))
    {
        $xml_emission = $xml->createElement("emission");
        foreach($emission as $index => $value)
        {
                $xml_txt = $xml->createElement($index,$value);
                $xml_emission->appendChild($xml_txt);
        }    
    }
    /* START APPENDING TO XML */
    $classified = $xml->createElement("vehicle");
    // single elements
    foreach($data as $index => $value)
    {
        if($index == 'notes'){
            $text = $xml->createElement($index);
            $index = $xml->createElement('text',$value);
            $text->appendChild($index);
            $classified->appendChild($text);
        }else{
            $index = $xml->createElement($index,$value);
            $classified->appendChild($index);
        }
        
    }     
    $classified->appendChild($media);
    $classified->appendChild($xml_prices);
    $classified->appendChild($xml_opties);
//    if(!empty($opties))
//    {
//        $classified->appendChild($xml_opties);
//    }
    if(!empty($consumption))
    {
        $classified->appendChild($xml_consumption);
    }
    if(!empty($emission))
    {
         $classified->appendChild($xml_emission);
    }
    /* insert row at top */
    $snode->insertBefore($classified,$ori);

    //echo "<xmp>NEW:\n". $xml->saveXML() ."</xmp>";
    $xml->save("../xml/autoscout24.xml") or die("Error");
}

/******************* DELETE XML NODE *********************/
if(isset($_POST['action']) && $_POST['action'] == 'delete')
{
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $doc->Load('../xml/autoscout24.xml');
        $to_remove = array();
         
           foreach ($doc->getElementsByTagName('vehicles') as $tagcourses)
            {
               foreach ( $tagcourses ->getElementsByTagName('vehicle') as $tagcourse)
               {
                   foreach($tagcourse->getElementsByTagName('ownersvehicle_id') as $id)
                   {
                       if($id->nodeValue == $productid)
                       {
                           $to_remove[] = $tagcourse;
                       }
                   }
               }
            }
            // Remove the nodes stored in your array
            // by removing it from its parent
            foreach ($to_remove as $node)
            {
               $node->parentNode->removeChild($node);
            }
        $doc->Save('../xml/autoscout24.xml');
}