<?php

// ---------------------------
//    WebService addFromServer
// ---------------------------
// Adaptation de la méthode Piwigo: /include/ws_functions.inc.php - Fonction: ws_images_addSimple
// http://piwigo.org/dev/browser/trunk/ws.php#L441
//
// 1. Copie l'image originale dans le dossier LOCAL_DIR/AddFromServer/
// 2. Fonction add_uploaded_file
//    a. Déplacement de la copie dans UPLOAD_DIR/
//    b. Redimensionnement de l'original (configurable)
// 3. Si original pas redimensionné, remplacement du fichier dans UPLOAD_DIR par un lien vers l'original

function ws_images_addFromServer($params, &$service) {

	global $conf;
	
	$nbImages = count($params['images_paths']);
    $file_names = array_flip(array_map('stripslashes',$params['images_paths']));

	// Full-path construction
	$prefix = $conf['AddFromServer']['photos_local_folder'];
	if(!empty($params['prefix_path'])){
		$prefix .= trim(stripslashes($params['prefix_path']),'/').'/'; // Uniquement un slash final
	}
	
	foreach($file_names as $file_name => $value) {
	
		$full_path = $prefix.trim($file_name,'/'); // Suppression des slashs au début et à la fin
	
		// Image path verification
		if (!is_file($full_path)) {
			return new PwgError(WS_ERR_INVALID_PARAM, "Image path not specified or not valid: ".$full_path);
		}
		$md5 = md5_file($full_path);
		
		if ($nbImages == 1) {
			// Image already known ?
			include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');			
			$query = '
			  SELECT *
			  FROM '.IMAGES_TABLE.'
			  WHERE md5sum = \''.$md5.'\'
			  ;';
			$image_row = pwg_db_fetch_assoc(pwg_query($query));
			if ($image_row != null) {
				return new PwgError(WS_ERR_INVALID_PARAM, "Image already in database");
			}
		}
		
		// Copy original in temporary folder
		$original = $full_path;
		$full_path = PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/'.basename($original);
		copy($original, $full_path);

		require_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
		// Fonction add_uploaded_file du script http://piwigo.org/dev/browser/trunk/admin/include/functions_upload.inc.php
		$image_id = add_uploaded_file(
			$full_path,
			basename($full_path),
			isset($params['category']) ? $params['category'] : null, // Array attendu
			$params['level'], // level has a default value
			(isset($params['image_id']) && ($nbImages==1))? $params['image_id'] : null,
			$md5
		);
	
		// Update IMAGES table with provided additional information
		$info_columns = array('author', 'date_creation'); // level already inserted by add_uploaded_file
		if($nbImages == 1) {
			$info_columns = array_merge($info_columns,array('name', 'comment'));
		}
		$update = array();
		foreach($info_columns as $key) {
			if (isset($params[$key])) {
				$update[$key] = $params[$key];
			}
		}
		single_update(
			IMAGES_TABLE,
			$update, // single_update function will return immediatly if $update is empty
			array('id' => $image_id)
		);

		// Add tags to the image if specified
		$tag_ids = array();	
		if (!empty($params['tags'])) {
			$tag_names = $params['tags'];
		}	
		if (!empty($conf['AddFromServer']['systematic_tag'])) {
			$tag_names[] =  $conf['AddFromServer']['systematic_tag'];
		}
		foreach($tag_names as $tag_name) {
			$tag_ids[] = tag_id_from_tag_name($tag_name);
		}
		add_tags($tag_ids, array($image_id));

		// URL build-up
		$url_params = array('image_id' => $image_id);
		if (isset($params['category']) and count($params['category']) == 1){
			$query = '
			SELECT id, name, permalink
			FROM '.CATEGORIES_TABLE.'
			WHERE id = '.$params['category'][0].'
			;';
			$result = pwg_query($query);
			$category = pwg_db_fetch_assoc($result);

			$url_params['section'] = 'categories';
			$url_params['category'] = $category;
		}

		//Symlink original picture if not resized
		$query = '
		  SELECT
		  path
		  FROM '.IMAGES_TABLE.'
		  WHERE id = '.$image_id.'
		  ;';
		list($file_path) = pwg_db_fetch_row(pwg_query($query));
		
		$need_resize = ($conf['original_resize'] and need_resize($file_path, $conf['original_resize_maxwidth'], $conf['original_resize_maxheight']));
		if (!$conf['original_resize'] or !$need_resize) {
			//Replace HIGH picture by a symlink to the original
			$real_path = realpath($file_path);
			unlink($real_path);
			symlink($original, $real_path);
		}
  
		$file_names[$file_name] = array(
			'image_id' => $image_id,
			'url' => make_picture_url($url_params), // http://piwigo.org/dev/browser/trunk/include/functions_url.inc.php
			'derivatives' => array() //Default value
		);
	
		// ---------------------------
		// Derivatives urls generation
		// ---------------------------
		
		$types = array();
		if(!empty($conf['AddFromServer']['derivatives']) && is_array($conf['AddFromServer']['derivatives']))
			$types = $conf['AddFromServer']['derivatives'];

		include_once(PHPWG_ROOT_PATH.'/include/functions_plugins.inc.php');	
		$gthumb = get_db_plugins('active', 'GThumb');
		$gfmd = get_db_plugins('active', 'getFullMissingDerivatives');
		
		if (!empty($gthumb) && !empty($gfmd)){
			$types[] = 'custom';
			$derivatives = $service -> invoke("pwg.getFullMissingDerivatives", array(
			  'types' => $types,
			  'custom_width' => 9999,
			  'custom_height' => $conf['GThumb']['height'],
			  'ids' => $image_id
			));
		} else if (!empty($types)){
			$derivatives = $service -> invoke("pwg.getMissingDerivatives", array(
			  'types' => $types,
			  'ids' => $image_id
			));
		}
		if ( !empty($derivatives) ) {
			if(strtolower(@get_class($derivatives)) == 'pwgerror')
				return $derivatives;
			$file_names[$file_name]['derivatives'] = $derivatives['urls'];
		}
	}
	
	return $file_names;
}

// ---------------------------
//    WebService existFromPath
// ---------------------------

function ws_images_existFromPath($params, &$service) {
	
	global $conf;
	
    $file_names = array_flip(array_map('stripslashes',$params['images_paths']));
	
	// Full-path construction
	$prefix = $conf['AddFromServer']['photos_local_folder'];
	if(!empty($params['prefix_path'])){
		$prefix .= trim(stripslashes($params['prefix_path']),'/').'/'; // Uniquement un slash final
	}
	
    foreach($file_names as $file_name => $value) {
        
		$full_path = $prefix.trim($file_name,'/'); // Suppression des slashs au début et à la fin
        
        // Image path verification
        if (!is_file($full_path)) {
            return new PwgError(WS_ERR_INVALID_PARAM, "Fichier inconnu: ".$full_path);
        }
        
        $md5 = md5_file($full_path);
        $result = $service -> invoke("pwg.images.exist", array('md5sum_list' => $md5));
        
        if ( strtolower( @get_class($result) )!='pwgerror') {
            $file_names[$file_name] = array("id" => $result[$md5]);
			
			if(!is_null($result[$md5])) {
			
				// Récupération de l'url de l'image (si possible dans l'album)
				$infos = $service -> invoke("pwg.images.getInfo", array('image_id' => $result[$md5]));
				if ( strtolower( @get_class($infos) )!= 'pwgerror') {
					if(count($infos["categories"]->_content) == 1){ // Image liée à une seule catégorie
						$file_names[$file_name]["url"] = $infos["categories"]->_content[0]["page_url"];
					} else { // Image liée à plusieurs catégories
						$file_names[$file_name]["url"] = $infos["page_url"];
					}
				} else { // Notamment si l'image n'est liée à aucune catégorie
					include_once(PHPWG_ROOT_PATH."include/functions_html.inc.php");
					set_status_header(200); // Le return PwgError modifie le header
					$file_names[$file_name]["url"] = '';
				}
				
				// Récupération du chemin + vérification doublons
				$query = '
					SELECT
					path
					FROM '.IMAGES_TABLE.'
					WHERE id = '.$result[$md5].'
					;';
				list($path) = pwg_db_fetch_row(pwg_query($query));

				if(is_link($path) and (readlink($path) != $full_path)) {
					$file_names[$file_name]["double"] = "yes";
					$file_names[$file_name]["pwg_path"] = readlink($path);
				} else {
					$file_names[$file_name]["double"] ="no";
				}
			}
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
	
	// Tableau des images à déplacer dans la corbeille
	// Les chemins doivent être relatifs à $conf['AddFromServer']['photos_local_folder']
	$paths_to_be_deleted = array();
	
	$prefix  = '';
	if(!empty($params['prefix_path'])){
		$prefix .= trim(stripslashes($params['prefix_path']),'/').'/'; // Uniquement un slash final
	}
	
	// Récupération des chemins complets
	foreach ($params['images_paths'] as $file_name) {
		array_push($paths_to_be_deleted, $prefix.trim(stripslashes($file_name),'/')); // Suppression des slashs au début et à la fin
	}
	
	// Déplacement des fichiers vers la corbeille
	include_once(ADD_FROM_SERVER_PATH.'include/functions.inc.php');
	$errors_list = move_to_bin($paths_to_be_deleted);
	
	// Renvoi du tableau d'erreurs si non vide
	if ( count($errors_list) > 0 ) {
		return new PwgError(100, array("errors" => $errors_list));
	}
}

?>