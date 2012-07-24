$(function() {

    // --------------------------------
    // Loading and Unloading the Iframe
    // --------------------------------
    parent.showBrowser(); // Affichage du browser dans la fenêtre parente quand browse.php est totalement chargé
    $("a.item").click(function() {
        if ($(this).closest('tr').find("img:first").attr('alt') == "dir") parent.hideBrowser(); // Quand un dossier est ouvert, on ferme la fenêtre parente, car le calcul des md5 peut être long
    });

    // ------------------------------
    // Récupération du nom du dossier
    // ------------------------------
    var fullLink = $('tr.row.one.header td.name a').attr('href'); // Récupération du lien à partir du header 'Nom de fichier'
    parent.updateChemin(decodeURIComponent(fullLink.substring(fullLink.indexOf("&dir=photos/") + 12))); // On ne garde que la fin de la chaîne après "&dir=photos/"
    // --------------------------------------
    // Récupération du click sur les fichiers
    // --------------------------------------
    $('a.item.file').click(function() {

        if ($(this).closest('tr').find("img:first").attr('alt') == "jpg") parent.displayInfoFichier($(this).html()); //Suppression de 'photos/'
        else parent.displayNoThumb();

        return false; // Afin d'éviter que l'hyperlien soit réellement éxécuté
    });

    // -----------------------------------------
    // Récupération de l'état dans Piwigo ou pas
    // -----------------------------------------
    // Fait par le client car temps masqué
    
    var md5_list = [];
    var maxNumber = 10; // Nombre max de fichiers par requête
    $("td.md5.row").each(function(index) {
        md5_list[(index - index % maxNumber) / maxNumber] += $(this).attr('id') + ';';
    });

    if (md5_list.length() > 0) { // Si il y a au moins une photo, requête
        $.each(md5_list, function(index, MD5_chain) {
            $.ajaxq("checkExist", {
                url: './../../../ws.php?format=json',
                // Remontée jusqu'à la racine de Piwigo
                data: {
                    method: 'pwg.images.exist',
                    md5sum_list: MD5_chain
                },
                datatype: 'json',
                success: function(data) {
                    var missing = 0;
                    if (jQuery.parseJSON(data).stat == "ok") { // Si la requête n'a pas échoué
                        $.each(jQuery.parseJSON(data).result, function(md5_value, resultat) {
                            if (resultat > 0) {
                                $("#" + md5_value).append('<a href="./../../../picture.php?/' + resultat + '" target="_blank" title="Photo dans Piwigo"></a>');
                                $("#" + md5_value).children("a").append('<img src="./../../../admin/themes/clear/icon/category_elements.png" height="16" width="16"/>');
                            }
                            else {
                                missing++;
                            }
                        });
                        parent.updateMissingNb(missing);
                    }
                    else {
                        alert("erreur ws");
                    }
                }
            });
        });
    }
    else {
        parent.razMissingNb();
    }
});

// Refresh de la page, non utilisé pour l'instant

function refresh() {
    window.location.href = window.location.href;
}

// Association de la fonction 'reloadDossier' de la page select
parent.reloadDossier = removeRow;

function removeRow(id) {
    $('tr[id="' + id + '"]').remove();
}