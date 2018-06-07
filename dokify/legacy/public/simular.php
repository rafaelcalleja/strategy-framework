<?php
    require __DIR__ . '/api.php';


// --- El usuario que cambia de perfil
$uidusuario = obtener_uid_seleccionado();

if (isset($_SESSION[SESSION_USUARIO_SIMULADOR])) {
    // --- Extraemos al usuario real
    $realUser = new usuario($_SESSION[SESSION_USUARIO_SIMULADOR]);

    if ($uidusuario == 0) $uidusuario = $realUser->getUID();
}

if ($usuario->esStaff() || (isset($realUser) && $realUser->esStaff())) {

    if (is_numeric($uidusuario)) {
        // $page = $usuario->getLastPage(true);

        // --- Si ya se estaba simulando
        if (isset($_SESSION[SESSION_USUARIO_SIMULADOR])) {
            $simulador = $usuario->getUID();

            if ($uidusuario === $realUser->getUID()) {
                // Si es el mismo, dejamos se simular
                unset($_SESSION[SESSION_USUARIO_SIMULADOR]);

                // Si me quedo es el mismo tipo de sesion
                if (isset($_GET["action"]) && $_GET["action"] == "stay") {
                    $_SESSION[SESSION_TYPE] = $usuario->getType();
                } else {
                    $_SESSION[SESSION_TYPE] = 'usuario';

                    $page = "/agd/#buscar.php?q=tipo:{$usuario->getType()} uid:" . $usuario->getUID();
                    $usuario = $realUser;
                }
            }
        } else {
            // --- Si no se simulaba, se comienza a simular
            $_SESSION[SESSION_USUARIO_SIMULADOR] = $usuario->getUID();
            $usuario = new usuario($uidusuario);
        }

        if (isset($_GET["action"]) && $_GET["action"] == "stay") {
            // Mantenemos el usuario
            setcookie("username", 0, time()-3600, '/');
            setcookie("token", 0, time()-3600, '/');

            if ($usuario instanceof usuario) {
                $usuario->refreshCookieToken();
            }
        } else {
            $_SESSION[SESSION_USUARIO] = $usuario->getUID();
        }

        session_write_close();

        if ($usuario instanceof usuario) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
                $data = array(
                    "username" => $usuario->getUserName(),
                    "client" => $usuario->getCompany()->getUID(),
                    "tabs" => $usuario->obtenerElementosMenu(),
                    "configurar" => count($usuario->accesoConfiguracion()),
                    "validar" => $usuario->esValidador()
                );

                if ($usuario->getAppVersion() === 2) {
                    $data['page'] = '/app/user/' . $usuario->getUID();
                }

                die(json_encode($data));
            } else {
                header("Location: /agd/");
            }
        } elseif ($usuario instanceof empleado) {
            header("Location: /empleado/");
        }
    } else {
        header("Location: /agd/");
    }
}