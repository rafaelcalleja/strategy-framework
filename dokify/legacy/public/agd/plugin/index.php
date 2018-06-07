<?php
	require_once("../../api.php");

	$template = Plantilla::singleton();

	$installed = array();
	foreach( glob("*") as $pluginFolder){
		if( $pluginFolder != "." && $pluginFolder != ".." && is_dir($pluginFolder) ){

			$plugin = new plugin($pluginFolder);
			if( !$plugin->exists() ){
				if( $plugin->getVersion() ){
					if( $plugin->load() === true ){

					}
				}
			}

			if( $plugin->getInstalledVersion() < $plugin->getVersion() || 1 ){
				$plugin->update();
			}

			$installed[] = $plugin->getUID();
		}
	}


	$SQL = "DELETE FROM ". TABLE_PLUGINS ." WHERE 1 ";
	if( count($installed) ){
		$SQL .= "AND uid_plugin NOT IN (". implode(",", $installed) .")";
	}
	db::get($SQL);
	

	$template->display("succes_form.tpl");
?>
