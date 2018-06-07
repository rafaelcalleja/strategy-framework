(function(w, undefined){
	var d=w.document;

	w.onerror=function(e,js,ln){
		var xhr=(w.ActiveXObject)?new ActiveXObject("Microsoft.XMLHTTP"):new XMLHttpRequest();
		xhr.open("GET","/error.php?error="+encodeURIComponent(e) + "%20["+ encodeURIComponent(navigator.userAgent) +"]&script="+encodeURIComponent(js) + "&hash=" + encodeURIComponent(location.hash),true); 
		xhr.send(null);
		return false;
	};

	w.create=function(element,attr){
		element=d.createElement(element);
		for(type in attr){ element[type]=attr[type]; };
		return element;
	};

	w.__resources=r=require.s.contexts._.config.baseUrl.replace('/js/', '');
	h=w.head=d.getElementsByTagName('head')[0];

	var dependences=[r+"/js/jquery/all.js?"+w.__rversion, r+"/js/app/ahistory.js?"+w.__rversion];
	w.JSON||dependences.push(r+'/js/app/json2.js');

	function load(){
		require(dependences,function(){
			require([r+"/js/app/funciones.min.js?"+w.__rversion], function(){
				agd.init();
				h.appendChild(create('link',{type:"text/css",rel:"stylesheet",media:"print",href: d.getElementById("main-style").getAttribute('href')}));
			});
		});
	};

	require([r+"/js/jquery/jquery-1.9.1.min.js?"+w.__rversion], function(){
		$(load);
	});
})(window);