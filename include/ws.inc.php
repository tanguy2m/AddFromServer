<?php

// ---------------------------
//    WebService addFromServer
// ---------------------------
// Adaptation de la méthode Piwigo: /include/ws_functions.inc.php - Fonction: ws_images_addSimple

function ws_images_addFromServer($params, &$service) {
    
    global $conf;
    // Admin only
    if (!is_admin()) {
        return new PwgError(401, 'Access denied');
    }
    
    $params['image_path'] = stripslashes($params['image_path']);
    
    // Image path verification
    if (!is_file($params['image_path'])) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Image path not specified or not valid: ".$params['image_path']);
    }

    // Image already known ?
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    $query = '
      SELECT *
      FROM '.IMAGES_TABLE.'
      WHERE md5sum = \''.md5_file($params['image_path']).'\'
      ;';
    $image_row = pwg_db_fetch_assoc(pwg_query($query));
    if ($image_row != null) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Image already in database");
    }

    // Image_id verification
    $params['image_id'] = (int) $params['image_id'];
    if ($params['image_id'] > 0) {
        $query = '
        SELECT *
        FROM '.IMAGES_TABLE.'
        WHERE id = '.$params['image_id'].'
                             ;';
        $image_row = pwg_db_fetch_assoc(pwg_query($query));
        if ($image_row == null) {
            return new PwgError(404, "image_id not found");
        }
    }

    // Category
    $params['category'] = (int) $params['category'];
    if ($params['category'] <= 0 and $params['image_id'] <= 0) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
    }

    // Copy original in temporary folder
    $original = $params['image_path'];
    $params['image_path'] = PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/'.basename($original);
    copy($original, $params['image_path']);

    require_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
    // Fonction add_uploaded_file du script /admin/include/functions_upload.inc.php
    $image_id = add_uploaded_file(
        $params['image_path'],
        basename($params['image_path']),
        $params['category'] > 0 ? array($params['category']) : null,
        isset($params['level']) ? $params['level'] : null,
        $params['image_id'] > 0 ? $params['image_id'] : null
    );

    $info_columns = array('name', 'author', 'comment', 'level', 'date_creation', );

    foreach($info_columns as $key) {
        if (isset($params[$key])) {
            $update[$key] = $params[$key];
        }
    }

    if (count(array_keys($update)) > 0) {
        $update['id'] = $image_id;

        include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
        mass_updates(
            IMAGES_TABLE,
            array(
                'primary' => array('id'),
                'update' => array_diff(array_keys($update), array('id'))),
                array($update)
            );
    }

    // Add tags to the image if specified
    if (isset($params['tags']) and!empty($params['tags'])) {
        $tag_ids = array();
        $tag_names = explode(',', $params['tags']);
        foreach($tag_names as $tag_name) {
            $tag_id = tag_id_from_tag_name($tag_name);
            array_push($tag_ids, $tag_id);
        }

        add_tags($tag_ids, array($image_id));
    }

    $url_params = array('image_id' => $image_id);

    if ($params['category'] > 0) {
        $query = '
        SELECT id, name, permalink
        FROM '.CATEGORIES_TABLE.'
        WHERE id = '.$params['category'].'
        ;';
        $result = pwg_query($query);
        $category = pwg_db_fetch_assoc($result);

        $url_params['section'] = 'categories';
        $url_params['category'] = $category;
    }

    // update metadata from the uploaded file (exif/iptc), even if the sync
    // was already performed by add_uploaded_file().
    $query = '
      SELECT
      path
      FROM '.IMAGES_TABLE.'
      WHERE id = '.$image_id.'
      ;';
    list($file_path) = pwg_db_fetch_row(pwg_query($query));

    require_once(PHPWG_ROOT_PATH.'admin/include/functions_metadata.php');
    sync_metadata(array($image_id));

    //Symlink original picture if not resized
    $need_resize = ($conf['original_resize'] and need_resize($file_path, $conf['original_resize_maxwidth'], $conf['original_resize_maxheight']));
    if (!$conf['original_resize'] or!$need_resize) {
        //Replace HIGH picture by a symlink to the original
        $real_path = realpath($file_path);
        unlink($real_path);
        symlink($original, $real_path);
    }

    return array('image_id' => $image_id, 'url' => make_picture_url($url_params));
}

// ---------------------------
//    WebService existFromPath
// ---------------------------

function ws_images_existFromPath($params, &$service) {
    
    global $conf;
    
    if (!is_admin()){
        return new PwgError(401, 'Access denied');
    }
    
    // Récupération d'un tableau de noms de fichiers
    $file_names = preg_split(
        '/[,;\|]/',
        stripslashes($params['images_names']), //Permet de gérer les single quote (remplacé par \' par php)
        -1,
        PREG_SPLIT_NO_EMPTY
    );
    $file_names = array_flip($file_names);
    
    foreach($file_names as $file_name => $value) {
        
        $full_path = $conf['AddFromServer']['photos_local_folder'].$params['path'].$file_name;
        
        // Image path verification
        if (!is_file($full_path)) {
            return new PwgError(WS_ERR_INVALID_PARAM, "Image path not specified or not valid for ".$file_name);
        }
        
        $md5 = md5_file($full_path);
        $result = $service -> invoke("pwg.images.exist", array('md5sum_list' => $md5));
        
        if ( strtolower( @get_class($result) )!='pwgerror') {
            $file_names[$file_name] = $result[$md5];
	    }
        else {
            return $result;
        }        
    }
 
    return $file_names;
     
}

// ------------------------------
//    WebService deleteFromServer
// ------------------------------

function ws_images_deleteFromServer($params, &$service) {

	//Récupération des éléments de conf
	global $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	$photosBinPath = $conf['AddFromServer']['photos_bin_folder'];
	
	// Vérification des droits d'admin
	if (!is_admin()) {
		return new PwgError(401, 'Access denied');
	}
	
	// Vérification des paramètres d'entrée
	if ( empty($params['images_ids']) and empty($params['images_paths']) ) { // Aucun paramètre spécifié
		return new PwgError(WS_ERR_INVALID_PARAM, "images_paths or images_ids shall be defined");
	}
	
	// Tableau d'erreurs
	$errors_list = array();
	
	// Tableau des images à déplacer dans la corbeille
	// Les chemins doivent être relatifs à $conf['AddFromServer']['photos_local_folder']
	$paths_to_be_deleted = array();
	
	// Appel du web-service par des images_ids
	if ( !empty($params['images_ids']) ) {
		
		// Récupération de la liste des images_ids
		$params['images_ids'] = preg_split(
			'/[\s,;\|]/',
			$params['images_ids'],
			-1,
			PREG_SPLIT_NO_EMPTY
		);
		$params['images_ids'] = array_map('intval', $params['images_ids']);	
		$images_ids = array();
		foreach ($params['images_ids'] as $image_id) {
			if ($image_id > 0) {
				array_push($images_ids, $image_id);
			}
		}
	
		foreach($images_ids as $image_id) {	
			// Récupération du chemin vers la photo source de Piwigo
			$query = '
				SELECT
				path
				FROM '.IMAGES_TABLE.'
				WHERE id = '.$image_id.'
				;';
			list($file_path) = pwg_db_fetch_row(pwg_query($query));
			//TODO: et si l'image_id n'est pas connu ?
		
			// Récupération du chemin original pour suppression
			if (is_link($file_path)) {
				array_push($paths_to_be_deleted, str_replace($photosPath, "", readlink($file_path)));
			}
		}
		
		// Suppression de tous les éléments de Piwigo
		include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
		delete_elements($image_ids, true); // true => suppression de l'image source Piwigo
	}
	
	// Appel du web-service par des images_paths
	if ( !empty($params['images_names']) ) {
	
		// Récupération de la liste des chemins d'image à traiter
		$file_names = preg_split(
			'/[,;\|]/',
			stripslashes($params['images_names']), //Permet de gérer les single quote (remplacé par \' par php)
			-1,
			PREG_SPLIT_NO_EMPTY
		);
		
		// Récupération du préfixe éventuel des dossiers
		$prefix_path = empty($params['prefix_path']) ? '' : $params['prefix_path'];
		
		// Récupération des chemins complets
		foreach ($file_names as $file_name) {
			array_push($paths_to_be_deleted, $prefix_path.$file_name);
		}
	}
	
	// Déplacement des fichiers vers la corbeille
	foreach ($paths_to_be_deleted as $file_path) {
	
		// Chemin vers le dossier 'Poubelle' correspondant
		$dir = $photosBinPath.dirname($file_path);
		
		// Création du dossier de destination si nécessaire
		if( !is_dir($dir) ) { 
			if (mkdir($dir, 0777, true)) {
			
				// Déplacement du fichier
				exec("sudo -u generic mv '".$photosPath.$file_path."' '".$dir."' 2>&1",$out);
				if(!empty($out)) {
					$errors_list[$file_path] = implode("\n", $out);
				}
	
			} else {
				$errors_list[$file_path] = "Directory creation failed: ".$dir;
			}
		}
	}
	
	// Renvoi du tableau d'erreurs si non vide
	if ( count($errors_list) > 0 ) {
		return new PwgError(403, $errors_list);
	}
}

?>