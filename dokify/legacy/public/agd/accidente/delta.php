<?php
include( "../../api.php");

if ( !$list = obtener_uids_seleccionados()) {
	$list = accidente::getCurrentMonth();
}

try {
	$accidentes = ( $list instanceof ArrayIntList ) ? $list->toObjectList("accidente") : $list;

	if (!$accidentes || !count($accidentes)) {
		throw new Exception('No hay accidentes que exportar!');
	}

	$xml = accidente::deltaXML($accidentes, reset($accidentes)->obtenerDato('baja'));
	$xml->download();
} catch(Exception $e) {
	$tpl = new Plantilla;
	$tpl->assign("html", $e->getMessage());
	$tpl->assign("title", $tpl->getString("error"));
	die("<script>top.agd.actionCallback({cbox:'". db::scape($tpl->getHTML("simplebox.tpl")) ."'});</script>");
}