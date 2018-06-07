<?php
	include( "../../../api.php");

	$template = Plantilla::singleton();
	//$template = new Plantilla();
	if( $usuario->esStaff() ){
		if( isset($_REQUEST["send"] )){
			$data = $_REQUEST;
			//uid_hilo controla si depende de otra llamada
			//$data["uid_hilo"] = llamada::getMaxHilo();
			//unimos la fecha con la hora
			$hora = isset($data["hora_llamada_sati"]) ? $data["hora_llamada_sati"] : '00';
			$fecha = isset($data["fecha_llamada_sati"]) ? $data["fecha_llamada_sati"] : date('d/m/Y');
			$strDate = str_replace("/","-",$fecha)." ".$hora.":00";
			$data["fecha_llamada_sati"] = date("Y-m-d H:i:s",strtotime($strDate));

			if( isset($data["poid"]) ) {
				$llamadaPadre = new llamada($data["poid"]);
				$data["uid_hilo"] = $llamadaPadre->obtenerHilo();
			} else {
				$data["uid_hilo"] = 0;				
			}
			
			
			$data["uid_usuario_sati"] = $usuario->getUID(); 
			if( $usuarioAtendido = usuario::instanceFromUsername(@$data["uid_usuario_atendido"])) {
				$data["uid_usuario_atendido"] = $usuarioAtendido->getUID();
				$data['uid_empresa'] = $usuarioAtendido->getCompany()->getUID();
				$llamada = new llamada($data, $usuario);
				if($llamada->getUID()) {
					$template->display ("succes_form.tpl");
					exit;
				}
				else {
					$template->assign ("error", "Error al crear" );
				}

			}
			else {
				if(@$_POST['intro_cod_llamada']==null)
					$template->assign ("error", "El usuario especificado no existe" );			
			}
		}
		
		
		
		//si se envia parametro action hay que pedir código, sino no. 
		if(@$_GET['action']!='sincodigo' && (@$_GET['action']=='codigo' || @$_POST['intro_cod_llamada']==null || (@$_POST['intro_cod_llamada']!=null && !codigollamada::existe(@$_POST['intro_cod_llamada'])))){
			$template->assign ("titulo", "verificar_codigo_llamada" );
			$template->assign ("campos", codigollamada::publicFields("simple") );
			
			$arraybotones = array(
					array('innerHTML'=>$template('verificar'), 'type'=>'submit'), 
					array('innerHTML'=>$template('seguir_sin_código'), 'className'=>'colum-it post', 'href'=>"configurar/llamada/nuevo.php?type=sidebar&action=sincodigo")
			); 
			if(@$_POST['intro_cod_llamada']!=null && !codigollamada::existe($_POST['intro_cod_llamada'])){
				$template->assign ("error", "El código especificado no existe" );
			}
			if(@$_POST['intro_cod_llamada']==null && @$_GET['action']!='codigo'){
				$template->assign ("error", "Tienes que especificar un código");
			}
		}
		
		else{//tenemos codigo o decidimos seguir sin el
			$campos = llamada::publicFields("simple", NULL, $usuario);
			//este if entra si tenemos el codigo,o lo hemos tenido, lo va guardando, por si da error el formu volver a mostrar los datos del user correcto
			if((@$_GET['action']!='sincodigo' && @$_POST['intro_cod_llamada']!=null) || (@$_POST['uid_usuario_atendido']!=null && @$_POST['Codigo']!=null)){  //si tenemos el codigo
				
				if(@$_POST['intro_cod_llamada']!= null){
					$codigo=$_POST['intro_cod_llamada'];
					$usuarioatendiendo= codigollamada::obtenerUsuario($codigo);
				}
				else{
					$codigo=$_POST['Codigo'];
					$usuarioatendiendo= codigollamada::obtenerUsuario($_POST['Codigo']);
				}
				
				$company = $usuarioatendiendo->getCompany();
				$empresa = $company->getUserVisibleName();
				

				$html = "<a href='ficha.php?m=empresa&poid={$company->getUID()}' data-uid='{$company->getUID()}' class='box-it'>{$empresa}</a>";

				$extraFields = new FieldList();
				$extraFields["empresa"] = new FormField(array("tag" => "span", "value" => $html));
				$extraFields["atendiendo_a"] = new FormField(array("tag" => "span", "value" => $usuarioatendiendo->getUserVisibleName()));
				$extraFields["Codigo"] = new FormField(array("tag" => "input", "type" => "hidden", "value" => $codigo, "innerHTML" => ""));

	
				if (isset($campos['uid_usuario_atendido'])) {
					$campos['uid_usuario_atendido']['value']= $usuarioatendiendo->getUserName();
					$campos['uid_usuario_atendido']['type']= 'hidden';
					$campos['uid_usuario_atendido']['innerHTML']= '';
				}

				$campos['hora_fin_llamada_sati']['hr']=true;
			}
				
			
			
			$template->assign ("titulo", "nueva_llamada");
			$template->assign ("campos", $campos);
			if (isset($extraFields)) $template->assign ("extraOptions", $extraFields);
			
			$arraybotones=  array(
					array('innerHTML'=>$template->getString('cerrar'), 'className'=>'close'), 
					array('innerHTML'=>$template('crear'), 'type'=>'submit')
			);  //array para añadir los botones, en este caso es para añadir la X del cierre y el de crear
		}
		$template->assign ("botones", $arraybotones);

	}
	else {
		$template->assign ("titulo", "error" );
		$template->assign ("error","Combinacion de teclas desconocidas");
	}

	$template->assign ("className", "" );
	$template->assign ("boton", false );
	$template->display( "form.tpl");
