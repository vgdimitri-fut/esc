<?php
/*
 * $productid komt van autovlan.php
 * 
 */

include "../inc/db.php";

$data = array(); //[name] => value
$consumption = array();
$opties = array();
$price = array();
$emission = array();
$dealer_id = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
$data['dealer_id'] = $dealer_id->autoscout_user_id;
$data['ownersvehicle_id'] = $productid;
$data['visibility'] = 'dealer';
$data['status'] = 'active';
$data['brand'] = $brand;
$data['model'] = $model;
$data['type'] = 'car';
$data['category'] = 'used';
$data['title'] = $q_product->name;

$q = mysqli_query($conn, "SELECT * FROM tbl_product_values WHERE product_id=".$productid);
while($row = mysqli_fetch_object($q))
{
//    $q_field = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM tbl_product_fields WHERE id=".$row->product_fields_id));
    $q_choice = mysqli_query($conn, "SELECT * FROM tbl_product_field_choices WHERE id='".$row->value. "' AND product_fields_id=".$row->product_fields_id);
    if(mysqli_num_rows($q_choice) == 0)
    {
        //echo $q_field->field." heeft <strong>geen</strong> keuze. <br />";
        switch($row->product_fields_id)
        {
            case 58:
                $data['initial_registration'] = $row->value;
                break;
            case 2:
                $data['mileage'] = $row->value;
                break;
            case 54:
                $data['capacity'] = $row->value;
                break;
            case 56:
                $price['type'] = 'public';
                $price['currency'] = 'eur';
                $price['value'] = $row->value;
                break;
            case 60: // beschrijving
                $data['notes'] = $row->value;
                break;
            case 57: // verbruik gemengd
                $consumption['combined'] = $row->value;
                break;
            case 70: // binnen stad
                $consumption['urban'] = $row->value;
                break;
            case 71: // buiten stad
                $consumption['extra_urban'] = $row->value;
                break;
            case 72: // CO2
                $emission['co2_liquid'] = $row->value;
                break;
            case 73: // euronorm
                $emission['class'] = $row->value;
                break;
            case 76: // chassisnummer
                $data['vin'] = $row->value;
                break;
         }
    }else{
        //echo $q_field->field." heeft keuze. <br />";
        $choice = mysqli_fetch_object($q_choice);
        switch($row->product_fields_id)
        {
            case 1:
                switch($choice->choice)
                {
                    case 'Benzine':
                        $data['fuel_type'] = 'gasoline';
                        break;
                    case 'Diesel':
                        $data['fuel_type'] = 'diesel';
                        break;
                    case 'LPG':
                        $data['fuel_type'] = 'lpg';
                }
                break;
            case 55:  // carrosserie
                switch($choice->id)
                {
                        case 29: // SUV
                            $data['body'] = 'off_road';
                            break;
                        case 30: // coupe
                            $data['body'] = 'coupe';
                            break;
                        case 35: // berline
                            $data['body'] = 'other_car';
                            break;
                        case 36: // sedan
                            $data['body'] = 'sedan';
                            break;
                        case 40: // station wagen
                            $data['body'] = 'coupe';
                            break;
                        case 41: // hatch back
                            $data['body'] = 'compact';
                            break;
                        case 42: // crossauto
                            $data['body'] = 'other_car';
                            break;
                        case 43: // camper
                            $data['body'] = 'other_car';
                            break;
                        case 44: // cabriolet
                            $data['body'] = 'convertible';
                            break;
                        case 45: // bestelbus
                            $data['body'] = 'transporter';
                            break;
                        case 46: // schade auto
                            $data['body'] = 'other_car';
                            break;
                        case 47: // overig
                            $data['body'] = 'other_car';
                            break;
                }
                break;
            case 59:
                switch($choice->choice)
                {
                    case 'Manueel':
                        $data['gear_type'] = 'manual';
                        break;
                    default:
                        $data['gear_type'] = 'automatic';
                        break;
                }
                break;
            case 69: // opties
                switch($choice->id)
                {
                    case 48: //abs
                        $opties['text'][] = 'abs';
                        break;
                    case 49: //alarm
                        $opties['text'][] = 'alarm';
                        break;
                    case 50: //boordcomputer
                        $opties['text'][] = 'onboard_computer';
                        $opties['text'][] = 'climate_control';
                        break;
                    case 51: //airco
                        $opties['text'][] = 'air_conditioning';
                        break;
                    case 52: //centrale vergrendeling
                        $opties['text'][] = 'central_door_lock';
                        break;
                    case 53: //elektrische ruiten
                        $opties['text'][] = 'power_windows';
                        break;
                    case 54: //cruise control
                        $opties['text'][] = 'cruise_control';
                        break;
                    case 55: //leder
                        $data['covering'] = 'full_leather';
                        break;
                    case 56: //schuifdak
                        $opties['text'][] = 'sunroof';
                        break;
                    case 57: //stuurbekrachtiging
                        $opties['text'][] = 'power_steering';
                        break;
                    case 58: //navigatiesysteem
                        $opties['text'][] = 'navigation_system';
                        break;
                    case 59: //verwarmde zetels
                        $opties['text'][] = 'heated_seats';
                        break;
                    case 60: //Xenon lichten
                        $opties['text'][] = 'xenon_lights';
                        break;
                    case 61: //elektrische zetels
                        $opties['text'][] = 'electrical_adjustable_seats';
                        break;
                    case 62: //Elektronische Stabiliteitsregeling(ESP)
                        $opties['text'][] = 'esp';
                        $opties['text'][] = 'traction_control';
                        break;
                    case 63: //Parkeerhulpsysteem (PDS)
                        $opties['text'][] = 'park_distance_control';
                        break;
                    case 85: // Airbag
                        $opties['text'][] = 'airbag';
                        break;
                    case 88: // Soundsysteem
                        $opties['text'][] = 'radio_cd';
                        break;
                    case 91: // Mistlampen
                        $opties['text'][] = 'fog_lights';
                        break;
                    case 92: // Trekhaak
                        $opties['text'][] = 'towing_hook';
                        break;
                    case 93: // Licht metalen velg
                        $opties['text'][] = 'alloy_wheels';
                        break;
                    case 96: // Sportzetels
                        $opties['text'][] = 'sport_seats';
                        break;
                    case 97: // Dakdragers
                        $opties['text'][] = 'roof_rack';
                        break;
                    case 98: // Volledige service geschiedenis
                        $opties['text'][] = 'full_service_history';
                        break;
                    case 99: // Startonderbreker
                        $opties['text'][] = 'immobilizer';
                        break;
                    case 100: // extra vermarming
                        $opties['text'][] = 'auxiliary_heating';
                        break;
                    case 101: // Adaptive light
                        $opties['text'][] = 'bending_light';
                        break;
                    case 102: // Klimaatregeling
                        $opties['text'][] = 'climate_control';
                        break;
                    case 103: // Dagrijlichten
                        $opties['text'][] = 'daytime_running_light';
                        break;
                    case 104: // Aangepast voor mindervaliden
                        $opties['text'][] = 'handicapped_enabled';
                        break;
                    case 105: // Nieuw inspectie
                        $opties['text'][] = 'inspections_new';
                        break;
                    case 106: // Multifunctioneel stuur
                        $opties['text'][] = 'multifunctional_wheel';
                        break;
                    case 107: // Niet-rokersvoertuig
                        $opties['text'][] = 'nonsmoking_vehicle';
                        break;
                    case 108: // Ski zak
                        $opties['text'][] = 'ski_bag';
                        break;
                    case 109: // Roetfilter
                        $opties['text'][] = 'particulate_filter';
                        break;
                    case 110: // passagier airbag
                        $opties['text'][] = 'passenger_airbag';
                        break;
                    case 111: // zij airbags
                        $opties['text'][] = 'side_airbags';
                        break;
                    case 112: // Sportophanging
                        $opties['text'][] = 'sport_suspension';
                        break;
                    case 113: // Sportpakket
                        $opties['text'][] = 'sport_package';
                        break;
                    case 114: // start_stop_automatic
                        $opties['text'][] = 'Start/Stop systeem';
                        break;
                    case 115: // Met garantie
                        $opties['text'][] = 'used_car_warranty';
                        break;
                }
                break;
            case 74: // kleurid
                switch($choice->id)
                {
                    case 71: // beige
                        $data['body_colorgroup'] = 'beige';
                        break;
                    case 72: // blauw
                        $data['body_colorgroup'] = 'blue';
                        break;
                    case 74: // bruin
                        $data['body_colorgroup'] = 'brown';
                        break;
                    case 75: // geel
                        $data['body_colorgroup'] = 'yellow';
                        break;
                    case 77: // grijs
                        $data['body_colorgroup'] = 'grey';
                        break;
                    case 78: // groen
                        $data['body_colorgroup'] = 'green';
                        break;
                    case 79: // oranje
                        $data['body_colorgroup'] = 'orange';
                        break;
                    case 81: // rood
                        $data['body_colorgroup'] = 'red';
                        break;
                    case 82: // wit
                        $data['body_colorgroup'] = 'white';
                        break;
                    case 83: // zilver
                        $data['body_colorgroup'] = 'silver';
                        break;
                    case 84: // zwart
                        $data['body_colorgroup'] = 'black';
                        break;
                }
                break;
        }
    }
}
/*
 * ** = Added
 * -- = Not added
 * 
 ***              dealer_id = int *Verplicht*
 ***              ownersvehicle_id = string
 ***              status = active | inactive
 ***              visibility = public | dealer
 --               vin = string
 ***               type = bike | car *Verplicht*
 ***               category = classic | demonstration | employee | new | pre_registered | used *Verplicht*
 ***               body = bus_van | compact | convertible | coupe | off_road | other_car | sedan | station_wagon | transporter; *Verplicht*
 ***               brand = string *Verplicht*
 ***               model = string *Verplicht*
 --               version = string
 ***               title = string
 --               body_color = string
 ***              body_colorgroup = beige | black | blue | bronze | brown | green | grey | orange | red | silver | violet | white | yellow *Verplicht*
 --               body_painting = metallic | uni
 --               interior_color = beige | black | brown | grey | other
 --               covering = alcantara | cloth | full_leather | part_leather | velour | other
 --               doors = int
 ***               gear_type = automatic | semi-automatic | manual
 --               gears = int
 ***               fuel_type = cng | diesel | electric | ethanol | gasoline | hybrid | hybrid_diesel | hydrogene | lpg | others | two_stroke_gasoline *Verplicht*
 --               transmission = string
 ***               capacity = int
 --               kilowatt = int
 --               cylinder = int
 ***               <consumption>
 ***                   <liquid>
 ***                       <fuel_type>super</fuel_type>
 ***                       <urban>7.9</urban>
 ***                       <extra_urban>5.2</extra_urban>
 ***                       <combined>6.2</combined>
 ***                   </liquid>
 ***               </consumption>
 ***               <emission>
 ***                   <class>euro_5</class>
 --                   <sticker>green</sticker>
 ***                   <co2_liquid>149</co2_liquid>
 --                   <efficiency_class>b</efficiency_class>
 ***               </emission>
 ***             mileage = int
 ***              <service>
 ***                  <last_technical_service>2011-07-01</last_technical_service>
 --                   <last_change_cam_belt>2011-07-01</last_change_cam_belt>
 ***               </service>
 --               hsn = int
 --               tsn = string
 --               schwacke_code = int
 ***              initial_registration = date(YYYY-MM-DD)
 --               general_inspection = date(YYYY-MM-DD)
 --               licence_number = string
 ***              notes = string
 ***               <prices> *Verplicht*
 ***                   <price> *Verplicht*
 ***                       <type>public</type> *Verplicht*
 ***                       <vat_type>reclaimable</vat_type>
 ***                       <currency>eur</currency> *Verplicht*
 ***                       <negotiable>true</negotiable>
 ***                       <value>3000</value> *Verplicht*
 ***                   </price>
 ***                   <price>
 ***                       <type>dealer</type>
 ***                       <value>2900</value>
 ***                   </price>
 ***               </prices>
 ***              <equipments>
 ***                   <equipment>
 ***                       <text>abs</text>
 ***                   </equipment>
 ***                   <equipment>
 ***                       <text>alloy_wheels</text>
 ***                   </equipment>
 ***                   <equipment>
 ***                       <text>climate_control</text>
 ***                   </equipment>
 ***               </equipments>
                <media>
                    <images>
                        <image>
                            <local>60_1.jpg</local>
                        </image>
                        <image>
                            <local>60_2.jpg</local>
                        </image>
                        <image>
                            <local>60_3.jpg</local>
                        </image>
                        <image>
                            <local>60_4.jpg</local>
                        </image>
                        <image>
                            <local>60_5.jpg</local>
                        </image>
                    </images>
                    <videos>
                        <video>
                            <uri>www.youtube.com/watch?v=hR10_vBZtjM</uri>
                        </video>
                    </videos>
                </media>
 --               <product_bookings>
 --                   <product>
 --                       <name>top_insertion</name>
 --                       <status>active</status>
 --                   </product>
 --                   <product>
 --                       <name>featured_ad</name>
 --                       <status>active</status>
 --                   </product>
 --               </product_bookings>
 --               <previous_owner>
 --                   <count>3</count>
 --               </previous_owner>
 --               accident_free = true | false
 --               kerb_weight = int
 --               alloy_wheels_size = int 10 | 11 | 12 | 13 | 14 | 15 | 16 | 17 | 18 | 19 | 20 | 21 | 22 | 23 | 24 | 25 | 26
 --               seats = int
 */

