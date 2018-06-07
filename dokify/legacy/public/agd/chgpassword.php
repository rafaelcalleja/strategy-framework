<?php

include "../config.php";

// Inicializar la session
require_once DIR_CLASS . '/customSession.class.php';
$session = new CustomSession();

$usuarioTmp = false;
$restaurar = false;
$tipo = 'usuario';

if ((isset($_GET["tipo"]))) {
    if ($_GET["tipo"]=='empleado') {
        $tipo='empleado';
    } elseif ($_GET["tipo"]=='usuario') {
        $tipo='usuario';
    } else {
        header("Location: /login?loc=error_reseteo");
    }

    if ((isset($_GET["token"])) && ($_GET["token"]!='')) {
        $token=$_GET["token"];
        //$email=$_GET["email"];
        //comprobamos si venimos de un email para restaurar password y que es todo correcto
        if ($usuarioTmp = $tipo::uidTokenEmail($token)) {
            $_SESSION[SESSION_USUARIO."_TMP"]=$usuarioTmp->getUID();
            $restaurar = true;
        }
    }
}

if (!$restaurar && (isset($_GET["tipo"]))) {
    header("Location: /login?loc=error_reseteo");
} else {
    if (isset($_SESSION[SESSION_USUARIO."_TMP"]) && $uid = $_SESSION[SESSION_USUARIO."_TMP"]) {
        $usuarioCambio = new $tipo($uid);
        if ($tipo == 'usuario') {
            $usuarioCambio->establecerEstadoConexion(0);
        }

        //creamos la instancia de la plantilla
        $template = new Plantilla();
        $log = log::singleton();

        if (isset($_REQUEST["send"])) {
            $log->info("usuario", "cambiar password login", $usuarioCambio->getUserName());
            if ((isset($_REQUEST["old_password"]) && strlen($_REQUEST["old_password"]) || ($usuarioTmp))
                && isset($_REQUEST["new_password"]) && strlen($_REQUEST["new_password"])
                && isset($_REQUEST["new2_password"]) && strlen($_REQUEST["new2_password"])
            ) {
                if ($usuarioCambio->compararPassword(@$_REQUEST["old_password"]) || ($usuarioTmp)) {
                    $newPassword = usuario::comprobarPassword($_REQUEST["new_password"], $_REQUEST["new2_password"]);
                    if ($newPassword === true) {
                        $cambio = $usuarioCambio->cambiarPassword($_REQUEST["new_password"]);
                        if ($cambio === true) {
                            //al cambiar password se borrar token
                            $borrado_token=$usuarioCambio->borrarToken();
                            //para que no nos saque de la aplicación cuando se hace login desde aqui
                            //$_SESSION["password"] = $_REQUEST["new_password"];
                            //creamos este usuario para que se guarde en session y asi comprobamos que todo esta ok
                            //new usuario( $usuarioCambio->getUID(), true );
                            $_SESSION[SESSION_USUARIO] = $usuarioCambio->getUID();

                            unset($_SESSION[SESSION_USUARIO."_TMP"]);
                            if ($tipo=='usuario') {
                                $_SESSION[SESSION_TYPE] = 'usuario';

                                $usuarioCambio->checkFirstLogin();
                                header("Location: ./");
                            } else {
                                $_SESSION[SESSION_TYPE] = 'empleado';
                                header("Location: /empleado/");
                            }

                        } else {
                            $log->resultado("fallido nueva password no valida", true);
                            $template->assign("error", $cambio);
                        }
                    } else {
                        $log->resultado("fallido password nueva invalida", true);
                        $template->assign("error", $newPassword);
                    }

                } else {
                    $log->resultado("fallido password no valida", true);
                    $template->assign("error", "error_pass_no_valido");
                }

            } else {
                $template->assign("error", "error_completar_todos_campos");
            }
        }

        $template->assign("usuario", $usuarioCambio);
        $template->assign("token_email", $usuarioTmp);
        //mostramos la plantilla
        $template->display("chgpassword.tpl");

    } else {
        header("Location: /login?loc=error_reseteo");
    }
}
