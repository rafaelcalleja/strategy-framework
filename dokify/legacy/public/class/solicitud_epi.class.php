<?php
class solicitud_epi extends elemento implements Ielemento {
	private $tipoepi;
	private $epi;
	private $fake;

	public function __construct($param, $extra = false){
		if ($param instanceof tipo_epi) {
			$this->tipoepi = $param;
		}
		$this->epi = false;
		$this->tipo = 'solicitud_epi';
	}

	public function asignarEpi($epi) {
		if ($epi instanceof epi && $epi->obtenerTipoepi()->getUID() == $this->obtenerTipoepi()->getUID()) {
			$this->epi = $epi;
			return !!$this->epi;
		} else {
			$this->epi = false;
			return false;
		}
		return false;
	}

	public function quitarEpi() {
		$this->asignarEpi(false);
	}

	public function obtenerTipoepi() {
		return $this->tipoepi;
	}

	public function obtenerEpi() {
		return $this->epi;
	}

	public function isOK() {
		return !!$this->obtenerEpi();
	}

	public function isFake($set = null) {
		if ($set === true) {
			$this->fake = $set;
		}
		return $this->fake;
	}

	public function getUserVisibleName(){
		if ($epi = $this->obtenerEpi()) {
			return $epi->getUserVisibleName();
		} else {
			return $this->obtenerTipoepi()->getUserVisibleName();
		}
	}

	public function getInlineArray(){
		$inline = array();
		if ($epi = $this->obtenerEpi()) {
			if (!$this->isFake()) {
				$inline[] = array(
					"img"	=> RESOURCES_DOMAIN . "/img/famfam/bullet_go.png",
					array( "nombre" => 'Solicitud cumplimentada' )
				);
				return array_merge($inline,$epi->getInlineArray());
			}
			return $epi->getInlineArray();
		} else {
			$inline[] = array(
				"img"	=> RESOURCES_DOMAIN . "/img/famfam/bullet_go.png",
				array( "nombre" => 'Solicitud sin cumplimentar...' )
			);
			return $inline;
		}
	}


	public function getLineClass($parent, $usuario) {
		if ($epi = $this->obtenerEpi()) {
			return $epi->getLineClass();
		}
		return "color black";

		$estados = $this->obtenerEstado(true);
		if( in_array(epi::ESTADO_PROXIMO_REVISION, $estados) || in_array(epi::ESTADO_FUERA_REVISION, $estados) ){
			$class = "orange";
		}
		if( in_array(epi::ESTADO_REVISION, $estados) || in_array(epi::ESTADO_NO_UTIL_FECHA, $estados) || in_array(epi::ESTADO_NO_UTIL_MANUAL, $estados) ){
			$class = "red";
		}
		return "color {$class}";

	}

	public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $parent = false, $force = false){
		if ($epi = $this->obtenerEpi()) {
			return $epi->getInfo($publicMode, $comeFrom, $usuario, $parent, $force);
		} else {
			return $this->obtenerTipoepi()->getInfo($publicMode, $comeFrom, $usuario, $parent, $force);
		}
	}

	/*
	public static function cumplimentarSolicitudes($solicitudes, $epis) {
		$episAsignados = new ArrayObjectList();

		foreach ($solicitudes as $solicitud) {
			foreach($epis as $epi){
				if( $epi->obtenerTipoepi()->getUID() == $solicitud->obtenerTipoepi()->getUID() && !$solicitud->isOK() ){
					if ($solicitud->asignarEpi($epi)) {
						$episAsignados[] = $epi;
					}
				}
			}
		}

		$episRestantes = $epis->discriminar($episAsignados);
		foreach ($episRestantes as $epi) {
			$solicitudFalsa = new solicitud_epi($epi->obtenerTipoepi());
			if ($solicitudFalsa->asignarEpi($epi)) {
				$solicitudFalsa->isFake(true);
				$solicitudes[] = $solicitudFalsa;
			}
		}
		return $solicitudes;
	}*/

	public function __toString(){
		return __CLASS__ . '-' . $this->tipoepi;
	}


	public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
		return new FieldList;
	}
}

