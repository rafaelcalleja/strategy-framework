define(function(){function a(g){var c=$(g).data("sync"),h=false;function e(j,i){$.ajax({url:c,type:"post",data:{_method:j},error:i,success:function(l,k,m){var n=m.getResponseHeader("Content-type");
if(n.indexOf("application/json")!==-1){$(document).trigger("json:response",[l]);}h=false;}});}function d(){e("put",function(i){$(document).trigger("message",[i.responseText||i.statusText]);
g.checked=false;});}function f(){e("delete",function(i){$(document).trigger("message",[i.responseText||i.statusText]);g.checked=true;});}$(g).change(function(){if(h){g.checked=!g.checked;
return false;}h=true;if(this.checked){d();}else{f();}});}function b(){var c=$(this).attr("type");switch(c){case"checkbox":return a(this);}console.error(c+" not implemented for sync.js");
}return{init:b};});