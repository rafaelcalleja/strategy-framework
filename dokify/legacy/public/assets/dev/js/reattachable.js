define(function(){function a(i){var c=this,h=$(i).attr("href"),b=$("#file-upload-form"),e=$("#file"),d=$(i).data("success"),g=$(i).data("display"),f=$(i).data("display-status");
$(i).click(function(j){j.preventDefault();c.reattach(h,b,e,d,g,f);});}a.prototype.reattach=function(h,b,e,d,g,f){var c;e.trigger("progress",[NaN]);$.ajax({url:h,type:"POST",success:function(i){i.display=g;
i["display-status"]=f;$.ajax({url:d,type:"post",data:i,dataType:"json",success:function(k,j,l){e.trigger("progress",[100]);e.trigger("complete",[k,j,l]);
$(document).trigger("json:response",[k]);},error:function(k,j){e.trigger("error",["",j,k]);}});$.each(i,function(j,k){c=b.find("input[name=file\\["+j+"\\]]");
if(0===c.length){c=$(document.createElement("input")).attr({type:"hidden",name:"file["+j+"]"}).insertAfter(e);}c.val(k);});}});};a.init=function(){return new a(this);
};return a;});