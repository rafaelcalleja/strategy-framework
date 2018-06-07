<?php

include "../../api.php";

$template = Plantilla::singleton();
$empresaActual = new empresa(obtener_uid_seleccionado());

$userCompany = $usuario->getCompany();
if (!$empresaActual->compareTo($userCompany)) {
    $numberUsers = $empresaActual->obtenerUsuarios();
    if (!$usuario->esStaff() && count($numberUsers)>1) {
        die('Inaccesible');
    }
}

$numUsuarios = $empresaActual->obtenerUsuarios(false, false, false, true);
if ($empresaActual->isFree() && $numUsuarios > 0) {
    $template->display("paypal/userlimit.tpl");
    exit;
}

if (isset($_REQUEST["send"])) {
    $log = new log();
    $log->info("empresa", "crear usuario", $empresaActual->getUserVisibleName());

    /*
     * tratamos de crear el usuario
     * Se crea con $_REQUEST, la contraseña la cambiamos despues
     */
    $nuevoUsuario = usuario::crearNuevo(
        [
            "pass" => 0,
            "pass2" => 0,
            "locale" => $empresaActual->getCountry()->getLanguage(),
        ]
    );

    if ($nuevoUsuario instanceof usuario) {
        //si se insertar el usuario creamos el perfil
        $uidnuevoperfil = $nuevoUsuario->crearPerfil($usuario, $empresaActual, true);

        $log->info("empresa", "crear usuario " . $nuevoUsuario->getUserName(), $empresaActual->getUserVisibleName());

        //si todo es correcto mostramos el ok por pantalla
        if (is_numeric($uidnuevoperfil)) {
            $maillog = new log();
            $maillog->info("usuario", "enviar bienvenida", $nuevoUsuario->getUserVisibleName());

            if ($nuevoUsuario->sendWelcomeEmail($empresaActual) !== true) {
                $maillog->resultado("error", true);
                $template->assign("info", "email_no_enviado");
            }

            if (isset($_REQUEST["contact"]) && $_REQUEST["contact"] == 1) {
                $contacto = $nuevoUsuario->createContact($usuario);
            }

            /* Vamos a asignar un rol generico al usuario que se acaba de crear y a actualizar el perfil conforme a ese rol.*/
            if ($loginRole = $usuario->activeProfile()->getActiveRol()) {
                $rol = $loginRole;
            } else {
                /* El rol que se va asignar por defecto va a ser el rol 'default' */
                $rol = rol::obtenerRolesGenericos(rol::ROL_DEFAULT);
            }

            $rol->actualizarPerfil($uidnuevoperfil, true);

            $log->resultado("ok ", true);
            $maillog->resultado("ok", true);
            $template->display("succes_form.tpl");
            exit;
        }
        //si no, es un error
        $log->resultado("error ".$nuevoUsuario->error, true);

        //debemos borrar el usuario creado, para no confundir al usuario
        config::eliminarElemento($nuevoUsuario->getUID(), TABLE_USUARIO);
        $template->assign("error", $uidnuevoperfil);
    } else {
        //si no, es un error
        $template->assign("error", $nuevoUsuario);
    }
}

$fields = usuario::publicFields("simple");
$fields["utilizar_como_contacto"] = new FormField(
    [
        "tag" => "input",
        "type" => "checkbox",
        "name" => "contact",
    ]
);

$template->assign("campos", $fields);
$template->assign("titulo", "nuevo_usuario");
$template->assign("boton", "crear");
$template->display("form.tpl");
