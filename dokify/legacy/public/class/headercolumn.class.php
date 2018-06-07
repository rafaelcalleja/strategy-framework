<?php
	class headercolumn extends elemento implements Ielemento {

		public function __construct($param, $extra = false) {
			$this->tipo = __CLASS__;
			$this->tabla = TABLE_HEADERCOLUMN;
			$this->uid_modulo = 99;
	
			$this->instance($param, $extra);
		}

		public function getUserVisibleName () {

			$title = trim($this->obtenerDato('title'));

			if (!$title) {
				$lang = Plantilla::singleton();
				$title = $lang('vacio');
			}

			$rowspan = $this->getRowspan();
			$colspan = $this->getColspan();

			if ($rowspan > 1) $title .= " - {$rowspan} rows";
			if ($colspan > 1) $title .= " - {$colspan} cols";

			return $title;
		}

		public function getArrayCopy () {
			$info = $this->getInfo();

			return array(
				'title' => $info['title'],
				'colspan' => $info['colspan'],
				'rowspan' => $info['rowspan'],
				'color' => $info['color']
			);
		}

		public function getColspan () {
			return $this->obtenerDato('colspan');
		}

		public function getRowspan () {
			return $this->obtenerDato('rowspan');
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()){
			$data = array();
			$data["nombre"] = $this->getUserVisibleName();

			return array($this->getUID() => $data);
		}

		public static function getColors () {
			$colors = array(
				'white',
				'aqua',
				'cyan',
				'black',
				'blue',
				'brown',
				'magenta',
				'fuchsia',
				'gray',
				'grey',
				'green',
				'lime',
				'navy',
				'orange',
				'purple',
				'red',
				'silver',
				'yellow'
			);

			return array_combine($colors, $colors);
		}


		public static function defaultData($data, Iusuario $usuario = null){
			if (isset($data['poid'])) $data['uid_exportheader'] = $data['poid'];

			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$fields = new FieldList;

			$fields['title'] = new FormField(array('tag'=>'input', 'innerHTML' => 'titulo', 'type' => 'text'));
			$fields['colspan'] = new FormField(array('tag'=>'slider', 'default' => '1', 'count' => 50, 'min' => 1));
			$fields['rowspan'] = new FormField(array('tag'=>'slider', 'default' => '1', 'count' => 10, 'min' => 1));
			$fields['color'] = new FormField(array('tag'=>'input', 'id' => 'headercolumncolor', 'innerHTML' => 'css_background-color', 'type' => 'text', 'className' => 'selectorcolor', 'size' => 10, 'target' => '#headercolumncolor', 'rel' => 'background-color'));

			switch ($modo) {
				case self::PUBLIFIELDS_MODE_NEW:
					$fields['uid_exportheader'] = new FormField;
					break;
			}
			

			return $fields;
		}
	}