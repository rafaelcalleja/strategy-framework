;agd.func.registerCallback( "table2xls", function(json, contexto){

	if( !agd.tables.current ){ return; };

	var aTables = {"empresa":"","agrupador":"","usuario":"","empleado":"","maquina":"","documento_atributo":"","invoice":""},
		pos = ( agd.tables.current.indexOf("-") == -1 ) ? agd.tables.current.length : agd.tables.current.indexOf("-"),
		ctable = agd.tables.current.substring( 0, pos),
		parent = agd.tables.current.substring(pos+1);


	if( typeof(aTables[ctable]) != "undefined" && agd.views.activeView == "data" && contexto == null ){

		$( agd.create.button({
			innerHTML : "Exportar XLS",
			img : agd.staticdomain + "/img/famfam/page_white_excel.png"
		})).click(function(){
			if (agd.plugins===false) { return agd.func.open('payplugins.php?plugin=table2xls'); }
			var queryString = agd.func.array2url( "selected", agd.func.selectedRows() ); queryString = ( queryString ) ? queryString : "q=0";
		
			var url = "plugin/table2xls/?table=" + ctable + "&" + queryString + "&poid=" + ahistory.getValue("poid");	
		
			var parent = ( agd.tables.current.indexOf("-") == -1 ) ? "" : agd.tables.current.substring( agd.tables.current.lastIndexOf("-")+1 );
			if( parent ) url += "&comefrom=" + parent;

			agd.elements.asyncFrame.src = url;
		}).appendTo( agd.views.data.elements.options );
	};

	if( agd.tables.current == "buscar" && contexto == null ){

		$( agd.create.button({
			innerHTML : "Exportar XLS",
			img :  agd.staticdomain + "/img/famfam/page_white_excel.png"
		})).click(function(){
			if (agd.plugins===false) { return agd.func.open('payplugins.php?plugin=table2xls'); }
			var href = ahistory.curLocation.replace(/\+/ig, encodeURIComponent("+"));
			href = href.replace(/\#/ig, encodeURIComponent("#"));

			agd.elements.asyncFrame.src = href + "&export=xls";
		}).appendTo( agd.views.data.elements.options );

	}
});
