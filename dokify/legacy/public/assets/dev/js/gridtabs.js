(function(a,c){function b(){$(".grid-tabs").each(function(){var f=$(this),n=f.find("li"),j=f.find("li.current"),v=$(".grid-tabs + i"),h=$(f.data("contents")),s=h.find(".tab-content"),e=h.get(0).scrollHeight,q,t,i=n.size(),g=n.index(j)+1,m=$(this).data("timer"),d=f.position().left,r=v.outerWidth();
h.height(e);function o(){g=n.index(this)+1;j=$(this);var y=$(this).position().left-d,w=$(this).outerWidth(),x=y+(w/2)-(r/2);v.css("margin-left",x+"px");
s.css({opacity:0,"z-index":"-1"});h.find(".tab-"+g).css({opacity:1,"z-index":"1"});f.find("li.current").removeClass("current");j.addClass("current");}function k(){if(t){clearTimeout(t);
}if(q){clearTimeout(q);}o.call(this);}function u(){o.call(j);if(m){q=setTimeout(p,m);}}function l(){t=setTimeout(u,1000);}function p(){var w=(i>g)?g:0;
j=$(n.get(w));u();}n.on("mouseleave",l);n.on("mouseenter",k);s.hover(function(){k.call(j.get(0));},l);u();});}a[a.addEventListener?"addEventListener":"attachEvent"](a.addEventListener?"load":"onload",b,false);
})(window);