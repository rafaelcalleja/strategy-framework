define(["network","navigation","janddress"],function(s,c){var k=false,g,q,e;function n(){if(!g){return;}$(document.body).addClass("noscroll");q.show();
g.show();k=true;}function b(){if(!g){return;}$(document.body).removeClass("noscroll");q.hide();g.hide();k=false;}function o(t){if(t.keyCode==27&&g&&g.is(":visible")){$(document).trigger("modal:stop",[e]);
e.abort();b();}}function i(v){var u;if(!g){return;}if(e&&e.readyState<4){return;}if(e&&(u=e.getResponseHeader("mandatory"))){var t=$(v.currentTarget||v.target).hasClass("complete-modal")==true||v.isTrigger==true;
if(t==false){if(!confirm(u)){return false;}}}e=null;b();}function r(t){if(!g){q=$(document.createElement("div")).attr("id","modal-overlay").attr("class","modal-overlay");
g=$(document.createElement("div")).attr("id","modal-wrap").attr("class","modal-wrapper");g.on("click",".close-modal",i);$("body").append(g,q);initialized=true;
}g.html(t);q.appendTo(".modal-auto-scroll");q.on("click",i);g.find("[autofocus]:first").focus();$(document).trigger("redraw",g);n();return g;}function a(x,t,u){var y,v,w;
y=x.getResponseHeader("Content-type");v=y.indexOf("text/plain")!==-1||y.indexOf("text/html")!==-1;w=y.indexOf("application/json")!==-1;if(v){r(t);c.parseXHRHeaders(x);
$(document).trigger("modal:after",[x]);}else{if(w){s.parseResponse(t,u,x);}}}function f(t,w,v){var u="GET";d();w=(w||"GET").toUpperCase();v=v||[];if(w=="GET"||w=="POST"){u=w;
}else{u="POST";v.push({name:"_method",value:w});}e=$.ajax({type:u,data:v,url:t,beforeSend:function(x){x.setRequestHeader("Async",true);x.setRequestHeader("Modal",true);
$(document).trigger("modal:before",[x]);},error:function(A,y,z){var x=A.responseText;if(A.getResponseHeader("Content-type").indexOf("application/json")!==-1){x=$.parseJSON(x);
}a(A,x,y);},success:function(y,x,z){a(z,y,x);}});}function d(t){t=t||"";r(t);}function j(w){var u,x,v,t;w.preventDefault();u=$(this).attr("action");x=$(this).attr("method");
v=$(this).serializeArray();if(this.button){var t=$(this.button).attr("value");if(t){v.push({name:$(this.button).attr("name"),value:t});}}f(u,x,v);}function h(u){var t,v;
u.preventDefault();t=$(this).attr("href");v=$(this).data("method");f(t,v);}function l(w){var x,t,v,u;w.preventDefault();t=$(this).data("target");v=$(t);
if(v.length===0){return;}u=v.html();r(u);}function p(){var t,u=this;t=this.tagName.toLowerCase();if($(u).hasClass("bulk-action")){return;}dataTarget=$(u).data("target");
if(typeof dataTarget!=="undefined"&&dataTarget.charAt(0)==="#"){$(this).on("click",l);return;}if(t=="a"||t=="button"){$(this).on("click",h);}if(t=="form"){$(this).on("click","input[type=submit], button[type=submit]",function(v){u.button=this;
});$(this).on("submit",j);}}function m(){return k;}$(document).on("navigate:change",i);$(document).on("modal:hide",i);$(document).on("modal:show",n);$(document).keypress(o);
$(document).on("redraw",function(u,t){if(false===k){return;}g.find("img").on("load",function(){n();});});return{init:p,hide:i,show:r,fadeIn:n,fadeOut:b,load:f,isOpen:m,handler:{click:h,submit:j}};
});