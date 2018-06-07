<?php
	/** EVENTOS DE LA APLICACION */
	class trigger {
		protected $updateValues = null;
		protected $usuario = false;
		protected $table;

		/** CONSTRUCTOR DE LA CLASE, DEFINE LA TABLA SOBRE LA QUE SE TRABAJARA **/
		public function __construct($table, Iusuario $usuario = null){
			$this->table = $table;
			$this->usuario = $usuario;
		}

		/** ANTES DE CREAR EL ELEMENTO **/
		public function beforeCreate($data){
			$modulo = false;
			if (isset($data['uid_modulo']) && is_numeric($data['uid_modulo'])) {
				$modulo = util::nombreModulo($data['uid_modulo']);
			} else {
				return $data;
			}

			$fn = array($modulo, "triggerBeforeCreate");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario, $data);
			}
		}

		/** DESPUES DE CREAR EL ELEMENTO **/
		public function afterCreate($item){
			$fn = array($item, "triggerAfterCreate");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario, $item);
			}
		}

		public function beforeUpdate($item, $values){
			$this->updateValues = $values;

			$fn = array($item, "triggerBeforeUpdate");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario, $values);
			}
		}

		public function afterUpdate($item, $newValues, $fieldsMode){
			$fn = array($item, "triggerAfterUpdate");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario, $this->updateValues, $newValues, $fieldsMode);
			}
		}


		public function beforeDelete($item){
			$fn = array($item, "triggerBeforeDelete");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario);
			}
		}


		public function afterDelete($item){
			$fn = array($item, "triggerAfterDelete");
			if( is_callable($fn) ){
				return call_user_func($fn, $this->usuario);
			}
		}

	}
?>
