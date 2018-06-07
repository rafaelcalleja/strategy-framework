define(function(){function a(e){var b=this,g,f=60,d=32,c=5;this.dom=$(e);this.type=this.dom.data("effect");this.frame=0;this.icon=null;this.remove=function(){b.frame=0;
if(b.icon){b.icon.remove();}if(g){clearTimeout(g);}};this.nextFrame=function(){var h=0-(b.frame*d);b.icon.css("background-position","0 "+h+"px");b.frame++;
if(b.frame<=c){g=setTimeout(b.nextFrame,f);}else{b.remove();}};this.animate=function(h){if(g){clearTimeout(g);}b.icon=$(document.createElement("i")).addClass("effect "+b.type);
b.icon.appendTo("body");b.icon.css({top:h.pageY-(d/2)-10,left:h.pageX-(d/2)+10,"background-position":"0 0"});g=setTimeout(b.nextFrame,f);};this.dom.click(this.animate);
}a.init=function(){return new a(this);};return a;});