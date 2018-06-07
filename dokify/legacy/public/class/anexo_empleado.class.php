<?php
class anexo_empleado extends anexo {
	public function __construct($uid, $item = false){
		return parent::__construct($uid,'empleado');
	}

	public function getTableFields(){
		return array(
			array("Field" => "uid_anexo_empleado",		"Type" => "int(10)", 		"Null" => "NO",		"Key" => "PRI",		"Default" => "",					"Extra" => "auto_increment"),
			array("Field" => "uid_documento_atributo",	"Type" => "int(10)",		"Null" => "NO",		"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "archivo",					"Type" => "varchar(512)",	"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "estado",					"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",					"Extra" => ""),
			array("Field" => "uid_empleado",			"Type" => "int(10)",		"Null" => "NO",		"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "uid_agrupador",			"Type" => "int(11)",		"Null" => "NO",		"Key" => "", 		"Default" => "0",					"Extra" => ""),
			array("Field" => "uid_empresa_referencia",	"Type" => "varchar(255)",	"Null" => "NO",		"Key" => "", 		"Default" => "0",					"Extra" => ""),
			array("Field" => "hash",					"Type" => "varchar(100)",	"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "nombre_original",			"Type" => "varchar(512)",	"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "fecha_actualizacion",		"Type" => "timestamp",		"Null" => "NO",		"Key" => "", 		"Default" => "CURRENT_TIMESTAMP",	"Extra" => ""),
			array("Field" => "fecha_anexion",			"Type" => "int(16)",		"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "fecha_emision",			"Type" => "int(16)",		"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "fecha_expiracion",		"Type" => "int(16)",		"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "fecha_emision_real",		"Type" => "int(16)",		"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "descargas",				"Type" => "int(4)",			"Null" => "NO",		"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "fileId",					"Type" => "varchar(100)",	"Null" => "YES",	"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "language",				"Type" => "varchar(2)",		"Null" => "NO",		"Key" => "", 		"Default" => "2",					"Extra" => ""),
			array("Field" => "is_urgent",				"Type" => "int(1)",			"Null" => "YES",	"Key" => "", 		"Default" => "0",					"Extra" => ""),
			array("Field" => "uid_usuario",				"Type" => "int(10)",		"Null" => "YES",	"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "uid_empresa_anexo",		"Type" => "int(10)",		"Null" => "YES",	"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "uid_empresa_payment",		"Type" => "int(10)",		"Null" => "YES",	"Key" => "MUL", 	"Default" => "",					"Extra" => ""),
			array("Field" => "uid_validation",			"Type" => "int(10)",		"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "time_to_validate",		"Type" => "int(10)",		"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "screen_uid_usuario",		"Type" => "int(10)",		"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "screen_time_seen",		"Type" => "timestamp",		"Null" => "NO",		"Key" => "", 		"Default" => "0000-00-00 00:00:00",	"Extra" => ""),
			array("Field" => "uid_anexo_renovation",	"Type" => "int(10)",		"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "validation_errors",		"Type" => "int(2)",			"Null" => "YES",	"Key" => "", 		"Default" => "0",					"Extra" => ""),
			array("Field" => "duration",				"Type" => "varchar(20)",	"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "validation_argument",		"Type" => "int(2)",			"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "reverse_status",			"Type" => "int(2)",			"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "reverse_date",			"Type" => "int(16)",		"Null" => "YES",	"Key" => "", 		"Default" => "",					"Extra" => ""),
			array("Field" => "full_valid",				"Type" => "int(1)",			"Null" => "NO",		"Key" => "", 		"Default" => "0",					"Extra" => "")
		);
	}
}