(function(b,a,c){a.module("ngCookies",["ng"]).factory("$cookies",["$rootScope","$browser",function(j,l){var k={},e={},f,h=false,d=a.copy,g=a.isUndefined;
l.addPollFn(function(){var m=l.cookies();if(f!=m){f=m;d(m,e);d(m,k);if(h){j.$apply();}}})();h=true;j.$watch(i);return k;function i(){var n,o,p,m;for(n in e){if(g(k[n])){l.cookies(n,c);
}}for(n in k){o=k[n];if(!a.isString(o)){o=""+o;k[n]=o;}if(o!==e[n]){l.cookies(n,o);m=true;}}if(m){m=false;p=l.cookies();for(n in k){if(k[n]!==p[n]){if(g(p[n])){delete k[n];
}else{k[n]=p[n];}m=true;}}}}}]).factory("$cookieStore",["$cookies",function(d){return{get:function(e){var f=d[e];return f?a.fromJson(f):f;},put:function(e,f){d[e]=a.toJson(f);
},remove:function(e){delete d[e];}};}]);})(window,window.angular);