<?php

include "../inc/db.php";
include "../inc/functions.php";

$q_product = "SELECT * FROM tbl_products WHERE id=54";
    
    $q_all = mysqli_query($conn, $q_product);
    $counter = 0;
    $auto = mysqli_fetch_object($q_all);
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
        $q_photos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$auto->id." LIMIT 4");
        $i = 0;
        while($image = mysqli_fetch_object($q_photos))
        {            
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
            echo "Upload image ".$i.": <br />".$h;
            $pos1 = strpos($h,'form/?') + strlen('form/?');
            $pos2 = strpos(substr($h,$pos1),'&');
            $waarde = substr($h,$pos1,$pos2);
            $images_arr[] = $waarde;
            $i++;
        }
        var_dump($images_arr);
        
              
        
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
        $data['subject'] = $auto->name;
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
                        $data['month'] = '';//(int)$date[1];
                        break;
                    case 2: // kilometerstand
                        $data['mileage'] = $value->value;
                        break;
                    case 7:
                        $data['power'] = $value->value;
                        break;
                    case 57:
                        $data['avgconsumption'] = '13.6';//$value->value;
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
                                break;
                            case 49: //alarm
                                $opties['alarm'] = '1';
                                break;
                            case 51: //airco
                                $opties['airconditioning'] = '1';
                                break;
                            case 52: //centrale vergrendeling
                                $opties['centrallock'] = '1';
                                break;
                            case 53: //elektrische ruiten
                                $opties['powerwindows'] = '1';
                                break;
                            case 54: //cruise control
                                $opties['cruisecontrol'] = '1';
                                break;
                            case 55: //leder
                                $opties['leather'] = '1';
                                break;
                            case 57: //stuurbekrachtiging
                                $opties['steering'] = '1';
                                break;
                            case 58: //navigatiesysteem
                                $opties['navigationsystem'] = '1';
                                break;
                            case 60: //Xenon lichten
                                $opties['xenonheadlights'] = '1';
                                break;
                            case 62: //esp
                                $opties['autostabilitycontrol'] = '1';
                                break;
                            case 63: //Parkeerhulpsysteem (PDS)
                                $opties['parkdistancecontrol'] = '1';
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
                }
            }
        }
        $get_brand_kapaza = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=" . $auto->product_brand_id));
        if ($get_brand_kapaza->kapaza != 0) {
            $data['brand'] = $get_brand_kapaza->kapaza;

            echo "SELECT * FROM tbl_product_brand_kapaza WHERE brand_id=" . $get_brand_kapaza->kapaza . " AND year=" . $data['regdate'] . " AND shape=" . $data['car_shape'] . " AND fuel=" . $data['fuel'] . "<br />";
            $get_model_kapaza = mysqli_query($conn, "SELECT * FROM tbl_product_brand_kapaza WHERE brand_id=" . $get_brand_kapaza->kapaza . " AND year='" . $data['regdate'] . "' AND shape='" . $data['car_shape'] . "' AND fuel='" . $data['fuel'] . "'");
            if (mysqli_num_rows($get_model_kapaza) != 0) {
                $brand_kapaza_id = mysqli_fetch_object($get_model_kapaza);
                echo "SELECT * FROM tbl_product_model_kapaza WHERE brand_kapaza_id=" . $brand_kapaza_id->id . " AND model_id=" . $auto->product_model_id . " <br />";
                $get_model = mysqli_query($conn, "SELECT * FROM tbl_product_model_kapaza WHERE brand_kapaza_id=" . $brand_kapaza_id->id . " AND model_id=" . $auto->product_model_id);
                if (mysqli_num_rows($get_model) != 0) {
                    $model = mysqli_fetch_object($get_model);
                    $data['model'] = $model->kapaza_id;
                    $data['model_group_1'] = $model->name;
                } else {
                    $data['model'] = '5386';
                    $data['model_group_1'] = ' 911 Turbo 3.6 Turbo ';
                    $data['car_version'] = '66608';
                }
            } else {
                $data['model'] = '5386';
                $data['model_group_1'] = ' 911 Turbo 3.6 Turbo ';
                $data['car_version'] = '66608';
            }
        } else {
            $data['brand'] = '0';
            $data['model'] = '0';
            $data['model_group_1'] = '0';
            $data['car_version'] = '0';
        }
        $data['pro_private'] = '1';
        $data['ncylinders'] = '6';
        $data['ngears'] = '6';
        $data['nsits'] = '2';
        echo "<pre>";
        $test = '';
        $test2 = '';
        foreach($opties as $key => $value){
            $test .= "&extra_chks%5B%5D=".$key; 
        }
        foreach($images_arr as $key => $value){
            $test2 .= "&image%5B%5D=".substr($value,6);
        }
        echo "</pre>";
        echo "<pre>";
        $submit_form = "lang=nl&category=1020&type=s&zipcode=3980&car_shape=3&fuel=1&regdate=2008&brand=22&model_group_1=+Classe+C+&model=5936&car_version=74849&mileage=95000&ndoors=4&gearbox=2&chassisnumber=&avgconsumption=13.6&co2emit=316&ncylinders=8&ngears=7&nsits=5&power=457&color=&month=&extra_chks%5B%5D=ABS&extra_chks%5B%5D=airbag&extra_chks%5B%5D=alarm&extra_chks%5B%5D=cruisecontrol&extra_chks%5B%5D=powerwindows&extra_chks%5B%5D=stainedglass&extra_chks%5B%5D=tractioncontrol&subject=Mercedes-Benz+C+63+AMG+BREAK&body=C63+met+AMG+performance+package!%0D%0AEen+prachtige+wagen%2C+full+option.%0D%0AEr+is+ook+een+set+wintervelgen+met+banden+bij.&price=33000&pro_private=1&name=carengineering&email=info%40carengineering.be&phone=0484612660&account_passwd=carengineering123" . $test2;
        echo $submit_form;

        echo "</pre>";
        
        echo "<pre>";
        $submit_form2 = http_build_query($data);
        $post_data = $submit_form2 . $test . $test2;
        echo $post_data;
        echo "</pre>";
        echo "<pre>";
        var_dump(explode('&',$submit_form));
        var_dump(explode('&',$submit_form2 . $test . $test2));
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
        $e = curl_exec($ch);
        echo $e;
        
        curl_close($ch);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
    
    ?>