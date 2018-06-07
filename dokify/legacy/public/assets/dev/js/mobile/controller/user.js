define(function(){var a={};a.controller=function(c,b,d,f,e){e.loading=false;c.summary=d;c.user=b;c.checkins=f;};a.controller.$inject=["$scope","userData","checkinsSummary","checkinsData","Layout"];
a.resolve={loginData:["Login",function(b){return b.get().$promise;}],userData:function(d,c,b){return c.once({user:d.current.params.user},function(e){b.setTitle(e.name);
}).$promise;},checkinsSummary:function(d,c,b){return c.once({user:d.current.params.user}).$promise.then(function(e){return b.summary({user:e.uid}).$promise;
});},checkinsData:function(d,c,b){return c.once({user:d.current.params.user}).$promise.then(function(e){return b.query({user:e.uid}).$promise;});}};a.resolve.userData.$inject=["$route","User","Layout"];
a.resolve.checkinsSummary.$inject=["$route","User","Checkin"];a.resolve.checkinsData.$inject=["$route","User","Checkin"];a.templateUrl="/app/mobile/user/show.html";
return a;});