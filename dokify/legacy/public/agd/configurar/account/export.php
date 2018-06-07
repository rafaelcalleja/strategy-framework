<?php
require_once("../../../api.php");

if (!$oid = obtener_uid_seleccionado()) {
	die('Inaccesible');
}
$export = new exportacion_masiva($oid);

$owner = $export->getCompany();
if(!$usuario->getCompany()->getStartIntList()->contains($owner->getUID())) die("Inaccesible");

$tpl = Plantilla::singleton();
$log = log::singleton();
	
switch ($_REQUEST['action']) {
	case 'progress':
		if (!$export->estaBloqueada()) {
			print json_encode(array("refresh" => true));
			exit;
		}

		$html  = '<img src="'.RESOURCES_DOMAIN.'/img/famfam/time.png"> ';
		$html .= '<a class="ucase inline-text">'.$export->getProgressText().'</a>';
		print $html;
		exit;
	break;
	case 'dl':
		set_time_limit(0);
		if (isset($_REQUEST['send']) && $_REQUEST['send'] == 1) {
			$log->info($export->getModuleId(),"descargar exportacion {$export->getUserVisibleName()} ({$export->getUID()})", $export->getUserVisibleName() );

			$filename = $export->getFilename(true);
			if (archivo::is_readable($filename)) {
				$log->resultado("ok", true);

				if ($URL = archivo::getTemporaryPublicURL($export->getFilename(true), $export->getDownloadName())) {
					header("Location: $URL");
					/*
					header("Content-Transfer-Encoding: binary");
					header("Pragma: public");
					header("Expires: 0");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Cache-Control: public");
					header("Content-Description: File Transfer");
					header("Content-Type: application/zip");
					header("Content-Disposition: attachment; filename=backup.zip;");
					header("Content-Length: ".$export->getSize());

					readfile($URL);
					*/
				} else {
					archivo::descargar( $export->getFilename(true), $export->getDownloadName());
				}
			} else {
				$log->resultado("error {$filename} no legible", true);
				header('HTTP/1.1 404 Not Found');
				die('El archivo no está disponible.');
			}
			exit;
		}
		
		$tpl->assign('export',$export);
		$tpl->display( "descargarexportacion.tpl" ); //mostramos la plantilla
	break;
	case 'gen': default:
		$log->info($export->getModuleId(),"iniciando exportacion {$export->getUserVisibleName()} ({$export->getUID()})", $export->getUserVisibleName(), 'ok', true );
		if (!$export->estaBloqueada()) {
			$export->runScript();
		}

		$tpl->assign("succes", "mensaje_generando_exportacion_masiva");
		$tpl->assign('title','generando_exportacion_masiva');
		$tpl->display('succes_string.tpl');
	break;
}