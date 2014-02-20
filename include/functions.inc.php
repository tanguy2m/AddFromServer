<?php

// Log function
function log_line($line) {
	file_put_contents(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/log', $line.PHP_EOL, FILE_APPEND);
}

function move_to_bin($files) {
	
	global $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	$photosBinPath = $conf['AddFromServer']['photos_bin_folder'];
	
	$errors_list = array();
	
	// Déplacement des fichiers vers la corbeille
	foreach ($files as $file_path) {
	
		// Chemin vers le dossier 'Poubelle' correspondant
		$dir = $photosBinPath.dirname($file_path);
		
		$old_mask = umask(0); // Pour permettre au mkdir d'imposer les permissions
		
		// Création du dossier de destination si nécessaire
		if (is_dir($dir) or @mkdir($dir, 0777, true)) {		
			// Déplacement du fichier
			if ($conf['AddFromServer']['delete_type'] == 'sudoMove') {
				$commande = "sudo -u ".$conf['AddFromServer']['sudo_user']." mv \"".$photosPath.$file_path."\" \"".$dir."\" 2>&1";
				exec($commande,$out);
				if(!empty($out))
					$errors_list[] = array("file" => $file_path, "error" => implode(PHP_EOL, $out));
			} else if ($conf['AddFromServer']['delete_type'] == 'simpleMove'){
				rename($photosPath.$file_path,$photosBinPath.$file_path);
			}
		} else {
			$errors_list[] = array("file" => $file_path, "error" => "Directory creation failed: ".$dir);
		}
		
		umask($old_mask); // Rétablissement du masque d'origine
	}
	
	return $errors_list;
}

function get_cat_url_params($id){
	$url_params = array();
	$query = '
		SELECT id, name, permalink
		FROM '.CATEGORIES_TABLE.'
		WHERE id = '.$id.'
		;';
	$result = pwg_query($query);
	$category = pwg_db_fetch_assoc($result);

	$url_params['section'] = 'categories';
	$url_params['category'] = $category;

	return $url_params;
}

?>