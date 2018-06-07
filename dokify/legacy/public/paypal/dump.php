<?php
	include( realpath( dirname(__FILE__) . "/../config.php") );

	// Nos aseguramos de que es una shell
	if( !isset($_SERVER["SHELL"]) ){ die("Inaccesible"); }

	// Parametros si es fecha
	if( isset($_SERVER["argv"][1]) && is_numeric(str_replace("/","", $_SERVER["argv"][1])) && strlen($_SERVER["argv"][1]) == 10 ){
		echo "Buscando en fecha {$_SERVER["argv"][1]}\n";
		$date = "'". $_SERVER["argv"][1] ."'";
	} else {
		$date = "DATE_FORMAT(NOW(),'%d/%m/%Y')";
	}

	// Nos da igual la transaccion
	if( isset($_SERVER["argv"][1]) && !is_numeric(str_replace("/","", $_SERVER["argv"][1])) ){
		$date = "0";
	}

	// Conexion
	$db = db::singleton();

	$sql = "SELECT 
		cif,
		SUBSTRING_INDEX(payment_date,' ',1) payment_time,
		DATE_FORMAT(STR_TO_DATE( SUBSTRING_INDEX(payment_date,' ',-4),'%M %d,%Y'),'%d/%m/%Y') payment_date,
		first_name, last_name, 
		( SELECT telefono FROM ". TABLE_EMPRESA ."_contacto ec WHERE ec.uid_empresa = empresa.uid_empresa ORDER BY principal LIMIT 1 ) telfono,
		( SELECT fax FROM ". TABLE_EMPRESA ."_contacto ec WHERE ec.uid_empresa = empresa.uid_empresa ORDER BY principal LIMIT 1 ) fax,
		payer_email, shipping, address_name, address_country, address_country_code, address_zip, address_state, address_city, address_street, item_name
		, quantity, handling_amount, tax, mc_currency, mc_fee, mc_gross, txn_id, transaction_subject
		FROM ". paypal::TABLE_TRANSACTION ."
		INNER JOIN ". paypal::TABLE_CONCEPTS ." pc USING ( custom )
		INNER JOIN ". TABLE_EMPRESA ." USING( uid_empresa )
		WHERE ( test_ipn = 0 )
		AND (
			DATE_FORMAT(STR_TO_DATE( SUBSTRING_INDEX(payment_date,' ',-4),'%M %d,%Y'),'%d/%m/%Y') = $date
		)
	";

	if( isset($_SERVER["argv"][1]) && !is_numeric(str_replace("/","", $_SERVER["argv"][1])) ){
		echo "Buscando pago por id de transaccion\n";
		$sql .= " OR ( txn_id  = '{$_SERVER["argv"][1]}' )";
	}

	$data = $db->query($sql, true);

	$files = array();
	foreach($data as $line){
		$buffer = array();
		foreach($line as $field => $value){
			$buffer[] = csv::encapsulate($value);
		}
		$data = implode(";",$buffer);

		$filename = "/tmp/" . $line["txn_id"] . ".csv";
		if( file_put_contents($filename, utf8_encode($data)) ){
			$files[] = $filename;
		} else {
			die("No puedo escribir en $filename. Fin\n");
		}
	}

	if( !count($files) ){ echo "No hay ficheros para cargar\n"; exit; }

	// VAMOS A CONECTAR AL FTP
	$ftp_server = "192.168.10.2";
	$ftp_user_name = "Agd";
	$ftp_password = "skywalker";

	// set up basic connection
	$conn_id = ftp_connect($ftp_server); 
	// login with username and password
	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_password ); 


	// check connection
	if ((!$conn_id) || (!$login_result)) { 
		echo "FTP connection has failed!";
		echo "Attempted to connect to $ftp_server for user $ftp_user_name\n"; 
		exit;
	} else {
		echo "Conectado a $ftp_server como $ftp_user_name\n";

		foreach( $files as $file ){
			// Destino
			$destination_file = basename($file);

			// upload the file
			$upload = ftp_put($conn_id, $destination_file, $file, FTP_BINARY); 

			// check upload status
			if( !$upload ){
				var_dump($upload);
				echo "Upload $file has failed!\n";
			} else {
				echo "Uploaded $file to $ftp_server as $destination_file\n";
			}
		}

		// close the FTP stream 
		ftp_close($conn_id); 
	}


?>
