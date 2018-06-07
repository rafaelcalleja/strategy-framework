<?php
class anexo_historico_empleado extends anexo_historico {
	public function __construct($uid, $item = false){
		return parent::__construct($uid,'historico_empleado');
	}
}