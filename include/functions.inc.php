<?php

// Log function
function log_line($line) {
	file_put_contents(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/log', $line.PHP_EOL, FILE_APPEND);
}

function move_to_bin($files) {
	
	global $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	$photosBinPath = $conf['AddFromServer']['photos_bin_folder'];
	
	// D�placement des fichiers vers la corbeille
	foreach ($files as $file_path) {
	
		// Chemin vers le dossier 'Poubelle' correspondant
		$dir = $photosBinPath.dirname($file_path);
		
		$old_mask = umask(0); // Pour permettre au mkdir d'imposer les permissions
		
		// Cr�ation du dossier de destination si n�cessaire
		if (is_dir($dir) or @mkdir($dir, 0777, true)) {		
			// D�placement du fichier
			$commande = "sudo -u generic mv '".$photosPath.$file_path."' '".$dir."' 2>&1";
			exec($commande,$out);
			if(!empty($out)) {
				$errors_list[] = array("file" => $file_path, "error" => implode(PHP_EOL, $out));
			}
		} else {
			$errors_list[] = array("file" => $file_path, "error" => "Directory creation failed: ".$dir);
		}
		
		umask($old_mask); // R�tablissement du masque d'origine
	}
}

?>