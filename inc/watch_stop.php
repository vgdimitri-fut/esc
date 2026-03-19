<?php 

$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$finish = $time;
$totaltime = ($finish - $start_watch);
printf ("<br>This page took %f seconds to load.", $totaltime);

?>