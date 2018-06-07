(function(window, undefined){
	var mainAjax,
		$viewport = $("#viewport"),
		$topbar = $("#top-bar"),
		$sidebar = $("#sidebar"),
		$page = $("#page"),
		$body = $("#page-body"),
		$content = $("#page-content"),
		$data = $("#data-content"),
		$box = $("#box-layer"),
		$loading = $("#loading"),
		$search = $("#search-box"),
		$menulink = $('#menu-link'),
		$offline = $('#view-offline'),
		online = navigator.onLine 
	;



	var solicitable = ['empleado', 'empresa', 'maquina'];

	var prepareRoutes = function (href) {

		for (i in solicitable) {
			href = href.replace('ficha.php?m=' + solicitable[i], 'documentos.php?m=' + solicitable[i]);
		}

		return href;
	};

	var createRow = function(linea, fromTree, maxCols, rowIndex, rowsLength, response){
		var columns = linea[ linea.key ],
			$row = $(document.createElement("tr"))
		;

		var $checkBoxTD = $( document.createElement("td") ).attr("align","left").addClass("checkbox-colum").appendTo($row);
			$( document.createElement("input") ).attr({"type":"checkbox"}).appendTo($checkBoxTD);


		if (linea.className) $row.addClass(linea.className);

		var numcols = 0;
		$.each(columns, function(field, value){
			numcols++;
			if (typeof(value) == "string") {
				$td = $(document.createElement("td")).html(value);
			} else {
				var type = (value.href) ? "a" : "span";

				if (value.href) value.href = prepareRoutes(value.href);

				var $span = $(document.createElement(type)).attr(value).prop(value);

				if (type != "a") {
					$span.find('a').each(function() {
						this.href = prepareRoutes(this.href);
					});
				};

				$td = $(document.createElement("td")).append($span);
			};

			$row.append($td);
		});


		if (linea.inline) {
			var numInlineElements = 0;
			$.each( linea.inline, function(inlineName, inline){
				var tdInline = $(document.createElement("td")).addClass("inline-colum");

				if (inline && (inline[0] || inline.width)) {
					if( isNaN(inlineName) ){
						$(tdInline).addClass(inlineName);
						$(document.createElement('span')).addClass('light').html(inlineName + ": ").appendTo(tdInline);
					};

					if (inline.img) {
						var $img = $( document.createElement("img") );
						if( typeof inline.img == "object" ){
							$img.attr(inline.img);
						} else {
							$img.attr("src", inline.img);
						}

						$img.appendTo(tdInline);
						$(document.createTextNode(" ")).appendTo(tdInline);
					};

					$.each(inline, function(prop, elemento){
						var value = inline[prop];
						if (isNaN(prop)) {
							switch (prop){
								default:
									$(tdInline).attr(prop, value);
								break;
								case "img":
									return;
								break;
								case "className":
									$(tdInline).addClass(value);					
								break;
							};
							return; 
						};


						var className = ( elemento.className ) ?  elemento.className : "", 
							href = "";

						if (elemento.nombre) {
							if (elemento.tipo && elemento.oid) {
								href = "ficha.php?m="+elemento.tipo+"&oid=" + elemento.oid;
							};

							if (elemento.href) { href = elemento.href; }
							if (href && href.split("")[0] !== "#") { className+=" box-it"; }

							$a = $(document.createElement("a"))
								.addClass("ucase inline-text "+className)
								.html(elemento.nombre)
								.appendTo(tdInline);

							if (href) { $a.attr({"href":href}); };

							if (elemento.img) {
								$( document.createElement("img") ).css("margin-right","4px").attr(elemento.img).appendTo(tdInline);
							};
						}



						/*if (elemento.estado) {
							var estatusClass = ( elemento.estado.indexOf('<') == -1 ) ? "stat_"+elemento.estadoid : "";

							$(document.createElement("a")).html( elemento.estado  )
								//.addClass('stat stat_'+elemento.estadoid)
								.addClass("docinfo "+estatusClass )
								.appendTo( tdInline )
								.click(function(){
									var URI = "infoestados.php?oid=" + elemento.estadoid + "&current=" + agd.tables.current;
									if( elemento.oid ){ URI += "&poid=" + elemento.oid; }

									agd.func.queryCache(URI, function(data){
										agd.func.jGrowl("estado"+estatusClass, data );
									});
								});
						};*/


						if (elemento.extra) {
							$.each(elemento.extra, function(j, html){
								$(tdInline).append(html);
							});
						};
			
						var lastSeparator = $(document.createElement("div")).html("").addClass('line-block inline-separator').attr("width", "4px").appendTo( tdInline );
					});/**/

					$row.append(tdInline);

					numInlineElements++;
				};

				return false;
			});
		};


		numInlineElements = ( numInlineElements ) ? numInlineElements : 0;
		numcols = numcols + numInlineElements;
		if (maxCols) {
			if ((!linea.options||!linea.options.length)) numcols--;

			if (numcols < maxCols) {
				var newcols = maxCols - numcols;
				while (newcols--) {
					var td = $(document.createElement("td" )).html("&nbsp;").appendTo($row);
				}
			}
		}

		return $row.get(0);
	};



	/****************************************
	************** FUNCTIONS ****************
	*****************************************/
	var openBox = function(href){
		var pos = href.indexOf('/agd/');
		if (pos !== -1) href = href.substring(pos+5);

		return location.hash = href;
	};

	var closeBox = function(){
		$box.empty().css({"width": "0px"}).hide();
	};





	/****************************************************
	**************** DEFAULT PAGE EVENTS ****************
	*****************************************************/
	var defaultEvents = function(context, callback){
		callback = callback || function(){};
		context = context || document.body;

		$(context).find(".form-to-box").each(form2box);

		$(context).find(".box-it").click(function(e){
			e.preventDefault();
			openBox(this.href);
		});


		$(context).find('.async').on('click', function(e) {
			e.preventDefault();

			var $this = $(this),
				$target = $($this.data('target')),
				tagName = this.tagName.toLowerCase(),
				href = $this.data('href');


			if (tagName == 'button') {
				var $image = $(document.createElement('img')).css('margin-left', '1em').attr('src', agd.inlineLoadingImage).appendTo($this);
				$this.attr('disabled', true);
			}


			$.get(href, function (res) {
				$target.html(res);

				// if we have a button, and still in the page...
				if (tagName == 'button' && $this.parent()) {
					$image.remove();
				}
			});
		});


		$(context).find('button[href]').on('click', function(e){
			e.preventDefault();
			var link = $(this).attr('href'), aux = link.split("");
			if( aux[0] == "#" ){
				document.location = link;
			} else {
				openBox(link);
			}			
		});


		$(context).find('form.async-form').on('submit', function (e) {
			var $this = $(this), action, method;

			action = $this.attr('action');
			method = $this.attr('method').toUpperCase();

			
			$.ajax({
				method: method,
				url: action,
				data: $this.serialize(),
				dataType: 'json',
				success: function (res) {
					if (res.refresh) {
						navegar();
					}
				}
			});
			
			return false;
		});

		$(context).find('.load').click(function(e) {
			var $this = $(this),
				$target = $($this.data("target")),
				href = $this.attr("href");
				
			if (!$.trim(href)) return false;

			//ading the loading image
			var $img = $(document.createElement("img")).attr("src", agd.inlineLoadingImage);
			
			var html = $target.html();
			$this.html($img);

			//getting back the information
			$.get(href , function(data){
				$img.remove();
				$target.empty().append(data);

				agd.checkEvents($target);
			});
		
			e.stopImmediatePropagation();
			return false;
		});


		$(context).find('.map').each(function () {
			var map = this;
			require([agd.staticdomain + "/js/maps.min.js?" + __rversion], function(mapHandler) {
				require(["https://maps.googleapis.com/maps/api/js?key="+ agd.gkey +"&sensor=false&callback=onMapsLoaded"], function() {
					new mapHandler(map);
				});
			});
		});

		callback();
	};


	var adjustBoxLayer = function(){
		var h = $box.outerHeight() - ( $box.find(".box-title").outerHeight() || 0 ) - ( $box.find(".cboxButtons").outerHeight() || 0 ), 
			$boxBody = $($box.find(".ficha,  .box-message-block").get(0)); //, .box-title + div

		h = h - (parseInt($boxBody.css("padding-top"))||0) - (parseInt($boxBody.css("padding-bottom"))||0) - (parseInt($boxBody.css("border-top"))||0) - (parseInt($boxBody.css("border-bottom"))||0);

		$boxBody.css("height", h+"px");

		var $options = $box.find("ul.item-options");	
		$options.css("height", h+"px" );
	};

	var getURIparams = function () {
		var i, params, param, parts = {};
		
		params = location.search.substring(1).split('&');
		
		for (i in params) {
			param = params[i].split('=');
			parts[param[0]] = param[1];
		}

		return parts;
	}
	/************************************
	 *********** MAIN AJAX RESPONSE *****
	 ************************************/
	var mainResponse = function(res){
		var href;
		$sidebar.removeClass('open');	

		if (typeof res === 'string' ){
			$data.html(res);
			defaultEvents($data, function(){
				$loading.hide();
				$(window).trigger('server-response');
			});

			return;
		}

		if (res.action) {
			switch (res.action) {
				case "restore":
					href 	= "/login.php?goto=" + encodeURIComponent(location.hash);
					params 	= getURIparams();
					if (params.origin) {
						href += '&origin=' + params.origin;
					}
					
					location.href = href;

				break;
				case "go":
					return location.href = res.action.go;
				break;
			}
		}


		/*$("#main-menu li").removeClass("seleccionado");
		if( mname = res.moduloseleccionado ){
			$("#main-menu li[name^='"+mname+"']").addClass("seleccionado");
		};*/

		if( res.view ){
			$("#page-content > div").hide();
			$("#page-content div#view-"+res.view).show();
		};


		if( res.selector ){
			$.each( res.selector , function( selector, html ){
				$( selector ).html( html );
				defaultEvents(selector);
			});
		};

		if( res.open ){
			openBox(res.open);
		}


		$data.html('<table id="table-data" cellspacing="0" border="0"></table>');
		if( datos = res.datos ){
			var $table = $data.find("#table-data");

			var maxc = res.maxcolums || 0;


			var onTableDraw = function () {
				var $tr = $(document.createElement('tr')).addClass('pagination'),
					$td = $(document.createElement('td')).appendTo($tr).attr({colspan:maxc+1}),
					string = res.paginacion.from + " - " + res.paginacion.to + " <span style='font-size:14px;text-align:top'>|</span> " + res.paginacion.of + " total",
					html = "<div style='float:left'>" + string + "</div>";


				var $next = $(document.createElement("a")).attr({"href": "#" + res.paginacion.href.prox.replace('/agd/', ''), "class":"next-page"}).html("&raquo;");
				var $prev = $(document.createElement("a")).attr({"href": "#" + res.paginacion.href.prev.replace('/agd/', ''), "class":"prev-page"}).html("&laquo;");

				$td.html(html);
				if (res.paginacion.from != 1) $td.append($prev);
				if (res.paginacion.to != res.paginacion.of) $td.append($next);

				// añadimos a la tabla
				$tr.appendTo($table);


				defaultEvents($table, function(){
					$loading.hide();
				});
			};

			if( datos.length ){
				$.each( datos, function(i, linea){
					var row = createRow(linea, null, maxc, i, datos.length, res);
					$table.append(row);

					if (i === datos.length-1) onTableDraw();
				});
			}
		} else {
			$data.html("<div style='text-align: center'>No hay resultados</div>");
			$loading.hide();
		}

		$(window).trigger('server-response');
	};

	var navegar = function(){
		$loading.show();
		if (mainAjax && mainAjax.abort) mainAjax.abort();

		mainAjax = $.ajax({
			url: location.hash.replace("#",""),
			cache: false,
			dataType: 'json',
			data: 'type=ajax',
			success: mainResponse,
			complete: function(a, b, c) {
				if (window.ga) {
					ga('send', 'pageview',  '/agd/' + location.hash);
				}

				if(b=='parsererror') { mainResponse(a.responseText); }
			}
		});
	};


	defaultEvents();

	$data.click(function(e){
		try {
			if (e.target.tagName.toLowerCase() != "a" && $box.width()) {
				closeBox();
			};
		} catch(e) {};
	});



	function loadUserData (cb) {
		var tz, fromQR;

		if (online === false) return false;
		tz = new Date().getTimezoneOffset() / 60;
		fromQR = location.hash.indexOf('src=qr') !== -1;


		if (fromQR) {
			cb();

			// reset cb
			cb = null;
		};

		$.getJSON("userdata.php", {tz:tz}, function (data) {
			if (data.strings) agd.strings = data.strings;
			if (data.maxfile) agd.usermaxfile = data.maxfile;
			if (data.user) agd.user = data.user;
			if (data.gkey) agd.gkey = data.gkey;

			if (cb) cb(data);
		});

		if (navigator.geolocation) {

			function persistLocation (position) {
    			var location = position.coords.latitude + ',' + position.coords.longitude;
    			$.post("userdata.php?option=location", {location: location});
    		};


    		// first location
    		navigator.geolocation.getCurrentPosition(function (position) { 
    			// persist and get more accuracy location
    			persistLocation(position);

    			setTimeout(function () {
    				navigator.geolocation.getCurrentPosition(persistLocation, null, {enableHighAccuracy: true, maximumAge: 0});
    			}, 500);
    		}, function (error) {
    			// timeout error
    			if (error.code === 3) {
    				navigator.geolocation.getCurrentPosition(persistLocation, null, {enableHighAccuracy: true, maximumAge: 0});
    			}
    		}, {timeout: 10000});
		};
	};

	$('#menu-link').click(function () {
		var isOpen = $sidebar.hasClass('open');

		if (isOpen) {
			$sidebar.removeClass('open');	
		} else {
			$sidebar.addClass('open');	
		}

		return false;
	});

	$page.click(function () {
		var isOpen = $sidebar.hasClass('open');
		if (isOpen) {
			$sidebar.removeClass('open');
		}
	});


	window.agd = {
		staticdomain: __resources,
		inlineLoadingImage : __resources + '/img/common/ajax-loader.gif',
		func : {
			validateForm : function(){ return true; },
			open : function(href){ openBox(href);  }
		}
	};

	// --- preload images
	setTimeout(function () {
		document.createElement('img').src = agd.inlineLoadingImage;
		$("#home-icon").attr('src', function () {
			return $(this).data('src');
		});
	}, 100)


	if (!location.hash) location.href = '#home.php';

	window.onhashchange = navegar;


	function goOnline () {
		online = true;
		navegar();
		$search.show();
		$content.show();
		$menulink.show();
		$offline.hide();
	};

	function goOffline () {
		online = false;
		$search.hide();
		$content.hide();
		$sidebar.removeClass('open');
		$menulink.hide();
		$offline.show();

		/*
		// No activamos esta redireccion por que puede ocasionar bucles
		var url = location.hash.substring(1);

		if (url.indexOf('src=qr') !== -1 && url.indexOf('tipo:empleado') !== -1) {
			var uid, loc; 
			
			uid 	= url.substring(url.lastIndexOf(':') + 1);
			loc 	= window.location.protocol + '//' + window.location.hostname + '/qr/' + uid;

			window.location.href = loc;
		}*/
	};


	$(document).on('online', goOnline);
	$(document).on('offline', goOffline);

	if (online == false) {
		goOffline();
	}

	if (location.hash) {
		loadUserData(function () {
			navegar();
		});
	};

	function resizeLayout () {
		var width 			= $(window).width(), 
			height 			= $(window).height(),
			contentHeight 	= $data.height();

		$viewport.css('width', (width + 200) + "px");


		if (height < contentHeight) {
			height = contentHeight;
		}

		$body.css("height", height + "px");
		$body.css("width", width + "px");
		$box.css("height", $content.height()+"px");
		$sidebar.css("height", $("body").height() + "px");
	};

	setTimeout(function(){
		window.scrollTo(0, 1);
		window.scrollTo(0, 0);
	}, 1000);

	resizeLayout();
	$(window).one('server-response', resizeLayout);

	$(window).on('resize', resizeLayout);

	$("#next-qr").on('touchstart', function (e) {
		$(this).addClass('actived');
	 	return false;
	});

	$("#next-qr").on('touchend', function (e) {
		var self 		= this;
		location.href 	= this.href;
		setTimeout(function() {
			$(self).removeClass('actived')
		}, 300);
		return false;
	});


})(window);
