$(function(){
  // Affichage du dossier sélectionné
  fullLink = $('tr.row.one.header td.name a').attr('href'); // Récupération du lien à partir du header 'Nom de fichier'
  parent.updateChemin(decodeURIComponent(fullLink.substring(fullLink.indexOf("&dir=photos/")+12))); // On ne garde que la fin de la chaîne après "&dir=photos/"
  
  // Récupération du click sur les fichiers
  $('a.item.file').click(function() {
  
	if ($(this).closest('tr').find("img:first").attr('alt') == "jpg")
		parent.displayInfoFichier($(this).html()); //Suppression de 'photos/'
	else
		parent.displayNoThumb();
		
    return false; // Afin d'éviter que l'hyperlien soit réellement éxécuté
  });

  //Redimensionnement de l'Iframe parente
  parent.document.getElementById('browser').height = document['body'].offsetHeight+25;
});

// Refresh de la page
function refresh(){
  window.location.href = window.location.href;
}
// Association de la fonction 'reloadDossier' de la page select
parent.reloadDossier = refresh;



