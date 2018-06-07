<?php

	require_once __DIR__ . '/../../config.php';

	if (!$uid = @$_SERVER["argv"][1]) {
		die("We need a company uid");
	}


	$db = db::singleton();

	$SQLEmployees 	= "SELECT uid_empleado 	FROM ". TABLE_EMPLEADO ."_empresa 	WHERE uid_empresa = {$uid} AND papelera = 0";
	$SQLMachines 	= "SELECT uid_maquina 	FROM ". TABLE_MAQUINA ."_empresa 	WHERE uid_empresa = {$uid} AND papelera = 0";

	$SQL = "SELECT uid_elemento, uid_modulo 
			FROM ". TABLE_AGRUPADOR ."_elemento 
			WHERE (
				(uid_modulo = 8 AND uid_elemento IN ({$SQLEmployees}))
				OR
				(uid_modulo = 14 AND uid_elemento IN ({$SQLMachines}))
			)
	";

	// A filter parameter
	if (isset($_SERVER["argv"][2]) && $param = $_SERVER["argv"][2]) {
		// If it is an object
		if ($assigned = elemento::factory($param)) {
			// If it is an agrupador
			if ($assigned instanceof agrupador) {
				$SQL .= " AND uid_agrupador = {$assigned->getUID()}";
			}
		}
	}

	$updates = 0;
	$rows = $db->query($SQL, true);

	foreach ($rows as $row) {
		list($uid, $uidmodule) = array_values($row);
		$module = util::getModuleName($uidmodule);

		$item = new $module($uid);

		if ($item->actualizarSolicitudDocumentos()) {
			$updates++;
		}
	}

	print $updates . " items updated!\n";