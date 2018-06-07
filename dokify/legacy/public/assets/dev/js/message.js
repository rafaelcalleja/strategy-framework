define(["form","confirmbox","modal"],function(c,b,a){function d(e){this.dom=$(e);}d.prototype.submit=function(f,k,o,h,e,j,l){var n,m,g,i;n=f.parseURI().reverse["?"]||"selected";
f=f.replace(n+"="+encodeURIComponent("?"),"");m=$(document.createElement("form"));m.attr("action",f);m.attr("method",e);m.attr("style","display:none;");
if(j===false){for(i in l){m.append("<input type='hidden' name='"+n+"[]' value='"+i+"'/>");}}else{m.append("<input type='hidden' name='fullList' value='true'/>");
}if(k==="iframe"){g=$(document.createElement("iframe"));g.attr("name","customIframe");g.appendTo(m);m.attr("target","customIframe");}else{if(k==="modal"){a.init.call(m.get(0));
}else{new c(m);}}$(document.body).append(m);setTimeout(function(){m.remove();},10*60*1000);if(o!==undefined){$(document).trigger("message:"+h,[o]);}m.submit();
};d.init=function(){return new d(this);};return d;});