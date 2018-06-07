<?php
class empresaitem extends elemento{

	public function __construct($param, $extra = false ){
		$this->tipo = __CLASS__;
		$this->tabla = TABLE_EMPRESA . "_item";
		$this->instance($param, $extra);
	}

	public static function getByCompanyItem(empresa $empresa, solicitable $item) {
		if (!isset($empresa) || !isset($item)) return false;
		$db = db::singleton();
		$sql = "SELECT uid_empresa_item FROM ".TABLE_EMPRESA . "_item WHERE uid_empresa = ".$empresa->getUID()." 
				AND uid_item = ".$item->getUID()." AND uid_modulo = ".$item->getModuleId();
		$uid = $db->query($sql, 0, 0);
		if (true === is_numeric($uid)) {
			return new empresaitem($uid);
		}
		return false;
	}

	public function setSuitable($suitable = 1, $usuario = null){
		return $this->update(array("suitable" => $suitable), elemento::PUBLIFIELDS_MODE_EDIT, $usuario);
	}

	public function isSuitable(){
		return (bool)$this->obtenerDato("suitable");
	}

	public static function publicFields($modo, elemento $objeto = null, usuario $usuario = null, $tab = false){
		$arrayCampos = new FieldList();
		$arrayCampos["uid_empresa"] = new FormField();
		$arrayCampos['uid_item'] = new FormField();
		$arrayCampos["uid_modulo"] = new FormField();
		$arrayCampos["suitable"] = new FormField();

		return $arrayCampos;
	}

}