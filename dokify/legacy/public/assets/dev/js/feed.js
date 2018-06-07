define(function(){function a(c){var b=this;this.date=new Date();this.dom=$(c);this.ul=$(c).find(">ul");this.url=this.dom.data("feed-url");this.entries=this.dom.find("ul>li");
this.updates=[];this.dom.on("click",".feed-next",function(){b.next();});this.dom.on("click",".feed-sync",function(){b.sync();});this.listen();}a.prototype.sync=function(){var b=this;
$.each(this.updates,function(){b.prepend(this);});this.dom.removeClass("feed-unsynced");};a.prototype.buffer=function(b){var c=this;b.each(function(){c.updates.push(this);
});this.dom.addClass("feed-unsynced");this.date=new Date();};a.prototype.listen=function(){var b=this;setTimeout(function(){var c=b.dom.offset();if(c.top===0&&c.left===0){return;
}b.update();},4000);};a.prototype.update=function(d){var b,e,c;b=this;e=Math.floor((this.date.getTime())/1000);c=this.uri({since:e});$.ajax({url:c,success:function(g){var f=$(g).find("ul>li");
if(f.length===0){return;}b.buffer(f);},complete:function(){b.listen();}});};a.prototype.uri=function(c){var b=this.url.parseURI();$.each(c,function(e,d){b.query[e]=d;
});return b.build();};a.prototype.next=function(){var b,c;b=this;c=this.uri({offset:this.entries.length});$.get(c,function(e){var d=$(e).find("ul>li");
if(d.length===0){return b.dom.addClass("feed-complete");}d.each(function(g,f){b.add(f);});});};a.prototype.prepend=function(b){this.ul.prepend(b);this.entries.push(b);
};a.prototype.add=function(b){this.ul.append(b);this.entries.push(b);};a.init=function(){return new a(this);};return a;});