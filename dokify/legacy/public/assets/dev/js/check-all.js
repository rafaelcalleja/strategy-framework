define(function(){function a(c){var b=this;this.dom=c;this.target=$(c).data("target");this.countTarget=$(c).data("count-target");this.countTemplate=$(this.countTarget).html();
this.checkboxList=$(this.target).find(":checkbox");this.checkboxListLength=this.checkboxList.length;this.updateCountTemplate();$(this.countTarget).show();
$(c).on("change",function(){b.checkboxList.each(function(){$(this).prop("checked",$(c).prop("checked"));});b.updateCountTemplate();});$(this.checkboxList).on("change",function(){b.updateCheckAllCheckbox();
b.updateCountTemplate();});}a.prototype.updateCountTemplate=function(){var b=$(this.target).find(":checkbox:checked").length;$(this.countTarget).html(this.countTemplate.replace("%s",b));
};a.prototype.updateCheckAllCheckbox=function(){var b=$(this.target).find(":checkbox:checked").length;$(this.dom).prop("checked",b===this.checkboxListLength);
};a.init=function(){return new a(this);};return a;});