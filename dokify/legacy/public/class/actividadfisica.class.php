<?php
class actividadfisica extends concodigo {
	const NOMBRE_TABLA = 'accidente_actividad_fisica';
	const NOMBRE_TABLA_COMPLETO = TABLE_ACTIVIDAD_FISICA;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
