<?php
//----- CARGAMOS EL API
require_once( "../../api.php");

$template = Plantilla::singleton();


if( !$uid = obtener_uid_seleccionado() ){ die("Inaccesible"); }


$empresaUsuario = $usuario->getCompany();
$dataImport = new dataimport($uid);


if( !$dataImport->getKey() ){
    $template->assign("message", "El modelo utilizado no tiene ningún campo que se pueda usar como clave. Especifica un campo clave e intentalo de nuevo");
    $template->display("error.tpl" );
    exit;
}

$writables = $dataImport->getWritableDataFields($usuario);
if (  count($writables) < 1) {
    $template->assign('message','El modelo utilizado no tiene campos modificables.');
    $template->display('error.tpl');
    exit;
}


// Acciones a ejecutar en cada linea
$importActions = $dataImport->obtenerImportActions();
if( !count($importActions) ){
    $template->assign("message", "especifica_una_accion_de_actualizacion");
    $template->display("error.tpl" );
    exit;
}


$importActions = $importActions->reduce('getAction')->getArrayCopy();
// si utilizamos 'papelera' en una importación siempre vamos a tocar relaciones
$crearRelacion = in_array(importaction::ACTION_INSERT, $importActions) || in_array(importaction::ACTION_INSERT_OR_UPDATE, $importActions) || $dataImport->isUsing('papelera');



if( $crearRelacion === true) {
    $fields = new FieldList();
    $fields['uid_elemento_destino'] = new FormField(array('tag'=>'select', 'data' => $empresaUsuario->getStartList(), 'blank' => false ));
}

// Mostramos sobre que item se realizará la importacion
$template->assign("titulo", $template("dataimport") . " · " . $dataImport->getUserVisibleName() . "<hr />");

/*
// Intentamos extraer los campos a modo de informacion
try {
    $campos = call_user_func( array( $modulo ,"publicFields"), elemento::PUBLIFIELDS_MODE_IMPORT );
    $campos = ( $campos instanceof ArrayObject ) ? $campos->getArrayCopy() : $campos;
    $campos = array_keys( $campos );
    //indicar por pantalla el modelo que se necesita
    $template->assign("campos", $campos);
} catch(Error $e){
    die("No se puede realizar la operacion");
}
*/

if( isset($_REQUEST["send"]) ){
    $debug = false;

    if( isset($_SESSION["FILES"]) ){
        if( $debug === false || isset($_REQUEST["do"]) ){
            if($debug) { $tpl = new Plantilla(); $tpl->display("iframe_header.tpl"); }
            if($debug) { echo "Cargando fichero...<br>"; ob_flush(); flush(); }

            $files = unserialize($_SESSION["FILES"]);
            $archivo = $files["archivo"];

            try {
                if (empty($_REQUEST['uid_elemento_destino']) && $crearRelacion) {
                    throw new Exception("Hace falta una empresa a la que asignar los elementos importados.");
                }
                set_time_limit(0);
                //ignore_user_abort(true);
                session_write_close();
                $uidEmpresaDestino = isset($_REQUEST['uid_elemento_destino']) ? $_REQUEST['uid_elemento_destino'] : $empresaUsuario->getUID();
                if( $tmp = $dataImport->load($archivo["tmp_name"], $usuario) ){
                    $info = $dataImport->import($tmp, db::scape($uidEmpresaDestino), $usuario, $debug);

                    $htmlinfo = "<div style='text-align: center'> | ";
                    foreach( $info as $field => $value ){
                        if( $field == "tmp_table" ) continue;
                        if( !is_array($value) && trim($value) || is_numeric($value) ){
                            $htmlinfo .= "$field: $value | ";
                        }
                    }
                    $htmlinfo .= "</div>";

                    if($debug) echo $htmlinfo;
                    if($debug) die("</body></html>");
                    $template->assign("succes", $htmlinfo );
                } else {
                    $template->assign("error", "error" );
                }
            } catch(Exception $e){
                $template->assign("error", "Error: ". $e->getMessage() );
            }
        } else {
            die("<iframe border='0' style='width: 600px; height: 400px;' src='/agd/analytics/import.php?send=true&do=true&poid={$dataImport->getUID()}'></iframe>");
        }
    } else {
        $template->assign("error", "error" );
    }
}

$dataModel = $dataImport->getDataModel();
$modelFields = $dataModel->obtenerModelFields($usuario->getAnalyticsReadonlyCondition());
if ($crearRelacion) {
    $template->assign('campos',$fields);
}
$template->assign("htmlafter", "<br />Campos: <strong>". implode(", ", $modelFields->getNames()) ."</strong>");
$template->assign("ocultarComentario", true);
$template->display( "anexar_descargable.tpl" );
