<?php
	class exportheader extends elemento implements Ielemento {

		public function __construct($param, $extra = false) {
			$this->tipo = __CLASS__;
			$this->tabla = TABLE_EXPORTHEADER;
			$this->uid_modulo = 98;
	
			$this->instance( $param, $extra );
		}

		public function getUserVisibleName () {
			$index = $this->getIndex();
			$export = $this->getDataExport();

			return "cabecera {$index} - {$export->getUserVisibleName()}";
		}

		public function getIndex () {
			$export = $this->getDataExport();

			$SQL = "
				SELECT position FROM (
					SELECT uid_exportheader, @rownum := @rownum + 1 AS position
					FROM {$this->tabla}
					JOIN (SELECT @rownum := 0) n
					WHERE uid_dataexport = {$export->getUID()}
					ORDER BY uid_exportheader ASC
				) l WHERE uid_exportheader = {$this->getUID()}
			";

			return $this->db->query($SQL, 0, 0);
		}

		public function getDataExport () {
			if ($uid = $this->obtenerDato('uid_dataexport')) {
				return new dataexport($uid);
			} else {
				throw new Exception('no dataexport for exportheader '. $this->getUID());
			}
		}

		public function obtenerHeaderColumns ($limit = NULL){
			$sql = "SELECT uid_headercolumn FROM ". TABLE_HEADERCOLUMN ." WHERE uid_exportheader = {$this->getUID()}";
			if (is_numeric($limit)) $sql .= " LIMIT 0, $limit";

			$array = $this->db->query($sql, "*", 0, "headercolumn");
			return new ArrayObjectList($array);
		}

		public function getArrayCopy () {
			$header = array();

			if ($columns = $this->obtenerHeaderColumns()) foreach ($columns as $column) {
				$header[] = $column->getArrayCopy();
			}

			return $header;
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();
			$data["nombre"] = $this->getUserVisibleName();

			return array($this->getUID() => $data);
		}

		public static function defaultData($data, Iusuario $usuario = null){
			if (isset($data['poid'])) $data['uid_dataexport'] = $data['poid'];

			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
			$fields = new FieldList;

			//$fields['label'] = new FormField(array('tag'=>'input', 'default' => 'cabecera'));

			switch ($modo) {
				case self::PUBLIFIELDS_MODE_NEW:
					$fields['uid_dataexport'] = new FormField;
					break;
				
				default:
					# code...
					break;
			}
			

			return $fields;
		}
	}