<?php
 
  // Erreur 404 si l'image n'est pas précisée
  if(!isset($_GET['image'])){
	header('HTTP/1.0 404 Not Found');
	exit;
  }
  
  // Récupération du chemin vers l'image à modifier
  $size = isset($_GET['max']) ? $_GET['max'] : 500;
  
  //TODO: et si l'image est plus petite que $size ??
  $cmd = 'convert -define jpeg:size='.$size.'x'.$size.' "'.rawurldecode($_GET['image']).'" -auto-orient -thumbnail '.$size.'x'.$size.' -unsharp 0x.5 JPG:-';
  header("Content-Type: image/jpeg" ); 
  passthru($cmd, $retval);
  
?>