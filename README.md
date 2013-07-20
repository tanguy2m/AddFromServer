AddFromServer
-------------

Plugin pour la gallerie Piwigo permettant l'ajout de photos stockées sur le même serveur que la gallerie.  
L'outil permet de voir si la photo est déjà sur le site (md5sum).  
L'ajout se fait par dossier avec la possibilité d'ajouter un tag pour toutes les photos ajoutées.

La conf doit être déclarée avant d'activer le plugin:
```php
$conf['AddFromServer'] = array(
  /* Paramètres obligatoires */
  'photos_local_folder' => '/c/Photos/', /* Dossier de stockage local des photos */  
  'removeOriginals' => true, /* Mise à la corbeille de l'original si image supprimée du site */
  /* Si removeOriginals = true */
  'photos_bin_folder'   => '/c/Poubelle/', /* Corbeille utilisée lors de la suppression */
  'delete_type' => 'sudoMove', /* 'simpleMove' (rename php), 'sudoMove' (sudo move linux)*/
  /* Si delete_type = sudoMove */
  'sudo_user' => 'toto', /* Utilisateur à utiliser pour le sudo */
  /* Paramètres facultatifs */
  'derivatives' => array('small','thumb'), /*  Derivatives Piwigo à générer à chaque upload */
  'systematic_tag' => 'à trier' /* Tag systématique ajouté lors de l'upload */
);
```
Si le plugin GThumb+ est installé et actif, les miniatures nécessaires seront générées lors de l'upload automatiquement.