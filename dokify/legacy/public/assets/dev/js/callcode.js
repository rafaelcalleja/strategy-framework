define(["dokify"],function(c){var b=false;function a(e){var d=this;if(b==false){$.get("/call/code/generate",function(f){c.callcode=f;d.refresh();});b=true;
}d.refresh();}a.prototype.refresh=function(){$(document).find(".call-code-show").each(function(){$(this).text(c.callcode);});};a.init=function(){new a(this);
};return a;});