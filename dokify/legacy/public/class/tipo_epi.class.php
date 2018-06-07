<?php
	class tipo_epi extends elemento implements Ielemento {

		public function __construct( $param, $extra = false){
			//$this->uid = $param;
			$this->tipo = "tipo_epi";
			$this->tabla = TABLE_TIPO_EPI;

			$this->instance( $param, $extra );
		}

		public static function getRouteName () {
			return 'ppetype';
		}

		public function obtenerElementosActivables(usuario $usuario = NULL) {
			return $usuario->perfilActivo()->getCompany();
		}

		public function toSolicitudEpi(){
			return new solicitud_epi($this);
		}

		public function removeParent(elemento $parent, usuario $usuario = null) {
			return false;
		}

		public function getUserVisibleName(){
	
			$locale = Plantilla::getCurrentLocale();
			$description = false;
			
			if( $locale !== "es" ){
				$tipoEpiLanguage = new traductor($this->getUID(), $this);
				$description = $tipoEpiLanguage->getLocaleValue($locale);
			}

			if ($description) return $description ." ". $this->obtenerDato("modelo") ." ". $this->obtenerDato("fabricante");;
			return $this->obtenerDato("descripcion") ." ". $this->obtenerDato("modelo") ." ". $this->obtenerDato("fabricante");
		}

		public function actualizarEstado( $estado ){
			if( $estado != "" && is_int($estado) ) {
				$sql = "UPDATE ".TABLE_TIPO_EPI." SET estado = ".$estado." WHERE uid_tipo_epi = ".$this->uid;
				//$resultado = db::get( $sql,0,0);
			}
			
			return $sql;			
		}

		public static function obtenerEquipacion(){
			return array(
				"0" => "Ropa",
				"1" => "Equipación condiciones extremas",
				"2" => "Equipación frío",
				"3" => "Otros",
			);
		}
		
		public static function getAll(){
			$sql = "SELECT uid_tipo_epi FROM ". TABLE_TIPO_EPI ." ORDER BY descripcion ASC";
			$tiposEpi = db::get($sql, "*", 0, "tipo_epi");
			return new ArrayObjectList($tiposEpi);
		}

		public function getAvailableOptions(Iusuario $user = NULL, $publicMode = false, $config = 0, $groups=true, $ref=false, $extraData = null ) {
			if( $user->esStaff() ){
				return parent::getAvailableOptions($user, $publicMode, $config, $groups, $ref);
			}
			return null;
		}
		
		public function getInlineArray($usuario=false, $mode=null, $data){
			return null;
		}

		public static function getSearchData(Iusuario $usuario, $papelera = false){
			if (!$usuario->accesoModulo(__CLASS__,true)) return false;

			$searchData[ TABLE_TIPO_EPI ] = array(
				"type" => "tipo_epi",
				"fields" => array("descripcion"),			
				"accept" => array(
					"tipo" => "tipoepi"
				)
			);

			return $searchData;
		}		

		public function getInfo( $publicMode = false, $comeFrom = null, Iusuario $usuario= null, $parent = false, $force = false){

			if ($publicMode && $comeFrom === elemento::PUBLIFIELDS_MODE_TABLEDATA) {
				$nombre = $this->getUserVisibleName();
				return array( $this->getUID() => array("nombre" => array(
					"class" => "box-it", 	
					"href" => "ficha.php?m=tipo_epi&oid=". $this->uid,
					"title" => $nombre,
					"innerHTML" => $nombre,
					"draggable-data" => "mover.php?poid={$this->uid}"
				)));
			}

			$forceCacheUpdate = false;
			if ($comeFrom === elemento::PUBLIFIELDS_MODE_TRASH || $force) {
				$forceCacheUpdate = true;
			}

			$info = parent::getInfo($publicMode, $comeFrom, $usuario, null, $forceCacheUpdate);

			if ($publicMode) {
				$tpl = Plantilla::singleton();
				$data =& $info[$this->getUID()];

				$types = self::obtenerEquipacion();
				$data['tipo_equipacion'] = $types[$data['tipo_equipacion']];
				$data['serial_number_required'] = $data['serial_number_required'] ? $tpl('si') : $tpl('no');
				$data['individual'] = $data['individual'] ? $tpl('si') : $tpl('no');
			}

			return $info;
		}
		
		public function getLineClass($parent, $usuario){
			return "color black";
		}

		
		
		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fieldList = new FieldList();

			$fieldList["descripcion"] = 		new FormField( array("tag" => "input", "type" => "text", "blank" => false));
			$fieldList["fabricante"] = 			new FormField( array("tag" => "input", "type" => "text"));
			$fieldList["modelo"] = 				new FormField( array("tag" => "input", "type" => "text"));


			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_TABLEDATA:
					
				break;
				default: case elemento::PUBLIFIELDS_MODE_NEW:
					$fieldList["serial_number_required"] =	new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "value" => true));
					$fieldList["duracion"] = 			new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["revision"] = 			new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["individual"] = 			new FormField( array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "value" => true));
					$fieldList["tipo_equipacion"] = 	new FormField( array("tag" => "select", "type" => "text", "default" => "Seleccionar", "data" => self::obtenerEquipacion()));
					$fieldList["normativa"] = 			new FormField( array("tag" => "input", "type" => "text"));
					$fieldList["categoria"] = 				new FormField( array("tag" => "slider", "type" => "text", "match" => "^([1-3])$", "count" => "3", "min" => 1));
					$fieldList["dias_previos_revision"] =	new FormField( array("tag" => "slider", "type" => "text", "match" => "^([0-365])$", "count" => "365", "min" => 0));
					$fieldList["dias_previos_caducidad"] =	new FormField( array("tag" => "slider", "type" => "text", "match" => "^([0-365])$", "count" => "365", "min" => 0));
				break;
			}

			return $fieldList;
		}

	}
?>
