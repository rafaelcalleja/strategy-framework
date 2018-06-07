<?php

	//clase elemento
	class helper {
		private $uid;
		protected $name;
		protected $db;
		protected $ref;

		public function __construct( $name, $ref=false ){
			$this->name = $name;
			$this->ref = $ref;
			$this->db = db::singleton();
			$this->table = TABLE_HELPER;
			$this->uid = $this->db->query("SELECT uid_helper FROM $this->table WHERE helper = '". db::scape($name) ."'", 0, 0);
		}

		public function setComplete(usuario $usuario){
			$sql = "INSERT INTO ". TABLE_USUARIO ."_helper ( uid_usuario, uid_helper, fecha ) VALUES (". $usuario->getUID() .", ". $this->getUID() .", NOW())";
			return $this->db->query($sql);
		}

		public function getOutputHTML(){
			$data = $this->getHelperData();
			$html = "";
			foreach( $data as $step ){
				$html .= $this->getHTMLBlock($step["step"],  nl2br($step["html"]), $step["target"], $step["cancel_target"], $step["cancel_event"], $step["hide"], $step["filter"]  );
			}
			return $html;
		}

		public function getOutputArray(){
			$data = $this->getHelperData();
			foreach( $data as &$step ){
				$step["html"] = nl2br($step["html"]);
			}
			return $data;
		}

		public function getUID(){
			return $this->uid;
		}

		protected function getHTMLBlock($step, $desc, $target, $ctarget, $cevent, $cancel, $filter=false){
			$len = strlen($desc);
			$width = 320;
			$block = '<div class="helper" id="helper-'.$this->getUID().'-'.$step.'" style="width:'.$width.'px; display:none; z-index: 9999;" filter="'.$filter.'" target="'.$target.'" canceltarget="'.$ctarget.'" cancelevent="'.$cevent.'"><table class="helper-table"><tbody><tr><td><img src="'.RESOURCES_DOMAIN.'/img/common/new.png"><div class="helper-text"><div class="helper-title">Ayuda de AGD</div><p>'.$desc.'</p>';
			if( $cancel ){
				$block .= '<div class="helper-cancel"><a class="post toggle" href="helpercancel.php?comefrom='.$this->name.'" target="#helper-'.$this->getUID().'-'.$step.'">No volver a mostrar</a></div>';
			}
			$block .= '</div></td><td style="width: 48px;"><div class="helper-arrow"></div></td></tr></tbody></table></div>';
			return $block;
		}

		protected function getHelperData(){
			$sql = "
				SELECT uid_helper as uid, ( SELECT helper FROM ". DB_CORE .".helper WHERE helper.uid_helper = step.uid_helper) helper, html, target, cancel_event, cancel_target, hide, filter, width
				FROM ". $this->table ."_step step
				WHERE step.uid_helper = ". $this->getUID() ."
				AND step.href = '$this->ref'
			";
			$data = $this->db->query($sql, true);
			$data = utf8_multiple_encode($data);
			return $data;
		}

		public static function getFirstAssignHelper(){
			$lang = Plantilla::singleton();
			// uid_helper as uid, ( SELECT helper FROM ". DB_CORE .".helper WHERE helper.uid_helper = step.uid_helper) helper, html, target, cancel_event, cancel_target, hide, filter, width
			$helper = array(array(
				'uid' => uniqid(),
				'helper' => 'first-assign',
				'html' => $lang('seleccionar_asignar'),
				'target' => '.variable-column-table:visible:first td+td:first',
				'cancel_event' => 'click',
				'cancel_target' => 'button+br+button.list-move',
				'filter' => null,
				'img' => 'info',
				'width' => 600
			));

			return $helper;
		}

		public static function publicFields(){
			return $fieldList = new FieldList;
		}

	}
?>
