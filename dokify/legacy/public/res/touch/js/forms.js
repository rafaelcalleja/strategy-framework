
	var form2box = function(o, ob){
		var form = this, restoreForm,
			method = $.trim( ($(form).attr("method")||'get').toLowerCase() ),
			jajax = ( method && $[method] ) ? $[method] : $.post,
			boxLayer = "#box-layer"
		;

		// ------------ Comprobar que todos los archivos esten cargados...
		var allFilesUploaded = function(){
			var files = $("input[type='file']", form), len = files.length;
			while(len--){
				if( !files[len].isComplete && !$(files[len]).attr("complete") ){
					return false;
				}
			};
			return true;
		};

		$(".filecontainer", form).each(function(i, o){
			var boton = $("button", this),
				input = $("input", this),
				height = ($(boton).outerHeight() + 4),
				width = ($(boton).width() + 8),
				left = ($(boton).width()-width)
			;

			// Ajuste para webkit
			if( $.browser.webkit ){  width+= 6; }
			$( input ).css({"top": "-"+ height +"px", "left":"-"+ left +"px",display:"inline"});

			$( this ).hover(function(){
				$( boton ).focus();
			}).mouseout(function(){
				$( boton ).blur();
			}).css({
				"width": width+"px", 
				"height": height+"px",
				"visibility": "visible"
			});
		});

		//-------- fin estilizar inputs

		$("input[type='file']", form).change(function(e){
			var inputFile = this;
			if( inputFile.restoreForm ){ inputFile.restoreForm(); }


			//------ para avisar al usuario si sale de la página
			window.uploading = true;

			var 	target = $( $(this).attr("target") ),
					action = $(form).attr("action"),
					mtd = $(form).attr("mtd"),
					enct = $(form).attr("enctype"),
					input = $( this ),
					filetype = $(this).attr("filetype") || null,
					uniqID = encodeURIComponent( (new Date()).getTime() );

			inputFile.isCanceled = false;
			inputFile.isComplete = false; //cambiar a isComplete;
			target.html("Inicializando...");

			//------- si se asigno el estado por que se equivoco el usuario y selecciona el archivo de nuevo, hay que quitar el atributo para asegurar todas las comprobaciones
			try{ $(inputFile).removeAttr("complete"); } catch(e) { };



			//----------- SI EL USUARIO QUIERE CANCELAR EL EVENTO, O DECIDE CERRAR LA VENTANA
			var cancelUpload = function( txt, current ){
				current = current || false;
				if( inputFile.isCanceled ){ return; }

				//---- si no hay texto, evitamos el error
				txt = ( txt ) ? txt : "";

				//---- variables de proceso
				inputFile.isCanceled = true;
				window.uploading = false;
				if (!current) { input.val(""); }

				if (inputFile.lastProgress) delete(inputFile.lastProgress);

				var span = $(document.createElement("span")).html( txt );

				//----- describimos el problema
				target.empty().append( span );



				return span[0];
			};

			//----------- FUNCION PARA ESTABLECER EL PROGRESO DE LA CARGA
			var setProgress = function( progress, extra ){
				$(target).find(".progressbar").css("background-position",(100-progress)+"% 50%");
				$(target).find("span").html( progress+"% ("+ extra.upload+"Mb/"+extra.total+"Mb)");
				if( progress == 100 ){
					inputFile.isComplete = true;
				}
			};


			var uploadStartTime = ( new Date() ).getTime();


			var limiteExcedido = function(maxb, current, currentbytes){
				var aux = (!current)?"<":"";
				var texto = cancelUpload( "<span class='link'>Limite de tamaño excedido ("+ maxb +"MB). Tu archivo pesa " + aux + current + "MB</span>", true ); 
				return false;
			};

			var showCompleteInfo = function( fileName, fileSize, type ){
				//----------- INDICAMOS QUE NUESTRO ELEMENTO FILE ESTA COMPLETO
				inputFile.isComplete = true;

				try {
					var innerHTMLName = ( fileName.length > 60 ) ? fileName.substring(0,60)+"..." : fileName, size = Math.round(parseInt( (fileSize)/1024) );
					$( document.createElement("a") ).attr({
						"title": agd.strings.descargar + fileName + " ("+size+"Kb)",
						"href":"getuploaded.php?action=dl",
						"target":"async-frame"
					}).html( innerHTMLName + " <i>("+ type +")</i>" ).appendTo( target );
				} catch(e){
					$(target).html(agd.strings.error_texto);
				}
				

				try {
					$.fn.colorbox.resize();
				} catch(e) {};

				if( btn = $("button.send", form).get(0) ){
					btn.restore();
				};
			};

			//----------- EMPEZAMOS A CHECKEAR
			target.html( "<div id='uploadProgressBar' class='progressbar line-block'> </div>&nbsp;<span>0%</span> <a style='font-weight:normal'>Cancelar</a>" );
			$("a",target).click(function(){
				cancelUpload( "Cancelado por el usuario" ); 
			});

			
			// Comprobamos funcionalidades HTML5: FILE API
			if( (files = inputFile.files) && files[0] && files[0].name ){
				var file = inputFile.files[0], size = file.size;

				if( size > agd.usermaxfile ){
					return limiteExcedido(  Math.round(agd.usermaxfile/1024/1024), Math.round(size/1024/1024), size);
				};

				var done = function(){
					target.empty();
					showCompleteInfo(file.name, size, file.type);

					// Eliminados variables de control
					window.uploading = false;
					$(form).data("pass", true);
				};

				var xhr = file.upload("uploadfiles.php", {
					onprogress : function(e){
						if( e.lengthComputable ){
							var percentage = Math.round((e.loaded*100)/e.total);
							setProgress( percentage, { upload:  Math.round(e.loaded/1024/1024), total: Math.round(e.total/1024/1024)} );
						}
					},
					//onload : done,
					onsuccess : done,
					onerror : function(e){
						cancelUpload("Error al subir el fichero");
					}
				});

				$(target).find("a").click(function(){
					try{ xhr.abort(); } catch(e){};
				});
	
				
			} else {
				cancelUpload("Error al subir el fichero");
				return false;
			}


			// ---- Timeout para mostrar carga
			var timeToShowInfo = window.setTimeout(function(){
				if( (!inputFile.isComplete && typeof inputFile.lastProgress == "undefined" || inputFile.lastProgress == 0) ){
					target.html("<span><img src='"+ agd.inlineLoadingImage +"' /> Subiendo, espera por favor... <a>Cancelar</a></span>");
					$("a",target).click(function(){
						cancelUpload( "Cancelado por el usuario" ); 
					});
				}
			}, 4000);

			return false;
		});
		//--- Fin campos file



		var sndbutton = $("button.send", form).unbind("click").click(function(e){
			try {
				$(this).attr({ "disabled":true});

				if( allFilesUploaded() ){
					$(this).find("span > span").html( agd.strings.enviando );
					$(form).submit();
				} else {
					$("span > span",this).html( agd.strings.esperando_cargar_archivos );
				}

			} catch(e) { };
			return false;
		}).removeAttr("disabled").get(0);

		try {
			var defaultHTML = sndbutton.innerHTML;
			sndbutton.restore = function(){
				$(sndbutton).removeAttr("disabled");
				$(sndbutton).find("span > span").html(defaultHTML);		
			};
		} catch(e) {}

		//--- Fin botones de enviar
	
		$(this).unbind("submit").submit(function(e){
			//alert( form.getAttribute("enctype") + " ------ " + $(form).attr('enctype') );
			if( form.getAttribute("enctype") == "multipart/form-data" && !$(form).data("pass") ){
				if( !$(form).attr('rel') && allFilesUploaded() ){

					// ya no estamos haciendo upload
					window.uploading = false;
					// restauramos el formulario a los valores por defecto
					restoreForm();
					// asociamos una variable para pasar directamente al envio de datos simple..
					$(form).data("pass", true);

					//lo enviamos...
					$(form).submit();

					return false;
				} else {
					return true;
				}
			} else {
				if( agd.func.validateForm(form) ){
					var params = $(form).serialize(), action = $(form).attr("action"), cnct = ( action.indexOf("?")==-1 )?"?":"&";
					$('*[disabled]',form).each(function(){
						params = params + '&' + $(this).attr("name") + '=' + encodeURIComponent($(this).val());
		 	 		});

					agd.func.open( action + cnct +  params, false, method );
				}
			};
			return false;
		});
		//---- Fin evento envio
	};


	if ("File" in window) {
		File.prototype.upload = function( URL, options){
			options = options || {};
			options.method = (  options.method ) ? options.method.toLowerCase() : "put";
			options.onprogress = options.onprogress || function(){};
			options.onload = options.onload || function(){};
			options.onsuccess = options.onsuccess || function(){};
			options.onerror = options.onerror || function(){};

			// Uploading - for Firefox, Google Chrome and Safari
			var xhr = new XMLHttpRequest();
			
			xhr.upload.onprogress = options.onprogress;
			xhr.upload.onload = options.onload;
			xhr.upload.onerror = options.onerror;

			xhr.open(options.method, URL, true);


			// Set appropriate headers
			xhr.setRequestHeader("Content-Type", "multipart/form-data");
			xhr.setRequestHeader("X-File-Name", this.name);
			xhr.setRequestHeader("X-File-Size", this.size);
			xhr.setRequestHeader("X-File-Type", this.type);

			xhr.onreadystatechange = function(){ 

				if(xhr.readyState==4){
					if (xhr.status==200) {
						options.onsuccess.apply(xhr); 
					} else {
						options.onerror.apply(xhr, [xhr.responseText]); 
					}
				}
			};

			xhr.send(this);
			return xhr;
		};
	}
