(function(b){var a=[];b.janddress={base:"",ready:true,current:"",active:true,src:null,notify:function(d){var c=janddress.getCurrent();if((c&&c==janddress.current)||janddress.active==false){return;
}d=d||{};janddress.current=c;d.value=c;d.target=janddress.src;$.each(a,function(e,f){f(d);});janddress.src=null;},init:function(c){this.base=c;if(this.supportsState()){$(b).bind("popstate",this.notify);
}else{if(this.supportsHashChange()){b.onhashchange=this.notify;if(b.location.hash){var d=this.base+b.location.hash.substring(1);if(d!=b.location.pathname){this.ready=false;
this.notify();}}}else{return false;}}this.current=this.getCurrent();return true;},getCurrent:function(c){var d;if(this.supportsHashChange()){d=b.location.hash.substring(1);
}if(this.supportsState()){d=b.location.pathname.replace(this.base,"")+b.location.search;}if(c===true){return this.base+d;}return d.replace(this.base,"");
},supportsState:function(){return !!b.history.pushState;},supportsHashChange:function(){return"onhashchange" in b;},onChange:function(c){a.push(c);},go:function(d,e){var c=this.getCurrent();
this.src=e;d=d.replace(this.base,"");if(d=="#"){return false;}if(c==d||this.active==false){return false;}if(this.supportsState()){b.history.pushState({},"",this.base+d);
this.notify();}else{if(this.supportsHashChange()){b.location.hash=d;}}return true;},set:function(c){c=c.replace(this.base,"");if(this.supportsState()){b.history.pushState({},"",this.base+c);
}else{if(this.supportsHashChange()){b.onhashchange=function(){};b.location.hash=c;b.onhashchange=this.notify;}}this.current=janddress.getCurrent();}};})(window);
