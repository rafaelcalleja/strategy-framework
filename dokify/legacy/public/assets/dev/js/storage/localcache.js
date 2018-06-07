define(["vendor/json3","storage/itemfactory"],function(b,c){function a(){this.localStorage=window.localStorage;this.itemFactory=new c();}a.prototype.clear=function(){this.localStorage.clear();
};a.prototype.remove=function(d){this.localStorage.removeItem(d);};a.prototype.exist=function(e){var d=this.getObject(e);return !!d&&((new Date().getTime()-d.timestamp)<d.timeout)&&!!d.value;
};a.prototype.get=function(d){return this.getObject(d).value;};a.prototype.set=function(e,g,f){this.remove(e);var d=this.itemFactory.create(g,f);this.setObject(e,d);
};a.prototype.setObject=function(d,e){this.localStorage.setItem(d,b.stringify(e));};a.prototype.getObject=function(d){var e=this.localStorage.getItem(d);
return e&&b.parse(e);};return a;});