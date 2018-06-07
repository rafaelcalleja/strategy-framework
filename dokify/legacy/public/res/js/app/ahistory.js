/*####################################################
VISOR DE HISTORIAL QUE REEMPLAZA AL NAVEGADOR
####################################################*/
(function(window, undefined){
	window.ahistory = window.ajaxHistory = {
		curLocation : String,
		curPage : String,
		curParams : String,
		lastLocation : String,
		time : Number,
		marco :     null,
		observer : Object,
		onChange : function(){},
		check : function(){},
		start : function(){
			this.time = 250;
			if("onhashchange" in window && window.onhashchange){
				window.onhashchange = ahistory.nativeMethod;
				ahistory.nativeMethod();
			} else {
				if (navigator.userAgent.indexOf('MSIE') != -1){
					this.check = this.ie;
				} else {
					this.check = this.ff;
				}
				this.observe();
			}
		},
		nativeMethod : function(){
			var location = document.location.hash.replace("#","");
			ahistory.curLocation = location;
			ahistory.onChange( location );
		},
		getParams : function(url){
			url = url || this.curLocation;
			if( url.indexOf('?') == -1 ){ return false }
			var obj = {}, 
				param = url.substring( url.indexOf('?')+1, url.length ).split('&').sort(), 
				l = param.length;
			if( !l ){ return false; }
			while( l-- ){
				var aux = param[l].split('=');
				if( aux[0].indexOf('[]') == -1 ){
					obj[ aux[0] ] = aux[1];
				} else {
					val = aux[0].replace('[]','');
					if( !obj[val] ){ obj[val] = new Array }
					obj[val].push( aux[1] );
				}
			}
			return obj;
		},
		getEncodedURI : function(){
			var params = this.getParams(), pairs = [], encoded = this.getPage(), loop = 0;
			for( vari in params ){
				var paramval = params[vari];
				if( paramval ){
					if( typeof paramval == "string" ){
						pairs.push( vari + "=" + encodeURIComponent( paramval ) );				
					} else {
						pl = paramval.length;
						for(i=0;i<pl;i++){
							pairs.push( vari + "[]=" + encodeURIComponent( paramval[i] ) );			
						}
					}
				}
			}
			if( pairs.length ){ 
				encoded += "?" + pairs.join("&");
			}
			return encoded;
		},
		getPage : function(url){
			url = ( url ) ? url : this.curLocation;
			if( url.indexOf("?") == -1 ){
				return url;
			} else {
				return url.substring(0, url.indexOf("?") );
			}
		},
		observe : function(){
			this.observer = setInterval( this.check , this.time);
		},   
		stop : function(){
			clearInterval(this.observer);
		},
		queryString : function(){
			var params = this.getParams(), string = "", loop=0;
			for( param in params ){
				string += param + "=" + params[param] + "&";
			}
			string = string.substring(0, string.length-1);
			return string;
		},
		getValue: function( v ){
			var param = this.getParams();
			return param[ v ];
		},
		remove: function( v, rtr, from ){
			c = from || this.curLocation;
			if( c.indexOf('?') == -1 || c.indexOf(v) == -1 ){ return 0; }
			var param = v + "=" + this.getValue(v);
			if( c.indexOf( param + "&" ) != -1 ){ param += '&'; }
			c = c.replace( param, "");
			if( c.substring( c.length-1, c.length ) == '&' ){ c = c.substring(0,c.length-1); }
			if( c.substring( c.length-1, c.length ) == '?' ){ c = c.substring(0,c.length-1); }
			if( rtr ){ return c; }
			location.hash = c;
		},
		updateValue: function(vari, rtrn){
			
			if(typeof(vari) == "object"){
				var actual = this.curLocation;
				for(ob in vari){
					if( vari[ob] === null || !vari[ob] ){
						if( this.getValue(ob) ){
							actual = ahistory.remove( ob, true, actual );
						}
					} else {
						var a = this.getValue( ob.toString() );
						if( !a ){
							var put = {}; 
							put[ ob ] = vari[ob];
							actual = ahistory.add( put , actual);
						}
						else{
							var sa = ob.toString()+"="+a;
							var na = ob.toString()+"="+vari[ob].toString();
							actual = actual.replace(sa, na);
						}
					}
				}
				if( rtrn ){ return actual; }
				location.hash = actual;
			}
		},
		add: function(oValue, rtrn ){
			var act = ( rtrn ) ? rtrn : this.curLocation;
			var cncat = ( act.indexOf('?')!=-1 ) ? '&' : '?';
			var newHash = "";
			var i = 0;
			for(v in oValue){
				cVal = oValue[v] || "";
				if( i == 0 ){
					newHash += cncat+v+"="+cVal.toString();
				} else {
					newHash += '&'+v+"="+cVal.toString();
					i++;
				}
			}
			newHash = act + newHash;
			if( rtrn ){ return newHash;	}
			location.hash = newHash;
		},
		ff : function(){
			var that = ahistory;
			var newLocation = document.location.hash.replace("#","");

			if (newLocation != that.curLocation){
				that.lastLocation = that.curLocation
				that.curLocation = newLocation;
				that.curPage = ( that.curLocation.indexOf('?')!=-1 ) ?  that.curLocation.substring(0,that.curLocation.indexOf('?')) : that.curLocation;
				that.curParams = that.curLocation.substring( that.curLocation.indexOf('?')+1, that.curLocation.length ).split('&').sort().join('&');
				that.onChange(  that.curLocation );
			}
		},
		ie : function(){
			var that = ahistory;
			if( window.navigator.userAgent.indexOf("MSIE 6.") != -1 ){
				var ref = document.location.href.split("#");
				try{
					var chash = ref[1];
				} catch(e) {
					chash = "";
				}

				var newLocation = chash;
			} else {
				var newLocation = document.location.hash.replace("#","");
			}

			try {
				newLocation = newLocation.replace(/\&amp\;/g,"&");
			} catch(e) {}

			//Solo la primera vez
			if (!that.marco){
				that.makeFrame();
				that.marco.src = '/blank.html';
				var doc = that.marco.contentWindow.document;
				doc.open();doc.write('<html><body>'+ encodeURI(newLocation) +'</body></html>');doc.close();
			}
			//Estado en el iframe
			var estado = that.marco.contentWindow.document.body.innerHTML.replace(/\&amp\;/g,"&");
			
			try {
				that.curLocation = that.curLocation.replace(/\&amp\;/g,"&");
			} catch(e) {}

			if (newLocation != that.curLocation){
				try {
					that.curLocation = newLocation.replace(/\&amp\;/g,"&");
				} catch(e){ that.curLocation = ""; }

				try {
					that.curPage = ( that.curLocation.indexOf('?')!=-1 ) ?  that.curLocation.substring(0,that.curLocation.indexOf('?')) : that.curLocation;
					that.curParams = that.curLocation.substring( that.curLocation.indexOf('?')+1, that.curLocation.length ).split('&').sort().join('&');
				} catch(e) {};
				var doc = that.marco.contentWindow.document;
				doc.open();doc.write('<html><body>'+newLocation+'</body></html>');doc.close();
				that.onChange(  that.curLocation );
			}
			else {
				if (that.curLocation != estado){
					that.curLocation = estado;
					that.curPage = ( that.curLocation.indexOf('?')!=-1 ) ?  that.curLocation.substring(0,that.curLocation.indexOf('?')) : that.curLocation;
					that.curParams = that.curLocation.substring( that.curLocation.indexOf('?')+1, that.curLocation.length ).split('&').sort().join('&');
					location.hash = estado;
					that.onChange( that.curLocation );
				}
			}
		},
		set : function(url, go){
			var that = ahistory, hash = url.replace("#","");
			if(this.marco){
				var doc = this.marco.contentWindow.document;
				doc.open();doc.write('<html><body>'+hash+'</body></html>');doc.close();
			}
			that.curLocation = hash;
			location.href = url;
			if( go ){ ahistory.onChange(that.curLocation); }
		},
		makeFrame : function(){
			var frame = document.createElement('IFRAME');
			frame.style.display = "none";
			frame.name="app";
			document.body.appendChild(frame);
			this.marco = frame;
		}
	};
}(window));
