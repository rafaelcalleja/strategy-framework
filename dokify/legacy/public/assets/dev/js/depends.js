define(function(){function a(c){var b=this;this.dom=$(c);this.target=this.dom.data("depends-target").find();this.regexp=new RegExp(this.dom.data("depends-regexp"));
this.target.change(function(){b.evaluate();});this.evaluate();}a.prototype.evaluate=function(){var c,b;c=this.target.val();b=this.regexp.test(c);this.dom.toggle(b);
if(false===b){this.dom.find(":checked").removeAttr("checked");}};a.init=function(){return new a(this);};return a;});