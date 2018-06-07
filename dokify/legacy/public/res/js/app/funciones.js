(function(window, undefined){
    // conexion ajax permanente
    window.__comet = false;
    window.__asyncNavigation = false;


    var modalbox = {
        id : "#colorbox",
        body : "#cboxLoadedContent",
        event : {
            load : "cbox_complete",
            ready : "cbox_ready",
            resize : "cbox_resize",
            cleanup : "cbox_cleanup"
        },
        func : {
            resize : function(callback, data){
                // --- remvoe residual tipsy
                $(".tipsy").remove();

                return $.fn.colorbox.resize(data, callback);
            },
            close : function(){
                if (modalbox.settings.overlayClose && !modalbox.settings.overlayClose()) {
                    return false;
                }

                // --- remvoe residual tipsy
                $(".tipsy").remove();

                return $.fn.colorbox.close();
            },
            open : function(data, callback){
                // --- remvoe residual tipsy
                $(".tipsy").remove();

                return $.fn.colorbox(data, callback);
            }
        },
        exists : function(){
            return $.fn.colorbox.element()
        },
        settings : $.fn.colorbox.settings
    };


    modalbox.settings.overlayClose = function(){
        if (window.modalconfirm) {
            if (window.modalconfirm == true) {
                if (!confirm(agd.strings.pregunta_cerrar_ventana)) {
                    return false;
                }
            } else {
                if (!confirm(modalconfirm)) {
                    return false;
                }
            }

            window.modalconfirm = false;
        }

        return true;
    };

    var $loading = $("#loading"),
        $loadLayer = $("#load"),
        activeNotification,
        slowTimeout,
        polling;

    /*el objeto principal del cual colgara todo*/
    window.agd = {
        currentPage : false,
        loaded : false,
        loading : false,
        cachetime : 10000,
        iface : "docs",
        ifaces : {},
        messages : {},
        title : document.title,
        locale : window.__locale,
        staticdomain : __resources,
        sati : 0,
        agent : 0,
        empresa : null,
        user: null,
        plugins: false,
        optionsImage : __resources + '/img/famfam/cog.png',
        loadingImage : __resources + '/img/common/loading.gif',
        loadingErrorImage : __resources + '/img/famfam/error.png',
        inlineLoadingImage : __resources + '/img/common/ajax-loader.gif',
        uploadPath : "/agd/uploadfiles.php",
        gkey : false,
        strings : {},
        routes : {},
        tours : {},
        tour : false,
        constants : {
            async : {
                empty: 0,
                loading: 1,
                loaded: 2
            },
            ajaxAsyncCallStatus : {
                waiting: 1,
                loaded: 2
            }
        },
        /* guardara todos los callbacks necesarios para el biult-in o plugins */
        callbacks : {},
        /* elementos de la pagina */
        elements : {},
        /* Almacenamos la navegacion */
        history : [],
        create : {
            asistente : function( src ){
                $asistente = $(document.createElement("div")).addClass("asistente-usuario").appendTo(document.body);
                var params = ahistory.getParams(src);
                src = ahistory.getPage(src);

                function getStep(step){
                    step = parseInt(step) || 1, cnc = ( src.indexOf("?")==-1 ) ? "?" : "&";

                    url = src + cnc + "step=" + step;
                    agd.func.queryCache( url, function(res){
                        $asistente.html(res);

                        $asistente.find(".last").click(function(){
                            $asistente.empty();
                            getStep( step = -1 );
                        });
                        $asistente.find(".next").click(function(){
                            $asistente.empty();
                            getStep( step + 1 );
                        });
                        $asistente.find(".previous").click(function(){
                            $asistente.empty();
                            getStep( step - 1 );
                        });
                        $asistente.find(".close").click(function(){
                            $asistente.remove();
                        });
                        $asistente.find(".closePermanently").click(function(){
                            $.post('usuario/flags.php','flag=asistente');
                            $asistente.remove();
                        });
                        agd.checkEvents($asistente);
                    });
                };

                agd.func.registerCallback("asistente", function(){
                    setTimeout(function(){
                        $asistente.effect("shake", { distance:10, times:10 }, 50);
                    },500);
                    delete( agd.callbacks["asistente"] );
                });


                getStep( params.step || false );
            },
            treeView: function(tableRow, fromTree) {

                /* Aplicamos el evento... */
                var treeFunction = function (e) {

                    var targetTag = e ? e.target.tagName.toLowerCase() : false;

                    if (targetTag == "td" || targetTag == "img" || !targetTag) {
                        if (tableRow.subrow) {
                            if (tableRow.isTreeVisible) {
                                $(tableRow.img).attr("src", tableRow.tree.img.normal);
                                tableRow.isTreeVisible = false;
                            } else {
                                $(tableRow.img).attr("src", tableRow.tree.img.open || tableRow.tree.img.normal);
                                tableRow.isTreeVisible = true;
                            }

                            $(tableRow.subrow).toggle();
                            return;
                        }

                        $(tableRow.img).attr("src", agd.inlineLoadingImage);

                        var collenght = $("td", tableRow).length;
                        var appendDiv = $(document.createElement('div'));
                        var appendTable = $(document.createElement("table")).attr({"cellpadding":"0", "cellspacing":"0", "border":"0"}).appendTo(appendDiv);
                        var appendTd = $(document.createElement("td"))
                            .addClass("no-hover no-padding data-block")
                            .css("overflow-y","visible")
                            .attr("colspan", collenght )
                            .append(appendDiv);

                        var appendRow = $(document.createElement("tr")).addClass("desplegable").css("display","none").append(appendTd);


                        // ---- Si la linea aun no se ve, esperamos...
                        var showNewLine = function(tableRow, appendRow){
                            if($(tableRow).outerHeight(true)) {
                                $(tableRow).trigger("draw").after(appendRow);
                            } else {
                                window.setTimeout(function(){
                                    showNewLine(tableRow, appendRow);
                                }, 100);
                            }
                        };
                        showNewLine(tableRow, appendRow);


                        var parseTreeData = function(json){
                            if (json) {
                                var datos = json.datos, row, parentColspan = $(appendTable).parent().attr("colspan");
                                if (typeof parentColspan === "undefined" ) parentColspan = 1;
                                if (datos) {
                                    var level = fromTree ? fromTree + 1 : 1;

                                    $.each(datos, function(i, linea){
                                        if (linea.group) {

                                            var colspan = (fromTree) ? parentColspan-1 : parentColspan;

                                            var row = $(document.createElement("tr")).addClass("table-group-title extra-line"),
                                                blanktd = $(document.createElement("td")).appendTo(row),
                                                td = $(document.createElement("td")).attr("colspan", colspan ).appendTo(row).html(linea.group);

                                            appendTable.append(row);
                                        } else {
                                            var row = agd.func.rowFromData(linea, level, json.maxcolums, i, datos.length, json);
                                            $(row).find(".checkbox-colum").addClass("row-link").find("img").css("margin-left", "22px");
                                            appendTable.append(row);
                                        }
                                    });


                                    if (json.paginacion) {
                                        if (json.paginacion.to < json.paginacion.of) {
                                            var string = agd.strings.mostrando_del + " 0 " + agd.strings.al + " " + json.paginacion.to + " " + agd.strings.of + " " + json.paginacion.of,
                                            $trPagination = $(document.createElement("tr")),
                                            $span = $(document.createElement("span")).addClass("paginacion").html(string),
                                            $link = $(document.createElement("a")).css("margin", "0 5px").html(agd.strings.load_more_results),
                                            $tdPagination = $(document.createElement("td")).addClass("no-hover no-padding")
                                                    .attr({colspan: parentColspan + 3}).css("text-align", "right")
                                                    .appendTo($trPagination)
                                                    .append($span);

                                            // if we know the total number
                                            if (json.paginacion.of) string ;

                                            $link.click(function(e){
                                                $(document.createElement("img")).css("margin", "0 5px").attr("src", agd.inlineLoadingImage).appendTo($span);
                                                $link.remove();

                                                $.ajax({
                                                    url: json.paginacion.href.prox,
                                                    beforeSend : function(xhr, settings){
                                                        xhr.setRequestHeader("X-TREE", true);
                                                    },
                                                    success: function(data, textStatus, XMLHttpRequest){
                                                        var nextPageData = agd.func.getJson(data);

                                                        // ajustar columnas, restams el checkbox
                                                        if (nextPageData.maxcolums < collenght) { nextPageData.maxcolums = (collenght-1); }

                                                        // remove current pagination object
                                                        $trPagination.remove();

                                                        // append the new lines
                                                        parseTreeData(nextPageData);
                                                    }
                                                });
                                            }).appendTo($span);



                                            appendTable.append($trPagination);
                                        }
                                    }

                                } else {
                                    $(appendRow).css("border","0px").find("td:first").css("text-align","center").html("<div class='message highlight'>"+agd.strings.no_resultados+"</div>");
                                }

                                appendRow.css("display", "");

                                tableRow.subrow = appendRow;
                                tableRow.subtable = appendTable;

                                $(tableRow.img).attr("src", tableRow.tree.img.open || tableRow.tree.img.normal );
                                tableRow.isTreeVisible = true;
                            } else {
                                $(tableRow.img).attr({"src":agd.loadingErrorImage,"title": agd.strings.error });
                                agd.func.jGrowl("tree-load","Error al cargar la informacion");
                                $(tableRow).unbind("click");
                            }

                        };


                        if (location.href.indexOf('embedded=true') !== -1) {
                            tableRow.tree.url += '&embedded=true';
                        }

                        $.ajax({
                            url: tableRow.tree.url,
                            beforeSend : function(xhr, settings){
                                xhr.setRequestHeader("X-TREE", true);
                            },
                            success: function(data, textStatus, XMLHttpRequest){
                                parseTreeData(agd.func.getJson(data));
                            }
                        });
                    }
                };

                $(tableRow).click(treeFunction);

                $(tableRow).mouseover(function(){
                    $(this).addClass("selected");
                }).mouseout(function(){
                    $(this).removeClass("selected");
                });


                return treeFunction;
            },
            inPageAlert : function( atributos ){
                var $menu = $("#menu-avisos"),
                    $lista = $menu.find("ul"),
                    $avisos = $lista.find("li"),
                    $numeroAvisos = $("#numeroavisos"),
                    boton = $("#boton-avisos"),
                    li = $( document.createElement("li") ).addClass(atributos.className).appendTo( $lista ),
                    DOMLi = li[0],
                    main = $( document.createElement("div") ).html("<span>"+ atributos.title +"</span>" + atributos.innerHTML ).css("display", "none").appendTo( li ).slideToggle()
                ;

                if( boton.css("display") == "none" ){
                    boton.slideToggle();
                };

                $numeroAvisos.html( $lista.find("li").length );

                DOMLi.remove = function(){
                    agd.func.removeInPageAlert(this);
                };

                return DOMLi;
            },
            menuButton : function( atributos, appendMode ){
                if( !agd.elements.menu ){
                    window.setTimeout(function(){
                        agd.create.menuButton( atributos, appendMode );
                    }, 400);
                    return;
                };

                atributos.className = ( atributos.className ) ? atributos.className + " line-block" : "line-block";
                var href = atributos.href; delete(atributos.href);
                var innerHTML = atributos.innerHTML; delete(atributos.innerHTML);
                var src = atributos.src; delete(atributos.src);


                var html = "<img src='" + src + "' height='32px'/><div class='line-block'>&nbsp;<a href='" + href + "'>" + innerHTML + "</a></div>";
                var li = $( document.createElement('li') ).prop(atributos).html(html).css("padding-right", "14px");

                if( appendMode != "undefined" ){
                    if( !isNaN(appendMode) ){
                        var listOptions = $( "li", agd.elements.menu );
                        if( listOptions[ appendMode ] ){
                            $( listOptions[ appendMode ] ).before( li );
                        } else {
                            li.appendTo( agd.elements.menu );
                        }
                    } else {
                        li.appendTo( agd.elements.menu );
                    }
                } else {
                    return li[0];
                }
            },

            button : function( atributos ){
                var buttonAttr = $.extend({}, atributos);
                if( buttonAttr[ "img" ] ){
                    buttonAttr[ "innerHTML" ] = "<img src='"+ buttonAttr[ "img" ] +"' /> " + buttonAttr[ "innerHTML" ];
                    delete(buttonAttr[ "img" ]);
                }

                var className = buttonAttr["className"] || "";
                if( !className || className.indexOf("btn") == -1 ){ className+= " btn"; }
                delete(buttonAttr["className"]);

                buttonAttr[ "innerHTML" ] = "<span><span>" + buttonAttr[ "innerHTML" ] + "</span></span>";
                return $(document.createElement("button")).addClass(className).prop( buttonAttr ).attr( buttonAttr )[0];
            },
            li : function( attr ){
                atributos = attr;
                if( !atributos.name ){ atributos.name = false; }
                var imgstring = ( atributos.img ) ? "<img src='"+atributos.img+"' class='option-img' style='vertical-align: text-top;' height='16px' width='16px' />" : "";
                atributos.innerHTML = "<div><span>"+ imgstring + atributos.innerHTML +"</span></div>";
                delete( atributos.img );
                return $( document.createElement('li') ).prop( atributos )[0];
            },
            ul : function( lista ){
                var l=lista.length, total=l;

                var $ul = $( document.createElement('ul') );

                while (l--){
                    var item = lista[total-l-1];
                    var $li = $( document.createElement('li') ).addClass(item.className).appendTo($ul);
                    $li.html("<input type='" + item.type + "' name='" + item.name + "' value='" + item.value + "' "+ ((item.checked)?"checked":"") + ((item.disabled)?"disabled=true":"") + " /> " + item.innerHTML);
                };

                return $ul[0];
            },
            modal : function( title, buttons ){
                var $total = $(document.createElement("div"));

                if( title ){
                    var $cabecera = $( document.createElement("div") ).addClass( "box-title" ).html(title).appendTo( $total );
                }

                var $item = $( document.createElement("div") ).addClass( "cbox-content" ).appendTo( $total );

                if( buttons.length ){
                    var l= buttons.length, total=l;
                    var $botones = $( document.createElement("div") ).addClass( "cboxButtons" );
                    while (l--){
                        var buttonData = buttons[total-l-1], $button = agd.create.button(buttonData);
                        $botones.append( $button );
                    }
                    $botones.appendTo( $total );
                }

                return $total[0];
            },
            select : function( atributos ){
                if( atributos.inline ){
                    var div = $(document.createElement("div")).css("text-align","right"),
                        tbl = $(document.createElement("table")).css("display","inline").appendTo(div),
                        row = $(document.createElement("tr")).appendTo(tbl);
                    $.each( atributos.options, function( i, option ){
                        if( i > 0 ){
                            $(document.createElement("td"))
                            .html("<img src='"+option.img+"' href='"+option.href+"' class='option-img showname' name='"+option.innerHTML+"' title='"+option.innerHTML+"' style='vertical-align: text-top; cursor:pointer;'/>")
                            .appendTo(row).find("img").click(function(){agd.func.lineoption(this);return false;});
                        }
                    });
                    div[0].rawoptions = atributos.options; //link para acceso rapido
                    return div;
                } else {
                    var firstoption, ul, div, sum;

                    div = $(document.createElement('div')).addClass('select');

                    if( atributos.sub ){
                        div.addClass("subselect");
                    }

                    //------ DEFINIMOS LOS ATRTIBUTOS DEL PROPIO DIV
                    var attr = {};
                    for( opname in atributos ){
                        if( opname != "options" ){
                            attr[opname] = atributos[opname];
                        }
                    }

                    if( $.browser.msie ){
                        select = $(document.createElement("select"))
                            .appendTo(div)
                            .prop(attr)
                            .change(function(e){
                                var lanzador = this.options[ this.selectedIndex ];
                                if( $( lanzador ).hasClass("checkbox-desplegable") ){

                                } else {
                                    agd.func.lineoption( lanzador );
                                }
                                $(this.options[ this.selectedIndex ]).trigger("click");
                                this.selectedIndex = 0;
                                return false;
                            }).css('width', '100px')

                        $.each( atributos.options, function(i, option ){
                            //for(o in option){alert(o +" --- " + option[o]);}
                            var option = $( document.createElement('option') )
                                //.html( option.innerHTML )
                                .appendTo( select )
                                .prop( option );
                        });

                    } else {
                        var selecth = ( $.browser.webkit ) ? "20px" : "21px",
                            $wrap = $(document.createElement("div")).css("height", selecth).appendTo( div ),
                            ul = $(document.createElement('ul')).appendTo( $wrap ).prop(attr),
                            thiz = div;

                        $.each( atributos.options, function(i, option ){
                            if( i == 0 ){
                                if( atributos.sub ){
                                    firstoption = $( document.createElement('li') ).html( "<div><span class='arrow'></span>" + option.innerHTML + "</div>" ).appendTo( ul );
                                } else {
                                    var htmlString = ( option.img && option.img !== undefined ) ? "<div><span class='arrow'></span><img src='"+option.img+"' class='option-img' style='vertical-align: text-top;' width='16px' height='16px' />"+option.innerHTML+"</div>" : "<div><span class='arrow'></span>"+option.innerHTML+"</div>";
                                    firstoption = $( document.createElement('li') ).html( htmlString ).appendTo( ul );
                                }
                            } else {
                                if( option.options ){
                                    var subOptions = new Array( { innerHTML : option.innerHTML, selected : true } );
                                    subOptions = subOptions.concat( option.options );
                                    $( document.createElement("li") ).addClass("sub").append( agd.create.select( {options:subOptions, sub:true}) ).appendTo(ul);
                                } else {
                                    $( agd.create.li( option ) ).appendTo(ul);
                                }
                            }
                        });

                        // Aceso a la propiedad
                        div.ul = ul;


                        $( firstoption ).click(function(){
                            var listElement = $wrap[0];
                            var hide = function(e){
                                if( e ){
                                    var eSrc = e.originalTarget || e.target;

                                    if( $(eSrc).hasClass("checkbox-desplegable") ||
                                        $(eSrc).closest(".subselect").hasClass("subselect")
                                    ){
                                        $( document ).one("click", hide );
                                        return false;
                                    }
                                }

                                if( atributos.sub ){
                                    $(thiz).css("width", "100%" );
                                    $(listElement).css({"left":"", "width": "100%", "top":"","position":""});
                                }

                                $(thiz).removeClass("open").css("position","");
                                $(listElement).removeClass("open").addClass("closed").css("overflow", "hidden" );
                            };

                            if( $(listElement).css("overflow-y") == "hidden" ){
                                $(thiz).addClass("open");
                                //$(thiz).css("position","relative");
                                $(listElement).removeClass("closed").addClass("open");


                                if( atributos.sub ){
                                    offset = $(div).closest("ul").offset();
                                    parentLi = $(thiz).parent();
                                    parentLi.css("height", parentLi.height() );

                                    var offsetNow = $(thiz).offset(), top = offsetNow.top - offset.top + 11;

                                    var addWidth = $(listElement).outerWidth(), newWidth = addWidth - 10;
                                    $(thiz).css("width", ($(thiz).width()-10) );

                                    $(listElement).css({position:"absolute", width: newWidth}).animate({left:"-"+addWidth+"px",top:"-"+top+"px"});
                                }

                                window.setTimeout( function(){
                                    $( document ).one("click", hide );
                                }, 10 );

                                //---- Mostramos los checbox si es necesario
                                var rows = agd.func.selectedRows();

                                if( $(ul).attr("mode") == 1 && !$(thiz).hasClass("checkbox-created") && rows.length ){
                                    $("li", thiz).each(function(i, obj){
                                        var opt = this;
                                        if( i > 0 ){
                                            var checkbox = $( document.createElement("input") ).attr({
                                                type : "checkbox",
                                                name : $(opt).attr("name")
                                            }).addClass("checkbox-desplegable");

                                            //----- localizar y añadir
                                            var span = $("span", this);
                                            span.before( checkbox );

                                            //----- evento en checkbox
                                            checkbox.click(function(){
                                                $(span).toggleClass("strong");

                                                var applyOption = $(".apply-option", listElement ),
                                                    elementsChecked = $(":checked", listElement), len = elementsChecked.length;

                                                //----- Si hay elementos chequeados
                                                if( len  ){
                                                    //----- si no mostramos la opcion ya...
                                                    if( !applyOption.length ){
                                                        //------ la creamos...
                                                        var li = agd.create.li({
                                                            innerHTML:"Aplicar",
                                                            className:'apply-option'
                                                        });
                                                        $( li ).appendTo( ul ).click(function(){
                                                            var checked = $(":checked", listElement), l = checked.length;
                                                            var names = Array();
                                                            while(l--){
                                                                names.push( $(checked[l]).attr("name") );
                                                            }

                                                            var queryString = agd.func.array2url( "selected", agd.func.selectedRows() );
                                                            if( queryString.length ){ queryString += "&"; }
                                                            queryString += agd.func.array2url( "options", names );

                                                            var cncat = ( ahistory.curLocation.indexOf("?") != -1 ) ? "&" : "?";
                                                            var URI = ahistory.curLocation + cncat + queryString;

                                                            $.get( URI , function( data ){
                                                                data = agd.func.getJson( data );
                                                                agd.callback( data );
                                                            });
                                                        });
                                                    }
                                                } else {
                                                    //----- si existe la opcion
                                                    if( applyOption.length ){
                                                        //----- la eliminamos
                                                        $( applyOption ).remove();
                                                    }
                                                }
                                            });
                                        }
                                    });
                                    $(thiz).addClass("checkbox-created");
                                } else {
                                    $(".checkbox-desplegable", listElement).remove();
                                }

                                $(listElement).css("overflow", "visible").closest("tr").closest("td").css("overflow-y","visible");
                            } else {
                                hide();
                            };

                        });

                        $("li:not(.event)", ul ).addClass("event").click( function(e){
                            if( $( e.originalTarget ).hasClass("checkbox-desplegable") ){
                            } else {
                                if( !$(this).hasClass("multiple-action") ){
                                    agd.func.lineoption(this);
                                }
                            }
                        });

                        div.ajustar = function( sum ){
                            if( div.ajustado !== true ){
                                var altura = firstoption.outerHeight();
                                if( altura ){
                                    ul.css("height", altura );
                                }
                                div.css("visibility","visible");
                                div.ajustado = true;
                            }
                        }

                    }

                    div[0].rawoptions = atributos.options; //link para acceso rapido
                    return div;
                }
            }
        },

        //----- tipos de vista que tendra la aplicacion y funciones para trabajar con ellas
        views : {
            //----- permite cambiar el tipo de vista en el que estamos
            changeViewType : function( viewType, callback ){
                //----- if( typeof viewType === "undefined" ){ alert("Error al seleccionar la vista. Intenta F5"); return false; } else { alert(viewType); }

                //----- quizas solo queremos cambiar.... (por lo que sea)
                var callback = callback || function(){};

                //----- si estamos en otra vista, la quitamos de la pantalla
                $( agd.elements.main ).empty();


                //----- si ya tenemos la vista creada
                if( agd.views[ viewType ].created === true ){

                    //------ limpiamos de otros posibles botones, listas...
                    agd.views.cleanView( viewType );

                    //------- la establecemos como la vista activa
                    agd.views.activeView = viewType;

                    if( agd.views[ viewType ].object ){
                        $( agd.elements.main ).append( agd.views[ viewType ].object );}

                    //------- llamamos a la funcion de retorno
                    callback();
                } else {
                    //------- si el estado es false nunca se creo
                    if( agd.views[ viewType ].created == false ){
                        //------ creamos la vista
                        agd.views.createView( viewType, function(){
                            //------- rellamamos a esta funcion cuando este creada la vista
                            agd.views.changeViewType( viewType, callback );
                        });
                    }

                }
            },

            cleanView : function( viewType ){
                $.each( agd.views[ viewType ].elements , function( i, lugar){
                    if( lugar ){ $(lugar).empty(); }
                });
            },

            createView : function( viewType, callback ){

                var views = {
                    data : '<div><div id="data-menu-top"><div id="left-panel-title"></div><div class="line-options"></div>&nbsp;</div><table id="main-table"><tr><td id="left-panel"><div id="left-panel-container"></div></td><td id="spacer-panel"></td><td id="main-panel"><table class="data"><tbody><tr><td height="100%"><table id="table-container"><tr><td><table class="line-data" id="line-data"></table></td></tr></table></td></tr></tbody><tfoot><tr><th><div id="data-menu-bottom" class="left-bottom-rounded"><span class="right-bottom-line-options"></span><span class="bottom-line-options"></span>&nbsp;</div></th></tr></tfoot></table></td></tr></table></div>',
                    bigdata : '<div><div id="data-menu-top"><div id="left-panel-title"></div><div class="line-options"></div>&nbsp;</div><table id="main-table"><tr><td id="left-panel"><div id="left-panel-container"></div></td><td id="spacer-panel"></td><td id="main-panel"><table class="data"><tbody><tr><td height="100%"><table id="table-container"><tr><td id="bigdata-content"></td></tr></table></td></tr></tbody><tfoot><tr><th><div id="data-menu-bottom" class="left-bottom-rounded"><span class="right-bottom-line-options"></span><span class="bottom-line-options"></span>&nbsp;</div></th></tr></tfoot></table></td></tr></table></div>',
                    options : '<table width="100%" id="main-table"><tr><td id="left-panel"><div id="left-panel-container"></div></td><td id="spacer-panel"></td><td id="main-panel"><table width="100%" height="100%" class="options"><tbody><tr><td><div class="option-list"></div></td></tr></tbody></table></td></tr></table>'
                };
                agd.views[ viewType ].object = $( views[viewType] )[0];
                switch( viewType ){
                    case "data" :
                        agd.views[ viewType ].elements.options = $(".line-options", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.bottom = $(".bottom-line-options", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.bottomright = $(".right-bottom-line-options", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.table = $("#line-data", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.leftPanel = $("#left-panel-container", agd.views[ viewType ].object )[0];
                        //---- agd.views[ viewType ].elements.mainPanel = $("#main-panel", agd.views[ viewType ].object );
                    break;
                    case "options" :
                        agd.views[ viewType ].elements.title = $(".option-title", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.list = $(".option-list", agd.views[ viewType ].object )[0];
                        agd.views[ viewType ].elements.leftPanel = $("#left-panel-container", agd.views[ viewType ].object )[0];
                    break;
                };
                agd.views[ viewType ].created = true;
                callback();
            },

            //----- tipo de vista de opciones
            options : {
                created : false,
                object : Object,
                visible : false,
                elements : {}
            },

            //---- tipo de vista de simple
            bigdata : {
                created : false,
                object : Object,
                visible : false,
                elements : {}
            },

            //---- tipo de vista de datos
            data : {
                created : false,
                object : Object,
                visible : false,
                elements : {}
            },

            //------ tipo de vista de mensaje simple
            simple : {
                created : true,
                object : false,
                visible : false,
                elements : {}
            },

            currentElements : function(){
                return agd.views[ agd.views.current ].elements;
            },

            //------ LA VISTA ACTIVA
            activeView : String
        },

        //------ almacenara datos de las tablas que vamos viendo
        tables : {
            //----- crear una tabla
            create : function( sName ){
                //----- crear la tabla en el objeto agd
                agd.tables[ sName ] = {
                    //----- añadir lineas en la tabla
                    addData : function( lineName, lineData ){
                        agd.tables[ sName ].data[ lineName ] = lineData;
                    },
                    getData : function( lineName ){
                        if( lineName ){
                            return agd.tables[ sName ].data[ lineName ];
                        } else {
                            return agd.tables[ sName ].data;
                        }
                    },
                    data : {},
                    update : parseInt( ( new Date() ).getTime().toString().substring(0,10) )
                };

                return agd.tables[ sName ];
            },
            current : false
        },

        //---- CADA VEZ QUE EL HASH CAMBIA
        navegar : function( url, force ){

            if( !url ){ url = ahistory.curLocation; force = 1; }
            url = ( url ) ? url.replace('&amp;','&') : "";

            //---- indicamos que esta cargando...
            agd.loading = true;

            clearTimeout(slowTimeout);
            $loading.find("div").html(agd.strings.cargando + "...");

            //---- dejamos un tiempo, ya que cuando aparece la barra de carga da impresion de mayor lentitud
            var showLoadInterval = window.setTimeout(function(){
                if( agd.loading ){
                    $loading.show();
                    //$("#buscar").css("background-image","url("+ agd.loadingImage +")");
                };
            },300);

            // if no hash, or has is from any web modal
            if( !location.hash || location.hash.substring(1, 2) == '/' ){
                var initialHash = $(agd.elements.menu).find("li:first-child a").attr("href");
                if( initialHash ){
                    location.hash = initialHash;
                };
                return false;
            };


            try{
                if(  __asyncNavigation &&  __asyncNavigation.abort ){  __asyncNavigation.abort(); }
            } catch(e) {}

            try{
                $.each(agd.streaming.requests, function(i,ajax){ ajax.abort(); });
                agd.asynchronousCall.checkAjax();
            } catch(e){}

            //----- CACHE DE X SEGUNDOS
            var lastAccess = agd.cache.url[ url ];
            if( lastAccess && !force ){
                var current = ( new Date() ).getTime(), passTime = current-lastAccess.time, compareTime = lastAccess.json.cachetime || agd.cachetime;
                if( passTime < compareTime ){
                    if (window.ga) {
                        ga('send', 'pageview',  '/agd/#' + url);
                    }
                    agd.callback( lastAccess.json );
                    return;
                }
            }

            var page = ahistory.getPage();
            if( route = agd.routes[page] ){
                var queryURL = ahistory.getEncodedURI().replace(page, route);
            } else {
                var queryURL = ahistory.getEncodedURI();
            };

            var urlData = {type : "ajax", ct : ( new Date() ).getTime() };

            if (location.href.indexOf('embedded=true') !== -1) {
                urlData['embedded'] = 1;
            }

            //------ query normal
            __asyncNavigation = $.ajax({
                url: queryURL,
                beforeSend : function(xhr, settings){
                    if( agd.currentPage ) xhr.setRequestHeader("x-last-page", agd.currentPage );
                },
                data: urlData,
                error : function(XMLHttpRequest, textStatus, errorThrown){
                    clearTimeout(showLoadInterval);
                    agd.callbacks["default-error"](XMLHttpRequest, textStatus, errorThrown);
                },
                success: function(data, textStatus, XMLHttpRequest){
                    agd.currentPage = page;
                    clearTimeout(showLoadInterval);
                    if( !data ){ return agd.callbacks["default-error"](XMLHttpRequest, textStatus, "nodata"); }

                    if( $.trim(data) == "Inaccesible" ){  return agd.callbacks["default-error"](XMLHttpRequest, textStatus, "noaccess"); }
                    try{
                        oJson = agd.func.getJson( data );
                    } catch(e){
                        return agd.callbacks["default-error"](XMLHttpRequest, textStatus, "nojson");
                    }

                    if( oJson ){
                        agd.cache.seturl( url, oJson );
                        if (window.ga) {
                            ga('send', 'pageview',  '/agd/#' + url);
                        }

                        var appVersion = XMLHttpRequest.getResponseHeader('App-version');
                        if (appVersion && __rversion && appVersion != __rversion) {
                            return location.reload();
                        }

                        agd.callback( oJson, url );
                    } else {
                        return agd.callbacks["default-error"](XMLHttpRequest, textStatus, "parsejson");
                    }

                }
            });

        },

        actionCallback : function(data, url, callback){
            callback = callback || function(){};

            if( data.alert ){
                alert(data.alert);
            };

            var isRedirect = data.refresh || (data.action && data.action.go);

            if( !isRedirect && ( data.iface && data.iface != agd.iface ) || ( !data.iface && agd.iface != "docs" ) ){
                var iface = data.iface || "docs";
                //agd.setupInterface(data.iface || "docs");
                $bar = $("#top-bar-left");
                $bar.find(".current").removeClass("current");
                $bar.find("#" + iface).addClass("current");

                if( iface != "docs" ){
                    if( !agd.ifaces[iface] ){
                        if( data.selector && (ifaceHTML = data.selector["#main"]) ){
                            agd.ifaces[iface] = ifaceHTML;
                            delete(data.selector["#main"]);
                        } else {
                            $.getJSON(iface+"/", function(res){
                                agd.actionCallback(res, url, function(){
                                    agd.callback(data, url);
                                });
                            });
                            return false;
                        }
                    };

                    $("#main").html(agd.ifaces[iface]);

                    switch(iface){
                        case "analytics":
                            $(".head, #logo-cliente, #sub-head").hide();
                            agd.elements.main = "#analytics-data";
                            agd.elements.menu = $("#analytics-menu ul")[0];
                        break;
                        case "validation":
                            $(".head, #logo-cliente, #sub-head").hide();
                            agd.elements.main = "#analytics-data";
                            agd.elements.menu = $("#analytics-menu ul")[0];
                        break;
                        case "comment":
                            agd.elements.main = "#comment-data";
                            //Despite we are on comment iface, logically we're at docs
                            $bar.find("#comment").removeClass("current");
                            $bar.find("#docs").addClass("current");
                        break;
                    };
                } else {
                    $(".head, #logo-cliente, #sub-head").show();
                    agd.elements.main = $("#main")[0];
                    agd.elements.menu = $("#main-menu ul")[0];
                };

                agd.iface = iface;
            };


            /* CARGAMOS LOS ELEMENTOS SIMPLES *
            if( data.selector ){
                $.each(data.selector, function( selector, html ){
                    try {
                        $selector = $(selector);
                        if( $selector.length ){
                            $selector.html(html);
                        }
                    } catch(e){

                    };
                });
            };/**/

            if( data.load ){
                if( data.load.script ){
                    require(data.load.script, function(){
                        delete(data.load.script);
                        agd.callback(data);
                    });
                    return false;
                };

                if( data.load.style ){
                    $.each( data.load.style, function(i, styleName){
                        if (!$("link[href='"+styleName+"']").length){
                            var css = create("link", {type:"text/css",rel:"stylesheet",href:styleName} );
                            delete( data.load.style[i] );
                            head.appendChild(css);
                        }
                    });
                }
            };

            if( data.jGrowl ){
                $.jGrowl( data.jGrowl );
                //agd.func.jGrowl("multiple_info", data.jGrowl);
            }

            if( data.cbox ){
                modalbox.func.open({open:true, scrolling:false, html:data.cbox, returnFocus : false}, callback);
            }

            if (data.closebox) {
                modalbox.func.close();
            }

            if( data.open ){
                agd.func.open(data.open);
            }

            if (data.top) {
                window.scrollTo(0, 0);
            }

            if( data.action ){
                switch( data.action ){
                    default:
                        if (typeof data.action.force !== "undefined") {
                            var sameUrl = data.action.go.replace("#", "") == ahistory.curLocation;
                            if (sameUrl) {
                                agd.navegar();
                            } else {
                                location.href = data.action.go;
                            }
                        } else {
                            location.href = data.action.go;
                        }
                    break;
                    case "restore":
                        if( __currentUser ){
                            agd.func.open("../restore.php?cu="+ agd.un + "&u=" + __currentUser );
                        }
                        return false;
                    break;
                    case "back":
                        return history.back(1);
                    break;
                }
            };

            if( data.clear ){
                agd.func.clearSelectedItems(data.clear);
            }

            if( data.refresh ){
                agd.navegar();
            };

            if (data.html) {
                $.each(data.html, function(selector, html) {
                    var $target = $(selector);
                    $target.html(html).show();

                    agd.checkEvents($target);
                });
            }


            if (data.hightlight) {
                agd.func.registerCallback('hightlight', function() {
                    delete(agd.callbacks["hightlight"]);

                    var rows = agd.views[agd.views.activeView].elements.table.rows;
                    $.each(rows, function () {
                        if (this.uid == data.hightlight) {
                            $(this).effect("highlight", {color:"#fbec88"}, 2000);
                        }
                    });
                });
            }

            if( data.nofollow ){
                return false;
            };

            if (data.hideSelector) {
                if (typeof $(data.hideSelector) != "undefined") {
                    $(data.hideSelector).hide();
                }
            }


            callback();
            return true;
        },

        callback : function( response, url ){

            if( !response ){ return; }

            if( !agd.actionCallback(response, url) ){ return false; }
            var busqueda = ahistory.getValue("q");
            if( busqueda && busqueda.length ){
                $("#buscar").val(decodeURIComponent( busqueda.replace(':% ',':%25') ));
            }

            var StartTime = (new Date()).getTime(),
                currentPage = ahistory.getPage();

            if( "cache" in response && !response.cache ){
                delete(agd.cache.url[ url ]);
            };

            // --- remvoe residual tipsy
            $(".tipsy").remove();
            if (agd.tour && agd.tour.page != location.hash) {
                agd.tour.trigger('stop.tourbus');
            }

            //------ seleccionamos en el menu principal, donde estamos
            $(".seleccionado").removeClass("seleccionado");
            if (response.moduloseleccionado) {
                $(agd.elements.menu).find("li[name^='"+response.moduloseleccionado+"']").addClass("seleccionado");

                var $selectedMenu = $(agd.elements.menu).find("li.seleccionado");
                if (!$selectedMenu.length) {
                    $("#" + response.moduloseleccionado).addClass("seleccionado")
                }
            }

            //-------- mostrar la navegacion
            $( agd.elements.navegacion ).empty();
            $(".extra-table").remove();
            if( response.navegacion && response.navegacion.length ){
                $navegationlist = $( document.createElement("ul") ).appendTo( agd.elements.navegacion );
                $.each( response.navegacion, function(i, value){
                    $li = $( document.createElement('li') ).appendTo( $navegationlist );
                    if( typeof(value) == "string" ){
                        $li.html("<span>" + value + "</span>");
                    } else {
                        value = $.extend({}, value); // clonamos para que persista en cache si eliminamos alguna parte
                        var tag = value.tagName || "a", $navob = $(document.createElement(tag));
                        if( value.img ){
                            $(document.createElement("img")).attr(value.img).appendTo($li); delete(value.img);
                        }
                        $navob.prop( value ).appendTo( $li );
                    }
                });
                /*
                var wrapDiv = $( document.createElement('div') ).appendTo( agd.elements.navegacion );
                $.each( response.navegacion, function(i, value){
                    if( typeof(value) == "string" ){
                        $( document.createElement('div') ).addClass('texto').html(value).appendTo( wrapDiv );
                    } else {

                        var pdiv = $( document.createElement('div') ).addClass('texto').appendTo( wrapDiv );
                        if( value.img ){
                            $(document.createElement("img")).attr(value.img).appendTo(pdiv);
                            value = $.extend({}, value); delete(value.img); // clonamos y eliminamos para que persista en cache
                        }
                        $( document.createElement('a') ).attr( value ).appendTo( pdiv );
                    }
                    if( i < (response.navegacion.length-1) ){
                        $( document.createElement('span') ).html(" &raquo; ").appendTo( wrapDiv );
                    }
                });
                */
            }

            //------ alamacenamos y contrastamos datos de las tablas
            agd.tables.current = response.tabla;

            //----- si nuestra vista nunca se habia creado...
            if( !agd.tables[ agd.tables.current ] ){
                 agd.tables.create( agd.tables.current );
            }
            //agd.tables[ agd.tables.current ];

            var view = response["view"] || "simple";
            //----- se cambia la vista y a modo de callback indicamos que se ha de ejecutar
            agd.views.changeViewType( view, function(){

                /* CREAMOS Y MOSTRAMOS LOS ELEMENTOS COMO BOTONES, DESPLEGABLES... */
                //----- comprobamos lo elementos de la vista actual, si alguno de ellos esta en nuestro archivo json
                for( value in agd.views[ agd.views.activeView ].elements ){
                    if( agd.views[ agd.views.activeView ].elements[ value ] ){
                        $( agd.views[ agd.views.activeView ].elements[ value ] ).empty();
                        //----- si el valor existe
                        if( response[ value ] ){
                            //----- para cada tipo de elemento, creamos los elementos asociados
                            $.each( response[ value ], function( type, elements ){
                                //------ si existe una manera de crear el elemento
                                if( agd.create[ type ] ){
                                //------ para cada elemento del tipo dado
                                    $.each( elements, function(i, properties){
                                        //----- creamos el objeto
                                        var currentObject = agd.create[ type ]( properties );

                                        //------ incrustamos en el elemento de la vista actual selecionado
                                        $( agd.views[ agd.views.activeView ].elements[ value ] ).append( currentObject );
                                        if( currentObject.ajustar ){
                                            //------- si es la primera vez debemos borrar la precarga... TIENE QUE SER ANTES DE COMPROBAR LA POSICION DE LOS SELECT YA QUE SI NO NO EXISTEN
                                            agd.load( true );
                                            currentObject.ajustar();
                                        }
                                    });
                                }
                            });
                        }
                    }
                };


                if( response.selector ){
                    $.each( response.selector , function( selector, html ){
                        var $node = $(selector);
                        //if( !$node.html() ){
                            $node.html( html );
                        //}
                    });
                };

                //----FIN DE RECORRER LOS POSIBLES BOTONES, LISTAS...
                if (response["datos"] || response["extralines"]){
                    $(agd.views[agd.views.activeView].elements.table).empty();
                }

                var maxc = response.maxcolums || 0;


                /* Continuamos con busqueda */
                if (busqueda = response["busqueda"]) {
                    var row = $(document.createElement("tr")).addClass("table-group-title extra-line"),
                        td = $(document.createElement("td")).attr("colspan", maxc+3).appendTo(row);
                        $(row).appendTo( agd.views[ agd.views.activeView ].elements.table );

                    $formulario = $(document.createElement("form")).attr({"method":"GET","id":"form-descargables" }).appendTo(td);

                    $searchDiv = $(document.createElement("div")).appendTo($formulario);

                    $stringBuscar = $(document.createElement("span")).appendTo($searchDiv).html(agd.strings.buscar);

                    $busqueda = $(document.createElement("input")).attr({type:"text", name:"q", rel:"tr", target:"#", id:"qdescar"  }).val(busqueda.query)
                        .addClass("find-html").keyup(function(){
                            var value = encodeURIComponent($.trim( $(this).val() )).replace("%3A",":").replace("%23","#").replace("%2C",",");
                            if( value == "" ){
                                $formulario.submit();
                            }
                        }).appendTo($searchDiv).focus();

                    $button = $(document.createElement("button")).addClass("btn").attr({"type":"submit"}).html("<span><span>"+agd.strings.buscar+"</span></span>").appendTo($searchDiv);

                    $formulario.unbind().submit(function(){
                        var valueq = encodeURIComponent($.trim($busqueda.val())).replace("%3A",":").replace("%23","#").replace("%2C",",");
                        ahistory.updateValue({'q':valueq});
                        //var href = "#documentos.php?m="+modulo+"&poid="+parentID+"&comefrom="+comefrom+"&q="+valueq;
                        //location.href = href;

                        return false;

                    });
                };

                /* Continuamos con los datos */
                if (datos = response["datos"]) {

                    var ctable = agd.tables.current.split("-")[0],
                        numLines = 0,
                        group = {};

                    agd.views[agd.views.activeView].elements.table.className = "line-data " + ctable;
                    var targetTable = agd.views[agd.views.activeView].elements.table;

                    $.each(datos, function(i, linea) {

                        if (linea.group) {
                            var row = $(document.createElement("tr")).addClass("table-group-title extra-line"),
                                td = $(document.createElement("td")).attr("colspan", maxc+3).appendTo(row),
                                div = $(document.createElement("div")).appendTo(td).html(linea.group);

                            // --- ad row to table
                            $(row).appendTo(agd.views[agd.views.activeView].elements.table);

                            if (linea.searchable) {
                                $searchDiv = $(document.createElement("span")).css({"margin-left":"10px"}).appendTo(div);
                                $(document.createElement("input"))
                                    .attr({type:"text", placeholder:agd.strings.buscar, rel:"tr", target:"#" + linea.id })
                                    .addClass("find-html").appendTo($searchDiv);
                            };

                            if (i && linea.moveable && group.layer) {
                                $(div).addClass("resize-handler");

                                (function(row, id, layer){
                                    row.draggable({
                                        start: function(e, ui){
                                            $(this).data( "dimensions", { x : e.originalEvent.pageX, y : e.originalEvent.pageY, h : layer.height() });
                                        },
                                        drag: function(e, ui){
                                            var currentDimensions = { x : e.originalEvent.pageX, y : e.originalEvent.pageY }, initialDimensions = $(this).data( "dimensions");
                                            var diff = initialDimensions.y - currentDimensions.y, newHeight = initialDimensions.h - diff + "px";

                                            layer.height(newHeight).css("overflow-y", "scroll");
                                            agd.cache.save( id, newHeight );
                                        },
                                        stop: function(e, ui){
                                            layer.css("overflow-y", "scroll");
                                        }
                                    });
                                })(row, group.id, group.layer);


                                if( group.route ){
                                    group.layer.route = group.route;
                                    (function(layer, table){
                                        layer.bind("scroll", function(e){
                                            var diff = ( this.scrollHeight - this.scrollTop ) - layer.height();
                                            if( diff == 0 ){
                                                var p = ( layer.p ) ? layer.p + 1 : 1;
                                                layer.p = p + 1;
                                                $.get( layer.route + "&p=" + p, function(datos){
                                                    try {
                                                        var json = agd.func.getJson(datos), lineas = json.datos;
                                                        if( lineas ){
                                                            $.each( lineas, function( i, linea){
                                                                row = agd.func.rowFromData(linea, false, json.maxcolums, i, lineas.length);
                                                                $(row).appendTo( table );
                                                            });
                                                        } else {
                                                            layer.unbind("scroll");
                                                        }
                                                    } catch(e){}

                                                });
                                            }
                                        });
                                    })(group.layer, group.table);
                                };
                            };

                            group.row = $(document.createElement("tr")).addClass("tr-group-container").appendTo(agd.views[agd.views.activeView].elements.table);
                            group.td = $(document.createElement("td")).attr("colspan", maxc+3).addClass("clean-colum").appendTo(group.row);
                            group.layer = $(document.createElement("div")).addClass("data-layer-group").appendTo(group.td);
                            if (linea.id) { group.layer.attr("id", linea.id); }

                            if (i && linea.moveable && group.layer) {
                                $(group.layer).css("overflow-y", "scroll"); // Siempre con scroll así al aparecer este no se descuadra nada
                            };

                            group.route = linea.route;
                            group.id = "data-layer-" + linea.id;

                            if (linea.droppable && !agd.agent) {
                                (function(layer){
                                    $(layer)
                                    .bind("dragover", function(e){})
                                    .bind("dragleave", function(e){})
                                    .bind("drop", function(e) {
                                        var src = e.originalEvent.dataTransfer.getData("text"), cn = (linea.droppable.indexOf("?")!=-1)?"&":"?";
                                        if( $("#"+src).closest(".data-layer-group").get(0) !== layer.get(0) ){
                                            if( !isNaN(src) ){
                                                var url = linea.droppable + cn + "poid=" + src;
                                                $.post( url, function(data){
                                                    try {
                                                        agd.actionCallback( agd.func.getJson(data), url);
                                                    } catch(e){
                                                        alert("Error desconocido");
                                                    }
                                                });
                                            }
                                        }
                                    });
                                })(group.layer);
                            };


                            if (linea.css) {
                                group.layer.css(linea.css);
                            };

                            if (layerHeight = agd.cache.get(group.id)) { // si teniamos una altura predefinida
                                group.layer.height(layerHeight);
                            };

                            group.table = $(document.createElement("table")).addClass("table-group-container").appendTo(group.layer);
                            targetTable = group.table.get(0);
                        } else {
                            var row = agd.func.rowFromData(linea, null, maxc, i, datos.length, response);
                                ln = $(row).appendTo(targetTable)[0];

                            if (!i) {
                                targetTable.current = row;
                                $(row).addClass("current");
                                row.setShortCuts();
                            }

                            if (!numLines) {
                                agd.func.fixLayout(targetTable, row);
                            };

                            numLines++;
                        }
                    });


                    //----- ELEMENTOS DE LA PAGINACION
                    if ( response.asyncTable == agd.constants.async.empty || response.asyncTable == null || ahistory.getValue("force")  ){
                        ajaxAsyncCallStatus = undefined;
                    }

                    $(agd.views[ agd.views.activeView ].elements.bottomright).empty();
                    $(agd.views[ agd.views.activeView ].elements.bottom).empty();

                    if (response.paginacion) {
                        var realCurrentPaginationNumber = parseInt( ahistory.getValue("p") ), numberNext = 4, numberPrev = 4;
                        if( isNaN( realCurrentPaginationNumber ) ){ realCurrentPaginationNumber = 0;}


                        //------------- CREAMOS EL ELEMENTO QUE CONTENDRA LO RELACIONADO CON LA PAGINACION

                        var spanControlPaginacionRight = $( document.createElement("span") ).addClass("paginacion").addClass("rightElem"),
                        curPaginationNumber = realCurrentPaginationNumber+1;

                        //------------- MONTAMOS EL LINK PARA VOLVER ATRAS

                        if( realCurrentPaginationNumber > 0 ){
                            $( document.createElement("a") )
                                .attr({"href":"#" + ahistory.updateValue({"p": response.paginacion[0] },location.href), "class":"prev-page"})
                                .html( "&laquo; " + agd.strings.pagina_anterior )
                                .appendTo( spanControlPaginacionRight );
                        }

                        if (response.asyncTable != agd.constants.async.loaded && response.asyncTable != null){
                            var strPagination =  " " + agd.strings.pagina + " " +  curPaginationNumber + " " +  agd.strings.busqueda_alrededor + " " + response.paginacion["total"] + " ";
                        }else{
                            var strPagination =  " " + agd.strings.pagina + " " +  curPaginationNumber + " " +  agd.strings.de + " " + response.paginacion["total"] + " ";
                        }

                        $(document.createTextNode(strPagination)).appendTo( spanControlPaginacionRight );

                        if( response.paginacion[1] > realCurrentPaginationNumber ){
                            $(document.createElement("a"))
                                .attr({"href": "#" + ahistory.updateValue({"p": response.paginacion[1]},location.href), "class":"next-page" })
                                .html(agd.strings.pagina_siguiente + " &raquo;")
                                .appendTo( spanControlPaginacionRight );
                        }

                            //------------- CREAMOS UN ELEMENTO PARA ALINEAR A LA DERECHA TODOS
                        var flotanteDerecha = $(document.createElement("span")).css("float","right");
                        $( flotanteDerecha ).append( spanControlPaginacionRight ).appendTo(  agd.views[ agd.views.activeView ].elements.options );
                        $( spanControlPaginacionRight ).clone().appendTo( agd.views[ agd.views.activeView ].elements.bottomright );

                        if (response.asyncTable != agd.constants.async.loaded && response.asyncTable != null) {
                            var strPagination = agd.strings.mostrando_del + " " + response.paginacion.from + " " + agd.strings.al + " " + response.paginacion.to + " " + agd.strings.busqueda_alrededor + " " + response.paginacion.of;
                        }else{
                            var strPagination = agd.strings.mostrando_del + " " + response.paginacion.from + " " + agd.strings.al + " " + response.paginacion.to + " " + agd.strings.de + " " + response.paginacion.of;
                        }

                        $span = $( document.createElement("span") ).addClass("paginacion").addClass("leftElem")
                        .html( strPagination ).appendTo(
                            agd.views[ agd.views.activeView ].elements.bottom
                        );

                        if( response.paginacion["total"] > 1 ){
                            $span.append(" &nbsp; | &nbsp; " + agd.strings.pagina + " " );
                            $(document.createElement("input")).val(curPaginationNumber).attr({id:"paginacion-input",type:"text", size:3}).keypress(function(e){
                                if( e.keyCode == 13 ){
                                    var val = $(this).val();
                                    if ( typeof ajaxAsyncCallStatus !== 'undefined' && ajaxAsyncCallStatus === agd.constants.ajaxAsyncCallStatus.waiting && val > response.paginacion["total"] ){
                                        /* While we are retieving data fom the server */
                                        alert(agd.strings.proceso_busqueda_pagina_curso);
                                        return false;
                                    }else if  ( (response.asyncTable == agd.constants.async.loaded && (val > response.paginacion["total"] || isNaN(val) || val < 1) ) || ( response.asyncTable == null && (val > response.paginacion["total"] || isNaN(val) || val < 1)) || ( response.asyncTable == agd.constants.async.empty && (isNaN(val) || val < 1)) ){
                                    /* We already have the total number of pages in the DOM and the page the user has requested does not exist*/
                                        alert(agd.strings.error_buscando_pagina);
                                        return false;
                                    }else{
                                        /* We call to the page,does not matter if we have the page loaded or not  */
                                        ahistory.updateValue({"p": (val-1) });
                                    }
                                }
                            }).appendTo($span);
                        }

                        if ((response.asyncTable == agd.constants.async.empty || response.asyncTable == null) &&  ahistory.getValue("p") >= 4 && ahistory.getPage() == "buscar.php"){
                            // Cargamos asincronamente la totalidad de la tabla Si cargamos más de la página 5.
                            agd.func.asyncLoad();
                        }

                        if ( (typeof ajaxAsyncCallStatus!=='undefined') && (ajaxAsyncCallStatus === agd.constants.ajaxAsyncCallStatus.waiting) ){
                            var imgA = $(document.createElement("img")).attr({ src: agd.staticdomain + "/img/common/load.gif" }).css("margin-left","20px");
                            $('.paginacion.rightElem,.paginacion.leftElem').append(imgA);
                        }

                    } else {
                        $( document.createElement("span") ).addClass("paginacion").html(agd.strings.mostrando_del + " 1 " + agd.strings.al + " " + datos.length ).appendTo(agd.views[ agd.views.activeView ].elements.bottom);
                    }
                    if( $("#main table.data").height() < 250 ){
                        $("#main table.data").css("height","250px");
                    }
                    agd.func.showSelectedItems();
                } else {
                    if( !datos || !datos.length ){
                        if(ahistory.getValue("p") != 0 && typeof ahistory.getValue("p") != "undefined"   && ahistory.getPage() == "buscar.php"){
                            alert(agd.strings.error_buscando_pagina);
                            ahistory.updateValue({"p": 0 });
                            return;
                        }else{
                            var userMessage = response.ifnodata || agd.strings.no_resultados;
                            var innerHTMLrow = "<td class='clean-colum'><div class='message highlight'>" + userMessage + "</div></td>";
                            $( document.createElement('tr') ).html( innerHTMLrow ).appendTo( agd.views[ agd.views.activeView ].elements.table );
                        }
                    }
                };


                if (datatabs = response["datatabs"]) {
                    var $tr = $(document.createElement("tr")).addClass("extra-line table-group-title box-tabs"),
                        $td = $(document.createElement("td")).appendTo($tr),
                        $div = $(document.createElement("div")).addClass("box-tabs").appendTo($td);

                    $td.attr("colspan", maxc+3);


                    $(datatabs).each(function(i, tab){
                        var $tab = $(document.createElement("div")).addClass("box-tab");
                        if( tab.className ){ $tab.addClass(tab.className); }

                        if( tab.img ){ $(document.createElement("img")).attr("src", tab.img).appendTo($tab); }
                        var $a = $(document.createElement("span")).html(tab.innerHTML).appendTo($tab);
                        if( tab.title ) $a.attr('title', tab.title);
                        if( tab.count !== undefined ){ $(document.createElement("span")).addClass("super").html(tab.count).appendTo($tab); }
                        $tab.appendTo($div);

                        if(tab.href){
                            $tab.click(function(){ location.href = tab.href; });
                        };
                    });

                    $tr.prependTo(agd.views[ agd.views.activeView ].elements.table);
                };

                if (lineasExtra = response["extralines"]) {
                    var table = agd.views[agd.views.activeView].elements.table, offset = 0;
                    $.each(lineasExtra, function(i, html) {
                        i = isNaN(i) ? offset++ : i;
                        var $extraTR = $(table.insertRow(i)).addClass("extra-line");
                        var maxc = response.maxcolums || 0;
                        var extraTD = $(document.createElement("td")).attr("colspan", maxc+3).appendTo($extraTR);

                        if (typeof html == 'string') {
                            extraTD.html(html);
                        } else {
                            extraTD.html(html.innerHTML);
                            if (html.className) $extraTR.addClass(html.className);
                        }
                    });
                }


                //----- Siempre limpiamos el panel
                if( agd.views[ agd.views.activeView ].elements.leftPanel ){
                    $( agd.views[ agd.views.activeView ].elements.leftPanel ).empty();
                    if( acciones = response["acciones"] ){
                        //------ PARA CADA ACCION LA DIBUJAMOS EN LA PANTALLA
                        $.each( acciones , function(i, accion){
                            var obj =$( document.createElement('div') )
                            .addClass("module-options showname")
                            .attr({"title":accion.nombre, 'data-gravity':'w'})
                            //-----.addClass( accion.clase )
                            .html("<div><a href='"+accion.href+"' class='"+ accion.clase +"'><img src='"+ accion.img +"' /></a></div>")
                            .appendTo(  agd.views[ agd.views.activeView ].elements.leftPanel );
                        });
                    }
                }



                $(".helper").remove();
                if( "helpers" in response ){

                    function helperHandle(j, step){
                        var size, left, top, width, $filter;
                        try {
                            var $target = $(step.target), offset = $target.offset();

                            if( !step.html.length ){ return; }
                            if( !offset || offset.left == 0 ){
                                return setTimeout(function(){
                                    helperHandle(j, step);
                                }, 100);
                            };

                            size = step.html.length;
                            left = ( offset.left + 5 );
                            top = ( offset.top - 50 );
                            width = ( step.width && step.width != "0" ) ? step.width : Math.round(size*(0.05*size));
                            $filter = $(step.filter);


                            if( top < 15 ){
                                top = top + $target.height() + 70;
                                left = offset.left + (width/2);
                            }
                        } catch(e) {
                            return;
                        }

                        if( $filter.length ) { return; } // si existe pasamos

                        helperID = "helper_" + step.helper + "_" + j;
                        $helperDIV = $( document.createElement("div") ).addClass("helper").css({ width: width, top: top }).attr("id", helperID).appendTo( document.body );

                        $tableHelper = $(document.createElement("table")).addClass("helper-table").appendTo($helperDIV);
                            $helperTR = $(document.createElement("tr")).appendTo($tableHelper);
                            $contentTD = $(document.createElement('td')).appendTo($helperTR);
                            $arrowTD = $(document.createElement('td')).appendTo($helperTR).css("width", "48px");
                                $arrowDiv = $(document.createElement("div")).addClass("helper-arrow").appendTo($arrowTD );

                        var stepImg = step.img || 'new';
                        $img = $(document.createElement("img")).attr({ src: agd.staticdomain + "/img/common/"+ stepImg +".png" }).appendTo($contentTD);


                        $helperContent = $( document.createElement("div") ).addClass("helper-text").appendTo( $contentTD );
                        $(document.createElement("div")).addClass("helper-title").html("Dokify").appendTo($helperContent);
                        $p = $(document.createElement("p")).html( step.html ).appendTo($helperContent);

                        if( step.hide ){
                            $helperContent.append('<div class="helper-cancel"><a class="post toggle" href="helpercancel.php?comefrom='+ step.helper + '" target="#'+ helperID +'">No volver a mostrar</a></div>');
                        }

                        var leftDiff = left-$helperDIV.outerWidth();
                        if( leftDiff < 15 ){
                            $helperDIV.css({ left: left + $target.width() });
                        } else {
                            $helperDIV.css({ left:leftDiff });
                        }

                        var v = 200;
                        $cancelTarget = $(step.cancel_target);
                        var blink = function(){
                            $cancelTarget.animate({ color: "red" }, v, function(){
                                $cancelTarget.animate({ color: "black"}, v, function(){
                                    if(blink){ blink(); }
                                });
                            });
                        };
                        blink();

                        $cancelTarget.bind(step.cancel_event, function(){
                            $helperDIV.remove();
                            blink = null;
                        });

                        blink();

                        agd.checkEvents($helperContent);
                    };

                    $.each(response.helpers, function(i, helper){
                        $.each(helper, helperHandle);
                    });
                }


                //---- arreglamos el scroll en IE7
                if( $.browser.msie && $.browser.version <= 7 ){
                    var $table = $(agd.views[ agd.views.activeView ].elements.table), tableOffset = $table.offset();
                    if( tableOffset ){
                        var rightPos = (tableOffset.left + $table.width()), ww = $(window).width();
                        if( ( rightPos - ww  ) > 20  ){ // numero aprox
                            $("table.data").css("width", rightPos+"px");
                        }
                        if( rightPos < ww  ){
                            $("table.data").css("width", "");
                        }
                    }
                }

                //---- comprobamos los eventos
                agd.checkEvents(null, response);


                //---- si es la primera vez debemos borrar la precarga... TIENE QUE SER ANTES DE COMPROBAR LA POSICION DE LOS SELECT YA QUE SI NO NO EXISTEN
                agd.load( true );


                if( sTop = $(window).scrollTop() ){
                    if( $(".keep-visible").length ){
                        $(window).trigger('scroll');
                    }
                }

                agd.loading = false;

                if (response.tour) {
                    var tourLibs = [
                        agd.staticdomain + "/js/jquery/jquery.scrollTo.min.js?"+ __rversion,
                        agd.staticdomain + "/js/jquery/jquery.tourbus.min.js?"+ __rversion
                    ];

                    require(tourLibs, function(){
                        if (agd.tours[location.hash] === undefined) {
                            var $htmlTour = $(document.createElement("div")).html(response.tour);
                            var $selectorTour = $htmlTour.find('.tourbus-legs');

                            agd.tour = $selectorTour.tourbus({
                                target: "body",
                                urlDismiss: $selectorTour.data('dismiss'),
                                onStop: function() {},
                                onDestroy: function() {
                                    $.post(this.urlDismiss);
                                    $('.tourbus-overlay').hide();
                                    $('.tourbus-highlight').removeClass('tourbus-highlight');
                                },
                                onLegStart: function(leg, bus) {
                                    agd.checkEvents(leg.el);
                                    if (leg.rawData.highlight) {
                                        leg.$target.addClass('tourbus-highlight');
                                        $('.tourbus-overlay').show();
                                    }
                                },
                                onLegEnd: function(leg, bus) {
                                    if (leg.rawData.highlight) {
                                        leg.$target.removeClass('tourbus-highlight');
                                        $('.tourbus-overlay').hide();
                                    }
                                }
                            });

                            agd.tour.page = location.hash;
                            agd.tours[location.hash] = agd.tour;
                            agd.tour.trigger('depart.tourbus');
                        }
                    });
                }

            });
            //------ FIN DEL CALLBACK DEL CAMBIO DE LA VISTA
        },


        //------- FUNCION PARA COMPROBAR ASIGNAR LOS EVENTOS A LOS ELEMENTOS CARGADOS VIA AJAX
        checkEvents : function( where, json, nocallback ){
            var hideLoading = true;

            //----- INTENTAMOS LIMITAR EL CONTEXTO, PARA FAVORECER LA AGILIDAD
            if( where ){
                selector = $( where )[0];

                if( !selector || !selector.innerHTML.length ){
                    window.setTimeout(function(){ agd.checkEvents( where, json, nocallback );}, 0);
                    return false;
                }
            } else {
                selector = document.body;
            };

            var s = (new Date()).getTime()/1000;

            $(selector).find('.tag-list').each(function(i, dom) {
                require([agd.staticdomain + "/js/jquery/tag-it.min.js?"+ __rversion], function(){
                    var name = $(dom).attr('name');

                    function onSizeChange () {
                        var input = $(dom.nextSibling).find('input');
                        if (modalbox.exists()){
                            modalbox.func.resize();
                            $(input).focus();
                        }
                    };

                    $(dom).tagit({
                        filedName: name,
                        afterTagAdded: onSizeChange,
                        beforeTagAdded: onSizeChange
                    });

                });
            })


            $(selector).find('#frameopen').each(function () {
                $(agd.elements.asyncFrame).attr("src", $(this).val());
            });


            $(selector).find('.map').each(function () {
                var map = this;
                require([agd.staticdomain + "/js/maps.min.js?" + __rversion], function(mapHandler) {
                    require(["https://maps.googleapis.com/maps/api/js?key="+ agd.gkey +"&sensor=false&callback=onMapsLoaded"], function() {
                        new mapHandler(map);
                    });
                });
            });


            /***
               * Dado un input file, lo enviamos al servidor para saber cual es el siguiente paso de un proceso
               *
               *
               *
               */
            $(selector).find('.validate-file').each(function () {
                var _this, link, path, img, loadingStr, defaultText;

                _this       = this;
                path        = $(this).data('url');
                link        = $($(_this).data('link'));
                defaultText = link.html();
                loadingStr  = agd.strings.cargando + '...';



                require([agd.staticdomain + "/js/uploader.min.js?"+ __rversion], function (AsyncUploader) {
                    var uploader = new AsyncUploader(_this), tout;

                    if (agd.usermaxfile) uploader.setMaxSize(agd.usermaxfile);

                    $(document).one(modalbox.event.cleanup, function () {
                        uploader.abort();
                    });

                    $(uploader).on('complete', function (e, response, statusCode) {
                        window.modalconfirm = false;

                        function restore (err) {
                            $loading.hide();
                            link.html(defaultText).removeAttr('disabled');
                            return alert(err);
                        }

                        if (statusCode == 500) {
                            return restore(response);
                        }

                        if (response) {
                            agd.actionCallback(response);
                        } else {
                            return restore(agd.strings.error_desconocido);
                        }
                    });

                    $(uploader).on('progress', function (e, progress, XHRProgress) {
                        if (progress == 100) {
                            link.html(agd.strings.procesando_espera);
                        } else {
                            if (tout) clearTimeout(tout);
                            link.html(loadingStr.replace('...', ' ' + progress + '%...'));

                            tout = setTimeout(function () {
                                link.html(agd.strings.procesando_espera);
                            }, 200);
                        }
                    });

                    $(_this).on('change', function () {
                        window.modalconfirm = true;
                        link.attr('disabled', true);
                        $loading.show();
                        link.html(agd.strings.procesando_espera);

                        uploader.submit(path);
                    });
                });
            });


            /***
               * Dado un input file, generamos un link publico y lo ponemos en el textarea
               *
               *
               * @data link
               * @data target
               * @data disclaimer
               *
               *
               */
            $(selector).find('.text-attach').each(function () {
                var _this = this, img, $target, $link, $disclaimer, loadingStr, $loading;

                img = agd.staticdomain + "/img/common/load.gif?"+ __rversion;
                loadingStr = agd.strings.cargando + '... &nbsp; <img src=' + img + ' />';
                $loading = $(document.createElement('div')).html(loadingStr);
                $link = $($(_this).data('link'));
                $target = $($(_this).data('target'));
                $disclaimer = $($(_this).data('disclaimer'));

                // --- preload image
                document.createElement('img').src = img;

                require([agd.staticdomain + "/js/uploader.min.js?"+ __rversion], function (AsyncUploader) {
                    var uploader = new AsyncUploader(_this);
                    if (agd.usermaxfile) uploader.setMaxSize(agd.usermaxfile);

                    $(document).one(modalbox.event.cleanup, function () {
                        uploader.abort();
                    });

                    $(uploader).on('progress', function (e, progress, XHRProgress) {
                        $loading.html(loadingStr.replace('...', ' ' + progress + '%...'));
                    });

                    $(uploader).on('complete', function (e, link, statusCode) {
                        window.modalconfirm = false;

                        $loading.remove();
                        $link.show()

                        if (!link) {
                            switch (statusCode) {
                                case 413:
                                    alert(agd.strings.limite_upload_file.replace('%s', agd.func.formatBytes(agd.usermaxfile)));
                                break;
                                default:
                                    alert(agd.strings.error_desconocido);
                                break;
                            }

                            return false;
                        }

                        var text = $target.val();
                        $target.val(text + "\n" + link + "\n").focus();
                        $disclaimer.show().css('visibility', '');
                    });

                    $(_this).on('change', function () {
                        window.modalconfirm = true;
                        $link.before($loading);
                        $link.hide();

                        uploader.submit('/getpubliclink.php');
                    });

                });
            });


            $(selector).find(".affect-to-requirement").click(function(e){
                var target = $(this).data("target"),
                    selected = $(this).data("selected"),
                    trigger = $(this).data("trigger"),
                    focus = $(this).data("focus"),
                    counter = $(this).data("counter"),
                    from = $(this).data("from"),
                    selectorFrom = $(this).data("selectorfrom"),
                    total = $(this).data("total"),
                    totalText = $(this).data("totaltext"),
                    id = $(this).data("id"),
                    selectorId = $(this).data("targetid"),
                    selectedMap = [],
                    stringRequest = '';

                $(selected+" :input").each(function() {
                    selectedMap.push($(this).val());
                });

                $(target+" :input").each(function(i, ob){
                    if ($.inArray(ob["value"], selectedMap) == -1) {
                        $('input[value='+ob["value"]+']').attr('checked', false);
                    } else {
                        $('input[value='+ob["value"]+']').attr('checked', true);
                    }
                }).one('click', function() {
                    $(selectorFrom).parent().hide();
                    $(selectorId).val('');
                })

                var checkedNum = $(target+" :input:checked").length;

                $(counter).data('counter', checkedNum);
                if (checkedNum == 1) {
                    stringRequest = $(target+" :input:checked").data("visiblename");
                } else if (checkedNum == total) {
                    stringRequest = totalText;
                } else if (typeof $(counter).data('addtext') != "undefined") {
                    stringRequest += checkedNum+" "+ $(counter).data('addtext');
                } else {
                    stringRequest = checkedNum;
                }

                if (id && selectorId) {
                    $(selectorId).val(id);
                    $(selectorFrom).html(from);
                    $(selectorFrom).parent().show();
                }

                $(counter).html(stringRequest);
                $("body,html").animate({scrollTop:"0px"});
                if ($(focus)) {
                    $(focus).focus();
                }

                e.stopImmediatePropagation();
                return false;
            });


            $(selector).find('select.go').change(function () {
                var val, target;

                val = $(this).val();
                target = (target = $(this).data('target')) ? target : $(this.options[this.selectedIndex]).data('target');

                if (target == 'form') {
                    if (this.form) {
                        $.data(this.form, "sender", this);
                        $(this.form).submit();
                    }

                    return false;
                };

                if (target == 'top') return location.href = val;
                if (val) agd.func.open(val);
            });

            $(selector).find('.update-html').each(function(i, dom){
                var $this = $(this),
                    href = $(this).data('href'),
                    interval = $(this).data('interval'),
                    fn = function() {
                        if (!$this.parent()) return false;

                        $.get(href, function(res){
                            var json = agd.func.getJson(res);
                            if (json) {
                                agd.actionCallback(json);
                            } else {
                                if (res) {
                                    $this.html(res);
                                    setTimeout(fn, interval);
                                }
                            }
                        });
                    };

                fn();
            });



            $(selector).find("a.timerefresh").on("click", function(){
                if( !(href = $(this).data("default-href")) ){
                    href = $(this).attr("href");
                    $(this).data("default-href", href);
                }

                var newhref = href + (href.indexOf('?') !== -1 ? '&' : '?') + "timerefresh=" + (new Date()).getTime();
                $(this).attr("href", newhref);
            });

            $(selector).find(".bonus").on("click", function(){
                var href = $(this).attr("href");

                if( window[href] ){
                    try {
                        window[href].stop();
                        delete(window[href]);
                    } catch(e) {};
                } else {
                    require([agd.staticdomain + "/js/bonus/"+href+".js?"+(new Date()).getTime()], function(){
                        try {
                            window[href].init();
                        } catch(e) {};
                    });
                };

                return false;
            });

            $(selector).find("input.alternative").click(function(){
                var $src = $($(this).data("src")),
                    $target = $($(this).data("alternative")),
                    txt = $(this).data("alert")
                ;

                if( this.checked ){
                    $src.attr("disabled", true);
                    if( nval = $(this).data("src-value") ) $src.val(nval);
                    $target.closest("div[style]:hidden").show();
                    if(txt) alert(txt);
                    if( modalbox.exists() ){ modalbox.func.resize(); }
                    $target.focus();
                } else {
                    $src.removeAttr("disabled");
                }

            });

            $(selector).find("*[data-animate]").each(function(){
                var animation = $(this).data('animate').split(";"),
                    l = animation.length, param = {};

                for(i=0; i<l; i++){
                    var propval = animation[i].split(":");
                    param[propval[0]] = propval[1];
                };

                $(this).animate(param);
            });

            $(selector).find("img[data-src]").each(function(){
                $(this).attr("src", $(this).data("src")).removeAttr("data-src");
            });

            $(selector).find(".extended-cell").extendedCells(function(){
                agd.checkEvents(this);
            });

            $(selector).find('.selectorcolor').each(function(i,e){
                if( !window.cp ){
                    require([agd.staticdomain + "/js/jquery/colorpicker.js?"+ __rversion], function(){
                        agd.checkEvents(selector, json, nocallback);
                    });
                    return false;
                };

                var $this = $(this);
                $(e).ColorPicker({
                    onChange: function(hsb, hex, rgb){
                        var val = "#"+hex, target = $this.attr("target"), prop = $this.attr("rel");
                        $(target).css( prop, val);
                        $this.val(val);
                    }
                });
            });


            $(selector).find(".grafico").each(function(i, grafico){
                if( !$.jqplot ){
                    require([agd.staticdomain + "/js/jquery/jquery.jqplot.min.js?"+ __rversion], function(){
                        agd.checkEvents(selector, json, nocallback);
                    });
                    return false;
                };

                var $this = $(this), type = $this.attr("char-type") || "";

                //agd.func.registerCallback("graficos", function(){
                var src = $this.attr("data");
                if( src ){
                    $.ajax({
                        method : "GET",
                        url : src,
                        success : function(data){
                            var Xaxis = data.xaxis || {},
                                Yaxis = data.yaxis || {},
                                series = data.series || {},
                                showLegend = (data.legend==false) ? false : true
                            ;

                            $.jqplot.config.enablePlugins = true;
                            try {
                                var renderer = $.jqplot[type];
                                var plot = $.jqplot(grafico, data.data, {
                                    title: data.title || "Grafico",
                                    series : series,
                                    legend: {
                                        show: showLegend
                                    },
                                    seriesDefaults: {
                                        renderer : renderer,
                                        showMarker: true,
                                        pointLabels:{location:'no', ypadding:5}
                                    },
                                    axes: {
                                        xaxis: Xaxis,
                                        yaxis: Yaxis
                                    }
                                });
                            } catch(e) { agd.func.jGrowl(e.message); };
                        }
                    });
                }
                //});
            });

            $(selector).find(".calendario").each(function(){
                if ($.fullCalendar === undefined) {
                    var csspath = agd.staticdomain + "/css/fullcalendar.min.css?"+ __rversion,
                    css = $(document.createElement('link')).attr({type:"text/css", rel:"stylesheet", href: csspath }).get(0);

                    window.head.appendChild(css);
                    require([agd.staticdomain + "/js/jquery/fullcalendar.min.js?"+ __rversion], function(){
                        agd.checkEvents(selector, json, nocallback);
                    });

                    return false;
                };

                // --- dont hide loading, calendar do that
                hideLoading = false;

                var cal = this, srcs = $(this).attr("src").split('|');


                $(cal).fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                    },
                    timeFormat: 'HH:mm',
                    aspectRatio: 3,
                    firstDay: 1,
                    //------- weekends: false, // will hide Saturdays and Sundays
                    dayClick: function(date, allDay, event, view) {
                        if( $(event.target).hasClass("fc-day-number") ){
                            var href = "empresa/eventos.php?month="+(date.getMonth()+1)+"&day="+date.getDate()+"&year="+date.getFullYear()+"&poid="+ahistory.getValue("poid");
                            agd.func.open(href);
                        }
                    },
                    eventSources: srcs,
                    loading: function (isLoading) {
                        if (isLoading) $loading.show();
                        else $loading.hide();
                    },
                    viewDisplay: function(event, ui) {
                        // --- run this once
                        if (this.init === undefined) {
                            var hashView = ahistory.getValue('viewType') || 'month',
                                hashTime = ahistory.getValue('time');

                            //console.log(event, event.name, hashView);
                            if (event.name != hashView) event.calendar.changeView(hashView);
                            if (hashTime && event.calendar.getDate().getTime() != hashTime) {

                                var newDate = new Date(parseInt(hashTime));
                                event.calendar.gotoDate(newDate);
                            }

                            return this.init = true;
                        }

                        var keyValue = {
                            time: event.start.getTime(),
                            viewType: event.name
                        };

                        var newLocation = "#" + ahistory.updateValue(keyValue, true);
                        ahistory.set(newLocation);

                        return false;
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        var href = ( $(jsEvent.target).attr("href") )? $(jsEvent.target).attr("href") :calEvent.href;
                        if( href.split("")[0] == "#" ){
                            location.href = href;
                        } else {
                            agd.func.open(href);
                        }
                    }
                    //------- put your options and callbacks here
                });
            });


            $(selector).find(".async-info-load").each(function(i, ob){


                var callbackRun = function() {
                    var $this = $(this), href = $this.attr("href");

                    if (!$.trim(href)) return false;
                    if (!$this.hasClass("async-info-load")) return false;

                    var html = $this.html(), $img = $(document.createElement("img")).attr("src", agd.inlineLoadingImage);
                    if( $this.parent().hasClass("form-colum-value") ){
                        $this.parent().parent().find(".form-colum-description").append($img);
                    } else {
                        $this.empty().append($img, html );
                    }

                    agd.streaming.requests[ href ] = $.get( href , function(data){
                        $img.remove();
                        $this.html(data);
                        agd.checkEvents($this);
                    });


                    if ($this.hasClass("once")) {
                        $this.removeClass("async-info-load once");
                    }
                }

                if ($(this).is(":visible")) {
                    callbackRun.apply(this);
                } else {
                    $(this).on('appears', function(event) {
                        callbackRun.apply(this);
                    });
                }
            });


            $(selector).find("input.enable-button").click(function(){
                var $target = $($(this).attr("target"));
                if( this.checked ){ $target.removeAttr("disabled"); }
                else { $target.attr("disabled", true); }
            });

            $(selector).find(".close-confirm").click(function(){
                window.modalconfirm = false;
            });

            $(selector).find(".close-confirm").each(function(){
                var txt = $(this).data('confirm') || agd.strings.confirmar_abandonar;
                window.modalconfirm = txt;
            });

            $(selector).find("input.disable-button-anychecked").each(function(){
                var $target = $($(this).attr("target")), selector = "input", filter = $(this).data('filter');
                if (filter) selector += filter;


                if( this.checked ){ $target.attr("disabled", true); }
                else { $target.removeAttr("disabled"); }


                $(this).click(function(){
                    var $target = $($(this).attr("target")), anychecked = false;

                    if (this.checked){
                        $target.attr("disabled", true);
                    } else {
                        var inputs = $(selector).find(selector).toArray();
                        for (i in inputs) {
                            if (inputs[i].checked) {
                                anychecked = true;
                                break;
                            }
                        };

                        if (!anychecked) {
                            $target.removeAttr("disabled");
                        }
                    }
                });
            });



            $(selector).find("select[multiple] option").dblclick(function(){
                $(this).removeAttr("selected");
            });


            $(selector).find("option.add-option").each(function () {
                var $option = $(this), $select = $(this).parent(), $options = $select.find('option'), index = $options.index($option);

                $select.change(function(){
                    var $current = $(this).children(":selected");
                    if (index === $options.index($current)) {
                        var attrs = agd.func.getAttributes($select);

                        $(document.createElement("input")).attr(attrs).prop({type:"text"}).insertBefore($select);
                        $select.remove();
                    }
                });
            });

            /** RELACIONAR CAMPOS DENTRO DE UN FORMULARIO **/
            if( "#"+$(selector).attr("id") == modalbox.body ){

                $(selector).find("form").each(function(i, form){
                    var $form = $(this);

                    var onSelectChange = function(e){
                        var $td = $(this).parent(),
                            affects = $td.data("affects").split(','),
                            parts = $td.data("parts"),
                            val = $(this).val(),
                            compValue = false;

                        $.each(affects, function(i, affect){
                            $target = $form.find("tr#form-line-"+affect);
                            if (parts) {
                                compValue = (parts.toString().charAt(0) == "!") ? !(parts.toString().indexOf(val) === -1) : parts.toString().indexOf(val) === -1;
                            } else compValue = false;

                            if( compValue || (!parts && $td.parent().css("display") == "none") ){
                                var $select = $target.hide().find("select");//.change();
                            } else {
                                var $select = $target.show().find("select");//.change();
                            }

                            if( $select && $select.parent().data("affects") ){
                                setTimeout(function(){
                                    onSelectChange.call($select);
                                }, 0);
                            } else {
                                setTimeout(modalbox.func.resize, 0);
                            }
                        });
                    };

                    $(form).find("td[data-affects]").each(function(){
                        var $this = $(this);

                        $this.find("input[type=checkbox]").change(function(){
                            var affects = $this.data("affects").split(',')
                            $.each(affects, function(i, affect){
                                $affect = $(form).find("tr#form-line-"+affect).slideToggle(modalbox.func.resize);
                            })
                        });

                        $this.find("select").change(onSelectChange);
                    });
                });



                (function(selector){

                    var submitSingleField = function(){
                        var $item = $(this),
                            domNode = $item.get(0);
                            tagName = domNode.tagName.toLowerCase(),
                            $form = $item.closest("form"),
                            action = $form.attr("action"),
                            value = $item.val(),
                            attrs = agd.func.getAttributes(this);
                        $item.removeClass("fail");

                        if( agd.func.validateForm($form) ){
                            setTimeout(function(){
                                var postData = {};

                                if( tagName == "input" && domNode.type == "text" ){
                                    if( $item.hasClass("slider-value") ) {
                                        $item.val(value);
                                    } else {

                                        var $span = $(document.createElement("span")).attr(attrs).insertBefore($item);

                                        $span.html(value);

                                        if( value.length ){
                                            $span.removeClass("empty");
                                        } else {
                                            $span.addClass("empty");
                                        };

                                        if( $item.hasClass("datepicker") ){
                                            var parts = value.split("/");
                                            value = parts[2] + "-" + parts[1] + "-" + parts[0];
                                        };

                                        // cuando clonamos un elemento datepicker, los atributos del elemento (attrs) se están copiando
                                        // en el momento en el que el datepicker está abierto (y tiene la clase hasDatepicker, que el plugin)
                                        // usa internamente para controlar que ya está desplegado y por eso no se puede usar más de una vez.
                                        if ($span.hasClass('hasDatepicker')) {  $span.removeClass('hasDatepicker'); }

                                        $item.remove();
                                    }
                                };
                                postData[ $item.get(0).name ] = value;
                                if (action.indexOf("poid") === -1) {
                                    if (typeof $form.find("input[name=poid]") != "undefined") {
                                        postData["poid"] = $form.find("input[name=poid]").val();
                                        postData["send"] = 1;
                                    }
                                }
                                $.post(action, postData, function(result){
                                    if( result == "ok" ){ agd.navegar(); }
                                    if( result == "error" || result.indexOf('error') !== -1 ){
                                        if (typeof $span !== "undefined") {
                                            agd.func.addInputAlert( $span.get(0), "No Valido");
                                        } else if (typeof $item !== "undefined") {
                                            agd.func.addInputAlert( $item.get(0), "No Valido");
                                            $item.val(null);
                                        }
                                    }
                                    if( result.indexOf('mysql') != -1 ) {
                                        // agd.func.addInputAlert( $span.get(0), agd.strings[result]);
                                        agd.func.addInputAlert( $span.get(0), "Error: ya existe")
                                    }
                                    if (result == "null") {
                                        if (typeof $span !== "undefined") {
                                            agd.func.addInputAlert($span.get(0), "Sin Cambios");
                                            setTimeout(function(){
                                                agd.func.removeInputAlert($span.get(0));
                                            }, 1000);
                                        } else if (typeof $item !== "undefined") {
                                            agd.func.addInputAlert($item.get(0), "Sin Cambios");
                                            setTimeout(function(){
                                                agd.func.removeInputAlert($item.get(0));
                                            }, 1000);
                                        }
                                    } else {
                                        if (typeof $span !== "undefined") {
                                            agd.func.addInputAlert( $span.get(0), result);
                                        } else if (typeof $item !== "undefined") {
                                            agd.func.addInputAlert( $item.get(0), result);
                                        }
                                    }
                                });
                            }, 100);
                            return true;
                        } else {


                            return false;
                        }
                    };

                    $(selector).find(".agd-form").on("change", "select.editable, input.slider-value", function(){
                        submitSingleField.call(this);
                    });

                    $("input.slider-value", selector).unbind("keypress").keypress(function(e){
                        if( e.keyCode == 13 ){$(this).blur();}
                    });

                    $(selector).find(".agd-form").on("blur", "textarea.editable", submitSingleField);

                    $(selector).find(".agd-form").on("click", "span.editable, input.editable", function(){
                        var $this = $(this),
                            $parent = $this.parent(),
                            $form = $parent.closest("form"),
                            tagName = $(this).data("tagname"),
                            type = $this.attr("type"),
                            $item = $(document.createElement(tagName)),
                            attrs = agd.func.getAttributes(this);

                        $item.attr(attrs);

                        if( this.tagName.toLowerCase() != "span" ){
                            switch( this.tagName.toLowerCase() ){
                                case "input":
                                    switch( $this.attr("type").toLowerCase() ){
                                        case "checkbox":
                                            $this.val( (this.checked)?"1":"0" );
                                            submitSingleField.call($this);
                                        break;
                                        case "text":
                                            if ( $this.hasClass("slider-value") ) {
                                                submitSingleField.call($this);
                                            };
                                        break;
                                            //if( $this.hasClass("fail") ){ return; }

                                    };

                                break;
                            };

                            return true;
                        } else {
                            $parent.width($parent.width());

                            if( tagName == "input" ){
                                $item.val($this.text());
                            };

                            $item.insertBefore($this);
                            $this.remove();

                            agd.checkEvents($item.parent());
                            if( $item.hasClass("datepicker") ){
                                $item.bind("onDateChange", submitSingleField);
                            } else {
                                if( $item.hasClass("slider") ){

                                } else {
                                    $item.blur(function () {
                                        var submitted = submitSingleField.call(this);

                                        if (submitted === false) {
                                            $item.remove();
                                            $parent.append($this);
                                        }
                                    });
                                }
                            };

                            $item.focus().keypress(function(e){
                                if(e.keyCode==13){
                                    var submitted = submitSingleField.call($item);

                                    if (submitted === false) {
                                        $item.remove();
                                        $parent.append($this);
                                    }
                                }
                            });
                        };

                        return true;
                    });
                })(selector);



                //------ deslizadores de jquery
                var showCurrentValue = function(slider, evType){
                    var val = $(slider).slider("value"), divide = $(slider).attr("divide");
                    slider.value = val;
                    if( divide ){
                        val = Math.round(val / divide);
                    }
                    $(slider.nextSibling).val( val );

                    if( evType == "slidechange" && $(slider.nextSibling).hasClass("editable") ){
                        $(slider.nextSibling).trigger("click"); // hacemos esto para enviar el dato
                    };

                };

                $(selector).find(".slider").each(function(i, object){
                    var minimun = ( $(object).attr("min") ) ? parseInt($(object).attr("min")) : 0;
                    $( object ).slider({
                        'min' : minimun,
                        'max' : parseInt($(object).attr("count")),
                        'value' : parseInt($(object).attr("value")),
                        animate: true
                    }).unbind("slidechange").bind( "slidechange", function(event, ui) {
                        showCurrentValue(this, "slidechange");
                    }).unbind("slide").bind( "slide", function(event, ui) {
                        showCurrentValue(this, "slide");
                    });

                    showCurrentValue(object);

                    $( object.nextSibling.form ).submit(function(){
                        $(object.nextSibling).val( object.value );
                    });
                });
            }; // Fin eventos dentro de modal




            $(selector).find('.search-data').each(function(){
                var $this = $(this), q = $this.attr("src"), href = $this.attr("href") || "", src = "buscar.php?p=0&q=" + encodeURIComponent(q) + "&" + href;
                $this.addClass("loading");

                var $table = $(document.createElement("table")).addClass("line-data").appendTo($this);


                var drawJson = function(json){
                    $this.removeClass("loading");

                    if( json && json.datos ){
                        $.each(json.datos, function(i){
                            delete(this.inline);
                            //delete(this.options);

                            var row = agd.func.rowFromData(this);
                            $table.append(row);
                        });
                    } else {
                        $row = $(document.createElement("tr"));
                        $td = $(document.createElement("td")).addClass("clean-colum").appendTo($row);
                        $(document.createElement("div")).addClass("message highlight").html(agd.strings.no_resultados).appendTo($td);
                        $table.append($row);
                    }
                };

                var lastAccess = agd.cache.url[ src ], current = ( new Date() ).getTime(), passTime = null;
                if( lastAccess ){
                    passTime = current-lastAccess.time;
                }

                if( passTime && passTime < agd.cachetime ){
                    drawJson( lastAccess.json );
                } else {
                    $.getJSON(src, function(json){
                        agd.cache.seturl( src, json );
                        drawJson(json);
                    });
                }
            });

            $(selector).find('.confirm').click(function(e) {
                var $this = $(this), txt = $this.data('confirm') || $this.data('confirm-once') || agd.strings.continuar;

                // exception
                if ($this.hasClass("multiple-action")) return true;

                if ($this.hasClass("confirm")) { // no nos interesa si es de este tipo, tiene metodo propio, necesita la misma clase .confirm
                    if ($this.data('confirm-once')) $this.removeClass('confirm'); // no more confirms

                    if (!confirm(txt)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                }
            });

            $(selector).find(".lock-inputs-like-radio").click(function(){
                var $this = $(this),
                    context = $this.data("ctx") ? $($this.data("ctx")) : $("form"),
                    filterSelector = $this.data('filter'),
                    selector = "input"
                ;

                if (filterSelector) selector += filterSelector;

                if( this.checked ){
                    context.find(filterSelector).not(this).attr({disabled:true}).removeAttr("checked");
                } else {
                    context.find(filterSelector).removeAttr("disabled");
                }
            });

            $(selector).find('.confirm-to-off').on("change", function(e) {
                var $this = $(this), txt = $this.data('confirm') || $this.data('confirm-once') || agd.strings.continuar;
                // exception
                if ($this.hasClass("multiple-action")) return true;

                if (!this.checked && $this.hasClass("confirm-to-off")) { // no nos interesa si es de este tipo, tiene metodo propio, necesita la misma clase .confirm
                    if ($this.data('confirm-once')) $this.removeClass('confirm-to-off'); // no more confirms

                    if (!confirm(txt)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                }
            });

            $(selector).find(".lock-inputs").each(function(){
                var $this = $(this), blockname = $(this).data("blockname") || "*", $nodes = $(this).find(">"+blockname);
                if( $nodes.length ){
                    var tagName = $nodes.get(0).tagName;
                    $this.find("input").click(function(){
                        if( this.checked ){
                            var $parent = $(this).closest(tagName);
                            $this.find( tagName ).not( $parent ).find("input").attr({disabled:true}).removeAttr("checked");
                        //  $parent.find("input").removeAttr("disabled");
                        } else {
                            var $parent = $(this).closest(tagName);
                            $this.find( tagName ).not( $parent ).find("input").removeAttr("disabled");
                            // $this.find("input").removeAttr("disabled");
                        }
                    });
                }
            });

            $(selector).find(".simular").click(function(e){
                agd.func.simular( $(this).val() );
                return false;
            });

            $(selector).find("input.clear").each(function(i, input){
                var clearData = { };
                    clearData[ $(this).attr("name") ] = $(this).val().split(",");
                agd.func.clearSelectedItems(clearData);
            });



            $(".highlight-target").hover(function(){
                var $this = $(this), $target = $( $this.attr("target"));
                $target.toggleClass("highlight");
            });

            $(".stream", selector).each(function(i, dom){
                var $table = $( document.createElement("table") ).appendTo(this);

                var streamAction = $(this).attr("rel"),
                    setTableContents = function(res){
                        $table.empty();
                        $.each( res.rows, function(i, line){
                            $row = $(document.createElement("tr")).appendTo($table);
                            $.each( res.cols, function(j, col){
                                $(document.createElement("td")).html( line[col] ).appendTo($row);
                            });
                            $options = $(document.createElement("td")).appendTo($row);
                            $.each(line.options, function(i, op){
                                $op = $(document.createElement(op.tagName)); delete(op.tagName);
                                $op.attr(op).appendTo($options);
                            });
                        });
                        modalbox.func.resize();
                    },
                    getData = function(mode){
                        if( !$(dom).closest("body").length ){ return false; }
                        ajax = $.ajax({
                            url : "/stream/"+streamAction+"/"+mode,
                            error : function(res){
                                if( res.statusText!="abort"){
                                    setTimeout(function(){ getData("result"); }, 1000);
                                }
                            },
                            success : function(res){
                                var wait =(mode=="result")?0:1000;
                                if( res ){
                                    setTableContents(res);
                                };
                                setTimeout(function(){ getData("change"); }, wait);
                            }
                        });
                    }
                ;
                getData("result");
            });


            $(selector).find("input.count").click(function () {
                var query = $(this).data('count-target'),
                    $count = $(query),
                    num = ($count.data('counter') ? $count.data('counter') : $count.text()),
                    init = $count.data('init'),
                    name = $count.data('name');

                // normalizar el input
                num = num ? parseInt(num, 10) : 0;

                // sumar / restar
                num = this.checked ? num+1 : num-1;
                //Updating our countter
                $count.data('counter', num);
                if (num == init && typeof $count.data('inittext') != "undefined") {
                    num = $count.data('inittext');
                }

                if (num == 1 && typeof $count.data('elementonechecked') != "undefined") {
                    //When one element and flag elementonechecked, whe show especific text
                    num = $("input[name^='"+name+"']:checked:enabled").data("text");

                }

                if (typeof $count.data('addtext') != "undefined" && !isNaN(num)) {
                    //addtext means we want to add text to our content
                    num += " "+ $count.data('addtext');
                }

                $count.html(num);
            });

            $(".fast-add", selector).unbind("keypress").keypress(function(e){

                if( e.keyCode == 13 ){

                    if (agd.agent){
                        alert("No puedes modificar el elemento");
                        return false;
                    }

                    var $this = $(this),
                        val = $this.val(),
                        href = $this.attr("href"),
                        sTarget = $this.attr("target"),
                        $target = $(sTarget),
                        fName = $target.attr("id")+"[]",
                        prefixasignados = $this.attr("prefixasignados") || "e-a-",
                        prefixdisponibles = $this.attr("prefixdisponibles") || "e-d-"
                    ;


                    var createAssignLi = function(key, attrs){
                        var params = ahistory.getParams(href),
                            $li = $(document.createElement("li")).attr("id", 'id-li-'+ key +'-'+ params.poid ).draggable( draggableListOptions ),
                            $label = $(document.createElement("label")).attr({"for":"lbl-"+key}).appendTo($li),
                            $relation = $(document.createElement("span")).addClass("relation-options").appendTo($label),
                            $hidden = $(document.createElement("input")).attr({type:"hidden", value:key, name:fName, "id":"val-e-a-"+params.poid+"-"+key }).appendTo($label),
                            $checkbox = $(document.createElement("input")).addClass("line-assign").attr({type:"checkbox",id:"lbl-"+key}).appendTo($label),
                            $span = $(document.createElement("span")).addClass("ucase").html( "&nbsp;"+attrs.nombre ).appendTo($label)
                        ;

                        html = '<span class="update" target="#varl-'+prefixasignados+params.poid+'-'+ key +'" rel="name" update="'+prefixdisponibles+params.poid+'[]"><img src="'+agd.staticdomain+'/img/famfam/delete.png" class="toggle" target="#id-li-'+ key +'-'+params.poid+'" /></span>&nbsp;';
                        if( !attrs.rel ){
                            html += '<img src="'+agd.staticdomain+'/img/famfam/add.png" class="slide-list link" name="'+attrs.nombre+'" href="'+key+'" rel="#'+prefixasignados+params.poid+'" target="#'+prefixasignados+params.poid+'-op" />';
                        }

                        $relation.html(html);
                        agd.checkEvents($relation);
                        return $li;
                    };
                    var checkInsertedItem = function(res){
                        if( res.length ){
                            try {
                                var json = agd.func.getJson(res);
                                for( key in json ){
                                    var ob = json[key], $li = createAssignLi(key, ob).appendTo($target);
                                };

                                $target.find('.sinasignar').remove(); // remove info text

                                $target.get(0).scrollTop = $target.get(0).scrollHeight;
                                $this.addClass("complete");
                            } catch(e) {
                                agd.navegar();
                            }
                        } else {
                            $this.addClass("erro");
                            agd.func.jGrowl( "error-asignar", agd.strings.error )
                        }
                    };

                    $this.addClass("waiting");
                    valor=null;
                    $.post( href, { data : val }, function(result){

                        $this.removeClass("waiting");
                        if( result ){
                            if( isNaN(result) ){
                                if( result == "patron" ){
                                    alert("El nombre no cumple con el patron necesario");
                                    return false;
                                }
                                if( result == "asignado" ){
                                    alert(agd.strings.agrupador_asignado);
                                    return false;
                                }
                                if( result == "imposible" ){
                                    alert(agd.strings.agrupador_existente_otro_agrupamiento);
                                    return false;
                                }

                                var json = agd.func.getJson(result);

                                if( json.length ){
                                    //añadimos al inicio de la lista de coincidencias la sugerencia del usuario
                                    var sugerencia = { name:'item', type:'radio', innerHTML:val, value:val, checked:'checked', className : 'strong' };
                                    json.unshift( sugerencia );

                                    var ul = agd.create.ul( json ),
                                        title = agd.strings.titulo_seleccionar,
                                        buttons = [{ innerHTML : agd.strings.aceptar, className : 'unbox-it' }],
                                        modal = agd.create.modal( title, buttons );

                                    $(modal).find(".cbox-content").append( $(ul).addClass("item-list") ).addClass("cbox-list-content");
                                    modalbox.func.open({"html":modal});

                                    $(modal).find("button.unbox-it").click(function (){
                                        $.post( href, { data : $(":checked").val(), force : true }, checkInsertedItem);
                                    });
                                } else {
                                    for( key in json ){
                                        var ob = json[key], $li = createAssignLi(key, ob).appendTo($target);
                                    };

                                    $target.find('.sinasignar').remove(); // remove info text
                                    $this.addClass("complete");
                                }
                            } else {
                                if( confirm(agd.strings.elemento_existente + ". " + agd.strings.desea_asignarlo) ) {
                                    $.post( href, { data : val, force : true }, checkInsertedItem);
                                }
                            }
                        } else {
                            $this.addClass("error");
                        };
                        window.setTimeout(function(){ $this.removeClass("complete error");}, 4000);
                    });

                    return false;
                }
            });


            //------- estilizar los checkbox
            $(".iphone-checkbox", selector).iphoneStyle();

            $(".stylesheet", selector).click(function(){
                var stylesheet = $(this).attr("href");
                $("head link").remove();
                $( document.createElement("link") ).attr({"rel":"stylesheet", "href" : "../css/"+stylesheet, "type" : "text/css" }).appendTo( head );
                return false;
            });


            $("button.btn:not(.searchtoggle,.list-move,#boton-buscar),.toggle[title='Eliminar']").click(function(){
                if (agd.agent){
                    alert("1 No puedes modificar el elemento");
                    return false;
                }
            });

            //------- nos cargamos el menu de ayuda de busqueda
            $("#menu-ayuda-buscar").remove();

            $notification = $(selector).find("#notification-complete");
            if( $notification.length ){
                $notification.each(function() {
                    var notificationID = $(this).data('id'), active = activeNotification;
                    if( notificationID ){
                        var activeNode = document.getElementById(notificationID);
                        if( activeNode ) active = activeNode;
                    }
                    agd.func.removeInPageAlert(active);
                });


            }

            //------- recargar la página sin necesidad de llamar a niguna funcion de js
            var $reloader = $(selector).find("#reloader, .reloader");

            if ($reloader.length) {
                agd.cache.url = {};

                $reloader.removeAttr("id").removeClass("reloader");

                var href = $reloader.attr('href');

                if (href) {
                    location.href = href;
                } else {
                    try {

                        var url = false;

                        // --- Hack para no mostrar error al eliminar desde una busqueda
                        if (agd.history.length) {
                            var aux = ahistory.getPage(agd.history[agd.history.length-1].url).split("/");
                            if (aux[2] && aux[2] == "eliminar.php" && ahistory.getPage() == "buscar.php") {
                                location.href = location.hash + "&force=1";
                                return false;
                            }
                        }

                        agd.navegar(url);
                    } catch(e) {

                    };
                }
            }



            //------- convertir un input normal en un selector de fechas
            $("input.datepicker", selector).datepicker( { dateFormat: 'dd/mm/yy', showOn : 'focus', firstDay: 1, onSelect: function(dateText, inst){
                this.edited = true;
                $(this).trigger("onDateChange")}
            }).each(function(){
                var $this = $(this), val = $this.val();
                if( val.indexOf(" ")!=-1){
                    $this.val( val.split(" ")[0] );
                }
            });


            $(selector).find("input.timepicker").timepicker({
                showPeriod: false,
                showPeriodLabels: false,
                hourText: agd.strings.hora,
                minuteText:  agd.strings.minuto
            }).focus(function(){this.edited=true;});


            //------- enviar formularios al hash
            $(".sendhash", selector).each(function(i, form){
                var langSearch = agd.strings.buscar + "...";

                var availableTags = ["docs:caducados", "docs:validos", "docs:sin-anexar", "docs:anulados", "docs:pendientes", "tipo:empresa", "tipo:empleado", "tipo:usuario", "tipo:maquina", "tipo:agrupador" ];

                $("#buscar").focus(function(){
                    if( !$(this).val() || $(this).val()==langSearch ){
                        $(this).val("");
                    }
                }).blur(function(){
                    if( !$(this).val() || $(this).val()=="" ){
                        $(this).val(langSearch);
                    }
                }).autocomplete({
                    source: availableTags,
                    open: function(event, ui ,o) {
                        $("ul.ui-autocomplete").css("width", ($("#buscador").width()-7) );
                    }
                });

                $(form).unbind().submit(function(){
                    var value = encodeURIComponent($.trim( $("#buscar").val() )).replace("%3A",":").replace("%23","#").replace("%2C",",");

                    if( !value || value == langSearch ){
                        agd.func.jGrowl("ayuda_busqueda", agd.strings.ayuda_busqueda, { header: agd.strings.ayuda, sticky: true } );
                        return false;
                    }

                    location.hash = $(this).attr('action') + "?p=0&q=" + value;

                    return false;
                });

            });


            $(selector).find(".post[href]").off('click').on('click', function(e){
                e.preventDefault();
                try {
                    var $this = $(this), type = $this.attr("type"), href = $this.attr("href");

                    if( type && type == "checkbox" ){
                        var cnct = ( href.indexOf("?") == -1 ) ? "?" : "&";
                        href += cnct + "checked=" + (($this.get(0).checked)?"1":"0");
                    };

                    var $text = $(this).data("text");
                        if ($text){

                        $(this).html($text);
                    }

                    if ($(this).data("disable")){
                        $(this).attr("disabled", "disabled");
                    }

                    $.post(href, function(ok){
                        if( type && type == "checkbox" && ok==1){

                            if( $this.get(0).checked ){ $this.removeAttr("checked"); }
                            else { $this.get(0).checked = true ; }
                        }
                        try {
                            var jsonResponse = agd.func.getJson(ok);
                            agd.actionCallback(jsonResponse);

                            if (type && type == "checkbox" && jsonResponse.result == 1) {
                                if( $this.get(0).checked ){ $this.removeAttr("checked"); }
                                else { $this.get(0).checked = true ; }
                            }
                        } catch(e) {}

                        var target = $this.data('target');
                        if (target) {
                            $(target).html(ok);
                        }

                    });
                } catch(e) {}
            });

            //quitamos el binding actual de click para evitar que se envie el form.
            $(selector).find('button[href]:not(.post)').unbind("click").click(function(){
                if( $(this).hasClass("sendinput") || $(this).hasClass("goto") || $(this).hasClass("multiple-action") || $(this).hasClass("box-it") ){ }
                else {
                    var link = $(this).attr('href'), $target = $( $(this).attr("target") ), aux = link.split("");
                    if( $(this).hasClass("toframe") ){ $target = $("#async-frame"); }

                    if( $target.length && $target[0].tagName.toUpperCase() == "IFRAME" ){
                        $target.attr("src", link);
                        return false;
                    } else {
                        if( aux[0] == "#" ){
                            document.location = link;
                        } else {
                            agd.func.open(link);
                        }
                    };

                    return false;
                }
            });


            $(selector).find(".list-move").click(function(e){
                //$.data( this.form, "sender", this); //IE Event bubbling fix
                var   $from = $($(this).attr("rel"))
                    , currentID = $from.attr("id")
                    , currentString = currentID.substring(0, currentID.lastIndexOf("-") )
                    , currentSide = currentString.substring( currentString.lastIndexOf("-")+1 )
                    , agrupNumber = currentID.substring(4)
                    , sinAignar = $(".sinasignar.e-"+agrupNumber)
                    , $target = $($(this).attr("target"))
                    , targetID = $target.attr("id")
                    , targetString = targetID.substring(0, targetID.lastIndexOf("-") )
                    , targetSide = targetString.substring( targetString.lastIndexOf("-")+1 )
                    , collection = $from.find("input:checked:visible")
                    , ln = collection.length-1
                ;

                var confirmAlert = false;

                if( typeof sinAignar != "undefined" && targetSide === 'a' && collection.length > 0){
                    sinAignar.hide();
                }else if(typeof sinAignar != "undefined" && targetSide === 'd' && collection.length > 0){
                    sinAignar.show();
                }

                collection.each(function(i){
                    var $li = $(this.parentNode.parentNode);

                    var classHierarchy = $li.data('ishierarchy');
                    if (classHierarchy=='yes' && !confirmAlert){
                        confirmAlert = true;
                        if (!confirm(agd.strings.alert_desasignar_elementos_papelera))
                            return false;

                    }
                    var inputs = $li.find("input[type=text]"), l = inputs.length;
                    while(l--){
                        var input = inputs[l], val = $(input).val();
                        input.setAttribute("value", val);
                    };

                    var $newLi = $(document.createElement("li")).html($li.html());


                    // Si estamos usando grupos..
                    if( rel = $li.attr("rel") ){
                        $newLi.attr("rel", rel);
                        var $targetGroup = $target.find("#"+targetSide+"_"+rel), $currentGroup = $from.find("#"+currentSide+"_"+rel);

                        if( $targetGroup.length ){
                            $targetGroup.after($newLi);
                        } else {
                            $(document.createElement("li")).html( $currentGroup.html() )
                                .addClass("group")
                                .attr("id", targetSide+"_"+rel )
                                .appendTo($target)
                                .after($newLi);
                        }
                        $li.remove();
                    } else {
                        $newLi.appendTo($target);
                        $li.remove();
                    }

                    if( ln === i ){
                        if( rel ){
                            $from.find("li.group + li.group").remove();

                            $list = $from.find("li[rel]");
                            if( !$list.length ){
                                $from.find("li").remove();
                            };
                        }

                        $target.find("input").each(function(){
                            var newname = $(this).prop("name").replace(currentString, targetString);
                            $(this).attr("name", newname);
                        });

                        modalbox.func.resize();
                    }
                });
                return false;
            });

            $(selector).find('.send-info').click(function(){
                agd.func.sendInfo({
                    url : $(this).prop("href"),
                    confirm : false ,
                    src : (this)
                });
                return false;
            });

            $(selector).find(".async-form").bind("submit", function(e){
                var form = this, button = $.data( this, "sender"),
                    method = $.trim( $(form).prop("method").toLowerCase() ),
                    jajax = ( method && $[method] ) ? $[method] : $.post;

                if( button && $(button).hasClass("showload") ){
                    var $span = $("span > span", button), html = $span.html();
                    $span.html( "<img src='"+  agd.inlineLoadingImage +"' style='vertical-align: middle;' /> " + html );
                };

                if( !agd.func.validateForm(form) ){
                    return false;
                }

                var $text = $(button).data("text");
                if ($text){
                    $(button).html($text);
                }

                if ($(button).data("disable")){
                    window.setTimeout(function(){
                        $(button).attr("disabled", "disabled");
                    }, 0);
                }

                var hiddenType = $('input:hidden[name=type]').val();
                var hiddenValidate = $('input:hidden[name=validate]').val()
                if ( 'async-form' === hiddenType && undefined !== hiddenValidate) {
                    return false;
                }

                $input = $( document.createElement("input") ).attr({
                    type : "hidden",
                    name : "type",
                    value : "async-form"
                }).appendTo( $(form) );

                if ($(button) && $(button).attr("name") && $(button).attr("value")) {
                    $(document.createElement("input")).attr({
                        type : "hidden",
                        name : $(button).attr("name"),
                        value: $(button).attr("value")
                    }).appendTo($(form));
                }

                var action = $(form).prop("action"), formData = $(form).serialize();

                var callback = function( data ){
                    if( $.trim(data) ){
                        if( $(form).hasClass("return") ){
                            history.back(1);
                        } else {
                            if( $(form).attr("rel") ){ location.hash = $(form).attr("rel"); }
                        }
                        $.jGrowl( data );
                    }
                };


                setTimeout(function() {
                    $(form).find('button[type=submit], select.go').attr('disabled', 'true');
                }, 1);


                $loading.show();
                var xhr = jajax( action, formData, function(data){
                    var contentType = xhr.getResponseHeader('Content-type');
                    if (contentType.indexOf('text/plain') !== -1){
                        callback(data);

                        if( $(form).hasClass("reload") ){
                            agd.navegar();
                        }

                        if( !$(button).hasClass("detect-click") ){
                            var $checked = $(form).find(".auto-trigger").find("input:checked");
                            $checked.each( function(){
                                var $button = $(this).closest("div.auto-trigger").find("button");
                                var sName = $button.attr("name"), sVal = $button.attr("value");
                                jajax( action, formData + "&" + sName + "=" + sVal, callback);
                            });
                        }

                    } else if(contentType.indexOf('text/html') !== -1) {
                        modalbox.func.open({html:data});
                    } else if(contentType.indexOf('application/json') !== -1) {
                        agd.actionCallback(data);
                    };

                });

                return false;
            }).each(function (i, form) {
                for(name in agd.inputs){
                    $(form).find("input[name='"+name+"']").blur(agd.inputs[name]).keyup(function(){this.edited=true; }).on("paste",function(){this.edited=true;});
                };
            });


            $(".to-iframe-box", selector).click(function(){
                var currentID = $(this).attr("name"),
                    iframeTarget = $("#iframe-"+currentID),
                    target = $($(".cbox-content")[0]),
                    href = $(this).attr("href")
                ;

                // ---- Lo creamos si no existe
                if( !iframeTarget.length ){
                    iframeTarget = $( document.createElement("iframe") )
                        .attr({
                            "name": "iframe-"+currentID,
                            "id":   "iframe-"+currentID
                        })
                        .addClass("iframe-box")
                        .appendTo( target );
                }

                if( modalbox.exists() ){
                    var adaptar = function(){
                        $(iframeTarget).attr("src", href);
                        $(window).unbind(modalbox.event.resize, adaptar);
                    };
                    $(window).bind(modalbox.event.resize, adaptar);
                    modalbox.func.resize();
                };

                return false;
            });



            $(".form-to-box", selector).each(function(o, ob){
                var form = this, restoreForm, cancelUpload,
                    attrMethod = $(form).prop("method") || "post",
                    method = $.trim(attrMethod.toLowerCase()),
                    jajax = ( method && $[method] ) ? $[method] : $.post,
                    getUploadedPath = '/agd/getuploaded.php',
                    uploadPath = agd.uploadPath;

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


                for(name in agd.inputs){
                    $(form).find("input[name='"+name+"']").blur(agd.inputs[name]).keyup(function(){this.edited=true; }).on("paste",function(){this.edited=true;});
                };

                // Prevent errors in chrome
                $(".filecontainer", form).each(function(i, o){
                    if(!i){ // only to first occurrence try to preload the image
                        var imgPreLoad = document.createElement('img');
                        imgPreLoad.src = agd.staticdomain + '/img/progressbg_green.gif';
                    };


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
                    $(document).one(modalbox.event.cleanup, cancelUpload);
                    window.modalconfirm = true;

                    var     target = $( $(this).attr("target") ),
                            action = $(form).attr("action"),
                            actionData = action.split('?'),
                            queryString = actionData[1] +  "&_=" + (new Date()).getTime(),
                            mtd = $(form).attr("mtd"),
                            enct = $(form).attr("enctype"),
                            input = $( this ),
                            filetype = $(this).attr("filetype") || null,
                            uniqID = encodeURIComponent( (new Date()).getTime() ),
                            timeToShowInfo;
                    inputFile.isCanceled = false;
                    inputFile.isComplete = false; //cambiar a isComplete;
                    target.html("Inicializando...");

                    //------- si se asigno el estado por que se equivoco el usuario y selecciona el archivo de nuevo, hay que quitar el atributo para asegurar todas las comprobaciones
                    try{ $(inputFile).removeAttr("complete"); } catch(e) { };



                    //----------- SI EL USUARIO QUIERE CANCELAR EL EVENTO, O DECIDE CERRAR LA VENTANA
                    cancelUpload = function( txt, current ){
                        current = current || false;
                        if( inputFile.isCanceled ){ return; }

                        //---- si no hay texto, evitamos el error
                        txt = ( txt ) ? txt : "";

                        //---- quitamos eventos
                        window.modalconfirm = false;
                        $(document).off(modalbox.event.cleanup, cancelUpload);

                        //---- variables de proceso
                        inputFile.isCanceled = true;
                        if( !current ){ input.val(""); }

                        if( inputFile.lastProgress ){ delete(inputFile.lastProgress); }

                        var span = $( document.createElement("span") ).html( txt );

                        //----- describimos el problema
                        target.empty().append( span );

                        //--- cancelamos la carga del frame
                        window.setTimeout(function(){
                            $( agd.elements.asyncFrame ).attr("src","/blank.html");
                        }, 500);

                        clearTimeout(timeToShowInfo);

                        return span[0];
                    };

                    //----------- FUNCION PARA ESTABLECER EL PROGRESO DE LA CARGA
                    var setProgress = function( progress, extra ){
                        $(target).find(".progressbar").css("background-position",(100-progress)+"% 50%");
                        $(target).find("span").html( progress+"% ("+ agd.func.formatBytes(extra.upload) +"/"+ agd.func.formatBytes(extra.total) +")");
                        if( progress == 100 ){
                            inputFile.isComplete = true;
                        }
                    };


                    var uploadStartTime = ( new Date() ).getTime();
                    //----------- FUNCION PARA CHECKEAR EL ESTADO DE LA CARGA
                    /*var checkfn = function(uploadinfo){
                        if( typeof uploadinfo != "undefined" ){
                            var data = uploadinfo.data;
                            var currentTime = ( new Date() ).getTime(), diff = currentTime - uploadStartTime;
                            if( data != "null" ){
                                //----------- SIEMPRE Y CUANDO EL FICHERO NO ESTE COMPLETO
                                if( !inputFile.isComplete ){

                                    //----------- SI RETONAR -1 ES QUE NO SE PUEDE CARGAR ESTE FICHERO
                                    if( data == "-1" ){
                                        var texto = cancelUpload( "<span class='link'>Limite de tamaño excedido ("+ agd.func.formatBytes(uploadinfo.max,2) +"MB). Tu archivo pesa " + agd.func.formatBytes(uploadinfo.total,2) + "MB</span>", true );
                                        $( texto ).click(function(){
                                            solicitarUpload(uploadinfo.currentbytes);
                                        });
                                        return false;
                                    }

                                    //----------- CUANDO TENGAMOS EL PRIMER VALOR MAYOR QUE 0, MOSTRAREMOS EL PROGRESO Y EL LINK DE CANCELAR
                                    if( !inputFile.lastProgress && inputFile.lastProgress !== 0 && !inputFile.isCanceled ){
                                        inputFile.lastProgress = 0;
                                    }

                                    //----------- AQUI VAMOS COMPROBANDO EL PROGRESO Y PASANDOLO A LA FUNCION PARA QUE LO MUESTRE POR PANTALLA
                                    var currentProgress = parseInt(data);
                                    if( !(isNaN(currentProgress)) ){
                                        if( (currentProgress > inputFile.lastProgress) ){
                                            setProgress( currentProgress, uploadinfo );
                                        }
                                    }

                                }
                            }
                            //----------- SI EL USUARIO CANCELA LA CARGA, SE PARA EL CHECKEO
                            if( inputFile.isCanceled ){
                                inputFile.isCanceled = false;
                                return false;
                            }

                            //----------- SI EL FICHERO AUN NO SE HA CARGADO DEL TODO Y NO SE HA CANCELADO, VOLVEMOS A CHECKEAR
                            if (inputFile.isComplete) {
                                if( btn = $("button.send", form).get(0) ){
                                    btn.restore();
                                };
                            }
                        }
                    };*/


                    var limiteExcedido = function(maxb, current){
                        var mb = agd.func.formatBytes(maxb);
                        if (maxb < 1024*1024*10) {
                            var text = agd.strings.expl_limite_upload;

                            if (agd.userType != 'employee') {
                                text += ". <a href='/app/payment/license'>"+agd.strings.pincha_aqui+"</a>";
                            }
                        } else {
                            var text = agd.strings.limite_upload_file.replace('%s', mb);
                        }

                        if (window.ga) {
                            ga('send', 'event', 'Document', 'Upload', 'Upload '+ mb.replace(' ', '') +' limit');
                        }

                        cancelUpload(text, true);
                    };

                    var showCompleteInfo = function( fileName, fileSize, type ){
                        //----------- INDICAMOS QUE NUESTRO ELEMENTO FILE ESTA COMPLETO
                        inputFile.isComplete = true;

                        if( filetype && type.indexOf(filetype) !== -1 ){
                            switch(filetype){
                                case "image":

                                    var img = document.createElement("img"), currentw = $(modalbox.body).width();

                                    var maxheight = $($(modalbox.body)).find("#photoContainer").data("maxheight");
                                    img.onload = function(){
                                        var w = img.width, h = img.height, imgOffset = $(img).offset(), windowOffset = $(modalbox.body).offset();

                                        if (h > maxheight) $(img).height(maxheight);

                                        if( !$.browser.msie ){
                                            var coords = $(this).faceDetection({
                                                error : function(img, code, message){
                                                    //alert( 'Error: ' + message );
                                                }
                                            });

                                            var marginLeft = ($(modalbox.body).width() - $(img).width())/2;

                                            if( !coords[0] ){
                                                var cuadro = 50;
                                                coords[0] = {
                                                    positionX : marginLeft + cuadro,
                                                    positionY : imgOffset.top-windowOffset.top+cuadro,
                                                    x : cuadro,
                                                    y : cuadro,
                                                    height : cuadro,
                                                    width : cuadro
                                                };
                                            };

                                            var i = 0;
                                            var heightUnit = (coords[i].height / 100), addTop =  heightUnit*50;
                                            var widthUnit = (coords[i].width / 100), addWidth =  widthUnit*40;

                                            var pos = {
                                                height : coords[i].height + (addTop*2),
                                                width : coords[i].width + (addWidth*2),
                                                x : coords[i].x - (addWidth*1.2),
                                                y : coords[i].y - (addTop*1.2)
                                            };

                                            // Add to image wrapper ( parent of #my-picture )
                                            $div = $(document.createElement("div")).appendTo( target );

                                            var inputs = {};
                                            for( attr in pos ){
                                                inputs[attr] = $(document.createElement("input")).addClass("photo-size").attr({
                                                    type:"hidden",name:"size["+i+"]["+attr+"]",value:pos[attr]
                                                }).appendTo(target);
                                            };


                                            $div.draggable({
                                                start : function(event, ui){
                                                    this.initposition = { x : event.clientX, y : event.clientY };
                                                },
                                                stop : function(event, ui){
                                                    var diffX = event.clientX-this.initposition.x , diffY = event.clientY-this.initposition.y;
                                                    var xVal = parseInt(inputs["x"].val()), yVal = parseInt(inputs["y"].val());


                                                    inputs["x"].val( xVal+diffX );
                                                    inputs["y"].val( yVal+diffY );
                                                }
                                            }).resizable({
                                                aspectRatio: ".75",
                                                stop : function(event, ui){
                                                    inputs["width"].val( $div.width() );
                                                    inputs["height"].val( $div.height() );
                                                }
                                            }).addClass("face").css({
                                                'position': 'absolute',
                                                'left':     coords[i].positionX - (addWidth*1.2) +'px',
                                                'top':      coords[i].positionY - (addTop*1.2) +'px',
                                                'width':    pos.width +'px',
                                                'height':   pos.height+'px',
                                                'resize':   'both',
                                                'background-color':'transparent'
                                            }).attr("disabled", true);



                                        };

                                        modalbox.func.resize();
                                    };

                                    img.id = "uploaded-img";

                                    $(target).append(img);
                                    img.src = getUploadedPath + "?action=dl&mode=img&t=" + (new Date()).getTime();
                                break;
                            }
                        } else {
                            try {
                                var innerHTMLName = ( fileName.length > 60 ) ? fileName.substring(0,60)+"..." : fileName, size = Math.round(parseInt( (fileSize)/1024) );
                                $( document.createElement("a") ).attr({
                                    "title": agd.strings.descargar + fileName + " ("+size+"Kb)",
                                    "href": getUploadedPath+"?action=dl",
                                    "target":"async-frame"
                                }).html( innerHTMLName + " <i>("+ type +")</i>" ).appendTo( target );
                            } catch(e){
                                $(target).html(agd.strings.error_texto);
                            }
                        }

                        modalbox.func.resize();

                        if( btn = $("button.send", form).get(0) ){
                            btn.restore();
                        };
                    };

                    //----------- EMPEZAMOS A CHECKEAR
                    target.html( "<div id='uploadProgressBar' class='progressbar line-block'> </div>&nbsp;<span>0%</span> <a style='font-weight:normal'>Cancelar</a>" );
                    $(target).find("a").click(function(e){
                        cancelUpload("Cancelado por el usuario");
                    });


                    // Comprobamos funcionalidades HTML5: FILE API
                    if( (files = inputFile.files) && files[0] && files[0].name && files[0].upload) {
                        var file = inputFile.files[0], size = file.size;
                        if( size > agd.usermaxfile ){
                            return limiteExcedido(agd.usermaxfile, size);
                        };

                        var xhr = file.upload(uploadPath + '?' + queryString, {
                            onprogress : function(e){
                                if( e.lengthComputable ){
                                    var percentage = Math.round((e.loaded*100)/e.total);
                                    setProgress( percentage, { upload: e.loaded, total: e.total} );
                                }
                            },
                            onload : function(e){
                                target.html("<span><img src='"+ agd.inlineLoadingImage +"' /></span>");
                            },
                            onsuccess : function(e){
                                var contentType = this.getResponseHeader('content-type');

                                if (contentType.indexOf('application/json') !== -1) {
                                    var res = $.parseJSON(this.responseText);

                                    res.iface = agd.iface; // never change the interface in this case
                                    agd.actionCallback(res);
                                }

                                target.empty();
                                showCompleteInfo(file.name, size, file.type);

                                // Eliminados variables de control
                                $(form).data("pass", true);
                            },
                            onerror : function(e){
                                if( e == "size_error" ){
                                    return limiteExcedido(agd.usermaxfile ,size);
                                } else if (e == "write_error")  {
                                    cancelUpload(agd.strings.error_texto);
                                } else {
                                    if( e ){
                                        delete(File.prototype.upload); // fallback a upload tradicional
                                        $(inputFile).trigger('change');
                                        // $(form).submit();
                                        return false;
                                    } else {
                                        cancelUpload(agd.strings.error_texto);
                                    }
                                }
                            }
                        });

                        $(target).find("a").click(function(){
                            try{ xhr.abort(); } catch(e){};
                        });

                        return false;
                    } else {
                        //----------- AÑADIMOS AL FORMULARIO TEMPORAL DIFERENTES VALORES NECESARIOS PARA EL CHECKEO DEL PROGRESO
                        $("#UPLOAD_IDENTIFIER", form).remove();
                        $(form).prepend(
                            $( document.createElement("input") ).attr({
                                "type":"hidden", "name":"UPLOAD_IDENTIFIER", "value":uniqID, "id":"UPLOAD_IDENTIFIER"
                            })
                        );


                        inputFile.restoreForm = restoreForm = function(){
                            $("#UPLOAD_IDENTIFIER", form).remove();
                            $(form).attr({"action":action,"enctype":enct,"method":mtd,"target":target});
                        };

                        // Valores temporales para enviar el fichero
                        $(form).attr({
                            "action":uploadPath + '?' + queryString,
                            "enctype":"multipart/form-data",
                            "method":"post",
                            "target":"async-frame"
                        });

                        //----------- EVENTO PARA CUANDO EL IFRAME CARGUE, ES DECIR CUANDO EL FICHERO ESTE CARGADO POR COMPLETO
                        $("#async-frame").load(function(){
                            if(this.readyState != "complete") return;
                            $(this).unbind('load');

                            //----------- SI EL FORMULARIO NO SE HA ENVIADO TODAVIA, MOSTRAMOS EL ARCHIVO RECIEN SUBIDO PARA SU DESCARGA
                            if (!form.complete) {

                                $.get(getUploadedPath+"?action=info" + '&' + queryString, function(data, estado) {
                                    clearTimeout(timeToShowInfo);

                                    if (!inputFile.isCanceled) {
                                        if (data) {
                                            //var data = agd.func.getJson( data );
                                            //-------- COMPROBAMOS QUE SE PUEDA SUBIR SIN PASARSE DEL LIMITE
                                            if( data.data == -1 ){
                                                return limiteExcedido(data.max, data.current);
                                            } else {
                                                //----------- SI NO SE PASA DEL LIMITE, COMPROBAMOS QUE EL ARCHIVO SE SUBIO CORRECTAMENTE
                                                if( !data.archivo || data.archivo.error ){
                                                    if( data.archivo && data.archivo.error ){
                                                        cancelUpload( "Ocurrió un error #"+ errorString +". Intentalo de nuevo" );
                                                    } else {
                                                        cancelUpload( agd.strings.error_texto );
                                                    };
                                                } else {
                                                    target.empty();
                                                    var $formatos = $(form).find(".formato-solicitante");
                                                    if($formatos.length){
                                                        //-------------
                                                        var esFormatoValido = function(oFormato,sTipo){
                                                            var $imagenes = $("img",oFormato),
                                                                len=$imagenes.length,
                                                                valido = false ;
                                                                //$imagenes = $formato.find("img"), ;
                                                            while(len--) {
                                                                var tipo = $($imagenes[len]).attr("rel");
                                                                if( tipo == sTipo ) {
                                                                    valido = true;
                                                                    break;
                                                                }
                                                            };
                                                            return valido;
                                                        };

                                                        //un solo solicitante
                                                        if($formatos.length == 1) {
                                                            if( !esFormatoValido($formatos[0],data.archivo.type) ){
                                                                cancelUpload( "Formato no aceptado" ); return false;
                                                            }
                                                        } else {
                                                            $formatos.each(function(i,oSolicitante){
                                                                var     sReferencia = $(oSolicitante).attr("rel"),
                                                                    oReferencia = $(sReferencia),
                                                                    $contenedor = oReferencia.find(".solicitante-funciones"),
                                                                    $checkbox = oReferencia.find("input[type='checkbox']");
                                                                $(".error", oReferencia).remove();
                                                                if( !esFormatoValido(oSolicitante,data.archivo.type) ){
                                                                    $( document.createElement("span") )
                                                                        .html("Formato no valido para este solicitante")
                                                                        .addClass("error")
                                                                        .appendTo(oReferencia);
                                                                    $contenedor.css("display","none");
                                                                    $checkbox.attr("disabled",true).removeAttr("checked");

                                                                } else {
                                                                    $contenedor.css("display","");
                                                                    $checkbox.removeAttr("disabled").attr("checked",true);
                                                                }
                                                            });
                                                        }
                                                    }

                                                    try {
                                                        showCompleteInfo( data.archivo["name"], data.archivo["size"], data.archivo["type"] );
                                                        data.iface = agd.iface; // never change the interface in this case
                                                        agd.actionCallback(data);
                                                        if (modalbox.exists()) {
                                                            modalbox.func.resize(function(){ agd.checkEvents(modalbox.body)} );
                                                        }
                                                    } catch(e) {}
                                                }
                                            }
                                        } else {
                                            cancelUpload( "Ocurrió un error desconocido. Intentalo de nuevo" );
                                        }
                                    }
                                });
                            //----------- SI NO, ES QUE HAY QUE ENVIAR EL FORMULARIO, LIMPIAMOS LOS EVENTOS DE SEGURIDAD Y ENVIAMOS EL FORMULARIO
                            } else {
                                inputFile.isComplete = true;
                                window.modalconfirm = false;
                                $(document).off(modalbox.event.cleanup, cancelUpload);
                                $(form).submit();
                            }
                        });


                        //----------- ENVIAMOS EL FORMULARIO TEMPORAL
                        $(form).submit();
                    };


                    // ---- Timeout para mostrar carga
                    timeToShowInfo = window.setTimeout(function(){
                        if( (!inputFile.isComplete && typeof inputFile.lastProgress == "undefined" || inputFile.lastProgress == 0) ){
                            target.html( "<span>Subiendo, espera por favor... <img src='"+ agd.inlineLoadingImage +"' /> <a>Cancelar</a></span>" );
                            $("a",target).click(function(){
                                cancelUpload( "Cancelado por el usuario" );
                            });
                        }
                    }, 6000);

                });
                //--- Fin campos file


                // maybe we get some error
                $(form).find(".uploadedfile").each(function () {
                    var fileInput = $($(this).data('input-from'));
                    fileInput.attr("complete", true); // mark as completed
                    $(form).data("pass", true);
                });


                var sndbutton = $("button.send", form).unbind("click").click(function(e){
                    try {
                        $(this).attr({ "disabled":true});

                        if (allFilesUploaded()) {
                            $(this).find("span > span").html(agd.strings.enviando);
                            $(form).submit();
                        } else {
                            $("span > span",this).html( agd.strings.esperando_cargar_archivos );
                        }

                    } catch(e) { };
                    return false;
                }).removeAttr("disabled").get(0);

                try {
                    if( sndbutton && sndbutton.innerHTML ){
                        var defaultHTML = sndbutton.innerHTML;
                        sndbutton.restore = function(){
                            $(sndbutton).removeAttr("disabled");
                            $(sndbutton).find("span > span").html(defaultHTML);
                        };
                    }
                } catch(e) {}

                //--- Fin botones de enviar

                $(this).unbind("submit").submit(function(e){

                    if (form.getAttribute("enctype") == "multipart/form-data" && !$(form).data("pass")) {

                        if (!$(form).attr('rel') && allFilesUploaded()) {
                            // ya no estamos haciendo upload
                            window.modalconfirm = false;
                            $(document).off(modalbox.event.cleanup, cancelUpload);
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
                        var confirmForm = $(form).data('confirm-text');
                        if (confirmForm) {
                            if (!confirm(agd.strings[confirmForm] || confirmForm)) {
                                sndbutton.restore();
                                return false;
                            }
                        }

                        // ya no preguntaremos
                        window.modalconfirm = false;
                        $(document).off(modalbox.event.cleanup, cancelUpload);

                        if( agd.func.validateForm(form) ){
                            var params = $(form).serialize(), action = $(form).attr("action"), cnct = ( action.indexOf("?")==-1 )?"?":"&";
                            $('*[disabled]:not(.attr-upload)',form).each(function(){
                                params = params + '&' + $(this).attr("name") + '=' + encodeURIComponent($(this).val());
                            });

                            agd.func.open( action + cnct +  params, function(){
                                window.modalconfirm = false;
                            }, method, $(form).data('loading-lock'));
                        }
                    };
                    return false;
                });
                //---- Fin evento envio
            });
            //---- Fin eventos para form

            $(".mustfocus", selector).focus(function(){
                $(this).removeClass("mustfocus");
            });

            $(".date-period", selector).click(function(e){
                var $this = $(this), offset = $this.offset(), height = $(this).outerHeight();
                if( !$this.hasClass("date-period") ){ return true; }

                if( this.extended ){
                    if( this.open ){
                        $( this.extended ).fadeOut();
                        this.open = false;
                    } else {
                        $( this.extended ).fadeIn();
                        this.open = true;
                    }
                } else {
                    $div = $( document.createElement("div") ).css({
                        position:"absolute", "top":offset.top+height-1, "left":offset.left+3, visibility:'hidden'
                    }).addClass("btn-extend").appendTo( document.body );

                    if( $this.hasClass("month") ){
                        var year = ( new Date() ).getFullYear();

                        $start = $(document.createElement("select"));
                        $.each( $.datepicker._defaults.monthNames, function(i, month){
                            var value = "1/"+(i+1)+"/"+year;
                            $(document.createElement("option")).attr("value", value).html(year+" - " + month).appendTo($start);
                        });
                        $group = $(document.createElement("optgroup")).attr("label","----- " + (year-1) ).appendTo($start);
                        $.each( $.datepicker._defaults.monthNames, function(i, month){
                            var value = "1/"+(i+1)+"/"+(year-1);
                            $(document.createElement("option")).attr("value", value).html((year-1)+" - " + month).appendTo($group);
                        });

                        $end = $(document.createElement("select"));
                        $.each( $.datepicker._defaults.monthNames, function(i, month){
                            var dayMax = $.datepicker._getDaysInMonth(year, i), value = dayMax+"/"+(i+1)+"/"+year;
                            $(document.createElement("option")).attr("value", value).html(year+" - " + month).appendTo($end);
                        });

                        $group = $(document.createElement("optgroup")).attr("label","----- " + (year-1) ).appendTo($end);
                        $.each( $.datepicker._defaults.monthNames, function(i, month){
                            var dayMax = $.datepicker._getDaysInMonth((year-1), i), value = dayMax+"/"+(i+1)+"/"+(year-1);
                            $(document.createElement("option")).attr("value", value).html((year-1)+" - " + month).appendTo($group);
                        });

                        $table = $( document.createElement("table") );
                            $tr = $( document.createElement("tr") ).appendTo($table);
                                $td = $( document.createElement("td") ).html("Fecha inicio").appendTo($tr);
                                $td = $( document.createElement("td") ).append($start).appendTo($tr);
                            $tr = $( document.createElement("tr") ).appendTo($table);
                                $td = $( document.createElement("td") ).html("Fecha fin").appendTo($tr);
                                $td = $( document.createElement("td") ).append($end).appendTo($tr);

                    } else {
                        $start = $( document.createElement("input") ).attr({type:'text',size:10}).addClass("datepicker");
                        $end = $( document.createElement("input") ).attr({type:'text',size:10}).addClass("datepicker");

                        $table = $( document.createElement("table") );
                            $tr = $( document.createElement("tr") ).appendTo($table);
                                $td = $( document.createElement("td") ).html("Fecha inicio").appendTo($tr);
                                $td = $( document.createElement("td") ).append($start).appendTo($tr);
                            $tr = $( document.createElement("tr") ).appendTo($table);
                                $td = $( document.createElement("td") ).html("Fecha fin").appendTo($tr);
                                $td = $( document.createElement("td") ).append($end).appendTo($tr);
                    }

                    $button = $(document.createElement("button")).addClass("btn").css({margin:"3px 0 0 -3px"}).click(function(){
                        var href = $this.prop("href"),
                            cstring = ( href.indexOf('?') == -1 ) ? '?' : '&',
                            start = encodeURIComponent( $.trim($start.val()) ),
                            end = encodeURIComponent( $.trim($end.val()) );

                        if( !start || !end ){ return alert("Selecciona las fechas"); }

                        href += cstring + "datestart=" + $start.val() + "&dateend=" + $end.val();
                        $this.attr("href", href).removeClass("date-period").trigger("click").addClass("date-period");
                        $div.fadeOut();
                        this.open = false;

                        return false;
                    }).html("<span><span>"+agd.strings.enviar+"</span></span>");

                    $tr = $( document.createElement("tr") ).appendTo($table);
                        $td = $( document.createElement("td") ).attr("colspan",2).css({"text-align":"right",padding:"0"}).append($button).appendTo($tr);

                    $div.append($table);
                    agd.checkEvents($div);

                    this.extended = $div.get(0);
                    this.open = true;

                    agd.func.registerCallback('extended-buttons', function(){
                        $('.btn-extend').hide();
                    });
                    $div.hide().css('visibility','').fadeIn();
                }

                e.stopImmediatePropagation();
                return false;
            });


            //---- Eventos para la vista data
            if( agd.views.activeView == "data" ){

                //---- Botones que afectan a varias filas a la vez
                $("button", selector).click(function(){
                    var fn = $(this).attr("funcion");
                    if( fn && agd.func[ fn ] ){
                        agd.func[ fn ]();
                    }
                });


                //----
                $(selector).find(".multiple-action").click(function(e){
                    var $this = $(this), url = $this.attr("href") || $this.prop("href"), confirm = $this.prop("confirm"), rel = $this.prop("rel");

                    if( $this.hasClass("confirm") ){
                        confirm = ( confirm ) ? confirm : agd.strings.continuar;
                    };

                    if( url.split("")[0] == "#" ){
                        var queryString = agd.func.array2url( "selected", agd.func.selectedRows(false, rel) ), parts = queryString.split("");
                        if( !$.trim(queryString) && !$this.hasClass("continue") ){
                            return agd.func.jGrowl("sin_seleccionar", agd.strings.titulo_seleccionar );
                        }
                        if( parts[parts.length-1] == "&" ){ queryString = queryString.substring(0, queryString.length-1); }
                        url += "&" + queryString;
                        location.href = url;
                    } else {
                        agd.func.sendInfo({
                            url : $(this).prop("href"),
                            confirm : confirm ,
                            src : this
                        });
                    };
                    return false;
                });
            }

            //---- Activar y desctivar un parametro en la url
            $('.searchtoggle', selector).each(function(){
                var sName = $(this).attr("target"),
                    current = ahistory.getValue(sName),
                    paramValue = $(this).attr("value") || 1,
                    options = {};

                if (current && paramValue == current) $(this).addClass("selected");

                $(this).click(function(){
                    if( current && paramValue == current ){
                        ahistory.remove(sName);
                    } else {
                        options[ sName ] = paramValue;
                        ahistory.updateValue(options);
                    }
                });
            });

            //---- Elemento que con atributo href y aunque no sea un a, funciona
            $('.click', selector).click(function(){ alert(   $(this).attr("href") ); });

            //---- Los inputs que marcan como seleccionadas las lineas que las contienen
            $('input.line-assign', selector).click(function(){
                $( this ).closest("li").toggleClass("selected-element");
            });

            //---- Marcar como seleccionados todos los elementos input del formulario cuyo
            //---- Id esta definido en su atributo "rel" o bien target, como un selector css
            $('.checkall', selector).unbind('click').click( function(){
                if( target=$(this).attr("target") ){
                    var cssQuery = target + " input[type='checkbox']:visible", checkboxes = $(cssQuery);
                    var trigger = $(this).data("trigger");
                    if( $(checkboxes[0]).attr("checked") ){
                        checkboxes.removeAttr("checked");
                        checkboxes.closest("li").removeClass("selected-element");
                        checkboxes.closest("tr").removeClass("selected-row");

                        if(trigger) $(trigger).html($(trigger).data("init"));

                    } else {
                        checkboxes.attr("checked", true);
                        checkboxes.closest("li").addClass("selected-element");
                        checkboxes.closest("tr").addClass("selected-row");

                        if( trigger=$(this).data("trigger") ){
                            var init = $(trigger).data("init");
                            $(trigger).html(init+checkboxes.length);
                        }
                    }
                };

                return false;
            });

            // $('.delrel', selector).click( function(){
            //  ac = $(this).parents('form').attr('action');
            //  $(this).parents('form').attr('action',ac+'?delrel=1');
            //  return true;
            // });

            //---- Filtrar la tabla, funciona con atributos (ver funcion)
            $(".filter", selector).click( agd.func.filter );


            var $editor = $(selector).find("#editor");
            if( $editor.length && $editor.tinymce ){
                $editor.tinymce({
                    // Location of TinyMCE script (always same domain!!!)
                    script_url : '/res/js/tiny_mce/tiny_mce.js',

                    theme : "advanced",
                    plugins : "autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,strings",

                    // Theme options
                    theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect, cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
                    theme_advanced_buttons2 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen,insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage,|,strings",
                    theme_advanced_buttons3 : false,
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_statusbar_location : "bottom",
                    theme_advanced_resizing : false,

                    skin : "o2k7",
                    skin_variant : "silver"
                });
            };

            /** ENVIAR FORMULARIO Y SALIR DE NUEVO, MOSTRANDO EL RESULTADO VIA JGROWL */

            $(".form-to-back", selector).submit(function(){
                var form = this, callback = function( data ){
                    var rel = $(form).attr("rel");
                    if( rel && rel.length ){
                        location.hash = $(form).attr("rel");
                    }
                    $.jGrowl( data );
                };

                if( $(form).prop("method").toLowerCase() == "post" ){
                    $.post( $(form).attr("action"), $(form).serialize(), callback);
                } else {
                    $.get( $(form).attr("action") + "?" +  $(form).serialize(), callback);
                };
                return false;
            });




            /**
                PARA PODER ARRASTRAR Y SOLTAR ELEMENTOS EN CAMPOS DE ASIGNACION
            */

            var droppableOptions = {
                drop: function(ev, ui){
                    var newUl = $(this), newId = $(newUl).attr("id"), newString = newId.substring(0,  newId.lastIndexOf("-") ),
                    newGroup = newId.substring( newId.lastIndexOf("-")+1, newId.length );

                    var oldUl = $(ui.draggable).closest("ul"), oldId = $(oldUl).attr("id");
                    if( !oldUl || !oldId ){ return false; }

                    var oldString = oldId.substring(0,  oldId.lastIndexOf("-") ),
                         oldGroup = oldId.substring( oldId.lastIndexOf("-")+1, oldId.length );

                    if( newGroup !== oldGroup ){ agd.func.jGrowl("select","No puedes soltar este elemento aqui"); return false; }
                    if( newString === oldString ){ return false; }

                    newElement = $(ui.draggable).clone().prependTo( newUl );

                    $( ui.draggable ).remove();
                    $( ui.helper ).remove();


                    var input = $("input[type='hidden']", newElement)[0];
                    $(input).attr("name", $(input).attr("name").replace(oldString,newString) );


                    $( newElement ).draggable(draggableListOptions);

                    //------- los inputs que marcan como seleccionadas las lineas que las contienen
                    $('input.line-assign', newElement).click(function(){
                        $( this ).closest("li").toggleClass("selected-element");
                    });


                    //------- re-escalamos modalbox si esta presente
                    if( modalbox.exists() ){
                        modalbox.func.resize(function(){ agd.checkEvents( modalbox.body )} );
                    }
                },
                greedy: true,
                hoverClass: 'drophover',
                accept: 'li'
            },
            draggableListOptions = {
                revert: "invalid", scroll: false, appendTo: 'body', helper : "clone", start : function(){
                    if( $(modalbox.id).css("display") != "none" ){ return false; };

                    $this = $(this)
                        ,increment = 60
                        ,$ul = $(this).closest("ul")
                        ,id = $ul.attr("id")
                        ,ssearch = ( id.indexOf("e-a") ) ? "e-d" : "e-a"
                        ,sreplace = ( id.indexOf("e-a") ) ? "e-a" : "e-d"
                        ,newid = id.replace(ssearch, sreplace)
                        ,$newul = $("#"+newid)
                        ,ulheight = $newul.height()
                        ,ulpos = $ul.css("position")
                        ,$td = $newul.closest("td")
                        ,tdwidth = $td.width()
                        ,viewwidth = $(document).width()
                    ;

                    if( !$newul.get(0).droppable ){
                        $newul.droppable( droppableOptions );
                        $newul.get(0).droppable = true;
                    }

                    $ul.css("position", "");

                    $this.css("z-index", 99999);
                    $td.css({ width:$td.outerWidth()+increment, height:$td.outerHeight()+increment, margin:"-"+(increment/2)+"px 0 0 -"+(increment/2)+"px" }).addClass("zoom");

                    var offsetTD = $td.offset();
                    if( !offsetTD ){ return false; }

                    // comprobar overflow izquierdo
                    if( $td.offset().left < 10 ){ $td.css("margin-left", 0); }

                    // comprobar overflow derecho
                    newwidth = $(document).width();
                    if( viewwidth != newwidth ){ var rest = newwidth-viewwidth+10; $td.css("margin-left", "-"+((increment/2)+rest)+"px"); }

                    $newul.css("height", $td.height());
                    $("#cboxOverlay").css("opacity", 0.5).show();

                    $(document).one("mouseup", function(){
                        $ul.css("position", ulpos);
                        $td.removeAttr("style").removeClass("zoom").css("width", tdwidth);
                        $this.removeAttr("style");
                        $newul.css({"height":ulheight});
                        $("#cboxOverlay").hide();
                    });

                }
            };



            $("td.field-list li", selector).click(function(e){
                if( e.target && e.target.tagName.toUpperCase() == "LABEL" ){
                    $(this).find("a").click();
                }
            });

            if( $.browser.mozilla ){
                $("td.field-list li", selector).mouseover(function(){
                    if( !this.draggable ){
                        var input = $(this).find("input")[0];

                        if( input && input.disabled ){
                            $(this).draggable( draggableListOptions );
                            this.draggable = true;
                        }
                    }
                });
            };




            /**
                FILTRAR DATOS DIRECTAMENTE EN EL HTML
                    -   ATRIBUTO TARGET INDICA EL CONTEXTO DONDE SE BUSCARA COMO UN SELECTOR CSS
                    -   ATRIBUTO REL, INDICA EL SELECTOR DE LOS ELEMENTOS A FILTRAR
            */
            $(".find-html", selector ).keyup(function(e){
                var input = this, event = e, contextClassName = "find-html-loading";
                $(input).addClass(contextClassName);

                if( this.timeout ){ clearTimeout( this.timeout ); }
                this.timeout = window.setTimeout(function(){
                    var value= $(input).val().toUpperCase(),
                        dondeBuscar = ( $(input).attr("target") ).split(","),
                        len = dondeBuscar.length,
                        selector = $(input).attr("rel"),
                        stringInfo = $(input).attr("search");

                    if( stringInfo ){
                        stringInfo = stringInfo.replace("%s", value);
                    };

                    while(len--){
                        var lugar = dondeBuscar[len], $lugar = $(lugar), filtrar = false;

                        //$(lugar).css("width", $(lugar).css("width") );

                        var elementos = $lugar.find(selector), finds = elementos.length;
                        $(elementos).each(function(i, objeto){

                            var text = $.trim($(objeto).text()).toUpperCase();
                            if( text.indexOf(value) === -1 ){
                                filtrar = true;
                                $(objeto).css("display","none");
                            } else {
                                $(objeto).css("display","");
                            };

                            if( i === (finds-1) ){
                                if (modalbox.exists()){
                                    input.timeout = window.setTimeout(function(){
                                        modalbox.func.resize(null, {callback: function(){
                                            $(input).removeClass(contextClassName);
                                            $(input).focus();
                                        }});
                                    }, 150);
                                } else {
                                    $(input).removeClass(contextClassName);
                                };

                                if( $lugar.get(0).tagName.toLowerCase() == "select" ){
                                    try {
                                        $lugar.get(0).selectedIndex = $lugar.find("option:not(:hidden)").get(0).index;
                                    } catch(e) {}
                                };

                                if( stringInfo ){
                                    if( filtrar ){
                                        var aviso = $(document.createElement("div"))
                                            .css("width", $(lugar).width() )
                                            .addClass("filter-text")
                                            .appendTo( lugar )
                                            .html( stringInfo );

                                        $(lugar).css({"position":"relative", "padding-top": aviso.outerHeight()+3 });
                                    } else {
                                        $(lugar).css("padding-top","").find(".filter-text").remove();
                                    }
                                };


                                if( $lugar.hasClass("extended") ){
                                    try {
                                        if( event && event.keyCode == 13 ){
                                            $lugar.find("*[href]:visible").click();
                                        }
                                    } catch(e) {}
                                };
                            };

                        });
                    }
                }, 100);

            }).keypress(function(){
                if( this.timeout ){ clearTimeout( this.timeout ); }
            });



            $(".buscador-global").submit(function(e){
                if( $("#advanced-search", this).css("display") == "none" ){
                    var query = 'buscar.php?p=0&q='+ this.q.value;
                    location.hash = query;
                } else {
                    var strings = new Array(), els = this.elements, ln = els.length, send = false;

                    strings.push("tipo:agrupador");
                    while(ln--){
                        if(!ln) break;
                        var el = els[ln], field = $(el).attr("name"), val = $(el).val();

                        if( $.trim(val) && el.tagName.toLowerCase() != "button" ){
                            if( $.trim(field) ){
                                strings.push( field + ':"' + val + '"' );
                            } else {
                                strings.push( '"' + val + '"' );
                            }
                            send = true;
                        }
                    }

                    if( !send ){ return false; }

                    var query = 'buscar.php?p=0&q='+ strings.join(" ");
                    location.hash = query;
                };
                return false;
            }).find("#global-search-input").keyup(function(){

                var thiz = this.form;
                try{ clearTimeout(this.fireTimeout); } catch(e){};
                try{ this.ajax.abort(); } catch(e){};

                this.fireTimeout = window.setTimeout(function(){
                    if( !thiz.q.value && thiz.sugerencias ){ thiz.sugerencias.remove(); return; }
                    $(".loading", thiz).css("display","");
                    var query = 'buscar.php?p=0&q='+ thiz.q.value;
                    thiz.ajax = $.get(query,function(data){
                        if( !data || !$.trim(data) ){ $(".loading", thiz).css("display","none"); return false; }

                        try {
                            var json = agd.func.getJson(data), lineas = json.datos;
                            if( thiz.sugerencias ){ thiz.sugerencias.remove(); }
                            $.each(lineas, function(i, linea){
                                if( i === 0 ){
                                    thiz.sugerencias = $(document.createElement("table")).addClass("line-data").css("width","100%").insertAfter(thiz);
                                }
                                row = agd.func.rowFromData(linea, json.tabla, json.maxcolums, i, lineas.length);
                                $(row).appendTo( thiz.sugerencias );
                            });
                        } catch(e){};
                        $(".loading", thiz).css("display","none");
                    });
                }, 300);
            });



            function launchTreefunction (e) {
                var self = this;

                self.open = self.open || false;


                var href = $(self).attr('href'),
                    row = $(self).closest('tr'),
                    columnLength = $(row).find('td').length,
                    appendRow = $(document.createElement("tr")).addClass("desplegable").css("display","none"),
                    appendTd = $(document.createElement("td")).addClass("no-hover no-padding").css("overflow-y","visible").attr("colspan", columnLength).appendTo(appendRow),
                    wrapDiv = $(document.createElement('div')).addClass("nested").appendTo(appendTd),
                    appendTable = $(document.createElement("table")).attr({"cellpadding":"0", "cellspacing":"0", "border":"0"}).appendTo(wrapDiv)
                ;

                function drawNestedTable (res) {
                    // --- solo si hay datos..
                    if (res.datos) {
                        var len = res.datos.length;
                        $.each(res.datos, function(i, linea){
                            var nestedRow = agd.func.rowFromData(linea, 1.3, res.maxcolums, i, len);
                            //$(row).find(".checkbox-colum").addClass("row-link").find("img").css("margin-left", "22px");
                            appendTable.append(nestedRow);

                            if (i === (len-1)) {
                                $(row).after(appendRow);
                                $(appendRow).show();

                                agd.checkEvents(appendRow);
                            }
                        });
                    }
                };

                if (self.open) {
                    $(self.tree).hide();
                    self.open = row.get(0).isTreeVisible = false;

                } else {
                    if (self.tree) {
                        self.open = row.get(0).isTreeVisible = true;
                        $(self.tree).show();
                        return false;
                    }

                    $loading.show();

                    $.ajax({
                        url: href,
                        dataType: 'json',
                        beforeSend : function(xhr, settings){
                            xhr.setRequestHeader("X-TREE", true);
                        },
                        success: function (res) {
                            self.tree = appendRow;
                            self.open = row.get(0).isTreeVisible = true;
                            $loading.hide();
                            drawNestedTable(res);
                        }
                    });
                }


                return false;
            };

            $(selector).find('.treerow').click(launchTreefunction).each(function (){
                var row = $(this).closest('tr').get(0);

                if (row && row.isTreeVisible) {
                    launchTreefunction.call(this);
                }
            });


            var onModalBox = ($(selector).attr("id") === "cboxLoadedContent");
            if (onModalBox) {

                var $context = $(selector);
                if ($context.find("#buscador-avanzado").length) {
                    require([agd.staticdomain + "/js/app/advancedsearch.min.js?" + __rversion], function () {
                        enableAdvancedSearch($context);
                    });
                };


                $("body > .helper").remove(); // eliminamos anteriores
                $(".helper", selector).each(function(){
                    var  $this = $(this).clone(true).appendTo(document.body)
                        ,$target = $( $this.attr("target") )
                        ,offset = $target.offset()
                        ,ctarget = $(this).attr("canceltarget")
                        ,cevent = $(this).attr("cancelevent")
                        ,$filter = $($(this).attr("filter"))
                    ;

                    if( $filter.length ){ return; } // cancelamos si no se cumple el filtro

                    $this.removeAttr("canceltarget").removeAttr("cancelevent");
                    $(this).remove();

                    if( offset ){
                        var  left = ( offset.left-$this.outerWidth()+10 )
                            ,top = ( offset.top - 40 )
                        ;

                        if( left < 0 ){
                            sum =  (0 - left) + $(modalbox.id).offset().left;

                            $(modalbox.id).css("left", sum+"px");
                            left = 5;
                        }
                        $this.css({ top:top, left:left }).show();


                        $(document).one(modalbox.event.cleanup, function(){
                            $this.remove();
                        });

                        $(ctarget).bind(cevent, function(){
                            $this.remove();
                        });
                    } else {

                    }
                });
            };


            if( !nocallback  ){
                (function invokeCallbacks(){
                    if( !agd.loaded ){
                        setTimeout(invokeCallbacks, 100);
                        return false;
                    };
                    for( callbackname in agd.callbacks ){
                        // Comprobar eventos por defecto
                        if( callbackname.indexOf("default-") == -1 ){
                            agd.callbacks[ callbackname ]( json, where );
                        }
                    };
                })();
                //invokeCallbacks();
            }

            $(selector).find('.load').click(function(e) {

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
                //  $this.remove(); //removing link
                    e.stopImmediatePropagation();
                    return false;

            });

            $(selector).find("*[title]").tipsy({gravity: function () {
                var customGravity = $(this).data('gravity');
                if (customGravity) return customGravity;

                return $.fn.tipsy.autoNS.apply(this);
            }, html: true });



            $(selector).find('.modalframe').click (function (e) {
                e.preventDefault();
                var sideframe, wrapper, left, top, height;

                wrapper     = $("#colorbox");

                wrapper.css('margin-left', '-150px');

                left        = wrapper.offset().left + wrapper.outerWidth()
                top         = wrapper.offset().top;
                height      = wrapper.outerHeight() - 20;
                sideframe   = $("#modalframe").get(0);

                if (!sideframe) {
                    sideframe = $(document.createElement('iframe')).attr({'id':'modalframe', 'border':0, 'frameborder':0 }).get(0);
                    $('body').append(sideframe);
                }

                $(sideframe).css({
                    left: left,
                    top: top,
                    height: height
                });

                function hide () {
                    wrapper.css('margin-left', '0px');
                    $(sideframe).remove();
                };

                $(document).one(modalbox.event.cleanup, hide);
                $(document).one(modalbox.event.load, hide);


                sideframe.src = this.href;
            });



            $(document).trigger("checkevents_complete");

            if (hideLoading) $loading.hide();
            //modalbox.func.resize();
            //------- despues de todos los eventos

        }, //checkEvents


        func : {
            formatBytes : function(bytes, precision){
                var kilobyte = 1024;
                var megabyte = kilobyte * 1024;
                var gigabyte = megabyte * 1024;
                var terabyte = gigabyte * 1024;

                if ((bytes >= 0) && (bytes < kilobyte)) {
                    return bytes + ' B';
                } else if ((bytes >= kilobyte) && (bytes < megabyte)) {
                    return (bytes / kilobyte).toFixed(precision) + ' KB';
                } else if ((bytes >= megabyte) && (bytes < gigabyte)) {
                    return (bytes / megabyte).toFixed(precision) + ' MB';
                } else if ((bytes >= gigabyte) && (bytes < terabyte)) {
                    return (bytes / gigabyte).toFixed(precision) + ' GB';
                } else if (bytes >= terabyte) {
                    return (bytes / terabyte).toFixed(precision) + ' TB';
                } else {
                    return bytes + ' B';
                }
            },
            removeInPageAlert : function(li){
                var $menu = $("#menu-avisos"), $lista = $menu.find("ul"), boton = $("#boton-avisos"), $numeroAvisos = $("#numeroavisos"), $this = $(li);

                $this.slideToggle(function(){
                    $this.remove();

                    if( !$lista.find("li").length ){
                        $menu.find(".avisos-principal").css("display","none");
                        if (boton.is(':visible')) {
                            // aqui solo ocultamos cuando está visible.
                            boton.slideToggle();
                        }
                    };

                    $numeroAvisos.html( $lista.find("li").length );
                });
            },
            getAttributes : function(item){
                item = ( item.get ) ? item.get(0) : item;
                var attributes = item.attributes, attrs = {};
                for(i in attributes){
                    var attr = attributes[i];
                    attrs[ attr.nodeName ] = attr.nodeValue;
                };
                return attrs;
            },
            validateForm : function(form){
                var checkblank = function(form, obj) {
                    if ( obj ) {
                        if ( $(obj).val() ) {
                            agd.func.removeInputAlert(obj);
                        }
                        return false;
                    }

                    var formErrors = $("input[rel='blank']:visible, textarea[rel='blank']:visible, select[rel='blank']:visible", form),l = formErrors.length, ln = l, matches = [];
                    while (ln--) {

                        var i = l - ln - 1, input = formErrors[i];
                        var inputPass = (function(input) {
                            if ( $(input).val()=="" ) {
                                agd.func.addInputAlert( input, "No Valido");
                                $(input).blur(function() { checkblank(form, input); });
                                return false;
                            } else {
                                agd.func.removeInputAlert(input);
                            }
                            return true;
                        })(input);
                        if ( inputPass === false ) {
                            matches.push(input);
                        }
                    }
                    if ( matches.length ) {
                        $(matches[0]).focus();
                        return false;
                    } else {
                        return true;
                    }
                };

                var errorIncompatibles = false;
                $(form).find("input[type=checkbox]").each(function(){
                    var errorIncompatiblesString = "";
                    var valorCampo = $(form).find("input[type='hidden'][name='"+$(this).data('namediv')+"']").val();
                    var incompatibleVars = $.trim($(this).data("incompatible"));
                    if (incompatibleVars!=""){
                        incompatibleVars=incompatibleVars.split(",");
                        for (var i = 0; i < incompatibleVars.length; i++) {
                            var elementoFormVal = $(form).find("input[type='hidden'][name='"+incompatibleVars[i]+"']").val();
                            var nametranslate =  $(form).find("input[type='checkbox'][data-namediv='"+incompatibleVars[i]+"']").data("nametranslate");
                            if (valorCampo == elementoFormVal && valorCampo == "1" ){
                                errorIncompatiblesString += " " + nametranslate + ",";
                            }
                        }
                        if (errorIncompatiblesString!=""){
                            errorIncompatiblesString = errorIncompatiblesString.slice(0, -1);
                            alert(agd.strings.form_el_campo +": " + $(this).data("nametranslate") + " " + agd.strings.form_es_incompatible + " " + errorIncompatiblesString);
                            errorIncompatibles = true;
                            return false;
                        }
                    }
                });


                if (errorIncompatibles){
                    return false;
                }

                var inputError = checkblank(form);
                var arrayComparaciones = $(form).find("input[match]");
                if ( arrayComparaciones[0] ) {
                    var l = arrayComparaciones.length;
                    for (i=0; i<l; i++) {
                        var input = arrayComparaciones[i];
                        var re = new RegExp( $(input).attr("match") );
                        var matches = $(input).val().match(re);
                        if ( !matches ) {
                            agd.func.addInputAlert( input, "No Valido");
                            return false;
                        } else {
                            agd.func.removeInputAlert( input, "No Valido");
                        }
                    };
                }

                arrayComparaciones = $(form).find("input.mustfocus");
                if ( arrayComparaciones[0] ) {
                    var l = arrayComparaciones.length;
                    if( l ){
                        var input = arrayComparaciones[0], alertString = agd.strings.no_olvides_comprobar.replace("%s", $(input).attr("name"));
                        if ( btn = $("button.send", form).get(0) ) {
                            if ( btn.restore ) {
                                btn.restore();
                            }
                        }
                        alert(alertString);
                        return false;
                    };
                };

                arrayComparaciones = $(form).find("input.needconfirm");
                if ( arrayComparaciones[0] ) {
                    var l = arrayComparaciones.length;
                    var value = arrayComparaciones[0].value;

                    if( l && (value == '')){
                        var input = arrayComparaciones[0], alertString = arrayComparaciones.data('confirm') || agd.strings.continuar;
                        if ( btn = $("button.send", form).get(0) ) {
                            if ( btn.restore ) {
                                btn.restore();
                            }
                        }
                        if (!confirm(alertString)) return false;
                    };
                };

                for(name in agd.inputs){
                    var $arrayBlurs = $(form).find("input[name='"+name+"']");
                    if( l=$arrayBlurs.length ){
                        for(i=0;i<l;i++){
                            var result = agd.inputs[name].call($arrayBlurs[i]);
                            if( result ){
                                agd.func.removeInputAlert($arrayBlurs[i], "No Valido");
                            } else {
                                agd.func.addInputAlert($arrayBlurs[i], "No Valido");
                            };
                            if( result === false ){ $arrayBlurs[i].focus(); return false;  }
                        }
                    };
                };


                arrayComparaciones = $(form).find("input.mustverify");
                if ( arrayComparaciones[0] ) {
                    var l = arrayComparaciones.length, result = true;
                    for (i=0;i<l;i++) {
                        var input = arrayComparaciones[i];

                        if ( !$(input).hasClass("error") ) {

                            var verifybtn = agd.create.button({innerHTML:agd.strings.click_verificar_campo,img:agd.staticdomain+"/img/famfam/tick.png"});

                            var removeButton = (function(verifybtn, input){
                                return function(){
                                    $(verifybtn).remove();
                                    $(input).removeClass("mustverify error");
                                    return false;
                                };
                            })(verifybtn, input);

                            $(verifybtn).click(removeButton);

                            $(verifybtn).insertAfter(input);
                            $(input).addClass("error");
                        };

                        agd.func.shake(input.parentNode);

                        if( btn = $("button.send", form).get(0) ){
                            if( btn.restore ){ btn.restore(); }
                        };
                    };
                    return false;
                };

                if (form) {
                    if ($(form).data("must")){
                        var mustcheck = $(form).data("must"),
                            txt = $(form).data("alert")
                        ;
                    }else{
                        var sender = $(form).data("sender");
                        if (sender != undefined) {
                            var mustcheck = $(sender).data("must"),
                                txt = $(sender).data("alert");
                        } else {
                            var mustcheck = $("button.send", form).data("must"),
                                txt = $("button.send", form).data("alert");
                        }
                    }
                }

                if (mustcheck) {
                    mustcheck=mustcheck.split(",");
                    var allElementsRight = true;
                    $.each(mustcheck, function(key, value){
                        arrayComparaciones = $(value);
                        if (arrayComparaciones[0]) {
                            anychecked = false;
                            arrayComparaciones.each(function(){
                                if ((this.type=="checkbox" && this.checked) || ($(this).is("textarea") && $(this).val())){
                                    anychecked = true;
                                }
                            });

                            if (!anychecked) {
                                allElementsRight = false;
                                var allText = txt.split(",");
                                alert(allText[key] || agd.strings.error_must_default)
                                if ( btn = $("button.send", form).get(0) ) {
                                    if ( btn.restore ) {
                                        btn.restore();
                                    }
                                }
                                return false;
                            }
                        }
                    });

                    if (allElementsRight) return true;
                    else return false;

                }

                if( !inputError ){
                    if( btn = $(form).find("button.send").get(0) ){
                        if( btn.restore ){ btn.restore(); }
                    };
                    return false;
                };
                return true;
            },
            simular : function(user){
                var href = "../simular.php?poid=" + user;
                $.getJSON(href, function(data){
                    if( data.page && data.page != location.hash ){
                        location.href = data.page;
                    } else {
                        agd.navegar();
                    }

                    if( data.client ){
                        //agd.func.setupInterfaceForClient(data.client);
                        modalbox.func.close();
                        if (polling) polling.update();
                        agd.loadUserData();
                    }
                    if( data.tabs ){
                        $menu = $("#main-menu ul");
                        $menu.empty();
                        $.each(data.tabs, function(i, tab){
                            $li = $(document.createElement("li")).css("margin","0").addClass("line-block").attr({name:tab.name}).appendTo($menu);
                            $a = $(document.createElement("a")).attr({"href":tab.href}).appendTo($li);
                            if( tab.imgpath ){
                                $img = $(document.createElement("img")).attr({"src": tab.imgpath}).appendTo($a);
                            } else {
                                $img = $(document.createElement("img")).attr({"src":agd.staticdomain+"/img/32x32/iface/"+tab.name+".png"}).appendTo($a);
                            }

                            if( tab.icononly != 1 ){
                                var string = ( agd.strings["menu_"+tab.name] !== undefined ) ? agd.strings["menu_"+tab.name] : tab.name;
                                $a.append( string );
                            }
                        });
                    };
                    if( data.configurar != undefined ){
                        var method = ( data.configurar ) ? "show" : "hide";
                        $("#config-link")[ method ] ()
                    };
                    if( data.validar != undefined ){
                        var method = ( data.validar ) ? "show" : "hide";
                        $("#validar-link")[ method ] ()
                    };
                });

                return false;
            },
            /*setupInterfaceForClient : function(client){
                var $style = $("#main-style"), css = agd.staticdomain + "/css/main.min.css?lang="+agd.locale+"&c="+client;
                $style.attr("href", css);
            },*/
            shake : function(doms){
                $(doms).effect("shake", { distance:10, times:3 }, 100);
            },
            titleAlert : function(msg){
                if( window.__titleinterval ){ clearInterval(window.__titleinterval); }
                window.__titleinterval = setInterval(function(){
                    document.title = ( document.title == agd.title ) ? msg : agd.title;
                },800);
                return function(){
                    document.title = agd.title;
                    clearInterval(window.__titleinterval);
                };
            },
            changeColor : function(color){
                var $style=$("#main-style"), href=$style.attr("href");

                if( href.indexOf("maincolor") == -1 ){
                    href += "&maincolor=" + color;
                } else {
                    var page = ahistory.getPage(href), params = ahistory.getParams(href);
                    params.maincolor = color;
                    href = page + "?" +  decodeURIComponent($.param(params))
                }
                return $style.attr("href", href);
            },

            fixLayout: function (table, row) {
                row = row || table.rows[0];

                var columns = [], cells = row.cells, len = cells.length;

                $.each(cells, function (i, column) {
                    var def = document.createElement('col'),
                        w = $(column).width();

                    $(def).attr('width', w + 'px');
                    columns.push(def);

                    if (i+1 === len) {
                        $(table).prepend(columns);
                    }
                });
            },

            extractDate : function(string){
                try {
                    var date = false,
                        regexp = new RegExp('([0-9]{4}|[0-9]{2})[._-]([0]?[1-9]|[1][0-2])[._-]([0]?[1-9]|[1|2][0-9]|[3][0|1])'),
                        matches = string.match(regexp)
                    ;

                    if( matches ){
                        var year = matches[1],
                            month = matches[2],
                            day = matches[3]
                        ;

                        if( year.split("").length == 2 ){ year = "20"+year; }
                        if( month.split("").length == 1 ){ month = "0"+month; }
                        if( day.split("").length == 1 ){ day = "0"+day; }

                        date = new Date( year+"-"+month+"-"+day );
                        return date;
                    }
                } catch(e) {};

                return false;
            },
            changeProfile : function cambiarPerfil(id, toCompany){
                var     $cuerpo = $("#cuerpo")
                    ,   $credits = $("#credits")
                    ,   menuname = $("#main-menu .seleccionado").attr("name")
                    ,   ct = ( new Date() ).getTime()
                    ,   url = '../chgperfil.php?pid='+id+'&rt=' + ct
                    ,   defError = agd.callbacks["default-error"]
                ;


                if (toCompany) {
                    url = 'empresa/jump.php?poid=' + id;
                    location.href = "#home.php";
                }

                $("#lista-perfiles").hide();
                $("#cboxOverlay").css({"opacity":"1","z-index":"190"}).show();

                agd.func.registerCallback("default-error", function(resp){
                    location.hash = $("#main-menu li:first-child a").attr("href");
                    // dejamos el controlador habitual
                    agd.func.registerCallback("default-error", defError);
                });

                $.get(url, function(data){
                    if (json = agd.func.getJson(data)) {
                        return agd.actionCallback(json);
                    }

                    var     $body = $( data.substring( data.indexOf('<body>')+6, data.indexOf('</body>') ) )
                        ,   $head = $( "<div>" + data.substring( data.indexOf('<head>')+6, data.indexOf('</head>') ) + "</div>" )
                        ,   replaces = ["#head", "#credits", "#lista-perfiles", "#link-perfiles", "#config-actions", "#inline-style", "#top-bar-left", "#top-bar-right"]
                        ,   l = replaces.length
                    ;

                    $("#link-perfiles").removeClass("extended");

                    if( $body.find(".avisos-principal").find("li").length ){
                        $(document.body).find(".avisos-principal").html( $body.find(".avisos-principal").html() );
                        $("#numeroavisos").html( $(".avisos-principal li").length );
                        $("#boton-avisos").show();
                    }

                    while(l--){
                        var selector = replaces[l], html = $body.add($head).find(selector).html();
                        $(selector).html(html);
                        agd.checkEvents(selector);
                    };
                    agd.elements.menu = $("#main-menu ul").get(0);

                    agd.func.registerCallback("profile-change", function(resp){
                        agd.cache.url = {};
                        delete( agd.callbacks["profile-change"] );

                        // dejamos el controlador habitual
                        agd.func.registerCallback("default-error", defError);

                        currentNavigation = agd.elements.navegacion.innerHTML;
                        agd.elements.navegacion = $("#informacion-navegacion").html(currentNavigation).get(0);

                        $(".bad-profile").remove();

                        if( $(modalbox.id).css("display") != "none" ){
                            try {
                                $("#cboxOverlay").css({"z-index":"99","opacity":0.3});
                                agd.func.open( agd.history[ agd.history.length-1 ].url );
                            } catch(e) {}
                        } else {
                            $("#cboxOverlay").css("z-index","99").hide();
                        }

                        try {
                            $pluginscript = $("#pluginjs");
                            $pluginscript.clone().attr("src", "../pluginscript.js?t=" + ct).appendTo("head");

                        } catch(e) { };
                    });

                    agd.loadUserData(function(){
                        ahistory.onChange();
                    });
                });
            },
            showSelectedItems : function(returnRows){
                var pos = agd.views[ agd.views.activeView ].elements.bottom,
                    links = new Array(), rows = new Array(),
                    total = new Array(), totalCount = 0;

                for( i in agd.tables ){
                    var ob = agd.tables[i];
                    if( typeof(ob) == "object" ){
                        if( ob.info ){ totalCount+= parseInt(ob.info.rows); }
                        var data = ob.data;
                        for( j in data ){
                            var tr = data[j];
                            if( tr && (lineCheckbox=$(tr).find(".checkbox-colum input").get(0)) && lineCheckbox.checked ){
                                rows.push( tr );
                                tipo = tr.tipo;
                                if( !tipo || !isNaN(tipo) ){ continue; }

                                if( tipo.indexOf("-") != -1 ){
                                    tipo = tipo.substring(0, tipo.indexOf("-"));
                                }
                                if( tipo.indexOf("_") != -1 || tipo == "buscar" || tipo == "busqueda" ){ continue; }
                                if( !links[ tipo] ){ links[ tipo ] = new Array(); }
                                links[ tipo ].push( tr.uid );
                                total.push( tr.uid );
                            }
                        };
                    }
                };

                if( returnRows ){
                    return rows;
                }


                $(pos).find("#informacion-seleccion, #cancelar-seleccion, .selection-op").remove();
                if( total.length ){
                    try {

                        var href = "#buscar.php?p=0&q=",
                            info = $( document.createElement("a") ).attr("id", "informacion-seleccion"),
                            cancel = $( document.createElement("a") ).attr("id", "cancelar-seleccion").html(agd.strings.cancelar_seleccion).click(function(){
                                agd.func.clearSelectedItems();
                                agd.func.showSelectedItems();
                            });

                        for( tipo in links ){
                            if( typeof(links[tipo]) == "object" ){
                                href += "tipo:" + tipo + "#" + links[tipo].join(",") + "+";
                            }
                        }

                        href = href.substring(0, href.length-1);


                        info.html( total.length + " " + agd.strings.elementos_seleccionados)
                            .attr("href", href)
                            .appendTo( pos );

                        cancel.appendTo( pos );

                        var selfIndex = links.empresa.indexOf(agd.empresa);
                        if (selfIndex) delete(links.empresa[selfIndex]);
                    } catch(e) { }
                }

                return { rows : rows, count : totalCount, total : total }
            },
            clearSelectedItems : function(items){
                if( items ){
                    $.each(items, function(table, list){
                        var storage = agd.tables[ table ];
                        if( typeof storage == 'string' ){ storage = agd.tables[storage]; }
                        $.each(list, function(i, uid){
                            try {
                                delete( storage.data[ uid ] );
                            } catch(e) { }
                        });
                    });
                } else {
                    var rows = agd.func.showSelectedItems(true), len = rows.length;
                    while(len--){
                        var tr = rows[len], cb = $(tr).find(".checkbox-colum input");
                        $(cb).removeAttr("checked");
                        $(tr).removeClass("selected-row");
                    }
                }
            },
            getCookie : function(sName){
                var cookies = document.cookie.split( ';' ), l = cookies.length;
                while(l--){
                    var cookie = cookies[l], aux = cookie.split("=");
                    if( sName == $.trim(aux[0]) ){
                        return $.trim(aux[1]);
                    }
                };
                return false;
            },
            getJson : function( string ){
                try{
                    oJson = JSON.parse( string );
                } catch(e){
                    return false;
                };
                return oJson;
            },
            rowFromData : function(linea, fromTree, maxCols, rowIndex, rowsLength, response) {
                var columns = linea[linea.key];

                if (!columns) return;

                var trObject = document.createElement('tr'),
                    positionTd = $(document.createElement("td")).addClass("position-colum");

                //------ creamos la primera columna
                $checkBoxTD = $( document.createElement("td") ).attr("align","left").addClass("checkbox-colum");
                if (fromTree) {
                    var widthTD = (44*(fromTree-1))+18,
                    $treeOPTD = $( document.createElement("td") ).css({"width": widthTD+"px", "text-align":"left"}).appendTo(trObject);

                    if (response && response.tabla) {
                        if (rowIndex == 0 && (!rowsLength || rowsLength > 1)) {
                            $(document.createElement("input")).attr({"type":"checkbox","title":agd.strings.marcar_desmarcar}).click(function(){
                                var checked = this.checked, tbody = trObject.parentNode, l = tbody.rows.length;
                                while (l--){
                                    var row = tbody.rows[l];
                                    if( row.cells.length > 1 ){
                                        $(row).toggleClass("selected");
                                        $(row.cells[1].childNodes[1]).attr("checked", checked);
                                    }
                                };
                                agd.func.showSelectedItems();
                            }).appendTo($treeOPTD);
                        }
                    }

                    if (linea.tree) {
                        $checkBoxTD.css("width", "64px");
                    }
                };

                if (response && response.tabla) {
                    $checkBoxTD.appendTo(trObject);
                } else {
                    if (fromTree) {
                        positionTd.css('width', '30px').addClass('row-link');
                    }
                }

                //------ creamos la linea documente posicion
                positionTd.appendTo(trObject);


                //------ añadimos el resto de columnas
                var numcols = 0;
                $.each(columns, function(field, value) {
                    numcols++;
                    if (typeof(value) == "string") {
                        $td = $(document.createElement("td")).html(value);
                    } else {
                        var type = ( value && value.href ) ? "a" : "span";
                        type = ( value.tagName ) ? value.tagName : type;
                        delete(value.tagName);
                        var $span = $(document.createElement(type)).prop( value );

                        $td = $(document.createElement("td"));
                        if (value.icon) {
                            try {
                                var $icon = $(document.createElement('img')).attr(value.icon).prop(value.icon).css({"vertical-align":"middle", "width":"14px", "margin-right":"3px"});

                                if (value.icon.href) {
                                    $icon.css("cursor", "pointer").click(function(){
                                        location.href = value.icon.href;
                                    });
                                }

                                $td.append($icon);
                            } catch (e) {}
                        };

                        $td.append($span);

                        if( value && value.href ){
                            $span.bind("dragstart", function(e){
                                $(trObject).attr("id", linea.key);
                                var keyElement = linea.key;
                                e.originalEvent.dataTransfer.setData("text", keyElement.toString());
                            });
                        }
                    }
                    $(trObject).append( $td );
                });

                //------ creamos el objeto linea
                var lineStatus = (typeof linea.estado != "undefined") ? "stat-"+linea.estado.toString() : "";
                if (linea.papelera && linea.papelera == 1) {
                    lineStatus += " row-papelera";
                }

                if (linea.className) {
                    lineStatus += " " + linea.className;
                    linea = $.extend({}, linea); delete(linea["className"]); // clonamos y eliminamos para que la cache funcione bien
                }

                $(trObject).addClass(lineStatus);

                trObject.uid = linea.key;


                if (linea.tree && linea.tree.img) {
                    var imagen = $( document.createElement("img") ).attr("src", linea.tree.img.normal);
                    $checkBoxTD.html( imagen );
                    trObject.img = imagen;
                }

                if (linea.href) {
                    $(trObject).addClass("clickable-row").click(function(e){
                        if( e.target.tagName != "INPUT" && e.target.tagName != "A" &&
                            e.target.tagName != "SELECT" && e.target.tagName != "OPTION" &&
                            !$(e.target).closest("ul").parent().parent().hasClass("select") ){
                            agd.func.lineoption(linea);
                            return false;
                        }
                    });
                }

                if (!linea.tree || linea.tree.checkbox) {
                    //------ creamos el input
                    var inputCheck = $( document.createElement("input") )
                        .addClass("line-check")
                        .attr({
                            type:"checkbox",
                            "name":linea.key
                        }).appendTo( $checkBoxTD ).click(function(){
                            agd.func.showSelectedItems();
                        });

                    trObject.checkbox = inputCheck;
                    inputCheck.get(0).row = trObject;
                };

                // --- Si es una linea con vistar jerarquica, aplicamos la clase para los estilos
                // --- y el evento desplegable
                if (linea.tree && !linea.href) {
                    // aplicar la clase
                    if (linea.tree.url) {
                        $(trObject).addClass("tree");

                        // Referenciar el objeto
                        trObject.tree = linea.tree;

                        // Aplicar los eventos..
                        var treeView = agd.create.treeView(trObject, fromTree);

                        if (linea.tree.autoload) {
                            if (!agd.loaded) {
                                var callback = agd.func.registerCallback("tree-autoload", function(){
                                    treeView();

                                    delete(callback);
                                });
                            } else {
                                window.setTimeout(function(){
                                    treeView();
                                },100);
                            }
                        };
                    }
                }


                //------ PROCESARA CUALQUIER ELEMENTO QUE SE QUIERA VER EN LA MISMA LINEA
                if( linea.inline ){
                    var numInlineElements = 0;
                    $.each( linea.inline, function(inlineName, inline){
                        var tdInline = $(document.createElement("td")).addClass("inline-colum");

                        if( inline && ( inline[0] || inline.width ) ){
                            if( isNaN(inlineName) ){
                                $( tdInline ).addClass(inlineName);
                                $( document.createElement('span') ).addClass('light').html( inlineName + ": " ).appendTo( tdInline );
                            };

                            if( inline.img ){
                                var $img = $(document.createElement("img")), href;


                                if (inline.img.href) {
                                    href = inline.img.href;

                                    if (href.split("")[0] == "#" || href.indexOf('http') !== -1) {
                                        $img.on('click', function () {
                                            location.href = href;
                                        });
                                    } else {
                                        $img.attr("href", href);
                                    }
                                    delete(inline.img.href);
                                };

                                if( typeof inline.img == "object" ){
                                    $img.prop(inline.img);
                                } else {
                                    $img.prop("src", inline.img);
                                }
                                $img.appendTo(tdInline);
                                $( document.createTextNode(" ") ).appendTo(tdInline);
                            };

                            $.each(inline, function(prop, elemento){
                                var value = inline[prop];
                                if( isNaN(prop) ){
                                    switch(prop){
                                        default:
                                            $(tdInline).attr(prop, value);
                                            if( !$.browser.msie ){ $(tdInline).prop(prop, value); }
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


                                var className = (elemento.className) ? elemento.className : "", href = "";

                                if (elemento.nombre) {
                                    if (elemento.tipo && elemento.oid) href = "ficha.php?m="+elemento.tipo+"&oid=" + elemento.oid;
                                    if (elemento.href) href = elemento.href;

                                    var blacklistClassName = ['extended-cell', 'send-info'], inBlackList = false;
                                    for (i in blacklistClassName) {
                                        if (className.indexOf(blacklistClassName[i]) !== -1) {
                                            inBlackList = true;
                                            break;
                                        }
                                    }

                                    if (!elemento.target && href && href.split("")[0] !== "#" && !inBlackList) className+=" box-it";

                                    var tag = (elemento.tagName) ? elemento.tagName : "a";

                                    $a = $(document.createElement(tag)).addClass("ucase inline-text "+className).html( elemento.nombre ).appendTo(tdInline);

                                    if (href) $a.attr("href", href);

                                    if (elemento.title) $a.prop("title", elemento.title);
                                    if (elemento.target) $a.prop("target", elemento.target);
                                }

                                if (elemento.img) {
                                    var $img = $(document.createElement("img")).css({"margin-right":"4px","padding-bottom":"4px"})
                                    .attr(elemento.img).appendTo(tdInline);

                                    if (elemento.img.href && (elemento.img.href.split("")[0] == "#" || elemento.img.href.indexOf('http') !== -1)) {
                                        $img.on('click', function () {
                                            location.href = elemento.img.href;
                                        });
                                    }
                                };



                                /*if( elemento.estado ){
                                    var estatusClass = ( elemento.estado.indexOf('<') == -1 ) ? "stat_"+elemento.estadoid : "";

                                    $( document.createElement("a") ).html( elemento.estado  )
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


                                if( elemento.extra ){
                                    $.each(elemento.extra, function(j, html){
                                        $(tdInline).append(html);
                                    });
                                };

                                var lastSeparator = $( document.createElement("div") ).html("").addClass('line-block inline-separator').attr("width", "4px").appendTo( tdInline );
                            });

                            $( trObject ).append( tdInline );

                            numInlineElements++;
                        }
                    });

                    // var numTotalColums = response.maxcolums + numInlineElements;
                }



                //------- AJUSTAMOS EL NUMERO DE COLUMNAS
                numInlineElements = ( numInlineElements ) ? numInlineElements : 0;
                numcols = numcols + numInlineElements;
                if( maxCols ){
                    if( (!linea.options||!linea.options.length) ){ numcols--; }
                    if( numcols < maxCols ){
                        var newcols = maxCols - numcols;
                        while( newcols-- ){
                            var td = $( document.createElement("td" ) ).html("&nbsp;").appendTo( trObject );
                        }
                    }
                }


                if( linea.options && linea.options.length ){

                    //------ preparamos las opciones de esta linea
                    opcioneslinea = new Array( { innerHTML : agd.strings.opciones + "...", selected : true } );
                    opcioneslinea = opcioneslinea.concat( linea.options );

                    //------ creamos el select
                    var selectOption = agd.create.select( { id : "opt_"+linea.key , options : opcioneslinea, inline : linea.inlineoptions } );
                    //------ añadimos la ultima columna
                    var td = $( document.createElement("td" ) ).addClass("option-colum").append( selectOption ).appendTo( trObject );
                }

                //------- añadimos los elementos dinamicos a las columnas correspondientes
                //$( trObject ).find("td.option-colum").append( selectOption );

                if( linea.type ){
                    var currentTable = linea.type
                } else {
                    var currentTable = ( fromTree ) ? fromTree : agd.tables.current;
                }
                if( !agd.tables[ currentTable ] ){
                    var curtable = agd.tables.create( currentTable );
                    if( response && response.paginacion && response.paginacion.of ){
                        curtable["info"] = { rows : response.paginacion.of };
                    }
                }
                trObject.tipo = currentTable;



                if( previousTr = agd.tables[ currentTable ].getData(linea.key) ){

                    if( $( previousTr ).hasClass("selected-row") ){
                        $( inputCheck ).attr("checked", true);
                        $( trObject ).addClass("selected-row");
                    }


                    if (previousTr.isTreeVisible) {

                        if (treeView) {
                            treeView();
                        } else {
                            trObject.isTreeVisible = true;
                        }

                    }


                    if( !isNaN(previousTr.progress) ){
                        if( trObject.upload = previousTr.upload ){
                            trObject.upload.row = trObject;
                            trObject.progress = previousTr.progress;
                            agd.func.registerCallback("upload-restore", function(){
                                trObject.upload.show.call( trObject );
                            });
                        }
                    }
                }

                //------ si es la primera vez debemos borrar la precarga... TIENE QUE SER ANTES DE COMPROBAR LA POSICION DE LOS SELECT YA QUE SI NO NO EXISTEN
                agd.load( true );

                try {
                    trObject.rawoptions = selectOption[0].rawoptions;
                } catch(e) {};


                //------ Ajustar la posicion del select (ul) y enlazar el option con la linea
                if( selectOption && selectOption.ajustar ){
                    trObject.options = $("ul", selectOption);
                    selectOption.ajustar();
                };

                trObject.setShortCuts = function(){
                    try {
                        $.each( trObject.rawoptions, function(i, option){
                            if( option.shortcut ){
                                shortcut.remove( option.shortcut );
                                shortcut.add( option.shortcut, function(e){
                                    if( agd.views.activeView == "data" ){
                                        agd.func.open( option.href );
                                    }
                                });
                            }
                        });
                    } catch(e) {};
                    return true;
                };


                agd.tables[ currentTable ].addData( linea.key, trObject );
                return trObject;
            },

            solicitar : function( sName, sValue, callback ){

                var callback = callback || function(){};
                agd.streaming.callback[ sName ] = callback;
                $.get("configurar/solicitud/nueva.php?t=" + sName + "&v=" + sValue, function(uidSolicitud){

                    var solicitud = {
                        "tipo" : "solicitud",
                        "uid" : uidSolicitud,
                        "name" : sName,
                        "value" : sValue,
                        "callback" : callback,
                        "eliminar" : function(closeCallback){
                            var closeCallback = closeCallback || function(){};
                            $.get("configurar/solicitud/eliminar.php?oid=" +uidSolicitud , function(data){
                                closeCallback(data, solicitud);
                            });
                        }
                    };

                    callback( solicitud );
                });

                uploadRequestTimeout = window.setTimeout(function(){
                    callback({});
                }, 120000);
            },
            registerCallback : function( sName, cl ){
                agd.callbacks[ sName ] = cl;
                return agd.callbacks[ sName ];
            },
            addToTable : function( rowSrc, data ){
                var table = $(rowSrc).closest("table")[0], index = rowSrc.rowIndex, newRow = table.insertRow(index+1), extraline = $("#extra-line", table)[0];
                if( extraline ){ $(extraline).remove(); }
                $(newRow).attr("id","extra-line");
                $(data).each(function(i,o){
                    $(document.createElement("td")).appendTo(newRow).html(o);
                });

                modalbox.func.resize();
            },
            minimizeUpload : function(){
                $("#cboxOverlay").toggle();
                $(modalbox.id).toggle();
                var progressDiv = $( document.createElement('div') );
                agd.uploadPreviewInterval = setInterval(function(){
                    if( progressBar = $("#uploadProgressBar")[0] ){
                        $( this ).html( $(progressBar).html() );
                    }
                },1000);

                var div = $( document.createElement('div') ).attr({
                    innerHTML : 'TEXTO DEMO',
                    id : 'uploadPreview'
                })
                .append( progressDiv )
                .appendTo(document.body);
                return false;
            },
            jGrowl : function( sName, sString, oOption ){
                if( !sString ){ return false; }
                var nameDiv = sName + "-growl";

                if( $("." + nameDiv).length ){
                    return false;
                } else {
                    var capa = $("#jGrowl"),
                        not = $(".jGrowl-notification .message", capa),
                        notif = $(".jGrowl-notification");

                    if( not.length ){
                        $(notif).trigger("jGrowl.close",{
                            callback : function(){
                                window.setTimeout(function(){
                                    agd.func.jGrowl( sName, sString, oOption );
                                },100);
                            }
                        });
                        return false;
                    }
                }

                var opt = {
                    beforeOpen : function( domNode ){
                        $(domNode).addClass(nameDiv);
                    },
                    beforeClose : function( domNode ){
                        $(domNode).removeClass(nameDiv);
                    }
                };
                if( oOption ){
                    for( optionName in oOption ){
                        opt[ optionName ] = oOption[ optionName ];
                    }
                };

                $.jGrowl( sString, opt );
            },
            queryCache : function( href, callback ){
                var callback = callback || function(){};
                if( current = agd.cache.get( href ) ){
                    callback( current );
                } else {
                    $.get( href , function( data ){
                        agd.cache.save( href, data );
                        callback( data );
                    });
                }
            },
            filter : function(e){
                if( !$(e.originalTarget).hasClass("checkbox-desplegable") ){

                    var selectedName = $(this).prop("name").split(";");
                    var fieldForFilter = $(this).prop("rel").split(";");

                    if( selectedName.length ){
                        var updater = {}, total = selectedName.length;
                        for( i in selectedName ){
                            if( selectedName[i] == "null" ){
                                updater[ fieldForFilter[i] ] = null;
                            } else {
                                if(i<= total-1) {
                                    updater[ fieldForFilter[i] ] = selectedName[i];
                                }
                            }
                        };
                        ahistory.updateValue( updater );
                    }

                }
            },

            //------- Cuando se hace click en una opcion de la lista
            lineoption : function( option ){
                try {
                    var href = $(option).prop("href"), target = $(option).prop("target");
                    if( href ){
                        var letters = href.split("");
                        //y es una direccion que va al hash, indica que solo tenemos que cambiarlo
                        if( letters[0] == "#" ){
                            location.href = href;
                        } else {
                            if( target ){
                                $(target).prop("src", href);
                            } else {
                                //abrimos el enlace
                                agd.func.open( href );
                            }
                        }
                    }
                } catch(e) {}
            },

            //-------- BUSCAR EN LA TABLA FILTRANDO POR UN CAMPO Y UN VALOR
            query : function( obj, callback ){
                if( obj.value ){
                    var url = "query.php?t=" + obj.table + "&v=" + obj.value + "&f=" + obj.field;
                    $.ajax({
                        type: "GET",
                        url : url,
                        success : function(data){ callback( agd.func.getJson(data) ); }
                    });
                }
            },

            validar : function(){

                //agd.func.sendInfo( "validarmultiple.php" );
            },
            anular : function(){

                //agd.func.sendInfo( "anularmultiple.php" );
            },



            sendInfo : function( options, callback ){
                var url = options.url,
                    src = options.src,
                    confirmacion = options.confirm,
                    queryString = agd.func.array2url( "selected", agd.func.selectedRows() ),
                    len = queryString.length,
                    confirmAll;

                if( len || ( src && ($(src).hasClass("continue") || $(src).hasClass("send-info") ) ) ) {
                    if( (confirmacion && confirm(confirmacion)) || !confirmacion ){
                        if( $(src).hasClass("iframe") ){ $(src).prop("target", "#async-frame"); }

                        if( src && $(src).prop("target") ){
                            if (!len && (confirmAll = $(src).attr('confirm-all'))) {
                                if (!confirm(confirmAll)) return false;
                            }


                            $( $(src).prop("target") ).prop("src", url + (url.indexOf('?')==-1?'?':'&') + queryString);
                        } else {
                            $.ajax({ type: "POST", url : url, data : queryString, success : function( data ){
                                if( src && $(src).hasClass("box-it") ){
                                    modalbox.func.open( {"html":data} );
                                } else {
                                    try {
                                        var resultado = agd.func.getJson(data);
                                    } catch(e) {
                                        if( data ){
                                            agd.func.jGrowl("multiple_info", data);
                                        } else {
                                            agd.func.jGrowl("multiple_info", "Error!!!");
                                        };
                                        return false;
                                    }
                                    if( callback ){
                                        callback( resultado );
                                    } else {
                                        agd.actionCallback(data);
                                    }
                                }
                            }});
                        }
                    }
                } else {
                    agd.func.jGrowl("sin_seleccionar", "Selecciona algun elemento..");
                }
            },

            //------ RECOGER LOS ELEMENTOS SELECCIONADOS
            selectedRows : function( callback, type ){
                var selected = new Array(), callback = callback || function(){};

                if( agd.tables.current == "buscar" ){ // en buscar seleccionamos de todo..
                    var rowCollection = agd.func.showSelectedItems(true), l = rowCollection.length;
                    while(l--){
                        var tr = rowCollection[l], key = tr.uid;
                        if( $(tr).find(".checkbox-colum input").attr("checked") ){
                            selected.push( key );
                        }
                    }
                } else {
                    for( key in agd.tables[ agd.tables.current ].data ){
                        var tr = agd.tables[ agd.tables.current ].data[ key ];
                        if( tr ){
                            if( $(tr).hasClass("tree") && tr.isTreeVisible ){
                                var lines =  $(tr.subtable).find("tr.selected-row"), len = lines.length;
                                while(len--){
                                    var subrow = lines[len], uid = subrow.uid;
                                    if( type && subrow.tipo != type ){ continue; }
                                    selected.push( uid );
                                    callback( uid, subrow );
                                }
                            }

                            if( type && tr.tipo != type ){ continue; }
                            if( $( tr ).find(".checkbox-colum input").attr("checked") ){
                                selected.push( key );
                                callback( key, agd.tables[ agd.tables.current ].data[ key ] );
                            }
                        }
                    };
                };
                return selected;
            },

            //------- CONVERTIR UN ARRAY EN UNA URL
            array2url : function( varName, arr, callback ){
                var i = 1,total = arr.length, queryString = "", callback = callback || function(){};
                for( i in arr ){
                    //fix para los IE, ya que en ultimo lugar de arr, manda la funcion y la concatena con los valores seleccionados,
                    //por eso el ultimo no le tenemos en cuenta y empezados i=1 en vez de i=0
                    if(i<= total-1) {
                        queryString += varName + "[]=" + arr[i] + "&";
                        callback(i, arr[i] );
                    }
                };
                return queryString.substring( 0, queryString.length );
            },

            open : function(url, callback, method, lock){
                /*
                    Abre una ventana en un modalbox
                        @param url String - La url que queremos abrir
                        @param callback Function - Funcion a ejecutar cuando termine
                        @param method String [ POST | GET ] - Metodo de la peticion AJAX
                */


                if( !url ){ return; }
                method = method || null;
                callback = callback || function(){};

                if (lock) {
                    var previousHandler = modalbox.settings.overlayClose;

                    modalbox.settings.overlayClose = function(){
                        window.modalconfirm = lock;
                        modalbox.settings.overlayClose = previousHandler;

                        return true;
                    };
                }

                var cnct = ( url.indexOf('?') == -1 ) ? "?" : "&";
                url = url + cnct + "type=modal";

                // Guardamos el historial
                agd.history.push({ url : url, timestamp : (new Date()).getTime()});

                // hide al exteded divs
                $("div.extended:visible").each(function (){
                    this.restore(); // call restore method
                });

                if (slowTimeout) clearTimeout(slowTimeout);
                slowTimeout = setTimeout(function(){
                    ($loading).show().find("div").html(agd.strings.still_working);
                },  10000);

                var callbackTimeOut  = function (timeOut, callback) {
                    clearTimeout(timeOut);
                    $loading.hide().find("div").html(agd.strings.cargando + "...");
                    callback();
                };

                // --- Lanzamos el modalbox
                modalbox.func.open({href: url, open:true, scrolling:false, returnFocus:false, method:method}, function() {callbackTimeOut(slowTimeout, callback)});
            },

            addInputAlert : function( obj, text ){
                $("div.inline-alert", $(obj).parent() ).remove();
                var h = $(obj).outerHeight()-2, width = (obj.size) ? "auto" : "70%",
                        multiple = $(obj).hasClass("multiple"),
                        alertMarginLeft = ( multiple ) ? "-4px" : 0,
                        alertWidth = ( multiple ) ? "16%" : "28%";
                $( obj ).addClass("fail line-block").css({"width":width, "vertical-align":"middle"});
                var spanAlert = $( document.createElement("div") )
                    .addClass("inline-force inline-alert")
                    .css({"width":alertWidth,"line-height":h+"px","margin-left":alertMarginLeft})
                    .height(h)
                    .html(text);

                if( multiple ){
                    $(obj).parent().find(".multiple:last").after( spanAlert );
                } else {
                    $(obj).after( spanAlert );
                }
            },

            removeInputAlert : function( obj ){
                $("div.inline-alert", $(obj).parent() ).remove();
                $( obj ).removeClass("fail").css("width","");
            },

            /* Use to load the pages on the backgorund*/
            asyncLoad : function( obj ){
                var ajaxSyncLoad = $.ajax({
                    url:'buscar.php',
                    type: 'GET',
                    data: {
                        q: ahistory.getValue("q"),
                        isAsync: true,
                        all: ahistory.getValue("all")
                    }
                    ,beforeSend: function(){
                        ajaxAsyncCallStatus = agd.constants.ajaxAsyncCallStatus.waiting;
                    }
                    ,error: function(){
                        if (typeof ajaxAsyncCallStatus!=='undefined')
                            ajaxAsyncCallStatus = undefined;
                    }
                    ,success: function(data){
                        response = agd.func.getJson(data);
                        ajaxAsyncCallStatus = agd.constants.ajaxAsyncCallStatus.loaded;
                        var elemPaginationLeft = $('.paginacion.leftElem');
                        var elemPaginationRight = $('.paginacion.rightElem');
                        elemPaginationRight.html('');
                        elemPaginationLeft.html('');
                        var realCurrentPaginationNumber = parseInt( ahistory.getValue("p"),10 );
                        if( isNaN( realCurrentPaginationNumber ) ){ realCurrentPaginationNumber = 0;}

                        var pag = realCurrentPaginationNumber + 1;
                        if( pag -1 > 0 ){
                            var prevPag = realCurrentPaginationNumber - 1;
                            $( document.createElement("a") )
                                .attr({"href":"#" + ahistory.updateValue({"p":  prevPag},location.href), "class":"prev-page"})
                                .html( "&laquo; " + agd.strings.pagina_anterior )
                                .appendTo( elemPaginationRight );
                        }
                        var strPaginationRight =  " " + agd.strings.pagina + " " + pag + " " +  agd.strings.de + " " + response.paginacion["total"]+" ";
                        elemPaginationRight.append(strPaginationRight);
                        if ( pag < response.paginacion["total"] ){
                            $( document.createElement("a") )
                            .attr({ "href": "#" + ahistory.updateValue({"p": realCurrentPaginationNumber+1 },location.href), "class":"next-page" })
                            .html( " " + agd.strings.pagina_siguiente + " &raquo;" )
                            .appendTo( elemPaginationRight );
                        }

                        var fromPage = realCurrentPaginationNumber * 10;
                        var toPage = (realCurrentPaginationNumber + 1)* 10;

                        var strPaginationLeft  = agd.strings.mostrando_del + " " + fromPage + " " + agd.strings.al + " " + toPage + " " + agd.strings.de + " " + response.paginacion.of;
                        elemPaginationLeft.append(strPaginationLeft);


                        if( response.paginacion["total"] > 1 ){
                            $span.append(" &nbsp; | &nbsp; " + agd.strings.pagina + " " );
                            $(document.createElement("input")).val(pag).attr({id:"paginacion-input",type:"text", size:3}).keypress(function(e){
                                if( e.keyCode == 13 ){
                                    /*checking if is stored in cache*/
                                    var val = $(this).val();
                                    if ( typeof ajaxAsyncCallStatus !== 'undefined' && ajaxAsyncCallStatus === agd.constants.ajaxAsyncCallStatus.waiting && val > response.paginacion["total"] ){
                                        /* While we are retieving data fom the server */
                                        alert(agd.strings.proceso_busqueda_pagina_curso);
                                        return false;
                                    }else if  ( (response.asyncTable == agd.constants.async.loaded && (val > response.paginacion["total"] || isNaN(val) || val < 1) ) || ( response.asyncTable == null && (val > response.paginacion["total"] || isNaN(val) || val < 1)) || ( response.asyncTable == agd.constants.async.empty && (isNaN(val) || val < 1)) ){
                                    /* We already have the total number of pages in the DOM and the page the user has requested does not exist*/
                                        alert(agd.strings.error_buscando_pagina);
                                        return false;
                                    }else{
                                        /* We call to the page,does not matter if we have the page loaded or not  */
                                        ahistory.updateValue({"p": (val-1) });
                                    }
                                }
                            }).appendTo($span);
                        }
                    }
                });

                action = location.href.split('?')[0].split('#')[1];
                agd.asynchronousCall.saveAjax(ajaxSyncLoad,ahistory.getValue("q"),action);
                return;
            }
        },



        inputs : {

            cif : function(e){
                var needCheckCif = $(this).hasClass('needcheck');
                if (needCheckCif && this.edited && e) {
                    agd.func.query( { table : "empresa", value : this.value, field : "cif"}, function( datos ){
                        if( typeof("datos") == undefined ){

                        } else {
                            var empresa = datos[0];
                            if( empresa ){
                                var parentID = ahistory.getValue("poid");
                                parentID = !isNaN(parentID) ? parentID : agd.empresa;
                                var query = [ "txt="+ encodeURIComponent(empresa.nombre), "oid="+ empresa.oid, "poid="+ parentID, "back="+ encodeURIComponent(agd.history[agd.history.length-1].url) ];
                                var url = "empresa/asignarexistente.php?"+ query.join("&");
                                agd.func.open( url );
                            }
                        }
                    });
                };

                return true;
            },
            dni : function(e){
                if(e){
                    agd.func.query( { table : "empleado", value : this.value, field : "dni"}, function( datos ){
                        //------ var datos = JSON.parse( response );
                        if( typeof(datos) == undefined ){
                        } else {
                            var empleado = datos[0];
                            if( empleado ){
                                var parentID = ahistory.getValue("poid");
                                parentID = !isNaN(parentID) ? parentID : agd.empresa;
                                var query = [ "txt="+ encodeURIComponent(empleado.nombre+" "+empleado.apellidos), "oid="+empleado.oid, "poid="+ parentID, "back="+ encodeURIComponent(agd.history[agd.history.length-1].url)  ];
                                var url = "empleado/asignarexistente.php?"+ query.join("&");
                                agd.func.open(url);
                            }
                        }
                    });
                };

                return true;
            },

            usuario : function(e){
                var query, _this;
                if (e) {

                    _this = this;
                    query = {
                        table: 'usuario',
                        value: this.value,
                        field: 'usuario'
                    };

                    agd.func.query(query, function(datos) {
                        if (typeof(datos) == undefined || !datos.length) {
                            agd.func.removeInputAlert(_this, "No Valido");
                        } else {
                            agd.func.addInputAlert(_this, "No Valido");
                        }
                    });
                };
                return true;
            },

            serie : function(e){
                if( this.edited && e ){
                    agd.func.query( { table : "maquina", value : this.value, field : "serie"}, function( datos ){
                        //------ var datos = JSON.parse( response );
                        if( typeof(datos) == undefined ){
                        } else {
                            var maquina = datos[0];
                            if( maquina ){
                                var url = "maquina/asignarexistente.php?txt="+ encodeURIComponent(maquina.nombre+" "+maquina.marca_modelo+" "+maquina.serie)+"&oid="+maquina.oid+"&poid="+ ahistory.getValue("poid");
                                agd.func.open(url);
                            }
                        }
                    });
                };

                return true;
            }

        },

        cache : {
            seturl : function( sName, oJson ){
                agd.cache.url[ sName ] = { time : ( new Date() ).getTime(), json : oJson };
            },
            save : function( sName, sValue ){
                sValue = $.trim(sValue);
                sName = $.trim(sName);
                if( sValue && sName ){
                    agd.cache.elements[ sName ] = sValue;
                }
            },
            get : function( sName ){
                return agd.cache.elements[ sName ];
            },
            clean : function(){
                agd.cache.elements = {}
            },
            elements : {},
            url : {}
        },





        streaming : {
            requests : {},
            callback : {}
        },
        asynchronousCall :{
            elementList : [],
            saveAjax : function(ajaxCallSearch,q,action){
                var element = "action-"+ action + "-backgroundAsync-";
                if (q){ element = element + q; }
                agd.asynchronousCall.elementList[element] = ajaxCallSearch;
            },
            checkAjax : function(){
                    action = location.href.split('?')[0].split('#')[1];
                    var sajaxCallSearch = "action-"+ action + "-backgroundAsync-";
                    q = ahistory.getValue("q");
                    if (q){ sajaxCallSearch = sajaxCallSearch + q; }
                    for(var i in agd.asynchronousCall.elementList){
                        if (i!=sajaxCallSearch){
                            auxElement = agd.asynchronousCall.elementList[i];
                            if (auxElement){
                                auxElement.abort();
                                auxElement = false;
                            }
                        }
                    }
            }
        },


        //------- indicamos fin de carga cuando se hace la primera query ajax y se procesa
        load : function(){
            if( !agd.loaded ){
                $("#start_style").remove();
                $("#start_script").remove();
                $("#load").remove();
                $("#cuerpo").css("display","");
                window.setTimeout(function(){
                    $(window).scroll();
                }, 120);
                agd.loaded = true;


                if( typeof(__openOnStart) != "undefined" ){
                    var startOpen = function(){
                        try {
                            agd.func.open( __openOnStart );
                        } catch(e){
                            setTimeout( startOpen, 100 );
                        }
                    };
                    startOpen();
                };

                if( window.__asistente ){
                    agd.create.asistente( window.__asistente );
                };
            }
        },

        loadUserData : function (callback) {
            callback = callback || function(){};
            var tz = new Date().getTimezoneOffset()/ 60;
            $.getJSON("userdata.php", {tz:tz}, function(data){
                if (data) {
                    if (data.live && !polling) {
                        require([agd.staticdomain + '/js/app/polling.min.js?' + __rversion], function (pollingHandler) {
                            polling = new pollingHandler('../agd/live.php');
                            polling.init();
                        });
                    }


                    if( data.type ) agd.userType = data.type;
                    if( data.user ) agd.user = data.user;
                    if( data.maxfile ) agd.usermaxfile = data.maxfile;
                    if( data.sati ) agd.sati = data.sati;
                    if( data.agent ) agd.agent = data.agent;
                    if( data.strings ) agd.strings = data.strings;
                    if( data.locale ) agd.locale = data.locale;
                    if( data.empresa ) agd.empresa = data.empresa;
                    //------ DEFINIMOS EL CODIGO NUMERICO UNICO (COSAS COMO EL PROGRESO DEL UPLOAD)
                    if( data.un ) agd.un = data.un;

                    if( data.routes ) agd.routes = data.routes;
                    if (data.gkey) agd.gkey = data.gkey;

                    if( !$.browser.opera ){
                        if (data.plugins) {
                            agd.plugins = true;
                            h.appendChild(create('link',{type:"text/css",rel:"stylesheet",id:"main-style",href:'../pluginstyle.css.php'}));
                        }
                        require(['/pluginscript.js.php'], callback);
                        return false;
                    } else {
                        callback();
                    }
                };
            });
        },

        //inicializara los valores de la página
        init : function(force){

            // --- IE Messages
            if ($.browser.msie && $.browser.version < 8 && !force) {
                $.get('iehelp.php', function(html){
                    $loadLayer.html(html).find('#continue').one('click', function(e){
                        $(this).html("...");
                        agd.init(true);
                        return false;
                    });
                });

                return;
            };

            agd.loadUserData(function(){
                //--------- INICIAMOS LA NAVEGACION VIA HASH
                ahistory.onChange = agd.navegar;
                ahistory.start();
            });


            $(".avisos-principal").on("click", "li", function(e){
                activeNotification = this;
            });


            $(document).on("click", "#buscador-clear", function () {
                $("#buscar").val('').focus();
            });

            document.body.focused = true;
            if( !$.browser.msie ){
                $(window).blur(function(){
                    if (polling) polling.setDelay(10 * 1000);
                    document.body.focused = false;
                }).focus(function(){
                    if (polling) polling.setDelay(4000);
                    document.body.focused = true;
                });
                if( !document.body.focused ){
                    $(window).trigger("blur");
                }
            };


            $(document).bind(modalbox.event.ready, function(){
                // Redimensiona el modalbox al redimensionar un textarea
                $(modalbox.body).on("mousedown", "textarea", function(){
                    this.defaultHeight = $(this).height();
                    $(this).one("mouseup", function(){
                        if( this.defaultHeight != $(this).height() ){
                            modalbox.func.resize();
                        }
                    });
                });
            });


            //------ EVENTOS POR DEFECTO
            agd.callbacks["default-error"] = function(XMLHttpRequest, textStatus, code){
                var loadLayer = document.getElementById("load");
                if( code != "abort" ){
                    if( textStatus != "success" && !XMLHttpRequest.responseText  ){
                        if( loadLayer ){
                            $("h1", loadLayer).html("Error al cargar la página, haz click <a href='/agd/'>aqui</a> para intentarlo de nuevo").css("color","red");
                        } else {
                            $.jGrowl( "Error al cargar la página solicitada" );
                        }
                    }

                    if( loadLayer ){
                        $("h1", loadLayer).html("No puedes acceder a esta informacion. Haz click <a href='/agd/'>aqui</a> para volver al inicio").css("color","red");
                    } else {
                        if( XMLHttpRequest.responseText ){
                            agd.func.jGrowl("error_acceso", "No tienes acceso a esta información");
                        }
                    };
                }
                $loading.hide();
                return false;
            };


            /** LIVE EVENTS **/
            $(".autocomplete-input").live('keyup',function(){
                var that = this, widthMax = $(".autocomplete-input").outerWidth(), rel = $(this).attr("rel");
                $(this).autocomplete({

                    //define callback to format results
                    source: function(req, add){
                        var suggestions = [],
                            url = "query.php?"+ $(that).attr("href") +"&ct=1&v=" + encodeURIComponent(req.term);

                        $.get( url, function(data) {
                            json = agd.func.getJson(data), ln = json.length;
                            while(ln--){
                                ob = json[ln];
                                suggestions.push(ob[rel]);
                            }
                            add( suggestions );
                        });
                        return true;
                    },
                    open: function(event, ui) {
                        $(that).autocomplete( "widget" ).width(widthMax).addClass('box-autocomplete');
                    },
                    minLength:2

                });
            });

            //-------- CLASE SHOWNAME - MOSTRAR EL ATRIBUTO NAME EN EL MENU DE NAVEGACION
            /*
            $(".showname").live('mouseover',function(){
                var sName = $( this ).attr('name');
                $("#head-text").html( sName );
                $(agd.elements.navegacion).hide();
            }).live('mouseout',function(){
                $("#head-text").empty();
                $(agd.elements.navegacion).show();
            }).live('click', function(){
                $("#head-text").empty();
                $(agd.elements.navegacion).hide();
            });*/

            $(".pay").live("click", function(){
                var $textNode = $(this).find("span > span"), text = $textNode.text();
                $textNode.html( agd.strings.cargando + "..." );
                $(this).attr("disabled", "true");
                var params = $(this).data("params"),
                    chek = $("input[name=terms]").attr("checked") ? 1 : 0,
                    url = $(this).data("url") +"?action=send&terms=" + chek;

                if (params){
                    url +=  "&" + params;
                }

                if (chek){
                    location.href = url;
                }else{
                    alert("Por favor, acepta los terminos y condiciones");
                    $(this).removeAttr("disabled");
                    $textNode.html(text);
                }


            });

            $("#show-comment-box").live("click", function(event){
                event.preventDefault();
            });

            $(".btn.cancel").live("click", function(){
                modalbox.func.close();
                return false;
            });

            $("input.enable-button").click(function(){
                var $target = $($(this).attr("target"));
                if( this.checked ){ $target.removeAttr("disabled"); }
                else { $target.attr("disabled", true); }
            });

            $("option.other").live("click", function(){
                var $select = $(this).closest("select");
                $select.wrap("<div />");

                $input = $( document.createElement("input") ).attr({type:"text",name:$select.attr("name") });

                var dom = $select.get(0), attrs = $select.get(0).attributes;
                for( i in attrs ){
                    if( dom.hasOwnProperty(i) ){
                        var attr = attrs[i];
                        $input.attr( attr.nodeName, attr.nodeValue );
                    };
                };

                $select.parent().html($input);
            });

            $(".refresh").live("click", function(){

                var $text = $(this).data("text");
                if ($text){
                    $(this).html($text);
                }

                agd.navegar();
                return false;
            });

            $(".next-step").live("click", function(){
                var value = $(this).attr("name"),
                $step = $(this).closest(".vertical-step"),
                $title = $step.find("h1"),
                titlehtml = $title.html(),
                stepNumber = ( $step.attr("step") ) ? parseInt($step.attr("step")) : 1,
                steps = $(".vertical-step"), ln = steps.length,
                vals = [], data = {};

                $img = $("<img src='" + agd.inlineLoadingImage +"' />").appendTo($title);

                if( this.tagName == "LI" ){
                    var aux = $(this).clone().removeClass("next-step");
                    $(this).parent().empty().append(aux);
                }
                if( this.tagName == "BUTTON" ){
                    $(this).remove();
                }

                if(ln>1){
                    for(i=1;i<ln;i++){
                        var stepVal = $(steps[i]).attr("value");
                        vals.push(stepVal);
                    }
                };

                // Ponemos el nuevo value en la lista de los valores seleccionados anteriormente
                vals.push(value);
                data = { value : vals, step : stepNumber };

                // Guardams el hidden para el envio final
                $(document.createElement("input")).attr({type:"hidden","name":"value[]",value:value}).appendTo($step);

                // Le quitamos la clase al elemento clickado
                $(this).parent().find(".next-step").removeClass("next-step");

                // Recopilamos los datos
                $.get( ahistory.curLocation, data, function(res){
                    $nextStep = $(res).attr({step:stepNumber+1,value:value});
                    $(steps[ ln-1 ]).after($nextStep);
                    agd.checkEvents($nextStep);

                    $title.html( titlehtml );
                });

                return false;
            });

            $(".changeprofile").live("click", function(){
                return agd.func.changeProfile($(this).attr("to"), $(this).attr('rel'));
            });

            $(".option-block").live("click", function(e){
                if( $(e.target.parentNode).hasClass("option-block") ){
                    $(this).toggleClass("open").find("div").toggle();
                    return false;
                }
            });


            $("input.update-input").live("keyup", function(){
                var $this = $(this), $target = $($this.prop("target"));
                $target.val( $this.val() );
            });


            $("select.update-input").live("change", function(){
                options=($(this).prop("options"));
                selected = $(options[this.selectedIndex]).attr("update");

                var $this = $(this).closest("select"),
                    $target = $($this.attr("target")),
                    val = ( $(this).attr("update") ) ? $(this).attr("update") : selected;

                $target.val(val);
            });



            $(".goto").live('click', function(){
                location.href = $(this).attr("href");
            });

            //------- CLASE SENDINPUT - ENVIAR POR AJAX EL VALOR DEL ELEMENTO DEFINIDO EN EL ATTR TARGET A LA URL DEL ATTR HREF
            $(".sendinput").live('click',function(e,input){
                var selector = $(this).attr("target"),
                    target = $(selector),
                    type = $(this).attr("type"),
                    href = $(this).attr("href"),
                    total = target.length-1;

                    if( type == "text" && !input ){ return false; }

                    target.each(function(i, input){
                        input = $(input);
                        var inputname = input.attr("name"),
                        inputvalue = input.val(),
                        conct = ( href.indexOf("?") == -1 )?"?":"&";

                        if( input.attr("type") == "checkbox" ){
                            inputvalue = ( input.attr("checked") ) ? "on" : "off";
                        }

                        href = href + conct + inputname + "=" + encodeURIComponent(inputvalue);

                        if( i == total ){
                            agd.func.open(href);
                        }
                    });
                return false;
            }).live('keypress', function(e){
                if( e.keyCode == 13 ){
                    $(this).trigger("click", true);
                    return false;
                }
            });


            //---- Romper con un enlace el modal-box
            $('.unbox-it').live("click", function(){
                modalbox.func.close();
            });


            $(".update").live("click", function(){
                var $this = $(this), target = $this.attr("target"), $target = $(target), rel = $this.attr("rel"), val = $this.attr("update"), update = {};
                update[ rel ] = val;
                $target.attr(update);
            });

            $(".remove").live("click", function(){
                var $this = $(this), target = $this.attr("target"), $target = $(target);
                $target.remove();
            });

            $(".move").live("click", function(){
                var $this = $(this), target = $this.attr("target");
                $this.clone(true).appendTo(target);
                $this.remove();
            });

            $(".toggle-param").live("click", function(){

                var href = $(this).attr("href");

                if( $.browser.msie && $.browser.version < 8 ){
                    var base = window.location.href.substring(0, window.location.href.lastIndexOf("#") + 1), base = base.substring(0, base.lastIndexOf("/") + 1);
                    href = href.replace(base, "");
                }
                var aux = href.split("="), param = aux[0], value = aux[1];

                if( ahistory.getValue(param) ){
                    ahistory.remove(param);
                } else {
                    var obj = {};
                    obj[ param ] = value;
                    ahistory.add(obj);
                };

                return false;
            });

            $('.slide-list').live("click", function(){
                var thiz = this,
                    $target = $($(thiz).attr("target")),
                    $parent = $target.closest("td"),
                    $rel = $($(thiz).attr("rel")),
                    uid = $(thiz).attr("href"),
                    name = $(thiz).attr("name")
                ;

                if( !$parent.data("init") ){
                    $parent.css("width", $rel.width() );
                    $rel.css({ "width":$rel.width(), "float":"left" });

                    $target.find("a").each(function(){
                        $(this).data("href", $(this).attr("href") );
                    });

                    $parent.data("init", true);
                }


                $target.find("a").each(function(){
                    $(this).attr("href", $(this).data("href").replace("%s", uid));
                });

                $btn = $(document.createElement("button")).addClass("btn")
                    .attr("title", agd.strings.atras )
                    .html('<span><span> <img src="'+ agd.staticdomain +'/img/famfam/arrow_undo.png" /> </span></span>').click(function(){
                        $parent.css("white-space", "nowrap");
                        $rel.css( { "margin-left" : "0px" }).show();
                        $target.css({"width":"0px"});
                        return false;
                    });


                $target.find(".field-list-title").remove();
                var text = " " + $.trim( $(thiz).closest("li").text() ),
                    $title = $( document.createElement("li") ).addClass("field-list-title").append($btn, text).prependTo( $target ),
                    w = $parent.outerWidth();

                $parent.css("white-space", "nowrap");
                $target.css({"width":w, "display":""});
                $rel.css("margin-left","-" + w).hide();

                return false;
            });

            //---- Enviar un enlace al modal-box
            $('.box-it').live("click", function(){

                var href = $(this).attr('href');

                if( this.tagName.toLowerCase() == "input" && $(this).attr("type").toLowerCase()  == "checkbox" ){
                    var cnct = ( href.indexOf('?') == -1 ) ? '?' : '&',
                        stat = ( this.checked ) ? '1' : '0';
                    href = href + cnct + "input=" + stat;
                }

                agd.func.open( href );
                return false;
            });


            $('.colum-it').live("click", function(){
                var href = $(this).attr('href');

                if( this.tagName.toLowerCase() == "input" && $(this).attr("type").toLowerCase()  == "checkbox" ){
                    var cnct = ( href.indexOf('?') == -1 ) ? '?' : '&',
                        stat = ( this.checked ) ? '1' : '0';
                    href = href + cnct + "input=" + stat;
                }
                mostrarCapaColumnaLateral(href,400);
                return false;
            });

            $(document.body).delegate(".a-extend", "click", function(ev){
                ev.preventDefault();
                var that = this,
                    href = $(this).attr("href"),
                    selector = $(this).attr("target"),
                    target = $( selector ),
                    targetParent = target.parent(),
                    width = $(this).width(),
                    offset = $(this).offset();



                var restore = function(e){
                    if( e && e.target ){
                        if (!that.parentNode) {
                            return;
                        }

                        if( $(e.target).closest(".extended").get(0) === target.get(0) || $(e.target).closest(".a-extend").get(0) === that ){
                            return true;
                        };
                    };
                    target.css({display:"none"});
                    $(that).removeClass("extended");
                    $( window ).unbind("resize", restore).bind("resize", restore).unbind("click", restore).bind("click", restore);
                };


                if( !target.length && href  ){
                    var rand = ( new Date() ).getTime();
                    $.get(href, function(div){
                        var extend = $(div)
                        .css({"display":"none"})
                        .attr("id", rand)
                        .appendTo( document.body );

                        //--- Relanzamos el evento..
                        $(that).attr("target", "#"+rand ).trigger("click");
                    });
                    return false;
                };

                if( !target.length ){ return false; }

                $(".extend-replace", target).click(function(e){
                    var cName = $(that).attr("name");
                    var data = $( document.createElement("input") ).attr({
                        "type" : "hidden",
                        "name" : cName,
                        "value" : $(this).attr("src"),
                        "id" : cName
                    });
                    $("#"+cName).remove();
                    $(that).empty().append( $(this).clone() ).append(data);
                });

                if( $(that).hasClass("extended") ){ restore(); return false; }


                domTarget = target.get(0);
                domTarget.restore = restore; // call from ouside
                domTarget.position = function(){
                    target.css({visibility:"hidden","min-width":width,"z-index":10000000000}).addClass("extended");
                    target[0]["extender"] = that;
                    var height = target.outerHeight(), top = offset.top - height;

                    if( ( offset.top - target.height() ) < 5 ){
                        top = offset.top + $(that).height();
                    };

                    //var parentRightPos = targetParent.offset().left + targetParent.outerWidth(), targetPos = offset.left + target.outerWidth();
                    if( $(that).hasClass("right") ){
                        var pos = $(window).width() - ( $(that).outerWidth() + offset.left );
                        target.css({display:"", visibility:"", position:"absolute", top: top, right: pos});
                    } else {
                        target.css({display:"", visibility:"", position:"absolute", top: top, left: offset.left});
                    }

                    $(that).addClass("extended").css({"z-index":11});


                    $( window ).unbind("resize", restore).bind("resize", restore).unbind("click", restore).bind("click", restore);
                };

                //target.width(target.width());
                domTarget.position();

                // Focalizar input si lo hay
                target.find("input[type=text]").focus();
            });


            $(".box-tab").live("click", function(){
                $(this).parent().find(".selected").removeClass("selected");
                $(this).addClass("selected");
                $("#tabs-content > div").css("display", "none");

                var relatedselector = $(this).attr("rel"), $visible = $(relatedselector);

                $visible.css("display", "");

                var $hidden = $visible.find("input[name=ctab]");
                if( !$hidden[0] ){
                    $hidden = $(document.createElement("input")).attr({"type":"hidden","name":"ctab"}).appendTo($visible);
                }

                if( relatedselector ) { $hidden.val( relatedselector.replace("#tab-","") ); }

                $(modalbox.body).find(".message").remove();
                modalbox.func.resize();
            });


            //---- Los inputs que marcan como seleccionadas las lineas que las contienen
            $(document.body).delegate('input.line-check', "click", function(e){
                if( e.currentTarget.type.toLowerCase() == "radio" ){
                    var table = $( e.currentTarget ).closest("table");
                    $("tr.selected-row", table).removeClass("selected-row");
                };
                $( this ).closest("tr").toggleClass("selected-row");
            });

            $("button.pulsar").live("click", function(){
                if( $(this).attr("rel") ){
                    var contexto = $( $(this).attr("rel") )[0];
                    if( contexto ){
                        $(".selected", contexto).not(this).removeClass("selected");
                    }
                }
                $(this).toggleClass("selected");
            });


            $(".slideToggle").live("click", function(){

                var target = $(this).data("target");
                if ($(target).is(':visible')) {
                     $(this).text($(this).data("uncompressedtext"));
                } else {
                    if (!$(target).is(':visible')) $(target).trigger('appears');
                     $(this).text($(this).data("compressedtext"));
                }

                if ($(this).data("after") == "delete") {
                    $(this).remove();
                }

                $(target).slideToggle();
            });

            $(".toggle" ).live("click", function(){

                var info = $(this).data('info');
                if (info == "show") {
                    $(this).removeClass("show-toggle");
                    $(this).addClass("hide-toggle");
                    $(this).data('info', "hide");
                } else if (info == "hide") {
                    $(this).removeClass("hide-toggle");
                    $(this).addClass("show-toggle");
                    $(this).data('info', "show");
                }

                var classHierarchy = $(this.parentNode.parentNode.parentNode.parentNode).data('ishierarchy');
                if (classHierarchy=='yes'){
                    if (!confirm(agd.strings.alert_desasignar_elementos_papelera))
                        return false;
                }

                var selector = $(this).attr("target"), fn = $(this).attr("rel"), target = $( selector );
                if( fn && target[fn] ){
                    target[fn]( function(){
                        modalbox.func.resize();
                    });
                } else {
                    target.toggle();
                    if( modalbox.exists() ){    modalbox.func.resize(); }
                };

                if(this.tagName=="INPUT"&&this.type=="checkbox"||this.tagName=="IMG"||this.tagName=="SPAN"){ return true; };
                return false;
            //------- prevenir la reasignacion del evento
            });


            $(".jGrowl").live("click", function(){
                agd.func.jGrowl( this.name, $(this).attr("rel") );
            });
            $("button.send").live("click", function(){
                $.data( this.form, "sender", this);
            });

            $(".detect-click").live("click", function(){
                if( !this.form ){ return false; }

                var sName = $(this).attr("name"), sVal =  $(this).attr("value");
                $input = $( document.createElement("input") ).attr({
                    type : "hidden",
                    name : sName,
                    value : sVal
                }).appendTo( this.form );

            });

            //-------- APLICAMOS LOS EVENTOS A LOS MODULOS
            $(".module").click(function(){
                location.href = $(this).attr("href");
            });


            $(".simple-select").live("click", function(e){
                var $this = $(this), $ul = $(this).find("ul"), ch = $ul.css("height");
                function close(){
                    try { $this.css("position","inherit");} catch(e){};
                    $ul.css({"height":"21px","position":"inherit"}).removeClass("open");
                }

                if( ch == "21px" ){
                    $this.css("position","relative");
                    $ul.css({"height":"100%","position":"relative"}).addClass("open");
                    window.setTimeout(function(){
                        $(document).one("click", close);
                    },100);
                } else {
                    close();
                }
            });



            $(".convert-editable").live("click", function(link){
                var target = $($(this).attr("target")),
                    href = $(this).attr("href"),
                    content = $(target).text(),
                    rel = $($(this).attr("rel")),
                    height = $(target).height(),
                    minHeight = $(this).data("minheight"),
                    maxHeight = $(this).data("maxheight"),
                    self = this
                ;

                if (target.children("textarea").length > 0) {
                    $(target).empty().append(content.replace(/\n/g, "<br />\n"));
                    return false;
                }

                if (height > maxHeight) {
                    height = maxHeight;
                } else if (height < minHeight) {
                    height = minHeight;
                }
                var map = {13: false, 16: false}

                var textarea = $(document.createElement("textarea"))
                    .addClass("converted-editable")
                    .html(content)
                    .height(height)
                    .keypress(function(e) {
                        if (e.keyCode == 13 && map[16] == false) {
                            return false;
                        }
                    })
                    .keyup(function(e){
                        if (e.keyCode in map) {
                            map[e.keyCode] = true;
                            if (map[13] && map[16]) {
                                return false;
                            }
                        }

                        var content = textarea.addClass("loading-editable").val();

                        if (rel.length) {
                            rel.html(agd.strings.editing_comment)
                                .attr("title", agd.strings.shift_plus_enter)
                                .tipsy();
                            $(self).hide();
                        }

                        if (e.keyCode == 13) {
                            if (rel.length) {
                                var $img = $(document.createElement("img")).attr("src", agd.inlineLoadingImage);
                                rel.html($img);
                            }

                            var xhr = $.get( href, { comentario : content }, function(isSaved){
                                $(target).empty().append(textarea.val().replace(/\n/g, '<br />'));
                                if (rel.length) {
                                    var contentType = xhr.getResponseHeader('Content-type');
                                    if (contentType.indexOf('application/json') !== -1) {
                                        agd.actionCallback(isSaved);
                                    } else if (parseInt(isSaved)) {
                                        rel.html(agd.strings.saved);
                                        window.setTimeout(function(){
                                            if( rel.length ){ rel.html(""); }
                                        }, 3000);
                                        agd.navegar( ahistory.curLocation, true );
                                    } else {
                                        rel.html(agd.strings.saved);
                                    }

                                }
                            });

                            return false;
                        } else {
                            map = {13: false, 16: false};
                        }

                    }).keydown(function(e) {
                        if (e.keyCode in map) {
                            map[e.keyCode] = true;
                        }
                    });
                ;


                $(target).empty().append(textarea);


                return false;
            });



            $("a.multiple").live("click", function(){
                var $this = $(this), $form = $(this).closest("form"), iname = $this.attr("name"), $parent = $this.closest("tr"), $newtr = $parent.clone(true), mode = $this.text();
                switch(mode){
                    case "+":
                        $parent.after($newtr);
                        $newtr.find("input[type='checkbox']").removeAttr("checked");
                        $td = $newtr.find('input[name="'+iname+'"]').val("").parent();
                        if( !$newtr.find(".rest").length ){
                            var html = '<a class="multiple rest" name="'+iname+'">-</a>';
                            $td.find("a.multiple").after(html);
                            $this.after(html);
                        }
                    break;
                    case "-":
                        $parent.remove();
                        $rest = $form.find('input[name="'+iname+'"]');
                        if( $rest.length == 1 ){
                            $rest.parent().find(".rest").remove();
                        };
                    break;
                }
                modalbox.func.resize();
                return false;
            });







            var $avisos = $("#menu-avisos");
            if( $avisos.length ){
                $avisos.find(".click-bar").click(function(e){
                    e.preventDefault();
                });
            };


            //--------- EL MENU DE AYUDA PARA EL BUSCADOR
            $("#ayuda-buscar").click(function(){
                try {
                    var drawSearchHelp = function(onlyIfExists){
                        var exists = $("#menu-ayuda-buscar")[0];
                        if( exists ){
                        var buscador = $("#buscador"), buscar = $("#buscar", buscador), boton = $("#boton-buscar", buscador), ayuda = $("#boton-ayuda", buscador), bbusquedas = $('#boton-busquedas',buscador),
                            width = buscar.outerWidth()+boton.outerWidth()+ayuda.outerWidth()-2 + bbusquedas.outerWidth()+30, height = buscador.height(),
                            offset = buscador.offset(), left = offset.left, top = offset.top+height;

                            $( exists ).css({
                                position:"absolute", right:0, top:top, width:"400px", display:""
                            });
                        } else {
                            if( !onlyIfExists ){
                                var menuAyuda = $( document.createElement("div") ).attr({
                                    id:"menu-ayuda-buscar"
                                }).css("display","none").appendTo( document.body );
                                agd.func.queryCache( "gettpl.php?tpl=ayudabuscar", function(data){
                                    $(menuAyuda).html(data);
                                    $(".cbox-close-title").click(function(){
                                        $(menuAyuda).remove();
                                    });
                                });
                                window.setTimeout(function(){
                                    drawSearchHelp();
                                },0);
                            }
                        }
                    };
                    $( window ).resize(function(){ drawSearchHelp(true); });
                    drawSearchHelp();
                } catch(e) { alert(e); }
                return false;
            });



            //-------- CUANDO EL MODALBOX CARGUE O CAMBIE DE TAMAÑO COMPROBAREMOS LOS EVENTOS
            $(document).bind(modalbox.event.load, function(e,o){
                $wrap = $(modalbox.body);

                // ARREGLAR PROBLEMILLA EN CHROME
                if( navigator.appVersion.indexOf("Chrome") !== -1 ){
                    $wrap.find("button.btn span span").each(function(i, ob){
                        var html = $(this).html();
                        $(this).html('&nbsp;'+html+'&nbsp;');
                    });
                }

                if( $.browser.webkit === true ){
                    $wrap.find("textarea").each(function(){
                        var area = this;
                        area.lastHeight = $(area).outerHeight(), area.lastWidth = $(area).css('width');
                        $(this).mouseup(function(){
                            if( area.lastWidth != $(area).css('width') ){
                                $(area).css('width', '99%');
                            };

                            if( area.lastHeight != $(area).outerHeight() ){
                                modalbox.func.resize(function(){
                                    $(area).focus();
                                });
                            }
                            $(area).focus();

                        });
                    });
                };

                if (!$wrap.find(".cbox-close-title").length) {
                    var div = $(document.createElement("div")).addClass("cbox-close-title").attr("title","Cerrar").click(function(){
                        modalbox.func.close();
                    });

                    $boxtitle = $wrap.find(".box-title");
                    if ($boxtitle.length) {
                        if ($.browser.msie) {
                            div.css("float","right");
                            var width = $wrap.width();

                            if (width > 960) {
                                $(modalbox.body +" > div").css("width","650px");
                                modalbox.func.resize();
                            }
                        }
                        $wrap.find(".box-title").prepend(div);
                    }
                };

                $(document).one("checkevents_complete", function(){
                    try {
                        if( $wrap.get(0).scrollHeight != $wrap.get(0).offsetHeight ){
                            modalbox.func.resize();
                        }
                    } catch(e){};
                });

                setTimeout(function(){ $wrap.find("input:first").focus() }, 100);
                var $reloader = $wrap.find("#reloader, .reloader");
                if ($reloader.length != 0) {
                    $reloader.removeAttr("id").removeClass("reloader");
                    agd.navegar();
                    return;
                }

                agd.checkEvents($wrap);

            });


            //-------- DEFINIMOS ELEMENTOS IMPORTANTES DE LA NAVEGACION PARA NO TENER QUE ACCEDER VARIAS VECES MEDIANTE EL SELECTOR
            agd.elements.navegacion = $("#informacion-navegacion")[0];
            agd.elements.asyncFrame = $("#async-frame")[0];
            agd.elements.main = $("#main")[0];
            agd.elements.menu = $("#main-menu ul")[0];




            if (window.UserVoice) {
                $(document).one("checkevents_complete", function(){
                    UserVoice.push(['showTab', 'classic_widget', {
                        mode: 'feedback',
                        primary_color: '#cc6d00',
                        link_color: '#007dbf',
                        forum_id: 207575,
                        tab_label: 'Mejora dokify',
                        tab_color: '#cc6d00',
                        tab_position: 'bottom-left',
                        tab_inverted: false
                    }]);
                });
            }



            //--------- ACCESOS MEDIANTE TECLAS

            shortcut.add("Alt+Right", function(e){
                var href = $("a.next-page", agd.views[ agd.views.activeView ].elements.bottomright).attr("href");
                if( href ){ location.href = href; };
                return false;
            });
            shortcut.add("Alt+Left", function(e){
                var href = $("a.prev-page", agd.views[ agd.views.activeView ].elements.bottomright).attr("href");
                if( href ){ location.href = href; };
                return false;
            });
            shortcut.add("Alt+Down", function(e){
                if( agd.views.activeView == "data" ){
                    var table = agd.views[ agd.views.activeView ].elements.table, current = table.current;
                    $(current).removeClass("current");
                    if( !(next = current.nextSibling) ){
                        next = table.rows[0];
                    }
                    $(next).addClass("current");
                    next.setShortCuts();
                    agd.views[ agd.views.activeView ].elements.table.current = next;
                };
                return false;
            });

            shortcut.add("Alt+Up", function(e){
                if( agd.views.activeView == "data" ){
                    var table = agd.views[ agd.views.activeView ].elements.table, current = table.current;
                    $(current).removeClass("current");
                    if( !(prev = current.previousSibling) ){
                        prev = table.rows[ table.rows.length-1 ];
                    }
                    $(prev).addClass("current");
                    prev.setShortCuts();
                    agd.views[ agd.views.activeView ].elements.table.current = prev;
                };
                return false;
            });

            //---- SHORTCUT PARA ACCEDER A LA FICHA DE REGISTRO DE LLAMADAS TELEFONICA
            shortcut.add("Ctrl+L",function() {
                require([agd.staticdomain + "/js/app/sidebar.min.js?"+ __rversion], function(){
                    mostrarCapaColumnaLateral('configurar/llamada/nuevo.php?type=sidebar&action=codigo',400);
                });
            });


            //---- SHORTCUT PARA ACCEDER A LA FICHA DE REGISTRO DE LLAMADAS TELEFONICA
            shortcut.add("Alt+q",function() {
                var iframe;

                iframe = $(document.createElement('iframe'));
                iframe.css({
                    'position': 'fixed',
                    'bottom': 0,
                    'right': 0,
                    'border': 0,
                    'height': '240px',
                    'width': '320px'
                });

                iframe.get(0).src = '/qr-reader/iframe.html';

                iframe.appendTo('body');
            });


            $(document).on('qr', function (e, qr) {
                var a = document.createElement('a'), parts;
                a.href = qr;

                if (a.host.indexOf('dokify') !== -1 || a.host.indexOf('192.168') !== -1) {
                    parts = a.pathname.substr(1).split('/');

                    // Si tenemos un id aqui..
                    if (parts[1] !== undefined && !isNaN(parts[1])) {
                        agd.func.open('ficha.php?src=qr&m=empleado&poid=' + parts[1]);
                    }
                }

            });

            $(document).bind('sidebar-open', function(){
                var $sidebar = $('#sidebar'), $companyUser = $sidebar.find("#form-line-empresa a");

                if ($companyUser){
                    var uid = $companyUser.data("uid");

                    if (uid) agd.func.changeProfile(uid, true);
                }

                agd.checkEvents($sidebar);
            });



            shortcut.add("Ctrl+E",function() {
                try {
                    var sendCapture = function(){
                        $("#credits").css({"position":"absolute"});
                        var c = new html2canvas( document.body, {
                            ready: function(renderer) {
                                $("#credits").css("position","fixed");
                                var base64 = renderer.canvas.toDataURL("image/png");
                                $.post("labs/canvas/share.php", "img="+ encodeURIComponent(base64), function(res){
                                    if( res == "1" ){ alert("Ok"); } else { alert("Error"); }
                                });
                            }
                        });
                    };
                    alert("Acepta para enviar");
                    var cv = create("script", { src : agd.staticdomain + "/js/canvas/html2canvas.min.js", onload : sendCapture});
                    document.body.appendChild(cv);
                } catch(e) {};
            });


            //---- SHORTCUT PARA SELECCIONAR TODO
            shortcut.add("Ctrl+A",function() {
                if( agd.views.activeView == "data" ){
                    try {
                        var $inputs = $("#table-container input[type='checkbox']"), now = $inputs.get(0).checked;
                        $inputs.attr("checked", !now).each(function(i, ch){
                            $(ch.row).toggleClass("selected-row");
                            if( i === ( $inputs.length-1 ) ){
                                var result = agd.func.showSelectedItems();

                                /*if( 0 && agd.tables.current == "buscar" ){
                                    if( !now ){
                                        $tr = $( document.createElement("tr") ).addClass("extra-line");
                                        $td = $( document.createElement("td") ).appendTo($tr).attr({ colspan : result.rows[0].childNodes.length }).html("Has seleccionado " + result.rows.length + " elementos. ");
                                        $a = $(document.createElement("a")).html("Seleccionar las " + result.count + " filas").appendTo($td).click(function(){
                                            $seleccion = $("#informacion-seleccion"), href = "#buscar.php?p=0&q=";
                                            $.getJSON( ahistory.curLocation + "&export=array&action=json", function(data){
                                                var links = {}, parts = [], total = [];
                                                $.each(data, function(i, line){
                                                    if( !links[line.tipo] ){ links[line.tipo] = []; }
                                                    links[line.tipo].push( line.uid_objeto );
                                                    total.push( line.uid_objeto );
                                                });


                                                for( tipo in links ){
                                                    if( typeof(links[tipo]) == "object" ){
                                                        parts.push("tipo:" + tipo + "#" + links[tipo].join(","));
                                                    }
                                                }

                                                $seleccion.attr("href", href + parts.join("+")).html( total.length + " elementos seleccionados");
                                                $td.html("Has seleccionado " + total.length + " elementos. ");
                                            });
                                        });

                                        $("#line-data tbody tr:first-child").before( $tr );
                                    } else {
                                        $("#line-data tbody tr:first-child").remove();
                                    }
                                }*/
                            }
                        });
                    } catch(e) {};
                }
            });

            //---- SHORTCUT PARA CREAR UN ELEMENTO NUEVO
            shortcut.add("Ctrl+N",function() {
                if( agd.views.activeView == "data" ){
                    $("#left-panel img[src$='boxadd.png']").parent().click();
                }
            });

            //----- SHORTCUT PARA CREAR UN ELEMENTO NUEVO
            shortcut.add("Ctrl+D",function(){
                if( agd.views.activeView == "data" ){
                    var href = "eliminar.php?confirmed=1&send=1", selected = agd.func.selectedRows(), count = selected.length,
                        queryString = agd.func.array2url( "uids", selected);

                    href += "&m=" + agd.tables.current +"&"+ queryString;

                    if( count && confirm("Continuar?") ){
                        $.getJSON(href, function(res){
                            agd.actionCallback(res);
                            ahistory.onChange(ahistory.curLocation, true);
                        });
                    }
                };
                return false;
            });

            shortcut.add("Ctrl+M",function(){
                $("#link-perfiles").click();
            });

            //---- HUEVOPASCUA!!!!
            shortcut.add("Ctrl+Shift+Alt+G",function(){ modalbox.func.open({html:'<audio src="'+agd.staticdomain+'/audio/gueba.ogg" loop controls autoplay="true"></audio>'}); });





            // Helper para algunos elementos que viene bien tener visibles siempre...
            $(window).scroll(function(){
                if( agd.tables.current ){

                    var $move = $(".keep-visible");

                    if( $move.length ){
                        ;var offset = $move.offset()
                            ,top = ( $move.data("top") ) ? $move.data("top") : ( offset.top || 0 )
                            ,scroll = $(window).scrollTop()
                            ,rest = scroll - top;
                        ;

                        if( !$move.data("top") ){
                            if( rest > -15 ){
                                $move.data("top", top);
                                $move.css({ width: $("#main").width(), left: 0, top: scroll + 'px', position:"absolute"});
                                if (agd.tables.current.indexOf("asignacion-") != -1) $move.addClass("float")
                            }
                        } else {
                            if( rest > -15 ){
                                $move.css({ top : scroll+ "px"});
                            } else {
                                $move.css({width:"",position:"",top:"",left:""}).removeClass("float");
                                $move.data("top", 0);
                            }
                        }
                    }
                }

                /*if( $.browser.msie && $.browser.version < 7 ){
                    $credits = $("#credits").css({"position": "absolute", "top": ($(window).height()+$(window).scrollTop()-30)  + "px", "left":"0px"});
                }*/
            });



            $(document.body).delegate("#left-panel", "dragover", function(e){
                if( e && e.target && e.target.src && e.target.src.indexOf("papelera.png") != -1 ){
                    $(e.target).attr("src", agd.staticdomain + "/img/48x48/iface/red_papelera.png");
                }
            }).delegate("#left-panel", "dragleave", function(e){
                if( e && e.target && e.target.src && e.target.src.indexOf("papelera.png") != -1 ){
                    $(e.target).attr("src", agd.staticdomain + "/img/48x48/iface/papelera.png");
                }
            }).delegate("#left-panel", "drop", function(e){
                try {
                    $(e.target).attr("src", agd.staticdomain + "/img/48x48/iface/papelera.png");
                    var src = e.originalEvent.dataTransfer.getData("text");
                    if( src == "#config-link" ){
                        var $link = $("#config-link").remove();
                        agd.func.open( agd.staticdomain + "/img/common/wtf.jpg" );
                    };
                    if( !isNaN(src) ){
                        var trashLink = "enviar" + $(e.target).parent().attr("href").split("&")[0] + "&poid=" + src;
                        agd.func.open(trashLink);
                    };
                } catch(e){}
            });

            $("#config-link").bind("dragstart", function(e){
                e.originalTarget.dataTransfer.setData('text', '#config-link');
            });



            var uploading = [];
            $(document.body).bind("dragover", function(e){
                var transfer = e.originalEvent.dataTransfer;

                if( transfer.files || ( transfer.types && transfer.types.contains && transfer.types.contains("Files") ) ){
                    var target = e.target, tr = $(target).closest("tr").get(0);

                    if( !window.dragging ){
                        window.dragging = true;
                    };

                    if( tr && tr.uid && $(tr).hasClass("drop-area") && !tr.over ){
                        tr.over = true;
                        var   $this = $(tr)
                            , curHeight = $this.outerHeight()
                            , uniqid = tr.uid
                            , collength = $("td", $this).length
                            , lineName = $("td.position-colum+td > a", $this).attr("title")
                            , $text = $(document.createElement("strong")).html(lineName)
                            , $tr = $( document.createElement("tr") ).addClass("file-drop")
                            , $td = $( document.createElement("td") ).attr("colspan", collength).css({"line-height": curHeight+"px","text-align":"center","padding":"0px"}).appendTo($tr)
                            , $div = $( document.createElement("div") ).addClass("line-drop").html( "Soltar aqui para cargar el documento " ).append($text).appendTo($td)
                        ;

                        $div.attr("name", lineName );
                        $div[0].row = tr;
                        $div.get(0).restore = function(e){
                            $tr.remove();
                            $( $div[0].row ).show();
                            tr.over = false;
                        };

                        var show = function(){
                            $div[0].row = this;
                            $("a.go-back", $div[0]).click(function(){
                                delete($div[0].row.progress);
                                $div[0].restore();
                            });
                            $tr.insertBefore(this);
                            $(this).hide();
                        };

                        $div[0].show = show;
                        show.call(tr);

                        tr.upload = $div.get(0);

                        $div.one("dragleave", $div.get(0).restore );
                    } else {
                        if( tr && $(tr).hasClass("file-drop") ){
                            return false;
                        }
                    };
                };

                return false;
            }).bind("drop", function(e){
                var transfer = e.originalEvent.dataTransfer;
                var uploadPath = agd.uploadPath;

                if( transfer && ( files = transfer.files ) && ( file = files[0] ) ){
                    var target = e.target,
                        $div = $(target).closest("div"),
                        //width = $div.width(),
                        progressWidth = 2200,
                        row = $div.get(0).row,
                        lineid = $div[0].row.uid,
                        params = "&o="+ ahistory.getValue("poid") + "&m="+ ahistory.getValue("m") + "&poid="+lineid,
                        date = (new Date())
                    ;

                    if( $div.hasClass("line-drop") && files[0] ){
                        // tratamos de extraer la fecha del propio fichero
                        var fecha = agd.func.extractDate( file.name );

                        if( !fecha || !fecha.getDate() ){
                            fecha = prompt("No encuentro una fecha valida. Por favor indica la fecha de Emision", date.getDate() + "/" + (date.getMonth()+1) + "/" + date.getFullYear());
                        } else {
                            fecha = fecha.getDate() + "/" + (fecha.getMonth()+1) + "/" + fecha.getFullYear();
                        };

                        if( !fecha || !$.trim(fecha) ){
                            if( $div.get(0).restore ){ $div.get(0).restore(); }
                            return false;
                        };

                        // Definimos la URL
                        var URL = "anexar.php?src=ajax&type=modal&send=1&fecha="+ fecha + params;

                        // Comenzamos el proceso de upload
                        row.progress = 0;

                        $div.unbind("dragleave", $div.get(0).restore );

                        // Funcion onProgress para dar feedback del estado de la carga del fichero
                        var onProgress = function(e) {
                            if( e.lengthComputable ){
                                var percentage = Math.round((e.loaded * 100) / e.total), restante = ( progressWidth * percentage ) / 100, position = progressWidth-restante;
                                $div.stop().addClass("progress").css("background-position", "-"+position+"px 0").html("<strong>Cargando " + percentage +"%</strong>");
                                row.progress = percentage;
                            }
                        };

                        var onSuccess = function(e) {

                            $.getJSON( URL, function(response){
                                if( response.error || !response ){
                                    $div.removeClass("uploading").addClass("error").html(response.error);
                                    window.setTimeout(function(){
                                        $div[0].restore();
                                    },5000);
                                }

                                if( response.upload ){
                                    $volver = $( document.createElement("a") ).addClass("go-back").html("Volver").click(function(){
                                        delete(row.progress);
                                        $(row).find(".docinfo").removeAttr("className").addClass("docinfo stat_1").html( agd.strings.anexado );
                                        $div[0].restore();
                                    });

                                    $div.removeClass("uploading").addClass("success").html("<strong>"+ $div.attr("name") +" cargado correctamente</strong>");
                                    $div.append( document.createTextNode(" | "+ fecha +" | ") );
                                    $( document.createElement("img") ).attr({src: agd.staticdomain + "/img/famfam/spellcheck.png"}).appendTo($div);
                                    $( document.createElement("a") ).html("Validar").addClass("box-it go-back").attr("href", "validar.php?src=ajax" + params)
                                        .click(function(){$volver.click();}).appendTo( $div );
                                    $div.append( document.createTextNode(" | ") );
                                    $( document.createElement("img") ).attr({src: agd.staticdomain + "/img/famfam/stop.png"}).appendTo($div);
                                    $( document.createElement("a") ).html("Anular").addClass("box-it go-back").attr("href", "anular.php?src=ajax" + params)
                                        .click(function(){$volver.click();}).appendTo( $div );
                                    $div.append( document.createTextNode(" | ") );
                                    $( document.createElement("img") ).attr({src: agd.staticdomain + "/img/famfam/arrow_undo.png"}).appendTo($div);
                                    $volver.appendTo( $div );
                                }
                            });
                        };


                        var xhr = file.upload(uploadPath, {
                            onprogress : onProgress,
                            onload : onProgress,
                            onsuccess : onSuccess,
                            onerror : function(e){
                                $div.removeClass("uploading").addClass("error").html("Ocurrio un error al anexar el fichero...");
                                window.setTimeout(function(){
                                    $div.get(0).restore();
                                },5000);
                            }
                        });

                        uploading.push(xhr);

                        $div.addClass("uploading").html("<strong><img src='"+ agd.inlineLoadingImage +"' /> Cargando...</strong>");
                    }
                }

                return false;
            });


            //-------- AVISAR AL SALIR SI SE ESTA EJECUTANDO ALGUNA ACCION
            window.onbeforeunload = function(){
                try {
                    if (window.modalconfirm && !$.browser.msie) {
                        return agd.strings.pregunta_cerrar_ventana;
                    }

                    $.each(uploading, function(i, xhr){
                        try{ xhr.abort(); } catch(e){};
                    });

                    $(agd.elements.asyncFrame).attr("src","");
                    var xhr = (window.ActiveXObject)?new ActiveXObject("Microsoft.XMLHTTP"):new XMLHttpRequest();
                    xhr.open("GET", "salir.php?mode=ajax", false);
                    xhr.send(null);
                } catch(e){}
            };


            //-------- INDICAMOS A EL FRAME PRINCIPAL QUE NUNCA SE QUEDE CON UN POST
            var onAsyncFrameLoad = function(){
                var that = this;
                $(that).attr("src","/blank.html");
                setTimeout(function(){ $(that).one('load',onAsyncFrameLoad);},1000);
            };
            $(agd.elements.asyncFrame).one("load", onAsyncFrameLoad);
            /* FIN INIT */
        }
    };


    if( "File" in window ){
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

            // compatibility with docx and xlsx
            var filetype;

            if (this.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                filetype = 'application/msword';
            } else if (this.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                filetype = 'application/vnd.ms-excel';
            } else {
                filetype = this.type;
            };

            // Set appropriate headers
            xhr.setRequestHeader("Content-Type", "multipart/form-data");
            xhr.setRequestHeader("X-File-Name", encodeURIComponent(this.name));
            xhr.setRequestHeader("X-File-Size", this.size);
            xhr.setRequestHeader("X-File-Type", filetype || "");

            xhr.onreadystatechange = function(){
                if(xhr.readyState==4){
                    if (xhr.status==200) {
                        options.onsuccess.apply(xhr);
                    } else {
                        onerror(JSON.stringify({responseText:xhr.responseText,status:xhr.status,statusText:xhr.statusText,readyState:xhr.readyState}), "js", 0);
                        options.onerror.apply(xhr, [xhr.responseText]);
                    }
                }
            };

            xhr.send(this);
            return xhr;
        };
    };

}(window));
