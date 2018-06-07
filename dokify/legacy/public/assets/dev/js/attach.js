define(["modal"],function(i){function j(l){var k=this;this.dom=l;this.id=$(l).attr("id");this.name=$(l).attr("name");this.uid=$(l).attr("value");this.hidden=false;
this.dom.on("change",function(){k.uid=$(this).val();});}j.prototype.enable=function(){$(this.dom).removeAttr("disabled");$(this.hidden).remove();$(this.dom).val(this.uid||"");
};j.prototype.disable=function(k){var l;$(this.dom).attr("disabled",true);if(k){$(this.dom).val(k);}this.hidden=$("<input>").attr({type:"hidden",name:this.name,value:k});
$("#"+this.id).before(this.hidden);};function e(m,l){var k;k=this;this.dom=m;this.name=$(m).attr("name");this.uid=$(m).attr("value");this.checked=$(m).is(":checked");
this.request=l;$(m).on("click",function(n){k.selected();});}e.prototype.selected=function(){this.checked=true;this.request.autoExpiration.current=this;
};function f(m,l){var k=this;this.dom=m;this.uid=$(m).val();this.name=$(m).text();this.checked=$(m).attr("selected");this.organization=l;}function h(m,l){var k=this;
this.dom=m;this.uid=$(m).data("uid");this.groups={};this.multiple=$(m).attr("multiple");this.request=l;$(m).find("option").each(function(){var n=new f(this,k);
k.groups[n.uid]=n;});$(m).on("click",function(n){k.selected();});}h.prototype.selected=function(){var k,l,m;k=this;m=$(this.dom).find("option:selected");
m.each(function(){l=$(this).val();group=k.findGroup(l);group.checked=true;});};h.prototype.findGroup=function(k){var l;if(l=this.groups[k]){return l;}return false;
};h.prototype.enable=function(){$(this.dom).removeAttr("disabled");};h.prototype.disable=function(){$.each(this.groups,function(){$(this.dom).removeAttr("selected");
});$(this.dom).attr("disabled",true);this.groups={};};function g(m,l){var k=this;this.dom=m;this.uid=$(m).data("uid");this.request=l;this.notApply=false;
this.message=false;this.organization=new h($(m).find("select").first(),k);$(m).find("input").on("click",function(){k.message=$(this).data("alert");k.selected(this);
});}g.prototype.selected=function(k){this.notApply=$(k).is(":checked")?true:false;if(this.notApply){this.organization.disable();this.showComment();}else{this.organization.enable();
this.hideComment();}};g.prototype.showComment=function(){var l,k;l=$(this.request.client.attach.comment);if(this.message){alert(this.message);}l.show();
l.find("textarea").focus();};g.prototype.hideComment=function(){$(this.request.client.attach.comment).hide();};function c(m,l){var k=this;this.dom=m;this.request=l;
this.notExpiring=false;this.date=new j($(m).find("input[type=text]").first());$(m).find("input[type=checkbox]").on("click",function(){k.selected(this);
});}c.prototype.selected=function(k){this.notExpiring=$(k).is(":checked")?true:false;if(this.notExpiring){this.date.disable($(k).data("express-message"));
}else{this.date.enable();}};function a(n,k){var l,m;l=this;this.dom=n;this.uid=$(n).data("uid");this.label=$(n).find("input[type=checkbox].request+label");
this.checkbox=$(n).find("input[type=checkbox].request");this.status=$(n).data("status");this.multiple=$(n).data("multiple")==1;this.checked=this.checkbox.is(":checked");
this.disabled=false;this.extraInfo=$(this.dom).find(".pull-down").first();this.attachment=$(n).data("attachment");this.autoExpiration={set:[],current:null};
this.autoOrg=new g($(n).find("section.auto-organization").first(),l);this.manualExpiration=new c($(n).find("section.manual-expiration").first(),l);this.client=k;
$(n).find("input[type=radio].auto-expiration").each(function(){var o=new e(this,l);l.autoExpiration.set.push(o);});this.checkbox.on("click",function(p){var o=$(l.checkbox).is(":checked");
if(l.checked===o){return true;}if(o){return l.selected();}else{return l.unselected();}});}a.prototype.selected=function(){var k=this.client.attach.lockClient;
if(k&&this.client!=k&&this.client.attach.express.value==false){alert(this.client.attach.express.message);return false;}this.checked=true;this.showExtra();
this.client.attach.totalChecked+=1;this.client.attach.lockClient=this.client;this.client.attach.selected(this);if(this.client.attach.totalChecked==1){this.client.attach.requestChecked(this);
}};a.prototype.unselected=function(){this.checked=false;this.hideExtra();this.client.attach.totalChecked-=1;this.client.attach.selected(this);if(this.client.attach.totalChecked==0){this.client.attach.requestUnchecked(this);
}};a.prototype.showExtra=function(){this.checked=true;$(this.extraInfo).fadeIn();};a.prototype.hideExtra=function(){this.checked=false;$(this.extraInfo).fadeOut();
};a.prototype.enable=function(){this.disabled=false;$(this.checkbox).removeAttr("disabled");if($(this.dom).hasClass("source")){$(this.dom).removeClass("source");
}};a.prototype.disable=function(k){this.disabled=true;$(this.checkbox).prop("disabled",true);$(this.checkbox).prop("checked",false);if(k){this.setAsSource();
}};a.prototype.setAsSource=function(){var k=this;$(this.dom).addClass("source");$.each(this.client.attach.requests,function(l,m){if(m.uid==k.uid){return true;
}if(m.multiple==false){return m.disable();}});};function b(m,l){var k=this;this.dom=m;this.uid=$(m).data("uid");this.request=[];this.attach=l;$(m).find("li.request").each(function(){var n=new a(this,k);
k.request.push(n);k.attach.requests.push(n);});}function d(l){var k;k=this;this.dom=l;this.clients=[];this.requests=[];this.date=$(l).find("#datefile");
this.re=$(l).find("#reattach");this.file=$(l).find("#file");this.submit=$(l).find("button.submit");this.model=$(l).data("model");this.vat=$(l).find("#vat");
this.comment=$(l).find("#comment");this.uploadToAllClients=$(l).find("#all-clients");this.clientsCheckboxes=$(l).find(":input[name='clients[]']");this.attachment=null;
this.locked=true;this.steps={one:$(this.dom).find("section.step.one .circle").first(),two:$(this.dom).find("section.step.two .circle").first(),three:$(this.dom).find("section.step.three .circle").first()};
this.errors={date:$(this.dom).find(".step-error.date").first(),request:$(this.dom).find(".step-error.request").first(),group:$(this.dom).find(".step-error.group").first(),comment:$(this.dom).find(".step-error.explain-comment").first(),expiredate:$(this.dom).find(".step-error.expiredate").first(),vat:$(this.dom).find(".step-error.vat").first()};
this.express={value:$(l).data("express"),message:$(l).data("express-message")};this.totalChecked=0;this.lockClient=false;$(l).find("div.client").each(function(){var m=new b(this,k);
k.clients.push(m);});$(this.file).on("complete",function(n,m){k.fileChanged();});$(this.file).on("change",function(){k.reset();});$(this.date).on("change",function(){k.dateChanged(this);
});$(this.re).on("click",function(m){k.getRettachables(m);});$(this.submit).on("click",function(m){k.checkSubmit(this);return false;});$(this.vat).on("change",function(m){k.vatUpdated(this);
});$(this.uploadToAllClients).on("change",function(){if(this.checked){k.clientsCheckboxes.prop("checked",true);}k.clientsCheckboxes.prop("disabled",function(){return !$(this).prop("disabled");
});$(k.clientsCheckboxes).trigger("change");});$(this.clientsCheckboxes).on("change",function(){var m=$(this).closest(".client"),o=m.find(":input[name='requests[]']"),n=m.find(":input[name='requests[]']:hidden");
o.prop("checked",this.checked);n.prop("disabled",!this.checked);});$(l).on("error",function(n,m){k.erroClear();switch(m.name){case"date":k.errorDate(m);
break;case"request":k.errorRequest(m);break;case"group":k.errorGroup(m);break;case"comment":k.errorComment(m);break;case"expiredate":k.errorExpireDate(m);
break;case"vat":k.errorVat(m);break;}return false;});this.renderLockCheckbox();}d.prototype.errorDate=function(k){if(this.model=="large"){this.steps.two.removeClass("done");
}else{this.steps.one.removeClass("done");}this.errors.date.html(k.message);this.checkLock();};d.prototype.errorRequest=function(k){if(this.model=="large"){this.steps.three.removeClass("done");
}else{this.steps.two.removeClass("done");}this.errors.request.html(k.message);this.checkLock();};d.prototype.errorGroup=function(k){this.errors.group.html(k.message);
};d.prototype.errorComment=function(k){this.errors.comment.html(k.message);};d.prototype.erroClear=function(){this.errors.date.add(this.errors.request).add(this.errors.group).add(this.errors.comment).html("");
};d.prototype.errorExpireDate=function(k){this.errors.expiredate.html(k.message);};d.prototype.errorVat=function(k){this.errors.vat.html(k.message);};d.prototype.checkSubmit=function(k){if(this.locked==true){return;
}$(k).submit();};d.prototype.checkLock=function(){if(this.stepsCompleted()){this.locked=false;$(this.submit).removeClass("disabled");}else{this.locked=true;
$(this.submit).addClass("disabled");}};d.prototype.stepsCompleted=function(){if(this.model=="large"){return $(this.steps.one).hasClass("done")&&$(this.steps.two).hasClass("done")&&$(this.steps.three).hasClass("done");
}else{return $(this.steps.one).hasClass("done")&&$(this.steps.two).hasClass("done");}};d.prototype.reset=function(l){var k;this.steps.one.removeClass("done");
this.setDate();this.checkLock();if(this.attachment){if(k=this.findByAttachment(this.attachment)){this.attachment=null;k.enable();}}};d.prototype.getRettachables=function(m){var k=this,l;
m.preventDefault();l=$(m.currentTarget).attr("href");$.get(l,function(o){var n;i.show(o).find("button.reattach").on("click",function(p){p.preventDefault();
i.hide(p);k.reAttach(this);});});};d.prototype.setDate=function(k){var l="date-aux",m;if(k){this.date.val(k).attr("disabled",true);this.steps.two.addClass("done");
m=$(document.createElement("input"));m.attr({type:"hidden",name:"date",id:l});m.val(k);m.insertBefore(this.date);}else{$(this.dom).find("#"+l).remove();
this.date.removeAttr("disabled").val("");this.steps.two.removeClass("done");}};d.prototype.reAttach=function(m){var l,n,o,k;l=this;href=$(m).data("href");
o=$(m).data("uid");n=$(this.re).data("display");$(this.re).trigger("progress",[NaN]);$.ajax({url:href,type:"GET",success:function(r,p){var q,u,s,t;u=$(l.file).attr("name");
$(l.re).trigger("complete",[r]);$(l.re).trigger("progress",[100]);if(r.path&&r.name){k=$(document.createElement("a")).attr({href:"/app/download?path="+r.path+"&name="+r.name,target:"_blank"});
k.html(r.name);$(n).html(k).show();l.reset();if(r.date){l.setDate(r.date);delete r.date;}$.each(r,function(v,w){s=$(l.dom).find("input[name="+u+"\\["+v+"\\]]");
if(s.length==0){s=$(document.createElement("input")).attr({type:"hidden",name:u+"["+v+"]",}).insertAfter(l.file);}s.val(w);});if(t=l.findByAttachment(o)){l.fileChanged(o);
t.disable(true);}else{l.fileChanged();}}else{$(n).html("Error").show();}}});};d.prototype.vatUpdated=function(k){var l=$(k).data("step");if($(k).val().length>0){this.steps[l].addClass("done");
}else{this.steps[l].removeClass("done");}this.checkLock();};d.prototype.fileChanged=function(l){this.steps.one.addClass("done");this.checkLock();if(l){this.attachment=l;
}else{var k=$(this.file).attr("name");$(this.dom).find("input[name="+k+"\\[attachment\\]]").remove();this.attachment=null;}this.selected();};d.prototype.dateChanged=function(k){var l=$(k).data("step");
this.steps[l].addClass("done");this.checkLock();};d.prototype.requestChecked=function(l){var k=$(l.dom).data("step");this.steps[k].addClass("done");this.checkLock();
};d.prototype.requestUnchecked=function(l){var k=$(l.dom).data("step");this.steps[k].removeClass("done");this.lockClient=false;this.checkLock();};d.prototype.renderLockCheckbox=function(o){var l,n,m,k;
l=this;n=$(this.dom).find(".request input[type=checkbox]:checked");n.each(function(){k=$(this).val();m=l.clients.length;while(m--){client=l.clients[m];
requestLength=client.request.length;while(requestLength--){request=client.request[requestLength];if(request.uid==k){request.selected();l.selected(request);
}}}});};d.prototype.selected=function(p){var l=this,n,m,k,o;n=this.clients.length;while(n--){k=this.clients[n];m=k.request.length;while(m--){o=k.request[m];
if(p&&o.uid==p.uid){continue;}if(p&&p.checked){if(o.multiple==false||p.multiple==false){o.disable();}}else{if(this.totalChecked==0){if(o.disabled){if(l.attachment){if(o.attachment!=l.attachment&&o.multiple==true){o.enable();
}}else{o.enable();}}}}}}};d.prototype.findByAttachment=function(m){var n,l,k,o;n=this.clients.length;while(n--){k=this.clients[n];l=k.request.length;while(l--){o=k.request[l];
if(o.attachment===m){return o;}}}};d.init=function(){new d(this);$(this).on("keypress",function(k){if(k.keyCode==13){k.preventDefault();return false;}});
};return d;});