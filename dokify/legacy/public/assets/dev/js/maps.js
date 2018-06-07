define(function(a){var c="https://maps.googleapis.com/maps/api/js?key=AIzaSyCXk-m2W1u-796B9lFjVWvqbNmlqnNUCZ0&sensor=false&callback=onMapsLoaded";function b(k){var q,d,f,g,m,h,l;
l=[];d=$(k).data("src");q=$(k).data("streetview")==false?false:true;f=$(k).data("polling")||false;activity=(activity=$(k).data("activity"))?$(activity):false;
marker=$(k).data("marker");var n=function(t){if(d){$.getJSON(d,function(v){if($(k).is(":visible")){if(v.hash!=m){e(v.markers,t);if(activity&&v.activity){activity.html(v.activity);
}m=v.hash;}if(f){g=setTimeout(function(){n(false);},5000);}}});}else{if(marker){var s,u;s=new google.maps.LatLng(marker.lat,marker.lng);marker=new google.maps.Marker({map:map,position:s,optimized:false});
map.setCenter(marker.position);map.setZoom(11);}}};function o(){mapTypes=$(k).data("types")||[google.maps.MapTypeId.ROADMAP];map=new google.maps.Map(k,{mapTypeId:google.maps.MapTypeId.ROADMAP,mapTypeControlOptions:{mapTypeIds:mapTypes},streetViewControl:q,styles:[{elementType:"geometry",stylers:[{color:"#ebe3cd"},],},{elementType:"labels.text.fill",stylers:[{color:"#523735"},],},{elementType:"labels.text.stroke",stylers:[{color:"#f5f1e6"},],},{featureType:"administrative",elementType:"geometry.stroke",stylers:[{color:"#c9b2a6"},],},{featureType:"administrative.land_parcel",elementType:"geometry.stroke",stylers:[{color:"#dcd2be"},],},{featureType:"administrative.land_parcel",elementType:"labels.text.fill",stylers:[{color:"#ae9e90"},],},{featureType:"landscape.natural",elementType:"geometry",stylers:[{color:"#dfd2ae"},],},{featureType:"poi",elementType:"geometry",stylers:[{color:"#dfd2ae"},],},{featureType:"poi",elementType:"labels.text.fill",stylers:[{color:"#93817c"},],},{featureType:"poi.park",elementType:"geometry.fill",stylers:[{color:"#a5b076"},],},{featureType:"poi.park",elementType:"labels.text.fill",stylers:[{color:"#447530"},],},{featureType:"road",elementType:"geometry",stylers:[{color:"#f5f1e6"},],},{featureType:"road.arterial",elementType:"geometry",stylers:[{color:"#fdfcf8"},],},{featureType:"road.highway",elementType:"geometry",stylers:[{color:"#f8c967"},],},{featureType:"road.highway",elementType:"geometry.stroke",stylers:[{color:"#e9bc62"},],},{featureType:"road.highway.controlled_access",elementType:"geometry",stylers:[{color:"#e98d58"},],},{featureType:"road.highway.controlled_access",elementType:"geometry.stroke",stylers:[{color:"#db8555"},],},{featureType:"road.local",elementType:"labels.text.fill",stylers:[{color:"#806b63"},],},{featureType:"transit.line",elementType:"geometry",stylers:[{color:"#dfd2ae"},],},{featureType:"transit.line",elementType:"labels.text.fill",stylers:[{color:"#8f7d77"},],},{featureType:"transit.line",elementType:"labels.text.stroke",stylers:[{color:"#ebe3cd"},],},{featureType:"transit.station",elementType:"geometry",stylers:[{color:"#dfd2ae"},],},{featureType:"water",elementType:"geometry.fill",stylers:[{color:"#b9d3c2"},],},{featureType:"water",elementType:"labels.text.fill",stylers:[{color:"#92998d"},],},],});
h=new google.maps.Geocoder();}function p(s,t){t=t||"";return marker=new google.maps.Marker({map:map,title:t,position:s,optimized:false});}function j(){for(i in l){l[i].setMap(null);
}}function e(u,t){j();var y=new google.maps.LatLngBounds();var s=u.length;t=t===undefined?true:t;if(s){for(i in u){var x=u[i];var w=new google.maps.LatLng(x.address[0],x.address[1]);
var v=p(w,x.title);var z=(function(A){return function(){$(document).trigger("location",[A.href]);};})(x);google.maps.event.addListener(v,"click",z);l.push(v);
y.extend(v.position);}}if(t){map.fitBounds(y);if(map.getZoom()>12){map.setZoom(12);}if(!s){map.setZoom(2);}}}function r(){o();n();}if(window.google&&google.maps&&google.maps.Map){r();
}else{window.onMapsLoaded=r;a([c]);}}b.init=function(){return new b(this);};return b;});