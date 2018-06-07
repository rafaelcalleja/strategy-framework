if(!Array.prototype.findIndex){Array.prototype.findIndex=function(a){if(this===null){throw new TypeError("Array.prototype.findIndex called on null or undefined");
}if(typeof a!=="function"){throw new TypeError("predicate must be a function");}var f=Object(this);var d=f.length>>>0;var b=arguments[1];var e;for(var c=0;
c<d;c++){e=f[c];if(a.call(b,e,c,f)){return c;}}return -1;};}if(!Array.prototype.diff){Array.prototype.diff=function(b){return this.filter(function(a){return b.indexOf(a)<0;
});};}