<?php

include "../inc/db.php";

$bank_id = $_POST["id"];

if(isset($bank_id) && isset($_POST["delete"]))
{
    $q_deletebank = mysqli_query($conn, "DELETE FROM kal_bank WHERE id='$bank_id'");
    echo "bank is verwijderd.";
}

?>

