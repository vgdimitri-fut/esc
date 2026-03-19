<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";

if(isset($_POST['product_id']))
{
    foreach($_POST['product_id'] as $productid)
    {
        $auto = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_products WHERE id=".$productid));
        $name = $auto->name;
        mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$name."',".$productid.",'0',1)");

        $ch = curl_init();

        // initialisatie
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0";

        $baseurl = "http://www.2dehands.be/login.html?doel=%2Fbeheer";
        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
        $logindata = array('email' => $user->twee_user,
                                'password' => $user->twee_pwd,
                                'stuur' => '1',
                                'submit' => 'Inloggen');

        $cookie_file = realpath("../../tmp/cookies2dehands.txt");

        // LOGIN
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_URL, $baseurl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //$verbose = fopen('php://temp', 'rw+');
        //curl_setopt($ch, CURLOPT_STDERR, $verbose);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $logindata);
        //curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_exec($ch);

        // CONTROL PANEL
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_URL, 'http://www.2dehands.be/beheer');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        echo $html;

        // GET CHECKBOX ID
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // hide xml warnings
        if($doc->loadHTML($html) === false)
        {
            echo "Het is niet gelukt.";
        }
        $id = $doc->getElementById('zoekertjes-found');
        echo $doc->saveHTML();
        foreach($id->childNodes as $node){ // loop auto's
            $test = explode( '-', $node->nodeValue );
            $id = trim($test[0]);
            if(strstr($node->nodeValue, strtolower($name)) || strstr($node->nodeValue,$name))
            {

                // GET delete pagina
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_URL, 'http://www.2dehands.be/beheer/'.$id.'/verwijder');
                echo curl_exec($ch);

                // DELETE
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_URL, 'http://www.2dehands.be/beheer/'.$id.'/verwijder');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "submit=Ja, verwijder het zoekertje");
                echo curl_exec($ch);
            }
        }
    }
}