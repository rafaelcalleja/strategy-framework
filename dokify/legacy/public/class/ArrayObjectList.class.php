<?php
	class ArrayObjectList extends extendedArray {

		public static function factory() {
			$args = func_get_args();
			$data = array_shift($args);
			$array = explode(",", $data);
			$class = get_called_class();

			$list = new $class;
			foreach($array as $obString) {
				$arguments = array_merge(array($obString), $args);
				$item = call_user_func_array('elemento::factory', $arguments);
				$list[] = $item; 
			}

			return $list;
		}

		public function sort ($fn) {
			$array = $this->getArrayCopy();
			usort($array, $fn);
			return new self($array);
		}

		public function toArray ($app = null) {
			$data 	= array();

			foreach ($this as $item) {
				$data[] = $item->toArray($app);
			}

			return $data;
		}

		public function getViewData (elemento $usuario = null, $config = 0, $extraData = null, $options = true){
			$data 	= array();
			$parent = $usuario instanceof Iusuario ? $usuario->getCompany() : false;

			foreach ($this as $item) {
				//objeto donde guardaremos los datos de este documento
				$datosItem = $item->getViewData($usuario);

				//guardamos el objeto actual al global
				$data[] = $datosItem;

			}

			return $data;
		}

		public function toArrayData(Iusuario $usuario, $config = 0, $extraData = null, $options = true){
			$data = array();
			$parent = ( $usuario instanceof usuario ) ? $usuario->getCompany() : false;
			foreach( $this as $item ){

				//objeto donde guardaremos los datos de este documento
				$datosItem = array();

				$getTableInfoMethod = array($item, "getTableInfo");
				if (method_exists($item, "getTableInfo")) {
					$datosItem["lineas"] =  call_user_func($getTableInfoMethod, $usuario, NULL, $extraData);
				}

				//$datosItem["lineas"] = $item->getTableInfo($usuario, $parent, $extraData);
				
				$getInlineArrayMethod = array($item, "getInlineArray");
				if (method_exists($item, "getInlineArray")) {
					$datosItem["inline"] =  call_user_func($getInlineArrayMethod, $usuario, true, $extraData);
				}

				//$datosItem["options"] = config::obtenerOpciones( $item->getUID(), $item->getModuleId(), $usuario, true );
				if( $options ){
					$datosItem["options"] = $item->getAvailableOptions( $usuario, true, $config, true, false, $extraData );
				}

				$getTreeDataMethod = array($item, "getTreeData");
				if (method_exists($item, "getTreeData")) {
					$datosItem["tree"] = call_user_func($getTreeDataMethod, $usuario, $extraData);
				}


				$getLineClassMethod = array($item, "getLineClass");
				if (method_exists($item, "getLineClass")) {
					if( $class = $item->getLineClass($parent, $usuario,$extraData) ){
						$datosItem["lineas"]["className"] = $class;
					}
				}


				$getLineClick = array($item, "getClickURL");
				if (method_exists($item, "getClickURL")) {
					if( $href = call_user_func($getLineClick, $usuario, $config, $extraData) ){
						$datosItem["href"] = $href;
					}
				} else {
					// La accion por defecto de una linea es ver sus elementos
					$lineOptions = $usuario->getAvailableOptionsForModule($item->getModuleId(), "elementos");
					if ($lineOptions && $accion = reset($lineOptions)) {
						$datosItem["href"] = $accion["href"] . "?poid=" . $item->getUID();
					}
				}


				$datosItem["type"] = get_class($item);

				//guardamos el objeto actual al global
				$data[] = $datosItem;

			}

			return $data;
		}

		/** COVIERTE ESTA COLECCION APLICANDO EL METODO OBTENER{$CLASSNAME} A CADA ITEM **/
		public function transform($className){
			$array = $this->getArrayCopy();
			array_walk($array, function(&$item, $key, $className){
				// La clase empresa tiene una funcion getCompany, es un hack temporal para que funcione con el nombre en ingles.
				if ($className == "empresa"){
					$method = "getCompany";
				}else{
					$method = "obtener{$className}";
				}
				$item = $item->$method();
			}, $className);

			return new ArrayObjectList($array);
		}


		public function foreachCall($fn, $params = array()){
			$collection = $this->each($fn, $params);
			return $collection->unique();
		}

		public function each($fn, $params = array()){
			$collection = new ArrayObjectList;
			foreach($this as $i => $item ){ 
				$collection = $collection->merge(@call_user_func_array(array($item, $fn), $params));
			}
			return $collection;
		}
		
		public function reduce($fn, $params = array(), $returnType = 'ArrayObjectList' ){
			$collection = array();
			foreach($this as $i => $item ){ 
				$collection[] = call_user_func_array(array($item, $fn), $params);
			}
			return new $returnType(array_unique($collection));
		}

		public function getData($colname){
			return $this->foreachCall("obtenerDato", array($colname))->getArrayCopy();
		}


		/** OBTENER LAS ETIQUETAS COMUNES A TODOS LOS ITEMS **/
		public function obtenerEtiquetas(){
			$classNames = $this->getClassNames();
			if( count($classNames) && $class = reset($classNames) ){
				$arrayCopy = $this->getArrayCopy();
				$aux = reset($arrayCopy);
				if( $aux instanceof etiquetable ){
					$tabla = constant("TABLE_". strtoupper($class)) . "_etiqueta";

					$lists = array();
				
					$sql = "SELECT uid_etiqueta FROM";
					foreach($this as $i => $item ){
						$list = "( SELECT uid_etiqueta FROM {$tabla} WHERE uid_{$class} = {$item->getUID()} GROUP BY uid_etiqueta) as list_{$i}";
						if( $i ) $list .= " USING(uid_etiqueta) ";
						$lists[] = $list;
					}

					$sql = "SELECT uid_etiqueta FROM ". implode(" INNER JOIN ", $lists) . " GROUP BY uid_etiqueta";


					$array = db::get($sql, "*", 0, "etiqueta");
					return new ArrayObjectList($array);
				} else {
					throw new Exception("Only collections with etiquetable items can use this method");
				}
			} else {
				throw new Exception("Only collections with one type of object cant use this method");
			}
		}


		/** OBTENER LOS AGRUPADORES COMUNES A TODOS LOS ITEMS **/
		public function obtenerAgrupadores(Iusuario $usuario){
			$db  		= db::singleton();
			$classNames = $this->getClassNames();

			if (count($classNames) && $class = reset($classNames)) {
				$arrayCopy = $this->getArrayCopy();
				$aux = reset($arrayCopy);
				if ($aux instanceof categorizable) {

					$empresa = $usuario->getCompany();
					$agrupadores = $empresa->obtenerAgrupadoresVisibles();


					$tabla = TABLE_AGRUPADOR . "_elemento";

					$lists = array();
					$sql = "SELECT uid_agrupador FROM";
					foreach($this as $i => $item ){
						$list = "( SELECT uid_agrupador FROM {$tabla} WHERE uid_elemento = {$item->getUID()} AND uid_modulo = {$aux->getModuleId()} GROUP BY uid_agrupador) as list_{$i}";
						if( $i ) $list .= " USING(uid_agrupador) ";
						$lists[] = $list;
					}

					$intList = $agrupadores && count($agrupadores) > 1 ? $agrupadores->toIntList() : "0";
					$sql = "SELECT uid_agrupador FROM ". implode(" INNER JOIN ", $lists) . " WHERE 1 AND uid_agrupador IN ({$intList}) GROUP BY uid_agrupador";


					$array = $db->query($sql, "*", 0, "agrupador");
					return new ArrayObjectList($array);
				} else {
					throw new Exception("Only collections with categorizable items can use this method");
				}
			} else {
				throw new Exception("Only collections with one type of object cant use this method");
			}
		}


		public function toIntList(){
			return new ArrayIntList( array_map(function($item){
				if( $item instanceof elemento ){
					return $item->getUID();
				}
			}, $this->getArrayCopy()) );
		}

		public function getNames(){
			return array_map(function($item){
				return $item->getUserVisibleName();
			}, $this->getArrayCopy() );
		}

		public function getUserVisibleName(){
			return implode(", ", $this->getNames());
		}
		
		public function getClassNames(){
			return array_unique(array_map(function($item){
				return get_class($item);
			}, $this->getArrayCopy() ));
		}

		public function discriminar($extraer){
			if( !$extraer ) return $this;
			$coleccion = $this->getArrayCopy();
			$resultado = new ArrayObjectList();

			if( is_object($extraer) && !$extraer instanceof ArrayObjectList ){ 
				$idsActuales = array($extraer->getUID());
			} else {
				$idsActuales = $extraer->toIntList()->getArrayCopy();
			}


			if( is_traversable($coleccion) && count($coleccion) ){
				foreach($coleccion as $objeto){
					if( !in_array($objeto->getUID(), $idsActuales) ){
						$resultado[] = $objeto;
					}
				}
			}

			return $resultado;
		}

		public function match($collection){	
			$result = new ArrayObjectList;
			$return = false;
			if( $collection instanceof ArrayObjectList ){
				foreach($collection as $item){
					if( $this->contains($item) ){
						$return = true;
						$result[] = $item;
					} 
				}
			}
			if ($return) return $result;
			else return false;
		}

		public function toComaList(){
			return implode(",", $this->toIntList()->getArrayCopy() );
		}

		/** comprobar si el valor se encuentra en la lista **/
		public function contains($param){
			return in_array("$param", array_map('strtolower', $this->getArrayCopy()) );
		}

		public function compareTo(ArrayObjectList $list){
			if( !$list instanceof ArrayObjectList ) return false;

			$arrayA = $this->toIntList()->getArrayCopy();
			$arrayB = $list->toIntList()->getArrayCopy();

			sort($arrayA);
			sort($arrayB);

			return implode(",", $arrayA) === implode(",", $arrayB);
		}

		public function toUL($return = false){
			$coleccion = $this->getArrayCopy();
			$cadena = "<ul>";
				foreach($coleccion as $item){
					$cadena .= "<li>{$item->getUserVisibleName()}</li>";
				}
			$cadena .=  "</ul>";
			if ($return) {
				return $cadena;
			}
			echo $cadena;
		}
	}
?>
