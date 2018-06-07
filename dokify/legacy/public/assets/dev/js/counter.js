define(["form","modal"],function(b,a){function c(f){var j,e,i,d,n;j=[];n=this;d=$(f).find("#num");i=d.data("init")||0;e=$(f).data("counter-filter")||false;
$(f).on("click",".cancel",l);function m(){var p,r,s,q,o;if(e&&$(this).is(e)==false){return;}p=(p=$(this).data("counter-origin"))?$(p):$(this);s=p.attr("id");
q=p.attr("name");r=(r=$(p).data("type"))?r:"selected";o=document.createElement("input");o.type="hidden";o.name=r+"[]";o.value=q;$(f).append(o);j[s]=o;i=i+1;
d.html(i);if(i==1){k();}return true;}function g(){var q,p,o;p=(p=$(this).data("counter-origin"))?$(p):$(this);q=p.attr("id");o=j[q];i=i-1;o.remove();delete (j[q]);
if(i==0){h();}d.html(i);return true;}function l(q){q.preventDefault();var p,o;for(p in j){o=j[p];$("#"+p).trigger("counter:cancel");o.remove();}j=[];i=0;
d.html(i);h();return true;}function h(){$(f).fadeOut();return true;}function k(){$(f).fadeIn();var p=$(window).scrollTop(),o=$(f).position().top-80;if(p>o){$(window).scrollTop(o);
}return true;}$(document).on("click",".counter-add",m);$(document).on("click",".counter-remove",g);}c.init=function(){return new c(this);};return c;});
