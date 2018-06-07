/*!
 * History API JavaScript Library v4.2.8
 *
 * Support: IE8+, FF3+, Opera 9+, Safari, Chrome and other
 *
 * Copyright 2011-2017, Dmitrii Pakhtinov ( spb.piksel@gmail.com )
 *
 * http://spb-piksel.ru/
 *
 * MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Update: 2017-03-01 12:07
 */
(function(a){if(typeof define==="function"&&define.amd){if(typeof requirejs!=="undefined"){var d=requirejs,c="[history"+(new Date()).getTime()+"]";
var b=d.onError;a.toString=function(){return c;};d.onError=function(e){if(e.message.indexOf(c)===-1){b.call(d,e);}};}define([],a);}if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=a();
}else{return a();}})(function(){var b=(typeof window==="object"?window:this)||{};if(!b.history||"emulate" in b.history){return b.history;}var j=b.document;
var x=j.documentElement;var v=b.Object;var q=b.JSON;var Z=b.location;var I=b.history;var F=I;var a=I.pushState;var y=I.replaceState;var u=O();var ac="state" in I;
var Q=v.defineProperty;var z=f({},"t")?{}:j.createElement("a");var h="";var p=b.addEventListener?"addEventListener":(h="on")&&"attachEvent";var V=b.removeEventListener?"removeEventListener":"detachEvent";
var o=b.dispatchEvent?"dispatchEvent":"fireEvent";var r=b[p];var d=b[V];var R=b[o];var Y={basepath:"/",redirect:0,type:"/",init:0};var B="__historyAPI__";
var E=j.createElement("a");var g=Z.href;var w="";var e=1;var M=false;var ab=0;var N={};var i={};var l=j.title;var c;var U={onhashchange:null,onpopstate:null};
var T=function(af,ae){var ad=b.history!==I;if(ad){b.history=I;}af.apply(I,ae);if(ad){b.history=F;}};var D={setup:function(ad,ae,af){Y.basepath=(""+(ad==null?Y.basepath:ad)).replace(/(?:^|\/)[^\/]*$/,"/");
Y.type=ae==null?Y.type:ae;Y.redirect=af==null?Y.redirect:!!af;},redirect:function(ae,ad){F.setup(ad,ae);ad=Y.basepath;if(b.top==b.self){var af=C(null,false,true)._relative;
var ag=Z.pathname+Z.search;if(u){ag=ag.replace(/([^\/])$/,"$1/");if(af!=ad&&(new RegExp("^"+ad+"$","i")).test(ag)){Z.replace(af);}}else{if(ag!=ad){ag=ag.replace(/([^\/])\?/,"$1/?");
if((new RegExp("^"+ad,"i")).test(ag)){Z.replace(ad+"#"+ag.replace(new RegExp("^"+ad,"i"),Y.type)+Z.hash);}}}}},pushState:function(af,ag,ad){var ae=j.title;
if(l!=null){j.title=l;}a&&T(a,arguments);X(af,ad);j.title=ae;l=ag;},replaceState:function(af,ag,ad){var ae=j.title;if(l!=null){j.title=l;}delete N[Z.href];
y&&T(y,arguments);X(af,ad,true);j.title=ae;l=ag;},location:{set:function(ad){if(ab===0){ab=1;}b.location=ad;},get:function(){if(ab===0){ab=1;}return z;
}},state:{get:function(){if(typeof N[Z.href]==="object"){return q.parse(q.stringify(N[Z.href]));}else{if(typeof N[Z.href]!=="undefined"){return N[Z.href];
}else{return null;}}}}};var A={assign:function(ad){if(!u&&(""+ad).indexOf("#")===0){X(null,ad);}else{Z.assign(ad);}},reload:function(ad){Z.reload(ad);},replace:function(ad){if(!u&&(""+ad).indexOf("#")===0){X(null,ad,true);
}else{Z.replace(ad);}},toString:function(){return this.href;},origin:{get:function(){if(c!==void 0){return c;}if(!Z.origin){return Z.protocol+"//"+Z.hostname+(Z.port?":"+Z.port:"");
}return Z.origin;},set:function(ad){c=ad;}},href:u?null:{get:function(){return C()._href;}},protocol:null,host:null,hostname:null,port:null,pathname:u?null:{get:function(){return C()._pathname;
}},search:u?null:{get:function(){return C()._search;}},hash:u?null:{set:function(ad){X(null,(""+ad).replace(/^(#|)/,"#"),false,g);},get:function(){return C()._hash;
}}};function J(){}function C(af,ak,am){var ap=/(?:([a-zA-Z0-9\-]+\:))?(?:\/\/(?:[^@]*@)?([^\/:\?#]+)(?::([0-9]+))?)?([^\?#]*)(?:(\?[^#]+)|\?)?(?:(#.*))?/;
if(af!=null&&af!==""&&!ak){var an=C(),ah=j.getElementsByTagName("base")[0];if(!am&&ah&&ah.getAttribute("href")){ah.href=ah.href;an=C(ah.href,null,true);
}var ai=an._pathname,ad=an._protocol;af=""+af;af=/^(?:\w+\:)?\/\//.test(af)?af.indexOf("/")===0?ad+af:af:ad+"//"+an._host+(af.indexOf("/")===0?af:af.indexOf("?")===0?ai+af:af.indexOf("#")===0?ai+an._search+af:ai.replace(/[^\/]+$/g,"")+af);
}else{af=ak?af:Z.href;if(!u||am){af=af.replace(/^[^#]*/,"")||"#";af=Z.protocol.replace(/:.*$|$/,":")+"//"+Z.host+Y.basepath+af.replace(new RegExp("^#[/]?(?:"+Y.type+")?"),"");
}}E.href=af;var ar=ap.exec(E.href);var ao=ar[2]+(ar[3]?":"+ar[3]:"");var ag=ar[4]||"/";var aq=ar[5]||"";var aj=ar[6]==="#"?"":(ar[6]||"");var ae=ag+aq+aj;
var al=ag.replace(new RegExp("^"+Y.basepath,"i"),Y.type)+aq;return{_href:ar[1]+"//"+ao+ae,_protocol:ar[1],_host:ao,_hostname:ar[2],_port:ar[3]||"",_pathname:ag,_search:aq,_hash:aj,_relative:ae,_nohash:al,_special:al+aj};
}function O(){var ad=b.navigator.userAgent;if((ad.indexOf("Android 2.")!==-1||(ad.indexOf("Android 4.0")!==-1))&&ad.indexOf("Mobile Safari")!==-1&&ad.indexOf("Chrome")===-1&&ad.indexOf("Windows Phone")===-1){return false;
}return !!a;}function t(){var ae;try{ae=b.sessionStorage;ae.setItem(B+"t","1");ae.removeItem(B+"t");}catch(ad){ae={getItem:function(ag){var af=j.cookie.split(ag+"=");
return af.length>1&&af.pop().split(";").shift()||"null";},setItem:function(af,ah){var ag={};if(ag[Z.href]=F.state){j.cookie=af+"="+q.stringify(ag);}}};
}try{N=q.parse(ae.getItem(B))||{};}catch(ad){N={};}r(h+"unload",function(){ae.setItem(B,q.stringify(N));},false);}function f(af,ad,ae,ak){var ai=0;if(!ae){ae={set:J};
ai=1;}var aj=!ae.set;var ao=!ae.get;var ah={configurable:true,set:function(){aj=1;},get:function(){ao=1;}};try{Q(af,ad,ah);af[ad]=af[ad];Q(af,ad,ae);}catch(ag){}if(!aj||!ao){if(af.__defineGetter__){af.__defineGetter__(ad,ah.get);
af.__defineSetter__(ad,ah.set);af[ad]=af[ad];ae.get&&af.__defineGetter__(ad,ae.get);ae.set&&af.__defineSetter__(ad,ae.set);}if(!aj||!ao){if(ai){return false;
}else{if(af===b){try{var al=af[ad];af[ad]=null;}catch(ag){}if("execScript" in b){b.execScript("Public "+ad,"VBScript");b.execScript("var "+ad+";","JavaScript");
}else{try{Q(af,ad,{value:J});}catch(ag){if(ad==="onpopstate"){r("popstate",ae=function(){d("popstate",ae,false);var ap=af.onpopstate;af.onpopstate=null;
setTimeout(function(){af.onpopstate=ap;},1);},false);e=0;}}}af[ad]=al;}else{try{try{var an=v.create(af);Q(v.getPrototypeOf(an)===af?an:af,ad,ae);for(var am in af){if(typeof af[am]==="function"){an[am]=af[am].bind(af);
}}try{ak.call(an,an,af);}catch(ag){}af=an;}catch(ag){Q(af.constructor.prototype,ad,ae);}}catch(ag){return false;}}}}}return af;}function G(ad,af,ae){ae=ae||{};
ad=ad===A?Z:ad;ae.set=(ae.set||function(ag){ad[af]=ag;});ae.get=(ae.get||function(){return ad[af];});return ae;}function aa(ae,af,ad){if(ae in i){i[ae].push(af);
}else{if(arguments.length>3){r(ae,af,ad,arguments[3]);}else{r(ae,af,ad);}}}function n(af,ah,ad){var ag=i[af];if(ag){for(var ae=ag.length;ae--;){if(ag[ae]===ah){ag.splice(ae,1);
break;}}}else{d(af,ah,ad);}}function s(ai,ae){var ag=(""+(typeof ai==="string"?ai:ai.type)).replace(/^on/,"");var aj=i[ag];if(aj){ae=typeof ai==="string"?ae:ai;
if(ae.target==null){for(var ah=["target","currentTarget","srcElement","type"];ai=ah.pop();){ae=f(ae,ai,{get:ai==="type"?function(){return ag;}:function(){return b;
}});}}if(e){((ag==="popstate"?b.onpopstate:b.onhashchange)||J).call(b,ae);}for(var af=0,ad=aj.length;af<ad;af++){aj[af].call(b,ae);}return true;}else{return R(ai,ae);
}}function K(){var ad=j.createEvent?j.createEvent("Event"):j.createEventObject();if(ad.initEvent){ad.initEvent("popstate",false,false);}else{ad.type="popstate";
}ad.state=F.state;s(ad);}function P(){if(M){M=false;K();}}function X(ag,ad,ae,ah){if(!u){if(ab===0){ab=2;}var af=C(ad,ab===2&&(""+ad).indexOf("#")!==-1);
if(af._relative!==C()._relative){g=ah;if(ae){Z.replace("#"+af._special);}else{Z.hash=af._special;}}}else{g=Z.href;}if(!ac&&ag){N[Z.href]=ag;}M=false;}function W(ag){var af=g;
g=Z.href;if(af){if(w!==Z.href){K();}ag=ag||b.event;var ae=C(af,true);var ad=C();if(!ag.oldURL){ag.oldURL=ae._href;ag.newURL=ad._href;}if(ae._hash!==ad._hash){s(ag);
}}}function S(ad){setTimeout(function(){r("popstate",function(ae){w=Z.href;if(!ac){ae=f(ae,"state",{get:function(){return F.state;}});}s(ae);},false);},0);
if(!u&&ad!==true&&"location" in F){H(z.hash);P();}}function L(ad){while(ad){if(ad.nodeName==="A"){return ad;}ad=ad.parentNode;}}function m(aj){var ag=aj||b.event;
var ai=L(ag.target||ag.srcElement);var ad="defaultPrevented" in ag?ag.defaultPrevented:ag.returnValue===false;if(ai&&ai.nodeName==="A"&&!ad){var ah=C();
var af=C(ai.getAttribute("href",2));var ae=ah._href.split("#").shift()===af._href.split("#").shift();if(ae&&af._hash){if(ah._hash!==af._hash){z.hash=af._hash;
}H(af._hash);if(ag.preventDefault){ag.preventDefault();}else{ag.returnValue=false;}}}}function H(af){var ae=j.getElementById(af=(af||"").replace(/^#/,""));
if(ae&&ae.id===af&&ae.nodeName==="A"){var ad=ae.getBoundingClientRect();b.scrollTo((x.scrollLeft||0),ad.top+(x.scrollTop||0)-(x.clientTop||0));}}function k(){var ae=j.getElementsByTagName("script");
var ai=(ae[ae.length-1]||{}).src||"";var ad=ai.indexOf("?")!==-1?ai.split("?").pop():"";ad.replace(/(\w+)(?:=([^&]*))?/g,function(ak,al,am){Y[al]=(am||"").replace(/^(0|false)$/,"");
});r(h+"hashchange",W,false);var ag=[A,z,U,b,D,F];if(ac){delete D.state;}for(var af=0;af<ag.length;af+=2){for(var aj in ag[af]){if(ag[af].hasOwnProperty(aj)){if(typeof ag[af][aj]!=="object"){ag[af+1][aj]=ag[af][aj];
}else{var ah=G(ag[af],aj,ag[af][aj]);if(!f(ag[af+1],aj,ah,function(al,ak){if(ak===F){b.history=F=ag[af+1]=al;}})){d(h+"hashchange",W,false);return false;
}if(ag[af+1]===b){i[aj]=i[aj.substr(2)]=[];}}}}}F.setup();if(Y.redirect){F.redirect();}if(Y.init){ab=1;}if(!ac&&q){t();}if(!u){j[p](h+"click",m,false);
}if(j.readyState==="complete"){S(true);}else{if(!u&&C()._relative!==Y.basepath){M=true;}r(h+"load",S,false);}return true;}if(!k()){return;}F.emulate=!u;
b[p]=aa;b[V]=n;b[o]=s;return F;});