<?php

interface Isolicitable
{
    /** Isolicitable getCreationDate
      *
      * Fecha de creación del item en formato europeo o ?? en el caso de no disponer de ella
      */
    public function getCreationDate();

    /** Isolicitable obtenerEmpresaContexto
      *
      * La empresa a la que pertenece el empleado actual en el contacto actual
      */
    public function obtenerEmpresaContexto(Iusuario $usuario = null);

    public function getDocumentsId();

    public function getStatusImage($usuario, $html = false);

    public function obtenerEstadoEnAgrupador(Iusuario $usuario, agrupador $agrupador);

    public function getDocumentsSolicitantes($descargar = false, $obligatorio = null, $papelera = false, $filtro = false);

    public function getNumberOfDocumentsByStatus(Iusuario $usuario = null, $estado = false, $obligatorio = null, $papelera = false, $descargar = 0, $columns = MYSQLI_BOTH, $all = false);

    public function obtenerAtributosDeSolicitante(usuario $usuario = null, Ielemento $solicitante, $descargar = 0, $papelera = null);

    public function obtenerSolicitantesPorModulo(usuario $usuario = null);

    public function tieneDocumentacion();

    public function obtenerDocumentosDeSolicitante(usuario $usuario, $solicitante, $descargar = 0);

    public function obtenerEstadoDocumentos($usuario, $descarga = 0, $obligatorio = null, $companyFilter = null);

    public function getNumberOfDocumentsWithState($estado, usuario $usuario);

    public function informacionDocumentos($usuario, $descarga = 0, $obligatorio = null, $certificacion = false);

    public function obtenerSolicitantesIndirectos();

    public static function status2String($uidestado);
}
