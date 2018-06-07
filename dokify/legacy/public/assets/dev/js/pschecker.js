(function(a){a.fn.extend({pschecker:function(b){var c=a.extend({minlength:8,maxlength:16,onPasswordValidate:null,onPasswordMatch:null},b);return this.each(function(){var k=a(".repeat-password");
var f=a(".strong-password:eq(0)",k);var e=a(".strong-password:eq(1)",k);e.removeClass("no-match");f.keyup(h).blur(h).focus(h);e.keyup(h).blur(h).focus(h);
function h(){var n=f.val().toString();var q=a(".meter");if(c.onPasswordValidate!=null){c.onPasswordValidate(n.length>=c.minlength);}if(n.length<c.maxlength){q.removeClass("strong").removeClass("acceptable").removeClass("weak");
}if(n.length>0){var s=new RegExp(/^(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{7,30}$/);if(s.test(n)&&n.length>7){q.removeClass("strong").removeClass("acceptable").removeClass("weak");
q.addClass("strong");}else{var r=i(n);var p=d(n);var o=j(n);var m=g(n);var l=r+p+o+m;if(l>2&&n.length>7){q.removeClass("strong").removeClass("acceptable").removeClass("weak");
q.addClass("acceptable");}else{q.removeClass("strong").removeClass("acceptable").removeClass("weak");q.addClass("weak");}}}}function i(m){var l=new RegExp(/[a-z]/);
if(l.test(m)){return 1;}return 0;}function d(m){var l=new RegExp(/[0-9]/);if(l.test(m)){return 1;}return 0;}function j(m){var l=new RegExp(/[A-Z]/);if(l.test(m)){return 1;
}return 0;}function g(n){var m=new RegExp(/[\W]/);var l=new RegExp(/[ ]/);if(l.test(n)){return 0;}else{if(m.test(n)){return 1;}return 0;}}});}});})(jQuery);
