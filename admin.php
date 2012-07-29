<?php
  
  if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
  global $conf, $template;
    
  $plugin_url = get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__));

  // +-----------------------------------------------------------------------+
  // | Tabs                                                                  |
  // +-----------------------------------------------------------------------+

  define(
      'PHOTOS_ADD_BASE_URL',
      get_root_url().'admin.php?page=photos_add'
  );
    
  include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
  $tabsheet = new tabsheet();
  $tabsheet->set_id('photos_add');
  $tabsheet->select('addFromServer');
  
  // +-----------------------------------------------------------------------+
  // | Variables                                                             |
  // +-----------------------------------------------------------------------+
  
  // Récupération de la liste des catégories
  include_once('include/functions_category.inc.php'); // Pour avoir la fonction 'display_select_cat_wrapper'
  display_select_cat_wrapper(
    'SELECT id,name,uppercats,global_rank
    FROM '.CATEGORIES_TABLE.';',
    array(), // Id de la catégorie sélectionnée par défaut
    'category_options' //Nom du tableau assigné au template
  );
	
  $template->assign(array(
    'plugin_folder'=> ADD_FROM_SERVER_PATH,
    'conf' => $conf['AddFromServer'],
    'level_options'=> get_privacy_level_options(), // Récupération des différents niveaux de visibilité
    'level_options_selected' => array(8) // Par défaut, visibilité = "Admins only"
  ));
  $template->set_filenames(array('plugin_admin_content' => dirname(__FILE__) . '/template/admin.tpl'));
 
  $template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
  
?>