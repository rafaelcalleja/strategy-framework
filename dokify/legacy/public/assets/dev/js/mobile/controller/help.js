define(function(){var a={};a.controller=function(b,d,c){b.help=c;d.loading=false;};a.controller.$inject=["$scope","Layout","help"];a.resolve={loginData:["Login",function(b){return b.get().$promise;
}],help:["$q","$http","Layout","localStorageService",function(c,f,e,d){var b=c.defer();f.get("/app/help").success(function(g){d.set("help",g);e.setTitle(g.title);
b.resolve(g);}).error(function(h){var g=d.get("help",false);if(g){return b.resolve(g);}b.reject();});return b.promise;}]};a.templateUrl="/app/mobile/help/show.html";
return a;});