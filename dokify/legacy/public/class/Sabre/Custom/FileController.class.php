<?php
	class FileController extends Sabre_DAV_File {
		private $debug;

		private $path;
		protected $name;

		function __construct($path, $name) {
			$this->path = $path;
			$this->name = $name;

			$this->debug = false;
			if($this->debug) file_put_contents("/home/jandres/test/file.txt", "{$this->path} instance as {$this->name} \n", FILE_APPEND );
		}

		function getName(){
			return $this->name;
		}

		function get() {
			if( archivo::is_readable($this->path) ){
				if($this->debug) file_put_contents("/home/jandres/test/file.txt", "DOWNLOAD FROM {$this->path} \n", FILE_APPEND );
				return archivo::leer($this->path);
			}
		}

		function getSize() {
			if( is_readable($this->path) ){
				//if($this->debug) file_put_contents("/home/jandres/test/file.txt", "GET SIZE FROM {$this->path} \n", FILE_APPEND );
				return archivo::filesize($this->path);
			}
		}

		function getETag() {
			if( is_readable($this->path) ){
				//if($this->debug) file_put_contents("/home/jandres/test/file.txt", "GET ETAG FROM {$this->path} \n", FILE_APPEND );
				return '"' . md5($this->path) . '"';
			}
		}

		/* EL GESTOR DE ARCHVOS INTERPRETA MEJOR LAS EXTENSIOENES
		function getContentType(){
			if( is_readable($this->path) ){
				return archivo::getMimeType($this->path);
			}
		}*/

	}
?>
