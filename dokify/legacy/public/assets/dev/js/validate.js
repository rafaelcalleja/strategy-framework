define(function(){function a(f){var c,d,b,e;d=$(f).find("button[value=reject]");b=$(f).find("button[value=validate]");$(f).find("input").click(function(){var g=$(this).attr("class");
if(this.checked){if(g.indexOf("Delayed")===-1){if(g.indexOf("Validated")!==-1){b.attr({disabled:true});b.attr("title","You have selected a validated request");
}if(g.indexOf("Rejected")!==-1){d.attr({disabled:true});d.attr("title","You have selected a rejected request");}}}else{if(g.indexOf("Delayed")===-1){if(g.indexOf("Validated")!==-1){if($(f).find("input.Validated:checked").not(this).length==0){b.removeAttr("disabled title");
}}if(g.indexOf("Rejected")!==-1){if($(f).find("input.Rejected:checked").not(this).length==0){d.removeAttr("disabled title");}}}}});}a.init=function(){return new a(this);
};return a;});