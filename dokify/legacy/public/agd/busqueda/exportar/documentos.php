<?php
    set_time_limit(0);

    if (isset($_GET["pkey"])) {
        require_once __DIR__ . "/../../../config.php";
        $key = $_GET["pkey"];

        if (!$buscador = buscador::getFromKey($key)) {
            header("Location: /sinacceso.html"); exit;
        } else {
            new customSession();
            $usuario = $buscador->getUser();
        }

    } else {
        $loginParams = array("forceie" => "true"); // Modificar como se redirige la página del login de ir alli por falta de session
        require_once __DIR__ . "/../../../api.php";

        $buscador = new buscador(obtener_uid_seleccionado());

        if (!$usuario->accesoElemento($buscador)) {
            die("Innacesible");
        }
    }

    $template = new Plantilla();


    if (isset($_REQUEST["send"])) {
        switch ($_REQUEST["action"]) {
            case "restorekey":
                if( !isset($_GET["pkey"]) && ($nkey=$buscador->regenerarLink()) ){
                    $buscador->writeLogUI( logui::ACTION_REGENERATE_KEY, $nkey, $usuario);
                    $template->assign("succes", "exito_texto");
                } else {
                    $template->assign("error", "error_texto");
                }
            break;
            case "zip":
                ini_set("memory_limit", "512M");

                define("NO_CACHE_OBJECTS", TRUE);
                $referencias = $buscador->getAssignedReferencias($usuario)->toIntList()->getArrayCopy();
                $whiteList = $buscador->getAssignedAttributes($usuario)->toIntList()->getArrayCopy();


                $availableStatus    = [];
                $downloaded         = false;
                $previous           = false;
                if ($comefrom = obtener_comefrom_seleccionado()) {
                    $availableStatus = $buscador->getSelectedStatuses($comefrom);


                    if (isset($_GET["pkey"])) {
                        $downloaded = $buscador->getDownloadDate($comefrom);
                        $previous   = $buscador->getDownloadDate($comefrom, true);

                        // update the download date
                        $buscador->updateDownloadedDate($comefrom);

                        // update the previous download date too
                        if ($downloaded && $downloaded != $previous && date('Y-m-d', $downloaded) !== date('Y-m-d')) {
                            $buscador->updateDownloadedDate($comefrom, date('Y-m-d h:i:s', $downloaded));
                        }
                    }
                }

                if (!$availableStatus) $availableStatus = array(documento::ESTADO_VALIDADO);


                // Que objetos se encuentran en esta busqueda?
                $objetos = $buscador->getResultObjects($usuario);

                // Instanciamos nuestro zip
                $zipName = "/tmp/". uniqid() .".zip";
                $zip = new ZipHandler($zipName);

                $numfiles   = 0;
                $total      = count($objetos);
                if ($objetos) foreach ($objetos as $i => $objeto) {
                    $progress = round(($i * 100) / $total, 2);


                    // skip if not solicitable
                    if (!$objeto instanceof solicitable) continue;

                    switch ($type = $objeto->getType()) {
                        case "empresa":
                            $folder = trim(archivo::cleanFilenameString($objeto->getCif()." - ". $objeto->getUserVisibleName()));
                        break;
                        case "empleado":
                            $companies = $objeto->getCompanies(false, $usuario);
                            if (!isset($companies[0])) continue;
                            $empresa = $companies[0];
                            $parentFolder = archivo::cleanFilenameString(trim($empresa->getCif()." - ". $empresa->getUserVisibleName()));


                            $info = $objeto->getInfo();
                            $itemName = trim($info["dni"]);
                            if (trim($info["nombre"])) $itemName .= " - ". trim($info["nombre"]);
                            if (trim($info["apellidos"])) $itemName .= " ". trim($info["apellidos"]);

                            $folder = $parentFolder . "/" . archivo::cleanFilenameString($itemName);
                        break;
                        case "maquina":
                            $companies = $objeto->getCompanies(false, $usuario);
                            if (!isset($companies[0])) continue;
                            $empresa = $companies[0];
                            $parentFolder = archivo::cleanFilenameString(trim($empresa->getCif()." - ". $empresa->getUserVisibleName()));

                            $info = $objeto->getInfo();

                            $itemName = trim($info["serie"] . "-". $info["nombre"]);
                            $folder = $parentFolder . "/" . archivo::cleanFilenameString($itemName);
                        break;
                    }


                    //$arrayDocumentos = $objeto->getDocuments(0);
                    $reqtypes = $objeto->getReqTypes(['viewer' => $usuario]);

                    foreach ($reqtypes as $d => $documento) {
                        $requests = $documento->requests;

                        if ($requests && count($requests)) foreach ($requests as $request) {
                            // skip if no attachment found
                            if (!$attachment = $request->getAnexo()) continue;

                            $status = $attachment->getStatus();

                            // skip if status not match
                            if (!in_array($status, $availableStatus)) continue;

                            // if renovation, we use the valid one
                            if ($renovation = $attachment->getAnexoRenovation()) $attachment = $renovation;


                            if ($comefrom && $downloaded) {
                                $uploaded   = $attachment->getUpdateDate();
                                $todaydl    = date('Y-m-d', $downloaded) === date('Y-m-d');
                                $previously = ($previous && $uploaded > $previous) || $previous == false;

                                // si se descargo despues de que este documento se anexara
                                if ($downloaded > $uploaded) {
                                    // si se descargo otro dia diferente a hoy o si se descargo hoy, pero no estaba incluido en el ultimo zip, saltamos
                                    if ($todaydl === false || $previously === false) {
                                        // var_dump('ignorar');exit;
                                        continue;
                                    }
                                }
                            }


                            $file = $attachment->download(true);

                            // skip if cant read
                            if (!archivo::is_readable($file)) continue;

                            // skip if cant download or read
                            if (!$fileData = archivo::leer($file)) continue;

                            $attr = $request->obtenerDocumentoAtributo();

                            // skip if attr is not in the whitelist
                            if (!in_array($attr->getUID(), $whiteList)) continue;


                            $companyReference   = $request->obtenerEmpresaReferencia();
                            $groupReference     = $request->obtenerAgrupadorReferencia();
                            $isValidReference   = !count($referencias) || (count($referencias) && (!$groupReference || in_array($groupReference->getUID(), $referencias)));

                            // skip if reference is invalid
                            if (!$isValidReference) continue;


                            $companyName    = $groupName = false;
                            $alias          = $attr->getUserVisibleName();
                            $requester      = $attr->getElement();
                            $requesterName  = utf8_decode($requester->getUserVisibleName());

                            if ($groupReference instanceof agrupador)           $groupName      = '('.$groupReference->getUserVisibleName().')';
                            if ($companyReference instanceof empresa)           $companyName    = '('.$companyReference->getUserVisibleName().')';
                            if ($companyReference instanceof ArrayObjectList)   $companyName    = '('.implode('', $companyReference->getNames()).')';

                            $ext            = archivo::getExtension($file);
                            $pieces         = array_filter([$alias, $requesterName, $groupName, $companyName]);
                            $fileName       = archivo::cleanFilenameString(implode(' - ', $pieces));

                            $maxFileNameLength = 254 - strlen($ext);
                            if (strlen($fileName) > $maxFileNameLength) {
                                $fileName = substr($fileName, 0, $maxFileNameLength);
                            }

                            $fullPath = "/{$folder}/{$fileName}.{$ext}";
                            if ($zip->addFromString($fullPath, $fileData)) {
                                $numfiles++;
                            }
                        }
                    }

                    customSession::set('dlprogress', $progress);
                }

                customSession::set('progress', "-1");

                if ($numfiles === 0) die("<script>alert('La busqueda que intentas descargar no contiene ningun documento o bien no hay ningún documento actualizado que descargar')</script>");


                // temporal cookie, tell js when we are ready to download
                setcookie("dlfile", "1", time()+60*60*24*30, '/');
                $buscador->writeLogUI(logui::ACTION_DOWNLOAD, "", $usuario);
                $zip->sendToBrowser("documentos.zip");
                exit;
            break;
        }
    }


    if (isset($_GET["pkey"])) {
        header("Location: /sinacceso.html");
        exit;
    }

    if (!$link = $buscador->getLink()) {
        header("Location: /sinacceso.html");
        exit;
    }

    $date = $buscador->getLinkDate();
    $template->assign("titulo", "Fecha de creacion: $date");


    // POSIBLES ACCIONES...
    $elementos = array(
        array(
            "innerHTML" => "Guardar todos los documentos en un zip",
            "href" => "busqueda/exportar/documentos.php?poid=". obtener_uid_seleccionado() ."&action=zip&send=1",
            "name" => "zipdocs",
            "target" => "async-frame"
        ),
        array(
            "innerHTML" => "Abrir mi gestor de correo...",
            "href" => "mailto:persona@empresa.es?subject=Documentos&body=Link de descarga: ". urlencode($buscador->getLink(true)),
            "name" => "email"
        ),
        array(
            "innerHTML" => "Definir aviso periódico",
            "href" => "busqueda/aviso.php?poid=". obtener_uid_seleccionado(),
            "name" => "aviso",
            "className" => "box-it"
        )
    );

    // Seleccionar que documentos no van en la búsqueda
    $elementos[] = array(
            "innerHTML" => "Seleccionar documentos a descargar",
            "href" => "busqueda/asignar.php?comefrom=atributo&poid=" . obtener_uid_seleccionado(),
            "className" => "box-it"
    );

    $restore = $_SERVER["PHP_SELF"] . "?poid=". $buscador->getUID() ."&action=restorekey&send=1";
    $botones = array(
        array("innerHTML" => "Regenerar link", "href" => $restore , "className" => "confirm box-it")
    );

    $template->assign("botones", $botones);
    $template->assign("elementos", $elementos);
    $template->assign("title", "seleccionar_accion");
    $template->display("listaacciones.tpl");
