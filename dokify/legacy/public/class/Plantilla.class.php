<?php
require_once DIR_SMARTY . "Smarty.class.php";

class Plantilla extends Smarty { 
	public $language;
	public $strings;
	public $folder;
	private static $instance;
	public $flags = array();

	const PORTUGAL_LANGUAGE = 'pt';
	const DEFAULT_LANGUAGE = 'es';

	const NEW_COMMENT = "newComment";
	const UPLOAD = "upload";
	const HABILITACION = "habilitacion";
	
	public function __construct($param=false){
		parent::__construct();
		$this->folder = ( isset($param)&&$param&&is_dir($param) ) ? $param : DIR_TEMPLATES;
	}

	public static function getCurrentLocale(){
		$language = getCurrentLanguage();
		$map = getLocaleMap();

		@list($language, $country) = explode("_", strtolower($language));

		// si tenemos un fichero de idioma para un pais especifico
		if (@$map[$country]) {
			return $country;
		}

		// si no, devolvemos el idioma
		return $language;
	}

	public static function singleton($param=false){
		if( !isset(self::$instance) ){
			$c = __CLASS__;
			self::$instance = new $c($param);
		}
		return self::$instance;
	}

	function end(usuario $usuario){
		if( $usuario->esStaff() ){
			$this->display("sin_acceso_perfil.tpl");
		} else {
			die("Inaccesible");
		}
	}

	function display($resource_name, $cache_id = null, $compile_id = null){
		$idioma = (($lang = $this->get_template_vars("lang")) && is_string($lang)) ? $lang : self::getCurrentLocale();

		if (!$lang = Plantilla::getLanguage($idioma )) {
			if (CURRENT_ENV == 'dev') error_log("No se encuentra el idioma seleccionado {$idioma}");

			// mostrar otro idioma es mejor que enviar un mensaje de error
			$lang = Plantilla::getLanguage(Plantilla::DEFAULT_LANGUAGE);
		}

		$this->strings = $lang;

		//$this->compile_dir = DIR_TEMPLATES . "templates_c";
		$this->compile_dir = '/tmp/cache';
		if( !is_dir($this->compile_dir) ){
			if( !mkdir($this->compile_dir) ){
				die("No puedo crear la carpeta ". $this->compile_dir .", por favor crea esta carpeta manualmente o cambia los permisos a ". dirname($this->compile_dir));
			} else {
				chmod($this->compile_dir, 0777);
			}
		}

		$fecha = array( "dd" => date("d"), "mm" => date("m"), "yyyy" => date("Y") );
		$this->assign("fecha", $fecha );
		$this->assign("ie", is_ie() );
		$this->assign("ie6", is_ie6() );
		$this->assign("locale", $idioma);
		$this->assign("time", time() );
		$this->assign("lang", $lang );
		$this->assign("tpldir", $this->folder );
		$this->assign("errorpath", DIR_TEMPLATES . "/error_string.tpl");
		$this->assign("succespath", DIR_TEMPLATES . "/succes_string.tpl");
		$this->assign("infopath", DIR_TEMPLATES . "/info_string.tpl");
		$this->assign("alertpath", DIR_TEMPLATES . "/alert_string.tpl");
		$this->assign("resources", RESOURCES_DOMAIN );
		$this->assign("assets", WEBCDN);
		$this->assign("touch_device", is_touch_device());
		$this->assign("mobile_device", is_mobile_device());

		if( isset($_SESSION[SESSION_USUARIO]) && !$this->get_template_vars("user") && $sessionType = @$_SESSION[SESSION_TYPE] ){
			$sessionType = trim($sessionType) ? $sessionType : 'usuario';
			$user = new $sessionType($_SESSION[SESSION_USUARIO]);
			$this->assign("user", $user);
			if ($user instanceof Iusuario) $this->assign("timezone", $user->getTimezoneOffset());
		}

		if( isset($_SESSION[SESSION_USUARIO_SIMULADOR]) && !$this->get_template_vars("realuser") ){
			$this->assign("realuser", new usuario($_SESSION[SESSION_USUARIO_SIMULADOR]) );
		}

		// Montar la paginacion
		parse_str( $_SERVER["QUERY_STRING"], $currentParams  );
		unset( $currentParams["p"] );
		unset( $currentParams["send"] );
		unset( $currentParams["type"] );
		$firstParams = $previousParams = $nextParams = $currentParams;


		$currentPage = $_SERVER["PHP_SELF"];
		$paginationArray = array();

		// si se pagina dejamos indices de paginas y paginas completas disponibles en plantillas
		if( isset($_REQUEST["p"]) && is_numeric($_REQUEST["p"]) ){
			$page = $_REQUEST["p"];

			// indice de la anterior página y href
			if( $page == 1 || $page == 0 ){
				$paginationArray["previous"] = 0;
			} else {
				$paginationArray["previous"] = ($page-1);
			}
			$previousParams["p"] = $paginationArray["previous"];
			$paginationArray["href"]["previous"] = $currentPage . "?" . http_build_query($previousParams);
			

			// la pagina actual es la misma
			$currentParams["p"] = $page;
			$paginationArray["current"] = $page;
			$paginationArray["href"]["current"] = $currentPage . "?" .  http_build_query($currentParams);


			// indice de la siguiente página y href
			$paginationArray["next"] = ($page+1);
			$nextParams["p"] = $paginationArray["next"];
			$paginationArray["href"]["next"] = $currentPage . "?" .  http_build_query($nextParams);


			$firstParams["p"] = 0;
			$paginationArray["href"]["first"] = $currentPage . "?" . http_build_query($firstParams);

		} else {


			$paginationArray["href"] = array(
				"previous" => $_SERVER["PHP_SELF"] . "?" .  http_build_query($nextParams),
				"next" => $_SERVER["PHP_SELF"] . "?" .  http_build_query($nextParams),
				"current" => $_SERVER["PHP_SELF"] . "?" .  http_build_query($nextParams),
				"first" => $_SERVER["PHP_SELF"] . "?" .  http_build_query($nextParams)
			);
			$paginationArray["previous"] = 0;
			$paginationArray["next"] = 0;
			$paginationArray["current"] = 0;
		}

		$this->assign("pagination", $paginationArray );




		//acceso a configuracion global
		$this->assign("system", new system(1) );


		/*if (CURRENT_ENV != "dev") {
			if (!in_array('ob_gzhandler', ob_list_handlers())) ob_start('ob_gzhandler');
		}*/

		parent::display( $this->folder . $resource_name, $cache_id, $compile_id );

		if( isset($user) && $user instanceof usuario ){
			$helpers = $user->getHelpers($_SERVER["PHP_SELF"] );
			if( count($helpers) ){
				foreach($helpers as $helper ){
					print $helper->getOutputHTML();
				}
			}
		}
		
		if( count($this->flags) ){
			echo implode("", $this->flags);
		}

		//if (CURRENT_ENV != "dev") ob_flush();
	}


	function getString($string, $idioma = null) {
		if( !isset($this->strings) || $idioma !== null ){
			if( $idioma === null ){
				$idioma = self::getCurrentLocale();
			}
			$this->strings = Plantilla::getLanguage($idioma);
		}
		//if( !isset($this->strings[ $string ]) ){ dumptrace(); }
		if (isset($this->strings[$string])) {
			return $this->strings[$string];
		} elseif(isset($this->strings[utf8_encode($string)])) {
			return $this->strings[utf8_encode($string)];
		}

		return $string;
	}


	function __invoke($string, $idioma = null){
		return $this->getString($string, $idioma);
	}


	function sendFlag($flag, array $data){
		$string = "<div id='{$flag}'";
		if( count($data) ){
			foreach($data as $key => $val) $string .= " data-{$key}='{$val}'";
		}
		$string .= "></div>";
		$this->flags[] = $string;
	}


	function parseHTML($html){
		$tmp = "/tmp/templ.". uniqid() . ".tpl";

		$idioma = ( ($lang = $this->get_template_vars("lang")) && is_string($lang) ) ? $lang : self::getCurrentLocale();
		if( !$lang = Plantilla::getLanguage($idioma ) ){
			error_log("No se encuentra el idioma seleccionado $idioma");
			$lang = Plantilla::getLanguage(Plantilla::DEFAULT_LANGUAGE);
		}

		//$this->compile_dir = DIR_TEMPLATES . "/templates_c";
		$this->compile_dir = '/tmp/cache';

		if( isset($_SESSION[SESSION_USUARIO]) && !$this->get_template_vars("user") && isset($_SESSION[SESSION_TYPE]) && ($sessionType = $_SESSION[SESSION_TYPE]) ){
			$this->assign("user", new $sessionType($_SESSION[SESSION_USUARIO]) );
		}

		$this->assign("system", new system(1) );
		$this->assign("lang", $lang );

		if( archivo::escribir($tmp, $html) ){
			return parent::fetch($tmp);
		}

		return false;
	}

	function getHTML($resource_name, $cache_id = null, $compile_id = null){
		$idioma = ( ($lang = $this->get_template_vars("lang")) && is_string($lang) ) ? $lang : self::getCurrentLocale();
		if( !$lang = Plantilla::getLanguage($idioma ) ){
			error_log("No se encuentra el idioma seleccionado $idioma");
			$lang = Plantilla::getLanguage(Plantilla::DEFAULT_LANGUAGE);
		}

		//$this->compile_dir = DIR_TEMPLATES . "/templates_c";
		$this->compile_dir = '/tmp/cache';

		if( isset($_SESSION[SESSION_USUARIO]) && !$this->get_template_vars("user") && isset($_SESSION[SESSION_TYPE]) && ($sessionType = $_SESSION[SESSION_TYPE]) ){
			$user = new $sessionType($_SESSION[SESSION_USUARIO]);
			$this->assign("user", $user);
			if ($user instanceof Iusuario) $this->assign("timezone", $user->getTimezoneOffset());
		}
		
		$this->assign("tpldir", $this->folder );
		$this->assign("system", new system(1) );
		$this->assign("lang", $lang );
		return parent::fetch( $this->folder . $resource_name, $cache_id, $compile_id, false );
	}


	public static function getLanguage( $lang = "es" ){
		//el array que contendra todas las cadenas de texto
		$strings = array();

		//el archivo de idioma
		$langFile = DIR_LANG . $lang . ".json";

		//si no se puede leer retorno false
		if( !is_readable($langFile) ){
			return false;
		}

		//el contenido del archivo de idioma
		$data = archivo::leer( $langFile );

		$encoding = mb_detect_encoding($data, mb_detect_order(), true);

		if( $encoding != "UTF-8" ){
			//echo "La codificacion es $encoding\n\n";
			$data = utf8_encode($data);
		}

		$data = str_replace("\n","",$data);

		if( !($strings = json_decode($data, true)) ){
			if( isset($_GET["debug"]) ) { self::debugJSON($data); }
			return false;
		}

		return $strings;
	}

	public static function debugJSON($string){
		header("Content-type: text/html; charset:iso-8859-1");
		switch(json_last_error()){
			case JSON_ERROR_DEPTH:
				echo 'JSON: Maximum stack depth exceeded';
			break;
			case JSON_ERROR_CTRL_CHAR:
				echo 'JSON: Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				echo 'JSON: Syntax error, malformed JSON';

				$aux = trim(str_replace(array("{","}"), "", $string));
				$parts = explode("\",", $aux);
				
				if( count($parts) > 1 ){
					foreach( $parts as $i => $minjson ){
						$auxstring = "{".$minjson."\"}";
						$check = json_decode($auxstring, true);
						if( $check === null ){
							dump("Error de sintaxis en $auxstring en la linea ".($i+2));
							exit;
						}
					}
				} else {
					dump($parts);
				}
				
			break;
			case JSON_ERROR_NONE:
				//echo 'JSON: No errors';
			break;
		}
		exit;
	}
}
?>
