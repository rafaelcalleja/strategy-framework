define(["vendor/typeahead.bundle.min","janddress"],function(){function a(j){var c,b,d,l,i,k,m,h,e,g;c=j.form;j=$(j);b=j.data("search-remote");d=j.data("search-limit")||10;
l=j.data("search-location")||"url";i=null;m={};if(j.data("search-prefetch")==="true"){i=b;}k=j.data("search-template-footer");if(k){m.footer=k.find().html();
}g=j.data("search-template-empty");if(g){m.empty=g.find().html();}h=b.parseURI();h.query.q="%QUERY";b=h.build();function f(){$(c).find(".tt-dropdown-menu").hide();
}e=new Bloodhound({datumTokenizer:function(n){return n.token;},queryTokenizer:Bloodhound.tokenizers.whitespace,prefetch:i,remote:b,limit:d});e.initialize();
j.typeahead({highlight:true,hint:true},{source:e.ttAdapter(),templates:m});j.on("typeahead:selected",function(q,n){if(!n){var p=$(this).data("url-alternate");
if(!p){return;}$(document).trigger("location",[p]);}else{var o=n[l];if(!o){return;}$(document).trigger("location",[o]);j.typeahead("val","");}});$(c).submit(f);
$(document).on("navigate:before",f);$(document).on("navigate:after",f);}a.init=function(){return new a(this);};return a;});