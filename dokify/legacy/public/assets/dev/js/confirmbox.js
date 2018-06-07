define(["modal"],function(b){function a(d){var c=this;this.callback=function(f){c.show(f);};$(d).click(this.callback);}a.prototype.defaultConfirm=function(c){var f,d;
f=$(c.currentTarget);d=f.data("text");if(d&&!confirm(d)){c.preventDefault();c.stopImmediatePropagation();return false;}return true;};a.prototype.show=function(t,g){var s=t.currentTarget,o=s,d=$(s).data("type")?$(s).data("type"):"red",h=$(s).data("action-inverse")?true:false,f=false;
if(s.tagName=="LABEL"){var p=$(s).attr("for");f=$("#"+p).is(":disabled");}else{if(s.tagName=="INPUT"){f=$(s).is(":disabled");}}if(f){return true;}if($(s).hasClass("once")){$(s).off("click",this.callback);
}if(d==="default"){return this.defaultConfirm(t);}if(t.confirmed===undefined){var l=$(s).data("text"),c=$(s),i=$("#modal-confirm").html(),q=new RegExp("%type%","g");
var u=function(){var e=jQuery.Event(t.type);e.confirmed=true;if(o.tagName=="LABEL"){var v=c.attr("for");$("#"+v).trigger(e);}else{c.trigger(e);}if(g!==undefined){g(t);
}};i=i.replace(q,d);i=i.replace("%text%",l);var m=$("<div>"+i+"</div>"),k=$(s).data("title-text"),n=$(s).data("confirm-text"),j=$(s).data("cancel-text"),r;
if(k){m.find("h4 span").text(k);}if(n){m.find(".continue").text(n);}if(j){m.find(".cancel").text(j);}i=m.html();r=b.show(i);t.preventDefault();t.stopImmediatePropagation();
$(r).find("button.continue").one("click",function(v){b.hide(v);if(!h){u();}}).focus();$(r).find("button.cancel").one("click",function(v){b.hide(v);if(h){u();
}});}};a.init=function(){return new a(this);};return a;});