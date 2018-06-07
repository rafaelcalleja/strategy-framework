define(["modal"],function(c){var d=$("#tasks"),b=$("#task-async").html();function a(e){this.id=e;this.fails=0;this.endpoint=a.endpoint+"/"+this.id;}a.endpoint="/app/taskhandler";
a.prototype.init=function(){this.consume();};a.prototype.writeSync=function(f){var e="";e+='<span class="spinner middle"></span>';e+=" &nbsp ";e+=f;c.show(e);
};a.prototype.getAsyncNode=function(){if(this.node!==undefined){return this.node;}this.node=$("<div>"+b+"</div>").css("display","none");this.node.appendTo(d);
return this.node;};a.prototype.writeAsync=function(e){var h,f,g=b;e=$.parseJSON(e);h=this.getAsyncNode();f=e.progress;if(f>100){f=100;}g=g.replace("%title%",e.title);
g=g.replace("%message%",e.message);g=g.replace("%progress%",f);h.html(g).show();};a.prototype.message=function(e){if(e.type==="sync"){this.writeSync(e.body);
}if(e.type==="async"){this.writeAsync(e.body);}};a.prototype.done=function(e){if(e.type==="sync"){if(e.body){this.writeSync(e.body);}setTimeout(function(){c.hide();
},1000);}if(e.type==="async"){this.node.remove();}};a.prototype.error=function(e){e=e||"Unknown error!";if(this.node===undefined){return this.writeSync(e);
}var f=this.getAsyncNode();f.find(".task-progress").hide();f.find(".task-message").html('<strong class="red">'+e+"</strong>");setTimeout(function(){f.remove();
},2000);};a.prototype.onMessage=function(e){if(typeof e==="string"){log(e);return this.consume();}if(typeof e!=="object"){return this.error(e);}if(e.code===100){this.message(e);
this.consume();}if(e.code===200){this.done(e);}if(e.code===500){this.error(e);}};a.prototype.consume=function(){var e=this;var f=$.getJSON(this.endpoint);
f.then(function(g){e.onMessage(g);});f.fail(function(){if(e.fails>3){return e.error();}e.fails++;this.consume();});};return a;});