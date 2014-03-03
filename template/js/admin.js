// jQuery File Tree Plugin
// Modified by Tanguy2m from version 1.01
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC (Cory S.N. LaViska - http://abeautifulsite.net/)
$.extend($.fn, {
	fileTree: function(o) {
		// Defaults
		if( !o ) var o = {};
		if( o.root == undefined ) o.root = '';
		if( o.script == undefined ) o.script = 'jqueryFileTree.php';
		if( o.folderEvent == undefined ) o.folderEvent = 'click';
		if( o.expandSpeed == undefined ) o.expandSpeed= 500;
		if( o.collapseSpeed == undefined ) o.collapseSpeed= 250;
		if( o.expandEasing == undefined ) o.expandEasing = null;
		if( o.collapseEasing == undefined ) o.collapseEasing = null;
		if( o.multiFolder == undefined ) o.multiFolder = false;
		if( o.loadMessage == undefined ) o.loadMessage = 'Loading...';
		o.fileClick = o.fileClick || function(path,file){ alert(path+file); };
		o.treeCreated = o.treeCreated || function(){};
		
		function showTree(c, t) {
			$(c).addClass('wait');
			$(".jqueryFileTree.start").remove();
			$.post(o.script, { dir: t }, function(data) {
				$(c).find('.start').html('');
				$ul = $('<ul class="jqueryFileTree" style="display: none;"></ul>');
				
				// Création des dossiers
				$.each( data.dirs, function( key, value ) {
					$dir = $('<li class="directory collapsed"></li>').attr("id",data.path + value + "/")
						.append('<div class="dirheader">'+value+'<span class="status"></span></div>')
						.appendTo($ul);
				});
				
				// Création des fichiers
				var re = /(?:\.([^./]+))?$/;
				$.each( data.files, function( key, value ) {
					$li = $('<li class="file"></li>').attr("data-name",value.name)
						.addClass('ext_'+re.exec(value.name)[1])
						.append(value.name)
						.bind('click', function() {
							o.fileClick($(this).parent().parent().attr('id'),$(this).attr('data-name'));
						});
					if(value.process) $li.addClass('pending');
					$li.appendTo($ul);
				});		
				
				$(c).removeClass('wait').append($ul);
				if( o.root == t )
					$(c).find('UL:hidden').show();
				else
					$(c).find('UL:hidden').slideDown({ duration: o.expandSpeed, easing: o.expandEasing });
				bindTree(c);
				o.treeCreated($(c),!$(c).hasClass('directory'));
			},"json");
		}
		
		function bindTree(t) {
			$(t).find('li.directory .dirheader').bind(o.folderEvent, function() {
				$dir = $(this).parent();
				if( $dir.hasClass('collapsed') ) {
					// Expand
					if( !o.multiFolder ) {
						$dir.parent().find('UL').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
						$dir.parent().find('LI.directory').removeClass('expanded').addClass('collapsed');
					}
					$dir.find('UL').remove(); // cleanup
					showTree( $dir, $dir.attr('id') );
					$dir.removeClass('collapsed').addClass('expanded');
				} else {
					// Collapse
					$dir.find('UL').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
					$dir.removeClass('expanded').addClass('collapsed');
				}
			});
		}
		
		$(this).each( function() {
			// Loading message
			$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + o.loadMessage + '<li></ul>');
			// Get the initial file list
			showTree( $(this), o.root );
		});
	}
});

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
    $.jGrowl(message, {
        theme: 'error',
        header: titre,
        sticky: true
    });
}

function infoNotif(titre, message) {
    $.jGrowl(message, {
        theme: 'success',
        header: titre,
        life: 4000,
        sticky: false
    });
}

///////////////////////////////////////
//     Categories select filling     //
///////////////////////////////////////

function fillCategoryListbox(selector, selectedValue) {
	$.getJSON("ws.php?format=json", {
			method: "pwg.categories.getList",
			recursive: true,
			fullname: true
		},
		function(data) {
			$.each(
				data.result.categories, function(i, category) {
					var selected = null;
					if (category.id == selectedValue) {
						selected = "selected";
					}
					$("<option/>")
						.attr("value", category.id)
						.attr("selected", selected)
						.attr("data-url",category.url)
						.text(category.name)
						.appendTo(selector);
				}
			);
		}
	);
}

$(function() {
	fillCategoryListbox(".albumSelect",-1);
});

///////////////////////////////////////////////////
//    Gestion de la popup "Ajout de catégorie"   //
///////////////////////////////////////////////////

$(document).ready(function() {

    $(".addAlbumOpen").colorbox({
        inline: true,
        href: "#addAlbumForm",
        onComplete: function() {
            $("input[name=category_name]").focus();
        }
    });

    $("#addAlbumForm form").submit(function() {
        $("#categoryNameError").text("");

        $.ajax({
            url: "ws.php?format=json&method=pwg.categories.add",
            data: {
                parent: $("select[name=category_parent] option:selected").val(),
                name: $("input[name=category_name]").val()
            },
            beforeSend: function() {
                $("#albumCreationLoading").show();
            },
            success: function(html) {
                $("#albumCreationLoading").hide();

                var newAlbum = $.parseJSON(html).result.id;
                $(".addAlbumOpen").colorbox.close();
				
				/* Album selection refresh */
                $(".albumSelect").find("option").not('[value=0]').remove();
                fillCategoryListbox(".albumSelect", newAlbum);

                $("#addAlbumForm form input[name=category_name]").val('');

                return true;
            },
            error: function(XMLHttpRequest, textStatus, errorThrows) {
                $("#albumCreationLoading").hide();
                $("#categoryNameError").text(errorThrows).css("color", "red");
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
        if ($.parseJSON(data).stat == "fail") {
          errorNotif('Suppression ' + params.image, $.parseJSON(data).message);
        } else {
          var token = $.parseJSON(data).result.pwg_token;
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
                if ($.parseJSON(answer).stat == "fail") {
                  errorNotif('Suppression ' + params.image, $.parseJSON(answer).message);
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
        if ($.parseJSON(data).stat == "fail") {
          var message = $.parseJSON(data).message;
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

function processImages(args){

	args.beforeSend = args.beforeSend || function(){};
	args.complete = args.complete || function(){};
	args.noimage = args.noimage || function(){};
	args.maxNumber = args.maxNumber || 20; // Nombre max de fichiers par requête    
	
	if (args.names.length == 0) { // Cas des dossiers
		args.noimage();
		return;
	}
	
	var slices = new Array();
	var j = 0;	
	do {
		slice = args.names.slice(j*args.maxNumber,(j+1)*args.maxNumber);
		if (slice.length > 0) slices.push(slice); // Si args.names.length est un multiple de maxNumber
		j++;
	} while (slice.length == args.maxNumber);
	
	for (var i = 0; i < slices.length; i++) {
		var options = {
            url: 'ws.php?format=json',
            data: {
                method: args.service,
                prefix_path: args.prefix,
                images_paths: slices[i]
            },
            success: function(data) {
                try { // Le parseJSON peut échouer
                    if ($.parseJSON(data).stat == "ok") // Si la requête n'a pas échoué
                        args.success($.parseJSON(data).result);
                    else
                        errorNotif("Erreur "+$.parseJSON(data).err, $.parseJSON(data).message);
                }
                catch (error) {
                    errorNotif("Erreur",error);
                }
            }
        };
		if (i == 0) options.beforeSend = args.beforeSend;
		if (i == slices.length-1) options.complete = args.complete;	
        $.ajaxq("files", options);
    }
}

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

$(function() {
	// Récupération de la liste des fichiers
	$('#navigateur').fileTree({
		script: 'admin.php?page=plugin-AddFromServer',
		treeCreated: function($dossier,isRoot){		
			// Récupération de l'état dans piwigo
			path = isRoot ? '' : $dossier.attr('id');
			processImages({
				prefix: path,
				names: $.map($dossier.find('li.pending'), function(dom){
					return $(dom).text();
				}),
				service: "pwg.images.existFromPath",
				beforeSend: function(){
					console.log("Before send du dossier: "); //OBSO
					console.log($dossier); //OBSO
					$dossier.addClass('wait');
				},
				success: function(answer){
					$.each(answer, function(file_name, resultat) {
						var $fichier = $dossier.find("[data-name='"+file_name+"']");
						$fichier.removeClass("pending");
						if (resultat.id > 0) {
							$fichier.unbind("click");
							if (resultat.double == "yes") {
								$fichier.addClass('double').attr("title","Image en double");
							} else {
								//addPwgLink($li,resultat.id,resultat.url);
								$fichier.addClass("present").bind("click",function(){
									window.open(resultat.url);
								});
							}
						} else {
							$fichier.addClass("missing").attr('title','Manque dans Piwigo');
						}
					});
					updateDirStatus($dossier,isRoot);
				},
				complete: function(){
					console.log("After send du dossier: "); //OBSO
					console.log($dossier); //OBSO
					$dossier.removeClass('wait');
				},      
				//noimage: razMissingNb
			});
		}
	});
});

// --------------------------------------- //
//      Mise à jour du panel 'dossier'     //
// --------------------------------------- //

function startScan() { //OBSO
	$("span#loadingMissing").show();
}

function stopScan() { //OBSO
	$("span#loadingMissing").hide();
}

function updateMissingNb() { //OBSO
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

function updateDirStatus($dossier,isRoot) {
	if(!isRoot){
		$missing = $dossier.find("span.status:first");
		var number = $dossier.find('li.file.missing').length + $dossier.find('li.file.error').length;
		$missing.attr("id",number); //TODO: utilité ?
		if (number > 1) {
			$missing.html(" - " + number + " photos absentes du site");
			displayAddAlbum($missing);
		} else if (number == 1) {
			$missing.html(" - " + number + " photo absente du site");
			//$("fieldset#album").show();
		} else { // No missing or error
			if ($dossier.find('li.file.present').length > 0) {
				$missing.html(" - Toutes les photos sont déjà sur le site");
				//$("fieldset#album").hide();
			}
		}
	}
}

function displayAddAlbum($missing){
	$missing.parent().after($('.addToAlbum:first').clone(true));
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

	if(remaining > 1) {
		$("#nbRestant").html(remaining-1);
		$("#progressbar").progressbar({ value: (1-(remaining-1)/nbTotal)*100 });
	} else {
		$("#status.start").hide();             
		$("#status.end").empty()
			.html("Images envoyées: " + $("#browser").contents().find('td.site.error').length + " erreur(s) parmi les " +
				nbTotal + ' photos. <a href="' + $("select#albumSelect option:selected").data('url') + '" target="_blank">Afficher l\'album</a>');
		$("#status.end").show();             
		updateMissingNb(); // Inutile si on a changé de dossier mais n'est pas très lourd
	}
}

$(function() {
$("input.launch").click(function() {
    
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
            
      beforeSend: $.proxy(function() {
        $(this).removeClass("missing")
               .addClass("sending").attr('title','En cours d\'envoi');
      },$(this)),
      
		success: $.proxy(function(data) {  
			try { // Le parseJSON peut échouer
				var answer = $.parseJSON(data);    
				if (answer.stat == "ok") {// Si la requête n'a pas échoué
					var nbDerivatives = answer.result.derivatives.length;
					if (nbDerivatives > 0) {
						$.ajaxq("fichiers",{
							url: answer.result.derivatives[0] + "&ajaxload=true",
							success: $.proxy(postSending,$(this),$(this),true,answer.result.image_id,answer.result.url) //Les premiers seront les derniers
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
