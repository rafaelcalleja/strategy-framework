(function($){
	$.fn.extendedCells=function(fn){
		var docWidth = $(document).width();
		$(this).each(function(i,ob){

			var td = (ob.tagName == "TD") ? ob : $(ob).closest("TD").get(0) ;
			if( !td && td.tagName != "TD" ){ return false; }
			if( !$(td).attr("href") ){ return false; }

			// Tomamos el contenido actual de la celda 
			// y el alto actual para utilizarlo en nuesto div
			// el cual aparecera oculto
			var currentContent = $(td).html(), 
				currentHeight = $(td).height(),
				currentWidth = $(td).width(),
				currentOffset = $(td).offset()
			;

		
			// Vaciamos la celda en cuestion
			// para rellenarla con el div que estara "medio" visible
			$(td).empty();
			if( $.browser.msie ){
				$(td).css("vertical-align","top");
				//necesario al usar position absolute, se descuadra.
			}

		
			// Creamos el div que pondremos en la celda
			// y definimos su alto, para ocultar el scroll
			// que falta para dejar oculto el contenido antes del click

			var wrap = $(document.createElement("div"))
				//.width( currentWidth )
				.css("overflow", "hidden")
				.html( currentContent )
				.addClass("extended-wrap-cell")
				.appendTo( td )
			;

			if( $.browser.msie ){ wrap.css("position","absolute")}
		
			if( ob.tagName != "TD" ){
				ob = wrap.find(".extended-cell").get(0);
			};
		
			// Creamos la funcion que se encarga de pedir los datos
			// si no estan cargados y de recuperarlos si lo estan
			function getData( oTd, callback ){
		
				// Funcion a la que se llama cuando acaba de solicitarse los datos
				callback = callback || function(){};
			
			
				// Si se ha cargado previamente la informacion ??
				// la devolvemos
				if( oTd.serverData ){

					callback(  oTd.serverData );
				} else {
				
					// Llamamos a la página que nos da la informacion
					// y la pasamos como parametro despues de almacenarla en 
					// el objeto td actual
					$( document.body ).css("cursor", "wait" );
					var href = $( oTd ).attr("href"), ajax = $.get( href, function(data){
						oTd.serverData = data;
						$( document.body ).css("cursor", "" );
						callback( data );
					});

					agd.func.registerCallback("extended-"+href, function(){
						try {
							delete(agd.callbacks["extended-"+href]);
							$( document.body ).css("cursor", "" );
							ajax.abort();
						} catch(e){};
					}); 
				}
			};
		
		
			// Creamos la funcion que crear la caja donde 
			// almacenaremos el contenido 
			function createExtendedBox(){
		
				// creamos el div principal
				// de momento oculto
				var div = $( document.createElement("div") ).addClass("extended-cell");
				

				if( $.browser.msie ){ div.css("position","relative"); }
			
				div.getRealWidth = function(){
					if( this.realWidth ){
						return this.realWidth;
					};
				
					var prev = $(document.createElement("div"))
						.html( $(this).html() )
						.addClass("extended-cell")
						.css({
							"visivility":"hidden", 
							//"width" : currentWidth,
							"display":"inline-block", 
							"font-size": $(td).css("font-size")
						})
						.appendTo( document.body );

					var width = $( prev ).width();
					$( prev ).remove();
				
					this.realWidth = width;
				
					return this.realWidth;
				};
			
				return div;
			};
		

			// Añadimos a la celda actual el div con la informacion extra
			var box = createExtendedBox();
			// Por si hay padding...
			box.css("margin-left", "-"+ $(wrap).css("padding-left") );
			$( wrap ).append( box );
		
			// Creamos la funcion para ocultar la caja
			function removeCurrentBox(e){
				//$( document ).unbind("click",  removeCurrentBox );
				//$( document ).one("click",  removeCurrentBox );

				if( e && e.target && $(e.target).closest(".extended-wrap-cell")[0] == wrap[0] ){
					$( document ).one("click", removeCurrentBox );
					// No hacer nada, se toca el mismo boton...
				} else {
					wrap.removeClass("extended-cell-active").css({"overflow":"hidden","position":""});

					if( $.browser.msie ){ wrap.css("position","absolute").css("z-index","1"); }
					
					$(box).css("visibility","hidden");
					$(td).removeClass("extended-td-active");
					// console.log( wrap.css("overflow") );
				}
			};
		
			function showCurrentBox(){
				// Solicitamos los datos y los
				// añadimos a nuestro div principal
				// ademas de hacer visible el objeto extendido
				getData( td, function(sData){
					if( !sData ){
						return false; 
					};


					$( box ).html( sData );	
					
					//$( box ).width( boxWidth );

		
					var	aproxDocumentWidth = docWidth - 20, 
						widthBoxDocumentWidth = ( box.offset().left + $(box).width() ),
						diff = widthBoxDocumentWidth - aproxDocumentWidth ;

					if( aproxDocumentWidth < widthBoxDocumentWidth ){
						$(box).css("margin-left", "-"+ diff+"px");
					}

					$(box).css("visibility","");

					$( wrap )
						.css("overflow","")
						.css("z-index","100")
						.addClass("extended-cell-active");
					$( td ).addClass("extended-td-active");

					$( document ).one("click", removeCurrentBox );

					fn && fn.apply(box); // call external callback
				});
			};

			// Cuando se haga un click....
			$( ob ).click(function(){
				// Comprobamos si actualmente se esta viendo el 
				// div añadido en pantalla
				var currentOverflow = $( wrap ).css("overflow");

				if( !td.style.width ){
					$(td).css("width", $(td).width() );
				}

				// Si no se esta viendo...
				if( currentOverflow == "hidden" ){
					showCurrentBox();
				}/* else {
					//removeCurrentBox();
				}*/
			});
		
			// Añadimos a la pagina el div formateado para que no sea visible			
			$( td ).append( wrap );
			
		});
	};
})($);
