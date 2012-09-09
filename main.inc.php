<?php
  /*
  Plugin Name: AddFromServer
  Version: 2.4.c_beta
  Description: Add images already stored on server to Piwigo
  Author: TdM
  */

define('ADD_FROM_SERVER_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
global $conf;
$conf['AddFromServer'] = unserialize($conf['AddFromServer']);

// Log function
function log_line($line) {
	file_put_contents(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/log', $line.PHP_EOL, FILE_APPEND);
}

// Déclaration des web-services
add_event_handler('ws_add_methods', 'new_ws');
function new_ws($arr) {

    global $conf;
    include_once(ADD_FROM_SERVER_PATH.'include/ws.inc.php');
    $service = &$arr[0];
	
	// Ajout d'un web-service permettant d'ajouter des images depuis le serveur
    $service -> addMethod(
        'pwg.images.addFromServer',
        'ws_images_addFromServer',
        array(
            'image_path' => array(),
            'category' => array('default' => null),
            'name' => array('default' => null),
            'author' => array('default' => null),
            'comment' => array('default' => null),
            'level' => array('default' => 0, 'maxValue' => $conf['available_permission_levels']),
            'tags' => array('default' => null),
            'image_id' => array('default' => null),
            'date_creation' => array('default' => null)
        ),
        '<b>Admin only</b><br>Permet d\'ajouter une image depuis le filesystem du serveur.');

	// Ajout d'un web-service permettant de vérifier la présence d'une image dans Piwigo
    $service -> addMethod(
        'pwg.images.existFromPath',
        'ws_images_existFromPath',
        array(
            'path' => array(),
            'images_names' => array()
        ),
        '<b>Admin only</b><br>
        Permet de vérifier la présence d\'une liste de photos sur Piwigo à partir de leur chemin commum et de leur nom sur le serveur.<br>
        Le paramètre \'path\' est relatif à  $conf[\'AddFromServer\'][\'photos_local_folder\']'
    );
	
	// Ajout d'un web-service permettant de supprimer des photos de Piwigo et du serveur
	$service -> addMethod(
        'pwg.images.deleteFromServer',
        'ws_images_deleteFromServer',
        array(
            'images_ids' => array('default' => null),
			'prefix_path' => array('default' => null),
			'images_paths' => array('default' => null)
        ),
        '<b>Admin only</b><br>
		POST mandatory<br>
		Supprime une liste de photos du serveur et de Piwigo si nécessaire.<br>
		Le paramètre \'images_ids\' est une liste d\'ids d\'images Piwigo. Séparation par un espace ou , ou ; ou |<br>	
		Le paramètre \'prefix_path\' est relatif à  $conf[\'AddFromServer\'][\'photos_local_folder\'] et <b>doit se terminer par un /</b><br>
		Le paramètre \'images_paths\' est une liste d\'emplacements d\'images relatifs à prefix_path. Séparation par , ou ; ou |<br>
		Si prefix_path n\'est pas renseigné, le path est relatif à $conf[\'AddFromServer\'][\'photos_local_folder\']
        '
    );
}

// Add a new tab in photos_add page
add_event_handler('tabsheet_before_select','addFromServer_add_tab', 50, 2);
function addFromServer_add_tab($sheets, $id) { 
    
    if ($id == 'photos_add') {
        $sheets['addFromServer'] = array(
            'caption' => l10n('Depuis serveur'),
            'url' => get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__))
        );
    }	 
    
    return $sheets;
}

// --------------------------
//   Catch images deletion
// --------------------------

global $removeOriginals, $paths_to_be_deleted;
$removeOriginals = true;

// Tableau des images à déplacer dans la corbeille
// Les chemins doivent être relatifs à $conf['AddFromServer']['photos_local_folder']
$paths_to_be_deleted = array();

add_event_handler('begin_delete_elements', 'get_paths');
function get_paths($ids) {

	global $removeOriginals, $paths_to_be_deleted, $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	
	if( $removeOriginals ) {
		foreach($ids as $image_id) {	
				// Récupération du chemin vers la photo source de Piwigo
				$query = '
					SELECT
					path
					FROM '.IMAGES_TABLE.'
					WHERE id = '.$image_id.'
					;';
				list($file_path) = pwg_db_fetch_row(pwg_query($query));
			
				// Récupération du chemin original pour suppression
				if (is_link($file_path)) {
					array_push($paths_to_be_deleted, str_replace($photosPath, "", readlink($file_path)));
				}
		}
	}
}

add_event_handler('delete_elements', 'delete_originals');
function delete_originals($ids) {
	
	global $paths_to_be_deleted, $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	$photosBinPath = $conf['AddFromServer']['photos_bin_folder'];
	
	// Déplacement des fichiers vers la corbeille
	foreach ($paths_to_be_deleted as $file_path) {
	
		// Chemin vers le dossier 'Poubelle' correspondant
		$dir = $photosBinPath.dirname($file_path);
		
		$old_mask = umask(0); // Pour permettre au mkdir d'imposer les permissions
		
		// Création du dossier de destination si nécessaire
		if (is_dir($dir) or @mkdir($dir, 0777, true)) {		
			// Déplacement du fichier
			$commande = "sudo -u generic mv '".$photosPath.$file_path."' '".$dir."' 2>&1";
			exec($commande,$out);
			if(!empty($out)) {
				log_line($file_path." ### ".implode(PHP_EOL, $out));
			}
		} else {
			log_line($file_path." ### Directory creation failed: ".$dir);
		}
		
		umask($old_mask); // Rétablissement du masque d'origine
	}
}

?>