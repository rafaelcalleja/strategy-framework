<?php
abstract class concodigo extends elemento implements Ielemento {
	
	const NOMBRE_TABLA = null;
	const NOMBRE_TABLA_COMPLETO = null;

	public function instance($param, $extra = false){
		$c = get_called_class();
		$this->tabla = $c::NOMBRE_TABLA_COMPLETO;
		$this->nombre_tabla = $c::NOMBRE_TABLA;
		parent::instance($param,$extra);
	}
	
	public function getUserVisibleName(){
		return $this->obtenerDato("nombre");
	}
	
	public static function obtenerTodos(){
		$db = db::singleton();
		$c = get_called_class();
		$sql = "SELECT uid_". $c::NOMBRE_TABLA ." FROM ". $c::NOMBRE_TABLA_COMPLETO ." WHERE 1 ORDER BY nombre";
		$elementos = $db->query($sql, "*", 0, $c);
		return new ArrayObjectList($elementos);
	}
	
	public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
		$fieldList = new FieldList;
		$fieldList["nombre"] = new FormField( array("tag" => "input", "type" => "text", "blank" => false ) );
		return $fieldList;
	}
}

 