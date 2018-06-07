<?php
class anexo_historico extends anexo {
	
	public function getValidationStatus(){
		$info = $this->getInfo();
		$anexo = $info["uid_anexo"];
		$validationsStatus = db::get("SELECT uid_validation_status FROM " .TABLE_VALIDATION_STATUS. " WHERE uid_anexo = $anexo", "*", 0, "validationStatus");

		if ($validationsStatus) return new ArrayObjectList($validationsStatus);
		else return new ArrayObjectList();

	}

	public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
		
		$arrayCampos = new FieldList();
			$arrayCampos['estado'] = new FormField(array('tag' => 'select', 'data'=> documento::getAllStatus(), 'default'=> '1' ) /*documento::ESTADO_ANEXADO /*1*/);
			$arrayCampos['fecha_emision'] = new FormFIeld();
			$arrayCampos['fecha_emision_real'] = new FormFIeld();
			$arrayCampos['fecha_expiracion'] = new FormFIeld();
			$arrayCampos['uid_empresa_referencia'] = new FormFIeld();
			$arrayCampos['language'] = new FormFIeld();
			$arrayCampos['is_urgent'] = new FormFIeld();
			$arrayCampos['uid_usuario'] = new FormFIeld();
			$arrayCampos['fileId'] = new FormFIeld();
			$arrayCampos['uid_empresa_anexo'] = new FormFIeld();
			$arrayCampos['uid_empresa_payment'] = new FormFIeld();
			$arrayCampos['uid_validation'] = new FormFIeld();
			$arrayCampos['time_to_validate'] = new FormFIeld();
			$arrayCampos['uid_anexo'] = new FormField();

			return $arrayCampos;


	}
}