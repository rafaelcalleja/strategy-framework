<?php
	
	function preparePagination( $numeroRegistrosPorPagina, $totalNumeroRegistros, $restar = 0, $force = false){
		//aqui almacenaremos los datos
		$arrDatosPaginacion = array();

		//numero de pagina actual
		$arrDatosPaginacion["pagina_actual"] = ( isset($_REQUEST["p"]) && is_numeric($_REQUEST["p"]) && !$force) ? $_REQUEST["p"] : 0;

		//limites para pasar a sql
		$arrDatosPaginacion["sql_limit_start"] = $numeroRegistrosPorPagina * $arrDatosPaginacion["pagina_actual"];
		$arrDatosPaginacion["sql_limit_end"] = $numeroRegistrosPorPagina - $restar;
		$arrDatosPaginacion["sql_total"] = $totalNumeroRegistros;
		$arrDatosPaginacion["numeroRegistrosPorPagina"] = $numeroRegistrosPorPagina;
		//Maximo paginas
		if( is_numeric($totalNumeroRegistros) && is_numeric($numeroRegistrosPorPagina) && $numeroRegistrosPorPagina > 0){
			$arrDatosPaginacion["pagina_total"] = ceil($totalNumeroRegistros/$numeroRegistrosPorPagina);
		} else {
			$arrDatosPaginacion["pagina_total"] = 0;
		}

		//pagina para crear links de anterior y siguiente
		$arrDatosPaginacion["pagina_anterior"] = ($arrDatosPaginacion["pagina_actual"]) ? ($arrDatosPaginacion["pagina_actual"]-1) : 0;
		
		$arrDatosPaginacion["pagina_siguiente"] = ( ($arrDatosPaginacion["pagina_actual"]+1)>=$arrDatosPaginacion["pagina_total"] ) ? $arrDatosPaginacion["pagina_actual"] : ($arrDatosPaginacion["pagina_actual"]+1);
			
		// helper for new app
		$arrDatosPaginacion["pagination"] = (object) array(
			'limit' => array($arrDatosPaginacion["sql_limit_start"], $arrDatosPaginacion["sql_limit_end"], $totalNumeroRegistros),
			'pages' => (object) array(
				'current' 	=> (int) $arrDatosPaginacion['pagina_actual'],
				'total' 	=> (int) $arrDatosPaginacion['pagina_total'],
				'next' 		=> (int) $arrDatosPaginacion['pagina_siguiente'],
				'prev' 		=> $arrDatosPaginacion['pagina_anterior'] > 1 ? $arrDatosPaginacion['pagina_anterior'] : 1,
				'rows'	 	=> (int) $arrDatosPaginacion['numeroRegistrosPorPagina']
			),
			'total' => $totalNumeroRegistros
		);

		return $arrDatosPaginacion;
	}
