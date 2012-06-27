///////////////////////////
//    Iframe loading     //
///////////////////////////

function showBrowser(size){
  $("#browser").height(window.frames["browser"].document['body'].offsetHeight+30); // Dimensionement de l'Iframe en fonction du contenu
  $("#waitBrowser").css('background-color',$("#content").css('background-color')); // Copie de la couleur de fond du thème
  $("#waitBrowser").hide();
}

function hideBrowser(){ // Le dossier change dans browse.php
  razFile();
  $("#waitBrowser").show();
}

///////////////////////////
//     Notifications     //
///////////////////////////

function errorNotif(titre,message){
  jQuery.jGrowl( message, { theme: 'error', header: titre, sticky: true });
}

function infoNotif(titre,message){
  jQuery.jGrowl( message, { theme: 'success', header: titre, life: 4000, sticky: false });
}

///////////////////////////////////////////////////
//    Gestion de la popup "Ajout de catégorie"   //
///////////////////////////////////////////////////

jQuery(document).ready(function(){
  
  function fillCategoryListbox(selectId, selectedValue) {
    jQuery.getJSON(
      "ws.php?method=pwg.categories.getList",
      {
        recursive: true,
        fullname: true,
        format: "json",
      },
      function(data) {
        jQuery.each(
          data.result.categories,
          function(i,category) {
            var selected = null;
            if (category.id == selectedValue) {
              selected = "selected";
            }
            
            jQuery("<option/>")
              .attr("value", category.id)
              .attr("selected", selected)
              .text(category.name)
              .appendTo("#"+selectId)
              ;
          }
        );
      }
    );
  }
  
  jQuery(".addAlbumOpen").colorbox({
    inline:true,
    href:"#addAlbumForm",
    onComplete:function(){
      jQuery("input[name=category_name]").focus();
    }
  });
  
  jQuery("#addAlbumForm form").submit(function(){
    jQuery("#categoryNameError").text("");
    
    jQuery.ajax({
      url: "ws.php?format=json&method=pwg.categories.add",
      data: {
        parent: jQuery("select[name=category_parent] option:selected").val(),
        name: jQuery("input[name=category_name]").val(),
      },
      beforeSend: function() {
        jQuery("#albumCreationLoading").show();
      },
      success:function(html) {
        jQuery("#albumCreationLoading").hide();
        
        var newAlbum = jQuery.parseJSON(html).result.id;
        jQuery(".addAlbumOpen").colorbox.close();
        
        jQuery("#albumSelect").find("option").remove();
        fillCategoryListbox("albumSelect", newAlbum);
        
        /* we refresh the album creation form, in case the user wants to create another album */
        jQuery("#category_parent").find("option").remove();
        
        jQuery("<option/>")
          .attr("value", 0)
          .text("------------")
          .appendTo("#category_parent")
          ;
        
        fillCategoryListbox("category_parent", newAlbum);
        
        jQuery("#addAlbumForm form input[name=category_name]").val('');
        
        jQuery("#albumSelection").show();
        
        return true;
      },
      error:function(XMLHttpRequest, textStatus, errorThrows) {
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
function suppr(image)
{ 
  $.ajax({
    url: pluginPath + 'include/delete.php',
    type: 'POST',
    async: false, // On attend le résultat de cette requête pour continuer
    data: { image: image,
			photosPath: photosPath,
			photosBinPath: photosBinPath
	},
    success: function(data) {
      try { // Le parseJSON peut échouer
        if(jQuery.parseJSON(data).stat == "fail")
          throw "Message";
      }
      catch (error) {
        if(error=="Message")
          throw jQuery.parseJSON(data).message;
        else
          throw data; // Permet d'afficher toutes les erreurs non catchées
      }
    }
  });
}

$(function() {
  $('#suppr').click(function() {
    
    // Suppression du fichier
    try {
      suppr($("#chemin").html()+$("#cheminFichier").html());
      infoNotif( $('#cheminFichier').html(), 'Fichier supprimé' );
    }
    catch (error) { errorNotif( 'Suppression '+$('#cheminFichier').html(), error ); }   
    
    // Rafraîchissement de l'iframe
    reloadDossier($('#cheminFichier').html()); //C'est la fonction 'refresh' de l'Iframe
    // Remise à zéro de la zone propre à l'images
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

function displayInfoFichier(filename)
{ 
  // Affichage du chemin vers le fichier
  $('#cheminFichier').html(filename);
  
  // Affichage de la miniature avec sablier
  var img = new Image();
  
  $("#miniature a").remove(); // Suppression de l'image existante
  $("#suppr").hide();
  $("#cheminFichier").addClass('loading');

  $(img)
    .load(function () { // Code exécuté à l'ouverture de l'image
      $(this).hide(); // On cache l'image par défaut      
      $("#cheminFichier").removeClass('loading');     
      $('#miniature')
        // Ajout du lien vers l'image 'HD'
        .append('<a href=\"'+pluginPath+'include/thumb.php?max=800&image='+encodeURIComponent($("#fullDir").text()+$("#cheminFichier").html())+'\" target="_blank"></a>');      
      $('#miniature a').append(this); // Insertion de l'image dans le div #miniature
      $(this).fadeIn(); // Petit effet à l'ouverture de l'image
      $("#suppr").show();
    })
    
    .error(function () {
      errorNotif( 'Calcul des miniatures', 'Chargement de l\'image impossible');
	  $("#cheminFichier").removeClass('loading'); 
    })
    
    // *finally*, set the src attribute of the new image to our image
    .attr('src', pluginPath+'include/thumb.php?max=480&image='+encodeURIComponent($("#fullDir").text()+$("#cheminFichier").html()));
}

// --------------------------------------- //
//      Mise à jour du panel 'dossier'     //
// --------------------------------------- //

function updateMissingNb(number) {
  if ( number > 1 )
    $(".titrePage h2 span").html("- "+number+" photos absentes de Piwigo");
  else if ( number == 1 )
    $(".titrePage h2 span").html("- "+number+" photo absente de Piwigo");
  else
    $(".titrePage h2 span").html("- Toutes les photos sont déjà dans Piwigo");
}

function razMissingNb() {
  $(".titrePage h2 span").empty();
}

function updateChemin(path)
{
  $("#chemin").html(path);
  $("#dossier").val($("#fullDir").text());
}