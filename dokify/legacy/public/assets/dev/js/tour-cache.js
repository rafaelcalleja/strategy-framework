define(["storage/strategy/polyfillcache","jquery"],function(c,a){function b(e){var d=this;this.enabledUrls=[];this.cacheStorage=new c();this.cacheKeyPrefix="";
this.urlcache=e;if(typeof(e.cackeKeyPrefix)!=="undefined"){this.cacheKeyPrefix=e.cackeKeyPrefix;}}b.prototype.setToursOfUrl=function(g){var e=this;var k=[];
var h=e.cacheKeyPrefix+g;var f=$("#help-tours-available").find("[data-open-tour]").size()>0;if(false===f||!g){return;}$("#help-tours-available").find("[data-open-tour]").each(function(){k.push($(this).data("open-tour"));
});var j=e.cacheStorage.get(h);console.log("tourUrl ",g);console.log("cachedToursOfUrl ",j);console.log("NEW toursOfUrl ",k);if(a(j).not(k).get().length>0||a(k).not(j).get().length>0){var d=e.getToursOfUrl(g);
if(d){for(i=0;i<d.length;i++){this.urlcache.purgeUrl("/app/tour/"+d[i]);}}}e.cacheStorage.set(h,k);};b.prototype.getToursOfUrl=function(e){var d=this;var f=d.cacheKeyPrefix+e;
return d.cacheStorage.get(f);};b.prototype.existTourInTourUrl=function(e,f){var d=this;var g=d.getToursOfUrl(f);return g.indexOf(e)!==-1;};return b;});
