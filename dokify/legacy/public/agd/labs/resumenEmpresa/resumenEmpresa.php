<?php

	require_once("../../../config.php");
	include("./funcionesResumenEmpresa.php");

	$db = new db();

	//Almacenamos en variables lo ingresado en el formulario
	$IDEmpresa 	= $_POST["txtIDEmp"];
	$nivelDetalle 	= ($_POST["txtNivelDetalle"]!=""?$_POST["txtNivelDetalle"]:0);


	$listaEmpresas 	= array();
	$listaEmpleados = array();
	$listaMaquinas 	= array();
	$listaUsuarios 	= array();

	$listaEmpresas 	= getEmpresas($IDEmpresa, $nivelDetalle, $db);
	$listaEmpleados = getListaEmpleados($listaEmpresas, $db);
	$listaMaquinas 	= getListaMaquinas($listaEmpresas, $db);
	$listaUsuarios 	= getListaUsuarios($listaEmpresas, $db);

	$totalDocsVEmpresaT	= getDocsVigentesEmpresa($listaEmpresas, $db, true);
	$totalDocsVEmpleadoT	= getDocsVigentesEmpleado($listaEmpleados, $db, true);
	$totalDocsVMaquinaT 	= getDocsVigentesMaquina($listaMaquinas, $db, true);

	$totalDocsVEmpresaF	= getDocsVigentesEmpresa($listaEmpresas, $db);
	$totalDocsVEmpleadoF 	= getDocsVigentesEmpleado($listaEmpleados, $db);
	$totalDocsVMaquinaF	= getDocsVigentesMaquina($listaMaquinas, $db);


	$totalEspacioDocsVEmpresaT	= getEspacioDocsVigentesEmpresa($listaEmpresas, $db, true);
	$totalEspacioDocsVEmpleadoT 	= getEspacioDocsVigentesEmpleado($listaEmpleados, $db, true);
	$totalEspacioDocsVMaquinaT 	= getEspacioDocsVigentesMaquina($listaMaquinas, $db, true);

	$totalEspacioDocsVEmpresaF	= getEspacioDocsVigentesEmpresa($listaEmpresas, $db);
	$totalEspacioDocsVEmpleadoF 	= getEspacioDocsVigentesEmpleado($listaEmpleados, $db);
	$totalEspacioDocsVMaquinaF 	= getEspacioDocsVigentesMaquina($listaMaquinas, $db);

	$totalEspacioVigentesT = $totalEspacioDocsVEmpresaT + $totalEspacioDocsVEmpleadoT + $totalEspacioDocsVMaquinaT;
	$totalEspacioVigentesF = $totalEspacioDocsVEmpresaF + $totalEspacioDocsVEmpleadoF + $totalEspacioDocsVMaquinaF;

	$totalDocsHEmpresaT	= getDocsHistoricoEmpresa($listaEmpresas, $db, true);
	$totalDocsHEmpleadoT 	= getDocsHistoricoEmpleado($listaEmpleados, $db, true);
	$totalDocsHMaquinaT 	= getDocsHistoricoMaquina($listaMaquinas, $db, true);

	$totalDocsHEmpresaF	= getDocsHistoricoEmpresa($listaEmpresas, $db);
	$totalDocsHEmpleadoF 	= getDocsHistoricoEmpleado($listaEmpleados, $db);
	$totalDocsHMaquinaF	= getDocsHistoricoMaquina($listaMaquinas, $db);

	$totalEspacioDocsHEmpresaT	= getEspacioDocsHistoricoEmpresa($listaEmpresas, $db, true);
	$totalEspacioDocsHEmpleadoT 	= getEspacioDocsHistoricoEmpleado($listaEmpleados, $db, true);
	$totalEspacioDocsHMaquinaT 	= getEspacioDocsHistoricoMaquina($listaMaquinas, $db, true);

	$totalEspacioDocsHEmpresaF	= getEspacioDocsHistoricoEmpresa($listaEmpresas, $db);
	$totalEspacioDocsHEmpleadoF 	= getEspacioDocsHistoricoEmpleado($listaEmpleados, $db);
	$totalEspacioDocsHMaquinaF 	= getEspacioDocsHistoricoMaquina($listaMaquinas, $db);


	$totalEspacioHistoricoT = $totalEspacioDocsHEmpresaT + $totalEspacioDocsHEmpleadoT + $totalEspacioDocsHMaquinaT;
	$totalEspacioHistoricoF = $totalEspacioDocsHEmpresaF + $totalEspacioDocsHEmpleadoF + $totalEspacioDocsHMaquinaF;

	$totalEspacioT = $totalEspacioVigentesT + $totalEspacioHistoricoT;
	$totalEspacioF = $totalEspacioVigentesF + $totalEspacioHistoricoF;

?>
<html>
	<head>
		<h1>Resumen Informacion Empresa</h1>
	</head>
	<body>
		<table border="1" bgcolor="D8D8D8" cellpadding="3">
			<tr>
				<td colspan=2><strong>ID_Empresa:</strong></td>
				<td colspan=2><?=$IDEmpresa?></td>
			</tr>
			<tr>
				<td colspan=2><strong>Nivel de Detalle de Subcontratas:</strong></td>
				<td colspan=2><?=$nivelDetalle?></td>
			</tr>
			<tr>
				<td><strong>Empresas</strong></td>
				<td><strong>Empleados</strong></td>
				<td><strong>Maquinas</strong></td>
				<td><strong>Usuarios</strong></td>
			</tr>
			<tr>
				<td><?php echo count($listaEmpresas); ?></td>
				<td><?php echo count($listaEmpleados); ?></td>
				<td><?php echo count($listaMaquinas); ?></td>
				<td><?php echo count($listaUsuarios); ?></td>
			</tr>
			<tr>
				<td colspan=2><strong>Documentos Vigentes</strong></td>
				<td colspan=1><strong>Activos:</strong></td>
				<td colspan=1><strong>Reales:</strong></td>

			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Empresa:</strong></td>
				<td colspan=1><?=$totalDocsVEmpresaT?></td>
				<td colspan=1><?=$totalDocsVEmpresaF?></td>
			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVEmpresaT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVEmpresaF)?></td>
			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Empleado:</strong></td>
				<td colspan=1><?=$totalDocsVEmpleadoT?></td>
				<td colspan=1><?=$totalDocsVEmpleadoF?></td>

			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVEmpleadoT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVEmpleadoF)?></td>
			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Maquina:</strong></td>
				<td colspan=1><?=$totalDocsVMaquinaT?></td>
				<td colspan=1><?=$totalDocsVMaquinaF?></td>

			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVMaquinaT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsVMaquinaF)?></td>
			</tr>
			<tr>
				<td colspan=2><strong>Espacio Ocupado Vigentes:</strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioVigentesT)?></strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioVigentesF)?></strong></td>
			</tr>
<!--Aquí empieza el historico -->
			<tr>
				<td colspan=2><strong>Documentos Historico</strong></td>
				<td colspan=1><strong>Activos:</strong></td>
				<td colspan=1><strong>Reales:</strong></td>

			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Empresa:</strong></td>
				<td colspan=1><?=$totalDocsHEmpresaT?></td>
				<td colspan=1><?=$totalDocsHEmpresaF?></td>

			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHEmpresaT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHEmpresaF)?></td>
			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Empleado:</strong></td>
				<td colspan=1><?=$totalDocsHEmpleadoT?></td>
				<td colspan=1><?=$totalDocsHEmpleadoF?></td>
			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHEmpleadoT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHEmpleadoF)?></td>
			</tr>
			<tr>
				<td colspan=2 rowspan=2><strong>Maquina:</strong></td>
				<td colspan=1><?=$totalDocsHMaquinaT?></td>
				<td colspan=1><?=$totalDocsHMaquinaF?></td>
			</tr>
			<tr>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHMaquinaT)?></td>
				<td colspan=1><?=archivo::formatBytes($totalEspacioDocsHMaquinaF)?></td>
			</tr>
			<tr>
				<td colspan=2><strong>Espacio Ocupado Historico:</strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioHistoricoT)?></strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioHistoricoF)?></strong></td>
			</tr>
			<tr>
				<td colspan=2><strong>Total Espacio Ocupado:</strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioT)?></strong></td>
				<td colspan=1><strong><?=archivo::formatBytes($totalEspacioF)?></strong></td>
			</tr>
		</table>
	</body>
</html>
