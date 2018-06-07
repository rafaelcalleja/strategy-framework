<?php
	
	interface IbloqueWeb {
		public static function getAll();
		public function getName();
		public function getTitulo($locale='es');
		public function getHtml($locale='es');
		public function getDato($dato);
	}
?>
