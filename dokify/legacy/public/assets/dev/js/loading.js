define([],function(){function a(g){var c=$(g).data("toggle-element")||false,f=$(g).data("class-list")||false,b=$(g).data("class-target")||false;function e(){if(false!==c){$(c).hide();
}if(false!==f&&false!==b){$(b).removeClass(f).addClass(f);}}function d(){if(false!==c){$(c).show();}if(false!==f&&false!==b){$(b).removeClass(f);}}$(g).click(function(){var h=$.Callbacks(),i;
i=function(k,n,m){h.remove(i);var l=n.beforeSend||$.noop,j=n.error||$.noop;k.beforeSend=function(p,o){e();if(true===$.isFunction(l)){return l(p,o);}};k.error=function(o,q,p){d();
if(true===$.isFunction(j)){return j(o,q,p);}};};h.add(i);$.ajaxPrefilter(h.fire);});}a.init=function(){return new a(this);};return a;});