<?php
session_start();

include "../inc/db.php";
include "../inc/functions.php";
//$test = array();
//    echo "Script is begonnen ...";
//    $brands = mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE kapaza=5");
//    while($brand = mysqli_fetch_object($brands))
//    {    
//            $ch = curl_init();
//            $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0";
//            file_put_contents(realpath("../../tmp/cookieskapaza.txt"), ""); // clear cookie
//            $cookie_file = realpath("../../tmp/cookieskapaza.txt");
//            for($i=1970 ; $i<2014;$i++)
//            {
//                for($j=1;$j<7;$j++) // shape
//                {
//                    for($x=1; $x<3; $x++) // fuel
//                    {
//                        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
//                        curl_setopt($ch, CURLOPT_HTTPGET, true);
//                        curl_setopt($ch, CURLOPT_URL, "www2.kapaza.be/car_models.html?brand=".$brand->kapaza."&regdate=".$i."&car_shape=".$j."&car_fuel=".$x);
//                        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
//                        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
//                        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
//                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                        $html = curl_exec($ch);
//
//                        libxml_use_internal_errors(true);
//                        $DOM = new DOMDocument;
//                        $DOM->loadHTML($html);
//
//                        $items = $DOM->getElementsByTagName('option');
////                        echo "<pre>";
////                        echo $i . " - " . $j ." - " . $x ." : " .$items->length;
////                        echo "</pre>";
//                        if($items->length > 2)
//                        {
////                              mysqli_query($conn, "INSERT INTO tbl_product_brand_kapaza (brand_id,year,shape,fuel) VALUES (".$brand->kapaza.",".$i.",".$j.",".$x.")");
//                            $brand_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand_kapaza WHERE brand_id=".$brand->kapaza." AND year=".$i." AND shape=".$j." AND fuel=".$x));
//                            for ($l = 2; $l < $items->length; $l++)
//                            {
//                               if(!empty($items->item($l)->attributes))
//                               {
//                                    $val = $items->item($l)->getAttribute('value');
////                                    echo "<pre>";
////                                    echo "<b>". $items->item($l)->nodeValue . " - " . $val ." -> gevonden waarde</b><br />";
////                                    echo "</pre>";
//                                    
//                                    $kapaza_name = $items->item($l)->nodeValue; // kapaza name
//                                    $kapaza_name_stripped = strtolower(preg_replace('/\s+/', '', $kapaza_name)); // kapaza name stripped
//                                    
//                                    $get_brand_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE kapaza=".$brand_id->brand_id)); // get brand id
//                                    
//                                    $get_all_models = mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE merk_id=".$get_brand_id->id); // get all models with that id
//                                    $o = 0;
//                                    while($model = mysqli_fetch_object($get_all_models))
//                                    {
//                                        $model_name_stripped = strtolower(preg_replace('/\s+/', '', $model->naam)); // model name stripped
//                                        if($kapaza_name_stripped == $model_name_stripped)
//                                        {
//                                            $o++;
////                                            mysqli_query($conn, "INSERT INTO tbl_product_model_kapaza (brand_kapaza_id,model_id,kapaza_id,name) VALUES (".$brand_id->id.",".$model->id.",".$val.",'".$items->item($l)->nodeValue."')");
//                                        }
//                                    }
//                                    if($o == 0)
//                                    {
//                                        $get_all_models = mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE merk_id=".$get_brand_id->id); // get all models with that id
//                                        while($model = mysqli_fetch_object($get_all_models))
//                                        {
////                                            echo $kapaza_name;
//                                            if($kapaza_name == '4')
//                                            {
//                                                echo "<pre>";
//                                                echo $i ." - " . $j ." - " . $x. " - " . strtolower( substr( $kapaza_name, 0, 3 ) ) . " - " . strtolower(substr($model->naam,0,4));
//                                                echo "</pre>";
//                                                if((strtolower( substr( $kapaza_name, 0, 1 ) )) == (strtolower(substr($model->naam,0,1))))
//                                                {
//                                                    echo "<pre>";
//                                                    echo "<i>" . $kapaza_name ." - ". $model->naam ." is gepost.</i>";
//                                                    echo "</pre>";
//                                                    mysqli_query($conn, "INSERT INTO tbl_product_model_kapaza (brand_kapaza_id,model_id,kapaza_id,name) VALUES (".$brand_id->id.",".$model->id.",".$val.",'".$items->item($l)->nodeValue."')");
//                                                } 
//                                            }
//                                            
//                                        }
//                                    }
//                               }
//                            }
//                        }
//                    }
//                }
//            }
//    }
//    
//
//    
//    
//
////}
//
//
//die();
if(isset($_POST['product_id']))
{
    foreach($_POST['product_id'] as $productid)
    {
            $ch = curl_init();
            $auto = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$productid));
            $name = substr($auto->name,0,46);
            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$name."',".$productid.",'0',2)");
            // initialisatie
            $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0";

            $baseurl = "http://www2.kapaza.be/nl/ai";
            $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
            $logindata = array('username' => $user->kapaza_user,
                                    'passwd' => $user->kapaza_pwd);

            file_put_contents(realpath("../../tmp/cookieskapaza.txt"), ""); // clear cookie
            $cookie_file = realpath("../../tmp/cookieskapaza.txt");

            // GET KAPAZA PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, "http://www2.kapaza.be/nl/");
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'rw+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, "http://www2.kapaza.be/nl/store/init/0?request_state=login");
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);

            $url_redirect = htmlspecialchars($verboseLog);
            echo $url_redirect;
            $pos = strrpos($url_redirect,'Location: ') + strlen('Location: '); // begin
            $pos2 = strpos(substr($url_redirect, $pos), '?'); // einde
            $e = substr($url_redirect,$pos,$pos2);
            $a = substr($e,0);
            echo "<pre>" . $a  ."</pre>";
            
            // GET LOGIN PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, $a);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            
            // POST LOGIN PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, "http://www2.kapaza.be/nl/store/verify_login/0");
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            $verbose = fopen('php://temp', 'rw+');
//            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'rw+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $logindata);
            curl_exec($ch);
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);

            $url_redirect = htmlspecialchars($verboseLog);
            echo $url_redirect;
            $pos = strrpos($url_redirect,'Location: ') + strlen('Location: '); // begin
            $pos2 = strpos(substr($url_redirect, $pos), '?'); // einde
            $e = substr($url_redirect,$pos,$pos2);
            $a = substr($e,0);
            echo "<pre>" . $a  ."</pre>";
            
            // GET LOGIN PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, $a);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'rw+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);

            $url_redirect = htmlspecialchars($verboseLog);
            echo $url_redirect;
            $pos = strrpos($url_redirect,'Location: ') + strlen('Location: '); // begin
            $pos2 = strpos(substr($url_redirect, $pos), '?'); // einde
            $e = substr($url_redirect,$pos,$pos2);
            $a = substr($e,0);
            echo "<pre>" . $a  ."</pre>";
            
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, $a);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $html = curl_exec($ch);
            echo $html;
            
            if(!empty($html))
            {
                libxml_use_internal_errors(true);
                $DOM = new DOMDocument;
                $DOM->loadHTML($html);
                $items = $DOM->getElementsByTagName('a');
                 for ($i = 0; $i < $items->length; $i++)
                 {
                     if($items->item($i)->nodeValue == $name) // check if name exist
                     {
                        if(!empty($items->item($i)->attributes))
                        {
                            $val = $items->item($i)->getAttribute('href');
                            $pos = strpos($val,'.htm') ; // begin
                            $pos2 = strrpos($val, '-',-4) + 1; // einde
                            $id_ext = substr($val,$pos2,$pos); // get
                            $id = substr($id_ext,0,-4);
                            
                        }
                     }
                 }
            }else{
                echo "html is leeg";
            }
            
            // GET LOGIN PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_URL, "http://www2.kapaza.be/nl/store/delete_confirm/0?id=".$id);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            echo curl_exec($ch);
            
            
            $deletedata = array('user_delete_reason' => '2');
            // POST LOGIN PAGE
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, "http://www2.kapaza.be/nl/store/delete_ads/0");
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deletedata);
            echo curl_exec($ch);
                        
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);

            echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
    }
}
