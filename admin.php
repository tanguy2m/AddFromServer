<?php
  
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
global $template;

// +-----------------------------------------------------------------------+
// | Tabs                                                                  |
// +-----------------------------------------------------------------------+

define(
	'PHOTOS_ADD_BASE_URL', // Mandatory pour la suite
	get_root_url().'admin.php?page=photos_add'
);

include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
$tabsheet = new tabsheet();
$tabsheet->set_id('photos_add');
$tabsheet->select('addFromServer');
$tabsheet->assign();
  
// +-----------------------------------------------------------------------+
// | Variables                                                             |
// +-----------------------------------------------------------------------+

$template->assign(array(
	'plugin_folder'=> ADD_FROM_SERVER_PATH,
	'level_options'=> get_privacy_level_options(), // Récupération des différents niveaux de visibilité
	'level_options_selected' => array(8) // Par défaut, visibilité = "Admins only"
));
$template->set_filenames(array('plugin_admin_content' => dirname(__FILE__) . '/template/admin.tpl'));

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
  
?>