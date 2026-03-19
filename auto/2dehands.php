<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";

if(isset($_POST['id']))
{
    $productid = $_POST['id'];
    if($_POST['action'] == 'add')
    {
        mysqli_query($conn, "UPDATE tbl_products SET tweedehands='1' WHERE id=".$productid);
    }else{
        mysqli_query($conn, "UPDATE tbl_products SET tweedehands='0' WHERE id=".$productid);
    }
    exit();
}
if(isset($_POST['action']) && $_POST['action'] == 'upload')
{
    $q_product = "SELECT * FROM tbl_products WHERE";
    $i = 0;
    foreach($_POST['ids'] as $id)
    {
        if($i == 0)
        {
            $q_product .= " id=" . $id;
        }else{
            $q_product .= " OR id=" . $id;
        }
        $i++;
    }
    echo $q_product;
    $q_all = mysqli_query($conn, $q_product);
    while($auto = mysqli_fetch_object($q_all))
    {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $logindata);
            //curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_exec($ch);
            
            mysqli_query($conn, "INSERT INTO tbl_site_historiek (datetime,name,product_id,actie,website) VALUES ('".date('Y-m-d H:i:s')."','".$auto->name."',".$auto->id.",'1',1)");

            $getBrand = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM tbl_product_brand WHERE id=".$auto->product_brand_id));
            // URL - NEW ADS
            $formurl = "http://www.2dehands.be/plaats/auto/auto/".$getBrand['naam_2dehands']."/plaats_zoekertje";

            // GET FORM
            curl_setopt($ch, CURLOPT_URL, $formurl);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            // GET SCRATCH_ID
            $doc = new DOMDocument();
            libxml_use_internal_errors(true); // hide xml warnings
            $doc->loadHTMLFile($formurl);
            $id = $doc->getElementById('scratch_id_input')->getAttribute('value');
            //echo "<br />". $id . "<br />";

            // FORM IMAGES
            $photo_url = "http://www.2dehands.be/callback/image/".$id."/upload/";
            $first_photo = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=".$auto->id." AND product_fields_id=75");
            if(mysqli_num_rows($first_photo) != 0){
                $f_photo = mysqli_fetch_object($first_photo);
                $hoofdfoto = $f_photo->value;
                $images = array('files[0]' => "@".realpath("../../images/uploads/products/".$auto->id."/".$hoofdfoto));
                curl_setopt($ch, CURLOPT_URL, $photo_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $images);
                curl_exec($ch);
            }
            $q_photos = mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort_id=".$auto->id." LIMIT 4");
            while($image = mysqli_fetch_object($q_photos))
            {            
                if($hoofdfoto != $image->cf_file){
                    $images = array('files[0]' => "@".realpath("../../images/uploads/products/".$auto->id."/".$image->cf_file));
                    curl_setopt($ch, CURLOPT_URL, $photo_url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $images);
                    curl_exec($ch);
                }
            }

            // FORM DATA
            $data['adv.title.nl_BE'] = $auto->name; // title name
            $getModelName = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_model WHERE id=".$auto->product_model_id));
            $data['adv.auto_type'] = $getModelName->naam; // model name
            $opties = '';
            $opties2 = array();
            $data['adv.description.nl_BE'] = '';
            // loop fields
            $q_field_values = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=" . $auto->id);
            while ($value = mysqli_fetch_object($q_field_values)) {
                $q_choice = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id='" . $value->value . "' AND product_fields_id=" . $value->product_fields_id);
                if (mysqli_num_rows($q_choice) == 0) {
                    //echo $q_field->field." heeft <strong>geen</strong> keuze. <br />";
                    switch ($value->product_fields_id) {
                        case 58:
                            $date = explode('-', $value->value);
                            $data['adv.auto_bj.jaar'] = $date[2];
                            $pos = strpos($date[0], 0);
                            if ($pos == 0) {
                                $maand = substr($date[0], 1);
                            } else {
                                $maand = $date[0];
                            }
                            $data['adv.auto_bj.maand'] = $maand;
                            break;
                        case 2: // kilometerstand
                            switch($value->value)
                            {
                                case ($value->value>=0 && $value->value<=20000):
                                    $data['adv.auto_km'] = 'auto_km/tot-20-000';
                                    break;
                                case ($value->value>20000 && $value->value<=30000):
                                    $data['adv.auto_km'] = 'auto_km/20-000-30-000';
                                    break;
                                case ($value->value>30000 && $value->value<=40000):
                                    $data['adv.auto_km'] = 'auto_km/30-000-40-000';
                                    break;
                                case ($value->value>40000 && $value->value<=50000):
                                    $data['adv.auto_km'] = 'auto_km/40-000-50-000';
                                    break;
                                case ($value->value>50000 && $value->value<=60000):
                                    $data['adv.auto_km'] = 'auto_km/50-000-60-000';
                                    break;
                                case ($value->value>60000 && $value->value<=70000):
                                    $data['adv.auto_km'] = 'auto_km/60-000-70-000';
                                    break;
                                case ($value->value>70000 && $value->value<=80000):
                                    $data['adv.auto_km'] = 'auto_km/70-000-80-000';
                                    break;
                                case ($value->value>80000 && $value->value<=90000):
                                    $data['adv.auto_km'] = 'auto_km/80-000-90-000';
                                    break;
                                case ($value->value>90000 && $value->value<=100000):
                                    $data['adv.auto_km'] = 'auto_km/90-000-100-000';
                                    break;
                                case ($value->value>100000 && $value->value<=120000):
                                    $data['adv.auto_km'] = 'auto_km/100-000-120-000';
                                    break;
                                case ($value->value>120000 && $value->value<=140000):
                                    $data['adv.auto_km'] = 'auto_km/120-000-140-000';
                                    break;
                                case ($value->value>140000 && $value->value<=160000):
                                    $data['adv.auto_km'] = 'auto_km/140-000-160-000';
                                    break;
                                case ($value->value>160000 && $value->value<=180000):
                                    $data['adv.auto_km'] = 'auto_km/160-000-180-000';
                                    break;
                                case ($value->value>180000 && $value->value<=200000):
                                    $data['adv.auto_km'] = 'auto_km/180-000-200-000';
                                    break;
                                case ($value->value>200000 && $value->value<=250000):
                                    $data['adv.auto_km'] = 'auto_km/200-000-250-000';
                                    break;
                                case ($value->value>250000 && $value->value<=300000):
                                    $data['adv.auto_km'] = 'auto_km/250-000-300-000';
                                    break;
                                case ($value->value>300000):
                                    $data['adv.auto_km'] = 'auto_km/300-000-en-meer';
                                    break;
                            }
                            break;
                        case 56:
                            $data['prijs_amount'] = $value->value;
                            break;
                        case 60: // beschrijving
                            $data['adv.description.nl_BE'] = html_entity_decode($value->value);
                            break;
                    }
                } else {
                    //echo $q_field->field." heeft keuze. <br />";
                    $choice = mysqli_fetch_object($q_choice);
                    switch ($value->product_fields_id) {
                        case 1:
                            switch ($choice->choice) {
                                case 'Benzine':
                                    $brandstof = 'auto_brandstof/benzine';
                                    break;
                                case 'Diesel':
                                    $brandstof = 'auto_brandstof/diesel';
                                    break;
                                case 'LPG':
                                    $brandstof = 'auto_brandstof/lpg';
                            }
                            $data['adv.auto_brandstof'] = $brandstof;
                            break;
                        case 55: // carrosserie
                            switch($choice->id){
                                case 29: // suv
                                    $data['adv.auto_carros'] = 'auto_carros/all-terrain';
                                    break;
                                case 30: // coupe
                                    $data['adv.auto_carros'] = 'auto_carros/coupe';
                                    break;
                                case 35: // berline
                                    $data['adv.auto_carros'] = 'auto_carros/overig';
                                    break;
                                case 36: // sedan
                                    $data['adv.auto_carros'] = 'auto_carros/sedan';
                                    break;
                                case 40: // Stationwagon
                                    $data['adv.auto_carros'] = 'auto_carros/stationwagon';
                                    break;
                                case 41: // hatchback
                                    $data['adv.auto_carros'] = 'auto_carros/hatchback';
                                    break;
                                case 42: // crossauto
                                    $data['adv.auto_carros'] = 'auto_carros/crossauto';
                                    break;
                                case 43: // camper
                                    $data['adv.auto_carros'] = 'auto_carros/camper';
                                    break;
                                case 44: // cabriolet
                                    $data['adv.auto_carros'] = 'auto_carros/cabriolet';
                                    break;
                                case 45: // bestelbus
                                    $data['adv.auto_carros'] = 'auto_carros/bestelbus';
                                    break;
                                case 46: // schadeauto
                                    $data['adv.auto_carros'] = 'auto_carros/schadeauto';
                                    break;
                                case 47: // overig
                                    $data['adv.auto_carros'] = 'auto_carros/overig';
                                    break;                            
                            }
                        case 69: // opties
                            switch($choice->id)
                            {
                                case 48: //abs
                                    $opties .= 'adv.auto_opties=auto_opties/abs&';
                                    $opties2[] = 'abs';
                                    break;
                                case 49: //alarm
                                    $opties .= 'adv.auto_opties=auto_opties/alarm&';
                                    $opties2[] = 'alarm';
                                    break;
                                case 50: //boordcomputer
                                    $opties .= 'adv.auto_opties=auto_opties/boordcomputer&';
                                    $opties2[] = 'boordcomputer';
                                    break;
                                case 51: //airco
                                    $opties .= 'adv.auto_opties=auto_opties/airco&';
                                    $opties2[] = 'airco';
                                    break;
                                case 52: //centrale vergrendeling
                                    $opties .= 'adv.auto_opties=auto_opties/centr-vergrendeling&';
                                    $opties2[] = 'centrale vergrendeling';
                                    break;
                                case 53: //elektrische ruiten
                                    $opties .= 'adv.auto_opties=auto_opties/elektr-ramen-v-a&';
                                    $opties2[] = 'elektrische ramen voor en achter';
                                    break;
                                case 54: //cruise control
                                    $opties .= 'adv.auto_opties=auto_opties/cruise-control&';
                                    $opties2[] = 'cruise control';
                                    break;
                                case 55: //leder
                                    $opties .= 'adv.auto_opties=auto_opties/lederen-bekleding&';
                                    $opties2[] = 'lederen bekleding';
                                    break;
                                case 57: //stuurbekrachtiging
                                    $opties .= 'adv.auto_opties=auto_opties/stuurbekrachtiging&';
                                    $opties2[] = 'stuurbekrachtiging';
                                    break;
                                case 58: //navigatiesysteem
                                    $opties .= 'adv.auto_opties=auto_opties/navigatiesysteem&';
                                    $opties2[] = 'navigatiesysteem';
                                    break;
                                case 63: //Parkeerhulpsysteem (PDS)
                                    $opties .= 'adv.auto_opties=auto_opties/parkeersensor&';
                                    $opties2[] = 'parkeersensor';
                                    break;
                                case 85: // airbag enkel
                                    $opties .= 'adv.auto_opties=auto_opties/airbag-enkel';
                                    $opties2[] = 'enkel airbag';
                                    break;
                                case 86: // airbag dubbel
                                    $opties .= 'adv.auto_opties=auto_opties/airbag-dubbel';
                                    $opties2[] = 'dubbele airbag';
                                    break;
                                case 87: // Neerklapbare achterbank
                                    $opties2[] = 'Neerklapbare achterbank';
                                    break;
                                case 88: // Soundsysteem
                                    $opties .= 'adv.auto_opties=auto_opties/radio-cd';
                                    $opties2[] = 'radio cd';
                                    break;
                                case 89: // Vierwielaandrijving
                                    $opties2[] = 'Vierwielaandrijving';
                                    break;
                                case 90: // getinte ruiten
                                    $opties .= 'adv.auto_opties=auto_opties/getint-glas';
                                    $opties2[] = 'getint glas';
                                    break;
                                case 91: // Mistlampen
                                    $opties2[] = 'Mistlampen';
                                    break;
                                case 92: // trekhaak
                                    $opties .= 'adv.auto_opties=auto_opties/trekhaak';
                                    $opties2[] = 'trekhaak';
                                    break;
                                case 93: // Licht metalen velg
                                    $opties2[] = 'Licht metalen velg';
                                    break;
                                case 94: // Elektrische spiegels
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
                                    $data['adv.auto_kleur'] = 'auto_kleur/beige';
                                    break;
                                case 72: // blauw
                                    $data['adv.auto_kleur'] = 'auto_kleur/blauw';
                                    break;
                                case 74: // bruin
                                    $data['adv.auto_kleur'] = 'auto_kleur/bruin';
                                    break;
                                case 75: // geel
                                    $data['adv.auto_kleur'] = 'auto_kleur/geel';
                                    break;
                                case 76: // goud
                                    $data['adv.auto_kleur'] = 'auto_kleur/goud';
                                    break;
                                case 77: // grijs
                                    $data['adv.auto_kleur'] = 'auto_kleur/grijs';
                                    break;
                                case 78: // groen
                                    $data['adv.auto_kleur'] = 'auto_kleur/groen';
                                    break;
                                case 79: // oranje
                                    $data['adv.auto_kleur'] = 'auto_kleur/oranje';
                                    break;
                                case 80: // paars
                                    $data['adv.auto_kleur'] = 'auto_kleur/paars';
                                    break;
                                case 81: // rood
                                    $data['adv.auto_kleur'] = 'auto_kleur/rood';
                                    break;
                                case 82: // wit
                                    $data['adv.auto_kleur'] = 'auto_kleur/wit';
                                    break;
                                case 83: // zilver
                                    $data['adv.auto_kleur'] = 'auto_kleur/zilver';
                                    break;
                                case 84: // zwart
                                    $data['adv.auto_kleur'] = 'auto_kleur/zwart';
                                    break;
                            }
                            break;
                        case 80: // add opties
                            switch ($choice->id) {
                                case 116: // 2dehands
                                    $add_opties = '1';
                                    break;
                            }       
                            break;
                    }
                }
            }
            $data2 = array('scratch_id' => $id,
                'adv.conditie' => "conditie/gebruikt",
                'multi_language' => "0",
                'adv.bedragplaats' => "<amount>",
                'adv.allow_bieden' => "on",
                'adv.minimumbod' => "0,00",
                'adv.auto_deurs' => "auto_deurs/5-deurs",
                'adv.website' => "www.carengineering.be");
            
            // opties onder beschrijving toevoegen
            if(count($opties2) < 9 && $add_opties!= '1'){
                $datamerge = array_merge($data,$data2);
                $dataquery = http_build_query($datamerge);
                $post = $opties . $dataquery;
                
            }else{
                $data['adv.description.nl_BE'] .= '
                        
                        ';
                $data['adv.description.nl_BE'] .= 'Opties: ';
                foreach($opties2 as $index => $value){
                    $data['adv.description.nl_BE'] .= $value .", ";
                }
                $data['adv.description.nl_BE'] = substr($data['adv.description.nl_BE'],0,-2);
                $datamerge = array_merge($data,$data2);
                $dataquery = http_build_query($datamerge);
                $post = $dataquery;
            }
            

            echo "<pre>";
            var_dump($datamerge);
            echo "</pre>";
            curl_setopt($ch, CURLOPT_URL, $formurl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            echo curl_exec($ch);
            curl_close($ch);
    }

    // show curl details
    //rewind($verbose);
    //$verboseLog = stream_get_contents($verbose);
    //
    //echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
}