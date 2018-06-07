<?php
class empresarelacion extends basic
{
  public $empresaInferior;
  public $empresaSuperior;
  public $apta;
  
	public function __construct( $param, $extra = NULL )
	{
	  // @param $param debería ser siempre el uid de la empresa inferior.
	  // comprobar si es uid o si es un objeto empresa?
		$this->tipo = "empresa_relacion";
		$this->tabla = TABLE_EMPRESA.'_relacion';
		$this->instance( $param, false );
		
		$this->empresaInferior = new empresa($param);
		
		if( $extra instanceof usuario ){
			$this->empresaSuperior = $extra->getCompany();
		}
	}    
	
	public function getAptitud()
	{
	  return $this->empresaInferior->esAptaPara($this->empresaSuperior)?1:0;
	}
  
	public function getFormData(){
		$arrayCampos = new FieldList;
	
		  $arrayCampos['apta'] = new FormField( array(
		    'tag' => 'input', 
		    'type' => 'checkbox', 
		    'className'=>'iphone-checkbox', 
		    'value' => $this->getAptitud())
		  );	
		return $arrayCampos;
	}
	
	public function updateWithRequest($data = false, $fieldsMode = false, Iusuario $usuario = NULL){
		$return = null; // no hacemos nada
		$currentValue = $this->getAptitud();
		if( isset($_REQUEST['apta']) ){
			$newValue = db::scape($_REQUEST['apta']);
			if( $newValue != $currentValue ){
				if ( $this->setAptitud($newValue) ) { $return = true; } // cambio ok
				else { $return = false; } // error
			}
		}
		return $return;
	}
  
	public function setAptitud($value){
		return $this->empresaSuperior->guardarAptitud( $this->empresaInferior, $value );
		/*
		$uid_empresa_inferior = $this->empresaInferior->getUID();
		$uid_empresa_superior = $this->empresaSuperior->getUID();  	    
		$sql = '
			UPDATE '. $this->tabla .' SET apta = '. $value .' 
			WHERE uid_empresa_superior = '. $uid_empresa_superior .' 
			AND uid_empresa_inferior = '. $uid_empresa_inferior
		;

		dump($sql);
		$this->db->query($sql);
		return $this->db->getAffectedRows()==1?true:false;
		*/
	}  
}
