define(function(require, exports, module) {

	function polling (path) {
		var _this, path, timeout, $loading;

		$loading = $("#loading");
		_this = this;
		_this.delay = 4000;

		var handle = function (json) {
			json.remote = json.remote || 0;


			/***
			   *
			   *
			   *
			   *
			   *
			   */	
			if (json && json[0]) {
				var servertime = new Date(json[0]*1000), 
					minutes = (servertime.getMinutes().toString().length == 1) ? "0" + servertime.getMinutes() : servertime.getMinutes(),
					seconds = (servertime.getSeconds().toString().length == 1) ? "0" + servertime.getSeconds() : servertime.getSeconds();
			}


			/***
			   *
			   *
			   *
			   *
			   *
			   */
			if (json.access == "0") {
				// alert("Atencion!!!\n\nEstamos efecutando tareas de mantenimiento.\nVolvemos enseguida!.");
				return location.reload();
			}


			/***
			   *
			   *
			   *
			   *
			   *
			   */	
			if (json.exit !== undefined) {
				return location.href = "/agd/salir.php?manual=1&loc=" + json.exit;
			}  

			/***
			   *
			   *
			   *
			   *
			   *
			   */		
			if (json.progress) {
				if (json.progress == "-1") {
					$loading.hide();
				} else {
					$loading.show().find("div").html(json.progress);
				}
			}


			/***
			   *
			   *
			   *
			   *
			   *
			   */
			if (json.messages.length > 0) {

				var messages = json.messages;
				var message;

				for (var i = 0; i < messages.length; i++) {
					message = messages[i];
					if (typeof agd.messages[message.uid] == "undefined") {
						//buscamos en la cadena de mensajes el primero que no se ha mostrado y lo mostramos
						break;
					}
				};

				var selector = "system-message-"+message.uid,
					$altertdiv = $("#"+selector);
				if( !$altertdiv.length ){
					$("#cboxOverlay").css("opacity", 0.5).show();
					$altertdiv = $(document.createElement("div")).attr("id", selector).addClass("system-message").appendTo(document.body);
					
					var $header = $(document.createElement("div")).addClass("header").appendTo($altertdiv);

					if (message.title) {
						var $title = $(document.createElement("span")).addClass("title").appendTo($header).html(message.title.substring(0,50)).attr("title", message.title);
					}

					if (message.companyInviter) {
						var $company = $(document.createElement("span")).addClass("from").appendTo($header).html(message.companyInviter).attr("title", message.companyInviter);
					}

					var $message = $(document.createElement("div")).addClass("message").appendTo($altertdiv).html(message.message);
					var $div = $(document.createElement("div")).appendTo($altertdiv);

					var $footer = $(document.createElement("div")).addClass("footer").appendTo($div);
					if( message.action ){
						$(document.createElement("a")).css("float", "left").addClass("btn").html("<span><span>" + message.action + "</span></span>" ).appendTo($footer).click(function(){
							if (message.href) {
								agd.func.open(message.href);
							} else {
								$.post("userdata.php?message=" + message.uid);
								$("#cboxOverlay").hide();
								$altertdiv.hide();
							}
							return false;
						});
					}

					var cancelable = ( message.cancelable !== undefined ) ? message.cancelable : false;
					if( cancelable ){
						$(document.createElement("a")).html("<span><span>" + agd.strings.no_volver_mostrar + "</span></span>" ).appendTo($footer).click(function(){
							$.post("userdata.php?message=" + message.uid);
							$altertdiv.hide();
							$("#cboxOverlay").hide();
							return false;
						});
					}
					
					$(document.createElement("a")).css("float", "right").html("<span><span>" + agd.strings.recordar_mas_tarde + "</span></span>" ).appendTo($footer).click(function(){
						agd.messages[message.uid] = true;
						$altertdiv.hide();
						$("#cboxOverlay").hide();
						return false;
					});
				};
			};
	


			/***
			   *
			   *
			   *
			   *
			   *
			   */
			if (json.remote) {
				var statusClass = (json.remote.status == 'Conectado') ? 'succes' : 'error',
					displayController = ( json.remote.status == 'Conectado' ) ? '' : 'none';

				$("#link-perfiles").hide();
				if( !window.isRemoteVisor ){
					var aviso = $(document.createElement("div"))
						.attr("id","control-remoto")
						.addClass("estado-pagina")
						.html("Control remoto <strong>"+  json.remote["remote-user"] +"</strong> | ")
						.insertBefore( "#cuerpo" );
					;

					var retorno = $(document.createElement("a"))
						.html("Volver")
						.attr("href", "../simular.php?action=return")
						.click(function(){
							agd.func.simular( json.remote["user-id"] );
							return false;
						}).appendTo(aviso);

					$(document.createTextNode(" | ")).appendTo(aviso);


					var stay = $(document.createElement("a"))
						.html("Me quedo!").attr("title", "Cancela la simulación de este usuario y navega como si fueses él")
						.attr("href", "../simular.php?action=stay").appendTo(aviso);

					$(document.createTextNode(" | ")).appendTo(aviso);

					var toogleControl = function(link){
						if( window.isRemoteControl ){
							$(link).html("Visualizar");
							window.isRemoteControl = false;
						} else {
							$(link).html("Cancelar visualizacion");
							window.isRemoteControl = true;
						}
					};

					var remoteController = $(document.createElement("span")).attr("id", "remote-user-control").css("display",displayController).appendTo(aviso);
					$(document.createElement("a")).html("Visualizar").click(function(){
							toogleControl(this);
							return false;
					}).appendTo(remoteController);
					$(document.createTextNode(" | ")).appendTo(remoteController);

					var $remoteActions = $(document.createElement("span")).css("display",displayController).attr("id","remote-user-actions").appendTo(aviso);



					$( document.createElement("span") ).attr("id", "remote-user-status").html( json.remote.status ).addClass(statusClass).appendTo($remoteActions);
					$(document.createTextNode(" | ")).appendTo($remoteActions);

					// el valor de json[0] == time
					$( document.createElement("span") ).attr("id", "aviso-servertime").html( servertime.getHours() + ":" + minutes + ":" + seconds ).appendTo(aviso); 
				} else {
					_this.setDelay(1000);
					$("#remote-user-actions, #remote-user-control").css("display", displayController); 
					$("#aviso-servertime").html( servertime.getHours() + ":" + minutes + ":" + seconds );
					$("#remote-user-status").attr("className","").addClass(statusClass).html(json.remote.status);
				}

				if( window.isRemoteVisor && window.isRemoteControl && json.remote.hash ){
					var navigationHash = json.remote.hash, strings = navigationHash.split("");

					if( strings[0] == "#" ){
						navigationHash = navigationHash.replace("#","");
						if( location.hash != navigationHash ){
							if( modalbox.exists() ){
								modalbox.func.close();
							}
							location.hash = navigationHash;
						}
					} else {
						if( navigationHash != window.lastModalWindow ){
							agd.func.open(navigationHash);
							window.lastModalWindow = navigationHash;
						}
					}
				}

				window.isRemoteVisor = true;
			} else {
				try {
					$("#link-perfiles").show();
					$("#control-remoto").remove();
					delete(window.isRemoteVisor);
					_this.setDelay(4000);
				} catch(e) {};
			}



			/***
			   *
			   *
			   *
			   *
			   *
			   */
			if (agd.iface == "validation" && json.otherUserAssigned){
				$("button[type=submit].button").attr("disabled", "disabled");
				alert(agd.strings.alert_validation_same_fileid);
				agd.navegar();
			}


			/***
			   *
			   *
			   *
			   *
			   *
			   */
			if (json.respuesta) {
				$.each(json.respuesta, function(i, respuesta){
					if (respuesta.estado == "0") return;

					var cacheString = "respuesta-" + respuesta.uid;
					if (agd.cache.get(cacheString)) return;

					$.each(agd.streaming.callback, function(i,callback){
						callback(respuesta);
					});
				
					$.get("configurar/solicitud/eliminar.php?oid=" + respuesta.uid);

					if (respuesta.estado) {
						agd.cache.save(cacheString, "true");
					};

				});
			};

			/***
			   *
			   *
			   *
			   *
			   *
			   */
			var avisosAsync = ['asignar','upload','transferencia','contratacion','subcontrata'], $notifications;

			$notifications = $('.avisos-principal ul li');

			if (json.solicitud) {
				// si hay solicitudes, recorremos los avisos existentes para eliminar los que no están en el json
				// (han podido borrarse o completarse de manera implicita a través de otra acción que no nos
				// permite retirarla directamente: cascade delete en la bd, enviar papelera, etc.)
				$notifications.each(function(ia,aviso) {
					var idaviso = $(aviso).prop('id'), 
						removethis = true, 
						tipo = $(aviso).prop('class');
					if ($.inArray(tipo, avisosAsync) !== -1) {
						$.each( json.solicitud, function(is,solicitud) {
							if (idaviso == 'aviso-'+solicitud.uid) {
								removethis = false;
							}
						});
						if (removethis) {
							agd.func.removeInPageAlert(aviso);
							agd.cache.save(idaviso, 'false');
						}
					}
				});


				$.each( json.solicitud, function(is, solicitud){
					var cacheString = "aviso-" + solicitud.uid,
						currentLi = $("#"+cacheString);
	
					// Esto va a elminar esta solicitud de la pagina
					if( solicitud.estado == 3 && currentLi.length ){
						currentLi[0].remove();
					}

					// Si la solicitud esta validada o ya se encuentra en la pagina
					if( solicitud.estado == 3 || agd.cache.get(cacheString) ){ return; }
					if( currentLi.length ) return;


					try {
						var alert = agd.create.inPageAlert(solicitud);
						$(alert).prop("id", cacheString);

						if (solicitud.className.indexOf('confirm') !== -1) {
							$(alert).click(function(){
								if( confirm("Aceptar solicitud?") ){
									$.get("configurar/solicitud/validar.php?oid=" + solicitud.uid, function(data){

									});
								} else {
									$.get("configurar/solicitud/rechazar.php?oid=" + solicitud.uid, function(data){

									});
								}
								alert.remove();
							});
						};

					} catch(e){ return false; }

					agd.cache.save( cacheString, "true" );

				});
			} else {
				// si el json no contiene solicitudes de ningun tipo, no tiene que haber avisos de solicitudes ...
				$notifications.each(function(){
					var tipo = $(this).prop('class');
					if ($.inArray(tipo, avisosAsync) !== -1) {
						agd.func.removeInPageAlert($(this));
					}
				});
			}
		};


		this.setDelay = function (delay) {
			_this.delay = delay;
		}

		this.update = function () {
			$.getJSON(path, function (res) {
				handle(res);

				timeout = setTimeout(_this.update, _this.delay);
			});
		}


		this.init = this.update;
	};

	return polling;
});
