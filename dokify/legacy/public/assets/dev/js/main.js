/*! main web js file */
(function(f,i,b){var c=navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/),d=f("html"),e=f(i.document.body),k=e.find("#canvas"),g,l;
f.fn.scrollView=function(m){var o,n;o=this.offset().top||this.parent().offset().top;o=o>10?o-10:o;setTimeout(function(){f("html, body").animate({scrollTop:o},"normal",function(){if(!n){n=true;
m();}});},10);};function h(o){var m,n;if(o.pathNames[0]=="modal"){n=o.pathNames[1];g=f("#"+n);e.animate({scrollTop:0},"fast",function(){k.css("overflow","hidden");
g.reveal({animation:"fade",dismissModalClass:"close-reveal-modal",opened:function(){g.find("input[autofocus]").focus();if(i.PIE){g.find(".circle").css("margin-left","1px");
}},close:function(){location.hash="";k.css("overflow","visible");}});});}else{if(o.pathNames[0]=="toggle"){m=o.pathNames[1];if(m){l=f("#"+m);l.scrollView(function(){l.slideToggle("normal");
});}}else{if(l){l.scrollView(function(){l.slideToggle();l=false;});}if(g){g.trigger("reveal:close");}}}}f.address.change(h);String.prototype.linkify=function(){return this.replace(/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+/,function(n){return n.link(n);
});};String.prototype.tweetify=function(){return this.replace(/@[\w]+/g,function(n){return"<a href='http://www.twitter.com/"+n.replace("@","")+"'>"+n+"</a>";
});};function j(o){var n=o.split(" ");o=n[1]+" "+n[2]+", "+n[5]+" "+n[3];var m=Date.parse(o);var q=(arguments.length>1)?arguments[1]:new Date();var s=parseInt((q.getTime()-m)/1000);
s=s+(q.getTimezoneOffset()*60);var p="";if(s<60){p="a minute ago";}else{if(s<120){p="couple of minutes ago";}else{if(s<(45*60)){p=(parseInt(s/60)).toString()+" minutes ago";
}else{if(s<(90*60)){p="an hour ago";}else{if(s<(24*60*60)){p=""+(parseInt(s/3600)).toString()+" hours ago";}else{if(s<(48*60*60)){p="1 day ago";}else{p=(parseInt(s/86400)).toString()+" days ago";
}}}}}}return p;}function a(m){f(m).find(".popup").click(function(t){var p=f(this).attr("href"),o=f(this).data("popup-name")||false,q=f(this).data("popup-width")||500,n=f(this).data("popup-height")||280,s=(screen.width/2)-(q/2),r=(screen.height/2)-(n/2);
i.open(p,o,"height="+n+",width="+q+",top="+r+",left="+s+",resizable=no");});f(m).find(".fb-count").each(function(){var r=f(this),q,p=(r.data("fb-count")||"share")+"_count",n=r.data("url")||location.href,o="https://api.facebook.com/method/fql.query?query=select "+p+" from link_stat where url='"+encodeURI(n)+"'&format=json&_="+(new Date()).getTime();
function s(t){r.html(t);}f.getJSON(o,function(t){if(t[0]&&t[0][p]!==b){s(t[0][p]);}});});f(m).find(".twitter-timeline").each(function(){var q=f(this),n=q.data("tweets-debug")||false,p=q.data("tweets-tmpl"),o=q.data("tweets-user"),s=q.data("tweets-count")||3,r=f(document.createElement("div"));
if(o){f.getJSON("https://api.twitter.com/1/statuses/user_timeline/"+o+".json?count="+s+"&include_rts=1&callback=?",function(t){var u=t.length-1;f(t).each(function(v,w){if(n&&i.console){console.log(w);
}w.created_since=j(w.created_at);w.html=w.text.linkify().tweetify();r.append(tmpl(p,w));if(v===u){q.html(r);}});});}});f(m).find("form.async").submit(function(s){s.preventDefault();
var v=f(this),o=v.find('button[type="submit"]'),u=o.text(),p=v.serialize(),q=v.attr("action")||location.href,n=v.attr("method")||"post",t=f[n];v.removeAttr("onsubmit");
(function r(){setTimeout(function(){var y=o.text(),w=y.length-u.length,x=(w===3)?u:y+".";o.html(x);r();},250);})();o.attr("disabled",true).css("width",o.outerWidth()+"px");
t(q,p,function(w){v.html(w);a(v);});});f(m).find(".carousel").each(function(){f(this).slick();});f(m).find(".up").click(function(){f("body,html").animate({scrollTop:0},"slow");
});f(m).find(".scroll").click(function(p){p.preventDefault();var o=f(this).attr("href"),n=f(o).offset().top;f("body,html").animate({scrollTop:n},"slow");
});f(m).find(".toggle").click(function(){var o=f(this),n=f(o.data("toggle"));o.toggleClass("open");n.slideToggle();});f(m).find(".doki-tooltip").each(function(){var n=f(this),p=n.find("section"),w=p.find("input:first"),s=false,q=false,r=700,u;
if(c){return p.remove();}function v(){if(u){clearTimeout(u);}if(s==false){q=false;p.removeClass("transparent");n.addClass("hover");w.focus().keyup(function(){q=!!f(this).val().length;
});}s=true;}function o(){s=false;p.addClass("transparent");u=setTimeout(function(){n.removeClass("hover");p.removeClass("transparent");},r);}function t(){if(q){return;
}u=setTimeout(o,1000);}n.hover(v,t);});}(function(s){var m=s("nav"),q=m.find("li.magic"),p=m.find("li.current a");if(p.length){q.width(p.width()).css("left",p.position().left).data("origLeft",q.position().left).data("origWidth",q.width());
}else{q.width("0px").data("origLeft",0).data("origWidth",0);}var o,n,r;m.find("li").find("a").hover(function(){o=s(this);n=o.position().left;r=o.parent().width();
q.stop().animate({left:n,width:r});},function(){q.stop().animate({left:q.data("origLeft"),width:q.data("origWidth")});});})(i.$);if(c&&!i.location.hash){f(i).load(function(){setTimeout(function(){i.scrollTo(0,1);
},0);});}if(i.PIE){f(".block-grid.two-up>li:nth-child(2n+1)").css({clear:"both"});f(".block-grid.three-up>li:nth-child(3n+1)").css({clear:"both"});f(".block-grid.four-up>li:nth-child(4n+1)").css({clear:"both"});
f(".block-grid.five-up>li:nth-child(5n+1)").css({clear:"both"});}a(e);})($,window);