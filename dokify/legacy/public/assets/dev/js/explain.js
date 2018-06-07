define(["vendor/ion.calendar"],function(){function a(h){var c,f,d,b,g,e;f=$(h).data("target");this.dom=h;this.form=h.form;this.target=f?$(f):this.form;
this.empty=$(this.dom).data("explain-empty");this.layer=$(document.createElement("div"));this.textarea=$(document.createElement("textarea"));c=this;d=$(this.target).outerWidth();
b=$(this.target).outerHeight();g=$(this.target).offset();e=$(this.dom).data("explain-message");if($(this.dom).data("explain-height")){b=parseInt($(this.dom).data("explain-height"),10);
}this.textarea.attr("name","message").attr("placeholder",e).css({width:d+"px",height:b+"px"});this.layer.css({position:"absolute",top:g.top+"px",left:g.left+"px",width:d+"px",height:b+"px"}).addClass("explain-textarea");
this.layer.append(this.textarea);$(this.dom).click(function(i){i.preventDefault();c.show();});}a.prototype.hide=function(){this.layer.remove();};a.prototype.show=function(){var b=this;
$(this.target).append(this.layer);this.textarea.focus();this.textarea.keypress(function(c){var d=$(this).val();if(c.keyCode===13){c.preventDefault();if(d.length>5){$(b.form).submit();
$(b.textarea).attr("disabled","disabled");setTimeout(function(){b.hide();$(b.textarea).removeAttr("disabled");},1000);}else{if(b.empty){$(document).trigger("message:error",[b.empty]);
}}}if(c.keyCode===9){c.preventDefault();}return true;});this.textarea.blur(function(c){c.stopPropagation();b.hide();return false;});};a.init=function(){return new a(this);
};return a;});