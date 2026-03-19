jQuery(document).ready(function() {
    $('[class^=fac_extra_]').live('click', function() {
        var id = $(this).attr('class').substring(10);
            $.post("ajax/facturatie_ajax.php", {fac_id: id, action: 'fac_detail'}, function(data) {
                // show new tab
                $('#facturen_overzicht_' + id).html(data);
                $('#facturen_overzicht_' + id).toggle();
            });
    });
    $('[class^=fac_del_]').live('click', function() {
        var id = $(this).attr('class').substring(8);
            $.post("ajax/facturatie_ajax.php", {fac_id: id, action: 'fac_del'}, function() {
            });
        $(this).parent().hide();    
    });
    $('[class^=cn_del_').live('click', function() {
        var id = $(this).attr('class').substring(7);
        $.post("ajax/facturatie_ajax.php", {fac_id: id, action: 'cn_del'}, function() {
            });
        $(this).parent().hide();  
    });
});

