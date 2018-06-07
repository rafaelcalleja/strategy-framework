define(["form","confirmbox","modal","message"],function(d,c,b,e){function a(g){var f=this;this.dom=$(g);this.dom.click(function(h){return f.submit(h);});
}a.prototype.submit=function(){var h,i,g,f,j;h=this.dom.attr("href");i=this.dom.attr("target");j=this.dom.attr("method")||"POST";g=this.dom.data("action-message");
f=this.dom.data("action-message-type")||"info";e.prototype.submit.apply(this,[h,i,g,f,j]);return false;};a.init=function(){return new a(this);};return a;
});