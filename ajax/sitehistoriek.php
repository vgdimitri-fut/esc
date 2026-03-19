<?php

include "../inc/db.php";
include "../inc/functions.php";

echo "<h3>Historiek</h3>";
$q_allhistoriek = mysqli_query($conn, "SELECT * FROM tbl_site_historiek ORDER BY id DESC");
if(mysqli_num_rows($q_allhistoriek) == 0)
{
    echo "Er is geen historiek beschikbaar";
}else
{
    echo "<table cellpadding='0' cellspacing='0' width='900'>";
    echo "<tr>";
//    echo "<th>Auto ID</th>";
    echo "<th>Naam</th>";
    echo "<th>Actie</th>";
    echo "<th>Datum</th>";
    echo "</tr>";
    $i=0;
    while($historiek = mysqli_fetch_object($q_allhistoriek))
    {
        $i++;
        $kleur = $kleur_grijs;
        if ($i % 2) {
            $kleur = "white";
        }
        echo "<tr style='background-color:" . $kleur . ";cursor:pointer;' onmouseover='ChangeColor(this, true, \"" . $kleur . "\");' onmouseout='ChangeColor(this, false, \"" . $kleur . "\");'>";
//        echo "<td>".$historiek->product_id."</td>";
        echo "<td>".$historiek->name."</td>";
        if($historiek->actie == '1'){$actie = 'Verzonden';}else{$actie = 'Verwijderd';}
        echo "<td>".$actie."</td>";
        echo "<td>".changeDateTime2EU($historiek->datetime)."</td>";
        echo "</tr>";
    }
    echo "</table>";
}