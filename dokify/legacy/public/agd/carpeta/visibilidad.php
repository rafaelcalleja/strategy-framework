<?php
/*
INSERT INTO `accion` (`alias`, `icono`) VALUES
('Visibilidad', '/img/famfam/find.png');

INSERT INTO `modulo_accion` (`uid_modulo`, `uid_accion`, `href`, `config`, `string`, `confirm`, `prioridad`, `tipo`, `activo`, `uid_modulo_referencia`) VALUES
(25, ___UID_ACCION___, 'carpeta/visibilidad.php', 0, 'desc_visibilidad_cadenas', '', 10, 1, 1, 25);

CREATE TABLE IF NOT EXISTS `carpeta_usuario` (
  `uid_carpeta_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `uid_carpeta` int(11) NOT NULL,
  `uid_usuario` int(11) NOT NULL,
  PRIMARY KEY (`uid_carpeta_usuario`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
*/

include("../../api.php");
$template = Plantilla::singleton();
$log = log::singleton();
$carpeta = new carpeta( obtener_uid_seleccionado() );

// si no tiene acceso al elemento...
if ( ! $usuario->accesoElemento($carpeta) ) 
{
  // dump('no hay permiso');
  $log->info( $carpeta->getModuleName(),
              "error visibilidad carpeta ".$carpeta->getUserVisibleName(), 
              $carpeta->getUserVisibleName(),
              'error permisos',
              true );
  die('Sin permiso para ejecutar esta acción.');
}
// else dump('hay permiso');

if ( isset($_REQUEST["send"]) ){
  $estado = $carpeta->actualizarVisibilidad();
  if ( $estado === true ) {
		$template->assign( "succes", "exito_titulo" );
	} else {
		$template->assign( "error" , $estado );
	}
}

$asignados = $usuario->getCompany()->obtenerUsuarios();
$disponibles = $carpeta->obtenerVisibilidad();


$assigned = new ArrayObjectList;
if ($asignados && count($asignados)) {
  foreach ($asignados as $i => $asignado) {
    if ( !in_array($asignado,$disponibles) ) { $assigned[] = $asignados[$i]; }
  }
}

$template->assign( 'carpeta', $carpeta );
$template->assign( 'asignados' , $assigned );
$template->assign( 'disponibles' , $disponibles );
$template->display( 'configurar/asignarsimple.tpl' ); 
