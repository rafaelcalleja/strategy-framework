(function($){
	$.fn.floatInfo = function(props){
		// Definimos unos atributos de css por defecto
		var options = {
			width : "380px",
			height : "162px",
			bottom : "30px",
			right : "30px",
			position : "absolute"
		};

		// Extendemos si el dev quiere adaptar la posicion
		// del div en la pagina
		options = $.extend(options, props);




		var showFloatInfo = function(ob, options){
			var toggleColor = options.toggleColor; delete( options.toggleColor );

			// Si ya se creo el div, lo recuperamos
			if( ob.floatDiv ){
				return false;
			} else {
				var floatDiv = $( document.createElement("div") ).addClass("floatinfo").css(options).css("display", "none");

				// Mostramos el div principal
				floatDiv.appendTo( document.body );
			}

			var img = '<img src="' +__resources +'/img/common/restorewin.gif" />';
			var topFloat = parseInt( options.bottom ) + $(floatDiv).outerHeight(true);
			var minimizer = $(document.createElement("div"))
				.css({
					display : "none",
					position : "absolute",
					bottom : topFloat,
					right : options.right
				})
				.addClass("floatinfo-minimize")
				.html("<a>"+img+"</a>")
				.click(function(){
				
					if( this.isHidden ){
						// Ahora la informacion se muestra...
						this.isHidden = false;
						$(this).animate({
							right : options.right,
							bottom : topFloat
						}).html( this.defaultHtml );
					} else {
						// Ahora la informacion esta oculta
						this.isHidden = true;

						$(this).animate({
							right:		"0px",
							bottom : 	options.bottom
						});
					}

					floatDiv.slideToggle();
					return false;
				})
				.appendTo(document.body)
			;
			minimizer[0].defaultHtml = minimizer[0].innerHTML;

			// Informamos de que esta cargando...
			$( floatDiv ).addClass("loading");
		
		
			var href = ( options.url ) ? options.url : $(ob).attr("href");

			// Buscamos la informacion que tenemos que mostrar...
			var ajax = $.get( href, function(data){
				if( data ){
					$( floatDiv ).removeClass("loading").append( data ).slideToggle();
					$( minimizer ).toggle();

					if( toggleColor ){
						floatDiv.interval = window.setInterval(function(){
							$( floatDiv ).toggleClass("colored");
							$( minimizer ).toggleClass("colored");
						}, 700);
					}
				} else {
					$( floatDiv ).remove();
					$( minimizer ).remove();
				}
			});

			ob.floatDiv = floatDiv;

			return ajax;	
		};


		//Si hay coleccion de elementos...
		if( this.length ){
			// Para cada objeto encontrado
			$(this).each(function(i, ob){
				// En el vento click
				$(ob).click(function(){
					showFloatInfo(ob, options);
				});
			});
		} else {
			if( options.url ){
				return showFloatInfo(this, options);
			}
		}

	
	};
})($);
