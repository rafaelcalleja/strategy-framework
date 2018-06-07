<?php

	class ArrayRequestGroupedList extends ArrayRequestList {

		const STATUS = "status";
		const STATE_ACCEPTED = 0;
		const STATE_DENIED = 1;
		const STATE_PENDING = 2;
		const STATE_PROCESSED = 3;

		protected $status;
		protected $type;
		protected $applicantCompany;
		protected $element;
		protected $user;

		public function __construct( $request = NULL ){
			$this->instance($request = NULL );
		}

		protected function instance( $request = NULL ){
			if (isset($request) && $request instanceof empresasolicitud) {
				$this->setType($request->getTypeOf());
				$this->setApplicantCompany($request->getSolicitante());
				$this->setElement($request->getItem());
				$this->setUser($request->getUser());
				$this[] = $request;
				$this->setStatus($this->getStatus());
			} 
		}

		public function addCompanyRequest(empresasolicitud $request){
			$type = $this->getType();
			if (!isset($type)) {
				$this->instance($request);
				return true;
			} else if ($this->isSuitable($request)) {
				$this[] = $request;
				$this->setStatus($this->getStatus());
				return true;
			} 
			return false;
		}

		public function isSuitable(empresasolicitud $request){
			if ($request instanceof empresasolicitud) {
				if ( ($request->getTypeOf() == $this->getType()) 
					&& ($request->getSolicitante()->compareTo($this->getApplicantCompany())) 
					&& ($request->getItem()->compareTo($this->element)) ) {
					return true;
				}
			}			
			return false;
		}

		public function getStatus() { 
			$state = NULL;
			foreach ($this as $request) {
				if (!isset($state)) {
					$state = $this->getStatusByRequest($request);
				} else {
					$state = $this->getStatusAddRequest($state,$request);				
				}
			}
			return $state;
		}

		protected function getStatusByRequest($request) {	
			switch ($request->getTypeOf()) {
				case solicitud::TYPE_TRANSFERENCIA:
					switch ($request->getState()) {
						case solicitud::ESTADO_ACEPTADA: case solicitud::ESTADO_SHARED: 
							return ArrayRequestGroupedList::STATE_ACCEPTED;
							break;

						case solicitud::ESTADO_PROCESSED:
							return ArrayRequestGroupedList::STATE_PROCESSED;
							break;

						case solicitud::ESTADO_RECHAZADA: case solicitud::ESTADO_CANCELADA:
							return ArrayRequestGroupedList::STATE_DENIED;
							break;
						
						default:
							return ArrayRequestGroupedList::STATE_PENDING;
							break;
					}
					break;
				default:
					return NULL;
					break;
			}
		}

		public function getStatusAddRequest($state,$request) { 
			switch ($request->getTypeOf()) {
				case solicitud::TYPE_TRANSFERENCIA:
					switch ($state) {
						case ArrayRequestGroupedList::STATE_ACCEPTED: 
							return $state;
							break;

						case ArrayRequestGroupedList::STATE_PROCESSED:
							return $this->getStatusByRequest($request);
							break;

						case ArrayRequestGroupedList::STATE_DENIED:						
							return $this->getStatusByRequest($request);
							break;

						case ArrayRequestGroupedList::STATE_PENDING:
							if ($request->isAccepted() || $request->isShared()) {
								return ArrayRequestGroupedList::STATE_ACCEPTED;
							} 
							return $state;
							break;
						
						default:
							return false;
							break;
					}
					break;
				
				default:
					return false;
					break;
			}
		}

		public function getApplicantCompany() { 
			return $this->applicantCompany;
		}

		public function getElement() { 
			return $this->element;
		}

		public function getType() { 
			return $this->type;
		}

		public function getUser() { 
			return $this->user;
		}

		public function setStatus($status) { 
			$this->status = $status;
		}

		public function setApplicantCompany($applicantCompany) { 
			$this->applicantCompany = $applicantCompany;
		}

		public function setElement($element) { 
			$this->element = $element;
		}

		public function setUser($user) { 
			$this->user = $user;
		}

		public function setType($type) { 
			$this->type = $type;
		}

		public static function getPendingsEmpresaSolicitud($tipo = NULL) { 
			$requests = empresasolicitud::getRequestByApplicant($tipo,solicitud::ESTADO_PROCESSED,array('>',1));
			$requests = $requests->merge(empresasolicitud::getRequestByApplicant($tipo,solicitud::ESTADO_PROCESSED,array('=',1)))->unique(); 
			return $requests->toArrayGroupedList();
		}

		public function sendNotificationsEmpresaSolicitud(){
			$result = true;
			$status = $this->getStatus();
			switch ($this->getType()) {
				// All the request which owns this element and are of Transfer Type
				case solicitud::TYPE_TRANSFERENCIA:
					switch ($status) {

						case ArrayRequestGroupedList::STATE_DENIED:
							$messages = array();
							$enviar = true;
							foreach ($this as $request) {
								if ($enviar) {
									$enviar = $request->daysSinceCreated() < 5 ? true : false;	
								}
								
								if ($request->isRefused() ) {
									$messages[] = $request->getMessage();
								}
								$request->setState(solicitud::ESTADO_PROCESSED);								
							}
							$this->setStatus(ArrayRequestGroupedList::STATE_PROCESSED);
							if ($enviar) {
								$result = empresasolicitud::sendDeniedTransferEmployee($this->getApplicantCompany(),$this->getElement(),$messages,$this->getUser()) && $result;
							}							
							break;

						case ArrayRequestGroupedList::STATE_PENDING:
							$expired = false;
							foreach ($this as $request) {
								$days = $request->daysSinceCreated();
								if ($request->isCreatedStatus()) {
									switch ($days) {
										case 4:
											$request->setState(solicitud::ESTADO_PROCESSED);
											$expired = true;
											break;
										case 3:
											$result = $request->sendAlertExpiredTransferEmployee() && $result;
											break;
										case 1:
											$result = $request->sendAlertExpiredTransferEmployee() && $result;
											break;				
										default:
											if ($days > 4) {
												//Compartir Empleado
												//$result= $request->share();
												$request->setState(solicitud::ESTADO_PROCESSED);
											}
											//$result = false;
											break;
									}
								} else if ($days == 4) $request->setState(solicitud::ESTADO_PROCESSED);																
							}
							if ($expired)  {
								$this->setStatus(ArrayRequestGroupedList::STATE_PROCESSED);
								$result = solicitud::sendExpiredTransferEmployee($this->getApplicantCompany(),$this->getElement(),$this->getUser()) && $result;
							}
							break;
						case ArrayRequestGroupedList::STATE_ACCEPTED: 
							foreach ($this as $request) {
								$request->setState(solicitud::ESTADO_PROCESSED);							
							}
							break;

						case ArrayRequestGroupedList::STATE_PROCESSED:
							foreach ($this as $request) {
								$request->setState(solicitud::ESTADO_PROCESSED);							
							}
							break;

						default:
							
							break;
					}

					return $result;
						
					break;
				
				default:
					return false;
					break;
			}
			return false;		
		}


	}
