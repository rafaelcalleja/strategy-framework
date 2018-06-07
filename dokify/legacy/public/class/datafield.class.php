<?php
	class datafield extends elemento implements Ielemento {

		const COMPARATOR_EQUAL = '=';
		const COMPARATOR_NOT_EQUAL = '!=';
		const COMPARATOR_LIKE = 'LIKE%%';
		const COMPARATOR_MORE = '>=';
		const COMPARATOR_LESS = '<=';

		public function __construct($param, $extra = false) {
			$this->tipo = "datafield";
			$this->tabla = TABLE_DATAFIELD;
			$this->uid_modulo = 74;
	
			$this->instance( $param, $extra );
		}

		// así vemos si estamos metiendo campos de solo lectura mientras creamos el modelo, que es bastante farragoso si no...
		public function getInlineArray($usuarioActivo=false, $mode, $data ){
			
			$tpl = Plantilla::singleton();
			
			if ($this->obtenerDato('readonly') == 1) {
				$inline[] = array(
					'img' => RESOURCES_DOMAIN.'/img/famfam/eye.png',
					array('nombre' => $tpl->getString('solo_lectura')));
			}
			if ($this->obtenerDato('clave') == 1) {
				$inline[] = array(
					'img' => RESOURCES_DOMAIN.'/img/famfam/key.png',
					array('nombre' => $tpl->getString('clave')));
			}

			if (isset($inline)) return $inline;
			return false;
		}

		public function getColumn(){
			return $this->obtenerDato("column");
		}

		public function obtenerModuloDatos(){
			return util::getModuleName($this->obtenerDato("uid_modulo"));
		}

		/** LA SQL A EJECUTAR PARA EXTRAER LOS DATOS **/
		public function getSQL($param=false){
			switch( $name = $this->obtenerDato("name") ){
				// Los campos modelfield::$specials los ponemos en código
				// Debemos refactorizar esto con algo mas de tiempo
				case "estado_contratacion":
					$sql = "( -- empresas validas
							n1 IN (<%empresasvalidas%>)
						AND if(n2 IS NULL OR !n2, 1, n2 IN (<%empresasvalidas%>) )
						AND if(n3 IS NULL OR !n3, 1, n3 IN (<%empresasvalidas%>) )
						AND if(n4 IS NULL OR !n4, 1, n4 IN (<%empresasvalidas%>) )
					)";

					return $sqlFinal = "(SELECT if(($sql), 'Valido', 'No Valido'))";
				break;
				case "cadena_contratacion_cumplimentada":
					$sql = "( -- empresas cumplimentadas
							n1 IN (<%empresacumplimentadas%>)
						AND if(n2 IS NULL OR !n2, 1, n2 IN (<%empresacumplimentadas%>) )
						AND if(n3 IS NULL OR !n3, 1, n3 IN (<%empresacumplimentadas%>) )
						AND if(n4 IS NULL OR !n4, 1, n4 IN (<%empresacumplimentadas%>) )
					)";

					return $sqlFinal = "(SELECT if(($sql), 'Si', 'No'))";
				break;
				case 'asignado_en_conjunto_de_agrupadores': case 'valido_conjunto_agrupadores': 
				// case 'valido_conjunto_agrupadores_asignados':
				case 'valido_conjunto_agrupadores_seleccionados':
				case 'valido_conjunto_agrupadores_solo_asignados':
					if( $param instanceof ArrayObjectList && $sql = trim($this->obtenerDato("sql")) ){
						$subsql = array();
						foreach ($param as $item) {
							$subsql[] = "((". str_replace('%s', $item->getUID(), $sql) .") = 1)";
						}
						return $sql = '( IF( '.implode(' AND ',$subsql) .',\'Si\',\'No\') )';
					}

				break;
				case 'valido_algun_agrupador': 
					if( $param instanceof ArrayObjectList && $sql = trim($this->obtenerDato("sql")) ){
						foreach($param as $item) {
							$subsql[] = "((". str_replace('%s', $item->getUID(), $sql) .") = 1)";
						}
						return $sql = '( IF( '.implode(' OR ',$subsql) .',\'Si\',\'No\') )';
					} 
				break;
				case 'mostrar_agrupador_asignado': case 'trabajos': case 'codigo_agrupador_valido':
					if( $param instanceof ArrayObjectList && $sql = trim($this->obtenerDato("sql")) ){
						return str_replace('%s', $param->toComaList() ,$sql);
					}
				break;
				default:
					if( $sql = trim($this->obtenerDato("sql")) ){
						if( $param instanceof Ielemento ) $param = $param->getUID();
						if( $param ) $sql = str_replace("%s", $param, $sql);
						return $sql;
					}
				break;
			}

			return false;
		}/**/

		public function getUserVisibleName(){
			$tpl = Plantilla::singleton();
			return $tpl->getString($this->obtenerDato("name"));
		}


		public function isBool(){
			return (bool) $this->obtenerDato("is_bool");
		}


		public function getParam(){
			if( $param = trim($this->obtenerDato("param")) ){
				return $param;
			}
			return false;
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$info = parent::getInfo(true, $usuario);
			$data = array();

			$data["nombre"] =  array(
				"innerHTML" => $this->getUserVisibleName(),
				"href" => "../agd/ficha.php?m={$this->tipo}&poid=". $this->uid,
				"className" => "box-it link"
			);

			return array($this->getUID() => $data);
		}

		public function getValueField(){
			$tpl = Plantilla::singleton();

			$formfield = array("tag" => "input", "type" => "text" );
			switch($name = $this->obtenerDato("name")){
				case "estado_agrupador": case "estado_agrupamiento": case "estado_agrupamiento_vacio_es_valido":
					$formfield = array("tag" => "select", "data" => array("1" => $tpl->getString("valido"), "0" => $tpl->getString("no_valido")), "blank" => false);
				break;
				case 'fecha_anexo_atributo':
				case 'fecha_anexo_documento':
				case 'fecha_nacimiento':
				case 'fecha_alta_empresa':
				case 'fecha_baja_empresa':
				// case 'caducidad':
				case 'fecha_caducidad':
				case 'fecha_alta_teletrabajo':
					$formfield = array("tag" => "input", "type" => "text", "blank" => false, "className" => "datepicker", "date_format" => "%d/%m/%Y");
				break;
				default:
					if( strpos($name, "bool") !== false){
						$formfield = array("tag" => "select", "data" => array("1" => $tpl->getString("si"), "0" => $tpl->getString("no")), "blank" => false);
					}
				break;
			}

			return $formfield;
		}

		public function getComparatorField(){
			$tpl = Plantilla::singleton();

			$list = array(
				self::COMPARATOR_EQUAL 		=> self::COMPARATOR_EQUAL,
				self::COMPARATOR_NOT_EQUAL 	=> self::COMPARATOR_NOT_EQUAL,
				self::COMPARATOR_LIKE 		=> self::COMPARATOR_LIKE,
			);
			$formfield = array("tag" => "select", "blank" => false);

			switch($this->obtenerDato("name")){
				case "estado_agrupador":

				break;
				case 'fecha_anexo_atributo':
				case 'fecha_anexo_documento':
				case 'fecha_nacimiento':
				case 'fecha_alta_empresa':
				case 'fecha_baja_empresa':
				case 'caducidad':
				case 'fecha_caducidad':
				case 'fecha_alta_teletrabajo':
					$formfield['data'] = array(
						self::COMPARATOR_EQUAL => self::COMPARATOR_EQUAL,
						self::COMPARATOR_MORE => self::COMPARATOR_MORE,
						self::COMPARATOR_LESS => self::COMPARATOR_LESS);
					return $formfield;
				break;
			}

			$formfield["data"] = $list;
			return $formfield;
		}


		public static function defaultData($data, Iusuario $usuario = null){
			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;

			switch( $modo ){
				case elemento::PUBLIFIELDS_MODE_INIT:
				case elemento::PUBLIFIELDS_MODE_NEW:
				case elemento::PUBLIFIELDS_MODE_EDIT:
				default:
					$fields["name"] 		= new FormField(array("tag" => "input", "type" => "text", "blank" => false ));
				break;
			}

			return $fields;
		}

		public function getTableFields(){
			return array(
				array("Field" => "uid_datafield",		"Type" => "int(11)", 		"Null" => "NO",		"Key" => "PRI",		"Default" => "",	"Extra" => "auto_increment"),
				array("Field" => "uid_modulo",			"Type" => "int(11)",		"Null" => "NO",		"Key" => "MUL", 	"Default" => "",	"Extra" => ""),
				array("Field" => "name",				"Type" => "varchar(255)",	"Null" => "NO",		"Key" => "", 		"Default" => "",	"Extra" => ""),
				array("Field" => "column",				"Type" => "varchar(255)",	"Null" => "NO",		"Key" => "", 		"Default" => "",	"Extra" => ""),
				array("Field" => "sql",				"Type" => "text",			"Null" => "NO",		"Key" => "", 		"Default" => "",	"Extra" => ""),
				array("Field" => "descripcion",		"Type" => "varchar(512)",	"Null" => "NO",		"Key" => "", 		"Default" => "",	"Extra" => ""),
				array("Field" => "clave",				"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",	"Extra" => ""),
				array("Field" => "param",				"Type" => "varchar(100)",	"Null" => "NO",		"Key" => "", 		"Default" => "0",	"Extra" => ""),
				array("Field" => "readonly",			"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",	"Extra" => ""),
				array("Field" => "is_bool",			"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",	"Extra" => ""),
				array("Field" => "multiple_criterion",	"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",	"Extra" => "")
			);
		}
		
	}
?>
