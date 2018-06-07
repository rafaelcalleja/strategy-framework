<?php
class clientecss extends basic {
	public $cliente;
  
	public function __construct( $param ){
		$this->tipo = "clientecss";
		$this->tabla = TABLE_CSS;
		
		$this->instance( $param, false );
		$this->cliente = new cliente($param);
	}


	public function styleDataExists(){
		$sql = "SELECT count(*) FROM ". $this->tabla ." WHERE uid_cliente = ". $this->cliente->getUID();
		$num = $this->db->query($sql,0,0);
		return $num;
	}

	public function createStyleData(){
		$sql = "
			INSERT INTO ". $this->tabla ."
			SELECT '', ". $this->cliente->getUID() .", selector, propiedad, valor 
			FROM ". $this->tabla ." WHERE uid_cliente = ( 
				SELECT uid_cliente FROM ". $this->tabla ." LIMIT 1 
			)";
		return $this->db->query($sql);
	}

	public function getFormData(){
		// Utilizaremos este string para reemplazar los nombres de las clases por su correspondiente
		// texto entendible por cualquiera
		$language = "css_";
		$tpl = Plantilla::singleton();
		$estilos = $this->cliente->getStyleData();

		$campos =new FieldList;	
		foreach($estilos as $selector => $data){
			// Quitamos el tipo al selector para luego buscarlo
			$id = substr($selector, 1);
			$campos[$selector] =new FormField (array());


			$actualStyle = "";
			foreach( $data as $propiedad => $valor ){
				$campo = new FormField (array());

				$campo["value"] = $valor;
				$campo["innerHTML"] = $tpl->getString($language.$propiedad);
				$campo["tag"] = "input";
				$campo["rel"] = $propiedad;
				$campo["target"] = "#".$id;
				$campo["className"] = $this->getClassTo($propiedad);
				$campo["type"] = "text";
				$campo["name"] = "css[$selector][$propiedad]";

				//todos los campos
				$campos[$selector.";".$propiedad] = $campo;

				$actualStyle .= $propiedad.":".$valor.";";
			}

			// Mostramos el estado actual
			$campos[$selector]["innerHTML"] = $this->getStyleHtml( $tpl->getString($language.$selector), $actualStyle, $id);
			$campos[$selector]["tag"] = "span";
		}

		return $campos;
	}


	public function updateWithRequest($data = false, $fieldsMode = false, Iusuario $usuario = NULL){
		$css = $_REQUEST["css"];
		if( !is_array($css) ){
			return false;
		}

		if( !$this->styleDataExists() ){
			$this->createStyleData();
		}

		$affected = 0;
		foreach( $css as $selector => $data ){
			$updates = array();
			foreach( $data as $propiedad => $valor ){
				$sql = "
					UPDATE $this->tabla 
					SET valor = '". db::scape($valor) ."' 
					WHERE selector = '$selector'
					AND propiedad = '$propiedad'
					AND uid_cliente = ". $this->cliente->getUID() ."
				";

				if( !$this->db->query($sql) ){
					return false;
				}

				if( $this->db->getAffectedRows() ){
					$affected++;
				}

			}
		}

		if( $affected ){ return true; }
		return null;
	}

	private function getClassTo($propiedad){
		switch( $propiedad ){ 
			case "color": case "background-color":
				return "selectorcolor";
			break;      	  
		}
	}
	private function getStyleHtml($string, $style, $id=""){
		return "<span class='ucase inline-text stat' style='$style' id='$id'>". $string ."</span>";
	}

}
