define(function(){var a=angular.module("directives",["ngResource"]);a.directive("profiles",["$filter","Layout","Login",function(e,c,d){var b=["$rootScope","$timeout",function(f,g){this.useProfile=f.useProfile=function(i,h){d.get(function(j){var l=e("orderBy");
angular.forEach(j.profiles,function(o,n){j.profiles[n].active=false;if(o.uid.toString()===i.toString()){j.profiles[n].active=true;j.current_profile=j.profiles[n];
j.company=j.current_profile.company;}});j.profiles=l(j.profiles,"active",true);function k(){d.cache({profiles:j.profiles,current_profile:j.current_profile,company:j.company});
m();}function m(){f.login=j;c.toggleProfiles(false);if(h){h();}}d.update({profiles:j.profiles,current_profile:j.current_profile,company:j.company},m,k);
});};this.toggleList=function(h){f.$apply(function(){c.toggleProfiles(h);});};}];return{scope:false,controller:b};}]);a.directive("profile",["$location","$route","Layout",function(e,d,c){function b(h,g,f,i){g.on("click",function(){if(f.active==="true"){i.toggleList();
return;}c.inNav=false;c.loading=true;i.useProfile(f.uid,function(){if(e.path().indexOf("offline")!==-1){d.reload();return;}e.url("/");});});}return{require:"^profiles",scope:false,link:{post:b}};
}]);a.directive("bgImage",function(){return{restrict:"A",link:function(f,e,d){var c=d.bgImage;var b=d.bgImageIf;if(b!==undefined){if(!b||b==="false"){return;
}}if(!c||c==="false"){return;}e.css({"background-image":"url("+c+")"});}};});a.directive("autofocus",["$timeout",function(b){return{restrict:"A",link:function(d,c){b(function(){c[0].focus();
});}};}]);});