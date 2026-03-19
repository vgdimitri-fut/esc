<?php

session_start();

include "inc/db.php";
include "inc/functions.php";
include "inc/checklogin.php";

/*
echo "<pre style='text-align:left;'>";
echo "<br>FILES : " . count( $_FILES );
echo "<br>POST : " . count( $_POST );
echo "</pre>";
*/

// futech.be
$link_futech = mysqli_connect('localhost', 'root', '');
mysqli_select_db($link_futech, 'tcc_db');

$aant_verplicht = 0;

$kleur_aankoop = "#A14599";
$kleur_huur = "blue";

// BEGIN toevoegen nieuwe klant
// opslaan van de nieuwe klant
if( isset( $_POST["bewaar"] ) && $_POST["bewaar"] == "Bewaar" )
{
	$error = "";
	if( empty($_POST["n_naam"]) && empty($_POST["n_bedrijf"]) )
	{
		$error = "Naam en/of bedrijf zijn verplicht";
	}else
	{
		// evt. opslaan
		$datum = explode("-", $_POST["nw_offerte_datum"]);
		$_POST["nw_offerte_datum"] = $datum[2] . "-" . $datum[1] ."-" .$datum[0];

		$q_ins = "INSERT INTO kal_customers(cus_naam,
		                                    cus_bedrijf,
		                                    cus_btw,
		                                    cus_straat,
		                                    cus_nr,
		                                    cus_postcode,
		                                    cus_gemeente,
		                                    cus_email,
		                                    cus_tel,
		                                    cus_gsm,
		                                    cus_acma,
		                                    cus_offerte_datum)
		                            VALUES('". htmlentities($_POST["n_naam"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_bedrijf"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_btw"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_straat"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_nr"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_postcode"], ENT_QUOTES) ."',
		                                   '". htmlentities($_POST["n_gemeente"], ENT_QUOTES)."',
		                                   '". $_POST["n_email"] ."',
		                                   '". $_POST["n_tel"] ."',
		                                   '". $_POST["n_gsm"] ."',
		                                   '". $_POST["nw_acma"] ."',
		                                   '". $_POST["nw_offerte_datum"] ."')";

		if( mysqli_query($conn, $q_ins) )
		{
			// get id en redirect to tab 1
			$_POST["klant_id"] = mysqli_insert_id($conn);
			$_POST["cus_id1"] = mysqli_insert_id($conn);
    		$_REQUEST["tab_id"] = '1';
			
			if( !empty( $_POST["nw_acma"] ) )
			{
				$q_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST["nw_acma"]));
                
				$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
				$bericht .= "<tr><td>Beste ". $q_acma->voornaam . " " . $q_acma->naam . " </td></tr>";
				$bericht .= "<tr><td>&nbsp;</td></tr>";
				$bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
				$bericht .= "<tr><td>&nbsp;</td></tr>";
				$bericht .= "<tr><td>Klantgegevens :</td></tr>";
				$bericht .= "<tr><td><b>". $_POST["n_naam"] . " " . $_POST["n_bedrijf"] ."</b></td></tr>";
				$bericht .= "<tr><td><b>". $_POST["n_straat"] . " " . $_POST["n_nr"] ."</b></td></tr>";
				$bericht .= "<tr><td><b>". $_POST["n_postcode"] . " " . $_POST["n_gemeente"] ."</b></td></tr>";
				$bericht .= "<tr><td><b>GSM. : ". $_POST["n_gsm"] ."</b></td></tr>";
				$bericht .= "<tr><td><b>Tel. : ". $_POST["n_tel"] ."</b></td></tr>";
				$bericht .= "</table>";
		
				mail( $q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers );
			}
		}
	}
	echo $error;
}
// EINDE toevoegen nieuwe klant

// START ARRAY VERKOOP
$verkoop_arr["0"] = "N";
$verkoop_arr["1"] = "J, verkoop";
$verkoop_arr["2"] = "J, verhuur";
$verkoop_arr["3"] = "J, RvO";
// EINDE ARRAY VERKOOP

$acmas = array();
// BEGIN meerdere klanten toekennen aan acma
if( isset( $_POST["acma_toekennen"] ) && $_POST["acma_toekennen"] == "Klanten toekennen" )
{
    $q_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['naar_acma']));
    
	// toekennen van een acma
	if( is_array( $_POST["acma_nodig"] ) )
	{
	    include "inc/phpmailer5/class.phpmailer.php";
       
		foreach( $_POST["acma_nodig"] as $acma )
		{
			$acmas[] = $acma;
			$q_klant = "UPDATE kal_customers SET cus_acma = ". $_POST["naar_acma"] ." WHERE cus_id = " . $acma;
			mysqli_query($conn, $q_klant) or die( mysqli_error($conn) );
            
            // mail sturen naar de klant om te verwittigen wie de acma is en indien nodig deze te contacteren.
            $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $acma));
            
            if( !empty( $klant->cus_email ) )
            {
                $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
            	$bericht .= "<tr><td>Beste ". $klant->cus_naam . "</td></tr>";
            	$bericht .= "<tr><td>&nbsp;</td></tr>";
            	$bericht .= "<tr><td>Bedankt voor uw offerte aanvraag.</td></tr>";
                $bericht .= "<tr><td>Gezien de grote drukte zijn we niet meer in de mogelijkheid u binnen de 48 uur te contacteren omtrent uw interesse in zonnepanelen.</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                
                if( $klant->cus_klant_wilt == "Huren" )
                {
                    $bericht .= "<tr><td>Wat extra uitleg omtrent de huurformule</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Bij de huurformule doet u zelf geen investering. Futech installeert de panelen kosteloos op uw dak.</td></tr>";
                    $bericht .= "<tr><td>Het enige wat we vragen is een vaste maandelijkse onderhoudskost die berekend wordt op basis van </td></tr>";
                    $bericht .= "<tr><td>het aantal panelen. In ruil hiervoor krijgt Futech gedurende 20 jaar de certificaten en u krijgt gratis </td></tr>";
                    $bericht .= "<tr><td>stroom.</td></tr>";
                    $bericht .= "<tr><td>In deze maandelijkse onderhoudskost zit alles vervat. Gaat uw omvormer stuk, dan krijgt u kosteloos </td></tr>";
                    $bericht .= "<tr><td>een nieuw toestel. Gaan uw panelen stuk, dan krijgt u kosteloos nieuwe panelen. Wat er ook </td></tr>";
                    $bericht .= "<tr><td>gebeurt, uw maandelijks bedrag blijft constant en is het enige wat u aan Futech dient te betalen.</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>De voordelen van de huurformule verduidelijken wij u graag tijdens een persoonlijk gesprek.</td></tr>";
                    $bericht .= "<tr><td>In een notendop samengevat zal u gemiddeld <b>40% op uw huidige energiekost besparen.</b></td></tr>";
                    $bericht .= "<tr><td>Uw offerte op maat krijgt u na ons bezoek via e-mail toegestuurd. Hierin zal u duidelijk zien wat u </td></tr>";
                    $bericht .= "<tr><td>maandelijks aan onderhoudskost zal betalen.</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Om u louter een idee te geven vindt u <b>in bijlage een voorbeeldofferte</b> voor een gezin dat op </td></tr>";
                    $bericht .= "<tr><td>jaarbasis ongeveer 4250 kwh verbruikt. Dit verbruik loopt bij de gewone energieleverancier al snel op </td></tr>";
                    $bericht .= "<tr><td>tot een kost van 90� per maand. Indien u zonnepanelen laat plaatsen door Futech zal u zelf deze </td></tr>";
                    $bericht .= "<tr><td>energie gaan opwekken. Uiteraard dient u dan geen 90� meer te betalen aan uw energieleverancier. </td></tr>";
                    $bericht .= "<tr><td>In plaats hiervan betaalt u in dit voorbeeld slechts 60� aan Futech en maakt u dus 30� per maand </td></tr>";
                    $bericht .= "<tr><td>winst. </td></tr>";
                    $bericht .= "<tr><td>Deze winst zal in de komende jaren alleen maar stijgen. Wij beloven u namelijk dat de maandelijkse </td></tr>";
                    $bericht .= "<tr><td>onderhoudskost bij Futech gedurende 20 jaar constant blijft, terwijl de stroomprijzen vermoedelijk  </td></tr>";
                    $bericht .= "<tr><td>zullen blijven stijgen. Met andere woorden betaalt het gezin in dit voorbeeld na 20 jaar nog steeds </td></tr>";
                    $bericht .= "<tr><td>60� terwijl de stroomprijzen tegen dan waarschijnlijk enorm gestegen zijn.</td></tr>";
                    $bericht .= "<tr><td>Na 20 jaar bent u zelf eigenaar van de panelen, en dit <b>geheel kostenloos (let wel, dit is een actie die</b> </td></tr>";
                    $bericht .= "<tr><td>enkel de komende 2 weken geldig is).</b> U krijgt de panelen na 20 jaar dus volledig gratis. De panelen </td></tr>";
                    $bericht .= "<tr><td>zijn dan zeker nog niet versleten maar kunnen dan nog gemakkelijk 20 jaar mee. </td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td><b>Hopelijk verduidelijkt deze uitleg al meer en ik hoor het graag van u wanneer u een afspraak wenst  </b></td></tr>";
                    $bericht .= "<tr><td><b>te maken. Op die manier zal u zien welke winst er in uw situatie gemaakt zal worden en hoeveel de  </b></td></tr>";
                    $bericht .= "<tr><td><b>maandelijkse onderhoudskost voor u zal bedragen. </b></td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Indien u hierna interesse heeft in het concept, vragen we u vriendelijk om via mail of </td></tr>";
                    $bericht .= "<tr><td>telefonisch contact op te nemen met de aan u toegewezen account manager zodanig dat er alsnog </td></tr>";
                    $bericht .= "<tr><td>een afspraak gemaakt kan worden.</td></tr>";
                    $bericht .= "<tr><td>U kan uiteraard ook steeds via mail enkele gegevens doorgeven (verbruik, adres, monofase/driefase, </td></tr>";
                    $bericht .= "<tr><td>helling dak, afmetingen dak,�) zodanig dat we u de uitgewerkte offerte via mail kunnen bezorgen.</td></tr>";
                    
                    /*
                    $bericht .= "<tr><td>Vandaar dat u van ons via mail reeds een standaardofferte krijgt met wat uitleg zodat u weet hoe het concept in elkaar zit.</td></tr>";
                    $bericht .= "<tr><td>Indien u hierna interesse heeft in het concept, vragen we u vriendelijk om via mail of telefonisch,</td></tr>";
                    $bericht .= "<tr><td>contact op te nemen met de aan u toegewezen account manager zodanig dat er alsnog een afspraak gemaakt kan worden.</td></tr>";
                    $bericht .= "<tr><td>U kan uiteraard ook steeds via mail enkele gegevens doorgeven (verbruik, adres, monofase/driefase, helling dak, afmetingen dak,�)</td></tr>";
                    $bericht .= "<tr><td>zodanig dat we u de uitgewerkte offerte via mail kunnen terugbezorgen.</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Wat extra uitleg omtrent de huurformule:</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Bij de huurformule doet u zelf geen investering. Futech installeert de panelen kosteloos op uw dak.</td></tr>";
                    $bericht .= "<tr><td>Het enige wat we vragen is een vaste maandelijkse onderhoudskost die berekend wordt op basis van het aantal panelen,</td></tr>";
                    $bericht .= "<tr><td>het type dak,� In ruil hiervoor krijgt Futech gedurende 20 jaar de certificaten en u krijgt gratis stroom.</td></tr>";
                    $bericht .= "<tr><td>In deze maandelijkse onderhoudskost zit alles vervat. Gaat uw omvormer stuk, dan krijgt u kosteloos een nieuw toestel.</td></tr>";
                    $bericht .= "<tr><td>Gaan uw panelen stuk, dan krijgt u kosteloos nieuwe panelen. Wat er ook gebeurt, uw maandelijks bedrag blijft constant</td></tr>";
                    $bericht .= "<tr><td>en is het enige wat u aan Futech dient te betalen.</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Voordelen huurformule (gebaseerd  op voorbeeld in bijlage)</td></tr>";
                    $bericht .= "<tr><td>-	Maandelijkse onderhoudskost in het voorbeeld is 60 euro. Echter iemand met een equivalent verbruik(4250 kwh per jaar),</td></tr>";
                    $bericht .= "<tr><td>   betaalt in realiteit al snel 90 euro per maand �> zonder investering vanaf maand ��n 30 euro( 90-60) winst.</td></tr>";
                    $bericht .= "<tr><td>   Deze winst wordt in de komende 20 jaar alleen maar groter omdat de stroomprijs alleen maar zal stijgen maar</td></tr>";
                    $bericht .= "<tr><td>   de vaste maandelijkse kost aan Futech constant blijft.</td></tr>";
                    $bericht .= "<tr><td>-	Onze maandelijkse onderhoudskost wordt niet ge�ndexeerd. Concreet betekent dit dat wanneer u nu 60 euro moet betalen,</td></tr>";
                    $bericht .= "<tr><td>   u dit binnen 20 jaar nog steeds betaalt. Dit in tegenstelling tot de huidige energieprijzen die de pan uit swingen.</td></tr>";
                    $bericht .= "<tr><td>-	Na 20 jaar krijgt u kosteloos de panelen (opgelet, dit is een actie die enkel de komende 2 weken geldig is)</td></tr>";
                    $bericht .= "<tr><td>&nbsp;</td></tr>";
                    $bericht .= "<tr><td>Hopelijk verduidelijkt deze uitleg al meer en ik hoor het graag van u wanneer u een afspraak wenst te maken</td></tr>";
                    */
                }
                
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                $bericht .= "<tr><td>Uw aanvraag werd doorgestuurd naar onze verkoper :</td></tr>";
                $bericht .= "<tr><td>Naam : " . $q_acma->naam . " " . $q_acma->voornaam . "</td></tr>";
                $bericht .= "<tr><td>Tel : " . $q_acma->tel . "</td></tr>";
                $bericht .= "<tr><td>U kan steeds de verkoper zelf contacteren voor dringende zaken.</td></tr>";
            	$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Uw gegevens :</td></tr>";
        		$bericht .= "<tr><td>". $klant->cus_naam . " " . $klant->cus_bedrijf ."</td></tr>";
        		$bericht .= "<tr><td>". $klant->cus_straat . " " . $klant->cus_nr ."</td></tr>";
        		$bericht .= "<tr><td>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td></tr>";
        		$bericht .= "<tr><td>GSM. : ". $klant->cus_gsm ."</td></tr>";
        		$bericht .= "<tr><td>Tel. : ". $klant->cus_tel ."</td></tr>";
                $bericht .= "<tr><td>&nbsp;</td></tr>";
                
               	$bericht .= "</table>";
            
                $mail = new PHPMailer();
			
        		$mail->From     = $q_acma->email; 
                $mail->FromName = $q_acma->voornaam . " " . $q_acma->naam; 
                $mail->Subject = "Uw offerte aanvraag";
                //$mail->IsSMTP(); 
                
                $mail->Host     = "localhost";
                $mail->IsHTML(true);// send as HTML
                $mail->Mailer   = "sendmail"; 
        
                $text_body  = $bericht;

        		$body = $text_body;
        
                $mail->Body    = $body; 
                $mail->AltBody = $text_body;
                $mail->AddAddress($klant->cus_email, $klant->cus_naam);
                $mail->AddAttachment("downloads/FUTE_magazine_EVV07.pdf");
                
                if( $klant->cus_klant_wilt == "Huren" )
                {
                    $mail->AddAttachment("downloads/standaard_offerte_met_huurvoorstel.pdf");    
                }
                
                $mail->Send();
            }
		}
	}

	// mailen naar de acma dat er nieuwe klanten zijn toegevoegd
	$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
	$bericht .= "<tr><td>Beste ". $q_acma->naam . " " . $q_acma->voornaam . " </td></tr>";
	$bericht .= "<tr><td>&nbsp;</td></tr>";
	$bericht .= "<tr><td>U heeft ��n of meerdere nieuwe klanten toegekend gekregen.</td></tr>";
	$bericht .= "<tr><td>&nbsp;</td></tr>";

	foreach( $acmas as $klant )
	{
		$a_klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $klant));

		$bericht .= "<tr><td><br/>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $a_klant->cus_naam . " " . $a_klant->cus_bedrijf ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $a_klant->cus_straat . " " . $a_klant->cus_nr ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $a_klant->cus_postcode . " " . $a_klant->cus_gemeente ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $a_klant->cus_gsm ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $a_klant->cus_tel ."</b></td></tr>";
	}
	$bericht .= "</table>";

	mail( $q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers );
}
// EINDE meerdere klanten toekennen aan acma

$verwijderen = 0;
if( isset( $_POST["verwijderen"] ) && $_POST["verwijderen"] == "Verwijderen" && $_POST["cus_id"] > 0 )
{
	//$q_del = "DELETE FROM kal_customers WHERE cus_id = " . $_POST["cus_id"];
	$q_del = "UPDATE kal_customers SET cus_active = '0' WHERE cus_id = " . $_POST["cus_id"];

	if( mysqli_query($conn,  $q_del) )
	{
		$verwijderen = 1;
	}
}

// begin sorteren lijst van de omvormers
$list_inv = array();
$list_inv1 = array();

$q_inv = mysqli_query($conn, "SELECT * FROM kal_inverters WHERE active = '1' ORDER BY in_inverter");

while( $inv = mysqli_fetch_object($q_inv) )
{
	$tmp_inv = explode(" ", $inv->in_inverter);
	
	$list_inv[ $inv->in_id ] = (int)$tmp_inv[1];
	$list_inv1[ $inv->in_id ] = $inv->in_inverter;
}

asort( $list_inv );

foreach( $list_inv as $key => $inv )
{
	$list_inv[$key] = $list_inv1[$key];
}
// einde sorteren lijst van de omvormers

if( isset( $_POST["pasaan"] ) && $_POST["pasaan"] == "Wijzig" )
{
	$klant_old_data = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"]));
	
    if( isset( $_POST["datum_arei1"] ) && !empty( $_POST["datum_arei1"] ) )
    {
        $_POST["datum_arei"] = $_POST["datum_arei1"];
    }
    
	// versturen van de mail naar de uitgenodigde
	if( isset( $_POST["invitees"] ) && count( $_POST["invitees"] ) > 0 )
	{
		$invit = array();
		$uitgenodigde = "";
		
		// dubbels er uit halen
		foreach( $_POST["invitees"] as $users )
		{
			$invit[$users] = $users;
		}
		
		// $uitgenodigde
		foreach( $invit as $id )
		{
			$users = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $id));
			$uitgenodigde .= $users->naam ." " . $users->voornaam . ", ";
		}
		
		$acma_inv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
		
		// mail versturen
		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>U werd uitgenodigd voor een afspraak.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>ACMA : ". $acma_inv->voornaam ." " . $acma_inv->naam ."</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Offerte bespreking : ". $_POST["offerte_besproken1"] . " " . $_POST["offerte_besproken2"] . " " . $_POST["offerte_besproken3"] ."</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Uitgenodigden : ". $uitgenodigde ."</td></tr>";
		$bericht .= "</table>";

		// opslaan in de geschiedenis om te melden dat er een mail is verstuurd naar de uitgenodigden
		$q_ins = "INSERT INTO kal_customers_log(cl_cus_id,
		                                        cl_wie,
		                                        cl_veld,
		                                        cl_naar) 
		                                VALUES('".$_POST["cus_id"]."',
		                                       '". $_SESSION["kalender_user"]->user_id ."',
		                                       'uitgenodigde',
		                                       '". $uitgenodigde ."')";
		
		mysqli_query($conn,  $q_ins) or die( mysqli_error($conn) );
		
		foreach( $invit as $id )
		{
			$users = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $id));
			mail( $users->email, "Uitnodiging voor afspraak bij " . $klant_old_data->cus_naam, $bericht, $headers );
		}
	}
    
    if( !empty($_POST["aant_panelen"]) && $_POST["aant_panelen"] != 0 && !empty($_POST["werk_aant_panelen"]) && $_POST["werk_aant_panelen"] != 0 )
    {
        if( $_POST["aant_panelen"] > 0 && $_POST["werk_aant_panelen"] > 0 && $_POST["werk_aant_panelen"] != $klant_old_data->cus_werk_aant_panelen )
        {
            // zoeken ofdat de mail al verstuurd is.
            $aant_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_panelen WHERE cus_id = " . $_POST["cus_id"] . " AND van_panelen = ". $klant_old_data->cus_werk_aant_panelen ." AND aant_panelen = " . $_POST["werk_aant_panelen"]));
            
            if( $aant_zoek == 0 )
            {
                // mail naar de acma
                $acma_inv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
                $bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        		$bericht .= "<tr><td>Beste</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Het aantal panelen is gewijzigd van ".$klant_old_data->cus_werk_aant_panelen." naar ".$_POST["werk_aant_panelen"]." en de prijs dient herberekend te worden.</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>ACMA : ". $acma_inv->voornaam ." " . $acma_inv->naam ."</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
        		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
        		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "</table>";
                
                mail( $acma_inv->email, "Aantal panelen is gewijzigd bij " . $klant_old_data->cus_naam, $bericht, $headers );
                mail( "elise@weygersmontage.be", "Aantal panelen is gewijzigd bij " . $klant_old_data->cus_naam, $bericht, $headers );
            
                // insert rec into new table
                $q_ins = "INSERT INTO kal_cus_panelen(cus_id, van_panelen, aant_panelen) VALUES(". $_POST["cus_id"] .",". $klant_old_data->cus_werk_aant_panelen . "," . $_POST["werk_aant_panelen"] .")";
                mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );    
            }
        }
    }
    
	// begin kmo opslaan van de kortingen
	foreach( $_POST as $key => $post )
	{
		if( substr( $key, 0, 6 ) == 'ascus_' )
		{
			// ascus_5 10
			$tmp = explode("_", $key);
			$as_id = $tmp[1];
			$korting = $post;
			
			if( $korting == '' )
			{
				$korting = 0;
			}
			
			// eerst kijken ofdat deze regel al bestaat, als bestaat dan upd anders insert
			$q_zoek = mysqli_query($conn, "SELECT * FROM kal_as_cus_korting WHERE as_id = " . $as_id . " AND cus_id = " . $_POST["cus_id"]) or die( mysqli_error($conn) );
			
			if( mysqli_num_rows($q_zoek) == 0 )
			{
				// insert
				$q_ins = mysqli_query($conn, "INSERT INTO kal_as_cus_korting(as_id, 
				                                                     cus_id, 
				                                                     korting)
				                                             VALUES(".$as_id.",
				                                                    ".$_POST["cus_id"].",
				                                                    ".$korting.")") or die( mysqli_error($conn) );
				
			}else
			{
				// update
				$q_upd = mysqli_query($conn, "UPDATE kal_as_cus_korting SET korting = " . $korting . " WHERE as_id = ".$as_id." AND cus_id = " . $_POST["cus_id"]) or die( mysqli_error($conn) );
			}
		}
	}
	// eind kmo opslaan van de kortingen.
	
	// nakijken van de installatie datums
	if( $_SESSION["kalender_user"]->group_id != 5 )
	{
		$sub_bericht = "";
		$change = 0;
		
		$dat1 = changeDate2EU( $klant_old_data->cus_installatie_datum );
		if( $dat1 == "00-00-0000" )
		{
			$dat1 = "";
		}
	
		$dat2 = changeDate2EU( $klant_old_data->cus_installatie_datum2 );
		if( $dat2 == "00-00-0000" )
		{
			$dat2 = "";
		}
		
		$dat3 = changeDate2EU( $klant_old_data->cus_installatie_datum3 );
		if( $dat3 == "00-00-0000" )
		{
			$dat3 = "";
		}
		
		$dat4 = changeDate2EU( $klant_old_data->cus_installatie_datum4 );
		if( $dat4 == "00-00-0000" )
		{
			$dat4 = "";
		}
        
		if( $dat1 != $_POST["installatie_datum"] )
		{
			$change = 1;
			$sub_bericht .= "Installatie datum 1 van " . changeDate2EU( $klant_old_data->cus_installatie_datum  ). " naar " . $_POST["installatie_datum"] . "<br/>";
		} 
		
		if( $dat2 != $_POST["installatie_datum2"] )
		{
			$change = 1;
			$sub_bericht .= "Installatie datum 2 van " . changeDate2EU( $klant_old_data->cus_installatie_datum2  ). " naar " . $_POST["installatie_datum2"] . "<br/>";
		} 
		
		if( $dat3 != $_POST["installatie_datum3"] )
		{
			$change = 1;
			$sub_bericht .= "Installatie datum 3 van " . changeDate2EU( $klant_old_data->cus_installatie_datum3  ). " naar " . $_POST["installatie_datum3"] . "<br/>";
		} 
		
		if( $dat4 != $_POST["installatie_datum4"] )
		{
			$change = 1;
			$sub_bericht .= "Installatie datum 4 van " . changeDate2EU( $klant_old_data->cus_installatie_datum4  ). " naar " . $_POST["installatie_datum4"] . "<br/>";
		}
		
		if( $change == 1 )
		{
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Beste</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>De installatiedatum(s) zijn gewijzigd.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>". $sub_bericht ."</td></tr>";
			$bericht .= "</table>";
	
			mail( "elise@weygersmontage.be, jolien.roosen@futech.be", "Wijziging installatiedatum(s) " . $klant_old_data->cus_naam, $bericht, $headers );
		}
	}
	
	// BEGIN mail sturen naar pieter wanneer werkdocument klaar verandert van nee naar ja
	if( $klant_old_data->cus_werkdoc_klaar == '0' && $_POST["werkdocument_klaar"] == '1' )
	{
		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste Pieter</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Het werkdocument van onderstaande klant dient nog gecontrolleerd te worden.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
		$bericht .= "</table>";

		
		// entry toevoegen om dit werkdocument in een lijst te plaatsen van nog te controleren werkdocumenten
		mysqli_query($conn, "INSERT INTO kal_check_werkdoc( cw_cus_id ) VALUES(". $_POST["cus_id"] . ")") or die( mysqli_error($conn) ); 
	}
	// EINDE mail sturen naar pieter wanneer werkdocument klaar verandert van nee naar ja
	
	// verwijderen van de entry indien werkdoc_klaar gaat van ja -> nee
	if( $klant_old_data->cus_werkdoc_klaar == '1' && $_POST["werkdocument_klaar"] == '0' )
	{
		mysqli_query($conn, "DELETE FROM kal_check_werkdoc WHERE cw_cus_id = " . $_POST["cus_id"] . " LIMIT 1") or die( mysqli_error($conn) );
	}
	// einde verwijderen van de entry
	
	// BEGIN mail sturen naar elise wanneer werkdocument gecontrolleerd is
	if( $klant_old_data->cus_werkdoc_check == '0' && ( $_POST["werkdoc_check"] == 'on' || $_POST["werkdoc_check"] == '1' ) )
	{
		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Het werkdocument van onderstaande klant is klaar.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
		$bericht .= "</table>";

    	mail( "elise@weygersmontage.be", "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );
		mail( "jolien@futech.be", "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );

		//mail( "dimitri@futech.be", "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );
		
		// opzoeken van de acma en deze ook een mail sturen.
		$zoek_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
		mail( $zoek_acma->email, "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );
        //mail( "dimitri@futech.be", "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );
	}
	// EINDE mail sturen naar elise wanneer werkdocument gecontrolleerd is
	
	// opslaan van de serienummers van de omvormers
	//- Eerst verwijderen van de nummers
	$q_del_omv = mysqli_query($conn, "DELETE FROM kal_customers_omvormers WHERE co_cus_id = " . $_POST["cus_id"]);
	
	//-Toevoegen van de omvormers
	$aantal_omv = $_POST["aantal_omv"];
	
	for( $j=1;$j<=$aantal_omv; $j++ )
	{
		$q_ins = "INSERT INTO kal_customers_omvormers(  co_cus_id,
														co_omvormer,
														co_sn,
                                                        co_text,
														co_user_id) 
											   VALUES(". $_POST["cus_id"] .",
											          '". $_POST["omv".$j] ."',
											          '". $_POST["sn".$j] . "',
                                                      '". $_POST["text".$j] . "',
											          '". $_SESSION["kalender_user"]->user_id ."')";
                                                      
		if( !empty( $_POST["sn".$j] ) ||!empty( $_POST["text".$j] ) )
		{
			mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );	
		}
	}
	// einde opslaan van de serienummers van de omvormers
		
	// verwijderen van de uitbreiding
	if( !isset( $_POST["maak_uitbreiding"] ) )
	{
		// zoeken of er een uitbreiding is
		$q_zoek_uit = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"]);
		$aantal_uit = mysqli_num_rows($q_zoek_uit);
		
		// Als er een uitbreiding is gevonden dan deze verwijderen
		if( $aantal_uit == 1 )
		{
			$uit = mysqli_fetch_object($q_zoek_uit);
			
			if( is_numeric( $uit->cus_id ) && $uit->cus_id > 0 )
			{
				$q_del = mysqli_query($conn, "UPDATE kal_customers SET cus_active = '0' WHERE cus_id = " . $uit->cus_id);
			}
			
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "Uitbreiding", "1", "0", $conn);
		}
	}
	// EINDE verwijderen van de uitbreiding
	
	// mailen naar admin, ismael, elise dat er een overeenkomst is met een klant
	if( $_POST["verkoop"] == '1' )
	{
		$q_verkoop = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_verkoop WHERE cv_cus_id = " . $_POST["cus_id"]));
		
		if( $q_verkoop == 0 )
		{
			// toevoegen
			$q_ins = mysqli_query($conn, "INSERT INTO kal_cus_verkoop(cv_cus_id) VALUES('". $_POST["cus_id"] ."')");
            $acma_inv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
			
			// mailen
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Deze klant werd op verkoop gezet door <b>". $acma_inv->naam . " " . $acma_inv->voornaam ."</b>.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "administratie@futech.be", "Nieuwe Verkoop - " . $_POST["naam"], $bericht, $headers );
			mail( "ismael@futech.be", "Nieuwe Verkoop - " . $_POST["naam"], $bericht, $headers );
			
			// mailen naar elise, nog op te meten
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Onderstaande klant werd op verkoop gezet, en dient nog opgemeten te worden.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "elise@weygersmontage.be", "Nog op te meten - " . $_POST["naam"], $bericht, $headers );
		}
	}
	
	if( $_POST["verkoop"] == '2' )
	{
		$q_verkoop = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_verkoop WHERE cv_cus_id = " . $_POST["cus_id"]));
		
		if( $q_verkoop == 0 )
		{
			// toevoegen
			$q_ins = mysqli_query($conn, "INSERT INTO kal_cus_verkoop(cv_cus_id) VALUES('". $_POST["cus_id"] ."')");
			$acma_inv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
            
			// mailen
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Deze klant heeft een huurovereenkomst afgesloten bij <b>". $acma_inv->naam . " " . $acma_inv->voornaam ."</b>.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "administratie@futech.be", "Nieuwe Verhuur - " . $_POST["naam"], $bericht, $headers );
			mail( "ismael@futech.be", "Nieuwe Verhuur - " . $_POST["naam"], $bericht, $headers );
			
			// mailen naar elise, nog op te meten
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Onderstaande klant heeft een huurovereenkomst, en dient nog opgemeten te worden.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "elise@weygersmontage.be", "Nog op te meten - " . $_POST["naam"], $bericht, $headers );
		}
	}
	// einde mailen van de huur overeenkomst 
	
    // Uitbreiding op verkoop gezet.
    if( $_POST["uit_verkoop"] == '1' )
	{
        // opzoeken 
        // ophalen van de uitbreiding.
        $cus1 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
        $cus2 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
		$q_verkoop = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_verkoop WHERE cv_cus_id = " . $cus2->cus_id));
		
		if( $q_verkoop == 0 )
		{
			// toevoegen
			$q_ins = mysqli_query($conn, "INSERT INTO kal_cus_verkoop(cv_cus_id) VALUES('". $cus2->cus_id ."')");
			
			// mailen
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Deze klant werd op verkoop gezet.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Uitbreiding - Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_naam . " " . $cus1->cus_bedrijf ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_straat . " " . $cus1->cus_nr ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_postcode . " " . $cus1->cus_gemeente ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $cus1->cus_gsm ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $cus1->cus_tel ."</b></td></tr>";
			$bericht .= "</table>";
	
            
			mail( "administratie@futech.be", "Nieuwe Verkoop bij uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
			mail( "ismael@futech.be", "Nieuwe Verkoop bij uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
			
            //mail( "dimitri@futech.be", "Nieuwe Verkoop bij uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
            
			// mailen naar elise, nog op te meten
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Onderstaande klant werd op verkoop gezet, en dient nog opgemeten te worden.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Uitbreiding - Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_naam . " " . $cus1->cus_bedrijf ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_straat . " " . $cus1->cus_nr ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_postcode . " " . $cus1->cus_gemeente ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $cus1->cus_gsm ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $cus1->cus_tel ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "elise@weygersmontage.be", "Nog op te meten Uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
            //mail( "dimitri@futech.be", "Nog op te meten Uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
		}
	}
    // einde uitbreiding op verkoop gezet
    
    if( $_POST["uit_verkoop"] == '2' )
	{
        $cus1 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
        $cus2 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
		$q_verkoop = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_verkoop WHERE cv_cus_id = " . $cus2->cus_id));
		
		if( $q_verkoop == 0 )
		{
			// toevoegen
			$q_ins = mysqli_query($conn, "INSERT INTO kal_cus_verkoop(cv_cus_id) VALUES('". $cus2->cus_id ."')");
			
			// mailen
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Deze klant heeft een huurovereenkomst afgesloten.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Uitbreiding - Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_naam . " " . $cus1->cus_bedrijf ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_straat . " " . $cus1->cus_nr ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_postcode . " " . $cus1->cus_gemeente ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $cus1->cus_gsm ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $cus1->cus_tel ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "administratie@futech.be", "Nieuwe Verhuur Uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
			mail( "ismael@futech.be", "Nieuwe Verhuur Uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
			
            //mail( "dimitri@futech.be", "Nieuwe Verhuur Uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
            
			// mailen naar elise, nog op te meten
			$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
			$bericht .= "<tr><td>Onderstaande klant heeft een huurovereenkomst, en dient nog opgemeten te worden.</td></tr>";
			$bericht .= "<tr><td>&nbsp;</td></tr>";
			$bericht .= "<tr><td>Uitbreiding - Klantgegevens :</td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_naam . " " . $cus1->cus_bedrijf ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_straat . " " . $cus1->cus_nr ."</b></td></tr>";
			$bericht .= "<tr><td><b>". $cus1->cus_postcode . " " . $cus1->cus_gemeente ."</b></td></tr>";
			$bericht .= "<tr><td><b>GSM. : ". $cus1->cus_gsm ."</b></td></tr>";
			$bericht .= "<tr><td><b>Tel. : ". $cus1->cus_tel ."</b></td></tr>";
			$bericht .= "</table>";
	
			mail( "elise@weygersmontage.be", "Nog op te meten uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
            //mail( "dimitri@futech.be", "Nog op te meten uitbreiding - " . $cus1->cus_naam . " " . $cus1->cus_bedrijf, $bericht, $headers );
		}
	}
	// einde mailen van de huur overeenkomst 
    
	// eerst de klant ophalen en zo nakijken welke velden er gewijzigd zijn geweest

	// als nieuwe of andere acma, dan moet deze een mail krijgen met de melding van een nieuwe klant
	//echo "<br>1" . $klant_old_data->cus_acma . "1 2" . $_POST["acma"] . "2";

	if( empty($klant_old_data->cus_acma) && !empty( $_POST["acma"] ) )
	{
		// toekennen van een acma
		$q_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['acma']));

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma->naam . " " . $q_acma->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma->email, "Nieuwe klant toegevoegd", $bericht, $headers );
	}
    
    $cus1 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
    $cus2 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"] . " AND cus_active = '1' "));
    
    if( empty($cus2->cus_acma) && !empty( $_POST["uit_acma"] ) )
	{
		// toekennen van een acma
		$q_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['uit_acma']));

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma->naam . " " . $q_acma->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>U heeft een uitbreiding toegekend gekregen.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma->email, "Nieuwe uitbreiding toegevoegd", $bericht, $headers );
        //mail( "dimitri@futech.be", "Nieuwe uitbreiding toegevoegd", $bericht, $headers );
	}
    
    if( !empty($cus2->cus_acma) && !empty( $_POST["uit_acma"] ) && $cus2->cus_acma != $_POST["uit_acma"] )
	{
		// toekennen van een andere acma
		// mailen naar nieuwe en mailen naar de oude acma
		$q_acma_new = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['uit_acma']));
		$q_acma_old = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $cus2->cus_acma));

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma_new->naam . " " . $q_acma_new->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma_new->email, "Nieuwe uitbreiding toegevoegd", $bericht, $headers );
		//mail( "dimitri@futech.be", "Nieuwe uitbreiding toegevoegd", $bericht, $headers );

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma_old->naam . " " . $q_acma_old->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Deze klant werd weggenomen bij u en geplaatst bij ". $q_acma_new->naam . " " . $q_acma_new->voornaam .".</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma_old->email, "Uitbreiding weggenomen", $bericht, $headers );
		//mail( "dimitri@futech.be", "Uitbreiding weggenomen", $bericht, $headers );
	}
	// einde mailen naar nieuwe acma

	if( !empty($klant_old_data->cus_acma) && !empty( $_POST["acma"] ) && $klant_old_data->cus_acma != $_POST["acma"] )
	{
		// toekennen van een andere acma
		// mailen naar nieuwe en mailen naar de oude acma
		$q_acma_new = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $_POST['acma']));
		$q_acma_old = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma_new->naam . " " . $q_acma_new->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>U heeft een nieuwe klant toegekend gekregen.</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma_new->email, "Nieuwe klant toegevoegd", $bericht, $headers );
		//mail( "dimitri@futech.be", "Nieuwe klant toegevoegd", $bericht, $headers );

		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
		$bericht .= "<tr><td>Beste ". $q_acma_old->naam . " " . $q_acma_old->voornaam . " </td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Deze klant werd weggenomen bij u en geplaatst bij ". $q_acma_new->naam . " " . $q_acma_new->voornaam .".</td></tr>";
		$bericht .= "<tr><td>&nbsp;</td></tr>";
		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
		$bericht .= "<tr><td><b>". $_POST["naam"] . " " . $_POST["bedrijf"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["straat"] . " " . $_POST["nr"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>". $_POST["postcode"] . " " . $_POST["gemeente"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>GSM. : ". $_POST["gsm"] ."</b></td></tr>";
		$bericht .= "<tr><td><b>Tel. : ". $_POST["tel"] ."</b></td></tr>";
		$bericht .= "</table>";

		mail( $q_acma_old->email, "Klant weggenomen", $bericht, $headers );
		//mail( "dimitri@futech.be", "Klant weggenomen", $bericht, $headers );
	}
	// einde mailen naar nieuwe acma

	// VERWIJDEREN OFFERTE FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 12) == "offerte_del_" )
		{
			// opzoeken record
			$id = substr( $key, 12 );
			$offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'offerte' AND cf_id = " . $id));
			
			// record verwijderen
			$q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/offerte/" . $offerte->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/offerte/" . $offerte->cf_file  );
			}
		}
	}
	// EINDE VERWIJDEREN OFFERTE FILE
	
	// TOEVOEGEN offerte file
	$offerte_file = "";
	$offerte_filename = "";

	if( $_FILES["offerte"]["tmp_name"] !=  "" )
	{
		$offerte_file = "";
		$offerte_filename = $_FILES["offerte"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "offerte" );
		chdir( "offerte" );
		move_uploaded_file( $_FILES['offerte']['tmp_name'], $offerte_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'offerte',
		                                                             '".$offerte_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN offerte file
    
   	// VERWIJDEREN extra FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 15) == "file_extra_del_" )
		{
			// opzoeken record
			$id = substr( $key, 15 );
			$offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'file_extra' AND cf_id = " . $id));
			
			// record verwijderen
			$q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/file_extra/" . $offerte->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/file_extra/" . $offerte->cf_file  );
			}
		}
	}
	// EINDE VERWIJDEREN extra FILE
    
    // TOEVOEGEN extra file file_extra
	$extra_filename = "";

	if( $_FILES["file_extra"]["tmp_name"] !=  "" )
	{
		$extra_filename = $_FILES["file_extra"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "file_extra" );
		chdir( "file_extra" );
		move_uploaded_file( $_FILES['file_extra']['tmp_name'], $extra_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'file_extra',
		                                                             '".$extra_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN extra file
	
	// VERWIJDEREN orderbon FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 10) == "order_del_" )
		{
			// opzoeken record
			$id = substr( $key, 10 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'orderbon' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $order->cf_file  );
			}
		}
	}
	// EINDE VERWIJDEREN orderbon FILE
	
    // toevoegen hypotheek
    $hypo_file = "";
	$hypo_filename = "";

	if( $_FILES["hypotheek"]["tmp_name"] !=  "" )
	{
		$hypo_file = "";
		$hypo_filename = $_FILES["hypotheek"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "hypotheek" );
		chdir( "hypotheek" );
		move_uploaded_file( $_FILES['hypotheek']['tmp_name'], $hypo_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'hypotheek',
		                                                             '".$hypo_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN hypotheek file
	
	// VERWIJDEREN hypotheek FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 14) == "hypotheek_del_" )
		{
			// opzoeken record
			$id = substr( $key, 14 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'hypotheek' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/hypotheek/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/hypotheek/" . $order->cf_file  );
			}
		}
	}
    // einde verwijderen hypotheek
    
    // toevoegen eigendomsacte
    $eigendom_file = "";
	$eigendom_filename = "";

	if( $_FILES["eigendom"]["tmp_name"] !=  "" )
	{
		$eigendom_file = "";
		$eigendom_filename = $_FILES["eigendom"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "eigendom" );
		chdir( "eigendom" );
		move_uploaded_file( $_FILES['eigendom']['tmp_name'], $eigendom_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'eigendom',
		                                                             '".$eigendom_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN eigendomsacte file
	
	// VERWIJDEREN eigendomsacte FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 13) == "eigendom_del_" )
		{
			// opzoeken record
			$id = substr( $key, 13 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'eigendom' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/eigendom/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/eigendom/" . $order->cf_file  );
			}
		}
	}
    // einde verwijderen eigendomsacte
    
    // toevoegen isolatie
    $isolatie_file = "";
	$isolatie_filename = "";

	if( $_FILES["isolatie"]["tmp_name"] !=  "" )
	{
		$isolatie_file = "";
		$isolatie_filename = $_FILES["isolatie"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "isolatie" );
		chdir( "isolatie" );
		move_uploaded_file( $_FILES['isolatie']['tmp_name'], $isolatie_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'isolatie',
		                                                             '".$isolatie_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN isolatie file
	
	// VERWIJDEREN isolatie FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 13) == "isolatie_del_" )
		{
			// opzoeken record
			$id = substr( $key, 13 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'isolatie' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/isolatie/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/isolatie/" . $order->cf_file  );
			}
		}
	}
    // einde verwijderen isolatie
    
    // toevoegen loonfiche
    $loonfiche_file = "";
	$loonfiche_filename = "";

	if( $_FILES["loonfiche"]["tmp_name"] !=  "" )
	{
		$loonfiche_file = "";
		$loonfiche_filename = $_FILES["loonfiche"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "loonfiche" );
		chdir( "loonfiche" );
		move_uploaded_file( $_FILES['loonfiche']['tmp_name'], $loonfiche_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'loonfiche',
		                                                             '".$loonfiche_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN loonfiche file
	
	// VERWIJDEREN loonfiche FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 14) == "loonfiche_del_" )
		{
			// opzoeken record
			$id = substr( $key, 14 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'loonfiche' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/loonfiche/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/loonfiche/" . $order->cf_file  );
			}
		}
	}
    // einde verwijderen loonfiche
    
    // toevoegen alg. vw
    $vol_off_file = "";
	$vol_off_filename = "";

	if( $_FILES["vol_off"]["tmp_name"] !=  "" )
	{
		$vol_off_file = "";
		$vol_off_filename = $_FILES["vol_off"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "vol_off" );
		chdir( "vol_off" );
		move_uploaded_file( $_FILES['vol_off']['tmp_name'], $vol_off_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'vol_off',
		                                                             '".$vol_off_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN alg. vw. file
	
	// VERWIJDEREN alg. vw. FILE
	foreach( $_POST as $key => $post )
	{
		if( substr($key, 0, 12) == "vol_off_del_" )
		{
			// opzoeken record
			$id = substr( $key, 12 );
			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'vol_off' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/vol_off/" . $order->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/vol_off/" . $order->cf_file  );
			}
		}
	}
    // einde verwijderen als.vw
    
	// TOEVOEGEN orderbon file
	$order_file = "";
	$order_filename = "";

	if( $_FILES["orderbon"]["tmp_name"] !=  "" )
	{
		$order_file = "";
		$order_filename = $_FILES["orderbon"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "orderbon" );
		chdir( "orderbon" );
		move_uploaded_file( $_FILES['orderbon']['tmp_name'], $order_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'orderbon',
		                                                             '".$order_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN orderbon file
	
	$werkdoc_file = "";
	$werkdoc_filename = "";
	if( $_FILES["werkdocument_file"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_werkdoc_filename  ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/" . $klant_old_data->cus_werkdoc_filename  );
		}

		//$werkdoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["werkdocument_file"]["tmp_name"] ));
		$werkdoc_file = "";
		$werkdoc_filename = $_FILES["werkdocument_file"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "werkdocument_file" );
		chdir( "werkdocument_file" );
		move_uploaded_file( $_FILES['werkdocument_file']['tmp_name'], $werkdoc_filename );
		chdir("../../../");
	}

	$areidoc_file = "";
	$areidoc_filename = "";
	if( $_FILES["doc_arei"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_areidoc_filename   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_arei/" . $klant_old_data->cus_areidoc_filename   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$areidoc_file = "";
		$areidoc_filename = $_FILES["doc_arei"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_arei" );
		chdir( "doc_arei" );
		move_uploaded_file( $_FILES['doc_arei']['tmp_name'], $areidoc_filename );
		chdir("../../../");
	}
	
	// begin toevoegen elec schema
	$elecdoc_file = "";
	$elecdoc_filename = "";
	if( $_FILES["doc_elec"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_elecdoc_filename   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_elec/" . $klant_old_data->cus_elecdoc_filename   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$elecdoc_file = "";
		$elecdoc_filename = $_FILES["doc_elec"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_elec" );
		chdir( "doc_elec" );
		move_uploaded_file( $_FILES['doc_elec']['tmp_name'], $elecdoc_filename );
		chdir("../../../");

	}
	// einde toevoegen elec schema

	$gemeentedoc_file = "";
	$gemeentedoc_filename = "";
	if( $_FILES["doc_gemeente"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_gemeentedoc_filename   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_gemeente/" . $klant_old_data->cus_gemeentedoc_filename   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$gemeentedoc_file = "";
		$gemeentedoc_filename = $_FILES["doc_gemeente"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_gemeente" );
		chdir( "doc_gemeente" );
		move_uploaded_file( $_FILES['doc_gemeente']['tmp_name'], $gemeentedoc_filename );
		chdir("../../../");
	}

	$bouwdoc_file = "";
	$bouwdoc_filename = "";
	if( $_FILES["doc_bouwver"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_bouwvergunning_filename   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_bouwver/" . $klant_old_data->cus_bouwvergunning_filename   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$bouwdoc_file = "";
		$bouwdoc_filename = $_FILES["doc_bouwver"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_bouw" );
		chdir( "doc_bouw" );
		move_uploaded_file( $_FILES['doc_bouwver']['tmp_name'], $bouwdoc_filename );
		chdir("../../../");
	}

	$stringdoc_file = "";
	$stringdoc_filename = "";
	if( $_FILES["doc_string"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_stringdoc_filename   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_string/" . $klant_old_data->cus_stringdoc_filename   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$stringdoc_file = "";
		$stringdoc_filename = $_FILES["doc_string"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_string" );
		chdir( "doc_string" );
		move_uploaded_file( $_FILES['doc_string']['tmp_name'], $stringdoc_filename );
		chdir("../../../");
	}

	$werkdoc_file1 = "";
	$werkdoc_filename1 = "";
	if( $_FILES["werkdoc_pic1"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_werkdoc_pic1   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic1/" . $klant_old_data->cus_werkdoc_pic1   );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$werkdoc_file1 = "";
		$werkdoc_filename1 = $_FILES["werkdoc_pic1"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "werkdocument_file" );
		chdir( "werkdocument_file" );
		@mkdir( "pic1" );
		chdir( "pic1" );
		move_uploaded_file( $_FILES['werkdoc_pic1']['tmp_name'], $werkdoc_filename1 );
		chdir("../../../../");
	}

	$werkdoc_file2 = "";
	$werkdoc_filename2 = "";
	if( $_FILES["werkdoc_pic2"]["tmp_name"] != "" )
	{
		if( !empty( $klant_old_data->cus_werkdoc_pic2   ) )
		{
			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic2/" . $klant_old_data->cus_werkdoc_pic2 );
		}

		//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
		$werkdoc_file2 = "";
		$werkdoc_filename2 = $_FILES["werkdoc_pic2"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "werkdocument_file" );
		chdir( "werkdocument_file" );
		@mkdir( "pic2" );
		chdir( "pic2" );
		move_uploaded_file( $_FILES['werkdoc_pic2']['tmp_name'], $werkdoc_filename2 );
		chdir("../../../../");
	}

	// TOEVOEGEN offerte file
	$factuur_file = "";
	$factuur_filename = "";

	if( $_FILES["doc_factuur"]["tmp_name"] !=  "" )
	{
		$factuur_file = "";
		$factuur_filename = $_FILES["doc_factuur"]["name"];

		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "factuur" );
		chdir( "factuur" );
		move_uploaded_file( $_FILES['doc_factuur']['tmp_name'], $factuur_filename );
		chdir("../../../");
		
		// toevoegen in de nieuwe tabel
		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
		                                                              cf_soort, 
		                                                              cf_file) 
		                                                      VALUES('".$_POST["cus_id"]."',
		                                                             'factuur',
		                                                             '".$factuur_filename."')") or die( mysqli_error($conn) );
	}
	// EINDE TOEVOEGEN offerte file
	
	foreach( $_POST as $key => $post )
	{
		// VERWIJDEREN factuur FILE
		if( substr($key, 0, 12) == "factuur_del_" )
		{
			// opzoeken record
			$id = substr( $key, 12 );
			$factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $factuur->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
            // als deze rec in kal_fac_huur staat, dan deze regel ook verwijderen
            $zoek_fac_huur = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_fac_huur WHERE cf_id = " . $factuur->cf_id));
            
            if( $zoek_fac_huur > 0 )
            {
                $q_del = "DELETE FROM kal_fac_huur WHERE cf_id = " . $factuur->cf_id;
                mysqli_query($conn, $q_del) or die( mysqli_error($conn) );
            }
            
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/factuur/" . $factuur->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/factuur/" . $factuur->cf_file  );
			}
		}
		// EINDE VERWIJDEREN factuur FILE
		
		// VERWIJDEREN cn FILE
		if( substr($key, 0, 7) == "cn_del_" )
		{
			// opzoeken record
			$id = substr( $key, 7 );
			$factuur = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'creditnota' AND cf_id = " . $id));
			
			// record verwijderen
			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $factuur->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/creditnota/" . $factuur->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/creditnota/" . $factuur->cf_file  );
			}
		}
		// EINDE VERWIJDEREN cn FILE
		
		// begin verwijderen distri offerte
		if( substr($key, 0, 15) == "distri_off_del_" )
		{
			// opzoeken record
			$id = substr( $key, 15 );
			$distri_off = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_offerte' AND cf_id = " . $id));
			
			// record verwijderen
			$q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $distri_off->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/doc_distri/" . $distri_off->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/doc_distri/" . $distri_off->cf_file  );
			}
		}
		// einde verwijderen distri offerte

		// begin verwijderen distri leverbon
		if( substr($key, 0, 15) == "distri_bon_del_" )
		{
			// opzoeken record
			$id = substr( $key, 15 );
			$distri_off = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'distri_bestelbon' AND cf_id = " . $id));
			
			// record verwijderen
			$q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $distri_off->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
			
			// bestand verwijderen 
			if( file_exists( "cus_docs/" . $_POST["cus_id"] . "/bon_distri/" . $distri_off->cf_file ) )
			{
				unlink("cus_docs/" . $_POST["cus_id"] . "/bon_distri/" . $distri_off->cf_file  );
			}
		}
		// einde verwijderen distri leverbon
	}
	
	// als er als een bestand is geupload en er wordt een nieuwe bestand geupload zonder het oude te verwijderen, dan moet eerst het oude bestand verwijdert worden.
	// OF als isset bestand verwijderen.
	if( (isset( $_FILES["doc_opmeting"]["tmp_name"] ) && !empty($_FILES["doc_opmeting"]["tmp_name"]) && !empty( $klant_old_data->cus_opmetingdoc_filename )) || isset( $_POST["opmetingdoc_del"] ) )
	{
		$q_upd6 = "UPDATE kal_customers SET cus_opmetingdoc_filename = ''
	                              WHERE cus_id = " . $_POST["cus_id"];
	
		mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );

		// verwijderen loggen
		customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_opmetingdoc_filename", $klant_old_data->cus_opmetingdoc_filename, "", $conn);
			
		unlink("cus_docs/" . $_POST["cus_id"] . "/doc_opmeting/" . $klant_old_data->cus_opmetingdoc_filename );
	}
	
	// toevoegen van het stringopmetingsrapport
	$opmetingdoc_filename = "";
	if( $_FILES["doc_opmeting"]["tmp_name"] != "" )
	{
		$opmetingdoc_filename = $_FILES["doc_opmeting"]["name"];
		
		chdir( "cus_docs/");
		@mkdir( $_POST["cus_id"] );
		chdir( $_POST["cus_id"]);
		@mkdir( "doc_opmeting" );
		chdir( "doc_opmeting" );
		move_uploaded_file( $_FILES['doc_opmeting']['tmp_name'], $opmetingdoc_filename );
		chdir("../../../");
		
		$q_upd6 = "UPDATE kal_customers 
		              SET cus_opmetingdoc_filename  = '" . $opmetingdoc_filename ."'
	                WHERE cus_id = " . $_POST["cus_id"];
	
		mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn));	
		
		// toevoegen loggen
		customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_opmetingdoc_filename", $klant_old_data->cus_opmetingdoc_filename, $opmetingdoc_filename, $conn);
	}

	// begin keuze type omvormers
	$werk_omvormers = "";
	if( count( $_POST["werk_omvormers"] ) > 0 )
	{
		foreach( $_POST["werk_omvormers"] as $omv )
		{
			if( $omv > 0 )
			{
				$werk_omvormers .= $omv . "@";
			}
		}
		$werk_omvormers = substr( $werk_omvormers, 0, -1 );
	}
	// einde keuze type omvormers

	$offerte_besproken = $_POST["offerte_besproken1"] . "@" . $_POST["offerte_besproken2"] . "@" . $_POST["offerte_besproken3"];
	$_POST["offerte_datum"] = changeDate2EU($_POST["offerte_datum"]);
	$_POST["offerte_gemaakt"] = changeDate2EU($_POST["offerte_gemaakt"]);
	$_POST["datum_vreg"] = changeDate2EU($_POST["datum_vreg"]);
	$_POST["gecontacteerd"] = changeDate2EU($_POST["gecontacteerd"]);
	$_POST["installatie_datum"] = changeDate2EU($_POST["installatie_datum"]);
	$_POST["installatie_datum2"] = changeDate2EU($_POST["installatie_datum2"]);
	$_POST["installatie_datum3"] = changeDate2EU($_POST["installatie_datum3"]);
	$_POST["installatie_datum4"] = changeDate2EU($_POST["installatie_datum4"]);
	$_POST["nw_installatie_datum"] = changeDate2EU($_POST["nw_installatie_datum"]);
	$_POST["datum_net"] = changeDate2EU( $_POST["datum_net"] );
	$_POST["verkoop_datum"] = changeDate2EU( $_POST["verkoop_datum"] );
	$_POST["installatie_aanp"] = changeDate2EU( $_POST["installatie_aanp"] );
    $_POST["datum_dom"] = changeDate2EU( $_POST["datum_dom"] );
    
	$fac_adres = '0';
	if( isset( $_POST["fac_adres"] ) && $_POST["fac_adres"] == 'on' )
	{
		$fac_adres = '1';
	}

	$set_sunny = '0';
	if( isset( $_POST["sunnybeam"] ) && $_POST["sunnybeam"] == 'on' )
	{
		$set_sunny = '1';
	}
    
    $set_huur_doc = '0';
	if( isset( $_POST["huur_doc"] ) && ($_POST["huur_doc"] == 'on' || $_POST["huur_doc"] == '1' ) )
	{
		$set_huur_doc = '1';
	}
	
	$in_oa = '0';
	if( isset( $_POST["oa"] ) && $_POST["oa"] == 'on' )
	{
		$in_oa = '1';
	}
	
	$ref = "0";
	if( isset( $_POST["ref"] ) && $_POST["ref"] == 'on' )
	{
		$ref = '1';
	}
	
	$werkdoc_check = '0';
	if( $_POST["werkdoc_check"] == 'on' || $_POST["werkdoc_check"] == '1' )
	{
		$werkdoc_check = '1';
		
		// verwijderen uit de lijst van te controleren werkdocument
		mysqli_query($conn, "DELETE FROM kal_check_werkdoc WHERE cw_cus_id = " . $_POST["cus_id"] . " LIMIT 1") or die( mysqli_error($conn) );
	}
	
	$medecontractant = '0';
	if( isset( $_POST["contractant"] ) && $_POST["contractant"] == 'on' )
	{
		$medecontractant = '1';
	}
    
    // berekenen van de looptijd
    $looptijd = 0;
    if( isset( $_POST["looptijd_jaar"] ) && isset( $_POST["looptijd_maand"] ) )
    {
        $looptijd = ( $_POST["looptijd_jaar"] * 12 ) + $_POST["looptijd_maand"];
    }
    
    $schaduw = '0';
    if( isset( $_POST["schaduw"] ) && $_POST["schaduw"] == 'on' )
	{
		$schaduw = '1';
	}
    
    $schaduw_w = '0';
    if( isset( $_POST["winter"] ) && $_POST["winter"] == 'on' )
	{
		$schaduw_w = '1';
	}
    
    $schaduw_z = '0';
    if( isset( $_POST["zomer"] ) && $_POST["zomer"] == 'on' )
	{
		$schaduw_z = '1';
	}
    
    $schaduw_lh = '0';
    if( isset( $_POST["lente_herfst"] ) && $_POST["lente_herfst"] == 'on' )
	{
		$schaduw_lh = '1';
	}
    
    $overschrijving = '0';
    if( isset( $_POST["overschrijving"] ) )
	{
		$overschrijving = '1';
	}

	// begin nakijken wie welke velden heeft aangepast
	$mapping = array();
	
	if( $_SESSION["kalender_user"]->group_id == 5 )
	{
		// Engineering
		$mapping["cus_ingetekend"] = htmlentities($_POST["ingetekend"], ENT_QUOTES);
		$mapping["cus_werk_aant_panelen"] = $_POST["werk_aant_panelen"];
		$mapping["cus_werk_w_panelen"] = $_POST["werk_w_panelen"];
		$mapping["cus_werk_merk_panelen"] = $_POST["werk_merk_panelen"];
		$mapping["cus_werk_aant_omvormers"] = $_POST["werk_aant_omvormers"];
		$mapping["cus_werkdoc_door"] = $_POST["werkdocument_door"];
		$mapping["cus_werkdoc_klaar"] = $_POST["werkdocument_klaar"];
		$mapping["cus_werkdoc_opm"] = $_POST["werkdoc_opm"];
		$mapping["cus_werkdoc_opm2"] = $_POST["cus_werkdoc_opm2"];
		$mapping["cus_ac_vermogen"] = $_POST["ac_vermogen"];
	}else
	{
		$mapping["cus_naam"] = htmlentities($_POST["naam"], ENT_QUOTES);
		$mapping["cus_bedrijf"] = htmlentities($_POST["bedrijf"], ENT_QUOTES);
		$mapping["cus_btw"] = $_POST["btw_edit"];
		$mapping["cus_straat"] = htmlentities($_POST["straat"], ENT_QUOTES);
		$mapping["cus_nr"] = $_POST["nr"];
		$mapping["cus_postcode"] = $_POST["postcode"];
		$mapping["cus_gemeente"] = htmlentities($_POST["gemeente"], ENT_QUOTES);
                $mapping["cus_land"] = htmlentities($_POST["land"], ENT_QUOTES);
		$mapping["cus_email"] = $_POST["email"];
		$mapping["cus_tel"] = $_POST["tel"];
		$mapping["cus_gsm"] = $_POST["gsm"];
		$mapping["cus_acma"] = $_POST["acma"];
		$mapping["cus_contact"] = $_POST["gecontacteerd"];
		$mapping["cus_fac_adres"] = $fac_adres;
		$mapping["cus_fac_naam"] = htmlentities($_POST["fac_naam"], ENT_QUOTES);
		$mapping["cus_fac_straat"] = htmlentities($_POST["fac_straat"], ENT_QUOTES);
		$mapping["cus_fac_nr"] = $_POST["fac_nr"];
		$mapping["cus_fac_postcode"] = $_POST["fac_postcode"];
		$mapping["cus_fac_gemeente"] = htmlentities($_POST["fac_gemeente"], ENT_QUOTES);
                $mapping["cus_fac_land"] = htmlentities($_POST["fac_land"], ENT_QUOTES);
		$mapping["cus_offerte_datum"] = $_POST["offerte_datum"];
		$mapping["cus_offerte_gemaakt"] = $_POST["offerte_gemaakt"];
		$mapping["cus_offerte_besproken"] = $offerte_besproken;
		$mapping["cus_aant_panelen"] = $_POST["aant_panelen"];
		$mapping["cus_type_panelen"] = $_POST["type_panelen"];
		$mapping["cus_w_panelen"] = $_POST["w_panelen"];
		$mapping["cus_merk_panelen"] = $_POST["merk_panelen"];
		$mapping["cus_kwhkwp"] = $_POST["kwhkwp"];
		$mapping["cus_hoek_z"] = $_POST["hoek_z"];
		$mapping["cus_hoek"] = $_POST["hoek"];
		$mapping["cus_soort_dak"] = $_POST["soort_dak"];
		$mapping["cus_prijs_wp"] = $_POST["ppwp"];
		//$mapping["cus_bedrag_excl"] = $_POST["bedrag_excl"];
		$mapping["cus_woning5j"] = $_POST["woning5j"];
		$mapping["cus_opwoning"] = $_POST["opwoning"];
		$mapping["cus_driefasig"] = $_POST["driefasig"];
		$mapping["cus_nzn"] = $_POST["nzn"];
		$mapping["cus_verkoop"] = $_POST["verkoop"];
		$mapping["cus_verkoop_datum"] = $_POST["verkoop_datum"];
		$mapping["cus_reden"] = htmlentities($_POST["reden"], ENT_QUOTES);
		$mapping["cus_datum_orderbon"] = $_POST["datum_orderbon"];
		$mapping["cus_sunnybeam"] = $set_sunny;
		$mapping["cus_werkdoc_check"] = $werkdoc_check;
		$mapping["cus_actie"] = htmlentities($_POST["actie"], ENT_QUOTES);
		$mapping["cus_ingetekend"] = htmlentities($_POST["ingetekend"], ENT_QUOTES);
		$mapping["cus_werkdoc_door"] = $_POST["werkdocument_door"];
		$mapping["cus_werkdoc_klaar"] = $_POST["werkdocument_klaar"];
		$mapping["cus_werkdoc_opm"] = $_POST["werkdoc_opm"];
		$mapping["cus_werkdoc_opm2"] = $_POST["werkdoc_opm2"];
		$mapping["cus_werk_aant_panelen"] = $_POST["werk_aant_panelen"];
		$mapping["cus_werk_w_panelen"] = $_POST["werk_w_panelen"];
		$mapping["cus_werk_merk_panelen"] = $_POST["werk_merk_panelen"];
		$mapping["cus_werk_aant_omvormers"] = $_POST["werk_aant_omvormers"];
		$mapping["cus_ac_vermogen"] = $_POST["ac_vermogen"];
		$mapping["cus_arei"] = $_POST["arei"];
		$mapping["cus_klant_tevree"] = $_POST["klant_tevree"];
		$mapping["cus_tevree_reden"] = htmlentities($_POST["niet_tevree"], ENT_QUOTES);
		$mapping["cus_type_omvormers"] = $werk_omvormers;
		$mapping["cus_opmerkingen"] = htmlentities($_POST["opmerkingen"], ENT_QUOTES);
		$mapping["cus_arei_datum"] = $_POST["datum_arei"];
		$mapping["cus_arei_meterstand"] = $_POST["arei_meterstand"];
		$mapping["cus_vreg_datum"] = $_POST["datum_vreg"];
        $mapping["cus_vreg_un"] = $_POST["vreg_un"];
        $mapping["cus_vreg_pwd"] = $_POST["vreg_pwd"];
		$mapping["cus_datum_net"] = $_POST["datum_net"];
		$mapping["cus_pvz"] = $_POST["pvz"];
		$mapping["cus_ean"] = $_POST["ean"];
		$mapping["cus_reknr"] = $_POST["reknr"];
        $mapping["cus_iban"] = $_POST["iban"];
        $mapping["cus_bic"] = $_POST["bic"];
        $mapping["cus_banknaam"] = $_POST["banknaam"];
		$mapping["cus_opmeting_datum"] = $_POST["opmeting_datum"];
		$mapping["cus_opmeting_door"] = $_POST["opmeting_door"];
		$mapping["cus_installatie_datum"] = $_POST["installatie_datum"];
		$mapping["cus_installatie_datum2"] = $_POST["installatie_datum2"];
		$mapping["cus_installatie_datum3"] = $_POST["installatie_datum3"];
		$mapping["cus_installatie_datum4"] = $_POST["installatie_datum4"];
		$mapping["cus_nw_installatie_datum"] = $_POST["nw_installatie_datum"];
		$mapping["cus_aanp_datum"] = $_POST["installatie_aanp"];
		$mapping["cus_installatie_ploeg"] = $_POST["installatie_ploeg"];
		$mapping["cus_elec"] = $_POST["elec"];
		$mapping["cus_elec_door"] = htmlentities($_POST["elec_door"], ENT_QUOTES);
		$mapping["cus_elec_datum"] = $_POST["elec_datum"];
		$mapping["cus_gemeentepremie"] = $_POST["gem_premie"];
		$mapping["cus_bouwvergunning"] = $_POST["bouwver"];
		$mapping["cus_verkoopsbedrag_excl"] = $_POST["verkoopsbedrag_excl"];
		$mapping["cus_verkoopsbedrag_incl"] = $_POST["verkoopsbedrag_incl"];
		$mapping["cus_bet_termijn"] = $_POST["bet_termijn"];
		$mapping["cus_ref"] = $ref;
		$mapping["cus_ont_huur"] = $_POST["ont_huur"];
		$mapping["cus_bet_huur"] = $_POST["bet_huur"];
        $mapping["cus_looptijd_huur"] = $looptijd;
        $mapping["cus_huur_doc"] = $set_huur_doc;
        $mapping["cus_schaduw"] = $schaduw;
        $mapping["cus_schaduw_w"] = $schaduw_w;
        $mapping["cus_schaduw_z"] = $schaduw_z;
        $mapping["cus_schaduw_lh"] = $schaduw_lh;
        $mapping["cus_dag"] = $_POST["verbruik_dag"];
        $mapping["cus_nacht"] = $_POST["verbruik_nacht"];
        $mapping["cus_dag_tarief"] = $_POST["dag_tarief"];
        $mapping["cus_nacht_tarief"] = $_POST["nacht_tarief"];
        $mapping["cus_vergoeding"] = $_POST["vergoeding"];
        $mapping["cus_dom_datum"] = $_POST["datum_dom"];
        $mapping["cus_overschrijving"] = $overschrijving;
		
		if( $_POST["kent"] == "" )
		{
			$_POST["kent"] = 0;	
		}
		
		$mapping["cus_kent_ons_van"] = $_POST["kent"];
	}
	
	if( !isset( $_POST["bet_termijn"] ) )
	{
		$_POST["bet_termijn"] = $klant_old_data->cus_bet_termijn;
		$mapping["cus_bet_termijn"] = $_POST["bet_termijn"];
	}
	
	foreach( $mapping as $field => $new_value )
	{
        if( $new_value == "--" )
        {
            $new_value = "";            
        }
        
        if( $klant_old_data->$field == "--" )
        {
            $klant_old_data->$field = "";
        }
        
        //echo "<br>" . $klant_old_data->$field . "!=" . $new_value;
        
		if( $klant_old_data->$field != $new_value )
		{
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, $field, $klant_old_data->$field, $new_value, $conn);
		}
	}
	// einde nakijken wie welke velden heeft aangepast
	
	if( $_SESSION["kalender_user"]->group_id == 5 )
	{
		$q_upd = "UPDATE kal_customers SET 	cus_ingetekend = '" .htmlentities($_POST["ingetekend"], ENT_QUOTES). "',
		                               		cus_werkdoc_door = '" . $_POST["werkdocument_door"] . "',
		                               		cus_werkdoc_klaar = '" . $_POST["werkdocument_klaar"] . "',
		                               		cus_werkdoc_opm = '" . $_POST["werkdoc_opm"] ."',
		                               		cus_werkdoc_opm2 = '" . $_POST["werkdoc_opm2"] ."',
		                               		cus_werk_aant_panelen = '" . $_POST["werk_aant_panelen"] . "',
		                               		cus_werk_w_panelen = '" . $_POST["werk_w_panelen"] . "',
		                               		cus_werk_merk_panelen = '" . $_POST["werk_merk_panelen"] . "',
		                               		cus_werk_aant_omvormers = '" . $_POST["werk_aant_omvormers"] . "',
		                               		cus_ac_vermogen = '" . $_POST["ac_vermogen"] . "',
		                               		cus_type_omvormers = '" . $werk_omvormers . "'
		                              WHERE cus_id = " . $_POST["cus_id"];
	}else
	{
        /* Als emailadres wordt gewijzig dan de koppeling naar futech.be ook wijzigen */
        if( $klant_old_data->cus_email != $_POST["email"] )
        {
            $q_upd = "UPDATE UserData SET UserEmail = '" . $_POST["email"] . "' WHERE UserEmail = '" . $klant_old_data->cus_email . "'";
            mysqli_query($link_futech, $q_upd) or die( mysqli_error($link_futech) );
        }
        /* Einde - Als emailadres wordt gewijzig dan de koppeling naar futech.be ook wijzigen */
       
		$q_upd = "UPDATE kal_customers SET 	cus_naam = '". htmlentities($_POST["naam"], ENT_QUOTES) ."',
											cus_bedrijf = '". htmlentities($_POST["bedrijf"], ENT_QUOTES) ."',
		                               		cus_btw = '". $_POST["btw_edit"] ."', 
		                               		cus_medecontractor = '" . $medecontractant . "',
											cus_oa = '" . $in_oa . "',
		                               		cus_oa_bij = '" . $_POST["in_oa_van"] . "',
		                               		cus_straat = '". htmlentities($_POST["straat"], ENT_QUOTES)."',
		                               		cus_nr = '". $_POST["nr"] ."',
		                               		cus_postcode = '". $_POST["postcode"] ."',
		                               		cus_gemeente = '". htmlentities($_POST["gemeente"], ENT_QUOTES) ."',
		                               		cus_email = '". $_POST["email"]."',
		                               		cus_tel = '". $_POST["tel"]."',
		                               		cus_gsm = '". $_POST["gsm"]."',
		                               		cus_kent_ons_van = '" . $_POST["kent"] ."',
		                               		cus_acma = '" . $_POST["acma"] ."',
		                               		cus_contact = '" . $_POST["gecontacteerd"] . "',
		                               		cus_fac_adres = '" . $fac_adres . "',
		                               		cus_fac_naam = '" . htmlentities($_POST["fac_naam"], ENT_QUOTES) . "',
		                               		cus_fac_straat = '". htmlentities($_POST["fac_straat"], ENT_QUOTES)."',
		                               		cus_fac_nr = '". $_POST["fac_nr"] ."',
		                               		cus_fac_postcode = '". $_POST["fac_postcode"] ."',
		                               		cus_fac_gemeente = '". htmlentities($_POST["fac_gemeente"], ENT_QUOTES) ."',
		                               		cus_offerte_datum = '". $_POST["offerte_datum"] ."',
		                               		cus_offerte_gemaakt = '" . $_POST["offerte_gemaakt"] . "',
		                               		cus_offerte_besproken = '" . $offerte_besproken . "',
		                               		cus_aant_panelen = '" . $_POST["aant_panelen"] . "',
		                               		cus_type_panelen = '" . $_POST["type_panelen"] ."',
		                               		cus_w_panelen = '" . $_POST["w_panelen"] . "',
		                               		cus_merk_panelen = '" . $_POST["merk_panelen"] . "',
		                               		cus_kwhkwp = '" . $_POST["kwhkwp"] . "',
		                               		cus_hoek_z = '" . $_POST["hoek_z"] ."',
		                               		cus_hoek = '" . $_POST["hoek"] ."',
                                            cus_schaduw = '" . $schaduw . "',
                                            cus_schaduw_w = '" . $schaduw_w . "',
                                            cus_schaduw_z = '" . $schaduw_z . "',
                                            cus_schaduw_lh = '" . $schaduw_lh . "',
		                               		cus_soort_dak = '" . $_POST["soort_dak"] ."',
                                            cus_dag = '" . $_POST["verbruik_dag"] ."',
                                            cus_nacht = '" . $_POST["verbruik_nacht"] ."',
                                            cus_dag_tarief = '" . $_POST["dag_tarief"] ."',
                                            cus_nacht_tarief = '" . $_POST["nacht_tarief"] ."',
                                            cus_vergoeding = '" . $_POST["vergoeding"] ."',
		                               		cus_prijs_wp = '" . $_POST["ppwp"] ."',
		                               		cus_bedrag_excl = '" . $_POST["bedrag_excl"] . "',
		                               		cus_woning5j = '" . $_POST["woning5j"] . "',
		                               		cus_opwoning = '" . $_POST["opwoning"] . "',
		                               		cus_driefasig = '" . $_POST["driefasig"] . "',
		                               		cus_nzn = '" . $_POST["nzn"] . "',
		                               		cus_verkoop = '" . $_POST["verkoop"] . "',
		                               		cus_ont_huur = '" . $_POST["ont_huur"] ."',	
											cus_bet_huur = '" . $_POST["bet_huur"] . "',
                                            cus_looptijd_huur = '" . $looptijd . "',
                                            cus_huur_doc = '". $set_huur_doc ."',
		                               		cus_verkoop_datum = '" . $_POST["verkoop_datum"] . "',
		                               		cus_reden = '" . htmlentities($_POST["reden"], ENT_QUOTES) . "',
		                               		cus_verkoopsbedrag_excl = '" . $_POST["verkoopsbedrag_excl"] . "',
		                               		cus_verkoopsbedrag_incl = '" . $_POST["verkoopsbedrag_incl"] . "',
		                               		cus_datum_orderbon = '" . $_POST["datum_orderbon"] . "',
		                               		cus_sunnybeam = '" . $set_sunny . "',
		                               		cus_actie = '" . htmlentities($_POST["actie"], ENT_QUOTES)  . "',
		                               		cus_ingetekend = '" .htmlentities($_POST["ingetekend"], ENT_QUOTES). "',
		                               		cus_werkdoc_check = '". $werkdoc_check ."', 
		                               		cus_werkdoc_door = '" . $_POST["werkdocument_door"] . "',
		                               		cus_werkdoc_klaar = '" . $_POST["werkdocument_klaar"] . "',
		                               		cus_werkdoc_opm = '" . $_POST["werkdoc_opm"] ."',
		                               		cus_werkdoc_opm2 = '" . $_POST["werkdoc_opm2"] ."',
		                               		cus_werk_aant_panelen = '" . $_POST["werk_aant_panelen"] . "',
		                               		cus_werk_w_panelen = '" . $_POST["werk_w_panelen"] . "',
		                               		cus_werk_merk_panelen = '" . $_POST["werk_merk_panelen"] . "',
		                               		cus_werk_aant_omvormers = '" . $_POST["werk_aant_omvormers"] . "',
		                               		cus_ac_vermogen = '" . $_POST["ac_vermogen"] . "',
		                               		cus_arei = '" . $_POST["arei"] . "',
		                               		cus_klant_tevree = '" . $_POST["klant_tevree"] . "',
		                               		cus_tevree_reden = '" . htmlentities($_POST["niet_tevree"], ENT_QUOTES) . "',
		                               		cus_type_omvormers = '" . $werk_omvormers . "',
		                               		cus_opmerkingen = '" . htmlentities($_POST["opmerkingen"], ENT_QUOTES) . "',
		                               		cus_arei_datum = '" . $_POST["datum_arei"] . "',
                                            cus_dom_datum = '" . $_POST["datum_dom"] . "',
                                            cus_overschrijving = '". $overschrijving ."',
		                               		cus_arei_meterstand = '" . $_POST["arei_meterstand"] ."',
		                               		cus_vreg_datum = '" . $_POST["datum_vreg"] . "',
                                            cus_vreg_un = '" . $_POST["vreg_un"] . "',
                                            cus_vreg_pwd = '" . $_POST["vreg_pwd"] . "',
                                            cus_vreg_opm = '" . htmlentities($_POST["vreg_opm"], ENT_QUOTES) . "',
		                               		cus_datum_net = '" . $_POST["datum_net"] . "',
                                            cus_netbeheerder = '" . $_POST["netbeheerder"] . "',
		                               		cus_pvz = '" . $_POST["pvz"] . "',
		                               		cus_ean = '". $_POST["ean"] . "',
		                               		cus_reknr = '" . $_POST["reknr"] . "',
                                            cus_iban = '" . $_POST["iban"] . "',
                                            cus_bic = '" . $_POST["bic"] . "',
                                            cus_banknaam = '" . $_POST["banknaam"] . "',
		                               		cus_opmeting_datum = '" . $_POST["opmeting_datum"] . "',
		                               		cus_opmeting_door = '" . $_POST["opmeting_door"] . "',
		                               		cus_installatie_datum = '" . $_POST["installatie_datum"] . "',
		                               		cus_installatie_datum2 = '" . $_POST["installatie_datum2"] . "',
		                               		cus_installatie_datum3 = '" . $_POST["installatie_datum3"] . "',
		                               		cus_installatie_datum4 = '" . $_POST["installatie_datum4"] . "',
		                               		cus_nw_installatie_datum = '" . $_POST["nw_installatie_datum"] . "',
		                               		cus_aanp_datum = '" . $_POST["installatie_aanp"] . "',
		                               		cus_installatie_ploeg = '" . $_POST["installatie_ploeg"] . "',
		                               		cus_elec = '" . $_POST["elec"] . "',
		                               		cus_elec_door = '" . htmlentities($_POST["elec_door"], ENT_QUOTES) . "',
		                               		cus_elec_datum = '" . $_POST["elec_datum"] . "',
		                               		cus_gemeentepremie = '" . $_POST["gem_premie"] . "',
		                               		cus_bouwvergunning = '" . $_POST["bouwver"] . "',
		                               		cus_bet_termijn = '" . $_POST["bet_termijn"] . "',
		                               		cus_ref = '" . $ref ."', 
			                               	cus_ref_lengte = '". $_POST["lengte"] ."',
			                               	cus_ref_breedte = '". $_POST["breedte"] ."'
		                              WHERE cus_id = " . $_POST["cus_id"];

	}

	if( !empty( $_POST["naam"] ) || !empty( $_POST["bedrijf"] ) || $_SESSION["kalender_user"]->group_id == 5 )
	{
		mysqli_query($conn,  $q_upd) or die( mysqli_error($conn) );

		// begin delete van de blobs
		if( isset( $_POST["order_del"] ) )
		{
			$q_upd2 = "UPDATE kal_customers SET cus_order_file = '',
		                               			cus_order_filename = ''
		                              	WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd2) or die( mysqli_error($conn) . "-2" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_order_filename", $klant_old_data->cus_order_filename, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/orderbon/" . $klant_old_data->cus_order_filename );
		}

		if( isset( $_POST["werkdoc_del"] ) )
		{
			$q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '',
		                               			cus_werkdoc_filename = ''
		                             	 WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd3) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_filename", $klant_old_data->cus_werkdoc_filename, "", $conn);
			
			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/" . $klant_old_data->cus_werkdoc_filename );
		}

		if( isset( $_POST["areidoc_del"] ) )
		{
			$q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '',
		                               			cus_areidoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_areidoc_filename", $klant_old_data->cus_areidoc_filename, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_arei/" . $klant_old_data->cus_areidoc_filename );
		}

		if( isset( $_POST["gemeentedoc_del"] ) )
		{
			$q_upd5 = "UPDATE kal_customers SET cus_gemeentedoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-3" );

			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_gemeentedoc_filename", $klant_old_data->cus_gemeentedoc_filename, "", $conn);
			
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_gemeente/" . $klant_old_data->cus_gemeentedoc_filename );
		}

		if( isset( $_POST["bouwverdoc_del"] ) )
		{
			$q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_bouwvergunning_filename", $klant_old_data->cus_bouwvergunning_filename, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_bouw/" . $klant_old_data->cus_bouwvergunning_filename );
		}

		if( isset( $_POST["stringdoc_del"] ) )
		{
			$q_upd6 = "UPDATE kal_customers SET cus_stringdoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );

			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_stringdoc_filename", $klant_old_data->cus_stringdoc_filename, "", $conn);
			
			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_string/" . $klant_old_data->cus_stringdoc_filename );
		}

		if( isset( $_POST["werkdocpic1_del"] ) )
		{
			$q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic1 = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic1", $klant_old_data->cus_werkdoc_pic1, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic1/" . $klant_old_data->cus_werkdoc_pic1 );
		}

		if( isset( $_POST["werkdocpic2_del"] ) )
		{
			$q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic2 = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic2", $klant_old_data->cus_werkdoc_pic2, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/werkdocument_file/pic2/" . $klant_old_data->cus_werkdoc_pic2 );
		}

		if( isset( $_POST["elecdoc_del"] ) )
		{
			$q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = ''
		                              WHERE cus_id = " . $_POST["cus_id"];

			mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-3" );
			
			// verwijderen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_elecdoc_filename", $klant_old_data->cus_elecdoc_filename, "", $conn);

			unlink("cus_docs/" . $_POST["cus_id"] . "/doc_elec/" . $klant_old_data->cus_elecdoc_filename );
		}
		// EINDE DELETE VAN DE FILES
		
		if( !empty( $_FILES["orderbon"]["name"] ) )
		{
			$q_upd2 = "UPDATE kal_customers SET cus_order_file = '" . $order_file . "',
		                               		cus_order_filename = '" . $order_filename . "'
		                              WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_order_filename", $klant_old_data->cus_order_filename, $order_filename, $conn);
			
			mysqli_query($conn,  $q_upd2) or die( mysqli_error($conn) . "-2" );
		}

		if( !empty( $_FILES["werkdocument_file"]["name"] ))
		{
			$q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '" . $werkdoc_file ."',
		                               		cus_werkdoc_filename = '" . $werkdoc_filename ."'
		                              WHERE cus_id = " . $_POST["cus_id"];

			
			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_filename", $klant_old_data->cus_werkdoc_filename, $werkdoc_filename, $conn);
			
			mysqli_query($conn,  $q_upd3) or die( mysqli_error($conn) . "-3" );
		}

		if( !empty( $_FILES["doc_arei"]["name"] ))
		{
			$q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '" . $areidoc_file ."',
		                               			cus_areidoc_filename = '" . $areidoc_filename ."'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_areidoc_filename", $klant_old_data->cus_areidoc_filename, $areidoc_filename, $conn);
						
			mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-4" );
		}

		if( !empty( $_FILES["doc_gemeente"]["name"] ))
		{
			$q_upd4 = "UPDATE kal_customers SET cus_gemeentedoc_filename = '" . $gemeentedoc_filename ."'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_gemeentedoc_filename", $klant_old_data->cus_gemeentedoc_filename, $gemeentedoc_filename, $conn);
			
			mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-5" );
		}

		if( !empty( $_FILES["doc_bouwver"]["name"] ))
		{
			$q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = '" . $bouwdoc_filename ."'
		                             	 WHERE cus_id = " . $_POST["cus_id"];
			
			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_bouwvergunning_filename", $klant_old_data->cus_bouwvergunning_filename, $bouwdoc_filename, $conn);

			mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-5" );
		}

		if( !empty( $_FILES["doc_string"]["name"] ))
		{
			$q_upd6 = "UPDATE kal_customers
			              SET cus_stringdoc_filename  = '" . $stringdoc_filename ."'
		                WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_stringdoc_filename", $klant_old_data->cus_stringdoc_filename, $stringdoc_filename, $conn);
						
			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
		}

		if( !empty( $_FILES["werkdoc_pic1"]["name"] ))
		{
			$q_upd6 = "UPDATE kal_customers
			              SET cus_werkdoc_pic1  = '" . $werkdoc_filename1 ."'
		                WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic1", $klant_old_data->cus_werkdoc_pic1, $werkdoc_filename1, $conn);
			
			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
		}

		if( !empty( $_FILES["werkdoc_pic2"]["name"] ))
		{
			$q_upd6 = "UPDATE kal_customers
			              SET cus_werkdoc_pic2  = '" . $werkdoc_filename2 ."'
		                WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic2", $klant_old_data->cus_werkdoc_pic2, $werkdoc_filename2, $conn);
						
			mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
		}

		if( !empty( $_FILES["doc_elec"]["name"] ))
		{
			$q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = '" . $elecdoc_filename ."'
		                             	 WHERE cus_id = " . $_POST["cus_id"];

			// toevoegen loggen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "cus_elecdoc_filename", $klant_old_data->cus_elecdoc_filename, $elecdoc_filename, $conn);
						
			mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-4" );
		}
	}
	
	// kijken of sub bestaat
	if( isset( $_POST["maak_uitbreiding"] ) )
	{
		$uit_set_sunny = '0';
		if( isset( $_POST["uit_sunnybeam"] ) && $_POST["uit_sunnybeam"] == 'on' )
		{
			$uit_set_sunny = '1';
		}
        
        $uit_set_huur_doc = '0';
    	if( isset( $_POST["uit_huur_doc"] ) && ($_POST["uit_huur_doc"] == 'on' || $_POST["uit_huur_doc"] == '1' ) )
    	{
    		$uit_set_huur_doc = '1';
    	}
		
		$q_zoek_uit = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"] . " AND cus_active = '1'");
		
		$offerte_besproken = $_POST["uit_offerte_besproken1"] . "@" . $_POST["uit_offerte_besproken2"] . "@" . $_POST["uit_offerte_besproken3"];
		$_POST["uit_offerte_datum"] = changeDate2EU($_POST["uit_offerte_datum"]);
		$_POST["uit_offerte_gemaakt"] = changeDate2EU($_POST["uit_offerte_gemaakt"]);
		$_POST["uit_datum_vreg"] = changeDate2EU($_POST["uit_datum_vreg"]);
		$_POST["uit_gecontacteerd"] = changeDate2EU($_POST["uit_gecontacteerd"]);
		$_POST["uit_installatie_datum"] = changeDate2EU($_POST["uit_installatie_datum"]);
        $_POST["uit_installatie_datum2"] = changeDate2EU($_POST["uit_installatie_datum2"]);
        $_POST["uit_installatie_datum3"] = changeDate2EU($_POST["uit_installatie_datum3"]);
        $_POST["uit_installatie_datum4"] = changeDate2EU($_POST["uit_installatie_datum4"]);
        $_POST["uit_nw_installatie_datum"] = changeDate2EU($_POST["uit_nw_installatie_datum"]);
        $_POST["uit_datum_net"] = changeDate2EU($_POST["uit_datum_net"]);
		$_POST["uit_verkoop_datum"] = changeDate2EU($_POST["uit_verkoop_datum"]);
        $_POST["uit_installatie_aanp"] = changeDate2EU($_POST["uit_installatie_aanp"]);
		
		if( mysqli_num_rows($q_zoek_uit) == 0 )
		{
			// nieuwe - insert
            // eerst zoeken en als deze al bestaat, dan deze terug op actief zetten
            
            $q_zoek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $_POST["cus_id"]));
            
            if( $q_zoek == 0 )
            { 
    			$q_upd = "INSERT INTO kal_customers(uit_cus_id,
    			                                    cus_acma,
    			                                    cus_offerte_datum)
    			                             VALUES('" . $_POST["cus_id"] . "',
    			                                    '" . $_POST["uit_acma"] ."',
    			                                    '" . $_POST["uit_offerte_datum"] ."')";
    			
    			mysqli_query($conn,  $q_upd) or die( mysqli_error($conn) );
            }else
            {
                // oude uitbreiding terug actief maken
                $q_upd = "UPDATE kal_customers SET cus_active = '1' WHERE uit_cus_id = " . $_POST["cus_id"];
                mysqli_query($conn,  $q_upd) or die( mysqli_error($conn) );
            }
			
			// hier toevoegen
			customersLog($_POST["cus_id"], $_SESSION["kalender_user"]->user_id, "Uitbreiding", "0", "1", $conn);
			
		}else
		{
			// opzoeken van de id
			$q_cus2 = mysqli_fetch_object($q_zoek_uit);
			
            // BEGIN mail sturen naar pieter wanneer werkdocument klaar verandert van nee naar ja
        	if( $q_cus2->cus_werkdoc_klaar == '0' && $_POST["uit_werkdocument_klaar"] == '1' )
        	{
        		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        		$bericht .= "<tr><td>Beste Pieter</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Het werkdocument van onderstaande klant dient nog gecontrolleerd te worden.</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
        		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
        		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
        		$bericht .= "</table>";
        
        		//mail( "dimitri@futech.be", "Werkdocument controlleren bij Uitbreiding " . $klant_old_data->cus_naam, $bericht, $headers );
        		
        		// entry toevoegen om dit werkdocument in een lijst te plaatsen van nog te controleren werkdocumenten
        		mysqli_query($conn, "INSERT INTO kal_check_werkdoc( cw_cus_id ) VALUES(". $q_cus2->cus_id . ")") or die( mysqli_error($conn) ); 
        	}
        	// EINDE mail sturen naar pieter wanneer werkdocument klaar verandert van nee naar ja
        	
        	// verwijderen van de entry indien werkdoc_klaar gaat van ja -> nee
        	if( $q_cus2->cus_werkdoc_klaar == '1' && $_POST["uit_werkdocument_klaar"] == '0' )
        	{
        		mysqli_query($conn, "DELETE FROM kal_check_werkdoc WHERE cw_cus_id = " . $q_cus2->cus_id . " LIMIT 1") or die( mysqli_error($conn) );
        	}
        	// einde verwijderen van de entry
            
            $uit_werkdoc_check = '0';
        	if( $_POST["uit_werkdoc_check"] == 'on' || $_POST["uit_werkdoc_check"] == '1' )
        	{
        		$uit_werkdoc_check = '1';
        		
        		// verwijderen uit de lijst van te controleren werkdocument
        		mysqli_query($conn, "DELETE FROM kal_check_werkdoc WHERE cw_cus_id = " . $q_cus2->cus_id . " LIMIT 1") or die( mysqli_error($conn) );
        	}
            
            // BEGIN mail sturen naar elise wanneer werkdocument gecontrolleerd is
        	if( $q_cus2->cus_werkdoc_check == '0' && ( $_POST["uit_werkdoc_check"] == 'on' || $_POST["uit_werkdoc_check"] == '1' ) )
        	{
        		$bericht = "<table cellpadding='0' cellspacing='0' border='0'>";
        		$bericht .= "<tr><td>Beste</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Het werkdocument van onderstaande klant is klaar.</td></tr>";
        		$bericht .= "<tr><td>&nbsp;</td></tr>";
        		$bericht .= "<tr><td>Klantgegevens :</td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_naam . " " . $klant_old_data->cus_bedrijf ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_straat . " " . $klant_old_data->cus_nr ."</b></td></tr>";
        		$bericht .= "<tr><td><b>". $klant_old_data->cus_postcode . " " . $klant_old_data->cus_gemeente ."</b></td></tr>";
        		$bericht .= "<tr><td><b>GSM. : ". $klant_old_data->cus_gsm ."</b></td></tr>";
        		$bericht .= "<tr><td><b>Tel. : ". $klant_old_data->cus_tel ."</b></td></tr>";
        		$bericht .= "</table>";
        
        		mail( "elise@weygersmontage.be", "Werkdocument klaar bij uitbreiding " . $klant_old_data->cus_naam, $bericht, $headers );
        		mail( "jolien@futech.be", "Werkdocument klaar bij uitbreiding " . $klant_old_data->cus_naam, $bericht, $headers );
                
        		//mail( "dimitri@futech.be", "Werkdocument klaar bij uitbreiding " . $klant_old_data->cus_naam, $bericht, $headers );
        		
        		// opzoeken van de acma en deze ook een mail sturen.
        		$zoek_acma = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $klant_old_data->cus_acma));
        		//mail( $zoek_acma->email, "Werkdocument klaar bij " . $klant_old_data->cus_naam, $bericht, $headers );
        	}
        	// EINDE mail sturen naar elise wanneer werkdocument gecontrolleerd is
            
            // opslaan van de serienummers van de omvormers
        	//- Eerst verwijderen van de nummers
        	$q_del_omv = mysqli_query($conn, "DELETE FROM kal_customers_omvormers WHERE co_cus_id = " . $q_cus2->cus_id);
        	
        	//-Toevoegen van de omvormers
        	$aantal_omv = $_POST["uit_aantal_omv"];
        	
        	for( $j=1;$j<=$aantal_omv; $j++ )
        	{
        		$q_ins = "INSERT INTO kal_customers_omvormers(  co_cus_id,
        														co_omvormer,
        														co_sn,
                                                                co_text,
        														co_user_id) 
        											   VALUES(". $q_cus2->cus_id .",
        											          '". $_POST["uit_omv".$j] ."',
        											          '". $_POST["uit_sn".$j] . "',
                                                              '". $_POST["uit_text".$j] . "',
        											          '". $_SESSION["kalender_user"]->user_id ."')";
        		if( !empty( $_POST["uit_sn".$j] ) )
        		{
        			mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );	
        		}
        	}
        	// einde opslaan van de serienummers van de omvormers
            
			// begin verwijderen offerte file
			foreach( $_POST as $key => $post )
			{
				if( substr($key, 0, 16) == "uit_offerte_del_" )
				{
					// opzoeken record
					$id = substr( $key, 16 );
					$offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'offerte' AND cf_id = " . $id));
					
					//echo "SELECT * FROM kal_customers_files WHERE cf_soort = 'offerte' AND cf_id = " . $id;
					
					// record verwijderen
					$q_offerte_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $offerte->cf_id . " LIMIT 1 ");
					
					// bestand verwijderen 
					if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/offerte/" . $offerte->cf_file ) )
					{
						unlink("cus_docs/" . $q_cus2->cus_id . "/offerte/" . $offerte->cf_file  );
					}
				}
			}
			// einde verwijderen offerte file
			
            // BEGIN -------------------------------------------------------------------------------------------------------------------
            // toevoegen hypotheek
            $hypo_file = "";
        	$hypo_filename = "";
        
        	if( $_FILES["uit_hypotheek"]["tmp_name"] !=  "" )
        	{
        		$hypo_file = "";
        		$hypo_filename = $_FILES["uit_hypotheek"]["name"];
        
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id);
        		@mkdir( "hypotheek" );
        		chdir( "hypotheek" );
        		move_uploaded_file( $_FILES['uit_hypotheek']['tmp_name'], $hypo_filename );
        		chdir("../../../");
        		
        		// toevoegen in de nieuwe tabel
        		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
        		                                                              cf_soort, 
        		                                                              cf_file) 
        		                                                      VALUES('".$q_cus2->cus_id."',
        		                                                             'hypotheek',
        		                                                             '".$hypo_filename."')") or die( mysqli_error($conn) );
        	}
        	// EINDE TOEVOEGEN hypotheek file
        	
        	// VERWIJDEREN hypotheek FILE
        	foreach( $_POST as $key => $post )
        	{
        		if( substr($key, 0, 18) == "uit_hypotheek_del_" )
        		{
        			// opzoeken record
        			$id = substr( $key, 18 );
        			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'hypotheek' AND cf_id = " . $id));
        			
        			// record verwijderen
        			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
        			
        			// bestand verwijderen 
        			if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/hypotheek/" . $order->cf_file ) )
        			{
        				unlink("cus_docs/" . $q_cus2->cus_id . "/hypotheek/" . $order->cf_file  );
        			}
        		}
        	}
            // einde verwijderen hypotheek
            
            // toevoegen eigendomsacte
            $eigendom_file = "";
        	$eigendom_filename = "";
        
        	if( $_FILES["uit_eigendom"]["tmp_name"] !=  "" )
        	{
        		$eigendom_file = "";
        		$eigendom_filename = $_FILES["uit_eigendom"]["name"];
        
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id );
        		@mkdir( "eigendom" );
        		chdir( "eigendom" );
        		move_uploaded_file( $_FILES['uit_eigendom']['tmp_name'], $eigendom_filename );
        		chdir("../../../");
        		
        		// toevoegen in de nieuwe tabel
        		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
        		                                                              cf_soort, 
        		                                                              cf_file) 
        		                                                      VALUES('".$q_cus2->cus_id."',
        		                                                             'eigendom',
        		                                                             '".$eigendom_filename."')") or die( mysqli_error($conn) );
        	}
        	// EINDE TOEVOEGEN eigendomsacte file
        	
        	// VERWIJDEREN eigendomsacte FILE
        	foreach( $_POST as $key => $post )
        	{
        		if( substr($key, 0, 17) == "uit_eigendom_del_" )
        		{
        			// opzoeken record
        			$id = substr( $key, 17 );
        			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'eigendom' AND cf_id = " . $id));
        			
        			// record verwijderen
        			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
        			
        			// bestand verwijderen 
        			if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/eigendom/" . $order->cf_file ) )
        			{
        				unlink("cus_docs/" . $q_cus2->cus_id . "/eigendom/" . $order->cf_file  );
        			}
        		}
        	}
            // einde verwijderen eigendomsacte
            
            // toevoegen isolatie
            $isolatie_file = "";
        	$isolatie_filename = "";
        
        	if( $_FILES["uit_isolatie"]["tmp_name"] !=  "" )
        	{
        		$isolatie_file = "";
        		$isolatie_filename = $_FILES["uit_isolatie"]["name"];
        
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id );
        		@mkdir( "isolatie" );
        		chdir( "isolatie" );
        		move_uploaded_file( $_FILES['uit_isolatie']['tmp_name'], $isolatie_filename );
        		chdir("../../../");
        		
        		// toevoegen in de nieuwe tabel
        		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
        		                                                              cf_soort, 
        		                                                              cf_file) 
        		                                                      VALUES('".$q_cus2->cus_id."',
        		                                                             'isolatie',
        		                                                             '".$isolatie_filename."')") or die( mysqli_error($conn) );
        	}
        	// EINDE TOEVOEGEN isolatie file
        	
        	// VERWIJDEREN isolatie FILE
        	foreach( $_POST as $key => $post )
        	{
        		if( substr($key, 0, 17) == "uit_isolatie_del_" )
        		{
        			// opzoeken record
        			$id = substr( $key, 17 );
        			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'isolatie' AND cf_id = " . $id));
        			
        			// record verwijderen
        			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
        			
        			// bestand verwijderen 
        			if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/isolatie/" . $order->cf_file ) )
        			{
        				unlink("cus_docs/" . $q_cus2->cus_id . "/isolatie/" . $order->cf_file  );
        			}
        		}
        	}
            // einde verwijderen isolatie
            
            // toevoegen loonfiche
            $loonfiche_file = "";
        	$loonfiche_filename = "";
        
        	if( $_FILES["uit_loonfiche"]["tmp_name"] !=  "" )
        	{
        		$loonfiche_file = "";
        		$loonfiche_filename = $_FILES["uit_loonfiche"]["name"];
        
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id );
        		@mkdir( "loonfiche" );
        		chdir( "loonfiche" );
        		move_uploaded_file( $_FILES['uit_loonfiche']['tmp_name'], $loonfiche_filename );
        		chdir("../../../");
        		
        		// toevoegen in de nieuwe tabel
        		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
        		                                                              cf_soort, 
        		                                                              cf_file) 
        		                                                      VALUES('".$q_cus2->cus_id."',
        		                                                             'loonfiche',
        		                                                             '".$loonfiche_filename."')") or die( mysqli_error($conn) );
        	}
        	// EINDE TOEVOEGEN loonfiche file
        	
        	// VERWIJDEREN loonfiche FILE
        	foreach( $_POST as $key => $post )
        	{
        		if( substr($key, 0, 18) == "uit_loonfiche_del_" )
        		{
        			// opzoeken record
        			$id = substr( $key, 18 );
        			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'loonfiche' AND cf_id = " . $id));
        			
        			// record verwijderen
        			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
        			
        			// bestand verwijderen 
        			if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/loonfiche/" . $order->cf_file ) )
        			{
        				unlink("cus_docs/" . $q_cus2->cus_id . "/loonfiche/" . $order->cf_file  );
        			}
        		}
        	}
            // einde verwijderen loonfiche
            
            // toevoegen alg. vw
            $vol_off_file = "";
        	$vol_off_filename = "";
        
        	if( $_FILES["uit_vol_off"]["tmp_name"] !=  "" )
        	{
        		$vol_off_file = "";
        		$vol_off_filename = $_FILES["uit_vol_off"]["name"];
        
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id );
        		@mkdir( "vol_off" );
        		chdir( "vol_off" );
        		move_uploaded_file( $_FILES['uit_vol_off']['tmp_name'], $vol_off_filename );
        		chdir("../../../");
        		
        		// toevoegen in de nieuwe tabel
        		$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
        		                                                              cf_soort, 
        		                                                              cf_file) 
        		                                                      VALUES('".$q_cus2->cus_id."',
        		                                                             'vol_off',
        		                                                             '".$vol_off_filename."')") or die( mysqli_error($conn) );
        	}
        	// EINDE TOEVOEGEN alg. vw. file
        	
        	// VERWIJDEREN alg. vw. FILE
        	foreach( $_POST as $key => $post )
        	{
        		if( substr($key, 0, 16) == "uit_vol_off_del_" )
        		{
        			// opzoeken record
        			$id = substr( $key, 16 );
        			$order = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_soort = 'vol_off' AND cf_id = " . $id));
        			
        			// record verwijderen
        			$q_order_del = mysqli_query($conn, "DELETE FROM kal_customers_files WHERE cf_id = " . $order->cf_id . " LIMIT 1 ") or die( mysqli_error($conn) );
        			
        			// bestand verwijderen 
        			if( file_exists( "cus_docs/" . $q_cus2->cus_id . "/vol_off/" . $order->cf_file ) )
        			{
        				unlink("cus_docs/" . $q_cus2->cus_id . "/vol_off/" . $order->cf_file  );
        			}
        		}
        	}
            // einde verwijderen als.vw
            // EINDE -------------------------------------------------------------------------------------------------------------------
            
			// TOEVOEGEN offerte file
			$offerte_file = "";
			$offerte_filename = "";
			if( $_FILES["uit_offerte"]["tmp_name"] !=  "" )
			{
				$offerte_file = "";
				$offerte_filename = $_FILES["uit_offerte"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "offerte" );
				chdir( "offerte" );
				move_uploaded_file( $_FILES['uit_offerte']['tmp_name'], $offerte_filename );
				chdir("../../../");
				
				// toevoegen in de nieuwe tabel
				$q_ins_offerte = mysqli_query($conn, "INSERT INTO kal_customers_files(cf_cus_id, 
				                                                              cf_soort, 
				                                                              cf_file) 
				                                                      VALUES('".$q_cus2->cus_id."',
				                                                             'offerte',
				                                                             '".$offerte_filename."')");
			}
			// EINDE TOEVOEGEN offerte file
			
            // als er als een bestand is geupload en er wordt een nieuwe bestand geupload zonder het oude te verwijderen, dan moet eerst het oude bestand verwijdert worden.
        	// OF als isset bestand verwijderen.
        	if( (isset( $_FILES["uit_doc_opmeting"]["tmp_name"] ) && !empty($_FILES["uit_doc_opmeting"]["tmp_name"]) && !empty( $q_cus2->cus_opmetingdoc_filename )) || isset( $_POST["uit_opmetingdoc_del"] ) )
        	{
        		$q_upd6 = "UPDATE kal_customers SET cus_opmetingdoc_filename = ''
        	                              WHERE cus_id = " . $q_cus2->cus_id;
        	
        		mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
        
        		// verwijderen loggen
        		customersLog($q_cus2, $_SESSION["kalender_user"]->user_id, "cus_opmetingdoc_filename", $q_cus2->cus_opmetingdoc_filename, "", $conn);
        			
        		unlink("cus_docs/" . $q_cus2->cus_id . "/doc_opmeting/" . $q_cus2->cus_opmetingdoc_filename );
        	}
        	
        	// toevoegen van het stringopmetingsrapport
        	$opmetingdoc_filename = "";
        	if( $_FILES["uit_doc_opmeting"]["tmp_name"] != "" )
        	{
        		$opmetingdoc_filename = $_FILES["uit_doc_opmeting"]["name"];
        		
        		chdir( "cus_docs/");
        		@mkdir( $q_cus2->cus_id );
        		chdir( $q_cus2->cus_id );
        		@mkdir( "doc_opmeting" );
        		chdir( "doc_opmeting" );
        		move_uploaded_file( $_FILES['uit_doc_opmeting']['tmp_name'], $opmetingdoc_filename );
        		chdir("../../../");
        		
        		$q_upd6 = "UPDATE kal_customers 
        		              SET cus_opmetingdoc_filename  = '" . $opmetingdoc_filename ."'
        	                WHERE cus_id = " . $q_cus2->cus_id;
        	
        		mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn));	
        		
        		// toevoegen loggen
        		customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_opmetingdoc_filename", $q_cus2->cus_opmetingdoc_filename, $opmetingdoc_filename, $conn);
        	}
            
			$order_file = "";
			$order_filename = "";
			if( $_FILES["uit_orderbon"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_order_filename  ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/orderbon/" . $q_cus2->cus_order_filename  );
				}
		
				//$order_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["orderbon"]["tmp_name"] ));
				$order_file = "";
				$order_filename = $_FILES["uit_orderbon"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "orderbon" );
				chdir( "orderbon" );
				move_uploaded_file( $_FILES['uit_orderbon']['tmp_name'], $order_filename );
				chdir("../../../");
			}
		
			$werkdoc_file = "";
			$werkdoc_filename = "";
			if( $_FILES["uit_werkdocument_file"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_werkdoc_filename  ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/" . $q_cus2->cus_werkdoc_filename  );
				}
		
				//$werkdoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["werkdocument_file"]["tmp_name"] ));
				$werkdoc_file = "";
				$werkdoc_filename = $_FILES["uit_werkdocument_file"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "werkdocument_file" );
				chdir( "werkdocument_file" );
				move_uploaded_file( $_FILES['uit_werkdocument_file']['tmp_name'], $werkdoc_filename );
				chdir("../../../");
			}
		
			$areidoc_file = "";
			$areidoc_filename = "";
			if( $_FILES["uit_doc_arei"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_areidoc_filename   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/doc_arei/" . $q_cus2->cus_areidoc_filename   );
				}
		
				//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
				$areidoc_file = "";
				$areidoc_filename = $_FILES["uit_doc_arei"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "doc_arei" );
				chdir( "doc_arei" );
				move_uploaded_file( $_FILES['uit_doc_arei']['tmp_name'], $areidoc_filename );
				chdir("../../../");
			}
			
			// elec schema
			$elecdoc_filename = "";
			if( $_FILES["uit_doc_elec"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_elecdoc_filename   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/doc_elec/" . $q_cus2->cus_elecdoc_filename   );
				}

				$elecdoc_filename = $_FILES["uit_doc_elec"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "doc_elec" );
				chdir( "doc_elec" );
				move_uploaded_file( $_FILES['uit_doc_elec']['tmp_name'], $elecdoc_filename );
				chdir("../../../");
			}
		
			$gemeentedoc_file = "";
			$gemeentedoc_filename = "";
			if( $_FILES["uit_doc_gemeente"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_gemeentedoc_filename   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/doc_gemeente/" . $q_cus2->cus_gemeentedoc_filename   );
				}
		
				//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
				$gemeentedoc_file = "";
				$gemeentedoc_filename = $_FILES["uit_doc_gemeente"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "doc_gemeente" );
				chdir( "doc_gemeente" );
				move_uploaded_file( $_FILES['uit_doc_gemeente']['tmp_name'], $gemeentedoc_filename );
				chdir("../../../");
			}
		
			$bouwdoc_file = "";
			$bouwdoc_filename = "";
			if( $_FILES["uit_doc_bouwver"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_bouwvergunning_filename   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/doc_bouwver/" . $q_cus2->cus_bouwvergunning_filename   );
				}
		
				//$areidoc_file = mysqli_real_escape_string($conn, file_get_contents ( $_FILES["doc_arei"]["tmp_name"] ));
				$bouwdoc_file = "";
				$bouwdoc_filename = $_FILES["uit_doc_bouwver"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "doc_bouw" );
				chdir( "doc_bouw" );
				move_uploaded_file( $_FILES['uit_doc_bouwver']['tmp_name'], $bouwdoc_filename );
				chdir("../../../");
			}
		
			$stringdoc_file = "";
			$stringdoc_filename = "";
			if( $_FILES["uit_doc_string"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_stringdoc_filename   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/doc_string/" . $q_cus2->cus_stringdoc_filename   );
				}
		
				$stringdoc_file = "";
				$stringdoc_filename = $_FILES["uit_doc_string"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "doc_string" );
				chdir( "doc_string" );
				move_uploaded_file( $_FILES['uit_doc_string']['tmp_name'], $stringdoc_filename );
				chdir("../../../");
			}
		
			$werkdoc_file1 = "";
			$werkdoc_filename1 = "";
			if( $_FILES["uit_werkdoc_pic1"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_werkdoc_pic1   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/pic1/" . $q_cus2->cus_werkdoc_pic1   );
				}
		
				$werkdoc_file1 = "";
				$werkdoc_filename1 = $_FILES["uit_werkdoc_pic1"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "werkdocument_file" );
				chdir( "werkdocument_file" );
				@mkdir( "pic1" );
				chdir( "pic1" );
				move_uploaded_file( $_FILES['uit_werkdoc_pic1']['tmp_name'], $werkdoc_filename1 );
				chdir("../../../../");
			}
		
			$werkdoc_file2 = "";
			$werkdoc_filename2 = "";
			if( $_FILES["uit_werkdoc_pic2"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_werkdoc_pic2   ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/pic2/" . $q_cus2->cus_werkdoc_pic2 );
				}
		
				$werkdoc_file2 = "";
				$werkdoc_filename2 = $_FILES["uit_werkdoc_pic2"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "werkdocument_file" );
				chdir( "werkdocument_file" );
				@mkdir( "pic2" );
				chdir( "pic2" );
				move_uploaded_file( $_FILES['uit_werkdoc_pic2']['tmp_name'], $werkdoc_filename2 );
				chdir("../../../../");
			}
		
			$factuur_file = "";
			$factuur_filename = "";
			if( $_FILES["uit_doc_factuur"]["tmp_name"] != "" )
			{
				if( !empty( $q_cus2->cus_factuur_filename ) )
				{
					unlink("cus_docs/" . $q_cus2->cus_id . "/factuur/" . $q_cus2->cus_factuur_filename );
				}
		
				$factuur_file = "";
				$factuur_filename = $_FILES["uit_doc_factuur"]["name"];
		
				chdir( "cus_docs/");
				@mkdir( $q_cus2->cus_id );
				chdir( $q_cus2->cus_id );
				@mkdir( "factuur" );
				chdir( "factuur" );
				move_uploaded_file( $_FILES['uit_doc_factuur']['tmp_name'], $factuur_filename );
				chdir("../../../");
			}
		
			// einde keuze type omvormers
			$uit_werk_omvormers = "";
			if( count( $_POST["uit_werk_omvormers"] ) > 0 )
			{
				foreach( $_POST["uit_werk_omvormers"] as $omv )
				{
					if( $omv > 0 )
					{
						$uit_werk_omvormers .= $omv . "@";
					}
				}
				$uit_werk_omvormers = substr( $uit_werk_omvormers, 0, -1 );
			}
			// einde keuze type omvormers
			
			// begin delete van de blobs
			if( isset( $_POST["uit_order_del"] ) )
			{
				$q_upd2 = "UPDATE kal_customers SET cus_order_file = '',
			                               			cus_order_filename = ''
			                              	WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd2) or die( mysqli_error($conn) . "-2" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_order_filename", $q_cus2->cus_order_filename, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/orderbon/" . $q_cus2->cus_order_filename );
			}
	
			if( isset( $_POST["uit_werkdoc_del"] ) )
			{
				$q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '',
			                               			cus_werkdoc_filename = ''
			                             	 WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd3) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_filename", $q_cus2->cus_werkdoc_filename, "", $conn);
				
				unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/" . $q_cus2->cus_werkdoc_filename );
			}
	
			if( isset( $_POST["uit_areidoc_del"] ) )
			{
				$q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '',
			                               			cus_areidoc_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_areidoc_filename", $q_cus2->cus_areidoc_filename, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/doc_arei/" . $q_cus2->cus_areidoc_filename );
			}
			
			// verwijderen elec schema
			if( isset( $_POST["uit_elecdoc_del"] ) )
			{
				$q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_elecdoc_filename", $q_cus2->cus_elecdoc_filename, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/doc_elec/" . $q_cus2->cus_elecdoc_filename );
			}
	
			if( isset( $_POST["uit_gemeentedoc_del"] ) )
			{
				$q_upd5 = "UPDATE kal_customers SET cus_gemeentedoc_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-3" );
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_gemeentedoc_filename", $q_cus2->cus_gemeentedoc_filename, "",$conn);
				
				unlink("cus_docs/" . $q_cus2->cus_id . "/doc_gemeente/" . $q_cus2->cus_gemeentedoc_filename );
			}
	
			if( isset( $_POST["uit_bouwverdoc_del"] ) )
			{
				$q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_bouwvergunning_filename", $q_cus2->cus_bouwvergunning_filename, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/doc_bouw/" . $q_cus2->cus_bouwvergunning_filename );
			}
	
			if( isset( $_POST["uit_stringdoc_del"] ) )
			{
				$q_upd6 = "UPDATE kal_customers SET cus_stringdoc_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_stringdoc_filename", $q_cus2->cus_stringdoc_filename, "",$conn);
				
				unlink("cus_docs/" . $q_cus2->cus_id . "/doc_string/" . $q_cus2->cus_stringdoc_filename );
			}
	
			if( isset( $_POST["uit_werkdocpic1_del"] ) )
			{
				$q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic1 = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic1", $q_cus2->cus_werkdoc_pic1, "",$conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/pic1/" . $q_cus2->cus_werkdoc_pic1 );
			}
	
			if( isset( $_POST["uit_werkdocpic2_del"] ) )
			{
				$q_upd6 = "UPDATE kal_customers SET cus_werkdoc_pic2 = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic2", $q_cus2->cus_werkdoc_pic2, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/werkdocument_file/pic2/" . $q_cus2->cus_werkdoc_pic2 );
			}
	
			if( isset( $_POST["uit_factuur_del"] ) )
			{
				$q_upd6 = "UPDATE kal_customers SET cus_factuur_filename = ''
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-3" );
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_factuur_filename", $q_cus2->cus_factuur_filename, "", $conn);
	
				unlink("cus_docs/" . $q_cus2->cus_id . "/factuur/" . $q_cus2->cus_factuur_filename );
			}
			// EINDE DELETE VAN DE FILES
			
			if( !empty( $_FILES["uit_orderbon"]["name"] ) )
			{
				$q_upd2 = "UPDATE kal_customers SET cus_order_file = '" . $order_file . "',
			                               		cus_order_filename = '" . $order_filename . "'
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_order_filename", $q_cus2->cus_order_filename, $order_filename, $conn);
				
				mysqli_query($conn,  $q_upd2) or die( mysqli_error($conn) . "-2" );
			}
	
			if( !empty( $_FILES["uit_werkdocument_file"]["name"] ))
			{
				$q_upd3 = "UPDATE kal_customers SET cus_werkdoc_file = '" . $werkdoc_file ."',
			                               		cus_werkdoc_filename = '" . $werkdoc_filename ."'
			                              WHERE cus_id = " . $q_cus2->cus_id;
	
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_filename", $q_cus2->cus_werkdoc_filename, $werkdoc_filename, $conn);
				
				mysqli_query($conn,  $q_upd3) or die( mysqli_error($conn) . "-3" );
			}
	
			if( !empty( $_FILES["uit_doc_arei"]["name"] ))
			{
				$q_upd4 = "UPDATE kal_customers SET cus_areidoc_file = '" . $areidoc_file ."',
			                               			cus_areidoc_filename = '" . $areidoc_filename ."'
			                             	 WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_areidoc_filename", $q_cus2->cus_areidoc_filename, $areidoc_filename, $conn);
							
				mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-4" );
			}
			
			if( !empty( $_FILES["uit_doc_elec"]["name"] ))
			{
				$q_upd4 = "UPDATE kal_customers SET cus_elecdoc_filename = '" . $elecdoc_filename ."'
			                             	 WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_elecdoc_filename", $q_cus2->cus_elecdoc_filename, $elecdoc_filename, $conn);
							
				mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-4" );
			}
	
			if( !empty( $_FILES["uit_doc_gemeente"]["name"] ))
			{
				$q_upd4 = "UPDATE kal_customers SET cus_gemeentedoc_filename = '" . $gemeentedoc_filename ."'
			                             	 WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_gemeentedoc_filename", $q_cus2->cus_gemeentedoc_filename, $gemeentedoc_filename, $conn);
				
				mysqli_query($conn,  $q_upd4) or die( mysqli_error($conn) . "-5" );
			}
	
			if( !empty( $_FILES["uit_doc_bouwver"]["name"] ))
			{
				$q_upd5 = "UPDATE kal_customers SET cus_bouwvergunning_filename = '" . $bouwdoc_filename ."'
			                             	 WHERE cus_id = " . $q_cus2->cus_id;
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_bouwvergunning_filename", $q_cus2->cus_bouwvergunning_filename, $bouwdoc_filename, $conn);
	
				mysqli_query($conn,  $q_upd5) or die( mysqli_error($conn) . "-5" );
			}
	
			if( !empty( $_FILES["uit_doc_string"]["name"] ))
			{
				$q_upd6 = "UPDATE kal_customers
				              SET cus_stringdoc_filename  = '" . $stringdoc_filename ."'
			                WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_stringdoc_filename", $q_cus2->cus_stringdoc_filename, $stringdoc_filename, $conn);
							
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
			}
	
			if( !empty( $_FILES["uit_werkdoc_pic1"]["name"] ))
			{
				$q_upd6 = "UPDATE kal_customers
				              SET cus_werkdoc_pic1  = '" . $werkdoc_filename1 ."'
			                WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic1", $q_cus2->cus_werkdoc_pic1, $werkdoc_filename1, $conn);
				
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
			}
	
			if( !empty( $_FILES["uit_werkdoc_pic2"]["name"] ))
			{
				$q_upd6 = "UPDATE kal_customers
				              SET cus_werkdoc_pic2  = '" . $werkdoc_filename2 ."'
			                WHERE cus_id = " . $q_cus2->cus_id;
	
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_werkdoc_pic2", $q_cus2->cus_werkdoc_pic2, $werkdoc_filename2, $conn);
							
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
			}
	
			if( !empty( $_FILES["uit_doc_factuur"]["name"] ))
			{
				$q_upd6 = "UPDATE kal_customers
				              SET cus_factuur_filename = '" . $factuur_filename ."'
			                WHERE cus_id = " . $q_cus2->cus_id;
				
				// loggen
				customersLog($q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, "cus_factuur_filename", $q_cus2->cus_factuur_filename, $factuur_filename, $conn);
				
				mysqli_query($conn,  $q_upd6) or die( mysqli_error($conn) . "-5" );
			}
			
            
            // berekenen van de looptijd
            $uit_looptijd = 0;
            if( isset( $_POST["uit_looptijd_jaar"] ) && isset( $_POST["uit_looptijd_maand"] ) )
            {
                $uit_looptijd = ( $_POST["uit_looptijd_jaar"] * 12 ) + $_POST["uit_looptijd_maand"];
            }
            
			// bijwerken - update
            
            if( $_SESSION["kalender_user"]->group_id == 5 )
        	{
        		$q_upd = "UPDATE kal_customers SET 	cus_ingetekend = '" .htmlentities($_POST["uit_ingetekend"], ENT_QUOTES). "',
        		                               		cus_werkdoc_door = '" . $_POST["uit_werkdocument_door"] . "',
        		                               		cus_werkdoc_klaar = '" . $_POST["uit_werkdocument_klaar"] . "',
        		                               		cus_werkdoc_opm = '" . $_POST["uit_werkdoc_opm"] ."',
        		                               		cus_werkdoc_opm2 = '" . $_POST["uit_werkdoc_opm2"] ."',
        		                               		cus_werk_aant_panelen = '" . $_POST["uit_werk_aant_panelen"] . "',
        		                               		cus_werk_w_panelen = '" . $_POST["uit_werk_w_panelen"] . "',
        		                               		cus_werk_merk_panelen = '" . $_POST["uit_werk_merk_panelen"] . "',
        		                               		cus_werk_aant_omvormers = '" . $_POST["uit_werk_aant_omvormers"] . "',
        		                               		cus_ac_vermogen = '" . $_POST["uit_ac_vermogen"] . "',
        		                               		cus_type_omvormers = '" . $uit_werk_omvormers . "'
        		                              WHERE cus_id = " . $q_cus2->cus_id;
        	}else
            {
    			$q_upd = "UPDATE kal_customers SET 	cus_acma = '" . $_POST["uit_acma"] ."',
    			                               		cus_contact = '" . $_POST["uit_gecontacteerd"] . "',
    			                               		cus_offerte_datum = '". $_POST["uit_offerte_datum"] ."',
    			                               		cus_offerte_gemaakt = '" . $_POST["uit_offerte_gemaakt"] . "',
    			                               		cus_offerte_besproken = '" . $offerte_besproken . "',
    			                               		cus_aant_panelen = '" . $_POST["uit_aant_panelen"] . "',
    			                               		cus_type_panelen = '" . $_POST["uit_type_panelen"] ."',
    			                               		cus_w_panelen = '" . $_POST["uit_w_panelen"] . "',
    			                               		cus_merk_panelen = '" . $_POST["uit_merk_panelen"] . "',
    			                               		cus_kwhkwp = '" . $_POST["uit_kwhkwp"] . "',
    			                               		cus_hoek_z = '" . $_POST["uit_hoek_z"] ."',
    			                               		cus_hoek = '" . $_POST["uit_hoek"] ."',
    			                               		cus_soort_dak = '" . $_POST["uit_soort_dak"] ."',
    			                               		cus_prijs_wp = '" . $_POST["uit_ppwp"] ."',
    			                               		cus_bedrag_excl = '" . $_POST["uit_bedrag_excl"] . "',
    			                               		cus_woning5j = '" . $_POST["uit_woning5j"] . "',
    			                               		cus_opwoning = '" . $_POST["uit_opwoning"] . "',
    			                               		cus_driefasig = '" . $_POST["uit_driefasig"] . "',
    			                               		cus_nzn = '" . $_POST["uit_nzn"] . "',
    			                               		cus_verkoop = '" . $_POST["uit_verkoop"] . "',
    			                               		cus_verkoop_datum = '" . $_POST["uit_verkoop_datum"] . "',
    			                               		cus_reden = '" . htmlentities($_POST["uit_reden"], ENT_QUOTES) . "',
    			                               		cus_verkoopsbedrag_incl = '" . $_POST["uit_verkoopsbedrag_incl"] . "',
                                                    cus_ont_huur = '" . $_POST["uit_ont_huur"] ."',	
        											cus_bet_huur = '" . $_POST["uit_bet_huur"] . "',
                                                    cus_looptijd_huur = '" . $uit_looptijd . "',
                                                    cus_huur_doc = '". $uit_set_huur_doc ."',
    			                               		cus_datum_orderbon = '" . $_POST["uit_datum_orderbon"] . "',
    			                               		cus_sunnybeam = '" . $uit_set_sunny . "',
    			                               		cus_actie = '" . htmlentities($_POST["uit_actie"], ENT_QUOTES)  . "',
    			                               		cus_ingetekend = '" .htmlentities($_POST["uit_ingetekend"], ENT_QUOTES). "',
    			                               		cus_werkdoc_door = '" . $_POST["uit_werkdocument_door"] . "',
    			                               		cus_werkdoc_klaar = '" . $_POST["uit_werkdocument_klaar"] . "',
                                                    cus_werkdoc_check = '" . $uit_werkdoc_check . "',
    			                               		cus_werkdoc_opm = '" . $_POST["uit_werkdoc_opm"] ."',
    			                               		cus_werkdoc_opm2 = '" . $_POST["uit_werkdoc_opm2"] ."',
    			                               		cus_werk_aant_panelen = '" . $_POST["uit_werk_aant_panelen"] . "',
    			                               		cus_werk_w_panelen = '" . $_POST["uit_werk_w_panelen"] . "',
    			                               		cus_werk_merk_panelen = '" . $_POST["uit_werk_merk_panelen"] . "',
    			                               		cus_werk_aant_omvormers = '" . $_POST["uit_werk_aant_omvormers"] . "',
    			                               		cus_ac_vermogen = '" . $_POST["uit_ac_vermogen"] . "',
    			                               		cus_arei = '" . $_POST["uit_arei"] . "',
    			                               		cus_klant_tevree = '" . $_POST["uit_klant_tevree"] . "',
    			                         			cus_tevree_reden = '" . htmlentities($_POST["uit_niet_tevree"], ENT_QUOTES) . "',
    			                               		cus_type_omvormers = '" . $uit_werk_omvormers . "',
    			                               		cus_opmerkingen = '" . htmlentities($_POST["uit_opmerkingen"], ENT_QUOTES) . "',
    			                               		cus_arei_datum = '" . $_POST["uit_datum_arei"] . "',
    			                               		cus_arei_meterstand = '" . $_POST["uit_arei_meterstand"] ."',
    			                               		cus_vreg_datum = '" . $_POST["uit_datum_vreg"] . "',
    			                               		cus_datum_net = '" . $_POST["uit_datum_net"] . "',
    			                               		cus_pvz = '" . $_POST["uit_pvz"] . "',
    			                               		cus_ean = '". $_POST["uit_ean"] . "',
    			                               		cus_opmeting_datum = '" . $_POST["uit_opmeting_datum"] . "',
    			                               		cus_opmeting_door = '" . $_POST["uit_opmeting_door"] . "',
    			                               		cus_installatie_datum = '" . $_POST["uit_installatie_datum"] . "',
                                                    cus_installatie_datum2 = '" . $_POST["uit_installatie_datum2"] . "',
                                                    cus_installatie_datum3 = '" . $_POST["uit_installatie_datum3"] . "',
                                                    cus_installatie_datum4 = '" . $_POST["uit_installatie_datum4"] . "',
    			                               		cus_nw_installatie_datum = '" . $_POST["uit_nw_installatie_datum"] . "',  
                                                    cus_aanp_datum = '" . $_POST["uit_installatie_aanp"] . "',
                                                    cus_installatie_ploeg = '" . $_POST["uit_installatie_ploeg"] . "',
    			                               		cus_elec = '" . $_POST["uit_elec"] . "',
    			                               		cus_elec_door = '" . htmlentities($_POST["uit_elec_door"], ENT_QUOTES) . "',
    			                               		cus_elec_datum = '" . $_POST["uit_elec_datum"] . "',
    			                               		cus_gemeentepremie = '" . $_POST["uit_gem_premie"] . "',
    			                               		cus_bouwvergunning = '" . $_POST["uit_bouwver"] . "'
    			                              WHERE cus_id = " . $q_cus2->cus_id;
            }
			
			mysqli_query($conn,  $q_upd) or die( mysqli_error($conn) );
            
            $mapping = array();
	
        	if( $_SESSION["kalender_user"]->group_id == 5 )
        	{
        		// Engineering
        		$mapping["cus_ingetekend"] = htmlentities($_POST["uit_ingetekend"], ENT_QUOTES);
        		$mapping["cus_werk_aant_panelen"] = $_POST["uit_werk_aant_panelen"];
        		$mapping["cus_werk_w_panelen"] = $_POST["uit_werk_w_panelen"];
        		$mapping["cus_werk_merk_panelen"] = $_POST["uit_werk_merk_panelen"];
        		$mapping["cus_werk_aant_omvormers"] = $_POST["uit_werk_aant_omvormers"];
        		$mapping["cus_werkdoc_door"] = $_POST["uit_werkdocument_door"];
        		$mapping["cus_werkdoc_klaar"] = $_POST["uit_werkdocument_klaar"];
        		$mapping["cus_werkdoc_opm"] = $_POST["uit_werkdoc_opm"];
        		$mapping["cus_werkdoc_opm2"] = $_POST["uit_cus_werkdoc_opm2"];
        		$mapping["cus_ac_vermogen"] = $_POST["uit_ac_vermogen"];
        	}else
        	{
        		$mapping["cus_contact"] = $_POST["uit_gecontacteerd"];
                $mapping["cus_offerte_datum"] = $_POST["uit_offerte_datum"];
        		$mapping["cus_offerte_gemaakt"] = $_POST["uit_offerte_gemaakt"];
        		$mapping["cus_offerte_besproken"] = $offerte_besproken;
        		$mapping["cus_aant_panelen"] = $_POST["uit_aant_panelen"];
        		$mapping["cus_type_panelen"] = $_POST["uit_type_panelen"];
        		$mapping["cus_w_panelen"] = $_POST["uit_w_panelen"];
        		$mapping["cus_merk_panelen"] = $_POST["uit_merk_panelen"];
        		$mapping["cus_kwhkwp"] = $_POST["uit_kwhkwp"];
        		$mapping["cus_hoek_z"] = $_POST["uit_hoek_z"];
        		$mapping["cus_hoek"] = $_POST["uit_hoek"];
        		$mapping["cus_soort_dak"] = $_POST["uit_soort_dak"];
        		$mapping["cus_prijs_wp"] = $_POST["uit_ppwp"];
        		$mapping["cus_woning5j"] = $_POST["uit_woning5j"];
        		$mapping["cus_opwoning"] = $_POST["uit_opwoning"];
        		$mapping["cus_driefasig"] = $_POST["uit_driefasig"];
        		$mapping["cus_nzn"] = $_POST["uit_nzn"];
        		$mapping["cus_verkoop"] = $_POST["uit_verkoop"];
        		$mapping["cus_verkoop_datum"] = $_POST["uit_verkoop_datum"];
        		$mapping["cus_reden"] = htmlentities($_POST["uit_reden"], ENT_QUOTES);
        		$mapping["cus_datum_orderbon"] = $_POST["uit_datum_orderbon"];
        		$mapping["cus_sunnybeam"] = $uit_set_sunny;
        		$mapping["cus_werkdoc_check"] = $uit_werkdoc_check;
        		$mapping["cus_actie"] = htmlentities($_POST["uit_actie"], ENT_QUOTES);
        		$mapping["cus_ingetekend"] = htmlentities($_POST["uit_ingetekend"], ENT_QUOTES);
        		$mapping["cus_werkdoc_door"] = $_POST["uit_werkdocument_door"];
        		$mapping["cus_werkdoc_klaar"] = $_POST["uit_werkdocument_klaar"];
        		$mapping["cus_werkdoc_opm"] = $_POST["uit_werkdoc_opm"];
        		$mapping["cus_werkdoc_opm2"] = $_POST["uit_werkdoc_opm2"];
        		$mapping["cus_werk_aant_panelen"] = $_POST["uit_werk_aant_panelen"];
        		$mapping["cus_werk_w_panelen"] = $_POST["uit_werk_w_panelen"];
        		$mapping["cus_werk_merk_panelen"] = $_POST["uit_werk_merk_panelen"];
        		$mapping["cus_werk_aant_omvormers"] = $_POST["uit_werk_aant_omvormers"];
        		$mapping["cus_ac_vermogen"] = $_POST["uit_ac_vermogen"];
        		$mapping["cus_arei"] = $_POST["uit_arei"];
        		$mapping["cus_klant_tevree"] = $_POST["uit_klant_tevree"];
        		$mapping["cus_tevree_reden"] = htmlentities($_POST["uit_niet_tevree"], ENT_QUOTES);
        		$mapping["cus_type_omvormers"] = $uit_werk_omvormers;
        		$mapping["cus_opmerkingen"] = htmlentities($_POST["uit_opmerkingen"], ENT_QUOTES);
        		$mapping["cus_arei_datum"] = $_POST["uit_datum_arei"];
        		$mapping["cus_arei_meterstand"] = $_POST["uit_arei_meterstand"];
        		$mapping["cus_vreg_datum"] = $_POST["uit_datum_vreg"];
                $mapping["cus_vreg_un"] = $_POST["uit_vreg_un"];
                $mapping["cus_vreg_pwd"] = $_POST["uit_vreg_pwd"];
        		$mapping["cus_datum_net"] = $_POST["uit_datum_net"];
        		$mapping["cus_pvz"] = $_POST["uit_pvz"];
        		$mapping["cus_ean"] = $_POST["uit_ean"];
        		$mapping["cus_opmeting_datum"] = $_POST["uit_opmeting_datum"];
        		$mapping["cus_opmeting_door"] = $_POST["uit_opmeting_door"];
        		$mapping["cus_installatie_datum"] = $_POST["uit_installatie_datum"];
        		$mapping["cus_installatie_datum2"] = $_POST["uit_installatie_datum2"];
        		$mapping["cus_installatie_datum3"] = $_POST["uit_installatie_datum3"];
        		$mapping["cus_installatie_datum4"] = $_POST["uit_installatie_datum4"];
        		$mapping["cus_nw_installatie_datum"] = $_POST["uit_nw_installatie_datum"];
        		$mapping["cus_aanp_datum"] = $_POST["uit_installatie_aanp"];
        		$mapping["cus_installatie_ploeg"] = $_POST["uit_installatie_ploeg"];
        		$mapping["cus_elec"] = $_POST["uit_elec"];
        		$mapping["cus_elec_door"] = htmlentities($_POST["uit_elec_door"], ENT_QUOTES);
        		$mapping["cus_elec_datum"] = $_POST["uit_elec_datum"];
        		$mapping["cus_gemeentepremie"] = $_POST["uit_gem_premie"];
        		$mapping["cus_bouwvergunning"] = $_POST["uit_bouwver"];
        		$mapping["cus_verkoopsbedrag_excl"] = $_POST["uit_verkoopsbedrag_excl"];
        		$mapping["cus_verkoopsbedrag_incl"] = $_POST["uit_verkoopsbedrag_incl"];
        		$mapping["cus_bet_termijn"] = $_POST["uit_bet_termijn"];
        		$mapping["cus_ont_huur"] = $_POST["uit_ont_huur"];
        		$mapping["cus_bet_huur"] = $_POST["uit_bet_huur"];
                $mapping["cus_looptijd_huur"] = $uit_looptijd;
                $mapping["cus_huur_doc"] = $uit_set_huur_doc;
        		
        		if( $_POST["uit_kent"] == "" )
        		{
        			$_POST["uit_kent"] = 0;	
        		}
        		
        		$mapping["cus_kent_ons_van"] = $_POST["uit_kent"];
        	}
        	
        	if( !isset( $_POST["uit_bet_termijn"] ) )
        	{
        		$_POST["uit_bet_termijn"] = $q_cus2->cus_bet_termijn;
        		$mapping["cus_bet_termijn"] = $_POST["uit_bet_termijn"];
        	}
        	
        	foreach( $mapping as $field => $new_value )
        	{
                if( $new_value == "--" )
                {
                    $new_value = "";            
                }
                
                if( $q_cus2->$field == "--" )
                {
                    $q_cus2->$field = "";
                }
                
        		if( $q_cus2->$field != $new_value )
        		{
        			customersLog( $q_cus2->cus_id, $_SESSION["kalender_user"]->user_id, $field, $q_cus2->$field, $new_value, $conn);
        		}
        	}
		}
	} 
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<?php
$extra_url = "";
// naam in titel zetten
if( ((isset( $_POST["submit"] ) && $_POST["submit"] == "Go") || $_POST["cus_id1"] > 0 || $_POST["cus_id2"] > 0 || $_REQUEST["klant_id"] > 0 ) && $verwijderen == 0 )
{
	if( isset( $_REQUEST["klant_id"] ) && !isset( $_POST["klant_val"] ) )
	{
		$_POST["klant_val"] = $_REQUEST["klant_id"];
	}

	if( isset( $_POST["cus_id1"] ) )
	{
		$_POST["klant_val"] = $_POST["cus_id1"];
	}

	if( isset( $_POST["cus_id2"] ) )
	{
		$_POST["klant_val"] = $_POST["cus_id2"];
	}
    
    $naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["klant_val"]));
    $name_title= "";
    if( $naam->cus_naam == $naam->cus_bedrijf  )
    {
        $name_title = ucfirst($naam->cus_bedrijf);
    }else
    {
        $name_title= ucfirst($naam->cus_naam) . " " .  ucfirst($naam->cus_bedrijf);
    }
    
    $name_title .= " - Solarlogs - Futech";
    $extra_url = "?tab_id=1&klant_id=" . $naam->cus_id;
 }else
 {
    $name_title .= "Algemene kalender - Klantenlijst";
 }

?>
<title><?php echo $name_title; ?></title>
<style type='text/css'>

table.main_table {
	background-color: #FFFFCC;
	border-top: 1px solid silver;
	border-left: 1px solid silver;
	border-bottom: 2px solid silver;
	border-right: 2px solid silver;
}

input:focus {
	border: 2px solid #3333FF;
}

select:hover {
	border: 1px solid #3399FF;
}

input[type=text]:hover,textarea:hover,checkbox:hover {
	border: 2px solid #3399FF;
}

.klant_gegevens{
	font-weight:800;
}

.offerte_gegevens{
	font-weight:800;
	color: darkblue;
}

.verkoop_gegevens{
	font-weight:800;
	color: darkgreen;
}

fieldset{
	width: 440px;
}

legend{
	font-weight:800;
	font-style:italic;
}
</style>
<script type="text/javascript" src="js/functions.js"></script>


<link href="css/style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" 	src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" media="print" href="css/print.css" />

<script type="text/javascript" src="js/jquery.autocomplete.js"></script>

<!-- 
<script type="text/javascript" src="js/jquery.validate.js"></script>
-->

<script type="text/javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" src="js/jquery.ui.widget.js"></script>
<script type="text/javascript" src="js/jquery.ui.tabs.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

<script type='text/javascript'>

function checkSchaduw(dit)
{
    if(dit.checked == false)
    {
        document.getElementById("table_schaduw").style.display = 'none';
    }else
    {
        document.getElementById("table_schaduw").style.display = 'block';
    }
}

var XMLHttpRequestObject3 = false;

try{
	XMLHttpRequestObject3 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject3 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject3 = false;
	}
 
	if(!XMLHttpRequestObject3 && window.XMLHttpRequest){
		XMLHttpRequestObject3 = new XMLHttpRequest();
	}
}

function check_opbrengstfactor(id)
{
    var hoek_zuiden = document.getElementById("hoek_z").value;
    var hoek_p = document.getElementById("hoek").value;
    var kwhkwp = document.getElementById("kwhkwp").value;
    var schaduw = document.getElementById("schaduw").checked;
    var schaduw_w = document.getElementById("winter").checked;
    var schaduw_z = document.getElementById("zomer").checked;
    var schaduw_lh = document.getElementById("lente_herfst").checked;
    var cus_id = id;
    
    //if( hoek_zuiden != '' && hoek_zuiden != 0 && hoek_p != '' && hoek_p != 0 && (kwhkwp == '' ||kwhkwp == 0) )
    if( hoek_zuiden != '' && hoek_p != '' )
    {
        DIVOK = "kwhkwp";
    	datasource = "klanten_ajax_kwhkwp.php?hoek_z=" + hoek_zuiden + "&hoek_p=" + hoek_p + "&cus_id=" + cus_id + "&schaduw=" + schaduw + "&schaduw_w=" + schaduw_w + "&schaduw_z=" + schaduw_z + "&schaduw_lh=" + schaduw_lh;
    
    	if(XMLHttpRequestObject3){
    		var obj = document.getElementById(DIVOK);
    
    		XMLHttpRequestObject3.open("GET",datasource,true);
    		XMLHttpRequestObject3.onreadystatechange = function(){
    			if(XMLHttpRequestObject3.readyState == 4 && XMLHttpRequestObject3.status == 200)
                {
                    if( parseInt(kwhkwp) == parseInt(XMLHttpRequestObject3.responseText) )
                    {
                        alert("De ingevulde en berekende opbrengstfactor zijn hetzelfde.");
                    }else
                    {
                        var antwoord = confirm("De huidige waarde is " + kwhkwp + "\nDe berekende waarde is " + XMLHttpRequestObject3.responseText + "\n\nDe berekende waarde overnemen?" );
                        if( antwoord )
                        {
                            obj.value = XMLHttpRequestObject3.responseText;    
                        }
                    }
    			}
    		}
    		
    		XMLHttpRequestObject3.send(null);
    	}
    }else
    {
        alert("Voor het berekenen van de opbrengstfactor zijn de 2 onderstaande velden verplicht.\n- Hoek van de panelen met het zuiden.\n-Hoek van de panelen");
    }
    
}

function check_stuur_mail_offerte(cus_id, cf_id)
{
    var email = document.getElementById("email").value;
    
    if( email == '' )
    {
        alert("Er is geen email adres ingevuld.");
    }else
    {
        var antwoord = confirm("Offerte werkelijk versturen?");
        
        if( antwoord )
        {
            window.open("mail_offerte.php?cus_id="+ cus_id +"&file="+ cf_id,"mail_offerte","width=100,height=100");
            document.getElementById("go_away_mail").style.display = "inline";
            
            $(function() {
    			$("#go_away_mail").fadeOut(5000);
    		});
        }
    }
}

function check_stuur_mail(cus_id, soort)
{
    var email = document.getElementById("email").value;
    
    if( email == '' )
    {
        alert("Er is geen email adres ingevuld.");
    }else
    {
        var antwoord = confirm("Mail werkelijk versturen?");
        
        if( antwoord )
        {
            window.open("mail_offerte_info.php?cus_id="+ cus_id +"&file="+ soort,"mail_offerte","width=100,height=100");
            document.getElementById("go_away_mail").style.display = "inline";
            
            $(function() {
    			$("#go_away_mail").fadeOut(5000);
    		});
        }
    }
}

function maakOfferte(cus_id)
{
    // Eerst nakijken ofdat al de velden ingevuld zijn.
    //var naam = document.getElementById("naam").value;
    var straat = document.getElementById("straat").value;
    var nr = document.getElementById("nr").value;
    var postcode = document.getElementById("postcode").value;
    var gemeente = document.getElementById("gemeente").value;
    var gsm = document.getElementById("gsm").value;
    var tel = document.getElementById("tel").value;
    var aant_panelen = document.getElementById("aant_panelen").value;
    var w_panelen = document.getElementById("w_panelen").value;
    var factor = document.getElementById("kwhkwp").value;
    var hoek_zuiden = document.getElementById("hoek_z").value;
    var hoek_panelen = document.getElementById("hoek").value;
    var dak = document.getElementById("soort_dak").value;
    var woning5j = document.getElementById("woning5j").value;
    var opwoning = document.getElementById("opwoning").value;
    var soortp = document.getElementById("type_panelen").value;
    
    var fout = false;
    var foutMsg = "Gelieve de volgende velden na te kijken :\n";
    
    if( straat == "" )
    {
        fout = true;
        foutMsg += "- straat\n";
    }
    
    if( nr == "" )
    {
        fout = true;
        foutMsg += "- huisnr\n";
    }
    
    if( postcode == "" )
    {
        fout = true;
        foutMsg += "- postcode\n";
    }
    
    if( gemeente == "" )
    {
        fout = true;
        foutMsg += "- gemeente\n";
    }

    if( tel == "" && gsm == "" )
    {
        fout = true;
        foutMsg += "- Telefoon en/of GSM \n";
    }
    
    if( aant_panelen == "" )
    {
        fout = true;
        foutMsg += "- Aantal panelen\n";
    }
    
    if( soortp == "" )
    {
        fout = true;
        foutMsg += "- Gewone of zwarte panelen? \n";
    }
    
    if( w_panelen == "" )
    {
        fout = true;
        foutMsg += "- Vermogen per paneel\n";
    }
    
    if( factor == "" || factor == 0 )
    {
        fout = true;
        foutMsg += "- Opbrengst factor\n";
    }
    
    if( factor == "" )
    {
        fout = true;
        foutMsg += "- Opbrengst factor\n";
    }
    
    if( nr == "" )
    {
        fout = true;
        foutMsg += "- nr\n";
    }
    
    if( hoek_zuiden == "" || hoek_zuiden == 0 )
    {
        fout = true;
        foutMsg += "- Hoek panelen met het zuiden:\n";
    }

    if( hoek_panelen == "" || hoek_panelen == 0  )
    {
        fout = true;
        foutMsg += "- Hoek van de panelen\n";
    }    
    
    if( dak == 0 )
    {
        fout = true;
        foutMsg += "- Soort dak\n";
    }
    
    if( woning5j == 2 )
    {
        fout = true;
        foutMsg += "- Woning ouder dan 5 jaar ?\n";
    }
    
    if( opwoning == 2 )
    {
        fout = true;
        foutMsg += "- Panelen op woning\n";
    }
    
    if( fout == false )
    {
        window.open("maak_offerte.php?cus_id=" + cus_id,"auto_offerte","left=200,width=860,height=800");    
    }else
    {
        alert(foutMsg);
    }
}

function isNumberKey(evt)
{
   var charCode = (evt.which) ? evt.which : evt.keyCode;

   if (charCode > 31 && (charCode < 48 || charCode > 57 ) && charCode != 46 && charCode != 44)
      return false;

   return true;
}

function commadot(that) {
	if (that.value.indexOf(",") >= 0) 
	{
		that.value = that.value.replace(/\,/g,".");
	}
}

function selectAlles(FieldName, dit)
{
	var CheckValue = dit.checked;

	var objCheckBoxes = document.forms["frm_factuur"].elements[FieldName];
	if(!objCheckBoxes)
		return;
	var countCheckBoxes = objCheckBoxes.length;
	if(!countCheckBoxes)
		objCheckBoxes.checked = CheckValue;
	else
		// set the check value for all check boxes
		for(var i = 0; i < countCheckBoxes; i++)
			objCheckBoxes[i].checked = CheckValue;
}

function toonElec(dit)
{
	if(dit.value == 1)
	{
		// tonen
		document.getElementById("elec1").style.display = "block";
		document.getElementById("elec2").style.display = "block";
		document.getElementById("elec3").style.display = "block";
		document.getElementById("elec4").style.display = "block";
	}else
	{
		// verbergen
		document.getElementById("elec1").style.display = "none";
		document.getElementById("elec2").style.display = "none";
		document.getElementById("elec3").style.display = "none";
		document.getElementById("elec4").style.display = "none";
	}
}

function toonElec_uit(dit)
{
	if(dit.value == 1)
	{
		// tonen
		document.getElementById("uit_elec1").style.display = "block";
		document.getElementById("uit_elec2").style.display = "block";
		document.getElementById("uit_elec3").style.display = "block";
		document.getElementById("uit_elec4").style.display = "block";
	}else
	{
		// verbergen
		document.getElementById("uit_elec1").style.display = "none";
		document.getElementById("uit_elec2").style.display = "none";
		document.getElementById("uit_elec3").style.display = "none";
		document.getElementById("uit_elec4").style.display = "none";
	}
}

function toonKlantNietTevree(dit)
{
	if(dit.value == 0 )
	{
		// tonen
		document.getElementById("niet_tevree1").style.display = "block";
		document.getElementById("niet_tevree2").style.display = "block";
		
	}else
	{
		//verbergen
		document.getElementById("niet_tevree1").style.display = "none";
		document.getElementById("niet_tevree2").style.display = "none";
	}
    
    if( dit.value == "" )
    {
        document.getElementById("niet_tevree1").style.display = "none";
		document.getElementById("niet_tevree2").style.display = "none";
    }
}

function uit_toonKlantNietTevree(dit)
{
	if(dit.value == 0 )
	{
		// tonen
		document.getElementById("uit_niet_tevree1").style.display = "block";
		document.getElementById("uit_niet_tevree2").style.display = "block";
		
	}else
	{
		//verbergen
		document.getElementById("uit_niet_tevree1").style.display = "none";
		document.getElementById("uit_niet_tevree2").style.display = "none";
	}
    
    if( dit.value == "" )
    {
        document.getElementById("uit_niet_tevree1").style.display = "none";
		document.getElementById("uit_niet_tevree2").style.display = "none";
    }
}

function showFacadres(dit)
{
	if(dit.checked == true)
	{
		document.getElementById("id_facadres").style.display = "block";
	}else
	{
		document.getElementById("id_facadres").style.display = "none";
	}
}

function maakPrijs()
{
	var ppwp = document.getElementById("ppwp").value;
	var aant_panelen = document.getElementById("aant_panelen").value;
	var w_panelen = document.getElementById("w_panelen").value;

	if( ppwp != "" && ppwp != 0 && aant_panelen != "" && aant_panelen != 0 && w_panelen != "" && w_panelen != 0 )
	{
		var tmp_prijs = (ppwp * aant_panelen * w_panelen)+100;
		document.getElementById("bedrag_excl").value = tmp_prijs.toFixed(2);
	}

	berekenPrijs();
}

function gotoKlant(cus_id1)
{
	document.getElementById("cus_id1").value = cus_id1;
	document.getElementById("frm_overzicht").submit();	
}

function checkDriefase(dit)
{
	var ac = document.getElementById("ac_vermogen").value;

	if( ac <= 5000 )
	{
		document.getElementById("driefase_noodzakelijk").innerHTML = "&nbsp;Neen";
	}else
	{
		document.getElementById("driefase_noodzakelijk").innerHTML = "&nbsp;<span class='error'>Ja</span>";
	}
}

function getOfferte()
{
	
}

function getInverters()
{
	var newSel = document.createElement('select');
	var nzn = document.getElementById("nzn").value;
		
	newSel.setAttribute('id', 'werk_omvormers[]');
	newSel.setAttribute('name', 'werk_omvormers[]');
	newSel.setAttribute('class', 'lengte');

	var newOpt = document.createElement('option');
	newOpt.setAttribute('value', 0);
	newOpt.innerHTML = '== Keuze ==';
	newSel.appendChild(newOpt);
	
	<?php 
		foreach( $list_inv as $key => $inv )
		{
	?>
			if( nzn == 0 )
			{ 
				var newOpt = document.createElement('option');
				newOpt.setAttribute('value', <?php echo $key; ?>);
				newOpt.innerHTML = '<?php echo $inv; ?>';
				newSel.appendChild(newOpt);
			}else
			{
				<?php 
				if( !stristr($inv, "TL") )
				{
				?>
					var newOpt = document.createElement('option');
					newOpt.setAttribute('value', <?php echo $key; ?>);
					newOpt.innerHTML = '<?php echo $inv; ?>';
					newSel.appendChild(newOpt);
				<?php 
				}
				?>
			}
	<?php 
		}
	?>
	
	document.getElementById('extra_inverters').innerHTML += "<br/>";
	document.getElementById('extra_inverters').appendChild( newSel );
}


function getInverters_uit()
{
	var newSel = document.createElement('select');
	var nzn = document.getElementById("uit_nzn").value;
		
	newSel.setAttribute('id', 'uit_werk_omvormers[]');
	newSel.setAttribute('name', 'uit_werk_omvormers[]');
	newSel.setAttribute('class', 'lengte');

	var newOpt = document.createElement('option');
	newOpt.setAttribute('value', 0);
	newOpt.innerHTML = '== Keuze ==';
	newSel.appendChild(newOpt);
	
	<?php 
		foreach( $list_inv as $key => $inv )
		{
	?>
			if( nzn == 0 )
			{ 
				var newOpt = document.createElement('option');
				newOpt.setAttribute('value', <?php echo $key; ?>);
				newOpt.innerHTML = '<?php echo $inv; ?>';
				newSel.appendChild(newOpt);
			}else
			{
				<?php 
				if( !stristr($inv, "TL") )
				{
				?>
					var newOpt = document.createElement('option');
					newOpt.setAttribute('value', <?php echo $key; ?>);
					newOpt.innerHTML = '<?php echo $inv; ?>';
					newSel.appendChild(newOpt);
				<?php 
				}
				?>
			}
	<?php 
		}
	?>
	
	document.getElementById('uit_extra_inverters').innerHTML += "<br/>";
	document.getElementById('uit_extra_inverters').appendChild( newSel );
}


function checkConform()
{
	var p1 = document.getElementById("w_panelen").value;
	var p2 = document.getElementById("werk_w_panelen").value;
	var aant1 = document.getElementById("aant_panelen").value;
	var aant2 = document.getElementById("werk_aant_panelen").value;

	if( p1 == p2 && aant1 == aant2 )
	{
		document.getElementById("conform_offerte").innerHTML = "Ja";
		document.getElementById("id_orderbon").innerHTML = "Neen";
			
	}else
	{
		document.getElementById("conform_offerte").innerHTML = "<span class='error'>Neen</span>";
		document.getElementById("id_orderbon").innerHTML = "<span class='error'>Ja</span>";
	}
}

function berekenPrijs()
{
	dit = document.getElementById("woning5j"); 
	
	if( dit.value == 1 || dit.value == 0 )
	{
		var prijs = parseFloat(document.getElementById("bedrag_excl").value);
		var btw = 0;
	
		if( dit.value == 0 )
		{
			btw = 1.21;
		}else
		{
			btw = 1.06;
		}

		if( document.getElementById("btw_edit").value != "" )
		{
			btw = 1.21;
		}

		prijs = prijs * btw;
	
		document.getElementById("id_bedrag_incl").innerHTML = "&euro; " + prijs.toFixed(2);;
	}
}

function checkInOA(dit)
{
	if( dit.checked == true )
	{
		// bij 
		document.getElementById("in_oa").style.display = "block";

		// acma
		document.getElementById("showhide1").style.display = "none";

		// installatie
		document.getElementById("showhide3").style.display = "block";

		// facturatie
		document.getElementById("tabel2").style.display = "block";

		// opvolging
		document.getElementById("tabel4").style.display = "none";

		// offerte
		document.getElementById("showhide2").style.display = "block";

		switchOA( "none" );
		
	}else
	{ 
		// bij 
		document.getElementById("in_oa").style.display = "none";

		// acma
		document.getElementById("showhide1").style.display = "block";

		// installatie
		document.getElementById("showhide3").style.display = "none";

		if( document.getElementById("verkoop").value != '1' )
		{
			// facturatie
			document.getElementById("tabel2").style.display = "none";
		}

		// opvolging
		document.getElementById("tabel4").style.display = "none";

		// offerte
		document.getElementById("showhide2").style.display = "block";

		switchOA( "block" );
	}
}


function switchOA( waarde )
{
	for (tel=2;tel<=110;tel++)
	{
		if( document.getElementById("id_off" + tel) )
		{
			document.getElementById("id_off" + tel).style.display = waarde;
		}
	}
}

function viewTable2(dit)
{
	if( dit.value == 0 )
	{
		document.getElementById("tabel2").style.display = "none";
		document.getElementById("tabel4").style.display = "none";
		document.getElementById("tabel3").style.display = "block";
		
		// gedeelte onder verkoop
		document.getElementById("showhide3").style.display = "none";
		document.getElementById("showhide4").style.display = "none";
	}else
	{
		// nakijken ofdat het huur of verkoop is.
		if( dit.value == 1 )
		{
			// verkoop actief
            for( i=1; i<15; i++ )
            {
                if( document.getElementById("verkoop" + i) )
                {
                    document.getElementById("verkoop" + i).style.display = "block";
                }
            } 
            
			// verhuur inactief
            for( i=1; i<15; i++ )
            {
                if( document.getElementById("verhuur" + i) )
                {
                    document.getElementById("verhuur" + i).style.display = "none";
                }
            } 
		}

		if( dit.value == 2 )
		{
			// verhuur actief
            for( i=1; i<15; i++ )
            {
                if( document.getElementById("verhuur" + i) )
                {
                    document.getElementById("verhuur" + i).style.display = "block";
                }
            } 
            
			// verkoop inactief
            for( i=1; i<15; i++ )
            {
                if( document.getElementById("verkoop" + i) )
                {
                    document.getElementById("verkoop" + i).style.display = "none";
                }
            }
		}
		
		document.getElementById("tabel3").style.display = "none";
		document.getElementById("tabel2").style.display = "block";
		document.getElementById("tabel4").style.display = "block";

		// gedeelte onder verkoop
		document.getElementById("showhide3").style.display = "block";
		document.getElementById("showhide4").style.display = "block";

		// waarde overnemen indien er nog geen waardes ingevuld zijn.
		if( document.getElementById("werk_aant_panelen").value == 0 && document.getElementById("werk_w_panelen").value == 0 )
		{ 
			document.getElementById("werk_aant_panelen").value = document.getElementById("aant_panelen").value;
			document.getElementById("werk_w_panelen").value = document.getElementById("w_panelen").value;
			document.getElementById("werk_merk_panelen").value = document.getElementById("merk_panelen").value; 
		}
	}
    
    if( dit.value == "" )
    {
        document.getElementById("tabel3").style.display = "none";
    } 
}

function viewTable2_uit(dit)
{
	if( dit.value == 0 )
	{
		document.getElementById("uit_tabel2").style.display = "none";
		document.getElementById("uit_tabel3").style.display = "block";
        document.getElementById("uit_tabel4").style.display = "none";
        document.getElementById("uit_showhide3").style.display = "none";
        document.getElementById("uit_showhide4").style.display = "none";
	}else
	{
		document.getElementById("uit_tabel2").style.display = "block";
        document.getElementById("uit_tabel3").style.display = "none";
        document.getElementById("uit_tabel4").style.display = "block";
        document.getElementById("uit_showhide3").style.display = "block";
        document.getElementById("uit_showhide4").style.display = "block";

		// waarde overnemen indien er nog geen waardes ingevuld zijn.
		if( document.getElementById("uit_werk_aant_panelen").value == 0 && document.getElementById("uit_werk_w_panelen").value == 0 )
		{ 
			document.getElementById("uit_werk_aant_panelen").value = document.getElementById("uit_aant_panelen").value;
			document.getElementById("uit_werk_w_panelen").value = document.getElementById("uit_w_panelen").value;
			document.getElementById("uit_werk_merk_panelen").value = document.getElementById("uit_merk_panelen").value; 
		}
	}
    
    if( dit.value == "" )
    {
        document.getElementById("uit_tabel3").style.display = "none";
    } 
}

function maakUitbreiding(dit, klant_id)
{
	if( dit.checked == true )
	{
		document.getElementById("id_uitbreiding").style.display = 'block'; 
	}else
	{
		var answer = confirm("Uitbreiding verwijderen?");
		if (answer){
			document.getElementById("id_uitbreiding").style.display = 'none';
		}
	}
}

$(function() {
	$( "#nw_offerte_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#offerte_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#offerte_gemaakt" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#datum_vreg" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#installatie_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#installatie_datum2" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#installatie_datum3" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#installatie_datum4" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#gecontacteerd" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#datum_net" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#verkoop_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#datum_arei").datetimepicker({dateFormat: 'dd-mm-yy'});
    $( "#datum_arei1").datepicker({dateFormat: 'dd-mm-yy'});
    
	$( "#offerte_besproken1" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#offerte_besproken2" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#offerte_besproken3" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#opmeting_datum" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#elec_datum" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#datum_orderbon" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#nw_installatie_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#installatie_aanp" ).datepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#datum_dom" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	
	// UIT gedeelte
	$( "#uit_offerte_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_offerte_gemaakt" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_datum_vreg" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_installatie_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#uit_installatie_datum2" ).datepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#uit_installatie_datum3" ).datepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#uit_installatie_datum4" ).datepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#uit_nw_installatie_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_gecontacteerd" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_datum_net" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_verkoop_datum" ).datepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_datum_arei").datetimepicker({dateFormat: 'dd-mm-yy'});
	$( "#uit_offerte_besproken1" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_offerte_besproken2" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_offerte_besproken3" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_opmeting_datum" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_elec_datum" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
	$( "#uit_datum_orderbon" ).datetimepicker( { dateFormat: 'dd-mm-yy' } );
    $( "#uit_installatie_aanp" ).datepicker( { dateFormat: 'dd-mm-yy' } );
});

jQuery(document).ready(function() {
	$("#various5").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'iframe'
	});

	$("#various6").fancybox({
		'width'				: '60%',
		'height'			: '70%',
	    'autoScale'     	: true,
	    'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'type'				: 'iframe'
	});
});

function checkref( dit )
{
	if( dit.checked == true )
	{
		document.getElementById("ref1").style.display = "block";
		document.getElementById("ref2").style.display = "block";
		document.getElementById("ref3").style.display = "block";
		document.getElementById("ref4").style.display = "block";
	}else
	{
		document.getElementById("ref1").style.display = "none";
		document.getElementById("ref2").style.display = "none";
		document.getElementById("ref3").style.display = "none";
		document.getElementById("ref4").style.display = "none";
	}
}

//begin ajax invite
function selectAll(selectBox,selectAll) {
    // have we been passed an ID
    if (typeof selectBox == "string") {
        selectBox = document.getElementById(selectBox);
    }

    // is the select box a multiple select box?
    if (selectBox.type == "select-multiple") {
        for (var i = 0; i < selectBox.options.length; i++) {
            selectBox.options[i].selected = selectAll;
        }
    }
}

function delOption(dit)
{
	var elSel = document.getElementById('invitees[]');
  	var i;

  	for (i = elSel.length - 1; i>=0; i--) 
  	{
    	if (elSel.options[i].selected) 
        {
      		elSel.remove(i);
    	}
	}
}


function inviteAjax()
{
	var selObj = document.getElementById("sel_invite");
	var selObj1 = document.getElementById("invitees[]");
	var user = document.getElementById("sel_invite").value;

	if( user != 0 )
	{
		var selIndex = selObj.selectedIndex;
		
		var elOptNew = document.createElement('option');
		elOptNew.text = selObj.options[selIndex].text;
		elOptNew.value = user;
	
		try {
			selObj1.add(elOptNew, null); // standards compliant; doesn't work in IE
		}
		catch(ex) {
			selObj1.add(elOptNew); // IE only
		}
	}
}
// einde ajax invite


var XMLHttpRequestObject1 = false;

try{
	XMLHttpRequestObject1 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject1 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject1 = false;
	}
 
	if(!XMLHttpRequestObject1 && window.XMLHttpRequest){
		XMLHttpRequestObject1 = new XMLHttpRequest();
	}
}

function checkCity(dit)
{
	DIVOK = "n_gemeente";
	datasource = "klanten_ajax2.php?postcode=" + dit.value;

	if(XMLHttpRequestObject1){
		var obj = document.getElementById(DIVOK);

		XMLHttpRequestObject1.open("GET",datasource,true);
		XMLHttpRequestObject1.onreadystatechange = function(){
			if(XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200){
				obj.value = XMLHttpRequestObject1.responseText;
			}
		}
		
		XMLHttpRequestObject1.send(null);
	}
}

// berekenen van ppwp

var XMLHttpRequestObject2 = false;

try{
	XMLHttpRequestObject2 = new ActiveXObject("MSXML2.XMLHTTP");
}catch(exception1){
	try{
		XMLHttpRequestObject2 = new ActiveXObject("Microsoft.XMLHTTP");
	}catch(exception2){
		XMLHttpRequestObject2 = false;
	}
 
	if(!XMLHttpRequestObject2 && window.XMLHttpRequest){
		XMLHttpRequestObject2 = new XMLHttpRequest();
	}
}

function getPpwp()
{
	var paneel = document.getElementById("aant_panelen").value;
	var dak = document.getElementById("soort_dak").value;
    var type_paneel = document.getElementById("type_panelen").value;

    var ori_dak = dak;

/*
 $daksoorten[1] = "Plat dak/pannen dak";
 $daksoorten[2] = "Leien dak";
 $daksoorten[3] = "Schans/Zinken dak";


 $daksoorten[1] = "Plat dak";
 $daksoorten[2] = "pannen dak";
 $daksoorten[3] = "Leien dak";
 $daksoorten[4] = "Schans";
 $daksoorten[5] = "Zinken dak";
 $daksoorten[6] = "Steeldeck";
 $daksoorten[7] = "golf dak";
 $daksoorten[8] = "overzetdak";
 $daksoorten[9] = "Schans op voeten";
 $daksoorten[10] = "Hellend roofing dak";
 
 */

 /* remapping */
	if( dak == 1 || dak == 2|| dak == 6 || dak == 7 || dak == 10 )
	{
		dak = 1;
	}

	if( dak == 3 || dak == 8 )
	{
		dak = 2;
	} 

	if( dak == 4 || dak == 5 || dak == 9 )
	{
		dak = 3;
	}
	
	
	if( paneel > 0 && dak > 0 ) 
	{
		DIVOK = "ppwp";
		datasource = "klanten_ajax3.php?paneel=" + paneel + "&dak=" + dak + "&type_paneel=" + type_paneel + "&ori_dak=" + ori_dak;
	
		if(XMLHttpRequestObject2){
			var obj = document.getElementById(DIVOK);
	
			XMLHttpRequestObject2.open("GET",datasource,true);
			XMLHttpRequestObject2.onreadystatechange = function(){
				if(XMLHttpRequestObject2.readyState == 4 && XMLHttpRequestObject2.status == 200){
					obj.value = XMLHttpRequestObject2.responseText;
					maakPrijs();
				}
			}
			
			XMLHttpRequestObject2.send(null);
		}
	}
}

// einde berekenen van ppwp

$().ready(function() {
	$("#klant").autocomplete("klanten_ajax.php", {
		width: 260,
		matchContains: false,
		mustMatch: false,
		//minChars: 0,
		//multiple: true,
		//highlight: false,
		//multipleSeparator: ",",
		selectFirst: false
	});
	
	$("#klant").result(function(event, data, formatted) {
		$("#klant_val").val(data[1]);
	});
});

$(function() {
	$( "#tabs" ).tabs({ selected: <?php if( isset( $_REQUEST["tab_id"] ) ){ echo $_REQUEST["tab_id"]; }else{ echo 0; };  ?> });
});

</script>

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-24625187-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body onload='checkConform();getPpwp();maakPrijs();'>

<div id='pagewrapper'><img src='images/header.png' /><br />
<div id='logout'><a href='logout.php'><?php echo $_SESSION["kalender_user"]->naam; ?>
&nbsp;Uitloggen</a></div>
<a href='menu.php'>&lt;&lt;&lt; Terug</a>
<?php

if( $_SESSION["kalender_user"]->group_id == 1 )
{
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='facturatie.php'>Facturatie</a>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='overzicht_klanten.php'>Overzicht</a>";
}
?>

<h1>Particuliere klanten</h1>

<div id="tabs">
<ul>
	<li><a href="#tabs-1">Nieuw</a></li>
	<li><a href="#tabs-2">Zoek</a></li>
	<li><a href="#tabs-4">Overzicht</a></li>
	<li><a href="#tabs-5">Nog bespreken</a></li>
	<?php

	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
	{
		?>
	<li><a href="#tabs-6">ACMA toekennen</a></li>
	<?php
	}

	?>
	
	<li><a href="#tabs-7">Verkoop</a></li>
    <li><a href="#tabs-7a">Verhuur</a></li>
    <li><a href="#tabs-7b" style='font-size:0.63em;'>Geen <br/> overeenkomst </a></li>
	<?php 
	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 )
	{
		?>
		<li><a href="#tabs-8" style='font-size:0.63em;'>Intekenen <br/> Opmeten </a></li>
		<?php 
	}
	?>
	<?php

	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
	{
		?>
		<li><a href="#tabs-9">Controle</a></li>
		<?php
	}
	?>
	
	<li><a href="#tabs-10" style='font-size:0.63em;'>Uitgebreid<br/>Zoeken</a></li>
</ul>
<div id="tabs-1">

Nieuwe klant toevoegen. Bij het toekennen van een ACMA krijg deze een e-mail.<br/><br/>

<form method='post' id='frm_new_cus' name='frm_new_cus' action='' enctype='multipart/form-data'>

<table>
	<tr>
		<td>Naam:</td>
		<td><input type='text' name='n_naam' id='n_naam' class='lengte' value='' />
		</td>
	</tr>

	<tr>
		<td>Bedrijf:</td>
		<td><input type='text' name='n_bedrijf' id='n_bedrijf' class='lengte'
			value='' /></td>
	</tr>
	<tr>
		<td>BTW nr.:</td>
		<td><input type='text' name='n_btw' id='n_btw' class='lengte' value='' />
		</td>
	</tr>
	<tr>
		<td>Straat &amp; Nr.:</td>
		<td><input type='text' name='n_straat' id='n_straat' value='' /> <input
			type='text' name='n_nr' id='n_nr' value='' size='4' /></td>
	</tr>
	<tr>
		<td>Postcode &amp; gemeente:</td>
		<td><input type='text' name='n_postcode' id='n_postcode' size='4' value=''
			onblur='checkCity(this);' /> <input type='text' name='n_gemeente'
			id='n_gemeente' value='' /></td>
	</tr>
	<tr>
		<td>E-mail:</td>
		<td><input type='text' name='n_email' id='n_email' class='lengte' value='' />
		</td>
	</tr>
	<tr>
		<td>Tel.:</td>
		<td><input type='text' name='n_tel' id='n_tel' class='lengte' value='' />
		</td>
	</tr>
	<tr>
		<td>GSM:</td>
		<td><input type='text' name='n_gsm' id='n_gsm' class='lengte' value='' />
		</td>
	</tr>
	<tr>
		<td colspan='2' align='center'>&nbsp;</td>
	</tr>

	<tr>
		<td>ACMA:</td>
		<td>
            <select name='nw_acma' id='nw_acma' class='lengte'>
			<option value=''></option>
			<?php
			foreach( $acma_arr as $key => $acma )
			{
				if( $_SESSION["kalender_user"]->group_id == 3 )
				{
					if( $key == $_SESSION["kalender_user"]->user_id )
					{
						echo "<option value='". $key ."'>". $acma ."</option>";
					}

					if( $_SESSION["kalender_user"]->user_id == 29 && in_array( $key, $klanten_onder_frans_arr ) )
					{
						echo "<option value='". $key ."'>". $acma ."</option>";
					}
				}else
				{
					echo "<option value='". $key ."'>". $acma ."</option>";
				}
                
                $exclude_klant[ $key ] = $key;
			}
			?>
		</select></td>
	</tr>

	<tr>
		<td>Datum offerte Aanvraag:</td>
		<td><input type='text' name='nw_offerte_datum' id='nw_offerte_datum'
			class='lengte' value='' /></td>
	</tr>

	<tr>
		<td colspan='2' align='center'>&nbsp;</td>
	</tr>

	<tr>
		<td colspan='2' align='center'><input type='submit' name='bewaar'
			id='bewaar' value='Bewaar' /></td>
	</tr>
</table>

<input type='hidden' name='tab_id' id='tab_id' value='0' /></form>
</div>

<div id="tabs-2">
<table width='100%'>
	<tr>
		<td><?php 

		if( $_SESSION["kalender_user"]->group_id == 3 )
		{
			$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_acma = ". $_SESSION["kalender_user"]->user_id ." ORDER BY cus_naam, cus_bedrijf");
		}else
		{
			$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers ORDER BY cus_naam, cus_bedrijf");
		}
		?>
        
<script type="text/javascript">
function searchKlant()
{
    var klant = document.getElementById("klant_val").value;
    //alert(klant);
    document.frm_klant.action = "http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=" + klant;
    //document.frm_klant.action = "http://www.google.be";
    document.frm_klant.submit();
}        
</script>
		<form autocomplete="off" method='post' id='frm_klant' name='frm_klant' action="">
		<label for='klant'>Zoek klant :</label> <input type="text" name="klant" id="klant" /> 
		<input type="hidden" name="klant_id" id="klant_val" /> 
		<input type='hidden' name='tab_id' id='tab_id' value='1' /> 
		<input type="button" name="button" onclick="searchKlant();" value="Go" />
		</form>
		</td>
		<td align='right'>
        <?php

		if( isset( $_POST["pasaan"] ) && $_POST["cus_id2"] > 0 )
		{
			?> <script type='text/javascript'>
					$(function() {
						$("#go_away1").fadeOut(5000);
					});
					</script> <?php
					if( isset( $_POST["invitees"] ) && count( $_POST["invitees"] ) > 0 )
					{
						echo "<span id='go_away1' class='correct' >Gegevens zijn bewaard &amp; uitgenodigden gemaild.</span>";
					}else
					{
						echo "<span id='go_away1' class='correct' >Gegevens zijn bewaard</span>";	
					}
		}

        echo "<span id='go_away_mail' style='display:none;' class='correct' >E-mail is verstuurd.</span>";

		if( $verwijderen == 1 )
		{
			?> <script type='text/javascript'>
					$(function() {
						$("#go_away2").fadeOut(5000);
					});
					</script> <?php
					echo "<span id='go_away2' class='correct' >Klant is verwijderd</span>";
		}

		if( ((isset( $_POST["submit"] ) && $_POST["submit"] == "Go") || $_POST["cus_id1"] > 0 || $_POST["cus_id2"] > 0 || (int)$_REQUEST["klant_id"] > 0) && $verwijderen == 0 )
		{
			echo "&nbsp;&nbsp;&nbsp;";
			//echo "test:".$_REQUEST["klant_id"];
			if( isset( $_REQUEST["klant_id"] )  )
			{
				$_POST["klant_val"] = $_REQUEST["klant_id"];
			}

			if( isset( $_POST["cus_id1"] ) )
			{
				$_POST["klant_val"] = $_POST["cus_id1"];
			}

			if( isset( $_POST["cus_id2"] ) )
			{
				$_POST["klant_val"] = $_POST["cus_id2"];
			}
			
            if( $_SESSION["kalender_user"]->user_id == 19 || $_SESSION["kalender_user"]->user_id == 26 )
            {
                // als er coda bestanden zijn dan de knop weergeven.
                
                $aant_coda = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_coda WHERE cus_id = " . $_POST["klant_val"]));
                
                if( $aant_coda > 0 )
                {
			?>
			<input type='button' value='CODA' onclick="window.open('klanten_coda.php?klant_id=<?php echo $_POST["klant_val"];  ?>','Coda','status,width=1100,height=800,scrollbars=yes'); return false;" />
            <?php
                }
            }
            
            
            ?>
            
            
            <input type='button' value='Geschiedenis' onclick="window.open('geschiedenis.php?klant_id=<?php echo $_POST["klant_val"];  ?>','geschiedenis','status,width=1100,height=800,scrollbars=yes'); return false;" />
			<?php 
		}
		?></td>
	</tr>
</table>

		<?php

		if( ((isset( $_POST["submit"] ) && $_POST["submit"] == "Go") || $_POST["cus_id1"] > 0 || $_POST["cus_id2"] > 0 || $_REQUEST["klant_id"] > 0 ) && $verwijderen == 0 )
		{
			echo "<br/>";

			if( isset( $_REQUEST["klant_id"] ) && !isset( $_POST["klant_val"] ) )
			{
				$_POST["klant_val"] = $_REQUEST["klant_id"];
			}

			if( isset( $_POST["cus_id1"] ) )
			{
				$_POST["klant_val"] = $_POST["cus_id1"];
			}

			if( isset( $_POST["cus_id2"] ) )
			{
				$_POST["klant_val"] = $_POST["cus_id2"];
			}

			if( $_POST["klant_val"] == "" )
			{
				echo "Geen klanten gevonden.";
			}else
			{
			     /*
                 Verwijderen van de regel in tabel user_open_cus_id
                 daarna user_id en cus_id toevoegen
                 */
                 // BEGIN CONTROLE OM TE ZIEN WIE DE KLANT GEOPEND HEEFT
                 $q_del = "DELETE FROM monitoring.user_open_cus_id WHERE user_id = " . $_SESSION["kalender_user"]->user_id;
                 mysqli_query($conn,  $q_del) or die( mysqli_error($conn) );
                 
                 $q_ins = "INSERT INTO monitoring.user_open_cus_id(user_id, cus_id) VALUES(".$_SESSION["kalender_user"]->user_id.",".$_POST["klant_val"].")";
                 mysqli_query($conn,  $q_ins) or die( mysqli_error($conn) );
                 // EINDE TEST
                 
                 // UITLEZEN VAN DE NIEUWE TABEL EN TONEN WIE DAT ER DEZE KLANT GEOPEND HEEFT.
                 $q_zoek_klant = mysqli_query($conn, "SELECT * FROM monitoring.user_open_cus_id WHERE cus_id = " . $_POST["klant_val"]);
                 
                 if( mysqli_num_rows($q_zoek_klant) > 0 )
                 {
                     $tmp_g = "";
                     while( $u = mysqli_fetch_object($q_zoek_klant) )
                     {
                        $dt = explode("-", substr( $u->datetime, 0, 10) );
                        $dt = $dt[0] . $dt[1] . $dt[2];
                        
                        if( $dt < $nu )
                        {
                            // verwijderen van de regel van geopende klant uit het verleden
                            $q_del = "DELETE FROM monitoring.user_open_cus_id WHERE id = " . $u->id;
                            mysqli_query($conn,  $q_del) or die( mysqli_error($conn) );
                        }else
                        {
                            if( $u->user_id != $_SESSION["kalender_user"]->user_id )
                            {
                                $gebruiker = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_users WHERE user_id = " . $u->user_id));
                                $tmp_g .= $gebruiker->voornaam . ", ";
                            }
                        }
                     }
                     
                     if( !empty( $tmp_g ) )
                     {
                         echo "<div style='width:943px;height:20px;border:2px solid orange;background-color:#FFFFCC;padding:2px;padding-left:10px;padding-top:4px;'>";
                         echo "Deze klant staat open bij de volgende gebruiker(s) : ";
                         echo substr( $tmp_g, 0, -2);
                         echo "</div>";
                         echo "<br/>";
                     }
                 }
                  
             
				$q_cus = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_POST["klant_val"]);
				$aant_cus = mysqli_num_rows($q_cus);
				$cus = mysqli_fetch_object($q_cus);
				
				// zoeken en weergeven ofdat deze klant een uitbreiding heeft.
				
				$zoek_uitbr = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $cus->cus_id . " AND cus_active='1'"));

				if( $zoek_uitbr > 0 )
				{
					//echo "<a href='?tab_id=1&klant_id=". $cus->cus_id ."#goto_uitbreiding' style='font-weight:800;color:blue;'>";
                    echo "<a href='#goto_uitbreiding' style='font-weight:800;color:blue;'>";
					echo "Ga naar uitbreiding.";
					echo "</a>";
				}
				
				//echo "<form method='post' action='". str_replace("/kalender", "", $_SERVER['PHP_SELF']) ."' id='frm_go' enctype='multipart/form-data'>";
                echo "<form method='post' action='". $_SERVER['PHP_SELF'] ."' id='frm_go' class='frm_go' name='frm_go' enctype='multipart/form-data'>";
                
                $isProject = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $cus->cus_id));
                
                if( $isProject )
                {
                    echo "<table border='0' width='100%' class='main_table' style='background-color:lightblue;' >";
                }else
                {
                    echo "<table border='0' width='100%' class='main_table' >";    
                }
				
                
				echo "<tr>";
				echo "<td valign='top' width='50%'>";

				// begin eerste tabel
				echo "<table border='0'>";
				echo "<tr><td colspan='2'>";
				
                ////////////////
                
                
                
                // zoeken ofdat er een koppeling kan gemaakt worden, tussen solarlogs.be en futech.be
                $q_zoek_klant = mysqli_query($link_futech, "SELECT * FROM UserData WHERE UserEmail = '". $cus->cus_email ."'");
                $aant_f1 = mysqli_num_rows($q_zoek_klant);
                
                if( $cus->cus_verkoop == "1" || $cus->cus_verkoop == "2" || $aant_f1 > 0 )
                {
                    /*************************************************** OPHALEN VAN DE GEGEVENS VAN OP DE WEBSITE *******************************************/
                    
                    if( mysqli_num_rows($q_zoek_klant) > 0 )
                    {
                        $zoek_klant = mysqli_fetch_object($q_zoek_klant);
                        $client = mysqli_fetch_object(mysqli_query($link_futech, "SELECT * FROM futech_client WHERE user_id = " . $zoek_klant->UserId));
                    }
                    
                    /*************************************************** BEPALEN VAN DE VERWACHTE OPBRENGST *******************************************/
                    /*
                    * AREI keuring moet ingevuld zijn
                    * aantal panelen 
                    * vermogen per paneel
                    * opbrengstfactor
                    */
                    // nakijken of deze klant al gedaan is
                    
                    // als de 4 factoren verschillen dan al de regels verwijderen en laten herberekenen
                    //echo "<br>" . $cus->cus_arei_datum . " " . $cus->cus_werk_aant_panelen . " " . $cus->cus_werk_w_panelen . " " . $cus->cus_kwhkwp;
                    if( !empty( $cus->cus_arei_datum ) && !empty($cus->cus_werk_aant_panelen) && !empty($cus->cus_werk_w_panelen) && !empty($cus->cus_kwhkwp) )
                    {
                        // params toevoegen in db
                        $bereken = 0;
                        
                        $q_param = mysqli_query($conn, "SELECT * 
                                                  FROM kal_customers_kwh_param 
                                                 WHERE cus_id = " . $cus->cus_id);
                        
                        if( mysqli_num_rows($q_param) == 0 )
                        {
                            $bereken = 1;
                            
                            $q_ins = "INSERT INTO kal_customers_kwh_param(cus_id,factor,aantal_p,vermogen_p,arei) VALUES(".$cus->cus_id.",".$cus->cus_kwhkwp.",".$cus->cus_werk_aant_panelen.",".$cus->cus_werk_w_panelen.",'". changeDate2EU( substr($cus->cus_arei_datum, 0, 10) ) ."')";
                            mysqli_query($conn, $q_ins);
                        }else
                        {
                            $param = mysqli_fetch_object($q_param);
                            
                            if( $param->factor != $cus->cus_kwhkwp ||
                                $param->aantal_p != $cus->cus_werk_aant_panelen ||
                                $param->vermogen_p != $cus->cus_werk_w_panelen ||
                                $param->arei !=  changeDate2EU( substr($cus->cus_arei_datum, 0, 10) )  )
                            {
                                
                                // delete van huidige waardes in kal_customer_kwh
                                $q_del = "DELETE FROM kal_customers_kwh WHERE cus_id = " . $cus->cus_id;
                                mysqli_query($conn,  $q_del) or die( mysqli_error($conn) );
                                
                                // update van kwh_param
                                $q_upd= "UPDATE kal_customers_kwh_param SET factor = ". $cus->cus_kwhkwp .", 
                                                                            aantal_p = ". $cus->cus_werk_aant_panelen .",
                                                                            vermogen_p = ". $cus->cus_werk_w_panelen .",
                                                                            arei = '". changeDate2EU( substr($cus->cus_arei_datum, 0, 10) ) ."' WHERE cus_id = " . $cus->cus_id;
                                
                                mysqli_query($conn,  $q_upd) or die( mysqli_error($conn) );
                                
                                
                                $bereken = 1;
                            }
                        }

                        
                        if( $bereken == 1 )
                        {
                            
                            $deg = 0.005;
                            $deg_fac = array();
                            
                            for( $i=1;$i<100;$i++ )
                            {
                                $deg_fac[$i] = number_format( pow((1-$deg), $i), 6, ".", "" );
                            }
                            
                            // bepalen van de verwacht opbrengst
                            // ophalen van de verdeling per maand
                            $jaaropbrengst = ($cus->cus_werk_aant_panelen * $cus->cus_werk_w_panelen) * ( $cus->cus_kwhkwp / 1000 );
                            
                            $perc = array();
                            $q_maand = mysqli_query($link_futech, "SELECT * FROM futech_percentage WHERE year_num = 0");
                            
                            while( $rij = mysqli_fetch_object($q_maand) )
                            {
                                $perc[ (int)$rij->month_num ] = $rij->percentage / 100;
                            }
                            
                            $arei_dmy = explode("-", substr($cus->cus_arei_datum, 0, 10));
                            
                            $start = substr($cus->cus_arei_datum, 0, 10);
                            $einde = date("d-m-Y", mktime(0, 0, 0, $arei_dmy[1], $arei_dmy[0], $arei_dmy[2]+20 ) );
                            
                            $start_dmy = explode("-", $start);
                            $einde_dmy = explode("-", $einde);
                            
                            $mk_einde = mktime( 0, 0, 0, $einde_dmy[1], $einde_dmy[0], $einde_dmy[2] );
                            $mk_start = mktime( 0, 0, 0, $start_dmy[1], $start_dmy[0], $start_dmy[2] );
    
                            $cum_dag_waarde = 0;
                            
                            $jaar = 1;
                            while( $mk_start < $mk_einde )
                            {
                                $dag_perc = $perc[ (int)$start_dmy[1] ] / date("t", $mk_start );
                                $dag_waarde = $jaaropbrengst * $dag_perc * $deg_fac[$jaar];
                                
                                $cum_dag_waarde += $dag_waarde;
                                
                                //echo "<br>" . $start_dmy[0] . "-" . $start_dmy[1] . "-" . $start_dmy[2] . " " . number_format($cum_dag_waarde, 2, ".", "");
                                
                                $q_ins = "INSERT INTO kal_customers_kwh(cus_id, jaar, maand, dag, waarde) VALUES(". $cus->cus_id .",".$start_dmy[2].",".$start_dmy[1].",".$start_dmy[0].",'". number_format($cum_dag_waarde, 2, ".", "") ."')";
                                mysqli_query($conn,  $q_ins) or die( mysqli_error($conn) );
                                
                                $mk_start = mktime( 0, 0, 0, $start_dmy[1], $start_dmy[0]+1, $start_dmy[2] ); 
                                $start = date("d-m-Y", $mk_start);
                                $start_dmy = explode("-", $start);
                                
                                
                                
                                if( $start_dmy[0] . $start_dmy[1] == $arei_dmy[0] . $arei_dmy[1] )
                                {
                                    $jaar++;
                                }
                            }
                        }
                    }
                }
                
                
                
				echo "<fieldset>";
				echo "<legend>Klantgegevens (Referte : ". maakReferte( $cus->cus_id, $conn ) .")</legend>";
				echo "<table border='0'>";
				echo "<tr>";
				echo "<td class='klant_gegevens' >Naam:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma ) || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='naam' id='naam' class='lengte' value='".$cus->cus_naam."' />";
				}else {
					echo $cus->cus_naam;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>Bedrijf:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' name='bedrijf' id='bedrijf' class='lengte' value='".$cus->cus_bedrijf."' />";
				}else {
					echo $cus->cus_bedrijf;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>BTW:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' name='btw_edit' id='btw_edit' class='lengte' value='".$cus->cus_btw."' onblur='berekenPrijs();' />";
				}else {
					echo $cus->cus_btw;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='klant_gegevens'>Referentie:</td>";
				echo "<td>";
				
				switch( $cus->cus_ref)
				{
					case '0':
						$ref_chk = "";
						$ref = "Nee";
						break;
					case '1' :
						$ref_chk = " checked='checked' ";
						$ref = "Ja";
						break;
				}
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input onclick='checkref(this);' type='checkbox' ". $ref_chk ." name='ref' id='ref' />";
				}else {
					echo $ref;
				}
				
				echo "</td>";
				echo "</tr>";
				
				$stijl_ref = " style='display:none;' ";
				if( $cus->cus_ref == 1 )
				{
					$stijl_ref = "";
				}
				
				echo "<tr><td class='klant_gegevens'><span id='ref1' ".$stijl_ref.">Lengtegraad :</span></td><td><span id='ref3' ".$stijl_ref.">";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='lengte' id='lengte' value='".$cus->cus_ref_lengte."' />";	
				}else
				{
					echo $cus->cus_ref_lengte;
				}
				
				echo "</span></td></tr>";
				
				echo "<tr><td class='klant_gegevens'><span id='ref2' ".$stijl_ref.">Breedtegraad :</span></td><td><span id='ref4' ".$stijl_ref.">";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='breedte' id='breedte' value='".$cus->cus_ref_breedte."' />";	
				}else
				{
					$cus->cus_ref_breedte;
				}
				
				echo "</span></td></tr>";
				
				echo "<tr>";
				echo "<td class='klant_gegevens'>Mede Contractant:</td>";
				
				echo "<td>";
				
				$contractant = "";
				$contractant_chk = "";
				
				switch( $cus->cus_medecontractor )
				{
					case '0':
						$contractant_chk = "";
						$contractant = "Nee";
						break;
					case '1' :
						$contractant_chk = " checked='checked' ";
						$contractant = "Ja";
						break;
				}
				
				echo "<table width='230' cellpadding='0' cellspacing='0' border='0'>";
				echo "<tr>";
				echo "<td align='left'>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='checkbox' ". $contractant_chk ." name='contractant' id='contractant' />";
				}else {
					echo $contractant;
				}
				
				echo "</td>";
				echo "<td align='right'>";
				
                //http://be.bing.com/maps/?v=2&where1=10%20downing%20street,%20london&sty=b
                echo "<a title='Toon locatie in Bing Maps' href='http://be.bing.com/maps/?v=2&where1=". $cus->cus_straat ." ". $cus->cus_nr .", ". $cus->cus_postcode ." ". $cus->cus_gemeente ."&sty=b'  target='_blank'> <img border='0' src='images/bing.png' /> </a>";
				echo "<a title='Toon locatie in Google Maps' href='http://maps.google.be/maps?q=". $cus->cus_straat ."+". $cus->cus_nr ."+". $cus->cus_postcode ."+". $cus->cus_gemeente ."'  target='_blank'> <img border='0' src='images/google.png' /> </a>";
				
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='klant_gegevens'>Klant in onderaanneming:</td>";
				
				echo "<td>";
				
				$oa = "";
				$oa_chk = "";
				
				switch( $cus->cus_oa )
				{
					case '0':
						$oa_chk = "";
						$oa = "Nee";
						break;
					case '1' :
						$oa_chk = " checked='checked' ";
						$oa = "Ja";
						break;
				}
				
				echo "<table width='230' cellpadding='0' cellspacing='0' border='0'>";
				echo "<tr><td>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='checkbox' ". $oa_chk ." name='oa' id='oa' onclick='checkInOA(this);' />";
				}else {
					echo $oa;
				}
				
				echo "</td><td>";
				
				switch( $cus->cus_oa )
				{
					case '0' :
						$oa_stijl = " style='display:none;' ";
						break;
					case '1' :
						$oa_stijl = "";
						break;
				}
				
				echo "<span id='in_oa' ". $oa_stijl ."  class='klant_gegevens'>Bij : ";
				
				$q_klanten_oa = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id IN (980, 1409, 1076)");
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='in_oa_van' id='in_oa_van' >";
					while( $oa_klanten = mysqli_fetch_object($q_klanten_oa) )
					{
					   if( $cus->cus_oa == '1' && $cus->cus_oa_bij == $oa_klanten->cus_id )
                       {
                            echo "<option selected='selected' value='". $oa_klanten->cus_id ."'>". $oa_klanten->cus_naam ."</option>";
                       }else
                       {
                            echo "<option value='". $oa_klanten->cus_id ."'>". $oa_klanten->cus_naam ."</option>";
                       }
						
					}
					echo "</select>";
				}else
				{
					while( $oa_klanten = mysqli_fetch_object($q_klanten_oa) )
					{
					   if( $cus->cus_oa_bij == $oa_klanten->cus_id )
                       {
                            echo $oa_klanten->cus_naam;
                       }
					}
				}
				
				echo "</span>";
				
				echo "</td></tr>";
				echo "</table>";
				
				
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>Straat &amp; Nr.: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='straat' id='straat' value='".$cus->cus_straat."' /> ";
					echo "<input type='text' style='border:2px solid green;' name='nr' id='nr' value='".$cus->cus_nr."' size='4' />";
				}else {
					echo $cus->cus_straat . " " . $cus->cus_nr;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'> Postcode &amp; gemeente:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='postcode' id='postcode' value='".$cus->cus_postcode."' size='4' /> ";
					echo "<input type='text' style='border:2px solid green;' name='gemeente' id='gemeente' value='".$cus->cus_gemeente."' />";
				}else {
					echo $cus->cus_postcode . " " . $cus->cus_gemeente;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				
                if( !empty( $cus->cus_email ) )
                {
                    echo "<td class='klant_gegevens'>";
                    
                    echo "<table width='100%'>";
                    echo "<tr><td>";
                    echo "E-mail:";
                    echo "</td><td align='right'>";
                    echo "<a href='mailto:". $cus->cus_email ."' style='font-weight:normal;'><img alt='Klik hier om een e-mail te sturen naar deze klant' title='Klik hier om een e-mail te sturen naar deze klant' src='images/mail.jpg' border='1' width='30' height='15'></a>";
                    echo "</td></tr></table>";
                    echo "</td>";
                }else
                {
                    echo "<td class='klant_gegevens'>E-mail:</td>";    
                }
				
                echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='email' id='email' class='lengte' value='".$cus->cus_email."' />";
				}else {
					echo $cus->cus_email;
				}
					
				echo "</td>";
				echo "</tr>";
                
                // zoeken naar klanten die ook dit email adres gebruiken.
                if( !empty( $cus->cus_email ) )
                {
                    $q_mail = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' AND cus_email = '". $cus->cus_email ."' AND cus_id != " . $cus->cus_id);
                    
                    if( mysqli_num_rows($q_mail) > 0 )
                    {
                        echo "<tr>";
                        echo "<td colspan='2' style='color:red;'>";
                    
                        echo "DUBBELE KLANT?<br/>";
                    }
                    
                    if( mysqli_num_rows($q_mail) == 1 )
                    {
                        echo "Er is nog "  . mysqli_num_rows($q_mail) . " klant gevonden met dit e-mail adres<br/>";
                    }else
                    {
                        if( mysqli_num_rows($q_mail) > 1 )
                        {
                            echo "Er zijn nog "  . mysqli_num_rows($q_mail) . " klanten gevonden met dit e-mail adres<br/>";
                        }    
                    }
                    
                    //echo "<table width='100%'>";
                    
                    
                    while( $r = mysqli_fetch_object($q_mail) )
                    {
                        echo maakReferte( $r->cus_id, $conn );
                        echo "&nbsp;&nbsp;<a href='klanten.php?tab_id=1&klant_id=". $r->cus_id ."'>" . $r->cus_naam . "</a><br/>";
                    }
                    
                    //echo "</table>";
                    
                    if( mysqli_num_rows($q_mail) > 0 )
                    {
                        echo "</td>";
                        echo "</tr>";    
                    }
                }
                 
                echo "<tr>";
				echo "<td class='klant_gegevens'>GSM:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='gsm' id='gsm' class='lengte' value='".$cus->cus_gsm."' />";
				}else {
					echo $cus->cus_gsm;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>Tel.:</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' style='border:2px solid green;' name='tel' id='tel' class='lengte' value='".$cus->cus_tel."' />";
				}else {
					echo $cus->cus_tel;
				}
					
				echo "</td>";
				echo "</tr>";

				$fac_adres_checked = "";
				$fac_stijl = "style='display:none;'";

				if( $cus->cus_fac_adres == '1' )
				{
					$fac_adres_checked = "checked='checked'";
					$fac_stijl = "";
				}

				echo "<tr><td class='klant_gegevens'>Ander facturatie adres: ";

				echo "</td><td>";
				echo "<input type='checkbox' ". $fac_adres_checked ." name='fac_adres' id='fac_adres' onclick='showFacadres(this);' />";
				echo "</td></tr>";

				echo "<tr><td colspan='2'>";

				echo "<table id='id_facadres' border='0' ". $fac_stijl .">";
				echo "<tr>";
				echo "<td class='klant_gegevens'>Naam en/of bedrijf:</td>";
				echo "<td width='235'>";
				echo "<input type='text' class='lengte' name='fac_naam' id='fac_naam' value='". $cus->cus_fac_naam ."' />";
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>Straat + nr:</td>";
				echo "<td>";
				//echo "<input type='text' class='lengte' name='fac_adres1' id='fac_adres1' value='". $cus->cus_fac_adres1 ."' />";
				echo "<input type='text' name='fac_straat' id='fac_straat' value='".$cus->cus_fac_straat."' /> ";
				echo "<input type='text' name='fac_nr' id='fac_nr' value='".$cus->cus_fac_nr."' size='4' />";
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='klant_gegevens'>Postcode &amp; gemeente: </td>";
				echo "<td>";
				//echo "<input type='text' class='lengte' name='fac_adres2' id='fac_adres2' value='". $cus->cus_fac_adres2 ."' />";
				echo "<input type='text' name='fac_postcode' id='fac_postcode' value='".$cus->cus_fac_postcode."' size='4' /> ";
				echo "<input type='text' name='fac_gemeente' id='fac_gemeente' value='".$cus->cus_fac_gemeente."' />";
				echo "</td>";
				echo "</tr>";
				echo "<tr><td colspan='2'>&nbsp;</td></tr>";
				echo "</table>";

				echo "</td></tr>";
				
				echo "</table>";
				echo "</fieldset>";
				
				echo "</td></tr>";
				echo "</table>";
				
				if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
				{
					echo "<fieldset>";
					echo "<legend>Facturatie docs</legend>";
				
					echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
					echo "<tr>";
					echo "<td width='190' ><b>Factuur: </b></td>";
					echo "<td>";
					echo "<input class='lengte' type='file' name='doc_factuur' id='doc_factuur' />";
					echo "</td>";
					echo "</tr>";
					
                    $cus_id_p = $cus->cus_id;
                    
                    // zoeken ofdat het een project is en ofdat er een cus_id is gekoppeld
                    $q_zoek_p = mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $cus_id_p);
                    
                    if( mysqli_num_rows($q_zoek_p) > 0 )
                    {
                        // project gevonden
                        $p = mysqli_fetch_object($q_zoek_p);
                        
                        /*
                        echo "<pre>";
                        var_dump( $p );
                        echo "</pre>";
                        */
                        $cus_id_p = $p->project_id;
                        
                        /****************************/
                        // zoeken of er facturen zijn
    					$q_zoek_factuur = mysqli_query($conn, "SELECT * 
    					                                 FROM kal_customers_files
    					                                WHERE cf_cus_id = '". $cus_id_p ."'
    					                                  AND cf_soort = 'factuur' ");
    					
    					if( mysqli_num_rows($q_zoek_factuur) > 0 )
    					{
    						while( $factuur = mysqli_fetch_object($q_zoek_factuur) )
    						{
    							if( file_exists( "facturen/" . $factuur->cf_file ) )
    							{
    								echo "<tr><td align='right' valign='top'>";
    	
    								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    								{
    									echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
    								}
    								
    								echo "</td><td>";
    								echo "<input type='checkbox' name='factuur_del_". $factuur->cf_id ."' id='factuur_del_". $factuur->cf_id ."' />";
    								echo "<a href='facturen/" . $factuur->cf_file . "' target='_blank' >";
    								echo $factuur->cf_file;
    								echo "</a>";
    								echo " (". changeDate2EU( $factuur->cf_date )  .")";
    								
    								// per factuur gaan kijken ofdat er een aanmaning aanwezig is.
    								$q_zoek_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $cus_id_p ." AND aa_factuur = '" . $factuur->cf_file . "' ORDER BY 1");
    								
    								if( mysqli_num_rows($q_zoek_aanm) > 0 )
    								{
    									$teller = 0;
    									while( $aanmaning = mysqli_fetch_object($q_zoek_aanm) )
    									{
    										$teller++;
    										echo "<br/>- ";
    										
    										echo "<a href='aanmaningen/".  $aanmaning->aa_filename ."' target='_blank'>";
    										echo "Aanmaning " . $teller . " (". $aanmaning->aa_datum .")";
    										echo "</a>";
    										
    										//daarna hieronder ook het openstaande saldo vermelden
    										// bedrag van factuur ophalen
    										$saldo_openstaand = $factuur->cf_bedrag;
    										
    										$q_pay = mysqli_query($conn, "SELECT * 
    										                        FROM kal_customers_payments 
    										                       WHERE cp_cus_id = '". $cus_id_p ."' 
    										                         AND cp_factuur = '". $factuur->cf_file ."'") or die( mysqli_error($conn) );
    										
    										while( $pay = mysqli_fetch_object($q_pay) )
    										{
    											$saldo_openstaand -= $pay->cp_bedrag;
    										}
    										
    										echo "<br/>&nbsp;&nbsp;&nbsp;+ ";
    										echo "Openstaand saldo : ";
    										echo "&euro; " . number_format( $saldo_openstaand, 2, ",", " ");
    									}
    								}
    								echo "</td>";
    								echo "</tr>";
    							}
    						}
    						
    						echo "<tr>";
    						echo "<td>";
    						echo "<b>Betalingstermijn : </b>";
    						echo "</td>";
    						echo "<td>";
    						
    						if( $cus->cus_bet_termijn == 0  )
    						{
    							$cus->cus_bet_termijn = $betalings_termijn;
    						}
    						
    						echo "<input type='text' style='text-align:right;' size='4' name='bet_termijn' id='bet_termijn' value='". $cus->cus_bet_termijn ."' /> dagen";
    						echo "</td>";
    						echo "</tr>";
    					}
                        /****************************/
                        
                    }else
                    {
                        // zoeken of er facturen zijn
    					$q_zoek_factuur = mysqli_query($conn, "SELECT * 
    					                                 FROM kal_customers_files
    					                                WHERE cf_cus_id = '". $cus_id_p ."'
    					                                  AND cf_soort = 'factuur' ");
    					
    					if( mysqli_num_rows($q_zoek_factuur) > 0 )
    					{
    						while( $factuur = mysqli_fetch_object($q_zoek_factuur) )
    						{
    							if( file_exists( "cus_docs/" . $cus_id_p . "/factuur/" . $factuur->cf_file ) )
    							{
    								echo "<tr><td align='right' valign='top'>";
    	
    								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    								{
    									echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
    								}
    								
    								echo "</td><td>";
    								echo "<input type='checkbox' name='factuur_del_". $factuur->cf_id ."' id='factuur_del_". $factuur->cf_id ."' />";
    								echo "<a href='cus_docs/" . $cus_id_p . "/factuur/" . $factuur->cf_file . "' target='_blank' >";
    								echo $factuur->cf_file;
    								echo "</a>";
    								echo " (". changeDate2EU( $factuur->cf_date )  .")";
    								
    								// per factuur gaan kijken ofdat er een aanmaning aanwezig is.
    								$q_zoek_aanm = mysqli_query($conn, "SELECT * FROM tbl_aanmaningen WHERE aa_cus_id = " . $cus_id_p ." AND aa_factuur = '" . $factuur->cf_file . "' ORDER BY 1");
    								
    								if( mysqli_num_rows($q_zoek_aanm) > 0 )
    								{
    									$teller = 0;
    									while( $aanmaning = mysqli_fetch_object($q_zoek_aanm) )
    									{
    										$teller++;
    										echo "<br/>- ";
    										
    										echo "<a href='aanmaningen/".  $aanmaning->aa_filename ."' target='_blank'>";
    										echo "Aanmaning " . $teller . " (". $aanmaning->aa_datum .")";
    										echo "</a>";
    										
    										//daarna hieronder ook het openstaande saldo vermelden
    										// bedrag van factuur ophalen
    										$saldo_openstaand = $factuur->cf_bedrag;
    										
    										$q_pay = mysqli_query($conn, "SELECT * 
    										                        FROM kal_customers_payments 
    										                       WHERE cp_cus_id = '". $cus_id_p ."' 
    										                         AND cp_factuur = '". $factuur->cf_file ."'") or die( mysqli_error($conn) );
    										
    										while( $pay = mysqli_fetch_object($q_pay) )
    										{
    											$saldo_openstaand -= $pay->cp_bedrag;
    										}
    										
    										echo "<br/>&nbsp;&nbsp;&nbsp;+ ";
    										echo "Openstaand saldo : ";
    										echo "&euro; " . number_format( $saldo_openstaand, 2, ",", " ");
    									}
    								}
    								echo "</td>";
    								echo "</tr>";
    							}
    						}
    						
    						echo "<tr>";
    						echo "<td>";
    						echo "<b>Betalingstermijn : </b>";
    						echo "</td>";
    						echo "<td>";
    						
    						if( $cus->cus_bet_termijn == 0  )
    						{
    							$cus->cus_bet_termijn = $betalings_termijn;
    						}
    						
    						echo "<input type='text' style='text-align:right;' size='4' name='bet_termijn' id='bet_termijn' value='". $cus->cus_bet_termijn ."' /> dagen";
    						echo "</td>";
    						echo "</tr>";
    					}
                    }
                     
					
					
					// zoeken of er creditnota zijn
					$q_zoek_factuur = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus->cus_id ."'
					                                  AND cf_soort = 'creditnota' ");
					
					if( mysqli_num_rows($q_zoek_factuur) > 0 )
					{
						echo "<tr><td align='left' valign='top' colspan='2'><b>Credit nota:</b></td></tr>";
						
						while( $factuur = mysqli_fetch_object($q_zoek_factuur) )
						{
							if( file_exists( "cus_docs/" . $cus->cus_id . "/creditnota/" . $factuur->cf_file ) )
							{
								echo "<tr><td align='right'> ";
								
								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
								{
									echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
								}
								
								echo "</td><td align='left'>";
									echo "<input type='checkbox' name='cn_del_". $factuur->cf_id ."' id='cn_del_". $factuur->cf_id ."' />";
									echo "<a href='cus_docs/" . $cus->cus_id . "/creditnota/" . $factuur->cf_file . "' target='_blank' >";
									echo $factuur->cf_file;
									echo "</a>";
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					
					// zoeken of er distri offertes zijn
					$q_zoek_distri = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus->cus_id ."'
					                                  AND cf_soort = 'distri_offerte' ");
					
					if( mysqli_num_rows($q_zoek_distri) > 0 )
					{
						echo "<tr><td align='left' valign='top' colspan='2'><b>Offertes distri:</b></td></tr>";
						
						while( $distri_offerte = mysqli_fetch_object($q_zoek_distri) )
						{
							if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_distri/" . $distri_offerte->cf_file ) )
							{
								echo "<tr><td align='right'> ";
								
								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
								{
									echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
								}
								
								echo "</td><td align='left'>";
									echo "<input type='checkbox' name='distri_off_del_". $distri_offerte->cf_id ."' id='distri_off_". $distri_offerte->cf_id ."' />";
									echo "<a href='cus_docs/" . $cus->cus_id . "/doc_distri/" . $distri_offerte->cf_file . "' target='_blank' >";
									echo $distri_offerte->cf_file;
									echo "</a>";
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					
					// zoeken of er distri leverbonnen zijn
					$q_zoek_distri = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus->cus_id ."'
					                                  AND cf_soort = 'distri_bestelbon' ");
					
					if( mysqli_num_rows($q_zoek_distri) > 0 )
					{
						echo "<tr><td align='left' valign='top' colspan='2'><b>Leverbonnen distri:</b></td></tr>";
						
						while( $distri_offerte = mysqli_fetch_object($q_zoek_distri) )
						{
							if( file_exists( "cus_docs/" . $cus->cus_id . "/bon_distri/" . $distri_offerte->cf_file ) )
							{
								echo "<tr><td align='right'> ";
								
								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
								{
									echo "<b>Verwijderen?</b>&nbsp;&nbsp;";
								}
								
								echo "</td><td align='left'>";
									echo "<input type='checkbox' name='distri_bon_del_". $distri_offerte->cf_id ."' id='distri_bon_del_". $distri_offerte->cf_id ."' />";
									echo "<a href='cus_docs/" . $cus->cus_id . "/bon_distri/" . $distri_offerte->cf_file . "' target='_blank' >";
									echo $distri_offerte->cf_file;
									echo "</a>";
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					
					echo "</table>";
					echo "</fieldset>";
				}
				
				echo "<table>";

				$showhide1 = "";
				if( $cus->cus_oa == 1 )
				{
					$showhide1 = " style='display:none;' ";
				}
				
				echo "<tr><td colspan='2'>";
					echo "<fieldset id='showhide1' ". $showhide1 .">";
					echo "<legend>ACMA</legend>";
					echo "<table width='100%' cellpadding='0' cellspacing='0'>";
					
                    if( !empty($cus->cus_klant_wilt) )
                    {
                        echo "<tr>";
                        echo "<td>";
                        echo "Klant wil :";
                        echo "</td>";
                        echo "<td>" . $cus->cus_klant_wilt . "</td>";
                        echo "</tr>";
                    }
                    
					echo "<tr>";
					echo "<td>Kent ons van:</td>";
					echo "<td>";
						
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='kent' id='kent' class='lengte' >";
						echo "<option value=''>== Keuze ==</option>";
						
						foreach( $kent_ons_van as $key => $kent_ons )
						{
							if( $cus->cus_kent_ons_van == $key )
							{
								echo "<option selected='selected' value='". $key ."'>". $kent_ons ."</option>";
							}else
							{
								echo "<option value='". $key ."'>". $kent_ons ."</option>";	
							}
						}
						echo "</select>";
					}else {
						if( isset( $kent_ons_van[$cus->cus_kent_ons_van] ) )
						{
							echo $kent_ons_van[$cus->cus_kent_ons_van];
						}
					}
						
					echo "</td>";
					echo "</tr>";
                    
					if( ($_SESSION["kalender_user"]->group_id != 3) || $_SESSION["kalender_user"]->user_id == 29 || $_SESSION["kalender_user"]->active == '2' )
					{
						echo "<tr>";
						echo "<td width='190' >ACMA:</td>";
						echo "<td>";
                        
                        if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "<select style='border:2px solid green;' name='acma' id='acma' class='lengte'>";
							echo "<option value=''></option>";
								
							foreach( $acma_tel as $key => $acma )
							{
								if( $key == $cus->cus_acma )
								{
									echo "<option selected='yes' value='". $key ."'>". $acma["naam"] ." (". $acma["tel"] .")</option>";
								}else
								{
									echo "<option value='". $key ."'>". $acma["naam"] ." (". $acma["tel"] .")</option>";
								}
							}
								
							echo "</select>";
						}else {
							echo $acma_tel[ $cus->cus_acma ]["naam"] . " " . $acma_tel[ $cus->cus_acma ]["tel"];
						}
	
						echo "</td>";
						echo "</tr>";
					}else
					{
						echo "<tr>";
						echo "<td>ACMA:</td>";
						echo "<td>". $acma_tel[ $cus->cus_acma ]["naam"] . " " . $acma_tel[ $cus->cus_acma ]["tel"];
						echo "<input type='hidden' name='acma' id='acma' value='". $cus->cus_acma ."' /> ";
						echo "</td>";
						echo "</tr>";
					}
	
					if( $cus->cus_offerte_datum == "0000-00-00" )
					{
						$cus->cus_offerte_datum = "";
					}else
					{
						$datum = explode("-", $cus->cus_offerte_datum);
						$cus->cus_offerte_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
	
					echo "<tr>";
					echo "<td>Datum offerte aanvraag:</td>";
					echo "<td>";
						
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' name='offerte_datum' id='offerte_datum' class='lengte' value='".$cus->cus_offerte_datum."' />";
					}else {
						echo $cus->cus_offerte_datum;
					}
						
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					echo "</fieldset>";
				
					echo "</td></tr>";
	
					echo "<tr><td colspan='2'>";
					
					$showhide2 = " style='display:none;' ";
					
					if( ( !empty( $cus->cus_acma ) && !empty( $cus->cus_offerte_datum ) ) || $cus->cus_oa == '1' )
					{
						$showhide2 = "";
					}
				
					echo "<fieldset id='showhide2' ". $showhide2 .">";
					echo "<legend>Offerte</legend>";
					
					echo "<table border='0'>";
					
                    echo "<tr>";
					echo "<td class='offerte_gegevens' width='190' valign='top'>Extra documenten:</span></td>";
					echo "<td>";
                    
                    echo "<table cellpadding='2' cellspacing='2' width='100%' >";
                    echo "<tr><td><a target='_blank' href='pdf/Futech_brochure.pdf'>Brochure</a></td>";
                    echo "<td>";
                    echo "<a href='#' onclick='check_stuur_mail(". $cus->cus_id .",\"brochure\")' > <b ". $groene_stijl ." >[mail]</b></a>";
                    echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr><td><a target='_blank' href='pdf/Futech_Isolatienormen_overheid.pdf'>Isolatie vw.</a></td>";
                    echo "<td>";
                    echo "<a href='#' onclick='check_stuur_mail(". $cus->cus_id .",\"isolatie\")' > <b ". $groene_stijl ." >[mail]</b></a>";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    
                    echo "</td></tr>";
                    
					if( $cus->cus_contact == "0000-00-00" )
					{
						$cus->cus_contact = "";
					}else
					{
						$datum = explode("-", $cus->cus_contact);
						$cus->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens' width='190'><span id='id_off2'> Al gecontacteerd:</span></td>";
					echo "<td><span id='id_off3'>";

					if( $_SESSION["kalender_user"]->user_id == $cus->cus_acma && isset( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='text' class='lengte' name='gecontacteerd' id='gecontacteerd' value='". $cus->cus_contact ."' />";
					}else {
						echo $cus->cus_contact;
					}

					echo "</span></td>";
					echo "</tr>";

					if( $cus->cus_offerte_gemaakt == "0000-00-00" )
					{
						$cus->cus_offerte_gemaakt = "";
					}else
					{
						$datum = explode("-", $cus->cus_offerte_gemaakt);
						$cus->cus_offerte_gemaakt = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off4'>Offerte gemaakt:</span></td>";
					echo "<td><span id='id_off5'>";

					if( $_SESSION["kalender_user"]->user_id == $cus->cus_acma && isset( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='text' class='lengte' name='offerte_gemaakt' id='offerte_gemaakt' value='". $cus->cus_offerte_gemaakt ."' />";
					}else {
						echo $cus->cus_offerte_gemaakt;
					}

					echo "</span></td>";
					echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td colspan='2'>";
                    echo "<input type='button' name='auto_offerte' id='auto_offerte' value='Genereer offerte' onclick='maakOfferte(". $cus->cus_id .");' />";
                    echo "</td>";
                    echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off6'>Offerte:</span></td>";
					echo "<td><span id='id_off7'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1  || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='file' class='lengte' name='offerte' id='offerte' />";
					}

					echo "</span></td>";
					echo "</tr>";
					
					echo "<tr><td colspan='2'><span id='id_off8'>";
					
					// zoeken of er offertes zijn
					$q_zoek_offerte = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus->cus_id ."'
					                                  AND cf_soort = 'offerte' ");
					
					if( mysqli_num_rows($q_zoek_offerte) > 0 )
					{
						echo "<table width='100%'>";
						
						while( $offerte = mysqli_fetch_object($q_zoek_offerte) )
						{
							if( file_exists( "cus_docs/" . $cus->cus_id . "/offerte/" . $offerte->cf_file ) )
							{
								echo "<tr><td align='right' valign='top' class='offerte_gegevens' >";
	
								if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
								{
									echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='offerte_del_". $offerte->cf_id ."' id='offerte_del_". $offerte->cf_id ."' />";
								}
								
								echo "</td><td>";
								
								echo "<a href='cus_docs/" . $cus->cus_id . "/offerte/" . $offerte->cf_file . "' target='_blank' >";
								echo $offerte->cf_file;
								echo "</a>";
								
                                $groene_stijl = "";
                                
                                if( $offerte->cf_mail == '1' )
                                {
                                    $groene_stijl = " style='color:green;' title='Deze offerte werd via mail verstuurd.' ";
                                }
                                
                                echo " <a href='#' onclick='check_stuur_mail_offerte(". $cus->cus_id .",". $offerte->cf_id .")' > <b ". $groene_stijl ." >[mail]</b></a>";
                                
								echo "</td>";
								echo "</tr>";
							}
						}
						
						echo "</table>";
					}
					
					echo "</span></td></tr>";
					
					$offerte_datum1 = "";
					$offerte_datum2 = "";
					$offerte_datum3 = "";

					if( !empty( $cus->cus_offerte_besproken ))
					{
						$tmp_off = explode('@', $cus->cus_offerte_besproken );

						$offerte_datum1 = $tmp_off[0];
						$offerte_datum2 = $tmp_off[1];
						$offerte_datum3 = $tmp_off[2];


						if( $offerte_datum1 == "--" )
						{
							$offerte_datum1 = "";
						}

						if( $offerte_datum2 == "--" )
						{
							$offerte_datum2 = "";
						}

						if( $offerte_datum3 == "--" )
						{
							$offerte_datum3 = "";
						}
					}

					echo "<tr>";
					echo "<td valign='top' class='offerte_gegevens'><span id='id_off9'>Offerte bespreking:</span></td>";
					echo "<td valign='top'> <span id='id_off10'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='offerte_besproken1' id='offerte_besproken1' value='". $offerte_datum1 ."' /><br/>";
						echo "<input type='text' class='lengte' name='offerte_besproken2' id='offerte_besproken2' value='". $offerte_datum2 ."' /><br/>";
						echo "<input type='text' class='lengte' name='offerte_besproken3' id='offerte_besproken3' value='". $offerte_datum3 ."' />";
					}else {
						echo $offerte_datum1;
						echo "<br/>" . $offerte_datum2;
						echo "<br/>" . $offerte_datum3;
					}

					echo "</span></td>";
					echo "</tr>";
                    
                    if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
    					echo "<tr>";
    					echo "<td valign='top' class='offerte_gegevens'><span id='id_off112'> Iemand uitnodigen :<br/> <span style='font-size:9px;font-weight:normal;'>Deze personen krijgen een<br/>e-mail met de datum van<br/>de offertebespreking</span> </span></td>";
    					echo "<td>";
    					echo "<span id='id_off111'>";
    					
    					echo "<select name='sel_invite' id='sel_invite'>";
    					echo "<option value='0'> [Maak uw keuze] </option>";
    					foreach( $acma_arr as $key => $acma )
    					{
    						echo "<option value='". $key ."'>". $acma ."</option>";
    					}		
    					
    					echo "</select>";
    					
    					echo "<input type='button' name='but_invite' id='but_invite' value='+' onclick='inviteAjax();' />";
    					
    					echo "<br/>";
    					echo "<div id='id_invite'>";
    					echo "<select name='invitees[]' id='invitees[]' size='3' onclick='delOption(this);' multiple='multiple' />";
    
    					echo "</select>";
    					echo "</div>";
    					
    					echo "</span>";
    					echo "</td>";
    					echo "</tr>";
                    }
					
					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off11'>Aantal panelen:</span></td>";
					echo "<td><span id='id_off12'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' style='border:2px solid green;' class='lengte' name='aant_panelen' id='aant_panelen' value='". $cus->cus_aant_panelen ."' onblur='checkConform();getPpwp();maakPrijs();' />";
					}else {
						echo $cus->cus_aant_panelen;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'>Type panelen:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						$typep = array();

						//$typep[] = "";
						$typep[] = "Gewone";
						$typep[] = "Zwarte";
							
						echo "<select style='border:2px solid green;' name='type_panelen' id='type_panelen' onchange='checkConform();getPpwp();maakPrijs();'>";
						foreach( $typep as $type )
						{
							if( $type == $cus->cus_type_panelen )
							{
								echo "<option selected='yes' value='".$type."'>". ucfirst($type) ."</option>";
							}else
							{
								echo "<option value='".$type."'>". ucfirst($type) ."</option>";
							}
						}
						echo "</select>";
					}else {
						echo $cus->cus_type_panelen;
					}

					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off13'>Vermogen/paneel:</span></td>";
					echo "<td><span id='id_off14'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' style='border:2px solid green;' class='lengte' name='w_panelen' id='w_panelen' value='". $cus->cus_w_panelen ."' onblur='checkConform();maakPrijs();' />";
					}else {
						echo $cus->cus_w_panelen;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off21'>Merk panelen:</span></td>";
					echo "<td><span id='id_off22'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='merk_panelen' id='merk_panelen' value='". $cus->cus_merk_panelen ."' />";
					}else {
						echo $cus->cus_merk_panelen;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'>Opbrengst factor:";
                    
                    echo "<input type='button' onclick='check_opbrengstfactor(\"". $cus->cus_id ."\");' value='Calc' />";
                    
                    //echo "<a style='color:seagreen;' href='#'  >Calc</a>";
                    echo "</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
					    $stijl = "";
                        
                        if( ($cus->cus_kwhkwp == 0 || empty($cus->cus_kwhkwp)) && !empty( $cus->cus_verkoop ) )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border-top:2px solid red;border-left:2px solid red;border-bottom:2px solid green;border-right:2px solid green;' ";    
                        }
                        
                        if( $cus->cus_verkoop == 0 )
                        {
                            $stijl = "";
                        }
                        
                        if( $stijl == "" )
                        {
                            $stijl = " style='border:2px solid green;' ";
                        }
                       
                       
						echo "<input ". $stijl ." type='text' class='lengte' name='kwhkwp' id='kwhkwp' value='". $cus->cus_kwhkwp ."' />";
					}else {
						echo $cus->cus_kwhkwp;
					}

					echo "</td>";
					echo "</tr>";
                    
                    /*
                    if( $client != NULL )
                    {
                        if( $client->profit_factor != $cus->cus_kwhkwp )
                        {
                            echo "<tr>";
                            echo "<td colspan='2' class='error' align='right'>";
                            echo "Op de website werd " . $client->profit_factor . " ingegeven.";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    */

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off17'>Hoek panelen met het zuiden:</span></td>";
					echo "<td><span id='id_off18'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' style='border:2px solid green;' class='lengte' name='hoek_z' id='hoek_z' value='". $cus->cus_hoek_z ."' />";
					}else {
						echo $cus->cus_hoek_z;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Hoek van de panelen:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' style='border:2px solid green;' class='lengte' name='hoek' id='hoek' value='". $cus->cus_hoek ."' />";
					}else {
						echo $cus->cus_hoek;
					}

					echo "</span></td>";
					echo "</tr>";

                    echo "<tr>";
                    echo "<td class='offerte_gegevens'>Schaduw :</td>";
                    echo "<td>";
                    
                    $check_schaduw = "";
                    $stijl_schaduw = " style='display:none;' ";
                    if( $cus->cus_schaduw == '1' )
                    {
                        $check_schaduw = " checked='checked' ";
                        $stijl_schaduw = "";
                    }
                    
                    echo "<input ". $check_schaduw ." type='checkbox' name='schaduw' id='schaduw' onclick='checkSchaduw(this);' />";                    
                    echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td colspan='2'>";
                    
                    
                    $chk_zomer = "";
                    $chk_winter = "";
                    $chk_lente_herfst = "";
                    
                    if( $cus->cus_schaduw_z == '1' )
                    {
                        $chk_zomer = " checked='checked' ";
                    }
                    
                    if( $cus->cus_schaduw_w == '1' )
                    {
                        $chk_winter = " checked='checked' ";
                    }
                    
                    if( $cus->cus_schaduw_lh == '1' )
                    {
                        $chk_lente_herfst = " checked='checked' ";
                    }
                    
                    echo "<table border='0' id='table_schaduw' ". $stijl_schaduw ." name='table_schaduw' width='100%' cellpadding='0' cellspacing='0' >";
                    echo "<tr>";
                    echo "<td width='194' class='offerte_gegevens' > - Zomer </td>";
                    echo "<td> <input ". $chk_zomer ." type='checkbox' name='zomer' id='zomer' /></td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td class='offerte_gegevens' > - Winter </td>";
                    echo "<td> <input ". $chk_winter ." type='checkbox' name='winter' id='winter' /></td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td class='offerte_gegevens' > - Lente/Herfst </td>";
                    echo "<td> <input ". $chk_lente_herfst ." type='checkbox' name='lente_herfst' id='lente_herfst' /></td>";
                    echo "</tr>";
                    echo "</table>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'>Soort dak:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						?> <select style='border:2px solid green;' name='soort_dak' id='soort_dak' onchange='getPpwp( this.value );' class='lengte'>
							<?php
						
							echo "<option value='0' >== Keuze ==</option>";
						
							foreach( $daksoorten as $key => $soort )
							{
								if( $cus->cus_soort_dak == $key )
								{
									echo "<option selected='yes' value='". $key ."'>". $soort ."</option>";
								}else
								{
									echo "<option value='". $key ."'>". $soort ."</option>";
								}
							}
						
							?>
						</select> <?php 

					}else {
						echo $daksoorten[ $cus->cus_soort_dak ];
					}

					echo "</td>";
					echo "</tr>";
                    
                    $offerte = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_offerte_instellingen LIMIT 1"));
                    
                    if( $cus->cus_dag == 0.00 )
                    {
                        $cus->cus_dag = "";
                    }
                    
                    if( $cus->cus_nacht == 0.00 )
                    {
                        $cus->cus_nacht = "";
                    }
                    
                    if( $cus->cus_dag_tarief == 0.00 )
                    {
                        $cus->cus_dag_tarief = "";
                        $cus->cus_dag_tarief = $offerte->elec;
                        
                    }
                    
                    if( $cus->cus_nacht_tarief == 0.00 )
                    {
                        $cus->cus_nacht_tarief = "";
                        $cus->cus_nacht_tarief = $offerte->elec_nacht;
                        
                    }
                    
                    if( $cus->cus_vergoeding == 0.00 )
                    {
                        $cus->cus_vergoeding = "";
                    }
                    
                    echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Verbuik dag:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' name='verbruik_dag' id='verbruik_dag' value='". $cus->cus_dag ."' />";
					}else {
						echo $cus->cus_dag;
					}
                    
					echo "</span></td>";
					echo "</tr>";
                    
                    echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Verbuik nacht:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' name='verbruik_nacht' id='verbruik_nacht' value='". $cus->cus_nacht ."' />";
					}else {
						echo $cus->cus_nacht;
					}

					echo "</span></td>";
					echo "</tr>";
                    
                    echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Tarief dag:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' name='dag_tarief' id='dag_tarief' value='". $cus->cus_dag_tarief ."' />";
					}else {
						echo $cus->cus_dag_tarief;
					}

					echo "</span></td>";
					echo "</tr>";
                    
                    echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Tarief nacht:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' name='nacht_tarief' id='nacht_tarief' value='". $cus->cus_nacht_tarief ."' />";
					}else {
						echo $cus->cus_nacht_tarief;
					}

					echo "</span></td>";
					echo "</tr>";
                    
                    echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off19'>Vaste vergoeding:</span></td>";
					echo "<td><span id='id_off20'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' class='lengte' name='vergoeding' id='vergoeding' value='". $cus->cus_vergoeding ."' />";
					}else {
						echo $cus->cus_vergoeding;
					}

					echo "</span></td>";
					echo "</tr>";
                    
					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off23' style='display:none;' >Prijs per Wp:</span></td>";
					echo "<td><span id='id_off24'>";

					$db_dak[1] = "wp_plat";
					$db_dak[2] = "wp_plat";
					$db_dak[6] = "wp_plat";
					$db_dak[3] = "wp_leien";
					$db_dak[4] = "wp_schans";
					$db_dak[5] = "wp_schans";
                    $db_dak[7] = "wp_plat";
                    $db_dak[8] = "wp_leien";
                    $db_dak[9] = "wp_schans";
                    $db_dak[10] = "wp_plat";
                    
					
					if( ( $cus->cus_prijs_wp == "0" || empty($cus->cus_prijs_wp) ) && $cus->cus_soort_dak != 0 && !empty($cus->cus_soort_dak) && $cus->cus_aant_panelen != 0 && !empty($cus->cus_aant_panelen) )
					{
						// zoeken naar de ppwp
						$waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT " . $db_dak[ $cus->cus_soort_dak ] . " FROM kal_wp WHERE wp_start <= ".$cus->cus_aant_panelen." AND wp_end >=" . $cus->cus_aant_panelen));
							
						if( $cus->cus_prijs_wp == 0 || empty( $cus->cus_prijs_wp ) )
						{
							$cus->cus_prijs_wp = $waarde->$db_dak[ $cus->cus_soort_dak ];
						}
					}

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' style='display:none;' class='lengte' onblur='maakPrijs();' name='ppwp' id='ppwp' value='". $cus->cus_prijs_wp ."' />";
					}else {
						echo $cus->cus_prijs_wp;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off25'>Bedrag excl.:</span></td>";
					echo "<td><span id='id_off26'>";

					if( $cus->cus_bedrag_excl == 0 || $cus->cus_bedrag_excl == "" )
					{
						$cus->cus_bedrag_excl = (int)$cus->cus_aant_panelen * (int)$cus->cus_w_panelen * (float)$cus->cus_prijs_wp;
					}

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='bedrag_excl' id='bedrag_excl' value='". $cus->cus_bedrag_excl ."' />";
					}else {
						echo $cus->cus_bedrag_excl;
					}

					echo "</span></td>";
					echo "</tr>";

					$sel0 = "";
					$sel1 = "";
					$sel2 = "";

					switch( $cus->cus_woning5j )
					{
						case '0':
							$sel0 = "selected='yes'";
							break;

						case '1' :
							$sel1 = "selected='yes'";
							break;

						case '2' :
							$sel2 = "selected='yes'";
							break;
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off27'>Woning ouder dan 5j:</span></td>";
					echo "<td><span id='id_off28'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
					    $stijl = "";
                        
                        if( $cus->cus_woning5j == '2' && !empty( $cus->cus_verkoop ) && $cus->cus_oa == 0 )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border-top:2px solid red;border-left:2px solid red;border-bottom:2px solid green;border-right:2px solid green;' "; 
                        }
                        
                        
                        if( $stijl == "" )
                        {
                            $stijl = " style='border:2px solid green;' ";
                        }
                       
						echo "<select ". $stijl ." name='woning5j' id='woning5j' onchange='berekenPrijs(this);' class='lengte'>";
						echo "<option value='2' ".$sel2." >== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						switch( $cus->cus_woning5j )
						{
							case '0' :
								echo "Neen";
								break;
									
							case '1' :
								echo "Ja";
								break;
						}
					}

					echo "</span></td>";
					echo "</tr>";

					$sel0 = "";
					$sel1 = "";
					$sel2 = "";

					switch( $cus->cus_opwoning )
					{
						case '0' :
							$sel0 = "selected='yes'";
							break;
						case '1' :
							$sel1 = "selected='yes'";
							break;
						case '2' :
							$sel2 = "selected='yes'";
							break;
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off29'>Panelen op woning:</span></td>";
					echo "<td><span id='id_off30'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select style='border:2px solid green;' name='opwoning' id='opwoning'  class='lengte'>";
						echo "<option value='2' ".$sel2." >== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						switch( $cus->cus_woning5j )
						{
							case '0' :
								echo "Neen";
								break;
							case '1' :
								echo "Ja";
								break;
						}
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off31'>Bedrag incl.:</span></td>";
					echo "<td><span id='id_off32'><span id='id_bedrag_incl'>";

					switch( $cus->cus_woning5j )
					{
						case '0' :
							echo "&euro; " . number_format( $cus->cus_bedrag_excl*1.21, "2", ".", "");
							break;
						case '1' :
							if( !empty( $cus->cus_btw ) )
							{
								echo "&euro; " . number_format( $cus->cus_bedrag_excl*1.21, "2", ".", "");
							}else
							{
								echo "&euro; " . number_format( $cus->cus_bedrag_excl*1.06, "2", ".", "");
							}
							break;
					}

					echo "</span></span></td>";
					echo "</tr>";

					$sel0 = "";
					$sel1 = "";

					if( $cus->cus_driefasig == '0' )
					{
						$sel0 = "selected='yes'";
					}else
					{
						$sel1 = "selected='yes'";
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off33'>Driefasig aanwezig:</span></td>";
					echo "<td><span id='id_off34'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='driefasig' id='driefasig'>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						if( $cus->cus_driefasig == '0' )
						{
							echo "Neen";
						}else
						{
							echo "Ja";
						}
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off35'>Driefasig noodzakelijk:</span></td>";
					echo "<td><span id='id_off36'>";
					echo "<span id='driefase_noodzakelijk'>";

				   if( $cus->cus_aant_panelen < 25  )
                   {
                        echo "&nbsp;Neen";
                   }else
                   {
                        echo "&nbsp;<span class='error'>Ja</span>";
                   }

					echo "</span>";
					echo "</span>";
					echo "</td>";
					echo "</tr>";

					$sel0 = "";
					$sel1 = "";

					if( $cus->cus_nzn == '0' )
					{
						$sel0 = "selected='yes'";
					}else
					{
						$sel1 = "selected='yes'";
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'><span id='id_off37'>NZN:</span></td>";
					echo "<td><span id='id_off38'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='nzn' id='nzn'>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						if( $cus->cus_nzn == '0' )
						{
							echo "Neen";
						}else
						{
							echo "Ja";
						}
						echo "<input type='hidden' name='nzn' id='nzn' value='". $cus->cus_nzn ."' />";
					}
					echo "</span></td>";
					echo "</tr>";

					$sel0 = "";
					$sel1 = "";
					$sel2 = "";
					
					switch( $cus->cus_verkoop )
					{
						case "0" :
							$sel0 = "selected='yes'";
							break;
						case "1" :
							$sel1 = "selected='yes'";
							break;
						case "2" :
							$sel2 = "selected='yes'";
							break;
					}

					echo "<tr>";
					echo "<td class='offerte_gegevens'>Overeenkomst:</td>";
					echo "<td>";

					?>
					<script type="text/javascript">
					function checkVerkoop( dit, verkoop, group_id )
					{
						if( (verkoop == 1 || verkoop == 2  ) && dit.value != 1 )
						{
							if( group_id != 1 )
							{
								alert( "Gelieve contact op te nemen met Ismael om de verkoop/verhuur terug op nee te zetten");
								var selObj = document.getElementById('verkoop');
								selObj.options[2].selected = true;
							}
						}
					}
					
					</script>
					<?php 
					
					if( empty( $cus->cus_verkoop ) )
					{
						$test_verkoop = "-";
					}else
					{
						$test_verkoop = $cus->cus_verkoop;
					}
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='verkoop' id='verkoop' onchange='checkVerkoop(this, \"". $test_verkoop ."\", ". $_SESSION["kalender_user"]->group_id ." );viewTable2(this);'>";
						echo "<option value=''>== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Verkoop</option>";
						echo "<option value='2' ".$sel2." >Verhuur</option>";
						echo "</select>";
					}else 
					{
						switch( $cus->cus_verkoop )
						{
							case '0' :
							case '' :
								echo "Neen";
                                break;
							case '1' :
								echo "Verkoop";
								break;
							case '2' :
								echo "Verhuur";
								break;
						}
					}

					echo "</td>";
					echo "</tr>";

					if( $cus->cus_verkoop != "0" )
					{
						$stijl = "style='display:none;'";
					}else
                    {
                        $stijl = "";
                    }

					echo "<tr>";
					echo "<td colspan='2'><span id='id_off41'>";
					echo "<table border='0' width='100%' id='tabel3' ". $stijl ."  cellpadding='0' cellspacing='0'>";
					echo "<tr>";
					echo "<td width='50%' class='offerte_gegevens'>Reden:</td>";
					echo "<td width='50%'>";
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='reden' id='reden' value='". $cus->cus_reden ."' />";
					}else {
						echo $cus->cus_reden;
					}
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					
					echo "</span></td>";
					echo "</tr>";
					
					echo "<tr><td colspan='2'>";
					echo "<span id='id_off42'>";
					
					$showhide4 = "";
					
					if( $cus->cus_verkoop == "1" || $cus->cus_verkoop == "2" )
					{
						$showhide4 = "";
					}else
					{
						$showhide4 = " style='display:none;' ";
					}

					echo "<table width='100%' ". $showhide4 ." id='showhide4' cellpadding='0' cellspacing='0' >";
					echo "<tr>";
					echo "<td class='offerte_gegevens' width='50%' >Datum overeenkomst:</td>";
					echo "<td>";

					if( $cus->cus_verkoop_datum == "0000-00-00" )
					{
						$cus->cus_verkoop_datum = "";
					}else
					{
						$datum = explode("-", $cus->cus_verkoop_datum);
						$cus->cus_verkoop_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='verkoop_datum' id='verkoop_datum' value='". $cus->cus_verkoop_datum ."' />";
					}else {
						echo $cus->cus_verkoop_datum;
					}

					echo "</td>";
					echo "</tr>";
					echo "</table>";
					
					echo "</span>";
					echo "</td></tr>";
					echo "</table>";
					echo "</fieldset>";	
					
					
					echo "</td></tr>";
					echo "</table>";
					
					echo "<table>";
					echo "<tr>";
					echo "<td colspan='2'>";
					
					$showhide3 = "";
					
					if( $cus->cus_verkoop == '1' || $cus->cus_verkoop == '2')
					{
						$showhide3 = "";
					}else
					{
						$showhide3 = " style='display:none;' ";
					}
					
					if( $cus->cus_oa == '1' )
					{
						$showhide3 = "";
					}
					
					echo "<fieldset id='showhide3' ". $showhide3 ." >";
					echo "<legend>Installatie</legend>";
					
					echo "<table width='100%'>";

					echo "<tr>";
					echo "<td><span id='id_off43'>Opmetingsdatum:</span></td>";
					echo "<td><span id='id_off44'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='opmeting_datum' id='opmeting_datum' value='". $cus->cus_opmeting_datum ."' />";
					}else {
						echo $cus->cus_opmeting_datum;
					}

					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td><span id='id_off45'>Opmeting door:</span></td>";
					echo "<td><span id='id_off46'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						//echo "<input type='text' class='lengte' name='opmeting_datum' id='opmeting_datum' value='". $cus->cus_opmeting_datum ."' />";
						echo "<input type='text' class='lengte' name='opmeting_door' id='opmeting_door' value='". $cus->cus_opmeting_door ."' />";
					}else {
						echo $cus->cus_opmeting_door;
					}

					echo "</span></td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td><span id='id_off47'>Opmetingsdocument:</span> </td>";
					echo "<td>";
						
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<span id='id_off48'>";
						echo "<input type='file' class='lengte' name='doc_opmeting' id='doc_opmeting' />";
						echo "</span>";
					}
					
					echo "</td>";
					echo "</tr>";
	
					if( !empty( $cus->cus_opmetingdoc_filename ) )
					{
						echo "<tr><td align='right'><span id='id_off49'>";
	
						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='opmetingdoc_del' id='opmetingdoc_del' />";
						}
	
						echo "</span></td><td><span id='id_off50'>";
	
						if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_opmeting/" . $cus->cus_opmetingdoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus->cus_id . "/doc_opmeting/" . $cus->cus_opmetingdoc_filename . "' target='_blank' >";
							echo $cus->cus_opmetingdoc_filename;
							echo "</a>";
						}
	
						echo "</span></td></tr>";
					}
					
					// installatie datum
					if( $cus->cus_installatie_datum == "0000-00-00" )
					{
						$cus->cus_installatie_datum = "";
					}else
					{
						$datum = explode("-", $cus->cus_installatie_datum);
						$cus->cus_installatie_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td>Installatiedatum 1:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='installatie_datum' id='installatie_datum' value='". $cus->cus_installatie_datum ."' />";
					}else {
						echo $cus->cus_installatie_datum;
					}

					echo "</td>";
					echo "</tr>";
					
					// installatiedatum 2
					if( $cus->cus_installatie_datum2 == "0000-00-00" )
					{
						$cus->cus_installatie_datum2 = "";
					}else
					{
						$datum = explode("-", $cus->cus_installatie_datum2);
						$cus->cus_installatie_datum2 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td>Installatiedatum 2:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='installatie_datum2' id='installatie_datum2' value='". $cus->cus_installatie_datum2 ."' />";
					}else {
						echo $cus->cus_installatie_datum2;
					}

					echo "</td>";
					echo "</tr>";
					
					// installatiedatum 3
					if( $cus->cus_installatie_datum3 == "0000-00-00" )
					{
						$cus->cus_installatie_datum3 = "";
					}else
					{
						$datum = explode("-", $cus->cus_installatie_datum3);
						$cus->cus_installatie_datum3 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td>Installatiedatum 3:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='installatie_datum3' id='installatie_datum3' value='". $cus->cus_installatie_datum3 ."' />";
					}else {
						echo $cus->cus_installatie_datum3;
					}

					echo "</td>";
					echo "</tr>";
					
					// installatiedatum 4
					if( $cus->cus_installatie_datum4 == "0000-00-00" )
					{
						$cus->cus_installatie_datum4 = "";
					}else
					{
						$datum = explode("-", $cus->cus_installatie_datum4);
						$cus->cus_installatie_datum4 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}

					echo "<tr>";
					echo "<td>Installatiedatum 4:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='installatie_datum4' id='installatie_datum4' value='". $cus->cus_installatie_datum4 ."' />";
					}else {
						echo $cus->cus_installatie_datum4;
					}

					echo "</td>";
					echo "</tr>";
					
					if( $cus->cus_nw_installatie_datum == "0000-00-00" )
					{
						$cus->cus_nw_installatie_datum = "";
					}else
					{
						$datum = explode("-", $cus->cus_nw_installatie_datum);
						$cus->cus_nw_installatie_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
					
					echo "<tr>";
					echo "<td>Nieuwe Installatiedatum:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='nw_installatie_datum' id='nw_installatie_datum' value='". $cus->cus_nw_installatie_datum ."' />";
					}else {
						echo $cus->cus_nw_installatie_datum;
					}

					echo "</td>";
					echo "</tr>";
					
					if( $cus->cus_aanp_datum == "0000-00-00" )
					{
						$cus->cus_aanp_datum = "";
					}else
					{
						$datum = explode("-", $cus->cus_aanp_datum);
						$cus->cus_aanp_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
					
					echo "<tr>";
					echo "<td>Installatie aanpassen:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='installatie_aanp' id='installatie_aanp' value='". $cus->cus_aanp_datum ."' />";
					}else {
						echo $cus->cus_aanp_datum;
					}

					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td>Installatieploeg:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input title='Voor en achternaam ingeven aub' type='text' class='lengte' name='installatie_ploeg' id='installatie_ploeg' value='". $cus->cus_installatie_ploeg ."' />";
					}else {
						echo $cus->cus_installatie_ploeg;
					}

					echo "</td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td>Stringopmetingsrapport: </td>";
					echo "<td>";
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='file' class='lengte' name='doc_string' id='doc_string' />";
					}	
					
					echo "</td>";
					echo "</tr>";

					if( !empty( $cus->cus_stringdoc_filename) )
					{
						echo "<tr><td align='right'>";
						
						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
						
							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
							{
								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='stringdoc_del' id='stringdoc_del' />";
							}
						}
						
						echo "</td><td>";

						if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_string/" . $cus->cus_stringdoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus->cus_id . "/doc_string/" . $cus->cus_stringdoc_filename . "' target='_blank' >";
							echo $cus->cus_stringdoc_filename ;
							echo "</a>";
						}
						echo "</td></tr>";
					}
					
					echo "<tr>";
					echo "<td>Elektrische bekabeling door:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						$sel0 = "";
						$sel1 = "";
						$sel2 = "";
							
						switch( $cus->cus_elec )
						{
							case '2' :
								$sel2 = "selected='yes'";
								break;
							case '0' :
								$sel0 = "selected='yes'";
								break;
							case '1' :
								$sel1 = "selected='yes'";
								break;
						}
							
						echo "<select name='elec' id='elec' class='lengte' onchange='toonElec(this);'>";
						echo "<option value='2' ". $sel2 .">== Keuze == </option>";
						echo "<option value='0' ". $sel0 .">Dezelfde ploeg </option>";
						echo "<option value='1' ". $sel1 .">Andere: </option>";
						echo "</select>";
					}else {
						switch( $cus->cus_elec )
						{
							case '2' :
								echo "";
								break;
							case '0' :
								echo "Dezelfde ploeg";
								break;
							case '1' :
								echo "Andere :";
								break;
						}
					}

					echo "</td>";
					echo "</tr>";

					$elec_stijl = "style='display:none'";

					if( $cus->cus_elec == 1 )
					{
						$elec_stijl = "";
					}

					echo "<tr>";
					echo "<td><span id='elec1' ". $elec_stijl .">Door: </span></td>";
					echo "<td><span id='elec2' ". $elec_stijl .">";
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='elec_door' id='elec_door' value='". $cus->cus_elec_door ."' />";
					}else
					{
						echo $cus->cus_elec_door;	
					}
					
					echo "</span></td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td><span id='elec3' ". $elec_stijl .">Datum:</span></td>";
					echo "<td><span id='elec4' ". $elec_stijl ." >";
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='elec_datum' id='elec_datum' value='". $cus->cus_elec_datum ."' />";
					}else
					{
						echo $cus->cus_elec_datum;	
					}
					
					echo "</span></td>";
					echo "</tr>";
					
					// zoeken of er fotos zijn
					$q_zoek_offerte = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus->cus_id ."'
					                                  AND cf_soort = 'foto' ");
					
					if( mysqli_num_rows($q_zoek_offerte) > 0 )
					{
						$i=0;
						
						while( $offerte = mysqli_fetch_object($q_zoek_offerte) )
						{
							if( file_exists( "cus_docs/" . $cus->cus_id . "/foto/" . $offerte->cf_file ) )
							{
								echo "<tr><td align='left' valign='top'>";
								
								if( $i == 0 )
								{
									echo "Foto's";
								}
								
								$i++;
								
								echo "</td><td>";
								
								echo "<a href='cus_docs/" . $cus->cus_id . "/foto/" . $offerte->cf_file . "' target='_blank' title='Klik op de foto voor een grotere weergave.' >";
								echo $offerte->cf_file;
								echo "</a>";
								
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					
					echo "</table>";
					echo "</fieldset>";
					
				echo "</td></tr>";
				echo "</table>";

				echo "</td>";
				echo "<td valign='top' width='50%'>";
				// begin tabel 2

				echo "<table width='100%' >";

				echo "<tr>";
				echo "<td colspan='2' >";
				
				echo "<table width='100%'>";
				echo "<tr>";
				echo "<td>";
				echo "<a id='various5' class='verkoop_gegevens' href='klanten_tel.php?klantid=".$cus->cus_id."'>";
                
                // tellen en weergeven van het aantal interventies
				$aant_tel = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_tel WHERE ct_cus_id = " . $cus->cus_id));
				echo "Telefonische opmerkingen";
				
				if( $aant_tel > 0 )
				{
					echo " (". $aant_tel .")";
				}
                echo "</a>";
				
				echo "</td>";
				echo "<td>";
				echo "<a id='various6' class='verkoop_gegevens' href='klanten_interventies.php?klantid=".$cus->cus_id."'>";
				
				// tellen en weergeven van het aantal interventies
				$aant_interventies = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_cus_interventies WHERE ct_cus_id = " . $cus->cus_id));
				echo "Interventies";
				
				if( $aant_interventies > 0 )
				{
					echo " (". $aant_interventies .")";
				}
				
				echo "</a>";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td colspan='2' class='verkoop_gegevens'>";
				echo "Opmerkingen:<br/>";

				if( ( $_SESSION["kalender_user"]->user_id == $cus->cus_acma && isset( $cus->cus_acma ) ) || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<textarea rows='5' style='width:452px;' name='opmerkingen' id='opmerkingen' >". $cus->cus_opmerkingen ."</textarea>";
				}else {
					echo $cus->cus_opmerkingen;
				}

				echo "</td>";
				echo "</tr>";
				echo "</table>";

				$stijl = " style='display:none;' ";
				
				if( !empty( $cus->cus_btw ) )
				{
					$stijl = "";
				}
				
				// nieuwe blok voor het ingeven van de kortingen
				if( $_SESSION["kalender_user"]->group_id == 1 )
				{
					echo "<fieldset id='tabel2a' ". $stijl ." >";
					echo "<legend>Kortingen per materiaalsoort</legend>";
					echo "<table width='100%' border='0' >";
					
					// materiaal soorten ophalen
					$q_ms = mysqli_query($conn, "SELECT * FROM kal_art_soort ORDER BY as_soort");
					while($ms = mysqli_fetch_object($q_ms) )
					{
						echo "<tr>";
						echo "<td>";
						echo $ms->as_soort;
						echo ": </td>";
						
						echo "<td>";
						// ophalen van de reeds bestaande waardes
						$sql_korting = "SELECT * FROM kal_as_cus_korting WHERE as_id = " . $ms->as_id . " AND cus_id = " . $cus->cus_id;
						$q_korting = mysqli_query($conn,  $sql_korting);
						$korting = mysqli_fetch_object($q_korting);
						
						echo "<input type='text' name='ascus_". $ms->as_id ."' value='". $korting->korting ."' />";
						
						if( $ms->as_soort != "Zonnepanelen" )
						{
							echo "%";
						}
						
						echo "</td>";
						echo "</tr>";
					}
					
					echo "</table>";
					echo "</fieldset>";
				}
				// einde blok ingeven van de kortingen
				
				$stijl = "style='display:none;'";

				if( $cus->cus_verkoop == '1' || $cus->cus_verkoop == '2' )
				{
					$stijl = "style='display:block;'";
				}
				
				if( $cus->cus_oa == '1' )
				{
					$stijl = "";
				}

				echo "<fieldset id='tabel2' ". $stijl ." >";
				echo "<legend>Facturatie</legend>";
				
				echo "<table width='100%' border='0' >";
				
				if( $cus->cus_verkoop == '1' )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='verkoop1'>Verkoopsbedrag incl.:</span></td>";
					echo "<td>";
					echo "<span id='verkoop2'>";	
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						if( $cus->cus_verkoopsbedrag_incl == 0 || $cus->cus_verkoopsbedrag_incl == "" || $_SESSION["kalender_user"]->group_id == 1 )
						{
							echo "<input type='text' class='lengte' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";
						}else
						{
							echo "<span title='Eenmaal dat de prijs is ingevuld, kan deze enkel door het management worden gewijzigd.'>" . $cus->cus_verkoopsbedrag_incl . "</span>";
							echo "<input type='hidden' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";	
						}
					}else {
						echo $cus->cus_verkoopsbedrag_incl;
						echo "<input type='hidden' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";
					}
					
					echo "</span>";
					echo "</td>";
					echo "</tr>";
				}
				
				if( $cus->cus_verkoop == '2' )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens' ><span id='verhuur1' >Huur per paneel : </span> </td>";
					echo "<td>";
					echo "<span id='verhuur2' >";
					
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						if( $cus->cus_verkoopsbedrag_incl == 0 || $cus->cus_verkoopsbedrag_incl == "" || $_SESSION["kalender_user"]->group_id == 1 )
						{
							echo "<input type='text' class='lengte' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";
						}else
						{
							echo "<span title='Eenmaal dat de prijs is ingevuld, kan deze enkel door het management worden gewijzigd.'>" . $cus->cus_verkoopsbedrag_incl . "</span>";
							echo "<input type='hidden' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";	
						}
					}else {
						echo $cus->cus_verkoopsbedrag_incl;
						echo "<input type='hidden' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus->cus_verkoopsbedrag_incl ."' />";
					}
					echo "</span>";
					echo "</td>";
					echo "</tr>";
					
					if( $cus->cus_ont_huur == "" || $cus->cus_ont_huur == 0.00 )
					{
						$cus->cus_ont_huur = $cus->cus_werk_aant_panelen * $cus->cus_verkoopsbedrag_incl;
					}
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='verhuur3'> Tot. ont. huur per maand:</span></td>";
					echo "<td><span id='verhuur4'>";  
					
                    if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
					    $stijl = "";
                        
                        if( ( $cus->cus_ont_huur == 0 || empty($cus->cus_ont_huur) ) && $cus->cus_verkoop == '2' )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border:2px solid red;' ";    
                        }
                       
                        echo "<input ". $stijl ." type='text' class='lengte' name='ont_huur' id='ont_huur' value='". $cus->cus_ont_huur ."' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);'/>";
                    }else
                    {
                        echo number_format($cus->cus_ont_huur, 2, ",", "");
                        echo "<input type='hidden' name='ont_huur' id='ont_huur' value='". $cus->cus_ont_huur ."' />";
                    }
					echo "</span></td>";
					echo "</tr>";
					
                    if( $cus->cus_bet_huur == 0.00 )
                    {
                        $cus->cus_bet_huur = 0;
                    }
                    
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='verhuur5'> Tot. te betalen huur per maand:</span></td>";
					echo "<td><span id='verhuur6'>";
                    if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
		                  echo "<input type='text' class='lengte' name='bet_huur' id='bet_huur' value='". $cus->cus_bet_huur ."' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);'/>";
                    }else
                    {
                        echo number_format( $cus->cus_bet_huur, 2, ",", "");
                        echo "<input type='hidden' name='bet_huur' id='bet_huur' value='". $cus->cus_bet_huur ."' />";
                    }
					
					echo "</span></td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='verhuur9'> Looptijd huurcontr. : </span></td>";
					echo "<td><span id='verhuur10'>";
                    
                    $huur_jaar = 0;
                    $huur_maand = 0;
                    
                    if( $cus->cus_looptijd_huur == 0 )
                    {
                        $cus->cus_looptijd_huur = 240;
                    }
                    
                    if( $cus->cus_looptijd_huur > 0 )
                    {
                        $huur_maand = $cus->cus_looptijd_huur % 12;
                        $huur_jaar = explode(".", $cus->cus_looptijd_huur / 12 );
                        $huur_jaar = $huur_jaar[0];
                    }
                    
                    if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
                        echo "<select name='looptijd_jaar' id='looptijd_jaar'>";
                        
                        for( $i=0;$i<=50;$i++ )
                        {
                            if( $huur_jaar == $i )
                            {
                                echo "<option selected='selected' value='".$i."'>". $i ."</option>";
                            }else
                            {
                                echo "<option value='".$i."'>". $i ."</option>";    
                            }
                        }
                        
                        echo "</select>&nbsp;jaar&nbsp;&nbsp;&nbsp;";
                        
                        echo "<select name='looptijd_maand' id='looptijd_maand'>";
                        
                        for( $i=0;$i<=12;$i++ )
                        {
                            if( $huur_maand == $i )
                            {
                                echo "<option selected='selected' value='".$i."'>". $i ."</option>";
                            }else
                            {
                                echo "<option value='".$i."'>". $i ."</option>";    
                            }
                        }
                        
                        echo "</select>&nbsp;maanden";
                    }else
                    {
                        echo $huur_jaar . " jaar, " . $huur_maand . " maanden";
                        echo "<input type='hidden' name='looptijd_jaar' id='looptijd_jaar' value='". $huur_jaar ."' />";
                        echo "<input type='hidden' name='looptijd_maand' id='looptijd_maand' value='". $huur_maand ."' />";
                    }
                    
					//echo "<input type='text' class='lengte' name='looptijd_huur' id='looptijd_huur' value='". $cus->cus_looptijd_huur ."' onkeypress='return isNumberKey(event);' />";
					
                    echo "</span></td>";
					echo "</tr>";
                    
                    /* begin hypo */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='verhuur11'>Hypotheekvrijgave:</span></td>";
    				echo "<td><span id='verhuur12'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='hypotheek' id='hypotheek' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'hypotheek' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/hypotheek/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='hypotheek_del_". $orderbon->cf_id ."' id='hypotheek_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/hypotheek/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</td></tr>";
                    /* einde hypo */
                    
                    /* begin eigendomsacte */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='verhuur13'>Eigendomsakte:</span></td>";
    				echo "<td><span id='verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='eigendom' id='eigendom' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'eigendom' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/eigendom/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='eigendom_del_". $orderbon->cf_id ."' id='eigendom_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/eigendom/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</td></tr>";
                    /* einde eigendomsacte */
                    
                    /* begin isolatievw */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='verhuur13'>Isolatiedoc. :</span></td>";
    				echo "<td><span id='verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='isolatie' id='isolatie' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'isolatie' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/isolatie/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='isolatie_del_". $orderbon->cf_id ."' id='isolatie_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/isolatie/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</td></tr>";
                    /* einde isolatievw */
                    
                    /* begin loonfiche */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='verhuur13'>Loonfiche :</span></td>";
    				echo "<td><span id='verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='loonfiche' id='loonfiche' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'loonfiche' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/loonfiche/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='loonfiche_del_". $orderbon->cf_id ."' id='loonfiche_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/loonfiche/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</td></tr>";
                    /* einde loonfiche */
                    
                    /* begin vol_off */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='verhuur13'>Volledige offerte :</span></td>";
    				echo "<td><span id='verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='vol_off' id='vol_off' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'vol_off' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/vol_off/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='vol_off_del_". $orderbon->cf_id ."' id='vol_off_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/vol_off/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</td></tr>";
                    /* einde vol_off */
                    
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='id_off55'>Huurdocs. volledig:</span></td>";
    				echo "<td align='left'><span id='id_off56'>";
    					
    				if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
    				{
    					if( $cus->cus_huur_doc == "0" )
    					{
    						$huurdoc = "";
    					}else
    					{
    						$huurdoc = "checked='checked'";
    					}
    
    					echo "<input type='checkbox' ". $huurdoc ." name='huur_doc' id='huur_doc' />";
    				}else {
    				    
    					if( $cus->cus_huur_doc == "0" )
    					{
    						$huurdoc = "Nee";
                            $huurdoc_chk = "";
                            $waarde = 0;
    					}else
    					{
    						$huurdoc = "Ja";
                            $huurdoc_chk = "checked='checked'";
                            $waarde = 1;
    					}
    
    					echo $huurdoc;
                        echo "<input type='hidden' name='huur_doc' id='huur_doc' value='". $waarde ."' />";
    				}
    					
    				echo "</span></td>";
    				echo "</tr>";
				}
				
				echo "<tr>";
				
                if( $cus->cus_verkoop == '1' )
				{
                    echo "<td class='verkoop_gegevens'><span id='id_off53'>Datum orderbon:</span></td>";
                }else
                {
                    echo "<td class='verkoop_gegevens'><span id='id_off53'>Datum offerte:</span></td>";
                }
                
				echo "<td><span id='id_off54'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='datum_orderbon' id='datum_orderbon' value='". $cus->cus_datum_orderbon ."' />";
				}else {
					echo $cus->cus_datum_orderbon;
				}
					
				echo "</span></td>";
				echo "</tr>";
                
                if( $cus->cus_verkoop == '1' )
				{
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='id_off55'>Sunny Beam:</span></td>";
    				echo "<td align='left'><span id='id_off56'>";
    					
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					if( $cus->cus_sunnybeam == "0" )
    					{
    						$sunny = "";
    					}else
    					{
    						$sunny = "checked='checked'";
    					}
    
    					echo "<input type='checkbox' ". $sunny ." name='sunnybeam' id='sunnybeam' />";
    				}else {
    					if( $cus->cus_sunnybeam == "0" )
    					{
    						$sunnyb = "Nee";
    					}else
    					{
    						$sunnyb = "Ja";
    					}
    
    					echo $sunnyb;
    				}
    					
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='id_off57'>Actie:</span></td>";
    				echo "<td><span id='id_off58'>";
    					
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='text' class='lengte' name='actie' id='actie' value='". $cus->cus_actie ."' />";
    				}else {
    					echo $cus->cus_actie;
    				}
    				echo "</span></td>";
    				echo "</tr>";

                
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='id_off59'>Orderbon:</span></td>";
    				echo "<td><span id='id_off60'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='orderbon' id='orderbon' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus->cus_id ."'
    				                                  AND cf_soort = 'orderbon' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus->cus_id . "/orderbon/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='order_del_". $orderbon->cf_id ."' id='order_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus->cus_id . "/orderbon/" . rawurlencode($orderbon->cf_file) . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                }

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off62'>Ingetekend door: </span></td>";
				echo "<td><span id='id_off63'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='ingetekend' id='ingetekend' class='lengte' >";
					echo "<option value=''>== Keuze ==</option>";
					
                    foreach( $active_users as $user_id => $naam )
                    {
                        if( $cus->cus_ingetekend == $naam["voornaam"] )
						{
							echo "<option selected='selected' value='". $naam["voornaam"] ."'>". $naam["fullname"] ."</option>";
						}else
						{
							echo "<option value='". $naam["voornaam"] ."'>". $naam["fullname"] ."</option>";	
						}
                    }
					echo "</select>";
				}else {
					echo $cus->cus_ingetekend;
				}

				echo "</span></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Aantal panelen: </td>";
				echo "<td>";
					
				if( ($cus->cus_werk_aant_panelen == 0 || empty($cus->cus_werk_aant_panelen) ) && ( $cus->cus_aant_panelen != 0 || !empty($cus->cus_aant_panelen) ) )
				{
					$cus->cus_werk_aant_panelen = $cus->cus_aant_panelen;
				}

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					$stijl = "";
                        
                    if( ($cus->cus_werk_aant_panelen == 0 ||empty($cus->cus_werk_aant_panelen) ) && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
                    echo "<input type='text' " . $stijl . " class='lengte' name='werk_aant_panelen' id='werk_aant_panelen' value='". $cus->cus_werk_aant_panelen ."' onblur='checkConform();' />";
				}else 
                {
					echo $cus->cus_werk_aant_panelen;
				}
					
				echo "</td>";
				echo "</tr>";
                
                /*
                if( $client != NULL )
                {
                    if( $client->nbr_panels != $cus->cus_werk_aant_panelen )
                    {
                        echo "<tr>";
                        echo "<td colspan='2' class='error' align='center'>";
                        echo "Op de website werd " . $client->nbr_panels . " ingegeven.";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                */

				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Vermogen/paneel: </td>";
				echo "<td>";
					
				if( ($cus->cus_werk_w_panelen == 0 || empty($cus->cus_werk_w_panelen) ) && ( $cus->cus_w_panelen != 0 || !empty($cus->cus_w_panelen) ) )
				{
					$cus->cus_werk_w_panelen = $cus->cus_w_panelen;
				}

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                        
                    if( ($cus->cus_werk_w_panelen == 0 ||empty($cus->cus_werk_w_panelen) ) && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ".$stijl." type='text' class='lengte' name='werk_w_panelen' id='werk_w_panelen' value='". $cus->cus_werk_w_panelen ."' onblur='checkConform();' />";
				}else {
					echo $cus->cus_werk_w_panelen;
				}
					
				echo "</td>";
				echo "</tr>";
                
                /*
                if( $client != NULL )
                {
                    if( $client->radiation != $cus->cus_werk_w_panelen )
                    {
                        echo "<tr>";
                        echo "<td colspan='2' class='error' align='center'>";
                        echo "Op de website werd " . $client->radiation . " ingegeven.";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                */

				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Merk panelen: </td>";
				echo "<td>";
					
				if( (empty($cus->cus_werk_merk_panelen) ) && ( !empty($cus->cus_merk_panelen) ) )
				{
					$cus->cus_werk_merk_panelen = $cus->cus_merk_panelen;
				}

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='werk_merk_panelen' id='werk_merk_panelen' value='". $cus->cus_werk_merk_panelen ."' />";
				}else {
					echo $cus->cus_werk_merk_panelen;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Aantal omvormers: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='werk_aant_omvormers' id='werk_aant_omvormers' value='". $cus->cus_werk_aant_omvormers ."' />";
				}else {
					echo $cus->cus_werk_aant_omvormers;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td valign='top' class='verkoop_gegevens'>Type omvormer: </td>";
				echo "<td>";

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					if( $cus->cus_type_omvormers == "" )
					{
						echo "<select name='werk_omvormers[]' id='werk_omvormers[]' class='lengte' >";
						echo "<option value='0'>== Keuze ==</option>";

						foreach( $list_inv as $key => $inv )
						{
							if( $cus->cus_nzn == '1' )
							{
								if( !stristr( $inv, 'TL' ) )
								{
									echo "<option value='". $key ."'>". $inv ."</option>";
								}
							}else
							{
								echo "<option value='". $key ."'>". $inv ."</option>";
							}
						}

						echo "</select>";
					}else {
						if( !stristr( $cus->cus_type_omvormers, '@') )
						{
							// 1 waarde in het veld
							echo "<select name='werk_omvormers[]' id='werk_omvormers[]' class='lengte' >";
							echo "<option value='0'>== Keuze ==</option>";
							foreach( $list_inv as $key => $inv )
							{
								if( $cus->cus_nzn == '1' )
								{
									if( !stristr( $inv, 'TL' ) )
									{
										if( $key == $cus->cus_type_omvormers )
										{
											echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
										}else
										{
											echo "<option value='". $key ."'>". $inv ."</option>";
										}
									}
								}else
								{
									if( $key == $cus->cus_type_omvormers )
									{
										echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
									}else
									{
										echo "<option value='". $key ."'>". $inv ."</option>";
									}
								}
							}
                            
                            if( !in_array($cus->cus_type_omvormers, $list_inv) )
                            {
                                $naam_omv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $cus->cus_type_omvormers));
                                echo "<option selected='selected' value='". $cus->cus_type_omvormers ."' >". $naam_omv->in_inverter ."</option>";
                            }
                            
							echo "</select>";

						}else
						{
							// meerdere waardes gevonden
							$keuzes = explode('@', $cus->cus_type_omvormers );

							$aant_keuze = count($keuzes);

							$i=0;
							foreach( $keuzes as $keuze )
							{
								$i++;
								echo "<select name='werk_omvormers[]' id='werk_omvormers[]' class='lengte' >";
								echo "<option value='0'>== Keuze ==</option>";
								foreach( $list_inv as $key => $inv )
								{
									if( $cus->cus_nzn == '1' )
									{
										if( !stristr( $inv, 'TL' ) )
										{
											if( $key == $keuze )
											{
												echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
											}else
											{
												echo "<option value='". $key ."'>". $inv ."</option>";
											}
										}
									}else
									{
										if( $key == $keuze )
										{
											echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
										}else
										{
											echo "<option value='". $key ."'>". $inv ."</option>";
										}
									}
								}
                                
                                if( !in_array( $keuze, $list_inv) )
                                {
                                    $naam_omv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $keuze));
                                    echo "<option selected='selected' value='". $keuze ."' >". $naam_omv->in_inverter ."</option>";
                                }
                                
                                
								echo "</select>";

								if( $i < $aant_keuze )
								{
									echo "<br/>";
								}
							}
						}
					}

					echo "&nbsp;";
					echo "<b><a onclick='getInverters();' style='cursor:pointer;' >+</a></b>";
					echo "<span id='extra_inverters'></span>";
				}else 
				{
					if( !stristr( $cus->cus_type_omvormers, '@') )
					{
						// 1 waarde in het veld
						foreach( $list_inv as $key => $inv )
						{
							if( $key == $cus->cus_type_omvormers )
							{
								echo $inv;
							}
						}
                        
                        if( !in_array( $cus->cus_type_omvormers, $list_inv) )
                        {
                            $naam_omv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $cus->cus_type_omvormers));
                            echo $naam_omv->in_inverter;
                        }
                        
					}else
					{
						// meerdere waardes gevonden
						$keuzes = explode('@', $cus->cus_type_omvormers );

						$aant_keuze = count($keuzes);

						$i=0;
						foreach( $keuzes as $keuze )
						{
							$i++;
							foreach( $list_inv as $key => $inv )
							{
								if( $key == $keuze )
								{
									echo $inv;
								}
							}
                            
                            if( !in_array( $keuze, $list_inv) )
                            {
                                $naam_omv = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $keuze));
                                echo $naam_omv->in_inverter;
                            }
						}
					}
				}
					
				echo "</td>";
				echo "</tr>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					if( !empty( $cus->cus_type_omvormers ) )
					{
						$omv_keuzes = explode('@', $cus->cus_type_omvormers );
						
						if( count( $omv_keuzes ) == 1 )
						{
							echo "<tr>";
                            
                            if( !in_array($cus->cus_type_omvormers, $list_inv ) )
                            {
                                $omv_naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $cus->cus_type_omvormers ));
                                echo "<td  class='verkoop_gegevens'>SN. " . $omv_naam->in_inverter . " :</td>";
                            }else
                            {
                                echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$cus->cus_type_omvormers] . " :</td>";    
                            }
                            
							
							echo "<td>";
							
							$waarde = "";
							$q_zoek_waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_omvormers 
                                                                              WHERE co_cus_id = '". $cus->cus_id ."' 
                                                                              AND co_omvormer = '". $cus->cus_type_omvormers ."'"));
							
							if( !empty( $q_zoek_waarde->co_sn ) )
							{
								$waarde = $q_zoek_waarde->co_sn;
							} 
							
							echo "<input type='text' class='lengte' name='sn1' id='sn1' value='". $waarde ."' />";
							echo "<input type='hidden' name='omv1' id='omv1' value='".$cus->cus_type_omvormers."' />";
							
							echo "</td>";
							echo "</tr>";
                            echo "<tr>";
                            
                            if( !in_array($cus->cus_type_omvormers, $list_inv ) )
                            {
                                $omv_naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $cus->cus_type_omvormers ));
                                echo "<td  class='verkoop_gegevens'>Omv. " . $omv_naam->in_inverter . " :</td>";
                            }else
                            {
                                echo "<td  class='verkoop_gegevens'>Omv. " . $list_inv[ $cus->cus_type_omvormers ] . " :</td>";    
                            }
                            
                            echo "<td>";
							
							echo "<input type='text' class='lengte' name='text1' id='text1' value='". $q_zoek_waarde->co_text ."' />";
                            
							
							echo "</td>";
							echo "</tr>";
							echo "<input type='hidden' name='aantal_omv' id='aantal_omv' value='1' />";
						}else
						{
							$q_zoek_omv = mysqli_query($conn, "SELECT * FROM kal_customers_omvormers WHERE co_cus_id = " . $cus->cus_id) or die( mysqli_error($conn) );
							
							$i=0;
							
							$omv_keuzes_extra = $omv_keuzes; 
							
							// bestaande
							while( $keuze_omv = mysqli_fetch_object($q_zoek_omv) )
							{
								$i++;
								
								echo "<tr>";
                                
                                if( !in_array($keuze_omv->co_omvormer, $list_inv ) )
                                {
                                    $omv_naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $keuze_omv->co_omvormer ));
                                    echo "<td  class='verkoop_gegevens'>SN. " . $omv_naam->in_inverter . " :</td>";
                                }else
                                {
                                    echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";    
                                }
								echo "<td>";
								
								echo "<input type='text' class='lengte' name='sn". $i ."' id='sn". $i ."' value='". $keuze_omv->co_sn ."' />";
								echo "<input type='hidden' name='omv". $i ."' id='omv". $i ."' value='".$keuze_omv->co_omvormer."' />";
								
								echo "</td>";
								echo "</tr>";
                                
                                echo "<tr>";
                                if( !in_array($keuze_omv->co_omvormer, $list_inv ) )
                                {
                                    $omv_naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_inverters WHERE in_id = " . $keuze_omv->co_omvormer ));
                                    echo "<td  class='verkoop_gegevens'>Omv. " . $omv_naam->in_inverter . " :</td>";
                                }else
                                {
                                    echo "<td  class='verkoop_gegevens'>Omv. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";    
                                }
								echo "<td>";
								
								echo "<input type='text' class='lengte' name='text". $i ."' id='text". $i ."' value='". $keuze_omv->co_text ."' />";
								
								echo "</td>";
								echo "</tr>";
								
								// diegene die al bestaan niet meer tonen in de volgende lus
								$blokme = 0;
								foreach( $omv_keuzes_extra as $key1 => $o )
								{
									if( $o == (int)$keuze_omv->co_omvormer )
									{
										if( $blokme == 0 )
										{
											unset($omv_keuzes_extra[$key1]);
											$blokme = 1;	
										}
									}
								}
							}
							
							// nieuwe
							foreach( $omv_keuzes_extra as $key => $keuze )
							{
								$i++;
								
								echo "<tr>";
								echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$keuze] . " :</td>";
								echo "<td>";
								
								$waarde = "";
								$q_zoek_waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_omvormers WHERE co_cus_id = '". $cus->cus_id ."' AND co_omvormer = '". $keuze ."'"));
								
								if( !empty( $q_zoek_waarde->co_sn ) )
								{
									$waarde = $q_zoek_waarde->co_sn;
								}
								
								echo "<input type='text' class='lengte' name='sn". $i ."' id='sn". $i ."' value='". $waarde ."' />";
								echo "<input type='hidden' name='omv". $i ."' id='omv". $i ."' value='".$keuze."' />";
								
								echo "</td>";
								echo "</tr>";
                                
                                echo "<tr>";
								echo "<td  class='verkoop_gegevens'>Omv. " . $list_inv[$keuze] . " :</td>";
								echo "<td>";
								echo "<input type='text' class='lengte' name='text". $i ."' id='text". $i ."' value='". $q_zoek_waarde->co_text ."' />";
								echo "</td>";
								echo "</tr>";
							}
							echo "<input type='hidden' name='aantal_omv' id='aantal_omv' value='". $i ."' />";
						}
					}	
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off64'>Werkdocument gemaakt door:</span> </td>";
				echo "<td><span id='id_off65'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='werkdocument_door' id='werkdocument_door' value='". $cus->cus_werkdoc_door ."' /> ";
				}else {
					echo $cus->cus_werkdoc_door;
				}

				echo "</span></td>";
				echo "</tr>";

				$sel0 = "";
				$sel1 = "";

				if( $cus->cus_werkdoc_klaar == '0' )
				{
					$sel0 = "selected='yes'";
				}else
				{
					$sel1 = "selected='yes'";
				}

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off66'>Werkdocument klaar?: </span></td>";
				echo "<td><span id='id_off67'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='werkdocument_klaar' id='werkdocument_klaar'>";
					echo "<option value='0' ".$sel0." >Neen</option>";
					echo "<option value='1' ".$sel1." >Ja</option>";
					echo "</select>";
				}else {
					if( $cus->cus_werkdoc_klaar == '0' )
					{
						echo "Neen";
					}else
					{
						echo "Ja";
					}
				}
				echo "</span></td>";
				echo "</tr>";
				
                $checked = "";
				if( $cus->cus_werkdoc_check == '1' )
				{
					$checked = " checked='checked' ";
				}else
				{
					$checked = "";
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off68'>Gecontrolleerd?: </span></td>";
				echo "<td><span id='id_off69'>";
				if( $_SESSION["kalender_user"]->group_id == 1 )
				{
					echo "<input type='checkbox' ". $checked ." name='werkdoc_check' id='werkdoc_check' />";
				}else {
					if( $cus->cus_werkdoc_check == '0' )
					{
						echo "Neen";
					}else
					{
						echo "Ja";
					}
                    
                    echo "<input type='hidden' name='werkdoc_check' id='werkdoc_check' value='".$cus->cus_werkdoc_check."' />";
				}
                
				echo "</span></td>";
				echo "</tr>";

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td><b>";
					echo "<a href='pdf_werkdoc.php?id=". $cus->cus_id ."' target='_blank'><input type='button' value='Genereer werkdocument' /></a>";

					echo "</b></td>";
					echo "<td>";

					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Opmerking:</td>";
					echo "<td>";
					echo "<input type='text' class='lengte' name='werkdoc_opm' id='werkdoc_opm' value='". $cus->cus_werkdoc_opm ."' />";
					echo "</td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Opmerking2:</td>";
					echo "<td>";
					echo "<input type='text' class='lengte' name='werkdoc_opm2' id='werkdoc_opm2' value='". $cus->cus_werkdoc_opm2 ."' />";
					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Foto 1: (.jpg)</td>";
					echo "<td>";
					echo "<input type='file' class='lengte' name='werkdoc_pic1' id='werkdoc_pic1' value='". $cus->cus_werkdoc_pic1 ."' />";
					echo "</td>";
					echo "</tr>";

					if( !empty( $cus->cus_werkdoc_pic1) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";

						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='werkdocpic1_del' id='werkdocpic1_del' />";
						}

						echo "</td><td>";

						echo "<a href='cus_docs/". $cus->cus_id . "/werkdocument_file/pic1/" . $cus->cus_werkdoc_pic1 ."' target='_blank' >";
						echo $cus->cus_werkdoc_pic1 ;
						echo "</a>";

						echo "</td></tr>";
					}

					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Foto 2: (.jpg)</td>";
					echo "<td>";
					echo "<input type='file' name='werkdoc_pic2' id='werkdoc_pic2' value='". $cus->cus_werkdoc_pic2 ."' />";
					echo "</td>";
					echo "</tr>";

					if( !empty( $cus->cus_werkdoc_pic2) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";

						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='werkdocpic2_del' id='werkdocpic2_del' />";
						}

						echo "</td><td>";

						echo "<a href='cus_docs/". $cus->cus_id . "/werkdocument_file/pic2/" . $cus->cus_werkdoc_pic2 ."' target='_blank' >";
						echo $cus->cus_werkdoc_pic2;
						echo "</a>";

						echo "</td></tr>";
					}

					echo "<tr>";
					echo "<td>&nbsp;</td>";
					echo "<td>OF</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Werkdocument invoegen: </td>";
					echo "<td>";
					echo "<input type='file' name='werkdocument_file' id='werkdocument_file' />";
					echo "</td>";
					echo "</tr>";
				}

				if( !empty( $cus->cus_werkdoc_file ) && !empty( $cus->cus_werkdoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='werkdoc_del' id='werkdoc_del' />";
					}

					echo "</td><td>";

					if( file_exists( "cus_docs/" . $cus->cus_id . "/werkdocument_file/" . $cus->cus_werkdoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus->cus_id . "/werkdocument_file/" . rawurlencode($cus->cus_werkdoc_filename) . "' target='_blank' >";
						echo $cus->cus_werkdoc_filename;
						echo "</a>";
					}else
					{
						echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus->cus_id ."&soort=werkdoc_file\",\"". $cus->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo $cus->cus_werkdoc_filename;
						echo "</a>";
					}

					echo "</td></tr>";
				}else
				{
					if( !empty( $cus->cus_werkdoc_filename) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";

						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='werkdoc_del' id='werkdoc_del' />";
						}

						echo "</td><td>";

						if( file_exists( "cus_docs/" . $cus->cus_id . "/werkdocument_file/" . $cus->cus_werkdoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus->cus_id . "/werkdocument_file/" . rawurlencode($cus->cus_werkdoc_filename) . "' target='_blank' >";
							echo $cus->cus_werkdoc_filename;
							echo "</a>";
						}else
						{
							echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus->cus_id ."&soort=werkdoc_file\",\"". $cus->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
							echo $cus->cus_werkdoc_filename;
							echo "</a>";
						}

						echo "</td></tr>";
					}
				}

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off83'>Max. AC-vermogen: </span></td>";
				echo "<td><span id='id_off84'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='ac_vermogen' id='ac_vermogen' value='". $cus->cus_ac_vermogen ."' onblur='checkDriefase(this);' />";
				}else {
					echo $cus->cus_ac_vermogen;
				}
					
				echo "</span></td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off85'>Conform offerte: </span></td>";
				echo "<td><span id='id_off86'>";
				// nakijken van aantal panelen en vermogen per panelen
				echo "<span id='conform_offerte'>";

				if( $cus->cus_aant_panelen == $cus->cus_werk_aant_panelen && $cus->cus_w_panelen == $cus->cus_werk_w_panelen )
				{
					echo "Ja";
				}else
				{
					echo "<span class='error'>Neen</span>";
				}

				echo "</span>";
				echo "</span>";
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off87'>Orderbon aanpassen: </span></td>";
				echo "<td><span id='id_off88'>";
				echo "<span id='id_orderbon'>";

				if( $cus->cus_aant_panelen == $cus->cus_werk_aant_panelen && $cus->cus_w_panelen == $cus->cus_werk_w_panelen )
				{
					echo "Neen";
				}else
				{
					echo "<span class='error'>Ja</span>";
				}

				echo "</span>";
				echo "</span>";
				echo "</td>";
				echo "</tr>";

				// begin elec schema
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='id_off89'>Elec. schema:</span></td>";
					echo "<td><span id='id_off90'>";
					echo "<input type='file' name='doc_elec' id='doc_elec' />";
					echo "</span></td>";
					echo "</tr>";
				}

				if( !empty( $cus->cus_elecdoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'><span id='id_off91'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='elecdoc_del' id='elecdoc_del' />";
					}

					echo "</span></td><td><span id='id_off92'>";

					if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_elec/" . $cus->cus_elecdoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus->cus_id . "/doc_elec/" . $cus->cus_elecdoc_filename . "' target='_blank' >";
						echo $cus->cus_elecdoc_filename;
						echo "</a>";
					}
					echo "</span></td></tr>";
				}
				// einde elec schema
                
                if( $cus->cus_dom_datum == "0000-00-00" )
				{
					$cus->cus_dom_datum = "";
				}else
				{
					$datum = explode("-", $cus->cus_dom_datum);
					$cus->cus_dom_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}
                
                echo "<tr>";
				echo "<td class='verkoop_gegevens'>Startdatum domicili&euml;ring: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='datum_dom' id='datum_dom' value='". $cus->cus_dom_datum ."' />";
				}else {
					echo $cus->cus_dom_datum;
				}

				echo "</td>";
				echo "</tr>";
                
                $checked = "";
				if( $cus->cus_overschrijving == '1' )
				{
					$checked = " checked='checked' ";
				}else
				{
					$checked = "";
				}
                
                echo "<tr>";
				echo "<td class='verkoop_gegevens'>Via overschrijving :</td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='checkbox' ". $checked ." name='overschrijving' id='overschrijving' value='". $cus->cus_overschrijving ."' />";
				}else {
					echo $cus->cus_overschrijving;
				}

				echo "</td>";
				echo "</tr>";
			
				$sel0 = "";
				$sel1 = "";

				if( $cus->cus_arei == '0' )
				{
					$sel0 = "selected='yes'";
				}else
				{
					$sel1 = "selected='yes'";
				}

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off93'>AREI keuring: </span></td>";
				echo "<td><span id='id_off94'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='arei' id='arei'>";
					echo "<option value='0' ".$sel0." >Niet OK</option>";
					echo "<option value='1' ".$sel1." >OK</option>";
					echo "</select>";
				}else {
					if( $cus->cus_arei == '0' )
					{
						echo "Niet OK";
					}else
					{
						echo "OK";
					}
				}

				echo "</span></td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Datum AREI keuring: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='datum_arei' id='datum_arei' value='". $cus->cus_arei_datum ."' />";
				}else {
					echo $cus->cus_arei_datum;
				}

				echo "</td>";
				echo "</tr>";
                
                /*
                if( $client != NULL )
                {
                    if( substr($client->start_date, 0, 10) != changeDate2EU($cus->cus_arei_datum) )
                    {
                        echo "<tr>";
                        echo "<td colspan='2' class='error' align='center'>";
                        echo "Op de website werd " . changeDate2EU( substr($client->start_date, 0, 10) ) . " ingegeven.";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                */

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='id_off97'>Document AREI keuring: </span></td>";
					echo "<td><span id='id_off98'>";
					echo "<input type='file' name='doc_arei' id='doc_arei' />";
					echo "</span></td>";
					echo "</tr>";
				}

				if( !empty( $cus->cus_areidoc_file  ) && !empty( $cus->cus_areidoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'><span id='id_off99'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='areidoc_del' id='areidoc_del' />";
					}

					echo "</span></td><td><span id='id_off100'>";

					if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_arei/" . $cus->cus_areidoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus->cus_id . "/doc_arei/" . $cus->cus_areidoc_filename . "' target='_blank' >";
						echo $cus->cus_areidoc_filename;
						echo "</a>";
					}else
					{
						echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus->cus_id ."&soort=areidoc_file\",\"". $cus->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo $cus->cus_areidoc_filename;
						echo "</a>";
					}
					echo "</span></td></tr>";
				}else
				{
					if( !empty( $cus->cus_areidoc_filename) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'><span id='id_off101'>";

						if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='areidoc_del' id='areidoc_del' />";
						}

						echo "</span></td><td><span id='id_off102'>";

						if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_arei/" . $cus->cus_areidoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus->cus_id . "/doc_arei/" . $cus->cus_areidoc_filename . "' target='_blank' >";
							echo $cus->cus_areidoc_filename;
							echo "</a>";
						}else
						{
							echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus->cus_id ."&soort=areidoc_file\",\"". $cus->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
							echo $cus->cus_areidoc_filename ;
							echo "</a>";
						}

						echo "</span></td></tr>";
					}
				}

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off103'>Meterstand AREI keuring: </span></td>";
				echo "<td><span id='id_off104'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='arei_meterstand' id='arei_meterstand' value='". $cus->cus_arei_meterstand ."' />";
				}else {
					echo $cus->cus_arei_meterstand;
				}

				echo "</span></td>";
				echo "</tr>";

				$sel0 = "";
				$sel1 = "";

				if( $cus->cus_klant_tevree == '0' )
				{
					$sel0 = "selected='yes'";
				}

				if( $cus->cus_klant_tevree == '1' )
				{
					$sel1 = "selected='yes'";
				}

				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off105'>Klant tevreden: </span></td>";
				echo "<td><span id='id_off106'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='klant_tevree' id='klant_tevree' onchange='toonKlantNietTevree(this);' class='lengte'>";
					echo "<option value='' >== Keuze ==</option>";
					echo "<option value='0' ".$sel0." >Neen</option>";
					echo "<option value='1' ".$sel1." >Ja</option>";
					echo "</select>";
				}else {
					if( $cus->cus_klant_tevree == '0' )
					{
						echo "Neen";
					}

					if( $cus->cus_klant_tevree == '1' )
					{
						echo "Ja";
					}
				}

				echo "</span></td>";
				echo "</tr>";
				
				$stijl_niet_tevree = "";
				if( $cus->cus_klant_tevree != '0' )
				{
					$stijl_niet_tevree = "display:none;";
				}
				echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off107'>";
				echo "<span id='niet_tevree2'  style='". $stijl_niet_tevree ."' >";
				echo "Waarom niet?";
				echo "</span>";
				echo "</span>";
				echo "</td>";
				echo "<td><span id='id_off108'>";
				echo "<span id='niet_tevree1' style='". $stijl_niet_tevree ."' >";
				echo "<input type='text' class='lengte' name='niet_tevree' id='niet_tevree' value='". $cus->cus_tevree_reden ."' />";
				echo "</span>";
				echo "</span>";
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				echo "</fieldset>";
				
				$stijl = "style='display:none;'";

				if( $cus->cus_verkoop == '1' || $cus->cus_verkoop == '2' )
				{
					$stijl = " style='display:block;' ";
				}
				
                /*
				if( $cus->cus_oa == '1' )
				{
					$stijl = " style='display:none;' ";
				}
				*/
                
				echo "<fieldset id='tabel4' ". $stijl ." >";
				
				echo "<legend>Opvolging</legend>";
				echo "<table width='100%'>";

				if( $cus->cus_vreg_datum == "0000-00-00" )
				{
					$cus->cus_vreg_datum = "";
				}else
				{
					$datum = explode("-", $cus->cus_vreg_datum);
					$cus->cus_vreg_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}

				echo "<tr>";
				echo "<td>Datum VREG aanvraag: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='datum_vreg' id='datum_vreg' value='". $cus->cus_vreg_datum ."' />";
				}else {
					echo $cus->cus_vreg_datum;
				}
					
				echo "</td>";
				echo "</tr>";
                
                echo "<tr>";
                echo "<td>VREG MB nr. :</td>";
                echo "<td>";
                if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='vreg_un' id='vreg_un' value='". $cus->cus_vreg_un ."' />";
				}else {
					echo $cus->cus_vreg_un;
				}
                echo "</td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td>VREG pwd :</td>";
                echo "<td>";
                if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='vreg_pwd' id='vreg_pwd' value='". $cus->cus_vreg_pwd ."' />";
				}else {
					echo $cus->cus_vreg_pwd;
				}
                echo "</td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td>VREG opm. :</td>";
                echo "<td>";
                if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<textarea type='text' style='width:225px;' name='vreg_opm' id='vreg_opm'/>". $cus->cus_vreg_opm ."</textarea>";
				}else {
					echo $cus->cus_vreg_opm;
                    echo "<input type='hidden' name='vreg_opm' id='vreg_opm' value='". $cus->cus_vreg_opm ."' />";
				}
                echo "</td>";
                echo "</tr>";

				if( $cus->cus_datum_net == "0000-00-00" )
				{
					$cus->cus_datum_net = "";
				}else
				{
					$datum = explode("-", $cus->cus_datum_net);
					$cus->cus_datum_net = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}

				echo "<tr>";
				echo "<td>Meldingsdatum netbeheerder: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='datum_net' id='datum_net' value='". $cus->cus_datum_net ."' />";
				}else {
					echo $cus->cus_datum_net;
				}
					
				echo "</td>";
				echo "</tr>";
                
                echo "<tr>";
				echo "<td>Netbeheerder: <a style='color:seagreen;' href='http://www.vreg.be/uw-netbeheerder' target='_blank'>Zoek</a> </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='netbeheerder' id='netbeheerder' value='". $cus->cus_netbeheerder ."' />";
				}else {
					echo $cus->cus_netbeheerder;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>PVZ nr.: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='pvz' id='pvz' value='". $cus->cus_pvz ."' />";
				}else {
					echo $cus->cus_pvz;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>EAN nr.: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='ean' id='ean' value='". $cus->cus_ean ."' />";
				}else {
					echo $cus->cus_ean;
				}
					
				echo "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>Rekeningnummer klant: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='reknr' id='reknr' value='". $cus->cus_reknr ."' />";
				}else {
					echo $cus->cus_reknr;
				}
					
				echo "</td>";
				echo "</tr>";
                
                echo "<tr>";
				echo "<td>IBAN : <a style='color:seagreen;' href='http://www.ibanbic.be/' target='_blank' >Calculator</a> </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    
                    $stijl = "";
                    
                    if( empty($cus->cus_iban) && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ". $stijl ." type='text' class='lengte' name='iban' id='iban' value='". $cus->cus_iban ."' />";
				}else {
					echo $cus->cus_iban;
				}
					
				echo "</td>";
				echo "</tr>";
                
                echo "<tr>";
				echo "<td>BIC : </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                    
                    if( empty($cus->cus_bic) && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ". $stijl ." type='text' class='lengte' name='bic' id='bic' value='". $cus->cus_bic ."' />";
				}else {
					echo $cus->cus_bic;
				}
					
				echo "</td>";
				echo "</tr>";
                
                echo "<tr>";
				echo "<td>Naam van de bank: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                    
                    if( empty($cus->cus_banknaam) && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ". $stijl ." type='text' class='lengte' name='banknaam' id='banknaam' value='". $cus->cus_banknaam ."' />";
				}else {
					echo $cus->cus_banknaam;
				}
					
				echo "</td>";
				echo "</tr>";

				$sel0 = "";
				$sel1 = "";
				$sel2 = "";

				switch( $cus->cus_gemeentepremie )
				{
					case '0' :
						$sel0 = "selected='yes'";
						break;
					case '1' :
						$sel1 = "selected='yes'";
						break;
					case '2' :
						$sel2 = "selected='yes'";
						break;
				}

				echo "<tr>";
				echo "<td>Gemeentepremie: </td>";
				echo "<td>";
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                    
                    if( $cus->cus_gemeentepremie == '2' && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<select ". $stijl ." name='gem_premie' id='gem_premie' class='lengte'>";
					echo "<option value='2' ". $sel2 .">== Keuze ==</option>";
					echo "<option value='0' ". $sel0 .">Nee</option>";
					echo "<option value='1' ". $sel1 .">Ja</option>";
					echo "</select>";
				}else
				{
					switch( $cus->cus_gemeentepremie )
					{
						case '0' :
							echo "Nee";
							break;
						case '1' :
							echo "Ja";
							break;
						case '2' :
							echo "-";
							break;
					}
				}
					
				echo "</td>";
				echo "</tr>";

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td>Gemeentepremie document: </td>";
					echo "<td>";
					echo "<input type='file' name='doc_gemeente' id='doc_gemeente' />";
					echo "</td>";
					echo "</tr>";
				}

				if( !empty( $cus->cus_gemeentedoc_filename ) )
				{
					echo "<tr><td align='right'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='gemeentedoc_del' id='gemeentedoc_del' />";
					}

					echo "</td><td>";

					if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_gemeente/" . $cus->cus_gemeentedoc_filename ) )
					{
						//echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus->cus_id ."&soort=gemeentedoc_file\",\"". $cus->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo "<a href='cus_docs/" . $cus->cus_id . "/doc_gemeente/" . $cus->cus_gemeentedoc_filename . "' target='_blank' >";
						echo $cus->cus_gemeentedoc_filename;
						echo "</a>";
					}


					echo "</td></tr>";
				}

				$sel0 = "";
				$sel1 = "";
				$sel2 = "";

				switch( $cus->cus_bouwvergunning )
				{
					case '0' :
						$sel0 = "selected='yes'";
						break;
					case '1' :
						$sel1 = "selected='yes'";
						break;
					case '2' :
						$sel2 = "selected='yes'";
						break;
				}

				echo "<tr>";
				echo "<td>Bouwvergunning: </td>";
				echo "<td>";

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                    
                    if( $cus->cus_bouwvergunning == '2' && !empty( $cus->cus_verkoop ) )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<select ". $stijl ." name='bouwver' id='bouwver' class='lengte'>";
					echo "<option value='2' ". $sel2 .">== Keuze ==</option>";
					echo "<option value='0' ". $sel0 .">Neen</option>";
					echo "<option value='1' ". $sel1 .">Ja</option>";
					echo "</select>";
				}else
				{
					switch( $cus->cus_bouwvergunning )
					{
						case '0' :
							echo "Neen";
							break;
						case '1' :
							echo "Ja";
							break;
						case '2' :
							echo "-";
							break;
					}
				}
					
				echo "</td>";
				echo "</tr>";

				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td>Bouwvergunning document: </td>";
					echo "<td>";
					echo "<input type='file' name='doc_bouwver' id='doc_bouwver' />";
					echo "</td>";
					echo "</tr>";
				}

				if( !empty( $cus->cus_bouwvergunning_filename ) )
				{
					echo "<tr><td align='right'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='bouwverdoc_del' id='bouwverdoc_del' />";
					}

					echo "</td><td>";

					if( file_exists( "cus_docs/" . $cus->cus_id . "/doc_bouw/" . $cus->cus_bouwvergunning_filename ) )
					{
						echo "<a href='cus_docs/" . $cus->cus_id . "/doc_bouw/" . $cus->cus_bouwvergunning_filename . "' target='_blank' >";
						echo $cus->cus_bouwvergunning_filename;
						echo "</a>";
					}

					echo "</td></tr>";
				}
				
				
				// ************** //

				echo "</table>";
				echo "</fieldset>";
                
                // futech.be
                $link_futech = mysqli_connect('localhost', 'futech', 'solarmysql?321');
                mysqli_select_db($link_futech, 'futech');
                
                // zoeken ofdat er een koppeling kan gemaakt worden, tussen solarlogs.be en futech.be
                $q_zoek_klant = mysqli_query($link_futech, "SELECT * FROM UserData WHERE UserEmail = '". $cus->cus_email ."'");
                
                $aantal_futech = mysqli_num_rows($q_zoek_klant);
                
                $gev = 0;
                $gev = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kal_customers_kwh WHERE cus_id = " . $cus->cus_id . " LIMIT 1"));
                
                if( $cus->cus_verkoop == "1" || $cus->cus_verkoop == "2" || $aantal_futech > 0 || $gev || 1 == 1 )
                {
                    echo "<fieldset>";
                    echo "<legend>Mijn Futech</legend>";
                    echo "<table>";
                    echo "<tr><td>";
                    
                    if( empty( $cus->cus_acma ) || ( $cus->cus_verkoop != '1' && $cus->cus_verkoop != '2' ) )
                    {
                        echo "<table>";
                        echo "<tr>";
                        echo "<td>AREI keuringsdatum :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='datum_arei1' id='datum_arei1' value='". $cus->cus_arei_datum ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td>Aantal panelen :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='werk_aant_panelen' id='werk_aant_panelen' value='". $cus->cus_werk_aant_panelen ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td>Vermogen per paneel :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='werk_w_panelen' id='werk_w_panelen' value='". $cus->cus_werk_w_panelen ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td>Opbrengstfactor :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='kwhkwp' id='kwhkwp' value='". $cus->cus_kwhkwp ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        if( empty($cus->cus_acma) && empty( $cus->cus_offerte_datum ) )
                        {
                            echo "<tr>";
                            echo "<td>Opbrengstfactor :</td>";
                            echo "<td>";
                            echo "<input type='text' class='lengte' name='kwhkwp' id='kwhkwp' value='". $cus->cus_kwhkwp ."' />";
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo "<tr>";
                        echo "<td>VREG MB nr. :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='vreg_un' id='vreg_un' value='". $cus->cus_vreg_un ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td>VREG pwd :</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='vreg_pwd' id='vreg_pwd' value='". $cus->cus_vreg_pwd ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "<tr>";
                        echo "<td>PVZ nr.:</td>";
                        echo "<td>";
                        echo "<input type='text' class='lengte' name='pvz' id='pvz' value='". $cus->cus_pvz ."' />";
                        echo "</td>";
                        echo "</tr>";
                        
                        echo "</table>";
                    }
                    
                    if( empty( $cus->cus_arei_datum ) )
                    {
                        echo "&nbsp;&nbsp;<span class='error'>AREI keuringsdatum nog niet gekend</span>";
                    }else
                    {
                        // nakijken ofdat de keuringsdatum in het verleden ligt
                        $arei_dmy = explode("-", $cus->cus_arei_datum );
                        $arei_stamp = mktime( 0, 0, 0, $arei_dmy[1], $arei_dmy[0], $arei_dmy[2] );
                        
                        if( $arei_stamp < time() )
                        {
                            $verwacht = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_kwh WHERE cus_id = " . $cus->cus_id . " AND jaar = " . date('Y'). " AND maand = " . date('m') . " AND dag = " . date('d')));
                            
                            if( mysqli_num_rows($q_zoek_klant) > 0 )
                            {
                                
                                // nakijken ofdat de activatiecode nog is ingevuld.
                                $fut_user = mysqli_fetch_object($q_zoek_klant);
                                
                                $fut_code = mysqli_fetch_object(mysqli_query($link_futech, "SELECT * FROM Users WHERE UserId = " . $fut_user->UserId));
                                
                                if( !empty( $fut_code->activation_code ) )
                                {
                                    echo "<span class='error'>Klant moet zijn account nog activeren.</span>";  
                                    echo "<br/>";
                                    echo "De klant dient nog te klikken op de link die hij in de e-mail ontvangen heeft. Ofwel <b><a href='http://www.futech.be/ActivateUser.php?code=".$fut_code->activation_code."&user=".$fut_code->UserName."&id=".$fut_code->UserId."'>Handmatig activeren</a></b>";
                                    
                                    // zoek de verwachte meterstand
                                    echo "<br/><br/>Verwachte meterstand : <span class='correct'>" . number_format( $verwacht->waarde, 2, ",", "" ) . " kWh</span>";    
                                }else
                                {
                                    echo "<span class='correct'>Klant maakt gebruik van de grafiek op futech.be</span>";
                                    echo "<br/><br/>";
                                    //echo "Verwachte meterstand : " . getmeterstand( $cus->cus_id, $conn, $link_futech );
                                    
                                    // zoek de verwachte meterstand
                                    echo "Verwachte meterstand : <span class='correct'>" . number_format( $verwacht->waarde, 2, ",", "" ) . " kWh</span><br/>";
                                    
                                    // deze klant zoeken uit de db van futech.be
                                    $go = 1;
                                    $dag = "";
                                    $waarde = 0;
                                    
                                    //$q_userdata = mysqli_query($link_futech, "SELECT * FROM UserData WHERE UserEmail = '". $cus->cus_id ."'");
                                    
                                    $aantal_punten = 0;
                                        
                                    $q_futech_client = mysqli_query($link_futech, "SELECT * FROM futech_client WHERE cus_id = " . $cus->cus_id);
                                    $futech_client = mysqli_fetch_object($q_futech_client);
                                    
                                    if( mysqli_num_rows($q_futech_client) == 0 )
                                    {
                                        $go = 0;
                                    }
                                    
                                    $fm = array();
                                    
                                    $q_fm = mysqli_query($link_futech, "SELECT * FROM futech_measure WHERE client_id = " . $futech_client->id . " ORDER BY day ASC");
                                    
                                    if( count( $q_fm ) > 0 && $go == 1 )
                                    {
                                        while( $m = mysqli_fetch_object($q_fm) )
                                        {
                                            $aantal_punten++;
                                            if( $waarde < $m->measure )
                                            {
                                                $dag = $day = substr($m->day, 0, 4) . "-" . substr($m->day, 4, 2) . "-" . substr($m->day,6,2);
                                                $waarde = $m->measure;
                                            }
                                        }
                                    }
                                    
                                    $dag = changeDate2EU($dag);
                                    
                                    if( $dag == "" || $dag == "--" )
                                    {
                                        $dag = "/";
                                    }

                                    echo "Laatst ingegeven meterstand :  <span class='correct'>" . number_format($waarde,0,"","") . " kWh</span> op  <span class='correct'>" . $dag . "</span>";
                                    
                                    if( number_format($waarde,0,"","") != 0 )
                                    {
                                        echo "<br>Aantal ingegeven punten : " . "<span class='correct'>".$aantal_punten. "</span>";
                                    }
                                    
                                    echo "<br/><br/>";
                                    ?>
                                    <input type='button' name='btn_grafiek' id='btn_grafiek' value="Open grafiek" onclick="window.open('http://www.futech.be/mijnfutech_solarlogs.php?cus_id=<?php echo $cus->cus_id;  ?>','Monitoring tool','status,width=1100,height=960,scrollbars=yes'); return false;" />
                                    <?php
                                }
                            }else
                            {
                                if( !empty( $cus->cus_email ) )
                                {
                                    echo "<span class='error'>Geen account gevonden op www.futech.be met e-mailadres : ". $cus->cus_email ."</span>";
                                    echo "&nbsp;&nbsp;<b><a href='fut_grafiek.php'>Maak account</a></b>";  
                                    
                                    // zoek de verwachte meterstand
                                    echo "<br/>Verwachte meterstand : <span class='correct'>" . number_format( $verwacht->waarde, 2, ",", "" ) . " kWh</span>";  
                                }else
                                {
                                    echo "<span class='error'>E-mail adres ontbreekt.</span>";
                                }
                            }
                        }else
                        {
                            echo "<span class='error'>De AREI-keuring zal plaatsvinden op ". $cus->cus_arei_datum .".</span>";
                        }
                    }
                    
                    echo "</td></tr>";
                    echo "</table>";
                    echo "</fieldset>";
                }
                
                echo "<fieldset>";
                echo "<legend>Extra documenten/foto's</legend>";
                echo "<table>";
                echo "<tr><td colspan='2'>";
                echo "<i>Kies goede bestandsnamen.</i>";
                echo "</td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td>";
                echo "<strong>Upload document :</strong>";
                echo "</td>";
                echo "<td>";
                echo "<input type='file' name='file_extra' id='file_extra' />";
                echo "</td>";
                echo "</tr>";
                
				// zoeken of er offertes zijn
				$q_zoek_extra = mysqli_query($conn, "SELECT * 
				                               FROM kal_customers_files
				                              WHERE cf_cus_id = '". $cus->cus_id ."'
				                                AND cf_soort = 'file_extra' ");
				
				if( mysqli_num_rows($q_zoek_extra) > 0 )
				{
				    echo "<tr><td colspan='2'>";
					echo "<table width='100%'>";
					
					while( $extra_f = mysqli_fetch_object($q_zoek_extra) )
					{
						if( file_exists( "cus_docs/" . $cus->cus_id . "/file_extra/" . $extra_f->cf_file ) )
						{
							echo "<tr><td align='right' valign='top' class='offerte_gegevens' >";

							if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
							{
								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='file_extra_del_". $extra_f->cf_id ."' id='file_extra_del_". $extra_f->cf_id ."' />";
							}
							
							echo "</td><td>";
							
							echo "<a href='cus_docs/" . $cus->cus_id . "/file_extra/" . $extra_f->cf_file . "' target='_blank' >";
							echo $extra_f->cf_file;
							echo "</a>";
                            
							echo "</td>";
							echo "</tr>";
						}
					}
					
					echo "</table>";
                    echo "</td></tr>";
				}
				
				
                
                
                echo "</table>";
                echo "</fieldset>";

				// EINDE TABEL 2
				echo "</td>";
				echo "</tr>";

				echo "</table>";

				echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
				echo "<input type='hidden' name='cus_id' id='cus_id' value='". $cus->cus_id ."' />";
				echo "<input type='hidden' name='cus_id2' id='cus_id2' value='". $cus->cus_id ."' />";
				
				$stijl = "style='display:none;'";
				$checked = "";
				
				// zoeken ofdat er een uitbreiding is op deze klant
				$q_zoek_uit = mysqli_query($conn, "SELECT * FROM kal_customers WHERE uit_cus_id = " . $cus->cus_id ." AND cus_active='1'");
				
				if( mysqli_num_rows($q_zoek_uit) > 0 )
				{
					$zoek_uit = mysqli_fetch_object($q_zoek_uit);
					$cus2 = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $zoek_uit->cus_id));
					
					$stijl = "";
					$checked = " checked='checked' ";
				}
				
                if( $checked != "" )
                {
                    echo "<a name='goto_uitbreiding'>";    
                }
                
				echo "<input type='checkbox' ". $checked ." name='maak_uitbreiding' id='maak_uitbreiding' onclick='maakUitbreiding(this, ". $cus->cus_id .");' /><label for='maak_uitbreiding'> Klant wenst een uitbreiding</label>";
				
                if( $checked != "" )
                {
                    echo "</a>";    
                }
                
				echo "<div id='id_uitbreiding' ". $stijl .">";
				
				// BEGIN UITBREIDING
				echo "<table border='0' width='100%' class='main_table'>";
				echo "<tr>";
				echo "<td valign='top' width='50%'>";
				
				// begin eerste tabel
                echo "<fieldset>";
                echo "<legend>ACMA </legend>";
    			echo "<table border='0'>";
				
				if( ($_SESSION["kalender_user"]->group_id != 3) || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td width='190'>ACMA:</td>";
					echo "<td>";
                    
					if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='uit_acma' id='uit_acma' class='lengte'>";
						echo "<option value=''></option>";
				
						foreach( $acma_arr as $key => $acma )
						{
							if( $key == $cus2->cus_acma )
							{
								echo "<option selected='yes' value='". $key ."'>". $acma ."</option>";
							}else
							{
								echo "<option value='". $key ."'>". $acma ."</option>";
							}
						}
							
						echo "</select>";
					}else {
						echo $acma_arr[ $cus2->cus_acma ];
                        echo "<input type='hidden' name='uit_acma' id='uit_acma' value='". $cus2->cus_acma ."' /> ";
					}
				
					echo "</td>";
					echo "</tr>";
				}else
				{
					echo "<tr>";
					echo "<td>ACMA:</td>";
					echo "<td>". $acma_arr[ $cus2->cus_acma ];
					echo "<input type='hidden' name='uit_acma' id='uit_acma' value='". $cus2->cus_acma ."' /> ";
					echo "</td>";
					echo "</tr>";
				}
					
				if( $cus2->cus_offerte_datum == "0000-00-00" )
				{
					$cus2->cus_offerte_datum = "";
				}else
				{
					$datum = explode("-", $cus2->cus_offerte_datum);
					$cus2->cus_offerte_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}
				
				echo "<tr>";
				echo "<td>Datum offerte aanvraag:</td>";
				echo "<td>";
				
                if( $cus2->cus_offerte_datum == "--" )
                {
                    $cus2->cus_offerte_datum = "";
                }
                
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' name='uit_offerte_datum' id='uit_offerte_datum' class='lengte' value='".$cus2->cus_offerte_datum."' />";
					
				}else {
					echo $cus2->cus_offerte_datum;
                    echo "<input type='hidden' name='uit_offerte_datum' id='uit_offerte_datum' class='lengte' value='".$cus2->cus_offerte_datum."' />";
				}
					
				echo "</td>";
				echo "</tr>";
                echo "</table>";
                echo "</fieldset>";
                
                echo "<fieldset>";
                echo "<legend>Offerte</legend>";
				echo "<table border='0'>";
				//echo "<tr><td colspan='2'>&nbsp;</td></tr>";
				
				if( !empty( $cus2->cus_acma ) && !empty( $cus2->cus_offerte_datum ) )
				{
					if( $cus2->cus_contact == "0000-00-00" )
					{
						$cus2->cus_contact = "";
					}else
					{
						$datum = explode("-", $cus2->cus_contact);
						$cus2->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens' width='190'>Al gecontacteerd:</td>";
					echo "<td>";
				
					if( $_SESSION["kalender_user"]->user_id == $cus2->cus_acma && isset( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='text' class='lengte' name='uit_gecontacteerd' id='uit_gecontacteerd' value='". $cus2->cus_contact ."' />";
					}else {
						echo $cus2->cus_contact;
                        echo "<input type='hidden' class='lengte' name='uit_gecontacteerd' id='uit_gecontacteerd' value='". $cus2->cus_contact ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					if( $cus2->cus_offerte_gemaakt == "0000-00-00" )
					{
						$cus2->cus_offerte_gemaakt = "";
					}else
					{
						$datum = explode("-", $cus2->cus_offerte_gemaakt);
						$cus2->cus_offerte_gemaakt = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Offerte gemaakt:</td>";
					echo "<td>";
				
					if( $_SESSION["kalender_user"]->user_id == $cus2->cus_acma && isset( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='text' class='lengte' name='uit_offerte_gemaakt' id='uit_offerte_gemaakt' value='". $cus2->cus_offerte_gemaakt ."' />";
					}else {
						echo $cus2->cus_offerte_gemaakt;
                        echo "<input type='hidden' class='lengte' name='uit_offerte_gemaakt' id='uit_offerte_gemaakt' value='". $cus2->cus_offerte_gemaakt ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Offerte:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1  || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29)
					{
						echo "<input type='file' class='lengte' name='uit_offerte' id='uit_offerte' />";
					}
				
					echo "</td>";
					echo "</tr>";
					
					// zoeken of er offertes zijn
					$q_zoek_offerte = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus2->cus_id ."'
					                                  AND cf_soort = 'offerte' ");
					
					if( mysqli_num_rows($q_zoek_offerte) > 0 )
					{
						while( $offerte = mysqli_fetch_object($q_zoek_offerte) )
						{
							if( file_exists( "cus_docs/" . $cus2->cus_id . "/offerte/" . $offerte->cf_file ) )
							{
								echo "<tr><td align='right' valign='top' class='offerte_gegevens'>";
				
								if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
								{
									echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_offerte_del_". $offerte->cf_id ."' id='uit_offerte_del_". $offerte->cf_id ."' />";
								}
								
								echo "</td><td>";
								
								echo "<a href='cus_docs/" . $cus2->cus_id . "/offerte/" . $offerte->cf_file . "' target='_blank' >";
								echo $offerte->cf_file;
								echo "</a>";
								
								echo "</td>";
								echo "</tr>";
							}
						}
					}
					
					$offerte_datum1 = "";
					$offerte_datum2 = "";
					$offerte_datum3 = "";
				
					if( !empty( $cus2->cus_offerte_besproken ))
					{
						$tmp_off = explode('@', $cus2->cus_offerte_besproken );
				
						$offerte_datum1 = $tmp_off[0];
						$offerte_datum2 = $tmp_off[1];
						$offerte_datum3 = $tmp_off[2];
				
				
						if( $offerte_datum1 == "--" )
						{
							$offerte_datum1 = "";
						}
				
						if( $offerte_datum2 == "--" )
						{
							$offerte_datum2 = "";
						}
				
						if( $offerte_datum3 == "--" )
						{
							$offerte_datum3 = "";
						}
					}

					echo "<tr>";
					echo "<td valign='top' class='offerte_gegevens'>Offerte bespreking:</td>";
					echo "<td valign='top'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_offerte_besproken1' id='uit_offerte_besproken1' value='". $offerte_datum1 ."' /><br/>";
						echo "<input type='text' class='lengte' name='uit_offerte_besproken2' id='uit_offerte_besproken2' value='". $offerte_datum2 ."' /><br/>";
						echo "<input type='text' class='lengte' name='uit_offerte_besproken3' id='uit_offerte_besproken3' value='". $offerte_datum3 ."' />";
					}else {
						echo $offerte_datum1;
						echo "<br/>" . $offerte_datum2;
						echo "<br/>" . $offerte_datum3;
                        
                        echo "<input type='hidden' class='lengte' name='uit_offerte_besproken1' id='uit_offerte_besproken1' value='". $offerte_datum1 ."' /><br/>";
						echo "<input type='hidden' class='lengte' name='uit_offerte_besproken2' id='uit_offerte_besproken2' value='". $offerte_datum2 ."' /><br/>";
						echo "<input type='hidden' class='lengte' name='uit_offerte_besproken3' id='uit_offerte_besproken3' value='". $offerte_datum3 ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Aantal panelen:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_aant_panelen' id='uit_aant_panelen' value='". $cus2->cus_aant_panelen ."' onblur='checkConform();getPpwp();maakPrijs();' />";
					}else {
						echo $cus2->cus_aant_panelen;
                        echo "<input type='hidden' class='lengte' name='uit_aant_panelen' id='uit_aant_panelen' value='". $cus2->cus_aant_panelen ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Type panelen:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						$typep = array();
				
						$typep[] = "";
						$typep[] = "Gewone";
						$typep[] = "Zwarte";
							
						echo "<select name='uit_type_panelen' id='uit_type_panelen'>";
						foreach( $typep as $type )
						{
							if( $type == $cus2->cus_type_panelen )
							{
								echo "<option selected='yes' value='".$type."'>". ucfirst($type) ."</option>";
							}else
							{
								echo "<option value='".$type."'>". ucfirst($type) ."</option>";
							}
						}
						echo "</select>";
					}else {
						echo $cus2->cus_type_panelen;
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Vermogen/paneel:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_w_panelen' id='uit_w_panelen' value='". $cus2->cus_w_panelen ."' onblur='checkConform();maakPrijs();' />";
					}else {
						echo $cus2->cus_w_panelen;
                        echo "<input type='hidden' class='lengte' name='uit_w_panelen' id='uit_w_panelen' value='". $cus2->cus_w_panelen ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Merk panelen:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_merk_panelen' id='uit_merk_panelen' value='". $cus2->cus_merk_panelen ."' />";
					}else {
						echo $cus2->cus_merk_panelen;
                        echo "<input type='hidden' class='lengte' name='uit_merk_panelen' id='uit_merk_panelen' value='". $cus2->cus_merk_panelen ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Opbrengst factor:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
					    $stijl = "";
                        
                        if( $cus2->cus_kwhkwp == 0 || empty($cus2->cus_kwhkwp) )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border:2px solid red;' ";
                            
                            if( $cus2->cus_verkoop == 0 )
                            {
                                $aant_verplicht--;
                                $stijl = "";
                            }    
                        }
                        
                        
                        
						echo "<input ". $stijl ." type='text' class='lengte' name='uit_kwhkwp' id='uit_kwhkwp' value='". $cus2->cus_kwhkwp ."' />";
					}else {
						echo $cus2->cus_kwhkwp;
                        echo "<input type='hidden' class='lengte' name='uit_kwhkwp' id='uit_kwhkwp' value='". $cus2->cus_kwhkwp ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Hoek panelen met het zuiden:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_hoek_z' id='uit_hoek_z' value='". $cus2->cus_hoek_z ."' />";
					}else {
						echo $cus2->cus_hoek_z;
                        echo "<input type='hidden' class='lengte' name='uit_hoek_z' id='uit_hoek_z' value='". $cus2->cus_hoek_z ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Hoek van de panelen:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_hoek' id='uit_hoek' value='". $cus2->cus_hoek ."' />";
					}else {
						echo $cus2->cus_hoek;
                        echo "<input type='hidden' class='lengte' name='uit_hoek' id='uit_hoek' value='". $cus2->cus_hoek ."' />";
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Soort dak:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						?> <select name='uit_soort_dak' id='uit_soort_dak'
							onchange='getPpwp( this.value );' class='lengte'>
							<?php
						
							echo "<option value='0' >== Keuze ==</option>";
						
							foreach( $daksoorten as $key => $soort )
							{
								if( $cus2->cus_soort_dak == $key )
								{
									echo "<option selected='yes' value='". $key ."'>". $soort ."</option>";
								}else
								{
									echo "<option value='". $key ."'>". $soort ."</option>";
								}
							}
						
							?>
						</select> <?php 
				
					}else {
						echo $daksoorten[ $cus2->cus_soort_dak ];
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Prijs per Wp:</td>";
					echo "<td>";
				
					$db_dak[1] = "wp_plat";
					$db_dak[2] = "wp_plat";
					$db_dak[6] = "wp_plat";
					$db_dak[3] = "wp_leien";
					$db_dak[4] = "wp_schans";
					$db_dak[5] = "wp_schans";
                    $db_dak[7] = "wp_plat";
                    $db_dak[8] = "wp_leien";
                    $db_dak[9] = "wp_schans";
                    $db_dak[10] = "wp_plat";
				
					if( ( $cus2->cus_prijs_wp == "0" || empty($cus2->cus_prijs_wp) ) && $cus2->cus_soort_dak != 0 && !empty($cus2->cus_soort_dak) && $cus2->cus_aant_panelen != 0 && !empty($cus2->cus_aant_panelen) )
					{
						// zoeken naar de ppwp
						$waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT " . $db_dak[ $cus2->cus_soort_dak ] . " FROM kal_wp WHERE wp_start <= ".$cus2->cus_aant_panelen." AND wp_end >=" . $cus2->cus_aant_panelen));
							
						if( $cus2->cus_prijs_wp == 0 || empty( $cus2->cus_prijs_wp ) )
						{
							$cus2->cus_prijs_wp = $waarde->$db_dak[ $cus2->cus_soort_dak ];
						}
					}
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' onblur='maakPrijs();' name='uit_ppwp' id='uit_ppwp' value='". $cus2->cus_prijs_wp ."' />";
					}else {
						echo $cus2->cus_prijs_wp;
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Bedrag excl.:</td>";
					echo "<td>";
				
					if( $cus2->cus_bedrag_excl == 0 || $cus2->cus_bedrag_excl == "" )
					{
						$cus2->cus_bedrag_excl = (int)$cus2->cus_aant_panelen * (int)$cus2->cus_w_panelen * (float)$cus2->cus_prijs_wp;
					}
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_bedrag_excl' id='uit_bedrag_excl' value='". $cus2->cus_bedrag_excl ."' />";
					}else {
						echo $cus2->cus_bedrag_excl;
					}
				
					echo "</td>";
					echo "</tr>";
				
					$sel0 = "";
					$sel1 = "";
					$sel2 = "";
				
					switch( $cus2->cus_woning5j )
					{
						case '0':
							$sel0 = "selected='yes'";
							break;
						case '1' :
							$sel1 = "selected='yes'";
							break;
						case '2' :
							$sel2 = "selected='yes'";
							break;
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Woning ouder dan 5j:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
					    $stijl = "";
                        
                        if( $cus2->cus_woning5j == '2' )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border:2px solid red;' ";    
                        }
                       
						echo "<select ". $stijl ." name='uit_woning5j' id='uit_woning5j' onchange='berekenPrijs(this);' class='lengte'>";
						echo "<option value='2' ".$sel2." >== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						switch( $cus2->cus_woning5j )
						{
							case '0' :
								echo "Neen";
								break;
							case '1' :
								echo "Ja";
								break;
						}
					}
				
					echo "</td>";
					echo "</tr>";
				
					$sel0 = "";
					$sel1 = "";
					$sel2 = "";
				
					switch( $cus2->cus_opwoning )
					{
						case '0' :
							$sel0 = "selected='yes'";
							break;
						case '1' :
							$sel1 = "selected='yes'";
							break;
						case '2' :
							$sel2 = "selected='yes'";
							break;
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Panelen op woning:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='uit_opwoning' id='uit_opwoning'  class='lengte'>";
						echo "<option value='2' ".$sel2." >== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						switch( $cus2->cus_woning5j )
						{
							case '0' :
								echo "Neen";
								break;
							case '1' :
								echo "Ja";
								break;
						}
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Bedrag incl.:</td>";
					echo "<td><span id='id_bedrag_incl'>";
				
					switch( $cus2->cus_woning5j )
					{
						case '0' :
							echo "&euro; " . number_format( $cus2->cus_bedrag_excl*1.21, "2", ".", "");
							break;
						case '1' :
							if( !empty( $cus2->cus_btw ) )
							{
								echo "&euro; " . number_format( $cus2->cus_bedrag_excl*1.21, "2", ".", "");
							}else
							{
								echo "&euro; " . number_format( $cus2->cus_bedrag_excl*1.06, "2", ".", "");
							}
							break;
					}
				
					echo "</span></td>";
					echo "</tr>";
				
					$sel0 = "";
					$sel1 = "";
				
					if( $cus2->cus_driefasig == '0' )
					{
						$sel0 = "selected='yes'";
					}else
					{
						$sel1 = "selected='yes'";
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Driefasig aanwezig:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='uit_driefasig' id='uit_driefasig'>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						if( $cus2->cus_driefasig == '0' )
						{
							echo "Neen";
						}else
						{
							echo "Ja";
						}
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Driefasig noodzakelijk:</td>";
					echo "<td>";
					echo "<span id='driefase_noodzakelijk'>";
				
                    
				   if( $cus2->cus_aant_panelen < 25 )
                   {
                        echo "&nbsp;Neen";
                   }else
                   {
                        echo "&nbsp;<span class='error'>Ja</span>";
                   }
					
                    
					echo "</span>";
					echo "</td>";
					echo "</tr>";
				
					$sel0 = "";
					$sel1 = "";
				
					if( $cus2->cus_nzn == '0' )
					{
						$sel0 = "selected='yes'";
					}else
					{
						$sel1 = "selected='yes'";
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>NZN:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<select name='uit_nzn' id='uit_nzn'>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Ja</option>";
						echo "</select>";
					}else {
						if( $cus2->cus_nzn == '0' )
						{
							echo "Neen";
						}else
						{
							echo "Ja";
						}
					}
					echo "</td>";
					echo "</tr>";
				
					$sel0 = "";
					$sel1 = "";
					$sel2 = "";
				
					switch( $cus2->cus_verkoop )
					{
						case "0" :
							$sel0 = "selected='yes'";
							break;
						case "1" :
							$sel1 = "selected='yes'";
							break;
						case "2" :
							$sel2 = "selected='yes'";
							break;
					}
				
					echo "<tr>";
					echo "<td class='offerte_gegevens'>Overeenkomst:</td>";
					echo "<td>";
				
					?>
					<script>
					function checkVerkoop_uit( dit, verkoop, group_id )
					{
						if( (verkoop == "1" || verkoop == "2" || verkoop == "3" ) && dit.value != 1 )
						{
							if( group_id != 1 )
							{
								alert( "Gelieve contact op te nemen met Ismael om de verkoop terug op nee te zetten");
								var selObj = document.getElementById('uit_verkoop');
								selObj.options[2].selected = true;
							}
						}
					}
					
					</script>
					<?php 
					
					if( empty( $cus2->cus_verkoop ) )
					{
						$test_verkoop_uit = "-";
					}else
					{
						$test_verkoop_uit = $cus->cus_verkoop;
					}
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						//echo "<select name='uit_verkoop' id='uit_verkoop' onchange='viewTable2_uit(this);'>";
						echo "<select name='uit_verkoop' id='uit_verkoop' onchange='checkVerkoop_uit(this, \"". $test_verkoop_uit ."\", ". $_SESSION["kalender_user"]->group_id ." );viewTable2_uit(this);'>";
						echo "<option value=''>== Keuze ==</option>";
						echo "<option value='0' ".$sel0." >Neen</option>";
						echo "<option value='1' ".$sel1." >Verkoop</option>";
						echo "<option value='2' ".$sel2." >Verhuur</option>";
						echo "</select>";
					}else {
						
						switch ( $cus2->cus_verkoop )
						{
							case '0' :
							case '' :
								echo "Neen";
								break;
							case '1' :
								echo "ja, verkoop";
								break;
							case '2' :
								echo "ja, verhuur";
								break;
						}
					}
				
					echo "</td>";
					echo "</tr>";
				
					if( $cus2->cus_verkoop_datum == "0000-00-00" )
					{
						$cus2->cus_verkoop_datum = "";
					}else
					{
						$datum = explode("-", $cus2->cus_verkoop_datum);
						$cus2->cus_verkoop_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
                    
                    echo "<tr><td colspan='2'>";
					
					$uit_showhide4 = "";
					
					if( $cus2->cus_verkoop == "1" || $cus2->cus_verkoop == "2" )
					{
						$uit_showhide4 = "";
					}else
					{
						$uit_showhide4 = " style='display:none;' ";
					}

					echo "<table width='100%' ". $uit_showhide4 ." id='uit_showhide4' cellpadding='0' cellspacing='0' >";
					echo "<tr>";
					echo "<td class='offerte_gegevens' width='50%' >Datum overeenkomst:</td>";
					echo "<td>";

					if( $cus2->cus_verkoop_datum == "0000-00-00" || $cus2->cus_verkoop_datum == "" )
					{
						$cus2->cus_verkoop_datum = "";
					}else
					{
						$datum = explode("-", $cus2->cus_verkoop_datum);
						$cus2->cus_verkoop_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
					
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_verkoop_datum' id='uit_verkoop_datum' value='". $cus2->cus_verkoop_datum ."' />";
					}else {
						echo $cus2->cus_verkoop_datum;
					}

					echo "</td>";
					echo "</tr>";
					echo "</table>";
					
					echo "</span>";
					echo "</td></tr>";
                    
				    $stijl = "";
					if( $cus2->cus_verkoop != "0" )
					{
						$stijl = "style='display:none;'";
					}
				
					echo "<tr>";
					echo "<td colspan='2' >";
					echo "<table border='0' width='100%' id='uit_tabel3' ". $stijl ."  cellpadding='0' cellspacing='0'>";
					echo "<tr>";
					echo "<td width='50%' class='offerte_gegevens'>Reden:</td>";
					echo "<td width='50%'>";
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_reden' id='uit_reden' value='". $cus2->cus_reden ."' />";
					}else {
						echo $cus2->cus_reden;
					}
				
					echo "</td>";
					echo "</tr>";
					echo "</table>";
				
					echo "</td>";
					echo "</tr>";
				    echo "</table>";
                    echo "</fieldset>";
                    
                    $uit_showhide3 = "";
					
					if( $cus2->cus_verkoop == '1' || $cus2->cus_verkoop == '2')
					{
						$uit_showhide3 = "";
					}else
					{
						$uit_showhide3 = " style='display:none;' ";
					}
					
					if( $cus->cus_oa == '1' )
					{
						$uit_showhide3 = "";
					}
					
					echo "<fieldset id='uit_showhide3' ". $uit_showhide3 ." >";
                    echo "<legend>Installatie</legend>";
                    
                    echo "<table border='0' width='100%'>";
					echo "<tr>";
					echo "<td width='190'>Opmetingsdatum:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_opmeting_datum' id='uit_opmeting_datum' value='". $cus2->cus_opmeting_datum ."' />";
					}else {
						echo $cus2->cus_opmeting_datum;
					}
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td>Opmeting door:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						//echo "<input type='text' class='lengte' name='opmeting_datum' id='opmeting_datum' value='". $cus2->cus_opmeting_datum ."' />";
						echo "<input type='text' class='lengte' name='uit_opmeting_door' id='uit_opmeting_door' value='". $cus2->cus_opmeting_door ."' />";
					}else {
						echo $cus2->cus_opmeting_door;
					}
				
					echo "</td>";
					echo "</tr>";
                    
                    echo "<tr>";
					echo "<td>Opmetingsdocument:</td>";
					echo "<td>";
						
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<span id='id_off48'>";
						echo "<input type='file' class='lengte' name='uit_doc_opmeting' id='uit_doc_opmeting' />";
						echo "</span>";
					}
					
					echo "</td>";
					echo "</tr>";
	
					if( !empty( $cus2->cus_opmetingdoc_filename ) )
					{
						echo "<tr><td align='right'>";
	
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_opmetingdoc_del' id='uit_opmetingdoc_del' />";
						}
	
						echo "</td><td>";
	
						if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_opmeting/" . $cus2->cus_opmetingdoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_opmeting/" . $cus2->cus_opmetingdoc_filename . "' target='_blank' >";
							echo $cus2->cus_opmetingdoc_filename;
							echo "</a>";
						}
	
						echo "</td></tr>";
					}
                    
					if( $cus2->cus_installatie_datum == "0000-00-00" )
					{
						$cus2->cus_installatie_datum = "";
					}else
					{
						$datum = explode("-", $cus2->cus_installatie_datum);
						$cus2->cus_installatie_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
				
					echo "<tr>";
					echo "<td>Installatiedatum 1:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_installatie_datum' id='uit_installatie_datum' value='". $cus2->cus_installatie_datum ."' />";
					}else {
						echo $cus2->cus_installatie_datum;
					}
				
					echo "</td>";
					echo "</tr>";
                    
                    if( $cus2->cus_installatie_datum2 == "0000-00-00" )
					{
						$cus2->cus_installatie_datum2 = "";
					}else
					{
						$datum = explode("-", $cus2->cus_installatie_datum2);
						$cus2->cus_installatie_datum2 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
                    
                    echo "<tr>";
					echo "<td>Installatiedatum 2:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_installatie_datum2' id='uit_installatie_datum2' value='". $cus2->cus_installatie_datum2 ."' />";
					}else {
						echo $cus2->cus_installatie_datum2;
					}
				
					echo "</td>";
					echo "</tr>";
                    
                    if( $cus2->cus_installatie_datum3 == "0000-00-00" )
					{
						$cus2->cus_installatie_datum3 = "";
					}else
					{
						$datum = explode("-", $cus2->cus_installatie_datum3);
						$cus2->cus_installatie_datum3 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
                    
                    echo "<tr>";
					echo "<td>Installatiedatum 3:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_installatie_datum3' id='uit_installatie_datum3' value='". $cus2->cus_installatie_datum3 ."' />";
					}else {
						echo $cus2->cus_installatie_datum3;
					}
				
					echo "</td>";
					echo "</tr>";
                    
                    if( $cus2->cus_installatie_datum4 == "0000-00-00" )
					{
						$cus2->cus_installatie_datum4 = "";
					}else
					{
						$datum = explode("-", $cus2->cus_installatie_datum4);
						$cus2->cus_installatie_datum4 = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
                    
                    echo "<tr>";
					echo "<td>Installatiedatum 4:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_installatie_datum4' id='uit_installatie_datum4' value='". $cus2->cus_installatie_datum4 ."' />";
					}else {
						echo $cus2->cus_installatie_datum4;
					}
				
					echo "</td>";
					echo "</tr>";
                    
                    if( $cus2->cus_nw_installatie_datum == "0000-00-00" )
					{
						$cus2->cus_nw_installatie_datum = "";
					}else
					{
						$datum = explode("-", $cus2->cus_nw_installatie_datum);
						$cus2->cus_nw_installatie_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
					
					echo "<tr>";
					echo "<td>Nieuwe Installatiedatum:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_nw_installatie_datum' id='uit_nw_installatie_datum' value='". $cus2->cus_nw_installatie_datum ."' />";
					}else {
						echo $cus2->cus_nw_installatie_datum;
					}

					echo "</td>";
					echo "</tr>";
                    
                    if( $cus2->cus_aanp_datum == "0000-00-00" )
					{
						$cus2->cus_aanp_datum = "";
					}else
					{
						$datum = explode("-", $cus2->cus_aanp_datum);
						$cus2->cus_aanp_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
					}
                    
 					echo "<tr>";
					echo "<td>Installatie aanpassen:</td>";
					echo "<td>";

					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input type='text' class='lengte' name='uit_installatie_aanp' id='uit_installatie_aanp' value='". $cus2->cus_aanp_datum ."' />";
					}else {
						echo $cus2->cus_aanp_datum;
					}

					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td>Installatieploeg:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<input title='Voor en achternaam ingeven aub' type='text' class='lengte' name='uit_installatie_ploeg' id='uit_installatie_ploeg' value='". $cus2->cus_installatie_ploeg ."' />";
					}else {
						echo $cus2->cus_installatie_ploeg;
					}
				
					echo "</td>";
					echo "</tr>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "<tr>";
						echo "<td width='190'>Stringopmetingsrapport: </td>";
						echo "<td>";
						echo "<input type='file' class='lengte' name='uit_doc_string' id='uit_doc_string' />";
						echo "</td>";
						echo "</tr>";
							
						if( !empty( $cus2->cus_stringdoc_filename) )
						{
							echo "<tr><td align='right'>";
				
							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
							{
								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_stringdoc_del' id='uit_stringdoc_del' />";
							}
				
							echo "</td><td>";
				
							if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_string/" . $cus2->cus_stringdoc_filename ) )
							{
								echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_string/" . $cus2->cus_stringdoc_filename . "' target='_blank' >";
								echo $cus2->cus_stringdoc_filename ;
								echo "</a>";
							}
				
							echo "</td></tr>";
						}
					}
				
					echo "<tr>";
					echo "<td>Elektrische bekabeling door:</td>";
					echo "<td>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						$sel0 = "";
						$sel1 = "";
						$sel2 = "";
							
						switch( $cus2->cus_elec )
						{
							case '2' :
								$sel2 = "selected='yes'";
								break;
							case '0' :
								$sel0 = "selected='yes'";
								break;
							case '1' :
								$sel1 = "selected='yes'";
								break;
						}
							
						echo "<select name='uit_elec' id='uit_elec' class='lengte' onchange='toonElec_uit(this);'>";
						echo "<option value='2' ". $sel2 .">== Keuze == </option>";
						echo "<option value='0' ". $sel0 .">Dezelfde ploeg </option>";
						echo "<option value='1' ". $sel1 .">Andere: </option>";
						echo "</select>";
					}else {
						switch( $cus2->cus_elec )
						{
							case '2' :
								echo "";
								break;
							case '0' :
								echo "Dezelfde ploeg";
								break;
							case '1' :
								echo "Andere :";
								break;
						}
					}
				
					echo "</td>";
					echo "</tr>";
				
					$elec_stijl = "style='display:none'";
				
					if( $cus2->cus_elec == 1 )
					{
						$elec_stijl = "";
					}
				
					echo "<tr>";
					echo "<td><span id='uit_elec1' ". $elec_stijl .">Door: </span></td>";
					echo "<td><span id='uit_elec2' ". $elec_stijl .">";
					echo "<input type='text' class='lengte' name='uit_elec_door' id='uit_elec_door' value='". $cus2->cus_elec_door ."' />";
					echo "</span></td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td><span id='uit_elec3' ". $elec_stijl .">Datum:</span></td>";
					echo "<td><span id='uit_elec4' ". $elec_stijl ." >";
					echo "<input type='text' class='lengte' name='uit_elec_datum' id='uit_elec_datum' value='". $cus2->cus_elec_datum ."' />";
					echo "</span></td>";
					echo "</tr>";
                    
                    // zoeken of er fotos zijn
					$q_zoek_offerte = mysqli_query($conn, "SELECT * 
					                                 FROM kal_customers_files
					                                WHERE cf_cus_id = '". $cus2->cus_id ."'
					                                  AND cf_soort = 'foto' ");
					
					if( mysqli_num_rows($q_zoek_offerte) > 0 )
					{
						$i=0;
						
						while( $offerte = mysqli_fetch_object($q_zoek_offerte) )
						{
							if( file_exists( "cus_docs/" . $cus2->cus_id . "/foto/" . $offerte->cf_file ) )
							{
								echo "<tr><td align='left' valign='top'>";
								
								if( $i == 0 )
								{
									echo "Foto's";
								}
								
								$i++;
								
								echo "</td><td>";
								
								echo "<a href='cus_docs/" . $cus2->cus_id . "/foto/" . $offerte->cf_file . "' target='_blank' title='Klik op de foto voor een grotere weergave.' >";
								echo $offerte->cf_file;
								echo "</a>";
								
								echo "</td>";
								echo "</tr>";
							}
						}
					}
				}
				
                
                
				echo "</table>";
				echo "</fieldset>";
                
                
				echo "</td>";
				echo "<td valign='top' width='50%'>";
				// begin tabel 2
				
				echo "<table width='100%' >";
				echo "<tr>";
				echo "<td colspan='2' >";
				echo "<a id='various5' class='verkoop_gegevens' href='klanten_tel.php?klantid=".$cus2->cus_id."'>Telefonische opmerkingen</a>";
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td colspan='2' class='verkoop_gegevens'>";
				echo "Opmerkingen:<br/>";
				
				if( ( $_SESSION["kalender_user"]->user_id == $cus2->cus_acma && isset( $cus2->cus_acma ) ) || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<textarea rows='5' cols='60' name='uit_opmerkingen' id='uit_opmerkingen' >". $cus2->cus_opmerkingen ."</textarea>";
				}else {
					echo $cus2->cus_opmerkingen;
				}
				
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				
				$stijl = "style='display:none;'";
				
				if( $cus2->cus_verkoop == '1' || $cus2->cus_verkoop == '2')
				{
					$stijl = "style='display:block;'";
				}
				
                echo "<fieldset id='uit_tabel2' ". $stijl ." >";
                echo "<legend>Facturatie</legend>"; 
				echo "<table width='100%'  border='0' >";
                
                if( $cus2->cus_verkoop == '1' )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='verkoop1'>Verkoopsbedrag incl.:</span></td>";
					echo "<td>";
					echo "<span id='verkoop2'>";	
					
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						if( $cus2->cus_verkoopsbedrag_incl == 0 || $cus2->cus_verkoopsbedrag_incl == "" || $_SESSION["kalender_user"]->group_id == 1 )
						{
							echo "<input type='text' class='lengte' name='uit_verkoopsbedrag_incl' id='uit_verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";
						}else
						{
							echo "<span title='Eenmaal dat de prijs is ingevuld, kan deze enkel door het management worden gewijzigd.'>" . $cus2->cus_verkoopsbedrag_incl . "</span>";
							echo "<input type='hidden' name='verkoopsbedrag_incl' id='verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";	
						}
					}else {
						echo $cus2->cus_verkoopsbedrag_incl;
						echo "<input type='hidden' name='uit_verkoopsbedrag_incl' id='uit_verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";
					}
					
					echo "</span>";
					echo "</td>";
					echo "</tr>";
				}
                
                if( $cus2->cus_verkoop == '2' )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens' ><span id='uit_verhuur1' >Huur per paneel : </span> </td>";
					echo "<td>";
					echo "<span id='uit_verhuur2' >";
					
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						if( $cus2->cus_verkoopsbedrag_incl == 0 || $cus2->cus_verkoopsbedrag_incl == "" || $_SESSION["kalender_user"]->group_id == 1 )
						{
							echo "<input type='text' class='lengte' name='uit_verkoopsbedrag_incl' id='uit_verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";
						}else
						{
							echo "<span title='Eenmaal dat de prijs is ingevuld, kan deze enkel door het management worden gewijzigd.'>" . $cus2->cus_verkoopsbedrag_incl . "</span>";
							echo "<input type='hidden' name='uit_verkoopsbedrag_incl' id='uit_verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";	
						}
					}else {
						echo $cus2->cus_verkoopsbedrag_incl;
						echo "<input type='hidden' name='uit_verkoopsbedrag_incl' id='uit_verkoopsbedrag_incl' value='". $cus2->cus_verkoopsbedrag_incl ."' />";
					}
					echo "</span>";
					echo "</td>";
					echo "</tr>";
					
					if( $cus2->cus_ont_huur == "" || $cus2->cus_ont_huur == 0.00 )
					{
						$cus2->cus_ont_huur = $cus2->cus_werk_aant_panelen * $cus2->cus_verkoopsbedrag_incl;
					}
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='uit_verhuur3'> Tot. ont. huur per maand:</span></td>";
					echo "<td><span id='uit_verhuur4'>";  
					
                    if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
                        $stijl = "";
                        
                        if( $cus2->cus_ont_huur == 0 ||empty($cus2->cus_ont_huur) )
                        {
                            $aant_verplicht++;
                            $stijl = " style='border:2px solid red;' ";    
                        }
                        
                        echo "<input ". $stijl ." type='text' class='lengte' name='uit_ont_huur' id='uit_ont_huur' value='". $cus2->cus_ont_huur ."' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);'/>";
                    }else
                    {
                        echo number_format($cus2->cus_ont_huur, 2, ",", "");
                        echo "<input type='hidden' name='uit_ont_huur' id='uit_ont_huur' value='". $cus2->cus_ont_huur ."' />";
                    }
					echo "</span></td>";
					echo "</tr>";
					
                    if( $cus2->cus_bet_huur == 0.00 )
                    {
                        $cus2->cus_bet_huur = 0;
                    }
                    
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='uit_verhuur5'> Tot. te betalen huur per maand:</span></td>";
					echo "<td><span id='uit_verhuur6'>";
                    if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
		                  echo "<input type='text' class='lengte' name='uit_bet_huur' id='uit_bet_huur' value='". $cus2->cus_bet_huur ."' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);'/>";
                    }else
                    {
                        echo number_format( $cus2->cus_bet_huur, 2, ",", "");
                        echo "<input type='hidden' name='uit_bet_huur' id='uit_bet_huur' value='". $cus2->cus_bet_huur ."' />";
                    }
					
					echo "</span></td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'><span id='uit_verhuur9'> Looptijd huurcontr. : </span></td>";
					echo "<td><span id='uit_verhuur10'>";
                    
                    if( $cus2->cus_looptijd_huur == 0 )
                    {
                        $cus2->cus_looptijd_huur = 240;
                    }
                    
                    $huur_jaar = 0;
                    $huur_maand = 0;
                    
                    if( $cus2->cus_looptijd_huur > 0 )
                    {
                        $huur_maand = $cus2->cus_looptijd_huur % 12;
                        $huur_jaar = explode(".", $cus2->cus_looptijd_huur / 12 );
                        $huur_jaar = $huur_jaar[0];
                    }
                    
                    if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
                        echo "<select name='uit_looptijd_jaar' id='uit_looptijd_jaar'>";
                        
                        for( $i=0;$i<=50;$i++ )
                        {
                            if( $huur_jaar == $i )
                            {
                                echo "<option selected='selected' value='".$i."'>". $i ."</option>";
                            }else
                            {
                                echo "<option value='".$i."'>". $i ."</option>";    
                            }
                        }
                        
                        echo "</select>&nbsp;jaar&nbsp;&nbsp;&nbsp;";
                        
                        echo "<select name='uit_looptijd_maand' id='uit_looptijd_maand'>";
                        
                        for( $i=0;$i<=12;$i++ )
                        {
                            if( $huur_maand == $i )
                            {
                                echo "<option selected='selected' value='".$i."'>". $i ."</option>";
                            }else
                            {
                                echo "<option value='".$i."'>". $i ."</option>";    
                            }
                        }
                        
                        echo "</select>&nbsp;maanden";
                    }else
                    {
                        echo $huur_jaar . " jaar, " . $huur_maand . " maanden";
                        echo "<input type='hidden' name='uit_looptijd_jaar' id='uit_looptijd_jaar' value='". $huur_jaar ."' />";
                        echo "<input type='hidden' name='uit_looptijd_maand' id='uit_looptijd_maand' value='". $huur_maand ."' />";
                    }
                    
					//echo "<input type='text' class='lengte' name='looptijd_huur' id='looptijd_huur' value='". $cus->cus_looptijd_huur ."' onkeypress='return isNumberKey(event);' />";
					
                    echo "</span></td>";
					echo "</tr>";
                    
                    /* begin hypo */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='uit_verhuur11'>Hypotheekvrijgave:</span></td>";
    				echo "<td><span id='uit_verhuur12'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='uit_hypotheek' id='uit_hypotheek' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus2->cus_id ."'
    				                                  AND cf_soort = 'hypotheek' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus2->cus_id . "/hypotheek/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_hypotheek_del_". $orderbon->cf_id ."' id='uit_hypotheek_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus2->cus_id . "/hypotheek/" . $orderbon->cf_file . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                    /* einde hypo */
                    
                    /* begin eigendomsacte */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'>Eigendomsakte:</td>";
    				echo "<td>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
                        echo "<input type='file' name='uit_eigendom' id='uit_eigendom' />";
    				}
    				echo "</td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus2->cus_id ."'
    				                                  AND cf_soort = 'eigendom' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus2->cus_id . "/eigendom/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_eigendom_del_". $orderbon->cf_id ."' id='uit_eigendom_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus2->cus_id . "/eigendom/" . $orderbon->cf_file . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                    /* einde eigendomsacte */
                    
                    /* begin isolatievw */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='uit_verhuur13'>Isolatiedoc. :</span></td>";
    				echo "<td><span id='uit_verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='uit_isolatie' id='uit_isolatie' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus2->cus_id ."'
    				                                  AND cf_soort = 'isolatie' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus2->cus_id . "/isolatie/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_isolatie_del_". $orderbon->cf_id ."' id='uit_isolatie_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus2->cus_id . "/isolatie/" . $orderbon->cf_file . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                    /* einde isolatievw */
                    
                    /* begin loonfiche */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='uit_verhuur13'>Loonfiche :</span></td>";
    				echo "<td><span id='uit_verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='uit_loonfiche' id='uit_loonfiche' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus2->cus_id ."'
    				                                  AND cf_soort = 'loonfiche' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus2->cus_id . "/loonfiche/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_loonfiche_del_". $orderbon->cf_id ."' id='uit_loonfiche_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus2->cus_id . "/loonfiche/" . $orderbon->cf_file . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                    /* einde loonfiche */
                    
                    /* begin vol_off */
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='uit_verhuur13'>Volledige offerte :</span></td>";
    				echo "<td><span id='uit_verhuur14'>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='uit_vol_off' id='uit_vol_off' />";
    				}
    				echo "</span></td>";
    				echo "</tr>";
    
    				echo "<tr><td colspan='2'><span id='id_off61'>";
    				// zoeken of er offertes zijn
    				$q_zoek_orderbon = mysqli_query($conn, "SELECT * 
    				                                 FROM kal_customers_files
    				                                WHERE cf_cus_id = '". $cus2->cus_id ."'
    				                                  AND cf_soort = 'vol_off' ");
    				
    				if( mysqli_num_rows($q_zoek_orderbon) > 0 )
    				{
    					echo "<table width='100%'>";
    					while( $orderbon = mysqli_fetch_object($q_zoek_orderbon) )
    					{
    						if( file_exists( "cus_docs/" . $cus2->cus_id . "/vol_off/" . $orderbon->cf_file ) )
    						{
    							echo "<tr><td align='right' valign='top' class='verkoop_gegevens'>";
    
    							if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    							{
    								echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_vol_off_del_". $orderbon->cf_id ."' id='uit_vol_off_del_". $orderbon->cf_id ."' />";
    							}
    							
    							echo "</td><td>";
    							
    							echo "<a href='cus_docs/" . $cus2->cus_id . "/vol_off/" . $orderbon->cf_file . "' target='_blank' >";
    							echo $orderbon->cf_file;
    							echo "</a>";
    							
    							echo "</td>";
    							echo "</tr>";
    						}
    					}
    					echo "</table>";
    				}
    				
    				echo "</span></td></tr>";
                    /* einde vol_off */
                    
                    echo "<tr>";
    				echo "<td class='verkoop_gegevens'><span id='id_off55'>Huurdocs. volledig:</span></td>";
    				echo "<td align='left'><span id='id_off56'>";
    					
    				if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
    				{
    					if( $cus2->cus_huur_doc == "0" )
    					{
    						$huurdoc = "";
    					}else
    					{
    						$huurdoc = "checked='checked'";
    					}
    
    					echo "<input type='checkbox' ". $huurdoc ." name='uit_huur_doc' id='uit_huur_doc' />";
    				}else {
    				    
    					if( $cus2->cus_huur_doc == "0" )
    					{
    						$huurdoc = "Nee";
                            $huurdoc_chk = "";
    					}else
    					{
    						$huurdoc = "Ja";
                            $huurdoc_chk = "checked='checked'";
    					}
    
    					echo $huurdoc;
                        echo "<input type='hidden' name='uit_huur_doc' id='uit_huur_doc' value='1' />";
    				}
    					
    				echo "</span></td>";
    				echo "</tr>";
				}
                
				echo "<tr>";
				
                if( $cus->cus_verkoop == '1' )
				{
                    echo "<td class='verkoop_gegevens'>Datum orderbon:</td>";
                }else
                {
                    echo "<td class='verkoop_gegevens'>Datum offerte:</td>";
                }
                
                echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_datum_orderbon' id='uit_datum_orderbon' value='". $cus2->cus_datum_orderbon ."' />";
				}else {
					echo $cus2->cus_datum_orderbon;
				}
					
				echo "</td>";
				echo "</tr>";
                
				if( $cus2->cus_verkoop == '1' )
				{
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'>Sunny Beam:</td>";
    				echo "<td align='left'>";
    					
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					if( $cus2->cus_sunnybeam == "0" )
    					{
    						$sunny = "";
    					}else
    					{
    						$sunny = "checked='checked'";
    					}
    					echo "<input type='checkbox' ". $sunny ." name='uit_sunnybeam' id='uit_sunnybeam' />";
    				}else 
    				{
    					if( $cus2->cus_sunnybeam == "0" )
    					{
    						$sunnyb = "Nee";
    					}else
    					{
    						$sunnyb = "Ja";
    					}
    					echo $sunnyb;
    				}
    					
    				echo "</td>";
    				echo "</tr>";
    				
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'>Actie:</td>";
    				echo "<td>";
    					
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='text' class='lengte' name='uit_actie' id='uit_actie' value='". $cus2->cus_actie ."' />";
    				}else {
    					echo $cus2->cus_actie;
    				}
    				echo "</td>";
    				echo "</tr>";
    				
    				echo "<tr>";
    				echo "<td class='verkoop_gegevens'>Orderbon:</td>";
    				echo "<td>";
    				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
    				{
    					echo "<input type='file' name='uit_orderbon' id='uit_orderbon' />";
    				}
    				echo "</td>";
    				echo "</tr>";
                }
				
				if( !empty( $cus2->cus_order_file ) && !empty( $cus2->cus_order_filename  ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_order_del' id='uit_order_del' />";
					}
				
					echo "</td><td>";
				
					// orderbon
					if( file_exists( "cus_docs/" . $cus2->cus_id . "/orderbon/" . $cus2->cus_order_filename ) )
					{
						echo "<a href='cus_docs/" . $cus2->cus_id . "/orderbon/" . $cus2->cus_order_filename . "' target='_blank' >";
						echo $cus2->cus_order_filename;
						echo "</a>";
					}else
					{
						echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=order_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo $cus2->cus_order_filename ;
						echo "</a>";
					}
				
					echo "</td></tr>";
				}else{
					if( !empty( $cus2->cus_order_filename) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";
				
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_order_del' id='uit_order_del' />";
						}
				
						echo "</td><td>";
				
						if( file_exists( "cus_docs/" . $cus2->cus_id . "/orderbon/" . $cus2->cus_order_filename ) )
						{
							echo "<a href='cus_docs/" . $cus2->cus_id . "/orderbon/" . $cus2->cus_order_filename . "' target='_blank' >";
							echo $cus2->cus_order_filename;
							echo "</a>";
						}else
						{
							echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=order_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
							echo $cus2->cus_order_filename ;
							echo "</a>";
						}
				
						echo "</td></tr>";
					}
				}
				
                echo "<tr>";
				echo "<td class='verkoop_gegevens'><span id='id_off62'>Ingetekend door: </span></td>";
				echo "<td><span id='id_off63'>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='uit_ingetekend' id='uit_ingetekend' class='lengte' >";
					echo "<option value=''>== Keuze ==</option>";
					
                    foreach( $active_users as $user_id => $naam )
                    {
                        if( $cus2->cus_ingetekend == $naam["voornaam"] )
						{
							echo "<option selected='selected' value='". $naam["voornaam"] ."'>". $naam["fullname"] ."</option>";
						}else
						{
							echo "<option value='". $naam["voornaam"] ."'>". $naam["fullname"] ."</option>";	
						}
                    }
                    
                    
                    /*
					$q_users = mysqli_query($conn, "SELECT * FROM kal_users ORDER BY voornaam");
					while( $us = mysqli_fetch_object($q_users) )
					{
						if( $cus2->cus_ingetekend == $us->voornaam )
						{
							echo "<option selected='selected' value='". $us->voornaam ."'>". $us->voornaam . " " . $us->naam ."</option>";
						}else
						{
							echo "<option value='". $us->voornaam ."'>". $us->voornaam . " " . $us->naam ."</option>";	
						}
					}
					*/
					echo "</select>";
					
				}else {
					echo $cus2->cus_ingetekend;
				}

				echo "</span></td>";
				echo "</tr>";
                
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Aantal panelen: </td>";
				echo "<td>";
					
				if( ($cus2->cus_werk_aant_panelen == 0 || empty($cus2->cus_werk_aant_panelen) ) && ( $cus2->cus_aant_panelen != 0 || !empty($cus2->cus_aant_panelen) ) )
				{
					$cus2->cus_werk_aant_panelen = $cus2->cus_aant_panelen;
				}
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                        
                    if( ($cus2->cus_werk_aant_panelen == 0 || empty($cus2->cus_werk_aant_panelen)) && $cus2->cus_verkoop != ''  )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ". $stijl ." type='text' class='lengte' name='uit_werk_aant_panelen' id='uit_werk_aant_panelen' value='". $cus2->cus_werk_aant_panelen ."' onblur='checkConform();' />";
				}else {
					echo $cus2->cus_werk_aant_panelen;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Vermogen/paneel: </td>";
				echo "<td>";
				
				if( ($cus2->cus_werk_w_panelen == 0 || empty($cus2->cus_werk_w_panelen) ) && ( $cus2->cus_w_panelen != 0 || !empty($cus2->cus_w_panelen) ) )
				{
					$cus2->cus_werk_w_panelen = $cus2->cus_w_panelen;
				}
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->user_id == 29 )
				{
				    $stijl = "";
                        
                    if( ($cus2->cus_werk_w_panelen == 0 || empty($cus2->cus_werk_w_panelen)) && $cus2->cus_verkoop != '' )
                    {
                        $aant_verplicht++;
                        $stijl = " style='border:2px solid red;' ";    
                    }
                    
					echo "<input ". $stijl ." type='text' class='lengte' name='uit_werk_w_panelen' id='uit_werk_w_panelen' value='". $cus2->cus_werk_w_panelen ."' onblur='checkConform();' />";
				}else {
					echo $cus2->cus_werk_w_panelen;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Merk panelen: </td>";
				echo "<td>";
					
				if( (empty($cus2->cus_werk_merk_panelen) ) && ( !empty($cus2->cus_merk_panelen) ) )
				{
					$cus2->cus_werk_merk_panelen = $cus2->cus_merk_panelen;
				}
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_werk_merk_panelen' id='uit_werk_merk_panelen' value='". $cus2->cus_werk_merk_panelen ."' />";
				}else {
					echo $cus2->cus_werk_merk_panelen;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Aantal omvormers: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_werk_aant_omvormers' id='uit_werk_aant_omvormers' value='". $cus2->cus_werk_aant_omvormers ."' />";
				}else {
					echo $cus2->cus_werk_aant_omvormers;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td valign='top' class='verkoop_gegevens'>Type omvormer: </td>";
				echo "<td>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					if( $cus2->cus_type_omvormers == "" )
					{
						echo "<select name='uit_werk_omvormers[]' id='uit_werk_omvormers[]' class='lengte' >";
						echo "<option value='0'>== Keuze ==</option>";
				
						foreach( $list_inv as $key => $inv )
						{
							echo "<option value='". $key ."'>". $inv ."</option>";
						}
				
						echo "</select>";
					}else {
						if( !stristr( $cus2->cus_type_omvormers, '@') )
						{
							// 1 waarde in het veld
							echo "<select name='uit_werk_omvormers[]' id='uit_werk_omvormers[]' class='lengte' >";
							echo "<option value='0'>== Keuze ==</option>";
							foreach( $list_inv as $key => $inv )
							{
								if( $key == $cus2->cus_type_omvormers )
								{
									echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
								}else
								{
									echo "<option value='". $key ."'>". $inv ."</option>";
								}
							}
							echo "</select>";
						}else
						{
							// meerdere waardes gevonden
							$keuzes = explode('@', $cus2->cus_type_omvormers );
				
							$aant_keuze = count($keuzes);
				
							$i=0;
							foreach( $keuzes as $keuze )
							{
								$i++;
								echo "<select name='uit_werk_omvormers[]' id='uit_werk_omvormers[]' class='lengte' >";
								echo "<option value='0'>== Keuze ==</option>";
								foreach( $list_inv as $key => $inv )
								{
									if( $key == $keuze )
									{
										echo "<option selected='yes' value='". $key ."'>". $inv ."</option>";
									}else
									{
										echo "<option value='". $key ."'>". $inv ."</option>";
									}
								}
								echo "</select>";
				
								if( $i < $aant_keuze )
								{
									echo "<br/>";
								}
							}
						}
					}
				
					echo "&nbsp;";
					echo "<b><a onclick='getInverters_uit();' style='cursor:pointer;' >+</a></b>";
					echo "<span id='uit_extra_inverters'></span>";
				}else 
				{
					if( !stristr( $cus2->cus_type_omvormers, '@') )
					{
						// 1 waarde in het veld
						foreach( $list_inv as $key => $inv )
						{
							if( $key == $cus2->cus_type_omvormers )
							{
								echo $inv;
							}
						}
					}else
					{
						// meerdere waardes gevonden
						$keuzes = explode('@', $cus2->cus_type_omvormers );
				
						$aant_keuze = count($keuzes);
				
						$i=0;
						foreach( $keuzes as $keuze )
						{
							$i++;
							foreach( $list_inv as $key => $inv )
							{
								if( $key == $keuze )
								{
									echo $inv;
								}
							}
						}
					}
				}
				
				echo "</td>";
				echo "</tr>";
				
                // begin weergeven van de omvormers sn
                if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					if( !empty( $cus2->cus_type_omvormers ) )
					{
						$omv_keuzes = explode('@', $cus2->cus_type_omvormers );
						
						if( count( $omv_keuzes ) == 1 )
						{
							echo "<tr>";
							echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$cus2->cus_type_omvormers] . " :</td>";
							echo "<td>";
							
							$waarde = "";
							$q_zoek_waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_omvormers WHERE co_cus_id = '". $cus2->cus_id ."' AND co_omvormer = '". $cus2->cus_type_omvormers ."'"));
							
							if( !empty( $q_zoek_waarde->co_sn ) )
							{
								$waarde = $q_zoek_waarde->co_sn;
							} 
							
							echo "<input type='text' class='lengte' name='uit_sn1' id='uit_sn1' value='". $waarde ."' />";
							echo "<input type='hidden' name='uit_omv1' id='uit_omv1' value='".$cus2->cus_type_omvormers."' />";
							
							echo "</td>";
							echo "</tr>";
                            
                            echo "<tr>";
							echo "<td  class='verkoop_gegevens'>Omv. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";
							echo "<td>";
							
							echo "<input type='text' class='lengte' name='uit_text1' id='uit_text1' value='". $keuze_omv->co_text ."' />";
							
							echo "</td>";
							echo "</tr>";
							
                            echo "<input type='hidden' name='uit_aantal_omv' id='uit_aantal_omv' value='1' />";
						}else
						{
							$q_zoek_omv = mysqli_query($conn, "SELECT * FROM kal_customers_omvormers WHERE co_cus_id = " . $cus2->cus_id) or die( mysqli_error($conn) );
							
							$i=0;
							
							$omv_keuzes_extra = $omv_keuzes; 
							
							// bestaande
							while( $keuze_omv = mysqli_fetch_object($q_zoek_omv) )
							{
								$i++;
								
								echo "<tr>";
								echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";
								echo "<td>";
								
								echo "<input type='text' class='lengte' name='uit_sn". $i ."' id='uit_sn". $i ."' value='". $keuze_omv->co_sn ."' />";
								echo "<input type='hidden' name='uit_omv". $i ."' id='uit_omv". $i ."' value='".$keuze_omv->co_omvormer."' />";
								
								echo "</td>";
								echo "</tr>";
                                
                                echo "<tr>";
								echo "<td class='verkoop_gegevens'>Omv. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";
								echo "<td>";
								
								echo "<input type='text' class='lengte' name='uit_text". $i ."' id='uit_text". $i ."' value='". $keuze_omv->co_text ."' />";
								
								echo "</td>";
								echo "</tr>";
								
								// diegene die al bestaan niet meer tonen in de volgende lus
								$blokme = 0;
								foreach( $omv_keuzes_extra as $key1 => $o )
								{
									if( $o == (int)$keuze_omv->co_omvormer )
									{
										if( $blokme == 0 )
										{
											unset($omv_keuzes_extra[$key1]);
											$blokme = 1;	
										}
									}
								}
							}
							
							// nieuwe
							foreach( $omv_keuzes_extra as $key => $keuze )
							{
								$i++;
								
								echo "<tr>";
								echo "<td  class='verkoop_gegevens'>SN. " . $list_inv[$keuze] . " :</td>";
								echo "<td>";
								
								$waarde = "";
								$q_zoek_waarde = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_omvormers WHERE co_cus_id = '". $cus2->cus_id ."' AND co_omvormer = '". $keuze ."'"));
								
								if( !empty( $q_zoek_waarde->co_sn ) )
								{
									$waarde = $q_zoek_waarde->co_sn;
								}
								
								echo "<input type='text' class='lengte' name='uit_sn". $i ."' id='uit_sn". $i ."' value='". $waarde ."' />";
								echo "<input type='hidden' name='uit_omv". $i ."' id='uit_omv". $i ."' value='".$keuze."' />";
								
								echo "</td>";
								echo "</tr>";
                                
                                echo "<tr>";
								echo "<td class='verkoop_gegevens'>Omv. " . $list_inv[$keuze_omv->co_omvormer] . " :</td>";
								echo "<td>";
								
								echo "<input type='text' class='lengte' name='uit_text". $i ."' id='uit_text". $i ."' value='". $keuze_omv->co_text ."' />";
								
								echo "</td>";
								echo "</tr>";
							}
							echo "<input type='hidden' name='uit_aantal_omv' id='uit_aantal_omv' value='". $i ."' />";
						}
					}	
				}
                // einde weergeven van de omvormers sn
                
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Werkdocument gemaakt door: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_werkdocument_door' id='uit_werkdocument_door' value='". $cus2->cus_werkdoc_door ."' /> ";
				}else {
					echo $cus2->cus_werkdoc_door;
				}
				
				echo "</td>";
				echo "</tr>";
				
				$sel0 = "";
				$sel1 = "";
				
				if( $cus2->cus_werkdoc_klaar == '0' )
				{
					$sel0 = "selected='yes'";
				}else
				{
					$sel1 = "selected='yes'";
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Werkdcument klaar?: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='uit_werkdocument_klaar' id='uit_werkdocument_klaar'>";
					echo "<option value='0' ".$sel0." >Neen</option>";
					echo "<option value='1' ".$sel1." >Ja</option>";
					echo "</select>";
				}else {
					if( $cus2->cus_werkdoc_klaar == '0' )
					{
						echo "Neen";
					}else
					{
						echo "Ja";
					}
				}
				
				echo "</td>";
				echo "</tr>";
                
                if( $cus2->cus_werkdoc_check == '1' )
				{
					$checked = " checked='checked' ";
				}else
				{
					$checked = "";
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Gecontrolleerd?:</td>";
				echo "<td>";
				if( $_SESSION["kalender_user"]->group_id == 1 )
				{
					echo "<input type='checkbox' ". $checked ." name='uit_werkdoc_check' id='uit_werkdoc_check' />";
				}else {
					if( $cus2->cus_werkdoc_check == '0' )
					{
						echo "Neen";
					}else
					{
						echo "Ja";
					}
				}
				echo "</td>";
				echo "</tr>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td><b>";
                    echo "<a href='pdf_werkdoc.php?id=". $cus2->cus_id ."' target='_blank'><input type='button' value='Genereer werkdocument' /></a>";
				
					echo "</b></td>";
					echo "<td>";
				
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Opmerking:</td>";
					echo "<td>";
					echo "<input type='text' class='lengte' name='uit_werkdoc_opm' id='uit_werkdoc_opm' value='". $cus2->cus_werkdoc_opm ."' />";
					echo "</td>";
					echo "</tr>";
					
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Opmerking2:</td>";
					echo "<td>";
					echo "<input type='text' class='lengte' name='uit_werkdoc_opm2' id='uit_werkdoc_opm2' value='". $cus2->cus_werkdoc_opm2 ."' />";
					echo "</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Foto 1: (.jpg)</td>";
					echo "<td>";
					echo "<input type='file' class='lengte' name='uit_werkdoc_pic1' id='uit_werkdoc_pic1' value='". $cus2->cus_werkdoc_pic1 ."' />";
					echo "</td>";
					echo "</tr>";
				
					if( !empty( $cus2->cus_werkdoc_pic1) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";
				
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_werkdocpic1_del' id='uit_werkdocpic1_del' />";
						}
				
						echo "</td><td>";
				
						echo "<a href='cus_docs/". $cus2->cus_id . "/werkdocument_file/pic1/" . $cus2->cus_werkdoc_pic1 ."' target='_blank' >";
						echo $cus2->cus_werkdoc_pic1 ;
						echo "</a>";
				
						echo "</td></tr>";
					}
				
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Foto 2: (.jpg)</td>";
					echo "<td>";
					echo "<input type='file' name='uit_werkdoc_pic2' id='uit_werkdoc_pic2' value='". $cus2->cus_werkdoc_pic2 ."' />";
					echo "</td>";
					echo "</tr>";
				
					if( !empty( $cus2->cus_werkdoc_pic2) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";
				
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_werkdocpic2_del' id='uit_werkdocpic2_del' />";
						}
				
						echo "</td><td>";
				
						echo "<a href='cus_docs/". $cus2->cus_id . "/werkdocument_file/pic2/" . $cus2->cus_werkdoc_pic2 ."' target='_blank' >";
						echo $cus2->cus_werkdoc_pic2;
						echo "</a>";
				
						echo "</td></tr>";
					}
				
					echo "<tr>";
					echo "<td>&nbsp;</td>";
					echo "<td>OF</td>";
					echo "</tr>";
				
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Werkdocument invoegen: </td>";
					echo "<td>";
					echo "<input type='file' name='uit_werkdocument_file' id='uit_werkdocument_file' />";
					echo "</td>";
					echo "</tr>";
				}
				
				if( !empty( $cus2->cus_werkdoc_file ) && !empty( $cus2->cus_werkdoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_werkdoc_del' id='uit_werkdoc_del' />";
					}
				
					echo "</td><td>";
				
					if( file_exists( "cus_docs/" . $cus2->cus_id . "/werkdocument_file/" . $cus2->cus_werkdoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus2->cus_id . "/werkdocument_file/" . $cus2->cus_werkdoc_filename . "' target='_blank' >";
						echo $cus2->cus_werkdoc_filename;
						echo "</a>";
					}else
					{
						echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=werkdoc_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo $cus2->cus_werkdoc_filename;
						echo "</a>";
					}
				
					echo "</td></tr>";
				}else
				{
					if( !empty( $cus2->cus_werkdoc_filename) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";
				
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_werkdoc_del' id='uit_werkdoc_del' />";
						}
				
						echo "</td><td>";
				
						if( file_exists( "cus_docs/" . $cus2->cus_id . "/werkdocument_file/" . $cus2->cus_werkdoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus2->cus_id . "/werkdocument_file/" . $cus2->cus_werkdoc_filename . "' target='_blank' >";
							echo $cus2->cus_werkdoc_filename;
							echo "</a>";
						}else
						{
							echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=werkdoc_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
							echo $cus2->cus_werkdoc_filename;
							echo "</a>";
						}
				
						echo "</td></tr>";
					}
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Max. AC-vermogen: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 5 ||  $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_ac_vermogen' id='uit_ac_vermogen' value='". $cus2->cus_ac_vermogen ."' onblur='checkDriefase(this);' />";
				}else {
					echo $cus2->cus_ac_vermogen;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Conform offerte: </td>";
				echo "<td>";
				// nakijken van aantal panelen en vermogen per panelen
				echo "<span id='conform_offerte'>";
				
				if( $cus2->cus_aant_panelen == $cus2->cus_werk_aant_panelen && $cus2->cus_w_panelen == $cus2->cus_werk_w_panelen )
				{
					echo "Ja";
				}else
				{
					echo "<span class='error'>Neen</span>";
				}
				
				echo "</span>";
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Orderbon aanpassen: </td>";
				echo "<td>";
				echo "<span id='id_orderbon'>";
				
				if( $cus2->cus_aant_panelen == $cus2->cus_werk_aant_panelen && $cus2->cus_w_panelen == $cus2->cus_werk_w_panelen )
				{
					echo "Neen";
				}else
				{
					echo "<span class='error'>Ja</span>";
				}
				
				echo "</span>";
				echo "</td>";
				echo "</tr>";
				
				// begin elec schema
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Elec. schema: </td>";
					echo "<td>";
					echo "<input type='file' name='uit_doc_elec' id='uit_doc_elec' />";
					echo "</td>";
					echo "</tr>";
				}

				if( !empty( $cus2->cus_elecdoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'>";

					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_elecdoc_del' id='uit_elecdoc_del' />";
					}

					echo "</td><td>";

					if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_elec/" . $cus2->cus_elecdoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_elec/" . $cus2->cus_elecdoc_filename . "' target='_blank' >";
						echo $cus2->cus_elecdoc_filename;
						echo "</a>";
					}
					echo "</td></tr>";
				}
				// einde elec schema
				
				$sel0 = "";
				$sel1 = "";
				
				if( $cus2->cus_arei == '0' )
				{
					$sel0 = "selected='yes'";
				}else
				{
					$sel1 = "selected='yes'";
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>AREI keuring: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='uit_arei' id='uit_arei'>";
					echo "<option value='0' ".$sel0." >Niet OK</option>";
					echo "<option value='1' ".$sel1." >OK</option>";
					echo "</select>";
				}else {
					if( $cus2->cus_arei == '0' )
					{
						echo "Niet OK";
					}else
					{
						echo "OK";
					}
				}
				
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Datum AREI keuring: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_datum_arei' id='uit_datum_arei' value='". $cus2->cus_arei_datum ."' />";
				}else {
					echo $cus2->cus_arei_datum;
				}
				
				echo "</td>";
				echo "</tr>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td class='verkoop_gegevens'>Document AREI keuring: </td>";
					echo "<td>";
					echo "<input type='file' name='uit_doc_arei' id='uit_doc_arei' />";
					echo "</td>";
					echo "</tr>";
				}
				
				if( !empty( $cus2->cus_areidoc_file  ) && !empty( $cus2->cus_areidoc_filename ) )
				{
					echo "<tr><td align='right' class='verkoop_gegevens'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_areidoc_del' id='uit_areidoc_del' />";
					}
				
					echo "</td><td>";
				
					if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_arei/" . $cus2->cus_areidoc_filename ) )
					{
						echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_arei/" . $cus2->cus_areidoc_filename . "' target='_blank' >";
						echo $cus2->cus_areidoc_filename;
						echo "</a>";
					}else
					{
						echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=areidoc_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo $cus2->cus_areidoc_filename;
						echo "</a>";
					}
					echo "</td></tr>";
				}else
				{
					if( !empty( $cus2->cus_areidoc_filename) )
					{
						echo "<tr><td align='right' class='verkoop_gegevens'>";
				
						if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
						{
							echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_areidoc_del' id='uit_areidoc_del' />";
						}
				
						echo "</td><td>";
				
						if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_arei/" . $cus2->cus_areidoc_filename ) )
						{
							echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_arei/" . $cus2->cus_areidoc_filename . "' target='_blank' >";
							echo $cus2->cus_areidoc_filename;
							echo "</a>";
						}else
						{
							echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=areidoc_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
							echo $cus2->cus_areidoc_filename ;
							echo "</a>";
						}
						echo "</td></tr>";
					}
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Meterstand AREI keuring: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_arei_meterstand' id='uit_arei_meterstand' value='". $cus2->cus_arei_meterstand ."' />";
				}else {
					echo $cus2->cus_arei_meterstand;
				}
				
				echo "</td>";
				echo "</tr>";
				
				$sel0 = "";
				$sel1 = "";
				
				if( $cus2->cus_klant_tevree == '0' )
				{
					$sel0 = "selected='yes'";
				}
				
				if( $cus2->cus_klant_tevree == '1' )
				{
					$sel1 = "selected='yes'";
				}
				
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>Klant tevreden: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<select name='uit_klant_tevree' id='uit_klant_tevree' onchange='uit_toonKlantNietTevree(this);' class='lengte'>";
					echo "<option value='' >== Keuze ==</option>";
					echo "<option value='0' ".$sel0." >Neen</option>";
					echo "<option value='1' ".$sel1." >Ja</option>";
					echo "</select>";
				}else {
					if( $cus2->cus_klant_tevree == '0' )
					{
						echo "Neen";
					}
				
					if( $cus2->cus_klant_tevree == '1' )
					{
						echo "Ja";
					}
				}
				
				echo "</td>";
				echo "</tr>";
                
                $stijl_niet_tevree = "";
				if( $cus2->cus_klant_tevree != '0' )
				{
					$stijl_niet_tevree = "display:none;";
				}
				echo "<tr>";
				echo "<td class='verkoop_gegevens'>";
				echo "<span id='uit_niet_tevree2' style='". $stijl_niet_tevree ."' >";
				echo "Waarom niet?";
				echo "</span>";
				echo "</td>";
				echo "<td>";
				echo "<span id='uit_niet_tevree1' style='". $stijl_niet_tevree ."' >";
				echo "<input type='text' class='lengte' name='uit_niet_tevree' id='uit_niet_tevree' value='". $cus2->cus_tevree_reden ."' />";
				echo "</span>";
				echo "</td>";
				echo "</tr>";
                
				echo "</table>";
                echo "</fieldset>";
                
                $stijl = "style='display:none;'";

				if( $cus2->cus_verkoop == '1' || $cus2->cus_verkoop == '2' )
				{
					$stijl = " style='display:block;' ";
				}
                
                echo "<fieldset id='uit_tabel4' ". $stijl ." > ";
                echo "<legend>Opvolging</legend>";
                
                echo "<table border='0'>";

				if( $cus2->cus_vreg_datum == "0000-00-00" )
				{
					$cus2->cus_vreg_datum = "";
				}else
				{
					$datum = explode("-", $cus2->cus_vreg_datum);
					$cus2->cus_vreg_datum = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}
				
				echo "<tr>";
				echo "<td>Datum VREG aanvraag: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_datum_vreg' id='uit_datum_vreg' value='". $cus2->cus_vreg_datum ."' />";
				}else {
					echo $cus2->cus_vreg_datum;
				}
					
				echo "</td>";
				echo "</tr>";
				
				if( $cus2->cus_datum_net == "0000-00-00" )
				{
					$cus2->cus_datum_net = "";
				}else
				{
					$datum = explode("-", $cus2->cus_datum_net);
					$cus2->cus_datum_net = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
				}
				
				echo "<tr>";
				echo "<td>Meldingsdatum netbeheerder: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_datum_net' id='uit_datum_net' value='". $cus2->cus_datum_net ."' />";
				}else {
					echo $cus2->cus_datum_net;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td>PVZ nr.: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_pvz' id='uit_pvz' value='". $cus2->cus_pvz ."' />";
				}else {
					echo $cus2->cus_pvz;
				}
					
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td>EAN nr.: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_ean' id='uit_ean' value='". $cus2->cus_ean ."' />";
				}else {
					echo $cus2->cus_ean;
				}
					
				echo "</td>";
				echo "</tr>";
				
                /*
				echo "<tr>";
				echo "<td>Rekeningnummer klant: </td>";
				echo "<td>";
					
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<input type='text' class='lengte' name='uit_reknr' id='uit_reknr' value='". $cus2->cus_reknr ."' />";
				}else {
					echo $cus2->cus_un_vreg;
				}
					
				echo "</td>";
				echo "</tr>";
                */
				
				$sel0 = "";
				$sel1 = "";
				$sel2 = "";
				
				switch( $cus2->cus_gemeentepremie )
				{
					case '0' :
						$sel0 = "selected='yes'";
						break;
					case '1' :
						$sel1 = "selected='yes'";
						break;
					case '2' :
						$sel2 = "selected='yes'";
						break;
				}
				
				echo "<tr>";
				echo "<td>Gemeentepremie: </td>";
				echo "<td>";
				
                $stijl = "";
                        
                if( $cus2->cus_gemeentepremie == '2' )
                {
                    $aant_verplicht++;
                    $stijl = " style='border:2px solid red;' ";    
                }
                
				echo "<select ". $stijl ." name='uit_gem_premie' id='uit_gem_premie' class='lengte'>";
				echo "<option value='2' ". $sel2 .">== Keuze ==</option>";
				echo "<option value='0' ". $sel0 .">Aangevraagd en nodig</option>";
				echo "<option value='1' ". $sel1 .">Aangevraagd maar niet nodig</option>";
				echo "</select>";
					
				echo "</td>";
				echo "</tr>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td>Gemeentepremie document: </td>";
					echo "<td>";
					echo "<input type='file' name='uit_doc_gemeente' id='uit_doc_gemeente' />";
					echo "</td>";
					echo "</tr>";
				}
				
				if( !empty( $cus2->cus_gemeentedoc_filename ) )
				{
					echo "<tr><td align='right'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_gemeentedoc_del' id='uit_gemeentedoc_del' />";
					}
				
					echo "</td><td>";
				
					if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_gemeente/" . $cus2->cus_gemeentedoc_filename ) )
					{
						//echo "<a style='cursor:pointer;' onclick='window.open(\"klanten_file.php?id=". $cus2->cus_id ."&soort=gemeentedoc_file\",\"". $cus2->cus_id ."\",\"width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes\" )'>";
						echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_gemeente/" . $cus2->cus_gemeentedoc_filename . "' target='_blank' >";
						echo $cus2->cus_gemeentedoc_filename;
						echo "</a>";
					}
				
				
					echo "</td></tr>";
				}
				
				$sel0 = "";
				$sel1 = "";
				$sel2 = "";
				
				switch( $cus2->cus_bouwvergunning )
				{
					case '0' :
						$sel0 = "selected='yes'";
						break;
					case '1' :
						$sel1 = "selected='yes'";
						break;
					case '2' :
						$sel2 = "selected='yes'";
						break;
				}
				
				echo "<tr>";
				echo "<td>Bouwvergunning: </td>";
				echo "<td>";
				
                $stijl = "";
                        
                if( $cus2->cus_bouwvergunning == '2' )
                {
                    $aant_verplicht++;
                    $stijl = " style='border:2px solid red;' ";    
                }
                	
				echo "<select ". $stijl ." name='uit_bouwver' id='uit_bouwver' class='lengte'>";
				echo "<option value='2' ". $sel2 .">== Keuze ==</option>";
				echo "<option value='0' ". $sel0 .">Neen</option>";
				echo "<option value='1' ". $sel1 .">Ja</option>";
				echo "</select>";
					
				echo "</td>";
				echo "</tr>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<tr>";
					echo "<td>Bouwvergunning document: </td>";
					echo "<td>";
					echo "<input type='file' name='uit_doc_bouwver' id='uit_doc_bouwver' />";
					echo "</td>";
					echo "</tr>";
				}
				
				if( !empty( $cus2->cus_bouwvergunning_filename ) )
				{
					echo "<tr><td align='right'>";
				
					if( ($_SESSION["kalender_user"]->user_id == $cus2->cus_acma) || empty( $cus2->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->user_id == 29 )
					{
						echo "Verwijderen?&nbsp;&nbsp;<input type='checkbox' name='uit_bouwverdoc_del' id='uit_bouwverdoc_del' />";
					}
				
					echo "</td><td>";
				
					if( file_exists( "cus_docs/" . $cus2->cus_id . "/doc_bouw/" . $cus2->cus_bouwvergunning_filename ) )
					{
						echo "<a href='cus_docs/" . $cus2->cus_id . "/doc_bouw/" . $cus2->cus_bouwvergunning_filename . "' target='_blank' >";
						echo $cus2->cus_bouwvergunning_filename;
						echo "</a>";
					}
				
					echo "</td></tr>";
				}
				// ************** //
				
				echo "</table>";
                echo "</fieldset>";
				
				// EINDE TABEL 2
				echo "</td>";
				echo "</tr>";
				echo "</table>";
				
				echo "<input type='hidden' name='tab_id' id='tab_id' value='1' />";
				echo "<input type='hidden' name='cus_id' id='cus_id' value='". $cus->cus_id ."' />";
				echo "<input type='hidden' name='cus_id2' id='cus_id2' value='". $cus->cus_id ."' />";
				
				echo "<br/>";
				// EINDE UITBREIDING
				
				echo "</div>";
				
				if( ($_SESSION["kalender_user"]->user_id == $cus->cus_acma) || empty( $cus->cus_acma )  || $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 || $_SESSION["kalender_user"]->user_id == 29 )
				{
					echo "<table border='0' width='100%' class='main_table'>";
					echo "<tr><td>&nbsp;</td></tr>";
                    
                    if( $aant_verplicht > 0 )
                    {
                        if( $aant_verplicht == 1 )
                        {
                            echo "<tr><td align='center'><span class='error'>Er is ". $aant_verplicht ." verplicht veld dat nog dient ingevuld te worden.</span></td></tr>";
                        }else
                        {
                            echo "<tr><td align='center'><span class='error'>Er zijn ". $aant_verplicht ." verplichte velden die nog dienen ingevuld te worden.</span></td></tr>";    
                        }
                        
                    }
					
                    echo "<tr><td colspan='2' align='center'>";
					echo "<input type='submit' name='pasaan' id='pasaan' value='Wijzig' onclick='selectAll(\"invitees[]\", true);' />&nbsp;&nbsp;&nbsp;";
	
					if( $_SESSION["kalender_user"]->group_id ==1 )
					{
						echo "<input type='submit' name='verwijderen' id='verwijderen' value='Verwijderen' onclick=\"javascript:return confirm('Deze klant verwijderen?')\" />";
					}
					echo "</td></tr>";
					echo "<tr><td>&nbsp;</td></tr>";
					echo "</table>";
				}
				
				echo "</form>";
			}
		}

		if( $cus->cus_oa == '1' )
		{
			?>
			<script type='text/javascript'>
				switchOA( "none" );
			</script>
			<?php 
		}
		
		?>
</div>

<div id="tabs-4"><?php

$sorteer = "ORDER BY cus_offerte_datum DESC";

if( isset( $_POST["soort1"] ) && !empty($_POST["soort1"]) )
{
	$sorteer = "ORDER BY " . $_POST["soort1"];
}

if( isset( $_POST["volgorde1"] ) )
{
	if( $_POST["volgorde1"] == 1 )
	{
		$sorteer .= " ASC";
	}else
	{
		$sorteer .= " DESC";
	}
}

if( !isset($_POST["showall_no"]) )
{
    $sorteer .= " LIMIT 0, 20";        
}

if( $_SESSION["kalender_user"]->group_id == 3 )
{
	if( $_SESSION["kalender_user"]->user_id == 29 )
	{
		$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND cus_verkoop = '' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
	}else
	{
		$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND cus_verkoop = '' AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
	}
}else
{
	$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND cus_verkoop = '' " . $sorteer);
}

$sorteer = "";

?>
<form name='frm_overzicht' id='frm_overzicht' method='post'>
	<input type='hidden' name='tab_id' id='tab_id' value='1' /> 
	<input type='hidden' name='cus_id1' id='cus_id1' />
</form>

<script type='text/javascript'>

function setSort1(soort, volgorde)
{
	document.getElementById("soort1").value = soort;
	document.getElementById("volgorde1").value = volgorde;
	document.getElementById("frm_overzicht_sort1").submit(); 
}

</script>

<form name='frm_overzicht_sort1' id='frm_overzicht_sort1' method='post'>
	<input type='hidden' name='tab_id' id='tab_id' value='2' /> 
	<input type='hidden' name='soort1' id='soort1' value='' /> 
	<input type='hidden' name='volgorde1' id='volgorde1' value='' />
    
    <?php
    
    if( isset($_POST["showall_no"]) )
    {
    ?>
    <input type='hidden' name='showall_no' id='showall_no' value='showall_no' />
    
    <?php
    }
    ?>
    
</form>

<?php

$sort1 = 1;

if( isset( $_POST["volgorde1"] ) && $_POST["volgorde1"] == 1 )
{
	$sort1 = 0;
}
?>

<table cellpadding='0' cellspacing='0' width='100%'>
	<tr style='cursor: pointer;'>
		<td onclick='setSort1("cus_offerte_datum", <?php echo $sort1; ?>);' width="100"><b>Aanvraag</b></td>
        <td onclick='setSort1("cus_bedrijf, cus_naam", <?php echo $sort1; ?>);' width="250"><b>Naam</b></td>
		<!-- <td onclick='setSort1("cus_straat", <?php echo $sort1; ?>);'><b>Straat</b></td> -->
		<td onclick='setSort1("cus_gemeente", <?php echo $sort1; ?>);'><b>Gemeente</b></td>
		<td onclick='setSort1("cus_acma", <?php echo $sort1; ?>);'><b>ACMA</b></td>
		<td onclick='setSort1("cus_contact", <?php echo $sort1; ?>);'><b>Contact</b></td>
		<td onclick='setSort1("cus_offerte_besproken", <?php echo $sort1; ?>);'><b>Bespreking</b></td>
		<td onclick='setSort1("cus_verkoop", <?php echo $sort1; ?>);'><b>Verkoop</b></td>
		<td onclick='setSort1("cus_opmerkingen", <?php echo $sort1; ?>);'><b>Opm.</b></td>
	</tr>

	<?php

	$cus = array();

	$i = 0;
	while( $klant = mysqli_fetch_object($q_klanten) )
	{
		$cus_id = $klant->cus_id;
		$sub = 0;
		if( $klant->uit_cus_id != 0 )
		{
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {
    			$hoofdklant = mysqli_fetch_object($q_hoofdklant);
    			$sub=1;
    			$cus_id = $hoofdklant->cus_id;
            }
		}
		
		$i++;

		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}

		echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		
		if( $sub == 0 )
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". changeDate2EU($klant->cus_offerte_datum) ."</td>";	
		}else
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". changeDate2EU($hoofdklant->cus_offerte_datum) ."</td>";
		}
		
		echo "<td onclick='gotoKlant(".$cus_id.")'>";

		$vol_klant = "";

		if( $sub == 0 )
		{
			if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
			{
				$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $klant->cus_naam;
				}else
				{
					$vol_klant = $klant->cus_bedrijf;
				}
			}
		}else
		{
			if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .") <i>(uitbr.)</i>";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $hoofdklant->cus_naam . " <i>(uitbr.)</i>";
				}else
				{
					$vol_klant = $hoofdklant->cus_bedrijf . " <i>(uitbr.)</i>";
				}
			}
		}

		if( strlen($vol_klant) > 20 )
		{
			$vol_klant = "<span title='". strip_tags($vol_klant) ."' >". strip_tags(substr($vol_klant,0,20))  ."...</span>";
		}

		echo "<span title='". $klant->cus_gsm ."-". $klant->cus_tel ."' >";
		echo $vol_klant;
		echo "</span>";

		echo "</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";
		
		echo "<td onclick='gotoKlant(".$cus_id.")' title='".$acma_arr[ $klant->cus_acma ]."'>";
		$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
		echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
		echo "</td>";	
		
		$datum = explode("-", $klant->cus_contact);
		$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
		if( $klant->cus_contact == "00-00-0000" )
		{
			$klant->cus_contact = "";
		}
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_contact ."</td>";
				
		$bespreking = "";
		$tmp_b = explode('@', $klant->cus_offerte_besproken);
		if( count( $tmp_b ) > 1 )
		{
			foreach( $tmp_b as $b )
			{
				$tmp_b1 = explode(" ", $b);
				$bespreking .= $tmp_b1[0];
			}
		}
		
		//$klant->cus_offerte_besproken
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $bespreking ."</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>". $verkoop_arr[$klant->cus_verkoop]  ."</td>";
		echo "<td onclick='gotoKlant(".$cus_id.")'>";

		if( !empty( $klant->cus_opmerkingen ) )
		{
			echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
		}

		echo "</td>";
		echo "</tr>";
	}

	?>

</table>
<br />

<?php
if( !isset($_POST["showall_no"]) )
{
    ?>
    <form method="post" id="frm_no_alles" name="frm_no_alles">
    <input type="submit" value="Toon alles" name="showall_no" id="showall_no" />
    <input type="hidden" name="tab_id" id="tab_id" value="2" />
    </form> 
    <?php
}
?>


</div>

<div id="tabs-5"><?php

$sorteer = "";

if( isset( $_POST["sel_contact_acma"] ) && $_POST["sel_contact_acma"] > 0 )
{
    $sorteer .= " AND cus_acma = " . $_POST["sel_contact_acma"]; 
}

if( isset( $_POST["soort2"] ) && !empty($_POST["soort2"]) )
{
	$sorteer .= " ORDER BY " . $_POST["soort2"];
}else
{
    $sorteer .= " ORDER BY cus_offerte_datum DESC";    
}

if( isset( $_POST["volgorde2"] ) )
{
	if( $_POST["volgorde2"] == 1 )
	{
		// asc
			
		$sorteer .= " ASC";
	}else
	{
		$sorteer .= " DESC";
	}
}

if( !isset( $_POST["alles_nb"] ) && !isset( $_POST["sel_contact_acma"] )  )
{
    $sorteer .= " LIMIT 0, 30";
}

if( $_SESSION["kalender_user"]->group_id == 3 )
{
	if( $_SESSION["kalender_user"]->user_id == 29 )
	{
		$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
        $q_klanten1 = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
	}else
	{
		$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
        $q_klanten1 = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
	}

}else
{
	$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) " . $sorteer);
    $q_klanten1 = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_oa = '0' AND cus_id >= 617 AND (cus_verkoop = '' ) " . $sorteer);
}

$sorteer = "";

?>
<form name='frm_overzicht' id='frm_overzicht' method='post'>
	<input type='hidden' name='tab_id' id='tab_id' value='1' /> 
	<input type='hidden' name='cus_id1' id='cus_id1' />
</form>

<script type='text/javascript'>

function setSort2(soort, volgorde)
{
	document.getElementById("soort2").value = soort;
	document.getElementById("volgorde2").value = volgorde;
	document.getElementById("frm_overzicht_sort2").submit(); 
}

</script>

<form name='frm_overzicht_sort2' id='frm_overzicht_sort2' method='post'>
	<input type='hidden' name='tab_id' id='tab_id' value='3' /> 
	<input type='hidden' name='soort2' id='soort2' value='' /> 
	<input type='hidden' name='volgorde2' id='volgorde2' value='' />
    
    <?php
    if( isset( $_POST["alles_nb"] ) )
    {
    ?>
    <input type='hidden' name='alles_nb' id='alles_nb' value='alles_nb' />
    <?php
    }
    ?>
</form>

<?php

$sort1 = 1;

if( isset( $_POST["volgorde2"] ) && $_POST["volgorde2"] == 1 )
{
	$sort1 = 0;
}
?>
<!--
Onderstaande klanten dienen nog gecontacteerd te worden om de offerte te bespreken.
-->
Onderstaande klanten hebben nog geen offerte besprekingsdatum.
<br /><br />

<?php

if( $_SESSION["kalender_user"]->group_id != 3 || $_SESSION["kalender_user"]->user_id == 29 )
{
?>

<form method="post" name="frm_contact_acma" id="frm_contact_acma">
Gegevens tonen van : 
<select name="sel_contact_acma" id="sel_contact_acma">
<?php
$acma_contact =  mysqli_query($conn, "SELECT * FROM kal_users WHERE active != '0' AND group_id != 5 AND group_id != 6 AND group_id != 7 ORDER by voornaam, naam");

echo "<option value='0'>Iedereen</option>";

if( $_SESSION["kalender_user"]->user_id == 29 )
{
    while( $ac = mysqli_fetch_object($acma_contact) )
    {
        if( in_array($ac->user_id, $klanten_onder_frans_arr ) )
        {
            if( isset( $_POST["sel_contact_acma"] ) && $_POST["sel_contact_acma"] == $ac->user_id )
            {
                echo "<option selected='selected' value='".$ac->user_id."'>" . $ac->voornaam . " " . $ac->naam . "</option>";
            }else
            {
                echo "<option value='".$ac->user_id."'>" . $ac->voornaam . " " . $ac->naam . "</option>";    
            }
        }
    }    
}else
{
    while( $ac = mysqli_fetch_object($acma_contact) )
    {
        if( isset( $_POST["sel_contact_acma"] ) && $_POST["sel_contact_acma"] == $ac->user_id )
        {
            echo "<option selected='selected' value='".$ac->user_id."'>" . $ac->voornaam . " " . $ac->naam . "</option>";
        }else
        {
            echo "<option value='".$ac->user_id."'>" . $ac->voornaam . " " . $ac->naam . "</option>";    
        }
    }
}
?>
</select>

<input type="submit" name="contact_acma_go" id="contact_acma_go" value="GO" />
<input type="hidden" name="tab_id" id="tab_id" value="3" />
</form><br />

<?php
}

$tel_klant = 0;
while( $klant = mysqli_fetch_object($q_klanten1) )
{
	if( $klant->cus_offerte_besproken == "@@" || empty($klant->cus_offerte_besproken) && $klant->cus_offerte_datum != "0000-00-00" && $klant->cus_offerte_datum != "" )
	{
	   $tel_klant++;
    }
}

echo "<b>Aantal klanten in de lijst : " . $tel_klant . "</b><br/><br/>";
?>

<table cellpadding='0' cellspacing='0' width='100%'>
	<tr style='cursor: pointer;'>
		<td onclick='setSort2("cus_offerte_datum", <?php echo $sort1; ?>);' width="100"><b>Aanvraag</b></td>
		<td onclick='setSort2("cus_bedrijf, cus_naam", <?php echo $sort1; ?>);' width="250"><b>Naam</b></td>
		<td onclick='setSort2("cus_gemeente", <?php echo $sort1; ?>);'><b>Gemeente</b></td>
		<td onclick='setSort2("cus_acma", <?php echo $sort1; ?>);'><b>ACMA</b></td>
		<td onclick='setSort2("cus_contact", <?php echo $sort1; ?>);'><b>Contact</b></td>
		<td><b>Opm.</b></td>
	</tr>

	<?php
    
    if( mysqli_num_rows($q_klanten) > 0 )
    {
    	$cus = array();
    
    	$i = 0;
    	while( $klant = mysqli_fetch_object($q_klanten) )
    	{
    		if( $klant->cus_offerte_besproken == "@@" || empty($klant->cus_offerte_besproken) && $klant->cus_offerte_datum != "0000-00-00" && $klant->cus_offerte_datum != "" )
    		{
    			$cus_id = $klant->cus_id;
    			
    			$sub = 0;
    			if( $klant->uit_cus_id != 0 )
    			{
                    $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
                    
                    if( mysqli_num_rows($q_hoofdklant) > 0 )
                    {
                        $hoofdklant = mysqli_fetch_object($q_hoofdklant);
                        $sub=1;
        				$cus_id = $hoofdklant->cus_id;
                    }
    			}
    			
    			$i++;
    
    			$kleur = $kleur_grijs;
    			if( $i%2 )
    			{
    				$kleur = "white";
    			}
    
    			echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
    			echo "<td onclick='gotoKlant(".$cus_id.")'>". changeDate2EU($klant->cus_offerte_datum) ."</td>";
    			
                $tekst_kleur = "";
                
                if( $klant->cus_klant_wilt == "Aankopen" )
                {
                    $tekst_kleur = $kleur_aankoop;
                }
                
                if( $klant->cus_klant_wilt == "Huren" )
                {
                    $tekst_kleur = $kleur_huur;
                }
                
                echo "<td onclick='gotoKlant(".$cus_id.")'><span style='color:". $tekst_kleur .";'>";
    
    			$vol_klant = "";
    
    			if( $sub == 0 )
    			{
    				if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
    				{
    					$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
    				}else
    				{
    					if( $klant->cus_bedrijf == "" )
    					{
    						$vol_klant = $klant->cus_naam;
    					}else
    					{
    						$vol_klant = $klant->cus_bedrijf;
    					}
    				}
    			}else
    			{
    				if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
    				{
    					$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .") <i>(Uitbreiding)</i>";
    				}else
    				{
    					if( $hoofdklant->cus_bedrijf == "" )
    					{
    						$vol_klant = $hoofdklant->cus_naam . " <i>(Uitbreiding)</i>";
    					}else
    					{
    						$vol_klant = $hoofdklant->cus_bedrijf . " <i>(Uitbreiding)</i>";
    					}
    				}
    			}
    
    			if( strlen($vol_klant) > 20 )
    			{
    				$vol_klant = "<span title='". strip_tags($vol_klant) ."' >". substr(strip_tags($vol_klant),0,20) ."...</span>";
    			}
    
    			echo $vol_klant;
    			echo "</span></td>";
    			
    			if( $sub == 0 )
    			{
    				echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";
    			}else
    			{
    				echo "<td onclick='gotoKlant(".$cus_id.")'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";	
    			}
    			
    			echo "<td onclick='gotoKlant(".$cus_id.")' title='".$acma_arr[ $klant->cus_acma ]."'>";
    			$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
    			echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
    			echo "</td>";
    
    			$datum = explode("-", $klant->cus_contact);
    			$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
    			if( $klant->cus_contact == "00-00-0000" )
    			{
    				$klant->cus_contact = "";
    			}
    			echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_contact ."</td>";
    
    			echo "<td onclick='gotoKlant(".$cus_id.")'>";
    
    			if( !empty( $klant->cus_opmerkingen ) )
    			{
    				echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
    			}
    			echo "</td>";
    			echo "</tr>";
    		}
    	}
    }
	?>
</table>

<?php
if( !isset( $_POST["alles_nb"] ) && !isset( $_POST["sel_contact_acma"] ) )
{
?>
<br />
<form method="post" name="frm_nb_alles" id="frm_nb_alles" >
<input type="submit" name="alles_nb" id="alles_nb" value="Toon alles" />
<input type="hidden" name="tab_id" id="tab_id" value="3" />
</form>
<?php
}
?>
<br />
Legende - de klant wilt : 
<table>
    <tr>
        <td width="20" bgcolor="<?php echo $kleur_aankoop; ?>">
        &nbsp;
        </td>
        <td>
            Aankopen
        </td>
    </tr>
    
    <tr>
        <td width="20" bgcolor="<?php echo $kleur_huur; ?>">
        &nbsp;
        </td>
        <td>
            Huren
        </td>
    </tr>
</table>
</div>

<?php
if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
{
?>
<div id='tabs-6'>
<form method='post'>
<table>
	<tr>
		<td colspan='2'>Met behulp van de CTRL-knop is het mogelijk om
		meerdere klanten te selecteren en toe te kennen aan een ACMA. <br />
		<br />
		</td>
	</tr>
	<tr>
		<?php
			
		//$q_zonder_acma = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id >= 617 AND cus_acma = '' ORDER BY cus_offerte_datum DESC");
		$q_zonder_acma = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id >= 1 AND cus_oa = '0' AND cus_acma = '' AND cus_offerte_datum != '0000-00-00' ORDER BY cus_id DESC");
		
        // JOLIEN
        //$q_zonder_acma = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_acma = '32' AND cus_verkoop = '' AND ( cus_offerte_besproken = '' OR cus_offerte_besproken = '@@' ) ORDER BY cus_id DESC");
        
        $aantal_z_acma = mysqli_num_rows($q_zonder_acma);
        
        echo "<td><b>". $aantal_z_acma ." klanten zonder acma :</b><br />";
        
        $sel_hoogte = 10;
        
        if( $aantal_z_acma < 10 )
        {
            $sel_hoogte = 10;
        }else
        {
            if( $aantal_z_acma > 30 )
            {
                $sel_hoogte = 30;
            }else
            {
                $sel_hoogte = $aantal_z_acma;
            }
        }
        
        echo "<select style='width:450px;' name='acma_nodig[]' id='acma_nodig[]' multiple='multiple' size='". $sel_hoogte ."' >";
		while($rij = mysqli_fetch_object($q_zonder_acma))
		{
            $acma_stijl = "";
          
            switch( $rij->cus_klant_wilt )
            {
                case "Aankopen" :
                    $acma_stijl = " style='color:". $kleur_aankoop .";' ";
                    break;
                case "Huren" :
                    $acma_stijl = " style='color:". $kleur_huur .";' ";
                    break;
            }
            
            $gem = "";
            
            if( !empty(  $rij->cus_gemeente ) )
            {
                $gem = " (" . $rij->cus_gemeente . ")"; 
            }
          
			echo "<option ". $acma_stijl ." value='" . $rij->cus_id . "'>" . $rij->cus_offerte_datum .  " " . $rij->cus_naam . $gem . "</option>";
		}
		echo "</select>";
			
		?>
		</td>
		<td valign="top"><b>Toekennen aan :</b><br />

		<?php
			
		$q_acma = mysqli_query($conn, "SELECT * FROM kal_users WHERE group_id IN (1,3,9) AND active = '1' ORDER BY voornaam");
		
        $aantal_acma = mysqli_num_rows($q_acma);
        
		echo "<select style='width:250px;' name='naar_acma' id='naar_acma' size='". $aantal_acma ."' >";
		while($rij = mysqli_fetch_object($q_acma))
		{
			echo "<option value='" . $rij->user_id . "'>" . $rij->voornaam .  " " . $rij->naam . "</option>";
		}
		echo "</select>";
			
		?></td>
		<td valign='bottom'>
			<input type='hidden' name='tab_id' id='tab_id' value='4' /> 
			<input type='submit' name='acma_toekennen' id='acma_toekennen' value='Klanten toekennen' />
		</td>
	</tr>
    
    <tr>
        <td colspan="2">
            <br />
            Legende - de klant wilt : 
            <table>
                <tr>
                    <td width="20" bgcolor="<?php echo $kleur_aankoop; ?>">
                    &nbsp;
                    </td>
                    <td>
                        Aankopen
                    </td>
                </tr>
                
                <tr>
                    <td width="20" bgcolor="<?php echo $kleur_huur; ?>">
                    &nbsp;
                    </td>
                    <td>
                        Huren
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<?php

	if( isset( $_POST["acma_toekennen"] ) && $_POST["acma_toekennen"] == "Klanten toekennen" )
	{
		echo "<tr>";
		echo "<td colspan='3'>";

		if( !isset( $_POST["naar_acma"] ) || !isset( $_POST["acma_nodig"] ) )
		{
			echo "<span class='error'>";

			if( !isset( $_POST["naar_acma"] ) )
			{
				echo "Gelieve een acma te selecteren";
			}else
			{
				echo "Gelieve klanten te selecteren";
			}
			echo "</span>";
		}else
		{
			echo "De volgende klanten zijn toegekend <br/>";

			foreach( $acmas as $kl_acma)
			{
				$zoek_klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $kl_acma));

				echo "<br/>- " . $zoek_klant->cus_naam;
			}

			echo "<br/><br/><b>Aan : " . $acma_arr[$_POST["naar_acma"]] . "</b>";
		}

		echo "</td>";
		echo "</tr>";
	}
	?>
</table>
</form>
</div>
	<?php
	}
	?>

<div id="tabs-7"><!-- 
		Als ACMA, mag alleen zichzelf zien
		Als Frans ingelogd. mag klanten van frans zien
		Als geen ACMA, mag alle acma's zien
	 --> <?php 

		$sort3 = 1;

		if( isset( $_POST["volgorde3"] ) && $_POST["volgorde3"] == 1 )
		{
			$sort3 = 0;
		}

		if( isset( $_POST["soort3"] ) && !empty($_POST["soort3"]) )
		{
			$sorteer = "ORDER BY " . $_POST["soort3"];
		}

		if( isset( $_POST["volgorde3"] ) )
		{
			if( $_POST["volgorde3"] == 1 )
			{
				// asc

				$sorteer .= " ASC";
			}else
			{
				$sorteer .= " DESC";
			}
		}
        
        if( empty( $sorteer ) )
        {
            $sorteer = " ORDER BY cus_verkoop_datum DESC";
        }

        if( !isset( $_POST["verkoop_alles"] ) )
        {
            $sorteer .= " LIMIT 0, 30";
        }

		if( $_SESSION["kalender_user"]->group_id == 3 )
		{
			if( $_SESSION["kalender_user"]->user_id == 29 )
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
			}else
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
			}
		}else
		{
			$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '1' AND cus_oa = '0' AND cus_active = '1' " . $sorteer);
		}
        
        $sorteer = "";
        
		?>
		 
		<script type='text/javascript'>
		function setSort3(soort, volgorde)
		{
			document.getElementById("soort3").value = soort;
			document.getElementById("volgorde3").value = volgorde;
			document.getElementById("frm_overzicht_sort3").submit(); 
		}
		</script>

		<form name='frm_overzicht_sort3' id='frm_overzicht_sort3' method='post'>
		<?php

		$tab_id = 5;

		if( $_SESSION["kalender_user"]->group_id == 3 || $_SESSION["kalender_user"]->group_id == 5 )
		{
			$tab_id = 4;
		}

		?> 
		<input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' /> 
		<input type='hidden' name='soort3' id='soort3' value='' /> 
		<input type='hidden' name='volgorde3' id='volgorde3' value='' />
        
        <?php
        if( isset( $_POST["verkoop_alles"] ) )
        {
        ?>
        <input type='hidden' name='verkoop_alles' id='verkoop_alles' value='verkoop_alles' />
        <?php   
        }
        ?>
        
        </form>

		<!--  
		<a style='cursor:pointer;' onclick="window.open('overzicht_verkoop.php','Verkoopsoverzicht','status,width=1100,height=800,scrollbars=yes'); return false;">Overzicht per week</a>
		<a style='cursor:pointer;' onclick='window.open("overzicht_verkoop.php","width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes" );return false;'>Overzicht per week</a>
		--> 
		
<input type='button' value='Overzicht per week' onclick="window.open('overzicht_verkoop.php','Verkoopsoverzicht','status,width=1100,height=800,scrollbars=yes'); return false;" />

<br />
<br />
<table cellpadding='0' cellspacing='0' width='100%'>
	<tr style='cursor: pointer;'>
		<td onclick='setSort3("cus_bedrijf, cus_naam", <?php echo $sort3; ?>);' width="350"><b>Naam</b></td>
		<td onclick='setSort3("cus_gemeente", <?php echo $sort3; ?>);' width="350"><b>Gemeente</b></td>
		<td onclick='setSort3("cus_acma", <?php echo $sort3; ?>);'><b>ACMA</b></td>
		<td onclick='setSort3("cus_verkoop_datum", <?php echo $sort3; ?>);' align='right'><b>Verkoopsdatum</b></td>
	</tr>

	<?php

	$i = 0;
	while( $klant = mysqli_fetch_object($q_verkocht) )
	{
		$cus_id = $klant->cus_id;
		
		$sub = 0;
		if( $klant->uit_cus_id != 0 )
		{
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {            
                $hoofdklant = mysqli_fetch_object($q_hoofdklant);
    			$sub=1;
    			$cus_id = $hoofdklant->cus_id;
            }
		}
		
		$i++;
		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}
			
		echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		echo "<td onclick='gotoKlant(".$cus_id.")'>";
			
		$vol_klant = "";
		
		if( $sub == 1 )
		{
			if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .")" . " <i>(uitbreiding)</i>";
			}else
			{
				if( $hoofdklant->cus_bedrijf == "" )
				{
					$vol_klant = $hoofdklant->cus_naam . " <i>(uitbreiding)</i>";
				}else
				{
					$vol_klant = $hoofdklant->cus_bedrijf . " <i>(uitbreiding)</i>";
				}
			}
		}else
		{
			if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
			{
				$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $klant->cus_naam;
				}else
				{
					$vol_klant = $klant->cus_bedrijf;
				}
			}
		}
			
		echo $vol_klant;
		echo "</td>";
		
		if( $sub == 1 )
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
		}else
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
		}
		
		echo "<td onclick='gotoKlant(".$cus_id.")' title='".$acma_arr[ $klant->cus_acma ]."'>";
		$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
		echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
		echo "</td>";
			
		echo "<td onclick='gotoKlant(".$cus_id.")' align='right'>";

		if( dateEU( $klant->cus_verkoop_datum ) != "00-00-0000" )
		{
			echo dateEU( $klant->cus_verkoop_datum );
		}
			
		echo "</td>";
		echo "</tr>";
	}

	?>

</table>
<br />
<?php
if( !isset( $_POST["verkoop_alles"] ) )
{
?>
<form method="post" name="frm_verkoop_alles" id="frm_verkoop_alles">
<input type="submit" name="verkoop_alles" id="verkoop_alles" value="Toon alles" />
<input type="hidden" name="tab_id" id="tab_id" value="5" />
</form>
<?php
}
?>
</div>

<div id="tabs-7a"><!-- 
		Als ACMA, mag alleen zichzelf zien
		Als Frans ingelogd. mag klanten van frans zien
		Als geen ACMA, mag alle acma's zien
	 --> <?php 

		$sort3 = 1;

		if( isset( $_POST["volgorde3a"] ) && $_POST["volgorde3a"] == 1 )
		{
			$sort3 = 0;
		}

		if( isset( $_POST["soort3a"] ) && !empty($_POST["soort3a"]) )
		{
			$sorteer = "ORDER BY " . $_POST["soort3a"];
		}

		if( isset( $_POST["volgorde3a"] ) )
		{
			if( $_POST["volgorde3a"] == 1 )
			{
				// asc

				$sorteer .= " ASC";
			}else
			{
				$sorteer .= " DESC";
			}
		}
        
        if( empty( $sorteer ) )
        {
            $sorteer = " ORDER BY cus_verkoop_datum DESC";
        }

        if( !isset( $_POST["verhuur_alles"] ) )
        {
            $sorteer .= " LIMIT 0, 30";
        }
        
		if( $_SESSION["kalender_user"]->group_id == 3 )
		{
			if( $_SESSION["kalender_user"]->user_id == 29 )
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '2' AND cus_oa = '0' AND cus_active = '1' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
			}else
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '2' AND cus_oa = '0' AND cus_active = '1' AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
			}
		}else
		{
			$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '2' AND cus_oa = '0' AND cus_active = '1' " . $sorteer);
		}
        
        $sorteer = "";
        
		?>
		 
		<script type='text/javascript'>
		function setSort3a(soort, volgorde)
		{
			document.getElementById("soort3a").value = soort;
			document.getElementById("volgorde3a").value = volgorde;
			document.getElementById("frm_overzicht_sort3a").submit(); 
		}
		</script>

		<form name='frm_overzicht_sort3a' id='frm_overzicht_sort3a' method='post'>
		<?php

		$tab_id = 6;

		if( $_SESSION["kalender_user"]->group_id == 3 || $_SESSION["kalender_user"]->group_id == 5 )
		{
			$tab_id = 5;
		}
        
		?> 
		<input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' /> 
		<input type='hidden' name='soort3a' id='soort3a' value='' /> 
		<input type='hidden' name='volgorde3a' id='volgorde3a' value='' /></form>

		<!--  
		<a style='cursor:pointer;' onclick="window.open('overzicht_verkoop.php','Verkoopsoverzicht','status,width=1100,height=800,scrollbars=yes'); return false;">Overzicht per week</a>
		<a style='cursor:pointer;' onclick='window.open("overzicht_verkoop.php","width=800,height=800,left=450,top=200,scrollbars=yes,toolbar=yes,location=yes" );return false;'>Overzicht per week</a>
		--> 
		
<input type='button' value='Overzicht per week' onclick="window.open('overzicht_verkoop.php','Verkoopsoverzicht','status,width=1100,height=800,scrollbars=yes'); return false;" />

<br />
<br />
<table cellpadding='0' cellspacing='0' width='100%'>
	<tr style='cursor: pointer;'>
		<td><b>Docs OK</b></td>
        <td onclick='setSort3a("cus_bedrijf, cus_naam", <?php echo $sort3; ?>);' width="350"><b>Naam</b></td>
		<td onclick='setSort3a("cus_gemeente", <?php echo $sort3; ?>);' width="350"><b>Gemeente</b></td>
		<td onclick='setSort3a("cus_acma", <?php echo $sort3; ?>);'><b>ACMA</b></td>
		<td onclick='setSort3a("cus_verkoop_datum", <?php echo $sort3; ?>);' align="right"><b>Huurdatum</b></td>
	</tr>

	<?php

	$i = 0;
	while( $klant = mysqli_fetch_object($q_verkocht) )
	{
		$cus_id = $klant->cus_id;
		
		$sub = 0;
		if( $klant->uit_cus_id != 0 )
		{
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {
                $sub=1;
                $hoofdklant = mysqli_fetch_object($q_hoofdklant);
    			$cus_id = $hoofdklant->cus_id;
            }
		}
		
		$i++;
		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}
			
		echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		
        echo "<td onclick='gotoKlant(".$cus_id.")' align='left'>";
        
        if(  $klant->cus_huur_doc == '1' )
        {
            echo "&nbsp;&nbsp;Ja";
        }
        
        echo "</td>";
        
        $stijl = "";
        
        //
        if( empty($klant->cus_kwhkwp) )
        {
            $stijl = " style='color:red;' ";  
        }
        
        if( $klant->cus_woning5j == '2' )
        {
            $stijl = " style='color:red;' ";  
        }
        
        if( $klant->cus_ont_huur == 0 || empty($klant->cus_ont_huur) )
        {
            $stijl = " style='color:red;' ";
        }
        
        if($klant->cus_werk_aant_panelen == 0 || empty($klant->cus_werk_aant_panelen) )
        {
            $stijl = " style='color:red;' ";
        }
        
        if($klant->cus_werk_w_panelen == 0 || empty($klant->cus_werk_w_panelen) )
        {
            $stijl = " style='color:red;' ";
        }
        
        /*
        if( $klant->cus_reknr == 0 || empty($klant->cus_reknr) )
        {
            $stijl = " style='color:red;' ";
        } 
        */
        
        if( empty($klant->cus_iban) )
        {
            $stijl = " style='color:red;' ";
        }
        
        if( empty($klant->cus_bic) )
        {
            $stijl = " style='color:red;' ";
        }
        
        if( empty($klant->cus_banknaam) )
        {
            $stijl = " style='color:red;' ";
        }
        
        if( $klant->cus_gemeentepremie == '2' || $klant->cus_bouwvergunning == '2' )
        {
            $stijl = " style='color:red;' ";
        }
        //
        
        echo "<td onclick='gotoKlant(".$cus_id.")' " . $stijl . " >";
			
		$vol_klant = "";
		
		if( $sub == 1 )
		{
			if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .")" . " <i>(uitbreiding)</i>";
			}else
			{
				if( $hoofdklant->cus_bedrijf == "" )
				{
					$vol_klant = $hoofdklant->cus_naam . " <i>(uitbreiding)</i>";
				}else
				{
					$vol_klant = $hoofdklant->cus_bedrijf . " <i>(uitbreiding)</i>";
				}
			}
		}else
		{
			if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
			{
				$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $klant->cus_naam;
				}else
				{
					$vol_klant = $klant->cus_bedrijf;
				}
			}
		}
			
		echo $vol_klant;
		echo "</td>";
		
		if( $sub == 1 )
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
		}else
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
		}
		
		echo "<td onclick='gotoKlant(".$cus_id.")' title='".$acma_arr[ $klant->cus_acma ]."'>";
		$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
		echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
		echo "</td>";
			
		echo "<td onclick='gotoKlant(".$cus_id.")'  align='right'>";

		if( dateEU( $klant->cus_verkoop_datum ) != "00-00-0000" )
		{
			echo dateEU( $klant->cus_verkoop_datum );
		}
			
		echo "</td>";
		echo "</tr>";
	}

	?>

</table>
<?php
if( !isset( $_POST["verhuur_alles"] ) )
{
?>
<br />
<form method="post" name="frm_verhuur_alles" id="frm_verhuur_alles">
<input type="submit" name="verhuur_alles" id="verhuur_alles" value="Toon alles" />
<input type="hidden" name="tab_id" id="tab_id" value="6" />
</form>
<?php
}
?>
</div>

<div id="tabs-7b"><!-- 
		Als ACMA, mag alleen zichzelf zien
		Als Frans ingelogd. mag klanten van frans zien
		Als geen ACMA, mag alle acma's zien
	 --> <?php 

		$sort3 = 1;

		if( isset( $_POST["volgorde3b"] ) && $_POST["volgorde3b"] == 1 )
		{
			$sort3 = 0;
		}

		if( isset( $_POST["soort3b"] ) && !empty($_POST["soort3b"]) )
		{
			$sorteer = "ORDER BY " . $_POST["soort3b"];
		}

		if( isset( $_POST["volgorde3b"] ) )
		{
			if( $_POST["volgorde3b"] == 1 )
			{
				// asc

				$sorteer .= " ASC";
			}else
			{
				$sorteer .= " DESC";
			}
		}
        
        if( !isset( $_POST["geenov_alles"] ) )
        {
            $sorteer .= " LIMIT 0, 30";
        }

		if( $_SESSION["kalender_user"]->group_id == 3 )
		{
			if( $_SESSION["kalender_user"]->user_id == 29 )
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' AND cus_acma IN (". $klanten_onder_frans .") " . $sorteer);
			}else
			{
				$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' AND cus_acma = '". $_SESSION["kalender_user"]->user_id ."' " . $sorteer);
			}
		}else
		{
			$q_verkocht = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_verkoop = '0' AND cus_oa = '0' AND cus_active = '1' " . $sorteer);
		}
        
        $sorteer = "";
        
		?>
		 
		<script type='text/javascript'>
		function setSort3b(soort, volgorde)
		{
			document.getElementById("soort3b").value = soort;
			document.getElementById("volgorde3b").value = volgorde;
			document.getElementById("frm_overzicht_sort3b").submit(); 
		}
		</script>

		<form name='frm_overzicht_sort3b' id='frm_overzicht_sort3b' method='post'>
		<?php

		$tab_id = 7;

		if( $_SESSION["kalender_user"]->group_id == 3 || $_SESSION["kalender_user"]->group_id == 5 )
		{
			$tab_id = 6;
		}

		?> 
		<input type='hidden' name='tab_id' id='tab_id' value='<?php echo $tab_id; ?>' /> 
		<input type='hidden' name='soort3b' id='soort3b' value='' /> 
		<input type='hidden' name='volgorde3b' id='volgorde3b' value='' />
        </form>
<br />
<br />
<table cellpadding='0' cellspacing='0' width='100%'>
	<tr style='cursor: pointer;'>
		<td onclick='setSort3b("cus_bedrijf, cus_naam", <?php echo $sort3; ?>);' width="350"><b>Naam</b></td>
		<td onclick='setSort3b("cus_gemeente", <?php echo $sort3; ?>);' width="350"><b>Gemeente</b></td>
		<td onclick='setSort3b("cus_acma", <?php echo $sort3; ?>);'><b>ACMA</b></td>
		<td onclick='setSort3b("cus_verkoop", <?php echo $sort3; ?>);' align='right'><b>Overeenkomst</b></td>
	</tr>

	<?php

	$i = 0;
	while( $klant = mysqli_fetch_object($q_verkocht) )
	{
		$cus_id = $klant->cus_id;
		
		$sub = 0;
		if( $klant->uit_cus_id != 0 )
		{
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {
    			$hoofdklant = mysqli_fetch_object($q_hoofdklant);
    			$sub=1;
    			$cus_id = $hoofdklant->cus_id;
            }
		}
		
		$i++;
		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}
			
		echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		echo "<td onclick='gotoKlant(".$cus_id.")'>";
			
		$vol_klant = "";
		
		if( $sub == 1 )
		{
			if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .")" . " <i>(uitbreiding)</i>";
			}else
			{
				if( $hoofdklant->cus_bedrijf == "" )
				{
					$vol_klant = $hoofdklant->cus_naam . " <i>(uitbreiding)</i>";
				}else
				{
					$vol_klant = $hoofdklant->cus_bedrijf . " <i>(uitbreiding)</i>";
				}
			}
		}else
		{
			if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
			{
				$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $klant->cus_naam;
				}else
				{
					$vol_klant = $klant->cus_bedrijf;
				}
			}
		}
			
		echo $vol_klant;
		echo "</td>";
		
		if( $sub == 1 )
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
		}else
		{
			echo "<td onclick='gotoKlant(".$cus_id.")'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
		}
		
		echo "<td onclick='gotoKlant(".$cus_id.")' title='".$acma_arr[ $klant->cus_acma ]."'>";
		$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
		echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
		echo "</td>";
			
		echo "<td align='right'>";
		if( $sub == 1 )
		{
			echo "N - <span title='". $hoofdklant->cus_reden . "'>reden</span>";
		}else
		{
			echo "N - <span title='". $klant->cus_reden . "'>reden</span>";
		}
		echo "</td>";
		echo "</tr>";
	}

	?>

</table>
<?php
if( !isset( $_POST["geenov_alles"] ) )
{
?>
<br />
<form method="post" name="frm_geenov_alles" id="frm_geenov_alles">
<input type="submit" name="geenov_alles" id="geenov_alles" value="Toon alles" />
<input type="hidden" name="tab_id" id="tab_id" value="7" />
</form>
<?php
}
?>

</div>

	<?php 
	
	if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 || $_SESSION["kalender_user"]->group_id == 5 )
	{
	
	?>

	<div id='tabs-8'>
	<b>In te tekenen</b><br/>
	Onderstaande lijst bevat de klanten waar er wel een opmetingsdocument voor is maar waarbij 'ingetekend door' leeg is.<br/><br/>
	<?php 
	
	$q_klanten = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND (cus_verkoop = '1' OR cus_verkoop = '2' ) AND cus_oa = '0' AND cus_opmetingdoc_filename != '' AND cus_ingetekend = '' ORDER BY cus_installatie_datum ASC");
	
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
    echo "<td><b>Inst. datum 1</b></td>";
    echo "<td><b>Naam</b></td>";
    echo "<td><b>Plaats</b></td>";
    echo "<td><b>ACMA </b></td>";
    echo "<td><b></b></td>";    
    echo "</tr>";
    
	$i = 0;
	while( $klant = mysqli_fetch_object($q_klanten) )
	{
		$i++;

		$kleur = $kleur_grijs;
		if( $i%2 )
		{
			$kleur = "white";
		}
        
        $sub = 0;
        $cus_id = $klant->cus_id;
        
		if( $klant->uit_cus_id != 0 )
		{
            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
            
            if( mysqli_num_rows($q_hoofdklant) > 0 )
            {
    			$hoofdklant = mysqli_fetch_object($q_hoofdklant);
    			$sub=1;
    			$cus_id = $hoofdklant->cus_id;
            }
		}

		echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
		echo "<td >";
        
        if( $sub == 0 )
		{
			if( $klant->cus_installatie_datum != '0000-00-00' )
            {
                echo changeDate2EU($klant->cus_installatie_datum);
            }	
		}else
		{
			if( $hoofdklant->cus_installatie_datum != '0000-00-00' )
            {
                echo changeDate2EU($hoofdklant->cus_installatie_datum);
            }
		}
        
        echo "</td>";
        
        echo "<td  width='400'>";

		$vol_klant = "";
        
		if( $sub == 0 )
		{
			if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
			{
				$vol_klant = $klant->cus_naam . " (". $klant->cus_bedrijf .")";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $klant->cus_naam;
				}else
				{
					$vol_klant = $klant->cus_bedrijf;
				}
			}
		}else
		{
			if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
			{
				$vol_klant = $hoofdklant->cus_naam . " (". $hoofdklant->cus_bedrijf .") <i>(uitbr.)</i>";
			}else
			{
				if( $klant->cus_bedrijf == "" )
				{
					$vol_klant = $hoofdklant->cus_naam . " <i>(uitbr.)</i>";
				}else
				{
					$vol_klant = $hoofdklant->cus_bedrijf . " <i>(uitbr.)</i>";
				}
			}
		}

		if( strlen($vol_klant) > 20 )
		{
			$vol_klant = "<span title='". strip_tags($vol_klant) ."' >". substr(strip_tags($vol_klant),0,20) ."...</span>";
		}
        
        echo "<a title='Klik hier om de klant te openen in een nieuw scherm' href='http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=".$cus_id."' target='_blank' ><u>".$vol_klant."</u></a>";

		echo "</td>";
        
        if( $sub == 0 )
        {
            echo "<td  width='400'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";
        }else
        {
            echo "<td width='400'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";    
        }

		echo "<td title='".$acma_arr[ $klant->cus_acma ]."'>";
		$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
		echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
		echo "</td>";

		$datum = explode("-", $klant->cus_contact);
		$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
		if( $klant->cus_contact == "00-00-0000" )
		{
			$klant->cus_contact = "";
		}

		echo "<td >";

		if( !empty( $klant->cus_opmerkingen ) )
		{
			echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
		}
		echo "</td>";
		echo "</tr>";
	}
	
	echo "</table>";
	
	?>
	<br/>
    <hr />
    <br/>
	<b>Op te meten</b>
	<br/>
	Onderstaande lijst bevat de klanten waar er geen opmetingsdatum voor is, de verkoop/verhuur op 'ja' staat, en waarbij de installatiedatum groter is dan vandaag.<br/><br/>
	<?php 
	$q_klanten = mysqli_query($conn, "SELECT * 
                            FROM kal_customers 
                           WHERE cus_active = '1' 
                             AND (cus_opmeting_datum = '0000-00-00' OR cus_opmeting_datum = '' ) 
                             AND cus_installatie_datum != '0000-00-00'
                             AND (cus_verkoop = '1' OR cus_verkoop = '2' )
                        ORDER BY cus_installatie_datum ASC");
	
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
    echo "<td><b>Inst. datum 1</b></td>";
    echo "<td><b>Naam</b></td>";
    echo "<td><b>Plaats</b></td>";
    echo "<td><b>ACMA </b></td>";
    echo "<td><b></b></td>";    
    echo "</tr>";
    
	$i = 0;
	while( $klant = mysqli_fetch_object($q_klanten) )
	{
		$tmp_inst_datum = explode("-", $klant->cus_installatie_datum );
		$inst_datum = mktime( 0, 0, 0, (int)$tmp_inst_datum[1], (int)$tmp_inst_datum[2], (int)$tmp_inst_datum[0] );
		
		$nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
		
		if( ($inst_datum > $nu || $klant->cus_installatie_datum == '0000-00-00')  )
		{
			$sub=0;
			$cus_id = $klant->cus_id;
			
			if( $klant->uit_cus_id != 0 )
			{
                $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
                
                if( mysqli_num_rows($q_hoofdklant) > 0 )
                {
                    $hoofdklant = mysqli_fetch_object($q_hoofdklant);
                    $sub=1;
    				$cus_id = $hoofdklant->cus_id;
                }
			}
			
			$i++;
	
			$kleur = $kleur_grijs;
			if( $i%2 )
			{
				$kleur = "white";
			}
	
			echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
			echo "<td >";
        
            if( $klant->cus_installatie_datum != '0000-00-00' )
            {
                echo changeDate2EU($klant->cus_installatie_datum);
            }
            echo "</td>";
            echo "<td width='400'>";
	
			$vol_klant = "";
			
			if( $sub == 0 )
			{
				if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
				{
					$vol_klant = trim($klant->cus_naam) . " (". $klant->cus_bedrijf .")";
				}else
				{
					if( $klant->cus_bedrijf == "" )
					{
						$vol_klant = trim($klant->cus_naam);
					}else
					{
						$vol_klant = $klant->cus_bedrijf;
					}
				}
			}else
			{
				if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
				{
					$vol_klant = trim($hoofdklant->cus_naam) . " (". $hoofdklant->cus_bedrijf .") (<i>Uitbreiding</i>)";
				}else
				{
					if( $hoofdklant->cus_bedrijf == "" )
					{
						$vol_klant = trim($hoofdklant->cus_naam) . " (<i>Uitbreiding</i>)";
					}else
					{
						$vol_klant = $hoofdklant->cus_bedrijf . " (<i>Uitbreiding</i>)";
					}
				}
			}
			
            echo "<a title='Klik hier om de klant te openen in een nieuw scherm' href='http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=".$cus_id."' target='_blank' ><u>".$vol_klant."</u></a>";
			echo "</td>";
			
			if( $sub == 1 )
			{
				echo "<td  width='400'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
			}else
			{
				echo "<td  width='400'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
			}
			
			echo "<td title='".$acma_arr[ $klant->cus_acma ]."'>";
			$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
			echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
			echo "</td>";
	
			$datum = explode("-", $klant->cus_contact);
			$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
			if( $klant->cus_contact == "00-00-0000" )
			{
				$klant->cus_contact = "";
			}
	
			echo "<td >";
	
			if( !empty( $klant->cus_opmerkingen ) )
			{
				echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
			}
			
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
    
    
    /***************************************/
    ?>
    <hr />
    <br/>
	<b>Op te meten verkopen, onafhankelijk van installatiedatum</b>
	<br/>
	Onderstaande lijst bevat de klanten waar er geen opmetingsdatum voor is, de verkoop op 'ja' staat, en niet toegekend aan OA.<br/><br/>
	<?php 
	$q_klanten = mysqli_query($conn, "SELECT * 
                            FROM kal_customers 
                           WHERE cus_active = '1' 
                             AND (cus_opmeting_datum = '0000-00-00' OR cus_opmeting_datum = '' ) 
                             AND (cus_verkoop = '1')
                             AND cus_oa = '0'
                        ORDER BY cus_installatie_datum ASC");
	
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
    echo "<td><b>Inst. datum 1</b></td>";
    echo "<td><b>Naam</b></td>";
    echo "<td><b>Plaats</b></td>";
    echo "<td><b>ACMA </b></td>";
    echo "<td><b></b></td>";    
    echo "</tr>";
    
	$i = 0;
	while( $klant = mysqli_fetch_object($q_klanten) )
	{
		$tmp_inst_datum = explode("-", $klant->cus_installatie_datum );
		$inst_datum = mktime( 0, 0, 0, (int)$tmp_inst_datum[1], (int)$tmp_inst_datum[2], (int)$tmp_inst_datum[0] );
		
		$nu = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
		
		if( ($inst_datum > $nu || $klant->cus_installatie_datum == '0000-00-00')  )
		{
			$sub=0;
			$cus_id = $klant->cus_id;
			
			if( $klant->uit_cus_id != 0 )
			{
                $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id);
                
                if( mysqli_num_rows($q_hoofdklant) > 0 )
                {
                    $hoofdklant = mysqli_fetch_object($q_hoofdklant);
                    $sub=1;
    				$cus_id = $hoofdklant->cus_id;
                }
			}
			
			$i++;
	
			$kleur = $kleur_grijs;
			if( $i%2 )
			{
				$kleur = "white";
			}
	
			echo "<tr  style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
			echo "<td >";
        
            if( $klant->cus_installatie_datum != '0000-00-00' )
            {
                echo changeDate2EU($klant->cus_installatie_datum);
            }
            echo "</td>";
            echo "<td width='400'>";
	
			$vol_klant = "";
			
			if( $sub == 0 )
			{
				if( $klant->cus_bedrijf != "" && $klant->cus_naam != "" )
				{
					$vol_klant = trim($klant->cus_naam) . " (". $klant->cus_bedrijf .")";
				}else
				{
					if( $klant->cus_bedrijf == "" )
					{
						$vol_klant = trim($klant->cus_naam);
					}else
					{
						$vol_klant = $klant->cus_bedrijf;
					}
				}
			}else
			{
				if( $hoofdklant->cus_bedrijf != "" && $hoofdklant->cus_naam != "" )
				{
					$vol_klant = trim($hoofdklant->cus_naam) . " (". $hoofdklant->cus_bedrijf .") (<i>Uitbreiding</i>)";
				}else
				{
					if( $hoofdklant->cus_bedrijf == "" )
					{
						$vol_klant = trim($hoofdklant->cus_naam) . " (<i>Uitbreiding</i>)";
					}else
					{
						$vol_klant = $hoofdklant->cus_bedrijf . " (<i>Uitbreiding</i>)";
					}
				}
			}
			
            echo "<a title='Klik hier om de klant te openen in een nieuw scherm' href='http://www.solarlogs.be/kalender/klanten.php?tab_id=1&klant_id=".$cus_id."' target='_blank' ><u>".$vol_klant."</u></a>";
			echo "</td>";
			
			if( $sub == 1 )
			{
				echo "<td  width='400'>". $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente ."</td>";
			}else
			{
				echo "<td  width='400'>". $klant->cus_postcode . " " . $klant->cus_gemeente ."</td>";	
			}
			
			echo "<td title='".$acma_arr[ $klant->cus_acma ]."'>";
			$acma_tmp = explode(" ", $acma_arr[ $klant->cus_acma ]);
			echo substr($acma_tmp[0],0,2) . substr($acma_tmp[1],0,1);
			echo "</td>";
	
			$datum = explode("-", $klant->cus_contact);
			$klant->cus_contact = $datum[2] . "-" . $datum[1] ."-" .$datum[0];
			if( $klant->cus_contact == "00-00-0000" )
			{
				$klant->cus_contact = "";
			}
	
			echo "<td >";
	
			if( !empty( $klant->cus_opmerkingen ) )
			{
				echo "<span title='".$klant->cus_opmerkingen."'> <img src='images/info.jpg' width='16' height='16' /> </span>";
			}
			
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
    
	
	?>
	
</div>
	
	<?php 
	
	}
	
	if( $_SESSION["kalender_user"]->group_id == 1 )
	{
	
	?>
		<div id="tabs-9">
			Nog te controleren werkdocumenten : <br/><br/>
			
			<?php 

				$q_zoek = mysqli_query($conn, "SELECT cus_id, uit_cus_id, cus_naam, cus_straat, cus_nr, cus_postcode, cus_gemeente FROM kal_customers, kal_check_werkdoc WHERE cus_active = '1' AND cus_id = cw_cus_id") or die( mysqli_error($conn) );
			
				if( mysqli_num_rows($q_zoek) > 0 )
				{
					echo "<table cellpadding='0' cellspacing='0' width='100%'>";
					echo "<tr>";
	
					echo "<td><b>Naam</b></td>";
					echo "<td><b>Plaats</b></td>";
					
					echo "</tr>";
					
					$i = 0;
					while( $klant = mysqli_fetch_object($q_zoek) )
					{
					    $sub=0;
            			$cus_id = $klant->cus_id;
            			
            			if( $klant->uit_cus_id != 0 )
            			{
                            $q_hoofdklant = mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_active = '1' AND cus_id = " . $klant->uit_cus_id) or die( mysqli_error($conn) );
            				
                            if( mysqli_num_rows($q_hoofdklant) > 0 )
                            {
                                $sub=1;
                                $hoofdklant = mysqli_fetch_object($q_hoofdklant);
                				$cus_id = $hoofdklant->cus_id;
                            }
            			}
                    
						$i++;
		
						$kleur = $kleur_grijs;
						if( $i%2 )
						{
							$kleur = "white";
						}
				
						echo "<tr style='background-color:". $kleur .";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $klant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
						
                        if( $sub == 0 )
                        {
                            echo "<td onclick='gotoKlant(".$cus_id.")'>";
    						echo $klant->cus_naam;
    						echo "</td>";
    						
    						echo "<td onclick='gotoKlant(".$cus_id.")'>";
    						echo $klant->cus_straat . " " . $klant->cus_nr . ", " . $klant->cus_postcode . " " . $klant->cus_gemeente;
    						echo "</td>";
                        }else
                        {
                            echo "<td onclick='gotoKlant(".$cus_id.")'>";
    						echo $hoofdklant->cus_naam . " (Uitbr.)";
    						echo "</td>";
    						
    						echo "<td onclick='gotoKlant(".$cus_id.")'>";
    						echo $hoofdklant->cus_straat . " " . $hoofdklant->cus_nr . ", " . $hoofdklant->cus_postcode . " " . $hoofdklant->cus_gemeente;
    						echo "</td>";   
                        }
						
						
						echo "</tr>";
					}
					
					echo "</table>";
				}
			
			
			?>
		</div>
	<?php 
	
	}
	
	?>
	
	<div id='tabs-10'>
		Uitgebreid zoeken<br/><br/>
		Vul 1 of meerdere velden volledig of gedeeltelijk in om klanten te zoeken.<br/><br/>
		
		<form method='post' name='frm_uit_zoeken' id='frm_uit_zoeken'>
		<table>
		<tr>
			<td> Referte : </td>
			<td> <input type='text' name='z_ref' id='z_ref' /> </td>
		</tr>
        
        <tr>
			<td> Naam : </td>
			<td> <input type='text' name='z_naam' id='z_naam' /> </td>
		</tr>
		
		<tr>
			<td>Bedrijf :</td>
			<td> <input type='text' name='z_bedrijf' id='z_bedrijf' /> </td>
		</tr>
		
		<tr>
			<td>Straat :</td>
			<td> <input type='text' name='z_straat' id='z_straat' /> </td>
		</tr>
		
		<tr>
			<td> Huis nr. : </td>
			<td> <input type='text' name='z_nr' id='z_nr' /> </td>
		</tr>
		
		<tr>
			<td>Postcode :</td>
			<td> <input type='text' name='z_postcode' id='z_postcode' /> </td>
		</tr>
		
		<tr>
			<td> Gemeente : </td>
			<td> <input type='text' name='z_gemeente' id='z_gemeente' /> </td>
		</tr>
		
		<tr>
			<td> E-mail : </td>
			<td> <input type='text' name='z_email' id='z_email' /> </td>
		</tr>
		
		<tr>
			<td> Tel. / GSM : </td>
			<td> <input type='text' name='z_telgsm' id='z_telgsm' /> </td>
		</tr>
        <tr>
			<td> Naam van de bank : </td>
			<td> <input type='text' name='z_bank' id='z_bank' /> </td>
		</tr>
        
        <tr>
			<td> PVZ nr. : </td>
			<td> <input type='text' name='z_pvz' id='z_pvz' /> </td>
			<td> <input type='submit' name='z_zoek' id='z_zoek' value='Zoek' /> </td>
		</tr>
		</table>
		
		<?php 
		
		if( $_SESSION["kalender_user"]->group_id == 5 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='8' />";	
		}
		
		if( $_SESSION["kalender_user"]->group_id == 3 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='7' />";	
		}
		
		if( $_SESSION["kalender_user"]->group_id == 1 || $_SESSION["kalender_user"]->group_id == 4 )
		{
			echo "<input type='hidden' name='tab_id' id='tab_id' value='10' />";
		}
		?>
		</form>
		
		<?php 
		if( isset( $_POST["z_zoek"] ) && $_POST["z_zoek"] == "Zoek" )
		{
			$where = "";
            if( $_POST["z_ref"] != "" && !empty( $_POST["z_ref"] ) )
			{
			    if( strlen( $_POST["z_ref"] ) > 6 )
                {
                    $where = " AND cus_id = '". substr($_POST["z_ref"],6) ."' ";
                }else
                {
                    $where = " AND cus_id = '". $_POST["z_ref"] ."' ";    
                }
			}
            
			if( $_POST["z_naam"] != "" && !empty( $_POST["z_naam"] ) )
			{
				$where = " AND cus_naam LIKE '%". $_POST["z_naam"] ."%' ";
			}
			
			if( isset( $_POST["z_bedrijf"] ) && !empty( $_POST["z_bedrijf"] ) )
			{
				$where .= " AND cus_bedrijf LIKE '%". $_POST["z_bedrijf"] ."%' ";
			}
			
			if( isset( $_POST["z_straat"] ) && !empty( $_POST["z_straat"] ) )
			{
				$where .= " AND cus_straat LIKE '%". $_POST["z_straat"] ."%' ";
			}
			
			if( isset( $_POST["z_nr"] ) && !empty( $_POST["z_nr"] ) )
			{
				$where .= " AND cus_nr LIKE '%". $_POST["z_nr"] ."%' ";
			}
			
			if( isset( $_POST["z_postcode"] ) && !empty( $_POST["z_postcode"] ) )
			{
				$where .= " AND cus_postcode LIKE '%". $_POST["z_postcode"] ."%' ";
			}
			
			if( isset( $_POST["z_gemeente"] ) && !empty( $_POST["z_gemeente"] ) )
			{
				$where .= " AND cus_gemeente LIKE '%". $_POST["z_gemeente"] ."%' ";
			}
			
			if( isset( $_POST["z_email"] ) && !empty( $_POST["z_email"] ) )
			{
				$where .= " AND cus_email LIKE '%". $_POST["z_email"] ."%' ";
			}
            
            if( isset( $_POST["z_bank"] ) && !empty( $_POST["z_bank"] ) )
			{
				$where .= " AND cus_banknaam LIKE '%". $_POST["z_bank"] ."%' ";
			}
			
			if( isset( $_POST["z_telgsm"] ) && !empty( $_POST["z_telgsm"] ) )
			{
				$where .= " AND ( cus_tel LIKE '%". $_POST["z_telgsm"] ."%' OR cus_gsm LIKE '%". $_POST["z_telgsm"] ."%' ) ";
			}
            
            if( isset( $_POST["z_pvz"] ) && !empty( $_POST["z_pvz"] ) )
			{
				$where .= " AND cus_pvz LIKE '%". $_POST["z_pvz"] ."%' ";
			}
			
			$q_zzoek = "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' " . $where ." ORDER BY cus_naam";
			$q_zoek = mysqli_query($conn, $q_zzoek) or die( mysqli_error($conn) );
			
			echo "<hr/>";
			
			if( mysqli_num_rows($q_zoek) == 0 )
			{
				echo "<br/><b>Geen gegevens gevonden.</b><br/><br/>";
			}else
			{
				echo "<br/><b>Klanten gevonden : " . mysqli_num_rows($q_zoek) . "</b><br/><br/>";
			}
			
			echo "<table cellpadding='0' cellspacing='0' width='100%'>";
			
			echo "<tr>";
			echo "<td><b>Naam</b></td>";
			echo "<td><b>Straat </b></td>";
			echo "<td><b>Gemeente </b></td>";
			echo "</tr>";
			
			$i = 1;
			while( $zklant = mysqli_fetch_object($q_zoek) )
			{
				$i++;
		
				$kleur = $kleur_grijs;
				if( $i%2 )
				{
					$kleur = "white";
				}
				
				echo "<tr style='background-color:".$kleur.";cursor:pointer;' onmouseover='ChangeColor(this, true, \"".$kleur."\");document.getElementById(\"cus_id1\").value=". $zklant->cus_id .";' onmouseout='ChangeColor(this, false, \"".$kleur."\");' >";
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_naam;
				echo "</td>";
				
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_straat . " " . $zklant->cus_nr;
				echo "</td>";
				
				echo "<td onclick='gotoKlant(".$zklant->cus_id.")'>";
				echo $zklant->cus_postcode . " " . $zklant->cus_gemeente;
				echo "</td>";
				echo "</tr>";
			}
			
			echo "</table>";
		}
		?>
	</div>
</div>
</div>
<center><?php 

include "inc/footer.php";

?></center>

</body>
</html>
<script type="text/javascript">
$('#frm_go').submit(function(){
    //alert("test");
    $('input:file[value=""]').attr('disabled', true);

});
</script>