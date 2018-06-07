<?php 
	
	class invoiceItem extends solicitable implements IinvoiceItem, Ielemento{

		const DESCRIPTION_VALIDATION = 'Cobro validacion';
		const DESCRIPTION_LICENSE = 'Cobro licencia';

		public function __construct($param, $extra = false){
			$this->tipo = "invoiceItem";
			$this->tabla = TABLE_INVOICE_ITEM;
			$this->instance( $param, $extra );
		}

		public static function defaultData ($data, Iusuario $usuario = null) {

			$data["date"] = isset($data["date"]) ? $data["date"] : date("Y-m-d H:i:s");

			return $data;
		}


		public static function getRouteName () {
			return 'invoiceitem';
		}

		public function getTreeData(Iusuario $usuario, $extraData = array()){

			$treeData = array();
			$item = $this->getItem();
			
			switch (get_class($item)) {
				case 'validationStatus':
					$treeData = array(
									"checkbox" => false,
									"img" => array(
										"normal" => RESOURCES_DOMAIN ."/img/famfam/spellcheck.png"
									)
								);
					break;

				case 'paypalLicense':
					$treeData = array(
									"checkbox" => false,
									"img" => array(
										"normal" => RESOURCES_DOMAIN ."/img/common/certified.png"
									)
								);
					break;
			}
		
			return $treeData;
		}


		public static function getAllTypes() {
			$types = array(
				self::DESCRIPTION_VALIDATION,
				self::DESCRIPTION_LICENSE
			);

			return $types;
		}


		public function getUserVisibleName(){
			$info = $this->getInfo();
			return "invoice_item";
		}

		public function getInvoice(){
			$info = $this->getInfo();
			return new invoice($info["uid_invoice"]);
		}

		public function getDescription(){
			$info = $this->getInfo();
			return $info["description"];
		}

		public function getAmount(){
			$info = $this->getInfo();
			return $info["amount"];
		}

		public function getNumItems(){
			$info = $this->getInfo();
			return $info["num_items"];
		}

		public function getReferenceId(){
			$info = $this->getInfo();
			return $info["uid_reference"];
		}

		public function getModulo(){
			$info = $this->getInfo();
			return $info["uid_modulo"];
		}

		public function getItem(){

			$referenceItem = $this->getReferenceId();
			$moduleName = util::getModuleName($this->getModulo());
			if ($moduleName && $referenceItem) {
				$item = new $moduleName($referenceItem);
				if ($item instanceof $moduleName) return $item;
			}

			return false;
		}

		public function getDate(){
			$item = $this->getItem();
			if ($item) return $item->getDate();
			return false;
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $data = array()){

			$tpl = Plantilla::singleton();
			$dataTable = array();
			$item = $this->getItem();
			
			switch (get_class($item)) {
				case 'validationStatus':
					$owner 		= $data["parent"];
					$anexo 		= $item->getAttachment();
					$modulo 	= $item->getRequestableModuleName();
					$estadoID 	= $anexo->getStatus();
					$documento 	= $anexo->obtenerDocumento();
					$element 	= $anexo->getElement();
					
					$dataTable["documentName"] 	= $documento->getUserVisibleName();
					$dataTable["itemName"] 		= $element ? $element->getUserVisibleName() : '';
					

					$moduloName 				= str_replace("anexo_historico_", "", $modulo);
					$moduloName 				= str_replace("anexo_", "", $moduloName);
					$dataTable["itemModule"] 	= $moduloName;

					$dataTable["estado"] = array(
								"tagName" => "span",
								"innerHTML" => documento::status2String($estadoID), 
								"title" => $tpl->getString('explain_request.stat_'.$estadoID), 
								"className" => "help stat stat_".$estadoID
							);

					if ($element) {
						if ($element->inTrash($owner)) {
							$dataTable["ver"] = $tpl->getString("elemento_actualmente_papelera");
						} elseif (strpos(get_class($anexo), "historico")!==false) {
							$modulo = str_replace("anexo_historico_", "", $modulo);

							$dataTable["ver"] = array(
									"class" => "box-it",
									"href" => "documentohistorico.php?p=0&m=$modulo&poid={$documento->getUID()}&o={$element->getUID()}&selected={$anexo->getUID()}&type=modal",
									"title" => $tpl->getString("historico_documento"),
									"innerHTML" => $tpl->getString("historico_documento")
								);
						} else {
							$modulo = str_replace("_", "-", $modulo);

							$dataTable["ver"] = array(
									"href" => "#buscar.php?q=tipo:$modulo anexo:{$anexo->getUID()}",
									"title" => $tpl->getString("ver_anexo"),
									"innerHTML" => $tpl->getString("ver_anexo")
								);
						}
					}

					$dataTable["amount"] = $this->getAmount(). " €";

					break;

				case 'paypalLicense':
					
					$paypalLicense			= $this->getItem();
					$typeString 			= $tpl->getString($paypalLicense->getTypeName());
					$company 				= $paypalLicense->getCompany();
					$companyname			= $company->getUserVisibleName();
					$dataTable["type"] 		= $typeString;	
					$dataTable["compnay"] 	= $companyname;
					$dataTable["amount"]	= $this->getAmount(). " €";

					break;
				
				default:
					break;
			}

			$tableInfo = array($this->getUID() => $dataTable);
			return $tableInfo;
		}


		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false){
			$arrayCampos = new FieldList();

			$arrayCampos["uid_invoice"] = new FormField();
			$arrayCampos["description"] = new FormField();
			$arrayCampos["amount"] = new FormField();
			$arrayCampos["num_items"] = new FormField();
			$arrayCampos["uid_reference"] = new FormField();
			$arrayCampos["uid_modulo"] = new FormField();
			$arrayCampos["date"] = new FormField();
			return $arrayCampos;
		}

	}