$.extend($.fn, {
	// jQuery File Tree Plugin
	// Heavily modified by tanguy2m in 2014 from version 1.01
	// This plugin is dual-licensed under the GNU General Public License and the MIT License and
	// is copyright 2008 A Beautiful Site, LLC (Cory S.N. LaViska - http://abeautifulsite.net/)
	fileTree: function(options) {
		var o = $.extend({
			root: '',
			script: 'jqueryFileTree.php',
			folderEvent: 'click',
			expandSpeed: 500,
			collapseSpeed: 250,
			fileClass: '',
			multiFolder: false,
			loadMessage: 'Loading...',
			treeCreated: $.noop, folderCollapsed: $.noop
		},options);
		
		function showTree(c, t) {
			$(c).addClass('wait');
			$(".jqueryFileTree.start").remove();
			$.post(o.script, { dir: t }, function(data) {
				$(c).find('.start').html('');
				$ul = $('<ul class="jqueryFileTree" style="display: none;"></ul>');

				// Création des dossiers
				$.each( data.dirs, function( key, value ) {
					$dir = $('<li class="directory collapsed"></li>').attr("id",data.path + value + "/")
						.append('<div class="dirheader">'+value+'</div>')
						.appendTo($ul);
				});

				// Création des fichiers
				var re = /(?:\.([^./]+))?$/;
				$.each( data.files, function( key, value ) {
					$li = $('<li class="file"></li>').attr("data-name",value.name)
						.addClass('ext_'+re.exec(value.name)[1])
						.append(value.name);
					if(value.process) $li.addClass(o.fileClass);
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
			$(t).find('li.directory').children('.dirheader').bind(o.folderEvent, function() {
				$dir = $(this).parent();
				if( $dir.hasClass('collapsed') ) { // Expand
					if( !o.multiFolder ) {
						$dir.parent().find('UL').slideUp({ duration: o.collapseSpeed });
						$folders = $dir.parent().find('LI.directory.expanded');
						$folders.removeClass('expanded').addClass('collapsed');
						o.folderCollapsed($folders);
					}
					$dir.find('UL').remove(); // cleanup
					showTree( $dir, $dir.attr('id') );
					$dir.removeClass('collapsed').addClass('expanded');
				} else { // Collapse
					$dir.find('UL').slideUp({ duration: o.collapseSpeed });
					$dir.removeClass('expanded').addClass('collapsed');
					o.folderCollapsed($dir);
				}
			});
		}
		
		$(this).each( function() {
			// Loading message
			$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + o.loadMessage + '<li></ul>');
			// Get the initial file list
			showTree( $(this), o.root );
		});
	},

	// ------------------------------- //
	// Plugins pour dossiers fileTree  //
	// ------------------------------- //
	
    addPiwigoMarkup: function() { // Ajoute à un dossier fileTree le markup pour upload Piwigo
        return this.each(function() {
			if($(this).children('.addToAlbum').length == 0) {
				$(this).children('.dirheader')
					.append('<span class="status"></span>')
					.after($('.addToAlbum:first').clone(true));
			}
        });
    },

	updateStatus: function(state) { // Met à jour le header et le panel addToAlbum d'un dossier
		return this.each(function() {
			var $ata = $(this).children('.addToAlbum');
			var $missing = $(this).children('.dirheader').children('.status');		

			if (state=="during") {
				$missing.html(" - Ajout des photos au site");
				$ata.children('.before').hide();
				$ata.children('.during').show();
				return;
			} else if (state=="after") {
				$ata.children('.during').hide();
				var nbErrors = $(this).find('.error').length;
				$ata.find('.nbErrors').html(nbErrors);
				if (nbErrors < parseInt($ata.find('.nbTotal').html()))
					$ata.find('.after a').attr('href',$ata.find('.albumSelect option:selected').data('url'));
				else
					$ata.find('.after a').hide();
				$ata.children('.after').show();
			}

			var number = $(this).children('ul').children('li.file.missing').length + $(this).children('ul').children('li.file.error').length;
			if (number > 1) {
				$missing.html(" - " + number + " photos absentes du site");
			} else if (number == 0) {
				if ($(this).children('ul').children('li.file.present').length > 0)
					$missing.html(" - Toutes les photos sont déjà sur le site");
			} else {
				$missing.html(" - 1 photo absente du site");
			}

			if (state=="before") {
				$ata.find('.nbRestant').html(number);
				$ata.find('.nbTotal').html(number);
				if (number>0) $(this).children('.addToAlbum').show();			
			}
		});
	},

	updateProgress: function(nbFichiers){ // Met à jour la progression d'un upload
		return this.each(function()
			var $ata = $(this).children('.addToAlbum');
			var remaining = parseInt($ata.find('.nbRestant').html()) - nbFichiers;
			$ata.find('.nbRestant').html(remaining);
			$ata.find('.progressbar').progressbar({ value: (1-remaining/parseInt($ata.find('.nbTotal').html()))*100 });
			if (remaining==0) $(this).updateStatus("after");
		});
	},

	reset: function() { // Remet à zéro le div addToAlbum
		return this.each(function() {
			$ata = $(this).children('.addToAlbum');
			$ata.hide();
			$ata.find('.after').hide();
			$ata.find('.before').show();
			$ata.find('.progressbar').progressbar({ value: 0 });
		});
	},

	// ------------------------------- //
	// Plugins pour fichiers fileTree  //
	// ------------------------------- //

	addPwgLink: function(url){ // Crée un lien vers la photo Piwigo
		return this.each(function() {
			$(this)
				.removeClass('cboxElement') // Désactivation de la colorbox si besoin
				.addClass('present')
				.removeAttr("title")
				.click(function(){
					window.open(url);
				});
		});
	}
});

// ---------------------------//
//        Notifications       //
// ---------------------------//

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

// ---------------------------//
//   Gestion des catégories   //
// ---------------------------//

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
	fillCategoryListbox(".albumSelect",-1); // Récupération de la liste des catégories

    $(".addAlbumOpen").colorbox({ // Création de la pop-up d'ajout d'une catégorie
        inline: true,
        href: "#addAlbumForm",
        onComplete: function() {
            $("input[name=category_name]").focus();
        }
    });

    $("#addAlbumForm form").submit(function() { // Demande d'ajout d'une nouvelle catégorie
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
function supprFromID(params) { // Obso ?
    
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
//             Gestion des images          //
// --------------------------------------- //

// Mandatory = args.directory, args.fileClass, args.service, args.prefix
function processImages(options){

	var args = $.extend({
		success: $.noop, error: $.noop, afterProcess: $.noop,
		maxNumber: 20 // Nombre max de fichiers par requête
	}, options);

	var files = args.directory.children('ul').children('li.'+args.fileClass);
	var slices = new Array(), j = 0;
	do {
		slice = files.slice(j*args.maxNumber,(j+1)*args.maxNumber);
		if (slice.length > 0) slices.push(slice); // Si files.length est un multiple de maxNumber
		j++;
	} while (slice.length == args.maxNumber);

	for (var i = 0; i < slices.length; i++) {
		var options = {
            url: 'ws.php?format=json',
            data: $.extend({
					method: args.service,
					prefix_path: args.prefix,
					images_paths: $.map(slices[i], function(dom){ return $(dom).data('name'); })
				}, args.data ),
			beforeSend: $.proxy(function(){
							$(this).removeClass(args.fileClass).addClass('wait');
						},slices[i]),
            success: $.proxy(function(data) {
				$(this).removeClass('wait');
                try { // Le parseJSON peut échouer
                    if ($.parseJSON(data).stat == "ok") { // Si la requête n'a pas échoué
						$.each($.parseJSON(data).result, function(file_name, resultat) {
							var $fichier = args.directory.children('ul').children("li[data-name='"+file_name+"']");
							args.success($fichier, resultat);
						});
                    } else {
                        errorNotif("Erreur "+$.parseJSON(data).err, $.parseJSON(data).message);
						args.error(this,$.parseJSON(data).message);
					}
                }
                catch (error) {
                    errorNotif("Erreur",error);
					args.error(this,error);
                }
            },slices[i]),
			error: $.proxy(function() {
				$(this).removeClass('wait');
				args.error(this,'Erreur HTML');
			},slices[i]),
        };
		if (i == 0)
			options.beforeSend = $.proxy(function(){
				args.directory.addClass('wait');
				$(this).removeClass(args.fileClass).addClass('wait');
			},slices[i]);
		if (i == slices.length-1) options.complete = function(){ args.afterProcess(); args.directory.removeClass('wait'); };
        $.ajaxq(args.service, options);
    }
}

$(function() {

	// Récupération de la liste des fichiers
	var fileClass = 'pending';
	$('#navigateur').fileTree({
		script: 'ws.php', // ou alors admin.php, peu importe
		fileClass: fileClass,
		treeCreated: function($dossier,isRoot){
			if(!isRoot) $dossier.addPiwigoMarkup();
			// Récupération de l'état dans piwigo
			var prefix = isRoot ? '' : $dossier.attr('id');
			processImages({
				prefix: prefix,
				directory: $dossier,
				fileClass: fileClass,
				service: "pwg.images.existFromPath",
				success: function($fichier,resultat){
					if (resultat.id > 0) {
						if (resultat.double == "yes")
							$fichier.addClass('double').attr("title","Image en double");
						else
							$fichier.addPwgLink(resultat.url);
					} else {
						$fichier
							.addClass("missing").attr('title','Manque dans Piwigo')
							.colorbox({
								href: "ws.php?thumb="+prefix+$fichier.attr('data-name'),
								title: $fichier.attr('data-name'),
								rel: $dossier.attr('id'),
								photo: true
							});
					}
				},
				afterProcess: function(){
					if(!isRoot) $dossier.updateStatus("before");
				}
			});
		},
		folderCollapsed: function($dirs) { // Suppression de toutes les requêtes en attente + reset du dossier			
			$dirs.removeClass('wait').reset();
			if ($dirs.length > 0) {
				$.ajaxq.clear("pwg.images.existFromPath");
				$.ajaxq.clear("pwg.images.addFromServer");
				$.ajaxq.clear("derivatives");
			}
		}
	});

	// Ajout des photos au site
	$("input.launch").click(function() {
		var $dossier = $(this).parent().parent().parent();
		$dossier.updateStatus("during");

		processImages({
			prefix: $dossier.attr('id'),
			directory: $dossier,
			fileClass: 'missing',
			service: 'pwg.images.addFromServer',
			maxNumber: 3,
			data: {
				category:  $dossier.children('.addToAlbum').find("select[name=category] option:selected").val(),
				level: $dossier.children('.addToAlbum').find("select[name=level] option:selected").val()
			},
			success: function($file,resultat){
				$file.addPwgLink(resultat.url);
				var nbDerivatives = resultat.derivatives.length;
				if (nbDerivatives > 0) {
					for (var i=0; i < nbDerivatives; i++) {
						options = {url: resultat.derivatives[i] + "&ajaxload=true"};
						if (i==0) options.beforeSend = function(){ $file.addClass('wait'); };
						if (i==nbDerivatives-1) options.complete = function(){ $file.removeClass('wait'); $dossier.updateProgress(1); };
						$.ajaxq("derivatives",options);
					}
				} else {
					$dossier.updateProgress(1);
				}
			},
			error: function(files,message){
				files.addClass("error").attr('title','Erreur lors du transfert');
				$dossier.updateProgress(files.length);
			}
		});
	});
});

// ---------------------------------------- //
// Empêcher la fermeture si upload en cours //
// ---------------------------------------- //

window.onbeforeunload = function() {
    if ($('.during:visible').length > 0)
        return 'Un ajout de photos est en cours, voulez-vous vraiment quitter la page?';
}

// ======================================= //
//                   Obso                  //
// ======================================= //

// --------------------------------------- //
//                Miniatures               //
// --------------------------------------- //

function removeThumb(){ // OBSO
	$("#thumb").hide();
}

function positionThumb(e){ // OBSO
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

function displayThumb(path,e){ //OBSO
	$("#thumb").hide();
	$("#thumbName").html(decodeURIComponent(path.substring(path.lastIndexOf('/') + 1)));
	$("#thumb").show();
	$("#thumb img").load(function() { // Code exécuté à l'ouverture de l'image
		positionThumb(e);
		$(this).fadeIn("medium");
    })
	.attr('src',pluginPath +"template/browse.php?thumb="+ path);
}

// Reset du bas de la page si un autre dossier est affiché
function reset() {
    if( $('#status.end').is(':visible') ) { // L'upload est terminé
        $("fieldset#progress").hide();
        $("#status.end").hide();
        $("#status.start").show();
        $("fieldset#album").show();      
    }
}

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

function postSendingo(cell,success,image_id,url){

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
$("input#launch").click(function() {
    
  var nbTotal = $("span.missing").attr("id");
  
  $("fieldset#album").hide();
  $("#nbRestant").html(nbTotal);
  $("#nbTotal").html(nbTotal);
  $("fieldset#progress").show();
  
  $("span.missing").html(" - Ajout des photos au site");
  
  $("#browser").contents().find('td.site.missing').each(function (index) {
    
    var image_name = $(this).closest('tr').attr('id');
    
    $.ajaxq("fichiers",{
      url: 'ws.php?format=json',
      data: { method: 'pwg.images.addFromServer',
			  prefix_path: $("#chemin").text(),
              images_paths: image_name,
              category: $("select#albumSelect option:selected").val(),
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
					var nbDerivatives = answer.result[0].derivatives.length;
					if (nbDerivatives > 0) {
						$.ajaxq("fichiers",{
							url: answer.result[0].derivatives[0] + "&ajaxload=true",
							success: $.proxy(postSendingo,$(this),$(this),true,answer.result[0].image_id,answer.result[0].url) //Les premiers seront les derniers
						},true);
						for (var i=1; i < nbDerivatives; i++) {
							$.ajaxq("fichiers",{url: answer.result[0].derivatives[i] + "&ajaxload=true"},true);
						}
					} else {
						postSendingo($(this),true, answer.result[0].image_id, answer.result[0].url);
					}
				} else {
					errorNotif(image_name, answer.message);
					postSendingo($(this),false);
				}
			}
			catch (error) {
				errorNotif(image_name, data);
				postSendingo($(this),false);
			}
		},$(this))
    });
  });
});
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
