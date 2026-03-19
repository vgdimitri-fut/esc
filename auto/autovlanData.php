<?php
/*
 * $productid komt van autovlan.php
 * 
 */

include "../inc/db.php";
$verkoperid = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
$data = array(); //[name] => value
$data['Id'] = 0;
$data['Zoeker'] = 'auto';
$data['VerkoperId'] = $verkoperid->autovlan_user_id;
$data['MerkId'] = $brandid;
$data['ModelId'] = $modelid;
$data['Type'] = $q_product->name;
$data['VanParticulier'] = 0;

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
                $date = explode('-',$row->value);
                $data['Bouwjaar'] = $date[2];
                $pos = strpos($date[0],0);
                if($pos == 0){
                    $maand = substr($date[0],1);
                }else{$maand = $date[0];}
                $data['Bouwmaand'] = $maand;
                break;
            case 2:
                $data['KmStand'] = $row->value;
                break;
            case 54:
                $data['Cilinderinhoud'] = $row->value;
                break;
            case 56:
                $data['Prijs'] = $row->value;
                break;
            case 60: // beschrijving
                $data['Commentaar_nl'] = $row->value;
                break;
            case 75: // hoofdfoto
                $data['Hoofdfoto'] = "www.carengineering.be/images/uploads/products/".$productid."/".$row->value;
                break;
            case 57: // verbruik gemengd
                $data['VerbruikGemengd'] = $row->value;
                break;
            case 70:
                $data['VerbruikStad'] = $row->value;
                break;
            case 71:
                $data['VerbruikBuiten'] = $row->value;
                break;
            case 72:
                $data['CO2Uitstoot'] = $row->value;
                break;
            case 73:
                $data['Euronorm'] = $row->value;
                break;
            case 76:
                $data['Chassisnr'] = $row->value;
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
                        $brandstof = 1;
                        break;
                    case 'Diesel':
                        $brandstof = 2;
                        break;
                    case 'LPG':
                        $brandstof = 3;
                }
                $data['BrandstofId'] = $brandstof;
                break;
            case 59:
                switch($choice->choice)
                {
                    case 'Manueel':
                        $schakeling = 1;
                        break;
                    default:
                        $schakeling = 2;
                        break;
                }
                $data['TransmissieId'] = $schakeling;
                break;
            case 67: 
                if($choice->choice == 'Verkocht')
                {
                    $data['Verkocht'] = 1;
                }else{
                    $data['Verkocht'] = 0;
                }
                break;
            case 69: // opties
                switch($choice->id)
                {
                    case 48: //abs
                        $data['Opties'] .= 1 . " ";
                        break;
                    case 49: //alarm
                        $data['Opties'] .= 2 . " ";
                        break;
                    case 50: //boordcomputer
                        $data['Opties'] .= 5 . " ";
                        break;
                    case 51: //airco
                        $data['Opties'] .= 6 . " ";
                        break;
                    case 52: //centrale vergrendeling
                        $data['Opties'] .= 7 . " ";
                        break;
                    case 53: //elektrische ruiten
                        $data['Opties'] .= 8 . " ";
                        break;
                    case 54: //cruise control
                        $data['Opties'] .= 12 . " ";
                        break;
                    case 55: //leder
                        $data['Opties'] .= 17 . " ";
                        break;
                    case 56: //schuifdak
                        $data['Opties'] .= 19 . " ";
                        break;
                    case 57: //stuurbekrachtiging
                        $data['Opties'] .= 21 . " ";
                        break;
                    case 58: //navigatiesysteem
                        $data['Opties'] .= 26 . " ";
                        break;
                    case 59: //verwarmde zetels
                        $data['Opties'] .= 27 . " ";
                        break;
                    case 60: //Xenon lichten
                        $data['Opties'] .= 37 . " ";
                        break;
                    case 61: //elektrische zetels
                        $data['Opties'] .= 45 . " ";
                        break;
                    case 62: //Elektronische Stabiliteitsregeling(ESP)
                        $data['Opties'] .= 46 . " ";
                        break;
                    case 63: //Parkeerhulpsysteem (PDS)
                        $data['Opties'] .= 47 . " ";
                        break;
                    case 85: // airbag
                        $data['Opties'] .= 15 . " ";
                        break;
                    case 87: // neerklapbare achterbank
                        $data['Opties'] .= 34 . " ";
                        break;
                    case 88: // Soundsysteem
                        $data['Opties'] .= 11 . " ";
                        break;
                    case 90: // Getinte ruiten
                        $data['Opties'] .= 28 . " ";
                        break;
                    case 91: // Mistlampen
                        $data['Opties'] .= 14 . " ";
                        break;
                    case 92: // Trekhaak
                        $data['Opties'] .= 22 . " ";
                        break;
                    case 94: // Elektrische spiegels
                        $data['Opties'] .= 9 . " ";
                        break;
                    case 95: // Sportstuur
                        $data['Opties'] .= 30 . " ";
                        break;
                    case 96: // Sportzetel
                        $data['Opties'] .= 31 . " ";
                        break;
                    case 97: // Dakdragers
                        $data['Opties'] .= 18 . " ";
                        break;
                }
                break;
            case 74: // kleurid
                switch($choice->id)
                {
                    case 70: //antraciet
                        $data['KleurId'] = 2;
                        break;
                    case 71: // beige
                        $data['KleurId'] = 3;
                        break;
                    case 72: // blauw
                        $data['KleurId'] = 4;
                        break;
                    case 73: // bordeaux
                        $data['KleurId'] = 5;
                        break;
                    case 74: // bruin
                        $data['KleurId'] = 6;
                        break;
                    case 75: // geel
                        $data['KleurId'] = 7;
                        break;
                    case 76: // goud
                        $data['KleurId'] = 8;
                        break;
                    case 77: // grijs
                        $data['KleurId'] = 10;
                        break;
                    case 78: // groen
                        $data['KleurId'] = 9;
                        break;
                    case 79: // oranje
                        $data['KleurId'] = 11;
                        break;
                    case 80: // paars
                        $data['KleurId'] = 13;
                        break;
                    case 81: // rood
                        $data['KleurId'] = 12;
                        break;
                    case 82: // wit
                        $data['KleurId'] = 14;
                        break;
                    case 83: // zilver
                        $data['KleurId'] = 15;
                        break;
                    case 84: // zwart
                        $data['KleurId'] = 16;
                        break;
                }
                break;
        }
    }
}
$data['Opties'] = rtrim($data['Opties']);
/*
 * ** = Added
 * -- = Not added
 * 
 *** Id = 0
 *** Zoeker = auto
 *** MerkId = int
 *** ModelId = int
 *** Type = naam
 *** Bouwjaar = jaar
 *** Bouwmaand = maand
 *** KleurId
 *** KmStand
 *-- Deuren
 *** TransmissieId
 *-- Versnellingen
 *-- Cilinders
 *** Cilinderinhoud
 *-- Kw
 *** BrandstofId
 *-- Gewicht
 *** VerkoperId = ID AUTOVLAN
 *** VanParticulier = 0
 *** Prijs = bedrag
 *** Verkocht = 0
 *-- BTWAftrekbaar = 0|1
 *-- Schadevoertuig = 0|1
 *** Opties = 1 2 3 4 5
 *** Commentaar_nl = beschrijving
 *-- CarPassOk = 0|1
 *** Hoofdfoto = 123.jpg
 *-- Chassisnr
 *** VerbruikStad
 *** VerbruikBuiten
 *** VerbruikGemengd
 *** CO2Uitstoot
 *** Euronorm 
 * 
 */

