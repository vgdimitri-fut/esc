<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";

if(isset($_POST['id']))
{
    $productid = $_POST['id'];
    if($_POST['action'] == 'add')
    {
        mysqli_query($conn, "UPDATE tbl_products SET kapaza='1' WHERE id=".$productid);
    }else{
        mysqli_query($conn, "UPDATE tbl_products SET kapaza='0' WHERE id=".$productid);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'upload') {
    $q_product = "SELECT * FROM tbl_products WHERE";
    $i = 0;
    foreach ($_POST['ids'] as $id) {
        if ($i == 0) {
            $q_product .= " id=" . $id;
        } else {
            $q_product .= " OR id=" . $id;
        }
        $i++;
    }
    $q_all = mysqli_query($conn, $q_product);
    while ($auto = mysqli_fetch_object($q_all)) {
        $ch = curl_init();

        // initialisatie
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:26.0) Gecko/20100101 Firefox/26.0";

        $baseurl = "http://m.kapaza.be/nl/mai/account";
        $user = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
        $logindata = array('email' => $user->kapaza_user,
            'pass' => $user->kapaza_pwd,
            'lang' => 'nl');

        file_put_contents(realpath("../../tmp/cookieskapaza.txt"), ""); // clear cookie
        $cookie_file = realpath("../../tmp/cookieskapaza.txt");

        unset($data);
        // LOGIN ACCOUNT
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_URL, $baseurl);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mai/myads');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $logindata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $base = curl_exec($ch);
        echo $base;
        
        $waarde = strpos($base,'token')+14;
        $value1 = substr($base, $waarde);
        $waarde2 = strpos($value1,'"');
        $token = substr($base,$waarde,$waarde2);
        echo "Account token = ".$token;
        echo "<br /><br />";
        
        // FORM LOAD + GET TOKEN OF FORM
        $load_url = "http://m.kapaza.be/nl/mai/form?token=".$token;
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $load_url);
        $c = curl_exec($ch);
        echo $c;
        
        // GET TOKEN
        $waarde_f = strpos($c,'token')+25;
        $value1_f = substr($c, $waarde_f);        
        $waarde2_f = strpos($value1_f,'"');
        $tokenform = substr($c,$waarde_f,$waarde2_f);
        echo "Form token = ".$tokenform . "<br /><br />";
        
        // POST GET CATEGORY PARAMS
        $gcp_url = 'http://m.kapaza.be/nl/get_category_params';
        

        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mai/form?token='.$tokenform);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $gcp_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'cat=1020&type=s');
        curl_setopt($ch, CURLOPT_POST, true);
        $e = curl_exec($ch);
        echo "POST GCP <br />".$e ." <br /><br />";;
        
        // GET FORM
        $get_form = 'http://m.kapaza.be/nl/mpu/form';
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mai/form?token='.$tokenform);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $get_form);
        $f = curl_exec($ch);
        echo "GET FORM <br />".$f ." <br /><br />";
        
        // FORM IMAGES
        $images_url = 'http://m.kapaza.be/nl/mpu/upload';
        $images_arr = array();
        $first_photo = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=".$auto->id." AND product_fields_id=75");
            if(mysqli_num_rows($first_photo) != 0){
                $f_photo = mysqli_fetch_object($first_photo);
                $hoofdfoto = $f_photo->value;
                $images = array('image' => "@".realpath("../../images/uploads/products/".$auto->id."/".$hoofdfoto),
                'token' => $tokenform);
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_URL, $images_url);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mpu/form?token='.$tokenform);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $images);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $h = curl_exec($ch);
                $pos1 = strpos($h,'form/?') + strlen('form/?');
                $pos2 = strpos(substr($h,$pos1),'&');
                $waarde = substr($h,$pos1,$pos2);
                $images_arr[] = $waarde;
            }
        $q_photos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$auto->id." LIMIT 7");
        while($image = mysqli_fetch_object($q_photos))
        {            
            if($image->cf_file != $hoofdfoto){
                $images = array('image' => "@".realpath("../../images/uploads/products/".$auto->id."/".$image->cf_file),
                'token' => $tokenform);
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_URL, $images_url);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
                curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mpu/form?token='.$tokenform);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $images);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $h = curl_exec($ch);
                $pos1 = strpos($h,'form/?') + strlen('form/?');
                $pos2 = strpos(substr($h,$pos1),'&');
                $waarde = substr($h,$pos1,$pos2);
                $images_arr[] = $waarde;
            }
        }
        echo "<pre>";
        echo "<b>".$i."</b>";
        var_dump($images_arr);
        echo "</pre>";
            
        $add_opties = '';
        $opties2 = array();
        $instellingen = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
        // fill in form
        $data['zipcode'] = $instellingen->bedrijf_postcode;
        $data['lang'] = 'nl';
        $data['category'] = '1020';
        $data['type'] = 's';
        $data['name'] = $instellingen->bedrijf_naam;
        $data['email'] = $user->kapaza_user;
        $data['account_passwd'] = $user->kapaza_pwd;
        $data['phone'] = $instellingen->bedrijf_tel;
        $data['phone_hidden'] = '0';
        $data['chassisnumber'] = '';
        $data['subject'] = substr($auto->name,0,46);
        // loop fields
        $q_field_values = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=" . $auto->id);
        while ($value = mysqli_fetch_object($q_field_values)) {
            $q_choice = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id='" . $value->value . "' AND product_fields_id=" . $value->product_fields_id);
            if (mysqli_num_rows($q_choice) == 0) {
                //echo $q_field->field." heeft <strong>geen</strong> keuze. <br />";
                switch ($value->product_fields_id) {
                    case 58:
                        $date = explode('-', $value->value);
                        $data['regdate'] = $date[2];
                        $data['month'] = (int)$date[1];
                        break;
                    case 2: // kilometerstand
                        $data['mileage'] = $value->value;
                        break;
                    case 7:
                        $data['power'] = $value->value;
                        break;
                    case 57:
                        $data['avgconsumption'] = $value->value;
                        break;
                    case 72:
                        $data['co2emit'] = $value->value;
                        break;
                    case 56:
                        $data['price'] = $value->value;
                        break;
                    case 60: // beschrijving
                        $data['body'] = $value->value;
                        break;
                    case 76: // chassisnummer
                        $data['chassisnumber'] = $value->value;
                        break;
                    case 77: // cylinders
                        $data['ncylinders'] = $value->value;
                        break;
                    case 78: // versnellingen
                        $data['ngears'] = $value->value;
                        break;
                    case 79: // zitplaatsen
                        $data['nsits'] = $value->value;
                        break;
                }
            } else {
                //echo $q_field->field." heeft keuze. <br />";
                $choice = mysqli_fetch_object($q_choice);
                switch ($value->product_fields_id) {
                    case 1:
                        switch ($choice->choice) { // 00100003 -> diesel, 00100001 -> benzine, 00100006 -> lpg
                            case 'Benzine':
                                $data['fuel'] = '1';
                                break;
                            case 'Diesel':
                                $data['fuel'] = '2';
                                break;
                            case 'LPG':
                                $data['fuel'] = '3';
                        }
                        break;
                    case 55: // carrosserie
                        switch ($choice->id) {
                            case 29: // suv
                                $data['car_shape'] = '6';
                                $data['ndoors'] = '4';
                                break;
                            case 30: // coupe
                                $data['car_shape'] = '4';
                                $data['ndoors'] = '2';
                                break;
                            case 35: // berline
                                $data['car_shape'] = '2';
                                $data['ndoors'] = '4';
                                break;
                            case 36: // sedan
                                $data['car_shape'] = '2';
                                $data['ndoors'] = '4';
                                break;
                            case 40: // Stationwagon
                                $data['car_shape'] = '3';
                                $data['ndoors'] = '4';
                                break;
                            case 41: // hatchback
                                $data['car_shape'] = '2';
                                $data['ndoors'] = '2';
                                break;
                            case 42: // crossauto
                                $data['car_shape'] = '6';
                                $data['ndoors'] = '4';
                                break;
                            case 43: // camper
                                $data['car_shape'] = '7';
                                break;
                            case 44: // cabriolet
                                $data['car_shape'] = '5';
                                $data['ndoors'] = '2';
                                break;
                            case 45: // bestelbus
                                $data['car_shape'] = '7';
                                break;
                            case 46: // schadeauto
                                $data['car_shape'] = '2';
                                break;
                            case 47: // overig
                                $data['car_shape'] = '2';
                                $data['ndoors'] = '4';
                                break;
                        }
                    case 59: // schakeling
                        switch ($choice->id) {
                            case 32:
                                $data['gearbox'] = '1';
                                break;
                            case 33:
                                $data['gearbox'] = '2';
                                break;
                        }
                        break;
                    case 69: // opties
                        switch ($choice->id) {
                            case 48: //abs
                                $opties['ABS'] = '1';
                                $opties2[] = 'abs';
                                break;
                            case 49: //alarm
                                $opties['alarm'] = '1';
                                $opties2[] = 'alarm';
                                break;
                            case 51: //airco
                                $opties['airconditioning'] = '1';
                                $opties2[] = 'airco';
                                break;
                            case 52: //centrale vergrendeling
                                $opties['centrallock'] = '1';
                                $opties2[] = 'centrale vergrendeling';
                                break;
                            case 53: //elektrische ruiten
                                $opties['powerwindows'] = '1';
                                $opties2[] = 'elektrische ruiten';
                                break;
                            case 54: //cruise control
                                $opties['cruisecontrol'] = '1';
                                $opties2[] = 'cruise control';
                                break;
                            case 55: //leder
                                $opties['leather'] = '1';
                                $opties2[] = 'leder';
                                break;
                            case 56: //schuifdak
                                $opties['slidingroof'] = '1';
                                $opties2[] = 'schuifdak';
                                break;
                            case 57: //stuurbekrachtiging
                                $opties['steering'] = '1';
                                $opties2[] = 'stuurbekrachtiging';
                                break;
                            case 58: //navigatiesysteem
                                $opties['navigationsystem'] = '1';
                                $opties2[] = 'navigatiesysteem';
                                break;
                            case 60: //Xenon lichten
                                $opties['xenonheadlights'] = '1';
                                $opties2[] = 'Xenon lichten';
                                break;
                            case 62: //esp
                                $opties['autostabilitycontrol'] = '1';
                                $opties['tractioncontrol'] = '1';
                                $opties2[] = 'ESP';
                                break;
                            case 63: //Parkeerhulpsysteem (PDS)
                                $opties['parkdistancecontrol'] = '1';
                                $opties2[] = 'Parkeerhulpsysteem (PDS)';
                                break;
                            case 85: // Airbag
                                $opties['airbag'] = '1';
                                $opties2[] = 'Airbag';
                                break;
                            case 87: // Neerklapbare achterbank
                                $opties['foldingbench'] = '1';
                                $opties2[] = 'Neerklapbare achterbank';
                                break;
                            case 88: // Soundsysteem
                                $opties['cddvdreader'] = '1';
                                $opties2[] = 'cd/dvd';
                                break;
                            case 89: // Vierwielaandrijving
                                $opties['allterraintraction'] = '1';
                                $opties2[] = 'Vierwielaandrijving';
                                break;
                            case 90: // Getinte ruiten
                                $opties['stainedglass'] = '1';
                                $opties2[] = 'Getinte ruiten';
                                break;
                            case 91: // Mistlampen
                                $opties['fogheadlights'] = '1';
                                $opties2[] = 'Mistlampen';
                                break;
                            case 92: // Trekhaak
                                $opties['trailerhitch'] = '1';
                                $opties2[] = 'Trekhaak';
                                break;
                            case 93: // Licht metalen velgen
                                $opties['aluminumrims'] = '1';
                                $opties2[] = 'Licht metalen velgen';
                                break;
                            case 94: // Elektrische spiegels
                                $opties['electricmirrors'] = '1';
                                $opties2[] = 'Elektrische spiegels';
                                break;
                            case 96: // Sportzetels
                                $opties2[] = 'Sportzetels';
                                break;
                            case 97: // Dakdragers
                                $opties2[] = 'Dakdragers';
                                break;
                            case 98: // Volledige service geschiedenis
                                $opties2[] = 'Volledige service geschiedenis';
                                break;
                            case 99: // Startonderbreker
                                $opties2[] = 'Startonderbreker';
                                break;
                            case 100: // extra vermarming
                                $opties2[] = 'extra vermarming';
                                break;
                            case 101: // Adaptive light
                                $opties2[] = 'Adaptive light';
                                break;
                            case 102: // Klimaatregeling
                                $opties2[] = 'Klimaatregeling';
                                break;
                            case 103: // Dagrijlichten
                                $opties2[] = 'Dagrijlichten';
                                break;
                            case 104: // Aangepast voor mindervaliden
                                $opties2[] = 'Aangepast voor mindervaliden';
                                break;
                            case 105: // Nieuw inspectie
                                $opties2[] = 'nieuw geïnspecteerd';
                                break;
                            case 106: // Multifunctioneel stuur
                                $opties2[] = 'Multifunctioneel stuur';
                                break;
                            case 107: // Niet-rokersvoertuig
                                $opties2[] = 'Niet-rokersvoertuig';
                                break;
                            case 108: // Ski zak
                                $opties2[] = 'Ski zak';
                                break;
                            case 109: // Roetfilter
                                $opties2[] = 'Roetfilter';
                                break;
                            case 110: // passagier airbag
                                $opties2[] = 'passagier airbag';
                                break;
                            case 111: // zij airbags
                                $opties2[] = 'zij airbags';
                                break;
                            case 112: // Sportophanging
                                $opties2[] = 'Sportophanging';
                                break;
                            case 113: // Sportpakket
                                $opties2[] = 'Sportpakket';
                                break;
                            case 114: // start_stop_automatic
                                $opties2[] = 'Start/Stop systeem';
                                break;
                            case 115: // Met garantie
                                $opties2[] = 'Met garantie';
                                break;
                        }
                        break;
                    case 74: // kleurid
                        switch ($choice->id) {
                            case 71: // beige
                                $data['color'] = '18';
                                break;
                            case 72: // blauw
                                $data['color'] = '9';
                                break;
                            case 74: // bruin
                                $data['color'] = '13';
                                break;
                            case 75: // geel
                                $data['color'] = '12';
                                break;
                            case 76: // goud
                                $data['color'] = '16';
                                break;
                            case 77: // grijs
                                $data['color'] = '4';
                                break;
                            case 78: // groen
                                $data['color'] = '11';
                                break;
                            case 79: // oranje
                                $data['color'] = '15';
                                break;
                            case 80: // paars
                                $data['color'] = '14';
                                break;
                            case 81: // rood
                                $data['color'] = '6';
                                break;
                            case 82: // wit
                                $data['color'] = '';
                                break;
                            case 83: // zilver
                                $data['color'] = '3';
                                break;
                            case 84: // zwart
                                $data['color'] = '2';
                                break;
                        }
                        break;
                  case 80:// opties toevoegen bij beschrijving.
                      switch ($choice->id) {
                            case 117: // kapaza
                                $add_opties = '1';
                      }  
                      break;
                }
            }
        }
        $get_brand_kapaza = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=" . $auto->product_brand_id));
        if ($get_brand_kapaza->kapaza != 0) {
            $data['brand'] = $get_brand_kapaza->kapaza;
            
            $get_brand_kapaza = mysqli_query($conn, "SELECT * FROM tbl_product_brand_kapaza WHERE brand_id=" . $get_brand_kapaza->kapaza . " AND year='" . $data['regdate'] . "' AND shape='" . $data['car_shape'] . "' AND fuel='" . $data['fuel'] . "'");
            
            if (mysqli_num_rows($get_brand_kapaza) != 0) {
                
                while($brand = mysqli_fetch_object($get_brand_kapaza)){
                    
                    $model_kapaza_id = mysqli_query($conn, "SELECT * FROM tbl_product_model_kapaza WHERE brand_kapaza_id=".$brand->id." AND model_id=".$auto->product_model_id);
                    
                    if(mysqli_num_rows($model_kapaza_id) != 0){
                        
                        $o_model = mysqli_fetch_object($model_kapaza_id);
                        $data['model'] = $o_model->kapaza_id;
                        $data['model_group_1'] = $o_model->name;
                    }else{
                        $data['model'] = '0';
                        $data['model_group_1'] = '0';
                    }
                }
            } else {
                $data['model'] = '0';
                $data['model_group_1'] = '0';
            }
        } else {
            $data['brand'] = '0';
            $data['model'] = '0';
            $data['model_group_1'] = '0';
        }
        $data['pro_private'] = '1';
        $test = '';
        $test2 = '';
        
        foreach($opties as $key => $value){
                $test .= "&extra_chks%5B%5D=".$key; 
            }
        // opties onder beschrijving toevoegen
        if($add_opties == '1'){
            $add = '';
            
            foreach($opties2 as $key => $value){
                $add .= $value .", "; 
            }
            $data['body'] .= "
                    
                    ";
            $data['body'] .= "Opties: ";
            $data['body'] .= substr($add,0,-2);
        }
        
        foreach($images_arr as $key => $value){
            $test2 .= "&image%5B%5D=".substr($value,6);
        }

        $submit_form2 = http_build_query($data);
        $post_data = $submit_form2 . $test . $test2;
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
        
        // POST FORM PAGE
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_URL, 'http://m.kapaza.be/nl/mai/submit');
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_REFERER, 'http://m.kapaza.be/nl/mai/form?token='.$tokenform);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $k = curl_exec($ch);
        echo $k;
        
        curl_close($ch);
        // toevoegen in geschiedenis
        mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$auto->name."',".$auto->id.",'1',2)");
    }    
}