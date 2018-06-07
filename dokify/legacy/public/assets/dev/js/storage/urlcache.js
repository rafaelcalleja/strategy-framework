define(["storage/strategy/polyfillcache","jquery"],function(c,a){function b(e){var d=this;this.enabledUrls=[];this.cacheStorage=new c();this.cackeKeyPrefix="";
if(typeof(e)!=="undefined"){this.cackeKeyPrefix=e;}a.ajaxPrefilter(function(o,j,m){for(i=0;i<d.enabledUrls.length;i++){var g=d.enabledUrls[i].url;var l=d.enabledUrls[i].timeout;
var f=d.enabledUrls[i].method.toUpperCase();var h=new RegExp(g.replace(/\//g,"\\/"));if(true===h.test(j.url)&&f===o.type.toUpperCase()){var n=j.success||a.noop;
var k=d.cackeKeyPrefix+j.url;o.cache=false;o.beforeSend=function(){if(d.cacheStorage.exist(k)){n(d.cacheStorage.get(k));return false;}return true;};o.success=function(p,q){d.cacheStorage.set(k,p,l);
if(a.isFunction(n)){n(p);}};break;}}});}b.prototype.urlIsEnabled=function(e){var d=a.grep(this.enabledUrls,function(f){return f.url==e;});return d.length>0;
};b.prototype.enableForUrlMatch=function(e,h,g){var d=this;if(typeof(h)==="undefined"){h="GET";}var f={url:e,method:h,timeout:g};if(this.urlIsEnabled(e)){return false;
}this.enabledUrls.push(f);return true;};return b;});