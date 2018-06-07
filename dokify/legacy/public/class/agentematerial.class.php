<?php
class agentematerial extends concodigo {
	const NOMBRE_TABLA = 'accidente_agente_material';
	const NOMBRE_TABLA_COMPLETO = TABLE_AGENTE_MATERIAL;
	public function __construct($param, $extra = false) {
		parent::instance($param,$extra);
	}
}
