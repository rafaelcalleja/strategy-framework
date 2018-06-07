<?php
	//clase webempleo
	
	class webempleo implements IbloqueWeb {
		
		const TABLE_EMPLEO = "dokify_web.empleo";
		private $id;
		
		public function __construct($id){
			$this->id=$id;
		}
		
		public function getTitulo($locale='es'){
			return $this->getDato('titulo_'.$locale);
		}
		
		public function getHtml($locale='es'){
			return $this->getDato('html_'.$locale);
		}

		public function getName(){
			return $this->getDato('titulo_name');
		}
		
		public function getDato($dato){
			$sql = "SELECT $dato FROM ".self::TABLE_EMPLEO." WHERE uid_empleo=".$this->id;
			return utf8_encode(db::get($sql,0,0));
		}
		
		public static function getAll(){
			$sql = "SELECT uid_empleo FROM ".self::TABLE_EMPLEO. " WHERE active=1 ORDER BY priority DESC";
			return db::get($sql, '*',0, 'webempleo');
		}
	}
?>
