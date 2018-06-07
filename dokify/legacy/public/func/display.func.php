<?php
	//MOSTRAR POR PANTALLA UNA VARIABLE
	if( !function_exists("dump") ){
		function dump(){
			$args = func_get_args();
			$traceparts = debug_backtrace();
			//echo "<pre>"; print_r($traceparts); echo "</pre>";exit;
			foreach($args as $var){
				echo "<pre>"; 
				if( is_bool($var) || is_null($var) ){
					ob_start(); var_dump($var); echo trim(ob_get_clean()); echo "</pre>";
				} else {
					print_r($var); 
				}
				echo "</pre>";
			}
			echo "<pre>\n". $traceparts[0]['file'].":".$traceparts[0]['line'] ."</pre>";
		}
	}

	function trace($return=false){
		//ob_start();
		//debug_print_backtrace();
		//$trace = ob_get_clean();
		//$trace = str_replace( array("("), array("(\n\t"), $trace);
		$traceparts = debug_backtrace();
		$parts = array();
		if(!$return) echo "<pre>";
			foreach( $traceparts as $i => $part ){
				$ln = str_replace(DIR_ROOT,"",@$part["file"]).":".@$part["line"].":".@$part["function"];
				if(!$return) print $ln ."\n";
				else $parts[] = trim($ln);
			}
		if(!$return) echo "</pre>";
		if($return) return $parts;
	}


	function dumptrace(){
		//ob_start();
		//debug_print_backtrace();
		//$trace = ob_get_clean();
		//$trace = str_replace( array("("), array("(\n\t"), $trace);
		$traceparts = debug_backtrace();
		echo "<pre>"; dump($traceparts); echo "</pre>";
	}