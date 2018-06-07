
;(function($){
	$.fn.getRealSize = function(){
		var element = this[0], tagName = element.tagName;
	
		if( element.realSize ){ return element.realSize; }
		var previo = document.createElement( tagName );


		for( attr in element ){
			try {
				previo[ attr ] = element[ attr ];
			} catch(e) {}
		}

		$( previo ).css({
			"visibility":"hidden"
		}).appendTo( document.body );

		var size = {
			"width" : $( previo ).width(),
			"outerWidth" : $( previo ).outerWidth(true),
			"height" : $( previo ).height(),
			"outerHeight" : $( previo ).outerHeight(true)
		};
		$(previo).remove();
		element.realSize = size;
		return element.realSize;
	};
})($);
