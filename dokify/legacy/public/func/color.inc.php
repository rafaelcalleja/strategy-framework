<?php
	
	function color($hex, $change){
		$hex = substr($hex,1);

		$parts = str_split ($hex, 2);
		foreach($parts as &$part ){
			$dec = hexdec($part);
			$value = $dec + $change;

			if( $value > 255 ) { $value = 255; }
			if( $value < 0 ) { $value = 0; }

			$part = dechex($value);
			if( strlen($part) == 1 ){ $part = $part.$part; }
		}

		return "#" . implode("",$parts);
	}
?>
