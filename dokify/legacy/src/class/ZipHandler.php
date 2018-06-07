<?php

	class ZipHandler {

		private $basedir;
		private $dir;
		private $file;
		private $filename;


		const BUFFER_SIZE = 8192;
		const PRIORITY = 'ionice -c 3 nice -n 19';

		public function __construct ($file) {
			
			$this->basedir = dirname($file);
			$this->filename = basename($file);
			$this->file = $file;

			$zipname = str_replace('.zip', '', $this->filename);
			$this->dir = $this->basedir . '/' . $zipname;

			if (is_writable($this->basedir) && mkdir($this->dir)) {
				// -- ok!
			} else {
				throw new InvalidArgumentException("Folder {$basedir} is not writeable", 1);
			}
		}

		public function addEmptyDir ($dir) {
			$newdir = $this->dir . '/' . $dir;
			if (is_dir($newdir)) return true;

			return mkdir($newdir, 0777, true);
		}

		public function addFromString ($path, $data) {
			$dirname = dirname($path);
			$dir = $this->dir . $dirname;

			if (!is_dir($dir)) {
				if (!$this->addEmptyDir(substr($dirname, 1))) {
					return false;
				}
			}

			$file = $this->dir . $path;
			return file_put_contents($file, $data);
		}

		public function sendToBrowser ($filename = "file.zip") {
			header("Cache-Control: private");
			header("Pragma: cache");
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="'. $filename .'"');

			$cmd = 'cd '. $this->dir .' && '. self::PRIORITY .' zip -9 -m -r - *';


			$fp = popen($cmd, 'r');
			
			$buffer = '';
			while (!feof($fp)) {
			   $buffer = fread($fp, self::BUFFER_SIZE);
			   print $buffer;
			}

			pclose($fp);
		}

	}