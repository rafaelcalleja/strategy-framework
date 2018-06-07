<?php

	if( !isset($_REQUEST["oid"]) && isset($_REQUEST["oid"]) ){ exit(); }
	include_once( "../../api.php");

	if( !$usuario->accesoModulo("contactoempresa") ){
		die("Inaccesible");
	}

	$uid = obtener_uid_seleccionado();
	$empresaContactos = new empresa($uid);
	$accessContact = false;
	if ( ($empresaContactos->compareTo($usuario->getCompany())) || ( ($corp = $empresaContactos->perteneceCorporacion()) && $corp->compareTo($usuario->getCompany()) ) ){
		$accessContact = true;
	}

	$tpl = Plantilla::singleton();

	if( isset($_GET["action"]) ){
		$contacto = new contactoempresa($uid);
		$empresa = $contacto->getCompany();
		switch( $_GET["action"] ){
			case "plantilla":
				$plantilla = new plantillaemail($_GET["ref"]);
				$method = ( $_GET["checked"] ) ? "activarRecibirEmail" : "desactivarRecibirEmail";
				$result = call_user_func( array($contacto, $method), $plantilla);

				if( $result === true ){
					die("1");
				} else {
					die($result);
				}
			break;
			case "principal":
				if( !$usuario->accesoElemento($empresa) ){ die("Inaccesible"); }
				if( $contacto->hacerPrincipal() !== true ){
					$tpl->assign("error", $actualizacion );
				}
			break;
		}
	} else {
		if ($uid = obtener_uid_seleccionado()) {
			$empresa = new empresa($uid);
			if( !$usuario->accesoElemento($empresa) ){ die("Inaccesible"); }
		} else {
			die("Inaccesible");
		}
	}

	$plantillas = plantillaemail::obtenerTodas(array("contacto" => 1, "company" => $usuario->getCompany()));

	$tpl->assign("plantillas", $plantillas);
	$tpl->assign("empresa", $empresa);
	$tpl->assign("templatesToAvoid", plantillaemail::$templatesToAvoid);
	$tpl->assign("accessContact", $accessContact);
	$tpl->display("contacto_empresa.tpl");
