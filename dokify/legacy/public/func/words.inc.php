<?php

	function isRelevantWord($string){
		if( !trim($string) ) return false;

		$blacklist = array("y", "de", "en", "a", "para", "el", "la", "los", "las", "the", "in");
		if( in_array(strtolower($string), $blacklist) ) return false;

		if( strtotime($string) || strtotime(str_replace("/","-",$string)) ){ return false; }

		return true;
	}

	function getSimilarWords($string){
		$similars = array();

		$string = strtolower($string);
		$lastChar = $string[strlen($string)-1];
		if( $lastChar == "s" ){
			$similars[] = substr($string, 0, strlen($string)-1);
		}

		if( in_array($lastChar, array("a", "o")) ){
			$similars[] = $string."s";
		}

		if( strpos($string, "ft") === false ){
			if( in_array(substr($string, -2), array("ar", "er", "ir")) ){
				$similars[] = substr($string, 0, strlen($string)-1) . "cion";
			}

			if( str_replace(utf8_decode("ó"),"o", substr(utf8_decode($string), -4)) == "cion" ){
				$similars[] = substr(utf8_decode($string), 0, strlen(utf8_decode($string))-4) . "r";
			}
		}

		if( substr($string, -3) === "ect" ){
			$similars[] = $string . "ion";
		}


		// Palabras unidas por -
		$parts = explode("-", $string);
		if( count($parts) > 1 ){
			foreach($parts as $part){
				if( strlen($part) > 2 ){
					$similars[] = $part;

					$similars = array_merge($similars, getSimilarWords($part));
				}
			}
		}


		return array_unique($similars);
	}

?>
