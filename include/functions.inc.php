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

function listDirectory($dossier) {

	global $conf;
	header('Content-type: text/plain; charset=utf-8');
	$dir = $conf['AddFromServer']['photos_local_folder'] . $dossier;
	
	if( file_exists($dir) ) {
		$items = scandir($dir);
		natcasesort($items);
		if( count($items) > 2 ) { /* 2 = . and .. */
			$dirs = array();
			$files = array();
			foreach( $items as $item ) {
				if( file_exists($dir . $item) && $item != '.' && $item != '..' ){					
					if( is_dir($dir . $item) ) { // Folder
						$dirs[] = $item;
					} else { // File
						$ext = preg_replace('/^.*\./', '', $item);
						$files[] = array(
							"name" => $item,
							"process" => ( in_array($ext, $conf['picture_ext']) ? true : false )
						);
					}
				}
			}
			echo json_encode(array(
				"path" => $dossier,
				"dirs" => $dirs,
				"files" => $files
			));
		}
	}
	exit();
}

function generateThumb($fichier) {

	global $conf;
	$file = $conf['AddFromServer']['photos_local_folder'] . $fichier;
	
	// Reset les headers ajoutés par session_cache_limiter()
	// http://stackoverflow.com/a/681584
	header_remove("Cache-Control");
	header_remove("Expires");
	header_remove("Pragma");
	
	if(filemtime($file) < filemtime(__FILE__))
		$mtime = gmdate('r', filemtime(__FILE__));
	else
		$mtime = gmdate('r', filemtime($file));
	$etag = md5($mtime.$file);

	if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $mtime)
		|| (isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag)) 
	{
		header('HTTP/1.1 304 Not Modified');
		exit;
	} else {
		header('ETag: "'.$etag.'"');
		header('Last-Modified: '.$mtime);
		$size = isset($_GET['size']) ? $_GET['size'] : 300;
		$cmd = 'convert -define jpeg:size='.$size.'x'.$size.' "'.$file.'" -auto-orient -thumbnail '.$size.'x'.$size.' -unsharp 0x.5 JPG:-';
		header("Content-Type: image/jpeg" );
		passthru($cmd, $retval);
		exit;
	}
}

?>