AddFromServer
=============

Plugin pour la gallerie Piwigo permettant l'ajout de photos stockées sur le même serveur que la gallerie.
L'outil permet de voir si la photo est déjà sur le site (md5sum).
L'ajout se fait par dossier avec la possibilité d'ajouter un tag pour toutes les photos ajoutées.

La conf doit être déclarée avant d'activer le plugin:
```php
$conf['AddFromServer'] = array(
  'photos_local_folder' => '/c/Photos/', /* Dossier de stockage local des photos */  
  'systematic_tag' => 'à trier', /* Tag systématique ajouté lors de l'upload */
  'removeOriginals' => true, /* Mise à la corbeille de l'original si image supprimée du site */
  'delete_type' => 'sudoMove', /* Si removeOriginals true: 'simpleMove' (rename php), 'sudoMove' (sudo move linux)*/
  'sudo_user' => 'toto', /* Si delete_type à 'sudoMove', utilisateur à utiliser pour le sudo */
  'photos_bin_folder'   => '/c/Poubelle/', /* Corbeille utilisée lors de la suppression */
);
```
