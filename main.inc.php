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

?>