<?php

	class exportacion {
		const TYPE_ASIGNACION = "asignacion";
		const TYPE_ASIGNACIONRELACIONES = "asignacionrelaciones";
		const TYPE_ASIGNACIONEMPRESA = "asignacionempresa";

		protected $name;
		protected $module;
		protected $list;

		public function __construct($name, $module, $list = false){

			$this->name = $name;
			$this->module = $module;
			$this->list = $list;
		}

		public function getName(){
			return $this->name;
		}

		public function getIcon(){
			return RESOURCES_DOMAIN . "/img/famfam/page_white_excel.png";
		}

		public function getURL($parent){
			return "informes.php?poid=". $parent->getUID() ."&m=". $this->module . "&name=". $this->name ."&action=export&send=1";
		}

		public function getType(){
			return $this->module;
		}

		public function getUserVisibleName(){
			$lang = Plantilla::singleton();
			return $lang->getString("exportacion_". $this->module ."_". $this->name );
		}

		public function export($formato="xls", $usuario, $parent = false){

			switch($formato){
				case "xls":
					return self::exportXLS($this->name, $this->module, $usuario, $this->list,  $parent);
				break;
			}
		}

		/*---------------------------- UTILES ----------------------------------*/
		public static function getSqlRebotesModulo($modulo, $usuario){
			$company = $usuario->getCompany();
			$empresas = $company->obtenerEmpresasSolicitantes();
			$sql = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPAMIENTO ." WHERE uid_empresa IN ({$empresas->toComaList()})";
			$list = db::get($sql, "*", 0);
			$comaList = ($list && count($list)) ? implode(",",$list) : '0';

			$sql = "(
				SELECT 
					rel.uid_elemento, 
					( SELECT nombre FROM ". TABLE_AGRUPAMIENTO ." a WHERE a.uid_agrupamiento = aa.uid_agrupamiento ) tipoasignacion,
					( SELECT nombre FROM ". TABLE_AGRUPADOR ." a WHERE a.uid_agrupador = rel.uid_agrupador ) asignacion,
					( SELECT abbr FROM ". TABLE_AGRUPADOR ." a WHERE a.uid_agrupador = rel.uid_agrupador ) abbrasignacion,
					rebote.tiporebote,
					( SELECT nombre FROM ". TABLE_AGRUPADOR ." a WHERE a.uid_agrupador = rebote.uid_agrupador ) rebote,
					( SELECT abbr FROM ". TABLE_AGRUPADOR ." a WHERE a.uid_agrupador = rebote.uid_agrupador ) abbrrebote,
					aa.uid_empresa
				FROM ". TABLE_AGRUPADOR ."_elemento rel 
				LEFT JOIN ( 
					SELECT uid_agrupador, uid_elemento, ( SELECT nombre FROM ". TABLE_AGRUPAMIENTO ." a WHERE a.uid_agrupamiento = aa.uid_agrupamiento ) tiporebote
					FROM ". TABLE_AGRUPADOR ."_elemento 
					INNER JOIN ". TABLE_AGRUPADOR ." aa USING( uid_agrupador )
					WHERE uid_agrupamiento IN (". implode(",",$list) .") AND uid_modulo = ". util::getModuleId("agrupador") ." 
					GROUP BY uid_elemento, uid_agrupador, uid_agrupamiento
				) as rebote
				ON rel.uid_agrupador = rebote.uid_elemento
				INNER JOIN ". TABLE_AGRUPADOR ." aa
				ON rel.uid_agrupador = aa.uid_agrupador
				WHERE 1
					AND uid_agrupamiento IN ({$comaList})
					AND ( 
						aa.uid_agrupador IN (SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento WHERE uid_modulo = 1 AND uid_elemento = {$company->getUID()})
						OR aa.uid_agrupador IN (SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ." WHERE uid_empresa = {$company->getUID()})
					)
					AND rel.uid_modulo = ". util::getModuleId($modulo) ." 
					AND rel.rebote = 0
					AND aa.papelera = 0
				GROUP BY rel.uid_elemento, rel.uid_agrupador, rebote.uid_agrupador
				ORDER BY rel.uid_elemento
			)
			";
			return $sql;
		}


		/*---------------------------- EXPORTACIONES ----------------------------------*/

		/** NOS DARA UN LISTADO DE AGRUPADORES Y LAS EMPRESAS ASIGNADAS A ELLOS **/
		private static function getExportAsignacionempresa($module, $usuario, $list, $parent){
			$list = implode(",",$list);
			$asignaciones = self::getSqlRebotesModulo("agrupador", $usuario);

			$exportSQL = "
				SELECT nombre, abbr, empresa
				FROM ". TABLE_AGRUPADOR ." 
				INNER JOIN (
					SELECT uid_agrupador, nombre as empresa FROM ". TABLE_AGRUPADOR ."_elemento 
					INNER JOIN ". TABLE_EMPRESA ."
					ON empresa.uid_empresa = uid_elemento
					WHERE uid_modulo = ". util::getModuleId("empresa") ."
				) as assign
				USING( uid_agrupador )
				WHERE uid_agrupador IN ($list)
			";

			$exportSQL = "
				SELECT nombre, abbr, tipoasignacion, asignacion, tiporebote, rebote, empresa
				FROM ". TABLE_AGRUPADOR ." agr
				INNER JOIN $asignaciones as assign
				ON assign.uid_elemento = agr.uid_agrupador
				INNER JOIN (
					SELECT uid_agrupador, nombre as empresa FROM ". TABLE_AGRUPADOR ."_elemento 
					INNER JOIN ". TABLE_EMPRESA ."
					ON empresa.uid_empresa = uid_elemento
					WHERE uid_modulo = ". util::getModuleId("empresa") ."
				) as rel
				USING( uid_agrupador )
				WHERE uid_agrupador IN ($list)
			";


			if( $parent ){
				$exportSQL .= " AND uid_agrupamiento = $parent";
			}

			return $exportSQL;
		}

		private static function getExportAsignacion($module, $usuario, $list, $parent){

			if ($parent) {
				$company = new empresa($parent);
			} else {
				$company = $usuario->getCompany();
			}
			if ($company->esCorporacion()) {
				$empresasCorporacion = $company->obtenerEmpresasInferiores()->toComaList();
			}

			$empresasCliente = $company->obtenerEmpresasSolicitantes()->toComaList();

			$SQLEmpresa = "(
				SELECT if(empresa.uid_empresa={$company->getUID()},'', empresa.nombre) nombre FROM ". TABLE_EMPRESA ." INNER JOIN ". TABLE_AGRUPADOR ." sa USING(uid_empresa)
				WHERE sa.uid_agrupador = assign.uid_agrupador LIMIT 1
			)";
			

			switch($module){
				case "empresa":
					$companyList = implode(",", $list);
					$exportSQL = "
						SELECT 
							e.nombre, cif, 
							( 
								SELECT cat.nombre FROM ". TABLE_AGRUPADOR ." agr 
								INNER JOIN ". TABLE_AGRUPAMIENTO ." cat
								ON cat.uid_agrupamiento = agr.uid_agrupamiento
								WHERE agr.uid_agrupador = assign.uid_agrupador 
								LIMIT 1
							) tipo,
							( 
								SELECT agr.nombre FROM ". TABLE_AGRUPADOR ." agr WHERE agr.uid_agrupador = assign.uid_agrupador 
							) asignacion,
							( 
								SELECT agr.abbr FROM ". TABLE_AGRUPADOR ." agr WHERE agr.uid_agrupador = assign.uid_agrupador 
							) abbr,
							{$SQLEmpresa} cliente,
							( 	
								SELECT concat(nombre, ' ', apellidos, ' - ', email, ' - ', telefono, ' - ', movil) 
								FROM ". TABLE_CONTACTOEMPRESA ." contacto 
								WHERE contacto.uid_empresa = e.uid_empresa 
								LIMIT 1
							) contacto
						FROM ". TABLE_EMPRESA ." e
						INNER JOIN ". TABLE_AGRUPADOR ."_elemento assign
						ON e.uid_empresa = assign.uid_elemento
						INNER JOIN ". TABLE_AGRUPADOR ." a
						USING( uid_agrupador )
						WHERE 1		
						AND a.uid_empresa = {$company->getUID()}			
						AND uid_modulo = 1	
						AND a.papelera = 0					
					";

					if (count($list)) $exportSQL .= " AND e.uid_empresa IN ( $companyList )
						";
					$exportSQL .=	"GROUP BY assign.uid_agrupador, e.uid_empresa
						;";
				break;
				case "empleado":
					$employeeList = implode(",", $list);

					$exportSQL = "
						SELECT 
							e.nombre, e.apellidos, ";
					if ($usuario->accesoAccionConcreta(8,10,'','dni')) {
						$exportSQL .= " e.dni, ";
					}
					$exportSQL .= " empresa.nombre as empresa, agupamiento.nombre as tipo, agrupador.nombre as asignacion, agrupador.abbr as abbr, {$SQLEmpresa} cliente
								FROM ". TABLE_EMPLEADO . " e
								INNER JOIN ". TABLE_EMPLEADO ."_empresa ee
								USING( uid_empleado )
								INNER JOIN ". TABLE_EMPRESA ." empresa
								USING( uid_empresa )
								INNER JOIN ". TABLE_AGRUPADOR ."_elemento assign
								ON e.uid_empleado = assign.uid_elemento
								INNER JOIN ". TABLE_AGRUPADOR ." agrupador
								using(uid_agrupador)
								INNER JOIN ". TABLE_AGRUPAMIENTO ." agupamiento
								using(uid_agrupamiento)
								
								WHERE uid_modulo = 8
								AND ee.uid_empresa = {$company->getUID()}
								AND agupamiento.uid_empresa IN ({$empresasCliente})
								AND agrupador.papelera = 0	
						
					";


					if (isset($empresasCorporacion)) {
						// Desde el punto de vista de una corporacion, los empleados propios son todos los de las empresas de la corporación.
						$empleadosPropios = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa IN ({$empresasCorporacion})";
					} else {
						//No corporacion, los empleados propios son los de la empresa del usuario.
						$empleadosPropios = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa = {$company->getUID()}";
					}

					//Filtramos por la lista seleccionada, si se da el caso.
					if (count($list)) $empleadosPropios .= " AND uid_empleado IN ($employeeList) ";

					$empleadosVisibles =  0;
					if (count($empresasCliente)) {
						//Checqueamos la visibilidad de las empresas superiores.
						$empleadosVisibles = "SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_visibilidad WHERE uid_empresa IN ({$empresasCliente})";
							if (count($list)) $empleadosVisibles .= " AND uid_empleado IN ($employeeList) ";
					}
					
					$exportSQL .= " AND (e.uid_empleado IN ($empleadosPropios) OR e.uid_empleado IN ($empleadosVisibles))";

					$exportSQL .= " GROUP BY assign.uid_agrupador, e.uid_empleado ;";

				break;
				case "maquina":
					$machineryList = implode(",", $list);


					$exportSQL = "SELECT m.serie, m.nombre, empresa.nombre as empresa, agupamiento.nombre as tipo, agrupador.nombre as asignacion, agrupador.abbr as abbr, {$SQLEmpresa} cliente
								FROM ". TABLE_MAQUINA . " m
								INNER JOIN ". TABLE_MAQUINA ."_empresa me
								USING( uid_maquina )
								INNER JOIN ". TABLE_EMPRESA ." empresa
								USING( uid_empresa )
								INNER JOIN ". TABLE_AGRUPADOR ."_elemento assign
								ON m.uid_maquina = assign.uid_elemento
								INNER JOIN ". TABLE_AGRUPADOR ." agrupador
								using(uid_agrupador)
								INNER JOIN ". TABLE_AGRUPAMIENTO ." agupamiento
								using(uid_agrupamiento)
								
								WHERE uid_modulo = 14
								AND agupamiento.uid_empresa IN ({$empresasCliente})
								AND agrupador.papelera = 0
						
					";

					if (isset($empresasCorporacion)) {
						$maquinasPropias = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa IN ({$empresasCorporacion})";
					} else {
						$maquinasPropias = "SELECT uid_maquina FROM ". TABLE_MAQUINA ."_empresa WHERE uid_empresa = {$company->getUID()}";
					}

					if (count($list)) $maquinasPropias .= " AND uid_maquina IN ($machineryList) ";

					$maquinasVisibles =  0;
					if (count($empresasCliente)) {
						$maquinasVisibles = "SELECT uid_maquina FROM ". TABLE_EMPLEADO ."_visibilidad WHERE uid_empresa IN ({$empresasCliente})";
							if (count($list)) $maquinasVisibles .= " AND uid_maquina IN ($machineryList) ";
					}
					
					$exportSQL .= " AND (m.uid_maquina IN ($maquinasPropias) OR m.uid_maquina IN ($maquinasVisibles))";
					$exportSQL .=	"GROUP BY assign.uid_agrupador, m.uid_maquina;";
							
				break;
			}

			return $exportSQL;
		}


		/**
			NOS MOSTRARA DATOS DEL ELEMENTO SEGUIDO DE SUS ASIGNACIONES ( EXCLUYENDO REBOTES ) Y LAS ASIGNACIONES DE SUS ASIGNACINES ( LOS REBOTES )
		**/
		private static function getExportAsignacionrelaciones($module, $usuario, $list, $parent){

			if ($parent) {
				$company = new empresa($parent);
			} else {
				$company = $usuario->getCompany();
			}

			$maxCompanyList = $company->getAllCompaniesIntList();
			$startList = ($corp = $company->perteneceCorporacion()) ? $corp->getStartIntList() : $company->getStartIntList();

			$SQLEmpresa = "( SELECT if(empresa.uid_empresa={$company->getUID()},'', empresa.nombre) nombre FROM ". TABLE_EMPRESA . " WHERE empresa.uid_empresa = assign.uid_empresa LIMIT 1 )";

			$corpCompany = "(
				SELECT r.uid_empresa_superior 
				FROM ". TABLE_EMPRESA ."_relacion r INNER JOIN ". TABLE_EMPRESA ." em ON r.uid_empresa_superior = em.uid_empresa
				WHERE r.uid_empresa_inferior = v.uid_empresa AND em.activo_corporacion = 1
				LIMIT 1
			)";

			switch($module){
				case "empresa":
					$db = db::singleton();
					$temporal = "TEMPORARY";
					//$temporal = "";
					

					// Formamos la sql
					$companyList = implode(",", $list);

					$asignaciones = self::getSqlRebotesModulo("empresa", $usuario);
					$getsql = "
						SELECT 
							null, nombre, cif, ( 	
								SELECT concat(nombre, ' ', apellidos, ' - ', email, ' - ', telefono, ' - ', movil) 
								FROM ". TABLE_CONTACTOEMPRESA ." contacto 
								WHERE contacto.uid_empresa = e.uid_empresa 
								LIMIT 1
							) contacto,
							assign.tipoasignacion tipo, assign.asignacion, assign.abbrasignacion, 
							$SQLEmpresa empresa,
							assign.tiporebote, assign.rebote, assign.abbrrebote
						FROM ". TABLE_EMPRESA . " e
						INNER JOIN $asignaciones as assign 
							ON assign.uid_elemento = e.uid_empresa
						WHERE e.uid_empresa IN ({$maxCompanyList->toComaList()})						
						";
					if (count($list)) $getsql .= " AND e.uid_empresa IN ( $companyList )
						";

					$temporary = DB_TMP . ".export_". uniqid();
					$createsql = "CREATE $temporal TABLE $temporary (
						`uid_temp` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`nombre` VARCHAR( 255 ) NOT NULL ,
						`cif` VARCHAR( 255 ) NOT NULL,
						`contacto` VARCHAR( 255 ) NOT NULL,
						`tipo` VARCHAR( 255 ) NOT NULL,
						`asignacion` VARCHAR( 255 ) NOT NULL,
						`abbrasignacion` VARCHAR( 255 ) NOT NULL,
						`cliente` VARCHAR( 255 ) NOT NULL,
						`tiporebote` VARCHAR( 255 ) NOT NULL,
						`rebote` VARCHAR( 255 ) NOT NULL,
						`abbrrebote` VARCHAR( 255 ) NOT NULL
					) ENGINE = MYISAM ;";
					$db->query($createsql);


					$sql = "INSERT INTO $temporary $getsql";
					if( $db->query($sql) ){

						$auxsplit = "{%}";
						$getinfosql = "
							SELECT group_concat(DISTINCT tiporebote SEPARATOR '$auxsplit') cols 
							FROM $temporary 
							GROUP BY cif, tipo, asignacion 
							ORDER BY count( tiporebote ) DESC 
							LIMIT 1
						";
						$data = $db->query($getinfosql, 0, 0);
						$data = explode($auxsplit, $data);

						$sorts = array_map(function($val){
							return "tiporebote = '".$val."' DESC";
						}, $data);



						if( count($data) ){
							$parts = $ocurrences = $insertcols = array();
							foreach( $data as $i => $col ){
								$colname = archivo::cleanFilenameString($col);
								$insertcols[] = db::getGroupPart("rebote", ($i+1), $auxsplit, implode(",",$sorts)) . " as `$colname`";
							}

							return "
								SELECT nombre, cif, tipo, asignacion, abbrasignacion, cliente, ". implode(",",$insertcols) .", abbrrebote 
								FROM $temporary 
								GROUP BY cif, tipo, asignacion
							";
						} // fin hay columnas que crear...
					} // fin si se ha insertado correctamente los datos
				break;
				case "empleado":
					$employeeList = implode(",", $list);
					$asignaciones = self::getSqlRebotesModulo("empleado", $usuario);

					$exportSQL = "
						SELECT 
							e.nombre, e.apellidos, ";
					if ($usuario->accesoAccionConcreta(8,10,'','dni')) {
						$exportSQL .= " e.dni, ";
					}


					$exportSQL .= " empresa.nombre as empresa, assign.tipoasignacion tipo, assign.asignacion, assign.abbrasignacion, $SQLEmpresa empresa, assign.tiporebote 'tipo relacion', assign.rebote relacion, assign.abbrrebote 'abbr relacion'
						FROM ". TABLE_EMPLEADO . " e
						INNER JOIN ". TABLE_EMPLEADO ."_empresa ee
							USING( uid_empleado )
						INNER JOIN ". TABLE_EMPRESA ." empresa
							USING( uid_empresa )
						INNER JOIN $asignaciones as assign 
							ON assign.uid_elemento = uid_empleado
						WHERE (
							assign.uid_empresa IN ({$startList->toComaList()}) 
							OR e.uid_empleado IN (
								SELECT v.uid_empleado FROM ". TABLE_EMPLEADO . "_visibilidad v 
								WHERE v.uid_empleado = e.uid_empleado AND ( v.uid_empresa = assign.uid_empresa OR $corpCompany = assign.uid_empresa )
							)
						) 		
						";
					if (count($list)) $exportSQL .= " AND uid_empleado IN ( $employeeList )";

				break;
				case "maquina":
					$machineryList = implode(",", $list);
					$asignaciones = self::getSqlRebotesModulo("maquina", $usuario);

					$exportSQL = "
						SELECT 
							m.serie, m.nombre, empresa.nombre as empresa, assign.tipoasignacion tipo, assign.asignacion, assign.abbrasignacion, $SQLEmpresa empresa, assign.tiporebote 'tipo relacion', assign.rebote relacion, assign.abbrrebote 'abbr relacion'
						FROM ". TABLE_MAQUINA . " m
						INNER JOIN ". TABLE_MAQUINA ."_empresa ee
							USING( uid_maquina )
						INNER JOIN ". TABLE_EMPRESA ." empresa
							USING( uid_empresa )
						INNER JOIN $asignaciones as assign 
							ON assign.uid_elemento = uid_maquina
						WHERE (
							assign.uid_empresa IN ({$startList->toComaList()}) 
							OR m.uid_maquina IN (
								SELECT v.uid_maquina FROM ". TABLE_MAQUINA . "_visibilidad v 
								WHERE v.uid_maquina = e.uid_maquina AND ( v.uid_empresa = assign.uid_empresa OR $corpCompany = assign.uid_empresa )
							)
						) 								
						";
					if (count($list)) $exportSQL .= " AND uid_maquina IN ( $machineryList )";
				break;
			}

			$exportSQL .= " LIMIT 30000";
			
			return $exportSQL;
		}


		public static function exportXLS($exporttype, $module, $usuario, $userlist = false, $parent = false ){
			// Buscamos que empresas hemos de extraer
			$searchString = "tipo:$module";
			
			if ($parent instanceof empresa) {
				$parent = $parent->getUID();
				// Si $module es tipo empresa no podemos acotar al $parent, de momento quitamos este modificador
				// De momento cuando $module es tipo empresa obtenemos toda la lista de empresas accesibles para ese elemento (contratas y subcontratas)
				$searchString .= " empresa:{$parent}";
			}

			$listTotal = buscador::search($searchString, "uid", $usuario );
			/*
			$searchString = "tipo:$module";
			$searchExport = "uid";
			$listTotal = include( DIR_ROOT . "agd/buscar.php");
			*/

			if( $userlist ){

				$list = array();
				foreach( $userlist as $uid ){
					if( in_array($uid, $listTotal) ){
						$list[] = $uid;
					}
				}
			} else {
				$list = $listTotal;
			}
			
			
			$fn = array("self","getExport". ucfirst($exporttype));
			$exportSQL = call_user_func($fn, $module, $usuario, $list, $parent);

			$excel = new excel( $exportSQL );
			if( !count($list) || !$excel->Generar("exportacion.xls", true) ){
				if( CURRENT_ENV == 'dev' ) dump( db::singleton() );
				echo "<script>alert('No hay datos que exportar');</script>";
			}
		}

	}
?>
