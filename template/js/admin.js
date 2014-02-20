///////////////////////////
//    Iframe loading     //
///////////////////////////

function showBrowser(size) {
    stopScan();
    $("#browser").height(window.frames.browser.document.body.offsetHeight + 30); // Dimensionement de l'Iframe en fonction du contenu
    $("#waitBrowser").css('background-color', $("#content").css('background-color')); // Copie de la couleur de fond du thème
    $("#waitBrowser").hide();
}

function hideBrowser() { // Le dossier change dans browse.php
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

// Suppression d'une photo à partir de son ID
function supprFromID(params) {
    
  params.image = params.image || '';
  //params.id mandatory
  params.success = params.success || function(){};
  
  $.ajax({
    url: 'ws.php?format=json',
    data: { method: 'pwg.session.getStatus' },
    success: function(data) {
      try { // Le parseJSON peut échouer
        if (jQuery.parseJSON(data).stat == "fail") {
          errorNotif('Suppression ' + params.image, jQuery.parseJSON(data).message);
        } else {
          var token = jQuery.parseJSON(data).result.pwg_token;
          $.ajax({
            url: 'ws.php?format=json',
            type: "POST",
            data: {
              method: 'pwg.images.delete',
              image_id: params.id,
              pwg_token: token
            },
            success: function(answer) {
              try { // Le parseJSON peut échouer
                if (jQuery.parseJSON(answer).stat == "fail") {
                  errorNotif('Suppression ' + params.image, jQuery.parseJSON(answer).message);
                } else {
                  infoNotif(params.image, 'Fichier supprimé');
                  params.success();
                  updateMissingNb();
                }
              }
              catch (error) {
                errorNotif('Suppression ' + params.image, answer);
              }
            }
          });
        }
      }
      catch (error) {
        errorNotif('Suppression ' + params.image, data);
      }
    }
  });
}

// Suppression d'une image à partir de son chemin
function supprFromPath(params) {
    
  params.path = params.path || '';
  //params.image mandatory
  params.success = params.success || function(){};
  
  $.ajax({
    url: 'ws.php?format=json',
    data: {
      method: 'pwg.images.deleteFromServer',
      prefix_path: params.path,
      images_paths: params.image
    },
    success: function(data) {
      try { // Le parseJSON peut échouer
        if (jQuery.parseJSON(data).stat == "fail") {
          var message = jQuery.parseJSON(data).message;
          if ('errors' in message) { // Le message contient un attribut errors
            for (var err in message.errors) {
              errorNotif('Suppression ' + message.errors[err].file, message.errors[err].error);
            }
          } else {
            errorNotif('Suppression ' + params.image, message);
          }
        } else {
          infoNotif(params.image, 'Fichier supprimé');
          params.success();
          updateMissingNb();
        }
      }
      catch (error) {
        errorNotif('Suppression ' + params.image, data);
      }
    }
  });
}

// --------------------------------------- //
//      Mise à jour du panel 'fichier'     //
// --------------------------------------- //

function removeThumb(){
	$("#thumb").hide();
}

function displayThumb(path,e){
	$("#thumb").hide();
	$("#thumbName").html(decodeURIComponent(path.substring(path.lastIndexOf('/') + 1)));
	$("#thumb").show();
	$("#thumb img").load(function() { // Code exécuté à l'ouverture de l'image
		positionThumb(e);
		$(this).fadeIn("medium");
    })
	.attr('src',pluginPath +"template/browse.php?thumb="+ path);
}

function positionThumb(e){
	xOffset = 35;
	yOffset = 35;
	$("#thumb").css("left",(e.clientX + xOffset) + "px");
  
	$("#thumb").css("bottom","auto").css('top', 'auto');
	if(e.clientY + yOffset + $("#thumb").height() > $("#origine").height()) {
		$("#thumb").css("bottom","0");
	} else {
		$("#thumb").css("top", (e.clientY + yOffset) + "px");
	}
}

// --------------------------------------- //
//      Mise à jour du panel 'dossier'     //
// --------------------------------------- //

function startScan() {
	$("span#loadingMissing").show();
}

function stopScan() {
	$("span#loadingMissing").hide();
}

function updateMissingNb() {
    var number = $("#browser").contents().find('td.site.missing').length + $("#browser").contents().find('td.site.error').length;
    $("span.missing").attr("id",number);
    if (number > 1) {
      $("span.missing").html(" - " + number + " photos absentes du site");
      $("fieldset#album").show();
    } else if (number == 1) {
      $("span.missing").html(" - " + number + " photo absente du site");
      $("fieldset#album").show();
    } else { // No missing or error
	  if ($("#browser").contents().find('td.site.present').length > 0) {
        $("span.missing").html(" - Toutes les photos sont déjà sur le site");
        $("fieldset#album").hide();
	  } else {
	    razMissingNb();
	  }
    }
}

function razMissingNb() {
    $("span.missing").empty();
    $("fieldset#album").hide();
}

function changeFolder(folder) {
  $.ajaxq.clear("fichiers");
  hideBrowser();
  $("#browser").attr("src",pluginPath+"template/browse.php?dir=photos/"+folder);
}

function updateChemin(path) {

  $("#chemin").empty();

  var folders = path.split("/");
  
  $.each(folders, function(index, folder) {
    if(folder) {
      $("#chemin").append("<a onclick='changeFolder(\""+folders.slice(0,index+1).join("/")+"\");'>"+folder+"</a>/");
    }
  });

}

// --------------------------------------- //
//         Ajout des photos au site        //
// --------------------------------------- //

function postSending(cell,success,image_id,url){

	cell.removeClass("sending");
	if (success) {	
		document.getElementById('browser').contentWindow.addPwgLink(cell,image_id,url);
	} else {
		cell
			.addClass("error")
			.attr('title','Erreur lors du transfert');
	}
	
	var remaining = parseInt($("#nbRestant").html());
	var nbTotal = $("span.missing").attr("id");
	var category_id = $("select#albumSelect option:selected").val();

	if(remaining > 1) {
		$("#nbRestant").html(remaining-1);
		$("#progressbar").progressbar({ value: (1-(remaining-1)/nbTotal)*100 });
	} else {
		$("#status.start").hide();             
		$("#status.end").empty()
			.html("Images envoyées: " + $("#browser").contents().find('td.site.error').length + " erreur(s) parmi les " +
				nbTotal + ' photos. <a href="index.php?/category/' + category_id + '" target="_blank">Afficher l\'album</a>');
		$("#status.end").show();             
		updateMissingNb(); // Inutile si on a changé de dossier mais n'est pas très lourd
	}
}

$(function() {
$("input#launch").click(function() {
    
  var nbTotal = $("span.missing").attr("id");
  
  $("fieldset#album").hide();
  $("#nbRestant").html(nbTotal);
  $("#nbTotal").html(nbTotal);
  $("fieldset#progress").show();
  
  $("span.missing").html(" - Ajout des photos au site");
  
  $("#browser").contents().find('td.site.missing').each(function (index) {
    
    var image_name = $(this).closest('tr').attr('id');
    var category_id = $("select#albumSelect option:selected").val();
    
    $.ajaxq("fichiers",{
      url: 'ws.php?format=json',
      data: { method: 'pwg.images.addFromServer',
              image_path: $("#chemin").text() +  image_name,
              category: category_id,
              level: $("select[name=level] option:selected").val(),
			},
            
      beforeSend: jQuery.proxy(function() {
        $(this).removeClass("missing")
               .addClass("sending").attr('title','En cours d\'envoi');
      },$(this)),
      
		success: jQuery.proxy(function(data) {  
			try { // Le parseJSON peut échouer
				var answer = jQuery.parseJSON(data);    
				if (answer.stat == "ok") {// Si la requête n'a pas échoué
					var nbDerivatives = answer.result.derivatives.length;
					if (nbDerivatives > 0) {
						$.ajaxq("fichiers",{
							url: answer.result.derivatives[0] + "&ajaxload=true",
							success: jQuery.proxy(postSending,$(this),$(this),true,answer.result.image_id,answer.result.url) //Les premiers seront les derniers
						},true);
						for (var i=1; i < nbDerivatives; i++) {
							$.ajaxq("fichiers",{url: answer.result.derivatives[i] + "&ajaxload=true"},true);
						}
					} else {
						postSending($(this),true, answer.result.image_id, answer.result.url);
					}
				} else {
					errorNotif(image_name, answer.message);
					postSending($(this),false);
				}
			}
			catch (error) {
				errorNotif(image_name, data);
				postSending($(this),false);
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
    }
}

// ---------------------------------------- //
// Empêcher la fermeture si upload en cours //
// ---------------------------------------- //

window.onbeforeunload = function() {
    if ($("#browser").contents().find('td.site.sending').length > 0)
        return 'Un ajout de photos est en cours, voulez-vous vraiment quitter la page?';
}
