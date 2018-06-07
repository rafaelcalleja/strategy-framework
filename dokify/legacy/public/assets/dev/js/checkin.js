define(function(){var a={};a.controller=function(o,h,f,k,i,g,d,m,c){var e=h.search().profile;if(g===403&&i.length===0){o.error=403;m.loading=false;return;
}o.employee=g;o.profiles=false;o.assignmentRelevant=c.query({employee:g.uid});o.goToProfile=function(p){f.eventTrack("Profile change",{category:"Checkin",label:p.name});
m.loading=true;o.useProfile(p.uid,function(){h.search("profile",p.uid);});};o.Checkin=function(p){var q=Math.round((new Date()).getTime()/1000,0);f.eventTrack("Check-in",{category:"Checkin",label:g.name});
k.save({employee:g.uid,company:p,time:q},function(r){h.path("/checkin/"+r.uid);});};if(i===undefined){window.errorLog("no profiles found: "+angular.toJson(d));
}if(i.length===1){var n=i[0];if(n.uid!==d.current_profile.uid){if(!n){throw new Error("invalid profile choosed: "+n);}return o.goToProfile(n);}if(g&&g.checkin){var b,l,j;
b=g.checkin.owner.uid===d.company.uid;l=angular.inCollection(g.checkin.checkin_profile,d.profiles);j=l||g.checkin.is_near;if(b&&j){return h.path("/checkin/"+g.checkin.uid);
}}}else{if(!e&&i.length!==0){o.profiles=i;}}m.loading=false;};a.controller.$inject=["$scope","$location","$analytics","Checkin","profilesData","employeeData","loginData","Layout","AssignmentRelevant"];
a.resolve={loginData:["Login",function(b){return b.get().$promise;}],employeeData:["$q","$location","$route","Employee","Checkin","Layout",function(g,d,i,e,h,j){var b,f;
b=g.defer();f=i.current.params.employee;if(!f){var c=angular.toJson(i.current.params);throw new Error("missing employee parameter ["+c+"]");}e.get({employee:f},function(k){j.setTitle(k.name);
if(!k){b.reject();throw new Error("missing employee data ["+angular.toJson(k)+"]");}if(!k.checkin){b.resolve(k);return;}h.get({checkin:k.checkin},function(l){k.checkin=l;
b.resolve(k);});},function(k){if(k&&k.status===0){d.path("/employee/"+f+"/offline");d.replace();return;}if(k&&k.status===403){return b.resolve(403);}b.reject();
});return b.promise;}],profilesData:["$route","$location","Login","Profile","readOnly",function(i,h,e,b,g){var c,d;c=i.current.params.employee;d=h.search().profile;
if(!c){var f=angular.toJson(i.current.params);throw new Error("missing employee parameter ["+f+"]");}if(g){h.path("/employee/"+c+"/offline");h.replace();
return;}return e.get().$promise.then(function(j){if(d){d=angular.inCollection(parseInt(d,10),j.profiles);return[d];}if(j.profiles!==undefined&&j.profiles.length===1){return j.profiles;
}if(j.profiles===undefined){window.errorLog("no profiles found: "+angular.toJson(j));}return b.query({access:"employee:"+c,scopes:"uid,name,company.name,company.shortname,company.logo"}).$promise["catch"](function(k){h.path("/employee/"+c+"/offline");
h.replace();});});}]};a.templateUrl="/app/mobile/employee/checkin.html";return a;});