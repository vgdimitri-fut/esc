function recalc_mon()
{
    document.getElementById("mon_ops_brons").value = 0;
    document.getElementById("mon_ops_zilver").value = 0;
    document.getElementById("mon_mnd_brons").value = 0;
    document.getElementById("mon_mnd_zilver").value = 0;
    document.getElementById("mon_mnd_goud").value = 0;
    document.getElementById("mon_mnd_platinum").value = 0;

    document.forms["frm_go"].submit();
}

function checkindubox(dit)
{
    if (dit.checked == true)
    {
        document.getElementById("tbl_gsm").style.display = "block";
    } else
    {
        document.getElementById("tbl_gsm").style.display = "none";
    }
}

function toonDubbeleKlant()
{
    if (document.getElementById("dubbele_klant").style.display == "none")
    {
        document.getElementById("dubbele_klant").style.display = "block";
        document.getElementById("switch_dubbele_klant").style.fontWeight = "800";
        document.getElementById("switch_dubbele_klant").innerHTML = "Verberg";
    } else
    {
        document.getElementById("dubbele_klant").style.display = "none";
        document.getElementById("switch_dubbele_klant").style.fontWeight = "800";
        document.getElementById("switch_dubbele_klant").innerHTML = "Toon";
    }
}

function addFac(cus_id)
{
    window.open('klanten_add.php?cus_id=' + cus_id + '', 'new_window1', 'status=1,scrollbars=1,resizable=0,menu=no,width=1050,height=440');
}

function showHide(cus_id)
{
    if (document.getElementById("id_" + cus_id).style.display == "none")
    {
        document.getElementById("id_" + cus_id).style.display = "block";
    } else
    {
        document.getElementById("id_" + cus_id).style.display = "none";
    }
}

function checkSchaduw(dit)
{
    if (dit.checked == false)
    {
        document.getElementById("table_schaduw").style.display = 'none';
    } else
    {
        document.getElementById("table_schaduw").style.display = 'block';
    }
}

var XMLHttpRequestObject4A = false;

try {
    XMLHttpRequestObject4A = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject4A = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject4A = false;
    }

    if (!XMLHttpRequestObject4A && window.XMLHttpRequest) {
        XMLHttpRequestObject4A = new XMLHttpRequest();
    }
}

function uitgebreidZoek(id)
{

    var uz_referte = document.getElementById("z_ref").value;
    var uz_naam = document.getElementById("z_naam").value;
    var uz_bedrijf = document.getElementById("z_bedrijf").value;
    var uz_straat = document.getElementById("z_straat").value;
    var uz_huisnummer = document.getElementById("z_nr").value;
    var uz_postcode = document.getElementById("z_postcode").value;
    var uz_gemeente = document.getElementById("z_gemeente").value;
    var uz_email = document.getElementById("z_email").value;
    var uz_bank = document.getElementById("z_bank").value;
    var uz_telgsm = document.getElementById("z_telgsm").value;
    var uz_pvz = document.getElementById("z_pvz").value;
    var uz_mb = document.getElementById("z_mb").value;
    var url = "klanten/klanten_uitgebreid_zoeken.php";
    // parameters doorgeven
    var params = "referte=" + uz_referte + "&naam=" + uz_naam + "&bedrijf=" + uz_bedrijf + "&straat=" + uz_straat + "&huisnummer=" + uz_huisnummer + "&postcode=" + uz_postcode + "&gemeente=" + uz_gemeente + "&email=" + uz_email + "&bank=" + uz_bank + "&telgsm=" + uz_telgsm + "&pvz=" + uz_pvz + "&mb=" + uz_mb;
    XMLHttpRequestObject4A.open("POST", url, true);

    //Send the proper header information along with the request
    XMLHttpRequestObject4A.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=utf-8");
    XMLHttpRequestObject4A.setRequestHeader("Content-length", params.length);
    XMLHttpRequestObject4A.setRequestHeader("Connection", "close");

    XMLHttpRequestObject4A.onreadystatechange = function() {//Call a function when the state changes.
        if (XMLHttpRequestObject4A.readyState == 4 && XMLHttpRequestObject4A.status == 200) {
            res_uitgebreid_zoeken.innerHTML = XMLHttpRequestObject4A.responseText;
        } else
        {
            res_uitgebreid_zoeken.innerHTML = "<br/><br/><img src='images/indicator.gif' /> Loading...";
        }
    }
    XMLHttpRequestObject4A.send(params);
}

var XMLHttpRequestObject5A = false;

try {
    XMLHttpRequestObject5A = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject5A = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject5A = false;
    }

    if (!XMLHttpRequestObject5A && window.XMLHttpRequest) {
        XMLHttpRequestObject5A = new XMLHttpRequest();
    }
}
function bankCalculator()
{
    var iban = document.getElementById("iban").value;
    var url = "klanten/klanten_calculator.php";
    // parameters doorgeven
    var params = "iban=" + iban;
    XMLHttpRequestObject5A.open("POST", url, true);

    //Send the proper header information along with the request
    XMLHttpRequestObject5A.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=utf-8");
    XMLHttpRequestObject5A.setRequestHeader("Content-length", params.length);
    XMLHttpRequestObject5A.setRequestHeader("Connection", "close");

    XMLHttpRequestObject5A.onreadystatechange = function() {//Call a function when the state changes.
        if (XMLHttpRequestObject5A.readyState == 4 && XMLHttpRequestObject5A.status == 200) {
            // after <br/>, get bank, bic , iban
            //alert(XMLHttpRequestObject5A.responseText);
            var bankinfo = XMLHttpRequestObject5A.responseText;
            // zet waardes in een array na comma
            var bankarray = bankinfo.split(',');
            // als geen ongeldige bankaccount
            if (bankarray[2].trim() !== "not a bankaccount")
            {
                if(bankarray[0].indexOf('safe_mode') > 0){
                    var n = bankarray[0].lastIndexOf("<br />");
                    document.getElementById("banknaam").value = bankarray[0].substring(n+6);
                }else{
                    document.getElementById("banknaam").value = bankarray[0];
                }
                document.getElementById("bic").value = bankarray[1].trim();
                document.getElementById("iban").value = bankarray[2].trim();
                if (bankarray[3].trim() != 'undefined')
                {
                    document.getElementById("reknr").value = bankarray[3].trim();
                }
            } else {
                // ongeldig bankaccount
                alert(bankarray[2].trim());
            }
        } else
        {
            bic.value.innerHTML = "<br/><br/><img src='images/indicator.gif' /> Loading...";
        }
    }
    XMLHttpRequestObject5A.send(params);
    return false;
}
var XMLHttpRequestObject3 = false;

try {
    XMLHttpRequestObject3 = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject3 = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject3 = false;
    }

    if (!XMLHttpRequestObject3 && window.XMLHttpRequest) {
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
    if (hoek_zuiden != '' && hoek_p != '')
    {
        DIVOK = "kwhkwp";
        datasource = "klanten_ajax_kwhkwp.php?hoek_z=" + hoek_zuiden + "&hoek_p=" + hoek_p + "&cus_id=" + cus_id + "&schaduw=" + schaduw + "&schaduw_w=" + schaduw_w + "&schaduw_z=" + schaduw_z + "&schaduw_lh=" + schaduw_lh;

        if (XMLHttpRequestObject3) {
            var obj = document.getElementById(DIVOK);

            XMLHttpRequestObject3.open("GET", datasource, true);
            XMLHttpRequestObject3.onreadystatechange = function() {
                if (XMLHttpRequestObject3.readyState == 4 && XMLHttpRequestObject3.status == 200)
                {
                    if (parseInt(kwhkwp) == parseInt(XMLHttpRequestObject3.responseText))
                    {
                        alert("De ingevulde en berekende opbrengstfactor zijn hetzelfde.");
                    } else
                    {
                        var antwoord = confirm("De huidige waarde is " + kwhkwp + "\nDe berekende waarde is " + XMLHttpRequestObject3.responseText + "\n\nDe berekende waarde overnemen?");
                        if (antwoord)
                        {
                            obj.value = XMLHttpRequestObject3.responseText;
                        }
                    }
                }
            }

            XMLHttpRequestObject3.send(null);
        }
    } else
    {
        alert("Voor het berekenen van de opbrengstfactor zijn de 2 onderstaande velden verplicht.\n- Hoek van de panelen met het zuiden.\n- Hoek van de panelen");
    }

}

var XMLHttpRequestObject4 = false;
try {
    XMLHttpRequestObject4 = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject4 = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject4 = false;
    }

    if (!XMLHttpRequestObject4 && window.XMLHttpRequest) {
        XMLHttpRequestObject4 = new XMLHttpRequest();
    }
}

function ajax_refresh(id, datasource)
{
    if (XMLHttpRequestObject4) {
        var obj = document.getElementById(id);

        XMLHttpRequestObject4.open("GET", datasource, true);
        XMLHttpRequestObject4.onreadystatechange = function() {
            if (XMLHttpRequestObject4.readyState == 4 && XMLHttpRequestObject4.status == 200)
            {
                obj.innerHTML = XMLHttpRequestObject4.responseText;
            }
        }
        XMLHttpRequestObject4.send(null);
    }
}

function check_stuur_mail_offerte(cus_id, cf_id)
{
    var email = document.getElementById("email").value;

    if (email == '')
    {
        alert("Er is geen email adres ingevuld.");
    } else
    {
        var antwoord = confirm("Offerte werkelijk versturen?");

        if (antwoord)
        {
            window.open("mail_offerte.php?cus_id=" + cus_id + "&file=" + cf_id, "mail_offerte", "width=100,height=100");
            document.getElementById("go_away_mail").style.display = "inline";

            $(function() {
                $("#go_away_mail").fadeOut(5000);
            });
        }
    }
}

function check_stuur_mail_offerte_mon(cus_id, cf_id)
{
    var email = document.getElementById("email").value;

    if (email == '')
    {
        alert("Er is geen email adres voor verslag ingevuld.");
    } else
    {
        var antwoord = confirm("Offerte werkelijk versturen?");

        if (antwoord)
        {
            window.open("mail_offerte.php?cus_id=" + cus_id + "&file=" + cf_id, "mail_offerte", "width=100,height=100");
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

    if (email == '')
    {
        alert("Er is geen email adres ingevuld.");
    } else
    {
        var antwoord = confirm("Mail werkelijk versturen?");

        if (antwoord)
        {
            window.open("mail_offerte_info.php?cus_id=" + cus_id + "&file=" + soort, "mail_offerte", "width=100,height=100");
            document.getElementById("go_away_mail").style.display = "inline";

            $(function() {
                $("#go_away_mail").fadeOut(5000);
            });
        }
    }
}

function maakOfferteMon(cus_id)
{
    var keuze_pakket = document.getElementById("mon_keuze_pakket").value;

    if (keuze_pakket != '')
    {
        document.forms["frm_go"].submit();
        window.open("klanten/maak_offerte_mon.php?cus_id=" + cus_id, "auto_offerte", "left=200,width=860,height=800");
    } else
    {
        alert("Keuze pakket : brons? zilver? goud? platinum?");
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

    if (straat == "")
    {
        fout = true;
        foutMsg += "- straat\n";
    }

    if (nr == "")
    {
        fout = true;
        foutMsg += "- huisnr\n";
    }

    if (postcode == "")
    {
        fout = true;
        foutMsg += "- postcode\n";
    }

    if (gemeente == "")
    {
        fout = true;
        foutMsg += "- gemeente\n";
    }
    // TEL AAAAA
    if (tel == "" && gsm == "")
    {
        fout = true;
        foutMsg += "- Telefoon en/of GSM \n";
    }

    if (aant_panelen == "")
    {
        fout = true;
        foutMsg += "- Aantal panelen\n";
    }

    if (soortp == "")
    {
        fout = true;
        foutMsg += "- Gewone of zwarte panelen? \n";
    }

    if (w_panelen == "")
    {
        fout = true;
        foutMsg += "- Vermogen per paneel\n";
    }

    if (factor == "" || factor == 0)
    {
        fout = true;
        foutMsg += "- Opbrengst factor\n";
    }

    /*
     if( factor == "" )
     {
     fout = true;
     foutMsg += "- Opbrengst factor\n";
     }
     */
    if (nr == "")
    {
        fout = true;
        foutMsg += "- nr\n";
    }

    if (hoek_zuiden == "" || hoek_zuiden == 0)
    {
        fout = true;
        foutMsg += "- Hoek panelen met het zuiden:\n";
    }

    if (hoek_panelen == "" || hoek_panelen == 0)
    {
        fout = true;
        foutMsg += "- Hoek van de panelen\n";
    }

    if (dak == 0)
    {
        fout = true;
        foutMsg += "- Soort dak\n";
    }

    if (woning5j == 2)
    {
        fout = true;
        foutMsg += "- Woning ouder dan 5 jaar ?\n";
    }

    if (opwoning == 2)
    {
        fout = true;
        foutMsg += "- Panelen op woning\n";
    }

    if (fout == false)
    {
        window.open("maak_offerte.php?cus_id=" + cus_id, "auto_offerte", "left=200,width=860,height=800");
    } else
    {
        alert(foutMsg);
    }
}

function isNumberKey(evt)
{
    var charCode = (evt.which) ? evt.which : evt.keyCode;

    if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode != 46 && charCode != 44)
        return false;

    return true;
}

function commadot(that) {
    if (that.value.indexOf(",") >= 0)
    {
        that.value = that.value.replace(/\,/g, ".");
    }
}

function selectAlles(FieldName, dit)
{
    var CheckValue = dit.checked;

    var objCheckBoxes = document.forms["frm_factuur"].elements[FieldName];
    if (!objCheckBoxes)
        return;
    var countCheckBoxes = objCheckBoxes.length;
    if (!countCheckBoxes)
        objCheckBoxes.checked = CheckValue;
    else
        // set the check value for all check boxes
        for (var i = 0; i < countCheckBoxes; i++)
            objCheckBoxes[i].checked = CheckValue;
}

function toonElec(dit)
{
    if (dit.value == 1)
    {
        // tonen
        document.getElementById("elec1").style.display = "block";
        document.getElementById("elec2").style.display = "block";
        document.getElementById("elec3").style.display = "block";
        document.getElementById("elec4").style.display = "block";
    } else
    {
        // verbergen
        document.getElementById("elec1").style.display = "none";
        document.getElementById("elec2").style.display = "none";
        document.getElementById("elec3").style.display = "none";
        document.getElementById("elec4").style.display = "none";
    }
}

function toonKlantNietTevree(dit)
{
    if (dit.value == 0)
    {
        // tonen
        document.getElementById("niet_tevree1").style.display = "block";
        document.getElementById("niet_tevree2").style.display = "block";

    } else
    {
        //verbergen
        document.getElementById("niet_tevree1").style.display = "none";
        document.getElementById("niet_tevree2").style.display = "none";
    }

    if (dit.value == "")
    {
        document.getElementById("niet_tevree1").style.display = "none";
        document.getElementById("niet_tevree2").style.display = "none";
    }
}

function showFacadres(dit)
{
    if (dit.checked == true)
    {
        document.getElementById("id_facadres").style.display = "block";
    } else
    {
        document.getElementById("id_facadres").style.display = "none";
    }
}

function gotoKlant(cus_id1)
{
    document.getElementById("cus_id1").value = cus_id1;
    document.getElementById("frm_overzicht").submit();
}

function checkDriefase(dit)
{
    var ac = document.getElementById("ac_vermogen").value;

    if (ac <= 5000)
    {
        document.getElementById("driefase_noodzakelijk").innerHTML = "&nbsp;Neen";
    } else
    {
        document.getElementById("driefase_noodzakelijk").innerHTML = "&nbsp;<span class='error'>Ja</span>";
    }
}

function getOfferte()
{

}

function berekenPrijs()
{
    dit = document.getElementById("woning5j");

    if (dit.value == 1 || dit.value == 0)
    {
        var prijs = parseFloat(document.getElementById("bedrag_excl").value);
        var btw = 0;

        if (dit.value == 0)
        {
            btw = 1.21;
        } else
        {
            btw = 1.06;
        }

        if (document.getElementById("btw_edit").value != "")
        {
            btw = 1.21;
        }

        prijs = prijs * btw;

        document.getElementById("id_bedrag_incl").innerHTML = "&euro; " + prijs.toFixed(2);
        ;
    }
}

function checkInOA(dit)
{
    if (dit.checked == true)
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

        switchOA("none");

    } else
    {
        // bij 
        document.getElementById("in_oa").style.display = "none";

        // acma
        document.getElementById("showhide1").style.display = "block";

        // installatie
        document.getElementById("showhide3").style.display = "none";

        if (document.getElementById("verkoop").value != '1')
        {
            // facturatie
            document.getElementById("tabel2").style.display = "none";
        }

        // opvolging
        document.getElementById("tabel4").style.display = "none";

        // offerte
        document.getElementById("showhide2").style.display = "block";

        switchOA("block");
    }
}


function switchOA(waarde)
{
    for (tel = 2; tel <= 110; tel++)
    {
        if (document.getElementById("id_off" + tel))
        {
            document.getElementById("id_off" + tel).style.display = waarde;
        }
    }
}

function viewTable2(dit)
{
    if (dit.value == 0)
    {
        document.getElementById("tabel2").style.display = "none";
        document.getElementById("tabel4").style.display = "none";
        document.getElementById("tabel3").style.display = "block";

        // gedeelte onder verkoop
        document.getElementById("showhide3").style.display = "none";
        document.getElementById("showhide4").style.display = "none";
    } else
    {
        // nakijken ofdat het huur of verkoop is.
        if (dit.value == 1)
        {
            // verkoop actief
            for (i = 1; i < 15; i++)
            {
                if (document.getElementById("verkoop" + i))
                {
                    document.getElementById("verkoop" + i).style.display = "block";
                }
            }

            // verhuur inactief
            for (i = 1; i < 15; i++)
            {
                if (document.getElementById("verhuur" + i))
                {
                    document.getElementById("verhuur" + i).style.display = "none";
                }
            }
        }

        if (dit.value == 2)
        {
            // verhuur actief
            for (i = 1; i < 15; i++)
            {
                if (document.getElementById("verhuur" + i))
                {
                    document.getElementById("verhuur" + i).style.display = "block";
                }
            }

            // verkoop inactief
            for (i = 1; i < 15; i++)
            {
                if (document.getElementById("verkoop" + i))
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
        if (document.getElementById("werk_aant_panelen").value == 0 && document.getElementById("werk_w_panelen").value == 0)
        {
            document.getElementById("werk_aant_panelen").value = document.getElementById("aant_panelen").value;
            document.getElementById("werk_w_panelen").value = document.getElementById("w_panelen").value;
            document.getElementById("werk_merk_panelen").value = document.getElementById("merk_panelen").value;
        }
    }

    if (dit.value == "")
    {
        document.getElementById("tabel3").style.display = "none";
    }
}

$(function() {
    $("#nw_offerte_datum").datepicker({dateFormat: 'dd-mm-yy'});
    $("#offerte_datum").datepicker({dateFormat: 'dd-mm-yy'});
    $("#offerte_gemaakt").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_vreg").datepicker({dateFormat: 'dd-mm-yy'});
    $("#installatie_datum").datepicker({dateFormat: 'dd-mm-yy'});
    $("#installatie_datum2").datepicker({dateFormat: 'dd-mm-yy'});
    $("#installatie_datum3").datepicker({dateFormat: 'dd-mm-yy'});
    $("#installatie_datum4").datepicker({dateFormat: 'dd-mm-yy'});
    $("#gecontacteerd").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_net").datepicker({dateFormat: 'dd-mm-yy'});
    $("#verkoop_datum").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_arei").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_arei1").datepicker({dateFormat: 'dd-mm-yy'});

    $("#offerte_besproken1").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#offerte_besproken2").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#offerte_besproken3").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#opmeting_datum").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#elec_datum").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_orderbon").datetimepicker({dateFormat: 'dd-mm-yy'});
    $("#nw_installatie_datum").datepicker({dateFormat: 'dd-mm-yy'});
    $("#installatie_aanp").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_dom").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_indienst").datepicker({dateFormat: 'dd-mm-yy'});
    $("#datum_indienst1").datepicker({dateFormat: 'dd-mm-yy'});

    $("#gsm_datum_offset").datepicker({dateFormat: 'dd-mm-yy'});
    $("#onderhoud_datum").datepicker({dateFormat: 'dd-mm-yy'});

});

jQuery(document).ready(function() {
    $("#various5").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });

    $("#various_m").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });

    $("#various6").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });

    $("#various7").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });

    $("#callhistory").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });
    $("#transactie_add").fancybox({
        'width': '80%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe',
    });
    $(".edit_trans").fancybox({
        'width': '60%',
        'height': '70%',
        'autoScale': true,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });
    
    $('tr[id^=extra_]').hide();
    // TOGGLE EXTRA DETAIL
    $("tr[id^='transactie_']").live("click",function(){
        var id = $(this).children().find('a').attr('alt');
       $('#extra_' + id).toggle();
       $.post("ajax/klanten_ajax.php", {transactie_id: id,action: 'getList'}, function(data) {
            $('#tbl_' + id).html(data);
        });
    });
    $('.delete_trans').live('click',function(){
        if(confirm("Verwijderen?")){
            var id = $(this).attr('alt');
            $.post("ajax/klanten_ajax.php", {transactie_id: id,action: 'del_cus'}, function() {
                $('#transactie_' + id).remove();
            });
        }
        return false;
    });
    $('.edit_trans').live('click',function(){
        return false;
    });
    $('.deletebank').click(function() {
        if (confirm('Verwijderen?'))
        {
            // get gsm nummer
            var bank_iban = $(this).children('img').attr('title');
            var bank_id = $(this).children('img').attr('name');
            var upd_gsm = $.post("ajax/klanten_ajax.php", {bank_iban: bank_iban, bank_id: bank_id});
            upd_gsm.done(function(data) {
                // loop alle gsm nummers
                $('.deletebank').each(function() {
                    // if nummer = data.nummer dan delete
                    if (data == $(this).children().attr('title'))
                    {
                        $(this).remove();
                    }
                });
            });
        }
        ;
        return false;
    });
    $('td .trans_pdf').live('click',function(e){
        parent().e.stopPropagation();
    });
    /****************** EINDE DELETE FOTO  *******************/
    /****************** GET KLANT ID VANUIT URL  *******************/
    function getUrlVars()
    {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    }
    /****************** EINDE GET KLANT ID VANUIT URL  *******************/
    /****************** EMAIL  *******************/
    /*****  EMAIL HTML TELEFOON  *****/
    $('.add_email').click(function() {
        var klant_cus_id = getUrlVars()["klant_id"];
        var email_aantal = parseInt($("input[name^=email]").last().attr('id').substr(6)) + 1;
        var last_input_email = $("input[name^=email]").last();
        var input_html = "  <tr>\n\
                                                <td class='klant_gegevens'><table cellpadding='0' cellspacing='0' width='100%'><tr><td class='klant_gegevens'><a href='' class='delete_email' alt=''><img src='images/delete.png' title=''/></a>E-mail " + email_aantal + ".:</td>\n\
                                                </tr></table></td><td>\n\
                                                <input type='text' style='border:2px solid green;' id='email_" + email_aantal + "' title='" + klant_cus_id + "' name='email[]' class='lengte' value='' />\n\
                                                </td>\n\
                                            </tr>";
        // voeg een veld                            
        last_input_email.parent().parent().after(input_html);
        return false;
    });
    /*****  VERWIJDEREN RECORD/HTML EMAIL  *****/
    $('.delete_email').live('click', function(event) {
        event.preventDefault();
        if (confirm('Verwijderen?'))
        {
            // database record id
            var details_id = $(this).children().attr('title');
            if (details_id != '')
            {
                // verwijder database record
                var del_email = $.post("ajax/klanten_ajax.php", {details_id: details_id, soort: '3'});
                del_email.done(function(data) {
                    // loop alle telefoon nummers
                    $('.delete_email').each(function() {
                        // if data = id record dan delete html
                        if (data == $(this).children().attr('title'))
                        {
                            // verwijder html
                            $(this).parent().parent().parent().parent().parent().parent().remove();
                        }
                    });
                });
            } else {
                // heeft geen database record dus verwijder alleen html
                $(this).parent().parent().parent().parent().parent().parent().remove();
            }
        }
        ;
        return false;
    });
    /****************** EINDE EMAIL  *******************/
    /****************** GSM  *******************/
    /*****  GSM HTML TELEFOON  *****/
    $('.add_gsm').click(function() {
        var klant_cus_id = getUrlVars()["klant_id"];
        var gsm_aantal = parseInt($("input[name^=gsm]").last().attr('id').substr(4)) + 1;
        var last_input_gsm = $("input[name^=gsm]").last();
        var input_html = "  <tr>\n\
                                                <td class='klant_gegevens'><table cellpadding='0' cellspacing='0' width='100%'><tr><td class='klant_gegevens'><a href='' class='delete_gsm' alt=''><img src='images/delete.png' title=''/></a>GSM " + gsm_aantal + ".:</td>\n\
                                                </tr></table></td><td>\n\
                                                <input type='text' style='border:2px solid green;' id='gsm_" + gsm_aantal + "' title='" + klant_cus_id + "' name='gsm[]' class='lengte' value='' />\n\
                                                </td>\n\
                                            </tr>";
        // voeg een veld           
        last_input_gsm.parent().parent().after(input_html);
        return false;
    });
    /*****  VERWIJDEREN RECORD/HTML GSM  *****/
    $('.delete_gsm').live('click', function(event) {
        event.preventDefault();
        if (confirm('Verwijderen?'))
        {
            // database record id
            var details_id = $(this).children().attr('title');
            if (details_id != '')
            {
                // verwijder database record
                var del_gsm = $.post("ajax/klanten_ajax.php", {details_id: details_id, soort: '2'});
                del_gsm.done(function(data) {
                    // loop alle telefoon nummers
                    $('.delete_gsm').each(function() {
                        // if data = id record dan delete html
                        if (data == $(this).children().attr('title'))
                        {
                            // verwijder html
                            $(this).parent().parent().parent().parent().parent().parent().remove();
                        }
                    });
                });
            } else {
                // heeft geen database record dus verwijder alleen html
                $(this).parent().parent().parent().parent().parent().parent().remove();
            }
        }
        ;
        return false;
    });
    /****************** EINDE GSM  *******************/
    /****************** TELEFOON  *******************/
    /*****  TOEVOEGEN HTML TELEFOON  *****/
    $('.add_tel').click(function() {
        var klant_cus_id = getUrlVars()["klant_id"];
        var tel_aantal = parseInt($("input[name^=tel]").last().attr('id').substr(4)) + 1;
        var last_input_tel = $("input[name^=tel]").last();
        var input_html = "  <tr>\n\
                                                <td class='klant_gegevens'><table cellpadding='0' cellspacing='0' width='100%'><tr><td class='klant_gegevens'><a href='' class='delete_tel' alt=''><img src='images/delete.png' name='teldelete' title=''/></a>Tel " + tel_aantal + ".:</td>\n\
                                                </tr></table></td><td>\n\
                                                <input type='text' style='border:2px solid green;' id='tel_" + tel_aantal + "' title='" + klant_cus_id + "' name='tel[]' class='lengte' value='' />\n\
                                                </td>\n\
                                            </tr>";
        // voeg een veld                
        last_input_tel.parent().parent().after(input_html);
        return false;
    });
    /*****  VERWIJDEREN RECORD/HTML TELEFOON  *****/
    $('.delete_tel').live('click', function(event) {
        event.preventDefault();
        if (confirm('Verwijderen?'))
        {
            // database record id
            var details_id = $(this).children().attr('title');
            if (details_id != '')
            {
                // verwijder database record
                var del_tel = $.post("ajax/klanten_ajax.php", {details_id: details_id, soort: '1'});
                del_tel.done(function(data) {
                    // loop alle telefoon nummers
                    $('.delete_tel').each(function() {
                        // if data = id record dan delete html
                        if (data == $(this).children().attr('title'))
                        {
                            // verwijder html
                            $(this).parent().parent().parent().parent().parent().parent().remove();
                        }
                    });
                });
            } else {
                // heeft geen database record dus verwijder alleen html
                $(this).parent().parent().parent().parent().parent().parent().remove();
            }
        }
        ;
        return false;
    });
    /****************** EINDE TELEFOON  *******************/
    /****************** WIJZIG  *******************/
    $('#pasaan').click(function(event) {
        //********** VALIDATIE EMAIL,GSM & TEL
        var regexEmail = /^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i;
        var regexTel = /\d{9}/;
        $("input[name^=email]").each(function() {
            if ($(this).val() != '')
            {
                if (!regexEmail.test($(this).val()))
                {
                    $(this).css('border-color', 'red');
                    event.preventDefault();
                    return false;
                } else {
                    $(this).css('border-color', 'green');
                }
            }
        });
        $("input[name^=gsm]").each(function() {
            if ($(this).val() != '')
            {
                if (!regexTel.test($(this).val()))
                {
                    $(this).css('border-color', 'red');
                    event.preventDefault();
                    return false;
                } else {
                    $(this).css('border-color', 'green');
                }
            }
        });
        $("input[name^=tel]").each(function() {
            if ($(this).val() != '')
            {
                if (!regexTel.test($(this).val()))
                {
                    $(this).css('border-color', 'red');
                    event.preventDefault();
                    return false;
                } else {
                    $(this).css('border-color', 'green');
                }
            }
        });
        //********** EINDE VALIDATIE EMAIL,GSM & TEL
    });
    /****************** EINDE WIJZIG  *******************/
});
function checkref(dit)
{
    if (dit.checked == true)
    {
        document.getElementById("ref1").style.display = "block";
        document.getElementById("ref2").style.display = "block";
        document.getElementById("ref3").style.display = "block";
        document.getElementById("ref4").style.display = "block";
    } else
    {
        document.getElementById("ref1").style.display = "none";
        document.getElementById("ref2").style.display = "none";
        document.getElementById("ref3").style.display = "none";
        document.getElementById("ref4").style.display = "none";
    }
}

//begin ajax invite
function selectAll(selectBox, selectAll) {
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

    for (i = elSel.length - 1; i >= 0; i--)
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

    if (user != 0)
    {
        var selIndex = selObj.selectedIndex;

        var elOptNew = document.createElement('option');
        elOptNew.text = selObj.options[selIndex].text;
        elOptNew.value = user;

        try {
            selObj1.add(elOptNew, null); // standards compliant; doesn't work in IE
        }
        catch (ex) {
            selObj1.add(elOptNew); // IE only
        }
    }
}
// einde ajax invite


var XMLHttpRequestObject1 = false;

try {
    XMLHttpRequestObject1 = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject1 = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject1 = false;
    }

    if (!XMLHttpRequestObject1 && window.XMLHttpRequest) {
        XMLHttpRequestObject1 = new XMLHttpRequest();
    }
}

function checkCity(dit)
{
    DIVOK = "n_gemeente";
    datasource = "klanten_ajax2.php?postcode=" + dit.value;

    if (XMLHttpRequestObject1) {
        var obj = document.getElementById(DIVOK);

        XMLHttpRequestObject1.open("GET", datasource, true);
        XMLHttpRequestObject1.onreadystatechange = function() {
            if (XMLHttpRequestObject1.readyState == 4 && XMLHttpRequestObject1.status == 200) {
                obj.value = XMLHttpRequestObject1.responseText;
            }
        }

        XMLHttpRequestObject1.send(null);
    }
}

// berekenen van ppwp
var XMLHttpRequestObject2 = false;

try {
    XMLHttpRequestObject2 = new ActiveXObject("MSXML2.XMLHTTP");
} catch (exception1) {
    try {
        XMLHttpRequestObject2 = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (exception2) {
        XMLHttpRequestObject2 = false;
    }

    if (!XMLHttpRequestObject2 && window.XMLHttpRequest) {
        XMLHttpRequestObject2 = new XMLHttpRequest();
    }
}
// einde berekenen van ppwp

$().ready(function() {
    $("#klant").autocomplete("ajax/klanten_ajax.php", {
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

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-24625187-1']);
_gaq.push(['_trackPageview']);

(function() {
    var ga = document.createElement('script');
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
})();