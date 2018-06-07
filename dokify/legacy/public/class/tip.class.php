<?php

	class tip extends elemento { 
		const TABLE = "agd_data.tip"; 
		const DOWNLOAD_ATTACH = 1;
		const DOCUMENT_REJECTED_DATE = 2;

		const CONTEXT_ATTACH = 'attach_window';
		

		public function __construct($uid){
			$this->tipo = "tip";
			$this->tabla = tip::TABLE;
			
			$this->instance($uid);
		}

		public function getString() {
			$tpl = Plantilla::singleton();
			$args = func_get_args();
			$string = $tpl->getString($this->obtenerDato('string'));

			if (count($args)) {
				array_unshift($args, $string);
				$string = call_user_func_array("sprintf", $args);
			} 
			
			return $string;
		}

		public function getHTML() {
			$tpl = Plantilla::singleton();
			$title = $tpl->getString('get_into_tip');
			$html = "<hr />";
			$html .= "<div class=\"tip-message\"> ";			
			$html .= "<img class=\"help\" title=\"{$title}\" src=\"".RESOURCES_DOMAIN."/img/famfam/information.png\"><span>";
			$html .= $tpl->getString('tip');
			$html .= count(func_get_args()) ? call_user_func_array(array("tip", "getString"), func_get_args()) : $this->getString();
			$html .= "</span></div>";
			
			return $html;
		}

		public static function getRandomTip($context = false, $rand = 0.8) {
			$db = db::singleton();
			
			$sql = "SELECT uid_tip FROM ".tip::TABLE." WHERE (SELECT RAND() < {$rand}) ";

			if ($context) $sql .= " AND context = '{$context}' ";

			$sql .= " ORDER BY RAND() LIMIT 1";

			$uid = $db->query($sql, 0, 0);

			if( is_numeric($uid) && $uid ){
				return new tip($uid);
			}

			return false;
		}

	}
?>
