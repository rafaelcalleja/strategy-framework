define(function(){function a(d){var c;c=this;this.items=[];if(typeof d!=="undefined"&&d.length){this.items=d;}}a.prototype.num=function(){return $(this.items).toArray().length;
};a.prototype.show=function(){$(this.items).removeClass("last-item hide-item");$(this.items).addClass("show-item");};a.prototype.hide=function(){$(this.items).removeClass("show-item last-item");
$(this.items).addClass("hide-item");};a.prototype.push=function(c){this.items.push(c);};function b(d){var c,f;c=this;this.ul=$(d);this.search=this.ul.find("input[type=search]");
this.status=this.ul.find(".status");this.template="";this.checks=this.ul.find("input[type=checkbox]");this.checked=this.checks.filter(":checked").length;
this.clear=this.ul.find(".clear");this.items=this.ul.find("li.group li[data-text]").toArray();this.number=this.ul.find("input.number");if(this.ul.data("toggle")){$(this.ul.data("toggle")).click(function(){c.toggle();
});}if(this.status.length==0&&this.ul.data("status")){this.status=this.ul.data("status").find();}if(this.items.length==0){this.items=this.ul.find("li.item").toArray();
}if(this.search.length==0){var e=$(this.ul.data("search"));if(e.length>0){this.search=e;}}if(this.status.length){this.template=this.status.data("tpl");
}this.search.keyup(function(g){if(g.keyCode==27){return c.remove();}if(f){clearTimeout(f);}f=setTimeout(function(){c.filter();},200);});this.number.keyup(function(g){c.summarize();
});this.search.keydown(function(g){if(g.keyCode==13){if(f){clearTimeout(f);}g.preventDefault();c.filter();}});this.search.on("input",function(g){if(this.value==""){c.remove();
}});this.clear.click(function(){c.remove();});this.checks.click(function(){c.summarize(this);});if(this.status.html()===""){this.summarize();}}b.prototype.toggle=function(){var c=!$(this.checks.get(0)).is(":checked");
this.checks.attr("checked",c?"checked":null).prop("checked",c);if(c){this.checked=this.checks.length;}else{this.checked=0;}this.summarize();};b.prototype.summarize=function(c){var f,e,d,h,g;
if(c){f=$(c).is(":checked")?1:-1;this.checked=this.checked+f;}g=this.checked;h=this.number.val();d=parseInt(h,10);if(false===isNaN(d)){if(d<g){this.number.addClass("error");
}else{this.number.removeClass("error");g=d;}}else{if(h){this.number.addClass("error");}}if(this.template){e=this.template.replace("%s",g).replace("%s",this.checks.length);
}this.status.html(e);};b.prototype.notify=function(c,e){if(c===0){this.ul.addClass("nodata").removeClass("filtering");}else{this.ul.removeClass("nodata");
if(e===0){this.ul.removeClass("filtering");}else{this.ul.addClass("filtering");}}if(c>0){var d=this.ul.find("li.item.show-item");$(d.get(d.length-1)).addClass("last-item");
}};b.prototype.remove=function(f){if(f){f.preventDefault();}this.search.val("");var c=this.ul.find("li.item");var d=new a(c);d.show();this.notify(1,0);
};b.prototype.filter=function(){var h,g;g=this.search.val().toString().toLowerCase();if(g===""){return;}var d=new a();var f=new a();for(h in this.items){var j,e,c;
c=$(this.items[h]);j=(c.data("text")||c.text()).toString().toLowerCase();e=this.cleanText(j);if(g&&j.indexOf(g)===-1&&e.indexOf(g)===-1){f.push(this.items[h]);
}else{d.push(this.items[h]);}}d.show();f.hide();this.notify(d.num(),f.num());};b.prototype.deleteItem=function(c){var d,f,e;f=c.attr("id");for(d in this.items){e=$(this.items[d]);
idItem=e.attr("id");if(idItem==f){this.items.splice(d,1);}}};b.prototype.addItem=function(c){this.items.push(c.get(0));};b.prototype.cleanText=function(e){var c,d={"á":"a","è":"e","í":"i","ó":"o","ú":"u"};
for(c in d){e=e.replace(c,d[c]);}return e;};b.init=function(){return new b(this);};return b;});