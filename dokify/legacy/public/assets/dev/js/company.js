define(function(){var a={};a.controller=function a(c,b,d,f,e){e.loading=false;c.summary=d;c.company=b;c.checkins=f;};a.controller.$inject=["$scope","companyData","checkinsSummary","checkinsData","Layout"];
a.templateUrl="/app/mobile/company/show.html";a.resolve={loginData:["Login",function(b){return b.get().$promise;}],companyData:["$route","Company","Layout",function(d,c,b){return c.get({company:d.current.params.company},function(e){b.setTitle(e.name);
}).$promise;}],checkinsSummary:["Checkin",function(b){return b.summary().$promise;}],checkinsData:["Checkin",function(b){return b.query().$promise;}]};
return a;});