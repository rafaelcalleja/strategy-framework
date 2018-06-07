(function(window, undefined){

 
	
  
	 var mostrarCapaColumnaLateral = function( URL, ancho){
	 
	 	ancho = ancho || '400px';
	 	var $columna = $(document.createElement("div")),
		$carga = $(document.createElement("div")),
		$capa =  $(document.createElement("div"));//lo cremaos aquí para tenerlo dispo en la 
		
		$('#sidebar').remove();
		
		
		
		//funcion enviaform
		var enviaform = function(e){
			e.preventDefault();
			$columna.empty();
			$carga.show();
			var url = $(this).attr('action'); 
			var datos = $(this).serialize(); 
			$carga.appendTo($columna);
			$.post(url,datos, function(response) {  
				 $columna.html(response); 
				 $columna.find('.close').click(function (e){
					$capa.remove();		
				});
				 $columna.find('form').submit(enviaform); 

				$(document).trigger('sidebar-open');
			}); 
		}	
	
	 	$capa.css({'position':'absolute', 'top':'30px', 'right':'0px' }).attr('id','sidebar').appendTo('body');		
		
	  	var $muestra = $(document.createElement("div"));
		$muestra.attr('id','Xsidebar');		
		
		$muestra.appendTo($capa).click(function(e){
			if($columna.width()!=1){
				$columna.animate({"width":'1px'}, "slow");
				$muestra.addClass("cerrada");
			}
			else{
				$columna.animate({"width": ancho+'px' }, "slow");
				$muestra.removeClass("cerrada");
			}	
		});	
		$columna.css({'height':'100%', 'overflow' : 'hidden', 'width': ancho+'px', 'float':'left', 'white-space': 'nowrap'}).appendTo($capa);		
		$columna.attr('id','columnalateral');
		
		
		/*barra cargando, en este caso es un texto cutre*/
		
		$carga.css({'width':'100%', 'height':'300px' }).attr('id','cargacolumna').appendTo($columna);	
		
		
		//$carga.show()
		$.get(URL, function(response){
			$columna.html(response);
			$columna.find('.close').click(function (){
				$capa.remove();			
			});
			$columna.find('form').submit(enviaform);//cargar aqui el evento submit del form, que es cuando acabará de cargar la plantilla. Sacamos la funcion fuera, llamarla						

			$(document).trigger('sidebar-open');
			//$carga.hide();
			/*$carga.ajaxStart(function() { });
			$carga.ajaxStop(function() {  }); esto equivale a $carga.show y $carga.hide */
		});
		
		
		//si quiero crear otra funcion dentro de esta se hace igual que esta principal, y no hace falta sacarla como global, se llamaría desde la principal, no individualmente    var minuevafuncion = function ()

	 }  //fin funcion mostrarCapaColumnaLateral
	 
	 window.mostrarCapaColumnaLateral = mostrarCapaColumnaLateral; //sacar a global la funcion para poder usarla fuera, por ejemplo en este caso el boton 'muestra' desde el html
	 
})(window);
