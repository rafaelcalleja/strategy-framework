<?php
	require_once("../../api.php");

	if( !$usuario->esStaff() ){ die("Inaccesible"); }

	$template = new Plantilla();

	
	//array de datos
	$datosPlantillas = array();

	//array con todas las etiquetas
	$plantillas = plantillaemail::getVisibleEmailTemplates();

	foreach( $plantillas as $plantillaemail ){

		$datosPlantilla = array();
				
		//datos de la plantilla
		$datosPlantillaemail = $plantillaemail->getInfo();
				
		$datosPlantilla["lineas"] = array( array( $datosPlantillaemail["descripcion"] ) );

		$datosPlantilla["options"] = config::obtenerOpciones( $plantillaemail->getUID(), "plantilla" /* MODULO */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );
		if( !$plantillaemail->hasAttributes() ){
			foreach( $datosPlantilla["options"] as $i => $option ){
				if( $option["uid_accion"] == 13 ){
					unset( $datosPlantilla["options"][$i] );
					sort( $datosPlantilla["options"] );
						break;
				}
			}
		}

		//guardamos el objeto actual al global
		$datosPlantillas[] = $datosPlantilla;
	}


	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$json->establecerTipo("data");
	$json->nombreTabla("plantilla");
	$json->informacionNavegacion($template->getString("inicio"), $template->getString("configurar"), $template->getString("plantillas_email")  );
	$json->datos( $datosPlantillas );
	$json->display();
?>
