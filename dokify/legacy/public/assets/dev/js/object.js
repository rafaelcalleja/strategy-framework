if(typeof Object.create!="function"){(function(){var a=function(){};Object.create=function(b){if(arguments.length>1){throw Error("Second argument not supported");
}if(b===null){throw Error("Cannot set a null [[Prototype]]");}if(typeof b!="object"){throw TypeError("Argument must be an object");}a.prototype=b;return new a();
};})();}