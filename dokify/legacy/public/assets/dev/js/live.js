define(["network"],function(e){var h,i,c,f,a,b;h=$("#notifications-list");i=$("#deploying");c=$("#simulating");f=$("#readonly");a=false;b=$("#progress");
function d(j){this.dom=j;this.counter=j.data("counter").find();this.company=j.data("company");}d.prototype.update=function(l){var j,k;j=parseInt(this.counter.text());
k=this.company;if(l.company===k&&l.count===j){return true;}if(l.count===0){this.counter.addClass("empty");}else{this.counter.removeClass("empty").addClass("animated");
}this.counter.html(l.count);this.dom.html(l.html);this.dom.data("company",l.company);$(document).trigger("redraw",this.dom);};function g(){var j=this;this.url="/app/live";
this.timeout=4000;this.get=function(){return $.getJSON(j.url);};this.done=function(k){var l=new d(h);l.update(k.notifications);if(k.progress){b.html(k.progress).show();
b.css("margin-left",0-b.outerWidth()/2);j.timeout=1000;}else{if(b.is(":visible")){b.hide();j.timeout=4000;}}if(k.deploying!==undefined){if(k.deploying==true&&i.is(":visible")===false){i.show();
}if(k.deploying==false&&i.is(":visible")===true){i.hide();}}if(k.readonly!==undefined){if(k.readonly==true&&f.is(":visible")===false){f.show();}if(k.readonly==false&&f.is(":visible")===true){f.hide();
}}if(k.simulating!==undefined){if(k.simulating==true&&c.is(":visible")===false){c.show();}if(k.simulating==false&&c.is(":visible")===true){c.hide();}}if(k.messages&&window.onTour===false){require(["modal"],function(m){if(a===false&&m.isOpen()===false){a=true;
m.load(k.messages);}});}if(k.tasks){require(["backgroundtask"],function(m){$.each(k.tasks,function(o,p){var n=new m(p);n.init();});});}j.delay();};this.exec=function(){return j.get().done(j.done).fail(j.delay);
};this.delay=function(l){if(l&&l.status===401){var k=janddress.getCurrent(true);window.location="/login?goto="+encodeURIComponent(k);return;}setTimeout(function(){j.exec();
},j.timeout);};this.exec();}g.init=function(){return new g();};return g;});