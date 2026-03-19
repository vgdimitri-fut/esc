<?php

$q_get_naam = mysqli_fetch_object(mysqli_query($conn, "SELECT * FROM kal_instellingen"));
switch($q_get_naam->bedrijf_titel){
    case 0:
        echo "";
        return false;
    case 1:
        echo " - " .$q_get_naam->bedrijf_naam;
        return false;
    case 2:
        echo " - " .$q_get_naam->bedrijf_erp_titel;
        return false;
    case 3:
        echo " - " .$q_get_naam->bedrijf_erp_titel . " - " . $q_get_naam->bedrijf_naam;
        return false;
}

