define(["vendor/highcharts/highcharts"],function(){function a(d){var b=$(d).data("serie-name"),c=$(d).data("serie-src");Highcharts.setOptions({lang:{decimalPoint:",",thousandsSep:"."}});
$.getJSON(c,function(e){$(d).highcharts({chart:{type:"column"},credits:{enabled:false},legend:{enabled:false},title:{text:false},xAxis:{labels:{enabled:false}},yAxis:{title:{text:false}},series:[{name:b,data:e,color:"gray"}]});
});}a.init=function(){return new a(this);};return a;});