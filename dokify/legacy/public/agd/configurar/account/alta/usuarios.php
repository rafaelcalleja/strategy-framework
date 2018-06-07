<?php
	/*
	 *	Creacion masiva de usuarios en AGD
	 *
	 */

	$debug = FALSE;
	set_time_limit(0);
	define("NO_CACHE_OBJECTS", TRUE);
	require_once("../../../../api.php");
	if( !$usuario->esStaff() ){ header('HTTP/1.0 404 Not Found'); exit; }

	
	$template = Plantilla::singleton();
	$empresa = new empresa( obtener_uid_seleccionado() );

	// Buscamos todas las subcontratas
	$contratas = $empresa->obtenerEmpresasInferiores(false, false, $usuario, empresa::DEFAULT_DISTANCIA );
	$parts = array();

	if( !isset($_REQUEST["rol"]) ){
		$parts[] = "Selecciona un rol";
		$contratas = array();
	} else {
		$rol = new rol($_REQUEST["rol"]);
	}

	if( !$rol instanceof rol || !$rol->exists() ){
		$parts[] = "Error al seleccionar el rol";
		$contratas = array();
	}

	// Recorremos el listado
	foreach( $contratas as $contrata ){
		// Vamos a ver si tiene o no usuarios...
		$usuarios = $contrata->obtenerUsuarios();
		if( !count($usuarios) ){
			$contacto = $contrata->obtenerContactoPrincipal();

			$email = $contrata->obtenerDato("email");

			if( !$contacto instanceof contactoempresa  ){
				$parts[] = "La empresa <a class='box-it' href='". $contrata->obtenerUrlFicha() ."'>". $contrata->getUserVisibleName() . "</a> no tiene un contacto principal. La saltamos <br />";
				continue;
			}

			$data = $contacto->getInfo();
			if( !trim($data["email"]) ){
				$parts[] = "La empresa <a class='box-it' href='". $contrata->obtenerUrlFicha() ."'>". $contrata->getUserVisibleName() . "</a> no tiene los datos necesario para crear el usuario. La saltamos <br />";
				continue;
			}

	
			// Vamos a generar unos datos para los usuarios
			$password = usuario::randomPassword();
			$username = usuario::getValidUserName($contrata->getUserVisibleName());

			if( !trim($data["nombre"]) ){
				$data["nombre"] = $username;
			}

			if( $debug === TRUE ) $data["email"] = "jandres@dokify.net";

			$datos = array(
				"usuario" => $username, 
				"nombre" => $data["nombre"],
				"apellidos" => $data["apellidos"],
				"email" => $data["email"],
				"telefono" => @$data["telefono"],
				"pass" => $password, "pass2" => $password,
				"locale" => $empresa->getCountry()->getLanguage(),
			);

			$newuser = usuario::crearNuevo( $datos );
			if( $newuser instanceof usuario ){
				$uidPerfil = $newuser->crearPerfil( $usuario, $contrata, true);

				if( is_numeric($uidPerfil) ){
					/*
					if( !$newuser->createContact($usuario) ){
						$parts[] = "&nbsp;¡¡¡ Parece que no se ha podido crear el contacto !!!";
					}*/

					// Enviar el email
					if( $newuser->sendWelcomeEmail($contrata) !== true ){  //quitamos cliente
						$parts[] = "Error al enviar el email a ". $username ." <br />";
						break;
					} else {
						$parts[] = "Se ha creado el usuario <a class='box-it' href='".$newuser->obtenerUrlFicha()."'>". $username ."</a> para la empresa ". $contrata->getUserVisibleName() . " <br />";
						//dump($parts);exit;
					}

					// Aplicamos el rol
					if( ($estado = $rol->actualizarPerfil($uidPerfil, true) !== true ) ){
						$parts[] = "&nbsp;¡¡¡ Parece que no se ha podido vincular el usuario con el rol ($estado) !!!";
					}
				} else {
					$parts[] = "&nbsp;¡¡¡ Error al crear el perfil ($uidPerfil) !!!";
				}

			} else {
				$parts[] = "Ocurrió un error ($newuser) al crear el usuario ". $username ." en ". $contrata->getUserVisibleName() . " tendrás que intentarlo manualmente<br />";
			}

			if( $debug === TRUE ){ /*dump($parts); exit;*/ }
		}
	}

	// --- 
	$HTML = ( count($parts) ) ? implode("",$parts) : "No hay empresas sin usuarios" ;

	// Mostrar html
	$template->assign("title", "Alta de usuarios");
	$template->assign("html", $HTML );
	$template->display("simplebox.tpl");
?>
