<?php

include "../inc/db.php";
include "../inc/functions.php";


echo "<pre>";
var_dump($_POST);
echo "</pre>";

if(isset($_POST['image'])){
    echo "<br />image is posted.<br />";
}else{
    echo "<br />image is not posted.<br />";
}
?>