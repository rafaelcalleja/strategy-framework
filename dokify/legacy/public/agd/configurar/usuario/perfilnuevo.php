<?php

require_once("../../../api.php");

$template = new Plantilla();

$UIDusuarioSeleccionado = obtener_uid_seleccionado();
$usuarioPerfil = new usuario($UIDusuarioSeleccionado);

if (isset($_REQUEST["send"])) {
    $arrayDatos = [];

    if (isset($_REQUEST["empresa"])) {
        if (is_numeric($_REQUEST["empresa"])) {
            $empresa = new empresa($_REQUEST["empresa"]);

            if (!$empresa->exists()) {
                $template->assign("error", "error_empresa_no_existe");
            }
        } else {
            $template->assign("error", "error_seleccionar_empresa");
        }
    }

    if (isset($empresa)) {
        if (!$usuarioPerfil->perfilEmpresa($empresa, false)) {
            $arrayDatos["alias"] = "Perfil ".$empresa->getUserVisibleName();
            $arrayDatos["uid_empresa"] = $empresa->getUID();

            if ($empresa->esCorporacion()) {
                $arrayDatos["uid_corporation"] = $empresa->getUID();
            } else {
                $arrayDatos["uid_corporation"] = 'NULL';
            }

            if (!$template->get_template_vars('error')) {
                $arrayDatos["uid_empresa"] = $empresa->getUID();
                $arrayDatos["uid_usuario"] = $usuarioPerfil->getUID();

                $perfil = new perfil($arrayDatos, $usuario);
                if ($perfil->error) {
                    $template->assign("error", $perfil->error);
                } else {
                    $template->assign("acciones", [[
                        "href"   => "#configurar/usuario/editarperfil.php?poid=".$perfil->getUID(),
                        "string" => "configurar_perfil",
                        "class"  => "unbox-it"
                    ]]);
                    $template->display("succes_form.tpl");
                    exit;
                }
            }
        } else {
            $template->assign("error", "Ya tienes un perfil en la empresa: ".$empresa->getUserVisibleName());
        }
    }
}
$empresasCliente = empresa::getEnterpriseCompanies();

$template->assign("usuario", $usuario);
$template->assign("empresasCliente", $empresasCliente);
$template->display("configurar/crearperfil.tpl");
