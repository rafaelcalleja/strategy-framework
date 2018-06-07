(function(window, undefined){
	window.enableAdvancedSearch = function(parent){
		var $parent = $(parent),
			$form = forms = $parent.find(".advanced-search-form"),
			$content =  $parent.find("#advanced-search-content"),
			$btn = $parent.find("#buscador-avanzado")
		;
				

		var addFormFilter = function(button, val){
			var //forms = $parent.find(".advanced-search-form"),
				form = $($form[forms.length-1]),
				newForm = form.clone(true).appendTo($content),
				td = $(button).closest("td"),
				oldactions = form.find(".action-buttons").empty(),
				actions = newForm.find(".action-buttons"),
				input = $(document.createElement("input")).attr({ type : "hidden", name : "concat", value : val }).appendTo(td);

			$(actions).find(".remove-form").remove();
			var btn = $(document.createElement("button")).addClass("btn remove-form")
				.html("<span><span>"+agd.strings.quitar_filtro+"</span></span>")
				.click(function(){
					var form = $(this.form), prevForm = form.prev(), prevActions = $(prevForm).find(".action-buttons");
					if( $(".advanced-search-form").length <= 2 ){ $(this).remove(); }
					$("input[name='concat']", prevForm).remove();
					$(".action-buttons button", form).clone(true).appendTo( prevActions );
					$(form).remove();
					$.fn.colorbox.resize();
					return false;
				}).appendTo(actions);

			if( val == " " ){
				$(newForm[0].string).val("").closest("tr").css("display", "none");
			} else {
				$(newForm[0].string).val("").closest("tr").css("display", "");
			}

			$.fn.colorbox.resize();
			return newForm[0];
		};

		var sendAll = function(){
			var forms = $parent.find(".advanced-search-form"), ln = forms.length, query = [];

			for(i=0;i<ln;i++){
				var form = forms[i],
					data = [],
					text = $.trim($(form.string).attr("value")),
					docs = $.trim($(form.docs).val()),
					tipo = $.trim($(form.tipo).val()),
					asignado = $(form.asignado).val(),
					estado = $(form.estado).val(),
					activo = $(form.papelera).val()
				;

		
				if( tipo ){ data.push("tipo:"+ encodeURIComponent(tipo) ); }
				if( estado ){ data.push("estado:"+estado); }
				if( !isNaN(docs) ){ data.push("docs:"+ encodeURIComponent(docs) ); }
				if( !isNaN(asignado) ){ data.push("asignado:"+ encodeURIComponent(asignado) ); }
				if( text ){ data.push( encodeURIComponent(text) ); }
				if( data.length && ( !isNaN(activo) || activo=="all") ){ data.push("papelera:"+ activo ); }


				var searchString = data.join(" ");
				if( $.trim(searchString) ){
					query.push( searchString );

					if( form.concat && data.length ){ query.push( encodeURIComponent($(form.concat).val()) ); };
				}
			}

			var searchString = query.join(""), URI = "buscar.php?p=0&q=" + searchString;
			if( !searchString.length ){ alert("Por favor introduce los criterios de busqueda"); return false; }

			$.fn.colorbox.close();
			location.hash = URI;

			return false;
		};

		$parent.find("#mas-and").click(function(){
			var nForm = addFormFilter(this, " "); //" "
			return false;
		});

		$parent.find("#mas-or").click(function(){
			var nForm = addFormFilter(this, "+"); //"+"
			return false;
		});


		$form.submit(sendAll);
		$btn.click(sendAll);

		$parent.find("#agrupamiento-asignado").change(function(){
			var thiz = this, value = $(thiz).val(), URI = "query.php?t=agrupador&f=uid_agrupamiento&v=" + value;
			$.get(URI, function(data){
				var options = agd.func.getJson(data), target = $("#asignado", $(thiz).closest("form"));
				$(":not(.default)", target).remove();
				var defaultHTML = ( thiz.selectedIndex != 0 ) ? "Seleccionar " + $(":selected", thiz).text() : "&laquo;&laquo;&laquo;";
				$(".default", target).html( defaultHTML );
				$.each(options, function(i, op){
					$( document.createElement("option") ).prop({
						innerHTML : op.nombre,
						value : op.oid
					}).appendTo(target);
				});
			});
		});
	};

})(window);
