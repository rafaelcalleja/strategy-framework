/*! NProgress (c) 2013, Rico Sta. Cruz
 *  http://ricostacruz.com/nprogress */
(function(a){if(typeof module==="function"){module.exports=a(this.jQuery||require("jquery"));
}else{this.NProgress=a(this.jQuery);}})(function(e){var a={};a.version="0.1.2";var b=a.settings={minimum:0.08,easing:"ease",positionUsing:"",speed:200,trickle:true,trickleRate:0.02,trickleSpeed:800,showSpinner:true,template:'<div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div>'};
a.configure=function(g){e.extend(b,g);return this;};a.status=null;a.set=function(l){var h=a.isStarted();l=f(l,b.minimum,1);a.status=(l===1?null:l);var g=a.render(!h),j=g.find('[role="bar"]'),i=b.speed,k=b.easing;
g[0].offsetWidth;g.queue(function(m){if(b.positionUsing===""){b.positionUsing=a.getPositioningCSS();}j.css(c(l,i,k));if(l===1){g.css({transition:"none",opacity:1});
g[0].offsetWidth;setTimeout(function(){g.css({transition:"all "+i+"ms linear",opacity:0});setTimeout(function(){a.remove();m();},i);},i);}else{setTimeout(m,i);
}});return this;};a.isStarted=function(){return typeof a.status==="number";};a.start=function(){if(!a.status){a.set(0);}var g=function(){setTimeout(function(){if(!a.status){return;
}a.trickle();g();},b.trickleSpeed);};if(b.trickle){g();}return this;};a.done=function(g){if(!g&&!a.status){return this;}return a.inc(0.3+0.5*Math.random()).set(1);
};a.inc=function(g){var h=a.status;if(!h){return a.start();}else{if(typeof g!=="number"){g=(1-h)*f(Math.random()*h,0.1,0.95);}h=f(h+g,0,0.994);return a.set(h);
}};a.trickle=function(){return a.inc(Math.random()*b.trickleRate);};a.render=function(g){if(a.isRendered()){return e("#nprogress");}e("html").addClass("nprogress-busy");
var i=e("<div id='nprogress'>").html(b.template);var h=g?"-100":d(a.status||0);i.find('[role="bar"]').css({transition:"all 0 linear",transform:"translate3d("+h+"%,0,0)"});
if(!b.showSpinner){i.find('[role="spinner"]').remove();}i.appendTo(document.body);return i;};a.remove=function(){e("html").removeClass("nprogress-busy");
e("#nprogress").remove();};a.isRendered=function(){return(e("#nprogress").length>0);};a.getPositioningCSS=function(){var g=document.body.style;var h=("WebkitTransform" in g)?"Webkit":("MozTransform" in g)?"Moz":("msTransform" in g)?"ms":("OTransform" in g)?"O":"";
if(h+"Perspective" in g){return"translate3d";}else{if(h+"Transform" in g){return"translate";}else{return"margin";}}};function f(i,h,g){if(i<h){return h;
}if(i>g){return g;}return i;}function d(g){return(-1+g)*100;}function c(j,h,i){var g;if(b.positionUsing==="translate3d"){g={transform:"translate3d("+d(j)+"%,0,0)"};
}else{if(b.positionUsing==="translate"){g={transform:"translate("+d(j)+"%,0)"};}else{g={"margin-left":d(j)+"%"};}}g.transition="all "+h+"ms "+i;return g;
}return a;});