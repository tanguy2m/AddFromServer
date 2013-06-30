<?php
  /*
  Plugin Name: AddFromServer
  Version: 2.5.a_beta
  Description: Add images already stored on server to Piwigo
  Author: tanguy2m
  */

define('ADD_FROM_SERVER_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');

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
        '<b>Admin only</b><br>Permet d\'ajouter une image depuis le filesystem du serveur.'
	);

	// Ajout d'un web-service permettant de vérifier la présence d'une image dans Piwigo
    $service -> addMethod(
        'pwg.images.existFromPath',
        'ws_images_existFromPath',
        array(
            'prefix_path' => array(),
            'images_paths' => array()
        ),
        '<b>Admin only</b><br>
        Permet de vérifier la présence d\'une liste de photos sur Piwigo à partir de leur chemin commum et de leur nom sur le serveur.<br>
		Le paramètre \'prefix_path\' est relatif à  $conf[\'AddFromServer\'][\'photos_local_folder\'] et <b>doit se terminer par un /</b><br>
		Le paramètre \'images_paths\' est une liste d\'emplacements d\'images relatifs à prefix_path. Séparation par ; ou |<br>
		Si prefix_path n\'est pas renseigné, le path est relatif à $conf[\'AddFromServer\'][\'photos_local_folder\']'
    );
	
	// Ajout d'un web-service permettant de supprimer des photos du serveur
	$service -> addMethod(
        'pwg.images.deleteFromServer',
        'ws_images_deleteFromServer',
        array(
			'prefix_path' => array('default' => null),
			'images_paths' => array('default' => null)
        ),
        '<b>Admin only</b><br>
		Supprime une liste de photos du serveur<br>
		Le paramètre \'prefix_path\' est relatif à  $conf[\'AddFromServer\'][\'photos_local_folder\'] et <b>doit se terminer par un /</b><br>
		Le paramètre \'images_paths\' est une liste d\'emplacements d\'images relatifs à prefix_path. Séparation par ; ou |<br>
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

// Add a new delete button in picture.php
add_event_handler('loc_end_picture', 'add_button');
function add_button() {
	global $template, $page, $picture;
	
	// Delete image if requested
	if (isset($_GET['action']) && $_GET['action'] == 'delete') {
		
		include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
		
		//Re-use: admin/picture_modify.php@line 53
		check_pwg_token(); // include/functions.inc.php@line 1543
		delete_elements(array($page['image_id']), true);
		invalidate_user_cache();
		
		// Prepare deletion confirmation
		$_SESSION['page_infos'][] = 'Photo '.$picture['current']['TITLE'].' supprimée';
		// Redirect to the relevant page
		if (isset($page['next_item'])){
			redirect($picture['next']['url']);
		} else if (isset($page['previous_item'])){
			redirect($picture['previous']['url']);
		} else {
			redirect($url_up);
		}
	}
	
	// Add button to template if user is admin
	if (is_admin()) {
		$template->assign('U_DELETE',
			add_url_params(
				$picture['current']['url'],
				array(
					'action'=>'delete',
					'pwg_token'=>get_pwg_token()
				)
			)
		);
		$template->set_filename('delete_button', dirname(__FILE__).'/template/delete_button.tpl');
		$button = $template->parse('delete_button', true);  
		$template->add_picture_button($button, EVENT_HANDLER_PRIORITY_NEUTRAL);
	}
}

// --------------------------
//   Catch images deletion
// --------------------------

global $conf;
if( !empty($conf['AddFromServer']['removeOriginals']) && $conf['AddFromServer']['removeOriginals'] ){
	add_event_handler('begin_delete_elements', 'get_paths');
	add_event_handler('delete_elements', 'delete_originals');
	
	global $paths_to_be_deleted; // Tableau des images à déplacer dans la corbeille
	$paths_to_be_deleted = array(); // Les chemins doivent être relatifs à $conf['AddFromServer']['photos_local_folder']
}

function get_paths($ids) {
	global $paths_to_be_deleted, $conf;
	$photosPath = $conf['AddFromServer']['photos_local_folder'];
	
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

function delete_originals($ids) {

	include_once(ADD_FROM_SERVER_PATH.'include/functions.inc.php');
	global $paths_to_be_deleted;
	
	log_line("$paths_to_be_deleted: ".implode(";",$paths_to_be_deleted));
	$errors_list = move_to_bin($paths_to_be_deleted);
	foreach ($errors_list as $err) {
		log_line($err["file"]." ### ".$err["error"]);
	}
}

?>