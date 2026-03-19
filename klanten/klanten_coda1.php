<?php

session_start();

include "../inc/db.php";
include "../inc/functions.php";
include "../inc/checklogin.php";

$q = "SELECT * FROM kal_coda WHERE cus_id = " . $_GET["klant_id"] . " AND cf_id_fac = 0 ORDER BY boek_dat DESC" ;

if( isset( $_GET["lev_id"] ) )
{
    $q = "SELECT * FROM kal_coda WHERE lev_id = " . $_GET["lev_id"] . " AND cf_id_fac = 0 ORDER BY boek_dat DESC" ;
}

$isproject = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $_GET["klant_id"]));

$klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $_GET["klant_id"]));

if( $klant->cus_10 == '1' )
{
    $isproject = 1;
}


$q_zoek = mysqli_query($conn, $q) or die( mysqli_error($conn) . " " . __LINE__ . " " . $q );

$totaal = 0;

while( $rij = mysqli_fetch_object($q_zoek) )
{
    // $rij->cf_id_fac
    
    $go = 0;
    
    if( $rij->cf_id_fac > 0  )
    {
        $fac = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $rij->cf_id_fac));
        
        if( $isproject == $fac->cf_type )
        {
           $go = 1; 
        }
        
        $go = 1; 
        
    }else
    {
        $go = 1;
    }
    
    if( $go == 1 )
    {
        $bedrag_coda = 0;
        $coda_id_bedrag = 0;
        $coda_id_bedrag_aantal = 0;
        
        echo "<div id='tabel_".$rij->id."' style='display:block;'>";
        echo "<table cellpadding='2' cellspacing='0' border='1' width='100%' >";
        echo "<tr style='background-color:black;color:white;' ><td width='33%'>";
        
        if( !empty( $rij->naam ) )
        {
            echo "<strong>Naam :</strong> " . $rij->naam;    
        }
        
        echo "</td><td width='33%' align='center'><strong>Boekingsdatum :</strong> ". changeDate2EU($rij->boek_dat)."</td><td width='34%' align='right'>";
        
        $totaal += $rij->bedrag;
        $bedrag_coda = $rij->bedrag;
        
        echo "<strong>Bedrag :</strong> ". number_format($rij->bedrag, 2, ",", " " );
        
        if( !empty($rij->curr) && $rij->curr != "EUR" )
        {
            echo " " . $rij->curr;
        }
        
        echo "</td></tr>";
        echo "<tr><td colspan='3' style='background-color:#F8F8F8;'>";
        
        
        if( !empty( $rij->med1 ) )
        {
            echo $rij->med1 . "<br>";    
        }
        
        if( !empty( $rij->med2 ) )
        {
            echo $rij->med2 . "<br>";    
        }
        
        if( !empty( $rij->med3 ) )
        {
            echo str_replace("\n", "<br>", $rij->med3) . "<br>";    
        }
        
        if( !empty( $rij->ref_cl ) )
        {
            echo "Ref. Cl. : " . $rij->ref_cl . "<br>";    
        }
        
        echo "</td></tr>";
        echo "<tr><td colspan='3' style='background-color:darkgray;color:white;' ><b>Bestandsnaam : </b>". $rij->filename ."</td></tr>";
        
        echo "<tr>";
        if( $rij->cus_id != 0 )
        {
            $klant = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers WHERE cus_id = " . $rij->cus_id));
            
            $naam = "";
                        
            if( $klant->cus_naam == $klant->cus_bedrijf )
            {
                $naam = $klant->cus_naam;
            }else
            {
                if( empty($klant->cus_naam) && !empty( $klant->cus_bedrijf ) )
                {
                    $naam = $klant->cus_bedrijf;
                }
                
                if( !empty($klant->cus_naam) && empty( $klant->cus_bedrijf ) )
                {
                    $naam = $klant->cus_naam;
                }
                
                if( !empty($klant->cus_naam) && !empty( $klant->cus_bedrijf ) && $klant->cus_bedrijf != $klant->cus_naam )
                {
                    $naam = $klant->cus_naam . " / " . $klant->cus_bedrijf;
                }
            }
            
            echo "<td colspan='3' align='right' style='background-color:green;color:white;' ><b>Gekoppeld aan : ". $naam ."</b>";
        }else
        {
            echo "<td colspan='3' style='background-color:darkgray;color:white;' >Koppelen aan : ";
            
            echo "<select name='sel_".$rij->id."' id='sel_".$rij->id."'>";
            
            $stijl = "";
            foreach( $klanten_arr as $cus_id => $cus_naam )
            {
                if( strtolower($rij->naam) == strtolower($cus_naam) )
                {
                    $stijl = " style='background-color:green;' ";
                    echo "<option style='color:green;' selected='selected' value='". $cus_id ."'>". $cus_naam ."</option>";
                }else
                {
                    echo "<option value='". $cus_id ."'>". $cus_naam ."</option>";    
                }
            }
            
            echo "</select>";
            
            echo "<input ". $stijl ." type='button' name='koppel_". $rij->id ."' id='koppel_". $rij->id ."' value='Koppel' onclick='koppelAanKlant(". $rij->id .")' />";
        }
        
        echo "</td></tr>";
        
        if( $rij->cf_id_fac != 0 )
        {
            echo "<tr><td colspan='3' >";
            
            $fact = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_customers_files WHERE cf_id = " . $rij->cf_id_fac));
            
            $fac_date = explode("-", $fact->cf_date);
            $mk_fac_date = mktime(0,0,0,$fac_date[1],$fac_date[2],$fac_date[0]);
            $begin_nw_bj = mktime(0,0,0,7,1,2012);
            
            //echo "<br>" . $mk_fac_date . "<" . $begin_nw_bj;
            $dir = "";
    		if( $mk_fac_date < $begin_nw_bj )
            {
                $dir = "";  
            }else
            {
                // bepalen van de dir.
                $nw_boek_jaar = "01-07";
                $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
                $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
                
                
                if( $mk_nu >= $mk_nw_boek_jaar )
                {
                    //echo "<br> NA 01-07";
                    $jaar_1 = date('Y') + 1;
                    $jaar_2 = date('Y', mktime(0,0,0,7,1-1,$jaar_1) );
                    $dir = date('Y') . $jaar_2;    
                }else{
                    //echo "<br> VOOR 01-07";
                    $jaar_1 = date('Y') - 1;
                    $jaar_2 = date('Y', mktime(0,0,0,7,1-1,date('Y')) );
                    
                    $dir = $jaar_1 . $jaar_2;
                }
            }
            
            if( !empty( $dir ) )
            {
                $dir .= "/";
            }
            
            echo "Coda verrichting is gekoppeld aan factuur : <a href='facturen/".$dir. $fact->cf_file . "' target='_blank'>" . $fact->cf_file . "</a> van " . changeDate2EU( $fact->cf_date ) ;
        }else
        {
            echo "<tr><td colspan='3' style='background-color:red;' >";
        }
        
        $q_aant_p = mysqli_query($conn, "SELECT * FROM tbl_projects WHERE cus_id = " . $rij->cus_id);
        $aant_p = mysqli_num_rows($q_aant_p);
        
        $con = " cf_cus_id = " . $rij->cus_id;
        
        if( $aant_p > 0 )
        {
            $p = mysqli_fetch_object($q_aant_p);
            $con = " cf_cus_id IN(".$rij->cus_id.",".$p->project_id.") ";
        }
        
        $q_factuur = "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND ". $con ." ORDER BY cf_date DESC";
        //$q_factuur = "SELECT * FROM kal_customers_files WHERE cf_soort = 'factuur' AND cf_cus_id = " . $rij->cus_id . " ORDER BY cf_date DESC";
        $q_factuur = mysqli_query($conn, $q_factuur) or die( mysqli_error($conn) ." " . __LINE__ );
        
        $aant_facturen = mysqli_num_rows($q_factuur);
        
        echo "<form method='post' name='frm_fac_".$rij->id."' id='frm_fac_".$rij->id."' style='display:inline;' >";
        echo "<select name='sel_fac' id='sel_fac'>";
        
        while( $fac = mysqli_fetch_object($q_factuur) )
        {
            $fac_date = explode("-", $fac->cf_date);
            $mk_fac_date = mktime(0,0,0,$fac_date[1],$fac_date[2],$fac_date[0]);
            $begin_nw_bj = mktime(0,0,0,7,1,2012);
            
            //echo "<br>" . $mk_fac_date . "<" . $begin_nw_bj;
            $dir = "";
    		if( $mk_fac_date < $begin_nw_bj )
            {
                //$dir = "20112012";  
            }else
            {
                // bepalen van de dir.
                $nw_boek_jaar = "01-07";
                $mk_nw_boek_jaar = mktime(0,0,0,7,1,0);
                $mk_nu = mktime(0,0,0,date('m'),date('d'),0);
                
                
                if( $mk_nu >= $mk_nw_boek_jaar )
                {
                    //echo "<br> NA 01-07";
                    $jaar_1 = date('Y') + 1;
                    $jaar_2 = date('Y', mktime(0,0,0,7,1-1,$jaar_1) );
                    $dir = date('Y') . $jaar_2;    
                }else{
                    //echo "<br> VOOR 01-07";
                    $jaar_1 = date('Y') - 1;
                    $jaar_2 = date('Y', mktime(0,0,0,7,1-1,date('Y')) );
                    
                    $dir = $jaar_1 . $jaar_2;
                }
            } 
    
            echo "<option value='". $fac->cf_id ."'> (Bj : ". $dir .") " . $fac->cf_file . " - &euro;" . number_format($fac->cf_bedrag,2,",", " ") . "</value>";
            
            if( $fac->cf_bedrag == $bedrag_coda )
            {
                $coda_id_bedrag = $fac->cf_id;
                $coda_id_bedrag_aantal++;
            }
            
            if( $aant_facturen == 1 )
            {
                $coda_id_bedrag = $fac->cf_id;
            }
        }
        
        
        echo "</select>";
        
        echo "<input type='submit' name='koppel' id='koppel' value='koppel' />";
        echo "<input type='hidden' name='coda_id' id='coda_id' value='". $rij->id ."' />";
        echo "Coda opslitsen in ";
        echo "<select name='sel_coda' id='sel_coda' >";
        
        for($i=2;$i<40;$i++)
        {
            echo "<option value='". $i ."'>". $i ."</option>";
        }
        
        echo "</select>";
        echo "<input type='submit' name='splits' id='splits' value='Verder' />";
        echo "</form>";
        
        if( isset( $_GET["splits"] ) && $_GET["splits"] == "Verder" )
        {
            echo "<form name='frm_check_splits' method='post'>";
            
            for($i=1;$i<=$_GET["sel_coda"];$i++)
            {
                echo "<br>" . $i . ". <input type='text' onkeyup='commadot(this);' onkeypress='return isNumberKey(event);' name='waarde[]' id='waarde_". $i ."' />";
                
            }
            
            echo "<input type='hidden' name='coda_id' id='coda_id' value='". $rij->id ."' />";
            echo "<input type='hidden' name='splits' id='splits' value='Verder' />";
            echo "<input type='hidden' name='sel_coda' id='sel_coda' value='".$_GET["sel_coda"]."' />";
            echo "<input type='submit' name='check_bedrag' id='check_bedrag' value='Splits' />";
            echo "</form>";
        }
        
        if( isset( $_GET["check_bedrag"] ) && $_GET["check_bedrag"] == "Splits" )
        {
            $tot_waarde_splits = 0;
            
            if( is_array( $_SESSION["solarlogs_coda"] ) )
            {
                foreach( $_SESSION["solarlogs_coda"] as $waarde )
                {
                    $tot_waarde_splits += $waarde;
                }
            }
            
            $coda = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_coda WHERE id = " . $_GET["coda_id"]));
            
            if( number_format( $coda->bedrag, 2, ".", "" ) == number_format( $tot_waarde_splits, 2, ".", "" ) )
            {
                echo "De bedragen komen overeen : " . $coda->bedrag . " = " . $tot_waarde_splits;
                echo "<br>Er zijn ". $_GET["sel_coda"] ." nieuwe CODA's gemaakt, en de originele CODA is weg.";
                
                $teller_i = 0;
                foreach( $_SESSION["solarlogs_coda"] as $waarde )
                {
                    $teller_i++;
                    
                    echo "<br>" . $teller_i;
                    
                    $q_ins = "INSERT INTO kal_coda(cus_id,
                                                   boek_dat,
                                                   bedrag,
                                                   med1,
                                                   med2,
                                                   med3,
                                                   ref_cl,
                                                   naam,
                                                   filename) 
                                            VALUES(". $coda->cus_id.",
                                                  '". $coda->boek_dat ."',
                                                  '". $waarde ."',
                                                  '". $coda->med1 ." Opgeslits ". $teller_i ."/".$_GET["sel_coda"]." ',
                                                  '". $coda->med2 ."',
                                                  '". $coda->med3 ."',
                                                  '". $coda->ref_cl ."',
                                                  '". $coda->naam ."',
                                                  '". $coda->filename ."')";
                                                  
                    mysqli_query($conn, $q_ins) or die( mysqli_error($conn) );

                }
                
                // verwijderen van de echte coda.
                
                $q_del = "DELETE FROM kal_coda WHERE id = " . $_GET["coda_id"] . " LIMIT 1";
                mysqli_query($conn, $q_del);
                
                unset( $_SESSION["solarlogs_coda"] );
                
                echo "<br/><a href='klanten_coda.php?klant_id=". $coda->cus_id ."'><b style='color:white;' >Klik hier om de wijzigingen te zien</b></a>";
                
            }else
            {
                echo "De bedragen komen niet overeen : " . $coda->bedrag . " != " . $tot_waarde_splits;
            }
        }
        
        
        echo "</td></tr>";
        
        
        if( $rij->cf_id_fac == 0 )
        {
            echo "<tr><td colspan='3'>";
            
            if( $coda_id_bedrag_aantal == 1 )
            {
                $q_upd = "UPDATE kal_coda SET cf_id_fac = ". $coda_id_bedrag ." WHERE id = " . $rij->id;
                mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
                
                echo "Is automatisch gekoppeld";
                //echo "<br>Kan automatisch gekoppeld worden." . $coda_id_bedrag . "   " . $rij->cf_id_fac . " " . $coda_id_bedrag_aantal;
            }else
            {
                echo "Kan NIET automatisch gekoppeld worden. Er zijn " . $coda_id_bedrag_aantal . " verrichtingen gevonden met het zelfde bedrag.";
            }
            
            echo "</td></tr>";
        }
        
        // als er maar 1 factuur is
        if( $aant_facturen == 1 )
        {
            /*
            echo "<tr><td colspan='3'>";
            echo "Er is maar 1 factuur gevonden. Al de CODA-verrichtingen zijn met dit factuur gekoppeld.";
            
            $q_upd = "UPDATE kal_coda SET cf_id_fac = ". $coda_id_bedrag ." WHERE id = " . $rij->id;
            //echo $q_upd;
            mysqli_query($conn, $q_upd) or die( mysqli_error($conn) );
            
            echo "</td></tr>";
            */
        }
        
        echo "</table><br/>";    
        echo "</div>";
    }
}

echo "<b>Totaal : € " . number_format($totaal, 2, ",", " ") . "</b>";

?>