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
    var md5_list = '';
    $("td.md5.row").each(function(index) {
        md5_list += $(this).attr('id') + ';';
    }); // Fait par le client car temps masqué
    if (md5_list !== '') { // Si il y a au moins une photo, requête
        $.ajax({
            url: './../../../ws.php?format=json',
            // Remontée jusqu'à la racine de Piwigo
            data: {
                method: 'pwg.images.exist',
                md5sum_list: md5_list
            },
            datatype: 'json',
            success: function(data) {
                var missing = 0;
                if (jQuery.parseJSON(data).stat == "ok") { // Si la requête n'a pas échoué
                    $("td.md5.row").each(function(index) { // Boucle sur toutes les cellules de type 'md5'
                        var result = jQuery.parseJSON(data).result[$(this).attr('id')];
                        if (result > 0) {
                            $(this).append('<a href="./../../../picture.php?/' + result + '" target="_blank" title="Photo dans Piwigo"></a>');
                            $(this).children("a").append('<img src="./../../../admin/themes/clear/icon/category_elements.png" height="16" width="16"/>');
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