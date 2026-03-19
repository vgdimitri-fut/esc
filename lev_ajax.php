<?php 

include "inc/db.php";

$sql = "SELECT naam as klant, id as cus_id 
        FROM kal_leveranciers WHERE naam LIKE '%". $_GET["q"] ."%'
        ORDER BY klant
        ";	

$rsd = mysqli_query($conn, $sql);

while($rs = mysqli_fetch_array($rsd)) {
	$cid = $rs['cus_id'];
	$cname = $rs['klant'];
	echo "$cname|$cid\n";
}

?>