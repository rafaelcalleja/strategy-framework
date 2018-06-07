define(["vendor/highcharts/highcharts"],function(){function a(n){var q,h,d,k,s,p,f,l,g,j,e,b,i,o,c,r;s=$(n).data("name")||"";h=$(n).data("src");d=$(n).data("links");
j=$(n).data("colors")||Highcharts.getOptions().colors;q=$(n).data("title")||"";f=$(n).data("type")||"line";p=$(n).data("inner")||"5%";g=$(n).data("tooltip")==false?false:true;
o=$(n).data("animation")==false?false:true;r=(r=$(n).data("titles"))?$(r):false;k=[];b={};i={};c={};g={enabled:g};l={animation:o,dataLabels:{enabled:false,color:"#000000",connectorColor:"#000000",format:"<b>{point.name}</b>: {point.percentage:.1f} %"},pointPadding:0,groupPadding:0,events:{click:function(t){if(t.point.href){$(document).trigger("location",[t.point.href]);
}}}};function m(){var t=r.data("template").replace("%y",this.y).replace("%x",this.x).replace("%s",this.name).replace("%c",this.color.replace("#",""));r.html(t);
}if(r){l.point={events:{mouseOver:m}};}this.load=function(t){t=t||function(){};e={};e[f]={};e[f]=l;if(typeof h=="object"){k=[{type:f,name:s,data:h,innerSize:p}];
t();}else{$.getJSON(h,function(u){if(u.series){k=u.series;}if(u.yAxis){b=u.yAxis;}if(u.xAxis){i=u.xAxis;}if(u.legend){c=u.legend;}if(u.tooltip){g=u.tooltip;
}t();});}};this.create=function(){function t(){var B=Highcharts.getOptions().colors;Highcharts.setOptions({colors:j});if(i.categories){var w=i.categories;
i.labels={formatter:function(){return w[this.value];}};delete (i.categories);}var A=$(h).filter(function(){return this.href;}).length>0;e[f].cursor=A?"pointer":"default";
$(n).highcharts({tooltip:g,chart:{backgroundColor:"rgba(0, 0, 0, 0)",margin:0},title:{text:q},legend:c,yAxis:b,xAxis:i,plotOptions:e,series:k,credits:{enabled:false}});
if(r){var y,z,u;y=$(n).highcharts();if(y&&y.series&&(z=y.series[0])){for(var x=0,v=z.data.length;x<v;x++){u=z.data[x];if(u&&u.y){m.call(u);break;}}}}Highcharts.setOptions({colors:B});
}this.load(t);};}a.init=function(){var b=new a(this);b.create();};return a;});