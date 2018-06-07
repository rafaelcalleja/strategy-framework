define(["dokify","jquery-tourbus","vendor/jquery.scrollTo"],function(c){window.onTour=false;var b={};function a(d){this.uid=d;this.path=a.path+d;this.tourbus=null;
this.cover=$(document.createElement("div")).addClass("tourbus-cover");this.cover.click(function(){return false;});$("body").prepend(this.cover);}a.highlight="tourbus-highlight";
a.overlay=$(".tourbus-overlay");a.path="/app/tour/";a.prototype.preventLeave=function(f){if(f.get(0)===undefined){throw new Error("Error in tour");}var d=f.get(0).tagName,e={};
if(d==="BODY"){return;}e=f.offset();e.width=f.outerWidth();e.height=f.outerHeight();this.cover.show().css(e);};a.init=function(){var d,e;d=$(this).data("tour");
if(!d||0===d.length){return false;}if(window.onTour===true){return false;}if(c.preload!==false){return false;}if(b[d]!==undefined){return false;}window.onTour=true;
e=new a(d);e.fetch();};a.prototype.setHTML=function(e){var d=this,f=$(e);this.tourbus=f.tourbus({tour:this,onStop:function(){d.onStop();},onDestroy:this.onDestroy,onLegStart:this.onLegStart,onLegEnd:this.onLegEnd});
};a.prototype.fetch=function(e){var d=this;e=e||true;if(true===$("#help-box-button").hasClass("open")){$("#help-box-button").click();}if(e){a.overlay.html('<span class="spinner overlay"></span>').show();
}$.get(this.path,function(f){a.overlay.empty();if(f){d.setHTML(f);if(e){d.depart();}}});};a.prototype.onLegStart=function(g,e){var h,i,j,f;f=this;i=e.options.tour;
j=g.$target.closest(".pull-down");if(j.length===0){$(".dropdown.open").trigger("hide");}if(g.rawData.highlight){g.$target.addClass(a.highlight);a.overlay.show();
}else{a.overlay.hide();}function d(){e.repositionLegs();}if(g.rawData.elNext){if(g.$target.hasClass("toggle")){g.$target.one("click",function(k){k.stopPropagation();
i.next();setTimeout(function(){d();if(e.legs){i.preventLeave(e.currentLeg().$target);}},50);});}if(g.$target.hasClass("dropdown")){h=g.$target.data("target").find();
g.$target.click(function(){i.next();});if(h){h.addClass(a.highlight);h.one("dropdown:open",function(){d();i.preventLeave(h);});h.one("dropdown:hide",function(){h.removeClass(a.highlight);
});}}}else{if(g.rawData.elStop){g.$target.on("click",function(k){i.stop(e);});}else{i.preventLeave(g.$target);}}if(j.length){g.$el.on("click",function(k){k.stopPropagation();
setTimeout(function(){if(j.is(":visible")){d();i.preventLeave(j);}},50);});}g.$el.on("click",".tourbus-reset",function(){i.onDestroy(g,e);i=new a(i.uid);
i.fetch();});g.$el.on("click",".tourbus-destroy",function(){i.onDestroy(g,e);});$(document).on("redraw",function(){e.repositionLegs();});e.repositionLegs();
$(document).ready(function(){e.currentLeg().render();$(g.$el).find(".tourbus-hover").mouseover(function(){$(g.rawData.el).addClass("tourbus-hover-highlight");
}).mouseout(function(){$(g.rawData.el).removeClass("tourbus-hover-highlight");});});};a.prototype.onLegEnd=function(e,d){var f=d.options.tour;f.cover.hide();
if(e.rawData.highlight){e.$target.removeClass(a.highlight);}};a.prototype.onStop=function(){$("."+a.highlight).removeClass(a.highlight);a.overlay.hide();
if(this.cover){this.cover.remove();}b[this.uid]=true;window.onTour=false;$.tourbus("destroyAll");};a.prototype.stop=function(d){d.destroy();this.onStop();
};a.prototype.onDestroy=function(f,d){var e=d.options.tour;this.stop(d);$.post(e.path,{_method:"delete"});};a.prototype.next=function(){this.tourbus.trigger("next.tourbus");
};a.prototype.depart=function(){a.overlay.click(function(){return false;});window.onTour=true;this.tourbus.trigger("depart.tourbus");};return a;});