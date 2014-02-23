function addPwgLink(cell,photo_ID,url) {
    cell
    .addClass('present')
    .removeAttr("title")
    .append('<a id="' + photo_ID + '" href="' + url + '" target="_blank" title="Photo dans Piwigo"></a>')
    .children("a").append('<img src="./../../../admin/themes/clear/icon/category_elements.png" height="16" width="16"/>');
}

function processImages(args){
    
    args.beforeSend = args.beforeSend || function(){};
    args.complete = args.complete || function(){};
    args.noimage = args.noimage || function(){};
    
    var maxNumber = 20; // Nombre max de fichiers par requête    

    var i= 0;
    var $slice = $(args.selector).slice(0,maxNumber);
    while ($slice.length > 0) { // Si il y a au moins une photo, requête
    
        var filesNames = new Array();
        $slice.each(function() {
            filesNames.push($(this).closest('tr').attr('id'));
        });       

        $.ajaxq("files", {
            url: './../../../ws.php?format=json', // Remontée jusqu'à la racine de Piwigo
            data: {
                method: args.service,
                prefix_path: $('tr.row.one.header').attr('id').substring(7), //Suppression de "photos/"
                images_paths: filesNames
            },
            beforeSend: $.proxy( args.beforeSend, $slice),
            success: function(data) {
                try { // Le parseJSON peut échouer
                    if (jQuery.parseJSON(data).stat == "ok") { // Si la requête n'a pas échoué
                        args.success(jQuery.parseJSON(data).result);
                    }
                    else {
                        parent.errorNotif("Erreur "+jQuery.parseJSON(data).err, jQuery.parseJSON(data).message);
                    }
                }
                catch (error) {
                    parent.errorNotif("Erreur",error);
                }
            },
            complete: args.complete
        });
        
        $slice = $(args.selector).slice((i+1)*maxNumber,(i+2)*maxNumber);
        i++;
    }
    
    if(i===0) // Cas des dossiers
        args.noimage();
}

$(function() {
    
    // Reset du parent si l'upload est fini
    parent.reset();
    
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
    parent.updateChemin($('tr.row.one.header').attr('id').substring(7)); // On ne garde que la fin de la chaîne après "&dir=photos/"
    
    // --------------------------------------
    // Récupération du click sur bouton suppr
    // --------------------------------------
    $('td.suppr img').click(function() {
        
        var path = decodeURIComponent($(this).closest('tr').find('td.name a').attr('href').substring(7)); //Suppression de 'photos/'
        var $td_site = $(this).closest('tr').find('td.site');
        
        if ($td_site.is('.pending, .sending, .error')) { // La photo est dans une phase d'ajout au site
        
          alert("Cette photo est en cours d'envoi. Impossible de la supprimer");
          
        } else if ($td_site.is('.present')) { // La photo est déjà sur le site
        
          var answer=confirm("Cette photo est déjà sur Piwigo, êtes-vous sûr de vouloir la supprimer du site?");
          if (answer === true) {
            parent.supprFromID({
                image: path,
                id: $td_site.find('a:first').attr('id'),
                success: jQuery.proxy(function() { $(this).closest('tr').remove(); }, $(this))
            });
          }
          
        } else { // La photo n'est présente que sur le NAS
          parent.supprFromPath({
              image: path,
              success: jQuery.proxy(function() { $(this).closest('tr').remove(); }, $(this))
          });
        }
    });
    
    // -----------------------------------------
    // Récupération de l'état dans Piwigo ou pas
    // -----------------------------------------       
    // Fait par le client car temps masqué
    processImages({
        selector: "td.site.pending",
        service: "pwg.images.existFromPath",
        beforeSend: parent.startScan,
        
        success: function(answer){
            $.each(answer, function(file_name, resultat) {
                var $td_site = $(document.getElementById(file_name)).find('td.site'); //Pas de jQuery pour les ID (caractères spéciaux comme '.')
                if (resultat.id > 0) {
                    $td_site.removeClass("pending");
                    if (resultat.double == "yes") {
                        $td_site
                        .addClass('double')
                        .removeAttr("title")
                        .attr("title","Image en double");
                    } else {
						addPwgLink($td_site,resultat.id,resultat.url);
                    }
                }
                else {
                    $td_site
                    .removeClass("pending")
                    .addClass("missing").attr('title','Manque dans Piwigo');
                }
            });
            parent.updateMissingNb();
        },
        
        complete: parent.stopScan,      
        noimage: parent.razMissingNb
    });
});

// Refresh de la page, non utilisé pour l'instant

function refresh() {
    window.location.href = window.location.href;
}
