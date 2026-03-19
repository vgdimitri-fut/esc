<strong>Add new Solar Team</strong><br/><br/>

<form method='post' id='frm_new_cus' name='frm_new_cus' action='' enctype='multipart/form-data'>

    <table>
        <tr>
            <td>Name:</td>
            <td><input type='text' name='n_naam' id='n_naam' class='lengte' value='' />
            </td>
        </tr>

        <tr>
            <td>Company:</td>
            <td><input type='text' name='n_bedrijf' id='n_bedrijf' class='lengte'
                       value='' /></td>
        </tr>
        <tr>
            <td>VAT nr.:</td>
            <td><input type='text' name='n_btw' id='n_btw' class='lengte' value='' />
            </td>
        </tr>
        <tr>
            <td>Street &amp; Nr.:</td>
            <td><input type='text' name='n_straat' id='n_straat' value='' /> <input
                    type='text' name='n_nr' id='n_nr' value='' size='4' /></td>
        </tr>
        <tr>
            <td>Zip code &amp; city:</td>
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
                    foreach ($acma_arr as $key => $acma) {
                        if ($_SESSION[ $session_var ]->group_id == 3) {
                            if ($key == $_SESSION[ $session_var ]->user_id) {
                                echo "<option value='" . $key . "'>" . $acma . "</option>";
                            }

                            if ($_SESSION[ $session_var ]->user_id == 29 && in_array($key, $klanten_onder_frans_arr)) {
                                echo "<option value='" . $key . "'>" . $acma . "</option>";
                            }
                        } else {
                            echo "<option value='" . $key . "'>" . $acma . "</option>";
                        }

                        $exclude_klant[$key] = $key;
                    }
                    ?>
                </select></td>
        </tr>

        <tr>
            <td>Date quotation:</td>
            <td><input type='text' name='nw_offerte_datum' id='nw_offerte_datum'
                       class='lengte' value='' /></td>
        </tr>

        <tr>
            <td colspan='2' align='center'>&nbsp;</td>
        </tr>

        <tr>
            <td colspan='2' align='center'><input type='submit' name='bewaar'
                                                  id='bewaar' value='Save' /></td>
        </tr>
    </table>

    <input type='hidden' name='tab_id' id='tab_id' value='0' /></form>