/*! jQuery Address v${version} | (c) 2009, 2013 Rostislav Hristov | jquery.org/license */
(function(a){a.address=(function(){var d=function(aj){var ak=a.extend(a.Event(aj),(function(){var ao={},an=a.address.parameterNames();
for(var am=0,al=an.length;am<al;am++){ao[an[am]]=a.address.parameter(an[am]);}return{value:a.address.value(),path:a.address.path(),pathNames:a.address.pathNames(),parameterNames:an,parameters:ao,queryString:a.address.queryString()};
}).call(a.address));a(a.address).trigger(ak);return ak;},k=function(aj){return Array.prototype.slice.call(aj);},j=function(al,ak,aj){a().bind.apply(a(a.address),Array.prototype.slice.call(arguments));
return a.address;},I=function(ak,aj){a().unbind.apply(a(a.address),Array.prototype.slice.call(arguments));return a.address;},O=function(){return(ac.pushState&&C.state!==H);
},G=function(){return("/"+Y.pathname.replace(new RegExp(C.state),"")+Y.search+(U()?"#"+U():"")).replace(Q,"/");},U=function(){var aj=Y.href.indexOf("#");
return aj!=-1?Y.href.substr(aj+1):"";},y=function(){return O()?G():U();},b=function(){try{return top.document!==H&&top.document.title!==H?top:window;}catch(aj){return window;
}},l=function(){return"javascript";},ah=function(aj){aj=aj.toString();return(C.strict&&aj.substr(0,1)!="/"?"/":"")+aj;},T=function(aj,ak){return parseInt(aj.css(ak),10);
},ab=function(){if(!s){var ak=y(),aj=decodeURI(W)!=decodeURI(ak);if(aj){if(h&&q<7){Y.reload();}else{if(h&&!P&&C.history){n(D,50);}W=ak;Z(ad);}}}},Z=function(aj){n(v,10);
return d(F).isDefaultPrevented()||d(aj?g:ag).isDefaultPrevented();},v=function(){if(C.tracker!=="null"&&C.tracker!==E){var aj=a.isFunction(C.tracker)?C.tracker:R[C.tracker],ak=(Y.pathname+Y.search+(a.address&&!O()?a.address.value():"")).replace(/\/\//,"/").replace(/^\/$/,"");
if(a.isFunction(aj)){aj(ak);}else{if(a.isFunction(R.urchinTracker)){R.urchinTracker(ak);}else{if(R.pageTracker!==H&&a.isFunction(R.pageTracker._trackPageview)){R.pageTracker._trackPageview(ak);
}else{if(R._gaq!==H&&a.isFunction(R._gaq.push)){R._gaq.push(["_trackPageview",decodeURI(ak)]);}}}}}},D=function(){var aj=l()+":"+ad+";document.open();document.writeln('<html><head><title>"+af.title.replace(/\'/g,"\\'")+"</title><script>var "+x+' = "'+encodeURIComponent(y()).replace(/\'/g,"\\'")+(af.domain!=Y.hostname?'";document.domain="'+af.domain:"")+"\";<\/script></head></html>');document.close();";
if(q<7){e.src=aj;}else{e.contentWindow.location.replace(aj);}},ae=function(){if(i&&c!=-1){var aj,al,ak=i.substr(c+1).split("&");for(aj=0;aj<ak.length;aj++){al=ak[aj].split("=");
if(/^(autoUpdate|history|strict|wrap)$/.test(al[0])){C[al[0]]=(isNaN(al[1])?/^(true|yes)$/i.test(al[1]):(parseInt(al[1],10)!==0));}if(/^(state|tracker)$/.test(al[0])){C[al[0]]=al[1];
}}i=E;}W=y();},S=function(){if(!X){X=B;ae();if(C.wrap){var ak=a("body"),al=a("body > *").wrapAll('<div style="padding:'+(T(ak,"marginTop")+T(ak,"paddingTop"))+"px "+(T(ak,"marginRight")+T(ak,"paddingRight"))+"px "+(T(ak,"marginBottom")+T(ak,"paddingBottom"))+"px "+(T(ak,"marginLeft")+T(ak,"paddingLeft"))+'px;" />').parent().wrap('<div id="'+x+'" style="height:100%;overflow:auto;position:relative;'+(r&&!window.statusbar.visible?"resize:both;":"")+'" />');
a("html, body").css({height:"100%",margin:0,padding:0,overflow:"hidden"});if(r){a('<style type="text/css" />').appendTo("head").text("#"+x+"::-webkit-resizer { background-color: #fff; }");
}}if(h&&!P){var aj=af.getElementsByTagName("frameset")[0];e=af.createElement((aj?"":"i")+"frame");e.src=l()+":"+ad;if(aj){aj.insertAdjacentElement("beforeEnd",e);
aj[aj.cols?"cols":"rows"]+=",0";e.noResize=B;e.frameBorder=e.frameSpacing=0;}else{e.style.display="none";e.style.width=e.style.height=0;e.tabIndex=-1;af.body.insertAdjacentElement("afterBegin",e);
}n(function(){a(e).bind("load",function(){var am=e.contentWindow;W=am[x]!==H?am[x]:"";if(W!=y()){Z(ad);Y.hash=W;}});if(e.contentWindow[x]===H){D();}},50);
}n(function(){d("init");Z(ad);},1);if(!O()){if((h&&q>7)||(!h&&P)){if(R.addEventListener){R.addEventListener(V,ab,ad);}else{if(R.attachEvent){R.attachEvent("on"+V,ab);
}}}else{t(ab,50);}}if("state" in window.history){a(window).trigger("popstate");}}},p=function(){if(decodeURI(W)!=decodeURI(y())){W=y();Z(ad);}},o=function(){if(R.removeEventListener){R.removeEventListener(V,ab,ad);
}else{if(R.detachEvent){R.detachEvent("on"+V,ab);}}},M=function(ak){ak=ak.toLowerCase();var aj=/(chrome)[ \/]([\w.]+)/.exec(ak)||/(webkit)[ \/]([\w.]+)/.exec(ak)||/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ak)||/(msie) ([\w.]+)/.exec(ak)||ak.indexOf("compatible")<0&&/(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ak)||[];
return{browser:aj[1]||"",version:aj[2]||"0"};},L=function(){var ak={},aj=M(navigator.userAgent);if(aj.browser){ak[aj.browser]=true;ak.version=aj.version;
}if(ak.chrome){ak.webkit=true;}else{if(ak.webkit){ak.safari=true;}}return ak;},H,E=null,x="jQueryAddress",aa="string",V="hashchange",m="init",F="change",g="internalChange",ag="externalChange",B=true,ad=false,C={autoUpdate:B,history:B,strict:B,wrap:ad},A=L(),q=parseFloat(A.version),r=A.webkit||A.safari,h=!a.support.opacity,R=b(),af=R.document,ac=R.history,Y=R.location,t=setInterval,n=setTimeout,Q=/\/{2,9}/g,ai=navigator.userAgent,P="on"+V in R,e,K,i=a("script:last").attr("src"),c=i?i.indexOf("?"):-1,J=af.title,s=ad,X=ad,N=B,w=ad,z={},W=y();
if(h){q=parseFloat(ai.substr(ai.indexOf("MSIE")+4));if(af.documentMode&&af.documentMode!=q){q=af.documentMode!=8?7:8;}var u=af.onpropertychange;af.onpropertychange=function(){if(u){u.call(af);
}if(af.title!=J&&af.title.indexOf("#"+y())!=-1){af.title=J;}};}if(ac.navigationMode){ac.navigationMode="compatible";}if(document.readyState=="complete"){var f=setInterval(function(){if(a.address){S();
clearInterval(f);}},50);}else{ae();a(S);}a(window).bind("popstate",p).bind("unload",o);return{bind:function(ak,al,aj){return j.apply(this,k(arguments));
},unbind:function(ak,aj){return I.apply(this,k(arguments));},init:function(ak,aj){return j.apply(this,[m].concat(k(arguments)));},change:function(ak,aj){return j.apply(this,[F].concat(k(arguments)));
},internalChange:function(ak,aj){return j.apply(this,[g].concat(k(arguments)));},externalChange:function(ak,aj){return j.apply(this,[ag].concat(k(arguments)));
},baseURL:function(){var aj=Y.href;if(aj.indexOf("#")!=-1){aj=aj.substr(0,aj.indexOf("#"));}if(/\/$/.test(aj)){aj=aj.substr(0,aj.length-1);}return aj;},autoUpdate:function(aj){if(aj!==H){C.autoUpdate=aj;
return this;}return C.autoUpdate;},history:function(aj){if(aj!==H){C.history=aj;return this;}return C.history;},state:function(aj){if(aj!==H){C.state=aj;
var ak=G();if(C.state!==H){if(ac.pushState){if(ak.substr(0,3)=="/#/"){Y.replace(C.state.replace(/^\/$/,"")+ak.substr(2));}}else{if(ak!="/"&&ak.replace(/^\/#/,"")!=U()){n(function(){Y.replace(C.state.replace(/^\/$/,"")+"/#"+ak);
},1);}}}return this;}return C.state;},strict:function(aj){if(aj!==H){C.strict=aj;return this;}return C.strict;},tracker:function(aj){if(aj!==H){C.tracker=aj;
return this;}return C.tracker;},wrap:function(aj){if(aj!==H){C.wrap=aj;return this;}return C.wrap;},update:function(){w=B;this.value(W);w=ad;return this;
},title:function(aj){if(aj!==H){n(function(){J=af.title=aj;if(N&&e&&e.contentWindow&&e.contentWindow.document){e.contentWindow.document.title=aj;N=ad;}},50);
return this;}return af.title;},value:function(aj){if(aj!==H){aj=ah(aj);if(aj=="/"){aj="";}if(W==aj&&!w){return;}W=aj;if(C.autoUpdate||w){if(Z(B)){return this;
}if(O()){ac[C.history?"pushState":"replaceState"]({},"",C.state.replace(/\/$/,"")+(W===""?"/":W));}else{s=B;if(r){if(C.history){Y.hash="#"+W;}else{Y.replace("#"+W);
}}else{if(W!=y()){if(C.history){Y.hash="#"+W;}else{Y.replace("#"+W);}}}if((h&&!P)&&C.history){n(D,50);}if(r){n(function(){s=ad;},1);}else{s=ad;}}}return this;
}return ah(W);},path:function(ak){if(ak!==H){var aj=this.queryString(),al=this.hash();this.value(ak+(aj?"?"+aj:"")+(al?"#"+al:""));return this;}return ah(W).split("#")[0].split("?")[0];
},pathNames:function(){var ak=this.path(),aj=ak.replace(Q,"/").split("/");if(ak.substr(0,1)=="/"||ak.length===0){aj.splice(0,1);}if(ak.substr(ak.length-1,1)=="/"){aj.splice(aj.length-1,1);
}return aj;},queryString:function(ak){if(ak!==H){var al=this.hash();this.value(this.path()+(ak?"?"+ak:"")+(al?"#"+al:""));return this;}var aj=W.split("?");
return aj.slice(1,aj.length).join("?").split("#")[0];},parameter:function(ak,at,am){var aq,ao;if(at!==H){var ar=this.parameterNames();ao=[];at=at===H||at===E?"":at.toString();
for(aq=0;aq<ar.length;aq++){var an=ar[aq],au=this.parameter(an);if(typeof au==aa){au=[au];}if(an==ak){au=(at===E||at==="")?[]:(am?au.concat([at]):[at]);
}for(var ap=0;ap<au.length;ap++){ao.push(an+"="+au[ap]);}}if(a.inArray(ak,ar)==-1&&at!==E&&at!==""){ao.push(ak+"="+at);}this.queryString(ao.join("&"));
return this;}at=this.queryString();if(at){var aj=[];ao=at.split("&");for(aq=0;aq<ao.length;aq++){var al=ao[aq].split("=");if(al[0]==ak){aj.push(al.slice(1).join("="));
}}if(aj.length!==0){return aj.length!=1?aj:aj[0];}}},parameterNames:function(){var aj=this.queryString(),am=[];if(aj&&aj.indexOf("=")!=-1){var an=aj.split("&");
for(var al=0;al<an.length;al++){var ak=an[al].split("=")[0];if(a.inArray(ak,am)==-1){am.push(ak);}}}return am;},hash:function(ak){if(ak!==H){this.value(W.split("#")[0]+(ak?"#"+ak:""));
return this;}var aj=W.split("#");return aj.slice(1,aj.length).join("#");}};})();a.fn.address=function(b){if(!this.data("address")){this.on("click",function(f){if(f.shiftKey||f.ctrlKey||f.metaKey||f.which==2){return true;
}var d=f.currentTarget;if(a(d).is("a")){f.preventDefault();var c=b?b.call(d):/address:/.test(a(d).attr("rel"))?a(d).attr("rel").split("address:")[1].split(" ")[0]:a.address.state()!==undefined&&!/^\/?$/.test(a.address.state())?a(d).attr("href").replace(new RegExp("^(.*"+a.address.state()+"|\\.)"),""):a(d).attr("href").replace(/^(#\!?|\.)/,"");
a.address.value(c);}}).on("submit",function(g){var f=g.currentTarget;if(a(f).is("form")){g.preventDefault();var d=a(f).attr("action"),c=b?b.call(f):(d.indexOf("?")!=-1?d.replace(/&$/,""):d+"?")+a(f).serialize();
a.address.value(c);}}).data("address",true);}return this;};})(jQuery);