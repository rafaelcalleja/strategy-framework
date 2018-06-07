define(function(){var a={};function b(){var j,i,c,g,e,l,d,m,f,k,h,n,o;j=this;i=$(this).is(":visible");c=$(this).data("src");g=$(this).data("interval");
e=$(this).data("context");d=$(this).data("cache");m=$(this).data("keydown");l=Math.round((new Date()).getTime()/1000);if(!c){return true;}$(this).removeAttr("data-src");
f=function(){if(a[c]){delete a[c];}};k=function(p){var q=$(j).html();if(d>0){setTimeout(f,d);}if(p&&q!==p){l=Math.round((new Date()).getTime()/1000);$(j).html(p);
$(document).trigger("redraw",[j]);}};h=function(){var p=$(j).offset().top;if(g>0&&p!==0){setTimeout(n,g);}};n=function(){var p=a[c];if(p!==undefined){p.done(k).complete(h);
return;}$.ajax({url:c,data:{since:l},beforeSend:function(q){q.setRequestHeader("Async",true);if(e){q.setRequestHeader("Context",e);}if(d>0){a[c]=q;}},success:k,error:function(){f();
},complete:h});};o=function(r,q){var p=$(j).is(q)||q.find(j).length;if(p){$(document).off("appear",o);n();}};if(i){if(g){setTimeout(n,g);}else{n();}return;
}$(document).on("appear",o);if(m){$(document).on("keydown",function(p){if(p.keyCode===m&&p.ctrlKey){p.preventDefault();n();$(j).show();}});}}return{init:b,cache:a};
});