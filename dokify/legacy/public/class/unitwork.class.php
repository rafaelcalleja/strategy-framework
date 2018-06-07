<?php
	class unitwork {
		protected $key;
		protected $empleado;
		protected $description;
		protected $path;
		protected $root;
		protected $db;

		const FIELD = "IF( INSTR(unitwork,' '), SUBSTRING(unitwork, 1, INSTR(unitwork,' ')-1), unitwork )";
		const MAX_DEEP = 5;
		
		public function __construct($key, empleado $empleado = NULL){
			$this->key = $key;
			$this->empleado = $empleado;

			// pasamos a path la primera parte de lo introducido, p.ej: EEM/EIF/B BCAM MSS Services South Europe
			$parts = explode(" ", $key);
			$path = array_shift($parts);
			// path ahora es: EEM/EIF/B

			$this->description = implode(" ", $parts);
			// asignamos el resto de la cadena a description: BCAM MSS Services South Europe

			if( ( $pos = strpos($path, "/") ) === false ){
				// si no hay subunidades, todo es el path de la unidad. es la parte con la que trabajaremos.
				$this->root = NULL;
				$this->path = $path;
			} else {
				// si hay varias /, root es solo hasta la primera: EEM
				$this->root = substr($path, 0, $pos);
				// y path el resto: EIF/B
				$this->path = substr($path, $pos+1); 
			}		

			$this->db = db::singleton();	
		}
		
		public function setEmpleado(empleado $empleado){
			$this->empleado = $empleado;
		}

		public function getPath(){
			return $this->path;
		}

		public function getRoot(){
			return $this->root;
		}
		
		public function getKey(){
			return $this->key;
		}

		public function getFullPath(){
			return "{$this->getRoot()}/{$this->getPath()}";
		}

		public function getEmpleado(){
			return ($this->empleado instanceof empleado?$this->empleado:false);
		}

		public function getUserVisibleName(){
			return $this->getFullPath();
		}

		public function getTableInfo(){
			return array(
				array( "name" => $this->key )
			);
		}
		
		public function isChildOf(Ielemento $elemento){
			$parentUnit = ( $elemento instanceof empleado ) ? $elemento->getUnitWork() : $elemento;
			if( $parentUnit instanceof unitwork ){
				$sql = "SELECT unitwork FROM ". TABLE_EMPLEADO ." 
				WHERE unitwork LIKE '{$this->getFullPath()}%' 
				AND uid_empleado = {$this->empleado->getUID()} ";
				if( $this->db->query($sql, 0, 0) ){
					return true;
				}
			}

			return false;
		}


		public function getCompanies(){
			if( !$empleado = $this->getEmpleado() ){
				error_log("Unitwork {$this->path} should be instanced with a second parameter empleado in order to use the method unitwork::obtenerEmpresas");
				return false;
			} 

			$empresa = $empleado->getCompany();
			
			if ( $corporacion = $empresa->perteneceCorporacion() ) {
				$empresasGrupo = $corporacion->getStartIntList();
			} else {
				$empresasGrupo = $empresa->getStartIntList();
			}

			if( !count($empresasGrupo) ){
				error_log("Unable to find empresas for employee {$empleado->getUID()}");
				return false;
			}

			$sql = "SELECT uid_empresa 
			FROM ". TABLE_EMPLEADO . " INNER JOIN ". TABLE_EMPLEADO ."_empresa USING(uid_empleado) 
			WHERE 1 
			AND ". self::FIELD ." = '{$this->getFullPath()}' 
			AND uid_empresa in ({$empresasGrupo->toComaList()})
			GROUP BY uid_empresa";
			$items = $this->db->query($sql, "*", 0, "empresa");

			if( count($items) ){
				return new ArrayObjectList($items);
			}

			return false;
 		}

		private function getSQLFiltroEmpresa(){
			if( $empresas = $this->getCompanies() ){
				$sql = " uid_empleado IN ( SELECT uid_empleado FROM ". TABLE_EMPLEADO ."_empresa WHERE uid_empresa IN ({$empresas->toComaList()}) )";
				return $sql;
			} else {
				// error_log("El unitwork {$this->path} no tiene empresas!");
				return " 0";
			}
		}

		public function getMysqlSearchString($deep=1){
			$deepchars = implode( array_fill(0, $deep, '_'), '');
			return $search = ( $this->root ) ? implode( array_fill(0, strlen($this->root), '_'), '') . "/{$this->path}{$deepchars}" : "{$deepchars}";
		}


		public function obtenerEmpleados($manager = NULL, $blacklist = array() ){
			$sql = "SELECT uid_empleado FROM ". TABLE_EMPLEADO . " WHERE ". self::FIELD ." = '{$this->getFullPath()}'
			AND {$this->getSQLFiltroEmpresa()} ";
			
			if( is_bool($manager) ){
				$sql .= " AND es_manager = ". (int) $manager;
			}
			
			if( $blacklist instanceof empleado ){
				$sql .= " AND uid_empleado != {$blacklist->getUID()} ";
			} elseif( $blacklist instanceof ArrayObjectList && count($blacklist) ){
				$sql .= " AND uid_empleado NOT IN ({$blacklist->toComaList()}) ";
			}

			$empleados = $this->db->query($sql, "*", 0, "empleado");
			return new ArrayObjectList($empleados);
		}

		/***
		  ** DEVUELVE LAS UNITWORKS HIJAS DE LA ACTUAL
		  ** @param $param [ TRUE | empleado ] Si evalua true entonces ha de buscar unidades con managers. Si es un empleado, se usará para dar mas información del origen de la unidad
		***/
		public function getChilds($param=1){
			$sql = "SELECT ". self::FIELD ."  FROM ". TABLE_EMPLEADO ." 
			WHERE unitwork != '{$this->key}' 
			AND {$this->getSQLFiltroEmpresa()} ";

			// Buscamos tambien en cualquier path similar	
			$find = $sql . " AND ". self::FIELD ." LIKE '{$this->getMysqlSearchString()}' ";
			if( is_numeric($param) && $param > 1 ) $find .= " AND es_manager = 1";
			$find .= " GROUP BY ". self::FIELD;
			//if( $n > 1 ){ dump($find); exit; }

			$paths = $this->db->query($find, "*", 0);	



			$findeep = "SELECT ". self::FIELD ." FROM ". TABLE_EMPLEADO ." WHERE 1
				AND unitwork != '{$this->key}' AND ". self::FIELD ." LIKE '{$this->getMysqlSearchString()}%' 
			";

			foreach($paths as $path){
				$unit = new unitwork($path);
				$findeep .= "AND ". self::FIELD ." NOT LIKE '{$unit->getMysqlSearchString()}%'";
			}
			$findeep .= "GROUP BY ". self::FIELD;

			$deepPaths = $this->db->query($findeep, "*", 0);
			if( count($deepPaths) ){
				$paths = array_merge($paths, $deepPaths);
			}


			$units = array();
			foreach($paths as $path){
				// Apaño para prevenir mas niveles de los que queremos
				$previousPath = substr($path, 0, -1);
				if( in_array($previousPath, $paths) ) continue;
				
				$units[] = new unitwork($path, $param instanceof empleado?$param:false );
			}

			return $units;
		}

		
		public function getParent(empresa $empresa){
			return self::getParentUnit($this, $empresa);
		}

		// devuelve la unidad superior más próxima que tenga empleados activos. 
		public static function getParentUnit(unitwork $unitwork, empresa $empresa, $deep = 0) {
			$unitName = $unitwork->getFullPath();
			$nextUnitName = self::getParentName($unitName);
			
			$startList = $empresa->getStartIntList();
			if ($corp = $empresa->perteneceCorporacion()) {
				$startList = $corp->getStartIntList();
			}

			$sql = " SELECT DISTINCT unitwork FROM ".TABLE_EMPLEADO." WHERE 1 
			AND ".self::FIELD." = '{$nextUnitName}' 
			AND uid_empleado IN (SELECT uid_empleado FROM ".TABLE_EMPLEADO."_empresa WHERE papelera=0 
			AND uid_empresa IN ({$startList->toComaList()}) ) LIMIT 1 "; 
			$db = db::singleton();
			if ($parentUnit = $db->query($sql,'*',0,'unitwork')) {
				return reset($parentUnit);
			} else if ($deep <= self::MAX_DEEP && strlen($nextUnitName)) {
				if ($grandParentUnit = self::getParentUnit(new unitwork($nextUnitName),$empresa,$deep+1)) {
					return $grandParentUnit;
				} 
			} 

			return false;
		}

		public static function getSiblingUnit(unitwork $unitwork, empresa $empresa) {
			$unitName = $unitwork->getFullPath();
			$siblingName = self::getSiblingName($unitName);
			
			$startList = $empresa->getStartIntList();
			if ($corp = $empresa->perteneceCorporacion()) {
				$startList = $corp->getStartIntList();
			}
			
			$sql = " SELECT DISTINCT unitwork FROM ".TABLE_EMPLEADO." WHERE 1 
			AND ".self::FIELD." LIKE '{$siblingName}' 
			AND uid_empleado IN (SELECT uid_empleado FROM ".TABLE_EMPLEADO."_empresa WHERE papelera=0 
			 AND uid_empresa IN ({$startList->toComaList()})  ) ORDER BY ".self::FIELD." LIKE '{$siblingName}' LIMIT 1 ";
			$db = db::singleton();

			if ($siblingUnit = $db->query($sql,'*',0,'unitwork')){
				return reset($siblingUnit);
			}
			return false;
		}

		public static function extractFullPath($key) {
			$parts = explode(" ", $key);
			return array_shift($parts);
		}

		public static function extractRoot($key) {
			$key = self::extractFullPath($key);

			if( ( $pos = strpos($key, '/') ) === false ){
				return null;
			} else {
				return substr($key, 0, $pos);
			}	
		}

		public static function extractPath($key) {
			$key = self::extractFullPath($key);

			if( ( $pos = strpos($key, "/") ) === false ){
				return $key;
			} else {
				return substr($key, $pos+1); 
			}	
		}

		public static function getSiblingName($key) {
			$key = self::extractFullPath($key);

			$root = self::extractRoot($key);
			// $newRoot = implode( array_fill(0, strlen($root), '_'), '')
			// if (strlen($newRoot)>0) {
			// 	$newRoot .= '/';
			// }
			$newRoot = '';
			if (strlen($root)) {
				$newRoot.='%/';
			} 
			return $newRoot.self::extractPath($key);
		}

		public static function getParentName($key) {
			$key = self::extractFullPath($key);
			$path = self::extractPath($key);
			$nextPath = substr($path, 0, strlen($path)-1);
			if (strlen($nextPath)>0) {
				$nextPath = '/'.$nextPath;
			}
			return self::extractRoot($key).$nextPath;
		}

		public static function getManagerLocal(unitwork $unitwork, empresa $empresa) {
			$unitName = $unitwork->getFullPath();

			$startList = $empresa->getStartIntList();
			if ($corp = $empresa->perteneceCorporacion()) {
				$startList = $corp->getStartIntList();
			}

			$sql = " SELECT uid_empleado FROM ".TABLE_EMPLEADO." 
			WHERE ".self::FIELD." = '{$unitName}' 
			AND es_manager = 1 
			AND uid_empleado IN (SELECT uid_empleado FROM ".TABLE_EMPLEADO."_empresa WHERE papelera=0 
			AND uid_empresa IN ({$startList->toComaList()}) ) "; 
			$db = db::singleton();
			if ($empleados = $db->query($sql,'*',0,'empleado')) {
				// devolvemos el primero
				return reset($empleados);
			}
			return false;
		}



		public function getManager(empleado $empleado) {
			if( !$empleado instanceof empleado ) return false;
			if (!$empresa = $empleado->getCompany()) {
				return false;
			}

			if ( !$empleado->isManager() && $manager = self::getManagerLocal($this, $empresa)) {
				return $manager;
			}

			// si el empleado es manager, o no lo es pero no hay manager local
			// buscamos unidad padre (depth first)
			if ($parentUnit = self::getParentUnit($this,$empresa)) {
				// si hay unidad padre, buscamos su manager local
				if ($parentManager = self::getManagerLocal($parentUnit,$empresa)) {
					return $parentManager;
				} 
			} else if ($siblingUnit = self::getSiblingUnit($this,$empresa)) {
				if ($siblingManager = self::getManagerLocal($siblingUnit,$empresa)) {
					return $siblingManager;
				} 	
			}

			return false;
		}
	}
?>
