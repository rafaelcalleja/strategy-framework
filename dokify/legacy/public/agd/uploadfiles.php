<?php
	ini_set('memory_limit', '256M');
	ob_start();
	require_once __DIR__ . "/../api.php";
	unset($_SESSION["FILES"]);


	// El proceso de upload es lento...
	set_time_limit(60*4);


	// Simulamos la variable $_FILES de PHP
	$FILES = array();	

	// Definimos la variable files...
	$FILE = NULL;

	// El usuario tiene un limite de upload
	$maxSize = $usuario->maxUploadSize();

	// Define data
	$data = NULL;

	$method = trim($_SERVER["REQUEST_METHOD"]);
	$document = false;
	if ($documentID = obtener_uid_seleccionado()) {
		$itemID = @$_REQUEST["o"];
		$module = obtener_modulo_seleccionado();
		if ($module && $itemID) {
			$selectedItem = new $module($itemID);
			$document = new documento($documentID, $selectedItem);
		}
	}


	// Si se hace mediante POST
	if ($method === "POST") {
		$bodySize = $_SERVER['CONTENT_LENGTH'];
		// error_log("max size {$maxSize} - post size {$bodySize}");

		if ($bodySize > $maxSize) {
			$FILE = array("error" => "size_error");
			$FILES["archivo"] = $FILE;
			$_SESSION["FILES"] = serialize($FILES);
			//header("HTTP/1.1 500 Internal Server Error");
			die($FILE["error"]);
		}

		// set_time_limit(0);
		echo str_repeat('-',1024*64);
		flush();
		ob_flush();

		// Usamos la variable FILE
		if (is_array($_FILES) && count($_FILES) && isset($_FILES["archivo"])) {
			$FILE = $_FILES["archivo"];

			if ($FILE["error"]){
				error_log("File upload error #".$FILE["error"]);

				ob_get_clean();
				@header("HTTP/1.1 500 Internal Server Error");

				die("error_".$FILE["error"]);
			}

			// Si el limite es superado...
			if ($FILE["size"] > $maxSize) {
				$FILE["error"] = "size_error";

				$FILES["archivo"] = $FILE;

				$_SESSION["FILES"] = serialize($FILES);

				//header("HTTP/1.1 500 Internal Server Error");
				die($FILE["error"]);
			} else {
				// Si acceso de lectura a nuestro fichero temporal...
				if (file_exists($FILE["tmp_name"]) && is_readable($FILE["tmp_name"])) {
					$data = file_get_contents($FILE["tmp_name"]);

					$FILE["md5_file"] = md5_file($FILE["tmp_name"]);
				}
			}
		} else {
			unset($_SESSION["FILES"]);
			session_write_close();
			if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
				die("<div style='padding: 30px'>Ocurrio un error desconocido.</div>");
			}
			exit;
		}
	} elseif ($method === "PUT") {
		session_write_close();

		// Array estilo $_FILES
		$FILE = array("name" => $_SERVER['HTTP_X_FILE_NAME'], "size" => $_SERVER['HTTP_X_FILE_SIZE'], "type" => @$_SERVER['HTTP_X_FILE_TYPE'], "error" => "" );

		// Si el limite es superado...
		if ($FILE["size"] > $maxSize) {
			ob_get_clean();
			header("HTTP/1.1 500 Internal Server Error");
			die("size_error");
		}
		
		$data = file_get_contents("php://input");

		$FILE["md5_file"] = md5($data);
	}


	$filename 	= archivo::getRandomName($FILE["name"]);

	if (!$data || !archivo::tmp($filename, $data)) {
		if ($data) {
			error_log("{$method}_upload_write_error:uploadfiles.php");
		} else {
			error_log("{$method}_upload_write_error:uploadfiles.php [no_data]");
		}

		if ($method === "PUT") {
			new CustomSession();
			ob_get_clean();
			header("HTTP/1.1 500 Internal Server Error");
		}
		
		unset($_SESSION["FILES"]);
		die("write_error");
	}

	$FILE["tmp_name"] = $filename;

	// codificar en utf8 si es necesario
	if (mb_detect_encoding($FILE["name"], ['UTF-8', 'ISO-8859-1']) != 'UTF-8') $FILE["name"] = utf8_encode($FILE["name"]);
	

	$FILES["archivo"] = $FILE;
	if ($method === "PUT") new CustomSession();
	$_SESSION["FILES"] = serialize($FILES);
	

	if ($method === "POST") {
		//header("Location: /blank.html");
	} else {
		ob_clean();

		$res = array('html' => array('#post-process' => ''));
		if ($document instanceof documento && $usuario instanceof usuario) {
			if ($document->canSelectItems($usuario) && $document->isVersionable()) {
				$html = $document->getHTMLEmployeeList($usuario, $filename);

				$res['html'] = array(
					'#post-process' => $html
				);
			}
		}
	
		header("Content-type: application/json");
		print json_encode($res);
	}