///////////////////////////
//    Iframe loading     //
///////////////////////////

function showBrowser(size) {
    $("#browser").height(window.frames.browser.document.body.offsetHeight + 30); // Dimensionement de l'Iframe en fonction du contenu
    $("#waitBrowser").css('background-color', $("#content").css('background-color')); // Copie de la couleur de fond du thème
    $("#waitBrowser").hide();
}

function hideBrowser() { // Le dossier change dans browse.php
    razFile();
    $("#waitBrowser").show();
}

///////////////////////////
//     Notifications     //
///////////////////////////

function errorNotif(titre, message) {
    jQuery.jGrowl(message, {
        theme: 'error',
        header: titre,
        sticky: true
    });
}

function infoNotif(titre, message) {
    jQuery.jGrowl(message, {
        theme: 'success',
        header: titre,
        life: 4000,
        sticky: false
    });
}

///////////////////////////////////////////////////
//    Gestion de la popup "Ajout de catégorie"   //
///////////////////////////////////////////////////
jQuery(document).ready(function() {

    function fillCategoryListbox(selectId, selectedValue) {
        jQuery.getJSON("ws.php?method=pwg.categories.getList", {
            recursive: true,
            fullname: true,
            format: "json"
        }, function(data) {
            jQuery.each(
            data.result.categories, function(i, category) {
                var selected = null;
                if (category.id == selectedValue) {
                    selected = "selected";
                }

                jQuery("<option/>").attr("value", category.id).attr("selected", selected).text(category.name).appendTo("#" + selectId);
            });
        });
    }

    jQuery(".addAlbumOpen").colorbox({
        inline: true,
        href: "#addAlbumForm",
        onComplete: function() {
            jQuery("input[name=category_name]").focus();
        }
    });

    jQuery("#addAlbumForm form").submit(function() {
        jQuery("#categoryNameError").text("");

        jQuery.ajax({
            url: "ws.php?format=json&method=pwg.categories.add",
            data: {
                parent: jQuery("select[name=category_parent] option:selected").val(),
                name: jQuery("input[name=category_name]").val()
            },
            beforeSend: function() {
                jQuery("#albumCreationLoading").show();
            },
            success: function(html) {
                jQuery("#albumCreationLoading").hide();

                var newAlbum = jQuery.parseJSON(html).result.id;
                jQuery(".addAlbumOpen").colorbox.close();

                jQuery("#albumSelect").find("option").remove();
                fillCategoryListbox("albumSelect", newAlbum);

                /* we refresh the album creation form, in case the user wants to create another album */
                jQuery("#category_parent").find("option").remove();

                jQuery("<option/>").attr("value", 0).text("------------").appendTo("#category_parent");

                fillCategoryListbox("category_parent", newAlbum);

                jQuery("#addAlbumForm form input[name=category_name]").val('');

                jQuery("#albumSelection").show();

                return true;
            },
            error: function(XMLHttpRequest, textStatus, errorThrows) {
                jQuery("#albumCreationLoading").hide();
                jQuery("#categoryNameError").text(errorThrows).css("color", "red");
            }
        });

        return false;
    });

});

// ---------------------------//
//   Suppression de photos    //
// ---------------------------//
// Fonction permettant de 'supprimer' une image d'un dossier

function suppr(chemin,image) {
    $.ajax({
        url: 'ws.php?format=json',
        async: false, // On attend le résultat de cette requête pour continuer
        data: {
			method: 'pwg.images.deleteFromServer',
			prefix_path: chemin,
            images_paths: image
        },
        success: function(data) {
            try { // Le parseJSON peut échouer
                if (jQuery.parseJSON(data).stat == "fail") {
					var message = jQuery.parseJSON(data).message;
					if ('errors' in message) { // Le message contient un attribut errors
						for (err in message.errors) {
							errorNotif('Suppression ' + message.errors[err].file, message.errors[err].error);
						}
					} else {
						errorNotif('Suppression ' + image, message);
					}
				} else {
					infoNotif(image, 'Fichier supprimé');
				}
            }
            catch (error) {
				errorNotif('Suppression ' + image, data);
            }
        }
    });
}

$(function() {
    $('#suppr').click(function() {
        // Suppression du fichier
        suppr($("#chemin").html(),$("#cheminFichier").html());
        // Rafraîchissement de l'iframe
        reloadDossier($('#cheminFichier').html()); //C'est la fonction 'refresh' de l'Iframe
        // Remise à zéro de la zone propre à l'image
        razFile();
    });
});

// --------------------------------------- //
//      Mise à jour du panel 'fichier'     //
// --------------------------------------- //

function razFile() {
    // Suppression de la miniature
    $("#miniature a").remove();
    // Suppression du lien vers l'image
    $('#cheminFichier').html('Sélectionner une photo pour afficher un aperçu');
    $("#suppr").hide();
}

function displayNoThumb() {
    $('#cheminFichier').html("Ce n'est pas une photo");
    $("#miniature a").remove(); // Suppression de l'image existante
    $("#suppr").hide();
}

var thumb = new Image();

function buildThumbURL(size) {
   return pluginPath + 'include/thumb.php?max=' + size + '&image=' + encodeURIComponent($("#fullDir").text() + $("#cheminFichier").text()); 
}

function displayInfoFichier(filename) {
    // Affichage du chemin vers le fichier
    $('#cheminFichier').html(filename);

    if ($("#cheminFichier").hasClass("loading")) {
        $(thumb).attr('src', buildThumbURL(480));
    }
    else {
        $("#miniature a").remove(); // Suppression de l'image existante
        $("#suppr").hide();
        $("#cheminFichier").addClass('loading');

        $(thumb).load(function() { // Code exécuté à l'ouverture de l'image
            $(this).hide(); // On cache l'image par défaut      
            $("#cheminFichier").removeClass('loading');
            $('#miniature')
            // Ajout du lien vers l'image 'HD'
            .append('<a href=\"' + buildThumbURL(800) + '\" target="_blank"></a>');
            $('#miniature a').append(this); // Insertion de l'image dans le div #miniature
            $(this).fadeIn(); // Petit effet à l'ouverture de l'image
            $("#suppr").show();
        })

        .error(function() {
            errorNotif('Calcul des miniatures', 'Chargement de l\'image impossible');
            $("#cheminFichier").removeClass('loading');
        })

        // *finally*, set the src attribute of the new image to our image
        .attr('src', buildThumbURL(480));
    }
}

// --------------------------------------- //
//      Mise à jour du panel 'dossier'     //
// --------------------------------------- //

function updateMissingNb() {
    var number = $("#browser").contents().find('td.site.missing').length + $("#browser").contents().find('td.site.error').length;
    $(".titrePage h2").attr("id",number);
    if (number > 1) $(".titrePage h2").html(number + " photos de ce dossier absentes de Piwigo");
    else if (number == 1) $(".titrePage h2").html(number + " photo de ce dossier absente de Piwigo");
    else $(".titrePage h2").html("Toutes les photos de ce dossier sont déjà dans Piwigo");
}

function razMissingNb() {
    $(".titrePage h2").empty();
}

function updateChemin(path) {
    $("#chemin").html(path);
}

// --------------------------------------- //
//         Ajout des photos au site        //
// --------------------------------------- //

$(function() {
$("input#launch").click(function() {
    
  var nbTotal = $(".titrePage h2").attr("id");
  
  $("fieldset#album").hide();
  $("p#submit").hide();
  $("#nbRestant").html(nbTotal);
  $("#nbTotal").html(nbTotal);
  $("fieldset#progress").show();
  
  $("#browser").contents().find('td.site.missing').each(function (index) {
    
    var image_name = $(this).closest('tr').find('a.item.file').text();
    var category_id = $("select#albumSelect option:selected").val();
    
    $.ajaxq("fichiers",{
      url: 'ws.php?format=json',
      data: { method: 'pwg.images.addFromServer',
              image_path: $("#fullDir").text() +  image_name,
              category: category_id,
              level: $("select#level option:selected").val(),
              tags: systematic_tag //Variable déclarée dans select.tpl
			},
            
     beforeSend: jQuery.proxy(function() {
		$(this).removeClass("missing")
        .addClass("sending").attr('title','En cours d\'envoi');
     },$(this)),
      
      datatype: 'json',
      
      success: jQuery.proxy(function(data) {
        var status = jQuery.parseJSON(data).stat;
        
        $(this).removeClass("sending"); // Dans tous les cas on supprime l'état "sending"
        
        if (status == "ok") // Si la requête n'a pas échoué
          document.getElementById('browser').contentWindow.addPwgLink($(this),jQuery.parseJSON(data).result.image_id);
        else {
          $(this).addClass("error")
          .attr('title','Erreur lors du transfert');
          errorNotif(image_name, jQuery.parseJSON(data).message);
		}
        
		var remaining = parseInt($("#nbRestant").html());
		if(remaining > 1)
			$("#nbRestant").html(remaining-1);
		else {
            $("#status.start").hide();
            
            $("#status.end").empty()
            .html("Images envoyées: " + $("#browser").contents().find('td.site.error').length + " erreur(s) parmi les " +
              nbTotal + ' photos. <a href="index.php?/category/' + category_id + '" target="_blank">Afficher l\'album</a>');
            $("#status.end").show();
            
            updateMissingNb(); // Inutile si on a changé de dossier mais n'est pas très lourd
        }
      },$(this))
      
    });
  });
});
});

// Reset du bas de la page si un autre dossier est affiché
function reset() {
    if( $('#status.end').is(':visible') ) { // L'upload est terminé
        $("fieldset#progress").hide();
        $("#status.end").hide();
        $("#status.start").show();
        $("fieldset#album").show();
        $("p#submit").show();      
    }
}

// ---------------------------------------- //
// Empêcher la fermeture si upload en cours //
// ---------------------------------------- //

window.onbeforeunload = function() {
    if ($("#browser").contents().find('td.site.sending').length > 0)
        return 'Un ajout de photos est en cours, voulez-vous vraiment quitter la page?';
}
