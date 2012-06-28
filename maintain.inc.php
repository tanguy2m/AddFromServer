<?php

	if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
	
	function plugin_install($plugin_id, $plugin_version, &$errors) // method called on first activation
	{
		$config = array(
			'photos_local_folder' => '/c/archive/Photos/',
			'photos_bin_folder'   => '/c/archive/Recycle Bin/Photos/',
            'systematic_tag' => 'à trier'
		);
		
		// Store configuration in database
		$query = '
			INSERT INTO '.CONFIG_TABLE.' (param,value,comment)
			VALUES ("AddFromServer" , "'.addslashes(serialize($config)).'" , "AddFromServer plugin parameters");';
		pwg_query($query);
		
		// Temp dir creation
		mkdir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer',0755);
		
		// Symlink creation to the photos directory
		symlink($config['photos_local_folder'],realpath(dirname(__FILE__)).'/template/photos');
	}
	
	// function plugin_activate($plugin_id, $plugin_version, &$errors){}
	// function plugin_deactivate($plugin_id){}
	
	function plugin_uninstall($plugin_id)
	{ 
		// Remove symlink to photos directory
		unlink(realpath(dirname(__FILE__)).'/template/photos');
		
		// Remove configuration from database
		$query = 'DELETE FROM ' . CONFIG_TABLE . ' WHERE param="AddFromServer" LIMIT 1;';
		pwg_query($query);
		
		// Delete Temp dir
		rmdir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer');
	}

  ?>