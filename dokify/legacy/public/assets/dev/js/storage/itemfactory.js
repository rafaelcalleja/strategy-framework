define([],function(){function a(){this.defaultTimeout=(1000*60)*60*24;}a.prototype.changeDefaultTimeout=function(b){if(typeof(b)==="number"){this.defaultTimeout=b;
}};a.prototype.create=function(c,b){if(typeof(b)!=="number"){b=this.defaultTimeout;}return{value:c,timestamp:new Date().getTime(),timeout:b};};return a;
});