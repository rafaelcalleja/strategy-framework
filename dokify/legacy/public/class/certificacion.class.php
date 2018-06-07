<?php
	class certificacion extends elemento {
		const ESTADO_PENDIENTE = 0;
		const ESTADO_VALIDADO = 1;
		const ESTADO_ANULADO = 2;

		public function __construct($param, $user=false){
			$this->tipo = "certificacion";
			$this->tabla = TABLE_CERTIFICACION;
			//$this->reference = ( is_numeric($reference) ) ? $reference : self::getModuleId($reference);

			$this->instance( $param, $user );
		}

			
		public function getInlineArray($usuario){
			$inline = array();

			$empresa = $this->getCompany();

				
			/* SUMA DE COSTE DE CONCEPTOS */
			$total = 0;
			$conceptos = $this->obtenerConceptosAsignados();
			foreach($conceptos as $agrupador){
				$unidades = $this->getUnits($agrupador);

				$parametro = reset( $empresa->obtenerParametrosDeRelacion($agrupador) );
				if( $parametro instanceof elementorelacion ){
					$coste = $parametro->obtenerDato("precio_unitario"); // -- Elementorelacion sustituye si existe
				} else {
					$coste = $agrupador->obtenerDato("precio_unitario");
				}

				$total += ( $unidades * $coste );
			}

			$precio = array( "img" => RESOURCES_DOMAIN . "/img/famfam/money.png" );
				$precio[] = array(
					"nombre" => $total ." €"
				);
			$inline["0"] = $precio;

			return $inline;
		}

		public static function getSearchData(Iusuario $usuario, $papelera = false){
			if (!$usuario->accesoModulo(__CLASS__)) return false;

			$limit = "uid_empresa IN (<%companies%>)";

			$lugarCertificacion = "( SELECT nombre FROM ". TABLE_AGRUPADOR . " a WHERE a.uid_agrupador = certificacion.uid_agrupador ) ";
			$searchData[ TABLE_CERTIFICACION ] = array(
				"type" => "certificacion",
				"fields" => array($lugarCertificacion),
				"limit" => $limit,
				"accept" => array(
					"tipo" => "certificacion",
					"uid" => true,
					"estado" => true
				)
			);

			return $searchData;
		}

		public function actualizarConceptos($list){
			$sql = "DELETE FROM ". $this->tabla ."_agrupador WHERE uid_certificacion = $this->uid";
			if( $this->db->query($sql) ){
				if( count($list) ){
					$inserts = array();
					foreach( $list as $uid ){
						$valor = 0;
						if( isset($_POST["valor"]) && isset($_POST["valor"][$uid]) ){
							$valor = (int) $_POST["valor"][$uid];
						}
						$inserts[] = "( $this->uid, $uid, $valor)";
					}

					$sql = "INSERT INTO ". $this->tabla ."_agrupador (uid_certificacion, uid_agrupador, valor) VALUES " . implode(",", $inserts);
					if( $this->db->query($sql) ){
						return true;
					}
					
				}
			}

			return $this->db->lastError();
		}

		public function getUnits($agrupador){
			$sql = "SELECT valor FROM ". $this->tabla ."_agrupador WHERE uid_certificacion = $this->uid AND uid_agrupador = " . $agrupador->getUID();
			return $this->db->query($sql, 0, 0);
		}

		public function obtenerConceptosAsignados(){
			$tabla = $this->tabla ."_agrupador INNER JOIN ". TABLE_AGRUPADOR ." a USING(uid_agrupador)";
			$arrayRelaciones = $this->obtenerRelacionados( $tabla, "uid_certificacion", "uid_agrupador", false, "ambito");
			$agrupadores = new ArrayObjectList();
			if( is_array($arrayRelaciones) && count($arrayRelaciones) ){
				foreach( $arrayRelaciones as $datosRelacion ){
					$agrupadores[] = new agrupador($datosRelacion["uid_agrupador"]);
				}
			}

			return $agrupadores;
		}

		// Para compatibilidad
		public function getUserVisibleName(){
			$empresa = $this->getCompany();
			//$usuario = $this->getUser();
			$date = $this->getDate();
			return $date . " - " . $empresa->getUserVisibleName();
		}


		public function getLineClass($parent, $usuario){
			$class = false;
			switch( $this->obtenerDato("estado") ){
				case self::ESTADO_VALIDADO:
					$class = "color green";
				break;
				case self::ESTADO_ANULADO:
					$class = "color red";
				break;
			}
			return $class;
		}

		public function getInfo ($publicMode = false, $comeFrom = NULL, Iusuario $usuario = NULL, $extra = array(), $force = false) {
			$data = parent::getInfo($publicMode, $comeFrom, $force);

			// Hack para mostrar en la tabla el nombre del
			// proyecto referencia
			if( $publicMode == true && $comeFrom == "table" ){
				$line =& $data[$this->getUID()];

				$uid = $line["uid_agrupador"];
				$referencia = new agrupador($uid);
				$line["uid_agrupador"] = array(
					"href" => $referencia->obtenerUrlFicha(),
					"innerHTML" => $referencia->getUserVisibleName(),
					"className" => "box-it link"
				);
		
				$line["fecha"] = $line["year"] . "/" . $line["month"];

				unset($line["year"]);
				unset($line["month"]);


				$data["className"] = $this->getLineClass($this->getCompany(), $usuario);
			}



			return $data;
		}

		public function obtenerReferencia(){
			return new agrupador( $this->obtenerDato("uid_agrupador") );
		}

		public function getCompany(){
			return new empresa( $this->obtenerDato("uid_empresa") );
		}

		public function getUser(){
			return new usuario( $this->obtenerDato("uid_usuario") );
		}

		public function getTime(){
			return strtotime($this->obtenerDato("fecha"));
		}

		public function getDate(){
			$year = $this->obtenerDato("year");
			$month = $this->obtenerDato("month");

			if( strlen($month) == 1 ){ $month = 0 . $month; }			

			return $year . "/" . $month;
			//return date( $this->obtenerDato("fecha"), "d/m/Y" );
		}

		static public function obtenerAgrupadoresReferencia($usuario, $objeto){
			$agrupadores = array();

			if( !$objeto || !$usuario ) return $agrupadores;
			$agrupamientos = self::obtenerAgrupamientosReferencia($usuario);
			$agrupadores = $objeto->obtenerAgrupadores(null, $usuario, $agrupamientos);

			return $agrupadores;
		}
	

		/**
			DEVOLVER TODOS LOS AGRUPAMIENTOS QUE SE PUEDEN "ASIGNAR" EN EL MODULO DE CERTIFICACIONES
		**/
		static public function obtenerAgrupamientosReferencia($usuario){
			return config::obtenerAgrupamientos( "35", $usuario, false, false, $usuario );
		}

		static public function obtenerConceptosPago($usuario, $order="nombre"){
			$agrupadores = new ArrayObjectList();

			$agrupamientos = config::obtenerAgrupamientos( false, $usuario, false, false, $usuario, "pago" );
			foreach($agrupamientos as $agrupamiento ){
				$agrupadores = $agrupadores->merge( $agrupamiento->obtenerAgrupadores($usuario, false, false, false, false, $order) );
			}

			return $agrupadores;
		}

		static public function string2status($string){
			$string = strtolower($string);
			$estados = array_map("strtolower", self::obtenerEstados());
			if( in_array($string, $estados) ){
				$trans = array_flip($estados);
				return $trans[$string];
			}
			return "0";
		}

		static public function obtenerEstados(){
			$lang = Plantilla::singleton();
			$estados = array( self::ESTADO_VALIDADO => $lang->getString("validado"), self::ESTADO_ANULADO => $lang->getString("anulado"));
			return $estados;
		}

		static public function publicFields($modo, $objeto, $usuario){
			$arrayCampos = new FieldList();

			if( $modo == "nuevo" || $modo == "init" ){
				$years = array(2011,2012,2013,2014,2015,2016); $years = array_combine($years,$years);
				$months = array(1,2,3,4,5,6,7,8,9,10,11,12); $months = array_combine($months,$months);

				$data = self::obtenerAgrupadoresReferencia($usuario, $objeto);
				if( $data instanceof ArrayObjectList ){
					$data = $data->getArrayCopy();
				}

				$arrayCampos["uid_agrupador"] 	= 	new FormField( array( "tag" => "select", "data" => $data, "innerHTML" => "Elemento para certificación", "search" => true ));
				$arrayCampos["month"] 			=	new FormField( array("tag" => "select", "data" => $months, "innerHTML" => "mes" ));
				$arrayCampos["year"] 			=  	new FormField( array("tag" => "select", "data" => $years , "innerHTML" => "año" ));
			}

			switch( $modo ){
				case "nuevo":					
					$arrayCampos["uid_empresa"] =  new FormField( array( "tag" => "select" ));
				break;
				case "table":
						$arrayCampos["uid_agrupador"] 	=  new FormField( array( "tag" => "select"));
						$arrayCampos["month"] 			=  new FormField( array());
						$arrayCampos["year"] 			=  new FormField( array());
				break;

				case "simple":
				case "edit":
				break;

				case "estado":
						$arrayCampos["estado"] =  new FormField( array( "tag" => "select", "data" => self::obtenerEstados($usuario)));
				break;
				case "buscador":
						$arrayCampos["uid_empresa"] =  new FormField( array( "tag" => "select" ));
						$arrayCampos["estado"] 		=  new FormField( array( "tag" => "select", "data" => self::obtenerEstados($usuario)));
				break;
				default:
					
				break;
			}

			return $arrayCampos;

		}


	}
?>
