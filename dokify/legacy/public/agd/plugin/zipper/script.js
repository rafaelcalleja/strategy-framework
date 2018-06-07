;agd.func.registerCallback( "zipper", function(json, where){
	if( !agd.tables.current ){ return false; };
	var ct = ( agd.tables.current.indexOf("-") == -1 ) ? agd.tables.current : agd.tables.current.substring(0,  agd.tables.current.indexOf("-"));

	if( ( ct == "anexo_empresa" || ct == "anexo_empleado" || ct == "anexo_maquina"  ) && !where ){

		$( agd.create.button({
			innerHTML : agd.strings.descargar_zip, 
			className : "btn zipper",
			img :  agd.staticdomain + "/img/famfam/page_white_compressed.png"
		})).click(function(){
			if (agd.plugins===false) { return agd.func.open('payplugins.php?plugin=zipper'); }
			var queryString = agd.func.array2url( "selected", agd.func.selectedRows() );
			if( queryString.length ){
				agd.elements.asyncFrame.src = "plugin/zipper/?poid="+ahistory.getValue("poid") +"&m=" + ahistory.getValue("m") + "&" + queryString;
			} else {
				agd.func.jGrowl("zipper", "Selecciona algun documento");
			}

		}).appendTo( agd.views.data.elements.options );
	}

});
