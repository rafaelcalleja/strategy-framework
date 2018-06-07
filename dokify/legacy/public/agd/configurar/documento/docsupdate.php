<?php
	include_once( dirname(__FILE__) . "/../../../config.php");

	$ARGS = $_SERVER["argv"];
	$db = db::singleton();
	$mode = $ARGS[1];
	$data = unserialize(base64_decode($ARGS[2]));

	switch( $mode ) {
		case "nuevo":
			// Por cada destino del documento
			foreach( $data["tipo_receptores"] as $moduloDestino ){
				// Buscamos el modulo de origen
				$moduloOrigen = ( strtolower($data["tipo_solicitante"]) == "empresa" ) ? "empresa" : "agrupador";

				// Por cada elemento de origen
				foreach( $data["id_solicitante"] as $uid ){
					$origen = new $moduloOrigen($uid, false);

					// Ejutamos las acciones pertinentes para cada tipo de origen - destino
					switch( $moduloOrigen ){
						case "empresa":

							switch( $moduloDestino ){
								case "empresa":
									$destinatarios = $origen->obtenerIdEmpresasInferiores( false, false, false, empresa::DEFAULT_DISTANCIA );
									$destinatarios[] = $uid;
									$sql = "UPDATE ". TABLE_EMPRESA ." SET updated = 0 WHERE uid_empresa IN (".  implode(",", $destinatarios) .");";
								break;
								case "empleado":
									$destinatarios = $origen->obtenerIdEmpleados();
									$contratas = $origen->obtenerEmpresasInferiores( false, false, false, empresa::DEFAULT_DISTANCIA );
									foreach( $contratas as $contrata ){
										$uidEmpleados = $contrata->obtenerIdEmpleados();
										$destinatarios = array_merge_recursive($destinatarios, $uidEmpleados);
									}
									$sql = "UPDATE ". TABLE_EMPLEADO ." SET updated = 0 WHERE uid_empleado IN (".  implode(",", $destinatarios) .");";
								break;
								case "maquina":
									$destinatarios = $origen->obtenerIdMaquinas();
									$contratas = $origen->obtenerEmpresasInferiores( false, false, false, empresa::DEFAULT_DISTANCIA );
									foreach( $contratas as $contrata ){
										$uidEmpleados = $contrata->obtenerIdMaquinas();
										$destinatarios = array_merge_recursive($destinatarios, $uidEmpleados);
									}
									$sql = "UPDATE ". TABLE_MAQUINA ." SET updated = 0 WHERE uid_maquina IN (".  implode(",", $destinatarios) .");";
								break;
							}

						break;

						case "agrupador":
							switch( $moduloDestino ){
								case "empresa":
									$sql = "
										UPDATE ". TABLE_EMPRESA ." SET updated = 0
										WHERE uid_empresa IN (
											SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento 
											WHERE uid_modulo = 1 AND uid_agrupador = $uid
										)
									";
								break;
								case "empleado":
									$sql = "
										UPDATE ". TABLE_EMPLEADO ." SET updated = 0
										WHERE uid_empleado IN (
											SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento 
											WHERE uid_modulo = 8 AND uid_agrupador = $uid
										)
									";
								break;
								case "maquina":
									$sql = "
										UPDATE ". TABLE_MAQUINA ." SET updated = 0
										WHERE uid_maquina IN (
											SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento 
											WHERE uid_modulo = 14 AND uid_agrupador = $uid
										)
									";
								break;
							}
						break;
					}

					if( !$db->query($sql) ){
						echo "Ocurrio un error: ". $sql ." -- ". $db->lastError();
					} else {
						echo "success";
					}
				}
			}
		break;
		case "modificar":
			$uidatributo = $data["poid"];

			$sql = "SELECT uid_modulo_destino FROM ". TABLE_DOCUMENTO_ATRIBUTO ." WHERE uid_documento_atributo = $uidatributo";
			$currentdata = $db->query($sql, 0, "*");

			$uidmodulo = $currentdata["uid_modulo_destino"];
			$modulo = util::getModuleName($currentdata["uid_modulo_destino"]);
			$tabla = constant("TABLE_". strtoupper($modulo));

			$sql = "UPDATE $tabla SET updated = 0 WHERE uid_$modulo IN (
				SELECT uid_elemento_destino FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." 
				WHERE uid_modulo_destino = $uidmodulo AND uid_documento_atributo = $uidatributo
			)";

			if( !$db->query($sql) ){
				echo "Ocurrio un error: ". $sql ." -- ". $db->lastError();
			}
		break;
	}

?>
