<?php
	require_once("../../api.php");

	$template = new Plantilla();

	// --- extraemos el modulo
	$modulo = obtener_modulo_seleccionado(); 
	$modulo = $modulo ? $modulo : "empresa"; // --- por defecto empresas

	// --- Buscamos el id de la empresa actual
	if ($idSeleccionado = obtener_uid_seleccionado()) {
		// --- INSTANCIAMOS LA EMPRESA QUE SE SOLICITA
		$elementoPadre = new $modulo($idSeleccionado);
	} else {
		$elementoPadre = $usuario->getCompany();
	}

	//------- comprobar el acceso a esta empresa
	if (!$usuario->accesoElemento($elementoPadre)) die("Inaccesible");


	// EDITAR EVENTOS
	if (isset($_GET["oid"])) {
		$evento = new eventdate($_GET["oid"]);

		if( isset($_GET["send"]) ){
			$data = $_REQUEST;

			if (isset($data['fecha'])) {
				// Parsear fecha correctamente
				$aux = explode("/", $data["fecha"]);
				if (count($aux) > 2) $data["fecha"] = "{$aux[2]}-{$aux[1]}-{$aux[0]}";
			}
			
			$update = $evento->updateWithRequest($data, false, $usuario);
			switch( $update ){
				case null:
					$template->assign ("error", "No se modifico nada");
				break;
				case false:
					$template->assign ("error", "Error al intentar modificar");
				break;
				default:
					$template->assign ("succes", "exito_texto");
				break;
			}
		}

		$btnAlarm = array(
			"innerHTML" => "alarma", 
			"img" => RESOURCES_DOMAIN . '/img/famfam/bell.png',
			"className" => "box-it", 
			"href" => "alarma/alarma.php?poid=".$evento->getUID()."&m=eventdate"
		);

		$btnDelete = array(
			"style" => "float:left",
			"innerHTML" => "eliminar", 
			"img" => RESOURCES_DOMAIN . '/img/famfam/delete.png',
			"className" => "box-it", 
			"href" => "eliminar.php?m=eventdate&poid=".$evento->getUID()
		);

		$btnSave = array(
			"innerHTML" => "guardar", 
			"img" => RESOURCES_DOMAIN . '/img/famfam/add.png',
			"type" => "submit"
		);

		$template->assign("botones", array($btnDelete, $btnAlarm, $btnSave));
		$template->assign("boton", false);
		$template->assign("elemento", $evento);
		$template->display("form.tpl");
		exit;
	}
	

	$date["day"] = $_REQUEST["day"];
	$date["month"] = $_REQUEST["month"];
	$date["year"] = $_REQUEST["year"];



	// Si se ha enviado el formulario de nuevo evento
	if (isset($_GET["send"])) {
		$data = $_REQUEST;
			$data["poid"] = $elementoPadre->getUID();
			$data["fecha"] = $date["year"]."/".$date["month"]."/".$date["day"];
			$data["uid_empresa"] = $elementoPadre->getUID();
			$data["uid_usuario"] = $usuario->getUID();

		// instanciamos y creamos el evento al mismo tiempo
		$evento = new eventdate($data, "empresa", $usuario);

		if( $evento->exists() ){
			$params = "poid=" . $elementoPadre->getUID() . "&" . http_build_query($date);

			$template->assign("botones", array(
				array( "innerHTML" => "insertar_otro", "className" => "box-it", "href" => "empresa/eventos.php?$params" ),
				array( "innerHTML" => "crear_alarma", "className" => "box-it", "href" => "alarma/nuevo.php?poid=".$evento->getUID()."&m=eventdate" )
			));
			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", "error_texto");
		}
	}




	$fields = eventdate::publicFields(NULL);
	$fields[$template("fecha")] = new FormField(array("tag" => "span", "value" => $date["day"]."-".$date["month"]."-".$date["year"]));


	$extradata = array("year" => $date["year"], "month" => $date["month"], "day" => $date["day"]);

	$template->assign ("titulo","titulo_nuevo_evento");
	//$template->assign ("boton","continuar");
	$template->assign ("data",  $extradata);
	$template->assign ("campos", $fields );
	$template->display("form.tpl");
