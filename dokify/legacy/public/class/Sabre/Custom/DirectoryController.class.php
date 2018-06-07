<?php

	class DirectoryController extends Sabre_DAV_Collection {

		private static $user;

		protected $path;
		protected $auth;
		protected $node;
		//protected $parentNode;


		const CARPETA_EMPRESA = "Mi empresa";
		const CARPETA_EMPLEADOS = "Mis empleados";
		const CARPETA_MAQUINAS = "Mis maquinas";


		function __construct($path = "", AuthController $auth = NULL, $node = NULL/*, $parentNode = NULL*/) {
			$this->path = $path;
			$this->auth = $auth;
			$this->node = $node;
			//$this->parentNode = $parentNode;

			//file_put_contents("/home/jandres/test/file.txt", "INSTACE: ". print_r($this, true) ."\n", FILE_APPEND );
		}


		public static function getUser(AuthController $auth){
			if( !isset(self::$user) ){
				$username = $auth->getCurrentUser();
				$usuario = usuario::instanceFromUserName($username);

				if( !$usuario ){
					ob_start(); trace(); $t = ob_get_clean();
					return false;
					//file_put_contents("/home/jandres/test/file.txt", "NO USER: $t\n", FILE_APPEND );
				}

				self::$user = $usuario;
			}
			return self::$user;
		}

		function getChildren($name=false) {
			$children = array();
			$parts = explode("/", $this->path);

			if( $this->path ){			
				$usuario = self::getUser($this->auth);
				$empresa = $usuario->getCompany();

				/********************************
				 *********** MI EMPRESA **********
				 ********************************/
				if( reset($parts) == DirectoryController::CARPETA_EMPRESA ){
					$docs = $empresa->getDocuments(0);

					//file_put_contents("/home/jandres/test/file.txt", "SEARCH IN PATH: {$this->path} (". count($docs) ." docs found)\n", FILE_APPEND );
					// Mostrar las carpetas de los documentos
					if( count($parts) == 1 ){
						foreach($docs as $i => $doc){
							$docname = trim(utf8_decode(archivo::cleanFileNameString($doc->getUserVisibleName())));
							$path = DirectoryController::CARPETA_EMPRESA . "/" . $docname;
							$node = new DirectoryController($path, $this->auth, $doc);

							//file_put_contents("/home/jandres/test/file.txt", "BUSCANDO $name [". strlen($name) ."] === $docname [". strlen($docname) ."] (".($name===$docname).")\n", FILE_APPEND );
							if( $name && $name === $docname ){
								return $node;
							}

							$children[] = $node;
						 }

					// Mostrar los ficheros
					} elseif( count($parts) == 2 ){
						$solicitantes = $this->node->obtenerSolicitantes($usuario);
						foreach( $solicitantes as $i => $solicitante ){
							//$info = $doc->informacionArchivo($solicitante, true);
							$info = $this->node->downloadFile($solicitante, false, true );

							$filename = utf8_encode($info["alias"]).".".$info["ext"];
							$filepath = DIR_FILES . "{$info["path"]}";

							if( !archivo::is_readable($filepath) ) continue;
							$node = new FileController($filepath, $filename);

							if( $name && $name == $filename ) return $node;
							$children[] = $node;
						}
					}
				}


				/************************************
				 *********** MIS EMPLEADOS **********
				 ************************************/
				if( reset($parts) == DirectoryController::CARPETA_EMPLEADOS || reset($parts) == DirectoryController::CARPETA_MAQUINAS ){
					$PARENT = reset($parts);
					$empleados = ( $PARENT == DirectoryController::CARPETA_EMPLEADOS ) ? $empresa->obtenerEmpleados() : $empresa->obtenerMaquinas();
					$dato = ( $PARENT == DirectoryController::CARPETA_EMPLEADOS ) ? "dni" : "serie";


					// Listamos los empleados
					if( count($parts) == 1 ){
						foreach( $empleados as  $empleado ){
							$dni = strtoupper(str_replace(array(" ","-","."), "", $empleado->obtenerDato($dato)));
							$nombreempleado = archivo::cleanFileNameString($dni ." - " . ucfirst(strtolower($empleado->getUserVisibleName())));
							$path = $PARENT . "/". $nombreempleado;


							$node = new DirectoryController($path, $this->auth, $empleado);
							if( $name && $name == $nombreempleado ) return $node;
							$children[] = $node;
						}

					// Listamos los documentos como carpetas
					} elseif( count($parts) == 2 ){
						$empleado = $this->node;
						$docs = $empleado->getDocuments(0);
						foreach($docs as $i => $doc){
							
							$dni = strtoupper(str_replace(array(" ","-","."), "", $empleado->obtenerDato($dato)));
							$nombreempleado = archivo::cleanFileNameString($dni ." - " . ucfirst(strtolower($empleado->getUserVisibleName())));
							$docname = trim(utf8_encode(archivo::cleanFileNameString($doc->getUserVisibleName())));
							$path = $PARENT . "/". $nombreempleado . "/" . $docname;

							$node = new DirectoryController($path, $this->auth, $doc);

							if( $name && $name == $docname ) return $node;
							$children[] = $node;
						}

					// Listamos los ficheros decada documento
					} elseif( count($parts) == 3 ) {
						$solicitantes = $this->node->obtenerSolicitantes($usuario);
						foreach( $solicitantes as $i => $solicitante ){
							//$info = $doc->informacionArchivo($solicitante, true);
							$info = $this->node->downloadFile($solicitante, false, true );

							$filename = utf8_encode($info["alias"]).".".$info["ext"];
							$filepath = DIR_FILES . "{$info["path"]}";

							if( !archivo::is_readable($filepath) ) continue;
							$node = new FileController($filepath, $filename);

							if( $name && $name == $filename ) return $node;
							$children[] = $node;
						}
					}
				}

			} else {
				$node = new DirectoryController(DirectoryController::CARPETA_EMPRESA, $this->auth);
				if( $name && $name == DirectoryController::CARPETA_EMPRESA ) return $node;
				$children[] = $node;

				$node = new DirectoryController(self::CARPETA_EMPLEADOS, $this->auth);
				if( $name && $name == DirectoryController::CARPETA_EMPLEADOS ) return $node;
				$children[] = $node;

				$node = new DirectoryController(self::CARPETA_MAQUINAS, $this->auth);
				if( $name && $name == DirectoryController::CARPETA_MAQUINAS ) return $node;
				$children[] = $node;
			}

			if($name) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');

			return $children;
		}

		function getChild($name) {
			return $this->getChildren($name);
		}

		function childExists($name) {
			return ( $this->getChildren($name) ) ? true : false;
		}

		function getName() {
			return $this->path;
		}

	}
?>
