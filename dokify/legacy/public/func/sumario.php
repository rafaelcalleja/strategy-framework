<?php
	/*	NOS DEVUELVE UN ARRAY FORMATEADO ESPECIFICAMENTE PARA 
		USARLO EN LA PLANTILLA DE SUMARIO
	*/
	function obtener_informacion_sumario($usuario){
		//array final
		$info = array();


		//recuperamos la empresa actual
		$empresa = $usuario->getCompany();


		//recuperamos el objeto db	
		$db = db::singleton();
		$tpl = Plantilla::singleton();
		
		


		// ------- informacion GENERAL	
		$langString = "informacion_general";
		$info[ $langString ] = array();

			//conteo de usuarios
			$info[ $langString ][ "desc_usuarios_cargados" ] = count( $empresa->obtenerIdUsuarios() );
			//conteo de empresas
			$info[ $langString ][ "desc_empresas_cargadas" ] = count( $empresa->obtenerIdEmpresasInferiores() );
			//conteo de empleados	
			$info[ $langString ][ "desc_empleados_cargados" ] = count( $empresa->obtenerIdEmpleados() );





		$empresasConflictivas = empresa::obtenerEmpresasConflictivas($usuario, 3);

			$langString = "empresas_sin_actividad";
			$info[ $langString ] = array();

			foreach($empresasConflictivas as $empresa){
				$date = $empresa->lastAccessDate();
				if( $date == "00-00-0000"){ // nunca ha accedido...
					$date = $tpl->getString("N/A");
				}

				$info[ $langString ][] = array(
					"innerHTML" => $empresa->getUserVisibleName() . " &nbsp; " . $date,
					"href" => $empresa->obtenerURLPreferida()
				);
			}

			
			
		/*
		// ----- informacion de ACCESO
		$langString = "informacion_acceso";
		$info[ $langString ] = array();
			
			//usuarios online
			$info[ $langString ]["desc_usuarios_online"]  = count( $empresa->obtenerUsuariosOnline() );
			//accesos hoy
			//$info[ $langString ]["desc_accesos_hoy"]  = "?";
			//accesos mes
			//$info[ $langString ]["desc_accesos_este_mes"]  = "?";
		*/
		/*
		// ----- informacion global de documentos....
		$langString = "informacion_documentos_empleados_empresa";
		$info[ $langString ] = array();
			// documentos de empresas...
			$href = "empleado/contardocs.php?poid=".$empresa->getUID();
		
			$info[ $langString ]["desc_documentos_pendientes_empleado"] = "<span class='async-info-load' href='$href&e=1'></span>";
			$info[ $langString ]["desc_documentos_caducados_empleado"] = "<span class='async-info-load' href='$href&e=3'></span>";
			$info[ $langString ]["desc_documentos_anulados_empleado"] = "<span class='async-info-load' href='$href&e=4'></span>";
			$info[ $langString ]["desc_documentos_sinanexar_empleado"] = "<span class='async-info-load' href='$href&e=-1'></span>";

		// ----- informacion global de documentos....
		$langString = "informacion_documentos_maquinas_empresa";
		$info[ $langString ] = array();
			// documentos de empresas...
			$href = "maquina/contardocs.php?poid=".$empresa->getUID();
		
			$info[ $langString ]["desc_documentos_pendientes_maquinas"] = "<span class='async-info-load' href='$href&e=1'></span>";
			$info[ $langString ]["desc_documentos_caducados_maquinas"] = "<span class='async-info-load' href='$href&e=3'></span>";
			$info[ $langString ]["desc_documentos_anulados_maquinas"] = "<span class='async-info-load' href='$href&e=4'></span>";
			$info[ $langString ]["desc_documentos_sinanexar_maquinas"] = "<span class='async-info-load' href='$href&e=-1'></span>";
		*/

		return $info;
	}
?>
