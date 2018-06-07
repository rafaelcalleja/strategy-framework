<?php
	class log {
		protected $db;
		protected $datos;
		protected $tabla;
		static $instance;

		const LOGLEVEL_MEH 			= 1;
		const LOGLEVEL_WAT 			= 2;
		const LOGLEVEL_WTF 			= 3;
		const LOGLEVEL_COBRA 		= 4;
		const LOGLEVEL_OMEGA 		= 5;
		const LOGLEVEL_BLACK_SWAN 	= 6;

		public function __construct($nivel=0, $usuario = false){
			$this->db = db::singleton();
			$this->tabla = TABLE_LOG;
			$this->datos = array();
			$this->datos["nivel"] = $nivel;

			if( !$usuario && isset($_SESSION[SESSION_USUARIO]) ){
				$type = (isset($_SESSION[SESSION_TYPE]) && $sessionType = trim($_SESSION[SESSION_TYPE])) ? $sessionType : 'usuario';
				$usuario = new $type($_SESSION[SESSION_USUARIO]);
			}
			if( isset($usuario) && $usuario instanceof Iusuario && $usuario->getUID() ){
				$this->datos["usuario"] = $usuario->getUserName();
			} else {
				$this->datos["usuario"] = "n/a";
			}

			$this->datos["ip"] = self::getIPAddress();
		}


		public function info($tipo, $descripcion, $alias, $resultado = false, $guardar=false){
			$this->datos["tipo"] = utf8_decode(db::scape($tipo));
			$this->datos["descripcion"] = utf8_decode(db::scape($descripcion));
			$this->datos["alias"] = utf8_decode(db::scape($alias));
			if (CURRENT_ENV == 'dev') {
				//error_log(print_r($this->datos,true));
			}
			if( $resultado ){
				$this->datos["resultado"] = db::scape($resultado);
			}
			if( $guardar ){
				$this->save();
			}
		}

		public function nivel($nivel=0){
			$this->datos["nivel"] = (int)$nivel;
		}

		public function resultado($resultado=0, $guardar=false) {
			if (CURRENT_ENV == 'dev') {
				// error_log(print_r($resultado,true));
			}
			$this->datos["resultado"] = db::scape($resultado);
			if( $guardar ){
				$this->save();
			}
		}

		public function save(){
			if(!@$this->datos["alias"]){
				$this->datos["alias"] = "NA";
			}
			if( isset($this->datos["resultado"]) && $this->datos["tipo"] && $this->datos["descripcion"] ){
				$sql = "INSERT INTO ". $this->tabla ." (tipoelemento, alias, descripcion, resultado, nivel, usuario, ip) VALUES (
					'".$this->datos["tipo"]."', '".$this->datos["alias"]."', '".$this->datos["descripcion"]."',  '".$this->datos["resultado"]."', ".$this->datos["nivel"].", '".$this->datos["usuario"]."', '".$this->datos["ip"]."'
				)";
				$resultset = $this->db->query($sql);
				if( $resultset ){ 
					return true; 
				} else{
					//dump( $this->db->lastErrorString() );
				}
			} else {
				//dump($this->datos);
				die("No se puede guardar el log");
			}
		}

		public static function singleton(){
			if( !isset(self::$instance) ){
				$c = __CLASS__;
				self::$instance = new $c;
			}
			return self::$instance;
		}

		public static function getIPAddress(){
			$vars = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
			foreach($vars as $key){
				if( array_key_exists($key, $_SERVER) === true ){
					foreach( explode(',', $_SERVER[$key]) as $ip ){
						if( filter_var($ip, FILTER_VALIDATE_IP) !== false ) return $ip;
					}
				}
			}
		}
		
	}
?>
