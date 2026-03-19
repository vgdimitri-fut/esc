<?php 

include "../inc/db.php";

// Verwijder actie
if(isset($_POST["action"]) && $_POST["action"] == 'delete')
{
    echo $_POST["id"];
    mysqli_query($conn, "DELETE FROM kal_user_groups WHERE ug_id='". $_POST["id"] ."'");    
}

// Wijzig actie
if(isset($_POST["action"]) && $_POST["action"] == 'wijzig')
{
    ?>
        <form method='post' name='frm_edit_groep' id='frm_edit_groep'>
        <table width='500'>
            <tr>
                <td width='150' valign='top'>Name group:</td>
                <td>
                    <input type='text' class='lengte required' name='groepnaam' id='groepnaam' value='<?php echo $_POST["naam"] ?>'/>
                </td>
            </tr>
            <tr>
                <td width='150' valign="top">Type of rights:</td>
                <td>
                    <input type='text' class='lengte required' name='soortrechten' id='soortrechten' value='<?php echo $_POST["rechten"] ?>'/>
                </td>
            </tr>
            <tr>
                <td>
                    <input type='submit' name='wijzigen_groep' id='wijzigen_groep' value='Save' />
                </td>
            </tr>
        </table>
        <input type='hidden' name='tab_id' id='tab_id' value='3' />
        <input type='hidden' name='id' id='id' value='<?php echo $_POST["id"] ?>' />
    </form>
    <?php
    //mysqli_query($conn, "DELETE FROM kal_user_groups WHERE ug_id='". $_POST["id"] ."'");    
}
?>