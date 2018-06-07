<?php
	class watchComment extends solicitable implements Ielemento {

		const MANUALLY = 0; 
		const AUTOMATICALLY_VALIDATION = 1;
		const AUTOMATICALLY_ATTACHMENT = 2;
		const AUTOMATICALLY_COMMENT = 3;
		const AUTOMATICALLY_CHANGE_DATE = 4;

		public function __construct($param, $extra = false){
			$this->tipo = "watchComment";
			$this->tabla = TABLE_WATCH_COMMENT;
			if (is_string($extra)) $this->tabla .= "_{$extra}";
			$this->instance( $param, $extra );
		}

		public function getUserVisibleName() {
			//do not need it, just to implement Ielemento
			return $this->getUID();
		}

		public function getAssigned() {
			$info = $this->getInfo();
			return $info["assigned"];
		}


		public function getModule() {
			$info = $this->getInfo();
			return $info["uid_modulo"];
		}

		public function getElement() {
			$module = $this->getModule();
			$moduleName = util::getModuleName($module);
			$info = $this->getInfo();			
			return new $moduleName($info["uid_elemento"]);
		}

		public function getDocument() {
			$info = $this->getInfo();
			$element = $this->getElement(); 
			return new documento($info["uid_documento"], $element);
		}

		public function getModuleWatcher() {
			$info = $this->getInfo();
			return $info["uid_module_watcher"];
		}

		public function getWatcher() {
			$info = $this->getInfo();
			if ($uid = $info["uid_watcher"]) {
				$moduleName = util::getModuleName($this->getModuleWatcher());
				return new $moduleName($uid);
			}
			
			throw new Exception("No se ha encontrado el watcher");
			
		}

		public static function defaultData($data, Iusuario $usuario = null) {
			
			$data["date"] = date("Y-m-d H:i:s");
			return $data;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
		
			$fieldList = new FieldList();
			$fieldList["uid_elemento"]		= new FormField(array());
			$fieldList["uid_modulo"] = new FormField(array());
			$fieldList["uid_documento"]	= new FormField(array());
			$fieldList['date']	= new FormField(array());
			$fieldList['uid_watcher']	= new FormField(array());
			$fieldList['uid_module_watcher']	= new FormField(array());
			$fieldList['assigned']	= new FormField(array());

			return $fieldList;

		}	
	}