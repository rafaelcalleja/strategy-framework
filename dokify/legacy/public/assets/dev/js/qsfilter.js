define(["janddress"],function(){function a(d){var b,i,h,f,g,e,j,c;g=$(d).data("reset")||false;i=$(d).val();h=i.length;b=$(d).attr("name");f=janddress.getCurrent();
e=f.parseURI();if(true===g){c={};j=e.pathname;}else{if(g){c={};j=e.pathname;$.each(g.split(","),function(){var k=this.toString();if(e.query[k]){c[k]=e.query[k];
}});}else{c=e.query;j=f;}}if(e.query[b]===i){d.selectionStart=h;d.selectionEnd=h;$(d).focus();}$(d).keyup(function(k){if(k.keyCode===13){i=encodeURI($(d).val());
c[b]=i;$(document).trigger("location",[j,c]);}});$(d).keypress(function(k){if(k.which===13){k.preventDefault();}});}a.init=function(){return new a(this);
};return a;});