<?php
session_start();

include "../inc/db.php";
include "../inc/functions.php";


$productid = $_POST['id'];
if($_POST['action'] == 'add')
{
    mysqli_query($conn, "UPDATE tbl_products SET autovlan='1' WHERE id=".$productid);
}else{
    mysqli_query($conn, "UPDATE tbl_products SET autovlan='0' WHERE id=".$productid);
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

// CHECK BRAND
if($q_brand->autovlan_id == 0)
{
    $xml=simplexml_load_file("http://auto.vlan.be/xml/xmlservice.asp?getcarmakes=1&merkid=194&site=auto&language=&encoding=utf");
    foreach($xml->children()->children() as $index => $child)
    {
        if($brand == $child['Merk'])
        {
            mysqli_query($conn, "UPDATE tbl_product_brand SET autovlan_id=".$child['Id']." WHERE id=".$oud_brand_id);
            $brandid = $child['Id'];
            $brandname = $child['Merk'];
            break;
        }else{
            // overig
            $brandid = 260;
            $brandname = 'Andere - Autres - Others';
        }
    }
}else{
    $brandid = $q_brand->autovlan_id;
    $brandname = $brand;
}

// CHECK MODEL
if($q_model->autovlan_id == 0)
{
    $xml=simplexml_load_file("http://auto.vlan.be/xml/xmlservice.asp?getmodels=1&merkid=".$brandid."&site=auto&language=&encoding=utf");
    foreach($xml->children()->children() as $index => $child)
    {
        echo $child['Model']. " <br />";
        if($model == $child['Model'])
        {
            mysqli_query($conn, "UPDATE tbl_product_model SET autovlan_id=".$child['Id']." WHERE id=".$oud_model_id);
            $modelid = $child['Id'];
            $modelname = $child['Model'];
            break;
        }else{
            $modelid='';
            $modelname='';
        }
    }
}else{
    $modelid = $q_model->autovlan_id;
    $modelname = $model;
}

echo $brandid . " - " . $brandname . " / " . $modelid . " - " .$modelname;

// GET EVERYTHING
include('autovlanData.php');
// DATA
//echo "<pre>";
//print_r($data);
//echo "</pre>";


/******************* ADD XML NODE *********************/

if(isset($_POST['id']) && $_POST['action'] == 'add')
{
    echo $_POST['action'];
    $file = "../xml/autovlan.xml";
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

    //add a node
    $ori = $fnode->childNodes->item(2);

    // create PhotoCollection
    $PhotoCollection = $xml->createElement("PhotoCollection");

    // loop all photos
    $getAllPhotos = mysqli_query($conn, "SELECT cf_file FROM kal_customers_files WHERE cf_soort_id=".$productid);
    $aantalFotos = mysqli_num_rows($getAllPhotos);
    if($aantalFotos > 15)
    {
        $aantalFotos = 15;
    }
    $i=0;
    while($link = mysqli_fetch_object($getAllPhotos))
    {
        if($i < 15)
        {
           // create Photo
            $Photo = $xml->createElement("Photo");
            $PhotoFileName = $xml->createAttribute('FileName');
            $PhotoFileName->value = "http://www.carengineering.be/images/uploads/products/".$productid."/".$link->cf_file;
            $PhotoFileIncluded = $xml->createAttribute('FileIncluded');
            $PhotoFileIncluded->value = 'False';
            $Photo->appendChild($PhotoFileName);
            $Photo->appendChild($PhotoFileIncluded);
            $PhotoCollection->appendChild($Photo); 
        }
        $i++;
    }
    $photo = $xml->createAttribute('Aantalfotos');
    $photo->value = $aantalFotos;
    
    
    // append to XML
    $classified = $xml->createElement("Classified");
    $classified->appendChild($PhotoCollection);
    
    
    // add attributes classified
    foreach($data as $index => $value)
    {
        $index = $xml->createAttribute($index);
        $index->value = $value;
        $classified->appendChild($index);
    }    
    $classified->appendChild($photo);    
    
    $fnode->insertBefore($classified,$ori);

    //echo "<xmp>NEW:\n". $xml->saveXML() ."</xmp>";
    $xml->save("../xml/autovlan.xml") or die("Error");
}

/******************* DELETE XML NODE *********************/
if(isset($_POST['action']) && $_POST['action'] == 'delete')
{
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $doc->Load('../xml/autovlan.xml');
        $to_remove = array();

            foreach ($doc->getElementsByTagName('ClassifiedCollection') as $tagcourses)
            {
               foreach ( $tagcourses ->getElementsByTagName('Classified') as $tagcourse)
               {
                 if(($tagcourse->getAttribute('MerkId')) == $brandid && ($tagcourse->getAttribute('ModelId') == $modelid)){

                     $to_remove[] = $tagcourse;
                 }
               }
            }
            // Remove the nodes stored in your array
            // by removing it from its parent
            foreach ($to_remove as $node)
            {
               $node->parentNode->removeChild($node);
            }
        $doc->Save('../xml/autovlan.xml');
}