<?php
	function compress($buffer) {
		//return $buffer;
		$buffer = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!' , '', $buffer);

		if( strpos($_SERVER["REQUEST_URI"], ".css") === false ){
			$buffer = preg_replace( "#\s//(.+)\n#", '', $buffer );
		}

		$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
		$buffer = str_replace(array(" = "," == "," : ",", "," + "," += "," else ","( "," )"," || "," && ","; "," < "," > ","{ ","[ "," ]"),array("=","==",":",",","+","+=","else","(",")","||","&&",";","<",">","{","[","]"), $buffer);
		return $buffer;
	}
?>
