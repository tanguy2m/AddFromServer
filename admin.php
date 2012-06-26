<?php
  
  if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
  global $conf, $template;
  
  // ---------------------------
  //    Listing d'un dossier
  // ---------------------------
  function getDirContent($path){
  
	global $conf;
    $content = array();
    $nb = 0;
	
    if ($handle = opendir($path)) {
      while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
          if (is_dir($path."/".$entry)) {
            $content[$entry] = "dir";
          } else if (is_file($path."/".$entry) && in_array(pathinfo($entry, PATHINFO_EXTENSION),$conf['picture_ext'])) {
            $content[$entry] = "img"; // Récupération des extensions d'images acceptées par Piwigo
			$nb++;
          } else {
            $content[$entry] = "other";
          }
        }
      }
      closedir($handle);
    }
    
    return array("entries"=>$content,"nbImages"=>$nb);
  }
  
  $plugin_url = get_root_url().'admin.php?page=plugin-'.basename(dirname(__FILE__));

  $template->assign('plugin_folder',ADD_FROM_SERVER_PATH); // Variable nécessaire pour les 2 templates
  
  if (isset($_POST['dossier'])) // Le formulaire de la page 'select.tpl' a été soumis
  {
    $template->assign(array(
      'category_id' => $_POST['category'],
      'level_id' => $_POST['level'],
      'dossier' => $_POST['dossier'],
      'content' => getDirContent($_POST['dossier'])
    ));
    $template->set_filenames(array('plugin_admin_content' => dirname(__FILE__) . '/template/treat.tpl'));
  }
  else
  {
    // Récupération de la liste des catégories
    include_once('include/functions_category.inc.php'); // Pour avoir la fonction 'display_select_cat_wrapper'
    display_select_cat_wrapper(
      'SELECT id,name,uppercats,global_rank
      FROM '.CATEGORIES_TABLE.';',
      array(), // Id de la catégorie sélectionnée par défaut
      'category_options' //Nom du tableau assigné au template
    );
	
    $template->assign(array(
      'treat_page' => $plugin_url,
      //'photos_root' => $conf['AddFromServer']['photos_local_folder'],
	  'conf' => $conf['AddFromServer'],
      'level_options'=> get_privacy_level_options(), // Récupération des différents niveaux de visibilité
      'level_options_selected' => array(8) // Par défaut, visibilité = "Admins only"
    ));
    $template->set_filenames(array('plugin_admin_content' => dirname(__FILE__) . '/template/select.tpl'));
  }
  
  $template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
  
  ?>