<?php
	/* DAR DE ALTA ELEMENTOS DESDE INPUT TEXT - SIMPLES */
	
	/**/
	include( "../api.php");

	use Dokify\Application\Event\Group\Store as StoreEvent;
	use Dokify\Events;

	$m = obtener_modulo_seleccionado();
	$uid = obtener_uid_seleccionado();
	$mode = ( isset($_GET["mode"]) ) ? $_GET["mode"] : false;
	$data = trim($_POST["data"]);
	$parent = new $m($uid);
	$tpl = Plantilla::singleton();

	if( $data && $mode ){

		// Comprobamos el patron..
		if( $patron = $parent->obtenerDato("patron") ){
			if( !preg_match("#".$patron."#", $data, $matches) ){
				die("patron");
			}
		}

		if( $coleccion = $mode::getFromWhere("nombre", $data, "agrupador", $parent) ){

			$existente = reset($coleccion);
			if( isset($_GET["o"]) ){
				$idAsignar = $_GET["o"]; // EL ELEMENTO REAL ( empresa, empleado, maquina ) EN SI ( EL UID )
				$moduloAsignar = $_GET["assign"]; // El modulo 

				$objeto = new $moduloAsignar($idAsignar);  

				if( isset($_POST["force"]) ){
					$data = $existente->getInfo(true);
					if( $objeto->asignarAgrupadores($existente) ){
						$data = $existente->getInfo(true);

						if( $usuario->isViewFilterByGroups() ){
							$usuario->asignarAgrupadores( $existente );
						}
						die( json_encode($data) );
					} else {
						die( $lang->getString("no_podido_asignar") );
					}
				} else {
					$agrupadores = $objeto->obtenerAgrupadores();
					$agrupadoresUIDs = $agrupadores->toIntList()->getArrayCopy();

					if( in_array($existente->getUID(), $agrupadoresUIDs)){
						die( "asignado" );
					} else {
						die( $existente->getUID() );
					}
				}
			} else {
				die( $lang->getString("no_recibido_uid") );
			}
		} else {
			// Si el nombre no existe
			if( isset($_GET["poid"]) && isset($_GET["o"]) && isset($_GET["assign"]) ){
				$uidParent = $_GET["poid"]; // EL ELEMENTO REAL ( empresa, empleado, maquina ) EN SI ( EL UID )
				$moduloParent = $_GET["m"]; // El modulo 
				$parent = new $moduloParent($uidParent); 

				$idAsignar = $_GET["o"]; // EL ELEMENTO REAL ( empresa, empleado, maquina ) EN SI ( EL UID )
				$moduloAsignar = $_GET["assign"]; // El modulo  
				$objeto = new $moduloAsignar($idAsignar);

				// Creamos la condicion de búsqueda, para ver si hay alguno parecido
				$arrayNombre = explode(" ", $data);
				$parts = array();
				$condicion = false;
				foreach($arrayNombre as $i => $string){
					if( trim($string) && strlen($string)>3 ){
						$parts[] = " nombre LIKE '%" . db::scape($string) . "%' ";
					}
				}
				// 1 = 1 -> Chapu para que filtre correctamente en agrupamiento::obtenerAgrupadores
				if( count($parts) ) $condicion = " 1 = 1 AND (". implode(" OR ", $parts) . ")";

				// Agrupadores de este agrupamiento parecidos a $condicion
				$agrupadoresCoincidencia = $parent->obtenerAgrupadores(null, $condicion, array(), false, array(0,9) );
				if( count($agrupadoresCoincidencia) == 0 || isset($_REQUEST["force"]) ){
					$op = reset($usuario->getAvailableOptionsForModule($mode, "Crear nuevo"));
						if( count($op) ){
							try {
								$new = new $mode( array("nombre" => $data, "poid" => $parent->getUID() ), $usuario );
								if( $new->exists() ){
									$data = array();
								
									if( $m && $uid ){
										switch($mode){
											case "agrupador":
												$app   = \Dokify\Application::getInstance();
												$event = new StoreEvent($new->asDomainEntity());
												$app->dispatch(Events::POST_GROUP_STORE, $event);

												if( $usuario->isViewFilterByGroups() ){
													$usuario->asignarAgrupadores( $new );
												}
												$data = $new->getInfo(true);

												if( $usuario->accesoElemento($objeto) ){
													if( isset($_REQUEST["rel"]) ){
														$data[$new->getUID()]["rel"] = true;
														$agrupador = new agrupador($_REQUEST["rel"]);
														if( true !== ( $error = $agrupador->asignarRelacion($objeto, array($new->getUID()) ) ) ){
															exit;
														}
													} else {
														$objeto->asignarAgrupadores( $new );
													}
												}															
											break;
										}
									}
									print json_encode($data);
									exit;
								}
							} catch(Exception $e){
								//die( $e->getMessage() );
								exit;
							}
						}
				} else {
					if( isset($_GET["o"]) ){
						$listCoincidencias = array();
						$html = $tpl->getString("agrupador_asignado");
						foreach($agrupadoresCoincidencia as $i => $coincidencia){
							$name = $coincidencia->getUserVisibleName();
							$item = array ( "name" => "item", "type" => "radio", "innerHTML" => $name, "value" => $name);

							// Si ya esta asignado...
							if( $objeto->estadoAgrupador($coincidencia) ){
								$item["disabled"] = true;
								$item["title"] = $html;
							}

							$listCoincidencias[] = $item;
						}
						print json_encode($listCoincidencias);
						exit;
					}
				}
			}
		}
	}
?>
