<?php
	class reporte {
		public $nombre;
		public $empresa;

		const TYPE_DOCUMENTOS_ANEXADOS = "documentos_anexados";
		const TYPE_ATENCION_TELEFONICA = "atencion_telefonica";
		const TYPE_LISTADO_EMPLEADOS = "listado_empleados";
		const TYPE_LISTADO_MAQUINAS = "listado_maquinas";
		
		public function __construct($nombre, $empresa=false){
			$this->nombre = $nombre;
			$this->empresa = $empresa;
		}

		public function getUserVisibleName(){
			$tpl = Plantilla::singleton();
			return $tpl->getString("reporte_" . $this->nombre);
		}

		public function getUID(){
			return $this->nombre;
		}

		// ---------- FUNCIONALIDAD

		/**
			NOS INDICA EL NOMBRE QUE HA DE PONER AL PASO i DEL ASISTENTE
		**/
		public function getStepName($step){
			$tpl = Plantilla::singleton();
			switch($this->nombre){
				case self::TYPE_DOCUMENTOS_ANEXADOS:
					switch($step){
						case 2:
							return $tpl->getString("Seleccionar modulo");
						break;
						case 3:
							return $tpl->getString("Seleccionar fechas");
						break;
						case 4:
							return $tpl->getString("Seleccionar modo");
						break;
					}
				break;
				case self::TYPE_ATENCION_TELEFONICA:
					switch($step){
						case 2:
							return $tpl->getString("Seleccionar modo");
						break;
					}
				break;
			}
			return $tpl->getString("descargar");
		}

		/** 
			NOS DEVOLVERA UN CONJUNTO DE OPCIONES PARA form_parts.tpl 
		**/
		public function getOptions($step){
			$tpl = Plantilla::singleton();
			$options = new FieldList;
			switch($this->nombre){
				case self::TYPE_DOCUMENTOS_ANEXADOS:
					switch($step){
						case 2:
							$data = array();
							$modulos = solicitable::getModules();
							foreach( $modulos as $i => $m ){
								$data[$m] = array("innerHTML"=>$m,"value"=>$m);
							}

							$options["modulo"] = new FormField( array( "tag" => "select", "data" => $data ) );
							$options["continuar"] = new FormField( array("tag" => "button", "className"=>"next-step")  );
						break;
						case 3:
							$data = array(
								"normal" => array("innerHTML"=>"normal","value"=>"normal"),
								"avanzado" => array("innerHTML"=>"avanzado","value"=>"avanzado")
							);

							$options["modo"] = new FormField( array( "tag" => "select", "data" => $data ) );
							$options["continuar"] = new FormField( array("tag" => "button", "className"=>"next-step")  );
						break;
					}
				break;
				case self::TYPE_ATENCION_TELEFONICA:
					switch($step){
						case 2:
							$data = array(
								"normal" => array("innerHTML"=>"normal","value"=>"normal"),
								"avanzado" => array("innerHTML"=>"avanzado","value"=>"avanzado")
							);

							$options["modo"] = new FormField( array( "tag" => "select", "data" => $data ) );
							$options["continuar"] = new FormField( array("tag" => "button", "className"=>"next-step")  );
						break;
					}
				break;
				case self::TYPE_LISTADO_EMPLEADOS: case self::TYPE_LISTADO_MAQUINAS:
					switch($step){
						case 2:
							$data = array(
								"lista" => array("innerHTML"=>"Lista","value"=>"lista"),
								"conteo" => array("innerHTML"=>"Conteo","value"=>"conteo")
							);

							$options["modo"] = new FormField( array( "tag" => "select", "data" => $data ) );
							$options["continuar"] = new FormField( array("tag" => "button", "className"=>"next-step")  );
						break;
					}
				break;
			}
			return $options;
		}

		public function generate($POST, usuario $usuario){
			$formato = $POST["formato"];

			switch($this->nombre){
				case self::TYPE_ATENCION_TELEFONICA:
					$modo = strtolower($POST["modo"]);
					$campos = array();
			

					if( $modo && $modo =="avanzado" ){
						$campos[] = "uid_empresa";
						$campos[] = "uid_usuario_sati";
						$campos[] = "uid_usuario_atendido";
						$campos[] = "uid_hilo as hilo";
					} else {
						$campos[] = self::dato("llamada.uid_empresa", "empresa", "nombre")." Empresa";
						$campos[] = self::dato("llamada.uid_usuario_sati", "usuario", "usuario")." 'Usuario SATI'";
						$campos[] = self::dato("llamada.uid_usuario_atendido", "usuario", "usuario")." 'Usuario'";
					}
					$campos[] = "estado";
					$campos[] = "ambito";
					$campos[] = "comentario";
					$campos[] = "date_format(fecha_llamada_sati,'%Y-%m-%d') as fecha";
					$campos[] = "date_format(fecha_llamada_sati,'%i:%H') as hora";
					$campos[] = "hora_fin_llamada_sati as 'hora fin'";
					$campos[] = "fecha_fin_llamada as 'fecha registro'";

					$SQL = "
					SELECT ". implode(",\n", $campos) ."
					FROM ". TABLE_LLAMADA;
				break;



				case self::TYPE_DOCUMENTOS_ANEXADOS:
					$m = strtolower($POST["modulo"]);
					$modo = strtolower($POST["modo"]);


					$campos = array();
					if( $modo && $modo =="avanzado" ){
						$campos[] = "docs.*";
						$campos[] = self::tipoSoliciante("docs")." as tipo_origen";
						$campos[] = "a.estado";
						$campos[] = "docs.uid_elemento_destino as uid_$m";
						$campos[] = "docs.uid_agrupador";
						$campos[] = "a.hash";

					} else {
						$fields = $m::publicFields("table");
						$campos[] = "docs.alias as 'Atributo'";
						$campos[] = "docs.lang as 'Attribute'";
						$campos[] = self::tipoSoliciante("docs")." as 'tipo origen'";
						$campos[] = self::soliciante("docs")." as origen";
						$campos[] = self::estadoDocs("a")." as estado";
						foreach($fields as $campo => $data){
							$campos[] = self::dato("docs.uid_elemento_destino", $m, $campo)." $campo";
						}
						if( $m != "empresa" ){
							$campos[] = self::dato("docs.uid_empresa", "empresa", "nombre")." empresa";
						}
						$campos[] = self::dato("a.uid_agrupador", "agrupador", "nombre")." referencia";
					}

					$campos[] = "DATE_FORMAT(FROM_UNIXTIME(a.fecha_anexion),'%d-%m-%Y') anexion";
					$campos[] = "DATE_FORMAT(FROM_UNIXTIME(a.fecha_emision),'%d-%m-%Y') emision";
					$campos[] = "DATE_FORMAT(FROM_UNIXTIME(a.fecha_expiracion),'%d-%m-%Y') expiracion";
					$campos[] = "if(a.fecha_emision_real, DATE_FORMAT(FROM_UNIXTIME(a.fecha_emision_real),'%d-%m-%Y'), null) 'emision real'";
					$campos[] = "descargas";
					$campos[] = "if( duracion = 0, 'No Caduca', duracion) as duracion";
					$campos[] = "if( descargar, 'Descarga', 'Subida' ) as tipo";
					$campos[] = "if( obligatorio, 'Si', 'No' ) as obligatorio";
					$campos[] = "if( activo = 1, 'Si', 'No' ) as activo";

					// Busqueda de empresas que nos ayuda a limitar los resultados facilmente
					$arrayUids = buscador::export("tipo:empresa", $usuario, "uid");
					$list = count($arrayUids) ? implode(",",$arrayUids) : 0;


				$SQL ="SELECT ". implode(",\n", $campos) ."
				FROM (
					SELECT 
						de.uid_elemento_destino, 
						de.uid_agrupador, 
						da.*,
						ee.uid_empresa,
						". self::idiomaAtributo("da") ." as lang

					FROM ". TABLE_DOCUMENTOS_ELEMENTOS ." de
					INNER JOIN ". TABLE_DOCUMENTO_ATRIBUTO ." da
					USING ( uid_documento_atributo, uid_modulo_destino)
					INNER JOIN ". self::tableEmpresa($m, $list, $this) ." as ee
					ON ee.uid_$m = de.uid_elemento_destino
					WHERE 1
						AND de.papelera = 0
						AND uid_modulo_destino = ". util::getModuleId($m) ."
						AND ee.papelera = 0
						AND ee.uid_empresa IN ( $list )
						AND da.uid_empresa_propietaria IN ($list)
				) as docs LEFT JOIN ". PREFIJO_ANEXOS ."$m a
					ON a.uid_$m = docs.uid_elemento_destino
					AND a.uid_agrupador = docs.uid_agrupador
					AND a.uid_documento_atributo = docs.uid_documento_atributo
				";

				break;
				case self::TYPE_LISTADO_EMPLEADOS: case self::TYPE_LISTADO_MAQUINAS:
					$modo = strtolower($POST["modo"]); // Como listamos?

					$modulo = ( $this->nombre === self::TYPE_LISTADO_EMPLEADOS ) ? "empleado" : "maquina";
					$table = constant("TABLE_". strtoupper($modulo));

					$arrayUids = buscador::export("tipo:empresa", $usuario, "uid");
					$list = count($arrayUids) ? implode(",",$arrayUids) : 0;

					if( $modo === "lista" ){
						$fields = array_keys( $modulo::publicFields(elemento::PUBLIFIELDS_MODE_EDIT)->getArrayCopy() );
		
						array_walk($fields, function(&$val, $index, $modulo){
							$val = $modulo.".". $val;
						}, $modulo);

						$SQL = "
							SELECT ". implode(", ", $fields) .", empresa.nombre as empresa, cif FROM $table 
							INNER JOIN ". $table ."_empresa USING(uid_$modulo) INNER JOIN ". TABLE_EMPRESA ." USING(uid_empresa)
								 WHERE papelera = 0 AND uid_empresa IN ( $list ) AND (
								 	(uid_{$modulo} IN 
										(SELECT uid_{$modulo} FROM {$table}_visibilidad WHERE uid_empresa IN ({$list}))
									)
									OR (uid_{$modulo} IN 
										(SELECT uid_{$modulo} FROM ". $table ."_empresa  WHERE papelera = 0 AND uid_empresa IN ({$list}))
									))
								
						";

					} else {	
						
						$SQL = "
							SELECT empresa.nombre, cif, count(uid_$modulo) conteo FROM ". $table ."_empresa INNER JOIN ". TABLE_EMPRESA ." USING(uid_empresa)
								 WHERE papelera = 0 AND uid_empresa IN ( $list ) AND (
								 	(uid_{$modulo} IN 
										(SELECT uid_{$modulo} FROM {$table}_visibilidad WHERE uid_empresa IN ({$list}))
									)
									OR (uid_{$modulo} IN 
										(SELECT uid_{$modulo} FROM ". $table ."_empresa  WHERE papelera = 0 AND uid_empresa IN ({$list}))
									))
							GROUP BY uid_empresa ORDER BY count(uid_$modulo) DESC
						";

					}
				break;
			}

			if( $formato == "sql" ){
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header("Content-Disposition: attachment; filename=\"report.sql\";");
				header("Content-Transfer-Encoding: binary");
				die($SQL);
			}

			$class = ( $formato == "csv" ) ? "csv" : "excel";

			$exportacion = new $class($SQL);
			$exportacion->Generar("Report", true);

			if( $exportacion->db->getNumRows() === 0 ){
				die("<script>alert('No hay resultados para los criterios seleccionados');</script>");
			}
		}

		// --------------- Auxiliares
		public static function dato($columna, $modulo, $dato){
			return " ( SELECT $dato FROM ". constant("TABLE_". strtoupper($modulo)) ." WHERE $modulo.uid_$modulo = $columna )";
		}

		public static function estadoDocs($table){
			return " CASE $table.estado 
				WHEN ". documento::ESTADO_ANEXADO ." THEN '". documento::status2String(documento::ESTADO_ANEXADO) ."'
				WHEN ". documento::ESTADO_VALIDADO ." THEN '". documento::status2String(documento::ESTADO_VALIDADO) ."'
				WHEN ". documento::ESTADO_CADUCADO ." THEN '". documento::status2String(documento::ESTADO_CADUCADO) ."'
				WHEN ". documento::ESTADO_ANULADO ." THEN '". documento::status2String(documento::ESTADO_ANULADO) ."'
				ELSE '". documento::status2String(documento::ESTADO_PENDIENTE)  ."'
			END";
		}

		public static function nombreElemento($uid, $modulo){
			return " CASE $modulo
				WHEN ". util::getModuleId("empresa") ." THEN ( SELECT nombre FROM ". TABLE_EMPRESA ." WHERE empresa.uid_empresa = $uid )
				WHEN ". util::getModuleId("empleado") ." THEN ( SELECT concat(nombre,' ',apellidos) FROM ". TABLE_EMPLEADO ." WHERE empleado.uid_empleado = $uid )
				WHEN ". util::getModuleId("maquina") ." THEN ( SELECT concat(nombre,' - ', serie) FROM ". TABLE_MAQUINA ." WHERE maquina.uid_maquina = $uid )
				ELSE 'N/A'
			END";
		}
		public static function nombreModulo($modulo){
			return " CASE $modulo
				WHEN ". util::getModuleId("empresa") ." THEN 'Empresa'
				WHEN ". util::getModuleId("empleado") ." THEN 'Empleado' 
				WHEN ". util::getModuleId("maquina") ." THEN 'Maquina'
				ELSE 'N/A'
			END";
		}

		public static function tableEmpresa($modulo, $list=null, $own = null){
			switch($modulo){
				case "empresa":
					$sql =  " ( 
						SELECT uid_empresa_inferior as uid_empresa, papelera, uid_empresa_superior 
						FROM ". TABLE_EMPRESA ."_relacion 
						WHERE papelera = 0
						GROUP BY uid_empresa_inferior
						";

					if( $own instanceof reporte ){
						$sql .= " UNION SELECT {$own->empresa->getUID()}, 0, {$own->empresa->getUID()}";
					}

					$sql .=") ";
					return $sql;
				break;
				case "empleado":case "maquina":
					return " ( 
						SELECT uid_$modulo, uid_empresa, papelera FROM ". constant("TABLE_". strtoupper($modulo)) ."_empresa empresa 
						WHERE empresa.papelera = 0 AND uid_empresa ". ( $list ? " IN ( $list )" : "" ) ."
						GROUP BY uid_$modulo , uid_empresa
					) ";
				break;
			}
		}

		public static function idiomaAtributo($table,$locale="en"){
			return "( SELECT alias FROM ". TABLE_DOCUMENTO_ATRIBUTO ."_idioma i WHERE i.uid_documento_atributo = $table.uid_documento_atributo AND locale = '$locale' )";
		}

		public static function tipoSoliciante($table){
			
			return "case $table.uid_modulo_origen 
				when 11 then ( SELECT agrupamiento.nombre FROM ". TABLE_AGRUPAMIENTO ." INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador aa USING(uid_agrupamiento) WHERE aa.uid_agrupador = $table.uid_elemento_origen LIMIT 1 )
				when 12 then 'Agrupamiento'
				when 1 then 'Empresa' 
			end";

			/**/
			return "if( $table.uid_modulo_origen = 11, (
					SELECT agrupamiento.nombre 
					FROM ". TABLE_AGRUPAMIENTO ." INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador aa
					USING(uid_agrupamiento) 
					WHERE aa.uid_agrupador = $table.uid_elemento_origen LIMIT 1
				 ), 'Empresa')";
			
		}
		
		public static function soliciante($table){
			return "case $table.uid_modulo_origen 
				when 11 then (". self::dato("$table.uid_elemento_origen", "agrupador", "nombre") .")
				when 12 then (". self::dato("$table.uid_elemento_origen", "agrupamiento", "nombre") .")
				when 1 then (". self::dato("$table.uid_elemento_origen", "empresa", "nombre") .")
			end ";
			
			/**/
			return "if( $table.uid_modulo_origen = 11, (
				". self::dato("$table.uid_elemento_origen", "agrupador", "nombre") ."
			), (
				". self::dato("$table.uid_elemento_origen", "empresa", "nombre") ."
			))";
		}

		public static function nombreEmpresa($table,$field="uid_empresa"){
			return "( SELECT nombre FROM ". TABLE_EMPRESA ." WHERE empresa.uid_empresa=$table.$field )";
		}
	}
?>
