<?php

	class SearchNotification extends elemento implements Ielemento {

		public function __construct ($param, $extra = false) {
			$this->tipo = "searchnotification";
			$this->tabla = TABLE_BUSQUEDA_USUARIO . "_notification";
			$this->instance($param, $extra);
		}

		public function getCC () {
			$emails = array();

			$cc = $this->obtenerDato('cc');
			$pieces = explode(',', $cc);

			foreach ($pieces as $piece) {
				$piece = trim($piece);
				$regexp = elemento::getEmailRegExp();

				if (preg_match("/$regexp/", $piece)) {
					$emails[] = $piece;
				}
			}

			return $emails;
		}

		public function getUserVisibleName () {
			return $this->getBusqueda()->getUserVisibleName();
		}

		public function getBusqueda () {
			return new buscador($this->obtenerDato("uid_usuario_busqueda"));
		}

		public function getUser () {
			return new usuario ($this->obtenerDato('uid_usuario'));
		}

		public function getCompany () {
			return new empresa ($this->obtenerDato('uid_empresa'));
		}

		public function render () {
			$subject = $this->obtenerDato('subject');
			$comment = nl2br($this->obtenerDato('comment'));


			$html = "<div style='width:700px;margin:20px'><h1>{$subject}</h1><hr /><p>{$comment}</p></div>";

			return $html;
		}

		public function getInlineArray(Iusuario $usuario = NULL, $config = false, $data = NULL) {
			$busqueda = $this->getBusqueda();
			$tpl = Plantilla::singleton();
			$inline = array();

			// --- Dates
			$dates = array();
			$dates['img'] = RESOURCES_DOMAIN . "/img/famfam/calendar.png";

			$dates[] = array(
				'tagName'	=> 'span',
				'title'		=> $tpl('fecha'),
				'nombre' 	=> date('Y-m-d', $this->getTimestamp()),
			);

			$inline[] = $dates;


			// --- Emails inline block
			$receipts = $this->getReceipts();
			$num = count($receipts);
			$title = sprintf($tpl('n_destinatarios'), $num);

			$emails = array();
			$emails["img"] = array(
				'src' => RESOURCES_DOMAIN . "/img/famfam/user_go.png",
				'title' => $title
			);
			$emails[] = array(
				'title'		=> $tpl('click_aqui_ver'),
				'nombre' 	=> $title,
				'href' 		=> "#busqueda/notifications.php?poid={$busqueda->getUID()}&comefrom={$this->getUID()}"
			);

			$inline[] = $emails;


			// --- View inline 
			$view = array();
			$view['img'] = RESOURCES_DOMAIN . "/img/famfam/eye.png";

			$view[] = array(
				'className' => 'box-it',
				'href'		=> "busqueda/notifications.php?poid={$busqueda->getUID()}&comefrom={$this->getUID()}&action=view",
				'nombre' 	=> $tpl('ver'),
			);

			$inline[] = $view;

			return $inline;
		}

		public function getTableInfo(Iusuario $usuario = NULL, Ielemento $parent = NULL, $extraData = array()) {
			$data = array();

			$data["subject"] = $this->obtenerDato("subject");


			return array($this->getUID() => $data);
		}


		public function getTimestamp () {
			return strtotime($this->obtenerDato('created'));
		}

		public function getReceipts () {
			$SQL = "SELECT uid_usuario_busqueda_notification_status FROM {$this->tabla}_status WHERE uid_usuario_busqueda_notification = {$this->getUID()}";
			if (count($array = $this->db->query($SQL, "*", 0, 'SearchNotificationStatus'))) {
				return new ArrayObjectList($array);
			}

			return new ArrayObjectList;
		}

		public function createQueue () {
			$usuario = $this->getUser();
			$buscador = $this->getBusqueda();
			$items = $buscador->getResultObjects($usuario);
			$regexp = elemento::getEmailRegExp();

			$results = new ArrayObjectList;

			foreach ($items as $item) {
				$class = get_class($item);

				switch ($class) {
					case 'empresa':
						$users = $item->obtenerUsuarios();

						foreach ($users as $user) {

							$email 	= $user->getEmail();

							// si no es un email valido, lo saltamos
							if (!preg_match("/{$regexp}/", $email)) continue;

							$data = array(
								'uid_usuario_busqueda_notification' => $this->getUID(),
								'receipt'							=> $email,
								'status'							=> SearchNotificationStatus::STATUS_SENDING,
								'uid_modulo'						=> $user->getModuleId(),
								'uid_elemento'						=> $user->getUID(),
								'uid_empresa'						=> $item->getUID()
							);

							
							$notifyStatus = new SearchNotificationStatus($data, $usuario);

							$results[] = $notifyStatus;
						}

						break;
					
					case 'empleado':
							
							if (($email = $item->getEmail()) && preg_match("/{$regexp}/", $email)) {
								
								$data = array(
									'uid_usuario_busqueda_notification' => $this->getUID(),
									'receipt'							=> $email,
									'status'							=> SearchNotificationStatus::STATUS_SENDING,
									'uid_modulo'						=> $item->getModuleId(),
									'uid_elemento'						=> $item->getUID(),
									'uid_empresa'						=> $item->getCompany()->getUID()
								);

								
								$notifyStatus = new SearchNotificationStatus($data, $usuario);
								$results[] = $notifyStatus;
							}

						break;

					default:
						continue;
						break;
				}
			}

			return $results;
		}

		public function inProgress()
		{
			$statuses = TABLE_BUSQUEDA_USUARIO ."_notification_status";
			$sent     = SearchNotificationStatus::STATUS_SEND;

			$sql = "SELECT count(uid_usuario_busqueda_notification)
			FROM {$statuses}
			WHERE uid_usuario_busqueda_notification = {$this->getUID()}
			AND status = {$sent}
			";

			$num = (int) $this->db->query($sql, 0, 0);

			// not in progress if there are no emails sent yet
			if ($num === 0) {
				return false;
			}

			return true;
		}

		public static function cronCall ($time, $force = false) {
			$db = db::singleton();

			$existingSearch = "SELECT uid_usuario_busqueda FROM ". TABLE_BUSQUEDA_USUARIO;

			$SQL = "SELECT uid_usuario_busqueda_notification_status 
			FROM ". TABLE_BUSQUEDA_USUARIO ."_notification_status 
			INNER JOIN ". TABLE_BUSQUEDA_USUARIO ."_notification USING (uid_usuario_busqueda_notification) 
			WHERE status = " . SearchNotificationStatus::STATUS_SENDING ." 
			AND uid_usuario_busqueda IN ({$existingSearch})
			LIMIT 20";

			$notifications = $db->query($SQL, "*", 0, 'SearchNotificationStatus');

			if ($num = count($notifications)) {
				print "Found {$num} emails pending...\n";
				foreach ($notifications as $i => $notificatioStatus) {
					try {
						// First send to cc
						// Por lógica deberíamos extraer de otra forma los datos, pero de esta manera
						// es un proceso bastante óptimo
						if ($i == 0) {
							$notification = $notificatioStatus->getSearchNotification();

							// Si tenemos emails a los que poner en copia, enviamos un email a cada uno
							if ($notification->inProgress() === false && ($cc = $notification->getCC())) {
								foreach ($cc as $email) {
									print "Sending CC to {$email}... \n";
									$notificatioStatus->send($email, $force);
								}
							}
						}

						$receipt = $notificatioStatus->getReceipt();

						print "Sending to {$receipt}... ";
						$notificatioStatus->send(false, $force);

						print "OK!";
					} catch (Exception $e) {
						print "Error! [{$e->getMessage()}]";
					}
					
					print "\n";
				}
			}

			return true;
		}

		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false) {
			$fields = new FieldList;

			$fields["uid_usuario_busqueda"] = new FormField;
			$fields["uid_usuario"] 	= new FormField(array());
			$fields["uid_empresa"] 	= new FormField(array());
			$fields["subject"] 		= new FormField(array());
			$fields["comment"] 		= new FormField(array());
			$fields["cc"] 			= new FormField(array());

			return $fields;
		}
	}