<?php

include( "../../api.php");
session_write_close(); // si tardamos en ejecutar esta operacion no queremos bloquear el resto de la aplicación

$empresa = new empresa(obtener_uid_seleccionado());

if (!$usuario->accesoElemento($empresa)) {
    die("Inaccesible");
}

$template = new Plantilla();

$infoEmpresa = $empresa->getNumberOfDocumentsByStatus($usuario, false, null, false, 0, MYSQLI_NUM, true);
$infoEmpleado = $empresa->getNumberOfDocumentsByStatusOfChilds($usuario, 'empleado');
$infoMaquina = $empresa->getNumberOfDocumentsByStatusOfChilds($usuario, 'maquina');

// Recuperamos toda la información "en bruto" de los documenotos
$info = [
    $empresa->getUserVisibleName() => $infoEmpresa,
    'empleado' => $infoEmpleado,
    'maquina' => $infoMaquina
];


$nvalidos = $ninvalidos = 0;
foreach ($info as $modulo => $docdata) {
    foreach ($docdata as $estado => $conteo) {
        if ($estado == documento::ESTADO_VALIDADO) {
            $nvalidos += $conteo;
        } else {
            $ninvalidos += $conteo;
        }
    }
}

$cornerString = "";

// Conteo de porcentajes ...
$ntotal = $nvalidos + $ninvalidos;
if ($ntotal) {
    $percent = round($nvalidos * 100 / $ntotal);
    $cornerString = "<div style='white-space:nowrap'><span>{$percent}%</span>&nbsp;<div id='uploadProgressBar' class='progressbar line-block' style='background-position:".(100-$percent)."% 50%'> </div></div>";
}

$data = [];
// Recorremos los modulos
foreach ($info as $modulo => $docdata) {
    $line = [
        $cornerString => $modulo
    ];

    foreach ($docdata as $estado => $conteo) {
        if ($modulo == 'empleado' || $modulo == 'maquina') {
            $href = "buscar.php?p=0&q=empresa:{$empresa->getUID()}%20tipo:anexo-$modulo%20docs:$estado%20obligatorio:1";
        } else {
            $href = "documentos.php?m=empresa&poid={$empresa->getUID()}&estado=$estado";
        }

        //ÑAPA PARA QUE COINCIDA CON EL CÓDIGO DE NUMEROS QUE TIENE LA APLICACION QUE NO ESTA BIEN YA Q EN LA CLASE DOCUMENTO, SIN ANEXAR SE
        //DEFINE CON -1 Y EN LA APLICACION SE ESTA TOMANDO 0...LIO. EN ESTA PANTALLA ES LO MISMO PORQUE SIN SOLICITAR NUNCA VA A APERECER
        //$estado = ( $estado == "-1" ) ? "0" : $estado ;

        $estadoString = documento::status2string($estado);
        $line[$estadoString] = "<a href='#$href' class='stat stat_$estado' style='text-transform:none'>$estadoString $conteo</a>";
    }
    // Almacenamos cada linea en el array general
    $data[] = $line;
}

$template->assign("data", $data);
$template->assign("titulos_columnas", "1");
//$template->assign("titulo", "resumen_documentos");

$template->display("extended_simple.tpl");
