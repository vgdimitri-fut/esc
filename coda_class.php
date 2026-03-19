<?php

class beginOpnameClass
{
    public $status;
    public $datum;
    public $bank_id;
    public $toep_code;
    public $duplicate;
    public $bank_referte;
    public $naam;
    public $bic;
    public $btwnr;
    public $versie;  
    
    public function __construct( $string )
    {
        if( substr($string, 0, 1) == 0 )
        {
            //echo "<br>" . $string;    
            
            $this->setStatus("ok");
            
            $this->setDatum( substr( $string,5,6) );
            $this->setBankId( substr( $string,11,3) );
            $this->setToepCode( substr( $string,14,2) );
            $this->setIsDuplicate( substr( $string,16,1) );
            $this->setBankReferte( substr( $string, 24, 10 ) );
            $this->setNaam( substr($string, 34, 26) );
            $this->setBIC( substr( $string, 60, 11 ) );
            $this->setBTWnr( substr( $string, 71, 11 ) );
            $this->setVersie( substr($string,127,1) );
        }else
        {
            $this->setStatus("nok");
        }
    }
    
    public function setStatus($waarde)
    {
        $this->status = $waarde;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setDatum($waarde)
    {
        $this->datum = $waarde;
    }
    
    public function getDatum()
    {
        return $this->datum;
    }
    
    public function setBankId($waarde)
    {
        $this->bank_id = $waarde;
    }
    
    public function getBankId()
    {
        return $this->bank_id;
    }
    
    public function setToepCode($waarde)
    {
        $this->toep_code = $waarde;
    }
    
    public function getToepcode()
    {
        return $this->toep_code;
    }
    
    public function setIsDuplicate($waarde)
    {
        $this->duplicate = $waarde;
    }
    
    public function getIsDuplicate()
    {
        return $this->duplicate;
    }
    
    public function setBankReferte($waarde)
    {
        $this->bank_referte = $waarde;
    }
    
    public function getBankReferte()
    {
        return $this->bank_referte;
    }
    
    public function setNaam($waarde)
    {
        $this->naam = $waarde;
    }
    
    public function getNaam()
    {
        return $this->naam;
    }
    
    public function setBIC($waarde)
    {
        $this->bic = $waarde;
    }
    
    public function getBIC()
    {
        return $this->bic;
    }
    
    public function setBTWnr($waarde)
    {
        $this->btwnr = $waarde;
    }
    
    public function getBTWnr()
    {
        return $this->btwnr;
    }
    
    public function setVersie($waarde)
    {
        $this->versie = $waarde;
    }
    
    public function getVersie()
    {
        return $this->versie;
    }
}

class gegevensOpnameClass{
    public $status;
    public $structuur;
    public $volgnummer;
    public $rek_nr;
    public $saldo_soort;
    public $oud_saldo;
    public $datum_oud_saldo;
    public $naam_rekh;
    public $soort_rek;
    public $nr_dagafschrift;
    
    public function __construct( $string )
    {
        //echo "<br>" . $string;   
        
        if( substr($string, 0, 1) == 1 )
        {
            $this->setStatus("ok");
            
            switch( substr($string,1,1) )
            {
                case 0 :
                    $this->setStructuur("Belgisch rekeningnummer");
                    break;
                case 1 :
                    $this->setStructuur("Buitenlands rekeningnummer");
                    break;
                case 2 :
                    $this->setStructuur("IBAN van Belgisch rekeningnummer");
                    break;
                case 3 :
                    $this->setStructuur("IBAN Buitenlands rekeningnummer");
                    break;
            }
            
            $this->setVolgNummer( substr($string, 2, 3) );
            $this->setRekNr( preg_replace('!\s+!', ' ', substr( $string, 5, 37 )));
            $this->setSaldoSoort( substr($string,42,1) );
            $this->setOudSaldo( $string );
            $this->SetDatumOudSaldo( substr($string, 58, 6 ) );
            $this->setNaamRekh( substr( $string, 64, 26 ) );
            $this->setSoortRek( substr( $string, 90, 35 ) );
            $this->setNrDagafschrift( substr($string, 125, 3) );
            
        }else
        {
            $this->setStatus("nok");
        }
    }
    
    public function setStatus($waarde)
    {
        $this->status = $waarde;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setStructuur($waarde)
    {
        $this->structuur = $waarde;
    }
    
    public function getStructuur()
    {
        return $this->structuur;
    }
    
    public function setRekNr($waarde)
    {
        $this->rek_nr = $waarde;
    }
    
    public function getRekNr()
    {
        return $this->rek_nr;
    }
    
    public function setSaldoSoort($waarde)
    {
        switch( $waarde )
        {
            case 0 :
                $this->saldo_soort = "credit";
                break;
            case 1 :
                $this->saldo_soort = "debet";
                break;
        }
        
    }
    
    public function getSaldoSoort()
    {
        return $this->saldo_soort;
    }
    
    public function setOudSaldo($string)
    {
        $oud_saldo_tmp1 = substr( substr($string,43,15), 0, 12 );
        $oud_saldo_tmp2 = substr( substr($string,43,15), 12, 3 );
        
        $tmp = $oud_saldo_tmp1 . "." . $oud_saldo_tmp2;
        $this->oud_saldo = (float)$tmp;
    }
    
    public function getOudSaldo()
    {
        return $this->oud_saldo;
    }
    
    public function setDatumOudSaldo($string)
    {
        $this->datum_oud_saldo = $string;
    }
    
    public function getDatumOudSaldo()
    {
        return $this->datum_oud_saldo;
    }
    
    public function setVolgNummer($waarde)
    {
        $this->volgnummer = $waarde;
    }
    
    public function getVolgNummer()
    {
        return $this->volgnummer;
    }
    
    public function setNaamRekh($waarde)
    {
        $this->naam_rekh = $waarde;
    }
    
    public function getNaamRekh()
    {
        return $this->naam_rekh;
    }
    
    public function setSoortRek($waarde)
    {
        $this->soort_rek = $waarde;
    }
    
    public function getSoortRek()
    {
        return $this->soort_rek;
    }
    
    public function setNrDagafschrift($waarde)
    {
        $this->nr_dagafschrift = $waarde;
    }
    
    public function getNrDagafschrift()
    {
        return $this->nr_dagafschrift;
    }
}

class gegevensOpnameNieuwClass
{
    public $status;
    public $volgnummer;
    public $rek_nr;
    public $saldo_soort;
    public $nieuw_saldo;
    public $datum_nieuw_saldo;
    public $code_binding;
    
    public function __construct($string8)
    {
        if( substr($string8,0,1) == 8 )
        {
            $this->setStatus("ok");
            $this->setVolgNummer( substr( $string8, 1, 3 ) );
            $this->setRekNr( preg_replace('!\s+!', ' ', substr( $string8, 4, 37 )));
            $this->setSaldoSoort( substr($string8,41,1) );
            $this->setNieuwSaldo($string8);
            $this->setDatumNieuwSaldo( substr( $string8, 57, 6 ) );
            $this->setCodeBinding( substr( $string8, 127, 1 ) );
        }else
        {
            $this->setStatus("nok");
        }
    }
    
    public function setStatus($waarde)
    {
        $this->status = $waarde;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setVolgNummer($waarde)
    {
        $this->volgnummer = $waarde;
    }
    
    public function getVolgNummer()
    {
        return $this->volgnummer;
    }
    
    public function setRekNr($waarde)
    {
        $this->rek_nr = $waarde;
    }
    
    public function getRekNr()
    {
        return $this->rek_nr;
    }
    
    public function setSaldoSoort($waarde)
    {
        switch( $waarde )
        {
            case 0 :
                $this->saldo_soort = "credit";
                break;
            case 1 :
                $this->saldo_soort = "debet";
                break;
        }
        
    }
    
    public function getSaldoSoort()
    {
        return $this->saldo_soort;
    }
    
    public function setNieuwSaldo($string)
    {
        $oud_saldo_tmp1 = substr( substr($string,42,15), 0, 12 );
        $oud_saldo_tmp2 = substr( substr($string,42,15), 12, 3 );
        
        $tmp = $oud_saldo_tmp1 . "." . $oud_saldo_tmp2;
        $this->nieuw_saldo = (float)$tmp;
    }
    
    public function getNieuwSaldo()
    {
        return $this->nieuw_saldo;
    }
    
    public function setDatumNieuwSaldo($string)
    {
        $this->datum_nieuw_saldo = $string;
    }
    
    public function getDatumNieuwSaldo()
    {
        return $this->datum_nieuw_saldo;
    }
    
    public function setCodeBinding($string)
    {
        $this->code_binding = $string;
    }
    
    public function getCodeBinding()
    {
        return $this->code_binding;
    }
}

class eindOpnameClass
{
    public $status;
    public $aant_verr;
    public $debet_omzet;
    public $credit_omzet;
    public $meervoudig_bestand;
    
    public function __construct( $string )
    {
        if( substr($string,0,1) == 9 )
        {
            $this->setStatus( "ok" );
            
            $this->setAantVerr( substr( $string, 16, 6 ) );
            $this->setDebetOmzet($string);
            $this->setCreditOmzet($string);
            $this->setMeervoudigBestand( substr( $string, 127, 1 ) );
            
        }else
        {
            $this->setStatus( "nok" );
        }
    }
    
    public function setStatus($waarde)
    {
        $this->status = $waarde;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setAantVerr($waarde)
    {
        $this->aant_verr = $waarde;
    }
    
    public function getAantVerr()
    {
        return $this->aant_verr;
    }
    
    public function setDebetOmzet($string)
    {
        $debet_tmp1 = substr( substr( $string, 22, 15 ), 0, 12 );
        $debet_tmp2 = substr( substr( $string, 22, 15 ), 12, 3 );
        $tmp = $debet_tmp1 . "." . $debet_tmp2;
        
        $this->debet_omzet = (float)$tmp;
    }
    
    public function getDebetOmzet()
    {
        return $this->debet_omzet;
    }
    
    public function setCreditOmzet($string)
    {
        $credit_tmp1 = substr( substr( $string, 37, 15 ), 0, 12 );
        $credit_tmp2 = substr( substr( $string, 37, 15 ), 12, 3 );
        $tmp = $credit_tmp1 . "." . $credit_tmp2;
        
        $this->credit_omzet = (float)$tmp;
    }
    
    public function getCreditOmzet()
    {
        return $this->credit_omzet;
    }
    
    public function setMeervoudigBestand($waarde)
    {
        $this->meervoudig_bestand = $waarde;
    }
    
    public function getMeervoudigBestand()
    {
        return $this->meervoudig_bestand;
    }
}

function getDataVerr( $soort, $string )
{
    $str = new stdClass();
    
    switch( $soort )
    {
        case 21 :
            
            $str->geg_opname = 21;
            
            $str->bew_volgnr = substr($string, 2, 4 );
            $str->bew_detnr = substr($string, 6, 4);
            $str->bew_bank_refnr = substr($string, 10, 21 );
            
            if( substr( $string, 31, 1 ) == 0 )
            {
                $str->bew_soort = "+";
            }else
            {
                $str->bew_soort = "-";
            }
            
            
            $bedrag_tmp1 = substr( substr( $string, 32, 15 ), 0, 12 );
            $bedrag_tmp2 = substr( substr( $string, 32, 15 ), 12, 3 );
            
            $str->bew_bedrag = $bedrag_tmp1 . "." . $bedrag_tmp2;
            $str->bew_bedrag = (float)$str->bew_bedrag;
            
            $str->bew_vervaldat = substr( $string, 47, 6 );
            $str->bew_code_verr = substr( $string, 53, 8 );
            
            /*
            0 : geen of niet gestructureerde mededeling
            1 : gestructureerde mededeling
            */
            $str->bew_soort_mededeling = substr( $string, 61, 1 );
            
            if( $str->bew_soort_mededeling == 0 )
            {
                $str->bew_mededeling = substr( $string, 62, 52 );
            }
            
            if( $str->bew_soort_mededeling == 1 )
            {
                $str->bew_mededeling = substr( $string, 62, 3 );
                $str->bew_mededeling_extra = substr( $string, 65, 63 );
            }
            
            $str->bew_boekingsdat = substr( $string, 115, 6 );
            $str->bew_volgnr_papier = substr( $string, 121, 3 );
            $str->bew_globalisatie = substr( $string, 124, 1 ); 
            $str->bew_vervolg = substr( $string, 125, 1 );
            $str->bew_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
        case 22 :
            $str->geg_opname = 22;
            $str->bew_doorl_volgnr = substr( $string, 2,4 );
            $str->bew_detail_nr = substr( $string, 6, 4 ); 
            $str->bew_mededeling = substr( $string, 10, 53 );
            $str->bew_referte_client = substr( $string, 63, 35 );
            $str->bew_bic = substr( $string, 98, 11 );
            $str->bew_cat_purpose = substr( $string, 117, 4 );
            $str->bew_purpose = substr( $string, 121, 4 );
            $str->bew_vervolg = substr( $string, 125, 1 );
            $str->bew_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
        case 23 :
            $str->geg_opname = 23;
            $str->bew_doorl_volgnr = substr( $string, 2, 4 );
            $str->bew_detail_nr = substr( $string, 6, 4 );
            $str->bew_reknr = substr( $string, 10, 37 );
            $str->bew_naam = substr( $string, 47, 35 );
            $str->bew_mededeling = substr( $string, 82, 43 );
            $str->bew_vervolg = substr( $string, 125, 1 );
            $str->bew_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
        case 31 :
            $str->geg_opname = 31;
            $str->info_volgnr = substr( $string, 2, 4 );
            $str->info_detail_nr = substr( $string, 6, 4 );
            $str->info_referte_bank = substr( $string, 10, 21 );
            $str->info_code_verr = substr( $string, 31, 8 );
            $str->info_soort_mededeling = substr( $string, 39, 1 );
            $str->info_mededeling = substr( $string, 40, 73 );
            $str->info_vervolg = substr( $string, 125, 1 );
            $str->info_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
        case 32 :
            $str->geg_opname = 32;
            $str->info_volgnr = substr( $string, 2, 4 );
            $str->info_detail_nr = substr( $string, 6, 4 );
            $str->info_mededeling = substr( $string, 10, 105 );
            $str->info_vervolg = substr( $string, 125, 1 );
            $str->bew_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
        case 33 :
            $str->geg_opname = 33;
            $str->info_volgnr = substr( $string, 2, 4 );
            $str->info_detail_nr = substr( $string, 6, 4 );
            $str->info_mededeling = substr( $string, 10, 90 );
            $str->info_vervolg = substr( $string, 125, 1 );
            $str->bew_volgende_gegevensopn = substr( $string, 127, 1 );
            break;
    }
    
    return $str;
}

function makeDatum($string)
{
    $str = substr($string,0,2). "-" . substr($string,2,2) . "-20" . substr($string,4,2); 
    return $str;
}

function getHuurKlanten()
{
    $klanten_arr = array();
    
    $q_zoek = "SELECT * FROM kal_customers WHERE cus_active = '1' AND uit_cus_id = '0' AND cus_verkoop = '2'";
    $q_zoek = mysqli_query($conn, $q_zoek) or die( mysqli_error($conn) );
    
    while( $klant = mysqli_fetch_object($q_zoek) )
    {
        if( !empty( $klant->cus_arei_datum ) )
        {
            $klanten_arr[ maakReferte($klant->cus_id, "") ] = $klant->cus_naam;
        }    
    }
    
    return $klanten_arr;
}

?>