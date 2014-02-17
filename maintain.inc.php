<?php

class AddFromServer_maintain extends PluginMaintain	{

	function install($plugin_version, &$errors=array()) // method called on first activation
	{ 
		// Temp dir creation
		mkdir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer',0755);
	}
	
	function activate($plugin_version, &$errors=array()){
        
        global $conf;
        if ( empty($conf['AddFromServer']) || empty($conf['AddFromServer']['photos_local_folder']) ){
            $errors[] = "La variable de configuration 'AddFromServer' n'est pas correctement déclarée dans le fichier de configuration";
            return;
        }
		
        if(substr($conf['AddFromServer']['photos_local_folder'], -1) !== '/'){
            $errors[] = "La variable de configuration ['AddFromServer']['photos_local_folder'] doit se terminer par un '/'";
            return;
        }
		
        // Symlink creation to the photos directory
    	symlink($conf['AddFromServer']['photos_local_folder'],realpath(dirname(__FILE__)).'/template/photos');
	}
    
	function deactivate(){
        // Remove symlink to photos directory
    	unlink(realpath(dirname(__FILE__)).'/template/photos');
	}
	
	function uninstall()
	{ 
		// Delete Temp dir
		rmdir(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer');
	}
}
?>