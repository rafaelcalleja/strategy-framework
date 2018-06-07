define(function(){var a=function(d){var b,c;this.dom=$(d);this.form=$(d.form);this.state=this.form.find("select[name=state]");this.town=this.form.find("select[name=town]");
this.childs=this.state.add(this.town);b=this;c=this.getValue();this.values={country:c,state:this.state.val(),town:this.town.val()};if(c!==a.SPAIN){this.changed();
}this.dom.on("change",function(){b.changed();});this.state.on("change",function(){b.values.state=$(this).val();b.refreshTown(true);});this.town.on("change",function(){b.values.town=$(this).val();
b.refreshTown(false);});};a.SPAIN=174;a.prototype.getValue=function(){return parseInt(this.dom.val(),10);};a.prototype.changed=function(){var b=this.getValue();
this.values.country=b;if(b===a.SPAIN){this.unlock();}else{this.lock();}if(this.state.hasClass("chosen")){this.state.trigger("chosen:updated");}if(this.town.hasClass("chosen")){this.town.trigger("chosen:updated");
}};a.prototype.unlock=function(){this.childs.removeAttr("disabled");this.state.val(this.values.state);this.town.val(this.values.town);};a.prototype.lock=function(){this.childs.attr("disabled",true).val(0);
};a.prototype.refreshTown=function(c){var b=this.town.data("src");if(b){var d=b.parseURI();d.query.state=this.values.state;b=d.build();this.town.data("src",b);
if(c){this.town.trigger("fetch");}}};a.init=function(){return new a(this);};return a;});