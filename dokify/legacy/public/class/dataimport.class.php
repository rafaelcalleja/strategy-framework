<?php
	class dataimport extends elemento implements Ielemento {

		public function __construct($param, $extra = false) {
			$this->tipo = "dataimport";
			$this->tabla = TABLE_DATAIMPORT;
			$this->uid_modulo = 78;
			$this->instance( $param, $extra );
		}

		/** dataimport::import
		  * 
		  * @param $origen · La tabla que tiene los datos necesarios
		  *
		  * test: SELECT * FROM $origen
		  */
		public function import($origen, $destino = null, $usuario, $debug = false) {
			if ( !$dataFieldKey = $this->getKey() ) {
				return false;
			}

			$dataModel = $this->getDataModel();
			$modelFields = $dataModel->obtenerModelFields($usuario->getAnalyticsReadonlyCondition());
			$modulo = $dataModel->obtenerModuloDatos();
			$keyColumn = $dataFieldKey->getColumn();
			$table = constant("TABLE_" . strtoupper($modulo));
			$UIDColum = "( SELECT uid_$modulo FROM $table d WHERE d.$keyColumn = tmp.$keyColumn ) as uid";
			$currentKeyColum = "( SELECT d.$keyColumn FROM $table d WHERE d.$keyColumn = tmp.$keyColumn ) as keyValue";
			//$dataModel = $this->getDataModel();
			$SQL = "SELECT *, $UIDColum, $currentKeyColum FROM $origen tmp";	
			// Si tenemos datos que importar...
			if ( $data = $this->db->query($SQL, true) ) {
				$total = count($data);
				// Guardar reporte para dar feedback al usuario
				$results = array(
					"lines_affected" => 0
				);
				// Acciones a ejecutar en cada linea
				$importActions = $this->obtenerImportActions();
				if ( !count($importActions) ) { 
					throw new Exception("especifica_una_accion_de_actualizacion"); 
				}
				foreach($importActions as $action){
					$folder = DIR_LOG . "/import/";
					if ( !is_dir($folder) ) { 
						die("Para empezar a usar esta funcionalidad en este servidor se tiene que crear la carpeta {$folder}"); 
					}
					$updateFile = $folder . "/query.".time().".".uniqid().".sql";
					$querys = array();

					if ($debug) { echo "<table style='width:95%;margin:10px;'>"; }

					foreach ($data as $i => $line) {
						$lineData = $line;
						$actionType = $action->getAction();
						if ( !is_numeric($actionType) ) {
							return false;
						}
						switch ($actionType) {
							case importaction::ACTION_INSERT: 
								// $done = $action->doINSERT($dataImport, $modelFields, $table, $keyColumn, $keyValue, $data); 
								$done = $action->doINSERT($this, $modelFields, $modulo, $line, $updateFile, $destino, $usuario);
								if ($updateFile) {
									$querys = array_merge($querys,$done);
									$done = true;
								}
							break;
							case importaction::ACTION_UPDATE:
								$acceso = false;
								if ($uid = $lineData['uid']) {
									$item = new $modulo($uid);
									$empresaDestino = new empresa($destino);
								
									switch ($modulo) {
										case 'empleado':
											$acceso = $empresaDestino->getStartIntList()->match($item->getCompanies()->toIntList());
											break;
										case 'empresa':
											$acceso = $item->getCompanies()->toIntList()->match($empresaDestino->getStartIntList());
											break;
										case 'maquina':
											$acceso = $empresaDestino->getStartIntList()->match($item->getCompanies()->toIntList());
											break;
										
										default:
											$acceso = false;
											break;
									}
																		
								}

								if ($uid && $acceso){
									//Controlar el item pertenece a la empresa donde se quiere insertar
									unset($lineData['uid']);
									if ( isset($lineData[$keyColumn]) ) {
										unset($lineData[$keyColumn]);
									}
									if ( isset($lineData["keyValue"]) ) {
										unset($lineData["keyValue"]);
									}
									$done = $action->doUPDATE($this, $modelFields, $modulo, $lineData, $uid, $updateFile, $destino, $usuario);
									if ( $updateFile ) {
										$querys = array_merge($querys, $done);
										$done = true;
									}
								}
							break;
							case importaction::ACTION_INSERT_OR_UPDATE:
								$acceso = false;
								if ($uid = $lineData['uid']) {
									$item = new $modulo($uid);
									$empresaDestino = new empresa($destino);
									switch ($modulo) {
										case 'empleado':
											$acceso = $empresaDestino->obtenerEmpleados()->contains($item);
											break;
										case 'empresa':
											$acceso = $empresaDestino->obtenerEmpresasInferiores()->contains($item);
											break;
										case 'maquina':
											$acceso = $empresaDestino->obtenerMaquinas()->contains($item);
											break;
											
										default:
											$acceso = false;
											break;
									}
								}
								if ($uid && $acceso){
									//Controlar el item pertenece a la empresa donde se quiere insertar
									unset($lineData['uid']);
									if ( isset($lineData[$keyColumn]) ) {
										unset($lineData[$keyColumn]);
									}
									if ( isset($lineData["keyValue"]) ) {
										unset($lineData["keyValue"]);
									}
									$done = $action->doUPDATE($this, $modelFields, $modulo, $lineData, $uid, $updateFile, $destino); 
									if ( $updateFile ) {
										$querys = array_merge($querys, $done);
										$done = true;
									}
								} else {
									$done = $action->doINSERT($this, $modelFields, $modulo, $lineData, $updateFile, $destino, $usuario);
									if ( $updateFile ) {
										$querys = array_merge($querys, $done);
										$done = true;
									}
								} 
							break;
						}

						// Control de progresos...
						if ( $done === true ) {
							if($debug) { echo "<tr><td>Datos actualizados correctamente para $keyColumn $line[$keyColumn] </td><td></td><td style='width:30px; color: #333'>".($i+1)."/$total</td></tr>"; ob_flush(); flush(); }
							if ($debug && $i==($total-1)) { echo "<script>window.scrollBy(0, document.body.scrollHeight)</script>"; }
							$results["lines_affected"]++;
						} elseif ( is_string($done) ) {
							$results["error"] = $done;
							return $results;
						}
					}

					if ($debug) { echo "</table>"; }

					if ( $updateFile ) {
						$querys[] = "SELECT 'ok'"; // para validar el resultado
						if ( file_put_contents($updateFile, implode(";\n", $querys) ) ) {
							if ( is_readable($updateFile) ) {
								$result = db::run($updateFile);
								if ( CURRENT_ENV == "dev") { dump('DEV TRACE:', $result); }
							} else {
								dump("No se puede leer $updateFile");
							}
						} else {
							dump("No se puede escribir en $updateFile");
						}
					}
				}
				return $results;
			}

			$results = array( "error" => "Ocurrió un problema al extraer los datos de la tabla temporal" );
			return $results;
		}

		public function isUsing($name){
			if ($this->getDataModel()->isUsing($name)) {
				return true;
			}

			$sql = "
				SELECT count(*) FROM ". TABLE_DATACRITERION . " dc
				INNER JOIN ". TABLE_DATAFIELD . " USING(uid_datafield)
				WHERE dc.uid_modulo = {$this->getModuleId()} AND dc.uid_elemento = {$this->getUID()}
				AND name = '{$name}'
			";

			return (bool) $this->db->query($sql, 0, 0);
		}

		public function load($archivo, Iusuario $usuario){
			$tmptabla = "tmp_import_{$usuario->getUID()}". uniqid();
			$temporal = DB_TMP .".$tmptabla";

			$dataModel = $this->getDataModel();
			$modelFields = $dataModel->obtenerModelFields($usuario->getAnalyticsReadonlyCondition());
			$cols = $modelFields->foreachCall('getColumn');

			$reader = new dataReader($tmptabla, $archivo, archivo::getExtension($archivo), $cols);
			if( $reader->error ) throw new Exception($reader->error);


			if( $dataFieldKey = $this->getKey() ){
				$camposFichero = array_map( "strtolower", $reader->leerCampos());
				$camposFichero = array_map( "trim", $camposFichero );
			
				if( count($camposFichero) != count($modelFields) ){
					throw new Exception("El numero de campos del fichero (". count($camposFichero) .") no coincide. Deben ser ". count($modelFields). "separados por ';'.");
				}

				if( $colKey = $dataFieldKey->getColumn() ){
					if( $reader->cargar(true, $colKey) ){
						return $temporal;
					} else {
						if( $usuario->esStaff() ){
							throw new Exception($reader->error);
						} else {
							throw new Exception("Error #1 al imporar el fichero");
						}
						return false;
					}
				} else {
					throw new Exception("No se encuentra el campo clave!");
				}			

			} else {
				throw new Exception("Sin clave primaria");
			}
			
			return false;
		}

		public function getKey(){
			$dataFields = $this->getDataModel()->obtenerUsedDataFields();
			$list = ( count($dataFields) ) ? $dataFields->toComaList() : 0;
			$sql = "SELECT uid_datafield FROM ". TABLE_DATAFIELD . " WHERE clave = 1 AND uid_datafield IN ($list) LIMIT 1";
			if( $uid = $this->db->query($sql, 0, 0 ) ){
				return new datafield($uid);
			}
			return false;
		}

		// @return un ArrayObjectList con los datafields modificables (no clave y no readonly)
		public function getWritableDataFields($usuario) {
			$dataFields = $this->getDataModel()->obtenerUsedDataFields();
			$list = (count($dataFields)) ? $dataFields->toComaList() : null;
			if ($list) {
				$sql = " SELECT uid_datafield FROM ". TABLE_DATAFIELD . " WHERE ({$usuario->getAnalyticsReadonlyCondition()}) AND clave = 0 AND uid_datafield IN ($list) ";
				$collection = $this->db->query($sql,'*', 0, 'datafield');
				return new ArrayObjectList($collection);
			}
			return $list;
		}

		public function obtenerImportActions(){
			return $this->obtenerObjetosRelacionados(TABLE_IMPORTACTION, "importaction");
		}


		public function getUserVisibleName(){
			return $this->obtenerDato("name");
		}

		public function getDataModel(){
			$uid = $this->obtenerDato("uid_datamodel");
			return new datamodel($uid);
		}

		
		public function obtenerAvailableDataFields(){
			$model = $this->getDataModel();
			$sql = "
				SELECT d.uid_datafield FROM ". TABLE_DATAFIELD ." d
				WHERE d.uid_modulo = {$model->obtenerDato("uid_modulo")} 
				AND (
					d.uid_datafield NOT IN (
						SELECT uid_datafield FROM ". TABLE_DATACRITERION ." WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()}
					)  
					OR d.param != '0'
				)
			";
			$array = $this->db->query($sql, "*", 0, "datafield");
			return new ArrayObjectList($array);
		}

		public function obtenerDataCriterions($limit = NULL){
			$sql = "SELECT uid_datacriterion FROM ". TABLE_DATACRITERION ." WHERE uid_modulo = {$this->getModuleId()} AND uid_elemento = {$this->getUID()}";
			if( is_numeric($limit) ) $sql .= " LIMIT 0, $limit";

			$array = $this->db->query($sql, "*", 0, "datacriterion");
			return new ArrayObjectList($array);
		}


		public function getInlineArray($usuarioActivo=false, $mode, $data ){
			$tpl = Plantilla::singleton();
			$inline = array();
			
			/*
			$inline[] = array(
				"title" => $tpl->getString("descargar"),
				"img" => RESOURCES_DOMAIN . "/img/famfam/drive_web.png",
				array( "nombre" => $tpl->getString("descargar"), "href" => "analytics/descargar.php?poid={$this->getUID()}", "target" => "async-frame" )
			);

			$criterions = $this->obtenerDataCriterions(8);
			if( $criterions && count($criterions) ){
				$inline[] =  array( 
					"title" => $tpl->getString("filtros"),
					"img" => RESOURCES_DOMAIN . "/img/famfam/application_form_magnify.png",
					array( "nombre" => implode(", ", $criterions->getNames()) )
				);
			}
			*/

			return $inline;
		}

		public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false){
			$data = parent::getInfo($publicMode,  $comeFrom , $usuario);
			if ($comeFrom === 'ficha'){
				$datamodel = new datamodel($data[$this->getUID()]["uid_datamodel"]);
				$data[$this->getUID()]["uid_datamodel"] = $datamodel->getUserVisibleName();
			}
			return $data;
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
			$info = parent::getInfo(true, $usuario);
			$data = array();
	

			$data["nombre"] =  array(
				"innerHTML" => $this->getUserVisibleName(),
				"href" => "../agd/ficha.php?m=dataimport&poid=". $this->uid,
				"className" => "box-it link"
			);

			//$data["modulo"] = $this->obtenerTipo();

			return array($this->getUID() => $data);
		}


		public static function defaultData($data, Iusuario $usuario = null){
			if( $usuario instanceof Iusuario ){
				$data["uid_usuario"] = $usuario->getUID();
			}

			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;

			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_INIT:
				case elemento::PUBLIFIELDS_MODE_NEW:
				case elemento::PUBLIFIELDS_MODE_EDIT:
				default:
					$fields["name"] = new FormField(array("tag" => "input", "type" => "text", "blank" => false ));

					if( $usuario instanceof usuario ){
						$fields["uid_datamodel"]	= new FormField(array("tag" => "select", "data" => $usuario->obtenerDataModels(), "blank" => false ));
						// $fields['uid_elemento_destino'] = new FormField(array('tag'=>'select', 'data' => $usuario->perfilActivo()->getCompany()->obtenerEmpresasInferioresMasActual(), 'blank' => false ));
						if( $modo == elemento::PUBLIFIELDS_MODE_NEW ){
							$fields["uid_usuario"] = new FormField;
						}
					}
				break;
			}

			return $fields;
		}
		
	}
?>
