<?php
$bestand = mysqli_fetch_object(mysqli_query($conn, "SELECT bedrijf_foto FROM kal_instellingen"));
if($bestand->bedrijf_foto != '')
{
    echo '<img src="images/' . $bestand->bedrijf_foto . '" /><br />';
}
?>
<div id='logout'><a href='logout.php'><?php echo $_SESSION[ $session_var ]->naam; ?>
&nbsp;Uitloggen</a></div>
<a href='menu.php'>&lt;&lt;&lt; Terug</a>