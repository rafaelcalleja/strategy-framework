<?php
// if(!defined('NO_CACHE_OBJECTS')) define('NO_CACHE_OBJECTS',1);
include("../api.php");
session_write_close();
// m=empleado&field=uid_municipio&poid=65192
if( ($modulo= obtener_modulo_seleccionado()) && $field = @$_REQUEST["field"] ){
	if( $uid = obtener_uid_seleccionado() ){
		$item = new $modulo($uid);
		if( $usuario->accesoElemento($item) ){
			$fields = $item->getPublicFields(true, elemento::PUBLIFIELDS_MODE_EDIT, $usuario);
		}
	} else {
		$fields = $modulo::publicFields(elemento::PUBLIFIELDS_MODE_EDIT, null, $usuario);
	}

	if( isset($fields) && isset($fields[$field]) && $data = $fields[$field] ){
		if( is_string($data['data']) && is_callable($data['data'])) {
			$param = ( isset($item) && $item instanceof elemento ) ? $item->obtenerDato($data['depends']) : NULL;
			$data['data'] = call_user_func($data['data'], $param);
		} 
		$template = new Plantilla();
		$template->assign("campo", $data);
		$template->display("form/form_parts_live.inc.tpl");
	}
}

