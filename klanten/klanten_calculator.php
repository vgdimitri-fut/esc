<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";

// include CURL
require_once("../inc/Curl_HTTP_Client.php");
$curl = new Curl_HTTP_Client();

$ibanreknr = '';
if(preg_match('/^[a-z]/i', $_POST["iban"]))
    {
        $ibanreknr = '1';
    }
// als er een rekening nummer is verzonden
if(isset($_POST['iban']))
    {
        // SET user CURL
        $useragent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)";
        $curl->set_user_agent($useragent);
        $baseurl = "http://www.ibanbic.be";
        // Cookies momenteel onnodig
        //$cookies_file = "/tmp/cookies". time() .".txt";
        //$curl->store_cookies($cookies_file);

        //Uncomment next line if you want to set credentials
        //$curl->set_credentials($user1, $pass1);
        $html1 = $curl->fetch_url( $baseurl . "/default.aspx", null, 15);
        
        //<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="cQE8PsVBB40ePlSP9AM0Mw+nio+LfjrzYLjWAACTSLD8xB/9ECz7FRsW6wBIEbEMepajGFKF/MW3r7srMW+NELj+JDsEClGdKzkxZFN2X9b+hgWB" />
        //<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="77xjIirgCYAv9hbG0+IJM4Vtwth6dari29T7fdSdvWV0nX8YzOdh2ZYFFBWy6QjH71L7xBaE31OTCZLQcHZyuUYiHkn/V262txOkA8hJrTEexkTwxqn7KKH2LD8uiYIbZdDKhw==" />
        
        $pos = strpos($html1,'id="__VIEWSTATE" value="') + strlen('id="__VIEWSTATE" value="'); // begin
        $pos2 = strpos(substr($html1, $pos), '"'); // einde
        $viewstate = substr($html1,$pos,$pos2); // get
        
        $_pos = strpos($html1,'id="__EVENTVALIDATION" value="') + strlen('id="__EVENTVALIDATION" value="'); // begin
        $_pos2 = strpos(substr($html1, $_pos), '"'); // einde
        $validation = substr($html1,$_pos,$_pos2); // get
        
        $post_data = array('__LASTFOCUS' => "",
                               '__VIEWSTATE' => $viewstate,
                               '__EVENTARGUMENT' => "",
                               '__VIEWSTATEENCRYPTED' => "",
                               '__EVENTVALIDATION' => $validation,
                               '__EVENTTARGET' => "",
                               'textboxBBAN' => $_POST['iban'],
                               'Convert' => "Convert Number");
        //and send request to $url.
        $check_login = $curl->send_post_data( $baseurl, $post_data,null,30);
        
        // Neem BIC,IBAN EN BANKNUMMER UIT CHECK_LOGIN
        // BIC nummer
        $result = strpos($check_login, 'name="textboxBIC"') + strlen('name="textboxBIC" type="text" value="');
        $result2 = strpos( substr($check_login, $result), '"' );
        $bic = substr($check_login, $result, $result2);
        
        // IBAN
        $resultiban = strpos($check_login, 'name="textboxIBAN"') + strlen('name="textboxIBAN" type="text" value="');
        $resultiban2 = strpos( substr($check_login, $resultiban), '"' );
        $iban = substr($check_login, $resultiban, $resultiban2);
        
        // BANKNUMMER
        $resultbank = strpos($check_login, 'name="textboxBankName"') + strlen('name="textboxBankName" type="text" value="');
        $resultbank2 = strpos( substr($check_login, $resultbank), '"' );
        $bank = substr($check_login, $resultbank, $resultbank2);
        
        $var = $bank . "," . $bic;
        $var .= "," .$iban;
        if($ibanreknr != '1')
            {
                $var .= "," . $_POST["iban"];
            }
        echo $var;
    }
?>