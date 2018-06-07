<?php
	require_once("../../api.php");
	session_write_close();

	// --- Empresa de la cual se quiere obtener informacion
	$empresa = new empresa( obtener_uid_seleccionado(), false );
	
	// --- Comprobamos el acceso a la empresa seleccionada...
	if( !$usuario->accesoElemento($empresa) ){ die("Denegado"); }

	// ---- Buscamos todos los empleados de esta empresa...
	$coleccionEmpleados = $empresa->obtenerEmpleados();

	// ---- Empezams desde 0
	$total = 0;

	// ---- Que estado queremos ver?
	if( !isset($_REQUEST["e"]) || !is_numeric($_REQUEST["e"]) ){ die("N/A") ; }
	$estado = $_REQUEST["e"];
	

	foreach($coleccionEmpleados as $empleado){
		// --- Buscamos todos los documentos caducados del elemento
		$info = $empleado->getNumberOfDocumentsByStatus($usuario, $estado );

		// --- Sumamos los documentos
		if( isset($info[$estado]) && is_numeric($info[$estado]) ){ 
			$total = $total + $info[$estado]; 	
		}
	}

	echo $total;
?>
