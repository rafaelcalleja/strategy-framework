typeof JSON!="object"&&(JSON={}),function(){function f(e){return e<10?"0"+e:e;}function quote(e){return escapable.lastIndex=0,escapable.test(e)?'"'+e.replace(escapable,function(e){var t=meta[e];
return typeof t=="string"?t:"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+e+'"';}function str(e,t){var n,r,i,s,o=gap,u,a=t[e];a&&typeof a=="object"&&typeof a.toJSON=="function"&&(a=a.toJSON(e)),typeof rep=="function"&&(a=rep.call(t,e,a));
switch(typeof a){case"string":return quote(a);case"number":return isFinite(a)?String(a):"null";case"boolean":case"null":return String(a);case"object":if(!a){return"null";
}gap+=indent,u=[];if(Object.prototype.toString.apply(a)==="[object Array]"){s=a.length;for(n=0;n<s;n+=1){u[n]=str(n,a)||"null";}return i=u.length===0?"[]":gap?"[\n"+gap+u.join(",\n"+gap)+"\n"+o+"]":"["+u.join(",")+"]",gap=o,i;
}if(rep&&typeof rep=="object"){s=rep.length;for(n=0;n<s;n+=1){typeof rep[n]=="string"&&(r=rep[n],i=str(r,a),i&&u.push(quote(r)+(gap?": ":":")+i));}}else{for(r in a){Object.prototype.hasOwnProperty.call(a,r)&&(i=str(r,a),i&&u.push(quote(r)+(gap?": ":":")+i));
}}return i=u.length===0?"{}":gap?"{\n"+gap+u.join(",\n"+gap)+"\n"+o+"}":"{"+u.join(",")+"}",gap=o,i;}}typeof Date.prototype.toJSON!="function"&&(Date.prototype.toJSON=function(e){return isFinite(this.valueOf())?this.getUTCFullYear()+"-"+f(this.getUTCMonth()+1)+"-"+f(this.getUTCDate())+"T"+f(this.getUTCHours())+":"+f(this.getUTCMinutes())+":"+f(this.getUTCSeconds())+"Z":null;
},String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(e){return this.valueOf();});var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={"\b":"\\b","	":"\\t","\n":"\\n","\f":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},rep;
typeof JSON.stringify!="function"&&(JSON.stringify=function(e,t,n){var r;gap="",indent="";if(typeof n=="number"){for(r=0;r<n;r+=1){indent+=" ";}}else{typeof n=="string"&&(indent=n);
}rep=t;if(!t||typeof t=="function"||typeof t=="object"&&typeof t.length=="number"){return str("",{"":e});}throw new Error("JSON.stringify");}),typeof JSON.parse!="function"&&(JSON.parse=function(text,reviver){function walk(e,t){var n,r,i=e[t];
if(i&&typeof i=="object"){for(n in i){Object.prototype.hasOwnProperty.call(i,n)&&(r=walk(i,n),r!==undefined?i[n]=r:delete i[n]);}}return reviver.call(e,t,i);
}var j;text=String(text),cx.lastIndex=0,cx.test(text)&&(text=text.replace(cx,function(e){return"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4);}));
if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,""))){return j=eval("("+text+")"),typeof reviver=="function"?walk({"":j},""):j;
}throw new SyntaxError("JSON.parse");});}(),function(c,a){var d=c.History=c.History||{},b=c.jQuery;if(typeof d.Adapter!="undefined"){throw new Error("History.js Adapter has already been loaded...");
}d.Adapter={bind:function(g,f,h){b(g).bind(f,h);},trigger:function(g,f,h){b(g).trigger(f,h);},extractEventData:function(h,j,g){var f=j&&j.originalEvent&&j.originalEvent[h]||g&&g[h]||a;
return f;},onDomLoad:function(f){b(f);}},typeof d.init!="undefined"&&d.init();}(window),function(f,b){var h=f.document,d=f.setTimeout||d,a=f.clearTimeout||a,c=f.setInterval||c,g=f.History=f.History||{};
if(typeof g.initHtml4!="undefined"){throw new Error("History.js HTML4 Support has already been loaded...");}g.initHtml4=function(){if(typeof g.initHtml4.initialized!="undefined"){return !1;
}g.initHtml4.initialized=!0,g.enabled=!0,g.savedHashes=[],g.isLastHash=function(j){var i=g.getHashByIndex(),k;return k=j===i,k;},g.isHashEqual=function(j,i){return j=encodeURIComponent(j).replace(/%25/g,"%"),i=encodeURIComponent(i).replace(/%25/g,"%"),j===i;
},g.saveHash=function(i){return g.isLastHash(i)?!1:(g.savedHashes.push(i),!0);},g.getHashByIndex=function(j){var i=null;return typeof j=="undefined"?i=g.savedHashes[g.savedHashes.length-1]:j<0?i=g.savedHashes[g.savedHashes.length+j]:i=g.savedHashes[j],i;
},g.discardedHashes={},g.discardedStates={},g.discardState=function(m,k,o){var l=g.getHashByState(m),j;return j={discardedState:m,backState:o,forwardState:k},g.discardedStates[l]=j,!0;
},g.discardHash=function(k,i,l){var j={discardedHash:k,backState:l,forwardState:i};return g.discardedHashes[k]=j,!0;},g.discardedState=function(j){var i=g.getHashByState(j),k;
return k=g.discardedStates[i]||!1,k;},g.discardedHash=function(j){var i=g.discardedHashes[j]||!1;return i;},g.recycleState=function(j){var i=g.getHashByState(j);
return g.discardedState(j)&&delete g.discardedStates[i],!0;},g.emulated.hashChange&&(g.hashChangeInit=function(){g.checkerFunction=null;var l="",m,k,j,e,n=Boolean(g.getHash());
return g.isInternetExplorer()?(m="historyjs-iframe",k=h.createElement("iframe"),k.setAttribute("id",m),k.setAttribute("src","#"),k.style.display="none",h.body.appendChild(k),k.contentWindow.document.open(),k.contentWindow.document.close(),j="",e=!1,g.checkerFunction=function(){if(e){return !1;
}e=!0;var o=g.getHash(),i=g.getHash(k.contentWindow.document);return o!==l?(l=o,i!==o&&(j=i=o,k.contentWindow.document.open(),k.contentWindow.document.close(),k.contentWindow.document.location.hash=g.escapeHash(o)),g.Adapter.trigger(f,"hashchange")):i!==j&&(j=i,n&&i===""?g.back():g.setHash(i,!1)),e=!1,!0;
}):g.checkerFunction=function(){var i=g.getHash()||"";return i!==l&&(l=i,g.Adapter.trigger(f,"hashchange")),!0;},g.intervalList.push(c(g.checkerFunction,g.options.hashChangeInterval)),!0;
},g.Adapter.onDomLoad(g.hashChangeInit)),g.emulated.pushState&&(g.onHashChange=function(l){var p=l&&l.newURL||g.getLocationHref(),o=g.getHashByUrl(p),k=null,m=null,j=null,e;
return g.isLastHash(o)?(g.busy(!1),!1):(g.doubleCheckComplete(),g.saveHash(o),o&&g.isTraditionalAnchor(o)?(g.Adapter.trigger(f,"anchorchange"),g.busy(!1),!1):(k=g.extractState(g.getFullUrl(o||g.getLocationHref()),!0),g.isLastSavedState(k)?(g.busy(!1),!1):(m=g.getHashByState(k),e=g.discardedState(k),e?(g.getHashByIndex(-2)===g.getHashByState(e.forwardState)?g.back(!1):g.forward(!1),!1):(g.pushState(k.data,k.title,encodeURI(k.url),!1),!0))));
},g.Adapter.bind(f,"hashchange",g.onHashChange),g.pushState=function(w,j,e,m){e=encodeURI(e).replace(/%25/g,"%");if(g.getHashByUrl(e)){throw new Error("History.js does not support states with fragment-identifiers (hashes/anchors).");
}if(m!==!1&&g.busy()){return g.pushQueue({scope:g,callback:g.pushState,args:arguments,queue:m}),!1;}g.busy(!0);var x=g.createStateObject(w,j,e),v=g.getHashByState(x),q=g.getState(!1),o=g.getHashByState(q),k=g.getHash(),p=g.expectedStateId==x.id;
return g.storeState(x),g.expectedStateId=x.id,g.recycleState(x),g.setTitle(x),v===o?(g.busy(!1),!1):(g.saveState(x),p||g.Adapter.trigger(f,"statechange"),!g.isHashEqual(v,k)&&!g.isHashEqual(v,g.getShortUrl(g.getLocationHref()))&&g.setHash(v,!1),g.busy(!1),!0);
},g.replaceState=function(v,j,e,m){e=encodeURI(e).replace(/%25/g,"%");if(g.getHashByUrl(e)){throw new Error("History.js does not support states with fragment-identifiers (hashes/anchors).");
}if(m!==!1&&g.busy()){return g.pushQueue({scope:g,callback:g.replaceState,args:arguments,queue:m}),!1;}g.busy(!0);var w=g.createStateObject(v,j,e),q=g.getHashByState(w),p=g.getState(!1),o=g.getHashByState(p),k=g.getStateByIndex(-2);
return g.discardState(p,w,k),q===o?(g.storeState(w),g.expectedStateId=w.id,g.recycleState(w),g.setTitle(w),g.saveState(w),g.Adapter.trigger(f,"statechange"),g.busy(!1)):g.pushState(w.data,w.title,w.url,!1),!0;
}),g.emulated.pushState&&g.getHash()&&!g.emulated.hashChange&&g.Adapter.onDomLoad(function(){g.Adapter.trigger(f,"hashchange");});},typeof g.init!="undefined"&&g.init();
}(window),function(x,C){var k=x.console||C,b=x.document,q=x.navigator,D=!1,j=x.setTimeout,B=x.clearTimeout,A=x.setInterval,w=x.clearInterval,m=x.JSON,z=x.alert,v=x.History=x.History||{},g=x.history;
try{D=x.sessionStorage,D.setItem("TEST","1"),D.removeItem("TEST");}catch(y){D=!1;}m.stringify=m.stringify||m.encode,m.parse=m.parse||m.decode;if(typeof v.init!="undefined"){throw new Error("History.js Core has already been loaded...");
}v.init=function(a){return typeof v.Adapter=="undefined"?!1:(typeof v.initCore!="undefined"&&v.initCore(),typeof v.initHtml4!="undefined"&&v.initHtml4(),!0);
},v.initCore=function(e){if(typeof v.initCore.initialized!="undefined"){return !1;}v.initCore.initialized=!0,v.options=v.options||{},v.options.hashChangeInterval=v.options.hashChangeInterval||100,v.options.safariPollInterval=v.options.safariPollInterval||500,v.options.doubleCheckInterval=v.options.doubleCheckInterval||500,v.options.disableSuid=v.options.disableSuid||!1,v.options.storeInterval=v.options.storeInterval||1000,v.options.busyDelay=v.options.busyDelay||250,v.options.debug=v.options.debug||!1,v.options.initialTitle=v.options.initialTitle||b.title,v.options.html4Mode=v.options.html4Mode||!1,v.options.delayInit=v.options.delayInit||!1,v.intervalList=[],v.clearAllIntervals=function(){var f,d=v.intervalList;
if(typeof d!="undefined"&&d!==null){for(f=0;f<d.length;f++){w(d[f]);}v.intervalList=null;}},v.debug=function(){(v.options.debug||!1)&&v.log.apply(v,arguments);
},v.log=function(){var E=typeof k!="undefined"&&typeof k.log!="undefined"&&typeof k.log.apply!="undefined",n=b.getElementById("log"),l,p,F,h,d;E?(h=Array.prototype.slice.call(arguments),l=h.shift(),typeof k.debug!="undefined"?k.debug.apply(k,[l,h]):k.log.apply(k,[l,h])):l="\n"+arguments[0]+"\n";
for(p=1,F=arguments.length;p<F;++p){d=arguments[p];if(typeof d=="object"&&typeof m!="undefined"){try{d=m.stringify(d);}catch(r){}}l+="\n"+d+"\n";}return n?(n.value+=l+"\n-----\n",n.scrollTop=n.scrollHeight-n.clientHeight):E||z(l),!0;
},v.getInternetExplorerMajorVersion=function(){var d=v.getInternetExplorerMajorVersion.cached=typeof v.getInternetExplorerMajorVersion.cached!="undefined"?v.getInternetExplorerMajorVersion.cached:function(){var h=3,f=b.createElement("div"),i=f.getElementsByTagName("i");
while((f.innerHTML="<!--[if gt IE "+ ++h+"]><i></i><![endif]-->")&&i[0]){}return h>4?h:!1;}();return d;},v.isInternetExplorer=function(){var d=v.isInternetExplorer.cached=typeof v.isInternetExplorer.cached!="undefined"?v.isInternetExplorer.cached:Boolean(v.getInternetExplorerMajorVersion());
return d;},v.options.html4Mode?v.emulated={pushState:!0,hashChange:!0}:v.emulated={pushState:!Boolean(x.history&&x.history.pushState&&x.history.replaceState&&!/ Mobile\/([1-7][a-z]|(8([abcde]|f(1[0-8]))))/i.test(q.userAgent)&&!/AppleWebKit\/5([0-2]|3[0-2])/i.test(q.userAgent)),hashChange:Boolean(!("onhashchange" in x||"onhashchange" in b)||v.isInternetExplorer()&&v.getInternetExplorerMajorVersion()<8)},v.enabled=!v.emulated.pushState,v.bugs={setHash:Boolean(!v.emulated.pushState&&q.vendor==="Apple Computer, Inc."&&/AppleWebKit\/5([0-2]|3[0-3])/.test(q.userAgent)),safariPoll:Boolean(!v.emulated.pushState&&q.vendor==="Apple Computer, Inc."&&/AppleWebKit\/5([0-2]|3[0-3])/.test(q.userAgent)),ieDoubleCheck:Boolean(v.isInternetExplorer()&&v.getInternetExplorerMajorVersion()<8),hashEscape:Boolean(v.isInternetExplorer()&&v.getInternetExplorerMajorVersion()<7)},v.isEmptyObject=function(f){for(var d in f){if(f.hasOwnProperty(d)){return !1;
}}return !0;},v.cloneObject=function(f){var d,h;return f?(d=m.stringify(f),h=m.parse(d)):h={},h;},v.getRootUrl=function(){var d=b.location.protocol+"//"+(b.location.hostname||b.location.host);
if(b.location.port||!1){d+=":"+b.location.port;}return d+="/",d;},v.getBaseHref=function(){var f=b.getElementsByTagName("base"),d=null,h="";return f.length===1&&(d=f[0],h=d.href.replace(/[^\/]+$/,"")),h=h.replace(/\/+$/,""),h&&(h+="/"),h;
},v.getBaseUrl=function(){var d=v.getBaseHref()||v.getBasePageUrl()||v.getRootUrl();return d;},v.getPageUrl=function(){var f=v.getState(!1,!1),d=(f||{}).url||v.getLocationHref(),h;
return h=d.replace(/\/+$/,"").replace(/[^\/]+$/,function(l,i,o){return/\./.test(l)?l:l+"/";}),h;},v.getBasePageUrl=function(){var d=v.getLocationHref().replace(/[#\?].*/,"").replace(/[^\/]+$/,function(h,f,i){return/[^\/]$/.test(h)?"":h;
}).replace(/\/+$/,"")+"/";return d;},v.getFullUrl=function(h,d){var i=h,f=h.substring(0,1);return d=typeof d=="undefined"?!0:d,/[a-z]+\:\/\//.test(h)||(f==="/"?i=v.getRootUrl()+h.replace(/^\/+/,""):f==="#"?i=v.getPageUrl().replace(/#.*/,"")+h:f==="?"?i=v.getPageUrl().replace(/[\?#].*/,"")+h:d?i=v.getBaseUrl()+h.replace(/^(\.\/)+/,""):i=v.getBasePageUrl()+h.replace(/^(\.\/)+/,"")),i.replace(/\#$/,"");
},v.getShortUrl=function(h){var d=h,i=v.getBaseUrl(),f=v.getRootUrl();return v.emulated.pushState&&(d=d.replace(i,"")),d=d.replace(f,"/"),v.isTraditionalAnchor(d)&&(d="./"+d),d=d.replace(/^(\.\/)+/g,"./").replace(/\#$/,""),d;
},v.getLocationHref=function(d){return d=d||b,d.URL===d.location.href?d.location.href:d.location.href===decodeURIComponent(d.URL)?d.URL:d.location.hash&&decodeURIComponent(d.location.href.replace(/^[^#]+/,""))===d.location.hash?d.location.href:d.URL.indexOf("#")==-1&&d.location.href.indexOf("#")!=-1?d.location.href:d.URL||d.location.href;
},v.store={},v.idToState=v.idToState||{},v.stateToId=v.stateToId||{},v.urlToId=v.urlToId||{},v.storedStates=v.storedStates||[],v.savedStates=v.savedStates||[],v.normalizeStore=function(){v.store.idToState=v.store.idToState||{},v.store.urlToId=v.store.urlToId||{},v.store.stateToId=v.store.stateToId||{};
},v.getState=function(f,d){typeof f=="undefined"&&(f=!0),typeof d=="undefined"&&(d=!0);var h=v.getLastSavedState();return !h&&d&&(h=v.createStateObject()),f&&(h=v.cloneObject(h),h.url=h.cleanUrl||h.url),h;
},v.getIdByState=function(f){var d=v.extractId(f.url),h;if(!d){h=v.getStateString(f);if(typeof v.stateToId[h]!="undefined"){d=v.stateToId[h];}else{if(typeof v.store.stateToId[h]!="undefined"){d=v.store.stateToId[h];
}else{for(;;){d=(new Date).getTime()+String(Math.random()).replace(/\D/g,"");if(typeof v.idToState[d]=="undefined"&&typeof v.store.idToState[d]=="undefined"){break;
}}v.stateToId[h]=d,v.idToState[d]=f;}}}return d;},v.normalizeState=function(f){var d,h;if(!f||typeof f!="object"){f={};}if(typeof f.normalized!="undefined"){return f;
}if(!f.data||typeof f.data!="object"){f.data={};}return d={},d.normalized=!0,d.title=f.title||"",d.url=v.getFullUrl(f.url?f.url:v.getLocationHref()),d.hash=v.getShortUrl(d.url),d.data=v.cloneObject(f.data),d.id=v.getIdByState(d),d.cleanUrl=d.url.replace(/\??\&_suid.*/,""),d.url=d.cleanUrl,h=!v.isEmptyObject(d.data),(d.title||h)&&v.options.disableSuid!==!0&&(d.hash=v.getShortUrl(d.url).replace(/\??\&_suid.*/,""),/\?/.test(d.hash)||(d.hash+="?"),d.hash+="&_suid="+d.id),d.hashedUrl=v.getFullUrl(d.hash),(v.emulated.pushState||v.bugs.safariPoll)&&v.hasUrlDuplicate(d)&&(d.url=d.hashedUrl),d;
},v.createStateObject=function(h,d,i){var f={data:h,title:d,url:i};return f=v.normalizeState(f),f;},v.getStateById=function(d){d=String(d);var f=v.idToState[d]||v.store.idToState[d]||C;
return f;},v.getStateString=function(h){var d,i,f;return d=v.normalizeState(h),i={data:d.data,title:h.title,url:h.url},f=m.stringify(i),f;},v.getStateId=function(f){var d,h;
return d=v.normalizeState(f),h=d.id,h;},v.getHashByState=function(f){var d,h;return d=v.normalizeState(f),h=d.hash,h;},v.extractId=function(l){var f,o,h,d;
return l.indexOf("#")!=-1?d=l.split("#")[0]:d=l,o=/(.*)\&_suid=([0-9]+)$/.exec(d),h=o?o[1]||l:l,f=o?String(o[2]||""):"",f||!1;},v.isTraditionalAnchor=function(f){var d=!/[\/\?\.]/.test(f);
return d;},v.extractState=function(l,f){var o=null,h,d;return f=f||!1,h=v.extractId(l),h&&(o=v.getStateById(h)),o||(d=v.getFullUrl(l),h=v.getIdByUrl(d)||!1,h&&(o=v.getStateById(h)),!o&&f&&!v.isTraditionalAnchor(l)&&(o=v.createStateObject(null,null,d))),o;
},v.getIdByUrl=function(d){var f=v.urlToId[d]||v.store.urlToId[d]||C;return f;},v.getLastSavedState=function(){return v.savedStates[v.savedStates.length-1]||C;
},v.getLastStoredState=function(){return v.storedStates[v.storedStates.length-1]||C;},v.hasUrlDuplicate=function(f){var d=!1,h;return h=v.extractState(f.url),d=h&&h.id!==f.id,d;
},v.storeState=function(d){return v.urlToId[d.url]=d.id,v.storedStates.push(v.cloneObject(d)),d;},v.isLastSavedState=function(l){var f=!1,o,h,d;return v.savedStates.length&&(o=l.id,h=v.getLastSavedState(),d=h.id,f=o===d),f;
},v.saveState=function(d){return v.isLastSavedState(d)?!1:(v.savedStates.push(v.cloneObject(d)),!0);},v.getStateByIndex=function(f){var d=null;return typeof f=="undefined"?d=v.savedStates[v.savedStates.length-1]:f<0?d=v.savedStates[v.savedStates.length+f]:d=v.savedStates[f],d;
},v.getCurrentIndex=function(){var d=null;return v.savedStates.length<1?d=0:d=v.savedStates.length-1,d;},v.getHash=function(f){var d=v.getLocationHref(f),h;
return h=v.getHashByUrl(d),h;},v.unescapeHash=function(f){var d=v.normalizeHash(f);return d=decodeURIComponent(d),d;},v.normalizeHash=function(f){var d=f.replace(/[^#]*#/,"").replace(/#.*/,"");
return d;},v.setHash=function(h,f){var l,d;return f!==!1&&v.busy()?(v.pushQueue({scope:v,callback:v.setHash,args:arguments,queue:f}),!1):(v.busy(!0),l=v.extractState(h,!0),l&&!v.emulated.pushState?v.pushState(l.data,l.title,l.url,!1):v.getHash()!==h&&(v.bugs.setHash?(d=v.getPageUrl(),v.pushState(null,null,d+"#"+h,!1)):b.location.hash=h),v);
},v.escapeHash=function(d){var f=v.normalizeHash(d);return f=x.encodeURIComponent(f),v.bugs.hashEscape||(f=f.replace(/\%21/g,"!").replace(/\%26/g,"&").replace(/\%3D/g,"=").replace(/\%3F/g,"?")),f;
},v.getHashByUrl=function(f){var d=String(f).replace(/([^#]*)#?([^#]*)#?(.*)/,"$2");return d=v.unescapeHash(d),d;},v.setTitle=function(h){var f=h.title,l;
f||(l=v.getStateByIndex(0),l&&l.url===h.url&&(f=l.title||v.options.initialTitle));try{b.getElementsByTagName("title")[0].innerHTML=f.replace("<","&lt;").replace(">","&gt;").replace(" & "," &amp; ");
}catch(d){}return b.title=f,v;},v.queues=[],v.busy=function(f){typeof f!="undefined"?v.busy.flag=f:typeof v.busy.flag=="undefined"&&(v.busy.flag=!1);if(!v.busy.flag){B(v.busy.timeout);
var d=function(){var i,l,h;if(v.busy.flag){return;}for(i=v.queues.length-1;i>=0;--i){l=v.queues[i];if(l.length===0){continue;}h=l.shift(),v.fireQueueItem(h),v.busy.timeout=j(d,v.options.busyDelay);
}};v.busy.timeout=j(d,v.options.busyDelay);}return v.busy.flag;},v.busy.flag=!1,v.fireQueueItem=function(d){return d.callback.apply(d.scope||v,d.args||[]);
},v.pushQueue=function(d){return v.queues[d.queue||0]=v.queues[d.queue||0]||[],v.queues[d.queue||0].push(d),v;},v.queue=function(f,d){return typeof f=="function"&&(f={callback:f}),typeof d!="undefined"&&(f.queue=d),v.busy()?v.pushQueue(f):v.fireQueueItem(f),v;
},v.clearQueue=function(){return v.busy.flag=!1,v.queues=[],v;},v.stateChanged=!1,v.doubleChecker=!1,v.doubleCheckComplete=function(){return v.stateChanged=!0,v.doubleCheckClear(),v;
},v.doubleCheckClear=function(){return v.doubleChecker&&(B(v.doubleChecker),v.doubleChecker=!1),v;},v.doubleCheck=function(d){return v.stateChanged=!1,v.doubleCheckClear(),v.bugs.ieDoubleCheck&&(v.doubleChecker=j(function(){return v.doubleCheckClear(),v.stateChanged||d(),!0;
},v.options.doubleCheckInterval)),v;},v.safariStatePoll=function(){var d=v.extractState(v.getLocationHref()),f;if(!v.isLastSavedState(d)){return f=d,f||(f=v.createStateObject()),v.Adapter.trigger(x,"popstate"),v;
}return;},v.back=function(d){return d!==!1&&v.busy()?(v.pushQueue({scope:v,callback:v.back,args:arguments,queue:d}),!1):(v.busy(!0),v.doubleCheck(function(){v.back(!1);
}),g.go(-1),!0);},v.forward=function(d){return d!==!1&&v.busy()?(v.pushQueue({scope:v,callback:v.forward,args:arguments,queue:d}),!1):(v.busy(!0),v.doubleCheck(function(){v.forward(!1);
}),g.go(1),!0);},v.go=function(f,d){var h;if(f>0){for(h=1;h<=f;++h){v.forward(d);}}else{if(!(f<0)){throw new Error("History.go: History.go requires a positive or negative integer passed.");
}for(h=-1;h>=f;--h){v.back(d);}}return v;};if(v.emulated.pushState){var c=function(){};v.pushState=v.pushState||c,v.replaceState=v.replaceState||c;}else{v.onPopState=function(f,u){var l=!1,d=!1,h,p;
return v.doubleCheckComplete(),h=v.getHash(),h?(p=v.extractState(h||v.getLocationHref(),!0),p?v.replaceState(p.data,p.title,p.url,!1):(v.Adapter.trigger(x,"anchorchange"),v.busy(!1)),v.expectedStateId=!1,!1):(l=v.Adapter.extractEventData("state",f,u)||!1,l?d=v.getStateById(l):v.expectedStateId?d=v.getStateById(v.expectedStateId):d=v.extractState(v.getLocationHref()),d||(d=v.createStateObject(null,null,v.getLocationHref())),v.expectedStateId=!1,v.isLastSavedState(d)?(v.busy(!1),!1):(v.storeState(d),v.saveState(d),v.setTitle(d),v.Adapter.trigger(x,"statechange"),v.busy(!1),!0));
},v.Adapter.bind(x,"popstate",v.onPopState),v.pushState=function(f,o,l,d){if(v.getHashByUrl(l)&&v.emulated.pushState){throw new Error("History.js does not support states with fragement-identifiers (hashes/anchors).");
}if(d!==!1&&v.busy()){return v.pushQueue({scope:v,callback:v.pushState,args:arguments,queue:d}),!1;}v.busy(!0);var h=v.createStateObject(f,o,l);return v.isLastSavedState(h)?v.busy(!1):(v.storeState(h),v.expectedStateId=h.id,g.pushState(h.id,h.title,h.url),v.Adapter.trigger(x,"popstate")),!0;
},v.replaceState=function(f,o,l,d){if(v.getHashByUrl(l)&&v.emulated.pushState){throw new Error("History.js does not support states with fragement-identifiers (hashes/anchors).");
}if(d!==!1&&v.busy()){return v.pushQueue({scope:v,callback:v.replaceState,args:arguments,queue:d}),!1;}v.busy(!0);var h=v.createStateObject(f,o,l);return v.isLastSavedState(h)?v.busy(!1):(v.storeState(h),v.expectedStateId=h.id,g.replaceState(h.id,h.title,h.url),v.Adapter.trigger(x,"popstate")),!0;
};}if(D){try{v.store=m.parse(D.getItem("History.store"))||{};}catch(a){v.store={};}v.normalizeStore();}else{v.store={},v.normalizeStore();}v.Adapter.bind(x,"unload",v.clearAllIntervals),v.saveState(v.storeState(v.extractState(v.getLocationHref(),!0))),D&&(v.onUnload=function(){var l,f,o;
try{l=m.parse(D.getItem("History.store"))||{};}catch(h){l={};}l.idToState=l.idToState||{},l.urlToId=l.urlToId||{},l.stateToId=l.stateToId||{};for(f in v.idToState){if(!v.idToState.hasOwnProperty(f)){continue;
}l.idToState[f]=v.idToState[f];}for(f in v.urlToId){if(!v.urlToId.hasOwnProperty(f)){continue;}l.urlToId[f]=v.urlToId[f];}for(f in v.stateToId){if(!v.stateToId.hasOwnProperty(f)){continue;
}l.stateToId[f]=v.stateToId[f];}v.store=l,v.normalizeStore(),o=m.stringify(l);try{D.setItem("History.store",o);}catch(d){if(d.code!==DOMException.QUOTA_EXCEEDED_ERR){throw d;
}D.length&&(D.removeItem("History.store"),D.setItem("History.store",o));}},v.intervalList.push(A(v.onUnload,v.options.storeInterval)),v.Adapter.bind(x,"beforeunload",v.onUnload),v.Adapter.bind(x,"unload",v.onUnload));
if(!v.emulated.pushState){v.bugs.safariPoll&&v.intervalList.push(A(v.safariStatePoll,v.options.safariPollInterval));if(q.vendor==="Apple Computer, Inc."||(q.appCodeName||"")==="Mozilla"){v.Adapter.bind(x,"hashchange",function(){v.Adapter.trigger(x,"popstate");
}),v.getHash()&&v.Adapter.onDomLoad(function(){v.Adapter.trigger(x,"hashchange");});}}},(!v.options||!v.options.delayInit)&&v.init();}(window);