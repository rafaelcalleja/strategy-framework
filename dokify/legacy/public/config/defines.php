<?php

define('DIR_ROOT', dirname(__DIR__) . "/");

define('GOOGLE_API_KEY', 'AIzaSyCXk-m2W1u-796B9lFjVWvqbNmlqnNUCZ0');
define('BOOT_FILE', 'boot.3.min.js');

define('CURRENT_PROTOCOL', get_cfg_var("dokify.protocol"));
define('CURRENT_ENV', get_cfg_var('dokify.env'));
define('GA_CODE', get_cfg_var('dokify.ga'));
define("TOOLKIT_PATH", DIR_ROOT . '../vendor/simplesamlphp/lib/');


$domain = get_cfg_var("dokify.domain");
define('CURRENT_DOMAIN', CURRENT_PROTOCOL . '//'. $domain);

// Siempre sin la última / plase !!!
if (($cdn = get_cfg_var('dokify.cdn')) && !empty($cdn)) {
    define('RESOURCES_DOMAIN', CURRENT_PROTOCOL . '//'. $cdn);
} else if (isset($_SERVER["HTTP_HOST"])) {
    define('RESOURCES_DOMAIN', CURRENT_PROTOCOL . '//'. @$_SERVER["HTTP_HOST"] .'/res');
} else {
    define('RESOURCES_DOMAIN', CURRENT_PROTOCOL . '//'. $domain .'/res');
}

$assetsVersion = (CURRENT_ENV == 'dev') ? 'dev' : VKEY;
if (($assets = get_cfg_var('dokify.assets')) && !empty($assets)) {
    define('WEBCDN', CURRENT_PROTOCOL . '//'. $assets . '/' . $assetsVersion);
} else if (isset($_SERVER["HTTP_HOST"])) {
    define('WEBCDN', CURRENT_PROTOCOL . '//'. @$_SERVER["HTTP_HOST"] .'/assets/' . $assetsVersion);
} else {
    define('WEBCDN', CURRENT_PROTOCOL . '//'. $domain .'/assets/' . $assetsVersion);
}


$errorLog = ($logfile = get_cfg_var('dokify.error.log')) ? $logfile : '/var/log/nginx/error.log';
define('ERROR_LOG_FILE', $errorLog);

define('LIVE', get_cfg_var('dokify.live'));

$attachmentsValidationLimit = get_cfg_var('dokify.attachments_validation_limit');
if (empty($attachmentsValidationLimit) === false) {
    define('ATTACHMENTS_VALIDATION_LIMIT', $attachmentsValidationLimit);
} else {
    define('ATTACHMENTS_VALIDATION_LIMIT', 6000);
}

define('SESSION_USUARIO', 'UID_USUARIO');
define('SESSION_TYPE', 'SESSION_TYPE');
define('SESSION_USUARIO_SIMULADOR', 'UID_USUARIO_SIMULADOR');


//CARPETA DE CLASES, AQUI HABRA UNA CARPETA CON CADA CLASE
define('DIR_CLASS', DIR_ROOT . 'class/');

//CARPETA DE FUNCIONES PHP
define('DIR_FUNC', DIR_ROOT . 'func/');

//CARPETA DE ARCHIVOS DE CONFIGURACION
define('DIR_CONFIG', DIR_ROOT . 'config/');

//CARPETA DE ARCHIVOS DE IDIOMA
define('DIR_LANG', DIR_ROOT . 'lang/');

//CARPETA DE LA CLASE "smarty" PARA LAS PLANTILLAS
define('DIR_SMARTY', DIR_CLASS . 'templates/');

//CARPETA DONDE SE GUARDAN LAS PLANTILLAS
define('DIR_TEMPLATES', DIR_ROOT . 'tpl/');

//CARPETA DONDE SE GUARDAN LOS CSS
define('DIR_CSS', DIR_ROOT . 'res/css/');


$dir_files = get_cfg_var('dokify.dir_files');
if (!empty($dir_files)) {
    define('DIR_FILES', $dir_files);
} else {
    define('DIR_FILES', '/home/agd/files/');
}

$dir_export = get_cfg_var('dokify.dir_export');
if (!empty($dir_export)) {
    define('DIR_EXPORT', $dir_export);
} else {
    define('DIR_EXPORT', DIR_FILES . 'export/');
}

$dir_log = get_cfg_var('dokify.dir_log');
if (!empty($dir_log)) {
    define('DIR_LOG', $dir_log);
} else {
    define('DIR_LOG', '/home/agd/log/');
}

define('PHP_CLI', '/usr/bin/php');

//CARPETA DONDE SE GUARDAN LAS PLANTILLAS PARA EVALUACIÓN DE RIESGOS
define('DIR_RIESGOS', DIR_FILES . 'riesgos/');

//CARPETA DONDE SE GUARDAN LOS ARCHIVOS DE PLANTILLA DE DOCUMENTOS DESCARGABLES
define('DIR_DOCUMENTOSPLANTILLA', DIR_FILES . 'descargables/');

//CARPETA DONDE SE GUARDAN LOS ARCHIVOS DE PLANTILLA DE CADA CLIENTE
define('DIR_EMAILTEMPLATES', DIR_FILES . 'templates/');

//CARPETA DONDE SE GUARDAN LOS PLUGINS
define('DIR_PLUGINS', DIR_ROOT . 'agd/plugin/');

//CARPETA DONDE ESTARAN LOS LOGOS DE LOS CLIENTES
define('DIR_LOGOS', DIR_ROOT . 'res/img/logos/');



define('DIR_IMG', DIR_ROOT . 'res/img/');

//CARPETA DONDE ESTARAN LOS LOGOS DE LOS CLIENTES
define('DIR_ICONOS_PUESTOS', DIR_ROOT . 'res/img/puestos/');

//CARPETA DONDE ESTARAN LOS LOGOS DE LOS CLIENTES
define('DIR_ICONOS_CATEGORIAS', DIR_ROOT . 'res/img/categorias/');

define('DIR_ADJUNTOS', DIR_FILES . 'adjuntos/');


// un script auxiliar (tests/setupdb.sh) modifica esta linea.
// ojo al modificarla
define('DB_PREFIX', 'agd');
//base de datos para acciones temporales
define('DB_TMP', DB_PREFIX.'_tmp');

//base de datos para informacion de la web / blog
define('DB_WEB', 'dokify_web');

//base de datos de plugins
define('DB_PLUGINS', DB_PREFIX.'_plugin');

//base de datos del nucleo
define('DB_CORE', DB_PREFIX.'_core');
//tabla de ayudas al usuario
define('TABLE_HELPER', DB_CORE . '.helper');
//tabla "live" para comprobar modificaciones en las tablas
define('TABLE_LIVE', DB_CORE . '.live');
//tabla de los logs
define('TABLE_LOG', DB_CORE . '.log');
//tabla de los logs de emails
define('TABLE_LOG_EMAIL', DB_CORE . '.log_email');
//tabla de las acciones
define('TABLE_ACCIONES', DB_CORE . '.accion');
//tabla de los modulos
define('TABLE_MODULOS', DB_CORE . '.modulo');
//tabla de las busquedas
define('TABLE_BUSQUEDAS', DB_CORE . '.busqueda');
//tabla de los criterios de buscqueda
define('TABLE_CRITERIOS', DB_CORE . '.criterio');
//tabla de las plugis
define('TABLE_PLUGINS', DB_CORE . '.plugin');
//tabla de las plantillas de email
define('TABLE_PLANTILLAEMAIL', DB_CORE . '.plantillaemail');
//tabla de las opciones globales
define('TABLE_SYSTEM', DB_CORE . '.system');
//categorias predefinidas de la aplicacion
define('TABLE_CATEGORIA', DB_CORE . '.categoria');
// tabla de llamadas a croncall
define('TABLE_CRONCALL', DB_CORE.'.croncall');



//base de datos donde se almacena todo lo con datos
define('DB_DATA', DB_PREFIX.'_data');
// Modelos de datos
define('TABLE_DATAMODEL', DB_DATA . '.datamodel');
define('TABLE_DATAFIELD', DB_DATA . '.datafield');
define('TABLE_MODELFIELD', DB_DATA . '.modelfield'); // cada asignacion de datafield a datamodel
define('TABLE_DATAEXPORT', DB_DATA . '.dataexport');
define('TABLE_DATAIMPORT', DB_DATA . '.dataimport');
define('TABLE_IMPORTACTION', DB_DATA . '.importaction');
define('TABLE_DATACRITERION', DB_DATA . '.datacriterion');
define('TABLE_EXPORTHEADER', DB_DATA . '.exportheader');
define('TABLE_HEADERCOLUMN', DB_DATA . '.headercolumn');

define('TABLE_EXPORTACION_MASIVA', DB_DATA . '.exportacion_masiva');


// Cualquier item podrá tener adjuntos, se almacenan aqui
define('TABLE_ADJUNTO', DB_DATA . '.adjunto');
// Todos los accidentes de todos los empleados
define('TABLE_ACCIDENTE', DB_DATA . '.accidente');
// Guardar los item centro de cotizacion de una empresa
define('TABLE_CENTRO_COTIZACION', DB_DATA . '.centrocotizacion');
// Almacena cada cita para reconocimiento medico
define('TABLE_CITA_MEDICA', DB_DATA . '.citamedica');
// Almacena las convocatorias para citas medicas
define('TABLE_CONVOCATORIA_MEDICA', DB_DATA . '.convocatoriamedica');
//tabla de las bajas de los empleados
define('TABLE_BAJA', DB_DATA . '.baja');
//tabla de epis
define('TABLE_EPI', DB_DATA . '.epi');
//tabla de empleado_epis
define('TABLE_EMPLEADO_EPI', DB_DATA . '.empleado_epi');
//tabla de empleado_epis
//define('TABLE_EMPLEADO_EPI_HISTORICO', DB_DATA . '.empleado_epi_historico');
//tabla de empleado_epis
define('TABLE_TIPO_EPI', DB_DATA . '.tipo_epi');

//lista de modalidades de organizacion preventivas
define('TABLE_ORGANIZACION_PREVENTIVA', DB_DATA . '.organizacion_preventiva');
//log de interfaz de usuario
define('TABLE_LOGUI', DB_DATA . '.logui');
//lista de comentarios guardados como plantillas de anulacion
define('TABLE_COMENTARIO_ANULACION', DB_DATA . '.comentario_anulacion');
//relacion de elementos reales con agrupadores ( no asignaciones )
define('TABLE_ELEMENTO_RELACION', DB_DATA . '.elemento_relacion');
//tabla de las validaciones internas
define('TABLE_VALIDATION', DB_DATA . '.validation');
//tabla de las validaciones internas
define('TABLE_VALIDATION_STATUS', DB_DATA . '.validation_status');
//tabla de las certificaciones
define('TABLE_CERTIFICACION', DB_DATA . '.certificacion');
//tabla de los eventos
define('TABLE_EVENTOS', DB_DATA . '.evento');
//tabla de los paises
define('TABLE_PAIS', DB_DATA . '.pais');
//tabla de las provincias
define('TABLE_PROVINCIA', DB_DATA . '.provincia');
//tabla de las municipios
define('TABLE_MUNICIPIO', DB_DATA . '.municipio');
//tabla de las plantillas de email
define('TABLE_PLANTILLAATRIBUTO', DB_DATA . '.plantilla_atributo');
//tabla de los perfiles
define('TABLE_PERFIL', DB_DATA . '.perfil');
//tabla de los usuarios
define('TABLE_USUARIO', DB_DATA . '.usuario');
//tabla de los usuarios y el historico de contraseñas
define('TABLE_USUARIOS_PASSWORD', DB_DATA . '.usuario_password');
//tabla de los agrupadores
define('TABLE_AGRUPADOR', DB_DATA . '.agrupador');
//tabla de los tipos de agrupamiento
define('TABLE_AGRUPAMIENTO', DB_DATA . '.agrupamiento');
//tabla para los idiomas de los agrupadores
define('TABLE_AGRUPADOR_IDIOMA', DB_DATA . '.agrupador_idioma');
//tabla de los idiomas de los agrupamientos
define('TABLE_AGRUPAMIENTO_IDIOMA', DB_DATA . '.agrupamiento_idioma');
//tabla de los tipos de estructuras
define('TABLE_ESTRUCTURA', DB_DATA . '.estructura');
//tabla de los empleados
define('TABLE_EMPLEADO', DB_DATA . '.empleado');
//tabla de las noticias
define('TABLE_NOTICIA', DB_DATA . '.noticia');
//tabla de los informes
define('TABLE_INFORME', DB_DATA . '.informe');
//tabla de las empresas
define('TABLE_EMPRESA', DB_DATA  . '.empresa');
//tabla de las contactos  - empresa
define('TABLE_CONTACTOEMPRESA', TABLE_EMPRESA . '_contacto');
//relacion de contactos de empresa y plantillas de email
define('TABLE_CONTACTO_PLANTILLAEMAIL', DB_DATA . '.contacto_plantillaemail');
//tabla de las maquinas
define('TABLE_MAQUINA', DB_DATA . '.maquina');
//tabla de las etiquetas
define('TABLE_ETIQUETA', DB_DATA . '.etiqueta');
//tabla de los campos dinamicos
define('TABLE_CAMPO', DB_DATA . '.campo');
//tabla de los carpetas...
define('TABLE_CARPETA', DB_DATA . '.carpeta');
//tabla de los ficheros...
define('TABLE_FICHERO', DB_DATA . '.fichero');
//tabla relacional ficheros con carpetas...
define('TABLE_FICHERO_CARPETA', DB_DATA . '.fichero_carpeta');
//tabla relacional carpetas con carpetas...
define('TABLE_CARPETA_CARPETA', DB_DATA . '.carpeta_carpeta');
//tabla relacional carpetas con carpetas...
define('TABLE_CARPETA_AGRUPADOR', DB_DATA . '.carpeta_agrupador');
//tabla de los roles
define('TABLE_ROL', DB_DATA . '.rol');
//tabla de busquedas guardadas
define('TABLE_BUSQUEDA_USUARIO', DB_DATA.'.usuario_busqueda');
//tabla de negacion de documentos asociados a una búsqueda
define('TABLE_BUSQUEDA_DOCUMENTO', DB_DATA . '.usuario_busqueda_documento_atributo');
//tabla de pantalla de llamadas
define('TABLE_LLAMADA', DB_DATA.'.llamada');
//tabla de pantalla de alarmas
define('TABLE_ALARMA', DB_DATA.'.alarma');
//tabla de pantalla de alarmas pero tabla relacional
define('TABLE_ALARMA_ELEMENTO', DB_DATA.'.alarma_elemento');
//tabla de codigos cnae para empleado
define('TABLE_CNAE', DB_DATA.'.cnae');
//tabla de codigos cnae para empleado
define('TABLE_TIPOCONTRATO', DB_DATA.'.tipocontrato');
//tabla de codigos de actividad empresarial de centros de cotizacion
define('TABLE_ACTIVIDADEMPRESARIAL', DB_DATA.'.actividadempresarial');
//tabla de codigos de ocupacion de los empleados
define('TABLE_CODIGOOCUPACION', DB_DATA.'.codigoocupacion');
//---------
//tabla de codigos de actividad economica de accidentes
define('TABLE_ACTIVIDAD_ECONOMICA', DB_DATA.'.accidente_actividad_economica');
//tabla de codigos de actividad fisica de accidentes
define('TABLE_ACTIVIDAD_FISICA', DB_DATA.'.accidente_actividad_fisica');
//tabla de codigos de desviacion de accidentes
define('TABLE_DESVIACION', DB_DATA.'.accidente_desviacion');
//tabla de codigos de lesion de accidentes
define('TABLE_LESION', DB_DATA.'.accidente_lesion');
//tabla de codigos de modalidad de accidentes
define('TABLE_MODALIDAD', DB_DATA.'.accidente_modalidad');
//tabla de codigos de agente material de accidentes
define('TABLE_AGENTE_MATERIAL', DB_DATA.'.accidente_agente_material');
//tabla de codigos de mutua de accidentes
define('TABLE_MUTUA', DB_DATA.'.accidente_mutua');
//tabla de codigos de parte lesionada de accidentes
define('TABLE_PARTE_LESIONADA', DB_DATA.'.accidente_parte_lesionada');
//tabla de codigos de tipo de trabajo de accidentes
define('TABLE_TIPO_TRABAJO', DB_DATA.'.accidente_tipo_trabajo');
// tabla de codigos de tipo de asistencia de accidentes
define('TABLE_TIPO_ASISTENCIA', DB_DATA.'.accidente_tipo_asistencia');
// tabla de codigos de grado de lesion de accidentes
define('TABLE_GRADO_LESION', DB_DATA.'.accidente_grado_lesion');
// tabla de codigos de causa de accidentes
define('TABLE_CAUSA', DB_DATA.'.accidente_causa');
// tabla de empleados asociados a empresas
define('TABLE_EMPLEADO_EMPRESA', DB_DATA.'.empleado_empresa');
// tabla de maquinas para empresas
define('TABLE_MAQUINA_EMPRESA', DB_DATA.'.maquina_empresa');
// tabla de tareas de soporte
define('TABLE_CODIGOLLAMADA', DB_DATA.'.codigollamada');
// tabla registro invitaciones
define('TABLE_SIGNINREQUEST', DB_DATA.'.signin_request');
// tabla ficheros publicos
define('TABLE_PUBLICFILE', DB_DATA.'.publicfile');
// tabla de relacion empresas con empresas partner
define('TABLE_EMPRESA_PARTNER', DB_DATA.'.empresa_partner');
// tabla donde se guardarán las facturas emitidas
define('TABLE_INVOICE', DB_DATA.'.invoice');
// Items de las facturas emitidas
define('TABLE_INVOICE_ITEM', DB_DATA.'.invoice_item');
// tabla de transacciones de paypal
define('TABLE_TRANSACTION', DB_DATA.'.paypal');
// tabla de mensajes
define('TABLE_MESSAGE', DB_DATA.'.message');
// tabla de idiomas de tipo_epis
define('TABLE_TIPO_EPI_IDIOMA', DB_DATA.'.tipo_epi_idioma');
// show tour to elements
define('ELEMENT_TOUR', DB_DATA.'.element_tour');
// average validation time
define('TABLE_AVG_TIME', DB_DATA.'.validation_avg_time');


// tabla de cuestionario
/*define('TABLE_CUESTIONARIO',DB_DATA.'.cuestionario');
define('TABLE_CUESTIONARIO_PREGUNTA',DB_DATA.'.cuestionario_pregunta');
define('TABLE_CUESTIONARIO_RESPUESTA',DB_DATA.'.cuestionario_respuesta');
define('TABLE_CUESTIONARIO_CUMPLIMENTACION',DB_DATA.'.cuestionario_cumplimentacion');*/


//base de datos donde se almacena todo lo relacionado con los documentos
define('DB_DOCS', DB_PREFIX.'_docs');
//tabla donde se guardan los documentos, id_documento y nombre exclusivamente (flags para busquedas)
define('TABLE_DOCUMENTO', DB_DOCS . '.documento');
define('TABLE_TIPODOCUMENTO', DB_DOCS . '.documento');
//tabla donde se guardan los documentos que se solicitan a cada elemento
define('TABLE_DOCUMENTOS_ELEMENTOS', DB_DOCS . '.documento_elemento');
//tabla donde se guardan los documentos, caracteristicas de cada documento TABLE_ATRIBUTOS_DOCUMENTOS
define('TABLE_DOCUMENTO_ATRIBUTO', DB_DOCS . '.documento_atributo');
//tabla donde se guardan los alias en diferentes idiomas de los atributos
define('TABLE_DOCUMENTO_ATRIBUTO_IDIOMA', DB_DOCS . '.documento_atributo_idioma');
//tabla donde se guardan los alias en diferentes idiomas
define('TABLE_DOCUMENTO_IDIOMA', DB_DOCS . '.documento_idioma');
//tabla donde se anexan los documentos de las empresas, empleados, maquinas...
define('PREFIJO_ANEXOS', DB_DOCS . '.anexo_');
//tabla donde se estableceran atributos para anexos, independiente del autoincremental
define('PREFIJO_ANEXOS_ATRIBUTOS', DB_DOCS . '.anexo_atributo_');
//tabla donde se anexan los documentos de las empresas, empleados, maquinas... en modo historico
define('PREFIJO_ANEXOS_HISTORICO', DB_DOCS . '.anexo_historico_');
//tabla donde se guardan los formatos de documentos permitidos
define('TABLE_FORMATO', DB_DOCS . '.documento_atributo_formato_permitido');
//tabla comentarios
define('PREFIJO_COMENTARIOS', DB_DOCS . '.comentario_');
//tabla seguimiento de comentarios
define('TABLE_WATCH_COMMENT', DB_DOCS . '.watch_comment');
