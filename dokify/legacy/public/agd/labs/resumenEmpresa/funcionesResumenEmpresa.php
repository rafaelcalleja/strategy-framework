<?php

	require_once("../../../config.php");

	//funciones
	function getEmpresas($IdEmp, $nvlDetal=0, $db){

		$sqlEmpExiste = "SELECT count(`uid_empresa`) FROM ".TABLE_EMPRESA." WHERE `uid_empresa` = $IdEmp";

		$datos = $db->query( $sqlEmpExiste, true );
		foreach($datos as $dato){
			$empresa = $dato["count(`uid_empresa`)"];
		}
		if($empresa==1){
			$listaEmpresas[] = $IdEmp;
			for ($i = 1; $i <= $nvlDetal; $i++) {
				foreach($listaEmpresas as $lempresa){
					$sqlSubcontratas = "SELECT `uid_empresa_inferior` FROM ".TABLE_EMPRESA."_relacion WHERE `uid_empresa_superior` = $lempresa";
					$lineas = $db->query( $sqlSubcontratas, true );
						foreach($lineas as $linea){
							$listaEmpresas[$linea["uid_empresa_inferior"]] = $linea["uid_empresa_inferior"];
						}
				}
			}
			return $listaEmpresas;
		}else{
			echo "No se ha introducido correctamente el id de la empresa.";
			exit;
		}
	}

	function getListaEmpleados($listaEmpresas, $db){

		foreach($listaEmpresas as $empresa ){
			$sqlEmpleados = "SELECT `uid_empleado` FROM ".TABLE_EMPLEADO."_empresa WHERE uid_empresa = $empresa";
			$datos = $db->query( $sqlEmpleados, true );
				foreach($datos as $dato){
					$listaEmpleados[$dato["uid_empleado"]] = $dato["uid_empleado"];
				}
		}
		return $listaEmpleados;
	}

	function getListaMaquinas($listaEmpresas, $db){

		foreach($listaEmpresas as $empresa ){
			$sqlMaquinas = "SELECT uid_maquina FROM ".TABLE_MAQUINA."_empresa WHERE uid_empresa = $empresa";
			$datos = $db->query( $sqlMaquinas, true );
				foreach($datos as $dato){
					$listaMaquinas[$dato["uid_maquina"]] = $dato["uid_maquina"];
				}
		}
		return $listaMaquinas;
	}

	function getListaUsuarios($listaEmpresas, $db){

		foreach($listaEmpresas as $empresa ){
			$sqlUsuarios = "SELECT uid_usuario FROM ".TABLE_PERFIL." WHERE uid_empresa = $empresa";
			$datos = $db->query( $sqlUsuarios, true );
				foreach($datos as $dato){
					$listaUsuarios[$dato["uid_usuario"]] = $dato["uid_usuario"];
				}
		}
		return $listaUsuarios;
	}
//--------------------------------------------------------------------------------------------------------------------------------------
	function getDocsVigentesEmpresa($listaEmpresas, $db, $uso=false){

		$totalDocsVigentesEmpresa = 0;

		foreach($listaEmpresas as $empresa ){

			if($uso==false){
				$sqlDocsVigentesEmpresa = "SELECT count(uid_anexo_empresa) FROM ".PREFIJO_ANEXOS."empresa WHERE uid_empresa = $empresa";
			}
			else{
				$sqlDocsVigentesEmpresa = 	"SELECT count(uid_anexo_empresa) 
								FROM ".PREFIJO_ANEXOS."empresa 
								WHERE uid_empresa = $empresa AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empresa
																AND uid_modulo_destino =".util::getModuleId("empresa").")";

			}
			$datos = $db->query( $sqlDocsVigentesEmpresa, true );
				foreach($datos as $dato){
					$totalDocsVigentesEmpresa += $dato["count(uid_anexo_empresa)"];
				}
		}
		return $totalDocsVigentesEmpresa;
	}

	function getDocsVigentesEmpleado($listaEmpleados, $db, $uso=false){

		$totalDocsVigentesEmpleado = 0;

		foreach($listaEmpleados as $empleado ){

			if($uso==false){
				$sqlDocsVigentesEmpleado = "SELECT count(uid_anexo_empleado) FROM ".PREFIJO_ANEXOS."empleado WHERE uid_empleado = $empleado";
			}
			else{
				$sqlDocsVigentesEmpleado = 	"SELECT count(uid_anexo_empleado) 
								FROM ".PREFIJO_ANEXOS."empleado 
								WHERE uid_empleado = $empleado AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empleado
																AND uid_modulo_destino =".util::getModuleId("empleado").")";

			}
			$datos = $db->query( $sqlDocsVigentesEmpleado, true );
				foreach($datos as $dato){
					$totalDocsVigentesEmpleado += $dato["count(uid_anexo_empleado)"];
				}
		}
		return $totalDocsVigentesEmpleado;
	}

	function getDocsVigentesMaquina($listaMaquinas, $db, $uso=false){

		$totalDocsVigentesMaquina = 0;

		foreach($listaMaquinas as $maquina ){

			if($uso==false){
				$sqlDocsVigentesMaquina = "SELECT count(uid_anexo_maquina) FROM ".PREFIJO_ANEXOS."maquina WHERE uid_maquina = $maquina";
			}
			else{
				$sqlDocsVigentesMaquina = 	"SELECT count(uid_anexo_maquina) 
								FROM ".PREFIJO_ANEXOS."maquina 
								WHERE uid_maquina = $maquina AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $maquina
																AND uid_modulo_destino =".util::getModuleId("maquina").")";
			}
			$datos = $db->query( $sqlDocsVigentesMaquina, true );
				foreach($datos as $dato){
					$totalDocsVigentesMaquina += $dato["count(uid_anexo_maquina)"];
				}
		}
		return $totalDocsVigentesMaquina;
	}
//--------------------------------------------------------------------------------------------------------------------------------------
	function getDocsHistoricoEmpresa($listaEmpresas, $db, $uso=false){

		$totalDocsHistoricoEmpresa = 0;

		foreach($listaEmpresas as $empresa ){
	
			if($uso==false){
				$sqlDocsHistoricoEmpresa = "SELECT count(uid_anexo_historico_empresa) FROM ".PREFIJO_ANEXOS_HISTORICO."empresa WHERE uid_empresa = $empresa";
			}
			else{
				$sqlDocsHistoricoEmpresa = 	"SELECT count(uid_anexo_historico_empresa) 
								FROM ".PREFIJO_ANEXOS_HISTORICO."empresa 
								WHERE uid_empresa = $empresa AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empresa
																AND uid_modulo_destino =".util::getModuleId("empresa").")";
			}
			$datos = $db->query( $sqlDocsHistoricoEmpresa, true );
				foreach($datos as $dato){
					$totalDocsHistoricoEmpresa += $dato["count(uid_anexo_historico_empresa)"];
				}
		}
		return $totalDocsHistoricoEmpresa;
	}

	function getDocsHistoricoEmpleado($listaEmpleados, $db, $uso=false){

		$totalDocsHistoricoEmpleado = 0;

		foreach($listaEmpleados as $empleado ){

			if($uso==false){
				$sqlDocsHistoricoEmpleado = "SELECT count(uid_anexo_historico_empleado) FROM ".PREFIJO_ANEXOS_HISTORICO."empleado WHERE uid_empleado = $empleado";
			}
			else{
				$sqlDocsHistoricoEmpleado = 	"SELECT count(uid_anexo_historico_empleado) 
								FROM ".PREFIJO_ANEXOS_HISTORICO."empleado 
								WHERE uid_empleado = $empleado AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empleado
																AND uid_modulo_destino =".util::getModuleId("empleado").")";
			}
			$datos = $db->query( $sqlDocsHistoricoEmpleado, true );
				foreach($datos as $dato){
					$totalDocsHistoricoEmpleado += $dato["count(uid_anexo_historico_empleado)"];
				}
		}
		return $totalDocsHistoricoEmpleado;
	}

	function getDocsHistoricoMaquina($listaMaquinas, $db, $uso=false){

		$totalDocsHistoricoMaquina = 0;

		foreach($listaMaquinas as $maquina ){

			if($uso==false){
				$sqlDocsHistoricoMaquina = "SELECT count(uid_anexo_historico_maquina) FROM ".PREFIJO_ANEXOS_HISTORICO."maquina WHERE uid_maquina = $maquina";
			}
			else{
				$sqlDocsHistoricoMaquina = 	"SELECT count(uid_anexo_historico_maquina) 
								FROM ".PREFIJO_ANEXOS_HISTORICO."maquina 
								WHERE uid_maquina = $maquina AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $maquina
																AND uid_modulo_destino =".util::getModuleId("maquina").")";
			}
			$datos = $db->query( $sqlDocsHistoricoMaquina, true );
				foreach($datos as $dato){
					$totalDocsHistoricoMaquina += $dato["count(uid_anexo_historico_maquina)"];
				}
		}
		return $totalDocsHistoricoMaquina;
	}
//--------------------------------------------------------------------------------------------------------------------------------------
	function getEspacioDocsVigentesEmpresa($listaEmpresas, $db, $uso=false){

		$totalEspacioDocsVigentesEmpresa = 0;

		foreach($listaEmpresas as $empresa ){

			if($uso==false){
				$sqlArchivoDocsVigentesEmpresa = "SELECT archivo FROM ".PREFIJO_ANEXOS."empresa WHERE uid_empresa = $empresa";
			}
			else{
				$sqlArchivoDocsVigentesEmpresa = 	"SELECT archivo 
								FROM ".PREFIJO_ANEXOS."empresa 
								WHERE uid_empresa = $empresa AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empresa
																AND uid_modulo_destino =".util::getModuleId("empresa").")";
			}
			$datos = $db->query( $sqlArchivoDocsVigentesEmpresa, true );
				foreach($datos as $dato){
					$totalEspacioDocsVigentesEmpresa += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsVigentesEmpresa;
	}

	function getEspacioDocsVigentesEmpleado($listaEmpleados, $db, $uso=false){

		$totalEspacioDocsVigentesEmpleado = 0;

		foreach($listaEmpleados as $empleado ){

			if($uso==false){
				$sqlArchivoDocsVigentesEmpleado = "SELECT archivo FROM ".PREFIJO_ANEXOS."empleado WHERE uid_empleado = $empleado";
			}
			else{
				$sqlArchivoDocsVigentesEmpleado = "SELECT archivo 
								FROM ".PREFIJO_ANEXOS."empleado 
								WHERE uid_empleado = $empleado AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empleado
																AND uid_modulo_destino =".util::getModuleId("empleado").")";
			}
			$datos = $db->query( $sqlArchivoDocsVigentesEmpleado, true );
				foreach($datos as $dato){
					$totalEspacioDocsVigentesEmpleado += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsVigentesEmpleado;
	}

	function getEspacioDocsVigentesMaquina($listaMaquinas, $db, $uso=false){

		$totalEspacioDocsVigentesMaquina = 0;

		foreach($listaMaquinas as $maquina ){
			$sqlArchivoDocsVigentesMaquina = "SELECT archivo FROM ".PREFIJO_ANEXOS."maquina WHERE uid_maquina = $maquina";
			$datos = $db->query( $sqlArchivoDocsVigentesMaquina, true );
				foreach($datos as $dato){
					$totalEspacioDocsVigentesMaquina += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsVigentesMaquina;
	}
//--------------------------------------------------------------------------------------------------------------------------------------
	function getEspacioDocsHistoricoEmpresa($listaEmpresas, $db, $uso=false){

		$totalEspacioDocsHistoricoEmpresa = 0;

		foreach($listaEmpresas as $empresa ){

			if($uso==false){
				$sqlArchivoDocsHistoricoEmpresa = "SELECT archivo FROM ".PREFIJO_ANEXOS_HISTORICO."empresa WHERE uid_empresa = $empresa";
			}
			else{
				$sqlArchivoDocsHistoricoEmpresa = "SELECT archivo 
								FROM ".PREFIJO_ANEXOS_HISTORICO."empresa 
								WHERE uid_empresa = $empresa AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empresa
																AND uid_modulo_destino =".util::getModuleId("empresa").")";
			}
			$datos = $db->query( $sqlArchivoDocsHistoricoEmpresa, true );
				foreach($datos as $dato){
					$totalEspacioDocsHistoricoEmpresa += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsHistoricoEmpresa;
	}

	function getEspacioDocsHistoricoEmpleado($listaEmpleados, $db, $uso=false){

		$totalEspacioDocsHistoricoEmpleado = 0;

		foreach($listaEmpresas as $empresa ){
			if($uso==false){
				$sqlArchivoDocsHistoricoEmpleado = "SELECT archivo FROM ".PREFIJO_ANEXOS_HISTORICO."empleado WHERE uid_empleado = $empleado";
			}
			else{
				$sqlArchivoDocsHistoricoEmpleado = "SELECT archivo 
								FROM ".PREFIJO_ANEXOS_HISTORICO."empleado 
								WHERE uid_empleado = $empleado AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $empleado
																AND uid_modulo_destino =".util::getModuleId("empleado").")";
			}
			$datos = $db->query( $sqlArchivoDocsHistoricoEmpleado, true );
				foreach($datos as $dato){
					$totalEspacioDocsHistoricoEmpleado += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsHistoricoEmpleado;
	}

	function getEspacioDocsHistoricoMaquina($listaMaquinas, $db, $uso=false){

		$totalEspacioDocsHistoricoMaquina = 0;

		foreach($listaMaquinas as $maquina ){

			if($uso==false){
				$sqlArchivoDocsHistoricoMaquina = "SELECT archivo FROM ".PREFIJO_ANEXOS_HISTORICO."maquina WHERE uid_maquina = $maquina";
			}
			else{
				$sqlArchivoDocsHistoricoMaquina = "SELECT archivo 
								FROM ".PREFIJO_ANEXOS_HISTORICO."maquina 
								WHERE uid_maquina = $maquina AND uid_documento_atributo in (	SELECT uid_documento_atributo
																FROM ".TABLE_DOCUMENTOS_ELEMENTOS."
																WHERE uid_elemento_destino = $maquina
																AND uid_modulo_destino =".util::getModuleId("maquina").")";
			}
			$datos = $db->query( $sqlArchivoDocsHistoricoMaquina, true );
				foreach($datos as $dato){
					$totalEspacioDocsHistoricoMaquina += filesize(DIR_FILES . $dato["archivo"]);
				}
		}
		return $totalEspacioDocsHistoricoMaquina;
	}
?>
