<?php
  /*
  Plugin Name: AddFromServer
  Version: 2.6.a_beta
  Description: Add images already stored on server to Piwigo
  Author: tanguy2m
  Plugin URI: https://github.com/tanguy2m/AddFromServer/
  */

define('ADD_FROM_SERVER_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');

// Déclaration des web-services
add_event_handler('ws_add_methods', 'new_ws');
function new_ws($arr) {

    global $conf;
    $service = &$arr[0];
	
	$commonArgs = array(
		'prefix_path' => array('flags' => WS_PARAM_OPTIONAL, 'info' => 'Relatif à $conf[\'AddFromServer\'][\'photos_local_folder\']'),
		'images_paths' => array('flags' => WS_PARAM_FORCE_ARRAY, 'info' => 'Tableau d\'emplacements d\'images relatifs à prefix_path')
	);
	
	// Ajout d'un web-service permettant d'ajouter des images depuis le serveur
    $service -> addMethod(
        'pwg.images.addFromServer',
        'ws_images_addFromServer',
        array_merge($commonArgs,array(
            'category' => array(
				'flags' => WS_PARAM_OPTIONAL|WS_PARAM_FORCE_ARRAY,
				'type' => WS_TYPE_ID,
				'info' => 'Tableau d\'ids (ou id unique) des catégories auxquelles l\'image sera liée.'
			),
            'name' => array('flags' => WS_PARAM_OPTIONAL, 'info' => 'Nom de l\'image<br>Uniquement si 1 image ajoutée'),
            'author' => array('flags' => WS_PARAM_OPTIONAL, 'info' => 'Auteur de l\'image'),
            'comment' => array('flags' => WS_PARAM_OPTIONAL, 'info' => 'Commentaire associé à l\'image<br>Uniquement si 1 image ajoutée'),
            'level' => array(
				'type' => WS_TYPE_INT|WS_TYPE_POSITIVE,
				'default' => 0, 'maxValue' => max($conf['available_permission_levels']),
				'info' => 'Entier spécifiant le niveau de confidentialité'
			),
            'tags' => array('flags' => WS_PARAM_OPTIONAL|WS_PARAM_FORCE_ARRAY, 'info' => 'Tableau de tags à appliquer à l\'image'),
            'image_id' => array('flags' => WS_PARAM_OPTIONAL, 'type' => WS_TYPE_ID, 'info' => 'ID de la photo dans le cas d\'un update<br>Uniquement si 1 image ajoutée'),
            'date_creation' => array('flags' => WS_PARAM_OPTIONAL)
        )),
        'Ajoute une image au site depuis le filesystem du serveur.<br><b>Attention, une fois ajoutée l\'image originale ne doit pas être déplacée</b>',
		ADD_FROM_SERVER_PATH.'include/ws.inc.php', // file to be included at runtime
		array('admin_only' => true)
	);

	// Ajout d'un web-service permettant de vérifier la présence d'une image dans Piwigo
    $service -> addMethod(
        'pwg.images.existFromPath',
        'ws_images_existFromPath',
        $commonArgs,
		'Vérifie la présence d\'une liste de photos sur le serveur',
		ADD_FROM_SERVER_PATH.'include/ws.inc.php', // file to be included at runtime
		array('admin_only' => true)
    );
	
	// Ajout d'un web-service permettant de supprimer des photos du serveur
	$service -> addMethod(
        'pwg.images.deleteFromServer',
        'ws_images_deleteFromServer',
        $commonArgs,
		'Supprime une liste de photos du serveur',
		ADD_FROM_SERVER_PATH.'include/ws.inc.php', // file to be included at runtime
		array('admin_only' => true)
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

// Add a new delete button in picture.php if Admin Tools plugin is not installed
include_once(PHPWG_ROOT_PATH.'/include/functions_plugins.inc.php');	
$admintools = get_db_plugins('active', 'AdminTools');
if (empty($admintools)){
	add_event_handler('loc_end_picture', 'add_button');
}

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
		$_SESSION['page_infos'][] = "Photo '".$picture['current']['TITLE']."' supprimée";
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