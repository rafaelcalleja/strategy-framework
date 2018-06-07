<?php
	class importaction extends elemento implements Ielemento {

		const ACTION_INSERT = 0;
		const ACTION_UPDATE = 1;
		const ACTION_INSERT_OR_UPDATE = 2;

		const IMPORT_MODE_NORMAL = 1; // update
		const IMPORT_MODE_INSERT = 2; // insert on duplicate key update
		const IMPORT_MODE_IGNORE = 3; // just insert ignore
		const IMPORT_MODE_REMOVE = 4; // remove from table (like update an assign)

	
		public function __construct($param, $extra = false) {
			$this->tipo = "importaction";
			$this->tabla = TABLE_IMPORTACTION;
			$this->uid_modulo = 79;
			$this->instance( $param, $extra );
		}

		public static function prepareValue($fieldSQL, $value) {
			$tpl = Plantilla::singleton();
			if ( strpos(trim($fieldSQL), " ") === false) {
				// Campos de fecha, convertir a timestamp	
				if ( stripos($fieldSQL, "fecha") !== false ) {
					if ( $time = strtotime( str_replace("/", "-", $value) ) ) {
						$value = date("Y-m-d", $time);
					} else {
						$value = "0000-00-00";
					}
				} else {
					switch($fieldSQL){
						case "sexo":
							if ( stripos($value, "mujer") !== false || stripos($value, "femenino") !== false || stripos($value,'female') !== false ) {
								$value = $tpl->getString('femenino');
							} else {
								$value = $tpl->getString('masculino');
							}
						break;
						case "es_manager":
							if (stripos($value,'empl') !== false || $value === '0' || empty($value) || strtolower($value) == strtolower($tpl->getString('no'))) {
								$value = 0; 
							} else {
								$value = 1;
							}
						break;
						case 'ett': case 'es_responsable_trabajos': case 'delegado_prevencion': case 'papelera':
							if (empty($value) || $value === 0 || strtolower($value) == strtolower($tpl->getString('no'))) {
								$value = 0;
							} else {
								$value = 1;
							}
						break;
					}
				}
			} 
			return $value;
		}

		public function doINSERT(dataImport $dataImport, ArrayObjectList $modelFields, $modulo, $data, $tmpFile = false, $destino = null, Iusuario $usuario) {

			$table = constant('TABLE_'.strtoupper($modulo));
			$updates = $querys = array();
			$addExtra = false;
			$existe = !empty($data['uid']);
			$done = true;

			foreach ($modelFields as $modelField) {
				$colName = $modelField->getColumn();
				if (isset($data[$colName])) {
					$fieldSQL = $modelField->getSQL();
					$value = db::scape($data[$colName]);
					$value = self::prepareValue($fieldSQL,$value);
					// si no tienen espacios en el nombre son campos de la misma tabla
					
					
					// Si es de campos "complejos"
					$tablaExtra = $columnaExtra = null;
					if ( strpos(trim($fieldSQL), " ") !== false && strpos(trim($fieldSQL), "agd_data.") !== false ) {
						$iColumn = self::extractFields($fieldSQL);
						$iTable = self::extractTable($fieldSQL);
						
						switch ($iTable) {
							case TABLE_MUNICIPIO: case TABLE_PROVINCIA: case TABLE_PAIS:
								$class = trim(str_replace( DB_DATA . "." , "", $iTable));
								if ( trim($value) && $item = $class::getFromName($value) ) {
									$value = $item->getUID();
								} else {
									$value = 0;
								}
								// Redefinir este dato
								$iColumn = "uid_$class";
								$iTable = $table;
								$updates[$table][$iColumn] = $value;
								continue 2;
							break;
							case TABLE_CENTRO_COTIZACION:
								$class = trim(str_replace( DB_DATA . "." , "", $iTable));
								if ( trim($value) && $item = $class::getFromCode($value) ) {
									$value = $item->getUID();
								} else {
									$value = 0;
								}
								$iColumn = "uid_$class";
								$iTable = $table;
								$updates[$table][$iColumn] = $value;
								continue 2;
							break;
							case TABLE_AGRUPADOR:
								$class = trim(str_replace( DB_DATA . "." , "", $iTable));
								if ( trim($value) && $item = $class::getFromParams($value, $destino, categoria::TYPE_CLIENTES) ) {
									$value = $item->getUID();
								} else {
									$value = 0;
								}
																			
								$iColumn = $colName;
								$iTable = $table;
								$updates[$table][$iColumn] = $value;
							break;
						}
						
						if ( strpos($iTable, TABLE_CAMPO) !== false ) {
							$addExtra = true;
						}
					}
				}
				
				if ($addExtra) {
					$tablaExtra = self::extractTable($modelField->getSQL());
					$campoExtra = self::extractFields($modelField->getSQL());
					if ( !isset($updates[$tablaExtra]) ) {
						$updates[$tablaExtra] = array();
					}
					$updates[$tablaExtra][$campoExtra] = $value;
					// $updates[$tablaExtra]['uid_'.$modulo] = "(SELECT id FROM {$tmptable} LIMIT 1)";
					$updates[$tablaExtra]['uid_'.$modulo] = "@lastid";
					$addExtra = false;
				} else {
					$updates[$table][$colName] = $value;
				}
			}
			
			foreach ($updates as $tabla => $paresCampoValor) {
				$campos = implode(',',array_keys($paresCampoValor));
				$valores = '\''.implode('\',\'',array_values($paresCampoValor)).'\'';
				$SQL = sprintf('INSERT INTO %s (%s) VALUES (%s)',$tabla,$campos,$valores);
				// ñapa para que sólo en este caso el valor no vaya entrecomillado - fgomez
				// $SQL = str_replace("'(SELECT id FROM {$tmptable} LIMIT 1)'","(SELECT id FROM {$tmptable} LIMIT 1)",$SQL);
				$SQL = str_replace("'@lastid'","@lastid",$SQL);
				$SQLREL = "";
				if ($destino && (stripos($tabla, TABLE_CAMPO) === false)) {
					switch (strtolower($modulo)) {
						case 'empresa':
							$SQLREL = " INSERT INTO {$table}_relacion (uid_empresa_superior,uid_empresa_inferior,apta,papelera) VALUES ({$destino},(@lastid),0,1,0)";
						break;
						case 'maquina': 
							$otraRelacion = $this->db->query(" SELECT uid_empresa FROM {$table}_empresa WHERE uid_{$modulo} = {$data['uid']} AND papelera = 0 AND uid_empresa != {$destino} LIMIT 1 ",0,0);
							if ($existe) {
								if ($otraRelacion) {
									$SQLREL = " INSERT INTO {$table}_empresa_temporal (uid_{$modulo},uid_empresa,papelera) VALUES ({$data['uid']},{$destino},0)";
								}
							} else {
								$SQLREL = " INSERT INTO {$table}_empresa (uid_{$modulo},uid_empresa,papelera) VALUES (@lastid,{$destino},0)";
							}							
						break;
						case 'empleado': 
				           	if ($existe) {
				           		$otrasEmpresas = $this->db->query(" SELECT uid_empresa FROM {$table}_empresa WHERE uid_{$modulo} = {$data['uid']} AND papelera = 0 AND uid_empresa != {$destino}", "*", 0, "empresa");	  	
				      			$miEmpresa = $this->db->query(" SELECT uid_empresa FROM {$table}_empresa WHERE uid_{$modulo} = {$data['uid']} AND papelera = 0 AND uid_empresa = {$destino}", "*", 0, "empresa");	  	
					            if (!count($miEmpresa)) {	 
					            	if (!count($otrasEmpresas)) {
					            		// Si esta el empelado en al papelera
					            		$miEmpresaPapelera = $this->db->query(" SELECT uid_empresa FROM {$table}_empresa WHERE uid_{$modulo} = {$data['uid']} AND papelera = 1 AND uid_empresa = {$destino}", "*", 0, "empresa");
					            		if (count($miEmpresaPapelera)) {
					            			$SQLREL = "UPDATE {$table}_empresa SET papelera = 0 WHERE uid_{$modulo} = {$data['uid']} AND uid_empresa = {$destino}";
					            		} else $SQLREL = " INSERT INTO {$table}_empresa (uid_{$modulo},uid_empresa,papelera) VALUES ({$data['uid']},{$destino},0)";
					            	} else {
					            		// Solicitamos transferencia de empleado	
					            		$empleado = new empleado ($data['uid']);
					            		$empresaDestino = new empresa ($destino);
					            		$empleado->solicitarTransferenciaEmpresa($empresaDestino, $usuario);
					            	}					           	  	
					            } else {	  
					            // 		No tengo que insertar si pertenece a mi empresa	
					            //    $SQLREL = " INSERT INTO {$table}_empresa (uid_{$modulo},uid_empresa,papelera) VALUES (@lastid,{$destino},0)";				                
					            }
					   		} else {
					   			// si no existe el empleado tenemos que insertarlo
					   			 $SQLREL = " INSERT INTO {$table}_empresa (uid_{$modulo},uid_empresa,papelera) VALUES (@lastid,{$destino},0)";
					   		}

            			break;
					}
				}

				if ( $tmpFile ) {
					$querys[] = !$existe?$SQL:'-- '.$SQL;
					if (stripos($tabla, TABLE_CAMPO) === false) {
						$sqlSetUID = "SET @lastid = (SELECT LAST_INSERT_ID())";
						$querys[] = !$existe?$sqlSetUID:'-- '.$sqlSetUID;
					}
					if (!empty($SQLREL)) {
						$querys[] = $SQLREL;
					}
				} else if (!$existe) {
					if ( !$this->db->query($SQL) ) {
						$done = false; 
						throw new Exception($this->db->error);
					} else if (!empty($SQLREL) && $uid = $this->db->getLastId()) {
						$SQLREL = str_replace('@lastid',$uid,$SQLREL);
						if (!$this->db->query($SQLREL)) {
							$done = false;
							throw new Exception($this->db->error);	
					    }
					} 
				}
			}
			if ( $tmpFile ) {
				return $querys;
			}
			return $done;	
		}

		public function doUPDATE(dataImport $dataImport, ArrayObjectList $modelFields, $modulo, $data, $uid, $tmpFile = false, $destino = null, $usuario = null){
			if ($dataImport->isUsing('papelera') && !$destino ) return false;
			$table = constant("TABLE_" . strtoupper($modulo));
			$uidmodulo = util::getModuleId($modulo);
			$done = true;
			$tpl = Plantilla::singleton();

			// Si tenemos el uid, el item existe
			if ( is_numeric($uid) ) {
				$updates = array();
				$querys = array();

				// Por cada modelField ...
				foreach ($modelFields as $modelField) {
					// Extraer la columna, para ver los datos
					$colname = $modelField->getColumn();
					// Si se ha cargado mediante csv (que debería ser siempre así)
					if ( isset($data[$colname]) ) {
						$fieldSQL = $updateColumn = $modelField->getSQL();
						$value = db::scape(trim($data[$colname]));
						$mode = self::IMPORT_MODE_NORMAL;
						$updateTable = $table; // por defecto la misma del modulo
						
						if (strpos(trim($fieldSQL), "agd_data.") !== false) {
							$updateColumn = self::extractFields($fieldSQL);
							$updateTable = self::extractTable($fieldSQL);

							// Super hack :( -- Jose
							if ($colname === "direccion" && $table === TABLE_EMPLEADO) {
								$updateColumn 	= "direccion";
								$updateTable 	= TABLE_EMPLEADO;
							}

							switch ($updateTable) {
								case TABLE_MUNICIPIO: case TABLE_PROVINCIA: case TABLE_PAIS:
									$class = trim(str_replace( DB_DATA . "." , "", $updateTable));
									if ( trim($value) && $item = $class::getFromName($value) ) {

										$value = $item->getUID();
									} else {
										$value = 0;
									}

									// Redefinir este dato
									$updateColumn = "uid_$class";
									$updateTable = $table;
								break;

								case TABLE_AGRUPADOR.'_elemento':
									// --- por si el array aun no está creado
									if (!isset($updates[$updateTable])) $updates[$updateTable] = array();

									$updateObject = new ArrayObjectList();
									$updateObject->key = "uid_elemento";


									// --- al importar asignaciones, no contemplamos cuando una columna esta vacia, (el value es el uid)
									if ($value) {
										$updateObject->mode = self::IMPORT_MODE_IGNORE;
									} else {
										$updateObject->mode = self::IMPORT_MODE_REMOVE;
									}


									// --- agrupador a asignar
									$value = $modelField->getParamValue();
									
									$updateObject['uid_agrupador'] = $value;
									$updateObject['uid_modulo'] = $uidmodulo;

 									$updates[$updateTable][] = $updateObject;
								break;

								case TABLE_CENTRO_COTIZACION:
									$class = trim(str_replace( DB_DATA . "." , "", $updateTable));
									if ( trim($value) && $item = $class::getFromCode($value) ) {
										$value = $item->getUID();
									} else {
										$value = 0;
									}

									$updateColumn = "uid_$class";
									$updateTable = $table;

								break;

								case TABLE_AGRUPADOR:
									$class = trim(str_replace( DB_DATA . "." , "", $updateTable));
									if ( trim($value) && $item = $class::getFromParams($value, $destino, categoria::TYPE_CLIENTES) ) {
										$value = $item->getUID();
									} else {
										$value = 0;
									}

									$updateColumn = $colname;
									$updateTable = $table;

								break;

							}
							// Para estas tablas usamos modo insert on duplicate key
							if (strpos($updateTable, TABLE_CAMPO) !== false) {
								$mode = self::IMPORT_MODE_INSERT;
							}
						// SI NO HAY ESPACIOS EN BLANCO es un campo único, no una subconsulta
						} elseif ( !modelfield::isSpecial($fieldSQL)) {
							$value = self::prepareValue($fieldSQL,$value);

						// Si es de campos "extra"
						} elseif (modelfield::isSpecial($fieldSQL)) {
							$updateTable = constant('TABLE_' . strtoupper($modulo)).'_empresa';
							$updateColumn = $fieldSQL;
						}

						if (!isset($updates[$updateTable])) $updates[$updateTable] = new ArrayObject;

						// --- dont do anything when array, only when ArrayObject
						if ($updates[$updateTable] instanceof ArrayObject) {
							$updates[$updateTable][$updateColumn] = $value;
							$updates[$updateTable]->mode = $mode;
						}
					}
				}



				foreach ( $updates as $table => $updateData ) {
					$updateObject = $updateData instanceof ArrayObject ? array($updateData) : $updateData;

					foreach ($updateObject as $updateItem) {
						$rowKey = isset($updateItem->key) ? $updateItem->key : "uid_{$modulo}";
						$updateList = $valuesList = array();

						foreach ($updateItem->getArrayCopy() as $key => $val) {

							$valuesList[] = "'{$val}'";
							$updateList[] = "`{$key}` = '{$val}'";
						}

						switch ($updateItem->mode) {
							case self::IMPORT_MODE_NORMAL:
								$SQL = "UPDATE {$table} SET " . implode(", ", $updateList) . " WHERE `uid_{$modulo}` = {$uid}";
								if ($table == constant('TABLE_' . strtoupper($modulo)).'_empresa') {
									$SQL .= " AND `uid_empresa` = {$destino}";
								}
							break;
							case self::IMPORT_MODE_INSERT:
								$cols = array_keys( $updateItem->getArrayCopy() );
								$SQL = "INSERT INTO $table ($rowKey, ". implode(",", $cols) .") VALUES ($uid,  ". implode(",", $valuesList) .") ON DUPLICATE KEY UPDATE " .  implode(", ", $updateList);
							break;
							case self::IMPORT_MODE_IGNORE:
								$cols = array_keys( $updateItem->getArrayCopy() );
								$SQL = "INSERT IGNORE INTO $table ($rowKey, ". implode(",", $cols) .") VALUES ($uid,  ". implode(",", $valuesList) .")";
							break;
							case self::IMPORT_MODE_REMOVE:
								$cols = array_keys($updateItem->getArrayCopy());
								$SQL = "DELETE FROM $table WHERE {$rowKey} = {$uid} AND " . implode(" AND ", $updateList);
							break;
						}

						if ( $tmpFile ) {
							$querys[] = $SQL;

							$isInsert = strpos($SQL, "INSERT") === 0;
							if ($table === TABLE_AGRUPADOR . "_elemento" && $isInsert && $usuario) {
								$company = $usuario->getCompany();

								$assignmentId = "IF (
									LAST_INSERT_ID(),
									LAST_INSERT_ID(),
									(
										SELECT uid_agrupador_elemento
										FROM agd_data.agrupador_elemento
										WHERE uid_modulo = {$uidmodulo}
										AND uid_elemento = {$uid}
										LIMIT 1
									)
								)";

								$SQL = "INSERT IGNORE INTO agd_data.assignment_version (uid_assignment, uid_company, creation_date, uid_user)
								VALUES ({$assignmentId}, {$company->getUID()}, NOW(), {$usuario->getUID()})";
								$querys[] = $SQL;
							}
						} else {
							if ( !$this->db->query($SQL) ) {
								$done = false; 
								throw new Exception($this->db->error);
							}
						}
					}
				}

				if ( $tmpFile ) {
					return $querys;
				}
				return $done;
			}

			return false;
		}

		public static function getUpdateData(modelField $field, $uid, $value){
			$modulo = $field->getDataField()->obtenerModuloDatos();
			$fieldSQL = $field->getSQL();
			$value = db::scape($value);


			// SI NO HAY ESPACIOS EN BLANCO			
			if( strpos(trim($fieldSQL), " ") === false ){
				$table = constant("TABLE_" . strtoupper($modulo));
				$update = $fieldSQL;

				// Campos de fecha, convertir a timestamp	
				if( stripos($fieldSQL, "fecha") !== false ){
					$value = strtotime($value);
				}

				return "UPDATE $table SET $update = '$value' WHERE uid_$modulo = $uid";

			// Si es de campos "extra"
			} elseif( strpos(trim($fieldSQL), "agd_data.campo") !== false ){
				$colname = self::extractFields($fieldSQL);
				$table = TABLE_CAMPO . "_{$modulo}";

				return "INSERT INTO $table (uid_$modulo, $colname) VALUES ($uid, '$value') ON DUPLICATE KEY UPDATE $colname = '$value'";
			}

			return false;
		}

		public static function getUpdateSQL(modelField $field, $uid, $value){
			$modulo = $field->getDataField()->obtenerModuloDatos();
			$fieldSQL = $field->getSQL();
			$value = db::scape($value);


			// SI NO HAY ESPACIOS EN BLANCO			
			if( strpos(trim($fieldSQL), " ") === false ){
				$table = constant("TABLE_" . strtoupper($modulo));
				$update = $fieldSQL;

				// Campos de fecha, convertir a timestamp	
				if( stripos($fieldSQL, "fecha") !== false ){
					$value = strtotime($value);
				}

				return "UPDATE $table SET $update = '$value' WHERE uid_$modulo = $uid";
			} elseif( strpos(trim($fieldSQL), "agd_data.campo") !== false ){
				$colname = self::extractFields($fieldSQL);
				$table = TABLE_CAMPO . "_{$modulo}";

				return "INSERT INTO $table (uid_$modulo, $colname) VALUES ($uid, '$value') ON DUPLICATE KEY UPDATE $colname = '$value'";
			}
			return false;
		}

		public static function extractFields($sql){
			$aux = explode("select", strtolower($sql));
			$aux = explode("from", strtolower($aux[1]));
			return trim(reset($aux));
		} 

		public static function extractTable($sql){
			$aux = explode("from", strtolower($sql));
			$aux = explode(" ", trim($aux[1]));
			return trim(reset($aux));
		} 

		public function getUserVisibleName(){
			return $this->getActionName();
		}

		public function getAction(){
			return $this->obtenerDato("action_type");
		}

		public function getActionName(){
			$action = $this->obtenerDato("action_type");
			$types = self::getTypes();
			if( isset($types[$action]) ){
				return $types[$action];
			}
	
			return "N/D";
		}

		public function getDataImport(){
			$uid = $this->obtenerDato("uid_dataimport");
			return new dataimport($uid);
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$info = parent::getInfo(true, null, $usuario);
			$data = array();
			$data["nombre"] = $this->getUserVisibleName();
			return array($this->getUID() => $data);
		}

		public static function defaultData($data, Iusuario $usuario = null){
			if( isset($data["poid"]) ){ $data["uid_dataimport"] = $data["poid"]; }
			return $data;
		}

		public static function getTypes(){
			$tpl = Plantilla::singleton();
			$types = array();
				$types[self::ACTION_INSERT] = $tpl->getString("insertar");
				$types[self::ACTION_UPDATE] = $tpl->getString("actualizar");
				$types[self::ACTION_INSERT_OR_UPDATE] = $tpl->getString("insertar_o_actualizar");

			return $types;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;
			switch ($modo) {
				case elemento::PUBLIFIELDS_MODE_EDIT:
				case elemento::PUBLIFIELDS_MODE_INIT:
				case elemento::PUBLIFIELDS_MODE_NEW:
				default:
					$actions = self::getTypes();

					if ($objeto instanceof dataimport && $objeto->getDataModel()->obtenerModuloDatos()=='empresa') {
						$actions = array(1 => $actions[1]); 
					}

					if ($usuario instanceof usuario) {
						$fields["action_type"]	= new FormField(array("tag" => "select", "data" => $actions, "blank" => false ));
						if ($modo == elemento::PUBLIFIELDS_MODE_NEW) {
							$fields["uid_dataimport"] = new FormField(array("blank" => false));
						}
					}
				break;
			}

			return $fields;
		}
		
	}
?>
