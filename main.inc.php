<?php
  /*
  Plugin Name: AddFromServer
  Version: 2.4.b_beta
  Description: Add images already stored on server to Piwigo
  Author: TdM
  */

define('ADD_FROM_SERVER_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
global $conf;
$conf['AddFromServer'] = unserialize($conf['AddFromServer']);

// Récupération de l'event qui liste tous les web-services
add_event_handler('ws_add_methods', 'new_add_image_ws');
// Fonction ajoutant le nouveau web-service
function new_add_image_ws($arr) {
    global $conf;
    include_once(ADD_FROM_SERVER_PATH.'include/ws.inc.php');

    $service = &$arr[0];
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
}

add_event_handler('ws_add_methods', 'new_check_exists');
function new_check_exists($arr) {
    global $conf;
    include_once(ADD_FROM_SERVER_PATH.'include/ws.inc.php');

    $service = &$arr[0];
    $service -> addMethod(
        'pwg.images.existFromPath',
        'ws_images_existFromPath',
        array(
            'path' => array(),
            'images_names' => array()
        ),
        '<b>Admin only</b><br>
        Permet de vérifier la présence d\'une liste de photos sur Piwigo à partir de leur chemin commum et de leur nom sur le serveur.<br>
        Le paramètre path est relatif à  $conf[\'AddFromServer\'][\'photos_local_folder\']'
    );
}

// Add a new entry in Admin plugins menu
add_event_handler('get_admin_plugin_menu_links', 'add_entry_admin_menu');
function add_entry_admin_menu($menu) {
    array_push(
        $menu,
        array(
            'NAME' => 'Ajouter photos du NAS',
            'URL' => get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__))
        )
    );
    return $menu;
}

// Add a new tab in photos_add page
add_event_handler('tabsheet_before_select','addFromServer_add_tab', 50, 2);
function addFromServer($sheets, $id) { 
    
    if ($id == 'photos_add') {
        $sheets['rotate'] = array(
            'caption' => l10n('Depuis serveur'),
            'url' => get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__))
        );
    }	 
    
    return $sheets;
}

?>