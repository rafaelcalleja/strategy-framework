<?php
	require __DIR__ . "/../api.php";

	ini_set('memory_limit', '256M');
	if (!isset($_GET["action"]) || !isset($_SESSION["FILES"])) exit;

	set_time_limit(0);
	
	$res = unserialize($_SESSION["FILES"]); 

	if ($res) {
		switch( strtolower($_GET["action"]) ){
			case "info":
				$maxSize = $usuario->maxUploadSize();

				if ($res["archivo"]["error"] === 'size_error') {
					$data = array("data" => -1, "max" => $maxSize);

					//header("HTTP/1.1 500 Internal Server Error");
					header("Content-type: application/json");
					
					print json_encode($data);
					exit;
				}

				$filename = $res["archivo"]["tmp_name"];
				$fileData = archivo::tmp($filename);
				
				if ($size = strlen($fileData)) {
					if ($size > $maxSize) {
						//$MBmaxSize = round((($maxSize/1024)/1024),2);
						//$MDcurrentSize = round((($size/1024)/1024),2);

						header("HTTP/1.1 500 Internal Server Error");
						header("Content-type: application/json");

						$data = array("data" => -1, "max" => $maxSize,  "current" => $size);
						print json_encode($data);
						exit;
					}
						 
					if ($documentID = obtener_uid_seleccionado()) {

						$itemID = @$_REQUEST["o"];
						$module = obtener_modulo_seleccionado();
						if (!$module || !$itemID) { 
							header("HTTP/1.1 500 Internal Server Error");
							exit;
						}
						$selectedItem = new $module($itemID);
						$document = new documento($documentID, $selectedItem);

						if ($document->canSelectItems($usuario) && $document->isVersionable()) {
							$html = $document->getHTMLEmployeeList($usuario, $filename);

							$res['html'] = array(
								'#post-process' => $html
							);
						}
					}
	
					$res["archivo"]["size"] = $size;
					unset($res["archivo"]["tmp_name"]);

					header("Content-type: application/json");
					print json_encode( $res );
				} else {

					header("HTTP/1.1 500 Internal Server Error");
					die("no_uploaded_yet");
				}
				exit;
			break;
			case "dl":
				archivo::dump( archivo::tmp($res["archivo"]["tmp_name"]), $res["archivo"]["name"] ); exit;
			break;
		}

		throw new Exception("No info available");
	}
?>
