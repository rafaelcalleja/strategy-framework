<?php
interface Irequestable {
	public function onRequestResponse(solicitud $solicitud, $usuario = null);
	public function hasPendingRequests(elemento $parent = null);
	public function obtenerSolicitudesEmpresa(elemento $parent = null, $filter = null);
}