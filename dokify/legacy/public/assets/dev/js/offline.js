define(function(){var a={};a.controller=function(c,h,g,f,b,e){var d=g.current.params.employee;if(f.length!==1){c.goToProfile=function(i){e.loading=true;
c.useProfile(i.uid,function(){h.path("/employee/"+d+"/offline/"+i.uid);});};c.profiles=[];angular.forEach(f,function(i){c.profiles.push(i.profile);});}if(f.length===1){c.employee=f[0];
}e.loading=false;};a.controller.$inject=["$scope","$location","$route","matches","loginData","Layout"];a.resolve={loginData:["Login",function(b){return b.get().$promise;
}],matches:["$q","$route","$http","Login","Layout",function(c,i,h,g,f){var b=c.defer(),d=i.current.params.employee,e=i.current.params.profile;g.get(function(j){var l=[],k=[];
angular.forEach(j.profiles,function(o){var n,m;n=o.uid.toString();if(e&&e!==n){return;}m="/app/profile/"+n+"/offline.json";promise=h.get(m).then(function(p){p.profile=o;
return p;});k.push(promise);});c.all(k).then(function(m){angular.forEach(m,function(o){if(o.data[d]===undefined){return;}var p=o.data[d].split(",");var n={name:p[0],vat:p[1],ok:p[2]==="1",date:new Date(o.headers("last-modified")),profile:o.profile};
f.setTitle(p[0]);l.push(n);});if(l.length){return b.resolve(l);}b.reject();},function(){b.reject();});});return b.promise;}]};a.templateUrl="/app/mobile/employee/offline.html";
return a;});