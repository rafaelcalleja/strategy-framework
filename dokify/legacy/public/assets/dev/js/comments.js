define(["network"],function(c){function b(h,i){var f,g;f=this;this.dom=h;this.uid=$(h).data("uid");this.chain=$(h).find(".chain");this.comment=i;this.checkbox=$(h).find("input[type=checkbox]");
this.label=$(h).find("label");$(this.checkbox).on("click",function(){if(this.checked){f.comment.addRequest(f);}else{f.comment.removeRequest(f);}});}function a(g){var f;
f=this;this.dom=g;this.requestsData={total:0,actives:0,requests:[]};this.infoReply=$(g).find(".info-reply");this.submit=$(g).find(":submit");this.content=$(g).find(".content");
$(this.submit).on("click",function(){f.loading();});$(g).find(".request").each(function(){var h=new b(this,f);f.requestsData.requests.push(h);f.requestsData.total++;
f.requestsData.actives++;});}function d(h,g){var f,i;f=this;this.dom=h;this.uid=$(h).data("uid");this.user=$(h).data("user");this.requests=[];this.replyto=$(h).find(".send-reply");
this.newcomment=g;this.avatar=$(h).find(".avatar");this.editable=$(h).find(".editable");i=$(h).find(".text-comment");this.text=$(i).find("span.data").text();
$(h).find(".request").each(function(){var j=new b(this,f);f.requests.push(j);});$(this.replyto).on("click",function(j){f.reply(f.newcomment);return false;
});$(this.editable).on("click",function(j){j.preventDefault();f.edit();});}a.prototype.loading=function(){$(this.content).addClass("loading");};a.prototype.removeReply=function(){var f;
$(this.infoReply).hide();$(this.infoReply).find(".reply-user").text("");$(this.infoReply).find(".reply-id").val(null);f=$(this.dom).find("textarea");$(f).focus();
$(f).attr("placeholder",$(f).data("placeholder"));};a.prototype.addRequest=function(f){this.requestsData.actives++;this.printRequests();this.removeReply();
};a.prototype.removeRequest=function(f){this.requestsData.actives--;this.printRequests();this.removeReply();};a.prototype.applyCommentRequests=function(h){var g=[],f;
f=this;$.each(h.requests,function(j,k){g.push(k.uid);});f.requestsData.actives=0;$.each(this.requestsData.requests,function(j,k){if(g.indexOf(k.uid)!==-1){$(k.checkbox).prop("checked",true);
f.requestsData.actives++;}else{$(k.checkbox).removeAttr("checked");}});f.printRequests();};a.prototype.printRequests=function(){var i,h;i=$(this.dom).find(".requesters-info");
if(this.requestsData.actives==1){var g,f;$.each(this.requestsData.requests,function(j,l){var k;k=$(l.checkbox).is(":checked")?true:false;if(k){g=l;}});
f=$(g.chain).html();$(i).text("");$(i).append(f);}else{if(this.requestsData.total!=this.requestsData.actives){h=this.requestsData.actives+" requirements";
$(i).text(h);}else{h="all requirements";$(i).text(h);}}};d.prototype.edit=function(){var f;f=$(this.dom).find(".text-comment");if($(f).find("span.data").is(":visible")){this.openEdit();
}else{this.closeEdit();}};d.prototype.openEdit=function(){var g,f,h;g=this;h=$(this.dom).find(".text-comment");f=$(this.dom).find("textarea");$(h).addClass("editing");
$(this.editable).addClass("selected");f.keypress(function(i){var j;j=g.editable.attr("method")||"POST";if(i.keyCode==13){i.preventDefault();g.loading();
$.ajax({type:j,url:$(g.editable).attr("href"),data:"text="+$(this).val(),success:c.parseResponse});}return true;});};d.prototype.closeEdit=function(){var f;
f=$(this.dom).find(".text-comment");$(this.editable).removeClass("selected");$(f).removeClass("editing");$(f).addClass("normal");};d.prototype.loading=function(){var f;
f=$(this.dom).find(".text-comment");$(f).removeClass("editing");$(f).addClass("loading");};d.prototype.reply=function(h){var f,i,g;g=$(this.avatar).attr("src");
$(h.infoReply).show();$(h.infoReply).find(".reply-user").text(this.user);$(h.infoReply).find(".reply-id").val(this.uid);$(h.infoReply).find(".avatar").attr("src",g);
f=$(h.dom).find("textarea");var i=$(f).data("reply-placeholder").replace("%s",this.user);$(f).focus();$(f).attr("placeholder",i);h.applyCommentRequests(this);
};e.prototype.loading=function(){$(this.dom).find("#more-comments").addClass("loading");};e.prototype.seeMoreComments=function(){var g,i,f,h;g=this;h=$(this.dom).find(".see-more");
i=$(this.dom).find("#more-comments");f=$(h).attr("href");this.loading();$.get(f,function(k){$(g.load).removeClass("loading");var j=$("<div>"+k+"</div>");
$(i).replaceWith(j);$(document).trigger("redraw",j);g.addComments();});return false;};e.prototype.addComments=function(){var g=[],f;f=this;$.each(this.comments,function(h,j){g.push(j.uid);
});$(f.dom).find(".commentid").each(function(){var h;h=$(this).data("uid");if(g.indexOf(h)==-1){var i=new d(this,f.newcomment);f.comments.push(i);}});};
function e(g){var f;f=this;this.dom=g;this.comments=[];this.newcomment=new a($(g).find("#new-comment"));$(g).find(".commentid").each(function(){var h=new d(this,f.newcomment);
f.comments.push(h);});$(this.dom).on("click",".see-more",function(h){h.preventDefault();f.seeMoreComments();});}e.init=function(){new e(this);};return e;
});