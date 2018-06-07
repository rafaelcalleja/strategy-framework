<?php

	function get_parameters_array_format ( $reflectionMethod ) {
		$reflectionParameters = $reflectionMethod->getParameters();
		$parameterClassArray = array();
		foreach ($reflectionParameters as $i => $reflectionParameter) {
			$parameterDefaultValue = $reflectionParameter->getDefaultValue();
			if ($parameterClass = $reflectionParameter->getClass()) {			
				$parameterClassArray[$i]['name'] = $parameterClass->getName();
				$parameterClassArray[$i]['value'] = $parameterDefaultValue; 
			} else {
				$parameterClassArray[$i]['name'] = gettype($reflectionParameter->getDefaultValue()) ;
				$parameterClassArray[$i]['value'] = $parameterDefaultValue; 
			}
		}
		return 	$parameterClassArray;
	}

	function order_parameters(){
		$result = array();
		$debugBacktrace = debug_backtrace();
		$functionParameters = $debugBacktrace[1]['args'];
		$reflectionMethod = new ReflectionMethod( $debugBacktrace[1]['class'],$debugBacktrace[1]['function'] );
		$defaultClassArray = get_parameters_array_format( $reflectionMethod );
		foreach ($defaultClassArray as $index => $value) {
			foreach ($functionParameters as $key => $parameter) {							
				if ( $parameter instanceof $value['name'] || gettype($parameter) == $value['name'] ) {
			 		$result[$index] = $parameter;
			 		unset($functionParameters[$key]);
			 		break;
				}
				$result[$index] = $value['value'];
			} 
		}
		return $result;
	}