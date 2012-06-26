<?php
	
	function returnError($message)
	{
		echo json_encode(array("stat"=>"fail","message"=>$message));
		exit(1);
	}
  
	// Récupération du chemin vers l'image à modifier
	if ( !isset($_POST['image']) || !isset($_POST['photosPath']) || !isset($_POST['photosBinPath']) )
		returnError('Impossible de récupérer le nom de l\'image');
	$path = rawurldecode($_POST['image']); //$path est un chemin relatif à $conf['AddFromServer']['photos_local_folder']
	
	// Chemin vers le dossier 'Poubelle' correspondant
    $dir = $_POST['photosBinPath'].dirname($path);
    
    // Création du dossier de destination si nécessaire
    exec("mkdir -p -m 777 '$dir'"); // La fonction php 'recursive'=true plante si le dossier existe déjà
    
    // Déplacement du fichier
    exec("sudo -u generic mv '".$_POST['photosPath'].$path."' '$dir' 2>&1",$out);
    if(!empty($out))
      returnError(implode("\n", $out));  
	  
	echo json_encode(array('stat'=>'ok'));
  
  ?>