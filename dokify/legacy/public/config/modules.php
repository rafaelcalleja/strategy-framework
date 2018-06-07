<?php
		function module2id($string){
			switch(strtolower($string)){
				case "empresa": 							return 1; 	break;
				case "usuario": 							return 2; 	break;
				case "etiqueta": 							return 7; 	break;
				case "empleado": 							return 8; 	break;
				case "agrupador": 							return 11; 	break;
				case "agrupamiento":						return 12; 	break;
				case "maquina": 							return 14; 	break;	
				case "documento_atributo": 					return 5; 	break;	
				case "documento":case "tipodocumento":		return 18; 	break;	
				default:
					dump("Define el modulo $string");exit;
				break;
			}
		}
?>
