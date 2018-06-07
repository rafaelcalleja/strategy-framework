<?php
	function check_client_url(){
		if( CURRENT_ENV != "dev" ){
			$servername = $_SERVER["SERVER_NAME"];
			$subdomain = explode(".", $_SERVER["SERVER_NAME"]); $subdomain = reset($subdomain);
			$sql = "SELECT subdominio FROM ". TABLE_CLIENTE ." WHERE subdominio LIKE '%". db::scape($subdomain) ."%' LIMIT 1";
			$realsubdomain = trim( db::get($sql, 0, 0) );
			if( $realsubdomain ){
				$realdomain = $realsubdomain.".afianza.net";
				if( $realdomain != $servername ){
					header("Location: http://$realdomain");
					exit;
				}
			}
		}
	}
?>
