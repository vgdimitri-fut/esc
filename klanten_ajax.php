<?php 

include "inc/db.php";

$_GET["q"] = htmlentities($_GET["q"], ENT_QUOTES, "UTF-8");
/*
if( isset( $_GET["onder"] ) && $_GET["onder"] == 1 )
{
	$sql = "SELECT cus_naam as klant, kal_customers.cus_id 
	        FROM kal_customers
            LEFT JOIN kal_customer_boiler ON ( kal_customers.cus_id = kal_customer_boiler.cus_id ) 
            WHERE cus_active='1' AND cus_naam LIKE '%". $_GET["q"] ."%' AND (kal_customers.cus_verkoop = '1' OR kal_customers.cus_verkoop = '2' OR kal_customer_boiler.cus_verkoop = '1')
            UNION
	        SELECT cus_bedrijf as klant, kal_customers.cus_id 
	        FROM kal_customers
            LEFT JOIN kal_customer_boiler ON ( kal_customers.cus_id = kal_customer_boiler.cus_id ) 
            WHERE cus_active='1' AND cus_bedrijf LIKE '%". $_GET["q"] ."%' AND (kal_customers.cus_verkoop = '1' OR kal_customers.cus_verkoop = '2' OR kal_customer_boiler.cus_verkoop = '1')
            ORDER BY klant
	        ";
 //echo $sql;
}else
{*/
	$sql = "SELECT cus_naam as klant, cus_id 
	        FROM kal_customers WHERE cus_active='1' AND 
	        cus_naam LIKE '%". $_GET["q"] ."%'
            UNION
	        SELECT cus_bedrijf as klant, cus_id 
	        FROM kal_customers WHERE cus_active='1' AND 
	        cus_bedrijf LIKE '%". $_GET["q"] ."%'
            ORDER BY klant
	        ";	
//}

//echo $sql;

//$sql = "SELECT CONCAT(cus_naam + ', ' + cus_bedrijf) as klant, cus_id FROM kal_customers WHERE klant LIKE '%". $_GET["q"] ."%'";

$rsd = mysqli_query($conn, $sql);

while($rs = mysqli_fetch_array($rsd)) {
	$cid = $rs['cus_id'];
    $cname = html_entity_decode($rs['klant'], ENT_QUOTES, "UTF-8");
	echo "$cname|$cid\n";
}

?>