<?php

	include_once( DIR_CLASS . "elemento.class.php" );

	//clase elemento
	class system extends elemento implements Ielemento {
		const VIDEO_ALTA_EMPRESA = 'https://dokify.zendesk.com/entries/23018756-como-funciona-el-sistema-de-alta-de-contratas-proveedores';
		
		/* variables accesibles desde la clase elemento
				protected $db;
				protected $uid;
				protected $tabla;
				protected $tipo;

			funciones:
				getUID
				getFields
				obtenerRelacionados
		*/
		public function __construct( $param, $extra = true ){
			$this->tipo = "system";
			$this->tabla = TABLE_SYSTEM;

			$this->instance($param);
		}

		public function getUserVisibleName(){
			$data = $this->getServerData();
			return "AGD @". $data["SERVER_ADDR"];
		}

		/**
			Retorna un parametro o todos de la configuracion
		*/
		public function getSystemStatus(){
			$sql = "SELECT access FROM $this->tabla WHERE uid_system = $this->uid";
			return $this->db->query($sql, 0, 0);
		}


		/**
			Devuelve el texto de aviso para la home si lo hay, si no false
		*/
		public function getAvisoHome(){
			if( !isset($this) ){
				$system = new self(1);
				return $system->getAvisoHome();
			} else {
				$sql = "SELECT aviso_home FROM $this->tabla WHERE uid_system = $this->uid";
				$texto = trim($this->db->query($sql, 0, 0));
				return ( $texto ) ? utf8_encode($texto) : false;
			}
		}

		/** CAMPOS DE LA TABLA USUARIO PARA DIFERENTES VISTAS */
		static public function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$arrayCampos = new FieldList();
				$arrayCampos["access"] 		= new FormField( array("tag" => "input",	"type" => "checkbox", "className" => "iphone-checkbox" ));
				$arrayCampos["aviso_home"] 	= new FormField( array("tag" => "textarea" ));
					

			return $arrayCampos;
		}


		static public function getServerData(){
			$data = array();
			$data["DOKIFY_ENV"] = CURRENT_ENV;
			$data["DOMAIN"] = CURRENT_DOMAIN;
			$data["STATIC_FILES"] = RESOURCES_DOMAIN;

			$data["REDIS"] = (class_exists('Redis') && $redisServer = RedisStorage::getServer()) ? $redisServer : "NO";

			$data["APC"] = (extension_loaded("apc")) ? "YES" : "NO";

			if( $s3 = archivo::getS3() ){
				$data["AMAZON_S3"] = "YES";
			}

			if( $endeveKey = get_cfg_var('endeve.key') ){
				$data["ENDEVE_ENABLED"] = "YES";
			}

			$data["HOST_NAME"] = gethostname();
			$data["SERVER_NAME"] = $_SERVER["SERVER_NAME"];
			$data["SERVER_SOFTWARE"] = $_SERVER["SERVER_SOFTWARE"];
			$data["PHP_API"] = php_sapi_name() . " " . PHP_VERSION;
			$data["SERVER_ADDR"] = $_SERVER["SERVER_ADDR"];
			$data["DOCUMENT_ROOT"] = $_SERVER["DOCUMENT_ROOT"];

			return $data;
		}

		public static function getLanguages($lang = NULL)
		{
			/**
			 * IMPORTANT: The keys should be keeped!
			 * They are being used as ids in a few places in the legacy code
			 *
			 * @var array
			 */
			$languages = [
				0 => 'cl',
				1 => 'en',
				2 => 'es',
				3 => 'fr',
				4 => 'pt',
				5 => 'it',
			];

			if ($lang && isset($languages[$lang])) {
				return $languages[$lang];
			}

			return $languages;
		}

		public static function getIdLanguage($lang = 2){

			$languages = system::getLanguages();

			$idLang = array_search($lang, $languages);
			if ($idLang) return $idLang;
			return $lang;
		}

		public static function getLanguageFromId($id){
			$array = self::getLanguages();
			return ( isset($array[$id]) ) ? $array[$id] : false;
		}

		/*Devuelve el espacio libre o total de disco duro en bytes*/
		public static function getHardDiskSize($freespace=false){
			$dev = '/home';  
			if($freespace===true){
				$space = disk_free_space($dev);  
			}else{
				$space = disk_total_space($dev);  
			}
			return $space;
		}

	}
?>
